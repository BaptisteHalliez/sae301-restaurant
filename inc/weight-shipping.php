<?php
/** Livraison à domicile selon le poids, réglée dans SCF Tarification (itération 19). */
defined( 'ABSPATH' ) || exit;

/** Accepter les nombres SCF, y compris une saisie avec virgule décimale. */
function thaimoon_weight_shipping_number( $value ) {
    if ( is_string( $value ) ) {
        $value = str_replace( ',', '.', trim( $value ) );
    }
    return is_numeric( $value ) && is_finite( (float) $value ) ? (float) $value : null;
}

/** Aucun tarif codé en dur : les cinq valeurs proviennent du groupe existant. */
function thaimoon_weight_shipping_settings() {
    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }

    $settings = array();
    foreach ( array( 'seuil_poids_1', 'seuil_poids_2', 'tarif_tranche_1', 'tarif_tranche_2', 'tarif_tranche_3' ) as $name ) {
        $value = thaimoon_weight_shipping_number( get_field( $name, 'option', false ) );
        if ( null === $value || $value < 0 ) {
            return array();
        }
        $settings[ $name ] = round( $value, 0 === strpos( $name, 'seuil_' ) ? 6 : wc_get_price_decimals() );
    }

    if ( $settings['seuil_poids_1'] <= 0 || $settings['seuil_poids_2'] <= $settings['seuil_poids_1'] ) {
        return array();
    }
    return $settings;
}

/** Le produit du panier est la variation choisie ; get_weight() hérite du parent. */
function thaimoon_weight_shipping_package_weight( $package ) {
    $weight = 0.0;
    $has_items = false;
    foreach ( $package['contents'] ?? array() as $item ) {
        $product = $item['data'] ?? null;
        $quantity = thaimoon_weight_shipping_number( $item['quantity'] ?? null );
        if ( ! $product instanceof WC_Product || null === $quantity || $quantity < 0 ) {
            return null;
        }
        if ( 0.0 === $quantity || ! $product->needs_shipping() ) {
            continue;
        }
        $unit_weight = thaimoon_weight_shipping_number( $product->get_weight() );
        if ( null === $unit_weight || $unit_weight < 0 ) {
            // Un poids manquant n'est pas un poids nul : conserver le forfait existant.
            return null;
        }
        $weight += wc_get_weight( $unit_weight, 'kg' ) * $quantity;
        $has_items = true;
    }
    // Six décimales évitent de changer de tranche à cause des flottants.
    return $has_items && is_finite( $weight ) ? round( $weight, 6 ) : null;
}

function thaimoon_weight_shipping_price( $weight, $settings ) {
    if ( $weight < $settings['seuil_poids_1'] ) {
        return $settings['tarif_tranche_1'];
    }
    if ( $weight <= $settings['seuil_poids_2'] ) {
        return $settings['tarif_tranche_2'];
    }
    return $settings['tarif_tranche_3'];
}

// Le cache natif exclut les objets produit : inclure poids et options dans sa clé.
add_filter( 'woocommerce_cart_shipping_packages', 'thaimoon_weight_shipping_packages', 100 );
function thaimoon_weight_shipping_packages( $packages ) {
    $settings = thaimoon_weight_shipping_settings();
    foreach ( $packages as &$package ) {
        $package['thaimoon_weight_shipping'] = array(
            'version' => 1,
            'settings' => $settings,
            'weight_kg' => thaimoon_weight_shipping_package_weight( $package ),
        );
    }
    unset( $package );
    return $packages;
}

// Modifier le forfait disponible dans la zone, avant le calcul natif des taxes.
add_filter( 'woocommerce_shipping_method_add_rate_args', 'thaimoon_weight_shipping_rate', 20, 2 );
function thaimoon_weight_shipping_rate( $args, $method ) {
    if ( ! $method instanceof WC_Shipping_Method || 'flat_rate' !== $method->id || ( $args['id'] ?? '' ) !== $method->get_rate_id() || empty( $args['package']['contents'] ) ) {
        return $args;
    }

    $settings = thaimoon_weight_shipping_settings();
    $weight = thaimoon_weight_shipping_package_weight( $args['package'] );
    if ( empty( $settings ) || null === $weight ) {
        return $args;
    }

    $args['cost'] = thaimoon_weight_shipping_price( $weight, $settings );
    $args['taxes'] = '';
    $args['calc_tax'] = 'per_order';
    return $args;
}

// Précision et aide de saisie du groupe SCF existant, sans recréer ses champs.
foreach ( array( 'seuil_poids_1', 'seuil_poids_2', 'tarif_tranche_1', 'tarif_tranche_2', 'tarif_tranche_3' ) as $thaimoon_shipping_field ) {
    add_filter( 'acf/load_field/name=' . $thaimoon_shipping_field, 'thaimoon_weight_shipping_field' );
}
unset( $thaimoon_shipping_field );

function thaimoon_weight_shipping_field( $field ) {
    $threshold = 0 === strpos( $field['name'], 'seuil_' );
    $field['step'] = $threshold ? '0.001' : '0.01';
    $field['min'] = $threshold ? '0.001' : '0';
    $field['instructions'] = $threshold
        ? __( 'Poids en kg. La deuxième tranche inclut les deux seuils ; le deuxième doit dépasser le premier.', 'restaurant' )
        : __( 'Montant de livraison, selon les réglages de taxes WooCommerce. Le retrait et les frais de préparation restent distincts.', 'restaurant' );
    return $field;
}

add_action( 'admin_notices', 'thaimoon_weight_shipping_settings_notice' );
function thaimoon_weight_shipping_settings_notice() {
    $screen = get_current_screen();
    if ( ! $screen || false === strpos( $screen->id, 'tarification' ) || ! current_user_can( 'manage_options' ) || ! function_exists( 'get_field' ) || thaimoon_weight_shipping_settings() ) {
        return;
    }
    echo '<div class="notice notice-warning"><p>' . esc_html__( 'Livraison selon le poids : renseignez les cinq nombres, avec deux seuils positifs et croissants et des tarifs positifs ou nuls. Tant que ces réglages sont incomplets ou invalides, le forfait WooCommerce existant est conservé.', 'restaurant' ) . '</p></div>';
}
