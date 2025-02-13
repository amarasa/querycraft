<?php

namespace QueryCraft;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class QueryCraft_Query_Builder
{
    public static function build_query_args($atts = array())
    {
        $defaults = array(
            'pt'         => 'post',
            'display'    => 2,
            'paged'      => 'numbered',
            'orderby'    => 'date',
            'order'      => 'DESC',
            'status'     => 'publish',
            'taxonomy'   => '',
            'term'       => '',
            'meta_key'   => '',
            'meta_value' => '',
            'compare'    => '=',
            'offset'     => 0,
        );

        $parsed = shortcode_atts($defaults, $atts, 'load');

        $query_args = array(
            'post_type'      => array_map('sanitize_text_field', explode(',', $parsed['pt'])),
            'posts_per_page' => (int) $parsed['display'],
            'orderby'        => sanitize_text_field($parsed['orderby']),
            'order'          => ('DESC' === strtoupper($parsed['order'])) ? 'DESC' : 'ASC',
            'post_status'    => array_map('sanitize_text_field', explode(',', $parsed['status'])),
        );

        if (! empty($parsed['taxonomy']) && ! empty($parsed['term'])) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => sanitize_text_field($parsed['taxonomy']),
                    'field'    => 'slug',
                    'terms'    => array_map('sanitize_text_field', explode(',', $parsed['term'])),
                ),
            );
        }

        if (! empty($parsed['meta_key']) && '' !== $parsed['meta_value']) {
            $query_args['meta_query'] = array(
                array(
                    'key'     => sanitize_text_field($parsed['meta_key']),
                    'value'   => sanitize_text_field($parsed['meta_value']),
                    'compare' => sanitize_text_field($parsed['compare']),
                ),
            );
        }

        if (isset($parsed['offset']) && is_numeric($parsed['offset'])) {
            $query_args['offset'] = (int) $parsed['offset'];
        }

        return $query_args;
    }
}
