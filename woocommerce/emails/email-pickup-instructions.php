<?php
defined( 'ABSPATH' ) || exit;
if ( ! isset( $order ) || ! $order instanceof WC_Order || ! empty( $sent_to_admin ) ) {
    return;
}
$shipping_items = $order->get_shipping_methods();
if ( empty( $shipping_items ) ) {
    return;
}
foreach ( $shipping_items as $shipping_item ) {
    $method = $shipping_item->get_method_id();
    $pickup = in_array( $method, array( 'local_pickup', 'pickup_location' ), true ) ||
        ( 'free_shipping' === $method && false !== strpos( sanitize_title( $shipping_item->get_name() ), 'retrait' ) );
    
    if ( ! $pickup ) {
        return;
    }
}
$thaimoon_email = require __DIR__ . '/thaimoon-email-settings.php';
$number = (string) $order->get_order_number();
$message = 'Attendez notre confirmation de disponibilité avant de vous déplacer. Cet email confirme la réception de votre commande, pas qu’elle est prête.';
if ( ! empty( $plain_text ) ) {
    echo "\nRETRAIT AU RESTAURANT\n";
    echo wp_strip_all_tags( $thaimoon_email['address'] ) . "\n";
    echo wp_strip_all_tags( $thaimoon_email['days'] . ' : ' . $thaimoon_email['hours'] ) . "\n";
    echo 'Samedi : ' . wp_strip_all_tags( $thaimoon_email['saturday_hours'] ) . "\n";
    echo 'Présentez votre numéro de commande : #' . wp_strip_all_tags( $number ) . "\n";
    echo $message . "\n";
    echo 'Contact : ' . wp_strip_all_tags( $thaimoon_email['phone'] ) . "\n\n";
    return;
}
?>
<table class="email-pickup" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr><td class="email-pickup__content">
        <h2 class="email-pickup__title">Retrait au restaurant</h2>
        <p><strong>Adresse :</strong> <?php echo esc_html( $thaimoon_email['address'] ); ?></p>
        <p><strong>Horaires :</strong> <?php echo esc_html( $thaimoon_email['days'] . ' : ' . $thaimoon_email['hours'] ); ?><br>Samedi : <?php echo esc_html( $thaimoon_email['saturday_hours'] ); ?></p>
        <p><strong>Numéro à présenter : #<?php echo esc_html( $number ); ?></strong></p>
        <p><?php echo esc_html( $message ); ?></p>
        <p class="email-pickup__contact">Une question ? <a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $thaimoon_email['phone'] ) ); ?>"><?php echo esc_html( $thaimoon_email['phone'] ); ?></a></p>
    </td></tr>
</table>
