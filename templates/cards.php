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
    'class' => 'w-full absolute top-0 left-0 right-0 bottom-0 h-full object-cover translate-z-[1px]'
]);
?>

<div class="col-span-12 md:col-span-6 lg:col-span-4">
    <div class="shadow-md transition-shadow elevation-3 animate-fadeIn h-full">
        <?php if ($featured_image) : ?>
            <div class="eh-feature-image w-full relative pb-[50%]">
                <a href="<?php echo esc_url(get_permalink($post)); ?>">
                    <?php echo $featured_image; ?>
                </a>

                <?php if ($first_category_name) : ?>
                    <a href="<?php echo esc_url($first_category_link); ?>"
                        class="bg-zinc-500 text-white absolute z-10 top-[15px] right-[20px] text-xs font-semibold px-2 py-1 rounded-full inline-block">
                        <?php echo esc_html($first_category_name); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="bg-white rounded-br-lg rounded-bl-lg pt-6 px-8">
            <span class="text-xs text-gray-500 mt-2">
                <?php echo get_the_date('', $post->ID); ?>
            </span>
            <h3 class="eh-card-title text-xl font-bold mt-2">
                <a href="<?php echo esc_url(get_permalink($post)); ?>">
                    <?php echo esc_html(get_the_title($post)); ?>
                </a>
            </h3>
            <p class="eh-excerpt text-sm text-gray-700 line-clamp-3 mt-2 mb-8">
                <?php echo esc_html(get_the_excerpt($post)); ?>
            </p>
        </div>
        <a href="<?php echo esc_url(get_permalink($post)); ?>"
            class="button block bg-slate-400 px-5 py-3 text-white text-center">
            Read More
        </a>
    </div>
</div>