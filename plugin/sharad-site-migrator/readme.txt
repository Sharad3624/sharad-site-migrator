=== Sharad Site Migrator ===
Contributors: sharad
Tags: migrate, backup, restore, clone, move site
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.5
License: GPLv3 or later

Export, import, back up and restore a WordPress site between live, demo and local environments, with no upload-size limit.

== Description ==
Sharad Site Migrator is a GPL fork of All-in-One WP Migration 7.5 (ServMask Inc.). The original copyright and licence are kept; see NOTICE.md.

* Import a site of any size: the browser uploads the archive in resumable chunks sized to the server's real limits.
* Restore a backup straight from the site's own storage (wp-content/shm-backups) - no re-upload.
* Rewrites URLs, serialized data and absolute paths when the address changes (live, demo, local, http/https, sub-folder).
* Uses the same .wpress archive format as All-in-One WP Migration (importing archives made by the original plugin has not been tested yet).
* No paid extensions, no update checks, no telemetry.

Not included: cloud-storage providers, multisite, WP-CLI, scheduled backups, import from URL/FTP.

== Installation ==
Upload the folder to wp-content/plugins and activate. Tools appear under "Sharad Migrator" in the admin menu.

== Changelog ==
= 1.0.5 =
Align heading, backup action and Create Backup icons.

= 1.0.4 =
Import and Export dropdowns fit their contents in both text directions.

= 1.0.3 =
Fix numeric addition of WordPress-localized string chunk sizes so every request remains bounded, including the second chunk of large archives.

= 1.0.2 =
Detect incomplete upload chunks, retry safely, require complete server acknowledgements, and check total upload size before extraction.

= 1.0.1 =
Database preflight probe failures are advisory and logged.

= 1.0.0 =
First release. See NOTICE.md for the list of changes from All-in-One WP Migration 7.5.
