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

defined( 'ABSPATH' ) or exit;

/**
 * Creates a team programmatically.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     optional - an array of team arguments
 *
 *     @type int $owner_id owner user id
 *     @type int|\WC_Memberships_Plan $plan_id plan id or instance
 *     @type int|\WC_Product $product_id product id or instance
 *     @type int|\WC_Order $order_id order id or instance
 *     @type string $name team name, defaults to 'Team'
 *     @type int $seats the number of seats to add to the team - if not provided, will use the max member count from the product/variation
 * }
 * @param string $action either 'create' or 'renew' -- when in doubt, use 'create'
 * @throws \SV_WC_Plugin_Exception on validation errors or when wp_insert_post fails
 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team team instance
 */
function wc_memberships_for_teams_create_team( $args = array(), $action = 'create' ) {
	return wc_memberships_for_teams()->get_teams_handler_instance()->create_team( $args, $action );
}


/**
 * Returns a team instance.
 *
 * @since 1.0.0
 *
 * @param int|string|\WP_Post $post optional team id, registration key or post object, defaults to current global post object
 * @return false|\SkyVerge\WooCommerce\Memberships\Teams\Team team instance or false if not found
 */
function wc_memberships_for_teams_get_team( $post = null ) {
	return wc_memberships_for_teams()->get_teams_handler_instance()->get_team( $post );
}


/**
 * Returns a list of teams for a user.
 *
 * Can return either a plain list of team objects or an associative array with query results and team objects.
 *
 * @since 1.0.0
 *
 * @param int $user_id optional, defaults to current user
 * @param array $args {
 *     (optional) an array of arguments to pass to \WP_Query - additionally, a few special arguments can be passed:
 *
 *     @type string|array $status team status, defaults to 'any'
 *     @type string|array $role a comma-separated list or array of team member roles, defaults to 'owner, manager' - specifying this will only fetch teams that the user has one of the given roles
 *     @type int $paged the page number for paging the results (corresponds to paged param for get_posts())
 *     @type int $per_page the number of teams to fetch per page (corresponds to the posts_per_page param for get_posts())
 * }
 * @param bool $force_refresh (optional) whether to force reloading the results even if a previous result has been memoized, defaults to false
 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team[]|array|false $teams an array of teams, associative array of query results or false on failure
 */
function wc_memberships_for_teams_get_teams( $user_id = null, $args = array(), $return = null, $force_refresh = false ) {
	return wc_memberships_for_teams()->get_teams_handler_instance()->get_teams( $user_id, $args, $return, $force_refresh );
}


/**
 * Returns team ID for the given user membership, if any.
 *
 * @since 1.0.0
 *
 * @param int $user_membership_id user membership id
 * @return int|null team id or null if no link found
 */
function wc_memberships_for_teams_get_user_membership_team_id( $user_membership_id ) {
	return wc_memberships_for_teams()->get_teams_handler_instance()->get_user_membership_team_id( $user_membership_id );
}


/**
 * Returns the team for the given user membership, if any.
 *
 * @since 1.0.0
 *
 * @param int $user_membership_id user membership id
 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team|false team instance or false if not found
 */
function wc_memberships_for_teams_get_user_membership_team( $user_membership_id ) {
	return wc_memberships_for_teams()->get_teams_handler_instance()->get_user_membership_team( $user_membership_id );
}
