<?php
/**
 * Shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public static function init() {
        add_shortcode('fth_travel_hub', array(__CLASS__, 'travel_hub'));
        add_shortcode('fth_search_form', array(__CLASS__, 'search_form'));
        add_shortcode('fth_featured_activities', array(__CLASS__, 'featured_activities'));
        add_shortcode('fth_featured_cities', array(__CLASS__, 'featured_cities'));
        add_shortcode('fth_categories', array(__CLASS__, 'categories'));
        add_shortcode('fth_activities_grid', array(__CLASS__, 'activities_grid'));
        add_shortcode('fth_city_activities', array(__CLASS__, 'city_activities'));
        add_shortcode('fth_activity_card', array(__CLASS__, 'activity_card'));
    }
    
    /**
     * Travel Hub main page
     */
    public static function travel_hub($atts) {
        $atts = shortcode_atts(array(
            'featured_count' => 6,
            'cities_count'   => 8,
        ), $atts);
        
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        
        // Get data
        $featured_activities = FTH_Search::get_featured_activities($atts['featured_count']);
        $cities = FTH_Taxonomies::get_cities(array('hide_empty' => true, 'number' => $atts['cities_count']));
        $categories = FTH_Taxonomies::get_categories(array('hide_empty' => false));
        
        ob_start();
        ?>
        <div class="fth-travel-hub">
            <!-- Hero Section -->
            <section class="fth-hub-hero">
                <div class="fth-hub-hero-bg"></div>
                <div class="fth-hub-hero-overlay"></div>
                <div class="fth-hub-hero-content">
                    <h1 class="fth-hub-title">Discover Amazing Experiences</h1>
                    <p class="fth-hub-subtitle">Find and book the best tours, activities, and attractions worldwide</p>
                    
                    <?php echo FTH_Search::get_search_form(array('form_class' => 'fth-hub-search')); ?>
                </div>
            </section>
            
            <!-- Featured Cities -->
            <?php if (!empty($cities)) : ?>
            <section class="fth-hub-section">
                <div class="fth-hub-container">
                    <div class="fth-section-header">
                        <h2 class="fth-section-title">Popular Destinations</h2>
                        <p class="fth-section-subtitle">Explore things to do in top destinations around the world</p>
                    </div>
                    
                    <div class="fth-cities-grid">
                        <?php foreach ($cities as $city) : ?>
                            <?php echo FTH_Templates::get_city_card($city); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Categories -->
            <?php if (!empty($categories)) : ?>
            <section class="fth-hub-section fth-hub-section-alt">
                <div class="fth-hub-container">
                    <div class="fth-section-header">
                        <h2 class="fth-section-title">Explore by Category</h2>
                        <p class="fth-section-subtitle">Find experiences that match your interests</p>
                    </div>
                    
                    <div class="fth-categories-grid">
                        <?php foreach ($categories as $category) : ?>
                            <?php echo FTH_Templates::get_category_card($category); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Featured Activities -->
            <?php if ($featured_activities->have_posts()) : ?>
            <section class="fth-hub-section">
                <div class="fth-hub-container">
                    <div class="fth-section-header">
                        <h2 class="fth-section-title">Featured Experiences</h2>
                        <p class="fth-section-subtitle">Hand-picked activities loved by travelers</p>
                    </div>
                    
                    <div class="fth-activities-grid">
                        <?php while ($featured_activities->have_posts()) : $featured_activities->the_post(); ?>
                            <?php echo FTH_Templates::get_activity_card(get_the_ID()); ?>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- CTA Section -->
            <section class="fth-hub-cta" style="background-color: <?php echo esc_attr($primary_color); ?>;">
                <div class="fth-hub-container">
                    <h2>Ready for Your Next Adventure?</h2>
                    <p>Discover popular experiences, tours and city highlights</p>
                    <a href="<?php echo esc_url(get_post_type_archive_link('travel_activity')); ?>" class="fth-cta-btn">
                        Explore All Activities
                    </a>
                </div>
            </section>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Search form shortcode
     */
    public static function search_form($atts) {
        $atts = shortcode_atts(array(
            'placeholder'   => '',
            'show_city'     => 'true',
            'show_category' => 'true',
            'city'          => '',
        ), $atts);
        
        return FTH_Search::get_search_form(array(
            'placeholder'   => $atts['placeholder'],
            'show_city'     => $atts['show_city'] === 'true',
            'show_category' => $atts['show_category'] === 'true',
            'city'          => $atts['city'],
        ));
    }
    
    /**
     * Featured activities shortcode
     */
    public static function featured_activities($atts) {
        $atts = shortcode_atts(array(
            'count'  => 6,
            'city'   => '',
            'title'  => 'Featured Activities',
        ), $atts);
        
        $activities = FTH_Search::get_featured_activities($atts['count'], $atts['city']);
        
        if (!$activities->have_posts()) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="fth-shortcode-section">
            <?php if ($atts['title']) : ?>
                <h2 class="fth-shortcode-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="fth-activities-grid">
                <?php while ($activities->have_posts()) : $activities->the_post(); ?>
                    <?php echo FTH_Templates::get_activity_card(get_the_ID()); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Featured cities shortcode
     */
    public static function featured_cities($atts) {
        $atts = shortcode_atts(array(
            'count' => 6,
            'title' => 'Popular Destinations',
        ), $atts);
        
        $cities = FTH_Taxonomies::get_cities(array(
            'hide_empty' => true,
            'number'     => $atts['count'],
        ));
        
        if (empty($cities)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="fth-shortcode-section">
            <?php if ($atts['title']) : ?>
                <h2 class="fth-shortcode-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="fth-cities-grid">
                <?php foreach ($cities as $city) : ?>
                    <?php echo FTH_Templates::get_city_card($city); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Categories shortcode
     */
    public static function categories($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Explore by Category',
        ), $atts);
        
        $categories = FTH_Taxonomies::get_categories(array('hide_empty' => false));
        
        if (empty($categories)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="fth-shortcode-section">
            <?php if ($atts['title']) : ?>
                <h2 class="fth-shortcode-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="fth-categories-grid">
                <?php foreach ($categories as $category) : ?>
                    <?php echo FTH_Templates::get_category_card($category); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Activities grid shortcode
     */
    public static function activities_grid($atts) {
        $atts = shortcode_atts(array(
            'count'    => 12,
            'city'     => '',
            'country'  => '',
            'category' => '',
            'type'     => '',
            'orderby'  => 'date',
            'order'    => 'DESC',
            'title'    => '',
        ), $atts);
        
        $activities = FTH_Search::search_activities(array(
            'city'     => $atts['city'],
            'country'  => $atts['country'],
            'category' => $atts['category'],
            'type'     => $atts['type'],
            'per_page' => $atts['count'],
            'orderby'  => $atts['orderby'],
            'order'    => $atts['order'],
        ));
        
        if (!$activities->have_posts()) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="fth-shortcode-section">
            <?php if ($atts['title']) : ?>
                <h2 class="fth-shortcode-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="fth-activities-grid">
                <?php while ($activities->have_posts()) : $activities->the_post(); ?>
                    <?php echo FTH_Templates::get_activity_card(get_the_ID()); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * City activities shortcode
     */
    public static function city_activities($atts) {
        $atts = shortcode_atts(array(
            'city'  => '',
            'count' => 6,
            'title' => '',
        ), $atts);
        
        if (empty($atts['city'])) {
            return '';
        }
        
        $activities = FTH_Search::get_activities_by_city($atts['city'], $atts['count']);
        
        if (!$activities->have_posts()) {
            return '';
        }
        
        // Get city name for title
        $city_term = get_term_by('slug', $atts['city'], 'travel_city');
        $city_name = $city_term ? $city_term->name : $atts['city'];
        
        $title = $atts['title'] ?: 'Things to Do in ' . $city_name;
        
        ob_start();
        ?>
        <div class="fth-shortcode-section">
            <h2 class="fth-shortcode-title"><?php echo esc_html($title); ?></h2>
            
            <div class="fth-activities-grid">
                <?php while ($activities->have_posts()) : $activities->the_post(); ?>
                    <?php echo FTH_Templates::get_activity_card(get_the_ID()); ?>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            
            <?php if ($city_term) : ?>
                <div class="fth-shortcode-footer">
                    <a href="<?php echo esc_url(get_term_link($city_term)); ?>" class="fth-view-all-link" style="color: <?php echo esc_attr(Flavor_Travel_Hub::get_primary_color()); ?>;">
                        View all activities in <?php echo esc_html($city_name); ?> →
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Single activity card shortcode
     */
    public static function activity_card($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);
        
        if (empty($atts['id'])) {
            return '';
        }
        
        $post = get_post($atts['id']);
        
        if (!$post || $post->post_type !== 'travel_activity') {
            return '';
        }
        
        return FTH_Templates::get_activity_card($atts['id']);
    }
}
