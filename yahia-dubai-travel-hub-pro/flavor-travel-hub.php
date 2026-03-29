<?php
/**
 * Plugin Name: Yahia Dubai Travel Hub Pro
 * Plugin URI: https://flavor.ae/
 * Description: Yahia Dubai travel hub – attractions, tours and hotels imported from Klook with affiliate links, full AIOSEO automation, Klook-style design and WP Residence-safe templates. v1.13
 * Version: 1.13.0
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
define('FTH_VERSION', '1.13.0');
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
        add_action('init', array($this, 'handle_image_proxy'), 1);
        add_action('init', array($this, 'migrate_french_options'), 5);
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
            'fth_promo_text'          => 'Exclusive deal negotiated by Yahia Fadlallah for you',
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
        return get_option('fth_promo_text', 'Exclusive deal negotiated by Yahia Fadlallah for you');
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
     * Migrate options that may still hold French values from a previous install.
     * Runs once on init priority 5 – only updates if the option contains French text.
     */
    public function migrate_french_options() {
        $migration_key = 'fth_options_migrated_v190';
        if (get_option($migration_key)) {
            return;
        }

        $english_map = array(
            'fth_promo_text'           => 'Exclusive deal negotiated by Yahia Fadlallah for you',
            'fth_booking_button_text'  => 'ACTIVATE DISCOUNT',
            'fth_things_hero_title'    => 'Worldwide Tours & Attractions',
            'fth_things_hero_subtitle' => 'Discover trusted tours, attractions and experiences with a premium Yahia Dubai presentation.',
            'fth_hotels_hero_title'    => 'Worldwide Hotels',
            'fth_hotels_hero_subtitle' => 'Compare hotel pages, amenities and live rates with a premium Yahia Dubai presentation.',
            'fth_search_placeholder'   => 'Search attractions, tours and hotels...',
        );

        // French keywords that indicate an old translated value
        $french_markers = array('négoci', 'pour vous', 'exclusif', 'activez', 'découvrez', 'comparez', 'partout dans', 'hôtels', 'monde');

        foreach ($english_map as $key => $english_value) {
            $current = get_option($key, '');
            $has_french = false;
            if ($current) {
                foreach ($french_markers as $marker) {
                    if (stripos($current, $marker) !== false) {
                        $has_french = true;
                        break;
                    }
                }
            }
            // Update if empty OR contains French
            if (!$current || $has_french) {
                update_option($key, $english_value);
            }
        }

        update_option($migration_key, '1.11.0');
    }

    /**
     * Generate a branded featured image for a taxonomy term (city / country / category).
     * White background · Yahia photo bottom-centre · term name in blue Poppins at top.
     * Returns WP attachment ID on success, 0 on failure.
     */
    public static function generate_taxonomy_image($term_name, $term_id, $taxonomy = 'travel_city') {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) {
            return 0; // GD not available
        }

        $upload  = wp_upload_dir();
        $dir     = $upload['basedir'] . '/fth-taxonomy-images';
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $slug    = sanitize_title($term_name);
        $imgfile = $dir . '/' . $taxonomy . '-' . $slug . '.jpg';
        $imgurl  = $upload['baseurl'] . '/fth-taxonomy-images/' . $taxonomy . '-' . $slug . '.jpg';

        // Download and cache Poppins Bold TTF if not already cached
        $font_dir  = $upload['basedir'] . '/fth-fonts';
        if (!is_dir($font_dir)) {
            wp_mkdir_p($font_dir);
        }
        $font_file = $font_dir . '/Poppins-Bold.ttf';
        if (!file_exists($font_file)) {
            $font_resp = wp_remote_get(
                'https://github.com/google/fonts/raw/main/ofl/poppins/Poppins-Bold.ttf',
                array('timeout' => 20, 'sslverify' => false)
            );
            if (!is_wp_error($font_resp) && wp_remote_retrieve_response_code($font_resp) === 200) {
                file_put_contents($font_file, wp_remote_retrieve_body($font_resp));
            }
        }

        // Canvas: 1200 × 630 (standard OG / featured image)
        $w   = 1200;
        $h   = 630;
        $img = imagecreatetruecolor($w, $h);

        // White background
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);

        // Light blue tint strip at top (40 px)
        $strip_color = imagecolorallocate($img, 41, 137, 192); // #2989C0
        imagefilledrectangle($img, 0, 0, $w, 8, $strip_color);

        // Download Yahia's photo and overlay it centred at bottom
        $yahia_url  = 'https://yahiadubai.com/wp-content/uploads/2026/03/New-Project-4.png';
        $yahia_file = $font_dir . '/yahia-promo.png';
        if (!file_exists($yahia_file)) {
            $yahia_resp = wp_remote_get($yahia_url, array('timeout' => 20, 'sslverify' => false));
            if (!is_wp_error($yahia_resp) && wp_remote_retrieve_response_code($yahia_resp) === 200) {
                file_put_contents($yahia_file, wp_remote_retrieve_body($yahia_resp));
            }
        }
        if (file_exists($yahia_file) && filesize($yahia_file) > 0) {
            $yahia_src = @imagecreatefrompng($yahia_file);
            if ($yahia_src) {
                $yw  = imagesx($yahia_src);
                $yh  = imagesy($yahia_src);
                // Target height: 340 px, preserve ratio
                $th  = 340;
                $tw  = (int) round($yw * $th / max($yh, 1));
                $dst_x = (int) round(($w - $tw) / 2);
                $dst_y = $h - $th - 20;
                imagecopyresampled($img, $yahia_src, $dst_x, $dst_y, 0, 0, $tw, $th, $yw, $yh);
                imagedestroy($yahia_src);
            }
        }

        // Title text — Poppins Bold, uppercase, blue
        $text       = mb_strtoupper($term_name, 'UTF-8');
        $font_size  = 58;
        $blue       = imagecolorallocate($img, 41, 137, 192);
        $text_y     = 130;

        if (file_exists($font_file)) {
            // Auto-reduce font size if text is too wide
            do {
                $bbox = imagettfbbox($font_size, 0, $font_file, $text);
                $text_w = abs($bbox[4] - $bbox[0]);
                if ($text_w > $w - 80 && $font_size > 24) {
                    $font_size -= 4;
                } else {
                    break;
                }
            } while (true);

            $bbox   = imagettfbbox($font_size, 0, $font_file, $text);
            $text_w = abs($bbox[4] - $bbox[0]);
            $text_x = (int) round(($w - $text_w) / 2);
            // Draw subtle shadow
            $shadow = imagecolorallocate($img, 200, 220, 235);
            imagettftext($img, $font_size, 0, $text_x + 2, $text_y + 2, $shadow, $font_file, $text);
            imagettftext($img, $font_size, 0, $text_x, $text_y, $blue, $font_file, $text);
        } else {
            // Fallback: built-in GD font (no TTF available)
            imagestring($img, 5, 40, 50, $text, $blue);
        }

        // Save as JPEG (quality 90)
        imagejpeg($img, $imgfile, 90);
        imagedestroy($img);

        if (!file_exists($imgfile) || filesize($imgfile) < 100) {
            return 0;
        }

        // Register as WordPress attachment
        $attachment = array(
            'post_mime_type' => 'image/jpeg',
            'post_title'     => sanitize_text_field($term_name),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $att_id = wp_insert_attachment($attachment, $imgfile);
        if ($att_id && !is_wp_error($att_id)) {
            if (!function_exists('wp_generate_attachment_metadata')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }
            wp_update_attachment_metadata($att_id, wp_generate_attachment_metadata($att_id, $imgfile));
            // Store on the term so get_city_card() / get_country_card() picks it up
            update_term_meta($term_id, 'fth_hero_image', wp_get_attachment_url($att_id));
            update_term_meta($term_id, 'fth_hero_image_id', $att_id);
        }
        return (int) $att_id;
    }

    /**
     * Return a proxied URL for Klook CDN images so browsers can load them
     * without being blocked by res.klook.com CDN hotlink protection.
     * Non-Klook URLs are returned unchanged.
     */
    public static function fth_img_url($url) {
        if (empty($url)) {
            return $url;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host || strpos($host, 'klook.com') === false) {
            return $url;
        }
        // Base64url-encode the original URL (no padding)
        $encoded = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
        return add_query_arg('fth_img', $encoded, home_url('/'));
    }

    /**
     * Image proxy handler – serves Klook CDN images with spoofed Referer
     * so the browser can display them without hotlink errors.
     * Called on init priority 1, exits early if ?fth_img is not set.
     */
    public function handle_image_proxy() {
        if (empty($_GET['fth_img'])) {
            return;
        }

        // Decode base64url → original URL
        $b64 = sanitize_text_field(wp_unslash($_GET['fth_img']));
        // Re-pad and reverse URL-safe encoding
        $pad = strlen($b64) % 4;
        if ($pad) { $b64 .= str_repeat('=', 4 - $pad); }
        $url = base64_decode(strtr($b64, '-_', '+/'), true);

        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            status_header(400);
            exit;
        }

        // Security: only proxy Klook CDN images
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host || strpos($host, 'klook.com') === false) {
            status_header(403);
            exit;
        }

        // Disk cache
        $upload_dir    = wp_upload_dir();
        $cache_dir     = $upload_dir['basedir'] . '/fth-img-cache';
        if (!is_dir($cache_dir)) {
            wp_mkdir_p($cache_dir);
            // Prevent directory listing
            @file_put_contents($cache_dir . '/.htaccess', "Options -Indexes\n");
        }
        $cache_key      = md5($url);
        $cache_img      = $cache_dir . '/' . $cache_key . '.img';
        $cache_meta     = $cache_dir . '/' . $cache_key . '.meta';

        // Serve from cache if available and recent (30 days)
        if (file_exists($cache_img) && file_exists($cache_meta) && (time() - filemtime($cache_img) < 2592000)) {
            $meta = json_decode(file_get_contents($cache_meta), true);
            $ct   = isset($meta['ct']) ? $meta['ct'] : 'image/jpeg';
            header('Content-Type: ' . $ct);
            header('Cache-Control: public, max-age=2592000');
            header('X-FTH-Cache: HIT');
            readfile($cache_img);
            exit;
        }

        // Fetch via cURL with Klook Referer header
        $img_data = false;
        $ct       = 'image/jpeg';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER     => array(
                    'Referer: https://www.klook.com/',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                ),
            ));
            $img_data  = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $raw_ct    = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            if (!$img_data || $http_code !== 200) {
                status_header(404);
                exit;
            }
            if ($raw_ct) {
                $ct = strtok($raw_ct, ';');
                $ct = trim($ct);
            }
        } else {
            // Fallback: wp_remote_get
            $resp = wp_remote_get($url, array(
                'timeout' => 20,
                'headers' => array(
                    'Referer'         => 'https://www.klook.com/',
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept'          => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ),
            ));
            if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) {
                status_header(404);
                exit;
            }
            $img_data = wp_remote_retrieve_body($resp);
            $raw_ct   = wp_remote_retrieve_header($resp, 'content-type');
            if ($raw_ct) {
                $ct = strtok($raw_ct, ';');
                $ct = trim($ct);
            }
        }

        // Validate content type
        $allowed_ct = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif');
        if (!in_array($ct, $allowed_ct, true)) {
            $ct = 'image/jpeg';
        }

        // Save to cache
        @file_put_contents($cache_img, $img_data);
        @file_put_contents($cache_meta, json_encode(array('ct' => $ct, 'url' => $url, 'ts' => time())));

        header('Content-Type: ' . $ct);
        header('Cache-Control: public, max-age=2592000');
        header('X-FTH-Cache: MISS');
        echo $img_data;
        exit;
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
