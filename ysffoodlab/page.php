<?php
/**
 * Varsayılan sayfa şablonu.
 *
 * @package ysffoodlab
 */

get_header();

while ( have_posts() ) :
	the_post();

	$ysf_id = get_the_ID();
	?>

	<section class="ysf-page-hero">
		<div class="ysf-wrap">
			<?php ysf_breadcrumb(); ?>
			<h1><?php echo esc_html( ysf_field( $ysf_id, 'title' ) ); ?></h1>
			<?php $ysf_excerpt = get_the_excerpt(); ?>
			<?php if ( $ysf_excerpt && 'en' === ysf_lang() && get_post_meta( $ysf_id, '_ysf_excerpt_en', true ) ) : ?>
				<p><?php echo esc_html( get_post_meta( $ysf_id, '_ysf_excerpt_en', true ) ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="ysf-section">
		<div class="ysf-wrap">
			<?php if ( has_post_thumbnail() ) : ?>
				<figure style="margin:0 0 40px">
					<?php
					the_post_thumbnail(
						'ysf-hero',
						array(
							'style' => 'border-radius:var(--ysf-radius-lg)',
							'alt'   => esc_attr( ysf_field( $ysf_id, 'title' ) ),
						)
					);
					?>
				</figure>
			<?php endif; ?>

			<div class="ysf-prose">
				<?php
				if ( 'en' === ysf_lang() && get_post_meta( $ysf_id, '_ysf_content_en', true ) ) {
					ysf_the_translated_content( $ysf_id );
				} else {
					the_content();
				}

				wp_link_pages(
					array(
						'before' => '<nav class="ysf-pagination">',
						'after'  => '</nav>',
					)
				);
				?>
			</div>
		</div>
	</section>

	<?php
endwhile;

get_footer();
