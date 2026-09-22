<?php
/**
 * İçerik tipleri: menü ürünleri, kampanya/duyuru, rezervasyon, sipariş.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * İçerik tiplerini ve taksonomileri kaydeder.
 */
function ysf_register_post_types() {
	// --- Menü ürünleri -----------------------------------------------------
	register_post_type(
		'ysf_menu_item',
		array(
			'labels'        => array(
				'name'                  => __( 'Menü', 'ysffoodlab' ),
				'singular_name'         => __( 'Menü Ürünü', 'ysffoodlab' ),
				'add_new'               => __( 'Yeni Ürün Ekle', 'ysffoodlab' ),
				'add_new_item'          => __( 'Yeni Menü Ürünü Ekle', 'ysffoodlab' ),
				'edit_item'             => __( 'Ürünü Düzenle', 'ysffoodlab' ),
				'new_item'              => __( 'Yeni Ürün', 'ysffoodlab' ),
				'view_item'             => __( 'Ürünü Görüntüle', 'ysffoodlab' ),
				'search_items'          => __( 'Ürün Ara', 'ysffoodlab' ),
				'not_found'             => __( 'Ürün bulunamadı.', 'ysffoodlab' ),
				'featured_image'        => __( 'Ürün Fotoğrafı', 'ysffoodlab' ),
				'set_featured_image'    => __( 'Ürün fotoğrafı ekle', 'ysffoodlab' ),
				'menu_name'             => __( 'Menü Yönetimi', 'ysffoodlab' ),
				'item_published'        => __( 'Ürün yayınlandı.', 'ysffoodlab' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'menu_icon'     => 'dashicons-food',
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'custom-fields' ),
			'rewrite'       => array( 'slug' => 'lezzet' ),
			'show_in_rest'  => true,
			// Cekirdegin /wp/v2/menu-items ucuyla cakismamasi icin on ekli.
			'rest_base'     => 'ysf-menu-items',
		)
	);

	register_taxonomy(
		'ysf_menu_cat',
		array( 'ysf_menu_item' ),
		array(
			'labels'            => array(
				'name'          => __( 'Menü Kategorileri', 'ysffoodlab' ),
				'singular_name' => __( 'Menü Kategorisi', 'ysffoodlab' ),
				'add_new_item'  => __( 'Yeni Kategori Ekle', 'ysffoodlab' ),
				'edit_item'     => __( 'Kategoriyi Düzenle', 'ysffoodlab' ),
				'menu_name'     => __( 'Kategoriler', 'ysffoodlab' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'menu-kategori' ),
			'show_in_rest'      => true,
			'rest_base'         => 'ysf-menu-categories',
		)
	);

	// --- Kampanya ve duyurular --------------------------------------------
	register_post_type(
		'ysf_campaign',
		array(
			'labels'        => array(
				'name'               => __( 'Kampanya & Duyuru', 'ysffoodlab' ),
				'singular_name'      => __( 'Kampanya', 'ysffoodlab' ),
				'add_new'            => __( 'Yeni Ekle', 'ysffoodlab' ),
				'add_new_item'       => __( 'Yeni Kampanya / Duyuru', 'ysffoodlab' ),
				'edit_item'          => __( 'Düzenle', 'ysffoodlab' ),
				'not_found'          => __( 'Kayıt bulunamadı.', 'ysffoodlab' ),
				'featured_image'     => __( 'Kampanya Görseli', 'ysffoodlab' ),
				'set_featured_image' => __( 'Kampanya görseli ekle', 'ysffoodlab' ),
			),
			'public'             => true,
			'has_archive'        => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'menu_icon'          => 'dashicons-megaphone',
			'menu_position'      => 6,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'custom-fields' ),
			'rewrite'            => array( 'slug' => 'kampanya' ),
			'show_in_rest'       => true,
			'rest_base'          => 'ysf-campaigns',
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'capabilities'       => array(
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'delete_posts'           => 'manage_options',
				'publish_posts'          => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'edit_private_posts'     => 'manage_options',
				'edit_published_posts'   => 'manage_options',
				'create_posts'           => 'manage_options',
			),
		)
	);

	// --- Rezervasyonlar (sadece panelde görünür) --------------------------
	register_post_type(
		'ysf_reservation',
		array(
			'labels'          => array(
				'name'          => __( 'Rezervasyonlar', 'ysffoodlab' ),
				'singular_name' => __( 'Rezervasyon', 'ysffoodlab' ),
				'edit_item'     => __( 'Rezervasyonu Görüntüle', 'ysffoodlab' ),
				'not_found'     => __( 'Rezervasyon yok.', 'ysffoodlab' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'menu_icon'       => 'dashicons-calendar-alt',
			'menu_position'   => 7,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'show_in_rest'    => false,
		)
	);

	// --- Siparişler (sadece panelde görünür) ------------------------------
	register_post_type(
		'ysf_order',
		array(
			'labels'          => array(
				'name'          => __( 'Siparişler', 'ysffoodlab' ),
				'singular_name' => __( 'Sipariş', 'ysffoodlab' ),
				'edit_item'     => __( 'Siparişi Görüntüle', 'ysffoodlab' ),
				'not_found'     => __( 'Sipariş yok.', 'ysffoodlab' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'menu_icon'       => 'dashicons-cart',
			'menu_position'   => 8,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'show_in_rest'    => false,
		)
	);
}
add_action( 'init', 'ysf_register_post_types' );

/**
 * Talep durumu etiketleri.
 *
 * @return array
 */
function ysf_request_states() {
	return array(
		'pending'   => __( 'Bekliyor', 'ysffoodlab' ),
		'confirmed' => __( 'Onaylandı', 'ysffoodlab' ),
		'done'      => __( 'Tamamlandı', 'ysffoodlab' ),
		'cancelled' => __( 'İptal edildi', 'ysffoodlab' ),
	);
}

/**
 * Menü ürünleri panelde sıra numarasına göre listelenir.
 *
 * @param WP_Query $query Sorgu.
 */
function ysf_admin_order_menu_items( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'ysf_menu_item' === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order title' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'ysf_admin_order_menu_items' );

/**
 * Rezervasyon listesinde tarih/kişi kolonları.
 *
 * @param array $columns Kolonlar.
 * @return array
 */
function ysf_reservation_columns( $columns ) {
	return array(
		'cb'            => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'         => __( 'Misafir', 'ysffoodlab' ),
		'ysf_res_when'  => __( 'Tarih / Saat', 'ysffoodlab' ),
		'ysf_res_party' => __( 'Kişi', 'ysffoodlab' ),
		'ysf_res_phone' => __( 'Telefon', 'ysffoodlab' ),
		'ysf_res_occ'   => __( 'Özel Gün', 'ysffoodlab' ),
		'ysf_status'    => __( 'Durum', 'ysffoodlab' ),
		'date'          => __( 'Geliş', 'ysffoodlab' ),
	);
}
add_filter( 'manage_ysf_reservation_posts_columns', 'ysf_reservation_columns' );

/**
 * Sipariş listesi kolonları.
 *
 * @param array $columns Kolonlar.
 * @return array
 */
function ysf_order_columns( $columns ) {
	return array(
		'cb'              => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'           => __( 'Sipariş', 'ysffoodlab' ),
		'ysf_order_type'  => __( 'Tip', 'ysffoodlab' ),
		'ysf_order_table' => __( 'Masa', 'ysffoodlab' ),
		'ysf_order_total' => __( 'Tutar', 'ysffoodlab' ),
		'ysf_res_phone'   => __( 'Telefon', 'ysffoodlab' ),
		'ysf_status'      => __( 'Durum', 'ysffoodlab' ),
		'date'            => __( 'Geliş', 'ysffoodlab' ),
	);
}
add_filter( 'manage_ysf_order_posts_columns', 'ysf_order_columns' );

/**
 * Özel kolon içerikleri.
 *
 * @param string $column  Kolon adı.
 * @param int    $post_id Gönderi kimliği.
 */
function ysf_render_admin_columns( $column, $post_id ) {
	switch ( $column ) {
		case 'ysf_res_when':
			$date = get_post_meta( $post_id, '_ysf_date', true );
			$time = get_post_meta( $post_id, '_ysf_time', true );
			echo esc_html( trim( $date . ' ' . $time ) );
			break;

		case 'ysf_res_party':
			echo esc_html( (string) get_post_meta( $post_id, '_ysf_guests', true ) );
			break;

		case 'ysf_res_phone':
			$phone = get_post_meta( $post_id, '_ysf_phone', true );
			if ( $phone ) {
				printf( '<a href="tel:%1$s">%2$s</a>', esc_attr( ysf_digits( $phone ) ), esc_html( $phone ) );
			}
			break;

		case 'ysf_res_occ':
			echo esc_html( (string) get_post_meta( $post_id, '_ysf_occasion', true ) );
			break;

		case 'ysf_order_type':
			echo esc_html( (string) get_post_meta( $post_id, '_ysf_order_type', true ) );
			break;

		case 'ysf_order_table':
			echo esc_html( (string) get_post_meta( $post_id, '_ysf_table', true ) );
			break;

		case 'ysf_order_total':
			$total = (float) get_post_meta( $post_id, '_ysf_total', true );
			echo esc_html( ysf_price( $total ) );
			break;

		case 'ysf_status':
			$state  = get_post_meta( $post_id, '_ysf_state', true );
			$states = ysf_request_states();
			$state  = isset( $states[ $state ] ) ? $state : 'pending';
			printf(
				'<span class="%1$s">%2$s</span>',
				esc_attr( in_array( $state, array( 'confirmed', 'done' ), true ) ? 'ysf-badge-done' : 'ysf-badge-pending' ),
				esc_html( $states[ $state ] )
			);
			break;
	}
}
add_action( 'manage_ysf_reservation_posts_custom_column', 'ysf_render_admin_columns', 10, 2 );
add_action( 'manage_ysf_order_posts_custom_column', 'ysf_render_admin_columns', 10, 2 );

/**
 * Menü listesinde fiyat kolonu.
 *
 * @param array $columns Kolonlar.
 * @return array
 */
function ysf_menu_item_columns( $columns ) {
	$new = array();

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['ysf_price'] = __( 'Fiyat', 'ysffoodlab' );
			$new['ysf_en']    = __( 'EN Çeviri', 'ysffoodlab' );
		}
	}

	return $new;
}
add_filter( 'manage_ysf_menu_item_posts_columns', 'ysf_menu_item_columns' );

/**
 * Menü listesi kolon içerikleri.
 *
 * @param string $column  Kolon adı.
 * @param int    $post_id Gönderi kimliği.
 */
function ysf_render_menu_item_columns( $column, $post_id ) {
	if ( 'ysf_price' === $column ) {
		echo esc_html( ysf_price( (float) get_post_meta( $post_id, '_ysf_price', true ) ) );
	}

	if ( 'ysf_en' === $column ) {
		$has = get_post_meta( $post_id, '_ysf_title_en', true );
		printf(
			'<span class="%1$s">%2$s</span>',
			$has ? 'ysf-badge-done' : 'ysf-badge-pending',
			$has ? esc_html__( 'Var', 'ysffoodlab' ) : esc_html__( 'Eksik', 'ysffoodlab' )
		);
	}
}
add_action( 'manage_ysf_menu_item_posts_custom_column', 'ysf_render_menu_item_columns', 10, 2 );
