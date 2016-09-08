"use strict";

/* global RMLisDefined RMLFormat rmlOpts jQuery localStorage wp navigator */

window.rml.library = {
    attachments: {},
    localStorageAllowed: false,
    lastIdInView: false,
    activeMediaFrame: false,
    
    /**
     * Functions responsible for the collapsable tree view on the left side.
     */
    tree: {
        sidebarsize: 250,
        cssInlineWorked: false,
        resizedOnce: false,
        
        init: function() {
            /**
             * Handler for clicking the expander to open childs.
             * First try, if localStorage is available
             */
            var test = 'test', $ = jQuery;
            try {
                localStorage.setItem(test, test);
                localStorage.removeItem(test);
                window.rml.library.localStorageAllowed = true;
                
                window.rml.library.tree.update();
                $(document).on("click", ".rml-expander", function(e) {
                    $(this).toggleClass("rml-open");
                    $(this).parent().toggleClass("rml-open");
                    window.rml.library.tree.toggle(parseInt($(this).parent().children("a").attr("data-id")));
                    //window.rml.library.sticky();
                    return false;
                });
                
                //this.initResize(true);
            } catch(e) {
                $(".list.rml-root-list li").addClass("rml-open");
                $(".rml-split-resize").hide();
            }
        },
        
        initResize: function(reload) {
            // Init resize when .rml-split-resize is visible
            if (window.rml.library.localStorageAllowed) {
                if (reload) {
                    this.sidebarsize = localStorage.getItem("rml-" + rmlOpts.blogId + "-sidebar-size");
                }
                var sidebarSize = this.sidebarsize;
                if (RMLisDefined(sidebarSize)) {
                    this.resize(sidebarSize);
                }
            }
        },
        
        /**
         * Resizes the sidebar.
         * 
         * @param width Width in px of the sidebar
         * @param save boolean if true it will be saved in localstorage
         */
        resize: function(width, save) {
            this.sidebarsize = width;
            
            var $ = jQuery;
            try {
                $(".rml-preview-css-inline").remove();
                var div = document.createElement( "div" );
                
                var inline = ".rml-container.rml-no-dummy{width:" + width + "px !important;}#wpbody-content{width:calc(100% - " + width + "px) !important;}";

                div.innerHTML = "<p>&nbsp;</p><style class=\"rml-preview-css-inline\">" + inline + "</style>";
                document.body.appendChild(div.childNodes[1]);
                this.cssInlineWorked = true;
            } catch (e) {
                jQuery(".rml-container.rml-no-dummy").css("width", width);
                jQuery('#wpbody-content').css("width", "calc(100% - " + width + "px)");
            }
            
            window.rml.library.sticky();
            if (save && window.rml.library.localStorageAllowed) {
                this.resizedOnce = true;
                window.localStorage.setItem("rml-" + rmlOpts.blogId + "-sidebar-size", width);
            }
        },
        
        /**
         * Get the current status of a folder expander.
         * 
         * @param id id of the folder
         */
        getStatus: function(id) {
            var value = window.localStorage.getItem('rml-' + rmlOpts.blogId + '-expand-' + id);
            if (RMLisDefined(value)) {
                return value == "true";
            }else{
                return true;
            }
        },
        
        /**
         * Toggle the tree expander status in the localstorage. Does
         * not apply to the DOM view.
         * 
         * @param id id of the folder
         */
        toggle: function(id) {
            var value = window.localStorage.getItem('rml-' + rmlOpts.blogId + '-expand-' + id);
            if (RMLisDefined(value)) {
                value = !(value == "true");
            }else{
                value = false;
            }
        
            window.localStorage.setItem('rml-' + rmlOpts.blogId + '-expand-' + id, value);
        },

        /**
         * Update the current directories with their parent expanders.
         */
        update: function() {
            var $ = jQuery;
            var expander, treeClass, treeStatus;
            $(".list.rml-root-list li .rml-expander").remove();
            $(".list.rml-root-list li").each(function() {
                if ($(this).children("ul").children("li").size() > 0) {
                    treeStatus = window.rml.library.tree.getStatus(parseInt($(this).children("a").attr("data-id")));
                    if (treeStatus) {
                        treeClass = 'rml-open';
                    }else{
                        treeClass = '';
                    }
                    $(this).addClass(treeClass);
                    expander = $("<div class=\"rml-expander " + treeClass + "\"><i class=\"fa fa-minus-square-o\"></i><i class=\"fa fa-plus-square-o\"></i></div>").appendTo($(this));
                }
            });
        }
    },
    
    /**
     * Checks if the current device is touch and returns
     * an array for mousedown mouseup and mousemove event names.
     */
    isTouch: function () {
      var isTouch = 'ontouchstart' in window || navigator.maxTouchPoints;
      if (isTouch) {
          return { touch: isTouch, up: "touchend", down: "touchstart", move: "touchmove" };
      }else{
          return { touch: isTouch, up: "mouseup", down: "mousedown", move: "mousemove" };
      }
    },
    
    /**
     * (Re)initialize the sticky floating left sidebar.
     */
    sticky: function(mode) {
        if (jQuery(window).width() < 667) {
            return;
        }
        
        var $ = jQuery, c = $(".rml-container.rml-no-dummy");
        if (c.data("sticky")) {
            c.hcSticky('reinit');
            return;
        }
        
        c.hcSticky({
           top: 32,
           offResolutions: -667
        });
        c.data("sticky", true);
    },
    
    /**
    * Updates the current type of folder. Can be
    * folder, collection or gallery.
    */
    updateActiveType: function() {
        var $ = jQuery;
        var active = window.rml.library.getActiveObject(),
            type = active.attr("data-type"), options = [
                { id: '#rml-add-new-folder', active: 1 },
                { id: '#rml-add-new-collection', active: 1 },
                { id: '#rml-add-new-gallery', active: 0 },
                { id: '#rml-do-nothing', active: 0 }
            ];
            
        if (RMLisDefined(type)) {
            if (type == "1") {
                options[0].active = 0;
                options[2].active = 1;
            }else if (type == "2") {
                options[0].active = 0;
                options[1].active = 0;
                options[2].active = 0;
                options[3].active = 1;
            }
        }
        
        for (var i = 0; i < options.length; i++) {
            if (options[i].active == 1) {
                $(options[i].id).show();
            }else{
                $(options[i].id).hide();
            }
        }
    },
    
    /**
    * Adds a create form.
    * 
    * @param type String folder|collection|gallery
    */
    prepareCreate: function(type) {
        var $ = jQuery;
        var active = window.rml.library.getActiveObject(),
            slug = active.attr("data-slug"),
            parentID;
        if (active.size() == 0 || !RMLisDefined(slug)) {
            parentID = -1;
            active = $(".rml-container .list.rml-root-list > ul");
        }else{
            parentID = active.attr("data-id");
            active = active.next(); // take ul list
            active.parents("li").addClass("rml-open");
        }
        
        // Detect icon variable
        var icon = 'fa fa-folder-open-o';
        if (type == 1) {
            icon = 'mwf-collection';
        }else if (type == 2) {
            icon = 'mwf-gallery';
        }
        
        var li = $('<li>\
                      <form data-type="' + type + '" data-create-rml="' + parentID + '">\
                        <a>\
                          <i class="' + icon + '"></i> \
                          <input type="text" />\
                          <button class="button-primary"><i class="fa fa-circle-o-notch fa-spin" style="display:none;"></i> OK</button>\
                        </a>\
                      </form>\
                    </li>').appendTo(active);
        li.find("input").focus();
        
        $(".uploader-inline").addClass("hidden");
    },
    
    /**
    * Toggle the edit mode (sort mode).
    * 
    * @param mode boolean
    */
    editMode: function(mode) {
        var $ = jQuery;
        
        // Remove opened input for creating new folder
        var form = $("form[data-create-rml]");
        if (form.size() > 0) {
            form.parent().remove();
        }
        
        if (mode) {
            window.rmlOldHTML = $(".rml-container .list.rml-root-list").html();
            $(".rml-container > .aio-wrap").addClass("edit-mode").removeClass("ready-mode");
            $("#wpbody-content").stop().fadeTo(200, 0.3);
            $(".rml-container .list.rml-root-list a").each(function() {
                $(this).attr("data-href", $(this).attr("href"));
                $(this).attr("href", "");
            });
            
            // Handler for sortable directories
            try {
                $( '.list.rml-root-list a' ).droppable( "destroy" );
            }catch (e) {}
            $('.rml-container .list.rml-root-list > ul').nestedSortable({
                handle: 'a[data-href]',
                items: 'li',
                listType: 'ul',
                tolerance: "pointer",
                toleranceElement: '> a',
                helper:	'clone',
                forceHelperSize: true,
                forcePlaceholderSize: true,
                doNotClear: true
            });
        }else{
            // Reset sortable and enable droppable
            try {
                $('.rml-container .list.rml-root-list > ul').sortable( "destroy" );
            }catch (e) {}
            setTimeout(function() {
                window.rml.library.droppable();
            }, 1000);
            
            $(".rml-container > .aio-wrap").addClass("ready-mode").removeClass("edit-mode");
            $("#wpbody-content").stop().fadeTo(200, 1);
            $(".rml-container .list.rml-root-list a").each(function() {
                $(this).attr("href", $(this).attr("data-href"));
                $(this).attr("data-href", "");
            });
        }
        
        this.sticky();
    },
    
    /**
    * Makes attachments in grid mode draggable with helper.
    */
    draggable: function() {
        var $ = jQuery;
        
        // Remove draggable on mobiles
        if ($(window).width() < 667 || $("body").hasClass("order-mode")) {
            return;
        }
        
        var draggableSettings = {
            revert: 'invalid',
            appendTo: 'body',
            //cursorAt: { top: 20, left: 35 },
            scrollSensitivity: 50,
            scrollSpeed: 50,
            distance: 10,
            helper: function() {
                var items = 0;
                if ($("body").hasClass("upload-php-mode-list")) {
                    // Count list
                    items = $('input[name="media[]"]:checked').size();
                }else{
                    // Count grid
                    if ($(".media-frame").hasClass("mode-select")) {
                        $("ul.attachments > li.selected").each(function() {
                            items++;
                        });
                    }
                }
                if (items == 0) {
                    items++;
                }
                
                var label = items > 1 ? rmlOpts.lang.moveMultipleFiles : rmlOpts.lang.moveSingleFile;
                return $('<div class="attachment-move"><i class="fa fa-arrow-right" style="margin-right:5px;"></i> ' + RMLFormat(label, items) + '</div>');
            }
        };
        
        window.rml.hooks.call("draggable", draggableSettings);
        $("ul.attachments > li, .wp-list-table.media tbody tr").draggable(draggableSettings);
    },
    
    /**
     * Handler for droppable areas. When dropping a attachment item
     * from grid mode move it to the folder.
     * 
     * It also handles bulk selected items.
     */
    droppable: function() {
        var $ = jQuery;
        
        // Remove draggable on mobiles
        if ($("body").hasClass("order-mode")) {
            return;
        }
        
        $( '.list.rml-root-list a' ).droppable({
            activeClass: "ui-state-default",
            hoverClass: "ui-state-hover",
            tolerance: "pointer",
            accept: function( ui ) {
                // A Collection can not have any attachments
                if ($(this).attr("data-type") == "1") {
                    return false;
                }
                
                // A gallery only contains images
                if ($(this).attr("data-type") == "2") {
                    var foundInvalid = false;
                    // List-mode
                    if ($("body").hasClass("upload-php-mode-list")) {
                        var trs = $('input[name="media[]"]:checked');
                        
                        // Multiselect
                        if (trs.size() > 0) {
                            trs.each(function() {
                                if (!$(this).parents("tr").find(".media-icon").hasClass("image-icon")) {
                                    foundInvalid = true;
                                    return false;
                                }
                            });
                        }else{
                            // One selected
                            if (!$(ui.context).find(".media-icon").hasClass("image-icon")) {
                                foundInvalid = true;
                            }
                        }
                        
                        
                    }else{
                        // Multiselect
                        if ($(".media-frame").hasClass("mode-select")) {
                            $("ul.attachments > li.selected").each(function() {
                                if (!$(this).children(".attachment-preview").hasClass("type-image")) {
                                    foundInvalid = true;
                                    return false;
                                }
                            });
                        }else{ // One selected
                            if (!$(ui.context).children(".attachment-preview").hasClass("type-image")) {
                                foundInvalid = true;
                            }
                        }
                    }
                    
                    if (foundInvalid) {
                        return false;
                    }
                }
                
                return true;
            },
            drop: function( event, ui ) {
                if ($(this).hasClass("active") || $(".rml-container > .aio-wrap").hasClass("edit-mode")) {
                    return;
                }
                $(this).addClass("needs-refresh");
                
                var folderObj = $(this),
                    folderId = folderObj.attr("data-id"), items = [], id,
                    isAllFiles = window.rml.library.getActiveObject().hasClass("rml-type-3");
                    
                // Get items
                if ($("body").hasClass("upload-php-mode-list")) {
                    var trs = $('input[name="media[]"]:checked');
                    if (trs.size() > 0) {
                        trs.each(function() {
                            id = parseInt($(this).attr("value"));
                            items.push(id);
                            $(this).parents("tr").addClass("rml-just-removed").fadeTo(500, 0.3);
                        });
                    }else{
                        id = parseInt(ui.draggable.find('input[name="media[]"]').attr("value"));
                        items.push(id);
                        ui.draggable.addClass("rml-just-removed").fadeTo(500, 0.3);
                    }
                }else{
                    if ($(".media-frame").hasClass("mode-select")) {
                        $(this).addClass("changed clicked-once");
                        $("ul.attachments > li.selected").each(function() {
                            id = $(this).attr("data-id");
                            items.push(id);
                            $(this).remove();
                        });
                    }else{
                        id = ui.draggable.attr("data-id");
                        items = [ id ];
                        ui.draggable.remove();
                    }
                }
                
                // The function to progress the move
                var doIt = function() {
                    window.rml.hooks.call("move", [ items, folderId ]);
                    
                    // Add loader to folder
                    var loaderContainer = folderObj.children("i.fa");
                    loaderContainer.addClass("fa-circle-o-notch fa-spin");
                    
                    jQuery.post(
                        rmlOpts.ajaxUrl, 
                        {
                            'action': 'rml_bulk_move',
                            'nonce': rmlOpts.nonces.bulkMove,
                            'ids' : items,
                            'to' : folderId
                        },
                        function(response){
                            if ($("body").hasClass("upload-php-mode-list")) {
                                // Move on table list mode
                                $(".rml-just-removed").remove();
                                
                                // Add no media
                                if ($(".wp-list-table.media tbody tr").size() <= 0) {
                                    $(".wp-list-table.media tbody").html('<tr class="no-items"><td class="colspanchange" colspan="6">' + rmlOpts.lang.noMedia + '</td></tr></tbody>');
                                }
                            }
                            
                            window.rml.hooks.call("moved", [ items, folderId ]);
                            loaderContainer.removeClass("fa-circle-o-notch fa-spin");
                        }
                    );
                };
                
                if (isAllFiles) {
                    // Give a warning that this is from different sources
                    window.rml.sweetAlert({   
                        title: rmlOpts.lang.deleteConfirmTitle,   
                        text: rmlOpts.lang.moveFromAllConfirmText,   
                        type: "warning",   
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",   
                        confirmButtonText: rmlOpts.lang.moveFromAllConfirmSubmit,   
                        cancelButtonText: rmlOpts.lang.deleteConfirmCancel,
                    }, function(){
                        doIt();
                    });
                }else{
                    doIt();
                }
            }
        });
    },
    
    /**
     * Refresh the page. Detect if it is in
     * grid mode and refresh via AJAX. Otherwise, refresh
     * full page.
     */
    refresh: function() {
        var $ = jQuery;
        
        if ($("body").hasClass("upload-php-mode-grid")) {
            try {
                if (RMLisDefined(this.activeMediaFrame.content)) {
                    this.activeMediaFrame.content.get().collection.props.set({ignore: (+ new Date())});
                }else{
                    window.location.reload();
                }
            }catch (e) {
                console.log(e);
                window.location.reload();
            }
        }else{
            window.location.reload();
        }
    },
    
    /**
     * Click handler for switching the dir.
     * If the function does not work, it will return
     * false and refresh the current view.
     * 
     * @param obj <a> object of valid folder
     * @return true or false
     */
    switchFolder: function(obj) {
        var $ = jQuery;
        window.rml.library.getActiveObject().removeClass("active");
        obj.addClass("active");
        this.updateActiveType();
        
        var id = obj.attr("data-id");
        if (typeof id !== "undefined"
            && id.length > 0
            && !obj.hasClass("changed")
            ) {
                var type = obj.attr("data-type");
                
                // Hide order mode when not entering a gallery
                if (!(type == "2" && $("body").hasClass("order-mode"))) {
                    this.orderMode(false);
                }
                
                // If it is a collection make the content opacity
                if (type != "1") {
                    $("#wpbody-content").stop().fadeTo(200, 1);
                    $('#media-attachment-folder-filters').val(id).change();
                }else{
                    $("#wpbody-content").stop().fadeTo(200, 0.3);
                }
                
                $(".rml-container")
                    .removeClass("rml-active-type-none rml-active-type-0 rml-active-type-1 rml-active-type-2")
                    .addClass("rml-active-type-" + ((type == "" || typeof type === "undefined") ? "none" : type));
                
                window.rml.hooks.call("switchFolder", [ obj, id, ((type == "" || typeof type === "undefined") ? "none" : type) ] );
                
                obj.addClass("clicked-once");
                setTimeout(function() {
                    // Update if it needs a refresh
                    if (obj.hasClass("needs-refresh")) {
                        obj.removeClass("needs-refresh");
                        window.rml.library.refresh();
                    }
                    
                    window.rml.library.draggable();
                }, 500);
                return true;
        }else{
            this.refresh();
            return false;
        }
    },
    
    /**
     * Initialize the media library. Add a filter to the
     * "Insert media" dialog.
     */
    init: function() {
        if (!window.wp || !window.wp.media || !rmlOpts.namesSlug) {
    		return;
    	}
    	
    	var media = window.wp.media;
    	var names = rmlOpts.namesSlug.names;
    	var slugs = rmlOpts.namesSlug.slugs;
    
    	var folderFilter = media.view.AttachmentFilters.extend({
    	    id:        'media-attachment-folder-filters',
    	    
    		createFilters: function() {
    			var filters = {};
    			
    			// default "all" filter, shows all tags
    			filters.all = {
    				text:  "All",
    				props: {
    					rml_folder: ""
    				},
    				priority: 10
    			};
    			
    			// create a filter for each tag
    			var i;
    			for (i = 0;i<names.length;i++) {
    				filters[slugs[i]] = {
    					// tag name
    					text:  names[i],
    					props: {
    						rml_folder: slugs[i]
    					},
    					priority: 20+i
    				};
    
    			}
    			
    			this.filters = filters;
    		}
    	});
    	
    	/* global mlaModal _ */
    	// backup the method
    	var orig = wp.media.view.AttachmentsBrowser;
	    wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
    		createToolbar: function() {
    			// call the original method
    			if (RMLisDefined(orig.__super__.createToolbar)) {
    			    orig.__super__.createToolbar.apply(this, arguments);
    			}else{
    			    orig.prototype.createToolbar.apply(this,arguments);
    			}
    			
    			media.model.Query.defaultArgs.filterSource = 'filter-media-taxonomies';
    			
    			// add our custom filter
    			this.toolbar.set('rml_folder', new folderFilter({
    				controller: this.controller,
    				model:      this.collection.props,
    				priority:   -75
    			}).render() );
    			
    			if (window.rml.hooks.exists("attachmentsBrowser")) {
    			    window.rml.hooks.call("attachmentsBrowser", [this.toolbar, this, orig]);
    			}else{
        			// Compatibility with media library assistent
        			// This is a workaround because the mla calls already the attachmentsbrowser extend
        			// This code is compressed from the original MLA plugin v2.24
        			try {
            			if (RMLisDefined(mlaModal)) {
            			    var filters,state=this.controller._state;mlaModal.settings.state=state,mlaModal.settings.$el=this.controller.$el,"undefined"==typeof mlaModal.settings.query[state]&&(mlaModal.settings.query[state]=_.clone(mlaModal.settings.query.initial),mlaModal.settings.query[state].searchFields=_.clone(mlaModal.settings.query.initial.searchFields)),mlaModal.utility.mlaAttachmentsBrowser=this,filters=this.options.filters,"all"===filters&&mlaModal.settings.enableMimeTypes&&(this.toolbar.unset("filters",{silent:!0}),this.toolbar.set("filters",new wp.media.view.AttachmentFilters.Mla({controller:this.controller,model:this.collection.props,priority:-80}).render())),"uploaded"===filters&&mlaModal.settings.enableMimeTypes&&(this.toolbar.unset("filters",{silent:!0}),this.toolbar.set("filters",new wp.media.view.AttachmentFilters.MlaUploaded({controller:this.controller,model:this.collection.props,priority:-80}).render())),this.options.search&&mlaModal.settings.enableMonthsDropdown&&(this.toolbar.unset("dateFilter",{silent:!0}),this.toolbar.set("dateFilter",new wp.media.view.AttachmentFilters.MlaMonths({controller:this.controller,model:this.collection.props,priority:-75}).render())),this.options.search&&mlaModal.settings.enableTermsDropdown&&this.toolbar.set("terms",new wp.media.view.AttachmentFilters.MlaTerms({controller:this.controller,model:this.collection.props,priority:-50}).render()),this.options.search&&mlaModal.settings.enableTermsSearch&&this.toolbar.set("termsSearch",new wp.media.view.MlaTermsSearch({controller:this.controller,model:this.collection.props,priority:-50}).render()),this.options.search&&(mlaModal.settings.enableSearchBox?(this.listenTo(this.controller,"content:activate",this.hideDefaultSearch),this.listenTo(this.controller,"router:render",this.hideDefaultSearch),this.listenTo(this.controller,"uploader:ready",this.hideDefaultSearch),this.listenTo(this.controller,"edit:activate",this.hideDefaultSearch),this.toolbar.set("MlaSearch",new wp.media.view.MlaSearch({controller:this.controller,model:this.collection.props,priority:60}).render())):this.toolbar.set("MlaSearch",new wp.media.view.MlaSearch({controller:this.controller,model:this.collection.props,priority:70}).render()));
            			}
        			}catch (e) { }
    			}
    		}
    	});
    	
    	/* Without MLA
    	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
    		createToolbar: function() {
    			// call the original method
    			orig.prototype.createToolbar.apply(this,arguments);
    			
    			media.model.Query.defaultArgs.filterSource = 'filter-media-taxonomies';
    			
    			// add our custom filter
    			this.toolbar.set('rml_folder', new folderFilter({
    				controller: this.controller,
    				model:      this.collection.props,
    				priority:   -75
    			}).render() );
    		}
    	});
    	*/
    },
    
    /**
     * Refresh the counts of folders in tree.
     * 
     * @param folders array
     * @param _cb callback
     */
    refreshCount: function(folders, _cb) {
        jQuery.ajax({
            url: rmlOpts.ajaxUrl,
            data: {
                action: "rml_folder_count",
                nonce: rmlOpts.nonces.folderCount,
                ids: folders
            },
            invokeData: _cb,
            success: function(response) {
                if (response.success) {
                    var data = response.data, obj;
                    jQuery.each(data, function(id, count) {
                        obj = window.rml.library.getObject(id);
                        
                        if (id == "ALL") {
                            jQuery(".rml-container .wp-filter .rml-info span:first").html(count);
                        }
                        
                        if (obj.size() > 0) {
                            obj.find(".rml-cnt").attr("class", "rml-cnt rml-cnt-" + count).html(count);
                        } 
                    });
                }
                
                if (RMLisDefined(this.invokeData)) {
                    this.invokeData();
                }
            }
        });
    },
    
    sweetAlertPosition: function() {
        var $ = jQuery;
        var modal = $(".sweet-alert.showSweetAlert");
        if (modal.size() > 0) {
            var height = modal.outerHeight();
            modal.stop().animate({ "margin-top": (height / 2 + 8.5) * -1 }, 500);
        }
    },
    
    /**
     * Get information about a folder.
     * 
     * @param fid folder id
     * @param infoType if there is a result, this key will be returned
     * @return null or object
     */
    getFolderInfo: function(fid, infoType) {
        var result = null;
        try {
            jQuery.each(rmlOpts.mce.dirs, function(key, value) {
                if (value.value == fid) {
                    result = value;
                }
            });
        }catch(e) {}
        
        if (typeof infoType !== "undefined" && result !== null) {
            return result[infoType];
        }
        
        return result;
    },
    
    /**
     * Get a folder object by an ID.
     * If the fid is "ALL" the All files li-element will be returned.
     * 
     * @param fid the folder ID
     * @return jQuery object
     */
    getObject: function(fid) {
        var obj;
        if (fid == "ALL" || !RMLisDefined(fid) || fid == "") {
            obj = jQuery("#rml-list-li-all-files");
        }else{
            obj = jQuery(".rml-container .list.rml-root-list a[data-id=\"" + fid + "\"]");
        }
        return obj;
    },
    
    /**
     * Get the node tree in media library depending
     * on the active media picker. If there is no media picker
     * active then get the current.
     * 
     * @uses this::getSelectObject
     * @uses this::getActiveObject
     * @uses this::getMediaPickerSelected
     * @return jQuery object
     */
    getObjectOfMediaPickerOrActive: function() {
        var modalActive = this.getMediaPickerSelected();
        if (modalActive !== null) {
            var modalID = modalActive.attr("value");
            return this.getObject(modalID);
        }else{
            return this.getActiveObject();
        }
    },
    
    /**
     * The same as this::getObject but here you get the
     * <option>-tag in the select.
     * 
     * @param fid the folder ID
     * @return jQuery object
     */
    getSelectObject: function(fid) {
        if (fid == "ALL" || !RMLisDefined(fid) || fid == "") {
            fid = "all";
        }
        return jQuery('#media-attachment-folder-filters option[value="' + fid + '"]');
    },
    
    /**
     * Get the selected folder in the wp media picker
     * dialog. The dialog must be visible and focused.
     * 
     * @return <option>-object or null
     */
    getMediaPickerSelected: function() {
        var $ = jQuery, modal = null, active = null;
        // Get each media modal
        $(".media-modal.wp-core-ui").each(function() {
            if ($(this).parent().is(":visible")) {
                modal = $(this);
            }
        });
        
        if (modal !== null) {
            // Get <select>
            active = modal.find("#media-attachment-folder-filters").find(":selected");
        }
        
        return active;
    },
    
    /**
     * Get the current folder object.
     * 
     * @return jQuery object
     */
    getActiveObject: function() {
        return jQuery(".rml-container .rml-root-list a.active");
    },
    
    /**
     * Initialize custom lists
     */
    liveCustomLists: false,
    customLists: function() {
        var $ = jQuery;
        $(".rml-root-list.rml-custom-list:not(.rml-custom-init)").each(function() {
            $(this).addClass("rml-custom-init");
            window.rml.hooks.call("customList", $(this));
            if (typeof $(this).attr("id") !== "undefined") {
                window.rml.hooks.call("customList-" + $(this).attr("data-id"), $(this));
            }
        });
        
        // Create live updater for hidden input type
        if (!this.liveCustomLists) {
            $(document).on("click", ".rml-root-list.rml-custom-list a", function(e) {
                var id = $(this).attr("data-id"),
                    list = $(this).parents(".rml-root-list.rml-custom-list");
                list.children("input").val(id);
                list.find("a.active").removeClass("active");
                $(this).addClass("active");
                e.preventDefault();
                return false;
            });
            
            this.liveCustomLists = true;
        }
    }
};

/**
 * General media library hooks.
 */
window.rml.hooks.register("renamed", function(e, args) {
    var fid = args[0],
        name = args[1],
        slug = args[2],
        optionTag = window.rml.library.getSelectObject(fid),
        aTag = window.rml.library.getObject(fid);
    
    optionTag.html(name);
    aTag.find(".rml-name").html(name);
    aTag.attr("data-slug", "/" + slug);
});

/**
 * @TRANSPORTED
 */
window.rml.hooks.register("afterInit", function(e, args) {
    // Save the gallery frame as active frame
    if (jQuery("body").hasClass("upload-php-mode-grid")) {
        window.rml.library.activeMediaFrame = wp.media.frame;
    }
});

window.rml.hooks.register("modifyMediaGridRequestForFolder", function(e, args) {
    var $ = jQuery,
        fid = args[3];
        
    // Only real folders
    if (fid < 1) {
        return;
    }
        
    var folderInfo = window.rml.library.getFolderInfo(fid), type = -1;
    if (folderInfo !== null) {
        type = folderInfo.type;
    }
    
    var action = "modifyMediaGridRequestForFolder/Type/" + type;
    window.rml.hooks.call(action, args);
});
 
window.rml.hooks.register("prepareCreate", function(e, id) {
    window.rml.library.prepareCreate(id);
});

window.rml.hooks.register("viewSwitch/rml-folder-sort", function(e) {
    window.rml.library.editMode(!jQuery(".rml-container > .aio-wrap").hasClass("edit-mode"));
});

/**
 * Hooks for changed folder count when moving
 */
window.rml.hooks.register("move", function(e, args) { // Start loader
    jQuery(".view-switch-refresh").find("i").addClass("fa-spin");
});

window.rml.hooks.register("moved", function(e, args) {
    // Catch my refreshable folders
    var refreshCountOfFolders = [ args[1] ], folderToRefresh;
    for (var i = 0; i < args[0].length; i++) {
        folderToRefresh = window.rml.library.attachments[args[0][i]];
        if (RMLisDefined(folderToRefresh)) {
            if (!(refreshCountOfFolders.indexOf(folderToRefresh.rmlFolderId) > -1)) {
                refreshCountOfFolders.push(folderToRefresh.rmlFolderId);
            }
        }
    }
    
    // If it is in table list mode we add our current folder to refresh
    if (jQuery("body").hasClass("upload-php-mode-list")) {
        var active = window.rml.library.getActiveObject();
        if (active.size() > 0)
            refreshCountOfFolders.push(active.attr("data-id"));
    }
    
    // Refresh it
    window.rml.library.refreshCount(refreshCountOfFolders, function() {
        jQuery(".view-switch-refresh").find("i").removeClass("fa-spin");
    });
});

/**
 * Hooks for changed folder count when uploading
 */
window.rml.hooks.register("upload/success/moved", function(e, args) {
    var data = args[1];
    if (RMLisDefined(data) && RMLisDefined(data.to)) {
        var to = data.to;
        window.rml.library.refreshCount(["ALL", to]);
    }
});

window.rml.hooks.register("general", function(e, args) {
    /**
     * Call an action filter to modify the query
     * for the attachments in media grid mode.
     * 
     * @hook modifyMediaGridRequestForFolder
     * @args [0] options
     * @args [1] originalOptions
     * @args [2] jqXHR
     * @args [3] request folder id
     * 
     * @example window.rml.hooks.register("modifyMediaGridRequestForFolder", function(e, args) {});
     */
    jQuery.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
        try {
            if (RMLisDefined(originalOptions.data)
                && RMLisDefined(originalOptions.data.action)
                && RMLisDefined(originalOptions.data.query.rml_folder)) {
                // call hook modifyMediaGridRequestForFolder
                if (originalOptions.data.action == "query-attachments") {
                    window.rml.hooks.call("modifyMediaGridRequestForFolder", [ options, originalOptions, jqXHR, originalOptions.data.query.rml_folder] );
                }
            }
        }catch(e) {}
    });
});

/**
 * Initialize the media library.
 */
jQuery(document).ready(function($) {
	window.rml.library.init();
});