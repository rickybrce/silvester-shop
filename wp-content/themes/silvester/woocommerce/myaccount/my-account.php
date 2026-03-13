<?php
/**
 * My Account page – theme override with sidebar layout
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-MyAccount-wrapper flex flex-col lg:flex-row gap-8">

    <aside class="woocommerce-MyAccount-sidebar lg:w-64 shrink-0">
        <?php do_action( 'woocommerce_account_navigation' ); ?>
    </aside>

    <div class="woocommerce-MyAccount-content flex-1 min-w-0">
        <?php do_action( 'woocommerce_account_content' ); ?>
    </div>

</div>
