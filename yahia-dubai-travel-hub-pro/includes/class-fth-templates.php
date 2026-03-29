<?php
/**
 * Custom Templates Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Templates {
    
    /**
     * Initialize templates
     */
    public static function init() {
        add_filter('template_include', array(__CLASS__, 'template_loader'), 99);
        add_filter('single_template', array(__CLASS__, 'single_template'), 10, 3);
        add_filter('archive_template', array(__CLASS__, 'archive_template'), 10, 3);
        add_filter('taxonomy_template', array(__CLASS__, 'taxonomy_template'), 10, 3);
        add_action('template_redirect', array(__CLASS__, 'maybe_route_travel_search'), 1);
        
        // Flush rewrite rules on init if needed
        add_action('init', array(__CLASS__, 'maybe_flush_rewrite_rules'), 20);
    }
    
    /**
     * Flush rewrite rules if needed (first load after activation)
     */
    public static function maybe_flush_rewrite_rules() {
        if (get_option('fth_needs_flush')) {
            flush_rewrite_rules();
            delete_option('fth_needs_flush');
        }
    }
    
    /**
     * Template loader
     */
    public static function template_loader($template) {
        // Main Hub Page (/things-to-do/)
        if (is_page('things-to-do') || (is_page() && get_page_by_path('things-to-do') && get_the_ID() === get_page_by_path('things-to-do')->ID)) {
            $custom = FTH_PLUGIN_DIR . 'templates/page-things-to-do.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        // Hotels Hub Page (/hotels/)
        if (is_page('hotels') || (is_page() && get_page_by_path('hotels') && get_the_ID() === get_page_by_path('hotels')->ID)) {
            $custom = FTH_PLUGIN_DIR . 'templates/page-hotels.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }

        // Travel Activity Single
        if (is_singular('travel_activity')) {
            $custom = FTH_PLUGIN_DIR . 'templates/single-travel-activity.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        // Travel Destination Single
        if (is_singular('travel_destination')) {
            $custom = FTH_PLUGIN_DIR . 'templates/single-travel-destination.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        // Travel Hotel Single
        if (is_singular('travel_hotel')) {
            $custom = FTH_PLUGIN_DIR . 'templates/single-travel-hotel.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        // Archive pages
        if (is_post_type_archive('travel_activity')) {
            $custom = FTH_PLUGIN_DIR . 'templates/archive-travel-activity.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        if (is_post_type_archive('travel_destination')) {
            $custom = FTH_PLUGIN_DIR . 'templates/archive-travel-destination.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        // Taxonomy pages
        if (is_tax('travel_city')) {
            $custom = FTH_PLUGIN_DIR . 'templates/taxonomy-travel-city.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        if (is_tax('travel_country')) {
            $custom = FTH_PLUGIN_DIR . 'templates/taxonomy-travel-country.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        if (is_tax('travel_category')) {
            $custom = FTH_PLUGIN_DIR . 'templates/taxonomy-travel-category.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
        
        return $template;
    }
    

    /**
     * Get a reliable hub URL by slug
     */
    public static function get_hub_url($slug = 'things-to-do') {
        $page = get_page_by_path($slug);
        if ($page && !is_wp_error($page)) {
            return get_permalink($page);
        }
        return home_url('/' . trim($slug, '/') . '/');
    }


    /**
     * Keep travel searches on the plugin hubs instead of WP Residence search routes.
     */
    public static function maybe_route_travel_search() {
        if (is_admin()) {
            return;
        }
        // Don't redirect when already on a taxonomy/term page — it handles its own search
        if (is_tax('travel_city') || is_tax('travel_country') || is_tax('travel_category')) {
            return;
        }
        $has_travel_query = isset($_GET['fth_search']) || isset($_GET['fth_city']) || isset($_GET['fth_country']) || isset($_GET['fth_category']);
        if (!$has_travel_query) {
            return;
        }
        $mode = isset($_GET['fth_mode']) && sanitize_text_field(wp_unslash($_GET['fth_mode'])) === 'hotels' ? 'hotels' : 'activities';
        $hub_slug = $mode === 'hotels' ? 'hotels' : 'things-to-do';
        $hub_url = self::get_hub_url($hub_slug);
        $current_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request ?? ''));
        $hub_page = get_page_by_path($hub_slug);
        if ($hub_page && is_page($hub_page->ID)) {
            return;
        }
        $args = array();
        foreach (array('fth_search','fth_city','fth_country','fth_category','fth_mode') as $key) {
            if (isset($_GET[$key]) && $_GET[$key] !== '') {
                $args[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
            }
        }
        if (empty($args['fth_mode'])) {
            $args['fth_mode'] = $mode === 'hotels' ? 'hotels' : 'activities';
        }
        $target = add_query_arg($args, $hub_url);
        $req_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        if ($req_uri && strpos($req_uri, '/' . trim($hub_slug,'/') . '/') !== false) {
            return;
        }
        wp_safe_redirect($target, 302);
        exit;
    }

    /**
     * Build a mode-specific browse URL for footer and hub links.
     */
    public static function get_browse_url($mode = 'activities', $country_slug = '', $city_slug = '') {
        $hub_slug = $mode === 'hotels' ? 'hotels' : 'things-to-do';
        $url = self::get_hub_url($hub_slug);
        $args = array('fth_mode' => $mode === 'hotels' ? 'hotels' : 'activities');
        if ($country_slug) {
            $args['fth_country'] = $country_slug;
        }
        if ($city_slug) {
            $args['fth_city'] = $city_slug;
        }
        return add_query_arg($args, $url);
    }

    /**
     * Single template
     */
    public static function single_template($template, $type, $templates) {
        return $template;
    }
    
    /**
     * Archive template
     */
    public static function archive_template($template, $type, $templates) {
        return $template;
    }
    
    /**
     * Taxonomy template
     */
    public static function taxonomy_template($template, $type, $templates) {
        return $template;
    }
    
    /**
     * Get template part
     */
    public static function get_template_part($slug, $name = null, $args = array()) {
        $templates = array();
        
        if ($name) {
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";
        
        foreach ($templates as $template) {
            $located = FTH_PLUGIN_DIR . 'templates/' . $template;
            if (file_exists($located)) {
                if (!empty($args)) {
                    extract($args);
                }
                include $located;
                return;
            }
        }
    }
    
    /**
     * Get activity card HTML
     */

    public static function get_activity_card($post_id, $args = array()) {
        $defaults = array(
            'show_rating'   => true,
            'show_price'    => true,
            'show_category' => true,
            'show_city'     => true,
            'card_class'    => '',
        );
        $args = wp_parse_args($args, $defaults);
        $title           = get_the_title($post_id);
        $permalink       = get_permalink($post_id);
        $price           = get_post_meta($post_id, '_fth_price', true);
        $original_price  = get_post_meta($post_id, '_fth_original_price', true);
        $currency        = get_post_meta($post_id, '_fth_currency', true) ?: 'USD';
        $rating          = get_post_meta($post_id, '_fth_rating', true);
        $review_count    = get_post_meta($post_id, '_fth_review_count', true);
        $external_image  = get_post_meta($post_id, '_fth_external_image', true);
        $affiliate_link  = get_post_meta($post_id, '_fth_affiliate_link', true);
        $image_url       = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'medium_large') : $external_image;
        if (!$image_url) { $image_url = 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=800&q=80'; }
        $image_url       = Flavor_Travel_Hub::fth_img_url($image_url);
        $cities          = wp_get_post_terms($post_id, 'travel_city');
        $city_name       = !empty($cities) ? $cities[0]->name : '';
        $categories      = wp_get_post_terms($post_id, 'travel_category');
        $category_name   = !empty($categories) ? $categories[0]->name : '';
        $currency_symbols = array('USD' => '$','AED' => 'AED ','EUR' => '€','GBP' => '£','SAR' => 'SAR ','QAR' => 'QAR ');
        $symbol          = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : $currency . ' ';
        $primary_color   = Flavor_Travel_Hub::get_primary_color();
        $has_discount    = $original_price && (float) $original_price > (float) $price && (float) $price > 0;
        $discount_pct    = $has_discount ? round((1 - (float)$price / (float)$original_price) * 100) : 0;
        $link            = $affiliate_link ?: $permalink;
        ob_start(); ?>
        <article class="fth-lux-card" itemscope itemtype="https://schema.org/TouristAttraction">
            <a href="<?php echo esc_url($permalink); ?>" class="fth-lux-img-wrap" aria-label="<?php echo esc_attr($title); ?>">
                <span class="fth-lux-img" style="background-image:url('<?php echo esc_url($image_url); ?>');"></span>
                <span class="fth-lux-img-overlay"></span>
                <?php if ($has_discount && $discount_pct >= 5): ?>
                <span class="fth-lux-badge fth-lux-badge-deal">-<?php echo (int)$discount_pct; ?>%</span>
                <?php elseif ($args['show_category'] && $category_name): ?>
                <span class="fth-lux-badge"><?php echo esc_html($category_name); ?></span>
                <?php endif; ?>
                <?php if ($args['show_rating'] && $rating): ?>
                <span class="fth-lux-rating-pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="#FFD700"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <?php echo esc_html(number_format((float)$rating, 1)); ?>
                    <?php if ($review_count): ?><span>(<?php echo esc_html(number_format((int)$review_count)); ?>)</span><?php endif; ?>
                </span>
                <?php endif; ?>
            </a>
            <div class="fth-lux-body">
                <?php if ($args['show_city'] && $city_name): ?>
                <div class="fth-lux-location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span itemprop="address"><?php echo esc_html($city_name); ?></span>
                </div>
                <?php endif; ?>
                <h3 class="fth-lux-title" itemprop="name"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
                <div class="fth-lux-footer">
                    <div class="fth-lux-price">
                        <?php if ($has_discount): ?>
                        <s class="fth-lux-price-old"><?php echo esc_html($symbol . number_format((float)$original_price, 0)); ?></s>
                        <?php endif; ?>
                        <?php if ($args['show_price'] && $price): ?>
                        <div class="fth-lux-price-now">
                            <small>From</small>
                            <strong><?php echo esc_html($symbol . number_format((float)$price, 0)); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo esc_url($link); ?>" class="fth-lux-btn" style="background:<?php echo esc_attr($primary_color); ?>;" <?php echo $affiliate_link ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                        Book Now
                    </a>
                </div>
            </div>
        </article>
        <?php return ob_get_clean();
    }

    
    /**
     * Get city card HTML
     */
    public static function get_city_card($term, $args = array()) {
        $defaults = array(
            'show_count' => true,
            'card_class' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $hero_image = get_term_meta($term->term_id, 'fth_hero_image', true);
        $deeplink = get_term_meta($term->term_id, 'fth_deeplink', true);
        
        if (!$hero_image) {
            $hero_image = FTH_PLUGIN_URL . 'assets/images/city-placeholder.jpg';
        }
        $hero_image = Flavor_Travel_Hub::fth_img_url($hero_image);

        $link = get_term_link($term);
        $activity_count = self::get_city_activity_count($term->term_id);
        
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        
        ob_start();
        ?>
        <a href="<?php echo esc_url($link); ?>" class="fth-city-card <?php echo esc_attr($args['card_class']); ?>">
            <div class="fth-city-image" style="background-image: url('<?php echo esc_url($hero_image); ?>');">
                <div class="fth-city-overlay"></div>
                <div class="fth-city-content">
                    <h3 class="fth-city-name"><?php echo esc_html($term->name); ?></h3>
                    <?php if ($args['show_count'] && $activity_count > 0) : ?>
                        <span class="fth-city-count"><?php echo esc_html($activity_count); ?> activities</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get hotel card HTML
     */

    public static function get_hotel_card($post_id, $args = array()) {
        $title          = get_the_title($post_id);
        $permalink      = get_permalink($post_id);
        $price          = get_post_meta($post_id, '_fth_price', true);
        $original_price = get_post_meta($post_id, '_fth_original_price', true);
        $currency       = get_post_meta($post_id, '_fth_currency', true) ?: 'USD';
        $rating         = get_post_meta($post_id, '_fth_rating', true);
        $review_count   = get_post_meta($post_id, '_fth_review_count', true);
        $star_rating    = get_post_meta($post_id, '_fth_star_rating', true);
        $external_image = get_post_meta($post_id, '_fth_external_image', true);
        $affiliate_link = get_post_meta($post_id, '_fth_affiliate_link', true);
        $cities         = wp_get_post_terms($post_id, 'travel_city');
        $city_name      = !empty($cities) ? $cities[0]->name : '';
        $primary_color  = Flavor_Travel_Hub::get_primary_color();
        $currency_symbols = array('USD' => '$','AED' => 'AED ','EUR' => '€','GBP' => '£','SAR' => 'SAR ','QAR' => 'QAR ');
        $symbol         = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : $currency . ' ';
        $image_url      = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'medium_large') : $external_image;
        if (!$image_url) { $image_url = 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800&q=80'; }
        $image_url      = Flavor_Travel_Hub::fth_img_url($image_url);
        $has_discount   = $original_price && (float) $original_price > (float) $price && (float) $price > 0;
        $discount_pct   = $has_discount ? round((1 - (float)$price / (float)$original_price) * 100) : 0;
        $link           = $affiliate_link ?: $permalink;
        ob_start(); ?>
        <article class="fth-lux-card fth-lux-card-hotel" itemscope itemtype="https://schema.org/LodgingBusiness">
            <a href="<?php echo esc_url($permalink); ?>" class="fth-lux-img-wrap" aria-label="<?php echo esc_attr($title); ?>">
                <span class="fth-lux-img" style="background-image:url('<?php echo esc_url($image_url); ?>');"></span>
                <span class="fth-lux-img-overlay"></span>
                <?php if ($has_discount && $discount_pct >= 5): ?>
                <span class="fth-lux-badge fth-lux-badge-deal">-<?php echo (int)$discount_pct; ?>%</span>
                <?php else: ?>
                <span class="fth-lux-badge">🏨 Hotel</span>
                <?php endif; ?>
                <?php if ($rating): ?>
                <span class="fth-lux-rating-pill">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="#FFD700"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    <?php echo esc_html(number_format((float)$rating, 1)); ?>
                    <?php if ($review_count): ?><span>(<?php echo esc_html(number_format((int)$review_count)); ?>)</span><?php endif; ?>
                </span>
                <?php endif; ?>
                <?php if ($star_rating): ?>
                <span class="fth-lux-stars"><?php echo str_repeat('★', (int)$star_rating); ?></span>
                <?php endif; ?>
            </a>
            <div class="fth-lux-body">
                <?php if ($city_name): ?>
                <div class="fth-lux-location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span itemprop="addressLocality"><?php echo esc_html($city_name); ?></span>
                </div>
                <?php endif; ?>
                <h3 class="fth-lux-title" itemprop="name"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
                <div class="fth-lux-footer">
                    <div class="fth-lux-price">
                        <?php if ($has_discount): ?>
                        <s class="fth-lux-price-old"><?php echo esc_html($symbol . number_format((float)$original_price, 0)); ?></s>
                        <?php endif; ?>
                        <div class="fth-lux-price-now">
                            <small><?php echo $price ? 'From' : ''; ?></small>
                            <strong><?php echo $price ? esc_html($symbol . number_format((float)$price, 0)) : 'Check rate'; ?></strong>
                        </div>
                        <?php if ($price): ?><div class="fth-lux-per-night">/night</div><?php endif; ?>
                    </div>
                    <a href="<?php echo esc_url($link); ?>" class="fth-lux-btn" style="background:<?php echo esc_attr($primary_color); ?>;" <?php echo $affiliate_link ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                        Book Now
                    </a>
                </div>
            </div>
        </article>
        <?php return ob_get_clean();
    }


    /**
     * Get activity count for city
     */
    public static function get_city_activity_count($city_id) {
        $query = new WP_Query(array(
            'post_type'      => 'travel_activity',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'travel_city',
                    'field'    => 'term_id',
                    'terms'    => $city_id,
                ),
            ),
        ));
        
        return $query->found_posts;
    }
    

    /**
     * Get hotel count for city
     */
    public static function get_city_hotel_count($city_id) {
        $query = new WP_Query(array(
            'post_type'      => 'travel_hotel',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'travel_city',
                    'field'    => 'term_id',
                    'terms'    => $city_id,
                ),
            ),
        ));
        return (int) $query->found_posts;
    }

    /**
     * Get a best-effort flag for a country term
     */
    public static function get_country_flag($country) {
        $slug = is_object($country) ? $country->slug : sanitize_title((string) $country);
        $name = is_object($country) ? strtolower($country->name) : strtolower((string) $country);
        $map = array(
            'united-arab-emirates' => '🇦🇪', 'uae' => '🇦🇪', 'dubai' => '🇦🇪',
            'saudi-arabia' => '🇸🇦', 'qatar' => '🇶🇦', 'oman' => '🇴🇲', 'bahrain' => '🇧🇭', 'kuwait' => '🇰🇼',
            'egypt' => '🇪🇬', 'morocco' => '🇲🇦', 'turkey' => '🇹🇷', 'thailand' => '🇹🇭', 'singapore' => '🇸🇬',
            'japan' => '🇯🇵', 'south-korea' => '🇰🇷', 'indonesia' => '🇮🇩', 'malaysia' => '🇲🇾', 'vietnam' => '🇻🇳',
            'united-kingdom' => '🇬🇧', 'france' => '🇫🇷', 'italy' => '🇮🇹', 'spain' => '🇪🇸', 'germany' => '🇩🇪',
            'switzerland' => '🇨🇭', 'united-states' => '🇺🇸', 'canada' => '🇨🇦', 'mexico' => '🇲🇽', 'australia' => '🇦🇺'
        );
        if (isset($map[$slug])) return $map[$slug];
        foreach ($map as $k => $v) {
            if (strpos($name, str_replace('-', ' ', $k)) !== false) return $v;
        }
        return '🌍';
    }

    /**
     * Render SEO footer with countries and cities
     */

    public static function render_seo_footer($mode = 'activities') {
        $countries = get_terms(array(
            'taxonomy'   => 'travel_country',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'number'     => 200,
        ));
        if (empty($countries) || is_wp_error($countries)) {
            return '';
        }
        $hub_mode = $mode === 'hotels' ? 'hotels' : 'activities';
        $primary = Flavor_Travel_Hub::get_primary_color();
        ob_start(); ?>
        <section class="fth-seo-footer">
            <div class="fth-seo-footer-inner">
                <div class="fth-seo-footer-head">
                    <h2><?php echo $hub_mode === 'hotels' ? esc_html__('Explore hotels by country and city', 'flavor-travel-hub') : esc_html__('Explore attractions by country and city', 'flavor-travel-hub'); ?></h2>
                    <p><?php echo $hub_mode === 'hotels' ? esc_html__('Open a destination, then compare stays city by city.', 'flavor-travel-hub') : esc_html__('Open a destination, then compare tickets, tours and attractions city by city.', 'flavor-travel-hub'); ?></p>
                </div>
                <div class="fth-seo-footer-grid">
                    <?php foreach ($countries as $country) :
                        $cities = get_terms(array(
                            'taxonomy'   => 'travel_city',
                            'hide_empty' => false,
                            'meta_query' => array(array('key' => 'fth_parent_country', 'value' => $country->term_id)),
                            'number'     => 24,
                            'orderby'    => 'name',
                            'order'      => 'ASC',
                        ));
                        $flag = get_term_meta($country->term_id, 'fth_flag_emoji', true);
                        if (!$flag) { $flag = self::get_country_flag($country); } ?>
                        <div class="fth-seo-country-card">
                            <a class="fth-seo-country-link" href="<?php echo esc_url(self::get_browse_url($hub_mode, $country->slug)); ?>">
                                <span class="fth-seo-flag"><?php echo esc_html($flag); ?></span>
                                <span><?php echo esc_html($country->name); ?></span>
                            </a>
                            <?php if (!empty($cities) && !is_wp_error($cities)) : ?>
                                <ul class="fth-seo-city-list">
                                    <?php foreach ($cities as $city) : ?>
                                        <li><a href="<?php echo esc_url(self::get_browse_url($hub_mode, $country->slug, $city->slug)); ?>"><?php echo esc_html($city->name); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="fth-seo-empty"><?php echo esc_html__('Browse this destination from the main hub.', 'flavor-travel-hub'); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <style>
                .fth-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;overflow:hidden;box-shadow:0 16px 34px rgba(15,23,42,.06);display:flex;flex-direction:column;height:100%}
                .fth-card-media{position:relative;display:block}
                .fth-card-media-img{display:block;width:100%;aspect-ratio:4/3;background-size:cover;background-position:center}
                .fth-card-tag{position:absolute;left:14px;top:14px;display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border-radius:999px;background:rgba(15,23,42,.72);color:#fff;font-size:12px;font-weight:800}
                .fth-card-body{padding:18px;display:flex;flex-direction:column;gap:8px;flex:1}
                .fth-card-place{font-size:13px;color:#64748b;text-align:center}
                .fth-card-title{margin:0;font-size:18px;line-height:1.35;text-align:center;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:48px}
                .fth-card-title a{color:#0f172a!important}
                .fth-card-rating{font-size:14px;color:#475569;text-align:center}
                .fth-card-rating span{color:#94a3b8;margin-left:4px}
                .fth-card-bottom{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-top:auto}
                .fth-card-price-box{min-height:40px}
                .fth-card-price-old{font-size:12px;color:#94a3b8;text-decoration:line-through}
                .fth-card-price-now{font-size:22px;font-weight:900;color:#0f172a}
                .fth-card-price-now span{display:block;font-size:12px;color:#94a3b8;font-weight:600}
                .fth-card-btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:12px;color:#fff!important;font-weight:800;white-space:nowrap}
                .fth-seo-footer{padding:52px 0;background:linear-gradient(180deg,#fff 0%,#f8fbff 100%);border-top:1px solid rgba(15,23,42,.08);margin-top:52px}
                .fth-seo-footer-inner{max-width:1240px;margin:0 auto;padding:0 20px}
                .fth-seo-footer-head{text-align:center;margin-bottom:24px}
                .fth-seo-footer-head h2{margin:0 0 8px;font-size:30px;line-height:1.15;color:#0f172a;text-align:center}
                .fth-seo-footer-head p{margin:0 auto;max-width:760px;color:#64748b}
                .fth-seo-footer-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
                .fth-seo-country-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:20px;padding:18px;box-shadow:0 12px 28px rgba(15,23,42,.05)}
                .fth-seo-country-link{display:flex;align-items:center;gap:10px;font-weight:800;color:#0f172a!important;margin-bottom:12px}
                .fth-seo-flag{font-size:22px}
                .fth-seo-city-list{list-style:none;margin:0;padding:0;display:grid;gap:8px}
                .fth-seo-city-list a{color:#475569!important}
                .fth-seo-city-list a:hover,.fth-seo-country-link:hover{color:<?php echo esc_attr($primary); ?>!important}
                .fth-seo-empty{margin:0;color:#64748b;font-size:14px}
                @media(max-width:1000px){.fth-seo-footer-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
                @media(max-width:720px){.fth-card-bottom{flex-direction:column;align-items:stretch}.fth-card-btn{width:100%}.fth-seo-footer-grid{grid-template-columns:1fr}}
            </style>
        </section>
        <?php return ob_get_clean();
    }


    /**
     * Get category card HTML
     */
    public static function get_category_card($term, $args = array()) {
        $icon = get_term_meta($term->term_id, 'fth_icon', true);
        $color = get_term_meta($term->term_id, 'fth_color', true) ?: Flavor_Travel_Hub::get_primary_color();
        
        $link = get_term_link($term);
        
        ob_start();
        ?>
        <a href="<?php echo esc_url($link); ?>" class="fth-category-card">
            <div class="fth-category-icon" style="background-color: <?php echo esc_attr($color); ?>20; color: <?php echo esc_attr($color); ?>;">
                <?php if ($icon) : ?>
                    <i class="fa <?php echo esc_attr($icon); ?>"></i>
                <?php else : ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                    </svg>
                <?php endif; ?>
            </div>
            <span class="fth-category-name"><?php echo esc_html($term->name); ?></span>
        </a>
        <?php
        return ob_get_clean();
    }
}
