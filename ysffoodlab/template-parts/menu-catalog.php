<?php
/**
 * Kategorili, aramalı menü listesi.
 *
 * @package ysffoodlab
 *
 * @var array $args {
 *     @type bool   $show_cart Sepete ekle butonu gösterilsin mi.
 *     @type string $layout    embed (menü) veya list (online siparişin eski satır görünümü).
 * }
 */

$ysf_show_cart = ! empty( $args['show_cart'] );
$ysf_layout    = ( isset( $args['layout'] ) && 'list' === $args['layout'] ) ? 'list' : 'embed';
$ysf_item_args = array(
	'show_cart' => $ysf_show_cart,
	'layout'    => $ysf_layout,
);
$ysf_grid_class = ( 'list' === $ysf_layout ) ? 'ysf-grid' : 'ysf-grid ysf-grid--menu';

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
?>

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

	<div class="ysf-menu ysf-menu--<?php echo esc_attr( $ysf_layout ); ?>">

	<?php if ( 'embed' === $ysf_layout ) : ?>
	<svg class="ysf-menu-clips" width="0" height="0" aria-hidden="true" focusable="false">
		<defs>
			<clipPath id="ysf-s-left" clipPathUnits="objectBoundingBox">
				<path d="M0.04,0 H0.88 C0.88,0.083 1,0.167 1,0.25 C1,0.333 0.88,0.417 0.88,0.5 C0.88,0.583 0.76,0.667 0.76,0.75 C0.76,0.833 0.88,0.917 0.88,1 H0.04 C0,1 0,0.99 0,0.96 V0.04 C0,0.01 0,0 0.04,0 Z" />
			</clipPath>
			<clipPath id="ysf-s-right" clipPathUnits="objectBoundingBox">
				<path d="M0.12,0 H0.96 C1,0 1,0.01 1,0.04 V0.96 C1,0.99 1,1 0.96,1 H0.12 C0.12,0.917 0,0.833 0,0.75 C0,0.667 0.12,0.583 0.12,0.5 C0.12,0.417 0.24,0.333 0.24,0.25 C0.24,0.167 0.12,0.083 0.12,0 Z" />
			</clipPath>
		</defs>
	</svg>
	<?php endif; ?>

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

			<div class="<?php echo esc_attr( $ysf_grid_class ); ?>">
				<?php
				global $post;
				foreach ( $ysf_items as $ysf_item ) :
					$post = $ysf_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $post );
					get_template_part( 'template-parts/menu-item', null, $ysf_item_args );
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		</div>
	<?php endforeach; ?>

	<?php
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
			<div class="<?php echo esc_attr( $ysf_grid_class ); ?>">
				<?php
				global $post;
				foreach ( $ysf_uncategorized as $ysf_item ) :
					$post = $ysf_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $post );
					get_template_part( 'template-parts/menu-item', null, $ysf_item_args );
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		</div>
	<?php endif; ?>
	</div>

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
