<?php
/**
 * Plugin Name: Silvester Bazne cijene
 * Description: Isticanje bazne cijene proizvoda i objava cjenika (CSV/XML) prema Zakonu o zaštiti potrošača (primjena od 17.11.2026.).
 * Version: 1.0.0
 * Author: Silvester Shop
 * Text Domain: silvester-bazne-cijene
 */

defined( 'ABSPATH' ) || exit;

const SBC_META         = '_bazna_cijena';
const SBC_CRON_DAILY   = 'sbc_generate_cjenik_daily';
const SBC_CRON_CHANGE  = 'sbc_generate_cjenik_change';
const SBC_ARCHIVE_DAYS = 30;
const SBC_DAILY_HOUR   = '05:00';

// ── Activation / deactivation ────────────────────────────────────────────────

register_activation_hook( __FILE__, function() {
	sbc_fill_missing_base_prices();
	sbc_schedule_daily();
	sbc_create_cjenik_page();
	sbc_add_footer_menu_item();
	wp_schedule_single_event( time() + 60, SBC_CRON_CHANGE );
} );

register_deactivation_hook( __FILE__, function() {
	wp_clear_scheduled_hook( SBC_CRON_DAILY );
	wp_clear_scheduled_hook( SBC_CRON_CHANGE );
} );

add_action( 'init', 'sbc_schedule_daily' );

function sbc_schedule_daily() {
	if ( wp_next_scheduled( SBC_CRON_DAILY ) ) return;
	$next = new DateTime( 'today ' . SBC_DAILY_HOUR, wp_timezone() );
	if ( $next->getTimestamp() <= time() ) $next->modify( '+1 day' );
	wp_schedule_event( $next->getTimestamp(), 'daily', SBC_CRON_DAILY );
}

function sbc_create_cjenik_page() {
	if ( get_page_by_path( 'cjenik' ) ) return;
	wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Cjenik',
		'post_name'    => 'cjenik',
		'post_content' => '[silvester_cjenik]',
	] );
}

// ── Base price storage ───────────────────────────────────────────────────────

/** Products that carry their own base price (variable parents aggregate from variations). */
function sbc_has_own_base( $product ) {
	return $product && ! $product->is_type( [ 'variable', 'grouped', 'external' ] );
}

function sbc_get_base( $product ) {
	$value = $product->get_meta( SBC_META, true );
	return '' === $value ? null : (float) $value;
}

/**
 * Fill empty base prices with the regular price (sale prices excluded).
 * Used once on activation and from the admin page.
 */
function sbc_fill_missing_base_prices() {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->postmeta} rp ON rp.post_id = p.ID AND rp.meta_key = '_regular_price' AND rp.meta_value <> ''
		 LEFT JOIN {$wpdb->postmeta} b ON b.post_id = p.ID AND b.meta_key = %s
		 WHERE p.post_type IN ('product','product_variation') AND p.post_status NOT IN ('trash','auto-draft')
		   AND ( b.meta_value IS NULL OR b.meta_value = '' )",
		SBC_META
	) );
	foreach ( $ids as $id ) {
		update_post_meta( $id, SBC_META, wc_format_decimal( get_post_meta( $id, '_regular_price', true ) ) );
	}
	return count( $ids );
}

// New products (admin, CSV import, REST): base price = price on the day first offered.
add_action( 'woocommerce_after_product_object_save', function( $product ) {
	if ( ! sbc_has_own_base( $product ) || '' !== (string) get_post_meta( $product->get_id(), SBC_META, true ) ) return;
	$regular = $product->get_regular_price( 'edit' );
	if ( '' !== $regular ) update_post_meta( $product->get_id(), SBC_META, wc_format_decimal( $regular ) );
} );

// ── Admin fields ─────────────────────────────────────────────────────────────

function sbc_field_description() {
	return __( 'Maloprodajna cijena koja je vrijedila 10.9.2026. (bez akcija). Za proizvode uvedene kasnije: cijena na dan prve ponude.', 'silvester-bazne-cijene' );
}

add_action( 'woocommerce_product_options_pricing', function() {
	woocommerce_wp_text_input( [
		'id'          => SBC_META,
		'label'       => __( 'Bazna cijena', 'silvester-bazne-cijene' ) . ' (' . get_woocommerce_currency_symbol() . ')',
		'data_type'   => 'price',
		'desc_tip'    => true,
		'description' => sbc_field_description(),
	] );
} );

add_action( 'woocommerce_admin_process_product_object', function( $product ) {
	if ( isset( $_POST[ SBC_META ] ) ) {
		$product->update_meta_data( SBC_META, wc_format_decimal( wp_unslash( $_POST[ SBC_META ] ) ) );
	}
} );

add_action( 'woocommerce_variation_options_pricing', function( $loop, $variation_data, $variation ) {
	woocommerce_wp_text_input( [
		'id'            => SBC_META . "[{$loop}]",
		'name'          => SBC_META . "[{$loop}]",
		'value'         => get_post_meta( $variation->ID, SBC_META, true ),
		'label'         => __( 'Bazna cijena', 'silvester-bazne-cijene' ) . ' (' . get_woocommerce_currency_symbol() . ')',
		'data_type'     => 'price',
		'desc_tip'      => true,
		'description'   => sbc_field_description(),
		'wrapper_class' => 'form-row form-row-full',
	] );
}, 10, 3 );

add_action( 'woocommerce_admin_process_variation_object', function( $variation, $i ) {
	if ( isset( $_POST[ SBC_META ][ $i ] ) ) {
		$variation->update_meta_data( SBC_META, wc_format_decimal( wp_unslash( $_POST[ SBC_META ][ $i ] ) ) );
	}
}, 10, 2 );

// ── Frontend display ─────────────────────────────────────────────────────────

add_filter( 'woocommerce_get_price_html', function( $html, $product ) {
	if ( '' === $html || ( is_admin() && ! wp_doing_ajax() ) ) return $html;

	if ( $product->is_type( 'variable' ) ) {
		$bases = [];
		foreach ( $product->get_visible_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && null !== ( $b = sbc_get_base( $child ) ) ) {
				$bases[] = wc_get_price_to_display( $child, [ 'price' => $b ] );
			}
		}
		if ( ! $bases ) return $html;
		$min = min( $bases );
		$max = max( $bases );
		$value = $min === $max ? wc_price( $min ) : wc_format_price_range( $min, $max );
	} elseif ( sbc_has_own_base( $product ) && null !== ( $b = sbc_get_base( $product ) ) ) {
		$value = wc_price( wc_get_price_to_display( $product, [ 'price' => $b ] ) );
	} else {
		return $html;
	}

	$tip = __( 'Bazna cijena je maloprodajna cijena proizvoda koja je vrijedila 10.9.2026. (bez akcija). Za proizvode uvedene kasnije to je cijena na dan prve ponude.', 'silvester-bazne-cijene' );

	return $html . '<span class="bazna-cijena">' . esc_html__( 'Bazna cijena:', 'silvester-bazne-cijene' ) . ' ' . $value
		. '<span class="sbc-info" tabindex="0" role="button" aria-label="' . esc_attr__( 'Što je bazna cijena?', 'silvester-bazne-cijene' ) . '">?'
		. '<span class="sbc-tip" role="tooltip">' . esc_html( $tip ) . '</span></span></span>';
}, 20, 2 );

add_action( 'wp_head', function() {
	?>
	<style>
	/* Fixed size/colour: theme price rules (e.g. .entry-summary .price .woocommerce-Price-amount) would otherwise make it as big as the real price */
	.bazna-cijena{display:block;position:relative;margin-top:4px;font-size:14px!important;font-weight:400!important;color:#555!important}
	.bazna-cijena .woocommerce-Price-amount{font-size:inherit!important;font-weight:inherit!important;color:inherit!important}
	.sbc-info{display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;margin-left:6px;border-radius:50%;background:#555;color:#fff;font-size:11px;font-weight:700;line-height:1;cursor:help;vertical-align:1px;user-select:none}
	.sbc-info:focus{outline:2px solid #999;outline-offset:1px}
	/* Opens upward over the full price block so card overflow never clips it */
	.sbc-tip{display:none;position:absolute;bottom:calc(100% + 8px);left:0;right:0;max-width:300px;z-index:30;padding:12px 14px;border-radius:4px;background:#333;color:#fff;font-size:13px;font-weight:400;line-height:1.5;text-align:center;text-transform:none;letter-spacing:normal;white-space:normal;box-shadow:0 2px 8px rgba(0,0,0,.2)}
	.sbc-info:hover .sbc-tip,.sbc-info:focus .sbc-tip,.sbc-info.is-open .sbc-tip{display:block}
	</style>
	<?php
} );

// Tap to toggle on touch devices; tap outside or Esc closes.
add_action( 'wp_footer', function() {
	?>
	<script>
	(function(){
		function closeAll(except){document.querySelectorAll('.sbc-info.is-open').forEach(function(el){if(el!==except){el.classList.remove('is-open');el.blur();}});}
		document.addEventListener('click',function(e){
			var info=e.target.closest('.sbc-info');
			closeAll(info);
			if(info){e.preventDefault();info.classList.toggle('is-open');}
		});
		document.addEventListener('keydown',function(e){if(e.key==='Escape')closeAll(null);});
	})();
	</script>
	<?php
} );

// ── Price list (cjenik) ──────────────────────────────────────────────────────

function sbc_dir() {
	$up = wp_upload_dir();
	return [ 'path' => $up['basedir'] . '/cjenik', 'url' => $up['baseurl'] . '/cjenik' ];
}

// Regenerate shortly after any product change (debounced): add/edit, price, stock (incl. orders), import.
function sbc_schedule_regen() {
	if ( ! wp_next_scheduled( SBC_CRON_CHANGE ) ) {
		wp_schedule_single_event( time() + 120, SBC_CRON_CHANGE );
	}
}
add_action( 'woocommerce_after_product_object_save', 'sbc_schedule_regen', 20 );

// Trash, restore and delete don't go through a product save.
foreach ( [ 'trashed_post', 'untrashed_post', 'before_delete_post' ] as $sbc_hook ) {
	add_action( $sbc_hook, function( $post_id ) {
		if ( in_array( get_post_type( $post_id ), [ 'product', 'product_variation' ], true ) ) sbc_schedule_regen();
	} );
}

add_action( SBC_CRON_DAILY, 'sbc_generate_cjenik' );
add_action( SBC_CRON_CHANGE, 'sbc_generate_cjenik' );

function sbc_cjenik_rows() {
	$rows = [];
	$page = 1;
	do {
		$products = wc_get_products( [ 'status' => 'publish', 'limit' => 200, 'page' => $page++, 'orderby' => 'ID', 'order' => 'ASC' ] );
		foreach ( $products as $product ) {
			if ( 'hidden' === $product->get_catalog_visibility() ) continue;
			$brands = wp_get_post_terms( $product->get_id(), 'product_brand', [ 'fields' => 'names' ] );
			$brand  = is_wp_error( $brands ) ? '' : implode( ', ', $brands );
			$items  = $product->is_type( 'variable' )
				? array_filter( array_map( 'wc_get_product', $product->get_visible_children() ) )
				: ( sbc_has_own_base( $product ) ? [ $product ] : [] );

			foreach ( $items as $item ) {
				if ( '' === $item->get_price() ) continue; // price on request
				$on_sale = $item->is_on_sale();
				$price   = wc_get_price_to_display( $item );
				$base    = sbc_get_base( $item );
				$rows[]  = [
					'naziv'                     => wp_strip_all_tags( $item->get_name() ),
					'sifra'                     => $item->get_sku() ?: (string) $item->get_id(),
					'marka'                     => $brand,
					'jedinica_mjere'            => 'kom',
					'cijena_za_jedinicu_mjere'  => sbc_num( $price ),
					'maloprodajna_cijena'       => sbc_num( wc_get_price_to_display( $item, [ 'price' => $item->get_regular_price() ] ) ),
					'posebni_oblik_prodaje'     => $on_sale ? 'Akcija' : '',
					'cijena_posebnog_oblika'    => $on_sale ? sbc_num( $price ) : '',
					'bazna_cijena'              => null === $base ? '' : sbc_num( wc_get_price_to_display( $item, [ 'price' => $base ] ) ),
					'barkod'                    => method_exists( $item, 'get_global_unique_id' ) ? $item->get_global_unique_id() : '',
					'dostupnost'                => $item->is_in_stock() ? 'Dostupno' : 'Nije dostupno',
					'url'                       => $item->get_permalink(),
				];
			}
		}
	} while ( count( $products ) === 200 );
	return $rows;
}

function sbc_num( $n ) {
	return number_format( (float) $n, 2, '.', '' );
}

function sbc_generate_cjenik() {
	$dir = sbc_dir();
	wp_mkdir_p( $dir['path'] );
	$rows  = sbc_cjenik_rows();
	$stamp = wp_date( 'Y-m-d_Hi' );
	$cols  = array_keys( $rows[0] ?? [ 'naziv' => '' ] );

	// CSV (UTF-8 with BOM, ; separator)
	$csv = fopen( "{$dir['path']}/cjenik_{$stamp}.csv", 'w' );
	fwrite( $csv, "\xEF\xBB\xBF" );
	fputcsv( $csv, $cols, ';', '"', '' );
	foreach ( $rows as $r ) fputcsv( $csv, $r, ';', '"', '' );
	fclose( $csv );

	// XML
	$xml = new XMLWriter();
	$xml->openUri( "{$dir['path']}/cjenik_{$stamp}.xml" );
	$xml->setIndent( true );
	$xml->startDocument( '1.0', 'UTF-8' );
	$xml->startElement( 'cjenik' );
	$xml->writeAttribute( 'trgovac', get_bloginfo( 'name' ) );
	$xml->writeAttribute( 'web', home_url( '/' ) );
	$xml->writeAttribute( 'vrijeme_objave', wp_date( 'c' ) );
	$xml->writeAttribute( 'valuta', get_woocommerce_currency() );
	foreach ( $rows as $r ) {
		$xml->startElement( 'proizvod' );
		foreach ( $r as $k => $v ) $xml->writeElement( $k, (string) $v );
		$xml->endElement();
	}
	$xml->endElement();
	$xml->endDocument();
	$xml->flush();

	// Stable "latest" copies
	copy( "{$dir['path']}/cjenik_{$stamp}.csv", "{$dir['path']}/cjenik.csv" );
	copy( "{$dir['path']}/cjenik_{$stamp}.xml", "{$dir['path']}/cjenik.xml" );

	// Archive retention
	$cutoff = time() - SBC_ARCHIVE_DAYS * DAY_IN_SECONDS;
	foreach ( array_merge( glob( "{$dir['path']}/cjenik_*.csv" ) ?: [], glob( "{$dir['path']}/cjenik_*.xml" ) ?: [] ) as $file ) {
		if ( filemtime( $file ) < $cutoff ) @unlink( $file );
	}
	update_option( 'sbc_last_generated', time(), false );
}

function sbc_archive_files() {
	$dir   = sbc_dir();
	$files = glob( "{$dir['path']}/cjenik_*.csv" ) ?: [];
	rsort( $files );
	return array_map( function( $f ) use ( $dir ) {
		$base = basename( $f, '.csv' );
		$ts   = DateTime::createFromFormat( 'Y-m-d_Hi', substr( $base, 7 ), wp_timezone() );
		return [
			'label' => $ts ? $ts->format( 'd.m.Y. H:i' ) : $base,
			'csv'   => "{$dir['url']}/{$base}.csv",
			'xml'   => "{$dir['url']}/{$base}.xml",
		];
	}, $files );
}

/** Rows of the current published price list (cjenik.csv), generating it if missing. */
function sbc_current_rows() {
	$file = sbc_dir()['path'] . '/cjenik.csv';
	if ( ! file_exists( $file ) ) sbc_generate_cjenik();
	$fh = @fopen( $file, 'r' );
	if ( ! $fh ) return [];
	fseek( $fh, 3 ); // skip BOM
	$cols = fgetcsv( $fh, 0, ';', '"', '' );
	$rows = [];
	while ( ( $r = fgetcsv( $fh, 0, ';', '"', '' ) ) !== false ) {
		if ( count( $r ) === count( $cols ) ) $rows[] = array_combine( $cols, $r );
	}
	fclose( $fh );
	return $rows;
}

add_shortcode( 'silvester_cjenik', function() {
	$dir   = sbc_dir();
	$rows  = sbc_current_rows();
	$files = sbc_archive_files();
	$last  = get_option( 'sbc_last_generated' );
	$price = function( $v ) { return '' === $v ? '' : wc_price( (float) $v ); };
	ob_start();
	?>
	<div class="sbc-cjenik">
		<p>
			<?php esc_html_e( 'Cjenik svih proizvoda u ponudi s maloprodajnom i baznom cijenom.', 'silvester-bazne-cijene' ); ?>
			<?php if ( $last ) : ?>
				<?php printf( esc_html__( 'Zadnje ažuriranje: %s.', 'silvester-bazne-cijene' ), esc_html( wp_date( 'd.m.Y. H:i', $last ) ) ); ?>
			<?php endif; ?>
		</p>
		<p class="sbc-downloads">
			<?php esc_html_e( 'Preuzmi cjenik:', 'silvester-bazne-cijene' ); ?>
			<a href="<?php echo esc_url( $dir['url'] . '/cjenik.csv' ); ?>">CSV</a> ·
			<a href="<?php echo esc_url( $dir['url'] . '/cjenik.xml' ); ?>">XML</a>
		</p>

		<input type="search" class="sbc-search" placeholder="<?php esc_attr_e( 'Pretraži po nazivu, šifri ili marki…', 'silvester-bazne-cijene' ); ?>" aria-label="<?php esc_attr_e( 'Pretraži cjenik', 'silvester-bazne-cijene' ); ?>">
		<p class="sbc-count"><span><?php echo esc_html( count( $rows ) ); ?></span> <?php esc_html_e( 'proizvoda', 'silvester-bazne-cijene' ); ?></p>

		<div class="sbc-table-wrap">
			<table class="sbc-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Naziv', 'silvester-bazne-cijene' ); ?></th>
						<th><?php esc_html_e( 'Šifra', 'silvester-bazne-cijene' ); ?></th>
						<th><?php esc_html_e( 'Marka', 'silvester-bazne-cijene' ); ?></th>
						<th><?php esc_html_e( 'Jed. mjere', 'silvester-bazne-cijene' ); ?></th>
						<th class="num"><?php esc_html_e( 'Maloprodajna cijena', 'silvester-bazne-cijene' ); ?></th>
						<th class="num"><?php esc_html_e( 'Akcijska cijena', 'silvester-bazne-cijene' ); ?></th>
						<th class="num"><?php esc_html_e( 'Bazna cijena', 'silvester-bazne-cijene' ); ?></th>
						<th><?php esc_html_e( 'Dostupnost', 'silvester-bazne-cijene' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $r['url'] ); ?>"><?php echo esc_html( $r['naziv'] ); ?></a></td>
							<td><?php echo esc_html( $r['sifra'] ); ?></td>
							<td><?php echo esc_html( $r['marka'] ); ?></td>
							<td><?php echo esc_html( $r['jedinica_mjere'] ); ?></td>
							<td class="num"><?php echo wp_kses_post( $price( $r['maloprodajna_cijena'] ) ); ?></td>
							<td class="num"><?php echo wp_kses_post( $price( $r['cijena_posebnog_oblika'] ) ); ?></td>
							<td class="num"><?php echo wp_kses_post( $price( $r['bazna_cijena'] ) ); ?></td>
							<td><?php echo esc_html( $r['dostupnost'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $files ) : ?>
			<details class="sbc-archive">
				<summary><?php printf( esc_html__( 'Arhiva cjenika (zadnjih %d dana)', 'silvester-bazne-cijene' ), SBC_ARCHIVE_DAYS ); ?></summary>
				<ul>
					<?php foreach ( $files as $f ) : ?>
						<li><?php echo esc_html( $f['label'] ); ?> — <a href="<?php echo esc_url( $f['csv'] ); ?>">CSV</a> · <a href="<?php echo esc_url( $f['xml'] ); ?>">XML</a></li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>
	</div>

	<style>
	.sbc-cjenik .sbc-search{width:100%;max-width:420px;padding:10px 12px;border:1px solid #d1d5db;border-radius:4px;margin:8px 0}
	.sbc-cjenik .sbc-count{font-size:13px;color:#6b7280;margin:0 0 8px}
	.sbc-table-wrap{overflow-x:auto;border:1px solid #e5e7eb;border-radius:4px}
	.sbc-table{width:100%;border-collapse:collapse;font-size:14px}
	.sbc-table th,.sbc-table td{padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}
	.sbc-table th{background:#f3f4f6;font-weight:600;white-space:nowrap;position:sticky;top:0}
	.sbc-table td.num,.sbc-table th.num{text-align:right;white-space:nowrap}
	.sbc-table tbody tr:hover{background:#f9fafb}
	.sbc-table a{color:inherit}
	.sbc-table a:hover{color:#c8102e}
	.sbc-cjenik .sbc-downloads a,.sbc-cjenik .sbc-archive a{color:#c8102e;text-decoration:underline;font-weight:600}
	.sbc-cjenik .sbc-archive{margin-top:24px}
	.sbc-cjenik .sbc-archive summary{cursor:pointer;font-weight:600}
	</style>
	<script>
	(function(){
		var root=document.currentScript.closest('.sbc-cjenik'),input=root.querySelector('.sbc-search'),
			rows=[].slice.call(root.querySelectorAll('.sbc-table tbody tr')),count=root.querySelector('.sbc-count span');
		rows.forEach(function(tr){tr._t=tr.textContent.toLowerCase();});
		input.addEventListener('input',function(){
			var q=input.value.trim().toLowerCase(),n=0;
			rows.forEach(function(tr){var show=!q||tr._t.indexOf(q)>-1;tr.style.display=show?'':'none';if(show)n++;});
			count.textContent=n;
		});
	})();
	</script>
	<?php
	return ob_get_clean();
} );

// ── Footer menu link ─────────────────────────────────────────────────────────

/** Add the Cjenik page to the footer legal menu once (on live this runs on activation). */
function sbc_add_footer_menu_item() {
	if ( get_option( 'sbc_menu_added' ) ) return;
	$page      = get_page_by_path( 'cjenik' );
	$locations = get_nav_menu_locations();
	$menu_id   = $locations['legal-menu'] ?? 0;
	if ( ! $page || ! $menu_id ) return;

	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
		if ( (int) $item->object_id === $page->ID ) { update_option( 'sbc_menu_added', 1 ); return; }
	}
	wp_update_nav_menu_item( $menu_id, 0, [
		'menu-item-object-id' => $page->ID,
		'menu-item-object'    => 'page',
		'menu-item-type'      => 'post_type',
		'menu-item-status'    => 'publish',
	] );
	update_option( 'sbc_menu_added', 1 );
}
add_action( 'admin_init', 'sbc_add_footer_menu_item' );

// ── Admin page ───────────────────────────────────────────────────────────────

add_action( 'admin_menu', function() {
	add_submenu_page( 'woocommerce', __( 'Bazne cijene', 'silvester-bazne-cijene' ), __( 'Bazne cijene', 'silvester-bazne-cijene' ), 'manage_woocommerce', 'sbc-bazne-cijene', 'sbc_admin_page' );
} );

function sbc_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) return;

	if ( isset( $_POST['sbc_fill'] ) && check_admin_referer( 'sbc_admin' ) ) {
		$n = sbc_fill_missing_base_prices();
		echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( 'Popunjeno baznih cijena: %d', 'silvester-bazne-cijene' ), $n ) . '</p></div>';
	}
	if ( isset( $_POST['sbc_generate'] ) && check_admin_referer( 'sbc_admin' ) ) {
		sbc_generate_cjenik();
		echo '<div class="notice notice-success"><p>' . esc_html__( 'Cjenik je generiran.', 'silvester-bazne-cijene' ) . '</p></div>';
	}

	global $wpdb;
	$missing = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->postmeta} rp ON rp.post_id = p.ID AND rp.meta_key = '_regular_price' AND rp.meta_value <> ''
		 LEFT JOIN {$wpdb->postmeta} b ON b.post_id = p.ID AND b.meta_key = %s
		 WHERE p.post_type IN ('product','product_variation') AND p.post_status NOT IN ('trash','auto-draft')
		   AND ( b.meta_value IS NULL OR b.meta_value = '' )",
		SBC_META
	) );
	$last = get_option( 'sbc_last_generated' );
	$next = wp_next_scheduled( SBC_CRON_DAILY );
	$dir  = sbc_dir();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bazne cijene i cjenik', 'silvester-bazne-cijene' ); ?></h1>
		<p><?php esc_html_e( 'Bazna cijena = maloprodajna cijena na dan 10.9.2026. (bez akcija). Za proizvode uvedene nakon toga: cijena na dan prve ponude. Uređuje se na svakom proizvodu / varijaciji u polju „Bazna cijena”.', 'silvester-bazne-cijene' ); ?></p>
		<table class="widefat striped" style="max-width:700px">
			<tr><td><?php esc_html_e( 'Proizvoda/varijacija bez bazne cijene', 'silvester-bazne-cijene' ); ?></td><td><strong><?php echo esc_html( $missing ); ?></strong></td></tr>
			<tr><td><?php esc_html_e( 'Zadnje generiranje cjenika', 'silvester-bazne-cijene' ); ?></td><td><?php echo $last ? esc_html( wp_date( 'd.m.Y. H:i', $last ) ) : '—'; ?></td></tr>
			<tr><td><?php esc_html_e( 'Sljedeće dnevno generiranje', 'silvester-bazne-cijene' ); ?></td><td><?php echo $next ? esc_html( wp_date( 'd.m.Y. H:i', $next ) ) : '—'; ?></td></tr>
			<tr><td><?php esc_html_e( 'Važeći cjenik', 'silvester-bazne-cijene' ); ?></td><td><a href="<?php echo esc_url( $dir['url'] . '/cjenik.csv' ); ?>">CSV</a> · <a href="<?php echo esc_url( $dir['url'] . '/cjenik.xml' ); ?>">XML</a></td></tr>
		</table>
		<form method="post" style="margin-top:1em">
			<?php wp_nonce_field( 'sbc_admin' ); ?>
			<button class="button" name="sbc_fill" value="1"><?php esc_html_e( 'Popuni prazne bazne cijene iz redovne cijene', 'silvester-bazne-cijene' ); ?></button>
			<button class="button button-primary" name="sbc_generate" value="1"><?php esc_html_e( 'Generiraj cjenik sada', 'silvester-bazne-cijene' ); ?></button>
		</form>
	</div>
	<?php
}
