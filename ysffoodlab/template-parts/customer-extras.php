<?php
/**
 * Müşteri ekranı ekleri: ürün detayı, masa servisi, aktif sipariş kısayolu.
 *
 * @package ysffoodlab
 */

$ysf_track_tables = function_exists( 'ysf_show_table_service' ) && ysf_show_table_service() ? ysf_table_numbers() : array();
?>

<dialog class="ysf-detail" data-ysf-detail-dialog aria-labelledby="ysf-detail-title">
	<form method="dialog" class="ysf-detail__close-form">
		<button type="submit" class="ysf-detail__close" aria-label="<?php echo esc_attr( ysf_t( 'cart_close' ) ); ?>">&times;</button>
	</form>
	<div class="ysf-detail__media" data-ysf-detail-media></div>
	<div class="ysf-detail__body">
		<h2 id="ysf-detail-title" data-ysf-detail-title></h2>
		<div class="ysf-detail__tags" data-ysf-detail-tags></div>
		<p class="ysf-detail__desc" data-ysf-detail-desc></p>
		<p class="ysf-card__meta" data-ysf-detail-meta></p>
		<div class="ysf-detail__buy" data-ysf-detail-buy></div>
		<section class="ysf-detail__pairs" data-ysf-detail-pairs hidden>
			<h3><?php ysf_e( 'pairs_title' ); ?></h3>
			<ul data-ysf-detail-pair-list></ul>
		</section>
	</div>
</dialog>

<?php if ( $ysf_track_tables ) : ?>
	<div class="ysf-tablebar" data-ysf-tablebar data-tables="<?php echo esc_attr( wp_json_encode( array_values( $ysf_track_tables ) ) ); ?>" hidden>
		<div class="ysf-tablebar__inner">
			<label class="ysf-tablebar__label">
				<span><?php ysf_e( 'call_table_label' ); ?></span>
				<select data-ysf-tablebar-select>
					<option value=""><?php ysf_e( 'call_pick_table' ); ?></option>
					<?php foreach ( $ysf_track_tables as $ysf_table_no ) : ?>
						<option value="<?php echo esc_attr( $ysf_table_no ); ?>"><?php echo esc_html( sprintf( ysf_t( 'pos_table' ), $ysf_table_no ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<div class="ysf-tablebar__actions">
				<button type="button" class="ysf-btn ysf-btn--sm" data-ysf-call="waiter"><?php ysf_e( 'call_waiter' ); ?></button>
				<button type="button" class="ysf-btn ysf-btn--ghost ysf-btn--sm" data-ysf-call="bill"><?php ysf_e( 'call_bill' ); ?></button>
			</div>
			<p class="ysf-tablebar__msg" data-ysf-tablebar-msg role="status" aria-live="polite"></p>
		</div>
	</div>
<?php endif; ?>

<a class="ysf-track-chip" href="#" data-ysf-track-chip hidden>
	<span class="ysf-track-chip__dot" aria-hidden="true"></span>
	<span><?php ysf_e( 'trk_chip' ); ?></span>
</a>
