<?php
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 6,
    'orderby' => 'date',
    'order' => 'DESC',
    'status' => 'publish'
);

$products_query = new WP_Query($args);

if ($products_query->have_posts()) : ?>
    <div class="w-full mt-[100px]">
        <div class="w-full mx-auto max-w-6xl px-4">
            <!-- Display latest 6 products -->
            <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php while ($products_query->have_posts()) : $products_query->the_post(); 
                    global $product;
                    if (!$product) continue;
                ?>
                    <div class="w-full border border-gray-200 rounded overflow-hidden hover:border-enduro-red-100 transition">
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="block">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="w-full h-[300px] bg-cover bg-center bg-no-repeat" style="background-image: url(<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>);"></div>
                            <?php else : ?>
                                <div class="w-full h-[300px] bg-enduro-grey-400 flex items-center justify-center">
                                    <span class="text-enduro-grey-600"><?php echo esc_html__('No image', 'silvester'); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="p-4">
                                <h3 class="text-xl mb-2 text-enduro-grey-900 hover:text-enduro-red-100 transition"><?php echo esc_html(get_the_title()); ?></h3>
                                <div class="text-enduro-red-100 text-lg font-medium">
                                    <?php echo $product->get_price_html(); ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <?php wp_reset_postdata(); ?>
<?php endif; ?>
