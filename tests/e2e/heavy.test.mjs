// The headline claim: no size limit. A >512 MB archive is imported into a server that accepts 8 MB per request
// (upstream refuses it in the browser at wp_max_upload_size(), and would need ONE request of that size anyway).
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { up } from './env.mjs';
import { wpctl, exportSite, importArchive, diffFingerprints } from './driver.mjs';
import { TMP, MB, logMark, pluginIssuesSince } from './helpers.mjs';

const { live, demo } = await up({ fresh: true });
const SIZE_MB = Number(process.env.SHM_HEAVY_MB || 600);

test(`a ${SIZE_MB} MB site (> 512 MB) travels live -> demo through an 8 MB request limit`, async (t) => {
  const phpLimit = await (await fetch(demo.url + '/wp-admin/admin-ajax.php?action=nope')).status;   // server is up
  assert.ok(phpLimit);
  wpctl(live.root, 'fixture', '9', String(SIZE_MB));
  const mark = logMark(demo);

  const file = path.join(TMP, 'heavy.wpress');
  const t0 = Date.now();
  const ex = await exportSite(live, file);
  assert.ok(ex.bytes > 512 * MB, `archive is ${(ex.bytes / MB).toFixed(0)} MB, must exceed 512 MB`);
  t.diagnostic(`export: ${(ex.bytes / MB).toFixed(0)} MB in ${((Date.now() - t0) / 1000).toFixed(0)}s`);

  const t1 = Date.now();
  const im = await importArchive(demo, file, { chunk: 4 * MB });
  t.diagnostic(`import: ${im.chunks} chunks of 4 MB in ${((Date.now() - t1) / 1000).toFixed(0)}s`);
  assert.ok(im.chunks > 128, `must have needed >128 requests, got ${im.chunks}`);
  assert.deepEqual(im.noise, []);

  const diffs = diffFingerprints(live.fingerprint(), demo.fingerprint());
  assert.deepEqual(diffs, [], diffs.slice(0, 10).join('\n'));
  const big = path.join(demo.root, 'wp-content/uploads', new Date().toISOString().slice(0, 7).replace('-', '/'), 'big-media.bin');
  assert.equal(fs.statSync(big).size, SIZE_MB * MB, 'the big media file arrived whole');
  assert.deepEqual(pluginIssuesSince(demo, mark), []);
  // the working copy of the archive must be gone from the target once the job is done
  const storage = path.join(demo.root, 'wp-content/shm-storage');
  const left = fs.existsSync(storage) ? fs.readdirSync(storage).filter((f) => !['index.php', '.htaccess'].includes(f)) : [];
  assert.deepEqual(left, [], 'job folders in shm-storage must be cleaned up (they hold a full copy of the archive) and error.log must not even exist (no PHP problems)');
});
