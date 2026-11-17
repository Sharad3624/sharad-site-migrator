<?php
/**
 * Plugin Name: Sharad Site Migrator
 * Description: Export, import, back up and restore a WordPress site between live, demo and local environments with no upload-size limit. Backups are kept in the site's own storage. A GPL fork of All-in-One WP Migration 7.5.
 * Author: Sharad
 * Version: 1.0.5
 * Requires PHP: 7.4
 * Text Domain: sharad-site-migrator
 * Domain Path: /languages
 * License: GPLv3 or later
 *
 * Based on All-in-One WP Migration 7.5, Copyright (C) 2014-2019 ServMask Inc.
 * Modifications Copyright (C) 2026 Sharad. See NOTICE.md for what changed.
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

// Check SSL Mode
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

// Plugin Basename
define( 'SHM_PLUGIN_BASENAME', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );

// Plugin Path
define( 'SHM_PATH', dirname( __FILE__ ) );

// Plugin URL
define( 'SHM_URL', plugins_url( '', SHM_PLUGIN_BASENAME ) );

// Plugin Storage URL
define( 'SHM_STORAGE_URL', content_url( 'shm-storage' ) );

// Plugin Backups URL
define( 'SHM_BACKUPS_URL', content_url( 'shm-backups', SHM_PLUGIN_BASENAME ) );

// Themes Absolute Path
define( 'SHM_THEMES_PATH', get_theme_root() );

// Include constants
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'constants.php';

// Include deprecated
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'deprecated.php';

// Include functions
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'functions.php';

// Include exceptions
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'exceptions.php';

// Include loader
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'loader.php';

// =========================================================================
// = All app initialization is done in Shm_Main_Controller __constructor =
// =========================================================================
$main_controller = new Shm_Main_Controller();
