<?php
$homepage_motors = get_field('home_page_motors');

$motors_category = get_term_by('slug', 'motocikli', 'product_cat');

if ($motors_category) :
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'status'         => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'motocikli',
            ),
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
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

                <style>
                .motors-slide { flex-shrink: 0; width: 100%; }
                @media (min-width: 640px)  { .motors-slide { width: calc(50% - 8px); } }
                @media (min-width: 1024px) { .motors-slide { width: calc(33.333% - 10.667px); } }
                </style>

                <!-- Carousel Container -->
                <div class="relative" id="motors-carousel-wrapper">
                    <div class="carousel-container overflow-hidden">
                        <div class="flex gap-4 transition-transform duration-500 ease-in-out items-stretch" id="motors-carousel-track">
                            <?php while ($motors_query->have_posts()) : $motors_query->the_post();
                                global $product;
                                if (!$product) continue;
                            ?>
                                <div class="motors-slide flex flex-col">
                                    <div class="flex flex-col h-full border border-gray-200 rounded overflow-hidden hover:border-enduro-red-100 transition">
                                        <a href="<?php echo esc_url(get_permalink()); ?>" class="block">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <div class="w-full h-[300px] bg-cover bg-center bg-no-repeat" style="background-image: url(<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>);"></div>
                                            <?php else : ?>
                                                <div class="w-full h-[300px] bg-enduro-grey-400 flex items-center justify-center">
                                                    <span class="text-enduro-grey-600"><?php echo esc_html__('Bez slike', 'silvester'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                        <div class="p-4 flex flex-col flex-1">
                                            <a href="<?php echo esc_url(get_permalink()); ?>">
                                                <h3 class="text-xl mb-2 text-enduro-grey-900 hover:text-enduro-red-100 transition"><?php echo esc_html(get_the_title()); ?></h3>
                                            </a>
                                            <div class="text-enduro-red-100 text-lg font-medium mb-4">
                                                <?php echo $product->get_price_html(); ?>
                                            </div>
                                            <div class="flex flex-wrap gap-2 mt-auto">
                                                <a href="<?php echo esc_url(get_permalink()); ?>" class="inline-block border border-enduro-red-100 text-enduro-red-100 hover:bg-enduro-red-100 hover:text-white py-2 px-4 rounded text-sm font-medium transition">
                                                    <?php echo esc_html__('Pogledaj više', 'silvester'); ?>
                                                </a>
                                                <?php if ($product->is_purchasable() && $product->is_in_stock() && !silvester_has_selectable_attrs($product)) : ?>
                                                    <a href="<?php echo esc_url($product->add_to_cart_url()); ?>"
                                                       data-product_id="<?php echo esc_attr($product->get_id()); ?>"
                                                       data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
                                                       data-quantity="1"
                                                       rel="nofollow"
                                                       class="add_to_cart_button ajax_add_to_cart inline-block bg-gradient-to-b from-enduro-red-100 to-enduro-red-200 hover:from-enduro-red-200 hover:to-enduro-red-100 text-white py-2 px-4 rounded text-sm font-medium transition">
                                                        <?php echo esc_html__('Kupi odmah', 'silvester'); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Navigation Arrows -->
                    <button class="carousel-nav absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 lg:-translate-x-12 bg-enduro-red-100 hover:bg-enduro-red-200 text-white rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center transition z-10" onclick="motorsScrollCarousel('prev')" aria-label="Previous">
                        <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button class="carousel-nav absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 lg:translate-x-12 bg-enduro-red-100 hover:bg-enduro-red-200 text-white rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center transition z-10" onclick="motorsScrollCarousel('next')" aria-label="Next">
                        <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <script>
        (function() {
            var currentSlide = 0;
            var track   = document.getElementById('motors-carousel-track');
            var wrapper = document.getElementById('motors-carousel-wrapper');
            var slides  = track ? track.querySelectorAll('.motors-slide') : [];
            var total   = slides.length;
            var timer   = null;

            function visible() {
                if (window.innerWidth >= 1024) return 3;
                if (window.innerWidth >= 640)  return 2;
                return 1;
            }

            function goTo(idx) {
                var n   = visible();
                var max = Math.max(0, total - n);
                currentSlide = idx < 0 ? max : (idx > max ? 0 : idx);
                var w = slides[0] ? slides[0].offsetWidth + 16 : 0;
                if (track) track.style.transform = 'translateX(-' + (currentSlide * w) + 'px)';
            }

            function startAutoplay() {
                stopAutoplay();
                timer = setInterval(function() { goTo(currentSlide + 1); }, 3000);
            }

            function stopAutoplay() {
                if (timer) { clearInterval(timer); timer = null; }
            }

            window.motorsScrollCarousel = function(dir) {
                stopAutoplay();
                goTo(currentSlide + (dir === 'next' ? 1 : -1));
                startAutoplay();
            };

            if (wrapper) {
                wrapper.addEventListener('mouseenter', stopAutoplay);
                wrapper.addEventListener('mouseleave', startAutoplay);
            }

            window.addEventListener('resize', function() { goTo(currentSlide); });

            startAutoplay();
        })();
        </script>

        <?php wp_reset_postdata(); ?>
    <?php endif;
endif; ?>
