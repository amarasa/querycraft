<?php

/**
 * Plugin Name: QueryCraft
 * Plugin URI:  https://github.com/amarasa/querycraft
 * Description: A flexible shortcode-based plugin for building dynamic post queries with multiple pagination options.
 * Version:     1.0.0
 * Author:      Angelo Marasa
 * Author URI:  https://github.com/amarasa
 * License:     GPL-2.0+
 * Text Domain: querycraft
 */

require 'puc/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/amarasa/querycraft',
    __FILE__,
    'querycraft'
);

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin version and paths.
define('QUERYCRAFT_VERSION', '1.0.0');
define('QUERYCRAFT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUERYCRAFT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main plugin class.
require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft.php';

/**
 * Initialize the plugin.
 */
function querycraft_init()
{
    QueryCraft::get_instance();
}
add_action('plugins_loaded', 'querycraft_init');

/**
 * AJAX callback for "Load More" or "Infinite Scroll" requests.
 */
function querycraft_load_more_callback()
{
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;

    // Default shortcode params (in case none are passed).
    $shortcode_params = [
        'pt'              => 'post',
        'display'         => 2,
        'paged'           => 'load_more', // or 'infinite_scroll'
        'orderby'         => 'date',
        'order'           => 'ASC',
        'status'          => 'publish',
        'taxonomy'        => '',
        'term'            => '',
        'meta_key'        => '',
        'meta_value'      => '',
        'compare'         => '=',
    ];

    // If the AJAX request includes a "shortcode" param, decode & merge it.
    if (! empty($_POST['shortcode'])) {
        $json_string = wp_unslash($_POST['shortcode']);
        $data = json_decode($json_string, true);
        if (is_array($data)) {
            $shortcode_params = wp_parse_args($data, $shortcode_params);
        }
    }

    require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft-query-builder.php';
    $query_args = QueryCraft_Query_Builder::build_query_args($shortcode_params);

    // Override the numeric paged value for subsequent pages.
    $query_args['paged'] = $page;

    $query = new WP_Query($query_args);

    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            echo '<li>' . get_the_title() . '</li>';
        }
    }
    $posts_html = ob_get_clean();
    wp_reset_postdata();

    wp_send_json_success(['posts' => $posts_html]);
}
add_action('wp_ajax_querycraft_load_more', 'querycraft_load_more_callback');
add_action('wp_ajax_nopriv_querycraft_load_more', 'querycraft_load_more_callback');
