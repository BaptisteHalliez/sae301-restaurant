<?php
defined( 'ABSPATH' ) || exit;
if ( ! thaimoon_orders_owned( $order, get_current_user_id() ) ) { return; }
$items = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
if ( apply_filters( 'woocommerce_order_downloads_table_show_downloads', $order->has_downloadable_item() && $order->is_download_permitted(), $order ) ) {
    wc_get_template( 'order/order-downloads.php', array( 'downloads' => $order->get_downloadable_items(), 'show_title' => true ) );
}
?>
<section class="account-panel account-order" aria-labelledby="account-order-title">
    <h2 id="account-order-title" class="account-order__title">Détails de la commande n°<?php echo esc_html( $order->get_order_number() ); ?></h2>
    <p class="account-order__status">État : <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></p>
    <?php $state = $order->get_meta( '_thaimoon_cancellation_state', true );
    if ( 'requested' === $state && $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) ) : ?>
        <p class="account-order__notice" role="status">Votre demande d’annulation est en attente de la décision du restaurant.</p>
    <?php elseif ( 'declined' === $state ) : ?>
        <p class="account-order__notice" role="status">Votre demande d’annulation n’a pas été acceptée. Consultez les messages du restaurant.</p>
    <?php endif;
    do_action( 'woocommerce_order_details_before_order_table', $order ); ?>
    <table class="woocommerce-table order_details account-order__table">
        <thead><tr><th scope="col">Commande</th><th scope="col">Prix</th></tr></thead>
        <tbody>
        <?php do_action( 'woocommerce_order_details_before_order_table_items', $order );
        foreach ( $items as $item_id => $item ) {
            $product = $item->get_product();
            wc_get_template( 'order/order-details-item.php', array( 'order' => $order, 'item_id' => $item_id, 'item' => $item, 'show_purchase_note' => $show_purchase_note, 'purchase_note' => $product ? $product->get_purchase_note() : '', 'product' => $product ) );
        }
        do_action( 'woocommerce_order_details_after_order_table_items', $order ); ?>
        </tbody>
        <tfoot>
        <?php foreach ( $order->get_order_item_totals() as $total ) : ?><tr><th scope="row"><?php echo esc_html( $total['label'] ); ?></th><td><?php echo wp_kses_post( $total['value'] ); ?></td></tr><?php endforeach; ?>
        <?php if ( $order->get_customer_note() ) : ?><tr><th scope="row">Votre message :</th><td><?php echo wp_kses_post( nl2br( esc_html( $order->get_customer_note() ) ) ); ?></td></tr><?php endif; ?>
        </tfoot>
    </table>
    <?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
    <?php $actions = wc_get_account_orders_actions( $order );
    unset( $actions['view'] );
    $can_request = thaimoon_orders_can_request( $order, get_current_user_id() );
    $result = thaimoon_orders_result( $order );
    if ( $actions || $can_request || $result ) : ?>
    <div class="account-order__actions">
        <?php foreach ( $actions as $key => $action ) : ?>
            <a class="button account-orders__button" href="<?php echo esc_url( $action['url'] ); ?>"><?php echo esc_html( $action['name'] ); ?></a>
        <?php endforeach; ?>
        <?php if ( $can_request ) : ?>
            <a class="button account-orders__button" data-order-cancel-open href="<?php echo esc_url( thaimoon_orders_confirmation_url( $order ) ); ?>">Demander l’annulation</a>
        <?php elseif ( $result ) : ?>
            <a class="button account-orders__button" href="<?php echo esc_url( add_query_arg( 'annulation', 'suivi', $order->get_view_order_url() ) ); ?>">Consulter le suivi</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>
<?php
do_action( 'woocommerce_after_order_details', $order );
wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
if ( thaimoon_orders_can_request( $order, get_current_user_id() ) ) {
    wc_get_template( 'myaccount/order-cancel-confirmation.php', array( 'order' => $order ) );
}
