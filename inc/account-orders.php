<?php
/** Compte V1.3 : demandes d'annulation, décision du restaurant et suivi WooCommerce. */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'thaimoon_orders_assets', 46 );
function thaimoon_orders_assets() {
    if ( is_account_page() ) {
        $path = get_stylesheet_directory();
        wp_enqueue_style( 'thaimoon-account-orders', get_stylesheet_directory_uri() . '/assets/css/account-orders.css', array( 'thaimoon-account' ), (string) filemtime( $path . '/assets/css/account-orders.css' ) );
        wp_enqueue_script( 'thaimoon-account-orders', get_stylesheet_directory_uri() . '/assets/js/account-orders.js', array(), (string) filemtime( $path . '/assets/js/account-orders.js' ), true );
    }
}

function thaimoon_orders_owned( $order, $customer_id ) {
    return $order instanceof WC_Order && $customer_id > 0 && (int) $order->get_customer_id() === (int) $customer_id;
}
function thaimoon_orders_can_request( $order, $customer_id ) {
    return thaimoon_orders_owned( $order, $customer_id ) &&
        $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) &&
        '' === (string) $order->get_meta( '_thaimoon_cancellation_state', true );
}
function thaimoon_orders_confirmation_url( $order ) {
    return add_query_arg( 'annuler', '1', $order->get_view_order_url() ) . '#account-cancel';
}
function thaimoon_orders_result( $order ) {
    if ( $order->has_status( 'refunded' ) ) {
        $amount = (float) $order->get_total_refunded();
        return array( 'title' => 'Votre commande est marquée remboursée', 'description' => $amount > 0 ? 'Le restaurant a enregistré un remboursement de ' . html_entity_decode( wp_strip_all_tags( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' ) . '. Son traitement dépend du moyen de paiement utilisé.' : 'Consultez les messages du restaurant pour les informations concernant le remboursement.' );
    }
    if ( $order->has_status( 'cancelled' ) ) {
        return array( 'title' => 'Votre commande a été annulée', 'description' => 'Le restaurant a confirmé l’annulation. Si un paiement a été encaissé, son remboursement doit être traité par le restaurant.' );
    }
    if ( 'requested' === $order->get_meta( '_thaimoon_cancellation_state', true ) && $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) ) {
        return array( 'title' => 'Votre demande a été envoyée', 'description' => 'Le restaurant va examiner votre demande d’annulation. Votre commande reste active jusqu’à sa confirmation.' );
    }
    return array();
}
function thaimoon_orders_layout_class() {
    if ( is_wc_endpoint_url( 'orders' ) ) {
        return ' account-layout--orders';
    }
    if ( is_wc_endpoint_url( 'view-order' ) ) {
        $order = wc_get_order( absint( get_query_var( 'view-order' ) ) );
        $followup = isset( $_GET['annulation'] ) && is_string( $_GET['annulation'] ) && 'suivi' === wp_unslash( $_GET['annulation'] );
        return $followup && thaimoon_orders_owned( $order, get_current_user_id() ) && thaimoon_orders_result( $order ) ? ' account-layout--order-result' : ' account-layout--order-detail';
    }
    return '';
}

/** Verrou atomique stocké dans les options WP, indépendant du stockage HPOS des commandes. */
function thaimoon_orders_unlock( $key, $token ) {
    global $wpdb;
    $wpdb->delete( $wpdb->options, array( 'option_name' => $key, 'option_value' => $token ), array( '%s', '%s' ) );
    wp_cache_delete( $key, 'options' );
}
function thaimoon_orders_locked( $order_id, $callback ) {
    $key = '_thaimoon_cancel_lock_' . absint( $order_id );
    $old = get_option( $key );
    if ( is_string( $old ) && (int) $old < time() - 300 ) {
        thaimoon_orders_unlock( $key, $old );
    }
    $token = time() . ':' . wp_generate_uuid4();
    if ( ! add_option( $key, $token, '', false ) ) {
        return new WP_Error( 'busy', 'Cette commande est déjà en cours de traitement. Réessayez dans un instant.' );
    }
    try {
        return call_user_func( $callback );
    } finally {
        thaimoon_orders_unlock( $key, $token );
    }
}

/** Entrée unique : POST, nonce propre à la commande, session et propriété vérifiés. */
function thaimoon_orders_submit_request( $post, $method, $customer_id ) {
    if ( 'POST' !== $method || $customer_id <= 0 ) {
        return new WP_Error( 'forbidden', 'Connectez-vous pour demander l’annulation de votre commande.' );
    }
    $id = isset( $post['order_id'] ) && is_string( $post['order_id'] ) ? absint( $post['order_id'] ) : 0;
    $nonce = isset( $post['_thaimoon_cancel_nonce'] ) && is_string( $post['_thaimoon_cancel_nonce'] ) ? wp_unslash( $post['_thaimoon_cancel_nonce'] ) : '';
    if ( ! $id || ! wp_verify_nonce( $nonce, 'thaimoon_cancel_request_' . $id ) ) {
        return new WP_Error( 'nonce', 'Le formulaire a expiré. Rechargez la page avant de réessayer.' );
    }
    return thaimoon_orders_locked( $id, static function () use ( $id, $customer_id ) {
        $order = wc_get_order( $id );
        if ( ! thaimoon_orders_owned( $order, $customer_id ) ) {
            return new WP_Error( 'forbidden', 'Cette commande ne peut pas être modifiée depuis ce compte.' );
        }
        if ( ! thaimoon_orders_can_request( $order, $customer_id ) ) {
            return new WP_Error( 'unavailable', 'Une demande existe déjà ou cette commande ne peut plus faire l’objet d’une demande d’annulation.' );
        }
        $order->update_meta_data( '_thaimoon_cancellation_state', 'requested' );
        $order->update_meta_data( '_thaimoon_cancellation_requested_at', time() );
        $order->save();
        $order->add_order_note( 'Demande d’annulation envoyée par le client depuis son compte. La commande reste active en attente de la décision du restaurant.' );
        // Une erreur de transport ne doit jamais effacer une demande enregistrée.
        try {
            $emails = WC()->mailer()->get_emails();
            $notified = isset( $emails['Thaimoon_Email_Cancellation_Request'] ) && $emails['Thaimoon_Email_Cancellation_Request']->trigger( $order->get_id() );
        } catch ( Throwable $error ) {
            $notified = false;
        }
        $order->update_meta_data( '_thaimoon_cancellation_notification', $notified ? 'sent' : 'not_sent' );
        $order->save();
        if ( ! $notified ) {
            $order->add_order_note( 'La demande d’annulation est enregistrée, mais la notification email au restaurant n’a pas pu être envoyée ou est désactivée.' );
        }
        return $order;
    } );
}
add_action( 'admin_post_thaimoon_request_cancellation', 'thaimoon_orders_request_handler' );
add_action( 'admin_post_nopriv_thaimoon_request_cancellation', 'thaimoon_orders_request_handler' );
function thaimoon_orders_request_handler() {
    $result = thaimoon_orders_submit_request( $_POST, $_SERVER['REQUEST_METHOD'] ?? '', get_current_user_id() );
    if ( is_wp_error( $result ) ) {
        if ( in_array( $result->get_error_code(), array( 'forbidden', 'nonce' ), true ) ) {
            wp_die( esc_html( $result->get_error_message() ), 'Demande d’annulation', array( 'response' => 403 ) );
        }
        wc_add_notice( $result->get_error_message(), 'error' );
        wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
    } else {
        wp_safe_redirect( add_query_arg( 'annulation', 'suivi', $result->get_view_order_url() ) );
    }
    exit;
}

// Le lien Annuler natif ne permet pas de sauter la validation du restaurant.
add_filter( 'woocommerce_my_account_my_orders_actions', 'thaimoon_orders_actions', 20, 2 );
function thaimoon_orders_actions( $actions, $order ) {
    if ( ! is_account_page() || ! thaimoon_orders_owned( $order, get_current_user_id() ) ) {
        return $actions;
    }
    unset( $actions['cancel'] );
    if ( isset( $actions['view'] ) ) {
        $actions['view']['name'] = 'Détails';
    }
    return $actions;
}

// Remplacer seulement le rendu de Mon compte → Détails ; les autres pages restent natives.
add_action( 'wp', 'thaimoon_orders_view_callback' );
function thaimoon_orders_view_callback() {
    if ( is_account_page() && is_wc_endpoint_url( 'view-order' ) ) {
        remove_action( 'woocommerce_view_order', 'woocommerce_order_details_table', 10 );
        add_action( 'woocommerce_view_order', 'thaimoon_orders_summary', 10 );
    }
}
function thaimoon_orders_summary( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( thaimoon_orders_owned( $order, get_current_user_id() ) ) {
        wc_get_template( 'myaccount/order-summary.php', array( 'order' => $order ) );
    }
}

add_filter( 'woocommerce_email_classes', 'thaimoon_orders_email_class' );
function thaimoon_orders_email_class( $emails ) {
    require_once __DIR__ . '/emails/class-thaimoon-email-cancellation-request.php';
    $emails['Thaimoon_Email_Cancellation_Request'] = new Thaimoon_Email_Cancellation_Request();
    return $emails;
}

/** Décision via les Actions de commande natives, protégées par leurs contrôles admin. */
function thaimoon_orders_review_request( $order_id, $decision, $staff_id ) {
    if ( ! current_user_can( 'edit_shop_order', $order_id ) || $staff_id !== get_current_user_id() || ! in_array( $decision, array( 'approved', 'declined' ), true ) ) {
        return new WP_Error( 'forbidden', 'Vous ne pouvez pas traiter cette demande.' );
    }
    return thaimoon_orders_locked( $order_id, static function () use ( $order_id, $decision, $staff_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order instanceof WC_Order || 'requested' !== $order->get_meta( '_thaimoon_cancellation_state', true ) || ! $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) ) {
            return new WP_Error( 'unavailable', 'Cette demande ne peut plus être traitée.' );
        }
        if ( 'approved' === $decision ) {
            // WooCommerce gère la transition et la restitution du stock ; aucun double réassort.
            if ( ! $order->update_status( 'cancelled', 'Demande d’annulation du client acceptée par le restaurant.', true ) ) {
                return new WP_Error( 'status', 'La commande n’a pas pu être annulée. La demande reste à examiner.' );
            }
        }
        $order->update_meta_data( '_thaimoon_cancellation_state', $decision );
        $order->update_meta_data( '_thaimoon_cancellation_reviewed_at', time() );
        $order->update_meta_data( '_thaimoon_cancellation_reviewer_id', $staff_id );
        $order->save();
        if ( 'approved' === $decision ) {
            $order->add_order_note( 'Votre demande d’annulation a été acceptée. Si un paiement a été encaissé, le restaurant vous communiquera les informations concernant son remboursement.', 1, true );
        } else {
            $order->add_order_note( 'Votre demande d’annulation n’a pas été acceptée. Votre commande reste active. Vous pouvez contacter le restaurant pour plus d’informations.', 1, true );
        }
        return $order;
    } );
}
add_filter( 'woocommerce_order_actions', 'thaimoon_orders_admin_actions', 20, 2 );
function thaimoon_orders_admin_actions( $actions, $order = null ) {
    if ( $order instanceof WC_Order && 'requested' === $order->get_meta( '_thaimoon_cancellation_state', true ) && $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) ) {
        $actions['thaimoon_accept_cancellation'] = 'Accepter la demande d’annulation';
        $actions['thaimoon_decline_cancellation'] = 'Refuser la demande d’annulation';
    }
    return $actions;
}
add_action( 'woocommerce_order_action_thaimoon_accept_cancellation', 'thaimoon_orders_admin_accept' );
function thaimoon_orders_admin_accept( $order ) {
    $result = thaimoon_orders_review_request( $order->get_id(), 'approved', get_current_user_id() );
    if ( is_wp_error( $result ) && class_exists( 'WC_Admin_Meta_Boxes' ) ) {
        WC_Admin_Meta_Boxes::add_error( $result->get_error_message() );
    }
}
add_action( 'woocommerce_order_action_thaimoon_decline_cancellation', 'thaimoon_orders_admin_decline' );
function thaimoon_orders_admin_decline( $order ) {
    $result = thaimoon_orders_review_request( $order->get_id(), 'declined', get_current_user_id() );
    if ( is_wp_error( $result ) && class_exists( 'WC_Admin_Meta_Boxes' ) ) {
        WC_Admin_Meta_Boxes::add_error( $result->get_error_message() );
    }
}
add_action( 'add_meta_boxes', 'thaimoon_orders_meta_box' );
function thaimoon_orders_meta_box() {
    $screens = array( 'shop_order' );
    if ( function_exists( 'wc_get_page_screen_id' ) ) {
        $screens[] = wc_get_page_screen_id( 'shop-order' );
    }
    foreach ( array_unique( $screens ) as $screen ) {
        add_meta_box( 'thaimoon-cancellation', 'Demande d’annulation du client', 'thaimoon_orders_meta_box_content', $screen, 'side', 'high' );
    }
}
function thaimoon_orders_meta_box_content( $post_or_order ) {
    $order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
    if ( ! $order || ! current_user_can( 'edit_shop_order', $order->get_id() ) ) {
        return;
    }
    $state = $order->get_meta( '_thaimoon_cancellation_state', true );
    $labels = array( 'requested' => 'Demande reçue — à examiner', 'approved' => 'Demande acceptée', 'declined' => 'Demande refusée' );
    if ( 'requested' === $state && ! $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) ) {
        $labels['requested'] = 'Demande enregistrée — commande désormais ' . wc_get_order_status_name( $order->get_status() );
    }
    echo '<p><strong>' . esc_html( $labels[ $state ] ?? 'Aucune demande du client.' ) . '</strong></p>';
    if ( $state ) {
        $time = (int) $order->get_meta( '_thaimoon_cancellation_requested_at', true );
        echo '<p>' . esc_html( 'Reçue le ' . wp_date( 'd/m/Y à H:i', $time ) ) . '</p>';
    }
    if ( 'requested' === $state && $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) ) {
        echo '<p>Dans « Actions de commande », choisissez « Accepter » ou « Refuser la demande d’annulation », puis appliquez l’action.</p>';
    }
    if ( 'approved' === $state || $order->has_status( 'cancelled' ) ) {
        echo '<p>L’annulation ne rembourse pas le paiement. Si nécessaire, utilisez le bouton natif « Rembourser » et vérifiez si le stock a déjà été restitué.</p>';
    }
    if ( 'not_sent' === $order->get_meta( '_thaimoon_cancellation_notification', true ) ) {
        echo '<p>La notification email n’a pas été envoyée ou était désactivée. La demande reste bien enregistrée ici.</p>';
    }
}

// Signalement visible dès la liste des commandes, avec les deux stockages WooCommerce.
add_action( 'manage_shop_order_posts_custom_column', 'thaimoon_orders_admin_badge', 20, 2 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'thaimoon_orders_admin_badge', 20, 2 );
function thaimoon_orders_admin_badge( $column, $order_or_id ) {
    if ( 'order_status' !== $column ) {
        return;
    }
    $order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( $order_or_id );
    if ( $order && 'requested' === $order->get_meta( '_thaimoon_cancellation_state', true ) && $order->has_status( array( 'pending', 'failed', 'on-hold', 'processing' ) ) ) {
        echo '<p><strong>Annulation demandée</strong></p>';
    }
}
