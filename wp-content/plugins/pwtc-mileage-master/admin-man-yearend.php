<?php
if (!current_user_can($capability)) {
    return;
}
$message = '';
$notice_type = '';
$show_buttons = true;
$clear_button = false;
$show_purge = false;
?>
<script type="text/javascript">
jQuery(document).ready(function($) { 

    var confirm_consolidate = true;
    var confirm_sync = true;
    var confirm_purge = true;
    var confirm_restore = true;
    var confirm_updmembs = true;

    $('.sync-frm').on('submit', function(evt) {
        if (confirm_sync) {
            evt.preventDefault();
            open_confirm_dialog(
                'This operation will synchronize the mileage database rider list with the master membership database. Do you want to continue?', 
                function() {
                    confirm_sync = false;
                    $('.sync-frm input[name="member_sync"]').click();
                }
            );
        }
    });

    $('.purge-frm').on('submit', function(evt) {
        if (confirm_purge) {
            evt.preventDefault();
            open_confirm_dialog(
                'This operation will purge all non-riders from the membership list. Do you want to continue?', 
                function() {
                    confirm_purge = false;
                    $('.purge-frm input[name="purge_nonriders"]').click();
                }
            );
        }
    });

    $('.consol-frm').on('submit', function(evt) {
        if (confirm_consolidate) {
            evt.preventDefault();
            open_confirm_dialog(
                'WARNING: this operation will delete all <?php echo(intval(date('Y'))-2); ?> rides in the mileage database, proceed ONLY after first exporting the mileage database. Do you want to continue?', 
                function() {
                    confirm_consolidate = false;
                    $('.consol-frm input[name="consolidate"]').click();
               }
            );
        }
    });

    $('.restore-frm').on('submit', function(evt) {
        if (confirm_restore) {
            evt.preventDefault();
            open_confirm_dialog(
                'WARNING: this operation will overwrite the mileage database. Do you want to continue?', 
                function() {
                    confirm_restore = false;
                    $('.restore-frm input[name="restore"]').click();
               }
            );
        }
    });

    $(".restore-btn").on('click', function(evt) {
		$(".restore-frm input[type='file']").val('');
        $(".restore-frm input[type='file']").next('label').html('Select file...'); 
        $(".restore-btn").hide('fast', function() {
            $('.restore-blk').show('slow'); 
            $(".restore-frm input[name='members_file']").focus();
        })
    });

	$(".restore-blk .cancel-btn").on('click', function(evt) {
		$('.restore-blk').hide('slow', function() {
            $(".restore-btn").show('fast');
        });
    });

    $('.updmembs-frm').on('submit', function(evt) {
        if (confirm_updmembs) {
            evt.preventDefault();
            open_confirm_dialog(
                'This operation will synchronize the mileage database rider list with the contents of the updmembs file. Do you want to continue?', 
                function() {
                    confirm_updmembs = false;
                    $('.updmembs-frm input[name="updmembs"]').click();
               }
            );
        }
    });

    $(".updmembs-btn").on('click', function(evt) {
		$(".updmembs-frm input[type='file']").val('');
        $(".updmembs-frm input[type='file']").next('label').html('Select file...'); 
        $(".updmembs-btn").hide('fast', function() {
            $('.updmembs-blk').show('slow'); 
            $(".updmembs-frm input[name='updmembs_file']").focus();
        })
    });

	$(".updmembs-blk .cancel-btn").on('click', function(evt) {
		$('.updmembs-blk').hide('slow', function() {
            $(".updmembs-btn").show('fast');
        });
    });

    $('.inputfile').each(function() {
		var $input	 = $(this),
			$label	 = $input.next('label'),
			labelVal = $label.html();

		$input.on('change', function(e) {
			var fileName = '';
            if (e.target.value) {
				fileName = e.target.value.split( '\\' ).pop();
            }
			if (fileName) {
				$label.html(fileName);
            }
			else {
				$label.html(labelVal);
            }
		});

		// Firefox bug fix
		$input
		.on('focus', function(){ $input.addClass('has-focus'); })
		.on('blur', function(){ $input.removeClass('has-focus'); });
	});

    $('.updmembs-btn').focus();

 });
</script>
<div class="wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
foreach ( $job_status as $status ) {
    if ($status['status'] == PwtcMileage_DB::TRIGGERED_STATUS) {
        $message = $status['job_id'] . ' action triggered';
        $detail = '';
        $notice_type = 'notice-warning';
        $show_buttons = false;
    } 
    else if ($status['status'] == PwtcMileage_DB::STARTED_STATUS) {
        $message = $status['job_id'] . ' action running';
        $detail = '';
        $notice_type = 'notice-warning';
        $show_buttons = false;
    }
    else if ($status['status'] == PwtcMileage_DB::FAILED_STATUS) {
        $message = $status['job_id'] . ' action failed';
        $detail = ' - ' . $status['error_msg'];
        $notice_type = 'notice-error';
        $clear_button = true;
    }
    else {
        $message = $status['job_id'] . ' action success';
        $detail = ' - ' . $status['error_msg'];
        $notice_type = 'notice-success';
        $clear_button = true;
    }
?>
    <div class="notice <?php echo $notice_type; ?>">
        <p><strong><?php echo $message; ?></strong><?php echo $detail; ?></p>
    </div>
<?php
}

if ($show_buttons) {
    if ($clear_button) {
?>
        <div><form class="clear-frm" method="POST">
        	<?php wp_nonce_field('pwtc_mileage_clear_errs'); ?>
            <input type="submit" name="clear_errs" value="Clear Messages" class="button">
        </form></div>
<?php        
    }
?>
    <p>
<?php if (false) { ?>
        <div><strong>Synchronize rider list with master membership database.</strong></div>
        <div><form class="sync-frm" method="POST">
            <?php wp_nonce_field('pwtc_mileage_member_sync'); ?>
            <input type="submit" name="member_sync" value="Synchronize" 
                class="button button-primary button-large"/>
        </form></div><br>
<?php } ?>
        <div><strong>Synchronize rider list with contents of updmembs file.</strong></div>
        <div>
            <button class="updmembs-btn button button-primary button-large">Synchronize</button>
            <span class="updmembs-blk popup-frm initially-hidden">
			<form class="updmembs-frm stacked-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('pwtc_mileage_updmembs'); ?>
                <span>Updmembs</span>
                <input id="select-updmembs-file" class="inputfile" type="file" name="updmembs_file" multiple="false" accept=".dbf"/>
                <label for="select-updmembs-file" class="button">Select file...</label>
				<input class="button button-primary" type="submit" name="updmembs" value="Synchronize"/>
				<input class="cancel-btn button button-primary" type="button" value="Cancel"/>
			</form>
		    </span>
        </div><br>
 <?php if ($show_purge) { ?>
       <div><strong>Purge all non-riders from rider list.</strong></div>
        <div><form class="purge-frm" method="POST">
            <?php wp_nonce_field('pwtc_mileage_purge_nonriders'); ?>
            <input type="submit" name="purge_nonriders" value="Purge" 
                class="button button-primary button-large"/>
        </form></div><br>
 <?php } ?>
       <div><strong>Consolidate all <?php echo(intval(date('Y'))-2); ?> club rides to single entry.</strong></div>
        <div><form class="consol-frm" method="POST">
            <?php wp_nonce_field('pwtc_mileage_consolidate'); ?>
            <input type="submit" name="consolidate" value="Consolidate" 
                class="button button-primary button-large" 
                <?php if ($rides_to_consolidate <= 1) { echo 'disabled'; } ?>/>
        </form></div><br>
        <div><strong>Export mileage database to CSV files.</strong></div>
        <div><form class="export-frm" method="POST">
            <?php wp_nonce_field('pwtc_mileage_export'); ?>
            <input type="submit" name="export_members" 
                value="Members (<?php echo $member_count; ?>)" 
                class="button button-primary button-large"/>
            <input type="submit" name="export_rides" 
                value="Rides (<?php echo $ride_count; ?>)" 
                class="button button-primary button-large"/>
            <input type="submit" name="export_mileage" 
                value="Mileage (<?php echo $mileage_count; ?>)" 
                class="button button-primary button-large"/>
            <input type="submit" name="export_leaders" 
                value="Leaders (<?php echo $leader_count; ?>)" 
                class="button button-primary button-large"/>
        </form></div><br>
        <div><strong>Restore mileage database from exported CSV files.</strong></div>
        <div>
            <button class="restore-btn button button-primary button-large">Restore</button>
            <span class="restore-blk popup-frm initially-hidden">
			<form class="restore-frm stacked-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('pwtc_mileage_restore'); ?>
                <span>Members</span>
                <input id="select-members-file" class="inputfile" type="file" name="members_file" multiple="false" accept=".csv"/>
                <label for="select-members-file" class="button">Select file...</label>
                <span>Rides</span>
                <input id="select-rides-file" class="inputfile" type="file" name="rides_file" multiple="false" accept=".csv"/>
                <label for="select-rides-file" class="button">Select file...</label>
                <span>Mileage</span>
                <input id="select-mileage-file" class="inputfile" type="file" name="mileage_file" multiple="false" accept=".csv"/>
                <label for="select-mileage-file" class="button">Select file...</label>
                <span>Leaders</span>
                <input id="select-leaders-file" class="inputfile" type="file" name="leaders_file" multiple="false" accept=".csv"/>
                <label for="select-leaders-file" class="button">Select file...</label>
				<input class="button button-primary" type="submit" name="restore" value="Restore"/>
				<input class="cancel-btn button button-primary" type="button" value="Cancel"/>
			</form>
		    </span>
        </div><br>
    </p>
<?php
    include('admin-rider-lookup.php');
}
else if ($show_clear_lock) {
?>
    <div>
        <form class="clear-lock-frm" method="POST">
            <?php wp_nonce_field('pwtc_mileage_clear_lock'); ?>
            <input type="submit" name="clear_lock" value="Clear Lock" class="button">
        </form>
    </div>
<?php
}
else {
?>
    <div>
        <form class="refresh-frm" method="POST">
            <input type="submit" name="refresh_page" value="Refresh" class="button">
        </form>
    </div>
<?php
}
?>
</div>
<?php
