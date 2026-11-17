// Builds throw-away WordPress sites for the E2E suite. Touches ONLY:
//   * <SIM_DIR>/{live,demo}            (copies of the core files of an existing local WP; no wp-content data)
//   * MariaDB databases sharad_sim_live / sharad_sim_demo
//   * php -S processes on 127.0.0.1:8081 and localhost:8082
// `local` is the user's real XAMPP site; it is only ever exported FROM unless a test says otherwise.
import { execFileSync, spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { PHP, MYSQL, Site, wpctl } from './driver.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));
export const PLUGIN_SRC = path.resolve(here, '../../plugin/sharad-site-migrator');
export const CORE_SRC = process.env.SHM_CORE_SRC || '/Applications/XAMPP/xamppfiles/htdocs/wordpress';
export const SIM_DIR = process.env.SHM_SIM_DIR || '/private/tmp/claude-501/-Users-allin-Documents-SHARAD-node-paractice/6e0adee5-2d1a-49c8-a775-b78c35114682/scratchpad/sim';

export const SITES = {
  live:   { name: 'live',   db: 'sharad_sim_live',   host: '127.0.0.1', port: 8081 },
  demo:   { name: 'demo',   db: 'sharad_sim_demo',   host: 'localhost', port: 8082 },
  sub:    { name: 'sub',    db: 'sharad_sim_sub',    host: 'localhost', port: 8083, subdir: '/wordpress' },   // like http://localhost/wordpress
  prefix: { name: 'prefix', db: 'sharad_sim_prefix', host: '127.0.0.1', port: 8084, prefix: 'abc_' },        // different $table_prefix
};
const urlOf = (s) => `http://${s.host}:${s.port}${s.subdir || ''}`;
const docrootOf = (s) => path.join(SIM_DIR, s.name);
const rootOf = (s) => path.join(docrootOf(s), s.subdir || '');
/** the user's real XAMPP site (only ever exported FROM by the suite) */
export const realSite = () => new Site({ name: 'real', root: CORE_SRC, url: 'http://localhost/wordpress' });

const sh = (cmd, args, opt = {}) => execFileSync(cmd, args, { encoding: 'utf8', ...opt });
const sql = (q) => sh(MYSQL, ['-uroot', '-N', '-e', q]);

export function deployPlugin(root) {
  const dest = path.join(root, 'wp-content/plugins/sharad-site-migrator');
  fs.mkdirSync(dest, { recursive: true });
  // like a WP-admin install: group = the directory's group (BSD inherits it), group-writable so the web server user can write
  sh('rsync', ['-rlt', '--no-g', '--no-o', '--no-p', '--delete', PLUGIN_SRC + '/', dest + '/']);
  sh('chmod', ['-R', 'u+rwX,g+rwX,o+rX', dest]);
}

function writeConfig(s, root) {
  const salt = () => crypto.randomBytes(24).toString('base64');
  const keys = ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT']
    .map((k) => `define('${k}', '${salt()}');`).join('\n');
  fs.writeFileSync(path.join(root, 'wp-config.php'), `<?php
define('DB_NAME', '${s.db}'); define('DB_USER', 'root'); define('DB_PASSWORD', ''); define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4'); define('DB_COLLATE', '');
${keys}
$table_prefix = '${s.prefix || 'wp_'}';
define('WP_DEBUG', true); define('WP_DEBUG_LOG', true); define('WP_DEBUG_DISPLAY', false);
define('DISABLE_WP_CRON', true); define('WP_MEMORY_LIMIT', '256M');
if ( ! defined('ABSPATH') ) { define('ABSPATH', __DIR__ . '/'); }
require_once ABSPATH . 'wp-settings.php';
`);
}

function createSite(s) {
  const root = rootOf(s);
  fs.rmSync(docrootOf(s), { recursive: true, force: true });
  fs.mkdirSync(root, { recursive: true });
  sh('rsync', ['-a', '--exclude', 'wp-content', '--exclude', 'wp-config.php', '--exclude', '*.log', '--exclude', '.htaccess', CORE_SRC + '/', root + '/']);
  for (const d of ['plugins', 'themes', 'uploads', 'mu-plugins']) fs.mkdirSync(path.join(root, 'wp-content', d), { recursive: true });
  fs.writeFileSync(path.join(root, 'wp-content/index.php'), '<?php // Silence is golden.\n');
  const themes = fs.readdirSync(path.join(CORE_SRC, 'wp-content/themes')).filter((t) => /^twenty/.test(t)).sort().slice(-1);
  for (const t of themes) sh('rsync', ['-a', path.join(CORE_SRC, 'wp-content/themes', t) + '/', path.join(root, 'wp-content/themes', t) + '/']);
  sql(`DROP DATABASE IF EXISTS \`${s.db}\`; CREATE DATABASE \`${s.db}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci`);
  writeConfig(s, root);
  const url = urlOf(s);
  fs.writeFileSync(path.join(root, 'shm-install.php'), `<?php
define('WP_INSTALLING', true); $_SERVER['HTTP_HOST'] = '${s.host}:${s.port}'; define('WP_HOME', '${url}'); define('WP_SITEURL', '${url}');
require __DIR__ . '/wp-load.php'; require ABSPATH . 'wp-admin/includes/upgrade.php';
wp_install('Sim ${s.name}', 'admin', 'admin@example.test', true, '', 'admin-pw-${s.name}');
echo "installed";
`);
  sh(PHP, [path.join(root, 'shm-install.php')]);
  fs.rmSync(path.join(root, 'shm-install.php'));
  sh(PHP, ['-r', `require '${root}/wp-load.php'; update_option('home','${url}'); update_option('siteurl','${url}');`]);
  deployPlugin(root);
  const site = new Site({ name: s.name, root, url });
  const r = wpctl(root, 'activate');
  if (!r.active) throw new Error(`activate failed on ${s.name}: ${JSON.stringify(r)}`);
  return site;
}

// "alive" = the HTTP server answers at all (a half-imported site legitimately answers 500)
async function alive(url) { try { await fetch(url + '/wp-login.php', { redirect: 'manual' }); return true; } catch { return false; } }

function serve(s) {
  const root = docrootOf(s);
  const log = fs.openSync(path.join(SIM_DIR, `${s.name}-server.log`), 'a');
  const child = spawn(PHP, ['-S', `${s.host}:${s.port}`, '-t', root,
    '-d', 'post_max_size=8M', '-d', 'upload_max_filesize=8M', '-d', 'memory_limit=512M', '-d', 'max_execution_time=0',
    '-d', 'display_errors=0', '-d', 'log_errors=1'], { env: { ...process.env, PHP_CLI_SERVER_WORKERS: '8' }, detached: true, stdio: ['ignore', log, log] });
  child.unref();
  return child.pid;
}

/** Bring up live+demo (idempotent: pass {fresh:true} to rebuild). Returns { live, demo, local }. */
export async function up({ fresh = false } = {}) {
  fs.mkdirSync(SIM_DIR, { recursive: true });
  const stateFile = path.join(SIM_DIR, 'state.json');
  const state = fs.existsSync(stateFile) ? JSON.parse(fs.readFileSync(stateFile, 'utf8')) : { pids: {} };
  const out = {};
  for (const s of Object.values(SITES)) {
    const root = rootOf(s);
    const exists = fs.existsSync(path.join(root, 'wp-config.php'));
    let site;
    if (fresh || !exists) {
      if (state.pids[s.name]) { try { process.kill(state.pids[s.name]); } catch { /* gone */ } }
      site = createSite(s);
      state.pids[s.name] = null;
    } else {
      site = new Site({ name: s.name, root, url: urlOf(s) });
      deployPlugin(root);
    }
    if (!(await alive(site.url))) {
      state.pids[s.name] = serve(s);
      for (let i = 0; i < 50 && !(await alive(site.url)); i++) await new Promise((r) => setTimeout(r, 200));
      if (!(await alive(site.url))) throw new Error(`${s.name} did not come up on ${site.url}`);
    }
    out[s.name] = site;
  }
  fs.writeFileSync(stateFile, JSON.stringify(state));
  out.real = realSite();
  return out;
}

export function down() {
  const stateFile = path.join(SIM_DIR, 'state.json');
  if (!fs.existsSync(stateFile)) return;
  for (const pid of Object.values(JSON.parse(fs.readFileSync(stateFile, 'utf8')).pids)) { if (pid) try { process.kill(pid); } catch { /* gone */ } }
}

export function dropDatabases() { for (const s of Object.values(SITES)) sql(`DROP DATABASE IF EXISTS \`${s.db}\``); }

if (fileURLToPath(import.meta.url) === process.argv[1]) {
  const cmd = process.argv[2];
  if (cmd === 'up') { const e = await up({ fresh: process.argv.includes('--fresh') }); console.log(Object.values(e).map((s) => `${s.name}: ${s.url}`).join('\n')); }
  else if (cmd === 'down') { down(); console.log('stopped'); }
  else if (cmd === 'clean') { down(); dropDatabases(); fs.rmSync(SIM_DIR, { recursive: true, force: true }); console.log('removed sim sites + databases'); }
  else console.log('usage: node env.mjs up [--fresh] | down | clean');
}
