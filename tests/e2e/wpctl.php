<?php
/**
 * Tiny WP-CLI stand-in for the E2E suite (WP-CLI isn't installed here).
 *   php wpctl.php <wp-root> <command> [args]
 * Prints one JSON document on a line starting with "@@" (WordPress may print notices too).
 * Commands: activate | deactivate | info | status | fixture | fingerprint | leftovers <needle> | set-url <url>
 * Run it with XAMPP's PHP (has mysqli): /Applications/XAMPP/xamppfiles/bin/php
 */
error_reporting( E_ALL & ~E_DEPRECATED );
$root = rtrim( $argv[1] ?? '', '/' );
$cmd  = $argv[2] ?? '';
if ( ! is_file( "$root/wp-load.php" ) ) {
	fwrite( STDERR, "not a WordPress root: $root\n" );
	exit( 2 );
}
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = '/';
define( 'WP_USE_THEMES', false );
ob_start();
require "$root/wp-load.php";
require_once ABSPATH . 'wp-admin/includes/taxonomy.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
ob_end_clean();

function out( $data ) { echo "\n@@" . json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n"; exit( 0 ); }

const PLUGIN = 'sharad-site-migrator/sharad-site-migrator.php';

/** Every spelling of the site URL that can appear in stored data. */
function url_variants( string $home ): array {
	$noscheme = preg_replace( '#^https?:#', '', $home );
	$v = array( $home, str_replace( '/', '\/', $home ), urlencode( $home ), rawurlencode( $home ), $noscheme, str_replace( '/', '\/', $noscheme ) );
	if ( strpos( $home, 'https://' ) === 0 ) { $v[] = 'http:' . $noscheme; }
	if ( strpos( $home, 'http://' ) === 0 ) { $v[] = 'https:' . $noscheme; }
	usort( $v, function ( $a, $b ) { return strlen( $b ) - strlen( $a ); } );
	return array_values( array_unique( $v ) );
}

/** Independent of the plugin: walk (possibly serialized) values and swap the site URL for a token. */
function norm_prefix( string $v ): string {
	global $wpdb;
	return ( $wpdb->prefix !== '' && strpos( $v, $wpdb->prefix ) === 0 ) ? '{{P}}' . substr( $v, strlen( $wpdb->prefix ) ) : $v;
}

/** Absolute server paths are rewritten by a migration (that is a feature), so compare them as tokens. */
function norm_paths( string $v ): string {
	$pairs = array( WP_CONTENT_DIR => '{{CONTENT}}', rtrim( ABSPATH, '/' ) => '{{ABS}}' );
	foreach ( $pairs as $from => $to ) {
		$v = str_replace( array( $from, str_replace( '/', '\\/', $from ) ), $to, $v );
	}
	return $v;
}

function normalize( $value, array $variants ) {
	if ( is_string( $value ) ) {
		if ( is_serialized( $value ) ) {
			$un = @unserialize( $value, array( 'allowed_classes' => false ) );
			if ( $un !== false || $value === 'b:0;' ) {
				return array( '__ser' => normalize( $un, $variants ) );
			}
		}
		return norm_paths( str_replace( $variants, '{{HOME}}', $value ) );
	}
	if ( is_array( $value ) ) {
		$o = array();
		foreach ( $value as $k => $v ) { $o[ is_string( $k ) ? str_replace( $variants, '{{HOME}}', $k ) : $k ] = normalize( $v, $variants ); }
		return $o;
	}
	if ( is_object( $value ) ) {
		return array( '__obj' => get_class( $value ), 'p' => normalize( (array) $value, $variants ) );
	}
	return $value;
}

switch ( $cmd ) {
	case 'activate':
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$r = activate_plugin( PLUGIN );
		out( is_wp_error( $r ) ? array( 'error' => $r->get_error_message() ) : array( 'active' => is_plugin_active( PLUGIN ), 'key' => get_option( 'shm_secret_key' ) ) );

	case 'cleanup':     // undo everything the migrator stored in this site's database
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( PLUGIN );
		foreach ( array( 'shm_secret_key', 'shm_status', 'shm_auth_user', 'shm_auth_password', 'shm_backups_labels' ) as $o ) { delete_option( $o ); }
		out( array( 'active' => is_plugin_active( PLUGIN ), 'key' => get_option( 'shm_secret_key' ) ) );

	case 'deactivate':
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( PLUGIN );
		out( array( 'active' => is_plugin_active( PLUGIN ) ) );

	case 'info':
		global $wp_version, $wpdb;
		out( array(
			'home' => get_option( 'home' ), 'siteurl' => get_option( 'siteurl' ), 'key' => get_option( 'shm_secret_key' ),
			'wp' => $wp_version, 'php' => PHP_VERSION, 'db' => $wpdb->db_version(), 'prefix' => $wpdb->prefix,
			'content' => WP_CONTENT_DIR, 'active' => in_array( PLUGIN, (array) get_option( 'active_plugins' ), true ),
		) );

	case 'status':
		out( get_option( 'shm_status', array() ) );

	case 'set-url':
		update_option( 'home', $argv[3] );
		update_option( 'siteurl', $argv[3] );
		out( array( 'home' => get_option( 'home' ) ) );

	case 'leftovers':   // rows (any table) still containing the needle
		global $wpdb;
		$needle = $argv[3];
		$hits = array();
		foreach ( $wpdb->get_col( 'SHOW TABLES' ) as $t ) {
			$cols = $wpdb->get_results( "SHOW COLUMNS FROM `$t`" );
			$where = array();
			foreach ( $cols as $c ) {
				if ( preg_match( '/char|text|blob/i', $c->Type ) ) { $where[] = "`{$c->Field}` LIKE '%" . esc_sql( $wpdb->esc_like( $needle ) ) . "%'"; }
			}
			if ( $where ) {
				$n = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$t` WHERE " . implode( ' OR ', $where ) );
				if ( $n ) { $hits[ $t ] = $n; }
			}
		}
		out( $hits );

	case 'fingerprint':
		global $wpdb;
		$home = get_option( 'home' );
		$var  = url_variants( $home );
		$volatile = '/^(_transient|_site_transient|shm_|cron$|recently_activated|fs_|rewrite_rules|auto_updates|can_compress|WPLANG|db_upgraded|\{\{P\}\}force_deactivated|finished_updating|admin_email_lifespan|_elementor_|elementor_log|action_scheduler|wc_admin|woocommerce_admin_|jetpack|duplicate_page|redirection_|\{\{P\}\}mail_smtp_email_sending_debug)/';
		$tables = array();
		foreach ( $wpdb->get_col( 'SHOW TABLES' ) as $t ) {
			$order = ( $t === $wpdb->options ) ? 'option_name' : '1';   // the import re-creates some options with new ids
			$rows = $wpdb->get_results( "SELECT * FROM `$t` ORDER BY $order", ARRAY_A );
			$h = hash_init( 'md5' ); $n = 0; $optrows = array();
			foreach ( $rows as $r ) {
				if ( $t === $wpdb->options && preg_match( $volatile, norm_prefix( $r['option_name'] ) ) ) { continue; }
				if ( $t === $wpdb->options && in_array( $r['option_name'], array( 'home', 'siteurl' ), true ) ) { continue; }
				if ( $t === $wpdb->usermeta && preg_match( '/session_tokens/', $r['meta_key'] ) ) { continue; }
				if ( $t === $wpdb->options ) {
					// order by the prefix-normalised name: 'abc_user_roles' and 'wp_user_roles' sort differently
					$optrows[ norm_prefix( $r['option_name'] ) ] = json_encode( normalize( array( norm_prefix( $r['option_name'] ), $r['option_value'] ), $var ) );
					$n++;
					continue;
				}
				if ( $t === $wpdb->usermeta ) { $r['meta_key'] = norm_prefix( $r['meta_key'] ); }
				hash_update( $h, json_encode( normalize( $r, $var ) ) );
				$n++;
			}
			if ( $t === $wpdb->options ) { ksort( $optrows ); foreach ( $optrows as $line ) { hash_update( $h, $line ); } }
			$tables[ norm_prefix( $t ) ] = array( 'rows' => $n, 'md5' => hash_final( $h ) );
		}
		$files = array();
		foreach ( array( 'uploads', 'themes', 'plugins', 'mu-plugins' ) as $d ) {
			$base = WP_CONTENT_DIR . "/$d";
			if ( ! is_dir( $base ) ) { continue; }
			$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $f ) {
				if ( ! $f->isFile() ) { continue; }
				$rel = $d . substr( $f->getPathname(), strlen( $base ) );
				if ( strpos( $rel, 'plugins/sharad-site-migrator' ) === 0 || strpos( $rel, 'plugins/index.php' ) === 0 ) { continue; }
				if ( preg_match( '#(^|/)(cache|upgrade)/|debug\.log$#', $rel ) ) { continue; }
				$files[ $rel ] = hash_file( 'sha256', $f->getPathname() );
			}
		}
		ksort( $files );
		out( array( 'tables' => $tables, 'files' => $files ) );

	case 'optdump':     // per-option normalized hash, for pinpointing a fingerprint mismatch
		global $wpdb;
		$var = url_variants( get_option( 'home' ) );
		$o = array();
		foreach ( $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options}", ARRAY_A ) as $r ) {
			if ( preg_match( '/^(_transient|_site_transient|shm_|cron$|home$|siteurl$)/', $r['option_name'] ) ) { continue; }
			$o[ norm_prefix( $r['option_name'] ) ] = md5( json_encode( normalize( $r['option_value'], $var ) ) );
		}
		out( $o );

	case 'mutate':      // destroy content so a Restore has something to bring back
		global $wpdb;
		foreach ( get_posts( array( 'numberposts' => -1, 'post_type' => 'any', 'post_status' => 'any' ) ) as $p ) { wp_delete_post( $p->ID, true ); }
		update_option( 'blogname', 'MUTATED' );
		$wpdb->query( "TRUNCATE TABLE `{$wpdb->prefix}shm_custom`" );
		@unlink( wp_upload_dir()['path'] . '/pic1.png' );
		out( array( 'posts' => (int) wp_count_posts()->publish ) );

	case 'fixture':
		$seed = (int) ( $argv[3] ?? 1 );
		$mb   = (int) ( $argv[4] ?? 7 );     // size of the big media file
		mt_srand( $seed );
		global $wpdb;
		$home = get_option( 'home' );
		$esc  = str_replace( '/', '\/', $home );
		$up   = wp_upload_dir();
		wp_mkdir_p( $up['path'] );
		wp_mkdir_p( $up['basedir'] . '/nested/deep/er' );
		// files: a real PNG, unicode name, empty file, dotfile, big binary
		$png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );
		file_put_contents( $up['path'] . '/pic1.png', $png );
		file_put_contents( $up['path'] . '/pic2.png', $png . 'x' );
		file_put_contents( $up['path'] . '/café-日本-🙂.txt', "unicode filename\n" );
		file_put_contents( $up['path'] . '/empty.dat', '' );
		file_put_contents( $up['path'] . '/.hidden', 'dot' );
		file_put_contents( $up['basedir'] . '/nested/deep/er/leaf.bin', random_bytes( 4097 ) );
		foreach ( array( 'with space & amp.txt', "quote'single.txt", 'percent%20enc.txt', '#hash.txt', '-leading-dash.txt', 'ünï-ñ.txt', str_repeat( 'n', 251 ) . '.txt' ) as $odd ) {
			file_put_contents( $up['path'] . '/' . $odd, "odd:$odd" );
		}
		$deep = $up['basedir'] . '/' . implode( '/', array_fill( 0, 30, 'd23456789' ) );
		wp_mkdir_p( $deep );
		file_put_contents( $deep . '/deep.txt', 'deep' );
		if ( is_file( $up['path'] . '/readonly.txt' ) ) {
			chmod( $up['path'] . '/readonly.txt', 0644 );
		}
		file_put_contents( $up['path'] . '/readonly.txt', 'ro' );
		chmod( $up['path'] . '/readonly.txt', 0444 );
		// a real plugin + mu-plugin that must stay active/present after a migration
		wp_mkdir_p( WP_CONTENT_DIR . '/plugins/shm-fixture-plugin' );
		file_put_contents( WP_CONTENT_DIR . '/plugins/shm-fixture-plugin/shm-fixture-plugin.php', "<?php\n/** Plugin Name: Shm Fixture Plugin */\nadd_filter( 'the_title', function ( \$t ) { return \$t; } );\n" );
		wp_mkdir_p( WP_CONTENT_DIR . '/mu-plugins' );
		file_put_contents( WP_CONTENT_DIR . '/mu-plugins/shm-fixture-mu.php', "<?php\n// mu fixture\n" );
		activate_plugin( 'shm-fixture-plugin/shm-fixture-plugin.php' );
		$big = fopen( $up['path'] . '/big-media.bin', 'wb' );
		for ( $i = 0; $i < $mb; $i++ ) { fwrite( $big, random_bytes( 1048576 ) ); }
		fclose( $big );
		foreach ( array( 'pic1.png', 'pic2.png' ) as $f ) {
			$id = wp_insert_attachment( array( 'post_title' => $f, 'post_mime_type' => 'image/png', 'post_status' => 'inherit', 'guid' => $up['url'] . "/$f" ), $up['path'] . "/$f" );
		}
		$cats = array();
		foreach ( array( 'Café ☕', 'Japón 日本', 'plain' ) as $c ) { $cats[] = wp_create_category( $c ); }
		for ( $i = 1; $i <= 30; $i++ ) {
			$meta = array(
				'_fixture_serialized' => array( 'url' => "$home/page-$i/", 'nested' => array( 'img' => "$home/wp-content/uploads/pic1.png", 'n' => $i, 'f' => 1.5, 'b' => true, 'z' => null ), 'text' => 'ünïcödé 🙂 ' . $i ),
				'_elementor_data'     => json_encode( array( array( 'id' => "e$i", 'settings' => array( 'url' => "$home/x$i", 'image' => array( 'url' => "$home/wp-content/uploads/pic1.png" ) ) ) ) ),
				'_fixture_json_esc'   => '{"u":"' . $esc . '\/api\/' . $i . '"}',
			);
			$pid = wp_insert_post( array(
				'post_title' => "Héllo 日本 🙂 post $i", 'post_status' => 'publish', 'post_category' => array( $cats[ $i % 3 ] ),
				'post_content' => "<!-- wp:image {\"url\":\"{$esc}\\/wp-content\\/uploads\\/pic1.png\"} --><img src=\"$home/wp-content/uploads/pic1.png\"/><!-- /wp:image -->\n<p><a href=\"$home/?p=$i\">link $i</a> " . str_repeat( 'lorem ipsum dolor ünï 🙂 ', $i === 7 ? 40000 : 20 ) . '</p>',
				'post_excerpt' => "excerpt $home/$i",
			) );
			foreach ( $meta as $k => $v ) { update_post_meta( $pid, $k, $v ); }
		}
		update_post_meta( 1, '_fixture_big', str_repeat( 'B', 1500000 ) . " $home/end" );
		for ( $i = 1; $i <= 5; $i++ ) { wp_insert_comment( array( 'comment_post_ID' => 1, 'comment_content' => "comment $i $home/c 🙂", 'comment_author' => "Ünï $i", 'comment_approved' => 1 ) ); }
		foreach ( array( 'editor', 'author', 'subscriber' ) as $i => $role ) {
			if ( ! username_exists( "fx_$role" ) ) { wp_insert_user( array( 'user_login' => "fx_$role", 'user_pass' => "pw-$role-é", 'user_email' => "fx_$role@example.test", 'role' => $role, 'display_name' => "Fx Ünï $role" ) ); }
		}
		$menu = wp_create_nav_menu( 'Fixture Menu' );
		wp_update_nav_menu_item( $menu, 0, array( 'menu-item-title' => 'About', 'menu-item-url' => "$home/about/", 'menu-item-status' => 'publish', 'menu-item-type' => 'custom' ) );
		update_option( 'widget_text', array( 2 => array( 'title' => 'W', 'text' => "<a href=\"$home/w\">w</a>" ), '_multiwidget' => 1 ) );
		update_option( 'shm_fixture_nested', array( 'a' => serialize( array( 'deep' => "$home/deep" ) ), 'url' => $home ), false );
		update_option( 'shm_fixture_bigopt', str_repeat( "$home/pad-", 300000 ), false );   // ~3MB
		// serialized object of a class that will NOT exist on the target + protected props
		$wpdb->replace( $wpdb->options, array( 'option_name' => 'shm_fixture_object', 'option_value' => 'O:11:"MissingPlug":2:{s:3:"url";s:' . strlen( "$home/o" ) . ':"' . "$home/o" . '";s:7:"' . "\0*\0" . 'tok";s:' . strlen( "$home/t" ) . ':"' . "$home/t" . '";}', 'autoload' => 'no' ) );
		// non-WP table with binary + 4-byte chars + big text
		$t = $wpdb->prefix . 'shm_custom';
		$wpdb->query( "DROP TABLE IF EXISTS `$t`" );
		$wpdb->query( "CREATE TABLE `$t` (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, b LONGBLOB, t LONGTEXT, d DATETIME NULL, f DOUBLE NULL, KEY k (d)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
		for ( $i = 0; $i < 50; $i++ ) {
			$wpdb->insert( $t, array( 'b' => random_bytes( 3000 + $i ), 't' => "row $i 🙂 日本 $home/c/$i " . str_repeat( 'x', $i * 100 ), 'd' => $i % 7 ? '2026-11-03 10:00:00' : null, 'f' => $i / 7 ) );
		}
		out( array( 'posts' => (int) wp_count_posts()->publish, 'files' => count( glob( $up['path'] . '/*' ) ) ) );

	default:
		fwrite( STDERR, "unknown command: $cmd\n" );
		exit( 2 );
}
