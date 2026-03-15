<?php
$homepage_products = get_field('home_page_products');

// Query for featured products (or latest if no featured products field)
$args = array(
    'post_type' => 'product',
    'posts_per_page' => 8,
    'orderby' => 'date',
    'order' => 'DESC',
    'status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_featured',
            'value' => 'yes',
            'compare' => '='
        )
    )
);

$featured_query = new WP_Query($args);

// If no featured products found, get latest products instead
if (!$featured_query->have_posts()) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 8,
        'orderby' => 'date',
        'order' => 'DESC',
        'status' => 'publish'
    );
    $featured_query = new WP_Query($args);
}

if ($featured_query->have_posts()) : ?>
    <div class="w-full mt-[40px] lg:mt-[70px]">
        <div class="w-full mx-auto max-w-7xl px-4">
            <?php if (isset($homepage_products["title"]) && $homepage_products["title"]) : ?>
                <h2 class="text-3xl md:text-4xl mb-2"><?php echo esc_html($homepage_products["title"]); ?></h2>
            <?php endif; ?>
            <?php if (isset($homepage_products["sub_title"]) && $homepage_products["sub_title"]) : ?>
                <h3 class="text-xl md:text-2xl mb-8 text-enduro-red-100"><?php echo esc_html($homepage_products["sub_title"]); ?></h3>
            <?php endif; ?>
            
            <style>
            .carousel-slide { flex-shrink: 0; width: 100%; }
            @media (min-width: 640px)  { .carousel-slide { width: calc(50% - 8px); } }
            @media (min-width: 1024px) { .carousel-slide { width: calc(33.333% - 10.667px); } }
            @media (min-width: 1280px) { .carousel-slide { width: calc(25% - 12px); } }
            </style>

            <!-- Carousel Container -->
            <div class="relative">
                <div class="carousel-container overflow-hidden">
                    <div class="carousel-track flex gap-4 transition-transform duration-300 ease-in-out items-stretch" id="featured-carousel-track">
                        <?php while ($featured_query->have_posts()) : $featured_query->the_post();
                            global $product;
                            if (!$product) continue;
                        ?>
                            <div class="carousel-slide flex flex-col">
                                <div class="flex flex-col h-full border border-gray-200 rounded overflow-hidden hover:border-enduro-red-100 transition">
                                    <a href="<?php echo esc_url(get_permalink()); ?>" class="block">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="w-full h-[300px] bg-cover bg-center bg-no-repeat" style="background-image: url(<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>);"></div>
                                        <?php else : ?>
                                            <div class="w-full h-[300px] bg-enduro-grey-400 flex items-center justify-center">
                                                <span class="text-enduro-grey-600"><?php echo esc_html__('No image', 'silvester'); ?></span>
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
                                            <?php if ($product->is_purchasable() && $product->is_in_stock()) : ?>
                                                <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" class="inline-block bg-gradient-to-b from-enduro-red-100 to-enduro-red-200 hover:from-enduro-red-200 hover:to-enduro-red-100 text-white py-2 px-4 rounded text-sm font-medium transition">
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
                <button class="carousel-nav carousel-prev absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 lg:-translate-x-12 bg-enduro-red-100 hover:bg-enduro-red-200 text-white rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center transition z-10" onclick="scrollCarousel('prev')" aria-label="Previous">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button class="carousel-nav carousel-next absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 lg:translate-x-12 bg-enduro-red-100 hover:bg-enduro-red-200 text-white rounded-full w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center transition z-10" onclick="scrollCarousel('next')" aria-label="Next">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <script>
    let currentSlide = 0;
    const track = document.getElementById('featured-carousel-track');
    const slides = track ? track.querySelectorAll('.carousel-slide') : [];
    const slidesToShow = window.innerWidth >= 1280 ? 4 : window.innerWidth >= 1024 ? 3 : window.innerWidth >= 640 ? 2 : 1;
    const totalSlides = slides.length;
    
    function updateSlidesToShow() {
        if (window.innerWidth >= 1280) return 4;
        if (window.innerWidth >= 1024) return 3;
        if (window.innerWidth >= 640) return 2;
        return 1;
    }
    
    function scrollCarousel(direction) {
        const slidesToShow = updateSlidesToShow();
        const slideWidth = slides[0] ? slides[0].offsetWidth + 16 : 0; // 16px for gap
        
        if (direction === 'next') {
            currentSlide = Math.min(currentSlide + 1, totalSlides - slidesToShow);
        } else {
            currentSlide = Math.max(currentSlide - 1, 0);
        }
        
        if (track) {
            track.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
        }
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const slidesToShow = updateSlidesToShow();
        currentSlide = Math.min(currentSlide, Math.max(0, totalSlides - slidesToShow));
        const slideWidth = slides[0] ? slides[0].offsetWidth + 16 : 0;
        if (track) {
            track.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
        }
    });
    </script>
    
    <?php wp_reset_postdata(); ?>
<?php endif; ?>
