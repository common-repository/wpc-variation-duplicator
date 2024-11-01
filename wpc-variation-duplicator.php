<?php
/*
Plugin Name: WPC Variation Duplicator for WooCommerce
Plugin URI: https://wpclever.net/
Description: WPC Variation Duplicator helps you duplicate a variation with all properties in just 1-click.
Version: 1.0.7
Author: WPClever
Author URI: https://wpclever.net
Text Domain: wpc-variation-duplicator
Domain Path: /languages/
Requires Plugins: woocommerce
Requires at least: 4.0
Tested up to: 6.6
WC requires at least: 3.0
WC tested up to: 9.1
*/

! defined( 'WPCVD_VERSION' ) && define( 'WPCVD_VERSION', '1.0.7' );
! defined( 'WPCVD_LITE' ) && define( 'WPCVD_LITE', __FILE__ );
! defined( 'WPCVD_FILE' ) && define( 'WPCVD_FILE', __FILE__ );
! defined( 'WPCVD_PATH' ) && define( 'WPCVD_PATH', plugin_dir_path( __FILE__ ) );
! defined( 'WPCVD_URI' ) && define( 'WPCVD_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCVD_REVIEWS' ) && define( 'WPCVD_REVIEWS', 'https://wordpress.org/support/plugin/wpc-variation-duplicator/reviews/?filter=5' );
! defined( 'WPCVD_SUPPORT' ) && define( 'WPCVD_SUPPORT', 'https://wpclever.net/support?utm_source=support&utm_medium=wpcpq&utm_campaign=wporg' );
! defined( 'WPCVD_CHANGELOG' ) && define( 'WPCVD_CHANGELOG', 'https://wordpress.org/plugins/wpc-variation-duplicator/#developers' );
! defined( 'WPCVD_DISCUSSION' ) && define( 'WPCVD_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-variation-duplicator' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCVD_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpcvd_init' ) ) {
	add_action( 'plugins_loaded', 'wpcvd_init', 11 );

	function wpcvd_init() {
		load_plugin_textdomain( 'wpc-variation-duplicator', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpcvd_notice_wc' );

			return null;
		}

		if ( ! class_exists( 'WPCleverWpcvd' ) && class_exists( 'WC_Product' ) ) {
			class WPCleverWpcvd {
				public function __construct() {
					require_once 'includes/class-backend.php';
				}
			}

			new WPCleverWpcvd();
		}
	}
}

if ( ! function_exists( 'wpcvd_notice_wc' ) ) {
	function wpcvd_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Variation Duplicator</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
