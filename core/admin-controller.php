<?php

class Admin_Interface_Controller {

	const SETTINGS_PAGE_SLUG  = 'dropbox_backups_settings_page';
	const SETTINGS_GROUP      = 'dropbox_backups_group';
	const SETTINGS_NONCE_NAME = 'dropbox_backups_nonce_name';

	private $plugin_settings = array(
		'sole_db_access_token',
		'sole_dropbox_db_backup_timestamp',
		'sole_dropbox_db_frequency',
		'sole_dropbox_uploads_backup_timestamp',
		'sole_dropbox_uploads_frequency',
	);

	private $backup_options = array(
		'daily', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
	);

	private $table_controller;

	public function __construct() {
		$this->table_controller = Sole_Dropbox_Logger::get_instance();

		// Need to add the admin views. Should be network settings if we're in a multisite.
		if( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'add_admin_menu') );
			// Need to manually update the settings.
			add_action( 'init', array( $this, 'check_options_updated' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'add_admin_menu') );
		}

		add_action( 'admin_init', array( $this, 'register_plugin_settings') );
	}

	public function add_admin_menu() {
		add_menu_page( 'Dropbox Backup', 'Dropbox Backup', 'administrator', self::SETTINGS_PAGE_SLUG, '', 'dashicons-analytics' );

		// Register submenu for plugin settings - default page for the plugin
		add_submenu_page( self::SETTINGS_PAGE_SLUG, 'Dropbox Backup Settings', 'Settings', 'administrator', self::SETTINGS_PAGE_SLUG, array( $this, 'display_settings_page' ) );

		// Register submenu for log page
		add_submenu_page( self::SETTINGS_PAGE_SLUG, 'Dropbox Backup Logs', 'Logs', 'administrator', self::SETTINGS_PAGE_SLUG . '-logs', array( $this, 'display_logs' ) );

		// Register submenu for README page
		add_submenu_page( self::SETTINGS_PAGE_SLUG, 'README', 'Help', 'administrator', self::SETTINGS_PAGE_SLUG . '-readme', array( $this, 'display_readme' ) );
	}

	public function register_plugin_settings() {
		foreach ( $this->plugin_settings as $setting ) {
			register_setting( self::SETTINGS_GROUP, $setting );
		}
	}

	/**
	 * Fallback for multisites to update the plugin options
	 * (no options.php on multisite network).
	 */
	public function check_options_updated() {
		if( isset( $_POST[self::SETTINGS_NONCE_NAME] ) &&
			wp_verify_nonce( $_POST[self::SETTINGS_NONCE_NAME], 'dropbox_options' ) &&
			is_admin() ) {
			// Options are being updated, go through and save each.
			foreach ( $this->plugin_settings as $setting ) {
				$new_option_value = $_POST[$setting];
				update_option( $setting, $new_option_value );
			}
		}
	}

	public function display_settings_page() {
		if( is_multisite() ) {
			include plugin_dir_path( __DIR__ ) . 'templates/multisite-settings-form.php';
		} else {
			include plugin_dir_path( __DIR__ ) . 'templates/settings-form.php';
		}
	}

	public function display_logs() {
		// Check if a page is set
		$page           = isset( $_GET['page_to_display'] ) ? $_GET['page_to_display']: 1;
		$page 			= max( $page, 1 );
		$type			= isset( $_GET['msg_type'] ) ? $_GET['msg_type'] : '';

		// Get sender information
		$sender  = isset( $_GET['sender'] ) ? $_GET['sender'] : '';
		$senders = $this->table_controller->get_log_senders();
		$senders = $this->table_controller->simplify_array( $senders, 'log_sender' );

		// Log results to display to the user
		$logs = $this->table_controller->get_log_messages( $page, $type, $sender );

		// Get the number of pages
		$total_pages = ceil( $this->table_controller->get_max_number_results() / $this->table_controller->num_to_display );
		include plugin_dir_path( __DIR__ ) . 'templates/log-file.php';
	}

	public function display_readme() {
		include plugin_dir_path( __DIR__ ) . 'templates/readme.php';
	}
}
