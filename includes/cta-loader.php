<?php

/**
 * CTA Loader for QueryCraft.
 *
 * This function locates and loads a CTA template file.
 * It first checks if a file exists in your active theme’s "querycraft/cta" folder.
 * If not, it falls back to the plugin's default CTA directory.
 *
 * @param string $cta_name Name of the CTA template (without .php extension).
 * @param array  $args     Optional variables to extract in the CTA template.
 */
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
