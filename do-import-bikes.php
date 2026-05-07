<?php
/**
 * Bikes importer — matches existing by SKU/title, creates new, adds Beta brand.
 * Access: http://silvester-shop.test/do-import-bikes.php?pass=silvester2024
 * Delete when done!
 */
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );
require_once __DIR__ . '/wp-load.php';

function bikes_cat( $name, $parent = 0 ) {
    $t = get_term_by( 'name', $name, 'product_cat' );
    if ( $t ) return $t->term_id;
    $r = wp_insert_term( $name, 'product_cat', [ 'parent' => $parent ] );
    return is_wp_error( $r ) ? 0 : $r['term_id'];
}
function bikes_beta_brand() {
    static $id;
    if ( $id ) return $id;
    $t = get_term_by( 'name', 'Beta', 'product_brand' ) ?: get_term_by( 'slug', 'beta', 'product_brand' );
    if ( $t ) { $id = $t->term_id; return $id; }
    $r = wp_insert_term( 'Beta', 'product_brand' );
    $id = is_wp_error( $r ) ? 0 : $r['term_id'];
    return $id;
}
function bikes_attach_image( $url, $pid ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $resp = wp_remote_get( $url, [ 'timeout' => 15, 'user-agent' => 'Mozilla/5.0' ] );
    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) return 0;
    $body = wp_remote_retrieve_body( $resp );
    if ( strlen( $body ) < 100 ) return 0;
    $ext = strtolower( pathinfo( parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
    if ( ! in_array( $ext, ['jpg','jpeg','png','gif','webp'] ) ) {
        $ct  = strtok( wp_remote_retrieve_header( $resp, 'content-type' ), ';' );
        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][trim($ct)] ?? 'jpg';
    }
    $u = wp_upload_dir();
    $fn = $pid . '_bike.' . $ext; $fp = $u['path'] . '/' . $fn;
    file_put_contents( $fp, $body );
    $ft = wp_check_filetype( $fn );
    if ( ! $ft['type'] ) return 0;
    $aid = wp_insert_attachment( ['post_mime_type'=>$ft['type'],'post_title'=>sanitize_file_name($fn),'post_content'=>'','post_status'=>'inherit'], $fp, $pid );
    if ( is_wp_error( $aid ) ) return 0;
    wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $fp ) );
    return $aid;
}
$products = [
  ['BETA ALP 4.0 4T 2024 CRVENI','BETA ALP 4.0 4T 2024 RED','034.85.010.00.97','6100.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Alp4.0-red-left-2_7ffe1cfe-e6e8-435c-8a7a-74eb824c2789.jpg?v=1776582883','The moment has finally come – the Beta family is growing, bringing two new models in the on-off and scrambler sector to the market. Inspired by the historic Alp 4.0, Betamotor technicians have designed and developed two completely new versions of the'],
  ['BETA ALP 4.0 4T 2024 BIJELI','BETA ALP 4.0 4T 2024 WHITE','034.85.010.00.51','6100.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/ALP-4.0-white-side-2_ca24d4f9-efe1-4c69-88f3-2d7f863d8eee.jpg?v=1776582871','The moment has finally come – the Beta family is growing, bringing two new models in the on-off and scrambler sector to the market. Inspired by the historic Alp 4.0, Betamotor technicians have designed and developed two completely new versions of the'],
  ['BETA ALP X 4T TAMNO SIVI','BETA ALP X 4T DARK GREY','048.85.010.00.18','6100.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/AlpX-side-2_1811c6ba-be85-41ef-9e24-73a6df1d7e80.jpg?v=1776582842','The moment has finally come – the Beta family is growing, bringing two new models in the on-off and scrambler sector to the market. Inspired by the historic Alp 4.0, Betamotor technicians have designed and developed two completely new versions of the'],
  ['BETA ALP X 4T SVIJETLO SIVI','BETA ALP X 4T LIGHT GREY','048.85.010.00.80','6100.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/AlpX-left_9f495390-ce18-492d-9e3f-765beae41b01.jpg?v=1776583946','The moment has finally come – the Beta family is growing, bringing two new models in the on-off and scrambler sector to the market. Inspired by the historic Alp 4.0, Betamotor technicians have designed and developed two completely new versions of the'],
  ['BETA EVO 2T 80 JUNIOR MY2025','BETA EVO 2T 80 JUNIOR MY2025','012.80.923.00.01','4400.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/EVO-jr-MY25_side_4a78d0b5-d9cf-42fb-a1dd-4d222c72b6c6.jpg?v=1776584315','The 2023 racing season is drawing to a close, and the Beta Factory Trial Team is determined to achieve the best possible results with its riders, who are competing in all World Championship categories: like Matteo Grattarola, who is proving his abili'],
  ['BETA EVO 300 4T MY2025','BETA EVO 300 4T MY2025','008.86.883.00.01','9100.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/EVO-4T-300-MY24-2_9e6b4ce9-316d-4acd-9152-5bcedb6b3141.jpg?v=1776584310','Beta is launching the new EVO My 2025 with a continued development that has helped the EVO line in becoming the best-selling trials model in the world. Beta engineers have continued the development of the EVO range for My 2025 with an all-new suspens'],
  ['BETA MINI CROSS E 12"','BETA MINI CROSS E 12"','039.80.006.00.00','2100.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Minicross-E-My-2022_MOD_0ab3f57c-d454-4442-bc9d-45a3e5324eff.jpg?v=1776582953','Beta launches the second generation of the Minicross-E, the small electric motorcycle designed for riders of tomorrow. Designed for children who love two wheels, this is the best moped to get closer to off-road world with reasonable gradualness. The '],
  ['BETA MINITRIAL 16"','BETA MINITRIAL 16"','038.80.010.00.00','2000.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Minitrial16-MY2021_Gallery_32172072-aa97-4a7b-999e-b01de7b8a9c7.jpg?v=1776582968','Now available, the first youth electric trial bike designed and manufactured by a leading manufacturer of off road motorcycles. The proper riding position, weight, braking, throttle control, and suspension are very important for the young rider. The '],
  ['BETA MINITRIAL 20"','BETA MINITRIAL 20"','038.80.011.00.00','2050.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Minitrial20-MY2021_Gallery_a41680e0-204f-4d85-bae2-d122f3b5e77f.jpg?v=1776582971','Now available, the first youth electric trial bike designed and manufactured by a leading manufacturer of off road motorcycles. The proper riding position, weight, braking, throttle control, and suspension are very important for the young rider. The '],
  ['BETA MINITRIAL 20" XL','BETA MINITRIAL 20" XL','038.80.012.00.00','3400.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/MinitrialXL-MY2021_Gallery_52173a0a-f007-481d-9317-4f740c41bebb.jpg?v=1776582976','Now available, the first youth electric trial bike designed and manufactured by a leading manufacturer of off road motorcycles. The proper riding position, weight, braking, throttle control, and suspension are very important for the young rider. The '],
  ['BETA RR 125 2T RACE MY2026','BETA RR 125 2T RACE MY2026','041.87.605.00.01','9350.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-Race-125-2T-My26-scaled_2dc33b1d-d4f5-4c61-bccb-d898c606759b.jpg?v=1776586140','It’s RACE time! RR Race bikes represent the race versions of Beta’s Enduro models, characterized by a series of modifications to the engine, suspension, frame and equipment, making the bikes ideal for giving their best on the track – always in the si'],
  ['BETA RR 125 2T X-PRO MY2026','BETA RR 125 2T X-PRO MY2026','041.87.600.00.01','8650.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/View_01_RR-X-PRO-2T-125_b8e6b220-c536-410b-814d-00f76836c57f.jpg?v=1776586034','Now in their twentieth year of production, the Beta RR models have been transformed into a new breed of enduro bikes. Introducing the all new range of 2T and 4T models known as RR X-Pro! Not to be confused with the older brother Race Edition models, '],
  ['BETA RR 125 4T R ENDURO','BETA RR 125 4T R ENDURO','043.85.020.00.01','5350.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-125-4T-R-scaled_6fc57615-346c-4e07-bb3c-93493e793523.jpg?v=1776585931','An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces the needs of an ever-growing audience.Potential Beta RR 125 4 Stroke customers are various, from young riders making their first moves in the motorcycling world to a mo'],
  ['BETA RR 125 4T R MOTARD CRNI','BETA RR 125 4T R MOTARD BLACK','043.85.024.00.59','5350.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-4T-125-R-Black-3-scaled_63c86134-32d2-4a28-bc76-5f7af97b58a1.jpg?v=1776585966','An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces the needs of an ever-growing audience.Potential Beta RR 125 4 Stroke customers are various, from young riders making their first moves in the motorcycling world to a mo'],
  ['BETA RR 125 4T R MOTARD PLAVI','BETA RR 125 4T R MOTARD BLUE','043.85.024.00.01','5350.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-125-4T-R-1-scaled_9d2941e7-425c-4066-8944-32c62bb58263.jpg?v=1776585956','An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces the needs of an ever-growing audience.Potential Beta RR 125 4 Stroke customers are various, from young riders making their first moves in the motorcycling world to a mo'],
  ['BETA RR 125 4T T ENDURO CRNI','BETA RR 125 4T T ENDURO BLACK','051.85.020.00.59','4850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/BETA-2025-125T-NERA-scaled_2973e29b-6dcf-4cdc-9b95-aac66840a4ad.jpg?v=1776585924','An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces the needs of an ever-growing audience.Potential Beta RR 125 4 Stroke customers are various, from young riders making their first moves in the motorcycling world to a mo'],
  ['BETA RR 125 4T T ENDURO CRVENI','BETA RR 125 4T T ENDURO RED','051.85.020.00.97','4850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/BETA-2025-125T-ROSSA-scaled_aa30fcd9-72bf-4edb-9b79-01aee2e811e4.jpg?v=1776585916','An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces the needs of an ever-growing audience.Potential Beta RR 125 4 Stroke customers are various, from young riders making their first moves in the motorcycling world to a mo'],
  ['BETA RR 125 4T T ENDURO BIJELI','BETA RR 125 4T T ENDURO WHITE','051.85.010.00.51','4850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-125-4T-Enduro-T-White-side_66ad4ca5-1b2a-4ee3-b968-3b8f33c6f24c.jpg?v=1776583908','An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces the needs of an ever-growing audience.Potential Beta RR 125 4 Stroke customers are various, from young riders making their first moves in the motorcycling world to a mo'],
  ['BETA RR 125 4T T ENDURO X','BETA RR 125 4T T ENDURO X','051.87.020.00.31','4850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-125-Enduro-X-Special-Edition_a3bdd301-3319-4d04-8dd1-f5841fd42dba.jpg?v=1776585928','Beta presents a special edition in pure monochrome style!A completely new colour scheme featuring pastel grey plastics and a set of matt grey and glossy black graphics. An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces'],
  ['BETA RR 125 4T T MOTARD CRNI','BETA RR 125 4T T MOTARD BLACK','051.85.021.00.59','4850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-125-4T-T-NERA-1-scaled_2db7d419-0af4-475d-8657-c92afe94209a.jpg?v=1776585944','An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces the needs of an ever-growing audience.Potential Beta RR 125 4 Stroke customers are various, from young riders making their first moves in the motorcycling world to a mo'],
  ['BETA RR 125 4T T MOTARD CRVENI','BETA RR 125 4T T MOTARD RED','051.85.021.00.97','4850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-125-4T-T-ROSSA-scaled_cae9b0f9-9c47-4e36-8b70-ff53981ebcaa.jpg?v=1776585936','An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces the needs of an ever-growing audience.Potential Beta RR 125 4 Stroke customers are various, from young riders making their first moves in the motorcycling world to a mo'],
  ['BETA RR 125 4T T MOTARD X','BETA RR 125 4T T MOTARD X','051.85.021.00.31','4850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-125-Motard-X-Special-Edition_ad571f83-6c30-476d-bd09-e16b0be78d70.jpg?v=1776585953','Beta presents a special edition in pure monochrome style!A completely new colour scheme featuring pastel grey plastics and a set of matt grey and glossy black graphics. An increasingly comprehensive and diversified Beta RR 125 4 Stroke range embraces'],
  ['BETA RR 200 2T RACE MY2026','BETA RR 200 2T RACE MY2026','041.87.606.00.01','9600.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-Race-200-2T-My26-scaled_a199322e-c1df-4f9a-a684-ab2c29061d7f.jpg?v=1776586151','It’s RACE time! RR Race bikes represent the race versions of Beta’s Enduro models, characterized by a series of modifications to the engine, suspension, frame and equipment, making the bikes ideal for giving their best on the track – always in the si'],
  ['BETA RR 200 2T X-PRO MY2026','BETA RR 200 2T X-PRO MY2026','041.87.601.00.01','8950.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/View_01_RR-X-PRO-2T-200_a52d413a-c13d-44b4-8b40-5c4efcf24a16.jpg?v=1776586043','“Ride like a X-PRO”: introduced last year, the new made-in-Beta way to enjoy Enduro was a hit among riders of all levels. With MY26, X-PRO becomes even more appealing with updates that make a difference. Expert users appreciated its universal feel, w'],
  ['BETA RR 250 2T RACE MY2026','BETA RR 250 2T RACE MY2026','040.87.605.00.01','10000.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-Race-300-2T-My26-scaled_0dc49502-f69c-4780-bf4f-ea203544a85f.jpg?v=1776586145','RR Race bikes represent the race versions of Beta’s Enduro models, characterized by a series of modifications to the engine, suspension, frame and equipment, making the bikes ideal for giving their best on the track – always in the sign of RideAbilit'],
  ['BETA RR 250 2T X-PRO MY2026','BETA RR 250 2T X-PRO MY2026','040.87.600.00.01','9200.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/View_02_RR-X-PRO-2T-250300-2_ff4dfa0a-82bb-4018-a1f9-fc5a8f363770.jpg?v=1776586062','“Ride like a X-PRO”: introduced last year, the new made-in-Beta way to enjoy Enduro was a hit among riders of all levels. With MY26, X-PRO becomes even more appealing with updates that make a difference. Expert users appreciated its universal feel, w'],
  ['BETA RR 300 2T RACE MY2026','BETA RR 300 2T RACE MY2026','040.87.606.00.01','10150.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-Race-300-2T-My26-scaled_09d86bae-71f1-48dc-8d35-aab7e53d7d90.jpg?v=1776586155','RR Race bikes represent the race versions of Beta’s Enduro models, characterized by a series of modifications to the engine, suspension, frame and equipment, making the bikes ideal for giving their best on the track – always in the sign of RideAbilit'],
  ['BETA RR 300 2T X-PRO MY2026','BETA RR 300 2T X-PRO MY2026','040.87.601.00.01','9250.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/View_01_RR-X-PRO-2T-250300-2_c3b97378-76bf-436f-91d0-659c005ac83d.jpg?v=1776586069','“Ride like a X-PRO”: introduced last year, the new made-in-Beta way to enjoy Enduro was a hit among riders of all levels. With MY26, X-PRO becomes even more appealing with updates that make a difference. Expert users appreciated its universal feel, w'],
  ['BETA RR 350 4T RACE MY2026','BETA RR 350 4T RACE MY2026','037.87.605.00.01','10600.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-Race-350-4T-My26-1-scaled_483abdc2-6204-4941-bb16-ba2b4fbff5a8.jpg?v=1776586116','RR Race bikes represent the race versions of Beta’s Enduro models, characterized by a series of modifications to the engine, suspension, frame and equipment, making the bikes ideal for giving their best on the track – always in the sign of RideAbilit'],
  ['BETA RR 350 4T X-PRO MY2026','BETA RR 350 4T X-PRO MY2026','037.87.600.00.01','10000.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/View_01_RR-X-PRO-4T-2_a390655e-5bac-4969-ab0b-0f99630245c2.jpg?v=1776585986','Now in their twentieth year of production, the Beta RR models have been transformed into a new breed of enduro bikes. Introducing the all new range of 2T and 4T models known as RR X-Pro! Not to be confused with the older brother Race Edition models, '],
  ['BETA RR 390 4T RACE MY2026','BETA RR 390 4T RACE MY2026','037.87.606.00.01','10600.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-Race-350-4T-My26-1-scaled_483abdc2-6204-4941-bb16-ba2b4fbff5a8.jpg?v=1776586116','RR Race bikes represent the race versions of Beta’s Enduro models, characterized by a series of modifications to the engine, suspension, frame and equipment, making the bikes ideal for giving their best on the track – always in the sign of RideAbilit'],
  ['BETA RR 390 4T X-PRO MY2026','BETA RR 390 4T X-PRO MY2026','037.87.601.00.01','10049.99','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/View_01_RR-X-PRO-4T-2_a390655e-5bac-4969-ab0b-0f99630245c2.jpg?v=1776585986','Now in their twentieth year of production, the Beta RR models have been transformed into a new breed of enduro bikes. Introducing the all new range of 2T and 4T models known as RR X-Pro! Not to be confused with the older brother Race Edition models, '],
  ['BETA RR 430 4T RACE MY2026','BETA RR 430 4T RACE MY2026','037.87.607.00.01','10599.99','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-Race-350-4T-My26-1-scaled_483abdc2-6204-4941-bb16-ba2b4fbff5a8.jpg?v=1776586116','RR Race bikes represent the race versions of Beta’s Enduro models, characterized by a series of modifications to the engine, suspension, frame and equipment, making the bikes ideal for giving their best on the track – always in the sign of RideAbilit'],
  ['BETA RR 430 4T X-PRO MY2026','BETA RR 430 4T X-PRO MY2026','037.87.602.00.01','10200.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/View_02_RR-X-PRO-4T-2_59fa24eb-80d4-4428-a028-5eabec0a93b4.jpg?v=1776585988','Now in their twentieth year of production, the Beta RR models have been transformed into a new breed of enduro bikes. Introducing the all new range of 2T and 4T models known as RR X-Pro! Not to be confused with the older brother Race Edition models, '],
  ['BETA RR 480 4T RACE MY2026','BETA RR 480 4T RACE MY2026','037.87.608.00.01','10599.99','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-Race-350-4T-My26-1-scaled_483abdc2-6204-4941-bb16-ba2b4fbff5a8.jpg?v=1776586116','RR Race bikes represent the race versions of Beta’s Enduro models, characterized by a series of modifications to the engine, suspension, frame and equipment, making the bikes ideal for giving their best on the track – always in the sign of RideAbilit'],
  ['BETA RR 480 4T X-PRO MY2026','BETA RR 480 4T X-PRO MY2026','037.87.603.00.01','10200.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/View_02_RR-X-PRO-4T-2_59fa24eb-80d4-4428-a028-5eabec0a93b4.jpg?v=1776585988','Now in their twentieth year of production, the Beta RR models have been transformed into a new breed of enduro bikes. Introducing the all new range of 2T and 4T models known as RR X-Pro! Not to be confused with the older brother Race Edition models, '],
  ['BETA RR 50 2T ENDURO CRVENI','BETA RR 50 2T ENDURO RED','046.81.020.00.97','3800.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-red-side-scaled_72ff7cf5-d157-47dc-8ce1-b57903727799.jpg?v=1776585857','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T ENDURO BIJELI','BETA RR 50 2T ENDURO WHITE','046.81.020.00.51','3800.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-white-side-scaled_994dcac0-74e6-4f2a-9732-343d54d1b6b3.jpg?v=1776585855','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T MOTARD CRNI','BETA RR 50 2T MOTARD BLACK','046.81.025.00.59','3750.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-black-side-scaled_2b5a2d2c-8b44-46a2-bf26-4446a2d7127a.jpg?v=1776585896','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T MOTARD BIJELI','BETA RR 50 2T MOTARD WHITE','046.81.025.00.51','3750.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-white-side-1-scaled_638552d2-2bf2-4e8c-9b58-06108a8106c8.jpg?v=1776585893','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T RACING ENDURO','BETA RR 50 2T RACING ENDURO','046.81.020.00.01','4700.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-Race-side-scaled_908b281d-73e3-4e8c-9491-70140c57cc91.jpg?v=1776585867','RR 50 ENDUROInspired by its bigger sisters competing in the WEC World Championship for both technical and aesthetic features, the RR 50 Enduro is born to face all kinds of terrain, from free-time practice to daily commuting. Versions:RR Enduro – Colo'],
  ['BETA RR 50 2T SPORT ENDURO CRVENI','BETA RR 50 2T SPORT ENDURO RED','046.81.021.00.97','4200.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-Sport-Red-side-scaled_1191495f-2966-4a32-a400-cb0674e66688.jpg?v=1776585865','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T SPORT ENDURO BIJELI','BETA RR 50 2T SPORT ENDURO WHITE','046.81.021.00.51','4200.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-Sport-white-side-scaled_f072a8a1-8490-4b71-8255-f7e9c14231d6.jpg?v=1776585860','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T SPORT MOTARD CRNI','BETA RR 50 2T SPORT MOTARD BLACK','046.81.023.00.59','3850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-Sport-black-LS-side-scaled_5f8abbdc-849d-44b4-a0fd-747de99447ba.jpg?v=1776585907','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T SPORT MOTARD LS','BETA RR 50 2T SPORT MOTARD LS','046.81.026.00.59','3850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-Sport-black-LS-side-scaled_5f8abbdc-849d-44b4-a0fd-747de99447ba.jpg?v=1776585907','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T SPORT MOTARD BIJELI','BETA RR 50 2T SPORT MOTARD WHITE','046.81.023.00.51','3850.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-Sport-white-side-1-scaled_1023fdfb-40f6-421a-8b77-0a46b3224057.jpg?v=1776585903','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T TRACK MOTARD CRNI','BETA RR 50 2T TRACK MOTARD BLACK','046.81.024.00.59','4200.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-Track-Black-side-scaled_1ade2510-f8ad-4b62-aa3c-0626a0eb73aa.jpg?v=1776585913','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T TRACK MOTARD PLAVI','BETA RR 50 2T TRACK MOTARD BLUE','046.81.024.00.32','4200.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-2025-Track-Blue-side-scaled-2_0a587e08-5424-4f85-a14c-4c8de8197088.jpg?v=1776585910','Beta believes in the new generation of motorcyclists, turning the dreams of youngsters into stylish two-wheeled realities for generations, and is now ready to unveil the new restyling for its entire 50cc, Enduro and Motard range. A modern, geometric '],
  ['BETA RR 50 2T X MOTARD','BETA RR 50 2T X MOTARD','046.81.025.00.31','3750.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RR-50-X-Special-Edition_59a45a7e-cd80-4090-abb7-3ca9345b6aaa.jpg?v=1776585900','Beta presents a special edition in pure monochrome style!A completely new colour scheme featuring pastel grey plastics and a set of matt grey and glossy black graphics. Beta believes in the new generation of motorcyclists, turning the dreams of young'],
  ['BETA RX 250 2T MY2026','BETA RX 250 2T MY2026','050.87.002.00.01','8990.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RX-350-2T-My-2026-1-scaled_e75cd689-d348-4b76-94b7-5aec258b3569.jpg?v=1776586076','Wanna play with us? Beta lands on a new fun dimension, with the new RX My2026, proving to believe in the MX project like never before. It expands its line and lays new foundations to develop a complete 2- and 4-Stroke model range. Two all new models '],
  ['BETA RX 350 2T MY2026','BETA RX 350 2T MY2026','050.87.003.00.01','9250.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RX-350-2T-My-2026-1-scaled_e75cd689-d348-4b76-94b7-5aec258b3569.jpg?v=1776586076','Wanna play with us? Beta lands on a new fun dimension, with the new RX My2026, proving to believe in the MX project like never before. It expands its line and lays new foundations to develop a complete 2- and 4-Stroke model range. Two all new models '],
  ['BETA RX 450 4T MY2026','BETA RX 450 4T MY2026','049.87.005.00.01','10400.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/RX-4T-My-2026-2-1-scaled_6b678dbb-428e-4f52-aa73-e60fb544bb22.jpg?v=1776586093','Wanna play with us? Beta lands on a new fun dimension, with the new RX My2026, proving to believe in the MX project like never before. It expands its line and lays new foundations to develop a complete 2- and 4-Stroke model range. Two all new models '],
  ['BETA SINCRO 125 2T FACTORY MY2026','BETA SINCRO 125 2T FACTORY MY2026','059.86.050.00.01','7950.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/SincroFactory1252TMy26_3_20468195-cde3-4ada-9a34-a4d17cd025ae.jpg?v=1776587935','The lightest and most agile in the range, capable of combining extreme agility with a top-level “ready-to-race” setup. Every component of the Sincro Factory 2T 125 has been lightened and optimized to guarantee explosiveness and a direct feeling with '],
  ['BETA SINCRO 125 2T MY2026','BETA SINCRO 125 2T MY2026','059.86.000.00.01','7500.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Betamotor-Trial-Sincro-125-2T-editorial-slider-1080x864-1_f6b5a16e-cd7c-4506-a0ad-8cb52b766500.jpg?v=1776587902','The smallest of the SINCRO range. Lightweight and easy to handle, the SINCRO 125 2T is designed for young riders who are moving up from the lower classes and starting to compete at the “big leagues”. A fun bike that sets a new benchmark in its catego'],
  ['BETA SINCRO 250 2T FACTORY MY2026','BETA SINCRO 250 2T FACTORY MY2026','059.86.070.00.01','8150.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/SincroFactory1252TMy26_3_20468195-cde3-4ada-9a34-a4d17cd025ae.jpg?v=1776587935','For those who experience competition as a continuous challenge, in perfect balance between power and rideability. Every component of the Sincro Factory 2T 250 is designed to reduce weight and increase reactivity, ensuring explosiveness and power with'],
  ['BETA SINCRO 250 2T MY2026','BETA SINCRO 250 2T MY2026','059.86.020.00.01','7950.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Betamotor-Trial-Sincro-125-2T-editorial-slider-1080x864-1_f6b5a16e-cd7c-4506-a0ad-8cb52b766500.jpg?v=1776587902','The SINCRO 250 2T is the ideal choice for those looking for a 2-stroke that is easy to handle, easy to control and has a smoother character than the top of the range. It offers solid but always manageable performance, with more linear torque and powe'],
  ['BETA SINCRO 300 2T FACTORY MY2026','BETA SINCRO 300 2T FACTORY MY2026','059.86.080.00.01','8400.01','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/SincroFactory1252TMy26_3_20468195-cde3-4ada-9a34-a4d17cd025ae.jpg?v=1776587935','The undisputed queen of the range. The Sincro Factory 2T 300 represents the maximum expression of Beta technology applied to Trial riding. Thanks to exclusive solutions such as the twin spark and the oversized clutch, it offers an uncompromising raci'],
  ['BETA SINCRO 300 2T MY2026','BETA SINCRO 300 2T MY2026','059.86.030.00.01','8200.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Betamotor-Trial-Sincro-125-2T-editorial-slider-1080x864-1_f6b5a16e-cd7c-4506-a0ad-8cb52b766500.jpg?v=1776587902','The SINCRO 300 2T is the most powerful and complete version of the SINCRO family, designed for experienced riders and those who tackle challenging terrain or high-level competitions. An engine displacement that offers full torque at all speeds, contr'],
  ['BETA SINCRO 300 2T SS MY2026','BETA SINCRO 300 2T SS MY2026','059.86.040.00.01','8200.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Betamotor-Trial-Sincro-125-2T-editorial-slider-1080x864-1_f6b5a16e-cd7c-4506-a0ad-8cb52b766500.jpg?v=1776587902','The SINCRO 300 SS was created to offer all the torque and solidity of the 300 engine, but with smoother and more progressive power delivery at medium and low revs. A motorcycle designed for those who want usable power, smooth riding and maximum ease '],
  ['BETA XTRAINER 250 2T MY2026','BETA XTRAINER 250 2T MY2026','054.87.102.00.01','7600.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/XTRAINER-My2026-1-scaled_0a56b72d-fdce-49f2-9f3b-e69dc8d5df7e.jpg?v=1776585978','As the Beta Xtrainer begins its eleventh year of production, it is being celebrated with a stunning new look featuring grey bodywork to compliment the Italian red frame. The new look separates the look from is cousins, the RR X-Pro and RR Race Editio'],
  ['BETA XTRAINER 300 2T MY2026','BETA XTRAINER 300 2T MY2026','054.87.101.00.01','7600.00','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/XTRAINER-My2026-1-scaled_f612ecc6-70ec-4a83-be48-d5ca4b3fc757.jpg?v=1776585969','As the Beta Xtrainer begins its eleventh year of production, it is being celebrated with a stunning new look featuring grey bodywork to compliment the Italian red frame. The new look separates the look from is cousins, the RR X-Pro and RR Race Editio'],
];
// ── Page ───────────────────────────────────────────────────────────────────────
$index    = max( 0, (int)( $_GET['index'] ?? 0 ) );
$total    = count( $products );
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?pass=silvester2024';

function bikes_html( $body, $refresh = '', $delay = 2 ) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Bikes Import</title>';
    if ( $refresh ) echo '<meta http-equiv="refresh" content="'.$delay.';url='.htmlspecialchars($refresh).'">';
    echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}h2{color:#fff;}a{color:#2196f3;}
    .ok{color:#4caf50;}.skip{color:#888;}.upd{color:#2196f3;}.fail{color:#f44336;}
    .bar-wrap{background:#333;border-radius:4px;height:18px;width:100%;max-width:500px;margin:10px 0;}
    .bar-fill{background:#4caf50;height:18px;border-radius:4px;}
    small{color:#555;font-size:11px;display:block;}</style></head>
    <body><h2>Motocikli — Import</h2>'.$body.'</body></html>';
}

if ( $index >= $total ) {
    bikes_html( '<p class="ok" style="font-size:18px">✓ Svih '.$total.' motocikala uvezeno!</p><p><a href="/wp-admin/edit.php?post_type=product">Pregled →</a></p>' );
    exit;
}

[ $hr_title, $en_title, $sku, $price, $img_url, $desc ] = $products[$index];

$next_url = $base_url . '&index=' . ( $index + 1 );
$pct      = round( ( ( $index + 1 ) / $total ) * 100 );
$cat_id   = bikes_cat( 'Motocikli' );

// Find existing: by SKU → Croatian title → English title → fuzzy LIKE search
$pid  = $sku ? wc_get_product_id_by_sku( $sku ) : 0;
$mode = 'create';
if ( ! $pid ) {
    foreach ( [ $hr_title, $en_title ] as $try_title ) {
        $q = new WP_Query( [ 'post_type'=>'product','title'=>$try_title,'posts_per_page'=>1,'no_found_rows'=>true,'fields'=>'ids' ] );
        if ( $q->have_posts() ) { $pid = $q->posts[0]; $mode = 'update'; break; }
    }
}
// Fuzzy fallback: extract key identifiers (model + cc + type) and do a LIKE search
if ( ! $pid ) {
    preg_match_all( '/\b(RR|RX|EVO|ALP|XTRAINER|SINCRO|MINITRIAL|MINICROSS|\d{2,3}(?:CC)?|2T|4T|RACE|X-PRO|SPORT|TRACK|JUNIOR|FACTORY)\b/i', $en_title, $m );
    $tokens = array_unique( $m[0] );
    if ( count( $tokens ) >= 3 ) {
        global $wpdb;
        // Build query requiring all tokens to appear in title
        $wheres = array_map( fn($t) => $wpdb->prepare( 'post_title LIKE %s', '%' . $wpdb->esc_like($t) . '%' ), $tokens );
        $found = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND " . implode(' AND ', $wheres) . " LIMIT 1"
        );
        if ( $found ) { $pid = (int) $found; $mode = 'update'; }
    }
}

$product = $pid ? wc_get_product( $pid ) : new WC_Product_Simple();
$product->set_name( $hr_title );
$product->set_description( $desc );
$product->set_regular_price( $price );
if ( $sku ) $product->set_sku( $sku );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product->set_manage_stock( false );
$product->set_stock_status( 'instock' );
$product->set_category_ids( array_filter( [ $cat_id ] ) );
$pid = $product->save();

$img_note = '';
if ( $pid && ! is_wp_error( $pid ) ) {
    if ( $img_url && ( $mode === 'create' || ! has_post_thumbnail( $pid ) ) ) {
        $aid = bikes_attach_image( $img_url, $pid );
        if ( $aid ) { set_post_thumbnail( $pid, $aid ); $img_note = ' img✓'; }
        else $img_note = ' img✗';
    }
    $brand_id = bikes_beta_brand();
    if ( $brand_id ) wp_set_object_terms( $pid, [ $brand_id ], 'product_brand' );
    $cls = $mode === 'update' ? 'upd' : 'ok';
    $msg = '['.strtoupper($mode).'] ID:'.$pid.' SKU:'.$sku.$img_note;
} else {
    $cls = 'fail'; $msg = 'Save failed';
}

$bar = '<div class="bar-wrap"><div class="bar-fill" style="width:'.$pct.'%"></div></div><p>'.($index+1).'/'.$total.' ('.$pct.'%)</p>';
bikes_html( $bar.'<p class="'.$cls.'">'.esc_html($hr_title).' — '.esc_html($msg).'</p>', $next_url );
