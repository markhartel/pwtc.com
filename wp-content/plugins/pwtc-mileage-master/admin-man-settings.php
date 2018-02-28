<?php
if (!current_user_can($capability)) {
    return;
}
?>
<script type="text/javascript">
jQuery(document).ready(function($) { 

    $("#ride_lookback_date").datepicker({
  		dateFormat: 'yy-mm-dd',
		changeMonth: true,
      	changeYear: true
	});

	$("#plugin_menu_label").focus();

});
</script>
<div class="wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
if (count($error_msgs) > 0) {
?>
    <div class="notice notice-error"><p>
	<?php 
	foreach ($error_msgs as $msg):
		echo '<strong>' . $msg . '</strong><br>';
	endforeach;
	?>
	</p></div>
<?php
}
?>
	<p>Use this page to adjust the settings for the PWTC Mileage plugin.</p>
	<form class="stacked-form" method="POST">
		<?php wp_nonce_field('pwtc_mileage_settings'); ?>
		<span>Plugin Menu Label</span>
		<input type="text" name="plugin_menu_label" id="plugin_menu_label" 
			value="<?php echo $plugin_options['plugin_menu_label']; ?>" required/>
		<span>Plugin Menu Location</span>
		<input type="text" name="plugin_menu_location" id="plugin_menu_location" 
			value="<?php echo $plugin_options['plugin_menu_location']; ?>" required/>
		<span>Posted Ride Maximum Lookback Date</span>
		<input type="text" name="ride_lookback_date" id="ride_lookback_date" 
			value="<?php echo $plugin_options['ride_lookback_date']; ?>"/>
		<span>Database Job Lock Time Limit (seconds)</span>
		<input type="text" name="db_lock_time_limit" id="db_lock_time_limit" 
			value="<?php echo $plugin_options['db_lock_time_limit']; ?>"/>
		<span>Expiration Grace Period (days)</span>
		<input type="text" name="expire_grace_period" id="expire_grace_period" 
			value="<?php echo $plugin_options['expire_grace_period']; ?>"/>
		<span>Disable Member Expiration Check</span>
		<span class="checkbox-wrap">
			<input type="checkbox" name="disable_expir_check" id="disable_expir_check" 
			<?php if ($plugin_options['disable_expir_check']) { echo 'checked'; } ?>/>
		</span>
		<span>Disable Delete Action Confirm</span>
		<span class="checkbox-wrap">
			<input type="checkbox" name="disable_delete_confirm" id="disable_delete_confirm" 
			<?php if ($plugin_options['disable_delete_confirm']) { echo 'checked'; } ?>/>
		</span>
		<span>Show Ride IDs</span>
		<span class="checkbox-wrap">
			<input type="checkbox" name="show_ride_ids" id="show_ride_ids" 
			<?php if ($plugin_options['show_ride_ids']) { echo 'checked'; } ?>/>
		</span>
		<span>Drop Tables/Views Upon Plugin Delete</span>
		<span class="checkbox-wrap">
			<input type="checkbox" id="drop_db_on_delete" name="drop_db_on_delete" 
			<?php if ($plugin_options['drop_db_on_delete']) { echo 'checked'; } ?>/>
		</span>
		<span>Administrator Maintenance Mode</span>
		<span class="checkbox-wrap">
			<input type="checkbox" id="admin_maint_mode" name="admin_maint_mode" 
			<?php if ($plugin_options['admin_maint_mode']) { echo 'checked'; } ?>/>
		</span>
		<input type="submit" value="Save" class="button button-primary button-large"/>
	</form>
</div>
<?php
