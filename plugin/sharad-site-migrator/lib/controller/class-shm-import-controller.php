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

class Shm_Import_Controller {

	public static function index() {
		Shm_Template::render( 'import/index' );
	}

	public static function import( $params = array() ) {
		shm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( array_merge( $_GET, $_POST ) );
		}

		// Set priority
		if ( ! isset( $params['priority'] ) ) {
			$params['priority'] = 10;
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) && is_string( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access import action
			shm_verify_secret_key( $secret_key );
		} catch ( Shm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		// Loop over filters
		if ( ( $filters = shm_get_filters( 'shm_import' ) ) ) {
			while ( $hooks = current( $filters ) ) {
				if ( intval( $params['priority'] ) === key( $filters ) ) {
					foreach ( $hooks as $hook ) {
						try {

							// Run function hook
							$params = call_user_func_array( $hook['function'], array( $params ) );

							// Log request
							Shm_Log::import( $params );

						} catch ( Shm_Import_Retry_Exception $e ) {
							if ( defined( 'WP_CLI' ) ) {
								WP_CLI::error( sprintf( __( 'Unable to import. Error code: %s. %s', SHM_PLUGIN_NAME ), $e->getCode(), $e->getMessage() ) );
							} else {
								status_header( $e->getCode() );
								echo json_encode( array( 'errors' => array( array( 'code' => $e->getCode(), 'message' => $e->getMessage() ) ) ) );
							}
							exit;
						} catch ( Throwable $e ) {
							if ( defined( 'WP_CLI' ) ) {
								WP_CLI::error( sprintf( __( 'Unable to import: %s', SHM_PLUGIN_NAME ), $e->getMessage() ) );
							} else {
								Shm_Status::error( __( 'Unable to import', SHM_PLUGIN_NAME ), $e->getMessage() );
								Shm_Notification::error( __( 'Unable to import', SHM_PLUGIN_NAME ), $e->getMessage() );
							}
							Shm_Directory::delete( shm_storage_path( $params ) );
							exit;
						}
					}

					// Set completed
					$completed = true;
					if ( isset( $params['completed'] ) ) {
						$completed = (bool) $params['completed'];
					}

					// Do request
					if ( $completed === false || ( $next = next( $filters ) ) && ( $params['priority'] = key( $filters ) ) ) {
						if ( defined( 'WP_CLI' ) ) {
							if ( ! defined( 'DOING_CRON' ) ) {
								continue;
							}
						}

						if ( isset( $params['shm_manual_import'] ) || isset( $params['shm_manual_restore'] ) ) {
							echo json_encode( $params );
							exit;
						}

						wp_remote_post( apply_filters( 'shm_http_import_url', admin_url( 'admin-ajax.php?action=shm_import' ) ), array(
							'timeout'   => apply_filters( 'shm_http_import_timeout', 10 ),
							'blocking'  => apply_filters( 'shm_http_import_blocking', false ),
							'sslverify' => apply_filters( 'shm_http_import_sslverify', false ),
							'headers'   => apply_filters( 'shm_http_import_headers', array() ),
							'body'      => apply_filters( 'shm_http_import_body', $params ),
						) );
						exit;
					}
				}

				next( $filters );
			}
		}

		return $params;
	}

	public static function buttons() {
		return array(
			apply_filters( 'shm_import_file', Shm_Template::get_content( 'import/button-file' ) ),
		);
	}

	public static function http_import_headers( $headers = array() ) {
		if ( ( $user = get_option( SHM_AUTH_USER ) ) && ( $password = get_option( SHM_AUTH_PASSWORD ) ) ) {
			if ( ( $hash = base64_encode( sprintf( '%s:%s', $user, $password ) ) ) ) {
				$headers['Authorization'] = sprintf( 'Basic %s', $hash );
			}
		}

		return $headers;
	}

	/**
	 * Biggest chunk the browser may POST: 90% of the tightest PHP limit (headroom for
	 * multipart overhead), capped at SHM_MAX_CHUNK_SIZE, never below 64KB.
	 * A limit of 0 or an unparsable value means "no limit".
	 */
	public static function chunk_size( $post_max_size, $upload_max_filesize ) {
		$limits = array_filter( array( shm_parse_size( $post_max_size, 0 ), shm_parse_size( $upload_max_filesize, 0 ) ) );
		$size   = SHM_MAX_CHUNK_SIZE;
		if ( $limits ) {
			$size = min( $size, (int) floor( min( $limits ) * 0.9 ) );
		}

		return max( 65536, $size );
	}

	public static function max_chunk_size() {
		return self::chunk_size( ini_get( 'post_max_size' ), ini_get( 'upload_max_filesize' ) );
	}
}
