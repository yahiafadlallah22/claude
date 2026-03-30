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
        add_action('wp_ajax_fth_delete_all_content', array(__CLASS__, 'handle_delete_all_content_ajax'));
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

        // Klook Links Library
        add_submenu_page(
            'fth-travel-hub',
            'Klook Links Library',
            '🔗 Klook Links',
            'edit_posts',
            'fth-klook-links',
            array(__CLASS__, 'klook_links_page')
        );

        // Marathon Import — dedicated bulk import page
        add_submenu_page(
            'fth-travel-hub',
            'Marathon Import',
            '🏃 Marathon Import',
            'manage_options',
            'fth-marathon',
            array(__CLASS__, 'marathon_page')
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

        // Stats
        $activities_count   = wp_count_posts('travel_activity')->publish;
        $hotels_count       = wp_count_posts('travel_hotel')->publish;
        $destinations_count = wp_count_posts('travel_destination')->publish;
        $countries_list     = get_terms(array('taxonomy' => 'travel_country', 'hide_empty' => false));
        $cities_list        = get_terms(array('taxonomy' => 'travel_city',    'hide_empty' => false));
        $categories_list    = get_terms(array('taxonomy' => 'travel_category','hide_empty' => false));
        $countries_list     = is_wp_error($countries_list) ? array() : $countries_list;
        $cities_list        = is_wp_error($cities_list)    ? array() : $cities_list;
        $categories_list    = is_wp_error($categories_list)? array() : $categories_list;

        // Recent imports
        $recent_activities = get_posts(array('post_type'=>'travel_activity','posts_per_page'=>6,'orderby'=>'date','order'=>'DESC'));
        $recent_hotels     = get_posts(array('post_type'=>'travel_hotel',   'posts_per_page'=>4,'orderby'=>'date','order'=>'DESC'));

        // Static hub pages (slug => label)
        $static_hubs = array(
            'things-to-do' => array('label' => '🗺 Things To Do',    'icon' => '🗺'),
            'hotels'        => array('label' => '🏨 Hotels',          'icon' => '🏨'),
            'passes'        => array('label' => '🎟 Passes & Tickets', 'icon' => '🎟'),
            'blog'          => array('label' => '📝 Blog',            'icon' => '📝'),
        );
        ?>
        <div class="wrap" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;max-width:1400px;">
        <h1 style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <span style="font-size:28px;">🌴</span>
            Travel Hub Dashboard
            <span style="font-size:12px;font-weight:400;color:#666;background:#f0f0f1;padding:3px 10px;border-radius:20px;">v<?php echo FTH_VERSION; ?></span>
        </h1>

        <!-- Quick links row -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:24px;">
            <a href="<?php echo admin_url('admin.php?page=fth-klook-import'); ?>" style="background:linear-gradient(135deg,#2575fc,#6a11cb);color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;">🚀 Import from Klook</a>
            <a href="<?php echo admin_url('admin.php?page=fth-klook-links'); ?>" style="background:#1e3a5f;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700;font-size:13px;">🔗 Klook Links Library</a>
            <a href="<?php echo admin_url('admin.php?page=fth-settings'); ?>" style="background:#fff;color:#333;border:1px solid #ddd;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;">⚙️ Settings</a>
            <a href="<?php echo admin_url('admin.php?page=fth-tools'); ?>" style="background:#fff;color:#333;border:1px solid #ddd;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;">🔧 Tools</a>
        </div>

        <!-- Stats bar -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:28px;">
            <?php
            $stat_cards = array(
                array('🎟', $activities_count, 'Activities',   admin_url('edit.php?post_type=travel_activity'), '#2575fc'),
                array('🏨', $hotels_count,     'Hotels',       admin_url('edit.php?post_type=travel_hotel'),    '#0e9f6e'),
                array('🗺', $destinations_count,'Destinations',admin_url('edit.php?post_type=travel_destination'),'#e3a008'),
                array('🏙', count($cities_list),'Cities',      admin_url('edit-tags.php?taxonomy=travel_city'), '#7c3aed'),
                array('🌍', count($countries_list),'Countries',admin_url('edit-tags.php?taxonomy=travel_country'),'#dc2626'),
                array('🏷', count($categories_list),'Categories',admin_url('edit-tags.php?taxonomy=travel_category'),'#d97706'),
            );
            foreach ($stat_cards as list($icon,$num,$label,$href,$clr)): ?>
            <a href="<?php echo esc_url($href); ?>" style="text-decoration:none;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 12px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.06);transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,.06)'">
                <div style="font-size:24px;"><?php echo $icon; ?></div>
                <div style="font-size:28px;font-weight:800;color:<?php echo $clr; ?>;line-height:1.1;"><?php echo number_format((int)$num); ?></div>
                <div style="font-size:11px;color:#666;font-weight:600;margin-top:2px;"><?php echo $label; ?></div>
            </a>
            <?php endforeach; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

        <!-- Hub Pages Overview -->
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <h2 style="margin:0 0 12px;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;">🌐 Hub Pages <span style="font-size:11px;font-weight:400;color:#888;">All auto-generated public pages</span></h2>

            <!-- Auto-created pages regenerate bar -->
            <div style="background:#f0f7ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 12px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
                <span style="font-size:12px;color:#1e40af;font-weight:600;">Pages WordPress créées automatiquement (Things To Do, Hotels, Passes…)</span>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=fth_regenerate_pages'), 'fth_regenerate_pages')); ?>" class="button button-primary" style="font-size:12px;padding:4px 14px;height:auto;">🔄 Régénérer les pages</a>
            </div>

            <!-- Static pages -->
            <div style="margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Static Hubs</div>
                <?php foreach ($static_hubs as $slug => $meta):
                    $page = get_page_by_path($slug);
                    $url  = $page ? get_permalink($page->ID) : home_url('/' . $slug . '/');
                    $status = $page ? $page->post_status : 'missing';
                    $status_dot = $status === 'publish' ? '#0e9f6e' : ($status === 'draft' ? '#e3a008' : '#dc2626');
                    $status_lbl = $status === 'publish' ? 'Live' : ($status === 'draft' ? 'Draft' : 'Missing');
                ?>
                <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f3f4f6;">
                    <span style="font-size:16px;"><?php echo $meta['icon']; ?></span>
                    <span style="flex:1;font-size:13px;font-weight:600;"><?php echo esc_html($meta['label']); ?></span>
                    <span style="font-size:10px;font-weight:700;color:<?php echo $status_dot; ?>;background:<?php echo $status_dot; ?>20;padding:2px 8px;border-radius:20px;"><?php echo $status_lbl; ?></span>
                    <?php if ($page): ?>
                    <a href="<?php echo esc_url(get_edit_post_link($page->ID)); ?>" style="font-size:11px;color:#888;" title="Edit">✏️</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" style="font-size:11px;color:#2575fc;" title="View">↗</a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- City hub pages -->
            <?php if (!empty($cities_list)): ?>
            <div style="margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">City Hubs (<?php echo count($cities_list); ?>)</div>
                <div style="max-height:200px;overflow-y:auto;">
                <?php foreach ($cities_list as $city):
                    $city_url  = get_term_link($city);
                    $act_count = $city->count;
                ?>
                <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #f3f4f6;">
                    <span style="font-size:13px;">🏙</span>
                    <span style="flex:1;font-size:12px;font-weight:600;"><?php echo esc_html($city->name); ?></span>
                    <span style="font-size:10px;color:#888;"><?php echo $act_count; ?> posts</span>
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?action=edit&taxonomy=travel_city&tag_ID=' . $city->term_id)); ?>" style="font-size:11px;color:#888;" title="Edit">✏️</a>
                    <?php if (!is_wp_error($city_url)): ?>
                    <a href="<?php echo esc_url($city_url); ?>" target="_blank" style="font-size:11px;color:#2575fc;" title="View">↗</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Country hub pages -->
            <?php if (!empty($countries_list)): ?>
            <div style="margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Country Hubs (<?php echo count($countries_list); ?>)</div>
                <div style="max-height:140px;overflow-y:auto;">
                <?php foreach ($countries_list as $country):
                    $country_url = get_term_link($country);
                    $flag = FTH_Templates::get_country_flag($country->name);
                ?>
                <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #f3f4f6;">
                    <span style="font-size:13px;"><?php echo $flag; ?></span>
                    <span style="flex:1;font-size:12px;font-weight:600;"><?php echo esc_html($country->name); ?></span>
                    <span style="font-size:10px;color:#888;"><?php echo $country->count; ?> posts</span>
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?action=edit&taxonomy=travel_country&tag_ID=' . $country->term_id)); ?>" style="font-size:11px;color:#888;" title="Edit">✏️</a>
                    <?php if (!is_wp_error($country_url)): ?>
                    <a href="<?php echo esc_url($country_url); ?>" target="_blank" style="font-size:11px;color:#2575fc;" title="View">↗</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Category hub pages -->
            <?php if (!empty($categories_list)): ?>
            <div>
                <div style="font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Category Hubs (<?php echo count($categories_list); ?>)</div>
                <div style="max-height:140px;overflow-y:auto;">
                <?php foreach ($categories_list as $cat):
                    $cat_url = get_term_link($cat);
                    $emoji   = FTH_Templates::get_category_emoji($cat);
                ?>
                <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #f3f4f6;">
                    <span style="font-size:13px;"><?php echo $emoji; ?></span>
                    <span style="flex:1;font-size:12px;font-weight:600;"><?php echo esc_html($cat->name); ?></span>
                    <span style="font-size:10px;color:#888;"><?php echo $cat->count; ?> posts</span>
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?action=edit&taxonomy=travel_category&tag_ID=' . $cat->term_id)); ?>" style="font-size:11px;color:#888;" title="Edit">✏️</a>
                    <?php if (!is_wp_error($cat_url)): ?>
                    <a href="<?php echo esc_url($cat_url); ?>" target="_blank" style="font-size:11px;color:#2575fc;" title="View">↗</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right column: Recent + Shortcodes -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Recent Activities -->
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <h2 style="margin:0 0 12px;font-size:15px;font-weight:800;">🎟 Recent Activities</h2>
                <?php if ($recent_activities): ?>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <?php foreach ($recent_activities as $act):
                        $price = get_post_meta($act->ID, '_fth_price', true);
                        $city_terms = wp_get_post_terms($act->ID, 'travel_city', array('fields'=>'names'));
                        $city_lbl = !empty($city_terms) ? $city_terms[0] : '';
                    ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f3f4f6;">
                        <span style="flex:1;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;">
                            <a href="<?php echo get_edit_post_link($act->ID); ?>" style="color:#333;text-decoration:none;"><?php echo esc_html($act->post_title); ?></a>
                        </span>
                        <?php if ($city_lbl): ?><span style="font-size:10px;color:#888;"><?php echo esc_html($city_lbl); ?></span><?php endif; ?>
                        <?php if ($price): ?><span style="font-size:11px;font-weight:700;color:#0e9f6e;"><?php echo esc_html($price); ?></span><?php endif; ?>
                        <a href="<?php echo get_permalink($act->ID); ?>" target="_blank" style="font-size:11px;color:#2575fc;">↗</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color:#888;font-size:13px;">No activities yet. <a href="<?php echo admin_url('admin.php?page=fth-klook-import'); ?>">Import from Klook</a></p>
                <?php endif; ?>
            </div>

            <!-- Recent Hotels -->
            <?php if (!empty($recent_hotels)): ?>
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <h2 style="margin:0 0 12px;font-size:15px;font-weight:800;">🏨 Recent Hotels</h2>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <?php foreach ($recent_hotels as $htl):
                        $stars = get_post_meta($htl->ID, '_fth_stars', true);
                        $price = get_post_meta($htl->ID, '_fth_price', true);
                    ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f3f4f6;">
                        <span style="flex:1;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;">
                            <a href="<?php echo get_edit_post_link($htl->ID); ?>" style="color:#333;text-decoration:none;"><?php echo esc_html($htl->post_title); ?></a>
                        </span>
                        <?php if ($price): ?><span style="font-size:11px;font-weight:700;color:#0e9f6e;"><?php echo esc_html($price); ?></span><?php endif; ?>
                        <a href="<?php echo get_permalink($htl->ID); ?>" target="_blank" style="font-size:11px;color:#2575fc;">↗</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Shortcodes reference -->
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <h2 style="margin:0 0 12px;font-size:15px;font-weight:800;">📋 Shortcodes</h2>
                <?php
                $shortcodes = array(
                    '[fth_travel_hub]'                   => 'Main hub (search + cities + activities)',
                    '[fth_featured_activities count="6"]' => 'Featured activities grid',
                    '[fth_featured_cities count="6"]'     => 'Popular cities grid',
                    '[fth_categories]'                    => 'Category icons grid',
                    '[fth_city_activities city="dubai"]'  => 'City-specific activity grid',
                    '[fth_search_form]'                   => 'Search bar only',
                );
                foreach ($shortcodes as $sc => $desc): ?>
                <div style="display:flex;gap:10px;align-items:flex-start;padding:6px 0;border-bottom:1px solid #f3f4f6;">
                    <code style="font-size:11px;background:#f0f0f1;padding:2px 6px;border-radius:4px;white-space:nowrap;flex-shrink:0;"><?php echo esc_html($sc); ?></code>
                    <span style="font-size:11px;color:#666;"><?php echo esc_html($desc); ?></span>
                </div>
                <?php endforeach; ?>
            </div>

        </div><!-- /right col -->
        </div><!-- /grid -->
        </div><!-- /wrap -->
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


    /**
     * AJAX: Delete all imported activities / hotels (and optionally their media)
     */
    public static function handle_delete_all_content_ajax() {
        if (!current_user_can('manage_options') || !check_ajax_referer('fth_import_publish', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $do_activities = !empty($_POST['activities']);
        $do_hotels     = !empty($_POST['hotels']);
        $do_media      = !empty($_POST['media']);

        $do_destinations = !empty($_POST['destinations']);
        $do_terms        = !empty($_POST['terms']);

        if (!$do_activities && !$do_hotels && !$do_destinations && !$do_terms) {
            wp_send_json_error(array('message' => 'Nothing selected.'));
        }

        // Extend PHP execution time for large datasets
        if (function_exists('set_time_limit')) @set_time_limit(600);
        if (function_exists('ignore_user_abort')) @ignore_user_abort(true);

        $post_types = array();
        if ($do_activities) $post_types[] = 'travel_activity';
        if ($do_hotels)     $post_types[] = 'travel_hotel';

        $posts = array();
        if (!empty($post_types)) {
            $posts = get_posts(array(
                'post_type'      => $post_types,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ));
        }

        $deleted = 0;
        $media_deleted = 0;
        foreach ((array) $posts as $post_id) {
            if ($do_media) {
                // Delete thumbnail
                $thumb_id = (int) get_post_thumbnail_id($post_id);
                if ($thumb_id) {
                    wp_delete_attachment($thumb_id, true);
                    $media_deleted++;
                }
                // Delete gallery attachments
                $gallery_ids = array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, '_fth_gallery', true))));
                $tracked_ids = array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, '_fth_imported_attachment_ids', true))));
                foreach (array_unique(array_merge($gallery_ids, $tracked_ids)) as $aid) {
                    if ($aid) { wp_delete_attachment($aid, true); $media_deleted++; }
                }
            }
            wp_delete_post($post_id, true);
            $deleted++;
        }

        // Nuclear option: also delete destinations and all taxonomy terms
        $terms_deleted = 0;
        if ($do_destinations) {
            $dest_posts = get_posts(array('post_type' => 'travel_destination', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids'));
            foreach ((array) $dest_posts as $dp_id) {
                if ($do_media) {
                    $t_id = (int) get_post_thumbnail_id($dp_id);
                    if ($t_id) { wp_delete_attachment($t_id, true); $media_deleted++; }
                }
                wp_delete_post($dp_id, true);
                $deleted++;
            }
        }
        if ($do_terms) {
            foreach (array('travel_city', 'travel_country', 'travel_category') as $tax) {
                $all_terms = get_terms(array('taxonomy' => $tax, 'hide_empty' => false, 'fields' => 'ids'));
                if (!is_wp_error($all_terms)) {
                    foreach ((array) $all_terms as $tid) {
                        wp_delete_term((int) $tid, $tax);
                        $terms_deleted++;
                    }
                }
            }
        }

        $msg = 'Deleted ' . $deleted . ' post(s)';
        if ($do_media) $msg .= ' and ' . $media_deleted . ' media file(s)';
        if ($do_terms) $msg .= ' and ' . $terms_deleted . ' taxonomy term(s)';
        $msg .= '.';

        wp_send_json_success(array('message' => $msg, 'deleted' => $deleted, 'media_deleted' => $media_deleted, 'terms_deleted' => $terms_deleted));
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
            <label style="display: block; margin-bottom: 8px; font-weight: 600;">Category <span style="font-weight:400;opacity:0.75;">(auto from Klook)</span></label>
            <select id="fth_bulk_category" style="width: 100%; padding: 10px; border: none; border-radius: 6px;">
                <option value="">Auto-detect from Klook</option>
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
    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:10px;">
        <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:#fff;font-weight:600;cursor:pointer;">
            <input type="checkbox" id="fth_bulk_featured" value="1" style="width:16px;height:16px;accent-color:#fff;">
            ⭐ Mark as Featured
        </label>
        <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:#fff;font-weight:600;cursor:pointer;">
            <input type="checkbox" id="fth_bulk_popular" value="1" style="width:16px;height:16px;accent-color:#fff;">
            🔥 Mark as Popular
        </label>
    </div>
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <button type="button" id="fth_bulk_import_btn" class="button" style="background: #fff; color: #2575fc; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">
            ⚡ Import Activities Live
        </button>
        <button type="button" id="fth_bulk_import_stop" style="display:none;background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.5);padding:10px 22px;font-size:14px;font-weight:600;border-radius:6px;cursor:pointer;">⏹ Stop</button>
        <span id="fth_bulk_import_counter" style="font-size:13px;opacity:0.85;"></span>
    </div>
    <div id="fth_bulk_import_status" style="margin-top: 12px; padding: 10px 14px; border-radius: 6px; display: none;"></div>
    <!-- Live preview log -->
    <div id="fth_bulk_live_log" style="display:none;margin-top:16px;background:rgba(0,0,0,0.35);border-radius:10px;padding:14px;max-height:460px;overflow-y:auto;">
        <div style="font-size:12px;opacity:0.65;margin-bottom:10px;letter-spacing:0.4px;">LIVE IMPORT LOG — activities</div>
        <div id="fth_bulk_live_log_items"></div>
    </div>
    <!-- Marathon Import panel -->
    <div style="margin-top:24px;padding:20px;background:rgba(0,0,0,0.18);border-radius:12px;border:1px solid rgba(255,255,255,0.15);">
        <h4 style="margin:0 0 12px;color:#fff;font-size:14px;font-weight:800;letter-spacing:0.3px;">🏃 Marathon Import — import everything, 5 at a time</h4>
        <p style="margin:0 0 14px;font-size:12px;color:rgba(255,255,255,0.7);">Discovers ALL pages and imports in batches of 5. Leave this tab open — it will run until complete.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px;margin-bottom:14px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:rgba(255,255,255,0.8);">Destination URL</label>
                <input type="text" id="fth_marathon_url" placeholder="https://www.klook.com/en-US/..." style="width:100%;padding:8px 10px;border:none;border-radius:6px;font-size:13px;">
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:rgba(255,255,255,0.8);">City</label>
                <select id="fth_marathon_city" style="width:100%;padding:8px 10px;border:none;border-radius:6px;font-size:13px;">
                    <option value="">— select —</option>
                    <?php foreach ($cities as $city): ?>
                    <option value="<?php echo esc_attr($city->term_id); ?>"><?php echo esc_html($city->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:rgba(255,255,255,0.8);">Country</label>
                <select id="fth_marathon_country" style="width:100%;padding:8px 10px;border:none;border-radius:6px;font-size:13px;">
                    <option value="">— select —</option>
                    <?php foreach ($countries as $country): ?>
                    <option value="<?php echo esc_attr($country->term_id); ?>"><?php echo esc_html($country->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:rgba(255,255,255,0.8);">Type</label>
                <select id="fth_marathon_type" style="width:100%;padding:8px 10px;border:none;border-radius:6px;font-size:13px;">
                    <option value="activity">Activities</option>
                    <option value="hotel">Hotels</option>
                    <option value="both">Both (activities then hotels)</option>
                </select>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
            <button type="button" id="fth_marathon_btn" style="background:#fff;color:#2575fc;border:none;padding:10px 24px;font-size:14px;font-weight:800;border-radius:6px;cursor:pointer;">🚀 Start Marathon</button>
            <button type="button" id="fth_marathon_stop" style="display:none;background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.5);padding:8px 18px;font-size:13px;font-weight:600;border-radius:6px;cursor:pointer;">⏹ Stop</button>
            <span id="fth_marathon_counter" style="font-size:13px;color:rgba(255,255,255,0.85);"></span>
        </div>
        <div id="fth_marathon_status" style="display:none;padding:10px 14px;border-radius:6px;font-size:13px;margin-bottom:10px;"></div>
        <div id="fth_marathon_log" style="display:none;background:rgba(0,0,0,0.4);border-radius:8px;padding:12px;max-height:300px;overflow-y:auto;font-size:11px;font-family:monospace;">
            <div id="fth_marathon_log_items"></div>
        </div>
    </div>
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
                    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:10px;">
                        <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:#fff;font-weight:600;cursor:pointer;">
                            <input type="checkbox" id="fth_bulk_hotel_featured" value="1" style="width:16px;height:16px;accent-color:#fff;">
                            ⭐ Mark as Featured
                        </label>
                        <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:#fff;font-weight:600;cursor:pointer;">
                            <input type="checkbox" id="fth_bulk_hotel_popular" value="1" style="width:16px;height:16px;accent-color:#fff;">
                            🔥 Mark as Popular
                        </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <button type="button" id="fth_bulk_import_hotel_btn" class="button" style="background: #fff; color: #115e59; border: none; padding: 12px 30px; font-size: 16px; font-weight: 700; border-radius: 6px; cursor: pointer;">⚡ Import Hotels Live</button>
                        <button type="button" id="fth_bulk_hotel_stop" style="display:none;background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.5);padding:10px 22px;font-size:14px;font-weight:600;border-radius:6px;cursor:pointer;">⏹ Stop</button>
                        <span id="fth_bulk_hotel_counter" style="font-size:13px;opacity:0.85;"></span>
                    </div>
                    <div id="fth_bulk_import_hotel_status" style="margin-top: 12px; padding: 10px 14px; border-radius: 6px; display: none;"></div>
                    <!-- Live preview log -->
                    <div id="fth_bulk_hotel_live_log" style="display:none;margin-top:16px;background:rgba(0,0,0,0.35);border-radius:10px;padding:14px;max-height:460px;overflow-y:auto;">
                        <div style="font-size:12px;opacity:0.65;margin-bottom:10px;letter-spacing:0.4px;">LIVE IMPORT LOG — hotels</div>
                        <div id="fth_bulk_hotel_live_log_items"></div>
                    </div>
                </div>

                <!-- Delete All Content -->
                <div class="fth-import-panel" style="background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                    <h2 style="margin: 0 0 10px; font-size: 24px;">🗑️ Delete all imported content</h2>
                    <p style="margin: 0 0 20px; opacity: 0.9;">Permanently remove all imported activities and/or hotels (and their media). This cannot be undone. Hub pages and taxonomy terms (cities, countries, categories) are <strong>not</strong> deleted.</p>
                    <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="fth_delete_activities" value="1" checked> Delete all activities
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="fth_delete_hotels" value="1" checked> Delete all hotels
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="fth_delete_destinations" value="1"> Delete destinations
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="fth_delete_terms" value="1"> Delete all cities/countries/categories
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="fth_delete_media" value="1"> Also delete imported images
                        </label>
                    </div>
                    <button type="button" id="fth_delete_all_btn" class="button" style="background:#fff;color:#7f1d1d;border:none;padding:12px 30px;font-size:16px;font-weight:700;border-radius:6px;cursor:pointer;">🗑️ Delete Selected Content</button>
                    <div id="fth_delete_all_status" style="margin-top:14px;padding:10px 14px;border-radius:6px;display:none;"></div>
                </div>
                <script>
                (function() {
                    var btn    = document.getElementById('fth_delete_all_btn');
                    var status = document.getElementById('fth_delete_all_status');
                    var nonce  = '<?php echo wp_create_nonce('fth_import_publish'); ?>';
                    if (!btn) return;
                    btn.addEventListener('click', function() {
                        var doAct   = document.getElementById('fth_delete_activities')  && document.getElementById('fth_delete_activities').checked;
                        var doHtl   = document.getElementById('fth_delete_hotels')       && document.getElementById('fth_delete_hotels').checked;
                        var doDest  = document.getElementById('fth_delete_destinations') && document.getElementById('fth_delete_destinations').checked;
                        var doTerms = document.getElementById('fth_delete_terms')        && document.getElementById('fth_delete_terms').checked;
                        var doMedia = document.getElementById('fth_delete_media')        && document.getElementById('fth_delete_media').checked;
                        if (!doAct && !doHtl && !doDest && !doTerms) {
                            status.style.background = 'rgba(244,67,54,0.3)';
                            status.textContent = 'Veuillez sélectionner au moins un type de contenu à effacer.';
                            status.style.display = '';
                            return;
                        }
                        var parts = [];
                        if (doAct)   parts.push('toutes les activités');
                        if (doHtl)   parts.push('tous les hôtels');
                        if (doDest)  parts.push('toutes les destinations');
                        if (doTerms) parts.push('toutes les villes/pays/catégories');
                        if (doMedia) parts.push('les images importées');
                        if (!confirm('⚠️ SUPPRESSION DÉFINITIVE\n\n' + parts.join(', ') + '\n\nCette action est irréversible. Confirmer ?')) return;
                        btn.disabled = true; btn.textContent = '⏳ Suppression en cours…';
                        status.style.background = 'rgba(255,255,255,0.15)';
                        status.textContent = 'Suppression en cours — veuillez patienter…';
                        status.style.display = '';
                        var fd = new FormData();
                        fd.append('action',       'fth_delete_all_content');
                        fd.append('activities',   doAct   ? '1' : '0');
                        fd.append('hotels',       doHtl   ? '1' : '0');
                        fd.append('destinations', doDest  ? '1' : '0');
                        fd.append('terms',        doTerms ? '1' : '0');
                        fd.append('media',        doMedia ? '1' : '0');
                        fd.append('nonce',        nonce);
                        fetch(ajaxurl, {method:'POST', body:fd, credentials:'same-origin'})
                            .then(function(r){ return r.json(); })
                            .then(function(res) {
                                if (res && res.success) {
                                    status.style.background = 'rgba(76,175,80,0.3)';
                                    status.textContent = '✅ ' + res.data.message;
                                } else {
                                    status.style.background = 'rgba(244,67,54,0.3)';
                                    status.textContent = '❌ ' + (res && res.data && res.data.message ? res.data.message : 'Erreur inconnue');
                                }
                            })
                            .catch(function(e) {
                                status.style.background = 'rgba(244,67,54,0.3)';
                                status.textContent = '❌ Erreur réseau: ' + e.message;
                            })
                            .finally(function() {
                                btn.disabled = false; btn.textContent = '🗑️ Delete Selected Content';
                            });
                    });
                })();
                </script>

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
            

// ── Live Bulk Import — Activities ────────────────────────────────────────────
(function() {
    var fthActStop = false;

    function fthActivityLogItem(item, index, total) {
        var discount = item.discount ? '<span style="background:#f59e0b;color:#000;font-size:10px;font-weight:700;padding:1px 5px;border-radius:4px;margin-left:6px;">-' + item.discount + '</span>' : '';
        var price    = item.price    ? '<span style="font-size:13px;opacity:0.8;margin-left:6px;">' + (item.currency || '') + item.price + '</span>' : '';
        var thumb    = item.thumb    ? '<img src="' + item.thumb + '" style="width:64px;height:48px;object-fit:cover;border-radius:5px;flex-shrink:0;" loading="lazy">' : '<div style="width:64px;height:48px;background:rgba(255,255,255,0.12);border-radius:5px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:20px;">🏖️</div>';
        return '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.08);">'
            + thumb
            + '<div style="flex:1;min-width:0;">'
            +   '<div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + $('<span>').text(item.title).html() + discount + price + '</div>'
            +   '<div style="font-size:11px;opacity:0.6;margin-top:2px;">#' + index + ' of ' + total + '</div>'
            + '</div>'
            + '<div style="display:flex;gap:6px;flex-shrink:0;">'
            +   '<a href="' + item.edit_url + '" style="font-size:11px;color:#93c5fd;text-decoration:none;">Edit</a>'
            +   '<a href="' + item.view_url + '" target="_blank" style="font-size:11px;color:#6ee7b7;text-decoration:none;">View</a>'
            + '</div>'
            + '</div>';
    }

    $('#fth_bulk_import_btn').on('click', function() {
        var url = $('#fth_bulk_city_url').val().trim();
        var $btn = $(this);
        var $stop = $('#fth_bulk_import_stop');
        var $status = $('#fth_bulk_import_status');
        var $counter = $('#fth_bulk_import_counter');
        var $log = $('#fth_bulk_live_log');
        var $logItems = $('#fth_bulk_live_log_items');

        if (!url || url.indexOf('klook.com') === -1) {
            $status.css('background','rgba(244,67,54,0.3)').text('Please enter a valid Klook destination URL').show();
            return;
        }

        fthActStop = false;
        $btn.prop('disabled', true).text('Discovering URLs...');
        $stop.show();
        $status.css('background','rgba(255,255,255,0.2)').text('Step 1/2 — Discovering activity URLs...').show();
        $counter.text('');
        $log.show();
        $logItems.html('<div style="opacity:0.55;font-size:12px;">Scanning Klook pages...</div>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 180000,
            data: {
                action: 'fth_discover_import_urls',
                url: url,
                type: 'activity',
                city: $('#fth_bulk_city_term').val(),
                country: $('#fth_bulk_country').val(),
                category: $('#fth_bulk_category').val(),
                limit: $('#fth_bulk_limit').val(),
                nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
            },
            success: function(res) {
                if (!res.success || !res.data.urls || !res.data.urls.length) {
                    var msg = (res.data && res.data.message) ? res.data.message : 'No URLs found';
                    $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + msg).show();
                    $btn.prop('disabled', false).text('⚡ Import Activities Live');
                    $stop.hide(); return;
                }
                var urls = res.data.urls;
                var skipped = res.data.skipped || 0;
                var total = urls.length;
                $status.css('background','rgba(255,255,255,0.2)').text('Step 2/2 — Importing ' + total + ' activities' + (skipped ? ' (' + skipped + ' already imported skipped)' : '') + '...').show();
                $logItems.empty();
                $btn.text('Importing 0 / ' + total + '...');

                var idx = 0;
                var imported = 0;
                var errors = 0;

                function importNext() {
                    if (fthActStop || idx >= urls.length) {
                        var summary = '✅ Done: ' + imported + ' imported, ' + errors + ' errors' + (skipped ? ', ' + skipped + ' skipped' : '');
                        $status.css('background', imported > 0 ? 'rgba(76,175,80,0.3)' : 'rgba(255,165,0,0.3)').text(summary).show();
                        $counter.text(imported + ' / ' + total + ' done');
                        $btn.prop('disabled', false).text('⚡ Import Activities Live');
                        $stop.hide(); return;
                    }
                    var currentUrl = urls[idx];
                    var currentIdx = idx + 1;
                    idx++;
                    $btn.text('Importing ' + (currentIdx) + ' / ' + total + '...');
                    $counter.text((currentIdx - 1) + ' / ' + total + ' done');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        timeout: 120000,
                        data: {
                            action: 'fth_import_single_live',
                            url: currentUrl,
                            type: 'activity',
                            city: $('#fth_bulk_city_term').val(),
                            country: $('#fth_bulk_country').val(),
                            category: $('#fth_bulk_category').val(),
                            is_featured: $('#fth_bulk_featured').is(':checked') ? 1 : 0,
                            is_bestseller: $('#fth_bulk_popular').is(':checked') ? 1 : 0,
                            nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
                        },
                        success: function(r) {
                            if (r.success && r.data) {
                                imported++;
                                $logItems.prepend(fthActivityLogItem(r.data, currentIdx, total));
                                $log[0].scrollTop = 0;
                            } else {
                                errors++;
                                var errMsg = (r.data && r.data.message) ? r.data.message : 'failed';
                                $logItems.prepend('<div style="padding:5px 0;font-size:11px;opacity:0.55;border-bottom:1px solid rgba(255,255,255,0.06);">❌ #' + currentIdx + ': ' + $('<span>').text(errMsg).html() + '</div>');
                            }
                        },
                        error: function() {
                            errors++;
                            $logItems.prepend('<div style="padding:5px 0;font-size:11px;opacity:0.55;border-bottom:1px solid rgba(255,255,255,0.06);">❌ #' + currentIdx + ': network error</div>');
                        },
                        complete: function() { importNext(); }
                    });
                }
                importNext();
            },
            error: function(xhr) {
                var msg = 'Network error';
                if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) msg = xhr.responseJSON.data.message;
                else if (xhr && xhr.responseText) msg = xhr.responseText.substring(0, 240);
                $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + msg).show();
                $btn.prop('disabled', false).text('⚡ Import Activities Live');
                $stop.hide();
            }
        });
    });

    $('#fth_bulk_import_stop').on('click', function() { fthActStop = true; $(this).hide(); });
})();

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

            // ── Live Bulk Import — Hotels ─────────────────────────────────────────────
            (function() {
                var fthHotelStop = false;

                function fthHotelLogItem(item, index, total) {
                    var discount = item.discount ? '<span style="background:#f59e0b;color:#000;font-size:10px;font-weight:700;padding:1px 5px;border-radius:4px;margin-left:6px;">-' + item.discount + '</span>' : '';
                    var price    = item.price    ? '<span style="font-size:13px;opacity:0.8;margin-left:6px;">' + (item.currency || '') + item.price + '</span>' : '';
                    var thumb    = item.thumb    ? '<img src="' + item.thumb + '" style="width:64px;height:48px;object-fit:cover;border-radius:5px;flex-shrink:0;" loading="lazy">' : '<div style="width:64px;height:48px;background:rgba(255,255,255,0.12);border-radius:5px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:20px;">🏨</div>';
                    return '<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.08);">'
                        + thumb
                        + '<div style="flex:1;min-width:0;">'
                        +   '<div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + $('<span>').text(item.title).html() + discount + price + '</div>'
                        +   '<div style="font-size:11px;opacity:0.6;margin-top:2px;">#' + index + ' of ' + total + '</div>'
                        + '</div>'
                        + '<div style="display:flex;gap:6px;flex-shrink:0;">'
                        +   '<a href="' + item.edit_url + '" style="font-size:11px;color:#93c5fd;text-decoration:none;">Edit</a>'
                        +   '<a href="' + item.view_url + '" target="_blank" style="font-size:11px;color:#6ee7b7;text-decoration:none;">View</a>'
                        + '</div>'
                        + '</div>';
                }

                $('#fth_bulk_import_hotel_btn').on('click', function() {
                    var url = $('#fth_bulk_hotel_url').val().trim();
                    var $btn = $(this);
                    var $stop = $('#fth_bulk_hotel_stop');
                    var $status = $('#fth_bulk_import_hotel_status');
                    var $counter = $('#fth_bulk_hotel_counter');
                    var $log = $('#fth_bulk_hotel_live_log');
                    var $logItems = $('#fth_bulk_hotel_live_log_items');

                    if (!url || url.indexOf('klook.com') === -1) {
                        $status.css('background','rgba(244,67,54,0.3)').text('Please enter a valid Klook hotels URL').show();
                        return;
                    }

                    fthHotelStop = false;
                    $btn.prop('disabled', true).text('Discovering URLs...');
                    $stop.show();
                    $status.css('background','rgba(255,255,255,0.2)').text('Step 1/2 — Discovering hotel URLs...').show();
                    $counter.text('');
                    $log.show();
                    $logItems.html('<div style="opacity:0.55;font-size:12px;">Scanning Klook pages...</div>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        timeout: 180000,
                        data: {
                            action: 'fth_discover_import_urls',
                            url: url,
                            type: 'hotel',
                            city: $('#fth_bulk_hotel_city').val(),
                            country: $('#fth_bulk_hotel_country').val(),
                            limit: $('#fth_bulk_hotel_limit').val(),
                            nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
                        },
                        success: function(res) {
                            if (!res.success || !res.data.urls || !res.data.urls.length) {
                                var msg = (res.data && res.data.message) ? res.data.message : 'No URLs found';
                                $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + msg).show();
                                $btn.prop('disabled', false).text('⚡ Import Hotels Live');
                                $stop.hide(); return;
                            }
                            var urls = res.data.urls;
                            var skipped = res.data.skipped || 0;
                            var total = urls.length;
                            $status.css('background','rgba(255,255,255,0.2)').text('Step 2/2 — Importing ' + total + ' hotels' + (skipped ? ' (' + skipped + ' already imported skipped)' : '') + '...').show();
                            $logItems.empty();
                            $btn.text('Importing 0 / ' + total + '...');

                            var idx = 0;
                            var imported = 0;
                            var errors = 0;

                            function importNext() {
                                if (fthHotelStop || idx >= urls.length) {
                                    var summary = '✅ Done: ' + imported + ' imported, ' + errors + ' errors' + (skipped ? ', ' + skipped + ' skipped' : '');
                                    $status.css('background', imported > 0 ? 'rgba(76,175,80,0.3)' : 'rgba(255,165,0,0.3)').text(summary).show();
                                    $counter.text(imported + ' / ' + total + ' done');
                                    $btn.prop('disabled', false).text('⚡ Import Hotels Live');
                                    $stop.hide(); return;
                                }
                                var currentUrl = urls[idx];
                                var currentIdx = idx + 1;
                                idx++;
                                $btn.text('Importing ' + currentIdx + ' / ' + total + '...');
                                $counter.text((currentIdx - 1) + ' / ' + total + ' done');

                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    timeout: 120000,
                                    data: {
                                        action: 'fth_import_single_live',
                                        url: currentUrl,
                                        type: 'hotel',
                                        city: $('#fth_bulk_hotel_city').val(),
                                        country: $('#fth_bulk_hotel_country').val(),
                                        is_featured: $('#fth_bulk_hotel_featured').is(':checked') ? 1 : 0,
                                        is_bestseller: $('#fth_bulk_hotel_popular').is(':checked') ? 1 : 0,
                                        nonce: '<?php echo wp_create_nonce('fth_import_publish'); ?>'
                                    },
                                    success: function(r) {
                                        if (r.success && r.data) {
                                            imported++;
                                            $logItems.prepend(fthHotelLogItem(r.data, currentIdx, total));
                                            $log[0].scrollTop = 0;
                                        } else {
                                            errors++;
                                            var errMsg = (r.data && r.data.message) ? r.data.message : 'failed';
                                            $logItems.prepend('<div style="padding:5px 0;font-size:11px;opacity:0.55;border-bottom:1px solid rgba(255,255,255,0.06);">❌ #' + currentIdx + ': ' + $('<span>').text(errMsg).html() + '</div>');
                                        }
                                    },
                                    error: function() {
                                        errors++;
                                        $logItems.prepend('<div style="padding:5px 0;font-size:11px;opacity:0.55;border-bottom:1px solid rgba(255,255,255,0.06);">❌ #' + currentIdx + ': network error</div>');
                                    },
                                    complete: function() { importNext(); }
                                });
                            }
                            importNext();
                        },
                        error: function(xhr) {
                            var msg = 'Network error';
                            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) msg = xhr.responseJSON.data.message;
                            else if (xhr && xhr.responseText) msg = xhr.responseText.substring(0, 240);
                            $status.css('background','rgba(244,67,54,0.3)').text('❌ ' + msg).show();
                            $btn.prop('disabled', false).text('⚡ Import Hotels Live');
                            $stop.hide();
                        }
                    });
                });

                $('#fth_bulk_hotel_stop').on('click', function() { fthHotelStop = true; $(this).hide(); });
            })();

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

        // Delete button handled by inline vanilla-JS block injected next to the button HTML.

// ── Marathon Import ──────────────────────────────────────────────────
var fthMarathonNonce = '<?php echo wp_create_nonce('fth_import_publish'); ?>';
var fthMarathonStop  = false;

// Fix: DOMContentLoaded may have ALREADY fired before this inline script runs
// (WordPress enqueues scripts at bottom of body). Always run immediately if DOM is ready.
function fthInitMarathon() {
    var jq = window.jQuery;
    if (!jq) { return; }

    // Auto-prefill from Klook Links Library query params
    (function() {
        try {
            var p = new URLSearchParams(window.location.search);
            var u = p.get('fthll_prefill_url');
            if (!u) return;
            jq('#fth_marathon_url').val(u);
            var tp = p.get('fthll_prefill_type') || 'activity';
            if (jq('#fth_marathon_type option[value="'+tp+'"]').length) jq('#fth_marathon_type').val(tp);
            var city = p.get('fthll_prefill_city') || '';
            var country = p.get('fthll_prefill_country') || '';
            if (city)    jq('#fth_marathon_city option').filter(function(){ return jq(this).val().toLowerCase() === city.toLowerCase(); }).prop('selected', true);
            if (country) jq('#fth_marathon_country option').filter(function(){ return jq(this).val().toLowerCase() === country.toLowerCase(); }).prop('selected', true);
            var panel = document.getElementById('fth_marathon_url');
            if (panel) panel.scrollIntoView({behavior:'smooth', block:'center'});
        } catch(e) {}
    })();

    // Direct DOM event binding (no jQuery .on(), avoids any delegation issues)
    var startBtn = document.getElementById('fth_marathon_btn');
    var stopBtn  = document.getElementById('fth_marathon_stop');
    if (!startBtn) return; // panel not rendered

    stopBtn && stopBtn.addEventListener('click', function() {
        fthMarathonStop = true;
        stopBtn.style.display = 'none';
    });

    startBtn.addEventListener('click', function() {
        var url = (document.getElementById('fth_marathon_url') || {}).value || '';
        url = url.trim();
        if (!url) { alert('Veuillez entrer une URL de destination Klook'); return; }

        fthMarathonStop = false;
        var type    = (document.getElementById('fth_marathon_type')    || {}).value || 'activity';
        var city    = (document.getElementById('fth_marathon_city')    || {}).value || '';
        var country = (document.getElementById('fth_marathon_country') || {}).value || '';

        var $status   = jq('#fth_marathon_status');
        var $counter  = jq('#fth_marathon_counter');
        var $log      = jq('#fth_marathon_log');
        var $logItems = jq('#fth_marathon_log_items');

        startBtn.disabled = true;
        startBtn.textContent = '⏳ Running...';
        if (stopBtn) stopBtn.style.display = '';
        $log.show(); $logItems.empty();
        $status.css('background','rgba(255,255,255,0.15)').text('🔍 Discovering URLs…').show();
        $counter.text('');

        var types         = (type === 'both') ? ['activity', 'hotel'] : [type];
        var typeIdx       = 0;
        var totalImported = 0;
        var totalErrors   = 0;
        var BATCH         = 5;

        function mLog(msg) {
            $logItems.prepend('<div style="padding:2px 0;border-bottom:1px solid rgba(255,255,255,0.06);">' + jq('<span>').text(msg).html() + '</div>');
        }

        function runType(ct) {
            if (fthMarathonStop) { finish(); return; }
            $status.text('🔍 Discovering ' + ct + ' URLs from ' + url + '…');
            jq.ajax({
                url: ajaxurl, type: 'POST', timeout: 180000,
                data: { action: 'fth_discover_import_urls', url: url, type: ct, city: city, country: country, category: '', limit: 200, nonce: fthMarathonNonce },
                success: function(res) {
                    if (!res || !res.success || !res.data || !res.data.urls || !res.data.urls.length) {
                        mLog('⚠️ No ' + ct + ' URLs found' + (res && res.data && res.data.message ? ': ' + res.data.message : ''));
                        nextType(); return;
                    }
                    var urls = res.data.urls;
                    mLog('✅ Found ' + urls.length + ' ' + ct + ' URL(s)');
                    $status.text('Importing ' + urls.length + ' ' + ct + 's in batches of ' + BATCH + '…');
                    importBatch(ct, urls, 0, urls.length, 0, 0);
                },
                error: function(xhr) {
                    mLog('❌ Discover failed: ' + (xhr.responseText ? xhr.responseText.substring(0,120) : 'network error'));
                    nextType();
                }
            });
        }

        function importBatch(ct, urls, idx, total, imported, errors) {
            if (fthMarathonStop || idx >= total) {
                mLog('──── ' + ct + 's done: ' + imported + ' imported, ' + errors + ' errors ────');
                totalImported += imported; totalErrors += errors;
                nextType(); return;
            }
            var batch = urls.slice(idx, idx + BATCH);
            var done = 0, bImp = 0, bErr = 0;
            $counter.text((idx) + ' / ' + total + ' ' + ct + 's');
            batch.forEach(function(burl) {
                jq.ajax({
                    url: ajaxurl, type: 'POST', timeout: 120000,
                    data: { action: 'fth_import_single_live', url: burl, type: ct, city: city, country: country, category: '', nonce: fthMarathonNonce },
                    success: function(r) {
                        if (r && r.success) { bImp++; mLog('✅ ' + (r.data && r.data.title ? r.data.title : burl)); }
                        else { bErr++; mLog('❌ ' + burl + ': ' + (r && r.data && r.data.message ? r.data.message : 'failed')); }
                    },
                    error: function() { bErr++; mLog('❌ network: ' + burl); },
                    complete: function() {
                        done++;
                        $counter.text((idx + done) + ' / ' + total + ' ' + ct + 's');
                        if (done === batch.length) importBatch(ct, urls, idx + BATCH, total, imported + bImp, errors + bErr);
                    }
                });
            });
        }

        function nextType() {
            typeIdx++;
            if (!fthMarathonStop && typeIdx < types.length) { runType(types[typeIdx]); } else { finish(); }
        }

        function finish() {
            startBtn.disabled = false;
            startBtn.textContent = '🚀 Start Marathon';
            if (stopBtn) stopBtn.style.display = 'none';
            $status.css('background', totalImported > 0 ? 'rgba(76,175,80,0.3)' : 'rgba(255,165,0,0.3)')
                   .text('✅ Done: ' + totalImported + ' imported, ' + totalErrors + ' errors').show();
            $counter.text(totalImported + ' total imported');
        }

        runType(types[0]);
    });
} // end fthInitMarathon
// Run now if DOM already ready, otherwise wait
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fthInitMarathon);
} else {
    fthInitMarathon();
}
        </script>
        <?php
    }

    // ─────────────────────────────────────────────────────────────
    // KLOOK LINKS LIBRARY
    // ─────────────────────────────────────────────────────────────
    public static function klook_links_page() {
        // ── Comprehensive city database ──────────────────────────
        // Format: 'Region' => array( array('City Name', 'Country', 'activities-slug', 'hotels-slug') )
        // Slugs match Klook URL patterns:
        //   Activities: https://www.klook.com/en-US/things-to-do/{activities-slug}/
        //   Hotels    : https://www.klook.com/en-US/hotels/{hotels-slug}/
        $klook_cities = array(

            'Middle East' => array(
                array('Dubai',          'UAE',          'destination/c78-dubai/1-things-to-do',       'destination/c78-dubai/3-hotel'),
                array('Abu Dhabi',      'UAE',          'destination/c79-abu-dhabi/1-things-to-do',   'destination/c79-abu-dhabi/3-hotel'),
                array('Sharjah',        'UAE',          'sharjah-things-to-do',             'sharjah-hotel'),
                array('Doha',           'Qatar',        'destination/c80-doha/1-things-to-do',        'destination/c80-doha/3-hotel'),
                array('Riyadh',         'Saudi Arabia', 'riyadh-things-to-do',              'riyadh-hotel'),
                array('Jeddah',         'Saudi Arabia', 'jeddah-things-to-do',              'jeddah-hotel'),
                array('Mecca',          'Saudi Arabia', 'mecca-things-to-do',               'mecca-hotel'),
                array('Medina',         'Saudi Arabia', 'medina-things-to-do',              'medina-hotel'),
                array('Kuwait City',    'Kuwait',       'kuwait-city-things-to-do',         'kuwait-city-hotel'),
                array('Muscat',         'Oman',         'muscat-things-to-do',              'muscat-hotel'),
                array('Salalah',        'Oman',         'salalah-things-to-do',             'salalah-hotel'),
                array('Manama',         'Bahrain',      'manama-things-to-do',              'manama-hotel'),
                array('Amman',          'Jordan',       'amman-things-to-do',               'amman-hotel'),
                array('Petra',          'Jordan',       'petra-things-to-do',               'petra-hotel'),
                array('Beirut',         'Lebanon',      'beirut-things-to-do',              'beirut-hotel'),
                array('Tel Aviv',       'Israel',       'tel-aviv-things-to-do',            'tel-aviv-hotel'),
                array('Jerusalem',      'Israel',       'jerusalem-things-to-do',           'jerusalem-hotel'),
                array('Istanbul',       'Turkey',       'istanbul-things-to-do',            'istanbul-hotel'),
                array('Ankara',         'Turkey',       'ankara-things-to-do',              'ankara-hotel'),
                array('Antalya',        'Turkey',       'antalya-things-to-do',             'antalya-hotel'),
                array('Cappadocia',     'Turkey',       'cappadocia-things-to-do',          'cappadocia-hotel'),
                array('Bodrum',         'Turkey',       'bodrum-things-to-do',              'bodrum-hotel'),
                array('Tbilisi',        'Georgia',      'tbilisi-things-to-do',             'tbilisi-hotel'),
                array('Batumi',         'Georgia',      'batumi-things-to-do',              'batumi-hotel'),
                array('Yerevan',        'Armenia',      'yerevan-things-to-do',             'yerevan-hotel'),
                array('Baku',           'Azerbaijan',   'baku-things-to-do',                'baku-hotel'),
            ),

            'Africa — North' => array(
                array('Cairo',          'Egypt',        'cairo-things-to-do',               'cairo-hotel'),
                array('Alexandria',     'Egypt',        'alexandria-things-to-do',          'alexandria-hotel'),
                array('Luxor',          'Egypt',        'luxor-things-to-do',               'luxor-hotel'),
                array('Hurghada',       'Egypt',        'hurghada-things-to-do',            'hurghada-hotel'),
                array('Sharm el-Sheikh','Egypt',        'sharm-el-sheikh-things-to-do',     'sharm-el-sheikh-hotel'),
                array('Marrakech',      'Morocco',      'marrakech-things-to-do',           'marrakech-hotel'),
                array('Casablanca',     'Morocco',      'casablanca-things-to-do',          'casablanca-hotel'),
                array('Fez',            'Morocco',      'fez-things-to-do',                 'fez-hotel'),
                array('Tangier',        'Morocco',      'tangier-things-to-do',             'tangier-hotel'),
                array('Agadir',         'Morocco',      'agadir-things-to-do',              'agadir-hotel'),
                array('Chefchaouen',    'Morocco',      'chefchaouen-things-to-do',         'chefchaouen-hotel'),
                array('Essaouira',      'Morocco',      'essaouira-things-to-do',           'essaouira-hotel'),
                array('Rabat',          'Morocco',      'rabat-things-to-do',               'rabat-hotel'),
                array('Meknes',         'Morocco',      'meknes-things-to-do',              'meknes-hotel'),
                array('Tunis',          'Tunisia',      'tunis-things-to-do',               'tunis-hotel'),
                array('Djerba',         'Tunisia',      'djerba-things-to-do',              'djerba-hotel'),
                array('Hammamet',       'Tunisia',      'hammamet-things-to-do',            'hammamet-hotel'),
                array('Algiers',        'Algeria',      'algiers-things-to-do',             'algiers-hotel'),
                array('Tripoli',        'Libya',        'tripoli-things-to-do',             'tripoli-hotel'),
            ),

            'Africa — Sub-Saharan' => array(
                array('Cape Town',      'South Africa', 'cape-town-things-to-do',           'cape-town-hotel'),
                array('Johannesburg',   'South Africa', 'johannesburg-things-to-do',        'johannesburg-hotel'),
                array('Durban',         'South Africa', 'durban-things-to-do',              'durban-hotel'),
                array('Pretoria',       'South Africa', 'pretoria-things-to-do',            'pretoria-hotel'),
                array('Nairobi',        'Kenya',        'nairobi-things-to-do',             'nairobi-hotel'),
                array('Mombasa',        'Kenya',        'mombasa-things-to-do',             'mombasa-hotel'),
                array('Zanzibar',       'Tanzania',     'zanzibar-things-to-do',            'zanzibar-hotel'),
                array('Dar es Salaam',  'Tanzania',     'dar-es-salaam-things-to-do',       'dar-es-salaam-hotel'),
                array('Accra',          'Ghana',        'accra-things-to-do',               'accra-hotel'),
                array('Lagos',          'Nigeria',      'lagos-things-to-do',               'lagos-hotel'),
                array('Abuja',          'Nigeria',      'abuja-things-to-do',               'abuja-hotel'),
                array('Addis Ababa',    'Ethiopia',     'addis-ababa-things-to-do',         'addis-ababa-hotel'),
                array('Kigali',         'Rwanda',       'kigali-things-to-do',              'kigali-hotel'),
                array('Kampala',        'Uganda',       'kampala-things-to-do',             'kampala-hotel'),
                array('Dakar',          'Senegal',      'dakar-things-to-do',               'dakar-hotel'),
                array('Antananarivo',   'Madagascar',   'antananarivo-things-to-do',        'antananarivo-hotel'),
                array('Mauritius',      'Mauritius',    'mauritius-things-to-do',           'mauritius-hotel'),
                array('Seychelles',     'Seychelles',   'seychelles-things-to-do',          'seychelles-hotel'),
            ),

            'Europe — West' => array(
                array('Paris',          'France',       'paris-things-to-do',               'paris-hotel'),
                array('Nice',           'France',       'nice-things-to-do',                'nice-hotel'),
                array('Lyon',           'France',       'lyon-things-to-do',                'lyon-hotel'),
                array('Marseille',      'France',       'marseille-things-to-do',           'marseille-hotel'),
                array('Bordeaux',       'France',       'bordeaux-things-to-do',            'bordeaux-hotel'),
                array('Strasbourg',     'France',       'strasbourg-things-to-do',          'strasbourg-hotel'),
                array('London',         'UK',           'london-things-to-do',              'london-hotel'),
                array('Edinburgh',      'UK',           'edinburgh-things-to-do',           'edinburgh-hotel'),
                array('Manchester',     'UK',           'manchester-things-to-do',          'manchester-hotel'),
                array('Dublin',         'Ireland',      'dublin-things-to-do',              'dublin-hotel'),
                array('Amsterdam',      'Netherlands',  'amsterdam-things-to-do',           'amsterdam-hotel'),
                array('Brussels',       'Belgium',      'brussels-things-to-do',            'brussels-hotel'),
                array('Bruges',         'Belgium',      'bruges-things-to-do',              'bruges-hotel'),
                array('Lisbon',         'Portugal',     'lisbon-things-to-do',              'lisbon-hotel'),
                array('Porto',          'Portugal',     'porto-things-to-do',               'porto-hotel'),
                array('Algarve',        'Portugal',     'algarve-things-to-do',             'algarve-hotel'),
                array('Madrid',         'Spain',        'madrid-things-to-do',              'madrid-hotel'),
                array('Barcelona',      'Spain',        'barcelona-things-to-do',           'barcelona-hotel'),
                array('Seville',        'Spain',        'seville-things-to-do',             'seville-hotel'),
                array('Valencia',       'Spain',        'valencia-things-to-do',            'valencia-hotel'),
                array('Malaga',         'Spain',        'malaga-things-to-do',              'malaga-hotel'),
                array('Ibiza',          'Spain',        'ibiza-things-to-do',               'ibiza-hotel'),
                array('Tenerife',       'Spain',        'tenerife-things-to-do',            'tenerife-hotel'),
                array('Zurich',         'Switzerland',  'zurich-things-to-do',              'zurich-hotel'),
                array('Geneva',         'Switzerland',  'geneva-things-to-do',              'geneva-hotel'),
                array('Interlaken',     'Switzerland',  'interlaken-things-to-do',          'interlaken-hotel'),
                array('Bern',           'Switzerland',  'bern-things-to-do',                'bern-hotel'),
            ),

            'Europe — Central & East' => array(
                array('Berlin',         'Germany',      'berlin-things-to-do',              'berlin-hotel'),
                array('Munich',         'Germany',      'munich-things-to-do',              'munich-hotel'),
                array('Hamburg',        'Germany',      'hamburg-things-to-do',             'hamburg-hotel'),
                array('Frankfurt',      'Germany',      'frankfurt-things-to-do',           'frankfurt-hotel'),
                array('Cologne',        'Germany',      'cologne-things-to-do',             'cologne-hotel'),
                array('Vienna',         'Austria',      'vienna-things-to-do',              'vienna-hotel'),
                array('Salzburg',       'Austria',      'salzburg-things-to-do',            'salzburg-hotel'),
                array('Prague',         'Czech Rep.',   'prague-things-to-do',              'prague-hotel'),
                array('Budapest',       'Hungary',      'budapest-things-to-do',            'budapest-hotel'),
                array('Warsaw',         'Poland',       'warsaw-things-to-do',              'warsaw-hotel'),
                array('Krakow',         'Poland',       'krakow-things-to-do',              'krakow-hotel'),
                array('Gdansk',         'Poland',       'gdansk-things-to-do',              'gdansk-hotel'),
                array('Bratislava',     'Slovakia',     'bratislava-things-to-do',          'bratislava-hotel'),
                array('Ljubljana',      'Slovenia',     'ljubljana-things-to-do',           'ljubljana-hotel'),
                array('Zagreb',         'Croatia',      'zagreb-things-to-do',              'zagreb-hotel'),
                array('Dubrovnik',      'Croatia',      'dubrovnik-things-to-do',           'dubrovnik-hotel'),
                array('Split',          'Croatia',      'split-things-to-do',               'split-hotel'),
                array('Sarajevo',       'Bosnia',       'sarajevo-things-to-do',            'sarajevo-hotel'),
                array('Belgrade',       'Serbia',       'belgrade-things-to-do',            'belgrade-hotel'),
                array('Bucharest',      'Romania',      'bucharest-things-to-do',           'bucharest-hotel'),
                array('Sofia',          'Bulgaria',     'sofia-things-to-do',               'sofia-hotel'),
                array('Kyiv',           'Ukraine',      'kyiv-things-to-do',                'kyiv-hotel'),
                array('Moscow',         'Russia',       'moscow-things-to-do',              'moscow-hotel'),
                array('St. Petersburg', 'Russia',       'saint-petersburg-things-to-do',    'saint-petersburg-hotel'),
            ),

            'Europe — North & South' => array(
                array('Copenhagen',     'Denmark',      'copenhagen-things-to-do',          'copenhagen-hotel'),
                array('Stockholm',      'Sweden',       'stockholm-things-to-do',           'stockholm-hotel'),
                array('Oslo',           'Norway',       'oslo-things-to-do',                'oslo-hotel'),
                array('Bergen',         'Norway',       'bergen-things-to-do',              'bergen-hotel'),
                array('Helsinki',       'Finland',      'helsinki-things-to-do',            'helsinki-hotel'),
                array('Tallinn',        'Estonia',      'tallinn-things-to-do',             'tallinn-hotel'),
                array('Riga',           'Latvia',       'riga-things-to-do',                'riga-hotel'),
                array('Vilnius',        'Lithuania',    'vilnius-things-to-do',             'vilnius-hotel'),
                array('Reykjavik',      'Iceland',      'reykjavik-things-to-do',           'reykjavik-hotel'),
                array('Athens',         'Greece',       'athens-things-to-do',              'athens-hotel'),
                array('Santorini',      'Greece',       'santorini-things-to-do',           'santorini-hotel'),
                array('Mykonos',        'Greece',       'mykonos-things-to-do',             'mykonos-hotel'),
                array('Thessaloniki',   'Greece',       'thessaloniki-things-to-do',        'thessaloniki-hotel'),
                array('Rome',           'Italy',        'rome-things-to-do',                'rome-hotel'),
                array('Milan',          'Italy',        'milan-things-to-do',               'milan-hotel'),
                array('Florence',       'Italy',        'florence-things-to-do',            'florence-hotel'),
                array('Venice',         'Italy',        'venice-things-to-do',              'venice-hotel'),
                array('Naples',         'Italy',        'naples-things-to-do',              'naples-hotel'),
                array('Amalfi Coast',   'Italy',        'amalfi-coast-things-to-do',        'amalfi-coast-hotel'),
                array('Sicily',         'Italy',        'sicily-things-to-do',              'sicily-hotel'),
            ),

            'Asia — East' => array(
                array('Tokyo',          'Japan',        'tokyo-things-to-do',               'tokyo-hotel'),
                array('Kyoto',          'Japan',        'kyoto-things-to-do',               'kyoto-hotel'),
                array('Osaka',          'Japan',        'osaka-things-to-do',               'osaka-hotel'),
                array('Hiroshima',      'Japan',        'hiroshima-things-to-do',           'hiroshima-hotel'),
                array('Nara',           'Japan',        'nara-things-to-do',                'nara-hotel'),
                array('Hokkaido',       'Japan',        'hokkaido-things-to-do',            'hokkaido-hotel'),
                array('Okinawa',        'Japan',        'okinawa-things-to-do',             'okinawa-hotel'),
                array('Seoul',          'South Korea',  'seoul-things-to-do',               'seoul-hotel'),
                array('Busan',          'South Korea',  'busan-things-to-do',               'busan-hotel'),
                array('Jeju',           'South Korea',  'jeju-things-to-do',                'jeju-hotel'),
                array('Beijing',        'China',        'beijing-things-to-do',             'beijing-hotel'),
                array('Shanghai',       'China',        'shanghai-things-to-do',            'shanghai-hotel'),
                array('Guangzhou',      'China',        'guangzhou-things-to-do',           'guangzhou-hotel'),
                array('Shenzhen',       'China',        'shenzhen-things-to-do',            'shenzhen-hotel'),
                array('Chengdu',        'China',        'chengdu-things-to-do',             'chengdu-hotel'),
                array("Xi'an",          'China',        'xian-things-to-do',                'xian-hotel'),
                array('Guilin',         'China',        'guilin-things-to-do',              'guilin-hotel'),
                array('Zhangjiajie',    'China',        'zhangjiajie-things-to-do',         'zhangjiajie-hotel'),
                array('Chongqing',      'China',        'chongqing-things-to-do',           'chongqing-hotel'),
                array('Sanya',          'China',        'sanya-things-to-do',               'sanya-hotel'),
                array('Hong Kong',      'Hong Kong',    'hong-kong-things-to-do',           'hong-kong-hotel'),
                array('Macau',          'Macau',        'macau-things-to-do',               'macau-hotel'),
                array('Taipei',         'Taiwan',       'taipei-things-to-do',              'taipei-hotel'),
                array('Taichung',       'Taiwan',       'taichung-things-to-do',            'taichung-hotel'),
                array('Tainan',         'Taiwan',       'tainan-things-to-do',              'tainan-hotel'),
            ),

            'Asia — Southeast' => array(
                array('Singapore',      'Singapore',    'singapore-things-to-do',           'singapore-hotel'),
                array('Bangkok',        'Thailand',     'bangkok-things-to-do',             'bangkok-hotel'),
                array('Phuket',         'Thailand',     'phuket-things-to-do',              'phuket-hotel'),
                array('Chiang Mai',     'Thailand',     'chiang-mai-things-to-do',          'chiang-mai-hotel'),
                array('Krabi',          'Thailand',     'krabi-things-to-do',               'krabi-hotel'),
                array('Koh Samui',      'Thailand',     'koh-samui-things-to-do',           'koh-samui-hotel'),
                array('Pattaya',        'Thailand',     'pattaya-things-to-do',             'pattaya-hotel'),
                array('Hua Hin',        'Thailand',     'hua-hin-things-to-do',             'hua-hin-hotel'),
                array('Bali',           'Indonesia',    'bali-things-to-do',                'bali-hotel'),
                array('Jakarta',        'Indonesia',    'jakarta-things-to-do',             'jakarta-hotel'),
                array('Yogyakarta',     'Indonesia',    'yogyakarta-things-to-do',          'yogyakarta-hotel'),
                array('Lombok',         'Indonesia',    'lombok-things-to-do',              'lombok-hotel'),
                array('Komodo',         'Indonesia',    'komodo-things-to-do',              'komodo-hotel'),
                array('Kuala Lumpur',   'Malaysia',     'kuala-lumpur-things-to-do',        'kuala-lumpur-hotel'),
                array('Penang',         'Malaysia',     'penang-things-to-do',              'penang-hotel'),
                array('Langkawi',       'Malaysia',     'langkawi-things-to-do',            'langkawi-hotel'),
                array('Kota Kinabalu',  'Malaysia',     'kota-kinabalu-things-to-do',       'kota-kinabalu-hotel'),
                array('Hanoi',          'Vietnam',      'hanoi-things-to-do',               'hanoi-hotel'),
                array('Ho Chi Minh',    'Vietnam',      'ho-chi-minh-city-things-to-do',    'ho-chi-minh-city-hotel'),
                array('Da Nang',        'Vietnam',      'da-nang-things-to-do',             'da-nang-hotel'),
                array('Hoi An',         'Vietnam',      'hoi-an-things-to-do',              'hoi-an-hotel'),
                array('Ha Long Bay',    'Vietnam',      'ha-long-bay-things-to-do',         'ha-long-bay-hotel'),
                array('Manila',         'Philippines',  'manila-things-to-do',              'manila-hotel'),
                array('Boracay',        'Philippines',  'boracay-things-to-do',             'boracay-hotel'),
                array('Cebu',           'Philippines',  'cebu-things-to-do',                'cebu-hotel'),
                array('Palawan',        'Philippines',  'palawan-things-to-do',             'palawan-hotel'),
                array('Phnom Penh',     'Cambodia',     'phnom-penh-things-to-do',          'phnom-penh-hotel'),
                array('Siem Reap',      'Cambodia',     'siem-reap-things-to-do',           'siem-reap-hotel'),
                array('Vientiane',      'Laos',         'vientiane-things-to-do',           'vientiane-hotel'),
                array('Luang Prabang',  'Laos',         'luang-prabang-things-to-do',       'luang-prabang-hotel'),
                array('Yangon',         'Myanmar',      'yangon-things-to-do',              'yangon-hotel'),
                array('Brunei',         'Brunei',       'brunei-things-to-do',              'brunei-hotel'),
            ),

            'Asia — South' => array(
                array('Mumbai',         'India',        'mumbai-things-to-do',              'mumbai-hotel'),
                array('Delhi',          'India',        'delhi-things-to-do',               'delhi-hotel'),
                array('Goa',            'India',        'goa-things-to-do',                 'goa-hotel'),
                array('Jaipur',         'India',        'jaipur-things-to-do',              'jaipur-hotel'),
                array('Agra',           'India',        'agra-things-to-do',                'agra-hotel'),
                array('Varanasi',       'India',        'varanasi-things-to-do',            'varanasi-hotel'),
                array('Kerala',         'India',        'kerala-things-to-do',              'kerala-hotel'),
                array('Chennai',        'India',        'chennai-things-to-do',             'chennai-hotel'),
                array('Kolkata',        'India',        'kolkata-things-to-do',             'kolkata-hotel'),
                array('Hyderabad',      'India',        'hyderabad-things-to-do',           'hyderabad-hotel'),
                array('Bangalore',      'India',        'bangalore-things-to-do',           'bangalore-hotel'),
                array('Maldives',       'Maldives',     'maldives-things-to-do',            'maldives-hotel'),
                array('Colombo',        'Sri Lanka',    'colombo-things-to-do',             'colombo-hotel'),
                array('Sigiriya',       'Sri Lanka',    'sigiriya-things-to-do',            'sigiriya-hotel'),
                array('Kathmandu',      'Nepal',        'kathmandu-things-to-do',           'kathmandu-hotel'),
                array('Pokhara',        'Nepal',        'pokhara-things-to-do',             'pokhara-hotel'),
                array('Dhaka',          'Bangladesh',   'dhaka-things-to-do',               'dhaka-hotel'),
                array('Karachi',        'Pakistan',     'karachi-things-to-do',             'karachi-hotel'),
                array('Lahore',         'Pakistan',     'lahore-things-to-do',              'lahore-hotel'),
                array('Islamabad',      'Pakistan',     'islamabad-things-to-do',           'islamabad-hotel'),
            ),

            'Americas — North' => array(
                array('New York',       'USA',          'new-york-things-to-do',            'new-york-hotel'),
                array('Los Angeles',    'USA',          'los-angeles-things-to-do',         'los-angeles-hotel'),
                array('Miami',          'USA',          'miami-things-to-do',               'miami-hotel'),
                array('Las Vegas',      'USA',          'las-vegas-things-to-do',           'las-vegas-hotel'),
                array('San Francisco',  'USA',          'san-francisco-things-to-do',       'san-francisco-hotel'),
                array('Chicago',        'USA',          'chicago-things-to-do',             'chicago-hotel'),
                array('Orlando',        'USA',          'orlando-things-to-do',             'orlando-hotel'),
                array('Hawaii',         'USA',          'hawaii-things-to-do',              'hawaii-hotel'),
                array('Washington DC',  'USA',          'washington-dc-things-to-do',       'washington-dc-hotel'),
                array('Boston',         'USA',          'boston-things-to-do',              'boston-hotel'),
                array('Seattle',        'USA',          'seattle-things-to-do',             'seattle-hotel'),
                array('Nashville',      'USA',          'nashville-things-to-do',           'nashville-hotel'),
                array('New Orleans',    'USA',          'new-orleans-things-to-do',         'new-orleans-hotel'),
                array('San Diego',      'USA',          'san-diego-things-to-do',           'san-diego-hotel'),
                array('Denver',         'USA',          'denver-things-to-do',              'denver-hotel'),
                array('Phoenix',        'USA',          'phoenix-things-to-do',             'phoenix-hotel'),
                array('Toronto',        'Canada',       'toronto-things-to-do',             'toronto-hotel'),
                array('Vancouver',      'Canada',       'vancouver-things-to-do',           'vancouver-hotel'),
                array('Montreal',       'Canada',       'montreal-things-to-do',            'montreal-hotel'),
                array('Quebec City',    'Canada',       'quebec-city-things-to-do',         'quebec-city-hotel'),
                array('Calgary',        'Canada',       'calgary-things-to-do',             'calgary-hotel'),
                array('Mexico City',    'Mexico',       'mexico-city-things-to-do',         'mexico-city-hotel'),
                array('Cancun',         'Mexico',       'cancun-things-to-do',              'cancun-hotel'),
                array('Playa del Carmen','Mexico',      'playa-del-carmen-things-to-do',    'playa-del-carmen-hotel'),
                array('Tulum',          'Mexico',       'tulum-things-to-do',               'tulum-hotel'),
                array('Los Cabos',      'Mexico',       'los-cabos-things-to-do',           'los-cabos-hotel'),
                array('Guadalajara',    'Mexico',       'guadalajara-things-to-do',         'guadalajara-hotel'),
            ),

            'Americas — Central & Caribbean' => array(
                array('Panama City',    'Panama',       'panama-city-things-to-do',         'panama-city-hotel'),
                array('San José',       'Costa Rica',   'san-jose-things-to-do',            'san-jose-hotel'),
                array('Havana',         'Cuba',         'havana-things-to-do',              'havana-hotel'),
                array('Santo Domingo',  'Dom. Rep.',    'santo-domingo-things-to-do',       'santo-domingo-hotel'),
                array('Punta Cana',     'Dom. Rep.',    'punta-cana-things-to-do',          'punta-cana-hotel'),
                array('Nassau',         'Bahamas',      'nassau-things-to-do',              'nassau-hotel'),
                array('Kingston',       'Jamaica',      'kingston-things-to-do',            'kingston-hotel'),
                array('Bridgetown',     'Barbados',     'bridgetown-things-to-do',          'bridgetown-hotel'),
            ),

            'Americas — South' => array(
                array('Rio de Janeiro', 'Brazil',       'rio-de-janeiro-things-to-do',      'rio-de-janeiro-hotel'),
                array('São Paulo',      'Brazil',       'sao-paulo-things-to-do',           'sao-paulo-hotel'),
                array('Salvador',       'Brazil',       'salvador-things-to-do',            'salvador-hotel'),
                array('Florianopolis',  'Brazil',       'florianopolis-things-to-do',       'florianopolis-hotel'),
                array('Buenos Aires',   'Argentina',    'buenos-aires-things-to-do',        'buenos-aires-hotel'),
                array('Bariloche',      'Argentina',    'bariloche-things-to-do',           'bariloche-hotel'),
                array('Santiago',       'Chile',        'santiago-things-to-do',            'santiago-hotel'),
                array('Lima',           'Peru',         'lima-things-to-do',                'lima-hotel'),
                array('Cusco',          'Peru',         'cusco-things-to-do',               'cusco-hotel'),
                array('Machu Picchu',   'Peru',         'machu-picchu-things-to-do',        'machu-picchu-hotel'),
                array('Bogota',         'Colombia',     'bogota-things-to-do',              'bogota-hotel'),
                array('Cartagena',      'Colombia',     'cartagena-things-to-do',           'cartagena-hotel'),
                array('Quito',          'Ecuador',      'quito-things-to-do',               'quito-hotel'),
                array('La Paz',         'Bolivia',      'la-paz-things-to-do',              'la-paz-hotel'),
                array('Montevideo',     'Uruguay',      'montevideo-things-to-do',          'montevideo-hotel'),
                array('Caracas',        'Venezuela',    'caracas-things-to-do',             'caracas-hotel'),
            ),

            'Oceania' => array(
                array('Sydney',         'Australia',    'sydney-things-to-do',              'sydney-hotel'),
                array('Melbourne',      'Australia',    'melbourne-things-to-do',           'melbourne-hotel'),
                array('Brisbane',       'Australia',    'brisbane-things-to-do',            'brisbane-hotel'),
                array('Gold Coast',     'Australia',    'gold-coast-things-to-do',          'gold-coast-hotel'),
                array('Cairns',         'Australia',    'cairns-things-to-do',              'cairns-hotel'),
                array('Perth',          'Australia',    'perth-things-to-do',               'perth-hotel'),
                array('Adelaide',       'Australia',    'adelaide-things-to-do',            'adelaide-hotel'),
                array('Darwin',         'Australia',    'darwin-things-to-do',              'darwin-hotel'),
                array('Uluru',          'Australia',    'uluru-things-to-do',               'uluru-hotel'),
                array('Auckland',       'New Zealand',  'auckland-things-to-do',            'auckland-hotel'),
                array('Queenstown',     'New Zealand',  'queenstown-things-to-do',          'queenstown-hotel'),
                array('Christchurch',   'New Zealand',  'christchurch-things-to-do',        'christchurch-hotel'),
                array('Wellington',     'New Zealand',  'wellington-things-to-do',          'wellington-hotel'),
                array('Fiji',           'Fiji',         'fiji-things-to-do',                'fiji-hotel'),
                array('Bora Bora',      'French Polynesia','bora-bora-things-to-do',        'bora-bora-hotel'),
                array('Phuket',         'Thailand',     'phuket-things-to-do',              'phuket-hotel'),
                array('Guam',           'Guam',         'guam-things-to-do',                'guam-hotel'),
            ),
        );

        $base = 'https://www.klook.com/en-US';
        // Build URL helper: supports both destination/cID-slug/1-tab and things-to-do/slug formats
        $klook_url = function($path) use ($base) {
            // If path already looks like a full segment (contains /), use as-is
            return $base . '/' . ltrim($path, '/');
        };
        $total_cities = 0;
        foreach ($klook_cities as $region => $cities) $total_cities += count($cities);
        ?>
        <div class="wrap" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
        <h1 style="display:flex;align-items:center;gap:12px;">🔗 Klook Links Library <span style="font-size:13px;font-weight:400;color:#666;background:#f0f0f1;padding:4px 10px;border-radius:20px;"><?php echo $total_cities; ?> cities</span></h1>
        <p style="color:#555;margin-bottom:20px;">Pre-built Klook listing URLs for activities &amp; hotels. Click any link to copy it, or use the "Send to Marathon" button to auto-fill the Marathon Import.</p>

        <!-- Search & Filter -->
        <div style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;align-items:center;">
            <input type="text" id="fthll_search" placeholder="🔍 Search city or country…" style="padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;min-width:260px;">
            <select id="fthll_region" style="padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                <option value="">All Regions</option>
                <?php foreach (array_keys($klook_cities) as $r): ?>
                <option value="<?php echo esc_attr($r); ?>"><?php echo esc_html($r); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="fthll_type" style="padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                <option value="both">Activities + Hotels</option>
                <option value="activity">Activities only</option>
                <option value="hotel">Hotels only</option>
            </select>
            <button id="fthll_copy_all" type="button" style="padding:10px 18px;background:#2575fc;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;">📋 Copy all visible URLs</button>
        </div>

        <!-- Results count -->
        <div id="fthll_count" style="font-size:13px;color:#666;margin-bottom:14px;"></div>

        <!-- Table -->
        <table id="fthll_table" style="width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.08);">
            <thead>
                <tr style="background:#1e3a5f;color:#fff;font-size:13px;">
                    <th style="padding:12px 16px;text-align:left;width:200px;">City</th>
                    <th style="padding:12px 16px;text-align:left;width:130px;">Country</th>
                    <th style="padding:12px 16px;text-align:left;width:110px;">Region</th>
                    <th style="padding:12px 8px;text-align:left;">Activities URL</th>
                    <th style="padding:12px 8px;text-align:left;">Hotels URL</th>
                    <th style="padding:12px 16px;text-align:center;width:110px;">Actions</th>
                </tr>
            </thead>
            <tbody id="fthll_tbody">
            <?php
            $row_idx = 0;
            foreach ($klook_cities as $region => $cities):
                foreach ($cities as $city_data):
                    list($city_name, $country, $act_path, $htl_path) = $city_data;
                    // Support both destination/cID-slug/tab paths and legacy things-to-do/slug paths
                    $act_url = (strpos($act_path, '/') !== false)
                        ? $klook_url($act_path)
                        : $klook_url('things-to-do/' . $act_path . '/');
                    $htl_url = (strpos($htl_path, '/') !== false)
                        ? $klook_url($htl_path)
                        : $klook_url('hotels/' . $htl_path . '/');
                    $bg = ($row_idx % 2 === 0) ? '#fff' : '#f8f9fa';
                    $row_idx++;
            ?>
            <tr class="fthll-row" data-region="<?php echo esc_attr($region); ?>" data-city="<?php echo esc_attr(strtolower($city_name)); ?>" data-country="<?php echo esc_attr(strtolower($country)); ?>" style="background:<?php echo $bg; ?>;border-bottom:1px solid #eee;">
                <td style="padding:10px 16px;font-weight:600;font-size:13px;"><?php echo esc_html($city_name); ?></td>
                <td style="padding:10px 16px;font-size:12px;color:#555;"><?php echo esc_html($country); ?></td>
                <td style="padding:10px 16px;font-size:11px;color:#888;"><?php echo esc_html($region); ?></td>
                <td class="fthll-act-cell" style="padding:10px 8px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span class="fthll-url" title="<?php echo esc_attr($act_url); ?>" style="font-size:11px;color:#2575fc;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px;cursor:pointer;" onclick="fthllCopy(this,'<?php echo esc_js($act_url); ?>')"><?php echo esc_html($act_url); ?></span>
                        <button type="button" class="fthll-copy-btn" data-url="<?php echo esc_attr($act_url); ?>" style="padding:3px 8px;font-size:11px;background:#e8f0fe;color:#2575fc;border:none;border-radius:4px;cursor:pointer;white-space:nowrap;flex-shrink:0;">Copy</button>
                    </div>
                </td>
                <td class="fthll-htl-cell" style="padding:10px 8px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span class="fthll-url" title="<?php echo esc_attr($htl_url); ?>" style="font-size:11px;color:#2575fc;font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px;cursor:pointer;" onclick="fthllCopy(this,'<?php echo esc_js($htl_url); ?>')"><?php echo esc_html($htl_url); ?></span>
                        <button type="button" class="fthll-copy-btn" data-url="<?php echo esc_attr($htl_url); ?>" style="padding:3px 8px;font-size:11px;background:#e8f0fe;color:#2575fc;border:none;border-radius:4px;cursor:pointer;white-space:nowrap;flex-shrink:0;">Copy</button>
                    </div>
                </td>
                <td style="padding:10px 16px;text-align:center;">
                    <button type="button" class="fthll-marathon-btn"
                        data-act="<?php echo esc_attr($act_url); ?>"
                        data-htl="<?php echo esc_attr($htl_url); ?>"
                        data-city="<?php echo esc_attr(strtolower($city_name)); ?>"
                        data-country="<?php echo esc_attr(strtolower($country)); ?>"
                        style="padding:4px 10px;font-size:11px;font-weight:700;background:#2575fc;color:#fff;border:none;border-radius:5px;cursor:pointer;white-space:nowrap;">
                        🚀 Marathon
                    </button>
                </td>
            </tr>
            <?php endforeach; endforeach; ?>
            </tbody>
        </table>
        <div id="fthll_toast" style="display:none;position:fixed;bottom:30px;right:30px;background:#323232;color:#fff;padding:12px 22px;border-radius:8px;font-size:14px;z-index:99999;box-shadow:0 4px 16px rgba(0,0,0,.2);"></div>
        </div>

        <script>
        (function($){
            var $rows = $('.fthll-row');
            function updateCount() {
                var v = $rows.filter(':visible').length;
                $('#fthll_count').text('Showing ' + v + ' cities');
            }
            function filterRows() {
                var q = $('#fthll_search').val().toLowerCase().trim();
                var reg = $('#fthll_region').val();
                var tp = $('#fthll_type').val();
                $rows.each(function() {
                    var $r = $(this);
                    var city = $r.data('city') || '';
                    var country = $r.data('country') || '';
                    var region = $r.data('region') || '';
                    var matchQ = !q || city.indexOf(q) !== -1 || country.indexOf(q) !== -1 || region.toLowerCase().indexOf(q) !== -1;
                    var matchR = !reg || region === reg;
                    $r.toggle(matchQ && matchR);
                    if (tp === 'activity') {
                        $r.find('.fthll-htl-cell').hide();
                    } else if (tp === 'hotel') {
                        $r.find('.fthll-act-cell').hide();
                    } else {
                        $r.find('.fthll-act-cell, .fthll-htl-cell').show();
                    }
                });
                updateCount();
            }
            $('#fthll_search, #fthll_region, #fthll_type').on('input change', filterRows);
            filterRows();

            // Copy individual URL
            $('.fthll-copy-btn').on('click', function() {
                fthllCopy(this, $(this).data('url'));
            });

            // Copy all visible
            $('#fthll_copy_all').on('click', function() {
                var tp = $('#fthll_type').val();
                var urls = [];
                $rows.filter(':visible').each(function() {
                    var $r = $(this);
                    if (tp !== 'hotel') urls.push($r.find('.fthll-act-cell .fthll-url').attr('title'));
                    if (tp !== 'activity') urls.push($r.find('.fthll-htl-cell .fthll-url').attr('title'));
                });
                navigator.clipboard.writeText(urls.join('\n')).then(function() {
                    fthllToast('📋 Copied ' + urls.length + ' URLs to clipboard!');
                });
            });

            // Send to Marathon Import
            $('.fthll-marathon-btn').on('click', function() {
                var $b = $(this);
                var act = $b.data('act');
                var htl = $b.data('htl');
                var tp = $('#fthll_type').val();
                var url = tp === 'hotel' ? htl : act;
                var type = tp === 'hotel' ? 'hotel' : (tp === 'activity' ? 'activity' : 'both');
                // Navigate to import page and prefill
                var importUrl = '<?php echo admin_url("admin.php?page=fth-klook-import"); ?>&fthll_prefill_url=' + encodeURIComponent(url) + '&fthll_prefill_type=' + type + '&fthll_prefill_city=' + encodeURIComponent($b.data('city')) + '&fthll_prefill_country=' + encodeURIComponent($b.data('country'));
                window.location.href = importUrl;
            });
        })(jQuery);

        function fthllCopy(el, url) {
            navigator.clipboard.writeText(url).then(function() {
                fthllToast('✅ Copied: ' + url.replace('https://www.klook.com/en-US','…'));
            });
        }
        function fthllToast(msg) {
            var $t = jQuery('#fthll_toast');
            $t.text(msg).fadeIn(200);
            clearTimeout(window._fthll_toast_timer);
            window._fthll_toast_timer = setTimeout(function(){ $t.fadeOut(400); }, 3000);
        }
        </script>
        <?php
    }

    // ─────────────────────────────────────────────────────────────
    // MARATHON IMPORT — Dedicated bulk import page
    // ─────────────────────────────────────────────────────────────
    public static function marathon_page() {
        if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }

        // WP cities for "All my cities" mode
        $cities_terms = get_terms(array('taxonomy' => 'travel_city', 'hide_empty' => false, 'orderby' => 'name'));
        if (is_wp_error($cities_terms)) $cities_terms = array();
        $countries_terms = get_terms(array('taxonomy' => 'travel_country', 'hide_empty' => false, 'orderby' => 'name'));
        if (is_wp_error($countries_terms)) $countries_terms = array();
        $js_wp_cities = array();
        foreach ($cities_terms as $ct) {
            $ku = get_term_meta($ct->term_id, '_fth_klook_url', true);
            if (empty($ku)) $ku = 'https://www.klook.com/en-US/things-to-do/' . $ct->slug . '/';
            $js_wp_cities[] = array('id' => $ct->term_id, 'name' => $ct->name, 'slug' => $ct->slug, 'url' => $ku, 'country_id' => (int)get_term_meta($ct->term_id, 'fth_parent_country', true));
        }

        // ── STATIC country → cities list (works on fresh install, no WP DB dependency)
        // Each city has: name, slug, acts_url (activities listing), hotels_url
        $country_cities_static = array(
            'UAE' => array(
                array('name'=>'Dubai',        'slug'=>'dubai',        'acts_url'=>'https://www.klook.com/en-US/destination/c78-dubai/1-things-to-do/', 'hotels_url'=>'https://www.klook.com/en-US/destination/c78-dubai/3-hotel/'),
                array('name'=>'Abu Dhabi',    'slug'=>'abu-dhabi',    'acts_url'=>'https://www.klook.com/en-US/destination/c79-abu-dhabi/1-things-to-do/', 'hotels_url'=>'https://www.klook.com/en-US/destination/c79-abu-dhabi/3-hotel/'),
                array('name'=>'Sharjah',      'slug'=>'sharjah',      'acts_url'=>'https://www.klook.com/en-US/things-to-do/sharjah/', 'hotels_url'=>'https://www.klook.com/en-US/things-to-do/sharjah/'),
                array('name'=>'Ras Al Khaimah','slug'=>'ras-al-khaimah','acts_url'=>'https://www.klook.com/en-US/things-to-do/ras-al-khaimah/', 'hotels_url'=>'https://www.klook.com/en-US/things-to-do/ras-al-khaimah/'),
                array('name'=>'Fujairah',     'slug'=>'fujairah',     'acts_url'=>'https://www.klook.com/en-US/things-to-do/fujairah/', 'hotels_url'=>'https://www.klook.com/en-US/things-to-do/fujairah/'),
            ),
            'Qatar' => array(
                array('name'=>'Doha','slug'=>'doha','acts_url'=>'https://www.klook.com/en-US/destination/c80-doha/1-things-to-do/','hotels_url'=>'https://www.klook.com/en-US/destination/c80-doha/3-hotel/'),
            ),
            'Saudi Arabia' => array(
                array('name'=>'Riyadh','slug'=>'riyadh','acts_url'=>'https://www.klook.com/en-US/things-to-do/riyadh/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/riyadh/'),
                array('name'=>'Jeddah','slug'=>'jeddah','acts_url'=>'https://www.klook.com/en-US/things-to-do/jeddah/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/jeddah/'),
                array('name'=>'Mecca','slug'=>'mecca','acts_url'=>'https://www.klook.com/en-US/things-to-do/mecca/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/mecca/'),
                array('name'=>'Medina','slug'=>'medina','acts_url'=>'https://www.klook.com/en-US/things-to-do/medina/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/medina/'),
                array('name'=>'AlUla','slug'=>'alula','acts_url'=>'https://www.klook.com/en-US/things-to-do/al-ula/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/al-ula/'),
            ),
            'Kuwait' => array(
                array('name'=>'Kuwait City','slug'=>'kuwait-city','acts_url'=>'https://www.klook.com/en-US/things-to-do/kuwait-city/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/kuwait-city/'),
            ),
            'Oman' => array(
                array('name'=>'Muscat','slug'=>'muscat','acts_url'=>'https://www.klook.com/en-US/things-to-do/muscat/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/muscat/'),
                array('name'=>'Salalah','slug'=>'salalah','acts_url'=>'https://www.klook.com/en-US/things-to-do/salalah/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/salalah/'),
            ),
            'Bahrain' => array(
                array('name'=>'Manama','slug'=>'manama','acts_url'=>'https://www.klook.com/en-US/things-to-do/manama/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/manama/'),
            ),
            'Jordan' => array(
                array('name'=>'Amman','slug'=>'amman','acts_url'=>'https://www.klook.com/en-US/things-to-do/amman/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/amman/'),
                array('name'=>'Petra','slug'=>'petra','acts_url'=>'https://www.klook.com/en-US/things-to-do/petra/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/petra/'),
                array('name'=>'Aqaba','slug'=>'aqaba','acts_url'=>'https://www.klook.com/en-US/things-to-do/aqaba/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/aqaba/'),
            ),
            'Egypt' => array(
                array('name'=>'Cairo','slug'=>'cairo','acts_url'=>'https://www.klook.com/en-US/things-to-do/cairo/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/cairo/'),
                array('name'=>'Luxor','slug'=>'luxor','acts_url'=>'https://www.klook.com/en-US/things-to-do/luxor/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/luxor/'),
                array('name'=>'Hurghada','slug'=>'hurghada','acts_url'=>'https://www.klook.com/en-US/things-to-do/hurghada/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/hurghada/'),
                array('name'=>'Sharm el-Sheikh','slug'=>'sharm-el-sheikh','acts_url'=>'https://www.klook.com/en-US/things-to-do/sharm-el-sheikh/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/sharm-el-sheikh/'),
            ),
            'Turkey' => array(
                array('name'=>'Istanbul','slug'=>'istanbul','acts_url'=>'https://www.klook.com/en-US/things-to-do/istanbul/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/istanbul/'),
                array('name'=>'Cappadocia','slug'=>'cappadocia','acts_url'=>'https://www.klook.com/en-US/things-to-do/cappadocia/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/cappadocia/'),
                array('name'=>'Antalya','slug'=>'antalya','acts_url'=>'https://www.klook.com/en-US/things-to-do/antalya/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/antalya/'),
                array('name'=>'Bodrum','slug'=>'bodrum','acts_url'=>'https://www.klook.com/en-US/things-to-do/bodrum/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/bodrum/'),
            ),
            'Thailand' => array(
                array('name'=>'Bangkok','slug'=>'bangkok','acts_url'=>'https://www.klook.com/en-US/things-to-do/bangkok/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/bangkok/'),
                array('name'=>'Phuket','slug'=>'phuket','acts_url'=>'https://www.klook.com/en-US/things-to-do/phuket/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/phuket/'),
                array('name'=>'Chiang Mai','slug'=>'chiang-mai','acts_url'=>'https://www.klook.com/en-US/things-to-do/chiang-mai/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/chiang-mai/'),
                array('name'=>'Pattaya','slug'=>'pattaya','acts_url'=>'https://www.klook.com/en-US/things-to-do/pattaya/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/pattaya/'),
                array('name'=>'Koh Samui','slug'=>'koh-samui','acts_url'=>'https://www.klook.com/en-US/things-to-do/koh-samui/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/koh-samui/'),
            ),
            'Singapore' => array(
                array('name'=>'Singapore','slug'=>'singapore','acts_url'=>'https://www.klook.com/en-US/things-to-do/singapore/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/singapore/'),
            ),
            'Malaysia' => array(
                array('name'=>'Kuala Lumpur','slug'=>'kuala-lumpur','acts_url'=>'https://www.klook.com/en-US/things-to-do/kuala-lumpur/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/kuala-lumpur/'),
                array('name'=>'Langkawi','slug'=>'langkawi','acts_url'=>'https://www.klook.com/en-US/things-to-do/langkawi/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/langkawi/'),
                array('name'=>'Penang','slug'=>'penang','acts_url'=>'https://www.klook.com/en-US/things-to-do/penang/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/penang/'),
            ),
            'Indonesia' => array(
                array('name'=>'Bali','slug'=>'bali','acts_url'=>'https://www.klook.com/en-US/things-to-do/bali/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/bali/'),
                array('name'=>'Jakarta','slug'=>'jakarta','acts_url'=>'https://www.klook.com/en-US/things-to-do/jakarta/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/jakarta/'),
                array('name'=>'Lombok','slug'=>'lombok','acts_url'=>'https://www.klook.com/en-US/things-to-do/lombok/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/lombok/'),
                array('name'=>'Yogyakarta','slug'=>'yogyakarta','acts_url'=>'https://www.klook.com/en-US/things-to-do/yogyakarta/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/yogyakarta/'),
            ),
            'Vietnam' => array(
                array('name'=>'Hanoi','slug'=>'hanoi','acts_url'=>'https://www.klook.com/en-US/things-to-do/hanoi/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/hanoi/'),
                array('name'=>'Ho Chi Minh City','slug'=>'ho-chi-minh-city','acts_url'=>'https://www.klook.com/en-US/things-to-do/ho-chi-minh-city/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/ho-chi-minh-city/'),
                array('name'=>'Da Nang','slug'=>'da-nang','acts_url'=>'https://www.klook.com/en-US/things-to-do/da-nang/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/da-nang/'),
                array('name'=>'Hoi An','slug'=>'hoi-an','acts_url'=>'https://www.klook.com/en-US/things-to-do/hoi-an/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/hoi-an/'),
                array('name'=>'Ha Long Bay','slug'=>'ha-long-bay','acts_url'=>'https://www.klook.com/en-US/things-to-do/ha-long-bay/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/ha-long-bay/'),
            ),
            'Japan' => array(
                array('name'=>'Tokyo','slug'=>'tokyo','acts_url'=>'https://www.klook.com/en-US/things-to-do/tokyo/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/tokyo/'),
                array('name'=>'Osaka','slug'=>'osaka','acts_url'=>'https://www.klook.com/en-US/things-to-do/osaka/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/osaka/'),
                array('name'=>'Kyoto','slug'=>'kyoto','acts_url'=>'https://www.klook.com/en-US/things-to-do/kyoto/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/kyoto/'),
                array('name'=>'Hokkaido','slug'=>'hokkaido','acts_url'=>'https://www.klook.com/en-US/things-to-do/hokkaido/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/hokkaido/'),
            ),
            'South Korea' => array(
                array('name'=>'Seoul','slug'=>'seoul','acts_url'=>'https://www.klook.com/en-US/things-to-do/seoul/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/seoul/'),
                array('name'=>'Busan','slug'=>'busan','acts_url'=>'https://www.klook.com/en-US/things-to-do/busan/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/busan/'),
                array('name'=>'Jeju','slug'=>'jeju','acts_url'=>'https://www.klook.com/en-US/things-to-do/jeju/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/jeju/'),
            ),
            'France' => array(
                array('name'=>'Paris','slug'=>'paris','acts_url'=>'https://www.klook.com/en-US/things-to-do/paris/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/paris/'),
                array('name'=>'Nice','slug'=>'nice','acts_url'=>'https://www.klook.com/en-US/things-to-do/nice/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/nice/'),
            ),
            'Italy' => array(
                array('name'=>'Rome','slug'=>'rome','acts_url'=>'https://www.klook.com/en-US/things-to-do/rome/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/rome/'),
                array('name'=>'Venice','slug'=>'venice','acts_url'=>'https://www.klook.com/en-US/things-to-do/venice/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/venice/'),
                array('name'=>'Florence','slug'=>'florence','acts_url'=>'https://www.klook.com/en-US/things-to-do/florence/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/florence/'),
                array('name'=>'Milan','slug'=>'milan','acts_url'=>'https://www.klook.com/en-US/things-to-do/milan/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/milan/'),
            ),
            'Spain' => array(
                array('name'=>'Barcelona','slug'=>'barcelona','acts_url'=>'https://www.klook.com/en-US/things-to-do/barcelona/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/barcelona/'),
                array('name'=>'Madrid','slug'=>'madrid','acts_url'=>'https://www.klook.com/en-US/things-to-do/madrid/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/madrid/'),
                array('name'=>'Seville','slug'=>'seville','acts_url'=>'https://www.klook.com/en-US/things-to-do/seville/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/seville/'),
            ),
            'Greece' => array(
                array('name'=>'Athens','slug'=>'athens','acts_url'=>'https://www.klook.com/en-US/things-to-do/athens/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/athens/'),
                array('name'=>'Santorini','slug'=>'santorini','acts_url'=>'https://www.klook.com/en-US/things-to-do/santorini/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/santorini/'),
                array('name'=>'Mykonos','slug'=>'mykonos','acts_url'=>'https://www.klook.com/en-US/things-to-do/mykonos/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/mykonos/'),
            ),
            'UK' => array(
                array('name'=>'London','slug'=>'london','acts_url'=>'https://www.klook.com/en-US/things-to-do/london/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/london/'),
                array('name'=>'Edinburgh','slug'=>'edinburgh','acts_url'=>'https://www.klook.com/en-US/things-to-do/edinburgh/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/edinburgh/'),
            ),
            'Australia' => array(
                array('name'=>'Sydney','slug'=>'sydney','acts_url'=>'https://www.klook.com/en-US/things-to-do/sydney/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/sydney/'),
                array('name'=>'Melbourne','slug'=>'melbourne','acts_url'=>'https://www.klook.com/en-US/things-to-do/melbourne/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/melbourne/'),
                array('name'=>'Brisbane','slug'=>'brisbane','acts_url'=>'https://www.klook.com/en-US/things-to-do/brisbane/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/brisbane/'),
                array('name'=>'Gold Coast','slug'=>'gold-coast','acts_url'=>'https://www.klook.com/en-US/things-to-do/gold-coast/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/gold-coast/'),
            ),
            'USA' => array(
                array('name'=>'New York','slug'=>'new-york','acts_url'=>'https://www.klook.com/en-US/things-to-do/new-york/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/new-york/'),
                array('name'=>'Los Angeles','slug'=>'los-angeles','acts_url'=>'https://www.klook.com/en-US/things-to-do/los-angeles/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/los-angeles/'),
                array('name'=>'Las Vegas','slug'=>'las-vegas','acts_url'=>'https://www.klook.com/en-US/things-to-do/las-vegas/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/las-vegas/'),
                array('name'=>'Orlando','slug'=>'orlando','acts_url'=>'https://www.klook.com/en-US/things-to-do/orlando/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/orlando/'),
                array('name'=>'Miami','slug'=>'miami','acts_url'=>'https://www.klook.com/en-US/things-to-do/miami/','hotels_url'=>'https://www.klook.com/en-US/things-to-do/miami/'),
            ),
        );

        // Build world cities flat list (for "World" mode)
        $world_cities = array();
        foreach ($country_cities_static as $country_name => $cities) {
            foreach ($cities as $c) {
                $world_cities[] = array_merge($c, array('country' => $country_name));
            }
        }

        // Build JS country list with city counts for dropdown
        $js_country_list = array();
        foreach ($country_cities_static as $country_name => $cities) {
            $js_country_list[] = array('name' => $country_name, 'cities' => $cities);
        }

        $nonce    = wp_create_nonce('fth_import_publish');
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <style>
        #fth-marathon-wrap{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0f172a;min-height:100vh;padding:0;margin:-10px -20px -20px;color:#fff}
        #fth-marathon-wrap *{box-sizing:border-box}
        .fm-header{background:linear-gradient(135deg,#1e3a5f,#2575fc);padding:28px 32px 22px;border-bottom:1px solid rgba(255,255,255,.1)}
        .fm-header h1{margin:0 0 4px;font-size:26px;font-weight:900}
        .fm-header p{margin:0;opacity:.8;font-size:14px}
        .fm-body{padding:24px 32px;max-width:1100px}
        .fm-card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:22px 24px;margin-bottom:20px}
        .fm-card h3{margin:0 0 16px;font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:rgba(255,255,255,.6)}
        .fm-modes{display:flex;gap:10px;flex-wrap:wrap}
        .fm-mode-btn{background:rgba(255,255,255,.08);border:2px solid rgba(255,255,255,.15);color:#fff;padding:12px 20px;border-radius:10px;cursor:pointer;font-size:14px;font-weight:700;transition:all .2s}
        .fm-mode-btn:hover{background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.3)}
        .fm-mode-btn.active{background:rgba(37,117,252,.3);border-color:#2575fc;color:#fff}
        .fm-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        @media(max-width:700px){.fm-row{grid-template-columns:1fr}}
        .fm-label{font-size:12px;font-weight:700;color:rgba(255,255,255,.6);margin-bottom:6px}
        .fm-select,.fm-input{width:100%;background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.15);color:#fff;padding:11px 14px;border-radius:8px;font-size:14px}
        .fm-select option{background:#1a2940;color:#fff}
        .fm-type-btns{display:flex;gap:10px}
        .fm-type-btn{flex:1;background:rgba(255,255,255,.08);border:2px solid rgba(255,255,255,.15);color:#fff;padding:11px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:700;transition:all .2s;text-align:center}
        .fm-type-btn.active{background:rgba(37,117,252,.3);border-color:#2575fc}
        .fm-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
        .fm-btn-start{background:linear-gradient(135deg,#2575fc,#6a11cb);color:#fff;border:none;padding:14px 36px;border-radius:10px;font-size:16px;font-weight:900;cursor:pointer;transition:opacity .2s}
        .fm-btn-start:disabled{opacity:.5;cursor:not-allowed}
        .fm-btn-stop{display:none;background:rgba(239,68,68,.2);border:1px solid #ef4444;color:#fca5a5;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer}
        .fm-btn-reset{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.2);color:#fff;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer}
        .fm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
        .fm-stat{background:rgba(255,255,255,.05);border-radius:10px;padding:14px;text-align:center}
        .fm-stat .num{font-size:32px;font-weight:900;line-height:1}
        .fm-stat .lbl{font-size:11px;color:rgba(255,255,255,.5);margin-top:4px;font-weight:600;text-transform:uppercase}
        .fm-progress-wrap{background:rgba(255,255,255,.1);border-radius:999px;height:10px;overflow:hidden;margin-bottom:8px}
        .fm-progress-bar{height:100%;border-radius:999px;background:linear-gradient(90deg,#2575fc,#6a11cb);width:0%;transition:width .4s}
        .fm-current{font-size:12px;color:rgba(255,255,255,.55);margin-bottom:16px;min-height:18px}
        .fm-log{background:rgba(0,0,0,.4);border-radius:10px;padding:14px;max-height:420px;overflow-y:auto;font-family:monospace;font-size:12px}
        .fm-log-empty{color:rgba(255,255,255,.3);text-align:center;padding:24px;font-style:italic}
        .fm-log-item{padding:4px 0;border-bottom:1px solid rgba(255,255,255,.05);display:flex;gap:8px;align-items:flex-start}
        .fm-log-item:last-child{border-bottom:none}
        .fm-log-ts{color:rgba(255,255,255,.3);flex-shrink:0;font-size:10px;padding-top:2px}
        .fm-panel-hidden{display:none}
        </style>
        <div id="fth-marathon-wrap">
        <div class="fm-header">
            <h1>🏃 Marathon Import</h1>
            <p>Import en masse depuis Klook — ville, pays, ou monde entier. S'exécute indéfiniment sans timeout.</p>
        </div>
        <div class="fm-body">

        <!-- Mode selector -->
        <div class="fm-card">
            <h3>1. Portée de l'import</h3>
            <div class="fm-modes">
                <button class="fm-mode-btn active" data-mode="city">🏙 Une ville</button>
                <button class="fm-mode-btn" data-mode="country">🌍 Un pays (toutes ses villes)</button>
                <button class="fm-mode-btn" data-mode="allcities">🗺 Toutes mes villes (base WP)</button>
                <button class="fm-mode-btn" data-mode="world">🌐 Monde entier (<?php echo count($world_cities); ?> villes Klook)</button>
            </div>
        </div>

        <!-- Scope config -->
        <div class="fm-card" id="fm-scope-city">
            <h3>Ville à importer</h3>
            <div class="fm-row">
                <div>
                    <div class="fm-label">Ville enregistrée en base</div>
                    <select class="fm-select" id="fm_city_select">
                        <option value="">— Choisir une ville —</option>
                        <?php foreach ($cities_terms as $ct):
                            $ku = get_term_meta($ct->term_id, '_fth_klook_url', true) ?: 'https://www.klook.com/en-US/things-to-do/' . $ct->slug . '/';
                        ?>
                        <option value="<?php echo $ct->term_id; ?>" data-slug="<?php echo esc_attr($ct->slug); ?>" data-url="<?php echo esc_attr($ku); ?>" data-name="<?php echo esc_attr($ct->name); ?>"><?php echo esc_html($ct->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <div class="fm-label">Ou entrer une URL Klook manuellement</div>
                    <input type="text" class="fm-input" id="fm_city_url" placeholder="https://www.klook.com/en-US/destination/c78-dubai/1-things-to-do/">
                </div>
            </div>
        </div>

        <div class="fm-card fm-panel-hidden" id="fm-scope-country">
            <h3>Pays à importer — activités &amp; hôtels de toutes ses villes</h3>
            <div class="fm-label">Sélectionner un pays</div>
            <select class="fm-select" id="fm_country_select" style="max-width:400px">
                <option value="">— Choisir un pays —</option>
                <?php foreach ($js_country_list as $co): ?>
                <option value="<?php echo esc_attr($co['name']); ?>"><?php echo esc_html($co['name']); ?> (<?php echo count($co['cities']); ?> villes)</option>
                <?php endforeach; ?>
            </select>
            <p style="margin:10px 0 0;font-size:12px;color:#34d399;">✅ Pour chaque ville : découverte des activités + hôtels sur Klook → import des posts WP avec photos, prix, description, itinéraire…</p>
        </div>

        <div class="fm-card fm-panel-hidden" id="fm-scope-allcities">
            <h3>Toutes mes villes enregistrées (<?php echo count($cities_terms); ?> villes en base WP)</h3>
            <p style="margin:0 0 8px;font-size:13px;color:#34d399;">✅ Pour chaque ville : découverte des activités + hôtels sur Klook → import complet.</p>
            <p style="margin:0;font-size:12px;opacity:.6;">Utilise les villes déjà présentes dans Travel Hub → Cities.</p>
        </div>

        <div class="fm-card fm-panel-hidden" id="fm-scope-world">
            <h3>🌐 Toutes destinations Klook — <?php echo count($world_cities); ?> villes dans <?php echo count($js_country_list); ?> pays</h3>
            <p style="margin:0 0 10px;font-size:13px;color:#34d399;">✅ Import de TOUTES les activités + hôtels pour chaque destination. Peut durer plusieurs heures — le marathon ne s'arrête pas.</p>
            <div style="display:flex;flex-wrap:wrap;gap:5px;max-height:120px;overflow-y:auto;">
                <?php foreach ($world_cities as $wc): ?>
                <span style="background:rgba(255,255,255,.08);border-radius:20px;padding:2px 8px;font-size:11px;"><?php echo esc_html($wc['name']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Import type -->
        <div class="fm-card">
            <h3>2. Type de contenu</h3>
            <div class="fm-type-btns">
                <button class="fm-type-btn active" data-type="both">🎟 + 🏨 Activités &amp; Hôtels</button>
                <button class="fm-type-btn" data-type="activity">🎟 Activités uniquement</button>
                <button class="fm-type-btn" data-type="hotel">🏨 Hôtels uniquement</button>
            </div>
        </div>

        <!-- WP assignment -->
        <div class="fm-card">
            <h3>3. Assignation WordPress (optionnel)</h3>
            <div class="fm-row">
                <div>
                    <div class="fm-label">Ville WP par défaut</div>
                    <select class="fm-select" id="fm_wp_city">
                        <option value="">Auto-detect</option>
                        <?php foreach ($cities_terms as $ct): ?>
                        <option value="<?php echo $ct->term_id; ?>"><?php echo esc_html($ct->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <div class="fm-label">Pays WP par défaut</div>
                    <select class="fm-select" id="fm_wp_country">
                        <option value="">Auto-detect</option>
                        <?php foreach ($countries_terms as $co): ?>
                        <option value="<?php echo $co->term_id; ?>"><?php echo esc_html($co->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Resume banner (hidden until a saved state is detected) -->
        <div class="fm-card" id="fm-resume-banner" style="display:none;background:rgba(52,211,153,.12);border:1px solid #34d399;">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <span style="font-size:20px;">💾</span>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:800;font-size:14px;color:#34d399;">Session précédente détectée</div>
                    <div style="font-size:12px;opacity:.8;" id="fm_resume_info"></div>
                </div>
                <button class="fm-btn-start" id="fm_resume" style="background:linear-gradient(135deg,#059669,#34d399);">▶ Reprendre</button>
                <button style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.3);padding:8px 16px;border-radius:8px;cursor:pointer;font-size:12px;" id="fm_discard_state">✕ Ignorer</button>
            </div>
        </div>

        <!-- Controls -->
        <div class="fm-card">
            <div class="fm-actions">
                <button class="fm-btn-start" id="fm_start">🚀 Lancer le Marathon</button>
                <button class="fm-btn-stop"  id="fm_stop">⏹ Arrêter</button>
                <button class="fm-btn-reset" id="fm_reset">🔄 Réinitialiser</button>
                <span id="fm_phase" style="font-size:13px;opacity:.7;"></span>
            </div>
            <div style="margin-top:10px;display:flex;align-items:center;gap:8px;">
                <label style="display:flex;align-items:center;gap:6px;font-size:12px;cursor:pointer;color:rgba(255,255,255,.75);">
                    <input type="checkbox" id="fm_force_update" style="width:14px;height:14px;">
                    <span>Forcer la mise à jour (réimporter même les éléments déjà en base)</span>
                </label>
            </div>
        </div>

        <!-- Progress & log (hidden until started) -->
        <div class="fm-card" id="fm-progress-card" style="display:none">
            <div class="fm-stats">
                <div class="fm-stat"><div class="num" id="fm_s_imported" style="color:#34d399">0</div><div class="lbl">✅ Importés</div></div>
                <div class="fm-stat"><div class="num" id="fm_s_errors"   style="color:#f87171">0</div><div class="lbl">❌ Erreurs</div></div>
                <div class="fm-stat"><div class="num" id="fm_s_skipped"  style="color:#fbbf24">0</div><div class="lbl">⏭ Ignorés</div></div>
                <div class="fm-stat"><div class="num" id="fm_s_remaining"style="color:#60a5fa">0</div><div class="lbl">⏳ Restants</div></div>
            </div>
            <div class="fm-progress-wrap"><div class="fm-progress-bar" id="fm_progress_bar"></div></div>
            <div class="fm-current" id="fm_current">En attente…</div>
            <div class="fm-log" id="fm_log"><div class="fm-log-empty" id="fm_log_empty">Le log apparaîtra ici…</div></div>
        </div>

        </div><!-- .fm-body -->
        </div><!-- #fth-marathon-wrap -->

        <script>
        (function() {
            'use strict';

            var AJAX_URL  = <?php echo json_encode($ajax_url); ?>;
            var NONCE     = <?php echo json_encode($nonce); ?>;
            var ALL_CITIES   = <?php echo json_encode(array_values($js_wp_cities)); ?>;
            var COUNTRY_CITIES = <?php echo json_encode($js_country_list); ?>;
            var WORLD_CITIES   = <?php echo json_encode($world_cities); ?>;

            var currentMode = 'city';
            var currentType = 'both';
            var stopped     = false;
            var running     = false;

            var imported = 0, errors = 0, skipped = 0, remaining = 0, discovered = 0;

            // ── DOM refs ──────────────────────────────────────────────
            var startBtn      = document.getElementById('fm_start');
            var stopBtn       = document.getElementById('fm_stop');
            var resetBtn      = document.getElementById('fm_reset');
            var resumeBtn     = document.getElementById('fm_resume');
            var resumeBanner  = document.getElementById('fm-resume-banner');
            var resumeInfo    = document.getElementById('fm_resume_info');
            var discardBtn    = document.getElementById('fm_discard_state');
            var forceUpdateEl = document.getElementById('fm_force_update');
            var progressCard  = document.getElementById('fm-progress-card');
            var progressBar   = document.getElementById('fm_progress_bar');
            var currentSpan   = document.getElementById('fm_current');
            var phaseSpan     = document.getElementById('fm_phase');
            var logEl         = document.getElementById('fm_log');
            var logEmpty      = document.getElementById('fm_log_empty');
            var sImported     = document.getElementById('fm_s_imported');
            var sErrors       = document.getElementById('fm_s_errors');
            var sSkipped      = document.getElementById('fm_s_skipped');
            var sRemaining    = document.getElementById('fm_s_remaining');

            // ── State persistence (localStorage) ─────────────────────
            var STATE_KEY = 'fth_marathon_state_v3';

            function saveState(cityQueue, ci) {
                try {
                    localStorage.setItem(STATE_KEY, JSON.stringify({
                        cityQueue: cityQueue,
                        ci:        ci,
                        mode:      currentMode,
                        type:      currentType,
                        imported:  imported,
                        errors:    errors,
                        skipped:   skipped,
                        ts:        Date.now()
                    }));
                } catch(e) {}
            }

            function loadState() {
                try {
                    var raw = localStorage.getItem(STATE_KEY);
                    if (!raw) return null;
                    var s = JSON.parse(raw);
                    // Only restore states less than 72 hours old
                    if (!s || !s.ts || (Date.now() - s.ts) > 72 * 3600 * 1000) return null;
                    if (!s.cityQueue || !s.cityQueue.length) return null;
                    return s;
                } catch(e) { return null; }
            }

            function clearState() {
                try { localStorage.removeItem(STATE_KEY); } catch(e) {}
            }

            // ── Check for resumable session on page load ───────────────
            (function checkResume() {
                var s = loadState();
                if (!s) return;
                var elapsed = Math.round((Date.now() - s.ts) / 60000);
                var timeStr = elapsed < 60 ? elapsed + ' min ago' : Math.round(elapsed/60) + 'h ago';
                var done    = s.ci || 0;
                var total   = s.cityQueue ? s.cityQueue.length : 0;
                resumeInfo.textContent = 'Mode: ' + (s.mode||'?') + ' | ' + done + '/' + total + ' villes traitées | ' + (s.imported||0) + ' importés, ' + (s.errors||0) + ' erreurs | Sauvegardé ' + timeStr;
                resumeBanner.style.display = '';
            })();

            // ── Mode buttons ──────────────────────────────────────────
            document.querySelectorAll('.fm-mode-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (running) return;
                    document.querySelectorAll('.fm-mode-btn').forEach(function(b){ b.classList.remove('active'); });
                    btn.classList.add('active');
                    currentMode = btn.dataset.mode;
                    ['city','country','allcities','world'].forEach(function(m){
                        var el = document.getElementById('fm-scope-' + m);
                        if (el) el.classList.toggle('fm-panel-hidden', m !== currentMode);
                    });
                });
            });

            // ── Type buttons ──────────────────────────────────────────
            document.querySelectorAll('.fm-type-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (running) return;
                    document.querySelectorAll('.fm-type-btn').forEach(function(b){ b.classList.remove('active'); });
                    btn.classList.add('active');
                    currentType = btn.dataset.type;
                });
            });

            // ── Stop / Reset ──────────────────────────────────────────
            stopBtn.addEventListener('click', function() {
                stopped = true;
                stopBtn.style.display = 'none';
                log('⏹ Arrêt demandé — la progression est sauvegardée, vous pourrez reprendre.', '#fbbf24');
            });

            function doReset() {
                stopped = true; running = false;
                imported = errors = skipped = remaining = discovered = 0;
                clearState();
                resumeBanner.style.display = 'none';
                updateStats();
                progressBar.style.width = '0%';
                currentSpan.textContent = 'En attente…';
                logEl.innerHTML = '<div class="fm-log-empty" id="fm_log_empty">Le log apparaîtra ici…</div>';
                logEmpty = document.getElementById('fm_log_empty');
                progressCard.style.display = 'none';
                startBtn.disabled = false; startBtn.textContent = '🚀 Lancer le Marathon';
                stopBtn.style.display = 'none';
                phaseSpan.textContent = '';
            }

            resetBtn.addEventListener('click', function() {
                if (running && !confirm('L\'import est en cours. Vraiment réinitialiser ?')) return;
                doReset();
            });

            if (discardBtn) discardBtn.addEventListener('click', function() {
                clearState();
                resumeBanner.style.display = 'none';
            });

            // ── AJAX helper ───────────────────────────────────────────
            function ajaxPost(data) {
                return new Promise(function(resolve) {
                    var fd = new FormData();
                    Object.keys(data).forEach(function(k){ fd.append(k, data[k]); });
                    fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(resolve)
                        .catch(function(e){ resolve({success: false, data: {message: e.message}}); });
                });
            }

            // ── Logging ───────────────────────────────────────────────
            function ts() {
                var d = new Date();
                return ('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2)+':'+('0'+d.getSeconds()).slice(-2);
            }
            function log(msg, color) {
                if (logEmpty) { logEmpty.remove(); logEmpty = null; }
                var item = document.createElement('div');
                item.className = 'fm-log-item';
                item.innerHTML = '<span class="fm-log-ts">' + ts() + '</span><span style="' + (color ? 'color:'+color : '') + '">' + escHtml(msg) + '</span>';
                logEl.insertBefore(item, logEl.firstChild);
            }
            function escHtml(s) {
                return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            }
            function updateStats() {
                sImported.textContent  = imported;
                sErrors.textContent    = errors;
                sSkipped.textContent   = skipped;
                sRemaining.textContent = remaining;
                if (discovered > 0) {
                    var pct = Math.round((imported + errors + skipped) / discovered * 100);
                    progressBar.style.width = Math.min(pct, 100) + '%';
                }
            }

            // ── Build city queue ──────────────────────────────────────
            function slugify(s) {
                return String(s).toLowerCase().replace(/\s+/g,'-').replace(/[^a-z0-9-]/g,'');
            }

            function buildCityQueue() {
                var queue = [];
                if (currentMode === 'city') {
                    var sel = document.getElementById('fm_city_select');
                    var manUrl = (document.getElementById('fm_city_url').value || '').trim();
                    if (manUrl) {
                        queue.push({name: manUrl.replace(/^https?:\/\/[^\/]+/,'').replace(/\/+$/,''), slug: '', url: manUrl, id: 0, country_id: 0, country_name: '', country_slug: ''});
                    } else if (sel && sel.value) {
                        var opt = sel.options[sel.selectedIndex];
                        queue.push({name: opt.dataset.name || '', slug: opt.dataset.slug || '', url: opt.dataset.url || '', id: parseInt(sel.value)||0, country_id: 0, country_name: '', country_slug: ''});
                    }
                } else if (currentMode === 'country') {
                    var csel = document.getElementById('fm_country_select');
                    if (csel && csel.value) {
                        var selCountryName = csel.value;
                        var selCountrySlug = slugify(selCountryName);
                        COUNTRY_CITIES.forEach(function(co) {
                            if (co.name === selCountryName) {
                                co.cities.forEach(function(c) {
                                    queue.push({name: c.name, slug: c.slug, url: c.acts_url, hotels_url: c.hotels_url,
                                        id: 0, country_id: 0, country_name: selCountryName, country_slug: selCountrySlug});
                                });
                            }
                        });
                    }
                } else if (currentMode === 'allcities') {
                    queue = ALL_CITIES.map(function(c){
                        return Object.assign({country_name: '', country_slug: ''}, c);
                    });
                } else if (currentMode === 'world') {
                    WORLD_CITIES.forEach(function(wc){
                        var cname = wc.country || '';
                        queue.push({name: wc.name, slug: wc.slug, url: wc.acts_url || wc.url, hotels_url: wc.hotels_url || wc.url,
                            id: 0, country_id: 0, country_name: cname, country_slug: slugify(cname)});
                    });
                }
                return queue;
            }

            // ── Core marathon runner (shared by start + resume) ───────
            async function runMarathon(cityQueue, startCi, resumeStats) {
                stopped  = false; running = true;
                if (resumeStats) {
                    imported  = resumeStats.imported  || 0;
                    errors    = resumeStats.errors    || 0;
                    skipped   = resumeStats.skipped   || 0;
                    discovered = 0; remaining = 0;
                } else {
                    imported = errors = skipped = remaining = discovered = 0;
                }
                updateStats();
                progressCard.style.display = '';
                startBtn.disabled = true; startBtn.textContent = '⏳ En cours…';
                if (resumeBtn) resumeBtn.disabled = true;
                stopBtn.style.display = '';
                if (!resumeStats) progressBar.style.width = '0%';
                logEl.innerHTML = '';
                resumeBanner.style.display = 'none';

                var wpCity       = document.getElementById('fm_wp_city').value    || '';
                var wpCountry    = document.getElementById('fm_wp_country').value || '';
                var types        = currentType === 'both' ? ['activity','hotel'] : [currentType];
                var forceUpdate  = forceUpdateEl && forceUpdateEl.checked ? 1 : 0;

                for (var ci = startCi; ci < cityQueue.length && !stopped; ci++) {
                    var cityInfo = cityQueue[ci];
                    phaseSpan.textContent = 'Ville ' + (ci+1) + '/' + cityQueue.length + ' — ' + cityInfo.name;
                    // Save position before starting each city so resume works
                    saveState(cityQueue, ci);

                    for (var ti = 0; ti < types.length && !stopped; ti++) {
                        var type = types[ti];
                        // Use dedicated hotels_url if available, otherwise swap tab in acts_url
                        var klookUrl;
                        if (type === 'hotel' && cityInfo.hotels_url) {
                            klookUrl = cityInfo.hotels_url;
                        } else {
                            klookUrl = cityInfo.url;
                            if (type === 'hotel' && klookUrl.indexOf('1-things-to-do') !== -1) {
                                klookUrl = klookUrl.replace('1-things-to-do', '3-hotel');
                            }
                        }

                        var typeLabel = type === 'hotel' ? 'hôtels' : 'activités';
                        currentSpan.textContent = '🔍 Découverte des ' + typeLabel + ' pour ' + cityInfo.name + '…';
                        log('🔍 Découverte ' + typeLabel + ' — ' + cityInfo.name, '#60a5fa');

                        var discRes = await ajaxPost({
                            action:       'fth_discover_import_urls',
                            url:          klookUrl,
                            type:         type,
                            city:         cityInfo.id || '',
                            limit:        200,
                            force_update: forceUpdate,
                            nonce:        NONCE
                        });

                        if (!discRes.success || !discRes.data || !discRes.data.urls || !discRes.data.urls.length) {
                            var msg = discRes.data && discRes.data.message ? discRes.data.message : 'Aucun résultat';
                            var isAlreadyImported = discRes.success && discRes.data && discRes.data.skipped > 0;
                            log((isAlreadyImported ? 'ℹ️' : '⚠️') + ' ' + cityInfo.name + ' (' + typeLabel + '): ' + msg, isAlreadyImported ? '#60a5fa' : '#fbbf24');
                            skipped += (discRes.data && discRes.data.skipped) ? discRes.data.skipped : 0;
                            updateStats();
                            continue;
                        }

                        var urls = discRes.data.urls;
                        skipped += discRes.data.skipped || 0;
                        discovered += urls.length;
                        remaining   = urls.length;
                        updateStats();
                        var existingLabel = forceUpdate ? ' (mise à jour forcée)' : '';
                        log('✅ ' + urls.length + ' ' + typeLabel + ' à importer pour ' + cityInfo.name + existingLabel, '#34d399');

                        for (var ui = 0; ui < urls.length && !stopped; ui++) {
                            var itemUrl = urls[ui];
                            remaining = urls.length - ui;
                            currentSpan.textContent = '⬇️ Import ' + (ui+1) + '/' + urls.length + ' — ' + itemUrl.split('/').slice(-2,-1)[0];
                            updateStats();

                            // Use resolved WP IDs when available; always pass name/slug as fallback
                            // so PHP can auto-create/find the correct terms per city
                            var useCity    = cityInfo.id         || 0;
                            var useCountry = cityInfo.country_id || 0;
                            // Only fall back to WP selector if city has no name info at all
                            if (!useCity && !cityInfo.name && wpCity)    useCity    = wpCity;
                            if (!useCountry && !cityInfo.country_name && wpCountry) useCountry = wpCountry;

                            var impRes = await ajaxPost({
                                action:        'fth_import_single_live',
                                url:           itemUrl,
                                type:          type,
                                city:          useCity,
                                city_name:     cityInfo.name         || '',
                                city_slug:     cityInfo.slug         || '',
                                country:       useCountry,
                                country_name:  cityInfo.country_name || '',
                                country_slug:  cityInfo.country_slug || '',
                                category:      '',
                                publish:       1,
                                nonce:         NONCE
                            });

                            if (impRes && impRes.success) {
                                imported++;
                                log('✅ ' + (impRes.data && impRes.data.title ? impRes.data.title : itemUrl));
                            } else {
                                errors++;
                                var errMsg = (impRes && impRes.data && impRes.data.message) ? impRes.data.message : 'Échec';
                                log('❌ ' + itemUrl.split('/').slice(-2,-1)[0] + ' — ' + errMsg, '#f87171');
                            }
                            updateStats();
                        }
                        remaining = 0;
                        updateStats();
                    }
                    // Save progress after completing each city
                    saveState(cityQueue, ci + 1);
                }

                // Done
                running = false;
                startBtn.disabled = false; startBtn.textContent = '🚀 Lancer le Marathon';
                if (resumeBtn) resumeBtn.disabled = false;
                stopBtn.style.display = 'none';
                progressBar.style.width = stopped ? progressBar.style.width : '100%';

                if (stopped) {
                    currentSpan.textContent = '⏹ Arrêté — ' + imported + ' importés, ' + errors + ' erreurs';
                    log('⏹ Marathon arrêté. Progression sauvegardée — cliquez "Reprendre" pour continuer.', '#fbbf24');
                    // Show resume banner with updated state
                    var s = loadState();
                    if (s) {
                        var done2 = s.ci || 0, total2 = s.cityQueue ? s.cityQueue.length : 0;
                        resumeInfo.textContent = 'Mode: ' + (s.mode||'?') + ' | ' + done2 + '/' + total2 + ' villes | ' + (s.imported||0) + ' importés, ' + (s.errors||0) + ' erreurs | Sauvegardé à l\'instant';
                        resumeBanner.style.display = '';
                    }
                } else {
                    clearState();
                    currentSpan.textContent = '🎉 Terminé — ' + imported + ' importés, ' + errors + ' erreurs, ' + skipped + ' ignorés';
                    log('🎉 Marathon terminé ! ' + imported + ' importés, ' + errors + ' erreurs.', '#34d399');
                    phaseSpan.textContent = 'Terminé';
                }
            }

            // ── Start button ──────────────────────────────────────────
            startBtn.addEventListener('click', async function() {
                if (running) return;
                var cityQueue = buildCityQueue();
                if (!cityQueue.length) { alert('Veuillez sélectionner une ville ou un pays.'); return; }
                await runMarathon(cityQueue, 0, null);
            });

            // ── Resume button ─────────────────────────────────────────
            if (resumeBtn) resumeBtn.addEventListener('click', async function() {
                if (running) return;
                var s = loadState();
                if (!s || !s.cityQueue || !s.cityQueue.length) { alert('Aucune session à reprendre.'); return; }
                // Restore mode/type from saved state
                currentMode = s.mode || currentMode;
                currentType = s.type || currentType;
                // Sync UI buttons
                document.querySelectorAll('.fm-mode-btn').forEach(function(b){ b.classList.toggle('active', b.dataset.mode === currentMode); });
                document.querySelectorAll('.fm-type-btn').forEach(function(b){ b.classList.toggle('active', b.dataset.type === currentType); });
                var startCi = s.ci || 0;
                if (startCi >= s.cityQueue.length) {
                    alert('Session déjà complète.'); clearState(); resumeBanner.style.display = 'none'; return;
                }
                log('▶ Reprise depuis la ville ' + (startCi+1) + '/' + s.cityQueue.length, '#34d399');
                await runMarathon(s.cityQueue, startCi, {imported: s.imported||0, errors: s.errors||0, skipped: s.skipped||0});
            });

        })();
        </script>
        <?php
    }
}
