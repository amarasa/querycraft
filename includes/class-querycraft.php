<?php

namespace QueryCraft;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once QUERYCRAFT_PLUGIN_DIR . 'includes/template-loader.php';
require_once QUERYCRAFT_PLUGIN_DIR . 'includes/cta-loader.php';

use QueryCraft\Pagination\QueryCraft_Numbered_Pagination;
use QueryCraft\Pagination\QueryCraft_Load_More_Pagination;
use QueryCraft\Pagination\QueryCraft_Infinite_Scroll_Pagination;
use QueryCraft\Pagination\QueryCraft_Prev_Next_Pagination;

class QueryCraft
{

    private static $instance = null;

    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft-query-builder.php';
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function register_shortcodes()
    {
        add_shortcode('load', array($this, 'render_shortcode'));
    }

    public function enqueue_assets()
    {
        wp_enqueue_style(
            'querycraft-css',
            QUERYCRAFT_PLUGIN_URL . 'assets/css/querycraft.css',
            array(),
            QUERYCRAFT_VERSION
        );

        wp_enqueue_script(
            'querycraft-js',
            QUERYCRAFT_PLUGIN_URL . 'assets/js/querycraft.js',
            array('jquery'),
            QUERYCRAFT_VERSION,
            true
        );

        wp_localize_script('querycraft-js', 'QueryCraftData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }

    public function render_shortcode($atts)
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return '<p>QueryCraft preview not available in the editor.</p>';
        }

        $atts = shortcode_atts(array(
            'pt'            => 'post',
            'display'       => 2,
            'paged'         => 'numbered',
            'orderby'       => 'date',
            'order'         => 'ASC',
            'status'        => 'publish',
            'taxonomy'      => '',
            'term'          => '',
            'meta_key'      => '',
            'meta_value'    => '',
            'compare'       => '=',
            'template'      => 'title',
            'cta_template'  => '',
            'cta_interval'  => 0,
            'offset'        => 0,
        ), $atts, 'load');

        // --- Pre-check for the Template module ---
        // If the specified template doesn't exist, fall back to "title".
        $template_exists = false;
        if (! empty($atts['template'])) {
            $located = locate_template('querycraft/templates/' . $atts['template'] . '.php');
            if (! $located) {
                $located = QUERYCRAFT_PLUGIN_DIR . 'templates/' . $atts['template'] . '.php';
            }
            if (file_exists($located)) {
                $template_exists = true;
            }
        }
        if (! $template_exists) {
            $atts['template'] = 'title';
        }

        // --- Pre-check for the CTA module (if provided) ---
        // For file-based CTAs: if the file doesn't exist, set to empty.
        // For post-based CTAs: if the post doesn't exist or isn't published, set to empty.
        if (! empty($atts['cta_template'])) {
            if (strpos($atts['cta_template'], 'post:') !== 0) {
                // File-based CTA: remove "file:" prefix if present.
                $cta_name = (strpos($atts['cta_template'], 'file:') === 0)
                    ? substr($atts['cta_template'], 5)
                    : $atts['cta_template'];
                $cta_file = locate_template('querycraft/cta/' . $cta_name . '.php');
                if (! $cta_file || ! file_exists($cta_file)) {
                    // If the file doesn't exist, disable CTA.
                    $atts['cta_template'] = '';
                }
            } elseif (strpos($atts['cta_template'], 'post:') === 0) {
                $post_id = intval(substr($atts['cta_template'], 5));
                $cta_post = get_post($post_id);
                if (! $cta_post || $cta_post->post_status !== 'publish') {
                    $atts['cta_template'] = '';
                }
            }
        }

        // Build query arguments.
        $query_args = QueryCraft_Query_Builder::build_query_args($atts);
        $query_args['ignore_sticky_posts'] = true;
        $query_args['no_found_rows'] = false;
        $query_args['cache_results'] = false;
        $query_args['suppress_filters'] = false;

        $current_page = max(1, absint(get_query_var('paged')), absint(get_query_var('page')));

        if (isset($atts['offset']) && (int) $atts['offset'] > 0) {
            $user_offset    = (int) $atts['offset'];
            $posts_per_page = (int) $atts['display'];
            $query_args['offset'] = $user_offset + (($current_page - 1) * $posts_per_page);
            if (isset($query_args['paged'])) {
                unset($query_args['paged']);
            }
        } else {
            $posts_per_page = (int) $atts['display'];
            $query_args['offset'] = (($current_page - 1) * $posts_per_page);
            $query_args['paged'] = $current_page;
        }

        global $paged;
        $paged = $current_page;

        $query = new \WP_Query($query_args);

        ob_start();

        if ($query->have_posts()) {
            $pagination_type = sanitize_text_field($atts['paged']);
            $post_count = 0;

            if ('infinite_scroll' === $pagination_type) {
                $shortcode_data = json_encode($atts);
                echo '<div class="querycraft-infinite-scroll" 
                    data-current-page="' . esc_attr($current_page) . '"
                    data-max-pages="' . esc_attr($query->max_num_pages) . '"
                    data-shortcode-params="' . esc_attr($shortcode_data) . '">';
                echo '<ul class="querycraft-list">';

                do_action('querycraft_before_loop', $atts, $query);

                while ($query->have_posts()) {
                    $query->the_post();
                    $post_count++;
                    querycraft_get_template($atts['template'], array('post' => get_post()));
                    if (! empty($atts['cta_template']) && (int) $atts['cta_interval'] > 0 && ($post_count % (int) $atts['cta_interval'] === 0)) {
                        $this->render_cta($atts['cta_template']);
                    }
                }

                do_action('querycraft_after_loop', $atts, $query);

                echo '</ul>';
                echo '</div>';
                echo '<div class="querycraft-infinite-scroll-spinner" style="display:none;">Loading...</div>';
                wp_reset_postdata();
            } else {
                echo '<ul class="querycraft-list">';

                do_action('querycraft_before_loop', $atts, $query);

                while ($query->have_posts()) {
                    $query->the_post();
                    $post_count++;
                    querycraft_get_template($atts['template'], array('post' => get_post()));
                    if (! empty($atts['cta_template']) && (int) $atts['cta_interval'] > 0 && ($post_count % (int) $atts['cta_interval'] === 0)) {
                        $this->render_cta($atts['cta_template']);
                    }
                }

                do_action('querycraft_after_loop', $atts, $query);

                echo '</ul>';
                wp_reset_postdata();
            }
        } else {
            echo '<p>No posts found.</p>';
        }

        // Handle pagination modules.
        switch ($atts['paged']) {
            case 'numbered':
                require_once QUERYCRAFT_PLUGIN_DIR . 'includes/pagination/class-numbered-pagination.php';
                $pagination = new QueryCraft_Numbered_Pagination();
                echo $pagination->render($query);
                break;
            case 'load_more':
                require_once QUERYCRAFT_PLUGIN_DIR . 'includes/pagination/class-load-more-pagination.php';
                $pagination = new QueryCraft_Load_More_Pagination($atts);
                echo $pagination->render($query);
                break;
            case 'infinite_scroll':
                require_once QUERYCRAFT_PLUGIN_DIR . 'includes/pagination/class-infinite-scroll-pagination.php';
                $pagination = new QueryCraft_Infinite_Scroll_Pagination($atts);
                echo $pagination->render($query);
                break;
            case 'prev_next':
                require_once QUERYCRAFT_PLUGIN_DIR . 'includes/pagination/class-prev-next-pagination.php';
                $pagination = new QueryCraft_Prev_Next_Pagination();
                echo $pagination->render($query);
                break;
        }

        return ob_get_clean();
    }

    /**
     * Render a CTA based on the value provided.
     * If the value starts with "file:", load a physical file template.
     * If it starts with "post:", query the CTA post and display its content.
     *
     * @param string $cta_value The CTA identifier (e.g., "file:blue-link" or "post:123").
     */
    private function render_cta($cta_value)
    {
        if (strpos($cta_value, 'file:') === 0) {
            $file_cta = substr($cta_value, 5);
            querycraft_get_cta($file_cta);
        } elseif (strpos($cta_value, 'post:') === 0) {
            $post_id = intval(substr($cta_value, 5));
            $cta_post = get_post($post_id);
            if ($cta_post && $cta_post->post_status === 'publish') {
                echo apply_filters('the_content', $cta_post->post_content);
            }
        }
    }
}
