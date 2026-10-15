<?php
/**
 * Carte produit du catalogue — Thaï Moon, maquette Figma V1.0.
 * Les hooks natifs conservent le lien produit, les promotions et l'ajout au panier.
 *
 * @package WooCommerce\Templates
 * @version 9.4.0
 */
defined( 'ABSPATH' ) || exit;

global $product;
if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
    return;
}

$description = $product->get_short_description();
if ( '' === trim( wp_strip_all_tags( $description ) ) ) {
    $description = $product->get_description();
}
// Du texte simple : pas de blocs, d'images ni de shortcodes dans une petite carte.
$description = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $description ) ), 24, '…' );
?>
<li <?php wc_product_class( 'product-card', $product ); ?>>
    <?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
    <div class="product-card__information">
        <div class="product-card__visual">
            <?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>
            <?php if ( '' !== $product->get_price_html() ) : ?>
                <div class="product-card__price"><?php woocommerce_template_loop_price(); ?></div>
            <?php endif; ?>
        </div>
        <div class="product-card__content">
            <?php do_action( 'woocommerce_shop_loop_item_title' ); ?>
            <?php if ( '' !== $description ) : ?>
                <p class="product-card__description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
            <?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>
        </div>
    </div>
    <?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
</li>
