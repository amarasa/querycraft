<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

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
            'pt'         => 'post',
            'display'    => 2,
            'paged'      => 'numbered', // can be 'load_more', 'infinite_scroll', etc.
            'orderby'    => 'date',
            'order'      => 'ASC',
            'status'     => 'publish',
            'taxonomy'   => '',
            'term'       => '',
            'meta_key'   => '',
            'meta_value' => '',
            'compare'    => '=',
        ], $atts, 'load');

        // Build the query.
        $query_args = QueryCraft_Query_Builder::build_query_args($atts);

        // Current page (for normal WP pagination).
        $current_page = max(1, get_query_var('paged', 1));
        $query_args['paged'] = $current_page;

        $query = new WP_Query($query_args);

        ob_start();

        if ($query->have_posts()) {
            $pagination_type = sanitize_text_field($atts['paged']);

            /**
             * If infinite scroll is selected, we do:
             * 1) A single container (querycraft-infinite-scroll)
             * 2) The <ul> for initial posts
             * 3) A spinner for loading
             */
            if ('infinite_scroll' === $pagination_type) {
                // Encode the shortcode attributes for the AJAX request.
                $shortcode_data = json_encode($atts);

                // Output the container with data attributes.
                echo '<div class="querycraft-infinite-scroll" 
					data-current-page="' . esc_attr($current_page) . '"
					data-max-pages="' . esc_attr($query->max_num_pages) . '"
					data-shortcode-params="' . esc_attr($shortcode_data) . '">';

                echo '<ul class="querycraft-list">';

                while ($query->have_posts()) {
                    $query->the_post();
                    echo '<li>' . get_the_title() . '</li>';
                }
                echo '</ul>';
                echo '</div>'; // close .querycraft-infinite-scroll

                echo '<div class="querycraft-infinite-scroll-spinner" style="display:none;">Loading...</div>';

                wp_reset_postdata();
            } else {
                // Normal loop (or load more, or numbered, etc.)
                echo '<ul class="querycraft-list">';
                while ($query->have_posts()) {
                    $query->the_post();
                    echo '<li>' . get_the_title() . '</li>';
                }
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
                // We no longer rely on the infinite scroll pagination class to output anything.
                // It's handled above. So let's skip calling it or call it and let it return ''.
                require_once QUERYCRAFT_PLUGIN_DIR . 'includes/pagination/class-infinite-scroll-pagination.php';
                $pagination = new QueryCraft_Infinite_Scroll_Pagination($atts);
                echo $pagination->render($query); // This will return ''
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
