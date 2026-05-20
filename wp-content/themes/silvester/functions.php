<?php

// Shared function: render payment instructions with dynamic order number
function silvester_render_payment_instructions( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_payment_method() !== 'bacs' ) return;

    $gateways = WC()->payment_gateways()->payment_gateways();
    if ( ! isset( $gateways['bacs'] ) ) return;

    $instructions = $gateways['bacs']->instructions;
    if ( ! $instructions ) return;

    $instructions = str_replace( '{order_number}', $order->get_order_number(), $instructions );
    $instructions = str_replace( '{order_amount}', strip_tags( wc_price( $order->get_total() ) ), $instructions );

    echo '<section class="silvester-payment-details" style="margin-top:1.5rem;margin-bottom:2rem;">';
    echo '<h2 class="woocommerce-order-details__title">' . esc_html__( 'Detalji o plaćanju', 'silvester' ) . '</h2>';
    echo '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:.375rem;padding:1rem;font-size:.9rem;line-height:1.8;">';
    echo wp_kses_post( wpautop( wptexturize( $instructions ) ) );
    echo '</div>';
    echo '</section>';
}

// Suppress WooCommerce's default BACS output via output buffering (fires before + after)
add_action( 'woocommerce_thankyou_bacs', function() { ob_start(); }, 1 );
add_action( 'woocommerce_thankyou_bacs', function() { ob_end_clean(); }, 999 );

// Suppress WooCommerce's default BACS email instructions (we render our own below)
add_action( 'woocommerce_email_before_order_table', function( $order, $sent_to_admin, $plain_text ) {
    if ( $order->get_payment_method() === 'bacs' && ! $sent_to_admin ) {
        $gateways = WC()->payment_gateways()->payment_gateways();
        if ( isset( $gateways['bacs'] ) ) {
            remove_action( 'woocommerce_email_before_order_table', [ $gateways['bacs'], 'email_instructions' ], 10 );
        }
    }
}, 1, 3 );

// Payment instructions in BACS pending/on-hold order email
add_action( 'woocommerce_email_after_order_table', function( $order, $sent_to_admin, $plain_text, $email ) {
    $allowed_emails = [ 'customer_on_hold_order', 'customer_processing_order', 'customer_pending_order' ];
    if ( $order->get_payment_method() !== 'bacs' || $sent_to_admin ) return;
    if ( ! in_array( $email->id, $allowed_emails, true ) ) return;

    $gateways     = WC()->payment_gateways()->payment_gateways();
    $instructions = $gateways['bacs']->instructions ?? '';
    if ( ! $instructions ) return;

    $instructions = str_replace( '{order_number}', $order->get_order_number(), $instructions );
    $instructions = str_replace( '{order_amount}', strip_tags( wc_price( $order->get_total() ) ), $instructions );

    if ( $plain_text ) {
        echo "\n\n" . wp_strip_all_tags( $instructions ) . "\n";
    } else {
        echo '<table cellpadding="0" cellspacing="0" style="width:100%;margin-top:24px;border:1px solid #e5e7eb;border-radius:4px;background:#f9fafb;">';
        echo '<tr><td style="padding:16px;font-size:14px;line-height:1.8;color:#374151;">';
        echo '<strong style="display:block;font-size:15px;margin-bottom:8px;">Detalji o plaćanju</strong>';
        echo wp_kses_post( wpautop( wptexturize( $instructions ) ) );
        echo '</td></tr></table>';
    }
}, 10, 4 );

// Between order table and address blocks (thankyou + view-order)
add_action( 'woocommerce_after_order_details', function( $order ) {
    silvester_render_payment_instructions( $order->get_id() );
}, 5 );

// Disable redirect + "View cart" link after AJAX add-to-cart (popup handles it)
add_filter( 'woocommerce_cart_redirect_after_add', '__return_false' );
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );

// Cart popup AJAX handler
add_action( 'wp_ajax_silvester_cart_popup', 'silvester_cart_popup_data' );
add_action( 'wp_ajax_nopriv_silvester_cart_popup', 'silvester_cart_popup_data' );
function silvester_cart_popup_data() {
    $items = [];
    foreach ( WC()->cart->get_cart() as $item ) {
        $product   = $item['data'];
        $thumbnail = get_the_post_thumbnail_url( $item['product_id'], 'thumbnail' ) ?: wc_placeholder_img_src();
        $items[]   = [
            'name'      => $product->get_name(),
            'quantity'  => $item['quantity'],
            'price'     => strip_tags( wc_price( $product->get_price() ) ),
            'thumbnail' => $thumbnail,
        ];
    }
    wp_send_json( [
        'items'    => $items,
        'subtotal' => strip_tags( WC()->cart->get_cart_subtotal() ),
    ] );
}

// SKU below title on single product page
add_action( 'woocommerce_single_product_summary', function() {
    global $product;
    $sku = $product->get_sku();
    if ( $sku ) {
        echo '<p class="product-sku-label">SKU: <span>' . esc_html( $sku ) . '</span></p>';
    }
}, 7 );

// Nav walker — appends chevron toggle button to parent menu items
class Silvester_Nav_Walker extends Walker_Nav_Menu {
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        parent::start_el( $output, $data_object, $depth, $args, $current_object_id );
        if ( in_array( 'menu-item-has-children', $data_object->classes ) ) {
            // Inject chevron button right after the closing </a>
            $output = substr( $output, 0, strrpos( $output, '</a>' ) )
                . '</a><button class="submenu-toggle" aria-expanded="false" aria-label="Toggle submenu">'
                . '<svg class="submenu-chevron" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                . '</button>';
        }
    }
}

// Allow SVG uploads
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
} );
add_filter( 'wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes ) {
    if ( ! $data['type'] ) {
        $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( $ext === 'svg' || $ext === 'svgz' ) {
            $data['type'] = 'image/svg+xml';
            $data['ext']  = $ext;
        }
    }
    return $data;
}, 10, 4 );

function custom_styles() {
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
    wp_enqueue_style( 'custom-style-lightbox', get_template_directory_uri() . '/imagelightbox/dist/imagelightbox.min.css' );
	wp_enqueue_style( 'custom-style', get_template_directory_uri() . '/dist/custom_style.css' );
    wp_enqueue_script( 'script', get_template_directory_uri() . '/imagelightbox/dist/imagelightbox.min.js', array( 'jquery' ), 1.1, true );
}
add_action( 'wp_enqueue_scripts', 'custom_styles' );

//ACF Options
if (function_exists('acf_add_options_page')) {

    acf_add_options_page([
        'page_title' => 'Theme General Settings',
        'menu_title' => 'Theme Settings',
        'menu_slug'  => 'theme-general-settings',
        'capability' => 'edit_posts',
        'redirect'   => false
    ]);
}

// ACF Footer menu title fields
add_action('acf/init', function() {
    if (!function_exists('acf_add_local_field_group')) return;

    acf_add_local_field_group([
        'key'    => 'group_footer_menus',
        'title'  => 'Footer Settings',
        'fields' => [
            [
                'key'   => 'field_footer_menu_1_title',
                'label' => 'Footer Menu 1 Title',
                'name'  => 'footer_menu_1_title',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_footer_menu_2_title',
                'label' => 'Footer Menu 2 Title',
                'name'  => 'footer_menu_2_title',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_footer_legal_menu_title',
                'label' => 'Footer Legal Menu Title',
                'name'  => 'footer_legal_menu_title',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_footer_contact_title',
                'label' => 'Footer Contact Title',
                'name'  => 'footer_contact_title',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_footer_address',
                'label' => 'Footer Address',
                'name'  => 'footer_address',
                'type'  => 'textarea',
                'rows'  => 3,
            ],
            [
                'key'   => 'field_footer_email',
                'label' => 'Footer Email',
                'name'  => 'footer_email',
                'type'  => 'text',
            ],
            [
                'key'   => 'field_footer_phone',
                'label' => 'Footer Phone',
                'name'  => 'footer_phone',
                'type'  => 'text',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'options_page',
                    'operator' => '==',
                    'value'    => 'theme-general-settings',
                ],
            ],
        ],
    ]);
});


//Custom image size
add_image_size('header', 1920, 729, true);
add_image_size('home', 900, 600, true);
add_image_size('postfeatured', 370, 440, true);

// Register the useful image size for use in Add Media modal
add_filter('image_size_names_choose', 'your_custom_sizes');
function your_custom_sizes($sizes)
{
    return array_merge($sizes, array(
        'home' => __('Home'),
    ));
}

//Menu
function wpb_custom_new_menu()
{
    register_nav_menus(
        array(
            'main-menu'   => __('Main Menu'),
            'top-menu'    => __('Top Menu'),
            'footer-menu' => __('Footer Menu'),
            'legal-menu'  => __('Legal Menu'),
        )
    );
}
add_action('init', 'wpb_custom_new_menu');

// Remove WooCommerce sidebar
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

// WooCommerce Support
function silvester_woocommerce_setup() {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'silvester_woocommerce_setup' );

// Remove default WooCommerce wrappers
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

// Add custom WooCommerce wrappers
function silvester_woocommerce_wrapper_start() {
    echo '<div class="sub-page-default w-full mx-auto max-w-7xl px-4 xl:px-0 py-8">';
}
add_action( 'woocommerce_before_main_content', 'silvester_woocommerce_wrapper_start', 10 );

function silvester_woocommerce_wrapper_end() {
    echo '</div>';
}
add_action( 'woocommerce_after_main_content', 'silvester_woocommerce_wrapper_end', 10 );

// Set WooCommerce products per row to 3
add_filter('loop_shop_columns', 'silvester_loop_shop_columns', 20);
function silvester_loop_shop_columns($columns) {
    return 3;
}

// Result count is output in archive-product.php above columns; remove from default position
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);

// Page title on cart
function silvester_cart_page_title() {
    echo '<h1 class="page-title">' . esc_html(get_the_title(wc_get_page_id('cart'))) . '</h1>';
}
add_action('woocommerce_before_cart', 'silvester_cart_page_title', 5);

// Page title on checkout
function silvester_checkout_page_title() {
    echo '<h1 class="page-title">' . esc_html(get_the_title(wc_get_page_id('checkout'))) . '</h1>';
}
add_action('woocommerce_before_checkout_form', 'silvester_checkout_page_title', 5);
