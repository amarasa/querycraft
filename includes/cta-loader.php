<?php

namespace QueryCraft;

if (! function_exists('querycraft_get_cta')) {
    function querycraft_get_cta($cta_name, $args = array())
    {
        // Look only in the active theme's querycraft/cta folder.
        $located = locate_template('querycraft/cta/' . $cta_name . '.php');
        // If the file doesn't exist, display an error message.
        if (!$located || ! file_exists($located)) {
            echo "<div class='querycraft-error' style='border:1px solid #e00; padding:10px; margin:10px 0; color:#e00;'>
                    Invalid CTA module \"{$cta_name}\". Please double-check your spelling or contact your developer if this issue persists.
                  </div>";
            return;
        }
        if (! empty($args) && is_array($args)) {
            extract($args);
        }
        include $located;
    }
}
