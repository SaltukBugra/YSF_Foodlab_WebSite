<?php
/**
 * Tema özelleştirici ayarları (Görünüm > Özelleştir).
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tek bir ayar + kontrol ekler.
 *
 * @param WP_Customize_Manager $wp_customize Yönetici.
 * @param string               $id           Ayar kimliği.
 * @param array                $args         label, section, type, default, choices, description, sanitize.
 */
function ysf_add_setting( $wp_customize, $id, $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'label'       => $id,
			'section'     => 'ysf_business',
			'type'        => 'text',
			'default'     => '',
			'choices'     => array(),
			'description' => '',
			'sanitize'    => null,
		)
	);

	if ( null === $args['sanitize'] ) {
		switch ( $args['type'] ) {
			case 'checkbox':
				$args['sanitize'] = 'ysf_sanitize_checkbox';
				break;
			case 'url':
				$args['sanitize'] = 'esc_url_raw';
				break;
			case 'email':
				$args['sanitize'] = 'sanitize_email';
				break;
			case 'select':
				$args['sanitize'] = 'sanitize_text_field';
				break;
			case 'password':
				$args['sanitize'] = 'ysf_sanitize_smtp_pass';
				break;
			case 'textarea':
				$args['sanitize'] = 'sanitize_textarea_field';
				break;
			case 'number':
				$args['sanitize'] = 'ysf_sanitize_float';
				break;
			default:
				$args['sanitize'] = 'sanitize_text_field';
		}
	}

	$wp_customize->add_setting(
		$id,
		array(
			'default'           => $args['default'],
			'sanitize_callback' => $args['sanitize'],
			'transport'         => 'refresh',
		)
	);

	if ( 'image' === $args['type'] ) {
		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				$id,
				array(
					'label'       => $args['label'],
					'section'     => $args['section'],
					'description' => $args['description'],
				)
			)
		);
		return;
	}

	$control = array(
		'label'       => $args['label'],
		'section'     => $args['section'],
		'type'        => $args['type'],
		'description' => $args['description'],
	);

	if ( ! empty( $args['choices'] ) ) {
		$control['choices'] = $args['choices'];
	}

	$wp_customize->add_control( $id, $control );
}

/**
 * Onay kutusu temizleme.
 *
 * @param mixed $value Değer.
 * @return bool
 */
function ysf_sanitize_checkbox( $value ) {
	return ( isset( $value ) && true === (bool) $value );
}

/**
 * Ondalık sayı temizleme.
 *
 * @param mixed $value Değer.
 * @return float
 */
function ysf_sanitize_float( $value ) {
	return (float) str_replace( ',', '.', (string) $value );
}

/**
 * Masa sayısı temizleme.
 *
 * @param mixed $value Değer.
 * @return int
 */
function ysf_sanitize_table_count( $value ) {
	return max( 1, min( 80, absint( $value ) ) );
}

/**
 * SMTP şifresi: özel karakterleri silmez.
 *
 * @param mixed $value Değer.
 * @return string
 */
function ysf_sanitize_smtp_pass( $value, $setting = null ) {
	if ( ! is_string( $value ) ) {
		$value = '';
	}

	$value = wp_unslash( $value );

	if ( '' === $value ) {
		$mods = get_option( 'theme_mods_' . get_stylesheet(), array() );
		$key  = ( is_object( $setting ) && isset( $setting->id ) ) ? $setting->id : 'ysf_smtp_pass';

		return isset( $mods[ $key ] ) ? (string) $mods[ $key ] : '';
	}

	return $value;
}

/**
 * Özelleştirici alanlarını kaydeder.
 *
 * @param WP_Customize_Manager $wp_customize Yönetici.
 */
function ysf_customize_register( $wp_customize ) {
	$wp_customize->add_panel(
		'ysf_panel',
		array(
			'title'       => __( 'YSF Food Lab Ayarları', 'ysffoodlab' ),
			'description' => __( 'Restoran bilgileri, çalışma saatleri, sipariş ve rezervasyon ayarları.', 'ysffoodlab' ),
			'priority'    => 20,
		)
	);

	$sections = array(
		'ysf_business'    => __( 'İşletme Bilgileri', 'ysffoodlab' ),
		'ysf_hours'       => __( 'Çalışma Saatleri', 'ysffoodlab' ),
		'ysf_home'        => __( 'Ana Sayfa', 'ysffoodlab' ),
		'ysf_order'       => __( 'Online Sipariş', 'ysffoodlab' ),
		'ysf_reservation' => __( 'Rezervasyon', 'ysffoodlab' ),
		'ysf_floor'       => __( 'Masa servisi', 'ysffoodlab' ),
		'ysf_social'      => __( 'Sosyal Medya', 'ysffoodlab' ),
		'ysf_advanced'    => __( 'Gelişmiş', 'ysffoodlab' ),
	);

	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section(
			$id,
			array(
				'title' => $title,
				'panel' => 'ysf_panel',
			)
		);
	}

	// --- İşletme bilgileri ------------------------------------------------
	$business = array(
		'ysf_tagline'      => array(
			'label'   => __( 'Logo altı kısa yazı', 'ysffoodlab' ),
			'default' => 'Kitchen & Coffee',
		),
		'ysf_tagline_en'   => array( 'label' => __( 'Logo altı kısa yazı (EN)', 'ysffoodlab' ) ),
		'ysf_phone'        => array(
			'label'       => __( 'Telefon', 'ysffoodlab' ),
			'description' => __( 'Örn: +90 555 000 00 00', 'ysffoodlab' ),
		),
		'ysf_whatsapp'     => array(
			'label'       => __( 'WhatsApp numarası', 'ysffoodlab' ),
			'description' => __( 'Ülke kodu ile, boşluksuz: 905550000000', 'ysffoodlab' ),
		),
		'ysf_email'        => array(
			'label'       => __( 'İletişim e-postası', 'ysffoodlab' ),
			'type'        => 'email',
			'default'     => 'info@ysffoodlab.com.tr',
			'description' => __( 'Sitede görünen adres. Doğrulama ve şifre maillerinin gitmesi için hemen aşağıdaki SMTP şifresini de yazın.', 'ysffoodlab' ),
		),
		'ysf_smtp_user'    => array(
			'label'       => __( 'Mail gönderen (SMTP kullanıcı)', 'ysffoodlab' ),
			'default'     => 'info@ysffoodlab.com.tr',
			'description' => __( 'cPanel’deki posta kutusu: info@ysffoodlab.com.tr (ysffoodlab.com değil).', 'ysffoodlab' ),
		),
		'ysf_smtp_pass'    => array(
			'label'       => __( 'Mail şifresi (SMTP)', 'ysffoodlab' ),
			'type'        => 'password',
			'description' => __( 'cPanel > E-posta hesapları’nda info@ kutusu için koyduğunuz şifre. WordPress yönetici şifresi değil. Webmail’e bu şifreyle girebiliyorsanız doğrudur.', 'ysffoodlab' ),
		),
		'ysf_smtp_host'    => array(
			'label'       => __( 'SMTP sunucu', 'ysffoodlab' ),
			'default'     => 'mail.ysffoodlab.com.tr',
			'description' => __( 'Genelde mail.ysffoodlab.com.tr — olmazsa mirel.veridyen.com', 'ysffoodlab' ),
		),
		'ysf_smtp_port'    => array(
			'label'       => __( 'SMTP port', 'ysffoodlab' ),
			'type'        => 'number',
			'default'     => 465,
			'description' => __( 'Veridyen için 465 (SSL). 587 (TLS) olmazsa bunu kullanın.', 'ysffoodlab' ),
		),
		'ysf_smtp_enc'     => array(
			'label'   => __( 'SMTP şifreleme', 'ysffoodlab' ),
			'type'    => 'select',
			'default' => 'ssl',
			'choices' => array(
				'ssl'  => 'SSL (port 465) — önerilen',
				'tls'  => 'TLS (port 587)',
				'none' => __( 'Yok', 'ysffoodlab' ),
			),
		),
		'ysf_address'      => array(
			'label' => __( 'Adres', 'ysffoodlab' ),
			'type'  => 'textarea',
		),
		'ysf_maps_link'    => array(
			'label'       => __( 'Google Maps yol tarifi bağlantısı', 'ysffoodlab' ),
			'type'        => 'url',
			'description' => __( 'Google Maps’te işletmenizi açıp “Paylaş > Bağlantıyı kopyala” ile alın.', 'ysffoodlab' ),
		),
		'ysf_maps_embed'   => array(
			'label'       => __( 'Google Maps gömme adresi (iframe src)', 'ysffoodlab' ),
			'type'        => 'url',
			'description' => __( 'Maps > Paylaş > Harita yerleştir > iframe içindeki src bağlantısı.', 'ysffoodlab' ),
		),
		'ysf_price_range'  => array(
			'label'       => __( 'Fiyat aralığı', 'ysffoodlab' ),
			'default'     => '₺₺',
			'description' => __( 'Google için: ₺, ₺₺, ₺₺₺', 'ysffoodlab' ),
		),
		'ysf_cuisine'      => array(
			'label'   => __( 'Mutfak türü', 'ysffoodlab' ),
			'default' => 'Modern Türk mutfağı',
		),
		'ysf_city'         => array(
			'label' => __( 'Şehir', 'ysffoodlab' ),
		),
		'ysf_postal'       => array(
			'label' => __( 'Posta kodu', 'ysffoodlab' ),
		),
	);

	foreach ( $business as $id => $args ) {
		ysf_add_setting( $wp_customize, $id, $args + array( 'section' => 'ysf_business' ) );
	}

	// --- Çalışma saatleri -------------------------------------------------
	foreach ( ysf_week_days() as $key => $names ) {
		ysf_add_setting(
			$wp_customize,
			'ysf_hours_' . $key,
			array(
				'label'       => $names[0],
				'section'     => 'ysf_hours',
				'default'     => '11:00-23:00',
				'description' => 'sun' === $key ? __( 'Kapalı günler için “Kapalı” yazın. Birden fazla aralık: 11:00-15:00, 18:00-23:00', 'ysffoodlab' ) : '',
			)
		);
	}

	// --- Ana sayfa --------------------------------------------------------
	$home = array(
		'ysf_hero_image'     => array(
			'label'       => __( 'Kapak görseli 1', 'ysffoodlab' ),
			'type'        => 'image',
			'description' => __( 'Boş bırakırsanız temayla gelen görseller slayt olarak gösterilir.', 'ysffoodlab' ),
		),
		'ysf_hero_image_2'   => array(
			'label' => __( 'Kapak görseli 2', 'ysffoodlab' ),
			'type'  => 'image',
		),
		'ysf_hero_image_3'   => array(
			'label' => __( 'Kapak görseli 3', 'ysffoodlab' ),
			'type'  => 'image',
		),
		'ysf_hero_image_4'   => array(
			'label' => __( 'Kapak görseli 4', 'ysffoodlab' ),
			'type'  => 'image',
		),
		'ysf_hero_title'     => array(
			'label'   => __( 'Kapak başlığı', 'ysffoodlab' ),
			'default' => 'Taze malzeme, iyi kahve, sıcak bir masa',
		),
		'ysf_hero_title_en'  => array(
			'label'   => __( 'Kapak başlığı (EN)', 'ysffoodlab' ),
			'default' => 'Fresh ingredients, great coffee, a warm table',
		),
		'ysf_hero_text'      => array(
			'label'   => __( 'Kapak açıklaması', 'ysffoodlab' ),
			'type'    => 'textarea',
			'default' => 'Her tabağı günlük hazırlıyor, kahvemizi taze çekiyoruz. Gel, otur, acele etme.',
		),
		'ysf_hero_text_en'   => array(
			'label'   => __( 'Kapak açıklaması (EN)', 'ysffoodlab' ),
			'type'    => 'textarea',
			'default' => 'Every plate is prepared daily and our coffee is ground fresh. Come in, sit down, take your time.',
		),
		'ysf_show_bar'       => array(
			'label'   => __( 'Üst duyuru şeridini göster', 'ysffoodlab' ),
			'type'    => 'checkbox',
			'default' => true,
		),
		'ysf_about_title'    => array(
			'label'   => __( 'Hakkımızda bölümü başlığı', 'ysffoodlab' ),
			'default' => 'Mutfağımızın hikâyesi',
		),
		'ysf_about_title_en' => array(
			'label'   => __( 'Hakkımızda bölümü başlığı (EN)', 'ysffoodlab' ),
			'default' => 'The story behind our kitchen',
		),
		'ysf_about_text'     => array(
			'label'   => __( 'Hakkımızda metni', 'ysffoodlab' ),
			'type'    => 'textarea',
			'default' => "Mutfağımızda gün, pazardan gelen malzemenin ayıklanmasıyla başlar. Sosları, hamurları ve tatlıları kendimiz hazırlar; menüyü mevsime göre tazeleriz.\n\nKahve tarafında tek kaynak çekirdeklerle çalışıyor, her demlemeyi gramına kadar ölçüyoruz. Amacımız basit: iyi bir tabak, dürüst bir fiyat ve kendinizi evinizde hissettiğiniz bir masa.",
		),
		'ysf_about_text_en'  => array(
			'label'   => __( 'Hakkımızda metni (EN)', 'ysffoodlab' ),
			'type'    => 'textarea',
			'default' => "Our day begins with sorting the produce that arrives from the market. We make our own sauces, doughs and desserts, and refresh the menu with every season.\n\nOn the coffee side we work with single origin beans and weigh every brew to the gram. Our goal is simple: a good plate, an honest price and a table where you feel at home.",
		),
		'ysf_about_image'    => array(
			'label'       => __( 'Hakkımızda görseli', 'ysffoodlab' ),
			'type'        => 'image',
			'description' => __( 'Boş bırakırsanız temayla gelen mutfak görseli kullanılır.', 'ysffoodlab' ),
		),
	);

	foreach ( $home as $id => $args ) {
		ysf_add_setting( $wp_customize, $id, $args + array( 'section' => 'ysf_home' ) );
	}

	for ( $i = 1; $i <= 4; $i++ ) {
		ysf_add_setting(
			$wp_customize,
			'ysf_fact_' . $i . '_num',
			array(
				'label'   => sprintf( /* translators: %d: sıra. */ __( '%d. rakam', 'ysffoodlab' ), $i ),
				'section' => 'ysf_home',
			)
		);
		ysf_add_setting(
			$wp_customize,
			'ysf_fact_' . $i . '_label',
			array(
				'label'   => sprintf( /* translators: %d: sıra. */ __( '%d. rakam açıklaması', 'ysffoodlab' ), $i ),
				'section' => 'ysf_home',
			)
		);
		ysf_add_setting(
			$wp_customize,
			'ysf_fact_' . $i . '_label_en',
			array(
				'label'   => sprintf( /* translators: %d: sıra. */ __( '%d. rakam açıklaması (EN)', 'ysffoodlab' ), $i ),
				'section' => 'ysf_home',
			)
		);
	}

	// --- Sipariş ----------------------------------------------------------
	$order = array(
		'ysf_orders_enabled'    => array(
			'label'       => __( 'Online siparişi aç', 'ysffoodlab' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => __( 'Kapatırsanız sepet ve sipariş butonları gizlenir.', 'ysffoodlab' ),
		),
		'ysf_currency'          => array(
			'label'   => __( 'Para birimi simgesi', 'ysffoodlab' ),
			'default' => '₺',
		),
		'ysf_min_order'         => array(
			'label' => __( 'Minimum sipariş tutarı', 'ysffoodlab' ),
			'type'  => 'number',
		),
		'ysf_delivery_fee'      => array(
			'label' => __( 'Teslimat ücreti', 'ysffoodlab' ),
			'type'  => 'number',
		),
		'ysf_free_delivery_over' => array(
			'label'       => __( 'Şu tutar üzerinde teslimat ücretsiz', 'ysffoodlab' ),
			'type'        => 'number',
			'description' => __( '0 yazarsanız bu kural uygulanmaz.', 'ysffoodlab' ),
		),
		'ysf_order_email'       => array(
			'label'       => __( 'Sipariş bildirim e-postası', 'ysffoodlab' ),
			'type'        => 'email',
			'description' => __( 'Boşsa site yöneticisi e-postası kullanılır.', 'ysffoodlab' ),
		),
		'ysf_order_wa'          => array(
			'label'       => __( 'Siparişi WhatsApp’a da yönlendir', 'ysffoodlab' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => __( 'Sipariş kaydedildikten sonra müşterinin WhatsApp’ı sipariş özeti ile açılır.', 'ysffoodlab' ),
		),
		'ysf_order_note'        => array(
			'label' => __( 'Sipariş sayfası bilgi notu', 'ysffoodlab' ),
			'type'  => 'textarea',
		),
		'ysf_order_note_en'     => array(
			'label' => __( 'Sipariş sayfası bilgi notu (EN)', 'ysffoodlab' ),
			'type'  => 'textarea',
		),
		'ysf_delivery_areas'    => array(
			'label'       => __( 'Teslimat bölgeleri', 'ysffoodlab' ),
			'type'        => 'textarea',
			'description' => __( 'Virgülle ayırın. Sipariş formunda seçenek olarak çıkar.', 'ysffoodlab' ),
		),
		'ysf_yemeksepeti'       => array(
			'label' => __( 'Yemeksepeti bağlantısı', 'ysffoodlab' ),
			'type'  => 'url',
		),
		'ysf_getir'             => array(
			'label' => __( 'Getir Yemek bağlantısı', 'ysffoodlab' ),
			'type'  => 'url',
		),
		'ysf_trendyol'          => array(
			'label' => __( 'Trendyol Yemek bağlantısı', 'ysffoodlab' ),
			'type'  => 'url',
		),
	);

	foreach ( $order as $id => $args ) {
		ysf_add_setting( $wp_customize, $id, $args + array( 'section' => 'ysf_order' ) );
	}

	// --- Rezervasyon ------------------------------------------------------
	$reservation = array(
		'ysf_res_enabled'     => array(
			'label'   => __( 'Rezervasyonu aç', 'ysffoodlab' ),
			'type'    => 'checkbox',
			'default' => true,
		),
		'ysf_res_email'       => array(
			'label' => __( 'Rezervasyon bildirim e-postası', 'ysffoodlab' ),
			'type'  => 'email',
		),
		'ysf_res_max_guests'  => array(
			'label'   => __( 'Maksimum kişi sayısı', 'ysffoodlab' ),
			'type'    => 'number',
			'default' => 20,
		),
		'ysf_res_lead_hours'  => array(
			'label'       => __( 'En az kaç saat önce rezervasyon alınır', 'ysffoodlab' ),
			'type'        => 'number',
			'default'     => 2,
			'description' => __( 'Bu süreden erken saatler formda seçilemez.', 'ysffoodlab' ),
		),
		'ysf_res_slot_start'  => array(
			'label'   => __( 'Rezervasyon başlangıç saati', 'ysffoodlab' ),
			'default' => '12:00',
		),
		'ysf_res_slot_end'    => array(
			'label'   => __( 'Rezervasyon bitiş saati', 'ysffoodlab' ),
			'default' => '22:30',
		),
		'ysf_res_note'        => array(
			'label' => __( 'Rezervasyon sayfası bilgi notu', 'ysffoodlab' ),
			'type'  => 'textarea',
		),
		'ysf_res_note_en'     => array(
			'label' => __( 'Rezervasyon sayfası bilgi notu (EN)', 'ysffoodlab' ),
			'type'  => 'textarea',
		),
	);

	foreach ( $reservation as $id => $args ) {
		ysf_add_setting( $wp_customize, $id, $args + array( 'section' => 'ysf_reservation' ) );
	}

	$floor = array(
		'ysf_floor_enabled' => array(
			'label'       => __( 'Masa servisini aç', 'ysffoodlab' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => __( 'Garson uygulaması, kasiyer kasası ve mutfak ekranı. Sayfalar: /garson, /kasiyer ve /mutfak-ekrani.', 'ysffoodlab' ),
		),
		'ysf_table_count'   => array(
			'label'       => __( 'Masa sayısı', 'ysffoodlab' ),
			'type'        => 'number',
			'default'     => 16,
			'sanitize'    => 'ysf_sanitize_table_count',
			'description' => __( 'Garson uygulamasında görünen masa adedi (1–80).', 'ysffoodlab' ),
		),
		'ysf_kds_online'    => array(
			'label'       => __( 'Mutfak ekranında online siparişleri de göster', 'ysffoodlab' ),
			'type'        => 'checkbox',
			'default'     => true,
			'description' => __( 'Açıkken siteden (WhatsApp’a da giden) adrese teslim ve gel-al siparişleri mutfak kuyruğuna düşer. Kapatırsanız mutfak yalnızca masa biletlerini görür.', 'ysffoodlab' ),
		),
	);

	foreach ( $floor as $id => $args ) {
		ysf_add_setting( $wp_customize, $id, $args + array( 'section' => 'ysf_floor' ) );
	}

	// --- Üyelik -----------------------------------------------------------
	ysf_add_setting(
		$wp_customize,
		'ysf_acc_enabled',
		array(
			'label'       => __( 'Yeni üye kaydını aç', 'ysffoodlab' ),
			'section'     => 'ysf_advanced',
			'type'        => 'checkbox',
			'default'     => true,
			'description' => __( 'Kapatırsanız Hesabım sayfasında yalnızca giriş formu görünür, yeni kayıt alınmaz.', 'ysffoodlab' ),
		)
	);

	ysf_add_setting(
		$wp_customize,
		'ysf_sms_usercode',
		array(
			'label'       => __( 'Netgsm kullanıcı kodu (kayıt OTP SMS)', 'ysffoodlab' ),
			'section'     => 'ysf_advanced',
			'description' => __( 'Kayıt OTP’sini telefon SMS’i olarak göndermek için. Üç alan da doluysa SMS + e-posta gider; boşsa yalnızca e-posta.', 'ysffoodlab' ),
		)
	);

	ysf_add_setting(
		$wp_customize,
		'ysf_sms_pass',
		array(
			'label'       => __( 'Netgsm şifresi', 'ysffoodlab' ),
			'section'     => 'ysf_advanced',
			'type'        => 'password',
			'description' => __( 'Netgsm panel şifresi. Boş kaydederseniz mevcut şifre korunur.', 'ysffoodlab' ),
		)
	);

	ysf_add_setting(
		$wp_customize,
		'ysf_sms_header',
		array(
			'label'       => __( 'SMS başlığı (msgheader)', 'ysffoodlab' ),
			'section'     => 'ysf_advanced',
			'description' => __( 'Netgsm’de onaylı gönderici adı. Örn. YSFFOODLAB. Onaysız başlık SMS’i düşürür.', 'ysffoodlab' ),
		)
	);

	// --- Sosyal medya -----------------------------------------------------
	$social = array(
		'ysf_social_instagram'   => array(
			'label'   => __( 'Instagram', 'ysffoodlab' ),
			'default' => 'https://www.instagram.com/ysffoodlab/',
		),
		'ysf_social_facebook'    => array(
			'label'   => __( 'Facebook', 'ysffoodlab' ),
			'default' => 'https://www.facebook.com/ysffoodlab',
		),
		'ysf_social_x'           => array( 'label' => __( 'X (Twitter)', 'ysffoodlab' ) ),
		'ysf_social_youtube'     => array( 'label' => __( 'YouTube', 'ysffoodlab' ) ),
		'ysf_social_tripadvisor' => array( 'label' => __( 'Tripadvisor', 'ysffoodlab' ) ),
	);

	foreach ( $social as $id => $args ) {
		ysf_add_setting(
			$wp_customize,
			$id,
			$args + array(
				'section' => 'ysf_social',
				'type'    => 'url',
			)
		);
	}

	// --- Gelişmiş ---------------------------------------------------------
	ysf_add_setting(
		$wp_customize,
		'ysf_google_fonts',
		array(
			'label'       => __( 'Google Fonts kullan', 'ysffoodlab' ),
			'section'     => 'ysf_advanced',
			'type'        => 'checkbox',
			'default'     => true,
			'description' => __( 'Kapatırsanız sistem yazı tipleri kullanılır, site birkaç ms daha hızlı açılır.', 'ysffoodlab' ),
		)
	);

	ysf_add_setting(
		$wp_customize,
		'ysf_show_langswitch',
		array(
			'label'   => __( 'TR / EN dil seçicisini göster', 'ysffoodlab' ),
			'section' => 'ysf_advanced',
			'type'    => 'checkbox',
			'default' => true,
		)
	);

	ysf_add_setting(
		$wp_customize,
		'ysf_kvkk_url',
		array(
			'label'   => __( 'KVKK / Gizlilik sayfası bağlantısı', 'ysffoodlab' ),
			'section' => 'ysf_advanced',
			'type'    => 'url',
		)
	);
}
add_action( 'customize_register', 'ysf_customize_register' );

/**
 * Aktif dile göre ayar metni döndürür (ysf_hero_title / ysf_hero_title_en).
 *
 * @param string $key     Ayar anahtarı (EN eki olmadan).
 * @param string $default Varsayılan.
 * @return string
 */
function ysf_option_i18n( $key, $default = '' ) {
	$value = ysf_get_option( $key, $default );

	if ( 'en' === ysf_lang() ) {
		$english = ysf_get_option( $key . '_en', '' );
		if ( $english ) {
			return $english;
		}
	}

	return $value;
}
