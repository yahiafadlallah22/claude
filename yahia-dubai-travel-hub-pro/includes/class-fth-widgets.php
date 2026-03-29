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
            echo '<ul class="fth-widget-categories-list">';
            foreach ($categories as $category) {
                $icon = get_term_meta($category->term_id, 'fth_icon', true);
                echo '<li>';
                echo '<a href="' . esc_url(get_term_link($category)) . '" style="color: ' . esc_attr($primary_color) . ';">';
                if ($icon) {
                    echo '<i class="fa ' . esc_attr($icon) . '"></i> ';
                }
                echo esc_html($category->name);
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
