<?php
/**
 * Initial content import from the approved static site.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the starting categories, services and specialists.
 */
class MCC_Seeder {

	/**
	 * Import flag option.
	 */
	const FLAG = 'mcc_seeded';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_mcc_seed_demo', array( __CLASS__, 'handle_manual' ) );
	}

	/**
	 * Activation: register types, import content, refresh permalinks.
	 *
	 * @return void
	 */
	public static function activate() {
		MCC_Post_Types::register();
		self::seed();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation: refresh permalinks.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Import once per installation.
	 *
	 * @return void
	 */
	public static function seed() {
		if ( get_option( self::FLAG ) ) {
			return;
		}

		self::import();

		update_option( self::FLAG, 1 );
	}

	/**
	 * Manual import from the settings screen.
	 *
	 * @return void
	 */
	public static function handle_manual() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Недостаточно прав для импорта данных.', 'mete-clinic-content' ),
				'',
				array( 'response' => 403 )
			);
		}

		check_admin_referer( 'mcc_seed_demo' );

		self::import();
		update_option( self::FLAG, 1 );

		wp_safe_redirect(
			add_query_arg(
				'mcc-imported',
				'1',
				admin_url( 'admin.php?page=' . MCC_Settings::TOOLS )
			)
		);
		exit;
	}

	/**
	 * Create missing categories, services and specialists.
	 *
	 * @return void
	 */
	public static function import() {
		$terms = self::import_categories();

		self::import_services( $terms );
		self::import_specialists();
	}

	/**
	 * Categories (service directions).
	 *
	 * @return array Map of slug to term ID.
	 */
	private static function import_categories() {
		$categories = array(
			'lazernaya-epilyaciya'          => array(
				'name'        => 'Лазерная эпиляция',
				'description' => 'Женские и мужские зоны, комплексы и отдельные участки.',
				'order'       => 1,
			),
			'kosmetologiya-lica'            => array(
				'name'        => 'Косметология лица',
				'description' => 'Чистки, пилинги, уходовые и аппаратные процедуры.',
				'order'       => 2,
			),
			'apparatnaya-korrekciya-figury' => array(
				'name'        => 'Аппаратная коррекция фигуры',
				'description' => 'ЛПЖ-массаж и аппаратные методики по прайсу клиники.',
				'order'       => 3,
			),
			'inekczionnaya-kosmetologiya'   => array(
				'name'        => 'Инъекционная косметология',
				'description' => 'Контурная пластика, ботулинотерапия, мезотерапия и другие процедуры.',
				'order'       => 4,
			),
		);

		$map = array();

		foreach ( $categories as $slug => $data ) {
			$existing = get_term_by( 'slug', $slug, MCC_Post_Types::TAX );

			if ( $existing instanceof WP_Term ) {
				$map[ $slug ] = (int) $existing->term_id;
				continue;
			}

			$created = wp_insert_term(
				$data['name'],
				MCC_Post_Types::TAX,
				array(
					'slug'        => $slug,
					'description' => $data['description'],
				)
			);

			if ( is_wp_error( $created ) ) {
				continue;
			}

			$term_id          = (int) $created['term_id'];
			$map[ $slug ]     = $term_id;

			update_term_meta( $term_id, 'mcc_cat_order', (int) $data['order'] );
		}

		return $map;
	}

	/**
	 * Services from the approved price list.
	 *
	 * @param array $terms Category map.
	 * @return void
	 */
	private static function import_services( $terms ) {
		$services = array(
			array(
				'title'    => 'Женский комплекс «Лайт № 1»',
				'category' => 'lazernaya-epilyaciya',
				'excerpt'  => 'подмышки + глубокое бикини',
				'type'     => 'fixed',
				'price'    => '2290',
				'order'    => 1,
			),
			array(
				'title'    => 'Женский комплекс «Лайт № 2»',
				'category' => 'lazernaya-epilyaciya',
				'excerpt'  => 'подмышки + ноги до колен',
				'type'     => 'fixed',
				'price'    => '2490',
				'order'    => 2,
			),
			array(
				'title'    => 'Женский комплекс «Стандарт»',
				'category' => 'lazernaya-epilyaciya',
				'excerpt'  => '',
				'type'     => 'fixed',
				'price'    => '3990',
				'order'    => 3,
			),
			array(
				'title'    => 'Мужской комплекс «Контур бороды»',
				'category' => 'lazernaya-epilyaciya',
				'excerpt'  => '',
				'type'     => 'fixed',
				'price'    => '3000',
				'order'    => 4,
			),
			array(
				'title'    => 'ЛПЖ-массаж (1 зона)',
				'category' => 'apparatnaya-korrekciya-figury',
				'excerpt'  => '',
				'type'     => 'fixed',
				'price'    => '1790',
				'order'    => 5,
			),
			array(
				'title'    => 'Ультразвуковая чистка лица',
				'category' => 'kosmetologiya-lica',
				'excerpt'  => '',
				'type'     => 'fixed',
				'price'    => '3500',
				'order'    => 6,
			),
			array(
				'title'    => 'Пилинги из каталога',
				'category' => 'kosmetologiya-lica',
				'excerpt'  => '',
				'type'     => 'from',
				'price'    => '2500',
				'order'    => 7,
			),
			array(
				'title'    => 'Первичный приём врача дерматолога-косметолога',
				'category' => 'kosmetologiya-lica',
				'excerpt'  => '',
				'type'     => 'fixed',
				'price'    => '3500',
				'order'    => 8,
			),
		);

		foreach ( $services as $service ) {
			if ( self::find_post_by_title( $service['title'], MCC_Post_Types::SERVICE ) > 0 ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => MCC_Post_Types::SERVICE,
					'post_status'  => 'publish',
					'post_title'   => $service['title'],
					'post_excerpt' => $service['excerpt'],
					'post_content' => '',
					'menu_order'   => (int) $service['order'],
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$term_id = isset( $terms[ $service['category'] ] ) ? (int) $terms[ $service['category'] ] : 0;

			if ( $term_id > 0 ) {
				wp_set_object_terms( $post_id, array( $term_id ), MCC_Post_Types::TAX );
			}

			update_post_meta( $post_id, MCC_Fields::META_PRICE_TYPE, $service['type'] );
			update_post_meta( $post_id, MCC_Fields::META_PRICE, $service['price'] );
			update_post_meta( $post_id, MCC_Fields::META_SHOW_HOME, '1' );
			update_post_meta( $post_id, MCC_Fields::META_HOME_ORDER, (int) $service['order'] );
		}
	}

	/**
	 * Specialists with verified names, roles and Yclients links.
	 *
	 * Образование и опыт не заполняются: подтверждённых данных нет.
	 *
	 * @return void
	 */
	private static function import_specialists() {
		$specialists = array(
			array(
				'name'    => 'Жарая Анна Сергеевна',
				'role'    => 'Врач дерматолог-косметолог',
				'booking' => 'https://n1801245.yclients.ru/company/1599669/personal/select-master/master-info/1599669/5948106?o=m5569812',
				'order'   => 1,
			),
			array(
				'name'    => 'Махмутова Дамира Дамировна',
				'role'    => 'Косметолог',
				'booking' => 'https://n1801245.yclients.ru/company/1599669/personal/select-master/master-info/1599669/5569812?o=m5569812',
				'order'   => 2,
			),
			array(
				'name'    => 'Сотникова Ксения',
				'role'    => 'Специалист лазерной эпиляции',
				'booking' => 'https://n1801245.yclients.ru/company/1599669/personal/select-master/master-info/1599669/4775574?o=m5569812',
				'order'   => 3,
			),
			array(
				'name'    => 'Яковлева Екатерина',
				'role'    => 'Специалист лазерной эпиляции и аппаратной косметологии',
				'booking' => 'https://n1801245.yclients.ru/company/1599669/personal/select-master/master-info/1599669/5716740?o=m5569812',
				'order'   => 4,
			),
		);

		foreach ( $specialists as $specialist ) {
			if ( self::find_post_by_title( $specialist['name'], MCC_Post_Types::SPECIALIST ) > 0 ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => MCC_Post_Types::SPECIALIST,
					'post_status'  => 'publish',
					'post_title'   => $specialist['name'],
					'post_content' => '',
					'menu_order'   => (int) $specialist['order'],
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, MCC_Fields::META_ROLE, $specialist['role'] );
			update_post_meta( $post_id, MCC_Fields::META_BOOKING_URL, $specialist['booking'] );
		}
	}

	/**
	 * Find an existing post by exact title.
	 *
	 * @param string $title     Post title.
	 * @param string $post_type Post type.
	 * @return int Post ID or 0.
	 */
	private static function find_post_by_title( $title, $post_type ) {
		$query = new WP_Query(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'title'            => $title,
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		if ( empty( $query->posts ) ) {
			return 0;
		}

		return (int) $query->posts[0];
	}
}
