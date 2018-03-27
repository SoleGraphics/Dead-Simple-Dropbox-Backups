<?php
	$dropbox_controller = new Dropbox_Controller();
	$usage = $dropbox_controller->get_account_usage();
?>
<div class="wrap">
	<h1>Dead Simple Dropbox Drive Backups - Settings</h1>
	<hr/>
	<p>Used <?php echo $usage['used']; ?> KB of <?php echo $usage['allocated']; ?> KB</p>
	<form method="POST" action="options.php" enctype="multipart/form-data">
		<?php settings_fields( self::SETTINGS_GROUP ); ?>
		<?php do_settings_sections( self::SETTINGS_PAGE_SLUG ); ?>
		<h2>Settings</h2>
		<table>
			<tr>
				<td>Application Key</td>
				<td><input id="sole_db_access_token" name="sole_db_access_token" type="text" value="<?php echo get_option( 'sole_db_access_token' ); ?>" /></td>
			</tr>
			<tr>
				<td>Database Backup Time</td>
				<td><input type="text" name="sole_dropbox_db_backup_timestamp" value="<?php echo get_option( 'sole_dropbox_db_backup_timestamp' ); ?>" /></td>
				<td>Enter time in a 24 hour "HH:MM" format</td>
			</tr>
			<tr>
				<td>Database Backup Frequency</td>
				<td><select name="sole_dropbox_db_frequency">
					<?php $selected = get_option( 'sole_dropbox_db_frequency' ); ?>
					<?php foreach( $this->backup_options as $option ): ?>
						<option value="<?php echo $option; ?>" <?php if($option == $selected){ echo 'selected="selected"'; } ?>><?php echo $option; ?></option>
					<?php endforeach; ?>
				</select></td>
			</tr>
			<tr>
				<td>Uploads Backup Time</td>
				<td><input type="text" name="sole_dropbox_uploads_backup_timestamp" value="<?php echo get_option( 'sole_dropbox_uploads_backup_timestamp' ); ?>"/></td>
				<td>Enter time in a 24 hour "HH:MM" format</td>
			</tr>
			<tr>
				<td>Uploads Backup Frequency</td>
				<td><select name="sole_dropbox_uploads_frequency">
					<?php $selected = get_option( 'sole_dropbox_uploads_frequency' ); ?>
					<?php foreach( $this->backup_options as $option ): ?>
						<option value="<?php echo $option; ?>" <?php if($option == $selected){ echo 'selected="selected"'; } ?>><?php echo $option; ?></option>
					<?php endforeach; ?>
				</select></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<h2>Extras</h2>
	<form method="POST" action="">
		<input type="hidden" name="manual-sole-dropbox-backup-trigger" value="true" />
		<?php submit_button( 'Backup Files & Database' ); ?>
	</form>
	<form method="POST" action="">
		<input type="hidden" name="manual-sole-dropbox-download-db-dump" value="true" />
		<?php submit_button( 'Download Database Dump' ); ?>
	</form>
	<form method="POST" action="">
		<input type="hidden" name="manual-sole-dropbox-download-uploads-zip" value="true" />
		<?php submit_button( 'Download Uploads Directory' ); ?>
	</form>
</div>
