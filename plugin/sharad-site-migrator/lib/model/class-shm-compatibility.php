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

class Shm_Compatibility {

	public static function check_php_version( $source, $target ) {
		if ( ! is_string( $source ) || ! preg_match( '/^[0-9]+\.[0-9]+(?:\.[0-9]+)?[a-zA-Z0-9.+~-]*$/D', $source ) ) {
			throw new Shm_Compatibility_Exception( __( 'The backup does not contain a valid PHP version. Create a fresh backup with PHP version metadata before importing.', SHM_PLUGIN_NAME ) );
		}

		$source_branch = implode( '.', array_slice( explode( '.', $source ), 0, 2 ) );
		$target_branch = implode( '.', array_slice( explode( '.', $target ), 0, 2 ) );

		// Imported plugins and themes can break on both PHP upgrades and downgrades.
		if ( $source_branch !== $target_branch ) {
			throw new Shm_Compatibility_Exception( sprintf(
				__( 'Import stopped before overwriting your site. Backup PHP: %1$s. Destination PHP: %2$s. Match the PHP major and minor versions on both sites (for example, both on PHP %3$s), then retry. The migrator cannot convert incompatible plugin or theme code.', SHM_PLUGIN_NAME ),
				esc_html( $source ),
				esc_html( $target ),
				esc_html( $source_branch )
			) );
		}
	}

	/**
	 * Messages that block an import/export. There are no separately-versioned
	 * extensions in this plugin, so there is never anything to report.
	 *
	 * @param  array $params Request parameters
	 * @return array
	 */
	public static function get( $params ) {
		return array();
	}
}
