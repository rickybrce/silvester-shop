<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

/**
 * Hook: woocommerce_shop_loop_header.
 *
 * @since 8.6.0
 *
 * @hooked woocommerce_product_taxonomy_archive_header - 10
 */
do_action( 'woocommerce_shop_loop_header' );
?>

<header class="woocommerce-products-header mt-0 w-full mb-8">
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

<?php if ( woocommerce_product_loop() ) : ?>
	<div class="w-full mb-4 lg:mb-8 text-left">
		<?php woocommerce_result_count(); ?>
	</div>
<?php endif; ?>

<div class="flex flex-col lg:flex-row gap-4 w-full">
	<!-- Categories Sidebar -->
	<aside class="w-full lg:w-1/4 flex-shrink-0">
		<div class="lg:sticky lg:top-30">

			<!-- Product search -->
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="mb-5">
				<div class="relative">
					<input type="search" name="s"
						value="<?php echo esc_attr( get_search_query() ); ?>"
						placeholder="<?php esc_attr_e( 'Pretraži proizvode...', 'silvester' ); ?>"
						class="w-full border border-gray-200 rounded py-2 pl-3 pr-10 text-sm text-enduro-grey-900 focus:outline-none focus:border-enduro-red-100 transition"
					>
					<input type="hidden" name="post_type" value="product">
					<?php
					// Preserve active category filter
					if ( is_product_category() ) {
						$cat = get_queried_object();
						echo '<input type="hidden" name="product_cat" value="' . esc_attr( $cat->slug ) . '">';
					}
					?>
					<button type="submit" class="absolute right-0 top-0 h-full px-3 text-enduro-grey-500 hover:text-enduro-red-100 transition" aria-label="Pretraži">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
						</svg>
					</button>
				</div>
			</form>

			<?php
			$product_categories = get_terms( [
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'parent'     => 0,
				'orderby'    => 'menu_order',
				'order'      => 'ASC',
			] );
			// Pin "motocikli" first
			if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) {
				usort( $product_categories, fn( $a, $b ) => $a->slug === 'motocikli' ? -1 : ( $b->slug === 'motocikli' ? 1 : 0 ) );
			}
			if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) :
			?>

			<!-- Mobile accordion toggle -->
			<button type="button" id="categories-accordion-toggle"
				class="lg:hidden w-full flex items-center justify-between py-2 px-4 bg-gray-100 rounded text-enduro-grey-900 font-semibold text-lg border border-gray-200"
				aria-expanded="false" aria-controls="categories-list">
				<?php echo esc_html__( 'Kategorije', 'silvester' ); ?>
				<svg id="categories-accordion-icon" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
				</svg>
			</button>

			<!-- Desktop heading -->
			<h3 class="hidden lg:block text-2xl !mb-2 text-enduro-grey-900 border-b border-gray-200 pb-2 mt-0!"><?php echo esc_html__( 'Kategorije', 'silvester' ); ?></h3>

			<!-- List: hidden on mobile until toggled, always visible on desktop -->
			<ul id="categories-list" class="categories-nav hidden lg:block mt-2 lg:mt-0">
				<li class="border-b border-gray-200">
					<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="block py-2 px-3 text-enduro-grey-800 hover:text-enduro-red-100 hover:bg-gray-100 rounded transition <?php echo is_shop() ? 'text-enduro-red-100 bg-gray-100' : ''; ?>">
						<?php echo esc_html__( 'Svi proizvodi', 'silvester' ); ?>
					</a>
				</li>
				<?php foreach ( $product_categories as $category ) :
					$category_link = get_term_link( $category );
					$is_current    = is_product_category( $category->slug );
					$subcategories = get_terms( [
						'taxonomy'   => 'product_cat',
						'hide_empty' => true,
						'parent'     => $category->term_id,
						'orderby'    => 'name',
						'order'      => 'ASC',
					] );
					$has_subs = ! empty( $subcategories ) && ! is_wp_error( $subcategories );
					// Is any subcategory currently active?
					$sub_active = false;
					if ( $has_subs ) {
						foreach ( $subcategories as $sub ) {
							if ( is_product_category( $sub->slug ) ) { $sub_active = true; break; }
						}
					}
				?>
					<li class="border-b border-gray-200">
						<a href="<?php echo esc_url( $category_link ); ?>" class="block py-2 px-3 text-enduro-grey-800 hover:text-enduro-red-100 hover:bg-gray-100 rounded transition <?php echo ( $is_current || $sub_active ) ? 'text-enduro-red-100 bg-gray-100' : ''; ?>">
							<?php echo esc_html( $category->name ); ?>
							<?php if ( $category->count > 0 ) : ?>
								<span class="text-enduro-grey-500 text-sm ml-2">(<?php echo esc_html( $category->count ); ?>)</span>
							<?php endif; ?>
						</a>
						<?php if ( $has_subs && ( $is_current || $sub_active ) ) : ?>
						<ul class="ml-3 mb-1">
							<?php foreach ( $subcategories as $sub ) :
								$sub_current = is_product_category( $sub->slug );
							?>
							<li class="border-t border-gray-100">
								<a href="<?php echo esc_url( get_term_link( $sub ) ); ?>" class="block py-1.5 px-3 text-sm text-enduro-grey-700 hover:text-enduro-red-100 hover:bg-gray-100 rounded transition <?php echo $sub_current ? 'text-enduro-red-100' : ''; ?>">
									<?php echo esc_html( $sub->name ); ?>
									<span class="text-enduro-grey-500 text-xs ml-1">(<?php echo esc_html( $sub->count ); ?>)</span>
								</a>
							</li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>

			<script>
			(function() {
				var btn  = document.getElementById('categories-accordion-toggle');
				var list = document.getElementById('categories-list');
				var icon = document.getElementById('categories-accordion-icon');
				if (!btn || !list) return;
				btn.addEventListener('click', function() {
					var open = list.classList.toggle('hidden');
					btn.setAttribute('aria-expanded', open ? 'false' : 'true');
					icon.style.transform = open ? '' : 'rotate(180deg)';
				});
			})();
			</script>

			<?php endif; ?>

			<?php
			$brands = get_terms( [
				'taxonomy'   => 'product_brand',
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			] );
			if ( ! empty( $brands ) && ! is_wp_error( $brands ) ) :
				$current_brand = get_queried_object();
				$is_brand_page = is_tax( 'product_brand' );
			?>
			<div class="mt-6">
				<!-- Mobile accordion -->
				<button type="button" id="brands-accordion-toggle"
					class="lg:hidden w-full flex items-center justify-between py-2 px-4 bg-gray-100 rounded text-enduro-grey-900 font-semibold text-lg border border-gray-200"
					aria-expanded="false" aria-controls="brands-list">
					<?php esc_html_e( 'Brendovi', 'silvester' ); ?>
					<svg id="brands-accordion-icon" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
					</svg>
				</button>

				<h3 class="hidden lg:block text-2xl mb-2! text-enduro-grey-900 border-b border-gray-200 pb-2 mt-0!"><?php esc_html_e( 'Brendovi', 'silvester' ); ?></h3>

				<ul id="brands-list" class="categories-nav hidden lg:block mt-2 lg:mt-0">
					<?php foreach ( $brands as $brand ) :
						$brand_link    = get_term_link( $brand );
						$brand_active  = $is_brand_page && $current_brand && $current_brand->term_id === $brand->term_id;
					?>
					<li class="border-b border-gray-200">
						<a href="<?php echo esc_url( $brand_link ); ?>" class="block py-2 px-3 text-enduro-grey-800 hover:text-enduro-red-100 hover:bg-gray-100 rounded transition <?php echo $brand_active ? 'text-enduro-red-100 bg-gray-100' : ''; ?>">
							<?php echo esc_html( $brand->name ); ?>
							<span class="text-enduro-grey-500 text-sm ml-2">(<?php echo esc_html( $brand->count ); ?>)</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<script>
			(function() {
				var btn  = document.getElementById('brands-accordion-toggle');
				var list = document.getElementById('brands-list');
				var icon = document.getElementById('brands-accordion-icon');
				if (!btn || !list) return;
				btn.addEventListener('click', function() {
					var open = list.classList.toggle('hidden');
					btn.setAttribute('aria-expanded', open ? 'false' : 'true');
					icon.style.transform = open ? '' : 'rotate(180deg)';
				});
			})();
			</script>

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

	if ( wc_get_loop_prop( 'total' ) ) {
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
// do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );
