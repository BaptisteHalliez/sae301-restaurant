<?php
/**
 * En-tête commun Thaï Moon : tables compatibles email, logo et titre WooCommerce.
 * @package WooCommerce\Templates\Emails
 * @version 10.7.0
 */
defined( 'ABSPATH' ) || exit;
$thaimoon_email = require __DIR__ . '/thaimoon-email-settings.php';
$store_name = $store_name ?? get_bloginfo( 'name', 'display' );
$header_image_url = apply_filters( 'woocommerce_email_header_image_url', home_url() );
$img = get_option( 'woocommerce_email_header_image' );
$logo_width = absint( get_option( 'woocommerce_email_header_image_width', 208 ) );
if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
    $pending_image = get_transient( 'woocommerce_email_header_image' );
    $img = false !== $pending_image ? $pending_image : $img;
    $pending_width = get_transient( 'woocommerce_email_header_image_width' );
    $logo_width = false !== $pending_width ? absint( $pending_width ) : $logo_width;
}
$logo_width = max( 60, min( 280, $logo_width ?: 208 ) );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $store_name ); ?></title>
</head>
<body class="thaimoon-email" style="margin:0; padding:0;" bgcolor="<?php echo esc_attr( $thaimoon_email['colors']['background'] ); ?>">
<table id="outer_wrapper" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
<tr><td align="center" class="email-shell">
<!--[if mso]><table role="presentation" width="600" border="0" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
<table id="inner_wrapper" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr><td id="template_header_image" class="email-brand" bgcolor="<?php echo esc_attr( $thaimoon_email['navbar'] ); ?>">
        <?php if ( $header_image_url ) : ?><a class="email-brand__link" href="<?php echo esc_url( $header_image_url ); ?>" target="_blank"><?php endif; ?>
        <?php if ( $img ) : ?>
            <img class="email-brand__logo" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" width="<?php echo esc_attr( $logo_width ); ?>" style="width:<?php echo esc_attr( $logo_width ); ?>px; max-width:100%; height:auto; margin:0;">
        <?php else : ?>
            <img class="email-brand__logo" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/wordmark.png' ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" width="166" height="30" style="width:166px; height:30px; margin:0;">
        <?php endif; ?>
        <?php if ( $header_image_url ) : ?></a><?php endif; ?>
        <p class="email-brand__tagline">Restaurant Thaïlandais · Click &amp; Collect</p>
    </td></tr>
    <tr><td>
        <table id="template_container" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr><td>
                <table id="template_header" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr><td id="header_wrapper"><h1><?php echo esc_html( $email_heading ); ?></h1></td></tr>
                </table>
            </td></tr>
            <tr><td>
                <table id="template_body" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr><td id="body_content" valign="top">
                        <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                            <tr><td id="body_content_inner_cell" valign="top"><div id="body_content_inner">
