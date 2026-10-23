<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'thaimoon_home_asset_loading', 100 );
function thaimoon_home_asset_loading() {
    if ( ! is_front_page() ) {
        return;
    }

    // Ces contrôleurs ciblent les menus et widgets Storefront remplacés par le thème.
    foreach ( array( 'storefront-navigation', 'storefront-header-cart', 'storefront-handheld-footer-bar', 'storefront-woocommerce-brands', 'wc-add-to-cart' ) as $handle ) {
        wp_dequeue_script( $handle );
    }

    $post = get_queried_object();
    if ( ! is_active_widget( false, false, 'woocommerce_widget_cart', true ) && ! ( $post instanceof WP_Post && has_block( 'woocommerce/mini-cart', $post ) ) ) {
        wp_dequeue_script( 'wc-cart-fragments' );
    }

    // WordPress conserve l’ordre des dépendances et peut refuser defer si nécessaire.
    foreach ( array( 'jquery-core', 'jquery-migrate', 'sourcebuster-js', 'wc-order-attribution' ) as $handle ) {
        wp_script_add_data( $handle, 'strategy', 'defer' );
    }
}

add_filter( 'wp_resource_hints', 'thaimoon_font_connections', 10, 2 );
function thaimoon_font_connections( $hints, $relation ) {
    if ( 'preconnect' === $relation && ! is_admin() ) {
        $hints[] = 'https://fonts.googleapis.com';
        $hints[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
    }
    return $hints;
}

add_filter( 'wp_preload_resources', 'thaimoon_home_preloads' );
function thaimoon_home_preloads( $resources ) {
    if ( ! is_front_page() ) {
        return $resources;
    }

    // Anticiper les @import sans modifier les fichiers CSS ni leurs couches de cascade.
    $styles = wp_styles();
    foreach ( array( 'storefront-style', 'storefront-woocommerce-style', 'storefront-icons' ) as $handle ) {
        if ( ! wp_style_is( $handle, 'enqueued' ) || empty( $styles->registered[ $handle ]->src ) ) {
            continue;
        }
        $style = $styles->registered[ $handle ];
        $url = $style->src;
        if ( $style->ver ) {
            $url = add_query_arg( 'ver', $style->ver, $url );
        }
        $resources[] = array( 'href' => $url, 'as' => 'style' );
    }
    $resources[] = array( 'href' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap', 'as' => 'style' );
    return $resources;
}

add_filter( 'wp_get_attachment_image_attributes', 'thaimoon_home_thumbnail_loading', 10, 3 );
function thaimoon_home_thumbnail_loading( $attributes, $attachment, $size ) {
    if ( is_front_page() && function_exists( 'wc_get_loop_prop' ) && 'thaimoon-home-latest' === wc_get_loop_prop( 'name' ) ) {
        $attributes['sizes'] = '150px';
    }
    return $attributes;
}
