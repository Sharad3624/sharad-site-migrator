<?php
use PHPUnit\Framework\TestCase;

/**
 * Found by the live->demo E2E: rows whose string ended in a backslash (…\\') vanished,
 * because the URL-rewriting regex mistook the closing quote for an escaped one,
 * and the failed INSERT was never reported.
 */
final class SqlStringScannerTest extends TestCase {

	private static function literals( string $sql ): array {
		$seen = array();
		Shm_Database_Utility::map_sql_strings( $sql, function ( $m ) use ( &$seen ) { $seen[] = $m[1]; return $m[0]; } );
		return $seen;
	}

	public function test_finds_each_literal_exactly(): void {
		$this->assertSame( array( 'a', 'b c' ), self::literals( "INSERT INTO t VALUES (1,'a','b c',NULL,3.5)" ) );
	}

	public function test_string_ending_in_an_escaped_backslash_closes_at_the_real_quote(): void {
		// SQL text:  ...,'abc\\','next' ...   (value ends with ONE backslash byte)
		$sql = "INSERT INTO t VALUES (25,'abc\\\\','next');";
		$this->assertSame( array( 'abc\\\\', 'next' ), self::literals( $sql ) );
	}

	public function test_escaped_quote_does_not_close_the_literal(): void {
		$this->assertSame( array( "it\\'s", 'x' ), self::literals( "VALUES ('it\\'s','x')" ) );
	}

	public function test_backslash_then_escaped_quote(): void {
		// bytes: \ ' -> escaped as  \\ \'   (three backslash-ish chars before the final quote)
		$this->assertSame( array( "a\\\\\\'b" ), self::literals( "VALUES ('a\\\\\\'b')" ) );
	}

	public function test_doubled_quote_form(): void {
		$this->assertSame( array( "it''s" ), self::literals( "VALUES ('it''s')" ) );
	}

	public function test_unterminated_quote_is_left_alone_not_destroyed(): void {
		$sql = "VALUES ('never closed";
		$this->assertSame( $sql, Shm_Database_Utility::map_sql_strings( $sql, function ( $m ) { return "'X'"; } ) );
	}

	public function test_three_megabyte_literal_does_not_hit_pcre_limits(): void {
		$big = str_repeat( 'x\\\\y\\\'z ', 400000 ); // ~3.2 MB of escaped content
		$out = Shm_Database_Utility::map_sql_strings( "VALUES ('$big')", function ( $m ) { return $m[0]; } );
		$this->assertSame( "VALUES ('$big')", $out );
	}

	/** The property that was violated: escape -> scan -> unescape is the identity for ANY bytes. */
	public function test_roundtrip_is_lossless_for_random_binary_and_nasty_endings(): void {
		mt_srand( 4242 );
		$alphabet = array( '\\', "'", '"', "\0", "\n", "\r", "\x1a", ';', ' ', 'a', 'é', "\xff" );
		for ( $i = 0; $i < 400; $i++ ) {
			$raw = '';
			$len = mt_rand( 0, 60 );
			for ( $j = 0; $j < $len; $j++ ) { $raw .= $alphabet[ mt_rand( 0, count( $alphabet ) - 1 ) ]; }
			if ( $i % 3 === 0 ) { $raw .= '\\'; }                // ends in a backslash byte: the failing case
			if ( $i % 5 === 0 ) { $raw .= str_repeat( '\\', mt_rand( 1, 4 ) ); }
			$n     = mt_rand( 0, 40 );
			$other = $n ? random_bytes( $n ) : '';
			$sql   = "INSERT INTO t VALUES ($i,'" . Shm_Database_Utility::escape_mysql( $raw ) . "','" . Shm_Database_Utility::escape_mysql( $other ) . "',NULL);";
			$got   = array();
			$out   = Shm_Database_Utility::map_sql_strings( $sql, function ( $m ) use ( &$got ) {
				$v = Shm_Database_Utility::unescape_mysql( $m[1] );
				$got[] = $v;
				return "'" . Shm_Database_Utility::escape_mysql( $v ) . "'";
			} );
			$this->assertSame( array( $raw, $other ), $got, "case $i: literal boundaries" );
			$this->assertSame( $sql, $out, "case $i: statement rewritten identically" );
		}
	}
}
