<?php
/**
 * Online sipariş: sepetin sunucu tarafında doğrulanması, kayıt ve bildirim.
 *
 * Fiyatlar asla istemciden gelen değerle hesaplanmaz; her kalem için
 * veritabanındaki güncel fiyat okunur.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aynı IP'den kısa sürede tekrarlanan gönderimleri engeller.
 *
 * @param string $bucket Sayaç adı.
 * @param int    $limit  İzin verilen gönderim sayısı.
 * @param int    $window Saniye cinsinden pencere.
 * @return bool Sınır aşıldıysa true.
 */
function ysf_rate_limited( $bucket, $limit = 5, $window = 600 ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key = 'ysf_rl_' . $bucket . '_' . md5( $ip );

	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return true;
	}

	set_transient( $key, $count + 1, $window );

	return false;
}

/**
 * Telefon numarasını doğrular (Türkiye ve uluslararası formatlar).
 *
 * @param string $phone Numara.
 * @return bool
 */
function ysf_valid_phone( $phone ) {
	$digits = ysf_digits( $phone );

	return strlen( $digits ) >= 10 && strlen( $digits ) <= 15;
}

/**
 * Bildirim e-postası gönderir.
 *
 * @param string $to      Alıcı.
 * @param string $subject Konu.
 * @param array  $rows    Etiket => değer satırları.
 * @param string $intro   Giriş metni.
 * @return bool
 */
function ysf_send_notification( $to, $subject, $rows, $intro = '' ) {
	$to = $to ? $to : get_option( 'admin_email' );

	$html  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#14100d">';
	$html .= '<h2 style="color:#b56a16;margin:0 0 12px">' . esc_html( $subject ) . '</h2>';

	if ( $intro ) {
		$html .= '<p>' . esc_html( $intro ) . '</p>';
	}

	$html .= '<table cellpadding="8" cellspacing="0" border="0" style="border-collapse:collapse;width:100%;max-width:600px">';

	foreach ( $rows as $label => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}

		$html .= sprintf(
			'<tr><td style="background:#f7f3ec;font-weight:bold;width:190px;border-bottom:1px solid #e6ded2">%1$s</td><td style="border-bottom:1px solid #e6ded2">%2$s</td></tr>',
			esc_html( $label ),
			nl2br( esc_html( (string) $value ) )
		);
	}

	$html .= '</table>';
	$html .= '<p style="color:#6d635b;font-size:12px;margin-top:18px">' . esc_html( home_url( '/' ) ) . '</p>';
	$html .= '</div>';

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %1$s <%2$s>', wp_specialchars_decode( get_bloginfo( 'name' ) ), 'wordpress@' . wp_parse_url( home_url(), PHP_URL_HOST ) ),
	);

	return wp_mail( $to, $subject, $html, $headers );
}

/**
 * Sipariş tiplerinin etiketleri.
 *
 * @return array
 */
function ysf_order_types() {
	return array(
		'delivery' => ysf_t( 'order_delivery' ),
		'pickup'   => ysf_t( 'order_pickup' ),
		'table'    => ysf_t( 'order_table' ),
	);
}

/**
 * Sipariş formunu işler.
 */
function ysf_ajax_submit_order() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ! ysf_get_option( 'ysf_orders_enabled', true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 403 );
	}

	// Bot tuzağı: gizli alan doldurulmuşsa sessizce reddet.
	if ( ! empty( $_POST['ysf_hp'] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( ysf_rate_limited( 'order', 8, 900 ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 429 );
	}

	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : 'delivery';
	$addr  = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
	$area  = isset( $_POST['area'] ) ? sanitize_text_field( wp_unslash( $_POST['area'] ) ) : '';
	$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
	$time  = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';
	$note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$items = isset( $_POST['items'] ) ? json_decode( wp_unslash( $_POST['items'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized

	if ( ! $name || ! ysf_valid_phone( $phone ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	if ( ! is_array( $items ) || empty( $items ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'order_empty_error' ) ), 400 );
	}

	if ( 'delivery' === $type && ! $addr ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	// Kalemleri veritabanındaki fiyatlarla yeniden hesapla.
	$lines    = array();
	$subtotal = 0.0;

	foreach ( $items as $item ) {
		$id  = isset( $item['id'] ) ? (int) $item['id'] : 0;
		$qty = isset( $item['qty'] ) ? max( 1, min( 50, (int) $item['qty'] ) ) : 1;

		if ( ! $id || 'ysf_menu_item' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
			continue;
		}

		if ( ! ysf_is_orderable( $id ) ) {
			continue;
		}

		$price = (float) get_post_meta( $id, '_ysf_price', true );
		$line  = array(
			'id'    => $id,
			'name'  => get_the_title( $id ),
			'qty'   => $qty,
			'price' => $price,
		);

		$subtotal += $price * $qty;
		$lines[]   = $line;
	}

	if ( empty( $lines ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'order_empty_error' ) ), 400 );
	}

	$min_order = (float) ysf_get_option( 'ysf_min_order', 0 );

	if ( 'delivery' === $type && $min_order > 0 && $subtotal < $min_order ) {
		wp_send_json_error(
			array( 'message' => sprintf( ysf_t( 'min_order_warning' ), ysf_price( $min_order ) ) ),
			400
		);
	}

	$fee = 0.0;

	if ( 'delivery' === $type ) {
		$fee       = (float) ysf_get_option( 'ysf_delivery_fee', 0 );
		$free_over = (float) ysf_get_option( 'ysf_free_delivery_over', 0 );

		if ( $free_over > 0 && $subtotal >= $free_over ) {
			$fee = 0.0;
		}
	}

	$total = $subtotal + $fee;
	$types = ysf_order_types();

	$order_id = wp_insert_post(
		array(
			'post_type'    => 'ysf_order',
			'post_status'  => 'publish',
			'post_title'   => sprintf( '%1$s — %2$s', $name, ysf_price( $total ) ),
			'post_content' => $note,
		),
		true
	);

	if ( is_wp_error( $order_id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	$meta = array(
		'_ysf_name'         => $name,
		'_ysf_phone'        => $phone,
		'_ysf_order_type'   => isset( $types[ $type ] ) ? $types[ $type ] : $type,
		'_ysf_address'      => trim( $area ? $area . ' — ' . $addr : $addr ),
		'_ysf_table'        => $table,
		'_ysf_time'         => $time,
		'_ysf_note'         => $note,
		'_ysf_items'        => $lines,
		'_ysf_subtotal'     => ysf_price( $subtotal ),
		'_ysf_delivery_fee' => $fee > 0 ? ysf_price( $fee ) : ysf_t( 'free' ),
		'_ysf_total'        => $total,
		'_ysf_state'        => 'pending',
		'_ysf_lang'         => ysf_lang(),
	);

	foreach ( $meta as $key => $value ) {
		update_post_meta( $order_id, $key, $value );
	}

	// Bildirim e-postası.
	$item_lines = array();
	foreach ( $lines as $line ) {
		$item_lines[] = sprintf( '%1$d x %2$s (%3$s)', $line['qty'], $line['name'], ysf_price( $line['price'] * $line['qty'] ) );
	}

	ysf_send_notification(
		ysf_get_option( 'ysf_order_email', '' ),
		sprintf(
			/* translators: %s: sipariş numarası. */
			__( 'Yeni online sipariş #%s', 'ysffoodlab' ),
			$order_id
		),
		array(
			__( 'Ad Soyad', 'ysffoodlab' )        => $name,
			__( 'Telefon', 'ysffoodlab' )         => $phone,
			__( 'Sipariş tipi', 'ysffoodlab' )    => $meta['_ysf_order_type'],
			__( 'Adres', 'ysffoodlab' )           => $meta['_ysf_address'],
			__( 'Masa no', 'ysffoodlab' )         => $table,
			__( 'İstenen saat', 'ysffoodlab' )    => $time,
			__( 'Ürünler', 'ysffoodlab' )         => implode( "\n", $item_lines ),
			__( 'Ara toplam', 'ysffoodlab' )      => ysf_price( $subtotal ),
			__( 'Teslimat ücreti', 'ysffoodlab' ) => $meta['_ysf_delivery_fee'],
			__( 'Toplam', 'ysffoodlab' )          => ysf_price( $total ),
			__( 'Not', 'ysffoodlab' )             => $note,
		),
		__( 'Web sitesinden yeni bir sipariş geldi.', 'ysffoodlab' )
	);

	// WhatsApp mesajı.
	$wa_url = '';

	if ( ysf_get_option( 'ysf_order_wa', true ) ) {
		$message  = sprintf( "*%s — %s #%d*\n", get_bloginfo( 'name' ), __( 'Sipariş', 'ysffoodlab' ), $order_id );
		$message .= sprintf( "%s: %s\n", __( 'Ad', 'ysffoodlab' ), $name );
		$message .= sprintf( "%s: %s\n", __( 'Telefon', 'ysffoodlab' ), $phone );
		$message .= sprintf( "%s: %s\n", __( 'Tip', 'ysffoodlab' ), $meta['_ysf_order_type'] );

		if ( $meta['_ysf_address'] ) {
			$message .= sprintf( "%s: %s\n", __( 'Adres', 'ysffoodlab' ), $meta['_ysf_address'] );
		}

		if ( $table ) {
			$message .= sprintf( "%s: %s\n", __( 'Masa', 'ysffoodlab' ), $table );
		}

		$message .= "\n" . implode( "\n", $item_lines ) . "\n";
		$message .= sprintf( "\n%s: %s", __( 'Toplam', 'ysffoodlab' ), ysf_price( $total ) );

		if ( $note ) {
			$message .= sprintf( "\n%s: %s", __( 'Not', 'ysffoodlab' ), $note );
		}

		$wa_url = ysf_whatsapp_url( $message );
	}

	wp_send_json_success(
		array(
			'message'  => ysf_t( 'order_success' ),
			'orderId'  => $order_id,
			'total'    => ysf_price( $total ),
			'whatsapp' => $wa_url,
		)
	);
}
add_action( 'wp_ajax_ysf_submit_order', 'ysf_ajax_submit_order' );
add_action( 'wp_ajax_nopriv_ysf_submit_order', 'ysf_ajax_submit_order' );

/**
 * Menü fiyatlarını istemciye veren yardımcı uç (sepet tutarlılığı için).
 */
function ysf_ajax_get_prices() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
	$out = array();

	foreach ( $ids as $id ) {
		if ( 'ysf_menu_item' !== get_post_type( $id ) ) {
			continue;
		}

		$out[ $id ] = array(
			'price'     => (float) get_post_meta( $id, '_ysf_price', true ),
			'name'      => ysf_field( $id, 'title' ),
			'orderable' => ysf_is_orderable( $id ),
		);
	}

	wp_send_json_success( $out );
}
add_action( 'wp_ajax_ysf_get_prices', 'ysf_ajax_get_prices' );
add_action( 'wp_ajax_nopriv_ysf_get_prices', 'ysf_ajax_get_prices' );
