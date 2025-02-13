<?php

namespace QueryCraft;

if (! function_exists('querycraft_get_template')) {
    function querycraft_get_template($template_name, $args = array())
    {
        // Look for a template in the theme’s querycraft folder.
        $located = locate_template('querycraft/templates/' . $template_name . '.php');
        if (!$located) {
            // Fallback to the plugin’s template directory.
            $located = QUERYCRAFT_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        }
        // If the file doesn't exist, display an error message.
        if (! file_exists($located)) {
            echo "<div class='querycraft-error' style='border:1px solid #e00; padding:10px; margin:10px 0; color:#e00;'>
                    Invalid Template module \"{$template_name}\". Please double-check your spelling or contact your developer if this issue persists.
                  </div>";
            return;
        }
        if (! empty($args) && is_array($args)) {
            extract($args);
        }
        include $located;
    }
}
