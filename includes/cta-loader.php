<?php

namespace QueryCraft;

if (! function_exists('querycraft_get_cta')) {
    function querycraft_get_cta($cta_name, $args = array())
    {
        // Look in the theme's querycraft/cta folder first.
        $located = locate_template('querycraft/cta/' . $cta_name . '.php');
        if (! $located) {
            // Fallback to the plugin's CTA folder.
            $located = QUERYCRAFT_PLUGIN_DIR . 'cta/' . $cta_name . '.php';
        }
        if (! empty($args) && is_array($args)) {
            extract($args);
        }
        include $located;
    }
}
