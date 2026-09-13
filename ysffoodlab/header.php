<?php
/**
 * Sayfa başlığı: duyuru şeridi, logo, menü, sepet.
 *
 * @package ysffoodlab
 */

$ysf_order_url = ysf_localize_url( ysf_get_page_url_by_template( 'template-order.php' ) );
$ysf_res_url   = ysf_localize_url( ysf_get_page_url_by_template( 'template-reservation.php' ) );
$ysf_menu_url  = ysf_localize_url( ysf_get_page_url_by_template( 'template-menu.php' ) );
$ysf_phone     = ysf_get_option( 'ysf_phone', '' );
$ysf_bar_items = ysf_get_option( 'ysf_show_bar', true ) ? ysf_get_campaigns( array( 'bar_only' => true, 'limit' => 4 ) ) : array();
$ysf_acc_url   = ysf_account_url();
$ysf_acc_label = is_user_logged_in() ? ysf_t( 'acc_nav' ) : ysf_t( 'acc_login_cta' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="ysf-visually-hidden ysf-skip-link" href="#ysf-content"><?php ysf_e( 'skip_to_content' ); ?></a>

<?php if ( $ysf_bar_items || $ysf_phone || $ysf_acc_url || ysf_get_option( 'ysf_show_langswitch', true ) ) : ?>
<div class="ysf-topbar">
	<div class="ysf-wrap ysf-topbar__inner">
		<?php if ( $ysf_bar_items ) : ?>
			<div class="ysf-topbar__ticker">
				<span class="ysf-topbar__badge"><?php ysf_e( 'announcements' ); ?></span>
				<div class="ysf-topbar__items" data-ysf-ticker>
					<?php foreach ( $ysf_bar_items as $ysf_index => $ysf_item ) : ?>
						<?php
						$ysf_bar_link = get_post_meta( $ysf_item->ID, '_ysf_link', true );
						$ysf_bar_text = ysf_field( $ysf_item->ID, 'title' );
						?>
						<div class="ysf-topbar__item <?php echo 0 === $ysf_index ? 'is-active' : ''; ?>">
							<span><?php echo esc_html( $ysf_bar_text ); ?></span>
							<?php if ( $ysf_bar_link ) : ?>
								<a href="<?php echo esc_url( ysf_localize_url( $ysf_bar_link ) ); ?>"><?php ysf_e( 'read_more' ); ?></a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php else : ?>
			<div class="ysf-topbar__ticker"></div>
		<?php endif; ?>

		<div class="ysf-topbar__meta">
			<?php if ( $ysf_acc_url ) : ?>
				<a class="ysf-topbar__account" href="<?php echo esc_url( $ysf_acc_url ); ?>">
					<?php echo ysf_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( $ysf_acc_label ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( $ysf_phone ) : ?>
				<a class="ysf-topbar__phone" href="tel:<?php echo esc_attr( ysf_digits( $ysf_phone ) ); ?>">
					<?php echo esc_html( $ysf_phone ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ysf_get_option( 'ysf_show_langswitch', true ) ) : ?>
				<div class="ysf-langswitch" role="group" aria-label="<?php echo esc_attr( ysf_t( 'lang_switch_label' ) ); ?>">
					<?php foreach ( ysf_languages() as $ysf_code => $ysf_meta ) : ?>
						<a
							href="<?php echo esc_url( ysf_lang_url( $ysf_code ) ); ?>"
							hreflang="<?php echo esc_attr( $ysf_code ); ?>"
							<?php echo ysf_lang() === $ysf_code ? 'aria-current="true"' : ''; ?>
						><?php echo esc_html( $ysf_meta['short'] ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endif; ?>

<header class="ysf-header" data-ysf-header>
	<div class="ysf-wrap ysf-header__inner">
		<a class="ysf-brand" href="<?php echo esc_url( ysf_localize_url( home_url( '/' ) ) ); ?>" rel="home">
			<?php if ( has_custom_logo() ) : ?>
				<?php
				$ysf_logo_id = get_theme_mod( 'custom_logo' );
				echo wp_get_attachment_image(
					$ysf_logo_id,
					'full',
					false,
					array(
						'alt'     => esc_attr( get_bloginfo( 'name' ) ),
						'loading' => 'eager',
					)
				);
				?>
			<?php else : ?>
				<span class="ysf-brand__text">
					<span class="ysf-brand__name"><?php bloginfo( 'name' ); ?></span>
					<?php $ysf_tagline = ysf_option_i18n( 'ysf_tagline', '' ); ?>
					<?php if ( $ysf_tagline ) : ?>
						<span class="ysf-brand__tagline"><?php echo esc_html( $ysf_tagline ); ?></span>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</a>

		<nav class="ysf-nav" id="ysf-nav" aria-label="<?php echo esc_attr( ysf_t( 'nav_menu' ) ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'depth'          => 2,
						'fallback_cb'    => false,
						'link_before'    => '',
					)
				);
			} else {
				echo '<ul>';
				printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( ysf_localize_url( home_url( '/' ) ) ), esc_html( ysf_t( 'nav_home' ) ) );

				if ( $ysf_menu_url ) {
					printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $ysf_menu_url ), esc_html( ysf_t( 'nav_menu' ) ) );
				}

				if ( $ysf_res_url ) {
					printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $ysf_res_url ), esc_html( ysf_t( 'nav_reservation' ) ) );
				}

				echo '</ul>';
			}
			?>

			<?php if ( $ysf_acc_url ) : ?>
				<a class="ysf-nav__account" href="<?php echo esc_url( $ysf_acc_url ); ?>">
					<?php echo ysf_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( $ysf_acc_label ); ?></span>
				</a>
			<?php endif; ?>
		</nav>

		<div class="ysf-header__actions">
			<?php if ( ysf_get_option( 'ysf_orders_enabled', true ) && $ysf_order_url ) : ?>
				<button type="button" class="ysf-cart-btn" data-ysf-cart-open hidden>
					<?php echo ysf_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="ysf-cart-btn__count" data-ysf-cart-count>0</span>
				</button>
				<a class="ysf-btn ysf-btn--sm" href="<?php echo esc_url( $ysf_order_url ); ?>">
					<?php ysf_e( 'cta_order' ); ?>
				</a>
			<?php elseif ( $ysf_res_url ) : ?>
				<a class="ysf-btn ysf-btn--sm" href="<?php echo esc_url( $ysf_res_url ); ?>">
					<?php ysf_e( 'cta_reserve' ); ?>
				</a>
			<?php endif; ?>

			<button
				type="button"
				class="ysf-burger"
				data-ysf-burger
				aria-controls="ysf-nav"
				aria-expanded="false"
				aria-label="<?php echo esc_attr( ysf_t( 'menu_toggle' ) ); ?>"
			>
				<span></span><span></span><span></span>
			</button>
		</div>
	</div>
</header>

<div class="ysf-scrim" data-ysf-scrim></div>

<main id="ysf-content">
