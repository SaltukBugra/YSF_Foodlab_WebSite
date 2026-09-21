<?php
/**
 * Template Name: İletişim Sayfası
 * Description: İletişim bilgileri, harita ve mesaj formu.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_page_id = get_the_ID();
$ysf_phone   = ysf_get_option( 'ysf_phone', '' );
$ysf_email   = ysf_get_option( 'ysf_email', '' );
$ysf_address = ysf_get_option( 'ysf_address', '' );
$ysf_map     = ysf_get_option( 'ysf_maps_embed', '' );
$ysf_maps    = ysf_get_option( 'ysf_maps_link', '' );
$ysf_hours   = ysf_get_hours();
$ysf_today   = ysf_today_key();
?>

<section class="ysf-page-hero">
	<div class="ysf-wrap">
		<?php ysf_breadcrumb(); ?>
		<h1><?php echo esc_html( ysf_field( $ysf_page_id, 'title' ) ); ?></h1>
		<?php if ( $ysf_address ) : ?>
			<p><?php echo esc_html( $ysf_address ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="ysf-section">
	<div class="ysf-wrap ysf-split">
		<div>
			<span class="ysf-eyebrow"><?php ysf_e( 'contact_title' ); ?></span>
			<h2><?php ysf_e( 'contact_form_title' ); ?></h2>

			<form class="ysf-form ysf-form--2col" data-ysf-form="contact" novalidate>
				<div class="ysf-field">
					<label for="ysf-c-name"><?php ysf_e( 'form_name' ); ?> <span class="ysf-req">*</span></label>
					<input type="text" id="ysf-c-name" name="name" required autocomplete="name">
				</div>

				<div class="ysf-field">
					<label for="ysf-c-phone"><?php ysf_e( 'form_phone' ); ?></label>
					<input type="tel" id="ysf-c-phone" name="phone" autocomplete="tel" inputmode="tel">
				</div>

				<div class="ysf-field">
					<label for="ysf-c-email"><?php ysf_e( 'form_email' ); ?></label>
					<input type="email" id="ysf-c-email" name="email" autocomplete="email">
				</div>

				<div class="ysf-field">
					<label for="ysf-c-subject"><?php ysf_e( 'form_subject' ); ?></label>
					<input type="text" id="ysf-c-subject" name="subject">
				</div>

				<div class="ysf-field ysf-field--full">
					<label for="ysf-c-message"><?php ysf_e( 'form_message' ); ?> <span class="ysf-req">*</span></label>
					<textarea id="ysf-c-message" name="message" required></textarea>
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
					<button type="submit" class="ysf-btn" data-ysf-submit><?php ysf_e( 'form_submit' ); ?></button>
				</div>
			</form>
		</div>

		<div>
			<ul class="ysf-info-list" style="margin-bottom:28px">
				<?php if ( $ysf_address ) : ?>
					<li>
						<span class="ysf-info-list__icon"><?php echo ysf_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span>
							<strong><?php ysf_e( 'contact_address' ); ?></strong>
							<?php if ( $ysf_maps ) : ?>
								<a href="<?php echo esc_url( $ysf_maps ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $ysf_address ); ?></a>
							<?php else : ?>
								<span><?php echo esc_html( $ysf_address ); ?></span>
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

				<li>
					<span class="ysf-info-list__icon"><?php echo ysf_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span>
						<strong><?php ysf_e( 'hours_title' ); ?></strong>
						<span><?php echo esc_html( $ysf_hours[ $ysf_today ] ? $ysf_hours[ $ysf_today ] : ysf_t( 'closed' ) ); ?></span>
					</span>
				</li>
			</ul>

			<?php ysf_the_social_links( 'ysf-social ysf-social--contact' ); ?>

			<?php if ( $ysf_map ) : ?>
				<iframe
					class="ysf-map"
					src="<?php echo esc_url( $ysf_map ); ?>"
					title="<?php echo esc_attr( ysf_t( 'contact_address' ) ); ?>"
					loading="lazy"
					referrerpolicy="no-referrer-when-downgrade"
					allowfullscreen
				></iframe>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ysf_has_translated_content( $ysf_page_id ) ) : ?>
		<div class="ysf-wrap ysf-prose" style="margin-top:48px">
			<?php ysf_the_translated_content( $ysf_page_id ); ?>
		</div>
	<?php endif; ?>
</section>

<?php
get_footer();
