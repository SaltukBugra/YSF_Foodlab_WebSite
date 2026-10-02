<?php
/**
 * Online sipariş takibi, puanlama ve damga kartı.
 *
 * Sipariş verildiğinde müşteriye gizli anahtarlı bir takip adresi verilir:
 * /?ysf_siparis=ID&k=ANAHTAR. Sayfa durumu kısa aralıklarla sorar, sipariş
 * hazır olunca sesli/tarayıcı bildirimi verir ve teslimden sonra puan ister.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Siparişin takip anahtarı (yoksa üretir).
 *
 * @param int $order_id Sipariş.
 * @return string
 */
function ysf_order_tracking_key( $order_id ) {
	$key = (string) get_post_meta( $order_id, '_ysf_track_key', true );

	if ( ! $key ) {
		$key = wp_generate_password( 24, false, false );
		update_post_meta( $order_id, '_ysf_track_key', $key );
	}

	return $key;
}

/**
 * Siparişin takip adresi.
 *
 * @param int $order_id Sipariş.
 * @return string
 */
function ysf_order_tracking_url( $order_id ) {
	return add_query_arg(
		array(
			'ysf_siparis' => (int) $order_id,
			'k'           => ysf_order_tracking_key( $order_id ),
		),
		home_url( '/' )
	);
}

/**
 * Adresten gelen sipariş ve anahtarı doğrular.
 *
 * @param mixed $id  Sipariş no.
 * @param mixed $key Anahtar.
 * @return int Geçerliyse sipariş no, değilse 0.
 */
function ysf_order_tracking_verify( $id, $key ) {
	$id  = absint( $id );
	$key = is_string( $key ) ? sanitize_text_field( $key ) : '';

	if ( ! $id || strlen( $key ) < 20 || 'ysf_order' !== get_post_type( $id ) ) {
		return 0;
	}

	$stored = (string) get_post_meta( $id, '_ysf_track_key', true );

	return ( $stored && hash_equals( $stored, $key ) ) ? $id : 0;
}

/**
 * Siparişin müşteriye gösterilen adımı.
 *
 * 0 alındı, 1 onaylandı, 2 hazırlanıyor, 3 hazır, 4 teslim edildi.
 *
 * @param int $order_id Sipariş.
 * @return int -1 iptal.
 */
function ysf_order_tracking_step( $order_id ) {
	$state   = (string) get_post_meta( $order_id, '_ysf_state', true );
	$kitchen = function_exists( 'ysf_order_kitchen_state' ) ? ysf_order_kitchen_state( $order_id ) : (string) get_post_meta( $order_id, '_ysf_kitchen_state', true );

	if ( 'cancelled' === $state ) {
		return -1;
	}

	if ( 'done' === $state || 'served' === $kitchen ) {
		return 4;
	}

	if ( 'ready' === $kitchen ) {
		return 3;
	}

	if ( 'cooking' === $kitchen ) {
		return 2;
	}

	if ( 'confirmed' === $state ) {
		return 1;
	}

	return 0;
}

/**
 * Kanal için adım adları.
 *
 * @param string $channel delivery|pickup|table.
 * @return string[]
 */
function ysf_order_tracking_labels( $channel ) {
	$last = 'trk_step_delivered';

	if ( 'pickup' === $channel ) {
		$last = 'trk_step_picked';
	} elseif ( 'table' === $channel ) {
		$last = 'trk_step_served';
	}

	$ready = 'pickup' === $channel ? 'trk_step_ready_pickup' : ( 'delivery' === $channel ? 'trk_step_ready_delivery' : 'trk_step_ready' );

	return array(
		ysf_t( 'trk_step_received' ),
		ysf_t( 'trk_step_confirmed' ),
		ysf_t( 'trk_step_cooking' ),
		ysf_t( $ready ),
		ysf_t( $last ),
	);
}

/**
 * Siparişi yeniden sepete koymak için kalemler.
 *
 * @param int $order_id Sipariş.
 * @return array
 */
function ysf_order_reorder_lines( $order_id ) {
	$lines = get_post_meta( $order_id, '_ysf_items', true );
	$out   = array();

	if ( ! is_array( $lines ) ) {
		return $out;
	}

	foreach ( $lines as $line ) {
		$id = isset( $line['id'] ) ? (int) $line['id'] : 0;

		if ( ! $id || 'publish' !== get_post_status( $id ) || ( function_exists( 'ysf_is_orderable' ) && ! ysf_is_orderable( $id ) ) ) {
			continue;
		}

		$out[] = array(
			'id'    => $id,
			'name'  => isset( $line['name'] ) ? (string) $line['name'] : '',
			'price' => isset( $line['price'] ) ? (float) $line['price'] : 0,
			'qty'   => isset( $line['qty'] ) ? max( 1, min( 50, (int) $line['qty'] ) ) : 1,
			'size'  => isset( $line['size'] ) ? (string) $line['size'] : '',
		);
	}

	return $out;
}

/**
 * Takip sayfasının istemciye verilen durumu.
 *
 * @param int $order_id Sipariş.
 * @return array
 */
function ysf_order_tracking_payload( $order_id ) {
	$channel = function_exists( 'ysf_order_channel' ) ? ysf_order_channel( $order_id ) : (string) get_post_meta( $order_id, '_ysf_channel', true );
	$step    = ysf_order_tracking_step( $order_id );
	$rating  = (int) get_post_meta( $order_id, '_ysf_rating', true );
	$items   = get_post_meta( $order_id, '_ysf_items', true );
	$lines   = array();

	if ( is_array( $items ) ) {
		foreach ( $items as $line ) {
			$lines[] = sprintf( '%1$d × %2$s', isset( $line['qty'] ) ? (int) $line['qty'] : 1, isset( $line['name'] ) ? $line['name'] : '' );
		}
	}

	return array(
		'id'        => (int) $order_id,
		'step'      => $step,
		'cancelled' => -1 === $step,
		'channel'   => $channel,
		'labels'    => ysf_order_tracking_labels( $channel ),
		'lines'     => $lines,
		'total'     => ysf_price( (float) get_post_meta( $order_id, '_ysf_total', true ) ),
		'placed'    => get_post_time( 'U', true, $order_id ),
		'rated'     => $rating > 0,
		'rating'    => $rating,
		'canRate'   => 4 === $step && ! $rating,
		'reorder'   => ysf_order_reorder_lines( $order_id ),
	);
}

/**
 * Takip durumu AJAX ucu.
 */
function ysf_ajax_order_status() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ysf_rate_limited( 'order_status', 240, 900 ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 429 );
	}

	$id = ysf_order_tracking_verify(
		isset( $_POST['id'] ) ? wp_unslash( $_POST['id'] ) : 0, // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized
		isset( $_POST['k'] ) ? wp_unslash( $_POST['k'] ) : '' // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized
	);

	if ( ! $id ) {
		wp_send_json_error( array( 'message' => ysf_t( 'trk_not_found' ) ), 404 );
	}

	wp_send_json_success( ysf_order_tracking_payload( $id ) );
}
add_action( 'wp_ajax_ysf_order_status', 'ysf_ajax_order_status' );
add_action( 'wp_ajax_nopriv_ysf_order_status', 'ysf_ajax_order_status' );

/**
 * Puan ve geri bildirim AJAX ucu.
 */
function ysf_ajax_order_rate() {
	check_ajax_referer( 'ysf_public', 'nonce' );

	if ( ysf_rate_limited( 'order_rate', 10, 900 ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 429 );
	}

	$id = ysf_order_tracking_verify(
		isset( $_POST['id'] ) ? wp_unslash( $_POST['id'] ) : 0, // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized
		isset( $_POST['k'] ) ? wp_unslash( $_POST['k'] ) : '' // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized
	);

	if ( ! $id ) {
		wp_send_json_error( array( 'message' => ysf_t( 'trk_not_found' ) ), 404 );
	}

	$stars = isset( $_POST['stars'] ) ? (int) $_POST['stars'] : 0;
	$note  = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$note  = function_exists( 'mb_substr' ) ? mb_substr( $note, 0, 1000 ) : substr( $note, 0, 1000 );

	if ( $stars < 1 || $stars > 5 || 4 !== ysf_order_tracking_step( $id ) ) {
		wp_send_json_error( array( 'message' => ysf_t( 'form_error' ) ), 400 );
	}

	$existing = (int) get_post_meta( $id, '_ysf_rating', true );

	if ( $existing && ! $note ) {
		wp_send_json_error( array( 'message' => ysf_t( 'trk_rated_already' ) ), 409 );
	}

	if ( ! $existing ) {
		update_post_meta( $id, '_ysf_rating', $stars );
		update_post_meta( $id, '_ysf_rating_at', time() );
	}

	if ( $note ) {
		update_post_meta( $id, '_ysf_rating_note', $note );
	}

	$stars  = $existing ? $existing : $stars;
	$review = esc_url_raw( (string) ysf_get_option( 'ysf_google_review_url', '' ) );

	if ( $note || $stars <= 3 ) {
		ysf_send_notification(
			ysf_get_option( 'ysf_order_email', '' ),
			sprintf(
				/* translators: 1: sipariş no, 2: yıldız. */
				__( 'Sipariş #%1$d için %2$d yıldız geri bildirim', 'ysffoodlab' ),
				$id,
				$stars
			),
			array(
				__( 'Ad Soyad', 'ysffoodlab' ) => (string) get_post_meta( $id, '_ysf_name', true ),
				__( 'Telefon', 'ysffoodlab' )  => (string) get_post_meta( $id, '_ysf_phone', true ),
				__( 'Puan', 'ysffoodlab' )     => str_repeat( '★', $stars ) . str_repeat( '☆', 5 - $stars ),
				__( 'Not', 'ysffoodlab' )      => $note,
			),
			__( 'Bir müşteri siparişini puanladı.', 'ysffoodlab' )
		);
	}

	wp_send_json_success(
		array(
			'message'   => $stars >= 4 ? ysf_t( 'trk_thanks_happy' ) : ysf_t( 'trk_thanks_sorry' ),
			'stars'     => $stars,
			'reviewUrl' => ( $stars >= 4 && $review ) ? $review : '',
			'askNote'   => $stars <= 3 && ! $note,
		)
	);
}
add_action( 'wp_ajax_ysf_order_rate', 'ysf_ajax_order_rate' );
add_action( 'wp_ajax_nopriv_ysf_order_rate', 'ysf_ajax_order_rate' );

/**
 * Damga kartı ayarları.
 *
 * @return array{goal:int,reward:string}
 */
function ysf_loyalty_settings() {
	$goal   = max( 0, min( 50, (int) ysf_get_option( 'ysf_loyalty_goal', 0 ) ) );
	$reward = 'en' === ysf_lang() ? ysf_get_option( 'ysf_loyalty_reward_en', '' ) : ysf_get_option( 'ysf_loyalty_reward', '' );

	return array(
		'goal'   => $goal,
		'reward' => (string) $reward,
	);
}

/**
 * Üyenin damga kartı durumu.
 *
 * Tamamlanan her online sipariş bir damgadır; hedefe ulaşınca kart yenilenir.
 *
 * @param int $user_id Üye.
 * @return array|null Kart kapalıysa null.
 */
function ysf_loyalty_progress( $user_id ) {
	$settings = ysf_loyalty_settings();

	if ( ! $settings['goal'] || ! $user_id ) {
		return null;
	}

	$done = get_posts(
		array(
			'post_type'      => 'ysf_order',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_ysf_user_id',
					'value' => (int) $user_id,
				),
				array(
					'key'   => '_ysf_state',
					'value' => 'done',
				),
			),
		)
	);

	$count   = count( $done );
	$current = $count % $settings['goal'];
	$earned  = (int) floor( $count / $settings['goal'] );

	return array(
		'goal'    => $settings['goal'],
		'stamps'  => $current,
		'left'    => $settings['goal'] - $current,
		'earned'  => $earned,
		'total'   => $count,
		'reward'  => $settings['reward'],
		'justWon' => $count > 0 && 0 === $current,
	);
}

/**
 * Damga kartını çizer.
 *
 * @param int $user_id Üye.
 */
function ysf_render_loyalty_card( $user_id ) {
	$card = ysf_loyalty_progress( $user_id );

	if ( ! $card ) {
		return;
	}
	?>
	<div class="ysf-loyalty" aria-label="<?php echo esc_attr( ysf_t( 'loy_title' ) ); ?>">
		<div class="ysf-loyalty__head">
			<strong><?php ysf_e( 'loy_title' ); ?></strong>
			<span>
				<?php
				echo esc_html(
					$card['justWon']
						? ysf_t( 'loy_won' )
						: sprintf( ysf_t( 'loy_left' ), $card['left'] )
				);
				?>
			</span>
		</div>
		<ol class="ysf-loyalty__stamps">
			<?php for ( $i = 1; $i <= $card['goal']; $i++ ) : ?>
				<li class="<?php echo ( $i <= $card['stamps'] || $card['justWon'] ) ? 'is-on' : ''; ?>" aria-hidden="true"></li>
			<?php endfor; ?>
		</ol>
		<?php if ( $card['reward'] ) : ?>
			<p class="ysf-loyalty__reward"><?php echo esc_html( sprintf( ysf_t( 'loy_reward' ), $card['reward'] ) ); ?></p>
		<?php endif; ?>
		<p class="ysf-visually-hidden"><?php echo esc_html( sprintf( ysf_t( 'loy_sr' ), $card['stamps'], $card['goal'] ) ); ?></p>
	</div>
	<?php
}

/**
 * Takip sayfası isteğini yakalar ve sayfayı çizer.
 */
function ysf_order_tracking_route() {
	if ( ! isset( $_GET['ysf_siparis'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$id = ysf_order_tracking_verify(
		wp_unslash( $_GET['ysf_siparis'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidationSanitization.InputNotSanitized
		isset( $_GET['k'] ) ? wp_unslash( $_GET['k'] ) : '' // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidationSanitization.InputNotSanitized
	);

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	do_action( 'litespeed_control_set_nocache', 'ysf order tracking' );
	nocache_headers();
	add_filter( 'wp_robots', 'wp_robots_no_robots' );
	add_filter(
		'document_title_parts',
		static function ( $parts ) {
			$parts['title'] = ysf_t( 'trk_title' );
			return $parts;
		}
	);

	if ( ! $id ) {
		status_header( 404 );
	}

	get_header();
	?>
	<section class="ysf-section ysf-track-page">
		<div class="ysf-wrap ysf-wrap--narrow">
			<?php if ( ! $id ) : ?>
				<div class="ysf-card-panel">
					<h1><?php ysf_e( 'trk_title' ); ?></h1>
					<p><?php ysf_e( 'trk_not_found' ); ?></p>
				</div>
			<?php else : ?>
				<?php $payload = ysf_order_tracking_payload( $id ); ?>
				<div
					class="ysf-card-panel ysf-track"
					data-ysf-track
					data-id="<?php echo esc_attr( $id ); ?>"
					data-k="<?php echo esc_attr( ysf_order_tracking_key( $id ) ); ?>"
					data-state="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
				>
					<p class="ysf-eyebrow"><?php echo esc_html( sprintf( ysf_t( 'trk_order_no' ), $id ) ); ?></p>
					<h1 data-ysf-track-headline><?php ysf_e( 'trk_title' ); ?></h1>

					<ol class="ysf-track__steps" data-ysf-track-steps aria-live="polite"></ol>
					<p class="ysf-track__cancelled" data-ysf-track-cancelled hidden><?php ysf_e( 'trk_cancelled' ); ?></p>

					<div class="ysf-track__actions">
						<button type="button" class="ysf-btn ysf-btn--ghost ysf-btn--sm" data-ysf-track-notify hidden><?php ysf_e( 'trk_notify_me' ); ?></button>
					</div>

					<details class="ysf-track__summary">
						<summary><?php ysf_e( 'trk_summary' ); ?></summary>
						<ul data-ysf-track-lines></ul>
						<p><strong><?php ysf_e( 'total' ); ?>:</strong> <span data-ysf-track-total></span></p>
					</details>

					<section class="ysf-track__rate" data-ysf-track-rate hidden>
						<h2><?php ysf_e( 'trk_rate_title' ); ?></h2>
						<div class="ysf-stars" role="radiogroup" aria-label="<?php echo esc_attr( ysf_t( 'trk_rate_title' ) ); ?>">
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<button type="button" role="radio" aria-checked="false" data-ysf-star="<?php echo esc_attr( $i ); ?>" aria-label="<?php echo esc_attr( sprintf( ysf_t( 'trk_stars' ), $i ) ); ?>">★</button>
							<?php endfor; ?>
						</div>
						<div class="ysf-track__note" data-ysf-track-note hidden>
							<label for="ysf-track-note"><?php ysf_e( 'trk_note_label' ); ?></label>
							<textarea id="ysf-track-note" rows="3" maxlength="1000"></textarea>
							<button type="button" class="ysf-btn ysf-btn--sm" data-ysf-track-send><?php ysf_e( 'trk_note_send' ); ?></button>
						</div>
						<div class="ysf-alert" data-ysf-result hidden></div>
					</section>

					<div class="ysf-track__again" data-ysf-track-again hidden>
						<button type="button" class="ysf-btn" data-ysf-reorder><?php ysf_e( 'reorder' ); ?></button>
					</div>

					<?php
					$user_id = (int) get_post_meta( $id, '_ysf_user_id', true );

					if ( $user_id && get_current_user_id() === $user_id ) {
						ysf_render_loyalty_card( $user_id );
					}
					?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	get_footer();
	exit;
}
add_action( 'template_redirect', 'ysf_order_tracking_route', 1 );
