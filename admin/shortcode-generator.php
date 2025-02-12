<?php

/**
 * QueryCraft Shortcode Generator
 *
 * This page allows users to configure QueryCraft options and generate a shortcode.
 *
 * @package QueryCraft
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Check user capability.
if (! current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

/**
 * Get available templates from both theme and plugin.
 *
 * @param string $subdir Directory inside the theme or plugin (e.g., 'templates' or 'cta').
 * @return array Unique list of template names (without .php extension).
 */
function querycraft_get_available_templates($subdir)
{
    $templates = array();

    // Get theme overrides.
    $theme_dir = get_stylesheet_directory() . '/querycraft/' . $subdir;
    if (is_dir($theme_dir)) {
        foreach (glob(trailingslashit($theme_dir) . '*.php') as $file) {
            $templates[] = basename($file, '.php');
        }
    }

    // Get plugin defaults.
    if ('templates' === $subdir) {
        $plugin_dir = QUERYCRAFT_PLUGIN_DIR . 'templates';
    } else {
        // For CTA templates.
        $plugin_dir = QUERYCRAFT_PLUGIN_DIR . 'cta';
    }
    if (is_dir($plugin_dir)) {
        foreach (glob(trailingslashit($plugin_dir) . '*.php') as $file) {
            $templates[] = basename($file, '.php');
        }
    }

    return array_unique($templates);
}

// Get available post types.
$post_types = get_post_types(array('public' => true), 'objects');

// Get available templates.
$available_templates = querycraft_get_available_templates('templates');

// Get available CTA templates.
$available_cta_templates = querycraft_get_available_templates('cta');
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>QueryCraft Shortcode Generator</title>
    <style>
        /* QueryCraft Shortcode Generator Styles */
        body {
            background: #f1f1f1;
            font-family: sans-serif;
            margin: 0;
            padding: 20px;
        }

        .qc-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }

        .qc-container h1,
        .qc-container h2 {
            text-align: center;
            color: #333;
        }

        .qc-container .form-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .qc-container .form-table th,
        .qc-container .form-table td {
            padding: 10px;
            vertical-align: top;
        }

        .qc-container .form-table th {
            text-align: left;
            font-weight: bold;
            color: #555;
            width: 30%;
        }

        .qc-container input[type="text"],
        .qc-container input[type="number"],
        .qc-container select,
        .qc-container textarea {
            width: 100%;
            padding: 8px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }

        .qc-container textarea {
            resize: vertical;
        }

        .qc-container .button {
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            background: #0073aa;
            color: #fff;
            border-radius: 3px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .qc-container .button:hover {
            background: #006799;
            color: #fff;
            /* Ensure text stays white on hover */
        }


        .qc-container .description {
            font-size: 12px;
            color: #777;
            margin-top: 4px;
        }

        .qc-container select[multiple] {
            height: auto;
        }
    </style>
</head>

<body>
    <div class="qc-container">
        <h1>QueryCraft Shortcode Generator</h1>
        <p>Configure your QueryCraft options below and copy the generated shortcode.</p>

        <form id="querycraft-shortcode-generator">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="qc-post-type">Post Type</label></th>
                    <td>
                        <select name="qc_post_type[]" id="qc-post-type" multiple>
                            <?php foreach ($post_types as $pt) : ?>
                                <option value="<?php echo esc_attr($pt->name); ?>" <?php selected('post', $pt->name); ?>>
                                    <?php echo esc_html($pt->labels->singular_name); ?> (<?php echo esc_html($pt->name); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Select one or more post types. Hold Ctrl/Cmd to select multiple.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="qc-display">Posts Per Page</label></th>
                    <td>
                        <input name="qc_display" type="number" id="qc-display" value="6" class="small-text" />
                        <p class="description">Number of posts per page.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="qc-paged">Pagination Type</label></th>
                    <td>
                        <select name="qc_paged" id="qc-paged">
                            <option value="numbered">Numbered</option>
                            <option value="load_more">Load More</option>
                            <option value="infinite_scroll">Infinite Scroll</option>
                            <option value="prev_next">Prev/Next</option>
                        </select>
                        <p class="description">Select the type of pagination.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="qc-template">Template</label></th>
                    <td>
                        <select name="qc_template" id="qc-template">
                            <?php foreach ($available_templates as $template) : ?>
                                <option value="<?php echo esc_attr($template); ?>">
                                    <?php echo esc_html(ucfirst($template)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Select the template for rendering posts.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="qc-cta-template">CTA Template</label></th>
                    <td>
                        <select name="qc_cta_template" id="qc-cta-template">
                            <option value="">None</option>
                            <?php foreach ($available_cta_templates as $cta_template) : ?>
                                <option value="<?php echo esc_attr($cta_template); ?>">
                                    <?php echo esc_html(ucfirst($cta_template)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Select a CTA template (if any). Leave as "None" to disable.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="qc-cta-interval">CTA Interval</label></th>
                    <td>
                        <input name="qc_cta_interval" type="number" id="qc-cta-interval" value="0" class="small-text" />
                        <p class="description">Insert CTA after every N posts (set 0 to disable).</p>
                    </td>
                </tr>
                <!-- Additional fields (order, orderby, taxonomy, etc.) can be added here. -->
            </table>

            <h2>Generated Shortcode</h2>
            <textarea id="qc-shortcode-output" rows="3" readonly style="width:100%;"></textarea>
            <p>
                <button type="button" id="qc-copy-btn" class="button">Copy Shortcode</button>
            </p>
        </form>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Function to generate the shortcode string from the form values.
            function generateShortcode() {
                // For post types, join the selected values with a comma.
                var postTypes = $('#qc-post-type').val();
                var pt = (postTypes && postTypes.length > 0) ? postTypes.join(',') : 'post';

                var display = $('#qc-display').val();
                var paged = $('#qc-paged').val();
                var template = $('#qc-template').val();
                var cta_template = $('#qc-cta-template').val();
                var cta_interval = $('#qc-cta-interval').val();

                var shortcode = '[load';
                shortcode += ' pt="' + pt + '"';
                shortcode += ' display="' + display + '"';
                shortcode += ' paged="' + paged + '"';
                shortcode += ' template="' + template + '"';

                if (cta_template !== '') {
                    shortcode += ' cta_template="' + cta_template + '"';
                }
                if (cta_interval > 0) {
                    shortcode += ' cta_interval="' + cta_interval + '"';
                }
                shortcode += ']';

                $('#qc-shortcode-output').val(shortcode);
            }

            // Initial generation.
            generateShortcode();

            // Bind change events to all inputs and selects.
            $('#querycraft-shortcode-generator').on('input change', 'input, select', function() {
                generateShortcode();
            });

            // Copy shortcode to clipboard.
            $('#qc-copy-btn').click(function() {
                var $textarea = $('#qc-shortcode-output');
                $textarea.select();
                document.execCommand('copy');
                alert('Shortcode copied to clipboard!');
            });
        });
    </script>