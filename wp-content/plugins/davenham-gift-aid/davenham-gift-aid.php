<?php
/**
 * Plugin Name: Davenham Gift Aid
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Gift Aid for WooCommerce — mark donation products as eligible, capture a Gift Aid declaration at checkout, store it against the order, and export an HMRC Charities Online claim CSV.
 * Version:     1.0.0
 * Author:      Davenham Scout Group
 * Text Domain: davenham-gift-aid
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'DGA_VERSION', '1.0.0' );

final class Davenham_Gift_Aid {

	const ELIGIBLE_META = '_davenham_gift_aid_eligible'; // product meta 'yes'/'no'
	const FIELD_ID      = 'davenham/gift-aid';           // additional checkout field id
	const CHARITY_NAME  = '1st Davenham Scout Group';
	const NONCE         = 'davenham_gift_aid_export';
	const SEED_OPTION   = 'davenham_gift_aid_seeded';

	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'boot' ) );
	}

	public static function boot() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Product editor: "Eligible for Gift Aid" checkbox.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'product_field' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_field' ) );

		// Register the checkout declaration field (block checkout / Store API).
		add_action( 'woocommerce_init', array( __CLASS__, 'register_checkout_field' ) );

		// Record the declaration + eligible amount when an order is placed
		// (both the classic and block/Store-API order flows).
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'record_from_order_id' ), 20, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'record_from_order' ), 20, 1 );

		// Admin: show Gift Aid status on the order + a claims export screen.
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_order_display' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_post_davenham_gift_aid_export', array( __CLASS__, 'handle_export' ) );

		// One-time: mark existing "Donation" products as eligible so it works
		// out of the box (never overrides a product already set).
		add_action( 'admin_init', array( __CLASS__, 'maybe_seed_eligibility' ) );
	}

	/* ---------------------------------------------------------------------
	 * The HMRC model Gift Aid declaration statement.
	 * ------------------------------------------------------------------- */
	public static function declaration_label() {
		return sprintf(
			/* translators: %s: charity name. */
			__( 'Yes, I want to Gift Aid my donation and any donations I make in the future or have made in the past 4 years to %s. I am a UK taxpayer and understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year it is my responsibility to pay any difference.', 'davenham-gift-aid' ),
			self::CHARITY_NAME
		);
	}

	/* ---------------------------------------------------------------------
	 * Product "eligible for Gift Aid" flag
	 * ------------------------------------------------------------------- */
	public static function product_field() {
		woocommerce_wp_checkbox(
			array(
				'id'          => self::ELIGIBLE_META,
				'label'       => __( 'Eligible for Gift Aid', 'davenham-gift-aid' ),
				'description' => __( 'Tick for donations the charity can reclaim Gift Aid on. A Gift Aid declaration is offered at checkout when the basket contains an eligible product.', 'davenham-gift-aid' ),
			)
		);
	}

	public static function save_product_field( $product_id ) {
		$val = ( isset( $_POST[ self::ELIGIBLE_META ] ) && 'yes' === $_POST[ self::ELIGIBLE_META ] ) ? 'yes' : 'no';
		update_post_meta( $product_id, self::ELIGIBLE_META, $val );
	}

	public static function product_is_eligible( $product_id ) {
		return 'yes' === get_post_meta( (int) $product_id, self::ELIGIBLE_META, true );
	}

	/* ---------------------------------------------------------------------
	 * Checkout declaration field (block checkout)
	 * ------------------------------------------------------------------- */
	public static function register_checkout_field() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return; // WooCommerce < 8.9 — block checkout field API unavailable.
		}
		try {
			woocommerce_register_additional_checkout_field(
				array(
					'id'       => self::FIELD_ID,
					'label'    => self::declaration_label(),
					'location' => 'order',
					'type'     => 'checkbox',
					'required' => false,
				)
			);
		} catch ( \Exception $e ) {
			// Field already registered / API changed — fail quietly.
		}
	}

	/**
	 * Read the declaration checkbox value from an order, resilient to the
	 * various meta keys WooCommerce has used for additional order fields.
	 */
	private static function declaration_is_set( $order ) {
		// Official reader first.
		if ( function_exists( 'wc_get_container' ) && class_exists( '\Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields' ) ) {
			try {
				$fields = wc_get_container()->get( \Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields::class );
				if ( $fields && method_exists( $fields, 'get_field_from_object' ) ) {
					$v = $fields->get_field_from_object( self::FIELD_ID, $order, 'other' );
					if ( '' !== $v && null !== $v ) {
						return (bool) $v;
					}
				}
			} catch ( \Exception $e ) {
				// fall through to meta.
			}
		}
		// Meta fallbacks across WC versions.
		foreach ( array( '_wc_order/' . self::FIELD_ID, '_wc_other/' . self::FIELD_ID, self::FIELD_ID ) as $key ) {
			$m = $order->get_meta( $key );
			if ( '' !== $m && null !== $m ) {
				return (bool) $m;
			}
		}
		return false;
	}

	/* ---------------------------------------------------------------------
	 * Record the declaration + eligible amount on the order
	 * ------------------------------------------------------------------- */
	public static function record_from_order_id( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			self::record_from_order( $order );
		}
	}

	public static function record_from_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		if ( 'yes' === $order->get_meta( '_gift_aid' ) ) {
			return; // already recorded
		}
		if ( ! self::declaration_is_set( $order ) ) {
			return;
		}

		// Sum the Gift-Aid-eligible line items (donations only).
		$amount = 0.0;
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( $product_id && self::product_is_eligible( $product_id ) ) {
				$amount += (float) $item->get_total() + (float) $item->get_total_tax();
			}
		}

		if ( $amount <= 0 ) {
			return; // declaration ticked but nothing eligible in the order.
		}

		$order->update_meta_data( '_gift_aid', 'yes' );
		$order->update_meta_data( '_gift_aid_amount', wc_format_decimal( $amount, 2 ) );
		$order->update_meta_data( '_gift_aid_declared_at', current_time( 'mysql' ) );
		$order->update_meta_data( '_gift_aid_statement', self::declaration_label() );
		$order->save();
	}

	/* ---------------------------------------------------------------------
	 * Order admin: show Gift Aid status
	 * ------------------------------------------------------------------- */
	public static function admin_order_display( $order ) {
		if ( ! $order instanceof WC_Order || 'yes' !== $order->get_meta( '_gift_aid' ) ) {
			return;
		}
		$amount = $order->get_meta( '_gift_aid_amount' );
		echo '<div class="address" style="margin-top:12px;padding-top:10px;border-top:1px solid #e5e7eb;">';
		echo '<p><strong>' . esc_html__( 'Gift Aid declared', 'davenham-gift-aid' ) . '</strong></p>';
		if ( $amount ) {
			/* translators: 1: eligible amount, 2: reclaimable amount. */
			echo '<p>' . wp_kses_post(
				sprintf(
					__( 'Eligible donation: %1$s — reclaimable: %2$s', 'davenham-gift-aid' ),
					wc_price( $amount ),
					wc_price( round( (float) $amount * 0.25, 2 ) )
				)
			) . '</p>';
		}
		echo '</div>';
	}

	/* ---------------------------------------------------------------------
	 * Admin: HMRC claim export screen (under WooCommerce)
	 * ------------------------------------------------------------------- */
	public static function admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Gift Aid', 'davenham-gift-aid' ),
			__( 'Gift Aid', 'davenham-gift-aid' ),
			'manage_woocommerce',
			'davenham-gift-aid',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function render_page() {
		$declared = self::get_declared_orders( '', '' );
		$count    = count( $declared );
		$total    = 0.0;
		foreach ( $declared as $o ) {
			$total += (float) $o->get_meta( '_gift_aid_amount' );
		}
		$reclaim = round( $total * 0.25, 2 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Gift Aid', 'davenham-gift-aid' ); ?></h1>
			<p><?php esc_html_e( 'Every Gift Aid declaration made at checkout is recorded here. Export the CSV to build your HMRC Charities Online claim.', 'davenham-gift-aid' ); ?></p>

			<div class="card" style="max-width:640px;padding:16px 18px;">
				<p style="margin:0 0 6px;"><strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong> <?php esc_html_e( 'donations with a Gift Aid declaration', 'davenham-gift-aid' ); ?></p>
				<p style="margin:0;"><?php echo wp_kses_post( sprintf( __( 'Eligible total: %1$s &nbsp;·&nbsp; potential Gift Aid at 25%%: %2$s', 'davenham-gift-aid' ), wc_price( $total ), wc_price( $reclaim ) ) ); ?></p>
			</div>

			<h2 style="margin-top:24px;"><?php esc_html_e( 'Export for HMRC', 'davenham-gift-aid' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="action" value="davenham_gift_aid_export" />
				<p>
					<label style="margin-right:16px;"><?php esc_html_e( 'From', 'davenham-gift-aid' ); ?> <input type="date" name="from" /></label>
					<label style="margin-right:16px;"><?php esc_html_e( 'To', 'davenham-gift-aid' ); ?> <input type="date" name="to" /></label>
				</p>
				<p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Leave the dates blank to export every declaration. The CSV columns match the HMRC Charities Online donations schedule.', 'davenham-gift-aid' ); ?></p>
				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Download CSV', 'davenham-gift-aid' ); ?></button></p>
			</form>
		</div>
		<?php
	}

	/**
	 * All orders carrying a Gift Aid declaration, optionally within a date
	 * range (YYYY-MM-DD). Filtered in PHP so it works with both the legacy
	 * post store and High-Performance Order Storage.
	 */
	private static function get_declared_orders( $from, $to ) {
		$args = array(
			'limit'   => -1,
			'type'    => 'shop_order',
			'status'  => array( 'processing', 'completed', 'on-hold', 'refunded' ),
			'orderby' => 'date',
			'order'   => 'ASC',
		);
		if ( $from ) {
			$args['date_created'] = ( $to ) ? ( $from . '...' . $to ) : ( '>=' . $from );
		} elseif ( $to ) {
			$args['date_created'] = '<=' . $to;
		}

		$orders = wc_get_orders( $args );
		$out    = array();
		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order && 'yes' === $order->get_meta( '_gift_aid' ) ) {
				$out[] = $order;
			}
		}
		return $out;
	}

	public static function handle_export() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export Gift Aid data.', 'davenham-gift-aid' ) );
		}
		check_admin_referer( self::NONCE );

		$from = isset( $_POST['from'] ) ? preg_replace( '/[^0-9\-]/', '', wp_unslash( $_POST['from'] ) ) : '';
		$to   = isset( $_POST['to'] ) ? preg_replace( '/[^0-9\-]/', '', wp_unslash( $_POST['to'] ) ) : '';

		$orders   = self::get_declared_orders( $from, $to );
		$filename = 'gift-aid-claim-' . gmdate( 'Y-m-d' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$out = fopen( 'php://output', 'w' );
		// HMRC Charities Online "donations" schedule columns.
		fputcsv( $out, array( 'Title', 'First name', 'Last name', 'House name or number', 'Postcode', 'Aggregated donations', 'Sponsored event', 'Donation date', 'Amount' ) );

		foreach ( $orders as $order ) {
			$date = $order->get_date_created();
			fputcsv(
				$out,
				array(
					'',
					$order->get_billing_first_name(),
					$order->get_billing_last_name(),
					$order->get_billing_address_1(),
					$order->get_billing_postcode(),
					'',
					'',
					$date ? $date->date( 'd/m/y' ) : '',
					$order->get_meta( '_gift_aid_amount' ),
				)
			);
		}
		fclose( $out );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * One-time seed: flag existing "Donation" products as eligible.
	 * ------------------------------------------------------------------- */
	public static function maybe_seed_eligibility() {
		if ( get_option( self::SEED_OPTION ) ) {
			return;
		}
		$products = wc_get_products(
			array(
				'limit'  => -1,
				'status' => 'publish',
				'return' => 'ids',
			)
		);
		foreach ( $products as $pid ) {
			if ( metadata_exists( 'post', $pid, self::ELIGIBLE_META ) ) {
				continue; // respect an existing choice
			}
			$title      = get_the_title( $pid );
			$is_donation = ( false !== stripos( $title, 'donation' ) );
			update_post_meta( $pid, self::ELIGIBLE_META, $is_donation ? 'yes' : 'no' );
		}
		update_option( self::SEED_OPTION, DGA_VERSION, false );
	}
}

Davenham_Gift_Aid::init();
