import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import assert from 'node:assert/strict';
import { exportSite, importArchive, diffFingerprints } from './driver.mjs';

export const TMP = fs.mkdtempSync(path.join(os.tmpdir(), 'shm-e2e-'));
export const MB = 1024 * 1024;

/** .wpress writer (format: 4377-byte header = name[255] size[14] mtime[12] path[4096], then bytes; EOF = 4377 NULs). */
export function wpress(entries, { eof = true } = {}) {
  const parts = [];
  for (const e of entries) {
    const data = Buffer.isBuffer(e.data) ? e.data : Buffer.from(e.data);
    const dir = e.path.includes('/') ? e.path.slice(0, e.path.lastIndexOf('/')) : '.';
    const name = e.path.slice(e.path.lastIndexOf('/') + 1);
    const h = Buffer.alloc(4377);
    h.write(name, 0, 255, 'utf8');
    h.write(String(data.length), 255, 14, 'utf8');
    h.write(String(e.mtime || Math.floor(Date.now() / 1000)), 269, 12, 'utf8');
    h.write(dir, 281, 4096, 'utf8');
    parts.push(h, data);
  }
  if (eof) parts.push(Buffer.alloc(4377));
  return Buffer.concat(parts);
}

/**
 * Where PHP problems end up: WordPress' debug.log AND the plugin's own wp-content/shm-storage/error.log
 * (its error handler writes there, and it also records notices raised inside WordPress core on the plugin's behalf).
 */
const size = (f) => (fs.existsSync(f) ? fs.statSync(f).size : 0);
const files = (site) => ({ debug: path.join(site.root, 'wp-content/debug.log'), err: path.join(site.root, 'wp-content/shm-storage/error.log') });
export const logMark = (site) => ({ debug: size(files(site).debug), err: size(files(site).err) });
export function pluginIssuesSince(site, mark) {
  const m = mark || { debug: 0, err: 0 };
  const f = files(site);
  const out = [];
  if (fs.existsSync(f.debug)) {
    out.push(...fs.readFileSync(f.debug, 'utf8').slice(m.debug).split('\n').filter((l) => /PHP (Warning|Notice|Fatal error|Deprecated|Parse error)/.test(l) && /sharad-site-migrator/.test(l)));
  }
  if (fs.existsSync(f.err)) {
    out.push(...fs.readFileSync(f.err, 'utf8').slice(m.err).split('\n').filter((l) => l.startsWith('{')).map((l) => 'error.log: ' + l.slice(0, 300)));
  }
  return out;
}

export const hostPath = (url) => { const u = new URL(url); return u.host + (u.pathname === '/' ? '' : u.pathname); };

/** Fetch a site page as a browser would; returns { status, body }. */
export async function get(url) { const r = await fetch(url, { redirect: 'follow' }); return { status: r.status, body: await r.text(), type: r.headers.get('content-type') }; }

/** The migrated site must actually serve its migrated content from its own URL. */
export async function assertHealthy(site, { seededPosts = true } = {}) {
  const info = site.info();
  assert.equal(info.active, true, `${site.name}: migrator must still be active after an import`);
  assert.equal(info.home.replace(/\/$/, ''), site.url, `${site.name}: home URL must be the site's own URL`);
  const home = await get(site.url + '/');
  assert.equal(home.status, 200, `${site.name}: front page ${home.status}`);
  assert.doesNotMatch(home.body, /critical error|Fatal error|Warning:|Deprecated:/, `${site.name}: front page shows an error`);
  if (seededPosts) {
    assert.match(home.body, /Héllo 日本 🙂 post/, `${site.name}: seeded posts missing from front page`);
    const ym = new Date().toISOString().slice(0, 7).replace('-', '/');   // uploads are dated: uploads/YYYY/MM/
    const png = await get(`${site.url}/wp-content/uploads/${ym}/pic1.png`);
    assert.equal(png.status, 200, `${site.name}: media file not served`);
    const uni = await get(`${site.url}/wp-content/uploads/${ym}/${encodeURIComponent('café-日本-🙂.txt')}`);
    assert.equal(uni.status, 200, `${site.name}: unicode-named file not served`);
  }
  const login = await get(site.url + '/wp-login.php');
  assert.equal(login.status, 200);
}

/**
 * Export `from`, import into `to`, and prove the result is identical:
 * same rows/content/files, no leftover source URLs, no output noise, no PHP issues from the plugin,
 * and a site that still serves its content.
 */
export async function migrate(from, to, { chunk = 4 * MB, label = `${from.name}->${to.name}`, exportExtra = {}, ignoreTables = [], healthy = true } = {}) {
  const file = path.join(TMP, `${from.name}-${Date.now()}.wpress`);
  const mFrom = logMark(from), mTo = logMark(to);
  const ex = await exportSite(from, file, exportExtra);
  assert.deepEqual(ex.noise, [], `${label}: export responses must be pure JSON`);
  const im = await importArchive(to, file, { chunk });
  assert.deepEqual(im.noise, [], `${label}: import responses must be pure JSON`);
  const diffs = diffFingerprints(from.fingerprint(), to.fingerprint(), { ignoreTables });
  assert.deepEqual(diffs, [], `${label}: source and target differ:\n${diffs.slice(0, 20).join('\n')}`);
  const src = hostPath(from.url);
  if (src !== hostPath(to.url)) {
    assert.deepEqual(to.leftovers(src), {}, `${label}: rows still contain the source URL "${src}"`);
    assert.deepEqual(to.leftovers(src.replace(/\//g, '\\/')), {}, `${label}: rows still contain the JSON-escaped source URL`);
  }
  assert.deepEqual(pluginIssuesSince(from, mFrom), [], `${label}: PHP issues on source`);
  assert.deepEqual(pluginIssuesSince(to, mTo), [], `${label}: PHP issues on target`);
  if (healthy) await assertHealthy(to);
  return { file, ex, im };
}
