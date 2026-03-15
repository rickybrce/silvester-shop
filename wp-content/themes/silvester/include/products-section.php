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
    <style>
    .products-carousel-slide { flex-shrink: 0; width: 80%; }
    @media (min-width: 768px) { .products-carousel-slide { width: auto; } }
    </style>
    <div class="w-full mt-[40px] lg:mt-[70px]">
        <div class="w-full mx-auto max-w-7xl px-4">

            <!-- Mobile carousel -->
            <div class="block md:hidden relative">
                <div class="overflow-hidden">
                    <div class="flex gap-4 transition-transform duration-300 ease-in-out" id="products-carousel-track">
                        <?php
                        $products_query->rewind_posts();
                        while ($products_query->have_posts()) : $products_query->the_post();
                            global $product;
                            if (!$product) continue;
                        ?>
                            <div class="products-carousel-slide shrink-0">
                                <div class="border border-gray-200 rounded overflow-hidden hover:border-enduro-red-100 transition h-full">
                                    <a href="<?php echo esc_url(get_permalink()); ?>" class="block">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="w-full h-65 bg-cover bg-center bg-no-repeat" style="background-image: url(<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>);"></div>
                                        <?php else : ?>
                                            <div class="w-full h-65 bg-enduro-grey-400 flex items-center justify-center">
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
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <button class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-2 bg-enduro-red-100 hover:bg-enduro-red-200 text-white rounded-full w-9 h-9 flex items-center justify-center transition z-10" onclick="productsCarousel('prev')" aria-label="Previous">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-2 bg-enduro-red-100 hover:bg-enduro-red-200 text-white rounded-full w-9 h-9 flex items-center justify-center transition z-10" onclick="productsCarousel('next')" aria-label="Next">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- Desktop grid -->
            <div class="hidden md:grid grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $products_query->rewind_posts();
                while ($products_query->have_posts()) : $products_query->the_post();
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

    <script>
    (function() {
        var currentSlide = 0;
        var track = document.getElementById('products-carousel-track');
        var slides = track ? track.querySelectorAll('.products-carousel-slide') : [];
        var totalSlides = slides.length;

        window.productsCarousel = function(direction) {
            if (!slides.length) return;
            var slideWidth = slides[0].offsetWidth + 16;
            if (direction === 'next') {
                currentSlide = Math.min(currentSlide + 1, totalSlides - 1);
            } else {
                currentSlide = Math.max(currentSlide - 1, 0);
            }
            track.style.transform = 'translateX(-' + (currentSlide * slideWidth) + 'px)';
        };
    })();
    </script>

    <?php wp_reset_postdata(); ?>
<?php endif; ?>
