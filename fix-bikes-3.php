<?php
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

$products = [
    [ 'sku' => '040.87.606.00.01', 'price' => '10150.00', 'hr' => 'BETA RR 300 2T RACE MY2026',   'tokens' => ['RR','300','2T','RACE'] ],
    [ 'sku' => '040.87.601.00.01', 'price' => '9250.00',  'hr' => 'BETA RR 300 2T X-PRO MY2026',  'tokens' => ['RR','300','2T','X-PRO'] ],
    [ 'sku' => '054.87.101.00.01', 'price' => '7600.00',  'hr' => 'BETA XTRAINER 300 2T MY2026',  'tokens' => ['XTRAINER','300','2T'] ],
];

function fix3_beta_brand() {
    $t = get_term_by( 'name', 'Beta', 'product_brand' ) ?: get_term_by( 'slug', 'beta', 'product_brand' );
    if ( $t ) return $t->term_id;
    $r = wp_insert_term( 'Beta', 'product_brand' );
    return is_wp_error( $r ) ? 0 : $r['term_id'];
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fix 3 Bikes</title>';
echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}h2{color:#fff;}
.ok{color:#4caf50;}.fail{color:#f44336;}.skip{color:#888;}</style></head><body><h2>Fix 3 Bikes</h2>';

foreach ( $products as $d ) {
    // Find by SKU first
    $pid = wc_get_product_id_by_sku( $d['sku'] );

    // Fuzzy LIKE fallback
    if ( ! $pid ) {
        global $wpdb;
        $wheres = array_map(
            fn( $t ) => $wpdb->prepare( 'post_title LIKE %s', '%' . $wpdb->esc_like( $t ) . '%' ),
            $d['tokens']
        );
        $pid = (int) $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND "
            . implode( ' AND ', $wheres ) . " LIMIT 1"
        );
    }

    if ( ! $pid ) {
        echo '<p class="fail">NOT FOUND: ' . esc_html( $d['hr'] ) . '</p>';
        continue;
    }

    $product = wc_get_product( $pid );
    $product->set_name( $d['hr'] );
    $product->set_regular_price( $d['price'] );
    if ( ! $product->get_sku() ) $product->set_sku( $d['sku'] );
    $product->save();

    $brand_id = fix3_beta_brand();
    if ( $brand_id ) wp_set_object_terms( $pid, [ $brand_id ], 'product_brand' );

    echo '<p class="ok">✓ [ID:' . $pid . '] ' . esc_html( get_the_title( $pid ) ) . ' → renamed to "' . esc_html( $d['hr'] ) . '", SKU: ' . esc_html( $d['sku'] ) . ', Beta brand set</p>';
}

echo '<p><a href="/wp-admin/edit.php?post_type=product" style="color:#2196f3">View products →</a></p>';
echo '</body></html>';
