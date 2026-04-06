<?php
/**
 * Browser-based image fixer — uses Shopify collection position-based matching.
 * Access: http://silvester-shop.test/fix-images-browser.php?pass=silvester2024
 * Delete this file when done!
 */

define( 'FIX_PASS', 'silvester2024' );
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== FIX_PASS ) {
    die( 'Access denied. Add ?pass=silvester2024 to the URL.' );
}

define( 'SILVESTER_DATA_ONLY', true );
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/import-products.php'; // populates $products

$mode       = $_GET['mode'] ?? 'build';
$index      = max( 0, (int) ( $_GET['index'] ?? 0 ) );
$base_url   = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?pass=' . FIX_PASS;
$map_file   = __DIR__ . '/fix-shopify-map.json';

// ── HTML helper ────────────────────────────────────────────────────────────────
function html_out( $content, $refresh_url = '', $delay = 2 ) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fix Images</title>';
    if ( $refresh_url ) echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . htmlspecialchars( $refresh_url ) . '">';
    echo '<style>
        body{font-family:monospace;background:#111;color:#ccc;padding:20px;}
        h2{color:#fff;} a{color:#2196f3;}
        .ok{color:#4caf50;} .skip{color:#888;} .fail{color:#f44336;} .info{color:#2196f3;}
        .bar-wrap{background:#333;border-radius:4px;height:18px;width:100%;max-width:500px;margin:10px 0;}
        .bar-fill{background:#4caf50;height:18px;border-radius:4px;}
        ul.debug{font-size:11px;color:#555;margin:4px 0 0 0;padding-left:16px;}
    </style></head><body><h2>Fix Missing Images</h2>';
    echo $content;
    echo '</body></html>';
}

// ── Attach image from URL ──────────────────────────────────────────────────────
function attach_image_from_url( $image_url, $post_id ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $resp    = wp_remote_get( $image_url, [ 'timeout' => 15, 'user-agent' => 'Mozilla/5.0' ] );
    $code    = is_wp_error( $resp ) ? 0 : wp_remote_retrieve_response_code( $resp );
    $body    = is_wp_error( $resp ) ? '' : wp_remote_retrieve_body( $resp );

    if ( $code !== 200 || strlen( $body ) < 100 ) {
        return [ false, 'Download failed HTTP ' . $code . ': ' . $image_url ];
    }

    // Determine extension from URL or Content-Type
    $ext = strtolower( pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
    if ( ! in_array( $ext, [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ] ) ) {
        $ct      = strtok( wp_remote_retrieve_header( $resp, 'content-type' ), ';' );
        $ext_map = [ 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp' ];
        $ext     = $ext_map[ trim( $ct ) ] ?? 'jpg';
    }

    $upload_dir = wp_upload_dir();
    $filename   = $post_id . '_fix.' . $ext;
    $file_path  = $upload_dir['path'] . '/' . $filename;
    file_put_contents( $file_path, $body );

    $wp_filetype = wp_check_filetype( $filename );
    if ( ! $wp_filetype['type'] ) {
        return [ false, 'Unknown filetype for ext "' . $ext . '"' ];
    }

    $attach_id = wp_insert_attachment( [
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $file_path, $post_id );

    if ( is_wp_error( $attach_id ) ) {
        return [ false, $attach_id->get_error_message() ];
    }

    wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file_path ) );
    return [ $attach_id, '' ];
}

// ══════════════════════════════════════════════════════════════════════════════
// MODE: build — fetch Shopify collection and save position-based map
// ══════════════════════════════════════════════════════════════════════════════
if ( $mode === 'build' ) {
    $resp = wp_remote_get(
        'https://inmoto.eu/collections/moto-parts/products.json?limit=250&page=1',
        [ 'timeout' => 20, 'user-agent' => 'Mozilla/5.0' ]
    );

    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
        html_out( '<p class="fail">Failed to fetch Shopify collection. Check your internet connection.</p>' );
        exit;
    }

    $data     = json_decode( wp_remote_retrieve_body( $resp ), true );
    $shopify  = $data['products'] ?? [];
    $map      = [];

    foreach ( $shopify as $sp ) {
        $img = '';
        foreach ( $sp['images'] as $im ) {
            $src = $im['src'] ?? '';
            // Skip SVG placeholders
            if ( $src && substr( strtolower( parse_url( $src, PHP_URL_PATH ) ), -4 ) !== '.svg' ) {
                $img = $src;
                break;
            }
        }
        $map[] = [
            'title'  => $sp['title'],
            'handle' => $sp['handle'],
            'image'  => $img,
        ];
    }

    file_put_contents( $map_file, json_encode( $map ) );

    $next = $base_url . '&mode=fix&index=0';
    html_out(
        '<p class="ok">Shopify map built — ' . count( $map ) . ' products loaded. Starting fix...</p>',
        $next, 1
    );
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// MODE: fix — one product per page load
// ══════════════════════════════════════════════════════════════════════════════
if ( $mode === 'fix' ) {
    if ( ! file_exists( $map_file ) ) {
        html_out( '<p class="fail">Map file missing. <a href="' . htmlspecialchars( $base_url . '&mode=build' ) . '">Rebuild map</a></p>' );
        exit;
    }

    // Shopify map: indexed 0..N in collection order (matches $products order)
    $shopify_map = json_decode( file_get_contents( $map_file ), true );

    // WP products missing thumbnail
    $missing = get_posts( [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'meta_query'     => [[
            'key'     => '_thumbnail_id',
            'compare' => 'NOT EXISTS',
        ]],
    ] );
    // Also catch broken thumbnail IDs (attachment deleted)
    foreach ( get_posts( [ 'post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' ] ) as $pid ) {
        if ( in_array( $pid, $missing ) ) continue;
        $tid = get_post_thumbnail_id( $pid );
        if ( $tid && ! get_post( $tid ) ) $missing[] = $pid;
    }

    $total = count( $missing );

    if ( $total === 0 || $index >= $total ) {
        @unlink( $map_file );
        html_out( '<p class="ok" style="font-size:18px">✓ All done! No more products with missing images.</p><p><a href="/wp-admin/edit.php?post_type=product">View products in admin →</a></p>' );
        exit;
    }

    $pid      = $missing[ $index ];
    $wp_title = get_the_title( $pid );
    $next_url = $base_url . '&mode=fix&index=' . ( $index + 1 );
    $pct      = round( ( ( $index + 1 ) / $total ) * 100 );

    // Find this product's index in our Croatian $products array (exact title match)
    $our_index    = null;
    $original_urls = [];
    foreach ( $products as $i => $p ) {
        if ( strtolower( trim( $p['title'] ) ) === strtolower( trim( $wp_title ) ) ) {
            $our_index     = $i;
            $original_urls = $p['images'] ?? [];
            break;
        }
    }

    $debug   = [];
    $status  = 'fail';
    $msg     = '';
    $img_url = '';

    $debug[] = 'Our array index: ' . ( $our_index !== null ? $our_index : 'NOT FOUND' );

    // Strategy 1: try original URLs (they may still work for some products)
    foreach ( $original_urls as $url ) {
        $check = wp_remote_head( $url, [ 'timeout' => 8, 'user-agent' => 'Mozilla/5.0' ] );
        $code  = is_wp_error( $check ) ? 0 : wp_remote_retrieve_response_code( $check );
        $debug[] = 'Original URL HTTP ' . $code . ': ' . basename( parse_url( $url, PHP_URL_PATH ) );
        if ( $code === 200 ) {
            $img_url = $url;
            $debug[] = '→ Using original URL';
            break;
        }
    }

    // Strategy 2: position-based match from Shopify collection
    if ( ! $img_url && $our_index !== null && isset( $shopify_map[ $our_index ] ) ) {
        $shopify_entry = $shopify_map[ $our_index ];
        $debug[] = 'Shopify[' . $our_index . ']: "' . $shopify_entry['title'] . '" → ' . basename( parse_url( $shopify_entry['image'], PHP_URL_PATH ) );
        if ( $shopify_entry['image'] ) {
            $img_url = $shopify_entry['image'];
            $debug[] = '→ Using position-based Shopify image';
        }
    }

    // Strategy 3: Shopify search by part number in original filename
    if ( ! $img_url ) {
        foreach ( $original_urls as $url ) {
            $fname = basename( parse_url( $url, PHP_URL_PATH ) );
            if ( preg_match( '/^([\d]+\.[\d\.]+)_/i', $fname, $pm ) ) {
                $search_q = $pm[1];
                $debug[]  = 'Searching by part#: "' . $search_q . '"';
                $sresp    = wp_remote_get( 'https://inmoto.eu/search/suggest.json?q=' . urlencode( $search_q ) . '&resources[type]=product&resources[limit]=1', [ 'timeout' => 10, 'user-agent' => 'Mozilla/5.0' ] );
                if ( ! is_wp_error( $sresp ) && wp_remote_retrieve_response_code( $sresp ) === 200 ) {
                    $sdata   = json_decode( wp_remote_retrieve_body( $sresp ), true );
                    $handle  = $sdata['resources']['results']['products'][0]['handle'] ?? '';
                    $debug[] = 'Search handle: "' . $handle . '"';
                    if ( $handle ) {
                        $presp = wp_remote_get( 'https://inmoto.eu/products/' . $handle . '.json', [ 'timeout' => 10, 'user-agent' => 'Mozilla/5.0' ] );
                        if ( ! is_wp_error( $presp ) && wp_remote_retrieve_response_code( $presp ) === 200 ) {
                            $pdata = json_decode( wp_remote_retrieve_body( $presp ), true );
                            $img   = $pdata['product']['images'][0]['src'] ?? '';
                            if ( $img && substr( strtolower( parse_url( $img, PHP_URL_PATH ) ), -4 ) !== '.svg' ) {
                                $img_url = $img;
                                $debug[] = '→ Using search result image';
                            }
                        }
                    }
                }
                break;
            }
        }
    }

    // Download and attach
    if ( ! $img_url ) {
        $status = 'skip';
        $msg    = 'Image not available — removed from Shopify store';
    } else {
        [ $attach_id, $err ] = attach_image_from_url( $img_url, $pid );
        if ( $attach_id ) {
            set_post_thumbnail( $pid, $attach_id );
            $status = 'ok';
            $msg    = 'Fixed — attachment #' . $attach_id;
        } else {
            $status = 'fail';
            $msg    = $err;
        }
    }

    $bar  = '<div class="bar-wrap"><div class="bar-fill" style="width:' . $pct . '%"></div></div>';
    $bar .= '<p>' . ( $index + 1 ) . ' / ' . $total . ' (' . $pct . '%)</p>';
    $line = '<p class="' . $status . '">[' . strtoupper( $status ) . '] [ID:' . $pid . '] ' . esc_html( $wp_title ) . ' — ' . esc_html( $msg ) . '</p>';
    $dbg  = '<ul class="debug">' . implode( '', array_map( fn( $d ) => '<li>' . esc_html( $d ) . '</li>', $debug ) ) . '</ul>';

    html_out( $bar . $line . $dbg, $next_url, 2 );
    exit;
}

// Fallback
header( 'Location: ' . $base_url . '&mode=build' );
exit;
