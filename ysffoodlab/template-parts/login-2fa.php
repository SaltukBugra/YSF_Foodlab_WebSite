<?php
/**
 * Giriş formundaki 2FA alanları (yönetici).
 *
 * @package ysffoodlab
 */
?>
<div class="ysf-field ysf-field--full ysf-2fa-setup" data-ysf-2fa-setup hidden>
	<p><?php ysf_e( 'tfa_scan' ); ?></p>
	<p class="ysf-2fa-setup__qr">
		<img data-ysf-2fa-qr alt="" width="180" height="180" hidden>
	</p>
	<p class="ysf-muted"><?php ysf_e( 'tfa_manual' ); ?> <code data-ysf-2fa-secret></code></p>
</div>

<div class="ysf-field ysf-field--full" data-ysf-2fa hidden>
	<label for="ysf-login-2fa"><?php ysf_e( 'tfa_code' ); ?> <span class="ysf-req">*</span></label>
	<input type="text" id="ysf-login-2fa" name="ysf_2fa_code" inputmode="numeric" autocomplete="one-time-code" maxlength="10">
	<input type="hidden" name="ysf_2fa_ticket" value="">
</div>
