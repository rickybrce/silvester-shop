<?php
$homepage_motors = get_field('home_page_motors');

$motors_category = get_term_by('slug', 'motocikli', 'product_cat');

if ($motors_category) :
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 3,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'status'         => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'motocikli',
            ),
        ),
    );

    $motors_query = new WP_Query($args);

    if ($motors_query->have_posts()) : ?>
        <div class="w-full mt-[40px]">
            <div class="w-full mx-auto max-w-7xl px-4">
                <?php if (isset($homepage_motors["title"]) && $homepage_motors["title"]) : ?>
                    <h2 class="text-3xl md:text-4xl mb-2"><?php echo esc_html($homepage_motors["title"]); ?></h2>
                <?php endif; ?>
                <?php if (isset($homepage_motors["sub_title"]) && $homepage_motors["sub_title"]) : ?>
                    <h3 class="text-xl md:text-2xl mb-8 text-enduro-red-100"><?php echo esc_html($homepage_motors["sub_title"]); ?></h3>
                <?php endif; ?>
                <div class="w-full grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php while ($motors_query->have_posts()) : $motors_query->the_post();
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
                                    <div class="text-enduro-red-100 text-lg font-medium mb-4">
                                        <?php echo $product->get_price_html(); ?>
                                    </div>
                                    <div class="flex flex-wrap gap-2 pl-0">
                                        <a href="<?php echo esc_url(get_permalink()); ?>" class="inline-block border border-enduro-red-100 text-enduro-red-100 hover:bg-enduro-red-100 hover:text-white py-2 px-4 rounded text-sm font-medium transition">
                                            <?php echo esc_html__('Pogledaj više', 'silvester'); ?>
                                        </a>
                                        <?php if ($product->is_purchasable() && $product->is_in_stock()) : ?>
                                            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="inline-block bg-gradient-to-b from-enduro-red-100 to-enduro-red-200 hover:from-enduro-red-200 hover:to-enduro-red-100 text-white py-2 px-4 rounded text-sm font-medium transition">
                                                <?php echo esc_html__('Kupi odmah', 'silvester'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php wp_reset_postdata(); ?>
    <?php endif;
endif; ?>
