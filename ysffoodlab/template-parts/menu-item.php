<?php
/**
 * Menü ürünü (görsel içine gömülü bilgi).
 *
 * @package ysffoodlab
 */

$ysf_id    = get_the_ID();
$ysf_thumb = get_the_post_thumbnail_url( $ysf_id, 'ysf-menu' );
$ysf_thumb = $ysf_thumb ? $ysf_thumb : get_the_post_thumbnail_url( $ysf_id, 'ysf-card' );
$ysf_thumb = $ysf_thumb ? $ysf_thumb : ysf_placeholder_image();
$ysf_desc  = ysf_field( $ysf_id, 'excerpt' );
$ysf_cal    = (int) get_post_meta( $ysf_id, '_ysf_calories', true );
$ysf_prep   = (int) get_post_meta( $ysf_id, '_ysf_prep', true );
$ysf_allerg = get_post_meta( $ysf_id, '_ysf_allergens', true );
$ysf_terms  = wp_get_post_terms( $ysf_id, 'ysf_menu_cat', array( 'fields' => 'slugs' ) );
$ysf_search = strtolower( ysf_field( $ysf_id, 'title' ) . ' ' . wp_strip_all_tags( $ysf_desc ) );
$ysf_show_cart = ! empty( $args['show_cart'] );
$ysf_layout    = ( isset( $args['layout'] ) && 'list' === $args['layout'] ) ? 'list' : 'embed';
$ysf_sold      = ysf_meta_flag( $ysf_id, '_ysf_sold_out' );
$ysf_diet      = array();

foreach ( array( 'vegan', 'vegetarian', 'glutenfree', 'spicy' ) as $ysf_flag ) {
	if ( ysf_meta_flag( $ysf_id, '_ysf_' . $ysf_flag ) ) {
		$ysf_diet[] = $ysf_flag;
	}
}

if ( in_array( 'vegan', $ysf_diet, true ) && ! in_array( 'vegetarian', $ysf_diet, true ) ) {
	$ysf_diet[] = 'vegetarian';
}

$ysf_title = ysf_field( $ysf_id, 'title' );
$ysf_full  = get_the_post_thumbnail_url( $ysf_id, 'large' );
$ysf_ingr  = ysf_item_ingredients( $ysf_id );
$ysf_opts  = ysf_item_options( $ysf_id );

foreach ( ysf_get_item_notes( $ysf_id ) as $ysf_note ) {
	$ysf_search .= ' ' . strtolower( $ysf_note['text'] . ' ' . $ysf_note['text_en'] );
}
?>
<?php if ( 'list' === $ysf_layout ) : ?>
<article
	class="ysf-item<?php echo $ysf_thumb ? '' : ' ysf-item--noimg'; ?><?php echo $ysf_sold ? ' ysf-item--soldout' : ''; ?>"
	data-ysf-item
	data-id="<?php echo esc_attr( $ysf_id ); ?>"
	data-cats="<?php echo esc_attr( is_wp_error( $ysf_terms ) ? '' : implode( ' ', $ysf_terms ) ); ?>"
	data-diet="<?php echo esc_attr( implode( ' ', $ysf_diet ) ); ?>"
	data-search="<?php echo esc_attr( $ysf_search ); ?>"
	<?php if ( $ysf_full ) : ?>
		data-full="<?php echo esc_url( $ysf_full ); ?>"
	<?php endif; ?>
	<?php if ( $ysf_ingr ) : ?>
		data-ingredients="<?php echo esc_attr( wp_json_encode( $ysf_ingr ) ); ?>"
	<?php endif; ?>
	<?php if ( $ysf_opts ) : ?>
		data-options="<?php echo esc_attr( wp_json_encode( $ysf_opts ) ); ?>"
	<?php endif; ?>
>
	<button type="button" class="ysf-fav" data-ysf-fav="<?php echo esc_attr( $ysf_id ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( sprintf( ysf_t( 'fav_add' ), $ysf_title ) ); ?>">
		<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.7 4.5c2.1 0 3.6 1.2 4.3 2.4h2c.7-1.2 2.2-2.4 4.3-2.4 3.7 0 5.8 3.9 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg>
	</button>
	<?php if ( $ysf_thumb ) : ?>
		<img
			class="ysf-item__thumb"
			src="<?php echo esc_url( $ysf_thumb ); ?>"
			alt="<?php echo esc_attr( ysf_field( $ysf_id, 'title' ) ); ?>"
			width="96"
			height="96"
			loading="lazy"
			decoding="async"
		>
	<?php endif; ?>

	<div class="ysf-item__body">
		<h3 class="ysf-item__title">
			<button type="button" class="ysf-item__open" data-ysf-detail aria-haspopup="dialog"><?php echo esc_html( $ysf_title ); ?></button>
		</h3>
		<div class="ysf-item__tags">
			<?php ysf_the_item_tags( $ysf_id ); ?>
			<?php if ( $ysf_sold ) : ?>
				<span class="ysf-tag ysf-tag--bad"><?php echo esc_html( ysf_t( 'sold_out' ) ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( $ysf_desc ) : ?>
			<p class="ysf-item__desc"><?php echo esc_html( wp_strip_all_tags( $ysf_desc ) ); ?></p>
		<?php endif; ?>
		<?php ysf_the_item_notes( $ysf_id ); ?>

		<?php if ( $ysf_cal || $ysf_prep || $ysf_allerg ) : ?>
			<p class="ysf-card__meta">
				<?php if ( $ysf_prep ) : ?>
					<span><?php echo esc_html( $ysf_prep . ' ' . ysf_t( 'prep_time' ) ); ?></span>
				<?php endif; ?>
				<?php if ( $ysf_cal ) : ?>
					<span><?php echo esc_html( $ysf_cal . ' ' . ysf_t( 'calories' ) ); ?></span>
				<?php endif; ?>
				<?php if ( $ysf_allerg ) : ?>
					<span><?php echo esc_html( ysf_t( 'allergens' ) . ': ' . $ysf_allerg ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="ysf-item__side">
		<?php if ( $ysf_show_cart && ! $ysf_sold && ysf_get_item_sizes( $ysf_id ) ) : ?>
			<?php ysf_the_size_select( $ysf_id ); ?>
		<?php elseif ( ysf_get_item_sizes( $ysf_id ) ) : ?>
			<?php ysf_the_sizes_info( $ysf_id ); ?>
		<?php else : ?>
			<?php ysf_the_price( $ysf_id ); ?>
		<?php endif; ?>
		<?php ysf_the_campaign_note( $ysf_id ); ?>
		<?php ysf_add_to_cart_button( $ysf_id, $ysf_show_cart ); ?>
	</div>
</article>
<?php else : ?>
<article
	class="ysf-item<?php echo $ysf_thumb ? ' ysf-item--embed' : ' ysf-item--noimg'; ?><?php echo $ysf_sold ? ' ysf-item--soldout' : ''; ?><?php echo $ysf_show_cart ? ' ysf-item--order' : ''; ?>"
	data-ysf-item
	data-id="<?php echo esc_attr( $ysf_id ); ?>"
	data-cats="<?php echo esc_attr( is_wp_error( $ysf_terms ) ? '' : implode( ' ', $ysf_terms ) ); ?>"
	data-diet="<?php echo esc_attr( implode( ' ', $ysf_diet ) ); ?>"
	data-search="<?php echo esc_attr( $ysf_search ); ?>"
	<?php if ( $ysf_full ) : ?>
		data-full="<?php echo esc_url( $ysf_full ); ?>"
	<?php endif; ?>
	<?php if ( $ysf_ingr ) : ?>
		data-ingredients="<?php echo esc_attr( wp_json_encode( $ysf_ingr ) ); ?>"
	<?php endif; ?>
	<?php if ( $ysf_opts ) : ?>
		data-options="<?php echo esc_attr( wp_json_encode( $ysf_opts ) ); ?>"
	<?php endif; ?>
>
	<button type="button" class="ysf-fav" data-ysf-fav="<?php echo esc_attr( $ysf_id ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( sprintf( ysf_t( 'fav_add' ), $ysf_title ) ); ?>">
		<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.7 4.5c2.1 0 3.6 1.2 4.3 2.4h2c.7-1.2 2.2-2.4 4.3-2.4 3.7 0 5.8 3.9 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg>
	</button>
	<?php if ( $ysf_thumb ) : ?>
		<div class="ysf-item__media">
			<img
				class="ysf-item__thumb"
				src="<?php echo esc_url( $ysf_thumb ); ?>"
				alt="<?php echo esc_attr( ysf_field( $ysf_id, 'title' ) ); ?>"
				width="960"
				height="540"
				loading="lazy"
				decoding="async"
			>
		</div>
	<?php endif; ?>

	<div class="ysf-item__body">
		<h3 class="ysf-item__title">
			<button type="button" class="ysf-item__open" data-ysf-detail aria-haspopup="dialog"><?php echo esc_html( $ysf_title ); ?></button>
		</h3>
		<div class="ysf-item__tags">
			<?php ysf_the_item_tags( $ysf_id ); ?>
			<?php if ( $ysf_sold ) : ?>
				<span class="ysf-tag ysf-tag--bad"><?php echo esc_html( ysf_t( 'sold_out' ) ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( $ysf_desc ) : ?>
			<p class="ysf-item__desc"><?php echo esc_html( wp_strip_all_tags( $ysf_desc ) ); ?></p>
		<?php endif; ?>
		<?php ysf_the_item_notes( $ysf_id ); ?>

		<?php if ( $ysf_cal || $ysf_prep || $ysf_allerg ) : ?>
			<p class="ysf-card__meta">
				<?php if ( $ysf_prep ) : ?>
					<span><?php echo esc_html( $ysf_prep . ' ' . ysf_t( 'prep_time' ) ); ?></span>
				<?php endif; ?>
				<?php if ( $ysf_cal ) : ?>
					<span><?php echo esc_html( $ysf_cal . ' ' . ysf_t( 'calories' ) ); ?></span>
				<?php endif; ?>
				<?php if ( $ysf_allerg ) : ?>
					<span><?php echo esc_html( ysf_t( 'allergens' ) . ': ' . $ysf_allerg ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<div class="ysf-item__side">
			<?php if ( ysf_get_item_sizes( $ysf_id ) ) : ?>
				<?php ysf_the_sizes_info( $ysf_id ); ?>
			<?php else : ?>
				<?php ysf_the_price( $ysf_id ); ?>
			<?php endif; ?>
			<?php ysf_the_campaign_note( $ysf_id ); ?>
			<?php
			if ( $ysf_show_cart && ! $ysf_sold ) {
				ysf_add_to_cart_button( $ysf_id, true );
			}
			?>
		</div>
	</div>
</article>
<?php endif; ?>
