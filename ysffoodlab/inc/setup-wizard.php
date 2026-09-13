<?php
/**
 * Tek tıkla kurulum: sayfalar, menüler, kategoriler ve örnek içerik.
 *
 * Yönetim panelinde "YSF Kurulum" sayfasından çalıştırılır. Var olan
 * kayıtları tekrar oluşturmaz, güvenle yeniden çalıştırılabilir.
 *
 * @package ysffoodlab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kurulacak sayfaların tanımı.
 *
 * @return array
 */
function ysf_wizard_pages() {
	return array(
		'ana-sayfa'      => array(
			'title'    => 'Ana Sayfa',
			'title_en' => 'Home',
			'template' => '',
			'front'    => true,
			'content'  => '',
		),
		'menu'           => array(
			'title'    => 'Menü',
			'title_en' => 'Menu',
			'template' => 'template-menu.php',
			'content'  => '',
		),
		'online-siparis' => array(
			'title'    => 'Online Sipariş',
			'title_en' => 'Order Online',
			'template' => 'template-order.php',
			'content'  => '',
		),
		'rezervasyon'    => array(
			'title'    => 'Rezervasyon',
			'title_en' => 'Reservations',
			'template' => 'template-reservation.php',
			'content'  => '',
		),
		'hakkimizda'     => array(
			'title'    => 'Hakkımızda',
			'title_en' => 'About Us',
			'template' => '',
			'content'  => "<!-- wp:paragraph -->\n<p>YSF Food Lab, mevsimin en iyi ürünleriyle çalışan bir mutfak ve kahve atölyesidir. Her tabağı kendi mutfağımızda, günlük hazırlanan soslar ve taze fırınlanmış ekmeklerle kuruyoruz.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h2>Mutfak yaklaşımımız</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Tedarikçilerimizi tek tek seçiyor, etimizi ve sebzemizi yerel üreticiden alıyoruz. Menümüz mevsime göre değişir; bu yüzden bazı tabaklar yılın belirli dönemlerinde masaya gelir.</p>\n<!-- /wp:paragraph -->",
		),
		'iletisim'       => array(
			'title'    => 'İletişim',
			'title_en' => 'Contact',
			'template' => 'template-contact.php',
			'content'  => '',
		),
		'blog'           => array(
			'title'    => 'Blog',
			'title_en' => 'Blog',
			'template' => '',
			'posts'    => true,
			'content'  => '',
		),
	);
}

/**
 * Örnek menü kategorileri.
 *
 * @return array
 */
function ysf_wizard_categories() {
	return array(
		'baslangiclar'   => array( 'Başlangıçlar', 'Starters' ),
		'ana-yemekler'   => array( 'Ana Yemekler', 'Main Courses' ),
		'burger-sandvic' => array( 'Burger & Sandviç', 'Burgers & Sandwiches' ),
		'salatalar'      => array( 'Salatalar', 'Salads' ),
		'tatlilar'       => array( 'Tatlılar', 'Desserts' ),
		'kahve-icecek'   => array( 'Kahve & İçecek', 'Coffee & Drinks' ),
	);
}

/**
 * Örnek menü ürünleri.
 *
 * @return array
 */
function ysf_wizard_menu_items() {
	return array(
		array(
			'title'    => 'Ev Yapımı Humus',
			'title_en' => 'House Hummus',
			'excerpt'  => 'Nohut, tahin, limon ve zeytinyağı; yanında sıcak lavaş.',
			'excerpt_en' => 'Chickpeas, tahini, lemon and olive oil, served with warm flatbread.',
			'price'    => 145,
			'cat'      => 'baslangiclar',
			'flags'    => array( '_ysf_vegan' => 1 ),
		),
		array(
			'title'    => 'Fırınlanmış Karnabahar',
			'title_en' => 'Roasted Cauliflower',
			'excerpt'  => 'Tahin sos, nar ekşisi, kavrulmuş badem.',
			'excerpt_en' => 'Tahini sauce, pomegranate molasses and toasted almonds.',
			'price'    => 165,
			'cat'      => 'baslangiclar',
			'flags'    => array( '_ysf_vegetarian' => 1, '_ysf_glutenfree' => 1 ),
		),
		array(
			'title'    => 'Acılı Karides Güveç',
			'title_en' => 'Spicy Shrimp Casserole',
			'excerpt'  => 'Tereyağı, sarımsak, kiraz domates ve pul biber.',
			'excerpt_en' => 'Butter, garlic, cherry tomatoes and chilli flakes.',
			'price'    => 285,
			'cat'      => 'baslangiclar',
			'flags'    => array( '_ysf_spicy' => 1 ),
		),
		array(
			'title'    => 'Kuzu İncik',
			'title_en' => 'Slow-Cooked Lamb Shank',
			'excerpt'  => '6 saat ağır ateşte pişmiş incik, kereviz püresi ve kendi suyu.',
			'excerpt_en' => 'Six-hour braised shank with celeriac purée and its own jus.',
			'price'    => 585,
			'cat'      => 'ana-yemekler',
			'badge'    => 'Şefin seçimi',
			'badge_en' => 'Chef’s pick',
			'featured' => 1,
		),
		array(
			'title'    => 'Antrikot (300 gr)',
			'title_en' => 'Ribeye Steak (300 g)',
			'excerpt'  => 'Dinlendirilmiş dana antrikot, kemikli patates, biberiyeli tereyağı.',
			'excerpt_en' => 'Aged ribeye with wedge potatoes and rosemary butter.',
			'price'    => 720,
			'cat'      => 'ana-yemekler',
			'featured' => 1,
		),
		array(
			'title'    => 'Tereyağlı Levrek',
			'title_en' => 'Sea Bass in Brown Butter',
			'excerpt'  => 'Fırında levrek fileto, kapari, limon ve mevsim yeşillikleri.',
			'excerpt_en' => 'Oven-baked sea bass fillet with capers, lemon and seasonal greens.',
			'price'    => 465,
			'cat'      => 'ana-yemekler',
			'flags'    => array( '_ysf_glutenfree' => 1 ),
		),
		array(
			'title'    => 'Mantarlı Kremalı Tagliatelle',
			'title_en' => 'Creamy Mushroom Tagliatelle',
			'excerpt'  => 'Ev yapımı makarna, istiridye mantarı, parmesan.',
			'excerpt_en' => 'House-made pasta with oyster mushrooms and parmesan.',
			'price'    => 325,
			'cat'      => 'ana-yemekler',
			'flags'    => array( '_ysf_vegetarian' => 1 ),
		),
		array(
			'title'    => 'YSF Signature Burger',
			'title_en' => 'YSF Signature Burger',
			'excerpt'  => '180 gr dana köfte, cheddar, karamelize soğan, özel sos.',
			'excerpt_en' => '180 g beef patty, cheddar, caramelised onion and our house sauce.',
			'price'    => 295,
			'cat'      => 'burger-sandvic',
			'badge'    => 'En çok satan',
			'badge_en' => 'Best seller',
			'featured' => 1,
		),
		array(
			'title'    => 'Acılı Tavuk Burger',
			'title_en' => 'Hot Chicken Burger',
			'excerpt'  => 'Çıtır tavuk göğsü, acı bal, turşu, brioche ekmek.',
			'excerpt_en' => 'Crispy chicken breast, hot honey, pickles, brioche bun.',
			'price'    => 265,
			'cat'      => 'burger-sandvic',
			'flags'    => array( '_ysf_spicy' => 1 ),
		),
		array(
			'title'    => 'Falafel Dürüm',
			'title_en' => 'Falafel Wrap',
			'excerpt'  => 'Nohut falafel, tahin sos, turşu ve taze otlar.',
			'excerpt_en' => 'Chickpea falafel with tahini, pickles and fresh herbs.',
			'price'    => 215,
			'cat'      => 'burger-sandvic',
			'flags'    => array( '_ysf_vegan' => 1 ),
		),
		array(
			'title'    => 'Sezar Salata',
			'title_en' => 'Caesar Salad',
			'excerpt'  => 'Marul, ızgara tavuk, parmesan, ev yapımı kruton.',
			'excerpt_en' => 'Romaine, grilled chicken, parmesan and house croutons.',
			'price'    => 245,
			'cat'      => 'salatalar',
		),
		array(
			'title'    => 'Kinoa & Avokado',
			'title_en' => 'Quinoa & Avocado Bowl',
			'excerpt'  => 'Kinoa, avokado, nar, ceviz ve limonlu sos.',
			'excerpt_en' => 'Quinoa, avocado, pomegranate, walnuts and lemon dressing.',
			'price'    => 235,
			'cat'      => 'salatalar',
			'flags'    => array( '_ysf_vegan' => 1, '_ysf_glutenfree' => 1 ),
		),
		array(
			'title'    => 'San Sebastian Cheesecake',
			'title_en' => 'San Sebastian Cheesecake',
			'excerpt'  => 'Yanık yüzeyli, akışkan dokulu klasik.',
			'excerpt_en' => 'The classic burnt-top, molten-centre cheesecake.',
			'price'    => 155,
			'cat'      => 'tatlilar',
			'featured' => 1,
		),
		array(
			'title'    => 'Fındıklı Brownie',
			'title_en' => 'Hazelnut Brownie',
			'excerpt'  => 'Sıcak brownie, vanilyalı dondurma.',
			'excerpt_en' => 'Warm brownie with vanilla ice cream.',
			'price'    => 145,
			'cat'      => 'tatlilar',
			'allergens' => 'gluten, süt, fındık',
		),
		array(
			'title'    => 'Filtre Kahve',
			'title_en' => 'Filter Coffee',
			'excerpt'  => 'Haftanın tek origin çekirdeği, V60.',
			'excerpt_en' => 'Single origin of the week, brewed on V60.',
			'price'    => 85,
			'cat'      => 'kahve-icecek',
		),
		array(
			'title'    => 'Flat White',
			'title_en' => 'Flat White',
			'excerpt'  => 'Çift shot espresso, kadifemsi süt.',
			'excerpt_en' => 'Double espresso with velvety milk.',
			'price'    => 95,
			'cat'      => 'kahve-icecek',
		),
		array(
			'title'    => 'Ev Yapımı Limonata',
			'title_en' => 'Homemade Lemonade',
			'excerpt'  => 'Limon, nane, az şeker.',
			'excerpt_en' => 'Lemon, mint and just a little sugar.',
			'price'    => 75,
			'cat'      => 'kahve-icecek',
			'flags'    => array( '_ysf_vegan' => 1, '_ysf_glutenfree' => 1 ),
		),
	);
}

/**
 * Örnek kampanya ve duyurular.
 *
 * @return array
 */
function ysf_wizard_campaigns() {
	return array(
		array(
			'title'      => 'Hafta içi öğle menüsü 295 ₺',
			'title_en'   => 'Weekday lunch menu for 295 ₺',
			'excerpt'    => 'Pazartesi–Cuma 12:00–16:00 arası çorba + ana yemek + filtre kahve.',
			'excerpt_en' => 'Monday to Friday, 12:00–16:00: soup, a main course and filter coffee.',
			'badge'      => 'Öğle fırsatı',
			'badge_en'   => 'Lunch deal',
			'type'       => 'kampanya',
			'bar'        => 1,
			'days'       => 45,
		),
		array(
			'title'      => 'İki kişilik akşam yemeği menüsü',
			'title_en'   => 'Dinner menu for two',
			'excerpt'    => 'Başlangıç, iki ana yemek ve tatlı; özel günler için ideal.',
			'excerpt_en' => 'A starter, two mains and dessert — ideal for special occasions.',
			'badge'      => 'Çift menü',
			'badge_en'   => 'For two',
			'type'       => 'kampanya',
			'days'       => 60,
		),
		array(
			'title'      => 'Doğum günü masası hazırlıyoruz',
			'title_en'   => 'We set the table for birthdays',
			'excerpt'    => 'Rezervasyon formundan “doğum günü” seçin; pasta ve süsleme bizden.',
			'excerpt_en' => 'Pick “birthday” in the reservation form and we will handle cake and decoration.',
			'badge'      => 'Duyuru',
			'badge_en'   => 'Notice',
			'type'       => 'duyuru',
			'bar'        => 1,
			'days'       => 120,
		),
	);
}

/**
 * Başlığa göre kayıt arar (get_page_by_title yerine, sürüm uyumlu).
 *
 * @param string $title     Başlık.
 * @param string $post_type İçerik tipi.
 * @return int Bulunan kimlik veya 0.
 */
function ysf_find_post_by_title( $title, $post_type ) {
	$found = get_posts(
		array(
			'post_type'              => $post_type,
			'title'                  => $title,
			'post_status'            => 'any',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	return ! empty( $found ) ? (int) $found[0] : 0;
}

/**
 * Kurulum sayfasını menüye ekler.
 */
function ysf_wizard_menu() {
	add_theme_page(
		__( 'YSF Kurulum', 'ysffoodlab' ),
		__( 'YSF Kurulum', 'ysffoodlab' ),
		'edit_theme_options',
		'ysf-setup',
		'ysf_wizard_page'
	);
}
add_action( 'admin_menu', 'ysf_wizard_menu' );

/**
 * Kurulum sayfası arayüzü.
 */
function ysf_wizard_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$done    = false;
	$report  = array();
	$samples = false;

	if ( isset( $_POST['ysf_wizard_run'] ) && check_admin_referer( 'ysf_wizard' ) ) {
		$samples = ! empty( $_POST['ysf_with_samples'] );
		$report  = ysf_wizard_run( $samples );
		$done    = true;
	}

	$menu_url = admin_url( 'edit.php?post_type=ysf_menu_item' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'YSF Food Lab — Site Kurulumu', 'ysffoodlab' ); ?></h1>

		<?php if ( $done ) : ?>
			<div class="notice notice-success"><p><strong><?php esc_html_e( 'Kurulum tamamlandı.', 'ysffoodlab' ); ?></strong></p></div>
			<?php if ( ! empty( $report ) ) : ?>
				<ul class="ysf-admin-note">
					<?php foreach ( $report as $line ) : ?>
						<li><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
					<?php esc_html_e( 'Siteyi görüntüle', 'ysffoodlab' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">
					<?php esc_html_e( 'Telefon / adres bilgilerini gir', 'ysffoodlab' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( $menu_url ); ?>">
					<?php esc_html_e( 'Menüyü düzenle', 'ysffoodlab' ); ?>
				</a>
			</p>
		<?php endif; ?>

		<div class="ysf-admin-note">
			<h2><?php esc_html_e( 'Bu düğme ne yapıyor?', 'ysffoodlab' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Ana Sayfa, Menü, Online Sipariş, Rezervasyon, Hakkımızda, İletişim ve Blog sayfalarını oluşturur.', 'ysffoodlab' ); ?></li>
				<li><?php esc_html_e( 'Ana sayfa ve blog sayfasını WordPress ayarlarına bağlar.', 'ysffoodlab' ); ?></li>
				<li><?php esc_html_e( 'Üst menüyü ve alt bilgi menüsünü kurar.', 'ysffoodlab' ); ?></li>
				<li><?php esc_html_e( 'Menü kategorilerini (başlangıç, ana yemek, tatlı…) açar.', 'ysffoodlab' ); ?></li>
				<li><?php esc_html_e( 'İsteğe bağlı: örnek menü ürünleri ve kampanyalar ekler; üzerine yazarak kendi ürünlerinizi girebilirsiniz.', 'ysffoodlab' ); ?></li>
			</ol>
			<p><?php esc_html_e( 'Butona birden fazla kez basmak güvenlidir; var olan kayıtlar tekrar oluşturulmaz.', 'ysffoodlab' ); ?></p>
		</div>

		<form method="post">
			<?php wp_nonce_field( 'ysf_wizard' ); ?>
			<p>
				<label>
					<input type="checkbox" name="ysf_with_samples" value="1" checked>
					<?php esc_html_e( 'Örnek menü ürünlerini ve kampanyaları da ekle (önerilir)', 'ysffoodlab' ); ?>
				</label>
			</p>
			<p>
				<button type="submit" name="ysf_wizard_run" value="1" class="button button-primary button-hero">
					<?php esc_html_e( 'Kurulumu Başlat', 'ysffoodlab' ); ?>
				</button>
			</p>
		</form>

		<h2><?php esc_html_e( 'Kurulumdan sonra yapılacaklar', 'ysffoodlab' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Görünüm > Özelleştir > YSF Food Lab Ayarları bölümünden telefon, WhatsApp, adres ve çalışma saatlerini girin.', 'ysffoodlab' ); ?></li>
			<li><?php esc_html_e( 'Menü Yönetimi bölümünden ürün fotoğraflarını ve gerçek fiyatları girin.', 'ysffoodlab' ); ?></li>
			<li><?php esc_html_e( 'Ayarlar > Kalıcı Bağlantılar sayfasını bir kez kaydedin (bağlantı yapısının yenilenmesi için).', 'ysffoodlab' ); ?></li>
		</ol>
	</div>
	<?php
}

/**
 * Kurulumu çalıştırır.
 *
 * @param bool $with_samples Örnek içerik eklenecek mi.
 * @return array Rapor satırları.
 */
function ysf_wizard_run( $with_samples = true ) {
	$report = array();

	// 1. Sayfalar.
	$page_ids = array();

	foreach ( ysf_wizard_pages() as $slug => $page ) {
		$existing = get_page_by_path( $slug );

		if ( $existing ) {
			$page_ids[ $slug ] = $existing->ID;
			continue;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_content' => $page['content'],
			)
		);

		if ( ! $id || is_wp_error( $id ) ) {
			continue;
		}

		$page_ids[ $slug ] = $id;

		if ( ! empty( $page['template'] ) ) {
			update_post_meta( $id, '_wp_page_template', $page['template'] );
		}

		if ( ! empty( $page['title_en'] ) ) {
			update_post_meta( $id, '_ysf_title_en', $page['title_en'] );
		}

		/* translators: %s: sayfa başlığı. */
		$report[] = sprintf( __( '“%s” sayfası oluşturuldu.', 'ysffoodlab' ), $page['title'] );
	}

	// 2. Ana sayfa ve blog sayfası ayarı.
	if ( isset( $page_ids['ana-sayfa'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_ids['ana-sayfa'] );
	}

	if ( isset( $page_ids['blog'] ) ) {
		update_option( 'page_for_posts', $page_ids['blog'] );
	}

	// 3. Menü kategorileri.
	$term_ids = array();

	foreach ( ysf_wizard_categories() as $slug => $names ) {
		$term = get_term_by( 'slug', $slug, 'ysf_menu_cat' );

		if ( $term ) {
			$term_ids[ $slug ] = $term->term_id;
			continue;
		}

		$created = wp_insert_term( $names[0], 'ysf_menu_cat', array( 'slug' => $slug ) );

		if ( is_wp_error( $created ) ) {
			continue;
		}

		$term_ids[ $slug ] = $created['term_id'];
		update_term_meta( $created['term_id'], '_ysf_name_en', $names[1] );
	}

	if ( $term_ids ) {
		$report[] = __( 'Menü kategorileri hazır.', 'ysffoodlab' );
	}

	// 4. Örnek içerik.
	if ( $with_samples ) {
		$created_items = 0;
		$order         = 0;

		foreach ( ysf_wizard_menu_items() as $item ) {
			++$order;
			if ( ysf_find_post_by_title( $item['title'], 'ysf_menu_item' ) ) {
				continue;
			}

			$id = wp_insert_post(
				array(
					'post_type'    => 'ysf_menu_item',
					'post_status'  => 'publish',
					'post_title'   => $item['title'],
					'post_excerpt' => $item['excerpt'],
					'menu_order'   => $order,
				)
			);

			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}

			++$created_items;

			update_post_meta( $id, '_ysf_price', (float) $item['price'] );
			update_post_meta( $id, '_ysf_title_en', $item['title_en'] );
			update_post_meta( $id, '_ysf_excerpt_en', $item['excerpt_en'] );
			update_post_meta( $id, '_ysf_orderable', 1 );

			if ( ! empty( $item['badge'] ) ) {
				update_post_meta( $id, '_ysf_badge', $item['badge'] );
			}

			if ( ! empty( $item['badge_en'] ) ) {
				update_post_meta( $id, '_ysf_badge_en', $item['badge_en'] );
			}

			if ( ! empty( $item['featured'] ) ) {
				update_post_meta( $id, '_ysf_featured', 1 );
			}

			if ( ! empty( $item['allergens'] ) ) {
				update_post_meta( $id, '_ysf_allergens', $item['allergens'] );
			}

			if ( ! empty( $item['flags'] ) ) {
				foreach ( $item['flags'] as $flag => $value ) {
					update_post_meta( $id, $flag, $value );
				}
			}

			if ( isset( $term_ids[ $item['cat'] ] ) ) {
				wp_set_object_terms( $id, array( (int) $term_ids[ $item['cat'] ] ), 'ysf_menu_cat' );
			}
		}

		if ( $created_items ) {
			/* translators: %d: ürün sayısı. */
			$report[] = sprintf( __( '%d örnek menü ürünü eklendi.', 'ysffoodlab' ), $created_items );
		}

		$created_campaigns = 0;

		foreach ( ysf_wizard_campaigns() as $campaign ) {
			if ( ysf_find_post_by_title( $campaign['title'], 'ysf_campaign' ) ) {
				continue;
			}

			$id = wp_insert_post(
				array(
					'post_type'    => 'ysf_campaign',
					'post_status'  => 'publish',
					'post_title'   => $campaign['title'],
					'post_excerpt' => $campaign['excerpt'],
				)
			);

			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}

			++$created_campaigns;

			update_post_meta( $id, '_ysf_title_en', $campaign['title_en'] );
			update_post_meta( $id, '_ysf_excerpt_en', $campaign['excerpt_en'] );
			update_post_meta( $id, '_ysf_badge', $campaign['badge'] );
			update_post_meta( $id, '_ysf_badge_en', $campaign['badge_en'] );
			update_post_meta( $id, '_ysf_type', $campaign['type'] );
			update_post_meta( $id, '_ysf_start', current_time( 'Y-m-d' ) );
			update_post_meta( $id, '_ysf_end', gmdate( 'Y-m-d', current_time( 'timestamp' ) + ( (int) $campaign['days'] * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

			if ( ! empty( $campaign['bar'] ) ) {
				update_post_meta( $id, '_ysf_show_in_bar', 1 );
			}

			if ( isset( $page_ids['online-siparis'] ) ) {
				update_post_meta( $id, '_ysf_link', get_permalink( $page_ids['online-siparis'] ) );
			}
		}

		if ( $created_campaigns ) {
			/* translators: %d: kampanya sayısı. */
			$report[] = sprintf( __( '%d örnek kampanya/duyuru eklendi.', 'ysffoodlab' ), $created_campaigns );
		}
	}

	// 5. Navigasyon menüleri.
	ysf_wizard_build_menus( $page_ids, $report );

	// 6. Varsayılan ayarlar.
	if ( ! get_theme_mod( 'ysf_hero_text' ) ) {
		set_theme_mod( 'ysf_hero_text', 'Mevsimin en iyi ürünleriyle hazırlanan tabaklar, günlük kavrulmuş kahve ve size ayrılmış sıcak bir masa.' );
	}

	if ( ! get_theme_mod( 'ysf_hero_text_en' ) ) {
		set_theme_mod( 'ysf_hero_text_en', 'Seasonal plates, freshly roasted coffee and a warm table saved just for you.' );
	}

	if ( ! get_theme_mod( 'ysf_about_text' ) ) {
		set_theme_mod( 'ysf_about_text', 'YSF Food Lab bir mutfak atölyesi: tarifleri kendi mutfağımızda geliştiriyor, soslarımızı ve ekmeklerimizi her gün yeniden hazırlıyoruz.' );
	}

	if ( ! get_theme_mod( 'ysf_about_text_en' ) ) {
		set_theme_mod( 'ysf_about_text_en', 'YSF Food Lab is a kitchen workshop: we develop our recipes in house and make our sauces and breads fresh every day.' );
	}

	$facts = array(
		1 => array( '2019', 'Açılış yılı', 'Opened in' ),
		2 => array( '48', 'Kişilik salon', 'Seats inside' ),
		3 => array( '%100', 'Günlük hazırlık', 'Made daily' ),
		4 => array( '4.8', 'Misafir puanı', 'Guest rating' ),
	);

	foreach ( $facts as $i => $fact ) {
		if ( ! get_theme_mod( 'ysf_fact_' . $i . '_num' ) ) {
			set_theme_mod( 'ysf_fact_' . $i . '_num', $fact[0] );
			set_theme_mod( 'ysf_fact_' . $i . '_label', $fact[1] );
			set_theme_mod( 'ysf_fact_' . $i . '_label_en', $fact[2] );
		}
	}

	if ( ! get_option( 'blogdescription' ) || 'Just another WordPress site' === get_option( 'blogdescription' ) ) {
		update_option( 'blogdescription', 'Kitchen & Coffee — taze malzeme, günlük hazırlık' );
	}

	// 7. Kalıcı bağlantıları yenile.
	flush_rewrite_rules();

	$report[] = __( 'Bağlantı yapısı yenilendi.', 'ysffoodlab' );

	return $report;
}

/**
 * Üst ve alt menüleri kurar.
 *
 * @param array $page_ids Sayfa kimlikleri.
 * @param array $report   Rapor (referans).
 */
function ysf_wizard_build_menus( $page_ids, &$report ) {
	$menus = array(
		'primary'   => array(
			'name'  => 'Ana Menü',
			'items' => array( 'ana-sayfa', 'menu', 'online-siparis', 'rezervasyon', 'hakkimizda', 'iletisim' ),
		),
		'secondary' => array(
			'name'  => 'Alt Bilgi Menüsü',
			'items' => array( 'menu', 'rezervasyon', 'blog', 'iletisim' ),
		),
	);

	$locations = get_theme_mod( 'nav_menu_locations', array() );

	foreach ( $menus as $location => $config ) {
		$menu = wp_get_nav_menu_object( $config['name'] );

		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( $config['name'] );

			if ( is_wp_error( $menu_id ) ) {
				continue;
			}

			foreach ( $config['items'] as $slug ) {
				if ( ! isset( $page_ids[ $slug ] ) ) {
					continue;
				}

				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $page_ids[ $slug ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}

			/* translators: %s: menü adı. */
			$report[] = sprintf( __( '“%s” oluşturuldu.', 'ysffoodlab' ), $config['name'] );
		} else {
			$menu_id = $menu->term_id;
		}

		$locations[ $location ] = $menu_id;
	}

	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Tema ilk etkinleştirildiğinde kurulum sayfasına yönlendirir.
 */
function ysf_after_switch_theme() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	set_transient( 'ysf_activation_notice', 1, 5 * MINUTE_IN_SECONDS );
}
add_action( 'after_switch_theme', 'ysf_after_switch_theme' );

/**
 * Etkinleştirme sonrası hatırlatma.
 */
function ysf_activation_notice() {
	if ( ! get_transient( 'ysf_activation_notice' ) || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong><?php esc_html_e( 'YSF Food Lab teması etkin.', 'ysffoodlab' ); ?></strong>
			<?php esc_html_e( 'Sayfaları, menüleri ve örnek içeriği tek tıkla oluşturmak için kurulum sihirbazını çalıştırın.', 'ysffoodlab' ); ?>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'themes.php?page=ysf-setup' ) ); ?>">
				<?php esc_html_e( 'Kurulumu aç', 'ysffoodlab' ); ?>
			</a>
		</p>
	</div>
	<?php
	delete_transient( 'ysf_activation_notice' );
}
add_action( 'admin_notices', 'ysf_activation_notice' );
