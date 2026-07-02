<?php
/**
 * Plugin Name: Davenham Pre-orders
 * Plugin URI:  https://davenhamscouts.org.uk
 * Description: Mark WooCommerce products as pre-order — adds a Pre-order badge on the shop and product page, an optional "available from" date, and changes the Add to Cart button to "Pre-order".
 * Version:     1.0.0
 * Author:      Davenham Scout Group
 * Text Domain: davenham-preorders
 * Requires at least: 6.0
 * Requires PHP: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'DPO_VERSION', '1.0.0' );

final class Davenham_Preorders {

	const FLAG = '_davenham_preorder';       // product meta 'yes'/'no'
	const DATE = '_davenham_preorder_date';  // optional YYYY-MM-DD

	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'boot' ) );
	}

	public static function boot() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Product editor (Inventory tab).
		add_action( 'woocommerce_product_options_inventory_product_data', array( __CLASS__, 'product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_fields' ) );

		// Badges.
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'loop_badge' ), 9 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'single_badge' ), 4 );

		// Button text.
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'loop_button_text' ), 10, 2 );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'single_button_text' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'styles' ) );
	}

	public static function is_preorder( $product ) {
		$id = is_object( $product ) ? $product->get_id() : (int) $product;
		return 'yes' === get_post_meta( $id, self::FLAG, true );
	}

	public static function preorder_date( $product ) {
		$id = is_object( $product ) ? $product->get_id() : (int) $product;
		return (string) get_post_meta( $id, self::DATE, true );
	}

	public static function product_fields() {
		woocommerce_wp_checkbox(
			array(
				'id'          => self::FLAG,
				'label'       => __( 'Pre-order', 'davenham-preorders' ),
				'description' => __( 'Show a "Pre-order" badge and change the button to "Pre-order". Use with backorders enabled if the item is not yet in stock.', 'davenham-preorders' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => self::DATE,
				'label'       => __( 'Available from', 'davenham-preorders' ),
				'type'        => 'date',
				'desc_tip'    => true,
				'description' => __( 'Optional. Shown as "Available from [date]" on the product page.', 'davenham-preorders' ),
			)
		);
	}

	public static function save_product_fields( $product_id ) {
		update_post_meta( $product_id, self::FLAG, ( isset( $_POST[ self::FLAG ] ) && 'yes' === $_POST[ self::FLAG ] ) ? 'yes' : 'no' );
		$date = isset( $_POST[ self::DATE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::DATE ] ) ) : '';
		update_post_meta( $product_id, self::DATE, preg_replace( '/[^0-9\-]/', '', $date ) );
	}

	public static function loop_badge() {
		global $product;
		if ( $product && self::is_preorder( $product ) ) {
			echo '<span class="davenham-preorder-flash">' . esc_html__( 'Pre-order', 'davenham-preorders' ) . '</span>';
		}
	}

	public static function single_badge() {
		global $product;
		if ( ! $product || ! self::is_preorder( $product ) ) {
			return;
		}
		echo '<div class="davenham-preorder-single">';
		echo '<span class="davenham-preorder-flash davenham-preorder-flash--inline">' . esc_html__( 'Pre-order', 'davenham-preorders' ) . '</span>';
		$date = self::preorder_date( $product );
		if ( $date ) {
			$ts    = strtotime( $date );
			$label = $ts ? date_i18n( get_option( 'date_format' ), $ts ) : $date;
			/* translators: %s: date the item is available from. */
			echo '<span class="davenham-preorder-note">' . esc_html( sprintf( __( 'Available from %s', 'davenham-preorders' ), $label ) ) . '</span>';
		}
		echo '</div>';
	}

	public static function loop_button_text( $text, $product ) {
		if ( $product && self::is_preorder( $product ) && $product->is_purchasable() && $product->is_in_stock() ) {
			return __( 'Pre-order', 'davenham-preorders' );
		}
		return $text;
	}

	public static function single_button_text( $text, $product ) {
		if ( $product && self::is_preorder( $product ) ) {
			return __( 'Pre-order', 'davenham-preorders' );
		}
		return $text;
	}

	public static function styles() {
		wp_register_style( 'davenham-preorders', plugin_dir_url( __FILE__ ) . 'assets/preorders.css', array(), DPO_VERSION );
		wp_enqueue_style( 'davenham-preorders' );
	}
}

Davenham_Preorders::init();
