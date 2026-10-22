<?php
/** Aides de saisie du groupe SCF existant, sans modifier les valeurs enregistrées. */
defined( 'ABSPATH' ) || exit;

foreach ( array( 'creneaux_de_livraison', 'creneaux_de_retrait', 'delai_de_preparation', 'jours_reservables' ) as $thaimoon_slots_field ) {
    add_filter( 'acf/load_field/name=' . $thaimoon_slots_field, 'thaimoon_slots_field_help' );
}
unset( $thaimoon_slots_field );

function thaimoon_slots_field_help( $field ) {
    if ( 0 === strpos( $field['name'], 'creneaux_de_' ) ) {
        $field['instructions'] = __( 'Une plage par ligne au format 12:00-12:30. Le début et la fin doivent être le même jour.', 'restaurant' );
    } elseif ( 'delai_de_preparation' === $field['name'] ) {
        $field['append'] = __( 'minutes', 'restaurant' );
        $field['min'] = 0;
        $field['max'] = 525600;
        $field['step'] = 1;
    } elseif ( 'jours_reservables' === $field['name'] ) {
        $field['instructions'] = __( 'Nombre de jours calendaires, aujourd’hui inclus. Les jours fermés sont retirés de cette période.', 'restaurant' );
        $field['min'] = 1;
        $field['max'] = 365;
        $field['step'] = 1;
    }
    return $field;
}

add_action( 'admin_notices', 'thaimoon_slots_settings_notice' );
function thaimoon_slots_settings_notice() {
    $screen = get_current_screen();
    if ( ! $screen || ( false === strpos( $screen->id, 'tarification' ) && false === strpos( $screen->id, 'creneaux' ) ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( ! thaimoon_slots_settings( 'pickup' ) || ! thaimoon_slots_settings( 'delivery' ) ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'Créneaux : vérifiez les jours cochés, les horaires (une ligne au format 12:00-12:30), le délai en minutes et le nombre de jours réservables (1 à 365). Les modes sans réglages valides ne peuvent pas être réservés.', 'restaurant' ) . '</p></div>';
    }
}
