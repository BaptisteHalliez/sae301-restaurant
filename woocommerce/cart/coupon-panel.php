<?php
defined( 'ABSPATH' ) || exit;
$feedback = thaimoon_coupon_feedback();
$applied = WC()->cart->get_applied_coupons();
$error = ! empty( $feedback ) && in_array( $feedback['state'], array( 'error', 'expired' ), true );
$value = $error ? $feedback['code'] : ( $applied ? end( $applied ) : '' );
?>
<div class="coupon commerce-coupon" data-coupon-panel>
    <label class="commerce-coupon__label" for="coupon_code">Avez-vous un code promo ?</label>
    <div class="commerce-coupon__entry">
        <input class="input-text commerce-coupon__input" type="text" name="coupon_code" id="coupon_code" form="thaimoon-cart-form" value="<?php echo esc_attr( $value ); ?>" placeholder="Code promotionnel" autocomplete="off" autocapitalize="none" spellcheck="false" <?php if ( $error ) : ?>aria-invalid="true" aria-describedby="commerce-coupon-error"<?php endif; ?>>
        <button class="button commerce-coupon__apply" type="submit" form="thaimoon-cart-form" name="apply_coupon" value="Appliquer" data-coupon-apply>Appliquer</button>
    </div>
    <?php if ( $error ) : ?><p class="commerce-coupon__error" id="commerce-coupon-error" role="alert"><?php echo esc_html( $feedback['message'] ); ?></p><?php endif; ?>
    <div class="commerce-coupon__states" aria-live="polite" aria-atomic="true">
    <?php foreach ( $applied as $code ) : ?>
        <div class="commerce-coupon__state">
            <span class="commerce-coupon__success"><?php echo esc_html( count( $applied ) > 1 ? $code . ' : Code activé !' : 'Code activé !' ); ?></span>
            <a class="woocommerce-remove-coupon commerce-coupon__remove" role="button" href="<?php echo esc_url( add_query_arg( 'remove_coupon', rawurlencode( $code ), wc_get_cart_url() ) ); ?>" data-coupon="<?php echo esc_attr( $code ); ?>" aria-label="<?php echo esc_attr( 'Désactiver le code ' . $code ); ?>">Désactiver ?</a>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php thaimoon_coupon_removal_notice(); ?>
