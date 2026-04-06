<?php
/**
 * Fix missing product images by fetching fresh URLs from inmoto.eu Shopify API.
 * Run with: wp eval-file fix-images.php --path="/path/to/wp"
 *
 * How it works:
 * 1. Fetches all products from inmoto.eu Shopify JSON API (paginated)
 * 2. For each WooCommerce product missing a featured image, finds a match
 *    by title comparison and downloads the current image URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
    require_once dirname( __FILE__ ) . '/wp-load.php';
}

set_time_limit( 0 );

// ── Helpers ────────────────────────────────────────────────────────────────────

function fix_normalize( $str ) {
    return strtolower( trim( preg_replace( '/\s+/', ' ', $str ) ) );
}

function fix_attach_image( $image_url, $post_id ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload_dir = wp_upload_dir();
    $filename   = $post_id . '_fix_' . basename( parse_url( $image_url, PHP_URL_PATH ) );
    $file_path  = $upload_dir['path'] . '/' . $filename;

    if ( ! file_exists( $file_path ) || filesize( $file_path ) < 100 ) {
        $response = wp_remote_get( $image_url, [
            'timeout'    => 20,
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        ] );
        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            echo "  [FAIL] Could not download: $image_url\n";
            return false;
        }
        $body = wp_remote_retrieve_body( $response );
        if ( strlen( $body ) < 100 ) {
            echo "  [FAIL] Empty body for: $image_url\n";
            return false;
        }
        file_put_contents( $file_path, $body );
    }

    $wp_filetype = wp_check_filetype( $filename );
    if ( ! $wp_filetype['type'] ) {
        echo "  [FAIL] Unknown filetype: $filename\n";
        return false;
    }

    $attach_id = wp_insert_attachment( [
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $file_path, $post_id );

    if ( is_wp_error( $attach_id ) ) {
        echo "  [FAIL] Attachment error: " . $attach_id->get_error_message() . "\n";
        return false;
    }

    wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file_path ) );
    return $attach_id;
}

// ── Step 1: Find WooCommerce products without featured images ─────────────────

echo "=== Finding products without featured images ===\n";

$wp_products = get_posts( [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'fields'         => 'ids',
] );

$missing = [];
foreach ( $wp_products as $pid ) {
    if ( ! has_post_thumbnail( $pid ) ) {
        $missing[ $pid ] = get_the_title( $pid );
    }
}

if ( empty( $missing ) ) {
    echo "All products have images. Nothing to fix.\n";
    exit;
}

echo "Found " . count( $missing ) . " products without images:\n";
foreach ( $missing as $pid => $title ) {
    echo "  [$pid] $title\n";
}

// ── Step 2: Fetch Shopify product catalog from inmoto.eu ──────────────────────

echo "\n=== Fetching Shopify product catalog ===\n";

$shopify_products = [];
$page = 1;
$per_page = 250;

while ( true ) {
    $url = "https://inmoto.eu/collections/moto-parts/products.json?limit={$per_page}&page={$page}";
    echo "Fetching page $page...\n";

    $response = wp_remote_get( $url, [
        'timeout'    => 30,
        'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ] );

    if ( is_wp_error( $response ) ) {
        echo "[ERROR] API request failed: " . $response->get_error_message() . "\n";
        break;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data['products'] ) ) break;

    foreach ( $data['products'] as $sp ) {
        $shopify_products[ fix_normalize( $sp['title'] ) ] = $sp;
    }

    if ( count( $data['products'] ) < $per_page ) break;
    $page++;
    sleep( 1 );
}

echo "Loaded " . count( $shopify_products ) . " Shopify products.\n";

// ── Step 3: Match and fix each WP product ────────────────────────────────────

echo "\n=== Fixing images ===\n";

$fixed   = 0;
$skipped = 0;

foreach ( $missing as $pid => $wp_title ) {
    $key = fix_normalize( $wp_title );

    // Direct match
    $match = $shopify_products[ $key ] ?? null;

    // Fuzzy: try stripping trailing parenthetical like "(1)", "(2)"
    if ( ! $match ) {
        $stripped = fix_normalize( preg_replace( '/\s*\(\d+\)$/', '', $wp_title ) );
        $match    = $shopify_products[ $stripped ] ?? null;
    }

    // Fuzzy: partial match if no exact match found
    if ( ! $match ) {
        foreach ( $shopify_products as $skey => $sp ) {
            if ( strpos( $skey, $key ) !== false || strpos( $key, $skey ) !== false ) {
                $match = $sp;
                break;
            }
        }
    }

    if ( ! $match ) {
        echo "[SKIP] No Shopify match for: $wp_title\n";
        $skipped++;
        continue;
    }

    $image_url = $match['images'][0]['src'] ?? '';
    if ( ! $image_url ) {
        echo "[SKIP] No image in Shopify data for: $wp_title\n";
        $skipped++;
        continue;
    }

    echo "[FIX] $wp_title\n";
    echo "      URL: $image_url\n";

    $attach_id = fix_attach_image( $image_url, $pid );
    if ( $attach_id ) {
        set_post_thumbnail( $pid, $attach_id );
        echo "      OK (attachment $attach_id)\n";
        $fixed++;
    } else {
        $skipped++;
    }

    // Rate-limit protection
    usleep( 300000 ); // 0.3s between image downloads
}

echo "\n=== Done ===\n";
echo "Fixed:   $fixed\n";
echo "Skipped: $skipped\n";
