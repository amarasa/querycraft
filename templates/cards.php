<?php

/**
 * Cards Template for QueryCraft
 *
 * Variables passed in:
 * - $post (WP_Post object)
 */

$categories = get_the_category($post->ID);
$first_category_name = '';
$first_category_link = '#';

if (! empty($categories)) {
    $first_category_name = $categories[0]->name;
    $first_category_link = get_category_link($categories[0]->term_id);
}

$featured_image = get_the_post_thumbnail($post->ID, 'large', [
    'class' => 'w-full h-auto object-cover'
]);
?>

<li class="mb-4">
    <div class="rounded-lg shadow-md p-4 bg-white hover:shadow-lg transition-shadow elevation-2 animate-fadeIn flex flex-col h-full">
        <?php if ($featured_image) : ?>
            <div class="w-full h-48 overflow-hidden rounded-t-md mb-2">
                <a href="<?php echo esc_url(get_permalink($post)); ?>">
                    <?php echo $featured_image; ?>
                </a>
            </div>
        <?php endif; ?>
        <?php if ($first_category_name) : ?>
            <a href="<?php echo esc_url($first_category_link); ?>"
                class="bg-gray-200 text-gray-700 text-xs font-semibold px-2 py-1 rounded-full inline-block">
                <?php echo esc_html($first_category_name); ?>
            </a>
        <?php endif; ?>
        <h2 class="text-xl font-bold mt-2 line-clamp-2">
            <a href="<?php echo esc_url(get_permalink($post)); ?>">
                <?php echo esc_html(get_the_title($post)); ?>
            </a>
        </h2>
        <p class="text-sm text-gray-700 line-clamp-3 mt-2">
            <?php echo esc_html(get_the_excerpt($post)); ?>
        </p>
        <a href="<?php echo esc_url(get_permalink($post)); ?>"
            class="text-indigo-500 font-semibold mt-auto hover:underline animate-bounce">
            Read More »
        </a>
        <span class="text-xs text-gray-500 mt-2">
            <?php echo get_the_date('', $post->ID); ?>
        </span>
    </div>
</li>