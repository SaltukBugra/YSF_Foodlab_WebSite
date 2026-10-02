<?php
/**
 * Müşteri PWA: ana ekrana ekleme, çevrimdışı menü.
 *
 * Bildirim /?ysf_pwa=manifest, servis işçisi /?ysf_pwa=sw adresinden sunulur.
 * Servis işçisi tema dosyalarını ve görselleri önbelleğe alır; menü ve ana
 * sayfa ziyaret edildikçe saklanır, bağlantı yoksa son sürüm gösterilir.
 * Hesap, sipariş takibi, personel ekranları ve AJAX istekleri asla saklanmaz.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PWA açık mı?
 *
 * @return bool
 */
function ysf_pwa_enabled() {
	return (bool) ysf_get_option( 'ysf_pwa', true );
}

/**
 * Sitenin kök yolu (servis işçisi kapsamı).
 *
 * @return string
 */
function ysf_pwa_scope() {
	$path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );

	return $path ? trailingslashit( $path ) : '/';
}

/**
 * Çevrimdışı saklanabilecek sayfalar.
 *
 * @return string[]
 */
function ysf_pwa_cacheable_pages() {
	$pages = array( home_url( '/' ) );

	foreach ( array( 'template-menu.php', 'template-contact.php' ) as $template ) {
		$url = ysf_get_page_url_by_template( $template );

		if ( $url ) {
			$pages[] = $url;
			$pages[] = ysf_localize_url( $url );
		}
	}

	$pages = array_map(
		static function ( $url ) {
			return untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		},
		$pages
	);

	return array_values( array_unique( $pages ) );
}

/**
 * Bildirim dosyası ve servis işçisini sunar.
 */
function ysf_pwa_serve() {
	if ( ! isset( $_GET['ysf_pwa'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$kind = sanitize_key( wp_unslash( $_GET['ysf_pwa'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! ysf_pwa_enabled() ) {
		if ( 'sw' === $kind ) {
			// Kapatılınca eski servis işçisi kendini kaldırır.
			header( 'Content-Type: application/javascript; charset=utf-8' );
			nocache_headers();
			echo "self.addEventListener('install',function(){self.skipWaiting();});self.addEventListener('activate',function(e){e.waitUntil(caches.keys().then(function(k){return Promise.all(k.filter(function(n){return n.indexOf('ysf-pwa-')===0;}).map(function(n){return caches.delete(n);}));}).then(function(){return self.registration.unregister();}));});";
			exit;
		}

		status_header( 404 );
		exit;
	}

	if ( 'manifest' === $kind ) {
		ysf_pwa_manifest();
	}

	if ( 'sw' === $kind ) {
		ysf_pwa_worker();
	}
}
add_action( 'template_redirect', 'ysf_pwa_serve', 0 );

/**
 * Web uygulaması bildirimi.
 */
function ysf_pwa_manifest() {
	$icons = array();
	$small = (string) get_site_icon_url( 192 );
	$big   = (string) get_site_icon_url( 512 );

	if ( ! $small ) {
		$small = YSF_URI . '/assets/brand/app-icon-192.png';
		$big   = YSF_URI . '/assets/brand/app-icon-512.png';
	}

	$icons[] = array(
		'src'     => $small,
		'sizes'   => '192x192',
		'type'    => 'image/png',
		'purpose' => 'any',
	);
	$icons[] = array(
		'src'     => $big ? $big : $small,
		'sizes'   => '512x512',
		'type'    => 'image/png',
		'purpose' => 'any maskable',
	);

	$shortcuts = array();

	foreach (
		array(
			'template-menu.php'        => 'nav_menu',
			'template-order.php'       => 'nav_order',
			'template-reservation.php' => 'nav_reservation',
		) as $template => $label
	) {
		$url = ysf_get_page_url_by_template( $template );

		if ( $url ) {
			$shortcuts[] = array(
				'name' => ysf_t( $label ),
				'url'  => ysf_localize_url( $url ),
			);
		}
	}

	$name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$manifest = array(
		'id'               => ysf_pwa_scope(),
		'name'             => $name,
		'short_name'       => function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 12 ) : substr( $name, 0, 12 ),
		'description'      => wp_specialchars_decode( get_bloginfo( 'description' ), ENT_QUOTES ),
		'start_url'        => add_query_arg( 'utm_source', 'pwa', home_url( '/' ) ),
		'scope'            => ysf_pwa_scope(),
		'display'          => 'standalone',
		'orientation'      => 'portrait',
		'background_color' => '#fbf7f1',
		'theme_color'      => '#14100d',
		'lang'             => 'en' === ysf_lang() ? 'en' : 'tr',
		'icons'            => $icons,
		'shortcuts'        => $shortcuts,
	);

	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	header( 'Cache-Control: public, max-age=86400' );
	echo wp_json_encode( $manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}

/**
 * Servis işçisi.
 */
function ysf_pwa_worker() {
	$config = array(
		'version' => YSF_VERSION,
		'scope'   => ysf_pwa_scope(),
		'pages'   => ysf_pwa_cacheable_pages(),
		'assets'  => wp_parse_url( YSF_URI, PHP_URL_PATH ) . '/',
		'uploads' => (string) wp_parse_url( content_url( '/uploads/' ), PHP_URL_PATH ),
		'offline' => array(
			'title' => ysf_t( 'pwa_offline_title' ),
			'text'  => ysf_t( 'pwa_offline_text' ),
			'retry' => ysf_t( 'pwa_offline_retry' ),
			'lang'  => 'en' === ysf_lang() ? 'en' : 'tr',
		),
	);

	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Service-Worker-Allowed: ' . ysf_pwa_scope() );
	nocache_headers();

	echo 'var YSF=' . wp_json_encode( $config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ";\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	readfile( YSF_DIR . '/assets/js/pwa-sw.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}

/**
 * Bildirim bağlantısı ve iOS etiketleri.
 */
function ysf_pwa_head() {
	if ( ! ysf_pwa_enabled() || ( function_exists( 'ysf_is_staff_app' ) && ysf_is_staff_app() ) ) {
		return;
	}

	$icon = (string) get_site_icon_url( 180 );
	$icon = $icon ? $icon : YSF_URI . '/assets/brand/app-icon-192.png';

	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( add_query_arg( 'ysf_pwa', 'manifest', home_url( '/' ) ) ) );
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
	printf( '<meta name="apple-mobile-web-app-title" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );

	if ( ! has_site_icon() ) {
		printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $icon ) );
	}
}
add_action( 'wp_head', 'ysf_pwa_head', 3 );

/**
 * Ön yüz betiğine PWA ayarlarını ekler.
 *
 * @return array
 */
function ysf_pwa_js_settings() {
	if ( ! ysf_pwa_enabled() ) {
		return array( 'sw' => '' );
	}

	return array(
		'sw'    => add_query_arg( 'ysf_pwa', 'sw', home_url( '/' ) ),
		'scope' => ysf_pwa_scope(),
	);
}
