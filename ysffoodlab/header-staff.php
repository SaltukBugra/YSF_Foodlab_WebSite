<?php
/**
 * Personel uygulamaları: tam ekran kabuk (genel site başlığı yok).
 *
 * @package ysffoodlab
 */

$ysf_is_kds     = is_page_template( YSF_KDS_TEMPLATE );
$ysf_is_cashier = is_page_template( YSF_CASHIER_TEMPLATE );

if ( $ysf_is_kds ) {
	$ysf_app_name = ysf_t( 'kds_name' );
	$ysf_kind     = 'kds';
} elseif ( $ysf_is_cashier ) {
	$ysf_app_name = ysf_t( 'cash_name' );
	$ysf_kind     = 'cashier';
} else {
	$ysf_app_name = ysf_t( 'pos_name' );
	$ysf_kind     = 'waiter';
}

$ysf_theme    = '#14100d';
$ysf_manifest = add_query_arg( 'ysf_manifest', $ysf_kind, get_permalink() );
$ysf_icon     = get_site_icon_url( 180 );
$ysf_body     = array( 'ysf-staff-app', 'ysf-staff-app--' . $ysf_kind );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="theme-color" content="<?php echo esc_attr( $ysf_theme ); ?>">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $ysf_app_name ); ?>">
	<meta name="robots" content="noindex,nofollow">
	<link rel="manifest" href="<?php echo esc_url( $ysf_manifest ); ?>">
	<?php if ( $ysf_icon ) : ?>
		<link rel="apple-touch-icon" href="<?php echo esc_url( $ysf_icon ); ?>">
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class( $ysf_body ); ?>>
<?php wp_body_open(); ?>
<main id="ysf-content" class="ysf-staff">
