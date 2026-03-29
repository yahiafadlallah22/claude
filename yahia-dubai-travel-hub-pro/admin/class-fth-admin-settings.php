<?php
/**
 * Admin Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Admin_Settings {
    
    /**
     * Initialize settings
     */
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_init', array(__CLASS__, 'handle_flush_rewrite'));
        add_action('admin_notices', array(__CLASS__, 'permalink_notice'));
    }
    
    /**
     * Handle flush rewrite rules request
     */
    public static function handle_flush_rewrite() {
        if (isset($_GET['fth_flush_rewrite']) && $_GET['fth_flush_rewrite'] === '1') {
            if (!current_user_can('manage_options')) {
                return;
            }
            
            // Verify nonce
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'fth_flush_rewrite')) {
                return;
            }
            
            flush_rewrite_rules();
            
            // Redirect back
            wp_redirect(admin_url('admin.php?page=fth-travel-hub&fth_flushed=1'));
            exit;
        }
    }
    
    /**
     * Show permalink notice if pages return 404
     */
    public static function permalink_notice() {
        // Only show on our admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'fth-travel-hub') === false) {
            // Also show on edit screens for our post types
            if (!$screen || !in_array($screen->post_type, array('travel_activity', 'travel_destination', 'travel_hotel'))) {
                return;
            }
        }
        
        // Show success message
        if (isset($_GET['fth_flushed']) && $_GET['fth_flushed'] === '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Yahia Dubai Travel Hub:</strong> Permalinks flushed successfully! Your pages should now work correctly.</p>';
            echo '</div>';
            return;
        }
        
        // Show help notice for first-time users
        if (get_option('fth_show_permalink_notice', true)) {
            $flush_url = wp_nonce_url(
                admin_url('admin.php?page=fth-travel-hub&fth_flush_rewrite=1'),
                'fth_flush_rewrite'
            );
            
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Yahia Dubai Travel Hub:</strong> If you see "Page not found" errors on your travel pages, ';
            echo '<a href="' . esc_url($flush_url) . '">click here to refresh permalinks</a>, ';
            echo 'or go to <strong>Settings → Permalinks</strong> and click "Save Changes".</p>';
            echo '</div>';
        }
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        // General settings
        register_setting('fth_settings', 'fth_primary_color', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#2989C0',
        ));
        register_setting('fth_settings', 'fth_secondary_color', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#FE7434',
        ));

        register_setting('fth_settings', 'fth_brand_name', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'Yahia Dubai',
        ));
        
        register_setting('fth_settings', 'fth_affiliate_id', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '115387',
        ));
        
        register_setting('fth_settings', 'fth_items_per_page', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 12,
        ));
        
        register_setting('fth_settings', 'fth_booking_button_text', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'Book Now',
        ));
        
        register_setting('fth_settings', 'fth_search_placeholder', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'Search activities, tours, attractions...',
        ));
        
        register_setting('fth_settings', 'fth_default_currency', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'USD',
        ));

        register_setting('fth_settings', 'fth_scraperapi_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'ecdd48490f38ad039aace84101208f7a',
        ));

        register_setting('fth_settings', 'fth_things_hero_title', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'Worldwide Tours & Attractions',
        ));
        register_setting('fth_settings', 'fth_things_hero_subtitle', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => 'Discover trusted tours, attractions and experiences with a premium Yahia Dubai presentation.',
        ));
        register_setting('fth_settings', 'fth_things_hero_image', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920',
        ));
        register_setting('fth_settings', 'fth_hotels_hero_title', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'Worldwide Hotels',
        ));
        register_setting('fth_settings', 'fth_hotels_hero_subtitle', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => 'Compare hotel pages, amenities and live rates with a premium Yahia Dubai presentation.',
        ));
        register_setting('fth_settings', 'fth_hotels_hero_image', array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=1600',
        ));
        
        register_setting('fth_settings', 'fth_enable_reviews', array(
            'type'              => 'boolean',
            'default'           => true,
        ));
        
        register_setting('fth_settings', 'fth_enable_ratings', array(
            'type'              => 'boolean',
            'default'           => true,
        ));
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Save message
        if (isset($_GET['settings-updated'])) {
            add_settings_error('fth_messages', 'fth_message', 'Settings saved successfully.', 'updated');
        }
        
        settings_errors('fth_messages');
        
        $primary_color = get_option('fth_primary_color', '#2989C0');
        $secondary_color = get_option('fth_secondary_color', '#FE7434');
        $brand_name = get_option('fth_brand_name', 'Yahia Dubai');
        $affiliate_id = get_option('fth_affiliate_id', '115387');
        $items_per_page = get_option('fth_items_per_page', 12);
        $booking_text = get_option('fth_booking_button_text', 'Book Now');
        $search_placeholder = get_option('fth_search_placeholder', 'Search activities, tours, attractions...');
        $default_currency = get_option('fth_default_currency', 'USD');
        $enable_reviews = get_option('fth_enable_reviews', true);
        $enable_ratings = get_option('fth_enable_ratings', true);
        $scraperapi_key = get_option('fth_scraperapi_key', 'ecdd48490f38ad039aace84101208f7a');
        $things_hero_title = get_option('fth_things_hero_title', 'Worldwide Tours & Attractions');
        $things_hero_subtitle = get_option('fth_things_hero_subtitle', 'Discover trusted tours, attractions and experiences with a premium Yahia Dubai presentation.');
        $things_hero_image = get_option('fth_things_hero_image', 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920');
        $hotels_hero_title = get_option('fth_hotels_hero_title', 'Worldwide Hotels');
        $hotels_hero_subtitle = get_option('fth_hotels_hero_subtitle', 'Compare hotel pages, amenities and live rates with a premium Yahia Dubai presentation.');
        $hotels_hero_image = get_option('fth_hotels_hero_image', 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=1600');
        ?>
        <div class="wrap fth-admin-wrap">
            <h1>
                <span class="dashicons dashicons-admin-settings" style="color: <?php echo esc_attr($primary_color); ?>;"></span>
                Travel Hub Settings
            </h1>
            <div class="notice notice-info"><p><strong>Step 1:</strong> Save your affiliate, ScraperAPI and hero content here. <strong>Step 2:</strong> import cities/activities/hotels. <strong>Step 3:</strong> regenerate hubs if needed. <strong>Step 4:</strong> refresh permalinks once if you see a 404.</p></div>
            
            <form method="post" action="options.php" class="fth-settings-form">
                <?php settings_fields('fth_settings'); ?>
                
                <div class="fth-settings-section">
                    <h2>Branding</h2>
                    <p class="description">Recommended for your site: Primary #2989C0, Secondary #FE7434, Brand name Yahia Dubai.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="fth_primary_color">Primary Brand Color</label>
                            </th>
                            <td>
                                <input type="color" id="fth_primary_color" name="fth_primary_color" value="<?php echo esc_attr($primary_color); ?>">
                                <input type="text" id="fth_primary_color_text" value="<?php echo esc_attr($primary_color); ?>" style="width: 100px; margin-left: 10px;">
                                <p class="description">Your brand color for buttons, links, and accents. Default: #19A880</p>
                            </td>
                        </tr>
                                            <tr>
                            <th scope="row">
                                <label for="fth_secondary_color">Secondary Accent Color</label>
                            </th>
                            <td>
                                <input type="color" id="fth_secondary_color" name="fth_secondary_color" value="<?php echo esc_attr($secondary_color); ?>">
                                <p class="description">Used for sale badges, highlights and callout accents.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fth_brand_name">Brand Name</label>
                            </th>
                            <td>
                                <input type="text" id="fth_brand_name" name="fth_brand_name" value="<?php echo esc_attr($brand_name); ?>" class="regular-text">
                                <p class="description">Frontend branding name used in generated copy and buttons.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="fth-settings-section">
                    <h2>Klook Affiliate</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="fth_affiliate_id">Affiliate ID</label>
                            </th>
                            <td>
                                <input type="text" id="fth_affiliate_id" name="fth_affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text">
                                <p class="description">Your Klook affiliate ID (e.g., 115387)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fth_scraperapi_key">ScraperAPI Key</label>
                            </th>
                            <td>
                                <input type="text" id="fth_scraperapi_key" name="fth_scraperapi_key" value="<?php echo esc_attr($scraperapi_key); ?>" class="regular-text">
                                <p class="description">Used to fetch Klook pages more reliably when direct requests are blocked.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fth_booking_button_text">Booking Button Text</label>
                            </th>
                            <td>
                                <input type="text" id="fth_booking_button_text" name="fth_booking_button_text" value="<?php echo esc_attr($booking_text); ?>" class="regular-text">
                                <p class="description">Text for the booking CTA button (e.g., "Book Now", "Book on Klook")</p>
                            </td>
                        </tr>
                                            <tr>
                            <th scope="row">
                                <label for="fth_secondary_color">Secondary Accent Color</label>
                            </th>
                            <td>
                                <input type="color" id="fth_secondary_color" name="fth_secondary_color" value="<?php echo esc_attr($secondary_color); ?>">
                                <p class="description">Used for sale badges, highlights and callout accents.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fth_brand_name">Brand Name</label>
                            </th>
                            <td>
                                <input type="text" id="fth_brand_name" name="fth_brand_name" value="<?php echo esc_attr($brand_name); ?>" class="regular-text">
                                <p class="description">Frontend branding name used in generated copy and buttons.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="fth-settings-section">
                    <h2>Display Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="fth_items_per_page">Items Per Page</label>
                            </th>
                            <td>
                                <input type="number" id="fth_items_per_page" name="fth_items_per_page" value="<?php echo esc_attr($items_per_page); ?>" min="4" max="48" class="small-text">
                                <p class="description">Number of activities to display per page in archives and search results</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fth_search_placeholder">Search Placeholder</label>
                            </th>
                            <td>
                                <input type="text" id="fth_search_placeholder" name="fth_search_placeholder" value="<?php echo esc_attr($search_placeholder); ?>" class="large-text">
                                <p class="description">Placeholder text for the search input</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fth_default_currency">Default Currency</label>
                            </th>
                            <td>
                                <select id="fth_default_currency" name="fth_default_currency">
                                    <option value="USD" <?php selected($default_currency, 'USD'); ?>>USD ($)</option>
                                    <option value="AED" <?php selected($default_currency, 'AED'); ?>>AED (د.إ)</option>
                                    <option value="EUR" <?php selected($default_currency, 'EUR'); ?>>EUR (€)</option>
                                    <option value="GBP" <?php selected($default_currency, 'GBP'); ?>>GBP (£)</option>
                                    <option value="SAR" <?php selected($default_currency, 'SAR'); ?>>SAR (﷼)</option>
                                    <option value="QAR" <?php selected($default_currency, 'QAR'); ?>>QAR (ر.ق)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Features</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="fth_enable_ratings" value="1" <?php checked($enable_ratings); ?>>
                                    Show ratings on activity cards
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="fth_enable_reviews" value="1" <?php checked($enable_reviews); ?>>
                                    Show review counts
                                </label>
                            </td>
                        </tr>
                                            <tr>
                            <th scope="row">
                                <label for="fth_secondary_color">Secondary Accent Color</label>
                            </th>
                            <td>
                                <input type="color" id="fth_secondary_color" name="fth_secondary_color" value="<?php echo esc_attr($secondary_color); ?>">
                                <p class="description">Used for sale badges, highlights and callout accents.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fth_brand_name">Brand Name</label>
                            </th>
                            <td>
                                <input type="text" id="fth_brand_name" name="fth_brand_name" value="<?php echo esc_attr($brand_name); ?>" class="regular-text">
                                <p class="description">Frontend branding name used in generated copy and buttons.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="fth-settings-section">
                    <h2>Tools</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Flush Permalinks</th>
                            <td>
                                <a href="<?php echo admin_url('options-permalink.php'); ?>" class="button">
                                    Go to Permalinks Settings
                                </a>
                                <p class="description">If your travel pages show 404 errors, visit Permalinks and click "Save Changes"</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Re-seed Data</th>
                            <td>
                                <button type="button" id="fth-reseed-data" class="button">
                                    Re-seed Countries, Cities & Categories
                                </button>
                                <p class="description">Re-run the initial data seeding if you need to restore default taxonomies</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Color picker sync
            $('#fth_primary_color').on('input', function() {
                $('#fth_primary_color_text').val($(this).val());
            });
            
            $('#fth_primary_color_text').on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $('#fth_primary_color').val(val);
                }
            });
            
            // Re-seed button
            $('#fth-reseed-data').on('click', function() {
                if (confirm('This will re-add any missing countries, cities, and categories. Continue?')) {
                    $(this).prop('disabled', true).text('Processing...');
                    
                    $.post(ajaxurl, {
                        action: 'fth_reseed_data',
                        nonce: '<?php echo wp_create_nonce('fth_reseed'); ?>'
                    }, function(response) {
                        $('#fth-reseed-data').prop('disabled', false).text('Re-seed Countries, Cities & Categories');
                        if (response.success) {
                            alert('Data re-seeded successfully!');
                        } else {
                            alert('Error: ' + response.data);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
}

// AJAX handler for re-seeding
add_action('wp_ajax_fth_reseed_data', function() {
    check_ajax_referer('fth_reseed', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }
    
    FTH_Seed_Data::seed_countries();
    FTH_Seed_Data::seed_cities();
    FTH_Seed_Data::seed_categories();
    FTH_Seed_Data::seed_types();
    
    wp_send_json_success();
});
