<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', 'thaimoon_account_body_class' );
function thaimoon_account_body_class( $classes ) {
    if ( is_account_page() ) {
        $classes[] = 'account-page';
    }
    return $classes;
}
function thaimoon_account_view_url( $view ) {
    return add_query_arg( 'compte', $view, wc_get_page_permalink( 'myaccount' ) );
}
function thaimoon_account_view() {
    $view = isset( $_GET['compte'] ) && is_string( $_GET['compte'] ) ? sanitize_key( wp_unslash( $_GET['compte'] ) ) : '';
    if ( is_user_logged_in() ) {
        return 'deconnexion' === $view ? 'deconnexion' : 'details';
    }
    return ( 'inscription' === $view || isset( $_POST['register'] ) ) && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ? 'inscription' : 'connexion';
}
add_filter( 'woocommerce_get_breadcrumb', 'thaimoon_account_breadcrumb' );
function thaimoon_account_breadcrumb( $crumbs ) {
    if ( is_account_page() && ! is_wc_endpoint_url() && ! empty( $crumbs ) ) {
        $last = count( $crumbs ) - 1;
        $crumbs[ $last ][0] = is_user_logged_in() ? 'Compte' : ( 'inscription' === thaimoon_account_view() ? 'Inscription' : 'Connexion' );
    }
    return $crumbs;
}
add_filter( 'document_title_parts', 'thaimoon_account_document_title' );
function thaimoon_account_document_title( $parts ) {
    if ( is_account_page() && ! is_wc_endpoint_url() ) {
        $parts['title'] = is_user_logged_in() ? ( 'deconnexion' === thaimoon_account_view() ? 'Déconnexion' : 'Compte' ) : ( 'inscription' === thaimoon_account_view() ? 'Inscription' : 'Connexion' );
    }
    return $parts;
}
add_filter( 'the_content', 'thaimoon_account_heading', 12 );
function thaimoon_account_heading( $content ) {
    if ( ! is_account_page() || ! is_main_query() || ! in_the_loop() ) {
        return $content;
    }
    $title = 'Compte';
    $description = 'Bienvenue dans votre espace personnel, vous pourrez y retrouver les informations de votre compte.';
    if ( is_wc_endpoint_url( 'lost-password' ) ) {
        $title = 'Mot de passe oublié';
        $description = 'Retrouvez l’accès à votre compte en suivant les instructions ci-dessous.';
    } elseif ( ! is_user_logged_in() ) {
        $register = 'inscription' === thaimoon_account_view();
        $title = $register ? 'Inscription' : 'Connexion à votre compte';
        $description = $register ? 'Inscrivez-vous afin d’avoir accès à davantage de fonctionnalités.' : 'Connectez-vous à votre compte afin de profiter de vos avantages.';
    }
    return '<header class="account-heading"><h1 class="account-heading__title">' . esc_html( $title ) . '</h1><p class="account-heading__description">' . esc_html( $description ) . '</p></header>' . $content;
}
add_filter( 'woocommerce_account_menu_items', 'thaimoon_account_menu' );
function thaimoon_account_menu( $items ) {
    if ( ! is_account_page() ) {
        return $items;
    }
    if ( isset( $items['dashboard'] ) ) {
        $items['dashboard'] = 'Détails du compte';
    }
    if ( isset( $items['customer-logout'] ) ) {
        $items['customer-logout'] = 'Déconnexion';
    }
    unset( $items['edit-account'] );
    if ( is_user_logged_in() && WC()->customer instanceof WC_Customer && empty( WC()->customer->get_downloadable_products() ) ) {
        unset( $items['downloads'] );
    }
    return $items;
}
function thaimoon_account_menu_current( $endpoint ) {
    if ( 'deconnexion' === thaimoon_account_view() ) {
        return 'customer-logout' === $endpoint;
    }
    return ( 'dashboard' === $endpoint && is_wc_endpoint_url( 'edit-account' ) ) || wc_is_current_account_menu_item( $endpoint );
}

function thaimoon_account_posted_text( $name ) {
    return isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
}
function thaimoon_account_registration_request() {
    return isset( $_POST['thaimoon_account_register'], $_POST['woocommerce-register-nonce'] ) &&
        '1' === $_POST['thaimoon_account_register'] && is_string( $_POST['woocommerce-register-nonce'] ) &&
        wp_verify_nonce( wp_unslash( $_POST['woocommerce-register-nonce'] ), 'woocommerce-register' );
}
add_filter( 'woocommerce_process_registration_errors', 'thaimoon_account_registration_errors', 10, 4 );
function thaimoon_account_registration_errors( $errors, $username, $password, $email ) {
    if ( ! thaimoon_account_registration_request() ) {
        return $errors;
    }
    if ( 'yes' !== get_option( 'woocommerce_enable_myaccount_registration' ) ) {
        $errors->add( 'registration-disabled', 'Les inscriptions sont actuellement désactivées.' );
    }
    foreach ( array( 'reg_last_name' => 'votre nom', 'reg_first_name' => 'votre prénom', 'reg_phone' => 'votre numéro de téléphone' ) as $name => $label ) {
        if ( '' === thaimoon_account_posted_text( $name ) ) {
            $errors->add( $name, sprintf( 'Veuillez renseigner %s.', $label ) );
        }
    }
    $phone = thaimoon_account_posted_text( 'reg_phone' );
    if ( $phone && ! WC_Validation::is_phone( $phone ) ) {
        $errors->add( 'invalid-phone', 'Veuillez saisir un numéro de téléphone valide.' );
    }
    if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
        $confirm = isset( $_POST['password_confirm'] ) && is_string( $_POST['password_confirm'] ) ? $_POST['password_confirm'] : '';
        if ( ! is_string( $password ) || '' === $password || '' === $confirm || $password !== $confirm ) {
            $errors->add( 'password-confirmation', 'Les deux mots de passe doivent être identiques.' );
        }
    }
    return $errors;
}
add_action( 'woocommerce_created_customer', 'thaimoon_account_save_registration' );
function thaimoon_account_save_registration( $customer_id ) {
    if ( ! thaimoon_account_registration_request() ) {
        return;
    }
    $customer = new WC_Customer( $customer_id );
    $customer->set_first_name( thaimoon_account_posted_text( 'reg_first_name' ) );
    $customer->set_last_name( thaimoon_account_posted_text( 'reg_last_name' ) );
    $customer->set_billing_first_name( thaimoon_account_posted_text( 'reg_first_name' ) );
    $customer->set_billing_last_name( thaimoon_account_posted_text( 'reg_last_name' ) );
    $customer->set_billing_phone( wc_sanitize_phone_number( thaimoon_account_posted_text( 'reg_phone' ) ) );
    $customer->save();
}

function thaimoon_account_languages() {
    $languages = array();
    foreach ( array_unique( array_merge( array( 'en_US', get_locale() ), get_available_languages() ) ) as $locale ) {
        $languages[ $locale ] = 0 === strpos( $locale, 'fr_' ) ? 'Français' : ( 'en_US' === $locale ? 'English' : str_replace( '_', ' — ', $locale ) );
    }
    return $languages;
}
add_action( 'woocommerce_edit_account_form_fields', 'thaimoon_account_extra_fields' );
function thaimoon_account_extra_fields() {
    $customer = new WC_Customer( get_current_user_id() );
    $phone = isset( $_POST['account_phone'] ) ? thaimoon_account_posted_text( 'account_phone' ) : $customer->get_billing_phone();
    $locale = isset( $_POST['account_locale'] ) ? thaimoon_account_posted_text( 'account_locale' ) : get_user_locale();
    ?>
    <p class="woocommerce-form-row form-row account-form__field">
        <label for="account_phone">Téléphone</label>
        <input type="tel" class="input-text" name="account_phone" id="account_phone" autocomplete="tel" value="<?php echo esc_attr( $phone ); ?>">
    </p>
    <p class="woocommerce-form-row form-row account-form__field">
        <label for="account_locale">Langue du compte</label>
        <select name="account_locale" id="account_locale"><?php foreach ( thaimoon_account_languages() as $code => $label ) : ?><option value="<?php echo esc_attr( $code ); ?>" <?php selected( $locale, $code ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
    </p>
    <?php
}
add_action( 'woocommerce_save_account_details_errors', 'thaimoon_account_details_errors', 10, 2 );
function thaimoon_account_details_errors( $errors, $user ) {
    if ( isset( $_POST['account_phone'] ) && ( ! is_string( $_POST['account_phone'] ) || ( '' !== thaimoon_account_posted_text( 'account_phone' ) && ! WC_Validation::is_phone( thaimoon_account_posted_text( 'account_phone' ) ) ) ) ) {
        $errors->add( 'invalid-phone', 'Veuillez saisir un numéro de téléphone valide.' );
    }
    if ( isset( $_POST['account_locale'] ) && ! array_key_exists( thaimoon_account_posted_text( 'account_locale' ), thaimoon_account_languages() ) ) {
        $errors->add( 'invalid-locale', 'Veuillez choisir une langue disponible.' );
    }
}
add_action( 'woocommerce_save_account_details', 'thaimoon_account_save_details' );
function thaimoon_account_save_details( $customer_id ) {
    if ( isset( $_POST['account_phone'] ) ) {
        $customer = new WC_Customer( $customer_id );
        $customer->set_billing_phone( wc_sanitize_phone_number( thaimoon_account_posted_text( 'account_phone' ) ) );
        $customer->save();
    }
    $locale = thaimoon_account_posted_text( 'account_locale' );
    if ( isset( $_POST['account_locale'] ) && array_key_exists( $locale, thaimoon_account_languages() ) ) {
        update_user_meta( $customer_id, 'locale', $locale );
    }
}


add_filter( 'gettext_woocommerce', 'thaimoon_account_password_labels', 10, 3 );
function thaimoon_account_password_labels( $translation, $text, $domain ) {
    $labels = array(
        'Current password (leave blank to leave unchanged)' => 'Mot de passe actuel',
        'New password (leave blank to leave unchanged)' => 'Nouveau mot de passe',
    );
    if ( isset( $labels[ $text ] ) && ! is_admin() && is_account_page() && is_wc_endpoint_url( 'edit-account' ) ) {
        return $labels[ $text ];
    }
    return $translation;
}

function thaimoon_account_shipping_form() {
    return ! is_admin() && is_user_logged_in() && is_account_page() && is_wc_endpoint_url( 'edit-address' ) &&
        'shipping' === wc_edit_address_i18n( get_query_var( 'edit-address' ), true );
}

function thaimoon_account_shipping_name( $part ) {
    $customer = new WC_Customer( get_current_user_id() );
    foreach ( array( 'get_shipping_' . $part, 'get_' . $part, 'get_billing_' . $part ) as $getter ) {
        $value = trim( $customer->$getter() );
        if ( '' !== $value ) {
            return $value;
        }
    }
    return '';
}

add_filter( 'woocommerce_address_to_edit', 'thaimoon_account_shipping_fields', 10, 2 );
function thaimoon_account_shipping_fields( $address, $type ) {
    if ( 'shipping' === $type && thaimoon_account_shipping_form() ) {
        foreach ( array( 'first_name', 'last_name' ) as $part ) {
            if ( '' !== thaimoon_account_shipping_name( $part ) ) {
                unset( $address[ 'shipping_' . $part ] );
            }
        }
    }
    return $address;
}

add_filter( 'woocommerce_process_myaccount_field_shipping_first_name', 'thaimoon_account_shipping_first_name' );
function thaimoon_account_shipping_first_name( $value ) {
    $name = thaimoon_account_shipping_form() ? thaimoon_account_shipping_name( 'first_name' ) : '';
    return '' !== $name ? $name : $value;
}
add_filter( 'woocommerce_process_myaccount_field_shipping_last_name', 'thaimoon_account_shipping_last_name' );
function thaimoon_account_shipping_last_name( $value ) {
    $name = thaimoon_account_shipping_form() ? thaimoon_account_shipping_name( 'last_name' ) : '';
    return '' !== $name ? $name : $value;
}

function thaimoon_account_profile_field( $label, $value, $url ) {
    ?>
    <div class="account-profile__field">
        <p class="account-profile__label"><?php echo esc_html( $label ); ?></p>
        <div class="account-profile__value"><span><?php echo esc_html( $value ); ?></span><a class="account-profile__edit" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( 'Modifier : ' . $label ); ?>"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/account-edit.svg' ); ?>" width="24.6664" height="24.6664" alt=""></a></div>
    </div>
    <?php
}


require_once __DIR__ . '/account-orders.php';
