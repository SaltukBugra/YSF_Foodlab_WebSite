<?php
/**
 * Template Name: Rezervasyon Sayfası
 * Description: Özel günler için masa rezervasyon formu.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_page_id  = get_the_ID();
$ysf_enabled  = ysf_get_option( 'ysf_res_enabled', true );
$ysf_max      = (int) ysf_get_option( 'ysf_res_max_guests', 20 );
$ysf_slots    = ysf_reservation_slots();
$ysf_note     = ysf_option_i18n( 'ysf_res_note', '' );
$ysf_min_date = current_time( 'Y-m-d' );
$ysf_hours    = ysf_get_hours();
$ysf_today    = ysf_today_key();
$ysf_me       = ysf_current_user_prefill();
$ysf_acc_url  = ysf_account_url();
?>

<section class="ysf-page-hero">
	<div class="ysf-wrap">
		<?php ysf_breadcrumb(); ?>
		<h1><?php echo esc_html( ysf_field( $ysf_page_id, 'title' ) ); ?></h1>
		<p><?php echo esc_html( $ysf_note ? $ysf_note : ysf_t( 'res_lead' ) ); ?></p>
	</div>
</section>

<section class="ysf-section">
	<div class="ysf-wrap">
		<?php if ( ! $ysf_enabled ) : ?>
			<div class="ysf-empty">
				<p><?php esc_html_e( 'Online rezervasyon şu anda kapalı. Lütfen bizi telefonla arayın.', 'ysffoodlab' ); ?></p>
				<?php if ( ysf_get_option( 'ysf_phone', '' ) ) : ?>
					<a class="ysf-btn" href="tel:<?php echo esc_attr( ysf_digits( ysf_get_option( 'ysf_phone', '' ) ) ); ?>">
						<?php echo esc_html( ysf_get_option( 'ysf_phone', '' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php else : ?>

			<div class="ysf-content-layout has-sidebar">
				<div>
					<?php if ( $ysf_me ) : ?>
						<p class="ysf-alert ysf-alert--info"><?php ysf_e( 'acc_prefilled' ); ?></p>
					<?php elseif ( $ysf_acc_url ) : ?>
						<p class="ysf-alert ysf-alert--info">
							<?php ysf_e( 'acc_lead' ); ?>
							<a href="<?php echo esc_url( $ysf_acc_url ); ?>"><?php ysf_e( 'acc_login_cta' ); ?></a>
						</p>
					<?php endif; ?>

					<form class="ysf-form ysf-form--2col" data-ysf-form="reservation" novalidate>
						<div class="ysf-field">
							<label for="ysf-res-name"><?php ysf_e( 'form_name' ); ?> <span class="ysf-req">*</span></label>
							<input type="text" id="ysf-res-name" name="name" required autocomplete="name"
								value="<?php echo esc_attr( isset( $ysf_me['name'] ) ? $ysf_me['name'] : '' ); ?>">
						</div>

						<div class="ysf-field">
							<label for="ysf-res-phone"><?php ysf_e( 'form_phone' ); ?> <span class="ysf-req">*</span></label>
							<input type="tel" id="ysf-res-phone" name="phone" required autocomplete="tel" inputmode="tel"
								placeholder="05xx xxx xx xx"
								value="<?php echo esc_attr( isset( $ysf_me['phone'] ) ? $ysf_me['phone'] : '' ); ?>">
						</div>

						<div class="ysf-field">
							<label for="ysf-res-email"><?php ysf_e( 'form_email' ); ?></label>
							<input type="email" id="ysf-res-email" name="email" autocomplete="email"
								value="<?php echo esc_attr( isset( $ysf_me['email'] ) ? $ysf_me['email'] : '' ); ?>">
							<small><?php esc_html_e( 'Girerseniz onay bilgisini e-posta ile de gönderiyoruz.', 'ysffoodlab' ); ?></small>
						</div>

						<div class="ysf-field">
							<label for="ysf-res-guests"><?php ysf_e( 'form_guests' ); ?> <span class="ysf-req">*</span></label>
							<select id="ysf-res-guests" name="guests" required>
								<?php for ( $ysf_i = 1; $ysf_i <= max( 1, $ysf_max ); $ysf_i++ ) : ?>
									<option value="<?php echo esc_attr( $ysf_i ); ?>" <?php selected( 2, $ysf_i ); ?>>
										<?php echo esc_html( $ysf_i . ' ' . ysf_t( 'per_person' ) ); ?>
									</option>
								<?php endfor; ?>
							</select>
						</div>

						<div class="ysf-field">
							<label for="ysf-res-date"><?php ysf_e( 'form_date' ); ?> <span class="ysf-req">*</span></label>
							<input type="date" id="ysf-res-date" name="date" required min="<?php echo esc_attr( $ysf_min_date ); ?>">
						</div>

						<div class="ysf-field">
							<label for="ysf-res-time"><?php ysf_e( 'form_time' ); ?> <span class="ysf-req">*</span></label>
							<select id="ysf-res-time" name="time" required>
								<?php foreach ( $ysf_slots as $ysf_slot ) : ?>
									<option value="<?php echo esc_attr( $ysf_slot ); ?>" <?php selected( '19:30', $ysf_slot ); ?>>
										<?php echo esc_html( $ysf_slot ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="ysf-field ysf-field--full">
							<label for="ysf-res-occasion"><?php ysf_e( 'res_occasion' ); ?></label>
							<select id="ysf-res-occasion" name="occasion">
								<?php foreach ( ysf_occasions() as $ysf_key => $ysf_label ) : ?>
									<option value="<?php echo esc_attr( $ysf_key ); ?>"><?php echo esc_html( $ysf_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="ysf-field ysf-field--full">
							<span><?php ysf_e( 'res_extras' ); ?></span>
							<div class="ysf-choice-row">
								<?php foreach ( ysf_reservation_extras() as $ysf_key => $ysf_label ) : ?>
									<label class="ysf-choice">
										<input type="checkbox" name="extras[]" value="<?php echo esc_attr( $ysf_key ); ?>">
										<span><?php echo esc_html( $ysf_label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="ysf-field ysf-field--full">
							<label for="ysf-res-note"><?php ysf_e( 'form_note' ); ?></label>
							<textarea id="ysf-res-note" name="note" placeholder="<?php echo esc_attr( ysf_t( 'form_note_ph' ) ); ?>"></textarea>
						</div>

						<div class="ysf-field ysf-field--full">
							<label>
								<input type="checkbox" name="consent" required>
								<?php ysf_e( 'form_consent' ); ?>
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
								<?php ysf_e( 'cta_reserve' ); ?>
							</button>
						</div>
					</form>
				</div>

				<aside class="ysf-sidebar">
					<div class="widget">
						<h2 class="widget-title"><?php ysf_e( 'hours_title' ); ?></h2>
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

					<?php if ( ysf_get_option( 'ysf_phone', '' ) ) : ?>
						<div class="widget">
							<h2 class="widget-title"><?php esc_html_e( 'Büyük gruplar', 'ysffoodlab' ); ?></h2>
							<p>
								<?php
								printf(
									/* translators: %d: kişi sayısı. */
									esc_html__( '%d kişiden kalabalık organizasyonlar için lütfen bizi arayın, size özel bir plan hazırlayalım.', 'ysffoodlab' ),
									(int) $ysf_max
								);
								?>
							</p>
							<a class="ysf-btn ysf-btn--block" href="tel:<?php echo esc_attr( ysf_digits( ysf_get_option( 'ysf_phone', '' ) ) ); ?>">
								<?php echo esc_html( ysf_get_option( 'ysf_phone', '' ) ); ?>
							</a>
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

		<?php if ( ysf_has_translated_content( $ysf_page_id ) ) : ?>
			<div class="ysf-prose" style="margin-top:48px">
				<?php ysf_the_translated_content( $ysf_page_id ); ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
