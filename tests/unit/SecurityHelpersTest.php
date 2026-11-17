<?php
use PHPUnit\Framework\TestCase;

/** Hardening carried over from the analysis of upstream v7.5 (see findings 1, 2). */
final class SecurityHelpersTest extends TestCase {

	protected function setUp(): void {
		update_option( SHM_SECRET_KEY, 'k3y-abcdefghijklmnopqrstuvwxyz012345' );
	}

	public function test_correct_secret_key_passes(): void {
		$this->assertTrue( shm_verify_secret_key( 'k3y-abcdefghijklmnopqrstuvwxyz012345' ) );
	}

	/** @dataProvider badKeys */
	public function test_wrong_missing_or_odd_keys_are_rejected( $key ): void {
		$this->expectException( Shm_Not_Valid_Secret_Key_Exception::class );
		shm_verify_secret_key( $key );
	}

	public static function badKeys(): array {
		return array( 'wrong' => array( 'nope' ), 'empty' => array( '' ), 'null (param missing)' => array( null ), 'prefix' => array( 'k3y-abc' ), 'array' => array( array( 'x' ) ), 'int' => array( 0 ) );
	}

	public function test_unset_stored_key_never_matches_an_empty_submission(): void {
		delete_option( SHM_SECRET_KEY );
		$this->expectException( Shm_Not_Valid_Secret_Key_Exception::class );
		shm_verify_secret_key( '' );
	}

	public function test_generated_secret_key_is_long_and_alphanumeric(): void {
		$k = shm_generate_secret_key();
		$this->assertSame( 32, strlen( $k ) );
		$this->assertMatchesRegularExpression( '/^[A-Za-z0-9]{32}$/', $k );
		$this->assertNotSame( $k, shm_generate_secret_key() );
	}

	public function test_archive_names_are_unguessable(): void {
		$seen = array();
		for ( $i = 0; $i < 300; $i++ ) {
			$n = shm_archive_file();
			$this->assertMatchesRegularExpression( '/^localhost-wordpress-\d{8}-\d{6}-[a-f0-9]{16}\.wpress$/', $n );
			$seen[ $n ] = true;
		}
		$this->assertCount( 300, $seen, 'names must not collide' );
	}

	public function test_job_folders_never_collide_even_when_requests_start_in_the_same_microsecond(): void {
		$seen = array();
		for ( $i = 0; $i < 2000; $i++ ) {
			$f = shm_storage_folder();
			$this->assertMatchesRegularExpression( '/^[a-f0-9]{16}$/', $f );
			$seen[ $f ] = true;
		}
		$this->assertCount( 2000, $seen, 'uniqid() repeats within one microsecond; two parallel exports then share (and delete) one folder' );
	}
}
