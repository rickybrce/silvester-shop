<?php
/**
 * Single Product Image – theme override with lightbox gallery
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
$all_image_ids = $main_image_id ? array_merge( [ $main_image_id ], $gallery_ids ) : $gallery_ids;

if ( empty( $all_image_ids ) ) {
    echo '<div class="woocommerce-product-gallery--placeholder"><img src="' . esc_url( wc_placeholder_img_src() ) . '" alt="' . esc_attr__( 'Awaiting product image', 'woocommerce' ) . '" /></div>';
    return;
}

// Get full-size and medium URLs for each image
$images = [];
foreach ( $all_image_ids as $id ) {
    $full   = wp_get_attachment_image_src( $id, 'full' );
    $medium = wp_get_attachment_image_src( $id, 'woocommerce_single' );
    $thumb  = wp_get_attachment_image_src( $id, 'woocommerce_gallery_thumbnail' );
    $alt    = get_post_meta( $id, '_wp_attachment_image_alt', true );
    if ( $full ) {
        $images[] = [
            'full'      => $full[0],
            'medium'    => $full[0],        // use full size to avoid blurry upscaling
            'thumb'     => $thumb  ? $thumb[0]  : $full[0],
            'alt'       => $alt ?: get_the_title( $id ),
            'nat_width' => (int) $full[1],  // natural image width
        ];
    }
}

if ( empty( $images ) ) return;
?>

<h1 class="product_title entry-title text-2xl lg:text-3xl font-bold text-enduro-grey-900 mt-3 mb-4"><?php the_title(); ?></h1>

<div class="silvester-product-gallery" id="silvester-product-gallery">

    <?php // Main image — clicking opens lightbox at index 0 ?>
    <div class="silvester-product-gallery__main mb-3 relative rounded-md bg-white border border-gray-200 cursor-zoom-in flex items-center justify-center">
        <a href="<?php echo esc_url( $images[0]['full'] ); ?>"
           data-imagelightbox="product"
           data-ilb2-caption="<?php echo esc_attr( $images[0]['alt'] ); ?>"
           class="silvester-gallery-trigger block relative">
            <img src="<?php echo esc_url( $images[0]['medium'] ); ?>"
                 alt="<?php echo esc_attr( $images[0]['alt'] ); ?>"
                 class="h-auto object-contain block"
                 style="max-width:100%; width:<?php echo esc_attr( $images[0]['nat_width'] ); ?>px;" />
            <span class="absolute bottom-3 right-3 bg-black/50 text-white text-xs px-2 py-1 rounded flex items-center gap-1 pointer-events-none">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
            </span>
        </a>
    </div>

    <?php if ( count( $images ) > 1 ) : ?>
    <?php // Hidden anchors for remaining gallery images (lightbox group) ?>
    <div class="silvester-gallery-hidden" style="display:none">
        <?php for ( $i = 1; $i < count( $images ); $i++ ) : ?>
            <a href="<?php echo esc_url( $images[ $i ]['full'] ); ?>"
               data-imagelightbox="product"
               data-ilb2-caption="<?php echo esc_attr( $images[ $i ]['alt'] ); ?>"></a>
        <?php endfor; ?>
    </div>

    <?php // Thumbnail strip ?>
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

<script>
jQuery(function($) {
    var gallery = $('[data-imagelightbox="product"]');

    var lb = gallery.imageLightbox({
        arrows:          true,
        overlay:         true,
        button:          true,
        caption:         false,
        navigation:      false,
        quitOnEnd:       false,
        quitOnDocClick:  false,
        animationSpeed:  300
    });

    var images = <?php echo wp_json_encode( array_values( $images ) ); ?>;

    // Thumbnail click — swap main image only, no lightbox
    $('.silvester-gallery-thumb').on('click', function() {
        var idx = parseInt($(this).data('index'));
        $('.silvester-product-gallery__main img').attr('src', images[idx].medium).css('width', images[idx].nat_width + 'px');
        $('.silvester-product-gallery__main a').attr('href', images[idx].full);
        $('.silvester-gallery-thumb').removeClass('border-enduro-red-100').addClass('border-gray-200');
        $(this).removeClass('border-gray-200').addClass('border-enduro-red-100');
    });

    // Main image click opens lightbox
    $('.silvester-product-gallery__main a').on('click', function(e) {
        e.preventDefault();
        gallery.eq(0).trigger('click.ilb7');
    });

    // Sync thumbnail active border when navigating in lightbox
    $(document).on('loaded.ilb2', function() {
        var src = $('#imagelightbox').attr('src');
        for (var i = 0; i < images.length; i++) {
            if (images[i].full === src) {
                $('.silvester-gallery-thumb').removeClass('border-enduro-red-100').addClass('border-gray-200');
                $('.silvester-gallery-thumb[data-index="' + i + '"]').removeClass('border-gray-200').addClass('border-enduro-red-100');
                break;
            }
        }
    });

    // Close on overlay click
    $(document).on('click touchend', '.imagelightbox-overlay', function() {
        lb.quitImageLightbox();
    });
});
</script>
