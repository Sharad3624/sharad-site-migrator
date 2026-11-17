<?php
/**
 * Copyright (C) 2014-2019 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

// ================
// = Plugin Debug =
// ================
define( 'SHM_DEBUG', false );

// ==================
// = Plugin Version =
// ==================
define( 'SHM_VERSION', '1.0.5' );

// ==============================
// = Archive (.wpress) Format   =
// ==============================
// Written into package.json. Behaviour gates (e.g. "< 4.10 means flush every table first") compare against it,
// so it must stay at the upstream level this fork is compatible with and never follow SHM_VERSION.
define( 'SHM_FORMAT_VERSION', '7.5' );

// ===============
// = Plugin Name =
// ===============
define( 'SHM_PLUGIN_NAME', 'sharad-site-migrator' );

// ============================
// = Directory index.php File =
// ============================
define( 'SHM_DIRECTORY_INDEX', 'index.php' );

// ================
// = Storage Path =
// ================
define( 'SHM_STORAGE_PATH', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'shm-storage' );

// ==================
// = Error Log Path =
// ==================
define( 'SHM_ERROR_FILE', SHM_STORAGE_PATH . DIRECTORY_SEPARATOR . 'error.log' );

// ===============
// = Status Path =
// ===============
define( 'SHM_STATUS_FILE', SHM_STORAGE_PATH . DIRECTORY_SEPARATOR . 'status.js' );

// ============
// = Lib Path =
// ============
define( 'SHM_LIB_PATH', SHM_PATH . DIRECTORY_SEPARATOR . 'lib' );

// ===================
// = Controller Path =
// ===================
define( 'SHM_CONTROLLER_PATH', SHM_LIB_PATH . DIRECTORY_SEPARATOR . 'controller' );

// ==============
// = Model Path =
// ==============
define( 'SHM_MODEL_PATH', SHM_LIB_PATH . DIRECTORY_SEPARATOR . 'model' );

// ===============
// = Export Path =
// ===============
define( 'SHM_EXPORT_PATH', SHM_MODEL_PATH . DIRECTORY_SEPARATOR . 'export' );

// ===============
// = Import Path =
// ===============
define( 'SHM_IMPORT_PATH', SHM_MODEL_PATH . DIRECTORY_SEPARATOR . 'import' );

// =============
// = View Path =
// =============
define( 'SHM_TEMPLATES_PATH', SHM_LIB_PATH . DIRECTORY_SEPARATOR . 'view' );

// ===================
// = Set Bandar Path =
// ===================
define( 'BANDAR_TEMPLATES_PATH', SHM_TEMPLATES_PATH );

// ===============
// = Vendor Path =
// ===============
define( 'SHM_VENDOR_PATH', SHM_LIB_PATH . DIRECTORY_SEPARATOR . 'vendor' );

// =========================
// = ServMask Table Prefix =
// =========================
define( 'SHM_TABLE_PREFIX', 'SERVMASK_PREFIX_' );

// ========================
// = Archive Backups Name =
// ========================
define( 'SHM_BACKUPS_NAME', 'shm-backups' );

// ================
// = Storage Name =
// ================
define( 'SHM_STORAGE_NAME', 'shm-storage' );

// =========================
// = Archive Database Name =
// =========================
define( 'SHM_DATABASE_NAME', 'database.sql' );

// ========================
// = Archive Package Name =
// ========================
define( 'SHM_PACKAGE_NAME', 'package.json' );

// ==========================
// = Archive Multisite Name =
// ==========================
define( 'SHM_MULTISITE_NAME', 'multisite.json' );

// ======================
// = Archive Blogs Name =
// ======================
define( 'SHM_BLOGS_NAME', 'blogs.json' );

// =========================
// = Archive Settings Name =
// =========================
define( 'SHM_SETTINGS_NAME', 'settings.json' );

// ==========================
// = Archive Multipart Name =
// ==========================
define( 'SHM_MULTIPART_NAME', 'multipart.list' );

// ========================
// = Archive Filemap Name =
// ========================
define( 'SHM_FILEMAP_NAME', 'filemap.list' );

// =================================
// = Archive Must-Use Plugins Name =
// =================================
define( 'SHM_MUPLUGINS_NAME', 'mu-plugins' );

// =============================
// = Less Cache Extension Name =
// =============================
define( 'SHM_LESS_CACHE_NAME', '.less.cache' );

// =============================
// = Endurance Page Cache Name =
// =============================
define( 'SHM_ENDURANCE_PAGE_CACHE_NAME', 'endurance-page-cache.php' );

// ===========================
// = Endurance PHP Edge Name =
// ===========================
define( 'SHM_ENDURANCE_PHP_EDGE_NAME', 'endurance-php-edge.php' );

// ================================
// = Endurance Browser Cache Name =
// ================================
define( 'SHM_ENDURANCE_BROWSER_CACHE_NAME', 'endurance-browser-cache.php' );

// =========================
// = GD System Plugin Name =
// =========================
define( 'SHM_GD_SYSTEM_PLUGIN_NAME', 'gd-system-plugin.php' );

// =======================
// = WP Stack Cache Name =
// =======================
define( 'SHM_WP_STACK_CACHE_NAME', 'wp-stack-cache.php' );

// ===========================
// = WP.com Site Helper Name =
// ===========================
define( 'SHM_WP_COMSH_LOADER_NAME', 'wpcomsh-loader.php' );

// ===================
// = Export Log Name =
// ===================
define( 'SHM_EXPORT_NAME', 'export.log' );

// ===================
// = Import Log Name =
// ===================
define( 'SHM_IMPORT_NAME', 'import.log' );

// ==================
// = Error Log Name =
// ==================
define( 'SHM_ERROR_NAME', 'error.log' );

// ==============
// = Secret Key =
// ==============
define( 'SHM_SECRET_KEY', 'shm_secret_key' );

// =============
// = Auth User =
// =============
define( 'SHM_AUTH_USER', 'shm_auth_user' );

// =================
// = Auth Password =
// =================
define( 'SHM_AUTH_PASSWORD', 'shm_auth_password' );

// ============
// = Site URL =
// ============
define( 'SHM_SITE_URL', 'siteurl' );

// ============
// = Home URL =
// ============
define( 'SHM_HOME_URL', 'home' );

// ==================
// = Active Plugins =
// ==================
define( 'SHM_ACTIVE_PLUGINS', 'active_plugins' );

// ===========================
// = Active Sitewide Plugins =
// ===========================
define( 'SHM_ACTIVE_SITEWIDE_PLUGINS', 'active_sitewide_plugins' );

// ==========================
// = Jetpack Active Modules =
// ==========================
define( 'SHM_JETPACK_ACTIVE_MODULES', 'jetpack_active_modules' );

// ======================
// = MS Files Rewriting =
// ======================
define( 'SHM_MS_FILES_REWRITING', 'ms_files_rewriting' );

// ===================
// = Active Template =
// ===================
define( 'SHM_ACTIVE_TEMPLATE', 'template' );

// =====================
// = Active Stylesheet =
// =====================
define( 'SHM_ACTIVE_STYLESHEET', 'stylesheet' );

// ===================
// = Backups Labels  =
// ===================
define( 'SHM_BACKUPS_LABELS', 'shm_backups_labels' );

// ============
// = Cron Key =
// ============
define( 'SHM_CRON', 'cron' );

// ==============
// = Status Key =
// ==============
define( 'SHM_STATUS', 'shm_status' );

// ================
// = Messages Key =
// ================
define( 'SHM_MESSAGES', 'shm_messages' );

// =================
// = Support Email =
// =================
define( 'SHM_SUPPORT_EMAIL', 'support@servmask.com' );

// ==================
// = Max Chunk Size =
// ==================
define( 'SHM_MAX_CHUNK_SIZE', 5 * 1024 * 1024 );

// =====================
// = Max Chunk Retries =
// =====================
define( 'SHM_MAX_CHUNK_RETRIES', 10 );

// ===========================
// = Max Transaction Queries =
// ===========================
define( 'SHM_MAX_TRANSACTION_QUERIES', 1000 );

// ======================
// = Max Select Records =
// ======================
define( 'SHM_MAX_SELECT_RECORDS', 1000 );

// =======================
// = Max Storage Cleanup =
// =======================
define( 'SHM_MAX_STORAGE_CLEANUP', 24 * 60 * 60 );

// ===========================
// = WP_CONTENT_DIR Constant =
// ===========================
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

// ================
// = Uploads Path =
// ================
define( 'SHM_UPLOADS_PATH', 'uploads' );

// ==============
// = Blogs Path =
// ==============
define( 'SHM_BLOGSDIR_PATH', 'blogs.dir' );

// ==============
// = Sites Path =
// ==============
define( 'SHM_SITES_PATH', SHM_UPLOADS_PATH . DIRECTORY_SEPARATOR . 'sites' );

// ================
// = Backups Path =
// ================
define( 'SHM_BACKUPS_PATH', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'shm-backups' );

// ==========================
// = Storage index.php File =
// ==========================
// ===========================
// = Storage .htaccess File =
// ===========================
define( 'SHM_STORAGE_HTACCESS', SHM_STORAGE_PATH . DIRECTORY_SEPARATOR . '.htaccess' );

define( 'SHM_STORAGE_INDEX', SHM_STORAGE_PATH . DIRECTORY_SEPARATOR . 'index.php' );

// ==========================
// = Backups index.php File =
// ==========================
define( 'SHM_BACKUPS_INDEX', SHM_BACKUPS_PATH . DIRECTORY_SEPARATOR . 'index.php' );

// ==========================
// = Backups .htaccess File =
// ==========================
define( 'SHM_BACKUPS_HTACCESS', SHM_BACKUPS_PATH . DIRECTORY_SEPARATOR . '.htaccess' );

// ===========================
// = Backups web.config File =
// ===========================
define( 'SHM_BACKUPS_WEBCONFIG', SHM_BACKUPS_PATH . DIRECTORY_SEPARATOR . 'web.config' );

// ============================
// = WordPress .htaccess File =
// ============================
define( 'SHM_WORDPRESS_HTACCESS', ABSPATH . DIRECTORY_SEPARATOR . '.htaccess' );

// ================================
// = WP Migration Plugin Base Dir =
// ================================
if ( defined( 'SHM_PLUGIN_BASENAME' ) ) {
	define( 'SHM_PLUGIN_BASEDIR', dirname( SHM_PLUGIN_BASENAME ) );
} else {
	define( 'SHM_PLUGIN_BASEDIR', 'sharad-site-migrator' );
}
