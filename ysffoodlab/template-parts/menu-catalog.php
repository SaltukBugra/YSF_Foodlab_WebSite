<?php
/**
 * Kategorili, aramalı menü listesi ve yapışkan kategori navigasyonu (Scrollspy).
 *
 * @package ysffoodlab
 *
 * @var array $args {
 *     @type bool   $show_cart Sepete ekle butonu gösterilsin mi.
 *     @type string $layout    embed (menü) veya list (online siparişin satır görünümü).
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

$ysf_catalog_data = array();

foreach ( $ysf_terms as $ysf_term ) {
	$ysf_items = ysf_get_menu_items( array( 'category' => $ysf_term->term_id ) );

	if ( ! $ysf_items ) {
		continue;
	}

	$ysf_catalog_data[] = array(
		'slug'        => $ysf_term->slug,
		'name'        => ysf_term_name( $ysf_term ),
		'description' => $ysf_term->description,
		'items'       => $ysf_items,
		'count'       => count( $ysf_items ),
	);
}

// Kategorisiz ürünler varsa "Diğer" başlığı altında listele
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

if ( $ysf_uncategorized ) {
	$ysf_catalog_data[] = array(
		'slug'        => 'diger',
		'name'        => __( 'Diğer', 'ysffoodlab' ),
		'description' => '',
		'items'       => $ysf_uncategorized,
		'count'       => count( $ysf_uncategorized ),
	);
}
?>

<?php if ( $ysf_catalog_data ) : ?>
	<div class="ysf-cat-layout ysf-cat-layout--<?php echo esc_attr( $ysf_layout ); ?>" data-ysf-cat-layout>

		<!-- Yan / Üst Yapışkan Kategori Menüsü (Scrollspy) -->
		<aside class="ysf-cat-nav" data-ysf-cat-nav aria-label="<?php echo esc_attr( ysf_t( 'menu_categories' ) ); ?>">
			<div class="ysf-cat-nav__track">
				<div class="ysf-cat-nav__head">
					<span class="ysf-cat-nav__title"><?php ysf_e( 'categories' ); ?></span>
				</div>
				<nav class="ysf-cat-nav__list" role="tablist">
					<?php foreach ( $ysf_catalog_data as $ysf_idx => $ysf_cat ) : ?>
						<a
							href="#kategori-<?php echo esc_attr( $ysf_cat['slug'] ); ?>"
							class="ysf-cat-nav__link<?php echo 0 === $ysf_idx ? ' is-active' : ''; ?>"
							data-ysf-cat-link="<?php echo esc_attr( $ysf_cat['slug'] ); ?>"
							role="tab"
							aria-selected="<?php echo 0 === $ysf_idx ? 'true' : 'false'; ?>"
						>
							<span class="ysf-cat-nav__label"><?php echo esc_html( $ysf_cat['name'] ); ?></span>
							<span class="ysf-cat-nav__count"><?php echo esc_html( (string) $ysf_cat['count'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>
			</div>
		</aside>

		<!-- Ana Menü İçeriği -->
		<div class="ysf-cat-main">
			<div class="ysf-cat-toolbar">
				<div class="ysf-field ysf-cat-search">
					<label for="ysf-menu-search" class="ysf-visually-hidden"><?php ysf_e( 'menu_search' ); ?></label>
					<input
						type="search"
						id="ysf-menu-search"
						data-ysf-menu-search
						placeholder="<?php echo esc_attr( ysf_t( 'menu_search' ) ); ?>"
						autocomplete="off"
					>
				</div>

				<div class="ysf-chips" role="group" aria-label="<?php echo esc_attr( ysf_t( 'diet_filters' ) ); ?>">
					<button type="button" class="ysf-chip" data-ysf-diet="vegan" aria-pressed="false"><?php ysf_e( 'vegan' ); ?></button>
					<button type="button" class="ysf-chip" data-ysf-diet="vegetarian" aria-pressed="false"><?php ysf_e( 'vegetarian' ); ?></button>
					<button type="button" class="ysf-chip" data-ysf-diet="glutenfree" aria-pressed="false"><?php ysf_e( 'glutenfree' ); ?></button>
					<button type="button" class="ysf-chip" data-ysf-diet="nospicy" aria-pressed="false"><?php ysf_e( 'diet_nospicy' ); ?></button>
					<button type="button" class="ysf-chip ysf-chip--fav" data-ysf-fav-filter aria-pressed="false" hidden>
						<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M12 21s-7.5-4.6-9.6-9.2C.9 8.4 3 4.5 6.7 4.5c2.1 0 3.6 1.2 4.3 2.4h2c.7-1.2 2.2-2.4 4.3-2.4 3.7 0 5.8 3.9 4.3 7.3C19.5 16.4 12 21 12 21z"/></svg>
						<?php ysf_e( 'fav_filter' ); ?>
					</button>
				</div>
				<p class="ysf-chips__note"><?php ysf_e( 'diet_note' ); ?></p>
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

			<?php foreach ( $ysf_catalog_data as $ysf_cat ) : ?>
				<div class="ysf-menu-group" data-ysf-group="<?php echo esc_attr( $ysf_cat['slug'] ); ?>">
					<div class="ysf-menu-group__head">
						<h2 id="kategori-<?php echo esc_attr( $ysf_cat['slug'] ); ?>"><?php echo esc_html( $ysf_cat['name'] ); ?></h2>
						<span class="ysf-menu-group__rule"></span>
					</div>

					<?php if ( $ysf_cat['description'] ) : ?>
						<p class="ysf-lead" style="margin-top:-12px"><?php echo esc_html( $ysf_cat['description'] ); ?></p>
					<?php endif; ?>

					<div class="<?php echo esc_attr( $ysf_grid_class ); ?>">
						<?php
						global $post;
						foreach ( $ysf_cat['items'] as $ysf_item ) :
							$post = $ysf_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $post );
							get_template_part( 'template-parts/menu-item', null, $ysf_item_args );
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</div>
			<?php endforeach; ?>
			</div>
		</div>

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
