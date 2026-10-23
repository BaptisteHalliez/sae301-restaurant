<?php
defined( 'ABSPATH' ) || exit;
class Thaimoon_Email_Cancellation_Request extends WC_Email {
    public function __construct() {
        $this->id = 'thaimoon_cancellation_request';
        $this->title = 'Demande d’annulation du client';
        $this->description = 'Prévient le restaurant lorsqu’un client demande l’annulation de sa propre commande.';
        $this->email_group = 'orders';
        $this->template_html = 'emails/admin-cancellation-request.php';
        $this->template_plain = 'emails/plain/admin-cancellation-request.php';
        $this->template_base = get_stylesheet_directory() . '/woocommerce/';
        $this->placeholders = array( '{order_number}' => '' );
        parent::__construct();
        $new_order = get_option( 'woocommerce_new_order_settings', array() );
        $fallback = ! empty( $new_order['recipient'] ) ? $new_order['recipient'] : get_option( 'admin_email' );
        $recipient = trim( (string) $this->get_option( 'recipient', '' ) );
        $this->recipient = '' !== $recipient ? $recipient : $fallback;
    }
    public function init_form_fields() {
        parent::init_form_fields();
        $this->form_fields['recipient'] = array( 'title' => 'Destinataire(s)', 'type' => 'text', 'description' => 'Adresses séparées par des virgules. Par défaut : destinataires des nouvelles commandes.', 'default' => '', 'desc_tip' => true );
    }
    public function get_default_subject() {
        return '[{site_title}] Demande d’annulation — commande #{order_number}';
    }
    public function get_default_heading() {
        return 'Demande d’annulation de la commande #{order_number}';
    }
    public function trigger( $order_id ) {
        $this->object = wc_get_order( $order_id );
        if ( ! $this->object instanceof WC_Order || 'requested' !== $this->object->get_meta( '_thaimoon_cancellation_state', true ) ) {
            return false;
        }
        $this->placeholders['{order_number}'] = $this->object->get_order_number();
        $this->setup_locale();
        try {
            if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
                return false;
            }
            return $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        } finally {
            $this->restore_locale();
        }
    }
    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array( 'order' => $this->object, 'email_heading' => $this->get_heading(), 'email' => $this, 'sent_to_admin' => true, 'plain_text' => false, 'additional_content' => $this->get_additional_content() ), '', get_stylesheet_directory() . '/woocommerce/' );
    }
    public function get_content_plain() {
        return wc_get_template_html( $this->template_plain, array( 'order' => $this->object, 'email_heading' => $this->get_heading(), 'email' => $this, 'sent_to_admin' => true, 'plain_text' => true, 'additional_content' => $this->get_additional_content() ), '', get_stylesheet_directory() . '/woocommerce/' );
    }
}
