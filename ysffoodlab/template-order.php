<?php
/**
 * Template Name: Online Sipariş Sayfası
 * Description: Sepet özeti, teslimat bilgileri ve WhatsApp/e-posta ile sipariş gönderimi.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_page_id   = get_the_ID();
$ysf_orders_on = ysf_get_option( 'ysf_orders_enabled', true );
$ysf_areas     = array_filter( array_map( 'trim', explode( ',', (string) ysf_get_option( 'ysf_delivery_areas', '' ) ) ) );
$ysf_note      = ysf_option_i18n( 'ysf_order_note', '' );
$ysf_min       = (float) ysf_get_option( 'ysf_min_order', 0 );
$ysf_free_over = (float) ysf_get_option( 'ysf_free_delivery_over', 0 );
$ysf_me        = ysf_current_user_prefill();
$ysf_acc_url   = ysf_account_url();

// Üyenin ilk kayıtlı adresi teslimat alanına hazır gelir.
$ysf_default_addr = '';

if ( ! empty( $ysf_me['addresses'] ) ) {
	$ysf_first_saved  = reset( $ysf_me['addresses'] );
	$ysf_default_addr = $ysf_first_saved['line'];
}
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

			<div class="ysf-order-layout">
				<div class="ysf-order-layout__main">
					<h2><?php ysf_e( 'order_pick_menu' ); ?></h2>
					<?php get_template_part( 'template-parts/menu-catalog', null, array( 'show_cart' => true, 'layout' => 'list' ) ); ?>

					<h2 id="ysf-teslimat" class="ysf-order-layout__form-title"><?php ysf_e( 'order_step_info' ); ?></h2>

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

					<?php if ( $ysf_me ) : ?>
						<p class="ysf-alert ysf-alert--info"><?php ysf_e( 'acc_prefilled' ); ?></p>
					<?php elseif ( $ysf_acc_url ) : ?>
						<p class="ysf-alert ysf-alert--info">
							<?php ysf_e( 'acc_lead' ); ?>
							<a href="<?php echo esc_url( $ysf_acc_url ); ?>"><?php ysf_e( 'acc_login_cta' ); ?></a>
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
							<input type="text" id="ysf-order-name" name="name" required autocomplete="name"
								value="<?php echo esc_attr( isset( $ysf_me['name'] ) ? $ysf_me['name'] : '' ); ?>">
						</div>

						<div class="ysf-field">
							<label for="ysf-order-phone"><?php ysf_e( 'form_phone' ); ?> <span class="ysf-req">*</span></label>
							<input type="tel" id="ysf-order-phone" name="phone" required autocomplete="tel" inputmode="tel"
								placeholder="05xx xxx xx xx"
								value="<?php echo esc_attr( isset( $ysf_me['phone'] ) ? $ysf_me['phone'] : '' ); ?>">
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

						<?php if ( ! empty( $ysf_me['addresses'] ) ) : ?>
							<div class="ysf-field ysf-field--full" data-ysf-when="delivery">
								<span><?php ysf_e( 'addr_saved_choose' ); ?></span>
								<div class="ysf-choice-row" data-ysf-saved-address>
									<?php $ysf_first = true; ?>
									<?php foreach ( $ysf_me['addresses'] as $ysf_type => $ysf_saved ) : ?>
										<label class="ysf-choice">
											<input type="radio" name="saved_address" value="<?php echo esc_attr( $ysf_type ); ?>"
												data-ysf-address-line="<?php echo esc_attr( $ysf_saved['line'] ); ?>"
												<?php checked( true, $ysf_first ); ?>>
											<span><?php echo esc_html( $ysf_saved['label'] ); ?></span>
										</label>
										<?php $ysf_first = false; ?>
									<?php endforeach; ?>
									<label class="ysf-choice">
										<input type="radio" name="saved_address" value="" data-ysf-address-line="">
										<span><?php ysf_e( 'addr_new' ); ?></span>
									</label>
								</div>
							</div>
						<?php endif; ?>

						<div class="ysf-field ysf-field--full" data-ysf-when="delivery">
							<label for="ysf-order-address"><?php ysf_e( 'form_address' ); ?> <span class="ysf-req">*</span></label>
							<textarea id="ysf-order-address" name="address" autocomplete="street-address"><?php echo esc_textarea( $ysf_default_addr ); ?></textarea>
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

				<aside class="ysf-sidebar ysf-order-sidebar">
					<div class="widget ysf-order-cart">
						<h2 class="widget-title"><?php ysf_e( 'order_step_items' ); ?></h2>
						<div data-ysf-order-lines></div>
						<div class="ysf-totals" data-ysf-order-totals></div>
						<a class="ysf-btn ysf-btn--block" href="#ysf-teslimat" data-ysf-to-delivery>
							<?php ysf_e( 'order_to_delivery' ); ?>
						</a>
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

				</aside>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
