<?php
/**
 * Admin styles and scripts for every plugin screen.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues one shared style and script and localizes their strings.
 *
 * Раньше стили подключались в трёх местах и каждая страница передавала
 * свой объект mccAdmin. Теперь точка одна: так проще не потерять строки
 * и не перезаписать чужие настройки.
 */
class MCC_Admin_Assets {

	/**
	 * Script handle.
	 */
	const HANDLE = 'mcc-admin';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Enqueue on plugin screens only.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function enqueue( $hook ) {
		unset( $hook );

		$screen = get_current_screen();

		if ( ! $screen instanceof WP_Screen || ! self::is_plugin_screen( $screen ) ) {
			return;
		}

		wp_enqueue_style( 'mcc-admin', MCC_URL . 'assets/css/admin.css', array(), MCC_VERSION );

		$deps = array( 'jquery', 'jquery-ui-sortable' );

		wp_enqueue_script( self::HANDLE, MCC_URL . 'assets/js/admin.js', $deps, MCC_VERSION, true );

		if ( MCC_Post_Types::TAX === (string) $screen->taxonomy || self::is_settings_page() ) {
			wp_enqueue_media();
		}

		wp_localize_script( self::HANDLE, 'mccAdmin', self::config( $screen ) );
	}

	/**
	 * Whether the screen belongs to the plugin.
	 *
	 * @param WP_Screen $screen Screen.
	 * @return bool
	 */
	private static function is_plugin_screen( WP_Screen $screen ) {
		$pages = array( MCC_Dashboard::PAGE, MCC_Settings::PAGE, MCC_Settings::TOOLS, MCC_Price_IO::PAGE );

		if ( in_array( (string) $screen->id, array( 'toplevel_page_' . MCC_Dashboard::PAGE ), true ) ) {
			return true;
		}

		if ( isset( $_GET['page'] ) && in_array( sanitize_key( wp_unslash( $_GET['page'] ) ), $pages, true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		if ( in_array( (string) $screen->post_type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true )
			&& in_array( (string) $screen->base, array( 'post', 'edit' ), true ) ) {
			return true;
		}

		if ( MCC_Post_Types::TAX === (string) $screen->taxonomy
			&& in_array( (string) $screen->base, array( 'edit-tags', 'term' ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the current screen is the plugin settings page.
	 *
	 * Нужен, чтобы подключить медиатеку для выбора изображения карточки
	 * направления на вкладке «Главная».
	 *
	 * @return bool
	 */
	private static function is_settings_page() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}

		return MCC_Settings::PAGE === sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Localized configuration.
	 *
	 * @param WP_Screen $screen Screen.
	 * @return array
	 */
	private static function config( WP_Screen $screen ) {
		$sort_type  = '';
		$sort_field = 'menu_order';

		if ( 'edit' === $screen->base && in_array( (string) $screen->post_type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true ) ) {
			$type = (string) $screen->post_type;

			/*
			 * У услуг порядок в списке — это порядок каталога, а на главной
			 * используется отдельное поле. Перетаскивание включаем только
			 * для отфильтрованного списка «На главной» и для специалистов,
			 * чтобы не перенумеровывать весь каталог по одной странице.
			 */
			if ( MCC_Post_Types::SPECIALIST === $type ) {
				$sort_type  = $type;
				$sort_field = 'menu_order';
			} elseif ( self::home_filter_active() ) {
				$sort_type  = $type;
				$sort_field = 'home_order';
			}
		}

		return array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'mcc_admin' ),
			'reorderNonce' => wp_create_nonce( MCC_Admin_List::REORDER ),
			'categories'  => self::categories( $screen ),
			'priceLabels' => array(
				'fixed' => __( 'Цена, ₽', 'mete-clinic-content' ),
				'from'  => __( 'Цена «от», ₽', 'mete-clinic-content' ),
				'range' => __( 'Цена «от», ₽', 'mete-clinic-content' ),
				'free'  => __( 'Цена, ₽', 'mete-clinic-content' ),
			),
			'priceHints'  => array(
				'fixed' => __( 'Одно значение: 2290 или 2290,50. Пустое поле — цена по запросу.', 'mete-clinic-content' ),
				'from'  => __( 'Минимальная цена. На сайте выводится как «от 2 290 ₽».', 'mete-clinic-content' ),
				'range' => __( 'Минимальная цена диапазона.', 'mete-clinic-content' ),
				'free'  => __( 'На сайте выводится «Бесплатно». Поля цены не нужны.', 'mete-clinic-content' ),
			),
			'mediaTitle'  => __( 'Изображение категории', 'mete-clinic-content' ),
			'mediaButton' => __( 'Использовать изображение', 'mete-clinic-content' ),
			'repeater'    => array(
				'home_directions' => __( 'карточку', 'mete-clinic-content' ),
				'home_categories' => __( 'категорию', 'mete-clinic-content' ),
			),
			'strings'     => array(
				'checking'     => __( 'Проверяем…', 'mete-clinic-content' ),
				'emptyUrl'     => __( 'Сначала заполните ссылку.', 'mete-clinic-content' ),
				'linkOk'       => __( 'Ссылка отвечает.', 'mete-clinic-content' ),
				'linkFail'     => __( 'Ссылка не отвечает.', 'mete-clinic-content' ),
				'linkBlocked'  => __( 'Этот адрес нельзя проверить с сервера.', 'mete-clinic-content' ),
				'sortSaved'    => __( 'Порядок сохранён.', 'mete-clinic-content' ),
				'sortFailed'   => __( 'Не удалось сохранить порядок.', 'mete-clinic-content' ),
				'sortHandle'   => __( 'Перетащите, чтобы изменить порядок.', 'mete-clinic-content' ),
				'bulkCategory' => __( 'Категория', 'mete-clinic-content' ),
				'chooseCat'    => __( 'Выберите категорию для массового изменения.', 'mete-clinic-content' ),
			),
			'sort'        => array(
				'postType' => $sort_type,
				'field'    => $sort_field,
			),
			'isList'      => 'edit' === $screen->base,
		);
	}

	/**
	 * Service categories for the bulk action select.
	 *
	 * @param WP_Screen $screen Screen.
	 * @return array
	 */
	private static function categories( WP_Screen $screen ) {
		if ( 'edit' !== $screen->base || MCC_Post_Types::SERVICE !== (string) $screen->post_type ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => MCC_Post_Types::TAX,
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		$items = array();

		foreach ( $terms as $term ) {
			$items[] = array(
				'id'   => (int) $term->term_id,
				'name' => (string) $term->name,
			);
		}

		return $items;
	}

	/**
	 * Whether the "На главной" filter is active.
	 *
	 * @return bool
	 */
	private static function home_filter_active() {
		return isset( $_GET['mcc_filter'] ) && 'home' === sanitize_key( wp_unslash( $_GET['mcc_filter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
