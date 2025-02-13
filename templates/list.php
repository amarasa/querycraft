<?php

/**
 * List View Template for QueryCraft
 *
 * Variables passed in:
 * - $post (WP_Post object)
 */

$featured_image = get_the_post_thumbnail($post->ID, 'medium', [
    'class' => 'w-full h-auto object-cover rounded'
]);

$author_name = get_the_author_meta('display_name', $post->post_author);
$post_date = get_the_date('', $post->ID);

$categories = get_the_category($post->ID);
$cat_links = [];
if (! empty($categories)) {
    foreach ($categories as $cat) {
        $cat_links[] = sprintf(
            '<a href="%s" class="text-indigo-500 hover:underline">%s</a>',
            esc_url(get_category_link($cat->term_id)),
            esc_html($cat->name)
        );
    }
}
$cats_output = implode(', ', $cat_links);
$excerpt = get_the_excerpt($post);
?>

<li class="mb-4">
    <div class="flex flex-col md:flex-row bg-white rounded shadow p-4 gap-4">
        <div class="w-full md:w-1/3 flex-shrink-0">
            <?php if ($featured_image) : ?>
                <a href="<?php echo esc_url(get_permalink($post)); ?>">
                    <?php echo $featured_image; ?>
                </a>
            <?php else : ?>
                <div class="bg-gray-200 w-full h-48 rounded"></div>
            <?php endif; ?>
        </div>
        <div class="w-full md:w-2/3 flex flex-col justify-between">
            <h2 class="text-xl font-bold mb-2">
                <a href="<?php echo esc_url(get_permalink($post)); ?>" class="hover:underline">
                    <?php echo esc_html(get_the_title($post)); ?>
                </a>
            </h2>
            <div class="text-sm text-gray-600 mb-3">
                <?php if ($author_name) : ?>
                    By <?php echo esc_html($author_name); ?>
                <?php endif; ?>
                <?php if ($post_date) : ?>
                    <span class="mx-2">&bull;</span>
                    <?php echo esc_html($post_date); ?>
                <?php endif; ?>
                <?php if ($cats_output) : ?>
                    <span class="mx-2">&bull;</span>
                    <?php echo wp_kses_post($cats_output); ?>
                <?php endif; ?>
            </div>
            <p class="text-gray-700 text-sm line-clamp-3 mb-4">
                <?php echo esc_html($excerpt); ?>
            </p>
            <div>
                <a href="<?php echo esc_url(get_permalink($post)); ?>"
                    class="inline-block px-4 py-2 bg-indigo-500 text-white text-sm font-medium rounded hover:bg-indigo-600">
                    Read More
                </a>
            </div>
        </div>
    </div>
</li>