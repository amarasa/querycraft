<?php

/**
 * Template Loader for QueryCraft.
 *
 * This function locates and loads a template file.
 * It first checks if a file exists in your active theme’s “querycraft” folder.
 * If not, it falls back to the plugin’s default template in the /templates directory.
 *
 * @param string $template_name Name of the template (without .php extension)
 * @param array  $args          Optional array of variables to pass to the template.
 */
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
