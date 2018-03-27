<?php

// Project Agnostic file controller class
//
// This is designed to just do simple file manipulation

class Sole_WP_Files_Controller {

	public function __construct() {
		$this->mysql_cmd  = $this->get_os_command_type();
		$this->mysql_path = $this->get_dump_command_path();
	}

	// Returns a SQL dump of the database
	public function create_db_dump() {
		// Check that a path exists
		if( ! $this->mysql_path ) {
			return false;
		}

		// Need somewhere to store the temp dump file (before upload)
		$plugin_path = plugin_dir_path( __DIR__ );
		$file_name   = 'db-backup-' . date('Y-m-d') . '.sql';

		// Build the command
		$cmd = $this->mysql_path . $this->mysql_cmd . ' -h ' . escapeshellarg( DB_HOST ) . ' -u ' . escapeshellarg( DB_USER ) . ' -p' . escapeshellarg( DB_PASSWORD ) . ' ' . escapeshellarg( DB_NAME ) . ' > ' . $plugin_path . $file_name;

		exec( $cmd, $output, $results );

		// Need to check if the file exists
		if( ! file_exists( $plugin_path . $file_name ) ) {
			return false;
		}

		// File exists! return the file
		return array(
			'name' => $file_name,
			'path' => $plugin_path,
		);
	}

	// Returns a zip of the WP uploads directory
	public function get_wp_uploads_zip() {
		// Get the uploads dir
		$uploads_dir = wp_upload_dir()['basedir'];

		// Set our destination
		$destination = plugin_dir_path( __DIR__ ) . 'uploads.zip';

		// Create our zip archive
		$zip = new ZipArchive();
		if( true !== $zip->open( $destination, ZipArchive::CREATE ) ) {
			return false;
		}

		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $uploads_dir ), RecursiveIteratorIterator::SELF_FIRST );
		$ignored_files = array (
			'.', '..', '.DS_Store'
		);

        foreach ($files as $file) {
            $file = str_replace('\\', '/', $file);
            $extension = substr( $file, strrpos( $file, '/' ) + 1 );
            $file = realpath($file);

            if( in_array( $extension, $ignored_files ) ) {
                continue;
            }

            if ( is_dir( $file ) ) {
                $zip->addEmptyDir(str_replace($uploads_dir . '/', '', $file . '/'));
            }
            else if ( is_file( $file ) ) {
                $zip->addFromString( str_replace( $uploads_dir . '/', '', $file ), file_get_contents( $file ) );
            }
        }

		$zip->close();

		if( ! file_exists( $destination ) ) {
			return false;
		}

		return array(
			'name' => 'uploads.zip',
			'path' => plugin_dir_path( __DIR__ ),
		);
	}

	/**
	 * ---------------------------------------------------------
	 * Manual Downloads
	 * ---------------------------------------------------------
	 */
	function download_db_dump() {
		$file_info = $this->create_db_dump();
		$this->download_generic_file( $file_info );
	}

	function download_uploads_dump() {
		$file_info = $this->get_wp_uploads_zip();
		$this->download_generic_file( $file_info );
	}

	// Download a single file
	// (script should call exit after cleanup)
	function download_generic_file( $file_info ) {
		if( false === $file_info ) {
			return;
		}

		$download_link = $file_info['path'] . $file_info['name'];

		// Download the file, need the file URL to force the download.
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-disposition: attachment; filename=\"" . basename($file_info['name']) . "\"");
		ob_clean();
    	flush();
		readfile($download_link);

		// Delete the file and exit immediately (since we set headers for downloading)
		unlink( $download_link );
		exit();
	}

	/**
	 * ---------------------------------------------------------
	 * Helpers
	 * ---------------------------------------------------------
	 */

	// Checks if current OS is WIN
	// Returns the correct terminal command.
	private function get_os_command_type() {
		$is_win = stristr( PHP_OS, 'WIN' );
		$is_dar = stristr( PHP_OS, 'DARWIN' );
		if( $is_win && ! $is_dar ) {
			return 'mysqldump.exe';
		}
		return 'mysqldump';
	}

	// Need to find the correct path to the mysqldump command
	private function get_dump_command_path() {
		$return_code = array();
		$output = array();

		// List of (some) possible places the mysqldump command could be residing.
		$possible_paths = array(
			'', '/usr/', '/usr/bin/', '/usr/bin/mysql/', '/usr/bin/mysql/bin/', '/usr/local/', '/usr/local/mysql/', '/usr/local/mysql/bin/'
		);

		foreach( $possible_paths as $path ) {
			exec( $path . $this->mysql_cmd . ' --help', $output, $return_code );
			if( 0 === $return_code ) {
				return $path;
			}
		}

		// Uh-oh, they can no use mysqldump. Or I'm missing a possible path.
		return false;
	}
}
