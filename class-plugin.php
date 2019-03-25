<?php
/**
 * Main plugin file.
 *
 * @package    dorzki\WooCommerce\Gift_Wrap
 * @subpackage Plugin
 * @author     Dor Zuberi <webmaster@dorzki.co.il>
 * @link       https://www.dorzki.co.il
 * @version    1.0.0
 */

namespace dorzki\WooCommerce\Gift_Wrap;

// Block if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Plugin
 *
 * @package dorzki\WooCommerce\Gift_Wrap
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var null|Plugin
	 */
	private static $instance = null;


	/* ------------------------------------------ */


	/**
	 * Plugin constructor.
	 */
	public function __construct() {

		add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_gift_wrap_tab' ] );

		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'save_gift_wrap_field_value' ] );
		add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'save_gift_wrap_field_value_session' ], 10, 2 );

		add_filter( 'woocommerce_get_item_data', [ $this, 'display_gift_wrap_on_cart_page' ], 10, 2 );

		add_action( 'woocommerce_product_data_panels', [ $this, 'gift_wrap_options_panel' ] );
		add_action( 'woocommerce_process_product_meta_simple', [ $this, 'gift_wrap_save_options' ] );

		add_action( 'woocommerce_before_add_to_cart_quantity', [ $this, 'print_gift_wrap_field' ] );

		add_action( 'woocommerce_before_calculate_totals', [ $this, 'calculate_gift_wrap' ] );
		add_action( 'woocommerce_add_order_item_meta', [ $this, 'save_gift_wrap_order' ], 10, 2 );
		add_action( 'woocommerce_order_item_display_meta_key', [ $this, 'display_gift_wrap_order_key' ] );
		add_action( 'woocommerce_order_item_display_meta_value', [ $this, 'display_gift_wrap_order_value' ], 10, 2 );

	}


	/* ------------------------------------------ */


	/**
	 * Add new tab to woocommerce product editing screen.
	 *
	 * @param array $tabs list of tabs and configuration.
	 *
	 * @return array
	 */
	public function add_gift_wrap_tab( $tabs ) {

		$tabs['product_gift_wrap'] = [
			'label'  => __( 'Gift Wrap', 'dorzki-wc-gift-wrap' ),
			'target' => 'gift_wrap_options',
			'class'  => 'show_if_simple',
		];

		return $tabs;

	}


	/* ------------------------------------------ */


	/**
	 * Save the field value to current cart session.
	 *
	 * @param array $cart_item cart item data.
	 *
	 * @return array
	 */
	public function save_gift_wrap_field_value( $cart_item ) {

		$field_value = ( isset( $_POST['wrap_as_gift'] ) ) ? sanitize_text_field( $_POST['wrap_as_gift'] ) : null;

		if ( ! empty( $field_value ) ) {
			$cart_item['wrap_as_gift'] = $field_value;
		}

		return $cart_item;

	}


	/**
	 * Stores cart item field data to current cart session.
	 *
	 * @param array $cart_item_data cart item data.
	 * @param array $values         product item data.
	 *
	 * @return array
	 */
	public function save_gift_wrap_field_value_session( $cart_item_data, $values ) {

		if ( isset( $values['wrap_as_gift'] ) ) {

			$cart_item_data['wrap_as_gift'] = $values['wrap_as_gift'];

		}

		return $cart_item_data;

	}


	/* ------------------------------------------ */


	/**
	 * Display gift wrap if user selected on cart page.
	 *
	 * @param array $item_data item registered data.
	 * @param array $cart_item item saved data.
	 *
	 * @return array
	 */
	public function display_gift_wrap_on_cart_page( $item_data, $cart_item ) {

		if ( ! empty( $cart_item['wrap_as_gift'] ) ) {

			$price = get_post_meta( $cart_item['data']->get_ID(), '_gift_wrap_price', true );
			$price = ( empty( $price ) ) ? __( 'FREE', 'dorzki-wc-gift-wrap' ) : wc_price( $price );

			$item_data[] = [
				'name'  => esc_html__( 'Gift Wrap', 'dorzki-wc-gift-wrap' ),
				'value' => sprintf( esc_html__( 'Yes (%s)', 'dorzki-wc-gift-wrap' ), $price ),
			];

		}

		return $item_data;

	}


	/* ------------------------------------------ */


	/**
	 * Print gift wrap panel fields.
	 */
	public function gift_wrap_options_panel() {

		echo "<div id='gift_wrap_options' class='panel woocommerce_options_panel'>";
		echo "	<div class='options_group'>";

		woocommerce_wp_checkbox( [
			'id'          => '_gift_wrap_enabled',
			'label'       => __( 'Enable Gift Wrap', 'dorzki-wc-gift-wrap' ),
			'desc_tip'    => 'true',
			'description' => __( 'Check in order to enable gift wrapping for this product.', 'dorzki-wc-gift-wrap' ),
		] );

		woocommerce_wp_text_input( [
			'id'                => '_gift_wrap_price',
			'label'             => __( 'Gift Wrap Fee', 'dorzki-wc-gift-wrap' ),
			'desc_tip'          => 'true',
			'description'       => __( 'Set service fee for this product ( 0 = Free ).', 'dorzki-wc-gift-wrap' ),
			'type'              => 'number',
			'custom_attributes' => [
				'min'  => 0,
				'step' => 0.1,
			],
		] );

		echo "	</div>";
		echo "</div>";

	}


	/**
	 * Save gift wrap options panel fields data.
	 *
	 * @param int $product_id current product id.
	 */
	public function gift_wrap_save_options( $product_id ) {

		update_post_meta( $product_id, '_gift_wrap_enabled', $_POST['_gift_wrap_enabled'] );
		update_post_meta( $product_id, '_gift_wrap_price', $_POST['_gift_wrap_price'] );

	}


	/* ------------------------------------------ */


	/**
	 * Display the gift wrap field on product page.
	 */
	public function print_gift_wrap_field() {

		global $product;

		if ( ! get_post_meta( $product->get_ID(), '_gift_wrap_enabled', true ) ) {
			return;
		}

		$price = get_post_meta( $product->get_ID(), '_gift_wrap_price', true );
		$price = ( empty( $price ) ) ? __( 'FREE', 'dorzki-wc-gift-wrap' ) : wc_price( $price );

		$text = sprintf( __( 'Gift Wrap (%s)', 'dorzki-wc-gift-wrap' ), $price );

		echo "<div class='gift-wrap-wrapper'>";
		echo "	<label><input type='checkbox' id='wrap_as_gift' name='wrap_as_gift' value='1'>{$text}</label>";
		echo "</div>";

	}


	/* ------------------------------------------ */


	/**
	 * Change product pricing to include gift wrap price.
	 *
	 * @param \WC_Cart $cart woocommerce user cart.
	 */
	public function calculate_gift_wrap( $cart ) {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $product_data ) {

			if ( isset( $product_data['wrap_as_gift'] ) ) {

				$price = get_post_meta( $product_data['data']->get_ID(), '_gift_wrap_price', true );

				if ( empty( $price ) ) {
					continue;
				}

				$product_data['data']->set_price( $product_data['data']->get_price() + $price );

			}

		}

	}


	/**
	 * Saves the order data to database.
	 *
	 * @param int   $item_id order item id.
	 * @param array $values  order item data.
	 */
	public function save_gift_wrap_order( $item_id, $values ) {

		if ( ! empty( $values['wrap_as_gift'] ) ) {
			wc_add_order_item_meta( $item_id, 'wrap_as_gift', $values['wrap_as_gift'] );
		}

	}


	/**
	 * Change the admin meta display key.
	 *
	 * @param string $display_key meta display key.
	 *
	 * @return string
	 */
	public function display_gift_wrap_order_key( $display_key ) {

		return ( 'wrap_as_gift' === $display_key ) ? __( 'Gift Wrap', 'dorzki-wc-gift-wrap' ) : $display_key;

	}


	/**
	 * Change the admin meta display value.
	 *
	 * @param mixed  $display_value meta display value.
	 * @param object $meta          meta details.
	 *
	 * @return mixed
	 */
	public function display_gift_wrap_order_value( $display_value, $meta ) {

		if ( 'wrap_as_gift' === $meta->key ) {
			return __( 'Yes', 'dorzki-wc-gift-wrap' );
		}

		return $display_value;

	}


	/* ------------------------------------------ */


	/**
	 * Retrieve plugin instance.
	 *
	 * @return Plugin|null
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;

	}

}

// initiate plugin.
Plugin::get_instance();
