<?php

/**
 * Numbered Pagination module for QueryCraft.
 *
 * This class implements QueryCraft_Pagination_Interface and outputs
 * classic numbered pagination links.
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the interface is loaded.
require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-pagination-interface.php';

class QueryCraft_Numbered_Pagination implements QueryCraft_Pagination_Interface
{

    /**
     * Render numbered pagination links.
     *
     * @param WP_Query $query The WP_Query object.
     * @return string HTML for the pagination links.
     */
    public function render($query)
    {
        // Only display pagination if there's more than one page.
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $current_page = max(1, get_query_var('paged', 1));

        $pagination_args = array(
            'base'      => esc_url_raw(str_replace(999999999, '%#%', get_pagenum_link(999999999))),
            'format'    => '?paged=%#%',
            'current'   => $current_page,
            'total'     => $query->max_num_pages,
            'type'      => 'list',
        );

        $pagination = paginate_links($pagination_args);

        return '<nav class="querycraft-pagination">' . $pagination . '</nav>';
    }
}
