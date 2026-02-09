<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

// Card layout matching home page slider (featured products).
?>
<li <?php wc_product_class( 'w-full list-none', $product ); ?>>
	<div class="w-full border border-gray-200 rounded overflow-hidden hover:border-enduro-red-100 transition h-full flex flex-col">
		<a href="<?php echo esc_url( get_permalink() ); ?>" class="block">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="w-full h-[300px] bg-cover bg-center bg-no-repeat" style="background-image: url(<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'large' ) ); ?>);"></div>
			<?php else : ?>
				<div class="w-full h-[300px] bg-enduro-grey-400 flex items-center justify-center">
					<span class="text-enduro-grey-600"><?php echo esc_html__( 'No image', 'silvester' ); ?></span>
				</div>
			<?php endif; ?>
		</a>
		<div class="p-4 flex-grow flex flex-col">
			<a href="<?php echo esc_url( get_permalink() ); ?>">
				<h23 class="woocommerce-loop-product__title text-lg mb-2 text-enduro-grey-900 hover:text-enduro-red-100 transition"><?php echo esc_html( get_the_title() ); ?></h3>
			</a>
			<div class="text-enduro-red-100 text-lg font-medium mb-4">
				<?php echo $product->get_price_html(); ?>
			</div>
			<div class="flex flex-wrap gap-2 mt-auto">
				<a href="<?php echo esc_url( get_permalink() ); ?>" class="inline-block border border-enduro-red-100 text-enduro-red-100 hover:bg-enduro-red-100 hover:text-white py-2 px-4 rounded text-sm font-medium transition">
					<?php echo esc_html__( 'Pogledaj više', 'silvester' ); ?>
				</a>
				<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
					<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="inline-block bg-gradient-to-b from-enduro-red-100 to-enduro-red-200 hover:from-enduro-red-200 hover:to-enduro-red-100 text-white py-2 px-4 rounded text-sm font-medium transition">
						<?php echo esc_html__( 'Kupi odmah', 'silvester' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</li>
