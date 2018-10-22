<?php
/**
 * Teams for WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Teams for WooCommerce Memberships to newer
 * versions in the future. If you wish to customize Teams for WooCommerce Memberships for your
 * needs please refer to https://docs.woocommerce.com/document/teams-woocommerce-memberships/ for more information.
 *
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2017-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Memberships\Teams;

defined( 'ABSPATH' ) or exit;

/**
 * Teams for WooCommerce Memberships Main Plugin Class
 *
 * @since 1.0.0
 */
class Plugin extends \SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.0.5-dev.2';

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Plugin single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'memberships-for-teams';

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Admin instance */
	protected $admin;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\AJAX instance */
	protected $ajax;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Cart instance */
	protected $cart;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Frontend instance */
	protected $frontend;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Capabilities instance */
	protected $capabilities;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Teams_Handler instance */
	protected $teams_handler;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Orders instance */
	protected $orders;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Emails instance */
	protected $emails;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Team_Members instance */
	protected $team_members;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Invitations instance */
	protected $invitations;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Membership_Plans instance */
	protected $membership_plans;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Integrations instance */
	protected $integrations;


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain'        => 'woocommerce-memberships-for-teams',
				'display_php_notice' => true,
				'dependencies'       => array(
					'mbstring',
				),
			)
		);

		// include required files
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ) );

		// initialize
		add_action( 'init', array( $this, 'init' ) );

		// lifecycle
		add_action( 'admin_init', array ( $this, 'maybe_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// make sure template files are searched for in our plugin
		add_filter( 'woocommerce_locate_template',      array( $this, 'locate_template' ), 20, 3 );
		add_filter( 'woocommerce_locate_core_template', array( $this, 'locate_template' ), 20, 3 );

		// add query vars for rewrite endpoints
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
	}


	/**
	 * Includes required files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		$this->capabilities     = new Capabilities;
		$this->teams_handler    = new Teams_Handler;
		$this->cart             = new Cart;
		$this->orders           = new Orders;
		$this->emails           = new Emails;
		$this->invitations      = new Invitations;
		$this->team_members     = new Team_Members;
		$this->membership_plans = new Membership_Plans;
		$this->integrations     = new Integrations;

		// frontend includes
		if ( ! is_admin() ) {
			$this->frontend_includes();
		}

		// admin includes
		if ( is_admin() ) {
			$this->admin_includes();
		}

		// AJAX includes
		if ( is_ajax() ) {
			$this->ajax_includes();
		}
	}


	/**
	 * Includes required admin classes.
	 *
	 * @since 1.0.0
	 */
	private function admin_includes() {

		$this->admin = new Admin;

		// message handler
		$this->admin->message_handler = $this->get_message_handler();
	}


	/**
	 * Includes required AJAX classes.
	 *
	 * @since 1.0.0
	 */
	private function ajax_includes() {
		$this->ajax = new AJAX;
	}


	/**
	 * Includes required frontend classes.
	 *
	 * @since 1.0.0
	 */
	private function frontend_includes() {

		// load front end
		$this->frontend = new Frontend;
	}


	/**
	 * Returns the Admin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Admin
	 */
	public function get_admin_instance() {
		return $this->admin;
	}


	/**
	 * Returns the Ajax instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\AJAX
	 */
	public function get_ajax_instance() {
		return $this->ajax;
	}


	/**
	 * Returns Cart instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Cart
	 */
	public function get_cart_instance() {
		return $this->cart;
	}


	/**
	 * Returns the Frontend instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Frontend
	 */
	public function get_frontend_instance() {
		return $this->frontend;
	}


	/**
	 * Returns the Teams_Handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Teams_Handler
	 */
	public function get_teams_handler_instance() {
		return $this->teams_handler;
	}


	/**
	 * Returns the Orders instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Orders
	 */
	public function get_orders_instance() {
		return $this->orders;
	}


	/**
	 * Returns the Emails instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Emails
	 */
	public function get_emails_instance() {
		return $this->emails;
	}


	/**
	 * Returns the Team_Members instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team_Members
	 */
	public function get_team_members_instance() {
		return $this->team_members;
	}


	/**
	 * Returns the Invitations instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Invitations
	 */
	public function get_invitations_instance() {
		return $this->invitations;
	}


	/**
	 * Returns the Membership Plans instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Membership_Plans
	 */
	public function get_membership_plans_instance() {
		return $this->membership_plans;
	}


	/**
	 * Returns the Integrations instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Integrations
	 */
	public function get_integrations_instance() {
		return $this->integrations;
	}


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		Post_Types::initialize();

		$this->add_rewrite_endpoints();
	}


	/**
	 * Locates the WooCommerce template files from our templates directory.
	 *
	 * @internal
	 * @since 1.0.0
	 *
	 * @param string $template Already found template
	 * @param string $template_name Searchable template name
	 * @param string $template_path Template path
	 * @return string Search result for the template
	 */
	public function locate_template( $template, $template_name, $template_path ) {

		// only keep looking if no custom theme template was found
		// or if a default WooCommerce template was found
		if ( ! $template || \SV_WC_Helper::str_starts_with( $template, WC()->plugin_path() ) ) {

			// set the path to our templates directory
			$plugin_path = $this->get_plugin_path() . '/templates/';

			// if a template is found, make it so
			if ( is_readable( $plugin_path . $template_name ) ) {
				$template = $plugin_path . $template_name;
			}
		}

		return $template;
	}


	/**
	 * Adds rewrite rules endpoints.
	 *
	 * TODO when WC 3.3+ is the minimum required version check if we still need this as WC 3.3 adds endpoints dynamically {IT 2018-05-09}
	 * @see \WC_Query::get_query_vars()
	 * @see \WC_Query::add_endpoints()
	 *
	 * @since 1.0.0
	 */
	private function add_rewrite_endpoints() {

		// add Teams Area endpoint
		add_rewrite_endpoint( get_option( 'woocommerce_myaccount_teams_area_endpoint', 'teams' ), EP_ROOT | EP_PAGES );

		// add join team endpoint
		add_rewrite_endpoint( get_option( 'woocommerce_myaccount_join_team_endpoint', 'join-team' ), EP_ROOT | EP_PAGES );
	}


	/**
	 * Handles query vars for endpoints.
	 *
	 * TODO when WC 3.3+ is the minimum required version check if we still need this as WC 3.3 adds endpoints dynamically {IT 2018-05-09}
	 * @see \WC_Query::get_query_vars()
	 * @see \WC_Query::add_endpoints()
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_vars associative array
	 * @return array
	 */
	public function add_query_vars( $query_vars ) {

		$query_vars[] = get_option( 'woocommerce_myaccount_teams_area_endpoint', 'teams' );
		$query_vars[] = get_option( 'woocommerce_myaccount_join_team_endpoint', 'join-team' );

		return $query_vars;
	}

	/** Admin methods ******************************************************/


	/**
	 * Retrurns the plugin configuration URL.
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::get_settings_url()
	 *
	 * @param string $plugin_id the plugin identifier
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {
		return admin_url( 'admin.php?page=wc-settings&tab=memberships&section=teams' );
	}


	/**
	 * Checks whether currently on the Teams settings screen.
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::is_plugin_settings()
	 *
	 * @return boolean true if on the admin settings page
	 */
	public function is_plugin_settings() {

		return isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] && isset( $_GET['tab'] ) && isset( $_GET['section'] )
		       // main memberships settings page
		       && ( 'memberships' === $_GET['tab']
		       // the teams settings section
		       && ( 'teams' === $_GET['section'] ) );
	}


	/**
	 * Renders a notice for the user to read the docs before adding add-ons.
	 *
	 * @since 1.0.0
	 * @see \SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		$screen = get_current_screen();

		// only render on plugins or settings screen
		if ( 'plugins' === $screen->id || $this->is_plugin_settings() ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				/* translators: the %s placeholders are meant for pairs of opening <a> and closing </a> link tags */
				sprintf( __( 'Thanks for installing Memberships for Teams! To get started, take a minute to %1$sread the documentation%2$s and then %3$ssetup a membership plan%4$s :)', 'woocommerce-memberships-for-teams' ),
					'<a href="https://docs.woocommerce.com/document/teams-woocommerce-memberships/" target="_blank">',
					'</a>',
					'<a href="' . admin_url( 'edit.php?post_type=wc_membership_plan' ) . '">',
					'</a>' ),
				'get-started-notice',
				array( 'always_show_on_settings' => false, 'notice_class' => 'updated' )
			);
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Returns the main Memberships for Teams Instance, ensures only one instance is/can be loaded.
	 *
	 * @since 1.0.0
	 * @see wc_memberships_for_teams()
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Returns the admin message handler instance
	 *
	 * TODO: remove this when the method gets fixed in framework {IT 2017-06-21}
	 *
	 * @since 1.0.0
	 */
	public function get_message_handler() {

		require_once( $this->get_framework_path() . '/class-sv-wp-admin-message-handler.php' );

		return parent::get_message_handler();
	}


	/**
	 * Returns the plugin documentation URL.
	 *
	 * @since 1.2.0
	 * @see \SV_WC_Plugin::get_documentation_url()
	 *
	 * @return string
	 */
	public function get_documentation_url() {
		return 'https://docs.woocommerce.com/document/teams-woocommerce-memberships/';
	}


	/**
	 * Returns the plugin support URL.
	 *
	 * @since 1.2.0
	 * @see \SV_WC_Plugin::get_support_url()
	 *
	 * @return string
	 */
	public function get_support_url() {
		return 'https://woocommerce.com/my-account/tickets/';
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @since 1.0.0
	 * @see \SV_WC_Plugin::get_plugin_name()
	 *
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'Teams for WooCommerce Memberships', 'woocommerce-memberships-for-teams' );
	}


	/**
	 * Returns the full path to the plugin entry script.
	 *
	 * @since 1.0.0
	 * @see \SV_WC_Plugin::get_file()
	 *
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return dirname( __DIR__ ) . "/woocommerce-{$this->get_id()}.php";
	}


	/**
	 * Generates a unique token.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function generate_token() {
		return md5( wp_generate_password() . time() );
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Handles plugin activation.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function maybe_activate() {

		$is_active = get_option( 'wc_memberships_for_teams_is_active', false );

		if ( ! $is_active ) {

			update_option( 'wc_memberships_for_teams_is_active', true );

			/**
			 * Runs when Memberships for Teams is activated.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_memberships_for_teams_activated' );

			$this->add_rewrite_endpoints();
			flush_rewrite_rules();
		}
	}


	/**
	 * Handles plugin deactivation.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		delete_option( 'wc_memberships_for_teams_is_active' );

		/**
		 * Runs when Memberships is deactivated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wc_memberships_for_teams_deactivated' );

		flush_rewrite_rules();
	}


	/**
	 * Runs upgrade scripts.
	 *
	 * @see \SV_WC_Plugin::install()
	 *
	 * @since 1.0.0
	 *
	 * @param string $installed_version semver
	 */
	protected function upgrade( $installed_version ) {

		Upgrade::run_update_scripts( $installed_version );

		$this->add_rewrite_endpoints();
		flush_rewrite_rules();
	}


} // end Teams class
