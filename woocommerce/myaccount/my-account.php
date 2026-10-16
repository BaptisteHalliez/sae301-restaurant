<?php
/** Espace client — hooks du gabarit WooCommerce 3.5.0. */
defined( 'ABSPATH' ) || exit;
$logout = 'deconnexion' === thaimoon_account_view();
?>
<div class="account-layout<?php echo $logout ? ' account-layout--logout' : ''; ?>">
    <?php do_action( 'woocommerce_account_navigation' ); ?>
    <div class="woocommerce-MyAccount-content account-layout__content">
        <?php if ( $logout ) : ?>
            <?php wc_print_notices(); ?>
            <section class="account-panel account-panel--logout">
                <h2 class="account-panel__title">Êtes-vous sûr de vouloir vous déconnecter ?</h2>
                <p class="account-panel__description">Vous pourrez vous reconnecter plus tard au besoin.</p>
                <div class="account-panel__actions"><a class="button account-panel__button" href="<?php echo esc_url( wc_logout_url( wc_get_page_permalink( 'myaccount' ) ) ); ?>">Se déconnecter</a></div>
            </section>
        <?php else : do_action( 'woocommerce_account_content' ); endif; ?>
    </div>
</div>
