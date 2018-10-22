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

namespace SkyVerge\WooCommerce\Memberships\Teams\Admin;

defined( 'ABSPATH' ) or exit;

/**
 * Admin User Memberships class
 *
 * @since 1.0.0
 */
class User_Memberships {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'wc_memberships_after_user_membership_details',   array( $this, 'disable_user_membership_fields' ) );
		add_filter( 'wc_memberships_user_membership_billing_details', array( $this, 'replace_user_membership_billing_details' ), 11, 2 );
		add_filter( 'wc_memberships_user_membership_actions',         array( $this, 'remove_transfer_action' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'maybe_disable_updating_user_membership' ) );

		add_action( 'restrict_manage_posts', array( $this, 'output_user_memberships_team_filters' ), 11 );

		// filter/sort by custom columns
		add_filter( 'request', array( $this, 'request_query' ) );
	}


	/**
	 * Disables editing user membership details for team-based memberships.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_User_Membership $user_membership user membership instance
	 */
	public function disable_user_membership_fields( $user_membership ) {

		$team_id = wc_memberships_for_teams_get_user_membership_team_id( $user_membership->get_id() );

		if ( $team_id ) {

			/** translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
			echo '<p class="form-field"><span class="description">' . sprintf( esc_html__( 'Editing has been disabled because this membership belongs to a team. %1$sEdit team details%2$s instead.', 'woocommerce-memberships-for-teams' ), '<a href="' . get_edit_post_link( $team_id ) . '">', '</a>' ) . '</span></p>';

			// disable all input fields and remove any datepickers
			wc_enqueue_js( "
				var membership_data = jQuery( '#wc-memberships-user-membership-data' );
				membership_data.find( 'input, select, textarea' ).prop( 'disabled', true );
				membership_data.find( '.hasDatepicker' ).datepicker( 'destroy' ).next( '.description' ).remove();

				wc_memberships_admin.i18n.delete_membership_confirm += ' " . esc_html__( 'This will remove the member from the team.' ) . "';
			" );
		}
	}


	/**
	 * Replaces the user membership billing details with team details for team-based memberships.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $billing_fields associative array of labels and data or inputs
	 * @param \WC_Memberships_User_Membership $user_membership user membership instance
	 */
	public function replace_user_membership_billing_details( $billing_fields, $user_membership ) {

		$team_id = wc_memberships_for_teams_get_user_membership_team_id( $user_membership->id );

		if ( $team_id ) {

			$team   = wc_memberships_for_teams_get_team( $team_id );
			$member = wc_memberships_for_teams_get_team_member( $team, $user_membership->get_user() );

			$added_time = $member->get_local_added_date( 'timestamp' );

			if ( ! $added_time ) {
				return __( 'N/A', 'woocommerce-memberships-for-teams' );
			}

			$date_format = wc_date_format();
			$time_format = wc_time_format();

			$date = esc_html( date_i18n( $date_format, (int) $added_time ) );
			$time = esc_html( date_i18n( $time_format, (int) $added_time ) );

			$added = sprintf( '%1$s %2$s', $date, $time );
			$role  = $member->get_role( 'label' );

			$billing_fields = array(
				__( 'Granted from team:', 'woocommerce-memberships-for-teams' ) => '<a href="' . get_edit_post_link( $team_id ) . '">' . esc_html( $team->get_name() ) . '</a>',
				__( 'Member added:', 'woocommerce-memberships-for-teams' )      => esc_html( $added ),
				__( 'Team role:', 'woocommerce-memberships-for-teams' )         => esc_html( $role ),
			);
		}

		return $billing_fields;
	}


	/**
	 * Disables updating user membership for team-based memberships.
	 *
	 * Unhooks the \WC_Memberships_User_Memberships::save_user_membership() method when on a team-based membership screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_updating_user_membership() {
		global $typenow, $pagenow, $wp_filter;

		if ( 'wc_user_membership' !== $typenow || 'post.php' !== $pagenow ) {
			return;
		}

		$post_id = isset( $_REQUEST['post_ID'] ) ? $_REQUEST['post_ID'] : null;

		if ( ! $post_id ) {
			return;
		}

		$team_id = wc_memberships_for_teams_get_user_membership_team_id( $post_id );

		// TODO: when doing code review, consider if this approach is necessary, as it may yield unexpected results with
		// 3rd party plugins/integrations/customizations that expect to customize the membership data even for team-based memberships {IT 2017-08-08}
		if ( $team_id && isset( $wp_filter['save_post'], $wp_filter['save_post']->callbacks[10] ) ) {

			foreach ( $wp_filter['save_post']->callbacks[10] as $key => $hook ) {
				if ( is_array( $hook['function'] ) && $hook['function'][0] instanceof \WC_Memberships_User_Memberships && $hook['function'][1] === 'save_user_membership' ) {
					unset( $wp_filter['save_post']->callbacks[10][ $key ] );
				}
			}
		}
	}


	/**
	 * Removes transfer membership action for team-based memberships.
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_membership_actions membership admin actions
	 * @param int $post_id the post id of the wc_user_membership post
	 */
	public function remove_transfer_action( $actions, $user_membership_id ) {

		$team_id = wc_memberships_for_teams_get_user_membership_team_id( $user_membership_id );

		if ( $team_id && isset( $actions['transfer-action'] ) ) {
			unset( $actions['transfer-action'] );
		}

		return $actions;
	}


	/**
	 * Outputs team filters for the user memberships list table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The post type slug.
	 */
	public function output_user_memberships_team_filters( $post_type ) {

		if ( 'wc_user_membership' === $post_type ) {

			$selected = array();

			if ( ! empty( $_GET['_team_id'] ) ) {

				$team_id = absint( $_GET['_team_id'] );
				$team    = wc_memberships_for_teams_get_team( $team_id );

				/* translators: %1$s - team name, %2$s - team id */
				$team_string = sprintf(
					esc_html__( '%1$s (#%2$s)', 'woocommerce-memberships-for-teams' ),
					$team->get_name(),
					$team_id
				);

				$selected[ $team_id ] = $team_string;
			}
			?>

			<?php if ( \SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) : ?>

				<select
						name="_team_id"
						class="sv-wc-enhanced-search"
						style="min-width: 200px;"
						data-action="wc_memberships_for_teams_json_search_teams"
						data-nonce="<?php echo wp_create_nonce( 'search-teams' ); ?>"
						data-placeholder="<?php esc_attr_e( 'Search for a team&hellip;', 'woocommerce-memberships-for-teams' ); ?>"
						data-allow_clear="true">
					<?php if ( ! empty( $selected ) ) : ?>
						<option value="<?php echo esc_attr( key( $selected ) ); ?>" selected><?php echo esc_html( $team_string ); ?></option>
					<?php endif; ?>
				</select>

			<?php else : ?>

				<input
						type="hidden"
						name="_team_id"
						class="sv-wc-enhanced-search"
						style="min-width: 200px;"
						data-multiple="false"
						data-action="wc_memberships_for_teams_json_search_teams"
						data-nonce="<?php echo wp_create_nonce( 'search-teams' ); ?>"
						data-placeholder="<?php esc_attr_e( 'Search for a team&hellip;', 'woocommerce-memberships-for-teams' ); ?>"
						data-allow_clear="true"
						data-selected="<?php echo esc_attr( current( $selected ) ); ?>"
						value="<?php echo esc_attr( key( $selected ) ); ?>"
				/>

			<?php endif;

			\SV_WC_Helper::render_select2_ajax();
		}
	}


	/**
	 * Handles custom filters and sorting for the user memberships screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $vars query vars for \WP_Query
	 * @return array modified query vars
	 */
	public function request_query( $vars ) {
		global $typenow;

		if ( 'wc_user_membership' === $typenow ) {

			if ( ! empty( $_GET['_team_id'] ) ) {

				$team_id = absint( $_GET['_team_id'] );

				if ( $team_id ) {

					if ( ! isset( $vars['meta_query'] ) ) {
						$vars['meta_query'] = array();
					}

					$vars['meta_query'][] = array(
						'key' => '_team_id',
						'value' => $team_id,
					);
				}
			}
		}

		return $vars;
	}

}
