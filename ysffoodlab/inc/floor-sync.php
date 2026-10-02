<?php
/**
 * Masa servisi yardımcıları: eşzamanlı işlem kilidi, değişiklik sayacı,
 * misafirden "Garson çağır / Hesap iste" çağrıları ve masa QR sayfası.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adlandırılmış veritabanı kilidi alır.
 *
 * Aynı masada kasiyer tahsilatı ile garsonun masa taşıması gibi işlemler
 * aynı anda gelirse biri diğerini bekler. MySQL/MariaDB GET_LOCK bağlantıya
 * bağlıdır; istek bitince kendiliğinden bırakılır. Desteklemeyen veritabanında
 * kilitsiz devam edilir.
 *
 * @param string $name    Kilit adı.
 * @param int    $timeout Bekleme süresi (sn).
 * @return bool Kilit alınamadıysa false.
 */
function ysf_lock( $name, $timeout = 5 ) {
	global $wpdb;

	$suppress = $wpdb->suppress_errors( true );
	$result   = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', 'ysf_' . $name, (int) $timeout ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->suppress_errors( $suppress );

	// Yalnızca "0" zaman aşımıdır; NULL ve emülatörlerin döndürdüğü değerler kilitsiz devam eder.
	return '0' !== (string) $result;
}

/**
 * Kilidi bırakır.
 *
 * @param string $name Kilit adı.
 */
function ysf_unlock( $name ) {
	global $wpdb;

	$suppress = $wpdb->suppress_errors( true );
	$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'ysf_' . $name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->suppress_errors( $suppress );
}

/**
 * Masa kilidi alır; alınamazsa JSON hata ile çıkar.
 *
 * @param string $table Masa no.
 * @return string Kilit adı.
 */
function ysf_lock_table_or_fail( $table ) {
	$name = 'table_' . md5( (string) $table );

	if ( ! ysf_lock( $name ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_busy_retry' ) ), 409 );
	}

	return $name;
}

/**
 * Sipariş kilidi alır; alınamazsa JSON hata ile çıkar.
 *
 * @param int $order_id Sipariş.
 * @return string Kilit adı.
 */
function ysf_lock_order_or_fail( $order_id ) {
	$name = 'order_' . (int) $order_id;

	if ( ! ysf_lock( $name ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_busy_retry' ) ), 409 );
	}

	return $name;
}

/**
 * Siparişlerde değişiklik sayacı.
 *
 * Personel ekranları 4 sn'de bir sorar; sayaç değişmediyse ağır sorgular
 * çalıştırılmadan "değişiklik yok" cevabı döner.
 *
 * @return string
 */
function ysf_floor_rev() {
	return (string) get_option( 'ysf_floor_rev', '0' );
}

/**
 * Bu istekte sipariş verisi değişti; sayaç istek sonunda artırılır.
 *
 * @param bool $flag İşaretle.
 * @return bool
 */
function ysf_floor_dirty( $flag = true ) {
	static $dirty = false;

	if ( $flag ) {
		$dirty = true;
	}

	return $dirty;
}

/**
 * Sipariş meta değişikliklerini izler.
 *
 * @param int    $meta_id   Meta.
 * @param int    $object_id Yazı.
 */
function ysf_floor_watch_meta( $meta_id, $object_id ) {
	unset( $meta_id );

	if ( 'ysf_order' === get_post_type( (int) $object_id ) ) {
		ysf_floor_dirty();
	}
}
add_action( 'added_post_meta', 'ysf_floor_watch_meta', 10, 2 );
add_action( 'updated_post_meta', 'ysf_floor_watch_meta', 10, 2 );
add_action( 'deleted_post_meta', 'ysf_floor_watch_meta', 10, 2 );

/**
 * Sipariş kaydı değişince işaretler.
 */
function ysf_floor_watch_post() {
	ysf_floor_dirty();
}
add_action( 'save_post_ysf_order', 'ysf_floor_watch_post' );

/**
 * İstek sonunda sayacı artırır.
 */
function ysf_floor_flush_rev() {
	if ( ysf_floor_dirty( false ) ) {
		update_option( 'ysf_floor_rev', (string) microtime( true ), true );
	}
}
add_action( 'shutdown', 'ysf_floor_flush_rev', 0 );

/**
 * İstemcinin bildiği sayaç hâlâ geçerliyse kısa cevap verip çıkar.
 */
function ysf_floor_maybe_unchanged() {
	$since = isset( $_POST['since'] ) ? sanitize_text_field( wp_unslash( $_POST['since'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( '' !== $since && $since === ysf_floor_rev() ) {
		wp_send_json_success(
			array(
				'unchanged' => true,
				'rev'       => $since,
			)
		);
	}
}

/* -------------------------------------------------------------------------
 * Masa çağrıları
 * ---------------------------------------------------------------------- */

/**
 * Çağrı türleri.
 *
 * @return array
 */
function ysf_table_call_types() {
	return array(
		'waiter' => ysf_t( 'call_waiter' ),
		'bill'   => ysf_t( 'call_bill' ),
	);
}

/**
 * Açık çağrılar (30 dk sonra kendiliğinden düşer).
 *
 * @return array[]
 */
function ysf_table_calls() {
	$calls = get_option( 'ysf_table_calls', array() );
	$calls = is_array( $calls ) ? $calls : array();
	$now   = time();

	return array_values(
		array_filter(
			$calls,
			static function ( $call ) use ( $now ) {
				return is_array( $call ) && ! empty( $call['at'] ) && ( $now - (int) $call['at'] ) < 30 * MINUTE_IN_SECONDS;
			}
		)
	);
}

/**
 * Çağrı listesini kaydeder.
 *
 * @param array[] $calls Çağrılar.
 */
function ysf_save_table_calls( $calls ) {
	update_option( 'ysf_table_calls', array_slice( array_values( $calls ), -100 ), false );
	update_option( 'ysf_floor_rev', (string) microtime( true ), true );
}

/**
 * Masaya çağrı ekler; aynı masa ve türde açık çağrı varsa saatini yeniler.
 *
 * @param string $table  Masa.
 * @param string $type   waiter|bill.
 * @param string $source guest|waiter.
 * @return array Çağrı.
 */
function ysf_add_table_call( $table, $type, $source = 'guest' ) {
	ysf_lock( 'table_calls' );

	$calls = ysf_table_calls();
	$found = null;

	foreach ( $calls as $index => $call ) {
		if ( (string) $call['table'] === (string) $table && $call['type'] === $type ) {
			$calls[ $index ]['at']     = time();
			$calls[ $index ]['source'] = $source;
			$found                     = $calls[ $index ];
		}
	}

	if ( ! $found ) {
		$found   = array(
			'id'     => wp_generate_password( 10, false ),
			'table'  => (string) $table,
			'type'   => $type,
			'source' => $source,
			'at'     => time(),
		);
		$calls[] = $found;
	}

	ysf_save_table_calls( $calls );
	ysf_unlock( 'table_calls' );

	return $found;
}

/**
 * Çağrıları istemciye uygun hâle getirir.
 *
 * @param string $only Yalnızca bu tür (boşsa hepsi).
 * @return array[]
 */
function ysf_table_calls_payload( $only = '' ) {
	$types = ysf_table_call_types();
	$out   = array();

	foreach ( ysf_table_calls() as $call ) {
		if ( $only && $call['type'] !== $only ) {
			continue;
		}

		$out[] = array(
			'id'     => $call['id'],
			'table'  => $call['table'],
			'type'   => $call['type'],
			'label'  => isset( $types[ $call['type'] ] ) ? $types[ $call['type'] ] : $call['type'],
			'source' => $call['source'],
			'age'    => max( 0, time() - (int) $call['at'] ),
		);
	}

	return $out;
}

/**
 * Misafir masadan garson çağırır veya hesap ister.
 */
function ysf_ajax_table_call() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ! ysf_floor_enabled() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_disabled' ) ), 403 );
	}

	$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
	$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';

	if ( ! in_array( $table, ysf_table_numbers(), true ) || ! isset( ysf_table_call_types()[ $type ] ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	if ( ysf_rate_limited( 'table_call', 6, 600 ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'acc_too_many' ) ), 429 );
	}

	ysf_add_table_call( $table, $type, 'guest' );

	wp_send_json_success(
		array(
			'message' => 'bill' === $type ? ysf_t( 'call_bill_sent' ) : ysf_t( 'call_waiter_sent' ),
		)
	);
}
add_action( 'wp_ajax_ysf_table_call', 'ysf_ajax_table_call' );
add_action( 'wp_ajax_nopriv_ysf_table_call', 'ysf_ajax_table_call' );

/**
 * Personel çağrıyı tamamlar.
 */
function ysf_ajax_table_call_done() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ! ysf_can_take_orders() && ! ysf_can_cashier() ) {
		wp_send_json_error( array( 'message' => ysf_t( 'pos_forbidden' ) ), 403 );
	}

	$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';

	ysf_lock( 'table_calls' );

	$calls = array_filter(
		ysf_table_calls(),
		static function ( $call ) use ( $id ) {
			return $call['id'] !== $id;
		}
	);

	ysf_save_table_calls( $calls );
	ysf_unlock( 'table_calls' );

	wp_send_json_success( array( 'calls' => ysf_table_calls_payload() ) );
}
add_action( 'wp_ajax_ysf_table_call_done', 'ysf_ajax_table_call_done' );

/**
 * Masa ödenince o masanın hesap çağrısı da kapanır.
 *
 * @param string $table Masa.
 */
function ysf_clear_table_calls( $table ) {
	ysf_lock( 'table_calls' );

	$calls = array_filter(
		ysf_table_calls(),
		static function ( $call ) use ( $table ) {
			return (string) $call['table'] !== (string) $table;
		}
	);

	ysf_save_table_calls( $calls );
	ysf_unlock( 'table_calls' );
}

/**
 * Menü sayfasının masa bağlantısı.
 *
 * @param string $table Masa.
 * @return string
 */
function ysf_table_menu_url( $table ) {
	$menu = ysf_get_page_url_by_template( 'template-menu.php' );
	$menu = $menu ? $menu : home_url( '/' );

	return add_query_arg( 'masa', rawurlencode( (string) $table ), $menu );
}

/**
 * Misafir ekranında masa servisi gösterilsin mi?
 *
 * @return bool
 */
function ysf_show_table_service() {
	return ysf_floor_enabled() && ( is_page_template( 'template-menu.php' ) || is_page_template( 'template-order.php' ) );
}

/**
 * Görünüm → Masa QR Kodları sayfasını ekler.
 */
function ysf_table_qr_menu() {
	add_theme_page(
		__( 'Masa QR Kodları', 'ysffoodlab' ),
		__( 'Masa QR Kodları', 'ysffoodlab' ),
		'edit_theme_options',
		'ysf-table-qr',
		'ysf_table_qr_page'
	);
}
add_action( 'admin_menu', 'ysf_table_qr_menu' );

/**
 * Her masa için yazdırılabilir QR kartları.
 */
function ysf_table_qr_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	?>
	<div class="wrap ysf-qr-wrap">
		<h1><?php esc_html_e( 'Masa QR Kodları', 'ysffoodlab' ); ?></h1>
		<p class="ysf-qr-lead">
			<?php esc_html_e( 'Her kod menüyü ilgili masa numarasıyla açar; misafir tek dokunuşla garson çağırabilir veya hesap isteyebilir. Yazdırmak için Ctrl+P.', 'ysffoodlab' ); ?>
		</p>
		<div class="ysf-qr-grid">
			<?php foreach ( ysf_table_numbers() as $ysf_table ) : ?>
				<?php $ysf_url = ysf_table_menu_url( $ysf_table ); ?>
				<figure class="ysf-qr-card">
					<?php echo ysf_qr_svg( $ysf_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Yerel SVG üreticisi. ?>
					<figcaption>
						<strong><?php echo esc_html( sprintf( ysf_t( 'pos_table' ), $ysf_table ) ); ?></strong>
						<span><?php bloginfo( 'name' ); ?></span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
	<style>
		.ysf-qr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-top:16px}
		.ysf-qr-card{margin:0;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;text-align:center;break-inside:avoid}
		.ysf-qr-card svg{width:100%;height:auto;max-width:180px}
		.ysf-qr-card figcaption{display:grid;gap:2px;margin-top:8px}
		.ysf-qr-card strong{font-size:18px}
		@media print{#adminmenumain,#wpadminbar,#wpfooter,.ysf-qr-lead,.notice,.wrap>h1{display:none!important}#wpcontent{margin:0!important;padding:0!important}.ysf-qr-grid{grid-template-columns:repeat(3,1fr)}}
	</style>
	<?php
}

/**
 * Personel (yönetici olmayan garson/kasiyer/mutfak) oturumunun en uzun süresi.
 *
 * Ortak tabletlerde "beni hatırla" işaretli kalsa bile oturum bir vardiyadan uzun sürmez.
 *
 * @param int  $length   Saniye.
 * @param int  $user_id  Kullanıcı.
 * @param bool $remember Beni hatırla.
 * @return int
 */
function ysf_staff_cookie_expiration( $length, $user_id, $remember ) {
	unset( $remember );

	$user = get_userdata( (int) $user_id );

	if ( ! $user || user_can( $user, 'manage_options' ) ) {
		return $length;
	}

	$is_staff = ysf_can_take_orders( $user->ID ) || ysf_can_cashier( $user->ID ) || ysf_can_view_kds( $user->ID ) || ( function_exists( 'ysf_can_manage_menu' ) && ysf_can_manage_menu( $user->ID ) );

	if ( ! $is_staff ) {
		return $length;
	}

	$hours = max( 1, min( 72, (int) ysf_get_option( 'ysf_staff_session_hours', 16 ) ) );

	return min( (int) $length, $hours * HOUR_IN_SECONDS );
}
add_filter( 'auth_cookie_expiration', 'ysf_staff_cookie_expiration', 20, 3 );
