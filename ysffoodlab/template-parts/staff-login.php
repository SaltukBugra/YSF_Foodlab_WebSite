<?php
/**
 * Personel girişi (garson / mutfak ekranı).
 *
 * @package ysffoodlab
 */

$ysf_redirect = isset( $args['redirect'] ) ? $args['redirect'] : '';
$ysf_title    = isset( $args['title'] ) ? $args['title'] : ysf_t( 'staff_login' );
$ysf_lead     = isset( $args['lead'] ) ? $args['lead'] : ysf_t( 'staff_need_login' );
$ysf_acc      = ysf_account_url();
?>
<section class="ysf-staff-auth">
	<div class="ysf-staff-auth__card">
		<p class="ysf-eyebrow"><?php bloginfo( 'name' ); ?></p>
		<h1><?php echo esc_html( $ysf_title ); ?></h1>
		<p class="ysf-muted"><?php echo esc_html( $ysf_lead ); ?></p>

		<form class="ysf-form" data-ysf-form="login" novalidate>
			<?php if ( $ysf_redirect ) : ?>
				<input type="hidden" name="redirect" value="<?php echo esc_url( $ysf_redirect ); ?>">
			<?php endif; ?>

			<div class="ysf-field ysf-field--full">
				<label for="ysf-login-user"><?php ysf_e( 'acc_login_or_phone' ); ?> <span class="ysf-req">*</span></label>
				<input type="text" id="ysf-login-user" name="login" required autocomplete="username">
			</div>

			<div class="ysf-field ysf-field--full">
				<label for="ysf-login-pass"><?php ysf_e( 'acc_password' ); ?> <span class="ysf-req">*</span></label>
				<input type="password" id="ysf-login-pass" name="password" required autocomplete="current-password">
			</div>

			<label class="ysf-check">
				<input type="checkbox" name="remember" value="1" checked>
				<span><?php ysf_e( 'acc_remember' ); ?></span>
			</label>

			<?php get_template_part( 'template-parts/login-2fa' ); ?>

			<label class="ysf-hp" aria-hidden="true">
				<?php esc_html_e( 'Bu alanı boş bırakın', 'ysffoodlab' ); ?>
				<input type="text" name="ysf_hp" tabindex="-1" autocomplete="off">
			</label>

			<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

			<div class="ysf-field ysf-field--full">
				<button type="submit" class="ysf-btn ysf-btn--block" data-ysf-submit>
					<?php ysf_e( 'acc_login_btn' ); ?>
				</button>
			</div>
		</form>

		<?php if ( $ysf_acc ) : ?>
			<p class="ysf-muted ysf-staff-auth__alt">
				<a href="<?php echo esc_url( $ysf_acc ); ?>"><?php ysf_e( 'acc_nav' ); ?></a>
			</p>
		<?php endif; ?>
	</div>
</section>
