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
 * Team membership ending soon email
 *
 * @type string $email_heading email heading
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
 *
 * @version 1.0.0
 * @since 1.0.0
 */

$owner = $team->get_owner();
$plan  = $team->get_plan();

$site_title          = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
$membership_end_date = date_i18n( wc_date_format(), $team->get_local_membership_end_date( 'timestamp' ) );

do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php /* translators: %s - email recipient's name */ printf( esc_html__( 'Hey %s', 'woocommerce-memberships-for-teams' ), $owner->display_name ); ?>,</p>

<p><?php /* translators: %1$s - site title, %2$s - a date */ printf( esc_html__( 'Heads up: your family membership access at %1$s is ending soon! Your membership access will stop on %2$s.', 'woocommerce-memberships-for-teams' ), $site_title, $membership_end_date ); ?></p>

<p><?php /* translators: %s - membership plan name */ printf( esc_html__( 'If you would like to continue having access to %s, please renew your membership.', 'woocommerce-memberships-for-teams' ), $plan->get_name() ); ?></p>

<p><a href="<?php echo esc_url( $team->get_renew_membership_url() ); ?>"><?php esc_html_e( 'Click here to log in and renew your family membership now', 'woocommerce-memberships-for-teams' ); ?></a>.</p>

<?php
do_action( 'woocommerce_email_footer' );
