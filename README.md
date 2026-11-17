# Sharad Site Migrator

GPL fork of **All-in-One WP Migration 7.5** (ServMask Inc.) by **Sharad**. Move a WordPress site between live, demo and
local with **no upload-size limit**; backups are kept in the site's own storage (`wp-content/shm-backups`).

* Install: upload `dist/sharad-site-migrator-1.0.6.zip` (Plugins → Add New → Upload) or copy `plugin/sharad-site-migrator/`.
* What changed from upstream: `plugin/sharad-site-migrator/NOTICE.md`.
* Not included: cloud storage, multisite, WP-CLI, scheduled backups, import from URL/FTP.
* PHP compatibility: source and destination must use the same major/minor PHP version (for example, 8.2 on both). Imports with a different or missing PHP version stop before overwriting the destination. Patch versions may differ; this check does not certify third-party plugin/theme compatibility.
* Import completion remains visible in the original tab if restoring the database logs you out. Log in with the imported site's credentials for subsequent admin work.

## Tests
`./bin/test-all.sh` runs unit (XAMPP PHP plus the available CLI PHP), browser-JS (jsdom) and real-WordPress E2E suites (needs XAMPP with MariaDB running;
`composer install` and `cd tests/js && npm install` once). The E2E suites build throw-away sites (`sharad_sim_*` databases, ports 8081-8084)
and remove nothing else. `SHM_REAL=1` also exports your real local site (installs the plugin there temporarily, then removes it).
`node tests/e2e/env.mjs clean` removes the throw-away sites.

## Releases
Download the installable plugin ZIP from [GitHub Releases](https://github.com/Sharad3624/sharad-site-migrator/releases/latest).
Use the attached `sharad-site-migrator-1.0.6.zip` with WordPress Plugins → Add New → Upload Plugin.
The GitHub source-code ZIP contains the development project and is not the installable plugin ZIP.

## License
GPL-3.0-or-later. Original copyright notices are retained; see [LICENSE](LICENSE) and
[NOTICE.md](plugin/sharad-site-migrator/NOTICE.md).
