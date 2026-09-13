<?php
/**
 * Template Name: Hesabım
 * Description: Üye girişi, kayıt, profil bilgileri ve adres defteri.
 *
 * @package ysffoodlab
 */

get_header();

$ysf_page_id = get_the_ID();
$ysf_logged  = is_user_logged_in();
$ysf_user    = wp_get_current_user();
$ysf_open    = ysf_get_option( 'ysf_acc_enabled', true );
?>

<section class="ysf-page-hero">
	<div class="ysf-wrap">
		<?php ysf_breadcrumb(); ?>
		<h1><?php echo esc_html( ysf_field( $ysf_page_id, 'title' ) ); ?></h1>
		<p><?php ysf_e( 'acc_lead' ); ?></p>
	</div>
</section>

<section class="ysf-section">
	<div class="ysf-wrap">

		<?php if ( ! $ysf_logged ) : ?>

			<div class="ysf-auth">
				<div class="ysf-auth__card">
					<h2 class="ysf-auth__title"><?php ysf_e( 'acc_login_title' ); ?></h2>

					<form class="ysf-form" data-ysf-form="login" novalidate>
						<div class="ysf-field ysf-field--full">
							<label for="ysf-login-user"><?php ysf_e( 'acc_login_or_phone' ); ?> <span class="ysf-req">*</span></label>
							<input type="text" id="ysf-login-user" name="login" required autocomplete="username">
						</div>

						<div class="ysf-field ysf-field--full">
							<label for="ysf-login-pass"><?php ysf_e( 'acc_password' ); ?> <span class="ysf-req">*</span></label>
							<input type="password" id="ysf-login-pass" name="password" required autocomplete="current-password">
						</div>

						<label class="ysf-check">
							<input type="checkbox" name="remember" value="1" checked>
							<span><?php ysf_e( 'acc_remember' ); ?></span>
						</label>

						<label class="ysf-hp" aria-hidden="true">
							<?php esc_html_e( 'Bu alanı boş bırakın', 'ysffoodlab' ); ?>
							<input type="text" name="ysf_hp" tabindex="-1" autocomplete="off">
						</label>

						<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

						<div class="ysf-field ysf-field--full">
							<button type="submit" class="ysf-btn ysf-btn--block" data-ysf-submit>
								<?php ysf_e( 'acc_login_btn' ); ?>
							</button>
						</div>
					</form>

					<details class="ysf-details">
						<summary><?php ysf_e( 'acc_forgot' ); ?></summary>

						<form class="ysf-form" data-ysf-form="lostpass" novalidate>
							<p class="ysf-muted"><?php ysf_e( 'acc_forgot_hint' ); ?></p>

							<div class="ysf-field ysf-field--full">
								<label for="ysf-lost-user"><?php ysf_e( 'form_email' ); ?> <span class="ysf-req">*</span></label>
								<input type="email" id="ysf-lost-user" name="login" required autocomplete="email">
							</div>

							<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

							<div class="ysf-field ysf-field--full">
								<button type="submit" class="ysf-btn ysf-btn--ghost ysf-btn--block" data-ysf-submit>
									<?php ysf_e( 'acc_forgot_send' ); ?>
								</button>
							</div>
						</form>
					</details>
				</div>

				<div class="ysf-auth__card">
					<h2 class="ysf-auth__title"><?php ysf_e( 'acc_register_title' ); ?></h2>

					<?php if ( ! $ysf_open ) : ?>
						<p class="ysf-muted"><?php ysf_e( 'acc_closed' ); ?></p>
					<?php else : ?>

						<form class="ysf-form ysf-form--2col" data-ysf-form="register" novalidate>
							<div class="ysf-field ysf-field--full">
								<label for="ysf-reg-name"><?php ysf_e( 'form_name' ); ?> <span class="ysf-req">*</span></label>
								<input type="text" id="ysf-reg-name" name="name" required autocomplete="name">
							</div>

							<div class="ysf-field">
								<label for="ysf-reg-email"><?php ysf_e( 'form_email' ); ?> <span class="ysf-req">*</span></label>
								<input type="email" id="ysf-reg-email" name="email" required autocomplete="email">
							</div>

							<div class="ysf-field">
								<label for="ysf-reg-phone"><?php ysf_e( 'form_phone' ); ?> <span class="ysf-req">*</span></label>
								<input type="tel" id="ysf-reg-phone" name="phone" required autocomplete="tel"
									inputmode="tel" placeholder="05xx xxx xx xx">
							</div>

							<div class="ysf-field">
								<label for="ysf-reg-pass"><?php ysf_e( 'acc_password' ); ?> <span class="ysf-req">*</span></label>
								<input type="password" id="ysf-reg-pass" name="password" required minlength="8" autocomplete="new-password">
								<small><?php ysf_e( 'acc_password_hint' ); ?></small>
							</div>

							<div class="ysf-field">
								<label for="ysf-reg-pass2"><?php ysf_e( 'acc_password_again' ); ?> <span class="ysf-req">*</span></label>
								<input type="password" id="ysf-reg-pass2" name="password2" required minlength="8" autocomplete="new-password">
							</div>

							<div class="ysf-field ysf-field--full">
								<details class="ysf-details">
									<summary><?php ysf_e( 'acc_addr_home' ); ?> — <?php ysf_e( 'acc_addr_optional' ); ?></summary>
									<?php get_template_part( 'template-parts/address-fields', null, array( 'type' => 'home' ) ); ?>
								</details>
							</div>

							<label class="ysf-check ysf-field--full">
								<input type="checkbox" name="consent" required>
								<span>
									<?php ysf_e( 'form_consent' ); ?>
									<?php if ( ysf_get_option( 'ysf_kvkk_url', '' ) ) : ?>
										<a href="<?php echo esc_url( ysf_get_option( 'ysf_kvkk_url', '' ) ); ?>" target="_blank" rel="noopener">KVKK</a>
									<?php endif; ?>
								</span>
							</label>

							<label class="ysf-hp" aria-hidden="true">
								<?php esc_html_e( 'Bu alanı boş bırakın', 'ysffoodlab' ); ?>
								<input type="text" name="ysf_hp" tabindex="-1" autocomplete="off">
							</label>

							<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

							<div class="ysf-field ysf-field--full">
								<button type="submit" class="ysf-btn ysf-btn--block" data-ysf-submit>
									<?php ysf_e( 'acc_register_btn' ); ?>
								</button>
							</div>
						</form>

					<?php endif; ?>
				</div>
			</div>

		<?php else : ?>

			<div class="ysf-account">
				<header class="ysf-account__head">
					<div>
						<p class="ysf-eyebrow"><?php ysf_e( 'acc_welcome' ); ?></p>
						<h2><?php echo esc_html( $ysf_user->display_name ); ?></h2>
					</div>
					<a class="ysf-btn ysf-btn--ghost ysf-btn--sm" href="<?php echo esc_url( wp_logout_url( ysf_account_url() ) ); ?>">
						<?php ysf_e( 'acc_logout' ); ?>
					</a>
				</header>

				<div class="ysf-account__grid">
					<div class="ysf-card-panel">
						<h3 class="ysf-card-panel__title"><?php ysf_e( 'acc_profile_title' ); ?></h3>

						<form class="ysf-form ysf-form--2col" data-ysf-form="profile" novalidate>
							<div class="ysf-field ysf-field--full">
								<label for="ysf-prof-name"><?php ysf_e( 'form_name' ); ?> <span class="ysf-req">*</span></label>
								<input type="text" id="ysf-prof-name" name="name" required autocomplete="name"
									value="<?php echo esc_attr( $ysf_user->display_name ); ?>">
							</div>

							<div class="ysf-field">
								<label for="ysf-prof-email"><?php ysf_e( 'form_email' ); ?> <span class="ysf-req">*</span></label>
								<input type="email" id="ysf-prof-email" name="email" required autocomplete="email"
									value="<?php echo esc_attr( $ysf_user->user_email ); ?>">
							</div>

							<div class="ysf-field">
								<label for="ysf-prof-phone"><?php ysf_e( 'form_phone' ); ?> <span class="ysf-req">*</span></label>
								<input type="tel" id="ysf-prof-phone" name="phone" required autocomplete="tel" inputmode="tel"
									placeholder="05xx xxx xx xx"
									value="<?php echo esc_attr( ysf_phone_display( ysf_user_phone( $ysf_user->ID ) ) ); ?>">
							</div>

							<div class="ysf-field ysf-field--full">
								<details class="ysf-details">
									<summary><?php ysf_e( 'acc_password_new' ); ?></summary>

									<p class="ysf-muted"><?php ysf_e( 'acc_password_keep' ); ?></p>

									<div class="ysf-addr-grid">
										<div class="ysf-field">
											<label for="ysf-prof-pass-cur"><?php ysf_e( 'acc_password_cur' ); ?></label>
											<input type="password" id="ysf-prof-pass-cur" name="password_current" autocomplete="current-password">
										</div>

										<div class="ysf-field">
											<label for="ysf-prof-pass-new"><?php ysf_e( 'acc_password_new' ); ?></label>
											<input type="password" id="ysf-prof-pass-new" name="password_new" autocomplete="new-password">
											<small><?php ysf_e( 'acc_password_hint' ); ?></small>
										</div>
									</div>
								</details>
							</div>

							<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

							<div class="ysf-field ysf-field--full">
								<button type="submit" class="ysf-btn" data-ysf-submit><?php ysf_e( 'acc_save' ); ?></button>
							</div>
						</form>
					</div>

					<div class="ysf-card-panel">
						<h3 class="ysf-card-panel__title"><?php ysf_e( 'acc_addr_title' ); ?></h3>
						<p class="ysf-muted"><?php ysf_e( 'acc_addr_optional' ); ?></p>

						<?php foreach ( ysf_address_types() as $ysf_type => $ysf_label ) : ?>
							<?php
							$ysf_addr = ysf_get_user_address( $ysf_user->ID, $ysf_type );
							$ysf_line = ysf_address_one_line( $ysf_addr );
							?>
							<div class="ysf-addr-card" data-ysf-addr-card="<?php echo esc_attr( $ysf_type ); ?>">
								<div class="ysf-addr-card__head">
									<h4><?php echo esc_html( $ysf_label ); ?></h4>
									<button type="button" class="ysf-link-btn" data-ysf-addr-toggle>
										<?php echo esc_html( $ysf_line ? ysf_t( 'acc_addr_edit' ) : ysf_t( 'acc_addr_add' ) ); ?>
									</button>
								</div>

								<p class="ysf-addr-card__line" data-ysf-addr-line>
									<?php echo esc_html( $ysf_line ? $ysf_line : ysf_t( 'acc_addr_empty' ) ); ?>
								</p>

								<form class="ysf-form ysf-addr-card__form" data-ysf-form="address" data-ysf-addr-type="<?php echo esc_attr( $ysf_type ); ?>" hidden novalidate>
									<input type="hidden" name="type" value="<?php echo esc_attr( $ysf_type ); ?>">

									<?php
									get_template_part(
										'template-parts/address-fields',
										null,
										array(
											'type'    => $ysf_type,
											'address' => $ysf_addr,
										)
									);
									?>

									<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

									<div class="ysf-addr-card__actions">
										<button type="submit" class="ysf-btn ysf-btn--sm" data-ysf-submit><?php ysf_e( 'acc_save' ); ?></button>
										<button type="button" class="ysf-btn ysf-btn--ghost ysf-btn--sm" data-ysf-addr-cancel><?php ysf_e( 'acc_cancel' ); ?></button>
										<?php if ( $ysf_line ) : ?>
											<button type="button" class="ysf-link-btn ysf-link-btn--danger" data-ysf-addr-delete><?php ysf_e( 'acc_addr_delete' ); ?></button>
										<?php endif; ?>
									</div>
								</form>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<?php
				$ysf_orders = ysf_user_records( 'ysf_order', 5 );
				$ysf_res    = ysf_user_records( 'ysf_reservation', 5 );
				?>

				<?php if ( $ysf_orders || $ysf_res ) : ?>
					<div class="ysf-account__grid">
						<div class="ysf-card-panel">
							<h3 class="ysf-card-panel__title"><?php ysf_e( 'acc_orders_title' ); ?></h3>

							<?php if ( $ysf_orders ) : ?>
								<ul class="ysf-record-list">
									<?php foreach ( $ysf_orders as $ysf_record ) : ?>
										<li>
											<span class="ysf-record-list__date"><?php echo esc_html( get_the_date( '', $ysf_record ) ); ?></span>
											<span><?php echo esc_html( get_the_title( $ysf_record ) ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="ysf-muted"><?php ysf_e( 'acc_history_empty' ); ?></p>
							<?php endif; ?>
						</div>

						<div class="ysf-card-panel">
							<h3 class="ysf-card-panel__title"><?php ysf_e( 'acc_res_records' ); ?></h3>

							<?php if ( $ysf_res ) : ?>
								<ul class="ysf-record-list">
									<?php foreach ( $ysf_res as $ysf_record ) : ?>
										<li>
											<span class="ysf-record-list__date"><?php echo esc_html( get_the_date( '', $ysf_record ) ); ?></span>
											<span><?php echo esc_html( get_the_title( $ysf_record ) ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="ysf-muted"><?php ysf_e( 'acc_history_empty' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
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

<?php
get_footer();
