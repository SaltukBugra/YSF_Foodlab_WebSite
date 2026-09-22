<?php
/**
 * Masa siparişi: garson, kasiyer ve mutfak ekranı.
 *
 * Garson telefonda masayı seçip ürün gönderir; kasiyer hesap tahsil eder;
 * mutfak tablette biletleri canlı görür. Üçü de temanın personel
 * sayfalarında çalışır, mağaza uygulaması gerektirmez; ana ekrana
 * eklenebilir (PWA).
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const YSF_ROLE_WAITER      = 'ysf_waiter';
const YSF_ROLE_CASHIER     = 'ysf_cashier';
const YSF_CAP_TABLES       = 'ysf_manage_tables';
const YSF_CAP_KDS          = 'ysf_view_kds';
const YSF_CAP_CASHIER      = 'ysf_manage_cashier';
const YSF_STAFF_PAGES_OPT  = 'ysf_staff_pages_seeded';
const YSF_WAITER_TEMPLATE  = 'template-waiter.php';
const YSF_KDS_TEMPLATE     = 'template-kds.php';
const YSF_CASHIER_TEMPLATE = 'template-cashier.php';

/**
 * Garson / kasiyer rollerini ve mutfak ekranı yetkisini kaydeder.
 */
function ysf_register_floor_roles() {
	$waiter_caps = array(
		'read'         => true,
		YSF_CAP_TABLES => true,
	);

	$role = get_role( YSF_ROLE_WAITER );

	if ( ! $role ) {
		add_role( YSF_ROLE_WAITER, __( 'Garson', 'ysffoodlab' ), $waiter_caps );
	} else {
		foreach ( $waiter_caps as $cap => $grant ) {
			if ( $grant ) {
				$role->add_cap( $cap );
			}
		}
	}

	$cashier_caps = array(
		'read'           => true,
		YSF_CAP_CASHIER  => true,
	);

	$cashier = get_role( YSF_ROLE_CASHIER );

	if ( ! $cashier ) {
		add_role( YSF_ROLE_CASHIER, __( 'Kasiyer', 'ysffoodlab' ), $cashier_caps );
	} else {
		foreach ( $cashier_caps as $cap => $grant ) {
			if ( $grant ) {
				$cashier->add_cap( $cap );
			}
		}
	}

	$kitchen = get_role( YSF_ROLE_KITCHEN );

	if ( $kitchen ) {
		$kitchen->add_cap( YSF_CAP_KDS );
	}

	$admin = get_role( 'administrator' );

	if ( $admin ) {
		$admin->add_cap( YSF_CAP_TABLES );
		$admin->add_cap( YSF_CAP_KDS );
		$admin->add_cap( YSF_CAP_CASHIER );
	}
}
add_action( 'init', 'ysf_register_floor_roles', 2 );

/**
 * Masa siparişi alabilir mi?
 *
 * @param int $user_id Kullanıcı. 0 = oturumdaki.
 * @return bool
 */
function ysf_can_take_orders( $user_id = 0 ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();

	return $user && $user->exists() && user_can( $user, YSF_CAP_TABLES );
}

/**
 * Mutfak ekranını görebilir mi?
 *
 * @param int $user_id Kullanıcı. 0 = oturumdaki.
 * @return bool
 */
function ysf_can_view_kds( $user_id = 0 ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();

	return $user && $user->exists() && user_can( $user, YSF_CAP_KDS );
}

/**
 * Garson rolündeki hesap mı (yönetici değil)?
 *
 * @param int $user_id Kullanıcı.
 * @return bool
 */
function ysf_is_waiter( $user_id = 0 ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();

	if ( ! $user || ! $user->exists() ) {
		return false;
	}

	return in_array( YSF_ROLE_WAITER, (array) $user->roles, true );
}

/**
 * Kasa ekranını görebilir mi? Yalnızca kasiyer ve yönetici.
 *
 * @param int $user_id Kullanıcı. 0 = oturumdaki.
 * @return bool
 */
function ysf_can_cashier( $user_id = 0 ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();

	return $user && $user->exists() && user_can( $user, YSF_CAP_CASHIER );
}

/**
 * Kasiyer rolündeki hesap mı (yönetici değil)?
 *
 * @param int $user_id Kullanıcı.
 * @return bool
 */
function ysf_is_cashier( $user_id = 0 ) {
	$user = $user_id ? get_userdata( (int) $user_id ) : wp_get_current_user();

	if ( ! $user || ! $user->exists() ) {
		return false;
	}

	return in_array( YSF_ROLE_CASHIER, (array) $user->roles, true );
}

/**
 * Masa servisi açık mı?
 *
 * @return bool
 */
function ysf_floor_enabled() {
	return (bool) ysf_get_option( 'ysf_floor_enabled', true );
}

/**
 * Masa numaraları (1..N).
 *
 * @return string[]
 */
function ysf_table_numbers() {
	$count = (int) ysf_get_option( 'ysf_table_count', 16 );
	$count = max( 1, min( 80, $count ) );
	$out   = array();

	for ( $i = 1; $i <= $count; $i++ ) {
		$out[] = (string) $i;
	}

	return $out;
}

/**
 * Personel uygulaması sayfası mı?
 *
 * @return bool
 */
function ysf_is_staff_app() {
	return is_page_template( YSF_WAITER_TEMPLATE ) || is_page_template( YSF_KDS_TEMPLATE ) || is_page_template( YSF_CASHIER_TEMPLATE );
}

/**
 * Garson uygulamasının adresi.
 *
 * @return string
 */
function ysf_waiter_url() {
	$url = ysf_get_page_url_by_template( YSF_WAITER_TEMPLATE );

	return $url ? ysf_localize_url( $url ) : '';
}

/**
 * Mutfak ekranının adresi.
 *
 * @return string
 */
function ysf_kds_url() {
	$url = ysf_get_page_url_by_template( YSF_KDS_TEMPLATE );

	return $url ? ysf_localize_url( $url ) : '';
}

/**
 * Kasiyer uygulamasının adresi.
 *
 * @return string
 */
function ysf_cashier_url() {
	$url = ysf_get_page_url_by_template( YSF_CASHIER_TEMPLATE );

	return $url ? ysf_localize_url( $url ) : '';
}

/**
 * Ödeme yöntemleri.
 *
 * @return array
 */
function ysf_cashier_pay_methods() {
	return array(
		'cash' => ysf_t( 'cash_pay_cash' ),
		'card' => ysf_t( 'cash_pay_card' ),
	);
}

/**
 * Personel sayfalarını yoksa oluşturur (kurulum sihirbazı çalışmasa da).
 */
function ysf_maybe_seed_staff_pages() {
	if ( get_option( YSF_STAFF_PAGES_OPT ) ) {
		return;
	}

	$pages = array(
		'garson'         => array(
			'title'    => 'Garson',
			'title_en' => 'Waiter',
			'template' => YSF_WAITER_TEMPLATE,
		),
		'mutfak-ekrani'  => array(
			'title'    => 'Mutfak Ekranı',
			'title_en' => 'Kitchen Display',
			'template' => YSF_KDS_TEMPLATE,
		),
		'kasiyer'        => array(
			'title'    => 'Kasiyer',
			'title_en' => 'Cashier',
			'template' => YSF_CASHIER_TEMPLATE,
		),
	);

	foreach ( $pages as $slug => $page ) {
		$existing = ysf_get_page_url_by_template( $page['template'] );

		if ( $existing ) {
			continue;
		}

		$by_path = get_page_by_path( $slug );

		if ( $by_path ) {
			update_post_meta( $by_path->ID, '_wp_page_template', $page['template'] );
			update_post_meta( $by_path->ID, '_ysf_staff_app', 1 );

			if ( ! get_post_meta( $by_path->ID, '_ysf_title_en', true ) ) {
				update_post_meta( $by_path->ID, '_ysf_title_en', $page['title_en'] );
			}

			wp_cache_delete( 'ysf_tpl_url_' . md5( $page['template'] ), 'ysffoodlab' );
			continue;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $id ) || ! $id ) {
			continue;
		}

		update_post_meta( $id, '_wp_page_template', $page['template'] );
		update_post_meta( $id, '_ysf_title_en', $page['title_en'] );
		update_post_meta( $id, '_ysf_staff_app', 1 );
		wp_cache_delete( 'ysf_tpl_url_' . md5( $page['template'] ), 'ysffoodlab' );
	}

	if ( ysf_waiter_url() && ysf_kds_url() && ysf_cashier_url() ) {
		update_option( YSF_STAFF_PAGES_OPT, '1', false );
	}
}
add_action( 'init', 'ysf_maybe_seed_staff_pages', 30 );
add_action( 'after_switch_theme', 'ysf_maybe_seed_staff_pages' );

/**
 * Kurulu sitelerde kasiyer sayfasını yoksa ekler.
 */
function ysf_maybe_seed_cashier_page() {
	if ( ysf_get_page_url_by_template( YSF_CASHIER_TEMPLATE ) ) {
		return;
	}

	$slug     = 'kasiyer';
	$title    = 'Kasiyer';
	$title_en = 'Cashier';
	$by_path  = get_page_by_path( $slug );

	if ( $by_path ) {
		update_post_meta( $by_path->ID, '_wp_page_template', YSF_CASHIER_TEMPLATE );
		update_post_meta( $by_path->ID, '_ysf_staff_app', 1 );

		if ( ! get_post_meta( $by_path->ID, '_ysf_title_en', true ) ) {
			update_post_meta( $by_path->ID, '_ysf_title_en', $title_en );
		}

		wp_cache_delete( 'ysf_tpl_url_' . md5( YSF_CASHIER_TEMPLATE ), 'ysffoodlab' );
		return;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $id ) || ! $id ) {
		return;
	}

	update_post_meta( $id, '_wp_page_template', YSF_CASHIER_TEMPLATE );
	update_post_meta( $id, '_ysf_title_en', $title_en );
	update_post_meta( $id, '_ysf_staff_app', 1 );
	wp_cache_delete( 'ysf_tpl_url_' . md5( YSF_CASHIER_TEMPLATE ), 'ysffoodlab' );
}
add_action( 'init', 'ysf_maybe_seed_cashier_page', 31 );
add_action( 'after_switch_theme', 'ysf_maybe_seed_cashier_page' );

/**
 * Varsayılan garson hesabını yoksa oluşturur.
 */
function ysf_maybe_seed_waiter_staff() {
	if ( get_option( 'ysf_waiter_staff_seeded' ) ) {
		return;
	}

	if ( ! get_role( YSF_ROLE_WAITER ) ) {
		return;
	}

	$email = 'garson@ysffoodlab.com.tr';
	$user  = get_user_by( 'email', $email );

	if ( ! $user ) {
		$user_id = wp_insert_user(
			array(
				'user_login'   => 'garson',
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 16, true, true ),
				'display_name' => __( 'Garson', 'ysffoodlab' ),
				'first_name'   => 'Garson',
				'role'         => YSF_ROLE_WAITER,
				'description'  => __( 'Masa servisi', 'ysffoodlab' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );
	} else {
		$user->set_role( YSF_ROLE_WAITER );
	}

	if ( ! $user || ! $user->ID ) {
		return;
	}

	update_option( 'ysf_waiter_staff_seeded', (string) $user->ID, false );
}
add_action( 'init', 'ysf_maybe_seed_waiter_staff', 21 );

/**
 * Varsayılan kasiyer hesabını yoksa oluşturur.
 */
function ysf_maybe_seed_cashier_staff() {
	if ( get_option( 'ysf_cashier_staff_seeded' ) ) {
		return;
	}

	if ( ! get_role( YSF_ROLE_CASHIER ) ) {
		return;
	}

	$email = 'kasiyer@ysffoodlab.com.tr';
	$user  = get_user_by( 'email', $email );

	if ( ! $user ) {
		$user_id = wp_insert_user(
			array(
				'user_login'   => 'kasiyer',
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 16, true, true ),
				'display_name' => __( 'Kasiyer', 'ysffoodlab' ),
				'first_name'   => 'Kasiyer',
				'role'         => YSF_ROLE_CASHIER,
				'description'  => __( 'Kasa / tahsilat', 'ysffoodlab' ),
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );
	} else {
		$user->set_role( YSF_ROLE_CASHIER );
	}

	if ( ! $user || ! $user->ID ) {
		return;
	}

	update_option( 'ysf_cashier_staff_seeded', (string) $user->ID, false );
}
add_action( 'init', 'ysf_maybe_seed_cashier_staff', 22 );

/**
 * Mutfak durumları.
 *
 * @return array
 */
function ysf_kitchen_states() {
	return array(
		'queued'  => ysf_t( 'kds_queued' ),
		'cooking' => ysf_t( 'kds_cooking' ),
		'ready'   => ysf_t( 'kds_ready' ),
		'served'  => ysf_t( 'kds_served' ),
	);
}

/**
 * Siparişin mutfak durumu.
 *
 * @param int $post_id Sipariş.
 * @return string
 */
function ysf_order_kitchen_state( $post_id ) {
	$state = get_post_meta( $post_id, '_ysf_kitchen_state', true );

	if ( isset( ysf_kitchen_states()[ $state ] ) ) {
		return $state;
	}

	$order = get_post_meta( $post_id, '_ysf_state', true );

	if ( in_array( $order, array( 'done', 'cancelled' ), true ) ) {
		return 'served';
	}

	return 'queued';
}

/**
 * Sipariş kanalı: table | delivery | pickup.
 *
 * @param int $post_id Sipariş.
 * @return string
 */
function ysf_order_channel( $post_id ) {
	$channel = get_post_meta( $post_id, '_ysf_channel', true );

	if ( in_array( $channel, array( 'table', 'delivery', 'pickup' ), true ) ) {
		return $channel;
	}

	if ( get_post_meta( $post_id, '_ysf_table', true ) ) {
		return 'table';
	}

	return 'online';
}

/**
 * Masa için ürün sipariş edilebilir mi (online kapağı yok sayılır).
 *
 * @param int $post_id Ürün.
 * @return bool
 */
function ysf_is_table_item( $post_id ) {
	if ( 'ysf_menu_item' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
		return false;
	}

	if ( ysf_meta_flag( $post_id, '_ysf_sold_out' ) ) {
		return false;
	}

	return (float) get_post_meta( $post_id, '_ysf_price', true ) > 0;
}

/**
 * Personel PWA bildirimi ve servis işçisini sunar.
 */
function ysf_staff_app_assets() {
	if ( isset( $_GET['ysf_manifest'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$kind = sanitize_key( wp_unslash( $_GET['ysf_manifest'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_kds     = 'kds' === $kind;
		$is_cashier = 'cashier' === $kind;

		if ( $is_kds ) {
			$start = ysf_kds_url();
			$name  = ysf_t( 'kds_name' );
		} elseif ( $is_cashier ) {
			$start = ysf_cashier_url();
			$name  = ysf_t( 'cash_name' );
		} else {
			$start = ysf_waiter_url();
			$name  = ysf_t( 'pos_name' );
		}

		if ( ! $start ) {
			status_header( 404 );
			exit;
		}

		$icon = (string) get_site_icon_url( 192 );

		if ( ! $icon ) {
			$icon = (string) ysf_share_image();
		}

		$icons = array();

		if ( $icon ) {
			$icons[] = array(
				'src'   => $icon,
				'sizes' => '192x192',
				'type'  => 'image/png',
			);
			$big     = (string) get_site_icon_url( 512 );
			$icons[] = array(
				'src'   => $big ? $big : $icon,
				'sizes' => '512x512',
				'type'  => 'image/png',
			);
		}

		$manifest = array(
			'name'             => get_bloginfo( 'name' ) . ' — ' . $name,
			'short_name'       => $name,
			'start_url'        => $start,
			'scope'            => $start,
			'display'          => 'standalone',
			'orientation'      => $is_kds ? 'any' : 'portrait',
			'background_color' => $is_kds ? '#14100d' : '#fbf7f1',
			'theme_color'      => '#14100d',
			'lang'             => 'tr' === ysf_lang() ? 'tr' : 'en',
			'icons'            => $icons,
		);

		nocache_headers();
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		echo wp_json_encode( $manifest ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	if ( ! isset( $_GET['ysf_sw'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	nocache_headers();
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Service-Worker-Allowed: /' );
	echo "self.addEventListener('install',function(e){self.skipWaiting();});\n";
	echo "self.addEventListener('activate',function(e){e.waitUntil(self.clients.claim());});\n";
	exit;
}
add_action( 'template_redirect', 'ysf_staff_app_assets', 0 );

/**
 * Personel sayfalarını dizine ekletmez.
 *
 * @param array $robots Robots yönergeleri.
 * @return array
 */
function ysf_staff_robots( $robots ) {
	if ( ysf_is_staff_app() ) {
		return array(
			'noindex'  => true,
			'nofollow' => true,
		);
	}

	return $robots;
}
add_filter( 'wp_robots', 'ysf_staff_robots' );

/**
 * Personel sayfalarını site haritasından çıkarır.
 *
 * @param array  $args      Sorgu.
 * @param string $post_type Tür.
 * @return array
 */
function ysf_staff_sitemap_query( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$meta = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$meta[] = array(
		'key'     => '_ysf_staff_app',
		'compare' => 'NOT EXISTS',
	);
	$args['meta_query'] = $meta;

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'ysf_staff_sitemap_query', 10, 2 );

/**
 * Personel uygulamalarında yönetim çubuğu gizlenir (tam ekran).
 *
 * @param bool $show Gösterilsin mi.
 * @return bool
 */
function ysf_staff_hide_admin_bar( $show ) {
	if ( ysf_is_staff_app() ) {
		return false;
	}

	return $show;
}
add_filter( 'show_admin_bar', 'ysf_staff_hide_admin_bar' );

/**
 * Personel uygulaması adresi mi?
 *
 * @param string $url Adres.
 * @return bool
 */
function ysf_is_staff_redirect( $url ) {
	$url = wp_validate_redirect( $url, '' );

	if ( ! $url ) {
		return false;
	}

	$clean = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	$pages = array(
		ysf_get_page_url_by_template( YSF_WAITER_TEMPLATE ),
		ysf_get_page_url_by_template( YSF_KDS_TEMPLATE ),
		ysf_get_page_url_by_template( YSF_CASHIER_TEMPLATE ),
	);

	foreach ( $pages as $allowed ) {
		if ( ! $allowed ) {
			continue;
		}

		if ( $clean === untrailingslashit( (string) wp_parse_url( $allowed, PHP_URL_PATH ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Personel AJAX yetki kontrolü.
 *
 * @param string $which tables|kds.
 */
function ysf_floor_guard( $which ) {
	if ( 'kds' === $which ) {
		if ( ! ysf_can_view_kds() ) {
			wp_send_json_error( array( 'message' => ysf_t( 'kds_forbidden' ) ), 403 );
		}
	} elseif ( 'cashier' === $which ) {
		if ( ! ysf_can_cashier() ) {
			wp_send_json_error( array( 'message' => ysf_t( 'cash_forbidden' ) ), 403 );
		}
	} elseif ( ! ysf_can_take_orders() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_forbidden' ) ), 403 );
	}

	if ( ! ysf_floor_enabled() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 403 );
	}
}

/**
 * İstekteki kalemleri fiyatlarla doğrular.
 *
 * @param array $items Ham kalemler.
 * @return array|\WP_Error
 */
function ysf_floor_parse_items( $items ) {
	if ( ! is_array( $items ) || empty( $items ) ) {
		return new WP_Error( 'empty', ysf_t( 'pos_cart_empty' ) );
	}

	$lines = array();

	foreach ( $items as $item ) {
		$id   = isset( $item['id'] ) ? (int) $item['id'] : 0;
		$qty  = isset( $item['qty'] ) ? max( 1, min( 50, (int) $item['qty'] ) ) : 1;
		$note = isset( $item['note'] ) ? ysf_clip( sanitize_text_field( (string) $item['note'] ), 80 ) : '';

		if ( ! $id || ! ysf_is_table_item( $id ) ) {
			continue;
		}

		$price = (float) get_post_meta( $id, '_ysf_price', true );
		$line  = array(
			'id'    => $id,
			'name'  => get_the_title( $id ),
			'qty'   => $qty,
			'price' => $price,
		);

		if ( $note ) {
			$line['note'] = $note;
		}

		$lines[] = $line;
	}

	if ( empty( $lines ) ) {
		return new WP_Error( 'empty', ysf_t( 'pos_cart_empty' ) );
	}

	if ( function_exists( 'ysf_apply_campaign_prices' ) ) {
		$lines = ysf_apply_campaign_prices( $lines );
	}

	$subtotal = 0.0;

	foreach ( $lines as $line ) {
		$subtotal += (float) $line['price'] * (int) $line['qty'];
	}

	return array(
		'lines'    => $lines,
		'subtotal' => $subtotal,
	);
}

/**
 * Siparişi mutfak ekranı için diziye çevirir.
 *
 * @param WP_Post|int $post Sipariş.
 * @return array|null
 */
function ysf_floor_ticket_payload( $post ) {
	$post = get_post( $post );

	if ( ! $post || 'ysf_order' !== $post->post_type ) {
		return null;
	}

	$items = get_post_meta( $post->ID, '_ysf_items', true );
	$items = is_array( $items ) ? $items : array();
	$lines = array();

	foreach ( $items as $line ) {
		$lines[] = array(
			'id'    => isset( $line['id'] ) ? (int) $line['id'] : 0,
			'name'  => isset( $line['name'] ) ? (string) $line['name'] : '',
			'qty'   => isset( $line['qty'] ) ? (int) $line['qty'] : 1,
			'price' => isset( $line['price'] ) ? (float) $line['price'] : 0,
			'note'  => isset( $line['note'] ) ? (string) $line['note'] : '',
		);
	}

	$total   = (float) get_post_meta( $post->ID, '_ysf_total', true );
	$created = get_post_time( 'U', true, $post );
	$channel = ysf_order_channel( $post->ID );
	$table   = (string) get_post_meta( $post->ID, '_ysf_table', true );
	$kstate  = ysf_order_kitchen_state( $post->ID );
	$ostate  = get_post_meta( $post->ID, '_ysf_state', true );
	$ostate  = $ostate ? $ostate : 'pending';

	return array(
		'id'         => (int) $post->ID,
		'table'      => $table,
		'channel'    => $channel,
		'kitchen'    => $kstate,
		'state'      => $ostate,
		'total'      => $total,
		'totalLabel' => ysf_price( $total ),
		'note'       => (string) get_post_meta( $post->ID, '_ysf_note', true ),
		'waiter'     => (string) get_post_meta( $post->ID, '_ysf_waiter_name', true ),
		'guests'     => (int) get_post_meta( $post->ID, '_ysf_guests', true ),
		'name'       => (string) get_post_meta( $post->ID, '_ysf_name', true ),
		'phone'      => (string) get_post_meta( $post->ID, '_ysf_phone', true ),
		'address'    => (string) get_post_meta( $post->ID, '_ysf_address', true ),
		'time'       => (string) get_post_meta( $post->ID, '_ysf_time', true ),
		'source'     => (string) get_post_meta( $post->ID, '_ysf_source', true ),
		'payMethod'  => (string) get_post_meta( $post->ID, '_ysf_pay_method', true ),
		'created'    => (int) $created,
		'age'        => max( 0, time() - (int) $created ),
		'items'      => $lines,
	);
}

/**
 * Açık masa siparişleri.
 *
 * @param string $table Masa no (boşsa hepsi).
 * @return WP_Post[]
 */
function ysf_floor_open_tickets( $table = '' ) {
	$meta = array(
		array(
			'key'     => '_ysf_state',
			'value'   => array( 'pending', 'confirmed' ),
			'compare' => 'IN',
		),
	);

	if ( '' !== $table ) {
		$meta[] = array(
			'key'   => '_ysf_channel',
			'value' => 'table',
		);
		$meta[] = array(
			'key'   => '_ysf_table',
			'value' => (string) $table,
		);
	}

	return get_posts(
		array(
			'post_type'      => 'ysf_order',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);
}

/**
 * Masanın özet durumu.
 *
 * @param WP_Post[] $tickets Biletler.
 * @return string empty|busy|ready|bill
 */
function ysf_floor_table_status( $tickets ) {
	if ( empty( $tickets ) ) {
		return 'empty';
	}

	$has_open  = false;
	$has_new   = false;
	$all_ready = true;

	foreach ( $tickets as $ticket ) {
		$kitchen = ysf_order_kitchen_state( $ticket->ID );

		if ( 'served' === $kitchen ) {
			continue;
		}

		$has_open = true;

		if ( in_array( $kitchen, array( 'queued', 'cooking' ), true ) ) {
			$has_new   = true;
			$all_ready = false;
		} elseif ( 'ready' !== $kitchen ) {
			$all_ready = false;
		}
	}

	if ( ! $has_open ) {
		return 'bill';
	}

	if ( $has_new ) {
		return 'busy';
	}

	return $all_ready ? 'ready' : 'busy';
}

/**
 * Masaların açık bilet özeti.
 *
 * @return array
 */
function ysf_floor_tables_payload() {
	$open    = ysf_floor_open_tickets();
	$grouped = array();

	foreach ( $open as $ticket ) {
		if ( 'table' !== ysf_order_channel( $ticket->ID ) ) {
			continue;
		}

		$table = (string) get_post_meta( $ticket->ID, '_ysf_table', true );

		if ( '' === $table ) {
			continue;
		}

		if ( ! isset( $grouped[ $table ] ) ) {
			$grouped[ $table ] = array();
		}

		$grouped[ $table ][] = $ticket;
	}

	$tables = array();
	$open_n = 0;
	$open_t = 0.0;

	foreach ( ysf_table_numbers() as $number ) {
		$tickets = isset( $grouped[ $number ] ) ? $grouped[ $number ] : array();
		$total   = 0.0;

		foreach ( $tickets as $ticket ) {
			$total += (float) get_post_meta( $ticket->ID, '_ysf_total', true );
		}

		if ( $tickets ) {
			++$open_n;
			$open_t += $total;
		}

		$tables[] = array(
			'id'     => $number,
			'status' => ysf_floor_table_status( $tickets ),
			'count'  => count( $tickets ),
			'total'  => $total,
			'label'  => ysf_price( $total ),
		);
	}

	return array(
		'tables'    => $tables,
		'openCount' => $open_n,
		'openTotal' => $open_t,
		'openLabel' => ysf_price( $open_t ),
		'stamp'     => time(),
	);
}

/**
 * Masalar ve açık biletler.
 */
function ysf_ajax_floor_tables() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'tables' );

	wp_send_json_success( ysf_floor_tables_payload() );
}
add_action( 'wp_ajax_ysf_floor_tables', 'ysf_ajax_floor_tables' );

/**
 * Tek masanın biletleri.
 */
function ysf_ajax_floor_table() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'tables' );

	$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';

	if ( ! in_array( $table, ysf_table_numbers(), true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$tickets = array();
	$total   = 0.0;

	foreach ( ysf_floor_open_tickets( $table ) as $ticket ) {
		$row = ysf_floor_ticket_payload( $ticket );

		if ( ! $row ) {
			continue;
		}

		$tickets[] = $row;
		$total    += $row['total'];
	}

	wp_send_json_success(
		array(
			'table'   => $table,
			'status'  => ysf_floor_table_status( ysf_floor_open_tickets( $table ) ),
			'tickets' => $tickets,
			'total'   => $total,
			'label'   => ysf_price( $total ),
		)
	);
}
add_action( 'wp_ajax_ysf_floor_table', 'ysf_ajax_floor_table' );

/**
 * Garson menüsü.
 */
function ysf_ajax_floor_menu() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'tables' );

	$cats = get_terms(
		array(
			'taxonomy'   => 'ysf_menu_cat',
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $cats ) ) {
		$cats = array();
	}

	$cat_out = array();

	foreach ( $cats as $cat ) {
		$cat_out[] = array(
			'id'   => (int) $cat->term_id,
			'name' => $cat->name,
			'slug' => $cat->slug,
		);
	}

	$items = get_posts(
		array(
			'post_type'      => 'ysf_menu_item',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);

	$item_out = array();

	foreach ( $items as $item ) {
		$price = (float) get_post_meta( $item->ID, '_ysf_price', true );
		$sale  = function_exists( 'ysf_campaign_unit_price' ) ? ysf_campaign_unit_price( $item->ID, $price ) : $price;

		if ( $price <= 0 ) {
			continue;
		}

		$terms  = wp_get_post_terms( $item->ID, 'ysf_menu_cat' );
		$cat_id = ( $terms && ! is_wp_error( $terms ) ) ? (int) $terms[0]->term_id : 0;
		$thumb  = get_the_post_thumbnail_url( $item->ID, 'ysf-thumb' );
		$sold   = ysf_meta_flag( $item->ID, '_ysf_sold_out' );

		$item_out[] = array(
			'id'         => (int) $item->ID,
			'name'       => ysf_field( $item->ID, 'title' ),
			'price'      => $price,
			'priceLabel' => ysf_price( $sale ),
			'cat'        => $cat_id,
			'thumb'      => $thumb ? $thumb : '',
			'sold'       => $sold,
			'excerpt'    => wp_trim_words( wp_strip_all_tags( ysf_field( $item->ID, 'excerpt' ) ), 12, '…' ),
		);
	}

	wp_send_json_success(
		array(
			'cats'  => $cat_out,
			'items' => $item_out,
		)
	);
}
add_action( 'wp_ajax_ysf_floor_menu', 'ysf_ajax_floor_menu' );

/**
 * Masadan mutfağa sipariş gönderir.
 */
function ysf_ajax_floor_submit() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'tables' );

	$table  = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
	$note   = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$guests = isset( $_POST['guests'] ) ? absint( wp_unslash( $_POST['guests'] ) ) : 0;
	$items  = isset( $_POST['items'] ) ? json_decode( wp_unslash( $_POST['items'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( ! in_array( $table, ysf_table_numbers(), true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$parsed = ysf_floor_parse_items( $items );

	if ( is_wp_error( $parsed ) ) {
		wp_send_json_error( array( 'message' => $parsed->get_error_message() ), 400 );
	}

	$user     = wp_get_current_user();
	$types    = ysf_order_types();
	$total    = $parsed['subtotal'];
	$order_id = ysf_insert_request_post(
		array(
			'post_type'    => 'ysf_order',
			'post_status'  => 'publish',
			'post_title'   => sprintf(
				/* translators: 1: masa no, 2: tutar. */
				__( 'Masa %1$s — %2$s', 'ysffoodlab' ),
				$table,
				ysf_price( $total )
			),
			'post_content' => $note,
		)
	);

	if ( is_wp_error( $order_id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 500 );
	}

	$meta = array(
		'_ysf_name'          => sprintf( ysf_t( 'pos_table' ), $table ),
		'_ysf_phone'         => ysf_get_option( 'ysf_phone', '' ),
		'_ysf_order_type'    => $types['table'],
		'_ysf_channel'       => 'table',
		'_ysf_source'        => 'waiter',
		'_ysf_table'         => $table,
		'_ysf_note'          => $note,
		'_ysf_items'         => $parsed['lines'],
		'_ysf_subtotal'      => ysf_price( $total ),
		'_ysf_delivery_fee'  => ysf_t( 'free' ),
		'_ysf_total'         => $total,
		'_ysf_state'         => 'pending',
		'_ysf_kitchen_state' => 'queued',
		'_ysf_waiter_id'     => (int) $user->ID,
		'_ysf_waiter_name'   => $user->display_name,
		'_ysf_lang'          => ysf_lang(),
	);

	if ( $guests > 0 ) {
		$meta['_ysf_guests'] = min( 40, $guests );
	}

	foreach ( $meta as $key => $value ) {
		update_post_meta( $order_id, $key, $value );
	}

	ysf_attach_user_to_record( $order_id );

	wp_send_json_success(
		array(
			'message' => ysf_t( 'pos_sent' ),
			'orderId' => (int) $order_id,
			'ticket'  => ysf_floor_ticket_payload( $order_id ),
		)
	);
}
add_action( 'wp_ajax_ysf_floor_submit', 'ysf_ajax_floor_submit' );

/**
 * Masayı kapatır (hesap alındı).
 */
function ysf_ajax_floor_close() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'tables' );

	$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';

	if ( ! in_array( $table, ysf_table_numbers(), true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$tickets = ysf_floor_open_tickets( $table );

	if ( ! $tickets ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_no_tickets' ) ), 400 );
	}

	foreach ( $tickets as $ticket ) {
		ysf_cashier_mark_paid( $ticket->ID, '' );
	}

	wp_send_json_success( array( 'message' => ysf_t( 'pos_closed' ) ) );
}
add_action( 'wp_ajax_ysf_floor_close', 'ysf_ajax_floor_close' );

/**
 * Açık siparişleri başka masaya taşır.
 *
 * @param int    $order_id Sipariş.
 * @param string $table    Yeni masa no.
 */
function ysf_floor_reassign_table( $order_id, $table ) {
	$order_id = (int) $order_id;
	$total    = (float) get_post_meta( $order_id, '_ysf_total', true );

	update_post_meta( $order_id, '_ysf_table', $table );
	update_post_meta( $order_id, '_ysf_name', sprintf( ysf_t( 'pos_table' ), $table ) );

	wp_update_post(
		array(
			'ID'         => $order_id,
			'post_title' => sprintf(
				/* translators: 1: masa no, 2: tutar. */
				__( 'Masa %1$s — %2$s', 'ysffoodlab' ),
				$table,
				ysf_price( $total )
			),
		)
	);
}

/**
 * Masadaki açık siparişleri başka masaya taşır.
 */
function ysf_ajax_floor_move() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'tables' );

	$from = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
	$to   = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
	$nums = ysf_table_numbers();

	if ( ! in_array( $from, $nums, true ) || ! in_array( $to, $nums, true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( $from === $to ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_move_same' ) ), 400 );
	}

	$tickets = ysf_floor_open_tickets( $from );

	if ( ! $tickets ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_no_tickets' ) ), 400 );
	}

	foreach ( $tickets as $ticket ) {
		if ( 'table' !== ysf_order_channel( $ticket->ID ) ) {
			continue;
		}

		ysf_floor_reassign_table( $ticket->ID, $to );
	}

	wp_send_json_success(
		array(
			'message' => ysf_t( 'pos_moved' ),
			'table'   => $to,
		)
	);
}
add_action( 'wp_ajax_ysf_floor_move', 'ysf_ajax_floor_move' );

/**
 * Henüz mutfağa alınmamış bileti iptal eder.
 */
function ysf_ajax_floor_cancel() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'tables' );

	$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$ticket = $id ? get_post( $id ) : null;

	if ( ! $ticket || 'ysf_order' !== $ticket->post_type ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( 'table' !== ysf_order_channel( $id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$kitchen = ysf_order_kitchen_state( $id );

	if ( 'queued' !== $kitchen ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_cancel_late' ) ), 400 );
	}

	update_post_meta( $id, '_ysf_state', 'cancelled' );
	update_post_meta( $id, '_ysf_kitchen_state', 'served' );

	wp_send_json_success( array( 'message' => ysf_t( 'pos_cancelled' ) ) );
}
add_action( 'wp_ajax_ysf_floor_cancel', 'ysf_ajax_floor_cancel' );

/**
 * Siparişi tahsil edildi olarak işaretler.
 *
 * @param int    $order_id Sipariş.
 * @param string $method   cash|card veya boş (garson kapatması).
 */
function ysf_cashier_mark_paid( $order_id, $method = '' ) {
	$order_id = (int) $order_id;
	$user     = wp_get_current_user();
	$methods  = ysf_cashier_pay_methods();

	update_post_meta( $order_id, '_ysf_state', 'done' );
	update_post_meta( $order_id, '_ysf_kitchen_state', 'served' );
	update_post_meta( $order_id, '_ysf_paid_at', time() );

	if ( $method && isset( $methods[ $method ] ) ) {
		update_post_meta( $order_id, '_ysf_pay_method', $method );
	}

	if ( $user && $user->exists() ) {
		update_post_meta( $order_id, '_ysf_cashier_id', (int) $user->ID );
		update_post_meta( $order_id, '_ysf_cashier_name', $user->display_name );
	}
}

/**
 * Günün kasa özeti (WordPress saat dilimi).
 *
 * @return array
 */
function ysf_cashier_today_summary() {
	$tz    = wp_timezone();
	$start = new DateTimeImmutable( 'today', $tz );
	$end   = $start->modify( '+1 day' );
	$from  = $start->getTimestamp();
	$to    = $end->getTimestamp();

	$posts = get_posts(
		array(
			'post_type'      => 'ysf_order',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_ysf_paid_at',
					'value'   => array( $from, $to - 1 ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	$cash  = 0.0;
	$card  = 0.0;
	$count = 0;

	foreach ( $posts as $post ) {
		if ( 'done' !== get_post_meta( $post->ID, '_ysf_state', true ) ) {
			continue;
		}

		$total  = (float) get_post_meta( $post->ID, '_ysf_total', true );
		$method = (string) get_post_meta( $post->ID, '_ysf_pay_method', true );

		if ( 'cash' === $method ) {
			$cash += $total;
			++$count;
		} elseif ( 'card' === $method ) {
			$card += $total;
			++$count;
		}
	}

	$taken = $cash + $card;

	return array(
		'cash'       => $cash,
		'card'       => $card,
		'taken'      => $taken,
		'count'      => $count,
		'cashLabel'  => ysf_price( $cash ),
		'cardLabel'  => ysf_price( $card ),
		'takenLabel' => ysf_price( $taken ),
		'day'        => $start->format( 'Y-m-d' ),
	);
}

/**
 * Kasiyer masa listesi ve günlük özet.
 */
function ysf_ajax_cashier_tables() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'cashier' );

	$payload            = ysf_floor_tables_payload();
	$payload['summary'] = ysf_cashier_today_summary();

	wp_send_json_success( $payload );
}
add_action( 'wp_ajax_ysf_cashier_tables', 'ysf_ajax_cashier_tables' );

/**
 * Kasiyer masa hesabı.
 */
function ysf_ajax_cashier_table() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'cashier' );

	$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';

	if ( ! in_array( $table, ysf_table_numbers(), true ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$tickets = array();
	$total   = 0.0;

	foreach ( ysf_floor_open_tickets( $table ) as $ticket ) {
		$row = ysf_floor_ticket_payload( $ticket );

		if ( ! $row ) {
			continue;
		}

		$tickets[] = $row;
		$total    += $row['total'];
	}

	wp_send_json_success(
		array(
			'table'   => $table,
			'status'  => ysf_floor_table_status( ysf_floor_open_tickets( $table ) ),
			'tickets' => $tickets,
			'total'   => $total,
			'label'   => ysf_price( $total ),
		)
	);
}
add_action( 'wp_ajax_ysf_cashier_table', 'ysf_ajax_cashier_table' );

/**
 * Masayı nakit veya kart ile tahsil eder.
 */
function ysf_ajax_cashier_pay() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'cashier' );

	$table  = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
	$method = isset( $_POST['method'] ) ? sanitize_key( wp_unslash( $_POST['method'] ) ) : '';

	if ( ! in_array( $table, ysf_table_numbers(), true ) || ! isset( ysf_cashier_pay_methods()[ $method ] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$tickets = ysf_floor_open_tickets( $table );

	if ( ! $tickets ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_no_tickets' ) ), 400 );
	}

	foreach ( $tickets as $ticket ) {
		ysf_cashier_mark_paid( $ticket->ID, $method );
	}

	wp_send_json_success(
		array(
			'message' => sprintf( ysf_t( 'cash_paid' ), ysf_cashier_pay_methods()[ $method ] ),
			'summary' => ysf_cashier_today_summary(),
		)
	);
}
add_action( 'wp_ajax_ysf_cashier_pay', 'ysf_ajax_cashier_pay' );

/**
 * Mutfak ekranı biletleri.
 */
function ysf_ajax_kds_tickets() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'kds' );

	$include_online = ysf_is_flag_value( ysf_get_option( 'ysf_kds_online', true ), true );
	$posts = get_posts(
		array(
			'post_type'      => 'ysf_order',
			'post_status'    => array( 'publish', 'pending' ),
			'posts_per_page' => 120,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_ysf_state',
					'value'   => array( 'pending', 'confirmed' ),
					'compare' => 'IN',
				),
			),
		)
	);

	$tickets = array();

	foreach ( $posts as $post ) {
		$channel = ysf_order_channel( $post->ID );
		$kitchen = ysf_order_kitchen_state( $post->ID );

		if ( in_array( $kitchen, array( 'served' ), true ) ) {
			continue;
		}

		if ( 'table' !== $channel && ! $include_online ) {
			continue;
		}

		$row = ysf_floor_ticket_payload( $post );

		if ( $row ) {
			$tickets[] = $row;
		}
	}

	wp_send_json_success(
		array(
			'tickets' => $tickets,
			'stamp'   => time(),
		)
	);
}
add_action( 'wp_ajax_ysf_kds_tickets', 'ysf_ajax_kds_tickets' );

/**
 * Mutfak durumunu günceller.
 */
function ysf_ajax_kds_state() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'kds' );

	$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$state = isset( $_POST['state'] ) ? sanitize_key( wp_unslash( $_POST['state'] ) ) : '';
	$post  = $id ? get_post( $id ) : null;

	if ( ! $post || 'ysf_order' !== $post->post_type || ! isset( ysf_kitchen_states()[ $state ] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	update_post_meta( $id, '_ysf_kitchen_state', $state );

	if ( 'served' === $state && 'table' !== ysf_order_channel( $id ) ) {
		update_post_meta( $id, '_ysf_state', 'done' );
	}

	wp_send_json_success(
		array(
			'message' => ysf_t( 'kit_saved' ),
			'ticket'  => ysf_floor_ticket_payload( $id ),
		)
	);
}
add_action( 'wp_ajax_ysf_kds_state', 'ysf_ajax_kds_state' );

/**
 * WhatsApp’ta iletilmeyen veya iptal edilen online siparişi hazırlamadan kapatır.
 */
function ysf_ajax_kds_close() {
	check_ajax_referer( 'ysf_public', 'nonce' );
	ysf_floor_guard( 'kds' );

	$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$post = $id ? get_post( $id ) : null;

	if ( ! $post || 'ysf_order' !== $post->post_type ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( 'table' === ysf_order_channel( $id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( 'queued' !== ysf_order_kitchen_state( $id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_cancel_late' ) ), 400 );
	}

	update_post_meta( $id, '_ysf_state', 'cancelled' );
	update_post_meta( $id, '_ysf_kitchen_state', 'served' );
	update_post_meta( $id, '_ysf_close_reason', 'whatsapp_undelivered' );

	wp_send_json_success( array( 'message' => ysf_t( 'kds_closed' ) ) );
}
add_action( 'wp_ajax_ysf_kds_close', 'ysf_ajax_kds_close' );
