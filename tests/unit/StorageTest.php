<?php
use PHPUnit\Framework\TestCase;

/**
 * Found by exporting the user's real XAMPP site: Apache (user "daemon") could not write
 * <plugin dir>/storage, because the plugin had been copied there by another user. Working files
 * belong next to the backups in wp-content, which WordPress itself must be able to write.
 */
final class StorageTest extends TestCase {

	public function test_working_storage_is_in_wp_content_not_inside_the_plugin_folder(): void {
		$this->assertSame( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'shm-storage', SHM_STORAGE_PATH );
		$this->assertStringStartsNotWith( SHM_PATH, SHM_STORAGE_PATH );
	}

	public function test_storage_and_backups_folders_are_never_part_of_an_export(): void {
		$filters = shm_content_filters();
		$this->assertContains( 'shm-storage', $filters );
		$this->assertContains( 'shm-backups', $filters );
	}

	public function test_deny_htaccess_blocks_direct_web_access_on_apache_2_2_and_2_4(): void {
		$dir = SHM_TEST_TMP . '/deny-' . bin2hex( random_bytes( 3 ) );
		mkdir( $dir );
		$this->assertTrue( Shm_File_Htaccess::deny( $dir . '/.htaccess' ) );
		$rules = file_get_contents( $dir . '/.htaccess' );
		$this->assertStringContainsString( 'Require all denied', $rules );
		$this->assertStringContainsString( 'Deny from all', $rules );
	}
}
