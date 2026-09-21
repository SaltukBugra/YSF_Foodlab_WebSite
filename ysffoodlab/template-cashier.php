<?php
/**
 * Template Name: Kasiyer Uygulaması
 * Description: Masa hesaplarının tahsil edilmesi (yalnızca kasiyer ve yönetici).
 *
 * @package ysffoodlab
 */

get_header( 'staff' );

$ysf_user     = wp_get_current_user();
$ysf_can      = ysf_can_cashier();
$ysf_here     = get_permalink();
$ysf_logo_id  = get_theme_mod( 'custom_logo' );
?>

<?php if ( ! is_user_logged_in() ) : ?>

	<?php
	get_template_part(
		'template-parts/staff-login',
		null,
		array(
			'title'    => ysf_t( 'cash_name' ),
			'lead'     => ysf_t( 'staff_need_login' ),
			'redirect' => $ysf_here,
		)
	);
	?>

<?php elseif ( ! $ysf_can || ! ysf_floor_enabled() ) : ?>

	<section class="ysf-staff-auth">
		<div class="ysf-staff-auth__card">
			<h1><?php ysf_e( 'cash_name' ); ?></h1>
			<p><?php echo esc_html( ysf_floor_enabled() ? ysf_t( 'cash_forbidden' ) : ysf_t( 'pos_disabled' ) ); ?></p>
			<p>
				<a class="ysf-btn" href="<?php echo esc_url( ysf_account_url() ); ?>"><?php ysf_e( 'acc_nav' ); ?></a>
				<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( wp_logout_url( $ysf_here ) ); ?>"><?php ysf_e( 'acc_logout' ); ?></a>
			</p>
		</div>
	</section>

<?php else : ?>

	<div class="ysf-pos ysf-cash" data-ysf-cashier>
		<header class="ysf-staff-bar">
			<div class="ysf-staff-bar__brand">
				<?php if ( $ysf_logo_id ) : ?>
					<?php echo wp_get_attachment_image( $ysf_logo_id, 'thumbnail', false, array( 'class' => 'ysf-staff-bar__logo', 'alt' => '' ) ); ?>
				<?php endif; ?>
				<div>
					<p class="ysf-staff-bar__kicker"><?php bloginfo( 'name' ); ?></p>
					<h1><?php ysf_e( 'cash_name' ); ?></h1>
				</div>
			</div>
			<div class="ysf-staff-bar__meta">
				<span class="ysf-staff-bar__user"><?php echo esc_html( $ysf_user->display_name ); ?></span>
				<a class="ysf-staff-bar__link" href="<?php echo esc_url( wp_logout_url( $ysf_here ) ); ?>"><?php ysf_e( 'acc_logout' ); ?></a>
			</div>
		</header>

		<?php get_template_part( 'template-parts/staff-tabs', null, array( 'current' => 'cashier' ) ); ?>

		<div class="ysf-pos__flash" data-ysf-pos-flash hidden></div>

		<section class="ysf-pos__view is-active" data-ysf-view="floor">
			<div class="ysf-cash__summary" data-ysf-cash-summary>
				<div>
					<span><?php ysf_e( 'cash_open' ); ?></span>
					<strong data-ysf-cash-open>0</strong>
				</div>
				<div>
					<span><?php ysf_e( 'cash_open_total' ); ?></span>
					<strong data-ysf-cash-open-total><?php echo esc_html( ysf_price( 0 ) ); ?></strong>
				</div>
				<div>
					<span><?php ysf_e( 'cash_today_cash' ); ?></span>
					<strong data-ysf-cash-cash><?php echo esc_html( ysf_price( 0 ) ); ?></strong>
				</div>
				<div>
					<span><?php ysf_e( 'cash_today_card' ); ?></span>
					<strong data-ysf-cash-card><?php echo esc_html( ysf_price( 0 ) ); ?></strong>
				</div>
				<div>
					<span><?php ysf_e( 'cash_today' ); ?></span>
					<strong data-ysf-cash-today><?php echo esc_html( ysf_price( 0 ) ); ?></strong>
				</div>
			</div>
			<div class="ysf-pos__legend">
				<span data-status="empty"><?php ysf_e( 'pos_empty' ); ?></span>
				<span data-status="occupied"><?php ysf_e( 'pos_busy' ); ?></span>
				<span data-status="bill"><?php ysf_e( 'pos_bill' ); ?></span>
			</div>
			<div class="ysf-pos__grid" data-ysf-tables></div>
		</section>

		<section class="ysf-pos__view" data-ysf-view="table" hidden>
			<header class="ysf-pos__subhead">
				<button type="button" class="ysf-pos__back" data-ysf-back-floor><?php ysf_e( 'pos_back' ); ?></button>
				<div>
					<h2 data-ysf-table-title></h2>
					<p class="ysf-muted" data-ysf-table-sum></p>
				</div>
			</header>
			<div class="ysf-pos__tickets" data-ysf-tickets></div>
			<div class="ysf-pos__table-actions ysf-cash__pay">
				<button type="button" class="ysf-btn" data-ysf-pay="cash"><?php ysf_e( 'cash_pay_cash' ); ?></button>
				<button type="button" class="ysf-btn" data-ysf-pay="card"><?php ysf_e( 'cash_pay_card' ); ?></button>
				<button type="button" class="ysf-btn ysf-btn--ghost" data-ysf-back-floor><?php ysf_e( 'pos_back' ); ?></button>
			</div>
		</section>
	</div>

<?php endif; ?>

<?php
get_footer( 'staff' );
