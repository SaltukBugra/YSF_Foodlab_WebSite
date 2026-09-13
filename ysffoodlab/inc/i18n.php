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
		'cart_empty'          => array( 'Sepetiniz henüz boş. Menüden ürün ekleyerek başlayın.', 'Your cart is empty. Add something from the menu to get started.' ),
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
		'order_step_items'    => array( 'Sepetiniz', 'Your order' ),
		'order_step_info'     => array( 'Teslimat bilgileri', 'Delivery details' ),
		'order_type'          => array( 'Sipariş tipi', 'Order type' ),
		'order_delivery'      => array( 'Adrese teslim', 'Delivery' ),
		'order_pickup'        => array( 'Gel al', 'Pickup' ),
		'order_table'         => array( 'Masaya servis', 'Dine-in' ),
		'order_success'       => array( 'Siparişiniz bize ulaştı! Onay için birkaç dakika içinde sizi arayacağız.', 'We received your order. We will call you shortly to confirm.' ),
		'order_wa_hint'       => array( 'WhatsApp penceresi açılmadıysa aşağıdaki butona dokunun.', 'If WhatsApp did not open, tap the button below.' ),
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
		'acc_login_or_phone'  => array( 'E-posta veya telefon', 'E-mail or phone' ),
		'acc_password'        => array( 'Şifre', 'Password' ),
		'acc_password_again'  => array( 'Şifre (tekrar)', 'Repeat password' ),
		'acc_password_new'    => array( 'Yeni şifre', 'New password' ),
		'acc_password_cur'    => array( 'Mevcut şifre', 'Current password' ),
		'acc_password_hint'   => array( 'En az 8 karakter.', 'At least 8 characters.' ),
		'acc_password_keep'   => array( 'Şifrenizi değiştirmek istemiyorsanız bu iki alanı boş bırakın.', 'Leave both fields empty to keep your current password.' ),
		'acc_remember'        => array( 'Beni hatırla', 'Remember me' ),
		'acc_forgot'          => array( 'Şifremi unuttum', 'Forgot my password' ),
		'acc_forgot_title'    => array( 'Şifre sıfırlama', 'Reset password' ),
		'acc_forgot_hint'     => array( 'E-posta adresinizi yazın, sıfırlama bağlantısını gönderelim.', 'Enter your e-mail and we will send you a reset link.' ),
		'acc_forgot_send'     => array( 'Bağlantı gönder', 'Send link' ),
		'acc_forgot_sent'     => array( 'Kayıtlı bir hesap varsa sıfırlama bağlantısını e-posta ile gönderdik.', 'If an account exists for that address, we have sent a reset link.' ),
		'acc_login_btn'       => array( 'Giriş yap', 'Sign in' ),
		'acc_register_btn'    => array( 'Üyeliği tamamla', 'Create account' ),
		'acc_logout'          => array( 'Çıkış yap', 'Sign out' ),
		'acc_login_ok'        => array( 'Giriş yapıldı, yönlendiriliyorsunuz…', 'Signed in, redirecting…' ),
		'acc_login_error'     => array( 'E-posta veya şifre hatalı.', 'Wrong e-mail or password.' ),
		'acc_login_required'  => array( 'Bu bölüm için giriş yapmanız gerekiyor.', 'Please sign in to continue.' ),
		'acc_logged_in'       => array( 'Zaten giriş yaptınız.', 'You are already signed in.' ),
		'acc_registered'      => array( 'Üyeliğiniz oluşturuldu, hoş geldiniz!', 'Your account is ready. Welcome!' ),
		'acc_welcome'         => array( 'Hoş geldiniz', 'Welcome' ),
		'acc_welcome_mail'    => array( 'Üyeliğiniz oluşturuldu. Bundan sonra sipariş ve rezervasyonlarınızda bilgileriniz otomatik dolacak.', 'Your account is ready. From now on your details will be filled in automatically for orders and reservations.' ),
		'acc_closed'          => array( 'Yeni üyelik kaydı şu anda kapalı.', 'New registrations are closed at the moment.' ),
		'acc_too_many'        => array( 'Çok fazla deneme yapıldı. Lütfen bir süre sonra tekrar deneyin.', 'Too many attempts. Please try again later.' ),
		'acc_email_invalid'   => array( 'E-posta adresi geçersiz görünüyor.', 'That e-mail address does not look valid.' ),
		'acc_email_taken'     => array( 'Bu e-posta adresiyle kayıtlı bir üyelik var.', 'An account with this e-mail already exists.' ),
		'acc_phone_taken'     => array( 'Bu telefon numarası başka bir üyelikte kayıtlı.', 'This phone number belongs to another account.' ),
		'acc_pass_short'      => array( 'Şifre en az 8 karakter olmalı.', 'The password must be at least 8 characters.' ),
		'acc_pass_mismatch'   => array( 'Şifreler birbiriyle aynı değil.', 'The passwords do not match.' ),
		'acc_pass_wrong'      => array( 'Mevcut şifreniz hatalı.', 'Your current password is wrong.' ),
		'acc_pass_changed'    => array( 'Şifreniz güncellendi.', 'Your password has been updated.' ),
		'acc_saved'           => array( 'Bilgileriniz kaydedildi.', 'Your details have been saved.' ),
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
		'order_send_wa',
		'menu_no_result',
		'open_now',
		'closed_now',
		'addr_select',
		'addr_select_first',
		'acc_addr_del_ask',
		'acc_addr_empty',
		'acc_save',
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
