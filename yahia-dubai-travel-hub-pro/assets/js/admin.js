/**
 * Flavor Travel Hub - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        FTHAdmin.init();
    });

    var FTHAdmin = {
        
        init: function() {
            this.initMediaUploader();
            this.initPreview();
            this.initBulkActions();
        },
        
        initMediaUploader: function() {
            var frame;
            
            // Single image upload buttons
            $(document).on('click', '.fth-upload-image-btn', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $input = $btn.siblings('.fth-image-input');
                var $preview = $btn.siblings('.fth-image-preview');
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: 'Select Image',
                    button: { text: 'Use Image' },
                    multiple: false
                });
                
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.url);
                    
                    if ($preview.length) {
                        $preview.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
                    }
                });
                
                frame.open();
            });
        },
        
        initPreview: function() {
            // Live preview refresh button
            $(document).on('click', '.fth-refresh-preview', function(e) {
                e.preventDefault();
                
                var postId = $(this).data('post-id');
                var $preview = $('#fth-card-preview');
                
                $preview.css('opacity', 0.5);
                
                $.post(fthAdmin.ajaxurl, {
                    action: 'fth_admin_preview_activity',
                    nonce: fthAdmin.nonce,
                    post_id: postId
                }, function(response) {
                    if (response.success) {
                        $preview.html(response.data.html);
                    }
                    $preview.css('opacity', 1);
                });
            });
        },
        
        initBulkActions: function() {
            // Custom bulk actions
            $(document).on('click', '.fth-bulk-action', function(e) {
                e.preventDefault();
                
                var action = $(this).data('action');
                var $checkboxes = $('input[name="post[]"]:checked');
                var postIds = [];
                
                $checkboxes.each(function() {
                    postIds.push($(this).val());
                });
                
                if (postIds.length === 0) {
                    alert('Please select at least one item.');
                    return;
                }
                
                if (!confirm('Apply "' + action + '" to ' + postIds.length + ' items?')) {
                    return;
                }
                
                $.post(fthAdmin.ajaxurl, {
                    action: 'fth_admin_bulk_action',
                    nonce: fthAdmin.nonce,
                    bulk_action: action,
                    post_ids: postIds
                }, function(response) {
                    if (response.success) {
                        alert('Updated ' + response.data.updated + ' items.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        }
    };

    window.FTHAdmin = FTHAdmin;

})(jQuery);
