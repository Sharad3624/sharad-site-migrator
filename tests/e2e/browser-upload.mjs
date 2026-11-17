// Execute the shipped uploader with real File/Blob/FormData and real HTTP requests.
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';

const require = createRequire(new URL('../js/package.json', import.meta.url));
const { JSDOM } = require('jsdom');
const jsDir = new URL('../../plugin/sharad-site-migrator/lib/view/assets/javascript/', import.meta.url);

export async function browserUpload(site, file, { truncateFinal = false, emptyFinalResponse = false } = {}) {
  const dom = new JSDOM('<form id="shm-import-form"><input type="file" id="shm-import-file"><input type="hidden" name="shm_manual_import" value="1"></form>', {
    url: site.url + '/wp-admin/', runScripts: 'outside-only',
  });
  const w = dom.window;
  const $ = require('jquery')(w);
  const key = site.key;
  const url = site.ajax('shm_import');
  const emptyResponseFile = path.join(site.root, 'shm-test-empty-upload.php');
  if (emptyFinalResponse) fs.writeFileSync(emptyResponseFile, '<?php http_response_code(200);');
  Object.assign(w, {
    jQuery: $, $,
    // wp_localize_script serializes these scalar configuration values as strings.
    shm_uploader: { chunk_size: String(4 * 1024 * 1024), max_retries: '3', url, params: { priority: '5', secret_key: key } },
    shm_import: { ajax: { url }, status: { url: url + '&status=1' }, secret_key: key },
    shm_compatibility: { messages: [] },
    shm_locale: { unable_to_import: 'Unable to import', unable_to_upload_the_chunk: 'Upload acknowledgement failed' },
  });
  w.eval(fs.readFileSync(new URL('util.min.js', jsDir), 'utf8'));
  w.eval(fs.readFileSync(new URL('import.min.js', jsDir), 'utf8'));
  await new Promise((resolve) => setTimeout(resolve, 30));

  const requests = [];
  let injected = false;
  const ajax = $.ajax;
  $.ajax = function (options) {
    if (options.data instanceof w.FormData) {
      const form = options.data;
      const blob = form.get('upload-file');
      const offset = Number(form.get('chunk_offset'));
      const total = Number(form.get('chunk_total'));
      requests.push({ offset, length: blob.size, storage: form.get('storage'), archive: form.get('archive') });
      if (!injected && offset + blob.size === total && (truncateFinal || emptyFinalResponse)) {
        injected = true;
        if (truncateFinal) {
          // A host delivers fewer file bytes while PHP still reports UPLOAD_ERR_OK.
          form.set('upload-file', blob.slice(0, Math.floor(blob.size / 2)), 'chunk');
        } else {
          // A proxy/auth layer returns HTTP 200 without storing the last chunk.
          options.url = site.url + '/shm-test-empty-upload.php';
        }
      }
    }
    return ajax.call(this, options);
  };

  try {
    const params = await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Browser upload timed out')), 120000);
      w.Shm.Import.prototype.start = function () { clearTimeout(timer); resolve(Object.fromEntries(this.params.map(({ name, value }) => [name, value]))); };
      const setStatus = w.Shm.Import.prototype.setStatus;
      w.Shm.Import.prototype.setStatus = function (status) {
        if (status.type === 'error') { clearTimeout(timer); reject(new Error(status.message)); }
        return setStatus.call(this, status);
      };
      const input = w.document.getElementById('shm-import-file');
      const archive = new w.File([fs.readFileSync(file)], path.basename(file));
      Object.defineProperty(input, 'files', { value: { item: () => archive } });
      $(input).trigger('change');
    });
    return { params, requests, injected };
  } finally {
    dom.window.close();
    if (emptyFinalResponse) fs.rmSync(emptyResponseFile, { force: true });
  }
}
