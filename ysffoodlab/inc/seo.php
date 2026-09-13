<?php
/**
 * SEO: açıklama etiketleri, Open Graph, hreflang ve Restaurant schema.
 *
 * Yoast / Rank Math gibi bir SEO eklentisi kuruluysa çakışmayı önlemek için
 * temanın meta çıktısı otomatik olarak devre dışı kalır.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Harici bir SEO eklentisi aktif mi?
 *
 * @return bool
 */
function ysf_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || class_exists( 'SEOPress' );
}

/**
 * Sayfa açıklaması üretir.
 *
 * @return string
 */
function ysf_meta_description() {
	if ( is_front_page() ) {
		$desc = ysf_option_i18n( 'ysf_hero_text', '' );

		if ( ! $desc ) {
			$desc = get_bloginfo( 'description' );
		}

		return $desc;
	}

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		$desc    = ysf_field( $post_id, 'excerpt' );

		return wp_trim_words( wp_strip_all_tags( $desc ), 30, '…' );
	}

	if ( is_tax() || is_category() ) {
		$term = get_queried_object();

		if ( $term && ! empty( $term->description ) ) {
			return wp_trim_words( wp_strip_all_tags( $term->description ), 30, '…' );
		}
	}

	return get_bloginfo( 'description' );
}

/**
 * Paylaşım görseli.
 *
 * @return string
 */
function ysf_share_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		return (string) get_the_post_thumbnail_url( get_queried_object_id(), 'ysf-hero' );
	}

	$hero = ysf_get_option( 'ysf_hero_image', '' );

	if ( $hero ) {
		return $hero;
	}

	return (string) get_site_icon_url( 512 );
}

/**
 * head bölümüne meta etiketleri basar.
 */
function ysf_print_head_meta() {
	$desc = ysf_meta_description();

	if ( ! ysf_seo_plugin_active() ) {
		if ( $desc ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
		}

		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		printf( '<meta property="og:type" content="%s">' . "\n", is_singular() ? 'article' : 'website' );
		printf( '<meta property="og:locale" content="%s">' . "\n", 'en' === ysf_lang() ? 'en_US' : 'tr_TR' );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( ysf_current_url() ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( wp_get_document_title() ) );

		if ( $desc ) {
			printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( wp_strip_all_tags( $desc ) ) );
		}

		$image = ysf_share_image();

		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
			echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		}
	}

	// Dil alternatifleri.
	printf( '<link rel="alternate" hreflang="tr" href="%s">' . "\n", esc_url( ysf_lang_url( 'tr' ) ) );
	printf( '<link rel="alternate" hreflang="en" href="%s">' . "\n", esc_url( ysf_lang_url( 'en' ) ) );
	printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( ysf_lang_url( 'tr' ) ) );

	printf( '<meta name="theme-color" content="%s">' . "\n", '#14100d' );

	// Kapak görselini erken yükle (LCP).
	if ( is_front_page() ) {
		$hero = ysf_get_option( 'ysf_hero_image', '' );

		if ( $hero ) {
			printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $hero ) );
		}
	}
}
add_action( 'wp_head', 'ysf_print_head_meta', 2 );

/**
 * Geçerli sayfanın tam adresi.
 *
 * @return string
 */
function ysf_current_url() {
	global $wp;

	return home_url( $wp->request ? $wp->request . '/' : '/' );
}

/**
 * Restaurant + Menu + Breadcrumb JSON-LD çıktısı.
 */
function ysf_print_schema() {
	$hours = array();

	$day_map = array(
		'mon' => 'Monday',
		'tue' => 'Tuesday',
		'wed' => 'Wednesday',
		'thu' => 'Thursday',
		'fri' => 'Friday',
		'sat' => 'Saturday',
		'sun' => 'Sunday',
	);

	foreach ( ysf_get_hours() as $day => $range ) {
		if ( ! $range || preg_match( '/kapal|closed/i', $range ) ) {
			continue;
		}

		if ( ! preg_match( '/(\d{1,2})[:.](\d{2})\s*[-–]\s*(\d{1,2})[:.](\d{2})/', $range, $m ) ) {
			continue;
		}

		$hours[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => $day_map[ $day ],
			'opens'     => sprintf( '%02d:%02d', (int) $m[1], (int) $m[2] ),
			'closes'    => sprintf( '%02d:%02d', (int) $m[3], (int) $m[4] ),
		);
	}

	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Restaurant',
		'@id'      => home_url( '/#restaurant' ),
		'name'     => get_bloginfo( 'name' ),
		'url'      => home_url( '/' ),
	);

	$description = get_bloginfo( 'description' );

	if ( $description ) {
		$schema['description'] = $description;
	}

	$phone = ysf_get_option( 'ysf_phone', '' );

	if ( $phone ) {
		$schema['telephone'] = $phone;
	}

	$email = ysf_get_option( 'ysf_email', '' );

	if ( $email ) {
		$schema['email'] = $email;
	}

	$address = ysf_get_option( 'ysf_address', '' );

	if ( $address ) {
		$schema['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => wp_strip_all_tags( $address ),
			'addressLocality' => ysf_get_option( 'ysf_city', '' ),
			'postalCode'      => ysf_get_option( 'ysf_postal', '' ),
			'addressCountry'  => 'TR',
		);
	}

	$cuisine = ysf_get_option( 'ysf_cuisine', '' );

	if ( $cuisine ) {
		$schema['servesCuisine'] = $cuisine;
	}

	$price_range = ysf_get_option( 'ysf_price_range', '' );

	if ( $price_range ) {
		$schema['priceRange'] = $price_range;
	}

	if ( $hours ) {
		$schema['openingHoursSpecification'] = $hours;
	}

	$image = ysf_share_image();

	if ( $image ) {
		$schema['image'] = $image;
	}

	$menu_url = ysf_get_page_url_by_template( 'template-menu.php' );

	if ( $menu_url ) {
		$schema['hasMenu'] = $menu_url;
	}

	$order_url = ysf_get_page_url_by_template( 'template-order.php' );

	if ( $order_url ) {
		$schema['acceptsReservations'] = ysf_get_option( 'ysf_res_enabled', true ) ? 'True' : 'False';
		$schema['potentialAction']     = array(
			'@type'  => 'OrderAction',
			'target' => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $order_url,
			),
		);
	}

	$social = array();

	foreach ( ysf_social_links() as $link ) {
		$social[] = $link['url'];
	}

	if ( $social ) {
		$schema['sameAs'] = $social;
	}

	$graph = array( $schema );

	// Menü sayfasında ayrıca Menu şeması.
	if ( is_page_template( 'template-menu.php' ) ) {
		$sections = array();
		$terms    = get_terms(
			array(
				'taxonomy'   => 'ysf_menu_cat',
				'hide_empty' => true,
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$items = ysf_get_menu_items( array( 'category' => $term->term_id ) );
				$list  = array();

				foreach ( $items as $item ) {
					$entry = array(
						'@type' => 'MenuItem',
						'name'  => get_the_title( $item ),
					);

					$price = (float) get_post_meta( $item->ID, '_ysf_price', true );

					if ( $price ) {
						$entry['offers'] = array(
							'@type'         => 'Offer',
							'price'         => $price,
							'priceCurrency' => 'TRY',
						);
					}

					$list[] = $entry;
				}

				if ( $list ) {
					$sections[] = array(
						'@type'          => 'MenuSection',
						'name'           => $term->name,
						'hasMenuItem'    => $list,
					);
				}
			}
		}

		if ( $sections ) {
			$graph[] = array(
				'@context'       => 'https://schema.org',
				'@type'          => 'Menu',
				'name'           => get_the_title(),
				'url'            => get_permalink(),
				'hasMenuSection' => $sections,
			);
		}
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( 1 === count( $graph ) ? $graph[0] : $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_footer', 'ysf_print_schema', 30 );

/**
 * Belge başlığını İngilizce sürümde çevirir.
 *
 * @param array $parts Başlık parçaları.
 * @return array
 */
function ysf_document_title_parts( $parts ) {
	if ( 'en' !== ysf_lang() ) {
		return $parts;
	}

	if ( is_singular() ) {
		$english = get_post_meta( get_queried_object_id(), '_ysf_title_en', true );

		if ( $english ) {
			$parts['title'] = $english;
		}
	}

	return $parts;
}
add_filter( 'document_title_parts', 'ysf_document_title_parts' );

/**
 * Rezervasyon ve sipariş kayıtlarını arama motorlarından uzak tutar.
 */
function ysf_noindex_requests() {
	if ( is_singular( array( 'ysf_reservation', 'ysf_order' ) ) ) {
		echo '<meta name="robots" content="noindex,nofollow">' . "\n";
	}
}
add_action( 'wp_head', 'ysf_noindex_requests', 1 );
