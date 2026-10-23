<?php
defined( 'ABSPATH' ) || exit;
$member = isset( $args['member'] ) ? $args['member'] : null;
if ( ! $member instanceof WP_Post || 'membre' !== $member->post_type || 'publish' !== $member->post_status || post_password_required( $member ) ) {
    return;
}
$name = get_the_title( $member );
$role = thaimoon_member_card_role( $member->ID );
$description = wp_trim_words( thaimoon_member_presentation( $member->ID ), 50, '…' );
$heading = isset( $args['heading_level'] ) && 3 === $args['heading_level'] ? 'h3' : 'h2';
?>
<a class="member-card" href="<?php echo esc_url( get_permalink( $member ) ); ?>">
    <?php if ( has_post_thumbnail( $member ) ) : ?>
        <?php echo get_the_post_thumbnail( $member, 'medium', array( 'class' => 'member-card__portrait', 'alt' => $name, 'loading' => 'lazy' ) ); ?>
    <?php endif; ?>
    <div class="member-card__content">
        <<?php echo $heading; ?> class="member-card__name"><?php echo esc_html( $name . ( $role ? ' - ' . $role : '' ) ); ?></<?php echo $heading; ?>>
        <?php if ( '' !== $description ) : ?><p class="member-card__description"><?php echo esc_html( $description ); ?></p><?php endif; ?>
    </div>
</a>
