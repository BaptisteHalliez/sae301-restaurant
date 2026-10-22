<?php
/** Jours et créneaux de livraison/retrait, groupe SCF Réglages Créneaux. */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/fulfilment-slots-settings.php';

/** Les libellés SCF lèvent l'ambiguïté des choix numériques (0/1 = lundi ou dimanche). */
function thaimoon_slots_days( $values, $choices = array() ) {
    if ( ! is_array( $values ) ) {
        return array();
    }
    $names = array( 'lundi' => 1, 'monday' => 1, 'mardi' => 2, 'tuesday' => 2, 'mercredi' => 3, 'wednesday' => 3, 'jeudi' => 4, 'thursday' => 4, 'vendredi' => 5, 'friday' => 5, 'samedi' => 6, 'saturday' => 6, 'dimanche' => 7, 'sunday' => 7 );
    $days = array();
    foreach ( $values as $value ) {
        if ( ! is_scalar( $value ) ) {
            return array();
        }
        $label = $choices[ $value ] ?? $value;
        $key = sanitize_title( (string) $label );
        $day = $names[ $key ] ?? ( ctype_digit( (string) $label ) ? (int) $label : 0 );
        if ( $day < 1 || $day > 7 ) {
            return array();
        }
        $days[] = $day;
    }
    return array_values( array_unique( $days ) );
}

/** Une ligne = une plage, par exemple 12:00 - 12:30 ou 12h00 – 12h30. */
function thaimoon_slots_ranges( $text ) {
    if ( ! is_string( $text ) || '' === trim( $text ) ) {
        return array();
    }
    $ranges = array();
    foreach ( preg_split( '/\R/u', trim( $text ) ) as $line ) {
        if ( '' === trim( $line ) ) {
            continue;
        }
        if ( ! preg_match( '/^\s*(\d{1,2})[:hH](\d{2})\s*(?:-|–|—|à)\s*(\d{1,2})[:hH](\d{2})\s*$/u', $line, $m ) ) {
            return array();
        }
        $start = (int) $m[1] * 60 + (int) $m[2];
        $end = (int) $m[3] * 60 + (int) $m[4];
        if ( (int) $m[1] > 23 || (int) $m[3] > 23 || (int) $m[2] > 59 || (int) $m[4] > 59 || $end <= $start ) {
            return array();
        }
        $value = sprintf( '%02d:%02d-%02d:%02d', $m[1], $m[2], $m[3], $m[4] );
        $ranges[ $value ] = str_replace( array( ':', '-' ), array( 'h', ' – ' ), $value );
    }
    ksort( $ranges );
    return $ranges;
}

function thaimoon_slots_settings( $mode ) {
    if ( ! function_exists( 'get_field' ) || ! in_array( $mode, array( 'pickup', 'delivery' ), true ) ) {
        return array();
    }
    $suffix = 'pickup' === $mode ? 'retrait' : 'livraison';
    $field = function_exists( 'get_field_object' ) ? get_field_object( 'jours_de_' . $suffix, 'option', false, false ) : false;
    $days = thaimoon_slots_days( get_field( 'jours_de_' . $suffix, 'option', false ), $field['choices'] ?? array() );
    $ranges = thaimoon_slots_ranges( get_field( 'creneaux_de_' . $suffix, 'option', false ) );
    $delay = get_field( 'delai_de_preparation', 'option', false );
    $horizon = get_field( 'jours_reservables', 'option', false );
    if ( ! $days || ! $ranges || ! is_numeric( $delay ) || ! is_numeric( $horizon ) || ! is_finite( (float) $delay ) || (float) $delay < 0 || (float) $delay > 525600 || (float) $delay !== floor( (float) $delay ) || (float) $horizon < 1 || (float) $horizon > 365 || (float) $horizon !== floor( (float) $horizon ) ) {
        return array();
    }
    return array( 'days' => $days, 'ranges' => $ranges, 'delay' => (int) $delay, 'horizon' => (int) $horizon );
}

/** Calcul centralisé, utilisé par l'affichage ET par la validation côté serveur. */
function thaimoon_slots_availability( $settings, $now = null ) {
    if ( empty( $settings ) ) {
        return array();
    }
    $now = $now instanceof DateTimeImmutable ? $now->setTimezone( wp_timezone() ) : current_datetime();
    $cutoff = $now->getTimestamp() + $settings['delay'] * MINUTE_IN_SECONDS;
    $available = array();
    for ( $offset = 0; $offset < $settings['horizon']; ++$offset ) {
        $day = $now->setTime( 0, 0 )->modify( '+' . $offset . ' days' );
        if ( ! in_array( (int) $day->format( 'N' ), $settings['days'], true ) ) {
            continue;
        }
        $date = $day->format( 'Y-m-d' );
        $slots = array();
        foreach ( $settings['ranges'] as $value => $label ) {
            $start = new DateTimeImmutable( $date . ' ' . substr( $value, 0, 5 ), wp_timezone() );
            // Une heure inexistante au passage à l'heure d'été n'est pas décalée silencieusement.
            if ( $start->format( 'H:i' ) !== substr( $value, 0, 5 ) || $start->getTimestamp() <= $now->getTimestamp() || $start->getTimestamp() < $cutoff ) {
                continue;
            }
            $slots[ $value ] = $label;
        }
        if ( $slots ) {
            $available[ $date ] = array( 'label' => wp_date( 'l j F Y', $day->getTimestamp(), wp_timezone() ), 'slots' => $slots );
        }
    }
    return $available;
}

/** Un seul mode doit couvrir tous les colis ; ne pas déduire le retrait du paiement. */
function thaimoon_slots_mode() {
    if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
        return '';
    }
    $rates = thaimoon_commerce_selected_rates();
    if ( ! $rates ) {
        return '';
    }
    $modes = array();
    foreach ( $rates as $rate ) {
        $modes[] = thaimoon_commerce_rate_is_pickup( $rate ) ? 'pickup' : 'delivery';
    }
    $modes = array_unique( $modes );
    return count( $modes ) === 1 ? reset( $modes ) : '';
}

function thaimoon_slots_selection( $source ) {
    $selected = array();
    foreach ( array( 'mode', 'date', 'slot' ) as $key ) {
        $value = $source[ 'thaimoon_fulfilment_' . $key ] ?? '';
        $selected[ $key ] = is_string( $value ) ? sanitize_text_field( $value ) : '';
    }
    return $selected;
}

function thaimoon_slots_validate( $selection, $mode, $now = null ) {
    if ( ! $mode || $selection['mode'] !== $mode ) {
        return new WP_Error( 'thaimoon_slots_mode', __( 'Choisissez votre mode d’expédition, puis un jour et un créneau correspondants.', 'restaurant' ) );
    }
    $settings = thaimoon_slots_settings( $mode );
    if ( ! $settings ) {
        return new WP_Error( 'thaimoon_slots_settings', __( 'Les créneaux ne sont pas encore configurés pour ce mode. Merci de contacter le restaurant ou de choisir un autre mode d’expédition.', 'restaurant' ) );
    }
    $available = thaimoon_slots_availability( $settings, $now );
    $parts = explode( '|', $selection['slot'] );
    $slot = count( $parts ) === 2 && $parts[0] === $selection['date'] ? $parts[1] : '';
    if ( ! isset( $available[ $selection['date'] ]['slots'][ $slot ] ) ) {
        return new WP_Error( 'thaimoon_slots_unavailable', __( 'Choisissez un jour et un créneau disponibles. Un horaire passé ou trop proche ne peut plus être réservé.', 'restaurant' ) );
    }
    $start = new DateTimeImmutable( $selection['date'] . ' ' . substr( $slot, 0, 5 ), wp_timezone() );
    return array( 'mode' => $mode, 'date' => $selection['date'], 'slot' => $slot, 'label' => $available[ $selection['date'] ]['label'] . ' — ' . $available[ $selection['date'] ]['slots'][ $slot ], 'timezone' => wp_timezone_string(), 'start' => $start->getTimestamp() );
}

add_action( 'woocommerce_checkout_update_order_review', 'thaimoon_slots_capture_selection' );
function thaimoon_slots_capture_selection( $posted ) {
    if ( ! WC()->session || ! is_string( $posted ) ) {
        return;
    }
    parse_str( $posted, $fields );
    WC()->session->set( 'thaimoon_fulfilment_selection', thaimoon_slots_selection( $fields ) );
}

add_action( 'woocommerce_before_order_notes', 'thaimoon_slots_render', 20 );
function thaimoon_slots_render() {
    $mode = thaimoon_slots_mode();
    $settings = thaimoon_slots_settings( $mode );
    $available = thaimoon_slots_availability( $settings );
    $selection = WC()->session ? WC()->session->get( 'thaimoon_fulfilment_selection', array() ) : array();
    if ( isset( $_POST['thaimoon_fulfilment_date'] ) ) { // La nonce du formulaire est validée par WooCommerce.
        $selection = thaimoon_slots_selection( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }
    if ( ( $selection['mode'] ?? '' ) !== $mode ) {
        $selection = array();
    }
    $date = isset( $available[ $selection['date'] ?? '' ] ) ? $selection['date'] : '';
    $slot = '';
    if ( $date && ! is_wp_error( thaimoon_slots_validate( array_merge( array( 'mode' => $mode, 'date' => $date, 'slot' => '' ), $selection ), $mode ) ) ) {
        $slot = $selection['slot'];
    }
    get_template_part( 'template-parts/checkout/fulfilment-slots', null, compact( 'mode', 'settings', 'available', 'date', 'slot' ) );
}

add_filter( 'woocommerce_update_order_review_fragments', 'thaimoon_slots_fragments' );
function thaimoon_slots_fragments( $fragments ) {
    ob_start();
    thaimoon_slots_render();
    $fragments['.checkout-fulfilment'] = ob_get_clean();
    return $fragments;
}

add_action( 'wp_enqueue_scripts', 'thaimoon_slots_assets', 50 );
function thaimoon_slots_assets() {
    if ( is_checkout() && ! is_wc_endpoint_url() ) {
        wp_enqueue_script( 'thaimoon-fulfilment-slots', get_stylesheet_directory_uri() . '/assets/js/fulfilment-slots.js', array( 'jquery', 'wc-checkout' ), (string) filemtime( get_stylesheet_directory() . '/assets/js/fulfilment-slots.js' ), true );
    }
}

add_action( 'woocommerce_after_checkout_validation', 'thaimoon_slots_checkout_validation', 20, 2 );
function thaimoon_slots_checkout_validation( $data, $errors ) {
    if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
        return;
    }
    $result = thaimoon_slots_validate( thaimoon_slots_selection( wp_unslash( $_POST ) ), thaimoon_slots_mode() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce valide la nonce du checkout.
    if ( is_wp_error( $result ) ) {
        $errors->add( $result->get_error_code(), $result->get_error_message() );
    }
}

add_action( 'woocommerce_checkout_create_order', 'thaimoon_slots_save_order', 20, 2 );
function thaimoon_slots_save_order( $order, $data ) {
    if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
        return;
    }
    // Revalider à l'enregistrement : l'heure ou les réglages ont pu changer entre-temps.
    $result = thaimoon_slots_validate( thaimoon_slots_selection( wp_unslash( $_POST ) ), thaimoon_slots_mode() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Appelé par le checkout vérifié.
    if ( is_wp_error( $result ) ) {
        throw new Exception( $result->get_error_message() );
    }
    $order->update_meta_data( '_thaimoon_fulfilment', $result );
}

/** Même contrôle si une intégration tente d'utiliser directement le checkout Store API. */
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'thaimoon_slots_store_api', 20, 2 );
function thaimoon_slots_store_api( $order, $request ) {
    if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
        return;
    }
    $extensions = $request->get_param( 'extensions' );
    $input = is_array( $extensions ) ? ( $extensions['thaimoon-fulfilment'] ?? array() ) : array();
    $result = thaimoon_slots_validate( thaimoon_slots_selection( is_array( $input ) ? $input : array() ), thaimoon_slots_mode() );
    if ( is_wp_error( $result ) ) {
        throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( $result->get_error_code(), $result->get_error_message(), 400 );
    }
    $order->update_meta_data( '_thaimoon_fulfilment', $result );
}

/** Le même libellé enregistré apparaît dans les détails, confirmations et emails natifs. */
add_filter( 'woocommerce_get_order_item_totals', 'thaimoon_slots_order_totals', 20, 3 );
function thaimoon_slots_order_totals( $totals, $order, $tax_display ) {
    $saved = $order->get_meta( '_thaimoon_fulfilment', true );
    if ( ! is_array( $saved ) || empty( $saved['label'] ) || ! in_array( $saved['mode'] ?? '', array( 'pickup', 'delivery' ), true ) ) {
        return $totals;
    }
    $row = array( 'label' => 'pickup' === $saved['mode'] ? __( 'Créneau de retrait :', 'restaurant' ) : __( 'Créneau de livraison :', 'restaurant' ), 'value' => esc_html( $saved['label'] ) );
    $result = array();
    foreach ( $totals as $key => $value ) {
        if ( 'order_total' === $key ) {
            $result['thaimoon_fulfilment'] = $row;
        }
        $result[ $key ] = $value;
    }
    if ( ! isset( $result['thaimoon_fulfilment'] ) ) {
        $result['thaimoon_fulfilment'] = $row;
    }
    return $result;
}

add_action( 'woocommerce_admin_order_data_after_shipping_address', 'thaimoon_slots_admin_order' );
function thaimoon_slots_admin_order( $order ) {
    $saved = $order->get_meta( '_thaimoon_fulfilment', true );
    if ( is_array( $saved ) && ! empty( $saved['label'] ) ) {
        echo '<p><strong>' . esc_html( 'pickup' === ( $saved['mode'] ?? '' ) ? __( 'Créneau de retrait', 'restaurant' ) : __( 'Créneau de livraison', 'restaurant' ) ) . '</strong><br>' . esc_html( $saved['label'] ) . '</p>';
    }
}
