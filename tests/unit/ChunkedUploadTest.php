<?php
use PHPUnit\Framework\TestCase;

/**
 * Chunked, resumable upload: what makes "any size" real. The free plugin POSTs the
 * whole file in one request, so PHP's post_max_size is the real ceiling.
 */
final class ChunkedUploadTest extends TestCase {

	private string $dir;

	protected function setUp(): void {
		$this->dir = SHM_TEST_TMP . '/up-' . bin2hex( random_bytes( 4 ) );
		mkdir( $this->dir, 0777, true );
	}

	private function chunkFile( string $bytes ): string {
		$p = $this->dir . '/tmp-' . bin2hex( random_bytes( 3 ) );
		file_put_contents( $p, $bytes );
		return $p;
	}

	public function test_sequential_chunks_reassemble_exactly(): void {
		$payload = random_bytes( 1_000_003 );
		$dest    = $this->dir . '/a.wpress';
		$offset  = 0;
		foreach ( str_split( $payload, 262144 ) as $piece ) {
			$size   = Shm_Import_Upload::append_chunk( $dest, $this->chunkFile( $piece ), $offset, strlen( $payload ) );
			$offset += strlen( $piece );
			$this->assertSame( $offset, $size );
		}
		$this->assertSame( hash( 'sha256', $payload ), hash_file( 'sha256', $dest ) );
	}

	public function test_first_chunk_truncates_a_stale_partial_file(): void {
		$dest = $this->dir . '/b.wpress';
		file_put_contents( $dest, str_repeat( 'x', 5000 ) );
		$size = Shm_Import_Upload::append_chunk( $dest, $this->chunkFile( 'fresh' ), 0, 5 );
		$this->assertSame( 5, $size );
		$this->assertSame( 'fresh', file_get_contents( $dest ) );
	}

	public function test_retry_of_an_already_written_chunk_is_idempotent(): void {
		$dest = $this->dir . '/c.wpress';
		Shm_Import_Upload::append_chunk( $dest, $this->chunkFile( 'AAAA' ), 0, 8 );
		Shm_Import_Upload::append_chunk( $dest, $this->chunkFile( 'BBBB' ), 4, 8 );
		// response for the second chunk was lost; the browser re-sends it
		$size = Shm_Import_Upload::append_chunk( $dest, $this->chunkFile( 'BBBB' ), 4, 8 );
		$this->assertSame( 8, $size );
		$this->assertSame( 'AAAABBBB', file_get_contents( $dest ) );
	}

	public function test_gap_is_rejected_with_409_and_file_untouched(): void {
		$dest = $this->dir . '/d.wpress';
		Shm_Import_Upload::append_chunk( $dest, $this->chunkFile( 'AAAA' ), 0, 12 );
		try {
			Shm_Import_Upload::append_chunk( $dest, $this->chunkFile( 'CCCC' ), 8, 12 );
			$this->fail( 'expected out-of-sequence exception' );
		} catch ( Shm_Import_Retry_Exception $e ) {
			$this->assertSame( 409, $e->getCode() );
		}
		$this->assertSame( 'AAAA', file_get_contents( $dest ) );
	}

	public function test_chunk_beyond_declared_total_is_rejected(): void {
		$this->expectException( Shm_Import_Retry_Exception::class );
		Shm_Import_Upload::append_chunk( $this->dir . '/e.wpress', $this->chunkFile( 'toolong' ), 0, 3 );
	}

	public function test_negative_offset_is_rejected(): void {
		$this->expectException( Shm_Import_Retry_Exception::class );
		Shm_Import_Upload::append_chunk( $this->dir . '/f.wpress', $this->chunkFile( 'x' ), -1, 10 );
	}

	public function test_zero_byte_final_file_is_allowed(): void {
		$size = Shm_Import_Upload::append_chunk( $this->dir . '/g.wpress', $this->chunkFile( '' ), 0, 0 );
		$this->assertSame( 0, $size );
	}

	/** @dataProvider chunkSizes */
	public function test_chunk_size_respects_server_limits( $post, $upload, int $expected ): void {
		$this->assertSame( $expected, Shm_Import_Controller::chunk_size( $post, $upload ) );
	}

	public static function chunkSizes(): array {
		return array(
			'roomy server -> 5MB cap'         => array( '512M', '512M', 5 * 1024 * 1024 ),
			'8M/2M shared host -> 90% of 2M'  => array( '8M', '2M', (int) floor( 2 * 1024 * 1024 * 0.9 ) ),
			'post_max_size 0 means unlimited' => array( '0', '0', 5 * 1024 * 1024 ),
			'100K limit -> 90% of 100K'       => array( '100K', '100K', (int) floor( 102400 * 0.9 ) ),
			'absurd 1K limit -> 64K floor'    => array( '1K', '1K', 65536 ),
			'garbage ini -> safe default'     => array( 'lots', '', 5 * 1024 * 1024 ),
		);
	}
}
