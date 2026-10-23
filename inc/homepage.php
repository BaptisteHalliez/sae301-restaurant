<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', 'thaimoon_home_body_class' );
function thaimoon_home_body_class( $classes ) {
    if ( is_front_page() ) {
        $classes[] = 'home-page';
    }
    return $classes;
}

add_action( 'wp', 'thaimoon_home_setup' );
function thaimoon_home_setup() {
    if ( is_front_page() ) {
        remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
    }
}

function thaimoon_home_latest_products() {
    if ( ! function_exists( 'WC' ) || ! function_exists( 'wc_get_product' ) ) {
        return array();
    }

    $query = new WP_Query( array(
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 3,
        'has_password'        => false,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'orderby'             => array( 'date' => 'DESC', 'ID' => 'DESC' ),
        'tax_query'           => WC()->query->get_tax_query(),
        'meta_query'          => WC()->query->get_meta_query(),
    ) );

    $products = array();
    foreach ( $query->posts as $product_post ) {
        $product = wc_get_product( $product_post->ID );
        if ( $product instanceof WC_Product && $product->is_visible() ) {
            $products[] = $product;
        }
    }
    return $products;
}

function thaimoon_home_image_srcset( $image ) {
    static $srcsets = array();
    if ( isset( $srcsets[ $image ] ) ) {
        return $srcsets[ $image ];
    }
    $base = pathinfo( $image, PATHINFO_FILENAME );
    $candidates = array();
    foreach ( array( 128, 256 ) as $width ) {
        $file = $base . '-' . $width . '.webp';
        if ( ! is_readable( get_stylesheet_directory() . '/assets/images/' . $file ) ) {
            $srcsets[ $image ] = '';
            return '';
        }
        $candidates[] = thaimoon_image_url( $file ) . ' ' . $width . 'w';
    }
    $candidates[] = thaimoon_image_url( $image ) . ' 400w';
    $srcsets[ $image ] = implode( ', ', $candidates );
    return $srcsets[ $image ];
}


add_filter( 'woocommerce_loop_add_to_cart_link', 'thaimoon_home_product_button', 30, 2 );
function thaimoon_home_product_button( $html, $product ) {
    if ( 'thaimoon-home-latest' !== wc_get_loop_prop( 'name' ) ) {
        return $html;
    }
    return '<a class="button product-card__button" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html__( 'Voir le produit', 'thaimoon-restaurant' ) . '</a>';
}

function thaimoon_home_render_products( $products ) {
    $previous_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
    $previous_loop    = isset( $GLOBALS['woocommerce_loop'] ) ? $GLOBALS['woocommerce_loop'] : null;

    try {
        wc_set_loop_prop( 'name', 'thaimoon-home-latest' );
        wc_set_loop_prop( 'columns', 3 );
        wc_set_loop_prop( 'loop', 0 );
        wc_set_loop_prop( 'total', count( $products ) );
        woocommerce_product_loop_start();
        foreach ( $products as $home_product ) {
            $GLOBALS['post'] = get_post( $home_product->get_id() );
            setup_postdata( $GLOBALS['post'] );
            $GLOBALS['product'] = $home_product;
            wc_get_template_part( 'content', 'product' );
        }
        woocommerce_product_loop_end();
    } finally {
        wp_reset_postdata();
        $GLOBALS['product'] = $previous_product;
        if ( null === $previous_loop ) {
            unset( $GLOBALS['woocommerce_loop'] );
        } else {
            $GLOBALS['woocommerce_loop'] = $previous_loop;
        }
    }
}
