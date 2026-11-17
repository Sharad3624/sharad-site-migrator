<?php
use PHPUnit\Framework\TestCase;

/** The plugin installs its own PHP error handler; it must behave like PHP's: silence for @, a trace for real problems. */
final class HandlerTest extends TestCase {

	private string $log;

	protected function setUp(): void {
		@mkdir( SHM_STORAGE_PATH, 0777, true );
		$this->log = SHM_STORAGE_PATH . '/error.log';
		@unlink( $this->log );
	}

	protected function tearDown(): void {
		restore_error_handler();
		@unlink( $this->log );
	}

	public function test_errors_suppressed_with_at_are_not_logged(): void {
		$old = error_reporting( E_ALL );
		set_error_handler( 'Shm_Handler::error' );
		@fopen( '/definitely/not/here', 'r' );
		restore_error_handler();
		error_reporting( $old );
		$this->assertFileDoesNotExist( $this->log, 'an @-suppressed warning is one the code already handles' );
	}

	public function test_real_problems_are_logged_with_a_call_trace(): void {
		$old = error_reporting( E_ALL );     // PHPUnit narrows the mask while it runs tests
		set_error_handler( 'Shm_Handler::error' );
		$this->triggerWarning();
		restore_error_handler();
		error_reporting( $old );
		$this->assertFileExists( $this->log );
		$entry = json_decode( trim( explode( "\n", file_get_contents( $this->log ) )[1] ), true );
		$this->assertSame( 'boom', $entry['Message'] );
		$this->assertNotEmpty( $entry['Trace'] );
		$this->assertStringContainsString( 'triggerWarning', implode( ' ', $entry['Trace'] ) );
	}

	public function test_shutdown_ignores_leftover_warnings_but_records_fatals(): void {
		// error_get_last() also returns warnings that were suppressed with @ and already handled
		Shm_Handler::shutdown( array( 'type' => E_WARNING, 'message' => 'fopen(): handled', 'file' => 'x.php', 'line' => 1 ) );
		$this->assertFileDoesNotExist( $this->log );
		Shm_Handler::shutdown( array( 'type' => E_ERROR, 'message' => 'Allowed memory size exhausted', 'file' => 'x.php', 'line' => 2 ) );
		$this->assertFileExists( $this->log );
		$this->assertStringContainsString( 'Allowed memory size exhausted', file_get_contents( $this->log ) );
	}

	private function triggerWarning(): void { trigger_error( 'boom', E_USER_WARNING ); }
}
