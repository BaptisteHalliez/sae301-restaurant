<?php
/** Aperçu des données réelles du client — hooks du gabarit WooCommerce 4.4.0. */
defined( 'ABSPATH' ) || exit;
$customer = new WC_Customer( get_current_user_id() );
$edit = wc_get_account_endpoint_url( 'edit-account' );
$name = trim( $customer->get_first_name() . ' ' . $customer->get_last_name() );
$address = $customer->get_billing_address_1() ? WC()->countries->get_formatted_address( array( 'address_1' => $customer->get_billing_address_1(), 'address_2' => $customer->get_billing_address_2(), 'postcode' => $customer->get_billing_postcode(), 'city' => $customer->get_billing_city(), 'state' => $customer->get_billing_state(), 'country' => $customer->get_billing_country() ), ', ' ) : '';
$languages = thaimoon_account_languages();
$locale = get_user_locale();
?>
<section class="account-panel account-profile" aria-label="Détails du compte">
    <div class="account-profile__columns">
        <div class="account-profile__column">
            <?php thaimoon_account_profile_field( 'Nom', $name ?: $customer->get_display_name(), $edit . '#account_first_name' ); ?>
            <?php thaimoon_account_profile_field( 'Adresse email', $customer->get_email(), $edit . '#account_email' ); ?>
            <?php thaimoon_account_profile_field( 'Téléphone', $customer->get_billing_phone() ?: 'Non renseigné', $edit . '#account_phone' ); ?>
        </div>
        <div class="account-profile__column">
            <?php thaimoon_account_profile_field( 'Mot de passe', '••••••••••••••', $edit . '#password_current' ); ?>
            <?php thaimoon_account_profile_field( 'Adresse', wp_strip_all_tags( $address ) ?: 'Non définie', wc_get_account_endpoint_url( 'edit-address' ) ); ?>
            <?php thaimoon_account_profile_field( 'Langue', isset( $languages[ $locale ] ) ? $languages[ $locale ] : $locale, $edit . '#account_locale' ); ?>
        </div>
    </div>
    <div class="account-profile__divider" aria-hidden="true"></div>
</section>
<?php
do_action( 'woocommerce_account_dashboard' );
do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );
