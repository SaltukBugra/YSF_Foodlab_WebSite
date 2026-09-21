<?php
/**
 * Türkçe / İngilizce çift dil altyapısı.
 *
 * Eklenti gerektirmez. Dil, ?lang=en sorgu parametresi ile değişir ve
 * ysf_lang çerezinde saklanır. LiteSpeed Cache kuruluysa çerez otomatik
 * olarak önbellek varyasyonuna eklenir, böylece yanlış dil sunulmaz.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Desteklenen diller.
 *
 * @return array
 */
function ysf_languages() {
	return array(
		'tr' => array(
			'label'  => 'Türkçe',
			'short'  => 'TR',
			'locale' => 'tr_TR',
		),
		'en' => array(
			'label'  => 'English',
			'short'  => 'EN',
			'locale' => 'en_US',
		),
	);
}

/**
 * Aktif dili döndürür.
 *
 * @return string 'tr' veya 'en'
 */
function ysf_lang() {
	static $lang = null;

	if ( null !== $lang ) {
		return $lang;
	}

	$lang = 'tr';

	if ( isset( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = sanitize_key( wp_unslash( $_GET['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( array_key_exists( $requested, ysf_languages() ) ) {
			$lang = $requested;
		}
	} elseif ( isset( $_COOKIE['ysf_lang'] ) ) {
		$cookie = sanitize_key( wp_unslash( $_COOKIE['ysf_lang'] ) );
		if ( array_key_exists( $cookie, ysf_languages() ) ) {
			$lang = $cookie;
		}
	}

	return $lang;
}

/**
 * Dil seçimini çereze yazar ve LiteSpeed varyasyonunu bildirir.
 */
function ysf_persist_lang() {
	if ( is_admin() ) {
		return;
	}

	$lang = ysf_lang();

	if ( isset( $_GET['lang'] ) && ! headers_sent() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		setcookie( 'ysf_lang', $lang, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
	}

	if ( 'tr' !== $lang ) {
		do_action( 'litespeed_vary_add', 'ysf_lang' );
	}
}
add_action( 'template_redirect', 'ysf_persist_lang' );

/**
 * Sayfa dilini html etiketine yansıtır.
 *
 * @param string $output Dil özniteliği.
 * @return string
 */
function ysf_language_attributes( $output ) {
	if ( 'en' === ysf_lang() ) {
		$output = preg_replace( '/lang="[^"]*"/', 'lang="en"', $output );
	}

	return $output;
}
add_filter( 'language_attributes', 'ysf_language_attributes' );

/**
 * Navigasyon menüsünü aktif dile uyarlar.
 *
 * Menü öğelerinin başlığı, bağlı olduğu sayfanın/yazının İngilizce başlığıyla
 * (_ysf_title_en) değiştirilir; kategori öğelerinde terim çevirisi kullanılır.
 * Site içi bağlantılara dil parametresi eklenir, böylece menüden gezinirken
 * İngilizce seçimi korunur.
 *
 * @param array $items Menü öğeleri.
 * @return array
 */
function ysf_nav_menu_i18n( $items ) {
	if ( 'en' !== ysf_lang() || ! is_array( $items ) ) {
		return $items;
	}

	$host = wp_parse_url( home_url(), PHP_URL_HOST );

	foreach ( $items as $item ) {
		$object_id  = isset( $item->object_id ) ? (int) $item->object_id : 0;
		$translated = '';
		$original   = '';

		if ( $object_id && 'taxonomy' === $item->type ) {
			$term = get_term( $object_id );

			if ( $term && ! is_wp_error( $term ) ) {
				$translated = get_term_meta( $object_id, '_ysf_name_en', true );
				$original   = $term->name;
			}
		} elseif ( $object_id ) {
			$translated = get_post_meta( $object_id, '_ysf_title_en', true );
			$original   = get_the_title( $object_id );
		}

		// Etiket, bağlı sayfanın başlığıyla aynıysa çeviriyi kullan. Yönetici
		// menüde kendi etiketini yazmışsa (ör. "Sipariş Ver") ona dokunmayız.
		if ( $translated && $original && $item->title === $original ) {
			$item->title = $translated;
		}

		// Dil parametresi yalnızca site içi bağlantılara eklenir.
		if ( ! empty( $item->url ) && wp_parse_url( $item->url, PHP_URL_HOST ) === $host ) {
			$item->url = ysf_localize_url( $item->url );
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'ysf_nav_menu_i18n' );

/**
 * Verilen dil için mevcut sayfanın bağlantısını üretir.
 *
 * @param string $lang Dil kodu.
 * @return string
 */
function ysf_lang_url( $lang ) {
	global $wp;

	$path = ( isset( $wp->request ) && $wp->request ) ? user_trailingslashit( $wp->request ) : '/';
	$args = array();

	foreach ( (array) $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'lang' === $key || is_array( $value ) ) {
			continue;
		}

		$args[ sanitize_key( $key ) ] = sanitize_text_field( wp_unslash( $value ) );
	}

	$args['lang'] = $lang;

	return add_query_arg( $args, home_url( $path ) );
}

/**
 * Tema arayüz metinleri sözlüğü.
 *
 * @return array
 */
function ysf_dictionary() {
	$dict = array(
		'nav_home'            => array( 'Ana Sayfa', 'Home' ),
		'nav_menu'            => array( 'Menü', 'Menu' ),
		'nav_order'           => array( 'Online Sipariş', 'Order Online' ),
		'nav_reservation'     => array( 'Rezervasyon', 'Reservations' ),
		'nav_about'           => array( 'Hakkımızda', 'About Us' ),
		'nav_contact'         => array( 'İletişim', 'Contact' ),
		'nav_blog'            => array( 'Blog', 'Blog' ),
		'menu_toggle'         => array( 'Menüyü aç/kapat', 'Toggle menu' ),
		'skip_to_content'     => array( 'İçeriğe geç', 'Skip to content' ),

		'cta_order'           => array( 'Online Sipariş Ver', 'Order Online' ),
		'cta_reserve'         => array( 'Rezervasyon Yap', 'Book a Table' ),
		'cta_menu'            => array( 'Menüyü İncele', 'Explore the Menu' ),
		'cta_call'            => array( 'Hemen Ara', 'Call Now' ),
		'cta_whatsapp'        => array( 'WhatsApp’tan Yaz', 'Chat on WhatsApp' ),
		'social_instagram'    => array( 'Instagram', 'Instagram' ),
		'social_facebook'     => array( 'Facebook', 'Facebook' ),
		'cta_directions'      => array( 'Yol Tarifi Al', 'Get Directions' ),
		'cta_all'             => array( 'Tümünü Gör', 'View All' ),
		'read_more'           => array( 'Devamını oku', 'Read more' ),

		'open_now'            => array( 'Şu an açık', 'Open now' ),
		'closed_now'          => array( 'Şu an kapalı', 'Currently closed' ),
		'hours_title'         => array( 'Çalışma Saatleri', 'Opening Hours' ),
		'closed'              => array( 'Kapalı', 'Closed' ),

		'announcements'       => array( 'Duyurular', 'Announcements' ),
		'campaigns_eyebrow'   => array( 'Kampanyalar', 'Special Offers' ),
		'campaigns_title'     => array( 'Bu haftanın fırsatları', 'This week’s offers' ),
		'campaigns_empty'     => array( 'Şu anda aktif bir kampanya yok.', 'There are no active offers right now.' ),
		'valid_until'         => array( 'Son gün', 'Valid until' ),

		'menu_eyebrow'        => array( 'Mutfağımızdan', 'From our kitchen' ),
		'menu_title'          => array( 'Öne çıkan lezzetler', 'Signature dishes' ),
		'menu_all'            => array( 'Tümü', 'All' ),
		'menu_empty'          => array( 'Bu kategoride henüz ürün eklenmedi.', 'No items in this category yet.' ),
		'menu_search'         => array( 'Menüde ara…', 'Search the menu…' ),
		'menu_no_result'      => array( 'Aramanızla eşleşen ürün bulunamadı.', 'No dishes matched your search.' ),

		'add_to_cart'         => array( 'Sepete Ekle', 'Add to Cart' ),
		'added'               => array( 'Sepete eklendi', 'Added to cart' ),
		'cart_title'          => array( 'Sepetim', 'My Cart' ),
		'cart_empty'          => array( 'Sepetiniz henüz boş. Aşağıdaki menüden ürün ekleyerek başlayın.', 'Your cart is empty. Add something from the menu below to get started.' ),
		'cart_clear'          => array( 'Sepeti boşalt', 'Clear cart' ),
		'cart_close'          => array( 'Sepeti kapat', 'Close cart' ),
		'subtotal'            => array( 'Ara toplam', 'Subtotal' ),
		'delivery_fee'        => array( 'Teslimat ücreti', 'Delivery fee' ),
		'total'               => array( 'Toplam', 'Total' ),
		'free'                => array( 'Ücretsiz', 'Free' ),
		'checkout'            => array( 'Siparişi Tamamla', 'Complete Order' ),
		'min_order_warning'   => array( 'Minimum sipariş tutarı: %s', 'Minimum order amount: %s' ),
		'free_delivery_hint'  => array( '%s üzeri siparişlerde teslimat ücretsiz.', 'Free delivery on orders over %s.' ),

		'order_title'         => array( 'Online Sipariş', 'Order Online' ),
		'order_pick_menu'     => array( 'Menüden seçin', 'Choose from the menu' ),
		'order_step_items'    => array( 'Sepetiniz', 'Your order' ),
		'order_step_info'     => array( 'Teslimat bilgileri', 'Delivery details' ),
		'order_to_delivery'   => array( 'Teslimat aşamasına geç', 'Continue to delivery' ),
		'order_type'          => array( 'Sipariş tipi', 'Order type' ),
		'order_delivery'      => array( 'Adrese teslim', 'Delivery' ),
		'order_pickup'        => array( 'Gel al', 'Pickup' ),
		'order_table'         => array( 'Masaya servis', 'Dine-in' ),
		'order_success'       => array( 'Siparişiniz kaydedildi. WhatsApp mesajını gönderin ve iletildiğini kontrol edin.', 'Your order is saved. Send the WhatsApp message and check that it was delivered.' ),
		'order_wa_hint'       => array( 'WhatsApp penceresi açılmadıysa aşağıdaki butona dokunun.', 'If WhatsApp did not open, tap the button below.' ),
		'order_wa_check'      => array( 'Mesaj iletilmezse veya iptal edilirse siparişi hazırlamayız. WhatsApp’tan gönderildiğini kontrol edin; gitmediyse aşağıdaki butondan tekrar gönderin.', 'If the message is not delivered or is cancelled, we will not prepare the order. Check WhatsApp; if it did not send, tap the button below and try again.' ),
		'order_send_wa'       => array( 'Siparişi WhatsApp’tan gönder', 'Send order via WhatsApp' ),
		'order_empty_error'   => array( 'Lütfen önce menüden ürün seçin.', 'Please choose at least one item from the menu.' ),

		'res_title'           => array( 'Rezervasyon', 'Reservation' ),
		'res_lead'            => array( 'Doğum günü, yıl dönümü, iş yemeği ya da özel bir kutlama: masanızı biz hazırlayalım.', 'Birthdays, anniversaries, business dinners or any celebration: let us set the table for you.' ),
		'res_success'         => array( 'Rezervasyon talebiniz alındı. En kısa sürede sizi arayarak teyit edeceğiz.', 'Your reservation request has been received. We will call you to confirm shortly.' ),
		'res_occasion'        => array( 'Özel gün', 'Occasion' ),
		'res_occasion_none'   => array( 'Belirtmek istemiyorum', 'Prefer not to say' ),
		'res_occasion_bd'     => array( 'Doğum günü', 'Birthday' ),
		'res_occasion_anniv'  => array( 'Yıl dönümü', 'Anniversary' ),
		'res_occasion_prop'   => array( 'Evlilik teklifi / nişan', 'Proposal / engagement' ),
		'res_occasion_biz'    => array( 'İş yemeği', 'Business dinner' ),
		'res_occasion_group'  => array( 'Grup / kutlama', 'Group celebration' ),
		'res_extras'          => array( 'Eklemek istedikleriniz', 'Optional extras' ),
		'res_extra_cake'      => array( 'Pasta / mumlu sunum', 'Cake with candles' ),
		'res_extra_flower'    => array( 'Çiçek süslemesi', 'Flower arrangement' ),
		'res_extra_quiet'     => array( 'Sakin bir köşe', 'Quiet corner table' ),
		'res_extra_music'     => array( 'Özel şarkı isteği', 'Special song request' ),

		'form_name'           => array( 'Ad Soyad', 'Full name' ),
		'form_phone'          => array( 'Telefon', 'Phone' ),
		'form_email'          => array( 'E-posta', 'E-mail' ),
		'form_date'           => array( 'Tarih', 'Date' ),
		'form_time'           => array( 'Saat', 'Time' ),
		'form_guests'         => array( 'Kişi sayısı', 'Number of guests' ),
		'form_address'        => array( 'Teslimat adresi', 'Delivery address' ),
		'form_table_no'       => array( 'Masa numarası', 'Table number' ),
		'form_note'           => array( 'Not', 'Note' ),
		'form_note_ph'        => array( 'Alerji, tercih veya özel isteğiniz varsa yazın.', 'Allergies, preferences or special requests.' ),
		'form_message'        => array( 'Mesajınız', 'Your message' ),
		'form_subject'        => array( 'Konu', 'Subject' ),
		'form_submit'         => array( 'Gönder', 'Send' ),
		'form_sending'        => array( 'Gönderiliyor…', 'Sending…' ),
		'form_required'       => array( 'Lütfen zorunlu alanları doldurun.', 'Please fill in the required fields.' ),
		'form_phone_invalid'  => array( 'Telefon numarası geçersiz görünüyor.', 'That phone number does not look valid.' ),
		'form_error'          => array( 'Bir sorun oluştu. Lütfen tekrar deneyin veya bizi arayın.', 'Something went wrong. Please try again or give us a call.' ),
		'form_consent'        => array( 'Kişisel verilerimin bu talep kapsamında işlenmesine onay veriyorum.', 'I consent to my personal data being processed for this request.' ),

		'contact_title'       => array( 'İletişim', 'Contact' ),
		'contact_address'     => array( 'Adres', 'Address' ),
		'contact_phone'       => array( 'Telefon', 'Phone' ),
		'contact_mail'        => array( 'E-posta', 'E-mail' ),
		'contact_social'      => array( 'Sosyal medya', 'Social media' ),
		'contact_form_title'  => array( 'Bize yazın', 'Write to us' ),
		'contact_success'     => array( 'Mesajınız iletildi. Teşekkür ederiz!', 'Your message has been sent. Thank you!' ),

		'blog_title'          => array( 'Blog', 'Blog' ),
		'blog_empty'          => array( 'Henüz yazı yayınlanmadı.', 'No posts published yet.' ),
		'search_results'      => array( 'Arama sonuçları', 'Search results' ),
		'search_empty'        => array( 'Aramanızla eşleşen içerik bulunamadı.', 'Nothing matched your search.' ),
		'notfound_title'      => array( 'Sayfa bulunamadı', 'Page not found' ),
		'notfound_text'       => array( 'Aradığınız sayfa taşınmış veya kaldırılmış olabilir.', 'The page you are looking for may have moved or been removed.' ),
		'back_home'           => array( 'Ana sayfaya dön', 'Back to home' ),
		'footer_rights'       => array( 'Tüm hakları saklıdır.', 'All rights reserved.' ),
		'footer_quick'        => array( 'Hızlı erişim', 'Quick links' ),
		'footer_contact'      => array( 'İletişim', 'Get in touch' ),
		'lang_switch_label'   => array( 'Dil seçimi', 'Language selection' ),
		'per_person'          => array( 'kişi', 'guests' ),
		'calories'            => array( 'kcal', 'kcal' ),
		'prep_time'           => array( 'dk', 'min' ),
		'allergens'           => array( 'Alerjenler', 'Allergens' ),
		'sold_out'            => array( 'Tükendi', 'Sold out' ),
		'vegan'               => array( 'Vegan', 'Vegan' ),
		'vegetarian'          => array( 'Vejetaryen', 'Vegetarian' ),
		'spicy'               => array( 'Acı', 'Spicy' ),
		'glutenfree'          => array( 'Glutensiz', 'Gluten free' ),
		'chef_pick'           => array( 'Şefin seçimi', 'Chef’s pick' ),
		'new_item'            => array( 'Yeni', 'New' ),

		'acc_nav'             => array( 'Hesabım', 'My Account' ),
		'acc_login_cta'       => array( 'Giriş / Üye Ol', 'Sign in' ),
		'acc_title'           => array( 'Hesabım', 'My Account' ),
		'acc_lead'            => array( 'Bilgilerinizi bir kez girin; sipariş ve rezervasyonlarda otomatik dolsun.', 'Save your details once and we will fill them in for every order and reservation.' ),
		'acc_login_title'     => array( 'Giriş yap', 'Sign in' ),
		'acc_register_title'  => array( 'Üye ol', 'Create an account' ),
		'acc_login_or_phone'  => array( 'Kullanıcı adı, e-posta veya telefon', 'Username, e-mail or phone' ),
		'acc_username'        => array( 'Kullanıcı adı', 'Username' ),
		'acc_username_hint'   => array( 'Giriş adınız. E-posta yazarsanız @ işaretinden önceki kısım kullanılır (ör. satuk.bughra).', 'Your sign-in name. If you type an e-mail, the part before @ is used (e.g. satuk.bughra).' ),
		'acc_username_lock'   => array( 'Kullanıcı adı sonradan değiştirilemez.', 'The username cannot be changed later.' ),
		'acc_username_short'  => array( 'Kullanıcı adı en az 3 karakter olmalı.', 'The username must be at least 3 characters.' ),
		'acc_username_invalid'=> array( 'Kullanıcı adı harfle başlamalı ve yalnızca küçük harf, rakam, nokta veya alt çizgi içerebilir.', 'The username must start with a letter and may only contain lowercase letters, numbers, dots or underscores.' ),
		'acc_username_taken'  => array( 'Bu kullanıcı adı kullanılamaz veya alınmış.', 'That username is not available.' ),
		'acc_verify_title'    => array( 'OTP doğrulama', 'OTP verification' ),
		'acc_verify_lead'     => array( '6 haneli tek kullanımlık kodu (OTP) girin. Kod telefonunuza SMS ve e-postanıza gönderilir.', 'Enter the 6-digit one-time code (OTP). It is sent by SMS and e-mail.' ),
		'acc_verify_code'     => array( 'OTP kodu', 'OTP code' ),
		'acc_verify_btn'      => array( 'Kodu doğrula', 'Verify code' ),
		'acc_verify_resend'   => array( 'Kodu tekrar gönder', 'Resend code' ),
		'acc_verify_back'     => array( 'Bilgileri değiştir', 'Change details' ),
		'acc_verify_sent'     => array( 'OTP kodu %s adresine gönderildi.', 'An OTP was sent to %s.' ),
		'acc_verify_sent_both'=> array( 'OTP kodu %1$s numarasına ve %2$s adresine gönderildi. Kod 10 dakika geçerlidir.', 'An OTP was sent to %1$s and %2$s. It is valid for 10 minutes.' ),
		'acc_verify_sent_sms' => array( 'OTP kodu %s numarasına SMS ile gönderildi. Kod 10 dakika geçerlidir.', 'An OTP was sent by SMS to %s. It is valid for 10 minutes.' ),
		'acc_verify_sent_mail'=> array( 'OTP kodu %s adresine gönderildi. Kod 10 dakika geçerlidir.', 'An OTP was sent to %s. It is valid for 10 minutes.' ),
		'acc_verify_onscreen' => array( 'Kod şu an SMS veya e-posta ile gönderilemedi. Üyeliği tamamlamak için OTP kodunuz: %s', 'The OTP could not be sent by SMS or e-mail. Use this code to finish signing up: %s' ),
		'acc_verify_mail'     => array( 'Üyeliğinizi tamamlamak için OTP kodunuz: %s. Kod 10 dakika geçerlidir. Kimseyle paylaşmayın.', 'Your OTP to finish signing up is %s. It is valid for 10 minutes. Do not share it.' ),
		'acc_verify_wrong'    => array( 'OTP kodu hatalı.', 'That OTP is wrong.' ),
		'acc_verify_expired'  => array( 'Doğrulama süresi doldu. Lütfen kaydı yeniden başlatın.', 'Verification expired. Please start registration again.' ),
		'acc_verify_code_exp' => array( 'OTP kodunun süresi doldu. Tekrar gönderin.', 'That OTP expired. Please resend it.' ),
		'acc_verify_locked'   => array( 'Çok fazla hatalı deneme. Lütfen kaydı yeniden başlatın.', 'Too many wrong attempts. Please start registration again.' ),
		'acc_register_otp_hint'=> array( 'Üyelik, telefonunuza ve e-postanıza gidecek 6 haneli OTP kodu ile tamamlanır.', 'Your account is created after you enter the 6-digit OTP sent to your phone and e-mail.' ),
		'acc_verify_send_fail'=> array( 'E-posta gönderilemedi: SMTP şifresi veya sunucu bilgisi hatalı. cPanel > E-posta hesapları’nda info@ysffoodlab.com.tr şifresini webmail’den deneyin. Özelleştir’de sunucu mail.ysffoodlab.com.tr, port 465, şifreleme SSL olsun.', 'The e-mail could not be sent: SMTP login failed. Check the info@ysffoodlab.com.tr password in cPanel webmail. In Customizer use host mail.ysffoodlab.com.tr, port 465, encryption SSL.' ),
		'acc_session_stale'   => array( 'Oturum süresi doldu. Sayfayı yenileyip tekrar deneyin.', 'Your session expired. Refresh the page and try again.' ),
		'acc_password'        => array( 'Şifre', 'Password' ),
		'acc_password_again'  => array( 'Şifre (tekrar)', 'Repeat password' ),
		'acc_password_new'    => array( 'Yeni şifre', 'New password' ),
		'acc_password_cur'    => array( 'Mevcut şifre', 'Current password' ),
		'acc_password_hint'   => array( 'En az 8 karakter.', 'At least 8 characters.' ),
		'acc_password_keep'   => array( 'Şifrenizi değiştirmek istemiyorsanız bu iki alanı boş bırakın.', 'Leave both fields empty to keep your current password.' ),
		'acc_pass_show'       => array( 'Göster', 'Show' ),
		'acc_pass_hide'       => array( 'Gizle', 'Hide' ),
		'acc_remember'        => array( 'Beni hatırla', 'Remember me' ),
		'acc_forgot'          => array( 'Şifremi unuttum', 'Forgot my password' ),
		'acc_forgot_title'    => array( 'Şifre sıfırlama', 'Reset password' ),
		'acc_forgot_hint'     => array( 'Kullanıcı adı veya e-posta adresinizi yazın, şifre yenileme bağlantısını gönderelim.', 'Enter your username or e-mail and we will send a reset link.' ),
		'acc_forgot_send'     => array( 'Bağlantı gönder', 'Send link' ),
		'acc_forgot_sent'     => array( 'Kayıtlı bir hesap varsa şifre yenileme bağlantısını e-posta ile gönderdik.', 'If an account exists, we have sent a password reset link.' ),
		'acc_forgot_mail'     => array( 'Şifrenizi yenilemek için aşağıdaki bağlantıya tıklayın. Bağlantı 24 saat geçerlidir.', 'Click the link below to set a new password. The link is valid for 24 hours.' ),
		'acc_forgot_cta'      => array( 'Şifremi yenile', 'Reset my password' ),
		'acc_reset_title'     => array( 'Yeni şifre belirleyin', 'Set a new password' ),
		'acc_reset_btn'       => array( 'Şifreyi kaydet', 'Save password' ),
		'acc_reset_invalid'   => array( 'Bu şifre yenileme bağlantısı geçersiz veya süresi dolmuş. Lütfen yeniden isteyin.', 'This reset link is invalid or has expired. Please request a new one.' ),
		'acc_reset_ok'        => array( 'Şifreniz güncellendi, yönlendiriliyorsunuz…', 'Your password has been updated, redirecting…' ),
		'acc_login_btn'       => array( 'Giriş yap', 'Sign in' ),
		'acc_register_btn'    => array( 'Doğrulama kodu gönder', 'Send verification code' ),
		'acc_logout'          => array( 'Çıkış yap', 'Sign out' ),
		'acc_login_ok'        => array( 'Giriş yapıldı, yönlendiriliyorsunuz…', 'Signed in, redirecting…' ),
		'acc_login_error'     => array( 'Kullanıcı adı, e-posta, telefon veya şifre hatalı.', 'Wrong username, e-mail, phone or password.' ),
		'acc_login_required'  => array( 'Bu bölüm için giriş yapmanız gerekiyor.', 'Please sign in to continue.' ),
		'acc_logged_in'       => array( 'Zaten giriş yaptınız.', 'You are already signed in.' ),
		'tfa_title'           => array( 'İki adımlı doğrulama', 'Two-step verification' ),
		'tfa_lead'            => array( 'Yönetici girişi için zorunludur. Şifre tek başına yetmez. Google Authenticator, Microsoft Authenticator veya benzeri bir uygulama kullanın. Garson, mutfak, kasiyer ve üyeler etkilenmez.', 'Required for administrator sign-in. A password alone is not enough. Use Google Authenticator, Microsoft Authenticator or a similar app. Waiter, kitchen, cashier and member accounts are not affected.' ),
		'tfa_notice'          => array( 'Yönetici girişi için iki adımlı doğrulamayı açın.', 'Turn on two-step verification for the administrator login.' ),
		'tfa_must'            => array( 'Yönetici hesabı için iki adımlı doğrulama zorunludur. Şifre tek başına giriş yaptırmaz.', 'Two-step verification is required for the administrator account. A password alone will not sign you in.' ),
		'tfa_setup_prompt'    => array( 'Şifre doğru. Yönetici girişi için Authenticator uygulamasıyla karekodu tarayıp 6 haneli kodu yazın.', 'Password is correct. Scan the QR code with your authenticator app and enter the 6-digit code to sign in as administrator.' ),
		'tfa_setup_lock'      => array( 'Önce iki adımlı doğrulamayı tamamlayın.', 'Complete two-step verification first.' ),
		'acc_reset_ok_login'  => array( 'Şifreniz güncellendi. Yönetici girişi için şifre ve Authenticator kodunu kullanın.', 'Your password has been updated. Sign in as administrator with the password and authenticator code.' ),
		'tfa_start'           => array( 'Kurulumu başlat', 'Start setup' ),
		'tfa_scan'            => array( 'Authenticator uygulamasıyla karekodu tarayın.', 'Scan the QR code with your authenticator app.' ),
		'tfa_manual'          => array( 'Karekod çalışmazsa bu anahtarı elle girin:', 'If the QR code does not work, enter this key manually:' ),
		'tfa_confirm'         => array( 'Uygulamadaki 6 haneli kod', 'The 6-digit code from the app' ),
		'tfa_enable'          => array( 'Doğrula ve aç', 'Verify and enable' ),
		'tfa_disable'         => array( 'İki adımlı doğrulamayı kapat', 'Turn off two-step verification' ),
		'tfa_disable_confirm' => array( 'Kapatmak için uygulamadaki kodu veya bir yedek kodu yazın.', 'Enter the app code or a backup code to turn it off.' ),
		'tfa_active'          => array( 'İki adımlı doğrulama bu yönetici hesabında açık.', 'Two-step verification is on for this administrator account.' ),
		'tfa_on'              => array( 'İki adımlı doğrulama açıldı. Yedek kodları güvenli bir yere yazın; bir daha gösterilmez.', 'Two-step verification is on. Save the backup codes somewhere safe; they will not be shown again.' ),
		'tfa_off'             => array( 'İki adımlı doğrulama kapatıldı.', 'Two-step verification has been turned off.' ),
		'tfa_backups_once'    => array( 'Yedek kodlar (her biri bir kez kullanılır):', 'Backup codes (each can be used once):' ),
		'tfa_code'            => array( 'Authenticator kodu', 'Authenticator code' ),
		'tfa_prompt'          => array( 'Şifre doğru. Yönetici girişi için Authenticator’daki 6 haneli kodu yazın.', 'Password is correct. Enter the 6-digit code from your authenticator app to sign in as administrator.' ),
		'tfa_invalid'         => array( 'Kod hatalı. Authenticator uygulamasındaki güncel kodu deneyin.', 'That code is wrong. Try the current code in your authenticator app.' ),
		'tfa_login_hint'      => array( 'Yönetici hesabı için zorunlu. Personel bu alanı boş bırakabilir.', 'Required for the administrator. Staff can leave this blank.' ),
		'tfa_continue'        => array( 'Kodu doğrula', 'Verify code' ),
		'acc_registered'      => array( 'Üyeliğiniz oluşturuldu, hoş geldiniz!', 'Your account is ready. Welcome!' ),
		'acc_welcome'         => array( 'Hoş geldiniz', 'Welcome' ),
		'acc_welcome_mail'    => array( 'Üyeliğiniz oluşturuldu. Bundan sonra sipariş ve rezervasyonlarınızda bilgileriniz otomatik dolacak.', 'Your account is ready. From now on your details will be filled in automatically for orders and reservations.' ),
		'acc_closed'          => array( 'Yeni üyelik kaydı şu anda kapalı.', 'New registrations are closed at the moment.' ),
		'acc_too_many'        => array( 'Çok fazla deneme yapıldı. 15 dakika sonra tekrar deneyin.', 'Too many attempts. Please try again in 15 minutes.' ),
		'acc_email_invalid'   => array( 'E-posta adresi geçersiz görünüyor.', 'That e-mail address does not look valid.' ),
		'acc_email_taken'     => array( 'Bu e-posta adresiyle kayıtlı bir üyelik var.', 'An account with this e-mail already exists.' ),
		'acc_phone_taken'     => array( 'Bu telefon numarası başka bir üyelikte kayıtlı.', 'This phone number belongs to another account.' ),
		'acc_pass_short'      => array( 'Şifre en az 8 karakter olmalı.', 'The password must be at least 8 characters.' ),
		'acc_pass_mismatch'   => array( 'Şifreler birbiriyle aynı değil.', 'The passwords do not match.' ),
		'acc_need_consent'    => array( 'Kayıt için alttaki onay kutusunu işaretleyin.', 'Please tick the consent box to create an account.' ),
		'acc_pass_wrong'      => array( 'Mevcut şifreniz hatalı.', 'Your current password is wrong.' ),
		'acc_pass_changed'    => array( 'Şifreniz güncellendi.', 'Your password has been updated.' ),
		'acc_saved'           => array( 'Bilgileriniz kaydedildi.', 'Your details have been saved.' ),
		'acc_tab_profile'     => array( 'Profil', 'Profile' ),
		'acc_profile_title'   => array( 'Üyelik bilgileri', 'Account details' ),
		'acc_addr_title'      => array( 'Adres defterim', 'My addresses' ),
		'acc_addr_home'       => array( 'Ev adresi', 'Home address' ),
		'acc_addr_work'       => array( 'İş adresi', 'Work address' ),
		'acc_addr_empty'      => array( 'Bu adres henüz girilmedi.', 'No address saved yet.' ),
		'acc_addr_optional'   => array( 'Adres girmek zorunlu değil; dilediğiniz zaman ekleyebilirsiniz.', 'Addresses are optional; you can add them whenever you like.' ),
		'acc_addr_edit'       => array( 'Düzenle', 'Edit' ),
		'acc_addr_add'        => array( 'Adres ekle', 'Add address' ),
		'acc_addr_delete'     => array( 'Adresi sil', 'Delete address' ),
		'acc_addr_saved'      => array( 'Adres kaydedildi.', 'Address saved.' ),
		'acc_addr_deleted'    => array( 'Adres silindi.', 'Address deleted.' ),
		'acc_addr_del_ask'    => array( 'Bu adresi silmek istediğinize emin misiniz?', 'Delete this address?' ),
		'acc_save'            => array( 'Kaydet', 'Save' ),
		'acc_cancel'          => array( 'Vazgeç', 'Cancel' ),
		'acc_orders_title'    => array( 'Son siparişlerim', 'Recent orders' ),
		'acc_res_records'     => array( 'Son rezervasyonlarım', 'Recent reservations' ),
		'acc_history_empty'   => array( 'Henüz bir kaydınız yok.', 'Nothing here yet.' ),
		'acc_prefilled'       => array( 'Bilgileriniz hesabınızdan dolduruldu.', 'We filled in the details from your account.' ),
		'acc_have_account'    => array( 'Hesabınız var mı?', 'Already have an account?' ),
		'acc_no_account'      => array( 'Hesabınız yok mu?', 'No account yet?' ),

		'kit_eyebrow'         => array( 'Mutfak', 'Kitchen' ),
		'kit_title'           => array( 'Menü kontrolü', 'Menu control' ),
		'kit_lead'            => array( 'Stok durumunu güncelleyin, yeni yiyecek veya içecek ekleyin, ürünü menüden kaldırın. Sipariş ekranı müşteriler içindir.', 'Update stock, add dishes or drinks, and remove items from the menu. The order screen is for guests.' ),
		'kit_add'             => array( 'Yeni ürün ekle', 'Add item' ),
		'kit_edit'            => array( 'Düzenle', 'Edit' ),
		'kit_sold_out'        => array( 'Stokta yok', 'Out of stock' ),
		'kit_in_stock'        => array( 'Stokta', 'In stock' ),
		'kit_remove'          => array( 'Menüden kaldır', 'Remove from menu' ),
		'kit_remove_ask'      => array( 'Bu ürün menüden tamamen kaldırılsın mı? İsterseniz sonra geri alabilirsiniz.', 'Remove this item from the menu? You can restore it later.' ),
		'kit_restore'         => array( 'Geri al', 'Restore' ),
		'kit_removed'         => array( 'Kaldırılan ürünler', 'Removed items' ),
		'kit_empty'           => array( 'Menüde henüz ürün yok.', 'There are no menu items yet.' ),
		'kit_saved'           => array( 'Menü güncellendi.', 'The menu has been updated.' ),
		'kit_marked_out'      => array( 'Ürün stokta yok olarak işaretlendi.', 'The item is marked as sold out.' ),
		'kit_marked_in'       => array( 'Ürün tekrar stokta.', 'The item is back in stock.' ),
		'kit_removed_ok'      => array( 'Ürün menüden kaldırıldı.', 'The item has been removed from the menu.' ),
		'kit_restored'        => array( 'Ürün menüye geri alındı.', 'The item is back on the menu.' ),
		'kit_name'            => array( 'Ürün adı', 'Item name' ),
		'kit_price'           => array( 'Fiyat', 'Price' ),
		'kit_cat'             => array( 'Kategori', 'Category' ),
		'kit_cat_none'        => array( 'Kategori seçin', 'Choose a category' ),
		'kit_desc'            => array( 'Kısa açıklama', 'Short description' ),
		'kit_photo'           => array( 'Fotoğraf', 'Photo' ),
		'kit_photo_hint'      => array( 'İsteğe bağlı. Düzenlerken boş bırakırsanız mevcut fotoğraf kalır.', 'Optional. Leave empty while editing to keep the current photo.' ),
		'kit_orderable'       => array( 'Online siparişe açık', 'Available for online orders' ),
		'kit_search'          => array( 'Ürün ara…', 'Search items…' ),
		'kit_forbidden'       => array( 'Bu işlem için mutfak yetkisi gerekir.', 'Kitchen permission is required for this action.' ),
		'kit_price_invalid'   => array( 'Fiyat geçersiz.', 'The price is not valid.' ),
		'kit_cat_invalid'     => array( 'Kategori geçersiz.', 'That category is not valid.' ),
		'kit_welcome_mail'    => array( 'Mutfak sorumlusu hesabınız açıldı. Hesabım sayfasından menüyü yönetebilirsiniz.', 'Your kitchen account is ready. You can manage the menu from My Account.' ),
		'kit_flags'           => array( 'Hazır özellikler', 'Preset traits' ),
		'kit_tags'            => array( 'İsim yanı etiketler', 'Name tags' ),
		'kit_tags_hint'        => array( 'Bilgi koyu, olumlu yeşil, olumsuz kırmızı, kampanya sarı görünür. Birkaç etiket ekleyebilirsiniz.', 'Info is dark, positive is green, negative is red, campaign is yellow. You can add several tags.' ),
		'kit_tag_add'         => array( 'Ekle', 'Add' ),
		'kit_tag_ph'          => array( 'Örn: Hafta sonu favorisi', 'e.g. Weekend favourite' ),
		'tag_info'            => array( 'Bilgi', 'Info' ),
		'tag_good'            => array( 'Olumlu', 'Positive' ),
		'tag_bad'             => array( 'Olumsuz', 'Negative' ),
		'tag_campaign'        => array( 'Kampanya', 'Campaign' ),

		'staff_login'         => array( 'Personel girişi', 'Staff sign in' ),
		'staff_need_login'    => array( 'Devam etmek için personel hesabınızla giriş yapın.', 'Sign in with your staff account to continue.' ),
		'pos_name'            => array( 'Garson', 'Waiter' ),
		'pos_tables'          => array( 'Masalar', 'Tables' ),
		'pos_table'           => array( 'Masa %s', 'Table %s' ),
		'pos_empty'           => array( 'Boş', 'Free' ),
		'pos_busy'            => array( 'Dolu', 'Occupied' ),
		'pos_ready'           => array( 'Hazır', 'Ready' ),
		'pos_bill'            => array( 'Hesap', 'Bill' ),
		'pos_add'             => array( 'Ekle', 'Add' ),
		'pos_done'            => array( 'Tamamlandı', 'Done' ),
		'pos_send'            => array( 'Mutfağa gönder', 'Send to kitchen' ),
		'pos_sent'            => array( 'Sipariş mutfağa iletildi.', 'The order was sent to the kitchen.' ),
		'pos_close'           => array( 'Masayı kapat', 'Close table' ),
		'pos_close_ask'       => array( 'Masa kapatılsın mı? Açık siparişler tahsil edildi kabul edilir.', 'Close this table? Open tickets will be marked as paid.' ),
		'pos_closed'          => array( 'Masa kapatıldı.', 'The table has been closed.' ),
		'pos_move'            => array( 'Masa değiştir', 'Change table' ),
		'pos_move_pick'       => array( 'Yeni masa seçin', 'Pick a new table' ),
		'pos_move_lead'       => array( 'Açık siparişler seçtiğiniz masaya taşınır. Dolu masada hesaplar birleşir.', 'Open tickets move to the table you pick. Occupied tables are merged.' ),
		'pos_move_ask'        => array( 'Açık siparişler Masa %s konumuna taşınsın mı?', 'Move open tickets to Table %s?' ),
		'pos_move_merge'      => array( 'Masa %s dolu. Açık siparişler bu masayla birleştirilsin mi?', 'Table %s is occupied. Merge the open tickets into that table?' ),
		'pos_moved'           => array( 'Masa değiştirildi.', 'The table has been changed.' ),
		'pos_move_same'       => array( 'Aynı masa seçilemez.', 'That is already the current table.' ),
		'pos_current'         => array( 'Bu masa', 'This table' ),
		'pos_cancel'          => array( 'İptal', 'Cancel' ),
		'pos_cancel_ask'      => array( 'Bu sipariş iptal edilsin mi?', 'Cancel this order?' ),
		'pos_cancelled'       => array( 'Sipariş iptal edildi.', 'The order has been cancelled.' ),
		'pos_cancel_late'     => array( 'Mutfak bu siparişi almış, iptal edilemez.', 'The kitchen has already taken this ticket.' ),
		'pos_note'            => array( 'Mutfak notu', 'Kitchen note' ),
		'pos_note_ph'         => array( 'Az pişmiş, soğansız…', 'Medium rare, no onion…' ),
		'pos_open_tickets'    => array( 'Açık siparişler', 'Open tickets' ),
		'pos_no_tickets'      => array( 'Bu masada açık sipariş yok.', 'This table has no open tickets.' ),
		'pos_back'            => array( 'Geri', 'Back' ),
		'pos_back_table'      => array( 'Geri', 'Back' ),
		'pos_cart_empty'      => array( 'Önce menüden ürün ekleyin.', 'Add at least one item first.' ),
		'pos_forbidden'       => array( 'Bu uygulama yalnızca garson hesapları içindir. Yönetici, Kullanıcılar ekranından Garson rolü atayabilir.', 'This app is for waiter accounts. An administrator can assign the Waiter role.' ),
		'pos_disabled'        => array( 'Masa servisi şu anda kapalı.', 'Table service is turned off right now.' ),
		'kds_name'            => array( 'Mutfak ekranı', 'Kitchen display' ),
		'kds_queued'          => array( 'Yeni', 'New' ),
		'kds_cooking'         => array( 'Hazırlanıyor', 'Cooking' ),
		'kds_ready'           => array( 'Hazır', 'Ready' ),
		'kds_served'          => array( 'Servis', 'Served' ),
		'kds_take'            => array( 'Aldım', 'Take' ),
		'kds_done'            => array( 'Hazır', 'Ready' ),
		'kds_serve'           => array( 'Servis edildi', 'Served' ),
		'kds_empty'           => array( 'Şu an bekleyen sipariş yok.', 'There are no tickets waiting.' ),
		'kds_queue'           => array( 'Siparişler', 'Orders' ),
		'kds_all'             => array( 'Tümü', 'All' ),
		'kds_table'           => array( 'Masa', 'Tables' ),
		'kds_online'          => array( 'Online', 'Online' ),
		'kds_sound_on'        => array( 'Ses açık', 'Sound on' ),
		'kds_sound_off'       => array( 'Ses kapalı', 'Sound off' ),
		'kds_ago'             => array( '%s dk', '%s min' ),
		'kds_just'            => array( 'Şimdi', 'Now' ),
		'kds_forbidden'       => array( 'Bu ekran mutfak içindir.', 'This screen is for the kitchen.' ),
		'kds_wa_check'        => array( 'WhatsApp’tan kontrol edin. Mesaj iletilmediyse veya iptal edildiyse hazırlamadan kapatın.', 'Check WhatsApp. If the message was not delivered or was cancelled, close this ticket without preparing it.' ),
		'kds_wa_take_ask'     => array( 'WhatsApp’ta bu siparişin mesajı iletildi mi? İletilmediyse hazırlamadan kapatın.', 'Was this order’s WhatsApp message delivered? If not, close it without preparing.' ),
		'kds_close'           => array( 'Kapat', 'Close' ),
		'kds_close_ask'       => array( 'WhatsApp’ta mesaj yok veya iptal edildi. Sipariş hazırlanmadan kapatılsın mı?', 'No WhatsApp message, or it was cancelled. Close this order without preparing it?' ),
		'kds_closed'          => array( 'Sipariş kapatıldı.', 'The order was closed.' ),
		'staff_open_pos'      => array( 'Garson uygulamasını aç', 'Open waiter app' ),
		'staff_open_kds'      => array( 'Mutfak ekranını aç', 'Open kitchen display' ),
		'staff_open_cash'     => array( 'Kasiyer uygulamasını aç', 'Open cashier app' ),
		'pos_tab_lead'        => array( 'Masayı seçin, siparişi alın, mutfağa gönderin. Tablet veya telefonu ana ekrana ekleyebilirsiniz.', 'Pick a table, take the order, send it to the kitchen. You can add the phone or tablet to the home screen.' ),
		'cash_name'           => array( 'Kasiyer', 'Cashier' ),
		'cash_tab_lead'       => array( 'Açık masaların hesabını görün, nakit veya kart tahsil edin. Kasiyer telefonu veya tableti ana ekrana ekleyebilirsiniz.', 'See open table bills and take cash or card. You can add the cashier phone or tablet to the home screen.' ),
		'cash_forbidden'      => array( 'Bu uygulama yalnızca kasiyer ve yönetici hesapları içindir.', 'This app is only for cashier and administrator accounts.' ),
		'cash_open'           => array( 'Açık masa', 'Open tables' ),
		'cash_open_total'     => array( 'Açık hesap', 'Open total' ),
		'cash_today'          => array( 'Bugün kasa', 'Taken today' ),
		'cash_today_cash'     => array( 'Bugün nakit', 'Cash today' ),
		'cash_today_card'     => array( 'Bugün kart', 'Card today' ),
		'cash_pay_cash'       => array( 'Nakit', 'Cash' ),
		'cash_pay_card'       => array( 'Kart', 'Card' ),
		'cash_pay_ask'        => array( '%s tahsil edilsin mi? Masa kapanacak.', 'Collect as %s? The table will close.' ),
		'cash_paid'           => array( 'Hesap tahsil edildi (%s).', 'Bill collected (%s).' ),
		'cash_no_bill'        => array( 'Tahsil edilecek açık hesap yok.', 'There is no open bill to collect.' ),

		'addr_province'       => array( 'İl', 'Province' ),
		'addr_district'       => array( 'İlçe', 'District' ),
		'addr_neighbourhood'  => array( 'Mahalle', 'Neighbourhood' ),
		'addr_street'         => array( 'Cadde / Sokak', 'Street' ),
		'addr_building'       => array( 'Bina no', 'Building no' ),
		'addr_flat'           => array( 'Daire / Kat', 'Flat / floor' ),
		'addr_zip'            => array( 'Posta kodu', 'Postal code' ),
		'addr_note'           => array( 'Adres tarifi', 'Directions' ),
		'addr_note_ph'        => array( 'Kapı kodu, kat, tarif gibi notlar.', 'Door code, floor, landmarks.' ),
		'addr_select'         => array( 'Seçiniz', 'Select' ),
		'addr_select_first'   => array( 'Önce il seçin', 'Choose a province first' ),
		'addr_required'       => array( 'Adres için il, ilçe, mahalle, cadde/sokak ve bina no zorunludur.', 'Province, district, neighbourhood, street and building number are required.' ),
		'addr_bad_province'   => array( 'İl listeden seçilmelidir.', 'Please pick a province from the list.' ),
		'addr_bad_district'   => array( 'İlçe, seçtiğiniz ile ait değil.', 'That district does not belong to the selected province.' ),
		'addr_bad_zip'        => array( 'Posta kodu 5 haneli olmalı.', 'The postal code must be 5 digits.' ),
		'addr_saved_choose'   => array( 'Kayıtlı adresim', 'Saved address' ),
		'addr_new'            => array( 'Yeni adres yazacağım', 'I will type a new address' ),
	);

	/**
	 * Sözlüğe yeni anahtar eklemek veya metin değiştirmek için filtre.
	 *
	 * @param array $dict Sözlük.
	 */
	return apply_filters( 'ysf_dictionary', $dict );
}

/**
 * Sözlükten metin döndürür.
 *
 * @param string $key  Anahtar.
 * @param string $lang Dil (boşsa aktif dil).
 * @return string
 */
function ysf_t( $key, $lang = '' ) {
	$dict = ysf_dictionary();

	if ( ! isset( $dict[ $key ] ) ) {
		return $key;
	}

	$lang  = $lang ? $lang : ysf_lang();
	$index = ( 'en' === $lang ) ? 1 : 0;

	if ( ! empty( $dict[ $key ][ $index ] ) ) {
		return $dict[ $key ][ $index ];
	}

	return $dict[ $key ][0];
}

/**
 * Sözlük metnini kaçış uygulayarak yazdırır.
 *
 * @param string $key Anahtar.
 */
function ysf_e( $key ) {
	echo esc_html( ysf_t( $key ) );
}

/**
 * JavaScript tarafında kullanılan metinler.
 *
 * @return array
 */
function ysf_js_strings() {
	$keys = array(
		'cart_title',
		'cart_empty',
		'cart_clear',
		'added',
		'subtotal',
		'delivery_fee',
		'total',
		'free',
		'checkout',
		'min_order_warning',
		'free_delivery_hint',
		'order_empty_error',
		'form_required',
		'form_phone_invalid',
		'form_sending',
		'form_submit',
		'form_error',
		'tfa_prompt',
		'tfa_invalid',
		'tfa_code',
		'tfa_continue',
		'tfa_setup_prompt',
		'tfa_scan',
		'tfa_manual',
		'tfa_on',
		'tfa_backups_once',
		'acc_session_stale',
		'acc_verify_send_fail',
		'acc_verify_onscreen',
		'order_send_wa',
		'order_wa_check',
		'menu_no_result',
		'open_now',
		'closed_now',
		'addr_select',
		'addr_select_first',
		'acc_addr_del_ask',
		'acc_addr_empty',
		'acc_save',
		'acc_pass_show',
		'acc_pass_hide',
		'acc_username_invalid',
		'acc_username_short',
		'acc_email_invalid',
		'acc_pass_short',
		'acc_pass_mismatch',
		'acc_need_consent',
		'form_name',
		'form_phone',
		'form_email',
		'acc_verify_wrong',
		'acc_verify_expired',
		'acc_verify_code_exp',
		'acc_verify_locked',
		'acc_reset_ok',
		'kit_remove_ask',
		'kit_sold_out',
		'kit_in_stock',
		'kit_tag_add',
		'tag_info',
		'tag_good',
		'tag_bad',
		'tag_campaign',
	);

	$out = array();
	foreach ( $keys as $key ) {
		$out[ $key ] = ysf_t( $key );
	}

	return $out;
}

/**
 * Personel uygulamalarının JavaScript metinleri.
 *
 * @return array
 */
function ysf_staff_js_strings() {
	$keys = array(
		'form_required',
		'form_sending',
		'form_error',
		'acc_login_ok',
		'acc_login_error',
		'tfa_prompt',
		'tfa_invalid',
		'tfa_code',
		'tfa_continue',
		'tfa_setup_prompt',
		'tfa_scan',
		'tfa_manual',
		'tfa_on',
		'tfa_backups_once',
		'menu_all',
		'menu_search',
		'sold_out',
		'order_delivery',
		'order_pickup',
		'pos_table',
		'pos_empty',
		'pos_busy',
		'pos_ready',
		'pos_bill',
		'pos_send',
		'pos_sent',
		'pos_add',
		'pos_done',
		'pos_close_ask',
		'pos_move_ask',
		'pos_move_merge',
		'pos_moved',
		'pos_current',
		'pos_cancel',
		'pos_cancel_ask',
		'pos_no_tickets',
		'pos_cart_empty',
		'kds_queued',
		'kds_cooking',
		'kds_ready',
		'kds_served',
		'kds_queue',
		'kds_take',
		'kds_done',
		'kds_serve',
		'kds_ago',
		'kds_just',
		'kds_online',
		'kds_wa_check',
		'kds_wa_take_ask',
		'kds_close',
		'kds_close_ask',
		'kds_sound_on',
		'kds_sound_off',
		'cash_pay_cash',
		'cash_pay_card',
		'cash_pay_ask',
		'cash_no_bill',
	);

	$out = array();
	foreach ( $keys as $key ) {
		$out[ $key ] = ysf_t( $key );
	}

	return $out;
}

/**
 * İçeriğin aktif dildeki karşılığını döndürür.
 *
 * İngilizce alan boşsa Türkçe içerik gösterilir, böylece site hiçbir zaman boş kalmaz.
 *
 * @param int    $post_id Gönderi kimliği.
 * @param string $field   'title', 'excerpt' veya 'content'.
 * @return string
 */
function ysf_field( $post_id, $field = 'title' ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return '';
	}

	$fallback = '';
	switch ( $field ) {
		case 'title':
			$fallback = $post->post_title;
			break;
		case 'excerpt':
			$fallback = $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24, '…' );
			break;
		case 'content':
			$fallback = $post->post_content;
			break;
	}

	if ( 'en' !== ysf_lang() ) {
		return $fallback;
	}

	$english = get_post_meta( $post_id, '_ysf_' . $field . '_en', true );

	return $english ? $english : $fallback;
}

/**
 * Terim adının aktif dildeki karşılığı.
 *
 * @param WP_Term|int $term Terim.
 * @return string
 */
function ysf_term_name( $term ) {
	$term = is_numeric( $term ) ? get_term( (int) $term ) : $term;

	if ( ! $term || is_wp_error( $term ) ) {
		return '';
	}

	if ( 'en' === ysf_lang() ) {
		$english = get_term_meta( $term->term_id, '_ysf_name_en', true );
		if ( $english ) {
			return $english;
		}
	}

	return $term->name;
}
