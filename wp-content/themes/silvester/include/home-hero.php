<?php
/**
 * Home Hero – full-width slider.
 * ACF group: home_page_hero
 * Repeater field: home_page_hero.items
 * Sub-fields per item: image (array), title (text), description (text), cta (link array)
 */
$hero  = get_field('home_page_hero');
$items = $hero['items'] ?? [];

if (empty($items)) return;
?>

<section class="home-hero relative w-full overflow-hidden bg-enduro-grey-900" id="home-hero-slider">

    <!-- Slides track -->
    <div class="home-hero__track flex transition-transform duration-500 ease-in-out" id="hero-track">
        <?php foreach ($items as $item) :
            $img = $item['image'] ?? null;
            $bg  = $img ? (is_array($img) ? ($img['url'] ?? '') : wp_get_attachment_image_url($img, 'full')) : '';
            $cta = $item['cta'] ?? null;
            $cta_url    = is_array($cta) ? ($cta['url']    ?? '') : '';
            $cta_title  = is_array($cta) ? ($cta['title']  ?? '') : '';
            $cta_target = is_array($cta) && !empty($cta['target']) ? $cta['target'] : '_self';
        ?>
            <div class="home-hero__slide relative w-full shrink-0 min-h-[50vh] lg:min-h-[60vh] flex items-center justify-center">
                <?php if ($bg) : ?>
                    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat z-0 pointer-events-none" style="background-image: url(<?php echo esc_url($bg); ?>);"></div>
                    <div class="absolute inset-0 bg-black/20 z-[1] pointer-events-none"></div>
                <?php endif; ?>

                <div class="relative z-10 w-full max-w-4xl mx-auto px-4 py-24 lg:py-32 text-center">
                    <?php if (!empty($item['title'])) : ?>
                        <h1 class="home-hero__title text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-medium text-white uppercase mb-4 lg:mb-6">
                            <?php echo esc_html($item['title']); ?>
                        </h1>
                    <?php endif; ?>

                    <?php if (!empty($item['description'])) : ?>
                        <div class="home-hero__description text-lg sm:text-xl lg:text-2xl text-white/90 max-w-2xl mx-auto mb-8 lg:mb-10">
                            <?php echo wp_kses_post(wpautop($item['description'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($cta_url && $cta_title) : ?>
                        <a href="<?php echo esc_url($cta_url); ?>" target="<?php echo esc_attr($cta_target); ?>" class="home-hero__cta inline-block bg-gradient-to-b from-enduro-red-100 to-enduro-red-200 hover:from-enduro-red-200 hover:to-enduro-red-100 text-white font-medium uppercase py-3 px-8 rounded text-base lg:text-lg transition ease-in-out">
                            <?php echo esc_html($cta_title); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($items) > 1) : ?>

        <!-- Prev / Next arrows -->
        <button class="hero-prev absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center bg-white/10 hover:bg-black/30 text-white rounded-full transition" aria-label="Previous slide">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button class="hero-next absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 lg:w-12 lg:h-12 flex items-center justify-center bg-white/10 hover:bg-black/30 text-white rounded-full transition" aria-label="Next slide">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>

        <!-- Dots -->
        <div class="hero-dots absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-2">
            <?php foreach ($items as $i => $_) : ?>
                <button class="hero-dot w-2.5 h-2.5 rounded-full bg-white/50 transition <?php echo $i === 0 ? 'bg-white' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="Slide <?php echo $i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</section>

<script>
(function() {
    var track  = document.getElementById('hero-track');
    if (!track) return;
    var slides = track.querySelectorAll('.home-hero__slide');
    var total  = slides.length;
    if (total < 2) return;

    var current = 0;
    var timer   = null;
    var section = document.getElementById('home-hero-slider');
    var dots    = section.querySelectorAll('.hero-dot');

    function goTo(idx) {
        current = (idx + total) % total;
        track.style.transform = 'translateX(-' + (current * slides[0].offsetWidth) + 'px)';
        dots.forEach(function(d, i) {
            d.style.backgroundColor = i === current ? '#fff' : 'rgba(255,255,255,0.5)';
        });
    }

    window.addEventListener('resize', function() { goTo(current); });

    function startAutoplay() {
        stopAutoplay();
        timer = setInterval(function() { goTo(current + 1); }, 5000);
    }

    function stopAutoplay() {
        if (timer) { clearInterval(timer); timer = null; }
    }

    section.querySelector('.hero-prev').addEventListener('click', function() {
        stopAutoplay(); goTo(current - 1); startAutoplay();
    });
    section.querySelector('.hero-next').addEventListener('click', function() {
        stopAutoplay(); goTo(current + 1); startAutoplay();
    });
    dots.forEach(function(d) {
        d.addEventListener('click', function() {
            stopAutoplay(); goTo(parseInt(d.dataset.index)); startAutoplay();
        });
    });

    section.addEventListener('mouseenter', stopAutoplay);
    section.addEventListener('mouseleave', startAutoplay);

    startAutoplay();
})();
</script>
