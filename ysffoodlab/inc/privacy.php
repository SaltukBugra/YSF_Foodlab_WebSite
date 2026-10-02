<?php
/**
 * KVKK / GDPR: kişisel veri dışa aktarma ve silme.
 *
 * WordPress'in Araçlar → Kişisel Verileri Dışa Aktar / Sil ekranlarına
 * sipariş, rezervasyon ve üye profili (telefon, adres) verilerini bağlar.
 * Silme işlemi muhasebe için tutar bilgisini korur, kişiyi tanımlayan
 * alanları anonimleştirir.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kayıtlarda kişiyi tanımlayan meta alanları.
 *
 * @return string[]
 */
function ysf_privacy_personal_keys() {
	return array( '_ysf_name', '_ysf_phone', '_ysf_email', '_ysf_address', '_ysf_note', '_ysf_occasion', '_ysf_rating_note', '_ysf_track_key' );
}

/**
 * E-postaya bağlı sipariş ve rezervasyon kayıtları.
 *
 * @param string $email E-posta.
 * @param int    $page  Sayfa (1'den başlar).
 * @return array{ids:int[],done:bool}
 */
function ysf_privacy_record_ids( $email, $page ) {
	$per   = 50;
	$user  = get_user_by( 'email', $email );
	$or    = array(
		'relation' => 'OR',
		array(
			'key'   => '_ysf_email',
			'value' => $email,
		),
	);

	if ( $user ) {
		$or[] = array(
			'key'   => '_ysf_user_id',
			'value' => (int) $user->ID,
		);

		$phone = (string) get_user_meta( $user->ID, YSF_META_PHONE, true );

		if ( $phone ) {
			$or[] = array(
				'key'     => '_ysf_phone',
				'value'   => array_values( array_unique( array( $phone, ysf_phone_display( $phone ) ) ) ),
				'compare' => 'IN',
			);
		}
	}

	$ids = get_posts(
		array(
			'post_type'      => array( 'ysf_order', 'ysf_reservation' ),
			'post_status'    => 'any',
			'posts_per_page' => $per,
			'paged'          => max( 1, (int) $page ),
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'meta_query'     => $or, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);

	return array(
		'ids'  => array_map( 'intval', $ids ),
		'done' => count( $ids ) < $per,
	);
}

/**
 * Kişisel veri dışa aktarıcı.
 *
 * @param string $email E-posta.
 * @param int    $page  Sayfa.
 * @return array
 */
function ysf_privacy_exporter( $email, $page = 1 ) {
	$items = array();
	$user  = get_user_by( 'email', $email );

	if ( 1 === (int) $page && $user ) {
		$data  = array();
		$phone = (string) get_user_meta( $user->ID, YSF_META_PHONE, true );

		if ( $phone ) {
			$data[] = array(
				'name'  => __( 'Telefon', 'ysffoodlab' ),
				'value' => ysf_phone_display( $phone ),
			);
		}

		foreach ( ysf_address_types() as $type => $label ) {
			$address = get_user_meta( $user->ID, YSF_META_ADDR . $type, true );

			if ( is_array( $address ) && array_filter( $address ) ) {
				$data[] = array(
					'name'  => $label,
					'value' => implode( ', ', array_filter( array_map( 'strval', $address ) ) ),
				);
			}
		}

		if ( $data ) {
			$items[] = array(
				'group_id'    => 'ysf-profile',
				'group_label' => __( 'YSF Food Lab profil', 'ysffoodlab' ),
				'item_id'     => 'ysf-profile-' . $user->ID,
				'data'        => $data,
			);
		}
	}

	$batch  = ysf_privacy_record_ids( $email, $page );
	$labels = array(
		'_ysf_name'         => __( 'Ad Soyad', 'ysffoodlab' ),
		'_ysf_phone'        => __( 'Telefon', 'ysffoodlab' ),
		'_ysf_email'        => __( 'E-posta', 'ysffoodlab' ),
		'_ysf_address'      => __( 'Adres', 'ysffoodlab' ),
		'_ysf_order_type'   => __( 'Sipariş tipi', 'ysffoodlab' ),
		'_ysf_date'         => __( 'Tarih', 'ysffoodlab' ),
		'_ysf_time'         => __( 'Saat', 'ysffoodlab' ),
		'_ysf_guests'       => __( 'Kişi', 'ysffoodlab' ),
		'_ysf_occasion'     => __( 'Özel gün', 'ysffoodlab' ),
		'_ysf_note'         => __( 'Not', 'ysffoodlab' ),
		'_ysf_consent_at'   => __( 'Onay zamanı', 'ysffoodlab' ),
		'_ysf_consent_text' => __( 'Onay metni', 'ysffoodlab' ),
		'_ysf_rating'       => __( 'Puan', 'ysffoodlab' ),
		'_ysf_rating_note'  => __( 'Geri bildirim', 'ysffoodlab' ),
	);

	foreach ( $batch['ids'] as $id ) {
		$data = array(
			array(
				'name'  => __( 'Kayıt tarihi', 'ysffoodlab' ),
				'value' => get_the_date( 'Y-m-d H:i', $id ),
			),
		);

		foreach ( $labels as $key => $label ) {
			$value = get_post_meta( $id, $key, true );

			if ( '' !== $value && null !== $value && ! is_array( $value ) ) {
				$data[] = array(
					'name'  => $label,
					'value' => (string) $value,
				);
			}
		}

		if ( 'ysf_order' === get_post_type( $id ) ) {
			$lines = get_post_meta( $id, '_ysf_items', true );

			if ( is_array( $lines ) && $lines ) {
				$data[] = array(
					'name'  => __( 'Ürünler', 'ysffoodlab' ),
					'value' => implode(
						"\n",
						array_map(
							static function ( $line ) {
								return sprintf( '%1$d x %2$s', isset( $line['qty'] ) ? (int) $line['qty'] : 1, isset( $line['name'] ) ? $line['name'] : '' );
							},
							$lines
						)
					),
				);
			}

			$data[] = array(
				'name'  => __( 'Toplam', 'ysffoodlab' ),
				'value' => ysf_price( (float) get_post_meta( $id, '_ysf_total', true ) ),
			);
		}

		$is_order = 'ysf_order' === get_post_type( $id );
		$items[]  = array(
			'group_id'    => $is_order ? 'ysf-orders' : 'ysf-reservations',
			'group_label' => $is_order ? __( 'Siparişler', 'ysffoodlab' ) : __( 'Rezervasyonlar', 'ysffoodlab' ),
			'item_id'     => 'ysf-record-' . $id,
			'data'        => $data,
		);
	}

	return array(
		'data' => $items,
		'done' => $batch['done'],
	);
}

/**
 * Kişisel veri silici (anonimleştirme).
 *
 * @param string $email E-posta.
 * @param int    $page  Sayfa.
 * @return array
 */
function ysf_privacy_eraser( $email, $page = 1 ) {
	$removed  = false;
	$retained = false;
	$messages = array();
	$user     = get_user_by( 'email', $email );

	// Kayıtlar anonimleşince e-posta/telefon eşleşmesi kalkar; her turda ilk sayfa işlenir.
	$batch = ysf_privacy_record_ids( $email, 1 );

	foreach ( $batch['ids'] as $id ) {
		foreach ( ysf_privacy_personal_keys() as $key ) {
			if ( metadata_exists( 'post', $id, $key ) ) {
				delete_post_meta( $id, $key );
			}
		}

		delete_post_meta( $id, '_ysf_user_id' );
		update_post_meta( $id, '_ysf_name', __( 'Anonim', 'ysffoodlab' ) );
		update_post_meta( $id, '_ysf_anonymized_at', time() );

		wp_update_post(
			array(
				'ID'           => $id,
				'post_title'   => sprintf( '%1$s #%2$d', __( 'Anonim', 'ysffoodlab' ), $id ),
				'post_content' => '',
			)
		);

		$removed = true;

		if ( 'ysf_order' === get_post_type( $id ) ) {
			$retained = true;
		}
	}

	if ( $batch['done'] && $user ) {
		delete_user_meta( $user->ID, YSF_META_PHONE );

		foreach ( array_keys( ysf_address_types() ) as $type ) {
			delete_user_meta( $user->ID, YSF_META_ADDR . $type );
		}

		$removed = true;
	}

	if ( $retained ) {
		$messages[] = __( 'Sipariş tutarları yasal saklama yükümlülüğü nedeniyle kişiden bağımsız olarak tutuldu.', 'ysffoodlab' );
	}

	return array(
		'items_removed'  => $removed,
		'items_retained' => $retained,
		'messages'       => array_unique( $messages ),
		'done'           => $batch['done'],
	);
}

/**
 * Dışa aktarıcıyı kaydeder.
 *
 * @param array $exporters Dışa aktarıcılar.
 * @return array
 */
function ysf_register_privacy_exporter( $exporters ) {
	$exporters['ysffoodlab'] = array(
		'exporter_friendly_name' => __( 'YSF Food Lab siparişler ve rezervasyonlar', 'ysffoodlab' ),
		'callback'               => 'ysf_privacy_exporter',
	);

	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'ysf_register_privacy_exporter' );

/**
 * Siliciyi kaydeder.
 *
 * @param array $erasers Siliciler.
 * @return array
 */
function ysf_register_privacy_eraser( $erasers ) {
	$erasers['ysffoodlab'] = array(
		'eraser_friendly_name' => __( 'YSF Food Lab siparişler ve rezervasyonlar', 'ysffoodlab' ),
		'callback'             => 'ysf_privacy_eraser',
	);

	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'ysf_register_privacy_eraser' );

/**
 * Gizlilik politikası sayfasına önerilen metin.
 */
function ysf_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	wp_add_privacy_policy_content(
		'YSF Food Lab',
		wp_kses_post(
			'<p>' . __( 'Sipariş, rezervasyon ve iletişim formlarında adınız, telefonunuz, e-postanız ve teslimat adresiniz yalnızca talebinizi yerine getirmek için işlenir. Sipariş tutarları muhasebe yükümlülüğü nedeniyle saklanır; kişisel alanlar talep üzerine anonimleştirilir.', 'ysffoodlab' ) . '</p>'
		)
	);
}
add_action( 'admin_init', 'ysf_privacy_policy_content' );
