<?php
/**
 * Public Frontend Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Public {
    
    /**
     * Initialize public
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('wp_head', array(__CLASS__, 'output_custom_css'), 100);
    }
    
    /**
     * Enqueue frontend scripts
     */
    public static function enqueue_scripts() {
        // FontAwesome — try multiple CDNs for reliability
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
            array(),
            '6.5.2'
        );
        // Inline fallback: if FA icons still fail, replace with Unicode equivalents via CSS
        wp_add_inline_style('font-awesome', self::get_icon_fallback_css());
        
        // Plugin CSS
        wp_enqueue_style(
            'fth-public',
            FTH_PLUGIN_URL . 'assets/css/public.css',
            array(),
            FTH_VERSION
        );
        
        // Plugin JS
        wp_enqueue_script(
            'fth-public',
            FTH_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            FTH_VERSION,
            true
        );
        
        wp_localize_script('fth-public', 'fthPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fth_ajax_nonce'),
        ));
    }
    
    /**
     * Output custom CSS variables
     */
    public static function output_custom_css() {
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        $secondary_color = Flavor_Travel_Hub::get_secondary_color();
        ?>
        <style id="fth-custom-css">
            :root {
                --fth-primary: <?php echo esc_attr($primary_color); ?>;
                --fth-primary-dark: <?php echo esc_attr(self::darken_color($primary_color, 15)); ?>;
                --fth-primary-light: <?php echo esc_attr(self::lighten_color($primary_color, 40)); ?>;
                --fth-primary-rgb: <?php echo esc_attr(self::hex_to_rgb($primary_color)); ?>;
                --fth-secondary: <?php echo esc_attr($secondary_color); ?>;
                --fth-secondary-dark: <?php echo esc_attr(self::darken_color($secondary_color, 15)); ?>;
            }
        </style>
        <?php
    }
    
    /**
     * Darken a hex color (public method)
     */
    public static function darken_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Lighten a hex color
     */
    private static function lighten_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = min(255, $r + ((255 - $r) * $percent / 100));
        $g = min(255, $g + ((255 - $g) * $percent / 100));
        $b = min(255, $b + ((255 - $b) * $percent / 100));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Convert hex to RGB string
     */
    private static function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "{$r}, {$g}, {$b}";
    }

    /**
     * CSS fallback for FontAwesome icons.
     * If the CDN is blocked or slow, common fa-* icons are replaced with
     * Unicode equivalents via CSS content so the UI doesn't show empty boxes.
     */
    public static function get_icon_fallback_css() {
        return "
/* FA icon fallbacks — used when CDN is unavailable */
.fa-star::before,.fas.fa-star::before{content:\"★\"}
.fa-star-half-alt::before,.fas.fa-star-half-alt::before{content:\"⯨\"}
.fa-check-circle::before,.fas.fa-check-circle::before{content:\"✓\"}
.fa-check::before,.fas.fa-check::before{content:\"✓\"}
.fa-times::before,.fas.fa-times::before{content:\"✕\"}
.fa-clock::before,.fas.fa-clock::before,.far.fa-clock::before{content:\"⏱\"}
.fa-map-marker-alt::before,.fas.fa-map-marker-alt::before{content:\"📍\"}
.fa-map-marker::before,.fas.fa-map-marker::before{content:\"📍\"}
.fa-globe::before,.fas.fa-globe::before{content:\"🌍\"}
.fa-hotel::before,.fas.fa-hotel::before{content:\"🏨\"}
.fa-bed::before,.fas.fa-bed::before{content:\"🛏\"}
.fa-wifi::before,.fas.fa-wifi::before{content:\"📶\"}
.fa-swimming-pool::before,.fas.fa-swimming-pool::before{content:\"🏊\"}
.fa-utensils::before,.fas.fa-utensils::before{content:\"🍽\"}
.fa-car::before,.fas.fa-car::before{content:\"🚗\"}
.fa-plane::before,.fas.fa-plane::before{content:\"✈\"}
.fa-ticket-alt::before,.fas.fa-ticket-alt::before{content:\"🎟\"}
.fa-users::before,.fas.fa-users::before{content:\"👥\"}
.fa-user::before,.fas.fa-user::before{content:\"👤\"}
.fa-heart::before,.fas.fa-heart::before{content:\"♥\"}
.fa-calendar::before,.fas.fa-calendar::before,.far.fa-calendar::before{content:\"📅\"}
.fa-info-circle::before,.fas.fa-info-circle::before{content:\"ℹ\"}
.fa-tag::before,.fas.fa-tag::before{content:\"🏷\"}
.fa-tags::before,.fas.fa-tags::before{content:\"🏷\"}
.fa-search::before,.fas.fa-search::before{content:\"🔍\"}
.fa-chevron-right::before,.fas.fa-chevron-right::before{content:\"›\"}
.fa-chevron-left::before,.fas.fa-chevron-left::before{content:\"‹\"}
.fa-chevron-down::before,.fas.fa-chevron-down::before{content:\"⌄\"}
.fa-angle-right::before,.fas.fa-angle-right::before{content:\"›\"}
.fa-arrow-right::before,.fas.fa-arrow-right::before{content:\"→\"}
.fa-share-alt::before,.fas.fa-share-alt::before{content:\"↗\"}
.fa-external-link-alt::before,.fas.fa-external-link-alt::before{content:\"↗\"}
.fa-eye::before,.fas.fa-eye::before{content:\"👁\"}
.fa-bars::before,.fas.fa-bars::before{content:\"≡\"}
.fa-times-circle::before,.fas.fa-times-circle::before{content:\"✖\"}
.fa-exclamation-circle::before,.fas.fa-exclamation-circle::before{content:\"⚠\"}
.fa-fire::before,.fas.fa-fire::before{content:\"🔥\"}
.fa-bolt::before,.fas.fa-bolt::before{content:\"⚡\"}
.fa-award::before,.fas.fa-award::before{content:\"🏆\"}
.fa-thumbs-up::before,.fas.fa-thumbs-up::before{content:\"👍\"}
.fa-percent::before,.fas.fa-percent::before{content:\"%\"}
.fa-coins::before,.fas.fa-coins::before{content:\"💰\"}
.fa-shield-alt::before,.fas.fa-shield-alt::before{content:\"🛡\"}
.fa-compass::before,.fas.fa-compass::before{content:\"🧭\"}
.fa-mountain::before,.fas.fa-mountain::before{content:\"⛰\"}
.fa-water::before,.fas.fa-water::before{content:\"🌊\"}
.fa-tree::before,.fas.fa-tree::before{content:\"🌲\"}
.fa-camera::before,.fas.fa-camera::before{content:\"📷\"}
.fa-music::before,.fas.fa-music::before{content:\"🎵\"}
.fa-shopping-bag::before,.fas.fa-shopping-bag::before{content:\"🛍\"}
.fa-spa::before,.fas.fa-spa::before{content:\"💆\"}
.fa-bicycle::before,.fas.fa-bicycle::before{content:\"🚲\"}
.fa-ship::before,.fas.fa-ship::before{content:\"🚢\"}
.fa-helicopter::before,.fas.fa-helicopter::before{content:\"🚁\"}
.fa-walking::before,.fas.fa-walking::before{content:\"🚶\"}
.fa-horse::before,.fas.fa-horse::before{content:\"🐴\"}
.fa-fish::before,.fas.fa-fish::before{content:\"🐟\"}
.fa-golf-ball::before,.fas.fa-golf-ball::before{content:\"⛳\"}
.fa-skiing::before,.fas.fa-skiing::before{content:\"⛷\"}
.fa-snowflake::before,.fas.fa-snowflake::before,.far.fa-snowflake::before{content:\"❄\"}
.fa-sun::before,.fas.fa-sun::before,.far.fa-sun::before{content:\"☀\"}
.fa-moon::before,.fas.fa-moon::before{content:\"🌙\"}
.fa-city::before,.fas.fa-city::before{content:\"🏙\"}
.fa-landmark::before,.fas.fa-landmark::before{content:\"🏛\"}
.fa-monument::before,.fas.fa-monument::before{content:\"🗿\"}
.fa-umbrella-beach::before,.fas.fa-umbrella-beach::before{content:\"🏖\"}
.fa-mosque::before,.fas.fa-mosque::before{content:\"🕌\"}
.fa-church::before,.fas.fa-church::before{content:\"⛪\"}
.fa-synagogue::before,.fas.fa-synagogue::before{content:\"🕍\"}
.fa-theater-masks::before,.fas.fa-theater-masks::before{content:\"🎭\"}
.fa-paint-brush::before,.fas.fa-paint-brush::before{content:\"🎨\"}
.fa-wine-glass::before,.fas.fa-wine-glass::before{content:\"🍷\"}
.fa-coffee::before,.fas.fa-coffee::before{content:\"☕\"}
.fa-cocktail::before,.fas.fa-cocktail::before{content:\"🍹\"}
";
    }
}
