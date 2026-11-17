<?php
use PHPUnit\Framework\TestCase;

class ShmFixtureWidget {
	public $title = 'pub http://old.example';
	protected $link = 'http://old.example/protected';
	private $secret = 'http://old.example/private';
	public function dump(): array { return array( $this->title, $this->link, $this->secret ); }
}

/**
 * Search-replace is what makes live<->local<->demo moves work: URLs inside
 * PHP-serialized options/postmeta must be rewritten AND their s:N: lengths fixed.
 */
final class ReplaceValuesTest extends TestCase {

	private const FROM = array( 'http://old.example', 'http:\/\/old.example' );
	private const TO   = array( 'https://new.test/sub', 'https:\/\/new.test\/sub' );

	private function rep( string $s ): string {
		return Shm_Database_Utility::replace_serialized_values( self::FROM, self::TO, $s );
	}

	public function test_plain_string_and_json_escaped_variants(): void {
		$this->assertSame( 'go https://new.test/sub/x', $this->rep( 'go http://old.example/x' ) );
		$this->assertSame( '{"u":"https:\/\/new.test\/sub\/x"}', $this->rep( '{"u":"http:\/\/old.example\/x"}' ) );
	}

	public function test_serialized_array_gets_correct_lengths(): void {
		$in  = serialize( array( 'home' => 'http://old.example', 'n' => 5, 'f' => 1.5, 'b' => true, 'z' => null, 'list' => array( 'http://old.example/a', 'keep' ) ) );
		$out = $this->rep( $in );
		$arr = unserialize( $out );
		$this->assertIsArray( $arr, 'result must still be valid serialized data' );
		$this->assertSame( 'https://new.test/sub', $arr['home'] );
		$this->assertSame( 'https://new.test/sub/a', $arr['list'][0] );
		$this->assertSame( array( 5, 1.5, true, null, 'keep' ), array( $arr['n'], $arr['f'], $arr['b'], $arr['z'], $arr['list'][1] ) );
	}

	public function test_multibyte_values_use_byte_lengths(): void {
		$in  = serialize( array( 't' => 'café 日本 🙂 http://old.example/é' ) );
		$arr = unserialize( $this->rep( $in ) );
		$this->assertSame( 'café 日本 🙂 https://new.test/sub/é', $arr['t'] );
	}

	public function test_serialized_inside_serialized(): void {
		$inner = serialize( array( 'u' => 'http://old.example/in' ) );
		$arr   = unserialize( $this->rep( serialize( array( 'blob' => $inner ) ) ) );
		$this->assertSame( 'https://new.test/sub/in', unserialize( $arr['blob'] )['u'] );
	}

	public function test_object_with_protected_and_private_properties(): void {
		$obj = unserialize( $this->rep( serialize( new ShmFixtureWidget() ) ) );
		$this->assertInstanceOf( ShmFixtureWidget::class, $obj );
		foreach ( $obj->dump() as $v ) {
			$this->assertStringStartsWith( 'pub https://new.test/sub', 'pub ' . substr( $v, strpos( $v, 'http' ) ), 'every property rewritten' );
			$this->assertStringNotContainsString( 'old.example', $v );
		}
	}

	public function test_class_that_is_not_loaded_on_the_target_site_is_still_rewritten(): void {
		$raw = 'O:11:"MissingPlug":1:{s:3:"url";s:24:"http://old.example/stuff";}';
		$this->assertSame( 24, strlen( 'http://old.example/stuff' ) );
		$out = $this->rep( $raw );
		$this->assertStringNotContainsString( 'old.example', $out );
		$obj = unserialize( $out );
		$this->assertSame( 'https://new.test/sub/stuff', ( (array) $obj )['url'] );
	}

	public function test_corrupt_serialized_input_never_fatals(): void {
		foreach ( array( 'a:1:{s:5:"abc";}', 's:99:"short";', 'a:1:{', 'O:3:"X', 's:3:"http://old.example";' ) as $bad ) {
			$this->assertIsString( $this->rep( $bad ) );
		}
	}

	public function test_unserialize_is_never_called_on_untrusted_data(): void {
		// a gadget-style payload must be treated as plain bytes, not instantiated
		$GLOBALS['shm_gadget_ran'] = false;
		$payload = 'O:' . strlen( 'ShmGadget' ) . ':"ShmGadget":0:{}';
		eval( 'class ShmGadget { public function __wakeup() { $GLOBALS["shm_gadget_ran"] = true; } public function __destruct() { $GLOBALS["shm_gadget_ran"] = true; } }' );
		$this->rep( $payload );
		$this->assertFalse( $GLOBALS['shm_gadget_ran'], 'search-replace must not instantiate objects' );
	}

	public function test_empty_search_list_is_a_noop(): void {
		$s = serialize( array( 'a' => 'http://old.example' ) );
		$this->assertSame( $s, Shm_Database_Utility::replace_serialized_values( array(), array(), $s ) );
	}

	public function test_two_megabyte_option_is_fast(): void {
		$big = serialize( array_fill( 0, 20000, 'row http://old.example/' . str_repeat( 'x', 80 ) ) );
		$t   = microtime( true );
		$out = $this->rep( $big );
		$this->assertLessThan( 3.0, microtime( true ) - $t );
		$this->assertSame( 20000, count( unserialize( $out ) ) );
	}
}
