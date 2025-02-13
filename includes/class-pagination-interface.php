<?php

namespace QueryCraft\Pagination;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

interface QueryCraft_Pagination_Interface
{
    public function render($query);
}
