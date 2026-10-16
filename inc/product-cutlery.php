<?php
/** Choix de couverts gratuit, indépendant des variations de portion/quantité. */
defined( 'ABSPATH' ) || exit;

function thaimoon_cutlery_options( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return array();
    }
    if ( $product->is_type( 'variation' ) ) {
        $product = wc_get_product( $product->get_parent_id() );
    }
    if ( ! $product || ! $product->is_type( array( 'simple', 'variable' ) ) ) {
        return array();
    }
    foreach ( $product->get_attributes() as $attribute ) {
        if ( ! in_array( sanitize_title( $attribute->get_name() ), array( 'couverts', 'pa_couverts' ), true ) || $attribute->get_variation() ) {
            continue;
        }
        $options = array();
        if ( $attribute->is_taxonomy() ) {
            $terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );
            if ( ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    $options[ $term->slug ] = wc_clean( $term->name );
                }
            }
        } else {
            foreach ( $attribute->get_options() as $label ) {
                $key = sanitize_title( $label );
                if ( '' !== $key ) {
                    $options[ $key ] = wc_clean( $label );
                }
            }
        }
        return $options;
    }
    return array();
}

function thaimoon_cutlery_selection( $options ) {
    if ( isset( $_POST['thaimoon_cutlery'] ) ) {
        // Comparaison stricte à la liste autorisée : aucun libellé client n'est enregistré.
        return is_string( $_POST['thaimoon_cutlery'] ) ? wp_unslash( $_POST['thaimoon_cutlery'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Formulaire natif d'ajout au panier.
    }
    foreach ( $options as $key => $label ) {
        if ( 'pas-de-couverts' === sanitize_title( $label ) ) {
            return $key;
        }
    }
    return key( $options );
}

add_action( 'woocommerce_before_add_to_cart_button', 'thaimoon_cutlery_fields', 5 );
function thaimoon_cutlery_fields() {
    global $product;
    $options = thaimoon_cutlery_options( $product );
    if ( ! $options ) {
        return;
    }
    $selected = thaimoon_cutlery_selection( $options );
    if ( ! is_string( $selected ) || ! array_key_exists( $selected, $options ) ) {
        // Un choix invalide ne doit pas empêcher de corriger le formulaire.
        $selected = key( $options );
    }
    ?>
    <fieldset class="cutlery-options">
        <legend class="cutlery-options__legend">Couverts :</legend>
        <?php foreach ( $options as $value => $label ) : ?>
            <label class="cutlery-options__option">
                <input class="cutlery-options__input" type="radio" name="thaimoon_cutlery" value="<?php echo esc_attr( $value ); ?>" <?php checked( $selected, $value ); ?> required>
                <span class="cutlery-options__label"><?php echo esc_html( $label ); ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <?php
}

add_filter( 'woocommerce_add_to_cart_validation', 'thaimoon_cutlery_validate', 10, 3 );
function thaimoon_cutlery_validate( $passed, $product_id, $quantity ) {
    $options = thaimoon_cutlery_options( wc_get_product( $product_id ) );
    if ( $options ) {
        $selected = thaimoon_cutlery_selection( $options );
        if ( ! is_string( $selected ) || ! array_key_exists( $selected, $options ) ) {
            wc_add_notice( 'Merci de choisir une option de couverts proposée pour ce produit.', 'error' );
            return false;
        }
    }
    return $passed;
}

add_filter( 'woocommerce_add_cart_item_data', 'thaimoon_cutlery_cart_data', 10, 3 );
function thaimoon_cutlery_cart_data( $data, $product_id, $variation_id ) {
    $options  = thaimoon_cutlery_options( wc_get_product( $product_id ) );
    $selected = thaimoon_cutlery_selection( $options );
    if ( is_string( $selected ) && array_key_exists( $selected, $options ) ) {
        // Des choix différents créent des lignes distinctes dans le panier.
        $data['thaimoon_cutlery'] = array( 'value' => $selected, 'label' => $options[ $selected ] );
    }
    return $data;
}

add_filter( 'woocommerce_get_item_data', 'thaimoon_cutlery_cart_display', 10, 2 );
function thaimoon_cutlery_cart_display( $data, $cart_item ) {
    if ( isset( $cart_item['thaimoon_cutlery']['label'] ) ) {
        $label  = wc_clean( $cart_item['thaimoon_cutlery']['label'] );
        $data[] = array( 'key' => 'Couverts', 'value' => $label, 'display' => esc_html( $label ) );
    }
    return $data;
}

add_action( 'woocommerce_checkout_create_order_line_item', 'thaimoon_cutlery_order_data', 10, 4 );
function thaimoon_cutlery_order_data( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['thaimoon_cutlery']['label'] ) ) {
        $item->add_meta_data( 'Couverts', wc_clean( $values['thaimoon_cutlery']['label'] ), true );
    }
}

// Un produit simple avec ce choix passe aussi par sa fiche avant l'ajout au panier.
add_filter( 'woocommerce_loop_add_to_cart_link', 'thaimoon_cutlery_product_link', 30, 2 );
function thaimoon_cutlery_product_link( $html, $product ) {
    if ( $product->is_type( 'simple' ) && $product->is_in_stock() && $product->is_purchasable() && thaimoon_cutlery_options( $product ) ) {
        return '<a class="button product-card__button" href="' . esc_url( $product->get_permalink() ) . '">Choisir les options</a>';
    }
    return $html;
}
