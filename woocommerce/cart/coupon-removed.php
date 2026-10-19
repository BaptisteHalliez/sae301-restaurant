<?php defined( 'ABSPATH' ) || exit; ?>
<div class="coupon-removal" data-coupon-removal="<?php echo esc_attr( $feedback['event'] ); ?>" role="status">
    <p class="coupon-removal__message" tabindex="-1">Votre code promotionnel a été désactivé.</p>
    <button type="button" class="coupon-removal__close" aria-label="Fermer la confirmation" data-coupon-removal-close hidden>
        <picture><source media="(max-width: 599px)" srcset="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/coupon-close-mobile.svg' ); ?>"><img class="coupon-removal__icon" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/coupon-close-desktop.svg' ); ?>" alt=""></picture>
    </button>
</div>
