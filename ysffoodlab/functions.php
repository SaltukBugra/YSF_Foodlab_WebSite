<?php
/**
 * YSF Food Lab teması - ana yapılandırma dosyası.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YSF_VERSION', '1.4.39' );
define( 'YSF_DIR', get_template_directory() );
define( 'YSF_URI', get_template_directory_uri() );
define( 'YSF_MAIL_FROM', 'info@ysffoodlab.com.tr' );

require_once YSF_DIR . '/inc/i18n.php';
require_once YSF_DIR . '/inc/post-types.php';
require_once YSF_DIR . '/inc/meta.php';
require_once YSF_DIR . '/inc/customizer.php';
require_once YSF_DIR . '/inc/template-tags.php';
require_once YSF_DIR . '/inc/campaigns.php';
require_once YSF_DIR . '/inc/announcements.php';
require_once YSF_DIR . '/inc/orders.php';
require_once YSF_DIR . '/inc/reservations.php';
require_once YSF_DIR . '/inc/accounts.php';
require_once YSF_DIR . '/inc/two-factor.php';
require_once YSF_DIR . '/inc/kitchen.php';
require_once YSF_DIR . '/inc/floor.php';
require_once YSF_DIR . '/inc/seo.php';
require_once YSF_DIR . '/inc/setup-wizard.php';

/**
 * Tema desteklerini kaydeder.
 */
function ysf_theme_setup() {
	load_theme_textdomain( 'ysffoodlab', YSF_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 200,
			'width'       => 200,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_image_size( 'ysf-card', 720, 460, true );
	add_image_size( 'ysf-menu', 960, 540, true );
	add_image_size( 'ysf-thumb', 320, 320, true );
	add_image_size( 'ysf-hero', 1920, 1100, true );

	register_nav_menus(
		array(
			'primary'   => __( 'Ana Menü (üst)', 'ysffoodlab' ),
			'secondary' => __( 'Alt Bilgi Menüsü', 'ysffoodlab' ),
		)
	);
}
add_action( 'after_setup_theme', 'ysf_theme_setup' );

/**
 * İçerik genişliği.
 */
function ysf_content_width() {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'ysf_content_width', 0 );

/**
 * Kenar çubuğu alanı.
 */
function ysf_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Blog Kenar Çubuğu', 'ysffoodlab' ),
			'id'            => 'ysf-sidebar',
			'description'   => __( 'Blog ve yazı sayfalarında sağda görünür.', 'ysffoodlab' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'ysf_widgets_init' );

/**
 * Stil ve betikleri yükler. jQuery kullanılmaz.
 */
function ysf_enqueue_assets() {
	$css_path = YSF_DIR . '/style.css';
	$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : YSF_VERSION;

	if ( ysf_get_option( 'ysf_google_fonts', true ) ) {
		wp_enqueue_style(
			'ysf-fonts',
			'https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		);
	}

	wp_enqueue_style( 'ysf-style', get_stylesheet_uri(), array(), $css_ver );

	if ( ysf_is_staff_app() ) {
		$staff_path = YSF_DIR . '/assets/js/staff.js';
		$staff_ver  = file_exists( $staff_path ) ? (string) filemtime( $staff_path ) : YSF_VERSION;

		wp_enqueue_script( 'ysf-staff', YSF_URI . '/assets/js/staff.js', array(), $staff_ver, true );

		wp_localize_script(
			'ysf-staff',
			'YSF',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ysf_public' ),
				'lang'     => ysf_lang(),
				'currency' => ysf_get_option( 'ysf_currency', '₺' ),
				'pollMs'   => 4000,
				'utcOffset'  => ysf_utc_offset(),
				'i18n'       => ysf_staff_js_strings(),
				'campaigns'  => function_exists( 'ysf_campaign_rules_for_js' ) ? ysf_campaign_rules_for_js() : array(),
			)
		);

		return;
	}

	$js_path = YSF_DIR . '/assets/js/main.js';
	$js_ver  = file_exists( $js_path ) ? (string) filemtime( $js_path ) : YSF_VERSION;

	wp_enqueue_script( 'ysf-main', YSF_URI . '/assets/js/main.js', array(), $js_ver, true );

	wp_localize_script(
		'ysf-main',
		'YSF',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'ysf_public' ),
			'lang'         => ysf_lang(),
			'currency'     => ysf_get_option( 'ysf_currency', '₺' ),
			'orderPageUrl' => ysf_get_page_url_by_template( 'template-order.php' ),
			'geoUrl'       => ysf_geo_url(),
			'whatsapp'     => ysf_digits( ysf_get_option( 'ysf_whatsapp', '' ) ),
			'minOrder'     => (float) ysf_get_option( 'ysf_min_order', 0 ),
			'deliveryFee'  => (float) ysf_get_option( 'ysf_delivery_fee', 0 ),
			'freeOver'     => (float) ysf_get_option( 'ysf_free_delivery_over', 0 ),
			'hours'        => ysf_get_hours(),
			'utcOffset'    => ysf_utc_offset(),
			'i18n'         => ysf_js_strings(),
			'campaigns'    => function_exists( 'ysf_campaign_rules_for_js' ) ? ysf_campaign_rules_for_js() : array(),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ysf_enqueue_assets' );

/**
 * Fonts için preconnect ipuçları.
 *
 * @param array  $urls          Kaynak ipuçları.
 * @param string $relation_type İlişki türü.
 * @return array
 */
function ysf_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type && ysf_get_option( 'ysf_google_fonts', true ) ) {
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'ysf_resource_hints', 10, 2 );

/**
 * Gereksiz WordPress çıktılarını temizler (hız + güvenlik).
 */
function ysf_cleanup_head() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
}
add_action( 'init', 'ysf_cleanup_head' );

/**
 * Blok editörünün ön yüzdeki varsayılan stillerini kaldırır (kullanılmıyor).
 */
function ysf_dequeue_unused() {
	if ( ! is_admin() ) {
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'ysf_dequeue_unused', 100 );

/**
 * Cookie Admin Pro çerez onayını yalnızca admin-ajax kaydı başarılı dönerse
 * tarayıcıya yazıyor. Sunucu tarafındaki kayıt hata verdiğinde onay hiç
 * saklanmadığı için uyarı her sayfada yeniden çıkıyor. Eklentinin kaydetme
 * fonksiyonunu sarmalayıp onayı çereze de yazdırıyoruz.
 */
function ysf_cookieadmin_consent_fix() {
	if ( ! wp_script_is( 'cookieadmin_js', 'enqueued' ) || ! wp_script_is( 'cookieadmin_pro_js', 'enqueued' ) ) {
		return;
	}

	$js = <<<'JS'
( function () {
	function wrap() {
		if ( typeof window.cookieadmin_pro_set_consent !== 'function' ) {
			return;
		}

		var proSetConsent = window.cookieadmin_pro_set_consent;

		window.cookieadmin_pro_set_consent = function ( preference, days ) {
			var result = proSetConsent.apply( this, arguments );

			if ( false === result || typeof window.cookieadmin_save_consent_cookie !== 'function' ) {
				return result;
			}

			try {
				window.cookieadmin_save_consent_cookie( preference, days || 365 );
			} catch ( e ) {}

			return result;
		};
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', wrap );
	} else {
		wrap();
	}
}() );
JS;

	wp_add_inline_script( 'cookieadmin_js', $js );
}
add_action( 'wp_enqueue_scripts', 'ysf_cookieadmin_consent_fix', 99 );

/**
 * Alıntı uzunluğu ve sonu.
 *
 * @return int
 */
function ysf_excerpt_length() {
	return 24;
}
add_filter( 'excerpt_length', 'ysf_excerpt_length', 999 );

/**
 * Alıntı sonu karakteri.
 *
 * @return string
 */
function ysf_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'ysf_excerpt_more' );

/**
 * Gövde sınıfları.
 *
 * @param array $classes Sınıflar.
 * @return array
 */
function ysf_body_classes( $classes ) {
	$classes[] = 'ysf-lang-' . ysf_lang();

	if ( ! is_active_sidebar( 'ysf-sidebar' ) ) {
		$classes[] = 'ysf-no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'ysf_body_classes' );

/**
 * Form sayfalarını tam sayfa önbelleğin dışında tutar.
 *
 * LiteSpeed gibi bir önbellek bu sayfaları saklarsa güvenlik anahtarı (nonce)
 * bayatlar ve gönderimler reddedilir. Bu yüzden sipariş, rezervasyon ve
 * iletişim sayfaları her zaman taze sunulur.
 */
function ysf_no_cache_form_pages() {
	$templates = array( 'template-order.php', 'template-reservation.php', 'template-contact.php', 'template-account.php', 'template-waiter.php', 'template-kds.php', 'template-cashier.php' );
	$is_form   = false;

	foreach ( $templates as $template ) {
		if ( is_page_template( $template ) ) {
			$is_form = true;
			break;
		}
	}

	if ( ! $is_form ) {
		return;
	}

	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'YSF form sayfasi' );
}
add_action( 'template_redirect', 'ysf_no_cache_form_pages', 20 );

/**
 * Yönetim panelinde tema için kısa yardım menüsü ve stil.
 */
function ysf_admin_assets() {
	$css = '
		.ysf-admin-note{background:#fff;border:1px solid #dcdcde;border-left:4px solid #d98324;padding:16px 20px;margin:16px 0 20px;border-radius:4px;line-height:1.65}
		.ysf-admin-note h2{margin:0 0 12px;font-size:16px}
		.ysf-admin-note p{margin:0 0 10px}
		.ysf-admin-note p:last-child{margin-bottom:0}
		.ysf-meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px 22px}
		.ysf-meta-grid label{display:block;font-weight:600;margin-bottom:6px}
		.ysf-meta-grid input[type=text],.ysf-meta-grid input[type=number],.ysf-meta-grid input[type=date],.ysf-meta-grid input[type=time],.ysf-meta-grid input[type=url],.ysf-meta-grid textarea,.ysf-meta-grid select{width:100%}
		.ysf-meta-grid .description{margin:8px 0 0;line-height:1.55}
		.ysf-meta-full{grid-column:1/-1}
		.ysf-badge-pending{background:#f0b849;color:#1d2327;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600}
		.ysf-badge-done{background:#68de7c;color:#1d2327;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600}
		.ysf-badge-expired{background:#dcdcde;color:#1d2327;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600}
		.ysf-camp-products{max-height:560px;overflow:auto;border:1px solid #dcdcde;background:#fff;padding:14px;border-radius:8px}
		.ysf-camp-search{width:100%;margin:0 0 16px;padding:8px 10px}
		.ysf-camp-groups{display:grid;gap:18px}
		.ysf-camp-cat{margin:0 0 8px;font-size:14px}
		.ysf-camp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px}
		.ysf-camp-check{display:grid;grid-template-columns:18px 48px minmax(0,1fr);gap:8px;align-items:center;margin:0;padding:8px;border:1px solid #dcdcde;border-radius:8px;line-height:1.35}
		.ysf-camp-check img{width:48px;height:48px;object-fit:cover;border-radius:6px}
		.ysf-camp-check[hidden],.ysf-camp-group[hidden]{display:none !important}
		.ysf-camp-label{display:block;font-weight:600;margin-bottom:8px}
		.ysf-campaign-form{max-width:920px;margin-top:16px}
	';
	wp_register_style( 'ysf-admin', false, array(), YSF_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
	wp_enqueue_style( 'ysf-admin' );
	wp_add_inline_style( 'ysf-admin', $css );
}
add_action( 'admin_enqueue_scripts', 'ysf_admin_assets' );

/**
 * SMTP şifresi yoksa yönetim panelinde doğrudan özelleştir bağlantısı gösterir.
 */
function ysf_smtp_admin_notice() {
	if ( ! current_user_can( 'customize' ) ) {
		return;
	}

	if ( (string) get_theme_mod( 'ysf_smtp_pass', '' ) ) {
		return;
	}

	$url = admin_url( 'customize.php?autofocus[control]=ysf_smtp_pass' );

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Üye doğrulama mailleri için SMTP şifresi gerekli.', 'ysffoodlab' );
	echo ' <a href="' . esc_url( $url ) . '">';
	echo esc_html__( 'Görünüm → Özelleştir → YSF Food Lab Ayarları → İşletme Bilgileri', 'ysffoodlab' );
	echo '</a></p></div>';
}
add_action( 'admin_notices', 'ysf_smtp_admin_notice' );
