<?php
/**
 * Custom Post Types Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Post_Types {
    
    /**
     * Initialize post types
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_types'), 5);
    }
    
    /**
     * Register all custom post types
     */
    public static function register_post_types() {
        self::register_destination();
        self::register_activity();
        self::register_hotel();
    }
    
    /**
     * Register travel_destination post type
     */
    private static function register_destination() {
        $labels = array(
            'name'                  => 'Destinations',
            'singular_name'         => 'Destination',
            'menu_name'             => 'Destinations',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Destination',
            'edit_item'             => 'Edit Destination',
            'new_item'              => 'New Destination',
            'view_item'             => 'View Destination',
            'view_items'            => 'View Destinations',
            'search_items'          => 'Search Destinations',
            'not_found'             => 'No destinations found',
            'not_found_in_trash'    => 'No destinations found in trash',
            'all_items'             => 'All Destinations',
            'archives'              => 'Destination Archives',
            'attributes'            => 'Destination Attributes',
            'insert_into_item'      => 'Insert into destination',
            'uploaded_to_this_item' => 'Uploaded to this destination',
            'featured_image'        => 'Hero Image',
            'set_featured_image'    => 'Set hero image',
            'remove_featured_image' => 'Remove hero image',
            'use_featured_image'    => 'Use as hero image',
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // Will be added to custom menu
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'things-to-do/destination',
                'with_front' => false,
            ),
            'capability_type'     => 'post',
            'has_archive'         => 'things-to-do/destinations',
            'hierarchical'        => false,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-location-alt',
            'supports'            => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'custom-fields',
                'revisions',
            ),
            'taxonomies'          => array('travel_country', 'travel_city'),
        );
        
        register_post_type('travel_destination', $args);
    }
    
    /**
     * Register travel_activity post type
     */
    private static function register_activity() {
        $labels = array(
            'name'                  => 'Activities',
            'singular_name'         => 'Activity',
            'menu_name'             => 'Activities',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Activity',
            'edit_item'             => 'Edit Activity',
            'new_item'              => 'New Activity',
            'view_item'             => 'View Activity',
            'view_items'            => 'View Activities',
            'search_items'          => 'Search Activities',
            'not_found'             => 'No activities found',
            'not_found_in_trash'    => 'No activities found in trash',
            'all_items'             => 'All Activities',
            'archives'              => 'Activity Archives',
            'attributes'            => 'Activity Attributes',
            'insert_into_item'      => 'Insert into activity',
            'uploaded_to_this_item' => 'Uploaded to this activity',
            'featured_image'        => 'Activity Image',
            'set_featured_image'    => 'Set activity image',
            'remove_featured_image' => 'Remove activity image',
            'use_featured_image'    => 'Use as activity image',
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'things-to-do/activity',
                'with_front' => false,
            ),
            'capability_type'     => 'post',
            'has_archive'         => 'things-to-do/activities',
            'hierarchical'        => false,
            'menu_position'       => 26,
            'menu_icon'           => 'dashicons-tickets-alt',
            'supports'            => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'custom-fields',
                'revisions',
            ),
            'taxonomies'          => array('travel_country', 'travel_city', 'travel_category', 'travel_type'),
        );
        
        register_post_type('travel_activity', $args);
    }
    
    /**
     * Register travel_hotel post type
     */
    private static function register_hotel() {
        $labels = array(
            'name'                  => 'Hotels',
            'singular_name'         => 'Hotel',
            'menu_name'             => 'Hotels',
            'add_new'               => 'Add New',
            'add_new_item'          => 'Add New Hotel',
            'edit_item'             => 'Edit Hotel',
            'new_item'              => 'New Hotel',
            'view_item'             => 'View Hotel',
            'view_items'            => 'View Hotels',
            'search_items'          => 'Search Hotels',
            'not_found'             => 'No hotels found',
            'not_found_in_trash'    => 'No hotels found in trash',
            'all_items'             => 'All Hotels',
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'things-to-do/hotel',
                'with_front' => false,
            ),
            'capability_type'     => 'post',
            'has_archive'         => 'things-to-do/hotels',
            'hierarchical'        => false,
            'menu_position'       => 27,
            'menu_icon'           => 'dashicons-building',
            'supports'            => array(
                'title',
                'editor',
                'excerpt',
                'thumbnail',
                'custom-fields',
                'revisions',
            ),
            'taxonomies'          => array('travel_country', 'travel_city'),
        );
        
        register_post_type('travel_hotel', $args);
    }
}
