<?php
/**
 * Blog yazısı kartı.
 *
 * @package ysffoodlab
 */

$ysf_id = get_the_ID();
?>
<article id="post-<?php echo esc_attr( $ysf_id ); ?>" <?php post_class( 'ysf-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="ysf-card__media">
			<a href="<?php echo esc_url( ysf_localize_url( get_permalink() ) ); ?>">
				<?php
				the_post_thumbnail(
					'ysf-card',
					array(
						'alt'      => esc_attr( ysf_field( $ysf_id, 'title' ) ),
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				?>
			</a>
		</div>
	<?php endif; ?>

	<div class="ysf-card__body">
		<span class="ysf-card__meta">
			<?php echo esc_html( get_the_date() ); ?>
			<?php
			$ysf_cats = get_the_category_list( ', ' );
			if ( $ysf_cats ) :
				?>
				· <?php echo wp_kses_post( $ysf_cats ); ?>
			<?php endif; ?>
		</span>

		<h2 class="ysf-card__title">
			<a href="<?php echo esc_url( ysf_localize_url( get_permalink() ) ); ?>">
				<?php echo esc_html( ysf_field( $ysf_id, 'title' ) ); ?>
			</a>
		</h2>

		<p class="ysf-card__text">
			<?php echo esc_html( wp_trim_words( wp_strip_all_tags( ysf_field( $ysf_id, 'excerpt' ) ), 24, '…' ) ); ?>
		</p>

		<div class="ysf-card__foot">
			<a class="ysf-btn ysf-btn--sm ysf-btn--ghost" href="<?php echo esc_url( ysf_localize_url( get_permalink() ) ); ?>">
				<?php ysf_e( 'read_more' ); ?>
			</a>
		</div>
	</div>
</article>
