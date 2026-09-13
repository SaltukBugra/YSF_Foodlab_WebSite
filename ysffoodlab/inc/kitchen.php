<?php
/**
 * Mutfak sorumlusu: menü kontrolü (stok, ekleme, kaldırma).
 *
 * Sipariş ekranından bağımsızdır. Yetkili kişi Hesabım sayfasındaki menü
 * kontrolünden ürün ekler, stokta yok işaretler veya menüden kaldırır.
 * WordPress yönetim paneline girmez.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YSF_ROLE_KITCHEN     = 'ysf_kitchen';
const YSF_CAP_MENU         = 'ysf_manage_menu';
const YSF_KITCHEN_EMAIL    = 'sef@ysffoodlab.com.tr';
const YSF_KITCHEN_PHONE    = '05372754263';
const YSF_KITCHEN_NAME     = 'Furkan Şef';
const YSF_KITCHEN_SEED_OPT = 'ysf_kitchen_staff_seeded';

/**
 * Mutfak rolünü ve menü yetkisini kaydeder.
 */
function ysf_register_roles() {
	$caps = array(
		'read'          => true,
		YSF_CAP_MENU    => true,
		'upload_files'  => true,
	);

	$role = get_role( YSF_ROLE_KITCHEN );

	if ( ! $role ) {
		add_role( YSF_ROLE_KITCHEN, __( 'Mutfak Sorumlusu', 'ysffoodlab' ), $caps );
	} else {
		foreach ( $caps as $cap => $grant ) {
			if ( $grant ) {
				$role->add_cap( $cap );
			}
		}
	}

	$admin = get_role( 'administrator' );

	if ( $admin ) {
		$admin->add_cap( YSF_CAP_MENU );
	}
}
add_action( 'init', 'ysf_register_roles', 1 );

/**
 * Menü kontrol yetkisi var mı?
 *
 * @param int $user_id Kullanıcı. 0 = oturumdaki.
 * @return bool
 */
function ysf_can_manage_menu( $user_id = 0 ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();

	return $user && $user->exists() && user_can( $user, YSF_CAP_MENU );
}

/**
 * Mutfak sorumlusu rolündeki hesap mı (yönetici değil)?
 *
 * @param int $user_id Kullanıcı.
 * @return bool
 */
function ysf_is_kitchen_staff( $user_id = 0 ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();

	if ( ! $user || ! $user->exists() ) {
		return false;
	}

	return in_array( YSF_ROLE_KITCHEN, (array) $user->roles, true );
}

/**
 * İşletmenin yapılandırılmış adresi (iş adresi olarak kullanılır).
 *
 * @return array
 */
function ysf_venue_address() {
	return array(
		'il'         => 'Konya',
		'ilce'       => 'Selçuklu',
		'mahalle'    => 'Bosna Hersek',
		'sokak'      => 'Abideyi Hürriyet Sk. G Blok',
		'bina'       => '4',
		'daire'      => 'H',
		'posta_kodu' => '',
		'tarif'      => '1.-2.-3.-4.-5. girişler',
	);
}

/**
 * Furkan Şef mutfak hesabını yoksa oluşturur, varsa yetkisini günceller.
 */
function ysf_maybe_seed_kitchen_staff() {
	if ( get_option( YSF_KITCHEN_SEED_OPT ) ) {
		return;
	}

	if ( ! get_role( YSF_ROLE_KITCHEN ) ) {
		return;
	}

	$email = YSF_KITCHEN_EMAIL;
	$phone = ysf_normalize_phone( YSF_KITCHEN_PHONE );
	$name  = YSF_KITCHEN_NAME;
	$user  = get_user_by( 'email', $email );

	if ( ! $user ) {
		$password = wp_generate_password( 16, true, true );
		$user_id  = wp_insert_user(
			array(
				'user_login'   => ysf_generate_username( $email, $name ),
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $name,
				'first_name'   => 'Furkan',
				'last_name'    => 'Şef',
				'role'         => YSF_ROLE_KITCHEN,
				'description'  => __( 'Mutfak sorumlusu', 'ysffoodlab' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );
		set_transient( 'ysf_kitchen_staff_pass', $password, WEEK_IN_SECONDS );

		try {
			ysf_send_notification(
				$email,
				sprintf(
					/* translators: %s: site adı. */
					__( '%s — mutfak hesabınız hazır', 'ysffoodlab' ),
					get_bloginfo( 'name' )
				),
				array(
					ysf_t( 'form_name' )  => $name,
					ysf_t( 'form_email' ) => $email,
					ysf_t( 'form_phone' ) => ysf_phone_display( $phone ),
					ysf_t( 'acc_password' ) => $password,
				),
				ysf_t( 'kit_welcome_mail' )
			);
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	} else {
		$user->set_role( YSF_ROLE_KITCHEN );
		wp_update_user(
			array(
				'ID'           => $user->ID,
				'display_name' => $name,
				'first_name'   => 'Furkan',
				'last_name'    => 'Şef',
			)
		);
	}

	if ( ! $user || ! $user->ID ) {
		return;
	}

	if ( $phone && ! ysf_phone_in_use( $phone, $user->ID ) ) {
		update_user_meta( $user->ID, YSF_META_PHONE, $phone );
	}

	$address = ysf_sanitize_address( ysf_venue_address() );
	$valid   = ysf_validate_address( $address );

	if ( ! is_wp_error( $valid ) ) {
		ysf_save_user_address( $user->ID, 'work', $address );
	}

	update_option( YSF_KITCHEN_SEED_OPT, (string) $user->ID, false );
}
add_action( 'init', 'ysf_maybe_seed_kitchen_staff', 20 );

/**
 * Yöneticiye bir kerelik mutfak şifresi hatırlatması.
 */
function ysf_kitchen_staff_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$password = get_transient( 'ysf_kitchen_staff_pass' );

	if ( ! $password ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Mutfak sorumlusu hesabı oluşturuldu.', 'ysffoodlab' ); ?></strong>
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: e-posta, 2: şifre. */
					__( 'Giriş: %1$s — geçici şifre: %2$s', 'ysffoodlab' ),
					YSF_KITCHEN_EMAIL,
					$password
				)
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'ysf_kitchen_staff_notice' );

/**
 * Mutfak panelinde listelenecek menü ürünleri.
 *
 * @return WP_Post[]
 */
function ysf_kitchen_items() {
	return get_posts(
		array(
			'post_type'      => 'ysf_menu_item',
			'post_status'    => array( 'publish', 'draft', 'pending', 'trash' ),
			'posts_per_page' => -1,
			'orderby'        => array(
				'post_status' => 'ASC',
				'title'       => 'ASC',
			),
		)
	);
}

/**
 * Mutfak AJAX isteklerinde yetki kontrolü.
 */
function ysf_kitchen_guard() {
	if ( ! ysf_can_manage_menu() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'kit_forbidden' ) ), 403 );
	}
}

/**
 * İstekten menü ürününü alır.
 *
 * @return WP_Post|null
 */
function ysf_kitchen_request_item() {
	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( ! $id || 'ysf_menu_item' !== get_post_type( $id ) ) {
		return null;
	}

	return get_post( $id );
}

/**
 * Ürün kaydeder veya yeni ürün ekler.
 */
function ysf_ajax_kitchen_save() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_kitchen_guard();

	$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	$excerpt = isset( $_POST['excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excerpt'] ) ) : '';
	$price   = isset( $_POST['price'] ) ? ysf_sanitize_meta_number( wp_unslash( $_POST['price'] ) ) : 0;
	$cat     = isset( $_POST['category'] ) ? absint( $_POST['category'] ) : 0;
	$sold    = ! empty( $_POST['sold_out'] );
	$order   = ! empty( $_POST['orderable'] );

	$title   = ysf_clip( $title, 140 );
	$excerpt = ysf_clip( $excerpt, 400 );

	if ( ! $title ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_required' ), 'field' => 'title' ), 400 );
	}

	if ( $price < 0 ) {
		wp_send_json_error( array( 'message' => ysf_t( 'kit_price_invalid' ), 'field' => 'price' ), 400 );
	}

	if ( $cat ) {
		$term = get_term( $cat, 'ysf_menu_cat' );

		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => ysf_t( 'kit_cat_invalid' ), 'field' => 'category' ), 400 );
		}
	}

	$existing = ysf_kitchen_request_item();
	$payload  = array(
		'post_type'    => 'ysf_menu_item',
		'post_status'  => $existing && 'trash' === $existing->post_status ? 'publish' : 'publish',
		'post_title'   => $title,
		'post_excerpt' => $excerpt,
		'post_content' => $excerpt,
	);

	if ( $existing ) {
		$payload['ID'] = $existing->ID;
		$post_id       = wp_update_post( $payload, true );
	} else {
		$post_id = wp_insert_post( $payload, true );
	}

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	update_post_meta( $post_id, '_ysf_price', $price );
	update_post_meta( $post_id, '_ysf_sold_out', $sold ? 1 : 0 );
	update_post_meta( $post_id, '_ysf_orderable', $order ? 1 : 0 );
	update_post_meta( $post_id, '_ysf_vegetarian', ! empty( $_POST['vegetarian'] ) ? 1 : 0 );
	update_post_meta( $post_id, '_ysf_vegan', ! empty( $_POST['vegan'] ) ? 1 : 0 );
	update_post_meta( $post_id, '_ysf_glutenfree', ! empty( $_POST['glutenfree'] ) ? 1 : 0 );
	update_post_meta( $post_id, '_ysf_spicy', ! empty( $_POST['spicy'] ) ? 1 : 0 );

	$tags = isset( $_POST['tags'] ) ? wp_unslash( $_POST['tags'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	ysf_save_item_tags( $post_id, $tags );

	if ( $cat ) {
		wp_set_object_terms( $post_id, array( $cat ), 'ysf_menu_cat' );
	}

	if ( ! empty( $_FILES['photo']['name'] ) && empty( $_FILES['photo']['error'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment = media_handle_upload( 'photo', $post_id );

		if ( ! is_wp_error( $attachment ) ) {
			set_post_thumbnail( $post_id, $attachment );
		}
	}

	wp_send_json_success(
		array(
			'message'  => ysf_t( 'kit_saved' ),
			'id'       => (int) $post_id,
			'redirect' => ysf_account_url() . '#ysf-kitchen',
		)
	);
}
add_action( 'wp_ajax_ysf_kitchen_save', 'ysf_ajax_kitchen_save' );

/**
 * Stok, gizleme ve menüden kaldırma.
 */
function ysf_ajax_kitchen_action() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_kitchen_guard();

	$item   = ysf_kitchen_request_item();
	$task   = isset( $_POST['task'] ) ? sanitize_key( wp_unslash( $_POST['task'] ) ) : '';
	$ok_msg = ysf_t( 'kit_saved' );

	if ( ! $item ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	switch ( $task ) {
		case 'sold_out':
			update_post_meta( $item->ID, '_ysf_sold_out', 1 );
			$ok_msg = ysf_t( 'kit_marked_out' );
			break;

		case 'in_stock':
			update_post_meta( $item->ID, '_ysf_sold_out', 0 );
			$ok_msg = ysf_t( 'kit_marked_in' );
			break;

		case 'remove':
			wp_trash_post( $item->ID );
			$ok_msg = ysf_t( 'kit_removed_ok' );
			break;

		case 'restore':
			wp_untrash_post( $item->ID );
			wp_publish_post( $item->ID );
			$ok_msg = ysf_t( 'kit_restored' );
			break;

		default:
			wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	wp_send_json_success(
		array(
			'message' => $ok_msg,
			'id'      => (int) $item->ID,
			'task'    => $task,
		)
	);
}
add_action( 'wp_ajax_ysf_kitchen_action', 'ysf_ajax_kitchen_action' );
