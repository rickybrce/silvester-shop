<?php
/**
 * Single Product Image – theme override with slider lightbox
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.5.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$main_image_id  = $product->get_image_id();
$gallery_ids    = $product->get_gallery_image_ids();

// Combine main image + gallery into one list
$all_image_ids = $main_image_id ? [ $main_image_id, ...$gallery_ids ] : $gallery_ids;

if ( empty( $all_image_ids ) ) {
    echo '<div class="woocommerce-product-gallery--placeholder"><img src="' . esc_url( wc_placeholder_img_src() ) . '" alt="' . esc_attr__( 'Awaiting product image', 'woocommerce' ) . '" /></div>';
    return;
}

// Get full-size and thumbnail URLs for each image
$images = [];
foreach ( $all_image_ids as $id ) {
    $full  = wp_get_attachment_image_src( $id, 'full' );
    $thumb = wp_get_attachment_image_src( $id, 'woocommerce_gallery_thumbnail' );
    $alt   = get_post_meta( $id, '_wp_attachment_image_alt', true );
    if ( $full ) {
        $images[] = [
            'full'      => $full[0],
            'medium'    => $full[0],
            'thumb'     => $thumb ? $thumb[0] : $full[0],
            'alt'       => $alt ?: get_the_title( $id ),
            'nat_width' => (int) $full[1],
        ];
    }
}

if ( empty( $images ) ) return;
?>

<h1 class="product_title entry-title text-2xl lg:text-3xl font-bold text-enduro-grey-900 mt-3 mb-4"><?php the_title(); ?></h1>

<div class="silvester-product-gallery" id="silvester-product-gallery">

    <!-- Main image -->
    <div class="silvester-product-gallery__main mb-3 relative rounded-md bg-white border border-gray-200 cursor-zoom-in flex items-center justify-center overflow-hidden" style="height:500px;">
        <img id="silvester-main-img"
             src="<?php echo esc_url( $images[0]['medium'] ); ?>"
             alt="<?php echo esc_attr( $images[0]['alt'] ); ?>"
             class="object-contain block transition-opacity duration-200"
             style="max-width:100%; max-height:100%; width:auto; height:100%;" />
        <span class="absolute bottom-3 right-3 bg-black/50 text-white text-xs px-2 py-1 rounded flex items-center gap-1 pointer-events-none">
            <i class="fa-solid fa-magnifying-glass-plus"></i>
        </span>
        <button type="button" id="silvester-open-lightbox" class="absolute inset-0 w-full h-full opacity-0 cursor-zoom-in" aria-label="Open lightbox"></button>
    </div>

    <?php if ( count( $images ) > 1 ) : ?>
    <!-- Thumbnail strip -->
    <div class="silvester-product-gallery__thumbs flex flex-wrap gap-2">
        <?php foreach ( $images as $i => $img ) : ?>
            <button type="button"
                    class="silvester-gallery-thumb rounded border-2 overflow-hidden transition focus:outline-none <?php echo $i === 0 ? 'border-enduro-red-100' : 'border-gray-200 hover:border-enduro-red-100'; ?>"
                    data-index="<?php echo esc_attr( $i ); ?>">
                <img src="<?php echo esc_url( $img['thumb'] ); ?>"
                     alt="<?php echo esc_attr( $img['alt'] ); ?>"
                     class="w-16 h-16 object-cover block" />
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Slider Lightbox -->
<div id="slvstr-lightbox" style="display:none" role="dialog" aria-modal="true" aria-label="Image gallery">
    <div id="slvstr-overlay"></div>
    <div id="slvstr-wrap">
        <button id="slvstr-close" aria-label="Close">&times;</button>
        <div id="slvstr-track-wrap">
            <div id="slvstr-track">
                <?php foreach ( $images as $i => $img ) : ?>
                    <div class="slvstr-slide">
                        <img src="<?php echo esc_url( $img['full'] ); ?>"
                             alt="<?php echo esc_attr( $img['alt'] ); ?>" />
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if ( count( $images ) > 1 ) : ?>
        <button id="slvstr-prev" aria-label="Previous">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button id="slvstr-next" aria-label="Next">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div id="slvstr-dots">
            <?php foreach ( $images as $i => $img ) : ?>
                <button class="slvstr-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo esc_attr( $i ); ?>" aria-label="Image <?php echo $i + 1; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Lightbox overlay */
#slvstr-lightbox {
    position: fixed;
    inset: 0;
    z-index: 99999;
}
#slvstr-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.92);
}
#slvstr-wrap {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
/* Track */
#slvstr-track-wrap {
    position: relative;
    width: calc(100% - 120px);
    max-width: 1200px;
    overflow: hidden;
}
#slvstr-track {
    display: flex;
    transition: transform .35s cubic-bezier(.4,0,.2,1);
    will-change: transform;
}
.slvstr-slide {
    min-width: 100%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    max-height: calc(100vh - 120px);
}
.slvstr-slide img {
    max-width: 100%;
    max-height: calc(100vh - 120px);
    object-fit: contain;
    display: block;
    user-select: none;
    -webkit-user-drag: none;
}
/* Close */
#slvstr-close {
    position: absolute;
    top: 16px;
    right: 20px;
    background: none;
    border: none;
    color: #fff;
    font-size: 36px;
    line-height: 1;
    cursor: pointer;
    z-index: 2;
    opacity: .8;
    transition: opacity .2s;
}
#slvstr-close:hover { opacity: 1; }
/* Arrows */
#slvstr-prev, #slvstr-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,.15);
    border: none;
    color: #fff;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 2;
    transition: background .2s;
}
#slvstr-prev:hover, #slvstr-next:hover { background: rgba(255,255,255,.3); }
#slvstr-prev { left: 12px; }
#slvstr-next { right: 12px; }
#slvstr-prev svg, #slvstr-next svg { width: 22px; height: 22px; }
/* Dots */
#slvstr-dots {
    position: absolute;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 2;
}
.slvstr-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,.4);
    cursor: pointer;
    padding: 0;
    transition: background .2s, transform .2s;
}
.slvstr-dot.active {
    background: #fff;
    transform: scale(1.3);
}
@media (max-width: 640px) {
    #slvstr-track-wrap { width: calc(100% - 88px); }
    #slvstr-prev { left: 4px; }
    #slvstr-next { right: 4px; }
}
</style>

<script>
(function() {
    var images   = <?php echo wp_json_encode( array_values( $images ) ); ?>;
    var total    = images.length;
    var active   = 0;
    var lbOpen   = false;
    var lbIndex  = 0;

    var mainImg   = document.getElementById('silvester-main-img');
    var openBtn   = document.getElementById('silvester-open-lightbox');
    var thumbs    = document.querySelectorAll('.silvester-gallery-thumb');
    var lightbox  = document.getElementById('slvstr-lightbox');
    var overlay   = document.getElementById('slvstr-overlay');
    var track     = document.getElementById('slvstr-track');
    var prevBtn   = document.getElementById('slvstr-prev');
    var nextBtn   = document.getElementById('slvstr-next');
    var closeBtn  = document.getElementById('slvstr-close');
    var dots      = document.querySelectorAll('.slvstr-dot');

    // ── Thumbnail swap ────────────────────────────────────────────
    thumbs.forEach(function(btn) {
        btn.addEventListener('click', function() {
            active = parseInt(this.getAttribute('data-index'));
            mainImg.src = images[active].medium;
            thumbs.forEach(function(b) {
                b.classList.remove('border-enduro-red-100');
                b.classList.add('border-gray-200');
            });
            this.classList.remove('border-gray-200');
            this.classList.add('border-enduro-red-100');
        });
    });

    // ── Lightbox open/close ───────────────────────────────────────
    function setSlideSizes() {
        var w = track.parentElement.offsetWidth;
        var slides = track.querySelectorAll('.slvstr-slide');
        slides.forEach(function(s) { s.style.width = w + 'px'; s.style.minWidth = w + 'px'; });
        return w;
    }

    function openLightbox(index) {
        lightbox.style.display = 'block';
        document.body.style.overflow = 'hidden';
        lbOpen = true;
        lbIndex = index;
        setSlideSizes();
        goTo(lbIndex, false);
    }

    function closeLightbox() {
        lightbox.style.display = 'none';
        document.body.style.overflow = '';
        lbOpen = false;
    }

    function goTo(index, animate) {
        if (animate === undefined) animate = true;
        var slideWidth = track.parentElement.offsetWidth;
        if (!animate) {
            track.style.transition = 'none';
            track.offsetHeight; // force reflow
        } else {
            track.style.transition = 'transform .35s cubic-bezier(.4,0,.2,1)';
        }
        track.style.transform = 'translateX(-' + (index * slideWidth) + 'px)';
        lbIndex = index;
        dots.forEach(function(d, i) {
            d.classList.toggle('active', i === index);
        });
    }

    function prev() { goTo(lbIndex > 0 ? lbIndex - 1 : total - 1); }
    function next() { goTo(lbIndex < total - 1 ? lbIndex + 1 : 0); }

    if (openBtn) openBtn.addEventListener('click', function() { openLightbox(active); });
    if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
    if (overlay)  overlay.addEventListener('click', closeLightbox);
    if (prevBtn)  prevBtn.addEventListener('click', function(e) { e.stopPropagation(); prev(); });
    if (nextBtn)  nextBtn.addEventListener('click', function(e) { e.stopPropagation(); next(); });

    dots.forEach(function(d) {
        d.addEventListener('click', function(e) {
            e.stopPropagation();
            goTo(parseInt(this.getAttribute('data-index')));
        });
    });

    // Keyboard
    document.addEventListener('keydown', function(e) {
        if (!lbOpen) return;
        if (e.key === 'Escape')     closeLightbox();
        if (e.key === 'ArrowLeft')  prev();
        if (e.key === 'ArrowRight') next();
    });

    // Recalculate position on resize
    window.addEventListener('resize', function() {
        if (lbOpen) goTo(lbIndex, false);
    });

    // Touch swipe
    var touchStartX = 0;
    if (lightbox) {
        lightbox.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].clientX;
        }, { passive: true });
        lightbox.addEventListener('touchend', function(e) {
            var dx = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(dx) > 50) { dx < 0 ? next() : prev(); }
        }, { passive: true });
    }
})();
</script>
