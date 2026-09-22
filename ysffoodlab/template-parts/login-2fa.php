<?php
/**
 * Giriş formundaki 2FA alanları (yönetici).
 *
 * @package ysffoodlab
 */
?>
<div class="ysf-2fa-panel" data-ysf-2fa-panel hidden>
	<div class="ysf-field ysf-field--full" data-ysf-2fa-email hidden>
		<label for="ysf-login-2fa-email"><?php ysf_e( 'tfa_email_code' ); ?> <span class="ysf-req">*</span></label>
		<input type="text" id="ysf-login-2fa-email" name="ysf_2fa_email_code" inputmode="numeric" autocomplete="one-time-code" maxlength="10">
		<p class="ysf-muted" data-ysf-2fa-email-hint></p>
	</div>

	<div class="ysf-field ysf-field--full ysf-2fa-setup" data-ysf-2fa-setup hidden>
		<p class="ysf-2fa-setup__title"><?php ysf_e( 'tfa_scan' ); ?></p>
		<div class="ysf-2fa-setup__qr" data-ysf-2fa-qr-box></div>
		<p class="ysf-muted"><?php ysf_e( 'tfa_manual' ); ?> <code data-ysf-2fa-secret></code></p>
	</div>

	<div class="ysf-field ysf-field--full" data-ysf-2fa hidden>
		<label for="ysf-login-2fa"><?php ysf_e( 'tfa_code' ); ?> <span class="ysf-req">*</span></label>
		<input type="text" id="ysf-login-2fa" name="ysf_2fa_code" inputmode="numeric" autocomplete="one-time-code" maxlength="10">
		<input type="hidden" name="ysf_2fa_ticket" value="">
	</div>
</div>
