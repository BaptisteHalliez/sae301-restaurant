<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-checkout-review-order-table commerce-summary">
<h2 id="order_review_heading" class="commerce-summary__title">Récapitulatif</h2>
<?php thaimoon_commerce_shipping_summary(); ?>
<div class="checkout-items">
<?php do_action( 'woocommerce_review_order_before_cart_contents' );
foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
    $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
    $visible = apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key );
    if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) { continue; }
    $cart_item_name = WC()->cart->get_item_product_name( $cart_item, $_product ); ?>
    <div class="checkout-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
        <div class="checkout-item__image"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key ) ); ?></div>
        <div class="checkout-item__info">
            <div class="checkout-item__heading"><div class="checkout-item__name"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $cart_item_name, $cart_item, $cart_item_key ) ); ?></div><span class="checkout-item__price"><?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?></span></div>
            <p class="checkout-item__quantity"><?php echo wp_kses_post( apply_filters( 'woocommerce_checkout_cart_item_quantity', 'Qté : ' . esc_html( $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?></p>
            <?php echo wc_get_formatted_cart_item_data( $cart_item, false, $cart_item_name ); ?>
        </div>
    </div>
<?php endforeach; do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
</div>
<table class="shop_table commerce-totals">
<tfoot>

		<tr class="cart-subtotal">
			<th><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>

			<?php wc_cart_totals_shipping_html(); ?>

			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>

		<?php endif; ?>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php echo esc_html( thaimoon_coupon_label( $coupon ) ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<tr class="fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</tfoot>
</table>
<?php thaimoon_coupon_removal_notice(); ?>
<?php wc_get_template( 'checkout/place-order.php' ); ?>
</div>
