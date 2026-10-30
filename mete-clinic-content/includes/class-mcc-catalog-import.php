<?php
/**
 * Import of the full service catalog (categories and services).
 *
 * Данные лежат в mete-clinic-content/data/catalog.json и обновляются
 * сборкой tools/build-catalog.js в проекте.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates categories and services from the catalog file.
 *
 * Импорт идемпотентный: услуга ищется по служебному ключу
 * «слаг категории | название». Существующие записи не изменяются,
 * поэтому повторный запуск не затирает правки владельца.
 */
class MCC_Catalog_Import {

	/**
	 * Admin action.
	 */
	const ACTION = 'mcc_import_catalog';

	/**
	 * Meta key that links a service to the catalog entry.
	 */
	const KEY = '_mcc_import_key';

	/**
	 * Result transient prefix.
	 */
	const RESULT = 'mcc_catalog_import_result_';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle' ) );
	}

	/**
	 * Handle the admin form.
	 *
	 * @return void
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'Недостаточно прав для импорта каталога.', 'mete-clinic-content' ),
				'',
				array( 'response' => 403 )
			);
		}

		check_admin_referer( self::ACTION );

		$result = self::run();

		set_transient( self::RESULT . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );

		wp_safe_redirect(
			add_query_arg(
				'mcc-catalog',
				'1',
				admin_url( 'admin.php?page=' . MCC_Settings::TOOLS )
			)
		);
		exit;
	}

	/**
	 * Import categories and services.
	 *
	 * @return array Counts and errors.
	 */
	public static function run() {
		$result = array(
			'categories_created' => 0,
			'categories_exists'  => 0,
			'services_created'   => 0,
			'services_skipped'   => 0,
			'errors'             => array(),
		);

		$file = MCC_DIR . 'data/catalog.json';

		if ( ! file_exists( $file ) ) {
			$result['errors'][] = 'Не найден файл данных: data/catalog.json';

			return $result;
		}

		$raw  = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		$data = json_decode( (string) $raw, true );

		if ( ! is_array( $data ) ) {
			$result['errors'][] = 'Файл данных повреждён: JSON не читается.';

			return $result;
		}

		foreach ( $data as $category ) {
			$slug = isset( $category['slug'] ) ? sanitize_title( $category['slug'] ) : '';
			$name = isset( $category['name'] ) ? trim( (string) $category['name'] ) : '';

			if ( '' === $slug || '' === $name ) {
				$result['errors'][] = 'Пропущена категория без слага или названия.';
				continue;
			}

			$term    = term_exists( $slug, MCC_Post_Types::TAX );
			$term_id = 0;

			if ( $term ) {
				$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
				$result['categories_exists']++;
			} else {
				$created = wp_insert_term(
					$name,
					MCC_Post_Types::TAX,
					array(
						'slug'        => $slug,
						'description' => isset( $category['description'] ) ? (string) $category['description'] : '',
					)
				);

				if ( is_wp_error( $created ) ) {
					$result['errors'][] = 'Категория «' . $name . '»: ' . $created->get_error_message();
					continue;
				}

				$term_id = (int) $created['term_id'];
				$result['categories_created']++;
			}

			if ( $term_id && isset( $category['order'] ) ) {
				update_term_meta( $term_id, 'mcc_cat_order', (int) $category['order'] );
			}

			$services = isset( $category['services'] ) && is_array( $category['services'] ) ? $category['services'] : array();

			foreach ( $services as $service ) {
				$title = isset( $service['title'] ) ? trim( (string) $service['title'] ) : '';

				if ( '' === $title ) {
					$result['errors'][] = 'В категории «' . $name . '» услуга без названия.';
					continue;
				}

				$key = md5( $slug . '|' . $title );

				if ( self::service_exists( $key, $title ) ) {
					$result['services_skipped']++;
					continue;
				}

				$post_id = wp_insert_post(
					array(
						'post_type'    => MCC_Post_Types::SERVICE,
						'post_status'  => 'publish',
						'post_title'   => $title,
						'post_content' => isset( $service['description'] ) ? (string) $service['description'] : '',
						'post_excerpt' => '',
					),
					true
				);

				if ( is_wp_error( $post_id ) ) {
					$result['errors'][] = 'Услуга «' . $title . '»: ' . $post_id->get_error_message();
					continue;
				}

				$post_id = (int) $post_id;

				update_post_meta( $post_id, self::KEY, $key );
				update_post_meta( $post_id, MCC_Fields::META_PRICE_TYPE, self::price_type( $service ) );

				if ( 'free' !== self::price_type( $service ) && isset( $service['price'] ) ) {
					update_post_meta( $post_id, MCC_Fields::META_PRICE, (string) (float) $service['price'] );
				}

				if ( isset( $service['minutes'] ) ) {
					update_post_meta( $post_id, MCC_Fields::META_DURATION, (string) (int) $service['minutes'] );
				}

				if ( ! empty( $service['note'] ) ) {
					update_post_meta( $post_id, MCC_Fields::META_PRICE_NOTE, sanitize_text_field( (string) $service['note'] ) );
				}

				if ( $term_id ) {
					wp_set_object_terms( $post_id, array( $term_id ), MCC_Post_Types::TAX, false );
				}

				$result['services_created']++;
			}
		}

		return $result;
	}

	/**
	 * Normalized price type.
	 *
	 * @param array $service Service data.
	 * @return string
	 */
	private static function price_type( $service ) {
		$type = isset( $service['type'] ) ? sanitize_key( (string) $service['type'] ) : 'fixed';

		return in_array( $type, array( 'fixed', 'from', 'range', 'free' ), true ) ? $type : 'fixed';
	}

	/**
	 * Whether the service was already imported.
	 *
	 * @param string $key   Import key.
	 * @param string $title Service title.
	 * @return bool
	 */
	private static function service_exists( $key, $title ) {
		$by_key = get_posts(
			array(
				'post_type'        => MCC_Post_Types::SERVICE,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'meta_key'         => self::KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => true,
			)
		);

		if ( ! empty( $by_key ) ) {
			return true;
		}

		/* Запись, созданная вручную с тем же названием, тоже считается импортированной. */
		return (bool) get_posts(
			array(
				'post_type'        => MCC_Post_Types::SERVICE,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'title'            => $title,
				'suppress_filters' => true,
			)
		);
	}
}
