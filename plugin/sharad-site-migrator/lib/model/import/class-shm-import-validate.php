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

class Shm_Import_Validate {

	public static function execute( $params ) {

		if ( isset( $params['upload_size'] ) ) {
			if ( ! is_scalar( $params['upload_size'] ) || ! ctype_digit( (string) $params['upload_size'] ) ) {
				throw new Shm_Import_Exception( __( 'Invalid upload size.', SHM_PLUGIN_NAME ) );
			}
			clearstatcache( true, shm_archive_path( $params ) );
			if ( ! is_file( shm_archive_path( $params ) ) || filesize( shm_archive_path( $params ) ) !== (int) $params['upload_size'] ) {
				throw new Shm_Import_Exception( __( 'The archive upload is incomplete. Please upload the backup again.', SHM_PLUGIN_NAME ) );
			}
		}

		// Verify file if size > 2GB and PHP = 32-bit
		if ( ! shm_is_filesize_supported( shm_archive_path( $params ) ) ) {
			throw new Shm_Import_Exception(
				__(
					'Your PHP is 32-bit. In order to import your file, please change your PHP version to 64-bit and try again. ' .
					'',
					SHM_PLUGIN_NAME
				)
			);
		}

		// Set archive bytes offset
		if ( isset( $params['archive_bytes_offset'] ) ) {
			$archive_bytes_offset = (int) $params['archive_bytes_offset'];
		} else {
			$archive_bytes_offset = 0;
		}

		// Set file bytes offset
		if ( isset( $params['file_bytes_offset'] ) ) {
			$file_bytes_offset = (int) $params['file_bytes_offset'];
		} else {
			$file_bytes_offset = 0;
		}

		// Get total archive size
		if ( isset( $params['total_archive_size'] ) ) {
			$total_archive_size = (int) $params['total_archive_size'];
		} else {
			$total_archive_size = shm_archive_bytes( $params );
		}

		if ( $total_archive_size < 1 ) {
			throw new Shm_Import_Exception( __( 'The archive file is empty. Please upload a valid backup.', SHM_PLUGIN_NAME ) );
		}

		// What percent of archive have we processed?
		$progress = (int) min( ( $archive_bytes_offset / $total_archive_size ) * 100, 100 );

		// Set progress
		Shm_Status::info( sprintf( __( 'Unpacking archive...<br />%d%% complete', SHM_PLUGIN_NAME ), $progress ) );

		// Open the archive file for reading
		$archive = new Shm_Extractor( shm_archive_path( $params ) );

		// Set the file pointer to the one that we have saved
		$archive->set_file_pointer( $archive_bytes_offset );

		// Validate the archive file consistency
		if ( ! $archive->is_valid() ) {
			throw new Shm_Import_Exception(
				__(
					'The archive file is corrupted. Please download a fresh backup and upload it again.',
					SHM_PLUGIN_NAME
				)
			);
		}

		// Flag to hold if file data has been processed
		$completed = true;

		if ( $archive->has_not_reached_eof() ) {
			$file_bytes_written = 0;

			// Unpack package.json, multisite.json and database.sql files
			if ( ( $completed = $archive->extract_by_files_array( shm_storage_path( $params ), array( SHM_PACKAGE_NAME, SHM_MULTISITE_NAME, SHM_DATABASE_NAME ), array(), array(), $file_bytes_written, $file_bytes_offset ) ) ) {
				$file_bytes_offset = 0;
			}

			// Get archive bytes offset
			$archive_bytes_offset = $archive->get_file_pointer();
		}

		// End of the archive?
		if ( $archive->has_reached_eof() ) {

			// Check package.json file
			if ( false === is_file( shm_package_path( $params ) ) ) {
				throw new Shm_Import_Exception(
					__(
						'Please make sure that your file was exported using <strong>All-in-One WP Migration</strong> plugin. ' .
						'',
						SHM_PLUGIN_NAME
					)
				);
			}

			// Set progress
			Shm_Status::info( __( 'Done unpacking archive.', SHM_PLUGIN_NAME ) );

			// Unset archive bytes offset
			unset( $params['archive_bytes_offset'] );

			// Unset file bytes offset
			unset( $params['file_bytes_offset'] );

			// Unset total archive size
			unset( $params['total_archive_size'] );

			// Unset completed flag
			unset( $params['completed'] );

		} else {

			// What percent of archive have we processed?
			$progress = (int) min( ( $archive_bytes_offset / $total_archive_size ) * 100, 100 );

			// Set progress
			Shm_Status::info( sprintf( __( 'Unpacking archive...<br />%d%% complete', SHM_PLUGIN_NAME ), $progress ) );

			// Set archive bytes offset
			$params['archive_bytes_offset'] = $archive_bytes_offset;

			// Set file bytes offset
			$params['file_bytes_offset'] = $file_bytes_offset;

			// Set total archive size
			$params['total_archive_size'] = $total_archive_size;

			// Set completed flag
			$params['completed'] = $completed;
		}

		// Close the archive file
		$archive->close();

		return $params;
	}
}
