<?php

/**
 	Plugin Name: Dead Simple Dropbox Backups
	Description: Allows for backing up site database and uploads to Dropbox
	Author: Ben Greene
	Version: 1
	License: MIT
 */

// Require core controllers
require( 'core/log-controller.php' );
require( 'core/dropbox-controller.php' );
require( 'core/admin-controller.php' );
require( 'core/schedule-controller.php' );
require( 'core/file-controller.php' );

class Sole_Dropbox_Backups {

	public function __construct() {
		$this->sole_dropbox_controller = new Dropbox_Controller();
		$this->sole_dropbox_admin_controller = new Admin_Interface_Controller();
		$this->sole_dropbox_file_controller = new Sole_WP_Files_Controller();

		add_action( 'init', array( $this, 'check_manual_triggers' ) );
	}

	public function check_manual_triggers() {
		if( ! is_admin() ) {
			return;
		}

		// Check for manual backup trigger
		if( isset( $_POST['manual-sole-dropbox-backup-trigger'] ) &&
			'true' === $_POST['manual-sole-dropbox-backup-trigger'] ) {
			$this->sole_dropbox_controller->backup_sql_to_drive();
			$this->sole_dropbox_controller->backup_uploads_to_drive();
		}

		if( isset( $_POST['manual-sole-dropbox-download-db-dump'] ) &&
			'true' === $_POST['manual-sole-dropbox-download-db-dump'] ) {
			$this->sole_dropbox_file_controller->download_db_dump();
		}

		if( isset( $_POST['manual-sole-dropbox-download-uploads-zip'] ) &&
			'true' === $_POST['manual-sole-dropbox-download-uploads-zip'] ) {
			$this->sole_dropbox_file_controller->download_uploads_dump();
		}
	}
}

new Sole_Dropbox_Backups();
