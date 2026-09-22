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
		<p><?php echo ( $ysf_logged && ysf_can_manage_menu() ) ? esc_html( ysf_t( 'kit_lead' ) ) : esc_html( ysf_t( 'acc_lead' ) ); ?></p>
	</div>
</section>

<section class="ysf-section">
	<div class="ysf-wrap">

		<?php if ( ! $ysf_logged ) : ?>
			<?php $ysf_reset = ysf_password_reset_request(); ?>

			<?php if ( $ysf_reset['user'] ) : ?>

				<div class="ysf-auth ysf-auth--single">
					<div class="ysf-auth__card">
						<h2 class="ysf-auth__title"><?php ysf_e( 'acc_reset_title' ); ?></h2>
						<p class="ysf-muted"><?php echo esc_html( $ysf_reset['user']->user_login ); ?></p>

						<form class="ysf-form" data-ysf-form="resetpass" novalidate>
							<input type="hidden" name="key" value="<?php echo esc_attr( $ysf_reset['key'] ); ?>">
							<input type="hidden" name="login" value="<?php echo esc_attr( $ysf_reset['login'] ); ?>">

							<div class="ysf-field ysf-field--full">
								<label for="ysf-reset-pass"><?php ysf_e( 'acc_password_new' ); ?> <span class="ysf-req">*</span></label>
								<input type="password" id="ysf-reset-pass" name="password" required minlength="8" autocomplete="new-password">
								<small><?php ysf_e( 'acc_password_hint' ); ?></small>
							</div>

							<div class="ysf-field ysf-field--full">
								<label for="ysf-reset-pass2"><?php ysf_e( 'acc_password_again' ); ?> <span class="ysf-req">*</span></label>
								<input type="password" id="ysf-reset-pass2" name="password2" required minlength="8" autocomplete="new-password">
							</div>

							<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

							<div class="ysf-field ysf-field--full">
								<button type="submit" class="ysf-btn ysf-btn--block" data-ysf-submit>
									<?php ysf_e( 'acc_reset_btn' ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>

			<?php else : ?>

			<div class="ysf-auth">
				<?php if ( $ysf_reset['error'] ) : ?>
					<div class="ysf-alert ysf-alert--err ysf-field--full" role="alert"><?php echo esc_html( $ysf_reset['error'] ); ?></div>
				<?php endif; ?>

				<div class="ysf-auth__card">
					<h2 class="ysf-auth__title"><?php ysf_e( 'acc_login_title' ); ?></h2>

					<form class="ysf-form" data-ysf-form="login" novalidate>
						<div data-ysf-login-creds>
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
						</div>

						<?php get_template_part( 'template-parts/login-2fa' ); ?>

						<label class="ysf-hp" aria-hidden="true">
							<?php esc_html_e( 'Bu alanı boş bırakın', 'ysffoodlab' ); ?>
							<input type="checkbox" name="ysf_hp" value="1" tabindex="-1" autocomplete="off">
						</label>

						<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

						<div class="ysf-field ysf-field--full">
							<button type="submit" class="ysf-btn ysf-btn--block" data-ysf-submit>
								<?php ysf_e( 'acc_login_btn' ); ?>
							</button>
						</div>
					</form>

					<details class="ysf-details" data-ysf-login-aside<?php echo $ysf_reset['error'] ? ' open' : ''; ?>>
						<summary><?php ysf_e( 'acc_forgot' ); ?></summary>

						<form class="ysf-form" data-ysf-form="lostpass" novalidate>
							<p class="ysf-muted"><?php ysf_e( 'acc_forgot_hint' ); ?></p>

							<div class="ysf-field ysf-field--full">
								<label for="ysf-lost-user"><?php ysf_e( 'acc_login_or_phone' ); ?> <span class="ysf-req">*</span></label>
								<input type="text" id="ysf-lost-user" name="login" required autocomplete="username">
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

						<div data-ysf-register-panel>
							<form class="ysf-form ysf-form--2col" data-ysf-form="register" novalidate>
								<div class="ysf-field ysf-field--full">
									<label for="ysf-reg-name"><?php ysf_e( 'form_name' ); ?> <span class="ysf-req">*</span></label>
									<input type="text" id="ysf-reg-name" name="name" required autocomplete="name">
								</div>

								<div class="ysf-field ysf-field--full">
									<label for="ysf-reg-user"><?php ysf_e( 'acc_username' ); ?></label>
									<input type="text" id="ysf-reg-user" name="username" maxlength="30"
										autocapitalize="none" autocorrect="off" spellcheck="false" autocomplete="nickname"
										placeholder="satuk.bughra">
									<small><?php ysf_e( 'acc_username_hint' ); ?></small>
								</div>

								<div class="ysf-field">
									<label for="ysf-reg-email"><?php ysf_e( 'form_email' ); ?> <span class="ysf-req">*</span></label>
									<input type="text" id="ysf-reg-email" name="email" required inputmode="email"
										autocomplete="email" autocapitalize="none" spellcheck="false">
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
									<input type="checkbox" name="consent" value="1">
									<span>
										<?php ysf_e( 'form_consent' ); ?>
										<?php if ( ysf_get_option( 'ysf_kvkk_url', '' ) ) : ?>
											<a href="<?php echo esc_url( ysf_get_option( 'ysf_kvkk_url', '' ) ); ?>" target="_blank" rel="noopener">KVKK</a>
										<?php endif; ?>
									</span>
								</label>

								<label class="ysf-hp" aria-hidden="true">
									<?php esc_html_e( 'Bu alanı boş bırakın', 'ysffoodlab' ); ?>
									<input type="checkbox" name="ysf_hp" value="1" tabindex="-1" autocomplete="off">
								</label>

								<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

								<div class="ysf-field ysf-field--full">
									<button type="submit" class="ysf-btn ysf-btn--block" data-ysf-submit>
										<?php ysf_e( 'acc_register_btn' ); ?>
									</button>
								</div>
							</form>
						</div>

						<div data-ysf-verify-panel hidden>
							<h3 class="ysf-auth__subtitle"><?php ysf_e( 'acc_verify_title' ); ?></h3>
							<p class="ysf-muted"><?php ysf_e( 'acc_verify_lead' ); ?></p>
							<p class="ysf-muted"><strong data-ysf-verify-email></strong></p>

							<form class="ysf-form" data-ysf-form="verify" novalidate>
								<input type="hidden" name="token" id="ysf-verify-token" value="">

								<div class="ysf-field ysf-field--full">
									<label for="ysf-verify-code"><?php ysf_e( 'acc_verify_code' ); ?> <span class="ysf-req">*</span></label>
									<input type="text" id="ysf-verify-code" name="code" required inputmode="numeric"
										autocomplete="one-time-code" maxlength="8" pattern="[0-9 ]{6,8}"
										class="ysf-verify-code" autocapitalize="none">
								</div>

								<div class="ysf-alert ysf-field--full" data-ysf-result hidden></div>

								<div class="ysf-field ysf-field--full">
									<button type="submit" class="ysf-btn ysf-btn--block" data-ysf-submit>
										<?php ysf_e( 'acc_verify_btn' ); ?>
									</button>
								</div>
							</form>

							<p class="ysf-auth__links">
								<button type="button" class="ysf-link-btn" data-ysf-resend-verify><?php ysf_e( 'acc_verify_resend' ); ?></button>
								<button type="button" class="ysf-link-btn" data-ysf-verify-back><?php ysf_e( 'acc_verify_back' ); ?></button>
							</p>
						</div>

					<?php endif; ?>
				</div>
			</div>

			<?php endif; ?>

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

				<?php
				$ysf_tab_kitchen       = ysf_can_manage_menu() || ysf_can_view_kds();
				$ysf_tab_campaigns     = current_user_can( 'manage_options' );
				$ysf_tab_announcements = current_user_can( 'manage_options' );
				$ysf_tab_waiter        = ysf_can_take_orders();
				$ysf_tab_cashier       = ysf_can_cashier();
				$ysf_tabbed            = $ysf_tab_kitchen || $ysf_tab_campaigns || $ysf_tab_announcements || $ysf_tab_waiter || $ysf_tab_cashier;
				$ysf_tab_start         = $ysf_tab_kitchen ? 'kitchen' : ( $ysf_tab_campaigns ? 'campaigns' : ( $ysf_tab_announcements ? 'announcements' : ( $ysf_tab_waiter ? 'waiter' : ( $ysf_tab_cashier ? 'cashier' : 'profile' ) ) ) );
				?>

				<?php if ( $ysf_tabbed ) : ?>
					<nav class="ysf-acc-tabs" data-ysf-acc-tabs aria-label="<?php echo esc_attr( ysf_t( 'staff_login' ) ); ?>">
						<?php if ( $ysf_tab_kitchen ) : ?>
							<button type="button" class="ysf-acc-tabs__tab<?php echo 'kitchen' === $ysf_tab_start ? ' is-active' : ''; ?>" data-ysf-acc-tab="kitchen"><?php ysf_e( 'kit_eyebrow' ); ?></button>
						<?php endif; ?>
						<?php if ( $ysf_tab_campaigns ) : ?>
							<button type="button" class="ysf-acc-tabs__tab<?php echo 'campaigns' === $ysf_tab_start ? ' is-active' : ''; ?>" data-ysf-acc-tab="campaigns"><?php ysf_e( 'camp_tab' ); ?></button>
						<?php endif; ?>
						<?php if ( $ysf_tab_announcements ) : ?>
							<button type="button" class="ysf-acc-tabs__tab<?php echo 'announcements' === $ysf_tab_start ? ' is-active' : ''; ?>" data-ysf-acc-tab="announcements"><?php ysf_e( 'ann_tab' ); ?></button>
						<?php endif; ?>
						<?php if ( $ysf_tab_waiter ) : ?>
							<button type="button" class="ysf-acc-tabs__tab<?php echo 'waiter' === $ysf_tab_start ? ' is-active' : ''; ?>" data-ysf-acc-tab="waiter"><?php ysf_e( 'pos_name' ); ?></button>
						<?php endif; ?>
						<?php if ( $ysf_tab_cashier ) : ?>
							<button type="button" class="ysf-acc-tabs__tab<?php echo 'cashier' === $ysf_tab_start ? ' is-active' : ''; ?>" data-ysf-acc-tab="cashier"><?php ysf_e( 'cash_name' ); ?></button>
						<?php endif; ?>
						<button type="button" class="ysf-acc-tabs__tab" data-ysf-acc-tab="profile"><?php ysf_e( 'acc_tab_profile' ); ?></button>
					</nav>
				<?php endif; ?>

				<?php if ( $ysf_tab_kitchen ) : ?>
					<div class="ysf-acc-panel" data-ysf-acc-panel="kitchen" <?php echo ( $ysf_tabbed && 'kitchen' !== $ysf_tab_start ) ? 'hidden' : ''; ?>>
						<?php if ( ysf_can_view_kds() && ysf_kds_url() ) : ?>
							<nav class="ysf-staff-launch">
								<a class="ysf-btn" href="<?php echo esc_url( ysf_kds_url() ); ?>"><?php ysf_e( 'staff_open_kds' ); ?></a>
							</nav>
						<?php endif; ?>
						<?php if ( ysf_can_manage_menu() ) : ?>
							<div class="ysf-card-panel ysf-card-panel--kitchen">
								<?php get_template_part( 'template-parts/kitchen-desk' ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $ysf_tab_campaigns ) : ?>
					<div class="ysf-acc-panel" data-ysf-acc-panel="campaigns" <?php echo ( $ysf_tabbed && 'campaigns' !== $ysf_tab_start ) ? 'hidden' : ''; ?>>
						<div class="ysf-card-panel ysf-card-panel--kitchen">
							<?php get_template_part( 'template-parts/account-campaigns' ); ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $ysf_tab_announcements ) : ?>
					<div class="ysf-acc-panel" data-ysf-acc-panel="announcements" <?php echo ( $ysf_tabbed && 'announcements' !== $ysf_tab_start ) ? 'hidden' : ''; ?>>
						<div class="ysf-card-panel ysf-card-panel--kitchen">
							<?php get_template_part( 'template-parts/account-announcements' ); ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $ysf_tab_waiter ) : ?>
					<div class="ysf-acc-panel" data-ysf-acc-panel="waiter" <?php echo 'waiter' === $ysf_tab_start ? '' : 'hidden'; ?>>
						<div class="ysf-card-panel">
							<h3 class="ysf-card-panel__title"><?php ysf_e( 'pos_name' ); ?></h3>
							<p class="ysf-muted"><?php ysf_e( 'pos_tab_lead' ); ?></p>
							<?php if ( ysf_waiter_url() ) : ?>
								<p>
									<a class="ysf-btn" href="<?php echo esc_url( ysf_waiter_url() ); ?>"><?php ysf_e( 'staff_open_pos' ); ?></a>
								</p>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $ysf_tab_cashier ) : ?>
					<div class="ysf-acc-panel" data-ysf-acc-panel="cashier" <?php echo 'cashier' === $ysf_tab_start ? '' : 'hidden'; ?>>
						<div class="ysf-card-panel">
							<h3 class="ysf-card-panel__title"><?php ysf_e( 'cash_name' ); ?></h3>
							<p class="ysf-muted"><?php ysf_e( 'cash_tab_lead' ); ?></p>
							<?php if ( ysf_cashier_url() ) : ?>
								<p>
									<a class="ysf-btn" href="<?php echo esc_url( ysf_cashier_url() ); ?>"><?php ysf_e( 'staff_open_cash' ); ?></a>
								</p>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="ysf-acc-panel" data-ysf-acc-panel="profile" <?php echo ( $ysf_tabbed && 'profile' !== $ysf_tab_start ) ? 'hidden' : ''; ?>>
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
								<label for="ysf-prof-user"><?php ysf_e( 'acc_username' ); ?></label>
								<input type="text" id="ysf-prof-user" value="<?php echo esc_attr( $ysf_user->user_login ); ?>"
									readonly disabled autocomplete="username">
								<small><?php ysf_e( 'acc_username_lock' ); ?></small>
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
				</div>

				<?php
				$ysf_orders = ( ysf_is_kitchen_staff() || ysf_is_waiter() || ysf_is_cashier() ) ? array() : ysf_user_records( 'ysf_order', 5 );
				$ysf_res    = ( ysf_is_kitchen_staff() || ysf_is_waiter() || ysf_is_cashier() ) ? array() : ysf_user_records( 'ysf_reservation', 5 );
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
