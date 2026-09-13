<?php
/**
 * Menü kartı (ana sayfada öne çıkan lezzetler).
 *
 * @package ysffoodlab
 */

$ysf_id    = get_the_ID();
$ysf_cover = ysf_item_cover_badge( $ysf_id );
$ysf_desc  = ysf_field( $ysf_id, 'excerpt' );
?>
<article class="ysf-card<?php echo get_post_meta( $ysf_id, '_ysf_sold_out', true ) ? ' ysf-card--soldout' : ''; ?>">
	<div class="ysf-card__media">
		<?php if ( has_post_thumbnail( $ysf_id ) ) : ?>
			<?php
			echo get_the_post_thumbnail(
				$ysf_id,
				'ysf-card',
				array(
					'alt'      => esc_attr( ysf_field( $ysf_id, 'title' ) ),
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
			?>
		<?php elseif ( ysf_placeholder_image() ) : ?>
			<img src="<?php echo esc_url( ysf_placeholder_image() ); ?>" alt="" loading="lazy" decoding="async">
		<?php endif; ?>

		<?php if ( $ysf_cover ) : ?>
			<span class="ysf-card__badge ysf-tag <?php echo esc_attr( $ysf_cover['class'] ); ?>"><?php echo esc_html( $ysf_cover['label'] ); ?></span>
		<?php endif; ?>
	</div>

	<div class="ysf-card__body">
		<h3 class="ysf-card__title">
			<?php echo esc_html( ysf_field( $ysf_id, 'title' ) ); ?>
			<?php ysf_the_item_tags( $ysf_id ); ?>
		</h3>

		<?php if ( $ysf_desc ) : ?>
			<p class="ysf-card__text"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $ysf_desc ), 18, '…' ) ); ?></p>
		<?php endif; ?>

		<div class="ysf-card__foot">
			<?php ysf_the_price( $ysf_id ); ?>
			<?php ysf_add_to_cart_button( $ysf_id ); ?>
		</div>
	</div>
</article>
