<?php
/** Suggestions complémentaires : entrée, plat et dessert sur les fiches produit. */
defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_product_related_products_heading', 'thaimoon_related_products_heading' );
function thaimoon_related_products_heading( $heading ) {
    return __( 'Vous aimerez aussi', 'thaimoon-restaurant' );
}

add_filter( 'woocommerce_output_related_products_args', 'thaimoon_related_products_args', 20 );
function thaimoon_related_products_args( $args ) {
    $args['posts_per_page'] = 3;
    $args['columns'] = 3;
    return $args;
}

function thaimoon_related_product_categories() {
    $aliases = array( 'entrees' => array( 'entrees' ), 'plats' => array( 'plats', 'plats-principaux' ), 'desserts' => array( 'desserts' ) );
    $groups = array_fill_keys( array_keys( $aliases ), array() );
    foreach ( $aliases as $group => $slugs ) {
        foreach ( $slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }
            $groups[ $group ][] = (int) $term->term_id;
            $children = get_term_children( $term->term_id, 'product_cat' );
            if ( ! is_wp_error( $children ) ) {
                $groups[ $group ] = array_merge( $groups[ $group ], array_map( 'absint', $children ) );
            }
        }
        $groups[ $group ] = array_values( array_unique( $groups[ $group ] ) );
    }
    return $groups;
}

function thaimoon_related_product_is_visible( $id, $excluded ) {
    if ( ! $id || in_array( $id, $excluded, true ) || 'publish' !== get_post_status( $id ) || post_password_required( $id ) ) {
        return false;
    }
    $product = wc_get_product( $id );
    return $product && $product->is_visible() && ( 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) || $product->is_in_stock() );
}

add_filter( 'woocommerce_related_products', 'thaimoon_complementary_related_products', 20, 3 );
function thaimoon_complementary_related_products( $related_ids, $product_id, $args ) {
    $limit = isset( $args['limit'] ) ? (int) $args['limit'] : 3;
    if ( $limit < 2 ) {
        return $related_ids;
    }
    $groups = thaimoon_related_product_categories();
    $current_terms = wc_get_product_term_ids( $product_id, 'product_cat' );
    $current_group = '';
    foreach ( $groups as $group => $terms ) {
        if ( array_intersect( $current_terms, $terms ) ) {
            $current_group = $group;
            break;
        }
    }
    if ( '' === $current_group ) {
        return $related_ids;
    }

    $required = array_values( array_diff( array_keys( $groups ), array( $current_group ) ) );
    $other_terms = array_values( array_unique( array_merge( $groups[ $required[0] ], $groups[ $required[1] ] ) ) );
    if ( ! $other_terms ) {
        return $related_ids;
    }
    $excluded = array_unique( array_map( 'absint', array_merge( array( $product_id ), isset( $args['excluded_ids'] ) ? $args['excluded_ids'] : array() ) ) );
    $tax_query = WC()->query->get_tax_query();
    $tax_query[] = array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $other_terms, 'include_children' => true );
    $query = new WP_Query( array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'has_password' => false,
        'fields' => 'ids',
        'posts_per_page' => -1,
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'post__not_in' => $excluded,
        'orderby' => 'rand',
        'tax_query' => $tax_query,
        'meta_query' => WC()->query->get_meta_query(),
    ) );
    $pools = array( array(), array() );
    $candidates = array();
    foreach ( $query->posts as $candidate_id ) {
        $candidate_id = absint( $candidate_id );
        if ( ! thaimoon_related_product_is_visible( $candidate_id, $excluded ) ) {
            continue;
        }
        $candidates[] = $candidate_id;
        $terms = wc_get_product_term_ids( $candidate_id, 'product_cat' );
        foreach ( $required as $index => $group ) {
            if ( array_intersect( $terms, $groups[ $group ] ) ) {
                $pools[ $index ][] = $candidate_id;
            }
        }
    }

    $selected = array();
    // Choisir deux produits distincts, même si certains appartiennent aux deux catégories.
    foreach ( $pools[0] as $first ) {
        foreach ( $pools[1] as $second ) {
            if ( $first !== $second ) {
                $selected = array( $first, $second );
                break 2;
            }
        }
    }
    if ( ! $selected ) {
        $selected = array_slice( array_values( array_unique( array_merge( $pools[0], $pools[1] ) ) ), 0, 2 );
    }
    foreach ( array_merge( $related_ids, $candidates ) as $id ) {
        if ( count( $selected ) >= $limit ) {
            break;
        }
        $id = absint( $id );
        if ( ! in_array( $id, $selected, true ) && thaimoon_related_product_is_visible( $id, $excluded ) ) {
            $selected[] = $id;
        }
    }
    // Le mélange et le rendu des cartes restent assurés par WooCommerce.
    return $selected;
}
