<?php
/**
 * Mixed importer v1 — Beta parts + apparel + accessories (inmoto.eu + boano.com).
 * Access: http://silvester-shop.test/do-import-mixed-v1.php?pass=silvester2024
 * Delete when done!
 */
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

// ── Helpers ────────────────────────────────────────────────────────────────────

function mv1_cat( $name, $parent = 0 ) {
    $t = get_term_by( 'name', $name, 'product_cat' );
    if ( $t ) return $t->term_id;
    $r = wp_insert_term( $name, 'product_cat', [ 'parent' => $parent ] );
    return is_wp_error( $r ) ? 0 : $r['term_id'];
}

function mv1_brand( $name ) {
    $t = get_term_by( 'name', $name, 'product_brand' )
      ?: get_term_by( 'slug', sanitize_title( $name ), 'product_brand' );
    if ( $t ) return $t->term_id;
    $r = wp_insert_term( $name, 'product_brand' );
    return is_wp_error( $r ) ? 0 : $r['term_id'];
}

function mv1_attach_image( $url, $pid ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $resp = wp_remote_get( $url, [ 'timeout' => 20, 'user-agent' => 'Mozilla/5.0' ] );
    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) return 0;
    $body = wp_remote_retrieve_body( $resp );
    if ( strlen( $body ) < 100 ) return 0;

    $ext = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
    if ( ! in_array( $ext, [ 'jpg','jpeg','png','gif','webp' ] ) ) {
        $ct  = strtok( wp_remote_retrieve_header( $resp, 'content-type' ), ';' );
        $ext = [ 'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif' ][ trim($ct) ] ?? 'jpg';
    }

    $u  = wp_upload_dir();
    $fn = $pid . '_mv1.' . $ext;
    $fp = $u['path'] . '/' . $fn;
    file_put_contents( $fp, $body );

    $ft = wp_check_filetype( $fn );
    if ( ! $ft['type'] ) return 0;

    $aid = wp_insert_attachment( [
        'post_mime_type' => $ft['type'],
        'post_title'     => sanitize_file_name( $fn ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $fp, $pid );

    if ( is_wp_error( $aid ) ) return 0;
    wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $fp ) );
    return $aid;
}

// ── Product data ───────────────────────────────────────────────────────────────
// parent: 'Moto dijelovi' for parts/accessories, 'Moto oprema' for apparel
// brand: exact brand name string

$products = [

    // ── inmoto.eu — Beta OEM parts ───────────────────────────────────────────

    [
        'title'       => 'BETA KOMPLET KVAČILA MY18-MY21',
        'description' => 'Originalni Beta OEM komplet kvačila za modele MY18-MY21. Izrađen prema tvorničkim tolerancijama Beta za gladak i učinkovit prijenos snage. Osigurava ispravnu tvrdoću, tolerancije i trajnost.',
        'price'       => '53.73',
        'sku'         => '029.03.008.82.00',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Prijenos',
        'brand'       => 'Beta',
        'image'       => 'https://inmoto.eu/cdn/shop/files/Unknown_579c45d3-3528-40c4-8753-cfc722877f21.jpg?v=1776564764&width=1946',
    ],
    [
        'title'       => 'BETA KOMPLET PREDNJE VILICE - KIT XTRAINER',
        'description' => 'Kompletna 48mm ZF vilica s zatvorenim karušom koja značajno poboljšava dinamičke performanse motocikla. Isporučuje se s upravljačkim pločama, osi kotača, distancerima, zaštitama stabla upravljača (crvena i bijela) i svim potrebnim za montažu. Kompatibilno s modelima XTRAINER MY15+.',
        'price'       => '1699.50',
        'sku'         => '036.46.002.82.61',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Ovjes',
        'brand'       => 'Beta',
        'image'       => 'https://inmoto.eu/cdn/shop/files/Unknown_f946ac65-b21f-4dc0-9792-fcf793e732bd.jpg?v=1776564849&width=1946',
    ],
    [
        'title'       => 'BETA KOMPLET MOTARD RR MY\' 13-19',
        'description' => 'Originalni Beta OEM komplet za konverziju kotača (supermoto) za Beta RR enduro motocikle MY13-19. Izrađen prema tvorničkim OEM tolerancijama za savršeno pristajanje i pouzdanu funkciju.',
        'price'       => '1324.60',
        'sku'         => '031.46.009.82.97',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Kotači i gume',
        'brand'       => 'Beta',
        'image'       => 'https://inmoto.eu/cdn/shop/files/Unknown_4e72cfa5-e8a0-4181-b884-56b34dfb89aa.jpg?v=1776557892&width=1946',
    ],

    // ── inmoto.eu — Beta paddock apparel ─────────────────────────────────────

    [
        'title'       => 'BETA PADDOCK KAPA TEAM REPLICA',
        'description' => 'Službena Beta team replica paddock kapa.',
        'price'       => '26.51',
        'sku'         => 'A03.02.001.00.00',
        'parent'      => 'Moto oprema',
        'subcat'      => 'Kape',
        'brand'       => 'Beta',
        'image'       => 'https://inmoto.eu/cdn/shop/files/Unknown_84c9470f-7a29-4be9-a06b-aa320248fb22.jpg?v=1776579893&width=1946',
    ],
    [
        'title'       => 'BETA PADDOCK DUKSERICA PLAVA CRVENA PRUGA',
        'description' => 'Beta paddock dukserica u plavoj boji s crvenom prugom. Dostupna u veličinama S do XXXL.',
        'price'       => '47.63',
        'sku'         => 'A03.03.004.02.00',
        'parent'      => 'Moto oprema',
        'subcat'      => 'Duksevi',
        'brand'       => 'Beta',
        'image'       => 'https://inmoto.eu/cdn/shop/files/Unknown_a8e8b457-ef91-4810-a29e-3289dab6fcb8.jpg?v=1776582614&width=1946',
    ],
    [
        'title'       => 'BETA KABANICA',
        'description' => 'Originalna Beta kabanica. Lagana i kompaktna kabanica koja se lako sprema. Pruža zaštitu od kiše za vozača u neočekivanim vremenskim uvjetima na terenu.',
        'price'       => '42.42',
        'sku'         => 'A02.04.004.02.00',
        'parent'      => 'Moto oprema',
        'subcat'      => 'Vodootporna odjeća',
        'brand'       => 'Beta',
        'image'       => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_293ed9ea-01be-4bff-905b-794ebb3b9c1a.jpg?v=1776582660',
    ],

    // ── boano.com — accessories & parts ──────────────────────────────────────

    [
        'title'       => 'CIRCUIT ELECTRA - RUKOHVATI ZA UPRAVLJAČ',
        'description' => 'Cross/enduro rukohvati za upravljač Ø22/25mm. Inovativna mikro-piramidna površina za poboljšano hvatanje. Kvalitetni materijali za udobnost i trajnost na terenu. Uključuje nehrđajuću žicu za pričvršćivanje u trkačkom stilu. Dostupne boje: Crna/Bijela (MA008-212), Plava/Bijela (MA008-242), Crvena/Bijela (MA008-252).',
        'price'       => '18.11',
        'sku'         => 'MA008',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Upravljač',
        'brand'       => 'Circuit',
        'image'       => 'https://www.boano.com/cgi-bin/immagini/4fcfac9a-4469-45e2-a56e-27bb216ca5ee_circuit%20electra%20all.jpg',
    ],
    [
        'title'       => 'AUTOMATSKA KVAČILA REKLUSE RADIUSX 4.0 BETA 2T \'22-\'25',
        'description' => 'Visokoučinkovita automatska kvačila s prevencijom gašenja motora za Beta 2T \'22-\'25. Drop-in zamjena za paket kvačila. TorqDrive tarni diskovi + EXP 4.0 automatska funkcija. Vanjsko podešavanje u vožnji. Produžen vijek trajanja kvačila u usporedbi s OEM. Uključuje Rekluse košarice kvačila.',
        'price'       => '1005.50',
        'sku'         => 'RMS-8302122',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Prijenos',
        'brand'       => 'Rekluse',
        'image'       => 'https://www.boano.com/cgi-bin/immagini/5604c343-3413-45ae-9e18-1ba1e0228d22_friz%20rekluse%20Radius%20x%20Beta.jpg',
    ],
    [
        'title'       => 'KYB 48MM BOANO KASHIMA FACTORY - KOMPLET VILICE',
        'description' => 'Kompletni KYB 48mm natjecateljski komplet vilice — najnovija evolucija. Kashima premazane cijevi, crni PVD unutarnji klizni elementi, novi sustav prednaprezanja opruge, podešavanje karuša. Opruge u 3 krutosti (5.6/5.8/6.0). Boano-spec upravljačke ploče za bolje ponašanje u zavojima. Dostupno u plavim ili crnim pločama. Rok isporuke 25 dana.',
        'price'       => '3430.00',
        'sku'         => 'KYB-BKF-T7',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Ovjes',
        'brand'       => 'KYB',
        'image'       => 'https://www.boano.com/cgi-bin/immagini/894d0e3c-39d4-4e83-9823-d9411a373a3b_KYB%2048%20Boano%20Kashima.jpg',
    ],
    [
        'title'       => 'ZAŠTITA MOTORA BOANO 8MM EXTREME BETA RR 250/300 2T \'20-\'26',
        'description' => 'Nova Boano 8mm PHDS plastična zaštita motora za modele Beta RR 250/300 2T 2020-2026. Razvijeno u suradnji s Cross Pro. Poboljšana bočna zaštita okvira i zaštita zadnje poluge. Prikladna za enduro i ekstremni teren.',
        'price'       => '114.90',
        'sku'         => 'PMPN-2T-EXH',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Zaštita okvira',
        'brand'       => 'Boano',
        'image'       => 'https://www.boano.com/cgi-bin/immagini/c0389b23-84a4-421c-aecb-da244d0e1f03_paramotore%208mm%20EXH%20beta%202t.jpg',
    ],
    [
        'title'       => 'KOMPLET VILICE BETA X-TRAINER \'15-\'26',
        'description' => 'Komplet vilice razvijen od strane trkaćeg odjela Betamotora za X-Trainer. 48mm vilica s zatvorenim karušom ZF poboljšava dinamičke performanse, stabilnost i preciznost upravljanja. Uključuje kompletne vilice s upravljačkim pločama, osi kotača, distancerima, zaštitom stabla upravljača, spojnim materijalom i naljepnicama zaštite vilice. Podešavajući prednapon, kompresija i povrat.',
        'price'       => '1393.03',
        'sku'         => '036.46.002.82.61-BOANO',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Ovjes',
        'brand'       => 'Beta',
        'image'       => 'https://www.boano.com/cgi-bin/immagini/8a0162d4-fe8a-4ee3-b8a3-751c406b27bb_kit%20forcella%20xtrainer.jpg',
    ],
    [
        'title'       => 'ZAŠTITA MOTORA AXP EXTREME CRVENA BETA RR 250/300 2T \'20-\'24',
        'description' => '8mm HDPE zaštita motora sa širokim bočnim nastavcima koji štite karter i vodenu pumpu. Produženi donji dio štiti vezu amortizera. Rupe za odvodnjavanje blata. Brza montaža i demontaža s priloženom opremom. NIJE kompatibilna s Racing verzijom.',
        'price'       => '123.16',
        'sku'         => 'AX1551',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Zaštita okvira',
        'brand'       => 'AXP',
        'image'       => 'https://www.boano.com/cgi-bin/immagini/5f875bb6-ab6d-4672-9e3d-1ea418676573_1.jpg',
    ],
    [
        'title'       => 'SVJEĆICA NGK "BR8 ECM" BETA RR 2T',
        'description' => 'NGK BR8 ECM svjećica za Beta RR dvotaktne modele.',
        'price'       => '7.56',
        'sku'         => '12.25123.000',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Motor',
        'brand'       => 'NGK',
        'image'       => 'https://www.boano.com/cgi-bin/immagini/20198231288.jpg',
    ],
    [
        'title'       => 'KLIP VERTEX BETA RR 300 2T \'22-\'26 ORIGINAL - VELIČINA "C"',
        'description' => 'Originalni Beta klip veličine C (72,97mm) za modele RR 300 2T \'22-\'26. Za utvrđivanje veličine klipa, skinite lijevi poklopac ventila i provjerite slovo utisnuto na cilindru (A, B ili C).',
        'price'       => '146.70',
        'sku'         => '026.02.060.80.0C',
        'parent'      => 'Moto dijelovi',
        'subcat'      => 'Motor',
        'brand'       => 'Vertex',
        'image'       => 'https://www.boano.com/cgi-bin/immagini/c5c236af-b447-40d0-8cb5-b6a5386e0e41_2019822101240.jpg',
    ],
];

// ── Page loop ──────────────────────────────────────────────────────────────────

$index    = max( 0, (int) ( $_GET['index'] ?? 0 ) );
$total    = count( $products );
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?pass=silvester2024';

function mv1_html( $body, $refresh = '', $delay = 2 ) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Mixed Import v1</title>';
    if ( $refresh ) echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . htmlspecialchars( $refresh ) . '">';
    echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}h2{color:#fff;}
    a{color:#2196f3;}.ok{color:#4caf50;}.skip{color:#888;}.fail{color:#f44336;}
    .bar-wrap{background:#333;border-radius:4px;height:18px;width:100%;max-width:500px;margin:10px 0;}
    .bar-fill{background:#4caf50;height:18px;border-radius:4px;}
    small{color:#555;font-size:11px;display:block;}</style></head>
    <body><h2>Mixed Import v1</h2>' . $body . '</body></html>';
}

if ( $index >= $total ) {
    mv1_html( '<p class="ok" style="font-size:18px">✓ All ' . $total . ' products done!</p><p><a href="/wp-admin/edit.php?post_type=product">View products →</a></p>' );
    exit;
}

$data     = $products[ $index ];
$next_url = $base_url . '&index=' . ( $index + 1 );
$pct      = round( ( ( $index + 1 ) / $total ) * 100 );

// Categories
$parent_id = mv1_cat( $data['parent'] );
$cat_ids   = [ $parent_id ];
if ( ! empty( $data['subcat'] ) ) {
    $sub_id = mv1_cat( $data['subcat'], $parent_id );
    if ( $sub_id ) $cat_ids[] = $sub_id;
}
$cat_ids = array_filter( array_unique( $cat_ids ) );

// Skip if SKU already exists
$pid  = wc_get_product_id_by_sku( $data['sku'] );
$mode = $pid ? 'update' : 'create';

$img_note = '';
$product  = $pid ? wc_get_product( $pid ) : new WC_Product_Simple();

$product->set_name( $data['title'] );
$product->set_description( $data['description'] );
$product->set_regular_price( $data['price'] );
$product->set_sku( $data['sku'] );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product->set_manage_stock( false );
$product->set_stock_status( 'instock' );
$product->set_category_ids( $cat_ids );
$pid = $product->save();

if ( $pid && ! is_wp_error( $pid ) ) {
    if ( ! empty( $data['image'] ) && ( $mode === 'create' || ! has_post_thumbnail( $pid ) ) ) {
        $aid = mv1_attach_image( $data['image'], $pid );
        if ( $aid ) { set_post_thumbnail( $pid, $aid ); $img_note = ' img✓'; }
        else $img_note = ' img✗';
    }
    if ( ! empty( $data['brand'] ) ) {
        $bid = mv1_brand( $data['brand'] );
        if ( $bid ) wp_set_object_terms( $pid, [ $bid ], 'product_brand' );
    }
    $status = 'ok';
    $msg    = '[' . strtoupper( $mode ) . '] ID:' . $pid . ' SKU:' . $data['sku'] . $img_note;
} else {
    $status = 'fail';
    $msg    = 'Save failed';
}

$bar  = '<div class="bar-wrap"><div class="bar-fill" style="width:' . $pct . '%"></div></div><p>' . ( $index + 1 ) . '/' . $total . ' (' . $pct . '%)</p>';
$line = '<p class="' . $status . '">' . esc_html( $data['title'] ) . ' — ' . esc_html( $msg ) . '</p>';
$sub  = '<small>brand: ' . esc_html( $data['brand'] ?? '' ) . ' | cat: ' . esc_html( $data['parent'] ) . ' → ' . esc_html( $data['subcat'] ?? '' ) . '</small>';
mv1_html( $bar . $line . $sub, $next_url );
