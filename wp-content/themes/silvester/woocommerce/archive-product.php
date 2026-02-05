<?php
/**
 * The Template for displaying product archives, including the main shop page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 */
do_action( 'woocommerce_before_main_content' );

?>
<header class="woocommerce-products-header mt-[120px] w-full mb-8">
	<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
		<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
	<?php endif; ?>

	<?php
	/**
	 * Hook: woocommerce_archive_description. 
	 *
	 * @hooked woocommerce_taxonomy_archive_description - 10
	 * @hooked woocommerce_product_archive_description - 10
	 */
	do_action( 'woocommerce_archive_description' );
	?>
</header>

<div class="flex flex-col lg:flex-row gap-8 w-full">
	<!-- Categories Sidebar -->
	<aside class="w-full lg:w-1/4 flex-shrink-0">
		<div class="sticky top-[120px]">
			<h2 class="text-2xl mb-4 text-enduro-grey-900 border-b border-gray-200 pb-2"><?php echo esc_html__('Categories', 'silvester'); ?></h2>
			<?php
			$product_categories = get_terms(array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => 0,
				'orderby'    => 'menu_order',
				'order'      => 'ASC'
			));

			if (!empty($product_categories) && !is_wp_error($product_categories)) : ?>
				<ul class="space-y-2">
					<li>
						<a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="block py-2 px-3 text-enduro-grey-800 hover:text-enduro-red-100 hover:bg-gray-100 rounded transition <?php echo is_shop() ? 'text-enduro-red-100 bg-gray-100' : ''; ?>">
							<?php echo esc_html__('All Products', 'silvester'); ?>
						</a>
					</li>
					<?php foreach ($product_categories as $category) : 
						$category_link = get_term_link($category);
						$is_current = is_product_category($category->slug);
					?>
						<li>
							<a href="<?php echo esc_url($category_link); ?>" class="block py-2 px-3 text-enduro-grey-800 hover:text-enduro-red-100 hover:bg-gray-100 rounded transition <?php echo $is_current ? 'text-enduro-red-100 bg-gray-100' : ''; ?>">
								<?php echo esc_html($category->name); ?>
								<?php if ($category->count > 0) : ?>
									<span class="text-enduro-grey-500 text-sm ml-2">(<?php echo esc_html($category->count); ?>)</span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</aside>
	<!-- Categories Sidebar -->

	<!-- Main Content -->
	<div class="w-full lg:w-3/4 flex-grow">
<?php
if ( woocommerce_product_loop() ) {

	/**
	 * Hook: woocommerce_before_shop_loop.
	 *
	 * @hooked woocommerce_output_all_notices - 10
	 * @hooked woocommerce_result_count - 20
	 * @hooked woocommerce_catalog_ordering - 30
	 */
	do_action( 'woocommerce_before_shop_loop' );

	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'is_shortcode' ) ) {
		$columns = absint( wc_get_loop_prop( 'columns' ) );
		$args['columns'] = $columns;
		$products = wc_get_products( $args );

		foreach ( $products as $product ) {
			$post_object = get_post( $product->get_id() );
			setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

			wc_get_template_part( 'content', 'product' );
		}
		wp_reset_postdata();
	} else {
		while ( have_posts() ) {
			the_post();

			/**
			 * Hook: woocommerce_shop_loop.
			 */
			do_action( 'woocommerce_shop_loop' );

			wc_get_template_part( 'content', 'product' );
		}
	}

	woocommerce_product_loop_end();

	/**
	 * Hook: woocommerce_after_shop_loop.
	 *
	 * @hooked woocommerce_pagination - 10
	 */
	do_action( 'woocommerce_after_shop_loop' );
} else {
	/**
	 * Hook: woocommerce_no_products_found.
	 *
	 * @hooked wc_no_products_found - 10
	 */
	do_action( 'woocommerce_no_products_found' );
}
?>
	</div>
	<!-- Main Content End -->
</div>

<?php
/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
//do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );
