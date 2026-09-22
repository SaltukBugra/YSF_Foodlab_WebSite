<?php
/**
 * Online sipariş: sepetin sunucu tarafında doğrulanması, kayıt ve bildirim.
 *
 * Fiyatlar asla istemciden gelen değerle hesaplanmaz; her kalem için
 * veritabanındaki güncel fiyat okunur.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aynı IP'den kısa sürede tekrarlanan gönderimleri engeller.
 *
 * @param string $bucket Sayaç adı.
 * @param int    $limit  İzin verilen gönderim sayısı.
 * @param int    $window Saniye cinsinden pencere.
 * @return bool Sınır aşıldıysa true.
 */
function ysf_rate_limited( $bucket, $limit = 5, $window = 600 ) {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'ysf_rl_' . $bucket . '_' . md5( $ip );

	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return true;
	}

	set_transient( $key, $count + 1, $window );

	return false;
}

/**
 * AJAX güvenlik anahtarını doğrular; başarısızsa JSON hata döner.
 */
function ysf_require_ajax_nonce() {
	if ( ! check_ajax_referer( 'ysf_public', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_session_stale' ) ), 403 );
	}
}

/**
 * Bot tuzağı doldurulmuş mu? Tarayıcı otomatik doldurmasını yok sayar.
 *
 * @return bool
 */
function ysf_honeypot_tripped() {
	if ( ! isset( $_POST['ysf_hp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return false;
	}

	$hp = wp_unslash( $_POST['ysf_hp'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( is_array( $hp ) ) {
		return true;
	}

	$hp = trim( (string) $hp );

	if ( '' === $hp || '0' === $hp ) {
		return false;
	}

	foreach ( array( 'name', 'email', 'login', 'username', 'phone' ) as $key ) {
		if ( empty( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}

		$other = trim( (string) wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( $other && 0 === strcasecmp( $hp, $other ) ) {
			return false;
		}
	}

	if ( is_email( $hp ) ) {
		return false;
	}

	return true;
}

/**
 * Son e-posta hata metni.
 *
 * @return string
 */
function ysf_mail_fail_message() {
	$reason = get_transient( 'ysf_mail_last_error' );
	$base   = ysf_t( 'acc_verify_send_fail' );

	if ( $reason ) {
		return $base . ' ' . $reason;
	}

	return $base;
}

/**
 * Telefon numarasını doğrular (Türkiye ve uluslararası formatlar).
 *
 * @param string $phone Numara.
 * @return bool
 */
function ysf_valid_phone( $phone ) {
	$digits = ysf_digits( $phone );

	return strlen( $digits ) >= 10 && strlen( $digits ) <= 15;
}

/**
 * Posta kutusunu gerçek alan adına çeker (ysffoodlab.com → ysffoodlab.com.tr).
 *
 * @param string $email Adres.
 * @return string
 */
function ysf_normalize_mailbox( $email ) {
	$email = trim( (string) $email );

	if ( preg_match( '/@ysffoodlab\\.com$/i', $email ) ) {
		return preg_replace( '/@ysffoodlab\\.com$/i', '@ysffoodlab.com.tr', $email );
	}

	return $email;
}

/**
 * SMTP kullanıcı adı (tam posta kutusu).
 *
 * @return string
 */
function ysf_smtp_username() {
	$user = ysf_normalize_mailbox( (string) ysf_get_option( 'ysf_smtp_user', '' ) );

	if ( is_email( $user ) ) {
		return $user;
	}

	return ysf_mail_from_address();
}

/**
 * Giden e-postaların gönderen adresi.
 *
 * @return string
 */
function ysf_mail_from_address() {
	$from = defined( 'YSF_MAIL_FROM' ) ? YSF_MAIL_FROM : 'info@ysffoodlab.com.tr';
	$user = ysf_normalize_mailbox( (string) ysf_get_option( 'ysf_smtp_user', '' ) );

	if ( is_email( $user ) ) {
		return $user;
	}

	$from = ysf_normalize_mailbox( $from );

	return is_email( $from ) ? $from : 'info@ysffoodlab.com.tr';
}

/**
 * Eski info@ysffoodlab.com gönderenini gerçek kutuya çevirir.
 */
function ysf_maybe_fix_mail_domain() {
	if ( get_option( 'ysf_mail_domain_v3' ) ) {
		return;
	}

	foreach ( array( 'ysf_smtp_user', 'ysf_email' ) as $key ) {
		$val = (string) get_theme_mod( $key, '' );

		if ( preg_match( '/@ysffoodlab\\.com$/i', $val ) ) {
			set_theme_mod( $key, ysf_normalize_mailbox( $val ) );
		}
	}

	update_option( 'ysf_mail_domain_v3', '1', false );
}
add_action( 'init', 'ysf_maybe_fix_mail_domain', 5 );

/**
 * Giden e-postaların görünen gönderen adı.
 *
 * @return string
 */
function ysf_mail_from_name() {
	$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	return $name ? $name : 'YSF Food Lab';
}

/**
 * WordPress wp_mail gönderenini restoran adresine çeker.
 *
 * @param string $from Mevcut adres.
 * @return string
 */
function ysf_filter_mail_from( $from ) {
	unset( $from );

	return ysf_mail_from_address();
}
add_filter( 'wp_mail_from', 'ysf_filter_mail_from' );

/**
 * WordPress wp_mail gönderen adını restoran adına çeker.
 *
 * @param string $name Mevcut ad.
 * @return string
 */
function ysf_filter_mail_from_name( $name ) {
	unset( $name );

	return ysf_mail_from_name();
}
add_filter( 'wp_mail_from_name', 'ysf_filter_mail_from_name' );

/**
 * SMTP şifresini theme_mod’dan okur.
 *
 * @return string
 */
function ysf_smtp_password() {
	$pass = (string) ysf_get_option( 'ysf_smtp_pass', '' );
	$pass = wp_unslash( $pass );
	$pass = html_entity_decode( $pass, ENT_QUOTES, 'UTF-8' );
	$pass = preg_replace( '/^\xEF\xBB\xBF/', '', $pass );

	return trim( $pass );
}

/**
 * Denenecek SMTP sunucu / port / şifreleme kombinasyonları.
 *
 * @return array<int,array{host:string,port:int,enc:string}>
 */
function ysf_smtp_attempts() {
	$host = trim( (string) ysf_get_option( 'ysf_smtp_host', 'mail.ysffoodlab.com.tr' ) );
	$host = $host ? $host : 'mail.ysffoodlab.com.tr';
	$port = (int) ysf_get_option( 'ysf_smtp_port', 465 );
	$enc  = sanitize_key( (string) ysf_get_option( 'ysf_smtp_enc', 'ssl' ) );
	$port = $port ? $port : 465;
	$enc  = $enc ? $enc : 'ssl';

	$saved = get_transient( 'ysf_smtp_ok' );
	$list  = array();

	if ( is_array( $saved ) && ! empty( $saved['host'] ) ) {
		$list[] = $saved;
	}

	$list[] = array(
		'host' => $host,
		'port' => $port,
		'enc'  => $enc,
	);
	$list[] = array(
		'host' => $host,
		'port' => 465,
		'enc'  => 'ssl',
	);
	$list[] = array(
		'host' => $host,
		'port' => 587,
		'enc'  => 'tls',
	);
	$list[] = array(
		'host' => 'localhost',
		'port' => 465,
		'enc'  => 'ssl',
	);
	$list[] = array(
		'host' => 'localhost',
		'port' => 587,
		'enc'  => 'tls',
	);
	$list[] = array(
		'host' => '127.0.0.1',
		'port' => 465,
		'enc'  => 'ssl',
	);
	$list[] = array(
		'host' => 'mirel.veridyen.com',
		'port' => 465,
		'enc'  => 'ssl',
	);
	$list[] = array(
		'host' => 'mirel.veridyen.com',
		'port' => 587,
		'enc'  => 'tls',
	);

	$out  = array();
	$seen = array();

	foreach ( $list as $row ) {
		$key = $row['host'] . ':' . (int) $row['port'] . ':' . $row['enc'];

		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$out[]        = $row;
	}

	return $out;
}

/**
 * SMTP ayarlıysa PHPMailer’ı kimlik doğrulamalı gönderime alır.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer Mailer.
 */
function ysf_phpmailer_from( $phpmailer ) {
	$from = ysf_mail_from_address();
	$name = ysf_mail_from_name();
	$user = ysf_smtp_username();
	$pass = ysf_smtp_password();
	$try  = isset( $GLOBALS['ysf_smtp_try'] ) && is_array( $GLOBALS['ysf_smtp_try'] ) ? $GLOBALS['ysf_smtp_try'] : array(
		'host' => trim( (string) ysf_get_option( 'ysf_smtp_host', 'mail.ysffoodlab.com.tr' ) ),
		'port' => (int) ysf_get_option( 'ysf_smtp_port', 465 ),
		'enc'  => sanitize_key( (string) ysf_get_option( 'ysf_smtp_enc', 'ssl' ) ),
	);

	$host = ! empty( $try['host'] ) ? $try['host'] : 'mail.ysffoodlab.com.tr';
	$port = ! empty( $try['port'] ) ? (int) $try['port'] : 465;
	$enc  = ! empty( $try['enc'] ) ? $try['enc'] : 'ssl';

	try {
		$phpmailer->CharSet  = 'UTF-8';
		$phpmailer->Encoding = 'base64';
		$phpmailer->setFrom( $from, $name, false );

		if ( $host && '' !== $pass ) {
			$phpmailer->isSMTP();
			$phpmailer->Host       = $host;
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Username   = $user;
			$phpmailer->Password   = $pass;
			$phpmailer->Port       = $port ? $port : 465;
			$phpmailer->Timeout    = 20;
			$phpmailer->Sender     = $user;
			$phpmailer->SMTPOptions = array(
				'ssl' => array(
					'verify_peer'       => false,
					'verify_peer_name'  => false,
					'allow_self_signed' => true,
				),
			);

			if ( 'ssl' === $enc ) {
				$phpmailer->SMTPSecure = 'ssl';
				if ( 587 === (int) $phpmailer->Port ) {
					$phpmailer->Port = 465;
				}
			} elseif ( 'none' === $enc ) {
				$phpmailer->SMTPSecure  = '';
				$phpmailer->SMTPAutoTLS = false;
			} else {
				$phpmailer->SMTPSecure = 'tls';
				if ( 465 === (int) $phpmailer->Port ) {
					$phpmailer->Port = 587;
				}
			}
		}
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		set_transient( 'ysf_mail_last_error', $e->getMessage(), 10 * MINUTE_IN_SECONDS );
	}
}
add_action( 'phpmailer_init', 'ysf_phpmailer_from' );

/**
 * wp_mail hata mesajını saklar.
 *
 * @param WP_Error $error Hata.
 */
function ysf_mail_failed( $error ) {
	if ( is_wp_error( $error ) ) {
		set_transient( 'ysf_mail_last_error', $error->get_error_message(), 10 * MINUTE_IN_SECONDS );
	}
}
add_action( 'wp_mail_failed', 'ysf_mail_failed' );

/**
 * HTML e-posta gönderir.
 *
 * @param string     $to      Alıcı.
 * @param string     $subject Konu.
 * @param array      $rows    Etiket => değer satırları.
 * @param string     $intro   Giriş metni.
 * @param array|null $cta     İsteğe bağlı düğme: label, url.
 * @return bool
 */
function ysf_send_notification( $to, $subject, $rows, $intro = '', $cta = null ) {
	$to = is_email( $to ) ? $to : ysf_mail_from_address();

	$html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#14100d">';
	$html .= '<h2 style="color:#b56a16;margin:0 0 12px">' . esc_html( $subject ) . '</h2>';

	if ( $intro ) {
		$html .= '<p>' . esc_html( $intro ) . '</p>';
	}

	$html .= '<table cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse;width:100%;max-width:600px">';

	foreach ( $rows as $label => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}

		$html .= sprintf(
			'<tr><td style="background:#f7f3ec;font-weight:bold;width:190px;border-bottom:1px solid #e6ded2">%1$s</td><td style="border-bottom:1px solid #e6ded2">%2$s</td></tr>',
			esc_html( $label ),
			nl2br( esc_html( (string) $value ) )
		);
	}

	$html .= '</table>';

	if ( is_array( $cta ) && ! empty( $cta['url'] ) ) {
		$label = isset( $cta['label'] ) && $cta['label'] ? $cta['label'] : $cta['url'];
		$html .= '<p style="margin:22px 0 8px">';
		$html .= '<a href="' . esc_url( $cta['url'] ) . '" style="display:inline-block;background:#d98324;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold">';
		$html .= esc_html( $label );
		$html .= '</a></p>';
		$html .= '<p style="color:#6d635b;font-size:12px;word-break:break-all">' . esc_html( $cta['url'] ) . '</p>';
	}

	$html .= '<p style="color:#6d635b;font-size:12px;margin-top:18px">' . esc_html( home_url( '/' ) ) . '</p>';
	$html .= '</div>';

	$from = ysf_mail_from_address();
	$name = ysf_mail_from_name();

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %1$s <%2$s>', $name, $from ),
		sprintf( 'Reply-To: %1$s <%2$s>', $name, $from ),
	);

	try {
		$ok = false;

		if ( ysf_smtp_password() ) {
			foreach ( ysf_smtp_attempts() as $try ) {
				$GLOBALS['ysf_smtp_try'] = $try;
				delete_transient( 'ysf_mail_last_error' );

				$ok = wp_mail( $to, $subject, $html, $headers );

				if ( $ok ) {
					set_transient( 'ysf_smtp_ok', $try, WEEK_IN_SECONDS );
					break;
				}
			}

			unset( $GLOBALS['ysf_smtp_try'] );
		} else {
			$ok = wp_mail( $to, $subject, $html, $headers );
		}
	} catch ( Throwable $e ) {
		unset( $GLOBALS['ysf_smtp_try'] );
		set_transient( 'ysf_mail_last_error', $e->getMessage(), 10 * MINUTE_IN_SECONDS );
		$ok = false;
	}

	if ( ! $ok ) {
		$reason = get_transient( 'ysf_mail_last_error' );

		if ( $reason ) {
			error_log( 'YSF mail failed: ' . $reason ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	return $ok;
}

/**
 * Sipariş tiplerinin etiketleri.
 *
 * @return array
 */
function ysf_order_types() {
	return array(
		'delivery' => ysf_t( 'order_delivery' ),
		'pickup'   => ysf_t( 'order_pickup' ),
		'table'    => ysf_t( 'order_table' ),
	);
}

/**
 * Sipariş formunu işler.
 */
function ysf_ajax_submit_order() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ! ysf_get_option( 'ysf_orders_enabled', true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 403 );
	}

	// Bot tuzağı: gizli alan doldurulmuşsa sessizce reddet.
	if ( ! empty( $_POST['ysf_hp'] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( ysf_rate_limited( 'order', 8, 900 ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 429 );
	}

	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'delivery';
	$addr  = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
	$area  = isset( $_POST['area'] ) ? sanitize_text_field( wp_unslash( $_POST['area'] ) ) : '';
	$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
	$time  = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';
	$note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$items = isset( $_POST['items'] ) ? json_decode( wp_unslash( $_POST['items'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized

	if ( ! $name || ! ysf_valid_phone( $phone ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	if ( ! is_array( $items ) || empty( $items ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'order_empty_error' ) ), 400 );
	}

	if ( 'delivery' === $type && ! $addr ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	// Kalemleri veritabanındaki fiyatlarla yeniden hesapla.
	$lines    = array();
	$subtotal = 0.0;

	foreach ( $items as $item ) {
		$id  = isset( $item['id'] ) ? (int) $item['id'] : 0;
		$qty = isset( $item['qty'] ) ? max( 1, min( 50, (int) $item['qty'] ) ) : 1;

		if ( ! $id || 'ysf_menu_item' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
			continue;
		}

		if ( ! ysf_is_orderable( $id ) ) {
			continue;
		}

		$price     = (float) get_post_meta( $id, '_ysf_price', true );
		$item_name = ysf_field( $id, 'title' );
		$sizes     = ysf_get_item_sizes( $id );
		$size      = '';

		if ( $sizes ) {
			$picked   = $sizes[0];
			$size_key = isset( $item['size'] ) ? sanitize_key( $item['size'] ) : '';

			foreach ( $sizes as $option ) {
				if ( $size_key && $option['key'] === $size_key ) {
					$picked = $option;
					break;
				}
			}

			$price      = (float) $picked['price'];
			$size       = $picked['key'];
			$item_name .= ' (' . ysf_size_label( $picked ) . ')';
		}

		$lines[] = array(
			'id'    => $id,
			'name'  => $item_name,
			'qty'   => $qty,
			'price' => $price,
			'size'  => $size,
		);
	}

	if ( empty( $lines ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'order_empty_error' ) ), 400 );
	}

	if ( function_exists( 'ysf_apply_campaign_prices' ) ) {
		$lines = ysf_apply_campaign_prices( $lines );
	}

	$subtotal = 0.0;
	$offers   = array();

	foreach ( $lines as $line ) {
		$subtotal += (float) $line['price'] * (int) $line['qty'];

		if ( ! empty( $line['offer'] ) ) {
			$offers[ $line['offer'] ] = $line['offer'];
		}
	}

	$savings = function_exists( 'ysf_campaign_savings' ) ? ysf_campaign_savings( $lines ) : 0.0;

	$min_order = (float) ysf_get_option( 'ysf_min_order', 0 );

	if ( 'delivery' === $type && $min_order > 0 && $subtotal < $min_order ) {
		wp_send_json_error(
			array( 'message' => sprintf( ysf_t( 'min_order_warning' ), ysf_price( $min_order ) ) ),
			400
		);
	}

	$fee = 0.0;

	if ( 'delivery' === $type ) {
		$fee       = (float) ysf_get_option( 'ysf_delivery_fee', 0 );
		$free_over = (float) ysf_get_option( 'ysf_free_delivery_over', 0 );

		if ( $free_over > 0 && $subtotal >= $free_over ) {
			$fee = 0.0;
		}
	}

	$total = $subtotal + $fee;
	$types = ysf_order_types();

	$order_id = ysf_insert_request_post(
		array(
			'post_type'    => 'ysf_order',
			'post_status'  => 'publish',
			'post_title'   => sprintf( '%1$s — %2$s', $name, ysf_price( $total ) ),
			'post_content' => $note,
		)
	);

	if ( is_wp_error( $order_id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	$meta = array(
		'_ysf_name'         => $name,
		'_ysf_phone'        => $phone,
		'_ysf_order_type'   => isset( $types[ $type ] ) ? $types[ $type ] : $type,
		'_ysf_address'      => trim( $area ? $area . ' — ' . $addr : $addr ),
		'_ysf_table'        => $table,
		'_ysf_time'         => $time,
		'_ysf_note'         => $note,
		'_ysf_items'        => $lines,
		'_ysf_subtotal'     => ysf_price( $subtotal ),
		'_ysf_discount'     => $savings > 0 ? ysf_price( $savings ) . ( $offers ? ' (' . implode( ', ', $offers ) . ')' : '' ) : '',
		'_ysf_delivery_fee' => $fee > 0 ? ysf_price( $fee ) : ysf_t( 'free' ),
		'_ysf_total'        => $total,
		'_ysf_state'        => 'pending',
		'_ysf_channel'      => isset( $types[ $type ] ) ? $type : 'delivery',
		'_ysf_source'       => 'online',
		'_ysf_kitchen_state'=> 'queued',
		'_ysf_lang'         => ysf_lang(),
	);

	foreach ( $meta as $key => $value ) {
		update_post_meta( $order_id, $key, $value );
	}

	ysf_attach_user_to_record( $order_id );

	// Bildirim e-postası.
	$item_lines = array();
	foreach ( $lines as $line ) {
		$item_lines[] = sprintf( '%1$d x %2$s (%3$s)', $line['qty'], $line['name'], ysf_price( $line['price'] * $line['qty'] ) );
	}

	ysf_send_notification(
		ysf_get_option( 'ysf_order_email', '' ),
		sprintf(
			/* translators: %s: sipariş numarası. */
			__( 'Yeni online sipariş #%s', 'ysffoodlab' ),
			$order_id
		),
		array(
			__( 'Ad Soyad', 'ysffoodlab' )        => $name,
			__( 'Telefon', 'ysffoodlab' )         => $phone,
			__( 'Sipariş tipi', 'ysffoodlab' )    => $meta['_ysf_order_type'],
			__( 'Adres', 'ysffoodlab' )           => $meta['_ysf_address'],
			__( 'Masa no', 'ysffoodlab' )         => $table,
			__( 'İstenen saat', 'ysffoodlab' )    => $time,
			__( 'Ürünler', 'ysffoodlab' )         => implode( "\n", $item_lines ),
			__( 'Ara toplam', 'ysffoodlab' )      => ysf_price( $subtotal ),
			__( 'Kampanya indirimi', 'ysffoodlab' ) => $savings > 0 ? ysf_price( $savings ) : '',
			__( 'Teslimat ücreti', 'ysffoodlab' ) => $meta['_ysf_delivery_fee'],
			__( 'Toplam', 'ysffoodlab' )          => ysf_price( $total ),
			__( 'Not', 'ysffoodlab' )             => $note,
		),
		__( 'Web sitesinden yeni bir sipariş geldi.', 'ysffoodlab' )
	);

	// WhatsApp mesajı.
	$wa_url = '';

	if ( ysf_get_option( 'ysf_order_wa', true ) ) {
		$message  = sprintf( "*%s — %s #%d*\n", get_bloginfo( 'name' ), __( 'Sipariş', 'ysffoodlab' ), $order_id );
		$message .= sprintf( "%s: %s\n", __( 'Ad', 'ysffoodlab' ), $name );
		$message .= sprintf( "%s: %s\n", __( 'Telefon', 'ysffoodlab' ), $phone );
		$message .= sprintf( "%s: %s\n", __( 'Tip', 'ysffoodlab' ), $meta['_ysf_order_type'] );

		if ( $meta['_ysf_address'] ) {
			$message .= sprintf( "%s: %s\n", __( 'Adres', 'ysffoodlab' ), $meta['_ysf_address'] );
		}

		if ( $table ) {
			$message .= sprintf( "%s: %s\n", __( 'Masa', 'ysffoodlab' ), $table );
		}

		$message .= "\n" . implode( "\n", $item_lines ) . "\n";
		$message .= sprintf( "\n%s: %s", __( 'Toplam', 'ysffoodlab' ), ysf_price( $total ) );

		if ( $note ) {
			$message .= sprintf( "\n%s: %s", __( 'Not', 'ysffoodlab' ), $note );
		}

		$wa_url = ysf_whatsapp_url( $message );
	}

	wp_send_json_success(
		array(
			'message'  => ysf_t( 'order_success' ),
			'orderId'  => $order_id,
			'total'    => ysf_price( $total ),
			'whatsapp' => $wa_url,
		)
	);
}
add_action( 'wp_ajax_ysf_submit_order', 'ysf_ajax_submit_order' );
add_action( 'wp_ajax_nopriv_ysf_submit_order', 'ysf_ajax_submit_order' );

/**
 * Menü fiyatlarını istemciye veren yardımcı uç (sepet tutarlılığı için).
 */
function ysf_ajax_get_prices() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
	$out = array();

	foreach ( $ids as $id ) {
		if ( 'ysf_menu_item' !== get_post_type( $id ) ) {
			continue;
		}

		$sizes = ysf_get_item_sizes( $id );
		$out[ $id ] = array(
			'price'     => $sizes ? (float) $sizes[0]['price'] : (float) get_post_meta( $id, '_ysf_price', true ),
			'name'      => ysf_field( $id, 'title' ),
			'orderable' => ysf_is_orderable( $id ),
			'sizes'     => array_map(
				function ( $size ) {
					return array(
						'key'   => $size['key'],
						'label' => ysf_size_label( $size ),
						'price' => (float) $size['price'],
					);
				},
				$sizes
			),
		);
	}

	wp_send_json_success( $out );
}
add_action( 'wp_ajax_ysf_get_prices', 'ysf_ajax_get_prices' );
add_action( 'wp_ajax_nopriv_ysf_get_prices', 'ysf_ajax_get_prices' );
