<?php
/** @version 10.6.0 */
defined( 'ABSPATH' ) || exit;
if ( ! thaimoon_orders_owned( $order, get_current_user_id() ) ) {
    wc_print_notice( 'Cette commande n’est pas accessible depuis ce compte.', 'error' );
    return;
}
$result = thaimoon_orders_result( $order );
$followup = isset( $_GET['annulation'] ) && is_string( $_GET['annulation'] ) && 'suivi' === wp_unslash( $_GET['annulation'] );
if ( $followup && $result ) : ?>
<section class="account-panel account-order-result" aria-labelledby="account-order-result-title">
    <h2 id="account-order-result-title" class="account-order-result__title"><?php echo esc_html( $result['title'] ); ?></h2>
    <p class="account-order-result__description"><?php echo esc_html( $result['description'] ); ?></p>
    <div class="account-order-result__actions"><a class="button account-orders__button" href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">Liste des commandes</a></div>
    <a class="account-order-result__details" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">Voir le récapitulatif</a>
</section>
<?php return; endif;
do_action( 'woocommerce_view_order', $order_id );
$notes = $order->get_customer_order_notes();
if ( $notes ) : ?>
<section class="account-order-notes" aria-label="Messages du restaurant"><h2>Messages du restaurant</h2>
    <?php foreach ( $notes as $note ) : ?><article class="account-order-notes__item"><p><small><?php echo esc_html( date_i18n( 'd/m/Y à H:i', strtotime( $note->comment_date ) ) ); ?></small></p><?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?></article><?php endforeach; ?>
</section>
<?php endif;
