<?php

/**
 * Plugin Name: QueryCraft
 * Plugin URI:  https://github.com/amarasa/querycraft
 * Description: A flexible shortcode-based plugin for building dynamic post queries with multiple pagination options.
 * Version:     1.0.5
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
define('QUERYCRAFT_VERSION', '1.0.5');
define('QUERYCRAFT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUERYCRAFT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Define backup directory (within the plugin folder)
define('QUERYCRAFT_BACKUP_DIR', QUERYCRAFT_PLUGIN_DIR . 'backups');

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
        'pt'         => 'post',
        'display'    => 2,
        'paged'      => 'load_more', // or 'infinite_scroll'
        'orderby'    => 'date',
        'order'      => 'ASC',
        'status'     => 'publish',
        'taxonomy'   => '',
        'term'       => '',
        'meta_key'   => '',
        'meta_value' => '',
        'compare'    => '=',
        'template'   => 'title',
        'offset'     => 0,
    ];

    // If the AJAX request includes a "shortcode" param, decode & merge it.
    if (!empty($_POST['shortcode'])) {
        $json_string = wp_unslash($_POST['shortcode']);
        $data = json_decode($json_string, true);
        if (is_array($data)) {
            $shortcode_params = wp_parse_args($data, $shortcode_params);
        }
    }

    require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft-query-builder.php';
    require_once QUERYCRAFT_PLUGIN_DIR . 'includes/template-loader.php';
    $query_args = QueryCraft_Query_Builder::build_query_args($shortcode_params);

    // Adjust offset if provided.
    if (isset($shortcode_params['offset']) && (int)$shortcode_params['offset'] > 0) {
        $user_offset = (int)$shortcode_params['offset'];
        $posts_per_page = (int)$shortcode_params['display'];
        // Calculate a dynamic offset: user_offset + ((page - 1) * posts_per_page)
        $query_args['offset'] = $user_offset + (($page - 1) * $posts_per_page);
    } else {
        // Otherwise, set paged normally.
        $query_args['paged'] = $page;
    }

    $query = new WP_Query($query_args);

    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            querycraft_get_template($shortcode_params['template'], ['post' => get_post()]);
        }
    }
    $posts_html = ob_get_clean();
    wp_reset_postdata();

    wp_send_json_success(['posts' => $posts_html]);
}
add_action('wp_ajax_querycraft_load_more', 'querycraft_load_more_callback');
add_action('wp_ajax_nopriv_querycraft_load_more', 'querycraft_load_more_callback');



// Add a top-level admin menu for QueryCraft.
function querycraft_add_admin_menu()
{
    add_menu_page(
        'QueryCraft Shortcode Generator', // Page title
        'QueryCraft',                     // Menu title
        'manage_options',                 // Capability required
        'querycraft-admin',               // Menu slug
        'querycraft_render_shortcode_generator_page', // Callback to render the page
        'dashicons-admin-customizer',     // Dashicon icon class
        26                                // Position in the menu (lower numbers appear higher)
    );
}
add_action('admin_menu', 'querycraft_add_admin_menu');

function querycraft_render_shortcode_generator_page()
{
    include_once QUERYCRAFT_PLUGIN_DIR . 'admin/shortcode-generator.php';
}

/**
 * Recursively remove a directory and all its contents.
 *
 * @param string $dir Directory path to remove.
 */
function querycraft_rrmdir($dir)
{
    if (! file_exists($dir)) {
        return;
    }
    if (is_file($dir)) {
        @unlink($dir);
        return;
    }
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            querycraft_rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

/**
 * Recursively copy files & folders from source to destination.
 *
 * @param string $source Source directory.
 * @param string $destination Destination directory.
 */
function querycraft_recursive_copy($source, $destination)
{
    $dir = opendir($source);
    @mkdir($destination, 0755, true);
    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $srcPath = $source . '/' . $file;
        $dstPath = $destination . '/' . $file;
        if (is_dir($srcPath)) {
            querycraft_recursive_copy($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

/**
 * Attempts to move a directory; if rename fails, falls back to a recursive copy.
 *
 * @param string $source
 * @param string $destination
 */
function querycraft_move_or_copy($source, $destination)
{
    // Try rename first.
    if (@rename($source, $destination)) {
        return;
    }
    // Otherwise, copy recursively.
    querycraft_recursive_copy($source, $destination);
}

/**
 * On plugin activation, create the 'querycraft' directories in the active theme
 * and add a sample CTA file, or restore them from backup if available.
 */
function querycraft_on_activation()
{
    // Ensure our backup directory exists.
    if (! file_exists(QUERYCRAFT_BACKUP_DIR)) {
        wp_mkdir_p(QUERYCRAFT_BACKUP_DIR);
    }

    $theme_dir = get_stylesheet_directory();
    $theme_qc = $theme_dir . '/querycraft';
    $backup_qc = QUERYCRAFT_BACKUP_DIR . '/querycraft';

    // If a backup exists, restore it.
    if (file_exists($backup_qc)) {
        if (file_exists($theme_qc)) {
            querycraft_rrmdir($theme_qc);
        }
        querycraft_move_or_copy($backup_qc, $theme_qc);
        querycraft_rrmdir($backup_qc);
    } else {
        // No backup exists; create fresh folder structure.
        if (! file_exists($theme_qc)) {
            wp_mkdir_p($theme_qc);
        }
        if (! file_exists($theme_qc . '/templates')) {
            wp_mkdir_p($theme_qc . '/templates');
        }
        if (! file_exists($theme_qc . '/cta')) {
            wp_mkdir_p($theme_qc . '/cta');
        }
        $sample_cta_file = $theme_qc . '/cta/sample-cta.php';
        if (! file_exists($sample_cta_file)) {
            $sample_cta_content = "<?php
/**
 * Sample CTA Template for QueryCraft
 *
 * You can override or remove this file as needed.
 */
?>
<div class=\"sample-cta bg-blue-100 p-4 rounded\">
    <h3 class=\"font-bold text-blue-800 mb-2\">Sample CTA</h3>
    <p class=\"text-sm text-blue-700\">
        This is a sample CTA from QueryCraft. Feel free to customize this file!
    </p>
</div>";
            file_put_contents($sample_cta_file, $sample_cta_content);
        }
    }
}
register_activation_hook(__FILE__, 'querycraft_on_activation');

/**
 * On plugin deactivation, back up the 'querycraft' folder from the active theme
 * to the plugin's backup directory, then remove it from the theme.
 */
function querycraft_on_deactivation()
{
    $theme_dir = get_stylesheet_directory();
    $theme_qc  = $theme_dir . '/querycraft';
    $backup_qc = QUERYCRAFT_BACKUP_DIR . '/querycraft';

    if (! file_exists($theme_qc)) {
        return;
    }

    // Remove any existing backup.
    if (file_exists($backup_qc)) {
        querycraft_rrmdir($backup_qc);
    }

    // Move (or copy) the theme querycraft folder to the backup.
    querycraft_move_or_copy($theme_qc, $backup_qc);

    // Remove the original querycraft folder from the theme.
    querycraft_rrmdir($theme_qc);
}
register_deactivation_hook(__FILE__, 'querycraft_on_deactivation');


add_action('wp_ajax_querycraft_get_terms', 'querycraft_get_terms_callback');
function querycraft_get_terms_callback()
{
    if (!isset($_GET['taxonomy'])) {
        wp_send_json_error('No taxonomy provided');
    }
    $taxonomy = sanitize_text_field($_GET['taxonomy']);
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ]);
    $options = [];
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $options[] = [
                'id'   => $term->slug,
                'text' => $term->name,
            ];
        }
    }
    wp_send_json_success($options);
}

function querycraft_add_documentation_page()
{
    add_submenu_page(
        'querycraft-admin',                   // Parent slug (your QueryCraft top-level menu)
        'Developer Documentation',            // Page title
        'Documentation',                      // Menu title
        'manage_options',                     // Capability
        'querycraft-documentation',           // Menu slug
        'querycraft_render_documentation_page' // Callback function
    );
}
add_action('admin_menu', 'querycraft_add_documentation_page');

function querycraft_render_documentation_page()
{
    include_once QUERYCRAFT_PLUGIN_DIR . 'admin/documentation.php';
}
