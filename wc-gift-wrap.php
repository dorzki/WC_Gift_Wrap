<?php
/**
 * WooCommerce plugin to add the ability to add gift wrap.
 *
 * Plugin Name: WooCommerce Gift Wrap
 * Plugin URI: https://www.dorzki.co.il/adding-gift-wrap-option-in-woocommerce/
 * Description: WooCommerce plugin to add the ability to add gift wrap.
 * Version: 1.0.0
 * Author: dorzki
 * Author URI: https://www.dorzki.co.il
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: dorzki-wc-gift-wrap
 *
 * @package    WordPress
 * @subpackage Plugins
 * @author     Dor Zuberi <webmaster@dorzki.co.il>
 * @link       https://www.dorzki.co.il
 * @version    1.0.0
 */

// Block if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Enable support for plugin localization and internationalization.
 */
function dorzki_register_plugin_i18n() {

	load_plugin_textdomain( 'dorzki-wc-gift-wrap', false, basename( dirname( __FILE__ ) ) . '/languages/' );

}

add_action( 'plugins_loaded', 'dorzki_register_plugin_i18n' );


/**
 * Checks if WooCommerce is installed and activated.
 *
 * @return bool
 */
function dorzki_check_required_plugins() {

	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

		add_action( 'admin_notices', 'dorzki_display_woocommerce_notice' );

		return false;

	}

	include_once 'class-plugin.php';

}

add_action( 'plugins_loaded', 'dorzki_check_required_plugins' );


/**
 * Displays an admin notice for WooCommerce.
 */
function dorzki_display_woocommerce_notice() {

	$notice = sprintf(
	/* translators: 1: WooCommerce 2: Plugin Name */
		esc_html__( '"%1$s" is required to be installed and activated in order to use "%2$s".', 'dorzki-wc-gift-wrap' ),
		'<strong>' . esc_html__( 'WooCommerce', 'dorzki-wc-gift-wrap' ) . '</strong>',
		'<strong>' . esc_html__( 'WooCommerce Gift Wrap', 'dorzki-wc-gift-wrap' ) . '</strong>'
	);

	echo "<div class='notice notice-error'><p>{$notice}</p></div>";

}
