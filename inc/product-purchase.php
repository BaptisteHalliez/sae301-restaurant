<?php
/** US3.2 : disponibilité, portions et quantité — composants Figma V1.2. */
defined( 'ABSPATH' ) || exit;

function thaimoon_low_stock_quantity( $product ) {
    if ( ! $product->is_in_stock() || $product->is_on_backorder( 1 ) ) {
        return 0;
    }
    $stock_product = $product;
    if ( $product->is_type( 'variation' ) && $product->get_stock_managed_by_id() !== $product->get_id() ) {
        $stock_product = wc_get_product( $product->get_stock_managed_by_id() );
    }
    if ( ! $stock_product || ! $stock_product->managing_stock() ) {
        return 0;
    }
    $quantity = $stock_product->get_stock_quantity();
    return null !== $quantity && $quantity >= 1 && $quantity <= 3 ? $quantity : 0;
}

function thaimoon_stock_badge( $product, $on_card = false ) {
    $classes = 'stock-badge' . ( $on_card ? ' stock-badge--card' : '' );
    if ( ! $product->is_in_stock() ) {
        return '<span class="' . esc_attr( $classes . ' stock-badge--empty' ) . '" role="status"><img class="stock-badge__icon" src="' . esc_url( get_stylesheet_directory_uri() . '/assets/images/stock-empty.svg' ) . '" width="16" height="16" alt="">Épuisé</span>';
    }
    $quantity = thaimoon_low_stock_quantity( $product );
    if ( ! $quantity ) {
        return '';
    }
    return '<span class="' . esc_attr( $classes ) . '" role="status">Plus que ' . esc_html( wc_format_localized_decimal( $quantity ) ) . '</span>';
}

add_filter( 'woocommerce_get_stock_html', 'thaimoon_purchase_stock_html', 20, 2 );
function thaimoon_purchase_stock_html( $html, $product ) {
    if ( ! $product->is_type( array( 'simple', 'variable', 'variation' ) ) ) {
        return $html;
    }
    if ( ! $product->is_in_stock() ) {
        return thaimoon_stock_badge( $product );
    }
    if ( $product->is_on_backorder( 1 ) ) {
        return '<p class="purchase__notice">Disponible sur commande</p>';
    }
    return thaimoon_stock_badge( $product );
}

add_action( 'woocommerce_before_shop_loop_item_title', 'thaimoon_product_card_stock', 20 );
function thaimoon_product_card_stock() {
    global $product;
    echo thaimoon_stock_badge( $product, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML construit et échappé ci-dessus.
}

add_filter( 'woocommerce_loop_add_to_cart_link', 'thaimoon_unavailable_card_button', 20, 2 );
function thaimoon_unavailable_card_button( $html, $product ) {
    if ( ! $product->is_in_stock() ) {
        return '<button type="button" class="product-card__button purchase__unavailable" disabled>Indisponible</button>';
    }
    return $html;
}

// Les données sont aussi utilisées lorsque WooCommerce recherche une variation par AJAX.
add_filter( 'woocommerce_available_variation', 'thaimoon_purchase_variation_data', 20, 3 );
function thaimoon_purchase_variation_data( $data, $parent, $variation ) {
    $data['thaimoon_price_html'] = $variation->get_price_html();
    return $data;
}

add_action( 'wp', 'thaimoon_purchase_setup' );
function thaimoon_purchase_setup() {
    if ( ! is_product() ) {
        return;
    }
    $product = wc_get_product( get_queried_object_id() );
    if ( ! $product || ! $product->is_type( array( 'simple', 'variable' ) ) ) {
        return;
    }
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
    add_action( 'woocommerce_before_add_to_cart_quantity', 'thaimoon_purchase_price' );
    add_action( 'woocommerce_single_product_summary', 'thaimoon_unavailable_simple_purchase', 31 );
    add_action( 'woocommerce_after_add_to_cart_form', 'thaimoon_unavailable_variable_purchase' );
}

function thaimoon_purchase_price() {
    global $product;
    ?>
    <div class="purchase__price" aria-live="polite" aria-atomic="true">
        <span class="purchase__amount"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
        <span class="purchase__unit">Par portion</span>
    </div>
    <?php
}

function thaimoon_unavailable_purchase() {
    ?>
    <div class="purchase__empty">
        <?php thaimoon_purchase_price(); ?>
        <button type="button" class="purchase__submit purchase__unavailable" disabled>Indisponible</button>
    </div>
    <?php
}

function thaimoon_unavailable_simple_purchase() {
    global $product;
    if ( $product->is_type( 'simple' ) && ( ! $product->is_in_stock() || ! $product->is_purchasable() ) ) {
        thaimoon_unavailable_purchase();
    }
}

function thaimoon_unavailable_variable_purchase() {
    global $product;
    // Si WooCommerce ne peut présenter aucune variation, il n'émet pas de bouton natif.
    if ( $product->is_type( 'variable' ) && empty( $product->get_available_variations() ) ) {
        echo thaimoon_stock_badge( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML échappé par le composant.
        thaimoon_unavailable_purchase();
    }
}

add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'thaimoon_portion_select' );
function thaimoon_portion_select( $html ) {
    return '<span class="portion-select">' . $html . '</span>';
}

add_action( 'wp_enqueue_scripts', 'thaimoon_purchase_assets', 45 );
function thaimoon_purchase_assets() {
    if ( ! is_product() ) {
        return;
    }
    $product = wc_get_product( get_queried_object_id() );
    if ( ! $product || ! $product->is_type( array( 'simple', 'variable' ) ) ) {
        return;
    }
    $dependencies = $product->is_type( 'variable' ) ? array( 'jquery', 'wc-add-to-cart-variation' ) : array( 'jquery' );
    wp_enqueue_script( 'thaimoon-product-purchase', get_stylesheet_directory_uri() . '/assets/js/product-purchase.js', $dependencies, (string) filemtime( get_stylesheet_directory() . '/assets/js/product-purchase.js' ), true );
}
