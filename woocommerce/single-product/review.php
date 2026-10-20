<?php
/**
 * Carte d'avis Figma V1.5. La fermeture du li est gérée par le walker WordPress.
 * @package WooCommerce\Templates
 * @version 2.6.0
 */
defined( 'ABSPATH' ) || exit;
?>
<li <?php comment_class( 'review-card' ); ?> id="li-comment-<?php comment_ID(); ?>">
    <article id="comment-<?php comment_ID(); ?>" class="comment_container review-card__body">
        <?php do_action( 'woocommerce_review_before', $comment ); ?>
        <header class="review-card__header">
            <?php do_action( 'woocommerce_review_meta', $comment ); ?>
            <?php do_action( 'woocommerce_review_before_comment_meta', $comment ); ?>
        </header>
        <?php if ( '0' === (string) $comment->comment_approved ) : ?>
            <p class="review-card__moderation"><?php esc_html_e( 'Your review is awaiting approval', 'woocommerce' ); ?></p>
        <?php endif; ?>
        <?php do_action( 'woocommerce_review_before_comment_text', $comment ); ?>
        <div class="review-card__text"><?php do_action( 'woocommerce_review_comment_text', $comment ); ?></div>
        <?php do_action( 'woocommerce_review_after_comment_text', $comment ); ?>
    </article>
