<?php
/** Réglages partagés des emails du thème ; aucune option WordPress n'est écrite. */
defined( 'ABSPATH' ) || exit;
$colors = array( 'background' => '#f9fafb', 'surface' => '#ffffff', 'accent' => '#2d4f19', 'text' => '#4b5563' );
$options = array( 'background' => 'woocommerce_email_background_color', 'surface' => 'woocommerce_email_body_background_color', 'accent' => 'woocommerce_email_base_color', 'text' => 'woocommerce_email_text_color' );
$preview = apply_filters( 'woocommerce_is_email_preview', false );
foreach ( $options as $key => $option ) {
    $value = get_option( $option );
    if ( $preview ) {
        $pending = get_transient( $option );
        $value = false !== $pending ? $pending : $value;
    }
    $value = is_string( $value ) ? sanitize_hex_color( $value ) : null;
    if ( $value ) {
        $colors[ $key ] = $value;
    }
}
$address = trim( (string) get_option( 'woocommerce_store_address' ) );
if ( $address ) {
    $address = implode( ', ', array_filter( array( $address, get_option( 'woocommerce_store_address_2' ), trim( get_option( 'woocommerce_store_postcode' ) . ' ' . get_option( 'woocommerce_store_city' ) ) ) ) );
} else {
    $address = str_replace( array( "\r\n", "\r", "\n" ), ', ', get_theme_mod( 'thaimoon_address', "8 rue des Chevaliers\n45190 Beaugency" ) );
}
return array(
    'colors' => $colors, 'navbar' => '#142b08', 'footer' => '#111827', 'footer_text' => '#e5e7eb', 'border' => '#e5e7eb', 'soft' => '#f3fce9',
    'address' => $address,
    'phone' => get_theme_mod( 'thaimoon_phone', '09 83 63 09 84' ),
    'days' => get_theme_mod( 'thaimoon_days', 'Lundi – Dimanche' ),
    'hours' => get_theme_mod( 'thaimoon_hours', '12h – 14h30 • 18h – 22h30' ),
    'saturday_hours' => get_theme_mod( 'thaimoon_saturday_hours', '11h – 14h30 • 18h – 22h30' ),
);
