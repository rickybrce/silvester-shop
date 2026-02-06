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
    <header class="fixed top-[40px] lg:top-0 left-0 w-full z-50"> 
        <div class="w-full px-4 py-2 bg-[rgba(0,0,0,0.8)]">
            <div class="w-full">
                <div class="max-w-full mx-auto">
                    <div class="flex">
                        <a href="<?php echo home_url(); ?>" class="pr-8 flex items-center relative z-40"><img class="w-[70px] lg:w-[140px] shrink-0" src="<?php bloginfo('template_directory'); ?>/images/logo.png" width="200" height="283" />
                        </a>
                        <div class="w-full flex items-center justify-between">
                            <div class="w-full mb-2 hidden">
                                <?php
                                /*wp_nav_menu(array(
                                    'theme_location' => 'top-menu',
                                    'container_class' => 'w-full text-sm py-2 w-full mobile-menu lg:flex fixed lg:relative left-0 top-0 mt-[50px] lg:mt-0 px-4 lg:pl-[20px] lg:pr-0 z-30 lg:items-center'
                                ));*/
                                ?>
                            </div>
                            <div class="w-full flex items-center justify-between">
                                <?php
                                wp_nav_menu(array(
                                    'theme_location' => 'main-menu',
                                    'container_class' => 'text-sm py-2 w-full mobile-menu hidden lg:flex fixed lg:relative left-0 top-0 mt-[120px] lg:mt-0 px-[20px] lg:px-0 z-50 lg:items-center !w-auto'
                                ));
                                ?>
                                 <!-- Cart icon -->
                                 <div class="ml-8 text-right">
                                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="relative inline-flex items-center gap-2 text-sm text-white hover:text-enduro-red-100 transition">
                                        <span class="relative inline-block">
                                            <i class="fa-solid fa-cart-shopping text-xl"></i>
                                            <?php if (function_exists('WC') && WC()->cart && WC()->cart->get_cart_contents_count() > 0) : ?>
                                                <span class="cart-count-badge absolute -top-2 -right-2 min-w-[18px] h-[18px] flex items-center justify-center rounded-full bg-enduro-red-100 text-white text-xs font-medium px-1"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <?php if (function_exists('WC') && WC()->cart) : ?>
                                            <span class="cart-total hidden sm:inline ml-4"><?php echo WC()->cart->get_cart_total(); ?></span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <!-- Cart icon -->
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