<?php

namespace QueryCraft\Pagination;

/**
 * Numbered Pagination module for QueryCraft, styled with Tailwind CSS.
 *
 * This class implements QueryCraft_Pagination_Interface and outputs
 * classic numbered pagination links, wrapped in Tailwind classes
 * for styling.
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-pagination-interface.php';

class QueryCraft_Numbered_Pagination implements QueryCraft_Pagination_Interface
{

    public function render($query)
    {
        // Only display pagination if there's more than one page.
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $current_page = max(1, get_query_var('paged', 1));

        // Instead of 'type' => 'list', we use 'type' => 'array' so we can build
        // custom HTML with Tailwind classes.
        $pagination_args = array(
            'base'      => esc_url_raw(str_replace(999999999, '%#%', get_pagenum_link(999999999))),
            'format'    => '?paged=%#%',
            'current'   => $current_page,
            'total'     => $query->max_num_pages,
            'type'      => 'array', // Important: returns an array of links
            'mid_size'  => 1,
            'prev_text' => 'Previous',
            'next_text' => 'Next',
        );

        $links = paginate_links($pagination_args);

        // If paginate_links() didn’t return an array, bail out.
        if (! is_array($links)) {
            return '';
        }

        // Start building our custom pagination markup.
        // We wrap everything in a <nav> with Tailwind classes.
        // The role="navigation" and aria-label="Pagination" help with accessibility.
        $html  = '<nav class="flex items-center justify-center space-x-2 my-4" role="navigation" aria-label="Pagination">';

        foreach ($links as $link) {
            // Check if this link is for the current page.
            if (strpos($link, 'current') !== false) {
                // Remove HTML tags (like <span class="page-numbers current">) to get just the page number text.
                $page_num = strip_tags($link);

                // Render the current page as a <span> with “active” Tailwind classes.
                $html .= '<span class="px-3 py-1 bg-gray-700 text-white rounded">' . $page_num . '</span>';
            } else {
                // It’s a normal link (for another page).
                // We want to inject Tailwind classes into the <a> tag.
                // We'll do a quick regex or str_replace to insert classes.
                $styled_link = preg_replace(
                    '/<a\s+class="([^"]*)"/',
                    '<a class="$1 px-3 py-1 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition"',
                    $link
                );

                // If the link was for “Previous” or “Next,” you can optionally check
                // if it has “prev” or “next” class and style them differently.
                // e.g. if (strpos($link, 'prev page-numbers') !== false) { ... }

                $html .= $styled_link;
            }
        }

        $html .= '</nav>';

        return $html;
    }
}
