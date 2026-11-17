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

class Shm_Import_Done {

	public static function execute( $params ) {

		// Check multisite.json file
		if ( true === is_file( shm_multisite_path( $params ) ) ) {
			// Read multisite.json file
			$handle = shm_open( shm_multisite_path( $params ), 'r' );

			// Parse multisite.json file
			$multisite = shm_read( $handle, filesize( shm_multisite_path( $params ) ) );
			$multisite = json_decode( $multisite, true );

			// Close handle
			shm_close( $handle );

			// Activate WordPress plugins
			if ( isset( $multisite['Plugins'] ) && ( $plugins = $multisite['Plugins'] ) ) {
				shm_activate_plugins( $plugins );
			}

			// Deactivate WordPress SSL plugins
			if ( ! is_ssl() ) {
				shm_deactivate_plugins( array(
					shm_discover_plugin_basename( 'really-simple-ssl/rlrsssl-really-simple-ssl.php' ),
					shm_discover_plugin_basename( 'wordpress-https/wordpress-https.php' ),
					shm_discover_plugin_basename( 'wp-force-ssl/wp-force-ssl.php' ),
					shm_discover_plugin_basename( 'force-https-littlebizzy/force-https.php' ),
				) );
			}

			// Deactivate WordPress plugins
			shm_deactivate_plugins( array(
				shm_discover_plugin_basename( 'invisible-recaptcha/invisible-recaptcha.php' ),
				shm_discover_plugin_basename( 'wps-hide-login/wps-hide-login.php' ),
				shm_discover_plugin_basename( 'hide-my-wp/index.php' ),
				shm_discover_plugin_basename( 'hide-my-wordpress/index.php' ),
				shm_discover_plugin_basename( 'mycustomwidget/my_custom_widget.php' ),
				shm_discover_plugin_basename( 'lockdown-wp-admin/lockdown-wp-admin.php' ),
				shm_discover_plugin_basename( 'rename-wp-login/rename-wp-login.php' ),
				shm_discover_plugin_basename( 'wp-simple-firewall/icwp-wpsf.php' ),
				shm_discover_plugin_basename( 'join-my-multisite/joinmymultisite.php' ),
				shm_discover_plugin_basename( 'multisite-clone-duplicator/multisite-clone-duplicator.php' ),
				shm_discover_plugin_basename( 'wordpress-mu-domain-mapping/domain_mapping.php' ),
				shm_discover_plugin_basename( 'pro-sites/pro-sites.php' ),
			) );

			// Deactivate Jetpack modules
			shm_deactivate_jetpack_modules( array(
				'photon',
				'sso',
			) );

		} else {

			// Check package.json file
			if ( true === is_file( shm_package_path( $params ) ) ) {

				// Read package.json file
				$handle = shm_open( shm_package_path( $params ), 'r' );

				// Parse package.json file
				$package = shm_read( $handle, filesize( shm_package_path( $params ) ) );
				$package = json_decode( $package, true );

				// Close handle
				shm_close( $handle );

				// Activate WordPress plugins
				if ( isset( $package['Plugins'] ) && ( $plugins = $package['Plugins'] ) ) {
					shm_activate_plugins( $plugins );
				}

				// Activate WordPress template
				if ( isset( $package['Template'] ) && ( $template = $package['Template'] ) ) {
					shm_activate_template( $template );
				}

				// Activate WordPress stylesheet
				if ( isset( $package['Stylesheet'] ) && ( $stylesheet = $package['Stylesheet'] ) ) {
					shm_activate_stylesheet( $stylesheet );
				}

				// Deactivate WordPress SSL plugins
				if ( ! is_ssl() ) {
					shm_deactivate_plugins( array(
						shm_discover_plugin_basename( 'really-simple-ssl/rlrsssl-really-simple-ssl.php' ),
						shm_discover_plugin_basename( 'wordpress-https/wordpress-https.php' ),
						shm_discover_plugin_basename( 'wp-force-ssl/wp-force-ssl.php' ),
						shm_discover_plugin_basename( 'force-https-littlebizzy/force-https.php' ),
					) );
				}

				// Deactivate WordPress plugins
				shm_deactivate_plugins( array(
					shm_discover_plugin_basename( 'invisible-recaptcha/invisible-recaptcha.php' ),
					shm_discover_plugin_basename( 'wps-hide-login/wps-hide-login.php' ),
					shm_discover_plugin_basename( 'hide-my-wp/index.php' ),
					shm_discover_plugin_basename( 'hide-my-wordpress/index.php' ),
					shm_discover_plugin_basename( 'mycustomwidget/my_custom_widget.php' ),
					shm_discover_plugin_basename( 'lockdown-wp-admin/lockdown-wp-admin.php' ),
					shm_discover_plugin_basename( 'rename-wp-login/rename-wp-login.php' ),
					shm_discover_plugin_basename( 'wp-simple-firewall/icwp-wpsf.php' ),
					shm_discover_plugin_basename( 'join-my-multisite/joinmymultisite.php' ),
					shm_discover_plugin_basename( 'multisite-clone-duplicator/multisite-clone-duplicator.php' ),
					shm_discover_plugin_basename( 'wordpress-mu-domain-mapping/domain_mapping.php' ),
					shm_discover_plugin_basename( 'pro-sites/pro-sites.php' ),
				) );

				// Deactivate Jetpack modules
				shm_deactivate_jetpack_modules( array(
					'photon',
					'sso',
				) );
			}
		}

		// Check blogs.json file
		if ( true === is_file( shm_blogs_path( $params ) ) ) {
			// Read blogs.json file
			$handle = shm_open( shm_blogs_path( $params ), 'r' );

			// Parse blogs.json file
			$blogs = shm_read( $handle, filesize( shm_blogs_path( $params ) ) );
			$blogs = json_decode( $blogs, true );

			// Close handle
			shm_close( $handle );

			// Loop over blogs
			foreach ( $blogs as $blog ) {

				// Activate WordPress plugins
				if ( isset( $blog['New']['Plugins'] ) && ( $plugins = $blog['New']['Plugins'] ) ) {
					shm_activate_plugins( $plugins );
				}

				// Activate WordPress template
				if ( isset( $blog['New']['Template'] ) && ( $template = $blog['New']['Template'] ) ) {
					shm_activate_template( $template );
				}

				// Activate WordPress stylesheet
				if ( isset( $blog['New']['Stylesheet'] ) && ( $stylesheet = $blog['New']['Stylesheet'] ) ) {
					shm_activate_stylesheet( $stylesheet );
				}

				// Deactivate WordPress SSL plugins
				if ( ! is_ssl() ) {
					shm_deactivate_plugins( array(
						shm_discover_plugin_basename( 'really-simple-ssl/rlrsssl-really-simple-ssl.php' ),
						shm_discover_plugin_basename( 'wordpress-https/wordpress-https.php' ),
						shm_discover_plugin_basename( 'wp-force-ssl/wp-force-ssl.php' ),
						shm_discover_plugin_basename( 'force-https-littlebizzy/force-https.php' ),
					) );
				}

				// Deactivate WordPress plugins
				shm_deactivate_plugins( array(
					shm_discover_plugin_basename( 'invisible-recaptcha/invisible-recaptcha.php' ),
					shm_discover_plugin_basename( 'wps-hide-login/wps-hide-login.php' ),
					shm_discover_plugin_basename( 'hide-my-wp/index.php' ),
					shm_discover_plugin_basename( 'hide-my-wordpress/index.php' ),
					shm_discover_plugin_basename( 'mycustomwidget/my_custom_widget.php' ),
					shm_discover_plugin_basename( 'lockdown-wp-admin/lockdown-wp-admin.php' ),
					shm_discover_plugin_basename( 'rename-wp-login/rename-wp-login.php' ),
					shm_discover_plugin_basename( 'wp-simple-firewall/icwp-wpsf.php' ),
					shm_discover_plugin_basename( 'join-my-multisite/joinmymultisite.php' ),
					shm_discover_plugin_basename( 'multisite-clone-duplicator/multisite-clone-duplicator.php' ),
					shm_discover_plugin_basename( 'wordpress-mu-domain-mapping/domain_mapping.php' ),
					shm_discover_plugin_basename( 'pro-sites/pro-sites.php' ),
				) );

				// Deactivate Jetpack modules
				shm_deactivate_jetpack_modules( array(
					'photon',
					'sso',
				) );
			}
		}

		// Set progress
		Shm_Status::done(
			__(
				'Your site has been imported successfully!',
				SHM_PLUGIN_NAME
			),
			sprintf(
				__(
					'» <a class="shm-no-underline" href="%s" target="_blank">Save permalinks structure</a>.</strong> (opens a new window)<br />' .
					'» <a class="shm-no-underline" href="https://wordpress.org/support/view/plugin-reviews/sharad-site-migrator?rate=5#postform" target="_blank">Optionally, review the plugin</a>.</strong> (opens a new window)',
					SHM_PLUGIN_NAME
				),
				admin_url( 'options-permalink.php#submit' )
			)
		);

		return $params;
	}
}
