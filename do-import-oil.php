<?php
/**
 * Oil & lubricants importer — one product per page load.
 * Access: http://silvester-shop.test/do-import-oil.php?pass=silvester2024
 * Delete when done!
 */
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

// ── Helpers ────────────────────────────────────────────────────────────────────

function oil_cat( $name, $parent = 0 ) {
    $t = get_term_by( 'name', $name, 'product_cat' );
    if ( $t ) return $t->term_id;
    $r = wp_insert_term( $name, 'product_cat', [ 'parent' => $parent ] );
    return is_wp_error( $r ) ? 0 : $r['term_id'];
}

function oil_attach_image( $url, $pid ) {
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
    $u  = wp_upload_dir();
    $fn = $pid . '_oil.' . $ext;
    $fp = $u['path'] . '/' . $fn;
    file_put_contents( $fp, $body );
    $ft = wp_check_filetype( $fn );
    if ( ! $ft['type'] ) return 0;
    $aid = wp_insert_attachment( [
        'post_mime_type' => $ft['type'], 'post_title' => sanitize_file_name( $fn ),
        'post_content' => '', 'post_status' => 'inherit',
    ], $fp, $pid );
    if ( is_wp_error( $aid ) ) return 0;
    wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $fp ) );
    return $aid;
}

// ── Product data ───────────────────────────────────────────────────────────────

$products = [
  [
    'title'   => 'GRO KOČNIČNA TEKUĆINA DOT-4',
    'desc'    => 'Universalna kočnična tekućina za sve kočione sustave (disk ili bubanj) i sustave kvačila za motocikle i automobile. Certificirana prema SAE J1703/J1704 · ISO 4925 (klase 3/4) · FMVSS NO. 116 DOT 3/4.',
    'price'   => '7.21',
    'sku'     => '9030186',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/GROdot4.jpg?v=1726867183',
  ],
  [
    'title'   => 'GRO KOČNIČNA TEKUĆINA DOT-5.1',
    'desc'    => '100% sintetička kočnična tekućina za kvačila i hidrauličke kočione sustave lakih i teških vozila, motocikla i bicikala najnovije generacije. Certificirana prema SAE J1703/J1704 · ISO 4925 (klase 3/4/5.1).',
    'price'   => '11.33',
    'sku'     => '9030386',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/liquido-de-freno-gro-51-500ml.jpg?v=1726867239',
  ],
  [
    'title'   => 'GRO MAZIVO ZA LANAC (270 ML)',
    'desc'    => 'Mazivo posebno formulirano za sve vrste lanaca, uključujući lance sa zaptivačima, štiteći ih u najtežim uvjetima eksploatacije. Posebni aditivi pružaju znatno veću otpornost mazivog filma i duži vijek trajanja lanca. 270 ml.',
    'price'   => '9.78',
    'sku'     => '5091199',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/spray-smar-do-lancucha-gro-chain-lube-500-ml_1.webp?v=1726867328',
  ],
  [
    'title'   => 'GRO MAZIVO ZA LANAC (650 ML)',
    'desc'    => 'Mazivo posebno formulirano za sve vrste lanaca, uključujući lance sa zaptivačima, štiteći ih u najtežim uvjetima eksploatacije. Posebni aditivi pružaju znatno veću otpornost mazivog filma i duži vijek trajanja lanca. 650 ml.',
    'price'   => '16.48',
    'sku'     => '5091198',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/spray-smar-do-lancucha-gro-chain-lube-500-ml_1_71e1c42a-be55-4784-b2f7-84d43c8e3e64.webp?v=1726867382',
  ],
  [
    'title'   => 'GRO SPREJ ZA ČIŠĆENJE KONTAKATA (650 ML)',
    'desc'    => 'Snažno i učinkovito sredstvo za odmašćivanje koje uklanja prljavštinu (prašina, pijesak, zemlja, ulje i mast). Posebno razvijeno za čišćenje metalnih površina i prijenosa s O-ring, X-ring ili Z-ring zaptivačima. 650 ml.',
    'price'   => '7.97',
    'sku'     => '5091598',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/722-915-98.jpg?v=1726867455',
  ],
  [
    'title'   => 'GRO SREDSTVO ZA ČIŠĆENJE FILTERA (5L)',
    'desc'    => 'Sredstvo za čišćenje pjenastih filtara zraka niske agresivnosti prema pjeni i gumenim dijelovima filtera. Učinkovito uklanja ulje, blato i nečistoće, poboljšavajući filtraciju i produljujući vijek trajanja filtera. 5 litara.',
    'price'   => '44.29',
    'sku'     => '5073373',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/51aiZtAUCuL.jpg?v=1726902860',
  ],
  [
    'title'   => 'GRO SPREJ ZA PJENASTI FILTER (650 ML)',
    'desc'    => 'Sprej mazivo koje pjenastim filtrima zraka pruža superiorne karakteristike filtracije za potpune motorske performanse. Štiti unutarnje komponente od kontaminacije, primjenjivo na stare i nove filtere. 650 ml.',
    'price'   => '11.33',
    'sku'     => '5091298',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/spray-do-nasaczania-filtrow-gabkowych-gro-foam-filter-500-ml_1_m2.webp?v=1726902937',
  ],
  [
    'title'   => 'GRO ULJE ZA VILICU SAE 10W',
    'desc'    => 'Sintetičko ulje za vilice projektirano za moderne sustave ovjesa Kayaba, Showa, Ohlins, WP i ostalih. Ekskluzivni aditivi štite zglobove i produljuju njihov vijek trajanja. Viskoznost SAE 10W, 1 litar.',
    'price'   => '13.40',
    'sku'     => '2026281',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/gro-sae-10-fork-oil-1-liter.jpg?v=1726903067',
  ],
  [
    'title'   => 'GRO ULJE ZA VILICU SAE 2.5W',
    'desc'    => 'Sintetičko ulje za vilice projektirano za moderne sustave ovjesa Kayaba, Showa, Ohlins, WP i ostalih. Ekskluzivni aditivi štite zglobove i produljuju njihov vijek trajanja. Viskoznost SAE 2.5W, 1 litar.',
    'price'   => '13.40',
    'sku'     => '2026081',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/8308_Fork_Fluid_2_5W_1L_Baixa_800x_jpg.webp?v=1726903204',
  ],
  [
    'title'   => 'GRO ULJE ZA VILICU SAE 7.5W',
    'desc'    => 'Sintetičko ulje za vilice projektirano za moderne sustave ovjesa Kayaba, Showa, Ohlins, WP i ostalih. Ekskluzivni aditivi štite zglobove i produljuju njihov vijek trajanja. Viskoznost SAE 7.5W, 1 litar.',
    'price'   => '13.40',
    'sku'     => '2026681',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/images-2.jpg?v=1726903380',
  ],
  [
    'title'   => 'GRO GLOBAL RACING 10W50',
    'desc'    => '100% sintetičko mazivo s tri-esterskom tehnologijom razvijenom za natjecanja, za sve vrste 4-taktnih motociklističkih motora s integriranim ili odvojenim mjenjačem i mokrim ili suhim kvačilom. Izvrsno za visoko-okretajne motore. 10W50, 1 litar.',
    'price'   => '18.53',
    'sku'     => '9007481',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/8332_Racing_10w50_1L_Baixa_800x_jpg.webp?v=1726903524',
  ],
  [
    'title'   => 'GRO GLOBAL SMART 15W50',
    'desc'    => 'Sintetičko mazivo za sve vrste 4-taktnih motociklističkih motora s integriranim ili odvojenim mjenjačem i mokrim ili suhim kvačilom. Namijenjeno motorima koji koriste viskoviskozna maziva prema Euro 2, 3, 4 standardima. 15W50, 1 litar.',
    'price'   => '13.20',
    'sku'     => '9021890',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/8328_Smart_15W50_1L_Baixa_800x_jpg.webp?v=1726903643',
  ],
  [
    'title'   => 'GRO GLOBAL ULTRA 5',
    'desc'    => 'Global Ultra 5 formuliran je s visokokvalitetnim parafinskim uljima i odabranim aditivima. Ekonomično mazivo za redovito servisiranje motocikla uz pouzdanu zaštitu motora.',
    'price'   => '11.33',
    'sku'     => '1100986',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/GRO_GLOBALULTRA5_1100986-1000x1000h.jpg?v=1726904112',
  ],
  [
    'title'   => 'GRO ŠAMPON ZA PRANJE MOTOCIKLA',
    'desc'    => 'Tekući deterdžent za izravnu upotrebu koji omogućuje jednostavno uklanjanje sve vrste nečistoće s svih dijelova motocikla. Izvrsna moć čišćenja uz minimalni napor, ne ostavlja tragove. 1 litar.',
    'price'   => '15.45',
    'sku'     => '5073081',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/szampon-do-mycia-motocykla-atomizer-gro-global-wash-shampoo-1l_1.webp?v=1726904191',
  ],
  [
    'title'   => 'GRO KART-2',
    'desc'    => 'Iznimno posebno 100% sintetičko mazivo za 2-taktne benzinske motore. Pruža visoku otpornost mazivog filma i odlično podnošenje opterećenja. Sintetička struktura osigurava maksimalnu zaštitu u ekstremnim uvjetima rada. 1 litar.',
    'price'   => '21.18',
    'sku'     => '9020581',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/8316_KART_2_2T_1L_Baixa_800x_jpg.webp?v=1726914799',
  ],
  [
    'title'   => 'GRO PERFORMANCE 2T OFF ROAD',
    'desc'    => '100% sintetičko mazivo na bazi estera, posebno razvijeno za cross, enduro i trial motore. Garantira maksimalnu zaštitu u teškim natjecateljskim uvjetima uz minimalnu emisiju dima. Idealno za zahtjevne terenske uvjete. 1 litar.',
    'price'   => '25.74',
    'sku'     => '9020390',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/8431_Performance_Off_Road_1L_Baix_600x600_crop_center_jpg.webp?v=1726914944',
  ],
  [
    'title'   => 'GRO RACING 10W50',
    'desc'    => '100% sintetičko mazivo s tri-esterskom tehnologijom razvijenom za natjecanja za sve vrste 4-taktnih motociklističkih motora. Idealno za motore koji rade pri visokim temperaturama i ekstremnim opterećenjima. 10W50, 1 litar.',
    'price'   => '20.85',
    'sku'     => '9007490',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/aceite-gro-global-racing-4t-10w50-1-litro.jpg?v=1726915010',
  ],
  [
    'title'   => 'GRO SPREJ ZA UKLANJANJE HRĐE',
    'desc'    => 'Sprej mazivo visoke penetracije s višestrukim primjenama. Odmašćuje, otpušta zahrđale vijke i zatike, štiti metalne površine od korozije i vlage. Nezaobilazan alat za servisiranje motocikla. 500 ml.',
    'price'   => '9.27',
    'sku'     => '5090799',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/634f07f78b788c734029b145-gro-rust-penetrant-500ml-spray.jpg?v=1726915167',
  ],
  [
    'title'   => 'GRO SILIKONSKI SPREJ PLUS',
    'desc'    => 'Silikonski sprej posebno formuliran za maksimalne performanse u svim primjenama. Ultra-fini raspršivač omogućuje preciznu kontrolu pri nanošenju. Idealan za gumene dijelove, plastiku, metal i vinilne površine.',
    'price'   => '12.36',
    'sku'     => '5091899',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/GROSILICONPLUSSPRAY-198x542.jpg?v=1726915235',
  ],
  [
    'title'   => 'GRO SYNT-10 2T',
    'desc'    => '100% sintetičko mazivo za 2-taktne motore koje pruža visoku čvrstoću mazivog filma i odlična svojstva podnošenja opterećenja. Sintetička struktura osigurava maksimalnu zaštitu u svim uvjetima rada motora. 1 litar.',
    'price'   => '15.35',
    'sku'     => '9022181',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/8340_Synt10_2t_1L_Baixa_500x_jpg.webp?v=1726865828',
  ],
  [
    'title'   => 'GRO TRANS EXTREM 75W',
    'desc'    => '100% sintetičko transmisijsko ulje za visoka natjecanja u najtežim uvjetima trošenja i visokih temperatura. Visoka moć podmazivanja, EP svojstva i izvrsna otpornost na smicanje za maksimalnu zaštitu prijenosa. 75W, 1 litar.',
    'price'   => '28.33',
    'sku'     => '1039490',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/8434_TransExtrem_75W_1L_Baixa_900x_jpg.webp?v=1726915340',
  ],
  [
    'title'   => 'GRO TRANS SYNT 10W40',
    'desc'    => 'Visokokvalitetno transmisijsko ulje posebno dizajnirano za podmazivanje kvačila i mjenjača. Nudi vrhunsku zaštitu prijenosa i kardanske osovine uz optimalne karakteristike u svim radnim temperaturama. 10W40, 1 litar.',
    'price'   => '16.47',
    'sku'     => '1035890',
    'image'   => 'https://cdn.shopify.com/s/files/1/0737/8164/1483/files/8436_TransSynt_10W40_1L_Baixa_600x_jpg.webp?v=1726915441',
  ],
];

// ── Page ───────────────────────────────────────────────────────────────────────

$index    = max( 0, (int)( $_GET['index'] ?? 0 ) );
$total    = count( $products );
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?pass=silvester2024';

function oil_html( $body, $refresh = '', $delay = 2 ) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Oil Import</title>';
    if ( $refresh ) echo '<meta http-equiv="refresh" content="'.$delay.';url='.htmlspecialchars($refresh).'">';
    echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}h2{color:#fff;}a{color:#2196f3;}
    .ok{color:#4caf50;}.skip{color:#888;}.upd{color:#2196f3;}.fail{color:#f44336;}
    .bar-wrap{background:#333;border-radius:4px;height:18px;width:100%;max-width:500px;margin:10px 0;}
    .bar-fill{background:#4caf50;height:18px;border-radius:4px;}</style></head>
    <body><h2>Ulje i maziva — Import</h2>'.$body.'</body></html>';
}

if ( $index >= $total ) {
    oil_html( '<p class="ok" style="font-size:18px">✓ Svih '.$total.' proizvoda uvezeno!</p><p><a href="/wp-admin/edit.php?post_type=product">Pregled proizvoda →</a></p>' );
    exit;
}

$d        = $products[ $index ];
$next_url = $base_url . '&index=' . ( $index + 1 );
$pct      = round( ( ( $index + 1 ) / $total ) * 100 );

$cat_id = oil_cat( 'Ulje i maziva' );

// Find existing by SKU or title
$pid  = $d['sku'] ? wc_get_product_id_by_sku( $d['sku'] ) : 0;
$mode = 'create';
if ( ! $pid ) {
    $q = new WP_Query( [ 'post_type'=>'product','title'=>$d['title'],'posts_per_page'=>1,'no_found_rows'=>true,'fields'=>'ids' ] );
    if ( $q->have_posts() ) { $pid = $q->posts[0]; $mode = 'update'; }
}

$product = $pid ? wc_get_product( $pid ) : new WC_Product_Simple();
$product->set_name( $d['title'] );
$product->set_description( $d['desc'] );
$product->set_regular_price( $d['price'] );
$product->set_sku( $d['sku'] );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product->set_manage_stock( false );
$product->set_stock_status( 'instock' );
$product->set_category_ids( array_filter( [ $cat_id ] ) );
$pid = $product->save();

$img_note = '';
if ( $pid && ! is_wp_error( $pid ) ) {
    if ( $d['image'] && ( $mode === 'create' || ! has_post_thumbnail( $pid ) ) ) {
        $aid = oil_attach_image( $d['image'], $pid );
        if ( $aid ) { set_post_thumbnail( $pid, $aid ); $img_note = ' img✓'; }
        else $img_note = ' img✗';
    }
    $cls = $mode === 'update' ? 'upd' : 'ok';
    $msg = '['.strtoupper($mode).'] ID:'.$pid.' SKU:'.$d['sku'].$img_note;
} else {
    $cls = 'fail';
    $msg = 'Save failed';
}

$bar = '<div class="bar-wrap"><div class="bar-fill" style="width:'.$pct.'%"></div></div><p>'.($index+1).'/'.$total.' ('.$pct.'%)</p>';
oil_html( $bar.'<p class="'.$cls.'">'.esc_html($d['title']).' — '.esc_html($msg).'</p>', $next_url );
