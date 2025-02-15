<?php

namespace QueryCraft\Pagination;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-pagination-interface.php';

class QueryCraft_Prev_Next_Pagination implements QueryCraft_Pagination_Interface
{

    /**
     * Render previous/next pagination links.
     *
     * @param WP_Query $query The WP_Query object.
     * @return string HTML for previous/next links.
     */
    public function render($query)
    {
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $prev_link = get_previous_posts_link('Previous');
        $next_link = get_next_posts_link('Next', $query->max_num_pages);

        $html  = '<div class="querycraft-prev-next-pagination">';
        if ($prev_link) {
            $html .= '<div class="querycraft-prev-link">' . $prev_link . '</div>';
        }
        if ($next_link) {
            $html .= '<div class="querycraft-next-link">' . $next_link . '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
