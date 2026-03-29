<?php
/**
 * Meta Boxes for Custom Post Types
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Meta_Boxes {
    
    /**
     * Initialize meta boxes
     */
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post', array(__CLASS__, 'save_meta_boxes'), 10, 2);

        // Load media uploader on taxonomy edit screens
        add_action('admin_enqueue_scripts', function($hook) {
            if (in_array($hook, array('edit-tags.php', 'term.php'))) {
                wp_enqueue_media();
            }
        });

        // Taxonomy meta
        add_action('travel_city_add_form_fields', array(__CLASS__, 'city_add_form_fields'));
        add_action('travel_city_edit_form_fields', array(__CLASS__, 'city_edit_form_fields'), 10, 2);
        add_action('created_travel_city', array(__CLASS__, 'save_city_meta'));
        add_action('edited_travel_city', array(__CLASS__, 'save_city_meta'));
        
        add_action('travel_category_add_form_fields', array(__CLASS__, 'category_add_form_fields'));
        add_action('travel_category_edit_form_fields', array(__CLASS__, 'category_edit_form_fields'), 10, 2);
        add_action('created_travel_category', array(__CLASS__, 'save_category_meta'));
        add_action('edited_travel_category', array(__CLASS__, 'save_category_meta'));
    }
    
    /**
     * Add meta boxes
     */
    public static function add_meta_boxes() {
        // Klook Auto-Import meta box (TOP PRIORITY)
        add_meta_box(
            'fth_klook_import',
            '🚀 Import from Klook URL',
            array(__CLASS__, 'klook_import_callback'),
            'travel_activity',
            'normal',
            'high'
        );
        
        // Activity meta box
        add_meta_box(
            'fth_activity_details',
            'Activity Details',
            array(__CLASS__, 'activity_details_callback'),
            'travel_activity',
            'normal',
            'high'
        );
        
        // Activity pricing meta box
        add_meta_box(
            'fth_activity_pricing',
            'Pricing & Booking',
            array(__CLASS__, 'activity_pricing_callback'),
            'travel_activity',
            'normal',
            'high'
        );
        
        // Activity gallery meta box
        add_meta_box(
            'fth_activity_gallery',
            'Image Gallery',
            array(__CLASS__, 'activity_gallery_callback'),
            'travel_activity',
            'normal',
            'default'
        );
        
        // Destination meta box
        add_meta_box(
            'fth_destination_details',
            'Destination Details',
            array(__CLASS__, 'destination_details_callback'),
            'travel_destination',
            'normal',
            'high'
        );
        
        // Hotel meta box
        add_meta_box(
            'fth_hotel_details',
            'Hotel Details',
            array(__CLASS__, 'hotel_details_callback'),
            'travel_hotel',
            'normal',
            'high'
        );
    }
    
    /**
     * Klook Import callback - Auto-fill from Klook URL
     */
    public static function klook_import_callback($post) {
        $affiliate_id = Flavor_Travel_Hub::get_affiliate_id();
        ?>
        <style>
            .fth-klook-import { background: linear-gradient(135deg, #ff5722 0%, #ff9800 100%); padding: 20px; border-radius: 8px; color: #fff; }
            .fth-klook-import h3 { margin: 0 0 15px; font-size: 18px; display: flex; align-items: center; gap: 10px; }
            .fth-klook-import p { margin: 0 0 15px; opacity: 0.9; }
            .fth-klook-input-row { display: flex; gap: 10px; }
            .fth-klook-input { flex: 1; padding: 12px 15px; border: none; border-radius: 6px; font-size: 14px; }
            .fth-klook-btn { padding: 12px 25px; background: #fff; color: #ff5722; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; }
            .fth-klook-btn:hover { background: #f5f5f5; }
            .fth-klook-btn:disabled { opacity: 0.7; cursor: not-allowed; }
            .fth-klook-status { margin-top: 15px; padding: 10px 15px; background: rgba(255,255,255,0.2); border-radius: 6px; display: none; }
            .fth-klook-status.success { background: rgba(76, 175, 80, 0.3); }
            .fth-klook-status.error { background: rgba(244, 67, 54, 0.3); }
            .fth-klook-help { margin-top: 15px; font-size: 12px; opacity: 0.8; }
            .fth-klook-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: fth-spin 0.8s linear infinite; }
            @keyframes fth-spin { to { transform: rotate(360deg); } }
        </style>
        
        <div class="fth-klook-import">
            <h3><span style="font-size: 24px;">🎫</span> Auto-Import from Klook</h3>
            <p>Paste a Klook activity URL to automatically fetch and fill all fields (title, description, price, images, rating, etc.)</p>
            
            <div class="fth-klook-input-row">
                <input type="text" id="fth_klook_url" class="fth-klook-input" placeholder="https://www.klook.com/activity/12345-activity-name/">
                <button type="button" id="fth_klook_fetch" class="fth-klook-btn">
                    <span class="btn-text">⚡ Fetch Data</span>
                    <span class="btn-spinner fth-klook-spinner" style="display: none;"></span>
                </button>
            </div>
            
            <div id="fth_klook_status" class="fth-klook-status"></div>
            
            <div class="fth-klook-help">
                <strong>Supported URL formats:</strong><br>
                • https://www.klook.com/activity/12345-activity-name/<br>
                • https://www.klook.com/en-AE/activity/12345-...<br>
                • Your affiliate ID (<?php echo esc_html($affiliate_id); ?>) will be automatically added to the booking link.
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#fth_klook_fetch').on('click', function() {
                var url = $('#fth_klook_url').val().trim();
                var $btn = $(this);
                var $status = $('#fth_klook_status');
                
                if (!url) {
                    $status.removeClass('success').addClass('error').text('Please enter a Klook URL').show();
                    return;
                }
                
                // Validate URL
                if (!url.includes('klook.com')) {
                    $status.removeClass('success').addClass('error').text('Please enter a valid Klook URL').show();
                    return;
                }
                
                // Show loading
                $btn.prop('disabled', true);
                $btn.find('.btn-text').text('Fetching...');
                $btn.find('.btn-spinner').show();
                $status.removeClass('success error').text('Scraping Klook page...').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fth_scrape_klook',
                        url: url,
                        post_id: <?php echo $post->ID; ?>,
                        nonce: '<?php echo wp_create_nonce('fth_scrape_klook'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var data = response.data;
                            
                            // Fill post title
                            if (data.title) {
                                $('#title').val(data.title);
                                $('#title-prompt-text').hide();
                            }
                            
                            // Fill content/description
                            if (data.description) {
                                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                                    tinymce.get('content').setContent(data.description);
                                } else {
                                    $('#content').val(data.description);
                                }
                            }
                            
                            // Fill excerpt
                            if (data.excerpt) {
                                $('#excerpt').val(data.excerpt);
                            }
                            
                            // Fill price
                            if (data.price) {
                                $('#fth_price').val(data.price);
                            }
                            
                            // Fill original price
                            if (data.original_price) {
                                $('#fth_original_price').val(data.original_price);
                            }
                            
                            // Fill currency
                            if (data.currency) {
                                $('#fth_currency').val(data.currency);
                            }
                            
                            // Fill rating
                            if (data.rating) {
                                $('#fth_rating').val(data.rating);
                            }
                            
                            // Fill review count
                            if (data.review_count) {
                                $('#fth_review_count').val(data.review_count);
                            }
                            
                            // Fill duration
                            if (data.duration) {
                                $('#fth_duration').val(data.duration);
                            }
                            
                            // Fill highlights
                            if (data.highlights) {
                                $('#fth_highlights').val(data.highlights);
                            }
                            
                            // Fill inclusions
                            if (data.inclusions) {
                                $('#fth_inclusions').val(data.inclusions);
                            }
                            
                            // Fill exclusions
                            if (data.exclusions) {
                                $('#fth_exclusions').val(data.exclusions);
                            }
                            
                            // Fill meeting point
                            if (data.meeting_point) {
                                $('#fth_meeting_point').val(data.meeting_point);
                            }
                            
                            // Fill affiliate link
                            if (data.affiliate_link) {
                                $('#fth_affiliate_link').val(data.affiliate_link);
                            }
                            
                            // Fill Klook activity ID
                            if (data.activity_id) {
                                $('#fth_klook_activity_id').val(data.activity_id);
                            }
                            
                            // Fill external image
                            if (data.image) {
                                // Store external image URL
                                var $imageInput = $('input[name="fth_external_image"]');
                                if ($imageInput.length === 0) {
                                    // Will be saved via hidden field
                                }
                                // Store in hidden field for save
                                if (!$('#fth_external_image_hidden').length) {
                                    $('<input>').attr({
                                        type: 'hidden',
                                        id: 'fth_external_image_hidden',
                                        name: 'fth_external_image',
                                        value: data.image
                                    }).appendTo('form#post');
                                } else {
                                    $('#fth_external_image_hidden').val(data.image);
                                }
                            }
                            
                            $status.removeClass('error').addClass('success').html(
                                '✅ <strong>Success!</strong> All fields have been filled. Review the data and click "Publish" or "Update".'
                            );
                        } else {
                            $status.removeClass('success').addClass('error').text(
                                '❌ Error: ' + (response.data && response.data.message ? response.data.message : 'Failed to fetch data')
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        $status.removeClass('success').addClass('error').text('❌ Network error: ' + error);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.find('.btn-text').text('⚡ Fetch Data');
                        $btn.find('.btn-spinner').hide();
                    }
                });
            });
            
            // Also fetch on Enter key
            $('#fth_klook_url').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#fth_klook_fetch').click();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Activity details callback
     */
    public static function activity_details_callback($post) {
        wp_nonce_field('fth_save_meta', 'fth_meta_nonce');
        
        $highlights = get_post_meta($post->ID, '_fth_highlights', true);
        $duration = get_post_meta($post->ID, '_fth_duration', true);
        $meeting_point = get_post_meta($post->ID, '_fth_meeting_point', true);
        $inclusions = get_post_meta($post->ID, '_fth_inclusions', true);
        $exclusions = get_post_meta($post->ID, '_fth_exclusions', true);
        $rating = get_post_meta($post->ID, '_fth_rating', true);
        $review_count = get_post_meta($post->ID, '_fth_review_count', true);
        $is_featured = get_post_meta($post->ID, '_fth_is_featured', true);
        $is_bestseller = get_post_meta($post->ID, '_fth_is_bestseller', true);
        ?>
        <style>
            .fth-meta-row { margin-bottom: 15px; }
            .fth-meta-row label { display: block; font-weight: 600; margin-bottom: 5px; }
            .fth-meta-row input[type="text"],
            .fth-meta-row input[type="number"],
            .fth-meta-row input[type="url"],
            .fth-meta-row textarea { width: 100%; }
            .fth-meta-row textarea { min-height: 100px; }
            .fth-meta-row .description { color: #666; font-style: italic; margin-top: 5px; }
            .fth-meta-checkbox { display: flex; align-items: center; gap: 10px; }
            .fth-meta-checkbox label { margin: 0; }
        </style>
        
        <div class="fth-meta-row">
            <label for="fth_highlights">Highlights</label>
            <textarea id="fth_highlights" name="fth_highlights" placeholder="Enter highlights, one per line"><?php echo esc_textarea($highlights); ?></textarea>
            <p class="description">Enter each highlight on a new line. These will be displayed as bullet points.</p>
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_duration">Duration</label>
            <input type="text" id="fth_duration" name="fth_duration" value="<?php echo esc_attr($duration); ?>" placeholder="e.g., 2-3 hours, Full day, 30 minutes">
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_meeting_point">Meeting Point / Location</label>
            <input type="text" id="fth_meeting_point" name="fth_meeting_point" value="<?php echo esc_attr($meeting_point); ?>" placeholder="e.g., Hotel pickup, At the venue">
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_inclusions">What's Included</label>
            <textarea id="fth_inclusions" name="fth_inclusions" placeholder="Enter inclusions, one per line"><?php echo esc_textarea($inclusions); ?></textarea>
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_exclusions">What's Not Included</label>
            <textarea id="fth_exclusions" name="fth_exclusions" placeholder="Enter exclusions, one per line"><?php echo esc_textarea($exclusions); ?></textarea>
        </div>
        
        <div class="fth-meta-row" style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <label for="fth_rating">Rating (0-5)</label>
                <input type="number" id="fth_rating" name="fth_rating" value="<?php echo esc_attr($rating); ?>" min="0" max="5" step="0.1" placeholder="4.5">
            </div>
            <div style="flex: 1;">
                <label for="fth_review_count">Review Count</label>
                <input type="number" id="fth_review_count" name="fth_review_count" value="<?php echo esc_attr($review_count); ?>" min="0" placeholder="1250">
            </div>
        </div>
        
        <div class="fth-meta-row">
            <div class="fth-meta-checkbox">
                <input type="checkbox" id="fth_is_featured" name="fth_is_featured" value="1" <?php checked($is_featured, '1'); ?>>
                <label for="fth_is_featured">Featured Activity</label>
            </div>
            <div class="fth-meta-checkbox" style="margin-top: 10px;">
                <input type="checkbox" id="fth_is_bestseller" name="fth_is_bestseller" value="1" <?php checked($is_bestseller, '1'); ?>>
                <label for="fth_is_bestseller">Bestseller Badge</label>
            </div>
        </div>
        <?php
    }
    
    /**
     * Activity pricing callback
     */
    public static function activity_pricing_callback($post) {
        $price = get_post_meta($post->ID, '_fth_price', true);
        $original_price = get_post_meta($post->ID, '_fth_original_price', true);
        $currency = get_post_meta($post->ID, '_fth_currency', true);
        $affiliate_link = get_post_meta($post->ID, '_fth_affiliate_link', true);
        $klook_activity_id = get_post_meta($post->ID, '_fth_klook_activity_id', true);
        ?>
        <div class="fth-meta-row" style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <label for="fth_price">Price From</label>
                <input type="number" id="fth_price" name="fth_price" value="<?php echo esc_attr($price); ?>" min="0" step="0.01" placeholder="99.00">
            </div>
            <div style="flex: 1;">
                <label for="fth_original_price">Original Price (for discount display)</label>
                <input type="number" id="fth_original_price" name="fth_original_price" value="<?php echo esc_attr($original_price); ?>" min="0" step="0.01" placeholder="120.00">
            </div>
            <div style="flex: 1;">
                <label for="fth_currency">Currency</label>
                <select id="fth_currency" name="fth_currency">
                    <option value="USD" <?php selected($currency, 'USD'); ?>>USD ($)</option>
                    <option value="AED" <?php selected($currency, 'AED'); ?>>AED (د.إ)</option>
                    <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR (€)</option>
                    <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP (£)</option>
                    <option value="SAR" <?php selected($currency, 'SAR'); ?>>SAR (﷼)</option>
                    <option value="QAR" <?php selected($currency, 'QAR'); ?>>QAR (ر.ق)</option>
                </select>
            </div>
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_affiliate_link">Klook Affiliate Deep Link</label>
            <input type="url" id="fth_affiliate_link" name="fth_affiliate_link" value="<?php echo esc_url($affiliate_link); ?>" placeholder="https://affiliate.klook.com/redirect?aid=115387&...">
            <p class="description">Paste the complete Klook affiliate deep link for this activity. This link will be used for the "Book Now" button.</p>
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_klook_activity_id">Klook Activity ID (optional)</label>
            <input type="text" id="fth_klook_activity_id" name="fth_klook_activity_id" value="<?php echo esc_attr($klook_activity_id); ?>" placeholder="e.g., 12345">
            <p class="description">Optional: Store the Klook activity ID for reference.</p>
        </div>
        <?php
    }
    
    /**
     * Activity gallery callback
     */
    public static function activity_gallery_callback($post) {
        $gallery = get_post_meta($post->ID, '_fth_gallery', true);
        $gallery_ids = $gallery ? explode(',', $gallery) : array();
        $external_image = get_post_meta($post->ID, '_fth_external_image', true);
        ?>
        <div class="fth-meta-row">
            <label for="fth_external_image">External Image URL (from Klook)</label>
            <input type="url" id="fth_external_image" name="fth_external_image" value="<?php echo esc_url($external_image); ?>" placeholder="https://res.klook.com/...">
            <p class="description">This image URL is automatically filled when importing from Klook. It will be used as the main activity image.</p>
            <?php if ($external_image): ?>
            <div style="margin-top: 10px;">
                <img src="<?php echo esc_url($external_image); ?>" style="max-width: 200px; border-radius: 8px;">
            </div>
            <?php endif; ?>
        </div>
        
        <div class="fth-gallery-container" style="margin-top: 20px;">
            <div id="fth-gallery-images" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <?php
                foreach ($gallery_ids as $image_id) {
                    $image_id = intval($image_id);
                    if ($image_id) {
                        $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                        if ($image_url) {
                            echo '<div class="fth-gallery-image" data-id="' . esc_attr($image_id) . '" style="position: relative;">';
                            echo '<img src="' . esc_url($image_url) . '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px;">';
                            echo '<button type="button" class="fth-remove-image" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px;">&times;</button>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>
            <input type="hidden" id="fth_gallery" name="fth_gallery" value="<?php echo esc_attr($gallery); ?>">
            <button type="button" id="fth-add-gallery-images" class="button">Add Images to Gallery</button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var frame;
            
            $('#fth-add-gallery-images').on('click', function(e) {
                e.preventDefault();
                
                if (frame) {
                    frame.open();
                    return;
                }
                
                frame = wp.media({
                    title: 'Select Gallery Images',
                    multiple: true,
                    library: { type: 'image' }
                });
                
                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    var currentIds = $('#fth_gallery').val() ? $('#fth_gallery').val().split(',') : [];
                    
                    attachments.forEach(function(attachment) {
                        if (currentIds.indexOf(attachment.id.toString()) === -1) {
                            currentIds.push(attachment.id);
                            var thumb = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                            var html = '<div class="fth-gallery-image" data-id="' + attachment.id + '" style="position: relative;">';
                            html += '<img src="' + thumb + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px;">';
                            html += '<button type="button" class="fth-remove-image" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px;">&times;</button>';
                            html += '</div>';
                            $('#fth-gallery-images').append(html);
                        }
                    });
                    
                    $('#fth_gallery').val(currentIds.filter(Boolean).join(','));
                });
                
                frame.open();
            });
            
            $(document).on('click', '.fth-remove-image', function() {
                var $parent = $(this).parent();
                var removeId = $parent.data('id').toString();
                var currentIds = $('#fth_gallery').val().split(',');
                currentIds = currentIds.filter(function(id) { return id !== removeId; });
                $('#fth_gallery').val(currentIds.join(','));
                $parent.remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Destination details callback
     */
    public static function destination_details_callback($post) {
        $affiliate_link = get_post_meta($post->ID, '_fth_affiliate_link', true);
        $hero_subtitle = get_post_meta($post->ID, '_fth_hero_subtitle', true);
        $seo_intro = get_post_meta($post->ID, '_fth_seo_intro', true);
        $is_featured = get_post_meta($post->ID, '_fth_is_featured', true);
        $external_image = get_post_meta($post->ID, '_fth_external_image', true);
        ?>
        
        <!-- Klook Import for Destinations -->
        <div style="background: linear-gradient(135deg, #ff5722 0%, #ff9800 100%); padding: 20px; border-radius: 8px; margin-bottom: 20px; color: #fff;">
            <h3 style="margin: 0 0 10px; font-size: 16px;">🌍 Import from Klook Destination URL</h3>
            <p style="margin: 0 0 15px; opacity: 0.9; font-size: 13px;">Paste a Klook city or destination URL to auto-fill all fields</p>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="fth_klook_dest_url" style="flex: 1; padding: 12px; border: none; border-radius: 6px;" placeholder="https://www.klook.com/city/123-dubai/">
                <button type="button" id="fth_fetch_dest_klook" class="button" style="background: #fff; color: #ff5722; border: none; padding: 12px 20px; font-weight: 700;">⚡ Fetch</button>
            </div>
            <div id="fth_dest_status" style="margin-top: 10px; display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#fth_fetch_dest_klook').on('click', function() {
                var url = $('#fth_klook_dest_url').val().trim();
                var $btn = $(this);
                var $status = $('#fth_dest_status');
                
                if (!url || !url.includes('klook.com')) {
                    $status.css('background', 'rgba(244,67,54,0.3)').text('Please enter a valid Klook URL').show();
                    return;
                }
                
                $btn.prop('disabled', true).text('Fetching...');
                $status.css('background', 'rgba(255,255,255,0.2)').text('Scraping Klook page...').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fth_scrape_klook_destination',
                        url: url,
                        nonce: '<?php echo wp_create_nonce('fth_scrape_klook_destination'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var data = response.data;
                            
                            if (data.title) {
                                $('#title').val(data.title);
                                $('#title-prompt-text').hide();
                            }
                            if (data.description) {
                                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                                    tinymce.get('content').setContent('<p>' + data.description + '</p>');
                                } else {
                                    $('#content').val(data.description);
                                }
                                $('#fth_seo_intro').val(data.description);
                            }
                            if (data.affiliate_link) {
                                $('#fth_affiliate_link').val(data.affiliate_link);
                            }
                            if (data.hero_image) {
                                $('#fth_external_image').val(data.hero_image);
                            }
                            
                            $status.css('background', 'rgba(76,175,80,0.3)').html('✅ <strong>Success!</strong> Fields filled. Click "Publish" to save.').show();
                        } else {
                            $status.css('background', 'rgba(244,67,54,0.3)').text('❌ ' + (response.data ? response.data.message : 'Failed')).show();
                        }
                    },
                    error: function() {
                        $status.css('background', 'rgba(244,67,54,0.3)').text('❌ Network error').show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('⚡ Fetch');
                    }
                });
            });
        });
        </script>
        
        <style>
            .fth-meta-row { margin-bottom: 15px; }
            .fth-meta-row label { display: block; font-weight: 600; margin-bottom: 5px; }
            .fth-meta-row input[type="text"], .fth-meta-row input[type="url"], .fth-meta-row textarea { width: 100%; }
            .fth-meta-row .description { color: #666; font-style: italic; margin-top: 5px; }
        </style>
        
        <div class="fth-meta-row">
            <label for="fth_external_image">Hero Image URL (from Klook)</label>
            <input type="url" id="fth_external_image" name="fth_external_image" value="<?php echo esc_url($external_image); ?>" placeholder="https://res.klook.com/...">
            <?php if ($external_image): ?>
            <div style="margin-top: 10px;"><img src="<?php echo esc_url($external_image); ?>" style="max-width: 200px; border-radius: 8px;"></div>
            <?php endif; ?>
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_hero_subtitle">Hero Subtitle</label>
            <input type="text" id="fth_hero_subtitle" name="fth_hero_subtitle" value="<?php echo esc_attr($hero_subtitle); ?>" placeholder="e.g., Discover the city of gold">
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_seo_intro">SEO Introduction Text</label>
            <textarea id="fth_seo_intro" name="fth_seo_intro" rows="4" placeholder="Write an engaging introduction for this destination..."><?php echo esc_textarea($seo_intro); ?></textarea>
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_affiliate_link">Klook Destination Deep Link</label>
            <input type="url" id="fth_affiliate_link" name="fth_affiliate_link" value="<?php echo esc_url($affiliate_link); ?>" placeholder="https://affiliate.klook.com/redirect?aid=115387&...">
            <p class="description">Paste the complete Klook affiliate deep link for this destination.</p>
        </div>
        
        <div class="fth-meta-row">
            <div class="fth-meta-checkbox">
                <input type="checkbox" id="fth_is_featured" name="fth_is_featured" value="1" <?php checked($is_featured, '1'); ?>>
                <label for="fth_is_featured">Featured Destination</label>
            </div>
        </div>
        <?php
    }
    
    /**
     * Hotel details callback
     */
    public static function hotel_details_callback($post) {
        $affiliate_link = get_post_meta($post->ID, '_fth_affiliate_link', true);
        $star_rating = get_post_meta($post->ID, '_fth_star_rating', true);
        $address = get_post_meta($post->ID, '_fth_address', true);
        $price_from = get_post_meta($post->ID, '_fth_price', true);
        ?>
        <div class="fth-meta-row">
            <label for="fth_star_rating">Star Rating</label>
            <select id="fth_star_rating" name="fth_star_rating">
                <option value="">Select rating</option>
                <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php selected($star_rating, $i); ?>><?php echo $i; ?> Star</option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_address">Address</label>
            <input type="text" id="fth_address" name="fth_address" value="<?php echo esc_attr($address); ?>" placeholder="Hotel address">
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_price">Price From (per night)</label>
            <input type="number" id="fth_price" name="fth_price" value="<?php echo esc_attr($price_from); ?>" min="0" step="0.01" placeholder="150.00">
        </div>
        
        <div class="fth-meta-row">
            <label for="fth_affiliate_link">Klook Hotel Deep Link</label>
            <input type="url" id="fth_affiliate_link" name="fth_affiliate_link" value="<?php echo esc_url($affiliate_link); ?>" placeholder="https://affiliate.klook.com/redirect?aid=115387&...">
        </div>
        <?php
    }
    
    /**
     * Save meta boxes
     */
    public static function save_meta_boxes($post_id, $post) {
        // Security checks
        if (!isset($_POST['fth_meta_nonce']) || !wp_verify_nonce($_POST['fth_meta_nonce'], 'fth_save_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Activity fields
        if ($post->post_type === 'travel_activity') {
            $fields = array(
                'fth_highlights'        => '_fth_highlights',
                'fth_duration'          => '_fth_duration',
                'fth_meeting_point'     => '_fth_meeting_point',
                'fth_inclusions'        => '_fth_inclusions',
                'fth_exclusions'        => '_fth_exclusions',
                'fth_rating'            => '_fth_rating',
                'fth_review_count'      => '_fth_review_count',
                'fth_price'             => '_fth_price',
                'fth_original_price'    => '_fth_original_price',
                'fth_currency'          => '_fth_currency',
                'fth_affiliate_link'    => '_fth_affiliate_link',
                'fth_klook_activity_id' => '_fth_klook_activity_id',
                'fth_gallery'           => '_fth_gallery',
                'fth_external_image'    => '_fth_external_image',
            );
            
            foreach ($fields as $post_key => $meta_key) {
                if (isset($_POST[$post_key])) {
                    if (in_array($post_key, array('fth_affiliate_link', 'fth_external_image'))) {
                        update_post_meta($post_id, $meta_key, esc_url_raw($_POST[$post_key]));
                    } elseif (in_array($post_key, array('fth_highlights', 'fth_inclusions', 'fth_exclusions'))) {
                        update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$post_key]));
                    } else {
                        update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
                    }
                }
            }
            
            // Checkboxes
            update_post_meta($post_id, '_fth_is_featured', isset($_POST['fth_is_featured']) ? '1' : '0');
            update_post_meta($post_id, '_fth_is_bestseller', isset($_POST['fth_is_bestseller']) ? '1' : '0');
        }
        
        // Destination fields
        if ($post->post_type === 'travel_destination') {
            $fields = array(
                'fth_hero_subtitle'   => '_fth_hero_subtitle',
                'fth_seo_intro'       => '_fth_seo_intro',
                'fth_affiliate_link'  => '_fth_affiliate_link',
                'fth_external_image'  => '_fth_external_image',
            );
            
            foreach ($fields as $post_key => $meta_key) {
                if (isset($_POST[$post_key])) {
                    if (in_array($post_key, array('fth_affiliate_link', 'fth_external_image'))) {
                        update_post_meta($post_id, $meta_key, esc_url_raw($_POST[$post_key]));
                    } elseif ($post_key === 'fth_seo_intro') {
                        update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$post_key]));
                    } else {
                        update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
                    }
                }
            }
            
            update_post_meta($post_id, '_fth_is_featured', isset($_POST['fth_is_featured']) ? '1' : '0');
        }
        
        // Hotel fields
        if ($post->post_type === 'travel_hotel') {
            $fields = array(
                'fth_star_rating'    => '_fth_star_rating',
                'fth_address'        => '_fth_address',
                'fth_price'          => '_fth_price',
                'fth_affiliate_link' => '_fth_affiliate_link',
            );
            
            foreach ($fields as $post_key => $meta_key) {
                if (isset($_POST[$post_key])) {
                    if ($post_key === 'fth_affiliate_link') {
                        update_post_meta($post_id, $meta_key, esc_url_raw($_POST[$post_key]));
                    } else {
                        update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
                    }
                }
            }
        }
    }
    
    /**
     * City add form fields
     */
    public static function city_add_form_fields() {
        ?>
        <!-- Klook Import for New City -->
        <div class="form-field">
            <label>Import from Klook</label>
            <div style="background: linear-gradient(135deg, #ff5722 0%, #ff9800 100%); padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                <p style="color: #fff; margin: 0 0 10px; font-weight: 600;">🎫 Auto-fill from Klook City URL</p>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="fth_klook_city_url_new" style="flex: 1; padding: 10px; border: none; border-radius: 4px;" placeholder="https://www.klook.com/city/123-dubai/">
                    <button type="button" id="fth_fetch_city_klook_new" class="button" style="background: #fff; color: #ff5722; border: none; font-weight: 700;">⚡ Fetch</button>
                </div>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $('#fth_fetch_city_klook_new').on('click', function() {
                    var url = $('#fth_klook_city_url_new').val().trim();
                    var $btn = $(this);
                    
                    if (!url || !url.includes('klook.com')) {
                        alert('Please enter a valid Klook URL');
                        return;
                    }
                    
                    $btn.prop('disabled', true).text('Fetching...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'fth_scrape_klook_city',
                            url: url,
                            nonce: '<?php echo wp_create_nonce('fth_scrape_klook_city'); ?>'
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                var data = response.data;
                                if (data.name) $('#tag-name').val(data.name);
                                if (data.description) $('#tag-description').val(data.description);
                                if (data.hero_image) $('#fth_city_hero').val(data.hero_image);
                                if (data.deeplink) $('#fth_city_deeplink').val(data.deeplink);
                                alert('✅ City data imported! Review and click "Add New City"');
                            } else {
                                alert('Error: ' + (response.data ? response.data.message : 'Failed to fetch'));
                            }
                        },
                        error: function() {
                            alert('Network error');
                        },
                        complete: function() {
                            $btn.prop('disabled', false).text('⚡ Fetch');
                        }
                    });
                });
            });
            </script>
        </div>
        
        <div class="form-field">
            <label for="fth_city_country">Parent Country</label>
            <select id="fth_city_country" name="fth_city_country">
                <option value="">Select Country</option>
                <?php
                $countries = get_terms(array('taxonomy' => 'travel_country', 'hide_empty' => false));
                foreach ($countries as $country) {
                    echo '<option value="' . esc_attr($country->term_id) . '">' . esc_html($country->name) . '</option>';
                }
                ?>
            </select>
            <p>Select the country this city belongs to.</p>
        </div>
        
        <div class="form-field">
            <label for="fth_city_hero">Hero Image URL</label>
            <input type="url" id="fth_city_hero" name="fth_city_hero" value="">
            <p>Enter the URL for the city hero image.</p>
        </div>
        
        <div class="form-field">
            <label for="fth_city_deeplink">Klook Destination Deep Link</label>
            <input type="url" id="fth_city_deeplink" name="fth_city_deeplink" value="">
            <p>Klook affiliate link for this city.</p>
        </div>
        <?php
    }
    
    /**
     * City edit form fields
     */
    public static function city_edit_form_fields($term, $taxonomy) {
        $country_id = get_term_meta($term->term_id, 'fth_parent_country', true);
        $hero_image = get_term_meta($term->term_id, 'fth_hero_image', true);
        $deeplink = get_term_meta($term->term_id, 'fth_deeplink', true);
        ?>
        <!-- Klook Import for Cities -->
        <tr class="form-field">
            <th scope="row"><label>Import from Klook</label></th>
            <td>
                <div style="background: linear-gradient(135deg, #ff5722 0%, #ff9800 100%); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <p style="color: #fff; margin: 0 0 10px; font-weight: 600;">🎫 Auto-fill from Klook City URL</p>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="fth_klook_city_url" style="flex: 1; padding: 10px; border: none; border-radius: 4px;" placeholder="https://www.klook.com/city/123-dubai/">
                        <button type="button" id="fth_fetch_city_klook" class="button" style="background: #fff; color: #ff5722; border: none; font-weight: 700;">⚡ Fetch</button>
                    </div>
                    <p style="color: rgba(255,255,255,0.8); font-size: 11px; margin: 8px 0 0;">Supported: City pages, destination pages from Klook</p>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    $('#fth_fetch_city_klook').on('click', function() {
                        var url = $('#fth_klook_city_url').val().trim();
                        var $btn = $(this);
                        
                        if (!url || !url.includes('klook.com')) {
                            alert('Please enter a valid Klook URL');
                            return;
                        }
                        
                        $btn.prop('disabled', true).text('Fetching...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'fth_scrape_klook_city',
                                url: url,
                                nonce: '<?php echo wp_create_nonce('fth_scrape_klook_city'); ?>'
                            },
                            success: function(response) {
                                if (response.success && response.data) {
                                    var data = response.data;
                                    if (data.name) $('#name').val(data.name);
                                    if (data.description) $('#description').val(data.description);
                                    if (data.hero_image) $('#fth_city_hero').val(data.hero_image);
                                    if (data.deeplink) $('#fth_city_deeplink').val(data.deeplink);
                                    alert('✅ City data imported successfully!');
                                } else {
                                    alert('Error: ' + (response.data ? response.data.message : 'Failed to fetch'));
                                }
                            },
                            error: function() {
                                alert('Network error');
                            },
                            complete: function() {
                                $btn.prop('disabled', false).text('⚡ Fetch');
                            }
                        });
                    });
                });
                </script>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="fth_city_country">Parent Country</label></th>
            <td>
                <select id="fth_city_country" name="fth_city_country">
                    <option value="">Select Country</option>
                    <?php
                    $countries = get_terms(array('taxonomy' => 'travel_country', 'hide_empty' => false));
                    foreach ($countries as $country) {
                        echo '<option value="' . esc_attr($country->term_id) . '" ' . selected($country_id, $country->term_id, false) . '>' . esc_html($country->name) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="fth_city_hero">Hero Image</label></th>
            <td>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <input type="url" id="fth_city_hero" name="fth_city_hero" value="<?php echo esc_url($hero_image); ?>" style="flex:1;min-width:260px;" class="fth-city-img-url">
                    <button type="button" class="button fth-city-media-btn" data-target="#fth_city_hero" data-preview="#fth_city_hero_preview">Choose Image</button>
                </div>
                <?php if ($hero_image): ?>
                <img id="fth_city_hero_preview" src="<?php echo esc_url($hero_image); ?>" style="max-width:280px;max-height:70px;object-fit:cover;border-radius:4px;margin-top:8px;display:block;">
                <?php else: ?>
                <img id="fth_city_hero_preview" src="" style="max-width:280px;max-height:70px;object-fit:cover;border-radius:4px;margin-top:8px;display:none;">
                <?php endif; ?>
                <p class="description">Recommended: at least 1600×500px.</p>
                <script>
                jQuery(document).ready(function($) {
                    if (typeof wp !== 'undefined' && wp.media) {
                        $(document).off('click.fthmedia', '.fth-city-media-btn').on('click.fthmedia', '.fth-city-media-btn', function(e) {
                            e.preventDefault();
                            var target  = $(this).data('target');
                            var preview = $(this).data('preview');
                            var frame = wp.media({ title: 'Select Hero Image', button: { text: 'Use this image' }, multiple: false, library: { type: 'image' } });
                            frame.on('select', function() {
                                var a = frame.state().get('selection').first().toJSON();
                                $(target).val(a.url);
                                $(preview).attr('src', a.url).show();
                            });
                            frame.open();
                        });
                        $(document).on('input', '.fth-city-img-url', function() {
                            var url = $(this).val();
                            if (url) { $('#fth_city_hero_preview').attr('src', url).show(); }
                            else     { $('#fth_city_hero_preview').hide(); }
                        });
                    }
                });
                </script>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="fth_city_deeplink">Klook Deep Link</label></th>
            <td>
                <input type="url" id="fth_city_deeplink" name="fth_city_deeplink" value="<?php echo esc_url($deeplink); ?>">
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save city meta
     */
    public static function save_city_meta($term_id) {
        if (isset($_POST['fth_city_country'])) {
            update_term_meta($term_id, 'fth_parent_country', absint($_POST['fth_city_country']));
        }
        if (isset($_POST['fth_city_hero'])) {
            update_term_meta($term_id, 'fth_hero_image', esc_url_raw($_POST['fth_city_hero']));
        }
        if (isset($_POST['fth_city_deeplink'])) {
            update_term_meta($term_id, 'fth_deeplink', esc_url_raw($_POST['fth_city_deeplink']));
        }
    }
    
    /**
     * Category add form fields
     */
    public static function category_add_form_fields() {
        ?>
        <div class="form-field">
            <label for="fth_category_icon">Icon Class</label>
            <input type="text" id="fth_category_icon" name="fth_category_icon" value="">
            <p>FontAwesome icon class (e.g., fa-landmark, fa-water)</p>
        </div>
        
        <div class="form-field">
            <label for="fth_category_color">Category Color</label>
            <input type="color" id="fth_category_color" name="fth_category_color" value="#19A880">
        </div>
        <?php
    }
    
    /**
     * Category edit form fields
     */
    public static function category_edit_form_fields($term, $taxonomy) {
        $icon = get_term_meta($term->term_id, 'fth_icon', true);
        $color = get_term_meta($term->term_id, 'fth_color', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="fth_category_icon">Icon Class</label></th>
            <td>
                <input type="text" id="fth_category_icon" name="fth_category_icon" value="<?php echo esc_attr($icon); ?>">
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="fth_category_color">Category Color</label></th>
            <td>
                <input type="color" id="fth_category_color" name="fth_category_color" value="<?php echo esc_attr($color ?: '#19A880'); ?>">
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save category meta
     */
    public static function save_category_meta($term_id) {
        if (isset($_POST['fth_category_icon'])) {
            update_term_meta($term_id, 'fth_icon', sanitize_text_field($_POST['fth_category_icon']));
        }
        if (isset($_POST['fth_category_color'])) {
            update_term_meta($term_id, 'fth_color', sanitize_hex_color($_POST['fth_category_color']));
        }
    }
}
