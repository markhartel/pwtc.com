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

namespace SkyVerge\WooCommerce\Memberships\Teams\Integrations;

use SkyVerge\WooCommerce\Memberships\Teams\Product;

defined( 'ABSPATH' ) or exit;

/**
 * Teams Subscriptions integration class.
 *
 * @since 1.0.0
 */
class Subscriptions {



	/**
	 * Sets up the Subscriptions integration class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// admin
		add_filter( 'wc_memberships_for_teams_team_billing_details', array( $this, 'add_subscription_details' ), 10, 2 );
		add_action( 'wc_memberships_for_teams_process_team_meta',    array( $this, 'update_team_subscription' ), 30, 2 );

		add_filter( 'wc_memberships_for_teams_membership_plan_list_team_product',        array( $this, 'remove_team_subscription_products' ), 10, 2 );
		add_filter( 'wc_memberships_for_teams_membership_plan_column_team_product_link', array( $this, 'adjust_subscription_team_product_link' ), 10, 3 );
		add_action( 'wc_memberships_for_teams_membership_plan_team_options',             array( $this, 'output_team_subscription_options' ) );

		// general
		add_action( 'wc_memberships_for_teams_create_team_from_order', array( $this, 'save_subscription_data' ), 10, 2 );
		add_action( 'wc_memberships_for_teams_add_team_member',        array( $this, 'adjust_team_member_user_membership_data' ), 10, 3 );
		add_action( 'woocommerce_checkout_subscription_created',       array( $this, 'update_team_subscription_on_resubscribe' ), 20, 2 );
		add_action( 'woocommerce_subscription_item_switched',          array( $this, 'update_team_subscription_on_switch' ), 10, 4 );

		add_filter( 'wc_memberships_for_teams_team_management_status', array( $this, 'adjust_team_management_status' ), 10, 2 );

		// frontend
		add_filter( 'woocommerce_order_again_cart_item_data', array( $this, 'remove_raw_cart_item_team_data'), 20, 2 );

		add_filter( 'wc_memberships_for_teams_teams_area_teams_actions',    array( $this, 'add_billing_action' ), 10, 2 );
		add_filter( 'wc_memberships_for_teams_teams_area_settings_actions', array( $this, 'add_billing_action' ), 10, 2 );

		add_filter( 'wc_memberships_for_teams_my_teams_column_names',      array( $this, 'add_next_bill_column' ) );
		add_filter( 'wc_memberships_for_teams_teams_area_my_team_details', array( $this, 'add_team_subscription_details' ), 10, 2 );

		add_action( 'wc_memberships_for_teams_my_teams_column_team-next-bill-on', array( $this, 'output_next_bill_date' ) );

		add_action( 'woocommerce_memberships_for_teams_join_team_form', array( $this, 'output_subscription_notice_and_options' ) );
		add_action( 'woocommerce_memberships_for_teams_joined_team', array( $this, 'maybe_cancel_existing_subscription' ), 10, 2 );

		// emails
		add_filter( 'woocommerce_email_enabled_wc_memberships_for_teams_team_membership_ending_soon', array( $this, 'skip_ending_soon_emails' ), 20, 2 );

		// init hooks that need to be executed early
		add_action( 'init', array( $this, 'init' ) );

		// add any admin notices
		add_action( 'admin_notices', array( $this, 'add_admin_notices' ) );
	}


	/**
	 * Adds any admin notices.
	 *
	 * @since 1.0.5-dev.2
	 */
	public function add_admin_notices() {

		$screen = get_current_screen();

		// viewing a Team post type (new, edit, or list table)
		if ( $screen && 'wc_memberships_team' === $screen->post_type ) {

			// viewing the Edit Team screen
			if ( 'post' === $screen->base && 'edit' === \SV_WC_Helper::get_request( 'action' ) ) {

				// sanity check to ensure the object being edited is a valid team
				if ( $team = wc_memberships_for_teams_get_team( \SV_WC_Helper::get_request( 'post' ) ) ) {

					$subscription_id  = get_post_meta( $team->get_id(), '_subscription_id', true );
					$switched_team_id = get_post_meta( $team->get_id(), '_subscription_switched_team_id', true );

					// display a notice if the subscription was switch and this team is no longer linked
					if ( ! $subscription_id && $switched_team_id && $switched_team = wc_memberships_for_teams_get_team( $switched_team_id ) ) {

						$message = sprintf(
							/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
							__( 'Heads up! The owner of this team switched their subscription and it\'s now linked to a new team. %1$sClick here to edit the new team &raquo;%2$s', 'woocommerce-memberships-for-teams' ),
							'<a href="' . esc_url( $switched_team->get_edit_url() ) . '">', '</a>'
						);

						wc_memberships_for_teams()->get_admin_notice_handler()->add_admin_notice( $message, "switched_team_{$switched_team_id}", array(
							'notice_class' => 'notice-warning',
						) );
					}
				}
			}
		}
	}


	/**
	 * Initializes early hooks.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_filter( 'wc_memberships_membership_plan', array( $this, 'get_membership_plan' ), 2, 3 );
	}


	/**
	 * Filters a Membership Plan to return a subscription-tied Membership Plan.
	 *
	 * This method is a filter callback and should not be used directly.
	 * @see \wc_memberships_get_membership_plan() instead.
	 *
	 * TODO: Note that this method currently relies on the team object being passed to wc_memberships_get_membership_plan()
	 * as the 2nd argument instead of the user membership. It works for now, but is somewhat hacky and should be refactored
	 * later, perhaps by changing the 2nd arg in that method to a generic $context array which addons could adjust, ie
	 * `wc_memberships_get_membership_plan( $plan_id, array( 'team' => $team ) )`. {IT 2017-11-13}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Membership_Plan $membership_plan the membership plan
	 * @param null|\WP_Post $membership_plan_post the membership plan post object
	 * @param null|\SkyVerge\WooCommerce\Memberships\Teams\Team $team the team object
	 * @return \WC_Memberships_Integration_Subscriptions_Membership_Plan|\WC_Memberships_Membership_Plan
	 */
	public function get_membership_plan( $membership_plan, $membership_plan_post = null, $team = null ) {

		// We can't filter directly $membership_plan:
		// it may have both regular products and subscription products that grant access;
		// instead, the team will tell the type of purchase.
		return $this->has_subscription_created_team( $team ) ? new \WC_Memberships_Integration_Subscriptions_Membership_Plan( $membership_plan->post ) : $membership_plan;
	}


	/**
	 * Checks if the product that created the team has a Subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team team instance
	 * @return bool
	 */
	public function has_subscription_created_team( $team ) {

		$is_subscription_tied = false;

		if ( $team instanceof \SkyVerge\WooCommerce\Memberships\Teams\Team ) {

			if ( $subscription_id = $this->get_team_subscription_id( $team ) ) {

				$is_subscription_tied = ! empty( $subscription_id ) && wcs_get_subscription( $subscription_id );

			} elseif ( $product = $team->get_product() ) {

				$is_subscription_tied = $this->is_subscription_product( $product );
			}
		}

		return $is_subscription_tied;
	}


	/**
	 * Adds subscription details to the edit team screen billing details section.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields an associative array of billing detail fields, in format label => field html
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return array
	 */
	public function add_subscription_details( $fields, $team ) {

		if ( ! $team instanceof \SkyVerge\WooCommerce\Memberships\Teams\Team ) {
			return $fields;
		}

		$next_payment = '';
		$subscription = $this->get_team_subscription( $team );

		if ( $subscription ) {
			$next_payment = $subscription->get_time( 'next_payment' );
		}

		$edit_subscription_input = $this->get_edit_subscription_input( $team, $subscription );

		$fields[ __( 'Subscription:', 'woocommerce-memberships-for-teams' ) ] = $edit_subscription_input;
		$fields[ __( 'Next Bill On:', 'woocommerce-memberships-for-teams' ) ] = $next_payment ? date_i18n( wc_date_format(), $next_payment ) : esc_html__( 'N/A', 'woocommerce-memberships-for-teams' );

		$core_integration = $this->get_core_integration();

		// maybe replace the expiration date input
		if ( $subscription && $plan = $team->get_plan() ) {

			if ( $plan->is_access_length_type( 'subscription' ) && $core_integration->get_plans_instance()->grant_access_while_subscription_active( $plan->get_id() ) ) {

				$subscription_expires = $subscription instanceof \WC_Subscription ? $subscription->get_date_to_display( 'end' ) : '';

				wc_enqueue_js( '
					$( "#team-membership-end-date-section" ).find( ".wc-memberships-for-teams-date-input" ).hide();
					$( "#team-membership-end-date-section" ).append( "<span>' . esc_html( $subscription_expires ) . '</span>" );
				' );
			}
		}

		return $fields;
	}


	/**
	 * Returns the edit subscription input HTML.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @param \WC_Subscription|null $subscription the subscription object
	 * @return string HTML
	 */
	private function get_edit_subscription_input( $team, $subscription = null ) {

		if ( $subscription && $subscription instanceof \WC_Subscription ) {
			$subscription_id   = \SV_WC_Order_Compatibility::get_prop( $subscription, 'id' );
			$subscription_url  = get_edit_post_link( $subscription_id );
			$subscription_link = '<a href="' . esc_url( $subscription_url ) . '">' . esc_html( $subscription_id ) . '</a>';
			$selected          = array( $subscription_id => $this->get_core_integration()->get_formatted_subscription_id_holder_name( $subscription ) );
		} else {
			$selected        = array();
			$subscription_id = '';
			$subscription_link = esc_html__( 'Team not linked to a Subscription', 'woocommerce-memberships-for-teams' );
		}

		/* translators: Placeholders: %1$s - link to a Subscription, %2$s - opening <a> HTML tag, %3%s - closing </a> HTML tag */
		$input = sprintf( __( '%1$s - %2$sEdit Link%3$s', 'woocommerce-memberships-for-teams' ),
			$subscription_link,
			'<a href="#" class="js-edit-subscription-link-toggle">',
			'</a>'
		);

		ob_start();

		?><br>
		<span class="wc-memberships-edit-subscription-link-field">

			<?php if ( \SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) : ?>

				<select
					class="sv-wc-enhanced-search"
					id="_subscription_id"
					name="_subscription_id"
					data-action="wc_memberships_edit_membership_subscription_link"
					data-nonce="<?php echo wp_create_nonce( 'edit-membership-subscription-link' ); ?>"
					data-placeholder="<?php esc_attr_e( 'Link to a Subscription or keep empty to leave unlinked', 'woocommerce-memberships-for-teams' ); ?>"
					data-allow_clear="true">
					<?php if ( $subscription instanceof \WC_Subscription ) : ?>
						<option value="<?php echo $subscription_id; ?>"><?php echo $subscription_id; ?></option>
					<?php endif; ?>
				</select>

			<?php else : ?>

				<input
					type="hidden"
					class="sv-wc-enhanced-search"
					id="_subscription_id"
					name="_subscription_id"
					data-action="wc_memberships_edit_membership_subscription_link"
					data-nonce="<?php echo wp_create_nonce( 'edit-membership-subscription-link' ); ?>"
					data-placeholder="<?php esc_attr_e( 'Link to a Subscription or keep empty to leave unlinked', 'woocommerce-memberships-for-teams' ); ?>"
					data-allow_clear="true"
					data-selected="<?php echo esc_html( current( $selected ) ); ?>"
					value="<?php echo esc_attr( $subscription_id ); ?>"
				/>

			<?php endif; ?>

		</span>
		<?php

		\SV_WC_Helper::render_select2_ajax();

		$input .= ob_get_clean();

		// toggle editing of subscription id link
		wc_enqueue_js( '
			$( ".js-edit-subscription-link-toggle" ).on( "click", function( e ) { e.preventDefault(); $( ".wc-memberships-edit-subscription-link-field" ).toggle(); } ).click();
		' );

		return $input;
	}


	/**
	 * Checks whether a team is on a subscription or not.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team team instance or id
	 * @return bool
	 */
	public function has_team_subscription( $team ) {
		return (bool) $this->get_team_subscription( $team );
	}


	/**
	 * Returns a Subscription for a team.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team team instance or id
	 * @return null|\WC_Subscription The Subscription object, null if not found
	 */
	public function get_team_subscription( $team ) {
		$subscription_id = $this->get_team_subscription_id( $team );

		return ! $subscription_id ? null : wcs_get_subscription( $subscription_id );
	}


	/**
	 * Returns a subscription ID for a team.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team team instance or id
	 * @return string|false
	 */
	public function get_team_subscription_id( $team ) {

		$team_id = is_object( $team ) ? $team->get_id() : $team;

		return get_post_meta( $team_id, '_subscription_id', true );
	}


	/**
	 * Updates the team subscription ID.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id post identifier
	 * @param \WP_Post $post the post object
	 */
	public function update_team_subscription( $post_id, \WP_Post $post ) {

		if ( $team = wc_memberships_for_teams_get_team( $post->ID ) ) {

			$new_subscription_id = ! empty( $_POST['_subscription_id'] ) ? (int) $_POST['_subscription_id'] : null;
			$old_subscription    = $this->get_team_subscription( $team );

			// always update the meta first in case the below membership looping fails
			update_post_meta( $post_id, '_subscription_id', $new_subscription_id );

			// if an ID is set, update the memberships with a new subscription link
			if ( $new_subscription_id && $new_subscription = wcs_get_subscription( $new_subscription_id ) ) {

				$this->update_team_user_memberships_subscription( $team, $new_subscription );

			// otherwise, remove the link from the memberships
			} elseif ( $old_subscription ) {

				$this->remove_team_user_memberships_subscription( $team, $old_subscription );
			}
		}
	}


	/**
	 * Adds a subscription attribute to the subscription team product listed among the access granting products.
	 *
	 * @internal
	 *
	 * @since 1.0.4
	 *
	 * @param string $html link HTML with additional information
	 * @param string $link link HTML (just the link)
	 * @param \WC_Product $product product object
	 * @return string HTML
	 */
	public function adjust_subscription_team_product_link( $html, $link, $product ) {

		if ( $this->is_subscription_product( $product ) ) {

			$attributes = array_map( 'strtolower', array(
				'(' . __( 'Subscription', 'woocommerce-memberships-for-teams' ) . ')',
				'(' . __( 'Team', 'woocommerce-memberships-for-teams' ) . ')',
			) );

			$html = sprintf( '<li>%1$s%2$s</li>', $link, ' <small>' . implode( ' ', $attributes ) . '</small>' );
		}

		return $html;
	}


	/**
	 * Toggles whether a product should be listed among the team products of a plan.
	 *
	 * Excludes subscription products, so these can be added separately in another list.
	 *
	 * @since 1.0.4
	 *
	 * @param bool $list_product whether to list the product among the team products of a membership plan
	 * @param \WC_Product $product a product that could be a subscription product
	 * @return bool
	 */
	public function remove_team_subscription_products( $list_product, $product ) {

		if ( $this->is_subscription_product( $product ) ) {
			$list_product = false;
		}

		return $list_product;
	}


	/**
	 * Outputs team membership subscription options.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function output_team_subscription_options() {
		global $post;

		$products = wc_memberships_for_teams()->get_membership_plans_instance()->get_membership_plan_team_products( $post->ID );

		if ( ! empty( $products ) ) :

			$items = array();

			foreach ( $products as $product ) :

				if ( $this->is_subscription_product( $product ) ) :

					$list_subscription_product = (bool) apply_filters( 'wc_memberships_for_teams_membership_plan_list_team_subscription_product', true, $product, $post->ID );

					if ( $list_subscription_product ) :

						$product_name = sprintf( '%1$s (#%2$s)', \SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? $product->get_name() : $product->get_title(), $product->get_id() );

						$items[] = '<a href="' . get_edit_post_link( $product->get_id() ) . '">' . $product_name . '</a>';

					endif;

				endif;

			endforeach;

			?>

			<?php if ( ! empty( $items ) ) : ?>

				<?php $product_links = wc_memberships_list_items( $items, __( 'and', 'woocommerce-memberships-for-teams' ) ); ?>

				<p class="form-field plan-team-subscriptions-field">
					<label><?php esc_html_e( 'Team subscriptions', 'woocommerce-memberships-for-teams' ); ?></label>
					<span class="team-subscriptions"><?php echo $product_links; ?></span>
				</p>

				<?php /* force display subscription length options */ ?>
				<style type="text/css">
					#membership-plan-data-general .plan-subscription-access-length-field { display: block !important }
				</style>

			<?php endif; ?>

			<?php

		endif;
	}


	/**
	 * Adjusts the team management status.
	 *
	 * Prevents managing the team if the related subscription is cancelled, expired or trashed.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array an associative array with 2 keys: `can_be_managed` and `decline_reason`
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the related team
	 * @return array
	 */
	public function adjust_team_management_status( $status, $team ) {

		if ( $status['can_be_managed'] && $subscription = $this->get_team_subscription( $team ) ) {

			$integration         = $this->get_core_integration();
			$subscription_status = $integration->get_subscription_status( $subscription );

			if ( in_array( $subscription_status, array( 'expired', 'trash', 'cancelled' ), true ) ) {
				$status['can_be_managed'] = false;
				$status['message']        = array(
					'general'       => __( 'Team subscription has been cancelled or expired.', 'woocommerce-memberships-for-teams' ),
					'add_member'    => __( "Can't add more members because your team subscription has been cancelled or expired.", 'woocommerce-memberships-for-teams' ),
					'remove_member' => __( "Can't remove members because your team subscription has been cancelled or expired.", 'woocommerce-memberships-for-teams' ),
					'join_team'     => __( "Can't join team at the moment - please contact your team owner for more details.", 'woocommerce-memberships-for-teams' ),
				);
			}
		}

		return $status;
	}


	/**
	 * Saves related subscription data when a team is created via a purchase.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @param array $args
	 */
	public function save_subscription_data( $team, $args ) {

		$product = wc_get_product( $args['product_id'] );

		// handle access from Subscriptions
		if ( $product && $this->is_subscription_product( $product ) ) {

			$subscription = wc_memberships_get_order_subscription( $args['order_id'], $product->get_id() );

			if ( $subscription ) {

				$previous_subscription_id = (int) $this->get_team_subscription_id( $team );
				$subscription_id          = (int) \SV_WC_Order_Compatibility::get_prop( $subscription, 'id' );

				update_post_meta( $team->get_id(), '_subscription_id', $subscription_id );

				// store team id on the subscription item
				if ( $team_uid = wc_get_order_item_meta( $args['item_id'], '_wc_memberships_for_teams_team_uid', true ) ) {

					foreach ( $subscription->get_items() as $item ) {

						if ( wc_get_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_uid', true ) === $team_uid ) {
							wc_update_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_id', $team->get_id() );
						}
					}
				}

				// finally, if this was a re-purchase of a cancelled subscription, make sure each user membership is
				// updated with the new subscription id and is re-activated
				if ( $previous_subscription_id && $previous_subscription_id !== $subscription_id ) {

					$this->update_team_user_memberships_subscription( $team, $subscription, $team->get_order(), $product );
				}
			}
		}
	}


	/**
	 * Updates related subscription data on resubscribe.
	 *
	 * A resubscribe order replaces a cancelled subscription with a new one.
	 *
	 * @internal
	 *
	 * @since 1.0.4
	 *
	 * @param \WC_Subscription $new_subscription the new subscription object
	 * @param \WC_Order $resubscribe_order the order that created a new subscription
	 */
	public function update_team_subscription_on_resubscribe( $new_subscription, $resubscribe_order ) {

		$new_order_id        = \SV_WC_Order_Compatibility::get_prop( $resubscribe_order, 'id' );
		$new_subscription_id = \SV_WC_Order_Compatibility::get_prop( $new_subscription, 'id' );
		$old_subscription_id = $new_subscription_id > 0 ? get_post_meta( $new_subscription_id, '_subscription_resubscribe', true ) : 0;
		$old_subscription    = $old_subscription_id > 0 ? wcs_get_subscription( $old_subscription_id ) : null;

		if ( $old_subscription && in_array( $old_subscription->get_status(), array( 'cancelled', 'pending-cancel' ), false ) ) {

			$existing_teams = $this->get_teams_from_subscription( $old_subscription_id );

			if ( ! empty( $existing_teams ) ) {

				foreach ( $existing_teams as $existing_team ) {

					// update the team's subscription link and the order link
					update_post_meta( $existing_team->get_id(), '_subscription_id', $new_subscription_id );
					update_post_meta( $existing_team->get_id(), '_order_id', $new_order_id );

					// also reactivate any cancelled memberships within the team's seats
					$this->update_team_user_memberships_subscription( $existing_team, $new_subscription, $resubscribe_order, $existing_team->get_product() );
				}
			}
		}
	}


	/**
	 * Updates a team's subscription data when the subscription is switched.
	 *
	 * This method updates a switched subscription's new line item with the new
	 * team ID that was generated during the switch, and marks the _old_ team
	 * as having been switched so we can alert the user.
	 *
	 * Also removes the _old_ team's link to the subscription.
	 *
	 * TODO: Eventually we want to properly support subscription switching, and
	 *       this handling shouldn't be needed when that happens. For now, this
	 *       helps avoid confusion a bit when a customer switches and a second
	 *       team is created {CW 2018-09-05}
	 *
	 * @internal
	 *
	 * @since 1.0.5
	 *
	 * @param \WC_Order $order order object
	 * @param \WC_Subscription $subscription subscription object
	 * @param int|string $new_line_item_id line item ID for the subscription being switched to
	 * @param int|string $old_line_item_id line item ID for the subscription being switched from
	 */
	public function update_team_subscription_on_switch( $order, $subscription, $new_line_item_id, $old_line_item_id ) {

		$new_team = null;

		// the switched-to subscription line item should have a UID for the new team
		if ( $new_team_uid = wc_get_order_item_meta( $new_line_item_id, '_wc_memberships_for_teams_team_uid', true ) ) {

			foreach ( $order->get_items() as $item ) {

				// find the matching line item on the switch order
				if ( wc_get_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_uid', true ) === $new_team_uid ) {

					// set the new team ID on the subscription item from the matching order item
					if ( $new_team = wc_memberships_for_teams_get_team( wc_get_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_id' ) ) ) {
						wc_update_order_item_meta( $new_line_item_id, '_wc_memberships_for_teams_team_id', $new_team->get_id() );
					}
				}
			}
		}

		$old_team_id = wc_get_order_item_meta( $old_line_item_id, '_wc_memberships_for_teams_team_id' );

		if ( $old_team = wc_memberships_for_teams_get_team( $old_team_id ) ) {

			// unlink the old team from the subscription being switched
			delete_post_meta( $old_team->get_id(), '_subscription_id' );

			// store the new team ID generated from the switch
			if ( $new_team ) {
				update_post_meta( $old_team->get_id(), '_subscription_switched_team_id', $new_team->get_id() );
			}
		}
	}


	/**
	 * Updates the user memberships in a team with a new subscription link.
	 *
	 * @since 1.0.4
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team team object
	 * @param \WC_Subscription $subscription subscription object
	 * @param \WC_Order|null $order order object
	 * @param \WC_Product|null $product subscription product object
	 */
	private function update_team_user_memberships_subscription( $team, $subscription, $order = null, $product = null ) {

		if ( $subscription instanceof \WC_Subscription ) {

			foreach ( $team->get_user_memberships() as $user_membership ) {

				// set the membership's subscription ID
				$subscription_membership = new \WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post );
				$subscription_membership->set_subscription_id( \SV_WC_Order_Compatibility::get_prop( $subscription, 'id' ) );

				$order_id = $order instanceof \WC_Order ? \SV_WC_Order_Compatibility::get_prop( $order, 'id' ) : null;

				// if associated with an order
				if ( $order_id ) {

					$note = '';

					$subscription_membership->set_order_id( $order_id );

					if ( $product ) {

						$subscription_membership->set_product_id( $product->get_id() );

						/* translators: Placeholders: %1$s - subscription product name, %2%s - order number */
						$note = sprintf( __( 'Membership re-activated due to subscription re-purchase (%1$s, Order %2$s).', 'woocommerce-memberships-for-teams' ),
							$product->get_title(),
							'<a href="' . esc_url( admin_url( 'post.php?post=' . $order_id  . '&action=edit' ) ) .'" >' . esc_html( $order_id ) . '</a>'
						);
					}

					if ( $subscription_membership->has_status( array( 'pending', 'cancelled' ) ) ) {

						$subscription_membership->update_status( 'active', $note );
					}
				}
			}
		}
	}


	/**
	 * Removes a subscription link from the user memberships in a team.
	 *
	 * @since 1.0.5
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team team object
	 * @param \WC_Subscription $subscription subscription object
	 */
	private function remove_team_user_memberships_subscription( $team, $subscription ) {

		if ( $subscription instanceof \WC_Subscription && $core_integration = $this->get_core_integration() ) {

			foreach ( $team->get_user_memberships() as $user_membership ) {
				$core_integration->unlink_membership( $user_membership, $subscription );
			}
		}
	}


	/**
	 * Sets the related subscription data when a user membership is created for a team member.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team_Member $team_member the team member instance
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @param \WC_Memberships_User_Membership $user_membership the user membership instance
	 */
	public function adjust_team_member_user_membership_data( $team_member, $team, $user_membership ) {

		$subscription = $this->get_team_subscription( $team_member->get_team_id() );

		if ( $subscription ) {

			$subscription_id = \SV_WC_Order_Compatibility::get_prop( $subscription, 'id' );
			$user_membership = new \WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post );

			$user_membership->set_subscription_id( $subscription_id );
			$user_membership->set_start_date( $team_member->get_team()->get_date() );

			// the following code is copy-paste from WC_Memberships_Integration_Subscriptions_Lifecycle::update_subscription_memberships() and could perhaps be abstracted in core {IT 2017-09-20}
			$integration = $this->get_core_integration();

			// if statuses do not match, update
			if ( ! $integration->has_subscription_same_status( $subscription, $user_membership ) ) {

				$subscription_status = $integration->get_subscription_status( $subscription );

				// special handling for paused memberships which might be put on free trial
				if ( 'active' === $subscription_status && 'paused' === $user_membership->get_status() ) {

					// get trial end timestamp
					$trial_end = $integration->get_subscription_event_time( $subscription, 'trial_end' );

					// if there is no trial end date or the trial end date is past and the Subscription is active, activate the membership...
					if ( ! $trial_end || current_time( 'timestamp', true ) >= $trial_end ) {
						$user_membership->activate_membership( __( 'Membership activated because WooCommerce Subscriptions was activated.', 'woocommerce-memberships-for-teams' ) );
					// ...otherwise, put the membership on free trial
					} else {
						$user_membership->update_status( 'free_trial', __( 'Membership free trial activated because WooCommerce Subscriptions was activated.', 'woocommerce-memberships-for-teams' ) );
						$user_membership->set_free_trial_end_date( date( 'Y-m-d H:i:s', $trial_end ) );
					}

				// all other membership statuses: simply update the status
				} else {

					$integration->update_related_membership_status( $subscription, $user_membership, $subscription_status );
				}
			}

			$plan = $team->get_plan();

			if ( $plan && $plan->is_access_length_type( 'subscription' ) && $integration->get_plans_instance()->grant_access_while_subscription_active( $plan->get_id() ) ) {

				$end_date = $integration->get_subscription_event_date( $subscription, 'end' );

			} else {

				$end_date = $team->get_membership_end_date( 'timestamp' );
			}

			// end date has changed
			if ( strtotime( $end_date ) !== $user_membership->get_end_date( 'timestamp' ) ) {
				$user_membership->set_end_date( $end_date );
			}
		}
	}


	/**
	 * Removes any user-supplied team field data from Subscription item's custom line item meta.
	 *
	 * Teams takes care of copying over the user-input itself, so this avoids the same meta from being
	 * added and displayed twice.
	 *
	 * @internal
	 *
	 * @see \SkyVerge\WooCommerce\Memberships\Teams\Cart::add_order_again_cart_item_team_data()
	 *
	 * @since 1.0.2
	 *
	 * @param array $cart_item_data associative-array of name/value pairs of cart item data
	 * @param \WC_Order_Item_Product $item the order item to order again
	 * @return array associative array of name/value pairs of cart item data to set in the session
	 */
	public function remove_raw_cart_item_team_data( $cart_item_data, $item ) {

		$cart_item_key = isset( $cart_item_data['subscription_resubscribe'] ) ? 'subscription_resubscribe' : 'subscription_renewal';

		if ( ! empty( $cart_item_data[ $cart_item_key ] ) ) {

			$product = $item->get_product();

			// remove any user-input fields, so that they're not being added/displayed twice
			$fields = Product::get_team_user_input_fields( $product );

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $key => $field ) {
					unset( $cart_item_data[ $cart_item_key ]['custom_line_item_meta'][ $key ] );
				}
			}
		}

		return $cart_item_data;
	}


	/**
	 * Adds subscription billing link to team actions in Teams Area.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions list of actions
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return array
	 */
	public function add_billing_action( $actions, $team ) {

		if ( current_user_can( 'wc_memberships_for_teams_manage_team_settings', $team ) && $subscription = $this->get_team_subscription( $team ) ) {

			$actions = array_merge( array( 'billing' => array(
				'url'  => $subscription->get_view_order_url(),
				'name' => __( 'Billing', 'woocommerce-memberships-for-teams' ),
			) ), $actions );

			unset( $actions['renew'], $actions['cancel'] );
		}

		return $actions;
	}


	/**
	 * Adds next bill date row to a subscription-tied team in Team Status table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns list of table columns and their names
	 * @return array
	 */
	public function add_next_bill_column( $columns ) {
		return \SV_WC_Helper::array_insert_after( $columns, 'team-created-date', array( 'team-next-bill-on' => __( 'Next Bill On', 'woocommerce-memberships-for-teams' ) ) );
	}


	/**
	 * Adds next bill date row to a subscription-tied team in Team Status table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $team_details associative array of team details
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return array
	 */
	public function add_team_subscription_details( $team_details, $team ) {

		if ( $subscription = $this->get_team_subscription( $team ) ) {

			$team_details = \SV_WC_Helper::array_insert_after(
				$team_details,
				'created-date',
				array( 'next-bill-date' => array(
					'label'   => __( 'Next Bill On', 'woocommerce-memberships-for-teams' ),
					'content' => $this->get_formatted_next_bill_date( $team ),
					'class'   => 'my-team-detail-team-next-bill-date',
				) )
			);
		}

		return $team_details;
	}


	/**
	 * Outputs the next bill date for a subscription-tied team.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 */
	public function output_next_bill_date( $team ) {
		echo $this->get_formatted_next_bill_date( $team );
	}


	/**
	 * Returns the formatted next bill date for a subscription-tied team.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return string
	 */
	public function get_formatted_next_bill_date( $team ) {

		if ( $subscription = $this->get_team_subscription( $team ) ) {
			$next_payment = $subscription->get_time( 'next_payment', 'site' );
		}

		if ( ! empty( $next_payment ) ) {
			$date = date_i18n( wc_date_format(), $next_payment );
		} else {
			$date = esc_html__( 'N/A', 'woocommerce-memberships-for-teams' );
		}

		return $date;
	}


	/**
	 * Outputs existing subscription cancellation notice and options on join team page.
	 *
	 * @internal
	 *
	 * @since 1.0.2
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 */
	public function output_subscription_notice_and_options( $team ) {

		$user_id      = get_current_user_id();
		$subscription = $user_id ? $this->get_user_existing_subscription( $user_id, $team ) : null;

		// check if the user ID matches to understand if the member owns the subscription too or they won't be able to cancel it: ?>
		<?php if ( $subscription && $user_id === $subscription->get_user_id() && $subscription->has_status( 'active' ) ) : ?>

			<p class="woocommerce-info"><?php printf( esc_html__( 'You have an active subscription (%s) tied to your current membership. Would you like this subscription to be cancelled when joining the team?.' ), '<a href="' . esc_url( $subscription->get_view_order_url() ) . '">' . sprintf( esc_html_x( '#%s', 'hash before order number', 'woocommerce-memberships-for-teams' ), esc_html( $subscription->get_order_number() ) ) . '</a>' ); ?></p>

			<?php woocommerce_form_field( 'cancel_existing_subscription', array(
				'label' => __( 'Cancel my existing subscription', 'woocommerce-memberships-for-teams' ),
				'type'  => 'checkbox'
			) ) ;?>

			<input
				type="hidden"
				name="existing_subscription_id"
				value="<?php echo esc_attr( $subscription->get_id() ); ?>"
			/>

		<?php endif; ?>

		<?php
	}


	/**
	 * Gets user's existing subscription for the given team's membership plan, if any.
	 *
	 * @since 1.0.2
	 *
	 * @param int $user_id the user id to get the subscription for
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return false|null|\WC_Subscription
	 */
	private function get_user_existing_subscription( $user_id, $team ) {

		$existing_user_membership = $team->get_existing_user_membership( $user_id );

		if ( ! $existing_user_membership ) {
			return null;
		}

		$subscription_user_membership = new \WC_Memberships_Integration_Subscriptions_User_Membership( $existing_user_membership->post );

		return $subscription_user_membership->get_subscription();
	}


	/**
	 * Cancels an existing subscription for a membership plan after user joins a team for the same plan.
	 *
	 * @internal
	 *
	 * @since 1.0.2
	 *
	 * @param int $user_id id of the the user that joined the team
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @throws \Exception
	 */
	public function maybe_cancel_existing_subscription( $user_id, $team) {

		if ( $user_id && ! empty( $_POST['cancel_existing_subscription'] ) && ! empty( $_POST['existing_subscription_id'] ) ) {

			$subscription_id = (int) $_POST['existing_subscription_id'];
			$subscription    = wcs_get_subscription( $subscription_id );

			if ( $subscription && $user_id === $subscription->get_user_id() ) {

				/* translators: Placeholders: %s - team name */
				$subscription->update_status( 'cancelled', sprintf( esc_html__( 'Subscription cancelled because user joined team (%s).', 'woocommerce-memberships-for-teams' ), $team->get_name()) );

				$message = sprintf( esc_html__( 'Your existing subscription (%s) has been cancelled.' ), '<a href="' . esc_url( $subscription->get_view_order_url() ) . '">' . sprintf( esc_html_x( '#%s', 'hash before order number', 'woocommerce-subscriptions' ), esc_html( $subscription->get_order_number() ) ) . '</a>' );

				wc_add_notice( $message, 'notice' );
			}
		}
	}


	/**
	 * Disables Membership Ending Soon emails for teams tied to a subscription.
	 *
	 * Currently, a subscription cannot be renewed before its expiration date.
	 *
	 * TODO however this could change in the future if Subscriptions introduces early renewals {FN 2017-04-04}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_enabled whether the email is enabled in the first place
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance which could be tied to a subscription
	 * @return bool
	 */
	public function skip_ending_soon_emails( $is_enabled, $team ) {

		if ( $is_enabled ) {

			if ( is_numeric( $team ) ) {
				$team = wc_memberships_for_teams_get_team( $team );
			}

			// if it's linked to a subscription, skip
			if ( $team && $subscription_id = $this->get_team_subscription_id( $team ) ) {
				$is_enabled = false;
			}
		}

		return $is_enabled;
	}


	/**
	 * Returns Teams from a Subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Subscription $subscription Subscription post object or ID
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team[] array of team objects or empty array, if none found
	 */
	public function get_teams_from_subscription( $subscription ) {

		$teams = array();

		if ( is_numeric( $subscription ) ) {
			$subscription_id = (int) $subscription;
		} elseif ( is_object( $subscription ) ) {
			$subscription_id = (int) \SV_WC_Order_Compatibility::get_prop( $subscription, 'id' );
		}

		if ( ! empty( $subscription_id ) ) {

			$team_posts = get_posts( array(
				'post_type'        => 'wc_memberships_team',
				'post_status'      => 'any',
				'nopaging'         => true,
				'suppress_filters' => 1,
				'meta_query'       => array(
					array(
						'key'   => '_subscription_id',
						'value' => $subscription_id,
						'type' => 'numeric',
					),
				) )
			);

			foreach ( $team_posts as $team_post ) {

				$team = wc_memberships_for_teams_get_team( $team_post );

				if ( $team ) {
					$teams[ $team->get_id() ] = $team;
				}
			}
		}

		return $teams;
	}


	/**
	 * Checks if a product is a subscription product or not
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product instance
	 * @return bool
	 */
	public function is_subscription_product( $product ) {

		$is_subscription = false;

		// by using Subscriptions method we can account for custom subscription product types
		if ( is_callable( '\WC_Subscriptions_Product::is_subscription' ) ) {
			$is_subscription = \WC_Subscriptions_Product::is_subscription( $product );
		}

		return $is_subscription || $product->is_type( array( 'subscription', 'variable-subscription', 'subscription_variation' ) );
	}


	/**
	 * Returns the core Subscriptions integration class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_Integration_Subscriptions instance
	 */
	private function get_core_integration() {
		return wc_memberships()->get_integrations_instance()->get_subscriptions_instance();
	}


	/**
	 * Separates regular team products from subscription-based team products in edit plan screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @deprecated since 1.0.4
	 *
	 * TODO remove this method by version 1.3.0 {FN 2018-06-28}
	 *
	 * @param \WC_Product[] $products array of team products
	 * @param int $plan_id membership plan id
	 * @return \WC_Product[]
	 */
	public function adjust_membership_plan_team_products( $products, $plan_id ) {
		_deprecated_function( 'SkyVerge\WooCommerce\Memberships\Teams\Integrations\Subscriptions::adjust_membership_plan_team_products()', '1.10.4' );
		return $products;
	}


}
