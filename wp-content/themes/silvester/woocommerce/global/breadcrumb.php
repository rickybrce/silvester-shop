<?php
/**
 * Shop breadcrumb – theme override
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $breadcrumb ) ) return;

$is_single_product = is_singular( 'product' );

$category_url = '';
if ( $is_single_product ) {
    $terms = get_the_terms( get_the_ID(), 'product_cat' );
    if ( $terms && ! is_wp_error( $terms ) ) {
        // Prefer the deepest (most specific) category
        usort( $terms, fn( $a, $b ) => $b->parent - $a->parent );
        $category_url = get_term_link( $terms[0] );
    }
    if ( ! $category_url || is_wp_error( $category_url ) ) {
        $category_url = get_permalink( wc_get_page_id( 'shop' ) );
    }
}
?>

<?php if ( $is_single_product ) : ?>
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between pt-4 lg:pt-0 mb-4">
<?php endif; ?>

<?php
echo $wrap_before;
foreach ( $breadcrumb as $key => $crumb ) {
    echo $before;
    if ( ! empty( $crumb[1] ) && sizeof( $breadcrumb ) !== $key + 1 ) {
        echo '<a href="' . esc_url( $crumb[1] ) . '">' . esc_html( $crumb[0] ) . '</a>';
    } else {
        echo esc_html( $crumb[0] );
    }
    echo $after;
    if ( sizeof( $breadcrumb ) !== $key + 1 ) {
        echo $delimiter;
    }
}
echo $wrap_after;
?>

<?php if ( $is_single_product ) : ?>
    <a href="<?php echo esc_url( $category_url ); ?>" class="inline-flex items-center gap-2 text-sm text-enduro-grey-600 hover:text-enduro-red-100 transition group shrink-0 mt-2 lg:mt-0">
        <svg class="w-4 h-4 transition-transform duration-200 group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        <?php echo esc_html__( 'Natrag', 'silvester' ); ?>
    </a>
</div>
<?php endif; ?>
