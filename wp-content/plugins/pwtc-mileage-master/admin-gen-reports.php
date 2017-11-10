<?php
if (!current_user_can($capability)) {
    return;
}
?>
<script type="text/javascript">
jQuery(document).ready(function($) { 

	function populate_report_table(data, header) {
        $('#report-results-section .results-div').empty();
        if (data.length > 0) {
            var str = '<table class="rwd-table"><tr>';
            header.forEach(function(item) {
                str += '<th>' + item + '</th>';
            });
            str += '</tr></table>';
            $('#report-results-section .results-div').append(str);
            data.forEach(function(row) {
                str = '<tr>';
                var count = 0;
                row.forEach(function(item) {
                    str += '<td data-th="' + header[count]+ '">' + item + '</td>';
                    count++;
                });
                str += '</tr>';
                $('#report-results-section .results-div table').append(str);
            });
        }
        else {
            $('#report-results-section .results-div').append(
                '<span class="empty-tbl">No records found.</span>');
        }
    }

    function show_report_section(title, header, data) {
        $('#report-results-section h2').html(title);
        populate_report_table(data, header);
        $('#report-main-section').hide('fast', function() {
            $('#report-results-section').fadeIn('slow');
            $('#report-results-section .back-btn').focus(); 
        });
    }

    function return_main_section() {
        $('#report-results-section').fadeOut('slow', function() {
            $('#report-main-section').show('fast');
        });        
    }

	function generate_report_cb(response) {
        var res = JSON.parse(response);
        if (res.error) {
            open_error_dialog(res.error);
        }
        else {
            show_report_section(res.title, res.header, res.data);
            if (history.pushState) {
                history.pushState(res.state, '');
            }
        }
        $('body').removeClass('waiting');
	}   

	function restore_report_cb(response) {
        var res = JSON.parse(response);
        if (res.error) {
            open_error_dialog(res.error);
        }
        else {
            show_report_section(res.title, res.header, res.data);
        }
        $('body').removeClass('waiting');
	}   

    $('#report-main-section .ride-mileage a').on('click', function(evt) {
        evt.preventDefault();
        if ($('#report-main-section .download-slt').val() != 'no') {
            $('#report-main-section .download').html(
                '<form method="post">' + 
                '<input type="hidden" name="' + $('#report-main-section .download-slt').val() + '"/>' +
                '<input type="hidden" name="report_id" value="' + $(this).attr('report-id') + '"/>' +
                '<input type="hidden" name="sort" value="' + 
                    $('#report-main-section .mileage-sort-slt').val() + '"/>' +
                '</form>');
            $('#report-main-section .download form').submit();
        }
        else {
            var action = '<?php echo admin_url('admin-ajax.php'); ?>';
            var data = {
                'action': 'pwtc_mileage_generate_report',
                'report_id': $(this).attr('report-id'),
                'sort': $('#report-main-section .mileage-sort-slt').val()
            };
            $('body').addClass('waiting');
            $.post(action, data, generate_report_cb);
        }
    });

    $('#report-main-section .ride-leader a').on('click', function(evt) {
        evt.preventDefault();
        if ($('#report-main-section .download-slt').val() != 'no') {
            $('#report-main-section .download').html(
                '<form method="post">' + 
                '<input type="hidden" name="' + $('#report-main-section .download-slt').val() + '"/>' +
                '<input type="hidden" name="report_id" value="' + $(this).attr('report-id') + '"/>' +
                '<input type="hidden" name="sort" value="' + 
                    $('#report-main-section .leader-sort-slt').val() + '"/>' +
                '</form>');
            $('#report-main-section .download form').submit();
        }
        else {
            var action = '<?php echo admin_url('admin-ajax.php'); ?>';
            var data = {
                'action': 'pwtc_mileage_generate_report',
                'report_id': $(this).attr('report-id'),
                'sort': $('#report-main-section .leader-sort-slt').val()
            };
            $('body').addClass('waiting');
            $.post(action, data, generate_report_cb);
        }
    });

    $('#report-main-section .specific-rider a').on('click', function(evt) {
        evt.preventDefault();
        var memberid = $('#report-main-section .riderid').html();
        if (memberid === '') {
            open_error_dialog('You must first select a rider before choosing this report.');
        }
        else {
            if ($('#report-main-section .download-slt').val() != 'no') {
                $('#report-main-section .download').html(
                    '<form method="post">' + 
                    '<input type="hidden" name="' + $('#report-main-section .download-slt').val() + '"/>' +
                    '<input type="hidden" name="report_id" value="' + $(this).attr('report-id') + '"/>' +
                    '<input type="hidden" name="member_id" value="' + memberid + '"/>' +
                    '<input type="hidden" name="name" value="' + 
                        $('#report-main-section .ridername').html() + '"/>' +
                    '</form>');
                $('#report-main-section .download form').submit();
            }
            else {
                var action = '<?php echo admin_url('admin-ajax.php'); ?>';
                var data = {
                    'action': 'pwtc_mileage_generate_report',
                    'report_id': $(this).attr('report-id'),
                    'member_id': memberid,
                    'name': $('#report-main-section .ridername').html()
                };
                $('body').addClass('waiting');
                $.post(action, data, generate_report_cb);
            }
        }
    });

    $('#report-main-section .awards a').on('click', function(evt) {
        evt.preventDefault();
        if ($('#report-main-section .download-slt').val() != 'no') {
            $('#report-main-section .download').html(
                '<form method="post">' + 
                '<input type="hidden" name="' + $('#report-main-section .download-slt').val() + '"/>' +
                '<input type="hidden" name="report_id" value="' + $(this).attr('report-id') + '"/>' +
                '</form>');
            $('#report-main-section .download form').submit();
        }
        else {
            var action = '<?php echo admin_url('admin-ajax.php'); ?>';
            var data = {
                'action': 'pwtc_mileage_generate_report',
                'report_id': $(this).attr('report-id')
            };
            $('body').addClass('waiting');
            $.post(action, data, generate_report_cb);
        }
    });

    $('#report-main-section .members a').on('click', function(evt) {
        evt.preventDefault();
        if ($('#report-main-section .download-slt').val() != 'no') {
            $('#report-main-section .download').html(
                '<form method="post">' + 
                '<input type="hidden" name="' + $('#report-main-section .download-slt').val() + '"/>' +
                '<input type="hidden" name="report_id" value="' + $(this).attr('report-id') + '"/>' +
                '</form>');
            $('#report-main-section .download form').submit();
        }
        else {
            var action = '<?php echo admin_url('admin-ajax.php'); ?>';
            var data = {
                'action': 'pwtc_mileage_generate_report',
                'report_id': $(this).attr('report-id')
            };
            $('body').addClass('waiting');
            $.post(action, data, generate_report_cb);
        }
    });

    $('#report-main-section .lookup-btn').on('click', function(evt) {
        lookup_pwtc_riders(function(riderid, name) {
            $('#report-main-section .riderid').html(riderid);
            $('#report-main-section .ridername').html(name);            
        });       
    });

    $('#report-results-section .back-btn').on('click', function(evt) {
        //evt.preventDefault();
        if (history.pushState) {
            history.back();
        }
        else {
            return_main_section();
        }
    });

    $('#report-main-section .download-slt').focus();

    if (history.pushState) {
        $(window).on('popstate', function(evt) {
            var state = evt.originalEvent.state;
            if (state !== null) {
                //console.log("Popstate event, state is " + JSON.stringify(state));
                var action = '<?php echo admin_url('admin-ajax.php'); ?>';
                $('body').addClass('waiting');
                $.post(action, state, restore_report_cb);
            }
            else {
                //console.log("Popstate event, state is null.");
                return_main_section();
            }
        });
    }
    else {
        //console.log("history.pushState is not supported");
    }

});
</script>
<div class="wrap">
	<h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
if ($running_jobs > 0) {
?>
    <div class="notice notice-warning"><p><strong>
        A database batch operation is currently running!
    </strong></p></div>
<?php
} else {
?>
    <div id='report-main-section'>
        <p>Use this page to view and download various mileage database reports.</p>
        <p>Download: 
            <select class='download-slt'>
                <option value="no" selected>No</option> 
                <option value="export_pdf">PDF file</option> 
                <option value="export_csv">CSV file</option>
            </select>
        </p>
        <div class='report-sec'>
        <h3><?php echo(intval(date('Y'))-1); ?> Award Reports</h3>
        <div class='awards'>
            <div><a href='#' report-id='award_achvmnt'>Accumulative mileage achievement</a></div>
            <div><a href='#' report-id='award_top_miles'>Top annual mileage</a></div>
            <div><a href='#' report-id='award_members'>Member annual and accumulative mileage</a></div>
            <div><a href='#' report-id='award_leaders'>Ride leaders</a></div>
        </div>
        </div>
        <div class='report-sec'>
        <h3>Ride Mileage Reports</h3>
        <p>Sort by: 
            <select class='mileage-sort-slt'>
                <option value="name">Name</option> 
                <option value="mileage" selected>Mileage</option>
            </select>
        </p>
        <div class='ride-mileage'>
            <div><a href='#' report-id='ytd_miles'>Year-to-date mileage</a></div>
            <div><a href='#' report-id='ly_miles'><?php echo(intval(date('Y'))-1); ?> mileage</a></div>
            <div><a href='#' report-id='lt_miles'>Lifetime mileage</a></div>
        </div>
        </div>
        <div class='report-sec'>
        <h3>Ride Leader Reports</h3>
        <p>Sort by: 
            <select class='leader-sort-slt'>
                <option value="name">Name</option> 
                <option value="rides_led" selected>Rides Led</option>
            </select>
        </p>
        <div class='ride-leader'>
            <div><a href='#' report-id='ytd_led'>Year-to-date ride leaders</a></div>
            <div><a href='#' report-id='ly_led'><?php echo(intval(date('Y'))-1); ?> ride leaders</a></div>
            <div><a href='#' report-id='pre_ly_led'>Pre-<?php echo(intval(date('Y'))-1); ?> ride leaders</a></div>
        </div>
        </div>
        <div class='report-sec'>
        <h3>Individual Rider Reports</h3>
        <p>
            <button class="lookup-btn button button-primary">Lookup Rider</button>&nbsp;
            <strong><label class="riderid"/></label>&nbsp;<label class="ridername"></label></strong>
        </p>
        <div class='specific-rider'>
            <div><a href='#' report-id='ytd_rides'>Year-to-date rides</a></div>
            <div><a href='#' report-id='ly_rides'><?php echo(intval(date('Y'))-1); ?> rides</a></div>
            <div><a href='#' report-id='ytd_rides_led'>Year-to-date rides led</a></div>
            <div><a href='#' report-id='ly_rides_led'><?php echo(intval(date('Y'))-1); ?> rides led</a></div>
        </div>
        </div>
        <div class='report-sec'>
        <h3>Member Reports</h3>
        <div class='members'>
            <div><a href='#' report-id='dup_members'>Duplicate members</a></div>
        </div>
        </div>
        <div class='download'></div>
    </div>
    <div id='report-results-section' class="initially-hidden">
        <p><button class='back-btn button button-primary button-large'>Back</button></p>
		<p><h2></h2></p>
        <p><div class="results-div"></div></p>
    </div>
<?php
    include('admin-rider-lookup.php');
}
?>
</div>
<?php
