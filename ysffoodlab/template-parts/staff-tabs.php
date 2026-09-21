<?php
/**
 * Personel sekmeleri: Garson | Mutfak | Kasiyer.
 *
 * Yalnızca kullanıcının yetkisi olan sekmeler görünür. Tek yetki varsa
 * (örneğin yalnızca garson) sekme şeridi gizlenir.
 *
 * @package ysffoodlab
 *
 * @var array $args {
 *     @type string $current 'waiter', 'kds' veya 'cashier'.
 * }
 */

$ysf_current     = isset( $args['current'] ) ? $args['current'] : '';
$ysf_waiter      = ysf_waiter_url();
$ysf_kds         = ysf_kds_url();
$ysf_cashier     = ysf_cashier_url();
$ysf_show_pos    = $ysf_waiter && ysf_can_take_orders();
$ysf_show_kds    = $ysf_kds && ysf_can_view_kds();
$ysf_show_cash   = $ysf_cashier && ysf_can_cashier();
$ysf_tab_count   = (int) $ysf_show_pos + (int) $ysf_show_kds + (int) $ysf_show_cash;

if ( $ysf_tab_count < 2 ) {
	return;
}
?>
<nav class="ysf-staff-tabs" aria-label="<?php echo esc_attr( ysf_t( 'staff_login' ) ); ?>">
	<?php if ( $ysf_show_pos ) : ?>
		<a
			class="ysf-staff-tabs__tab<?php echo 'waiter' === $ysf_current ? ' is-active' : ''; ?>"
			href="<?php echo esc_url( $ysf_waiter ); ?>"
			<?php echo 'waiter' === $ysf_current ? 'aria-current="page"' : ''; ?>
		><?php ysf_e( 'pos_name' ); ?></a>
	<?php endif; ?>
	<?php if ( $ysf_show_kds ) : ?>
		<a
			class="ysf-staff-tabs__tab<?php echo 'kds' === $ysf_current ? ' is-active' : ''; ?>"
			href="<?php echo esc_url( $ysf_kds ); ?>"
			<?php echo 'kds' === $ysf_current ? 'aria-current="page"' : ''; ?>
		><?php ysf_e( 'kit_eyebrow' ); ?></a>
	<?php endif; ?>
	<?php if ( $ysf_show_cash ) : ?>
		<a
			class="ysf-staff-tabs__tab<?php echo 'cashier' === $ysf_current ? ' is-active' : ''; ?>"
			href="<?php echo esc_url( $ysf_cashier ); ?>"
			<?php echo 'cashier' === $ysf_current ? 'aria-current="page"' : ''; ?>
		><?php ysf_e( 'cash_name' ); ?></a>
	<?php endif; ?>
</nav>
