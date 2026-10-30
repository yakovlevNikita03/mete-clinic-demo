<?php
/**
 * Russian names and descriptions of core fields on the plugin's screens.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replaces core interface strings on the plugin's own screens.
 *
 * Админка рассчитана на сотрудника клиники, поэтому названия полей и
 * заголовки столбцов должны быть на русском. Строки самого плагина уже
 * русские, а подписи ядра (Name, Slug, Description, Title, Date) приходят
 * из перевода WordPress. На сайте с нерусской локалью они подменяются здесь.
 *
 * Меню WordPress и блочный редактор не затрагиваются: это общий интерфейс,
 * он переводится выбором языка сайта. При уже установленном русском пакете
 * фильтры не подключаются, чтобы не спорить с настоящим переводом.
 */
class MCC_Admin_Text {

	/**
	 * Actions that render plugin rows without a screen.
	 */
	const AJAX_ACTIONS = array( 'inline-save', 'inline-save-tax', 'add-tag' );

	/**
	 * Whether the filters are already attached.
	 *
	 * @var bool
	 */
	private static $attached = false;

	/**
	 * Cached table of strings without context.
	 *
	 * @var array|null
	 */
	private static $map = null;

	/**
	 * Cached table of strings with context.
	 *
	 * @var array|null
	 */
	private static $context_map = null;

	/**
	 * Cached table of plural templates.
	 *
	 * @var array|null
	 */
	private static $plural_map = null;

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::is_plugin_ajax() ) {
			self::attach();
		}

		add_action( 'current_screen', array( __CLASS__, 'attach_on_screen' ) );
	}

	/**
	 * Attach the filters on a plugin screen.
	 *
	 * @param WP_Screen $screen Current screen.
	 * @return void
	 */
	public static function attach_on_screen( $screen ) {
		if ( ! $screen instanceof WP_Screen || ! self::is_plugin_screen( $screen ) ) {
			return;
		}

		self::attach();
	}

	/**
	 * Whether the screen shows plugin data.
	 *
	 * @param WP_Screen $screen Screen.
	 * @return bool
	 */
	public static function is_plugin_screen( WP_Screen $screen ) {
		$base = (string) $screen->base;

		if ( MCC_Post_Types::TAX === (string) $screen->taxonomy
			&& in_array( $base, array( 'edit-tags', 'term' ), true ) ) {
			return true;
		}

		return 'edit' === $base
			&& in_array( (string) $screen->post_type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true );
	}

	/**
	 * Whether the current request is a row-rendering AJAX call.
	 *
	 * @return bool
	 */
	public static function is_plugin_ajax() {
		if ( ! wp_doing_ajax() ) {
			return false;
		}

		// Only reads request data to choose the translation table.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] )
			? sanitize_key( wp_unslash( $_REQUEST['action'] ) )
			: '';

		$taxonomy = isset( $_REQUEST['taxonomy'] ) && is_string( $_REQUEST['taxonomy'] )
			? sanitize_key( wp_unslash( $_REQUEST['taxonomy'] ) )
			: '';

		$post_type = isset( $_REQUEST['post_type'] ) && is_string( $_REQUEST['post_type'] )
			? sanitize_key( wp_unslash( $_REQUEST['post_type'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $action, self::AJAX_ACTIONS, true ) ) {
			return false;
		}

		if ( MCC_Post_Types::TAX === $taxonomy ) {
			return true;
		}

		return in_array( $post_type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true );
	}

	/**
	 * Whether the site is already Russian.
	 *
	 * @return bool
	 */
	public static function is_russian() {
		return 0 === strpos( (string) determine_locale(), 'ru_' );
	}

	/**
	 * Attach filters once.
	 *
	 * @return void
	 */
	private static function attach() {
		if ( self::$attached ) {
			return;
		}

		self::$attached = true;

		add_filter( 'gettext', array( __CLASS__, 'translate' ), 10, 3 );
		add_filter( 'gettext_with_context', array( __CLASS__, 'translate_with_context' ), 10, 4 );
		add_filter( 'ngettext', array( __CLASS__, 'translate_plural' ), 10, 5 );
		add_filter( 'ngettext_with_context', array( __CLASS__, 'translate_plural_with_context' ), 10, 6 );
	}

	/**
	 * Translate a plain core string.
	 *
	 * @param string $translation Existing translation.
	 * @param string $text        Original string.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function translate( $translation, $text, $domain ) {
		if ( 'default' !== $domain ) {
			return $translation;
		}

		$map = self::map();

		// Сначала дешёвый поиск в таблице: determine_locale() вызывается редко.
		if ( ! isset( $map[ $text ] ) || self::is_russian() ) {
			return $translation;
		}

		return $map[ $text ];
	}

	/**
	 * Translate a core string that has a context.
	 *
	 * @param string $translation Existing translation.
	 * @param string $text        Original string.
	 * @param string $context     Gettext context.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function translate_with_context( $translation, $text, $context, $domain ) {
		if ( 'default' !== $domain ) {
			return $translation;
		}

		$map = self::context_map();
		$key = $text . "\0" . $context;

		if ( ! isset( $map[ $key ] ) || self::is_russian() ) {
			return $translation;
		}

		return $map[ $key ];
	}

	/**
	 * Translate a core plural template.
	 *
	 * @param string $translation Existing translation.
	 * @param string $single      Singular form.
	 * @param string $plural      Plural form.
	 * @param int    $number      Count.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function translate_plural( $translation, $single, $plural, $number, $domain ) {
		if ( 'default' !== $domain ) {
			return $translation;
		}

		$map = self::plural_map();

		if ( ! isset( $map[ $single ] ) || self::is_russian() ) {
			return $translation;
		}

		return $map[ $single ];
	}

	/**
	 * Translate a core plural template that has a context.
	 *
	 * @param string $translation Existing translation.
	 * @param string $single      Singular form.
	 * @param string $plural      Plural form.
	 * @param int    $number      Count.
	 * @param string $context     Gettext context.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function translate_plural_with_context( $translation, $single, $plural, $number, $context, $domain ) {
		if ( 'default' !== $domain ) {
			return $translation;
		}

		// Шаблоны вкладок статусов ядро помечает контекстом «posts».
		if ( 'posts' !== $context ) {
			return $translation;
		}

		$map = self::plural_map();

		if ( ! isset( $map[ $single ] ) || self::is_russian() ) {
			return $translation;
		}

		return $map[ $single ];
	}

	/**
	 * Strings without context.
	 *
	 * Значения записаны литералами: вызов __() внутри фильтра gettext
	 * запустил бы сам себя.
	 *
	 * Таблица собирается один раз и кэшируется в статическом свойстве.
	 *
	 * @return array
	 */
	private static function map() {
		if ( null === self::$map ) {
			self::$map = array(
				'Slug'               => 'Слаг',
				'Description'        => 'Описание',
				'None'               => 'Нет',
				'Title'              => 'Название',
				'Date'               => 'Дата',
				'Status'             => 'Статус',
				'Order'              => 'Порядок',
				'Password'           => 'Пароль',
				'Private'            => 'Личное',
				'Published'          => 'Опубликовано',
				'Draft'              => 'Черновик',
				'Pending Review'     => 'На проверке',
				'Update'             => 'Обновить',
				'Cancel'             => 'Отмена',
				'Delete'             => 'Удалить',
				'Edit'               => 'Изменить',
				'Quick Edit'         => 'Быстрое редактирование',
				'Bulk Edit'          => 'Массовое редактирование',
				'Trash'              => 'В корзину',
				'View'               => 'Смотреть',
				'Month'              => 'Месяц',
				'Day'                => 'День',
				'Year'               => 'Год',
				'Hour'               => 'Час',
				'Minute'             => 'Минута',
				'Show more details'  => 'Показать подробности',
				'Select bulk action' => 'Выберите действие',
				'Bulk actions'       => 'Действия',
				'All dates'          => 'Все даты',
				'Filter'             => 'Фильтр',
				'Apply'              => 'Применить',

				// В ядре эти строки содержат HTML-сущности.
				'Quick&nbsp;Edit'                   => 'Быстрое&nbsp;редактирование',
				'Quick edit &#8220;%s&#8221; inline' => 'Быстрое редактирование «%s»',
				'No description'                    => 'Без описания',
			);
		}

		return self::$map;
	}

	/**
	 * Strings with context, keyed as "text\0context".
	 *
	 * Таблица собирается один раз и кэшируется в статическом свойстве.
	 *
	 * @return array
	 */
	private static function context_map() {
		if ( null === self::$context_map ) {
			self::$context_map = array(
				"Name\0term name"              => 'Название',
				"Count\0Number/count of items" => 'Услуг',
				"Title\0column name"           => 'Название',
				"Trash\0verb"                  => 'В корзину',
			);
		}

		return self::$context_map;
	}

	/**
	 * Plural templates of the post status links, keyed by the singular form.
	 *
	 * @return array
	 */
	private static function plural_map() {
		if ( null === self::$plural_map ) {
			self::$plural_map = array(
				'All <span class="count">(%s)</span>'       => 'Все <span class="count">(%s)</span>',
				'Mine <span class="count">(%s)</span>'      => 'Мои <span class="count">(%s)</span>',
				'Published <span class="count">(%s)</span>' => 'Опубликовано <span class="count">(%s)</span>',
				'Trash <span class="count">(%s)</span>'     => 'Корзина <span class="count">(%s)</span>',
			);
		}

		return self::$plural_map;
	}
}
