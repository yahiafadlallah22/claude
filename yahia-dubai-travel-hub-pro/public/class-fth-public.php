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
        // FontAwesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
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
}
