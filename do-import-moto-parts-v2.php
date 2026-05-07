<?php
/**
 * Moto-parts importer v2 — SKU + subcategory + Beta brand + fresh images.
 * Access: http://silvester-shop.test/do-import-moto-parts-v2.php?pass=silvester2024
 * Delete when done!
 */
if ( empty( $_GET['pass'] ) || $_GET['pass'] !== 'silvester2024' ) die( 'Access denied.' );

define( 'SILVESTER_DATA_ONLY', true );
require_once __DIR__ . '/wp-load.php';
require_once __DIR__ . '/import-products.php'; // populates $products (184 Croatian entries)

// ── Pre-built data map indexed by position: [sku, subcat, fresh_image] ─────────
// Each index corresponds to the same index in $products.
$parts_data = [
  ['037.46.039.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ccd78ebb-7ae0-44c6-9bec-90e40f1a3d4d.jpg?v=1776568920'],
  ['031.46.049.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ab2a4dbb-0b30-47a7-a2da-2db357ea7e85.jpg?v=1776561160'],
  ['017.45.002.80.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c35545ce-18b6-47f3-a232-de7c61fd338a.jpg?v=1776552848'],
  ['20.08236.000','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_37823f23-4e57-425f-85b4-c14c1809d102.jpg?v=1776574677'],
  ['020.45.058.82.00','Hidraulika','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_15941b85-8ad3-4fe1-ad71-181415638537.jpg?v=1776553797'],
  ['024.45.002.00.00','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d75e126f-7d99-47cb-ac28-b5563010a6b2.jpg?v=1776555805'],
  ['034.46.003.82.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_91bed2de-8d66-49c7-9c95-fdaca1ce3283.jpg?v=1776584134'],
  ['20.11227.000','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e41c35e0-7ca2-4eae-b557-d36af03ea402.jpg?v=1776574786'],
  ['007.45.046.80.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8d04decd-68c0-4c03-b83b-901b54e53b3e.jpg?v=1776551011'],
  ['031.46.038.82.00','Kočnice','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d8c4e74b-d06c-4c6d-acce-342fa5323b42.jpg?v=1776561851'],
  ['037.42.009.82.00','Kočnice','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_5606f6a5-780f-44db-b23b-08f922b9a986.jpg?v=1776564693'],
  ['007.42.028.00.00','Kočnice','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ac7215b4-c05d-4988-9ace-139ceb16dbd8.jpg?v=1776550293'],
  ['007.42.006.00.00','Kočnice','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b794508f-f19d-4dbd-84de-575e351e1aaa.jpg?v=1776550261'],
  ['037.46.006.82.00','Plin i usisni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_af169a2b-d9ce-4152-8553-bdec4f12b9c9.jpg?v=1776562569'],
  ['037.46.048.82.53','Hidraulika','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_450757cb-406a-4d6b-b758-339fba37f8c5.jpg?v=1776582293'],
  ['031.46.008.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a6c4670f-9d69-4d27-9576-78b8412ca6c8.jpg?v=1776557889'],
  ['007.46.014.82.59','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_713af846-25f3-42a0-9d74-dd0444fd445b.jpg?v=1776561559'],
  ['007.46.014.82.53','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_15f3a92e-08d1-4ba7-be3e-c83b07f87db7.jpg?v=1776561562'],
  ['029.03.008.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_579c45d3-3528-40c4-8753-cfc722877f21.jpg?v=1776564764'],
  ['12.91910.053','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f5726aa8-1a01-4517-9e8b-a82f6a04a71c.jpg?v=1776568629'],
  ['026.46.007.82.53','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4c1b5058-631c-402b-b3f4-1fd01fdb2ec3.jpg?v=1776556721'],
  ['007.46.006.82.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c77aa6a0-eb13-4a33-96b0-e4b396c7291a.jpg?v=1776551088'],
  ['026.45.128.82.53','Hidraulika','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_dc84e04c-d91f-42f3-8621-a625109c1db8.jpg?v=1776582295'],
  ['16.61761.100','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_41f7e01f-8aa1-4878-b110-7ac58a1e6479.jpg?v=1776573666'],
  ['024.43.079.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4b35ea7d-9386-4257-967a-a542fa8e078f.jpg?v=1776562834'],
  ['037.43.070.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f83857f5-69fb-46e9-a94e-49e2f19ec910.jpg?v=1776562900'],
  ['007.43.226.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_62cfc639-a11a-4f40-99de-9ab0b5a2b64d.jpg?v=1776565007'],
  ['007.43.123.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_eac8ac02-9f55-4437-baea-08092daf0384.jpg?v=1776561101'],
  ['007.43.198.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_95659c1f-0ad9-483e-996f-84b01ee40fe3.jpg?v=1776563860'],
  ['024.43.047.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ecfea8e1-1545-40db-ad38-49b2f459d751.jpg?v=1776561591'],
  ['046.43.023.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4c6537ce-da30-48bf-95d0-b5c4ae7bca26.jpg?v=1776564790'],
  ['037.43.103.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f48e9848-919f-45e4-b499-401878c74934.jpg?v=1776563464'],
  ['043.46.002.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_39d6c2e3-cc53-4bcc-8bee-0dc1ce47c0fa.jpg?v=1776564798'],
  ['031.46.041.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d11f381f-3e8a-44cb-bc80-dadebe75fb5c.jpg?v=1776557933'],
  ['031.46.040.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_dd15465e-23b7-42b5-84f1-6db1feb2ea36.jpg?v=1776557931'],
  ['037.46.026.82.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4e9982ba-fef5-4547-8aa2-10a0996afbc6.jpg?v=1776563171'],
  ['20.11807.100','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c39947cd-87ec-4e5e-a4a4-f12002fa7150.jpg?v=1776574796'],
  ['037.46.002.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_7f2ca7c0-be9e-49e7-b8ad-df4247e74100.jpg?v=1776562330'],
  ['040.46.000.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d4c0bdbb-c867-45bd-807d-24b5835e453b.jpg?v=1776562332'],
  ['037.46.036.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8fc2d119-536b-492d-8834-e260e62c5285.jpg?v=1776565195'],
  ['043.32.018.82.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_227f5be3-121b-4625-b0d3-0442adab261d.jpg?v=1776564793'],
  ['007.45.034.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_145c3619-1dc9-4052-b817-4717df9cff08.jpg?v=1776550995'],
  ['007.45.042.80.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_424faef8-8697-4d4c-8c28-c66720ed6318.jpg?v=1776551002'],
  ['007.45.044.80.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ef591267-88be-4f3a-90b5-5f00e7dfaad4.jpg?v=1776551008'],
  ['007.45.031.82.00','Upravljač','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_15ab004a-58a3-4196-ae83-263b6db85d9c.jpg?v=1776550984'],
  ['007.45.035.80.00','Hidraulika','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_bb487b88-d76b-4429-bd0f-41bb7ad40f44.jpg?v=1776551000'],
  ['007.46.005.82.59','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_90f9092d-8013-4658-9a9a-d69b8256b9ee.jpg?v=1776551085'],
  ['001.04.010.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_aa4e8f8d-88fd-4ced-9938-0fba7d0fd2e7.jpg?v=1776547788'],
  ['007.45.033.82.00','Upravljač','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e2119e0d-947d-457d-af14-bc06c3251bf7.jpg?v=1776550989'],
  ['007.45.033.82.59','Upravljač','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_3a83f5c3-d25b-4236-b04a-5365c9341dc9.jpg?v=1776550991'],
  ['007.46.002.82.61','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e873ac88-269f-411d-87d9-b1491756bfbe.jpg?v=1776551081'],
  ['007.46.002.82.59','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_dad62dcb-b1b2-48f7-94a3-339caaa9865c.jpg?v=1776551078'],
  ['16.61761.020','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_6b611e97-128c-4023-8762-90db28ebea55.jpg?v=1776573661'],
  ['16.61761.030','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_de119247-f528-4858-8d20-86258c963317.jpg?v=1776573663'],
  ['16.61761.000','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_207a7f22-fe10-42aa-8393-2d85b797dae1.jpg?v=1776573658'],
  ['036.46.004.82.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8413a293-7020-49ea-9937-d2ff95871fb0.jpg?v=1776579854'],
  ['037.46.032.82.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8082243e-72db-4dbf-a63d-3c913d961c4e.jpg?v=1776563330'],
  ['037.46.033.82.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_06e5e401-9f57-4635-a78a-fc3ab6d79728.jpg?v=1776563332'],
  ['037.46.034.82.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_bc0667c2-887c-496a-898d-d9f3d3848e4c.jpg?v=1776563335'],
  ['037.46.045.82.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_08cbbbc8-1855-44b0-ab2f-40ba067d67e5.jpg?v=1776582285'],
  ['040.37.009.00.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0dd8c9b9-5473-4113-beb5-2003c889bccc.jpg?v=1776564958'],
  ['007.46.018.82.59','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c1050dd5-843a-4222-a95f-caf21dd59934.jpg?v=1776564557'],
  ['007.46.018.82.53','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a9c56e7a-f68d-4cbf-93f5-e0cae8d3e05c.jpg?v=1776564555'],
  ['037.46.025.82.53','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e2ad4342-7c67-429c-9512-3b379e33601f.jpg?v=1776564748'],
  ['037.46.025.82.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_77e818fc-1287-4176-bd00-d6aef0affbbf.jpg?v=1776563138'],
  ['037.34.090.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_221c47f4-404b-4414-9cbb-aabfab804871.jpg?v=1776581409'],
  ['034.46.017.00.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c802c235-7882-453d-a35a-01fd0621c826.jpg?v=1776582591'],
  ['036.46.002.82.61','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f946ac65-b21f-4dc0-9792-fcf793e732bd.jpg?v=1776564849'],
  ['026.34.075.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e3bd846a-acbf-46aa-a6e6-0d746f7829b0.jpg?v=1776561957'],
  ['007.45.050.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_7f8e4ca4-0421-40a3-8f2f-c0073aa61489.jpg?v=1776558906'],
  ['007.46.022.00.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_07270ca4-570e-49e2-b8d0-2fedc99d7288.jpg?v=1776580937'],
  ['037.46.011.82.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0aca69cc-ce65-4673-a2e0-f163a3c78791.jpg?v=1776563031'],
  ['049.46.000.82.97','Upravljač','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9c4ebc7f-8e74-4d04-b103-0c7eaf1d8276.jpg?v=1776580997'],
  ['037.35.000.00.53','Upravljač','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_79a0fadb-5850-4051-8123-1a32d35ab5b2.jpg?v=1776562805'],
  ['22.20391.000','Upravljač','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_33eaab40-4fb5-433e-b63e-d47fbf8dc103.jpg?v=1776575560'],
  ['020.45.013.80.00','Upravljač','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4cca3da4-1886-44b1-bc55-cb598b41e0bb.jpg?v=1776553786'],
  ['037.43.037.80.97','Plastika','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_3ffe2b9c-3605-4961-b5ac-8d018f15ad83.jpg?v=1776565388'],
  ['049.46.004.82.97','Plastika','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ca6af704-76d4-4c47-9391-a52f7ba12693.jpg?v=1776581495'],
  ['037.43.037.80.51','Plastika','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_69095628-b228-41a1-9523-a5634300f632.jpg?v=1776562704'],
  ['026.46.012.82.00','Plin i usisni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4d6606cd-40df-481d-8360-512469947b4c.jpg?v=1776563211'],
  ['007.46.020.00.32','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_aecfdf19-b17c-40f4-accf-12c71a59a949.jpg?v=1776564840'],
  ['046.46.002.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f7d55095-034b-467b-8bbb-70dc166b7332.jpg?v=1776565026'],
  ['046.46.001.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_22e961d4-37a3-4b32-811e-9eb3e7807a5d.jpg?v=1776565023'],
  ['040.46.017.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_81bca3bb-8175-4e3a-a752-5f19ac72c0c2.jpg?v=1776582411'],
  ['033.11.025.00.50','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/download_3_60cdeff0-a816-42f3-a299-cd904e0a819a.jpg?v=1776587967'],
  ['007.45.047.80.00','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_166236ab-715d-4ec1-979f-0412089da191.jpg?v=1776551013'],
  ['031.46.009.82.97','Kotači i gume','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4e72cfa5-e8a0-4181-b884-56b34dfb89aa.jpg?v=1776557892'],
  ['031.46.029.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_3d84c780-f234-45da-b04e-0d9b1305cbec.jpg?v=1776557914'],
  ['031.46.028.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_79d2f418-1f4f-4947-ae0f-a83440256a9b.jpg?v=1776557912'],
  ['007.46.015.82.00','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_6495f820-bfe1-4c9c-a46a-9843a542463c.jpg?v=1776561588'],
  ['040.46.018.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_502f1ca4-7034-4380-8a1d-37f71084d6ab.jpg?v=1776582419'],
  ['007.45.043.80.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_95a49201-0cbb-457c-ba86-36ac29d39240.jpg?v=1776551005'],
  ['026.46.001.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b1b99ac0-a9e0-4dfb-a202-630557963de1.jpg?v=1776556716'],
  ['031.46.024.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_29c333c4-baaa-40b0-b187-e12760b69237.jpg?v=1776557907'],
  ['026.46.006.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4e8fdfc8-2646-4e16-9e8f-6494646d6436.jpg?v=1776556718'],
  ['037.40.061.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e7e6a65a-ffcf-4920-9b5c-9e416b481242.jpg?v=1776562779'],
  ['034.46.005.82.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_615f4b71-a376-4fd9-9e23-bfdaefc75910.jpg?v=1776584125'],
  ['037.46.046.82.53','Upravljač','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f9732092-4aa6-45ed-8774-655141c912a7.jpg?v=1776582287'],
  ['29.09601.053','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e1d87198-3c82-419f-869a-a53d5d87c9cc.jpg?v=1776539049'],
  ['026.01.025.80.53','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4f1b816f-e5b2-4fa7-9765-eea8e77bbc78.jpg?v=1776555836'],
  ['026.01.013.80.53','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_2da633d9-e43e-4819-8fce-039feb7d9749.jpg?v=1776555831'],
  ['029.15.001.80.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e5c760e6-ab85-497e-a758-103c796b5e67.jpg?v=1776557037'],
  ['040.46.002.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e803c147-7ae0-43d7-b687-9cf9233f86f6.jpg?v=1776562566'],
  ['035.46.002.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_37a78968-303a-43e9-983c-e8878a8bf94c.jpg?v=1776564562'],
  ['037.15.001.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_b4417a64-aa90-4f81-818d-6ee5dd0db0f1.jpg?v=1776562776'],
  ['037.46.009.82.59','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_2f74a357-f192-4059-a272-50c5f4773426.jpg?v=1776563141'],
  ['034.46.009.82.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_07ddc429-2c15-4bc4-8047-307a89b7cdb8.jpg?v=1776582935'],
  ['037.46.019.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ca56ebfb-6f92-4607-b14f-4a1a361af224.jpg?v=1776562974'],
  ['006.04.050.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1d21dac8-3408-4355-8766-aefe9a640d33.jpg?v=1776548205'],
  ['034.46.008.00.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/034.32.083.42.59_b498be68-0720-4ef6-832b-fc5f86dd6949.jpg?v=1776584129'],
  ['051.45.007.82.53','Hidraulika','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_77eff789-d7e2-43dc-a043-79f45f8cdaec.jpg?v=1776582457'],
  ['031.46.017.80.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_3363eecd-426d-4884-b555-a7b1541adf35.jpg?v=1776557903'],
  ['031.42.046.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_808cd3a3-9ee8-4486-ba7c-627e46e05e56.jpg?v=1776557733'],
  ['026.42.007.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a69a0fee-4f66-4f10-9d11-a84517fb672c.jpg?v=1776556640'],
  ['031.42.047.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_932df160-31c6-4206-95b0-7d906ccb3129.jpg?v=1776557736'],
  ['031.42.013.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_378fae8a-bbf3-408d-b5fd-6055ddd5e3fb.jpg?v=1776557696'],
  ['031.42.048.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f648fd99-9c1a-4971-a2b5-4d979f2fe7ca.jpg?v=1776557738'],
  ['50.10251.053','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8013376a-d0e7-4662-bc27-49ba2b10e55f.jpg?v=1776547612'],
  ['034.46.004.82.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_45fc42ad-b80f-45c5-bcab-e4b182270123.jpg?v=1776584127'],
  ['037.43.027.00.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_353160a8-8190-4420-bdd8-20f311d8bfcb.jpg?v=1776561733'],
  ['026.46.013.82.53','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8919dda5-bf16-4772-b6a1-7b2e75e96e1a.jpg?v=1776563221'],
  ['024.46.002.82.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_793af5fc-7a9d-4b8f-bb20-f5d7b4586b0f.jpg?v=1776555808'],
  ['020.38.005.00.00','Plin i usisni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a2801b81-c8d0-4906-b555-99be9a54e0bd.jpg?v=1776553444'],
  ['031.43.064.82.00','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8b334030-7afd-44bb-87c7-93c9c3b435b6.jpg?v=1776557798'],
  ['034.46.001.00.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/034.46.001.00.00_60c71a2c-f1e2-4c1b-a41a-d21a5193ee52.jpg?v=1776584113'],
  ['006.15.006.80.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_2cd9b0d8-ac34-417d-b05c-14c9a784265a.jpg?v=1776548690'],
  ['031.46.048.82.00','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_a4f51df3-028a-4154-bb01-7f4062d5890d.jpg?v=1776560313'],
  ['037.46.020.82.59','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0bbeddb7-8856-4cf4-9572-a3d709aa47a3.jpg?v=1776565434'],
  ['037.46.020.82.00','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_06e13b7c-3f02-43b4-a529-141e8924c5c1.jpg?v=1776562879'],
  ['037.43.066.82.59','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_924f3169-b18a-4fed-8a79-ad5c056bf3be.jpg?v=1776565432'],
  ['037.43.066.82.00','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_219c2475-c70f-43cf-92f8-3a6ea3819448.jpg?v=1776562884'],
  ['020.33.147.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9b8086cb-8014-4c0c-a4af-4758872f77c8.jpg?v=1776553093'],
  ['031.46.013.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_25c9240d-9e6b-4b98-a9f6-22074d9e921c.jpg?v=1776557895'],
  ['031.46.014.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_c7ab0b40-687f-4130-b69a-18d876ecae03.jpg?v=1776557897'],
  ['031.46.031.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_dee3a20f-f115-4961-95a9-1d9d041c5efc.jpg?v=1776557917'],
  ['036.46.003.82.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4d15a28d-8a44-4207-b4d3-e8430d34ada8.jpg?v=1776565327'],
  ['034.46.011.00.26','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_343fb127-b76c-4149-af77-d2bef718279c.jpg?v=1776584137'],
  ['040.46.016.82.00','Ispušni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_33ed38fb-0340-4fe1-9d0c-46a2bf0b270e.jpg?v=1776581412'],
  ['036.33.009.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_8d95a2ee-c31d-42d6-995e-c1f624e4ae69.jpg?v=1776558097'],
  ['024.46.006.82.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_56b468c1-1bb5-4bbf-af73-3f6cfda2283b.jpg?v=1776581232'],
  ['035.34.022.82.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_facf0b9a-5f41-4b21-8510-5c00cb798ea5.jpg?v=1776563047'],
  ['034.33.042.00.51','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_496190f9-70a0-486c-b3c5-814772de8159.jpg?v=1776584141'],
  ['049.46.003.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_77592b3c-2733-487b-9e97-6510103bd79b.jpg?v=1776581239'],
  ['041.33.009.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_3190b39f-7a90-4dd5-958b-a27ed0a19630.jpg?v=1776564900'],
  ['037.46.028.82.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_54fd7aaf-4f2a-4b76-b9c0-850a5ec28e4c.jpg?v=1776563309'],
  ['037.46.029.82.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_542e7d39-5ddd-4756-9100-b5d4dcbc20f8.jpg?v=1776563312'],
  ['020.34.332.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_56b11c24-12e2-4ddc-a337-d6c2ebd77721.jpg?v=1776553283'],
  ['031.46.033.82.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_00122008-d6b5-44b6-9aa1-3faa88856e1a.jpg?v=1776557923'],
  ['020.34.322.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_f4043eb2-c713-4490-a306-10181138b06f.jpg?v=1776553257'],
  ['031.46.034.82.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_36b4f436-07a5-4bf7-ac1a-7afa5a753612.jpg?v=1776557926'],
  ['007.33.022.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_05f2d212-9d76-4e7a-9fa1-8a433a903438.jpg?v=1776549282'],
  ['007.34.008.00.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_086c76ea-8b33-43f2-9972-da84b02d3c43.jpg?v=1776549502'],
  ['036.04.000.00.00','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1f29537e-ed66-40f0-8763-ccb7724a167d.jpg?v=1776558047'],
  ['007.43.194.82.00','Grafike','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_db8aeb13-88a6-48d7-9b65-2cdc836f34c6.jpg?v=1776563608'],
  ['007.42.063.42.53','Prijenos','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_43dc34a5-f122-46b1-940a-b128991f478a.jpg?v=1776565335'],
  ['026.15.000.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9de5086f-7a13-465c-997f-93ef7940340c.jpg?v=1776562810'],
  ['035.46.000.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1a8551dd-65cf-47a9-8200-674a9a914446.jpg?v=1776561183'],
  ['036.15.000.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_20493c4d-b64d-4727-a8e6-6e5fd7c435b3.jpg?v=1776558079'],
  ['006.15.001.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_36021990-4850-49c1-9764-689784e3f15b.jpg?v=1776558817'],
  ['037.15.000.82.00','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_76c6f0d7-d1e6-4af7-a6ce-0f95c5e8b90b.jpg?v=1776562768'],
  ['037.46.047.82.53','Kočnice','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0e1a4e93-4075-4610-83e5-9c10043448ad.jpg?v=1776582290'],
  ['031.40.003.00.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_85acb565-0d0a-4e60-a6d4-2f427d82b724.jpg?v=1776557480'],
  ['034.46.016.00.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/034.46.016.00.00_fc88189b-ae67-46b4-8ac1-942e194ecd90.jpg?v=1776584122'],
  ['034.46.006.00.00','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_9bf9a25f-11bf-43f6-a13d-b1ea06abd6a3.jpg?v=1776584132'],
  ['007.45.062.00.53','Plin i usisni sustav','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_7258b5ee-73e2-40dd-b975-47bb77c010d2.jpg?v=1776551042'],
  ['007.46.016.82.61','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_ef66530c-e885-4744-a3d5-268532eb47bd.jpg?v=1776562913'],
  ['031.46.032.00.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_634796d8-481a-4ad4-a085-dbaca3137437.jpg?v=1776557920'],
  ['026.46.009.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_79cc665d-a708-4baa-a7e0-87f50ce756b5.jpg?v=1776556727'],
  ['037.46.021.82.51','Kotači i gume','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_21d344df-a628-4560-b1ad-9baa2592f136.jpg?v=1776563018'],
  ['026.41.002.82.00','Ovjes','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_cbf775d3-109a-477d-bee9-21ba424c2f10.jpg?v=1776561045'],
  ['031.40.194.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_1f243935-d1c5-4cfe-a7e7-ea56620e71b9.jpg?v=1776563338'],
  ['040.46.006.00.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_d65e4b72-b6ee-422a-86d3-9b349c35dbc7.jpg?v=1776562936'],
  ['037.46.018.00.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_e7954ec2-4ece-47a6-9561-9b95fc19074b.jpg?v=1776562933'],
  ['031.40.081.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_0bb9b04b-1f43-480b-b031-df6a9174437d.jpg?v=1776557592'],
  ['026.46.008.82.00','Električni dijelovi','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/Unknown_4b689fa2-0991-47f7-857f-aacc0945d1fe.jpg?v=1776556723'],
  ['CSBB20-RB','Sjedalo','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/20191218122327.jpg?v=1727182901'],
  ['PRNCP12','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/174218_beta_3_1276.jpg?v=1727183158'],
  ['PMACR-2T-18','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/2018112192252.jpg?v=1727600580'],
  ['CV71531MK','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-2.jpg?v=1727599768'],
  ['TOM01AR','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-8.png?v=1727600036'],
  ['MM003','Motor','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-30.jpg?v=1727967113'],
  ['2CP05901160001','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-27.jpg?v=1727894131'],
  ['2CP05901160007','Okvir','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-28.jpg?v=1727894703'],
  ['TTU18-02-HDRV','Kotači i gume','https://cdn.shopify.com/s/files/1/0737/8164/1483/files/i-12.png?v=1727964164'],
];
// ── Helpers ────────────────────────────────────────────────────────────────────

function mp2_cat( $name, $parent = 0 ) {
    $t = get_term_by( 'name', $name, 'product_cat' );
    if ( $t ) return $t->term_id;
    $r = wp_insert_term( $name, 'product_cat', [ 'parent' => $parent ] );
    return is_wp_error( $r ) ? 0 : $r['term_id'];
}

function mp2_beta_brand() {
    static $id;
    if ( $id ) return $id;
    $t  = get_term_by( 'name', 'Beta', 'product_brand' )
       ?: get_term_by( 'slug', 'beta', 'product_brand' );
    if ( $t ) { $id = $t->term_id; return $id; }
    $r  = wp_insert_term( 'Beta', 'product_brand' );
    $id = is_wp_error( $r ) ? 0 : $r['term_id'];
    return $id;
}

function mp2_attach_image( $url, $pid ) {
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
        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][ trim($ct) ] ?? 'jpg';
    }
    $u  = wp_upload_dir();
    $fn = $pid . '_mp2.' . $ext;
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

// ── Page ───────────────────────────────────────────────────────────────────────

$index    = max( 0, (int)( $_GET['index'] ?? 0 ) );
$total    = count( $products );
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?pass=silvester2024';

function mp2_html( $body, $refresh = '', $delay = 2 ) {
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Parts Import v2</title>';
    if ( $refresh ) echo '<meta http-equiv="refresh" content="'.$delay.';url='.htmlspecialchars($refresh).'">';
    echo '<style>body{font-family:monospace;background:#111;color:#ccc;padding:20px;}h2{color:#fff;}a{color:#2196f3;}
    .ok{color:#4caf50;}.skip{color:#888;}.fail{color:#f44336;}
    .bar-wrap{background:#333;border-radius:4px;height:18px;width:100%;max-width:500px;margin:10px 0;}
    .bar-fill{background:#4caf50;height:18px;border-radius:4px;}
    small{color:#555;font-size:11px;display:block;}</style></head>
    <body><h2>Moto Parts Import v2</h2>'.$body.'</body></html>';
}

if ( $index >= $total ) {
    mp2_html( '<p class="ok" style="font-size:18px">✓ All '.$total.' products done!</p><p><a href="/wp-admin/edit.php?post_type=product">View products →</a></p>' );
    exit;
}

$data     = $products[ $index ];       // from import-products.php
$extra    = $parts_data[ $index ];     // [sku, subcat, image]
[ $sku, $subcat, $img_url ] = $extra;

$next_url = $base_url . '&index=' . ( $index + 1 );
$pct      = round( ( ( $index + 1 ) / $total ) * 100 );

// Categories
$parent_id = mp2_cat( 'Moto dijelovi' );
$cat_ids   = [ $parent_id ];
if ( $subcat ) {
    $sub_id = mp2_cat( $subcat, $parent_id );
    if ( $sub_id ) $cat_ids[] = $sub_id;
}
$cat_ids = array_filter( array_unique( $cat_ids ) );

// Find existing product: by SKU first, then by title
$pid     = $sku ? wc_get_product_id_by_sku( $sku ) : 0;
$mode    = 'create';
if ( ! $pid ) {
    $q = new WP_Query( [
        'post_type'      => 'product',
        'title'          => $data['title'],
        'posts_per_page' => 1,
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ] );
    if ( $q->have_posts() ) {
        $pid  = $q->posts[0];
        $mode = 'update';
    }
}

$img_note = '';
$product  = $pid ? wc_get_product( $pid ) : new WC_Product_Simple();

$product->set_name( $data['title'] );
$product->set_description( $data['description'] );
$product->set_regular_price( $data['price'] );
if ( $sku ) $product->set_sku( $sku );
$product->set_status( 'publish' );
$product->set_catalog_visibility( 'visible' );
$product->set_manage_stock( false );
$product->set_stock_status( 'instock' );
$product->set_category_ids( $cat_ids );
$pid = $product->save();

if ( $pid && ! is_wp_error( $pid ) ) {
    // Image: attach only if no thumbnail yet, or mode=create
    if ( $img_url && ( $mode === 'create' || ! has_post_thumbnail( $pid ) ) ) {
        $aid = mp2_attach_image( $img_url, $pid );
        if ( $aid ) { set_post_thumbnail( $pid, $aid ); $img_note = ' img✓'; }
        else $img_note = ' img✗';
    }
    $brand_id = mp2_beta_brand();
    if ( $brand_id ) wp_set_object_terms( $pid, [ $brand_id ], 'product_brand' );
    $status = 'ok';
    $msg    = '['.strtoupper($mode).'] ID:'.$pid.' SKU:'.$sku.$img_note;
} else {
    $status = 'fail';
    $msg    = 'Save failed';
}

$bar  = '<div class="bar-wrap"><div class="bar-fill" style="width:'.$pct.'%"></div></div><p>'.($index+1).'/'.$total.' ('.$pct.'%)</p>';
$line = '<p class="'.$status.'">'.esc_html($data['title']).' — '.esc_html($msg).'</p>';
$sub  = '<small>subcat: '.esc_html($subcat).'</small>';
mp2_html( $bar.$line.$sub, $next_url );
