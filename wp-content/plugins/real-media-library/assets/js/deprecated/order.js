"use strict";

/* global jQuery rmlOpts wp RMLisDefined */

/**
 * Make the draggable rows not draggable. Make the
 * list or grid view sortable.
 * 
 * @param mode boolean
 * @see this::_orderModeReInitItems()
 */
window.rml.library.orderMode = function(mode) {
    var $ = jQuery;
    
    // Show error if the current view has filters.
    if ($("body").hasClass("rml-view-gallery-filter-on")) {
        window.rml.sweetAlert("Oops...", rmlOpts.lang.orderFailedFilterOn, "error");
        return;
    }
    
    if (mode) {
        $("body").addClass("order-mode");
        this._orderModeReInitItems();
    }else{
        $("body").removeClass("order-mode");
        this._orderModeDestroy();
        
        // Make it draggable and droppable at the beginning
        window.rml.library.droppable();
        window.rml.library.draggable();
    }
};
window.rml.library._orderModeReInitItems = function() {
    var $ = jQuery;
    if (!$("body").hasClass("order-mode")) {
        return false;
    }
    
    this._orderModeDestroy();
    $( ".wp-list-table.media tbody, ul.attachments" ).sortable({
        appendTo: 'body',
        tolerance: "pointer",
        scrollSensitivity: 50,
        scrollSpeed: 50,
        distance: 10,
        cursor: 'move',
        start: function(e, ui){
            ui.placeholder.height(ui.helper[0].scrollHeight);
            var $ = jQuery;
            
            // The last ID (grid mode is done in the ajax request)
            if ($("body").hasClass("upload-php-mode-list")) {
                window.rml.library.lastIdInView = $(".wp-list-table.media tbody tr:last").find('input[name="media[]"]').val();
            }
            
        },
        update: function(e, ui) {
            var next = ui.item.next(), nextId, attachmentId;
            
            // The next id
            if (typeof next.html() === "undefined") {
                nextId = false;
            }else{
                if ($("body").hasClass("upload-php-mode-list")) {
                    nextId = next.find('input[name="media[]"]').val();
                }else{
                    nextId = next.attr("data-id");
                }
            }
            
            // The current id
            if ($("body").hasClass("upload-php-mode-list")) {
                attachmentId = ui.item.find('input[name="media[]"]').val();
            }else{
                attachmentId = ui.item.attr("data-id");
            }
            
            window.rml.library.getActiveObject().addClass("needs-refresh");
            window.rml.hooks.call("attachmentOrder", [ attachmentId, next, nextId, e, ui ]);
        }
    });
    $( ".wp-list-table.media tbody, ul.attachments" ).disableSelection();
};
window.rml.library._orderModeDestroy = function() {
    var $ = jQuery;
    try {
        $("ul.attachments > li, .wp-list-table.media tbody tr").draggable("destroy");
        $( ".wp-list-table.media tbody, ul.attachments" ).sortable("destroy");
        $( '.list.rml-root-list a' ).droppable("destroy");
    }catch (e) {}
};

/**
 * Create the general order functions to draggable and
 * droppable the attachments. If dropped the order should
 * changed.
 * 
 * It uses the functions implemented in library.js
 * @see library::orderMode()
 * @see library::_orderModeReInitItems()
 * @see library::_orderModeDestroy
 * 
 * Create the hooks to order the media library.
 */
 
window.rml.hooks.register("ajaxComplete", function(e, args) {
    window.rml.library.lastIdInView = jQuery("ul.attachments > li:last").attr("data-id");
    window.rml.library._orderModeReInitItems();
});
 
window.rml.hooks.register("modifyMediaGridRequestForFolder/Type/2", function(e, args) {
    var $ = jQuery, options = args[0],
        original = args[1],
        foundFilter = false;
    
    // Make button to sort order visible
    $("body").addClass("rml-view-gallery");
    
    // check monthnum und year
    // post_mime_type = "all"; monthnum year
    try {
        if (RMLisDefined(original.data.query.post_mime_type)
            || RMLisDefined(original.data.query.monthnum)
            || RMLisDefined(original.data.query.year)) {
                if (original.data.query.post_mime_type != "all"
                    || original.data.query.monthnum > 0
                    || original.data.query.year > 0) {
                    foundFilter = true;
                }
            }
    }catch (e){}
    
    if (foundFilter) {
        $("body").addClass("rml-view-gallery-filter-on");
    }else{
        $("body").removeClass("rml-view-gallery-filter-on");
    }
    
    original.data.query.order = "ASC";
    original.data.query.orderby = "rml";
    
    var swallow = $.param(original.data);
    options.data = swallow;
});

window.rml.hooks.register("modifyMediaGridRequestForFolder/Type/0", function(e, args) {
    jQuery("body").removeClass("rml-view-gallery");
});

window.rml.hooks.register("attachmentOrder", function(e, args) {
    var data = {
        'action': 'rml_attachment_order',
        'nonce': rmlOpts.nonces.attachmentOrder,
        'attachmentId': args[0],
        'nextId': args[2],
        'lastId': window.rml.library.lastIdInView
    };
    var item = args[4].item;
    item.stop().fadeTo(250, 0.5);
    
    jQuery.post(
        rmlOpts.ajaxUrl, 
        data,
        function(response){
            item.stop().fadeTo(250, 1);
        }
    );
});

window.rml.hooks.register("viewSwitch/rml-folder-order", function(e) {
    window.rml.library.orderMode(!jQuery("body").hasClass("order-mode"));
});

/**
 * Override the default comperator for the gallery view
 * in media grid library.
 * 
 * @hook mediaGrid/Attachment/Add
 * @args [0] the post attachment
 * @args [1] the collection of attachments
 */
jQuery(document).ready(function() {
    // Add body class @redudant to main.js
    jQuery("body").addClass("upload-php-mode-" + rmlOpts.listMode);
    var wp = window.wp;
    if (jQuery("body").hasClass("upload-php-mode-grid") && RMLisDefined(wp) && RMLisDefined(wp.media) && RMLisDefined(wp.media.view)) {
        var orig = wp.media.view.Attachments;
        wp.media.view.Attachments = wp.media.view.Attachments.extend({
            initialize: function () {
                this.listenTo(this.collection, 'add', this.rmlAddOne);
                
                // call the original method
    			orig.prototype.initialize.apply(this,arguments);
    			
    			// comparator creation
    			var collection = this.collection;
    			this.collection.comparator = function(a, b, c) {
    			    if (collection.props.get("rml_folder") !== undefined) {
    			        if (typeof a.attributes.rmlGalleryOrder !== "undefined" &&
    			            typeof b.attributes.rmlGalleryOrder !== "undefined") {
    		                var aO = a.attributes.rmlGalleryOrder,
    		                    bO = b.attributes.rmlGalleryOrder;
    		                
    		                // Sort it as i reveice it from the ajax query
        			        if (aO < bO) {
        			            return -1;
        			        }else if (aO > bO) {
        			            return 1;
        			        }else{
        			            return 0;
        			        }
    		            }
    			    }
    			    
    			    var d = this.props.get("orderby"),
                    e = this.props.get("order") || "DESC",
                    f = a.cid,
                    g = b.cid;
                    return a = a.get(d), b = b.get(d), ("date" === d || "modified" === d) && (a = a || new Date, b = b || new Date), c && c.ties && (f = g = null), "DESC" === e ? wp.media.compare(a, b, f, g) : wp.media.compare(b, a, g, f);
    			};
            },
            rmlAddOne: function(post) {
                window.rml.hooks.call("mediaGrid/Attachment/Add", [ post, this.collection ]);
            }
        });
    }
});