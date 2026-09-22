<?php
/**
 * Template Name: Garson Uygulaması
 * Description: Masalardan alınan siparişlerin girilmesi ve yönetilmesi (personel, mobil).
 *
 * @package ysffoodlab
 */

get_header( 'staff' );

$ysf_user     = wp_get_current_user();
$ysf_can      = ysf_can_take_orders();
$ysf_here     = get_permalink();
$ysf_logo_id  = get_theme_mod( 'custom_logo' );
?>

<?php if ( ! is_user_logged_in() ) : ?>

	<?php
	get_template_part(
		'template-parts/staff-login',
		null,
		array(
			'title'    => ysf_t( 'pos_name' ),
			'lead'     => ysf_t( 'staff_need_login' ),
			'redirect' => $ysf_here,
		)
	);
	?>

<?php elseif ( ! $ysf_can || ! ysf_floor_enabled() ) : ?>

	<section class="ysf-staff-auth">
		<div class="ysf-staff-auth__card">
			<h1><?php ysf_e( 'pos_name' ); ?></h1>
			<p><?php echo esc_html( ysf_floor_enabled() ? ysf_t( 'pos_forbidden' ) : ysf_t( 'pos_disabled' ) ); ?></p>
			<p>
				<a class="ysf-btn" href="<?php echo esc_url( ysf_account_url() ); ?>"><?php ysf_e( 'acc_nav' ); ?></a>
				<a class="ysf-btn ysf-btn--ghost" href="<?php echo esc_url( wp_logout_url( $ysf_here ) ); ?>"><?php ysf_e( 'acc_logout' ); ?></a>
			</p>
		</div>
	</section>

<?php else : ?>

	<div class="ysf-pos" data-ysf-waiter>
		<header class="ysf-staff-bar">
			<div class="ysf-staff-bar__brand">
				<?php if ( $ysf_logo_id ) : ?>
					<?php echo wp_get_attachment_image( $ysf_logo_id, 'thumbnail', false, array( 'class' => 'ysf-staff-bar__logo', 'alt' => '' ) ); ?>
				<?php endif; ?>
				<div>
					<p class="ysf-staff-bar__kicker"><?php bloginfo( 'name' ); ?></p>
					<h1><?php ysf_e( 'pos_name' ); ?></h1>
				</div>
			</div>
			<div class="ysf-staff-bar__meta">
				<span class="ysf-staff-bar__user"><?php echo esc_html( $ysf_user->display_name ); ?></span>
				<a class="ysf-staff-bar__link" href="<?php echo esc_url( wp_logout_url( $ysf_here ) ); ?>"><?php ysf_e( 'acc_logout' ); ?></a>
			</div>
		</header>

		<?php get_template_part( 'template-parts/staff-tabs', null, array( 'current' => 'waiter' ) ); ?>

		<div class="ysf-pos__flash" data-ysf-pos-flash hidden></div>

		<section class="ysf-pos__view is-active" data-ysf-view="floor">
			<div class="ysf-pos__legend">
				<span data-status="empty"><?php ysf_e( 'pos_empty' ); ?></span>
				<span data-status="occupied"><?php ysf_e( 'pos_busy' ); ?></span>
			</div>
			<div class="ysf-pos__grid" data-ysf-tables></div>
		</section>

		<section class="ysf-pos__view" data-ysf-view="table" hidden>
			<header class="ysf-pos__subhead">
				<button type="button" class="ysf-pos__back" data-ysf-back-floor><?php ysf_e( 'pos_back' ); ?></button>
				<div>
					<h2 data-ysf-table-title></h2>
					<p class="ysf-muted" data-ysf-table-sum></p>
				</div>
			</header>
			<div class="ysf-pos__tickets" data-ysf-tickets></div>
			<div class="ysf-pos__table-actions">
				<button type="button" class="ysf-btn" data-ysf-open-menu><?php ysf_e( 'pos_add' ); ?></button>
				<button type="button" class="ysf-btn" data-ysf-done><?php ysf_e( 'pos_done' ); ?></button>
				<button type="button" class="ysf-btn ysf-btn--ghost" data-ysf-move-table><?php ysf_e( 'pos_move' ); ?></button>
				<button type="button" class="ysf-btn ysf-btn--ghost" data-ysf-close-table><?php ysf_e( 'pos_close' ); ?></button>
				<button type="button" class="ysf-btn ysf-btn--ghost" data-ysf-back-floor><?php ysf_e( 'pos_back' ); ?></button>
			</div>
		</section>

		<section class="ysf-pos__view" data-ysf-view="move" hidden>
			<header class="ysf-pos__subhead">
				<button type="button" class="ysf-pos__back" data-ysf-back-table><?php ysf_e( 'pos_back' ); ?></button>
				<div>
					<h2><?php ysf_e( 'pos_move_pick' ); ?></h2>
					<p class="ysf-muted" data-ysf-move-from></p>
				</div>
			</header>
			<p class="ysf-muted ysf-pos__hint"><?php ysf_e( 'pos_move_lead' ); ?></p>
			<div class="ysf-pos__legend">
				<span data-status="empty"><?php ysf_e( 'pos_empty' ); ?></span>
				<span data-status="occupied"><?php ysf_e( 'pos_busy' ); ?></span>
			</div>
			<div class="ysf-pos__grid" data-ysf-move-tables></div>
		</section>

		<section class="ysf-pos__view" data-ysf-view="menu" hidden>
			<header class="ysf-pos__subhead">
				<button type="button" class="ysf-pos__back" data-ysf-back-table><?php ysf_e( 'pos_back_table' ); ?></button>
				<div>
					<h2><?php ysf_e( 'order_pick_menu' ); ?></h2>
					<p class="ysf-muted" data-ysf-menu-table></p>
				</div>
			</header>
			<input type="search" class="ysf-pos__search" data-ysf-menu-search placeholder="<?php echo esc_attr( ysf_t( 'menu_search' ) ); ?>" autocomplete="off">
			<div class="ysf-filters ysf-pos__cats" data-ysf-menu-cats role="group"></div>
			<div class="ysf-pos__items" data-ysf-menu-items></div>
			<div class="ysf-pos__composer">
				<label class="ysf-visually-hidden" for="ysf-pos-note"><?php ysf_e( 'pos_note' ); ?></label>
				<input type="text" id="ysf-pos-note" data-ysf-ticket-note maxlength="200" placeholder="<?php echo esc_attr( ysf_t( 'pos_note_ph' ) ); ?>">
				<div class="ysf-pos__composer-nav">
					<button type="button" class="ysf-btn ysf-btn--ghost" data-ysf-back-table><?php ysf_e( 'pos_back' ); ?></button>
					<button type="button" class="ysf-btn" data-ysf-done><?php ysf_e( 'pos_done' ); ?></button>
				</div>
				<button type="button" class="ysf-btn ysf-btn--block" data-ysf-send disabled>
					<span data-ysf-send-label><?php ysf_e( 'pos_send' ); ?></span>
				</button>
			</div>
		</section>
	</div>

<?php endif; ?>

<?php
get_footer( 'staff' );
