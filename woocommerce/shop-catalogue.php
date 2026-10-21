<?php
/** Catalogue filtrable commun : boutique, catégories, collections et recherches. */
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );

$sections = thaimoon_get_shop_catalogue();
$state = thaimoon_catalogue_filter_state();
$active_filters = thaimoon_catalogue_active_filters( $state );
$total = array_sum( array_map( static function ( $section ) { return count( $section['products'] ); }, $sections ) );
$archive = ( is_product_category() || is_tax( 'collection' ) ) ? get_queried_object() : null;
$page_title = $archive instanceof WP_Term ? $archive->name : __( 'Click & Collect — La carte', 'restaurant' );
$previous_loop = isset( $GLOBALS['woocommerce_loop'] ) ? $GLOBALS['woocommerce_loop'] : null;
$previous_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;
?>
<h1 class="screen-reader-text"><?php echo esc_html( $page_title ); ?></h1>
<div class="catalogue-layout">
    <aside class="catalogue-layout__sidebar" aria-label="<?php echo esc_attr__( 'Recherche et filtres du catalogue', 'restaurant' ); ?>">
        <details class="catalogue-filters">
            <summary class="catalogue-filters__toggle">
                <span><?php esc_html_e( 'Rechercher et filtrer', 'restaurant' ); ?></span>
                <?php if ( $active_filters ) : ?><span class="catalogue-filters__count"><?php echo esc_html( (string) count( $active_filters ) ); ?><span class="screen-reader-text"> <?php esc_html_e( 'critères actifs', 'restaurant' ); ?></span></span><?php endif; ?>
            </summary>
            <div class="catalogue-filters__widgets"><?php thaimoon_catalogue_sidebar_widgets(); ?></div>
        </details>
    </aside>
    <div class="catalogue catalogue-layout__results" id="catalogue-results">
        <div class="catalogue-results">
            <p class="catalogue-results__count" role="status"><?php echo esc_html( sprintf( _n( '%s produit trouvé', '%s produits trouvés', $total, 'restaurant' ), number_format_i18n( $total ) ) ); ?></p>
            <?php if ( $active_filters ) : ?>
                <ul class="catalogue-results__criteria" aria-label="<?php echo esc_attr__( 'Critères actifs', 'restaurant' ); ?>">
                    <?php foreach ( $active_filters as $label ) : ?><li class="catalogue-results__criterion"><?php echo esc_html( $label ); ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ( $state['errors'] ) : ?>
                <ul class="catalogue-results__errors" role="alert"><?php foreach ( $state['errors'] as $error ) : ?><li><?php echo esc_html( $error ); ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        </div>
    <?php if ( $sections ) : ?>
        <?php do_action( 'woocommerce_before_shop_loop' ); ?>
        <?php foreach ( $sections as $section ) : ?>
            <section class="catalogue__section" aria-labelledby="<?php echo esc_attr( $section['id'] ); ?>">
                <header class="catalogue__header">
                    <h2 class="catalogue__title" id="<?php echo esc_attr( $section['id'] ); ?>"><?php echo esc_html( $section['title'] ); ?></h2>
                    <span class="catalogue__underline" aria-hidden="true"></span>
                    <?php if ( $section['description'] ) : ?>
                        <div class="catalogue__description"><?php echo wp_kses_post( wpautop( $section['description'] ) ); ?></div>
                    <?php endif; ?>
                </header>
                <?php
                if ( empty( $section['products'] ) ) {
                    do_action( 'woocommerce_no_products_found' );
                } else {
                    wc_setup_loop( array( 'name' => 'thaimoon-catalogue', 'columns' => 3, 'total' => count( $section['products'] ), 'per_page' => count( $section['products'] ), 'total_pages' => 1, 'current_page' => 1, 'is_paginated' => false ) );
                    woocommerce_product_loop_start();
                    global $post;
                    foreach ( $section['products'] as $post ) {
                        setup_postdata( $post );
                        do_action( 'woocommerce_shop_loop' );
                        wc_get_template_part( 'content', 'product' );
                    }
                    woocommerce_product_loop_end();
                    wp_reset_postdata();
                }
                ?>
            </section>
        <?php endforeach; ?>
        <?php do_action( 'woocommerce_after_shop_loop' ); ?>
    <?php else : ?>
        <section class="catalogue-empty" aria-labelledby="catalogue-empty-title">
            <h2 class="catalogue-empty__title" id="catalogue-empty-title"><?php esc_html_e( 'Aucun produit ne correspond à vos critères.', 'restaurant' ); ?></h2>
            <p class="catalogue-empty__description"><?php esc_html_e( 'Essayez d’élargir votre recherche ou d’effacer les filtres.', 'restaurant' ); ?></p>
            <a class="catalogue-empty__reset" href="<?php echo esc_url( thaimoon_catalogue_filter_url() . '#catalogue-results' ); ?>"><?php esc_html_e( 'Effacer les filtres', 'restaurant' ); ?></a>
        </section>
    <?php endif; ?>
    </div>
</div>
<?php
$GLOBALS['product'] = $previous_product;
if ( null === $previous_loop ) {
    wc_reset_loop();
} else {
    $GLOBALS['woocommerce_loop'] = $previous_loop;
}
do_action( 'woocommerce_after_main_content' );
do_action( 'woocommerce_sidebar' );
get_footer( 'shop' );
