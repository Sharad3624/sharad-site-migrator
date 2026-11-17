// The three admin screens, loaded the way a browser loads them (logged in): each must ship its own CSS and JS.
// (Found when a user saw an unstyled Import page: the asset check compared against a hard-coded page name.)
import test from 'node:test';
import assert from 'node:assert/strict';
import { up } from './env.mjs';

const { live } = await up({ fresh: true });

async function login(site) {
  const r = await fetch(`${site.url}/wp-login.php`, {
    method: 'POST', redirect: 'manual',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', Cookie: 'wordpress_test_cookie=WP%20Cookie%20check' },
    body: new URLSearchParams({ log: 'admin', pwd: `admin-pw-${site.name}`, 'wp-submit': 'Log In', redirect_to: `${site.url}/wp-admin/`, testcookie: '1' }),
  });
  const cookie = r.headers.getSetCookie().map((c) => c.split(';')[0]).join('; ');
  assert.match(cookie, /wordpress_logged_in/, 'login must succeed');
  return cookie;
}
const page = async (site, cookie, slug) => { const r = await fetch(`${site.url}/wp-admin/admin.php?page=${slug}`, { headers: { Cookie: cookie } }); return { status: r.status, html: await r.text() }; };

const A = 'plugins/sharad-site-migrator/lib/view/assets';
const cases = {
  shm_export:  { css: ['export.min.css'],  js: ['util.min.js', 'export.min.js'],  inline: [] },
  shm_import:  { css: ['import.min.css'],  js: ['util.min.js', 'import.min.js'],  inline: ['shm_uploader', '"chunk_size"', 'shm-import-file'] },
  shm_backups: { css: ['backups.min.css'], js: ['util.min.js', 'backups.min.js'], inline: ['shm_backups'] },
};

for (const [slug, want] of Object.entries(cases)) {
  test(`admin page ${slug} loads its stylesheet, scripts and page data`, async () => {
    const cookie = await login(live);
    const { status, html } = await page(live, cookie, slug);
    assert.equal(status, 200);
    for (const f of want.css) assert.ok(html.includes(`${A}/css/${f}`), `missing stylesheet ${f}`);
    for (const f of want.js) assert.ok(html.includes(`${A}/javascript/${f}`), `missing script ${f}`);
    for (const s of want.inline) assert.ok(html.includes(s), `missing ${s}`);
    assert.ok(!/ai1wm|Get unlimited|servmask\.com\/products/i.test(html), 'no upstream upsell/brand leftovers on the page');
  });
}

test('import page chunk size and no size gate are handed to the browser', async () => {
  const { html } = await page(live, await login(live), 'shm_import');
  const m = html.match(/"chunk_size":"?(\d+)/);
  assert.ok(m && Number(m[1]) >= 65536, 'chunk_size present and sane');
  assert.ok(!html.includes('max_file_size'));
});
