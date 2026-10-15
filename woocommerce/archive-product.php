<?php
/**
 * Boutique et catégories harmonisées ; recherches et résultats filtrés natifs.
 * @package WooCommerce\Templates
 * @version 8.6.0
 */
defined( 'ABSPATH' ) || exit;

if ( thaimoon_is_shop_catalogue() ) {
    require __DIR__ . '/shop-catalogue.php';
    return;
}
require WC()->plugin_path() . '/templates/archive-product.php';
