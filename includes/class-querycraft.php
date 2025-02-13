<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once QUERYCRAFT_PLUGIN_DIR . 'includes/template-loader.php';
require_once QUERYCRAFT_PLUGIN_DIR . 'includes/cta-loader.php';

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
        // Require the Query Builder.
        require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-querycraft-query-builder.php';

        // Register the shortcode.
        add_action('init', [$this, 'register_shortcodes']);

        // Enqueue assets.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_shortcodes()
    {
        add_shortcode('load', [$this, 'render_shortcode']);
    }

    public function enqueue_assets()
    {
        wp_enqueue_style(
            'querycraft-css',
            QUERYCRAFT_PLUGIN_URL . 'assets/css/querycraft.css',
            [],
            QUERYCRAFT_VERSION
        );

        wp_enqueue_script(
            'querycraft-js',
            QUERYCRAFT_PLUGIN_URL . 'assets/js/querycraft.js',
            ['jquery'],
            QUERYCRAFT_VERSION,
            true
        );

        wp_localize_script('querycraft-js', 'QueryCraftData', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }

    public function render_shortcode($atts)
    {
        // If we're in the block editor, return a placeholder.
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return '<p>QueryCraft preview not available in the editor.</p>';
        }

        // Merge shortcode attributes with defaults.
        $atts = shortcode_atts([
            'pt'            => 'post',
            'display'       => 2,
            'paged'         => 'numbered', // can be 'load_more', 'infinite_scroll', etc.
            'orderby'       => 'date',
            'order'         => 'ASC',
            'status'        => 'publish',
            'taxonomy'      => '',
            'term'          => '',
            'meta_key'      => '',
            'meta_value'    => '',
            'compare'       => '=',
            'template'      => 'title',  // Template for rendering each post
            'cta_template'  => '',       // CTA template (if provided)
            'cta_interval'  => 0,        // Insert CTA after every N posts (0 means disabled)
            'offset'        => 0,        // Number of posts to skip
        ], $atts, 'load');

        // Build the query arguments.
        $query_args = QueryCraft_Query_Builder::build_query_args($atts);
        $query_args['ignore_sticky_posts'] = true;
        $query_args['no_found_rows'] = false;
        $query_args['cache_results'] = false;
        $query_args['suppress_filters'] = false;

        // Determine current page by checking both 'paged' and 'page'
        $current_page = max(1, absint(get_query_var('paged')), absint(get_query_var('page')));
        error_log("Combined current_page: $current_page");

        // If an offset is provided, calculate effective offset; otherwise, set paged.
        if (isset($atts['offset']) && (int)$atts['offset'] > 0) {
            $user_offset = (int)$atts['offset'];
            $posts_per_page = (int)$atts['display'];
            $query_args['offset'] = $user_offset + (($current_page - 1) * $posts_per_page);
            if (isset($query_args['paged'])) {
                unset($query_args['paged']);
            }
            error_log("QueryCraft: Offset Found");
        } else {
            $posts_per_page = (int)$atts['display'];
            $query_args['offset'] = (($current_page - 1) * $posts_per_page);
            $query_args['paged'] = $current_page;
            error_log("QueryCraft: Offset Not Found");
        }

        error_log("QueryCraft: query_args = " . print_r($query_args, true));

        // Force WP_Query to use the correct current page.
        global $paged;
        $paged = $current_page;

        $query = new WP_Query($query_args);
        error_log("QueryCraft: query = " . print_r($query, true));

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
                    if (!empty($atts['cta_template']) && (int)$atts['cta_interval'] > 0 && ($post_count % (int)$atts['cta_interval'] === 0)) {
                        querycraft_get_cta($atts['cta_template']);
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
                    if (!empty($atts['cta_template']) && (int)$atts['cta_interval'] > 0 && ($post_count % (int)$atts['cta_interval'] === 0)) {
                        querycraft_get_cta($atts['cta_template']);
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
        $pagination_type = sanitize_text_field($atts['paged']);
        switch ($pagination_type) {
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
                echo $pagination->render($query); // This returns an empty string.
                break;
            case 'prev_next':
                require_once QUERYCRAFT_PLUGIN_DIR . 'includes/pagination/class-prev-next-pagination.php';
                $pagination = new QueryCraft_Prev_Next_Pagination();
                echo $pagination->render($query);
                break;
        }

        return ob_get_clean();
    }
}
