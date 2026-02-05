<?php

function custom_styles() {
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
    wp_enqueue_style( 'custom-style-lightbox', get_template_directory_uri() . '/imagelightbox/dist/imagelightbox.min.css' );
	wp_enqueue_style( 'custom-style', get_template_directory_uri() . '/dist/custom_style.css' );
    wp_enqueue_script( 'script', get_template_directory_uri() . '/imagelightbox/dist/imagelightbox.min.js', array( 'jquery' ), 1.1, true );
}
add_action( 'wp_enqueue_scripts', 'custom_styles' );

//ACF Options
if (function_exists('acf_add_options_page')) {

    acf_add_options_page(array(
        'page_title'     => 'Theme General Settings',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts',
        'redirect'        => false
    ));
}


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
            'main-menu' => __('Main Menu'),
            'top-menu' => __('Top Menu'),
            'footer-menu' => __('Footer Menu')
        )
    );
}
add_action('init', 'wpb_custom_new_menu');

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
    echo '<div class="sub-page-default w-full mx-auto max-w-6xl px-4 xl:px-0 py-8">';
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
