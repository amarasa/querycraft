<?php

namespace QueryCraft;

if (! function_exists('querycraft_get_template')) {
    function querycraft_get_template($template_name, $args = array())
    {
        // Look for a template in the theme’s querycraft folder.
        $located = locate_template('querycraft/templates/' . $template_name . '.php');
        if (! $located) {
            // Fallback to the plugin’s template directory.
            $located = QUERYCRAFT_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        }
        if (! empty($args) && is_array($args)) {
            extract($args);
        }
        include $located;
    }
}
