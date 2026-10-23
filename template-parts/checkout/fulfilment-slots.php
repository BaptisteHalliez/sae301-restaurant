<?php
defined( 'ABSPATH' ) || exit;
$mode = $args['mode'];
$available = $args['available'];
$date = $args['date'];
$slot = $args['slot'];
$needs_shipping = WC()->cart && WC()->cart->needs_shipping();
$title = 'pickup' === $mode ? __( 'Jour et créneau de retrait', 'restaurant' ) : __( 'Jour et créneau de livraison', 'restaurant' );
?>
<section class="checkout-fulfilment" aria-labelledby="thaimoon-fulfilment-title" <?php echo $needs_shipping ? '' : 'hidden'; ?>>
    <h3 class="checkout-fulfilment__title" id="thaimoon-fulfilment-title"><?php echo esc_html( $title ); ?></h3>
    <?php if ( ! $mode ) : ?>
        <p class="checkout-fulfilment__message" role="status"><?php esc_html_e( 'Choisissez votre mode d’expédition pour afficher les jours et créneaux disponibles.', 'restaurant' ); ?></p>
    <?php elseif ( ! $available ) : ?>
        <p class="checkout-fulfilment__message" role="status"><?php echo esc_html( $args['settings'] ? __( 'Aucun créneau n’est disponible pour ce mode sur la période proposée. Contactez le restaurant ou choisissez un autre mode d’expédition.', 'restaurant' ) : __( 'Les créneaux de ce mode ne sont pas encore configurés. Merci de contacter le restaurant.', 'restaurant' ) ); ?></p>
    <?php else : ?>
        <p class="checkout-fulfilment__hint" id="thaimoon-fulfilment-hint"><?php echo esc_html( sprintf( __( 'Choisissez un jour, puis un horaire. Délai de préparation : %d minutes. Horaires locaux du restaurant.', 'restaurant' ), $args['settings']['delay'] ) ); ?></p>
        <input type="hidden" name="thaimoon_fulfilment_mode" value="<?php echo esc_attr( $mode ); ?>">
        <div class="checkout-fulfilment__fields">
            <p class="checkout-fulfilment__field">
                <label class="checkout-fulfilment__label" for="thaimoon-fulfilment-date"><?php esc_html_e( 'Jour', 'restaurant' ); ?> <span aria-hidden="true">*</span></label>
                <select class="checkout-fulfilment__select" name="thaimoon_fulfilment_date" id="thaimoon-fulfilment-date" required aria-describedby="thaimoon-fulfilment-hint">
                    <option value=""><?php esc_html_e( 'Choisir un jour', 'restaurant' ); ?></option>
                    <?php foreach ( $available as $value => $day ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $date, $value ); ?>><?php echo esc_html( $day['label'] ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="checkout-fulfilment__field">
                <label class="checkout-fulfilment__label" for="thaimoon-fulfilment-slot"><?php esc_html_e( 'Créneau horaire', 'restaurant' ); ?> <span aria-hidden="true">*</span></label>
                <select class="checkout-fulfilment__select" name="thaimoon_fulfilment_slot" id="thaimoon-fulfilment-slot" required aria-describedby="thaimoon-fulfilment-hint" data-empty-label="<?php esc_attr_e( 'Choisir un créneau', 'restaurant' ); ?>" data-day-label="<?php esc_attr_e( 'Choisissez d’abord un jour', 'restaurant' ); ?>">
                    <option value=""><?php esc_html_e( 'Choisir un créneau', 'restaurant' ); ?></option>
                    <?php foreach ( $available as $day_date => $day ) : ?>
                        <optgroup label="<?php echo esc_attr( $day['label'] ); ?>">
                            <?php foreach ( $day['slots'] as $value => $label ) : $combined = $day_date . '|' . $value; ?>
                                <option value="<?php echo esc_attr( $combined ); ?>" <?php selected( $slot, $combined ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </p>
        </div>
        <noscript><p class="checkout-fulfilment__hint"><?php esc_html_e( 'Dans la seconde liste, choisissez un horaire du même jour que celui sélectionné dans la première.', 'restaurant' ); ?></p></noscript>
        <script type="application/json" class="checkout-fulfilment__data"><?php echo wp_json_encode( $available, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
    <?php endif; ?>
</section>
