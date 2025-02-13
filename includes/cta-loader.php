<?php

namespace QueryCraft;

if (! function_exists('querycraft_get_cta')) {
    function querycraft_get_cta($cta_name, $args = array())
    {
        // Look only in the active theme's querycraft/cta folder.
        $located = locate_template('querycraft/cta/' . $cta_name . '.php');
        if (! $located) {
            return; // If not found, do nothing.
        }
        if (! empty($args) && is_array($args)) {
            extract($args);
        }
        include $located;
    }
}
