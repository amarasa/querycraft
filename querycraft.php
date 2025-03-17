<?php
/*
 * Plugin Name: QueryCraft
 * Plugin URI:  https://github.com/amarasa/querycraft
 * Description: A flexible shortcode-based plugin for building dynamic post queries with multiple pagination options.
 * Version:     1.1.3
 * Author:      Angelo Marasa
 * Author URI:  https://github.com/amarasa
 * License:     GPL-2.0+
 * Text Domain: querycraft
 */

require 'puc/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'http://206.189.194.86/api/license/verify', // Your licensing system API endpoint
    __FILE__,
    'querycraft'
);

// Retrieve the license key from the stored option.
$myUpdateChecker->addQueryArgFilter(function (array $queryArgs) {
    $license_key = get_option('querycraft_license_key', '');
    $queryArgs['license_key'] = $license_key;
    $queryArgs['plugin_slug']  = 'querycraft';
    $queryArgs['domain']       = home_url();
    return $queryArgs;
});

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('QUERYCRAFT_VERSION', '1.1.2');
define('QUERYCRAFT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUERYCRAFT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QUERYCRAFT_BACKUP_DIR', QUERYCRAFT_PLUGIN_DIR . 'backups');

/**
 * Check whether the stored license key is valid.
 * Caches the result for one hour to reduce API calls.
 *
 * @return bool True if valid; false otherwise.
 */
function querycraft_is_license_valid()
{
    $cached = get_transient('querycraft_license_valid');
    if (false !== $cached) {
        return $cached;
    }
    $license_key = get_option('querycraft_license_key', '');
    if (empty($license_key)) {
        set_transient('querycraft_license_valid', false, HOUR_IN_SECONDS);
        return false;
    }
    $response = wp_remote_post('http://206.189.194.86/api/license/verify', array(
        'timeout' => 15,
        'body'    => array(
            'license_key' => $license_key,
            'plugin_slug' => 'querycraft',
            'domain'      => home_url(),
        ),
    ));
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        set_transient('querycraft_license_valid', false, HOUR_IN_SECONDS);
        return false;
    }
    $license_data = json_decode(wp_remote_retrieve_body($response), true);
    $valid = (! empty($license_data) && ! empty($license_data['valid']) && $license_data['valid'] === true);
    set_transient('querycraft_license_valid', $valid, HOUR_IN_SECONDS);
    return $valid;
}

/**
 * On admin load, check license validity.
 * If the license key is missing, display an admin notice.
 */
function querycraft_admin_license_check()
{
    if (! is_admin()) {
        return;
    }
    if (empty(get_option('querycraft_license_key', ''))) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' .
                __('QueryCraft is currently disabled because a valid license key is required. Please visit the License Settings page to enter a valid key.', 'querycraft') .
                '</p></div>';
        });
    }
}
add_action('admin_init', 'querycraft_admin_license_check');

/**
 * Register the QueryCraft CTA custom post type.
 * This CPT becomes the top-level "QueryCraft" menu item.
 */
function querycraft_register_cta_post_type()
{
    $labels = array(
        'name'               => 'QueryCraft CTAs',
        'singular_name'      => 'QueryCraft CTA',
        'menu_name'          => 'QueryCraft',
        'name_admin_bar'     => 'QueryCraft CTA',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New QueryCraft CTA',
        'new_item'           => 'New QueryCraft CTA',
        'edit_item'          => 'Edit QueryCraft CTA',
        'view_item'          => 'View QueryCraft CTA',
        'all_items'          => 'All CTAs',
        'search_items'       => 'Search QueryCraft CTAs',
        'parent_item_colon'  => 'Parent QueryCraft CTAs:',
        'not_found'          => 'No CTAs found.',
        'not_found_in_trash' => 'No CTAs found in Trash.',
    );
    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'querycraft-cta'),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'supports'           => array('title', 'editor'),
        'show_in_rest'       => true,
    );
    register_post_type('querycraft_cta', $args);
}
add_action('init', 'querycraft_register_cta_post_type');

/**
 * Add a License Settings submenu page.
 * This page lets administrators enter, update, or remove their license key.
 */
function querycraft_add_license_settings_page()
{
    add_submenu_page(
        'edit.php?post_type=querycraft_cta',
        'License Settings',
        'License Settings',
        'manage_options',
        'querycraft-license-settings',
        'querycraft_render_license_settings_page'
    );
}
add_action('admin_menu', 'querycraft_add_license_settings_page');

/**
 * Render the License Settings page.
 */
function querycraft_render_license_settings_page()
{
    if (! current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'querycraft'));
    }

    // Process form submission for updating the license.
    if (isset($_POST['update_license'])) {
        check_admin_referer('querycraft_license_settings');
        $new_key = sanitize_text_field($_POST['querycraft_license_key']);
        $response = wp_remote_post('http://206.189.194.86/api/license/validate', [
            'body'    => [
                'license_key' => $new_key,
                'plugin_slug' => 'querycraft',
                'domain'      => home_url(),
            ],
            'timeout' => 15,
        ]);
        if (is_wp_error($response)) {
            echo '<div class="error"><p>' . __('There was an error contacting the licensing server. Please try again later.', 'querycraft') . '</p></div>';
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code == 200) {
                update_option('querycraft_license_key', $new_key);
                delete_transient('querycraft_license_valid'); // Clear cached validation.
                echo '<div class="updated"><p>' . __('License key updated successfully.', 'querycraft') . '</p></div>';
            } elseif ($status_code == 404) {
                echo '<div class="error"><p>' . __('License key is invalid. Please enter a valid license key.', 'querycraft') . '</p></div>';
            } elseif ($status_code == 403) {
                echo '<div class="error"><p>' . __('License key is inactive or the activation limit has been reached.', 'querycraft') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . __('Unexpected response from licensing server.', 'querycraft') . '</p></div>';
            }
        }
    }

    // Process form submission for removing the license.
    if (isset($_POST['remove_license'])) {
        check_admin_referer('querycraft_license_settings');
        $current_key = get_option('querycraft_license_key', '');
        if (! empty($current_key)) {
            error_log('QueryCraft Remove License: Current key: ' . $current_key);
            $response = wp_remote_post('http://206.189.194.86/api/license/deactivate', [
                'body'    => [
                    'license_key' => $current_key,
                    'plugin_slug' => 'querycraft',
                    'domain'      => home_url(),
                ],
                'timeout' => 15,
            ]);
            error_log('QueryCraft Remove License Response: ' . print_r($response, true));
            if (! is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
                delete_option('querycraft_license_key');
                delete_transient('querycraft_license_valid');
                echo '<div class="updated"><p>' . __('License removed successfully. QueryCraft is now disabled until a valid license key is entered.', 'querycraft') . '</p></div>';
            } else {
                echo '<div class="error"><p>' . __('There was an error removing the license. Please try again.', 'querycraft') . '</p></div>';
            }
        }
    }

    $current_key = esc_attr(get_option('querycraft_license_key', ''));
?>
    <div class="wrap">
        <h1><?php _e('QueryCraft License Settings', 'querycraft'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('querycraft_license_settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('License Key', 'querycraft'); ?></th>
                    <td>
                        <input type="text" name="querycraft_license_key" value="<?php echo $current_key; ?>" style="width: 400px;" />
                        <p class="description"><?php _e('Enter your valid license key for QueryCraft. The license will be validated before saving.', 'querycraft'); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Update License', 'primary', 'update_license'); ?>
            <?php if (! empty($current_key)) : ?>
                <?php submit_button('Remove License', 'secondary', 'remove_license'); ?>
            <?php endif; ?>
        </form>
    </div>
<?php
}

/**
 * Load the main QueryCraft class.
 */
require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft.php';
/**
 * Load the template loader (defines querycraft_get_template).
 */
require_once QUERYCRAFT_PLUGIN_DIR . 'includes/template-loader.php';
/**
 * Import the querycraft_get_template() function into the global namespace.
 */

use function QueryCraft\querycraft_get_template;

/**
 * Initialize QueryCraft by instantiating the namespaced main class.
 * We check that a license key exists before initializing.
 */
function querycraft_init()
{
    if (! empty(get_option('querycraft_license_key', ''))) {
        \QueryCraft\QueryCraft::get_instance();
    }
}
add_action('plugins_loaded', 'querycraft_init');

/**
 * AJAX callback for "Load More" or "Infinite Scroll" requests.
 */
function querycraft_load_more_callback()
{
    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $shortcode_params = array(
        'pt'           => 'post',
        'display'      => 2,
        'paged'        => 'load_more',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'status'       => 'publish',
        'taxonomy'     => '',
        'term'         => '',
        'meta_key'     => '',
        'meta_value'   => '',
        'compare'      => '=',
        'template'     => 'title',
        'cta_template' => '',
        'cta_interval' => 0,
        'offset'       => 0,
    );
    if (! empty($_POST['shortcode'])) {
        $json_string = wp_unslash($_POST['shortcode']);
        $data = json_decode($json_string, true);
        if (is_array($data)) {
            $shortcode_params = wp_parse_args($data, $shortcode_params);
        }
    }
    require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft-query-builder.php';
    require_once QUERYCRAFT_PLUGIN_DIR . 'includes/template-loader.php';
    $query_args = \QueryCraft\QueryCraft_Query_Builder::build_query_args($shortcode_params);
    $posts_per_page = (int)$shortcode_params['display'];
    $current_page   = $page;
    $user_offset    = isset($shortcode_params['offset']) ? (int)$shortcode_params['offset'] : 0;
    if ($user_offset > 0) {
        $query_args['offset'] = $user_offset + (($current_page - 1) * $posts_per_page);
        if (isset($query_args['paged'])) {
            unset($query_args['paged']);
        }
    } else {
        $query_args['offset'] = (($current_page - 1) * $posts_per_page);
        $query_args['paged']  = $current_page;
    }
    $query = new \WP_Query($query_args);
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            querycraft_get_template($shortcode_params['template'], array('post' => get_post()));
        }
    }
    $posts_html = ob_get_clean();
    wp_reset_postdata();
    wp_send_json_success(array('posts' => $posts_html));
}
add_action('wp_ajax_querycraft_load_more', 'querycraft_load_more_callback');
add_action('wp_ajax_nopriv_querycraft_load_more', 'querycraft_load_more_callback');

/**
 * Submenu pages under the QueryCraft CPT top-level menu.
 * Parent slug is "edit.php?post_type=querycraft_cta".
 * This way, top-level = "QueryCraft" (the CPT listing),
 * and submenus = "Shortcode Builder" + "Documentation".
 */
function querycraft_add_submenus_under_cpt()
{
    add_submenu_page(
        'edit.php?post_type=querycraft_cta',
        'Shortcode Builder',
        'Shortcode Builder',
        'manage_options',
        'querycraft-shortcode-builder',
        'querycraft_render_shortcode_generator_page'
    );
    add_submenu_page(
        'edit.php?post_type=querycraft_cta',
        'Documentation',
        'Documentation',
        'manage_options',
        'querycraft-documentation',
        'querycraft_render_documentation_page'
    );
}
add_action('admin_menu', 'querycraft_add_submenus_under_cpt');

/**
 * Render the Shortcode Builder admin page.
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
 * On plugin activation, create the necessary directories in the active theme
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
        // Ensure a CTA folder exists for physical CTAs.
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
 * On plugin deactivation, hit the licensing API to deactivate the license,
 * then delete the stored license key so it doesn't get automatically reactivated,
 * then back up the 'querycraft' folder from the active theme and remove it.
 */
function querycraft_on_deactivation()
{
    // Deactivate license via API if a key exists.
    $license_key = get_option('querycraft_license_key', '');
    if (! empty($license_key)) {
        $response = wp_remote_post('http://206.189.194.86/api/license/deactivate', [
            'body'    => [
                'license_key' => $license_key,
                'plugin_slug' => 'querycraft',
                'domain'      => home_url(),
            ],
            'timeout' => 15,
        ]);
        error_log('QueryCraft Deactivation API Response: ' . print_r($response, true));
    }
    // Remove the stored license key so that re-activation doesn't automatically re-enable the plugin.
    delete_option('querycraft_license_key');
    delete_transient('querycraft_license_valid');

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
