<?php
/**
 * Browser importer — moto-gear products, one per page load.
 * Access: http://silvester-shop.test/do-import-moto-gear.php?pass=silvester2024
 * Delete when done!
 */
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

// ── Helpers ────────────────────────────────────────────────────────────────────

function gear_get_or_create_category( $name, $parent_id = 0 ) {
    $term = get_term_by( 'name', $name, 'product_cat' );
    if ( $term ) return $term->term_id;
    $r = wp_insert_term( $name, 'product_cat', [ 'parent' => $parent_id ] );
    return is_wp_error( $r ) ? 0 : $r['term_id'];
}

function gear_attach_image( $url, $post_id ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $resp = wp_remote_get( $url, [ 'timeout' => 15, 'user-agent' => 'Mozilla/5.0' ] );
    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) return 0;
    $body = wp_remote_retrieve_body( $resp );
    if ( strlen( $body ) < 100 ) return 0;

    $ext = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
    if ( ! in_array( $ext, [ 'jpg','jpeg','png','gif','webp' ] ) ) {
        $ct  = strtok( wp_remote_retrieve_header( $resp, 'content-type' ), ';' );
        $ext = [ 'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif' ][ trim($ct) ] ?? 'jpg';
    }

    $upload    = wp_upload_dir();
    $filename  = $post_id . '_gear.' . $ext;
    $filepath  = $upload['path'] . '/' . $filename;
    file_put_contents( $filepath, $body );

    $ft = wp_check_filetype( $filename );
    if ( ! $ft['type'] ) return 0;

    $aid = wp_insert_attachment( [
        'post_mime_type' => $ft['type'],
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $filepath, $post_id );

    if ( is_wp_error( $aid ) ) return 0;
    wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $filepath ) );
    return $aid;
}

// ── Product data ───────────────────────────────────────────────────────────────

$products = [
  [
    'title'       => 'BETA ENDURO JAKNA ENDURO GP',
    'description' => 'Originalna Beta Enduro jakna Enduro GP. Izrađena od visokokvalitetnih materijala za maksimalnu zaštitu i udobnost pri enduro vožnji. Lagana i izdržljiva konstrukcija prilagođena zahtjevnim terenskim uvjetima.',
    'price'       => '174.73',
    'sku'         => 'A02.04.005.02.00',
    'subcat'      => 'Jakne',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_166255fc-46ee-4874-aa6e-7b8ffff9fe7b.jpg?v=1776582668',
  ],
  [
    'title'       => 'BETA ENDURO SOFTSHELL JAKNA ENDURO GP',
    'description' => 'Originalna Beta Enduro Softshell jakna Enduro GP. Mekana i fleksibilna softshell tkanina pruža odličnu zaštitu od vjetra i laganih padalina uz maksimalnu slobodu pokreta.',
    'price'       => '201.52',
    'sku'         => 'A02.04.006.02.00',
    'subcat'      => 'Jakne',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_fb48a125-c788-4bd2-b31e-d72f469abad8.jpg?v=1776582654',
  ],
  [
    'title'       => 'BETA ENDURO DRES ENDURO GP',
    'description' => 'Originalni Beta Enduro dres Enduro GP. Izrađen od prozračnog i brzosušećeg materijala za optimalni komfort pri dugotrajnoj enduro vožnji. Ergonomski kroj omogućuje slobodu pokreta.',
    'price'       => '58.24',
    'sku'         => 'A02.08.003.02.00',
    'subcat'      => 'Dresovi',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_94106cb2-e514-4c6c-9b8f-bc6c28d7ac80.jpg?v=1776582666',
  ],
  [
    'title'       => 'BETA ENDURO HLAČE ENDURO GP',
    'description' => 'Originalne Beta Enduro hlače Enduro GP. Ojačane na mjestima najvećeg trošenja s ventilacijskim panelima za optimalan protok zraka. Kompatibilne s knee brace štitnicima.',
    'price'       => '190.91',
    'sku'         => 'A02.13.002.02.00',
    'subcat'      => 'Hlače',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b5c2c7ec-8eb3-44c0-b4aa-82b2854a9eb6.jpg?v=1776582674',
  ],
  [
    'title'       => 'BETA RUKAVICE LIGHT',
    'description' => 'Originalne Beta Light rukavice za vožnju motociklom. Lagane i prozračne rukavice za maksimalnu osjetljivost i udobnost u toplijim uvjetima vožnje.',
    'price'       => '46.59',
    'sku'         => 'A02.06.003.02.00',
    'subcat'      => 'Rukavice',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8c1a5e79-c37e-4783-b9da-25bf29a43856.jpg?v=1776582671',
  ],
  [
    'title'       => 'BETA RUKAVICE SHIELD',
    'description' => 'Originalne Beta Shield rukavice za vožnju motociklom. Pojačana zaštita zglobova i dlana uz izvrsnu osjetljivost na upravljaču za precizno upravljanje.',
    'price'       => '45.30',
    'sku'         => 'A02.06.002.02.00',
    'subcat'      => 'Rukavice',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4889dcb2-053b-4a96-bbf3-78adf1d94c78.jpg?v=1776582677',
  ],
  [
    'title'       => 'BETA KABANICA',
    'description' => 'Originalna Beta kabanica za motocikle. Lagana i kompaktna kabanica koja se lako sprema u džep. Pruža zaštitu od kiše za vozača u neočekivanim vremenskim uvjetima na terenu.',
    'price'       => '42.42',
    'sku'         => 'A02.04.004.02.00',
    'subcat'      => 'Vodootporna odjeća',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_293ed9ea-01be-4bff-905b-794ebb3b9c1a.jpg?v=1776582660',
  ],
  [
    'title'       => 'BETA TRIAL DRES PRO',
    'description' => 'Originalni Beta Trial Pro dres za trial natjecanja. Izrađen od laganih i prozračnih materijala prema tvorničkim specifikacijama za optimalne performanse na trial natjecanjima.',
    'price'       => '56.95',
    'sku'         => 'A01.08.001.02.00',
    'subcat'      => 'Dresovi',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_050a4635-0796-4713-acbf-e2c2df032143.jpg?v=1776582662',
  ],
  [
    'title'       => 'BETA TRIAL HLAČE PRO',
    'description' => 'Originalne Beta Trial Pro hlače za trial natjecanja. Fleksibilne i izdržljive hlače s ojačanjima na ključnim mjestima za maksimalnu slobodu pokreta i zaštitu pri trial vožnji.',
    'price'       => '148.33',
    'sku'         => 'A01.13.001.02.00',
    'subcat'      => 'Hlače',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_980d9cfe-f957-447f-aa7a-a34efbac4917.jpg?v=1776582657',
  ],
  [
    'title'       => 'POD K4 2.0 ŠTITNICI KOLJENA CRNI PAR',
    'description' => 'POD K4 2.0 štitnici koljena u crnoj boji — par. Nagrađivani dizajn štitnika koljena s revolucionarnom tehnologijom zaštite. Certificirani CE Level 2, idealni za enduro i motocross.',
    'price'       => '565.47',
    'sku'         => 'UPDK4V203',
    'subcat'      => 'Štitnici',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/POD-ProductShot-K4-GRAPH-PAIR-600x722.webp?v=1726923753',
  ],
  [
    'title'       => 'POD K4 2.0 ŠTITNICI KOLJENA CRNI YOUTH PAR',
    'description' => 'POD K4 2.0 štitnici koljena u crnoj boji za mlade vozače — par. Isti napredni sustav zaštite kao odrasla verzija, prilagođen manjim dimenzijama za mlade motocikliste.',
    'price'       => '393.56',
    'sku'         => 'UPDK4V202',
    'subcat'      => 'Štitnici',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/POD-ProductShot-K4-YTH-PAIR-m-600x722.webp?v=1726925180',
  ],
  [
    'title'       => 'POD K4 2.0 ŠTITNICI KOLJENA VR46 PAR',
    'description' => 'POD K4 2.0 štitnici koljena limitirano izdanje VR46 — par. Ekskluzivni dizajn u suradnji s timom Valentina Rossija s istom naprednom CE Level 2 zaštitnom tehnologijom.',
    'price'       => '747.68',
    'sku'         => 'UPDK4VR4601',
    'subcat'      => 'Štitnici',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/POD-ProductShot-K4-VR46LE-Pair-FRONT-600x722.webp?v=1726924029',
  ],
  [
    'title'       => 'POD K4 2.0 ŠTITNICI KOLJENA BIJELI PAR',
    'description' => 'POD K4 2.0 štitnici koljena u bijeloj boji — par. Vrhunska zaštita koljena s CE Level 2 certifikatom i prilagodljivim sustavom pričvršćivanja za savršeno pristajanje.',
    'price'       => '565.47',
    'sku'         => 'UPDK4V207',
    'subcat'      => 'Štitnici',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/POD-ProductShot-K4-PAIR-600x722.webp?v=1726923670',
  ],
  [
    'title'       => 'POD KX TORBA ZA ŠTITNIK KOLJENA',
    'description' => 'POD KX torba za nošenje štitnika koljena. Dizajnirana za transport i pohranu POD knee brace štitnika. Zaštitna i praktična torba s ručkama i patentnim zatvaračima.',
    'price'       => '41.09',
    'sku'         => 'UPDACC1',
    'subcat'      => 'Štitnici',
    'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/POD-ProductShot-BraceBag-Closed-600x722.webp?v=1726925385',
  ],
];

// ── Page ───────────────────────────────────────────────────────────────────────

$index    = max( 0, (int) ( $_GET['index'] ?? 0 ) );
$total    = count( $products );
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?pass=silvester2024';

function gear_html( $content, $refresh = '', $delay = 2 ) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Moto Gear Import</title>';
    if ( $refresh ) echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . htmlspecialchars( $refresh ) . '">';
    echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}h2{color:#fff;}
    a{color:#2196f3;}.ok{color:#4caf50;}.skip{color:#888;}.fail{color:#f44336;}
    .bar-wrap{background:#333;border-radius:4px;height:18px;width:100%;max-width:500px;margin:10px 0;}
    .bar-fill{background:#4caf50;height:18px;border-radius:4px;}</style></head><body>
    <h2>Moto Gear Import</h2>' . $content . '</body></html>';
}

if ( $index >= $total ) {
    gear_html( '<p class="ok" style="font-size:18px">✓ All ' . $total . ' products imported!</p><p><a href="/wp-admin/edit.php?post_type=product">View products →</a></p>' );
    exit;
}

$data     = $products[ $index ];
$next_url = $base_url . '&index=' . ( $index + 1 );
$pct      = round( ( ( $index + 1 ) / $total ) * 100 );

// Categories: Moto oprema → subcategory
$parent_id = gear_get_or_create_category( 'Moto oprema' );
$cat_ids   = [ $parent_id ];
if ( ! empty( $data['subcat'] ) ) {
    $sub_id    = gear_get_or_create_category( $data['subcat'], $parent_id );
    if ( $sub_id ) $cat_ids[] = $sub_id;
}
$cat_id = $parent_id; // keep for legacy ref below

// Skip if SKU already exists
$existing_by_sku = wc_get_product_id_by_sku( $data['sku'] );
if ( $existing_by_sku ) {
    $bar  = '<div class="bar-wrap"><div class="bar-fill" style="width:' . $pct . '%"></div></div>';
    $bar .= '<p>' . ( $index + 1 ) . ' / ' . $total . ' (' . $pct . '%)</p>';
    gear_html( $bar . '<p class="skip">[SKIP] ' . esc_html( $data['title'] ) . ' — SKU ' . esc_html( $data['sku'] ) . ' already exists (ID:' . $existing_by_sku . ')</p>', $next_url, 1 );
    exit;
}

// Create product
$product = new WC_Product_Simple();
$product->set_name( $data['title'] );
$product->set_description( $data['description'] );
$product->set_regular_price( $data['price'] );
$product->set_sku( $data['sku'] );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product->set_manage_stock( false );
$product->set_stock_status( 'instock' );
$product->set_category_ids( array_filter( array_unique( $cat_ids ) ) );
$pid = $product->save();

$img_msg = '';
if ( $pid && ! is_wp_error( $pid ) && ! empty( $data['image'] ) ) {
    $aid = gear_attach_image( $data['image'], $pid );
    if ( $aid ) { set_post_thumbnail( $pid, $aid ); $img_msg = ', image set'; }
    else $img_msg = ', image failed';
}

$status = ( $pid && ! is_wp_error( $pid ) ) ? 'ok' : 'fail';
$msg    = $status === 'ok' ? 'Created (ID:' . $pid . ')' . $img_msg : 'Save failed';

$bar  = '<div class="bar-wrap"><div class="bar-fill" style="width:' . $pct . '%"></div></div>';
$bar .= '<p>' . ( $index + 1 ) . ' / ' . $total . ' (' . $pct . '%)</p>';
gear_html( $bar . '<p class="' . $status . '">[' . strtoupper( $status ) . '] ' . esc_html( $data['title'] ) . ' — ' . esc_html( $msg ) . '</p>', $next_url );
