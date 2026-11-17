<?php
use PHPUnit\Framework\TestCase;

require_once SHM_TEST_PLUGIN . '/lib/model/import/class-shm-import-validate.php';

final class UploadIntegrityTest extends TestCase {

	/** @dataProvider invalidChunks */
	public function test_incomplete_or_malformed_chunk_is_rejected_before_writing( $declared, int $code ): void {
		$params  = array( 'storage' => 'integrity-' . bin2hex( random_bytes( 4 ) ), 'archive' => 'site.wpress', 'chunk_offset' => 4, 'chunk_total' => 12, 'chunk_size' => $declared );
		$archive = shm_archive_path( $params );
		file_put_contents( $archive, 'AAAA' );
		$tmp = tempnam( SHM_TEST_TMP, 'chunk' );
		file_put_contents( $tmp, 'BB' );
		$files = $_FILES;
		$_FILES = array( 'upload-file' => array( 'error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp ) );
		try {
			Shm_Import_Upload::execute( $params );
			$this->fail( 'The short chunk must not be acknowledged.' );
		} catch ( Shm_Import_Retry_Exception $e ) {
			$this->assertSame( $code, $e->getCode() );
			$this->assertSame( 'AAAA', file_get_contents( $archive ) );
		} finally {
			$_FILES = $files;
		}
	}

	public static function invalidChunks(): array {
		return array(
			'short chunk' => array( '4', 413 ),
			'empty declared chunk' => array( '0', 413 ),
			'negative size' => array( '-1', 400 ),
			'array size' => array( array( 2 ), 400 ),
			'non-numeric size' => array( 'oops', 400 ),
		);
	}

	public function test_total_upload_size_is_checked_before_extraction(): void {
		$params = array( 'storage' => 'total-' . bin2hex( random_bytes( 4 ) ), 'archive' => 'site.wpress', 'upload_size' => '10000' );
		file_put_contents( shm_archive_path( $params ), str_repeat( "\0", 4377 ) );
		$this->expectException( Shm_Import_Exception::class );
		$this->expectExceptionMessage( 'The archive upload is incomplete.' );
		Shm_Import_Validate::execute( $params );
	}

	public function test_missing_archive_is_reported_as_incomplete(): void {
		$params = array( 'storage' => 'missing-' . bin2hex( random_bytes( 4 ) ), 'archive' => 'site.wpress', 'upload_size' => '10000' );
		$this->expectException( Shm_Import_Exception::class );
		$this->expectExceptionMessage( 'The archive upload is incomplete.' );
		Shm_Import_Validate::execute( $params );
	}

	public function test_invalid_total_size_is_rejected(): void {
		$this->expectException( Shm_Import_Exception::class );
		$this->expectExceptionMessage( 'Invalid upload size.' );
		Shm_Import_Validate::execute( array( 'upload_size' => array( 10000 ) ) );
	}
}
