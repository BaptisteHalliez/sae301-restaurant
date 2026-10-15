<?php
/** Boutique et pages de catégories Thaï Moon, présentation commune. */
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
do_action( 'woocommerce_before_main_content' );

$sections = thaimoon_get_shop_catalogue();
$is_category = is_product_category();
$previous_loop = isset( $GLOBALS['woocommerce_loop'] ) ? $GLOBALS['woocommerce_loop'] : null;
?>
<div class="catalogue">
    <?php if ( ! $is_category ) : ?>
        <h1 class="screen-reader-text">Click &amp; Collect — La carte</h1>
    <?php endif; ?>
    <?php if ( $sections ) : ?>
        <?php do_action( 'woocommerce_before_shop_loop' ); ?>
        <?php foreach ( $sections as $section ) : ?>
            <section class="catalogue__section" aria-labelledby="<?php echo esc_attr( $section['id'] ); ?>">
                <header class="catalogue__header">
                    <?php if ( $is_category ) : ?>
                        <h1 class="catalogue__title" id="<?php echo esc_attr( $section['id'] ); ?>"><?php echo esc_html( $section['title'] ); ?></h1>
                    <?php else : ?>
                        <h2 class="catalogue__title" id="<?php echo esc_attr( $section['id'] ); ?>"><?php echo esc_html( $section['title'] ); ?></h2>
                    <?php endif; ?>
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
        <?php do_action( 'woocommerce_no_products_found' ); ?>
    <?php endif; ?>
</div>
<?php
if ( null === $previous_loop ) {
    wc_reset_loop();
} else {
    $GLOBALS['woocommerce_loop'] = $previous_loop;
}
do_action( 'woocommerce_after_main_content' );
do_action( 'woocommerce_sidebar' );
get_footer( 'shop' );
