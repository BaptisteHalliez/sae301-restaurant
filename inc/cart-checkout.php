<?php
/** Panier et validation classiques WooCommerce, présentation Figma V1.2. */
defined( 'ABSPATH' ) || exit;

// Codes promo V1.3 : formulaires, validations et calculs natifs de WooCommerce.
require_once __DIR__ . '/coupons.php';

add_action( 'wp_enqueue_scripts', 'thaimoon_commerce_assets', 45 );
function thaimoon_commerce_assets() {
    if ( ! is_cart() && ! is_checkout() ) {
        return;
    }
    $path = get_stylesheet_directory();
    $uri  = get_stylesheet_directory_uri();
    // Les pages enregistrées en blocs retirent ces scripts à la priorité 20.
    // Réactiver le moteur classique pour les formulaires rendus par ce thème.
    $engine = is_cart() ? 'wc-cart' : 'wc-checkout';
    wp_enqueue_script( $engine );
    wp_enqueue_script( 'selectWoo' );
    wp_enqueue_style( 'select2' );
    wp_enqueue_script( 'thaimoon-commerce', $uri . '/assets/js/cart-checkout.js', array( 'jquery', $engine ), (string) filemtime( $path . '/assets/js/cart-checkout.js' ), true );
}

// Remplacer uniquement les blocs panier/commande à l'affichage, sans écrire dans les pages.
add_filter( 'the_content', 'thaimoon_commerce_classic_content', 8 );
function thaimoon_commerce_classic_content( $content ) {
    if ( is_admin() || ! is_main_query() || ! in_the_loop() || ( ! is_cart() && ! is_checkout() ) ) {
        return $content;
    }
    if ( ! has_block( 'woocommerce/cart', $content ) && ! has_block( 'woocommerce/checkout', $content ) ) {
        return $content;
    }
    return serialize_blocks( thaimoon_commerce_classic_blocks( parse_blocks( $content ) ) );
}
function thaimoon_commerce_classic_blocks( $blocks ) {
    foreach ( $blocks as &$block ) {
        if ( in_array( $block['blockName'], array( 'woocommerce/cart', 'woocommerce/checkout' ), true ) ) {
            $shortcode = 'woocommerce/cart' === $block['blockName'] ? '[woocommerce_cart]' : '[woocommerce_checkout]';
            $block = array( 'blockName' => 'core/shortcode', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => $shortcode, 'innerContent' => array( $shortcode ) );
        } elseif ( ! empty( $block['innerBlocks'] ) ) {
            $block['innerBlocks'] = thaimoon_commerce_classic_blocks( $block['innerBlocks'] );
        }
    }
    unset( $block );
    return $blocks;
}

add_action( 'woocommerce_before_cart', 'thaimoon_cart_intro', 2 );
add_action( 'woocommerce_cart_is_empty', 'thaimoon_cart_intro', 2 );
add_filter( 'woocommerce_get_breadcrumb', 'thaimoon_commerce_breadcrumb' );
function thaimoon_commerce_breadcrumb( $crumbs ) {
    if ( is_checkout() && ! is_wc_endpoint_url() && count( $crumbs ) > 1 ) {
        array_splice( $crumbs, count( $crumbs ) - 1, 0, array( array( 'Panier', wc_get_cart_url() ) ) );
    }
    return $crumbs;
}
function thaimoon_cart_intro() {
    thaimoon_commerce_intro( 'Mon panier', WC()->cart->is_empty() ? 'Votre panier est vide. Découvrez la carte pour composer votre repas.' : 'Vérifiez vos produits, ajustez les quantités et choisissez votre mode de retrait avant de passer à la validation.' );
}
add_action( 'woocommerce_before_checkout_form', 'thaimoon_checkout_intro', 2 );
function thaimoon_checkout_intro() {
    thaimoon_commerce_intro( 'Validation de commande', 'Vérifiez vos informations de contact, laissez un message pour le retrait et choisissez votre mode de paiement avant de confirmer votre commande.' );
}
function thaimoon_commerce_intro( $title, $description ) {
    echo '<header class="commerce-heading"><h1 class="commerce-heading__title">' . esc_html( $title ) . '</h1><p class="commerce-heading__description">' . esc_html( $description ) . '</p></header>';
}

function thaimoon_commerce_store_address() {
    if ( '' === trim( (string) get_option( 'woocommerce_store_address' ) ) ) {
        return str_replace( array( "\r\n", "\r", "\n" ), ', ', get_theme_mod( 'thaimoon_address', "8 rue des Chevaliers\n45190 Beaugency" ) );
    }
    return implode( ', ', array_filter( array( get_option( 'woocommerce_store_address' ), get_option( 'woocommerce_store_address_2' ), trim( get_option( 'woocommerce_store_postcode' ) . ' ' . get_option( 'woocommerce_store_city' ) ) ) ) );
}
function thaimoon_commerce_selected_rates() {
    $selected = array();
    $chosen   = WC()->session ? WC()->session->get( 'chosen_shipping_methods', array() ) : array();
    foreach ( WC()->shipping()->get_packages() as $index => $package ) {
        if ( isset( $chosen[ $index ], $package['rates'][ $chosen[ $index ] ] ) ) {
            $selected[] = $package['rates'][ $chosen[ $index ] ];
        }
    }
    return $selected;
}
function thaimoon_commerce_is_pickup() {
    $rates = thaimoon_commerce_selected_rates();
    if ( empty( $rates ) ) {
        return false;
    }
    foreach ( $rates as $rate ) {
        if ( ! thaimoon_commerce_rate_is_pickup( $rate ) ) {
            return false;
        }
    }
    return true;
}
function thaimoon_commerce_rate_is_pickup( $rate ) {
    // Ce site nomme aussi « Retrait en Boutique » une méthode gratuite existante.
    $pickup = in_array( $rate->get_method_id(), array( 'local_pickup', 'pickup_location' ), true ) ||
        ( 'free_shipping' === $rate->get_method_id() && false !== strpos( sanitize_title( $rate->get_label() ), 'retrait' ) );
    return (bool) apply_filters( 'thaimoon_shipping_rate_is_pickup', $pickup, $rate );
}
function thaimoon_commerce_rate_price( $rate ) {
    $cost = (float) $rate->get_cost();
    if ( WC()->cart->display_prices_including_tax() ) {
        $cost += (float) $rate->get_shipping_tax();
    }
    return 0.0 === $cost ? esc_html__( 'GRATUIT', 'thaimoon-restaurant' ) : wc_price( $cost );
}
function thaimoon_commerce_shipping_summary() {
    if ( ! WC()->cart->needs_shipping() ) {
        return;
    }
    $rates = thaimoon_commerce_selected_rates();
    ?>
    <section class="shipping-choice" aria-label="Mode d’expédition choisi">
        <h3 class="shipping-choice__heading">Mode d’expédition</h3>
        <?php if ( empty( $rates ) ) : ?>
            <p class="shipping-choice__pending">Choisissez un mode d’expédition. Les possibilités dépendent de votre adresse.</p>
        <?php endif; ?>
        <?php foreach ( $rates as $rate ) :
            $pickup = thaimoon_commerce_rate_is_pickup( $rate ); ?>
            <div class="shipping-choice__card" data-commerce-pickup="<?php echo $pickup ? 'yes' : 'no'; ?>">
                <img class="shipping-choice__icon" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/commerce-check.svg' ); ?>" width="20" height="20" alt="">
                <div class="shipping-choice__details">
                    <p class="shipping-choice__name"><?php echo esc_html( $rate->get_label() ); ?></p>
                    <?php if ( $pickup && thaimoon_commerce_store_address() ) : ?>
                        <p class="shipping-choice__address"><?php echo esc_html( thaimoon_commerce_store_address() ); ?></p>
                    <?php endif; ?>
                    <p class="shipping-choice__price">Expédition : <?php echo wp_kses_post( thaimoon_commerce_rate_price( $rate ) ); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
    <?php
}
function thaimoon_commerce_shipping_total_row() {
    if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) {
        echo '<tr class="commerce-totals__shipping"><th>Expédition</th><td>' . wp_kses_post( WC()->cart->get_cart_shipping_total() ) . '</td></tr>';
    }
}
function thaimoon_commerce_product_description( $product ) {
    $source = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
    if ( ! $source ) {
        return '';
    }
    return wp_strip_all_tags( $source->get_short_description() ?: $source->get_description() );
}

add_filter( 'woocommerce_cart_item_quantity', 'thaimoon_commerce_cart_quantity', 10, 3 );
function thaimoon_commerce_cart_quantity( $html, $key, $item ) {
    if ( ! is_cart() || $item['data']->is_sold_individually() ) {
        return $html;
    }
    $uri = get_stylesheet_directory_uri() . '/assets/images/';
    return '<div class="cart-quantity"><button class="cart-quantity__button" type="button" data-cart-step="1" aria-label="Augmenter la quantité" hidden><img src="' . esc_url( $uri . 'quantity-add.svg' ) . '" width="26" height="26" alt=""></button>' . $html . '<button class="cart-quantity__button" type="button" data-cart-step="-1" aria-label="Diminuer la quantité" hidden><img src="' . esc_url( $uri . 'quantity-remove.svg' ) . '" width="26" height="26" alt=""></button></div>';
}

add_filter( 'woocommerce_checkout_fields', 'thaimoon_commerce_checkout_fields' );
function thaimoon_commerce_checkout_fields( $fields ) {
    // Les champs et leurs obligations de validation restent ceux de WooCommerce.
    $contact = array( 'billing_last_name' => array( 10, 'Votre nom' ), 'billing_first_name' => array( 20, 'Votre prénom' ), 'billing_email' => array( 30, 'Votre adresse email' ), 'billing_phone' => array( 40, 'Votre numéro de téléphone' ) );
    foreach ( $fields['billing'] as $key => &$field ) {
        if ( isset( $contact[ $key ] ) ) {
            $field['priority'] = $contact[ $key ][0];
            $field['placeholder'] = $contact[ $key ][1];
            $field['class'] = array( 10 === $field['priority'] || 30 === $field['priority'] ? 'form-row-first' : 'form-row-last' );
        } else {
            $field['priority'] = 50 + ( isset( $field['priority'] ) ? $field['priority'] : 0 );
        }
    }
    unset( $field );
    if ( isset( $fields['order']['order_comments'] ) ) {
        $fields['order']['order_comments']['label'] = 'Message au restaurant';
        $fields['order']['order_comments']['placeholder'] = 'Votre message au restaurant : horaire souhaité, précisions pour le retrait…';
    }
    return $fields;
}

add_action( 'woocommerce_before_order_notes', 'thaimoon_commerce_instructions' );
function thaimoon_commerce_instructions() {
    $pickup = thaimoon_commerce_is_pickup();
    ?>
    <section class="checkout-instructions">
        <h3 class="checkout-instructions__heading" data-commerce-instructions-title><?php echo $pickup ? 'Instructions pour le retrait' : 'Instructions pour la livraison'; ?></h3>
        <p data-commerce-instructions="pickup" <?php echo $pickup ? '' : 'hidden'; ?>>Récupérez votre commande au restaurant<?php echo thaimoon_commerce_store_address() ? ' : ' . esc_html( thaimoon_commerce_store_address() ) : ''; ?>.</p>
        <p data-commerce-instructions="delivery" <?php echo $pickup ? 'hidden' : ''; ?>>Pour une livraison à domicile, renseignez votre adresse complète. Les modes disponibles et leur tarif sont calculés selon cette adresse.</p>
    </section>
    <?php
}
add_filter( 'woocommerce_order_button_text', 'thaimoon_commerce_order_button_text' );
function thaimoon_commerce_order_button_text() {
    return 'Valider ma commande';
}

// Le panier conserve ses notices et ses hooks, sans recommandations dans les colonnes.
add_action( 'wp', 'thaimoon_commerce_setup' );
function thaimoon_commerce_setup() {
    if ( is_cart() ) {
        remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
    }
}
