// Runs the plugin's real browser bundles (unmodified files) inside jsdom, with $.ajax scripted.
const { JSDOM } = require('jsdom');
const fs = require('fs');
const path = require('path');

const JS_DIR = path.resolve(__dirname, '../../plugin/sharad-site-migrator/lib/view/assets/javascript');
const read = (f) => fs.readFileSync(path.join(JS_DIR, f), 'utf8');

const IMPORT_HTML = `
  <form id="shm-import-form"><input type="file" id="shm-import-file"></form>
  <div id="shm-drag-drop-area"></div>`;
const BACKUPS_HTML = `
  <table id="shm-backups-list"><tbody><tr>
    <td><a href="#" class="shm-backup-restore" data-archive="site-20261103-120000-0123456789abcdef.wpress">Restore</a></td>
  </tr></tbody></table>`;

/**
 * @param {'import.min.js'|'backups.min.js'} bundle
 * @param {object} opts  { html, globals, uploadOutcomes: [{status, body}], }
 */
async function boot(bundle, opts = {}) {
  const dom = new JSDOM(`<!doctype html><html><body>${opts.html || ''}</body></html>`, {
    runScripts: 'outside-only',
    url: 'http://localhost/wp-admin/',
  });
  const w = dom.window;
  const jQuery = require('jquery')(w);
  w.jQuery = w.$ = jQuery;

  const uploadUrl = '/wp-admin/admin-ajax.php?action=shm_import';
  Object.assign(w, {
    shm_uploader: { chunk_size: 1048576, max_retries: 3, url: uploadUrl, params: { priority: 5, secret_key: 'KEY' } },
    shm_import: { ajax: { url: uploadUrl }, status: { url: '/status' }, secret_key: 'KEY' },
    shm_export: { ajax: { url: '/x' }, status: { url: '/status' }, secret_key: 'KEY' },
    shm_backups: { ajax: { url: '/b' }, backups: { url: '/bl' }, labels: { url: '/l' }, secret_key: 'KEY' },
    shm_compatibility: { messages: [] },
    shm_locale: new Proxy({ unable_to_import: 'Unable to import', invalid_archive_extension: 'bad extension', unable_to_upload_the_chunk: 'gave up' },
      { get: (t, k) => (k in t ? t[k] : String(k)) }),
  }, opts.globals || {});

  const calls = [];            // every $.ajax invocation
  const statuses = [];         // every Import.setStatus payload
  const outcomes = (opts.uploadOutcomes || []).slice();
  const delays = [];           // backoff delays requested via setTimeout
  let pending = 0;             // async callbacks not yet delivered (a real XHR never calls back synchronously)
  const later = (fn) => { pending++; queueMicrotask(() => { pending--; fn(); }); };

  const neverFires = { done() { return this; }, fail() { return this; }, always() { return this; } };
  jQuery.ajax = function (o) {
    calls.push(o);
    if (o.url === uploadUrl && o.data instanceof w.FormData && o.data.has('upload-file')) {
      const next = outcomes.shift() || { status: 200 };
      later(() => {
        if (next.status === 200) {
          if (next.body !== undefined && o.dataType === 'json') {
            try { o.success(JSON.parse(next.body), 'success', { status: 200 }); }
            catch { o.error({ status: 200, responseText: next.body }, 'parsererror', 'invalid JSON'); }
          } else {
            o.success({ errors: [], size: Number(o.data.get('chunk_offset')) + Number(o.data.get('chunk_size')) }, 'success', { status: 200 });
          }
        } else {
          o.error({ status: next.status, responseText: next.body || '' }, 'error', 'x');
        }
      });
    }
    return neverFires;
  };

  w.eval(read('util.min.js'));
  w.eval(read(bundle));
  await new Promise((r) => setTimeout(r, 30));   // let jQuery(document).ready handlers run

  const orig = w.Shm.Import.prototype.setStatus;
  w.Shm.Import.prototype.setStatus = function (p) { statuses.push(p); return orig.call(this, p); };
  w.setTimeout = (fn, ms) => { delays.push(ms); later(fn); return 0; };   // run backoff immediately, remember it

  const settle = async () => { while (pending > 0) await new Promise((r) => setImmediate(r)); };

  return { w, $: jQuery, calls, statuses, delays, uploadUrl, settle,
    uploads: () => calls.filter((c) => c.url === uploadUrl && c.data instanceof w.FormData && c.data.has('upload-file')) };
}

/** A File of any claimed size without allocating it; slice() records the ranges asked for. */
function fakeFile(w, name, size) {
  const f = new w.File([], name);
  Object.defineProperty(f, 'size', { value: size });
  f.ranges = [];
  f.slice = (s, e) => { f.ranges.push([s, e]); return new w.Blob([]); };
  return f;
}

async function chooseFile(ctx, file) {
  const input = ctx.w.document.getElementById('shm-import-file');
  Object.defineProperty(input, 'files', { value: { item: () => file }, configurable: true });
  ctx.$(input).trigger('change');
  await ctx.settle();
}

module.exports = { boot, fakeFile, chooseFile, IMPORT_HTML, BACKUPS_HTML };
