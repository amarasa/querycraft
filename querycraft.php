<?php
/*
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

define('QUERYCRAFT_VERSION', '1.0.5');
define('QUERYCRAFT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUERYCRAFT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QUERYCRAFT_BACKUP_DIR', QUERYCRAFT_PLUGIN_DIR . 'backups');

/**
 * Register the QueryCraft CTA custom post type.
 */
function querycraft_register_cta_post_type()
{
    $labels = array(
        'name'               => 'CTAs',
        'singular_name'      => 'CTA',
        'menu_name'          => 'CTAs',
        'name_admin_bar'     => 'CTA',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New CTA',
        'new_item'           => 'New CTA',
        'edit_item'          => 'Edit CTA',
        'view_item'          => 'View CTA',
        'all_items'          => 'All CTAs',
        'search_items'       => 'Search CTAs',
        'parent_item_colon'  => 'Parent CTAs:',
        'not_found'          => 'No CTAs found.',
        'not_found_in_trash' => 'No CTAs found in Trash.',
    );
    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        // Make the CPT appear as a submenu under our main QueryCraft menu.
        'show_in_menu'       => 'querycraft-admin',
        'query_var'          => true,
        'rewrite'            => array('slug' => 'querycraft-cta'),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 25,
        'supports'           => array('title', 'editor'),
        'show_in_rest'       => true, // Gutenberg support.
    );
    register_post_type('querycraft_cta', $args);
}
add_action('init', 'querycraft_register_cta_post_type');

// Load the main QueryCraft class.
require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft.php';

/*
 * The main plugin file remains in the global namespace.
 */

/**
 * Create the main admin menu for QueryCraft.
 * This top-level menu will have the following submenus:
 * - Shortcode Builder (the main page)
 * - CTAs (from the CPT registration)
 * - Documentation
 */
function querycraft_add_admin_menu()
{
    // Create the top-level menu.
    add_menu_page(
        'QueryCraft',                      // Page title.
        'QueryCraft',                      // Menu title.
        'manage_options',                  // Capability.
        'querycraft-admin',                // Menu slug.
        'querycraft_render_shortcode_generator_page', // Callback.
        'dashicons-admin-customizer',      // Icon.
        26                                 // Position.
    );
    // Add a submenu for Shortcode Builder (points to the same page as the top-level).
    add_submenu_page(
        'querycraft-admin',
        'Shortcode Builder',
        'Shortcode Builder',
        'manage_options',
        'querycraft-admin',
        'querycraft_render_shortcode_generator_page'
    );
}
add_action('admin_menu', 'querycraft_add_admin_menu');

/**
 * Add a submenu for Developer Documentation.
 */
function querycraft_add_documentation_page()
{
    add_submenu_page(
        'querycraft-admin',
        'Documentation',
        'Documentation',
        'manage_options',
        'querycraft-documentation',
        'querycraft_render_documentation_page'
    );
}
add_action('admin_menu', 'querycraft_add_documentation_page');

/**
 * Render the Shortcode Builder page.
 */
function querycraft_render_shortcode_generator_page()
{
    include_once QUERYCRAFT_PLUGIN_DIR . 'admin/shortcode-generator.php';
}

/**
 * Render the Developer Documentation page.
 */
function querycraft_render_documentation_page()
{
    include_once QUERYCRAFT_PLUGIN_DIR . 'admin/documentation.php';
}

/**
 * Recursively remove a directory and all its contents.
 *
 * @param string $dir Directory path.
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
 * Recursively copy files and folders from source to destination.
 *
 * @param string $source      Source directory.
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
 * Attempt to move a directory; if rename fails, fall back to a recursive copy.
 *
 * @param string $source      Source directory.
 * @param string $destination Destination directory.
 */
function querycraft_move_or_copy($source, $destination)
{
    if (@rename($source, $destination)) {
        return;
    }
    querycraft_recursive_copy($source, $destination);
}

/**
 * On plugin activation, create necessary directories in the active theme.
 * For CTAs, we no longer use a plugin folder but still create a default physical CTA if needed.
 */
function querycraft_on_activation()
{
    if (! file_exists(QUERYCRAFT_BACKUP_DIR)) {
        wp_mkdir_p(QUERYCRAFT_BACKUP_DIR);
    }

    $theme_dir = get_stylesheet_directory();
    $theme_qc  = $theme_dir . '/querycraft';
    $backup_qc = QUERYCRAFT_BACKUP_DIR . '/querycraft';

    if (file_exists($backup_qc)) {
        if (file_exists($theme_qc)) {
            querycraft_rrmdir($theme_qc);
        }
        querycraft_move_or_copy($backup_qc, $theme_qc);
        querycraft_rrmdir($backup_qc);
    } else {
        if (! file_exists($theme_qc)) {
            wp_mkdir_p($theme_qc);
        }
        if (! file_exists($theme_qc . '/templates')) {
            wp_mkdir_p($theme_qc . '/templates');
        }
        // For physical CTAs, ensure a cta folder exists.
        $cta_folder = $theme_qc . '/cta';
        if (! file_exists($cta_folder)) {
            wp_mkdir_p($cta_folder);
        }
        $sample_cta_file = $cta_folder . '/sample-cta.php';
        if (! file_exists($sample_cta_file)) {
            $sample_cta_content = "<?php
/**
 * Sample CTA Template (Physical File) for QueryCraft
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
 * On plugin deactivation, back up the 'querycraft' folder from the active theme and then remove it.
 */
function querycraft_on_deactivation()
{
    $theme_dir = get_stylesheet_directory();
    $theme_qc  = $theme_dir . '/querycraft';
    $backup_qc = QUERYCRAFT_BACKUP_DIR . '/querycraft';

    if (! file_exists($theme_qc)) {
        return;
    }

    if (file_exists($backup_qc)) {
        querycraft_rrmdir($backup_qc);
    }

    querycraft_move_or_copy($theme_qc, $backup_qc);
    querycraft_rrmdir($theme_qc);
}
register_deactivation_hook(__FILE__, 'querycraft_on_deactivation');

/**
 * AJAX callback to get taxonomy terms.
 */
function querycraft_get_terms_callback()
{
    if (! isset($_GET['taxonomy'])) {
        wp_send_json_error('No taxonomy provided');
    }
    $taxonomy = sanitize_text_field($_GET['taxonomy']);
    $terms    = get_terms(array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ));
    $options = array();
    if (! is_wp_error($terms)) {
        foreach ($terms as $term) {
            $options[] = array(
                'id'   => $term->slug,
                'text' => $term->name,
            );
        }
    }
    wp_send_json_success($options);
}
add_action('wp_ajax_querycraft_get_terms', 'querycraft_get_terms_callback');
