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
    <style>
    .categories-carousel-slide { flex-shrink: 0; width: 70%; }
    </style>
    <div class="w-full mt-[40px] lg:mt-[70px] mb-[100px]">
        <div class="w-full mx-auto max-w-7xl px-4">
            <?php if (isset($homepage_product_categories["title"]) && $homepage_product_categories["title"]) : ?>
                <h2 class="text-3xl md:text-4xl mb-2"><?php echo esc_html($homepage_product_categories["title"]); ?></h2>
            <?php endif; ?>
            <?php if (isset($homepage_product_categories["sub_title"]) && $homepage_product_categories["sub_title"]) : ?>
                <h3 class="text-xl md:text-2xl mb-8 text-enduro-red-100"><?php echo esc_html($homepage_product_categories["sub_title"]); ?></h3>
            <?php endif; ?>

            <!-- Mobile carousel -->
            <div class="block lg:hidden relative">
                <div class="overflow-hidden">
                    <div class="flex gap-4 transition-transform duration-300 ease-in-out" id="categories-carousel-track">
                        <?php foreach ($product_categories as $category) :
                            $category_link = get_term_link($category);
                            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                            $image_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : '';
                        ?>
                            <div class="categories-carousel-slide">
                                <div class="border border-gray-200 rounded overflow-hidden hover:border-enduro-red-100 transition group">
                                    <a href="<?php echo esc_url($category_link); ?>" class="block">
                                        <?php if ($image_url) : ?>
                                            <div class="w-full h-55 bg-cover bg-center bg-no-repeat transition-transform duration-300 group-hover:scale-105" style="background-image: url(<?php echo esc_url($image_url); ?>);"></div>
                                        <?php else : ?>
                                            <div class="w-full h-55 bg-enduro-grey-400 flex items-center justify-center">
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
                                        </div>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-2 bg-enduro-red-100 hover:bg-enduro-red-200 text-white rounded-full w-9 h-9 flex items-center justify-center transition z-10" onclick="categoriesCarousel('prev')" aria-label="Previous">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-2 bg-enduro-red-100 hover:bg-enduro-red-200 text-white rounded-full w-9 h-9 flex items-center justify-center transition z-10" onclick="categoriesCarousel('next')" aria-label="Next">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            <!-- Desktop grid -->
            <div class="hidden lg:grid grid-cols-5 gap-4">
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

    <script>
    (function() {
        var currentSlide = 0;
        var track = document.getElementById('categories-carousel-track');
        var slides = track ? track.querySelectorAll('.categories-carousel-slide') : [];
        var totalSlides = slides.length;

        window.categoriesCarousel = function(direction) {
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

<?php endif; ?>
