<?php
defined( 'ABSPATH' ) || exit;

function thaimoon_parse_catalogue_filters( $input ) {
    $state = array( 'search' => '', 'min_price' => null, 'max_price' => null, 'category' => '', 'portion' => '', 'quantity' => '', 'stock' => '', 'errors' => array() );
    foreach ( array( 's' => 'search', 'filter_product_cat' => 'category', 'filter_portion' => 'portion', 'filter_quantite' => 'quantity', 'filter_stock_status' => 'stock' ) as $key => $name ) {
        if ( ! isset( $input[ $key ] ) || '' === $input[ $key ] ) {
            continue;
        }
        if ( ! is_string( $input[ $key ] ) ) {
            $state['errors'][] = __( 'Un filtre est invalide. Sélectionnez à nouveau vos critères.', 'restaurant' );
            continue;
        }
        $state[ $name ] = 'search' === $name ? sanitize_text_field( $input[ $key ] ) : sanitize_title( $input[ $key ] );
    }
    if ( '' !== $state['stock'] && ! in_array( $state['stock'], array( 'instock', 'outofstock', 'onbackorder' ), true ) ) {
        $state['errors'][] = __( 'Choisissez une disponibilité proposée dans la liste.', 'restaurant' );
        $state['stock'] = '';
    }
    foreach ( array( 'min_price', 'max_price' ) as $key ) {
        if ( ! isset( $input[ $key ] ) || ( is_string( $input[ $key ] ) && '' === trim( $input[ $key ] ) ) ) {
            continue;
        }
        $raw = is_string( $input[ $key ] ) ? trim( $input[ $key ] ) : '';
        if ( ! preg_match( '/^\d{1,9}(?:[.,]\d{1,6})?$/D', $raw ) ) {
            $state['errors'][] = __( 'Les prix doivent être des nombres positifs ou nuls.', 'restaurant' );
            continue;
        }
        $state[ $key ] = wc_format_decimal( str_replace( ',', '.', $raw ), wc_get_price_decimals() );
    }
    if ( null !== $state['min_price'] && null !== $state['max_price'] && (float) $state['min_price'] > (float) $state['max_price'] ) {
        $state['errors'][] = __( 'Le prix minimum doit être inférieur ou égal au prix maximum.', 'restaurant' );
    }
    $state['errors'] = array_values( array_unique( $state['errors'] ) );
    return $state;
}

function thaimoon_is_catalogue_query( $query ) {
    return ! is_admin() && $query->is_main_query() && (
        $query->is_post_type_archive( 'product' ) || $query->is_page( wc_get_page_id( 'shop' ) ) || $query->is_tax( array( 'product_cat', 'collection' ) ) ||
        ( $query->is_search() && 'product' === $query->get( 'post_type' ) )
    );
}

add_action( 'pre_get_posts', 'thaimoon_prepare_catalogue_filters', 5 );
function thaimoon_prepare_catalogue_filters( $query ) {
    if ( ! thaimoon_is_catalogue_query( $query ) ) {
        return;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Recherche publique, sans écriture.
    $state = thaimoon_parse_catalogue_filters( wp_unslash( $_GET ) );
    $query->set( '_thaimoon_catalogue_filters', $state );
    foreach ( array( 'min_price', 'max_price', 'filter_portion' => 'portion', 'filter_quantite' => 'quantity', 'filter_stock_status' => 'stock' ) as $key => $name ) {
        $parameter = is_int( $key ) ? $name : $key;
        if ( null === $state[ $name ] || '' === $state[ $name ] ) {
            unset( $_GET[ $parameter ] );
            $query->set( $parameter, '' );
        } else {
            $_GET[ $parameter ] = $state[ $name ];
            $query->set( $parameter, $state[ $name ] );
        }
    }
    if ( $query->is_search() ) {
        $query->set( 's', $state['search'] );
    }
}

add_action( 'pre_get_posts', 'thaimoon_filter_catalogue_query', 30 );
function thaimoon_filter_catalogue_query( $query ) {
    $state = $query->get( '_thaimoon_catalogue_filters' );
    if ( ! is_array( $state ) || ! thaimoon_is_catalogue_query( $query ) ) {
        return;
    }
    $query->set( 'posts_per_page', -1 );
    $query->set( 'no_found_rows', true );
    $query->set( 'has_password', false );
    $query->set( 'orderby', array( 'title' => 'ASC', 'ID' => 'ASC' ) );
    $query->set( 'order', 'ASC' );

    $tax_query = (array) $query->get( 'tax_query' );
    foreach ( array( 'category' => 'product_cat', 'portion' => 'pa_portion', 'quantity' => 'pa_quantite' ) as $name => $taxonomy ) {
        if ( '' === $state[ $name ] ) {
            continue;
        }
        $term = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $state[ $name ], $taxonomy ) : false;
        if ( ! $term ) {
            $state['errors'][] = __( 'Un critère sélectionné n’existe plus. Effacez les filtres pour recommencer.', 'restaurant' );
            continue;
        }
        $clause = array( 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => array( $term->term_id ), 'include_children' => 'category' === $name );
        $tax_query = empty( $tax_query ) ? array( $clause ) : array( 'relation' => 'AND', $tax_query, $clause );
    }
    $query->set( 'tax_query', $tax_query );
    if ( '' !== $state['stock'] ) {
        $stock_clause = array( 'key' => '_stock_status', 'value' => $state['stock'], 'compare' => '=' );
        $meta_query = (array) $query->get( 'meta_query' );
        $query->set( 'meta_query', empty( $meta_query ) ? array( $stock_clause ) : array( 'relation' => 'AND', $meta_query, $stock_clause ) );
    }
    if ( $state['errors'] ) {
        $query->set( 'post__in', array( 0 ) );
    }
    $query->set( '_thaimoon_catalogue_filters', $state );
}

function thaimoon_catalogue_offer_matches( $offer, $state ) {
    if ( '' === $offer->get_price() ) {
        return false;
    }
    if ( '' !== $state['stock'] && $offer->get_stock_status() !== $state['stock'] ) {
        return false;
    }
    if ( $offer->is_type( 'variation' ) ) {
        if ( ! $offer->variation_is_visible() || ! $offer->variation_is_active() ) {
            return false;
        }
        if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $offer->is_in_stock() ) {
            return false;
        }
        $attributes = $offer->get_attributes();
        foreach ( array( 'portion' => 'pa_portion', 'quantity' => 'pa_quantite' ) as $name => $taxonomy ) {
            if ( '' !== $state[ $name ] && ! empty( $attributes[ $taxonomy ] ) && $attributes[ $taxonomy ] !== $state[ $name ] ) {
                return false;
            }
        }
    }
    $price = wc_add_number_precision( wc_get_price_to_display( $offer ) );
    return ( null === $state['min_price'] || $price >= wc_add_number_precision( $state['min_price'] ) ) &&
        ( null === $state['max_price'] || $price <= wc_add_number_precision( $state['max_price'] ) );
}

add_filter( 'the_posts', 'thaimoon_filter_catalogue_offers', 20, 2 );
function thaimoon_filter_catalogue_offers( $posts, $query ) {
    $state = $query->get( '_thaimoon_catalogue_filters' );
    if ( ! is_array( $state ) || ! $query->is_main_query() || ( null === $state['min_price'] && null === $state['max_price'] && '' === $state['portion'] && '' === $state['quantity'] && '' === $state['stock'] ) ) {
        return $posts;
    }
    return array_values( array_filter( $posts, static function ( $post ) use ( $state ) {
        $product = wc_get_product( $post->ID );
        if ( ! $product || ! $product->is_visible() ) {
            return false;
        }
        if ( ! $product->is_type( 'variable' ) ) {
            return thaimoon_catalogue_offer_matches( $product, $state );
        }
        foreach ( $product->get_children() as $id ) {
            $variation = wc_get_product( $id );
            if ( $variation && thaimoon_catalogue_offer_matches( $variation, $state ) ) {
                return true;
            }
        }
        return false;
    } ) );
}

function thaimoon_catalogue_filter_state() {
    global $wp_query;
    $state = $wp_query instanceof WP_Query ? $wp_query->get( '_thaimoon_catalogue_filters' ) : null;
    return is_array( $state ) ? $state : thaimoon_parse_catalogue_filters( array() );
}

function thaimoon_catalogue_filter_url() {
    if ( is_tax( 'collection' ) ) {
        $url = get_term_link( get_queried_object() );
        if ( ! is_wp_error( $url ) ) {
            return $url;
        }
    }
    return wc_get_page_permalink( 'shop' );
}

function thaimoon_catalogue_active_filters( $state ) {
    $active = array();
    if ( '' !== $state['search'] ) {
        $active[] = sprintf( __( 'Recherche : %s', 'restaurant' ), $state['search'] );
    }
    if ( null !== $state['min_price'] || null !== $state['max_price'] ) {
        $min = null === $state['min_price'] ? '0' : wc_format_localized_price( $state['min_price'] );
        $max = null === $state['max_price'] ? '' : wc_format_localized_price( $state['max_price'] );
        $active[] = '' === $max ? sprintf( __( 'À partir de %s %s', 'restaurant' ), $min, get_woocommerce_currency_symbol() ) : sprintf( __( 'Prix : %1$s à %2$s %3$s', 'restaurant' ), $min, $max, get_woocommerce_currency_symbol() );
    }
    foreach ( array( 'category' => 'product_cat', 'portion' => 'pa_portion', 'quantity' => 'pa_quantite' ) as $name => $taxonomy ) {
        $term = $state[ $name ] ? get_term_by( 'slug', $state[ $name ], $taxonomy ) : false;
        if ( $term ) {
            $active[] = 'quantity' === $name ? sprintf( __( 'Quantité : %s', 'restaurant' ), $term->name ) : $term->name;
        }
    }
    $statuses = wc_get_product_stock_status_options();
    if ( isset( $statuses[ $state['stock'] ] ) ) {
        $active[] = $statuses[ $state['stock'] ];
    }
    return $active;
}

function thaimoon_catalogue_filter_settings( $block = array() ) {
    $defaults = array( 'price' => __( 'Prix', 'restaurant' ), 'category' => __( 'Catégorie', 'restaurant' ), 'stock' => __( 'Disponibilité', 'restaurant' ), 'portion' => __( 'Portion', 'restaurant' ), 'quantity' => __( 'Quantité', 'restaurant' ) );
    $settings = array( 'title' => __( 'Filtres', 'restaurant' ), 'groups' => array() );
    $names = array( 'woocommerce/product-filter-price' => 'price', 'woocommerce/product-filter-taxonomy' => 'category', 'woocommerce/product-filter-status' => 'stock' );
    foreach ( isset( $block['innerBlocks'] ) ? $block['innerBlocks'] : array() as $child ) {
        if ( 'core/heading' === $child['blockName'] ) {
            $settings['title'] = wp_strip_all_tags( $child['innerHTML'] );
        }
        if ( isset( $names[ $child['blockName'] ] ) ) {
            $key = $names[ $child['blockName'] ];
            $settings['groups'][ $key ] = $defaults[ $key ];
            foreach ( $child['innerBlocks'] as $heading ) {
                if ( 'core/heading' === $heading['blockName'] ) {
                    $settings['groups'][ $key ] = wp_strip_all_tags( $heading['innerHTML'] );
                    break;
                }
            }
        }
    }
    $settings['groups'] += $defaults;
    return $settings;
}

class Thaimoon_Catalogue_Filters_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct( 'thaimoon_catalogue_filters', __( 'Thaï Moon — Filtres du catalogue', 'restaurant' ), array( 'description' => __( 'Recherche, prix, catégorie, disponibilité, portion et quantité pour le catalogue PHP du thème.', 'restaurant' ) ) );
    }

    public function widget( $args, $instance ) {
        if ( ! thaimoon_is_shop_catalogue() ) {
            return;
        }
        $settings = thaimoon_catalogue_filter_settings();
        if ( ! empty( $instance['settings'] ) && is_array( $instance['settings'] ) ) {
            $settings = $instance['settings'];
        }
        if ( ! empty( $instance['title'] ) ) {
            $settings['title'] = $instance['title'];
        }
        echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Enveloppe du thème.
        get_template_part( 'template-parts/catalogue/filters', null, array( 'settings' => $settings, 'state' => thaimoon_catalogue_filter_state() ) );
        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Enveloppe du thème.
        do_action( 'thaimoon_catalogue_filters_rendered' );
    }

    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : __( 'Filtres', 'restaurant' );
        echo '<p><label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">' . esc_html__( 'Titre', 'restaurant' ) . '</label><input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $title ) . '"></p>';
    }

    public function update( $new_instance, $old_instance ) {
        return array( 'title' => sanitize_text_field( isset( $new_instance['title'] ) && is_string( $new_instance['title'] ) ? $new_instance['title'] : '' ) );
    }
}
add_action( 'widgets_init', static function () { register_widget( 'Thaimoon_Catalogue_Filters_Widget' ); } );

function thaimoon_catalogue_sidebar_widgets() {
    $rendered = false;
    $mark_rendered = static function () use ( &$rendered ) { $rendered = true; };
    $adapt_block = static function ( $pre_render, $block ) use ( &$rendered ) {
        if ( null !== $pre_render || 'woocommerce/product-filters' !== $block['blockName'] ) {
            return $pre_render;
        }
        if ( $rendered ) {
            return '';
        }
        ob_start();
        the_widget( 'Thaimoon_Catalogue_Filters_Widget', array( 'settings' => thaimoon_catalogue_filter_settings( $block ) ), array( 'before_widget' => '<div class="catalogue-filters__widget">', 'after_widget' => '</div>' ) );
        return ob_get_clean();
    };
    add_action( 'thaimoon_catalogue_filters_rendered', $mark_rendered );
    add_filter( 'pre_render_block', $adapt_block, 10, 2 );
    try {
        if ( is_active_sidebar( 'sidebar-1' ) ) {
            dynamic_sidebar( 'sidebar-1' );
        }
        if ( ! $rendered ) {
            the_widget( 'Thaimoon_Catalogue_Filters_Widget', array(), array( 'before_widget' => '<div class="catalogue-filters__widget">', 'after_widget' => '</div>' ) );
        }
    } finally {
        remove_filter( 'pre_render_block', $adapt_block, 10 );
        remove_action( 'thaimoon_catalogue_filters_rendered', $mark_rendered );
    }
}

add_action( 'wp_enqueue_scripts', 'thaimoon_catalogue_filter_assets', 45 );
function thaimoon_catalogue_filter_assets() {
    if ( thaimoon_is_shop_catalogue() ) {
        wp_enqueue_script( 'thaimoon-catalogue-filters', get_stylesheet_directory_uri() . '/assets/js/catalogue-filters.js', array(), (string) filemtime( get_stylesheet_directory() . '/assets/js/catalogue-filters.js' ), true );
    }
}

add_filter( 'woocommerce_redirect_single_search_result', static function ( $redirect ) {
    return thaimoon_is_shop_catalogue() ? false : $redirect;
} );
