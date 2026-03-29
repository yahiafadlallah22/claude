<?php
/**
 * Admin Dashboard Extras
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Admin_Dashboard {
    
    /**
     * Initialize
     */
    public static function init() {
        add_action('wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widgets'));
    }
    
    /**
     * Add dashboard widgets
     */
    public static function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'fth_dashboard_widget',
            'Travel Hub Overview',
            array(__CLASS__, 'dashboard_widget')
        );
    }
    
    /**
     * Dashboard widget content
     */
    public static function dashboard_widget() {
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        $activities = wp_count_posts('travel_activity')->publish;
        $destinations = wp_count_posts('travel_destination')->publish;
        ?>
        <div class="fth-dashboard-widget">
            <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                <div style="text-align: center; flex: 1;">
                    <div style="font-size: 24px; font-weight: bold; color: <?php echo esc_attr($primary_color); ?>;"><?php echo esc_html($activities); ?></div>
                    <div style="font-size: 12px; color: #666;">Activities</div>
                </div>
                <div style="text-align: center; flex: 1;">
                    <div style="font-size: 24px; font-weight: bold; color: #e74c3c;"><?php echo esc_html($destinations); ?></div>
                    <div style="font-size: 12px; color: #666;">Destinations</div>
                </div>
            </div>
            
            <p style="margin-bottom: 10px;">
                <a href="<?php echo admin_url('admin.php?page=fth-travel-hub'); ?>" class="button button-primary" style="background-color: <?php echo esc_attr($primary_color); ?>; border-color: <?php echo esc_attr($primary_color); ?>;">
                    Open Travel Hub
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=travel_activity'); ?>" class="button">
                    Add Activity
                </a>
            </p>
        </div>
        <?php
    }
}
