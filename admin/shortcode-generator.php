<?php

namespace QueryCraft\Admin;

/**
 * QueryCraft Shortcode Generator with Tabs, Checkboxes, and Radio Inputs
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
 * For 'cta', returns an associative array combining physical file CTAs and CTAs from the CPT.
 * For 'templates', returns an array of physical file template names.
 *
 * @param string $subdir Directory (e.g. 'templates' or 'cta').
 * @return array
 */
function querycraft_get_available_templates($subdir)
{
    if ($subdir === 'cta') {
        $templates = array();
        // Physical file CTAs.
        $theme_dir = get_stylesheet_directory() . '/querycraft/cta';
        if (is_dir($theme_dir)) {
            foreach (glob(trailingslashit($theme_dir) . '*.php') as $file) {
                $name = basename($file, '.php');
                $templates["file:" . $name] = ucfirst($name) . ' (Physical File)';
            }
        }
        // CTAs from the custom post type.
        $cta_posts = get_posts(array(
            'post_type'      => 'querycraft_cta',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ));
        if (! empty($cta_posts)) {
            foreach ($cta_posts as $cta_post) {
                $templates["post:" . $cta_post->ID] = get_the_title($cta_post) . ' (Post Type)';
            }
        }
        return $templates;
    } elseif ($subdir === 'templates') {
        $templates = array();
        // Theme overrides.
        $theme_dir = get_stylesheet_directory() . '/querycraft/' . $subdir;
        if (is_dir($theme_dir)) {
            foreach (glob(trailingslashit($theme_dir) . '*.php') as $file) {
                $templates[] = basename($file, '.php');
            }
        }
        // Plugin defaults.
        $plugin_dir = QUERYCRAFT_PLUGIN_DIR . 'templates';
        if (is_dir($plugin_dir)) {
            foreach (glob(trailingslashit($plugin_dir) . '*.php') as $file) {
                $templates[] = basename($file, '.php');
            }
        }
        return array_unique($templates);
    }
    return array();
}

// Fetch data for the form.
$post_types          = get_post_types(array('public' => true), 'objects');
$available_templates = querycraft_get_available_templates('templates');
$available_cta_temps = querycraft_get_available_templates('cta');
$statuses            = get_post_statuses();
$taxonomies          = get_taxonomies(array('public' => true), 'objects');
?>

<div class="qc-builder-wrap">
    <h1>QueryCraft Shortcode Builder</h1>
    <p class="description">Configure your QueryCraft options below. The generated shortcode will appear on the right.</p>

    <div class="qc-builder-columns">
        <!-- LEFT COLUMN (2/3): Tabbed Sections -->
        <div class="qc-builder-left">
            <!-- TAB NAVIGATION -->
            <ul class="qc-tabs-nav">
                <li class="qc-tab-nav-item active" data-tab="qc-tab-general">General Settings</li>
                <li class="qc-tab-nav-item" data-tab="qc-tab-taxonomy">Taxonomy</li>
                <li class="qc-tab-nav-item" data-tab="qc-tab-cta">CTA Options</li>
                <li class="qc-tab-nav-item" data-tab="qc-tab-meta">Meta Query</li>
                <li class="qc-tab-nav-item" data-tab="qc-tab-extras">Extras</li>
            </ul>

            <!-- TAB CONTENT WRAPPER -->
            <form id="querycraft-shortcode-generator">
                <div class="qc-tabs-content">

                    <!-- GENERAL SETTINGS TAB -->
                    <div class="qc-tab-panel active" id="qc-tab-general">
                        <h2>General Settings</h2>
                        <!-- Post Type (checkboxes) -->
                        <div class="qc-field-group">
                            <label><strong>Post Type</strong></label>
                            <p class="description">Select one or more post types.</p>
                            <div class="qc-checkbox-grid">
                                <?php foreach ($post_types as $pt) : ?>
                                    <label class="qc-checkbox-label">
                                        <input type="checkbox" name="qc_post_type[]" value="<?php echo esc_attr($pt->name); ?>" <?php checked($pt->name === 'post'); ?> />
                                        <span><?php echo esc_html($pt->labels->singular_name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Posts Per Page -->
                        <div class="qc-field-group">
                            <label for="qc-display"><strong>Posts Per Page</strong></label>
                            <input name="qc_display" type="number" id="qc-display" value="6" class="small-text" />
                        </div>

                        <!-- Max Total Posts -->
                        <div class="qc-field-group">
                            <label for="qc-max-total"><strong>Max Total Posts</strong></label>
                            <input name="qc_max_total" type="number" id="qc-max-total" value="" class="small-text" />
                            <p class="description">Set a hard limit on the total number of posts to display (leave blank for no limit).</p>
                        </div>

                        <!-- Pagination Type (radio) -->
                        <div class="qc-field-group">
                            <label><strong>Pagination Type</strong></label>
                            <div class="qc-radio-grid">
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_paged" value="numbered" checked />
                                    <span>Numbered</span>
                                </label>
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_paged" value="load_more" />
                                    <span>Load More</span>
                                </label>
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_paged" value="infinite_scroll" />
                                    <span>Infinite Scroll</span>
                                </label>
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_paged" value="prev_next" />
                                    <span>Prev/Next</span>
                                </label>
                            </div>
                        </div>
                        <!-- Template (select) -->
                        <div class="qc-field-group">
                            <label for="qc-template"><strong>Template</strong></label>
                            <select name="qc_template" id="qc-template">
                                <?php foreach ($available_templates as $template) : ?>
                                    <option value="<?php echo esc_attr($template); ?>">
                                        <?php echo esc_html(ucfirst($template)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Order By (radio) -->
                        <div class="qc-field-group">
                            <label><strong>Order By</strong></label>
                            <div class="qc-radio-grid">
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_orderby" value="date" checked />
                                    <span>Date</span>
                                </label>
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_orderby" value="title" />
                                    <span>Title</span>
                                </label>
                            </div>
                        </div>
                        <!-- Order (radio) -->
                        <div class="qc-field-group">
                            <label><strong>Order</strong></label>
                            <div class="qc-radio-grid">
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_order" value="DESC" checked />
                                    <span>DESC (Newest First)</span>
                                </label>
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_order" value="ASC" />
                                    <span>ASC (Oldest First)</span>
                                </label>
                            </div>
                        </div>
                        <!-- Post Status (checkboxes) -->
                        <div class="qc-field-group">
                            <label><strong>Post Status</strong></label>
                            <p class="description">Select one or more statuses.</p>
                            <div class="qc-checkbox-grid">
                                <?php foreach ($statuses as $status => $label): ?>
                                    <label class="qc-checkbox-label">
                                        <input type="checkbox" name="qc_status[]" value="<?php echo esc_attr($status); ?>" <?php checked($status === 'publish'); ?> />
                                        <span><?php echo esc_html($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Offset -->
                        <div class="qc-field-group">
                            <label for="qc-offset"><strong>Offset</strong></label>
                            <input name="qc_offset" type="number" id="qc-offset" value="0" class="small-text" />
                        </div>
                    </div><!-- /#qc-tab-general -->

                    <!-- TAXONOMY TAB -->
                    <div class="qc-tab-panel" id="qc-tab-taxonomy">
                        <h2>Taxonomy</h2>
                        <div class="qc-field-group">
                            <label><strong>Filter Mode</strong></label>
                            <div class="qc-radio-grid">
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_filter_mode" value="include" checked />
                                    <span>Filter by taxonomy</span>
                                </label>
                                <label class="qc-radio-label">
                                    <input type="radio" name="qc_filter_mode" value="exclude" />
                                    <span>Exclude taxonomy</span>
                                </label>
                            </div>
                        </div>
                        <!-- Filter by fields -->
                        <div id="qc-filter-fields">
                            <div class="qc-field-group">
                                <label for="qc-taxonomy"><strong>Filter by taxonomy</strong></label>
                                <select name="qc_taxonomy" id="qc-taxonomy">
                                    <option value="">None</option>
                                    <?php foreach ($taxonomies as $tax): ?>
                                        <option value="<?php echo esc_attr($tax->name); ?>">
                                            <?php echo esc_html($tax->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="qc-field-group">
                                <label for="qc-term"><strong>Filter by term</strong></label>
                                <select name="qc_term" id="qc-term">
                                    <option value="">None</option>
                                </select>
                            </div>
                        </div>
                        <!-- Exclude fields -->
                        <div id="qc-exclude-fields" style="display:none;">
                            <div class="qc-field-group">
                                <label for="qc-excluded-taxonomy"><strong>Exclude taxonomy</strong></label>
                                <select name="qc_excluded_taxonomy" id="qc-excluded-taxonomy">
                                    <option value="">None</option>
                                    <?php foreach ($taxonomies as $tax): ?>
                                        <option value="<?php echo esc_attr($tax->name); ?>">
                                            <?php echo esc_html($tax->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="qc-field-group">
                                <label for="qc-excluded-term"><strong>Exclude term</strong></label>
                                <select name="qc-excluded-term" id="qc-excluded-term">
                                    <option value="">None</option>
                                </select>
                            </div>
                        </div>
                    </div><!-- /#qc-tab-taxonomy -->

                    <!-- CTA OPTIONS TAB -->
                    <div class="qc-tab-panel" id="qc-tab-cta">
                        <h2>CTA Options</h2>
                        <div class="qc-field-group">
                            <label for="qc-cta-template"><strong>CTA Template</strong></label>
                            <select name="qc_cta_template" id="qc-cta-template">
                                <option value="">None</option>
                                <?php foreach ($available_cta_temps as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="qc-field-group">
                            <label for="qc-cta-interval"><strong>CTA Interval</strong></label>
                            <input name="qc_cta_interval" type="number" id="qc-cta-interval" value="0" class="small-text" />
                        </div>
                    </div><!-- /#qc-tab-cta -->

                    <!-- META QUERY TAB -->
                    <div class="qc-tab-panel" id="qc-tab-meta">
                        <h2>Meta Query</h2>
                        <div class="qc-field-group">
                            <label for="qc-meta-key"><strong>Meta Key</strong></label>
                            <input name="qc_meta_key" type="text" id="qc-meta-key" value="" />
                        </div>
                        <div class="qc-field-group">
                            <label for="qc-meta-value"><strong>Meta Value</strong></label>
                            <input name="qc_meta_value" type="text" id="qc-meta-value" value="" />
                        </div>
                        <div class="qc-field-group">
                            <label for="qc-compare"><strong>Compare Operator</strong></label>
                            <select name="qc_compare" id="qc-compare">
                                <option value="=">=</option>
                                <option value="!=">!=</option>
                                <option value=">">&gt;</option>
                                <option value="<">&lt;</option>
                                <option value=">=">&gt;=</option>
                                <option value="<=">&lt;=</option>
                            </select>
                        </div>
                    </div><!-- /#qc-tab-meta -->

                    <!-- EXTRAS TAB -->
                    <div class="qc-tab-panel" id="qc-tab-extras">
                        <h2>Extras</h2>
                        <div class="qc-field-group">
                            <label for="qc-container-class"><strong>Container Class</strong></label>
                            <input name="qc_container_class" type="text" id="qc-container-class" value="" />
                            <p class="description">This extra class will be appended to the default container (querycraft-list) for custom styling.</p>
                        </div>
                    </div><!-- /#qc-tab-extras -->

                </div><!-- /.qc-tabs-content -->
            </form>
        </div><!-- /.qc-builder-left -->

        <!-- RIGHT COLUMN (1/3): Sticky Shortcode Output -->
        <div class="qc-builder-right">
            <div class="qc-sticky-box">
                <h2>Shortcode Output</h2>
                <p class="description">Copy this shortcode and paste it wherever you want your listing to appear.</p>
                <textarea id="qc-shortcode-output" rows="6" readonly></textarea>
                <button type="button" id="qc-copy-btn" class="button button-primary">Copy Shortcode</button>
            </div>
        </div><!-- /.qc-builder-right -->
    </div><!-- /.qc-builder-columns -->
</div><!-- /.qc-builder-wrap -->

<style>
    /* --- Layout & Container Styles --- */
    .qc-builder-wrap {
        margin: 20px;
        padding: 20px;
        background: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-radius: 4px;
    }

    .qc-builder-wrap h1 {
        margin-top: 0;
        font-size: 24px;
    }

    .qc-builder-columns {
        display: flex;
        gap: 20px;
    }

    .qc-builder-left {
        flex: 2;
        /* ~ 2/3 width */
    }

    .qc-builder-right {
        flex: 1;
        /* ~ 1/3 width */
        position: relative;
    }

    .qc-sticky-box {
        position: sticky;
        top: 20px;
        padding: 15px;
        background: #fafafa;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    /* --- Tab Navigation --- */
    .qc-tabs-nav {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
        display: flex;
        border-bottom: 1px solid #ccc;
    }

    .qc-tab-nav-item {
        padding: 10px 15px;
        margin-right: 2px;
        cursor: pointer;
        background: #eee;
        border: 1px solid #ccc;
        border-bottom: none;
        border-radius: 4px 4px 0 0;
    }

    .qc-tab-nav-item.active {
        background: #fff;
        border-bottom: 1px solid #fff;
        font-weight: bold;
    }

    /* --- Tab Panels --- */
    .qc-tabs-content {
        background: #fff;
        border: 1px solid #ccc;
        border-top: none;
        border-radius: 0 4px 4px 4px;
        padding: 20px;
    }

    .qc-tab-panel {
        display: none;
    }

    .qc-tab-panel.active {
        display: block;
    }

    /* --- Field Group Styles --- */
    .qc-field-group {
        margin-bottom: 20px;
    }

    .qc-field-group label {
        display: inline-block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .qc-field-group .description {
        margin-top: 3px;
        font-size: 12px;
        color: #666;
    }

    .small-text {
        width: 80px;
    }

    /* --- Checkboxes & Radios --- */
    .qc-checkbox-grid,
    .qc-radio-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px 15px;
        margin-top: 5px;
    }

    .qc-checkbox-label,
    .qc-radio-label {
        display: inline-flex;
        align-items: center;
        background: #f9f9f9;
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .qc-checkbox-label:hover,
    .qc-radio-label:hover {
        background: #eee;
    }

    .qc-checkbox-label input[type="checkbox"],
    .qc-radio-label input[type="radio"] {
        margin-right: 5px;
    }

    /* --- Shortcode Output Styles --- */
    #qc-shortcode-output {
        width: 100%;
        font-family: monospace;
        font-size: 14px;
        margin-bottom: 10px;
    }
</style>

<script type="text/javascript">
    jQuery(document).ready(function($) {

        // TAB SWITCHING LOGIC
        $('.qc-tab-nav-item').on('click', function() {
            $('.qc-tab-nav-item').removeClass('active');
            $(this).addClass('active');
            var tabID = $(this).data('tab');
            $('.qc-tab-panel').removeClass('active');
            $('#' + tabID).addClass('active');
        });

        // Toggle between Filter and Exclude fields in the taxonomy tab.
        $('input[name="qc_filter_mode"]').on('change', function() {
            var mode = $(this).val();
            if (mode === 'include') {
                $('#qc-filter-fields').show();
                $('#qc-exclude-fields').hide();
            } else {
                $('#qc-filter-fields').hide();
                $('#qc-exclude-fields').show();
            }
        });

        // GATHER & GENERATE SHORTCODE
        function generateShortcode() {
            // Post Types (checkboxes)
            var postTypes = [];
            $('input[name="qc_post_type[]"]:checked').each(function() {
                postTypes.push($(this).val());
            });
            var pt = (postTypes.length > 0) ? postTypes.join(',') : 'post';

            // Pagination Type (radio)
            var paged = $('input[name="qc_paged"]:checked').val() || 'numbered';

            // Order By (radio)
            var orderby = $('input[name="qc_orderby"]:checked').val() || 'date';

            // Order (radio)
            var order = $('input[name="qc_order"]:checked').val() || 'DESC';

            // Post Status (checkboxes)
            var statuses = [];
            $('input[name="qc_status[]"]:checked').each(function() {
                statuses.push($(this).val());
            });

            var display = $('#qc-display').val();
            var template = $('#qc-template').val();
            var offset = $('#qc-offset').val();
            var max_total = $('#qc-max-total').val();

            // For taxonomy/exclusion, check the filter mode.
            var filterMode = $('input[name="qc_filter_mode"]:checked').val();
            var taxonomy = '';
            var term = '';
            var excluded_taxonomy = '';
            var excluded_term = '';

            if (filterMode === 'include') {
                taxonomy = $('#qc-taxonomy').val();
                term = $('#qc-term').val();
            } else {
                excluded_taxonomy = $('#qc-excluded-taxonomy').val();
                excluded_term = $('#qc-excluded-term').val();
            }

            var cta_template = $('#qc-cta-template').val();
            var cta_interval = $('#qc-cta-interval').val();
            var meta_key = $('#qc-meta-key').val();
            var meta_value = $('#qc-meta-value').val();
            var compare = $('#qc-compare').val();

            // Extras: Container Class
            var container_class = $('#qc-container-class').val();

            var shortcode = '[load';
            shortcode += ' pt="' + pt + '"';
            shortcode += ' display="' + display + '"';
            shortcode += ' paged="' + paged + '"';
            shortcode += ' template="' + template + '"';
            shortcode += ' orderby="' + orderby + '"';
            shortcode += ' order="' + order + '"';

            if (statuses.length > 0) {
                shortcode += ' status="' + statuses.join(',') + '"';
            }
            if (filterMode === 'include') {
                if (taxonomy !== '') {
                    shortcode += ' taxonomy="' + taxonomy + '"';
                }
                if (term !== '') {
                    shortcode += ' term="' + term + '"';
                }
            } else {
                if (excluded_taxonomy !== '') {
                    shortcode += ' excluded_taxonomy="' + excluded_taxonomy + '"';
                }
                if (excluded_term !== '') {
                    shortcode += ' excluded_term="' + excluded_term + '"';
                }
            }
            if (cta_template !== '') {
                shortcode += ' cta_template="' + cta_template + '"';
            }
            if (cta_interval > 0) {
                shortcode += ' cta_interval="' + cta_interval + '"';
            }
            if (meta_key !== '') {
                shortcode += ' meta_key="' + meta_key + '"';
            }
            if (meta_value !== '') {
                shortcode += ' meta_value="' + meta_value + '"';
            }
            if (compare !== '') {
                shortcode += ' compare="' + compare + '"';
            }
            if (offset !== '' && offset !== '0') {
                shortcode += ' offset="' + offset + '"';
            }
            if (max_total !== '' && max_total !== '0') {
                shortcode += ' max_total="' + max_total + '"';
            }
            if (container_class !== '') {
                shortcode += ' container-class="' + container_class + '"';
            }
            shortcode += ']';

            $('#qc-shortcode-output').val(shortcode);
        }

        generateShortcode();

        $('#querycraft-shortcode-generator').on('input change', 'input, select', function() {
            generateShortcode();
        });

        $('#qc-copy-btn').on('click', function() {
            var $textarea = $('#qc-shortcode-output');
            $textarea.select();
            document.execCommand('copy');
            var $notice = $('<div class="qc-notice">Copied to Clipboard</div>');
            $('body').append($notice);
            $notice.delay(2000).fadeOut(500, function() {
                $(this).remove();
            });
        });

        $('#qc-taxonomy').on('change', function() {
            var taxonomy = $(this).val();
            $('#qc-term').html('<option value="">None</option>');
            if (taxonomy !== '') {
                $.ajax({
                    url: ajaxurl,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'querycraft_get_terms',
                        taxonomy: taxonomy
                    },
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="">None</option>';
                            $.each(response.data, function(i, term) {
                                options += '<option value="' + term.id + '">' + term.text + '</option>';
                            });
                            $('#qc-term').html(options);
                        }
                    }
                });
            }
        });

        $('#qc-excluded-taxonomy').on('change', function() {
            var taxonomy = $(this).val();
            $('#qc-excluded-term').html('<option value="">None</option>');
            if (taxonomy !== '') {
                $.ajax({
                    url: ajaxurl,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'querycraft_get_terms',
                        taxonomy: taxonomy
                    },
                    success: function(response) {
                        if (response.success) {
                            var options = '<option value="">None</option>';
                            $.each(response.data, function(i, term) {
                                options += '<option value="' + term.id + '">' + term.text + '</option>';
                            });
                            $('#qc-excluded-term').html(options);
                        }
                    }
                });
            }
        });
    });
</script>