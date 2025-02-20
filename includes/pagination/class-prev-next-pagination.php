<?php

namespace QueryCraft\Pagination;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure the pagination interface is loaded.
require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-pagination-interface.php';

class QueryCraft_Prev_Next_Pagination implements QueryCraft_Pagination_Interface
{
    /**
     * Render previous/next pagination links, styled with Tailwind CSS.
     *
     * @param \WP_Query $query The WP_Query object.
     * @return string HTML for previous/next links.
     */
    public function render($query)
    {
        // Only display pagination if there's more than one page.
        if ($query->max_num_pages <= 1) {
            return '';
        }

        $prev_link = get_previous_posts_link('Previous');
        $next_link = get_next_posts_link('Next', $query->max_num_pages);

        // If neither link is present, return early.
        if (! $prev_link && ! $next_link) {
            return '';
        }

        // Wrap everything in a <nav> with Tailwind classes for styling.
        $html  = '<nav class="flex items-center justify-center space-x-4 my-4" role="navigation" aria-label="Pagination">';

        // Style the Previous link (if it exists).
        if ($prev_link) {
            // Insert Tailwind classes into the <a> tag.
            $prev_link = str_replace(
                '<a ',
                '<a class="px-4 py-2 border border-gray-700 rounded bg-gray-800 text-gray-300 hover:bg-gray-700 transition" ',
                $prev_link
            );
            $html .= $prev_link;
        }

        // Style the Next link (if it exists).
        if ($next_link) {
            $next_link = str_replace(
                '<a ',
                '<a class="px-4 py-2 border border-gray-700 rounded bg-gray-800 text-gray-300 hover:bg-gray-700 transition" ',
                $next_link
            );
            $html .= $next_link;
        }

        $html .= '</nav>';

        return $html;
    }
}
