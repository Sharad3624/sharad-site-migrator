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

class Shm_Import_Database {

	public static function execute( $params ) {
		global $wpdb;

		// Skip database import
		if ( ! is_file( shm_database_path( $params ) ) ) {
			return $params;
		}

		// Set query offset
		if ( isset( $params['query_offset'] ) ) {
			$query_offset = (int) $params['query_offset'];
		} else {
			$query_offset = 0;
		}

		// Set total queries size
		if ( isset( $params['total_queries_size'] ) ) {
			$total_queries_size = (int) $params['total_queries_size'];
		} else {
			$total_queries_size = 1;
		}

		// Read blogs.json file
		$handle = shm_open( shm_blogs_path( $params ), 'r' );

		// Parse blogs.json file
		$blogs = shm_read( $handle, filesize( shm_blogs_path( $params ) ) );
		$blogs = json_decode( $blogs, true );

		// Close handle
		shm_close( $handle );

		// Read package.json file
		$handle = shm_open( shm_package_path( $params ), 'r' );

		// Parse package.json file
		$config = shm_read( $handle, filesize( shm_package_path( $params ) ) );
		$config = json_decode( $config, true );

		// Close handle
		shm_close( $handle );

		// What percent of queries have we processed?
		$progress = (int) ( ( $query_offset / $total_queries_size ) * 100 );

		// Set progress
		Shm_Status::info( sprintf( __( 'Restoring database...<br />%d%% complete', SHM_PLUGIN_NAME ), $progress ) );

		$old_replace_values = $old_replace_raw_values = array();
		$new_replace_values = $new_replace_raw_values = array();

		// Get Blog URLs
		foreach ( $blogs as $blog ) {

			$home_urls = array();

			// Add Home URL
			if ( ! empty( $blog['Old']['HomeURL'] ) ) {
				$home_urls[] = $blog['Old']['HomeURL'];
			}

			// Add Internal Home URL
			if ( ! empty( $blog['Old']['InternalHomeURL'] ) ) {
				if ( parse_url( $blog['Old']['InternalHomeURL'], PHP_URL_SCHEME ) && parse_url( $blog['Old']['InternalHomeURL'], PHP_URL_HOST ) ) {
					$home_urls[] = $blog['Old']['InternalHomeURL'];
				}
			}

			// Get Home URL
			foreach ( $home_urls as $home_url ) {

				// Get blogs dir Upload Path
				if ( ! in_array( sprintf( "'%s'", trim( shm_blogsdir_path( $blog['Old']['BlogID'] ), '/' ) ), $old_replace_raw_values ) ) {
					$old_replace_raw_values[] = sprintf( "'%s'", trim( shm_blogsdir_path( $blog['Old']['BlogID'] ), '/' ) );
					$new_replace_raw_values[] = sprintf( "'%s'", get_option( 'upload_path' ) );
				}

				// Get sites dir Upload Path
				if ( ! in_array( sprintf( "'%s'", trim( shm_uploads_path( $blog['Old']['BlogID'] ), '/' ) ), $old_replace_raw_values ) ) {
					$old_replace_raw_values[] = sprintf( "'%s'", trim( shm_uploads_path( $blog['Old']['BlogID'] ), '/' ) );
					$new_replace_raw_values[] = sprintf( "'%s'", get_option( 'upload_path' ) );
				}

				// Handle old and new sites dir style
				if ( defined( 'UPLOADBLOGSDIR' ) ) {

					// Get plain Upload Path
					if ( ! in_array( shm_blogsdir_path( $blog['Old']['BlogID'] ), $old_replace_values ) ) {
						$old_replace_values[] = shm_blogsdir_path( $blog['Old']['BlogID'] );
						$new_replace_values[] = shm_blogsdir_path( $blog['New']['BlogID'] );
					}

					// Get URL encoded Upload Path
					if ( ! in_array( urlencode( shm_blogsdir_path( $blog['Old']['BlogID'] ) ), $old_replace_values ) ) {
						$old_replace_values[] = urlencode( shm_blogsdir_path( $blog['Old']['BlogID'] ) );
						$new_replace_values[] = urlencode( shm_blogsdir_path( $blog['New']['BlogID'] ) );
					}

					// Get URL raw encoded Upload Path
					if ( ! in_array( rawurlencode( shm_blogsdir_path( $blog['Old']['BlogID'] ) ), $old_replace_values ) ) {
						$old_replace_values[] = rawurlencode( shm_blogsdir_path( $blog['Old']['BlogID'] ) );
						$new_replace_values[] = rawurlencode( shm_blogsdir_path( $blog['New']['BlogID'] ) );
					}

					// Get JSON escaped Upload Path
					if ( ! in_array( addcslashes( shm_blogsdir_path( $blog['Old']['BlogID'] ), '/' ), $old_replace_values ) ) {
						$old_replace_values[] = addcslashes( shm_blogsdir_path( $blog['Old']['BlogID'] ), '/' );
						$new_replace_values[] = addcslashes( shm_blogsdir_path( $blog['New']['BlogID'] ), '/' );
					}

					// Get plain Upload Path
					if ( ! in_array( shm_uploads_path( $blog['Old']['BlogID'] ), $old_replace_values ) ) {
						$old_replace_values[] = shm_uploads_path( $blog['Old']['BlogID'] );
						$new_replace_values[] = shm_blogsdir_path( $blog['New']['BlogID'] );
					}

					// Get URL encoded Upload Path
					if ( ! in_array( urlencode( shm_uploads_path( $blog['Old']['BlogID'] ) ), $old_replace_values ) ) {
						$old_replace_values[] = urlencode( shm_uploads_path( $blog['Old']['BlogID'] ) );
						$new_replace_values[] = urlencode( shm_blogsdir_path( $blog['New']['BlogID'] ) );
					}

					// Get URL raw encoded Upload Path
					if ( ! in_array( rawurlencode( shm_uploads_path( $blog['Old']['BlogID'] ) ), $old_replace_values ) ) {
						$old_replace_values[] = rawurlencode( shm_uploads_path( $blog['Old']['BlogID'] ) );
						$new_replace_values[] = rawurlencode( shm_blogsdir_path( $blog['New']['BlogID'] ) );
					}

					// Get JSON escaped Upload Path
					if ( ! in_array( addcslashes( shm_uploads_path( $blog['Old']['BlogID'] ), '/' ), $old_replace_values ) ) {
						$old_replace_values[] = addcslashes( shm_uploads_path( $blog['Old']['BlogID'] ), '/' );
						$new_replace_values[] = addcslashes( shm_blogsdir_path( $blog['New']['BlogID'] ), '/' );
					}
				} else {

					// Get files dir Upload URL
					if ( ! in_array( sprintf( '%s/%s/', untrailingslashit( $home_url ), 'files' ), $old_replace_values ) ) {
						$old_replace_values[] = sprintf( '%s/%s/', untrailingslashit( $home_url ), 'files' );
						$new_replace_values[] = shm_uploads_url( $blog['New']['BlogID'] );
					}

					// Get plain Upload Path
					if ( ! in_array( shm_blogsdir_path( $blog['Old']['BlogID'] ), $old_replace_values ) ) {
						$old_replace_values[] = shm_blogsdir_path( $blog['Old']['BlogID'] );
						$new_replace_values[] = shm_uploads_path( $blog['New']['BlogID'] );
					}

					// Get URL encoded Upload Path
					if ( ! in_array( urlencode( shm_blogsdir_path( $blog['Old']['BlogID'] ) ), $old_replace_values ) ) {
						$old_replace_values[] = urlencode( shm_blogsdir_path( $blog['Old']['BlogID'] ) );
						$new_replace_values[] = urlencode( shm_uploads_path( $blog['New']['BlogID'] ) );
					}

					// Get URL raw encoded Upload Path
					if ( ! in_array( rawurlencode( shm_blogsdir_path( $blog['Old']['BlogID'] ) ), $old_replace_values ) ) {
						$old_replace_values[] = rawurlencode( shm_blogsdir_path( $blog['Old']['BlogID'] ) );
						$new_replace_values[] = rawurlencode( shm_uploads_path( $blog['New']['BlogID'] ) );
					}

					// Get JSON escaped Upload Path
					if ( ! in_array( addcslashes( shm_blogsdir_path( $blog['Old']['BlogID'] ), '/' ), $old_replace_values ) ) {
						$old_replace_values[] = addcslashes( shm_blogsdir_path( $blog['Old']['BlogID'] ), '/' );
						$new_replace_values[] = addcslashes( shm_uploads_path( $blog['New']['BlogID'] ), '/' );
					}

					// Get plain Upload Path
					if ( ! in_array( shm_uploads_path( $blog['Old']['BlogID'] ), $old_replace_values ) ) {
						$old_replace_values[] = shm_uploads_path( $blog['Old']['BlogID'] );
						$new_replace_values[] = shm_uploads_path( $blog['New']['BlogID'] );
					}

					// Get URL encoded Upload Path
					if ( ! in_array( urlencode( shm_uploads_path( $blog['Old']['BlogID'] ) ), $old_replace_values ) ) {
						$old_replace_values[] = urlencode( shm_uploads_path( $blog['Old']['BlogID'] ) );
						$new_replace_values[] = urlencode( shm_uploads_path( $blog['New']['BlogID'] ) );
					}

					// Get URL raw encoded Upload Path
					if ( ! in_array( rawurlencode( shm_uploads_path( $blog['Old']['BlogID'] ) ), $old_replace_values ) ) {
						$old_replace_values[] = rawurlencode( shm_uploads_path( $blog['Old']['BlogID'] ) );
						$new_replace_values[] = rawurlencode( shm_uploads_path( $blog['New']['BlogID'] ) );
					}

					// Get JSON escaped Upload Path
					if ( ! in_array( addcslashes( shm_uploads_path( $blog['Old']['BlogID'] ), '/' ), $old_replace_values ) ) {
						$old_replace_values[] = addcslashes( shm_uploads_path( $blog['Old']['BlogID'] ), '/' );
						$new_replace_values[] = addcslashes( shm_uploads_path( $blog['New']['BlogID'] ), '/' );
					}
				}
			}

			$site_urls = array();

			// Add Site URL
			if ( ! empty( $blog['Old']['SiteURL'] ) ) {
				$site_urls[] = $blog['Old']['SiteURL'];
			}

			// Add Internal Site URL
			if ( ! empty( $blog['Old']['InternalSiteURL'] ) ) {
				if ( parse_url( $blog['Old']['InternalSiteURL'], PHP_URL_SCHEME ) && parse_url( $blog['Old']['InternalSiteURL'], PHP_URL_HOST ) ) {
					$site_urls[] = $blog['Old']['InternalSiteURL'];
				}
			}

			// Get Site URL
			foreach ( $site_urls as $site_url ) {

				// Get www URL
				if ( stripos( $site_url, '//www.' ) !== false ) {
					$site_url_www_inversion = str_ireplace( '//www.', '//', $site_url );
				} else {
					$site_url_www_inversion = str_ireplace( '//', '//www.', $site_url );
				}

				// Replace Site URL
				foreach ( array( $site_url, $site_url_www_inversion ) as $url ) {

					// Get domain
					$old_domain = parse_url( $url, PHP_URL_HOST );
					$new_domain = parse_url( $blog['New']['SiteURL'], PHP_URL_HOST );

					// Get path
					$old_path = (string) parse_url( $url, PHP_URL_PATH );
					$new_path = (string) parse_url( $blog['New']['SiteURL'], PHP_URL_PATH );

					// Get scheme
					$new_scheme = parse_url( $blog['New']['SiteURL'], PHP_URL_SCHEME );

					// Add domain and path
					if ( ! in_array( sprintf( "'%s','%s'", $old_domain, trailingslashit( $old_path ) ), $old_replace_raw_values ) ) {
						$old_replace_raw_values[] = sprintf( "'%s','%s'", $old_domain, trailingslashit( $old_path ) );
						$new_replace_raw_values[] = sprintf( "'%s','%s'", $new_domain, trailingslashit( $new_path ) );
					}

					// Add domain and path with single quote
					if ( ! in_array( sprintf( "='%s%s", $old_domain, untrailingslashit( $old_path ) ), $old_replace_values ) ) {
						$old_replace_values[] = sprintf( "='%s%s", $old_domain, untrailingslashit( $old_path ) );
						$new_replace_values[] = sprintf( "='%s%s", $new_domain, untrailingslashit( $new_path ) );
					}

					// Add domain and path with double quote
					if ( ! in_array( sprintf( '="%s%s', $old_domain, untrailingslashit( $old_path ) ), $old_replace_values ) ) {
						$old_replace_values[] = sprintf( '="%s%s', $old_domain, untrailingslashit( $old_path ) );
						$new_replace_values[] = sprintf( '="%s%s', $new_domain, untrailingslashit( $new_path ) );
					}

					// Add Site URL scheme
					$old_schemes = array( 'http', 'https', '' );
					$new_schemes = array( $new_scheme, $new_scheme, '' );

					// Replace Site URL scheme
					for ( $i = 0; $i < count( $old_schemes ); $i++ ) {

						// Add plain Site URL
						if ( ! in_array( shm_url_scheme( $url, $old_schemes[ $i ] ), $old_replace_values ) ) {
							$old_replace_values[] = shm_url_scheme( $url, $old_schemes[ $i ] );
							$new_replace_values[] = shm_url_scheme( $blog['New']['SiteURL'], $new_schemes[ $i ] );
						}

						// Add URL encoded Site URL
						if ( ! in_array( urlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) ), $old_replace_values ) ) {
							$old_replace_values[] = urlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) );
							$new_replace_values[] = urlencode( shm_url_scheme( $blog['New']['SiteURL'], $new_schemes[ $i ] ) );
						}

						// Add URL raw encoded Site URL
						if ( ! in_array( rawurlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) ), $old_replace_values ) ) {
							$old_replace_values[] = rawurlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) );
							$new_replace_values[] = rawurlencode( shm_url_scheme( $blog['New']['SiteURL'], $new_schemes[ $i ] ) );
						}

						// Add JSON escaped Site URL
						if ( ! in_array( addcslashes( shm_url_scheme( $url, $old_schemes[ $i ] ), '/' ), $old_replace_values ) ) {
							$old_replace_values[] = addcslashes( shm_url_scheme( $url, $old_schemes[ $i ] ), '/' );
							$new_replace_values[] = addcslashes( shm_url_scheme( $blog['New']['SiteURL'], $new_schemes[ $i ] ), '/' );
						}
					}

					// Add email
					if ( ! isset( $config['NoEmailReplace'] ) ) {
						if ( ! in_array( sprintf( '@%s', $old_domain ), $old_replace_values ) ) {
							$old_replace_values[] = sprintf( '@%s', $old_domain );
							$new_replace_values[] = str_ireplace( '@www.', '@', sprintf( '@%s', $new_domain ) );
						}
					}
				}
			}

			$home_urls = array();

			// Add Home URL
			if ( ! empty( $blog['Old']['HomeURL'] ) ) {
				$home_urls[] = $blog['Old']['HomeURL'];
			}

			// Add Internal Home URL
			if ( ! empty( $blog['Old']['InternalHomeURL'] ) ) {
				if ( parse_url( $blog['Old']['InternalHomeURL'], PHP_URL_SCHEME ) && parse_url( $blog['Old']['InternalHomeURL'], PHP_URL_HOST ) ) {
					$home_urls[] = $blog['Old']['InternalHomeURL'];
				}
			}

			// Get Home URL
			foreach ( $home_urls as $home_url ) {

				// Get www URL
				if ( stripos( $home_url, '//www.' ) !== false ) {
					$home_url_www_inversion = str_ireplace( '//www.', '//', $home_url );
				} else {
					$home_url_www_inversion = str_ireplace( '//', '//www.', $home_url );
				}

				// Replace Home URL
				foreach ( array( $home_url, $home_url_www_inversion ) as $url ) {

					// Get domain
					$old_domain = parse_url( $url, PHP_URL_HOST );
					$new_domain = parse_url( $blog['New']['HomeURL'], PHP_URL_HOST );

					// Get path
					$old_path = (string) parse_url( $url, PHP_URL_PATH );
					$new_path = (string) parse_url( $blog['New']['HomeURL'], PHP_URL_PATH );

					// Get scheme
					$new_scheme = parse_url( $blog['New']['HomeURL'], PHP_URL_SCHEME );

					// Add domain and path
					if ( ! in_array( sprintf( "'%s','%s'", $old_domain, trailingslashit( $old_path ) ), $old_replace_raw_values ) ) {
						$old_replace_raw_values[] = sprintf( "'%s','%s'", $old_domain, trailingslashit( $old_path ) );
						$new_replace_raw_values[] = sprintf( "'%s','%s'", $new_domain, trailingslashit( $new_path ) );
					}

					// Add domain and path with single quote
					if ( ! in_array( sprintf( "='%s%s", $old_domain, untrailingslashit( $old_path ) ), $old_replace_values ) ) {
						$old_replace_values[] = sprintf( "='%s%s", $old_domain, untrailingslashit( $old_path ) );
						$new_replace_values[] = sprintf( "='%s%s", $new_domain, untrailingslashit( $new_path ) );
					}

					// Add domain and path with double quote
					if ( ! in_array( sprintf( '="%s%s', $old_domain, untrailingslashit( $old_path ) ), $old_replace_values ) ) {
						$old_replace_values[] = sprintf( '="%s%s', $old_domain, untrailingslashit( $old_path ) );
						$new_replace_values[] = sprintf( '="%s%s', $new_domain, untrailingslashit( $new_path ) );
					}

					// Set Home URL scheme
					$old_schemes = array( 'http', 'https', '' );
					$new_schemes = array( $new_scheme, $new_scheme, '' );

					// Replace Home URL scheme
					for ( $i = 0; $i < count( $old_schemes ); $i++ ) {

						// Add plain Home URL
						if ( ! in_array( shm_url_scheme( $url, $old_schemes[ $i ] ), $old_replace_values ) ) {
							$old_replace_values[] = shm_url_scheme( $url, $old_schemes[ $i ] );
							$new_replace_values[] = shm_url_scheme( $blog['New']['HomeURL'], $new_schemes[ $i ] );
						}

						// Add URL encoded Home URL
						if ( ! in_array( urlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) ), $old_replace_values ) ) {
							$old_replace_values[] = urlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) );
							$new_replace_values[] = urlencode( shm_url_scheme( $blog['New']['HomeURL'], $new_schemes[ $i ] ) );
						}

						// Add URL raw encoded Home URL
						if ( ! in_array( rawurlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) ), $old_replace_values ) ) {
							$old_replace_values[] = rawurlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) );
							$new_replace_values[] = rawurlencode( shm_url_scheme( $blog['New']['HomeURL'], $new_schemes[ $i ] ) );
						}

						// Add JSON escaped Home URL
						if ( ! in_array( addcslashes( shm_url_scheme( $url, $old_schemes[ $i ] ), '/' ), $old_replace_values ) ) {
							$old_replace_values[] = addcslashes( shm_url_scheme( $url, $old_schemes[ $i ] ), '/' );
							$new_replace_values[] = addcslashes( shm_url_scheme( $blog['New']['HomeURL'], $new_schemes[ $i ] ), '/' );
						}
					}

					// Add email
					if ( ! isset( $config['NoEmailReplace'] ) ) {
						if ( ! in_array( sprintf( '@%s', $old_domain ), $old_replace_values ) ) {
							$old_replace_values[] = sprintf( '@%s', $old_domain );
							$new_replace_values[] = str_ireplace( '@www.', '@', sprintf( '@%s', $new_domain ) );
						}
					}
				}
			}
		}

		$site_urls = array();

		// Add Site URL
		if ( ! empty( $config['SiteURL'] ) ) {
			$site_urls[] = $config['SiteURL'];
		}

		// Add Internal Site URL
		if ( ! empty( $config['InternalSiteURL'] ) ) {
			if ( parse_url( $config['InternalSiteURL'], PHP_URL_SCHEME ) && parse_url( $config['InternalSiteURL'], PHP_URL_HOST ) ) {
				$site_urls[] = $config['InternalSiteURL'];
			}
		}

		// Get Site URL
		foreach ( $site_urls as $site_url ) {

			// Get www URL
			if ( stripos( $site_url, '//www.' ) !== false ) {
				$site_url_www_inversion = str_ireplace( '//www.', '//', $site_url );
			} else {
				$site_url_www_inversion = str_ireplace( '//', '//www.', $site_url );
			}

			// Replace Site URL
			foreach ( array( $site_url, $site_url_www_inversion ) as $url ) {

				// Get domain
				$old_domain = parse_url( $url, PHP_URL_HOST );
				$new_domain = parse_url( site_url(), PHP_URL_HOST );

				// Get path
				$old_path = (string) parse_url( $url, PHP_URL_PATH );
				$new_path = (string) parse_url( site_url(), PHP_URL_PATH );

				// Get scheme
				$new_scheme = parse_url( site_url(), PHP_URL_SCHEME );

				// Add domain and path
				if ( ! in_array( sprintf( "'%s','%s'", $old_domain, trailingslashit( $old_path ) ), $old_replace_raw_values ) ) {
					$old_replace_raw_values[] = sprintf( "'%s','%s'", $old_domain, trailingslashit( $old_path ) );
					$new_replace_raw_values[] = sprintf( "'%s','%s'", $new_domain, trailingslashit( $new_path ) );
				}

				// Add domain and path with single quote
				if ( ! in_array( sprintf( "='%s%s", $old_domain, untrailingslashit( $old_path ) ), $old_replace_values ) ) {
					$old_replace_values[] = sprintf( "='%s%s", $old_domain, untrailingslashit( $old_path ) );
					$new_replace_values[] = sprintf( "='%s%s", $new_domain, untrailingslashit( $new_path ) );
				}

				// Add domain and path with double quote
				if ( ! in_array( sprintf( '="%s%s', $old_domain, untrailingslashit( $old_path ) ), $old_replace_values ) ) {
					$old_replace_values[] = sprintf( '="%s%s', $old_domain, untrailingslashit( $old_path ) );
					$new_replace_values[] = sprintf( '="%s%s', $new_domain, untrailingslashit( $new_path ) );
				}

				// Set Site URL scheme
				$old_schemes = array( 'http', 'https', '' );
				$new_schemes = array( $new_scheme, $new_scheme, '' );

				// Replace Site URL scheme
				for ( $i = 0; $i < count( $old_schemes ); $i++ ) {

					// Add plain Site URL
					if ( ! in_array( shm_url_scheme( $url, $old_schemes[ $i ] ), $old_replace_values ) ) {
						$old_replace_values[] = shm_url_scheme( $url, $old_schemes[ $i ] );
						$new_replace_values[] = shm_url_scheme( site_url(), $new_schemes[ $i ] );
					}

					// Add URL encoded Site URL
					if ( ! in_array( urlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) ), $old_replace_values ) ) {
						$old_replace_values[] = urlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) );
						$new_replace_values[] = urlencode( shm_url_scheme( site_url(), $new_schemes[ $i ] ) );
					}

					// Add URL raw encoded Site URL
					if ( ! in_array( rawurlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) ), $old_replace_values ) ) {
						$old_replace_values[] = rawurlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) );
						$new_replace_values[] = rawurlencode( shm_url_scheme( site_url(), $new_schemes[ $i ] ) );
					}

					// Add JSON escaped Site URL
					if ( ! in_array( addcslashes( shm_url_scheme( $url, $old_schemes[ $i ] ), '/' ), $old_replace_values ) ) {
						$old_replace_values[] = addcslashes( shm_url_scheme( $url, $old_schemes[ $i ] ), '/' );
						$new_replace_values[] = addcslashes( shm_url_scheme( site_url(), $new_schemes[ $i ] ), '/' );
					}
				}

				// Add email
				if ( ! isset( $config['NoEmailReplace'] ) ) {
					if ( ! in_array( sprintf( '@%s', $old_domain ), $old_replace_values ) ) {
						$old_replace_values[] = sprintf( '@%s', $old_domain );
						$new_replace_values[] = str_ireplace( '@www.', '@', sprintf( '@%s', $new_domain ) );
					}
				}
			}
		}

		$home_urls = array();

		// Add Home URL
		if ( ! empty( $config['HomeURL'] ) ) {
			$home_urls[] = $config['HomeURL'];
		}

		// Add Internal Home URL
		if ( ! empty( $config['InternalHomeURL'] ) ) {
			if ( parse_url( $config['InternalHomeURL'], PHP_URL_SCHEME ) && parse_url( $config['InternalHomeURL'], PHP_URL_HOST ) ) {
				$home_urls[] = $config['InternalHomeURL'];
			}
		}

		// Get Home URL
		foreach ( $home_urls as $home_url ) {

			// Get www URL
			if ( stripos( $home_url, '//www.' ) !== false ) {
				$home_url_www_inversion = str_ireplace( '//www.', '//', $home_url );
			} else {
				$home_url_www_inversion = str_ireplace( '//', '//www.', $home_url );
			}

			// Replace Home URL
			foreach ( array( $home_url, $home_url_www_inversion ) as $url ) {

				// Get domain
				$old_domain = parse_url( $url, PHP_URL_HOST );
				$new_domain = parse_url( home_url(), PHP_URL_HOST );

				// Get path
				$old_path = (string) parse_url( $url, PHP_URL_PATH );
				$new_path = (string) parse_url( home_url(), PHP_URL_PATH );

				// Get scheme
				$new_scheme = parse_url( home_url(), PHP_URL_SCHEME );

				// Add domain and path
				if ( ! in_array( sprintf( "'%s','%s'", $old_domain, trailingslashit( $old_path ) ), $old_replace_raw_values ) ) {
					$old_replace_raw_values[] = sprintf( "'%s','%s'", $old_domain, trailingslashit( $old_path ) );
					$new_replace_raw_values[] = sprintf( "'%s','%s'", $new_domain, trailingslashit( $new_path ) );
				}

				// Add domain and path with single quote
				if ( ! in_array( sprintf( "='%s%s", $old_domain, untrailingslashit( $old_path ) ), $old_replace_values ) ) {
					$old_replace_values[] = sprintf( "='%s%s", $old_domain, untrailingslashit( $old_path ) );
					$new_replace_values[] = sprintf( "='%s%s", $new_domain, untrailingslashit( $new_path ) );
				}

				// Add domain and path with double quote
				if ( ! in_array( sprintf( '="%s%s', $old_domain, untrailingslashit( $old_path ) ), $old_replace_values ) ) {
					$old_replace_values[] = sprintf( '="%s%s', $old_domain, untrailingslashit( $old_path ) );
					$new_replace_values[] = sprintf( '="%s%s', $new_domain, untrailingslashit( $new_path ) );
				}

				// Add Home URL scheme
				$old_schemes = array( 'http', 'https', '' );
				$new_schemes = array( $new_scheme, $new_scheme, '' );

				// Replace Home URL scheme
				for ( $i = 0; $i < count( $old_schemes ); $i++ ) {

					// Add plain Home URL
					if ( ! in_array( shm_url_scheme( $url, $old_schemes[ $i ] ), $old_replace_values ) ) {
						$old_replace_values[] = shm_url_scheme( $url, $old_schemes[ $i ] );
						$new_replace_values[] = shm_url_scheme( home_url(), $new_schemes[ $i ] );
					}

					// Add URL encoded Home URL
					if ( ! in_array( urlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) ), $old_replace_values ) ) {
						$old_replace_values[] = urlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) );
						$new_replace_values[] = urlencode( shm_url_scheme( home_url(), $new_schemes[ $i ] ) );
					}

					// Add URL raw encoded Home URL
					if ( ! in_array( rawurlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) ), $old_replace_values ) ) {
						$old_replace_values[] = rawurlencode( shm_url_scheme( $url, $old_schemes[ $i ] ) );
						$new_replace_values[] = rawurlencode( shm_url_scheme( home_url(), $new_schemes[ $i ] ) );
					}

					// Add JSON escaped Home URL
					if ( ! in_array( addcslashes( shm_url_scheme( $url, $old_schemes[ $i ] ), '/' ), $old_replace_values ) ) {
						$old_replace_values[] = addcslashes( shm_url_scheme( $url, $old_schemes[ $i ] ), '/' );
						$new_replace_values[] = addcslashes( shm_url_scheme( home_url(), $new_schemes[ $i ] ), '/' );
					}
				}

				// Add email
				if ( ! isset( $config['NoEmailReplace'] ) ) {
					if ( ! in_array( sprintf( '@%s', $old_domain ), $old_replace_values ) ) {
						$old_replace_values[] = sprintf( '@%s', $old_domain );
						$new_replace_values[] = str_ireplace( '@www.', '@', sprintf( '@%s', $new_domain ) );
					}
				}
			}
		}

		// Get WordPress Content Dir
		if ( isset( $config['WordPress']['Content'] ) && ( $content_dir = $config['WordPress']['Content'] ) ) {

			// Add plain WordPress Content
			if ( ! in_array( $content_dir, $old_replace_values ) ) {
				$old_replace_values[] = $content_dir;
				$new_replace_values[] = WP_CONTENT_DIR;
			}

			// Add URL encoded WordPress Content
			if ( ! in_array( urlencode( $content_dir ), $old_replace_values ) ) {
				$old_replace_values[] = urlencode( $content_dir );
				$new_replace_values[] = urlencode( WP_CONTENT_DIR );
			}

			// Add URL raw encoded WordPress Content
			if ( ! in_array( rawurlencode( $content_dir ), $old_replace_values ) ) {
				$old_replace_values[] = rawurlencode( $content_dir );
				$new_replace_values[] = rawurlencode( WP_CONTENT_DIR );
			}

			// Add JSON escaped WordPress Content
			if ( ! in_array( addcslashes( $content_dir, '/' ), $old_replace_values ) ) {
				$old_replace_values[] = addcslashes( $content_dir, '/' );
				$new_replace_values[] = addcslashes( WP_CONTENT_DIR, '/' );
			}
		}

		// Get replace old and new values
		if ( isset( $config['Replace'] ) && ( $replace = $config['Replace'] ) ) {
			for ( $i = 0; $i < count( $replace['OldValues'] ); $i++ ) {
				if ( ! empty( $replace['OldValues'][ $i ] ) && ! empty( $replace['NewValues'][ $i ] ) ) {

					// Add plain replace values
					if ( ! in_array( $replace['OldValues'][ $i ], $old_replace_values ) ) {
						$old_replace_values[] = $replace['OldValues'][ $i ];
						$new_replace_values[] = $replace['NewValues'][ $i ];
					}

					// Add URL encoded replace values
					if ( ! in_array( urlencode( $replace['OldValues'][ $i ] ), $old_replace_values ) ) {
						$old_replace_values[] = urlencode( $replace['OldValues'][ $i ] );
						$new_replace_values[] = urlencode( $replace['NewValues'][ $i ] );
					}

					// Add URL raw encoded replace values
					if ( ! in_array( rawurlencode( $replace['OldValues'][ $i ] ), $old_replace_values ) ) {
						$old_replace_values[] = rawurlencode( $replace['OldValues'][ $i ] );
						$new_replace_values[] = rawurlencode( $replace['NewValues'][ $i ] );
					}

					// Add JSON Escaped replace values
					if ( ! in_array( addcslashes( $replace['OldValues'][ $i ], '/' ), $old_replace_values ) ) {
						$old_replace_values[] = addcslashes( $replace['OldValues'][ $i ], '/' );
						$new_replace_values[] = addcslashes( $replace['NewValues'][ $i ], '/' );
					}
				}
			}
		}

		// Get site URL
		$site_url = get_option( SHM_SITE_URL );

		// Get home URL
		$home_url = get_option( SHM_HOME_URL );

		// Get secret key
		$secret_key = get_option( SHM_SECRET_KEY );

		// Get HTTP user
		$auth_user = get_option( SHM_AUTH_USER );

		// Get HTTP password
		$auth_password = get_option( SHM_AUTH_PASSWORD );

		// Get backups labels
		$backups_labels = get_option( SHM_BACKUPS_LABELS, array() );

		$old_table_prefixes = array();
		$new_table_prefixes = array();

		// Set main table prefixes
		$old_table_prefixes[] = shm_servmask_prefix( 'mainsite' );
		$new_table_prefixes[] = shm_table_prefix();

		// Set site table prefixes
		foreach ( $blogs as $blog ) {
			if ( shm_main_site( $blog['Old']['BlogID'] ) === false ) {
				$old_table_prefixes[] = shm_servmask_prefix( $blog['Old']['BlogID'] );
				$new_table_prefixes[] = shm_table_prefix( $blog['New']['BlogID'] );
			}
		}

		// Set base table prefixes
		foreach ( $blogs as $blog ) {
			if ( shm_main_site( $blog['Old']['BlogID'] ) === true ) {
				$old_table_prefixes[] = shm_servmask_prefix( 'basesite' );
				$new_table_prefixes[] = shm_table_prefix( $blog['New']['BlogID'] );
			}
		}

		// Set site table prefixes
		foreach ( $blogs as $blog ) {
			if ( shm_main_site( $blog['Old']['BlogID'] ) === true ) {
				$old_table_prefixes[] = shm_servmask_prefix( $blog['Old']['BlogID'] );
				$new_table_prefixes[] = shm_table_prefix( $blog['New']['BlogID'] );
			}
		}

		// Set table prefixes
		$old_table_prefixes[] = shm_servmask_prefix();
		$new_table_prefixes[] = shm_table_prefix();

		// Get database client
		if ( empty( $wpdb->use_mysqli ) ) {
			$mysql = new Shm_Database_Mysql( $wpdb );
		} else {
			$mysql = new Shm_Database_Mysqli( $wpdb );
		}

		// Set database options
		$mysql->set_old_table_prefixes( $old_table_prefixes )
			->set_new_table_prefixes( $new_table_prefixes )
			->set_old_replace_values( $old_replace_values )
			->set_new_replace_values( $new_replace_values )
			->set_old_replace_raw_values( $old_replace_raw_values )
			->set_new_replace_raw_values( $new_replace_raw_values );

		// Flush database
		if ( isset( $config['Plugin']['Version'] ) && ( $version = $config['Plugin']['Version'] ) ) {
			if ( $version !== 'develop' && version_compare( $version, '4.10', '<' ) ) {
				$mysql->set_include_table_prefixes( array( shm_table_prefix() ) );
				$mysql->flush();
			}
		}

		// Set atomic tables (do not stop the current request for all listed tables if timeout has been exceeded)
		$mysql->set_atomic_tables( array( shm_table_prefix() . 'options' ) );

		// Set Visual Composer
		$mysql->set_visual_composer( shm_validate_plugin_basename( 'js_composer/js_composer.php' ) );

		// Set Optimize Press
		$mysql->set_optimize_press( shm_validate_plugin_basename( 'optimizePressPlugin/optimizepress.php' ) );

		// Set BeTheme Responsive
		$mysql->set_betheme_responsive( shm_validate_theme_basename( 'betheme/style.css' ) );

		// Remember which plugins were active before the first database change (restored if the import fails)
		if ( $query_offset === 0 && ! isset( $params['shm_prev_plugins'] ) ) {
			$params['shm_prev_plugins'] = array_values( (array) get_option( SHM_ACTIVE_PLUGINS, array() ) );
		}

		$failed = false;
		try {
			// Dry-run the whole dump on this server first: if anything cannot work, say so while the site is still untouched.
			// Advisory only: a probe failure (a strict max_allowed_packet, an engine/row-format the probe mis-detects, or a
			// DB user without CREATE/DROP rights on the throw-away probe table) must NOT abort a restore that would otherwise
			// succeed. We log it and let the real import run - which is how All-in-One WP Migration behaves (it has no preflight).
			if ( $query_offset === 0 ) {
				Shm_Status::info( __( 'Checking that your database server can restore this backup...', SHM_PLUGIN_NAME ) );
				try {
					$mysql->preflight( shm_database_path( $params ) );
				} catch ( Throwable $e ) {
					Shm_Log::error( array( 'preflight_warning' => $e->getMessage() ) );
				}
			}

			// Import database
			if ( $mysql->import( shm_database_path( $params ), $query_offset ) ) {

				// Set progress
				Shm_Status::info( __( 'Done restoring database.', SHM_PLUGIN_NAME ) );

				// Unset query offset
				unset( $params['query_offset'] );

				// Unset total queries size
				unset( $params['total_queries_size'] );

				// Unset completed flag
				unset( $params['completed'] );

				// The saved plugin list is only needed to recover from a failure
				unset( $params['shm_prev_plugins'] );

			} else {

				// Get total queries size
				$total_queries_size = shm_database_bytes( $params );

				// What percent of queries have we processed?
				$progress = (int) ( ( $query_offset / $total_queries_size ) * 100 );

				// Set progress
				Shm_Status::info( sprintf( __( 'Restoring database...<br />%d%% complete', SHM_PLUGIN_NAME ), $progress ) );

				// Set query offset
				$params['query_offset'] = $query_offset;

				// Set total queries size
				$params['total_queries_size'] = $total_queries_size;

				// Set completed flag
				$params['completed'] = false;
			}
		} catch ( Throwable $e ) {
			$failed = true;
			throw $e;
		} finally {
			// Success or failure: the migrator must come back alive with its own settings, otherwise the
			// browser can never even show what happened (no secret key, plugin deactivated by the imported options)
			shm_cache_flush();

			// Activate plugins
			shm_activate_plugins( shm_active_servmask_plugins() );

			// Set the new site URL
			update_option( SHM_SITE_URL, $site_url );

			// Set the new home URL
			update_option( SHM_HOME_URL, $home_url );

			// Set the new secret key value
			update_option( SHM_SECRET_KEY, $secret_key );

			// Set the new HTTP user
			update_option( SHM_AUTH_USER, $auth_user );

			// Set the new HTTP password
			update_option( SHM_AUTH_PASSWORD, $auth_password );

			// Set the new backups labels
			update_option( SHM_BACKUPS_LABELS, $backups_labels );

			// A failed import must not leave the site without the plugins it had
			if ( $failed && ! empty( $params['shm_prev_plugins'] ) ) {
				shm_activate_plugins( $params['shm_prev_plugins'] );
			}
		}

		return $params;
	}
}
