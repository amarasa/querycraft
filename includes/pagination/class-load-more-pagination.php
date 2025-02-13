<?php

namespace QueryCraft\Pagination;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-pagination-interface.php';

class QueryCraft_Load_More_Pagination implements QueryCraft_Pagination_Interface
{

    protected $atts = array();

    /**
     * Constructor.
     *
     * @param array $atts Shortcode attributes.
     */
    public function __construct($atts = array())
    {
        $this->atts = $atts;
    }

    /**
     * Render the load more button.
     *
     * @param WP_Query $query The WP_Query object.
     * @return string HTML for the load more button.
     */
    public function render($query)
    {
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $current_page = max(1, get_query_var('paged', 1));
        if ($current_page >= $query->max_num_pages) {
            return '';
        }

        $shortcode_data = json_encode($this->atts);

        $html  = '<div class="querycraft-load-more-wrapper">';
        $html .= '<a href="#" class="querycraft-load-more-button" ';
        $html .= 'data-current-page="' . esc_attr($current_page) . '" ';
        $html .= 'data-max-pages="' . esc_attr($query->max_num_pages) . '" ';
        $html .= 'data-shortcode-params="' . esc_attr($shortcode_data) . '">';
        $html .= 'Load More';
        $html .= '</a>';
        $html .= '</div>';

        return $html;
    }
}
