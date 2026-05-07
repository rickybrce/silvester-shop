<?php
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

// [old_title, new_title, sku_source_title]
$ops = [
    [ 'BETA RR RACE 2T 300',   'BETA RR 300 2T RACE MY2026',   'BETA RR 300 2T RACE MY2026' ],
    [ 'BETA XTRAINER 2T 300',  'BETA XTRAINER 300 2T MY2026',  'BETA XTRAINER 300 2T MY2026' ],
    [ 'BETA RR X-PRO 2T 300',  'BETA RR 300 2T X-PRO MY2026',  'BETA RR 300 2T X-PRO MY2026' ],
];

function find_by_title( $title ) {
    $q = new WP_Query( [ 'post_type'=>'product','title'=>$title,'posts_per_page'=>1,'no_found_rows'=>true,'fields'=>'ids' ] );
    return $q->have_posts() ? $q->posts[0] : 0;
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fix Bikes</title>';
echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}h2{color:#fff;}
.ok{color:#4caf50;}.fail{color:#f44336;}.info{color:#2196f3;}a{color:#2196f3;}</style></head><body><h2>Rename & Merge Bikes</h2>';

foreach ( $ops as [ $old_title, $new_title, $sku_source_title ] ) {
    echo '<hr style="border-color:#333;margin:12px 0;">';

    $old_pid = find_by_title( $old_title );
    $src_pid = find_by_title( $sku_source_title );

    if ( ! $old_pid ) { echo '<p class="fail">NOT FOUND: ' . esc_html( $old_title ) . '</p>'; continue; }
    if ( ! $src_pid ) { echo '<p class="fail">SKU SOURCE NOT FOUND: ' . esc_html( $sku_source_title ) . '</p>'; continue; }

    // Get SKU from source product
    $src_product = wc_get_product( $src_pid );
    $sku         = $src_product->get_sku();

    // Delete duplicate FIRST so its SKU is freed, then rename & assign
    $deleted = wp_delete_post( $src_pid, true );
    if ( $deleted ) {
        echo '<p class="info">🗑 [ID:' . $src_pid . '] Deleted duplicate "' . esc_html( $sku_source_title ) . '"</p>';
    } else {
        echo '<p class="fail">Could not delete [ID:' . $src_pid . '] "' . esc_html( $sku_source_title ) . '"</p>';
    }

    $old_product = wc_get_product( $old_pid );
    $old_product->set_name( $new_title );
    if ( $sku ) $old_product->set_sku( $sku );
    $old_product->save();
    echo '<p class="ok">✓ [ID:' . $old_pid . '] Renamed "' . esc_html( $old_title ) . '" → "' . esc_html( $new_title ) . '" | SKU: ' . esc_html( $sku ?: 'none' ) . '</p>';
}

echo '<p style="margin-top:16px"><a href="/wp-admin/edit.php?post_type=product">View products →</a></p>';
echo '</body></html>';
