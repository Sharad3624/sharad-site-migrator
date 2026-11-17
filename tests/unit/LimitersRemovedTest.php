<?php
use PHPUnit\Framework\TestCase;

/**
 * Specification: every artificial limit of the upstream free plugin is gone.
 * (Upstream gates imports in the browser at wp_max_upload_size(), stubs Restore,
 * and ships ~700 lines of paid-extension scaffolding + telemetry.)
 */
final class LimitersRemovedTest extends TestCase {

	private static function files( string $ext ): array {
		$out = array();
		$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( SHM_TEST_PLUGIN, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $f ) {
			if ( $f->isFile() && substr( $f->getFilename(), -strlen( $ext ) ) === $ext ) {
				$out[] = $f->getPathname();
			}
		}
		return $out;
	}

	private static function source(): string {
		$all = '';
		foreach ( array_merge( self::files( '.php' ), self::files( '.js' ) ) as $f ) {
			$all .= "\n/* FILE $f */\n" . file_get_contents( $f );
		}
		return $all;
	}

	public function test_no_512mb_cap_constant(): void {
		$this->assertFalse( defined( 'SHM_MAX_FILE_SIZE' ), 'SHM_MAX_FILE_SIZE must not exist' );
	}

	public function test_no_upsell_or_paid_extension_text_anywhere(): void {
		$src = self::source();
		foreach ( array( 'unlimited-extension', 'Get unlimited', 'paid extension', 'Unlimited Extension', '512MB', 'import.wp-migration.com' ) as $needle ) {
			$this->assertStringNotContainsString( $needle, $src, "upsell text '$needle' still present" );
		}
	}

	public function test_browser_import_does_not_gate_on_upload_max_filesize(): void {
		$js = file_get_contents( SHM_TEST_PLUGIN . '/lib/view/assets/javascript/import.min.js' );
		$this->assertStringNotContainsString( 'max_file_size', $js );
		$this->assertStringNotContainsString( "type: 'pro'", $js );
	}

	public function test_restore_is_not_a_stub(): void {
		$js = file_get_contents( SHM_TEST_PLUGIN . '/lib/view/assets/javascript/backups.min.js' );
		$this->assertStringNotContainsString( 'restore_from_file', $js );
		$this->assertStringNotContainsString( "type: 'pro'", $js );
		$this->assertStringContainsString( 'shm_manual_restore', $js, 'Restore must start an import of the stored backup' );
	}

	public function test_no_telemetry_or_phone_home(): void {
		$src = self::source();
		$this->assertStringNotContainsString( 'servmask.com/shm/', $src );
		$this->assertStringNotContainsString( 'SHM_FEEDBACK_URL', $src );
		$this->assertStringNotContainsString( 'SHM_REPORT_URL', $src );
		$this->assertStringNotContainsString( 'plugins_api', $src, 'extension updater removed' );
		$this->assertFileDoesNotExist( SHM_TEST_PLUGIN . '/lib/model/class-shm-updater.php' );
		$this->assertFileDoesNotExist( SHM_TEST_PLUGIN . '/lib/model/class-shm-extensions.php' );
		$this->assertFileDoesNotExist( SHM_TEST_PLUGIN . '/lib/model/class-shm-feedback.php' );
		$this->assertFileDoesNotExist( SHM_TEST_PLUGIN . '/lib/model/class-shm-report.php' );
	}

	public function test_only_working_buttons_remain(): void {
		foreach ( array( 'dropbox', 'gdrive', 's3', 'b2', 'onedrive', 'box', 'mega', 'ftp', 'url', 'webdav', 'azure-storage', 'glacier', 'pcloud', 'digitalocean', 'gcloud-storage', 's3-client' ) as $b ) {
			$this->assertFileDoesNotExist( SHM_TEST_PLUGIN . "/lib/view/import/button-$b.php", "dead import button $b" );
			$this->assertFileDoesNotExist( SHM_TEST_PLUGIN . "/lib/view/export/button-$b.php", "dead export button $b" );
		}
		$this->assertFileExists( SHM_TEST_PLUGIN . '/lib/view/import/button-file.php' );
		$this->assertFileExists( SHM_TEST_PLUGIN . '/lib/view/export/button-file.php' );
	}

	public function test_no_extension_scaffolding_in_constants(): void {
		$c = file_get_contents( SHM_TEST_PLUGIN . '/constants.php' );
		$this->assertStringNotContainsString( '_PLUGIN_ABOUT', $c );
		$this->assertLessThan( 450, substr_count( $c, "\n" ), 'constants.php should lose its ~700 lines of extension descriptors' );
	}

	public function test_fork_identity(): void {
		$main = file_get_contents( SHM_TEST_PLUGIN . '/sharad-site-migrator.php' );
		$this->assertMatchesRegularExpression( '/Plugin Name:\s*Sharad Site Migrator/', $main );
		$this->assertMatchesRegularExpression( '/Author:\s*Sharad\b/', $main );
		$this->assertStringContainsString( 'ServMask', $main, 'upstream copyright notice must be preserved (GPL)' );
		$this->assertFileExists( SHM_TEST_PLUGIN . '/LICENSE' );
	}
}
