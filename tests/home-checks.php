<?php
/**
 * MÉTE CLINIC — интеграционные проверки главной страницы 1.4.0.
 *
 * Запуск на тестовом сайте (не на рабочем):
 *
 *   wp eval-file tests/home-checks.php
 *
 * Скрипт проверяет настройки карточек направлений и списка категорий,
 * санитизацию, публичный API плагина и вывод обоих блоков на главной.
 * Исходное значение mcc_settings восстанавливается в конце.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Запускайте через WP-CLI: wp eval-file tests/home-checks.php\n" );
	exit( 1 );
}

if ( ! class_exists( 'MCC_Settings' ) ) {
	fwrite( STDERR, "Плагин MÉTE CLINIC — Content не активирован.\n" );
	exit( 1 );
}

if ( ! function_exists( 'mete_home_check_say' ) ) {
	$GLOBALS['mete_home_check_count']    = 0;
	$GLOBALS['mete_home_check_failures'] = 0;

	/**
	 * Print a line.
	 *
	 * @param string $line Text.
	 * @return void
	 */
	function mete_home_check_say( $line ) {
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
	function mete_home_check_assert( $label, $ok, $details = '' ) {
		$GLOBALS['mete_home_check_count']++;

		if ( $ok ) {
			mete_home_check_say( 'OK   ' . $label );
			return;
		}

		$GLOBALS['mete_home_check_failures']++;
		mete_home_check_say( 'FAIL ' . $label . ( '' !== $details ? ' (' . $details . ')' : '' ) );
	}
}

/**
 * Save settings for one check.
 *
 * @param array $overrides Values to override.
 * @return void
 */
function mete_home_check_set( $overrides = array() ) {
	update_option( MCC_Settings::OPTION, wp_parse_args( $overrides, MCC_Settings::defaults() ) );
}

/**
 * Render one theme template part.
 *
 * @param string $name Part name without extension.
 * @return string
 */
function mete_home_check_render( $name ) {
	$path = get_template_directory() . '/template-parts/' . $name . '.php';

	if ( ! file_exists( $path ) ) {
		return '';
	}

	ob_start();
	include $path;

	return (string) ob_get_clean();
}

mete_home_check_say( 'MÉTE CLINIC — проверки главной страницы' );

$original_settings = get_option( MCC_Settings::OPTION, null );

$consult = get_term_by( 'slug', 'konsultaciya', MCC_Post_Types::TAX );
$empty   = get_term_by( 'slug', 'inekczionnaya-kosmetologiya', MCC_Post_Types::TAX );
$service = get_posts(
	array(
		'post_type'      => MCC_Post_Types::SERVICE,
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'post_status'    => 'any',
	)
);

$service_id = ! empty( $service ) ? (int) $service[0] : 0;
$attachment = 0;

try {
	/* Значения по умолчанию ---------------------------------------------- */

	$defaults = MCC_Settings::defaults();

	mete_home_check_assert(
		'По умолчанию три карточки направлений',
		3 === count( $defaults['home_directions'] ),
		(string) count( $defaults['home_directions'] )
	);

	mete_home_check_assert(
		'По умолчанию пять категорий',
		5 === count( $defaults['home_categories'] ),
		(string) count( $defaults['home_categories'] )
	);

	mete_home_check_assert(
		'Категории по умолчанию идут в заданном порядке',
		array(
			'konsultaciya',
			'epilyaciya-zhenskie-kompleksy',
			'apparatnaya-korrekciya-figury',
			'apparatnoe-omolozhenie-lica',
			'kosmetologiya-chistki-i-pilingi',
		) === wp_list_pluck( $defaults['home_categories'], 'slug' )
	);

	mete_home_check_assert(
		'Иллюстрации по умолчанию: лазер, лицо, инъекции',
		array( 'laser', 'face', 'injection' ) === wp_list_pluck( $defaults['home_directions'], 'icon' )
	);

	mete_home_check_assert(
		'У категорий по умолчанию нет жёстких term_id',
		array( 0, 0, 0, 0, 0 ) === wp_list_pluck( $defaults['home_categories'], 'term_id' )
	);

	mete_home_check_set();

	/* Публичный API плагина ---------------------------------------------- */

	$api_directions = mete_content_get_home_directions();

	mete_home_check_assert( 'API отдаёт три карточки', 3 === count( $api_directions ) );
	mete_home_check_assert(
		'API отдаёт заголовок первой карточки',
		'Лазерная эпиляция' === $api_directions[0]['title'],
		(string) $api_directions[0]['title']
	);

	$api_categories = mete_content_get_home_categories();

	mete_home_check_assert( 'API сопоставляет пять категорий по slug', 5 === count( $api_categories ) );
	mete_home_check_assert(
		'В элементе API есть термин, подпись и количество',
		$api_categories[0]['term'] instanceof WP_Term
			&& is_string( $api_categories[0]['label'] )
			&& $api_categories[0]['count'] > 0
	);
	mete_home_check_assert(
		'Без своей подписи выводится название категории',
		$api_categories[0]['label'] === $api_categories[0]['term']->name
	);

	/* Поиск категории ------------------------------------------------------ */

	mete_home_check_assert(
		'Категория находится по term_id',
		MCC_Content::resolve_home_category( array( 'term_id' => $consult->term_id ) ) instanceof WP_Term
	);
	mete_home_check_assert(
		'Категория находится по slug, если term_id потерян',
		MCC_Content::resolve_home_category( array( 'term_id' => 0, 'slug' => 'konsultaciya' ) ) instanceof WP_Term
	);
	mete_home_check_assert(
		'Несуществующая категория не находится',
		null === MCC_Content::resolve_home_category( array( 'term_id' => 999999, 'slug' => 'no-such-term' ) )
	);

	/* Санитизация карточек ------------------------------------------------ */

	$clean = MCC_Settings::sanitize(
		array(
			'home_directions' => array(
				array(
					'title'    => '  Лазерная эпиляция  ',
					'text'     => '  Женские и мужские зоны.  ',
					'image_id' => 0,
					'icon'     => 'laser',
				),
				array(
					'title'    => '   ',
					'text'     => 'Без заголовка',
					'image_id' => 0,
					'icon'     => 'face',
				),
				array(
					'title'    => 'Кастомная карточка',
					'text'     => '',
					'image_id' => $service_id,
					'icon'     => 'unknown',
				),
			),
		)
	);

	mete_home_check_assert( 'Заголовок карточки обрезан по краям', 'Лазерная эпиляция' === $clean['home_directions'][0]['title'] );
	mete_home_check_assert( 'Описание карточки обрезано по краям', 'Женские и мужские зоны.' === $clean['home_directions'][0]['text'] );
	mete_home_check_assert( 'Карточка без заголовка отброшена', 2 === count( $clean['home_directions'] ) );
	mete_home_check_assert( 'Неизвестная иллюстрация заменена на «нет»', 'none' === $clean['home_directions'][1]['icon'] );
	mete_home_check_assert( 'Не-изображение в image_id обнулено', 0 === $clean['home_directions'][1]['image_id'] );

	$long_title = str_repeat( 'А', 120 );
	$clean      = MCC_Settings::sanitize(
		array(
			'home_directions' => array(
				array(
					'title'    => $long_title,
					'text'     => str_repeat( 'Б', 300 ),
					'image_id' => 0,
					'icon'     => 'face',
				),
			),
		)
	);
	mete_home_check_assert( 'Заголовок обрезается до 80 символов', 80 === mb_strlen( $clean['home_directions'][0]['title'] ) );
	mete_home_check_assert( 'Описание обрезается до 240 символов', 240 === mb_strlen( $clean['home_directions'][0]['text'] ) );

	$many = array();

	for ( $i = 1; $i <= 15; $i++ ) {
		$many[] = array(
			'title'    => 'Карточка ' . $i,
			'text'     => '',
			'image_id' => 0,
			'icon'     => 'none',
		);
	}

	$clean = MCC_Settings::sanitize( array( 'home_directions' => $many ) );
	mete_home_check_assert( 'Карточек сохраняется не больше 12', 12 === count( $clean['home_directions'] ) );
	mete_home_check_assert( 'Порядок карточек сохраняется', 'Карточка 1' === $clean['home_directions'][0]['title'] && 'Карточка 12' === $clean['home_directions'][11]['title'] );

	/*
	 * Настоящая картинка: wp_attachment_is_image() проверяет и файл,
	 * поэтому создаём временный PNG в загрузках и удаляем его ниже.
	 */
	$uploads    = wp_upload_dir();
	$probe_path = trailingslashit( $uploads['path'] ) . 'mcc-home-check.png';

	file_put_contents(
		$probe_path,
		base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	);

	$attachment = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'MCC home check',
			'post_status'    => 'inherit',
		),
		$probe_path
	);

	if ( is_wp_error( $attachment ) ) {
		$attachment = 0;
	}

	$clean = MCC_Settings::sanitize(
		array(
			'home_directions' => array(
				array(
					'title'    => 'С картинкой',
					'text'     => '',
					'image_id' => $attachment,
					'icon'     => 'none',
				),
			),
		)
	);
	mete_home_check_assert( 'Изображение карточки сохраняется', (int) $attachment === $clean['home_directions'][0]['image_id'] );

	/* Санитизация категорий ------------------------------------------------ */

	$clean = MCC_Settings::sanitize(
		array(
			'home_categories' => array(
				array(
					'term_id' => $consult->term_id,
					'label'   => '  Консультация  ',
				),
				array(
					'term_id' => $consult->term_id,
					'label'   => 'Дубль',
				),
				array(
					'term_id' => 999999,
					'label'   => 'Удалённая',
				),
			),
		)
	);

	mete_home_check_assert( 'Подпись категории обрезана по краям', 'Консультация' === $clean['home_categories'][0]['label'] );
	mete_home_check_assert( 'Слаг категории берётся из термина', 'konsultaciya' === $clean['home_categories'][0]['slug'] );
	mete_home_check_assert( 'Дубликат категории удалён', 1 === count( $clean['home_categories'] ) );
	mete_home_check_assert( 'Несуществующая категория отброшена', 999999 !== (int) $clean['home_categories'][0]['term_id'] );

	$clean = MCC_Settings::sanitize(
		array(
			'home_categories' => array(
				array(
					'term_id' => 0,
					'slug'    => 'konsultaciya',
					'label'   => '',
				),
			),
		)
	);
	mete_home_check_assert( 'Категория по slug сохраняется с реальным term_id', (int) $consult->term_id === $clean['home_categories'][0]['term_id'] );

	/* Идемпотентность ------------------------------------------------------ */

	$input = array(
		'home_directions' => array(
			array(
				'title'    => 'Карточка',
				'text'     => 'Текст',
				'image_id' => 0,
				'icon'     => 'body',
			),
		),
		'home_categories' => array(
			array(
				'term_id' => $consult->term_id,
				'label'   => 'Подпись',
			),
		),
	);

	$once  = MCC_Settings::sanitize( $input );
	$twice = MCC_Settings::sanitize( $once );

	mete_home_check_assert( 'Санитизация карточек идемпотентна', $once['home_directions'] === $twice['home_directions'] );
	mete_home_check_assert( 'Санитизация категорий идемпотентна', $once['home_categories'] === $twice['home_categories'] );

	/* Пустой список и сохранённое значение -------------------------------- */

	$clean = MCC_Settings::sanitize(
		array(
			'home_directions_present' => '1',
			'home_categories_present' => '1',
		)
	);

	mete_home_check_assert( 'Пустой список карточек сохраняется пустым', array() === $clean['home_directions'] );
	mete_home_check_assert( 'Пустой список категорий сохраняется пустым', array() === $clean['home_categories'] );

	mete_home_check_set(
		array(
			'home_directions' => array(
				array(
					'title'    => 'Своя карточка',
					'text'     => '',
					'image_id' => 0,
					'icon'     => 'none',
				),
			),
		)
	);

	$clean = MCC_Settings::sanitize( array( 'phone' => '+7 (922) 399-14-46' ) );

	mete_home_check_assert( 'Без ключа и маркера карточки не сбрасываются', 1 === count( $clean['home_directions'] ) );
	mete_home_check_assert( 'Без ключа и маркера остаётся своя карточка', 'Своя карточка' === $clean['home_directions'][0]['title'] );

	/* Фильтр категорий на сайте ------------------------------------------- */

	mete_home_check_set(
		array(
			'home_categories' => array(
				array(
					'term_id' => (int) $consult->term_id,
					'slug'    => 'konsultaciya',
					'label'   => '',
				),
				array(
					'term_id' => (int) $empty->term_id,
					'slug'    => 'inekczionnaya-kosmetologiya',
					'label'   => '',
				),
				array(
					'term_id' => 999999,
					'slug'    => 'deleted-category',
					'label'   => '',
				),
			),
		)
	);

	$filtered = mete_content_get_home_categories();

	mete_home_check_assert( 'Категория без услуг пропускается', 1 === count( $filtered ) );
	mete_home_check_assert(
		'Остаётся именно непустая категория',
		$filtered[0]['term']->term_id === $consult->term_id
	);

	/* Вывод блока направлений --------------------------------------------- */

	mete_home_check_set();
	$html = mete_home_check_render( 'service-categories' );

	mete_home_check_assert( 'Секция направлений выводится', '' !== trim( $html ) );
	mete_home_check_assert( 'На странице три карточки направлений', 3 === substr_count( $html, '<article' ) );
	mete_home_check_assert( 'Карточки помечены как некликабельные', 3 === substr_count( $html, 'service-card--static' ) );
	mete_home_check_assert( 'Внутри блока направлений нет ссылок', false === strpos( $html, '<a ' ) );
	mete_home_check_assert( 'Нет кнопки «Показать все направления»', false === strpos( $html, 'data-services-toggle' ) );
	mete_home_check_assert( 'Нет остатков старой разметки', false === strpos( $html, 'service-card__arrow' ) );
	mete_home_check_assert( 'Класс сетки учитывает количество карточек', false !== strpos( $html, 'services-grid--count-3' ) );
	mete_home_check_assert( 'В сетке направлений есть data-reveal-group', false !== strpos( $html, 'data-reveal-group' ) );

	$eyebrow_position = mb_strpos( $html, 'Наши услуги' );
	$title_position   = mb_strpos( $html, 'Основные направления' );

	mete_home_check_assert(
		'Надзаголовок «Наши услуги» идёт перед «Основные направления»',
		false !== $eyebrow_position && false !== $title_position && $eyebrow_position < $title_position
	);

	mete_home_check_assert( 'Заголовок карточки выводится', false !== mb_strpos( $html, 'Лазерная эпиляция' ) );

	mete_home_check_set( array( 'home_directions' => array() ) );
	mete_home_check_assert( 'Без карточек секция направлений не выводится', '' === trim( mete_home_check_render( 'service-categories' ) ) );

	/* Вывод списка категорий ---------------------------------------------- */

	mete_home_check_set();
	$html = mete_home_check_render( 'home-categories' );

	mete_home_check_assert( 'Список категорий выводится', '' !== trim( $html ) );
	mete_home_check_assert( 'В списке пять категорий', 5 === substr_count( $html, 'category-list__link' ) );
	mete_home_check_assert( 'Ссылки ведут в каталог с фильтром', 5 === substr_count( $html, 'mete_cat=' ) );
	mete_home_check_assert( 'Есть кнопка полного каталога', false !== strpos( $html, esc_url( mete_catalog_url() ) ) );
	mete_home_check_assert( 'Выводится количество услуг', false !== mb_strpos( $html, 'услуг' ) );
	mete_home_check_assert( 'Нумерация начинается с 01', false !== strpos( $html, '>01<' ) );
	mete_home_check_assert( 'В списке категорий есть data-reveal-group', false !== strpos( $html, 'data-reveal-group' ) );

	mete_home_check_set( array( 'home_categories' => array() ) );
	mete_home_check_assert( 'Без категорий блок не выводится', '' === trim( mete_home_check_render( 'home-categories' ) ) );

	/* Русские формы числительных ------------------------------------------ */

	mete_home_check_assert( '1 услуга', '1 услуга' === mete_service_count_text( 1 ) );
	mete_home_check_assert( '3 услуги', '3 услуги' === mete_service_count_text( 3 ) );
	mete_home_check_assert( '5 услуг', '5 услуг' === mete_service_count_text( 5 ) );
	mete_home_check_assert( '11 услуг', '11 услуг' === mete_service_count_text( 11 ) );
	mete_home_check_assert( '21 услуга', '21 услуга' === mete_service_count_text( 21 ) );
} finally {
	if ( $attachment > 0 ) {
		wp_delete_attachment( $attachment, true );
	}

	if ( null === $original_settings ) {
		delete_option( MCC_Settings::OPTION );
	} else {
		update_option( MCC_Settings::OPTION, $original_settings );
	}
}

$total    = (int) $GLOBALS['mete_home_check_count'];
$failures = (int) $GLOBALS['mete_home_check_failures'];

mete_home_check_say( '' );
mete_home_check_say( sprintf( 'Итог: %d/%d проверок пройдено.', $total - $failures, $total ) );

if ( $failures > 0 && ! defined( 'MCC_CHECKS_COMBINED' ) ) {
	fwrite( STDERR, "Провалено: {$failures}\n" );
	exit( 1 );
}
