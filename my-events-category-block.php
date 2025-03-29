<?php
/**
 * Plugin Name: TEC Category Selector Block
 * Description: A Gutenberg block for The Events Calendar to display events from selected categories
 * Version: 1.0.0
 * Author: Dan Stephenson
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the block
 */
function tec_category_selector_block_init() {
    // Register the block using the metadata loaded from the block.json file
    register_block_type(__DIR__, array(
        'render_callback' => 'tec_category_selector_render_callback'
    ));
	
	// Enqueue the stylesheet
    wp_enqueue_style(
        'tec-category-selector-style',
        plugins_url('my-events-calendar-block.css', __FILE__),
        array(),
        '1.0.0'
    );

}
add_action('init', 'tec_category_selector_block_init');

/**
 * Server-side rendering for the block
 */
function tec_category_selector_render_callback($attributes) {
    // Check if The Events Calendar is active
    if (!class_exists('Tribe__Events__Main')) {
        return '<p>The Events Calendar plugin is required for this block to work.</p>';
    }

    // Get selected categories
    $selected_categories = isset($attributes['selectedCategories']) ? $attributes['selectedCategories'] : [];
    
    // Set up arguments for the query
    $args = [
        'post_type' => 'tribe_events',
        'posts_per_page' => isset($attributes['numberOfEvents']) ? $attributes['numberOfEvents'] : 5,
        'orderby' => 'event_date',
        'order' => 'ASC',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_EventStartDate',
                'value' => date('Y-m-d H:i:s'),
                'compare' => '>=',
                'type' => 'DATETIME'
            ]
        ]
    ];

    // Add category filter if categories are selected
    if (!empty($selected_categories)) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'tribe_events_cat',
                'field' => 'term_id',
                'terms' => $selected_categories,
            ]
        ];
    }

    // Query the events
    $events_query = new WP_Query($args);
    
    // Start output buffering
    ob_start();
    
    if ($events_query->have_posts()) {
        echo '<div class="tec-category-events-list">';
        
        while ($events_query->have_posts()) {
            $events_query->the_post();
            $event_id = get_the_ID();
            $event_title = get_the_title();
            $event_link = get_permalink();
            $event_start_date = tribe_get_start_date($event_id, false, 'F j, Y');
            $event_start_time = tribe_get_start_date($event_id, false, 'g:i a');
            
            echo '<div class="tec-event-item">';
            echo '<h3 class="tec-event-title"><a href="' . esc_url($event_link) . '">' . esc_html($event_title) . '</a></h3>';
            echo '<div class="tec-event-date">' . esc_html($event_start_date) . ' at ' . esc_html($event_start_time) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    } else {
        echo '<p class="tec-no-events">No upcoming events found in the selected categories.</p>';
    }
    
    // Reset post data
    wp_reset_postdata();
    
    // Return the buffered output
    return ob_get_clean();
}