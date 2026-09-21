<?php
/**
 * Üye hesapları: kayıt, giriş, profil ve adres defteri.
 *
 * Müşteriler WordPress kullanıcısı olarak saklanır (rol: subscriber).
 * Mutfak sorumlusu (ysf_kitchen) menüyü Hesabım sayfasından yönetir;
 * panele yine giremez. Telefon ve adresler kullanıcı meta alanlarında tutulur.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kullanıcı meta anahtarları.
 */
const YSF_META_PHONE = '_ysf_phone';
const YSF_META_ADDR  = '_ysf_addr_';

/**
 * Metni güvenli biçimde kısaltır.
 *
 * mbstring yüklü değilse substr kullanılır; kayıt isteği bu yüzden
 * 500 dönmesin diye çekirdek mb_substr'e bağlanmıyoruz.
 *
 * @param string $value Metin.
 * @param int    $max   Azami karakter.
 * @return string
 */
function ysf_clip( $value, $max ) {
	$value = (string) $value;

	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $value, 0, (int) $max );
	}

	return substr( $value, 0, (int) $max );
}

/**
 * Adres defterindeki adres türleri.
 *
 * @return array
 */
function ysf_address_types() {
	return array(
		'home' => ysf_t( 'acc_addr_home' ),
		'work' => ysf_t( 'acc_addr_work' ),
	);
}

/**
 * Adres alanlarının tanımı.
 *
 * @return array
 */
function ysf_address_fields() {
	return array(
		'il'         => array(
			'label'    => ysf_t( 'addr_province' ),
			'type'     => 'select',
			'required' => true,
		),
		'ilce'       => array(
			'label'    => ysf_t( 'addr_district' ),
			'type'     => 'select',
			'required' => true,
		),
		'mahalle'    => array(
			'label'    => ysf_t( 'addr_neighbourhood' ),
			'type'     => 'text',
			'required' => true,
		),
		'sokak'      => array(
			'label'    => ysf_t( 'addr_street' ),
			'type'     => 'text',
			'required' => true,
		),
		'bina'       => array(
			'label'    => ysf_t( 'addr_building' ),
			'type'     => 'text',
			'required' => true,
		),
		'daire'      => array(
			'label'    => ysf_t( 'addr_flat' ),
			'type'     => 'text',
			'required' => false,
		),
		'posta_kodu' => array(
			'label'    => ysf_t( 'addr_zip' ),
			'type'     => 'text',
			'required' => false,
		),
		'tarif'      => array(
			'label'    => ysf_t( 'addr_note' ),
			'type'     => 'textarea',
			'required' => false,
		),
	);
}

/**
 * İl/ilçe veri dosyasının yolu.
 *
 * @return string
 */
function ysf_geo_path() {
	return YSF_DIR . '/assets/data/tr-il-ilce.json';
}

/**
 * İl/ilçe veri dosyasının adresi (tarayıcı tarafında kullanılır).
 *
 * @return string
 */
function ysf_geo_url() {
	$path = ysf_geo_path();
	$ver  = file_exists( $path ) ? (string) filemtime( $path ) : YSF_VERSION;

	return add_query_arg( 'ver', $ver, YSF_URI . '/assets/data/tr-il-ilce.json' );
}

/**
 * İl => ilçeler listesi.
 *
 * @return array
 */
function ysf_geo_data() {
	static $data = null;

	if ( null !== $data ) {
		return $data;
	}

	$data = array();
	$path = ysf_geo_path();

	if ( ! file_exists( $path ) ) {
		return $data;
	}

	if ( function_exists( 'wp_json_file_decode' ) ) {
		$decoded = wp_json_file_decode( $path, array( 'associative' => true ) );
	} else {
		$decoded = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	if ( is_array( $decoded ) ) {
		$data = $decoded;
	}

	return $data;
}

/**
 * Telefon numarasını +90XXXXXXXXXX biçimine getirir.
 *
 * Kabul edilen girişler: 0552 392 44 19, 5523924419, +90 552 392 44 19,
 * 0090552..., 0332 sabit hat. Geçersizse boş dize döner.
 *
 * @param string $phone Ham numara.
 * @return string
 */
function ysf_normalize_phone( $phone ) {
	$raw    = trim( (string) $phone );
	$plus   = ( 0 === strpos( $raw, '+' ) );
	$digits = ysf_digits( $raw );

	if ( ! $digits ) {
		return '';
	}

	// Uluslararası arama öneki.
	if ( 0 === strpos( $digits, '00' ) ) {
		$digits = substr( $digits, 2 );
		$plus   = true;
	}

	// Ülke kodu veya baştaki sıfır ayıklanır.
	if ( 12 === strlen( $digits ) && 0 === strpos( $digits, '90' ) ) {
		$digits = substr( $digits, 2 );
	} elseif ( 11 === strlen( $digits ) && 0 === strpos( $digits, '0' ) ) {
		$digits = substr( $digits, 1 );
	}

	if ( 10 === strlen( $digits ) ) {
		return '+90' . $digits;
	}

	// Yurt dışı numaraları olduğu gibi korunur.
	if ( $plus && strlen( $digits ) >= 10 && strlen( $digits ) <= 15 ) {
		return '+' . $digits;
	}

	return '';
}

/**
 * Numarayı okunur biçimde yazdırır.
 *
 * @param string $phone Kayıtlı numara.
 * @return string
 */
function ysf_phone_display( $phone ) {
	$phone = (string) $phone;

	if ( 0 === strpos( $phone, '+90' ) && 13 === strlen( $phone ) ) {
		$local = substr( $phone, 3 );

		return sprintf(
			'0%s %s %s %s',
			substr( $local, 0, 3 ),
			substr( $local, 3, 3 ),
			substr( $local, 6, 2 ),
			substr( $local, 8, 2 )
		);
	}

	return $phone;
}

/**
 * Cep telefonu mu (5 ile başlayan Türkiye numarası)?
 *
 * @param string $phone Normalize edilmiş numara.
 * @return bool
 */
function ysf_is_mobile_phone( $phone ) {
	return (bool) preg_match( '/^\+905\d{9}$/', (string) $phone );
}

/**
 * Kullanıcının telefon numarası.
 *
 * @param int $user_id Kullanıcı kimliği.
 * @return string
 */
function ysf_user_phone( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	return $user_id ? (string) get_user_meta( $user_id, YSF_META_PHONE, true ) : '';
}

/**
 * Telefon numarasının başka bir üyede kayıtlı olup olmadığını kontrol eder.
 *
 * @param string $phone   Normalize edilmiş numara.
 * @param int    $exclude Hariç tutulacak kullanıcı.
 * @return bool
 */
function ysf_phone_in_use( $phone, $exclude = 0 ) {
	if ( ! $phone ) {
		return false;
	}

	$users = get_users(
		array(
			'meta_key'   => YSF_META_PHONE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $phone, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'exclude'    => $exclude ? array( (int) $exclude ) : array(),
			'number'     => 1,
			'fields'     => 'ID',
		)
	);

	return ! empty( $users );
}

/**
 * Adres alanlarını temizler.
 *
 * @param array $raw Ham veri.
 * @return array
 */
function ysf_sanitize_address( $raw ) {
	$raw   = is_array( $raw ) ? $raw : array();
	$clean = array();

	foreach ( ysf_address_fields() as $key => $field ) {
		$value = isset( $raw[ $key ] ) ? wp_unslash( $raw[ $key ] ) : '';

		if ( 'textarea' === $field['type'] ) {
			$value = sanitize_textarea_field( $value );
			$value = ysf_clip( $value, 300 );
		} else {
			$value = sanitize_text_field( $value );
			$value = ysf_clip( $value, 120 );
		}

		$clean[ $key ] = trim( $value );
	}

	$clean['posta_kodu'] = ysf_digits( $clean['posta_kodu'] );

	return $clean;
}

/**
 * Adresin tamamen boş olup olmadığını söyler.
 *
 * @param array $address Adres.
 * @return bool
 */
function ysf_address_is_empty( $address ) {
	foreach ( (array) $address as $value ) {
		if ( '' !== trim( (string) $value ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Adresi doğrular.
 *
 * Adres girmek zorunlu değildir; boş adres geçerli sayılır ve kaydı siler.
 * Doldurulduysa il/ilçe listeden seçilmiş olmalı, zorunlu alanlar dolu olmalı
 * ve posta kodu girildiyse 5 haneli olmalıdır.
 *
 * @param array $address Temizlenmiş adres.
 * @return true|WP_Error
 */
function ysf_validate_address( $address ) {
	if ( ysf_address_is_empty( $address ) ) {
		return true;
	}

	$geo = ysf_geo_data();

	foreach ( ysf_address_fields() as $key => $field ) {
		if ( ! empty( $field['required'] ) && '' === $address[ $key ] ) {
			return new WP_Error( 'ysf_addr_required', ysf_t( 'addr_required' ), array( 'field' => $key ) );
		}
	}

	if ( $geo && ! isset( $geo[ $address['il'] ] ) ) {
		return new WP_Error( 'ysf_addr_province', ysf_t( 'addr_bad_province' ), array( 'field' => 'il' ) );
	}

	if ( $geo && ! in_array( $address['ilce'], (array) $geo[ $address['il'] ], true ) ) {
		return new WP_Error( 'ysf_addr_district', ysf_t( 'addr_bad_district' ), array( 'field' => 'ilce' ) );
	}

	if ( '' !== $address['posta_kodu'] && ! preg_match( '/^\d{5}$/', $address['posta_kodu'] ) ) {
		return new WP_Error( 'ysf_addr_zip', ysf_t( 'addr_bad_zip' ), array( 'field' => 'posta_kodu' ) );
	}

	return true;
}

/**
 * Kullanıcının kayıtlı adresi.
 *
 * @param int    $user_id Kullanıcı.
 * @param string $type    'home' veya 'work'.
 * @return array
 */
function ysf_get_user_address( $user_id, $type ) {
	$user_id = (int) $user_id;
	$type    = sanitize_key( $type );

	if ( ! $user_id || ! array_key_exists( $type, ysf_address_types() ) ) {
		return array();
	}

	$stored = get_user_meta( $user_id, YSF_META_ADDR . $type, true );

	if ( ! is_array( $stored ) || ysf_address_is_empty( $stored ) ) {
		return array();
	}

	$address = array();

	foreach ( ysf_address_fields() as $key => $field ) {
		$address[ $key ] = isset( $stored[ $key ] ) ? (string) $stored[ $key ] : '';
	}

	return $address;
}

/**
 * Adresi kaydeder; boş adres kaydı siler.
 *
 * @param int    $user_id Kullanıcı.
 * @param string $type    Adres türü.
 * @param array  $address Temizlenmiş adres.
 * @return bool
 */
function ysf_save_user_address( $user_id, $type, $address ) {
	$user_id = (int) $user_id;
	$type    = sanitize_key( $type );

	if ( ! $user_id || ! array_key_exists( $type, ysf_address_types() ) ) {
		return false;
	}

	if ( ysf_address_is_empty( $address ) ) {
		delete_user_meta( $user_id, YSF_META_ADDR . $type );

		return true;
	}

	update_user_meta( $user_id, YSF_META_ADDR . $type, $address );

	return true;
}

/**
 * Adresi tek satır metne çevirir (sipariş formu ve bildirimler için).
 *
 * @param array $address Adres.
 * @return string
 */
function ysf_address_one_line( $address ) {
	if ( ! $address || ysf_address_is_empty( $address ) ) {
		return '';
	}

	$street = trim( $address['sokak'] . ' No: ' . $address['bina'] );

	if ( '' !== $address['daire'] ) {
		$street .= ' / ' . $address['daire'];
	}

	$parts = array(
		trim( $address['mahalle'] ),
		$street,
		trim( $address['posta_kodu'] . ' ' . $address['ilce'] ),
		$address['il'],
	);

	$line = implode( ', ', array_filter( $parts ) );

	if ( '' !== $address['tarif'] ) {
		$line .= ' (' . $address['tarif'] . ')';
	}

	return $line;
}

/**
 * Hesabım sayfasının adresi.
 *
 * @param array $args Eklenecek sorgu parametreleri.
 * @return string
 */
function ysf_account_url( $args = array() ) {
	$url = ysf_get_page_url_by_template( 'template-account.php' );

	if ( ! $url ) {
		return '';
	}

	if ( $args ) {
		$url = add_query_arg( $args, $url );
	}

	return ysf_localize_url( $url );
}

/**
 * Adres çubuğundaki şifre yenileme isteğini okur.
 *
 * @return array{user:?WP_User,error:string,key:string,login:string}
 */
function ysf_password_reset_request() {
	$out = array(
		'user'  => null,
		'error' => '',
		'key'   => '',
		'login' => '',
	);

	if ( empty( $_GET['ysf_rp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $out;
	}

	$out['key']   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$out['login'] = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ), true ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! $out['key'] || ! $out['login'] ) {
		$out['error'] = ysf_t( 'acc_reset_invalid' );

		return $out;
	}

	$user = check_password_reset_key( $out['key'], $out['login'] );

	if ( is_wp_error( $user ) || ! $user ) {
		$out['error'] = ysf_t( 'acc_reset_invalid' );

		return $out;
	}

	$out['user'] = $user;

	return $out;
}

/**
 * Müşteri hesabı mı (panele erişimi olmayan üye)?
 *
 * @param int $user_id Kullanıcı.
 * @return bool
 */
function ysf_is_customer( $user_id = 0 ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();

	if ( ! $user || ! $user->exists() ) {
		return false;
	}

	return ! user_can( $user, 'edit_posts' ) && ! user_can( $user, 'manage_options' );
}

/**
 * Müşterileri yönetim panelinden Hesabım sayfasına yönlendirir.
 */
function ysf_block_admin_for_customers() {
	if ( wp_doing_ajax() || ! is_user_logged_in() || ! ysf_is_customer() ) {
		return;
	}

	$target = ysf_account_url();

	wp_safe_redirect( $target ? $target : home_url( '/' ) );
	exit;
}
add_action( 'admin_init', 'ysf_block_admin_for_customers' );

/**
 * Müşterilere yönetim çubuğu gösterilmez.
 *
 * @param bool $show Gösterilsin mi.
 * @return bool
 */
function ysf_hide_admin_bar( $show ) {
	if ( is_user_logged_in() && ysf_is_customer() ) {
		return false;
	}

	return $show;
}
add_filter( 'show_admin_bar', 'ysf_hide_admin_bar' );

/**
 * Yeni üyeler için kullanıcı adı üretir.
 *
 * @param string $email E-posta.
 * @param string $name  Ad soyad.
 * @return string
 */
function ysf_generate_username( $email, $name = '' ) {
	$base = sanitize_user( (string) strstr( $email, '@', true ), true );

	if ( strlen( $base ) < 3 ) {
		$base = sanitize_user( remove_accents( $name ), true );
	}

	$base = strtolower( preg_replace( '/[^A-Za-z0-9_.\-]/', '', $base ) );
	$base = $base ? substr( $base, 0, 40 ) : 'uye';

	$login = $base;
	$i     = 1;

	while ( username_exists( $login ) ) {
		++$i;
		$login = $base . $i;
	}

	return $login;
}

/**
 * Giriş için kullanıcı adını sadeleştirir.
 *
 * @param string $raw Ham değer.
 * @return string
 */
function ysf_normalize_username( $raw ) {
	$login = strtolower( sanitize_user( (string) $raw, true ) );
	$login = preg_replace( '/[^a-z0-9._\-]/', '', $login );

	return substr( (string) $login, 0, 30 );
}

/**
 * E-posta veya e-posta yazılmış kullanıcı adından giriş adı üretir.
 *
 * @param string $raw E-posta veya yerel kısım.
 * @return string
 */
function ysf_username_from_email( $raw ) {
	$raw = strtolower( trim( (string) $raw ) );

	if ( false !== strpos( $raw, '@' ) ) {
		$raw = (string) strstr( $raw, '@', true );
	}

	return ysf_normalize_username( $raw );
}

/**
 * Kullanıcı adı kurallara uyuyor mu?
 *
 * @param string $login Kullanıcı adı.
 * @return true|\WP_Error
 */
function ysf_validate_username( $login ) {
	$login = ysf_normalize_username( $login );

	if ( strlen( $login ) < 3 ) {
		return new WP_Error( 'username', ysf_t( 'acc_username_short' ) );
	}

	if ( ! preg_match( '/^[a-z][a-z0-9._-]*$/', $login ) ) {
		return new WP_Error( 'username', ysf_t( 'acc_username_invalid' ) );
	}

	$reserved = array( 'admin', 'administrator', 'root', 'wordpress', 'www', 'support', 'info', 'mail', 'garson', 'kasiyer', 'mutfak', 'sef', 'ysf' );

	if ( in_array( $login, $reserved, true ) ) {
		return new WP_Error( 'username', ysf_t( 'acc_username_taken' ) );
	}

	if ( username_exists( $login ) ) {
		return new WP_Error( 'username', ysf_t( 'acc_username_taken' ) );
	}

	return true;
}

/**
 * Kullanıcı adı, e-posta veya telefonla üyeyi bulur.
 *
 * @param string $login Giriş değeri.
 * @return WP_User|null
 */
function ysf_find_user_by_login( $login ) {
	$login = trim( (string) $login );

	if ( '' === $login ) {
		return null;
	}

	if ( is_email( $login ) ) {
		$user = get_user_by( 'email', $login );

		if ( $user ) {
			return $user;
		}
	}

	$user = get_user_by( 'login', $login );

	if ( ! $user && strtolower( $login ) !== $login ) {
		$user = get_user_by( 'login', strtolower( $login ) );
	}

	if ( $user ) {
		return $user;
	}

	$phone = ysf_normalize_phone( $login );

	if ( ! $phone ) {
		return null;
	}

	$users = get_users(
		array(
			'meta_key'   => YSF_META_PHONE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $phone, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'number'     => 1,
		)
	);

	return $users ? $users[0] : null;
}

/**
 * Bekleyen kayıt e-posta anahtarı.
 *
 * @param string $email E-posta.
 * @return string
 */
function ysf_pending_reg_mail_key( $email ) {
	return 'ysf_regmail_' . md5( strtolower( $email ) );
}

/**
 * Bekleyen kayıt telefon anahtarı.
 *
 * @param string $phone Normalize telefon.
 * @return string
 */
function ysf_pending_reg_phone_key( $phone ) {
	return 'ysf_regphone_' . md5( (string) $phone );
}

/**
 * Bekleyen kaydı siler.
 *
 * @param string $token Jeton.
 * @param string $email E-posta.
 * @param string $phone Telefon.
 */
function ysf_clear_pending_reg( $token, $email = '', $phone = '' ) {
	if ( $token ) {
		$pending = get_transient( 'ysf_reg_' . $token );

		if ( is_array( $pending ) ) {
			if ( ! $email && ! empty( $pending['email'] ) ) {
				$email = $pending['email'];
			}

			if ( ! $phone && ! empty( $pending['phone'] ) ) {
				$phone = $pending['phone'];
			}
		}

		delete_transient( 'ysf_reg_' . $token );
	}

	if ( $email ) {
		delete_transient( ysf_pending_reg_mail_key( $email ) );
	}

	if ( $phone ) {
		delete_transient( ysf_pending_reg_phone_key( $phone ) );
	}
}

/**
 * 6 haneli OTP üretir.
 *
 * @return string
 */
function ysf_otp_make() {
	return (string) wp_rand( 100000, 999999 );
}

/**
 * OTP özeti.
 *
 * @param string $code Düz kod.
 * @return string
 */
function ysf_otp_hash( $code ) {
	return hash_hmac( 'sha256', (string) $code, wp_salt( 'auth' ) );
}

/**
 * OTP eşleşmesi.
 *
 * @param string $code Düz kod.
 * @param string $hash Saklanan özet.
 * @return bool
 */
function ysf_otp_matches( $code, $hash ) {
	if ( ! $code || ! $hash ) {
		return false;
	}

	return hash_equals( (string) $hash, ysf_otp_hash( $code ) );
}

/**
 * Bekleyen kayıttaki OTP geçerli mi.
 *
 * @param array  $pending Bekleyen kayıt.
 * @param string $code    Girilen kod.
 * @return true|WP_Error
 */
function ysf_pending_otp_ok( $pending, $code ) {
	if ( isset( $pending['code_expires'] ) && time() > (int) $pending['code_expires'] ) {
		return new WP_Error( 'otp_expired', ysf_t( 'acc_verify_code_exp' ) );
	}

	$ok = false;

	if ( ! empty( $pending['code_hash'] ) ) {
		$ok = ysf_otp_matches( $code, $pending['code_hash'] );
	} elseif ( isset( $pending['code'] ) ) {
		$ok = hash_equals( (string) $pending['code'], (string) $code );
	}

	if ( ! $ok ) {
		return new WP_Error( 'otp_wrong', ysf_t( 'acc_verify_wrong' ) );
	}

	return true;
}

/**
 * Netgsm SMS bilgileri dolu mu.
 *
 * @return bool
 */
function ysf_sms_configured() {
	return (bool) ysf_get_option( 'ysf_sms_usercode', '' )
		&& (bool) ysf_get_option( 'ysf_sms_pass', '' )
		&& (bool) ysf_get_option( 'ysf_sms_header', '' );
}

/**
 * SMS için ülke kodlu numara (90555...).
 *
 * @param string $phone Normalize telefon.
 * @return string
 */
function ysf_sms_msisdn( $phone ) {
	$phone = ysf_normalize_phone( $phone );

	if ( ! $phone ) {
		return '';
	}

	return ltrim( $phone, '+' );
}

/**
 * OTP SMS gönderir (Netgsm).
 *
 * @param string $phone Normalize telefon.
 * @param string $code  OTP.
 * @return bool
 */
function ysf_send_otp_sms( $phone, $code ) {
	if ( ! $code || ! ysf_sms_configured() ) {
		return false;
	}

	$msisdn = ysf_sms_msisdn( $phone );

	if ( ! $msisdn ) {
		return false;
	}

	$phone_key = 'ysf_smsn_' . md5( $msisdn );
	$sent_n    = (int) get_transient( $phone_key );

	if ( $sent_n >= 6 || ysf_rate_limited( 'otp_sms_ip', 10, 30 * MINUTE_IN_SECONDS ) ) {
		return false;
	}

	$header  = sanitize_text_field( ysf_get_option( 'ysf_sms_header', '' ) );
	$message = sprintf(
		'YSF Foodlab OTP kodunuz: %s. 10 dk gecerlidir. Kimseyle paylasmayin.',
		$code
	);

	$response = wp_remote_post(
		'https://api.netgsm.com.tr/sms/send/get',
		array(
			'timeout' => 12,
			'headers' => array(
				'Accept' => 'text/plain',
			),
			'body'    => array(
				'usercode'  => ysf_get_option( 'ysf_sms_usercode', '' ),
				'password'  => ysf_get_option( 'ysf_sms_pass', '' ),
				'gsmno'     => $msisdn,
				'message'   => $message,
				'msgheader' => $header,
				'dil'       => 'TR',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = trim( (string) wp_remote_retrieve_body( $response ) );

	if ( ! preg_match( '/^(00|01|02)\b/', $body ) ) {
		return false;
	}

	set_transient( $phone_key, $sent_n + 1, 30 * MINUTE_IN_SECONDS );

	return true;
}

/**
 * Doğrulama kodu e-postası gönderir.
 *
 * @param array  $pending Bekleyen kayıt.
 * @param string $code    Düz OTP.
 * @return bool
 */
function ysf_send_verify_email( $pending, $code = '' ) {
	if ( ! $code && isset( $pending['code'] ) ) {
		$code = $pending['code'];
	}

	$email = isset( $pending['email'] ) ? $pending['email'] : '';

	if ( ! $code || ! is_email( $email ) ) {
		return false;
	}

	return ysf_send_notification(
		$email,
		sprintf(
			/* translators: %s: site adı. */
			__( '%s — OTP doğrulama kodu', 'ysffoodlab' ),
			get_bloginfo( 'name' )
		),
		array(
			ysf_t( 'acc_username' )    => isset( $pending['username'] ) ? $pending['username'] : '',
			ysf_t( 'acc_verify_code' ) => $code,
		),
		sprintf( ysf_t( 'acc_verify_mail' ), $code )
	);
}

/**
 * Kayıt OTP’sini SMS ve e-posta ile gönderir.
 *
 * @param array  $pending Bekleyen kayıt.
 * @param string $code    Düz OTP.
 * @return array{mail:bool,sms:bool}
 */
function ysf_send_register_otp( $pending, $code ) {
	$mail = ysf_send_verify_email( $pending, $code );
	$sms  = false;

	if ( ! empty( $pending['phone'] ) ) {
		$sms = ysf_send_otp_sms( $pending['phone'], $code );
	}

	return array(
		'mail' => (bool) $mail,
		'sms'  => (bool) $sms,
	);
}

/**
 * Kayıt doğrulama ekranı cevabı.
 *
 * @param string $token     Jeton.
 * @param array  $pending   Bekleyen kayıt.
 * @param array  $sent      mail/sms bayrakları.
 * @param string $plain_code Düz kod (yalnızca her iki kanal da düşerse).
 * @return array
 */
function ysf_register_verify_payload( $token, $pending, $sent, $plain_code = '' ) {
	$email = isset( $pending['email'] ) ? $pending['email'] : '';
	$phone = isset( $pending['phone'] ) ? ysf_phone_display( $pending['phone'] ) : '';
	$mail  = ! empty( $sent['mail'] );
	$sms   = ! empty( $sent['sms'] );
	$out   = array(
		'step'     => 'verify',
		'token'    => $token,
		'email'    => $email,
		'phone'    => $phone,
		'mailSent' => $mail,
		'smsSent'  => $sms,
	);

	if ( $sms && $mail ) {
		$out['message'] = sprintf( ysf_t( 'acc_verify_sent_both' ), $phone, $email );

		return $out;
	}

	if ( $sms ) {
		$out['message'] = sprintf( ysf_t( 'acc_verify_sent_sms' ), $phone );

		return $out;
	}

	if ( $mail ) {
		$out['message'] = sprintf( ysf_t( 'acc_verify_sent_mail' ), $email );

		return $out;
	}

	$out['message']    = sprintf( ysf_t( 'acc_verify_onscreen' ), $plain_code );
	$out['code']       = $plain_code;
	$out['mailFailed'] = true;

	return $out;
}

/**
 * Formdan gelen adres verisini alır.
 *
 * @return array
 */
function ysf_address_from_request() {
	$raw = array();

	foreach ( array_keys( ysf_address_fields() ) as $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce çağıran uçta doğrulanır.
		$raw[ $key ] = isset( $_POST[ 'addr_' . $key ] ) ? $_POST[ 'addr_' . $key ] : '';
	}

	return ysf_sanitize_address( $raw );
}

/**
 * Giriş sonrası yönlendirme adresi.
 *
 * @param WP_User $user Üye.
 * @return string
 */
function ysf_after_login_redirect( $user ) {
	$redirect = ysf_account_url();

	if ( ysf_can_take_orders( $user->ID ) && ysf_waiter_url() ) {
		$redirect = ysf_waiter_url();
	}

	if ( ysf_can_manage_menu( $user->ID ) ) {
		$redirect = ysf_account_url() . '#ysf-kitchen';
	}

	if ( ysf_can_view_kds( $user->ID ) && ysf_kds_url() && ! ysf_can_manage_menu( $user->ID ) && ! ysf_can_take_orders( $user->ID ) ) {
		$redirect = ysf_kds_url();
	}

	if ( ysf_can_cashier( $user->ID ) && ysf_cashier_url() && ! ysf_can_manage_menu( $user->ID ) && ! ysf_can_take_orders( $user->ID ) && ! ysf_can_view_kds( $user->ID ) ) {
		$redirect = ysf_cashier_url();
	}

	$requested = isset( $_POST['redirect'] ) ? wp_unslash( $_POST['redirect'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$requested = wp_validate_redirect( $requested, '' );

	if ( $requested && ysf_is_staff_redirect( $requested ) ) {
		$kds_path    = untrailingslashit( (string) wp_parse_url( ysf_kds_url(), PHP_URL_PATH ) );
		$waiter_path = untrailingslashit( (string) wp_parse_url( ysf_waiter_url(), PHP_URL_PATH ) );
		$cash_path   = untrailingslashit( (string) wp_parse_url( ysf_cashier_url(), PHP_URL_PATH ) );
		$req_path    = untrailingslashit( (string) wp_parse_url( $requested, PHP_URL_PATH ) );

		if ( $kds_path && $req_path === $kds_path && ysf_can_view_kds( $user->ID ) ) {
			$redirect = $requested;
		} elseif ( $waiter_path && $req_path === $waiter_path && ysf_can_take_orders( $user->ID ) ) {
			$redirect = $requested;
		} elseif ( $cash_path && $req_path === $cash_path && ysf_can_cashier( $user->ID ) ) {
			$redirect = $requested;
		}
	}

	return $redirect;
}

/**
 * Oturumu açar.
 *
 * @param WP_User $user     Üye.
 * @param bool    $remember Beni hatırla.
 */
function ysf_sign_in_user( $user, $remember = true ) {
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, $remember );

	try {
		do_action( 'wp_login', $user->user_login, $user );
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
	}
}

/**
 * Üye kaydı: doğrulama kodu gönderir, hesabı henüz oluşturmaz.
 */
function ysf_ajax_register() {
	ysf_require_ajax_nonce();

	if ( is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_logged_in' ) ), 400 );
	}

	if ( ! ysf_get_option( 'ysf_acc_enabled', true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_closed' ) ), 403 );
	}

	if ( ysf_honeypot_tripped() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$raw_user  = isset( $_POST['username'] ) ? trim( (string) wp_unslash( $_POST['username'] ) ) : '';
	$raw_email = isset( $_POST['email'] ) ? trim( (string) wp_unslash( $_POST['email'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? ysf_normalize_phone( wp_unslash( $_POST['phone'] ) ) : '';
	$pass      = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
	$pass2     = isset( $_POST['password2'] ) ? (string) wp_unslash( $_POST['password2'] ) : '';

	if ( false !== strpos( $raw_user, '@' ) ) {
		if ( ! $raw_email ) {
			$raw_email = $raw_user;
		}

		$raw_user = (string) strstr( $raw_user, '@', true );
	}

	$username = ysf_normalize_username( $raw_user );
	$email    = sanitize_email( $raw_email );

	if ( ! $username && $email ) {
		$username = ysf_username_from_email( $email );
	}

	if ( empty( $_POST['consent'] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_need_consent' ), 'field' => 'consent' ), 400 );
	}

	if ( ! $name ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_name' ) . ': ' . ysf_t( 'form_required' ), 'field' => 'name' ), 400 );
	}

	if ( ! $email ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_email_invalid' ), 'field' => 'email' ), 400 );
	}

	$valid_login = ysf_validate_username( $username );

	if ( is_wp_error( $valid_login ) ) {
		wp_send_json_error( array( 'message' => $valid_login->get_error_message(), 'field' => 'username' ), 400 );
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_email_invalid' ), 'field' => 'email' ), 400 );
	}

	if ( ! $pass ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_pass_short' ), 'field' => 'password' ), 400 );
	}

	if ( email_exists( $email ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_email_taken' ), 'field' => 'email' ), 400 );
	}

	if ( ! $phone ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_phone_invalid' ), 'field' => 'phone' ), 400 );
	}

	if ( ysf_phone_in_use( $phone ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_phone_taken' ), 'field' => 'phone' ), 400 );
	}

	if ( strlen( $pass ) < 8 ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_pass_short' ), 'field' => 'password' ), 400 );
	}

	if ( $pass !== $pass2 ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_pass_mismatch' ), 'field' => 'password2' ), 400 );
	}

	$address = ysf_address_from_request();
	$valid   = ysf_validate_address( $address );

	if ( is_wp_error( $valid ) ) {
		$data = $valid->get_error_data();

		wp_send_json_error(
			array(
				'message' => $valid->get_error_message(),
				'field'   => 'addr_' . ( isset( $data['field'] ) ? $data['field'] : 'il' ),
			),
			400
		);
	}

	if ( ysf_rate_limited( 'reg_mail', 12, 15 * MINUTE_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$old_mail  = get_transient( ysf_pending_reg_mail_key( $email ) );
	$old_phone = get_transient( ysf_pending_reg_phone_key( $phone ) );

	if ( is_string( $old_mail ) && $old_mail ) {
		ysf_clear_pending_reg( $old_mail );
	}

	if ( is_string( $old_phone ) && $old_phone && $old_phone !== $old_mail ) {
		ysf_clear_pending_reg( $old_phone );
	}

	$token   = wp_generate_password( 32, false );
	$code    = ysf_otp_make();
	$pending = array(
		'name'         => $name,
		'username'     => $username,
		'email'        => $email,
		'phone'        => $phone,
		'pass'         => $pass,
		'address'      => $address,
		'code_hash'    => ysf_otp_hash( $code ),
		'code_expires' => time() + 10 * MINUTE_IN_SECONDS,
		'tries'        => 0,
		'lang'         => ysf_lang(),
	);

	set_transient( 'ysf_reg_' . $token, $pending, 30 * MINUTE_IN_SECONDS );
	set_transient( ysf_pending_reg_mail_key( $email ), $token, 30 * MINUTE_IN_SECONDS );
	set_transient( ysf_pending_reg_phone_key( $phone ), $token, 30 * MINUTE_IN_SECONDS );

	$sent  = ysf_send_register_otp( $pending, $code );
	$plain = ( empty( $sent['mail'] ) && empty( $sent['sms'] ) ) ? $code : '';

	wp_send_json_success( ysf_register_verify_payload( $token, $pending, $sent, $plain ) );
}
add_action( 'wp_ajax_nopriv_ysf_register', 'ysf_ajax_register' );
add_action( 'wp_ajax_ysf_register', 'ysf_ajax_register' );

/**
 * Kayıt doğrulama kodunu tekrar gönderir.
 */
function ysf_ajax_resend_verify() {
	ysf_require_ajax_nonce();

	$token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
	$pending = $token ? get_transient( 'ysf_reg_' . $token ) : false;

	if ( ! is_array( $pending ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_verify_expired' ) ), 400 );
	}

	if ( ysf_rate_limited( 'resend_mail', 8, 15 * MINUTE_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$code                    = ysf_otp_make();
	$pending['code_hash']    = ysf_otp_hash( $code );
	$pending['code_expires'] = time() + 10 * MINUTE_IN_SECONDS;
	$pending['tries']        = 0;
	unset( $pending['code'] );
	set_transient( 'ysf_reg_' . $token, $pending, 30 * MINUTE_IN_SECONDS );

	$sent  = ysf_send_register_otp( $pending, $code );
	$plain = ( empty( $sent['mail'] ) && empty( $sent['sms'] ) ) ? $code : '';

	wp_send_json_success( ysf_register_verify_payload( $token, $pending, $sent, $plain ) );
}
add_action( 'wp_ajax_nopriv_ysf_resend_verify', 'ysf_ajax_resend_verify' );
add_action( 'wp_ajax_ysf_resend_verify', 'ysf_ajax_resend_verify' );

/**
 * Doğrulama kodunu onaylayıp üyeliği oluşturur.
 */
function ysf_ajax_verify_register() {
	ysf_require_ajax_nonce();

	if ( is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_logged_in' ) ), 400 );
	}

	$token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
	$code    = isset( $_POST['code'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['code'] ) ) : '';
	$pending = $token ? get_transient( 'ysf_reg_' . $token ) : false;

	if ( ! is_array( $pending ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_verify_expired' ) ), 400 );
	}

	if ( ysf_rate_limited( 'verify_try', 30, 15 * MINUTE_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$otp_ok = ysf_pending_otp_ok( $pending, $code );

	if ( is_wp_error( $otp_ok ) ) {
		if ( 'otp_expired' === $otp_ok->get_error_code() ) {
			wp_send_json_error( array( 'message' => $otp_ok->get_error_message() ), 400 );
		}

		$pending['tries'] = isset( $pending['tries'] ) ? (int) $pending['tries'] + 1 : 1;

		if ( $pending['tries'] > 8 ) {
			ysf_clear_pending_reg( $token, $pending['email'], isset( $pending['phone'] ) ? $pending['phone'] : '' );
			wp_send_json_error( array( 'message' => ysf_t( 'acc_verify_locked' ) ), 400 );
		}

		set_transient( 'ysf_reg_' . $token, $pending, 30 * MINUTE_IN_SECONDS );
		wp_send_json_error( array( 'message' => $otp_ok->get_error_message() ), 400 );
	}

	if ( email_exists( $pending['email'] ) ) {
		ysf_clear_pending_reg( $token, $pending['email'], isset( $pending['phone'] ) ? $pending['phone'] : '' );
		wp_send_json_error( array( 'message' => ysf_t( 'acc_email_taken' ) ), 400 );
	}

	if ( username_exists( $pending['username'] ) ) {
		ysf_clear_pending_reg( $token, $pending['email'], isset( $pending['phone'] ) ? $pending['phone'] : '' );
		wp_send_json_error( array( 'message' => ysf_t( 'acc_username_taken' ) ), 400 );
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $pending['username'],
			'user_email'   => $pending['email'],
			'user_pass'    => $pending['pass'],
			'display_name' => $pending['name'],
			'first_name'   => $pending['name'],
			'role'         => 'subscriber',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	update_user_meta( $user_id, YSF_META_PHONE, $pending['phone'] );
	update_user_meta( $user_id, '_ysf_verified_phone', '1' );
	update_user_meta( $user_id, '_ysf_lang', isset( $pending['lang'] ) ? $pending['lang'] : ysf_lang() );
	ysf_save_user_address( $user_id, 'home', isset( $pending['address'] ) ? $pending['address'] : array() );
	ysf_clear_pending_reg( $token, $pending['email'], isset( $pending['phone'] ) ? $pending['phone'] : '' );

	$user = get_userdata( $user_id );

	if ( $user ) {
		ysf_sign_in_user( $user, true );
	}

	try {
		ysf_send_notification(
			ysf_get_option( 'ysf_email', '' ),
			__( 'Yeni üye kaydı', 'ysffoodlab' ),
			array(
				__( 'Ad Soyad', 'ysffoodlab' )       => $pending['name'],
				__( 'Kullanıcı adı', 'ysffoodlab' ) => $pending['username'],
				__( 'E-posta', 'ysffoodlab' )        => $pending['email'],
				__( 'Telefon', 'ysffoodlab' )        => ysf_phone_display( $pending['phone'] ),
				__( 'Adres', 'ysffoodlab' )          => ysf_address_one_line( isset( $pending['address'] ) ? $pending['address'] : array() ),
			),
			__( 'Web sitesinden yeni bir üye kaydoldu.', 'ysffoodlab' )
		);

		ysf_send_notification(
			$pending['email'],
			sprintf(
				/* translators: %s: site adı. */
				__( '%s — üyeliğiniz oluşturuldu', 'ysffoodlab' ),
				get_bloginfo( 'name' )
			),
			array(
				ysf_t( 'form_name' )    => $pending['name'],
				ysf_t( 'acc_username' ) => $pending['username'],
				ysf_t( 'form_email' )   => $pending['email'],
				ysf_t( 'form_phone' )   => ysf_phone_display( $pending['phone'] ),
			),
			ysf_t( 'acc_welcome_mail' )
		);
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
	}

	wp_send_json_success(
		array(
			'message'  => ysf_t( 'acc_registered' ),
			'redirect' => ysf_account_url(),
		)
	);
}
add_action( 'wp_ajax_nopriv_ysf_verify_register', 'ysf_ajax_verify_register' );
add_action( 'wp_ajax_ysf_verify_register', 'ysf_ajax_verify_register' );

/**
 * Giriş.
 */

function ysf_ajax_login() {
	ysf_require_ajax_nonce();

	if ( ysf_honeypot_tripped() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$login    = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
	$pass     = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
	$remember = ! empty( $_POST['remember'] );

	if ( ! $login || ! $pass ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	if ( ysf_rate_limited( 'login_try', 20, 15 * MINUTE_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$found = ysf_find_user_by_login( $login );

	if ( ! $found ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_login_error' ) ), 401 );
	}

	$user = wp_signon(
		array(
			'user_login'    => $found->user_login,
			'user_password' => $pass,
			'remember'      => $remember,
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		$code = $user->get_error_code();

		if ( 'ysf_2fa' === $code || 'ysf_2fa_setup' === $code ) {
			wp_send_json_success(
				array_merge(
					array(
						'step'    => 'ysf_2fa_setup' === $code ? '2fa_setup' : '2fa',
						'message' => $user->get_error_message(),
					),
					ysf_2fa_client_payload( $found )
				)
			);
		}

		if ( 'ysf_2fa_locked' === $code ) {
			wp_send_json_error( array( 'message' => $user->get_error_message() ), 429 );
		}

		wp_send_json_error( array( 'message' => ysf_t( 'acc_login_error' ) ), 401 );
	}

	wp_set_current_user( $user->ID );

	$payload = array(
		'message'  => ysf_t( 'acc_login_ok' ),
		'redirect' => ysf_after_login_redirect( $user ),
	);

	$codes = get_transient( 'ysf_2fa_new_codes_' . $user->ID );

	if ( is_array( $codes ) && $codes ) {
		$payload['backups'] = $codes;
		$payload['message'] = ysf_t( 'tfa_on' );
	}

	wp_send_json_success( $payload );
}
add_action( 'wp_ajax_nopriv_ysf_login', 'ysf_ajax_login' );
add_action( 'wp_ajax_ysf_login', 'ysf_ajax_login' );

/**
 * Şifre sıfırlama bağlantısı gönderir.
 */
function ysf_ajax_lost_password() {
	ysf_require_ajax_nonce();

	$login = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';

	if ( ! $login ) {
		wp_send_json_success( array( 'message' => ysf_t( 'acc_forgot_sent' ) ) );
	}

	if ( ysf_rate_limited( 'lost_mail', 8, 15 * MINUTE_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$user = ysf_find_user_by_login( $login );

	if ( $user ) {
		$key = get_password_reset_key( $user );

		if ( ! is_wp_error( $key ) ) {
			$url = ysf_account_url(
				array(
					'ysf_rp' => '1',
					'key'    => $key,
					'login'  => $user->user_login,
				)
			);

			ysf_send_notification(
				$user->user_email,
				sprintf(
					/* translators: %s: site adı. */
					__( '%s — şifre yenileme', 'ysffoodlab' ),
					get_bloginfo( 'name' )
				),
				array(
					ysf_t( 'acc_username' ) => $user->user_login,
					ysf_t( 'form_email' )   => $user->user_email,
				),
				ysf_t( 'acc_forgot_mail' ),
				array(
					'label' => ysf_t( 'acc_forgot_cta' ),
					'url'   => $url,
				)
			);
		}
	}

	wp_send_json_success( array( 'message' => ysf_t( 'acc_forgot_sent' ) ) );
}
add_action( 'wp_ajax_nopriv_ysf_lost_password', 'ysf_ajax_lost_password' );
add_action( 'wp_ajax_ysf_lost_password', 'ysf_ajax_lost_password' );

/**
 * Şifre yenileme bağlantısındaki yeni şifreyi kaydeder.
 */
function ysf_ajax_reset_password() {
	ysf_require_ajax_nonce();

	if ( ysf_rate_limited( 'resetpass', 8, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$key      = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	$login    = isset( $_POST['login'] ) ? sanitize_user( wp_unslash( $_POST['login'] ), true ) : '';
	$pass     = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
	$pass2    = isset( $_POST['password2'] ) ? (string) wp_unslash( $_POST['password2'] ) : '';
	$remember = true;

	if ( ! $key || ! $login ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_reset_invalid' ) ), 400 );
	}

	if ( strlen( $pass ) < 8 ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_pass_short' ), 'field' => 'password' ), 400 );
	}

	if ( $pass !== $pass2 ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_pass_mismatch' ), 'field' => 'password2' ), 400 );
	}

	$user = check_password_reset_key( $key, $login );

	if ( is_wp_error( $user ) || ! $user ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_reset_invalid' ) ), 400 );
	}

	reset_password( $user, $pass );

	if ( ysf_user_needs_2fa( $user ) ) {
		wp_send_json_success(
			array(
				'message'  => ysf_t( 'acc_reset_ok_login' ),
				'redirect' => ysf_account_url(),
			)
		);
	}

	ysf_sign_in_user( $user, $remember );

	wp_send_json_success(
		array(
			'message'  => ysf_t( 'acc_reset_ok' ),
			'redirect' => ysf_after_login_redirect( $user ),
		)
	);
}
add_action( 'wp_ajax_nopriv_ysf_reset_password', 'ysf_ajax_reset_password' );
add_action( 'wp_ajax_ysf_reset_password', 'ysf_ajax_reset_password' );

/**
 * Profil bilgilerini günceller.
 */
function ysf_ajax_save_profile() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_login_required' ) ), 401 );
	}

	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? ysf_normalize_phone( wp_unslash( $_POST['phone'] ) ) : '';

	if ( ! $name || ! $email ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_email_invalid' ), 'field' => 'email' ), 400 );
	}

	$owner = email_exists( $email );

	if ( $owner && (int) $owner !== $user_id ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_email_taken' ), 'field' => 'email' ), 400 );
	}

	if ( ! $phone ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_phone_invalid' ), 'field' => 'phone' ), 400 );
	}

	if ( ysf_phone_in_use( $phone, $user_id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_phone_taken' ), 'field' => 'phone' ), 400 );
	}

	$updated = wp_update_user(
		array(
			'ID'           => $user_id,
			'user_email'   => $email,
			'display_name' => $name,
			'first_name'   => $name,
		)
	);

	if ( is_wp_error( $updated ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	update_user_meta( $user_id, YSF_META_PHONE, $phone );

	// Şifre değişikliği isteğe bağlıdır.
	$current = isset( $_POST['password_current'] ) ? (string) wp_unslash( $_POST['password_current'] ) : '';
	$new     = isset( $_POST['password_new'] ) ? (string) wp_unslash( $_POST['password_new'] ) : '';

	if ( $new ) {
		$user = get_userdata( $user_id );

		if ( ! $current || ! wp_check_password( $current, $user->user_pass, $user_id ) ) {
			wp_send_json_error( array( 'message' => ysf_t( 'acc_pass_wrong' ), 'field' => 'password_current' ), 400 );
		}

		if ( strlen( $new ) < 8 ) {
			wp_send_json_error( array( 'message' => ysf_t( 'acc_pass_short' ), 'field' => 'password_new' ), 400 );
		}

		wp_set_password( $new, $user_id );
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		wp_send_json_success( array( 'message' => ysf_t( 'acc_pass_changed' ) ) );
	}

	wp_send_json_success( array( 'message' => ysf_t( 'acc_saved' ) ) );
}
add_action( 'wp_ajax_ysf_save_profile', 'ysf_ajax_save_profile' );

/**
 * Adres kaydeder veya siler.
 */
function ysf_ajax_save_address() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_login_required' ) ), 401 );
	}

	$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';

	if ( ! array_key_exists( $type, ysf_address_types() ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$remove = ! empty( $_POST['remove'] );

	if ( $remove ) {
		delete_user_meta( $user_id, YSF_META_ADDR . $type );

		wp_send_json_success(
			array(
				'message' => ysf_t( 'acc_addr_deleted' ),
				'type'    => $type,
				'address' => array(),
				'line'    => '',
			)
		);
	}

	$address = ysf_address_from_request();
	$valid   = ysf_validate_address( $address );

	if ( is_wp_error( $valid ) ) {
		$data = $valid->get_error_data();

		wp_send_json_error(
			array(
				'message' => $valid->get_error_message(),
				'field'   => 'addr_' . ( isset( $data['field'] ) ? $data['field'] : 'il' ),
			),
			400
		);
	}

	ysf_save_user_address( $user_id, $type, $address );

	wp_send_json_success(
		array(
			'message' => ysf_address_is_empty( $address ) ? ysf_t( 'acc_addr_deleted' ) : ysf_t( 'acc_addr_saved' ),
			'type'    => $type,
			'address' => $address,
			'line'    => ysf_address_one_line( $address ),
		)
	);
}
add_action( 'wp_ajax_ysf_save_address', 'ysf_ajax_save_address' );

/**
 * Sipariş ve rezervasyon formları için üye bilgileri.
 *
 * @return array
 */
function ysf_current_user_prefill() {
	if ( ! is_user_logged_in() ) {
		return array();
	}

	$user      = wp_get_current_user();
	$addresses = array();

	foreach ( ysf_address_types() as $type => $label ) {
		$address = ysf_get_user_address( $user->ID, $type );

		if ( $address ) {
			$addresses[ $type ] = array(
				'label' => $label,
				'line'  => ysf_address_one_line( $address ),
			);
		}
	}

	return array(
		'name'      => $user->display_name,
		'email'     => $user->user_email,
		'phone'     => ysf_phone_display( ysf_user_phone( $user->ID ) ),
		'addresses' => $addresses,
	);
}

/**
 * Üyelerin sipariş ve rezervasyon kayıtlarını hesabıyla ilişkilendirir.
 *
 * @param int $post_id Kayıt kimliği.
 */
function ysf_attach_user_to_record( $post_id ) {
	$user_id = get_current_user_id();

	if ( $user_id && $post_id ) {
		update_post_meta( $post_id, '_ysf_user_id', $user_id );
	}
}

/**
 * Üyenin son kayıtları.
 *
 * @param string $post_type Kayıt tipi.
 * @param int    $limit     Adet.
 * @return WP_Post[]
 */
function ysf_user_records( $post_type, $limit = 5 ) {
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => (int) $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_ysf_user_id',
					'value' => $user_id,
				),
			),
		)
	);
}
