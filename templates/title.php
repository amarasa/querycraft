<?php

/**
 * Default Title-Only Template for QueryCraft.
 *
 * Variables passed in:
 *   - $post : The current WP_Post object.
 */
?>
<li>
    <a href="<?php echo esc_url(get_permalink($post)); ?>">
        <?php echo esc_html(get_the_title($post)); ?>
    </a>
</li>