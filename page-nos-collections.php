<?php
/**
 * Template Name: Nos collections
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'thaimoon_get_collections_catalogue' ) ) {
    require get_template_directory() . '/page.php';
    return;
}

require __DIR__ . '/woocommerce/shop-catalogue.php';
