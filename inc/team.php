<?php
/** Équipe et fiches membres — Figma V1.4, contenu du type SCF « membre ». */
defined( 'ABSPATH' ) || exit;

function thaimoon_is_team_page() {
    return is_post_type_archive( 'membre' ) || is_singular( 'membre' );
}

add_filter( 'body_class', 'thaimoon_team_body_class' );
function thaimoon_team_body_class( $classes ) {
    if ( thaimoon_is_team_page() ) {
        $classes[] = 'team-page';
    }
    return $classes;
}

add_action( 'wp', 'thaimoon_team_layout' );
function thaimoon_team_layout() {
    if ( thaimoon_is_team_page() ) {
        // La maquette commence directement par le titre de l'équipe ou du membre.
        remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
    }
}

add_action( 'pre_get_posts', 'thaimoon_team_archive_order' );
function thaimoon_team_archive_order( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'membre' ) ) {
        $query->set( 'orderby', array( 'date' => 'ASC', 'ID' => 'ASC' ) );
        $query->set( 'has_password', false );
    }
}

function thaimoon_member_text( $id, $key ) {
    $value = get_post_meta( $id, $key, true );
    return is_string( $value ) ? trim( $value ) : '';
}

function thaimoon_member_presentation( $id ) {
    $text = thaimoon_member_text( $id, 'presentation' );
    if ( '' === $text ) {
        $text = get_post_field( 'post_content', $id );
    }
    return trim( wp_strip_all_tags( strip_shortcodes( $text ) ) );
}

function thaimoon_member_card_role( $id ) {
    // Dans les petites cartes, le nom du restaurant est déjà donné par le contexte.
    return preg_replace( '/\s+de Thaï Moon$/u', '', thaimoon_member_text( $id, 'poste' ) );
}

function thaimoon_member_products( $id ) {
    $products = array();
    if ( ! function_exists( 'wc_get_product' ) ) {
        return $products;
    }
    // Lire les IDs enregistrés par SCF : indépendant du format de retour du champ.
    $ids = array_unique( array_map( 'absint', (array) get_post_meta( $id, 'recommandations', true ) ) );
    foreach ( $ids as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( $product_id && $product && 'publish' === get_post_status( $product_id ) && ! post_password_required( $product_id ) && $product->is_visible() ) {
            $products[] = $product;
        }
    }
    return $products;
}

function thaimoon_member_recommendation_intro( $id ) {
    $role = thaimoon_member_text( $id, 'poste' );
    $intros = array(
        'Gérant de Thaï Moon' => 'Découvrez quelles sont les recommandations du patron',
        'Chef Cuisinier' => 'Découvrez les plats que le chef adore réaliser',
        'Responsable de Salle' => 'Découvrez ce que notre responsable a à vous servir',
    );
    return isset( $intros[ $role ] ) ? $intros[ $role ] : sprintf( 'Découvrez les recommandations de %s', get_the_title( $id ) );
}

function thaimoon_member_product_button( $html, $product ) {
    return '<a class="button product-card__button" href="' . esc_url( $product->get_permalink() ) . '">Voir le produit</a>';
}

function thaimoon_member_render_products( $products ) {
    // Réutiliser la vraie carte du thème et les prix WooCommerce, sans panier fictif.
    $previous_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
    $previous_loop = isset( $GLOBALS['woocommerce_loop'] ) ? $GLOBALS['woocommerce_loop'] : null;
    add_filter( 'woocommerce_loop_add_to_cart_link', 'thaimoon_member_product_button', 20, 2 );
    try {
        foreach ( $products as $recommendation ) {
            $GLOBALS['post'] = get_post( $recommendation->get_id() );
            setup_postdata( $GLOBALS['post'] );
            $GLOBALS['product'] = $recommendation;
            wc_get_template_part( 'content', 'product' );
        }
    } finally {
        remove_filter( 'woocommerce_loop_add_to_cart_link', 'thaimoon_member_product_button', 20 );
        wp_reset_postdata();
        $GLOBALS['product'] = $previous_product;
        if ( null === $previous_loop ) {
            unset( $GLOBALS['woocommerce_loop'] );
        } else {
            $GLOBALS['woocommerce_loop'] = $previous_loop;
        }
    }
}
