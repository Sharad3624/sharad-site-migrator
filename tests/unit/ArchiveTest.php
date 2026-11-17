<?php
use PHPUnit\Framework\TestCase;

/** .wpress container: round-trip fidelity + hostile-archive handling. */
final class ArchiveTest extends TestCase {

	private string $work;

	protected function setUp(): void {
		$this->work = SHM_TEST_TMP . '/ar-' . bin2hex( random_bytes( 4 ) );
		mkdir( $this->work . '/src', 0777, true );
		mkdir( $this->work . '/out', 0777, true );
	}

	/** @param array<string,string> $files archive path => bytes */
	private function build( array $files ): string {
		$archive = $this->work . '/t.wpress';
		$c       = new Shm_Compressor( $archive );
		$i       = 0;
		foreach ( $files as $path => $bytes ) {
			$src = $this->work . '/src/f' . ( $i++ );
			file_put_contents( $src, $bytes );
			$c->add_file( $src, $path );
		}
		$c->close( true );
		return $archive;
	}

	private function extractAll( string $archive ): void {
		$x = new Shm_Extractor( $archive );
		$this->assertTrue( $x->is_valid(), 'archive must carry a valid EOF block' );
		while ( $x->has_not_reached_eof() ) {
			$x->extract_one_file_to( $this->work . '/out' );
		}
		$x->close();
	}

	public function test_round_trip_is_byte_exact_for_awkward_files(): void {
		$files = array(
			'empty.txt'                              => '',
			'one.bin'                                => "\0",
			'wp-content/uploads/2026/11/photo.jpg'   => random_bytes( 300001 ),
			'wp-content/uploads/boundary-512000.bin' => random_bytes( 512000 ),
			'wp-content/uploads/boundary-512001.bin' => random_bytes( 512001 ),
			'wp-content/uploads/café-日本-🙂.txt'     => 'unicode name',
			'wp-content/themes/my theme/with space.css' => 'a { b: c }',
			'big.bin'                                => random_bytes( 5 * 1024 * 1024 + 7 ),
		);
		$this->extractAll( $this->build( $files ) );
		foreach ( $files as $path => $bytes ) {
			$got = $this->work . '/out/' . $path;
			$this->assertFileExists( $got, $path );
			$this->assertSame( strlen( $bytes ), filesize( $got ), "size of $path" );
			$this->assertSame( hash( 'sha256', $bytes ), hash_file( 'sha256', $got ), "hash of $path" );
		}
	}

	public function test_255_byte_filename_survives(): void {
		$name = str_repeat( 'n', 251 ) . '.txt'; // 255 bytes: the max on ext4/APFS
		$this->extractAll( $this->build( array( "wp-content/uploads/$name" => 'long' ) ) );
		$this->assertSame( 'long', file_get_contents( $this->work . "/out/wp-content/uploads/$name" ) );
	}

	/** @dataProvider traversalPaths */
	public function test_path_traversal_entries_are_refused_and_nothing_escapes( string $evil ): void {
		$archive = $this->build( array( 'ok.txt' => 'fine', $evil => 'pwned' ) );
		try {
			$this->extractAll( $archive );
			$this->fail( 'extractor must refuse a path-traversal entry' );
		} catch ( Shm_Archive_Exception $e ) {
			$this->assertStringContainsString( 'path', strtolower( $e->getMessage() ) );
		}
		$this->assertFileDoesNotExist( $this->work . '/escape.txt' );
		$this->assertFileDoesNotExist( dirname( $this->work ) . '/escape.txt' );
	}

	public static function traversalPaths(): array {
		return array(
			'parent'        => array( '../escape.txt' ),
			'deep parent'   => array( 'wp-content/../../escape.txt' ),
			'backslash'     => array( '..\\escape.txt' ),
			'mid-path'      => array( 'a/b/../../../escape.txt' ),
		);
	}

	public function test_dotdot_inside_a_filename_is_not_a_traversal(): void {
		$this->extractAll( $this->build( array( 'wp-content/uploads/my..file..v2.txt' => 'ok' ) ) );
		$this->assertSame( 'ok', file_get_contents( $this->work . '/out/wp-content/uploads/my..file..v2.txt' ) );
	}

	public function test_an_existing_read_only_file_is_replaced_not_silently_kept(): void {
		// A previous import (or a host) left a 0444 file; the new archive carries newer content for it
		$dest = $this->work . '/out/wp-content/uploads';
		mkdir( $dest, 0777, true );
		file_put_contents( $dest . '/locked.txt', 'OLD CONTENT' );
		chmod( $dest . '/locked.txt', 0444 );
		$this->extractAll( $this->build( array( 'wp-content/uploads/locked.txt' => 'NEW CONTENT', 'wp-content/uploads/after.txt' => 'still extracted' ) ) );
		$this->assertSame( 'NEW CONTENT', file_get_contents( $dest . '/locked.txt' ), 'stale read-only file must not survive an import' );
		$this->assertSame( 'still extracted', file_get_contents( $dest . '/after.txt' ), 'and the archive must keep being read in sync' );
	}

	public function test_truncated_archive_is_detected_as_invalid(): void {
		$archive = $this->build( array( 'a.bin' => random_bytes( 100000 ) ) );
		$fh      = fopen( $archive, 'r+b' );
		ftruncate( $fh, filesize( $archive ) - 4377 ); // drop the EOF block
		fclose( $fh );
		$this->assertFalse( ( new Shm_Extractor( $archive ) )->is_valid() );
	}
}
