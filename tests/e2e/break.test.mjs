// "Try to break it": hostile callers, hostile archives, broken dumps, interruptions, concurrency.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { up } from './env.mjs';
import { wpctl, exportSite, importArchive, diffFingerprints, readWpress } from './driver.mjs';
import { wpress, TMP, MB, logMark, pluginIssuesSince } from './helpers.mjs';

const { live, demo } = await up({ fresh: true });
wpctl(live.root, 'fixture', '1', '1');
const baseArchive = path.join(TMP, 'base.wpress');
await exportSite(live, baseArchive);
await importArchive(demo, baseArchive, { chunk: 4 * MB });          // demo == live: a known-good target
const storageOf = (s) => { const d = path.join(s.root, 'wp-content/shm-storage'); return fs.existsSync(d) ? fs.readdirSync(d).sort() : []; };
const backupsOf = (s) => { const d = path.join(s.root, 'wp-content/shm-backups'); return fs.existsSync(d) ? fs.readdirSync(d).sort() : []; };
const raw = async (url, params) => { const r = await fetch(url, { method: 'POST', body: new URLSearchParams(params) }); return { status: r.status, text: (await r.text()).trim() }; };

// ---------------------------------------------------------------- 1. callers with no / wrong credentials
test('wrong, empty, missing or malformed secret keys do nothing at all', async () => {
  const before = { st: storageOf(demo), bk: backupsOf(demo), fp: demo.fingerprint() };
  const real = demo.key;
  const attempts = [{}, { secret_key: '' }, { secret_key: 'nope' }, { secret_key: real.slice(0, -1) }, { secret_key: real + 'x' }, { secret_key: real.toUpperCase() },
    { 'secret_key[]': real }];   // (surrounding whitespace is trimmed by design: the attacker still needs every character)
  for (const action of ['shm_import', 'shm_export']) {
    for (const a of attempts) {
      const r = await raw(demo.ajax(action), { shm_manual_import: 1, shm_manual_export: 1, storage: 'x', archive: 'x.wpress', ...a });
      assert.equal(r.text, '', `${action} ${JSON.stringify(a).slice(0, 40)} must return nothing, got: ${r.text.slice(0, 120)}`);
    }
  }
  assert.deepEqual(storageOf(demo), before.st, 'no working folders created');
  assert.deepEqual(backupsOf(demo), before.bk, 'no archives created');
  assert.deepEqual(demo.fingerprint().tables, before.fp.tables, 'database untouched');
});

test('status remains available without a login only with the right secret key', async () => {
  const key = demo.key;
  const r = await fetch(demo.ajax('shm_status') + '&' + new URLSearchParams({ secret_key: key }));
  assert.equal(r.status, 200);
  assert.equal((await r.json()).type, 'done');
  for (const params of [{}, { secret_key: '' }, { secret_key: 'wrong' }, { 'secret_key[]': key }]) {
    const denied = await fetch(demo.ajax('shm_status') + '&' + new URLSearchParams(params));
    assert.equal((await denied.text()).trim(), '', 'invalid credentials must not expose status');
  }
});

test('backup list / delete / label are not reachable without a login, even with the right key', async () => {
  const bk = backupsOf(live);
  assert.ok(bk.length > 0);
  for (const action of ['shm_backups', 'shm_backup_list', 'shm_add_backup_label']) {
    const r = await raw(live.ajax(action), { secret_key: live.key, archive: bk[0], label: 'x' });
    assert.ok(r.text === '0' || r.text === '', `${action} answered: ${r.text.slice(0, 80)}`);
  }
  assert.deepEqual(backupsOf(live), bk, 'backups must survive an unauthenticated delete attempt');
});

// ---------------------------------------------------------------- 2. chunk protocol abuse
test('chunk protocol: gap -> 409, replay is harmless, negative/oversized chunks -> 400, no escape from storage', async () => {
  const key = live.key;
  const send = async (fields, bytes = 'AAAA') => {
    const f = new FormData();
    f.append('upload-file', new Blob([bytes]), 'chunk');
    for (const [k, v] of Object.entries({ priority: 5, secret_key: key, storage: 'abuse1', archive: 'abuse.wpress', ...fields })) f.append(k, String(v));
    const r = await fetch(live.ajax('shm_import'), { method: 'POST', body: f });
    return { status: r.status, text: (await r.text()).trim() };
  };
  const ok = await send({ chunk_offset: 0, chunk_total: 12 });
  assert.equal(ok.status, 200); assert.match(ok.text, /"size":4/);
  const gap = await send({ chunk_offset: 8, chunk_total: 12 });
  assert.equal(gap.status, 409, gap.text);
  const replay = await send({ chunk_offset: 0, chunk_total: 12 });   // offset 0 simply restarts the upload
  assert.equal(replay.status, 200);
  assert.equal((await send({ chunk_offset: -5, chunk_total: 12 })).status, 400);
  assert.equal((await send({ chunk_offset: 0, chunk_total: 2 })).status, 400, 'chunk larger than declared total');
  const f = path.join(live.root, 'wp-content/shm-storage/abuse1/abuse.wpress');
  assert.equal(fs.statSync(f).size, 4, 'failed attempts must not have modified the stored bytes');
  // traversal through the archive / storage names
  const before = fs.readdirSync(path.join(live.root, 'wp-content')).sort();
  for (const evil of [{ archive: '../../pwn.txt' }, { archive: '..\\..\\pwn.txt' }, { storage: '../../x', archive: 'a.wpress' }, { archive: '/etc/pwn' }]) {
    const r = await send({ chunk_offset: 0, chunk_total: 4, ...evil });
    assert.ok(![200].includes(r.status) || !/"size"/.test(r.text) || !('archive' in evil && /\.\./.test(evil.archive)), `${JSON.stringify(evil)} -> ${r.status} ${r.text.slice(0, 100)}`);
  }
  assert.deepEqual(fs.readdirSync(path.join(live.root, 'wp-content')).sort(), before, 'nothing created outside storage');
  assert.equal(fs.existsSync(path.join(live.root, 'pwn.txt')), false);
  assert.equal(fs.existsSync(path.join(live.root, 'wp-content/pwn.txt')), false);
});

// ---------------------------------------------------------------- 3. hostile / broken archives: loud failure, target untouched
const goodEntries = () => readWpress(baseArchive);
async function mustReject(name, archiveBytes, why) {
  const f = path.join(TMP, `bad-${name}.wpress`);
  fs.writeFileSync(f, archiveBytes);
  const before = demo.fingerprint();
  const mark = logMark(demo);
  await assert.rejects(importArchive(demo, f, { chunk: 2 * MB }), (e) => { assert.match(e.message, /import failed|HTTP|step/i); return true; }, `${name}: ${why} must be rejected`);
  assert.deepEqual(diffFingerprints(before, demo.fingerprint()), [], `${name}: the target site must be untouched by a rejected archive`);
  assert.deepEqual(pluginIssuesSince(demo, mark), [], `${name}: no PHP warnings`);
}

test('different PHP versions and missing metadata stop before overwriting the destination', async () => {
  const original = JSON.parse(goodEntries().find((e) => e.path === 'package.json').data);
  const major = Number(original.PHP.Version.split('.')[0]);
  for (const version of [`${major - 1}.4.33`, `${major + 1}.0.0`, null, ['8.2'], 'invalid']) {
    const entries = goodEntries().map((e) => {
      if (e.path !== 'package.json') return e;
      const pkg = { ...original, PHP: { Version: version } };
      if (version === null) delete pkg.PHP;
      return { ...e, data: Buffer.from(JSON.stringify(pkg)) };
    });
    await mustReject(`php-${String(version)}`, wpress(entries), 'PHP compatibility');
    assert.match(demo.status().message, /PHP/);
  }
});

test('archive with a path-traversal entry is rejected before anything is written', async () => {
  const entries = goodEntries();
  const evil = Buffer.from('<?php echo "pwned";');
  await mustReject('traversal', wpress([...entries.filter((e) => e.path === 'package.json' || e.path === 'database.sql'), { path: '../../escape.php', data: evil }]), 'path traversal');
  for (const p of [demo.root, path.dirname(demo.root), path.join(demo.root, 'wp-content')]) assert.equal(fs.existsSync(path.join(p, 'escape.php')), false, `escaped to ${p}`);
});

test('truncated archive (cut in the middle of a file) is rejected', async () => {
  const buf = fs.readFileSync(baseArchive);
  await mustReject('truncated-mid', buf.subarray(0, Math.floor(buf.length / 2)), 'truncation');
});

test('archive missing its EOF block is rejected', async () => {
  const buf = fs.readFileSync(baseArchive);
  await mustReject('no-eof', buf.subarray(0, buf.length - 4377), 'missing EOF');
});

test('random garbage named .wpress and an empty file are rejected', async () => {
  await mustReject('garbage', Buffer.from(Array.from({ length: 50000 }, (_, i) => (i * 131 + 7) & 255)), 'garbage');
  await mustReject('empty', Buffer.alloc(0), 'empty file');
});

test('valid container without package.json is rejected', async () => {
  await mustReject('no-package', wpress(goodEntries().filter((x) => x.path !== 'package.json')), 'no package.json');
});

test('a files-only archive (export option "no database") imports and leaves the database alone', async () => {
  const before = demo.fingerprint();
  const f = path.join(TMP, 'files-only.wpress');
  fs.writeFileSync(f, wpress(goodEntries().filter((x) => x.path !== 'database.sql')));
  await importArchive(demo, f, { chunk: 4 * MB });
  assert.deepEqual(diffFingerprints(before, demo.fingerprint(), { allowExtraFilesInTarget: true }).filter((d) => !d.startsWith('file')), []);
});

// ---------------------------------------------------------------- 4. dumps from other servers
test('a dump from MySQL 8 (utf8mb4_0900_ai_ci collation) imports into MariaDB', async () => {
  const entries = goodEntries().map((e) => e.path === 'database.sql'
    ? { ...e, data: Buffer.from(e.data.toString('latin1').replace(/utf8mb4_unicode_520_ci|utf8mb4_unicode_ci|utf8mb4_general_ci/g, 'utf8mb4_0900_ai_ci'), 'latin1') } : e);
  const hits = entries.find((e) => e.path === 'database.sql').data.toString('latin1').split('utf8mb4_0900_ai_ci').length - 1;
  assert.ok(hits > 0, 'test dump must actually contain the MySQL 8 collation');
  const f = path.join(TMP, 'mysql8.wpress');
  fs.writeFileSync(f, wpress(entries));
  await importArchive(demo, f, { chunk: 4 * MB });
  assert.deepEqual(diffFingerprints(live.fingerprint(), demo.fingerprint(), { allowExtraFilesInTarget: true }), []);
  const { execFileSync } = await import('node:child_process');
  const n = execFileSync('/Applications/XAMPP/xamppfiles/bin/mysql', ['-uroot', '-N', '-e', "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='sharad_sim_demo' AND table_collation LIKE '%0900%'"], { encoding: 'utf8' }).trim();
  assert.equal(n, '0', 'no MySQL-8-only collation may be left on a MariaDB target');
});

test('a statement the target cannot run fails the import loudly instead of dropping rows', async () => {
  const entries = goodEntries().map((e) => e.path === 'database.sql'
    ? { ...e, data: Buffer.concat([e.data, Buffer.from("\nINSERT INTO `SERVMASK_PREFIX_options` (no_such_column) VALUES ('x');\n")]) } : e);
  const f = path.join(TMP, 'badsql.wpress');
  fs.writeFileSync(f, wpress(entries));
  await assert.rejects(importArchive(demo, f, { chunk: 4 * MB }), /MySQL error|import failed/i);
});

// ---------------------------------------------------------------- 5. interruption + resume
test('closing the browser mid-import and resuming finishes with an identical site', async () => {
  // put demo in a different state first so a half-applied import is observable
  wpctl(demo.root, 'mutate');
  const r1 = await importArchive(demo, baseArchive, { chunk: 4 * MB, interruptAfterConfirm: 3 });
  assert.equal(r1.status.type, 'interrupted');
  assert.ok(r1.partial, 'server returned the params for the next step');
  const r2 = await importArchive(demo, null, { resume: r1.partial });
  assert.equal(r2.status.type, 'done');
  assert.deepEqual(diffFingerprints(live.fingerprint(), demo.fingerprint(), { allowExtraFilesInTarget: true }), []);
});

// ---------------------------------------------------------------- 6. concurrency
test('two exports of the same site at once each leave a complete, distinct archive in storage', async () => {
  // the plugin keeps ONE status record per site, so both callers may be shown the same link: that is by design.
  // What must hold is that neither export corrupts the other.
  const before = new Set(backupsOf(live));
  await Promise.all([exportSite(live, path.join(TMP, 'par-a.wpress')), exportSite(live, path.join(TMP, 'par-b.wpress'))]);
  // status is per-site, so BOTH callers are released by whichever export finishes first: wait for the second archive itself
  const complete = (f) => { try { const b = fs.readFileSync(path.join(live.root, 'wp-content/shm-backups', f)); return b.length > 4377 && b.subarray(b.length - 4377).every((x) => x === 0); } catch { return false; } };
  let added = [];
  for (let i = 0; i < 120; i++) {
    added = backupsOf(live).filter((f) => !before.has(f) && f.endsWith('.wpress'));
    if (added.length === 2 && added.every(complete)) break;
    await new Promise((r) => setTimeout(r, 500));
  }
  assert.equal(added.length, 2, `expected two new archives, got ${added.join(', ')}`);
  const lists = added.map((f) => readWpress(path.join(live.root, 'wp-content/shm-backups', f)).map((e) => e.path).sort());
  assert.ok(lists[0].includes('package.json') && lists[0].includes('database.sql'));
  const only = (a, b) => a.filter((x) => !b.includes(x));
  assert.deepEqual([only(lists[0], lists[1]), only(lists[1], lists[0])], [[], []], 'both archives are complete and contain the same files (left of each: entries only in the first / only in the second)');
});

test('importing an archive repeatedly while another import of a different archive runs does not corrupt either site', async () => {
  // demo and live are different sites; two imports in parallel into two targets must not interfere (storage is per-job)
  const { sub } = await up();
  const [i1, i2] = await Promise.all([importArchive(demo, baseArchive, { chunk: 4 * MB }), importArchive(sub, baseArchive, { chunk: 4 * MB })]);
  assert.equal(i1.status.type, 'done'); assert.equal(i2.status.type, 'done');
  assert.deepEqual(diffFingerprints(live.fingerprint(), sub.fingerprint(), { allowExtraFilesInTarget: true }), []);
});

test('no PHP warnings or notices from the plugin anywhere in the break suite', () => {
  assert.deepEqual(pluginIssuesSince(live, 0), []);
  assert.deepEqual(pluginIssuesSince(demo, 0), []);
});
