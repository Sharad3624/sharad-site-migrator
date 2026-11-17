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

class Shm_Database_Utility {

	/**
	 * Replace all occurrences of the search string with the replacement string.
	 * This function is case-sensitive.
	 *
	 * @param  array  $from List of string we're looking to replace.
	 * @param  array  $to   What we want it to be replaced with.
	 * @param  string $data Data to replace.
	 * @return mixed        The original string with all elements replaced as needed.
	 */
	public static function replace_values( $from = array(), $to = array(), $data = '' ) {
		if ( ! empty( $from ) && ! empty( $to ) ) {
			return strtr( $data, array_combine( $from, $to ) );
		}

		return $data;
	}

	/**
	 * Take a serialized array and unserialize it replacing elements as needed and
	 * unserializing any subordinate arrays and performing the replace on those too.
	 * This function is case-sensitive.
	 *
	 * @param  array $from       List of string we're looking to replace.
	 * @param  array $to         What we want it to be replaced with.
	 * @param  mixed $data       Used to pass any subordinate arrays back to in.
	 * @param  bool  $serialized Does the array passed via $data need serializing.
	 * @return mixed             The original array with all elements replaced as needed.
	 */
	public static function replace_serialized_values( $from = array(), $to = array(), $data = '', $serialized = false ) {
		if ( empty( $from ) || empty( $to ) || count( $from ) !== count( $to ) ) {
			return $data;
		}

		return self::replace_in( array_combine( $from, $to ), $data );
	}

	/**
	 * Replace inside any value. Serialized strings are rewritten token by token
	 * (s:N:"...";) with their byte lengths recomputed. No unserialize(): nothing is
	 * instantiated, private/protected properties and classes that are not loaded on
	 * this site are rewritten too.
	 *
	 * @param  array $map  Search => replacement
	 * @param  mixed $data Value
	 * @return mixed
	 */
	private static function replace_in( $map, $data ) {
		if ( is_array( $data ) ) {
			$tmp = array();
			foreach ( $data as $key => $value ) {
				$tmp[ $key ] = self::replace_in( $map, $value );
			}

			return $tmp;
		}

		if ( ! is_string( $data ) ) {
			return $data;
		}

		if ( ! is_serialized( $data ) ) {
			return strtr( $data, $map );
		}

		$out = '';
		$pos = 0;
		$len = strlen( $data );
		while ( $pos < $len && preg_match( '/s:(\d+):"/', $data, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
			$start = $m[0][1];
			$size  = (int) $m[1][0];
			$begin = $start + strlen( $m[0][0] );

			// Not a real string token (length does not land on the closing quote): move on
			if ( $begin + $size + 2 > $len || substr( $data, $begin + $size, 2 ) !== '";' ) {
				$out .= substr( $data, $pos, $start + 1 - $pos );
				$pos  = $start + 1;
				continue;
			}

			$value = self::replace_in( $map, substr( $data, $begin, $size ) );
			$out  .= substr( $data, $pos, $start - $pos ) . 's:' . strlen( $value ) . ':"' . $value . '";';
			$pos   = $begin + $size + 2;
		}

		return $out . substr( $data, $pos );
	}

	/**
	 * Run $callback over every quoted SQL string literal of a statement.
	 * A literal is: a quote, then runs of plain characters, backslash-escapes (\\x) or
	 * doubled quotes, then a quote. So a literal that ENDS in an escaped backslash
	 * (...\\') closes at that quote; a naive "quote not preceded by a backslash" regex
	 * does not, and swallows the rest of the statement.
	 *
	 * @param  string   $sql      SQL statement
	 * @param  callable $callback Receives the preg match (group 1 = literal contents, still escaped)
	 * @return string
	 * @throws Shm_Database_Exception
	 */
	public static function map_sql_strings( $sql, $callback ) {
		// possessive quantifiers: linear time, no backtracking, safe on multi-megabyte literals
		$result = preg_replace_callback( '/\'((?:[^\'\\\\]++|\\\\.|\'\')*+)\'/Ss', $callback, $sql );
		if ( $result === null ) {
			throw new Shm_Database_Exception( sprintf( 'Unable to scan SQL strings (PCRE error %d)', preg_last_error() ) );
		}

		return $result;
	}

	/**
	 * Escape MySQL special characters
	 *
	 * @param  string $data Data to escape
	 * @return string
	 */
	public static function escape_mysql( $data ) {
		return strtr(
			$data,
			array_combine(
				array( "\x00", "\n", "\r", '\\', "'", '"', "\x1a" ),
				array( '\\0', '\\n', '\\r', '\\\\', "\\'", '\\"', '\\Z' )
			)
		);
	}

	/**
	 * Unescape MySQL special characters
	 *
	 * @param  string $data Data to unescape
	 * @return string
	 */
	public static function unescape_mysql( $data ) {
		return strtr(
			$data,
			array_combine(
				array( '\\0', '\\n', '\\r', '\\\\', "\\'", '\\"', '\\Z' ),
				array( "\x00", "\n", "\r", '\\', "'", '"', "\x1a" )
			)
		);
	}

	/**
	 * Encode base64 characters
	 *
	 * @param  string $data Data to encode
	 * @return string
	 */
	public static function base64_encode( $data ) {
		return base64_encode( $data );
	}

	/**
	 * Encode base64 characters
	 *
	 * @param  string $data Data to decode
	 * @return string
	 */
	public static function base64_decode( $data ) {
		return base64_decode( $data );
	}

	/**
	 * Validate base64 data
	 *
	 * @param  string  $data Data to validate
	 * @return boolean
	 */
	public static function base64_validate( $data ) {
		return base64_encode( base64_decode( $data ) ) === $data;
	}
}
