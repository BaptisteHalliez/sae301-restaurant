<?php
/** Frais de préparation (itération 15), réglés dans la page SCF Tarification. */
defined( 'ABSPATH' ) || exit;

/** Lire les deux champs existants ; un montant ou un seuil nul désactive la règle. */
function thaimoon_preparation_fee_settings() {
    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }

    $amount    = get_field( 'montant', 'option', false );
    $threshold = get_field( 'seuil', 'option', false );

    if ( ! is_numeric( $amount ) || ! is_numeric( $threshold ) ) {
        return array();
    }

    $decimals  = wc_get_price_decimals();
    $amount    = round( (float) $amount, $decimals );
    $threshold = round( (float) $threshold, $decimals );

    if ( ! is_finite( $amount ) || ! is_finite( $threshold ) || $amount <= 0 || $threshold <= 0 ) {
        return array();
    }

    return array( 'amount' => $amount, 'threshold' => $threshold );
}

// Ce hook couvre aussi les recalculs AJAX et l’API panier de WooCommerce.
add_action( 'woocommerce_cart_calculate_fees', 'thaimoon_preparation_fee', 20 );
function thaimoon_preparation_fee( $cart ) {
    if ( ( is_admin() && ! wp_doing_ajax() ) || ! $cart instanceof WC_Cart || $cart->is_empty() ) {
        return;
    }

    $settings = thaimoon_preparation_fee_settings();
    if ( empty( $settings ) ) {
        return;
    }

    // Articles après remises, dans le mode d’affichage des taxes du panier.
    // Exclure livraison et frais évite que les frais influencent leur propre seuil.
    $items_total = (float) $cart->get_cart_contents_total();
    if ( $cart->display_prices_including_tax() ) {
        $items_total += (float) $cart->get_cart_contents_tax();
    }
    $items_total = round( $items_total, wc_get_price_decimals() );

    if ( $items_total < $settings['threshold'] ) {
        // WooCommerce réinitialise les frais à chaque calcul et les reporte sur la commande.
        $cart->add_fee( __( 'Frais de préparation', 'thaimoon-restaurant' ), $settings['amount'], false );
    }
}
