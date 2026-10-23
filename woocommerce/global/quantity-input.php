<?php
/**
 * @version 10.1.0
 * @var bool   $readonly If the input should be set to readonly mode.
 * @var string $type     The input type attribute.
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_product() || ! $GLOBALS['product']->is_type( array( 'simple', 'variable' ) ) ) {
    require WC()->plugin_path() . '/templates/global/quantity-input.php';
    return;
}

$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s quantity', 'woocommerce' ), wp_strip_all_tags( $args['product_name'] ) ) : esc_html__( 'Quantity', 'woocommerce' );

?>
<div class="quantity">
<div class="quantity-control">
    <?php if ( 'hidden' !== $type && ! $readonly ) : ?>
    <button type="button" class="quantity-control__button" data-quantity-step="1" aria-label="Augmenter la quantité" hidden><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/quantity-add.svg' ); ?>" width="26" height="26" alt=""></button>
    <?php endif; ?>
	<?php
	do_action( 'woocommerce_before_quantity_input_field' );
	?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $label ); ?></label>
	<input
		type="<?php echo esc_attr( $type ); ?>"
		<?php echo $readonly ? 'readonly="readonly"' : ''; ?>
		id="<?php echo esc_attr( $input_id ); ?>"
		class="<?php echo esc_attr( join( ' ', array_merge( (array) $classes, array( 'quantity-control__input' ) ) ) ); ?>"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $input_value ); ?>"
		aria-label="<?php esc_attr_e( 'Product quantity', 'woocommerce' ); ?>"
		<?php if ( in_array( $type, array( 'text', 'search', 'tel', 'url', 'email', 'password' ), true ) ) : ?>
			size="4"
		<?php endif; ?>
		min="<?php echo esc_attr( $min_value ); ?>"
		<?php if ( 0 < $max_value ) : ?>
			max="<?php echo esc_attr( $max_value ); ?>"
		<?php endif; ?>
		<?php if ( ! $readonly ) : ?>
			step="<?php echo esc_attr( $step ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			inputmode="<?php echo esc_attr( $inputmode ); ?>"
			autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'on' ); ?>"
		<?php endif; ?>
	/>
	<?php
	do_action( 'woocommerce_after_quantity_input_field' );
	?>
<?php if ( 'hidden' !== $type && ! $readonly ) : ?>
    <button type="button" class="quantity-control__button" data-quantity-step="-1" aria-label="Diminuer la quantité" hidden><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/quantity-remove.svg' ); ?>" width="26" height="26" alt=""></button>
    <?php endif; ?>
</div>
</div>
