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

class Shm_Export_Download {

	public static function execute( $params ) {

		// Set progress
		Shm_Status::info( __( 'Renaming exported file...', SHM_PLUGIN_NAME ) );

		// Open the archive file for writing
		$archive = new Shm_Compressor( shm_archive_path( $params ) );

		// Append EOF block
		$archive->close( true );

		// An archive that is nothing but the end-of-file block means the export lost its working files: say so
		// instead of offering an empty backup for download
		clearstatcache( true, shm_archive_path( $params ) );
		if ( filesize( shm_archive_path( $params ) ) <= 4377 ) {
			throw new Shm_Export_Exception( __( 'The export produced an empty archive. Please run the export again.', SHM_PLUGIN_NAME ) );
		}

		// Rename archive file
		if ( rename( shm_archive_path( $params ), shm_backup_path( $params ) ) ) {

			$blog_id = null;

			// Get subsite Blog ID
			if ( isset( $params['options']['sites'] ) && ( $sites = $params['options']['sites'] ) ) {
				if ( count( $sites ) === 1 ) {
					$blog_id = array_shift( $sites );
				}
			}

			// Set archive details
			$file = shm_archive_name( $params );
			$link = shm_backup_url( $params );
			$size = shm_backup_size( $params );
			$name = shm_site_name( $blog_id );

			// Set progress
			Shm_Status::download(
				sprintf(
					__(
						'<a href="%s" class="shm-button-green shm-emphasize shm-button-download" title="%s" download="%s">' .
						'<span>Download %s</span>' .
						'<em>Size: %s</em>' .
						'</a>',
						SHM_PLUGIN_NAME
					),
					$link,
					$name,
					$file,
					$name,
					$size
				)
			);
		}

		return $params;
	}
}
