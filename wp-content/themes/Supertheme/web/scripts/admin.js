jQuery(function() {
    // disable preview/submit buttons until validation
    jQuery('#schedule-rides-preview').attr('disabled','disabled');
    jQuery('#schedule-rides-create').attr('disabled','disabled');

    // initial values
    var every = jQuery('#acf-field_57c498d11ff63').val();
    var from = new Date(jQuery('#acf-field_57c4982a0c2a5+input').val());
    var until = new Date(jQuery('#acf-field_57c498370c2a6+input').val());
    var events = [];

    // update values on change
    jQuery('#acf-field_57c498d11ff63').on('change', function () {
        every = jQuery(this).val();
        //console.log("every now is: " + every);
        validate();
    });
    jQuery('#acf-field_57c4982a0c2a5+input').datepicker().on('input change', function(){
        from = new Date(jQuery(this).val());
        //console.log("from now is: " + from);
        validate();
    });
    jQuery('#acf-field_57c498370c2a6+input').datepicker().on('input change', function(){
        until = new Date(jQuery(this).val());
        //console.log("until now is: " + until);
        validate();
    });
    validate();

    // validate all three fields and enable the preview button
    function validate(){
        console.log("validating");
        if(!every || isNaN(from.getTime()) || !from || isNaN(until.getTime()) || !until) {
            console.log("missing values");
            jQuery('#schedule-rides-preview').attr('disabled','disabled');
            return;
        } else {
            console.log("validating dates");
            if(from > until) {
                console.log("from is later than until");
                jQuery('#schedule-rides-preview').attr('disabled','disabled');
            }
        }
        preview();
    }

    function buildPreviewTable()
    {
        var rows = "";
        for(var i = 0; i < events.length; i++) {
            rows += "<tr>"
                    + "<td>"
                        + "<label>"
                        + "<input type='checkbox' name='events[]' checked='checked' value='"
                            + events[i].getFullYear()
                            + "/"
                            + (events[i].getMonth()+1)
                            + "/"
                            + events[i].getDate()
                        + "'>"
                        + (events[i].getMonth()+1)
                        + "/"
                        + events[i].getDate()
                        + "/"
                        + events[i].getFullYear()
                        + "</label>"
                    + "</td>" +
                "</tr>"
        }
        jQuery("#preview").html(
            '<table class="wp-list-table widefat fixed striped posts">'
                + "<thead><tr><th>Schedule this date?</th></tr></thead>"
                + "<tbody>" + rows + "</tbody>"
            + '</table>'
        );
        jQuery('#schedule-rides-create').attr('disabled', false);
    }

    // on preview
    function preview(){
        var every_milliseconds;
        var time_between = until.getTime() - from.getTime();

        // get milliseconds for every value to use when calculating event dates
        // days * hours * minutes * seconds * milliseconds
        switch(every) {
            case 'day':
                every_milliseconds = 1 * 24 * 60 * 60 * 1000;
                break;
            case 'week':
                every_milliseconds = 7 * 24 * 60 * 60 * 1000;
                break;
            case 'month':
                every_milliseconds = 30 * 24 * 60 * 60 * 1000;
                break;
        }
        var number_of_events = Math.floor(time_between / every_milliseconds) + 1;

        // clear and populate events
        events = [];
        events.push(from);
        for (var i = 1; i < number_of_events; i++) {
            events.push(new Date(from.getTime() + every_milliseconds * i));
        }
        buildPreviewTable();
    }
});