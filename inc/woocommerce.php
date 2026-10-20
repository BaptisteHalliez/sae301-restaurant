<?php
/** Réglages des cartes du catalogue. Leurs balises sont dans woocommerce/content-product.php. */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'thaimoon_product_cards_setup', 30 );
function thaimoon_product_cards_setup() {
    // Le prix est rendu une seule fois, sur l'image, dans notre gabarit.
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
    // Storefront déplace la promotion après le titre ; la garder sur le visuel.
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 6 );
    add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
}

add_filter( 'woocommerce_product_loop_start', 'thaimoon_product_grid' );
function thaimoon_product_grid( $html ) {
    return preg_replace( '/class="products\b/', 'class="products product-grid', $html, 1 );
}

add_filter( 'woocommerce_product_loop_title_classes', 'thaimoon_product_title_classes' );
function thaimoon_product_title_classes( $classes ) {
    return $classes . ' product-card__title';
}

add_filter( 'woocommerce_loop_add_to_cart_args', 'thaimoon_product_button_classes' );
function thaimoon_product_button_classes( $args ) {
    // Conserver les classes, attributs et comportements natifs de WooCommerce.
    $args['class'] = ( isset( $args['class'] ) ? $args['class'] : 'button' ) . ' product-card__button';
    return $args;
}

// Libellé du lien vers la fiche des produits à options.
add_filter( 'woocommerce_product_add_to_cart_text', 'thaimoon_product_card_button_text', 10, 2 );
function thaimoon_product_card_button_text( $text, $product ) {
    if ( $product->is_type( 'variable' ) && $product->is_purchasable() ) {
        return __( 'Voir le produit', 'restaurant' );
    }
    return $text;
}

// Organisation de la boutique par catégories.
require_once __DIR__ . '/shop-catalogue.php';

// Stock, variations et quantité (US3.2).
require_once __DIR__ . '/product-purchase.php';

// Option de couverts en boutons radio, sans multiplier les variations.
require_once __DIR__ . '/product-cutlery.php';

// Allergènes et composition du groupe de champs du produit.
require_once __DIR__ . '/product-information.php';

// Panier et validation de commande, maquette V1.2.
require_once __DIR__ . '/cart-checkout.php';

// Connexion, inscription et espace client, maquette V1.2.
require_once __DIR__ . '/account.php';

// Suggestions complémentaires sur la fiche produit.
require_once __DIR__ . '/related-products.php';

// Avis produit, maquette Figma V1.5.
require_once __DIR__ . '/product-reviews.php';

// Frais de préparation administrables depuis la page SCF Tarification (itération 15).
require_once __DIR__ . '/preparation-fees.php';
