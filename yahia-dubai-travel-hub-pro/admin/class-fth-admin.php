<?php
/**
 * Admin Main Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Admin {
    
    /**
     * Initialize admin
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_filter('manage_travel_activity_posts_columns', array(__CLASS__, 'activity_columns'));
        add_action('manage_travel_activity_posts_custom_column', array(__CLASS__, 'activity_column_content'), 10, 2);
        add_filter('manage_travel_destination_posts_columns', array(__CLASS__, 'destination_columns'));
        add_action('manage_travel_destination_posts_custom_column', array(__CLASS__, 'destination_column_content'), 10, 2);
        add_action('admin_post_fth_regenerate_pages', array(__CLASS__, 'handle_regenerate_pages'));
        add_action('admin_post_fth_delete_generated_pages', array(__CLASS__, 'handle_delete_generated_pages'));
        add_action('admin_post_fth_delete_imported_media', array(__CLASS__, 'handle_delete_imported_media'));
    }
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        // Main menu
        add_menu_page(
            'Travel Hub',
            'Travel Hub',
            'edit_posts',
            'fth-travel-hub',
            array(__CLASS__, 'dashboard_page'),
            'dashicons-palmtree',
            26
        );
        
        // Dashboard
        add_submenu_page(
            'fth-travel-hub',
            'Dashboard',
            'Dashboard',
            'edit_posts',
            'fth-travel-hub',
            array(__CLASS__, 'dashboard_page')
        );
        
        // Destinations
        add_submenu_page(
            'fth-travel-hub',
            'Destinations',
            'Destinations',
            'edit_posts',
            'edit.php?post_type=travel_destination',
            null
        );
        
        // Activities
        add_submenu_page(
            'fth-travel-hub',
            'Activities',
            'Activities',
            'edit_posts',
            'edit.php?post_type=travel_activity',
            null
        );
        
        // Hotels
        add_submenu_page(
            'fth-travel-hub',
            'Hotels',
            'Hotels',
            'edit_posts',
            'edit.php?post_type=travel_hotel',
            null
        );
        
        // Countries
        add_submenu_page(
            'fth-travel-hub',
            'Countries',
            'Countries',
            'edit_posts',
            'edit-tags.php?taxonomy=travel_country',
            null
        );
        
        // Cities
        add_submenu_page(
            'fth-travel-hub',
            'Cities',
            'Cities',
            'edit_posts',
            'edit-tags.php?taxonomy=travel_city',
            null
        );
        
        // Categories
        add_submenu_page(
            'fth-travel-hub',
            'Categories',
            'Categories',
            'edit_posts',
            'edit-tags.php?taxonomy=travel_category',
            null
        );
        
        // Types
        add_submenu_page(
            'fth-travel-hub',
            'Types',
            'Types',
            'edit_posts',
            'edit-tags.php?taxonomy=travel_type',
            null
        );
        
        // IMPORT FROM KLOOK - Main feature
        add_submenu_page(
            'fth-travel-hub',
            'Import from Klook',
            '🚀 Import Klook',
            'edit_posts',
            'fth-klook-import',
            array(__CLASS__, 'klook_import_page')
        );
        
        // Tools
        add_submenu_page(
            'fth-travel-hub',
            'Tools',
            'Tools',
            'manage_options',
            'fth-tools',
            array(__CLASS__, 'tools_page')
        );

        // Settings
        add_submenu_page(
            'fth-travel-hub',
            'Settings',
            'Settings',
            'manage_options',
            'fth-settings',
            array('FTH_Admin_Settings', 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public static function enqueue_scripts($hook) {
        $screen = get_current_screen();
        
        // Check if we're on a Travel Hub page
        $is_fth_page = (
            strpos($hook, 'fth-') !== false ||
            (isset($screen->post_type) && in_array($screen->post_type, array('travel_activity', 'travel_destination', 'travel_hotel'))) ||
            (isset($screen->taxonomy) && in_array($screen->taxonomy, array('travel_country', 'travel_city', 'travel_category', 'travel_type')))
        );
        
        if (!$is_fth_page) {
            return;
        }
        
        // Media uploader
        wp_enqueue_media();
        
        // Admin CSS
        wp_enqueue_style(
            'fth-admin',
            FTH_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FTH_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'fth-admin',
            FTH_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            FTH_VERSION,
            true
        );
        
        wp_localize_script('fth-admin', 'fthAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fth_admin_nonce'),
        ));
    }
    
    /**
     * Dashboard page
     */
    public static function dashboard_page() {
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        
        // Get stats
        $activities_count = wp_count_posts('travel_activity')->publish;
        $destinations_count = wp_count_posts('travel_destination')->publish;
        $hotels_count = wp_count_posts('travel_hotel')->publish;
        $countries = FTH_Taxonomies::get_countries();
        $cities = FTH_Taxonomies::get_cities();
        $categories = FTH_Taxonomies::get_categories();
        
        // Get recent activities
        $recent_activities = get_posts(array(
            'post_type'      => 'travel_activity',
            'posts_per_page' => 5,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));
        ?>
        <div class="wrap fth-admin-wrap">
            <h1 class="fth-admin-title">
                <span class="dashicons dashicons-palmtree" style="color: <?php echo esc_attr($primary_color); ?>;"></span>
                Travel Hub Dashboard
            </h1>
            
            <div class="notice notice-info" style="padding:12px 16px;border-left-color: <?php echo esc_attr($primary_color); ?>;">
                <p style="margin:0 0 8px;"><strong>Quick start</strong></p>
                <ol style="margin:0 0 0 18px;">
                    <li>Open <strong>Settings</strong> and confirm your affiliate ID, ScraperAPI key, brand colors and brand name.</li>
                    <li>Open <strong>🚀 Import Klook</strong> to import a city, attractions/tours, or a hotel listing.</li>
                    <li>Check the generated content under <strong>Activities</strong>, <strong>Hotels</strong>, <strong>Cities</strong>, and <strong>Countries</strong>.</li>
                    <li>Use <strong>Tools</strong> to regenerate hubs or clean generated hub pages if you want to rebuild from scratch.</li>
                </ol>
            </div>

            <div class="fth-admin-stats">
                <div class="fth-stat-card">
                    <div class="fth-stat-icon" style="background-color: <?php echo esc_attr($primary_color); ?>20; color: <?php echo esc_attr($primary_color); ?>;">
                        <span class="dashicons dashicons-tickets-alt"></span>
                    </div>
                    <div class="fth-stat-content">
                        <span class="fth-stat-number"><?php echo esc_html($activities_count); ?></span>
                        <span class="fth-stat-label">Activities</span>
                    </div>
                    <a href="<?php echo admin_url('edit.php?post_type=travel_activity'); ?>" class="fth-stat-link">View All</a>
                </div>
                
                <div class="fth-stat-card">
                    <div class="fth-stat-icon" style="background-color: #e74c3c20; color: #e74c3c;">
                        <span class="dashicons dashicons-location-alt"></span>
                    </div>
                    <div class="fth-stat-content">
                        <span class="fth-stat-number"><?php echo esc_html($destinations_count); ?></span>
                        <span class="fth-stat-label">Destinations</span>
                    </div>
                    <a href="<?php echo admin_url('edit.php?post_type=travel_destination'); ?>" class="fth-stat-link">View All</a>
                </div>
                
                <div class="fth-stat-card">
                    <div class="fth-stat-icon" style="background-color: #3498db20; color: #3498db;">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                    </div>
                    <div class="fth-stat-content">
                        <span class="fth-stat-number"><?php echo count($cities); ?></span>
                        <span class="fth-stat-label">Cities</span>
                    </div>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=travel_city'); ?>" class="fth-stat-link">View All</a>
                </div>
                
                <div class="fth-stat-card">
                    <div class="fth-stat-icon" style="background-color: #9b59b620; color: #9b59b6;">
                        <span class="dashicons dashicons-flag"></span>
                    </div>
                    <div class="fth-stat-content">
                        <span class="fth-stat-number"><?php echo count($countries); ?></span>
                        <span class="fth-stat-label">Countries</span>
                    </div>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=travel_country'); ?>" class="fth-stat-link">View All</a>
                </div>
                
                <div class="fth-stat-card">
                    <div class="fth-stat-icon" style="background-color: #f39c1220; color: #f39c12;">
                        <span class="dashicons dashicons-category"></span>
                    </div>
                    <div class="fth-stat-content">
                        <span class="fth-stat-number"><?php echo count($categories); ?></span>
                        <span class="fth-stat-label">Categories</span>
                    </div>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=travel_category'); ?>" class="fth-stat-link">View All</a>
                </div>
                
                <div class="fth-stat-card">
                    <div class="fth-stat-icon" style="background-color: #1abc9c20; color: #1abc9c;">
                        <span class="dashicons dashicons-building"></span>
                    </div>
                    <div class="fth-stat-content">
                        <span class="fth-stat-number"><?php echo esc_html($hotels_count); ?></span>
                        <span class="fth-stat-label">Hotels</span>
                    </div>
                    <a href="<?php echo admin_url('edit.php?post_type=travel_hotel'); ?>" class="fth-stat-link">View All</a>
                </div>
            </div>
            
            <div class="fth-admin-grid">
                <div class="fth-admin-panel">
                    <h2>Quick Actions</h2>
                    <div class="fth-quick-actions">
                        <a href="<?php echo admin_url('post-new.php?post_type=travel_activity'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span> Add Activity
                        </a>
                        <a href="<?php echo admin_url('post-new.php?post_type=travel_destination'); ?>" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span> Add Destination
                        </a>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=travel_city'); ?>" class="button">
                            <span class="dashicons dashicons-plus-alt"></span> Add City
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=fth-settings'); ?>" class="button">
                            <span class="dashicons dashicons-admin-settings"></span> Settings
                        </a>
                    </div>
                </div>
                
                <div class="fth-admin-panel">
                    <h2>Recent Activities</h2>
                    <?php if ($recent_activities) : ?>
                        <ul class="fth-recent-list">
                            <?php foreach ($recent_activities as $activity) : ?>
                                <li>
                                    <a href="<?php echo get_edit_post_link($activity->ID); ?>">
                                        <?php echo esc_html($activity->post_title); ?>
                                    </a>
                                    <span class="fth-recent-date"><?php echo get_the_date('M j, Y', $activity); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p>No activities yet. <a href="<?php echo admin_url('post-new.php?post_type=travel_activity'); ?>">Create your first activity</a></p>
                    <?php endif; ?>
                </div>
                
                <div class="fth-admin-panel">
                    <h2>Shortcodes</h2>
                    <div class="fth-shortcode-list">
                        <div class="fth-shortcode-item">
                            <code>[fth_travel_hub]</code>
                            <span>Main travel hub page with search, cities, and featured activities</span>
                        </div>
                        <div class="fth-shortcode-item">
                            <code>[fth_search_form]</code>
                            <span>Search form only</span>
                        </div>
                        <div class="fth-shortcode-item">
                            <code>[fth_featured_activities count="6"]</code>
                            <span>Featured activities grid</span>
                        </div>
                        <div class="fth-shortcode-item">
                            <code>[fth_featured_cities count="6"]</code>
                            <span>Popular cities grid</span>
                        </div>
                        <div class="fth-shortcode-item">
                            <code>[fth_categories]</code>
                            <span>Categories grid</span>
                        </div>
                        <div class="fth-shortcode-item">
                            <code>[fth_city_activities city="dubai"]</code>
                            <span>Activities for a specific city</span>
                        </div>
                    </div>
                </div>
                
                <div class="fth-admin-panel">
                    <h2>Pages & Links</h2>
                    <ul class="fth-links-list">
                        <li>
                            <strong>Main Hub:</strong>
                            <a href="<?php echo home_url('/things-to-do/'); ?>" target="_blank"><?php echo home_url('/things-to-do/'); ?></a>
                        </li>
                        <li>
                            <strong>All Activities:</strong>
                            <a href="<?php echo get_post_type_archive_link('travel_activity'); ?>" target="_blank">View Archive</a>
                        </li>
                        <li>
                            <strong>All Destinations:</strong>
                            <a href="<?php echo get_post_type_archive_link('travel_destination'); ?>" target="_blank">View Archive</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Activity columns
     */
    public static function activity_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            if ($key === 'title') {
                $new_columns[$key] = $value;
                $new_columns['fth_image'] = 'Image';
                $new_columns['fth_price'] = 'Price';
                $new_columns['fth_rating'] = 'Rating';
                $new_columns['fth_featured'] = 'Featured';
            } else {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Activity column content
     */
    public static function activity_column_content($column, $post_id) {
        switch ($column) {
            case 'fth_image':
                $external_image = get_post_meta($post_id, '_fth_external_image', true);
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50), array('style' => 'border-radius: 4px;'));
                } elseif ($external_image) {
                    echo '<img src="' . esc_url($external_image) . '" width="50" height="50" style="object-fit: cover; border-radius: 4px;">';
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="font-size: 30px; color: #ccc;"></span>';
                }
                break;
                
            case 'fth_price':
                $price = get_post_meta($post_id, '_fth_price', true);
                $currency = get_post_meta($post_id, '_fth_currency', true) ?: 'USD';
                if ($price) {
                    echo esc_html($currency . ' ' . number_format((float)$price, 2));
                } else {
                    echo '—';
                }
                break;
                
            case 'fth_rating':
                $rating = get_post_meta($post_id, '_fth_rating', true);
                $reviews = get_post_meta($post_id, '_fth_review_count', true);
                if ($rating) {
                    echo '<span style="color: #f39c12;">★</span> ' . esc_html($rating);
                    if ($reviews) {
                        echo ' <small>(' . esc_html($reviews) . ')</small>';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'fth_featured':
                $is_featured = get_post_meta($post_id, '_fth_is_featured', true);
                $is_bestseller = get_post_meta($post_id, '_fth_is_bestseller', true);
                if ($is_featured === '1') {
                    echo '<span class="dashicons dashicons-star-filled" style="color: #f39c12;" title="Featured"></span>';
                }
                if ($is_bestseller === '1') {
                    echo '<span class="dashicons dashicons-awards" style="color: #19A880;" title="Bestseller"></span>';
                }
                if ($is_featured !== '1' && $is_bestseller !== '1') {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Destination columns
     */
    public static function destination_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            if ($key === 'title') {
                $new_columns[$key] = $value;
                $new_columns['fth_image'] = 'Hero Image';
                $new_columns['fth_featured'] = 'Featured';
            } else {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Destination column content
     */
    public static function destination_column_content($column, $post_id) {
        switch ($column) {
            case 'fth_image':
                $external_image = get_post_meta($post_id, '_fth_external_image', true);
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(80, 50), array('style' => 'border-radius: 4px; object-fit: cover;'));
                } elseif ($external_image) {
                    echo '<img src="' . esc_url($external_image) . '" width="80" height="50" style="object-fit: cover; border-radius: 4px;">';
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="font-size: 30px; color: #ccc;"></span>';
                }
                break;
                
            case 'fth_featured':
                $is_featured = get_post_meta($post_id, '_fth_is_featured', true);
                if ($is_featured === '1') {
                    echo '<span class="dashicons dashicons-star-filled" style="color: #f39c12;"></span>';
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Klook Import Page - Main Import Feature
     */

    public static function tools_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $regenerate_url = wp_nonce_url(admin_url('admin-post.php?action=fth_regenerate_pages'), 'fth_regenerate_pages');
        $delete_url = wp_nonce_url(admin_url('admin-post.php?action=fth_delete_generated_pages'), 'fth_delete_generated_pages');
        $delete_media_url = wp_nonce_url(admin_url('admin-post.php?action=fth_delete_imported_media'), 'fth_delete_imported_media');
        ?>
        <div class="wrap fth-admin-wrap">
            <h1>Travel Hub Tools</h1>
            <div class="notice notice-info"><p><strong>Step 1.</strong> Regenerate the two hub pages if they are missing. <strong>Step 2.</strong> Save permalinks if you see a 404. <strong>Step 3.</strong> Use delete only if you want to rebuild the hubs from scratch.</p></div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;max-width:1320px;">
                <div style="background:#fff;padding:20px;border:1px solid #e5e7eb;border-radius:12px;">
                    <h2 style="margin-top:0;">1. Regenerate hub pages</h2>
                    <p>Create or update the <strong>Worldwide Tours & Attractions</strong> and <strong>Worldwide Hotels</strong> pages again.</p>
                    <a class="button button-primary" href="<?php echo esc_url($regenerate_url); ?>">Regenerate hub pages</a>
                </div>
                <div style="background:#fff;padding:20px;border:1px solid #e5e7eb;border-radius:12px;">
                    <h2 style="margin-top:0;">2. Refresh permalinks</h2>
                    <p>If any generated page opens as not found, go to <strong>Settings → Permalinks</strong> and click <strong>Save Changes</strong>.</p>
                </div>
                <div style="background:#fff;padding:20px;border:1px solid #e5e7eb;border-radius:12px;">
                    <h2 style="margin-top:0;">3. Delete generated hub pages</h2>
                    <p>This only deletes the hub pages created by the plugin. It does not delete imported activities, hotels, cities, or countries.</p>
                    <a class="button" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Delete generated hub pages?');">Delete hub pages</a>
                </div>
                <div style="background:#fff;padding:20px;border:1px solid #e5e7eb;border-radius:12px;">
                    <h2 style="margin-top:0;">4. Delete imported media copies</h2>
                    <p>Use this when you want to clean the copied hotel and activity images created by the plugin before reinstalling or reimporting.</p>
                    <a class="button button-secondary" href="<?php echo esc_url($delete_media_url); ?>" onclick="return confirm('Delete imported media linked to travel posts?');">Delete imported images</a>
                </div>
            </div>
        </div>
        <?php
    }

    public static function handle_regenerate_pages() {
        if (!current_user_can('manage_options') || !check_admin_referer('fth_regenerate_pages')) {
            wp_die('Unauthorized');
        }
        $main_page = get_page_by_path('things-to-do');
        if (!$main_page) {
            wp_insert_post(array('post_title'=>'Worldwide Tours & Attractions','post_name'=>'things-to-do','post_content'=>'[fth_travel_hub]','post_status'=>'publish','post_type'=>'page'));
        }
        $hotel_page = get_page_by_path('hotels');
        if (!$hotel_page) {
            wp_insert_post(array('post_title'=>'Worldwide Hotels','post_name'=>'hotels','post_content'=>'[fth_hotels_hub]','post_status'=>'publish','post_type'=>'page'));
        }
        flush_rewrite_rules();
        wp_safe_redirect(admin_url('admin.php?page=fth-tools&regenerated=1'));
        exit;
    }

    public static function handle_delete_generated_pages() {
        if (!current_user_can('manage_options') || !check_admin_referer('fth_delete_generated_pages')) {
            wp_die('Unauthorized');
        }
        foreach (array('things-to-do','hotels') as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                wp_delete_post($page->ID, true);
            }
        }
        flush_rewrite_rules();
        wp_safe_redirect(admin_url('admin.php?page=fth-tools&deleted=1'));
        exit;
    }


    public static function handle_delete_imported_media() {
        if (!current_user_can('manage_options') || !check_admin_referer('fth_delete_imported_media')) {
            wp_die('Unauthorized');
        }
        $post_ids = get_posts(array(
            'post_type' => array('travel_activity', 'travel_hotel'),
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));
        foreach ($post_ids as $post_id) {
            $attachment_ids = array();
            $thumb_id = (int) get_post_thumbnail_id($post_id);
            if ($thumb_id) {
                $attachment_ids[] = $thumb_id;
                delete_post_thumbnail($post_id);
            }
            $gallery_ids = array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, '_fth_gallery', true))));
            $attachment_ids = array_merge($attachment_ids, $gallery_ids);
            $tracked = array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, '_fth_imported_attachment_ids', true))));
            $attachment_ids = array_merge($attachment_ids, $tracked);
            foreach (array_unique($attachment_ids) as $aid) {
                if ($aid) {
                    wp_delete_attachment($aid, true);
                }
            }
            delete_post_meta($post_id, '_fth_gallery');
            delete_post_meta($post_id, '_fth_external_gallery');
            delete_post_meta($post_id, '_fth_external_image');
            delete_post_meta($post_id, '_fth_imported_attachment_ids');
        }
        wp_safe_redirect(admin_url('admin.php?page=fth-tools&media_deleted=1'));
        exit;
    }

    public static function klook_import_page() {
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        $affiliate_id = Flavor_Travel_Hub::get_affiliate_id();
        
        // Get cities and countries for dropdowns
        $cities = FTH_Taxonomies::get_cities(array('hide_empty' => false));
        $countries = FTH_Taxonomies::get_countries(array('hide_empty' => false));
        $categories = FTH_Taxonomies::get_categories(array('hide_empty' => false));
        ?>
        <div class="wrap fth-admin-wrap">
            <h1 class="fth-admin-title">
                <span class="dashicons dashicons-download" style="color: <?php echo esc_attr($primary_color); ?>;"></span>
                Import from Klook
            </h1>
            <div class="notice notice-info"><p><strong>Step 1:</strong> Import or create the city. <strong>Step 2:</strong> import one activity or hotel to validate the layout. <strong>Step 3:</strong> use the bulk city importer for activities or hotels. <strong>Step 4:</strong> regenerate hubs and refresh permalinks once if a new city page returns 404.</p></div>
            
            <div class="fth-import-container">
                <!-- Import Activity -->
                <div class="fth-import-panel" style="background: linear-gradient(135deg, #ff5722 0%, #ff9800 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 10px; font-size: 24px;">1️⃣ Import one activity from Klook</h2>
                    <p style="margin: 0 0 20px; opacity: 0.9;">Paste a Klook activity URL to automatically import all details and publish</p>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Klook Activity URL</label>
                        <input type="text" id="fth_import_activity_url" style="width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 14px;" placeholder="https://www.klook.com/activity/12345-activity-name/">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">City</label>
                            <select id="fth_import_activity_city" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo esc_attr($city->term_id); ?>" <?php echo $city->slug === 'dubai' ? 'selected' : ''; ?>><?php echo esc_html($city->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Country</label>
                            <select id="fth_import_activity_country" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo esc_attr($country->term_id); ?>"><?php echo esc_html($country->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Category</label>
                            <select id="fth_import_activity_category" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600;">Image URL <span style="font-weight:400;opacity:0.8;">(optional — paste a direct image URL if auto-import fails)</span></label>
                        <input type="url" id="fth_import_activity_image_url" style="width: 100%; padding: 10px; border: none; border-radius: 6px; font-size: 13px;" placeholder="https://res.klook.com/image/upload/...">
                    </div>

                    <div style="display: flex; gap: 15px; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="fth_import_activity_featured" value="1">
                            <span>Featured</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="fth_import_activity_bestseller" value="1">
                            <span>Bestseller</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="fth_import_activity_publish" value="1" checked>
                            <span>Publish immediately</span>
                        </label>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="button" id="fth_import_activity_btn" class="button" style="background: #fff; color: #ff5722; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">
                            ⚡ Import Activity & Publish
                        </button>
                    </div>
                    
                    <div id="fth_import_activity_status" style="margin-top: 15px; padding: 12px; border-radius: 6px; display: none;"></div>
                </div>
                
                <!-- Import City/Destination -->
                <div class="fth-import-panel" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 10px; font-size: 24px;">2️⃣ Import one city from Klook</h2>
                    <p style="margin: 0 0 20px; opacity: 0.9;">Paste a Klook city/destination URL to create a new city</p>
                    
                    <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Klook City/Destination URL</label>
                        <input type="text" id="fth_import_city_url" style="width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 14px;" placeholder="https://www.klook.com/city/123-dubai/">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Parent Country</label>
                        <select id="fth_import_city_country" style="width: 100%; max-width: 300px; padding: 10px; border: none; border-radius: 6px;">
                            <option value="">Select Country</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo esc_attr($country->term_id); ?>"><?php echo esc_html($country->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="button" id="fth_import_city_btn" class="button" style="background: #fff; color: #3498db; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">
                        ⚡ Import City
                    </button>
                    
                    <div id="fth_import_city_status" style="margin-top: 15px; padding: 12px; border-radius: 6px; display: none;"></div>
                </div>
                

<!-- Bulk Import Activities -->
<div class="fth-import-panel" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
    <h2 style="margin: 0 0 10px; font-size: 24px;">3️⃣ Fetch all activities from a city</h2>
    <p style="margin: 0 0 20px; opacity: 0.9;">Paste a Klook destination URL to import multiple activities in one click</p>
    <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Klook Destination URL</label>
        <input type="text" id="fth_bulk_city_url" style="width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 14px;" placeholder="https://www.klook.com/destination/c78-dubai/">
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 140px; gap: 15px; margin-bottom: 20px;">
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">City</label>
            <select id="fth_bulk_city_term" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                <option value="">Select City</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?php echo esc_attr($city->term_id); ?>" <?php echo $city->slug === 'dubai' ? 'selected' : ''; ?>><?php echo esc_html($city->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Country</label>
            <select id="fth_bulk_country" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                <option value="">Select Country</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo esc_attr($country->term_id); ?>"><?php echo esc_html($country->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Category</label>
            <select id="fth_bulk_category" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Limit</label>
            <input type="number" id="fth_bulk_limit" value="60" min="1" max="200" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
        </div>
    </div>
    <button type="button" id="fth_bulk_import_btn" class="button" style="background: #fff; color: #2575fc; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">
        ⚡ Fetch All Activities
    </button>
    <div id="fth_bulk_import_status" style="margin-top: 15px; padding: 12px; border-radius: 6px; display: none;"></div>
</div>


                <!-- Import Hotel -->
                <div class="fth-import-panel" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 10px; font-size: 24px;">4️⃣ Import one hotel from Klook</h2>
                    <p style="margin: 0 0 20px; opacity: 0.9;">Use a Klook hotel detail URL here. Hotels are imported into their own hotel post type, not as activities.</p>
                    <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Klook Hotel Detail URL</label>
                        <input type="text" id="fth_import_hotel_url" style="width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 14px;" placeholder="https://www.klook.com/en-US/hotels/detail/92226-rove-at-the-park/">
                    </div>
                    <div style="background: rgba(255,255,255,0.15); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 6px; font-weight: 600;">Image URL <span style="font-weight:400;opacity:0.8;">(optional)</span></label>
                        <input type="url" id="fth_import_hotel_image_url" style="width: 100%; padding: 10px; border: none; border-radius: 6px; font-size: 13px;" placeholder="https://res.klook.com/image/upload/...">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 160px; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">City</label>
                            <select id="fth_import_hotel_city" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo esc_attr($city->term_id); ?>" <?php echo $city->slug === 'dubai' ? 'selected' : ''; ?>><?php echo esc_html($city->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Country</label>
                            <select id="fth_import_hotel_country" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo esc_attr($country->term_id); ?>"><?php echo esc_html($country->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex;align-items:flex-end;">
                            <label style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="fth_import_hotel_publish" value="1" checked>
                                <span>Publish now</span>
                            </label>
                        </div>
                    </div>
                    <button type="button" id="fth_import_hotel_btn" class="button" style="background: #fff; color: #0f172a; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">⚡ Import Hotel & Publish</button>
                    <div id="fth_import_hotel_status" style="margin-top: 15px; padding: 12px; border-radius: 6px; display: none;"></div>
                </div>

                <!-- Bulk Import Hotels -->
                <div class="fth-import-panel" style="background: linear-gradient(135deg, #134e4a 0%, #115e59 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 10px; font-size: 24px;">5️⃣ Fetch many hotels from a city hotels page</h2>
                    <p style="margin: 0 0 20px; opacity: 0.9;">Use a hotels listing URL like /destination/c78-dubai/3-hotel/ to import multiple hotel pages with gallery, reviews, amenities and rate data when available.</p>
                    <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Klook Hotels Listing URL</label>
                        <input type="text" id="fth_bulk_hotel_url" style="width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 14px;" placeholder="https://www.klook.com/en-US/destination/c78-dubai/3-hotel/">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 140px; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">City</label>
                            <select id="fth_bulk_hotel_city" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo esc_attr($city->term_id); ?>" <?php echo $city->slug === 'dubai' ? 'selected' : ''; ?>><?php echo esc_html($city->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Country</label>
                            <select id="fth_bulk_hotel_country" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo esc_attr($country->term_id); ?>"><?php echo esc_html($country->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Limit</label>
                            <input type="number" id="fth_bulk_hotel_limit" value="12" min="1" max="120" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                        </div>
                    </div>
                    <button type="button" id="fth_bulk_import_hotel_btn" class="button" style="background: #fff; color: #115e59; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">⚡ Fetch All Hotels</button>
                    <div id="fth_bulk_import_hotel_status" style="margin-top: 15px; padding: 12px; border-radius: 6px; display: none;"></div>
                </div>

                <!-- Batch URL import -->
                <div class="fth-import-panel" style="background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 10px; font-size: 24px;">6️⃣ Batch import — paste a list of Klook URLs</h2>
                    <p style="margin: 0 0 20px; opacity: 0.9;">One URL per line. Mix of activities and hotels is fine — the importer auto-detects each type. Max 50 URLs per run.</p>
                    <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Klook URLs (one per line)</label>
                        <textarea id="fth_batch_urls" rows="8" style="width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 13px; font-family: monospace; resize: vertical;" placeholder="https://www.klook.com/activity/12345-...&#10;https://www.klook.com/activity/67890-...&#10;https://www.klook.com/en-US/hotels/detail/92226-..."></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">City</label>
                            <select id="fth_batch_city" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo esc_attr($city->term_id); ?>" <?php echo $city->slug === 'dubai' ? 'selected' : ''; ?>><?php echo esc_html($city->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Country</label>
                            <select id="fth_batch_country" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo esc_attr($country->term_id); ?>"><?php echo esc_html($country->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Category (for activities)</label>
                            <select id="fth_batch_category" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="button" id="fth_batch_import_btn" class="button" style="background: #fff; color: #7c3aed; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">
                        ⚡ Import All Listed URLs
                    </button>
                    <div id="fth_batch_import_status" style="margin-top: 15px; padding: 12px; border-radius: 6px; display: none;"></div>
                </div>

                <!-- Country-wide bulk import -->
                <div class="fth-import-panel" style="background: linear-gradient(135deg, #b45309 0%, #92400e 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 10px; font-size: 24px;">7️⃣ Import entire country — activities OR hotels</h2>
                    <p style="margin: 0 0 20px; opacity: 0.9;">Iterates through all cities in a country and fetches every activity or hotel it can find. Cities must be imported first so their Klook destination URLs are stored.</p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 140px; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Country</label>
                            <select id="fth_country_import_country" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select Country</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo esc_attr($country->term_id); ?>"><?php echo esc_html($country->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Import type</label>
                            <select id="fth_country_import_type" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="activities">Activities</option>
                                <option value="hotels">Hotels</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Category (activities)</label>
                            <select id="fth_country_import_category" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Limit/city</label>
                            <input type="number" id="fth_country_import_limit" value="30" min="5" max="150" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                        </div>
                    </div>
                    <button type="button" id="fth_country_import_btn" class="button" style="background: #fff; color: #92400e; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">⚡ Import Entire Country</button>
                    <div id="fth_country_import_status" style="margin-top: 15px; padding: 12px; border-radius: 6px; display: none;"></div>
                </div>

                <!-- Notes -->
                <div class="fth-import-panel" style="background: #f8f9fa; padding: 25px; border-radius: 12px; border: 1px solid #e9ecef;">
                    <h3 style="margin: 0 0 15px;">8️⃣ After importing — regenerate hubs</h3>
                    <ul style="margin: 0; padding-left: 20px; color: #666;">
                        <li>Find activities on Klook.com, copy the URL, paste above — all details, images and SEO are auto-filled.</li>
                        <li>Your affiliate ID (<strong><?php echo esc_html($affiliate_id); ?></strong>) is added automatically to every booking link.</li>
                        <li>If a new city page returns 404, go to <a href="<?php echo admin_url('options-permalink.php'); ?>">Settings → Permalinks</a> and click Save Changes.</li>
                        <li>Use <a href="<?php echo admin_url('admin.php?page=fth-tools'); ?>">Tools</a> to regenerate hub pages if they were deleted.</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Import Activity
            $('#fth_import_activity_btn').on('click', function() {
                var url = $('#fth_import_activity_url').val().trim();
                var $btn = $(this);
                var $status = $('#fth_import_activity_status');
                
                if (!url || !url.includes('klook.com')) {
                    $status.css('background', 'rgba(244,67,54,0.3)').text('Please enter a valid Klook URL').show();
                    return;
                }
                
                $btn.prop('disabled', true).text('Importing...');
                $status.css('background', 'rgba(255,255,255,0.2)').text('Fetching data from Klook...').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fth_import_and_publish',
                        type: 'activity',
                        url: url,
                        city: $('#fth_import_activity_city').val(),
                        country: $('#fth_import_activity_country').val(),
                        category: $('#fth_import_activity_category').val(),
                        is_featured: $('#fth_import_activity_featured').is(':checked') ? 1 : 0,
                        is_bestseller: $('#fth_import_activity_bestseller').is(':checked') ? 1 : 0,
                        publish: $('#fth_import_activity_publish').is(':checked') ? 1 : 0,
                        manual_image_url: $('#fth_import_activity_image_url').val().trim(),
                        nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.css('background', 'rgba(76,175,80,0.3)').html(
                                '✅ <strong>Success!</strong> Activity imported and published. ' +
                                '<a href="' + response.data.edit_url + '" style="color: #fff; text-decoration: underline;">Edit</a> | ' +
                                '<a href="' + response.data.view_url + '" target="_blank" style="color: #fff; text-decoration: underline;">View</a>'
                            ).show();
                            $('#fth_import_activity_url').val('');
                        } else {
                            $status.css('background', 'rgba(244,67,54,0.3)').text('❌ ' + (response.data ? response.data.message : 'Import failed')).show();
                        }
                    },
                    error: function(xhr) {
                        var msg = 'Network error';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) { msg = xhr.responseJSON.data.message; }
                        else if (xhr && xhr.responseText) { msg = xhr.responseText.substring(0, 240); }
                        $status.css('background', 'rgba(244,67,54,0.3)').text('❌ ' + msg).show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('⚡ Import Activity & Publish');
                    }
                });
            });
            

// Bulk Import
$('#fth_bulk_import_btn').on('click', function() {
    var url = $('#fth_bulk_city_url').val().trim();
    var $btn = $(this);
    var $status = $('#fth_bulk_import_status');

    if (!url || !url.includes('klook.com')) {
        $status.css('background', 'rgba(244,67,54,0.3)').text('Please enter a valid Klook destination URL').show();
        return;
    }

    $btn.prop('disabled', true).text('Fetching...');
    $status.css('background', 'rgba(255,255,255,0.2)').text('Fetching activity links and importing...').show();

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'fth_import_bulk_city',
            url: url,
            city: $('#fth_bulk_city_term').val(),
            country: $('#fth_bulk_country').val(),
            category: $('#fth_bulk_category').val(),
            limit: $('#fth_bulk_limit').val(),
            nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
        },
        success: function(response) {
            if (response.success) {
                $status.css('background', 'rgba(76,175,80,0.3)').text('✅ ' + response.data.message).show();
            } else {
                $status.css('background', 'rgba(244,67,54,0.3)').text('❌ ' + (response.data ? response.data.message : 'Bulk import failed')).show();
            }
        },
        error: function(xhr) {
            var msg = 'Network error';
            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) { msg = xhr.responseJSON.data.message; }
            else if (xhr && xhr.responseText) { msg = xhr.responseText.substring(0, 240); }
            $status.css('background', 'rgba(244,67,54,0.3)').text('❌ ' + msg).show();
        },
        complete: function() {
            $btn.prop('disabled', false).text('⚡ Fetch All Activities');
        }
    });
});

            // Import Hotel
            $('#fth_import_hotel_btn').on('click', function() {
                var url = $('#fth_import_hotel_url').val().trim();
                var $btn = $(this);
                var $status = $('#fth_import_hotel_status');
                if (!url || !url.includes('klook.com')) {
                    $status.css('background','rgba(244,67,54,0.3)').text('Please enter a valid Klook hotel URL').show();
                    return;
                }
                $btn.prop('disabled', true).text('Importing...');
                $status.css('background','rgba(255,255,255,0.2)').text('Fetching hotel data from Klook...').show();
                $.post(ajaxurl, {action:'fth_import_and_publish', type:'hotel', url:url, city:$('#fth_import_hotel_city').val(), country:$('#fth_import_hotel_country').val(), publish: $('#fth_import_hotel_publish').is(':checked') ? 1 : 0, manual_image_url: $('#fth_import_hotel_image_url').val().trim(), nonce:'<?php echo wp_create_nonce('fth_import_publish'); ?>'}, function(response){
                    if (response.success) {
                        $status.css('background','rgba(76,175,80,0.3)').html('✅ <strong>Success!</strong> Hotel imported. <a href="'+response.data.edit_url+'" style="color:#fff;text-decoration:underline;">Edit</a> | <a href="'+response.data.view_url+'" target="_blank" style="color:#fff;text-decoration:underline;">View</a>').show();
                        $('#fth_import_hotel_url').val('');
                    } else {
                        $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + (response.data ? response.data.message : 'Import failed')).show();
                    }
                }).fail(function(xhr){ var msg = 'Network error'; if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) { msg = xhr.responseJSON.data.message; } else if (xhr && xhr.responseText) { msg = xhr.responseText.substring(0,240); } $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + msg).show(); }).always(function(){ $btn.prop('disabled', false).text('⚡ Import Hotel & Publish'); });
            });

            // Bulk Import Hotels
            $('#fth_bulk_import_hotel_btn').on('click', function() {
                var url = $('#fth_bulk_hotel_url').val().trim();
                var $btn = $(this);
                var $status = $('#fth_bulk_import_hotel_status');
                if (!url || !url.includes('klook.com')) {
                    $status.css('background','rgba(244,67,54,0.3)').text('Please enter a valid Klook hotels URL').show();
                    return;
                }
                $btn.prop('disabled', true).text('Fetching...');
                $status.css('background','rgba(255,255,255,0.2)').text('Fetching hotel links and importing...').show();
                $.post(ajaxurl, {action:'fth_import_bulk_hotels', url:url, city:$('#fth_bulk_hotel_city').val(), country:$('#fth_bulk_hotel_country').val(), limit:$('#fth_bulk_hotel_limit').val(), nonce:'<?php echo wp_create_nonce('fth_import_publish'); ?>'}, function(response){
                    if (response.success) {
                        $status.css('background','rgba(76,175,80,0.3)').text('✅ ' + response.data.message).show();
                    } else {
                        $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + (response.data ? response.data.message : 'Bulk import failed')).show();
                    }
                }).fail(function(xhr){ var msg = 'Network error'; if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) { msg = xhr.responseJSON.data.message; } else if (xhr && xhr.responseText) { msg = xhr.responseText.substring(0,240); } $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + msg).show(); }).always(function(){ $btn.prop('disabled', false).text('⚡ Fetch All Hotels'); });
            });

            // Country-wide import
            $('#fth_country_import_btn').on('click', function() {
                var country = $('#fth_country_import_country').val();
                var $btn = $(this);
                var $status = $('#fth_country_import_status');
                if (!country) {
                    $status.css('background','rgba(244,67,54,0.3)').text('Please select a country').show();
                    return;
                }
                $btn.prop('disabled', true).text('Importing...');
                $status.css('background','rgba(255,255,255,0.2)').text('Iterating through cities... this can take several minutes.').show();
                $.post(ajaxurl, {
                    action: 'fth_import_bulk_country',
                    country: country,
                    import_type: $('#fth_country_import_type').val(),
                    category: $('#fth_country_import_category').val(),
                    limit: $('#fth_country_import_limit').val(),
                    nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
                }, function(response) {
                    if (response.success) {
                        $status.css('background','rgba(76,175,80,0.3)').text('✅ ' + response.data.message).show();
                    } else {
                        $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + (response.data ? response.data.message : 'Import failed')).show();
                    }
                }).fail(function(xhr){ var msg = 'Network error'; if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) { msg = xhr.responseJSON.data.message; } else if (xhr && xhr.responseText) { msg = xhr.responseText.substring(0,240); } $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + msg).show(); }).always(function(){ $btn.prop('disabled', false).text('⚡ Import Entire Country'); });
            });

            // Import City
            $('#fth_import_city_btn').on('click', function() {
                var url = $('#fth_import_city_url').val().trim();
                var $btn = $(this);
                var $status = $('#fth_import_city_status');
                
                if (!url || !url.includes('klook.com')) {
                    $status.css('background', 'rgba(244,67,54,0.3)').text('Please enter a valid Klook URL').show();
                    return;
                }
                
                $btn.prop('disabled', true).text('Importing...');
                $status.css('background', 'rgba(255,255,255,0.2)').text('Fetching data from Klook...').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fth_import_and_publish',
                        type: 'city',
                        url: url,
                        country: $('#fth_import_city_country').val(),
                        nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var verb = response.data.updated ? 'Updated' : 'Imported';
                            $status.css('background', 'rgba(76,175,80,0.3)').html(
                                '✅ <strong>' + verb + '!</strong> City ' + verb.toLowerCase() + '. ' +
                                '<a href="' + response.data.edit_url + '" style="color: #fff; text-decoration: underline;">Edit</a> | ' +
                                '<a href="' + response.data.view_url + '" target="_blank" style="color: #fff; text-decoration: underline;">View</a>'
                            ).show();
                            $('#fth_import_city_url').val('');
                            // Reload page to update city dropdown
                            setTimeout(function() { location.reload(); }, 2000);
                        } else {
                            $status.css('background', 'rgba(244,67,54,0.3)').text('❌ ' + (response.data ? response.data.message : 'Import failed')).show();
                        }
                    },
                    error: function(xhr) {
                        var msg = 'Network error';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) { msg = xhr.responseJSON.data.message; }
                        else if (xhr && xhr.responseText) { msg = xhr.responseText.substring(0, 240); }
                        $status.css('background', 'rgba(244,67,54,0.3)').text('❌ ' + msg).show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('⚡ Import City');
                    }
                });
            });
            // Batch URL import
            $('#fth_batch_import_btn').on('click', function() {
                var urls = $('#fth_batch_urls').val().trim();
                var $btn = $(this);
                var $status = $('#fth_batch_import_status');
                if (!urls) {
                    $status.css('background','rgba(244,67,54,0.3)').text('Please paste at least one Klook URL.').show();
                    return;
                }
                $btn.prop('disabled', true).text('Importing…');
                $status.css('background','rgba(255,255,255,0.2)').text('Sending URLs to importer — this may take a minute…').show();
                $.post(ajaxurl, {
                    action: 'fth_import_bulk_urls',
                    urls: urls,
                    type: 'auto',
                    city: $('#fth_batch_city').val(),
                    country: $('#fth_batch_country').val(),
                    category: $('#fth_batch_category').val(),
                    nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
                }, function(response) {
                    if (response.success) {
                        var html = '✅ ' + response.data.message;
                        if (response.data.results && response.data.results.length) {
                            html += '<ul style="margin:8px 0 0;padding-left:18px;">';
                            $.each(response.data.results, function(i, r) {
                                html += '<li><a href="' + r.view_url + '" target="_blank" style="color:#fff;text-decoration:underline;">' + r.url + '</a></li>';
                            });
                            html += '</ul>';
                        }
                        $status.css('background','rgba(76,175,80,0.3)').html(html).show();
                    } else {
                        $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + (response.data ? response.data.message : 'Batch import failed')).show();
                    }
                }).fail(function(xhr) {
                    var msg = 'Network error';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) { msg = xhr.responseJSON.data.message; }
                    else if (xhr && xhr.responseText) { msg = xhr.responseText.substring(0, 240); }
                    $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + msg).show();
                }).always(function() {
                    $btn.prop('disabled', false).text('⚡ Import All Listed URLs');
                });
            });
        });
        </script>
        <?php
    }
}
