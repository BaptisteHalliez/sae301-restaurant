<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'thaimoon_coupon_assets', 48 );
function thaimoon_coupon_assets() {
    if ( ! is_cart() && ! is_checkout() ) { return; }
    $path = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    wp_enqueue_script( 'thaimoon-coupons', $uri . '/assets/js/coupons.js', array( 'jquery', is_cart() ? 'wc-cart' : 'wc-checkout' ), (string) filemtime( $path . '/assets/js/coupons.js' ), true );
}
add_action( 'wp', 'thaimoon_coupon_checkout_setup' );
function thaimoon_coupon_checkout_setup() {
    if ( is_checkout() ) { remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 ); }
}
function thaimoon_coupon_front_context() {
    return function_exists( 'WC' ) && WC()->session && WC()->cart && ( is_cart() || is_checkout() || wp_doing_ajax() );
}
function thaimoon_coupon_remember( $state, $code, $message ) {
    if ( ! thaimoon_coupon_front_context() ) { return; }
    WC()->session->set( 'thaimoon_coupon_feedback', array( 'state' => $state, 'code' => wc_format_coupon_code( $code ), 'message' => wp_strip_all_tags( html_entity_decode( $message, ENT_QUOTES, 'UTF-8' ) ), 'time' => time(), 'event' => wp_generate_uuid4() ) );
}
function thaimoon_coupon_feedback() {
    $value = WC()->session ? WC()->session->get( 'thaimoon_coupon_feedback', array() ) : array();
    return is_array( $value ) && isset( $value['time'], $value['state'], $value['code'], $value['message'], $value['event'] ) && (int) $value['time'] > time() - 600 ? $value : array();
}
add_filter( 'woocommerce_coupon_error', 'thaimoon_coupon_error', 20, 3 );
function thaimoon_coupon_error( $message, $error_code, $coupon ) {
    if ( ! thaimoon_coupon_front_context() ) { return $message; }
    $state = 'error';
    if ( WC_Coupon::E_WC_COUPON_EXPIRED === (int) $error_code ) {
        $message = 'Ce code est expiré';
        $state = 'expired';
    } elseif ( WC_Coupon::E_WC_COUPON_NOT_EXIST === (int) $error_code ) {
        $message = 'Votre code est erroné';
    }
    $code = $coupon instanceof WC_Coupon ? $coupon->get_code() : '';
    if ( isset( $_POST['coupon_code'] ) && is_string( $_POST['coupon_code'] ) ) {
        $code = wc_format_coupon_code( wp_unslash( $_POST['coupon_code'] ) );
    }
    thaimoon_coupon_remember( $state, $code, $message );
    return $message;
}
add_action( 'woocommerce_applied_coupon', 'thaimoon_coupon_applied', 30 );
function thaimoon_coupon_applied( $code ) { thaimoon_coupon_remember( 'applied', $code, 'Code activé !' ); }
add_action( 'woocommerce_removed_coupon', 'thaimoon_coupon_removed', 30 );
function thaimoon_coupon_removed( $code ) { thaimoon_coupon_remember( 'removed', $code, 'Votre code promotionnel a été désactivé.' ); }

function thaimoon_coupon_label( $coupon ) {
    return count( WC()->cart->get_applied_coupons() ) > 1 ? 'Code promo (' . $coupon->get_code() . ')' : 'Code promo';
}
add_filter( 'woocommerce_cart_totals_coupon_html', 'thaimoon_coupon_discount_html', 20, 3 );
function thaimoon_coupon_discount_html( $html, $coupon, $discount ) {
    if ( ! thaimoon_coupon_front_context() ) { return $html; }
    $checkout = is_checkout() || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT );
    if ( ! $checkout ) { return $discount; }
    return preg_replace( '/(<a\b[^>]*class="[^"]*\bwoocommerce-remove-coupon\b[^"]*"[^>]*>).*?(<\/a>)/s', '$1Désactiver ?$2', $html );
}
function thaimoon_coupon_panel() {
    if ( wc_coupons_enabled() ) { wc_get_template( 'cart/coupon-panel.php' ); }
}
function thaimoon_coupon_removal_notice() {
    $feedback = thaimoon_coupon_feedback();
    if ( empty( $feedback ) || 'removed' !== $feedback['state'] || in_array( $feedback['code'], WC()->cart->get_applied_coupons(), true ) ) { return; }
    wc_get_template( 'cart/coupon-removed.php', array( 'feedback' => $feedback ) );
}
