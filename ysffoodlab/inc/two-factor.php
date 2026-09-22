<?php
/**
 * Yönetici hesabı için TOTP iki adımlı doğrulama.
 *
 * Yalnız manage_options yetkisi olan kullanıcılar (wp-admin).
 * Garson, mutfak, kasiyer ve üyeler etkilenmez.
 * Yönetici oturumu, authenticator kurulup kod doğrulanmadan açılmaz.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Authenticator karekodunu sitede üretir (dış servis yok).
 *
 * @param string $text otpauth bağlantısı.
 * @return string
 */
function ysf_qr_svg( $text ) {
	$text = (string) $text;

	if ( '' === $text ) {
		return '';
	}

	if ( ! class_exists( 'YSF_QRCode' ) ) {
		$file = YSF_DIR . '/inc/lib-qrcode.php';

		if ( ! is_readable( $file ) ) {
			return '';
		}

		require_once $file;
	}

	if ( ! class_exists( 'YSF_QRCode' ) ) {
		return '';
	}

	try {
		return YSF_QRCode::svg( $text, array( 's' => 'qrm' ) );
	} catch ( Exception $e ) {
		return '';
	} catch ( Throwable $e ) {
		return '';
	}
}

/**
 * Aynı siteden karekod adresi.
 *
 * @param string $ticket 2FA bileti.
 * @return string
 */
function ysf_2fa_qr_ajax_url( $ticket ) {
	$ticket = (string) $ticket;

	if ( '' === $ticket ) {
		return '';
	}

	return add_query_arg(
		array(
			'action' => 'ysf_2fa_qr',
			't'      => $ticket,
		),
		admin_url( 'admin-ajax.php' )
	);
}

/**
 * Bu kullanıcı yönetici 2FA kapsamına girer mi?
 *
 * @param WP_User|int $user Kullanıcı.
 * @return bool
 */
function ysf_user_needs_2fa( $user ) {
	$user = $user instanceof WP_User ? $user : get_userdata( (int) $user );

	return $user && $user->exists() && user_can( $user, 'manage_options' );
}

/**
 * 2FA bu hesapta açık mı?
 *
 * @param int $user_id Kullanıcı.
 * @return bool
 */
function ysf_2fa_enabled( $user_id ) {
	$secret = (string) get_user_meta( (int) $user_id, '_ysf_2fa_secret', true );

	return strlen( $secret ) >= 16;
}

/**
 * Karekod / elle giriş için TOTP anahtarı.
 *
 * Kuruluysa kayıtlı anahtar, değilse bekleyen kurulum anahtarı.
 *
 * @param WP_User $user Kullanıcı.
 * @return string
 */
function ysf_2fa_enroll_secret( $user ) {
	if ( ! ( $user instanceof WP_User ) ) {
		return '';
	}

	if ( ysf_2fa_enabled( $user->ID ) ) {
		return (string) get_user_meta( $user->ID, '_ysf_2fa_secret', true );
	}

	return ysf_2fa_ensure_pending( $user );
}

/**
 * 2FA biletini okur.
 *
 * @param string $ticket Bilet.
 * @return array
 */
function ysf_2fa_ticket_get( $ticket ) {
	$ticket = (string) $ticket;

	if ( '' === $ticket ) {
		return array();
	}

	$data = get_transient( 'ysf_2fa_t_' . $ticket );

	return is_array( $data ) ? $data : array();
}

/**
 * 2FA biletini kaydeder.
 *
 * @param string $ticket Bilet.
 * @param array  $data   Veri.
 */
function ysf_2fa_ticket_save( $ticket, $data ) {
	set_transient( 'ysf_2fa_t_' . $ticket, $data, 10 * MINUTE_IN_SECONDS );
}

/**
 * 2FA meydan okumasını iptal eder.
 *
 * @param string $ticket Bilet.
 */
function ysf_2fa_ticket_abort( $ticket ) {
	$ticket = (string) $ticket;

	if ( '' !== $ticket ) {
		delete_transient( 'ysf_2fa_t_' . $ticket );
	}

	unset( $GLOBALS['ysf_2fa_challenge'] );
}

/**
 * E-postayı gizler.
 *
 * @param string $email E-posta.
 * @return string
 */
function ysf_mask_email( $email ) {
	$email = (string) $email;
	$at    = strpos( $email, '@' );

	if ( false === $at || $at < 1 ) {
		return $email;
	}

	$local = substr( $email, 0, $at );
	$domain = substr( $email, $at );
	$keep  = min( 2, strlen( $local ) );

	return substr( $local, 0, $keep ) . '***' . $domain;
}

/**
 * Yönetici 2FA e-posta kodu gönderir.
 *
 * @param WP_User $user Kullanıcı.
 * @param string  $code Kod.
 * @return bool
 */
function ysf_send_2fa_email( $user, $code ) {
	if ( ! ( $user instanceof WP_User ) || ! is_email( $user->user_email ) || '' === (string) $code ) {
		return false;
	}

	return ysf_send_notification(
		$user->user_email,
		sprintf(
			/* translators: %s: site adı. */
			__( '%s — yönetici doğrulama kodu', 'ysffoodlab' ),
			get_bloginfo( 'name' )
		),
		array(
			ysf_t( 'acc_username' )    => $user->user_login,
			ysf_t( 'acc_verify_code' ) => $code,
		),
		sprintf( ysf_t( 'tfa_email_mail' ), $code )
	);
}

/**
 * Kurulum için e-posta adımını başlatır.
 *
 * @param WP_User $user Kullanıcı.
 * @return array {ctx, sent, code, ticket}
 */
function ysf_2fa_start_email_challenge( $user ) {
	$ctx    = ysf_2fa_remember_challenge( $user, true );
	$ticket = isset( $ctx['ticket'] ) ? $ctx['ticket'] : '';
	$data   = ysf_2fa_ticket_get( $ticket );
	$code   = ysf_otp_make();
	$data['email_code'] = $code;
	$data['email_ok']   = false;
	ysf_2fa_ticket_save( $ticket, $data );

	return array(
		'ctx'    => $ctx,
		'ticket' => $ticket,
		'code'   => $code,
		'sent'   => ysf_send_2fa_email( $user, $code ),
	);
}

/**
 * Oturum açık yönetici henüz 2FA kurmadı mı?
 *
 * @return bool
 */
function ysf_2fa_incomplete_admin() {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$user = wp_get_current_user();

	return ysf_user_needs_2fa( $user ) && ! ysf_2fa_enabled( $user->ID );
}

/**
 * Base32 kodlar.
 *
 * @param string $data Ham bayt.
 * @return string
 */
function ysf_base32_encode( $data ) {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$buffer   = 0;
	$bits     = 0;
	$out      = '';
	$len      = strlen( $data );

	for ( $i = 0; $i < $len; $i++ ) {
		$buffer = ( $buffer << 8 ) | ord( $data[ $i ] );
		$bits  += 8;

		while ( $bits >= 5 ) {
			$bits -= 5;
			$out  .= $alphabet[ ( $buffer >> $bits ) & 31 ];
		}
	}

	if ( $bits > 0 ) {
		$out .= $alphabet[ ( $buffer << ( 5 - $bits ) ) & 31 ];
	}

	return $out;
}

/**
 * Base32 çözer.
 *
 * @param string $b32 Anahtar.
 * @return string
 */
function ysf_base32_decode( $b32 ) {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$b32      = strtoupper( preg_replace( '/[^A-Z2-7]/', '', (string) $b32 ) );
	$buffer   = 0;
	$bits     = 0;
	$out      = '';
	$len      = strlen( $b32 );

	for ( $i = 0; $i < $len; $i++ ) {
		$pos = strpos( $alphabet, $b32[ $i ] );

		if ( false === $pos ) {
			continue;
		}

		$buffer = ( $buffer << 5 ) | $pos;
		$bits  += 5;

		if ( $bits >= 8 ) {
			$bits -= 8;
			$out  .= chr( ( $buffer >> $bits ) & 0xFF );
		}
	}

	return $out;
}

/**
 * Verilen zaman dilimi için 6 haneli TOTP.
 *
 * @param string $secret Base32 gizli anahtar.
 * @param int    $slice  30 sn dilimi.
 * @return string
 */
function ysf_totp_at( $secret, $slice ) {
	$key  = ysf_base32_decode( $secret );
	$time = pack( 'N*', 0 ) . pack( 'N*', $slice );
	$hash = hash_hmac( 'sha1', $time, $key, true );
	$off  = ord( substr( $hash, -1 ) ) & 0x0F;
	$num  = unpack( 'N', substr( $hash, $off, 4 ) );
	$num  = isset( $num[1] ) ? ( $num[1] & 0x7FFFFFFF ) : 0;

	return str_pad( (string) ( $num % 1000000 ), 6, '0', STR_PAD_LEFT );
}

/**
 * TOTP veya yedek kodu doğrular.
 *
 * @param int         $user_id Kullanıcı.
 * @param string      $code    6 hane veya yedek kod.
 * @param string|null $secret  Boşsa kayıtlı anahtar. Verilirse yalnızca TOTP.
 * @return bool
 */
function ysf_2fa_verify( $user_id, $code, $secret = null ) {
	$code       = strtoupper( preg_replace( '/\s+/', '', (string) $code ) );
	$use_stored = ( null === $secret );
	$secret     = $use_stored ? (string) get_user_meta( (int) $user_id, '_ysf_2fa_secret', true ) : (string) $secret;

	if ( 6 === strlen( $code ) && ctype_digit( $code ) && strlen( $secret ) >= 16 ) {
		$slice = (int) floor( time() / 30 );

		for ( $i = -1; $i <= 1; $i++ ) {
			if ( hash_equals( ysf_totp_at( $secret, $slice + $i ), $code ) ) {
				return true;
			}
		}
	}

	if ( ! $use_stored ) {
		return false;
	}

	$backups = get_user_meta( (int) $user_id, '_ysf_2fa_backups', true );
	$backups = is_array( $backups ) ? $backups : array();

	foreach ( $backups as $index => $hash ) {
		if ( wp_check_password( $code, $hash ) ) {
			unset( $backups[ $index ] );
			update_user_meta( (int) $user_id, '_ysf_2fa_backups', array_values( $backups ) );
			return true;
		}
	}

	return false;
}

/**
 * Yeni yedek kod üretir (düz metin listesi).
 *
 * @param int $user_id Kullanıcı.
 * @return string[]
 */
function ysf_2fa_make_backups( $user_id ) {
	$plain  = array();
	$hashes = array();

	for ( $i = 0; $i < 8; $i++ ) {
		$code     = strtoupper( wp_generate_password( 8, false, false ) );
		$plain[]  = $code;
		$hashes[] = wp_hash_password( $code );
	}

	update_user_meta( (int) $user_id, '_ysf_2fa_backups', $hashes );

	return $plain;
}

/**
 * otpauth bağlantısı.
 *
 * @param WP_User $user   Kullanıcı.
 * @param string  $secret Anahtar.
 * @return string
 */
function ysf_2fa_otpauth( $user, $secret ) {
	$issuer = rawurlencode( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$label  = rawurlencode( $issuer . ':' . $user->user_login );

	return sprintf( 'otpauth://totp/%1$s?secret=%2$s&issuer=%3$s&period=30&digits=6', $label, $secret, $issuer );
}

/**
 * Karekod adresi.
 *
 * @param string $otpauth otpauth bağlantısı.
 * @return string
 */
function ysf_2fa_qr_url( $otpauth ) {
	return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&ecc=M&data=' . rawurlencode( $otpauth );
}

/**
 * Bekleyen kurulum anahtarını üretir veya döner.
 *
 * @param WP_User $user Kullanıcı.
 * @return string
 */
function ysf_2fa_ensure_pending( $user ) {
	$pending = (string) get_user_meta( $user->ID, '_ysf_2fa_pending', true );

	if ( strlen( $pending ) < 16 ) {
		$pending = ysf_base32_encode( random_bytes( 20 ) );
		update_user_meta( $user->ID, '_ysf_2fa_pending', $pending );
	}

	return $pending;
}

/**
 * 2FA'yı açar ve yedek kod üretir.
 *
 * @param int    $user_id Kullanıcı.
 * @param string $secret  Base32 anahtar.
 * @return string[]
 */
function ysf_2fa_activate( $user_id, $secret ) {
	update_user_meta( (int) $user_id, '_ysf_2fa_secret', $secret );
	delete_user_meta( (int) $user_id, '_ysf_2fa_pending' );
	$codes = ysf_2fa_make_backups( (int) $user_id );
	set_transient( 'ysf_2fa_new_codes_' . (int) $user_id, $codes, 10 * MINUTE_IN_SECONDS );

	return $codes;
}

/**
 * Bu istekteki 2FA meydan okumasını saklar.
 *
 * @param array $ctx Bağlam.
 */
function ysf_2fa_set_challenge( $ctx ) {
	$GLOBALS['ysf_2fa_challenge'] = $ctx;
}

/**
 * Saklanan meydan okuma.
 *
 * @return array
 */
function ysf_2fa_last_challenge() {
	return isset( $GLOBALS['ysf_2fa_challenge'] ) && is_array( $GLOBALS['ysf_2fa_challenge'] )
		? $GLOBALS['ysf_2fa_challenge']
		: array();
}

/**
 * Şifre doğrulandıktan sonra kısa ömürlü bilet basar.
 *
 * @param WP_User $user  Kullanıcı.
 * @param bool    $setup Kurulum adımı mı.
 * @return array
 */
function ysf_2fa_remember_challenge( $user, $setup ) {
	$ticket  = wp_generate_password( 32, false );
	$pending = ysf_2fa_enroll_secret( $user );
	$otpauth = $pending ? ysf_2fa_otpauth( $user, $pending ) : '';
	$remember = ! empty( $_POST['remember'] ) || ! empty( $_POST['rememberme'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	set_transient(
		'ysf_2fa_t_' . $ticket,
		array(
			'uid'      => $user->ID,
			'remember' => $remember,
			'setup'    => (bool) $setup,
		),
		10 * MINUTE_IN_SECONDS
	);

	$ctx = array(
		'ticket'  => $ticket,
		'setup'   => (bool) $setup,
		'user'    => $user,
		'qr'      => $otpauth ? ysf_2fa_qr_url( $otpauth ) : '',
		'otpauth' => $otpauth,
		'secret'  => $pending,
	);

	ysf_2fa_set_challenge( $ctx );

	return $ctx;
}

/**
 * Mevcut bileti yeniden kullanır (yanlış kod).
 *
 * @param WP_User $user   Kullanıcı.
 * @param string  $ticket Bilet.
 * @param array   $data   Transient.
 */
function ysf_2fa_restore_challenge( $user, $ticket, $data ) {
	$setup   = ! empty( $data['setup'] ) || ! ysf_2fa_enabled( $user->ID );
	$pending = ysf_2fa_enroll_secret( $user );
	$otpauth = $pending ? ysf_2fa_otpauth( $user, $pending ) : '';

	set_transient( 'ysf_2fa_t_' . $ticket, $data, 10 * MINUTE_IN_SECONDS );

	ysf_2fa_set_challenge(
		array(
			'ticket'  => $ticket,
			'setup'   => $setup,
			'user'    => $user,
			'qr'      => $otpauth ? ysf_2fa_qr_url( $otpauth ) : '',
			'otpauth' => $otpauth,
			'secret'  => $pending,
		)
	);
}

/**
 * AJAX cevabı için karekod / bilet.
 *
 * @param WP_User|null $user Kullanıcı.
 * @return array
 */
function ysf_2fa_client_payload( $user = null ) {
	$ctx   = ysf_2fa_last_challenge();
	$setup = ! empty( $ctx['setup'] );
	$data  = ! empty( $ctx['ticket'] ) ? ysf_2fa_ticket_get( $ctx['ticket'] ) : array();

	if ( $user instanceof WP_User && ysf_user_needs_2fa( $user ) && ! ysf_2fa_enabled( $user->ID ) ) {
		$setup = true;

		if ( empty( $ctx['ticket'] ) ) {
			$ctx  = ysf_2fa_remember_challenge( $user, true );
			$data = ysf_2fa_ticket_get( $ctx['ticket'] );
		}
	}

	$email_ok = ! empty( $data['email_ok'] );
	$out      = array();

	if ( ! empty( $ctx['ticket'] ) ) {
		$out['ticket'] = $ctx['ticket'];
	}

	if ( $setup && ! $email_ok ) {
		$out['step'] = '2fa_email';
	} elseif ( $setup ) {
		$out['step'] = '2fa_setup';
	} else {
		$out['step'] = '2fa';
	}

	if ( $setup && $email_ok && $user instanceof WP_User ) {
		$pending       = ysf_2fa_enroll_secret( $user );
		$out['manual'] = $pending;
		$out['secret'] = $pending;
		$out['qr']     = ! empty( $ctx['ticket'] ) ? ysf_2fa_qr_ajax_url( $ctx['ticket'] ) : '';
	}

	return $out;
}

/**
 * Karekodu ayrı istekte döner; giriş JSON'una SVG konmaz.
 */
function ysf_ajax_2fa_qr() {
	$ticket = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' === $ticket ) {
		status_header( 404 );
		exit;
	}

	$data = get_transient( 'ysf_2fa_t_' . $ticket );

	if ( ! is_array( $data ) || empty( $data['uid'] ) ) {
		status_header( 404 );
		exit;
	}

	$user = get_userdata( (int) $data['uid'] );

	if ( ! $user || ! ysf_user_needs_2fa( $user ) ) {
		status_header( 404 );
		exit;
	}

	if ( ! empty( $data['setup'] ) && empty( $data['email_ok'] ) ) {
		status_header( 404 );
		exit;
	}

	if ( empty( $data['setup'] ) ) {
		status_header( 404 );
		exit;
	}

	$pending = ysf_2fa_ensure_pending( $user );
	$svg     = $pending ? ysf_qr_svg( ysf_2fa_otpauth( $user, $pending ) ) : '';

	if ( '' === $svg ) {
		status_header( 500 );
		exit;
	}

	nocache_headers();
	header( 'Content-Type: image/svg+xml; charset=UTF-8' );
	header( 'X-Content-Type-Options: nosniff' );
	echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
add_action( 'wp_ajax_nopriv_ysf_2fa_qr', 'ysf_ajax_2fa_qr' );
add_action( 'wp_ajax_ysf_2fa_qr', 'ysf_ajax_2fa_qr' );

/**
 * wp-login.php ikinci adımında şifre gerekmesin diye bilet.
 *
 * @param WP_User|WP_Error|null $user     Önceki sonuç.
 * @param string                $username Kullanıcı adı.
 * @param string                $password Şifre.
 * @return WP_User|WP_Error|null
 */
function ysf_2fa_authenticate_ticket( $user, $username, $password ) {
	unset( $username, $password );

	if ( $user instanceof WP_User ) {
		return $user;
	}

	$ticket = isset( $_POST['ysf_2fa_ticket'] ) ? sanitize_text_field( wp_unslash( $_POST['ysf_2fa_ticket'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( '' === $ticket ) {
		return $user;
	}

	$data = get_transient( 'ysf_2fa_t_' . $ticket );

	if ( ! is_array( $data ) || empty( $data['uid'] ) ) {
		return is_wp_error( $user ) ? $user : new WP_Error( 'ysf_2fa', ysf_t( 'tfa_prompt' ) );
	}

	$account = get_userdata( (int) $data['uid'] );

	if ( ! $account || ! ysf_user_needs_2fa( $account ) ) {
		return $user;
	}

	$code  = isset( $_POST['ysf_2fa_code'] ) ? (string) wp_unslash( $_POST['ysf_2fa_code'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$setup = ! empty( $data['setup'] ) || ! ysf_2fa_enabled( $account->ID );

	if ( $setup && empty( $data['email_ok'] ) ) {
		ysf_2fa_restore_challenge( $account, $ticket, $data );

		return new WP_Error( 'ysf_2fa_email', ysf_t( 'tfa_email_prompt' ) );
	}

	if ( '' === trim( $code ) ) {
		ysf_2fa_restore_challenge( $account, $ticket, $data );

		return new WP_Error(
			$setup ? 'ysf_2fa_setup' : 'ysf_2fa',
			$setup ? ysf_t( 'tfa_setup_prompt' ) : ysf_t( 'tfa_prompt' )
		);
	}

	if ( $setup ) {
		$pending = ysf_2fa_ensure_pending( $account );

		if ( ysf_2fa_verify( $account->ID, $code, $pending ) ) {
			ysf_2fa_activate( $account->ID, $pending );
			delete_transient( 'ysf_2fa_t_' . $ticket );

			return $account;
		}

		ysf_2fa_ticket_abort( $ticket );

		if ( ysf_rate_limited( 'tfa_' . $account->ID, 12, 15 * MINUTE_IN_SECONDS ) ) {
			return new WP_Error( 'ysf_2fa_locked', ysf_t( 'acc_too_many' ) );
		}

		return new WP_Error( 'ysf_2fa_fail', ysf_t( 'tfa_or_pass_wrong' ) );
	}

	if ( ysf_2fa_verify( $account->ID, $code ) ) {
		delete_transient( 'ysf_2fa_t_' . $ticket );

		return $account;
	}

	ysf_2fa_ticket_abort( $ticket );

	if ( ysf_rate_limited( 'tfa_' . $account->ID, 12, 15 * MINUTE_IN_SECONDS ) ) {
		return new WP_Error( 'ysf_2fa_locked', ysf_t( 'acc_too_many' ) );
	}

	return new WP_Error( 'ysf_2fa_fail', ysf_t( 'tfa_or_pass_wrong' ) );
}
add_filter( 'authenticate', 'ysf_2fa_authenticate_ticket', 5, 3 );

/**
 * Şifre doğrulandıktan sonra yöneticiye kod sorar; yoksa kurulumu başlatır.
 *
 * @param WP_User|WP_Error $user     Kullanıcı.
 * @param string           $password Şifre.
 * @return WP_User|WP_Error
 */
function ysf_2fa_authenticate_user( $user, $password ) {
	unset( $password );

	if ( is_wp_error( $user ) || ! ( $user instanceof WP_User ) ) {
		return $user;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return $user;
	}

	if ( ! ysf_user_needs_2fa( $user ) ) {
		return $user;
	}

	$code       = isset( $_POST['ysf_2fa_code'] ) ? (string) wp_unslash( $_POST['ysf_2fa_code'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$ticket     = isset( $_POST['ysf_2fa_ticket'] ) ? sanitize_text_field( wp_unslash( $_POST['ysf_2fa_ticket'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$data       = ysf_2fa_ticket_get( $ticket );
	$email_code = isset( $_POST['ysf_2fa_email_code'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['ysf_2fa_email_code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( ysf_2fa_enabled( $user->ID ) ) {
		if ( '' === trim( $code ) ) {
			if ( $ticket && $data ) {
				ysf_2fa_restore_challenge( $user, $ticket, $data );
			} else {
				ysf_2fa_remember_challenge( $user, false );
			}

			return new WP_Error( 'ysf_2fa', ysf_t( 'tfa_prompt' ) );
		}

		if ( ysf_2fa_verify( $user->ID, $code ) ) {
			ysf_2fa_ticket_abort( $ticket );

			return $user;
		}

		ysf_2fa_ticket_abort( $ticket );

		if ( ysf_rate_limited( 'tfa_' . $user->ID, 12, 15 * MINUTE_IN_SECONDS ) ) {
			return new WP_Error( 'ysf_2fa_locked', ysf_t( 'acc_too_many' ) );
		}

		return new WP_Error( 'ysf_2fa_fail', ysf_t( 'tfa_or_pass_wrong' ) );
	}

	ysf_2fa_ensure_pending( $user );

	if ( empty( $data['email_ok'] ) ) {
		if ( '' !== $email_code && $ticket && ! empty( $data['email_code'] ) && 6 === strlen( $email_code ) && hash_equals( (string) $data['email_code'], $email_code ) ) {
			$data['email_ok'] = true;
			unset( $data['email_code'] );
			ysf_2fa_ticket_save( $ticket, $data );
			ysf_2fa_restore_challenge( $user, $ticket, $data );

			return new WP_Error( 'ysf_2fa_setup', ysf_t( 'tfa_setup_prompt' ) );
		}

		if ( '' !== $email_code ) {
			ysf_2fa_ticket_abort( $ticket );

			return new WP_Error( 'ysf_2fa_fail', ysf_t( 'tfa_or_pass_wrong' ) );
		}

		if ( $ticket && $data ) {
			ysf_2fa_restore_challenge( $user, $ticket, $data );
		} else {
			ysf_2fa_start_email_challenge( $user );
		}

		return new WP_Error( 'ysf_2fa_email', ysf_t( 'tfa_email_prompt' ) );
	}

	if ( '' === trim( $code ) ) {
		ysf_2fa_restore_challenge( $user, $ticket, $data );

		return new WP_Error( 'ysf_2fa_setup', ysf_t( 'tfa_setup_prompt' ) );
	}

	$pending = (string) get_user_meta( $user->ID, '_ysf_2fa_pending', true );

	if ( $pending && ysf_2fa_verify( $user->ID, $code, $pending ) ) {
		ysf_2fa_activate( $user->ID, $pending );
		ysf_2fa_ticket_abort( $ticket );

		return $user;
	}

	ysf_2fa_ticket_abort( $ticket );

	if ( ysf_rate_limited( 'tfa_' . $user->ID, 12, 15 * MINUTE_IN_SECONDS ) ) {
		return new WP_Error( 'ysf_2fa_locked', ysf_t( 'acc_too_many' ) );
	}

	return new WP_Error( 'ysf_2fa_fail', ysf_t( 'tfa_or_pass_wrong' ) );
}
add_filter( 'wp_authenticate_user', 'ysf_2fa_authenticate_user', 20, 2 );

/**
 * 2FA kurulmadan uygulama şifresi kabul edilmez.
 *
 * @param WP_Error $error Hata.
 * @param WP_User  $user  Kullanıcı.
 * @return WP_Error
 */
function ysf_2fa_app_password_errors( $error, $user ) {
	if ( $user instanceof WP_User && ysf_user_needs_2fa( $user ) && ! ysf_2fa_enabled( $user->ID ) ) {
		$error->add( 'ysf_2fa', ysf_t( 'tfa_must' ) );
	}

	return $error;
}
add_filter( 'wp_authenticate_application_password_errors', 'ysf_2fa_app_password_errors', 10, 2 );

/**
 * wp-login.php: şifre doğruysa yalnızca Authenticator adımı.
 */
function ysf_2fa_login_field() {
	$ctx = ysf_2fa_last_challenge();

	if ( empty( $ctx['ticket'] ) ) {
		return;
	}

	$data     = ysf_2fa_ticket_get( $ctx['ticket'] );
	$setup    = ! empty( $ctx['setup'] );
	$email_ok = ! empty( $data['email_ok'] );

	if ( $setup && ! $email_ok ) {
		echo '<p><label for="ysf_2fa_email_code">' . esc_html( ysf_t( 'tfa_email_code' ) ) . '<br>';
		echo '<input type="text" name="ysf_2fa_email_code" id="ysf_2fa_email_code" class="input" value="" size="20" autocomplete="one-time-code" inputmode="numeric" required></label></p>';
	} elseif ( $setup && $email_ok ) {
		$secret = isset( $ctx['secret'] ) ? (string) $ctx['secret'] : '';
		$mark   = ! empty( $ctx['otpauth'] ) ? ysf_qr_svg( $ctx['otpauth'] ) : '';

		echo '<div class="ysf-2fa-setup">';
		echo '<p class="ysf-2fa-setup__title">' . esc_html( ysf_t( 'tfa_scan' ) ) . '</p>';

		if ( $mark ) {
			echo '<div class="ysf-2fa-setup__qr">' . $mark . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( $secret ) {
			echo '<p>' . esc_html( ysf_t( 'tfa_manual' ) ) . ' <code>' . esc_html( $secret ) . '</code></p>';
		}

		echo '</div>';
	}

	if ( ! empty( $ctx['ticket'] ) ) {
		echo '<input type="hidden" name="ysf_2fa_ticket" value="' . esc_attr( $ctx['ticket'] ) . '">';
	}

	if ( ! $setup || $email_ok ) {
		?>
	<p>
		<label for="ysf_2fa_code"><?php echo esc_html( ysf_t( 'tfa_code' ) ); ?><br>
			<input type="text" name="ysf_2fa_code" id="ysf_2fa_code" class="input" value="" size="20" autocomplete="one-time-code" inputmode="numeric" required>
		</label>
	</p>
		<?php
	}
}
add_action( 'login_form', 'ysf_2fa_login_field' );

/**
 * wp-login ikinci adımında kullanıcı adı / şifre alanlarını gizler.
 *
 * @param string[] $classes Gövde sınıfları.
 * @return string[]
 */
function ysf_2fa_login_body_class( $classes ) {
	$ctx = ysf_2fa_last_challenge();

	if ( ! empty( $ctx['ticket'] ) ) {
		$classes[] = 'ysf-2fa-step';
	}

	return $classes;
}
add_filter( 'login_body_class', 'ysf_2fa_login_body_class' );

/**
 * wp-login karekod stili.
 */
function ysf_2fa_login_head() {
	echo '<style>
		.ysf-2fa-setup{margin:8px 0 16px;padding:12px;background:#fff8e5;border:1px solid #c9a227;border-radius:6px}
		.ysf-2fa-setup__title{font-weight:700;margin:0 0 8px}
		.ysf-2fa-setup img,.ysf-2fa-setup svg{display:block;margin:8px auto;width:180px;height:180px;background:#fff}
		.ysf-2fa-setup code{display:block;margin-top:8px;font-size:15px;letter-spacing:.06em;word-break:break-all}
		body.login.ysf-2fa-step #loginform #user_login,
		body.login.ysf-2fa-step #loginform .user-pass-wrap,
		body.login.ysf-2fa-step #loginform .forgetmenot,
		body.login.ysf-2fa-step #nav{display:none!important}
		body.login.ysf-2fa-step #login_error{border-left-color:#c9a227;background:#fff8e5}
	</style>';
}
add_action( 'login_head', 'ysf_2fa_login_head' );

/**
 * wp-login ikinci adımında gönder düğmesi ve kod alanı.
 */
function ysf_2fa_login_footer() {
	$ctx = ysf_2fa_last_challenge();

	if ( empty( $ctx['ticket'] ) ) {
		return;
	}

	$data     = ysf_2fa_ticket_get( $ctx['ticket'] );
	$email_ok = ! empty( $data['email_ok'] );
	$setup    = ! empty( $ctx['setup'] );
	$is_email = $setup && ! $email_ok;
	$label    = wp_json_encode( $is_email ? ysf_t( 'tfa_email_continue' ) : ysf_t( 'tfa_continue' ) );
	$focus    = $is_email ? 'ysf_2fa_email_code' : 'ysf_2fa_code';

	echo '<script>document.addEventListener("DOMContentLoaded",function(){var b=document.getElementById("wp-submit");if(b){b.value=' . $label . ';}var c=document.getElementById(' . wp_json_encode( $focus ) . ');if(c){c.focus();}});</script>';
}
add_action( 'login_footer', 'ysf_2fa_login_footer' );

/**
 * Bilet varken boş şifre hatası 2FA mesajını ezmesin.
 *
 * @param WP_User|WP_Error|null $user     Sonuç.
 * @param string                $username Kullanıcı adı.
 * @param string                $password Şifre.
 * @return WP_User|WP_Error|null
 */
function ysf_2fa_keep_challenge_error( $user, $username, $password ) {
	unset( $username, $password );

	if ( ! is_wp_error( $user ) ) {
		return $user;
	}

	$ticket = isset( $_POST['ysf_2fa_ticket'] ) ? sanitize_text_field( wp_unslash( $_POST['ysf_2fa_ticket'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( '' === $ticket ) {
		return $user;
	}

	$codes = $user->get_error_codes();

	if ( in_array( 'ysf_2fa', $codes, true ) || in_array( 'ysf_2fa_setup', $codes, true ) || in_array( 'ysf_2fa_email', $codes, true ) || in_array( 'ysf_2fa_fail', $codes, true ) || in_array( 'ysf_2fa_locked', $codes, true ) ) {
		$user->remove( 'empty_username' );
		$user->remove( 'empty_password' );
		$user->remove( 'invalid_username' );
		$user->remove( 'incorrect_password' );
	}

	return $user;
}
add_filter( 'authenticate', 'ysf_2fa_keep_challenge_error', 99, 3 );

/**
 * Kurulum bitmeden izin verilen istekler.
 *
 * @return bool
 */
function ysf_2fa_request_is_allowed_during_setup() {
	global $pagenow;

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return true;
	}

	if ( isset( $_GET['action'] ) && 'logout' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return true;
	}

	if ( isset( $pagenow ) && 'wp-login.php' === $pagenow ) {
		return true;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( is_admin() && 'ysf-2fa' === $page ) {
		return true;
	}

	if ( ! empty( $_POST['ysf_2fa_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return true;
	}

	return false;
}

/**
 * 2FA'sız yönetici oturumunu kurulum sayfasına kilitler.
 */
function ysf_2fa_lockdown() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	if ( wp_doing_cron() ) {
		return;
	}

	if ( ! ysf_2fa_incomplete_admin() ) {
		return;
	}

	if ( ysf_2fa_request_is_allowed_during_setup() ) {
		return;
	}

	if ( wp_doing_ajax() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'tfa_setup_lock' ) ), 403 );
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=ysf-2fa' ) );
	exit;
}
add_action( 'admin_init', 'ysf_2fa_lockdown', 0 );
add_action( 'template_redirect', 'ysf_2fa_lockdown', 0 );

/**
 * Çerezli REST isteklerinde de kurulumu zorunlu tutar.
 *
 * @param WP_Error|null|true $result Önceki sonuç.
 * @return WP_Error|null|true
 */
function ysf_2fa_rest_block( $result ) {
	if ( $result ) {
		return $result;
	}

	if ( ysf_2fa_incomplete_admin() ) {
		return new WP_Error( 'ysf_2fa', ysf_t( 'tfa_setup_lock' ), array( 'status' => 403 ) );
	}

	return $result;
}
add_filter( 'rest_authentication_errors', 'ysf_2fa_rest_block' );

/**
 * Ayar sayfası menüsü.
 */
function ysf_2fa_admin_menu() {
	add_options_page(
		ysf_t( 'tfa_title' ),
		ysf_t( 'tfa_title' ),
		'manage_options',
		'ysf-2fa',
		'ysf_2fa_admin_page'
	);
}
add_action( 'admin_menu', 'ysf_2fa_admin_menu' );

/**
 * 2FA form işlemleri.
 */
function ysf_2fa_admin_handle() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_POST['ysf_2fa_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	check_admin_referer( 'ysf_2fa' );

	$user_id = get_current_user_id();
	$action  = sanitize_key( wp_unslash( $_POST['ysf_2fa_action'] ) );
	$code    = isset( $_POST['ysf_2fa_code'] ) ? (string) wp_unslash( $_POST['ysf_2fa_code'] ) : '';

	if ( 'start' === $action ) {
		$secret = ysf_base32_encode( random_bytes( 20 ) );
		update_user_meta( $user_id, '_ysf_2fa_pending', $secret );
		wp_safe_redirect( add_query_arg( array( 'page' => 'ysf-2fa', 'ysf_2fa' => 'setup' ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	if ( 'confirm' === $action ) {
		$pending = (string) get_user_meta( $user_id, '_ysf_2fa_pending', true );

		if ( ! $pending || ! ysf_2fa_verify( $user_id, $code, $pending ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'ysf-2fa', 'ysf_2fa' => 'bad' ), admin_url( 'options-general.php' ) ) );
			exit;
		}

		ysf_2fa_activate( $user_id, $pending );
		wp_safe_redirect( add_query_arg( array( 'page' => 'ysf-2fa', 'ysf_2fa' => 'on' ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	if ( 'disable' === $action ) {
		if ( ! ysf_2fa_verify( $user_id, $code ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'ysf-2fa', 'ysf_2fa' => 'bad' ), admin_url( 'options-general.php' ) ) );
			exit;
		}

		delete_user_meta( $user_id, '_ysf_2fa_secret' );
		delete_user_meta( $user_id, '_ysf_2fa_pending' );
		delete_user_meta( $user_id, '_ysf_2fa_backups' );
		wp_safe_redirect( add_query_arg( array( 'page' => 'ysf-2fa', 'ysf_2fa' => 'off' ), admin_url( 'options-general.php' ) ) );
		exit;
	}
}
add_action( 'admin_init', 'ysf_2fa_admin_handle' );

/**
 * Ayar ekranı.
 */
function ysf_2fa_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$user    = wp_get_current_user();
	$enabled = ysf_2fa_enabled( $user->ID );
	$pending = (string) get_user_meta( $user->ID, '_ysf_2fa_pending', true );
	$flash   = isset( $_GET['ysf_2fa'] ) ? sanitize_key( wp_unslash( $_GET['ysf_2fa'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$codes   = get_transient( 'ysf_2fa_new_codes_' . $user->ID );

	echo '<div class="wrap">';
	echo '<h1>' . esc_html( ysf_t( 'tfa_title' ) ) . '</h1>';
	echo '<p>' . esc_html( ysf_t( 'tfa_lead' ) ) . '</p>';

	if ( 'bad' === $flash ) {
		echo '<div class="notice notice-error"><p>' . esc_html( ysf_t( 'tfa_invalid' ) ) . '</p></div>';
	} elseif ( 'on' === $flash ) {
		echo '<div class="notice notice-success"><p>' . esc_html( ysf_t( 'tfa_on' ) ) . '</p></div>';
	} elseif ( 'off' === $flash ) {
		echo '<div class="notice notice-warning"><p>' . esc_html( ysf_t( 'tfa_must' ) ) . '</p></div>';
	}

	if ( is_array( $codes ) && $codes ) {
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( ysf_t( 'tfa_backups_once' ) ) . '</strong></p><p style="font-family:monospace;font-size:15px">';
		echo esc_html( implode( '  ', $codes ) );
		echo '</p></div>';
		delete_transient( 'ysf_2fa_new_codes_' . $user->ID );
	}

	if ( ! $enabled && ( $pending || 'setup' === $flash || ysf_2fa_incomplete_admin() ) ) {
		if ( ! $pending ) {
			$pending = ysf_2fa_ensure_pending( $user );
		}

		$otpauth = ysf_2fa_otpauth( $user, $pending );
		$mark    = ysf_qr_svg( $otpauth );

		echo '<h2>' . esc_html( ysf_t( 'tfa_scan' ) ) . '</h2>';
		if ( $mark ) {
			echo '<p>' . $mark . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '<p>' . esc_html( ysf_t( 'tfa_manual' ) ) . ' <code style="font-size:16px;letter-spacing:0.06em">' . esc_html( $pending ) . '</code></p>';

		echo '<form method="post">';
		wp_nonce_field( 'ysf_2fa' );
		echo '<input type="hidden" name="ysf_2fa_action" value="confirm">';
		echo '<p><label>' . esc_html( ysf_t( 'tfa_confirm' ) ) . '<br>';
		echo '<input type="text" name="ysf_2fa_code" class="regular-text" autocomplete="one-time-code" inputmode="numeric" required></label></p>';
		submit_button( ysf_t( 'tfa_enable' ) );
		echo '</form>';
		echo '</div>';
		return;
	}

	if ( $enabled ) {
		echo '<p><strong>' . esc_html( ysf_t( 'tfa_active' ) ) . '</strong></p>';
		echo '<form method="post">';
		wp_nonce_field( 'ysf_2fa' );
		echo '<input type="hidden" name="ysf_2fa_action" value="disable">';
		echo '<p><label>' . esc_html( ysf_t( 'tfa_disable_confirm' ) ) . '<br>';
		echo '<input type="text" name="ysf_2fa_code" class="regular-text" autocomplete="one-time-code" required></label></p>';
		submit_button( ysf_t( 'tfa_disable' ), 'delete' );
		echo '</form>';
		echo '</div>';
		return;
	}

	echo '<form method="post">';
	wp_nonce_field( 'ysf_2fa' );
	echo '<input type="hidden" name="ysf_2fa_action" value="start">';
	submit_button( ysf_t( 'tfa_start' ) );
	echo '</form>';
	echo '</div>';
}

/**
 * 2FA kapalıysa yöneticiye hatırlatır.
 */
function ysf_2fa_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( $screen && isset( $screen->id ) && 'settings_page_ysf-2fa' === $screen->id ) {
		return;
	}

	if ( ysf_2fa_enabled( get_current_user_id() ) ) {
		return;
	}

	$url = admin_url( 'options-general.php?page=ysf-2fa' );

	echo '<div class="notice notice-error"><p>';
	echo esc_html( ysf_t( 'tfa_must' ) );
	echo ' <a href="' . esc_url( $url ) . '">' . esc_html( ysf_t( 'tfa_title' ) ) . '</a>';
	echo '</p></div>';
}
add_action( 'admin_notices', 'ysf_2fa_admin_notice' );
