<?php
/**
 * Admin Preview
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Admin_Preview {
    
    /**
     * Initialize
     */
    public static function init() {
        add_action('edit_form_after_title', array(__CLASS__, 'show_preview_box'));
    }
    
    /**
     * Show preview box on edit screens
     */
    public static function show_preview_box($post) {
        if (!in_array($post->post_type, array('travel_activity', 'travel_destination'))) {
            return;
        }
        
        if ($post->post_status !== 'publish' && $post->post_status !== 'draft') {
            return;
        }
        
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        ?>
        <div class="fth-preview-box" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px;">
            <h3 style="margin: 0 0 10px; color: <?php echo esc_attr($primary_color); ?>;">
                <span class="dashicons dashicons-visibility"></span> Card Preview
            </h3>
            <p style="margin: 0 0 15px; color: #666; font-size: 12px;">
                This is how your <?php echo $post->post_type === 'travel_activity' ? 'activity' : 'destination'; ?> card will appear on the frontend.
            </p>
            
            <div id="fth-card-preview" style="max-width: 350px;">
                <?php
                if ($post->post_type === 'travel_activity') {
                    echo FTH_Templates::get_activity_card($post->ID);
                } else {
                    // Destination preview
                    $external_image = get_post_meta($post->ID, '_fth_external_image', true);
                    $hero_subtitle = get_post_meta($post->ID, '_fth_hero_subtitle', true);
                    $image_url = '';
                    
                    if (has_post_thumbnail($post->ID)) {
                        $image_url = get_the_post_thumbnail_url($post->ID, 'medium_large');
                    } elseif ($external_image) {
                        $image_url = $external_image;
                    }
                    ?>
                    <div style="position: relative; height: 200px; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #1a1a2e, #16213e);">
                        <?php if ($image_url) : ?>
                            <div style="position: absolute; inset: 0; background-image: url('<?php echo esc_url($image_url); ?>'); background-size: cover; background-position: center;"></div>
                        <?php endif; ?>
                        <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);"></div>
                        <div style="position: absolute; bottom: 20px; left: 20px; color: white;">
                            <h3 style="margin: 0; font-size: 24px; font-weight: 700;"><?php echo esc_html($post->post_title); ?></h3>
                            <?php if ($hero_subtitle) : ?>
                                <p style="margin: 5px 0 0; font-size: 14px; opacity: 0.9;"><?php echo esc_html($hero_subtitle); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <p style="margin: 15px 0 0; font-size: 11px; color: #999;">
                Preview updates when you save the post.
            </p>
        </div>
        <?php
    }
}
