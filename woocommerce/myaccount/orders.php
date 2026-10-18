<?php
/** Historique V1.3 ; données, filtres de colonnes et pagination WooCommerce conservés. @version 9.5.0 */
defined( 'ABSPATH' ) || exit;
do_action( 'woocommerce_before_account_orders', $has_orders );
if ( $has_orders ) : ?>
<section class="account-panel account-orders" aria-label="Historique des commandes">
<?php foreach ( $customer_orders->orders as $customer_order ) :
    $order = wc_get_order( $customer_order );
    if ( ! thaimoon_orders_owned( $order, get_current_user_id() ) ) { continue; }
    $labels = array( 'order-number' => 'Commande', 'order-date' => 'Date', 'order-status' => 'État', 'order-total' => 'Prix' ); ?>
    <article class="account-orders__row">
        <dl class="account-orders__fields">
        <?php foreach ( wc_get_account_orders_columns() as $column => $label ) :
            if ( 'order-actions' === $column ) { continue; } ?>
            <div class="account-orders__field">
                <dt><?php echo esc_html( $labels[ $column ] ?? $label ); ?></dt>
                <dd><?php
                if ( has_action( 'woocommerce_my_account_my_orders_column_' . $column ) ) {
                    do_action( 'woocommerce_my_account_my_orders_column_' . $column, $order );
                } elseif ( 'order-number' === $column ) {
                    echo esc_html( 'Commande n°' . $order->get_order_number() );
                } elseif ( 'order-date' === $column ) {
                    $date = $order->get_date_created();
                    if ( $date ) { echo '<time datetime="' . esc_attr( $date->date( 'c' ) ) . '">' . esc_html( wc_format_datetime( $date, 'd / m / Y' ) ) . '</time>'; }
                } elseif ( 'order-status' === $column ) {
                    echo esc_html( wc_get_order_status_name( $order->get_status() ) );
                    if ( 'requested' === $order->get_meta( '_thaimoon_cancellation_state', true ) && $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) ) {
                        echo '<small class="account-orders__request-state">Annulation demandée</small>';
                    }
                } elseif ( 'order-total' === $column ) {
                    echo wp_kses_post( $order->get_formatted_order_total() );
                }
                ?></dd>
            </div>
        <?php endforeach; ?>
        </dl>
        <div class="account-orders__actions">
        <?php if ( has_action( 'woocommerce_my_account_my_orders_column_order-actions' ) ) :
            do_action( 'woocommerce_my_account_my_orders_column_order-actions', $order );
        else : foreach ( wc_get_account_orders_actions( $order ) as $key => $action ) : ?>
            <a class="button account-orders__button <?php echo esc_attr( sanitize_html_class( $key ) ); ?>" href="<?php echo esc_url( $action['url'] ); ?>" aria-label="<?php echo esc_attr( ( $action['name'] ?? '' ) . ' — commande n°' . $order->get_order_number() ); ?>"><?php echo esc_html( $action['name'] ); ?></a>
        <?php endforeach; endif; ?>
        </div>
    </article>
<?php endforeach; ?>
</section>
<?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>
<?php if ( 1 < $customer_orders->max_num_pages ) : ?>
<nav class="account-orders__pagination" aria-label="Pagination des commandes">
    <?php if ( 1 < $current_page ) : ?><a class="button account-orders__button" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">Précédent</a><?php endif; ?>
    <?php if ( $current_page < $customer_orders->max_num_pages ) : ?><a class="button account-orders__button" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>">Suivant</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php else : ?>
<section class="account-panel account-orders__empty"><h2>Vos commandes</h2><p>Vous n’avez pas encore passé de commande.</p><a class="button account-orders__button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Découvrir la carte</a></section>
<?php endif;
do_action( 'woocommerce_after_account_orders', $has_orders );
