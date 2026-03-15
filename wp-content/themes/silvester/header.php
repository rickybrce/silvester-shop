<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title(); ?></title>
    <link rel="profile" href="http://gmpg.org/xfn/11" />
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
    <?php if (is_singular() && get_option('thread_comments')) wp_enqueue_script('comment-reply'); ?>
    <?php wp_head(); ?>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php bloginfo('template_directory'); ?>/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php bloginfo('template_directory'); ?>/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php bloginfo('template_directory'); ?>/images/favicon/favicon-16x16.png">
    <link rel="mask-icon" href="<?php bloginfo('template_directory'); ?>/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" type="image/x-icon" href="<?php bloginfo('template_directory'); ?>/images/favicon/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700&display=swap" rel="stylesheet">
</head>

<body class="bg-white text-enduro-grey-900">
    <header class="fixed top-0 left-0 w-full z-50 transition-transform duration-300" id="site-header">
        <div class="w-full pl-4 pr-2 lg:px-4 py-2 bg-[rgba(0,0,0,0.8)]">
            <div class="w-full">
                <div class="max-w-full mx-auto">
                    <div class="flex">
                        <a href="<?php echo home_url(); ?>" class="pr-8 flex items-center relative z-40"><img class="w-[70px] lg:w-[140px] shrink-0" src="<?php bloginfo('template_directory'); ?>/images/logo.png" width="200" height="283" />
                        </a>
                        <div class="w-full flex items-center justify-between">
                            <div class="w-full flex items-center justify-end lg:justify-between gap-4 lg:flex-row-reverse">
                                <!-- Cart icon + Login/Logout -->
                                <div class="flex items-center gap-4">
                                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="relative inline-flex items-center gap-2 text-sm text-white hover:text-enduro-red-100 transition">
                                        <span class="relative inline-block">
                                            <i class="fa-solid fa-cart-shopping text-xl"></i>
                                            <?php if (function_exists('WC') && WC()->cart && WC()->cart->get_cart_contents_count() > 0) : ?>
                                                <span class="cart-count-badge absolute -top-2 -right-2 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-enduro-red-100 text-white text-xs font-medium px-1"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (function_exists('WC') && WC()->cart) : ?>
                                            <span class="cart-total inline text-sm ml-2 lg:ml-4"><?php echo WC()->cart->get_cart_total(); ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <?php if (is_user_logged_in()) :
                                        $current_user = wp_get_current_user();
                                        $display_name = $current_user->display_name ?: $current_user->user_login;
                                    ?>
                                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="inline-flex items-center gap-1.5 text-sm text-white hover:text-enduro-red-100 transition" title="<?php echo esc_attr($display_name); ?>">
                                            <i class="fa-solid fa-user text-base"></i>
                                            <span class="hidden lg:inline"><?php echo esc_html($display_name); ?></span>
                                        </a>
                                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="inline-flex items-center gap-1.5 text-sm text-white hover:text-enduro-red-100 transition">
                                            <i class="fa-solid fa-arrow-right-from-bracket text-base"></i>
                                            <span class="hidden lg:inline"><?php esc_html_e('Odjava', 'silvester'); ?></span>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="inline-flex items-center gap-1.5 text-sm text-white hover:text-enduro-red-100 transition">
                                            <i class="fa-solid fa-user text-base"></i>
                                            <span class="hidden lg:inline"><?php esc_html_e('Prijava', 'silvester'); ?></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <!-- Cart icon + Login/Logout -->
                                <!-- Mobile menu panel (overlay on mobile, normal on desktop) -->
                                <div id="mobile-menu-panel" class="mobile-menu-panel hidden lg:!flex lg:flex-1" aria-hidden="true">
                                    <a href="<?php echo home_url(); ?>" class="mobile-menu-logo lg:hidden absolute top-2 left-4 z-50"><img class="w-[70px] shrink-0" src="<?php bloginfo('template_directory'); ?>/images/logo.png" width="200" height="283" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" /></a>
                                    <a href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart')); ?>" class="mobile-menu-cart lg:hidden absolute top-4.5 right-16 z-50 inline-flex items-center gap-2 text-sm text-white hover:text-enduro-red-100 transition">
                                        <span class="relative inline-block">
                                            <i class="fa-solid fa-cart-shopping text-xl"></i>
                                            <?php if (function_exists('WC') && WC()->cart && WC()->cart->get_cart_contents_count() > 0) : ?>
                                                <span class="cart-count-badge absolute -top-2 -right-2 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-enduro-red-100 text-white text-xs font-medium px-1"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (function_exists('WC') && WC()->cart) : ?>
                                            <span class="cart-total inline text-sm ml-2"><?php echo WC()->cart->get_cart_total(); ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <button type="button" class="mobile-menu-close lg:hidden absolute top-2 right-2 w-10 h-10 flex items-center justify-center text-white text-3xl hover:text-enduro-red-100 z-50" aria-label="<?php echo esc_attr__('Zatvori izbornik', 'silvester'); ?>">&times;</button>
                                    <?php
                                    wp_nav_menu(array(
                                        'theme_location' => 'main-menu',
                                        'container'      => 'nav',
                                        'container_id'   => 'main-nav',
                                        'container_class' => 'main-nav text-sm py-2 w-full lg:w-auto lg:flex lg:items-center lg:relative lg:!block px-4 pt-16 lg:pt-0 lg:px-0',
                                        'menu_id'        => 'menu-main-menu',
                                        'menu_class'     => 'mobile-menu-list flex flex-col lg:flex-row gap-4 lg:gap-0 lg:items-center lg:w-full'
                                    ));
                                    ?>
                                </div>
                                <!-- Hamburger: visible only on mobile -->
                                <button type="button" class="mobile-menu-toggle lg:hidden flex items-center justify-center w-10 h-10 text-white hover:text-enduro-red-100 transition" aria-label="<?php echo esc_attr__('Otvori izbornik', 'silvester'); ?>" aria-expanded="false" aria-controls="mobile-menu-panel">
                                    <i class="fa-solid fa-bars text-2xl"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
    </header>

    <?php /*if (!is_home() && !is_front_page()) : ?>
        <?php $header_page = get_field('sub_page_header');
        if ($header_page["image"]) : ?>
            <div class="w-full lg:w-auto bg-cover bg-no-repeat bg-center bg-top-center w-full h-[400px] lg:h-[600px] flex items-end lg:items-center justify-center" style="background-image: url(<?php echo $header_page["image"] ?>)">
                <h1 class="w-full lg:w-auto relative text-white uppercase text-6xl px-4 lg:px-20 py-10">
                    <div class="relative z-10 box-head"><?php single_post_title(); ?></div>
                    <div class="absolute w-full h-full left-0 top-0 bg-black opacity-80 z-0"></div>
                </h1>
            </div>
        <?php endif;?>
    <?php endif;  */ ?>