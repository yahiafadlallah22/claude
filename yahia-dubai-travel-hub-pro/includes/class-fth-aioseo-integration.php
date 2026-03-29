<?php
/**
 * AIO SEO Premium Integration
 * Automatically fills SEO fields for all travel content
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_AIOSEO_Integration {
    
    /**
     * Initialize AIO SEO integration
     */
    public static function init() {
        // Check if AIO SEO is active
        if (!defined('AIOSEO_VERSION')) {
            return;
        }
        
        // Auto-fill SEO data on post save
        add_action('save_post_travel_activity', array(__CLASS__, 'auto_fill_activity_seo'), 20, 2);
        add_action('save_post_travel_destination', array(__CLASS__, 'auto_fill_destination_seo'), 20, 2);
        add_action('save_post_travel_hotel', array(__CLASS__, 'auto_fill_hotel_seo'), 20, 2);
        
        // Auto-fill on taxonomy term save
        add_action('created_travel_city', array(__CLASS__, 'auto_fill_city_seo'), 20, 2);
        add_action('edited_travel_city', array(__CLASS__, 'auto_fill_city_seo'), 20, 2);
        add_action('created_travel_country', array(__CLASS__, 'auto_fill_country_seo'), 20, 2);
        add_action('edited_travel_country', array(__CLASS__, 'auto_fill_country_seo'), 20, 2);
        add_action('created_travel_category', array(__CLASS__, 'auto_fill_category_seo'), 20, 2);
        add_action('edited_travel_category', array(__CLASS__, 'auto_fill_category_seo'), 20, 2);
        
        // Filter AIO SEO output
        add_filter('aioseo_title', array(__CLASS__, 'filter_title'), 10, 1);
        add_filter('aioseo_description', array(__CLASS__, 'filter_description'), 10, 1);
        add_filter('aioseo_schema_output', array(__CLASS__, 'add_schema'), 10, 1);
        add_filter('aioseo_canonical_url', array(__CLASS__, 'filter_canonical'), 10, 1);
        
        // Add Open Graph data
        add_filter('aioseo_facebook_tags', array(__CLASS__, 'add_facebook_tags'), 10, 1);
        add_filter('aioseo_twitter_tags', array(__CLASS__, 'add_twitter_tags'), 10, 1);
        
        // Add admin notice for AIO SEO detection
        add_action('admin_notices', array(__CLASS__, 'aioseo_integration_notice'));
        
        // Register custom variables for AIO SEO
        add_filter('aioseo_tags_list', array(__CLASS__, 'add_custom_tags'));
        add_filter('aioseo_tags_value', array(__CLASS__, 'get_custom_tag_value'), 10, 3);
    }
    
    /**
     * Auto-fill Activity SEO fields
     */
    public static function auto_fill_activity_seo($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if ($post->post_status === 'auto-draft') return;
        
        // Get activity data
        $title = $post->post_title;
        $cities = wp_get_post_terms($post_id, 'travel_city');
        $city_name = !empty($cities) ? $cities[0]->name : '';
        $countries = wp_get_post_terms($post_id, 'travel_country');
        $country_name = !empty($countries) ? $countries[0]->name : '';
        $categories = wp_get_post_terms($post_id, 'travel_category');
        $category_name = !empty($categories) ? $categories[0]->name : '';
        
        $price = get_post_meta($post_id, '_fth_price', true);
        $rating = get_post_meta($post_id, '_fth_rating', true);
        $review_count = get_post_meta($post_id, '_fth_review_count', true);
        $highlights = get_post_meta($post_id, '_fth_highlights', true);
        $duration = get_post_meta($post_id, '_fth_duration', true);
        
        // Generate SEO Title
        $seo_title = $title;
        if ($city_name) {
            $seo_title .= ' in ' . $city_name;
        }
        $seo_title .= ' | Tickets';
        
        // Generate SEO Description
        $excerpt = $post->post_excerpt;
        if (empty($excerpt)) {
            $excerpt = wp_trim_words(strip_tags($post->post_content), 25, '...');
        }
        
        $seo_description = '';
        if ($excerpt) {
            $seo_description = $excerpt;
        } else {
            $seo_description = 'Book ' . $title;
            if ($city_name) {
                $seo_description .= ' in ' . $city_name;
            }
            $seo_description .= '. ';
            if ($rating) {
                $seo_description .= 'Rated ' . $rating . '/5';
                if ($review_count) {
                    $seo_description .= ' by ' . number_format($review_count) . ' travelers';
                }
                $seo_description .= '. ';
            }
            if ($duration) {
                $seo_description .= 'Duration: ' . $duration . '. ';
            }
            $seo_description .= 'Instant confirmation & secure booking.';
        }
        
        // ── Focus keyphrase: most specific first ─────────────────────
        if ($city_name) {
            $focus_keyphrase = strtolower(trim($title . ' ' . $city_name));
        } else {
            $focus_keyphrase = strtolower(trim($title));
        }

        // ── Additional keyphrases (up to 4) ──────────────────────────
        $additional = array_values(array_unique(array_filter(array(
            $city_name    ? strtolower($city_name . ' things to do')   : '',
            $city_name    ? strtolower($city_name . ' tickets')        : '',
            $city_name    ? strtolower($city_name . ' tours')          : '',
            $country_name ? strtolower($country_name . ' activities')  : '',
            strtolower($title . ' tickets'),
            strtolower($title . ' booking'),
        ))));
        $additional = array_slice($additional, 0, 4);

        // ── Featured image for OG ─────────────────────────────────────
        $og_image = '';
        if (has_post_thumbnail($post_id)) {
            $og_image = get_the_post_thumbnail_url($post_id, 'large');
        }
        if (!$og_image) {
            $og_image = get_post_meta($post_id, '_fth_external_image', true);
        }

        self::update_aioseo_meta($post_id, array(
            'title'                => $seo_title,
            'description'          => $seo_description,
            'focus_keyphrase'      => $focus_keyphrase,
            'additional_keyphrases'=> $additional,
            'og_title'             => $seo_title,
            'og_description'       => $seo_description,
            'og_image'             => $og_image,
            'twitter_title'        => $seo_title,
            'twitter_description'  => $seo_description,
        ));

        // Legacy _fth_seo_keywords for internal use
        update_post_meta($post_id, '_fth_seo_keywords', implode(', ', array_merge(
            array($title),
            $city_name    ? array($city_name)    : array(),
            $country_name ? array($country_name) : array(),
            $category_name? array($category_name): array(),
            array('tours', 'activities', 'book online')
        )));
    }
    
    /**
     * Auto-fill Destination SEO fields
     */
    public static function auto_fill_destination_seo($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if ($post->post_status === 'auto-draft') return;
        
        $title = $post->post_title;
        $seo_intro = get_post_meta($post_id, '_fth_seo_intro', true);
        
        $cities = wp_get_post_terms($post_id, 'travel_city');
        $city_name = !empty($cities) ? $cities[0]->name : $title;
        
        $countries = wp_get_post_terms($post_id, 'travel_country');
        $country_name = !empty($countries) ? $countries[0]->name : '';
        
        // SEO Title
        $seo_title = 'Things to Do in ' . $title . ' | Tours, Attractions & Experiences';
        
        // SEO Description
        if ($seo_intro) {
            $seo_description = wp_trim_words($seo_intro, 25, '...');
        } else {
            $seo_description = 'Discover the best things to do in ' . $title . '. ';
            if ($country_name) {
                $seo_description .= 'Explore top attractions in ' . $country_name . '. ';
            }
            $seo_description .= 'Book tours, activities, and unique experiences. Instant confirmation & best prices.';
        }
        
        // Focus Keyphrase
        $focus_keyphrase = 'things to do in ' . strtolower($title);
        
        self::update_aioseo_meta($post_id, array(
            'title'           => $seo_title,
            'description'     => $seo_description,
            'focus_keyphrase' => $focus_keyphrase,
            'og_title'        => $seo_title,
            'og_description'  => $seo_description,
            'twitter_title'   => $seo_title,
            'twitter_description' => $seo_description,
        ));
    }
    
    /**
     * Auto-fill Hotel SEO fields
     */
    public static function auto_fill_hotel_seo($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if ($post->post_status === 'auto-draft') return;
        
        $title = $post->post_title;
        $cities = wp_get_post_terms($post_id, 'travel_city');
        $city_name = !empty($cities) ? $cities[0]->name : '';
        $countries = wp_get_post_terms($post_id, 'travel_country');
        $country_name = !empty($countries) ? $countries[0]->name : '';
        $star_rating = get_post_meta($post_id, '_fth_star_rating', true);
        $price = get_post_meta($post_id, '_fth_price', true);
        
        // SEO Title
        $seo_title = $title;
        if ($city_name) {
            $seo_title .= ' - ' . $city_name;
        }
        if ($star_rating) {
            $seo_title .= ' | ' . $star_rating . '-Star Hotel';
        }
        
        
        // SEO Description
        $seo_description = 'Book ' . $title;
        if ($city_name) {
            $seo_description .= ' in ' . $city_name;
        }
        $seo_description .= '. ';
        if ($star_rating) {
            $seo_description .= $star_rating . '-star accommodation. ';
        }
        if ($price) {
            $seo_description .= 'From $' . number_format($price, 2) . '/night. ';
        }
        $seo_description .= 'Best rates guaranteed.';
        
        $focus_keyphrase = $city_name
            ? strtolower(trim($title . ' ' . $city_name))
            : strtolower(trim($title));

        $additional = array_values(array_unique(array_filter(array(
            $city_name    ? strtolower($city_name . ' hotels')          : '',
            $city_name    ? strtolower($city_name . ' hotel rooms')     : '',
            $country_name ? strtolower($country_name . ' hotels')       : '',
            strtolower($title . ' booking'),
            strtolower($title . ' rooms'),
        ))));
        $additional = array_slice($additional, 0, 4);

        $og_image = '';
        if (has_post_thumbnail($post_id)) {
            $og_image = get_the_post_thumbnail_url($post_id, 'large');
        }
        if (!$og_image) {
            $og_image = get_post_meta($post_id, '_fth_external_image', true);
        }

        self::update_aioseo_meta($post_id, array(
            'title'                => $seo_title,
            'description'          => $seo_description,
            'focus_keyphrase'      => $focus_keyphrase,
            'additional_keyphrases'=> $additional,
            'og_title'             => $seo_title,
            'og_description'       => $seo_description,
            'og_image'             => $og_image,
        ));
    }
    
    /**
     * Auto-fill City taxonomy SEO
     */
    public static function auto_fill_city_seo($term_id, $tt_id = 0) {
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
        
        $activity_count = FTH_Templates::get_city_activity_count($term_id);
        
        // SEO Title
        $seo_title = 'Things to Do in ' . $term->name;
        if ($country_name) {
            $seo_title .= ', ' . $country_name;
        }
        $seo_title .= ' | ' . $activity_count . ' Tours & Activities';
        
        // SEO Description
        $seo_description = 'Discover ' . $activity_count . ' amazing things to do in ' . $term->name . '. ';
        if ($country_name) {
            $seo_description .= 'Top attractions in ' . $country_name . '. ';
        }
        $seo_description .= 'Book tours, activities, tickets & experiences. Best prices & instant confirmation.';
        
        // Focus Keyphrase
        $focus_keyphrase = 'things to do in ' . strtolower($term->name);
        
        $hero_image = get_term_meta($term_id, 'fth_hero_image', true);

        self::update_term_aioseo_meta($term_id, 'travel_city', array(
            'title'                => $seo_title,
            'description'          => $seo_description,
            'focus_keyphrase'      => $focus_keyphrase,
            'additional_keyphrases'=> array_filter(array(
                strtolower($term->name . ' attractions'),
                strtolower($term->name . ' tours'),
                $country_name ? strtolower($term->name . ' ' . $country_name) : '',
                strtolower($term->name . ' tickets'),
            )),
            'og_title'             => $seo_title,
            'og_description'       => $seo_description,
            'og_image'             => $hero_image ?: '',
        ));
    }

    /**
     * Auto-fill Country taxonomy SEO
     */
    public static function auto_fill_country_seo($term_id, $tt_id = 0) {
        $term = get_term($term_id, 'travel_country');
        if (!$term || is_wp_error($term)) return;

        $cities = get_terms(array(
            'taxonomy'   => 'travel_city',
            'hide_empty' => false,
            'meta_query' => array(array('key' => 'fth_parent_country', 'value' => $term_id)),
        ));
        $cities_count = is_array($cities) ? count($cities) : 0;

        $seo_title       = 'Things to Do in ' . $term->name . ' | Top Destinations & Activities';
        $seo_description = 'Explore ' . $term->name . ' with ' . $cities_count . ' destinations. Book tours, attractions, and experiences. Best prices guaranteed.';
        $focus_keyphrase = 'things to do in ' . strtolower($term->name);

        self::update_term_aioseo_meta($term_id, 'travel_country', array(
            'title'                => $seo_title,
            'description'          => $seo_description,
            'focus_keyphrase'      => $focus_keyphrase,
            'additional_keyphrases'=> array(
                strtolower($term->name . ' travel'),
                strtolower($term->name . ' tours'),
                strtolower($term->name . ' activities'),
            ),
        ));
    }
    
    /**
     * Auto-fill Category taxonomy SEO
     */
    public static function auto_fill_category_seo($term_id, $tt_id = 0) {
        $term = get_term($term_id, 'travel_category');
        if (!$term || is_wp_error($term)) return;
        
        $seo_title = $term->name . ' Tours & Activities | Book Online';
        $seo_description = 'Browse the best ' . strtolower($term->name) . ' tours and activities. ' . $term->description . ' Instant confirmation & secure booking.';
        $focus_keyphrase = strtolower($term->name) . ' tours';
        
        self::update_term_aioseo_meta($term_id, 'travel_category', array(
            'title'           => $seo_title,
            'description'     => $seo_description,
            'focus_keyphrase' => $focus_keyphrase,
        ));
    }
    
    /**
     * Update AIO SEO meta for posts
     */
    private static function update_aioseo_meta($post_id, $data) {
        // Check if AIOSEO Models exist
        if (!class_exists('AIOSEO\Plugin\Common\Models\Post')) {
            // Fallback to post meta
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
            return;
        }
        
        // Use AIOSEO ORM if available
        try {
            $aioseoPost = \AIOSEO\Plugin\Common\Models\Post::getPost($post_id);
            if ($aioseoPost) {
                if (!empty($data['title']))       $aioseoPost->title       = $data['title'];
                if (!empty($data['description'])) $aioseoPost->description = $data['description'];
                // Keyphrases (focus + additional)
                if (!empty($data['focus_keyphrase'])) {
                    $kpObj = array(
                        'focus'      => array('keyphrase' => $data['focus_keyphrase'], 'score' => 0, 'analysis' => new stdClass()),
                        'additional' => array(),
                    );
                    foreach ((array) ($data['additional_keyphrases'] ?? array()) as $ak) {
                        $kpObj['additional'][] = array('keyphrase' => $ak, 'score' => 0, 'analysis' => new stdClass());
                    }
                    $aioseoPost->keyphrases = wp_json_encode($kpObj);
                }
                // Open Graph
                $aioseoPost->og_title       = $data['og_title']       ?? $data['title']       ?? '';
                $aioseoPost->og_description = $data['og_description']  ?? $data['description'] ?? '';
                if (!empty($data['og_image'])) {
                    $aioseoPost->og_image_type       = 'custom_image';
                    $aioseoPost->og_image_custom_url = esc_url_raw($data['og_image']);
                }
                // Twitter = mirror OG
                $aioseoPost->twitter_use_og = 1;
                $aioseoPost->save();
            }
        } catch (\Throwable $e) {
            // Fallback to post meta
            $this_data = $data; // scope fix for PHP 5
        }
        // Always write post meta as fallback (AIOSEO reads these too)
        $meta_map = array(
            'title'             => $data['title']              ?? '',
            'description'       => $data['description']        ?? '',
            'og_title'          => $data['og_title']           ?? ($data['title'] ?? ''),
            'og_description'    => $data['og_description']     ?? ($data['description'] ?? ''),
            'twitter_title'     => $data['twitter_title']      ?? ($data['title'] ?? ''),
            'twitter_description'=> $data['twitter_description']?? ($data['description'] ?? ''),
            'twitter_use_og'    => '1',
        );
        foreach ($meta_map as $k => $v) {
            if ($v !== '') update_post_meta($post_id, '_aioseo_' . $k, $v);
        }
        if (!empty($data['og_image'])) {
            update_post_meta($post_id, '_aioseo_og_image_type',       'custom_image');
            update_post_meta($post_id, '_aioseo_og_image_custom_url', esc_url_raw($data['og_image']));
        }
        // Keyphrases
        if (!empty($data['focus_keyphrase'])) {
            $kp = array(
                'focus'      => array('keyphrase' => $data['focus_keyphrase'], 'score' => 0, 'analysis' => new stdClass()),
                'additional' => array_map(
                    function($ak){ return array('keyphrase'=>$ak,'score'=>0,'analysis'=>new stdClass()); },
                    (array)($data['additional_keyphrases']??[])
                ),
            );
            update_post_meta($post_id, '_aioseo_keyphrases', wp_json_encode($kp));
        }
    }
    
    /**
     * Update AIO SEO meta for terms (v1.7 – full support)
     */
    private static function update_term_aioseo_meta($term_id, $taxonomy, $data) {
        $meta = array(
            '_aioseo_title'             => isset($data['title'])           ? $data['title']           : '',
            '_aioseo_description'       => isset($data['description'])     ? $data['description']     : '',
            '_aioseo_og_title'          => isset($data['og_title'])        ? $data['og_title']        : (isset($data['title']) ? $data['title'] : ''),
            '_aioseo_og_description'    => isset($data['og_description'])  ? $data['og_description']  : (isset($data['description']) ? $data['description'] : ''),
            // Twitter mirrors OG
            '_aioseo_twitter_title'     => isset($data['title'])           ? $data['title']           : '',
            '_aioseo_twitter_description' => isset($data['description'])   ? $data['description']     : '',
            '_aioseo_twitter_use_og'    => '1',
        );
        if (!empty($data['og_image'])) {
            $meta['_aioseo_og_image_custom_url'] = esc_url_raw($data['og_image']);
            $meta['_aioseo_og_image_type']       = 'custom_image';
        }
        foreach ($meta as $key => $value) {
            if ($value !== '') update_term_meta($term_id, $key, $value);
        }
        // Keyphrases
        if (!empty($data['focus_keyphrase'])) {
            $kp = array(
                'focus'      => array('keyphrase' => $data['focus_keyphrase'], 'score' => 0, 'analysis' => new stdClass()),
                'additional' => array(),
            );
            foreach ((array) ($data['additional_keyphrases'] ?? array()) as $ak) {
                $kp['additional'][] = array('keyphrase' => $ak, 'score' => 0, 'analysis' => new stdClass());
            }
            update_term_meta($term_id, '_aioseo_keyphrases', wp_json_encode($kp));
            // Also write focus keyphrase to legacy key
            update_term_meta($term_id, '_aioseo_focus_keyphrase', $data['focus_keyphrase']);
        }
        // Try AIOSEO Term model if available
        if (class_exists('\\AIOSEO\\Plugin\\Common\\Models\\Term')) {
            try {
                $aioseoTerm = \AIOSEO\Plugin\Common\Models\Term::getTerm($term_id);
                if ($aioseoTerm) {
                    if (!empty($data['title']))       $aioseoTerm->title       = $data['title'];
                    if (!empty($data['description'])) $aioseoTerm->description = $data['description'];
                    if (!empty($data['focus_keyphrase'])) {
                        $kp = array(
                            'focus'      => array('keyphrase' => $data['focus_keyphrase'], 'score' => 0),
                            'additional' => array_map(function($ak){ return array('keyphrase'=>$ak,'score'=>0); }, (array)($data['additional_keyphrases']??[])),
                        );
                        $aioseoTerm->keyphrases = wp_json_encode($kp);
                    }
                    $aioseoTerm->og_title       = isset($data['og_title']) ? $data['og_title'] : ($data['title'] ?? '');
                    $aioseoTerm->og_description = isset($data['og_description']) ? $data['og_description'] : ($data['description'] ?? '');
                    $aioseoTerm->twitter_use_og = 1;
                    $aioseoTerm->save();
                }
            } catch (\Throwable $e) { /* silent fallback */ }
        }
    }
    
    /**
     * Filter AIO SEO title
     */
    public static function filter_title($title) {
        // Only override if we have custom data
        if (is_singular('travel_activity')) {
            $custom_title = get_post_meta(get_the_ID(), '_aioseo_title', true);
            if ($custom_title) return $custom_title;
            
            $post_title = get_the_title();
            $cities = wp_get_post_terms(get_the_ID(), 'travel_city');
            $city_name = !empty($cities) ? $cities[0]->name : '';
            
            return $post_title . ($city_name ? ' in ' . $city_name : '') . ' | Book Online';
        }
        
        if (is_singular('travel_destination')) {
            return 'Things to Do in ' . get_the_title() . ' | Tours, Attractions & Experiences';
        }
        
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            $country_id = get_term_meta($term->term_id, 'fth_parent_country', true);
            $country_name = '';
            if ($country_id) {
                $country = get_term($country_id, 'travel_country');
                if ($country && !is_wp_error($country)) {
                    $country_name = ', ' . $country->name;
                }
            }
            return 'Things to Do in ' . $term->name . $country_name . ' | Tours & Activities';
        }
        
        if (is_tax('travel_country')) {
            $term = get_queried_object();
            return 'Things to Do in ' . $term->name . ' | Top Destinations & Activities';
        }
        
        if (is_tax('travel_category')) {
            $term = get_queried_object();
            return $term->name . ' Tours & Activities | Book Online';
        }
        
        if (is_post_type_archive('travel_activity')) {
            return 'Things to Do | Tours, Attractions & Experiences';
        }
        
        return $title;
    }
    
    /**
     * Filter AIO SEO description
     */
    public static function filter_description($description) {
        if (is_singular('travel_activity')) {
            $custom_desc = get_post_meta(get_the_ID(), '_aioseo_description', true);
            if ($custom_desc) return $custom_desc;
            
            $excerpt = get_the_excerpt();
            if ($excerpt) return wp_trim_words($excerpt, 25, '...');
        }
        
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            $count = FTH_Templates::get_city_activity_count($term->term_id);
            return 'Discover ' . $count . ' amazing things to do in ' . $term->name . '. Book tours, activities, tickets & experiences. Best prices & instant confirmation.';
        }
        
        return $description;
    }
    
    /**
     * Add Schema.org markup
     */
    public static function add_schema($schema) {
        if (is_singular('travel_activity')) {
            $post_id = get_the_ID();
            $rating = get_post_meta($post_id, '_fth_rating', true);
            $review_count = get_post_meta($post_id, '_fth_review_count', true);
            $price = get_post_meta($post_id, '_fth_price', true);
            $currency = get_post_meta($post_id, '_fth_currency', true) ?: 'USD';
            $external_image = get_post_meta($post_id, '_fth_external_image', true);
            $duration = get_post_meta($post_id, '_fth_duration', true);
            
            $cities = wp_get_post_terms($post_id, 'travel_city');
            $city_name = !empty($cities) ? $cities[0]->name : '';
            
            // TouristAttraction Schema
            $attraction = array(
                '@type'       => 'TouristAttraction',
                'name'        => get_the_title(),
                'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 50),
                'url'         => get_permalink(),
            );
            
            if ($external_image) {
                $attraction['image'] = $external_image;
            } elseif (has_post_thumbnail()) {
                $attraction['image'] = get_the_post_thumbnail_url($post_id, 'large');
            }
            
            if ($city_name) {
                $attraction['address'] = array(
                    '@type'           => 'PostalAddress',
                    'addressLocality' => $city_name,
                );
            }
            
            if ($rating && $review_count) {
                $attraction['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => floatval($rating),
                    'reviewCount' => intval($review_count),
                    'bestRating'  => 5,
                    'worstRating' => 1,
                );
            }
            
            if ($price) {
                $attraction['offers'] = array(
                    '@type'         => 'Offer',
                    'price'         => floatval($price),
                    'priceCurrency' => $currency,
                    'availability'  => 'https://schema.org/InStock',
                    'url'           => get_permalink(),
                );
            }
            
            // Product Schema for better rich snippets
            $product = array(
                '@type'       => 'Product',
                'name'        => get_the_title(),
                'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 50),
                'url'         => get_permalink(),
                'category'    => 'Tours & Activities',
            );
            
            if ($external_image) {
                $product['image'] = $external_image;
            }
            
            if ($rating && $review_count) {
                $product['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => floatval($rating),
                    'reviewCount' => intval($review_count),
                    'bestRating'  => 5,
                    'worstRating' => 1,
                );
            }
            
            if ($price) {
                $product['offers'] = array(
                    '@type'         => 'Offer',
                    'price'         => floatval($price),
                    'priceCurrency' => $currency,
                    'availability'  => 'https://schema.org/InStock',
                );
            }
            
            $schema[] = $attraction;
            $schema[] = $product;
        }
        
        // City Page Schema
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            $hero_image = get_term_meta($term->term_id, 'fth_hero_image', true);
            
            $place = array(
                '@type'       => 'City',
                'name'        => $term->name,
                'description' => $term->description ?: 'Discover things to do in ' . $term->name,
                'url'         => get_term_link($term),
            );
            
            if ($hero_image) {
                $place['image'] = $hero_image;
            }
            
            $schema[] = $place;
        }
        
        return $schema;
    }
    
    /**
     * Add Facebook Open Graph tags
     */
    public static function add_facebook_tags($tags) {
        if (is_singular('travel_activity') || is_singular('travel_destination')) {
            $post_id = get_the_ID();
            $external_image = get_post_meta($post_id, '_fth_external_image', true);
            
            if ($external_image) {
                $tags['og:image'] = $external_image;
                $tags['og:image:width'] = '1200';
                $tags['og:image:height'] = '630';
            }
            
            $tags['og:type'] = 'product';
        }
        
        if (is_tax('travel_city')) {
            $term = get_queried_object();
            $hero_image = get_term_meta($term->term_id, 'fth_hero_image', true);
            
            if ($hero_image) {
                $tags['og:image'] = $hero_image;
            }
            
            $tags['og:type'] = 'website';
        }
        
        return $tags;
    }
    
    /**
     * Add Twitter Card tags
     */
    public static function add_twitter_tags($tags) {
        if (is_singular('travel_activity') || is_singular('travel_destination')) {
            $post_id = get_the_ID();
            $external_image = get_post_meta($post_id, '_fth_external_image', true);
            
            if ($external_image) {
                $tags['twitter:image'] = $external_image;
            }
            
            $tags['twitter:card'] = 'summary_large_image';
        }
        
        return $tags;
    }
    
    /**
     * Filter canonical URL
     */
    public static function filter_canonical($url) {
        // Ensure clean canonical URLs for taxonomy pages
        if (is_tax('travel_city') || is_tax('travel_country') || is_tax('travel_category')) {
            $term = get_queried_object();
            if ($term) {
                return get_term_link($term);
            }
        }
        
        return $url;
    }
    
    /**
     * Add custom AIOSEO tags
     */
    public static function add_custom_tags($tags) {
        $tags[] = array(
            'id'          => 'travel_city',
            'name'        => 'Travel City',
            'description' => 'The city name for travel activities',
        );
        
        $tags[] = array(
            'id'          => 'travel_price',
            'name'        => 'Activity Price',
            'description' => 'The starting price for the activity',
        );
        
        $tags[] = array(
            'id'          => 'travel_rating',
            'name'        => 'Activity Rating',
            'description' => 'The rating of the activity',
        );
        
        return $tags;
    }
    
    /**
     * Get custom tag values
     */
    public static function get_custom_tag_value($value, $tag, $id) {
        if (!is_singular(array('travel_activity', 'travel_destination'))) {
            return $value;
        }
        
        $post_id = get_the_ID();
        
        switch ($tag) {
            case 'travel_city':
                $cities = wp_get_post_terms($post_id, 'travel_city');
                return !empty($cities) ? $cities[0]->name : '';
                
            case 'travel_price':
                $price = get_post_meta($post_id, '_fth_price', true);
                $currency = get_post_meta($post_id, '_fth_currency', true) ?: 'USD';
                return $price ? $currency . ' ' . number_format($price, 2) : '';
                
            case 'travel_rating':
                $rating = get_post_meta($post_id, '_fth_rating', true);
                return $rating ? $rating . '/5' : '';
        }
        
        return $value;
    }
    
    /**
     * Admin notice for AIO SEO integration
     */
    public static function aioseo_integration_notice() {
        $screen = get_current_screen();
        
        if (strpos($screen->id, 'fth-') === false && 
            !in_array($screen->post_type, array('travel_activity', 'travel_destination', 'travel_hotel'))) {
            return;
        }
        
        if (!defined('AIOSEO_VERSION')) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Flavor Travel Hub:</strong> AIO SEO is not detected. Install and activate AIO SEO for automatic SEO optimization.';
            echo '</p></div>';
            return;
        }
        
        // Show success notice once
        if (!get_option('fth_aioseo_notice_dismissed')) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>Flavor Travel Hub:</strong> AIO SEO integration is active! SEO fields are automatically filled when you save activities and destinations.';
            echo '</p></div>';
            update_option('fth_aioseo_notice_dismissed', true);
        }
    }
}

// Initialize
add_action('init', array('FTH_AIOSEO_Integration', 'init'), 20);
