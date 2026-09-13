<?php
/**
 * Template Name: Online Sipariş Sayfası
 * Description: Sepet özeti, teslimat bilgileri ve WhatsApp/e-posta ile sipariş gönderimi.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_page_id   = get_the_ID();
$ysf_menu_url  = ysf_localize_url( ysf_get_page_url_by_template( 'template-menu.php' ) );
$ysf_orders_on = ysf_get_option( 'ysf_orders_enabled', true );
$ysf_areas     = array_filter( array_map( 'trim', explode( ',', (string) ysf_get_option( 'ysf_delivery_areas', '' ) ) ) );
$ysf_note      = ysf_option_i18n( 'ysf_order_note', '' );
$ysf_min       = (float) ysf_get_option( 'ysf_min_order', 0 );
$ysf_free_over = (float) ysf_get_option( 'ysf_free_delivery_over', 0 );
?>

<section class="ysf-page-hero">
	<div class="ysf-wrap">
		<?php ysf_breadcrumb(); ?>
		<h1><?php echo esc_html( ysf_field( $ysf_page_id, 'title' ) ); ?></h1>
		<?php if ( $ysf_note ) : ?>
			<p><?php echo esc_html( $ysf_note ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="ysf-section">
	<div class="ysf-wrap">
		<?php if ( ! $ysf_orders_on ) : ?>
			<div class="ysf-empty">
				<p><?php esc_html_e( 'Online sipariş şu anda kapalı. Lütfen bizi telefonla arayın.', 'ysffoodlab' ); ?></p>
				<?php if ( ysf_get_option( 'ysf_phone', '' ) ) : ?>
					<a class="ysf-btn" href="tel:<?php echo esc_attr( ysf_digits( ysf_get_option( 'ysf_phone', '' ) ) ); ?>">
						<?php echo esc_html( ysf_get_option( 'ysf_phone', '' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php else : ?>

			<div class="ysf-content-layout has-sidebar">
				<div>
					<h2><?php ysf_e( 'order_step_info' ); ?></h2>

					<?php if ( $ysf_min > 0 || $ysf_free_over > 0 ) : ?>
						<p class="ysf-alert ysf-alert--info">
							<?php if ( $ysf_min > 0 ) : ?>
								<?php echo esc_html( sprintf( ysf_t( 'min_order_warning' ), ysf_price( $ysf_min ) ) ); ?>
							<?php endif; ?>
							<?php if ( $ysf_free_over > 0 ) : ?>
								<?php echo esc_html( sprintf( ysf_t( 'free_delivery_hint' ), ysf_price( $ysf_free_over ) ) ); ?>
							<?php endif; ?>
						</p>
					<?php endif; ?>

					<form class="ysf-form ysf-form--2col" data-ysf-form="order" novalidate>
						<div class="ysf-field ysf-field--full">
							<span><?php ysf_e( 'order_type' ); ?> <span class="ysf-req">*</span></span>
							<div class="ysf-choice-row">
								<?php foreach ( ysf_order_types() as $ysf_key => $ysf_label ) : ?>
									<label class="ysf-choice">
										<input type="radio" name="type" value="<?php echo esc_attr( $ysf_key ); ?>" <?php checked( 'delivery', $ysf_key ); ?>>
										<span><?php echo esc_html( $ysf_label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="ysf-field">
							<label for="ysf-order-name"><?php ysf_e( 'form_name' ); ?> <span class="ysf-req">*</span></label>
							<input type="text" id="ysf-order-name" name="name" required autocomplete="name">
						</div>

						<div class="ysf-field">
							<label for="ysf-order-phone"><?php ysf_e( 'form_phone' ); ?> <span class="ysf-req">*</span></label>
							<input type="tel" id="ysf-order-phone" name="phone" required autocomplete="tel" inputmode="tel" placeholder="05xx xxx xx xx">
						</div>

						<?php if ( $ysf_areas ) : ?>
							<div class="ysf-field" data-ysf-when="delivery">
								<label for="ysf-order-area"><?php esc_html_e( 'Bölge', 'ysffoodlab' ); ?></label>
								<select id="ysf-order-area" name="area">
									<option value=""><?php esc_html_e( 'Seçiniz', 'ysffoodlab' ); ?></option>
									<?php foreach ( $ysf_areas as $ysf_area ) : ?>
										<option value="<?php echo esc_attr( $ysf_area ); ?>"><?php echo esc_html( $ysf_area ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>

						<div class="ysf-field">
							<label for="ysf-order-time"><?php esc_html_e( 'İstenen saat', 'ysffoodlab' ); ?></label>
							<input type="time" id="ysf-order-time" name="time">
							<small><?php esc_html_e( 'Boş bırakırsanız en kısa sürede hazırlarız.', 'ysffoodlab' ); ?></small>
						</div>

						<div class="ysf-field ysf-field--full" data-ysf-when="delivery">
							<label for="ysf-order-address"><?php ysf_e( 'form_address' ); ?> <span class="ysf-req">*</span></label>
							<textarea id="ysf-order-address" name="address" autocomplete="street-address"></textarea>
						</div>

						<div class="ysf-field" data-ysf-when="table" hidden>
							<label for="ysf-order-table"><?php ysf_e( 'form_table_no' ); ?></label>
							<input type="text" id="ysf-order-table" name="table">
						</div>

						<div class="ysf-field ysf-field--full">
							<label for="ysf-order-note"><?php ysf_e( 'form_note' ); ?></label>
							<textarea id="ysf-order-note" name="note" placeholder="<?php echo esc_attr( ysf_t( 'form_note_ph' ) ); ?>"></textarea>
						</div>

						<div class="ysf-field ysf-field--full">
							<label>
								<input type="checkbox" name="consent" required>
								<?php ysf_e( 'form_consent' ); ?>
								<?php if ( ysf_get_option( 'ysf_kvkk_url', '' ) ) : ?>
									<a href="<?php echo esc_url( ysf_get_option( 'ysf_kvkk_url', '' ) ); ?>" target="_blank" rel="noopener">KVKK</a>
								<?php endif; ?>
							</label>
						</div>

						<label class="ysf-hp" aria-hidden="true">
							<?php esc_html_e( 'Bu alanı boş bırakın', 'ysffoodlab' ); ?>
							<input type="text" name="ysf_hp" tabindex="-1" autocomplete="off">
						</label>

						<div class="ysf-field ysf-field--full">
							<div class="ysf-alert" data-ysf-result hidden></div>
						</div>

						<div class="ysf-field ysf-field--full">
							<button type="submit" class="ysf-btn ysf-btn--block" data-ysf-submit>
								<?php ysf_e( 'checkout' ); ?>
							</button>
						</div>
					</form>
				</div>

				<aside class="ysf-sidebar">
					<div class="widget" style="border:1px solid var(--ysf-line);border-radius:var(--ysf-radius-lg);padding:22px;background:#fff">
						<h2 class="widget-title"><?php ysf_e( 'order_step_items' ); ?></h2>
						<div data-ysf-order-lines></div>
						<div class="ysf-totals" data-ysf-order-totals></div>

						<?php if ( $ysf_menu_url ) : ?>
							<a class="ysf-btn ysf-btn--ghost ysf-btn--sm ysf-btn--block" href="<?php echo esc_url( $ysf_menu_url ); ?>">
								<?php ysf_e( 'cta_menu' ); ?>
							</a>
						<?php endif; ?>
					</div>

					<?php
					$ysf_platforms = array(
						'Yemeksepeti'    => ysf_get_option( 'ysf_yemeksepeti', '' ),
						'Getir Yemek'    => ysf_get_option( 'ysf_getir', '' ),
						'Trendyol Yemek' => ysf_get_option( 'ysf_trendyol', '' ),
					);
					$ysf_platforms = array_filter( $ysf_platforms );
					?>

					<?php if ( $ysf_platforms ) : ?>
						<div class="widget">
							<h2 class="widget-title"><?php esc_html_e( 'Diğer sipariş kanalları', 'ysffoodlab' ); ?></h2>
							<ul>
								<?php foreach ( $ysf_platforms as $ysf_label => $ysf_url ) : ?>
									<li><a href="<?php echo esc_url( $ysf_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $ysf_label ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php if ( ysf_whatsapp_url() ) : ?>
						<div class="widget">
							<a class="ysf-btn ysf-btn--wa ysf-btn--block" href="<?php echo esc_url( ysf_whatsapp_url() ); ?>" target="_blank" rel="noopener noreferrer">
								<?php ysf_e( 'cta_whatsapp' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</aside>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
