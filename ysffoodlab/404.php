<?php
/**
 * 404 sayfası.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_menu_url = ysf_localize_url( ysf_get_page_url_by_template( 'template-menu.php' ) );
?>

<section class="ysf-section ysf-section--dark" style="text-align:center">
	<div class="ysf-wrap">
		<span class="ysf-eyebrow" style="justify-content:center">404</span>
		<h1><?php ysf_e( 'notfound_title' ); ?></h1>
		<p class="ysf-lead" style="max-width:520px;margin-inline:auto"><?php ysf_e( 'notfound_text' ); ?></p>

		<div class="ysf-btn-row" style="justify-content:center;margin-top:28px">
			<a class="ysf-btn" href="<?php echo esc_url( ysf_localize_url( home_url( '/' ) ) ); ?>"><?php ysf_e( 'back_home' ); ?></a>
			<?php if ( $ysf_menu_url ) : ?>
				<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( $ysf_menu_url ); ?>"><?php ysf_e( 'cta_menu' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="ysf-section ysf-section--tight">
	<div class="ysf-wrap" style="max-width:520px">
		<?php get_search_form(); ?>
	</div>
</section>

<?php
get_footer();
