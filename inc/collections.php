<?php
defined( 'ABSPATH' ) || exit;

function thaimoon_is_collections_page() {
    return is_page( 'nos-collections' ) || is_page_template( 'page-nos-collections.php' );
}

add_filter( 'body_class', 'thaimoon_collections_body_class' );
function thaimoon_collections_body_class( $classes ) {
    if ( thaimoon_is_collections_page() ) {
        $classes[] = 'collections-page';
    }
    return $classes;
}

function thaimoon_collections_terms() {
    static $terms;
    if ( null !== $terms ) {
        return $terms;
    }
    $terms = array();
    $slugs = array( 'classiques', 'signatures', 'selection-du-moment' );
    $found = get_terms( array( 'taxonomy' => 'collection', 'slug' => $slugs, 'hide_empty' => false ) );
    if ( ! is_wp_error( $found ) ) {
        foreach ( $slugs as $slug ) {
            foreach ( $found as $term ) {
                if ( $slug === $term->slug ) {
                    $terms[ $term->term_id ] = $term;
                    break;
                }
            }
        }
    }
    return $terms;
}

function thaimoon_collections_query() {
    static $query;
    if ( $query instanceof WP_Query ) {
        return $query;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Filtres publics en lecture seule.
    $input = wp_unslash( $_GET );
    // Une recherche de produits ne doit pas remplacer la requête principale de la page.
    $input['s'] = isset( $input['catalogue_search'] ) ? $input['catalogue_search'] : '';
    $state = thaimoon_parse_catalogue_filters( $input );
    $terms = thaimoon_collections_terms();
    $tax_query = array( array( 'taxonomy' => 'collection', 'field' => 'term_id', 'terms' => array_keys( $terms ), 'include_children' => false ) );
    $query = new WP_Query( array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'has_password' => false,
        'posts_per_page' => -1,
        'no_found_rows' => true,
        's' => $state['search'],
        'post__in' => $terms ? array() : array( 0 ),
        'tax_query' => WC()->query->get_tax_query( $tax_query ),
        'meta_query' => WC()->query->get_meta_query(),
        '_thaimoon_collections_query' => true,
        '_thaimoon_catalogue_filters' => $state,
    ) );
    return $query;
}

function thaimoon_get_collections_catalogue() {
    $sections = array();
    foreach ( thaimoon_collections_terms() as $id => $term ) {
        $sections[ $id ] = array(
            'id' => 'collection-' . $term->slug,
            'title' => $term->name,
            'description' => $term->description,
            'products' => array(),
        );
    }
    $query = thaimoon_collections_query();
    $products = array();
    foreach ( $query->posts as $position => $product_post ) {
        $product = wc_get_product( $product_post->ID );
        if ( $product && $product->is_visible() ) {
            $products[] = array(
                'post' => $product_post,
                'price' => '' === $product->get_price() ? null : (float) wc_get_price_to_display( $product ),
                'position' => $position,
            );
        }
    }
    usort( $products, 'thaimoon_compare_catalogue_products' );
    foreach ( $products as $entry ) {
        $terms = get_the_terms( $entry['post'], 'collection' );
        if ( ! is_array( $terms ) ) {
            continue;
        }
        foreach ( $terms as $term ) {
            if ( isset( $sections[ $term->term_id ] ) ) {
                $sections[ $term->term_id ]['products'][] = $entry['post'];
            }
        }
    }
    $state = $query->get( '_thaimoon_catalogue_filters' );
    if ( $state['errors'] || thaimoon_catalogue_active_filters( $state ) ) {
        $sections = array_filter( $sections, static function ( $section ) {
            return ! empty( $section['products'] );
        } );
    }
    return $sections;
}
