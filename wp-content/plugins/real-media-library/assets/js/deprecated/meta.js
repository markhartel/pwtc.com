/**
 * List of hooks:
 * 
 * - folderMeta/loaded: Meta data loaded
 * - folderMeta/parsed: Meta dialog parsed
 * - folderMeta/save/failed: The changes failed to save
 * - folderMeta/save/success: The changes were successfully saved
 */

/* global jQuery rmlOpts RMLisDefined */

window.rml.hooks.register("afterInit", function() {
    var $ = jQuery;
    
    // Add listener to the more-buttons
    $(document).on("click", ".rml-cfbutton", function(e) {
        var link = $(this).parent(),
            id = link.attr("data-id"),
            name = link.find(".rml-name").html();
        
        window.rml.sweetAlert({
            title: '',
            text: '<i class="fa fa-circle-o-notch fa-spin fa-2x fa-fw" style="margin: 30px 0px 0px 0px;padding: 15px;"></i><br /><br />',
            html: true,
            showConfirmButton: false
        });
        
        $.ajax({
            url: rmlOpts.ajaxUrl,
            data: {
                action: "rml_meta_content",
                nonce: rmlOpts.nonces.metaContent,
                folderId: id
            },
            invokeData: {
                fid: id,
                name: name
            },
            success: function(response) {
                window.rml.hooks.call("folderMeta/loaded", [ response, this.invokeData, this ]);
            }
        });
        
        e.preventDefault();
        return false;
    });
});

/**
 * Add action button handler.
 */
window.rml.hooks.register("beforeInit noMediaLibrary", function() {
    var $ = jQuery;
    
    // Wipe data and action buttons
    $(document).on("click", ".rml-button-wipe, .sweet-alert a.actionbutton", function(e) {
        if (window.confirm(rmlOpts.lang.wipe)) {
            var id = $(this).attr("id"), useCallback = false;
            if (RMLisDefined(id)) {
                window.rml.hooks.call("folderMeta/action/" + id);
                useCallback = true; // Call hook for this function when data is finished
            }
            
            var button = $(this), method = button.attr("data-method");
            button.html("<i class=\"fa fa-circle-o-notch fa-spin\"></i>").prop("disabled", true);
            button.attr("disabled", "disabled"); // for <a>-tags
            
            var post = {
                action: $(this).attr("data-action"),
                nonce: rmlOpts.nonces[$(this).attr("data-nonce-key")],
                method: $(this).attr("data-method")
            };

            jQuery.ajax({
                url: rmlOpts.ajaxUrl,
                data: post,
                invokeData: button,
                success: function(response) {
                    var _btn = this.invokeData;
                    if (useCallback) {
                        window.rml.hooks.call("folderMeta/actionFinished/" + id, response);
                    }
                    
                    if (response.success) {
                        _btn.html("<i class=\"fa fa-check\"></i> Done");
                    }else{
                        _btn.html("<i class=\"fa fa-error\"></i> Failed");
                    }
                }
            });
        }
        e.preventDefault();
        return false;
    });
});

/**
 * Create handler for failed changes. Show the error messages
 * at the top of the meta box.
 */
window.rml.hooks.register("folderMeta/save/failed", function(e, args) {
    window.rml.sweetAlert.enableButtons();
    
    var liHTML = "<li>" + args[0].data.errors.join("</li><li>") + "</li>";
    jQuery(".rml-meta-errors").html(liHTML).show();
    window.rml.library.sweetAlertPosition();
});

/**
 * Create handler for successful changes. Close the
 * dialog.
 * 
 * It also handles the rename process.
 */
window.rml.hooks.register("folderMeta/save/success", function(e, args) {
    window.rml.sweetAlert.close();
    
    var folderId = args[1].folderId;
        
    // Rename the folder object
    if (RMLisDefined(args[0].data.newSlug)) {
        var slug = args[0].data.newSlug,
            newName = args[1].name;
        window.rml.hooks.call("renamed", [ folderId, newName, slug ]); // @see library.js registered hooks
    }
});

/**
 * Create sweet alert with this folder meta.
 */
window.rml.hooks.register("folderMeta/loaded", function(e, args) {
    var fid = args[1].fid,
        name = args[1].name,
        response = args[0];
    
    // Show the custom fields dialog!
    window.rml.sweetAlert({
        title: name,
        text: response,
        html: true,
        confirmButtonText: rmlOpts.lang.save,
        cancelButtonText: rmlOpts.lang.close,
        closeOnConfirm: false,
        showLoaderOnConfirm: true,
        showCancelButton: true
    }, function() {
        var sweet = this;
        jQuery(".rml-meta-errors").hide();
        window.rml.library.sweetAlertPosition();
        
        // Serialize the meta data form
        var data = jQuery("form.rml-meta").serializeArray();
        var fields = { };
        jQuery.each( data, function( key, value ) {
            fields[value.name] = value.value;
        });
        
        fields.action = "rml_meta_save";
        fields.nonce = rmlOpts.nonces.metaSave;
        
        // Post it!
        jQuery.ajax({
            url: rmlOpts.ajaxUrl,
            type: 'POST',
            data: fields,
            success: function(response) {
                var hookName;
                if (response.success) {
                    hookName = "folderMeta/save/success";
                }else{
                    hookName = "folderMeta/save/failed";
                }
                
                /**
                 * Register to this two hooks above!
                 * 
                 * @param response The response from the server after saved
                 * @param fields The POST query
                 * @param this The ajax request
                 * @param sweet The dialog
                 * @param name The name of the folder
                 */
                window.rml.hooks.call(hookName, [ response, fields, this, sweet, name ]);
            }
        });
    });
    
    // Hook after dialog parsed
    setTimeout(function() {
        window.rml.hooks.call("folderMeta/parsed", args);
    }.bind(this), 500);
});

/**
 * ========================================================
 * 
 *          Use the media picker in the cover image.
 * 
 * ========================================================
 */
window.rml.hooks.register("folderMeta/parsed", function(args, $) {
    // Check the filter on in the media gallery
    var hasFilter = $("body").hasClass("rml-view-gallery-filter-on");
    
    var picker = $(".rml-meta-media-picker");
    alert(picker.size());
    if (picker.size() <= 0) {
        return;
    }
    
    picker.wpMediaPicker({
        label_add: rmlOpts.lang.metadata.coverImage.label_add,
        label_replace: rmlOpts.lang.metadata.coverImage.label_replace,
        label_remove: rmlOpts.lang.metadata.coverImage.label_remove,
        label_modal: rmlOpts.lang.metadata.coverImage.label_modal,
        label_button: rmlOpts.lang.metadata.coverImage.label_button,
        query: {
            post_mime_type: 'image'
        },
        onClose: function() {
            // Fix filter
            if (!hasFilter) {
                $("body").removeClass("rml-view-gallery-filter-on");
            }
        },
        htmlChange: function() {
            setTimeout(function() {
                picker.parents("td").find("i.fa").remove();
                $(".rml-meta-media-picker").parents("fieldset").show();
                window.rml.library.sweetAlertPosition();
            }.bind(this), 500);
        }
    });
});

/**
 * When action button for reset order / reindex order also
 * refresh the current view.
 */
window.rml.hooks.register("folderMeta/actionFinished/rml-meta-action-order-reset folderMeta/actionFinished/rml-meta-action-order-reindex", function() {
    window.rml.library.refresh();
});