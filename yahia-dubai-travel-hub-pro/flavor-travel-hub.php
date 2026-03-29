<?php
/**
 * Plugin Name: Yahia Dubai Travel Hub Pro
 * Plugin URI: https://flavor.ae/
 * Description: Yahia Dubai travel hub – attractions, tours and hotels imported from Klook with affiliate links, full AIOSEO automation, Klook-style design and WP Residence-safe templates. v1.7
 * Version: 1.7.0
 * Author: Flavor
 * Author URI: https://flavor.ae/
 * Text Domain: flavor-travel-hub
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('FTH_VERSION', '1.7.0');
define('FTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FTH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FTH_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('FTH_PRIMARY_COLOR', '#2989C0');
define('FTH_SECONDARY_COLOR', '#FE7434');

/**
 * Main Plugin Class
 */
final class Flavor_Travel_Hub {
    
    /**
     * Single instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core includes
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-post-types.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-taxonomies.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-meta-boxes.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-templates.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-search.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-seo.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-shortcodes.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-widgets.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-ajax.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-seed-data.php';
        require_once FTH_PLUGIN_DIR . 'includes/class-fth-aioseo-integration.php';
        
        // Admin includes
        if (is_admin()) {
            require_once FTH_PLUGIN_DIR . 'admin/class-fth-admin.php';
            require_once FTH_PLUGIN_DIR . 'admin/class-fth-admin-settings.php';
            require_once FTH_PLUGIN_DIR . 'admin/class-fth-admin-dashboard.php';
            require_once FTH_PLUGIN_DIR . 'admin/class-fth-admin-preview.php';
        }
        
        // Public includes
        require_once FTH_PLUGIN_DIR . 'public/class-fth-public.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'), 0);
        add_action('init', array($this, 'maybe_create_missing_pages'), 25);
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize post types
        FTH_Post_Types::init();
        
        // Initialize taxonomies
        FTH_Taxonomies::init();
        
        // Initialize meta boxes
        FTH_Meta_Boxes::init();
        
        // Initialize templates
        FTH_Templates::init();
        
        // Initialize search
        FTH_Search::init();
        
        // Initialize SEO
        FTH_SEO::init();
        
        // Initialize shortcodes
        FTH_Shortcodes::init();
        
        // Initialize widgets
        FTH_Widgets::init();
        
        // Initialize AJAX handlers
        FTH_Ajax::init();
        
        // Initialize admin
        if (is_admin()) {
            FTH_Admin::init();
            FTH_Admin_Settings::init();
            FTH_Admin_Dashboard::init();
            FTH_Admin_Preview::init();
        }
        
        // Initialize public
        FTH_Public::init();
    }
    

    /**
     * Ensure the main hub pages still exist after reinstalls or manual deletions.
     */
    public function maybe_create_missing_pages() {
        if (is_admin() || wp_doing_ajax()) {
            $needs = false;
            foreach (array('things-to-do' => '[fth_travel_hub]', 'hotels' => '[fth_hotels_hub]') as $slug => $shortcode) {
                $page = get_page_by_path($slug);
                if (!$page) {
                    $title = $slug === 'hotels' ? 'Worldwide Hotels' : 'Worldwide Tours & Attractions';
                    wp_insert_post(array(
                        'post_title'     => $title,
                        'post_name'      => $slug,
                        'post_content'   => $shortcode,
                        'post_status'    => 'publish',
                        'post_type'      => 'page',
                        'comment_status' => 'closed',
                    ));
                    $needs = true;
                }
            }
            if ($needs) {
                update_option('fth_needs_flush', true);
            }
        }
    }

    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('flavor-travel-hub', false, dirname(FTH_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create post types and taxonomies
        FTH_Post_Types::init();
        FTH_Taxonomies::init();
        
        // Flush rewrite rules immediately
        flush_rewrite_rules();
        
        // Set flag to flush again on first page load (ensures it works)
        update_option('fth_needs_flush', true);
        
        // Seed only taxonomy data on activation to keep activation lightweight
        FTH_Seed_Data::seed_countries();
        FTH_Seed_Data::seed_cities();
        FTH_Seed_Data::seed_categories();
        FTH_Seed_Data::seed_types();
        
        // Create default pages
        $this->create_default_pages();
        
        // Set default options
        $this->set_default_options();
        
        // Set activation flag
        update_option('fth_activated', true);
        update_option('fth_version', FTH_VERSION);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create default pages
     */
    private function create_default_pages() {
        // Attractions & tours hub
        $main_page = get_page_by_path('things-to-do');
        if (!$main_page) {
            $page_id = wp_insert_post(array(
                'post_title'     => 'Worldwide Tours & Attractions',
                'post_name'      => 'things-to-do',
                'post_content'   => '[fth_travel_hub]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'post_author'    => get_current_user_id(),
                'comment_status' => 'closed',
            ));
            if ($page_id && !is_wp_error($page_id)) {
                update_option('fth_main_page_id', $page_id);
            }
        }

        // Hotels hub
        $hotels_page = get_page_by_path('hotels');
        if (!$hotels_page) {
            $page_id = wp_insert_post(array(
                'post_title'     => 'Worldwide Hotels',
                'post_name'      => 'hotels',
                'post_content'   => '[fth_hotels_hub]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'post_author'    => get_current_user_id(),
                'comment_status' => 'closed',
            ));
            if ($page_id && !is_wp_error($page_id)) {
                update_option('fth_hotels_page_id', $page_id);
            }
        }
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'fth_primary_color'       => '#2989C0',
            'fth_secondary_color'     => '#FE7434',
            'fth_brand_name'          => 'Yahia Dubai',
            'fth_affiliate_id'        => '115387',
            'fth_items_per_page'      => 12,
            'fth_enable_reviews'      => true,
            'fth_enable_ratings'      => true,
            'fth_default_currency'    => 'USD',
            'fth_booking_button_text' => 'ACTIVATE DISCOUNT',
            'fth_search_placeholder'  => 'Search attractions, tours and hotels...',
            'fth_scraperapi_key'      => 'ecdd48490f38ad039aace84101208f7a',
            'fth_promo_text'          => 'Promotion exclusive négociée par Yahia Fadlallah pour vous',
            'fth_things_hero_title'   => 'Worldwide Tours & Attractions',
            'fth_things_hero_subtitle'=> 'Discover trusted tours, attractions and experiences with a premium Yahia Dubai presentation.',
            'fth_things_hero_image'   => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920',
            'fth_hotels_hero_title'   => 'Worldwide Hotels',
            'fth_hotels_hero_subtitle'=> 'Compare hotel pages, amenities and live rates with a premium Yahia Dubai presentation.',
            'fth_hotels_hero_image'   => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=1600',
        );
        
        foreach ($defaults as $key => $value) {
            if (false === get_option($key)) {
                update_option($key, $value);
            }
        }
    }
    
    /**
     * Get primary color
     */
    public static function get_primary_color() {
        return get_option('fth_primary_color', FTH_PRIMARY_COLOR);
    }

    public static function get_secondary_color() {
        return get_option('fth_secondary_color', FTH_SECONDARY_COLOR);
    }

    public static function get_brand_name() {
        return get_option('fth_brand_name', 'Yahia Dubai');
    }
    
    /**
     * Get affiliate ID
     */
    public static function get_affiliate_id() {
        return get_option('fth_affiliate_id', '115387');
    }

    public static function get_promo_text() {
        return get_option('fth_promo_text', 'Promotion exclusive négociée par Yahia Fadlallah pour vous');
    }

    public static function get_cta_text() {
        return get_option('fth_booking_button_text', 'ACTIVATE DISCOUNT');
    }

    /**
     * Build Klook affiliate deeplink
     * @param string $base_url The base Klook URL
     * @param array $params Additional parameters
     * @return string The complete affiliate URL
     */
    public static function build_klook_deeplink($base_url, $params = array()) {
        $affiliate_id = self::get_affiliate_id();
        
        // Parse existing URL
        $parsed = parse_url($base_url);
        
        // Get existing query params
        $query = array();
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        
        // Add affiliate ID
        $query['aid'] = $affiliate_id;
        
        // Merge additional params
        $query = array_merge($query, $params);
        
        // Rebuild URL
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
        $host = isset($parsed['host']) ? $parsed['host'] : 'www.klook.com';
        $path = isset($parsed['path']) ? $parsed['path'] : '/';
        
        return $scheme . '://' . $host . $path . '?' . http_build_query($query);
    }
    
    /**
     * Generate Klook search deeplink for a city
     * @param string $city_name The city name
     * @return string The Klook search URL
     */
    public static function get_klook_city_search_url($city_name) {
        $base_url = 'https://www.klook.com/search/';
        return self::build_klook_deeplink($base_url, array(
            'query' => urlencode($city_name),
        ));
    }
    
    /**
     * Generate Klook search deeplink for an activity
     * @param string $activity_name The activity name
     * @param string $city_name Optional city name
     * @return string The Klook search URL
     */
    public static function get_klook_activity_search_url($activity_name, $city_name = '') {
        $base_url = 'https://www.klook.com/search/';
        $query = $activity_name;
        if ($city_name) {
            $query .= ' ' . $city_name;
        }
        return self::build_klook_deeplink($base_url, array(
            'query' => urlencode($query),
        ));
    }
}

/**
 * Initialize plugin
 */
function flavor_travel_hub() {
    return Flavor_Travel_Hub::get_instance();
}

// Start the plugin
flavor_travel_hub();
