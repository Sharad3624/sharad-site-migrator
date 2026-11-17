<?php
use PHPUnit\Framework\TestCase;

require_once SHM_TEST_PLUGIN . '/lib/model/class-shm-compatibility.php';
require_once SHM_TEST_PLUGIN . '/lib/model/import/class-shm-import-confirm.php';

final class PhpCompatibilityTest extends TestCase {

	/** @dataProvider incompatibleVersions */
	public function test_different_php_branches_are_rejected( string $source, string $target ): void {
		$this->expectException( Shm_Compatibility_Exception::class );
		$this->expectExceptionMessage( "Backup PHP: $source. Destination PHP: $target." );
		Shm_Compatibility::check_php_version( $source, $target );
	}

	public static function incompatibleVersions(): array {
		return array(
			'8.2 to 8.0 regression' => array( '8.2.24', '8.0.30' ),
			'upgrade' => array( '8.0.30', '8.2.24' ),
			'PHP 7 to 8' => array( '7.4.33', '8.2.24' ),
			'PHP 5 to 8' => array( '5.6.40', '8.2.24' ),
		);
	}

	public function test_same_branch_allows_patch_and_host_suffix_differences(): void {
		foreach ( array( '8.2', '8.2.4', '8.2.30', '8.2.24-1ubuntu1' ) as $source ) {
			Shm_Compatibility::check_php_version( $source, '8.2.24' );
		}
		$this->addToAssertionCount( 1 );
	}

	/** @dataProvider unknownVersions */
	public function test_unknown_php_version_is_rejected( $source ): void {
		$this->expectException( Shm_Compatibility_Exception::class );
		$this->expectExceptionMessage( 'does not contain a valid PHP version' );
		Shm_Compatibility::check_php_version( $source, PHP_VERSION );
	}

	public static function unknownVersions(): array {
		return array( array( null ), array( '' ), array( array( '8.2' ) ), array( 8.2 ), array( 'unknown' ), array( '8.2<script>' ) );
	}

	public function test_confirmation_checks_the_package_before_proceeding(): void {
		$params = array( 'storage' => 'php-compat-' . bin2hex( random_bytes( 4 ) ) );
		file_put_contents( shm_package_path( $params ), json_encode( array( 'PHP' => array( 'Version' => '5.6.40' ) ) ) );
		$this->expectException( Shm_Compatibility_Exception::class );
		Shm_Import_Confirm::execute( $params );
	}
}
