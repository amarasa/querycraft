<?php

/**
 * Pagination Interface for QueryCraft.
 *
 * Defines the methods that all pagination classes must implement.
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

interface QueryCraft_Pagination_Interface
{
    public function render($query);
}
