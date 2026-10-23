<?php
defined( 'ABSPATH' ) || exit;
do_action( 'woocommerce_before_account_navigation' );
?>
<nav class="woocommerce-MyAccount-navigation account-nav" aria-label="Pages de mon compte">
    <ul class="account-nav__list">
        <?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) :
            $current = thaimoon_account_menu_current( $endpoint );
            $url = 'customer-logout' === $endpoint ? thaimoon_account_view_url( 'deconnexion' ) : wc_get_account_endpoint_url( $endpoint ); ?>
            <li class="account-nav__item<?php echo $current ? ' account-nav__item--current' : ''; ?>"><a class="account-nav__link" href="<?php echo esc_url( $url ); ?>" <?php echo $current ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php do_action( 'woocommerce_after_account_navigation' ); ?>
