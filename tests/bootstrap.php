<?php
/**
 * Unit-test bootstrap: loads the plugin's pure classes without WordPress.
 * Only the handful of WP functions those classes touch are shimmed here;
 * anything touching the database or HTTP is covered by tests/e2e instead.
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

define( 'ABSPATH', __DIR__ . '/' );
define( 'SHM_TEST_PLUGIN', realpath( __DIR__ . '/../plugin/sharad-site-migrator' ) );
define( 'SHM_TEST_TMP', sys_get_temp_dir() . '/shm-unit-' . getmypid() );
@mkdir( SHM_TEST_TMP . '/wp-content', 0777, true );
define( 'WP_CONTENT_DIR', SHM_TEST_TMP . '/wp-content' );

define( 'SHM_PLUGIN_BASENAME', 'sharad-site-migrator/sharad-site-migrator.php' );
define( 'SHM_PATH', SHM_TEST_PLUGIN );
define( 'SHM_URL', 'http://example.test/wp-content/plugins/sharad-site-migrator' );
define( 'SHM_STORAGE_URL', SHM_URL . '/storage' );
define( 'SHM_BACKUPS_URL', 'http://example.test/wp-content/shm-backups' );
define( 'SHM_THEMES_PATH', SHM_TEST_TMP . '/themes' );

// ---- WP shims -------------------------------------------------------------
$GLOBALS['shm_test_options'] = array();
function __( $s ) { return $s; }
function _e( $s ) { echo $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function get_option( $k, $d = false ) { return $GLOBALS['shm_test_options'][ $k ] ?? $d; }
function update_option( $k, $v ) { $GLOBALS['shm_test_options'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['shm_test_options'][ $k ] ); return true; }
function apply_filters( $tag, $value ) { return $value; }
function add_filter( $tag, $fn, $prio = 10, $args = 1 ) { return true; }
function get_site_url( $b = null ) { return 'http://localhost/wordpress'; }
function wp_generate_password( $len = 12, $special = true ) {
	return substr( str_shuffle( str_repeat( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 4 ) ), 0, $len );
}
function validate_file( $file, $allowed = array() ) {
	if ( false !== strpos( $file, '..' ) ) { return 1; }
	if ( false !== strpos( $file, './' ) ) { return 1; }
	if ( ':' === substr( $file, 1, 1 ) ) { return 2; }
	return 0;
}
// Faithful port of WordPress' is_serialized() (GPL), enough for the replace tests.
function is_serialized( $data, $strict = true ) {
	if ( ! is_string( $data ) ) { return false; }
	$data = trim( $data );
	if ( 'N;' === $data ) { return true; }
	if ( strlen( $data ) < 4 || ':' !== $data[1] ) { return false; }
	if ( $strict ) {
		$lastc = substr( $data, -1 );
		if ( ';' !== $lastc && '}' !== $lastc ) { return false; }
	} else {
		$semicolon = strpos( $data, ';' );
		$brace     = strpos( $data, '}' );
		if ( false === $semicolon && false === $brace ) { return false; }
		if ( false !== $semicolon && $semicolon < 3 ) { return false; }
		if ( false !== $brace && $brace < 4 ) { return false; }
	}
	$token = $data[0];
	switch ( $token ) {
		case 's':
			if ( $strict ) {
				if ( '"' !== substr( $data, -2, 1 ) ) { return false; }
			} elseif ( false === strpos( $data, '"' ) ) { return false; }
			// no break
		case 'a':
		case 'O':
		case 'E':
			return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
		case 'b':
		case 'i':
		case 'd':
			$end = $strict ? '$' : '';
			return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
	}
	return false;
}

// ---- plugin files under test (order matters: constants first) --------------
require_once SHM_TEST_PLUGIN . '/constants.php';
require_once SHM_TEST_PLUGIN . '/exceptions.php';
require_once SHM_TEST_PLUGIN . '/functions.php';
$v = SHM_TEST_PLUGIN . '/lib/vendor/servmask';
foreach ( array(
	'filesystem/class-shm-directory.php',
	'filesystem/class-shm-file.php',
	'filesystem/class-shm-file-htaccess.php',
	'archiver/class-shm-archiver.php',
	'archiver/class-shm-compressor.php',
	'archiver/class-shm-extractor.php',
	'database/class-shm-database-utility.php',
	'database/class-shm-database.php',
) as $f ) {
	require_once "$v/$f";
}
// Model classes that are pure enough to test directly.
require_once SHM_TEST_PLUGIN . '/lib/model/class-shm-log.php';
require_once SHM_TEST_PLUGIN . '/lib/model/class-shm-handler.php';
require_once SHM_TEST_PLUGIN . '/lib/model/import/class-shm-import-upload.php';
require_once SHM_TEST_PLUGIN . '/lib/controller/class-shm-import-controller.php';

register_shutdown_function( function () {
	@exec( 'rm -rf ' . escapeshellarg( SHM_TEST_TMP ) );
} );
