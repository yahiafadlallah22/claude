<?php
/**
 * Seed Data for Initial Plugin Setup
 * With automatic SEO generation for AIO SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Seed_Data {
    
    /**
     * Seed all data
     */
    public static function seed_all() {
        self::seed_countries();
        self::seed_cities();
        self::seed_categories();
        self::seed_types();
        self::seed_sample_activities();
        self::seed_sample_destinations();
        
        // Trigger SEO generation for all seeded content
        self::trigger_seo_generation();
    }
    
    /**
     * Trigger SEO generation for all travel content
     */
    public static function trigger_seo_generation() {
        // Only run if AIO SEO is active
        if (!defined('AIOSEO_VERSION') && !class_exists('FTH_AIOSEO_Integration')) {
            return;
        }
        
        // Generate SEO for all activities
        $activities = get_posts(array(
            'post_type'      => 'travel_activity',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
        
        foreach ($activities as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                self::generate_activity_seo($post_id, $post);
            }
        }
        
        // Generate SEO for all destinations
        $destinations = get_posts(array(
            'post_type'      => 'travel_destination',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
        
        foreach ($destinations as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                self::generate_destination_seo($post_id, $post);
            }
        }
        
        // Generate SEO for all cities
        $cities = get_terms(array(
            'taxonomy'   => 'travel_city',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($cities)) {
            foreach ($cities as $city) {
                self::generate_city_seo($city->term_id);
            }
        }
        
        // Generate SEO for all countries
        $countries = get_terms(array(
            'taxonomy'   => 'travel_country',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($countries)) {
            foreach ($countries as $country) {
                self::generate_country_seo($country->term_id);
            }
        }
        
        // Generate SEO for all categories
        $categories = get_terms(array(
            'taxonomy'   => 'travel_category',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($categories)) {
            foreach ($categories as $cat) {
                self::generate_category_seo($cat->term_id);
            }
        }
    }
    
    /**
     * Generate SEO for a single activity
     */
    private static function generate_activity_seo($post_id, $post) {
        $title = $post->post_title;
        $cities = wp_get_post_terms($post_id, 'travel_city');
        $city_name = !empty($cities) ? $cities[0]->name : '';
        $countries = wp_get_post_terms($post_id, 'travel_country');
        $country_name = !empty($countries) ? $countries[0]->name : '';
        $categories = wp_get_post_terms($post_id, 'travel_category');
        $category_name = !empty($categories) ? $categories[0]->name : '';
        
        $rating = get_post_meta($post_id, '_fth_rating', true);
        $review_count = get_post_meta($post_id, '_fth_review_count', true);
        $duration = get_post_meta($post_id, '_fth_duration', true);
        
        // SEO Title
        $seo_title = $title;
        if ($city_name) {
            $seo_title .= ' in ' . $city_name;
        }
        $seo_title .= ' | Book Online';
        
        // SEO Description
        $excerpt = $post->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_trim_words(strip_tags($post->post_content), 25, '...');
        }
        
        $seo_description = $excerpt ?: ('Book ' . $title . ($city_name ? ' in ' . $city_name : '') . '. Instant confirmation & secure booking.');
        
        // Focus Keyphrase
        $focus_keyphrase = strtolower($title);
        if ($city_name) {
            $focus_keyphrase = strtolower($title . ' ' . $city_name);
        }
        
        // Store SEO data
        self::update_post_seo($post_id, array(
            'title'           => $seo_title,
            'description'     => $seo_description,
            'focus_keyphrase' => $focus_keyphrase,
            'og_title'        => $seo_title,
            'og_description'  => $seo_description,
            'twitter_title'   => $seo_title,
            'twitter_description' => $seo_description,
        ));
        
        // Keywords
        $keywords = array($title);
        if ($city_name) $keywords[] = $city_name;
        if ($country_name) $keywords[] = $country_name;
        if ($category_name) $keywords[] = $category_name;
        $keywords[] = 'tours';
        $keywords[] = 'activities';
        $keywords[] = 'book online';
        
        update_post_meta($post_id, '_fth_seo_keywords', implode(', ', $keywords));
    }
    
    /**
     * Generate SEO for a single destination
     */
    private static function generate_destination_seo($post_id, $post) {
        $title = $post->post_title;
        $countries = wp_get_post_terms($post_id, 'travel_country');
        $country_name = !empty($countries) ? $countries[0]->name : '';
        
        $seo_title = 'Things to Do in ' . $title . ' | Tours, Attractions & Experiences';
        
        $seo_description = 'Discover the best things to do in ' . $title . '. ';
        if ($country_name) {
            $seo_description .= 'Explore top attractions in ' . $country_name . '. ';
        }
        $seo_description .= 'Book tours, activities, and unique experiences. Instant confirmation & best prices.';
        
        $focus_keyphrase = 'things to do in ' . strtolower($title);
        
        self::update_post_seo($post_id, array(
            'title'           => $seo_title,
            'description'     => $seo_description,
            'focus_keyphrase' => $focus_keyphrase,
            'og_title'        => $seo_title,
            'og_description'  => $seo_description,
        ));
    }
    
    /**
     * Generate SEO for a city term
     */
    private static function generate_city_seo($term_id) {
        $term = get_term($term_id, 'travel_city');
        if (!$term || is_wp_error($term)) return;
        
        $country_id = get_term_meta($term_id, 'fth_parent_country', true);
        $country_name = '';
        if ($country_id) {
            $country = get_term($country_id, 'travel_country');
            if ($country && !is_wp_error($country)) {
                $country_name = $country->name;
            }
        }
        
        $activity_count = self::get_term_post_count($term_id, 'travel_city', 'travel_activity');
        
        $seo_title = 'Things to Do in ' . $term->name;
        if ($country_name) {
            $seo_title .= ', ' . $country_name;
        }
        $seo_title .= ' | ' . $activity_count . ' Tours & Activities';
        
        $seo_description = 'Discover ' . $activity_count . ' amazing things to do in ' . $term->name . '. ';
        if ($country_name) {
            $seo_description .= 'Top attractions in ' . $country_name . '. ';
        }
        $seo_description .= 'Book tours, activities, tickets & experiences. Best prices & instant confirmation.';
        
        $focus_keyphrase = 'things to do in ' . strtolower($term->name);
        
        self::update_term_seo($term_id, array(
            'title'           => $seo_title,
            'description'     => $seo_description,
            'focus_keyphrase' => $focus_keyphrase,
            'og_title'        => $seo_title,
            'og_description'  => $seo_description,
        ));
    }
    
    /**
     * Generate SEO for a country term
     */
    private static function generate_country_seo($term_id) {
        $term = get_term($term_id, 'travel_country');
        if (!$term || is_wp_error($term)) return;
        
        $cities = get_terms(array(
            'taxonomy'   => 'travel_city',
            'hide_empty' => false,
            'meta_query' => array(
                array('key' => 'fth_parent_country', 'value' => $term_id),
            ),
        ));
        $cities_count = is_array($cities) ? count($cities) : 0;
        
        $seo_title = 'Things to Do in ' . $term->name . ' | Top Destinations & Activities';
        $seo_description = 'Explore ' . $term->name . ' with ' . $cities_count . ' destinations. Book tours, attractions, and experiences. Best prices guaranteed.';
        $focus_keyphrase = 'things to do in ' . strtolower($term->name);
        
        self::update_term_seo($term_id, array(
            'title'           => $seo_title,
            'description'     => $seo_description,
            'focus_keyphrase' => $focus_keyphrase,
        ));
    }
    
    /**
     * Generate SEO for a category term
     */
    private static function generate_category_seo($term_id) {
        $term = get_term($term_id, 'travel_category');
        if (!$term || is_wp_error($term)) return;
        
        $seo_title = $term->name . ' Tours & Activities | Book Online';
        $seo_description = 'Browse the best ' . strtolower($term->name) . ' tours and activities. ' . $term->description . ' Instant confirmation & secure booking.';
        $focus_keyphrase = strtolower($term->name) . ' tours';
        
        self::update_term_seo($term_id, array(
            'title'           => $seo_title,
            'description'     => $seo_description,
            'focus_keyphrase' => $focus_keyphrase,
        ));
    }
    
    /**
     * Update post SEO meta
     */
    private static function update_post_seo($post_id, $data) {
        // Store as post meta for AIO SEO to pick up
        if (isset($data['title'])) {
            update_post_meta($post_id, '_aioseo_title', $data['title']);
        }
        if (isset($data['description'])) {
            update_post_meta($post_id, '_aioseo_description', $data['description']);
        }
        if (isset($data['focus_keyphrase'])) {
            update_post_meta($post_id, '_aioseo_keyphrases', json_encode(array(
                'focus' => array('keyphrase' => $data['focus_keyphrase'])
            )));
        }
        if (isset($data['og_title'])) {
            update_post_meta($post_id, '_aioseo_og_title', $data['og_title']);
        }
        if (isset($data['og_description'])) {
            update_post_meta($post_id, '_aioseo_og_description', $data['og_description']);
        }
        if (isset($data['twitter_title'])) {
            update_post_meta($post_id, '_aioseo_twitter_title', $data['twitter_title']);
        }
        if (isset($data['twitter_description'])) {
            update_post_meta($post_id, '_aioseo_twitter_description', $data['twitter_description']);
        }
        
        // Try using AIO SEO API if available
        if (class_exists('AIOSEO\Plugin\Common\Models\Post')) {
            try {
                $aioseoPost = \AIOSEO\Plugin\Common\Models\Post::getPost($post_id);
                
                if (isset($data['title'])) $aioseoPost->title = $data['title'];
                if (isset($data['description'])) $aioseoPost->description = $data['description'];
                if (isset($data['focus_keyphrase'])) {
                    $existingKeyphrases = isset($aioseoPost->keyphrases) ? $aioseoPost->keyphrases : '{}';
                    if (is_string($existingKeyphrases)) {
                        $keyphrases = json_decode($existingKeyphrases ?: '{}', true);
                    } elseif (is_object($existingKeyphrases)) {
                        $keyphrases = json_decode(wp_json_encode($existingKeyphrases), true);
                    } elseif (is_array($existingKeyphrases)) {
                        $keyphrases = $existingKeyphrases;
                    } else {
                        $keyphrases = array();
                    }
                    if (!is_array($keyphrases)) {
                        $keyphrases = array();
                    }
                    $keyphrases['focus'] = array('keyphrase' => $data['focus_keyphrase']);
                    $aioseoPost->keyphrases = wp_json_encode($keyphrases);
                }
                if (isset($data['og_title'])) $aioseoPost->og_title = $data['og_title'];
                if (isset($data['og_description'])) $aioseoPost->og_description = $data['og_description'];
                
                $aioseoPost->save();
            } catch (Exception $e) {
                // Fallback already handled via post meta
            }
        }
    }
    
    /**
     * Update term SEO meta
     */
    private static function update_term_seo($term_id, $data) {
        if (isset($data['title'])) {
            update_term_meta($term_id, '_aioseo_title', $data['title']);
        }
        if (isset($data['description'])) {
            update_term_meta($term_id, '_aioseo_description', $data['description']);
        }
        if (isset($data['focus_keyphrase'])) {
            update_term_meta($term_id, '_aioseo_focus_keyphrase', $data['focus_keyphrase']);
        }
        if (isset($data['og_title'])) {
            update_term_meta($term_id, '_aioseo_og_title', $data['og_title']);
        }
        if (isset($data['og_description'])) {
            update_term_meta($term_id, '_aioseo_og_description', $data['og_description']);
        }
    }
    
    /**
     * Get post count for a term
     */
    private static function get_term_post_count($term_id, $taxonomy, $post_type) {
        $query = new WP_Query(array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            ),
        ));
        return $query->found_posts;
    }
    
    /**
     * Seed countries
     */
    public static function seed_countries() {
        $countries = array(
            array(
                'name' => 'United Arab Emirates',
                'slug' => 'united-arab-emirates',
                'description' => 'Discover amazing attractions, tours and experiences in the United Arab Emirates.',
            ),
            array(
                'name' => 'France',
                'slug' => 'france',
                'description' => 'Explore the best of France with iconic landmarks, world-class cuisine, and unforgettable experiences.',
            ),
            array(
                'name' => 'Morocco',
                'slug' => 'morocco',
                'description' => 'Experience the magic of Morocco with its vibrant souks, stunning architecture, and rich culture.',
            ),
            array(
                'name' => 'Saudi Arabia',
                'slug' => 'saudi-arabia',
                'description' => 'Discover Saudi Arabia\'s ancient heritage, modern cities, and diverse landscapes.',
            ),
            array(
                'name' => 'Qatar',
                'slug' => 'qatar',
                'description' => 'Explore Qatar\'s blend of traditional Arabian culture and modern luxury.',
            ),
            array(
                'name' => 'Turkey',
                'slug' => 'turkey',
                'description' => 'Experience Turkey where East meets West with rich history and stunning landscapes.',
            ),
            array(
                'name' => 'Thailand',
                'slug' => 'thailand',
                'description' => 'Discover Thailand\'s temples, beaches, cuisine, and legendary hospitality.',
            ),
            array(
                'name' => 'Japan',
                'slug' => 'japan',
                'description' => 'Experience Japan\'s unique blend of ancient traditions and cutting-edge modernity.',
            ),
            array(
                'name' => 'Singapore',
                'slug' => 'singapore',
                'description' => 'Explore Singapore\'s futuristic architecture, diverse culture, and world-famous attractions.',
            ),
            array(
                'name' => 'Indonesia',
                'slug' => 'indonesia',
                'description' => 'Discover Indonesia\'s tropical paradise with stunning beaches, temples, and natural wonders.',
            ),
            array(
                'name' => 'United Kingdom',
                'slug' => 'united-kingdom',
                'description' => 'Experience the UK\'s royal heritage, historic landmarks, and vibrant cultural scene.',
            ),
            array(
                'name' => 'Italy',
                'slug' => 'italy',
                'description' => 'Explore Italy\'s art, architecture, cuisine, and timeless beauty.',
            ),
            array(
                'name' => 'Spain',
                'slug' => 'spain',
                'description' => 'Discover Spain\'s passionate culture, stunning architecture, and beautiful coastlines.',
            ),
            array(
                'name' => 'Egypt',
                'slug' => 'egypt',
                'description' => 'Experience Egypt\'s ancient wonders, from the pyramids to the Nile.',
            ),
            array(
                'name' => 'Malaysia',
                'slug' => 'malaysia',
                'description' => 'Explore Malaysia\'s diverse culture, tropical rainforests, and modern cities.',
            ),
            array('name' => 'South Korea','slug' => 'south-korea','description' => 'Discover South Korea\'s modern cities, food culture and entertainment hotspots.'),
            array('name' => 'Hong Kong','slug' => 'hong-kong','description' => 'Explore Hong Kong\'s skyline, shopping and urban attractions.'),
            array('name' => 'China','slug' => 'china','description' => 'Discover famous Chinese cities, landmarks and cultural highlights.'),
            array('name' => 'Vietnam','slug' => 'vietnam','description' => 'Explore Vietnam\'s cities, cuisine, coastlines and cultural highlights.'),
            array('name' => 'Philippines','slug' => 'philippines','description' => 'Discover island escapes, cities and travel experiences across the Philippines.'),
            array('name' => 'United States','slug' => 'united-states','description' => 'Explore city breaks, attractions and iconic travel experiences across the USA.'),
            array('name' => 'Canada','slug' => 'canada','description' => 'Discover Canada\'s urban attractions, nature and year-round travel experiences.'),
            array('name' => 'Australia','slug' => 'australia','description' => 'Explore Australia\'s cities, coastlines and bucket-list experiences.'),
            array('name' => 'Switzerland','slug' => 'switzerland','description' => 'Discover scenic rail journeys, mountains and premium travel experiences in Switzerland.'),
            array('name' => 'Germany','slug' => 'germany','description' => 'Explore Germany\'s historic cities, culture and major attractions.'),
        );
        
        foreach ($countries as $country) {
            if (!term_exists($country['slug'], 'travel_country')) {
                wp_insert_term(
                    $country['name'],
                    'travel_country',
                    array(
                        'slug'        => $country['slug'],
                        'description' => $country['description'],
                    )
                );
            }
        }
    }
    
    /**
     * Seed cities
     */
    public static function seed_cities() {
        $cities = array(
            // UAE
            array(
                'name'    => 'Dubai',
                'slug'    => 'dubai',
                'country' => 'united-arab-emirates',
                'hero'    => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1920',
                'desc'    => 'Experience the glamour of Dubai with world-famous attractions, luxury shopping, and thrilling adventures.',
            ),
            array(
                'name'    => 'Abu Dhabi',
                'slug'    => 'abu-dhabi',
                'country' => 'united-arab-emirates',
                'hero'    => 'https://images.unsplash.com/photo-1558610924-b3494b88a29e?w=1920',
                'desc'    => 'Discover Abu Dhabi\'s stunning architecture, cultural landmarks, and luxury experiences.',
            ),
            array(
                'name'    => 'Sharjah',
                'slug'    => 'sharjah',
                'country' => 'united-arab-emirates',
                'hero'    => 'https://images.unsplash.com/photo-1580674287404-79f5b7b7a8e4?w=1920',
                'desc'    => 'Explore Sharjah\'s rich cultural heritage, museums, and traditional souks.',
            ),
            array(
                'name'    => 'Ras Al Khaimah',
                'slug'    => 'ras-al-khaimah',
                'country' => 'united-arab-emirates',
                'hero'    => 'https://images.unsplash.com/photo-1597659840241-37e2b9c2f55f?w=1920',
                'desc'    => 'Adventure awaits in Ras Al Khaimah with mountains, beaches, and desert experiences.',
            ),
            // France
            array(
                'name'    => 'Paris',
                'slug'    => 'paris',
                'country' => 'france',
                'hero'    => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=1920',
                'desc'    => 'Fall in love with Paris - the city of lights, romance, and world-famous landmarks.',
            ),
            array(
                'name'    => 'Nice',
                'slug'    => 'nice',
                'country' => 'france',
                'hero'    => 'https://images.unsplash.com/photo-1533104816931-20fa691ff6ca?w=1920',
                'desc'    => 'Enjoy the French Riviera charm of Nice with beautiful beaches and Mediterranean vibes.',
            ),
            // Morocco
            array(
                'name'    => 'Marrakech',
                'slug'    => 'marrakech',
                'country' => 'morocco',
                'hero'    => 'https://images.unsplash.com/photo-1597212618440-806262de4f6b?w=1920',
                'desc'    => 'Immerse yourself in Marrakech\'s magical souks, palaces, and vibrant culture.',
            ),
            array(
                'name'    => 'Casablanca',
                'slug'    => 'casablanca',
                'country' => 'morocco',
                'hero'    => 'https://images.unsplash.com/photo-1569383746724-6f1b882b8f46?w=1920',
                'desc'    => 'Experience Casablanca\'s blend of modern architecture and Moroccan traditions.',
            ),
            // Saudi Arabia
            array(
                'name'    => 'Riyadh',
                'slug'    => 'riyadh',
                'country' => 'saudi-arabia',
                'hero'    => 'https://images.unsplash.com/photo-1586724237569-f3d0c1dee8c6?w=1920',
                'desc'    => 'Discover Riyadh\'s modern skyline, historical sites, and Saudi hospitality.',
            ),
            array(
                'name'    => 'Jeddah',
                'slug'    => 'jeddah',
                'country' => 'saudi-arabia',
                'hero'    => 'https://images.unsplash.com/photo-1578895101408-1a36b834405b?w=1920',
                'desc'    => 'Explore Jeddah\'s beautiful Red Sea coast, historic district, and vibrant culture.',
            ),
            array(
                'name'    => 'AlUla',
                'slug'    => 'alula',
                'country' => 'saudi-arabia',
                'hero'    => 'https://images.unsplash.com/photo-1588974269162-4c8e0f532f1c?w=1920',
                'desc'    => 'Step back in time at AlUla\'s ancient Nabataean tombs and stunning desert landscapes.',
            ),
            // Qatar
            array(
                'name'    => 'Doha',
                'slug'    => 'doha',
                'country' => 'qatar',
                'hero'    => 'https://images.unsplash.com/photo-1548017477-eb8b46fc4b4e?w=1920',
                'desc'    => 'Experience Doha\'s futuristic skyline, world-class museums, and Arabian heritage.',
            ),
            // Turkey
            array(
                'name'    => 'Istanbul',
                'slug'    => 'istanbul',
                'country' => 'turkey',
                'hero'    => 'https://images.unsplash.com/photo-1541432901042-2d8bd64b4a9b?w=1920',
                'desc'    => 'Where East meets West - explore Istanbul\'s iconic mosques, bazaars, and Bosphorus views.',
            ),
            array(
                'name'    => 'Cappadocia',
                'slug'    => 'cappadocia',
                'country' => 'turkey',
                'hero'    => 'https://images.unsplash.com/photo-1641128324972-af3212f0f6bd?w=1920',
                'desc'    => 'Float over fairy chimneys and explore ancient cave dwellings in magical Cappadocia.',
            ),
            // Thailand
            array(
                'name'    => 'Bangkok',
                'slug'    => 'bangkok',
                'country' => 'thailand',
                'hero'    => 'https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=1920',
                'desc'    => 'Experience Bangkok\'s ornate temples, floating markets, and legendary street food.',
            ),
            array(
                'name'    => 'Phuket',
                'slug'    => 'phuket',
                'country' => 'thailand',
                'hero'    => 'https://images.unsplash.com/photo-1589394815804-964ed0be2eb5?w=1920',
                'desc'    => 'Relax on Phuket\'s stunning beaches and discover island adventures.',
            ),
            // Japan
            array(
                'name'    => 'Tokyo',
                'slug'    => 'tokyo',
                'country' => 'japan',
                'hero'    => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=1920',
                'desc'    => 'Experience Tokyo\'s perfect blend of ancient temples and neon-lit modernity.',
            ),
            array(
                'name'    => 'Osaka',
                'slug'    => 'osaka',
                'country' => 'japan',
                'hero'    => 'https://images.unsplash.com/photo-1590559899731-a382839e5549?w=1920',
                'desc'    => 'Discover Osaka\'s culinary delights, historic castle, and vibrant nightlife.',
            ),
            array(
                'name'    => 'Kyoto',
                'slug'    => 'kyoto',
                'country' => 'japan',
                'hero'    => 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=1920',
                'desc'    => 'Step into traditional Japan with Kyoto\'s temples, geisha districts, and zen gardens.',
            ),
            // Singapore
            array(
                'name'    => 'Singapore',
                'slug'    => 'singapore-city',
                'country' => 'singapore',
                'hero'    => 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=1920',
                'desc'    => 'Explore Singapore\'s futuristic gardens, diverse cuisine, and world-class attractions.',
            ),
            // Indonesia
            array(
                'name'    => 'Bali',
                'slug'    => 'bali',
                'country' => 'indonesia',
                'hero'    => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=1920',
                'desc'    => 'Find your paradise in Bali with stunning temples, rice terraces, and beautiful beaches.',
            ),
            array(
                'name'    => 'Jakarta',
                'slug'    => 'jakarta',
                'country' => 'indonesia',
                'hero'    => 'https://images.unsplash.com/photo-1555899434-94d1368aa7af?w=1920',
                'desc'    => 'Experience Jakarta\'s diverse culture, historic sites, and vibrant city life.',
            ),
            // UK
            array(
                'name'    => 'London',
                'slug'    => 'london',
                'country' => 'united-kingdom',
                'hero'    => 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=1920',
                'desc'    => 'Discover London\'s royal palaces, world-class museums, and iconic landmarks.',
            ),
            array(
                'name'    => 'Edinburgh',
                'slug'    => 'edinburgh',
                'country' => 'united-kingdom',
                'hero'    => 'https://images.unsplash.com/photo-1543286386-2e659306cd6c?w=1920',
                'desc'    => 'Explore Edinburgh\'s medieval Old Town, stunning castle, and Scottish heritage.',
            ),
            // Italy
            array(
                'name'    => 'Rome',
                'slug'    => 'rome',
                'country' => 'italy',
                'hero'    => 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=1920',
                'desc'    => 'Walk through history in Rome with ancient ruins, art masterpieces, and la dolce vita.',
            ),
            array(
                'name'    => 'Venice',
                'slug'    => 'venice',
                'country' => 'italy',
                'hero'    => 'https://images.unsplash.com/photo-1523906834658-6e24ef2386f9?w=1920',
                'desc'    => 'Float through Venice\'s magical canals, historic palaces, and romantic bridges.',
            ),
            // Spain
            array(
                'name'    => 'Barcelona',
                'slug'    => 'barcelona',
                'country' => 'spain',
                'hero'    => 'https://images.unsplash.com/photo-1583422409516-2895a77efded?w=1920',
                'desc'    => 'Experience Barcelona\'s Gaudi architecture, beaches, and vibrant Catalan culture.',
            ),
            array(
                'name'    => 'Madrid',
                'slug'    => 'madrid',
                'country' => 'spain',
                'hero'    => 'https://images.unsplash.com/photo-1539037116277-4db20889f2d4?w=1920',
                'desc'    => 'Discover Madrid\'s royal palace, world-famous art museums, and lively tapas scene.',
            ),
            // Egypt
            array(
                'name'    => 'Cairo',
                'slug'    => 'cairo',
                'country' => 'egypt',
                'hero'    => 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=1920',
                'desc'    => 'Stand before the Great Pyramids and explore Cairo\'s ancient treasures.',
            ),
            // Malaysia
            array(
                'name'    => 'Kuala Lumpur',
                'slug'    => 'kuala-lumpur',
                'country' => 'malaysia',
                'hero'    => 'https://images.unsplash.com/photo-1596422846543-75c6fc197f07?w=1920',
                'desc'    => 'Marvel at the Petronas Towers and experience Kuala Lumpur\'s diverse culture.',
            ),
        );
        
        foreach ($cities as $city) {
            if (!term_exists($city['slug'], 'travel_city')) {
                $result = wp_insert_term(
                    $city['name'],
                    'travel_city',
                    array(
                        'slug'        => $city['slug'],
                        'description' => $city['desc'],
                    )
                );
                
                if (!is_wp_error($result)) {
                    $term_id = $result['term_id'];
                    
                    // Get country term
                    $country = get_term_by('slug', $city['country'], 'travel_country');
                    if ($country) {
                        update_term_meta($term_id, 'fth_parent_country', $country->term_id);
                    }
                    
                    // Set hero image
                    update_term_meta($term_id, 'fth_hero_image', $city['hero']);
                }
            }
        }
    }
    
    /**
     * Seed categories
     */
    public static function seed_categories() {
        $categories = array(
            array('name' => 'Attractions', 'slug' => 'attractions', 'icon' => 'fa-landmark', 'desc' => 'Must-see landmarks and iconic attractions'),
            array('name' => 'Theme Parks', 'slug' => 'theme-parks', 'icon' => 'fa-ferris-wheel', 'desc' => 'Thrilling theme parks and amusement experiences'),
            array('name' => 'Desert Safari', 'slug' => 'desert-safari', 'icon' => 'fa-sun', 'desc' => 'Exciting desert adventures and dune experiences'),
            array('name' => 'Water Activities', 'slug' => 'water-activities', 'icon' => 'fa-water', 'desc' => 'Water sports, aquariums, and marine experiences'),
            array('name' => 'Museums', 'slug' => 'museums', 'icon' => 'fa-building-columns', 'desc' => 'World-class museums and cultural exhibitions'),
            array('name' => 'Observation Decks', 'slug' => 'observation-decks', 'icon' => 'fa-binoculars', 'desc' => 'Stunning skyline views from iconic towers'),
            array('name' => 'Boat Tours', 'slug' => 'boat-tours', 'icon' => 'fa-ship', 'desc' => 'Scenic boat cruises and water tours'),
            array('name' => 'City Tours', 'slug' => 'city-tours', 'icon' => 'fa-bus', 'desc' => 'Guided city tours and sightseeing experiences'),
            array('name' => 'Family Activities', 'slug' => 'family-activities', 'icon' => 'fa-users', 'desc' => 'Fun activities for the whole family'),
            array('name' => 'Cultural Experiences', 'slug' => 'cultural-experiences', 'icon' => 'fa-masks-theater', 'desc' => 'Authentic cultural immersions and traditions'),
            array('name' => 'Outdoor Activities', 'slug' => 'outdoor-activities', 'icon' => 'fa-mountain', 'desc' => 'Adventure and nature experiences'),
            array('name' => 'Transfers', 'slug' => 'transfers', 'icon' => 'fa-car', 'desc' => 'Airport transfers and transportation services'),
            array('name' => 'Dining Experiences', 'slug' => 'dining-experiences', 'icon' => 'fa-utensils', 'desc' => 'Unique culinary and food experiences'),
            array('name' => 'Adventure Tours', 'slug' => 'adventure-tours', 'icon' => 'fa-person-hiking', 'desc' => 'Thrilling adventures and extreme activities'),
            array('name' => 'Shows & Entertainment', 'slug' => 'shows-entertainment', 'icon' => 'fa-ticket', 'desc' => 'Live shows, performances, and entertainment'),
            array('name' => 'Day Trips', 'slug' => 'day-trips', 'icon' => 'fa-route', 'desc' => 'Full-day excursions and guided trips'),
            array('name' => 'Wellness & Spa', 'slug' => 'wellness-spa', 'icon' => 'fa-spa', 'desc' => 'Relaxing spa treatments and wellness experiences'),
            array('name' => 'Nightlife', 'slug' => 'nightlife', 'icon' => 'fa-moon', 'desc' => 'Evening entertainment and nightlife experiences'),
        );
        
        foreach ($categories as $cat) {
            if (!term_exists($cat['slug'], 'travel_category')) {
                $result = wp_insert_term(
                    $cat['name'],
                    'travel_category',
                    array(
                        'slug'        => $cat['slug'],
                        'description' => $cat['desc'],
                    )
                );
                
                if (!is_wp_error($result)) {
                    update_term_meta($result['term_id'], 'fth_icon', $cat['icon']);
                    update_term_meta($result['term_id'], 'fth_color', '#19A880');
                }
            }
        }
    }
    
    /**
     * Seed types
     */
    public static function seed_types() {
        $types = array(
            array('name' => 'Ticket', 'slug' => 'ticket', 'desc' => 'Admission tickets and entry passes'),
            array('name' => 'Tour', 'slug' => 'tour', 'desc' => 'Guided tours with professional guides'),
            array('name' => 'Experience', 'slug' => 'experience', 'desc' => 'Unique experiences and activities'),
            array('name' => 'Pass', 'slug' => 'pass', 'desc' => 'Multi-attraction passes and combos'),
            array('name' => 'Transport', 'slug' => 'transport', 'desc' => 'Transportation and transfer services'),
            array('name' => 'Package', 'slug' => 'package', 'desc' => 'Bundled packages and deals'),
            array('name' => 'Rental', 'slug' => 'rental', 'desc' => 'Equipment and vehicle rentals'),
            array('name' => 'Class', 'slug' => 'class', 'desc' => 'Workshops and classes'),
        );
        
        foreach ($types as $type) {
            if (!term_exists($type['slug'], 'travel_type')) {
                wp_insert_term(
                    $type['name'],
                    'travel_type',
                    array(
                        'slug'        => $type['slug'],
                        'description' => $type['desc'],
                    )
                );
            }
        }
    }
    
    /**
     * Seed sample activities
     */
    public static function seed_sample_activities() {
        $activities = array(
            // Dubai Activities
            array(
                'title'       => 'Burj Khalifa At The Top Observation Deck',
                'content'     => 'Experience breathtaking 360-degree views of Dubai from the world\'s tallest building. At The Top observation deck on levels 124 and 125 offers unparalleled vistas of the city skyline, desert, and ocean. Watch the sunset paint the sky in brilliant colors or visit at night to see Dubai\'s glittering lights spread out beneath you.',
                'excerpt'     => 'Witness stunning panoramic views from the iconic Burj Khalifa observation deck.',
                'city'        => 'dubai',
                'country'     => 'united-arab-emirates',
                'category'    => 'observation-decks',
                'type'        => 'ticket',
                'price'       => 149,
                'rating'      => 4.8,
                'reviews'     => 12580,
                'featured'    => true,
                'bestseller'  => true,
                'duration'    => '1-2 hours',
                'highlights'  => "World's highest observation deck\n360-degree panoramic views\nInteractive displays and telescopes\nStunning sunset views",
                'image'       => 'https://images.unsplash.com/photo-1582672060674-bc2bd808a8b5?w=800',
            ),
            array(
                'title'       => 'Desert Safari with BBQ Dinner & Entertainment',
                'content'     => 'Embark on an unforgettable desert adventure with dune bashing in a 4x4 vehicle, camel riding, sandboarding, and a traditional BBQ dinner under the stars. Enjoy live entertainment including belly dancing, tanoura shows, and henna painting at a Bedouin-style camp.',
                'excerpt'     => 'Thrilling dune bashing, camel rides, and authentic BBQ dinner in the Arabian desert.',
                'city'        => 'dubai',
                'country'     => 'united-arab-emirates',
                'category'    => 'desert-safari',
                'type'        => 'tour',
                'price'       => 75,
                'rating'      => 4.7,
                'reviews'     => 8934,
                'featured'    => true,
                'bestseller'  => true,
                'duration'    => '6-7 hours',
                'highlights'  => "Exciting dune bashing experience\nCamel riding and sandboarding\nTraditional BBQ dinner\nLive belly dancing and shows\nHenna painting",
                'image'       => 'https://images.unsplash.com/photo-1451337516015-6b6e9a44a8a3?w=800',
            ),
            array(
                'title'       => 'Dubai Aquarium & Underwater Zoo',
                'content'     => 'Discover one of the world\'s largest suspended aquariums, home to over 140 species of sea life including sharks, rays, and colorful fish. Walk through the 48-meter tunnel for an immersive underwater experience and explore the Underwater Zoo with its diverse collection of aquatic animals.',
                'excerpt'     => 'Explore one of the world\'s largest aquariums with sharks, rays, and more.',
                'city'        => 'dubai',
                'country'     => 'united-arab-emirates',
                'category'    => 'water-activities',
                'type'        => 'ticket',
                'price'       => 135,
                'rating'      => 4.6,
                'reviews'     => 6721,
                'featured'    => true,
                'bestseller'  => false,
                'duration'    => '2-3 hours',
                'highlights'  => "10-million liter tank\n48-meter walk-through tunnel\n140+ species of sea life\nUnderwater Zoo experience",
                'image'       => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=800',
            ),
            array(
                'title'       => 'IMG Worlds of Adventure Tickets',
                'content'     => 'Enter the world\'s largest indoor theme park featuring Marvel, Cartoon Network, Lost Valley, and IMG Boulevard zones. Enjoy thrilling rides, meet your favorite characters, and experience attractions suitable for all ages in a climate-controlled environment.',
                'excerpt'     => 'World\'s largest indoor theme park with Marvel, Cartoon Network & more.',
                'city'        => 'dubai',
                'country'     => 'united-arab-emirates',
                'category'    => 'theme-parks',
                'type'        => 'ticket',
                'price'       => 89,
                'rating'      => 4.5,
                'reviews'     => 5432,
                'featured'    => false,
                'bestseller'  => true,
                'duration'    => 'Full day',
                'highlights'  => "World's largest indoor theme park\n4 epic adventure zones\nMarvel and Cartoon Network attractions\nAll-ages entertainment",
                'image'       => 'https://images.unsplash.com/photo-1563911302283-d2bc129e7570?w=800',
            ),
            array(
                'title'       => 'Dubai Marina Dinner Cruise',
                'content'     => 'Sail through the stunning Dubai Marina aboard a luxury dhow cruise while enjoying a delicious international buffet dinner. Take in the spectacular views of illuminated skyscrapers and waterfront landmarks as you glide past the Palm Jumeirah and JBR.',
                'excerpt'     => 'Luxurious dinner cruise through Dubai Marina with stunning skyline views.',
                'city'        => 'dubai',
                'country'     => 'united-arab-emirates',
                'category'    => 'boat-tours',
                'type'        => 'experience',
                'price'       => 65,
                'rating'      => 4.6,
                'reviews'     => 3876,
                'featured'    => true,
                'bestseller'  => false,
                'duration'    => '2 hours',
                'highlights'  => "Luxury dhow cruise\nInternational buffet dinner\nStunning marina views\nLive entertainment",
                'image'       => 'https://images.unsplash.com/photo-1580541631950-7282082b53ce?w=800',
            ),
            // Paris Activities
            array(
                'title'       => 'Eiffel Tower Summit Access',
                'content'     => 'Skip the lines and ascend to the summit of the iconic Eiffel Tower. Enjoy breathtaking panoramic views of Paris from 276 meters high, visit Gustave Eiffel\'s restored office, and sip champagne at the top. See all of Paris\'s famous landmarks from above.',
                'excerpt'     => 'Skip-the-line access to the Eiffel Tower summit with panoramic Paris views.',
                'city'        => 'paris',
                'country'     => 'france',
                'category'    => 'observation-decks',
                'type'        => 'ticket',
                'price'       => 79,
                'rating'      => 4.9,
                'reviews'     => 15680,
                'featured'    => true,
                'bestseller'  => true,
                'duration'    => '1-2 hours',
                'highlights'  => "Skip-the-line entry\nSummit level access\n360-degree Paris views\nGustave Eiffel's office",
                'image'       => 'https://images.unsplash.com/photo-1543349689-9a4d426bee8e?w=800',
            ),
            array(
                'title'       => 'Louvre Museum Guided Tour',
                'content'     => 'Discover masterpieces at the world\'s most visited museum with an expert guide. See the Mona Lisa, Venus de Milo, and Winged Victory of Samothrace while learning about the history behind these iconic works. Skip the long queues with priority access.',
                'excerpt'     => 'Expert-guided tour of the Louvre with priority access to see the Mona Lisa.',
                'city'        => 'paris',
                'country'     => 'france',
                'category'    => 'museums',
                'type'        => 'tour',
                'price'       => 65,
                'rating'      => 4.8,
                'reviews'     => 8934,
                'featured'    => true,
                'bestseller'  => true,
                'duration'    => '2-3 hours',
                'highlights'  => "Skip-the-line access\nExpert English guide\nMona Lisa and Venus de Milo\nSmall group experience",
                'image'       => 'https://images.unsplash.com/photo-1499426600726-ac2c8e6acec6?w=800',
            ),
            // Tokyo Activities
            array(
                'title'       => 'Tokyo Skytree Observation Deck',
                'content'     => 'Ascend to the observation decks of Tokyo Skytree, Japan\'s tallest structure at 634 meters. The Tembo Deck at 350m and Tembo Galleria at 450m offer stunning views of Tokyo, and on clear days, you can even see Mount Fuji.',
                'excerpt'     => 'Stunning views from Japan\'s tallest tower with Mount Fuji visibility.',
                'city'        => 'tokyo',
                'country'     => 'japan',
                'category'    => 'observation-decks',
                'type'        => 'ticket',
                'price'       => 25,
                'rating'      => 4.7,
                'reviews'     => 7654,
                'featured'    => true,
                'bestseller'  => true,
                'duration'    => '1-2 hours',
                'highlights'  => "Japan's tallest structure\nTwo observation levels\nMount Fuji views\nGlass floor section",
                'image'       => 'https://images.unsplash.com/photo-1536098561742-ca998e48cbcc?w=800',
            ),
            array(
                'title'       => 'Traditional Tea Ceremony Experience',
                'content'     => 'Immerse yourself in Japanese culture with an authentic tea ceremony led by a tea master. Learn the art of preparing matcha, understand the philosophy behind the ritual, and enjoy traditional Japanese sweets in a serene tatami room.',
                'excerpt'     => 'Authentic Japanese tea ceremony with a tea master in a traditional setting.',
                'city'        => 'tokyo',
                'country'     => 'japan',
                'category'    => 'cultural-experiences',
                'type'        => 'experience',
                'price'       => 45,
                'rating'      => 4.9,
                'reviews'     => 3421,
                'featured'    => false,
                'bestseller'  => true,
                'duration'    => '1 hour',
                'highlights'  => "Led by tea master\nTraditional tatami room\nMatcha preparation\nJapanese sweets included",
                'image'       => 'https://images.unsplash.com/photo-1545048702-79362596cdc9?w=800',
            ),
            // Bali Activities
            array(
                'title'       => 'Bali Instagram Tour: Gates of Heaven & More',
                'content'     => 'Visit Bali\'s most photogenic spots including Lempuyang Temple (Gates of Heaven), Tirta Gangga Water Palace, Tukad Cepung Waterfall, and Tegalalang Rice Terraces. Perfect for capturing stunning photos and experiencing Bali\'s natural beauty.',
                'excerpt'     => 'Visit Bali\'s most Instagrammable spots including the famous Gates of Heaven.',
                'city'        => 'bali',
                'country'     => 'indonesia',
                'category'    => 'city-tours',
                'type'        => 'tour',
                'price'       => 55,
                'rating'      => 4.8,
                'reviews'     => 4532,
                'featured'    => true,
                'bestseller'  => true,
                'duration'    => '10-12 hours',
                'highlights'  => "Gates of Heaven temple\nTirta Gangga palace\nTukad Cepung waterfall\nRice terraces\nLocal lunch included",
                'image'       => 'https://images.unsplash.com/photo-1604999333679-b86d54738315?w=800',
            ),
            // Istanbul
            array(
                'title'       => 'Hagia Sophia & Blue Mosque Guided Tour',
                'content'     => 'Explore Istanbul\'s most iconic landmarks with an expert guide. Visit the magnificent Hagia Sophia, the stunning Blue Mosque, and the ancient Hippodrome. Learn about the city\'s Byzantine and Ottoman history.',
                'excerpt'     => 'Guided tour of Istanbul\'s iconic Hagia Sophia and Blue Mosque.',
                'city'        => 'istanbul',
                'country'     => 'turkey',
                'category'    => 'city-tours',
                'type'        => 'tour',
                'price'       => 45,
                'rating'      => 4.8,
                'reviews'     => 5678,
                'featured'    => true,
                'bestseller'  => true,
                'duration'    => '3 hours',
                'highlights'  => "Hagia Sophia visit\nBlue Mosque tour\nAncient Hippodrome\nExpert English guide",
                'image'       => 'https://images.unsplash.com/photo-1541432901042-2d8bd64b4a9b?w=800',
            ),
            // Singapore
            array(
                'title'       => 'Gardens by the Bay with Cloud Forest',
                'content'     => 'Explore Singapore\'s stunning Gardens by the Bay with entry to both the Flower Dome and Cloud Forest conservatories. Marvel at the world\'s tallest indoor waterfall, walk among the Supertrees, and discover plants from every continent.',
                'excerpt'     => 'Explore iconic Singapore gardens with Cloud Forest and Flower Dome access.',
                'city'        => 'singapore-city',
                'country'     => 'singapore',
                'category'    => 'attractions',
                'type'        => 'ticket',
                'price'       => 28,
                'rating'      => 4.8,
                'reviews'     => 9876,
                'featured'    => true,
                'bestseller'  => true,
                'duration'    => '3-4 hours',
                'highlights'  => "Cloud Forest conservatory\nFlower Dome\nSupertree Grove\nWorld's tallest indoor waterfall",
                'image'       => 'https://images.unsplash.com/photo-1506351421178-63b52a2d2562?w=800',
            ),
        );
        
        foreach ($activities as $activity) {
            // Check if activity already exists
            $existing = get_page_by_title($activity['title'], OBJECT, 'travel_activity');
            if ($existing) {
                continue;
            }
            
            $post_id = wp_insert_post(array(
                'post_title'   => $activity['title'],
                'post_content' => $activity['content'],
                'post_excerpt' => $activity['excerpt'],
                'post_status'  => 'publish',
                'post_type'    => 'travel_activity',
                'post_author'  => 1,
            ));
            
            if ($post_id && !is_wp_error($post_id)) {
                // Set taxonomies
                $city = get_term_by('slug', $activity['city'], 'travel_city');
                if ($city) {
                    wp_set_object_terms($post_id, $city->term_id, 'travel_city');
                }
                
                $country = get_term_by('slug', $activity['country'], 'travel_country');
                if ($country) {
                    wp_set_object_terms($post_id, $country->term_id, 'travel_country');
                }
                
                $category = get_term_by('slug', $activity['category'], 'travel_category');
                if ($category) {
                    wp_set_object_terms($post_id, $category->term_id, 'travel_category');
                }
                
                $type = get_term_by('slug', $activity['type'], 'travel_type');
                if ($type) {
                    wp_set_object_terms($post_id, $type->term_id, 'travel_type');
                }
                
                // Set meta
                update_post_meta($post_id, '_fth_price', $activity['price']);
                update_post_meta($post_id, '_fth_currency', 'USD');
                update_post_meta($post_id, '_fth_rating', $activity['rating']);
                update_post_meta($post_id, '_fth_review_count', $activity['reviews']);
                update_post_meta($post_id, '_fth_is_featured', $activity['featured'] ? '1' : '0');
                update_post_meta($post_id, '_fth_is_bestseller', $activity['bestseller'] ? '1' : '0');
                update_post_meta($post_id, '_fth_duration', $activity['duration']);
                update_post_meta($post_id, '_fth_highlights', $activity['highlights']);
                update_post_meta($post_id, '_fth_external_image', $activity['image']);
            }
        }
    }
    
    /**
     * Seed sample destinations
     */
    public static function seed_sample_destinations() {
        $destinations = array(
            array(
                'title'    => 'Dubai',
                'content'  => 'Dubai, the jewel of the United Arab Emirates, is a city of superlatives. From the world\'s tallest building to man-made islands visible from space, Dubai constantly pushes the boundaries of what\'s possible. Experience luxury shopping in gold-covered souks, thrilling desert adventures, and world-class entertainment. Whether you\'re seeking adventure, relaxation, or cultural experiences, Dubai delivers unforgettable memories.',
                'excerpt'  => 'Discover the city of superlatives with iconic attractions, desert adventures, and luxury experiences.',
                'city'     => 'dubai',
                'country'  => 'united-arab-emirates',
                'subtitle' => 'City of Gold',
                'featured' => true,
                'image'    => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1920',
            ),
            array(
                'title'    => 'Paris',
                'content'  => 'Paris, the City of Light, captivates millions with its timeless elegance, world-renowned art, and romantic ambiance. Climb the Eiffel Tower, wander through the Louvre, stroll along the Seine, and indulge in exquisite French cuisine. Every corner of Paris tells a story, from the grand boulevards to hidden passages, making it a dream destination for travelers worldwide.',
                'excerpt'  => 'Experience the magic of the City of Light with iconic landmarks, art, and romance.',
                'city'     => 'paris',
                'country'  => 'france',
                'subtitle' => 'City of Light',
                'featured' => true,
                'image'    => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=1920',
            ),
            array(
                'title'    => 'Tokyo',
                'content'  => 'Tokyo seamlessly blends ultra-modern innovation with ancient traditions. Explore futuristic Shibuya, serene temples in Asakusa, world-class dining in Ginza, and vibrant pop culture in Harajuku. This dynamic metropolis offers endless discoveries, from cherry blossom parks to neon-lit entertainment districts, making every visit a unique adventure.',
                'excerpt'  => 'Where ancient traditions meet cutting-edge technology in one incredible city.',
                'city'     => 'tokyo',
                'country'  => 'japan',
                'subtitle' => 'Where Tradition Meets Tomorrow',
                'featured' => true,
                'image'    => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=1920',
            ),
            array(
                'title'    => 'Bali',
                'content'  => 'Bali, the Island of the Gods, enchants visitors with its stunning temples, lush rice terraces, pristine beaches, and vibrant culture. From spiritual retreats in Ubud to beach clubs in Seminyak, surfing in Uluwatu to diving in Nusa Penida, Bali offers the perfect blend of relaxation, adventure, and cultural immersion.',
                'excerpt'  => 'Find your paradise on the Island of the Gods with temples, beaches, and culture.',
                'city'     => 'bali',
                'country'  => 'indonesia',
                'subtitle' => 'Island of the Gods',
                'featured' => true,
                'image'    => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=1920',
            ),
            array(
                'title'    => 'Singapore',
                'content'  => 'Singapore punches well above its weight as a global destination. This garden city dazzles with futuristic architecture, world-famous food scene, pristine streets, and multicultural neighborhoods. From Marina Bay Sands to Gardens by the Bay, Sentosa Island to Chinatown, Singapore offers an incredible mix of experiences in a compact, efficient package.',
                'excerpt'  => 'Explore the Lion City\'s futuristic gardens, diverse cuisine, and world-class attractions.',
                'city'     => 'singapore-city',
                'country'  => 'singapore',
                'subtitle' => 'The Lion City',
                'featured' => true,
                'image'    => 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=1920',
            ),
            array(
                'title'    => 'Istanbul',
                'content'  => 'Istanbul straddles two continents, bridging Europe and Asia with its unique blend of cultures, cuisines, and history. Explore the magnificent Hagia Sophia, cruise the Bosphorus, haggle in the Grand Bazaar, and feast on Turkish delights. This ancient city of empires continues to captivate travelers with its vibrant energy and timeless beauty.',
                'excerpt'  => 'Where East meets West in a spectacular fusion of culture, history, and cuisine.',
                'city'     => 'istanbul',
                'country'  => 'turkey',
                'subtitle' => 'Where East Meets West',
                'featured' => true,
                'image'    => 'https://images.unsplash.com/photo-1541432901042-2d8bd64b4a9b?w=1920',
            ),
        );
        
        foreach ($destinations as $dest) {
            $existing = get_page_by_title($dest['title'], OBJECT, 'travel_destination');
            if ($existing) {
                continue;
            }
            
            $post_id = wp_insert_post(array(
                'post_title'   => $dest['title'],
                'post_content' => $dest['content'],
                'post_excerpt' => $dest['excerpt'],
                'post_status'  => 'publish',
                'post_type'    => 'travel_destination',
                'post_author'  => 1,
            ));
            
            if ($post_id && !is_wp_error($post_id)) {
                $city = get_term_by('slug', $dest['city'], 'travel_city');
                if ($city) {
                    wp_set_object_terms($post_id, $city->term_id, 'travel_city');
                }
                
                $country = get_term_by('slug', $dest['country'], 'travel_country');
                if ($country) {
                    wp_set_object_terms($post_id, $country->term_id, 'travel_country');
                }
                
                update_post_meta($post_id, '_fth_hero_subtitle', $dest['subtitle']);
                update_post_meta($post_id, '_fth_is_featured', $dest['featured'] ? '1' : '0');
                update_post_meta($post_id, '_fth_external_image', $dest['image']);
            }
        }
    }
}
