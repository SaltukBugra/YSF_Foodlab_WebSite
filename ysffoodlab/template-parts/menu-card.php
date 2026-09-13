<?php
/**
 * Menü kartı (ana sayfada öne çıkan lezzetler).
 *
 * @package ysffoodlab
 */

$ysf_id     = get_the_ID();
$ysf_badges = ysf_item_badges( $ysf_id );
$ysf_desc   = ysf_field( $ysf_id, 'excerpt' );
?>
<article class="ysf-card">
	<?php if ( has_post_thumbnail( $ysf_id ) ) : ?>
		<div class="ysf-card__media">
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
			<?php if ( ! empty( $ysf_badges[0] ) ) : ?>
				<span class="ysf-card__badge"><?php echo esc_html( $ysf_badges[0]['label'] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="ysf-card__body">
		<h3 class="ysf-card__title"><?php echo esc_html( ysf_field( $ysf_id, 'title' ) ); ?></h3>

		<?php if ( $ysf_desc ) : ?>
			<p class="ysf-card__text"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $ysf_desc ), 18, '…' ) ); ?></p>
		<?php endif; ?>

		<?php if ( count( $ysf_badges ) > 1 ) : ?>
			<p class="ysf-card__meta">
				<?php foreach ( array_slice( $ysf_badges, 1 ) as $ysf_badge ) : ?>
					<span class="ysf-tag <?php echo esc_attr( $ysf_badge['class'] ); ?>"><?php echo esc_html( $ysf_badge['label'] ); ?></span>
				<?php endforeach; ?>
			</p>
		<?php endif; ?>

		<div class="ysf-card__foot">
			<?php ysf_the_price( $ysf_id ); ?>
			<?php ysf_add_to_cart_button( $ysf_id ); ?>
		</div>
	</div>
</article>
