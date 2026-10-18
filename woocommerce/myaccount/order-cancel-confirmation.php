<?php
defined( 'ABSPATH' ) || exit;
if ( ! thaimoon_orders_can_request( $order, get_current_user_id() ) ) { return; }
$requested = isset( $_GET['annuler'] ) && is_string( $_GET['annuler'] ) && '1' === wp_unslash( $_GET['annuler'] );
?>
<div id="account-cancel" class="account-cancel<?php echo $requested ? ' account-cancel--requested' : ''; ?>" data-order-cancel-container>
    <section class="account-panel account-cancel__panel" aria-labelledby="account-cancel-title" aria-describedby="account-cancel-description" data-order-cancel-panel tabindex="-1">
        <h2 class="account-cancel__title" id="account-cancel-title">Demander l’annulation de cette commande&nbsp;?</h2>
        <p class="account-cancel__description" id="account-cancel-description">Le restaurant devra confirmer votre demande. Si un paiement a déjà été encaissé, le remboursement sera traité après son accord.</p>
        <form class="account-cancel__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-order-cancel-form>
            <input type="hidden" name="action" value="thaimoon_request_cancellation">
            <input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>">
            <?php wp_nonce_field( 'thaimoon_cancel_request_' . $order->get_id(), '_thaimoon_cancel_nonce' ); ?>
            <a class="button account-orders__button" href="<?php echo esc_url( $order->get_view_order_url() ); ?>" data-order-cancel-close>Non</a>
            <button class="button account-orders__button" type="submit" data-order-cancel-submit>Oui, envoyer la demande</button>
        </form>
    </section>
</div>
