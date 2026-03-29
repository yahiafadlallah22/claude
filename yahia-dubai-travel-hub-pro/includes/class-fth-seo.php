<?php
/**
 * SEO Integration - AIO SEO Compatible
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_SEO {
    
    /**
     * Initialize SEO
     */
    public static function init() {
        // Document title
        add_filter('document_title_parts', array(__CLASS__, 'modify_document_title'), 10, 1);
        
        // Meta description
        add_action('wp_head', array(__CLASS__, 'output_meta_tags'), 1);
        
        // AIO SEO support
        add_filter('aioseo_title', array(__CLASS__, 'aioseo_title'), 10, 1);
        add_filter('aioseo_description', array(__CLASS__, 'aioseo_description'), 10, 1);
        
        // Open Graph
        add_filter('aioseo_facebook_tags', array(__CLASS__, 'aioseo_facebook_tags'), 10, 1);
        add_filter('aioseo_twitter_tags', array(__CLASS__, 'aioseo_twitter_tags'), 10, 1);
        
        // Schema
        add_filter('aioseo_schema_output', array(__CLASS__, 'aioseo_schema'), 10, 1);
        
        // Breadcrumbs
        add_filter('aioseo_breadcrumbs_trail', array(__CLASS__, 'aioseo_breadcrumbs'), 10, 1);
    }
    
    /**
     * Modify document title
     */
    public static function modify_document_title($title) {
        if (is_singular('travel_activity')) {
            $post_id = get_the_ID();
            $activity_title = get_the_title($post_id);
            $cities = wp_get_post_terms($post_id, 'travel_city');
            $city_name = !empty($cities) ? $cities[0]->name : '';
            
            if ($city_name) {
                $title['title'] = $activity_title . ' in ' . $city_name . ' | Book Online';
            } else {
                $title['title'] = $activity_title . ' | Book Online';
            }
        }
        
        if (is_singular('travel_destination')) {
            $title['title'] = 'Things to Do in ' . get_the_title() . ' | Tours, Attractions & Experiences';
        }
        
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            $title['title'] = 'Things to Do in ' . $term->name . ' | Tours, Attractions & Experiences';
        }
        
        if (is_tax('travel_country')) {
            $term = get_queried_object();
            $title['title'] = 'Things to Do in ' . $term->name . ' | Top Destinations & Activities';
        }
        
        if (is_tax('travel_category')) {
            $term = get_queried_object();
            $title['title'] = $term->name . ' Tours & Activities | Book Online';
        }
        
        if (is_post_type_archive('travel_activity')) {
            $title['title'] = 'Things to Do | Tours, Attractions & Experiences';
        }
        
        return $title;
    }
    
    /**
     * Output meta tags
     */
    public static function output_meta_tags() {
        // Skip if AIO SEO is handling this
        if (defined('AIOSEO_VERSION')) {
            return;
        }
        
        $description = self::get_meta_description();
        
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }
    
    /**
     * Get meta description
     */
    public static function get_meta_description() {
        $description = '';
        
        if (is_singular('travel_activity')) {
            $post_id = get_the_ID();
            $activity_title = get_the_title($post_id);
            $cities = wp_get_post_terms($post_id, 'travel_city');
            $city_name = !empty($cities) ? $cities[0]->name : '';
            
            $excerpt = get_the_excerpt($post_id);
            if ($excerpt) {
                $description = wp_trim_words($excerpt, 25, '...');
            } else {
                $description = 'Book ' . $activity_title;
                if ($city_name) {
                    $description .= ' in ' . $city_name;
                }
                $description .= '. Read reviews, see highlights, and book securely online.';
            }
        }
        
        if (is_singular('travel_destination')) {
            $title = get_the_title();
            $seo_intro = get_post_meta(get_the_ID(), '_fth_seo_intro', true);
            
            if ($seo_intro) {
                $description = wp_trim_words($seo_intro, 25, '...');
            } else {
                $description = 'Discover the best things to do in ' . $title . '. Find top attractions, tours, activities, and experiences. Book online and save!';
            }
        }
        
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            $country = get_term_meta($term->term_id, 'fth_parent_country', true);
            $country_name = '';
            
            if ($country) {
                $country_term = get_term($country, 'travel_country');
                if ($country_term && !is_wp_error($country_term)) {
                    $country_name = $country_term->name;
                }
            }
            
            $description = 'Discover the best things to do in ' . $term->name;
            if ($country_name) {
                $description .= ', ' . $country_name;
            }
            $description .= '. From iconic attractions and desert safaris to family activities and unforgettable experiences.';
        }
        
        if (is_tax('travel_country')) {
            $term = get_queried_object();
            $description = 'Explore amazing things to do in ' . $term->name . '. Find top destinations, activities, tours, and experiences across the country.';
        }
        
        if (is_tax('travel_category')) {
            $term = get_queried_object();
            $description = 'Browse ' . strtolower($term->name) . ' tours and activities. Find the best experiences, read reviews, and book online with confidence.';
        }
        
        if (is_post_type_archive('travel_activity')) {
            $description = 'Discover amazing things to do around the world. Browse tours, attractions, activities, and experiences. Book online and save!';
        }
        
        return $description;
    }
    
    /**
     * AIO SEO title filter
     */
    public static function aioseo_title($title) {
        $custom_title = self::get_custom_title();
        return $custom_title ?: $title;
    }
    
    /**
     * AIO SEO description filter
     */
    public static function aioseo_description($description) {
        $custom_desc = self::get_meta_description();
        return $custom_desc ?: $description;
    }
    
    /**
     * Get custom title
     */
    public static function get_custom_title() {
        if (is_singular('travel_activity')) {
            $post_id = get_the_ID();
            $activity_title = get_the_title($post_id);
            $cities = wp_get_post_terms($post_id, 'travel_city');
            $city_name = !empty($cities) ? $cities[0]->name : '';
            
            if ($city_name) {
                return $activity_title . ' in ' . $city_name . ' | Book Online';
            }
            return $activity_title . ' | Book Online';
        }
        
        if (is_singular('travel_destination')) {
            return 'Things to Do in ' . get_the_title() . ' | Tours, Attractions & Experiences';
        }
        
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            return 'Things to Do in ' . $term->name . ' | Tours, Attractions & Experiences';
        }
        
        if (is_tax('travel_country')) {
            $term = get_queried_object();
            return 'Things to Do in ' . $term->name . ' | Top Destinations & Activities';
        }
        
        if (is_tax('travel_category')) {
            $term = get_queried_object();
            return $term->name . ' Tours & Activities | Book Online';
        }
        
        return '';
    }
    
    /**
     * AIO SEO Facebook tags
     */
    public static function aioseo_facebook_tags($tags) {
        if (is_singular('travel_activity') || is_singular('travel_destination')) {
            $post_id = get_the_ID();
            $external_image = get_post_meta($post_id, '_fth_external_image', true);
            
            if ($external_image) {
                $tags['og:image'] = $external_image;
            }
        }
        
        return $tags;
    }
    
    /**
     * AIO SEO Twitter tags
     */
    public static function aioseo_twitter_tags($tags) {
        if (is_singular('travel_activity') || is_singular('travel_destination')) {
            $post_id = get_the_ID();
            $external_image = get_post_meta($post_id, '_fth_external_image', true);
            
            if ($external_image) {
                $tags['twitter:image'] = $external_image;
            }
        }
        
        return $tags;
    }
    
    /**
     * AIO SEO Schema
     */
    public static function aioseo_schema($schema) {
        if (is_singular('travel_activity')) {
            $post_id = get_the_ID();
            $rating = get_post_meta($post_id, '_fth_rating', true);
            $review_count = get_post_meta($post_id, '_fth_review_count', true);
            $price = get_post_meta($post_id, '_fth_price', true);
            $currency = get_post_meta($post_id, '_fth_currency', true) ?: 'USD';
            
            // Add TouristAttraction schema
            $attraction_schema = array(
                '@type'       => 'TouristAttraction',
                'name'        => get_the_title($post_id),
                'description' => get_the_excerpt($post_id),
                'url'         => get_permalink($post_id),
            );
            
            if ($rating && $review_count) {
                $attraction_schema['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => $rating,
                    'reviewCount' => $review_count,
                    'bestRating'  => '5',
                    'worstRating' => '1',
                );
            }
            
            if ($price) {
                $attraction_schema['offers'] = array(
                    '@type'         => 'Offer',
                    'price'         => $price,
                    'priceCurrency' => $currency,
                    'availability'  => 'https://schema.org/InStock',
                );
            }
            
            $external_image = get_post_meta($post_id, '_fth_external_image', true);
            if ($external_image) {
                $attraction_schema['image'] = $external_image;
            } elseif (has_post_thumbnail($post_id)) {
                $attraction_schema['image'] = get_the_post_thumbnail_url($post_id, 'large');
            }
            
            $schema[] = $attraction_schema;
        }
        
        return $schema;
    }
    
    /**
     * AIO SEO Breadcrumbs
     */
    public static function aioseo_breadcrumbs($trail) {
        if (is_singular('travel_activity')) {
            $post_id = get_the_ID();
            $cities = wp_get_post_terms($post_id, 'travel_city');
            
            if (!empty($cities)) {
                $city = $cities[0];
                
                // Insert city before current item
                $current = array_pop($trail);
                
                $trail[] = array(
                    'label' => 'Things to Do',
                    'link'  => home_url('/things-to-do/'),
                );
                
                $trail[] = array(
                    'label' => $city->name,
                    'link'  => get_term_link($city),
                );
                
                $trail[] = $current;
            }
        }
        
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            
            $trail[] = array(
                'label' => 'Things to Do',
                'link'  => home_url('/things-to-do/'),
            );
            
            $trail[] = array(
                'label' => $term->name,
                'link'  => '',
            );
        }
        
        return $trail;
    }
    
    /**
     * Generate SEO-friendly breadcrumbs HTML
     */
    public static function get_breadcrumbs() {
        $primary_color = Flavor_Travel_Hub::get_primary_color();
        $breadcrumbs = array();
        
        // Home
        $breadcrumbs[] = array(
            'label' => 'Home',
            'link'  => home_url('/'),
        );
        
        // Things to Do
        $breadcrumbs[] = array(
            'label' => 'Things to Do',
            'link'  => home_url('/things-to-do/'),
        );
        
        if (is_singular('travel_activity')) {
            $post_id = get_the_ID();
            $cities = wp_get_post_terms($post_id, 'travel_city');
            
            if (!empty($cities)) {
                $breadcrumbs[] = array(
                    'label' => $cities[0]->name,
                    'link'  => get_term_link($cities[0]),
                );
            }
            
            $breadcrumbs[] = array(
                'label' => get_the_title($post_id),
                'link'  => '',
            );
        }
        
        if (is_singular('travel_destination')) {
            $breadcrumbs[] = array(
                'label' => get_the_title(),
                'link'  => '',
            );
        }
        
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            $breadcrumbs[] = array(
                'label' => $term->name,
                'link'  => '',
            );
        }
        
        if (is_tax('travel_country')) {
            $term = get_queried_object();
            $breadcrumbs[] = array(
                'label' => $term->name,
                'link'  => '',
            );
        }
        
        if (is_tax('travel_category')) {
            $term = get_queried_object();
            $breadcrumbs[] = array(
                'label' => $term->name,
                'link'  => '',
            );
        }
        
        ob_start();
        ?>
        <nav class="fth-breadcrumbs" aria-label="Breadcrumb">
            <ol class="fth-breadcrumbs-list" itemscope itemtype="https://schema.org/BreadcrumbList">
                <?php foreach ($breadcrumbs as $index => $crumb) : ?>
                    <li class="fth-breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <?php if ($crumb['link']) : ?>
                            <a href="<?php echo esc_url($crumb['link']); ?>" itemprop="item" style="color: <?php echo esc_attr($primary_color); ?>;">
                                <span itemprop="name"><?php echo esc_html($crumb['label']); ?></span>
                            </a>
                        <?php else : ?>
                            <span itemprop="name"><?php echo esc_html($crumb['label']); ?></span>
                        <?php endif; ?>
                        <meta itemprop="position" content="<?php echo esc_attr($index + 1); ?>">
                        <?php if ($crumb['link']) : ?>
                            <span class="fth-breadcrumb-separator">/</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php
        return ob_get_clean();
    }
}
