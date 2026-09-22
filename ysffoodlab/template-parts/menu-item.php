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
?>
<?php if ( 'list' === $ysf_layout ) : ?>
<article
	class="ysf-item<?php echo $ysf_thumb ? '' : ' ysf-item--noimg'; ?><?php echo $ysf_sold ? ' ysf-item--soldout' : ''; ?>"
	data-ysf-item
	data-cats="<?php echo esc_attr( is_wp_error( $ysf_terms ) ? '' : implode( ' ', $ysf_terms ) ); ?>"
	data-search="<?php echo esc_attr( $ysf_search ); ?>"
>
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
			<?php echo esc_html( ysf_field( $ysf_id, 'title' ) ); ?>
			<?php ysf_the_item_tags( $ysf_id ); ?>
		</h3>

		<?php if ( $ysf_desc ) : ?>
			<p class="ysf-item__desc"><?php echo esc_html( wp_strip_all_tags( $ysf_desc ) ); ?></p>
		<?php endif; ?>

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
	data-cats="<?php echo esc_attr( is_wp_error( $ysf_terms ) ? '' : implode( ' ', $ysf_terms ) ); ?>"
	data-search="<?php echo esc_attr( $ysf_search ); ?>"
>
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
			<?php echo esc_html( ysf_field( $ysf_id, 'title' ) ); ?>
			<?php ysf_the_item_tags( $ysf_id ); ?>
			<?php if ( $ysf_sold ) : ?>
				<span class="ysf-tag ysf-tag--bad"><?php echo esc_html( ysf_t( 'sold_out' ) ); ?></span>
			<?php endif; ?>
		</h3>

		<?php if ( $ysf_desc ) : ?>
			<p class="ysf-item__desc"><?php echo esc_html( wp_strip_all_tags( $ysf_desc ) ); ?></p>
		<?php endif; ?>

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
