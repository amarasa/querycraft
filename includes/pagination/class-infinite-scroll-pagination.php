<?php
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once QUERYCRAFT_PLUGIN_DIR . 'includes/class-pagination-interface.php';

class QueryCraft_Infinite_Scroll_Pagination implements QueryCraft_Pagination_Interface
{

    protected $atts = [];

    public function __construct($atts = [])
    {
        $this->atts = $atts;
    }

    public function render($query)
    {
        // We no longer output the container here.
        // The main plugin class handles it.
        return '';
    }
}
