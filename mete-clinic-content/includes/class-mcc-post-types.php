<?php
/**
 * Custom post types and taxonomy.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers services, specialists and the service category taxonomy.
 */
class MCC_Post_Types {

	/**
	 * Service post type.
	 */
	const SERVICE = 'mete_service';

	/**
	 * Specialist post type.
	 */
	const SPECIALIST = 'mete_specialist';

	/**
	 * Service category taxonomy.
	 */
	const TAX = 'mete_service_cat';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );

		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );

		add_filter( 'manage_' . self::SERVICE . '_posts_columns', array( __CLASS__, 'service_columns' ) );
		add_action( 'manage_' . self::SERVICE . '_posts_custom_column', array( __CLASS__, 'service_column' ), 10, 2 );

		add_filter( 'manage_' . self::SPECIALIST . '_posts_columns', array( __CLASS__, 'specialist_columns' ) );
		add_action( 'manage_' . self::SPECIALIST . '_posts_custom_column', array( __CLASS__, 'specialist_column' ), 10, 2 );
	}

	/**
	 * Register post types and taxonomy.
	 *
	 * @return void
	 */
	public static function register() {
		register_post_type(
			self::SERVICE,
			array(
				'labels'             => array(
					'name'               => __( 'Услуги', 'mete-clinic-content' ),
					'singular_name'      => __( 'Услуга', 'mete-clinic-content' ),
					'menu_name'          => __( 'Услуги', 'mete-clinic-content' ),
					'name_admin_bar'     => __( 'Услуга', 'mete-clinic-content' ),
					'add_new'            => __( 'Добавить услугу', 'mete-clinic-content' ),
					'add_new_item'       => __( 'Новая услуга', 'mete-clinic-content' ),
					'edit_item'          => __( 'Редактировать услугу', 'mete-clinic-content' ),
					'new_item'           => __( 'Новая услуга', 'mete-clinic-content' ),
					'view_item'          => __( 'Смотреть услугу', 'mete-clinic-content' ),
					'view_items'         => __( 'Смотреть услуги', 'mete-clinic-content' ),
					'search_items'       => __( 'Искать услуги', 'mete-clinic-content' ),
					'not_found'          => __( 'Услуги не найдены', 'mete-clinic-content' ),
					'not_found_in_trash' => __( 'В корзине услуг нет', 'mete-clinic-content' ),
					'all_items'          => __( 'Все услуги', 'mete-clinic-content' ),
					'archives'           => __( 'Каталог услуг', 'mete-clinic-content' ),
					'attributes'         => __( 'Атрибуты услуги', 'mete-clinic-content' ),
				),
				'description'        => __( 'Каталог услуг клиники.', 'mete-clinic-content' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_nav_menus'  => true,
				'show_in_admin_bar'  => false,
				'show_in_rest'       => true,
				'has_archive'        => 'uslugi',
				'rewrite'            => array(
					'slug'       => 'uslugi',
					'with_front' => false,
				),
				'query_var'          => true,
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'menu_icon'          => 'dashicons-heart',
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
				'taxonomies'         => array( self::TAX ),
			)
		);

		register_post_type(
			self::SPECIALIST,
			array(
				'labels'             => array(
					'name'               => __( 'Специалисты', 'mete-clinic-content' ),
					'singular_name'      => __( 'Специалист', 'mete-clinic-content' ),
					'menu_name'          => __( 'Специалисты', 'mete-clinic-content' ),
					'name_admin_bar'     => __( 'Специалист', 'mete-clinic-content' ),
					'add_new'            => __( 'Добавить специалиста', 'mete-clinic-content' ),
					'add_new_item'       => __( 'Новый специалист', 'mete-clinic-content' ),
					'edit_item'          => __( 'Редактировать специалиста', 'mete-clinic-content' ),
					'new_item'           => __( 'Новый специалист', 'mete-clinic-content' ),
					'view_item'          => __( 'Смотреть специалиста', 'mete-clinic-content' ),
					'view_items'         => __( 'Смотреть специалистов', 'mete-clinic-content' ),
					'search_items'       => __( 'Искать специалистов', 'mete-clinic-content' ),
					'not_found'          => __( 'Специалисты не найдены', 'mete-clinic-content' ),
					'not_found_in_trash' => __( 'В корзине специалистов нет', 'mete-clinic-content' ),
					'all_items'          => __( 'Все специалисты', 'mete-clinic-content' ),
					'archives'           => __( 'Специалисты', 'mete-clinic-content' ),
					'attributes'         => __( 'Атрибуты специалиста', 'mete-clinic-content' ),
				),
				'description'        => __( 'Команда клиники.', 'mete-clinic-content' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'show_in_nav_menus'  => true,
				'show_in_admin_bar'  => false,
				'show_in_rest'       => true,
				'has_archive'        => 'specialisty',
				'rewrite'            => array(
					'slug'       => 'specialisty',
					'with_front' => false,
				),
				'query_var'          => true,
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
				'menu_icon'          => 'dashicons-groups',
				'supports'           => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			)
		);

		register_taxonomy(
			self::TAX,
			array( self::SERVICE ),
			array(
				'labels'            => array(
					'name'                     => __( 'Категории услуг', 'mete-clinic-content' ),
					'singular_name'            => __( 'Категория услуги', 'mete-clinic-content' ),
					'search_items'             => __( 'Искать категории', 'mete-clinic-content' ),
					'all_items'                => __( 'Все категории', 'mete-clinic-content' ),
					'parent_item'              => __( 'Родительская категория', 'mete-clinic-content' ),
					'parent_item_colon'        => __( 'Родительская категория:', 'mete-clinic-content' ),
					'edit_item'                => __( 'Редактировать категорию', 'mete-clinic-content' ),
					'update_item'              => __( 'Обновить категорию', 'mete-clinic-content' ),
					'add_new_item'             => __( 'Новая категория', 'mete-clinic-content' ),
					'new_item_name'            => __( 'Название категории', 'mete-clinic-content' ),
					'menu_name'                => __( 'Категории', 'mete-clinic-content' ),
					'name_field_description'   => __( 'Так категория называется на сайте и в прайсе.', 'mete-clinic-content' ),
					'slug_field_description'   => __( 'Часть адреса страницы: латиница, цифры и дефис. Если оставить пустым, создастся автоматически.', 'mete-clinic-content' ),
					'parent_field_description' => __( 'Необязательно. Нужно, только если категория вложена в другую.', 'mete-clinic-content' ),
					'desc_field_description'   => __( 'Короткий текст о направлении. Показывается на сайте, если он нужен теме.', 'mete-clinic-content' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => false,
				'query_var'         => true,
			)
		);
	}

	/**
	 * Placeholder for the title field.
	 *
	 * @param string  $text Current placeholder.
	 * @param WP_Post $post Current post.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		if ( $post instanceof WP_Post ) {
			if ( self::SPECIALIST === $post->post_type ) {
				return __( 'ФИО специалиста', 'mete-clinic-content' );
			}

			if ( self::SERVICE === $post->post_type ) {
				return __( 'Название услуги', 'mete-clinic-content' );
			}
		}

		return $text;
	}

	/**
	 * Service list columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function service_columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['mcc_price']      = __( 'Цена', 'mete-clinic-content' );
				$new['mcc_desc']       = __( 'Описание', 'mete-clinic-content' );
				$new['mcc_home']       = __( 'На главной', 'mete-clinic-content' );
				$new['mcc_home_order'] = __( 'Порядок на главной', 'mete-clinic-content' );
				$new['mcc_booking']    = __( 'Запись', 'mete-clinic-content' );
			}
		}

		return $new;
	}

	/**
	 * Service column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function service_column( $column, $post_id ) {
		if ( 'mcc_price' === $column ) {
			$price = MCC_Content::format_price( $post_id );
			echo esc_html( '' !== $price ? $price : '—' );

			$type       = (string) get_post_meta( $post_id, MCC_Fields::META_PRICE_TYPE, true );
			$price_raw  = (string) MCC_Sanitize::price( get_post_meta( $post_id, MCC_Fields::META_PRICE, true ) );
			$max_raw    = (string) MCC_Sanitize::price( get_post_meta( $post_id, MCC_Fields::META_PRICE_MAX, true ) );
			$duration   = (string) get_post_meta( $post_id, MCC_Fields::META_DURATION, true );
			$show_home  = '1' === (string) get_post_meta( $post_id, MCC_Fields::META_SHOW_HOME, true ) ? '1' : '';
			$home_order = (string) get_post_meta( $post_id, MCC_Fields::META_HOME_ORDER, true );

			printf(
				'<span hidden class="mcc-qe-data" data-type="%1$s" data-price="%2$s" data-price-max="%3$s" data-duration="%4$s" data-show-home="%5$s" data-home-order="%6$s"></span>',
				esc_attr( in_array( $type, array( 'fixed', 'from', 'range', 'free' ), true ) ? $type : 'fixed' ),
				esc_attr( $price_raw ),
				esc_attr( $max_raw ),
				esc_attr( preg_match( '/^[0-9]+$/', $duration ) ? $duration : '' ),
				esc_attr( $show_home ),
				esc_attr( '' !== $home_order ? $home_order : '0' )
			);

			return;
		}

		if ( 'mcc_desc' === $column ) {
			$post = get_post( $post_id );
			$text = $post instanceof WP_Post ? trim( wp_strip_all_tags( (string) $post->post_content ) ) : '';

			echo '' !== $text
				? '<span class="mcc-yes" aria-label="' . esc_attr__( 'Описание есть', 'mete-clinic-content' ) . '">✓</span>'
				: '<span class="mcc-no" aria-label="' . esc_attr__( 'Описания нет', 'mete-clinic-content' ) . '">—</span>';

			return;
		}

		if ( 'mcc_home' === $column ) {
			echo '1' === (string) get_post_meta( $post_id, MCC_Fields::META_SHOW_HOME, true )
				? esc_html__( 'Да', 'mete-clinic-content' )
				: esc_html__( 'Нет', 'mete-clinic-content' );
			return;
		}

		if ( 'mcc_home_order' === $column ) {
			$order = (string) get_post_meta( $post_id, MCC_Fields::META_HOME_ORDER, true );
			echo esc_html( '' !== $order ? $order : '0' );
			return;
		}

		if ( 'mcc_booking' === $column ) {
			$url = MCC_Sanitize::url( get_post_meta( $post_id, MCC_Fields::META_BOOKING_URL, true ) );
			echo '' !== $url
				? esc_html__( 'Своя ссылка', 'mete-clinic-content' )
				: esc_html__( 'Общая', 'mete-clinic-content' );
		}
	}

	/**
	 * Specialist list columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function specialist_columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['mcc_role']    = __( 'Специализация', 'mete-clinic-content' );
				$new['mcc_order']   = __( 'Порядок', 'mete-clinic-content' );
				$new['mcc_booking'] = __( 'Запись', 'mete-clinic-content' );
			}
		}

		return $new;
	}

	/**
	 * Specialist column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function specialist_column( $column, $post_id ) {
		if ( 'mcc_role' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, MCC_Fields::META_ROLE, true ) );
			return;
		}

		if ( 'mcc_order' === $column ) {
			$post = get_post( $post_id );
			echo esc_html( (string) ( $post instanceof WP_Post ? (int) $post->menu_order : 0 ) );
			return;
		}

		if ( 'mcc_booking' === $column ) {
			$url = MCC_Sanitize::url( get_post_meta( $post_id, MCC_Fields::META_BOOKING_URL, true ) );
			echo '' !== $url
				? esc_html__( 'Есть ссылка', 'mete-clinic-content' )
				: esc_html__( 'Нет', 'mete-clinic-content' );
		}
	}
}
