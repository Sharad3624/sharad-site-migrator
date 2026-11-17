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

class Shm_Import_Upload {

	private static function validate() {
		if ( ! array_key_exists( 'upload-file', $_FILES ) || ! is_array( $_FILES['upload-file'] ) ) {
			throw new Shm_Import_Retry_Exception(
				__( 'Missing upload file.', SHM_PLUGIN_NAME ),
				400
			);
		}

		if ( ! array_key_exists( 'error', $_FILES['upload-file'] ) ) {
			throw new Shm_Import_Retry_Exception(
				__( 'Missing error key in upload file.', SHM_PLUGIN_NAME ),
				400
			);
		}

		if ( ! array_key_exists( 'tmp_name', $_FILES['upload-file'] ) ) {
			throw new Shm_Import_Retry_Exception(
				__( 'Missing tmp_name in upload file.', SHM_PLUGIN_NAME ),
				400
			);
		}
	}

	/**
	 * Append one chunk of an uploaded archive.
	 *
	 * Idempotent (a re-sent chunk that is already on disk is a no-op) and strictly
	 * ordered (a gap is a 409), so a flaky connection can retry any chunk safely.
	 *
	 * @param  string   $archive Destination archive path
	 * @param  string   $tmp     Uploaded chunk (PHP temp file)
	 * @param  integer  $offset  Bytes of the archive that precede this chunk
	 * @param  integer  $total   Declared size of the whole archive (null = unknown)
	 * @return integer           Archive size on disk after this chunk
	 * @throws Shm_Import_Retry_Exception
	 */
	public static function append_chunk( $archive, $tmp, $offset, $total = null ) {
		$offset = (int) $offset;
		if ( $offset < 0 ) {
			throw new Shm_Import_Retry_Exception( __( 'Invalid chunk offset.', SHM_PLUGIN_NAME ), 400 );
		}

		clearstatcache( true, $tmp );
		$length = (int) filesize( $tmp );
		if ( $total !== null && $offset + $length > (int) $total ) {
			throw new Shm_Import_Retry_Exception( __( 'The chunk is larger than the declared file size.', SHM_PLUGIN_NAME ), 400 );
		}

		clearstatcache( true, $archive );
		$current = is_file( $archive ) ? (int) filesize( $archive ) : 0;
		if ( $offset > 0 ) {
			// Already stored (the response was lost): acknowledge without writing twice
			if ( $current === $offset + $length ) {
				shm_unlink( $tmp );
				return $current;
			}

			if ( $current !== $offset ) {
				throw new Shm_Import_Retry_Exception(
					sprintf( __( 'Upload out of sequence: the server holds %1$d bytes but the chunk starts at %2$d.', SHM_PLUGIN_NAME ), $current, $offset ),
					409
				);
			}
		}

		$in  = @fopen( $tmp, 'rb' );
		$out = @fopen( $archive, $offset === 0 ? 'wb' : 'ab' );
		if ( ! $in || ! $out ) {
			throw new Shm_Import_Retry_Exception( __( 'Unable to open the archive for writing.', SHM_PLUGIN_NAME ), 500 );
		}

		$written = stream_copy_to_stream( $in, $out );
		fclose( $in );
		fclose( $out );
		if ( $written !== $length ) {
			throw new Shm_Import_Retry_Exception( __( 'Unable to write the chunk: the disk may be full.', SHM_PLUGIN_NAME ), 507 );
		}

		shm_unlink( $tmp );
		clearstatcache( true, $archive );

		return (int) filesize( $archive );
	}

	public static function execute( $params ) {
		self::validate();

		$error   = $_FILES['upload-file']['error'];
		$upload  = $_FILES['upload-file']['tmp_name'];
		$archive = shm_archive_path( $params );
		$size    = 0;

		switch ( $error ) {
			case UPLOAD_ERR_OK:
				if ( isset( $params['chunk_size'] ) ) {
					if ( ! is_scalar( $params['chunk_size'] ) || ! ctype_digit( (string) $params['chunk_size'] ) ) {
						throw new Shm_Import_Retry_Exception( __( 'Invalid chunk size.', SHM_PLUGIN_NAME ), 400 );
					}
					// Reject transport truncation before it changes the archive, so a smaller retry is safe.
					if ( filesize( $upload ) !== (int) $params['chunk_size'] ) {
						throw new Shm_Import_Retry_Exception( __( 'The upload chunk was incomplete. Retrying with a smaller chunk.', SHM_PLUGIN_NAME ), 413 );
					}
				}
				try {
					$size = self::append_chunk(
						$archive,
						$upload,
						isset( $params['chunk_offset'] ) ? $params['chunk_offset'] : 0,
						isset( $params['chunk_total'] ) ? $params['chunk_total'] : null
					);
				} catch ( Shm_Import_Retry_Exception $e ) {
					throw $e;
				} catch ( Exception $e ) {
					throw new Shm_Import_Retry_Exception(
						sprintf(
							__( 'Unable to upload the file because %s', SHM_PLUGIN_NAME ),
							$e->getMessage()
						),
						400
					);
				}
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_PARTIAL:
			case UPLOAD_ERR_NO_FILE:
				// A single chunk was too large for this server: the browser halves it and retries
				throw new Shm_Import_Retry_Exception(
					__( 'The chunk is too large for this server.', SHM_PLUGIN_NAME ),
					413
				);
			case UPLOAD_ERR_NO_TMP_DIR:
				throw new Shm_Import_Retry_Exception(
					__( 'Missing a temporary folder.', SHM_PLUGIN_NAME ),
					400
				);
			case UPLOAD_ERR_CANT_WRITE:
				throw new Shm_Import_Retry_Exception(
					__( 'Failed to write file to disk.', SHM_PLUGIN_NAME ),
					400
				);
			case UPLOAD_ERR_EXTENSION:
				throw new Shm_Import_Retry_Exception(
					__( 'A PHP extension stopped the file upload.', SHM_PLUGIN_NAME ),
					400
				);
			default:
				throw new Shm_Import_Retry_Exception(
					sprintf(
						__( 'Unrecognized error %s during upload.', SHM_PLUGIN_NAME ),
						$error
					),
					400
				);
		}

		echo json_encode( array( 'errors' => array(), 'size' => $size ) );
		exit;
	}
}
