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

/**
 * Get storage absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_storage_path( $params ) {
	if ( empty( $params['storage'] ) ) {
		throw new Shm_Storage_Exception( __( 'Unable to locate storage path.', SHM_PLUGIN_NAME ) );
	}

	// Get storage path
	$storage = SHM_STORAGE_PATH . DIRECTORY_SEPARATOR . basename( $params['storage'] );
	if ( ! is_dir( $storage ) ) {
		Shm_Directory::create( $storage );
	}

	return $storage;
}

/**
 * Get backup absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_backup_path( $params ) {
	if ( empty( $params['archive'] ) ) {
		throw new Shm_Archive_Exception( __( 'Unable to locate archive path.', SHM_PLUGIN_NAME ) );
	}

	// Validate archive path
	if ( validate_file( $params['archive'] ) !== 0 ) {
		throw new Shm_Archive_Exception( __( 'Invalid archive path.', SHM_PLUGIN_NAME ) );
	}

	return SHM_BACKUPS_PATH . DIRECTORY_SEPARATOR . $params['archive'];
}

/**
 * Get archive absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_archive_path( $params ) {
	if ( empty( $params['archive'] ) ) {
		throw new Shm_Archive_Exception( __( 'Unable to locate archive path.', SHM_PLUGIN_NAME ) );
	}

	// Validate archive path
	if ( validate_file( $params['archive'] ) !== 0 ) {
		throw new Shm_Archive_Exception( __( 'Invalid archive path.', SHM_PLUGIN_NAME ) );
	}

	// Get archive path
	if ( empty( $params['shm_manual_restore'] ) ) {
		return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . $params['archive'];
	}

	return shm_backup_path( $params );
}

/**
 * Get export log absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_export_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_EXPORT_NAME;
}

/**
 * Get import log absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_import_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_IMPORT_NAME;
}

/**
 * Get multipart.list absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_multipart_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_MULTIPART_NAME;
}

/**
 * Get filemap.list absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_filemap_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_FILEMAP_NAME;
}

/**
 * Get package.json absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_package_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_PACKAGE_NAME;
}

/**
 * Get multisite.json absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_multisite_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_MULTISITE_NAME;
}

/**
 * Get blogs.json absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_blogs_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_BLOGS_NAME;
}

/**
 * Get settings.json absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_settings_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_SETTINGS_NAME;
}

/**
 * Get database.sql absolute path
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_database_path( $params ) {
	return shm_storage_path( $params ) . DIRECTORY_SEPARATOR . SHM_DATABASE_NAME;
}

/**
 * Get error log absolute path
 *
 * @return string
 */
function shm_error_path() {
	return SHM_STORAGE_PATH . DIRECTORY_SEPARATOR . SHM_ERROR_NAME;
}

/**
 * Get WordPress content directory
 *
 * @param  string $path Relative path
 * @return string
 */
function shm_content_path( $path = null ) {
	if ( empty( $path ) ) {
		return WP_CONTENT_DIR;
	}

	return WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $path;
}

/**
 * Get archive name
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_archive_name( $params ) {
	return basename( $params['archive'] );
}

/**
 * Get backup URL address
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_backup_url( $params ) {
	return SHM_BACKUPS_URL . '/' . str_replace( DIRECTORY_SEPARATOR, '/', $params['archive'] );
}

/**
 * Get archive size in bytes
 *
 * @param  array   $params Request parameters
 * @return integer
 */
function shm_archive_bytes( $params ) {
	return filesize( shm_archive_path( $params ) );
}

/**
 * Get backup size in bytes
 *
 * @param  array   $params Request parameters
 * @return integer
 */
function shm_backup_bytes( $params ) {
	return filesize( shm_backup_path( $params ) );
}

/**
 * Get database size in bytes
 *
 * @param  array   $params Request parameters
 * @return integer
 */
function shm_database_bytes( $params ) {
	return filesize( shm_database_path( $params ) );
}

/**
 * Get package size in bytes
 *
 * @param  array   $params Request parameters
 * @return integer
 */
function shm_package_bytes( $params ) {
	return filesize( shm_package_path( $params ) );
}

/**
 * Get multisite size in bytes
 *
 * @param  array   $params Request parameters
 * @return integer
 */
function shm_multisite_bytes( $params ) {
	return filesize( shm_multisite_path( $params ) );
}

/**
 * Get archive size as text
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_archive_size( $params ) {
	return shm_size_format( filesize( shm_archive_path( $params ) ) );
}

/**
 * Get backup size as text
 *
 * @param  array  $params Request parameters
 * @return string
 */
function shm_backup_size( $params ) {
	return shm_size_format( filesize( shm_backup_path( $params ) ) );
}

/**
 * Parse file size
 *
 * @param  string $size    File size
 * @param  string $default Default size
 * @return string
 */
function shm_parse_size( $size, $default = null ) {
	$suffixes = array(
		''  => 1,
		'k' => 1024,
		'm' => 1048576,
		'g' => 1073741824,
	);

	// Parse size format (same units as php.ini shorthand)
	if ( preg_match( '/^\s*([0-9]+)\s*([kmg])?/i', (string) $size, $match ) ) {
		return (int) $match[1] * $suffixes[ strtolower( isset( $match[2] ) ? $match[2] : '' ) ];
	}

	return $default;
}

/**
 * Format file size into human-readable string
 *
 * Fixes the WP size_format bug: size_format( '0' ) => false
 *
 * @param  int|string   $bytes            Number of bytes. Note max integer size for integers.
 * @param  int          $decimals         Optional. Precision of number of decimal places. Default 0.
 * @return string|false False on failure. Number string on success.
 */
function shm_size_format( $bytes, $decimals = 0 ) {
	if ( strval( $bytes ) === '0' ) {
		return size_format( 0, $decimals );
	}

	return size_format( $bytes, $decimals );
}

/**
 * Get current site name
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_site_name( $blog_id = null ) {
	return parse_url( get_site_url( $blog_id ), PHP_URL_HOST );
}

/**
 * Get archive file name
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_archive_file( $blog_id = null ) {
	$name = array();

	// Add domain
	$name[] = parse_url( get_site_url( $blog_id ), PHP_URL_HOST );

	// Add path
	if ( ( $path = explode( '/', (string) parse_url( get_site_url( $blog_id ), PHP_URL_PATH ) ) ) ) {
		foreach ( $path as $directory ) {
			if ( $directory ) {
				$name[] = $directory;
			}
		}
	}

	// Add year, month and day
	$name[] = date( 'Ymd' );

	// Add hours, minutes and seconds
	$name[] = date( 'His' );

	// Add unique identifier
	$name[] = bin2hex( random_bytes( 8 ) );

	return sprintf( '%s.wpress', strtolower( implode( '-', $name ) ) );
}

/**
 * Get archive folder name
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_archive_folder( $blog_id = null ) {
	$name = array();

	// Add domain
	$name[] = parse_url( get_site_url( $blog_id ), PHP_URL_HOST );

	// Add path
	if ( ( $path = explode( '/', (string) parse_url( get_site_url( $blog_id ), PHP_URL_PATH ) ) ) ) {
		foreach ( $path as $directory ) {
			if ( $directory ) {
				$name[] = $directory;
			}
		}
	}

	return strtolower( implode( '-', $name ) );
}

/**
 * Get archive bucket name
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_archive_bucket( $blog_id = null ) {
	$name = array();

	// Add domain
	if ( ( $domain = explode( '.', parse_url( get_site_url( $blog_id ), PHP_URL_HOST ) ) ) ) {
		foreach ( $domain as $subdomain ) {
			if ( $subdomain = strtolower( preg_replace( '/[^A-Za-z0-9\-]/', '', $subdomain ) ) ) {
				$name[] = $subdomain;
			}
		}
	}

	// Add path
	if ( ( $path = explode( '/', (string) parse_url( get_site_url( $blog_id ), PHP_URL_PATH ) ) ) ) {
		foreach ( $path as $directory ) {
			if ( $directory = strtolower( preg_replace( '/[^A-Za-z0-9\-]/', '', $directory ) ) ) {
				$name[] = $directory;
			}
		}
	}

	return strtolower( implode( '-', $name ) );
}

/**
 * Get archive vault name
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_archive_vault( $blog_id = null ) {
	$name = array();

	// Add domain
	if ( ( $domain = explode( '.', parse_url( get_site_url( $blog_id ), PHP_URL_HOST ) ) ) ) {
		foreach ( $domain as $subdomain ) {
			if ( $subdomain = strtolower( preg_replace( '/[^A-Za-z0-9\-]/', '', $subdomain ) ) ) {
				$name[] = $subdomain;
			}
		}
	}

	// Add path
	if ( ( $path = explode( '/', (string) parse_url( get_site_url( $blog_id ), PHP_URL_PATH ) ) ) ) {
		foreach ( $path as $directory ) {
			if ( $directory = strtolower( preg_replace( '/[^A-Za-z0-9\-]/', '', $directory ) ) ) {
				$name[] = $directory;
			}
		}
	}

	return strtolower( implode( '-', $name ) );
}

/**
 * Get archive project name
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_archive_project( $blog_id = null ) {
	$name = array();

	// Add domain
	if ( ( $domain = explode( '.', parse_url( get_site_url( $blog_id ), PHP_URL_HOST ) ) ) ) {
		foreach ( $domain as $subdomain ) {
			if ( $subdomain ) {
				$name[] = $subdomain;
			}
		}
	}

	// Add path
	if ( ( $path = explode( '/', (string) parse_url( get_site_url( $blog_id ), PHP_URL_PATH ) ) ) ) {
		foreach ( $path as $directory ) {
			if ( $directory ) {
				$name[] = $directory;
			}
		}
	}

	return strtolower( implode( '-', $name ) );
}

/**
 * Get archive share name
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_archive_share( $blog_id = null ) {
	$name = array();

	// Add domain
	if ( ( $domain = explode( '.', parse_url( get_site_url( $blog_id ), PHP_URL_HOST ) ) ) ) {
		foreach ( $domain as $subdomain ) {
			if ( $subdomain = strtolower( preg_replace( '/[^A-Za-z0-9\-]/', '', $subdomain ) ) ) {
				$name[] = $subdomain;
			}
		}
	}

	// Add path
	if ( ( $path = explode( '/', (string) parse_url( get_site_url( $blog_id ), PHP_URL_PATH ) ) ) ) {
		foreach ( $path as $directory ) {
			if ( $directory = strtolower( preg_replace( '/[^A-Za-z0-9\-]/', '', $directory ) ) ) {
				$name[] = $directory;
			}
		}
	}

	return strtolower( implode( '-', $name ) );
}

/**
 * Get storage folder name
 *
 * @return string
 */
function shm_storage_folder() {
	// not uniqid(): that is only microsecond-resolution, so two parallel jobs could share (and clean up) one folder
	return bin2hex( random_bytes( 8 ) );
}

/**
 * Check whether blog ID is main site
 *
 * @param  integer $blog_id Blog ID
 * @return boolean
 */
function shm_main_site( $blog_id = null ) {
	return $blog_id === null || $blog_id === 0 || $blog_id === 1;
}

/**
 * Get sites absolute path by blog ID
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_sites_path( $blog_id = null ) {
	if ( shm_main_site( $blog_id ) ) {
		return SHM_UPLOADS_PATH;
	}

	return SHM_SITES_PATH . DIRECTORY_SEPARATOR . $blog_id;
}

/**
 * Get files absolute path by blog ID
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_files_path( $blog_id = null ) {
	if ( shm_main_site( $blog_id ) ) {
		return SHM_UPLOADS_PATH;
	}

	return SHM_BLOGSDIR_PATH . DIRECTORY_SEPARATOR . $blog_id . DIRECTORY_SEPARATOR . 'files';
}

/**
 * Get blogs.dir absolute path by blog ID
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_blogsdir_path( $blog_id = null ) {
	if ( shm_main_site( $blog_id ) ) {
		return '/wp-content/uploads/';
	}

	return "/wp-content/blogs.dir/{$blog_id}/files/";
}

/**
 * Get blogs.dir URL by blog ID
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_blogsdir_url( $blog_id = null ) {
	if ( shm_main_site( $blog_id ) ) {
		return get_site_url( $blog_id, '/wp-content/uploads/' );
	}

	return get_site_url( $blog_id, "/wp-content/blogs.dir/{$blog_id}/files/" );
}

/**
 * Get uploads absolute path by blog ID
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_uploads_path( $blog_id = null ) {
	if ( shm_main_site( $blog_id ) ) {
		return '/wp-content/uploads/';
	}

	return "/wp-content/uploads/sites/{$blog_id}/";
}

/**
 * Get uploads URL by blog ID
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_uploads_url( $blog_id = null ) {
	if ( shm_main_site( $blog_id ) ) {
		return get_site_url( $blog_id, '/wp-content/uploads/' );
	}

	return get_site_url( $blog_id, "/wp-content/uploads/sites/{$blog_id}/" );
}

/**
 * Get ServMask table prefix by blog ID
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_servmask_prefix( $blog_id = null ) {
	// Set base table prefix
	if ( shm_main_site( $blog_id ) ) {
		return SHM_TABLE_PREFIX;
	}

	return SHM_TABLE_PREFIX . $blog_id . '_';
}

/**
 * Get WordPress table prefix by blog ID
 *
 * @param  integer $blog_id Blog ID
 * @return string
 */
function shm_table_prefix( $blog_id = null ) {
	global $wpdb;

	// Set base table prefix
	if ( shm_main_site( $blog_id ) ) {
		return $wpdb->base_prefix;
	}

	return $wpdb->base_prefix . $blog_id . '_';
}

/**
 * Get default content filters
 *
 * @param  array $filters List of files and directories
 * @return array
 */
function shm_content_filters( $filters = array() ) {
	return array_merge( $filters, array(
		SHM_BACKUPS_NAME,
		SHM_STORAGE_NAME,
		SHM_PACKAGE_NAME,
		SHM_MULTISITE_NAME,
		SHM_DATABASE_NAME,
	) );
}

/**
 * Get default plugin filters
 *
 * @param  array $filters List of plugins
 * @return array
 */
function shm_plugin_filters( $filters = array() ) {
	// This plugin must never travel inside an archive
	if ( defined( 'SHM_PLUGIN_BASENAME' ) ) {
		$filters[] = 'plugins' . DIRECTORY_SEPARATOR . dirname( SHM_PLUGIN_BASENAME );
	} else {
		$filters[] = 'plugins' . DIRECTORY_SEPARATOR . 'sharad-site-migrator';
	}

	return $filters;
}

/**
 * Get the plugins that must stay active after an import
 *
 * @return array
 */
function shm_active_servmask_plugins( $plugins = array() ) {
	// Kept active across an import so the migrator survives its own restore
	if ( defined( 'SHM_PLUGIN_BASENAME' ) ) {
		$plugins[] = SHM_PLUGIN_BASENAME;
	}

	return $plugins;
}

/**
 * Get active sitewide plugins
 *
 * @return array
 */
function shm_active_sitewide_plugins() {
	return array_keys( get_site_option( SHM_ACTIVE_SITEWIDE_PLUGINS, array() ) );
}

/**
 * Get active plugins
 *
 * @return array
 */
function shm_active_plugins() {
	return array_values( get_option( SHM_ACTIVE_PLUGINS, array() ) );
}

/**
 * Set active sitewide plugins (inspired by WordPress activate_plugins() function)
 *
 * @param  array   $plugins List of plugins
 * @return boolean
 */
function shm_activate_sitewide_plugins( $plugins ) {
	$current = get_site_option( SHM_ACTIVE_SITEWIDE_PLUGINS, array() );

	// Add plugins
	foreach ( $plugins as $plugin ) {
		if ( ! isset( $current[ $plugin ] ) && ! is_wp_error( validate_plugin( $plugin ) ) ) {
			$current[ $plugin ] = time();
		}
	}

	return update_site_option( SHM_ACTIVE_SITEWIDE_PLUGINS, $current );
}

/**
 * Set active plugins (inspired by WordPress activate_plugins() function)
 *
 * @param  array   $plugins List of plugins
 * @return boolean
 */
function shm_activate_plugins( $plugins ) {
	$current = get_option( SHM_ACTIVE_PLUGINS, array() );

	// Add plugins
	foreach ( $plugins as $plugin ) {
		if ( ! in_array( $plugin, $current ) && ! is_wp_error( validate_plugin( $plugin ) ) ) {
			$current[] = $plugin;
		}
	}

	sort( $current );

	return update_option( SHM_ACTIVE_PLUGINS, $current );
}

/**
 * Get active template
 *
 * @return string
 */
function shm_active_template() {
	return get_option( SHM_ACTIVE_TEMPLATE );
}

/**
 * Get active stylesheet
 *
 * @return string
 */
function shm_active_stylesheet() {
	return get_option( SHM_ACTIVE_STYLESHEET );
}

/**
 * Set active template
 *
 * @param  string  $template Template name
 * @return boolean
 */
function shm_activate_template( $template ) {
	return update_option( SHM_ACTIVE_TEMPLATE, $template );
}

/**
 * Set active stylesheet
 *
 * @param  string  $stylesheet Stylesheet name
 * @return boolean
 */
function shm_activate_stylesheet( $stylesheet ) {
	return update_option( SHM_ACTIVE_STYLESHEET, $stylesheet );
}

/**
 * Set inactive sitewide plugins (inspired by WordPress deactivate_plugins() function)
 *
 * @param  array   $plugins List of plugins
 * @return boolean
 */
function shm_deactivate_sitewide_plugins( $plugins ) {
	$current = get_site_option( SHM_ACTIVE_SITEWIDE_PLUGINS, array() );

	// Add plugins
	foreach ( $plugins as $plugin ) {
		if ( isset( $current[ $plugin ] ) ) {
			unset( $current[ $plugin ] );
		}
	}

	return update_site_option( SHM_ACTIVE_SITEWIDE_PLUGINS, $current );
}


/**
 * Set inactive plugins (inspired by WordPress deactivate_plugins() function)
 *
 * @param  array   $plugins List of plugins
 * @return boolean
 */
function shm_deactivate_plugins( $plugins ) {
	$current = get_option( SHM_ACTIVE_PLUGINS, array() );

	// Remove plugins
	foreach ( $plugins as $plugin ) {
		if ( ( $key = array_search( $plugin, $current ) ) !== false ) {
			unset( $current[ $key ] );
		}
	}

	sort( $current );

	return update_option( SHM_ACTIVE_PLUGINS, $current );
}

/**
 * Deactivate Jetpack modules
 *
 * @param  array   $modules List of modules
 * @return boolean
 */
function shm_deactivate_jetpack_modules( $modules ) {
	$current = get_option( SHM_JETPACK_ACTIVE_MODULES, array() );

	// Remove modules
	foreach ( $modules as $module ) {
		if ( ( $key = array_search( $module, $current ) ) !== false ) {
			unset( $current[ $key ] );
		}
	}

	sort( $current );

	return update_option( SHM_JETPACK_ACTIVE_MODULES, $current );
}

/**
 * Discover plugin basename
 *
 * @param  string $basename Plugin basename
 * @return string
 */
function shm_discover_plugin_basename( $basename ) {
	if ( ( $plugins = get_plugins() ) ) {
		foreach ( $plugins as $plugin => $info ) {
			if ( strpos( dirname( $plugin ), dirname( $basename ) ) !== false ) {
				if ( basename( $plugin ) === basename( $basename ) ) {
					return $plugin;
				}
			}
		}
	}

	return $basename;
}

/**
 * Validate plugin basename
 *
 * @param  string  $basename Plugin basename
 * @return boolean
 */
function shm_validate_plugin_basename( $basename ) {
	if ( ( $plugins = get_plugins() ) ) {
		foreach ( $plugins as $plugin => $info ) {
			if ( $plugin === $basename ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Validate theme basename
 *
 * @param  string  $basename Theme basename
 * @return boolean
 */
function shm_validate_theme_basename( $basename ) {
	if ( ( $themes = search_theme_directories() ) ) {
		foreach ( $themes as $theme => $info ) {
			if ( $info['theme_file'] === $basename ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Flush WP options cache
 *
 * @return void
 */
function shm_cache_flush() {
	// Initialize WP cache
	wp_cache_init();

	// Flush WP cache
	wp_cache_flush();

	// Set WP cache
	wp_cache_set( 'alloptions', array(), 'options' );
	wp_cache_set( 'notoptions', array(), 'options' );

	// Delete WP cache
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );

	// Remove WP filters
	remove_all_filters( 'sanitize_option_siteurl' );
	remove_all_filters( 'sanitize_option_home' );
}

/**
 * Set URL scheme
 *
 * @param  string $url    URL value
 * @param  string $scheme URL scheme
 * @return string
 */
function shm_url_scheme( $url, $scheme = '' ) {
	if ( empty( $scheme ) ) {
		return preg_replace( '#^\w+://#', '//', $url );
	}

	return preg_replace( '#^\w+://#', $scheme . '://', $url );
}

/**
 * Opens a file in specified mode
 *
 * @param  string   $file Path to the file to open
 * @param  string   $mode Mode in which to open the file
 * @return resource
 * @throws Shm_Not_Accessible_Exception
 */
function shm_open( $file, $mode ) {
	$file_handle = @fopen( $file, $mode );
	if ( false === $file_handle ) {
		throw new Shm_Not_Accessible_Exception( sprintf( __( 'Unable to open %s with mode %s.', SHM_PLUGIN_NAME ), $file, $mode ) );
	}

	return $file_handle;
}

/**
 * Write contents to a file
 *
 * @param  resource $handle  File handle to write to
 * @param  string   $content Contents to write to the file
 * @return integer
 * @throws Shm_Not_Writable_Exception
 * @throws Shm_Quota_Exceeded_Exception
 */
function shm_write( $handle, $content ) {
	$write_result = @fwrite( $handle, $content );
	if ( false === $write_result ) {
		if ( ( $meta = stream_get_meta_data( $handle ) ) ) {
			throw new Shm_Not_Writable_Exception( sprintf( __( 'Unable to write to: %s.', SHM_PLUGIN_NAME ), $meta['uri'] ) );
		}
	} elseif ( strlen( $content ) !== $write_result ) {
		if ( ( $meta = stream_get_meta_data( $handle ) ) ) {
			throw new Shm_Quota_Exceeded_Exception( sprintf( __( 'Out of disk space. Unable to write to: %s.', SHM_PLUGIN_NAME ), $meta['uri'] ) );
		}
	}

	return $write_result;
}

/**
 * Read contents from a file
 *
 * @param  resource $handle   File handle to read from
 * @param  string   $filesize File size
 * @return integer
 * @throws Shm_Not_Readable_Exception
 */
function shm_read( $handle, $filesize ) {
	$read_result = @fread( $handle, $filesize );
	if ( false === $read_result ) {
		if ( ( $meta = stream_get_meta_data( $handle ) ) ) {
			throw new Shm_Not_Readable_Exception( sprintf( __( 'Unable to read file: %s.', SHM_PLUGIN_NAME ), $meta['uri'] ) );
		}
	}

	return $read_result;
}

/**
 * Seeks on a file pointer
 *
 * @param  string  $handle File handle to seeks
 * @return integer
 */
function shm_seek( $handle, $offset, $mode = SEEK_SET ) {
	$seek_result = @fseek( $handle, $offset, $mode );
	if ( -1 === $seek_result ) {
		if ( ( $meta = stream_get_meta_data( $handle ) ) ) {
			throw new Shm_Not_Seekable_Exception( sprintf( __( 'Unable to seek to offset %d on %s.', SHM_PLUGIN_NAME ), $offset, $meta['uri'] ) );
		}
	}

	return $seek_result;
}

/**
 * Tells on a file pointer
 *
 * @param  string  $handle File handle to tells
 * @return integer
 */
function shm_tell( $handle ) {
	$tell_result = @ftell( $handle );
	if ( false === $tell_result ) {
		if ( ( $meta = stream_get_meta_data( $handle ) ) ) {
			throw new Shm_Not_Tellable_Exception( sprintf( __( 'Unable to get current pointer position of %s.', SHM_PLUGIN_NAME ), $meta['uri'] ) );
		}
	}

	return $tell_result;
}

/**
 * Closes a file handle
 *
 * @param  resource $handle File handle to close
 * @return boolean
 */
function shm_close( $handle ) {
	return @fclose( $handle );
}

/**
 * Deletes a file
 *
 * @param  string  $file Path to file to delete
 * @return boolean
 */
function shm_unlink( $file ) {
	return @unlink( $file );
}

/**
 * Sets modification time of a file
 *
 * @param  string  $file Path to file to change modification time
 * @param  integer $time File modification time
 * @return boolean
 */
function shm_touch( $file, $mtime ) {
	return @touch( $file, $mtime );
}

/**
 * Changes file mode
 *
 * @param  string  $file Path to file to change mode
 * @param  integer $time File mode
 * @return boolean
 */
function shm_chmod( $file, $mode ) {
	return @chmod( $file, $mode );
}

/**
 * Copies one file's contents to another
 *
 * @param  string $source_file      File to copy the contents from
 * @param  string $destination_file File to copy the contents to
 */
function shm_copy( $source_file, $destination_file ) {
	$source_handle      = shm_open( $source_file, 'rb' );
	$destination_handle = shm_open( $destination_file, 'ab' );
	while ( $buffer = shm_read( $source_handle, 4096 ) ) {
		shm_write( $destination_handle, $buffer );
	}
	shm_close( $source_handle );
	shm_close( $destination_handle );
}

/**
 * Check whether file size is supported by current PHP version
 *
 * @param  string  $file         Path to file
 * @param  integer $php_int_size Size of PHP integer
 * @return boolean $php_int_max  Max value of PHP integer
 */
function shm_is_filesize_supported( $file, $php_int_size = PHP_INT_SIZE, $php_int_max = PHP_INT_MAX ) {
	$size_result = true;

	// Check whether file size is less than 2GB in PHP 32bits
	if ( $php_int_size === 4 ) {
		if ( ( $file_handle = @fopen( $file, 'r' ) ) ) {
			if ( @fseek( $file_handle, $php_int_max, SEEK_SET ) !== -1 ) {
				if ( @fgetc( $file_handle ) !== false ) {
					$size_result = false;
				}
			}

			@fclose( $file_handle );
		}
	}

	return $size_result;
}

/**
 * Verify secret key
 *
 * @param  string  $secret_key Secret key
 * @return boolean
 * @throws Shm_Not_Valid_Secret_Key_Exception
 */
function shm_verify_secret_key( $secret_key ) {
	$stored = get_option( SHM_SECRET_KEY );
	if ( ! is_string( $secret_key ) || ! is_string( $stored ) || $stored === '' || ! hash_equals( $stored, $secret_key ) ) {
		throw new Shm_Not_Valid_Secret_Key_Exception( __( 'Unable to authenticate the secret key.', SHM_PLUGIN_NAME ) );
	}

	return true;
}

/**
 * Generate a new secret key
 *
 * @return string
 */
function shm_generate_secret_key() {
	return wp_generate_password( 32, false );
}

/**
 * Is scheduled backup?
 *
 * @return boolean
 */
function shm_is_scheduled_backup() {
	if ( isset( $_GET['shm_manual_export'] ) || isset( $_POST['shm_manual_export'] ) ) {
		return false;
	}

	if ( isset( $_GET['shm_manual_import'] ) || isset( $_POST['shm_manual_import'] ) ) {
		return false;
	}

	if ( isset( $_GET['shm_manual_restore'] ) || isset( $_POST['shm_manual_restore'] ) ) {
		return false;
	}

	return true;
}

/**
 * PHP setup environment
 *
 * @return void
 */
function shm_setup_environment() {
	// Set whether a client disconnect should abort script execution
	@ignore_user_abort( true );

	// Set maximum execution time
	@set_time_limit( 0 );

	// Set maximum time in seconds a script is allowed to parse input data
	@ini_set( 'max_input_time', '-1' );

	// Set maximum backtracking steps
	@ini_set( 'pcre.backtrack_limit', PHP_INT_MAX );

	// Set binary safe encoding
	if ( @function_exists( 'mb_internal_encoding' ) && ( @ini_get( 'mbstring.func_overload' ) & 2 ) ) {
		@mb_internal_encoding( 'ISO-8859-1' );
	}

	// Clean (erase) the output buffer and turn off output buffering
	if ( @ob_get_length() ) {
		@ob_end_clean();
	}

	// Set error handler
	@set_error_handler( 'Shm_Handler::error' );

	// Set shutdown handler
	@register_shutdown_function( 'Shm_Handler::shutdown' );
}

/**
 * Get WordPress time zone string
 *
 * @return string
 */
function shm_get_timezone_string() {
	if ( ( $timezone_string = get_option( 'timezone_string' ) ) ) {
		return $timezone_string;
	}

	if ( ( $gmt_offset = get_option( 'gmt_offset' ) ) ) {
		if ( $gmt_offset > 0 ) {
			return sprintf( 'UTC+%s', abs( $gmt_offset ) );
		} elseif ( $gmt_offset < 0 ) {
			return sprintf( 'UTC-%s', abs( $gmt_offset ) );
		}
	}

	return 'UTC';
}

/**
 * Get WordPress filter hooks
 *
 * @param  string $tag The name of the filter hook
 * @return array
 */
function shm_get_filters( $tag ) {
	global $wp_filter;

	// Get WordPress filter hooks
	$filters = array();
	if ( isset( $wp_filter[ $tag ] ) ) {
		if ( ( $filters = $wp_filter[ $tag ] ) ) {
			// WordPress 4.7 introduces new class for working with filters/actions called WP_Hook
			// which adds another level of abstraction and we need to address it.
			if ( isset( $filters->callbacks ) ) {
				$filters = $filters->callbacks;
			}
		}

		ksort( $filters );
	}

	return $filters;
}

/**
 * i18n friendly version of basename()
 *
 * @param  string $path   File path
 * @param  string $suffix If the filename ends in suffix this will also be cut off
 * @return string
 */
function shm_basename( $path, $suffix = '' ) {
	return urldecode( basename( str_replace( array( '%2F', '%5C' ), '/', urlencode( $path ) ), $suffix ) );
}

/**
 * i18n friendly version of dirname()
 *
 * @param  string $path File path
 * @return string
 */
function shm_dirname( $path ) {
	return urldecode( dirname( str_replace( array( '%2F', '%5C' ), '/', urlencode( $path ) ) ) );
}
