<?php
/**
 * Plugin Name: Teams for WooCommerce Memberships
 * Plugin URI: https://woocommerce.com/products/teams-woocommerce-memberships/
 * Description: Expands WooCommerce Memberships to sell memberships to teams, families, companies, or other groups!
 * Author: SkyVerge
 * Author URI: https://www.woocommerce.com/
 * Version: 1.0.5
 * Text Domain: woocommerce-memberships-for-teams
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2017-2018 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   SkyVerge\WooCommerce\Memberships\Teams
 * @author    SkyVerge
 * @copyright Copyright (c) 2017-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * Woo: 2893267:f0b7ed22ec012e2e159ec30f5af5c1d1
 * WC requires at least: 3.0.4
 * WC tested up to: 3.4.5
 */

defined( 'ABSPATH' ) or exit;

// ensure required functions are loaded
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// queue plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), 'f0b7ed22ec012e2e159ec30f5af5c1d1', '2893267' );

// required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

// ensure WooCommerce and Memberships are active
if ( ! SV_WC_Framework_Bootstrap::is_woocommerce_active() || ! get_option( 'wc_memberships_is_active', false ) ) {
	return;
}

/**
 * The plugin loader class.
 *
 * @since 1.0.0
 */
class WC_Memberships_For_Teams_Loader {


	/** minimum PHP version required by this plugin */
	const MIN_PHP_VERSION = '5.3.0';

	/** minimum Memberships version required by this plugin */
	const MIN_MEMBERSHIPS_VERSION = '1.9.4';

	/** plugin namespace */
	const PLUGIN_NAMESPACE = 'SkyVerge\WooCommerce\Memberships\Teams';

	/** @var WC_Memberships_For_Teams_Loader single instance of this plugin */
	protected static $instance;

	/** @var array the admin notices to add */
	public $notices = array();


	/**
	 * Initializes the loader.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {

		add_action( 'admin_init', array( $this, 'check_environment' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );

		// if the environment check fails, don't initialize the plugin
		if ( self::get_environment_warning() ) {
			return;
		}

		$this->init_plugin();
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot clone instances of %s.', get_class( $this ) ), '1.0.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot unserialize instances of %s.', get_class( $this ) ), '1.0.0' );
	}


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init_plugin() {

		// autoload plugin and vendor files
		$loader = require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

		// register plugin namespace with autoloader
		$loader->addPsr4( self::PLUGIN_NAMESPACE . '\\', __DIR__ . '/src' );

		require_once( plugin_dir_path( __FILE__ ) . 'src/Functions.php' );

		SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.9.0', __( 'Teams for WooCommerce Memberships', 'woocommerce-memberships-for-teams' ), __FILE__, 'wc_memberships_for_teams', array(
			'minimum_wc_version'   => '3.0.4',
			'minimum_wp_version'   => '4.6',
			'backwards_compatible' => '4.4.0',
		) );
	}


	/**
	 * Adds an admin notice to be displayed.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_notice( $slug, $class, $message ) {

		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message
		);
	}


	/**
	 * Checks the server environment and other factors and deactivates plugins as necessary.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 *
	 * @since 1.0.0
	 */
	public static function activation_check() {

		$environment_warning = self::get_environment_warning( true );

		if ( $environment_warning ) {

			deactivate_plugins( plugin_basename( __FILE__ ) );

			wp_die( $environment_warning );
		}
	}


	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 *
	 * @since 1.0.0
	 */
	public function check_environment() {

		$environment_warning = self::get_environment_warning();

		if ( $environment_warning && is_plugin_active( plugin_basename( __FILE__ ) ) ) {

			deactivate_plugins( plugin_basename( __FILE__ ) );

			$this->add_admin_notice( 'bad_environment', 'error', $environment_warning );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}


	/**
	 * Checks the environment for compatibility problems.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $during_activation whether this check is during plugin activation
	 * @return string|bool the error message if one exists, or false if everything's okay
	 */
	public static function get_environment_warning( $during_activation = false ) {

		$prefix  = $during_activation ? 'The plugin could not be activated' : 'Teams for WooCommerce Memberships has been deactivated';
		$message = false;

		// check the PHP version
		if ( version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '<' ) ) {
			$message = sprintf( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', self::MIN_PHP_VERSION, PHP_VERSION );
		}

		// unfortunately we can't do wc_memberships()->get_version() here, as it's too early at this point
		$memberships_version = get_option( 'wc_memberships_version' );

		// check Memberships version
		if ( ! $message && version_compare( $memberships_version, self::MIN_MEMBERSHIPS_VERSION, '<' ) ) {
			$message = sprintf( 'The minimum WooCommerce Memberships version required for this plugin is %1$s. You are running %2$s.', $prefix, self::MIN_MEMBERSHIPS_VERSION, $memberships_version );
		}

		if ( $message ) {
			$message = sprintf( '%s. %s', $prefix, $message );
		}

		return $message;
	}


	/**
	 * Displays any admin notices added with \WC_Memberships_For_Teams_Loader::add_admin_notice()
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {

		foreach ( (array) $this->notices as $notice_key => $notice ) {

			echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
			echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
			echo "</p></div>";
		}
	}


	/**
	 * Returns the main \WC_Memberships_For_Teams_Loader, ensures only one instance is/can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_For_Teams_Loader
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


}

// fire it up!
WC_Memberships_For_Teams_Loader::instance();

register_activation_hook( __FILE__, array( 'WC_Memberships_For_Teams_Loader', 'activation_check' ) );
