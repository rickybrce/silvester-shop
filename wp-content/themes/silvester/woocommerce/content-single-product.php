<?php
defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'single-product-wrap', $product ); ?>>

	<!-- Image + Summary side by side -->
	<div class="flex flex-col lg:flex-row gap-8 lg:gap-12">

		<!-- Left: product images -->
		<div class="w-full lg:w-1/2 flex-shrink-0">
			<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
		</div>

		<!-- Right: summary -->
		<div class="w-full lg:w-1/2">
			<div class="summary entry-summary">

				<?php do_action( 'woocommerce_single_product_summary' ); ?>

			</div>
		</div>

	</div>

	<?php do_action( 'woocommerce_after_single_product_summary' ); ?>

</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
