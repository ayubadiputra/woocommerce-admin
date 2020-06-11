<?php
/**
 * WooCommerce Marketing > Coupons.
 *
 * NOTE: DO NOT edit this file in WooCommerce core, this is generated from woocommerce-admin.
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features;

use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Coupon_Page_Moved;
use Automattic\WooCommerce\Admin\Loader;

/**
 * Contains backend logic for the Coupons feature.
 */
class Coupons {

	use CouponsMovedTrait;

	/**
	 * Class instance.
	 *
	 * @var Coupons instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {

		if ( ! is_admin() ) {
			return;
		}

		/**
		 * Filter to override this feature.
		 *
		 * @param bool $disabled False.
		 */
		if ( apply_filters( 'woocommerce_admin_coupons', false ) ) {
			return;
		}

		// Only support coupon modifications if coupons are enabled.
		if ( ! wc_coupons_enabled() ) {
			return;
		}

		( new WC_Admin_Notes_Coupon_Page_Moved() )->init();

		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_add_marketing_coupon_script' ) );
		add_action( 'woocommerce_register_post_type_shop_coupon', array( $this, 'move_coupons' ) );
		add_action( 'admin_head', array( $this, 'fix_coupon_menu_highlight' ), 99 );
		add_filter( 'custom_menu_order', array( $this, 'reorder_coupon_menu' ) );
		add_action( 'admin_menu', array( $this, 'maybe_add_coupon_menu_redirect' ) );
	}

	/**
	 * Maybe add menu item back in original spot to help people transition
	 */
	public function maybe_add_coupon_menu_redirect() {

		if ( ! $this->should_display_legacy_menu() ) {
			return;
		}

		add_submenu_page(
			'woocommerce',
			__( 'Coupons', 'woocommerce-admin' ),
			__( 'Coupons', 'woocommerce-admin' ),
			'manage_options',
			'coupons-moved',
			[ $this, 'coupon_menu_moved' ]
		);
	}

	/**
	 * Call back for transition menu item
	 */
	public function coupon_menu_moved() {
		wp_safe_redirect( $this->get_legacy_coupon_url(), 301 );
		exit();
	}

	/**
	 * Modify registered post type shop_coupon
	 *
	 * @param array $args Array of post type parameters.
	 *
	 * @return array the filtered parameters.
	 */
	public function move_coupons( $args ) {
		$args['show_in_menu'] = current_user_can( 'manage_woocommerce' ) ? $this->get_management_url( 'marketing' ) : true;
		return $args;
	}

	/**
	 * Undo WC modifications to $parent_file for 'shop_coupon'
	 */
	public function fix_coupon_menu_highlight() {
		global $parent_file, $post_type;

		if ( 'shop_coupon' === $post_type ) {
			$parent_file = $this->get_management_url( 'marketing' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}

	/**
	 * Reorder marketing submenu items so Overview is at the top
	 *
	 * @param array $menu_order The existing menu order.
	 *
	 * @return array The filtered menu order.
	 */
	public function reorder_coupon_menu( $menu_order ) {
		global $submenu;

		$marketing = $this->get_management_url( 'marketing' );
		$settings  = $submenu[ $marketing ];

		$found_index = false;

		foreach ( $settings as $key => $details ) {
			if ( 'Overview' === $details[0] ) {
				$index       = $key;
				$found_index = true;
				break;
			}
		}

		if ( $found_index ) {
			$temp = [ $index => $settings[ $index ] ];
			unset( $settings[ $index ] );
			$settings              = $temp + $settings;
			$submenu[ $marketing ] = $settings; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}

		return $menu_order;
	}

	/**
	 * Maybe add our wc-admin coupon scripts if viewing coupon pages
	 */
	public function maybe_add_marketing_coupon_script() {

		$rtl = is_rtl() ? '-rtl' : '';

		wp_enqueue_style(
			'wc-admin-marketing-coupons',
			Loader::get_url( "marketing-coupons/style{$rtl}", 'css' ),
			array(),
			Loader::get_file_version( 'css' )
		);

		wp_enqueue_script(
			'wc-admin-marketing-coupons',
			Loader::get_url( 'wp-admin-scripts/marketing-coupons', 'js' ),
			array( 'wp-i18n', 'wp-data', 'wp-element', 'moment', 'wp-api-fetch', WC_ADMIN_APP ),
			Loader::get_file_version( 'js' ),
			true
		);
	}
}
