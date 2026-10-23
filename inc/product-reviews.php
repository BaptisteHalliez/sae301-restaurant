<?php
defined( 'ABSPATH' ) || exit;
add_filter( 'pre_option_woocommerce_review_rating_verification_required', 'thaimoon_product_reviews_require_purchase' );
function thaimoon_product_reviews_require_purchase() {
    return 'yes';
}

add_action( 'wp', 'thaimoon_product_reviews_setup' );
function thaimoon_product_reviews_setup() {
    if ( ! is_product() ) {
        return;
    }
    remove_action( 'woocommerce_review_before', 'woocommerce_review_display_gravatar', 10 );
    remove_action( 'woocommerce_review_meta', 'woocommerce_review_display_meta', 10 );
    remove_action( 'woocommerce_review_before_comment_meta', 'woocommerce_review_display_rating', 10 );
    add_action( 'woocommerce_review_meta', 'thaimoon_product_review_meta', 10 );
    add_action( 'woocommerce_review_before_comment_meta', 'thaimoon_product_review_rating', 10 );
}

function thaimoon_product_review_meta( $comment ) {
    echo '<h3 class="review-card__author">' . esc_html( get_comment_author( $comment ) ) . '</h3>';
    echo '<span class="screen-reader-text">' . esc_html( get_comment_date( wc_date_format(), $comment ) ) . '</span>';
    if ( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) && wc_review_is_from_verified_owner( $comment->comment_ID ) ) {
        echo '<span class="screen-reader-text">' . esc_html__( 'verified owner', 'woocommerce' ) . '</span>';
    }
}

function thaimoon_product_review_rating( $comment ) {
    $rating = min( 5, max( 0, (int) get_comment_meta( $comment->comment_ID, 'rating', true ) ) );
    if ( ! $rating || ! wc_review_ratings_enabled() ) {
        return;
    }
    echo '<span class="review-card__separator" aria-hidden="true">-</span>';
    echo '<span class="review-rating" role="img" aria-label="' . esc_attr( sprintf( __( 'Note : %s sur 5', 'thaimoon-restaurant' ), $rating ) ) . '">';
    for ( $star = 1; $star <= 5; $star++ ) {
        echo '<span class="review-rating__star' . ( $star > $rating ? ' review-rating__star--empty' : '' ) . '" aria-hidden="true"></span>';
    }
    echo '</span>';
}

add_filter( 'woocommerce_product_review_comment_form_args', 'thaimoon_product_review_form' );
function thaimoon_product_review_form( $args ) {
    $args['title_reply'] = __( 'Vous voulez laisser votre avis ?', 'thaimoon-restaurant' );
    $args['title_reply_before'] = '<h3 id="reply-title" class="comment-reply-title product-reviews__form-title">';
    $args['title_reply_after'] = '</h3>';
    $args['class_container'] = 'comment-respond product-reviews__respond';
    $args['class_form'] = 'comment-form product-reviews__form';
    $args['class_submit'] = 'submit product-reviews__submit';
    $args['label_submit'] = __( 'Soumettre', 'thaimoon-restaurant' );
    $args['submit_field'] = '<p class="form-submit product-reviews__actions">%1$s %2$s</p>';
    $args['comment_notes_before'] = '';
    if ( isset( $args['must_log_in'] ) ) {
        $args['must_log_in'] = str_replace( 'class="must-log-in"', 'class="must-log-in product-reviews__notice product-reviews__notice--restriction"', $args['must_log_in'] );
    }
    foreach ( array( 'author', 'email' ) as $key ) {
        if ( isset( $args['fields'][ $key ] ) ) {
            $args['fields'][ $key ] = str_replace( 'class="comment-form-' . $key . '"', 'class="comment-form-' . $key . ' product-reviews__identity"', $args['fields'][ $key ] );
        }
    }
    $args['comment_field'] = '';
    if ( wc_review_ratings_enabled() ) {
        $required = wc_review_ratings_required();
        $args['comment_field'] = '<div class="comment-form-rating product-reviews__rating"><label for="rating" id="comment-form-rating-label">' . esc_html__( 'Votre note', 'thaimoon-restaurant' ) . ( $required ? '<span class="required"> *</span>' : '' ) . '<span class="product-reviews__separator" aria-hidden="true">-</span></label>';
        $args['comment_field'] .= '<select class="product-reviews__select" name="rating" id="rating"' . ( $required ? ' required' : '' ) . '><option value="">' . esc_html__( 'Choisir une note', 'thaimoon-restaurant' ) . '</option>';
        $labels = array( 5 => 'Parfait', 4 => 'Bon', 3 => 'Moyen', 2 => 'Pas mal', 1 => 'Très mauvais' );
        foreach ( $labels as $value => $label ) {
            $args['comment_field'] .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
        }
        $args['comment_field'] .= '</select></div>';
    }
    $args['comment_field'] .= '<p class="comment-form-comment product-reviews__field"><label class="screen-reader-text" for="comment">' . esc_html__( 'Votre avis', 'thaimoon-restaurant' ) . '</label><textarea class="product-reviews__textarea" id="comment" name="comment" cols="45" rows="6" maxlength="65525" placeholder="' . esc_attr__( 'Commentaire', 'thaimoon-restaurant' ) . '" required></textarea></p>';
    return $args;
}
