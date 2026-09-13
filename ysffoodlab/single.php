<?php
/**
 * Tek yazı / menü ürünü / kampanya görünümü.
 *
 * @package ysffoodlab
 */

get_header();

while ( have_posts() ) :
	the_post();

	$ysf_id      = get_the_ID();
	$ysf_type    = get_post_type();
	$ysf_content = ysf_field( $ysf_id, 'content' );
	?>

	<section class="ysf-page-hero">
		<div class="ysf-wrap">
			<?php ysf_breadcrumb(); ?>
			<h1><?php echo esc_html( ysf_field( $ysf_id, 'title' ) ); ?></h1>

			<?php if ( 'post' === $ysf_type ) : ?>
				<p><?php echo esc_html( get_the_date() ); ?></p>
			<?php endif; ?>

			<?php if ( 'ysf_menu_item' === $ysf_type ) : ?>
				<p>
					<?php
					$ysf_terms = get_the_terms( $ysf_id, 'ysf_menu_cat' );
					if ( $ysf_terms && ! is_wp_error( $ysf_terms ) ) {
						$ysf_names = array();
						foreach ( $ysf_terms as $ysf_term ) {
							$ysf_names[] = ysf_term_name( $ysf_term );
						}
						echo esc_html( implode( ', ', $ysf_names ) );
					}
					?>
				</p>
			<?php endif; ?>
		</div>
	</section>

	<section class="ysf-section">
		<div class="ysf-wrap">
			<div class="ysf-content-layout <?php echo ( 'post' === $ysf_type && is_active_sidebar( 'ysf-sidebar' ) ) ? 'has-sidebar' : ''; ?>">
				<div>
					<?php if ( has_post_thumbnail() ) : ?>
						<figure style="margin:0 0 32px">
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

					<?php if ( 'ysf_menu_item' === $ysf_type ) : ?>
						<div class="ysf-item ysf-item--noimg" style="margin-bottom:28px">
							<div class="ysf-item__body">
								<h2 class="ysf-item__title" style="margin:0">
									<?php echo esc_html( ysf_field( $ysf_id, 'title' ) ); ?>
									<?php foreach ( ysf_item_badges( $ysf_id ) as $ysf_badge ) : ?>
										<span class="ysf-tag <?php echo esc_attr( $ysf_badge['class'] ); ?>"><?php echo esc_html( $ysf_badge['label'] ); ?></span>
									<?php endforeach; ?>
								</h2>
							</div>
							<div class="ysf-item__side">
								<?php ysf_the_price( $ysf_id ); ?>
								<?php ysf_add_to_cart_button( $ysf_id ); ?>
							</div>
						</div>
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

					<?php if ( 'post' === $ysf_type ) : ?>
						<?php
						$ysf_tags = get_the_tag_list( '', ' ' );
						if ( $ysf_tags ) :
							?>
							<p style="margin-top:24px"><?php echo wp_kses_post( $ysf_tags ); ?></p>
						<?php endif; ?>

						<div class="ysf-btn-row" style="margin-top:32px">
							<?php
							$ysf_prev = get_previous_post();
							$ysf_next = get_next_post();
							?>
							<?php if ( $ysf_prev ) : ?>
								<a class="ysf-btn ysf-btn--ghost ysf-btn--sm" href="<?php echo esc_url( ysf_localize_url( get_permalink( $ysf_prev ) ) ); ?>">
									‹ <?php echo esc_html( wp_trim_words( ysf_field( $ysf_prev->ID, 'title' ), 6, '…' ) ); ?>
								</a>
							<?php endif; ?>
							<?php if ( $ysf_next ) : ?>
								<a class="ysf-btn ysf-btn--ghost ysf-btn--sm" href="<?php echo esc_url( ysf_localize_url( get_permalink( $ysf_next ) ) ); ?>">
									<?php echo esc_html( wp_trim_words( ysf_field( $ysf_next->ID, 'title' ), 6, '…' ) ); ?> ›
								</a>
							<?php endif; ?>
						</div>

						<?php
						if ( comments_open() || get_comments_number() ) {
							comments_template();
						}
						?>
					<?php endif; ?>
				</div>

				<?php if ( 'post' === $ysf_type && is_active_sidebar( 'ysf-sidebar' ) ) : ?>
					<aside class="ysf-sidebar">
						<?php dynamic_sidebar( 'ysf-sidebar' ); ?>
					</aside>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
endwhile;

get_footer();
