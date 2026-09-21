<?php
/**
 * Template Name: Menü Sayfası
 * Description: Kategorili, filtreli ve aramalı dijital menü.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_order_url = ysf_localize_url( ysf_get_page_url_by_template( 'template-order.php' ) );
$ysf_page_id   = get_the_ID();
?>

<section class="ysf-page-hero">
	<div class="ysf-wrap">
		<?php ysf_breadcrumb(); ?>
		<h1><?php echo esc_html( ysf_field( $ysf_page_id, 'title' ) ); ?></h1>
		<?php $ysf_intro = ysf_field( $ysf_page_id, 'excerpt' ); ?>
		<?php if ( $ysf_intro ) : ?>
			<p><?php echo esc_html( wp_strip_all_tags( $ysf_intro ) ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="ysf-section ysf-section--tight">
	<div class="ysf-wrap">
		<?php get_template_part( 'template-parts/menu-catalog', null, array( 'show_cart' => false ) ); ?>

		<?php if ( ysf_has_translated_content( $ysf_page_id ) ) : ?>
			<div class="ysf-prose" style="margin-top:48px">
				<?php ysf_the_translated_content( $ysf_page_id ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ( $ysf_order_url && ysf_get_option( 'ysf_orders_enabled', true ) ) : ?>
<section class="ysf-section ysf-section--dark ysf-section--tight">
	<div class="ysf-wrap" style="text-align:center">
		<h2><?php ysf_e( 'cta_order' ); ?></h2>
		<p class="ysf-lead"><?php echo esc_html( ysf_option_i18n( 'ysf_order_note', '' ) ); ?></p>
		<a class="ysf-btn" href="<?php echo esc_url( $ysf_order_url ); ?>"><?php ysf_e( 'checkout' ); ?></a>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
