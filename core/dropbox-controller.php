<?php

/**
 * Handles connecting to the Google Libraries / using the Google API
 */
class Dropbox_Controller {

	private $logger;
	private $settings = array(
		'access_token'  => '',
	);
	private $folder = 'Sole_WP_Backup_Plugin';

	public function __construct() {
		if( get_option( 'sole_db_access_token' ) ) {
			$this->settings['access_token'] = get_option( 'sole_db_access_token' );
		}

		// Core Controllers
		$this->file_controller = new Sole_WP_Files_Controller();
		$this->logger          = Sole_Dropbox_Logger::get_instance();
	}

	// Create zip of the uploads folder / upload to Dropbox
	public function backup_uploads_to_drive() {
		$upload_zip = $this->file_controller->get_wp_uploads_zip();

		$result = $this->upload_file_to_db( $upload_zip );

		// Delete the file from the plugin dir
		unlink( $upload_zip['path'] . $upload_zip['name'] );

		if( false !== $result ) {
			$this->logger->add_log_event( 'Backed up the uploads directory', 'WP Uploads' );
		} else {
			$this->logger->add_log_event( 'Error backing up the uploads directory', 'WP Uploads' );
		}
	}

	// Create sql dump of the database / upload to Dropbox
	public function backup_sql_to_drive() {
		$dump_file = $this->file_controller->create_db_dump();

		$result = $this->upload_file_to_db( $dump_file );

		// Delete the file from the plugin dir
		unlink( $dump_file['path'] . $dump_file['name'] );

		if( false !== $result ) {
			$this->logger->add_log_event( 'Backed up the Database', 'Database' );
		} else {
			$this->logger->add_log_event( 'Error backing up the Database', 'Database' );
		}
	}

	// Upload a file to Dropbox
	public function upload_file_to_db( $file_info ) {
		// Check if the file is valid.
		if( ! $file_info ) {
			return false;
		}

		// Check file size, if > 150m then use an upload session
		$size = filesize( $file_info['path'] . $file_info['name'] );
		$is_large = $size > (1 * pow( 1024, 2 ));

		if( $is_large ) {
			return $this->upload_large_file($file_info);
		} else {
			return $this->upload_file_part(
				'https://content.dropboxapi.com/2/files/upload',
				'@' . file_get_contents( $file_info['path'] . $file_info['name'] ),
				'{"mode": "add", "autorename": true, "path": "/' . $file_info['name'] . '" }'
			);
		}
	}

	// For use with large files
	function upload_large_file( $file_info ) {
		// API endpoints used in batch uploading
		$start_url = 'https://content.dropboxapi.com/2/files/upload_session/start';
		$end_url   = 'https://content.dropboxapi.com/2/files/upload_session/finish';
		$batch_url = 'https://content.dropboxapi.com/2/files/upload_session/append_v2';

		// File reading
		$handle = fopen( $file_info['path'] . $file_info['name'], 'r' );
		$size_per_batch = 8 * pow( 1024, 2 );

		if( false == $handle ) {
			return false;
		}

		// start upload session (upload_file_part)
		$session_id = $this->upload_file_part( $start_url, '', '{"close": false}' );
		$offset = 0;

		while( true ) {
            $to_upload = fread( $handle, $size_per_batch );

            // If there is nothing left to read
            if( -1 == $to_upload || 0 == strlen( $to_upload ) ) break;

			// add `$to_upload` to session
            $options = json_encode( array(
                "cursor" => array(
                    "session_id" => $session_id,
                    "offset" => $offset
                ),
                "close" => false
            ) );
			$this->upload_file_part( $batch_url, $to_upload, $options );
            $offset = ftell( $handle );
		}
		fclose( $handle );

        $options = json_encode( array(
            "cursor" => array(
                "session_id" => $session_id,
                "offset" => $offset
            ),
            "commit" => array(
                "path" => '/' . $file_info['name'],
                "mode" => "add",
                "autorename" => true
            )
        ) );
		$result = $this->upload_file_part( $end_url, '', $options );
	}

	// For use with files smaller than 150 MB
	function upload_file_part( $url, $file, $dp_api_args ) {
		// Make a cURL request instead of the third party library
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: ' . $dp_api_args,
            'Authorization: Bearer ' . $this->settings['access_token'],
        ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $file );
        $raw_data    = curl_exec( $ch );
        $data        = json_decode( $raw_data );
        $http_status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close($ch);

        if( 200 !== $http_status ) {
            $this->logger->add_log_event( $http_status . ' ' . print_r( $raw_data, true ), 'Dropbox File Requester' );
            return false;
        }

        // If we are starting a session upload, return the session ID
        if( is_object( $data ) &&
        	property_exists( $data, 'session_id' ) ) {
        	return $data->session_id;
        }

		return true;
	}

	public function get_account_usage() {
		$url = 'https://api.dropboxapi.com/2/users/get_space_usage';
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->settings['access_token'],
        ) );

        $raw_data    = curl_exec( $ch );
        $http_status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close($ch);

        $data = json_decode( $raw_data );
        if( empty( $data ) ) {
            return array(
                'used'      => 0,
                'allocated' => 0,
            );
        }

        return array(
        	'used'      => round($data->used / 1024),
        	'allocated' => round($data->allocation->allocated / 1024),
        );
	}

}
