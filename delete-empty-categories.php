<?php
/**
 * Deletes all empty product categories.
 * Access: http://silvester-shop.test/delete-empty-categories.php?pass=silvester2024
 * Add &run=1 to apply. Delete this file when done!
 */
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

$dry_run = empty( $_GET['run'] );

$terms = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'fields'     => 'all',
] );

$empty = array_filter( $terms, fn( $t ) => $t->count === 0 && $t->name !== 'Uncategorized' );

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Delete Empty Categories</title>';
echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}h2{color:#fff;}
a{color:#2196f3;}.del{color:#f44336;}.dry{color:#f9a825;}
table{border-collapse:collapse;}td,th{padding:5px 12px;border:1px solid #333;}th{background:#222;}</style>';
echo '</head><body><h2>Empty Product Categories</h2>';

if ( empty( $empty ) ) {
    echo '<p style="color:#4caf50">No empty categories found.</p>';
    echo '</body></html>';
    exit;
}

if ( $dry_run ) {
    echo '<p class="dry">DRY RUN — <a href="?pass=silvester2024&run=1">Click here to delete</a></p>';
} else {
    echo '<p class="del">LIVE RUN — deleting...</p>';
}

echo '<table><tr><th>ID</th><th>Name</th><th>Slug</th><th>Parent</th><th>Action</th></tr>';

$deleted = 0;
foreach ( $empty as $term ) {
    $parent_name = $term->parent ? get_term( $term->parent, 'product_cat' )->name ?? '—' : '—';
    if ( $dry_run ) {
        $action = '<span class="dry">Would delete</span>';
    } else {
        $result = wp_delete_term( $term->term_id, 'product_cat' );
        $action = ( $result && ! is_wp_error( $result ) )
            ? '<span class="del">Deleted</span>'
            : '<span>Error: ' . ( is_wp_error( $result ) ? esc_html( $result->get_error_message() ) : 'false' ) . '</span>';
        $deleted++;
    }
    echo '<tr><td>' . $term->term_id . '</td><td>' . esc_html( $term->name ) . '</td><td>' . esc_html( $term->slug ) . '</td><td>' . esc_html( $parent_name ) . '</td><td>' . $action . '</td></tr>';
}

echo '</table>';
echo '<p>Total: ' . count( $empty ) . ( $dry_run ? ' would be deleted.' : ' deleted.' ) . '</p>';
echo '</body></html>';
