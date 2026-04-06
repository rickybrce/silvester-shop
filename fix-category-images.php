<?php
/**
 * Assigns a random product image to each category that has no thumbnail.
 * Access: http://silvester-shop.test/fix-category-images.php?pass=silvester2024
 * Delete this file when done!
 */

define( 'CAT_PASS', 'silvester2024' );
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== CAT_PASS ) {
    die( 'Access denied. Add ?pass=silvester2024 to the URL.' );
}

require_once __DIR__ . '/wp-load.php';

$index    = max( 0, (int) ( $_GET['index'] ?? 0 ) );
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?pass=' . CAT_PASS;

function cat_html( $content, $refresh_url = '', $delay = 1 ) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Category Images</title>';
    if ( $refresh_url ) echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . htmlspecialchars( $refresh_url ) . '">';
    echo '<style>
        body{font-family:monospace;background:#111;color:#ccc;padding:20px;}
        h2{color:#fff;} a{color:#2196f3;}
        .ok{color:#4caf50;} .skip{color:#888;} .fail{color:#f44336;}
        .bar-wrap{background:#333;border-radius:4px;height:18px;width:100%;max-width:500px;margin:10px 0;}
        .bar-fill{background:#4caf50;height:18px;border-radius:4px;}
    </style></head><body><h2>Category Images</h2>';
    echo $content;
    echo '</body></html>';
}

// Get all product categories
$categories = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
] );

if ( is_wp_error( $categories ) || empty( $categories ) ) {
    cat_html( '<p class="fail">No product categories found.</p>' );
    exit;
}

$total = count( $categories );

if ( $index >= $total ) {
    cat_html( '<p class="ok" style="font-size:18px">✓ All done!</p><p><a href="/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product">View categories in admin →</a></p>' );
    exit;
}

$cat      = $categories[ $index ];
$next_url = $base_url . '&index=' . ( $index + 1 );
$pct      = round( ( ( $index + 1 ) / $total ) * 100 );

$status = 'skip';
$msg    = '';

// Check if category already has a thumbnail
$existing_thumb = get_term_meta( $cat->term_id, 'thumbnail_id', true );
if ( $existing_thumb && get_post( $existing_thumb ) ) {
    $status = 'skip';
    $msg    = 'Already has image';
} else {
    // Find a product in this category that has a featured image
    $products = get_posts( [
        'post_type'      => 'product',
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'orderby'        => 'rand',
        'tax_query'      => [[
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $cat->term_id,
        ]],
    ] );

    $thumb_id = 0;
    foreach ( $products as $pid ) {
        $tid = get_post_thumbnail_id( $pid );
        if ( $tid && get_post( $tid ) ) {
            $thumb_id = $tid;
            break;
        }
    }

    if ( ! $thumb_id ) {
        $status = 'fail';
        $msg    = 'No product with image found in this category';
    } else {
        // Duplicate the attachment so it's independently associated with the category
        $src_post    = get_post( $thumb_id );
        $src_file    = get_attached_file( $thumb_id );
        $upload_dir  = wp_upload_dir();

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        if ( $src_file && file_exists( $src_file ) ) {
            // Copy file with new name
            $ext      = pathinfo( $src_file, PATHINFO_EXTENSION );
            $new_name = 'cat-' . $cat->term_id . '.' . $ext;
            $new_path = $upload_dir['path'] . '/' . $new_name;
            copy( $src_file, $new_path );

            $wp_filetype = wp_check_filetype( $new_name );
            $attach_id   = wp_insert_attachment( [
                'post_mime_type' => $wp_filetype['type'],
                'post_title'     => sanitize_file_name( $new_name ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ], $new_path );

            if ( ! is_wp_error( $attach_id ) ) {
                wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $new_path ) );
                update_term_meta( $cat->term_id, 'thumbnail_id', $attach_id );
                $status = 'ok';
                $msg    = 'Image set (attachment #' . $attach_id . ')';
            } else {
                $status = 'fail';
                $msg    = $attach_id->get_error_message();
            }
        } else {
            // Src file doesn't exist on disk — just reuse the attachment ID directly
            update_term_meta( $cat->term_id, 'thumbnail_id', $thumb_id );
            $status = 'ok';
            $msg    = 'Reused attachment #' . $thumb_id;
        }
    }
}

$bar  = '<div class="bar-wrap"><div class="bar-fill" style="width:' . $pct . '%"></div></div>';
$bar .= '<p>' . ( $index + 1 ) . ' / ' . $total . ' (' . $pct . '%)</p>';
$line = '<p class="' . $status . '">[' . strtoupper( $status ) . '] ' . esc_html( $cat->name ) . ' — ' . esc_html( $msg ) . '</p>';

cat_html( $bar . $line, $next_url );
