<?php
/** @version 9.8.0 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	echo esc_html__( 'We’ve received your order and it’s currently on hold until we can confirm your payment has been processed.', 'woocommerce' ) . "\n\n";
	echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";
} else {
	echo esc_html__( 'Thanks for your order. It’s on-hold until we confirm that payment has been received.', 'woocommerce' ) . "\n\n";
}

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

wc_get_template( 'emails/email-pickup-instructions.php', array( 'order' => $order, 'sent_to_admin' => $sent_to_admin, 'plain_text' => true ) );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
