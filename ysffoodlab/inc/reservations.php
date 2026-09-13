<?php
/**
 * Özel gün rezervasyonları ve iletişim formu.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Özel gün seçenekleri.
 *
 * @return array
 */
function ysf_occasions() {
	return array(
		'none'        => ysf_t( 'res_occasion_none' ),
		'birthday'    => ysf_t( 'res_occasion_bd' ),
		'anniversary' => ysf_t( 'res_occasion_anniv' ),
		'proposal'    => ysf_t( 'res_occasion_prop' ),
		'business'    => ysf_t( 'res_occasion_biz' ),
		'group'       => ysf_t( 'res_occasion_group' ),
	);
}

/**
 * Ek istek seçenekleri.
 *
 * @return array
 */
function ysf_reservation_extras() {
	return array(
		'cake'   => ysf_t( 'res_extra_cake' ),
		'flower' => ysf_t( 'res_extra_flower' ),
		'quiet'  => ysf_t( 'res_extra_quiet' ),
		'music'  => ysf_t( 'res_extra_music' ),
	);
}

/**
 * Rezervasyon için seçilebilir saat dilimleri.
 *
 * @return array
 */
function ysf_reservation_slots() {
	$start = ysf_get_option( 'ysf_res_slot_start', '12:00' );
	$end   = ysf_get_option( 'ysf_res_slot_end', '22:30' );

	$to_minutes = static function ( $value, $fallback ) {
		if ( preg_match( '/(\d{1,2})[:.](\d{2})/', (string) $value, $m ) ) {
			return (int) $m[1] * 60 + (int) $m[2];
		}

		return $fallback;
	};

	$from = $to_minutes( $start, 12 * 60 );
	$till = $to_minutes( $end, 22 * 60 + 30 );

	if ( $till <= $from ) {
		$till = $from + 600;
	}

	$slots = array();

	for ( $m = $from; $m <= $till; $m += 30 ) {
		$slots[] = sprintf( '%02d:%02d', intdiv( $m, 60 ) % 24, $m % 60 );
	}

	return $slots;
}

/**
 * Rezervasyon formunu işler.
 */
function ysf_ajax_submit_reservation() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ! ysf_get_option( 'ysf_res_enabled', true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 403 );
	}

	if ( ! empty( $_POST['ysf_hp'] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( ysf_rate_limited( 'reservation', 6, 900 ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 429 );
	}

	$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$date     = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
	$time     = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';
	$guests   = isset( $_POST['guests'] ) ? absint( wp_unslash( $_POST['guests'] ) ) : 0;
	$occasion = isset( $_POST['occasion'] ) ? sanitize_key( wp_unslash( $_POST['occasion'] ) ) : 'none';
	$note     = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$extras   = isset( $_POST['extras'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['extras'] ) ) : array();

	if ( ! $name || ! ysf_valid_phone( $phone ) || ! $date || ! $time || ! $guests ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	$max_guests = (int) ysf_get_option( 'ysf_res_max_guests', 20 );

	if ( $max_guests > 0 && $guests > $max_guests ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %d: kişi sayısı. */
					__( 'Bu form en fazla %d kişi içindir. Daha büyük gruplar için lütfen bizi arayın.', 'ysffoodlab' ),
					$max_guests
				),
			),
			400
		);
	}

	$timestamp = strtotime( $date . ' ' . $time );
	$lead      = (int) ysf_get_option( 'ysf_res_lead_hours', 2 ) * HOUR_IN_SECONDS;

	if ( ! $timestamp || $timestamp < ( current_time( 'timestamp' ) + $lead ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		wp_send_json_error(
			array(
				'message' => __( 'Seçtiğiniz saat için rezervasyon alamıyoruz. Lütfen daha ileri bir saat seçin veya bizi arayın.', 'ysffoodlab' ),
			),
			400
		);
	}

	$occasions   = ysf_occasions();
	$extras_all  = ysf_reservation_extras();
	$extra_names = array();

	foreach ( $extras as $extra ) {
		if ( isset( $extras_all[ $extra ] ) ) {
			$extra_names[] = $extras_all[ $extra ];
		}
	}

	$title = sprintf(
		'%1$s — %2$s %3$s / %4$d %5$s',
		$name,
		$date,
		$time,
		$guests,
		ysf_t( 'per_person' )
	);

	$reservation_id = wp_insert_post(
		array(
			'post_type'    => 'ysf_reservation',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $note,
		),
		true
	);

	if ( is_wp_error( $reservation_id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	$occasion_label = isset( $occasions[ $occasion ] ) ? $occasions[ $occasion ] : '';

	$meta = array(
		'_ysf_name'     => $name,
		'_ysf_phone'    => $phone,
		'_ysf_email'    => $email,
		'_ysf_date'     => $date,
		'_ysf_time'     => $time,
		'_ysf_guests'   => $guests,
		'_ysf_occasion' => $occasion_label,
		'_ysf_extras'   => implode( ', ', $extra_names ),
		'_ysf_note'     => $note,
		'_ysf_state'    => 'pending',
		'_ysf_lang'     => ysf_lang(),
	);

	foreach ( $meta as $key => $value ) {
		update_post_meta( $reservation_id, $key, $value );
	}

	ysf_send_notification(
		ysf_get_option( 'ysf_res_email', ysf_get_option( 'ysf_order_email', '' ) ),
		sprintf(
			/* translators: 1: tarih, 2: saat. */
			__( 'Yeni rezervasyon talebi: %1$s %2$s', 'ysffoodlab' ),
			$date,
			$time
		),
		array(
			__( 'Ad Soyad', 'ysffoodlab' )    => $name,
			__( 'Telefon', 'ysffoodlab' )     => $phone,
			__( 'E-posta', 'ysffoodlab' )     => $email,
			__( 'Tarih', 'ysffoodlab' )       => $date,
			__( 'Saat', 'ysffoodlab' )        => $time,
			__( 'Kişi sayısı', 'ysffoodlab' ) => $guests,
			__( 'Özel gün', 'ysffoodlab' )    => $occasion_label,
			__( 'Ek istekler', 'ysffoodlab' ) => implode( ', ', $extra_names ),
			__( 'Not', 'ysffoodlab' )         => $note,
		),
		__( 'Web sitesinden yeni bir rezervasyon talebi geldi.', 'ysffoodlab' )
	);

	// Misafire otomatik bilgilendirme.
	if ( $email ) {
		$subject = 'en' === ysf_lang()
			? sprintf( '%s — reservation request received', get_bloginfo( 'name' ) )
			: sprintf( '%s — rezervasyon talebiniz alındı', get_bloginfo( 'name' ) );

		ysf_send_notification(
			$email,
			$subject,
			array(
				ysf_t( 'form_date' )   => $date,
				ysf_t( 'form_time' )   => $time,
				ysf_t( 'form_guests' ) => $guests,
				ysf_t( 'form_phone' )  => ysf_get_option( 'ysf_phone', '' ),
			),
			ysf_t( 'res_success' )
		);
	}

	$wa_url  = '';
	$message = sprintf( "*%s — %s*\n", get_bloginfo( 'name' ), ysf_t( 'res_title' ) );

	$message .= sprintf( "%s: %s\n", ysf_t( 'form_name' ), $name );
	$message .= sprintf( "%s: %s\n", ysf_t( 'form_phone' ), $phone );
	$message .= sprintf( "%s: %s %s\n", ysf_t( 'form_date' ), $date, $time );
	$message .= sprintf( "%s: %d\n", ysf_t( 'form_guests' ), $guests );

	if ( $occasion_label ) {
		$message .= sprintf( "%s: %s\n", ysf_t( 'res_occasion' ), $occasion_label );
	}

	if ( $extra_names ) {
		$message .= sprintf( "%s: %s\n", ysf_t( 'res_extras' ), implode( ', ', $extra_names ) );
	}

	if ( $note ) {
		$message .= sprintf( "%s: %s", ysf_t( 'form_note' ), $note );
	}

	$wa_url = ysf_whatsapp_url( $message );

	wp_send_json_success(
		array(
			'message'  => ysf_t( 'res_success' ),
			'whatsapp' => $wa_url,
		)
	);
}
add_action( 'wp_ajax_ysf_submit_reservation', 'ysf_ajax_submit_reservation' );
add_action( 'wp_ajax_nopriv_ysf_submit_reservation', 'ysf_ajax_submit_reservation' );

/**
 * İletişim formunu işler.
 */
function ysf_ajax_submit_contact() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ! empty( $_POST['ysf_hp'] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( ysf_rate_limited( 'contact', 6, 900 ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 429 );
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( ! $name || ! $message || ( ! is_email( $email ) && ! ysf_valid_phone( $phone ) ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ) ), 400 );
	}

	$sent = ysf_send_notification(
		ysf_get_option( 'ysf_email', '' ),
		sprintf(
			/* translators: %s: konu. */
			__( 'Web sitesi iletişim formu: %s', 'ysffoodlab' ),
			$subject ? $subject : __( 'Yeni mesaj', 'ysffoodlab' )
		),
		array(
			__( 'Ad Soyad', 'ysffoodlab' ) => $name,
			__( 'E-posta', 'ysffoodlab' )  => $email,
			__( 'Telefon', 'ysffoodlab' )  => $phone,
			__( 'Konu', 'ysffoodlab' )     => $subject,
			__( 'Mesaj', 'ysffoodlab' )    => $message,
		)
	);

	if ( ! $sent ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	wp_send_json_success( array( 'message' => ysf_t( 'contact_success' ) ) );
}
add_action( 'wp_ajax_ysf_submit_contact', 'ysf_ajax_submit_contact' );
add_action( 'wp_ajax_nopriv_ysf_submit_contact', 'ysf_ajax_submit_contact' );
