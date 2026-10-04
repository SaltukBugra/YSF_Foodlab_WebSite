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
const YSF_KITCHEN_SEED_OPT = 'ysf_kitchen_staff_seeded';

/**
 * İlk kurulumda açılacak mutfak hesabının bilgileri.
 *
 * Kişisel bilgiler koda yazılmaz; wp-config.php içinde tanımlanır:
 * define( 'YSF_KITCHEN_SEED', array( 'email' => 'sef@ornek.com', 'name' => 'Ad Soyad', 'phone' => '05xx...' ) );
 *
 * @return array{email:string,name:string,phone:string}
 */
function ysf_kitchen_seed_config() {
	$config = defined( 'YSF_KITCHEN_SEED' ) && is_array( YSF_KITCHEN_SEED ) ? YSF_KITCHEN_SEED : array();
	$config = apply_filters( 'ysf_kitchen_seed', $config );

	return array(
		'email' => isset( $config['email'] ) ? sanitize_email( $config['email'] ) : '',
		'name'  => isset( $config['name'] ) ? sanitize_text_field( $config['name'] ) : __( 'Mutfak Sorumlusu', 'ysffoodlab' ),
		'phone' => isset( $config['phone'] ) ? (string) $config['phone'] : '',
	);
}

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
 * Yapılandırılmış mutfak hesabını yoksa oluşturur, varsa yetkisini günceller.
 *
 * Şifre e-postayla gönderilmez; hesap sahibine şifre belirleme bağlantısı gider.
 */
function ysf_maybe_seed_kitchen_staff() {
	if ( get_option( YSF_KITCHEN_SEED_OPT ) ) {
		return;
	}

	if ( ! get_role( YSF_ROLE_KITCHEN ) ) {
		return;
	}

	$config = ysf_kitchen_seed_config();
	$email  = $config['email'];

	if ( ! is_email( $email ) ) {
		return;
	}

	$phone = $config['phone'] ? ysf_normalize_phone( $config['phone'] ) : '';
	$name  = $config['name'];
	$user  = get_user_by( 'email', $email );

	if ( ! $user ) {
		$user_id = wp_insert_user(
			array(
				'user_login'   => ysf_generate_username( $email, $name ),
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => $name,
				'first_name'   => $name,
				'role'         => YSF_ROLE_KITCHEN,
				'description'  => __( 'Mutfak sorumlusu', 'ysffoodlab' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );

		if ( $user ) {
			retrieve_password( $user->user_login );
			set_transient( 'ysf_kitchen_staff_created', $email, WEEK_IN_SECONDS );
		}
	} else {
		$user->set_role( YSF_ROLE_KITCHEN );
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
 * Yöneticiye mutfak hesabının açıldığını bildirir (şifre gösterilmez).
 */
function ysf_kitchen_staff_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	delete_transient( 'ysf_kitchen_staff_pass' );
	$email = get_transient( 'ysf_kitchen_staff_created' );

	if ( ! $email ) {
		return;
	}
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Mutfak sorumlusu hesabı oluşturuldu.', 'ysffoodlab' ); ?></strong>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: e-posta. */
					__( '%s adresine şifre belirleme bağlantısı gönderildi.', 'ysffoodlab' ),
					$email
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
				'menu_order' => 'ASC',
				'title'      => 'ASC',
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

	if ( isset( $_POST['ingredients'] ) ) {
		$ingredients = ysf_clip( sanitize_textarea_field( wp_unslash( $_POST['ingredients'] ) ), 600 );
		update_post_meta( $post_id, '_ysf_ingredients', $ingredients );
	}

	$raw_sizes = isset( $_POST['sizes'] ) ? wp_unslash( $_POST['sizes'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$decoded   = is_string( $raw_sizes ) ? json_decode( $raw_sizes, true ) : $raw_sizes;
	update_post_meta( $post_id, '_ysf_sizes', ysf_sanitize_sizes( is_array( $decoded ) ? $decoded : array() ) );
	ysf_set_meta_flag( $post_id, '_ysf_sold_out', $sold );
	ysf_set_meta_flag( $post_id, '_ysf_orderable', $order );
	ysf_set_meta_flag( $post_id, '_ysf_vegetarian', ! empty( $_POST['vegetarian'] ) );
	ysf_set_meta_flag( $post_id, '_ysf_vegan', ! empty( $_POST['vegan'] ) );
	ysf_set_meta_flag( $post_id, '_ysf_glutenfree', ! empty( $_POST['glutenfree'] ) );
	ysf_set_meta_flag( $post_id, '_ysf_spicy', ! empty( $_POST['spicy'] ) );

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
			ysf_set_meta_flag( $item->ID, '_ysf_sold_out', true );
			$ok_msg = ysf_t( 'kit_marked_out' );
			break;

		case 'in_stock':
			ysf_set_meta_flag( $item->ID, '_ysf_sold_out', false );
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

		case 'reorder':
			$ids   = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
			$place = array();

			foreach ( $ids as $post_id ) {
				if ( 'ysf_menu_item' !== get_post_type( $post_id ) ) {
					continue;
				}

				$terms = wp_get_post_terms( $post_id, 'ysf_menu_cat', array( 'fields' => 'ids' ) );
				$cat   = ( $terms && ! is_wp_error( $terms ) ) ? (int) $terms[0] : 0;

				if ( ! isset( $place[ $cat ] ) ) {
					$place[ $cat ] = 0;
				}

				++$place[ $cat ];

				wp_update_post(
					array(
						'ID'         => $post_id,
						'menu_order' => $place[ $cat ],
					)
				);
			}

			$ok_msg = ysf_t( 'kit_reordered' );
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
