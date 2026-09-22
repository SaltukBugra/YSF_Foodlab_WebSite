<?php
/**
 * Özel alanlar: fiyat, İngilizce çeviri, rozet, diyet etiketleri.
 *
 * Alanlar REST API üzerinden de yazılabilir (show_in_rest), böylece
 * içerikler panel dışından toplu olarak da doldurulabilir.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menü ürünü alan tanımları.
 *
 * @return array
 */
function ysf_menu_item_fields() {
	return array(
		'_ysf_price'       => array(
			'label' => __( 'Fiyat', 'ysffoodlab' ),
			'type'  => 'number',
			'rest'  => 'number',
			'help'  => __( 'Ebat yoksa bu fiyat geçerlidir. Ebat eklerseniz menüde bilgi, online siparişte seçim olur.', 'ysffoodlab' ),
		),
		'_ysf_price_old'   => array(
			'label' => __( 'İndirim öncesi fiyat', 'ysffoodlab' ),
			'type'  => 'number',
			'rest'  => 'number',
			'help'  => __( 'Boş bırakabilirsiniz. Doluysa üstü çizili gösterilir.', 'ysffoodlab' ),
		),
		'_ysf_title_en'    => array(
			'label' => __( 'İngilizce ürün adı', 'ysffoodlab' ),
			'type'  => 'text',
			'rest'  => 'string',
		),
		'_ysf_excerpt_en'  => array(
			'label' => __( 'İngilizce kısa açıklama', 'ysffoodlab' ),
			'type'  => 'textarea',
			'rest'  => 'string',
			'full'  => true,
		),
		'_ysf_allergens'   => array(
			'label' => __( 'Alerjenler', 'ysffoodlab' ),
			'type'  => 'text',
			'rest'  => 'string',
			'help'  => __( 'Virgülle ayırın: gluten, süt, fındık', 'ysffoodlab' ),
		),
		'_ysf_calories'    => array(
			'label' => __( 'Kalori (kcal)', 'ysffoodlab' ),
			'type'  => 'number',
			'rest'  => 'number',
		),
		'_ysf_prep'        => array(
			'label' => __( 'Hazırlanma süresi (dk)', 'ysffoodlab' ),
			'type'  => 'number',
			'rest'  => 'number',
		),
		'_ysf_featured'    => array(
			'label' => __( 'Ana sayfada öne çıkar', 'ysffoodlab' ),
			'type'  => 'checkbox',
			'rest'  => 'boolean',
		),
		'_ysf_orderable'   => array(
			'label' => __( 'Online siparişe açık', 'ysffoodlab' ),
			'type'  => 'checkbox',
			'rest'  => 'boolean',
			'help'  => __( 'İşaretli değilse ürün sepete eklenemez, sadece menüde görünür.', 'ysffoodlab' ),
		),
		'_ysf_sold_out'    => array(
			'label' => __( 'Tükendi', 'ysffoodlab' ),
			'type'  => 'checkbox',
			'rest'  => 'boolean',
		),
		'_ysf_vegan'       => array(
			'label' => __( 'Vegan', 'ysffoodlab' ),
			'type'  => 'checkbox',
			'rest'  => 'boolean',
		),
		'_ysf_vegetarian'  => array(
			'label' => __( 'Vejetaryen', 'ysffoodlab' ),
			'type'  => 'checkbox',
			'rest'  => 'boolean',
		),
		'_ysf_spicy'       => array(
			'label' => __( 'Acı', 'ysffoodlab' ),
			'type'  => 'checkbox',
			'rest'  => 'boolean',
		),
		'_ysf_glutenfree'  => array(
			'label' => __( 'Glutensiz', 'ysffoodlab' ),
			'type'  => 'checkbox',
			'rest'  => 'boolean',
		),
	);
}

/**
 * Kampanya / duyuru alan tanımları.
 *
 * @return array
 */
function ysf_campaign_fields() {
	return array(
		'_ysf_type'        => array(
			'label'   => __( 'Kayıt tipi', 'ysffoodlab' ),
			'type'    => 'select',
			'rest'    => 'string',
			'options' => array(
				'kampanya' => __( 'Kampanya', 'ysffoodlab' ),
				'duyuru'   => __( 'Duyuru', 'ysffoodlab' ),
			),
		),
		'_ysf_badge'       => array(
			'label' => __( 'Rozet metni', 'ysffoodlab' ),
			'type'  => 'text',
			'rest'  => 'string',
			'help'  => __( 'Örn: %20 indirim, Yeni sezon', 'ysffoodlab' ),
		),
		'_ysf_badge_en'    => array(
			'label' => __( 'Rozet metni (EN)', 'ysffoodlab' ),
			'type'  => 'text',
			'rest'  => 'string',
		),
		'_ysf_start'       => array(
			'label' => __( 'Başlangıç tarihi', 'ysffoodlab' ),
			'type'  => 'date',
			'rest'  => 'string',
		),
		'_ysf_end'         => array(
			'label' => __( 'Bitiş tarihi', 'ysffoodlab' ),
			'type'  => 'date',
			'rest'  => 'string',
			'help'  => __( 'Kampanyalar bölümünden eklenen kayıtlar bu tarihten sonra da “Süresi doldu” yazısıyla görünür.', 'ysffoodlab' ),
		),
		'_ysf_link'        => array(
			'label' => __( 'Buton bağlantısı', 'ysffoodlab' ),
			'type'  => 'url',
			'rest'  => 'string',
		),
		'_ysf_show_in_bar' => array(
			'label' => __( 'Üst duyuru şeridinde göster', 'ysffoodlab' ),
			'type'  => 'checkbox',
			'rest'  => 'boolean',
		),
		'_ysf_title_en'    => array(
			'label' => __( 'İngilizce başlık', 'ysffoodlab' ),
			'type'  => 'text',
			'rest'  => 'string',
		),
		'_ysf_excerpt_en'  => array(
			'label' => __( 'İngilizce kısa açıklama', 'ysffoodlab' ),
			'type'  => 'textarea',
			'rest'  => 'string',
			'full'  => true,
		),
	);
}

/**
 * Yazı ve sayfalar için İngilizce çeviri alanları.
 *
 * @return array
 */
function ysf_translation_fields() {
	return array(
		'_ysf_title_en'   => array(
			'label' => __( 'İngilizce başlık', 'ysffoodlab' ),
			'type'  => 'text',
			'rest'  => 'string',
			'full'  => true,
		),
		'_ysf_excerpt_en' => array(
			'label' => __( 'İngilizce özet', 'ysffoodlab' ),
			'type'  => 'textarea',
			'rest'  => 'string',
			'full'  => true,
		),
		'_ysf_content_en' => array(
			'label' => __( 'İngilizce içerik', 'ysffoodlab' ),
			'type'  => 'editor',
			'rest'  => 'string',
			'full'  => true,
			'help'  => __( 'Boş bırakırsanız İngilizce sürümde Türkçe içerik gösterilir.', 'ysffoodlab' ),
		),
	);
}

/**
 * Meta alanlarını REST API için kaydeder.
 */
function ysf_register_meta() {
	$map = array(
		'ysf_menu_item' => ysf_menu_item_fields(),
		'ysf_campaign'  => ysf_campaign_fields(),
		'post'          => ysf_translation_fields(),
		'page'          => ysf_translation_fields(),
	);

	foreach ( $map as $post_type => $fields ) {
		foreach ( $fields as $key => $field ) {
			$type = isset( $field['rest'] ) ? $field['rest'] : 'string';

			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'default'           => 'boolean' === $type ? ( '_ysf_orderable' === $key ) : ( 'number' === $type ? 0 : '' ),
					'sanitize_callback' => 'ysf_sanitize_meta_' . $type,
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	register_post_meta(
		'ysf_menu_item',
		'_ysf_tags',
		array(
			'type'              => 'array',
			'single'            => true,
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'label'    => array( 'type' => 'string' ),
							'label_en' => array( 'type' => 'string' ),
							'type'     => array( 'type' => 'string' ),
						),
					),
				),
			),
			'default'           => array(),
			'sanitize_callback' => 'ysf_sanitize_tags',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' ) || current_user_can( 'ysf_manage_menu' );
			},
		)
	);

	register_post_meta(
		'ysf_menu_item',
		'_ysf_sizes',
		array(
			'type'              => 'array',
			'single'            => true,
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'key'      => array( 'type' => 'string' ),
							'label'    => array( 'type' => 'string' ),
							'label_en' => array( 'type' => 'string' ),
							'price'    => array( 'type' => 'number' ),
						),
					),
				),
			),
			'default'           => array(),
			'sanitize_callback' => 'ysf_sanitize_sizes',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' ) || current_user_can( 'ysf_manage_menu' );
			},
		)
	);
}
add_action( 'init', 'ysf_register_meta' );

/**
 * Metin meta temizleme.
 *
 * @param mixed $value Değer.
 * @return string
 */
function ysf_sanitize_meta_string( $value ) {
	if ( is_array( $value ) ) {
		return '';
	}

	return wp_kses_post( (string) $value );
}

/**
 * Sayısal meta temizleme.
 *
 * @param mixed $value Değer.
 * @return float
 */
function ysf_sanitize_meta_number( $value ) {
	return (float) str_replace( ',', '.', (string) $value );
}

/**
 * Boolean meta temizleme.
 *
 * @param mixed $value Değer.
 * @return bool
 */
function ysf_sanitize_meta_boolean( $value ) {
	return ysf_is_flag_value( $value, false );
}

/**
 * Menü kategorileri için İngilizce ad alanı.
 */
function ysf_register_term_meta() {
	register_term_meta(
		'ysf_menu_cat',
		'_ysf_name_en',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);
}
add_action( 'init', 'ysf_register_term_meta' );

/**
 * Yeni kategori ekleme formunda EN alanı.
 */
function ysf_term_add_field() {
	?>
	<div class="form-field">
		<label for="ysf-name-en"><?php esc_html_e( 'İngilizce kategori adı', 'ysffoodlab' ); ?></label>
		<input type="text" id="ysf-name-en" name="_ysf_name_en" value="">
		<p><?php esc_html_e( 'Sitenin İngilizce sürümünde bu ad kullanılır.', 'ysffoodlab' ); ?></p>
	</div>
	<?php
	wp_nonce_field( 'ysf_term_meta', 'ysf_term_nonce' );
}
add_action( 'ysf_menu_cat_add_form_fields', 'ysf_term_add_field' );

/**
 * Kategori düzenleme formunda EN alanı.
 *
 * @param WP_Term $term Terim.
 */
function ysf_term_edit_field( $term ) {
	$value = get_term_meta( $term->term_id, '_ysf_name_en', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="ysf-name-en"><?php esc_html_e( 'İngilizce kategori adı', 'ysffoodlab' ); ?></label></th>
		<td>
			<input type="text" id="ysf-name-en" name="_ysf_name_en" value="<?php echo esc_attr( $value ); ?>">
			<?php wp_nonce_field( 'ysf_term_meta', 'ysf_term_nonce' ); ?>
		</td>
	</tr>
	<?php
}
add_action( 'ysf_menu_cat_edit_form_fields', 'ysf_term_edit_field' );

/**
 * Terim meta kaydı.
 *
 * @param int $term_id Terim kimliği.
 */
function ysf_save_term_meta( $term_id ) {
	if ( ! isset( $_POST['ysf_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ysf_term_nonce'] ) ), 'ysf_term_meta' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	$value = isset( $_POST['_ysf_name_en'] ) ? sanitize_text_field( wp_unslash( $_POST['_ysf_name_en'] ) ) : '';
	update_term_meta( $term_id, '_ysf_name_en', $value );
}
add_action( 'created_ysf_menu_cat', 'ysf_save_term_meta' );
add_action( 'edited_ysf_menu_cat', 'ysf_save_term_meta' );

/**
 * Meta kutularını ekler.
 */
function ysf_add_meta_boxes() {
	add_meta_box(
		'ysf_menu_item_box',
		__( 'Ürün Detayları (fiyat, etiketler, İngilizce)', 'ysffoodlab' ),
		'ysf_render_meta_box',
		'ysf_menu_item',
		'normal',
		'high',
		array( 'fields' => 'menu_item' )
	);

	add_meta_box(
		'ysf_menu_tags_box',
		__( 'İsim yanı etiketler', 'ysffoodlab' ),
		'ysf_render_tags_box',
		'ysf_menu_item',
		'normal',
		'high'
	);

	add_meta_box(
		'ysf_campaign_box',
		__( 'Kampanya Ayarları', 'ysffoodlab' ),
		'ysf_render_meta_box',
		'ysf_campaign',
		'normal',
		'high',
		array( 'fields' => 'campaign' )
	);

	foreach ( array( 'post', 'page' ) as $type ) {
		add_meta_box(
			'ysf_translation_box',
			__( 'İngilizce Sürüm (EN)', 'ysffoodlab' ),
			'ysf_render_meta_box',
			$type,
			'normal',
			'low',
			array( 'fields' => 'translation' )
		);
	}

	add_meta_box(
		'ysf_request_box',
		__( 'Talep Detayları', 'ysffoodlab' ),
		'ysf_render_request_box',
		array( 'ysf_reservation', 'ysf_order' ),
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ysf_add_meta_boxes' );

/**
 * Alan grubunu isme göre döndürür.
 *
 * @param string $group Grup adı.
 * @return array
 */
function ysf_get_field_group( $group ) {
	switch ( $group ) {
		case 'menu_item':
			return ysf_menu_item_fields();
		case 'campaign':
			return ysf_campaign_fields();
		case 'translation':
			return ysf_translation_fields();
	}

	return array();
}

/**
 * Meta kutusunu çizer.
 *
 * @param WP_Post $post     Gönderi.
 * @param array   $meta_box Kutu argümanları.
 */
function ysf_render_meta_box( $post, $meta_box ) {
	$group  = isset( $meta_box['args']['fields'] ) ? $meta_box['args']['fields'] : '';
	$fields = ysf_get_field_group( $group );

	if ( empty( $fields ) ) {
		return;
	}

	wp_nonce_field( 'ysf_save_meta', 'ysf_meta_nonce' );

	echo '<div class="ysf-meta-grid">';

	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		$id    = 'ysf-field-' . sanitize_key( $key );
		$class = ! empty( $field['full'] ) ? 'ysf-meta-full' : '';

		echo '<p class="' . esc_attr( $class ) . '">';

		if ( 'checkbox' === $field['type'] ) {
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
				esc_attr( $id ),
				esc_attr( $key ),
				checked( $value, true, false ),
				esc_html( $field['label'] )
			);
		} else {
			printf( '<label for="%1$s">%2$s</label>', esc_attr( $id ), esc_html( $field['label'] ) );

			switch ( $field['type'] ) {
				case 'textarea':
					printf(
						'<textarea id="%1$s" name="%2$s" rows="3">%3$s</textarea>',
						esc_attr( $id ),
						esc_attr( $key ),
						esc_textarea( $value )
					);
					break;

				case 'editor':
					wp_editor(
						$value,
						$id,
						array(
							'textarea_name' => $key,
							'textarea_rows' => 10,
							'media_buttons' => true,
						)
					);
					break;

				case 'select':
					printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $key ) );
					foreach ( $field['options'] as $opt_key => $opt_label ) {
						printf(
							'<option value="%1$s" %2$s>%3$s</option>',
							esc_attr( $opt_key ),
							selected( $value, $opt_key, false ),
							esc_html( $opt_label )
						);
					}
					echo '</select>';
					break;

				default:
					printf(
						'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" %5$s>',
						esc_attr( $field['type'] ),
						esc_attr( $id ),
						esc_attr( $key ),
						esc_attr( $value ),
						'number' === $field['type'] ? 'step="0.01" min="0"' : ''
					);
			}
		}

		if ( ! empty( $field['help'] ) ) {
			printf( '<br><small>%s</small>', esc_html( $field['help'] ) );
		}

		echo '</p>';

		if ( 'menu_item' === $group && '_ysf_price' === $key ) {
			echo '<div class="ysf-meta-full">';
			ysf_render_sizes_fields( $post );
			echo '</div>';
		}
	}

	echo '</div>';
}

/**
 * İsim yanı etiket kutusunu çizer.
 *
 * @param WP_Post $post Ürün.
 */
function ysf_render_tags_box( $post ) {
	$tags  = ysf_get_item_tags( $post->ID );
	$types = ysf_tag_types();

	wp_nonce_field( 'ysf_save_meta', 'ysf_meta_nonce' );
	echo '<input type="hidden" name="ysf_tags_ready" value="1">';
	echo '<p>' . esc_html__( 'Ürün adının yanında görünen etiketler. Bilgi koyu, olumlu yeşil, olumsuz kırmızı, kampanya sarıdır. Birden fazla ekleyebilirsiniz.', 'ysffoodlab' ) . '</p>';
	echo '<table class="widefat striped" id="ysf-tags-table"><thead><tr>';
	echo '<th>' . esc_html__( 'Metin', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'İngilizce', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'Tür', 'ysffoodlab' ) . '</th>';
	echo '<th></th></tr></thead><tbody>';

	if ( ! $tags ) {
		$tags[] = array(
			'label'    => '',
			'label_en' => '',
			'type'     => 'info',
		);
	}

	foreach ( $tags as $tag ) {
		ysf_render_tag_row( $tag, $types );
	}

	echo '</tbody></table>';
	echo '<p><button type="button" class="button" id="ysf-tag-add">' . esc_html__( 'Etiket ekle', 'ysffoodlab' ) . '</button></p>';
	echo '<template id="ysf-tag-row-tpl">';
	ysf_render_tag_row(
		array(
			'label'    => '',
			'label_en' => '',
			'type'     => 'info',
		),
		$types
	);
	echo '</template>';
	?>
	<script>
	(function () {
		var add = document.getElementById('ysf-tag-add');
		var table = document.getElementById('ysf-tags-table');
		var tpl = document.getElementById('ysf-tag-row-tpl');
		if (!add || !table || !tpl) return;
		add.addEventListener('click', function () {
			var body = table.querySelector('tbody');
			body.insertAdjacentHTML('beforeend', tpl.innerHTML);
		});
		table.addEventListener('click', function (event) {
			if (!event.target.closest('[data-ysf-tag-remove]')) return;
			var row = event.target.closest('tr');
			if (row && table.querySelectorAll('tbody tr').length > 1) row.remove();
			else if (row) {
				row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
			}
		});
	})();
	</script>
	<?php
}

/**
 * Yönetim paneli etiket satırı.
 *
 * @param array $tag   Etiket.
 * @param array $types Türler.
 */
function ysf_render_tag_row( $tag, $types ) {
	echo '<tr>';
	printf(
		'<td><input type="text" name="ysf_tag_label[]" value="%s" class="widefat" maxlength="40"></td>',
		esc_attr( isset( $tag['label'] ) ? $tag['label'] : '' )
	);
	printf(
		'<td><input type="text" name="ysf_tag_label_en[]" value="%s" class="widefat" maxlength="40"></td>',
		esc_attr( isset( $tag['label_en'] ) ? $tag['label_en'] : '' )
	);
	echo '<td><select name="ysf_tag_type[]">';
	foreach ( $types as $key => $meta ) {
		printf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $key ),
			selected( isset( $tag['type'] ) ? $tag['type'] : 'info', $key, false ),
			esc_html( $meta['label'] )
		);
	}
	echo '</select></td>';
	echo '<td><button type="button" class="button-link" data-ysf-tag-remove>' . esc_html__( 'Sil', 'ysffoodlab' ) . '</button></td>';
	echo '</tr>';
}

/**
 * Ebat alanlarını ürün detay kutusunun içine çizer.
 *
 * @param WP_Post $post Ürün.
 */
function ysf_render_sizes_fields( $post ) {
	$sizes = ysf_get_item_sizes( $post->ID );

	if ( ! $sizes ) {
		$sizes[] = array(
			'label'    => '',
			'label_en' => '',
			'price'    => '',
		);
	}

	echo '<input type="hidden" name="ysf_sizes_ready" value="1">';
	echo '<p><strong>' . esc_html__( 'Ebatlar', 'ysffoodlab' ) . '</strong><br>';
	echo esc_html__( 'Küçük, orta, büyük gibi seçenekler. Menüde bilgi, online siparişte seçim olur. Tek fiyat yeterliyse bu satırı boş bırakın.', 'ysffoodlab' ) . '</p>';
	echo '<table class="widefat striped" id="ysf-sizes-table"><thead><tr>';
	echo '<th>' . esc_html__( 'Ebat', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'İngilizce', 'ysffoodlab' ) . '</th>';
	echo '<th>' . esc_html__( 'Fiyat', 'ysffoodlab' ) . '</th>';
	echo '<th></th></tr></thead><tbody>';

	foreach ( $sizes as $size ) {
		ysf_render_size_row( $size );
	}

	echo '</tbody></table>';
	echo '<p><button type="button" class="button" id="ysf-size-add">' . esc_html__( 'Ebat ekle', 'ysffoodlab' ) . '</button></p>';
	echo '<template id="ysf-size-row-tpl">';
	ysf_render_size_row(
		array(
			'label'    => '',
			'label_en' => '',
			'price'    => '',
		)
	);
	echo '</template>';
	?>
	<script>
	(function () {
		var add = document.getElementById('ysf-size-add');
		var table = document.getElementById('ysf-sizes-table');
		var tpl = document.getElementById('ysf-size-row-tpl');
		if (!add || !table || !tpl) return;
		add.addEventListener('click', function () {
			table.querySelector('tbody').insertAdjacentHTML('beforeend', tpl.innerHTML);
		});
		table.addEventListener('click', function (event) {
			if (!event.target.closest('[data-ysf-size-remove]')) return;
			var row = event.target.closest('tr');
			var body = table.querySelector('tbody');
			if (!row || !body) return;
			if (body.querySelectorAll('tr').length > 1) row.remove();
			else row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
		});
	})();
	</script>
	<?php
}

/**
 * Yönetim paneli ebat satırı.
 *
 * @param array $size Ebat.
 */
function ysf_render_size_row( $size ) {
	echo '<tr>';
	printf(
		'<td><input type="text" name="ysf_size_label[]" value="%s" class="widefat" maxlength="24" placeholder="%s"></td>',
		esc_attr( isset( $size['label'] ) ? $size['label'] : '' ),
		esc_attr__( 'Küçük', 'ysffoodlab' )
	);
	printf(
		'<td><input type="text" name="ysf_size_label_en[]" value="%s" class="widefat" maxlength="24" placeholder="Small"></td>',
		esc_attr( isset( $size['label_en'] ) ? $size['label_en'] : '' )
	);
	printf(
		'<td><input type="number" name="ysf_size_price[]" value="%s" class="widefat" min="0" step="0.01"></td>',
		esc_attr( isset( $size['price'] ) ? $size['price'] : '' )
	);
	echo '<td><button type="button" class="button-link" data-ysf-size-remove>' . esc_html__( 'Sil', 'ysffoodlab' ) . '</button></td>';
	echo '</tr>';
}

/**
 * Meta değerlerini kaydeder.
 *
 * @param int     $post_id Gönderi kimliği.
 * @param WP_Post $post    Gönderi.
 */
function ysf_save_meta( $post_id, $post ) {
	if ( ! isset( $_POST['ysf_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ysf_meta_nonce'] ) ), 'ysf_save_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$groups = array();

	if ( 'ysf_menu_item' === $post->post_type ) {
		$groups[] = ysf_menu_item_fields();

		if ( isset( $_POST['ysf_tags_ready'] ) ) {
			$labels = isset( $_POST['ysf_tag_label'] ) ? wp_unslash( $_POST['ysf_tag_label'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$ens    = isset( $_POST['ysf_tag_label_en'] ) ? wp_unslash( $_POST['ysf_tag_label_en'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$kinds  = isset( $_POST['ysf_tag_type'] ) ? wp_unslash( $_POST['ysf_tag_type'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$rows   = array();

			foreach ( (array) $labels as $index => $label ) {
				$rows[] = array(
					'label'    => $label,
					'label_en' => isset( $ens[ $index ] ) ? $ens[ $index ] : '',
					'type'     => isset( $kinds[ $index ] ) ? $kinds[ $index ] : 'info',
				);
			}

			ysf_save_item_tags( $post_id, $rows );
		}

		if ( isset( $_POST['ysf_sizes_ready'] ) ) {
			$labels = isset( $_POST['ysf_size_label'] ) ? wp_unslash( $_POST['ysf_size_label'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$ens    = isset( $_POST['ysf_size_label_en'] ) ? wp_unslash( $_POST['ysf_size_label_en'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$prices = isset( $_POST['ysf_size_price'] ) ? wp_unslash( $_POST['ysf_size_price'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$rows   = array();

			foreach ( (array) $labels as $index => $label ) {
				$rows[] = array(
					'label'    => $label,
					'label_en' => isset( $ens[ $index ] ) ? $ens[ $index ] : '',
					'price'    => isset( $prices[ $index ] ) ? $prices[ $index ] : 0,
				);
			}

			update_post_meta( $post_id, '_ysf_sizes', ysf_sanitize_sizes( $rows ) );
		}
	} elseif ( 'ysf_campaign' === $post->post_type ) {
		$groups[] = ysf_campaign_fields();
	} elseif ( in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
		$groups[] = ysf_translation_fields();
	}

	foreach ( $groups as $fields ) {
		foreach ( $fields as $key => $field ) {
			if ( 'checkbox' === $field['type'] ) {
				ysf_set_meta_flag( $post_id, $key, isset( $_POST[ $key ] ) );
				continue;
			}

			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized

			switch ( $field['type'] ) {
				case 'number':
					$clean = ysf_sanitize_meta_number( $raw );
					break;
				case 'url':
					$clean = esc_url_raw( (string) $raw );
					break;
				case 'editor':
					$clean = wp_kses_post( (string) $raw );
					break;
				case 'textarea':
					$clean = sanitize_textarea_field( (string) $raw );
					break;
				default:
					$clean = sanitize_text_field( (string) $raw );
			}

			update_post_meta( $post_id, $key, $clean );
		}
	}
}
add_action( 'save_post', 'ysf_save_meta', 10, 2 );

/**
 * Rezervasyon / sipariş detaylarını salt okunur gösterir.
 *
 * @param WP_Post $post Gönderi.
 */
function ysf_render_request_box( $post ) {
	$rows = array(
		__( 'Ad Soyad', 'ysffoodlab' )      => get_post_meta( $post->ID, '_ysf_name', true ),
		__( 'Telefon', 'ysffoodlab' )       => get_post_meta( $post->ID, '_ysf_phone', true ),
		__( 'E-posta', 'ysffoodlab' )       => get_post_meta( $post->ID, '_ysf_email', true ),
		__( 'Tarih', 'ysffoodlab' )         => get_post_meta( $post->ID, '_ysf_date', true ),
		__( 'Saat', 'ysffoodlab' )          => get_post_meta( $post->ID, '_ysf_time', true ),
		__( 'Kişi sayısı', 'ysffoodlab' )   => get_post_meta( $post->ID, '_ysf_guests', true ),
		__( 'Özel gün', 'ysffoodlab' )      => get_post_meta( $post->ID, '_ysf_occasion', true ),
		__( 'Ek istekler', 'ysffoodlab' )   => get_post_meta( $post->ID, '_ysf_extras', true ),
		__( 'Sipariş tipi', 'ysffoodlab' )  => get_post_meta( $post->ID, '_ysf_order_type', true ),
		__( 'Adres', 'ysffoodlab' )         => get_post_meta( $post->ID, '_ysf_address', true ),
		__( 'Masa no', 'ysffoodlab' )       => get_post_meta( $post->ID, '_ysf_table', true ),
		__( 'Ara toplam', 'ysffoodlab' )    => get_post_meta( $post->ID, '_ysf_subtotal', true ),
		__( 'Kampanya indirimi', 'ysffoodlab' ) => get_post_meta( $post->ID, '_ysf_discount', true ),
		__( 'Teslimat ücreti', 'ysffoodlab' ) => get_post_meta( $post->ID, '_ysf_delivery_fee', true ),
		__( 'Toplam', 'ysffoodlab' )        => get_post_meta( $post->ID, '_ysf_total', true ),
		__( 'Not', 'ysffoodlab' )           => get_post_meta( $post->ID, '_ysf_note', true ),
	);

	echo '<table class="widefat striped"><tbody>';
	foreach ( $rows as $label => $value ) {
		if ( '' === $value || null === $value ) {
			continue;
		}
		printf(
			'<tr><th style="width:180px">%1$s</th><td>%2$s</td></tr>',
			esc_html( $label ),
			esc_html( (string) $value )
		);
	}
	echo '</tbody></table>';

	$items = get_post_meta( $post->ID, '_ysf_items', true );
	if ( ! empty( $items ) && is_array( $items ) ) {
		echo '<h3>' . esc_html__( 'Sipariş Kalemleri', 'ysffoodlab' ) . '</h3>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Ürün', 'ysffoodlab' ) . '</th>';
		echo '<th>' . esc_html__( 'Adet', 'ysffoodlab' ) . '</th>';
		echo '<th>' . esc_html__( 'Birim', 'ysffoodlab' ) . '</th>';
		echo '<th>' . esc_html__( 'Tutar', 'ysffoodlab' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $items as $item ) {
			printf(
				'<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td><td>%4$s</td></tr>',
				esc_html( isset( $item['name'] ) ? $item['name'] : '' ),
				esc_html( isset( $item['qty'] ) ? $item['qty'] : '' ),
				esc_html( ysf_price( isset( $item['price'] ) ? (float) $item['price'] : 0 ) ),
				esc_html( ysf_price( ( isset( $item['price'] ) ? (float) $item['price'] : 0 ) * ( isset( $item['qty'] ) ? (int) $item['qty'] : 0 ) ) )
			);
		}
		echo '</tbody></table>';
	}

	wp_nonce_field( 'ysf_save_state', 'ysf_state_nonce' );

	$state  = get_post_meta( $post->ID, '_ysf_state', true );
	$states = ysf_request_states();
	$state  = isset( $states[ $state ] ) ? $state : 'pending';

	echo '<p class="ysf-admin-note">';
	printf( '<label for="ysf-state"><strong>%s</strong></label> ', esc_html__( 'Durum', 'ysffoodlab' ) );
	echo '<select id="ysf-state" name="_ysf_state">';
	foreach ( $states as $key => $label ) {
		printf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $key ),
			selected( $state, $key, false ),
			esc_html( $label )
		);
	}
	echo '</select>';
	printf( ' <small>%s</small>', esc_html__( 'Değişikliği kaydetmek için “Güncelle” butonuna basın.', 'ysffoodlab' ) );
	echo '</p>';
}

/**
 * Talep durumunu kaydeder.
 *
 * @param int $post_id Gönderi kimliği.
 */
function ysf_save_request_state( $post_id ) {
	if ( ! isset( $_POST['ysf_state_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ysf_state_nonce'] ) ), 'ysf_save_state' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['_ysf_state'] ) ) {
		return;
	}

	$state  = sanitize_key( wp_unslash( $_POST['_ysf_state'] ) );
	$states = ysf_request_states();

	if ( isset( $states[ $state ] ) ) {
		update_post_meta( $post_id, '_ysf_state', $state );
	}
}
add_action( 'save_post', 'ysf_save_request_state' );
