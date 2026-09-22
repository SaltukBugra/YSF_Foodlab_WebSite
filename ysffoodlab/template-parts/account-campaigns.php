<?php
/**
 * Hesabım kampanya paneli. Yalnızca site yöneticisi.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$ysf_campaigns = get_posts(
	array(
		'post_type'      => 'ysf_campaign',
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
?>

<section class="ysf-kitchen ysf-campaigns" id="ysf-campaigns" data-ysf-campaigns>
	<header class="ysf-kitchen__head">
		<div>
			<p class="ysf-eyebrow"><?php ysf_e( 'camp_tab' ); ?></p>
			<h3 class="ysf-card-panel__title"><?php ysf_e( 'camp_title' ); ?></h3>
			<p class="ysf-muted"><?php ysf_e( 'camp_lead' ); ?></p>
		</div>
		<button type="button" class="ysf-btn" data-ysf-camp-new><?php ysf_e( 'camp_add' ); ?></button>
	</header>

	<div class="ysf-alert" data-ysf-camp-flash hidden></div>

	<form class="ysf-form ysf-form--2col ysf-kitchen__form" data-ysf-form="campaign" hidden novalidate>
		<input type="hidden" name="id" value="0">

		<div class="ysf-field ysf-field--full">
			<label for="ysf-acc-camp-title"><?php ysf_e( 'camp_name' ); ?> <span class="ysf-req">*</span></label>
			<input type="text" id="ysf-acc-camp-title" name="title" required maxlength="140" autocomplete="off">
		</div>

		<div class="ysf-field ysf-field--full">
			<label for="ysf-acc-camp-scenario"><?php ysf_e( 'camp_scenario' ); ?></label>
			<select id="ysf-acc-camp-scenario" name="scenario" data-ysf-camp-scenario>
				<?php foreach ( ysf_campaign_scenarios() as $ysf_key => $ysf_label ) : ?>
					<option value="<?php echo esc_attr( $ysf_key ); ?>"><?php echo esc_html( $ysf_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="ysf-field ysf-field--full">
			<label for="ysf-acc-camp-excerpt"><?php ysf_e( 'camp_text' ); ?></label>
			<textarea id="ysf-acc-camp-excerpt" name="excerpt" rows="3" maxlength="400"></textarea>
		</div>

		<div class="ysf-field">
			<label for="ysf-acc-camp-title-en"><?php ysf_e( 'camp_name_en' ); ?></label>
			<input type="text" id="ysf-acc-camp-title-en" name="title_en" maxlength="140" autocomplete="off">
		</div>

		<div class="ysf-field">
			<label for="ysf-acc-camp-excerpt-en"><?php ysf_e( 'camp_text_en' ); ?></label>
			<textarea id="ysf-acc-camp-excerpt-en" name="excerpt_en" rows="2" maxlength="400"></textarea>
		</div>

		<div class="ysf-field ysf-field--full">
			<label for="ysf-acc-camp-photo"><?php ysf_e( 'kit_photo' ); ?></label>
			<img class="ysf-camp-photo" data-ysf-camp-photo alt="" hidden>
			<input type="file" id="ysf-acc-camp-photo" name="photo" accept="image/*">
			<small><?php ysf_e( 'kit_photo_hint' ); ?></small>
		</div>

		<div class="ysf-field ysf-field--full" data-ysf-camp="products">
			<span class="ysf-camp-label"><?php ysf_e( 'camp_products' ); ?></span>
			<div class="ysf-camp-products" data-ysf-camp-products>
				<?php ysf_campaign_product_checklist( array() ); ?>
			</div>
		</div>

		<div class="ysf-field" data-ysf-camp="discount">
			<label for="ysf-acc-camp-kind"><?php ysf_e( 'camp_kind' ); ?></label>
			<select id="ysf-acc-camp-kind" name="kind">
				<option value="percent"><?php ysf_e( 'camp_percent' ); ?></option>
				<option value="fixed"><?php ysf_e( 'camp_fixed' ); ?></option>
			</select>
		</div>

		<div class="ysf-field" data-ysf-camp="discount">
			<label for="ysf-acc-camp-value"><?php ysf_e( 'camp_value' ); ?></label>
			<input type="number" id="ysf-acc-camp-value" name="value" min="0" step="0.01" inputmode="decimal">
		</div>

		<div class="ysf-field ysf-field--full" data-ysf-camp="min">
			<label for="ysf-acc-camp-min"><?php ysf_e( 'camp_min' ); ?></label>
			<input type="number" id="ysf-acc-camp-min" name="min" min="0" step="0.01" inputmode="decimal">
			<small><?php ysf_e( 'camp_min_hint' ); ?></small>
		</div>

		<div class="ysf-field ysf-field--full" data-ysf-camp="bundle">
			<label for="ysf-acc-camp-bundle"><?php ysf_e( 'camp_bundle' ); ?></label>
			<input type="number" id="ysf-acc-camp-bundle" name="bundle" min="0" step="0.01" inputmode="decimal">
			<small><?php ysf_e( 'camp_bundle_hint' ); ?></small>
		</div>

		<div class="ysf-field">
			<label for="ysf-acc-camp-start"><?php ysf_e( 'camp_start' ); ?> <span class="ysf-req">*</span></label>
			<input type="date" id="ysf-acc-camp-start" name="start" required>
		</div>

		<div class="ysf-field">
			<label for="ysf-acc-camp-end"><?php ysf_e( 'camp_end' ); ?> <span class="ysf-req">*</span></label>
			<input type="date" id="ysf-acc-camp-end" name="end" required>
			<small><?php ysf_e( 'camp_end_hint' ); ?></small>
		</div>

		<div class="ysf-field">
			<label for="ysf-acc-camp-time-start"><?php ysf_e( 'camp_time_start' ); ?></label>
			<input type="time" id="ysf-acc-camp-time-start" name="time_start">
		</div>

		<div class="ysf-field">
			<label for="ysf-acc-camp-time-end"><?php ysf_e( 'camp_time_end' ); ?></label>
			<input type="time" id="ysf-acc-camp-time-end" name="time_end">
			<small><?php ysf_e( 'camp_time_hint' ); ?></small>
		</div>

		<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

		<div class="ysf-addr-card__actions ysf-field--full">
			<button type="submit" class="ysf-btn" data-ysf-submit><?php ysf_e( 'acc_save' ); ?></button>
			<button type="button" class="ysf-btn ysf-btn--ghost" data-ysf-camp-cancel><?php ysf_e( 'acc_cancel' ); ?></button>
		</div>
	</form>

	<?php if ( ! $ysf_campaigns ) : ?>
		<p class="ysf-muted" data-ysf-camp-empty><?php ysf_e( 'camp_empty' ); ?></p>
	<?php endif; ?>

	<div class="ysf-kitchen__list">
		<?php foreach ( $ysf_campaigns as $ysf_item ) : ?>
			<?php
			if ( function_exists( 'ysf_is_duyuru' ) && ysf_is_duyuru( $ysf_item->ID ) ) {
				continue;
			}

			$ysf_id       = $ysf_item->ID;
			$ysf_scenario = (string) get_post_meta( $ysf_id, '_ysf_scenario', true );
			$ysf_status   = ysf_campaign_admin_status( $ysf_id );
			$ysf_payload  = array(
				'id'         => $ysf_id,
				'title'      => $ysf_item->post_title,
				'excerpt'    => $ysf_item->post_excerpt,
				'title_en'   => (string) get_post_meta( $ysf_id, '_ysf_title_en', true ),
				'excerpt_en' => (string) get_post_meta( $ysf_id, '_ysf_excerpt_en', true ),
				'scenario'   => isset( ysf_campaign_scenarios()[ $ysf_scenario ] ) ? $ysf_scenario : 'direct',
				'kind'       => 'fixed' === get_post_meta( $ysf_id, '_ysf_discount_kind', true ) ? 'fixed' : 'percent',
				'value'      => (string) get_post_meta( $ysf_id, '_ysf_discount_value', true ),
				'min'        => (string) get_post_meta( $ysf_id, '_ysf_min_spend', true ),
				'bundle'     => (string) get_post_meta( $ysf_id, '_ysf_bundle_total', true ),
				'start'      => (string) get_post_meta( $ysf_id, '_ysf_start', true ),
				'end'        => (string) get_post_meta( $ysf_id, '_ysf_end', true ),
				'time_start' => (string) get_post_meta( $ysf_id, '_ysf_time_start', true ),
				'time_end'   => (string) get_post_meta( $ysf_id, '_ysf_time_end', true ),
				'products'   => ysf_campaign_product_ids( $ysf_id ),
				'thumb'      => (string) get_the_post_thumbnail_url( $ysf_id, 'ysf-thumb' ),
			);
			$ysf_thumb = $ysf_payload['thumb'] ? $ysf_payload['thumb'] : ysf_placeholder_image();
			$ysf_text  = wp_strip_all_tags( $ysf_item->post_excerpt );
			?>
			<article class="ysf-camp-row" data-ysf-camp-row data-campaign="<?php echo esc_attr( wp_json_encode( $ysf_payload ) ); ?>">
				<img class="ysf-camp-row__thumb" src="<?php echo esc_url( $ysf_thumb ); ?>" alt="" width="96" height="72">
				<div class="ysf-camp-row__body">
					<h4><?php echo esc_html( $ysf_item->post_title ); ?></h4>
					<?php if ( $ysf_text ) : ?>
						<p class="ysf-camp-row__text"><?php echo esc_html( $ysf_text ); ?></p>
					<?php endif; ?>
					<p class="ysf-camp-row__meta">
						<span><?php echo esc_html( ysf_campaign_scenario_label( $ysf_scenario ) ); ?></span>
						<?php if ( $ysf_payload['start'] && $ysf_payload['end'] ) : ?>
							<span>
								<?php
								echo esc_html(
									$ysf_payload['time_start'] && $ysf_payload['time_end']
										? $ysf_payload['start'] . ' ' . $ysf_payload['time_start'] . ' – ' . $ysf_payload['end'] . ' ' . $ysf_payload['time_end']
										: $ysf_payload['start'] . ' – ' . $ysf_payload['end']
								);
								?>
							</span>
						<?php endif; ?>
						<strong><?php echo esc_html( $ysf_status['label'] ); ?></strong>
					</p>
				</div>
				<div class="ysf-camp-row__actions">
					<button type="button" class="ysf-btn ysf-btn--sm ysf-btn--ghost" data-ysf-camp-edit>
						<?php ysf_e( 'kit_edit' ); ?>
					</button>
					<button type="button" class="ysf-link-btn ysf-link-btn--danger" data-ysf-camp-delete data-id="<?php echo esc_attr( (string) $ysf_id ); ?>">
						<?php ysf_e( 'camp_delete' ); ?>
					</button>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
