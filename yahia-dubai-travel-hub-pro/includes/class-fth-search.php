<?php
/**
 * Search Functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Search {
    
    /**
     * Initialize search
     */
    public static function init() {
        add_action('pre_get_posts', array(__CLASS__, 'modify_search_query'));
    }
    
    /**
     * Modify search query
     */
    public static function modify_search_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            // Custom search handling
            if (isset($_GET['fth_search'])) {
                $mode = isset($_GET['fth_mode']) && sanitize_text_field(wp_unslash($_GET['fth_mode'])) === 'hotels' ? 'travel_hotel' : 'travel_activity';
                $query->set('post_type', $mode);
                $query->set('s', sanitize_text_field($_GET['fth_search']));
                
                // City filter
                if (!empty($_GET['fth_city'])) {
                    $tax_query = $query->get('tax_query') ?: array();
                    $tax_query[] = array(
                        'taxonomy' => 'travel_city',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($_GET['fth_city']),
                    );
                    $query->set('tax_query', $tax_query);
                }
                
                // Country filter
                if (!empty($_GET['fth_country'])) {
                    $tax_query = $query->get('tax_query') ?: array();
                    $tax_query[] = array(
                        'taxonomy' => 'travel_country',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($_GET['fth_country']),
                    );
                    $query->set('tax_query', $tax_query);
                }
                
                // Category filter
                if (!empty($_GET['fth_category'])) {
                    $tax_query = $query->get('tax_query') ?: array();
                    $tax_query[] = array(
                        'taxonomy' => 'travel_category',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($_GET['fth_category']),
                    );
                    $query->set('tax_query', $tax_query);
                }
                
                // Type filter
                if (!empty($_GET['fth_type'])) {
                    $tax_query = $query->get('tax_query') ?: array();
                    $tax_query[] = array(
                        'taxonomy' => 'travel_type',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($_GET['fth_type']),
                    );
                    $query->set('tax_query', $tax_query);
                }
            }
        }
    }
    
    /**
     * Get search form HTML
     */
    public static function get_search_form($args = array()) {
        $defaults = array(
            'placeholder'  => get_option('fth_search_placeholder', 'Search activities, tours, attractions...'),
            'show_city'    => true,
            'show_category' => true,
            'city'         => '',
            'form_class'   => '',
            'action'       => home_url('/things-to-do/'),
        );
        
        $args = wp_parse_args($args, $defaults);
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        
        // Get cities
        $cities = FTH_Taxonomies::get_cities(array('hide_empty' => true));
        
        // Get categories
        $categories = FTH_Taxonomies::get_categories(array('hide_empty' => true));
        
        ob_start();
        ?>
        <form class="fth-search-form <?php echo esc_attr($args['form_class']); ?>" method="get" action="<?php echo esc_url($args['action']); ?>">
            <div class="fth-search-wrapper">
                <div class="fth-search-input-group">
                    <svg class="fth-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
                    </svg>
                    <input type="text" name="fth_search" class="fth-search-input" placeholder="<?php echo esc_attr($args['placeholder']); ?>" value="<?php echo esc_attr(isset($_GET['fth_search']) ? $_GET['fth_search'] : ''); ?>">
                </div>
                
                <?php if ($args['show_city'] && !$args['city']) : ?>
                    <div class="fth-search-select-group">
                        <select name="fth_city" class="fth-search-select">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $city) : ?>
                                <option value="<?php echo esc_attr($city->slug); ?>" <?php selected(isset($_GET['fth_city']) ? $_GET['fth_city'] : '', $city->slug); ?>>
                                    <?php echo esc_html($city->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($args['city']) : ?>
                    <input type="hidden" name="fth_city" value="<?php echo esc_attr($args['city']); ?>">
                <?php endif; ?>
                
                <?php if ($args['show_category']) : ?>
                    <div class="fth-search-select-group">
                        <select name="fth_category" class="fth-search-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected(isset($_GET['fth_category']) ? $_GET['fth_category'] : '', $cat->slug); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="fth-search-btn" style="background-color: <?php echo esc_attr($primary_color); ?>;">
                    <span>Search</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
                    </svg>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Perform activity search
     */
    public static function search_activities($args = array()) {
        $defaults = array(
            'keyword'      => '',
            'city'         => '',
            'country'      => '',
            'category'     => '',
            'type'         => '',
            'featured'     => false,
            'bestseller'   => false,
            'per_page'     => get_option('fth_items_per_page', 12),
            'paged'        => 1,
            'orderby'      => 'date',
            'order'        => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query_args = array(
            'post_type'      => 'travel_activity',
            'post_status'    => 'publish',
            'posts_per_page' => $args['per_page'],
            'paged'          => $args['paged'],
            'orderby'        => $args['orderby'],
            'order'          => $args['order'],
        );
        
        // Keyword search
        if (!empty($args['keyword'])) {
            $query_args['s'] = $args['keyword'];
        }
        
        // Taxonomy filters
        $tax_query = array('relation' => 'AND');
        
        if (!empty($args['city'])) {
            $tax_query[] = array(
                'taxonomy' => 'travel_city',
                'field'    => is_numeric($args['city']) ? 'term_id' : 'slug',
                'terms'    => $args['city'],
            );
        }
        
        if (!empty($args['country'])) {
            $tax_query[] = array(
                'taxonomy' => 'travel_country',
                'field'    => is_numeric($args['country']) ? 'term_id' : 'slug',
                'terms'    => $args['country'],
            );
        }
        
        if (!empty($args['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'travel_category',
                'field'    => is_numeric($args['category']) ? 'term_id' : 'slug',
                'terms'    => $args['category'],
            );
        }
        
        if (!empty($args['type'])) {
            $tax_query[] = array(
                'taxonomy' => 'travel_type',
                'field'    => is_numeric($args['type']) ? 'term_id' : 'slug',
                'terms'    => $args['type'],
            );
        }
        
        if (count($tax_query) > 1) {
            $query_args['tax_query'] = $tax_query;
        }
        
        // Meta filters
        $meta_query = array('relation' => 'AND');
        
        if ($args['featured']) {
            $meta_query[] = array(
                'key'   => '_fth_is_featured',
                'value' => '1',
            );
        }
        
        if ($args['bestseller']) {
            $meta_query[] = array(
                'key'   => '_fth_is_bestseller',
                'value' => '1',
            );
        }
        
        if (count($meta_query) > 1) {
            $query_args['meta_query'] = $meta_query;
        }
        
        return new WP_Query($query_args);
    }
    

    /**
     * Perform hotel search
     */
    public static function search_hotels($args = array()) {
        $defaults = array(
            'keyword'      => '',
            'city'         => '',
            'country'      => '',
            'per_page'     => get_option('fth_items_per_page', 12),
            'paged'        => 1,
            'orderby'      => 'date',
            'order'        => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $query_args = array(
            'post_type'      => 'travel_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => $args['per_page'],
            'paged'          => $args['paged'],
            'orderby'        => $args['orderby'],
            'order'          => $args['order'],
        );

        if (!empty($args['keyword'])) {
            $query_args['s'] = $args['keyword'];
        }

        $tax_query = array('relation' => 'AND');
        if (!empty($args['city'])) {
            $tax_query[] = array(
                'taxonomy' => 'travel_city',
                'field'    => is_numeric($args['city']) ? 'term_id' : 'slug',
                'terms'    => $args['city'],
            );
        }
        if (!empty($args['country'])) {
            $tax_query[] = array(
                'taxonomy' => 'travel_country',
                'field'    => is_numeric($args['country']) ? 'term_id' : 'slug',
                'terms'    => $args['country'],
            );
        }
        if (count($tax_query) > 1) {
            $query_args['tax_query'] = $tax_query;
        }
        return new WP_Query($query_args);
    }

    /**
     * Get featured activities
     */
    public static function get_featured_activities($limit = 6, $city = '') {
        $args = array(
            'featured' => true,
            'per_page' => $limit,
        );
        
        if ($city) {
            $args['city'] = $city;
        }
        
        return self::search_activities($args);
    }
    
    /**
     * Get bestseller activities
     */
    public static function get_bestseller_activities($limit = 6, $city = '') {
        $args = array(
            'bestseller' => true,
            'per_page'   => $limit,
        );
        
        if ($city) {
            $args['city'] = $city;
        }
        
        return self::search_activities($args);
    }
    
    /**
     * Get activities by city
     */
    public static function get_activities_by_city($city, $limit = 12) {
        return self::search_activities(array(
            'city'     => $city,
            'per_page' => $limit,
        ));
    }
    
    /**
     * Get activities by category
     */
    public static function get_activities_by_category($category, $limit = 12, $city = '') {
        $args = array(
            'category' => $category,
            'per_page' => $limit,
        );
        
        if ($city) {
            $args['city'] = $city;
        }
        
        return self::search_activities($args);
    }
    
    /**
     * Get related activities
     */
    public static function get_related_activities($post_id, $limit = 4) {
        $cities = wp_get_post_terms($post_id, 'travel_city', array('fields' => 'ids'));
        $categories = wp_get_post_terms($post_id, 'travel_category', array('fields' => 'ids'));
        
        $args = array(
            'post_type'      => 'travel_activity',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'post__not_in'   => array($post_id),
            'orderby'        => 'rand',
        );
        
        $tax_query = array('relation' => 'OR');
        
        if (!empty($cities)) {
            $tax_query[] = array(
                'taxonomy' => 'travel_city',
                'field'    => 'term_id',
                'terms'    => $cities,
            );
        }
        
        if (!empty($categories)) {
            $tax_query[] = array(
                'taxonomy' => 'travel_category',
                'field'    => 'term_id',
                'terms'    => $categories,
            );
        }
        
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }
        
        return new WP_Query($args);
    }
}
