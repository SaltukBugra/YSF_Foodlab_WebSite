<?php
/**
 * Template Name: Menü Sayfası
 * Description: Kategorili, filtreli ve aramalı dijital menü.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_terms = get_terms(
	array(
		'taxonomy'   => 'ysf_menu_cat',
		'hide_empty' => true,
		'orderby'    => 'term_order',
	)
);

if ( is_wp_error( $ysf_terms ) ) {
	$ysf_terms = array();
}

$ysf_order_url = ysf_localize_url( ysf_get_page_url_by_template( 'template-order.php' ) );
$ysf_page_id   = get_the_ID();
?>

<section class="ysf-page-hero">
	<div class="ysf-wrap">
		<?php ysf_breadcrumb(); ?>
		<h1><?php echo esc_html( ysf_field( $ysf_page_id, 'title' ) ); ?></h1>
		<?php $ysf_intro = ysf_field( $ysf_page_id, 'excerpt' ); ?>
		<?php if ( $ysf_intro ) : ?>
			<p><?php echo esc_html( wp_strip_all_tags( $ysf_intro ) ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="ysf-section ysf-section--tight">
	<div class="ysf-wrap">
		<?php if ( $ysf_terms ) : ?>
			<div class="ysf-field" style="max-width:360px;margin-bottom:8px">
				<label for="ysf-menu-search" class="ysf-visually-hidden"><?php ysf_e( 'menu_search' ); ?></label>
				<input
					type="search"
					id="ysf-menu-search"
					data-ysf-menu-search
					placeholder="<?php echo esc_attr( ysf_t( 'menu_search' ) ); ?>"
					autocomplete="off"
				>
			</div>

			<div class="ysf-filters" role="tablist" aria-label="<?php echo esc_attr( ysf_t( 'nav_menu' ) ); ?>">
				<button type="button" class="ysf-filter is-active" data-ysf-filter="all"><?php ysf_e( 'menu_all' ); ?></button>
				<?php foreach ( $ysf_terms as $ysf_term ) : ?>
					<button type="button" class="ysf-filter" data-ysf-filter="<?php echo esc_attr( $ysf_term->slug ); ?>">
						<?php echo esc_html( ysf_term_name( $ysf_term ) ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<p class="ysf-empty" data-ysf-menu-empty hidden><?php ysf_e( 'menu_no_result' ); ?></p>

			<?php foreach ( $ysf_terms as $ysf_term ) : ?>
				<?php $ysf_items = ysf_get_menu_items( array( 'category' => $ysf_term->term_id ) ); ?>

				<?php if ( ! $ysf_items ) : ?>
					<?php continue; ?>
				<?php endif; ?>

				<div class="ysf-menu-group" data-ysf-group="<?php echo esc_attr( $ysf_term->slug ); ?>">
					<div class="ysf-menu-group__head">
						<h2 id="kategori-<?php echo esc_attr( $ysf_term->slug ); ?>"><?php echo esc_html( ysf_term_name( $ysf_term ) ); ?></h2>
						<span class="ysf-menu-group__rule"></span>
					</div>

					<?php if ( $ysf_term->description ) : ?>
						<p class="ysf-lead" style="margin-top:-12px"><?php echo esc_html( $ysf_term->description ); ?></p>
					<?php endif; ?>

					<div class="ysf-grid">
						<?php
						global $post;
						foreach ( $ysf_items as $ysf_item ) :
							$post = $ysf_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $post );
							get_template_part( 'template-parts/menu-item' );
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</div>
			<?php endforeach; ?>

			<?php
			// Kategorisi olmayan ürünler.
			$ysf_uncategorized = get_posts(
				array(
					'post_type'      => 'ysf_menu_item',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'orderby'        => array(
						'menu_order' => 'ASC',
						'title'      => 'ASC',
					),
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'ysf_menu_cat',
							'operator' => 'NOT EXISTS',
						),
					),
				)
			);
			?>

			<?php if ( $ysf_uncategorized ) : ?>
				<div class="ysf-menu-group" data-ysf-group="diger">
					<div class="ysf-menu-group__head">
						<h2><?php esc_html_e( 'Diğer', 'ysffoodlab' ); ?></h2>
						<span class="ysf-menu-group__rule"></span>
					</div>
					<div class="ysf-grid">
						<?php
						global $post;
						foreach ( $ysf_uncategorized as $ysf_item ) :
							$post = $ysf_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $post );
							get_template_part( 'template-parts/menu-item' );
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</div>
			<?php endif; ?>

		<?php else : ?>
			<div class="ysf-empty">
				<p><?php ysf_e( 'menu_empty' ); ?></p>
				<?php if ( current_user_can( 'edit_posts' ) ) : ?>
					<a class="ysf-btn ysf-btn--sm" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ysf_menu_item' ) ); ?>">
						<?php esc_html_e( 'İlk ürünü ekle', 'ysffoodlab' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ysf_has_translated_content( $ysf_page_id ) ) : ?>
			<div class="ysf-prose" style="margin-top:48px">
				<?php ysf_the_translated_content( $ysf_page_id ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ( $ysf_order_url && ysf_get_option( 'ysf_orders_enabled', true ) ) : ?>
<section class="ysf-section ysf-section--dark ysf-section--tight">
	<div class="ysf-wrap" style="text-align:center">
		<h2><?php ysf_e( 'cta_order' ); ?></h2>
		<p class="ysf-lead"><?php echo esc_html( ysf_option_i18n( 'ysf_order_note', '' ) ); ?></p>
		<a class="ysf-btn" href="<?php echo esc_url( $ysf_order_url ); ?>"><?php ysf_e( 'checkout' ); ?></a>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
