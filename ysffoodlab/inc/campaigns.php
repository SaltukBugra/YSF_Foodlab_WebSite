<?php
/**
 * Kampanyalar: yönetim ekranı, tarih aralığı ve sepet indirimi.
 *
 * Dört senaryo vardır. Tarih aralığında fiyat uygulanır. Süre bitince
 * kayıt silinmez; duyuru şeridinde ve ana sayfada "Süresi doldu" olarak kalır.
 * Yönetim WordPress panelinde ve Hesabım sayfasında yönetici hesabına açıktır.
 * Silme yalnızca site yöneticisine açıktır.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Senaryo adları.
 *
 * @return array
 */
function ysf_campaign_scenarios() {
	return array(
		'direct'  => __( 'Seçili ürünlerde doğrudan indirim', 'ysffoodlab' ),
		'qty'     => __( 'Seçili ürünlerde ücret eşiği geçilince indirim', 'ysffoodlab' ),
		'bundle'  => __( 'Seçilen ürünler birlikte alınınca girilen toplam', 'ysffoodlab' ),
		'general' => __( 'Genel kampanya', 'ysffoodlab' ),
	);
}

/**
 * Kısa senaryo adı.
 *
 * @param string $scenario Senaryo anahtarı.
 * @return string
 */
function ysf_campaign_scenario_label( $scenario ) {
	$labels = array(
		'direct'  => __( 'Doğrudan indirim', 'ysffoodlab' ),
		'qty'     => __( 'Ücret eşiği', 'ysffoodlab' ),
		'bundle'  => __( 'Birlikte toplam', 'ysffoodlab' ),
		'general' => __( 'Genel kampanya', 'ysffoodlab' ),
	);

	return isset( $labels[ $scenario ] ) ? $labels[ $scenario ] : __( 'Duyuru', 'ysffoodlab' );
}

/**
 * Parayı kuruşa yuvarlar.
 *
 * @param float $amount Tutar.
 * @return float
 */
function ysf_campaign_money( $amount ) {
	return round( (float) $amount, 2 );
}

/**
 * Saat değerini HH:MM biçimine çevirir.
 *
 * @param mixed $value Gelen saat.
 * @return string
 */
function ysf_campaign_normalize_time( $value ) {
	$value = trim( (string) $value );

	if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', $value, $match ) ) {
		return '';
	}

	$hour   = (int) $match[1];
	$minute = (int) $match[2];

	if ( $hour > 23 || $minute > 59 ) {
		return '';
	}

	return sprintf( '%02d:%02d', $hour, $minute );
}

/**
 * Gün içi saat aralığı. İkisi de boşsa gün boyu geçerlidir.
 *
 * @param int $post_id Kampanya.
 * @return array{start:string,end:string}|null
 */
function ysf_campaign_time_bounds( $post_id ) {
	$start = ysf_campaign_normalize_time( get_post_meta( $post_id, '_ysf_time_start', true ) );
	$end   = ysf_campaign_normalize_time( get_post_meta( $post_id, '_ysf_time_end', true ) );

	if ( '' === $start || '' === $end ) {
		return null;
	}

	return array(
		'start' => $start,
		'end'   => $end,
	);
}

/**
 * Sitenin yerel saati, HH:MM.
 *
 * @return string
 */
function ysf_campaign_now_hm() {
	return current_time( 'H:i' );
}

/**
 * Şu an gün içi saat aralığında mı. Aralık yoksa gün boyu kabul edilir.
 *
 * @param int $post_id Kampanya.
 * @return bool
 */
function ysf_campaign_in_window( $post_id ) {
	$bounds = ysf_campaign_time_bounds( $post_id );

	if ( ! $bounds ) {
		return true;
	}

	$now = ysf_campaign_now_hm();

	return $now >= $bounds['start'] && $now <= $bounds['end'];
}

/**
 * Kampanya bitmiş mi. Saat varsa son gün aralık bitince de biter.
 *
 * @param int $post_id Kampanya.
 * @return bool
 */
function ysf_campaign_is_expired( $post_id ) {
	$end = (string) get_post_meta( $post_id, '_ysf_end', true );

	if ( '' === $end ) {
		return false;
	}

	$today = current_time( 'Y-m-d' );

	if ( $end < $today ) {
		return true;
	}

	if ( $end > $today ) {
		return false;
	}

	$bounds = ysf_campaign_time_bounds( $post_id );

	return $bounds && ysf_campaign_now_hm() > $bounds['end'];
}

/**
 * Kampanya başlangıcı gelmiş mi.
 *
 * @param int $post_id Kampanya.
 * @return bool
 */
function ysf_campaign_has_started( $post_id ) {
	$start = (string) get_post_meta( $post_id, '_ysf_start', true );

	return '' === $start || $start <= current_time( 'Y-m-d' );
}

/**
 * Kampanya bu tarih aralığında uygulanıyor mu.
 *
 * @param int $post_id Kampanya.
 * @return bool
 */
function ysf_campaign_is_active( $post_id ) {
	return ysf_campaign_has_started( $post_id ) && ! ysf_campaign_is_expired( $post_id ) && ysf_campaign_in_window( $post_id );
}

/**
 * Saat aralığı bugün kapandı ve kampanya ertesi gün de sürüyor.
 *
 * @param int $post_id Kampanya.
 * @return bool
 */
function ysf_campaign_awaits_tomorrow( $post_id ) {
	if ( ! ysf_campaign_has_started( $post_id ) || ysf_campaign_is_expired( $post_id ) ) {
		return false;
	}

	$bounds = ysf_campaign_time_bounds( $post_id );
	$end    = (string) get_post_meta( $post_id, '_ysf_end', true );

	if ( ! $bounds || '' === $end || $end <= current_time( 'Y-m-d' ) ) {
		return false;
	}

	return ysf_campaign_now_hm() > $bounds['end'];
}

/**
 * Saat aralığı henüz başlamadı ama kampanya tarih aralığında.
 *
 * @param int $post_id Kampanya.
 * @return bool
 */
function ysf_campaign_before_window( $post_id ) {
	if ( ! ysf_campaign_has_started( $post_id ) || ysf_campaign_is_expired( $post_id ) ) {
		return false;
	}

	$bounds = ysf_campaign_time_bounds( $post_id );

	if ( ! $bounds ) {
		return false;
	}

	return ysf_campaign_now_hm() < $bounds['start'];
}

/**
 * Kampanyanın ürün kimlikleri.
 *
 * @param int $post_id Kampanya.
 * @return int[]
 */
function ysf_campaign_product_ids( $post_id ) {
	$raw = get_post_meta( $post_id, '_ysf_products', true );

	if ( ! is_array( $raw ) ) {
		return array();
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );
}

/**
 * Birim fiyata yüzde ya da tutar indirimi uygular.
 *
 * @param float  $base  Liste fiyatı.
 * @param string $kind  percent|fixed.
 * @param float  $value İndirim.
 * @return float
 */
function ysf_campaign_discount_unit( $base, $kind, $value ) {
	$base  = (float) $base;
	$value = (float) $value;

	if ( 'percent' === $kind ) {
		$value = min( 100, max( 0, $value ) );

		return ysf_campaign_money( $base * ( 1 - ( $value / 100 ) ) );
	}

	return ysf_campaign_money( max( 0, $base - $value ) );
}

/**
 * Tarih aralığındaki fiyat kuralları.
 *
 * @return array
 */
function ysf_active_campaign_rules() {
	static $rules = null;

	if ( null !== $rules ) {
		return $rules;
	}

	$rules = array();
	$posts = get_posts(
		array(
			'post_type'      => 'ysf_campaign',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		)
	);

	foreach ( $posts as $post ) {
		if ( ! ysf_campaign_is_active( $post->ID ) ) {
			continue;
		}

		$scenario = (string) get_post_meta( $post->ID, '_ysf_scenario', true );

		if ( ! in_array( $scenario, array( 'direct', 'qty', 'bundle' ), true ) ) {
			continue;
		}

		$products = ysf_campaign_product_ids( $post->ID );

		if ( count( $products ) < ( 'bundle' === $scenario ? 2 : 1 ) ) {
			continue;
		}

		$rules[] = array(
			'id'       => (int) $post->ID,
			'scenario' => $scenario,
			'products' => $products,
			'kind'     => (string) get_post_meta( $post->ID, '_ysf_discount_kind', true ),
			'value'    => (float) get_post_meta( $post->ID, '_ysf_discount_value', true ),
			'minSpend' => (float) get_post_meta( $post->ID, '_ysf_min_spend', true ),
			'bundle'   => (float) get_post_meta( $post->ID, '_ysf_bundle_total', true ),
			'label'    => ysf_campaign_public_label( $post->ID ),
		);
	}

	return $rules;
}

/**
 * Sitede görünen kısa kampanya yazısı.
 *
 * @param int $post_id Kampanya.
 * @return string
 */
function ysf_campaign_public_label( $post_id ) {
	if ( 'en' === ysf_lang() ) {
		$en = (string) get_post_meta( $post_id, '_ysf_badge_en', true );

		if ( '' !== $en ) {
			return $en;
		}
	}

	$badge = (string) get_post_meta( $post_id, '_ysf_badge', true );

	return '' !== $badge ? $badge : get_the_title( $post_id );
}

/**
 * Ürünün doğrudan indirimli birim fiyatı. Adet ve birlikte alım sepette hesaplanır.
 *
 * @param int   $product_id Ürün.
 * @param float $base       Liste fiyatı.
 * @return float
 */
function ysf_campaign_unit_price( $product_id, $base ) {
	$best = (float) $base;

	foreach ( ysf_active_campaign_rules() as $rule ) {
		if ( 'direct' !== $rule['scenario'] || ! in_array( (int) $product_id, $rule['products'], true ) ) {
			continue;
		}

		$next = ysf_campaign_discount_unit( $base, $rule['kind'], $rule['value'] );

		if ( $next < $best ) {
			$best = $next;
		}
	}

	return $best;
}

/**
 * Ürünü kapsayan aktif kampanya yazıları.
 *
 * @param int $product_id Ürün.
 * @return string[]
 */
function ysf_product_campaign_labels( $product_id ) {
	$labels = array();

	foreach ( ysf_active_campaign_rules() as $rule ) {
		if ( in_array( (int) $product_id, $rule['products'], true ) && '' !== $rule['label'] ) {
			$labels[ $rule['label'] ] = $rule['label'];
		}
	}

	return array_values( $labels );
}

/**
 * Ürün kartındaki kampanya notu.
 *
 * @param int $post_id Ürün.
 */
function ysf_the_campaign_note( $post_id ) {
	$labels = ysf_product_campaign_labels( $post_id );

	if ( ! $labels ) {
		return;
	}

	printf( '<p class="ysf-campaign-hint">%s</p>', esc_html( implode( ' · ', $labels ) ) );
}

/**
 * Sepet kalemlerine kampanya fiyatını yazar.
 *
 * Her kalemde price liste fiyatıdır. Dönüşte price tahsil edilen birim fiyat,
 * base liste fiyatıdır.
 *
 * @param array $lines Kalemler.
 * @return array
 */
function ysf_apply_campaign_prices( $lines ) {
	$rules = ysf_active_campaign_rules();

	if ( ! $rules || ! is_array( $lines ) ) {
		return is_array( $lines ) ? $lines : array();
	}

	foreach ( $lines as $index => $line ) {
		$base                         = isset( $line['price'] ) ? (float) $line['price'] : 0;
		$lines[ $index ]['base']      = $base;
		$lines[ $index ]['price']     = $base;
		$lines[ $index ]['offer']     = '';
		$lines[ $index ]['bundled']   = false;
	}

	foreach ( $lines as $index => $line ) {
		$best  = (float) $line['base'];
		$label = '';

		foreach ( $rules as $rule ) {
			if ( ! in_array( (int) $line['id'], $rule['products'], true ) ) {
				continue;
			}

			if ( ! in_array( $rule['scenario'], array( 'direct', 'qty' ), true ) ) {
				continue;
			}

			if ( 'qty' === $rule['scenario'] ) {
				$sum = 0.0;

				foreach ( $lines as $other ) {
					if ( in_array( (int) $other['id'], $rule['products'], true ) ) {
						$base = isset( $other['base'] ) ? (float) $other['base'] : (float) $other['price'];
						$sum += $base * (int) $other['qty'];
					}
				}

				if ( $sum <= (float) $rule['minSpend'] + 0.001 ) {
					continue;
				}
			}

			$priced = ysf_campaign_discount_unit( $line['base'], $rule['kind'], $rule['value'] );

			if ( $priced < $best - 0.001 ) {
				$best  = $priced;
				$label = $rule['label'];
			}
		}

		$lines[ $index ]['price'] = $best;
		$lines[ $index ]['offer'] = $label;
	}

	foreach ( $rules as $rule ) {
		if ( 'bundle' !== $rule['scenario'] ) {
			continue;
		}

		$indexes = array();
		$missing = false;

		foreach ( $rule['products'] as $product_id ) {
			$found = null;

			foreach ( $lines as $index => $line ) {
				if ( null === $found && (int) $line['id'] === (int) $product_id && (int) $line['qty'] >= 1 && empty( $line['bundled'] ) ) {
					$found = $index;
				}
			}

			if ( null === $found ) {
				$missing = true;
				break;
			}

			$indexes[] = $found;
		}

		if ( $missing || count( $indexes ) < 2 ) {
			continue;
		}

		$sum = 0.0;

		foreach ( $indexes as $index ) {
			$sum += (float) $lines[ $index ]['price'];
		}

		$target = (float) $rule['bundle'];

		if ( $target <= 0 || $sum <= $target + 0.001 ) {
			continue;
		}

		$left = $target;
		$last = count( $indexes ) - 1;

		foreach ( $indexes as $position => $index ) {
			$unit = (float) $lines[ $index ]['price'];

			if ( $position === $last ) {
				$bundled_unit = ysf_campaign_money( $left );
			} else {
				$bundled_unit = ysf_campaign_money( $unit / $sum * $target );
				$left         = ysf_campaign_money( $left - $bundled_unit );
			}

			$qty  = max( 1, (int) $lines[ $index ]['qty'] );
			$rest = ( $qty - 1 ) * $unit;

			$lines[ $index ]['price']   = ysf_campaign_money( ( $bundled_unit + $rest ) / $qty );
			$lines[ $index ]['offer']   = $rule['label'];
			$lines[ $index ]['bundled'] = true;
		}
	}

	foreach ( $lines as $index => $line ) {
		unset( $lines[ $index ]['bundled'] );
	}

	return $lines;
}

/**
 * Sepetteki kampanya kazancı.
 *
 * @param array $lines ysf_apply_campaign_prices sonucu.
 * @return float
 */
function ysf_campaign_savings( $lines ) {
	$savings = 0.0;

	foreach ( $lines as $line ) {
		$base = isset( $line['base'] ) ? (float) $line['base'] : (float) $line['price'];
		$savings += ( $base - (float) $line['price'] ) * (int) $line['qty'];
	}

	return ysf_campaign_money( max( 0, $savings ) );
}

/**
 * Ön yüze giden kural listesi.
 *
 * @return array
 */
function ysf_campaign_rules_for_js() {
	$out   = array();
	$today = current_time( 'Y-m-d' );
	$posts = get_posts(
		array(
			'post_type'      => 'ysf_campaign',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		)
	);

	foreach ( $posts as $post ) {
		$start = (string) get_post_meta( $post->ID, '_ysf_start', true );
		$end   = (string) get_post_meta( $post->ID, '_ysf_end', true );

		if ( '' !== $end && $end < $today ) {
			continue;
		}

		$scenario = (string) get_post_meta( $post->ID, '_ysf_scenario', true );

		if ( ! in_array( $scenario, array( 'direct', 'qty', 'bundle' ), true ) ) {
			continue;
		}

		$products = ysf_campaign_product_ids( $post->ID );

		if ( count( $products ) < ( 'bundle' === $scenario ? 2 : 1 ) ) {
			continue;
		}

		$bounds = ysf_campaign_time_bounds( $post->ID );

		$out[] = array(
			'scenario'  => $scenario,
			'products'  => array_map( 'intval', $products ),
			'kind'      => (string) get_post_meta( $post->ID, '_ysf_discount_kind', true ),
			'value'     => (float) get_post_meta( $post->ID, '_ysf_discount_value', true ),
			'minSpend'  => (float) get_post_meta( $post->ID, '_ysf_min_spend', true ),
			'bundle'    => (float) get_post_meta( $post->ID, '_ysf_bundle_total', true ),
			'label'     => ysf_campaign_public_label( $post->ID ),
			'start'     => $start,
			'end'       => $end,
			'timeStart' => $bounds ? $bounds['start'] : '',
			'timeEnd'   => $bounds ? $bounds['end'] : '',
		);
	}

	return $out;
}

/**
 * Kampanya kaydı değişince önbelleği boşaltır.
 */
function ysf_campaign_purge_cache() {
	do_action( 'litespeed_purge_all' );
}

/**
 * Rozet metinleri.
 *
 * @param string $scenario Senaryo.
 * @param string $kind     percent|fixed.
 * @param float  $value    İndirim.
 * @param int    $min      Adet.
 * @param float  $bundle   Birlikte toplam.
 * @return array TR, EN.
 */
function ysf_campaign_badge_pair( $scenario, $kind, $value, $min, $bundle ) {
	$number = ysf_campaign_number( $value );

	if ( 'direct' === $scenario && 'percent' === $kind ) {
		return array( '%' . $number . ' indirim', $number . '% off' );
	}

	if ( 'direct' === $scenario ) {
		return array( ysf_price( $value ) . ' indirim', ysf_price( $value ) . ' off' );
	}

	if ( 'qty' === $scenario && 'percent' === $kind ) {
		return array(
			sprintf( '%1$s geçince %%%2$s', ysf_price( $min ), $number ),
			sprintf( 'Over %1$s: %2$s%% off', ysf_price( $min ), $number ),
		);
	}

	if ( 'qty' === $scenario ) {
		return array(
			sprintf( '%1$s geçince %2$s indirim', ysf_price( $min ), ysf_price( $value ) ),
			sprintf( 'Over %1$s: %2$s off', ysf_price( $min ), ysf_price( $value ) ),
		);
	}

	if ( 'bundle' === $scenario ) {
		return array(
			sprintf( 'Birlikte %s', ysf_price( $bundle ) ),
			sprintf( 'Together %s', ysf_price( $bundle ) ),
		);
	}

	return array( __( 'Kampanya', 'ysffoodlab' ), 'Offer' );
}

/**
 * Gereksiz sıfırı atılmış sayı.
 *
 * @param float $number Sayı.
 * @return string
 */
function ysf_campaign_number( $number ) {
	$number = round( (float) $number, 2 );

	if ( abs( $number - round( $number ) ) < 0.001 ) {
		return (string) (int) round( $number );
	}

	return (string) $number;
}

/**
 * Yönetim menüsü.
 */
function ysf_campaigns_admin_menu() {
	add_menu_page(
		__( 'Kampanyalar', 'ysffoodlab' ),
		__( 'Kampanyalar', 'ysffoodlab' ),
		'manage_options',
		'ysf-campaigns',
		'ysf_campaigns_admin_page',
		'dashicons-megaphone',
		6.4
	);
}
add_action( 'admin_menu', 'ysf_campaigns_admin_menu', 20 );

/**
 * Kampanya ekranı.
 */
function ysf_campaigns_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu sayfayı yalnız site yöneticisi kullanabilir.', 'ysffoodlab' ) );
	}

	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	echo '<div class="wrap ysf-campaign-admin">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Kampanyalar', 'ysffoodlab' ) . '</h1>';

	if ( 'new' !== $action ) {
		echo ' <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=ysf-campaigns&action=new' ) ) . '">' . esc_html__( 'Yeni kampanya', 'ysffoodlab' ) . '</a>';
	}

	echo '<hr class="wp-header-end">';

	$notice = isset( $_GET['ysf_notice'] ) ? sanitize_key( wp_unslash( $_GET['ysf_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'saved' === $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Kampanya kaydedildi. Duyuru şeridinde ve ana sayfada tarih aralığında görünür.', 'ysffoodlab' ) . '</p></div>';
	} elseif ( 'deleted' === $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Kampanya silindi.', 'ysffoodlab' ) . '</p></div>';
	}

	if ( 'new' === $action || 'edit' === $action ) {
		ysf_campaigns_admin_form();
	} else {
		ysf_campaigns_admin_list();
	}

	echo '</div>';
}

/**
 * Kayıt listesi.
 */
function ysf_campaigns_admin_list() {
	$posts = get_posts(
		array(
			'post_type'      => 'ysf_campaign',
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => 100,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	echo '<div class="ysf-admin-note">';
	echo '<h2>' . esc_html__( 'Dört senaryo', 'ysffoodlab' ) . '</h2>';
	echo '<p>' . esc_html__( 'Doğrudan indirim seçili ürünün fiyatını hemen düşürür.', 'ysffoodlab' ) . '</p>';
	echo '<p>' . esc_html__( 'Ücret eşiği, seçili ürünlerin sepetteki toplam tutarı girdiğiniz değeri geçince uygulanır.', 'ysffoodlab' ) . '</p>';
	echo '<p>' . esc_html__( 'Birlikte toplam, seçilen ürünlerin hepsi sepetteyken o ürünlerin birer adedinin toplamını girdiğiniz tutara çeker.', 'ysffoodlab' ) . '</p>';
	echo '<p>' . esc_html__( 'Genel kampanya fiyat değiştirmez, yalnızca duyuru olarak görünür.', 'ysffoodlab' ) . '</p>';
	echo '<p>' . esc_html__( 'Kampanya başlangıç ve bitiş tarihleri arasında geçerlidir. İsterseniz her gün için bir saat aralığı da girilir; boş bırakılırsa gün boyu geçerlidir. Aralık kapandıktan sonra kampanya ertesi güne sarkıyorsa duyuruda “Yarın gene bekleriz” görünür ve indirim uygulanmaz. Süre bitince silinmez; “Süresi doldu” yazısıyla kalır. Silme yalnız yönetici hesabıyla yapılır.', 'ysffoodlab' ) . '</p>';
	echo '</div>';

	echo '<table class="widefat striped"><thead><tr>';
	echo '<th>' . esc_html__( 'Kampanya', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'Senaryo', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'Tarih', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'Durum', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'İşlem', 'ysffoodlab' ) . '</th>';
	echo '</tr></thead><tbody>';

	if ( ! $posts ) {
		echo '<tr><td colspan="5">' . esc_html__( 'Henüz kampanya yok.', 'ysffoodlab' ) . '</td></tr>';
	}

	foreach ( $posts as $post ) {
		if ( function_exists( 'ysf_is_duyuru' ) && ysf_is_duyuru( $post->ID ) ) {
			continue;
		}

		$scenario = (string) get_post_meta( $post->ID, '_ysf_scenario', true );
		$start    = (string) get_post_meta( $post->ID, '_ysf_start', true );
		$end      = (string) get_post_meta( $post->ID, '_ysf_end', true );
		$bounds   = ysf_campaign_time_bounds( $post->ID );
		$when     = trim( $start . ' — ' . $end, ' —' );

		if ( $bounds ) {
			$when = trim( $start . ' ' . $bounds['start'] . ' — ' . $end . ' ' . $bounds['end'] );
		}

		$status   = ysf_campaign_admin_status( $post->ID );
		$edit     = admin_url( 'admin.php?page=ysf-campaigns&action=edit&id=' . (int) $post->ID );

		echo '<tr>';
		echo '<td><strong><a href="' . esc_url( $edit ) . '">' . esc_html( get_the_title( $post ) ) . '</a></strong></td>';
		echo '<td>' . esc_html( ysf_campaign_scenario_label( $scenario ) ) . '</td>';
		echo '<td>' . esc_html( $when ) . '</td>';
		echo '<td><span class="' . esc_attr( $status['class'] ) . '">' . esc_html( $status['label'] ) . '</span></td>';
		echo '<td>';
		echo '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Düzenle', 'ysffoodlab' ) . '</a>';

		if ( current_user_can( 'manage_options' ) ) {
			echo ' · ';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline" onsubmit="return confirm(\'' . esc_js( __( 'Bu kampanya kalıcı olarak silinsin mi?', 'ysffoodlab' ) ) . '\');">';
			wp_nonce_field( 'ysf_delete_campaign_' . (int) $post->ID );
			echo '<input type="hidden" name="action" value="ysf_delete_campaign">';
			echo '<input type="hidden" name="id" value="' . (int) $post->ID . '">';
			echo '<button type="submit" class="button-link-delete">' . esc_html__( 'Sil', 'ysffoodlab' ) . '</button>';
			echo '</form>';
		}

		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * Liste durumu.
 *
 * @param int $post_id Kampanya.
 * @return array
 */
function ysf_campaign_admin_status( $post_id ) {
	if ( ! ysf_campaign_has_started( $post_id ) ) {
		return array(
			'label' => __( 'Başlamadı', 'ysffoodlab' ),
			'class' => 'ysf-badge-pending',
		);
	}

	if ( ysf_campaign_is_expired( $post_id ) ) {
		return array(
			'label' => __( 'Süresi doldu', 'ysffoodlab' ),
			'class' => 'ysf-badge-expired',
		);
	}

	if ( ysf_campaign_awaits_tomorrow( $post_id ) ) {
		return array(
			'label' => __( 'Yarın gene bekleriz', 'ysffoodlab' ),
			'class' => 'ysf-badge-pending',
		);
	}

	if ( ! ysf_campaign_in_window( $post_id ) ) {
		return array(
			'label' => __( 'Saat aralığı dışında', 'ysffoodlab' ),
			'class' => 'ysf-badge-pending',
		);
	}

	return array(
		'label' => __( 'Aktif', 'ysffoodlab' ),
		'class' => 'ysf-badge-done',
	);
}

/**
 * Ekleme ve düzenleme formu.
 */
function ysf_campaigns_admin_form() {
	$id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post  = $id ? get_post( $id ) : null;
	$error = get_transient( 'ysf_campaign_error_' . get_current_user_id() );
	$old   = get_transient( 'ysf_campaign_old_' . get_current_user_id() );

	delete_transient( 'ysf_campaign_error_' . get_current_user_id() );
	delete_transient( 'ysf_campaign_old_' . get_current_user_id() );

	if ( $id && ( ! $post || 'ysf_campaign' !== $post->post_type ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Kampanya bulunamadı.', 'ysffoodlab' ) . '</p></div>';
		return;
	}

	if ( $error ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
	}

	$scenario = $old ? (string) $old['scenario'] : (string) get_post_meta( $id, '_ysf_scenario', true );
	$scenario = isset( ysf_campaign_scenarios()[ $scenario ] ) ? $scenario : 'direct';
	$kind     = $old ? (string) $old['kind'] : (string) get_post_meta( $id, '_ysf_discount_kind', true );
	$kind     = 'fixed' === $kind ? 'fixed' : 'percent';
	$value    = $old ? $old['value'] : get_post_meta( $id, '_ysf_discount_value', true );
	$min      = $old ? $old['min'] : get_post_meta( $id, '_ysf_min_spend', true );
	$bundle   = $old ? $old['bundle'] : get_post_meta( $id, '_ysf_bundle_total', true );
	$start    = $old ? (string) $old['start'] : (string) get_post_meta( $id, '_ysf_start', true );
	$end      = $old ? (string) $old['end'] : (string) get_post_meta( $id, '_ysf_end', true );
	$time_start = ( $old && isset( $old['time_start'] ) ) ? (string) $old['time_start'] : (string) get_post_meta( $id, '_ysf_time_start', true );
	$time_end   = ( $old && isset( $old['time_end'] ) ) ? (string) $old['time_end'] : (string) get_post_meta( $id, '_ysf_time_end', true );
	$title    = $old ? (string) $old['title'] : ( $post ? $post->post_title : '' );
	$excerpt  = $old ? (string) $old['excerpt'] : ( $post ? $post->post_excerpt : '' );
	$title_en = $old ? (string) $old['title_en'] : (string) get_post_meta( $id, '_ysf_title_en', true );
	$excerpt_en = $old ? (string) $old['excerpt_en'] : (string) get_post_meta( $id, '_ysf_excerpt_en', true );
	$selected = $old && isset( $old['products'] ) ? array_map( 'absint', (array) $old['products'] ) : ysf_campaign_product_ids( $id );

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="ysf-campaign-form" enctype="multipart/form-data">';
	wp_nonce_field( 'ysf_save_campaign' );
	echo '<input type="hidden" name="action" value="ysf_save_campaign">';
	echo '<input type="hidden" name="id" value="' . (int) $id . '">';

	echo '<div class="ysf-meta-grid">';
	ysf_campaign_field_open( 'title', true );
	echo '<label for="ysf-camp-title">' . esc_html__( 'Kampanya adı', 'ysffoodlab' ) . '</label>';
	echo '<input type="text" id="ysf-camp-title" name="title" required value="' . esc_attr( $title ) . '">';
	ysf_campaign_field_close();

	ysf_campaign_field_open( 'scenario', true );
	echo '<label for="ysf-scenario">' . esc_html__( 'Senaryo', 'ysffoodlab' ) . '</label>';
	echo '<select id="ysf-scenario" name="scenario">';
	foreach ( ysf_campaign_scenarios() as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $scenario, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
	ysf_campaign_field_close();

	ysf_campaign_field_open( 'excerpt', true );
	echo '<label for="ysf-camp-excerpt">' . esc_html__( 'Kısa açıklama', 'ysffoodlab' ) . '</label>';
	echo '<textarea id="ysf-camp-excerpt" name="excerpt" rows="3">' . esc_textarea( $excerpt ) . '</textarea>';
	ysf_campaign_field_close();

	ysf_campaign_field_open( 'title_en', false );
	echo '<label for="ysf-camp-title-en">' . esc_html__( 'İngilizce ad', 'ysffoodlab' ) . '</label>';
	echo '<input type="text" id="ysf-camp-title-en" name="title_en" value="' . esc_attr( $title_en ) . '">';
	ysf_campaign_field_close();

	ysf_campaign_field_open( 'excerpt_en', false );
	echo '<label for="ysf-camp-excerpt-en">' . esc_html__( 'İngilizce açıklama', 'ysffoodlab' ) . '</label>';
	echo '<textarea id="ysf-camp-excerpt-en" name="excerpt_en" rows="2">' . esc_textarea( $excerpt_en ) . '</textarea>';
	ysf_campaign_field_close();

	echo '<div class="ysf-meta-full" data-ysf-camp="products">';
	echo '<span class="ysf-camp-label">' . esc_html__( 'Ürünler', 'ysffoodlab' ) . '</span>';
	echo '<div class="ysf-camp-products">';
	ysf_campaign_product_checklist( $selected );
	echo '</div></div>';

	echo '<div data-ysf-camp="discount">';
	echo '<label for="ysf-camp-kind">' . esc_html__( 'İndirim türü', 'ysffoodlab' ) . '</label>';
	echo '<select id="ysf-camp-kind" name="kind">';
	echo '<option value="percent" ' . selected( $kind, 'percent', false ) . '>' . esc_html__( 'Yüzde', 'ysffoodlab' ) . '</option>';
	echo '<option value="fixed" ' . selected( $kind, 'fixed', false ) . '>' . esc_html__( 'Tutar', 'ysffoodlab' ) . '</option>';
	echo '</select></div>';

	echo '<div data-ysf-camp="discount">';
	echo '<label for="ysf-camp-value">' . esc_html__( 'İndirim değeri', 'ysffoodlab' ) . '</label>';
	echo '<input type="number" id="ysf-camp-value" name="value" min="0" step="0.01" value="' . esc_attr( (string) $value ) . '">';
	echo '</div>';

	echo '<div class="ysf-meta-full" data-ysf-camp="min">';
	echo '<label for="ysf-camp-min">' . esc_html__( 'Ücret eşiği', 'ysffoodlab' ) . '</label>';
	echo '<input type="number" id="ysf-camp-min" name="min" min="0" step="0.01" value="' . esc_attr( (string) $min ) . '">';
	echo '<p class="description">' . esc_html__( 'Seçili ürünlerin sepetteki toplam tutarı bu değeri geçince indirim uygulanır.', 'ysffoodlab' ) . '</p>';
	echo '</div>';

	echo '<div class="ysf-meta-full" data-ysf-camp="bundle">';
	echo '<label for="ysf-camp-bundle">' . esc_html__( 'Birlikte toplam tutar', 'ysffoodlab' ) . '</label>';
	echo '<input type="number" id="ysf-camp-bundle" name="bundle" min="0" step="0.01" value="' . esc_attr( (string) $bundle ) . '">';
	echo '<p class="description">' . esc_html__( 'Seçilen ürünlerin hepsi sepetteyken birer adedinin toplamı bu tutar olur. Fazla adetler kendi fiyatından kalır.', 'ysffoodlab' ) . '</p>';
	echo '</div>';

	ysf_campaign_field_open( 'start', false );
	echo '<label for="ysf-camp-start">' . esc_html__( 'Başlangıç', 'ysffoodlab' ) . '</label>';
	echo '<input type="date" id="ysf-camp-start" name="start" required value="' . esc_attr( $start ) . '">';
	ysf_campaign_field_close();

	ysf_campaign_field_open( 'end', false );
	echo '<label for="ysf-camp-end">' . esc_html__( 'Bitiş', 'ysffoodlab' ) . '</label>';
	echo '<input type="date" id="ysf-camp-end" name="end" required value="' . esc_attr( $end ) . '">';
	echo '<p class="description">' . esc_html__( 'Bu gün dahil geçerlidir. Ertesi gün “Süresi doldu” olarak kalır.', 'ysffoodlab' ) . '</p>';
	ysf_campaign_field_close();

	ysf_campaign_field_open( 'time', false );
	echo '<label for="ysf-camp-time-start">' . esc_html__( 'Saat başlangıcı', 'ysffoodlab' ) . '</label>';
	echo '<input type="time" id="ysf-camp-time-start" name="time_start" value="' . esc_attr( $time_start ) . '">';
	ysf_campaign_field_close();

	ysf_campaign_field_open( 'time', false );
	echo '<label for="ysf-camp-time-end">' . esc_html__( 'Saat bitişi', 'ysffoodlab' ) . '</label>';
	echo '<input type="time" id="ysf-camp-time-end" name="time_end" value="' . esc_attr( $time_end ) . '">';
	echo '<p class="description">' . esc_html__( 'İsteğe bağlı. Her gün bu saatler arasında geçerlidir. Aralık bitince kampanya ertesi güne sarkıyorsa duyuruda “Yarın gene bekleriz” görünür.', 'ysffoodlab' ) . '</p>';
	ysf_campaign_field_close();

	ysf_campaign_field_open( 'photo', true );
	echo '<label for="ysf-camp-photo">' . esc_html__( 'Kampanya görseli', 'ysffoodlab' ) . '</label>';

	if ( $id && has_post_thumbnail( $id ) ) {
		echo get_the_post_thumbnail( $id, 'thumbnail', array( 'style' => 'display:block;width:120px;height:80px;object-fit:cover;border-radius:8px;margin:0 0 8px' ) );
	}

	echo '<input type="file" id="ysf-camp-photo" name="photo" accept="image/*">';
	echo '<p class="description">' . esc_html__( 'İsteğe bağlı. Ana sayfadaki kampanya kartında görünür. Düzenlerken boş bırakırsanız mevcut görsel kalır.', 'ysffoodlab' ) . '</p>';
	ysf_campaign_field_close();

	echo '</div>';
	submit_button( $id ? __( 'Kampanyayı güncelle', 'ysffoodlab' ) : __( 'Kampanyayı yayınla', 'ysffoodlab' ) );
	echo '</form>';

	echo '<script>
		( function () {
			var scenario = document.getElementById( "ysf-scenario" );
			if ( ! scenario ) { return; }
			function sync() {
				var value = scenario.value;
				document.querySelectorAll( "[data-ysf-camp]" ).forEach( function ( node ) {
					var slot = node.getAttribute( "data-ysf-camp" );
					var show = true;
					if ( "products" === slot ) { show = "general" !== value; }
					if ( "discount" === slot ) { show = "direct" === value || "qty" === value; }
					if ( "min" === slot ) { show = "qty" === value; }
					if ( "bundle" === slot ) { show = "bundle" === value; }
					node.hidden = ! show;
				} );
			}
			scenario.addEventListener( "change", sync );
			sync();
			var search = document.querySelector( "[data-ysf-camp-product-search]" );
			if ( ! search ) { return; }
			search.addEventListener( "input", function () {
				var query = search.value.toLocaleLowerCase( "tr" ).trim();
				document.querySelectorAll( "[data-ysf-camp-group]" ).forEach( function ( group ) {
					var visible = 0;
					group.querySelectorAll( "[data-ysf-camp-product]" ).forEach( function ( row ) {
						var name = ( row.getAttribute( "data-name" ) || "" ).toLocaleLowerCase( "tr" );
						var show = ! query || name.indexOf( query ) !== -1;
						row.hidden = ! show;
						if ( show ) { visible++; }
					} );
					group.hidden = ! visible;
				} );
			} );
		}() );
	</script>';
}

/**
 * Form alanı sarmalayıcı.
 *
 * @param string $slot  Alan.
 * @param bool   $full  Tam satır.
 */
function ysf_campaign_field_open( $slot, $full ) {
	echo '<div class="' . ( $full ? 'ysf-meta-full' : '' ) . '" data-ysf-camp="' . esc_attr( $slot ) . '">';
}

/**
 * Form alanı kapanışı.
 */
function ysf_campaign_field_close() {
	echo '</div>';
}

/**
 * Kategoriye göre ürün kutuları.
 *
 * @param int[] $selected Seçili kimlikler.
 */
function ysf_campaign_product_checklist( $selected ) {
	$items = get_posts(
		array(
			'post_type'      => 'ysf_menu_item',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$terms  = get_terms(
		array(
			'taxonomy'   => 'ysf_menu_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	$groups = array();

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$groups[ $term->name ] = array();
		}
	}

	$groups[ __( 'Kategorisiz', 'ysffoodlab' ) ] = array();

	foreach ( $items as $item ) {
		$names = wp_get_post_terms( $item->ID, 'ysf_menu_cat', array( 'fields' => 'names' ) );
		$name  = ( $names && ! is_wp_error( $names ) ) ? $names[0] : __( 'Kategorisiz', 'ysffoodlab' );

		if ( ! isset( $groups[ $name ] ) ) {
			$groups[ $name ] = array();
		}

		$groups[ $name ][] = $item;
	}

	$groups = array_filter( $groups );

	if ( ! $groups ) {
		echo '<p>' . esc_html__( 'Menüde ürün yok.', 'ysffoodlab' ) . '</p>';
		return;
	}

	echo '<input type="search" class="ysf-camp-search" data-ysf-camp-product-search placeholder="' . esc_attr__( 'Ürün ara…', 'ysffoodlab' ) . '" autocomplete="off">';
	echo '<div class="ysf-camp-groups">';

	foreach ( $groups as $name => $group ) {
		echo '<section class="ysf-camp-group" data-ysf-camp-group>';
		echo '<h4 class="ysf-camp-cat">' . esc_html( $name ) . '</h4>';
		echo '<div class="ysf-camp-grid">';

		foreach ( $group as $item ) {
			$thumb = get_the_post_thumbnail_url( $item, 'thumbnail' );
			$thumb = $thumb ? $thumb : ( function_exists( 'ysf_placeholder_image' ) ? ysf_placeholder_image() : '' );
			$title = get_the_title( $item );

			echo '<label class="ysf-camp-check" data-ysf-camp-product data-name="' . esc_attr( $title ) . '">';
			echo '<input type="checkbox" name="products[]" value="' . (int) $item->ID . '" ' . checked( in_array( (int) $item->ID, $selected, true ), true, false ) . '>';

			if ( $thumb ) {
				echo '<img src="' . esc_url( $thumb ) . '" alt="" width="48" height="48" loading="lazy">';
			}

			echo '<span>' . esc_html( $title ) . '</span>';
			echo '</label>';
		}

		echo '</div></section>';
	}

	echo '</div>';
}

/**
 * İstekten kampanya alanlarını okur.
 *
 * @return array
 */
function ysf_campaign_request_raw() {
	$products = array();

	if ( isset( $_POST['products_json'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$decoded = json_decode( wp_unslash( $_POST['products_json'] ), true ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( is_array( $decoded ) ) {
			$products = $decoded;
		}
	} elseif ( isset( $_POST['products'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$products = (array) wp_unslash( $_POST['products'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	return array(
		'title'      => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'excerpt'    => isset( $_POST['excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excerpt'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'title_en'   => isset( $_POST['title_en'] ) ? sanitize_text_field( wp_unslash( $_POST['title_en'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'excerpt_en' => isset( $_POST['excerpt_en'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excerpt_en'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'scenario'   => isset( $_POST['scenario'] ) ? sanitize_key( wp_unslash( $_POST['scenario'] ) ) : 'general', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'kind'       => isset( $_POST['kind'] ) ? sanitize_key( wp_unslash( $_POST['kind'] ) ) : 'percent', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'value'      => isset( $_POST['value'] ) ? (float) wp_unslash( $_POST['value'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'min'        => isset( $_POST['min'] ) ? (float) wp_unslash( $_POST['min'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'bundle'     => isset( $_POST['bundle'] ) ? (float) wp_unslash( $_POST['bundle'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'start'      => isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'end'        => isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'time_start' => isset( $_POST['time_start'] ) ? ysf_campaign_normalize_time( wp_unslash( $_POST['time_start'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'time_end'   => isset( $_POST['time_end'] ) ? ysf_campaign_normalize_time( wp_unslash( $_POST['time_end'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'products'   => array_values( array_filter( array_map( 'absint', $products ) ) ),
	);
}

/**
 * Kampanya görselini kaydeder. Dosya yoksa mevcut görsel kalır.
 *
 * @param int $post_id Kampanya.
 * @return true|\WP_Error
 */
function ysf_campaign_attach_photo( $post_id ) {
	if ( ! empty( $_POST['remove_photo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		delete_post_thumbnail( $post_id );
	}

	if ( empty( $_FILES['photo']['name'] ) ) {
		return true;
	}

	if ( ! empty( $_FILES['photo']['error'] ) ) {
		return new WP_Error( 'photo', __( 'Görsel yüklenemedi.', 'ysffoodlab' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment = media_handle_upload( 'photo', $post_id );

	if ( is_wp_error( $attachment ) ) {
		return new WP_Error( 'photo', __( 'Görsel yüklenemedi.', 'ysffoodlab' ) );
	}

	set_post_thumbnail( $post_id, $attachment );

	return true;
}

/**
 * Kampanyayı kaydeder.
 *
 * @param int   $id  Mevcut kayıt. 0 ise yeni.
 * @param array $raw Alanlar.
 * @return int|\WP_Error
 */
function ysf_campaign_persist( $id, $raw ) {
	$result = ysf_campaign_validate( $raw );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$postarr = array(
		'post_type'    => 'ysf_campaign',
		'post_status'  => 'publish',
		'post_title'   => $raw['title'],
		'post_excerpt' => $raw['excerpt'],
	);

	if ( $id && 'ysf_campaign' === get_post_type( $id ) ) {
		if ( function_exists( 'ysf_is_duyuru' ) && ysf_is_duyuru( $id ) ) {
			return new WP_Error( 'id', __( 'Kampanya bulunamadı.', 'ysffoodlab' ) );
		}

		$postarr['ID'] = $id;
		$saved         = wp_update_post( $postarr, true );
	} else {
		$saved = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $saved ) || ! $saved ) {
		return new WP_Error( 'save', __( 'Kampanya kaydedilemedi.', 'ysffoodlab' ) );
	}

	$badges = ysf_campaign_badge_pair( $raw['scenario'], $raw['kind'], $raw['value'], (float) $raw['min'], $raw['bundle'] );
	$order  = function_exists( 'ysf_get_page_url_by_template' ) ? ysf_get_page_url_by_template( 'template-order.php' ) : '';

	update_post_meta( $saved, '_ysf_scenario', $raw['scenario'] );
	update_post_meta( $saved, '_ysf_products', 'general' === $raw['scenario'] ? array() : $raw['products'] );
	update_post_meta( $saved, '_ysf_discount_kind', 'fixed' === $raw['kind'] ? 'fixed' : 'percent' );
	update_post_meta( $saved, '_ysf_discount_value', max( 0, (float) $raw['value'] ) );
	update_post_meta( $saved, '_ysf_min_spend', ysf_campaign_money( max( 0, (float) $raw['min'] ) ) );
	update_post_meta( $saved, '_ysf_bundle_total', max( 0, (float) $raw['bundle'] ) );
	update_post_meta( $saved, '_ysf_start', $raw['start'] );
	update_post_meta( $saved, '_ysf_end', $raw['end'] );
	update_post_meta( $saved, '_ysf_time_start', $raw['time_start'] );
	update_post_meta( $saved, '_ysf_time_end', $raw['time_end'] );
	update_post_meta( $saved, '_ysf_type', 'kampanya' );
	update_post_meta( $saved, '_ysf_managed', '1' );
	update_post_meta( $saved, '_ysf_show_in_bar', '1' );
	update_post_meta( $saved, '_ysf_badge', $badges[0] );
	update_post_meta( $saved, '_ysf_badge_en', $badges[1] );
	update_post_meta( $saved, '_ysf_title_en', $raw['title_en'] );
	update_post_meta( $saved, '_ysf_excerpt_en', $raw['excerpt_en'] );

	if ( $order ) {
		update_post_meta( $saved, '_ysf_link', $order );
	}

	$photo = ysf_campaign_attach_photo( $saved );

	if ( is_wp_error( $photo ) ) {
		return $photo;
	}

	ysf_campaign_purge_cache();

	return (int) $saved;
}

/**
 * WordPress yönetim formunu kaydeder.
 */
function ysf_handle_save_campaign() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu işlem yalnız site yöneticisine açıktır.', 'ysffoodlab' ) );
	}

	check_admin_referer( 'ysf_save_campaign' );

	$id  = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
	$raw = ysf_campaign_request_raw();
	$saved = ysf_campaign_persist( $id, $raw );

	if ( is_wp_error( $saved ) ) {
		set_transient( 'ysf_campaign_error_' . get_current_user_id(), $saved->get_error_message(), 60 );
		set_transient( 'ysf_campaign_old_' . get_current_user_id(), $raw, 60 );
		$back = admin_url( 'admin.php?page=ysf-campaigns&action=' . ( $id ? 'edit&id=' . $id : 'new' ) );
		wp_safe_redirect( $back );
		exit;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=ysf-campaigns&ysf_notice=saved' ) );
	exit;
}
add_action( 'admin_post_ysf_save_campaign', 'ysf_handle_save_campaign' );

/**
 * Form değerlerini denetler.
 *
 * @param array $raw Form.
 * @return true|\WP_Error
 */
function ysf_campaign_validate( $raw ) {
	if ( '' === $raw['title'] ) {
		return new WP_Error( 'title', __( 'Kampanya adı gerekli.', 'ysffoodlab' ) );
	}

	if ( ! isset( ysf_campaign_scenarios()[ $raw['scenario'] ] ) ) {
		return new WP_Error( 'scenario', __( 'Senaryo seçin.', 'ysffoodlab' ) );
	}

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw['start'] ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw['end'] ) ) {
		return new WP_Error( 'date', __( 'Başlangıç ve bitiş tarihi gerekli.', 'ysffoodlab' ) );
	}

	if ( $raw['end'] < $raw['start'] ) {
		return new WP_Error( 'date', __( 'Bitiş tarihi başlangıçtan önce olamaz.', 'ysffoodlab' ) );
	}

	$time_start = ysf_campaign_normalize_time( $raw['time_start'] );
	$time_end   = ysf_campaign_normalize_time( $raw['time_end'] );
	$posted_start = isset( $raw['time_start'] ) ? trim( (string) $raw['time_start'] ) : '';
	$posted_end   = isset( $raw['time_end'] ) ? trim( (string) $raw['time_end'] ) : '';

	if ( ( '' !== $posted_start && '' === $time_start ) || ( '' !== $posted_end && '' === $time_end ) ) {
		return new WP_Error( 'time', __( 'Saat biçimi geçersiz.', 'ysffoodlab' ) );
	}

	if ( ( '' === $time_start ) !== ( '' === $time_end ) ) {
		return new WP_Error( 'time', __( 'Saat aralığı için başlangıç ve bitiş saatini birlikte girin.', 'ysffoodlab' ) );
	}

	if ( '' !== $time_start && $time_end <= $time_start ) {
		return new WP_Error( 'time', __( 'Bitiş saati başlangıç saatinden sonra olmalı.', 'ysffoodlab' ) );
	}

	$products = array_values( array_filter( array_map( 'absint', $raw['products'] ) ) );

	if ( 'general' !== $raw['scenario'] && ! $products ) {
		return new WP_Error( 'products', __( 'En az bir ürün seçin.', 'ysffoodlab' ) );
	}

	if ( 'bundle' === $raw['scenario'] && count( $products ) < 2 ) {
		return new WP_Error( 'products', __( 'Birlikte alım için en az iki ürün seçin.', 'ysffoodlab' ) );
	}

	if ( in_array( $raw['scenario'], array( 'direct', 'qty' ), true ) ) {
		if ( $raw['value'] <= 0 ) {
			return new WP_Error( 'value', __( 'İndirim değeri sıfırdan büyük olmalı.', 'ysffoodlab' ) );
		}

		if ( 'percent' === $raw['kind'] && $raw['value'] > 100 ) {
			return new WP_Error( 'value', __( 'Yüzde indirim 100’den büyük olamaz.', 'ysffoodlab' ) );
		}
	}

	if ( 'qty' === $raw['scenario'] && (float) $raw['min'] <= 0 ) {
		return new WP_Error( 'min', __( 'Ücret eşiği sıfırdan büyük olmalı.', 'ysffoodlab' ) );
	}

	if ( 'bundle' === $raw['scenario'] && $raw['bundle'] <= 0 ) {
		return new WP_Error( 'bundle', __( 'Birlikte toplam tutar sıfırdan büyük olmalı.', 'ysffoodlab' ) );
	}

	return true;
}

/**
 * Kampanyayı kalıcı siler. Yalnızca yönetici.
 */
function ysf_handle_delete_campaign() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Kampanyayı yalnız site yöneticisi silebilir.', 'ysffoodlab' ) );
	}

	$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

	check_admin_referer( 'ysf_delete_campaign_' . $id );

	if ( $id && 'ysf_campaign' === get_post_type( $id ) && ! ( function_exists( 'ysf_is_duyuru' ) && ysf_is_duyuru( $id ) ) ) {
		wp_delete_post( $id, true );
		ysf_campaign_purge_cache();
	}

	wp_safe_redirect( admin_url( 'admin.php?page=ysf-campaigns&ysf_notice=deleted' ) );
	exit;
}
add_action( 'admin_post_ysf_delete_campaign', 'ysf_handle_delete_campaign' );

/**
 * Hesabım kampanya isteğinde yönetici kontrolü.
 */
function ysf_campaign_account_guard() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'camp_forbidden' ) ), 403 );
	}
}

/**
 * Hesabım üzerinden kampanya kaydeder.
 */
function ysf_ajax_account_save_campaign() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_campaign_account_guard();

	$id    = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
	$saved = ysf_campaign_persist( $id, ysf_campaign_request_raw() );

	if ( is_wp_error( $saved ) ) {
		wp_send_json_error(
			array(
				'message' => $saved->get_error_message(),
			),
			400
		);
	}

	wp_send_json_success(
		array(
			'message' => ysf_t( 'camp_saved' ),
			'id'      => (int) $saved,
		)
	);
}
add_action( 'wp_ajax_ysf_account_save_campaign', 'ysf_ajax_account_save_campaign' );

/**
 * Hesabım üzerinden kampanyayı kalıcı siler.
 */
function ysf_ajax_account_delete_campaign() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_campaign_account_guard();

	$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

	if ( ! $id || 'ysf_campaign' !== get_post_type( $id ) || ( function_exists( 'ysf_is_duyuru' ) && ysf_is_duyuru( $id ) ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	wp_delete_post( $id, true );
	ysf_campaign_purge_cache();

	wp_send_json_success(
		array(
			'message' => ysf_t( 'camp_deleted' ),
		)
	);
}
add_action( 'wp_ajax_ysf_account_delete_campaign', 'ysf_ajax_account_delete_campaign' );

/**
 * Kampanya kaydı mı?
 *
 * @param int $post_id Gönderi.
 * @return bool
 */
function ysf_is_kampanya( $post_id ) {
	return ! ( function_exists( 'ysf_is_duyuru' ) && ysf_is_duyuru( $post_id ) );
}
