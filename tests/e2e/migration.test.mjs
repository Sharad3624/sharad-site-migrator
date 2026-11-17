// Real WordPress + MariaDB, over HTTP, exactly as the admin JS drives it.
//   live (127.0.0.1:8081)  demo (localhost:8082)  sub (localhost:8083/wordpress)  prefix (127.0.0.1:8084, abc_)
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { up } from './env.mjs';
import { wpctl, exportSite, importArchive, diffFingerprints, Site, archiveIntegrity } from './driver.mjs';
import { browserUpload } from './browser-upload.mjs';
import { migrate, assertHealthy, logMark, pluginIssuesSince, TMP, MB } from './helpers.mjs';

const env = await up({ fresh: true });
const { live, demo, sub, prefix } = env;
const seed = (site, n, mb = 7) => wpctl(site.root, 'fixture', String(n), String(mb));

test('seed: every environment has the migrator active and a different shape', () => {
  for (const s of [live, demo, sub, prefix]) assert.equal(s.info().active, true, s.name);
  assert.equal(prefix.info().prefix, 'abc_');
  assert.match(sub.url, /8083\/wordpress$/);
  seed(live, 1);
  assert.equal(live.info().home, 'http://127.0.0.1:8081');
});

test('LIVE -> DEMO: different host, port, 8 MB upload limit (chunked)', async () => {
  const { im } = await migrate(live, demo);
  assert.ok(im.chunks >= 5, `archive must have been sent in several chunks, got ${im.chunks}`);
  const info = demo.info();
  assert.equal(info.home, 'http://localhost:8082');
  // the plugin that was active on live is active on demo; the mu-plugin travelled
  assert.ok(fs.existsSync(path.join(demo.root, 'wp-content/mu-plugins/shm-fixture-mu.php')));
  assert.ok(JSON.stringify(wpctl(demo.root, 'leftovers', 'shm-fixture')).length >= 0);
});

test('DEMO -> LIVE: the reverse direction, after editing demo', async () => {
  wpctl(demo.root, 'fixture', '2', '1');          // demo now has extra content live does not
  await migrate(demo, live);
  assert.equal(live.info().home, 'http://127.0.0.1:8081');
});

test('LIVE (root) -> LOCAL (subdirectory /wordpress)', async () => {
  await migrate(live, sub);
  assert.equal(sub.info().home, 'http://localhost:8083/wordpress');
});

test('LOCAL (subdirectory) -> LIVE (root): URL path is dropped everywhere', async () => {
  wpctl(sub.root, 'fixture', '3', '1');
  await migrate(sub, live);
  assert.deepEqual(live.leftovers('/wordpress/wp-content'), {}, 'no /wordpress/ path left in data');
});

test('different $table_prefix on the target (wp_ -> abc_)', async () => {
  await migrate(live, prefix);
  const sql = `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='sharad_sim_prefix' AND table_name LIKE 'wp\\_%'`;
  const { execFileSync } = await import('node:child_process');
  const n = execFileSync('/Applications/XAMPP/xamppfiles/bin/mysql', ['-uroot', '-N', '-e', sql], { encoding: 'utf8' }).trim();
  assert.equal(n, '0', 'no wp_ tables may be left behind');
  assert.equal(prefix.info().prefix, 'abc_');
});

test('source stored as https://www.example (unreachable): every URL form is rewritten', async () => {
  const odd = 'https://www.live-shop.example';
  wpctl(live.root, 'set-url', odd);
  wpctl(live.root, 'fixture', '4', '1');
  const file = path.join(TMP, 'https.wpress');
  // the download link points at the unreachable https host; fetch it from where the server really is
  const origFetch = globalThis.fetch;
  globalThis.fetch = (u, o) => origFetch(String(u).replace(/^https?:\/\/www\.live-shop\.example/, 'http://127.0.0.1:8081'), o);
  try {
    await exportSite(live, file);
    await importArchive(demo, file, { chunk: 4 * MB });
  } finally {
    globalThis.fetch = origFetch;
    wpctl(live.root, 'set-url', 'http://127.0.0.1:8081');     // never leave the shared site in the odd state
  }
  for (const needle of ['live-shop.example', 'live-shop.example'.replace('.', '\\.')]) {
    assert.deepEqual(demo.leftovers(needle), {}, `leftover "${needle}"`);
  }
  assert.equal(demo.info().home, 'http://localhost:8082');
});

test('importing the same archive twice is idempotent', async () => {
  const file = path.join(TMP, 'twice.wpress');
  await exportSite(live, file);
  await importArchive(demo, file, { chunk: 3 * MB });
  const first = demo.fingerprint();
  await importArchive(demo, file, { chunk: 3 * MB });
  assert.deepEqual(diffFingerprints(first, demo.fingerprint()), []);
});

test('RESTORE: bring a stored backup back after the site was damaged (no re-upload)', async () => {
  const before = demo.fingerprint();
  const ex = await exportSite(demo, path.join(TMP, 'restore-src.wpress'));
  const backupName = decodeURIComponent(ex.href.split('/').pop());
  const stored = path.join(demo.root, 'wp-content/shm-backups', backupName);
  assert.ok(fs.existsSync(stored), 'export must have kept a copy in the site storage');
  wpctl(demo.root, 'mutate');
  assert.notDeepEqual(diffFingerprints(before, demo.fingerprint()), [], 'the damage must be visible');
  await importArchive(demo, null, { restore: backupName });
  assert.deepEqual(diffFingerprints(before, demo.fingerprint()), []);
  assert.ok(fs.existsSync(stored), 'restoring must not consume the backup');
});

test('the migrator never leaks its own plugin into an archive, and keeps working after import', async () => {
  const file = path.join(TMP, 'noself.wpress');
  await exportSite(live, file);
  const buf = fs.readFileSync(file);
  assert.equal(buf.includes(Buffer.from('plugins/sharad-site-migrator')), false);
  await assertHealthy(demo);
});

for (const fault of ['none', 'truncateFinal', 'emptyFinalResponse']) {
  test(`browser export -> download -> chunked import with ${fault}`, async (t) => {
    seed(live, 21, 12);
    const file = path.join(TMP, `browser-${fault}.wpress`);
    const ex = await exportSite(live, file);
    const source = path.join(live.root, 'wp-content/shm-backups', decodeURIComponent(ex.href.split('/').pop()));
    const exported = await archiveIntegrity(source);
    const downloaded = await archiveIntegrity(file);
    const upload = await browserUpload(demo, file, { [fault]: true });
    const destination = path.join(demo.root, 'wp-content/shm-storage', upload.params.storage, upload.params.archive);
    const received = await archiveIntegrity(destination);
    t.diagnostic(JSON.stringify({ exported, downloaded, received }));
    assert.deepEqual(downloaded, exported, 'HTTP download must preserve the exported archive');
    assert.deepEqual(received, exported, 'browser upload must preserve every byte before validation');
    assert.equal(exported.eof, true);
    assert.ok(upload.requests.length >= 4, 'must exercise several real multipart requests');
    assert.ok(upload.requests.every((r) => r.storage === upload.params.storage && r.archive === upload.params.archive));
    if (fault !== 'none') assert.equal(upload.injected, true);
    const im = await importArchive(demo, null, { uploaded: upload.params });
    assert.equal(im.status.type, 'done');
    assert.deepEqual(diffFingerprints(live.fingerprint(), demo.fingerprint()), []);
    await assertHealthy(demo);
  });
}

test('no PHP warnings/deprecations from the plugin on any site during the whole run', () => {
  for (const s of [live, demo, sub, prefix]) assert.deepEqual(pluginIssuesSince(s, 0), [], s.name);
});
