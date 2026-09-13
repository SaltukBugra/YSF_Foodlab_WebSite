<?php
/**
 * Arama formu.
 *
 * @package ysffoodlab
 */

$ysf_search_id = 'ysf-search-' . wp_unique_id();
?>
<form role="search" method="get" class="ysf-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="ysf-field">
		<label for="<?php echo esc_attr( $ysf_search_id ); ?>" class="ysf-visually-hidden">
			<?php esc_html_e( 'Ara', 'ysffoodlab' ); ?>
		</label>
		<div style="display:flex;gap:8px">
			<input
				type="search"
				id="<?php echo esc_attr( $ysf_search_id ); ?>"
				name="s"
				value="<?php echo esc_attr( get_search_query() ); ?>"
				placeholder="<?php esc_attr_e( 'Sitede ara…', 'ysffoodlab' ); ?>"
			>
			<button type="submit" class="ysf-btn"><?php esc_html_e( 'Ara', 'ysffoodlab' ); ?></button>
		</div>
	</div>
</form>
