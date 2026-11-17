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

class Shm_Main_Controller {

	/**
	 * Main Application Controller
	 *
	 * @return Shm_Main_Controller
	 */
	public function __construct() {
		register_activation_hook( SHM_PLUGIN_BASENAME, array( $this, 'activation_hook' ) );

		// Activate hooks
		$this->activate_actions();
		$this->activate_filters();
	}

	/**
	 * Activation hook callback
	 *
	 * @return void
	 */
	public function activation_hook() {
		if ( is_dir( SHM_BACKUPS_PATH ) ) {
			$this->create_backups_htaccess( SHM_BACKUPS_HTACCESS );
			$this->create_backups_webconfig( SHM_BACKUPS_WEBCONFIG );
			$this->create_backups_index( SHM_BACKUPS_INDEX );
		}

		if ( extension_loaded( 'litespeed' ) ) {
			$this->create_litespeed_htaccess( SHM_WORDPRESS_HTACCESS );
		}

		$this->setup_folders();
		$this->create_secret_key();
	}

	/**
	 * Initializes language domain for the plugin
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( SHM_PLUGIN_NAME, false, false );
	}

	/**
	 * Register listeners for actions
	 *
	 * @return void
	 */
	private function activate_actions() {
		// Init
		add_action( 'admin_init', array( $this, 'init' ) );

		// Router
		add_action( 'admin_init', array( $this, 'router' ) );

		// Setup folders
		add_action( 'admin_init', array( $this, 'setup_folders' ) );

		// Create secret key
		add_action( 'admin_init', array( $this, 'create_secret_key' ) );

		// Check user role capability
		add_action( 'admin_init', array( $this, 'check_user_role_capability' ) );

		// Schedule crons
		add_action( 'admin_init', array( $this, 'schedule_crons' ) );

		// Load text domain
		add_action( 'admin_init', array( $this, 'load_textdomain' ) );

		// Admin header
		add_action( 'admin_head', array( $this, 'admin_head' ) );

		// All in One WP Migration
		add_action( 'plugins_loaded', array( $this, 'shm_loaded' ), 10 );

		// Export and import commands
		add_action( 'plugins_loaded', array( $this, 'shm_commands' ), 10 );

		// Export and import buttons
		add_action( 'plugins_loaded', array( $this, 'shm_buttons' ), 10 );

		// Register scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_and_styles' ), 5 );

		// Enqueue export scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_export_scripts_and_styles' ), 5 );

		// Enqueue import scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_import_scripts_and_styles' ), 5 );

		// Enqueue backups scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_backups_scripts_and_styles' ), 5 );
	}

	/**
	 * Register listeners for filters
	 *
	 * @return void
	 */
	private function activate_filters() {
		// Add custom schedules
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ), 9999 );
	}

	/**
	 * Export and import commands
	 *
	 * @return void
	 */
	public function shm_commands() {
		// Add export commands
		add_filter( 'shm_export', 'Shm_Export_Init::execute', 5 );
		add_filter( 'shm_export', 'Shm_Export_Compatibility::execute', 5 );
		add_filter( 'shm_export', 'Shm_Export_Archive::execute', 10 );
		add_filter( 'shm_export', 'Shm_Export_Config::execute', 50 );
		add_filter( 'shm_export', 'Shm_Export_Config_File::execute', 60 );
		add_filter( 'shm_export', 'Shm_Export_Enumerate::execute', 100 );
		add_filter( 'shm_export', 'Shm_Export_Content::execute', 150 );
		add_filter( 'shm_export', 'Shm_Export_Database::execute', 200 );
		add_filter( 'shm_export', 'Shm_Export_Database_File::execute', 220 );
		add_filter( 'shm_export', 'Shm_Export_Download::execute', 250 );
		add_filter( 'shm_export', 'Shm_Export_Clean::execute', 300 );

		// Add import commands
		add_filter( 'shm_import', 'Shm_Import_Upload::execute', 5 );
		add_filter( 'shm_import', 'Shm_Import_Compatibility::execute', 10 );
		add_filter( 'shm_import', 'Shm_Import_Validate::execute', 50 );
		add_filter( 'shm_import', 'Shm_Import_Confirm::execute', 100 );
		add_filter( 'shm_import', 'Shm_Import_Blogs::execute', 150 );
		add_filter( 'shm_import', 'Shm_Import_Enumerate::execute', 200 );
		add_filter( 'shm_import', 'Shm_Import_Content::execute', 250 );
		add_filter( 'shm_import', 'Shm_Import_Mu_Plugins::execute', 270 );
		add_filter( 'shm_import', 'Shm_Import_Database::execute', 300 );
		add_filter( 'shm_import', 'Shm_Import_Done::execute', 350 );
		add_filter( 'shm_import', 'Shm_Import_Clean::execute', 400 );
	}

	/**
	 * Export and import buttons
	 *
	 * @return void
	 */
	public function shm_buttons() {
		// Add export buttons
		add_filter( 'shm_export_buttons', 'Shm_Export_Controller::buttons' );

		// Add import buttons
		add_filter( 'shm_import_buttons', 'Shm_Import_Controller::buttons' );
	}

	/**
	 * Sharad Site Migrator loaded
	 *
	 * @return void
	 */
	public function shm_loaded() {
		if ( is_multisite() ) {
			add_action( 'network_admin_notices', array( $this, 'multisite_notice' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}

		// Add HTTP export headers
		add_filter( 'shm_http_export_headers', 'Shm_Export_Controller::http_export_headers' );

		// Add HTTP import headers
		add_filter( 'shm_http_import_headers', 'Shm_Import_Controller::http_import_headers' );

		// Add chunk size limit
		add_filter( 'shm_max_chunk_size', 'Shm_Import_Controller::max_chunk_size' );

		// Add storage folder daily cleanup cron
		add_action( 'shm_storage_cleanup', 'Shm_Export_Controller::cleanup' );
	}

	/**
	 * Create folders and files needed for plugin operation, if they don't exist
	 *
	 * @return void
	 */
	public function setup_folders() {
		// Check if storage folder is created
		if ( ! is_dir( SHM_STORAGE_PATH ) ) {
			$this->create_storage_folder( SHM_STORAGE_PATH );
		}

		// Check if backups folder is created
		if ( ! is_dir( SHM_BACKUPS_PATH ) ) {
			$this->create_backups_folder( SHM_BACKUPS_PATH );
		}

		// Check if index.php is created in storage folder
		if ( ! is_file( SHM_STORAGE_INDEX ) ) {
			$this->create_storage_index( SHM_STORAGE_INDEX );
		}

		// Working files (database dumps, partial archives) are never meant to be fetched over HTTP
		if ( is_dir( SHM_STORAGE_PATH ) && ! is_file( SHM_STORAGE_HTACCESS ) ) {
			Shm_File_Htaccess::deny( SHM_STORAGE_HTACCESS );
		}

		// Check if index.php is created in backups folder
		if ( ! is_file( SHM_BACKUPS_INDEX ) ) {
			$this->create_backups_index( SHM_BACKUPS_INDEX );
		}

		// Check if .htaccess is created in backups folder
		if ( ! is_file( SHM_BACKUPS_HTACCESS ) ) {
			$this->create_backups_htaccess( SHM_BACKUPS_HTACCESS );
		}

		// Check if web.config is created in backups folder
		if ( ! is_file( SHM_BACKUPS_WEBCONFIG ) ) {
			$this->create_backups_webconfig( SHM_BACKUPS_WEBCONFIG );
		}
	}

	/**
	 * Create secret key if they don't exist yet
	 *
	 * @return void
	 */
	public function create_secret_key() {
		if ( ! get_option( SHM_SECRET_KEY ) ) {
			update_option( SHM_SECRET_KEY, shm_generate_secret_key() );
		}
	}

	/**
	 * Check user role capability
	 *
	 * @return void
	 */
	public function check_user_role_capability() {
		if ( ( $user = wp_get_current_user() ) && in_array( 'administrator', $user->roles ) ) {
			if ( ! $user->has_cap( 'export' ) || ! $user->has_cap( 'import' ) ) {
				if ( is_multisite() ) {
					return add_action( 'network_admin_notices', array( $this, 'missing_role_capability_notice' ) );
				} else {
					return add_action( 'admin_notices', array( $this, 'missing_role_capability_notice' ) );
				}
			}
		}
	}

	/**
	 * Schedule cron tasks for plugin operation, if not done yet
	 *
	 * @return void
	 */
	public function schedule_crons() {
		// Delete old cleanup cronjob
		if ( Shm_Cron::exists( 'shm_cleanup_cron' ) ) {
			Shm_Cron::clear( 'shm_cleanup_cron' );
		}

		// Schedule a new daily cleanup
		if ( ! Shm_Cron::exists( 'shm_storage_cleanup' ) ) {
			Shm_Cron::add( 'shm_storage_cleanup', 'daily', time() );
		}
	}

	/**
	 * Create storage folder
	 *
	 * @param  string Path to folder
	 * @return void
	 */
	public function create_storage_folder( $path ) {
		if ( ! Shm_Directory::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'storage_path_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'storage_path_notice' ) );
			}
		}
	}

	/**
	 * Create backups folder
	 *
	 * @param  string Path to folder
	 * @return void
	 */
	public function create_backups_folder( $path ) {
		if ( ! Shm_Directory::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_path_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_path_notice' ) );
			}
		}
	}

	/**
	 * Create storage index.php file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_storage_index( $path ) {
		if ( ! Shm_File_Index::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'storage_index_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'storage_index_notice' ) );
			}
		}
	}

	/**
	 * Create backups .htaccess file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_backups_htaccess( $path ) {
		if ( ! Shm_File_Htaccess::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_htaccess_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_htaccess_notice' ) );
			}
		}
	}

	/**
	 * Create backups web.config file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_backups_webconfig( $path ) {
		if ( ! Shm_File_Webconfig::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_webconfig_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_webconfig_notice' ) );
			}
		}
	}

	/**
	 * Create backups index.php file
	 *
	 * @param  string Path to file
	 * @return void
	 */
	public function create_backups_index( $path ) {
		if ( ! Shm_File_Index::create( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'backups_index_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'backups_index_notice' ) );
			}
		}
	}

	/**
	 * If the "noabort" environment variable has been set,
	 * the script will continue to run even though the connection has been broken
	 *
	 * @return void
	 */
	public function create_litespeed_htaccess( $path ) {
		if ( ! Shm_File_Htaccess::litespeed( $path ) ) {
			if ( is_multisite() ) {
				return add_action( 'network_admin_notices', array( $this, 'wordpress_htaccess_notice' ) );
			} else {
				return add_action( 'admin_notices', array( $this, 'wordpress_htaccess_notice' ) );
			}
		}
	}

	/**
	 * Display multisite notice
	 *
	 * @return void
	 */
	public function multisite_notice() {
		Shm_Template::render( 'main/multisite-notice' );
	}

	/**
	 * Display notice for storage directory
	 *
	 * @return void
	 */
	public function storage_path_notice() {
		Shm_Template::render( 'main/storage-path-notice' );
	}

	/**
	 * Display notice for index file in storage directory
	 *
	 * @return void
	 */
	public function storage_index_notice() {
		Shm_Template::render( 'main/storage-index-notice' );
	}

	/**
	 * Display notice for backups directory
	 *
	 * @return void
	 */
	public function backups_path_notice() {
		Shm_Template::render( 'main/backups-path-notice' );
	}

	/**
	 * Display notice for .htaccess file in backups directory
	 *
	 * @return void
	 */
	public function backups_htaccess_notice() {
		Shm_Template::render( 'main/backups-htaccess-notice' );
	}

	/**
	 * Display notice for web.config file in backups directory
	 *
	 * @return void
	 */
	public function backups_webconfig_notice() {
		Shm_Template::render( 'main/backups-webconfig-notice' );
	}

	/**
	 * Display notice for index file in backups directory
	 *
	 * @return void
	 */
	public function backups_index_notice() {
		Shm_Template::render( 'main/backups-index-notice' );
	}

	/**
	 * Display notice for .htaccess file in WordPress directory
	 *
	 * @return void
	 */
	public function wordpress_htaccess_notice() {
		Shm_Template::render( 'main/wordpress-htaccess-notice' );
	}

	/**
	 * Display notice for missing role capability
	 *
	 * @return void
	 */
	public function missing_role_capability_notice() {
		Shm_Template::render( 'main/missing-role-capability-notice' );
	}

	/**
	 * Register plugin menus
	 *
	 * @return void
	 */
	public function admin_menu() {
		// Top-level WP Migration menu
		add_menu_page(
			'Sharad Migrator',
			'Sharad Migrator',
			'export',
			'shm_export',
			'Shm_Export_Controller::index',
			'',
			'76.295'
		);

		// Sub-level Export menu
		add_submenu_page(
			'shm_export',
			__( 'Export', SHM_PLUGIN_NAME ),
			__( 'Export', SHM_PLUGIN_NAME ),
			'export',
			'shm_export',
			'Shm_Export_Controller::index'
		);

		// Sub-level Import menu
		add_submenu_page(
			'shm_export',
			__( 'Import', SHM_PLUGIN_NAME ),
			__( 'Import', SHM_PLUGIN_NAME ),
			'import',
			'shm_import',
			'Shm_Import_Controller::index'
		);

		// Sub-level Backups menu
		add_submenu_page(
			'shm_export',
			__( 'Backups', SHM_PLUGIN_NAME ),
			__( 'Backups', SHM_PLUGIN_NAME ),
			'import',
			'shm_backups',
			'Shm_Backups_Controller::index'
		);
	}

	/**
	 * Register scripts and styles
	 *
	 * @return void
	 */
	public function register_scripts_and_styles() {
		if ( is_rtl() ) {
			wp_register_style(
				'shm_servmask',
				Shm_Template::asset_link( 'css/servmask.min.rtl.css' )
			);
		} else {
			wp_register_style(
				'shm_servmask',
				Shm_Template::asset_link( 'css/servmask.min.css' )
			);
		}

		wp_register_script(
			'shm_util',
			Shm_Template::asset_link( 'javascript/util.min.js' ),
			array( 'jquery' )
		);
	}

	/**
	 * Enqueue scripts and styles for Export Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_export_scripts_and_styles( $hook ) {
		if ( (string) $hook !== 'toplevel_page_shm_export' ) {
			return;
		}

		// We don't want heartbeat to occur when exporting
		wp_deregister_script( 'heartbeat' );

		// We don't want auth check for monitoring whether the user is still logged in
		remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

		if ( is_rtl() ) {
			wp_enqueue_style(
				'shm_export',
				Shm_Template::asset_link( 'css/export.min.rtl.css' )
			);
		} else {
			wp_enqueue_style(
				'shm_export',
				Shm_Template::asset_link( 'css/export.min.css' )
			);
		}

		wp_enqueue_script(
			'shm_export',
			Shm_Template::asset_link( 'javascript/export.min.js' ),
			array( 'shm_util' )
		);

		wp_localize_script( 'shm_export', 'shm_export', array(
			'ajax'       => array(
				'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=shm_export' ) ),
			),
			'status'     => array(
				'url' => wp_make_link_relative( add_query_arg( array( 'secret_key' => get_option( SHM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=shm_status' ) ) ),
			),
			'secret_key' => get_option( SHM_SECRET_KEY ),
		) );

		wp_localize_script( 'shm_export', 'shm_locale', array(
			'stop_exporting_your_website'         => __( 'You are about to stop exporting your website, are you sure?', SHM_PLUGIN_NAME ),
			'preparing_to_export'                 => __( 'Preparing to export...', SHM_PLUGIN_NAME ),
			'unable_to_export'                    => __( 'Unable to export', SHM_PLUGIN_NAME ),
			'unable_to_start_the_export'          => __( 'Unable to start the export. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_run_the_export'            => __( 'Unable to run the export. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_stop_the_export'           => __( 'Unable to stop the export. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'please_wait_stopping_the_export'     => __( 'Please wait, stopping the export...', SHM_PLUGIN_NAME ),
			'close_export'                        => __( 'Close', SHM_PLUGIN_NAME ),
			'stop_export'                         => __( 'Stop export', SHM_PLUGIN_NAME ),
		) );
	}

	/**
	 * Enqueue scripts and styles for Import Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_import_scripts_and_styles( $hook ) {
		if ( substr( (string) $hook, -strlen( '_page_shm_import' ) ) !== '_page_shm_import' ) {
			return;
		}

		// We don't want heartbeat to occur when importing
		wp_deregister_script( 'heartbeat' );

		// We don't want auth check for monitoring whether the user is still logged in
		remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

		if ( is_rtl() ) {
			wp_enqueue_style(
				'shm_import',
				Shm_Template::asset_link( 'css/import.min.rtl.css' )
			);
		} else {
			wp_enqueue_style(
				'shm_import',
				Shm_Template::asset_link( 'css/import.min.css' )
			);
		}

		wp_enqueue_script(
			'shm_import',
			Shm_Template::asset_link( 'javascript/import.min.js' ),
			array( 'shm_util' )
		);

		wp_localize_script( 'shm_import', 'shm_uploader', array(
			'chunk_size'    => Shm_Import_Controller::max_chunk_size(),
			'max_retries'   => SHM_MAX_CHUNK_RETRIES,
			'url'           => wp_make_link_relative( admin_url( 'admin-ajax.php?action=shm_import' ) ),
			'params'        => array(
				'priority'   => 5,
				'secret_key' => get_option( SHM_SECRET_KEY ),
			),
		) );

		wp_localize_script( 'shm_import', 'shm_import', array(
			'ajax'       => array(
				'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=shm_import' ) ),
			),
			'status'     => array(
				'url' => wp_make_link_relative( add_query_arg( array( 'secret_key' => get_option( SHM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=shm_status' ) ) ),
			),
			'secret_key' => get_option( SHM_SECRET_KEY ),
		) );

		wp_localize_script( 'shm_import', 'shm_compatibility', array(
			'messages' => Shm_Compatibility::get( array() ),
		) );

		wp_localize_script( 'shm_import', 'shm_locale', array(
			'stop_importing_your_website'         => __( 'You are about to stop importing your website, are you sure?', SHM_PLUGIN_NAME ),
			'preparing_to_import'                 => __( 'Preparing to import...', SHM_PLUGIN_NAME ),
			'unable_to_import'                    => __( 'Unable to import', SHM_PLUGIN_NAME ),
			'unable_to_upload_the_chunk'          => __( 'The upload kept failing. Check your connection and try again; chunks already sent are kept.', SHM_PLUGIN_NAME ),
			'incomplete_upload'                   => __( 'The server did not acknowledge the complete upload chunk. Please upload the backup again.', SHM_PLUGIN_NAME ),
			'unable_to_start_the_import'          => __( 'Unable to start the import. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_confirm_the_import'        => __( 'Unable to confirm the import. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_prepare_blogs_on_import'   => __( 'Unable to prepare blogs on import. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_stop_the_import'           => __( 'Unable to stop the import. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'please_wait_stopping_the_import'     => __( 'Please wait, stopping the import...', SHM_PLUGIN_NAME ),
			'close_import'                        => __( 'Close', SHM_PLUGIN_NAME ),
			'finish_import'                       => __( 'Finish', SHM_PLUGIN_NAME ),
			'stop_import'                         => __( 'Stop import', SHM_PLUGIN_NAME ),
			'confirm_import'                      => __( 'Proceed', SHM_PLUGIN_NAME ),
			'continue_import'                     => __( 'Continue', SHM_PLUGIN_NAME ),
			'please_do_not_close_this_browser'    => __( 'Please do not close this browser window or your import will fail', SHM_PLUGIN_NAME ),
			'invalid_archive_extension'           => __(
				'The file type that you have tried to upload is not compatible with this plugin. ' .
				'Please ensure that your file is a <strong>.wpress</strong> file that was created with the All-in-One WP migration plugin. ' .
				'',
				SHM_PLUGIN_NAME
			),
		) );
	}

	/**
	 * Enqueue scripts and styles for Backups Controller
	 *
	 * @param  string $hook Hook suffix
	 * @return void
	 */
	public function enqueue_backups_scripts_and_styles( $hook ) {
		if ( substr( (string) $hook, -strlen( '_page_shm_backups' ) ) !== '_page_shm_backups' ) {
			return;
		}

		// We don't want heartbeat to occur when restoring
		wp_deregister_script( 'heartbeat' );

		// We don't want auth check for monitoring whether the user is still logged in
		remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

		if ( is_rtl() ) {
			wp_enqueue_style(
				'shm_backups',
				Shm_Template::asset_link( 'css/backups.min.rtl.css' )
			);
		} else {
			wp_enqueue_style(
				'shm_backups',
				Shm_Template::asset_link( 'css/backups.min.css' )
			);
		}

		wp_enqueue_script(
			'shm_backups',
			Shm_Template::asset_link( 'javascript/backups.min.js' ),
			array( 'shm_util' )
		);

		wp_localize_script( 'shm_backups', 'shm_import', array(
			'ajax'       => array(
				'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=shm_import' ) ),
			),
			'status'     => array(
				'url' => wp_make_link_relative( add_query_arg( array( 'secret_key' => get_option( SHM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=shm_status' ) ) ),
			),
			'secret_key' => get_option( SHM_SECRET_KEY ),
		) );

		wp_localize_script( 'shm_backups', 'shm_export', array(
			'ajax'       => array(
				'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=shm_export' ) ),
			),
			'status'     => array(
				'url' => wp_make_link_relative( add_query_arg( array( 'secret_key' => get_option( SHM_SECRET_KEY ) ), admin_url( 'admin-ajax.php?action=shm_status' ) ) ),
			),
			'secret_key' => get_option( SHM_SECRET_KEY ),
		) );

		wp_localize_script( 'shm_backups', 'shm_backups', array(
			'ajax'       => array(
				'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=shm_backups' ) ),
			),
			'backups'    => array(
				'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=shm_backup_list' ) ),
			),
			'labels'     => array(
				'url' => wp_make_link_relative( admin_url( 'admin-ajax.php?action=shm_add_backup_label' ) ),
			),
			'secret_key' => get_option( SHM_SECRET_KEY ),
		) );

		wp_localize_script( 'shm_backups', 'shm_locale', array(
			'stop_exporting_your_website'         => __( 'You are about to stop exporting your website, are you sure?', SHM_PLUGIN_NAME ),
			'preparing_to_export'                 => __( 'Preparing to export...', SHM_PLUGIN_NAME ),
			'unable_to_export'                    => __( 'Unable to export', SHM_PLUGIN_NAME ),
			'unable_to_start_the_export'          => __( 'Unable to start the export. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_run_the_export'            => __( 'Unable to run the export. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_stop_the_export'           => __( 'Unable to stop the export. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'please_wait_stopping_the_export'     => __( 'Please wait, stopping the export...', SHM_PLUGIN_NAME ),
			'close_export'                        => __( 'Close', SHM_PLUGIN_NAME ),
			'stop_export'                         => __( 'Stop export', SHM_PLUGIN_NAME ),
			'stop_importing_your_website'         => __( 'You are about to stop importing your website, are you sure?', SHM_PLUGIN_NAME ),
			'preparing_to_import'                 => __( 'Preparing to import...', SHM_PLUGIN_NAME ),
			'unable_to_import'                    => __( 'Unable to import', SHM_PLUGIN_NAME ),
			'unable_to_start_the_import'          => __( 'Unable to start the import. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_confirm_the_import'        => __( 'Unable to confirm the import. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_prepare_blogs_on_import'   => __( 'Unable to prepare blogs on import. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'unable_to_stop_the_import'           => __( 'Unable to stop the import. Refresh the page and try again', SHM_PLUGIN_NAME ),
			'please_wait_stopping_the_import'     => __( 'Please wait, stopping the import...', SHM_PLUGIN_NAME ),
			'finish_import'                       => __( 'Finish', SHM_PLUGIN_NAME ),
			'close_import'                        => __( 'Close', SHM_PLUGIN_NAME ),
			'stop_import'                         => __( 'Stop import', SHM_PLUGIN_NAME ),
			'confirm_import'                      => __( 'Proceed', SHM_PLUGIN_NAME ),
			'continue_import'                     => __( 'Continue', SHM_PLUGIN_NAME ),
			'please_do_not_close_this_browser'    => __( 'Please do not close this browser window or your import will fail', SHM_PLUGIN_NAME ),
			'want_to_delete_this_file'            => __( 'Are you sure you want to delete this file?', SHM_PLUGIN_NAME ),
		) );
	}

	/**
	 * Outputs menu icon between head tags
	 *
	 * @return void
	 */
	public function admin_head() {
		global $wp_version;

		// Admin header
		Shm_Template::render( 'main/admin-head', array( 'version' => $wp_version ) );
	}

	/**
	 * Register initial parameters
	 *
	 * @return void
	 */
	public function init() {

		// Set username
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			update_option( SHM_AUTH_USER, $_SERVER['PHP_AUTH_USER'] );
		} elseif ( isset( $_SERVER['REMOTE_USER'] ) ) {
			update_option( SHM_AUTH_USER, $_SERVER['REMOTE_USER'] );
		}

		// Set password
		if ( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			update_option( SHM_AUTH_PASSWORD, $_SERVER['PHP_AUTH_PW'] );
		}
	}

	/**
	 * Register initial router
	 *
	 * @return void
	 */
	public function router() {
		// Public actions: the export/import steps call themselves over HTTP without a login cookie
		add_action( 'wp_ajax_nopriv_shm_export', 'Shm_Export_Controller::export' );
		add_action( 'wp_ajax_nopriv_shm_import', 'Shm_Import_Controller::import' );
		// Restoring users invalidates login cookies; status still verifies the migration secret.
		add_action( 'wp_ajax_nopriv_shm_status', 'Shm_Status_Controller::status' );

		// Private actions
		add_action( 'wp_ajax_shm_export', 'Shm_Export_Controller::export' );
		add_action( 'wp_ajax_shm_import', 'Shm_Import_Controller::import' );
		add_action( 'wp_ajax_shm_status', 'Shm_Status_Controller::status' );
		add_action( 'wp_ajax_shm_backups', 'Shm_Backups_Controller::delete' );
		add_action( 'wp_ajax_shm_add_backup_label', 'Shm_Backups_Controller::add_label' );
		add_action( 'wp_ajax_shm_backup_list', 'Shm_Backups_Controller::backup_list' );
	}

	/**
	 * Add custom cron schedules
	 *
	 * @param  array $schedules List of schedules
	 * @return array
	 */
	public function add_cron_schedules( $schedules ) {
		$schedules['weekly']  = array(
			'display'  => __( 'Weekly', SHM_PLUGIN_NAME ),
			'interval' => 60 * 60 * 24 * 7,
		);
		$schedules['monthly'] = array(
			'display'  => __( 'Monthly', SHM_PLUGIN_NAME ),
			'interval' => ( strtotime( '+1 month' ) - time() ),
		);

		return $schedules;
	}
}
