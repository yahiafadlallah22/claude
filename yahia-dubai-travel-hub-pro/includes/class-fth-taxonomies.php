<?php
/**
 * Custom Taxonomies Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Taxonomies {
    
    /**
     * Initialize taxonomies
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_taxonomies'), 5);
    }
    
    /**
     * Register all custom taxonomies
     */
    public static function register_taxonomies() {
        self::register_country();
        self::register_city();
        self::register_category();
        self::register_type();
    }
    
    /**
     * Register travel_country taxonomy
     */
    private static function register_country() {
        $labels = array(
            'name'                       => 'Countries',
            'singular_name'              => 'Country',
            'menu_name'                  => 'Countries',
            'all_items'                  => 'All Countries',
            'edit_item'                  => 'Edit Country',
            'view_item'                  => 'View Country',
            'update_item'                => 'Update Country',
            'add_new_item'               => 'Add New Country',
            'new_item_name'              => 'New Country Name',
            'search_items'               => 'Search Countries',
            'popular_items'              => 'Popular Countries',
            'separate_items_with_commas' => 'Separate countries with commas',
            'add_or_remove_items'        => 'Add or remove countries',
            'choose_from_most_used'      => 'Choose from most used countries',
            'not_found'                  => 'No countries found',
            'no_terms'                   => 'No countries',
            'items_list_navigation'      => 'Countries list navigation',
            'items_list'                 => 'Countries list',
            'back_to_items'              => 'Back to Countries',
        );
        
        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => false,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'hierarchical'      => true,
            'rewrite'           => array(
                'slug'         => 'things-to-do/country',
                'with_front'   => false,
                'hierarchical' => false,
            ),
            'query_var'         => true,
        );
        
        register_taxonomy('travel_country', array('travel_destination', 'travel_activity', 'travel_hotel'), $args);
    }
    
    /**
     * Register travel_city taxonomy
     */
    private static function register_city() {
        $labels = array(
            'name'                       => 'Cities',
            'singular_name'              => 'City',
            'menu_name'                  => 'Cities',
            'all_items'                  => 'All Cities',
            'edit_item'                  => 'Edit City',
            'view_item'                  => 'View City',
            'update_item'                => 'Update City',
            'add_new_item'               => 'Add New City',
            'new_item_name'              => 'New City Name',
            'search_items'               => 'Search Cities',
            'popular_items'              => 'Popular Cities',
            'separate_items_with_commas' => 'Separate cities with commas',
            'add_or_remove_items'        => 'Add or remove cities',
            'choose_from_most_used'      => 'Choose from most used cities',
            'not_found'                  => 'No cities found',
            'no_terms'                   => 'No cities',
            'items_list_navigation'      => 'Cities list navigation',
            'items_list'                 => 'Cities list',
            'back_to_items'              => 'Back to Cities',
        );
        
        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => false,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'hierarchical'      => true,
            'rewrite'           => array(
                'slug'         => 'things-to-do',
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'query_var'         => true,
        );
        
        register_taxonomy('travel_city', array('travel_destination', 'travel_activity', 'travel_hotel'), $args);
    }
    
    /**
     * Register travel_category taxonomy
     */
    private static function register_category() {
        $labels = array(
            'name'                       => 'Activity Categories',
            'singular_name'              => 'Category',
            'menu_name'                  => 'Categories',
            'all_items'                  => 'All Categories',
            'edit_item'                  => 'Edit Category',
            'view_item'                  => 'View Category',
            'update_item'                => 'Update Category',
            'add_new_item'               => 'Add New Category',
            'new_item_name'              => 'New Category Name',
            'search_items'               => 'Search Categories',
            'popular_items'              => 'Popular Categories',
            'separate_items_with_commas' => 'Separate categories with commas',
            'add_or_remove_items'        => 'Add or remove categories',
            'choose_from_most_used'      => 'Choose from most used categories',
            'not_found'                  => 'No categories found',
            'no_terms'                   => 'No categories',
            'items_list_navigation'      => 'Categories list navigation',
            'items_list'                 => 'Categories list',
            'back_to_items'              => 'Back to Categories',
        );
        
        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'hierarchical'      => true,
            'rewrite'           => array(
                'slug'         => 'things-to-do/category',
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'query_var'         => true,
        );
        
        register_taxonomy('travel_category', array('travel_activity'), $args);
    }
    
    /**
     * Register travel_type taxonomy
     */
    private static function register_type() {
        $labels = array(
            'name'                       => 'Activity Types',
            'singular_name'              => 'Type',
            'menu_name'                  => 'Types',
            'all_items'                  => 'All Types',
            'edit_item'                  => 'Edit Type',
            'view_item'                  => 'View Type',
            'update_item'                => 'Update Type',
            'add_new_item'               => 'Add New Type',
            'new_item_name'              => 'New Type Name',
            'search_items'               => 'Search Types',
            'popular_items'              => 'Popular Types',
            'separate_items_with_commas' => 'Separate types with commas',
            'add_or_remove_items'        => 'Add or remove types',
            'choose_from_most_used'      => 'Choose from most used types',
            'not_found'                  => 'No types found',
            'no_terms'                   => 'No types',
            'items_list_navigation'      => 'Types list navigation',
            'items_list'                 => 'Types list',
            'back_to_items'              => 'Back to Types',
        );
        
        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => true,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,
            'hierarchical'      => false,
            'rewrite'           => array(
                'slug'         => 'things-to-do/type',
                'with_front'   => false,
            ),
            'query_var'         => true,
        );
        
        register_taxonomy('travel_type', array('travel_activity'), $args);
    }
    
    /**
     * Get all countries
     */
    public static function get_countries($args = array()) {
        $defaults = array(
            'taxonomy'   => 'travel_country',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );
        
        return get_terms(wp_parse_args($args, $defaults));
    }
    
    /**
     * Get all cities
     */
    public static function get_cities($args = array()) {
        $defaults = array(
            'taxonomy'   => 'travel_city',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );
        
        return get_terms(wp_parse_args($args, $defaults));
    }
    
    /**
     * Get cities by country
     */
    public static function get_cities_by_country($country_id) {
        return get_terms(array(
            'taxonomy'   => 'travel_city',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key'   => 'fth_parent_country',
                    'value' => $country_id,
                ),
            ),
        ));
    }
    
    /**
     * Get all categories
     */
    public static function get_categories($args = array()) {
        $defaults = array(
            'taxonomy'   => 'travel_category',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );
        
        return get_terms(wp_parse_args($args, $defaults));
    }
    
    /**
     * Get all types
     */
    public static function get_types($args = array()) {
        $defaults = array(
            'taxonomy'   => 'travel_type',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );
        
        return get_terms(wp_parse_args($args, $defaults));
    }
}
