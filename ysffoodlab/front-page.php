<?php
/**
 * Ana sayfa: kapak, duyurular, kampanyalar, öne çıkan menü, rezervasyon çağrısı.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_order_url = ysf_localize_url( ysf_get_page_url_by_template( 'template-order.php' ) );
$ysf_res_url   = ysf_localize_url( ysf_get_page_url_by_template( 'template-reservation.php' ) );
$ysf_menu_url  = ysf_localize_url( ysf_get_page_url_by_template( 'template-menu.php' ) );
$ysf_slides    = ysf_hero_slides();
$ysf_open      = ysf_is_open_now();
$ysf_hours     = ysf_get_hours();
$ysf_today     = ysf_today_key();
$ysf_orders_on = ysf_get_option( 'ysf_orders_enabled', true ) && $ysf_order_url;
?>

<section class="ysf-hero">
	<?php if ( $ysf_slides ) : ?>
		<div class="ysf-hero__media" data-ysf-slider>
			<?php foreach ( $ysf_slides as $ysf_slide_index => $ysf_slide ) : ?>
				<div class="ysf-hero__slide <?php echo 0 === $ysf_slide_index ? 'is-active' : ''; ?>" data-ysf-slide>
					<img
						src="<?php echo esc_url( $ysf_slide ); ?>"
						alt=""
						<?php if ( 0 === $ysf_slide_index ) : ?>
							fetchpriority="high"
						<?php else : ?>
							loading="lazy"
						<?php endif; ?>
						decoding="async"
					>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( count( $ysf_slides ) > 1 ) : ?>
			<div class="ysf-hero__dots" data-ysf-slider-dots role="group" aria-label="<?php echo esc_attr( ysf_t( 'announcements' ) ); ?>">
				<?php foreach ( $ysf_slides as $ysf_dot_index => $ysf_dot ) : ?>
					<button
						type="button"
						class="ysf-hero__dot <?php echo 0 === $ysf_dot_index ? 'is-active' : ''; ?>"
						data-ysf-slide-to="<?php echo esc_attr( $ysf_dot_index ); ?>"
						aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slayt sırası. */ __( '%d. görsel', 'ysffoodlab' ), $ysf_dot_index + 1 ) ); ?>"
					></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<div class="ysf-wrap ysf-hero__inner">
		<span class="ysf-status <?php echo $ysf_open ? '' : 'ysf-status--closed'; ?>" data-ysf-status>
			<span class="ysf-status__dot"></span>
			<span data-ysf-status-label><?php echo esc_html( $ysf_open ? ysf_t( 'open_now' ) : ysf_t( 'closed_now' ) ); ?></span>
			<?php if ( ! empty( $ysf_hours[ $ysf_today ] ) ) : ?>
				<span>· <?php echo esc_html( $ysf_hours[ $ysf_today ] ); ?></span>
			<?php endif; ?>
		</span>

		<h1><?php echo esc_html( ysf_option_i18n( 'ysf_hero_title', get_bloginfo( 'name' ) ) ); ?></h1>

		<?php $ysf_hero_text = ysf_option_i18n( 'ysf_hero_text', get_bloginfo( 'description' ) ); ?>
		<?php if ( $ysf_hero_text ) : ?>
			<p class="ysf-hero__sub"><?php echo esc_html( $ysf_hero_text ); ?></p>
		<?php endif; ?>

		<div class="ysf-btn-row">
			<?php if ( $ysf_orders_on ) : ?>
				<a class="ysf-btn" href="<?php echo esc_url( $ysf_order_url ); ?>"><?php ysf_e( 'cta_order' ); ?></a>
			<?php endif; ?>
			<?php if ( $ysf_res_url ) : ?>
				<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( $ysf_res_url ); ?>"><?php ysf_e( 'cta_reserve' ); ?></a>
			<?php endif; ?>
			<?php if ( $ysf_menu_url ) : ?>
				<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( $ysf_menu_url ); ?>"><?php ysf_e( 'cta_menu' ); ?></a>
			<?php endif; ?>
		</div>

		<div class="ysf-hero__meta">
			<?php if ( ysf_get_option( 'ysf_phone', '' ) ) : ?>
				<div>
					<?php ysf_e( 'contact_phone' ); ?>
					<strong><?php echo esc_html( ysf_get_option( 'ysf_phone', '' ) ); ?></strong>
				</div>
			<?php endif; ?>
			<?php if ( ysf_get_option( 'ysf_address', '' ) ) : ?>
				<div>
					<?php ysf_e( 'contact_address' ); ?>
					<strong><?php echo esc_html( wp_trim_words( ysf_get_option( 'ysf_address', '' ), 8, '…' ) ); ?></strong>
				</div>
			<?php endif; ?>
			<?php if ( ysf_get_option( 'ysf_cuisine', '' ) ) : ?>
				<div>
					<?php echo esc_html( ysf_t( 'menu_eyebrow' ) ); ?>
					<strong><?php echo esc_html( ysf_get_option( 'ysf_cuisine', '' ) ); ?></strong>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
// --- Kampanyalar ve duyurular ---------------------------------------------
$ysf_campaigns = ysf_get_campaigns( array( 'limit' => 12, 'keep_expired' => true ) );

if ( $ysf_campaigns ) :
	?>
	<section class="ysf-section ysf-section--soft">
		<div class="ysf-wrap">
			<div class="ysf-section-head ysf-section-head--center">
				<span class="ysf-eyebrow"><?php ysf_e( 'campaigns_eyebrow' ); ?></span>
				<h2><?php ysf_e( 'campaigns_title' ); ?></h2>
			</div>

			<div class="ysf-grid ysf-grid--3">
				<?php foreach ( $ysf_campaigns as $ysf_campaign ) : ?>
					<?php
					$ysf_expired = function_exists( 'ysf_campaign_is_expired' ) && ysf_campaign_is_expired( $ysf_campaign->ID );
					$ysf_waiting = ! $ysf_expired && function_exists( 'ysf_campaign_awaits_tomorrow' ) && ysf_campaign_awaits_tomorrow( $ysf_campaign->ID );
					$ysf_pending = ! $ysf_expired && ! $ysf_waiting && function_exists( 'ysf_campaign_before_window' ) && ysf_campaign_before_window( $ysf_campaign->ID );
					$ysf_bounds  = function_exists( 'ysf_campaign_time_bounds' ) ? ysf_campaign_time_bounds( $ysf_campaign->ID ) : null;
					$ysf_live_badge = ( 'en' === ysf_lang() && get_post_meta( $ysf_campaign->ID, '_ysf_badge_en', true ) )
						? get_post_meta( $ysf_campaign->ID, '_ysf_badge_en', true )
						: get_post_meta( $ysf_campaign->ID, '_ysf_badge', true );
					$ysf_badge   = $ysf_expired ? ysf_t( 'campaign_expired' ) : ( $ysf_waiting ? ysf_t( 'campaign_tomorrow' ) : ( $ysf_pending && $ysf_bounds ? sprintf( ysf_t( 'campaign_starts' ), $ysf_bounds['start'] ) : $ysf_live_badge ) );
					$ysf_link    = ( $ysf_expired || $ysf_waiting ) ? '' : get_post_meta( $ysf_campaign->ID, '_ysf_link', true );
					$ysf_end     = get_post_meta( $ysf_campaign->ID, '_ysf_end', true );
					$ysf_meta    = '';

					if ( $ysf_expired ) {
						$ysf_meta = ysf_t( 'campaign_expired' );
					} elseif ( $ysf_waiting ) {
						$ysf_meta = ysf_t( 'campaign_tomorrow' );
					} elseif ( $ysf_pending && $ysf_bounds ) {
						$ysf_meta = sprintf( ysf_t( 'campaign_starts' ), $ysf_bounds['start'] );
					} elseif ( $ysf_end ) {
						$ysf_meta = ysf_t( 'valid_until' ) . ': ' . mysql2date( 'j F Y', $ysf_end );
					}
					?>
					<article
						class="ysf-card<?php echo $ysf_expired ? ' is-expired' : ''; ?><?php echo $ysf_waiting ? ' is-waiting' : ''; ?><?php echo $ysf_pending ? ' is-pending' : ''; ?>"
						data-ysf-camp-notice
						data-title="<?php echo esc_attr( ysf_field( $ysf_campaign->ID, 'title' ) ); ?>"
						data-badge="<?php echo esc_attr( (string) $ysf_live_badge ); ?>"
						data-meta="<?php echo esc_attr( $ysf_end ? ysf_t( 'valid_until' ) . ': ' . mysql2date( 'j F Y', $ysf_end ) : '' ); ?>"
						data-start="<?php echo esc_attr( (string) get_post_meta( $ysf_campaign->ID, '_ysf_start', true ) ); ?>"
						data-end="<?php echo esc_attr( (string) $ysf_end ); ?>"
						data-time-start="<?php echo esc_attr( $ysf_bounds ? $ysf_bounds['start'] : '' ); ?>"
						data-time-end="<?php echo esc_attr( $ysf_bounds ? $ysf_bounds['end'] : '' ); ?>"
					>
						<?php if ( has_post_thumbnail( $ysf_campaign->ID ) ) : ?>
							<div class="ysf-card__media">
								<?php
								echo get_the_post_thumbnail(
									$ysf_campaign->ID,
									'ysf-card',
									array(
										'alt'      => esc_attr( ysf_field( $ysf_campaign->ID, 'title' ) ),
										'loading'  => 'lazy',
										'decoding' => 'async',
									)
								);
								?>
								<?php if ( $ysf_badge ) : ?>
									<span class="ysf-card__badge" data-ysf-camp-badge><?php echo esc_html( $ysf_badge ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<div class="ysf-card__body">
							<?php if ( $ysf_badge && ! has_post_thumbnail( $ysf_campaign->ID ) ) : ?>
								<span class="ysf-tag" data-ysf-camp-badge><?php echo esc_html( $ysf_badge ); ?></span>
							<?php endif; ?>

							<h3 class="ysf-card__title"><?php echo esc_html( ysf_field( $ysf_campaign->ID, 'title' ) ); ?></h3>
							<p class="ysf-card__text"><?php echo esc_html( wp_strip_all_tags( ysf_field( $ysf_campaign->ID, 'excerpt' ) ) ); ?></p>

							<div class="ysf-card__foot">
								<?php if ( $ysf_meta ) : ?>
									<span class="ysf-card__meta" data-ysf-camp-meta><?php echo esc_html( $ysf_meta ); ?></span>
								<?php else : ?>
									<span data-ysf-camp-meta></span>
								<?php endif; ?>

								<?php if ( $ysf_link ) : ?>
									<a class="ysf-btn ysf-btn--sm" href="<?php echo esc_url( ysf_localize_url( $ysf_link ) ); ?>"><?php ysf_e( 'read_more' ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
endif;

// --- Öne çıkan menü -------------------------------------------------------
$ysf_featured = ysf_get_menu_items(
	array(
		'featured' => true,
		'limit'    => 6,
	)
);

if ( ! $ysf_featured ) {
	$ysf_featured = ysf_get_menu_items( array( 'limit' => 6 ) );
}

if ( $ysf_featured ) :
	?>
	<section class="ysf-section">
		<div class="ysf-wrap">
			<div class="ysf-section-head ysf-section-head--center">
				<span class="ysf-eyebrow"><?php ysf_e( 'menu_eyebrow' ); ?></span>
				<h2><?php ysf_e( 'menu_title' ); ?></h2>
			</div>

			<div class="ysf-grid ysf-grid--2">
				<?php
				global $post;
				foreach ( $ysf_featured as $ysf_featured_item ) :
					$post = $ysf_featured_item; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $post );
					get_template_part( 'template-parts/menu-card' );
				endforeach;
				wp_reset_postdata();
				?>
			</div>

			<?php if ( $ysf_menu_url || $ysf_orders_on ) : ?>
				<p class="ysf-btn-row" style="justify-content:center;margin-top:36px">
					<?php if ( $ysf_menu_url ) : ?>
						<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( $ysf_menu_url ); ?>"><?php ysf_e( 'cta_menu' ); ?></a>
					<?php endif; ?>
					<?php if ( $ysf_orders_on ) : ?>
						<a class="ysf-btn" href="<?php echo esc_url( $ysf_order_url ); ?>"><?php ysf_e( 'cta_order' ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
endif;

// --- Hakkımızda -----------------------------------------------------------
$ysf_about_text = ysf_option_i18n( 'ysf_about_text', '' );

if ( $ysf_about_text ) :
	$ysf_about_img = ysf_about_image();
	?>
	<section class="ysf-section ysf-section--soft">
		<div class="ysf-wrap ysf-split ysf-split--media-right">
			<?php if ( $ysf_about_img ) : ?>
				<div class="ysf-split__media">
					<img src="<?php echo esc_url( $ysf_about_img ); ?>" alt="" loading="lazy" decoding="async">
				</div>
			<?php endif; ?>

			<div>
				<span class="ysf-eyebrow"><?php bloginfo( 'name' ); ?></span>
				<h2><?php echo esc_html( ysf_option_i18n( 'ysf_about_title', '' ) ); ?></h2>
				<p class="ysf-lead"><?php echo esc_html( $ysf_about_text ); ?></p>

				<?php ysf_the_social_links( 'ysf-social ysf-social--home' ); ?>

				<?php
				$ysf_about_page = get_page_by_path( 'hakkimizda' );
				if ( $ysf_about_page ) :
					?>
					<p>
						<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( ysf_localize_url( get_permalink( $ysf_about_page ) ) ); ?>">
							<?php ysf_e( 'nav_about' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
endif;

// --- Rakamlar -------------------------------------------------------------
$ysf_facts = array();

for ( $ysf_i = 1; $ysf_i <= 4; $ysf_i++ ) {
	$ysf_num = ysf_get_option( 'ysf_fact_' . $ysf_i . '_num', '' );

	if ( ! $ysf_num ) {
		continue;
	}

	$ysf_facts[] = array(
		'num'   => $ysf_num,
		'label' => ysf_option_i18n( 'ysf_fact_' . $ysf_i . '_label', '' ),
	);
}

if ( $ysf_facts ) :
	?>
	<section class="ysf-section ysf-section--tight">
		<div class="ysf-wrap">
			<div class="ysf-facts">
				<?php foreach ( $ysf_facts as $ysf_fact ) : ?>
					<div class="ysf-fact">
						<span class="ysf-fact__num"><?php echo esc_html( $ysf_fact['num'] ); ?></span>
						<span class="ysf-fact__label"><?php echo esc_html( $ysf_fact['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
endif;
?>

<?php if ( $ysf_res_url && ysf_get_option( 'ysf_res_enabled', true ) ) : ?>
<section class="ysf-section ysf-section--dark">
	<div class="ysf-wrap ysf-split">
		<div>
			<span class="ysf-eyebrow"><?php ysf_e( 'res_title' ); ?></span>
			<h2><?php ysf_e( 'res_lead' ); ?></h2>
			<div class="ysf-btn-row">
				<a class="ysf-btn" href="<?php echo esc_url( $ysf_res_url ); ?>"><?php ysf_e( 'cta_reserve' ); ?></a>
				<?php if ( ysf_whatsapp_url() ) : ?>
					<a class="ysf-btn ysf-btn--wa" href="<?php echo esc_url( ysf_whatsapp_url() ); ?>" target="_blank" rel="noopener noreferrer">
						<?php ysf_e( 'cta_whatsapp' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div>
			<ul class="ysf-info-list">
				<?php foreach ( ysf_reservation_extras() as $ysf_extra ) : ?>
					<li>
						<span class="ysf-info-list__icon">✓</span>
						<span><strong><?php echo esc_html( $ysf_extra ); ?></strong></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
// --- Teslimat platformları ------------------------------------------------
$ysf_platforms = array(
	'ysf_yemeksepeti' => 'Yemeksepeti',
	'ysf_getir'       => 'Getir Yemek',
	'ysf_trendyol'    => 'Trendyol Yemek',
);

$ysf_platform_links = array();

foreach ( $ysf_platforms as $ysf_key => $ysf_label ) {
	$ysf_url = ysf_get_option( $ysf_key, '' );

	if ( $ysf_url ) {
		$ysf_platform_links[ $ysf_label ] = $ysf_url;
	}
}

if ( $ysf_platform_links ) :
	?>
	<section class="ysf-section ysf-section--tight">
		<div class="ysf-wrap" style="text-align:center">
			<span class="ysf-eyebrow" style="justify-content:center"><?php ysf_e( 'nav_order' ); ?></span>
			<div class="ysf-btn-row" style="justify-content:center">
				<?php foreach ( $ysf_platform_links as $ysf_label => $ysf_url ) : ?>
					<a class="ysf-btn ysf-btn--ghost ysf-btn--sm" href="<?php echo esc_url( $ysf_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $ysf_label ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
endif;

// --- Blogdan son yazılar --------------------------------------------------
$ysf_posts = get_posts( array( 'posts_per_page' => 3 ) );

if ( $ysf_posts ) :
	?>
	<section class="ysf-section ysf-section--soft">
		<div class="ysf-wrap">
			<div class="ysf-section-head">
				<span class="ysf-eyebrow"><?php ysf_e( 'blog_title' ); ?></span>
				<h2><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
			</div>

			<div class="ysf-grid ysf-grid--3">
				<?php foreach ( $ysf_posts as $ysf_post ) : ?>
					<article class="ysf-card">
						<?php if ( has_post_thumbnail( $ysf_post->ID ) ) : ?>
							<div class="ysf-card__media">
								<a href="<?php echo esc_url( ysf_localize_url( get_permalink( $ysf_post ) ) ); ?>">
									<?php
									echo get_the_post_thumbnail(
										$ysf_post->ID,
										'ysf-card',
										array(
											'alt'      => esc_attr( ysf_field( $ysf_post->ID, 'title' ) ),
											'loading'  => 'lazy',
											'decoding' => 'async',
										)
									);
									?>
								</a>
							</div>
						<?php endif; ?>

						<div class="ysf-card__body">
							<span class="ysf-card__meta"><?php echo esc_html( get_the_date( '', $ysf_post ) ); ?></span>
							<h3 class="ysf-card__title">
								<a href="<?php echo esc_url( ysf_localize_url( get_permalink( $ysf_post ) ) ); ?>">
									<?php echo esc_html( ysf_field( $ysf_post->ID, 'title' ) ); ?>
								</a>
							</h3>
							<p class="ysf-card__text"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( ysf_field( $ysf_post->ID, 'excerpt' ) ), 18, '…' ) ); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
endif;

// --- Harita ---------------------------------------------------------------
$ysf_map = ysf_get_option( 'ysf_maps_embed', '' );

if ( $ysf_map ) :
	?>
	<section class="ysf-section">
		<div class="ysf-wrap ysf-split">
			<div>
				<span class="ysf-eyebrow"><?php ysf_e( 'contact_title' ); ?></span>
				<h2><?php ysf_e( 'contact_address' ); ?></h2>
				<p class="ysf-lead"><?php echo esc_html( ysf_get_option( 'ysf_address', '' ) ); ?></p>

				<div class="ysf-btn-row">
					<?php if ( ysf_get_option( 'ysf_maps_link', '' ) ) : ?>
						<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( ysf_get_option( 'ysf_maps_link', '' ) ); ?>" target="_blank" rel="noopener noreferrer">
							<?php ysf_e( 'cta_directions' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( ysf_get_option( 'ysf_phone', '' ) ) : ?>
						<a class="ysf-btn" href="tel:<?php echo esc_attr( ysf_digits( ysf_get_option( 'ysf_phone', '' ) ) ); ?>">
							<?php ysf_e( 'cta_call' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<?php ysf_the_social_links( 'ysf-social ysf-social--home' ); ?>
			</div>

			<div>
				<iframe
					class="ysf-map"
					src="<?php echo esc_url( $ysf_map ); ?>"
					title="<?php echo esc_attr( ysf_t( 'contact_address' ) ); ?>"
					loading="lazy"
					referrerpolicy="no-referrer-when-downgrade"
					allowfullscreen
				></iframe>
			</div>
		</div>
	</section>
	<?php
endif;

get_footer();
