<?php
/**
 * Üye hesapları: kayıt, giriş, profil ve adres defteri.
 *
 * Müşteriler WordPress kullanıcısı olarak saklanır (rol: subscriber). Telefon
 * ve adresler kullanıcı meta alanlarında tutulur. Panele erişim kapalıdır,
 * tüm işlemler ön yüzdeki Hesabım sayfasından yapılır.
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
			$value = mb_substr( $value, 0, 300 );
		} else {
			$value = sanitize_text_field( $value );
			$value = mb_substr( $value, 0, 120 );
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
 * Üye kaydı.
 */
function ysf_ajax_register() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_logged_in' ) ), 400 );
	}

	if ( ! ysf_get_option( 'ysf_acc_enabled', true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_closed' ) ), 403 );
	}

	if ( ! empty( $_POST['ysf_hp'] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( ysf_rate_limited( 'register', 5, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? ysf_normalize_phone( wp_unslash( $_POST['phone'] ) ) : '';
	$pass  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
	$pass2 = isset( $_POST['password2'] ) ? (string) wp_unslash( $_POST['password2'] ) : '';

	if ( ! $name || ! $email || ! $pass ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_email_invalid' ), 'field' => 'email' ), 400 );
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

	$user_id = wp_insert_user(
		array(
			'user_login'   => ysf_generate_username( $email, $name ),
			'user_email'   => $email,
			'user_pass'    => $pass,
			'display_name' => $name,
			'first_name'   => $name,
			'role'         => 'subscriber',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	update_user_meta( $user_id, YSF_META_PHONE, $phone );
	update_user_meta( $user_id, '_ysf_lang', ysf_lang() );
	ysf_save_user_address( $user_id, 'home', $address );

	$user = get_userdata( $user_id );

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	do_action( 'wp_login', $user->user_login, $user );

	ysf_send_notification(
		ysf_get_option( 'ysf_email', '' ),
		__( 'Yeni üye kaydı', 'ysffoodlab' ),
		array(
			__( 'Ad Soyad', 'ysffoodlab' ) => $name,
			__( 'E-posta', 'ysffoodlab' )  => $email,
			__( 'Telefon', 'ysffoodlab' )  => ysf_phone_display( $phone ),
			__( 'Adres', 'ysffoodlab' )    => ysf_address_one_line( $address ),
		),
		__( 'Web sitesinden yeni bir üye kaydoldu.', 'ysffoodlab' )
	);

	ysf_send_notification(
		$email,
		sprintf(
			/* translators: %s: site adı. */
			__( '%s — üyeliğiniz oluşturuldu', 'ysffoodlab' ),
			get_bloginfo( 'name' )
		),
		array(
			ysf_t( 'form_name' )  => $name,
			ysf_t( 'form_email' ) => $email,
			ysf_t( 'form_phone' ) => ysf_phone_display( $phone ),
		),
		ysf_t( 'acc_welcome_mail' )
	);

	wp_send_json_success(
		array(
			'message'  => ysf_t( 'acc_registered' ),
			'redirect' => ysf_account_url(),
		)
	);
}
add_action( 'wp_ajax_nopriv_ysf_register', 'ysf_ajax_register' );
add_action( 'wp_ajax_ysf_register', 'ysf_ajax_register' );

/**
 * Giriş.
 */
function ysf_ajax_login() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ! empty( $_POST['ysf_hp'] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( ysf_rate_limited( 'login', 12, 15 * MINUTE_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$login    = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
	$pass     = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
	$remember = ! empty( $_POST['remember'] );

	if ( ! $login || ! $pass ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	// Telefon numarasıyla da giriş yapılabilir.
	if ( ! is_email( $login ) && ! username_exists( $login ) ) {
		$phone = ysf_normalize_phone( $login );

		if ( $phone ) {
			$users = get_users(
				array(
					'meta_key'   => YSF_META_PHONE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $phone, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'number'     => 1,
					'fields'     => array( 'user_email' ),
				)
			);

			if ( $users ) {
				$login = $users[0]->user_email;
			}
		}
	}

	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $pass,
			'remember'      => $remember,
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_login_error' ) ), 401 );
	}

	wp_set_current_user( $user->ID );

	wp_send_json_success(
		array(
			'message'  => ysf_t( 'acc_login_ok' ),
			'redirect' => ysf_account_url(),
		)
	);
}
add_action( 'wp_ajax_nopriv_ysf_login', 'ysf_ajax_login' );
add_action( 'wp_ajax_ysf_login', 'ysf_ajax_login' );

/**
 * Şifre sıfırlama bağlantısı gönderir.
 */
function ysf_ajax_lost_password() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ysf_rate_limited( 'lostpass', 5, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	$login = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';

	if ( $login && function_exists( 'retrieve_password' ) ) {
		retrieve_password( $login );
	}

	// Kayıtlı olmayan adresler için de aynı yanıt döner (hesap ifşasını önler).
	wp_send_json_success( array( 'message' => ysf_t( 'acc_forgot_sent' ) ) );
}
add_action( 'wp_ajax_nopriv_ysf_lost_password', 'ysf_ajax_lost_password' );
add_action( 'wp_ajax_ysf_lost_password', 'ysf_ajax_lost_password' );

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
