#!/usr/bin/env bash
# Runs every test layer for Sharad Site Migrator.
#   SHM_E2E=0     skip the real-WordPress suites (unit + browser only)
#   SHM_HEAVY=0   skip the 600 MB test
#   SHM_REAL=1    ALSO export your real local WordPress (tests/e2e/real.test.mjs); it installs the plugin there
#                 temporarily and removes it again. Off by default because it touches a site that isn't a throw-away.
# Needs: XAMPP (PHP + MariaDB running), node 20+, `composer install` and `npm install` in tests/js done once.
set -u
cd "$(dirname "$0")/.."
PHP="${PHP:-/Applications/XAMPP/xamppfiles/bin/php}"
fail=0
run() { local title="$1"; shift; echo; echo "=== $title"; "$@" || fail=1; }

run "PHPUnit (unit) on $("$PHP" -r 'echo PHP_VERSION;')" "$PHP" vendor/bin/phpunit --no-progress
command -v php >/dev/null && [ "$(command -v php)" != "$PHP" ] && run "PHPUnit (unit) on $(php -r 'echo PHP_VERSION;')" php vendor/bin/phpunit --no-progress
run "Browser bundles in jsdom" bash -c 'cd tests/js && node --test'
if [ "${SHM_E2E:-1}" = 1 ]; then
  run "E2E: migrations (live/demo/subdir/prefix/https/restore)" node --test tests/e2e/migration.test.mjs
  run "E2E: admin pages (CSS/JS actually load)" node --test tests/e2e/admin-pages.test.mjs
  run "E2E: try to break it"                                   node --test tests/e2e/break.test.mjs
  [ "${SHM_HEAVY:-1}" = 1 ] && run "E2E: 600 MB through an 8 MB request limit" node --test tests/e2e/heavy.test.mjs
  [ "${SHM_REAL:-0}" = 1 ]  && run "E2E: your real local site" node --test tests/e2e/real.test.mjs
fi
echo; [ $fail = 0 ] && echo "ALL GREEN" || echo "FAILURES (see above)"
exit $fail
