// Drives Sharad Site Migrator over HTTP exactly as its admin-page JavaScript does
// (chunked upload -> POST the returned params until the server stops -> read status -> confirm).
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { Readable } from 'node:stream';
import { pipeline } from 'node:stream/promises';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';

export const PHP = '/Applications/XAMPP/xamppfiles/bin/php';
export const MYSQL = '/Applications/XAMPP/xamppfiles/bin/mysql';
const here = path.dirname(fileURLToPath(import.meta.url));
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const rand = (n) => crypto.randomBytes(n).toString('hex').slice(0, n);

export function wpctl(root, ...args) {
  const out = execFileSync(PHP, [path.join(here, 'wpctl.php'), root, ...args], { encoding: 'utf8', maxBuffer: 1 << 29, timeout: 900000 });
  const line = out.split('\n').reverse().find((l) => l.startsWith('@@'));
  if (!line) throw new Error('wpctl produced no JSON:\n' + out.slice(0, 1500));
  return JSON.parse(line.slice(2));
}

export class Site {
  constructor({ name, root, url }) { Object.assign(this, { name, root, url: url.replace(/\/$/, '') }); }
  info() { return wpctl(this.root, 'info'); }
  get key() { return this.info().key; }
  status() { return wpctl(this.root, 'status'); }
  fingerprint() { return wpctl(this.root, 'fingerprint'); }
  leftovers(needle) { const r = wpctl(this.root, 'leftovers', needle); return Array.isArray(r) ? {} : r; }   // PHP encodes empty as []
  ajax(action) { return `${this.url}/wp-admin/admin-ajax.php?action=${action}`; }
  debugLog() {
    const f = path.join(this.root, 'wp-content/debug.log');
    return fs.existsSync(f) ? fs.readFileSync(f, 'utf8') : '';
  }
}

/** Same extraction the browser uses: the first {...} in the body. `noise` = anything else printed. */
export function parseJson(text) {
  const m = text.match(/{[\s\S]+}/);
  if (!m) return { json: null, noise: text.trim() };
  let json = null;
  try { json = JSON.parse(m[0]); } catch { /* not json */ }
  return { json, noise: text.replace(m[0], '').trim() };
}

async function postForm(url, params, { retries = 5 } = {}) {
  for (let attempt = 0; ; attempt++) {
    try {
      const body = new URLSearchParams();
      for (const [k, v] of Object.entries(params)) body.append(k, String(v));
      const res = await fetch(url, { method: 'POST', body });
      return { status: res.status, text: await res.text() };
    } catch (e) {
      if (attempt >= retries) throw e;
      await sleep(500 * 2 ** attempt);
    }
  }
}

/** POST params, then POST whatever params the server returns, until it answers without any. */
async function runSteps(site, action, params, log, stopAfter = Infinity) {
  let p = params;
  for (let step = 0; step < 20000; step++) {
    if (step >= stopAfter) { log.partial = p; return p; }
    const { status, text } = await postForm(site.ajax(action), p);
    const { json, noise } = parseJson(text);
    if (noise) log.noise.push({ step, status, noise: noise.slice(0, 400) });
    if (status >= 400) throw new Error(`${action} step ${step} -> HTTP ${status}: ${text.slice(0, 600)}`);
    if (json && json.errors) throw new Error(`${action} step ${step}: ${JSON.stringify(json.errors).slice(0, 600)}`);
    log.steps++;
    if (!json || typeof json !== 'object') return p;
    p = json;
  }
  throw new Error(`${action}: more than 20000 steps`);
}

async function waitStatus(site, wanted, key, timeoutMs = 120000) {
  const t0 = Date.now();
  for (;;) {
    const response = await fetch(site.ajax('shm_status') + '&' + new URLSearchParams({ secret_key: key }));
    if (!response.ok) throw new Error(`status polling failed: HTTP ${response.status}`);
    const s = await response.json();
    if (s && wanted.includes(s.type)) return s;
    if (Date.now() - t0 > timeoutMs) throw new Error(`status never reached ${wanted}: ${JSON.stringify(s)}`);
    await sleep(300);
  }
}

/** Export the whole site to `outFile`. Returns { file, bytes, steps, noise }. */
export async function exportSite(site, outFile, extra = {}) {
  const log = { steps: 0, noise: [] };
  const key = site.key;
  await runSteps(site, 'shm_export', { secret_key: key, shm_manual_export: 1, ...extra }, log);
  const st = await waitStatus(site, ['download', 'error'], key);
  if (st.type === 'error') throw new Error(`export failed: ${st.title}: ${st.message}`);
  const href = st.message.match(/href="([^"]+)"/)[1];
  const res = await fetch(href);
  if (!res.ok) throw new Error(`download ${href} -> HTTP ${res.status}`);
  await pipeline(Readable.fromWeb(res.body), fs.createWriteStream(outFile));
  return { file: outFile, href, bytes: fs.statSync(outFile).size, ...log };
}

/**
 * Import `file` (or restore a stored backup when opts.restore = backup filename).
 * opts.chunk: bytes per upload chunk. Returns { steps, noise, chunks, status }.
 */
export async function importArchive(site, file, opts = {}) {
  const log = { steps: 0, noise: [], chunks: 0, partial: null };
  if (opts.resume) {                       // continue an interrupted import from the params the server last returned
    const key = site.key;
    await runSteps(site, 'shm_import', { ...opts.resume, secret_key: key }, log);
    const done = await waitStatus(site, ['done', 'error'], key, 600000);
    if (done.type === 'error') throw new Error(`import failed: ${done.title}: ${done.message}`);
    return { ...log, status: done };
  }
  const key = opts.resume ? null : site.key;
  const storage = rand(12);
  let archive;
  if (opts.restore) {
    archive = opts.restore;
  } else if (opts.uploaded) {
    archive = opts.uploaded.archive;
  } else {
    archive = path.basename(file);
    const size = fs.statSync(file).size;
    const chunk = opts.chunk || 4 * 1024 * 1024;
    const fd = fs.openSync(file, 'r');
    try {
      let offset = 0;
      do {
        const len = Math.min(chunk, size - offset);
        const buf = Buffer.alloc(len);
        fs.readSync(fd, buf, 0, len, offset);
        const fields = { priority: 5, secret_key: key, storage, archive, chunk_offset: offset, chunk_total: size };
        for (let attempt = 0; ; attempt++) {
          const form = new FormData();
          form.append('upload-file', new Blob([buf]), 'chunk');
          for (const [k, v] of Object.entries(fields)) form.append(k, String(v));
          let res;
          try { res = await fetch(site.ajax('shm_import'), { method: 'POST', body: form }); } catch (e) { if (attempt > 5) throw e; await sleep(300); continue; }
          const text = await res.text();
          const { json, noise } = parseJson(text);
          if (noise) log.noise.push({ chunkOffset: offset, noise: noise.slice(0, 400) });
          if (res.ok && json && json.errors && json.errors.length === 0 && json.size === offset + len) break;
          if (res.status >= 500 && attempt < 5) { await sleep(300); continue; }
          throw new Error(`chunk @${offset} -> HTTP ${res.status}: ${text.slice(0, 500)}`);
        }
        log.chunks++;
        offset += len;
      } while (offset < size);
    } finally { fs.closeSync(fd); }
  }

  const base = opts.restore
    ? { secret_key: key, storage, archive, shm_manual_restore: 1, priority: 10 }
    : { secret_key: key, storage, archive, shm_manual_import: 1, ...opts.uploaded };
  const last = await runSteps(site, 'shm_import', base, log);

  let st = await waitStatus(site, ['confirm', 'done', 'error'], key);
  if (st.type === 'confirm') {
    // The browser keeps the original secret across database replacement and lost login cookies.
    await runSteps(site, 'shm_import', { ...last, secret_key: key, priority: 150 }, log, opts.interruptAfterConfirm ?? Infinity);
    if (log.partial) return { ...log, status: { type: 'interrupted' } };
    st = await waitStatus(site, ['done', 'error'], key, 600000);
  }
  if (st.type === 'error') throw new Error(`import failed: ${st.title}: ${st.message}`);
  return { ...log, status: st };
}

/** Pure-Node .wpress reader: [{ path, data }]. */
export function readWpress(file) {
  const buf = fs.readFileSync(file);
  const out = [];
  for (let off = 0; off + 4377 <= buf.length; ) {
    const h = buf.subarray(off, off + 4377);
    if (h.every((b) => b === 0)) break;                       // EOF block
    const cut = (a, b) => h.subarray(a, b).toString('utf8').replace(/\0+$/, '');
    const name = cut(0, 255), size = parseInt(cut(255, 269), 10), dir = cut(281, 4377);
    off += 4377;
    out.push({ path: dir === '.' ? name : `${dir}/${name}`, data: buf.subarray(off, off + size) });
    off += size;
  }
  return out;
}

export function sha256File(f) { return crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex'); }

export async function archiveIntegrity(file) {
  const hash = crypto.createHash('sha256');
  for await (const chunk of fs.createReadStream(file)) hash.update(chunk);
  const bytes = fs.statSync(file).size;
  const tail = Buffer.alloc(4377);
  const fd = fs.openSync(file, 'r');
  let eof;
  try { eof = bytes >= 4377 && fs.readSync(fd, tail, 0, tail.length, bytes - tail.length) === tail.length && tail.every((byte) => byte === 0); }
  finally { fs.closeSync(fd); }
  return { bytes, sha256: hash.digest('hex'), eof };
}

/** Compare two fingerprints; returns a list of human-readable differences (empty = identical). */
export function diffFingerprints(a, b, { ignoreTables = [], allowExtraFilesInTarget = false } = {}) {
  const diffs = [];
  const ta = Object.keys(a.tables), tb = Object.keys(b.tables);
  for (const t of new Set([...ta, ...tb])) {
    if (ignoreTables.includes(t)) continue;
    if (!a.tables[t]) { diffs.push(`table only in target: ${t}`); continue; }
    if (!b.tables[t]) { diffs.push(`table missing in target: ${t}`); continue; }
    if (a.tables[t].rows !== b.tables[t].rows) diffs.push(`${t}: rows ${a.tables[t].rows} -> ${b.tables[t].rows}`);
    else if (a.tables[t].md5 !== b.tables[t].md5) diffs.push(`${t}: content differs (${a.tables[t].rows} rows)`);
  }
  for (const f of new Set([...Object.keys(a.files), ...Object.keys(b.files)])) {
    if (!(f in a.files)) { if (!allowExtraFilesInTarget) diffs.push(`file only in target: ${f}`); }
    else if (!(f in b.files)) diffs.push(`file missing in target: ${f}`);
    else if (a.files[f] !== b.files[f]) diffs.push(`file differs: ${f}`);
  }
  return diffs;
}
