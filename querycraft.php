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

require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft.php';

/*
 * Since the main plugin file must have the plugin header at the top,
 * we do not declare a namespace here.
 */

/**
 * Initialize QueryCraft by instantiating the namespaced main class.
 */
function querycraft_init()
{
    \QueryCraft\QueryCraft::get_instance();
}
add_action('plugins_loaded', 'querycraft_init');

/**
 * AJAX callback for "Load More" or "Infinite Scroll" requests.
 */
function querycraft_load_more_callback()
{
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;

    $shortcode_params = [
        'pt'         => 'post',
        'display'    => 2,
        'paged'      => 'load_more',
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

    if (! empty($_POST['shortcode'])) {
        $json_string = wp_unslash($_POST['shortcode']);
        $data        = json_decode($json_string, true);
        if (is_array($data)) {
            $shortcode_params = wp_parse_args($data, $shortcode_params);
        }
    }

    require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft-query-builder.php';
    require_once QUERYCRAFT_PLUGIN_DIR . 'includes/template-loader.php';
    $query_args = \QueryCraft\QueryCraft_Query_Builder::build_query_args($shortcode_params);

    if (isset($shortcode_params['offset']) && (int) $shortcode_params['offset'] > 0) {
        $user_offset    = (int) $shortcode_params['offset'];
        $posts_per_page = (int) $shortcode_params['display'];
        $query_args['offset'] = $user_offset + (($page - 1) * $posts_per_page);
    } else {
        $query_args['paged'] = $page;
    }

    $query = new \WP_Query($query_args);

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

/**
 * Add a top-level admin menu for the QueryCraft Shortcode Generator.
 */
function querycraft_add_admin_menu()
{
    add_menu_page(
        'QueryCraft Shortcode Generator',
        'QueryCraft',
        'manage_options',
        'querycraft-admin',
        'querycraft_render_shortcode_generator_page',
        'dashicons-admin-customizer',
        26
    );
}
add_action('admin_menu', 'querycraft_add_admin_menu');

/**
 * Render the shortcode generator admin page.
 */
function querycraft_render_shortcode_generator_page()
{
    include_once QUERYCRAFT_PLUGIN_DIR . 'admin/shortcode-generator.php';
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
 * On plugin activation, create the 'querycraft' directories in the active theme
 * and add a sample CTA file (or restore them from backup if available).
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
 * and then remove it.
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
    $terms    = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
    ]);
    $options = [];
    if (! is_wp_error($terms)) {
        foreach ($terms as $term) {
            $options[] = [
                'id'   => $term->slug,
                'text' => $term->name,
            ];
        }
    }
    wp_send_json_success($options);
}
add_action('wp_ajax_querycraft_get_terms', 'querycraft_get_terms_callback');

/**
 * Add a sub-menu page for Developer Documentation.
 */
function querycraft_add_documentation_page()
{
    add_submenu_page(
        'querycraft-admin',
        'Developer Documentation',
        'Documentation',
        'manage_options',
        'querycraft-documentation',
        'querycraft_render_documentation_page'
    );
}
add_action('admin_menu', 'querycraft_add_documentation_page');

/**
 * Render the Developer Documentation page.
 */
function querycraft_render_documentation_page()
{
    include_once QUERYCRAFT_PLUGIN_DIR . 'admin/documentation.php';
}
