const test = require('node:test');
const assert = require('node:assert/strict');
const { boot, fakeFile, chooseFile, IMPORT_HTML, BACKUPS_HTML } = require('./harness');

const GB = 1024 ** 3;
const field = (call, k) => call.data.get(k);

test('a 2 GB archive is accepted: no size gate, no upsell', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML });
  const file = fakeFile(ctx.w, 'big-site.wpress', 2 * GB);
  await chooseFile(ctx, file);

  assert.equal(ctx.statuses.filter((s) => s.type === 'pro').length, 0, 'must not show the paid-extension modal');
  assert.equal(ctx.statuses.filter((s) => s.type === 'error').length, 0, JSON.stringify(ctx.statuses.filter((s) => s.type === 'error')));
  assert.equal(ctx.uploads().length, Math.ceil((2 * GB) / 1048576));
});

test('chunks are contiguous, bounded, and cover the file exactly', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML });
  const size = 5 * 1048576 + 12345;   // not a multiple of the chunk size
  const file = fakeFile(ctx.w, 'a.wpress', size);
  await chooseFile(ctx, file);

  let expectedOffset = 0;
  for (const [s, e] of file.ranges) {
    assert.equal(s, expectedOffset, 'no gap, no overlap');
    assert.ok(e - s <= 1048576 && e > s, 'chunk bounded by server chunk_size');
    expectedOffset = e;
  }
  assert.equal(expectedOffset, size);
  const ups = ctx.uploads();
  assert.deepEqual(ups.map((u) => Number(field(u, 'chunk_offset'))), file.ranges.map((r) => r[0]));
  assert.ok(ups.every((u) => field(u, 'chunk_total') === String(size)));
  assert.deepEqual(ups.map((u) => Number(field(u, 'chunk_size'))), file.ranges.map(([start, end]) => end - start));
  assert.ok(ups.every((u) => field(u, 'secret_key') === 'KEY' && field(u, 'priority') === '5'));
  assert.ok(ups.every((u) => /^[a-z0-9]{12}$/.test(field(u, 'storage')) && field(u, 'archive') === 'a.wpress'));
});

test('WordPress-localized string chunk size stays bounded after the first chunk', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML, globals: {
    shm_uploader: { chunk_size: '1048576', max_retries: '3', url: '/wp-admin/admin-ajax.php?action=shm_import', params: { priority: '5', secret_key: 'KEY' } },
  } });
  const file = fakeFile(ctx.w, 'wordpress.wpress', 1024 ** 3 + 123);
  await chooseFile(ctx, file);
  assert.equal(ctx.uploads().length, Math.ceil(file.size / 1048576));
  let offset = 0;
  for (const [start, end] of file.ranges) {
    assert.equal(start, offset);
    assert.ok(end - start <= 1048576, `chunk @${start} is ${end - start} bytes`);
    offset = end;
  }
  assert.equal(offset, file.size);
});

test('after the last chunk the import pipeline starts', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML });
  await chooseFile(ctx, fakeFile(ctx.w, 'a.wpress', 1500000));
  const starts = ctx.calls.filter((c) => c.url === ctx.uploadUrl && Array.isArray(c.data));
  assert.equal(starts.length, 1, 'model.start() posts once, after the upload completes');
  const names = starts[0].data.map((p) => p.name);
  assert.ok(names.includes('secret_key') && names.includes('storage') && names.includes('archive'));
  const params = Object.fromEntries(starts[0].data.map(({ name, value }) => [name, value]));
  assert.equal(params.storage, field(ctx.uploads()[0], 'storage'));
  assert.equal(params.archive, field(ctx.uploads()[0], 'archive'));
  assert.equal(params.upload_size, 1500000);
});

test('an empty HTTP 200 is retried at the same offset before the pipeline starts', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML, uploadOutcomes: [{ status: 200, body: '' }] });
  await chooseFile(ctx, fakeFile(ctx.w, 'a.wpress', 1500000));
  assert.deepEqual(ctx.uploads().map((u) => Number(field(u, 'chunk_offset'))), [0, 0, 1048576]);
  assert.equal(ctx.statuses.filter((s) => s.type === 'error').length, 0);
});

for (const body of ['null', '{}', '{"errors":[],"size":10}', '{"errors":[{"message":"Host rejected upload"}],"size":1500000}']) {
  test(`an invalid upload acknowledgement stops before validation: ${body}`, async () => {
    const ctx = await boot('import.min.js', { html: IMPORT_HTML, uploadOutcomes: [{ status: 200, body }] });
    await chooseFile(ctx, fakeFile(ctx.w, 'a.wpress', 1500000));
    assert.equal(ctx.uploads().length, 1);
    assert.equal(ctx.calls.filter((c) => c.url === ctx.uploadUrl && Array.isArray(c.data)).length, 0);
    assert.ok(ctx.statuses.some((s) => s.type === 'error'));
    if (body.includes('Host rejected upload')) assert.equal(ctx.statuses.find((s) => s.type === 'error').message, 'Host rejected upload');
  });
}

test('repeated non-JSON responses fail with bounded retries and never start validation', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML, uploadOutcomes: Array.from({ length: 10 }, () => ({ status: 200, body: '<html>Login</html>' })) });
  await chooseFile(ctx, fakeFile(ctx.w, 'a.wpress', 1500000));
  assert.equal(ctx.uploads().length, 4);
  assert.equal(ctx.calls.filter((c) => c.url === ctx.uploadUrl && Array.isArray(c.data)).length, 0);
  assert.equal(ctx.statuses.find((s) => s.type === 'error').message, 'gave up');
});

test('zero-byte file still sends one well-formed chunk', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML });
  await chooseFile(ctx, fakeFile(ctx.w, 'empty.wpress', 0));
  assert.equal(ctx.uploads().length, 1);
  assert.equal(field(ctx.uploads()[0], 'chunk_total'), '0');
});

test('HTTP 413 halves the chunk and resends the same bytes', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML, uploadOutcomes: [{ status: 413 }, { status: 413 }] });
  const file = fakeFile(ctx.w, 'a.wpress', 3 * 1048576);
  await chooseFile(ctx, file);
  const sizes = file.ranges.map(([s, e]) => e - s);
  assert.equal(sizes[0], 1048576);
  assert.equal(sizes[1], 524288);
  assert.equal(sizes[2], 262144);
  assert.equal(file.ranges[1][0], 0);          // resent from the same offset
  assert.equal(file.ranges[2][0], 0);
  assert.equal(file.ranges.at(-1)[1], 3 * 1048576, 'still covers the whole file');
  assert.equal(ctx.statuses.filter((s) => s.type === 'error').length, 0);
});

test('chunk never shrinks below 64 KB and then fails cleanly', async () => {
  const outcomes = Array.from({ length: 20 }, () => ({ status: 413 }));
  const ctx = await boot('import.min.js', { html: IMPORT_HTML, uploadOutcomes: outcomes });
  const file = fakeFile(ctx.w, 'a.wpress', 2 * 1048576);
  await chooseFile(ctx, file);
  assert.ok(Math.min(...file.ranges.map(([s, e]) => e - s)) >= 65536);
  assert.ok(ctx.uploads().length <= 12, 'bounded attempts, no infinite loop');
});

test('transient 5xx / network errors retry the SAME chunk with backoff, then succeed', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML, uploadOutcomes: [{ status: 502 }, { status: 0 }] });
  const file = fakeFile(ctx.w, 'a.wpress', 2000000);
  await chooseFile(ctx, file);
  const offsets = ctx.uploads().map((u) => Number(field(u, 'chunk_offset')));
  assert.deepEqual(offsets.slice(0, 3), [0, 0, 0], 'first chunk attempted 3 times');
  assert.deepEqual(ctx.delays.slice(0, 2), [1000, 2000], 'exponential backoff');
  assert.equal(ctx.statuses.filter((s) => s.type === 'error').length, 0);
  assert.equal(offsets.at(-1), 1048576);
});

test('gives up after max_retries with a clear message and does not skip ahead', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML, uploadOutcomes: Array.from({ length: 10 }, () => ({ status: 503 })) });
  await chooseFile(ctx, fakeFile(ctx.w, 'a.wpress', 2000000));
  assert.equal(ctx.uploads().length, 4, '1 try + 3 retries (max_retries=3)');
  const err = ctx.statuses.find((s) => s.type === 'error');
  assert.equal(err.message, 'gave up');
  assert.ok(ctx.uploads().every((u) => field(u, 'chunk_offset') === '0'));
});

test('4xx (e.g. 409 out of sequence) is not retried and shows the server reason', async () => {
  const body = JSON.stringify({ errors: [{ code: 409, message: 'Upload out of sequence' }] });
  const ctx = await boot('import.min.js', { html: IMPORT_HTML, uploadOutcomes: [{ status: 200 }, { status: 409, body }] });
  await chooseFile(ctx, fakeFile(ctx.w, 'a.wpress', 3 * 1048576));
  assert.equal(ctx.uploads().length, 2);
  assert.equal(ctx.statuses.find((s) => s.type === 'error').message, 'Upload out of sequence');
});

test('non-.wpress files are still rejected before any upload', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML });
  await chooseFile(ctx, fakeFile(ctx.w, 'notes.zip', 100));
  assert.equal(ctx.uploads().length, 0);
  assert.equal(ctx.statuses.find((s) => s.type === 'error').message, 'bad extension');
});

test('drag-and-drop uses the same chunked path', async () => {
  const ctx = await boot('import.min.js', { html: IMPORT_HTML });
  const file = fakeFile(ctx.w, 'dropped.wpress', 3 * GB);
  const ev = ctx.$.Event('drop');
  ev.originalEvent = { preventDefault() {}, dataTransfer: { files: { item: () => file } } };
  ctx.$('#shm-drag-drop-area').trigger(ev);
  await ctx.settle();
  assert.equal(ctx.statuses.filter((s) => s.type === 'pro').length, 0);
  assert.equal(ctx.uploads().length, Math.ceil((3 * GB) / 1048576));
});

test('Restore starts an import of the stored backup and skips the upload step', async () => {
  const ctx = await boot('backups.min.js', { html: BACKUPS_HTML });
  ctx.$('.shm-backup-restore').trigger('click');
  assert.equal(ctx.statuses.filter((s) => s.type === 'pro').length, 0, 'no purchase prompt');
  const start = ctx.calls.find((c) => Array.isArray(c.data) && c.data.some((p) => p.name === 'shm_manual_restore'));
  assert.ok(start, 'an import request was sent');
  const p = Object.fromEntries(start.data.map((x) => [x.name, x.value]));
  assert.equal(p.archive, 'site-20261103-120000-0123456789abcdef.wpress');
  assert.equal(String(p.shm_manual_restore), '1');
  assert.equal(String(p.priority), '10', 'starts after the upload step (priority 5)');
  assert.match(p.storage, /^[a-z0-9]{12}$/);
  assert.equal(p.secret_key, 'KEY');
});
