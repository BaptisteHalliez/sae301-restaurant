<?php
defined( 'ABSPATH' ) || exit;
if ( ! isset( $order ) || ! $order instanceof WC_Order ) { return; }
echo wp_strip_all_tags( $email_heading ) . "\n\n";
echo 'Le client demande l’annulation de la commande n°' . wp_strip_all_tags( $order->get_order_number() ) . ".\n";
echo "La commande reste active. Examinez la demande dans sa fiche, puis utilisez les Actions de commande pour l’accepter ou la refuser.\n\n";
do_action( 'woocommerce_email_order_details', $order, true, true, $email );
echo "\nExaminer la demande : " . esc_url_raw( $order->get_edit_order_url() ) . "\n\n";
echo "Accepter l’annulation ne rembourse pas le paiement. Traitez si nécessaire le remboursement avec le bouton natif Rembourser.\n";
if ( $additional_content ) { echo wp_strip_all_tags( wptexturize( $additional_content ) ) . "\n"; }
