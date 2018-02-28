<div class="wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
if (!current_user_can($capability)) {
?> 
    <p><strong>Access Denied</strong> - you do not have the rights to view this page.</p>
<?php   
}
else if ($plugin_options['admin_maint_mode'] and !current_user_can('manage_options')) {
?> 
    <p><strong>Access Denied</strong> - the mileage database is maintenance mode.</p>
<?php       
}
else {
?>
<script type="text/javascript">
jQuery(document).ready(function($) { 

	function populate_riders_table(members) {
		$('#rider-inspect-section .riders-div').empty();
        if (members.length > 0) {
            $('#rider-inspect-section .riders-div').append('<table class="rwd-table">' +
                '<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Expiration Date</th><th>Actions</th></tr>' +
                '</table>');
            editlink = '<a title="Edit this rider\'s information." class="modify-btn">Edit</a>';
            deletelink = '<a title="Delete this rider." class="remove-btn">Delete</a>';
            members.forEach(function(item) {
                var fmtdate = getPrettyDate(item.expir_date);
                $('#rider-inspect-section .riders-div table').append(
                    '<tr memberid="' + item.member_id + '">' + 
                    '<td data-th="ID">' + item.member_id + '</td>' +
                    '<td data-th="First Name">' + item.first_name + 
                    '</td><td data-th="Last Name">' + item.last_name + '</td>' + 
                    '<td data-th="Expiration" date="' + item.expir_date + '">' + fmtdate + '</td>' + 
                    '<td data-th="Actions">' + editlink + ' ' + deletelink +
                    '</td></tr>');    
            });
            $('#rider-inspect-section .riders-div .modify-btn').on('click', function(evt) {
                evt.preventDefault();
                var action = '<?php echo admin_url('admin-ajax.php'); ?>';
                var data = {
                    'action': 'pwtc_mileage_get_rider',
                    'member_id': $(this).parent().parent().attr('memberid')
                };
                $('body').addClass('waiting');
                $.post(action, data, modify_rider_cb);
            });
            $('#rider-inspect-section .riders-div .remove-btn').on('click', function(evt) {
                evt.preventDefault();
                var action = '<?php echo admin_url('admin-ajax.php'); ?>';
                var data = {
                    'action': 'pwtc_mileage_remove_rider',
                    'member_id': $(this).parent().parent().attr('memberid'),
                    'nonce': '<?php echo wp_create_nonce('pwtc_mileage_remove_rider'); ?>'
                };
    <?php if ($plugin_options['disable_delete_confirm']) { ?>
                $('body').addClass('waiting');
                $.post(action, data, remove_rider_cb);
    <?php } else { ?>
                open_confirm_dialog(
                    'Are you sure you want to delete rider ID ' + data.member_id + '?', 
                    function() {
                        $('body').addClass('waiting');
                        $.post(action, data, remove_rider_cb);
                    }
                );
    <?php } ?>		
            });
        }
        else {
            $('#rider-inspect-section .riders-div').append(
                '<span class="empty-tbl">No riders found.</span>');
        }
    }

	function lookup_riders_cb(response) {
        var res = JSON.parse(response);
        $("#rider-inspect-section .search-frm input[name='memberid']").val(res.memberid);
        $("#rider-inspect-section .search-frm input[name='firstname']").val(res.firstname);
        $("#rider-inspect-section .search-frm input[name='lastname']").val(res.lastname);
		populate_riders_table(res.members);
        $('body').removeClass('waiting');
    }   

	function create_rider_cb(response) {
        $('body').removeClass('waiting');
        var res = JSON.parse(response);
		if (res.error) {
            open_error_dialog(res.error);
		}
		else {
            $('#rider-inspect-section .add-blk').hide('slow', function() {
                $("#rider-inspect-section .add-btn").show('fast');     
            });
            load_rider_table();
        }
    }   

	function modify_rider_cb(response) {
        var res = JSON.parse(response);
		if (res.error) {
            open_error_dialog(res.error);
		}
		else {
			$("#rider-inspect-section .add-blk .add-frm input[type='submit']").val(
                'Modify'
            );
			$("#rider-inspect-section .add-blk .add-frm input[name='memberid']").val(
                res.member_id
            );
			$("#rider-inspect-section .add-blk .add-frm input[name='firstname']").val(
                res.firstname
            );
			$("#rider-inspect-section .add-blk .add-frm input[name='lastname']").val(
                res.lastname
            );
			$("#rider-inspect-section .add-blk .add-frm input[name='expdate']").val(
                getPrettyDate(res.exp_date)
            );
			$("#rider-inspect-section .add-blk .add-frm input[name='fmtdate']").val(
                res.exp_date
            );
            $("#rider-inspect-section .add-blk .add-frm input[name='mode']").val('update');
            $("#rider-inspect-section .add-blk .add-frm input[name='memberid']").attr("disabled", "disabled");
            $("#rider-inspect-section .add-btn").hide('fast', function() {
		        $('#rider-inspect-section .add-blk').show('slow'); 
                $("#rider-inspect-section .add-blk .add-frm input[name='firstname']").focus();          
            });
        }
        $('body').removeClass('waiting');
	}   

	function remove_rider_cb(response) {
        $('body').removeClass('waiting');
        var res = JSON.parse(response);
		if (res.error) {
            open_error_dialog(res.error);
		}
		else {
            load_rider_table();
        }
	}   

    function load_rider_table() {
        var memberid = $("#rider-inspect-section .search-frm input[name='memberid']").val().trim();
        var lastname = $("#rider-inspect-section .search-frm input[name='lastname']").val().trim();
        var firstname = $("#rider-inspect-section .search-frm input[name='firstname']").val().trim();
        var active = false;
        if ($("#rider-inspect-section .search-frm input[name='active']").is(':checked')) {
            active = true;
        }
        if (memberid.length > 0 || lastname.length > 0 || firstname.length > 0) {
            var action = $('#rider-inspect-section .search-frm').attr('action');
            var data = {
                'action': 'pwtc_mileage_lookup_riders',
                'memberid': memberid,
                'lastname': lastname,
                'firstname': firstname,
                'active': active
            };
            $('body').addClass('waiting');
            $.post(action, data, lookup_riders_cb); 
        }
        else {
            $('#rider-inspect-section .riders-div').empty();  
        }  
    }

    $('#rider-inspect-section .search-frm').on('submit', function(evt) {
        evt.preventDefault();
        load_rider_table();
    });

    $('#rider-inspect-section .search-frm .reset-btn').on('click', function(evt) {
        evt.preventDefault();
        $("#rider-inspect-section .search-frm input[type='text']").val(''); 
        $('#rider-inspect-section .riders-div').empty();
    });

    $("#rider-inspect-section .add-btn").on('click', function(evt) {
        open_confirm_dialog(
			'WARNING: to add a new rider, you must have a valid ID which is assigned by the membership secretary. Do you want to continue?', 
			function() {
                $("#rider-inspect-section .add-blk .add-frm input[type='submit']").val('Create');
		        $("#rider-inspect-section .add-blk .add-frm input[type='text']").val(''); 
		        $("#rider-inspect-section .add-blk .add-frm input[type='hidden']").val(''); 
                $("#rider-inspect-section .add-blk .add-frm input[name='mode']").val('insert');
                $("#rider-inspect-section .add-blk .add-frm input[name='memberid']").removeAttr("disabled");
                $("#rider-inspect-section .add-btn").hide('fast', function() {
		            $('#rider-inspect-section .add-blk').show('slow'); 
                    $("#rider-inspect-section .add-blk .add-frm input[name='memberid']").focus();          
                });
			}
		);
    });

	$("#rider-inspect-section .add-blk .cancel-btn").on('click', function(evt) {
		$('#rider-inspect-section .add-blk').hide('slow', function() {
            $("#rider-inspect-section .add-btn").show('fast'); 
        });
    });

    $("#rider-inspect-section .add-blk .add-frm input[name='expdate']").datepicker({
  		dateFormat: 'D M d yy',
		altField: "#rider-inspect-section .add-blk .add-frm input[name='fmtdate']",
		altFormat: 'yy-mm-dd',
		changeMonth: true,
      	changeYear: true
	});

    $('#rider-inspect-section .add-blk .add-frm').on('submit', function(evt) {
        evt.preventDefault();
        var action = $('#rider-inspect-section .add-blk .add-frm').attr('action');
        var data = {
			'action': 'pwtc_mileage_create_rider',
            'nonce': '<?php echo wp_create_nonce('pwtc_mileage_create_rider'); ?>',
            'mode': $("#rider-inspect-section .add-blk .add-frm input[name='mode']").val(),
			'member_id': $("#rider-inspect-section .add-blk .add-frm input[name='memberid']").val(),
			'lastname': $("#rider-inspect-section .add-blk .add-frm input[name='lastname']").val(),
			'firstname': $("#rider-inspect-section .add-blk .add-frm input[name='firstname']").val(),
			'exp_date': $("#rider-inspect-section .add-blk .add-frm input[name='fmtdate']").val()
		};
        $('body').addClass('waiting');
        $.post(action, data, create_rider_cb);
    });

    $("#rider-inspect-section .search-frm input[type='text']").val('');
    
    $("#rider-inspect-section .search-frm input[name='memberid']").focus();

});
</script>
<?php
if ($running_jobs > 0) {
?>
    <div class="notice notice-warning"><p><strong>
        A database batch operation is currently running!
    </strong></p></div>
<?php
} else {
?>
    <div id='rider-inspect-section'>
        <p>A rider that is a recent new member may not be in the rider list, use this page to add them. These changes will be updated when a new UPDMEMBS.DBF file from the membership secretary is uploaded.</p>
        <div class='search-sec'>
        <p><strong>Enter search parameters to find riders.</strong>
        	<form class="search-frm stacked-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                <span>ID</span>
                <input name="memberid" type="text"/>
                <span>First Name</span>
                <input name="firstname" type="text"/>
                <span>Last Name</span>
                <input name="lastname" type="text"/>
		        <span>Active Members Only</span>
		        <span class="checkbox-wrap">
			        <input type="checkbox" name="active"/>
		        </span>
				<input class="button button-primary" type="submit" value="Search"/>
				<input class="reset-btn button button-primary" type="button" value="Reset"/>
			</form>
        </p>
        </div>

        <p><div><button class="add-btn button button-primary button-large">New</button>
		<span class="add-blk popup-frm initially-hidden">
			<form class="add-frm stacked-form" action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
                <span>ID</span>
                <input name="memberid" type="text" required/>
                <span>First Name</span>
                <input name="firstname" type="text" required/>
                <span>Last Name</span>
                <input name="lastname" type="text" required/>
                <span>Expiration Date</span>
                <input name="expdate" type="text" required/>
				<input type="hidden" name="fmtdate"/>
				<input type="hidden" name="mode"/>
				<input class="button button-primary" type="submit" value="Create"/>
				<input class="cancel-btn button button-primary" type="button" value="Cancel"/>
			</form>
		</span></div></p>

        <p><div class="riders-div"></div></p>
    </div>
<?php
    include('admin-rider-lookup.php');
}
}
?>
</div>
<?php
