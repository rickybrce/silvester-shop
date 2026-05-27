<?php
/**
 * Plugin Name: Silvester Customer Discount
 * Description: Percentage discount per registered customer, applied automatically to entire order.
 * Version: 1.0.0
 * Author: Silvester Shop
 * Text Domain: silvester-discount
 */

defined( 'ABSPATH' ) || exit;

// ── Admin menu page ───────────────────────────────────────────────────────────

add_action( 'admin_menu', function() {
    add_menu_page(
        __( 'Popusti za kupce', 'silvester-discount' ),
        __( 'Popusti kupaca', 'silvester-discount' ),
        'manage_woocommerce',
        'scd-discounts',
        'scd_admin_page',
        'dashicons-tag',
        58
    );
} );

function scd_admin_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;

    // Handle save
    if ( isset( $_POST['scd_save'] ) && check_admin_referer( 'scd_bulk_save' ) ) {
        $user_ids = array_map( 'intval', (array) ( $_POST['scd_user_ids'] ?? [] ) );
        $discount = max( 0, min( 100, (float) $_POST['scd_percent'] ) );
        $count    = 0;
        foreach ( $user_ids as $uid ) {
            if ( $uid > 0 ) { update_user_meta( $uid, 'scd_discount_percent', $discount ); $count++; }
        }
        if ( $count > 0 ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Popust spremljen za %d kupac(a).', 'silvester-discount' ), $count ) . '</p></div>';
        }
    }

    // Handle remove
    if ( isset( $_GET['scd_remove'] ) && check_admin_referer( 'scd_remove_' . (int) $_GET['scd_remove'] ) ) {
        update_user_meta( (int) $_GET['scd_remove'], 'scd_discount_percent', 0 );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Popust uklonjen.', 'silvester-discount' ) . '</p></div>';
    }

    // Get all users with discount
    $users_with_discount = get_users( [
        'meta_key'     => 'scd_discount_percent',
        'meta_compare' => '>',
        'meta_value'   => '0',
        'orderby'      => 'display_name',
    ] );

    // All customers for select
    $all_customers = get_users( [ 'role__in' => [ 'customer', 'subscriber', 'administrator', 'shop_manager' ], 'orderby' => 'display_name', 'number' => 500 ] );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Popusti za kupce', 'silvester-discount' ); ?></h1>

        <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:20px;">

            <!-- ADD / EDIT FORM -->
            <div style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:24px 28px;min-width:480px;flex:0 0 480px;">
                <h2 style="margin-top:0;"><?php esc_html_e( 'Dodaj / uredi popust', 'silvester-discount' ); ?></h2>

                <!-- Search is OUTSIDE the form so Enter doesn't submit -->
                <p style="margin-top:0;">
                    <input type="text" id="scd_search" placeholder="<?php esc_attr_e( 'Pretraži kupce...', 'silvester-discount' ); ?>"
                           style="width:100%;max-width:300px;padding:6px 10px;border:1px solid #8c8f94;border-radius:3px;margin-bottom:6px;"
                           onkeydown="return event.key !== 'Enter';">
                </p>

                <form method="post">
                    <?php wp_nonce_field( 'scd_bulk_save' ); ?>
                    <table class="form-table" style="margin-top:0;">
                        <tr>
                            <th style="padding:8px 0;vertical-align:top;padding-top:12px;">
                                <label><?php esc_html_e( 'Kupci', 'silvester-discount' ); ?></label><br>
                                <small style="font-weight:normal;color:#666;">
                                    <a href="#" id="scd_sel_all" style="text-decoration:none;"><?php esc_html_e( 'Sve', 'silvester-discount' ); ?></a> |
                                    <a href="#" id="scd_sel_none" style="text-decoration:none;"><?php esc_html_e( 'Ništa', 'silvester-discount' ); ?></a>
                                </small>
                            </th>
                            <td style="padding:8px 0;">
                                <div id="scd_user_list" style="max-height:260px;overflow-y:auto;border:1px solid #8c8f94;border-radius:3px;padding:6px 10px;background:#fff;min-width:280px;">
                                    <?php foreach ( $all_customers as $u ) :
                                        $d   = (float) get_user_meta( $u->ID, 'scd_discount_percent', true );
                                        $lbl = esc_html( $u->display_name . ' (' . $u->user_email . ')' . ( $d > 0 ? ' — ' . $d . '%' : '' ) );
                                    ?>
                                    <label class="scd-user-row" style="display:flex;align-items:center;gap:8px;padding:4px 0;cursor:pointer;border-bottom:1px solid #f0f0f0;"
                                           data-search="<?php echo esc_attr( strtolower( $u->display_name . ' ' . $u->user_email ) ); ?>">
                                        <input type="checkbox" name="scd_user_ids[]" value="<?php echo esc_attr( $u->ID ); ?>">
                                        <span><?php echo $lbl; ?></span>
                                        <?php if ( $d > 0 ) : ?><span style="margin-left:auto;color:#de423d;font-weight:600;white-space:nowrap;"><?php echo esc_html( $d ); ?>%</span><?php endif; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description"><?php esc_html_e( 'Odaberi jednog ili više kupaca.', 'silvester-discount' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th style="padding:8px 0;"><label for="scd_percent"><?php esc_html_e( 'Popust (%)', 'silvester-discount' ); ?></label></th>
                            <td style="padding:8px 0;">
                                <input type="number" name="scd_percent" id="scd_percent" value="" min="0" max="100" step="0.01" style="width:80px;"> %
                                <p class="description"><?php esc_html_e( 'Unesite 0 za ukidanje popusta.', 'silvester-discount' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <button type="submit" name="scd_save" class="button button-primary">
                            <?php esc_html_e( 'Spremi popust', 'silvester-discount' ); ?>
                        </button>
                    </p>
                </form>
            </div>

            <!-- CURRENT DISCOUNTS TABLE -->
            <div style="flex:1;min-width:300px;">
                <h2 style="margin-top:0;"><?php esc_html_e( 'Aktivni popusti', 'silvester-discount' ); ?></h2>
                <?php if ( empty( $users_with_discount ) ) : ?>
                    <p><?php esc_html_e( 'Nema aktivnih popusta.', 'silvester-discount' ); ?></p>
                <?php else : ?>
                <table class="widefat striped" style="max-width:700px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Kupac', 'silvester-discount' ); ?></th>
                            <th><?php esc_html_e( 'E-pošta', 'silvester-discount' ); ?></th>
                            <th><?php esc_html_e( 'Popust', 'silvester-discount' ); ?></th>
                            <th><?php esc_html_e( 'Akcija', 'silvester-discount' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $users_with_discount as $u ) :
                            $d        = (float) get_user_meta( $u->ID, 'scd_discount_percent', true );
                            $edit_url = admin_url( 'user-edit.php?user_id=' . $u->ID );
                            $rem_url  = wp_nonce_url( admin_url( 'admin.php?page=scd-discounts&scd_remove=' . $u->ID ), 'scd_remove_' . $u->ID );
                        ?>
                        <tr>
                            <td><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $u->display_name ); ?></a></td>
                            <td><?php echo esc_html( $u->user_email ); ?></td>
                            <td><strong style="color:#de423d;"><?php echo esc_html( $d ); ?>%</strong></td>
                            <td>
                                <a href="<?php echo esc_url( $rem_url ); ?>" class="button button-small"
                                   onclick="return confirm('<?php esc_attr_e( 'Ukloniti popust?', 'silvester-discount' ); ?>')">
                                    <?php esc_html_e( 'Ukloni', 'silvester-discount' ); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
    (function() {
        var search  = document.getElementById('scd_search');
        var rows    = document.querySelectorAll('.scd-user-row');
        var selAll  = document.getElementById('scd_sel_all');
        var selNone = document.getElementById('scd_sel_none');

        if (search) {
            search.addEventListener('input', function() {
                var q = this.value.toLowerCase();
                rows.forEach(function(row) {
                    row.style.display = row.dataset.search.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        }
        if (selAll) selAll.addEventListener('click', function(e) {
            e.preventDefault();
            rows.forEach(function(r) { if (r.style.display !== 'none') r.querySelector('input').checked = true; });
        });
        if (selNone) selNone.addEventListener('click', function(e) {
            e.preventDefault();
            rows.forEach(function(r) { r.querySelector('input').checked = false; });
        });
    })();
    </script>
    <?php
}

// ── Admin: add discount field on user edit screen ─────────────────────────────

add_action( 'show_user_profile', 'scd_user_discount_field' );
add_action( 'edit_user_profile', 'scd_user_discount_field' );

function scd_user_discount_field( $user ) {
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;
    $discount = (float) get_user_meta( $user->ID, 'scd_discount_percent', true );
    ?>
    <h3><?php esc_html_e( 'Popust za kupca', 'silvester-discount' ); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="scd_discount_percent"><?php esc_html_e( 'Postotak popusta (%)', 'silvester-discount' ); ?></label></th>
            <td>
                <input type="number" name="scd_discount_percent" id="scd_discount_percent"
                       value="<?php echo esc_attr( $discount ); ?>"
                       min="0" max="100" step="0.01" class="small-text">
                <p class="description"><?php esc_html_e( 'Unesite postotak popusta (npr. 10 za 10%). Ostavite 0 za bez popusta.', 'silvester-discount' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
    wp_nonce_field( 'scd_save_discount', 'scd_nonce' );
}

add_action( 'personal_options_update', 'scd_save_user_discount' );
add_action( 'edit_user_profile_update', 'scd_save_user_discount' );

function scd_save_user_discount( $user_id ) {
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;
    if ( ! isset( $_POST['scd_nonce'] ) || ! wp_verify_nonce( $_POST['scd_nonce'], 'scd_save_discount' ) ) return;

    $discount = isset( $_POST['scd_discount_percent'] ) ? max( 0, min( 100, (float) $_POST['scd_discount_percent'] ) ) : 0;
    update_user_meta( $user_id, 'scd_discount_percent', $discount );
}

// ── Admin: show discount column in Users list ─────────────────────────────────

add_filter( 'manage_users_columns', function( $cols ) {
    $cols['scd_discount'] = __( 'Popust', 'silvester-discount' );
    return $cols;
} );

add_filter( 'manage_users_custom_column', function( $val, $col, $user_id ) {
    if ( $col !== 'scd_discount' ) return $val;
    $d = (float) get_user_meta( $user_id, 'scd_discount_percent', true );
    return $d > 0 ? '<strong style="color:#de423d">' . $d . '%</strong>' : '—';
}, 10, 3 );

// ── Cart: apply discount as negative fee ──────────────────────────────────────

add_action( 'woocommerce_cart_calculate_fees', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( ! is_user_logged_in() ) return;

    $discount = (float) get_user_meta( get_current_user_id(), 'scd_discount_percent', true );
    if ( $discount <= 0 ) return;

    $subtotal     = $cart->get_subtotal();
    $discount_amt = -1 * ( $subtotal * $discount / 100 );

    $label = sprintf( __( 'Popust (%s%%)', 'silvester-discount' ), $discount );
    $cart->add_fee( $label, $discount_amt, true ); // true = taxable
} );

// ── Cart popup: pass discount info to AJAX handler ────────────────────────────

add_filter( 'silvester_cart_popup_data', function( $data ) {
    if ( ! is_user_logged_in() ) return $data;
    $discount = (float) get_user_meta( get_current_user_id(), 'scd_discount_percent', true );
    if ( $discount > 0 ) {
        $data['discount_label'] = sprintf( __( 'Popust (%s%%)', 'silvester-discount' ), $discount );
    }
    return $data;
} );

// ── Single product page: show customer discount badge ─────────────────────────

add_action( 'woocommerce_single_product_summary', function() {
    if ( ! is_user_logged_in() ) return;
    $discount = (float) get_user_meta( get_current_user_id(), 'scd_discount_percent', true );
    if ( $discount <= 0 ) return;
    echo '<p class="scd-badge" style="display:inline-block;background:linear-gradient(to bottom,#de423d,#8d1f1a);color:#fff;padding:4px 12px;border-radius:4px;font-size:0.85rem;font-weight:600;margin-bottom:12px;">';
    printf( esc_html__( 'Vaš popust: %s%%', 'silvester-discount' ), esc_html( $discount ) );
    echo '</p>';
}, 8 );

// ── Order received / view order: show discount note ───────────────────────────

add_action( 'woocommerce_after_order_details', function( $order ) {
    $fees = $order->get_fees();
    foreach ( $fees as $fee ) {
        if ( $fee->get_amount() < 0 ) {
            echo '<p style="color:#de423d;font-weight:600;margin-bottom:8px;">';
            echo esc_html( $fee->get_name() ) . ': ' . wp_kses_post( wc_price( $fee->get_amount() ) );
            echo '</p>';
            break;
        }
    }
}, 4 );
