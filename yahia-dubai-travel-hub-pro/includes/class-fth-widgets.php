<?php
/**
 * Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_Widget')) {
    $fth_widget_file = ABSPATH . WPINC . '/class-wp-widget.php';
    if (file_exists($fth_widget_file)) {
        require_once $fth_widget_file;
    }
}

class FTH_Widgets {
    
    /**
     * Initialize widgets
     */
    public static function init() {
        add_action('widgets_init', array(__CLASS__, 'register_widgets'));
    }
    
    /**
     * Register widgets
     */
    public static function register_widgets() {
        register_widget('FTH_Widget_Featured_Cities');
        register_widget('FTH_Widget_Featured_Activities');
        register_widget('FTH_Widget_Categories');
        register_widget('FTH_Widget_Search');
    }
}

/**
 * Featured Cities Widget
 */
class FTH_Widget_Featured_Cities extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'fth_featured_cities',
            'Flavor Travel - Featured Cities',
            array('description' => 'Display featured travel destinations')
        );
    }
    
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $count = isset($instance['count']) ? $instance['count'] : 4;
        
        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        $cities = FTH_Taxonomies::get_cities(array(
            'hide_empty' => true,
            'number'     => $count,
        ));
        
        if (!empty($cities)) {
            echo '<div class="fth-widget-cities">';
            foreach ($cities as $city) {
                echo FTH_Templates::get_city_card($city, array('card_class' => 'fth-widget-city-card'));
            }
            echo '</div>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Popular Destinations';
        $count = isset($instance['count']) ? $instance['count'] : 4;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('count'); ?>">Number of cities:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="number" value="<?php echo esc_attr($count); ?>" min="1" max="12">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['count'] = absint($new_instance['count']);
        return $instance;
    }
}

/**
 * Featured Activities Widget
 */
class FTH_Widget_Featured_Activities extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'fth_featured_activities',
            'Flavor Travel - Featured Activities',
            array('description' => 'Display featured travel activities')
        );
    }
    
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $count = isset($instance['count']) ? $instance['count'] : 4;
        
        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        $activities = FTH_Search::get_featured_activities($count);
        
        if ($activities->have_posts()) {
            echo '<div class="fth-widget-activities">';
            while ($activities->have_posts()) {
                $activities->the_post();
                echo FTH_Templates::get_activity_card(get_the_ID(), array(
                    'card_class'    => 'fth-widget-activity-card',
                    'show_category' => false,
                ));
            }
            wp_reset_postdata();
            echo '</div>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Featured Activities';
        $count = isset($instance['count']) ? $instance['count'] : 4;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('count'); ?>">Number of activities:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="number" value="<?php echo esc_attr($count); ?>" min="1" max="12">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['count'] = absint($new_instance['count']);
        return $instance;
    }
}

/**
 * Categories Widget
 */
class FTH_Widget_Categories extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'fth_categories',
            'Flavor Travel - Categories',
            array('description' => 'Display travel activity categories')
        );
    }
    
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        $categories = FTH_Taxonomies::get_categories(array('hide_empty' => false));

        if (!empty($categories)) {
            $primary_color = Flavor_Travel_Hub::get_primary_color();
            // Inline CSS for animated emoji icons — injected once per widget render
            static $fth_cat_widget_css_done = false;
            if (!$fth_cat_widget_css_done) {
                $fth_cat_widget_css_done = true;
                echo '<style>
@keyframes fth-emoji-wobble{0%,100%{transform:rotate(0) scale(1)}25%{transform:rotate(-14deg) scale(1.18)}75%{transform:rotate(14deg) scale(1.18)}}
@keyframes fth-emoji-pop{0%{transform:scale(1)}50%{transform:scale(1.35)}100%{transform:scale(1)}}
.fth-widget-categories-list{list-style:none;padding:0;margin:0}
.fth-widget-categories-list li{margin-bottom:4px}
.fth-widget-categories-list a{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;transition:background .2s,color .2s}
.fth-widget-categories-list a:hover{background:rgba(41,137,192,.08)}
.fth-widget-categories-list a:hover .fth-cat-emoji{animation:fth-emoji-wobble .5s ease-in-out}
.fth-cat-emoji{display:inline-block;font-size:18px;line-height:1;width:24px;text-align:center;flex-shrink:0}
.fth-cat-count{margin-left:auto;background:#f0f4f8;border-radius:999px;font-size:11px;color:#666;padding:1px 7px;font-weight:500}
</style>';
            }
            echo '<ul class="fth-widget-categories-list">';
            foreach ($categories as $category) {
                // Prefer emoji stored in term meta (fth_icon); fall back to taxonomy map
                $icon_meta = get_term_meta($category->term_id, 'fth_icon', true);
                // If it's a FontAwesome class (fa-*), ignore it and use the emoji map
                if ($icon_meta && strpos($icon_meta, 'fa-') === false) {
                    $emoji = $icon_meta; // stored as an emoji character directly
                } else {
                    $emoji = FTH_Taxonomies::get_emoji($category->slug, 'travel_category');
                }
                $count = (int) $category->count;
                echo '<li>';
                echo '<a href="' . esc_url(get_term_link($category)) . '" style="color:' . esc_attr($primary_color) . ';" title="' . esc_attr($category->name) . '">';
                echo '<span class="fth-cat-emoji" aria-hidden="true">' . esc_html($emoji) . '</span>';
                echo '<span>' . esc_html($category->name) . '</span>';
                if ($count > 0) {
                    echo '<span class="fth-cat-count">' . esc_html($count) . '</span>';
                }
                echo '</a>';
                echo '</li>';
            }
            echo '</ul>';
        }

        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Categories';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
}

/**
 * Search Widget
 */
class FTH_Widget_Search extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'fth_search',
            'Flavor Travel - Search',
            array('description' => 'Travel activities search form')
        );
    }
    
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        
        echo $args['before_widget'];
        
        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        echo FTH_Search::get_search_form(array(
            'form_class'    => 'fth-widget-search-form',
            'show_city'     => true,
            'show_category' => false,
        ));
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Find Activities';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
}
