<?php

class Sole_Schedule_Controller {

	const DB_BACKUP_EVENT      = 'sole_dropbox_db_event_hook';
	const UPLOADS_BACKUP_EVENT = 'sole_dropbox_uploads_event_hook';

	private $dropbox_controller;

	public function __construct() {
		$this->dropbox_controller = Dropbox_Controller::get_instance();

		// Need to add a weekly CRON job option
		add_filter( 'cron_schedules', array( $this, 'add_weekly_cron_job_option' ) );

		// Need to check that the timestamps are valid times
		add_filter( 'pre_update_option_sole_dropbox_db_backup_timestamp', array( $this,'check_if_is_valid_timestamp' ), 10, 2 );
		add_filter( 'pre_update_option_sole_dropbox_uploads_backup_timestamp', array( $this,'check_if_is_valid_timestamp' ), 10, 2 );

		// Need to check if the scheduled events settings were set
		add_action( 'update_option', array( $this, 'clear_outdated_schedules' ), 10, 3 );

		// Add the scheduled events
		add_action( self::DB_BACKUP_EVENT, array( $this->dropbox_controller, 'backup_sql_to_drive' ) );
		add_action( self::UPLOADS_BACKUP_EVENT, array( $this->dropbox_controller, 'backup_uploads_to_drive' ) );

		// Need to check on updating schedules/CRON jobs AFTER checking if there is updated option.
		add_action( 'shutdown', array( $this, 'verify_schedules_updated' ) );
	}

	// Set a schedule for a given event IF all info is present for that event
	public function create_new_schedule( $event, $frequency, $time ) {
		// Make sure that the settings for event frequency && time of day
		$backup_frequency = get_option( $frequency );
		$backup_time = get_option( $time );
		// Only want to set if both are set
		if( $backup_frequency && $backup_time ) {
			// Get the UNIX timestamp
			$start_time = $this->get_start_time( $backup_frequency, $backup_time );
			$cron_frequency = ( 'daily' == $backup_frequency ) ? 'daily' : 'weekly';
			wp_schedule_event( $start_time, $cron_frequency, $event );
		}
	}

	// Checks if an event is scheduled. If not, attempt to create it.
	public function verify_schedules_updated() {
		if( ! wp_next_scheduled( self::DB_BACKUP_EVENT ) ) {
			$this->create_new_schedule(
				self::DB_BACKUP_EVENT,
				'sole_dropbox_db_backup_timestamp',
				'sole_dropbox_db_frequency'
			);
		}
		if( ! wp_next_scheduled( self::UPLOADS_BACKUP_EVENT ) ) {
			$this->create_new_schedule(
				self::UPLOADS_BACKUP_EVENT,
				'sole_dropbox_uploads_backup_timestamp',
				'sole_dropbox_uploads_frequency'
			);
		}
	}

	// For both uploads and DB, checks if there is POST data
	// If so, need to clear the old scheduled event
	public function clear_outdated_schedules( $option, $old, $new ) {
		if( false !== strpos( $option, 'sole_dropbox_db_' ) ) {
			wp_clear_scheduled_hook( self::DB_BACKUP_EVENT );
		}
		else if ( false !== strpos( $option, 'sole_dropbox_uploads_' ) ) {
			wp_clear_scheduled_hook( self::UPLOADS_BACKUP_EVENT );
		}
	}

	// Need to add a weekly CRON job option (if it doesn't already exist)
	public function add_weekly_cron_job_option( $schedules ) {
		if( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __('Once Weekly'),
			);
		}
		return $schedules;
	}

	// Helper function to take a day of the week & a time and create a UNIX timestamp equivalent
	public function get_start_time( $frequency, $time ) {
		$start_time = 0;
		if( 'daily' != $frequency ) {
			$start_time = strtotime( $frequency . ' ' . $time );
		} else {
			$start_time = strtotime( $time );
		}
		return $start_time;
	}

	// Check if a given value is a timestamp or not.
	// If not, return the old value.
	public function check_if_is_valid_timestamp( $new, $old ) {
		if( preg_match("/^(2[0-3]|[01][0-9]):([0-5][0-9])$/", $new ) ) {
			return $new;
		}
		return $old;
	}
}
