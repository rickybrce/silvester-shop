<?php
/**
* Plugin Name: WooFiskal
* Plugin URI: https://www.switch-one.com/woo-fiskal/
* Description: Automatska fiskalizacija WooCommerce narudžbi kroz WebRačun servis
* Version: 1.4.4
* Author: Switch One
* Author URI: https://switch-one.com/
**/

#globals used inside the plugin
global 
$wpdb, 
$_SESSION,
$wf_table,
$wf_settings,
$plugin_admin_url,
$is_woo,
$wf_payment_types,
$wf_version;

#version
$wf_version = '1.4.4';

#add prefix to the table name
$wf_table = $wpdb->prefix.'woo_fiskal';

$wf_settings = [];
if( $wpdb->get_var( "SHOW TABLES LIKE '$wf_table'" ) == $wf_table ){
    #get fresh WooFiskal settings from DB
    $wf_settings = $wpdb->get_results ( $wpdb->prepare( "SELECT * FROM $wf_table WHERE id = %d", 1 ) );
}

#check for WooCommerce after all plugins are loaded - it has to be installed and activated before you can use WooFiskal
function wf_check_for_woocommerce() {
    global $is_woo;
    $is_woo = ( !defined('WC_VERSION') ? false : true );
}
add_action('plugins_loaded', 'wf_check_for_woocommerce');

#check for secure connection (prevent plugin dropping connection if not HTTPS)
$ssl = (is_ssl() ? 'https' : 'http');

$plugin_admin_url = admin_url( 'admin.php?page=woo-fiskal', $ssl );

#map available payment types from WooCoomerce to shopify API ['woocoomerce' => 'woofiskal']; 
$wf_payment_types = [
    'cod' => 'Cash on Delivery (COD)', 
    'bacs' => 'Bank Deposit', 
    'ppec_paypal' => 'paypal',
    'paypal' => 'paypal'
];

#Fires once you click "Activate" inside the WP admin plugin list.
function wf_activate() {

    global $wpdb,
    $wf_table;

    #Check to see if the table exists already, if not, then create it
    if( $wpdb->get_var( "SHOW TABLES LIKE '$wf_table'" ) != $wf_table ){

        #sample SQL query that creates custom plugin table
        $create = "CREATE TABLE `$wf_table`(
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `wf_api_link` VARCHAR(100) NOT NULL,
            `wf_auto_fiskal` TINYINT(1) UNSIGNED NOT NULL,
            `wf_auto_fiskal_processing` TINYINT(1) UNSIGNED NOT NULL,
            `wf_send_mail` TINYINT(1) UNSIGNED NOT NULL,
            `wf_r1` TINYINT(1) UNSIGNED NOT NULL,
            `wf_r1_label` VARCHAR(400) NOT NULL,
            `wf_company_name_label` VARCHAR(100) NOT NULL,
            `wf_company_name_placeholder` VARCHAR(100) NOT NULL,
            `wf_company_oib_label` VARCHAR(100) NOT NULL,
            `wf_company_oib_placeholder` VARCHAR(100) NOT NULL,
            PRIMARY KEY (`id`)
            )
            COLLATE = 'utf8_general_ci'
            ENGINE = InnoDB;";

        #needed for the dbDelta function
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

        #run CREATE query
        dbDelta($create);

        #run INSERT query - input first row with ID = 1 and default settings
        $wpdb->insert($wf_table, array(
            'id' => 1,
            'wf_api_link' => 'webRačun LINK',
            'wf_auto_fiskal' => 0, 
            'wf_auto_fiskal_processing' => 0, 
            'wf_send_mail' => 0,
            'wf_r1' => 1,
            'wf_r1_label' => 'Trebam R1 račun',
            'wf_company_name_label' => 'Naziv tvrtke',
            'wf_company_name_placeholder' => 'tvrtka d.o.o.',
            'wf_company_oib_label' => 'OIB tvrtke',
            'wf_company_oib_placeholder' => 'xxxxxxxxxxx'
        ));

    }

    #update order status so we can re-use old orders that were sent to webRacun before | only "wc-completed" orders change status!
    $wpdb->query("UPDATE `wp_posts` SET `post_status` = 'wc-completed-fiskal' 
    WHERE `post_type` = 'shop_order' 
    AND `post_status` = 'wc-completed'
    AND `id` IN (SELECT `post_id` FROM `wp_postmeta` WHERE `meta_key` = 'completed-fiskal' AND `meta_value` = 1);");

    #we triggered this with custom SQL query to avoid sending json requests to webRacun API again.
}   
register_activation_hook(__FILE__, 'wf_activate');

#Fires once you click "Dectivate" inside the WP admin plugin list.
function wf_deactivate() {
    global $wpdb;

    #move all WC orders from our "wc-completed-fiskal" status to "wc-completed" - user won't be able to see completed orders if we don't do this! 
    $wpdb->query("UPDATE `wp_posts` SET `post_status` = 'wc-completed' WHERE `post_type` = 'shop_order' AND `post_status` ='wc-completed-fiskal'");

    #clear the permalinks to remove our post type's rules from the database.
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wf_deactivate' );


#add direct link to WooFiskal settings on Wordpress plugin page 
function wf_settings_link( $links ) {
	$links[] = '<a href="'.admin_url('admin.php?page=woo-fiskal&tab=settings').'">'.__('Settings').'</a>';
	return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'wf_settings_link');


#add plugin to WP Admin backend menu  
function wf_load_admin_menu(){
        add_menu_page( 
            'WooFiskal', //plugin page title - appends to page HTML <title/>
            'WooFiskal', //plugin menu label
            'manage_options', 
            'woo-fiskal', //page slug inside the WP admin - link: admin.php?page=plugin-main
            'wf_admin_page_open', //function triggered when the plugin admin page is open
            plugin_dir_url( __FILE__ ).'admin/wf-icon.png' //icon before the menu label
        );

        add_submenu_page( 
            'woo-fiskal',
            'WooFiskal | Postavke', //plugin page title - appends to page HTML <title/>
            'Postavke', //plugin menu label
            'manage_options',
            'woo-fiskal&tab=settings', //page slug inside the WP admin - link: admin.php?page=plugin-main&tab=tab-1
            'wf_admin_page_open',//function triggered when the plugin admin page is open
            1
        );

        add_submenu_page( 
            'woo-fiskal',
            'WooFiskal | Općenito', //plugin page title - appends to page HTML <title/>
            'Općenito', //plugin menu label
            'manage_options',
            'woo-fiskal&tab=general', //page slug inside the WP admin - link: admin.php?page=plugin-main&tab=tab-2
            'wf_admin_page_open', //function triggered when the plugin admin page is open
            2
        );

        //WP adds menu page as submenu item - this is used to remove it from submenu and keep it only as parent
        remove_submenu_page('woo-fiskal','woo-fiskal');

}
//load function and add our plugin to WP Admin backend menu  
add_action('admin_menu', 'wf_load_admin_menu');
 
//Plugin Admin backend page
function wf_admin_page_open(){

    #add timestamp to css and js files to prevent browser caching => script.js?v=1586102738, style.css?v=1586102738
    $time = time();

    #load PHP file containing your custom HTML layout for the plugin admin page inside WP admin
    require('admin/woo-fiskal-admin.php');

    #add CSS to your admin page - BACKEND => $time is added as ?v= parameter to prevent browser chaching
    wp_enqueue_style( 'wf_admin_css', plugin_dir_url( __FILE__ ) . 'admin/wf-admin.css', false, $time, 'all');
    
    #add JS to your admin page - BACKEND => $time is added as ?v= parameter to prevent browser chaching
    wp_enqueue_script( 'wf_admin_js', plugins_url('/admin/wf-admin.js', __FILE__), array('jquery'), $time );
    
    #add AJAX support (first we load the script with "wp_enqueue_script" then give it access to use AJAX calls )
    wp_localize_script( 'wf_admin_js', 'ajax_admin_data', 
        array( 
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'admin_nonce' => wp_create_nonce('admin-nonce') 
            ) 
    );
}

#Highlight active menu item in plugin submenu
function wf_highlight_active_submenu($parent_file){
    global $submenu_file;
    if (isset($_GET['page']) && isset($_GET['tab'])) $submenu_file = $_GET['page'] . '&tab=' . $_GET['tab'];

    return $parent_file;
}
add_filter('parent_file', 'wf_highlight_active_submenu');

/*
*_____________________________
*  WOO FISKAL AJAX FUNCTIONS
*_____________________________ 
*/

#save data from WF ADMIN SETTINGS page
function wf_admin_settings_save() {

    #globals
    global $wpdb,
    $wf_table;
    
    #first security check - admin nonce 
    if( wp_verify_nonce( $_POST['security_nonce'], 'admin-nonce' ) &&
        check_ajax_referer( 'admin-nonce', 'security_nonce', false )
    ){

        #data sent from AJAX CALL
        $wf_api_link = trim( $_POST['wf_api_link'] );
        $wf_auto_fiskal = intval( $_POST['wf_auto_fiskal'] );
        $wf_auto_fiskal_processing = intval( $_POST['wf_auto_fiskal_processing'] );
        $wf_send_mail = intval( $_POST['wf_send_mail'] );
        $wf_r1 = intval( $_POST['wf_r1'] );

        #labels and placeholders
        $wf_r1_label = trim( $_POST['wf_r1_label'] );
        $wf_company_name_label = trim( $_POST['wf_company_name_label'] );
        $wf_company_name_placeholder = trim( $_POST['wf_company_name_placeholder'] );
        $wf_company_oib_label = trim( $_POST['wf_company_oib_label'] );
        $wf_company_oib_placeholder = trim( $_POST['wf_company_oib_placeholder'] );  

        #update database entry 
        $wpdb->query($wpdb->prepare("UPDATE $wf_table SET 
            wf_api_link = '$wf_api_link', 
            wf_auto_fiskal = '$wf_auto_fiskal',
            wf_auto_fiskal_processing = '$wf_auto_fiskal_processing',  
            wf_send_mail = '$wf_send_mail',
            wf_r1 = '$wf_r1',
            wf_r1_label = '$wf_r1_label',
            wf_company_name_label = '$wf_company_name_label',
            wf_company_name_placeholder = '$wf_company_name_placeholder',
            wf_company_oib_label = '$wf_company_oib_label',
            wf_company_oib_placeholder = '$wf_company_oib_placeholder'  
            WHERE id = %d", 1));

        #JSON array of returned values - SUCCESS   
        wp_send_json_success(array(
            'wf_api_link' => $wf_api_link,
            'wf_auto_fiskal' => $wf_auto_fiskal,
            'wf_send_mail' => $wf_send_mail,
            'wf_r1' => $wf_r1,
            'result' => 'SUCCESS :)'
        ));
    }
    #JSON array of returned values - ERROR
    else{
        wp_send_json_error(array( 
            'message' => 'Nice try hackerman!' 
        ), 500 ); //error code 500 will trigger jQuery AJAX error callback
    }

    wp_die();
            
}
#connect function with admin-ajax.php from WP core and allow AJAX to run
add_action( 'wp_ajax_wf_admin_settings_save', 'wf_admin_settings_save' );

/*
*_____________________________
*  WOOCOMMERCE FUNCTIONS
*_____________________________ 
*/

#load admin.css from WooFiskal to all admin pages including WooCommerce
function wf_woo_admin_css() {
    $time = time();
    wp_enqueue_style( 'wf_admin_css', plugin_dir_url( __FILE__ ) . 'admin/wf-admin.css', false, $time, 'all');
}
add_action('admin_head', 'wf_woo_admin_css');

#register new order status - COMPLETED-FISKAL - MUST CONTAIN WC- prefix!!!
function wf_register_completed_fiskal_status() {
    register_post_status( 'wc-completed-fiskal', array(
        'label'                     => 'Završeno / Fiskalizirano',
        'label_count'               => _n_noop( 'Završeno / Fiskalizirano <span class="count">(%s)</span>', 'Završeno / Fiskalizirano <span class="count">(%s)</span>', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true
    ) );
}
add_action( 'init', 'wf_register_completed_fiskal_status' );

#add new order status - COMPLETED-FISKAL to list of WC Order statuses - MUST CONTAIN WC- prefix!!!
function wf_add_completed_fiskal_status($order_statuses) {
 
    #add custom order status
    $order_statuses['wc-completed-fiskal'] = 'Završeno / Fiskalizirano';
    return $order_statuses;
}
add_filter( 'wc_order_statuses', 'wf_add_completed_fiskal_status' );

#expand billing fields for R1
function wf_add_checkout_fields($fields){

    global $wf_settings;

    #remove default billing_company field!
    unset($fields['billing']['billing_company']);

    #add billing fields if R1 setting is enabled
    if($wf_settings[0]->wf_r1){

        $fields['billing']['billing_wf_r1']['label'] = $wf_settings[0]->wf_r1_label;
        $fields['billing']['billing_wf_r1']['type'] = 'checkbox';
        $fields['billing']['billing_wf_r1']['priority'] = 0;

        $fields['billing']['billing_wf_company_name']['label'] = $wf_settings[0]->wf_company_name_label;
        $fields['billing']['billing_wf_company_name']['placeholder'] = $wf_settings[0]->wf_company_name_placeholder;
        $fields['billing']['billing_wf_company_name']['priority'] = 1;
        $fields['billing']['billing_wf_company_name']['required'] = true;

        $fields['billing']['billing_wf_company_oib']['label'] = $wf_settings[0]->wf_company_oib_label;
        $fields['billing']['billing_wf_company_oib']['placeholder'] = $wf_settings[0]->wf_company_oib_placeholder;
        $fields['billing']['billing_wf_company_oib']['priority'] = 2;
        $fields['billing']['billing_wf_company_oib']['required'] = true;

    }

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'wf_add_checkout_fields');

#validate OIB
/*
function wf_validate_oib( $fields, $errors ){
    
    global $wf_settings;

    if( !(preg_match( '/^\d{11}$/', $fields[ 'billing_wf_company_oib' ])) ){
        $errors->add( 'validation', '<b>'.$wf_settings[0]->wf_company_oib_label.'</b> mora sadržavati točno 11 brojeva' );
    }
}
add_action( 'woocommerce_after_checkout_validation', 'wf_validate_oib', 10, 2); 
*/
#custom js and css for WF R1 option
function wf_r1_CSS_JS() {
    // Only checkout page
    if( ! ( is_checkout() && ! is_wc_endpoint_url() ) ) return;

    else{

    echo "
    <style>
        #billing_wf_company_name_field, 
        #billing_wf_company_oib_field,
        #billing_wf_r1_field span.optional { 
            display:none;
        }

        #billing_wf_company_name_field.on,
        #billing_wf_company_oib_field.on { 
            display:block;
        }
    </style>
  
    <script type='text/javascript'>
    jQuery('input#billing_wf_r1').change(function(){
           
        if (! this.checked) {
           // HIDE IF NOT CHECKED
           jQuery('#billing_wf_company_name_field').hide();
           jQuery('#billing_wf_company_oib_field').hide();
           jQuery('#billing_wf_company_name_field input').val('-');
           jQuery('#billing_wf_company_oib_field input').val('-');

        } else {
           // SHOW IF CHECKED
           jQuery('#billing_wf_company_name_field').fadeIn();
           jQuery('#billing_wf_company_name_field input').val('');
           jQuery('#billing_wf_company_oib_field').fadeIn();
           jQuery('#billing_wf_company_oib_field input').val('');
        }
          
     }).change();

     //OIB validation
     /*
     jQuery('input#billing_wf_company_oib').on('blur change', function(){
        var wrapper = jQuery(this).closest('.form-row');

        if( /^\d{11}$/.test( jQuery(this).val() ) ) { // must be 11 digit
            wrapper.addClass('woocommerce-validated'); 
        } 
        else {
            wrapper.addClass('woocommerce-invalid'); 
        }
    });
    */
    </script>";
    }
}
add_action( 'wp_footer', 'wf_r1_CSS_JS' );

#Display custom fields on the order edit page
function wf_display_r1_fields_admin($order){
    echo '<p><strong>Naziv tvrtke:</strong> <br/>' . get_post_meta( $order->get_id(), '_billing_wf_company_name', true ) . '</p>';
    echo '<p><strong>OIB tvrtke:</strong> <br/>' . get_post_meta( $order->get_id(), '_billing_wf_company_oib', true ) . '</p>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'wf_display_r1_fields_admin', 10, 1 );

#manually change order status to COMPLETED-FISKAL
function wf_change_order_complete_fiskal_status($order_id){
    
    global $woocommerce,
    $wf_settings,
    $wf_payment_types;

    #get order data
    $order = new WC_Order( $order_id );

    #check if we have the payment gateway mapped (override with WooFiskal value if exists / else send default value)
    $gateway = ( array_key_exists($order->get_payment_method(), $wf_payment_types) ? $wf_payment_types[$order->get_payment_method()] : $order->get_payment_method() );

    #process data for R1
    $company_name = get_post_meta( $order_id, '_billing_wf_company_name', true );
    $company_name = ($company_name == '-' ? '' : $company_name );

    $company_oib = get_post_meta( $order_id, '_billing_wf_company_oib', true );
    $company_oib = ($company_oib == '-' ? '' : $company_oib );


    #get order items
    $order_items = array();

    $items = $order->get_items();

    #order total - needed for coupon (%) calculation
    $order_total = 0;

    #loop through ordered items
    foreach ($items as $item) {
 
        #check if product has variation.
        $product = $item['variation_id'] ? wc_get_product($item['variation_id']) : wc_get_product($item['product_id']);

        #calculate discount on SINGLE PRODUCT LEVEL if any - default string $discount = '0.00';
        $discount = '0.00';
        if( $product->is_on_sale() ) {
            $sale_price = $product->get_sale_price();
            #discount = (regular_price - sale_price) * item_quantity
            $discount = ( floatval( $product->get_regular_price() ) - floatval( $product->get_sale_price() ) ) * floatval( $item->get_quantity() );
        }

        #single item data for JSON
        $item_data = [
            'title'       => $item->get_name(),
            'quantity'    => strval( $item->get_quantity() ),
            'price'       => $product->get_regular_price(),
            'sku'         => $product->get_sku(),
            'discount_allocations' => [ array( 'amount' => strval($discount) ) ] //webracun settings must be exact amount not percentage!
        ];


        #tax rate
        $tax = new WC_Tax();
        $taxes = $tax->get_rates($product->get_tax_class());

        #add tax rate to item data
        if(!empty($taxes)){
            #dual array_shift needs temporary variable
            $tmp = array_shift($taxes);
            $item_data['tax_cst'] = round(array_shift( $tmp ));
        }

        #add item to item array for JSON
        array_push($order_items, $item_data);

        #add item price to order total
        $product_price = $product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price();
        $order_total += ( floatval($product_price) * $item->get_quantity() );

    }


    #get coupon code discounts
    $coupons = array();
    $coupon_codes = $order->get_coupon_codes();

    foreach($coupon_codes as $coupon){

        $c = new WC_Coupon($coupon);
        $c_type = $c->get_discount_type();
        $c_name = strtoupper($coupon);
        $c_description = $c->get_description();
        $c_amount = $c->get_amount();
    
        #fixed cash coupon calculation
        if($c_type == 'fixed_cart'){

            $x = $c_amount * -1;
            $total_discount = strval($x);
        }

        #percent coupon calculation
        if($c_type == 'percent'){
            $p = $c_amount / 100;
            //$x = $order->get_subtotal() * $p * -1;
            $x = $order_total * $p * -1;
            $total_discount = strval($x);
        }

        #fixed cash PER PRODUCT coupon calculation
        if($c_type == 'fixed_product'){

            $x = $c_amount * $order->get_item_count() * -1;
            $total_discount = strval($x);
        }

        #add coupon discount to array / same structure as order items - so webRacun can process it :)
        array_push($coupons, [
            'title'       => trim($c_name),
            'quantity'    => '1',
            'price'       => $total_discount,
            'sku'         => 'wr_kupon_'.$c_type,
            'discount_allocations' => [ array( 'amount' => '0' ) ]
        ]);

    }
    
    #order data - prepare JSON structure
    $order_data = array(
        'id'            => strval($order_id),
        'email'         => $order->get_billing_email(),
        'name'          => '#'.$order_id,
        'gateway'       => $gateway,
        'processed_at'  => $order->get_date_created(),
        'line_items'    => array_merge($order_items, $coupons), //sending coupons as order items with negative value
        'customer'      => 
        array(
           'email'          => $order->get_billing_email(),
           'first_name'     => $order->get_billing_first_name(),
           'last_name'      => $order->get_billing_last_name(),
           'default_address' => [
              'address1'    => trim( $order->get_billing_address_1().' '.$order->get_billing_address_2() ),
              'city'        => $order->get_billing_city(),
              'country'     => $order->get_billing_country(),
              'company'     => $company_name,
              'address2'     => $company_oib //OIB FIELD!
           ]
        ),
        'total_shipping_price_set' => 
        array(
           'shop_money'         => [
              'amount'          => $order->get_shipping_total(),
              'currency_code'   => $order->get_currency()
            ]
        )
    );

    $order_data = json_encode($order_data);

    error_log($order_data, 0);

    #connect to WebRacun API
    $ch = curl_init($wf_settings[0]->wf_api_link);                                                                      
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');                                                                     
    curl_setopt($ch, CURLOPT_POSTFIELDS, $order_data);                                                                  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',                                                                                
        'Content-Length: ' . strlen($order_data)
        )                                                                       
    );  

    #send JSON data
    $result = curl_exec($ch);
    curl_close($ch);

    #add extra meta information to differentiate orders sent to WebRacun servers :)
    update_post_meta( $order_id, 'completed-fiskal', '1' );

}
add_action( 'woocommerce_order_status_completed-fiskal', 'wf_change_order_complete_fiskal_status');


#change order status from COMPLETED to COMPLETED-FISKAL - only if automatic invoice processing is enabled in WF settings!!!
function wf_change_order_complete_status($order_id){
    global $woocommerce,
    $wf_settings;

    #get order data
    $order = new WC_Order( $order_id );
    
    #change order status if automatic setting is enabled
    if($wf_settings[0]->wf_auto_fiskal){
        $order->update_status('completed-fiskal');
        #this triggers woocommerce_order_status_completed-fiskal action and change_order_complete_fiskal_status function!!!
    }
}
add_action( 'woocommerce_order_status_completed', 'wf_change_order_complete_status' );

#change order status from PROCESSING to COMPLETED-FISKAL - only if automatic invoice processing is enabled in WF settings!!!
function wf_change_order_processing_status($order_id){
    global $woocommerce,
    $wf_settings;

    #get order data
    $order = new WC_Order( $order_id );
    
    #change order status if automatic setting is enabled
    if($wf_settings[0]->wf_auto_fiskal_processing){
        $order->update_status('completed-fiskal');
        #this triggers woocommerce_order_status_completed-fiskal action and change_order_complete_fiskal_status function!!!
    }
}
add_action( 'woocommerce_order_status_processing', 'wf_change_order_processing_status' );



#register custom bulk change action
function wf_completed_fiskal_bulk_option( $bulk_actions ) {
	$bulk_actions['completed-fiskal-bulk'] = 'Promjeni status u "Završeno / Fiskalizirano"';
	return $bulk_actions;
}
add_filter( 'bulk_actions-edit-shop_order', 'wf_completed_fiskal_bulk_option', 20 );
 
 
#Bulk action handler
function wf_completed_fiskal_bulk_action() {
 
	#if an array with order IDs is not presented, exit the function
	if( !isset( $_REQUEST['post'] ) && !is_array( $_REQUEST['post'] ) )
		return;

    
    #loop array of IDs and update status
	foreach( $_REQUEST['post'] as $order_id ) {
 
		$order = new WC_Order( $order_id );
		$order->update_status('completed-fiskal'); // "completed-fiskal" is the order status name (do not use wc-completed-fiskal)
 
	}
 
	#after update redirect - build URL inline
	$location = add_query_arg( array(
    	'post_type' => 'shop_order',
		'bulk_action' => 'completed-fiskal', // bulk_action = completed-fiskal is the $_GET variable for notice
		'changed' => count( $_REQUEST['post'] ), // number of changed orders
		'post_status' => 'all'
	), 'edit.php' );
 
	wp_redirect( admin_url( $location ) );
	exit;
 
}
add_action( 'admin_action_completed-fiskal-bulk', 'wf_completed_fiskal_bulk_action' );
 
 
#update notice
function wf_completed_fiskal_bulk_notice() {

	global $pagenow, $typenow;
 
	if( $typenow == 'shop_order' 
	 && $pagenow == 'edit.php'
	 && isset( $_REQUEST['bulk_action'] )
	 && $_REQUEST['bulk_action'] == 'completed-fiskal'
	 && isset( $_REQUEST['changed'] ) ) {

        $narudzba_HR = ($_REQUEST['changed'] > 4 ? 'narudžbi' : 'narudžbe'); 
		$notice = sprintf( _n( 'Promijenjen status narudžbe.', 'Promijenjen status %s '.$narudzba_HR.'.', $_REQUEST['changed'] ), number_format_i18n( $_REQUEST['changed'] ) );
		echo '<div class="updated"><p>'.$notice.'</p></div>';
 
	}
}
add_action('admin_notices', 'wf_completed_fiskal_bulk_notice');

#add custom status buttons to order preview lightbox
function wf_order_preview_custom_status( $actions, $order ){

    $custom_statuses = array(
        'completed-fiskal' => array( // The key (slug without "wc-")
            'label'     => __("Završeno / Fiskalizirano", "woocommerce"), // Label name
            'allowed'   => array( 'pending', 'on-hold', 'processing', 'completed-fiskal' ), // Button displayed for this statuses (slugs without "wc-")
        ),
    );

    // Loop through your custom orders Statuses
    foreach ( $custom_statuses as $status_slug => $values ){
        if ( $order->has_status( $values['allowed'] ) ) {
            $actions['status']['actions'][$status_slug] = array(
                'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status='.$status_slug.'&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
                'name'   => $values['label'],
                'title'  => __( 'Promijenite status narudžbe u', 'woocommerce' ) . ' "'.$values['label'].'"',
                'action' => $status_slug,
            );
        }
    }
    return $actions;
}
add_filter( 'woocommerce_admin_order_preview_actions', 'wf_order_preview_custom_status', 25, 2 );

?>