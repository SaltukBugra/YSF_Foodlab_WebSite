<?php
/**
 * Duyurular: yönetim ekranı, görsel, başlık ve açıklama.
 *
 * Kampanyalardan ayrı tutulur. Üst duyuru şeridi ve ana sayfada görünür.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kayıt duyuru mu?
 *
 * @param int $post_id Gönderi.
 * @return bool
 */
function ysf_is_duyuru( $post_id ) {
	return 'duyuru' === (string) get_post_meta( $post_id, '_ysf_type', true );
}

/**
 * Yönetim menüsü.
 */
function ysf_announcements_admin_menu() {
	add_menu_page(
		__( 'Duyurular', 'ysffoodlab' ),
		__( 'Duyurular', 'ysffoodlab' ),
		'manage_options',
		'ysf-announcements',
		'ysf_announcements_admin_page',
		'dashicons-bell',
		6.45
	);
}
add_action( 'admin_menu', 'ysf_announcements_admin_menu', 21 );

/**
 * Duyuru ekranı.
 */
function ysf_announcements_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu sayfayı yalnız site yöneticisi kullanabilir.', 'ysffoodlab' ) );
	}

	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	echo '<div class="wrap ysf-campaign-admin">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Duyurular', 'ysffoodlab' ) . '</h1>';

	if ( 'new' !== $action ) {
		echo ' <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=ysf-announcements&action=new' ) ) . '">' . esc_html__( 'Yeni duyuru', 'ysffoodlab' ) . '</a>';
	}

	echo '<hr class="wp-header-end">';

	$notice = isset( $_GET['ysf_notice'] ) ? sanitize_key( wp_unslash( $_GET['ysf_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'saved' === $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Duyuru kaydedildi.', 'ysffoodlab' ) . '</p></div>';
	} elseif ( 'deleted' === $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Duyuru silindi.', 'ysffoodlab' ) . '</p></div>';
	}

	if ( 'new' === $action || 'edit' === $action ) {
		ysf_announcements_admin_form();
	} else {
		ysf_announcements_admin_list();
	}

	echo '</div>';
}

/**
 * Duyuru listesi.
 */
function ysf_announcements_admin_list() {
	$posts = ysf_get_duyuru_posts();

	echo '<div class="ysf-admin-note">';
	echo '<p>' . esc_html__( 'Duyurular üst şeritte ve ana sayfada görünür. Başlık, kısa açıklama ve isteğe bağlı görsel yeterlidir. Süre bitince “Süresi doldu” olarak kalır; silme yalnız yönetici hesabına açıktır.', 'ysffoodlab' ) . '</p>';
	echo '</div>';

	echo '<table class="widefat striped"><thead><tr>';
	echo '<th>' . esc_html__( 'Duyuru', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'Tarih', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'Durum', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'İşlem', 'ysffoodlab' ) . '</th>';
	echo '</tr></thead><tbody>';

	if ( ! $posts ) {
		echo '<tr><td colspan="4">' . esc_html__( 'Henüz duyuru yok.', 'ysffoodlab' ) . '</td></tr>';
	}

	foreach ( $posts as $post ) {
		$start  = (string) get_post_meta( $post->ID, '_ysf_start', true );
		$end    = (string) get_post_meta( $post->ID, '_ysf_end', true );
		$when   = trim( $start . ' — ' . $end, ' —' );
		$status = ysf_campaign_admin_status( $post->ID );
		$edit   = admin_url( 'admin.php?page=ysf-announcements&action=edit&id=' . (int) $post->ID );

		echo '<tr>';
		echo '<td><strong><a href="' . esc_url( $edit ) . '">' . esc_html( get_the_title( $post ) ) . '</a></strong></td>';
		echo '<td>' . esc_html( $when ) . '</td>';
		echo '<td><span class="' . esc_attr( $status['class'] ) . '">' . esc_html( $status['label'] ) . '</span></td>';
		echo '<td>';
		echo '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Düzenle', 'ysffoodlab' ) . '</a>';

		if ( current_user_can( 'manage_options' ) ) {
			echo ' · ';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline" onsubmit="return confirm(\'' . esc_js( __( 'Bu duyuru kalıcı olarak silinsin mi?', 'ysffoodlab' ) ) . '\');">';
			wp_nonce_field( 'ysf_delete_announcement_' . (int) $post->ID );
			echo '<input type="hidden" name="action" value="ysf_delete_announcement">';
			echo '<input type="hidden" name="id" value="' . (int) $post->ID . '">';
			echo '<button type="submit" class="button-link-delete">' . esc_html__( 'Sil', 'ysffoodlab' ) . '</button>';
			echo '</form>';
		}

		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * Duyuru kayıtlarını getirir.
 *
 * @return WP_Post[]
 */
function ysf_get_duyuru_posts() {
	$posts = get_posts(
		array(
			'post_type'      => 'ysf_campaign',
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => 100,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_key'       => '_ysf_type',
			'meta_value'     => 'duyuru',
		)
	);

	return is_array( $posts ) ? $posts : array();
}

/**
 * Duyuru formu.
 */
function ysf_announcements_admin_form() {
	$id   = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$post = ( $id && 'ysf_campaign' === get_post_type( $id ) && ysf_is_duyuru( $id ) ) ? get_post( $id ) : null;
	$old  = get_transient( 'ysf_announcement_old_' . get_current_user_id() );
	$err  = get_transient( 'ysf_announcement_error_' . get_current_user_id() );

	if ( $err ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $err ) . '</p></div>';
		delete_transient( 'ysf_announcement_error_' . get_current_user_id() );
	}

	if ( $old ) {
		delete_transient( 'ysf_announcement_old_' . get_current_user_id() );
	} else {
		$old = array();
	}

	$today = current_time( 'Y-m-d' );
	$year  = gmdate( 'Y-m-d', strtotime( '+1 year', current_time( 'timestamp' ) ) );

	$values = wp_parse_args(
		$old,
		array(
			'title'    => $post ? $post->post_title : '',
			'excerpt'  => $post ? $post->post_excerpt : '',
			'content'  => $post ? $post->post_content : '',
			'start'    => $post ? (string) get_post_meta( $post->ID, '_ysf_start', true ) : $today,
			'end'      => $post ? (string) get_post_meta( $post->ID, '_ysf_end', true ) : $year,
			'show_bar' => $post ? ysf_meta_flag( $post->ID, '_ysf_show_in_bar' ) : true,
		)
	);

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data" class="ysf-campaign-form">';
	wp_nonce_field( 'ysf_save_announcement' );
	echo '<input type="hidden" name="action" value="ysf_save_announcement">';
	echo '<input type="hidden" name="id" value="' . (int) ( $post ? $post->ID : 0 ) . '">';

	echo '<table class="form-table" role="presentation"><tbody>';

	echo '<tr><th scope="row"><label for="ysf-ann-title">' . esc_html__( 'Başlık', 'ysffoodlab' ) . '</label></th>';
	echo '<td><input type="text" class="regular-text" id="ysf-ann-title" name="title" value="' . esc_attr( $values['title'] ) . '" required maxlength="140"></td></tr>';

	echo '<tr><th scope="row"><label for="ysf-ann-excerpt">' . esc_html__( 'Kısa açıklama', 'ysffoodlab' ) . '</label></th>';
	echo '<td><textarea class="large-text" id="ysf-ann-excerpt" name="excerpt" rows="3" maxlength="400" required>' . esc_textarea( $values['excerpt'] ) . '</textarea>';
	echo '<p class="description">' . esc_html__( 'Üst şerit ve ana sayfa kartında görünür.', 'ysffoodlab' ) . '</p></td></tr>';

	echo '<tr><th scope="row"><label for="ysf-ann-content">' . esc_html__( 'İçerik', 'ysffoodlab' ) . '</label></th>';
	echo '<td><textarea class="large-text" id="ysf-ann-content" name="content" rows="5">' . esc_textarea( $values['content'] ) . '</textarea>';
	echo '<p class="description">' . esc_html__( 'İsteğe bağlı ayrıntılı metin.', 'ysffoodlab' ) . '</p></td></tr>';

	echo '<tr><th scope="row"><label for="ysf-ann-photo">' . esc_html__( 'Görsel', 'ysffoodlab' ) . '</label></th><td>';
	if ( $post && has_post_thumbnail( $post->ID ) ) {
		echo '<p><img src="' . esc_url( get_the_post_thumbnail_url( $post->ID, 'ysf-thumb' ) ) . '" alt="" style="max-width:220px;height:auto;border-radius:8px"></p>';
		echo '<label><input type="checkbox" name="remove_photo" value="1"> ' . esc_html__( 'Görseli kaldır', 'ysffoodlab' ) . '</label><br>';
	}
	echo '<input type="file" id="ysf-ann-photo" name="photo" accept="image/*">';
	echo '</td></tr>';

	echo '<tr><th scope="row"><label for="ysf-ann-start">' . esc_html__( 'Başlangıç', 'ysffoodlab' ) . '</label></th>';
	echo '<td><input type="date" id="ysf-ann-start" name="start" value="' . esc_attr( $values['start'] ) . '" required></td></tr>';

	echo '<tr><th scope="row"><label for="ysf-ann-end">' . esc_html__( 'Bitiş', 'ysffoodlab' ) . '</label></th>';
	echo '<td><input type="date" id="ysf-ann-end" name="end" value="' . esc_attr( $values['end'] ) . '" required></td></tr>';

	echo '<tr><th scope="row">' . esc_html__( 'Üst şerit', 'ysffoodlab' ) . '</th>';
	echo '<td><label><input type="checkbox" name="show_bar" value="1" ' . checked( $values['show_bar'], true, false ) . '> ' . esc_html__( 'Üst duyuru şeridinde göster', 'ysffoodlab' ) . '</label></td></tr>';

	echo '</tbody></table>';

	submit_button( $post ? __( 'Duyuruyu güncelle', 'ysffoodlab' ) : __( 'Duyuruyu kaydet', 'ysffoodlab' ) );
	echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=ysf-announcements' ) ) . '">' . esc_html__( 'İptal', 'ysffoodlab' ) . '</a>';
	echo '</form>';
}

/**
 * Form alanlarını okur.
 *
 * @return array
 */
function ysf_announcement_request_raw() {
	return array(
		'title'    => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
		'excerpt'  => isset( $_POST['excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excerpt'] ) ) : '',
		'content'  => isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '',
		'start'    => isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '',
		'end'      => isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '',
		'show_bar' => ! empty( $_POST['show_bar'] ),
	);
}

/**
 * Duyuru alanlarını doğrular.
 *
 * @param array $raw Form.
 * @return true|\WP_Error
 */
function ysf_announcement_validate( $raw ) {
	if ( '' === $raw['title'] ) {
		return new WP_Error( 'title', __( 'Başlık gerekli.', 'ysffoodlab' ) );
	}

	if ( '' === $raw['excerpt'] ) {
		return new WP_Error( 'excerpt', __( 'Kısa açıklama gerekli.', 'ysffoodlab' ) );
	}

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw['start'] ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw['end'] ) ) {
		return new WP_Error( 'date', __( 'Başlangıç ve bitiş tarihi gerekli.', 'ysffoodlab' ) );
	}

	if ( $raw['end'] < $raw['start'] ) {
		return new WP_Error( 'date', __( 'Bitiş tarihi başlangıçtan önce olamaz.', 'ysffoodlab' ) );
	}

	return true;
}

/**
 * Duyuruyu kaydeder.
 *
 * @param int   $id  Mevcut kayıt. 0 ise yeni.
 * @param array $raw Alanlar.
 * @return int|\WP_Error
 */
function ysf_announcement_persist( $id, $raw ) {
	$result = ysf_announcement_validate( $raw );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( $id && ( 'ysf_campaign' !== get_post_type( $id ) || ! ysf_is_duyuru( $id ) ) ) {
		return new WP_Error( 'id', __( 'Duyuru bulunamadı.', 'ysffoodlab' ) );
	}

	$postarr = array(
		'post_type'    => 'ysf_campaign',
		'post_status'  => 'publish',
		'post_title'   => $raw['title'],
		'post_excerpt' => $raw['excerpt'],
		'post_content' => $raw['content'],
	);

	if ( $id ) {
		$postarr['ID'] = $id;
		$saved         = wp_update_post( $postarr, true );
	} else {
		$saved = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $saved ) || ! $saved ) {
		return new WP_Error( 'save', __( 'Duyuru kaydedilemedi.', 'ysffoodlab' ) );
	}

	update_post_meta( $saved, '_ysf_type', 'duyuru' );
	update_post_meta( $saved, '_ysf_scenario', 'general' );
	update_post_meta( $saved, '_ysf_managed', '1' );
	update_post_meta( $saved, '_ysf_show_in_bar', $raw['show_bar'] ? '1' : '0' );
	update_post_meta( $saved, '_ysf_start', $raw['start'] );
	update_post_meta( $saved, '_ysf_end', $raw['end'] );
	update_post_meta( $saved, '_ysf_badge', __( 'Duyuru', 'ysffoodlab' ) );
	update_post_meta( $saved, '_ysf_badge_en', 'Notice' );
	update_post_meta( $saved, '_ysf_products', array() );
	update_post_meta( $saved, '_ysf_time_start', '' );
	update_post_meta( $saved, '_ysf_time_end', '' );

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
function ysf_handle_save_announcement() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bu işlem yalnız site yöneticisine açıktır.', 'ysffoodlab' ) );
	}

	check_admin_referer( 'ysf_save_announcement' );

	$id    = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
	$raw   = ysf_announcement_request_raw();
	$saved = ysf_announcement_persist( $id, $raw );

	if ( is_wp_error( $saved ) ) {
		set_transient( 'ysf_announcement_error_' . get_current_user_id(), $saved->get_error_message(), 60 );
		set_transient( 'ysf_announcement_old_' . get_current_user_id(), $raw, 60 );
		$back = admin_url( 'admin.php?page=ysf-announcements&action=' . ( $id ? 'edit&id=' . $id : 'new' ) );
		wp_safe_redirect( $back );
		exit;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=ysf-announcements&ysf_notice=saved' ) );
	exit;
}
add_action( 'admin_post_ysf_save_announcement', 'ysf_handle_save_announcement' );

/**
 * Duyuruyu kalıcı siler.
 */
function ysf_handle_delete_announcement() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Duyuruyu yalnız site yöneticisi silebilir.', 'ysffoodlab' ) );
	}

	$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

	check_admin_referer( 'ysf_delete_announcement_' . $id );

	if ( $id && 'ysf_campaign' === get_post_type( $id ) && ysf_is_duyuru( $id ) ) {
		wp_delete_post( $id, true );
		ysf_campaign_purge_cache();
	}

	wp_safe_redirect( admin_url( 'admin.php?page=ysf-announcements&ysf_notice=deleted' ) );
	exit;
}
add_action( 'admin_post_ysf_delete_announcement', 'ysf_handle_delete_announcement' );

/**
 * Hesabım duyuru isteğinde yönetici kontrolü.
 */
function ysf_announcement_account_guard() {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'ann_forbidden' ) ), 403 );
	}
}

/**
 * Hesabım üzerinden duyuru kaydeder.
 */
function ysf_ajax_account_save_announcement() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_announcement_account_guard();

	$id    = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
	$saved = ysf_announcement_persist( $id, ysf_announcement_request_raw() );

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
			'message' => ysf_t( 'ann_saved' ),
			'id'      => (int) $saved,
		)
	);
}
add_action( 'wp_ajax_ysf_account_save_announcement', 'ysf_ajax_account_save_announcement' );

/**
 * Hesabım üzerinden duyuruyu kalıcı siler.
 */
function ysf_ajax_account_delete_announcement() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_announcement_account_guard();

	$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

	if ( ! $id || 'ysf_campaign' !== get_post_type( $id ) || ! ysf_is_duyuru( $id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	wp_delete_post( $id, true );
	ysf_campaign_purge_cache();

	wp_send_json_success(
		array(
			'message' => ysf_t( 'ann_deleted' ),
		)
	);
}
add_action( 'wp_ajax_ysf_account_delete_announcement', 'ysf_ajax_account_delete_announcement' );
