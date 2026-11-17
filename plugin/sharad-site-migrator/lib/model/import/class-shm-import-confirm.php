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

class Shm_Import_Confirm {

	public static function execute( $params ) {

		$messages = array();

		// Read package.json file
		$handle = shm_open( shm_package_path( $params ), 'r' );

		// Parse package.json file
		$package = shm_read( $handle, filesize( shm_package_path( $params ) ) );
		$package = json_decode( $package, true );

		// Close handle
		shm_close( $handle );

		// Set message
		$messages[] = __(
			'The import process will overwrite your website including the database, media, plugins, and themes. ' .
			'Please ensure that you have a backup of your data before proceeding to the next step.',
			SHM_PLUGIN_NAME
		);

		// Check compatibility of PHP versions
		if ( isset( $package['PHP']['Version'] ) ) {
			if ( version_compare( $package['PHP']['Version'], '7.0.0', '<' ) && version_compare( PHP_VERSION, '7.0.0', '>=' ) ) {

				if ( defined( 'WP_CLI' ) ) {
					WP_CLI::log(
						__( 'Your backup is from a PHP 5 but the site that you are importing to is PHP 7.', SHM_PLUGIN_NAME ) .
						__( 'This could cause the import to fail.', SHM_PLUGIN_NAME )
					);
				} else {
					$messages[] = __(
						'<i class="shm-import-info">Your backup is from a PHP 5 but the site that you are importing to is PHP 7. ' .
						'This could cause the import to fail.</i>',
						SHM_PLUGIN_NAME
					);
				}
			}
		}

		if ( defined( 'WP_CLI' ) ) {
			$message = __(
				'The import process will overwrite your website including the database, media, plugins, and themes. Are you sure to proceed?',
				SHM_PLUGIN_NAME
			);

			$assoc_args = array();
			if ( isset( $params['cli_args'] ) ) {
				$assoc_args = $params['cli_args'];
			}
			WP_CLI::confirm( $message, $assoc_args );
			return $params;
		}

		// Set progress
		Shm_Status::confirm( implode( $messages ) );
		exit;
	}
}
