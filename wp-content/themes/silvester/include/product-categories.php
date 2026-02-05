<?php
$product_categories_args = array(
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'parent'     => 0, // Get only top-level categories
    'orderby'    => 'menu_order',
    'order'      => 'ASC'
);

$product_categories = get_terms($product_categories_args);

$homepage_product_categories = get_field('home_page_categories');

if (!empty($product_categories) && !is_wp_error($product_categories)) : ?>
    <div class="w-full mt-[100px] mb-[100px]">
        <div class="w-full mx-auto max-w-6xl px-4">
            <?php if (isset($homepage_product_categories["title"]) && $homepage_product_categories["title"]) : ?>
                <h2 class="text-4xl mb-4"><?php echo esc_html($homepage_product_categories["title"]); ?></h2>
            <?php endif; ?>
            <?php if (isset($homepage_product_categories["sub_title"]) && $homepage_product_categories["sub_title"]) : ?>
                <h3 class="text-2xl mb-8 text-enduro-red-100"><?php echo esc_html($homepage_product_categories["sub_title"]); ?></h3>
            <?php endif; ?>
            <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($product_categories as $category) : 
                    $category_link = get_term_link($category);
                    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                    $image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : '';
                ?>
                    <div class="w-full border border-gray-200 rounded overflow-hidden hover:border-enduro-red-100 transition group">
                        <a href="<?php echo esc_url($category_link); ?>" class="block">
                            <?php if ($image_url) : ?>
                                <div class="w-full h-[300px] bg-cover bg-center bg-no-repeat transition-transform duration-300 group-hover:scale-105" style="background-image: url(<?php echo esc_url($image_url); ?>);"></div>
                            <?php else : ?>
                                <div class="w-full h-[300px] bg-enduro-grey-400 flex items-center justify-center">
                                    <span class="text-enduro-grey-600"><?php echo esc_html__('No image', 'silvester'); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="p-4">
                                <h3 class="text-xl mb-2 text-enduro-grey-900 group-hover:text-enduro-red-100 transition">
                                    <?php echo esc_html($category->name); ?>
                                </h3>
                                <?php if ($category->count > 0) : ?>
                                    <p class="text-enduro-grey-500 text-sm">
                                        <?php echo esc_html($category->count); ?> 
                                        <?php echo esc_html(_n('product', 'products', $category->count, 'silvester')); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($category->description) : ?>
                                    <p class="text-enduro-grey-400 text-sm mt-2 line-clamp-2">
                                        <?php echo esc_html(wp_trim_words($category->description, 15)); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
