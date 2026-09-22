<?php
/**
 * Adres alanları (kayıt formu ve adres defteri için).
 *
 * Zorunlu alanlar sunucu tarafında doğrulanır: adres tamamen boşsa geçerli
 * sayılır, doldurulduysa il/ilçe/mahalle/sokak/bina zorunlu olur. Bu yüzden
 * alanlara HTML required verilmez, gerekli olduğunda betik ekler.
 *
 * @package ysffoodlab
 */

$ysf_type    = isset( $args['type'] ) ? sanitize_key( $args['type'] ) : 'home';
$ysf_prefix  = 'ysf-addr-' . $ysf_type;
$ysf_geo     = ysf_geo_data();
$ysf_address = wp_parse_args(
	isset( $args['address'] ) && is_array( $args['address'] ) ? $args['address'] : array(),
	array_fill_keys( array_keys( ysf_address_fields() ), '' )
);
?>

<div class="ysf-addr-grid" data-ysf-addr>
	<div class="ysf-field">
		<label for="<?php echo esc_attr( $ysf_prefix ); ?>-il"><?php ysf_e( 'addr_province' ); ?></label>
		<select id="<?php echo esc_attr( $ysf_prefix ); ?>-il" name="addr_il" data-ysf-province>
			<option value=""><?php ysf_e( 'addr_select' ); ?></option>
			<?php foreach ( array_keys( $ysf_geo ) as $ysf_il ) : ?>
				<option value="<?php echo esc_attr( $ysf_il ); ?>" <?php selected( $ysf_address['il'], $ysf_il ); ?>>
					<?php echo esc_html( $ysf_il ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="ysf-field">
		<label for="<?php echo esc_attr( $ysf_prefix ); ?>-ilce"><?php ysf_e( 'addr_district' ); ?></label>
		<select id="<?php echo esc_attr( $ysf_prefix ); ?>-ilce" name="addr_ilce" data-ysf-district>
			<?php if ( $ysf_address['ilce'] ) : ?>
				<option value="<?php echo esc_attr( $ysf_address['ilce'] ); ?>" selected>
					<?php echo esc_html( $ysf_address['ilce'] ); ?>
				</option>
			<?php else : ?>
				<option value=""><?php ysf_e( 'addr_select_first' ); ?></option>
			<?php endif; ?>
		</select>
	</div>

	<div class="ysf-field">
		<label for="<?php echo esc_attr( $ysf_prefix ); ?>-mahalle"><?php ysf_e( 'addr_neighbourhood' ); ?></label>
		<input type="text" id="<?php echo esc_attr( $ysf_prefix ); ?>-mahalle" name="addr_mahalle"
			value="<?php echo esc_attr( $ysf_address['mahalle'] ); ?>" maxlength="120">
	</div>

	<div class="ysf-field">
		<label for="<?php echo esc_attr( $ysf_prefix ); ?>-sokak"><?php ysf_e( 'addr_street' ); ?></label>
		<input type="text" id="<?php echo esc_attr( $ysf_prefix ); ?>-sokak" name="addr_sokak"
			value="<?php echo esc_attr( $ysf_address['sokak'] ); ?>" maxlength="120" autocomplete="off">
	</div>

	<div class="ysf-field">
		<label for="<?php echo esc_attr( $ysf_prefix ); ?>-bina"><?php ysf_e( 'addr_building' ); ?></label>
		<input type="text" id="<?php echo esc_attr( $ysf_prefix ); ?>-bina" name="addr_bina"
			value="<?php echo esc_attr( $ysf_address['bina'] ); ?>" maxlength="30">
	</div>

	<div class="ysf-field">
		<label for="<?php echo esc_attr( $ysf_prefix ); ?>-daire"><?php ysf_e( 'addr_flat' ); ?></label>
		<input type="text" id="<?php echo esc_attr( $ysf_prefix ); ?>-daire" name="addr_daire"
			value="<?php echo esc_attr( $ysf_address['daire'] ); ?>" maxlength="30">
	</div>

	<div class="ysf-field">
		<label for="<?php echo esc_attr( $ysf_prefix ); ?>-posta"><?php ysf_e( 'addr_zip' ); ?></label>
		<input type="text" id="<?php echo esc_attr( $ysf_prefix ); ?>-posta" name="addr_posta_kodu"
			value="<?php echo esc_attr( $ysf_address['posta_kodu'] ); ?>"
			inputmode="numeric" maxlength="5" autocomplete="off">
	</div>

	<div class="ysf-field ysf-field--full">
		<label for="<?php echo esc_attr( $ysf_prefix ); ?>-tarif"><?php ysf_e( 'addr_note' ); ?></label>
		<textarea id="<?php echo esc_attr( $ysf_prefix ); ?>-tarif" name="addr_tarif" rows="2" maxlength="300"
			placeholder="<?php echo esc_attr( ysf_t( 'addr_note_ph' ) ); ?>"><?php echo esc_textarea( $ysf_address['tarif'] ); ?></textarea>
	</div>
</div>
