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

class Shm_Backups_Controller {

	public static function index() {
		Shm_Template::render(
			'backups/index',
			array(
				'backups'  => Shm_Backups::get_files(),
				'labels'   => Shm_Backups::get_labels(),
				'username' => get_option( SHM_AUTH_USER ),
				'password' => get_option( SHM_AUTH_PASSWORD ),
			)
		);
	}

	public static function delete( $params = array() ) {
		shm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) && is_string( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		// Set archive
		$archive = null;
		if ( isset( $params['archive'] ) && is_string( $params['archive'] ) ) {
			$archive = trim( $params['archive'] );
		}

		try {
			// Ensure that unauthorized people cannot access delete action
			shm_verify_secret_key( $secret_key );
		} catch ( Shm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		try {
			Shm_Backups::delete_file( $archive );
			Shm_Backups::delete_label( $archive );
		} catch ( Shm_Backups_Exception $e ) {
			echo json_encode( array( 'errors' => array( $e->getMessage() ) ) );
			exit;
		}

		echo json_encode( array( 'errors' => array() ) );
		exit;
	}

	public static function add_label( $params = array() ) {
		shm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_POST );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) && is_string( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		// Set archive
		$archive = null;
		if ( isset( $params['archive'] ) && is_string( $params['archive'] ) ) {
			$archive = trim( $params['archive'] );
		}

		// Set backup label
		$label = null;
		if ( isset( $params['label'] ) && is_string( $params['label'] ) ) {
			$label = trim( $params['label'] );
		}

		try {
			// Ensure that unauthorized people cannot access add label action
			shm_verify_secret_key( $secret_key );
		} catch ( Shm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		try {
			Shm_Backups::set_label( $archive, $label );
		} catch ( Shm_Backups_Exception $e ) {
			echo json_encode( array( 'errors' => array( $e->getMessage() ) ) );
			exit;
		}

		echo json_encode( array( 'errors' => array() ) );
		exit;
	}

	public static function backup_list( $params = array() ) {
		shm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_GET );
		}

		// Set secret key
		$secret_key = null;
		if ( isset( $params['secret_key'] ) && is_string( $params['secret_key'] ) ) {
			$secret_key = trim( $params['secret_key'] );
		}

		try {
			// Ensure that unauthorized people cannot access backups list action
			shm_verify_secret_key( $secret_key );
		} catch ( Shm_Not_Valid_Secret_Key_Exception $e ) {
			exit;
		}

		Shm_Template::render(
			'backups/backups-list',
			array(
				'backups' => Shm_Backups::get_files(),
				'labels'  => Shm_Backups::get_labels(),
			)
		);
		exit;
	}
}
