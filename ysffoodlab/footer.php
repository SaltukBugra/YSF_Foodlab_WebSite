<?php
/**
 * Alt bilgi, sepet paneli ve yüzen aksiyon butonları.
 *
 * @package ysffoodlab
 */

$ysf_phone     = ysf_get_option( 'ysf_phone', '' );
$ysf_email     = ysf_get_option( 'ysf_email', '' );
$ysf_address   = ysf_get_option( 'ysf_address', '' );
$ysf_maps      = ysf_get_option( 'ysf_maps_link', '' );
$ysf_order_url = ysf_localize_url( ysf_get_page_url_by_template( 'template-order.php' ) );
$ysf_res_url   = ysf_localize_url( ysf_get_page_url_by_template( 'template-reservation.php' ) );
$ysf_menu_url  = ysf_localize_url( ysf_get_page_url_by_template( 'template-menu.php' ) );
$ysf_wa        = ysf_whatsapp_url();
$ysf_hours     = ysf_get_hours();
$ysf_today     = ysf_today_key();
$ysf_orders_on = ysf_get_option( 'ysf_orders_enabled', true ) && $ysf_order_url;
?>
</main>

<footer class="ysf-footer">
	<div class="ysf-wrap">
		<div class="ysf-footer__top">
			<div>
				<h2 class="ysf-footer__title"><?php bloginfo( 'name' ); ?></h2>
				<p><?php echo esc_html( ysf_option_i18n( 'ysf_about_text', get_bloginfo( 'description' ) ) ); ?></p>

				<?php ysf_the_social_links(); ?>
			</div>

			<div>
				<h2 class="ysf-footer__title"><?php ysf_e( 'footer_quick' ); ?></h2>
				<?php
				if ( has_nav_menu( 'secondary' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'secondary',
							'container'      => false,
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
				} else {
					echo '<ul>';
					if ( $ysf_menu_url ) {
						printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $ysf_menu_url ), esc_html( ysf_t( 'nav_menu' ) ) );
					}
					if ( $ysf_orders_on ) {
						printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $ysf_order_url ), esc_html( ysf_t( 'nav_order' ) ) );
					}
					if ( $ysf_res_url ) {
						printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $ysf_res_url ), esc_html( ysf_t( 'nav_reservation' ) ) );
					}
					echo '</ul>';
				}
				?>
			</div>

			<div>
				<h2 class="ysf-footer__title"><?php ysf_e( 'hours_title' ); ?></h2>
				<table class="ysf-hours">
					<tbody>
						<?php foreach ( ysf_week_days() as $ysf_day => $ysf_names ) : ?>
							<tr class="<?php echo $ysf_day === $ysf_today ? 'is-today' : ''; ?>">
								<td><?php echo esc_html( 'en' === ysf_lang() ? $ysf_names[1] : $ysf_names[0] ); ?></td>
								<td><?php echo esc_html( $ysf_hours[ $ysf_day ] ? $ysf_hours[ $ysf_day ] : ysf_t( 'closed' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div>
				<h2 class="ysf-footer__title"><?php ysf_e( 'footer_contact' ); ?></h2>
				<ul class="ysf-info-list">
					<?php if ( $ysf_address ) : ?>
						<li>
							<span class="ysf-info-list__icon"><?php echo ysf_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span>
								<strong><?php ysf_e( 'contact_address' ); ?></strong>
								<?php if ( $ysf_maps ) : ?>
									<a href="<?php echo esc_url( $ysf_maps ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $ysf_address ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $ysf_address ); ?>
								<?php endif; ?>
							</span>
						</li>
					<?php endif; ?>

					<?php if ( $ysf_phone ) : ?>
						<li>
							<span class="ysf-info-list__icon"><?php echo ysf_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span>
								<strong><?php ysf_e( 'contact_phone' ); ?></strong>
								<a href="tel:<?php echo esc_attr( ysf_digits( $ysf_phone ) ); ?>"><?php echo esc_html( $ysf_phone ); ?></a>
							</span>
						</li>
					<?php endif; ?>

					<?php if ( $ysf_email ) : ?>
						<li>
							<span class="ysf-info-list__icon"><?php echo ysf_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span>
								<strong><?php ysf_e( 'contact_mail' ); ?></strong>
								<a href="mailto:<?php echo esc_attr( $ysf_email ); ?>"><?php echo esc_html( $ysf_email ); ?></a>
							</span>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</div>

		<div class="ysf-footer__bottom">
			<span>
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php ysf_e( 'footer_rights' ); ?>
			</span>
			<span>
				<?php if ( ysf_get_option( 'ysf_kvkk_url', '' ) ) : ?>
					<a href="<?php echo esc_url( ysf_get_option( 'ysf_kvkk_url', '' ) ); ?>">KVKK</a> ·
				<?php endif; ?>
				<a href="<?php echo esc_url( ysf_localize_url( home_url( '/' ) ) ); ?>"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></a>
			</span>
		</div>
	</div>
</footer>

<?php if ( $ysf_orders_on ) : ?>
<aside class="ysf-cart" data-ysf-cart aria-label="<?php echo esc_attr( ysf_t( 'cart_title' ) ); ?>" aria-hidden="true">
	<div class="ysf-cart__head">
		<h2><?php ysf_e( 'cart_title' ); ?></h2>
		<button type="button" class="ysf-cart__close" data-ysf-cart-close aria-label="<?php echo esc_attr( ysf_t( 'cart_close' ) ); ?>">&times;</button>
	</div>
	<div class="ysf-cart__body" data-ysf-cart-body></div>
	<div class="ysf-cart__foot">
		<div class="ysf-totals" data-ysf-cart-totals></div>
		<a class="ysf-btn ysf-btn--block" href="<?php echo esc_url( $ysf_order_url ); ?>" data-ysf-cart-checkout>
			<?php ysf_e( 'checkout' ); ?>
		</a>
		<p style="margin:10px 0 0;text-align:center">
			<button type="button" class="ysf-btn ysf-btn--ghost ysf-btn--sm" data-ysf-cart-clear><?php ysf_e( 'cart_clear' ); ?></button>
		</p>
	</div>
</aside>
<?php endif; ?>

<div class="ysf-float">
	<?php
	foreach ( ysf_social_links() as $ysf_key => $ysf_link ) {
		if ( ! in_array( $ysf_key, array( 'instagram', 'facebook' ), true ) ) {
			continue;
		}
		?>
		<a
			class="ysf-float--<?php echo esc_attr( $ysf_key ); ?>"
			href="<?php echo esc_url( $ysf_link['url'] ); ?>"
			target="_blank"
			rel="noopener noreferrer"
			aria-label="<?php echo esc_attr( $ysf_link['label'] ); ?>"
		><?php echo $ysf_link['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<?php
	}
	?>
	<?php if ( $ysf_wa ) : ?>
		<a href="<?php echo esc_url( $ysf_wa ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ysf_t( 'cta_whatsapp' ) ); ?>">
			<?php echo ysf_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	<?php endif; ?>
	<?php if ( $ysf_phone ) : ?>
		<a class="ysf-float--call" href="tel:<?php echo esc_attr( ysf_digits( $ysf_phone ) ); ?>" aria-label="<?php echo esc_attr( ysf_t( 'cta_call' ) ); ?>">
			<?php echo ysf_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	<?php endif; ?>
</div>

<nav class="ysf-mobilebar" aria-label="<?php echo esc_attr( ysf_t( 'nav_menu' ) ); ?>">
	<?php if ( $ysf_menu_url ) : ?>
		<a href="<?php echo esc_url( $ysf_menu_url ); ?>">
			<span aria-hidden="true">🍽</span><?php ysf_e( 'nav_menu' ); ?>
		</a>
	<?php endif; ?>
	<?php if ( $ysf_orders_on ) : ?>
		<a href="<?php echo esc_url( $ysf_order_url ); ?>">
			<span aria-hidden="true">🛍</span><?php ysf_e( 'nav_order' ); ?>
		</a>
	<?php endif; ?>
	<?php if ( $ysf_res_url ) : ?>
		<a href="<?php echo esc_url( $ysf_res_url ); ?>">
			<span aria-hidden="true">📅</span><?php ysf_e( 'nav_reservation' ); ?>
		</a>
	<?php endif; ?>
	<?php if ( $ysf_phone ) : ?>
		<a href="tel:<?php echo esc_attr( ysf_digits( $ysf_phone ) ); ?>">
			<span aria-hidden="true">📞</span><?php ysf_e( 'cta_call' ); ?>
		</a>
	<?php endif; ?>
</nav>

<?php wp_footer(); ?>
</body>
</html>
