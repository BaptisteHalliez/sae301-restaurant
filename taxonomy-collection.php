<?php
/**
 * Archive d'une collection de produits (taxonomie SCF : collection).
 * À placer à la racine du thème enfant restaurant.
 */
defined( 'ABSPATH' ) || exit;

// Réutiliser la présentation du catalogue sans regrouper les produits par catégorie.
add_filter( 'body_class', static function ( $classes ) {
    $classes[] = 'shop-catalogue';
    return $classes;
} );

// Conserver les notices WooCommerce et la pagination sous les produits.
foreach ( array( 'woocommerce_before_shop_loop', 'woocommerce_after_shop_loop' ) as $hook ) {
    remove_action( $hook, 'storefront_sorting_wrapper', 9 );
    remove_action( $hook, 'storefront_sorting_wrapper_close', 31 );
    remove_action( $hook, 'woocommerce_catalog_ordering', 10 );
    remove_action( $hook, 'woocommerce_catalog_ordering', 30 );
    remove_action( $hook, 'woocommerce_result_count', 20 );
}
remove_action( 'woocommerce_before_shop_loop', 'storefront_woocommerce_pagination', 30 );
remove_filter( 'woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories', 10 );

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );

$description = term_description();
?>
<div class="catalogue">
    <section class="catalogue__section" aria-labelledby="collection-title">
        <header class="catalogue__header">
            <h1 class="catalogue__title" id="collection-title"><?php echo esc_html( single_term_title( '', false ) ); ?></h1>
            <span class="catalogue__underline" aria-hidden="true"></span>
            <?php if ( $description ) : ?>
                <div class="catalogue__description"><?php echo wp_kses_post( $description ); ?></div>
            <?php endif; ?>
        </header>
        <?php
        // La requête principale conserve la collection courante, la visibilité
        // des produits et la pagination gérées par WooCommerce.
        if ( woocommerce_product_loop() ) {
            do_action( 'woocommerce_before_shop_loop' );
            wc_set_loop_prop( 'columns', 3 );
            woocommerce_product_loop_start();

            while ( have_posts() ) {
                the_post();
                do_action( 'woocommerce_shop_loop' );
                wc_get_template_part( 'content', 'product' );
            }

            woocommerce_product_loop_end();
            do_action( 'woocommerce_after_shop_loop' );
        } else {
            do_action( 'woocommerce_no_products_found' );
        }
        ?>
    </section>
</div>
<?php
do_action( 'woocommerce_after_main_content' );
do_action( 'woocommerce_sidebar' );
get_footer( 'shop' );
