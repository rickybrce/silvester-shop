<?php
/**
 * Browser-based product importer — one product per page load, auto-advances.
 * Access via: http://silvester-shop.test/do-import.php
 * Delete this file when done!
 */

// Basic protection — change this password
define( 'IMPORT_PASS', 'silvester2024' );

if ( empty( $_GET['pass'] ) || $_GET['pass'] !== IMPORT_PASS ) {
    die( 'Access denied. Add ?pass=silvester2024 to the URL.' );
}

require_once __DIR__ . '/wp-load.php';

// ─── HELPERS ──────────────────────────────────────────────────────────────────

function silvester_get_or_create_category( $name, $parent_id = 0 ) {
    $term = get_term_by( 'name', $name, 'product_cat' );
    if ( $term ) return $term->term_id;
    $result = wp_insert_term( $name, 'product_cat', [ 'parent' => $parent_id ] );
    return is_wp_error( $result ) ? 0 : $result['term_id'];
}

function silvester_attach_image( $image_url, $post_id ) {
    $upload_dir = wp_upload_dir();
    $filename   = $post_id . '_' . basename( parse_url( $image_url, PHP_URL_PATH ) );
    $file_path  = $upload_dir['path'] . '/' . $filename;

    if ( ! file_exists( $file_path ) || filesize( $file_path ) < 100 ) {
        $attempts = 0;
        $body = '';
        while ( $attempts < 3 ) {
            $response = wp_remote_get( $image_url, [ 'timeout' => 20, 'user-agent' => 'Mozilla/5.0' ] );
            if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $body = wp_remote_retrieve_body( $response );
                break;
            }
            $attempts++;
            sleep(1);
        }
        if ( strlen( $body ) < 100 ) return [ 'ok' => false, 'msg' => "Failed to download: $image_url" ];
        file_put_contents( $file_path, $body );
    }

    $wp_filetype = wp_check_filetype( $filename );
    if ( ! $wp_filetype['type'] ) return [ 'ok' => false, 'msg' => "Unknown filetype: $filename" ];

    $attach_id = wp_insert_attachment( [
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $file_path, $post_id );

    if ( is_wp_error( $attach_id ) ) return [ 'ok' => false, 'msg' => $attach_id->get_error_message() ];

    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $file_path ) );
    return [ 'ok' => true, 'id' => $attach_id ];
}

function silvester_process_product( $data ) {
    $logs = [];

    $query = new WP_Query( [
        'post_type'              => 'product',
        'title'                  => $data['title'],
        'posts_per_page'         => 1,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
    ] );

    if ( $query->have_posts() ) {
        $pid       = $query->posts[0]->ID;
        $has_thumb = has_post_thumbnail( $pid );
        $has_gal   = (bool) get_post_meta( $pid, '_product_image_gallery', true );

        if ( $has_thumb && ( count( $data['images'] ) <= 1 || $has_gal ) ) {
            return [ 'status' => 'skipped', 'msg' => 'Already has images', 'logs' => [] ];
        }

        // Attach missing images
        if ( ! $has_thumb && ! empty( $data['images'] ) ) {
            $res = silvester_attach_image( $data['images'][0], $pid );
            if ( $res['ok'] ) { set_post_thumbnail( $pid, $res['id'] ); $logs[] = 'Featured image added'; }
            else $logs[] = 'Featured: ' . $res['msg'];
        }
        if ( ! $has_gal && count( $data['images'] ) > 1 ) {
            $gallery_ids = [];
            foreach ( array_slice( $data['images'], 1 ) as $url ) {
                $res = silvester_attach_image( $url, $pid );
                if ( $res['ok'] ) $gallery_ids[] = $res['id'];
                else $logs[] = 'Gallery: ' . $res['msg'];
            }
            if ( $gallery_ids ) update_post_meta( $pid, '_product_image_gallery', implode( ',', $gallery_ids ) );
        }
        return [ 'status' => 'updated', 'msg' => 'Images updated', 'logs' => $logs ];
    }

    // Create new product
    $product = new WC_Product_Simple();
    $product->set_name( $data['title'] );
    $product->set_description( $data['description'] );
    $product->set_regular_price( $data['price'] );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'visible' );
    $product->set_manage_stock( false );
    $product->set_stock_status( 'instock' );

    $cat_ids = [];
    if ( ! empty( $data['type'] ) ) $cat_ids[] = silvester_get_or_create_category( $data['type'] );
    if ( ! empty( $data['tag'] ) )  $cat_ids[] = silvester_get_or_create_category( $data['tag'], $cat_ids[0] ?? 0 );
    $cat_ids = array_filter( array_unique( $cat_ids ) );
    if ( $cat_ids ) $product->set_category_ids( $cat_ids );

    $pid = $product->save();

    if ( ! empty( $data['images'] ) ) {
        $res = silvester_attach_image( $data['images'][0], $pid );
        if ( $res['ok'] ) { set_post_thumbnail( $pid, $res['id'] ); $logs[] = 'Featured image added'; }
        else $logs[] = 'Featured: ' . $res['msg'];

        if ( count( $data['images'] ) > 1 ) {
            $gallery_ids = [];
            foreach ( array_slice( $data['images'], 1 ) as $url ) {
                $res = silvester_attach_image( $url, $pid );
                if ( $res['ok'] ) $gallery_ids[] = $res['id'];
                else $logs[] = 'Gallery: ' . $res['msg'];
            }
            if ( $gallery_ids ) update_post_meta( $pid, '_product_image_gallery', implode( ',', $gallery_ids ) );
        }
    }

    return [ 'status' => 'created', 'msg' => "Created (ID: $pid)", 'logs' => $logs ];
}

// ─── PRODUCT DATA ─────────────────────────────────────────────────────────────
$products = [
  ['title'=>'BETA PODEŠAVAČ OPRUGE (KYB 2T/4T RR RACING)','description'=>'Regulator opruge amortizera KYB za 2T/4T RR racing. Omogućuje brzo i sigurno podešavanje prednapetosti opruge pomoću jednostavnog ključa, bez gubitka vremena na terenu.','price'=>'154.44','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_eb7ba7c0-ca15-4f9d-9111-b55c230a3542.jpg?v=1774777563']],
  ['title'=>'BETA PODEŠAVAČ OPRUGE (RR MY 19/23)','description'=>'Regulator opruge amortizera za 2T/4T RR MY 19/23. Omogućuje brzo i sigurno podešavanje prednapetosti opruge pomoću jednostavnog ključa.','price'=>'160.03','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f02ba9ff-83a7-47c0-bd5e-d3ce8cd76faa.jpg?v=1774777356']],
  ['title'=>'BETA ALP 200 ZAŠTITA MOTORA','description'=>'Originalni Beta OEM dio karoserije za ALP 200, izrađen prema tvorničkim specifikacijama s točnim materijalima i montažnim točkama za savršeno pristajanje.','price'=>'107.54','type'=>'Alp','tag'=>'Zaštita okvira','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_90e6154d-144a-49c1-99e1-990eda6076b2.jpg?v=1774777226']],
  ['title'=>'BETA ALP 4.0 CRNI NOSAČ PRTLJAGE','description'=>'Crni nosač prtljage za modele ALP 4.0, pruža sigurno nošenje tereta za avanturističku vožnju.','price'=>'92.88','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_6ddca6a1-a32f-4606-be99-6e366872491b.jpg?v=1774777585']],
  ['title'=>'BETA ANODIZIRANI POKLOPAC KOČNICE/KVAČILA','description'=>'Originalni Beta OEM motor. komponent za racing i enduro modele. Neophodan za optimalne performanse i preventivno održavanje.','price'=>'41.81','type'=>'Enduro','tag'=>'Poklopci cilindra','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e01b5512-f573-49d0-9c53-85134bcdc14f.jpg?v=1774777241']],
  ['title'=>'BETA PROTUKLIZNA PRESVLAKA SJEDALA RR END./MOT. 50','description'=>'Protuklizna presvlaka sjedala za modele Enduro/Motard 50cc koja poboljšava prianjanje vozača i udobnost pri dinamičnoj vožnji.','price'=>'113.09','type'=>'Enduro/Motard','tag'=>'Presvlake sjedala','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_38779d1f-b125-47ce-9c26-1e198d50277b.jpg?v=1774777243']],
  ['title'=>'BETA SET NOSAČA PRTLJAGE - KIT ALP 4.0','description'=>'Set nosača prtljage za Beta ALP uključuje vijke za montažu i parove distancera za ugradnju sa ili bez nosača registarske pločice. Kompatibilan sa stražnjim torbama i gornjim koferima.','price'=>'121.39','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_189d3565-46da-4134-a2ea-2135009f3da8.jpg?v=1774777710']],
  ['title'=>'BETA NOSAČ PRTLJAGE ALP-URBAN 4T 125','description'=>'Originalni Beta OEM zamjenski dio izrađen prema tvorničkim specifikacijama za besprijekorno pristajanje na ALP-Urban modele.','price'=>'78.31','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_5f8aa0d2-f307-40d6-baaa-c853b968845c.jpg?v=1774777587']],
  ['title'=>'BETA SILIKONSKE CIJEVI HLAĐENJA FACTORY 2011','description'=>'Originalni Beta OEM dio sustava hlađenja koji osigurava učinkovit protok rashladne tekućine i optimalne radne temperature.','price'=>'66.47','type'=>'Trial','tag'=>'Vodene cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f6c062b0-f57b-415a-be8f-9d31f6a0c4dc.jpg?v=1774777210']],
  ['title'=>'BETA KIT ČELJUSTI KOČNICE','description'=>'Set izvlakača osovine kočnih pločica za brzu zamjenu. Natjecateljski specifičan dio koji omogućuje učinkovitu zamjenu kočnih pločica. Samo za utrke.','price'=>'33.54','type'=>'Enduro','tag'=>'Dijelovi čeljusti','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_031de8a1-9d21-4ada-9676-91f2b570ba71.jpg?v=1774777380']],
  ['title'=>'BETA KOČIONA PLOČA (STRAŽNJA)','description'=>'Stražnja puna valovita kočiona ploča debljine 4mm za 2T-4T RR/RX/XTRAINER. Samo za utrke.','price'=>'123.20','type'=>'Enduro','tag'=>'Kočione ploče','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b307df82-f3dc-4b48-b563-7a70b104e4a8.jpg?v=1774777504']],
  ['title'=>'BETA KOČIONA PLOČA EVO','description'=>'FIM odobrena kočiona ploča za trial natjecanja, pouzdana snaga kočenja s tvorničkom preciznošću.','price'=>'91.62','type'=>'Trial','tag'=>'Kočione ploče','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_42054dcf-95ba-4f9f-9ae6-c0492986aa3b.jpg?v=1774777187']],
  ['title'=>'BETA ZAŠTITA KOČIONE PLOČE EVO','description'=>'Originalni OEM dio kočionog sustava koji osigurava pouzdanu snagu kočenja s performansama prema tvorničkim specifikacijama.','price'=>'104.69','type'=>'Trial','tag'=>'Kočione ploče','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_aa97474f-5c59-4bf7-a76a-179ed4c38ab6.jpg?v=1774777184']],
  ['title'=>'BETA POKLOPAC ZRAČNOG FILTERA - KIT','description'=>'Poklopac kutije za filtriranje zraka koji pruža zaštitu i brtvljenje za sustav usisa zraka na enduro motociklima.','price'=>'35.92','type'=>'Enduro','tag'=>'Poklopci zračnog filtera','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_2bde5b7a-11f0-4819-8a23-912ae65a7051.jpg?v=1774777394']],
  ['title'=>'BETA CRVENI POKLOPAC PUMPE','description'=>'Crveni anodizirani poklopac pumpe kočionog rezervoara za RR STD/racing MY 15+, RX 2T i XTRAINER.','price'=>'24.13','type'=>'Enduro','tag'=>'Poklopci cilindra','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d90b84c1-3581-469b-9c2e-5d6f69847ab6.jpg?v=1774777638']],
  ['title'=>'BETA CRVENA VODILICA LANCA RR','description'=>'Kit vodilice lanca izrađen od posebne plastike s maksimalnom otpornošću na habanje. Osigurava pravilno poravnanje lanca u svim uvjetima rada.','price'=>'129.87','type'=>'Enduro','tag'=>'Vodilice lanca','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4a74edec-70f0-4d21-9906-7b29dac7eb37.jpg?v=1774777293']],
  ['title'=>'BETA CRNI ZATEZAČ LANCA - KIT EVO','description'=>'Originalni OEM pogonski komponent koji osigurava glatki prijenos snage s tvorničkim tolerancijama.','price'=>'46.93','type'=>'Trial','tag'=>'Zatezači lanca','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_605dea33-b27d-4ef8-98c4-20e23e6cf90f.jpg?v=1774777361']],
  ['title'=>'BETA CRVENI ZATEZAČ LANCA - KIT EVO','description'=>'Originalni OEM pogonski komponent izrađen prema tvorničkim tolerancijama za učinkoviti prijenos snage.','price'=>'46.93','type'=>'Trial','tag'=>'Zatezači lanca','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_768347c1-f29d-494e-a390-6794389ba13e.jpg?v=1774777365']],
  ['title'=>'BETA KVAČILO - KIT MY18-MY21','description'=>'Originalni OEM pogonski komponent s tvorničkim tolerancijama koji osigurava glatki prijenos snage.','price'=>'53.73','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b177ddd0-2898-404d-a1c7-56ac19adf88d.jpg?v=1774777508']],
  ['title'=>'BETA TIJELO GLAVNOG CILINDRA KVAČILA EVO 2T-REV 2T/4T','description'=>'Komponenta tijela glavnog cilindra kvačila za trial motocikle, projektirana prema tvorničkim specifikacijama.','price'=>'83.11','type'=>'Trial','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_15aff778-c1f3-4532-ba87-6bd3786aed68.jpg?v=1774777561']],
  ['title'=>'BETA ZAŠTITA SLAVE CILINDRA KVAČILA CRVENA RR','description'=>'Zaštita slave cilindra kvačila za 250/300 RR MY 20+ / 4T RR MY 20+. Crvena ergal zaštita s Beta logom.','price'=>'86.75','type'=>'Enduro','tag'=>'Zaštita okvira','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9810e619-84e5-49cc-a90b-454413827790.jpg?v=1774777263']],
  ['title'=>'BETA KOLEKTOR KIT','description'=>'Kolektor kit za motocikle od 250/300cc.','price'=>'598.31','type'=>'Trial','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0f7bf7c3-b479-4842-a40f-af6fe6f31cb0.jpg?v=1774777224']],
  ['title'=>'BETA CRVENI POKLOPAC ISPUŠNOG VENTILA','description'=>'Crveni anodizirani poklopac ispušnog ventila za RR 250/300 2T, RX 2T i XTRAINER modele.','price'=>'45.62','type'=>'Enduro','tag'=>'Poklopci cilindra','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c2d22225-3dc9-42bc-ad51-a4096ff9a117.jpg?v=1774777640']],
  ['title'=>'BETA BRTVA KUĆIŠTA CILINDRA','description'=>'Originalni Beta OEM komponent motora izrađen prema tvorničkim specifikacijama za vrhunske performanse.','price'=>'11.94','type'=>'Trial','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_19bb721e-f88e-4b4b-8c1a-490272743818.jpg?v=1774777582']],
  ['title'=>'BETA NALJEPNICE - PUNI SET - ENDURO RR 2T 50','description'=>'Puni set naljepnica za RR 2T 50 Racing MY20.','price'=>'121.21','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_190e15c1-c781-4994-8e43-0cb31fd251ea.jpg?v=1774777412']],
  ['title'=>'BETA NALJEPNICE - PUNI SET - KIT RR RACING MY20','description'=>'Puni set naljepnica za RR racing modele MY20.','price'=>'166.25','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_651ddb50-fc3d-41ec-a2c8-021608e1edaf.jpg?v=1774777419']],
  ['title'=>'BETA NALJEPNICE - PUNI SET EVO 2T FACTORY MY22','description'=>'Originalni Beta OEM set zamjenskih naljepnica za EVO 2T Factory MY22 modele.','price'=>'143.33','type'=>'Trial','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ab45a76d-b104-4313-b7cb-36758d16961b.jpg?v=1774777531']],
  ['title'=>'BETA NALJEPNICE - PUNI SET EVO FACTORY MY19','description'=>'Originalni Beta OEM set naljepnica za EVO Factory MY19.','price'=>'141.15','type'=>'Trial','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_07f69145-eb72-42c5-812c-994af0b82a60.jpg?v=1774777354']],
  ['title'=>'BETA NALJEPNICE - PUNI SET EVO FACTORY MY21','description'=>'Originalni Beta OEM set naljepnica za EVO Factory MY21.','price'=>'171.65','type'=>'Trial','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f12e978f-6710-4d4f-bf50-0c13447eb7c6.jpg?v=1774777489']],
  ['title'=>'BETA NALJEPNICE - PUNI SET RR 2T 50 RACING 2019','description'=>'Set naljepnica za RR 50 Enduro racing MY19.','price'=>'105.23','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ce0d7e10-f087-4cfb-a54a-b1e0eac2bb41.jpg?v=1774777370']],
  ['title'=>'BETA NALJEPNICE - PUNI SET RR 2T 50 RACING 2021','description'=>'Set naljepnica za RR 50 racing 2021 modele.','price'=>'87.99','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_bdab52ad-1a04-4be3-98cc-fcee302c2a6e.jpg?v=1774777511']],
  ['title'=>'BETA NALJEPNICE - PUNI SET RR RACING MY21','description'=>'Puni set naljepnica za RR racing modele MY21.','price'=>'166.25','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_dc0ae2f1-f6af-45f6-b2d2-70d4f2cf87f8.jpg?v=1774777478']],
  ['title'=>'BETA NALJEPNICE - KIT RR 4T 125 LC','description'=>'Zaštitne naljepnice za klizač vilice.','price'=>'16.67','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_391f559e-b754-4b0d-b48b-b8642650cbb7.jpg?v=1774777516']],
  ['title'=>'BETA SET NALJEPNICA (1)','description'=>'Originalni Beta OEM set naljepnica.','price'=>'101.19','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b143a0d8-00d3-42d1-8046-57e66c7d86bc.jpg?v=1774777328']],
  ['title'=>'BETA SET NALJEPNICA (2)','description'=>'Originalni Beta OEM set naljepnica.','price'=>'101.19','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f17ad33d-f6cb-4a70-a33a-cfe82ebe0e53.jpg?v=1774777324']],
  ['title'=>'BETA ZAMJENSKI ZUBI ZA PAPUČICU - KIT','description'=>'Set zamjenskih zubi za papučice 037.46.025.82.00.','price'=>'20.07','type'=>'Enduro','tag'=>'Papučice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ab289b85-4ff2-4cb9-9bb4-0e8d0e2e95f7.jpg?v=1774777449']],
  ['title'=>'BETA KIT ELEKTRIČNE INSTALACIJE S STARTEROM RR4T 2010/12','description'=>'Pojednostavljeni kit električne instalacije bez indikatora, trube ili kontrola dugog svjetla za natjecanja.','price'=>'74.55','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_dcd9df42-d16f-4fe8-87d2-f2adce00a0a4.jpg?v=1774777590']],
  ['title'=>'BETA ELEKTRIČNI VENTILATOR - KIT RR 4T','description'=>'Ventilator hlađenja radijatora za 4T RR MY 20/21.','price'=>'166.36','type'=>'Enduro','tag'=>'Ventilatori','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_6ea581b5-9328-4d07-a97a-3be6ffbe8717.jpg?v=1774777385']],
  ['title'=>'BETA ELEKTRIČNI VENTILATOR RR 2T','description'=>'Ventilator hlađenja radijatora za 2T RR MY 20+ / RX 2T.','price'=>'169.45','type'=>'Enduro','tag'=>'Ventilatori','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_65575723-5902-47b8-8b7a-f490d573fb2f.jpg?v=1774777388']],
  ['title'=>'BETA ELEKTRIČNI VENTILATOR RR 4T','description'=>'Ventilator hlađenja radijatora za 4T RR MY 22+ / RX 4T.','price'=>'150.94','type'=>'Enduro','tag'=>'Ventilatori','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_64160d54-435b-41a9-be02-820778f4ed7f.jpg?v=1774777540']],
  ['title'=>'BETA ZAŠTITA MOTORA - KIT RR 4T 125 LC','description'=>'Originalni OEM kit zaštite motora za Beta RR 125 4T LC.','price'=>'90.16','type'=>'Enduro','tag'=>'Zaštita okvira','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_cf1eecb2-0e78-43f1-a45c-f7616936fb9b.jpg?v=1774777514']],
  ['title'=>'BETA EVO 2T - REV 2T ANODIZIRANI ČEPOVI ULJA MOTORA','description'=>'Originalni Beta OEM komponent motora za EVO 2T i REV 2T modele.','price'=>'14.53','type'=>'Trial','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e2801c28-4dc2-4db8-98b8-e01e59de88e9.jpg?v=1774777196']],
  ['title'=>'BETA EVO 2T KIT KVAČILA','description'=>'Originalni Beta OEM pogonski komponent koji osigurava glatki i učinkoviti prijenos snage.','price'=>'337.28','type'=>'Trial','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b500f5f0-914a-4be5-b2dd-39d2800968dd.jpg?v=1774777202']],
  ['title'=>'BETA EVO 2T RACING DISK KVAČILA KIT','description'=>'Originalni Beta OEM komponent kočionog sustava za pouzdanu snagu kočenja.','price'=>'42.77','type'=>'Trial','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_44882e29-a15b-471a-adb4-77bef20df333.jpg?v=1774777207']],
  ['title'=>'BETA EVO 2T/4T ANODIZIRANI POKLOPAC PUMPE KOČNICE/KVAČILA','description'=>'Originalni Beta OEM komponent kočionog sustava na enduro i trail motociklima.','price'=>'57.12','type'=>'Trial','tag'=>'Čepovi upravljača','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0f218c36-02d0-4fb1-ac2a-0e6799b769ae.jpg?v=1774777190']],
  ['title'=>'BETA EVO 2T/4T POLUGA KOČNICE/KVAČILA S OPRUGOM - KIT','description'=>'Originalni Beta OEM komponent projektiran prema originalnim tvorničkim specifikacijama.','price'=>'30.73','type'=>'Trial','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_858ab0c9-eca3-499b-ab8e-887bb77f387c.jpg?v=1774777200']],
  ['title'=>'BETA EVO 2T/4T ERGAL PAPUČICE KIT','description'=>'Originalni Beta OEM komponent okvira koji pruža ispravno pristajanje i čvrstoću za zahtjevnu vožnju izvan ceste.','price'=>'122.96','type'=>'Trial','tag'=>'Papučice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_aea8e7a7-1acf-4d31-ae8e-939cefb5a567.jpg?v=1774777221']],
  ['title'=>'BETA EVO 2T/4T PREDNJI LANČANIK Z.10','description'=>'Originalni Beta OEM pogonski komponent s tvorničkim tolerancijama za pouzdanu i učinkovitu dostavu snage.','price'=>'33.00','type'=>'Trial','tag'=>'Prednji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_19d130a7-0edb-4e4d-8f49-5fd45e3404a2.jpg?v=1774777170']],
  ['title'=>'BETA EVO 2T/4T-REV>06 ANODIZIRANI ČEPOVI UPRAVLJAČA','description'=>'Originalni Beta OEM komponent okvira koji pruža ispravno pristajanje i čvrstoću.','price'=>'23.28','type'=>'Enduro','tag'=>'Čepovi upravljača','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ddc126f7-db51-4f06-8224-8c78214aea95.jpg?v=1774777191']],
  ['title'=>'BETA EVO ANODIZIRANI ČEPOVI UPRAVLJAČA','description'=>'Originalni Beta OEM komponent okvira koji pruža ispravno pristajanje i čvrstoću.','price'=>'23.28','type'=>'Enduro','tag'=>'Čepovi upravljača','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_accb7740-7ef9-4061-90b6-39a7a5fdc6ee.jpg?v=1774777194']],
  ['title'=>'BETA EVO FACTORY KIT TROSTRUKE STEZALJKE','description'=>'Zlatni EVO Factory kit trostruke stezaljke za performanse ovjesa i upravljanja.','price'=>'478.42','type'=>'Trial','tag'=>'Trostruke stezaljke','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_205e0a2f-98aa-421c-af4f-d03806c3165a.jpg?v=1774777219']],
  ['title'=>'BETA EVO FACTORY KIT TROSTRUKE STEZALJKE (VARIJANTA 2)','description'=>'Originalni Beta OEM komponent ovjesa za enduro i motocikle izvan ceste.','price'=>'478.42','type'=>'Trial','tag'=>'Trostruke stezaljke','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_fa159a3f-de07-43cd-a985-d30290f374d4.jpg?v=1774777217']],
  ['title'=>'BETA EVO-REV BRTVA KUĆIŠTA CILINDRA 0,2mm','description'=>'Originalni Beta OEM komponent motora za racing i enduro modele.','price'=>'11.68','type'=>'Trial','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_bf3e494b-9ece-4974-a92d-50dddd35cd36.jpg?v=1774777577']],
  ['title'=>'BETA EVO-REV BRTVA KUĆIŠTA CILINDRA 0,3mm','description'=>'Originalni Beta OEM komponent motora za racing i enduro modele.','price'=>'12.97','type'=>'Trial','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_bbaa78b8-b221-42de-94e2-118468953699.jpg?v=1774777580']],
  ['title'=>'BETA EVO-REV BRTVA KUĆIŠTA CILINDRA 0,5mm','description'=>'Originalni Beta OEM komponent motora za racing i enduro modele.','price'=>'9.08','type'=>'Trial','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1034186a-9c16-41f5-8ace-804d5e8c724e.jpg?v=1774777573']],
  ['title'=>'BETA ISPUH - KIT XTRAINER 2T','description'=>'Tvornička cijev za XTrainer 250/300 2-taktni. Povećani okretni moment i snaga od niskih okretaja.','price'=>'297.81','type'=>'Enduro','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ca33224b-1c4e-4837-a0ec-f882a10dca7c.jpg?v=1774777595']],
  ['title'=>'BETA ISPUŠNI KOLEKTOR A - KIT RR 4T','description'=>'Tvornička ispušna cijev za RR 4T MY 20/22 s promjerom 40.','price'=>'203.78','type'=>'Enduro','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_086a41bd-200a-42ec-8f84-706a5a5a6c9d.jpg?v=1774777468']],
  ['title'=>'BETA ISPUŠNI KOLEKTOR B - KIT RR 4T','description'=>'Tvornička ispušna cijev za RR 4T MY 20/22 s promjerom 42.','price'=>'203.78','type'=>'Enduro','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b8ad8a01-dd7f-49b1-8a78-839cd09a5382.jpg?v=1774777470']],
  ['title'=>'BETA ISPUŠNI KOLEKTOR C - KIT RR 4T','description'=>'Ispušna cijev promjera 45 od nehrđajućeg čelika za RR Factory prigušivač.','price'=>'203.78','type'=>'Enduro','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ed4830e8-e3ae-4fe0-b6cc-34e525446938.jpg?v=1774777473']],
  ['title'=>'BETA ISPUŠNI KOLEKTOR RR 4T','description'=>'Tvornička ispušna cijev za RR 4T MY 22+.','price'=>'289.34','type'=>'Enduro','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_218b9ea0-3cd5-4d76-9c68-0d5018311afd.jpg?v=1774777630']],
  ['title'=>'BETA ISPUH RR 2T','description'=>'Tvornička cijev projektirana s HGS za Beta 2T motore. Samo za utrke.','price'=>'302.72','type'=>'Enduro','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_46b7f2a1-23bb-407a-b954-cb8ba94489ce.jpg?v=1774777528']],
  ['title'=>'BETA CRNE PAPUČICE - KIT','description'=>'CNC papučice s crnom anodizacijom uključuju distancere i opruge.','price'=>'126.15','type'=>'Enduro/Motard','tag'=>'Papučice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_5cbadaeb-3ced-46f6-bf2e-ee2b350214c8.jpg?v=1774777497']],
  ['title'=>'BETA CRVENE PAPUČICE - KIT','description'=>'Originalni Beta OEM kit papučica za ispravno pristajanje i čvrstoću.','price'=>'111.33','type'=>'Trial','tag'=>'Papučice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f39d92e9-3516-452b-b9fb-906d4827acd7.jpg?v=1774777495']],
  ['title'=>'BETA CRVENE PAPUČICE - KIT RR','description'=>'CNC ergal kit papučica, crvena anodizacija. Lakši i čvršći od originalnih.','price'=>'166.64','type'=>'Enduro','tag'=>'Papučice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_eaca1961-9130-4c48-82c2-c3987c2f4fb8.jpg?v=1774777506']],
  ['title'=>'BETA PAPUČICE - KIT RR','description'=>'CNC ergal kit papučica. Lakši i čvršći od originalnih.','price'=>'166.64','type'=>'Enduro','tag'=>'Papučice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8f88a6db-a034-458f-9cfc-23f7d409e7a7.jpg?v=1774777440']],
  ['title'=>'BETA NALJEPNICA VILICE RR - KYB','description'=>'Naljepnica vilice za KYB sustav ovjesa.','price'=>'10.47','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_fd17f2cc-4911-4b36-bfda-66cb5df5b630.jpg?v=1774777620']],
  ['title'=>'BETA NOSAČ PREDNJEG BLATOBRANA - KIT ALP 4.0','description'=>'Kit distancera s vijcima za podizanje prednjeg blatobrana ALP.','price'=>'14.69','type'=>'Alp','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8dbeb348-1828-497f-82ea-5e538e108478.jpg?v=1774777673']],
  ['title'=>'BETA PREDNJA VILICA - KIT XTRAINER','description'=>'Kompletna 48mm ZF zatvorena kartuša vilice. Uključuje ploče, os, distancere i zaštitu stabla.','price'=>'1699.50','type'=>'Enduro','tag'=>'Prednji ovjes','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4f35fb23-c573-48de-8636-3e5ab716897f.jpg?v=1774777521']],
  ['title'=>'BETA NALJEPNICA PREDNJE VILICE - KIT HOLCOMBE REPLICA','description'=>'Originalni Beta OEM komponent ovjesa.','price'=>'10.66','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_67a300af-2656-45c2-9eb6-11bc7844f280.jpg?v=1774777383']],
  ['title'=>'BETA PREKIDAČ MASE - KIT','description'=>'Originalni Beta OEM električni komponent koji osigurava ispravan napon i kompatibilnost.','price'=>'61.01','type'=>'Trial','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_847021a8-71ee-48ef-b363-0d73519bc808.jpg?v=1774777342']],
  ['title'=>'BETA ZAŠTITA - KIT EVO','description'=>'EVO zaštitni kit koji štiti zračni filter od vode, blata, prašine i prljavštine.','price'=>'15.90','type'=>'Trial','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9dc4e038-880b-41ca-a1ef-8b1142598da7.jpg?v=1774777601']],
  ['title'=>'BETA ZAŠTITA RADIJATORA RR','description'=>'Kit zaštite radijatora za RR MY 20 2 i 4-taktne modele. Kompatibilan s kitom ventilatora.','price'=>'130.60','type'=>'Enduro','tag'=>'Zaštita radijatora','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_eaf97056-3f3b-4647-a123-1cbbc6590324.jpg?v=1774777435']],
  ['title'=>'BETA ZAŠTITA RUKU CRVENA - CRNA','description'=>'Crveno-crni kit zaštite ruku s komponentama za montažu.','price'=>'45.30','type'=>'Enduro','tag'=>'Zaštita ruku','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_6572e600-83c0-4b62-9d3b-0f1e5566c96f.jpg?v=1774777604']],
  ['title'=>'BETA UPRAVLJAČ','description'=>'Originalni Beta OEM upravljač koji pruža ispravno pristajanje i čvrstoću.','price'=>'156.73','type'=>'Enduro','tag'=>'Upravljači','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4bf1857b-1a14-4478-a941-2b4d9d350559.jpg?v=1774777407']],
  ['title'=>'BETA UPRAVLJAČ RR 4T 2007','description'=>'Originalni Beta OEM upravljač za ispravnu geometriju i kompatibilnost.','price'=>'122.41','type'=>'Enduro','tag'=>'Upravljači','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_030eaf37-39cc-4737-8c14-888b53c9c563.jpg?v=1774777592']],
  ['title'=>'BETA NOSAČ UPRAVLJAČA PHDS SUSTAV RR 4T MY 2010','description'=>'Nosač upravljača za PHDS sustav na RR 4T MY 10 modelima.','price'=>'241.97','type'=>'Enduro','tag'=>'PHDS sustav','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_aabe8401-65ef-4f21-979d-515a76d47f85.jpg?v=1774777239']],
  ['title'=>'BETA CRVENA MASKA PREDNJEG SVJETLA - KIT RR','description'=>'Crvena prednja ploča za RR MY 20+ i RX modele.','price'=>'29.19','type'=>'Enduro','tag'=>'Maske','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_12237658-cf45-4715-9e93-f33c05016e71.jpg?v=1774777549']],
  ['title'=>'BETA CRVENA MASKA PREDNJEG SVJETLA - KIT RX','description'=>'Crvena prednja ploča za RR MY 20+ modele.','price'=>'34.94','type'=>'Enduro','tag'=>'Maske','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_cb2e8517-aef2-4970-abd9-47c532feec39.jpg?v=1774777625']],
  ['title'=>'BETA BIJELA MASKA PREDNJEG SVJETLA - KIT RR','description'=>'Bijela prednja ploča za RR MY 20+ i RX modele.','price'=>'28.35','type'=>'Enduro','tag'=>'Maske','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_5bd754e4-691b-412b-84c8-b37e1dbd931f.jpg?v=1774777397']],
  ['title'=>'BETA USISNI KOLEKTOR - KIT 2T','description'=>'Kit za uklanjanje automatskog sustava miješanja na RR 2T 250/300 MY 20+ i XTrainer.','price'=>'55.24','type'=>'Enduro','tag'=>'Karburatori','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b343ef9b-ea23-4559-ba19-94c12bccfbab.jpg?v=1774777450']],
  ['title'=>'BETA PLAVA ELASTIČNA TRAKA KILL-SWITCHA EVO','description'=>'Elastična vrpca za magnetski kill-switch. Rezervna vrpca za 007.45.050.82.00.','price'=>'9.16','type'=>'Trial','tag'=>'Prekidači zaustavljanja','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c360e9f9-a198-44b1-a3cb-b615a2a152c4.jpg?v=1774777518']],
  ['title'=>'BETA KIT - MOTARD 50 (EU 5)','description'=>'Kit za ugađanje Motard 50 (EU 5). Samo za utrke.','price'=>'152.71','type'=>'Enduro/Motard','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_81e4052a-37aa-47d8-a5c4-be5333ed1e15.jpg?v=1774777538']],
  ['title'=>'BETA KIT - ENDURO 50 (EU 5)','description'=>'Kit za ugađanje Enduro 50 (EU 5). Samo za utrke.','price'=>'152.88','type'=>'Enduro/Motard','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e7406e23-d975-49de-b9f2-9220ea39a263.jpg?v=1774777533']],
  ['title'=>'BETA KIT - 2 SVJEĆICE RR 2T','description'=>'Kit s dvostrukom svjećicom za RR 300 2T MY 22+. Sadrži glavu, brtvu, svjećice, zavojnicu paljenja, nosač i kabel.','price'=>'257.29','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_440ed901-bedb-441b-ab3f-482b9805aaaa.jpg?v=1774777642']],
  ['title'=>'BETA KIT - CRVENA GLAVA MOTORA D79','description'=>'Glava motora D79 crvena.','price'=>'74.35','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/download_3_9775a524-d6e1-40ce-9eae-10331a5afea3.jpg?v=1774777765']],
  ['title'=>'BETA KIT LONG RANGE EVO 2T/4T 2009-15','description'=>'Originalni Beta OEM karoserijski komponent za Long Range Evo 2T/4T MY 09-15.','price'=>'408.14','type'=>'Trial','tag'=>'Kompletno sjedalo','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_34132138-cf49-4f81-9385-36d7c5d84192.jpg?v=1774777212']],
  ['title'=>'BETA KIT MOTARD RR 2013-19','description'=>'Kit Motard RR MY 13/19. Samo za utrke.','price'=>'1324.60','type'=>'Enduro','tag'=>'Setovi kotača','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_28c2c949-4987-4e41-bff0-1486b6994bb3.jpg?v=1774777295']],
  ['title'=>'BETA KIT ZUPČANIKA ULJNE PUMPE (MY 15/19)','description'=>'Kit zupčanika uljne pumpe za 4T RR MY 15/19. Čelični zupčanici za maksimalnu pouzdanost.','price'=>'148.95','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_046cad34-dad0-4896-bdc7-ff911654a7a8.jpg?v=1774777312']],
  ['title'=>'BETA KIT ZUPČANIKA ULJNE PUMPE (MY 10/14)','description'=>'Racing kit zupčanika uljne pumpe za 4T RR MY 10/14. Čelični zupčanici za maksimalnu pouzdanost.','price'=>'145.39','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_7af12429-b453-4e6a-8f5d-97230343dbb0.jpg?v=1774777309']],
  ['title'=>'BETA KIT REV - SJEDALO/REZERVOAR LONG RANGE','description'=>'Rev sjedalo/rezervoar long range kit.','price'=>'478.87','type'=>'Trial','tag'=>'Kompletno sjedalo','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8c0f2cfa-cb55-42df-bd89-6404b7321d7a.jpg?v=1774777367']],
  ['title'=>'BETA KIT RR 2T 300 - TRANSFORMACIJSKI KIT','description'=>'Kit za transformaciju od 300cc. Svi dijelovi za pretvaranje RR 250 u RR 300.','price'=>'917.33','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d7d44e39-921f-44cb-b184-f66bd01611f5.jpg?v=1774777645']],
  ['title'=>'BETA KIT ELEKTRIČNI VENTILATOR MY 2010','description'=>'Kit električnog ventilatora za 4T RR MY 10/15.','price'=>'120.65','type'=>'Enduro','tag'=>'Ventilatori','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1d8eb806-61f9-4eab-b8ea-a2c3396fda08.jpg?v=1774777204']],
  ['title'=>'BETA KIT ELEKTRIČNI VENTILATOR RR 2T (MY 13/19)','description'=>'Kit električnog ventilatora za 2T RR MY 13/19.','price'=>'169.45','type'=>'Enduro','tag'=>'Ventilatori','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_08072df3-7acb-4e29-9b24-e33931f5c376.jpg?v=1774777258']],
  ['title'=>'BETA KIT ELEKTRIČNI VENTILATOR RR 4T 350 EFI','description'=>'Kit električnog ventilatora za 4T RR MY 15/19.','price'=>'166.36','type'=>'Enduro','tag'=>'Ventilatori','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_5356a5b2-97f1-48fc-9725-114dc11f3d76.jpg?v=1774777304']],
  ['title'=>'BETA KIT MIJEŠANJA ULJA 2T RR RACING MY 18/19','description'=>'Kit za miješanje ulja za 2T RR Racing 250/300 MY 18/19.','price'=>'259.57','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_624b966d-7150-4a77-8a7b-94812c7cfb56.jpg?v=1774777261']],
  ['title'=>'BETA LED LAMPA - KIT','description'=>'Kit LED žarulje koji pruža bolje osvjetljenje uz manje potrošnje energije. Samo za utrke.','price'=>'53.13','type'=>'Enduro','tag'=>'Osvjetljenje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8b3cbc16-8529-45c4-9cd8-76aec1552bac.jpg?v=1774777404']],
  ['title'=>'BETA LIJEVA BOČNA TORBA - KIT ALP 4.0','description'=>'Kit uključuje torbu, metalni nosač i vijke. Vodootporna torba za dulja putovanja.','price'=>'165.19','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_bc705dab-fb65-42a1-a197-afffa7778ef1.jpg?v=1774777700']],
  ['title'=>'BETA CRVENA POLUGA - KIT','description'=>'Crvena anodizirana poluga kočnice i kvačila za XTrainer MY 15+, RX, RR 125/200/250/300 2T i RR 4T MY 15+.','price'=>'76.74','type'=>'Enduro','tag'=>'Ručice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_2bc6cf9b-20ce-435d-bb6b-99f73736baa4.jpg?v=1774777633']],
  ['title'=>'BETA ČEPOVI ZA PUNJENJE ULJA RR4T MY 2010/12','description'=>'Čep za punjenje ulja za RR 4T 20+ i RX 4T. Lagani aluminij s crvenom anodizacijom.','price'=>'21.58','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_fd1e8d3a-5779-4e5a-8116-f8599e833f31.jpg?v=1774777142']],
  ['title'=>'BETA ČEP ZA PUNJENJE ULJA 2T RR MY 18+','description'=>'Čep za punjenje ulja za 2T 250/300 RR MY 18+, XTrainer MY 18+, RX 2T.','price'=>'17.24','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_5f025847-7228-4d22-83ae-6b3541a19f30.jpg?v=1774777251']],
  ['title'=>'BETA ČEP ZA PUNJENJE ULJA 4T RR MY 10+','description'=>'Čep za punjenje ulja za 4T RR MY 10+, 2T 250/300 RR do MY 17, XTrainer do MY 17.','price'=>'25.73','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d50e8507-088b-47f3-b007-d2ce308a1c7c.jpg?v=1774777248']],
  ['title'=>'BETA SKLOP POKLOPCA ULJNOG FILTERA','description'=>'Sklop anodiziranog aluminijskog čepa uljnog filtera.','price'=>'41.07','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_3f939869-d2e3-4745-95a8-8196f7784d2a.jpg?v=1774777270']],
  ['title'=>'BETA KIT SUSTAVA UBRIZGAVANJA ULJA','description'=>'Kit za miješanje ulja za 2T RR Racing 250/300 MY 20+.','price'=>'288.42','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_db8f3a2a-2b85-4de9-99bb-ee69017dca6d.jpg?v=1774777392']],
  ['title'=>'BETA KIT SUSTAVA UBRIZGAVANJA ULJA RR 2T 200','description'=>'Kit za miješanje ulja za 2T RR Racing 200 MY 21+.','price'=>'289.37','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_618e60e8-e4f4-4568-896c-c5407cfaf6b3.jpg?v=1774777502']],
  ['title'=>'BETA KIT ZUPČANIKA ULJNE PUMPE (MY 20+)','description'=>'Racing kit zupčanika uljne pumpe za 4T RR MY 20+.','price'=>'156.36','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f2afc68f-a1c1-4073-a840-aeb4c2f29a4e.jpg?v=1774777402']],
  ['title'=>'BETA ČEP REZERVOARA AMORTIZERA','description'=>'Produženi čep rezervoara amortizera koji poboljšava performanse i smanjuje toplinu.','price'=>'35.42','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ec672fb8-cb57-498a-9bf8-2994354f5665.jpg?v=1774777443']],
  ['title'=>'BETA PUTNIČKE PAPUČICE - KIT','description'=>'Originalni OEM komponent okvira izrađen prema tvorničkim specifikacijama.','price'=>'102.64','type'=>'Enduro/Motard','tag'=>'Papučice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_198723e8-dc92-4ee1-bb78-d79c9568e198.jpg?v=1774777676']],
  ['title'=>'BETA SET LATICA VILICE - KIT RR','description'=>'Set podešavajućih latica ZF/SACHS vilice, 32 različite latice (10 komada po vrsti).','price'=>'349.62','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c4115a21-3b8f-4d87-91a2-0634d1339a22.jpg?v=1774777431']],
  ['title'=>'BETA PINJION (PREDNJI LANČANIK)','description'=>'Originalni OEM pogonski komponent koji osigurava glatki i učinkoviti prijenos snage.','price'=>'19.17','type'=>'Enduro','tag'=>'Prednji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8d695797-205d-490e-babc-803ed9d9e155.jpg?v=1774777174']],
  ['title'=>'BETA NOSAČ PRTLJAGE SA VIJCIMA - KIT ALP 4.0','description'=>'Stražnja ploča držača torbe s vijcima za modele ALP 4.0 i ALP X 4T.','price'=>'72.10','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/034.32.083.42.59_43b3d0ba-0a13-494f-b04e-028965da6662.jpg?v=1774777705']],
  ['title'=>'BETA CRVENI POKLOPAC PUMPE (RR 50/125 4T)','description'=>'Prednji poklopac pumpe kočnice u crvenoj anodiziranoj izvedbi za RR 50/125 4T MY 11+.','price'=>'22.44','type'=>'Enduro/Motard','tag'=>'Poklopci cilindra','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_37d75b1d-b28b-4178-a8bd-f7ef44884e65.jpg?v=1774777647']],
  ['title'=>'BETA ZAŠTITA RADIJATORA','description'=>'Originalni OEM karoserijski komponent prema tvorničkim specifikacijama.','price'=>'123.92','type'=>'Enduro','tag'=>'Zaštita radijatora','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4df96349-7a5e-41da-bdd5-3f3700b5affe.jpg?v=1774777302']],
  ['title'=>'BETA STRAŽNJI LANČANIK Z.48','description'=>'Originalni OEM pogonski komponent koji osigurava glatki prijenos snage.','price'=>'87.99','type'=>'Enduro','tag'=>'Stražnji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b9b12393-ef78-44b1-9301-05870a7e0081.jpg?v=1774777282']],
  ['title'=>'BETA STRAŽNJI LANČANIK Z.49','description'=>'Originalni OEM pogonski komponent koji osigurava glatki prijenos snage.','price'=>'87.99','type'=>'Enduro','tag'=>'Stražnji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a76fa519-f0c3-43ea-8e0c-950b75d2d88f.jpg?v=1774777256']],
  ['title'=>'BETA STRAŽNJI LANČANIK Z.50','description'=>'Originalni OEM pogonski komponent koji osigurava glatki prijenos snage.','price'=>'87.99','type'=>'Enduro','tag'=>'Stražnji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9329b443-d6f1-41d0-a0cc-d92ce533f8a9.jpg?v=1774777285']],
  ['title'=>'BETA STRAŽNJI LANČANIK Z.51','description'=>'Originalni OEM pogonski komponent koji osigurava glatki prijenos snage.','price'=>'87.99','type'=>'Enduro','tag'=>'Stražnji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_66a96b25-c4c1-4966-946a-4cffb27e69a3.jpg?v=1774777280']],
  ['title'=>'BETA STRAŽNJI LANČANIK Z.47','description'=>'Originalni OEM pogonski komponent koji osigurava glatki prijenos snage.','price'=>'87.99','type'=>'Enduro','tag'=>'Stražnji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b925322f-284d-4cca-a75f-b0b74cdf4b4a.jpg?v=1774777287']],
  ['title'=>'BETA CRVENE NALJEPNICE NAPLATAKA','description'=>'Originalni OEM komponent kotača koji održava ispravnu geometriju i performanse.','price'=>'31.79','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f2f1c7a5-bc55-4601-aafa-c531ba83cb0e.jpg?v=1774777158']],
  ['title'=>'BETA DESNA BOČNA TORBA - KIT ALP 4.0','description'=>'Kompletni kit s mekom bočnom torbom, metalnim nosačem i vijcima. Vodootporni materijal.','price'=>'165.19','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_af0c0e10-e53b-41a8-b8a9-8313dfe95083.jpg?v=1774777703']],
  ['title'=>'BETA NALJEPNICE NAPLATAKA RR RACING','description'=>'Originalni OEM komponent kotača koji održava ispravnu geometriju i performanse.','price'=>'49.12','type'=>'Enduro','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_66be5f65-c489-456e-8174-2f1e2fcd802e.jpg?v=1774777377']],
  ['title'=>'BETA CRVENA ŠIPKA - KIT 2T','description'=>'Podešivač ispušnog ventila za 2-taktne motore od 250/300cc na RR i Xtrainer modelima.','price'=>'42.30','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9277b63e-fecf-47c4-9844-b9643cf43c6f.jpg?v=1774777453']],
  ['title'=>'BETA RR 50 RACING SKLOP PRIGUŠIVAČA','description'=>'Racing sklop prigušivača uključuje kit karburacije. Samo za natjecanje.','price'=>'219.27','type'=>'Enduro/Motard','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_5ee50286-6db6-46ea-a002-07fdcd9c95b3.jpg?v=1774777246']],
  ['title'=>'BETA RR4T MY 2010 POKLOPAC ZRAČNOG FILTERA','description'=>'Poklopac za pranje airbox filtera za održavanje zračnog filtera.','price'=>'35.90','type'=>'Enduro','tag'=>'Poklopci zračnog filtera','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9246993d-caac-4929-a09e-f35640c5b9b1.jpg?v=1774777236']],
  ['title'=>'BETA PRESVLAKA SJEDALA','description'=>'Protuklizna presvlaka sjedala za poboljšano prianjanje i izdržljivost.','price'=>'100.45','type'=>'Enduro','tag'=>'Presvlake sjedala','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d2fb10f3-f140-4d99-9879-3e61abd5ec37.jpg?v=1774777290']],
  ['title'=>'BETA SMEĐA TORBICA','description'=>'Crni nosač prtljage ALP 4.0 za povećan kapacitet pohrane.','price'=>'92.62','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/034.46.001.00.00_d8bcc917-fdbc-4fa1-a510-9f14e95ae9e0.jpg?v=1774777694']],
  ['title'=>'BETA BRTVENI ČEP (PRST OSOVINE)','description'=>'Originalni OEM hardverski komponent s ispravnim materijalima za sigurno pričvršćivanje.','price'=>'8.97','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_3675bba9-98f3-4106-8461-8926428de427.jpg?v=1774777177']],
  ['title'=>'BETA VISOKO SJEDALO - KIT','description'=>'Kit visokog sjedala koji se sastoji od spužvaste pjene i presvlake sjedala.','price'=>'135.10','type'=>'Enduro','tag'=>'Kompletno sjedalo','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_6af802c3-06d2-43d7-a25f-8b7008a5b9f3.jpg?v=1774777344']],
  ['title'=>'BETA CRNO SJEDALO - KIT RR','description'=>'Kit višeg sjedala s 15mm dodatne pjene i protukliznom presvlakom.','price'=>'125.11','type'=>'Enduro','tag'=>'Kompletno sjedalo','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f84d4f25-ebc8-4337-99ea-b4f8093fe429.jpg?v=1774777555']],
  ['title'=>'BETA PLAVO SJEDALO - KIT RR','description'=>'15mm povišena pjena s protukliznom presvlakom. Osnovna ploča nije uključena.','price'=>'125.11','type'=>'Enduro','tag'=>'Kompletno sjedalo','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8f18496f-9528-4d8b-b714-d74a13279dce.jpg?v=1774777414']],
  ['title'=>'BETA CRNA PRESVLAKA SJEDALA RR','description'=>'Protuklizna presvlaka sjedala u crnoj boji za poboljšano prianjanje.','price'=>'86.23','type'=>'Enduro','tag'=>'Presvlake sjedala','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_54e69f95-56f7-4186-9260-33dd067c6401.jpg?v=1774777553']],
  ['title'=>'BETA PLAVA PRESVLAKA SJEDALA RR','description'=>'Originalni OEM karoserijski komponent s tvorničkim pristajanjem i trajnim materijalima.','price'=>'86.23','type'=>'Enduro','tag'=>'Presvlake sjedala','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a4e3cf0e-1255-403d-8d80-81dc2185a89a.jpg?v=1774777416']],
  ['title'=>'BETA OPRUGA AMORTIZERA 5,0K','description'=>'ZF opruga amortizera ocijene K 5.0 za RR modele.','price'=>'145.72','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1e0211e8-c349-4afe-998c-1bec7e0c6ca4.jpg?v=1774777229']],
  ['title'=>'BETA OPRUGA AMORTIZERA 5,6K','description'=>'ZF opruga amortizera ocijene K 5.6 za RR modele.','price'=>'162.60','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9772a7e0-b522-489e-a5af-f0a160a24ca8.jpg?v=1774777297']],
  ['title'=>'BETA OPRUGA AMORTIZERA 5,8K','description'=>'ZF opruga amortizera ocijene K 5.8 za RR modele.','price'=>'159.26','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_368d39c7-fc9e-4eb4-afc4-f84c25ed73c2.jpg?v=1774777299']],
  ['title'=>'BETA PODEŠIVAČ OPRUGE AMORTIZERA','description'=>'Podešivač prednapetosti opruge za 2T/4T RR MY 15/18.','price'=>'158.82','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_5c544b5b-08aa-4079-8d09-9d431a9060bc.jpg?v=1774777314']],
  ['title'=>'BETA AMORTIZER XTRAINER','description'=>'Tvornički amortizer s aluminijskim tijelom od 52mm s neovisnim podešavanjima.','price'=>'490.07','type'=>'Enduro','tag'=>'Stražnji ovjes','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_27a9b63d-2dac-4659-84bf-7ce4f9e134ea.jpg?v=1774777544']],
  ['title'=>'BETA CRNI BOČNI ŠTITNIK PLOČE - KIT ALP 4.0','description'=>'Kit zaštite motora s metalnim nosačima i vijcima za zaštitu od pada/udara.','price'=>'129.31','type'=>'Alp','tag'=>'Zaštita okvira','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_99921be1-c5e3-425c-ad08-6e9bf4c4f28c.jpg?v=1774777713']],
  ['title'=>'BETA PRIGUŠIVAČ - KIT RR 2T','description'=>'Tvornički ispuh projektiran s HGS za 2T motore visokih performansi. Samo za natjecanje.','price'=>'244.65','type'=>'Enduro','tag'=>'Ispušne cijevi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a09c8498-de3f-4e32-a113-65260ca413f3.jpg?v=1774777623']],
  ['title'=>'BETA OPRUGA XTRAINER K 5.5','description'=>'Opruga amortizera Xtrainer K 5.5 za intenzivnu upotrebu zahtjevnih vozača.','price'=>'117.29','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_995ed1cf-b0bb-4a48-acb4-3ade9af87d10.jpg?v=1774777337']],
  ['title'=>'BETA OPRUGA - KIT','description'=>'Kit opruge amortizera za sustave ovjesa.','price'=>'94.32','type'=>'Enduro','tag'=>'Stražnji ovjes','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0d0fbac5-6c80-47a5-9c46-ddb170229fc4.jpg?v=1774777611']],
  ['title'=>'BETA OPRUGA 4K - KIT','description'=>'ZF KIT OPRUGE, K= 4,0 N/MM.','price'=>'88.97','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0e104c33-ffc6-4ff7-8090-bfe41e3d1a41.jpg?v=1774777438']],
  ['title'=>'BETA OPRUGA 7,6 KG/MM','description'=>'Opruga od 7.6K nudi više oslonca od standardne od 7K. Idealna za vozače koji putuju s putnikom.','price'=>'123.56','type'=>'Alp','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e6ac0b45-6724-4eef-baa5-be2ab751b7ae.jpg?v=1774777715']],
  ['title'=>'BETA PODEŠIVAČ OPRUGE - KIT C50','description'=>'OEM komponent ovjesa (049.46.003.00.00) za enduro motocikle.','price'=>'173.89','type'=>'Enduro','tag'=>'Pribor za ugađanje','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9b51e24f-2206-4ad4-8b3c-8d96d51bded0.jpg?v=1774777614']],
  ['title'=>'BETA OPRUGA K 4,8','description'=>'ZF OPRUGA AMORTIZERA K 4,8 - RR.','price'=>'126.94','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_6453d239-d87f-42c1-a74b-a38605d60ef8.jpg?v=1774777525']],
  ['title'=>'BETA OPRUGA K 4.2 N/MM - KIT RR 4T','description'=>'KIT OPRUGE ZA KYB VILICE 4,2K.','price'=>'163.60','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_cd3005ad-5ec6-4797-b470-e761af7ea5fd.jpg?v=1774777460']],
  ['title'=>'BETA OPRUGA K 4.6 N/MM - KIT RR 4T','description'=>'KIT OPRUGE ZA KYB VILICE 4,6K.','price'=>'163.42','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b866e897-7379-4502-a38a-9a506a414210.jpg?v=1774777464']],
  ['title'=>'BETA KIT OPRUGE K=4,2 N/MM','description'=>'OEM komponent ovjesa (020.34.332.00.00) za enduro motocikle.','price'=>'50.59','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_00d445bd-40f7-48c2-98e0-3515182cecc5.jpg?v=1774777234']],
  ['title'=>'BETA KIT OPRUGE K=4,6 N/MM','description'=>'OEM komponent ovjesa (031.46.033.82.00) za enduro motocikle.','price'=>'69.76','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4cb42931-d19c-4517-9a3f-25bb02fc17df.jpg?v=1774777319']],
  ['title'=>'BETA KIT OPRUGE K=4,8 N/MM','description'=>'OEM komponent ovjesa (020.34.322.00.00) za enduro motocikle.','price'=>'55.00','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_34f7f132-3d1f-43b9-b3f6-dfc6acecc51f.jpg?v=1774777231']],
  ['title'=>'BETA KIT OPRUGE K=5,0 N/MM','description'=>'OEM komponent ovjesa (031.46.034.82.00) za enduro motocikle.','price'=>'115.94','type'=>'Enduro','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_34699416-5295-46e7-b6e4-c9e5efb1dbcc.jpg?v=1774777321']],
  ['title'=>'BETA OPRUGA K 80N/MM','description'=>'OEM komponent ovjesa (007.33.022.00.00) za trial motocikle.','price'=>'101.63','type'=>'Trial','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1d782d15-c8f9-43cb-aadf-bfa9ea4477ed.jpg?v=1774777179']],
  ['title'=>'BETA OPRUGA K 8N/MM','description'=>'OEM komponent ovjesa (007.34.008.00.00) za trial motocikle.','price'=>'74.62','type'=>'Trial','tag'=>'Opruge','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0f484c40-c4b8-46f3-80c5-cfd7b583c239.jpg?v=1774777182']],
  ['title'=>'BETA LANČANIK 12Z','description'=>'OEM pogonski komponent (036.04.000.00.00). Glatki prijenos snage od motora do kotača.','price'=>'12.94','type'=>'Enduro','tag'=>'Prednji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_440e0813-2465-4698-a017-358ea7a112c8.jpg?v=1774777331']],
  ['title'=>'BETA NALJEPNICA LANČANIKA EVO FACTORY','description'=>'OEM pogonski komponent (007.43.194.82.00). Osigurava pouzdanu dostavu snage.','price'=>'11.82','type'=>'Trial','tag'=>'Setovi naljepnica','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_054f2e6b-1524-4e66-b0c7-5dfd8e1c4401.jpg?v=1774777487']],
  ['title'=>'BETA CRVENI LANČANIK Z42','description'=>'OEM pogonski komponent (007.42.063.42.53). Glatka i učinkovita dostava snage.','price'=>'63.64','type'=>'Trial','tag'=>'Stražnji lančanici','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8038bc06-df2e-4ab4-801b-364471efc304.jpg?v=1774777547']],
  ['title'=>'BETA KIT ZA POKRETANJE NOGOM - RR 2T 250/300 MY 20+','description'=>'Kit kick startera za 2T RR 250/300 MY 20+ / RX 2T. Omogućuje pokretanje nogom.','price'=>'244.83','type'=>'Enduro','tag'=>'Kickstarteri','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_90a890c9-0446-4d99-8383-4a3126a07186.jpg?v=1774777409']],
  ['title'=>'BETA KIT ZA POKRETANJE NOGOM - RR 2T 200 MY 20+','description'=>'Kit kick startera za 2T RR 200 MY 20+. Omogućuje pokretanje nogom.','price'=>'228.04','type'=>'Enduro','tag'=>'Kickstarteri','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9eeb24a2-67cd-438d-ba14-f8e57ff740d1.jpg?v=1774777358']],
  ['title'=>'BETA KIT ZA POKRETANJE NOGOM - RR 2T 250/300 MY 15/19','description'=>'Kit kick startera za 2T RR 250/300 MY 15/19 i XTR SVE MY.','price'=>'239.10','type'=>'Enduro','tag'=>'Kickstarteri','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_806b575b-97d0-4800-a5ce-2d34c80703b1.jpg?v=1774777334']],
  ['title'=>'BETA KIT ZA POKRETANJE NOGOM - RR 4T MY 15/19','description'=>'Kit kick startera za 4T RR MY 15/19. Omogućuje pokretanje nogom.','price'=>'239.10','type'=>'Enduro','tag'=>'Kickstarteri','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_434ecaa9-186e-4d81-bcf2-6771ae675b31.jpg?v=1774777339']],
  ['title'=>'BETA KIT ZA POKRETANJE NOGOM - RR 4T MY 20+','description'=>'Kit kick startera za 4T RR MY 20+. Omogućuje pokretanje nogom.','price'=>'279.67','type'=>'Enduro','tag'=>'Kickstarteri','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_22d2cfae-dc34-456b-b9d5-1db5db26b6a1.jpg?v=1774777399']],
  ['title'=>'BETA CRVENA PAPUČICA STRAŽNJE KOČNICE','description'=>'Crvena anodizirana stražnja šipka kočnice za RR STD/RACING MY 13+, XTRAINER sve MY, RX sve MY.','price'=>'44.10','type'=>'Enduro','tag'=>'Papučice kočnice','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ce09e09d-8784-4043-80b3-d6818241f17f.jpg?v=1774777635']],
  ['title'=>'BETA PREKIDAČ ZA DVOSTRUKU KARTU','description'=>'Prekidač za dvostruki električni sustav karte.','price'=>'67.31','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f3e65ce0-7f44-4815-a7e3-69f91440d82a.jpg?v=1774777273']],
  ['title'=>'BETA STRAŽNJA TORBA','description'=>'Crni nosač prtljage Alp 4.0 za nošenje stražnjeg tereta.','price'=>'102.54','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/034.46.016.00.00_f80ae56e-2df5-4cfd-9a0c-cfbe1e8d0e09.jpg?v=1774777698']],
  ['title'=>'BETA STRAŽNJA TORBA - KIT ALP 4.0','description'=>'Kompletni kit s stražnjom torbom, pločom za pričvršćivanje i vijcima. Vodootporni materijal.','price'=>'174.52','type'=>'Alp','tag'=>'Nosači prtljage','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a1c17b62-afd6-4fc6-bb8e-3a65698dab9c.jpg?v=1774777708']],
  ['title'=>'BETA ČEP REZERVOARA GORIVA EVO','description'=>'Originalni Beta OEM komponent sustava goriva za skladištenje i dostavu goriva.','price'=>'66.60','type'=>'Trial','tag'=>'Čepovi rezervoara','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_78425524-acf8-4d3c-b0d3-90d28c5d6384.jpg?v=1774777214']],
  ['title'=>'BETA KIT TROSTRUKE STEZALJKE EVO','description'=>'Komponent ovjesa koji isporučuje precizno prigušenje i krutost za upravljanje.','price'=>'478.42','type'=>'Trial','tag'=>'Trostruke stezaljke','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_35912463-3568-4ca2-a1e5-e1be7a102dc9.jpg?v=1774777421']],
  ['title'=>'BETA KIT LAMPICE UPOZORENJA','description'=>'Originalni Beta OEM električni komponent koji osigurava ispravan napon i pouzdanost.','price'=>'31.66','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0f9c0267-7527-47bf-9ef5-8cb3d3a0bfd9.jpg?v=1774777316']],
  ['title'=>'BETA KIT LAMPICE UPOZORENJA (VARIJANTA 2)','description'=>'Originalni Beta OEM električni komponent koji osigurava ispravan napon i pouzdanost.','price'=>'31.66','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_20a741bd-b245-4de5-961c-e44481a47b8c.jpg?v=1774777268']],
  ['title'=>'BETA KIT KOTAČA - MOTARD MY 20+','description'=>'Kit motard kotača za RR MY 20+ uključuje prednji/stražnji kotač, blatobrane, disk i nosač čeljusti.','price'=>'1324.60','type'=>'Enduro','tag'=>'Setovi kotača','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_866c4600-30a2-4d03-b1c4-89f7da66d6fc.jpg?v=1774777433']],
  ['title'=>'BETA POKLOPAC OSI KOTAČA','description'=>'Originalni Beta OEM komponent kotača koji održava geometriju i performanse sklopa kotača.','price'=>'37.70','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_94873658-87ec-4039-8c16-6443613ca23f.jpg?v=1774777350']],
  ['title'=>'BETA KIT PUNJENJA KABLOVA RR','description'=>'Originalni Beta OEM električni komponent za pouzdani napon i kompatibilnost.','price'=>'70.11','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_250054a2-6aa3-4a9d-a3db-da7d18fd1664.jpg?v=1774777475']],
  ['title'=>'BETA KIT ELEKTRIČNE INSTALACIJE RR 2T','description'=>'Kit električne instalacije s starterom za 2T. Samo za natjecanje.','price'=>'119.99','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c06c4aef-cc68-4cc0-8539-d2182cce401a.jpg?v=1774777428']],
  ['title'=>'BETA KIT ELEKTRIČNE INSTALACIJE RR 4T','description'=>'Kit električne instalacije s starterom za 4T. Samo za natjecanje.','price'=>'115.12','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1b6f95f2-01d1-41fa-bf25-5f6a19f5bd13.jpg?v=1774777424']],
  ['title'=>'BETA KIT SUSTAVA KABLOVA (1)','description'=>'Pojednostavljeni sustav kablova za natjecanje, bez pokazivača smjera ili kontrola trube.','price'=>'114.81','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_17b62fee-a12f-4326-9f83-652d52153aa7.jpg?v=1774777275']],
  ['title'=>'BETA KIT SUSTAVA KABLOVA (2)','description'=>'Originalni Beta OEM komponent ovjesa za prigušenje i izdržljivost.','price'=>'114.81','type'=>'Enduro','tag'=>'Električna instalacija','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_7dbaead2-5056-4eea-b441-7054118e2431.jpg?v=1774777266']],
  ['title'=>'BOANO BLACKBIRD CRVENO-PLAVA PRESVLAKA SJEDALA BETA RR 2020-2022','description'=>'Protuklizna presvlaka sjedala za Beta RR 2S-4S modele 2020-2022.','price'=>'86.41','type'=>'Enduro','tag'=>'Presvlake sjedala','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/20191218122327.jpg?v=1727182901']],
  ['title'=>'BOANO ZAŠTITA KUĆIŠTA I LANČANIKA','description'=>'Zaštita ponavljača kvačila koja sprječava klizanje lanca na lančaniku. Odgovara Beta RR 2T 2013-2025 i RR 4T 2010-2019.','price'=>'87.46','type'=>'Enduro','tag'=>'Zaštita okvira','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/174218_beta_3_1276.jpg?v=1727183158','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/beta_1_1276-2.jpg?v=1727183159','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/beta_2_1276.jpg?v=1727183159']],
  ['title'=>'BOANO ALUMINIJSKA ZAŠTITA MOTORA 2018/2019','description'=>'Visokočvrsta aluminijska zaštita motora od 3mm za 250/300 2S modele 2018/2019.','price'=>'118.45','type'=>'Enduro','tag'=>'Zaštita okvira','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/2018112192252.jpg?v=1727600580']],
  ['title'=>'BOANO ČEP RADIJATORA 2.0 BAR','description'=>'Čep radijatora od 2 bara.','price'=>'31.42','type'=>'Enduro','tag'=>'Radijator','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-2.jpg?v=1727599768']],
  ['title'=>'BOANO CRVENI ČEP ULJA 2013-17','description'=>'Crveni čep ulja za Beta modele 2013-2017.','price'=>'18.85','type'=>'Enduro','tag'=>'Vijci i čepovi','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-8.png?v=1727600036']],
  ['title'=>'KARBONSKI POKLOPAC KVAČILA RR 2T 2013-2017','description'=>'Karbonski poklopac kvačila za Beta RR 2T 2013-2017.','price'=>'81.36','type'=>'Enduro','tag'=>'Poklopci kvačila','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-30.jpg?v=1727967113']],
  ['title'=>'CROSSPRO ZAŠTITA MOTORA BETA RR 250/300 2T 2013 - ICE POLISCH','description'=>'Beta RR 2T 250 (250cc, 2013-2017) i RR 2T 300 (300cc, 2013-2017).','price'=>'124.77','type'=>'Enduro','tag'=>'Zaštita okvira','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-27.jpg?v=1727894131']],
  ['title'=>'CROSSPRO ZAŠTITA MOTORA BETA RR 250/300 2T 2013 - CRVENA','description'=>'Beta RR 2T 250 (250cc, 2013-2017) i RR 2T 300 (300cc, 2013-2017).','price'=>'137.24','type'=>'Enduro','tag'=>'Zaštita okvira','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-28.jpg?v=1727894703']],
  ['title'=>'TRAXY CIJEV 18-02 HEAVY DUTY OJAČANI VENTIL','description'=>'Rezervni dio.','price'=>'25.64','type'=>'Enduro','tag'=>'Traxy','images'=>['https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-12.png?v=1727964164']],
];

// ─── PROCESS ONE PRODUCT ──────────────────────────────────────────────────────
$total         = count( $products );
$progress_file = __DIR__ . '/import-progress.txt';
$index         = file_exists( $progress_file ) ? (int) trim( file_get_contents( $progress_file ) ) : 0;
$pass          = htmlspecialchars( $_GET['pass'] );
$base_url      = '?pass=' . $pass;
$done          = $index >= $total;
$result        = null;

if ( ! $done ) {
    $result = silvester_process_product( $products[ $index ] );
    file_put_contents( $progress_file, $index + 1 );
}

$percent  = $total > 0 ? round( ( min( $index + 1, $total ) / $total ) * 100 ) : 100;
$next_url = $base_url . '&t=' . time();

?><!DOCTYPE html>
<html lang="hr">
<head>
<meta charset="UTF-8">
<title>Import proizvoda</title>
<?php if ( ! $done ) : ?>
<meta http-equiv="refresh" content="1;url=<?php echo $next_url; ?>">
<?php endif; ?>
<style>
  body { font-family: monospace; background: #111; color: #ccc; padding: 20px; }
  h2 { color: #fff; }
  .bar-wrap { background: #333; border-radius: 6px; height: 24px; margin: 16px 0; }
  .bar { background: #c0392b; height: 24px; border-radius: 6px; transition: width .3s; }
  .done { color: #2ecc71; font-size: 1.4em; }
  .log { color: #aaa; font-size: .85em; margin-top: 4px; }
  .status-created { color: #2ecc71; }
  .status-updated { color: #f39c12; }
  .status-skipped { color: #7f8c8d; }
  a { color: #e74c3c; }
</style>
</head>
<body>
<h2>Uvoz WooCommerce proizvoda</h2>

<?php if ( $done ) : ?>
  <p class="done">✓ Svi <?php echo $total; ?> proizvoda su uvezeni!</p>
  <p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">Idi na proizvode →</a></p>
  <?php @unlink( $progress_file ); ?>
<?php else : ?>
  <p>
    <strong><?php echo $index + 1; ?> / <?php echo $total; ?></strong> —
    <span class="status-<?php echo $result['status']; ?>">
      <?php echo esc_html( $products[ $index ]['title'] ); ?>:
      <?php echo esc_html( $result['msg'] ); ?>
    </span>
  </p>
  <?php if ( ! empty( $result['logs'] ) ) : ?>
    <?php foreach ( $result['logs'] as $log ) : ?>
      <div class="log">↳ <?php echo esc_html( $log ); ?></div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="bar-wrap"><div class="bar" style="width:<?php echo $percent; ?>%"></div></div>
  <p><?php echo $percent; ?>% završeno — sljedeći za 1s…</p>
  <p><small><a href="<?php echo $next_url; ?>">Klikni ako se ne učitava automatski</a></small></p>
<?php endif; ?>
</body>
</html>
