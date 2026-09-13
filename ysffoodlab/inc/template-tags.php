<?php
/**
 * Şablon yardımcıları.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tema ayarını döndürür.
 *
 * @param string $key     Ayar anahtarı.
 * @param mixed  $default Varsayılan değer.
 * @return mixed
 */
function ysf_get_option( $key, $default = '' ) {
	return get_theme_mod( $key, $default );
}

/**
 * Metinden sadece rakamları alır (telefon bağlantıları için).
 *
 * @param string $value Değer.
 * @return string
 */
function ysf_digits( $value ) {
	return preg_replace( '/\D+/', '', (string) $value );
}

/**
 * Fiyatı biçimlendirir.
 *
 * @param float $amount Tutar.
 * @return string
 */
function ysf_price( $amount ) {
	$currency = ysf_get_option( 'ysf_currency', '₺' );
	$amount   = (float) $amount;
	$decimals = ( abs( $amount - round( $amount ) ) < 0.005 ) ? 0 : 2;

	return number_format_i18n( $amount, $decimals ) . ' ' . $currency;
}

/**
 * Belirli bir sayfa şablonunu kullanan ilk sayfanın bağlantısı.
 *
 * @param string $template Şablon dosya adı.
 * @return string
 */
function ysf_get_page_url_by_template( $template ) {
	$cache_key = 'ysf_tpl_url_' . md5( $template );
	$cached    = wp_cache_get( $cache_key, 'ysffoodlab' );

	if ( false !== $cached ) {
		return $cached;
	}

	$pages = get_posts(
		array(
			'post_type'        => 'page',
			'posts_per_page'   => 1,
			'post_status'      => 'publish',
			'meta_key'         => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	$url = ! empty( $pages ) ? get_permalink( $pages[0] ) : '';
	wp_cache_set( $cache_key, $url, 'ysffoodlab', HOUR_IN_SECONDS );

	return $url;
}

/**
 * Aktif dilde bağlantı üretir (EN ise ?lang=en eklenir).
 *
 * @param string $url Bağlantı.
 * @return string
 */
function ysf_localize_url( $url ) {
	if ( ! $url || 'en' !== ysf_lang() ) {
		return $url;
	}

	return add_query_arg( 'lang', 'en', $url );
}

/**
 * WhatsApp sohbet bağlantısı.
 *
 * @param string $message Ön dolgulu mesaj.
 * @return string
 */
function ysf_whatsapp_url( $message = '' ) {
	$number = ysf_digits( ysf_get_option( 'ysf_whatsapp', '' ) );

	if ( ! $number ) {
		return '';
	}

	$url = 'https://wa.me/' . $number;

	if ( $message ) {
		$url = add_query_arg( 'text', rawurlencode( $message ), $url );
	}

	return $url;
}

/**
 * Haftanın günleri ve ayar anahtarları.
 *
 * @return array
 */
function ysf_week_days() {
	return array(
		'mon' => array( 'Pazartesi', 'Monday' ),
		'tue' => array( 'Salı', 'Tuesday' ),
		'wed' => array( 'Çarşamba', 'Wednesday' ),
		'thu' => array( 'Perşembe', 'Thursday' ),
		'fri' => array( 'Cuma', 'Friday' ),
		'sat' => array( 'Cumartesi', 'Saturday' ),
		'sun' => array( 'Pazar', 'Sunday' ),
	);
}

/**
 * Çalışma saatlerini döndürür.
 *
 * @return array gün kodu => saat metni
 */
function ysf_get_hours() {
	$hours = array();

	foreach ( array_keys( ysf_week_days() ) as $day ) {
		$hours[ $day ] = trim( (string) ysf_get_option( 'ysf_hours_' . $day, '11:00-23:00' ) );
	}

	return $hours;
}

/**
 * Temayla birlikte gelen görselin adresi.
 *
 * @param string $file Dosya adı (assets/images içinde).
 * @return string
 */
function ysf_asset_image( $file ) {
	if ( ! file_exists( YSF_DIR . '/assets/images/' . $file ) ) {
		return '';
	}

	return YSF_URI . '/assets/images/' . $file;
}

/**
 * Kapak slaytında gösterilecek görseller.
 *
 * Özelleştirici'den görsel seçilmediyse temayla gelen varsayılanlar kullanılır,
 * böylece site ilk günden boş görünmez.
 *
 * @return array
 */
function ysf_hero_slides() {
	$slides = array();

	foreach ( array( 'ysf_hero_image', 'ysf_hero_image_2', 'ysf_hero_image_3', 'ysf_hero_image_4' ) as $key ) {
		$url = ysf_get_option( $key, '' );

		if ( $url ) {
			$slides[] = $url;
		}
	}

	if ( $slides ) {
		return $slides;
	}

	foreach ( array( 'hero-1.jpg', 'hero-2.jpg', 'hero-3.jpg', 'hero-4.jpg' ) as $file ) {
		$url = ysf_asset_image( $file );

		if ( $url ) {
			$slides[] = $url;
		}
	}

	return $slides;
}

/**
 * Hakkımızda bölümü görseli (seçilmediyse varsayılan).
 *
 * @return string
 */
function ysf_about_image() {
	$url = ysf_get_option( 'ysf_about_image', '' );

	return $url ? $url : ysf_asset_image( 'about.jpg' );
}

/**
 * Fotoğrafı olmayan ürünler için yedek görsel.
 *
 * @return string
 */
function ysf_placeholder_image() {
	return ysf_asset_image( 'placeholder.jpg' );
}

/**
 * Sitenin UTC farkını saniye cinsinden döndürür (yaz saati dahil).
 *
 * @return int
 */
function ysf_utc_offset() {
	try {
		$now = new DateTimeImmutable( 'now', wp_timezone() );

		return (int) $now->getOffset();
	} catch ( Exception $e ) {
		return (int) ( (float) get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );
	}
}

/**
 * Bugünün gün kodu.
 *
 * @return string
 */
function ysf_today_key() {
	$map = array( 'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' );
	$idx = (int) current_time( 'w' );

	return $map[ $idx ];
}

/**
 * Restoran şu anda açık mı?
 *
 * @return bool
 */
function ysf_is_open_now() {
	$hours = ysf_get_hours();
	$today = ysf_today_key();
	$now   = (int) current_time( 'H' ) * 60 + (int) current_time( 'i' );

	$candidates = array( $hours[ $today ] );

	// Gece yarısını aşan dünkü aralığı da kontrol et.
	$order = array_keys( ysf_week_days() );
	$pos   = array_search( $today, $order, true );
	if ( false !== $pos ) {
		$candidates[] = $hours[ $order[ ( $pos + 6 ) % 7 ] ] . '|yesterday';
	}

	foreach ( $candidates as $raw ) {
		$is_yesterday = false !== strpos( $raw, '|yesterday' );
		$raw          = str_replace( '|yesterday', '', $raw );

		if ( ! $raw || preg_match( '/kapal|closed/i', $raw ) ) {
			continue;
		}

		foreach ( explode( ',', $raw ) as $range ) {
			if ( ! preg_match( '/(\d{1,2})[:.](\d{2})\s*[-–]\s*(\d{1,2})[:.](\d{2})/', $range, $m ) ) {
				continue;
			}

			$start = (int) $m[1] * 60 + (int) $m[2];
			$end   = (int) $m[3] * 60 + (int) $m[4];

			if ( $end <= $start ) {
				// Gece yarısını aşan aralık.
				if ( $is_yesterday ) {
					if ( $now < $end ) {
						return true;
					}
				} elseif ( $now >= $start ) {
					return true;
				}
				continue;
			}

			if ( ! $is_yesterday && $now >= $start && $now < $end ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Yayında olan kampanya / duyuruları getirir.
 *
 * @param array $args Ek argümanlar: limit, type, bar_only.
 * @return WP_Post[]
 */
function ysf_get_campaigns( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'limit'    => 6,
			'type'     => '',
			'bar_only' => false,
		)
	);

	$meta_query = array(
		'relation' => 'AND',
		array(
			'relation' => 'OR',
			array(
				'key'     => '_ysf_end',
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
			array(
				'key'     => '_ysf_end',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'   => '_ysf_end',
				'value' => '',
			),
		),
	);

	if ( $args['type'] ) {
		$meta_query[] = array(
			'key'   => '_ysf_type',
			'value' => $args['type'],
		);
	}

	if ( $args['bar_only'] ) {
		$meta_query[] = array(
			'key'   => '_ysf_show_in_bar',
			'value' => '1',
		);
	}

	return get_posts(
		array(
			'post_type'      => 'ysf_campaign',
			'posts_per_page' => (int) $args['limit'],
			'post_status'    => 'publish',
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);
}

/**
 * Menü ürünlerini getirir.
 *
 * @param array $args limit, category, featured, orderable.
 * @return WP_Post[]
 */
function ysf_get_menu_items( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'limit'    => -1,
			'category' => '',
			'featured' => false,
		)
	);

	$query = array(
		'post_type'      => 'ysf_menu_item',
		'posts_per_page' => (int) $args['limit'],
		'post_status'    => 'publish',
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
	);

	if ( $args['featured'] ) {
		$query['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'   => '_ysf_featured',
				'value' => '1',
			),
		);
	}

	if ( $args['category'] ) {
		$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'ysf_menu_cat',
				'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
				'terms'    => $args['category'],
			),
		);
	}

	return get_posts( $query );
}

/**
 * Ürünün diyet / özellik etiketlerini döndürür.
 *
 * @param int $post_id Ürün kimliği.
 * @return array
 */
function ysf_item_badges( $post_id ) {
	$badges = array();

	$flags = array(
		'_ysf_vegan'      => array( 'vegan', 'ysf-tag--vegan' ),
		'_ysf_vegetarian' => array( 'vegetarian', 'ysf-tag--vegan' ),
		'_ysf_glutenfree' => array( 'glutenfree', '' ),
		'_ysf_spicy'      => array( 'spicy', 'ysf-tag--spicy' ),
	);

	foreach ( $flags as $meta_key => $info ) {
		if ( get_post_meta( $post_id, $meta_key, true ) ) {
			$badges[] = array(
				'label' => ysf_t( $info[0] ),
				'class' => $info[1],
			);
		}
	}

	$custom = 'en' === ysf_lang()
		? ( get_post_meta( $post_id, '_ysf_badge_en', true ) ? get_post_meta( $post_id, '_ysf_badge_en', true ) : get_post_meta( $post_id, '_ysf_badge', true ) )
		: get_post_meta( $post_id, '_ysf_badge', true );

	if ( $custom ) {
		array_unshift(
			$badges,
			array(
				'label' => $custom,
				'class' => 'ysf-tag--new',
			)
		);
	}

	return $badges;
}

/**
 * Ürünün sepete eklenebilir olup olmadığı.
 *
 * @param int $post_id Ürün kimliği.
 * @return bool
 */
function ysf_is_orderable( $post_id ) {
	if ( get_post_meta( $post_id, '_ysf_sold_out', true ) ) {
		return false;
	}

	if ( ! (float) get_post_meta( $post_id, '_ysf_price', true ) ) {
		return false;
	}

	if ( ! ysf_get_option( 'ysf_orders_enabled', true ) ) {
		return false;
	}

	return (bool) get_post_meta( $post_id, '_ysf_orderable', true );
}

/**
 * Fiyat bloğunu yazdırır.
 *
 * @param int $post_id Ürün kimliği.
 */
function ysf_the_price( $post_id ) {
	$price = (float) get_post_meta( $post_id, '_ysf_price', true );
	$old   = (float) get_post_meta( $post_id, '_ysf_price_old', true );

	if ( ! $price ) {
		return;
	}

	echo '<span class="ysf-price">';
	if ( $old > $price ) {
		echo '<del>' . esc_html( ysf_price( $old ) ) . '</del>';
	}
	echo esc_html( ysf_price( $price ) );
	echo '</span>';
}

/**
 * Sepete ekle butonu.
 *
 * @param int $post_id Ürün kimliği.
 */
function ysf_add_to_cart_button( $post_id ) {
	if ( ! ysf_is_orderable( $post_id ) ) {
		if ( get_post_meta( $post_id, '_ysf_sold_out', true ) ) {
			printf( '<span class="ysf-tag ysf-tag--spicy">%s</span>', esc_html( ysf_t( 'sold_out' ) ) );
		}
		return;
	}

	printf(
		'<button type="button" class="ysf-btn ysf-btn--sm ysf-add" data-id="%1$d" data-name="%2$s" data-price="%3$s">%4$s</button>',
		(int) $post_id,
		esc_attr( ysf_field( $post_id, 'title' ) ),
		esc_attr( (float) get_post_meta( $post_id, '_ysf_price', true ) ),
		esc_html( ysf_t( 'add_to_cart' ) )
	);
}

/**
 * Aktif dildeki gövde metnini yazdırır.
 *
 * Metin panelde kaydedilirken wp_kses_post ile temizlendiği için burada
 * çıktı, çekirdeğin the_content davranışıyla aynı şekilde basılır.
 *
 * @param int $post_id Gönderi kimliği.
 */
function ysf_the_translated_content( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$content = ysf_field( $post_id, 'content' );

	if ( ! trim( wp_strip_all_tags( $content ) ) ) {
		return;
	}

	echo apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Sayfada gösterilecek çevrilmiş içerik var mı?
 *
 * @param int $post_id Gönderi kimliği.
 * @return bool
 */
function ysf_has_translated_content( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	return (bool) trim( wp_strip_all_tags( ysf_field( $post_id, 'content' ) ) );
}

/**
 * Sayfalama.
 */
function ysf_pagination() {
	$links = paginate_links(
		array(
			'prev_text' => '‹',
			'next_text' => '›',
			'type'      => 'array',
		)
	);

	if ( empty( $links ) ) {
		return;
	}

	echo '<nav class="ysf-pagination" aria-label="' . esc_attr__( 'Sayfalama', 'ysffoodlab' ) . '">';
	foreach ( $links as $link ) {
		echo wp_kses_post( $link );
	}
	echo '</nav>';
}

/**
 * Basit ekmek kırıntısı.
 */
function ysf_breadcrumb() {
	if ( is_front_page() ) {
		return;
	}

	echo '<nav class="ysf-breadcrumb" aria-label="' . esc_attr__( 'Sayfa yolu', 'ysffoodlab' ) . '">';
	printf( '<a href="%1$s">%2$s</a>', esc_url( ysf_localize_url( home_url( '/' ) ) ), esc_html( ysf_t( 'nav_home' ) ) );

	if ( is_singular() ) {
		$post_id = get_queried_object_id();
		$parent  = wp_get_post_parent_id( $post_id );

		if ( $parent ) {
			printf(
				' <span aria-hidden="true">/</span> <a href="%1$s">%2$s</a>',
				esc_url( ysf_localize_url( get_permalink( $parent ) ) ),
				esc_html( ysf_field( $parent, 'title' ) )
			);
		}

		printf( ' <span aria-hidden="true">/</span> %s', esc_html( ysf_field( $post_id, 'title' ) ) );
	} elseif ( is_archive() ) {
		printf( ' <span aria-hidden="true">/</span> %s', esc_html( wp_strip_all_tags( get_the_archive_title() ) ) );
	} elseif ( is_search() ) {
		printf( ' <span aria-hidden="true">/</span> %s', esc_html( ysf_t( 'search_results' ) ) );
	}

	echo '</nav>';
}

/**
 * Sosyal medya bağlantıları ve SVG ikonları.
 *
 * @return array
 */
function ysf_social_links() {
	$icons = array(
		'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2zm0 5.1a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4zm0 7.7a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm6-7.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0z"/></svg>',
		'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.3-1.5 1.6-1.5h1.6V3.6c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.5-4 4.1v2.3H7.9V13h2.4v8h3.2z"/></svg>',
		'x'         => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.5 3h3.2l-7 8 7.3 10h-5.6l-4.4-6-5 6H2.8l7.3-8.6L3.1 3h5.7l4.1 5.6L17.5 3zm-1.1 16h1.7L7.5 4.8H5.7L16.4 19z"/></svg>',
		'youtube'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.6 7.2c-.2-1-.9-1.7-1.9-1.9C17.9 5 12 5 12 5s-5.9 0-7.7.3c-1 .2-1.7.9-1.9 1.9C2.1 9 2.1 12 2.1 12s0 3 .3 4.8c.2 1 .9 1.7 1.9 1.9C6.1 19 12 19 12 19s5.9 0 7.7-.3c1-.2 1.7-.9 1.9-1.9.3-1.8.3-4.8.3-4.8s0-3-.3-4.8zM10 15.1V8.9l5.3 3.1-5.3 3.1z"/></svg>',
		'tripadvisor' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6c-2.5 0-4.8.7-6.6 1.9H2l1.6 1.8A4.9 4.9 0 0 0 7 18a4.8 4.8 0 0 0 3.4-1.4L12 18.4l1.6-1.8A4.8 4.8 0 0 0 17 18a4.9 4.9 0 0 0 3.4-8.3L22 7.9h-3.4A11.7 11.7 0 0 0 12 6zM7 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm10 0a3 3 0 1 1 0-6 3 3 0 0 1 0 6zM7 12.1a1.1 1.1 0 1 0 2.2 0 1.1 1.1 0 0 0-2.2 0zm8.9 0a1.1 1.1 0 1 0 2.2 0 1.1 1.1 0 0 0-2.2 0z"/></svg>',
	);

	$links = array();

	foreach ( $icons as $key => $svg ) {
		$url = ysf_get_option( 'ysf_social_' . $key, '' );
		if ( $url ) {
			$links[ $key ] = array(
				'url'   => $url,
				'icon'  => $svg,
				'label' => ucfirst( $key ),
			);
		}
	}

	return $links;
}

/**
 * WhatsApp ve telefon SVG ikonları.
 *
 * @param string $name Ikon adı.
 * @return string
 */
function ysf_icon( $name ) {
	$icons = array(
		'whatsapp' => '<path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.2 1.2-1.7 1.2-.5.1-1 .1-1.7-.1-1.4-.5-3-1.6-4.3-3.4-.9-1.2-1.4-2.4-1.5-3.2 0-.7.3-1.4.7-1.8.2-.2.4-.3.6-.3h.5c.2 0 .4 0 .5.4l.7 1.6c.1.2 0 .4-.1.5l-.4.5c-.1.2-.2.3-.1.5.3.6.8 1.3 1.4 1.8.5.4 1 .7 1.4.8.2.1.4 0 .5-.1l.6-.6c.1-.2.3-.2.5-.1l1.5.8c.2.1.3.2.3.4 0 .2 0 .6-.1.9z"/>',
		'phone'    => '<path d="M6.6 3h3l1.5 3.8-2 1.4a11.4 11.4 0 0 0 5.7 5.7l1.4-2L20 13.4v3c0 .9-.7 1.6-1.6 1.6A15 15 0 0 1 3.4 3.6C3.4 3.3 4.1 3 6.6 3z"/>',
		'pin'      => '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>',
		'clock'    => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 11h-5v-2h3V6h2v7z"/>',
		'mail'     => '<path d="M3 5h18v14H3V5zm2 2v.4l7 4.4 7-4.4V7H5zm0 2.8V17h14V9.8l-7 4.4-7-4.4z"/>',
		'cart'     => '<path d="M7 4h-3v2h2l3 8h9l3-7H7.5L7 4zm2 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>',
		'user'     => '<path d="M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm0 10c-4 0-7 2-7 4.5V21h14v-3.5c0-2.5-3-4.5-7-4.5z"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	// width/height verilmezse SVG mevcut alani doldurur; hesap simgesi
	// masaustu menude header'i kaplayan dev bir siluete donusuyordu.
	return '<svg class="ysf-icon" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">' . $icons[ $name ] . '</svg>';
}
