<?php
/**
 * Copyright (C) 2014-2019 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Shm_Handler {

	/**
	 * Error handler
	 *
	 * @param  integer $errno   Error level
	 * @param  string  $errstr  Error message
	 * @param  string  $errfile Error file
	 * @param  integer $errline Error line
	 * @return void
	 */
	public static function error( $errno, $errstr, $errfile, $errline ) {
		// Respect the @ operator and error_reporting(): a suppressed error is one the calling code already handles
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		// A short call trace: a notice raised inside WordPress core is useless without knowing who called it
		$trace = array();
		foreach ( array_slice( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 9 ), 1 ) as $frame ) {
			$trace[] = ( isset( $frame['file'] ) ? basename( $frame['file'] ) . ':' . $frame['line'] : '' ) . ' ' . ( isset( $frame['class'] ) ? $frame['class'] . '::' : '' ) . $frame['function'];
		}

		Shm_Log::error( array(
			'Number'  => $errno,
			'Message' => $errstr,
			'File'    => $errfile,
			'Line'    => $errline,
			'Trace'   => $trace,
		) );
	}

	/**
	 * Shutdown handler
	 *
	 * @param  array|null $error Last error (defaults to error_get_last())
	 * @return void
	 */
	public static function shutdown( $error = null ) {
		if ( $error === null ) {
			$error = error_get_last();
		}

		// error_get_last() also returns warnings that were suppressed with @ and already handled:
		// only a request that actually died deserves an entry
		$fatal = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;
		if ( $error && ( $error['type'] & $fatal ) ) {
			Shm_Log::error( $error );
		}
	}
}
