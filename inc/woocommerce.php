<?php
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'thaimoon_product_cards_setup', 30 );
function thaimoon_product_cards_setup() {
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
    remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
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
    
    $args['class'] = ( isset( $args['class'] ) ? $args['class'] : 'button' ) . ' product-card__button';
    return $args;
}


add_filter( 'woocommerce_product_add_to_cart_text', 'thaimoon_product_card_button_text', 10, 2 );
function thaimoon_product_card_button_text( $text, $product ) {
    if ( $product->is_type( 'variable' ) && $product->is_purchasable() ) {
        return __( 'Voir le produit', 'restaurant' );
    }
    return $text;
}


require_once __DIR__ . '/shop-catalogue.php';


require_once __DIR__ . '/catalogue-filters.php';
require_once __DIR__ . '/collections.php';


require_once __DIR__ . '/product-purchase.php';


require_once __DIR__ . '/product-cutlery.php';


require_once __DIR__ . '/product-information.php';


require_once __DIR__ . '/cart-checkout.php';


require_once __DIR__ . '/account.php';


require_once __DIR__ . '/related-products.php';


require_once __DIR__ . '/product-reviews.php';


require_once __DIR__ . '/preparation-fees.php';


require_once __DIR__ . '/weight-shipping.php';


require_once __DIR__ . '/fulfilment-slots.php';
