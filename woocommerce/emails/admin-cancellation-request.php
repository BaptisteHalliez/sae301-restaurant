<?php
/** Demande d'annulation : notification du restaurant, aucune annulation automatique. */
defined( 'ABSPATH' ) || exit;
if ( ! isset( $order ) || ! $order instanceof WC_Order ) { return; }
do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p>Le client a demandé l’annulation de sa commande n°<?php echo esc_html( $order->get_order_number() ); ?> depuis son espace personnel.</p>
<p>La commande reste active. Examinez la demande dans sa fiche, puis utilisez « Actions de commande » pour l’accepter ou la refuser.</p>
<?php do_action( 'woocommerce_email_order_details', $order, true, false, $email ); ?>
<p><a class="email-button" href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">Examiner la demande</a></p>
<p>Accepter l’annulation ne rembourse pas un paiement encaissé. Traitez si nécessaire le remboursement depuis le bouton WooCommerce « Rembourser ».</p>
<?php
if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); }
do_action( 'woocommerce_email_footer', $email );
