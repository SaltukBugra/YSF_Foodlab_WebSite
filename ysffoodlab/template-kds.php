<?php
/**
 * Template Name: Mutfak Ekranı
 * Description: Masalardan (ve isteğe bağlı online) alınan siparişlerin mutfakta görüntülenmesi.
 *
 * @package ysffoodlab
 */

get_header( 'staff' );

$ysf_user    = wp_get_current_user();
$ysf_can     = ysf_can_view_kds();
$ysf_here    = get_permalink();
$ysf_logo_id = get_theme_mod( 'custom_logo' );
?>

<?php if ( ! is_user_logged_in() ) : ?>

	<?php
	get_template_part(
		'template-parts/staff-login',
		null,
		array(
			'title'    => ysf_t( 'kds_name' ),
			'lead'     => ysf_t( 'staff_need_login' ),
			'redirect' => $ysf_here,
		)
	);
	?>

<?php elseif ( ! $ysf_can || ! ysf_floor_enabled() ) : ?>

	<section class="ysf-staff-auth">
		<div class="ysf-staff-auth__card">
			<h1><?php ysf_e( 'kds_name' ); ?></h1>
			<p><?php echo esc_html( ysf_floor_enabled() ? ysf_t( 'kds_forbidden' ) : ysf_t( 'pos_disabled' ) ); ?></p>
			<p>
				<a class="ysf-btn" href="<?php echo esc_url( ysf_account_url() ); ?>"><?php ysf_e( 'acc_nav' ); ?></a>
				<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( wp_logout_url( $ysf_here ) ); ?>"><?php ysf_e( 'acc_logout' ); ?></a>
			</p>
		</div>
	</section>

<?php else : ?>

	<div class="ysf-kds" data-ysf-kds>
		<header class="ysf-staff-bar ysf-staff-bar--kds">
			<div class="ysf-staff-bar__brand">
				<?php if ( $ysf_logo_id ) : ?>
					<?php echo wp_get_attachment_image( $ysf_logo_id, 'thumbnail', false, array( 'class' => 'ysf-staff-bar__logo', 'alt' => '' ) ); ?>
				<?php endif; ?>
				<div>
					<p class="ysf-staff-bar__kicker"><?php bloginfo( 'name' ); ?></p>
					<h1><?php ysf_e( 'kds_name' ); ?></h1>
				</div>
			</div>
			<div class="ysf-staff-bar__meta">
				<time class="ysf-kds__clock" data-ysf-kds-clock datetime=""></time>
				<button type="button" class="ysf-staff-bar__link" data-ysf-kds-sound aria-pressed="false">
					<?php ysf_e( 'kds_sound_off' ); ?>
				</button>
				<a class="ysf-staff-bar__link" href="<?php echo esc_url( wp_logout_url( $ysf_here ) ); ?>"><?php ysf_e( 'acc_logout' ); ?></a>
			</div>
		</header>

		<?php get_template_part( 'template-parts/staff-tabs', null, array( 'current' => 'kds' ) ); ?>

		<div class="ysf-kds__queue">
			<header>
				<h2><?php ysf_e( 'kds_queue' ); ?></h2>
				<span data-ysf-kds-count>0</span>
			</header>
			<div class="ysf-kds__list" data-ysf-kds-list></div>
			<p class="ysf-kds__empty" data-ysf-kds-empty hidden><?php ysf_e( 'kds_empty' ); ?></p>
		</div>
	</div>

<?php endif; ?>

<?php
get_footer( 'staff' );
