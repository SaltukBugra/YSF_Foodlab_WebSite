<?php
/**
 * Mutfak sorumlusu menü kontrol paneli.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ysf_items = ysf_kitchen_items();
$ysf_cats  = get_terms(
	array(
		'taxonomy'   => 'ysf_menu_cat',
		'hide_empty' => false,
	)
);

if ( is_wp_error( $ysf_cats ) ) {
	$ysf_cats = array();
}

$ysf_live  = array();
$ysf_gone  = array();

foreach ( $ysf_items as $ysf_item ) {
	if ( 'trash' === $ysf_item->post_status ) {
		$ysf_gone[] = $ysf_item;
	} else {
		$ysf_live[] = $ysf_item;
	}
}
?>

<section class="ysf-kitchen" id="ysf-kitchen" data-ysf-kitchen>
	<header class="ysf-kitchen__head">
		<div>
			<p class="ysf-eyebrow"><?php ysf_e( 'kit_eyebrow' ); ?></p>
			<h3 class="ysf-card-panel__title"><?php ysf_e( 'kit_title' ); ?></h3>
			<p class="ysf-muted"><?php ysf_e( 'kit_lead' ); ?></p>
		</div>
		<button type="button" class="ysf-btn" data-ysf-kit-new><?php ysf_e( 'kit_add' ); ?></button>
	</header>

	<div class="ysf-alert" data-ysf-kit-flash hidden></div>

	<form class="ysf-form ysf-form--2col ysf-kitchen__form" data-ysf-form="kitchen" hidden novalidate>
		<input type="hidden" name="id" value="0">

		<div class="ysf-field ysf-field--full">
			<label for="ysf-kit-title"><?php ysf_e( 'kit_name' ); ?> <span class="ysf-req">*</span></label>
			<input type="text" id="ysf-kit-title" name="title" required maxlength="140" autocomplete="off">
		</div>

		<div class="ysf-field">
			<label for="ysf-kit-cat"><?php ysf_e( 'kit_cat' ); ?></label>
			<select id="ysf-kit-cat" name="category">
				<option value="0"><?php ysf_e( 'kit_cat_none' ); ?></option>
				<?php foreach ( $ysf_cats as $ysf_cat ) : ?>
					<option value="<?php echo esc_attr( (string) $ysf_cat->term_id ); ?>">
						<?php echo esc_html( $ysf_cat->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="ysf-field">
			<label for="ysf-kit-price"><?php ysf_e( 'kit_price' ); ?></label>
			<input type="number" id="ysf-kit-price" name="price" min="0" step="0.01" inputmode="decimal">
		</div>

		<div class="ysf-field ysf-field--full">
			<label for="ysf-kit-excerpt"><?php ysf_e( 'kit_desc' ); ?></label>
			<textarea id="ysf-kit-excerpt" name="excerpt" rows="3" maxlength="400"></textarea>
		</div>

		<div class="ysf-field ysf-field--full">
			<label for="ysf-kit-photo"><?php ysf_e( 'kit_photo' ); ?></label>
			<input type="file" id="ysf-kit-photo" name="photo" accept="image/*">
			<small><?php ysf_e( 'kit_photo_hint' ); ?></small>
		</div>

		<div class="ysf-field ysf-field--full">
			<span><?php ysf_e( 'kit_flags' ); ?></span>
			<div class="ysf-kit-flags">
				<label class="ysf-check">
					<input type="checkbox" name="vegetarian" value="1">
					<span><?php ysf_e( 'vegetarian' ); ?></span>
				</label>
				<label class="ysf-check">
					<input type="checkbox" name="vegan" value="1">
					<span><?php ysf_e( 'vegan' ); ?></span>
				</label>
				<label class="ysf-check">
					<input type="checkbox" name="glutenfree" value="1">
					<span><?php ysf_e( 'glutenfree' ); ?></span>
				</label>
				<label class="ysf-check">
					<input type="checkbox" name="spicy" value="1">
					<span><?php ysf_e( 'spicy' ); ?></span>
				</label>
			</div>
		</div>

		<div class="ysf-field ysf-field--full" data-ysf-kit-tags>
			<label><?php ysf_e( 'kit_tags' ); ?></label>
			<p class="ysf-muted"><?php ysf_e( 'kit_tags_hint' ); ?></p>
			<div class="ysf-kit-tags__composer">
				<select data-ysf-tag-type>
					<?php foreach ( ysf_tag_types() as $ysf_tag_key => $ysf_tag_meta ) : ?>
						<option value="<?php echo esc_attr( $ysf_tag_key ); ?>">
							<?php echo esc_html( $ysf_tag_meta['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<input type="text" data-ysf-tag-label maxlength="40" placeholder="<?php echo esc_attr( ysf_t( 'kit_tag_ph' ) ); ?>">
				<button type="button" class="ysf-btn ysf-btn--sm" data-ysf-tag-add><?php ysf_e( 'kit_tag_add' ); ?></button>
			</div>
			<ul class="ysf-kit-tags__list" data-ysf-tag-list></ul>
			<input type="hidden" name="tags" value="[]">
		</div>

		<label class="ysf-check">
			<input type="checkbox" name="sold_out" value="1">
			<span><?php ysf_e( 'kit_sold_out' ); ?></span>
		</label>

		<label class="ysf-check">
			<input type="checkbox" name="orderable" value="1" checked>
			<span><?php ysf_e( 'kit_orderable' ); ?></span>
		</label>

		<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

		<div class="ysf-addr-card__actions ysf-field--full">
			<button type="submit" class="ysf-btn" data-ysf-submit><?php ysf_e( 'acc_save' ); ?></button>
			<button type="button" class="ysf-btn ysf-btn--ghost" data-ysf-kit-cancel><?php ysf_e( 'acc_cancel' ); ?></button>
		</div>
	</form>

	<div class="ysf-kitchen__toolbar">
		<input type="search" class="ysf-kitchen__search" data-ysf-kit-search placeholder="<?php echo esc_attr( ysf_t( 'kit_search' ) ); ?>" autocomplete="off">

		<div class="ysf-filters ysf-kitchen__filters" role="group" aria-label="<?php echo esc_attr( ysf_t( 'kit_cat' ) ); ?>">
			<button type="button" class="ysf-filter is-active" data-ysf-kit-filter=""><?php ysf_e( 'menu_all' ); ?></button>
			<?php foreach ( $ysf_cats as $ysf_cat ) : ?>
				<button type="button" class="ysf-filter" data-ysf-kit-filter="<?php echo esc_attr( (string) $ysf_cat->term_id ); ?>">
					<?php echo esc_html( $ysf_cat->name ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( ! $ysf_live ) : ?>
		<p class="ysf-muted" data-ysf-kit-empty><?php ysf_e( 'kit_empty' ); ?></p>
	<?php endif; ?>

	<div class="ysf-kitchen__list">
		<?php foreach ( $ysf_live as $ysf_item ) : ?>
			<?php
			$ysf_id     = $ysf_item->ID;
			$ysf_sold   = ysf_meta_flag( $ysf_id, '_ysf_sold_out' );
			$ysf_canbuy = ysf_meta_flag( $ysf_id, '_ysf_orderable', true );
			$ysf_price  = (float) get_post_meta( $ysf_id, '_ysf_price', true );
			$ysf_terms  = wp_get_post_terms( $ysf_id, 'ysf_menu_cat' );
			$ysf_cat_id = ( $ysf_terms && ! is_wp_error( $ysf_terms ) ) ? (int) $ysf_terms[0]->term_id : 0;
			$ysf_cat_nm = $ysf_cat_id ? $ysf_terms[0]->name : '';
			$ysf_desc   = wp_strip_all_tags( $ysf_item->post_excerpt ? $ysf_item->post_excerpt : $ysf_item->post_content );
			$ysf_thumb  = get_the_post_thumbnail_url( $ysf_id, 'ysf-thumb' );
			$ysf_thumb  = $ysf_thumb ? $ysf_thumb : ysf_placeholder_image();
			$ysf_vegan  = ysf_meta_flag( $ysf_id, '_ysf_vegan' );
			$ysf_veg    = ysf_meta_flag( $ysf_id, '_ysf_vegetarian' );
			$ysf_gf     = ysf_meta_flag( $ysf_id, '_ysf_glutenfree' );
			$ysf_spicy  = ysf_meta_flag( $ysf_id, '_ysf_spicy' );
			$ysf_tags   = ysf_get_item_tags( $ysf_id );
			?>
			<article
				class="ysf-kitchen__row<?php echo $ysf_sold ? ' is-soldout' : ''; ?>"
				data-ysf-kit-row
				data-id="<?php echo esc_attr( (string) $ysf_id ); ?>"
				data-title="<?php echo esc_attr( $ysf_item->post_title ); ?>"
				data-price="<?php echo esc_attr( (string) $ysf_price ); ?>"
				data-cat="<?php echo esc_attr( (string) $ysf_cat_id ); ?>"
				data-excerpt="<?php echo esc_attr( $ysf_desc ); ?>"
				data-sold="<?php echo $ysf_sold ? '1' : '0'; ?>"
				data-orderable="<?php echo $ysf_canbuy ? '1' : '0'; ?>"
				data-vegan="<?php echo $ysf_vegan ? '1' : '0'; ?>"
				data-vegetarian="<?php echo $ysf_veg ? '1' : '0'; ?>"
				data-glutenfree="<?php echo $ysf_gf ? '1' : '0'; ?>"
				data-spicy="<?php echo $ysf_spicy ? '1' : '0'; ?>"
				data-tags="<?php echo esc_attr( wp_json_encode( $ysf_tags ) ); ?>"
				data-search="<?php echo esc_attr( strtolower( $ysf_item->post_title . ' ' . $ysf_desc ) ); ?>"
			>
				<?php if ( $ysf_thumb ) : ?>
					<img class="ysf-kitchen__thumb" src="<?php echo esc_url( $ysf_thumb ); ?>" alt="" width="72" height="72" loading="lazy">
				<?php endif; ?>

				<div class="ysf-kitchen__info">
					<h4><?php echo esc_html( $ysf_item->post_title ); ?></h4>
					<p>
						<?php if ( $ysf_cat_nm ) : ?>
							<span><?php echo esc_html( $ysf_cat_nm ); ?></span>
						<?php endif; ?>
						<?php if ( $ysf_price ) : ?>
							<span><?php echo esc_html( ysf_price( $ysf_price ) ); ?></span>
						<?php endif; ?>
						<strong class="ysf-kitchen__state" data-ysf-kit-state>
							<?php echo esc_html( $ysf_sold ? ysf_t( 'kit_sold_out' ) : ysf_t( 'kit_in_stock' ) ); ?>
						</strong>
					</p>
				</div>

				<div class="ysf-kitchen__actions">
					<button type="button" class="ysf-btn ysf-btn--sm ysf-btn--ghost" data-ysf-kit-edit>
						<?php ysf_e( 'kit_edit' ); ?>
					</button>
					<button
						type="button"
						class="ysf-btn ysf-btn--sm<?php echo $ysf_sold ? '' : ' ysf-btn--ghost'; ?>"
						data-ysf-kit-stock
						data-task="<?php echo $ysf_sold ? 'in_stock' : 'sold_out'; ?>"
					>
						<?php echo esc_html( $ysf_sold ? ysf_t( 'kit_in_stock' ) : ysf_t( 'kit_sold_out' ) ); ?>
					</button>
					<button type="button" class="ysf-link-btn ysf-link-btn--danger" data-ysf-kit-remove>
						<?php ysf_e( 'kit_remove' ); ?>
					</button>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<?php if ( $ysf_gone ) : ?>
		<details class="ysf-details ysf-kitchen__trash">
			<summary><?php echo esc_html( ysf_t( 'kit_removed' ) . ' (' . count( $ysf_gone ) . ')' ); ?></summary>
			<ul class="ysf-record-list">
				<?php foreach ( $ysf_gone as $ysf_item ) : ?>
					<li>
						<span><?php echo esc_html( $ysf_item->post_title ); ?></span>
						<button type="button" class="ysf-link-btn" data-ysf-kit-restore data-id="<?php echo esc_attr( (string) $ysf_item->ID ); ?>">
							<?php ysf_e( 'kit_restore' ); ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
</section>
