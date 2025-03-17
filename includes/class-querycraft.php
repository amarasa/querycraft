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
            'container-class' => '',
            'excluded_taxonomy' => '',
            'excluded_term' => '',
            'max_total'     => '', // Added max total posts attribute
        ), $atts, 'load');

        // Build query arguments.
        $query_args = QueryCraft_Query_Builder::build_query_args($atts);

        global $paged;
        $paged = max(1, get_query_var('paged', 1));

        $query = new \WP_Query($query_args);

        ob_start();

        if ($query->have_posts()) {
            $post_count = 0;
            $container_class = isset($atts['container-class']) ? trim($atts['container-class']) : '';
            $full_container_class = 'querycraft-list' . (!empty($container_class) ? ' ' . $container_class : '');

            echo '<div class="' . esc_attr($full_container_class) . '">';

            do_action('querycraft_before_loop', $atts, $query);

            while ($query->have_posts()) {
                $query->the_post();
                $post_count++;

                // Apply max_total limit
                if (!empty($atts['max_total']) && is_numeric($atts['max_total']) && $post_count > (int) $atts['max_total']) {
                    break;
                }

                querycraft_get_template($atts['template'], array('post' => get_post()));

                if (!empty($atts['cta_template']) && (int) $atts['cta_interval'] > 0 && ($post_count % (int) $atts['cta_interval'] === 0)) {
                    $this->render_cta($atts['cta_template']);
                }
            }

            do_action('querycraft_after_loop', $atts, $query);

            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>No posts found.</p>';
        }

        return ob_get_clean();
    }

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
