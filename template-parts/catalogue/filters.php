<?php
defined( 'ABSPATH' ) || exit;
$state = $args['state'];
$settings = $args['settings'];
$id = wp_unique_id( 'catalogue-filter-' );
$base_url = thaimoon_catalogue_filter_url();
$currency = get_woocommerce_currency_symbol();
$step = number_format( pow( 10, -wc_get_price_decimals() ), wc_get_price_decimals(), '.', '' );
$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name' ) );
$portions = taxonomy_exists( 'pa_portion' ) ? get_terms( array( 'taxonomy' => 'pa_portion', 'hide_empty' => true, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
$quantities = taxonomy_exists( 'pa_quantite' ) ? get_terms( array( 'taxonomy' => 'pa_quantite', 'hide_empty' => true, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : array();
if ( is_wp_error( $categories ) ) {
    $categories = array();
}
if ( is_wp_error( $portions ) ) {
    $portions = array();
}
if ( is_wp_error( $quantities ) ) {
    $quantities = array();
}
if ( '' === $state['category'] && is_product_category() ) {
    $category = get_queried_object();
    if ( $category instanceof WP_Term ) {
        $state['category'] = $category->slug;
    }
}
?>
<form class="catalogue-filters__form" action="<?php echo esc_url( $base_url . '#catalogue-results' ); ?>" method="get" role="search" aria-label="<?php echo esc_attr__( 'Rechercher et filtrer les produits', 'restaurant' ); ?>">
    <input type="hidden" name="post_type" value="product">
    <h2 class="catalogue-filters__title"><?php echo esc_html( $settings['title'] ); ?></h2>
    <div class="catalogue-filters__field">
        <label class="catalogue-filters__label" for="<?php echo esc_attr( $id . '-search' ); ?>"><?php esc_html_e( 'Rechercher un produit', 'restaurant' ); ?></label>
        <input class="catalogue-filters__input" id="<?php echo esc_attr( $id . '-search' ); ?>" type="search" name="s" value="<?php echo esc_attr( $state['search'] ); ?>" placeholder="<?php echo esc_attr__( 'Nom ou ingrédient…', 'restaurant' ); ?>">
    </div>
    <?php foreach ( $settings['groups'] as $group => $label ) : ?>
        <?php if ( 'price' === $group ) : ?>
            <fieldset class="catalogue-filters__group" aria-describedby="<?php echo esc_attr( $id . '-price-help' ); ?>">
                <legend class="catalogue-filters__legend"><?php echo esc_html( $label ); ?></legend>
                <div class="catalogue-filters__prices">
                    <?php foreach ( array( 'min_price' => __( 'Minimum', 'restaurant' ), 'max_price' => __( 'Maximum', 'restaurant' ) ) as $key => $price_label ) : ?>
                        <div class="catalogue-filters__field">
                            <label class="catalogue-filters__price-label" for="<?php echo esc_attr( $id . '-' . $key ); ?>"><?php echo esc_html( $price_label . ' (' . $currency . ')' ); ?></label>
                            <input class="catalogue-filters__input" id="<?php echo esc_attr( $id . '-' . $key ); ?>" type="number" inputmode="decimal" name="<?php echo esc_attr( $key ); ?>" min="0" step="<?php echo esc_attr( $step ); ?>" value="<?php echo esc_attr( null === $state[ $key ] ? '' : $state[ $key ] ); ?>" placeholder="<?php echo esc_attr( 'min_price' === $key ? '0' : '∞' ); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="catalogue-filters__hint" id="<?php echo esc_attr( $id . '-price-help' ); ?>"><?php esc_html_e( 'Prix par portion ou quantité. Une borne vide n’impose pas de limite.', 'restaurant' ); ?></p>
            </fieldset>
        <?php elseif ( 'category' === $group ) : ?>
            <div class="catalogue-filters__field">
                <label class="catalogue-filters__label" for="<?php echo esc_attr( $id . '-category' ); ?>"><?php echo esc_html( $label ); ?></label>
                <select class="catalogue-filters__select" id="<?php echo esc_attr( $id . '-category' ); ?>" name="filter_product_cat">
                    <option value=""><?php esc_html_e( 'Toutes les catégories', 'restaurant' ); ?></option>
                    <?php foreach ( $categories as $category ) : ?>
                        <option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $state['category'], $category->slug ); ?>><?php echo esc_html( $category->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php elseif ( 'stock' === $group ) : ?>
            <div class="catalogue-filters__field">
                <label class="catalogue-filters__label" for="<?php echo esc_attr( $id . '-stock' ); ?>"><?php echo esc_html( $label ); ?></label>
                <select class="catalogue-filters__select" id="<?php echo esc_attr( $id . '-stock' ); ?>" name="filter_stock_status">
                    <option value=""><?php esc_html_e( 'Toutes les disponibilités', 'restaurant' ); ?></option>
                    <?php foreach ( wc_get_product_stock_status_options() as $value => $stock_label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $state['stock'], $value ); ?>><?php echo esc_html( $stock_label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php elseif ( 'portion' === $group || 'quantity' === $group ) : ?>
            <?php
            $is_quantity = 'quantity' === $group;
            $terms = $is_quantity ? $quantities : $portions;
            $field = $is_quantity ? 'quantity' : 'portion';
            $parameter = $is_quantity ? 'filter_quantite' : 'filter_portion';
            ?>
            <div class="catalogue-filters__field">
                <label class="catalogue-filters__label" for="<?php echo esc_attr( $id . '-' . $field ); ?>"><?php echo esc_html( $label ); ?></label>
                <select class="catalogue-filters__select" id="<?php echo esc_attr( $id . '-' . $field ); ?>" name="<?php echo esc_attr( $parameter ); ?>"<?php disabled( empty( $terms ) ); ?>>
                    <option value=""><?php echo esc_html( $is_quantity ? __( 'Toutes les quantités', 'restaurant' ) : __( 'Toutes les portions', 'restaurant' ) ); ?></option>
                    <?php foreach ( $terms as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $state[ $field ], $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ( ! $terms ) : ?>
                    <p class="catalogue-filters__hint"><?php echo esc_html( $is_quantity ? __( 'Aucune quantité n’est encore associée aux produits.', 'restaurant' ) : __( 'Aucune portion n’est encore associée aux produits.', 'restaurant' ) ); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="catalogue-filters__actions">
        <button class="catalogue-filters__apply" type="submit"><?php esc_html_e( 'Appliquer les filtres', 'restaurant' ); ?></button>
        <a class="catalogue-filters__clear" href="<?php echo esc_url( $base_url . '#catalogue-results' ); ?>"><?php esc_html_e( 'Effacer les filtres', 'restaurant' ); ?></a>
    </div>
</form>
