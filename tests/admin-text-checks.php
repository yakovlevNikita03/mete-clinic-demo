<?php
/**
 * MÉTE CLINIC — интеграционные проверки русских подписей в админке.
 *
 * Запуск на тестовом сайте (не на рабочем):
 *
 *   wp eval-file tests/admin-text-checks.php
 *
 * Скрипт проверяет подмену подписей ядра на экранах плагина, отказ от
 * подмены при русской локали, русские метки таксономии и заголовки
 * столбцов. Данные сайта не изменяются.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Запускайте через WP-CLI: wp eval-file tests/admin-text-checks.php\n" );
	exit( 1 );
}

if ( ! class_exists( 'MCC_Admin_Text' ) ) {
	fwrite( STDERR, "Плагин MÉTE CLINIC — Content не активирован.\n" );
	exit( 1 );
}

if ( ! function_exists( 'mete_check_say' ) ) {
	$GLOBALS['mete_check_count']    = 0;
	$GLOBALS['mete_check_failures'] = 0;

	/**
	 * Print a line.
	 *
	 * @param string $line Text.
	 * @return void
	 */
	function mete_check_say( $line ) {
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::log( $line );
			return;
		}

		echo $line . "\n";
	}

	/**
	 * Assert one condition.
	 *
	 * @param string $label   Check label.
	 * @param bool   $ok      Result.
	 * @param string $details Extra context on failure.
	 * @return void
	 */
	function mete_check_assert( $label, $ok, $details = '' ) {
		$GLOBALS['mete_check_count']++;

		if ( $ok ) {
			mete_check_say( 'OK   ' . $label );
			return;
		}

		$GLOBALS['mete_check_failures']++;
		mete_check_say( 'FAIL ' . $label . ( '' !== $details ? ' (' . $details . ')' : '' ) );
	}
}

require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';

mete_check_say( 'MÉTE CLINIC — проверки русских подписей в админке' );

/* Привязка к экранам: чужие экраны не затрагиваются ---------------------- */

$screen_post             = WP_Screen::get( 'edit' );
$screen_post->post_type  = 'post';

MCC_Admin_Text::attach_on_screen( $screen_post );

mete_check_assert( 'На экране записей ядра фильтры не подключаются', ! has_filter( 'gettext', array( 'MCC_Admin_Text', 'translate' ) ) );

$screen_service            = WP_Screen::get( 'edit' );
$screen_service->post_type = MCC_Post_Types::SERVICE;

mete_check_assert( 'Экран списка услуг определяется как экран плагина', MCC_Admin_Text::is_plugin_screen( $screen_service ) );

$screen_specialist            = WP_Screen::get( 'edit' );
$screen_specialist->post_type = MCC_Post_Types::SPECIALIST;

mete_check_assert( 'Экран специалистов определяется как экран плагина', MCC_Admin_Text::is_plugin_screen( $screen_specialist ) );

$screen_terms            = WP_Screen::get( 'edit-tags' );
$screen_terms->taxonomy  = MCC_Post_Types::TAX;

mete_check_assert( 'Экран категорий определяется как экран плагина', MCC_Admin_Text::is_plugin_screen( $screen_terms ) );

MCC_Admin_Text::attach_on_screen( $screen_terms );

mete_check_assert( 'На экране категорий фильтры подключаются', (bool) has_filter( 'gettext', array( 'MCC_Admin_Text', 'translate' ) ) );

/* Подмена строк ядра ----------------------------------------------------- */

mete_check_assert(
	'«Slug» переводится как «Слаг»',
	'Слаг' === MCC_Admin_Text::translate( 'Slug', 'Slug', 'default' ),
	MCC_Admin_Text::translate( 'Slug', 'Slug', 'default' )
);

mete_check_assert(
	'«Description» переводится как «Описание»',
	'Описание' === MCC_Admin_Text::translate( 'Description', 'Description', 'default' )
);

mete_check_assert(
	'«Update» переводится как «Обновить»',
	'Обновить' === MCC_Admin_Text::translate( 'Update', 'Update', 'default' )
);

mete_check_assert(
	'Строки плагина фильтр не трогает',
	'Слаг' === MCC_Admin_Text::translate( 'Слаг', 'Slug', 'mete-clinic-content' )
);

mete_check_assert(
	'Неизвестная строка возвращается без изменений',
	'Comments' === MCC_Admin_Text::translate( 'Comments', 'Comments', 'default' )
);

mete_check_assert(
	'Контекст «term name» переводится',
	'Название' === MCC_Admin_Text::translate_with_context( 'Name', 'Name', 'term name', 'default' )
);

mete_check_assert(
	'Контекст «Number/count of items» переводится',
	'Услуг' === MCC_Admin_Text::translate_with_context( 'Count', 'Count', 'Number/count of items', 'default' )
);

mete_check_assert(
	'Контекст «verb» у «Trash» переводится',
	'В корзину' === MCC_Admin_Text::translate_with_context( 'Trash', 'Trash', 'verb', 'default' )
);

mete_check_assert(
	'Кнопка «Filter» переводится',
	'Фильтр' === MCC_Admin_Text::translate( 'Filter', 'Filter', 'default' )
);

mete_check_assert(
	'Шаблон вкладки «All (N)» переводится',
	'Все <span class="count">(%s)</span>' === MCC_Admin_Text::translate_plural(
		'All <span class="count">(%s)</span>',
		'All <span class="count">(%s)</span>',
		'All <span class="count">(%s)</span>',
		153,
		'default'
	)
);

mete_check_assert(
	'Шаблон вкладки «Mine (N)» переводится с контекстом «posts»',
	'Мои <span class="count">(%s)</span>' === MCC_Admin_Text::translate_plural_with_context(
		'Mine <span class="count">(%s)</span>',
		'Mine <span class="count">(%s)</span>',
		'Mine <span class="count">(%s)</span>',
		3,
		'posts',
		'default'
	)
);

mete_check_assert(
	'Шаблон вкладки «Trash (N)» переводится',
	'Корзина <span class="count">(%s)</span>' === MCC_Admin_Text::translate_plural(
		'Trash <span class="count">(%s)</span>',
		'Trash <span class="count">(%s)</span>',
		'Trash <span class="count">(%s)</span>',
		2,
		'default'
	)
);

mete_check_assert(
	'Шаблон с чужим контекстом не трогается',
	'Other <span class="count">(%s)</span>' === MCC_Admin_Text::translate_plural_with_context(
		'Other <span class="count">(%s)</span>',
		'Other <span class="count">(%s)</span>',
		'Other <span class="count">(%s)</span>',
		3,
		'comments',
		'default'
	)
);

/* Русская локаль: подмена не нужна --------------------------------------- */

/*
 * Языкового пакета ru_RU на тестовом сайте нет, поэтому switch_to_locale()
 * не сработает. Локаль подставляется фильтром — так проверяется именно
 * условие внутри класса.
 */
function mete_text_ru_locale() {
	return 'ru_RU';
}

add_filter( 'determine_locale', 'mete_text_ru_locale' );

mete_check_assert( 'Локаль подменена на ru_RU', 0 === strpos( determine_locale(), 'ru_' ) );
mete_check_assert( 'При локали ru_RU «Slug» не подменяется', 'Slug' === MCC_Admin_Text::translate( 'Slug', 'Slug', 'default' ) );
mete_check_assert( 'При локали ru_RU контекстные строки не подменяются', 'Name' === MCC_Admin_Text::translate_with_context( 'Name', 'Name', 'term name', 'default' ) );
mete_check_assert(
	'При локали ru_RU шаблоны вкладок не подменяются',
	'All <span class="count">(%s)</span>' === MCC_Admin_Text::translate_plural(
		'All <span class="count">(%s)</span>',
		'All <span class="count">(%s)</span>',
		'All <span class="count">(%s)</span>',
		153,
		'default'
	)
);

remove_filter( 'determine_locale', 'mete_text_ru_locale' );

mete_check_assert( 'Локаль возвращена', 0 !== strpos( determine_locale(), 'ru_' ) );

/* Столбцы и метки таксономии --------------------------------------------- */

$columns = MCC_Term_Fields::columns(
	array(
		'cb'          => '<input type="checkbox" />',
		'name'        => 'Name',
		'description' => 'Description',
		'slug'        => 'Slug',
		'posts'       => 'Count',
	)
);

mete_check_assert( 'Столбец «Название»', 'Название' === $columns['name'], isset( $columns['name'] ) ? $columns['name'] : 'нет' );
mete_check_assert( 'Столбец «Описание»', 'Описание' === $columns['description'] );
mete_check_assert( 'Столбец «Слаг»', 'Слаг' === $columns['slug'] );
mete_check_assert( 'Столбец «Услуг»', 'Услуг' === $columns['posts'] );
mete_check_assert( 'Столбцы плагина на месте', isset( $columns['mcc_order'], $columns['mcc_image'] ) );
mete_check_assert( 'Столбец «Порядок» подписан по-русски', 'Порядок' === $columns['mcc_order'] );

$taxonomy = get_taxonomy( MCC_Post_Types::TAX );

mete_check_assert( 'Таксономия зарегистрирована', (bool) $taxonomy );

if ( $taxonomy ) {
	$descriptions = array(
		'name_field_description'   => 'подпись к полю «Название»',
		'slug_field_description'   => 'подпись к полю «Слаг»',
		'parent_field_description' => 'подпись к полю «Родительская»',
		'desc_field_description'   => 'подпись к полю «Описание»',
	);

	foreach ( $descriptions as $key => $label ) {
		$value = isset( $taxonomy->labels->$key ) ? (string) $taxonomy->labels->$key : '';

		mete_check_assert(
			'Есть ' . $label,
			'' !== $value && preg_match( '/[А-Яа-яЁё]/u', $value ),
			$value
		);
	}
}

/* Быстрое редактирование: лишние поля скрыты ----------------------------- */

$css = (string) file_get_contents( MCC_DIR . 'assets/css/admin.css' );

mete_check_assert( 'В CSS скрыта дата в быстром редактировании услуг', false !== strpos( $css, '.post-type-mete_service .inline-edit-col-left .inline-edit-date' ) );
mete_check_assert( 'В CSS скрыта дата в быстром редактировании специалистов', false !== strpos( $css, '.post-type-mete_specialist .inline-edit-col-left .inline-edit-date' ) );
mete_check_assert( 'В CSS скрыты пароль и «Личное»', false !== strpos( $css, '.post-type-mete_service .inline-edit-col-left .inline-edit-group' ) );

/* AJAX-запросы без экрана ------------------------------------------------ */

if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', true );
}

$_REQUEST = array(
	'action'    => 'inline-save',
	'post_type' => MCC_Post_Types::SERVICE,
);

mete_check_assert( 'Быстрое редактирование услуги определяется как AJAX плагина', MCC_Admin_Text::is_plugin_ajax() );

$_REQUEST = array(
	'action'   => 'inline-save-tax',
	'taxonomy' => MCC_Post_Types::TAX,
);

mete_check_assert( 'Быстрое редактирование категории определяется как AJAX плагина', MCC_Admin_Text::is_plugin_ajax() );

$_REQUEST = array(
	'action'    => 'inline-save',
	'post_type' => 'post',
);

mete_check_assert( 'Чужие AJAX-запросы не затрагиваются', ! MCC_Admin_Text::is_plugin_ajax() );

$_REQUEST = array( 'action' => 'heartbeat' );

mete_check_assert( 'Прочие AJAX-запросы не затрагиваются', ! MCC_Admin_Text::is_plugin_ajax() );

$_REQUEST = array();

mete_check_say( '' );
mete_check_say( sprintf( 'Проверок: %d, ошибок: %d', (int) $GLOBALS['mete_check_count'], (int) $GLOBALS['mete_check_failures'] ) );

if ( (int) $GLOBALS['mete_check_failures'] > 0 && ! defined( 'MCC_CHECKS_COMBINED' ) ) {
	exit( 1 );
}
