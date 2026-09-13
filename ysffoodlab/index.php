<?php
/**
 * Blog listesi ve genel yedek şablon.
 *
 * @package ysffoodlab
 */

get_header();
?>

<section class="ysf-page-hero">
	<div class="ysf-wrap">
		<?php ysf_breadcrumb(); ?>
		<h1>
			<?php
			if ( is_home() && get_option( 'page_for_posts' ) ) {
				echo esc_html( ysf_field( (int) get_option( 'page_for_posts' ), 'title' ) );
			} elseif ( is_search() ) {
				echo esc_html( ysf_t( 'search_results' ) );
			} elseif ( is_archive() ) {
				echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
			} else {
				ysf_e( 'blog_title' );
			}
			?>
		</h1>
		<?php if ( is_search() ) : ?>
			<p><?php echo esc_html( get_search_query() ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="ysf-section">
	<div class="ysf-wrap">
		<div class="ysf-content-layout <?php echo is_active_sidebar( 'ysf-sidebar' ) ? 'has-sidebar' : ''; ?>">
			<div>
				<?php if ( have_posts() ) : ?>
					<div class="ysf-grid ysf-grid--2">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/post-card' );
						endwhile;
						?>
					</div>

					<?php ysf_pagination(); ?>
				<?php else : ?>
					<div class="ysf-empty">
						<p><?php echo esc_html( is_search() ? ysf_t( 'search_empty' ) : ysf_t( 'blog_empty' ) ); ?></p>
						<?php get_search_form(); ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( is_active_sidebar( 'ysf-sidebar' ) ) : ?>
				<aside class="ysf-sidebar">
					<?php dynamic_sidebar( 'ysf-sidebar' ); ?>
				</aside>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
get_footer();
