<?php
use PHPUnit\Framework\TestCase;

/** Stand-in for $wpdb: only what Shm_Database's constructor and helpers touch. */
class ShmFakeWpdb {
	public $dbh = null; public $dbhost = 'localhost'; public $dbname = 'x'; public $prefix = 'wp_'; public $base_prefix = 'wp_';
	public $caps = array( 'utf8mb4' => true, 'utf8mb4_520' => true );
	public function has_cap( $c ) { return ! empty( $this->caps[ $c ] ); }
}

/** A database whose answers are scripted, so import-time safety logic runs without a server. */
class ShmFakeDatabase extends Shm_Database {
	public $log = array();                 // every statement "executed"
	public $collations = array( 'utf8mb4_general_ci', 'utf8mb4_unicode_ci', 'utf8mb4_unicode_520_ci', 'utf8_general_ci', 'latin1_swedish_ci' );
	public $max_packet = 1048576;
	public $fail_on = null;                // substring of a statement that should fail with errno 1273
	private $errno = 0;

	public function query( $input ) {
		$this->log[] = $input;
		$this->errno = ( $this->fail_on !== null && strpos( $input, $this->fail_on ) !== false ) ? 1273 : 0;
		if ( stripos( $input, 'SHOW COLLATION' ) === 0 ) { return new ArrayIterator( array_map( function ( $c ) { return array( 'Collation' => $c ); }, $this->collations ) ); }
		return true;
	}
	public function escape( $input ) { return addslashes( $input ); }
	public function errno() { return $this->errno; }
	public function error() { return $this->errno ? 'Unknown collation' : ''; }
	public function version() { return '10.4.28-MariaDB'; }
	public function fetch_assoc( $result ) { if ( $result instanceof ArrayIterator && $result->valid() ) { $v = $result->current(); $result->next(); return $v; } return false; }
	public function fetch_row( $result ) { return false; }
	public function num_rows( $result ) { return 0; }
	public function free_result( $result ) {}
	protected function get_max_allowed_packet() { return $this->max_packet; }
	public function collate( $sql ) { return $this->replace_table_collations( $sql ); }
	public function preflight_file( $file ) { return $this->preflight( $file ); }
}

/**
 * Found by importing a MySQL 8 dump into MariaDB: the importer only knew one collation fallback, rewrote
 * row data as well as DDL, and (because of a version bug) dropped every table before discovering the
 * first CREATE TABLE could not run.
 */
final class DatabaseCompatTest extends TestCase {

	private function db(): ShmFakeDatabase { return new ShmFakeDatabase( new ShmFakeWpdb() ); }

	public function test_archive_format_version_is_not_the_plugin_version(): void {
		$this->assertTrue( defined( 'SHM_FORMAT_VERSION' ) );
		$this->assertFalse( version_compare( SHM_FORMAT_VERSION, '4.10', '<' ), 'must never look like a pre-4.10 archive (that triggers a full DROP of every table)' );
		$src = file_get_contents( SHM_TEST_PLUGIN . '/lib/model/export/class-shm-export-config.php' );
		$this->assertStringContainsString( "array( 'Version' => SHM_FORMAT_VERSION )", $src );
	}

	/** @dataProvider foreignCollations */
	public function test_unsupported_collations_are_mapped_to_the_best_supported_one( string $given, string $expected ): void {
		$sql = "CREATE TABLE `SERVMASK_PREFIX_t` (\n  `a` varchar(10) COLLATE $given NOT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$given;";
		$out = $this->db()->collate( $sql );
		$this->assertSame( 2, substr_count( $out, $expected ), $out );
		$this->assertStringNotContainsString( $given, $out );        // the unsupported name is gone
	}

	public static function foreignCollations(): array {
		return array(
			'MySQL 8 default'            => array( 'utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci' ),
			'MySQL 8 accent sensitive'   => array( 'utf8mb4_0900_as_cs', 'utf8mb4_unicode_520_ci' ),
			'MariaDB 11 uca1400'         => array( 'utf8mb4_uca1400_ai_ci', 'utf8mb4_unicode_520_ci' ),
			'MySQL 8.0.30+ utf8mb3'      => array( 'utf8mb3_general_ci', 'utf8_general_ci' ),
		);
	}

	public function test_supported_collations_and_row_data_are_never_touched(): void {
		$db  = $this->db();
		$ddl = "CREATE TABLE `t` (`a` text COLLATE utf8mb4_unicode_ci) COLLATE=utf8mb4_unicode_520_ci;";
		$this->assertSame( $ddl, $db->collate( $ddl ) );
		$row = "INSERT INTO `t` VALUES ('a blog post about utf8mb4_0900_ai_ci and COLLATE utf8mb4_0900_ai_ci');";
		$this->assertSame( $row, $db->collate( $row ), 'text inside a row must never be rewritten' );
	}

	public function test_old_mysql_without_utf8mb4_falls_back_to_utf8(): void {
		$db = $this->db();
		$db->collations = array( 'utf8_general_ci', 'utf8_unicode_ci', 'utf8_unicode_520_ci' );
		$out = $db->collate( 'CREATE TABLE `t` (`a` text COLLATE utf8mb4_unicode_520_ci) DEFAULT CHARSET=utf8mb4;' );
		$this->assertStringContainsString( 'utf8_unicode_520_ci', $out );
		$this->assertStringContainsString( 'CHARSET=utf8;', $out );
	}

	private function dump( string $body ): string {
		$f = SHM_TEST_TMP . '/dump-' . bin2hex( random_bytes( 3 ) ) . '.sql';
		file_put_contents( $f, $body );
		return $f;
	}

	public function test_preflight_rejects_a_row_bigger_than_max_allowed_packet_before_any_change(): void {
		$db = $this->db();
		$db->max_packet = 2000;
		$f = $this->dump( "DROP TABLE IF EXISTS `SERVMASK_PREFIX_t`;\nCREATE TABLE `SERVMASK_PREFIX_t` (`a` text);\nINSERT INTO `SERVMASK_PREFIX_t` VALUES ('" . str_repeat( 'x', 5000 ) . "');\n" );
		try {
			$db->preflight_file( $f );
			$this->fail( 'oversize row must be refused' );
		} catch ( Shm_Database_Exception $e ) {
			$this->assertStringContainsString( 'max_allowed_packet', $e->getMessage() );
		}
		$this->assertSame( array(), array_filter( $db->log, function ( $q ) { return stripos( $q, 'DROP' ) === 0 && strpos( $q, 'shmprobe_' ) === false; } ), 'no real table may be dropped' );
	}

	public function test_preflight_creates_each_table_under_a_throwaway_name_and_removes_it(): void {
		$db = $this->db();
		$f  = $this->dump( "DROP TABLE IF EXISTS `SERVMASK_PREFIX_a`;\nCREATE TABLE `SERVMASK_PREFIX_a` (\n  `id` int\n) ENGINE=InnoDB;\nINSERT INTO `SERVMASK_PREFIX_a` VALUES (1);\n" );
		$db->preflight_file( $f );
		$create = array_values( array_filter( $db->log, function ( $q ) { return stripos( $q, 'CREATE TABLE' ) === 0; } ) );
		$this->assertCount( 1, $create );
		$this->assertStringContainsString( 'shmprobe_', $create[0] );
		$this->assertStringNotContainsString( 'SERVMASK_PREFIX_', $create[0] );
		$this->assertContains( 'DROP TABLE IF EXISTS `shmprobe_a`', $db->log );
		$this->assertSame( array(), array_filter( $db->log, function ( $q ) { return stripos( $q, 'INSERT' ) === 0 || strpos( $q, '`wp_' ) !== false; } ), 'probing must not touch real tables or data' );
	}

	public function test_preflight_fails_before_dropping_when_a_create_table_cannot_run(): void {
		$db = $this->db();
		$db->fail_on = 'weird_engine';
		$f  = $this->dump( "DROP TABLE IF EXISTS `SERVMASK_PREFIX_ok`;\nCREATE TABLE `SERVMASK_PREFIX_ok` (`id` int);\nDROP TABLE IF EXISTS `SERVMASK_PREFIX_bad`;\nCREATE TABLE `SERVMASK_PREFIX_bad` (`id` int) ENGINE=weird_engine;\n" );
		try {
			$db->preflight_file( $f );
			$this->fail( 'incompatible table must be reported' );
		} catch ( Shm_Database_Exception $e ) {
			$this->assertStringContainsString( 'bad', $e->getMessage() );
			$this->assertStringContainsString( 'nothing has been changed', strtolower( $e->getMessage() ) );
		}
		$this->assertSame( array(), array_filter( $db->log, function ( $q ) { return preg_match( '/^DROP TABLE IF EXISTS `SERVMASK|^DROP TABLE IF EXISTS `wp_/', $q ); } ) );
	}
}
