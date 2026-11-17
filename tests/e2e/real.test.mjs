// The user's actual local site ("SSW Demo Site": Elementor, WooCommerce, Yoast, ...) migrated into a clean target.
// The real site is only EXPORTED from. The migrator is installed for the test and removed afterwards.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { up, deployPlugin } from './env.mjs';
import { wpctl, exportSite, importArchive, diffFingerprints } from './driver.mjs';
import { assertHealthy, logMark, pluginIssuesSince, TMP, MB } from './helpers.mjs';

const { demo, real } = await up({ fresh: true });   // clean target: the importer never prunes tables or files that are not in the archive

test('REAL local site (localhost/wordpress) -> DEMO: full Elementor/WooCommerce site, identical', async (t) => {
  const mark = logMark(real);
  const dest = path.join(real.root, 'wp-content/plugins/sharad-site-migrator');
  const preExisting = fs.existsSync(dest);
  assert.equal(preExisting, false, 'refusing to overwrite a pre-existing install on the real site');
  try {
    deployPlugin(real.root);
    const act = wpctl(real.root, 'activate');
    assert.equal(act.active, true, JSON.stringify(act));

    // baseline taken right before the export: a real site keeps changing (audit logs, cron, ...)
    const base = real.fingerprint();
    const baseOpts = wpctl(real.root, 'optdump');
    const file = path.join(TMP, 'real.wpress');
    const t0 = Date.now();
    const ex = await exportSite(real, file);
    assert.deepEqual(ex.noise, [], 'export responses must be pure JSON');
    t.diagnostic(`export: ${(ex.bytes / MB).toFixed(1)} MB in ${((Date.now() - t0) / 1000).toFixed(1)}s, ${ex.steps} steps`);

    const t1 = Date.now();
    const im = await importArchive(demo, file, { chunk: 4 * MB });
    assert.deepEqual(im.noise, [], 'import responses must be pure JSON');
    t.diagnostic(`import: ${im.chunks} chunks, ${im.steps} steps in ${((Date.now() - t1) / 1000).toFixed(1)}s`);

    // the importer overwrites, it never prunes: files that only existed on the target are allowed to stay
    const diffs = diffFingerprints(base, demo.fingerprint(), { allowExtraFilesInTarget: true, ignoreTables: ['{{P}}aiowps_audit_log', '{{P}}wpmailsmtp_debug_events'] });   // third-party runtime event logs (they hold absolute paths of the source)
    const dOpts = wpctl(demo.root, 'optdump');
    const optDiff = Object.keys({ ...baseOpts, ...dOpts }).filter((k) => baseOpts[k] !== dOpts[k]);
    assert.deepEqual(diffs, [], 'real site and migrated copy differ:\n' + diffs.slice(0, 30).join('\n') + '\noptions that differ: ' + optDiff.join(', '));
    // scheme-aware: the real site has an FTP-host setting "localhost/wordpress/" that is not a URL and must stay as typed
    for (const needle of ['//localhost/wordpress', '\\/\\/localhost\\/wordpress', '%2F%2Flocalhost%2Fwordpress']) {
      assert.deepEqual(demo.leftovers(needle), {}, `leftover "${needle}" in the migrated copy`);
    }
    assert.deepEqual(pluginIssuesSince(demo, 0).slice(0, 5), [], 'PHP issues from the migrator on the target');
    assert.deepEqual(pluginIssuesSince(real, mark), [], 'PHP issues from the migrator on the real site');
    await assertHealthy(demo, { seededPosts: false });
  } finally {
    // put the user's site back exactly as it was
    try { wpctl(real.root, 'cleanup'); } catch (e) { console.error('cleanup(wpctl) failed:', e.message); }
    fs.rmSync(dest, { recursive: true, force: true });
    for (const d of ['shm-backups', 'shm-storage']) fs.rmSync(path.join(real.root, 'wp-content', d), { recursive: true, force: true });
  }
});
