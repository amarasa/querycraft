<?php

namespace QueryCraft\Admin;

/**
 * QueryCraft Developer Documentation
 *
 * This page provides a comprehensive guide to QueryCraft’s templating system,
 * CTA integration, custom field filtering, hooks, and how to build your own templates.
 *
 * @package QueryCraft
 */

if (! current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>QueryCraft Developer Documentation</title>
    <!-- Highlight.js for syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>
        hljs.highlightAll();
    </script>
    <style>
        /* Developer Documentation Page Styles */
        body {
            background: #f8f8f8;
            font-family: "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }

        .qc-doc-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px 40px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }

        .qc-doc-container h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 32px;
            color: #222;
        }

        .qc-doc-container h2 {
            font-size: 24px;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            color: #444;
        }

        .qc-doc-container h3 {
            font-size: 20px;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #555;
        }

        .qc-doc-container p {
            font-size: 16px;
            margin-bottom: 15px;
        }

        .qc-doc-container pre {
            background: #f1f1f1;
            padding: 15px;
            border-left: 4px solid #0073aa;
            overflow: auto;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .qc-doc-container code {
            font-family: Consolas, Monaco, 'Andale Mono', 'Ubuntu Mono', monospace;
        }

        .qc-doc-container ul,
        .qc-doc-container ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .qc-doc-container a {
            color: #0073aa;
            text-decoration: none;
        }

        .qc-doc-container a:hover {
            text-decoration: underline;
        }

        .toc {
            background: #eef;
            border: 1px solid #ccd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 30px;
        }

        .toc h2 {
            margin-top: 0;
            font-size: 20px;
        }

        .toc ul {
            list-style: none;
            padding-left: 0;
        }

        .toc li {
            margin-bottom: 8px;
        }

        .toc a {
            text-decoration: none;
            color: #0073aa;
        }

        .toc a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="qc-doc-container">
        <h1>QueryCraft Developer Documentation</h1>
        <div class="toc">
            <h2>Table of Contents</h2>
            <ul>
                <li><a href="#templating">1. Templating System Overview</a></li>
                <li><a href="#cta">2. CTA Integration</a></li>
                <li><a href="#custom-fields">3. Custom Field Filtering</a></li>
                <li><a href="#building-template">4. Building a Custom Template</a></li>
                <li><a href="#hooks">5. Hooks and Extensibility</a></li>
                <li><a href="#faqs">6. Frequently Asked Questions</a></li>
                <li><a href="#tips">7. Additional Developer Tips</a></li>
                <li><a href="#styling-pagination">8. Styling Pagination with CSS</a></li>
                <li><a href="#conclusion">9. Conclusion</a></li>
            </ul>
        </div>
        <h2 id="templating">1. Templating System Overview</h2>
        <p>QueryCraft uses a flexible templating system to render post listings. The process works as follows:</p>
        <ol>
            <li>The function <code>querycraft_get_template( $template, $args )</code> loads a template file based on the <code>template</code> shortcode parameter.</li>
            <li>It first checks for an override in your active theme's <code>querycraft/templates/</code> folder. If a matching file is found, that file is used.</li>
            <li>If no override exists, the default template in the plugin’s <code>templates/</code> folder is loaded.</li>
            <li>You can create multiple templates (e.g., <code>cards.php</code>, <code>list.php</code>, <code>blurb.php</code>) for different layouts.</li>
        </ol>
        <h2 id="cta">2. CTA Integration</h2>
        <p>QueryCraft supports automatic insertion of Call-To-Actions (CTAs) within the post loop. It works as follows:</p>
        <ol>
            <li>If you include the <code>cta_template</code> and <code>cta_interval</code> parameters in the shortcode, QueryCraft will insert the specified CTA after every <code>cta_interval</code> posts by calling <code>querycraft_get_cta( $cta_template )</code>.</li>
            <li>CTA templates are loaded from your active theme's <code>querycraft/cta/</code> folder if available, or from the plugin’s default <code>cta/</code> folder.</li>
            <li>This allows you to easily integrate promotional content or custom elements without modifying core code.</li>
        </ol>
        <h2 id="custom-fields">3. Custom Field Filtering</h2>
        <p>QueryCraft supports filtering posts by custom fields using the following shortcode parameters:</p>
        <ul>
            <li><code>meta_key</code>: The custom field name.</li>
            <li><code>meta_value</code>: The value to filter by.</li>
            <li><code>compare</code>: The comparison operator (e.g., <code>=</code>, <code>!=</code>, <code>&gt;</code>, <code>&lt;</code>, <code>&gt;=</code>, <code>&lt;=</code>).</li>
        </ul>
        <p>For example, to display posts where the custom field <code>rating</code> is 4 or higher:</p>
        <pre><code class="language-markup">[load pt="post" display="6" paged="numbered" template="cards" orderby="date" order="DESC" status="publish" meta_key="rating" meta_value="4" compare=">="]</code></pre>
        <h2 id="building-template">4. Building a Custom Template</h2>
        <p>When creating a custom template for QueryCraft, it's critical to understand the PHP scope and context in which your template is loaded:</p>
        <ul>
            <li><strong>Inside the Loop:</strong> QueryCraft calls <code>$query->the_post()</code> before including your template file. This sets up the global <code>$post</code> variable, so you can use standard WordPress functions like <code>the_title()</code>, <code>the_permalink()</code>, and <code>the_content()</code> without any additional setup.</li>
            <li><strong>Passed Variables:</strong> In addition, QueryCraft passes an array of arguments to your template (typically including the current <code>WP_Post</code> object as <code>$post</code>). This gives you direct access to the post data if needed.</li>
        </ul>
        <h3>Example 1: Using Standard Template Tags</h3>
        <pre><code class="language-php">
&lt;?php
// File: title.php in your theme's querycraft/templates/ folder
the_title('&lt;h2&gt;', '&lt;/h2&gt;');
?&gt;
        </code></pre>
        <h3>Example 2: Using the Passed <code>$post</code> Object</h3>
        <pre><code class="language-php">
&lt;?php
// File: custom.php in your theme's querycraft/templates/ folder
if ( isset( $post ) ) {
    echo '&lt;h2&gt;' . esc_html( $post->post_title ) . '&lt;/h2&gt;';
    echo '&lt;div&gt;' . apply_filters( 'the_content', $post->post_content ) . '&lt;/div&gt;';
}
?&gt;
        </code></pre>
        <p><strong>Note:</strong> You do not need to call <code>setup_postdata()</code> in your template because QueryCraft already does that for each post in the loop.</p>
        <h2 id="hooks">5. Hooks and Extensibility</h2>
        <p>QueryCraft provides action hooks to allow you to extend or modify its output without changing core code:</p>
        <ul>
            <li><code>querycraft_before_loop</code>: Fires immediately before the post loop begins.</li>
            <li><code>querycraft_after_loop</code>: Fires immediately after the post loop ends.</li>
        </ul>
        <h3>Example: Before Loop Hook</h3>
        <pre><code class="language-php">
&lt;?php
function my_querycraft_before_loop( $atts, $query ) {
    echo '&lt;div class="custom-before"&gt;This content appears before the post loop.&lt;/div&gt;';
}
add_action( 'querycraft_before_loop', 'my_querycraft_before_loop', 10, 2 );
?&gt;
        </code></pre>
        <h3>Example: After Loop Hook</h3>
        <pre><code class="language-php">
&lt;?php
function my_querycraft_after_loop( $atts, $query ) {
    echo '&lt;div class="custom-after"&gt;This content appears after the post loop.&lt;/div&gt;';
}
add_action( 'querycraft_after_loop', 'my_querycraft_after_loop', 10, 2 );
?&gt;
        </code></pre>
        <h2 id="faqs">6. Frequently Asked Questions (FAQs)</h2>
        <h3>Q: How do I override a default template?</h3>
        <p><strong>A:</strong> Copy the template file (e.g., <code>cards.php</code>) from the plugin’s <code>templates/</code> folder into your active theme’s <code>querycraft/templates/</code> folder. QueryCraft will automatically use your version.</p>
        <h3>Q: Do I need to call <code>setup_postdata()</code> in my custom template?</h3>
        <p><strong>A:</strong> No. QueryCraft calls <code>$query->the_post()</code> before including your template, so the global <code>$post</code> variable is already set up for you.</p>
        <h3>Q: How can I insert a custom CTA within the post loop?</h3>
        <p><strong>A:</strong> Use the <code>cta_template</code> and <code>cta_interval</code> shortcode parameters. QueryCraft will insert the specified CTA every <code>cta_interval</code> posts by calling <code>querycraft_get_cta( $cta_template )</code>.</p>
        <h2 id="tips">7. Additional Developer Tips</h2>
        <ul>
            <li>Use the action hooks (<code>querycraft_before_loop</code> and <code>querycraft_after_loop</code>) to inject additional markup or functionality without modifying core files.</li>
            <li>Test your template overrides in a staging environment before deploying to production.</li>
            <li>Review inline comments in the plugin source code for deeper insights into QueryCraft’s architecture.</li>
        </ul>
        <h2 id="styling-pagination">8. Styling Pagination with CSS</h2>
        <p>
            QueryCraft outputs pagination in two main formats: <strong>Numbered Pagination</strong> and <strong>Previous/Next Pagination</strong>. Both are styled using Tailwind CSS by default, but since the markup is stable, you can easily override these styles in your theme's stylesheet without modifying plugin files.
        </p>
        <h3>Numbered Pagination</h3>
        <p>
            The numbered pagination is rendered inside a <code>&lt;nav&gt;</code> element with classes such as <code>flex items-center justify-center space-x-2 my-4</code>. Each page link is given additional classes for styling.
        </p>
        <p>For example, to override the background color and padding for numbered links, you could add the following CSS to your theme’s stylesheet:</p>
        <pre><code class="language-css">
/* Custom styles for numbered pagination links */
nav[aria-label="Pagination"] a {
  @apply bg-blue-600 text-white px-3 py-1 rounded;
}

/* Custom styles for the current page link */
nav[aria-label="Pagination"] span {
  @apply bg-blue-800 text-white px-3 py-1 rounded;
}
        </code></pre>
        <h3>Previous/Next Pagination</h3>
        <p>
            The previous/next pagination outputs links with Tailwind classes such as <code>px-4 py-2 border border-gray-700 rounded bg-gray-800 text-gray-300 hover:bg-gray-700 transition</code>. To override these, you can add custom CSS in your theme.
        </p>
        <p>For instance, to change the background color and border for previous/next links:</p>
        <pre><code class="language-css">
/* Custom styles for previous/next pagination links */
nav[aria-label="Pagination"] a {
  @apply bg-green-600 text-white px-4 py-2 rounded border border-green-700 hover:bg-green-700 transition;
}
        </code></pre>
        <p>
            Simply add these CSS rules to your theme’s stylesheet (or via a custom CSS plugin) to override the default Tailwind classes provided by QueryCraft. No changes to the plugin’s PHP code or markup are necessary.
        </p>
        <h2 id="conclusion">9. Conclusion</h2>
        <p>
            This documentation serves as a comprehensive guide for developers looking to extend and customize QueryCraft. With its flexible templating system, integrated CTA features, and powerful hooks, you can create custom solutions without modifying core code. For further details, please refer to the plugin repository or contact support.
        </p>
    </div>
</body>

</html>