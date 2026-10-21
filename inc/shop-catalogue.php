<?php
/** Boutique, catégories, collections et recherches avec les cartes Figma. */
defined( 'ABSPATH' ) || exit;

function thaimoon_is_shop_catalogue() {
    return function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_tax( 'collection' ) || ( is_search() && 'product' === get_query_var( 'post_type' ) ) );
}

add_filter( 'body_class', 'thaimoon_shop_catalogue_class' );
function thaimoon_shop_catalogue_class( $classes ) {
    if ( thaimoon_is_shop_catalogue() ) {
        $classes[] = 'shop-catalogue';
    }
    return $classes;
}

add_filter( 'woocommerce_get_breadcrumb', 'thaimoon_shop_catalogue_breadcrumb' );
function thaimoon_shop_catalogue_breadcrumb( $crumbs ) {
    if ( thaimoon_is_shop_catalogue() && is_shop() && count( $crumbs ) > 1 ) {
        $crumbs[ count( $crumbs ) - 1 ][0] = 'Click & Collect';
    }
    return $crumbs;
}

// La boutique et les catégories partagent la présentation, en conservant les notices.
add_action( 'wp', 'thaimoon_shop_catalogue_controls' );
function thaimoon_shop_catalogue_controls() {
    if ( ! thaimoon_is_shop_catalogue() ) {
        return;
    }
    $callbacks = array(
        'storefront_sorting_wrapper' => 9,
        'storefront_sorting_wrapper_close' => 31,
        'woocommerce_catalog_ordering' => 10,
        'woocommerce_result_count' => 20,
        'storefront_woocommerce_pagination' => 30,
        'woocommerce_pagination' => 30,
    );
    foreach ( array( 'woocommerce_before_shop_loop', 'woocommerce_after_shop_loop' ) as $hook ) {
        foreach ( $callbacks as $callback => $priority ) {
            remove_action( $hook, $callback, $priority );
        }
        remove_action( $hook, 'woocommerce_catalog_ordering', 30 );
        remove_action( $hook, 'woocommerce_pagination', 10 );
    }
    remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories', 10 );
}

function thaimoon_get_shop_catalogue() {
    global $wp_query;
    $defaults = array(
        'entrees' => array( 'title' => '', 'description' => 'Démarrez votre repas avec nos délicieuses entrées traditionnelles' ),
        'plats' => array( 'title' => 'Plats Principaux', 'description' => 'Nos plats traditionnels thaïlandais préparés avec des ingrédients frais' ),
        'plats-principaux' => array( 'title' => '', 'description' => 'Nos plats traditionnels thaïlandais préparés avec des ingrédients frais' ),
        'desserts' => array( 'title' => '', 'description' => 'Terminez votre repas en douceur avec nos desserts traditionnels thaïlandais' ),
    );
    $current_category = is_product_category() ? get_queried_object() : null;
    $collection = is_tax( 'collection' ) ? get_queried_object() : null;
    $categories = $current_category instanceof WP_Term
        ? array( $current_category )
        : get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC' ) );
    if ( is_wp_error( $categories ) ) {
        $categories = array();
    }
    $groups = array();
    // L'ordre Figma d'abord, puis toute nouvelle catégorie créée dans WooCommerce.
    foreach ( $defaults as $slug => $settings ) {
        foreach ( $categories as $category ) {
            if ( $slug === $category->slug ) {
                $groups[ $category->term_id ] = $category;
            }
        }
    }
    foreach ( $categories as $category ) {
        $groups[ $category->term_id ] = $category;
    }
    $sections = array();
    if ( $collection instanceof WP_Term ) {
        $sections['collection'] = array( 'id' => 'collection-' . $collection->slug, 'title' => $collection->name, 'description' => $collection->description, 'products' => array() );
    }
    foreach ( $groups as $id => $category ) {
        if ( $collection instanceof WP_Term ) {
            break;
        }
        $settings = isset( $defaults[ $category->slug ] ) ? $defaults[ $category->slug ] : array( 'title' => '', 'description' => '' );
        $sections[ $id ] = array(
            'id' => 'categorie-' . $category->slug,
            'title' => $settings['title'] ? $settings['title'] : $category->name,
            'description' => trim( $category->description ) ? $category->description : $settings['description'],
            'products' => array(),
        );
    }
    // Les filtres et la recherche agissent sur cette même requête principale.
    $sorted_products = array();
    foreach ( $wp_query->posts as $position => $product_post ) {
        $catalogue_product = wc_get_product( $product_post->ID );
        if ( ! $catalogue_product || ! $catalogue_product->is_visible() ) {
            continue;
        }
        $sorted_products[] = array(
            'post' => $product_post,
            'category_ids' => $catalogue_product->get_category_ids(),
            'price' => '' === $catalogue_product->get_price() ? null : (float) wc_get_price_to_display( $catalogue_product ),
            'position' => $position,
        );
    }
    usort( $sorted_products, 'thaimoon_compare_catalogue_products' );
    foreach ( $sorted_products as $entry ) {
        if ( $collection instanceof WP_Term ) {
            $sections['collection']['products'][] = $entry['post'];
            continue;
        }
        $section_id = $current_category instanceof WP_Term ? $current_category->term_id : 'other';
        foreach ( $groups as $id => $category ) {
            if ( $current_category instanceof WP_Term || in_array( $id, $entry['category_ids'], true ) ) {
                $section_id = $id;
                break; // Un produit de plusieurs catégories reste affiché une seule fois.
            }
        }
        if ( ! isset( $sections[ $section_id ] ) ) {
            $sections[ $section_id ] = array( 'id' => 'categorie-autres', 'title' => 'Autres produits', 'description' => '', 'products' => array() );
        }
        $sections[ $section_id ]['products'][] = $entry['post'];
    }
    return array_filter( $sections, static function ( $section ) {
        return ! empty( $section['products'] );
    } );
}

/** Prix décroissants ; à prix égal, garder l'ordre alphabétique de la requête WordPress. */
function thaimoon_compare_catalogue_products( $a, $b ) {
    // Un prix vide reste en fin de liste ; un produit gratuit conserve bien son prix zéro.
    if ( null === $a['price'] && null !== $b['price'] ) {
        return 1;
    }
    if ( null === $b['price'] && null !== $a['price'] ) {
        return -1;
    }
    $price_order = $b['price'] <=> $a['price'];
    return 0 !== $price_order ? $price_order : $a['position'] <=> $b['position'];
}
