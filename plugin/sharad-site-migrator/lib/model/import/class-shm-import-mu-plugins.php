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

class Shm_Import_Mu_Plugins {

	public static function execute( $params ) {

		// Set progress
		Shm_Status::info( __( 'Activating mu-plugins...', SHM_PLUGIN_NAME ) );

		$exclude_files = array(
			SHM_MUPLUGINS_NAME . DIRECTORY_SEPARATOR . SHM_ENDURANCE_PAGE_CACHE_NAME,
			SHM_MUPLUGINS_NAME . DIRECTORY_SEPARATOR . SHM_ENDURANCE_PHP_EDGE_NAME,
			SHM_MUPLUGINS_NAME . DIRECTORY_SEPARATOR . SHM_ENDURANCE_BROWSER_CACHE_NAME,
			SHM_MUPLUGINS_NAME . DIRECTORY_SEPARATOR . SHM_GD_SYSTEM_PLUGIN_NAME,
			SHM_MUPLUGINS_NAME . DIRECTORY_SEPARATOR . SHM_WP_STACK_CACHE_NAME,
			SHM_MUPLUGINS_NAME . DIRECTORY_SEPARATOR . SHM_WP_COMSH_LOADER_NAME,
		);

		// Open the archive file for reading
		$archive = new Shm_Extractor( shm_archive_path( $params ) );

		// Unpack mu-plugins files
		$archive->extract_by_files_array( WP_CONTENT_DIR, array( SHM_MUPLUGINS_NAME ), $exclude_files );

		// Close the archive file
		$archive->close();

		// Set progress
		Shm_Status::info( __( 'Done activating mu-plugins.', SHM_PLUGIN_NAME ) );

		return $params;
	}
}
