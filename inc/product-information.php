<?php
/** Informations du groupe SCF « Infos Nutritionnelles » sur la fiche produit. */
defined( 'ABSPATH' ) || exit;

// Après la description courte (20), avant le formulaire d'achat (30).
add_action( 'woocommerce_single_product_summary', 'thaimoon_product_information', 25 );
function thaimoon_product_information() {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $labels = array(
        'allergenes'  => __( 'Allergènes', 'thaimoon-restaurant' ),
        'composition' => __( 'Composition', 'thaimoon-restaurant' ),
    );
    $rows = array();

    foreach ( $labels as $name => $label ) {
        // Ces deux champs Texte SCF sont enregistrés comme métadonnées du produit.
        $value = get_post_meta( $product->get_id(), $name, true );
        if ( is_string( $value ) && '' !== trim( $value ) ) {
            $rows[] = array( 'label' => $label, 'value' => trim( $value ) );
        }
    }

    if ( empty( $rows ) ) {
        return;
    }
    ?>
    <dl class="product-information">
        <?php foreach ( $rows as $row ) : ?>
            <div class="product-information__row">
                <dt class="product-information__label"><?php echo esc_html( $row['label'] ); ?> :</dt>
                <dd class="product-information__content"><?php echo esc_html( $row['value'] ); ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
    <?php
}
