<?php
/**
 * Browser-based Sherco OEM product importer — one product per page load.
 * Access: http://silvester-shop.test/do-import-sherco.php?pass=silvester2024
 * Delete this file when done!
 */

define( 'SHERCO_PASS', 'silvester2024' );
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== SHERCO_PASS ) {
    die( 'Access denied. Add ?pass=silvester2024 to the URL.' );
}

require_once __DIR__ . '/wp-load.php';

// ── Helpers ────────────────────────────────────────────────────────────────────

function sherco_get_or_create_category( $name, $parent_id = 0 ) {
    $term = get_term_by( 'name', $name, 'product_cat' );
    if ( $term ) return $term->term_id;
    $result = wp_insert_term( $name, 'product_cat', [ 'parent' => $parent_id ] );
    return is_wp_error( $result ) ? 0 : $result['term_id'];
}

// ── Product data ───────────────────────────────────────────────────────────────

$products = [
  ['title'=>'SHERCO AXP ZAŠTITNA PLOČA SE 2014+','description'=>'Originalna Sherco OEM zaštitna ploča motora za SE modele 2014 i novije. Pruža zaštitu kućišta motora i donjeg dijela okvira od kamenja i ostataka s terena.','price'=>'188.49','sku'=>'5548'],
  ['title'=>'SHERCO LEŽAJ HK 16202RS','description'=>'Originalni Sherco OEM ležaj HK 16202RS. Izrađen prema tvorničkim specifikacijama za pouzdano funkcioniranje i dugi vijek trajanja.','price'=>'9.17','sku'=>'C167'],
  ['title'=>'SHERCO ŽARULJA PREDNJEG SVJETLA','description'=>'Originalna Sherco OEM žarulja za prednje svjetlo. Zamjenski dio prema tvorničkim specifikacijama za optimalnu vidljivost.','price'=>'14.31','sku'=>'C403'],
  ['title'=>'SHERCO BRTVA CENTRALNOG KUĆIŠTA','description'=>'Originalna Sherco OEM brtva centralnog kućišta motora. Sprječava curenje ulja i osigurava hermetičnost motora.','price'=>'31.42','sku'=>'3134'],
  ['title'=>'SHERCO POKLOPAC KVAČILA TR','description'=>'Originalni Sherco OEM poklopac kvačila za Trial modele. Štiti mehanizam kvačila i osigurava pravilno funkcioniranje.','price'=>'40.21','sku'=>'3131'],
  ['title'=>'SHERCO BRTVA KVAČILA','description'=>'Originalna Sherco OEM brtva kvačila. Sprječava curenje ulja iz kućišta kvačila i osigurava hermetičnost.','price'=>'25.13','sku'=>'3135'],
  ['title'=>'SHERCO UPRAVLJAČKI MOTOR','description'=>'Originalni Sherco OEM upravljački motor. Zamjenski komponent prema tvorničkim specifikacijama za optimalne performanse.','price'=>'77.91','sku'=>'3508'],
  ['title'=>'SHERCO SPOJNICA LANCA','description'=>'Originalna Sherco OEM spojnica lanca. Omogućuje spajanje i rastavljanje pogonskog lanca uz zadržavanje pune čvrstoće.','price'=>'18.85','sku'=>'R473'],
  ['title'=>'SHERCO BRTVA CILINDRA','description'=>'Originalna Sherco OEM brtva cilindra. Osigurava hermetičnost između cilindra i glave cilindra za optimalne kompresijske performanse.','price'=>'10.30','sku'=>'3025'],
  ['title'=>'SHERCO GLAVA CILINDRA','description'=>'Originalna Sherco OEM glava cilindra. Ključni motorni komponent izrađen prema tvorničkim tolerancijama za maksimalne performanse.','price'=>'138.23','sku'=>'4722'],
  ['title'=>'SHERCO CIJEV GLAVE CILINDRA 250/300 2T','description'=>'Originalna Sherco OEM cijev glave cilindra za 250/300cc 2-taktne modele. Osigurava učinkoviti protok rashladne tekućine i optimalne temperature rada.','price'=>'37.70','sku'=>'4723'],
  ['title'=>'SHERCO KUPOLA 300 ENDURO 2T','description'=>'Originalna Sherco OEM kupola za 300cc Enduro 2-taktne modele. Visoko precizni komponent koji optimizira kompresijski omjer i performanse motora.','price'=>'152.05','sku'=>'5034'],
  ['title'=>'SHERCO ZAŠTITNI POKLOPAC PJENASTOG FILTERA','description'=>'Originalni Sherco OEM zaštitni poklopac pjenastog filtera zraka. Štiti filter od prašine i prljavštine u zahtjevnim terenskim uvjetima.','price'=>'35.18','sku'=>'3956'],
  ['title'=>'SHERCO KAVEZ FILTERA','description'=>'Originalni Sherco OEM kavez filtera zraka. Pruža strukturalnu potporu filteru zraka i osigurava pravilno brtvljenje usisnog sustava.','price'=>'8.80','sku'=>'3044'],
  ['title'=>'SHERCO PJENASTA OBLOGA UPRAVLJAČA','description'=>'Originalna Sherco OEM pjenasta obloga upravljača. Pruža zaštitu i udobnost pri vožnji, amortizira udarce i smanjuje vibracije.','price'=>'10.30','sku'=>'1270'],
  ['title'=>'SHERCO BRTVA ULJA VILICE','description'=>'Originalna Sherco OEM brtva ulja prednje vilice. Sprječava curenje hidrauličnog ulja iz vilice i osigurava optimalno prigušenje.','price'=>'18.85','sku'=>'R070'],
  ['title'=>'SHERCO ZAŠTITA VILICE','description'=>'Originalna Sherco OEM zaštita cijevi prednje vilice. Štiti površinu vilice od ogrebotina i oštećenja u zahtjevnim terenskim uvjetima.','price'=>'18.85','sku'=>'2047'],
  ['title'=>'SHERCO ELEKTRIČNI KONTAKT PREDNJE KOČNICE','description'=>'Originalni Sherco OEM električni kontakt prednje kočnice. Aktivira svjetlo kočnice pri pritiskanju ručice prednje kočnice.','price'=>'10.30','sku'=>'R207'],
  ['title'=>'SHERCO PREDNJE SVJETLO','description'=>'Originalno Sherco OEM prednje svjetlo. Zamjenski sklop prednjeg fara prema tvorničkim specifikacijama za optimalnu vidljivost.','price'=>'31.42','sku'=>'0361'],
  ['title'=>'SHERCO PREDNJI BLATOBRAN','description'=>'Originalni Sherco OEM prednji blatobran. Izrađen od visokokvalitetne plastike prema tvorničkim specifikacijama za savršeno pristajanje.','price'=>'31.42','sku'=>'3161'],
  ['title'=>'SHERCO RUKAVICE (1)','description'=>'Originalne Sherco rukavice. Pružaju zaštitu i udobnost pri vožnji motociklom.','price'=>'43.98','sku'=>'S1008-S'],
  ['title'=>'SHERCO RUKAVICE (2)','description'=>'Originalne Sherco rukavice. Pružaju zaštitu i udobnost pri vožnji motociklom.','price'=>'43.98','sku'=>'S1008-XS'],
  ['title'=>'SHERCO ZATIK 3.2','description'=>'Originalni Sherco OEM zatik promjera 3.2mm. Standardni vezni element za spajanje različitih komponenti motocikla.','price'=>'5.03','sku'=>'0240'],
  ['title'=>'SHERCO KAČKET','description'=>'Originalni Sherco kačket. Kvalitetni promocijski kačket s Sherco logom.','price'=>'25.13','sku'=>'S8500'],
  ['title'=>'SHERCO CRIJEVO','description'=>'Originalno Sherco OEM crijevo rashladnog sustava. Osigurava učinkoviti protok rashladne tekućine i sprječava pregrijavanje motora.','price'=>'62.83','sku'=>'C388'],
  ['title'=>'SHERCO POKLOPAC PALJENJA 250 ENDURO','description'=>'Originalni Sherco OEM poklopac paljenja za 250cc Enduro modele. Štiti sklop paljenja od oštećenja i prašine.','price'=>'100.53','sku'=>'4383'],
  ['title'=>'SHERCO KIT VENTILATORA','description'=>'Originalni Sherco OEM kit ventilatora rashladnog sustava. Sprječava pregrijavanje motora u zahtjevnim uvjetima vožnje.','price'=>'226.19','sku'=>'6202'],
  ['title'=>'SHERCO POLUGA BREMBO','description'=>'Originalna Sherco OEM poluga Brembo kočnice/kvačila. Ergonomski dizajn za precizno upravljanje kočenjem ili kvačilom.','price'=>'37.70','sku'=>'3699'],
  ['title'=>'SHERCO KIT VIJAKA ZA FIKSIRANJE POLUGE','description'=>'Originalni Sherco OEM kit vijaka za fiksiranje upravljačke poluge. Osigurava čvrsto i pouzdano pričvršćivanje poluge na upravljač.','price'=>'13.40','sku'=>'4649'],
  ['title'=>'SHERCO ZAŠTITA KINEMATIKE','description'=>'Originalna Sherco OEM zaštita kinematike stražnjeg ovjesa. Štiti pivotne točke i komponente kinematike od kamenja i prljavštine.','price'=>'201.06','sku'=>'MM009'],
  ['title'=>'SHERCO FILTER KOLEKTORA KARBURATORA','description'=>'Originalni Sherco OEM filter kolektora karburatora. Sprječava ulaz nečistoća u karburarator i osigurava čistu mješavinu goriva i zraka.','price'=>'10.05','sku'=>'M224'],
  ['title'=>'SHERCO NIPLA','description'=>'Originalna Sherco OEM nipla. Standardni vezni element za spajanje crijeva i cijevi hidrauličnih i rashladnih sustava.','price'=>'3.77','sku'=>'R067'],
  ['title'=>'SHERCO MATICA S KLICOM','description'=>'Originalna Sherco OEM matica s klicom (nut clip). Osigurava sigurno i pouzdano pričvršćivanje komponenti bez mogućnosti samorazvijanja.','price'=>'6.28','sku'=>'M308'],
  ['title'=>'SHERCO O-PRSTEN','description'=>'Originalni Sherco OEM O-prsten. Standardni brtveni element za sprječavanje curenja tekućina i plinova na spojevima.','price'=>'1.89','sku'=>'5153'],
  ['title'=>'SHERCO O-PRSTEN 10X2.5','description'=>'Originalni Sherco OEM O-prsten dimenzija 10x2.5mm. Brtveni element za sprječavanje curenja tekućina na spojevima.','price'=>'2.51','sku'=>'M162'],
  ['title'=>'SHERCO O-PRSTEN 41X1,78','description'=>'Originalni Sherco OEM O-prsten dimenzija 41x1.78mm. Brtveni element za sprječavanje curenja tekućina na spojevima.','price'=>'3.77','sku'=>'0118'],
  ['title'=>'SHERCO O-PRSTEN VILICE','description'=>'Originalni Sherco OEM O-prsten prednje vilice. Osigurava brtvljenje unutar sklopa vilice i sprječava curenje ulja.','price'=>'4.40','sku'=>'R096'],
  ['title'=>'SHERCO O-PRSTEN POKLOPCA PALJENJA 150X2','description'=>'Originalni Sherco OEM O-prsten poklopca paljenja dimenzija 150x2mm. Brtveni element koji sprječava curenje ulja ispod poklopca.','price'=>'13.82','sku'=>'3158'],
  ['title'=>'SHERCO O-PRSTEN POKLOPCA VODENE PUMPE 37X2','description'=>'Originalni Sherco OEM O-prsten poklopca vodene pumpe dimenzija 37x2mm. Sprječava curenje rashladne tekućine.','price'=>'1.63','sku'=>'M033'],
  ['title'=>'SHERCO FILTER ULJA ENDURO','description'=>'Originalni Sherco OEM filter ulja za Enduro modele. Uklanja nečistoće iz motornog ulja i produžuje vijek trajanja motora.','price'=>'37.70','sku'=>'0116'],
  ['title'=>'SHERCO PLASTIČNI ČEP','description'=>'Originalni Sherco OEM plastični čep. Zatvorni element za otvore na karoseriji i kućištu koji sprječava ulaz prljavštine.','price'=>'16.34','sku'=>'C309'],
  ['title'=>'SHERCO ZAŠTITA LANČANIKA','description'=>'Originalna Sherco OEM zaštita prednjeg lančanika. Sprječava nagomilavanje prljavštine oko lančanika i štiti pogonski lanac.','price'=>'8.80','sku'=>'2026'],
  ['title'=>'SHERCO STRAŽNJA NIPLA ŽBICE','description'=>'Originalna Sherco OEM stražnja nipla žbice. Osigurava pravilnu napetost žbica stražnjeg kotača i točnost centriranja.','price'=>'4.40','sku'=>'3395'],
  ['title'=>'SHERCO PRSTEN POKLOPCA VODENE PUMPE','description'=>'Originalni Sherco OEM prsten poklopca vodene pumpe. Osigurava hermetičnost poklopca i sprječava curenje rashladne tekućine.','price'=>'5.03','sku'=>'3133'],
  ['title'=>'SHERCO VIJAK BHC 6X10','description'=>'Originalni Sherco OEM vijak BHC M6x10mm. Standardni vijak s unutarnjim šesterokutom za montažu komponenti motocikla.','price'=>'0.88','sku'=>'M282'],
  ['title'=>'SHERCO VIJAK BHC 6X12','description'=>'Originalni Sherco OEM vijak BHC M6x12mm. Standardni vijak s unutarnjim šesterokutom za montažu komponenti motocikla.','price'=>'1.20','sku'=>'M345'],
  ['title'=>'SHERCO VIJAK CHC 6X20','description'=>'Originalni Sherco OEM vijak CHC M6x20mm. Standardni vijak s unutarnjim šesterokutom za montažu komponenti motocikla.','price'=>'3.77','sku'=>'M249'],
  ['title'=>'SHERCO SKLOP POLUGE MJENJAČA CRNI','description'=>'Originalni Sherco OEM sklop poluge mjenjača u crnoj boji. Osigurava precizno i pouzdano mijenjanje brzina.','price'=>'31.42','sku'=>'5611'],
  ['title'=>'SHERCO BOČNA NOŽICA','description'=>'Originalna Sherco OEM bočna nožica. Stabilno oslonište motocikla u parkiranom položaju prema tvorničkim specifikacijama.','price'=>'12.57','sku'=>'C166'],
  ['title'=>'SHERCO SJEDALO RUKAVCA','description'=>'Originalno Sherco OEM sjedalo rukavca. Ležišni element koji osigurava pravilno pozicioniranje i klizanje rukavca.','price'=>'125.66','sku'=>'5782'],
  ['title'=>'SHERCO ŽBICA','description'=>'Originalna Sherco OEM žbica kotača. Izrađena od visokokvalitetnog čelika prema tvorničkim specifikacijama za pouzdanost i čvrstoću.','price'=>'6.28','sku'=>'0712'],
  ['title'=>'SHERCO GLAVA ŽBICE','description'=>'Originalna Sherco OEM glava žbice. Vezni element koji spaja žbicu s naplatkom i omogućuje podešavanje napetosti.','price'=>'3.77','sku'=>'0715'],
  ['title'=>'SHERCO MINI ŽBICA (1)','description'=>'Originalna Sherco OEM mini žbica za manje modele kotača. Izrađena prema tvorničkim specifikacijama za pouzdanost.','price'=>'5.03','sku'=>'3392'],
  ['title'=>'SHERCO MINI ŽBICA (2)','description'=>'Originalna Sherco OEM mini žbica za manje modele kotača. Izrađena prema tvorničkim specifikacijama za pouzdanost.','price'=>'4.40','sku'=>'3394'],
  ['title'=>'SHERCO STRAŽNJA ŽBICA','description'=>'Originalna Sherco OEM stražnja žbica kotača. Izrađena od visokokvalitetnog čelika prema tvorničkim specifikacijama.','price'=>'5.66','sku'=>'0713'],
  ['title'=>'SHERCO OPRUGA (1)','description'=>'Originalna Sherco OEM opruga. Zamjenski element opruge izrađen prema tvorničkim specifikacijama za optimalne performanse ovjesa ili kvačila.','price'=>'25.13','sku'=>'3387'],
  ['title'=>'SHERCO OPRUGA (2)','description'=>'Originalna Sherco OEM opruga. Zamjenski element opruge izrađen prema tvorničkim specifikacijama za optimalne performanse.','price'=>'1.89','sku'=>'0229'],
  ['title'=>'SHERCO PREKIDAČ MOTORA','description'=>'Originalni Sherco OEM prekidač za gašenje motora (kill switch). Omogućuje brzo i sigurno gašenje motora u nužnoj situaciji.','price'=>'50.84','sku'=>'3658'],
  ['title'=>'SHERCO HVATAČ GASA','description'=>'Originalni Sherco OEM hvatač gasa (sklop papučice gasa). Omogućuje precizno upravljanje gasom i osigurava povrat u nultu poziciju.','price'=>'100.53','sku'=>'1280'],
  ['title'=>'SHERCO BESCJEVASTI VENTIL','description'=>'Originalni Sherco OEM ventil za bescjevaste gume (tubeless). Omogućuje korištenje bescjevastih guma i olakšava punjenje zraka.','price'=>'43.98','sku'=>'R061'],
  ['title'=>'SHERCO LEŽAJ VODENE PUMPE','description'=>'Originalni Sherco OEM ležaj vodene pumpe. Osigurava glatki rad vodene pumpe i sprječava curenje rashladne tekućine.','price'=>'25.13','sku'=>'M027'],
  ['title'=>'SHERCO ČAHURA VODENE PUMPE 8X16X1','description'=>'Originalna Sherco OEM čahura vodene pumpe dimenzija 8x16x1mm. Klizni ležajni element koji osigurava pravilni rad osovine pumpe.','price'=>'2.57','sku'=>'M030'],
  ['title'=>'SHERCO IGLIČNI LEŽAJ VODENE PUMPE 608','description'=>'Originalni Sherco OEM iglični ležaj vodene pumpe 608. Visokoprecizni ležaj koji osigurava minimalno trenje i dugi vijek trajanja pumpe.','price'=>'18.85','sku'=>'M029'],
  ['title'=>'SHERCO WOODRUFF KLJUČ','description'=>'Originalni Sherco OEM Woodruff ključ (polu-uložak). Pozicionira zamašnjak/rotor paljenja na osovinu motora u pravilnom položaju za ispravno paljenje.','price'=>'18.85','sku'=>'M023'],
];

// ── Upload logo once, reuse attachment ID ─────────────────────────────────────

function sherco_get_logo_attachment_id() {
    $option_key = 'sherco_import_logo_id';
    $existing   = get_option( $option_key );
    if ( $existing && get_post( $existing ) ) return (int) $existing;

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $src_file   = get_template_directory() . '/images/sherco-logo.png';
    if ( ! file_exists( $src_file ) ) return 0;

    $upload_dir = wp_upload_dir();
    $dest_name  = 'sherco-logo.png';
    $dest_path  = $upload_dir['path'] . '/' . $dest_name;
    copy( $src_file, $dest_path );

    $attach_id = wp_insert_attachment( [
        'post_mime_type' => 'image/png',
        'post_title'     => 'Sherco Logo',
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $dest_path );

    if ( is_wp_error( $attach_id ) ) return 0;

    wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $dest_path ) );
    update_option( $option_key, $attach_id );
    return $attach_id;
}

// ── Page setup ─────────────────────────────────────────────────────────────────

$index    = max( 0, (int) ( $_GET['index'] ?? 0 ) );
$total    = count( $products );
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?pass=' . SHERCO_PASS;

function sherco_html( $content, $refresh_url = '', $delay = 1 ) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Sherco Import</title>';
    if ( $refresh_url ) echo '<meta http-equiv="refresh" content="' . $delay . ';url=' . htmlspecialchars( $refresh_url ) . '">';
    echo '<style>
        body{font-family:monospace;background:#111;color:#ccc;padding:20px;}
        h2{color:#fff;} a{color:#2196f3;}
        .ok{color:#4caf50;} .skip{color:#888;} .fail{color:#f44336;}
        .bar-wrap{background:#333;border-radius:4px;height:18px;width:100%;max-width:500px;margin:10px 0;}
        .bar-fill{background:#4caf50;height:18px;border-radius:4px;}
    </style></head><body><h2>Sherco OEM Import</h2>';
    echo $content;
    echo '</body></html>';
}

// Done
if ( $index >= $total ) {
    sherco_html( '<p class="ok" style="font-size:18px">✓ All ' . $total . ' Sherco products imported!</p><p><a href="/wp-admin/edit.php?post_type=product">View products →</a></p>' );
    exit;
}

// ── Process one product ────────────────────────────────────────────────────────

$data     = $products[ $index ];
$next_url = $base_url . '&index=' . ( $index + 1 );
$pct      = round( ( ( $index + 1 ) / $total ) * 100 );

// Get or create category hierarchy: OEM rezervni dijelovi → Sherco OEM
$parent_cat_id = sherco_get_or_create_category( 'OEM rezervni dijelovi' );
$cat_id        = sherco_get_or_create_category( 'Sherco OEM', $parent_cat_id );

$status = '';
$msg    = '';

// Check if already exists
$query = new WP_Query( [
    'post_type'      => 'product',
    'title'          => $data['title'],
    'posts_per_page' => 1,
    'no_found_rows'  => true,
    'fields'         => 'ids',
] );

if ( $query->have_posts() ) {
    // Update SKU on existing product if missing
    $pid     = $query->posts[0];
    $product = wc_get_product( $pid );
    if ( $product && ! $product->get_sku() && ! empty( $data['sku'] ) ) {
        $product->set_sku( $data['sku'] );
        $product->save();
        $status = 'updated';
        $msg    = 'SKU set: ' . $data['sku'];
    } else {
        $status = 'skip';
        $msg    = 'Already exists' . ( $product && $product->get_sku() ? ' (SKU: ' . $product->get_sku() . ')' : '' );
    }
} else {
    $product = new WC_Product_Simple();
    $product->set_name( $data['title'] );
    $product->set_description( $data['description'] );
    $product->set_regular_price( $data['price'] );
    if ( ! empty( $data['sku'] ) ) $product->set_sku( $data['sku'] );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'visible' );
    $product->set_manage_stock( false );
    $product->set_stock_status( 'instock' );
    if ( $cat_id ) $product->set_category_ids( [ $cat_id ] );
    $pid = $product->save();

    if ( $pid && ! is_wp_error( $pid ) ) {
        $logo_id = sherco_get_logo_attachment_id();
        if ( $logo_id ) set_post_thumbnail( $pid, $logo_id );
        $status = 'ok';
        $msg    = 'Created (ID: ' . $pid . ')' . ( $logo_id ? ', image set' : ', no logo found' );
    } else {
        $status = 'fail';
        $msg    = 'Save failed';
    }
}

$bar  = '<div class="bar-wrap"><div class="bar-fill" style="width:' . $pct . '%"></div></div>';
$bar .= '<p>' . ( $index + 1 ) . ' / ' . $total . ' (' . $pct . '%)</p>';
$line = '<p class="' . $status . '">[' . strtoupper( $status ) . '] ' . esc_html( $data['title'] ) . ' — ' . esc_html( $msg ) . '</p>';

sherco_html( $bar . $line, $next_url );
