<?php
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

$cart_id = wc_get_page_id( 'cart' );
$content = get_post_field( 'post_content', $cart_id );

$replacements = [
    'Your cart is currently empty!' => 'Vaša košarica je trenutno prazna!',
    'New in store'                  => 'Novo u trgovini',
];

$new_content = str_replace(
    array_keys( $replacements ),
    array_values( $replacements ),
    $content
);

if ( $new_content === $content ) {
    echo '<p style="font-family:monospace;padding:20px">Nothing changed — strings not found or already translated. Current content:<br><br><pre>' . esc_html( $content ) . '</pre></p>';
} else {
    wp_update_post( [ 'ID' => $cart_id, 'post_content' => $new_content ] );
    echo '<p style="font-family:monospace;color:green;padding:20px">✓ Cart page updated. <a href="/kosarica/">View cart →</a></p>';
}
