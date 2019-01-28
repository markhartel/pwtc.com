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
 * @category  Templates
 * @copyright Copyright (c) 2017-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Team invitation email
 *
 * @type string $email_heading email heading
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation the invitation instance
 *
 * @version 1.0.0
 * @since 1.0.0
 */

$team = $invitation->get_team();
$plan = $invitation->get_plan();

do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php /* translators: %s - membership plan name */ printf( esc_html__( 'This will give you %s access.', 'woocommerce-memberships-for-teams' ), $plan->get_name() ); ?></p>

<?php if ( $user = $invitation->get_user() ) : ?>

	<p><?php esc_html_e( 'Please click the confirmation link below to sign in and accept your invite.', 'woocommerce-memberships-for-teams' ); ?></p>

	<?php /* the following gettext messages are documented in templates/myaccount/join-team.php */ ?>
	<?php if ( $existing_membership = $team->get_existing_user_membership( $user->ID ) ) : ?>
		<p>
		<?php if ( $current_team = wc_memberships_for_teams()->get_teams_handler_instance()->get_user_membership_team( $existing_membership->get_id() ) ) : ?>
			<?php printf( esc_html__( 'You are a member of %1$s, which already gives you access to %2$s. Joining %3$s means you will leave your current family and your existing membership will be moved under new family management.' ), $current_team->get_name(), $team->get_plan()->get_name(), $team->get_name() ); ?>
		<?php else : ?>
			<?php printf( esc_html__( 'Your existing %s membership will be moved under family management.', 'woocommerce-memberships-for-teams' ), $plan->get_name() ); ?></p>
		<?php endif; ?>
		</p>
	<?php endif; ?>

<?php else : ?>

	<p><?php esc_html_e( 'Please click the confirmation link below to sign up for an account and accept your invite.', 'woocommerce-memberships-for-teams' ); ?></p>

<?php endif; ?>

<p>
	<a class="link" href="<?php echo esc_url( $invitation->get_accept_url() ); ?>">
		<?php /* translators: %s - team name */ printf( esc_html__( 'Click here to join %s', 'woocommerce-memberships-for-teams' ), $team->get_name() ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_email_footer' );
