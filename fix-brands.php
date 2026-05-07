<?php
defined( 'ABSPATH' ) || require_once __DIR__ . '/wp-load.php';

if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );

$dry_run = empty( $_GET['run'] );

// Find the term to remove — try common slugs
$remove_term = get_term_by( 'name', 'OEM Sherco', 'product_brand' )
            ?: get_term_by( 'name', 'Sherco OEM', 'product_brand' )
            ?: get_term_by( 'slug', 'oem-sherco', 'product_brand' )
            ?: get_term_by( 'slug', 'sherco-oem', 'product_brand' );

echo '<pre>';
if ( ! $remove_term ) {
    // List all brands so user can identify the right one
    $all = get_terms( [ 'taxonomy' => 'product_brand', 'hide_empty' => false ] );
    echo "Brand to remove not found. Available brands:\n";
    foreach ( $all as $t ) echo "  [{$t->term_id}] {$t->name} (slug: {$t->slug})\n";
    echo "\nAdd &brand_id=ID to the URL to specify which to remove.\n";

    if ( ! empty( $_GET['brand_id'] ) ) {
        $remove_term = get_term( (int) $_GET['brand_id'], 'product_brand' );
    }
}

if ( ! $remove_term || is_wp_error( $remove_term ) ) { echo '</pre>'; exit; }

echo "Removing brand: {$remove_term->name} (ID: {$remove_term->term_id})\n";
echo $dry_run ? "DRY RUN — add &run=1 to actually apply.\n\n" : "LIVE RUN\n\n";

// Find products that have this brand AND at least one other brand
$products = get_posts( [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'tax_query'      => [[
        'taxonomy' => 'product_brand',
        'field'    => 'term_id',
        'terms'    => $remove_term->term_id,
    ]],
] );

$fixed = 0;
foreach ( $products as $pid ) {
    $brands = wp_get_object_terms( $pid, 'product_brand', [ 'fields' => 'ids' ] );
    if ( count( $brands ) < 2 ) {
        echo "  SKIP [{$pid}] " . get_the_title( $pid ) . " — only brand, not removing\n";
        continue;
    }
    echo "  FIX  [{$pid}] " . get_the_title( $pid ) . "\n";
    if ( ! $dry_run ) {
        $new_brands = array_values( array_diff( $brands, [ $remove_term->term_id ] ) );
        wp_set_object_terms( $pid, $new_brands, 'product_brand' );
    }
    $fixed++;
}

echo "\nDone. " . ( $dry_run ? "Would fix" : "Fixed" ) . ": {$fixed} products.\n";
echo '</pre>';
