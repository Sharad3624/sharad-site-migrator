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

class Shm_Export_Controller {

	public static function index() {
		Shm_Template::render( 'export/index' );
	}

	public static function export( $params = array() ) {
		shm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( array_merge( $_GET, $_POST ) );
		}

		// Set priority
		if ( ! isset( $params['priority'] ) ) {
			$params['priority'] = 5;
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) && is_string( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access export action
			shm_verify_secret_key( $secret_key );
		} catch ( Shm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		// Loop over filters
		if ( ( $filters = shm_get_filters( 'shm_export' ) ) ) {
			while ( $hooks = current( $filters ) ) {
				if ( intval( $params['priority'] ) === key( $filters ) ) {
					foreach ( $hooks as $hook ) {
						try {

							// Run function hook
							$params = call_user_func_array( $hook['function'], array( $params ) );

							// Log request
							Shm_Log::export( $params );

						} catch ( Throwable $e ) {
							if ( defined( 'WP_CLI' ) ) {
								WP_CLI::error( sprintf( __( 'Unable to export: %s', SHM_PLUGIN_NAME ), $e->getMessage() ) );
							} else {
								Shm_Status::error( __( 'Unable to export', SHM_PLUGIN_NAME ), $e->getMessage() );
								Shm_Notification::error( __( 'Unable to export', SHM_PLUGIN_NAME ), $e->getMessage() );
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

						if ( isset( $params['shm_manual_export'] ) ) {
							echo json_encode( $params );
							exit;
						}

						wp_remote_post( apply_filters( 'shm_http_export_url', admin_url( 'admin-ajax.php?action=shm_export' ) ), array(
							'timeout'   => apply_filters( 'shm_http_export_timeout', 10 ),
							'blocking'  => apply_filters( 'shm_http_export_blocking', false ),
							'sslverify' => apply_filters( 'shm_http_export_sslverify', false ),
							'headers'   => apply_filters( 'shm_http_export_headers', array() ),
							'body'      => apply_filters( 'shm_http_export_body', $params ),
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
			apply_filters( 'shm_export_file', Shm_Template::get_content( 'export/button-file' ) ),
		);
	}

	public static function http_export_headers( $headers = array() ) {
		if ( ( $user = get_option( SHM_AUTH_USER ) ) && ( $password = get_option( SHM_AUTH_PASSWORD ) ) ) {
			if ( ( $hash = base64_encode( sprintf( '%s:%s', $user, $password ) ) ) ) {
				$headers['Authorization'] = sprintf( 'Basic %s', $hash );
			}
		}

		return $headers;
	}

	public static function cleanup() {
		try {
			// Iterate over storage directory
			$iterator = new Shm_Recursive_Directory_Iterator( SHM_STORAGE_PATH );

			// Exclude index.php
			$iterator = new Shm_Recursive_Exclude_Filter( $iterator, array( 'index.php' ) );

			// Loop over folders and files
			foreach ( $iterator as $item ) {
				try {
					if ( $item->getMTime() < ( time() - SHM_MAX_STORAGE_CLEANUP ) ) {
						if ( $item->isDir() ) {
							Shm_Directory::delete( $item->getPathname() );
						} else {
							Shm_File::delete( $item->getPathname() );
						}
					}
				} catch ( Exception $e ) {
				}
			}
		} catch ( Exception $e ) {
		}
	}
}
