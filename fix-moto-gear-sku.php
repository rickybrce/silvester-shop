<?php
/**
 * Sets SKU on moto-gear products from inmoto.eu/collections/moto-gear
 * Access: http://silvester-shop.test/fix-moto-gear-sku.php?pass=silvester2024
 * Add &run=1 to apply. Delete when done.
 */
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

$dry_run = empty( $_GET['run'] );

// Shopify title → SKU map
$shopify = [
    'BETA ENDURO JACKET ENDURO GP'          => 'A02.04.005.02.00',
    'BETA ENDURO JACKET SOFTSHELL ENDURO GP' => 'A02.04.006.02.00',
    'BETA ENDURO JERSEY ENDURO GP'          => 'A02.08.003.02.00',
    'BETA ENDURO PANTS ENDURO GP'           => 'A02.13.002.02.00',
    'BETA GLOVES LIGHT'                     => 'A02.06.003.02.00',
    'BETA GLOVES SHIELD'                    => 'A02.06.002.02.00',
    'BETA RAINCOAT JACKET'                  => 'A02.04.004.02.00',
    'BETA TRIAL JERSEY PRO'                 => 'A01.08.001.02.00',
    'BETA TRIAL PANTS PRO'                  => 'A01.13.001.02.00',
    'POD K4 2.0 KNEE BRACE BLACK PAIR'      => 'UPDK4V203',
    'POD K4 2.0 KNEE BRACE BLACK YOUTH PAIR' => 'UPDK4V202',
    'POD K4 2.0 KNEE BRACE VR46 PAIR'       => 'UPDK4VR4601',
    'POD K4 2.0 KNEE BRACE WHITE PAIR'      => 'UPDK4V207',
    'POD KX BAG KNEE BRACE'                 => 'UPDACC1',
];

// Distinctive search terms per product (words likely to survive translation)
$search_hints = [
    'BETA ENDURO JACKET ENDURO GP'          => ['ENDURO GP', 'JAKNA'],
    'BETA ENDURO JACKET SOFTSHELL ENDURO GP' => ['SOFTSHELL'],
    'BETA ENDURO JERSEY ENDURO GP'          => ['ENDURO GP', 'DRES'],
    'BETA ENDURO PANTS ENDURO GP'           => ['ENDURO GP', 'HLAČE'],
    'BETA GLOVES LIGHT'                     => ['GLOVES LIGHT', 'RUKAVICE LIGHT'],
    'BETA GLOVES SHIELD'                    => ['GLOVES SHIELD', 'RUKAVICE SHIELD'],
    'BETA RAINCOAT JACKET'                  => ['RAINCOAT', 'KABANICA'],
    'BETA TRIAL JERSEY PRO'                 => ['TRIAL JERSEY', 'TRIAL DRES'],
    'BETA TRIAL PANTS PRO'                  => ['TRIAL PANTS', 'TRIAL HLAČE'],
    'POD K4 2.0 KNEE BRACE BLACK PAIR'      => ['K4 2.0', 'BLACK'],
    'POD K4 2.0 KNEE BRACE BLACK YOUTH PAIR' => ['K4 2.0', 'YOUTH'],
    'POD K4 2.0 KNEE BRACE VR46 PAIR'       => ['K4 2.0', 'VR46'],
    'POD K4 2.0 KNEE BRACE WHITE PAIR'      => ['K4 2.0', 'WHITE'],
    'POD KX BAG KNEE BRACE'                 => ['KX BAG', 'TORBA'],
];

function find_product_by_title( $title ) {
    // 1. Exact match
    $q = new WP_Query( [
        'post_type' => 'product', 'title' => $title,
        'posts_per_page' => 1, 'no_found_rows' => true, 'fields' => 'ids',
    ] );
    if ( $q->have_posts() ) return $q->posts[0];

    // 2. Case-insensitive LIKE search
    global $wpdb;
    $like = '%' . $wpdb->esc_like( $title ) . '%';
    $id   = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_title LIKE %s LIMIT 1",
        $like
    ) );
    return $id ? (int) $id : 0;
}

function find_product_by_hints( $hints ) {
    global $wpdb;
    foreach ( $hints as $hint ) {
        $like = '%' . $wpdb->esc_like( $hint ) . '%';
        $id   = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_title LIKE %s LIMIT 1",
            $like
        ) );
        if ( $id ) return (int) $id;
    }
    return 0;
}

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Fix Moto-Gear SKU</title>';
echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}
h2{color:#fff;}.ok{color:#4caf50;}.skip{color:#888;}.fail{color:#f44336;}.updated{color:#2196f3;}
table{border-collapse:collapse;width:100%;}td,th{padding:6px 10px;border:1px solid #333;text-align:left;}
th{background:#222;}a{color:#2196f3;}</style></head><body>';
echo '<h2>Moto-Gear SKU Fix ' . ( $dry_run ? '— DRY RUN' : '— LIVE' ) . '</h2>';
if ( $dry_run ) echo '<p><a href="?pass=silvester2024&run=1">▶ Apply changes (live run)</a></p>';
echo '<table><tr><th>Shopify Title</th><th>WP Product Found</th><th>WP Title</th><th>SKU</th><th>Action</th></tr>';

$fixed = $skipped = $notfound = 0;

foreach ( $shopify as $shopify_title => $sku ) {
    $pid = find_product_by_title( $shopify_title );
    if ( ! $pid ) $pid = find_product_by_hints( $search_hints[ $shopify_title ] ?? [] );

    if ( ! $pid ) {
        echo '<tr><td>' . esc_html( $shopify_title ) . '</td><td colspan="3" class="fail">NOT FOUND</td><td class="fail">—</td></tr>';
        $notfound++;
        continue;
    }

    $product   = wc_get_product( $pid );
    $wp_title  = get_the_title( $pid );
    $current   = $product->get_sku();

    if ( $current === $sku ) {
        echo '<tr><td>' . esc_html( $shopify_title ) . '</td><td>#' . $pid . '</td><td>' . esc_html( $wp_title ) . '</td><td>' . esc_html( $current ) . '</td><td class="skip">Already set</td></tr>';
        $skipped++;
    } else {
        $action = $current ? "Update ($current → $sku)" : "Set $sku";
        if ( ! $dry_run ) {
            $product->set_sku( $sku );
            $product->save();
        }
        echo '<tr><td>' . esc_html( $shopify_title ) . '</td><td>#' . $pid . '</td><td>' . esc_html( $wp_title ) . '</td><td>' . esc_html( $sku ) . '</td><td class="' . ( $dry_run ? 'updated' : 'ok' ) . '">' . esc_html( $action ) . ( $dry_run ? ' (dry)' : ' ✓' ) . '</td></tr>';
        $fixed++;
    }
}

echo '</table>';
echo "<p>Fixed/would fix: <b>$fixed</b> &nbsp; Skipped: <b>$skipped</b> &nbsp; Not found: <b>$notfound</b></p>";
if ( $notfound ) echo '<p class="fail">Not-found products need manual matching. Check exact WP title and add a search hint.</p>';
echo '</body></html>';
