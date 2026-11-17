# Sharad Site Migrator

A fork of **All-in-One WP Migration 7.5** by ServMask Inc. (GPL), maintained by Sharad.
The original copyright and GPL licence notices are kept in every file and in `LICENSE`.
This is an independent project; it is not affiliated with or endorsed by ServMask.

## What changed from upstream 7.5

| Area | Upstream | This fork |
|---|---|---|
| Import size | Browser refuses files above `wp_max_upload_size()` and shows a paid-extension upsell; the file is sent in one POST | Chunked, resumable, idempotent upload; any size. Chunk size is derived from the server's real limits and halves itself on HTTP 413 |
| Restore from backup list | Stub that opens a purchase message | Starts an import of the stored backup (no re-upload) |
| Paid extension scaffolding, updater, feedback/report phone-home | ~700 lines of descriptors, remote update checks, posts to servmask.com | Removed |
| Backup file names | `host-date-time-<rand 100..999>.wpress` (guessable) | `…-<16 hex from random_bytes>.wpress` |
| Secret key | 12 chars, `!==` compare | 32 chars, `hash_equals`, type-safe, never matches an unset key |
| Archive extraction | Does not reject `..` in entry paths | Refuses path-traversal entries |
| Search/replace of serialized data | `unserialize()` on DB content; skipped private/protected props and classes not loaded on the target | Length-aware tokenizer, no `unserialize()` |
| `shm_parse_size()` / `parse_url()` | PHP 8 warnings; decimal units | ini-style units; null-safe |
| PHP compatibility | Warning only for PHP 5 to 7 | Reject different PHP major/minor versions and unknown version metadata before overwriting the destination; patch versions may differ |
| Completion polling | Login can expire when the database is restored | Status remains accessible with the migration secret after database restoration invalidates login cookies |

Backups are stored in `wp-content/shm-backups/` (the site's own storage). Cloud-storage providers,
multisite and WP-CLI are intentionally not part of this fork.
