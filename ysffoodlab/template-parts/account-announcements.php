<?php
/**
 * Hesabım duyuru paneli. Yalnızca site yöneticisi.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$ysf_announcements = function_exists( 'ysf_get_duyuru_posts' ) ? ysf_get_duyuru_posts() : array();
$today             = current_time( 'Y-m-d' );
$year              = gmdate( 'Y-m-d', strtotime( '+1 year', current_time( 'timestamp' ) ) );
?>

<section class="ysf-kitchen ysf-campaigns" id="ysf-announcements" data-ysf-announcements>
	<header class="ysf-kitchen__head">
		<div>
			<p class="ysf-eyebrow"><?php ysf_e( 'ann_tab' ); ?></p>
			<h3 class="ysf-card-panel__title"><?php ysf_e( 'ann_title' ); ?></h3>
			<p class="ysf-muted"><?php ysf_e( 'ann_lead' ); ?></p>
		</div>
		<button type="button" class="ysf-btn" data-ysf-ann-new><?php ysf_e( 'ann_add' ); ?></button>
	</header>

	<div class="ysf-alert" data-ysf-ann-flash hidden></div>

	<form class="ysf-form ysf-form--2col ysf-kitchen__form" data-ysf-form="announcement" hidden novalidate>
		<input type="hidden" name="id" value="0">

		<div class="ysf-field ysf-field--full">
			<label for="ysf-acc-ann-title"><?php ysf_e( 'ann_name' ); ?> <span class="ysf-req">*</span></label>
			<input type="text" id="ysf-acc-ann-title" name="title" required maxlength="140" autocomplete="off">
		</div>

		<div class="ysf-field ysf-field--full">
			<label for="ysf-acc-ann-excerpt"><?php ysf_e( 'ann_excerpt' ); ?> <span class="ysf-req">*</span></label>
			<textarea id="ysf-acc-ann-excerpt" name="excerpt" rows="3" maxlength="400" required></textarea>
			<small><?php ysf_e( 'ann_excerpt_hint' ); ?></small>
		</div>

		<div class="ysf-field ysf-field--full">
			<label for="ysf-acc-ann-content"><?php ysf_e( 'ann_content' ); ?></label>
			<textarea id="ysf-acc-ann-content" name="content" rows="5"></textarea>
		</div>

		<div class="ysf-field ysf-field--full">
			<label for="ysf-acc-ann-photo"><?php ysf_e( 'kit_photo' ); ?></label>
			<img class="ysf-camp-photo" data-ysf-ann-photo alt="" hidden>
			<input type="file" id="ysf-acc-ann-photo" name="photo" accept="image/*">
			<small><?php ysf_e( 'kit_photo_hint' ); ?></small>
		</div>

		<div class="ysf-field">
			<label for="ysf-acc-ann-start"><?php ysf_e( 'camp_start' ); ?> <span class="ysf-req">*</span></label>
			<input type="date" id="ysf-acc-ann-start" name="start" required value="<?php echo esc_attr( $today ); ?>">
		</div>

		<div class="ysf-field">
			<label for="ysf-acc-ann-end"><?php ysf_e( 'camp_end' ); ?> <span class="ysf-req">*</span></label>
			<input type="date" id="ysf-acc-ann-end" name="end" required value="<?php echo esc_attr( $year ); ?>">
			<small><?php ysf_e( 'camp_end_hint' ); ?></small>
		</div>

		<div class="ysf-field ysf-field--full">
			<label class="ysf-check">
				<input type="checkbox" name="show_bar" value="1" checked>
				<?php ysf_e( 'ann_show_bar' ); ?>
			</label>
		</div>

		<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

		<div class="ysf-addr-card__actions ysf-field--full">
			<button type="submit" class="ysf-btn" data-ysf-submit><?php ysf_e( 'acc_save' ); ?></button>
			<button type="button" class="ysf-btn ysf-btn--ghost" data-ysf-ann-cancel><?php ysf_e( 'acc_cancel' ); ?></button>
		</div>
	</form>

	<?php if ( ! $ysf_announcements ) : ?>
		<p class="ysf-muted" data-ysf-ann-empty><?php ysf_e( 'ann_empty' ); ?></p>
	<?php endif; ?>

	<div class="ysf-kitchen__list">
		<?php foreach ( $ysf_announcements as $ysf_item ) : ?>
			<?php
			$ysf_id      = $ysf_item->ID;
			$ysf_status  = ysf_campaign_admin_status( $ysf_id );
			$ysf_payload = array(
				'id'       => $ysf_id,
				'title'    => $ysf_item->post_title,
				'excerpt'  => $ysf_item->post_excerpt,
				'content'  => $ysf_item->post_content,
				'start'    => (string) get_post_meta( $ysf_id, '_ysf_start', true ),
				'end'      => (string) get_post_meta( $ysf_id, '_ysf_end', true ),
				'show_bar' => ysf_meta_flag( $ysf_id, '_ysf_show_in_bar' ),
				'thumb'    => (string) get_the_post_thumbnail_url( $ysf_id, 'ysf-thumb' ),
			);
			$ysf_thumb   = $ysf_payload['thumb'] ? $ysf_payload['thumb'] : ysf_placeholder_image();
			$ysf_text    = wp_strip_all_tags( $ysf_item->post_excerpt );
			?>
			<article class="ysf-camp-row" data-ysf-ann-row data-announcement="<?php echo esc_attr( wp_json_encode( $ysf_payload ) ); ?>">
				<img class="ysf-camp-row__thumb" src="<?php echo esc_url( $ysf_thumb ); ?>" alt="" width="96" height="72">
				<div class="ysf-camp-row__body">
					<h4><?php echo esc_html( $ysf_item->post_title ); ?></h4>
					<?php if ( $ysf_text ) : ?>
						<p class="ysf-camp-row__text"><?php echo esc_html( $ysf_text ); ?></p>
					<?php endif; ?>
					<p class="ysf-camp-row__meta">
						<?php if ( $ysf_payload['start'] && $ysf_payload['end'] ) : ?>
							<span><?php echo esc_html( $ysf_payload['start'] . ' – ' . $ysf_payload['end'] ); ?></span>
						<?php endif; ?>
						<strong><?php echo esc_html( $ysf_status['label'] ); ?></strong>
					</p>
				</div>
				<div class="ysf-camp-row__actions">
					<button type="button" class="ysf-btn ysf-btn--sm ysf-btn--ghost" data-ysf-ann-edit>
						<?php ysf_e( 'kit_edit' ); ?>
					</button>
					<button type="button" class="ysf-link-btn ysf-link-btn--danger" data-ysf-ann-delete data-id="<?php echo esc_attr( (string) $ysf_id ); ?>">
						<?php ysf_e( 'camp_delete' ); ?>
					</button>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
