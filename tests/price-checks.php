<?php
/**
 * MÉTE CLINIC — интеграционные проверки импорта прайса.
 *
 * Запуск на тестовом сайте (не на рабочем):
 *
 *   wp eval-file tests/price-checks.php
 *
 * Скрипт создаёт временную категорию, услуги и специалиста, проверяет
 * предпросмотр и применение плана импорта, затем удаляет созданные записи.
 * Рабочие данные не изменяются.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Запускайте через WP-CLI: wp eval-file tests/price-checks.php\n" );
	exit( 1 );
}

if ( ! class_exists( 'MCC_Price_Data' ) ) {
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

/**
 * Create a service row for import tests.
 *
 * @param string $title Title.
 * @param string $type  Price type.
 * @param string $price Price.
 * @param int    $order Home order.
 * @param int    $term  Category term ID.
 * @return int
 */
function mete_price_service( $title, $type, $price, $order, $term ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => MCC_Post_Types::SERVICE,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return 0;
	}

	update_post_meta( $post_id, MCC_Fields::META_PRICE_TYPE, $type );
	update_post_meta( $post_id, MCC_Fields::META_PRICE, $price );
	update_post_meta( $post_id, MCC_Fields::META_SHOW_HOME, '1' );
	update_post_meta( $post_id, MCC_Fields::META_HOME_ORDER, $order );

	if ( $term > 0 ) {
		wp_set_object_terms( $post_id, array( (int) $term ), MCC_Post_Types::TAX );
	}

	return (int) $post_id;
}

mete_check_say( '' );
mete_check_say( 'MÉTE CLINIC — проверки импорта прайса' );

$term = wp_insert_term(
	'Проверка импорта',
	MCC_Post_Types::TAX,
	array( 'slug' => 'mete-import-check' )
);

if ( is_wp_error( $term ) ) {
	$existing = get_term_by( 'slug', 'mete-import-check', MCC_Post_Types::TAX );
	$term_id  = $existing instanceof WP_Term ? (int) $existing->term_id : 0;
} else {
	$term_id = (int) $term['term_id'];
}

$created      = array();
$created_terms = array();

$service_a = mete_price_service( 'Проверка импорта — фиксированная', 'fixed', '1500', 5, $term_id );
$service_b = mete_price_service( 'Проверка импорта — от', 'from', '2500', 1, $term_id );

$created[] = $service_a;
$created[] = $service_b;

mete_check_assert( 'Тестовые услуги созданы', $service_a > 0 && $service_b > 0 );

$header = MCC_Price_Data::header_row( MCC_Price_Data::SERVICE );

$sheet = array(
	$header,
	array( (string) $service_a, 'Проверка импорта', 'Проверка импорта — фиксированная', 'fixed', '1700', '', '', '45', 'да', '3', 'опубликована', '', 'Новое описание' ),
	array( '', 'Проверка импорта', 'Проверка импорта — новая', 'free', '', '', '', '', 'нет', '', 'опубликована', '', '' ),
	array( '', 'Категории нет', 'Проверка импорта — без категории', 'fixed', '100', '', '', '', 'нет', '', 'опубликована', '', '' ),
	array( '', 'Проверка импорта', 'Проверка импорта — от', 'from', '', '', '', '', 'да', '1', 'опубликована', '', '' ),
);

$plan = MCC_Price_Data::plan( MCC_Price_Data::SERVICE, $sheet, 'both', false );

mete_check_assert( 'План содержит четыре строки', 4 === count( $plan['rows'] ) );
mete_check_assert( 'Найдено обновление по ID', 1 === (int) $plan['counts']['update'] || 2 === (int) $plan['counts']['update'], wp_json_encode( $plan['counts'] ) );
mete_check_assert( 'Найдено создание новой услуги', 1 === (int) $plan['counts']['create'], wp_json_encode( $plan['counts'] ) );
mete_check_assert( 'Строка с несуществующей категорией — ошибка', 1 === (int) $plan['counts']['error'], wp_json_encode( $plan['counts'] ) );
mete_check_assert(
	'Ошибка объясняет причину',
	false !== mb_strpos( implode( ' ', $plan['rows'][2]['errors'] ), 'не найдена' )
);

$update_row = $plan['rows'][0];
$fields     = wp_list_pluck( $update_row['changes'], 'field' );

mete_check_assert( 'В изменениях есть цена', in_array( 'Цена', $fields, true ), implode( ', ', $fields ) );
mete_check_assert( 'В изменениях есть продолжительность', in_array( 'Минут', $fields, true ), implode( ', ', $fields ) );
mete_check_assert( 'В изменениях есть порядок на главной', in_array( 'Порядок на главной', $fields, true ), implode( ', ', $fields ) );
mete_check_assert( 'В изменениях есть описание', in_array( 'Описание', $fields, true ), implode( ', ', $fields ) );

$empty_price_row = $plan['rows'][3];
mete_check_assert( 'Пустая цена не считается изменением', 'same' === $empty_price_row['action'], wp_json_encode( $empty_price_row ) );

$apply = MCC_Price_Data::apply( $plan );

mete_check_assert( 'Применение: одно обновление', 1 === (int) $apply['update'], wp_json_encode( $apply ) );
mete_check_assert( 'Применение: одно создание', 1 === (int) $apply['create'], wp_json_encode( $apply ) );
mete_check_assert( 'Применение: ошибка не записывается', 1 === (int) $apply['error'], wp_json_encode( $apply ) );

mete_check_assert(
	'Цена обновилась',
	'1700' === (string) get_post_meta( $service_a, MCC_Fields::META_PRICE, true ),
	(string) get_post_meta( $service_a, MCC_Fields::META_PRICE, true )
);
mete_check_assert(
	'Продолжительность обновилась',
	'45' === (string) get_post_meta( $service_a, MCC_Fields::META_DURATION, true ),
	(string) get_post_meta( $service_a, MCC_Fields::META_DURATION, true )
);
mete_check_assert(
	'Порядок на главной обновился',
	'3' === (string) get_post_meta( $service_a, MCC_Fields::META_HOME_ORDER, true ),
	(string) get_post_meta( $service_a, MCC_Fields::META_HOME_ORDER, true )
);
mete_check_assert(
	'Описание обновилось',
	'Новое описание' === (string) get_post_field( 'post_content', $service_a ),
	(string) get_post_field( 'post_content', $service_a )
);
mete_check_assert(
	'Пустая цена не стёрла существующую',
	'2500' === (string) get_post_meta( $service_b, MCC_Fields::META_PRICE, true ),
	(string) get_post_meta( $service_b, MCC_Fields::META_PRICE, true )
);

$new_service = MCC_Price_Data::find_service( 0, 'Проверка импорта — новая' );

mete_check_assert( 'Новая услуга создана', $new_service instanceof WP_Post );

if ( $new_service instanceof WP_Post ) {
	$created[] = (int) $new_service->ID;

	mete_check_assert(
		'Новая услуга бесплатная',
		'free' === (string) get_post_meta( $new_service->ID, MCC_Fields::META_PRICE_TYPE, true )
	);
	mete_check_assert(
		'Новая услуга получила категорию',
		has_term( $term_id, MCC_Post_Types::TAX, $new_service->ID )
	);
}

mete_check_assert(
	'Услуга с ошибкой не создана',
	null === MCC_Price_Data::find_service( 0, 'Проверка импорта — без категории' )
);

/* Режим «только добавить» не перезаписывает. */

$add_sheet = array(
	$header,
	array( (string) $service_a, 'Проверка импорта', 'Проверка импорта — фиксированная', 'fixed', '999', '', '', '', '', '', '', '', '' ),
);

$add_plan = MCC_Price_Data::plan( MCC_Price_Data::SERVICE, $add_sheet, 'add', false );

mete_check_assert( 'Режим add: строка пропущена', 'same' === $add_plan['rows'][0]['action'] );
mete_check_assert( 'Режим add: ноль обновлений', 0 === (int) $add_plan['counts']['update'] );

MCC_Price_Data::apply( $add_plan );

mete_check_assert(
	'Режим add не изменил цену',
	'1700' === (string) get_post_meta( $service_a, MCC_Fields::META_PRICE, true ),
	(string) get_post_meta( $service_a, MCC_Fields::META_PRICE, true )
);

/* Очистка пустых ячеек по галочке. */

$clear_sheet = array(
	$header,
	array( (string) $service_a, '', '', 'free', '', '', '', '', '', '', '', '', '' ),
);

$clear_plan = MCC_Price_Data::plan( MCC_Price_Data::SERVICE, $clear_sheet, 'update', true );

mete_check_assert( 'Очистка: цена будет удалена', 'update' === $clear_plan['rows'][0]['action'], wp_json_encode( $clear_plan['rows'][0] ) );

MCC_Price_Data::apply( $clear_plan );

mete_check_assert(
	'Бесплатная услуга не держит цену',
	'' === (string) get_post_meta( $service_a, MCC_Fields::META_PRICE, true ),
	(string) get_post_meta( $service_a, MCC_Fields::META_PRICE, true )
);

/* Категории ------------------------------------------------------------- */

$cat_header = MCC_Price_Data::header_row( MCC_Price_Data::CATEGORY );
$cat_sheet  = array(
	$cat_header,
	array( '', 'mete-import-new', 'Проверка новая категория', '33', 'Описание категории' ),
	array( (string) $term_id, 'mete-import-check', 'Проверка импорта', '44', '' ),
);

$cat_plan = MCC_Price_Data::plan( MCC_Price_Data::CATEGORY, $cat_sheet, 'both', false );

mete_check_assert( 'Категория: одна новая', 1 === (int) $cat_plan['counts']['create'], wp_json_encode( $cat_plan['counts'] ) );
mete_check_assert( 'Категория: одно обновление', 1 === (int) $cat_plan['counts']['update'], wp_json_encode( $cat_plan['counts'] ) );

MCC_Price_Data::apply( $cat_plan );

$new_term = get_term_by( 'slug', 'mete-import-new', MCC_Post_Types::TAX );

mete_check_assert( 'Новая категория создана', $new_term instanceof WP_Term );

if ( $new_term instanceof WP_Term ) {
	$created_terms[] = (int) $new_term->term_id;

	mete_check_assert(
		'Порядок новой категории записан',
		'33' === (string) get_term_meta( $new_term->term_id, MCC_Term_Fields::META_ORDER, true ),
		(string) get_term_meta( $new_term->term_id, MCC_Term_Fields::META_ORDER, true )
	);
}

mete_check_assert(
	'Порядок существующей категории обновлён',
	'44' === (string) get_term_meta( $term_id, MCC_Term_Fields::META_ORDER, true ),
	(string) get_term_meta( $term_id, MCC_Term_Fields::META_ORDER, true )
);

/* Специалисты ----------------------------------------------------------- */

$spec_header = MCC_Price_Data::header_row( MCC_Price_Data::SPECIALIST );
$spec_sheet  = array(
	$spec_header,
	array( '', 'Проверка Специалист', 'косметолог', '5 лет', "Первое образование\nВторое образование", '2', 'опубликован', '' ),
);

$spec_plan = MCC_Price_Data::plan( MCC_Price_Data::SPECIALIST, $spec_sheet, 'add', false );

mete_check_assert( 'Специалист: создание', 1 === (int) $spec_plan['counts']['create'], wp_json_encode( $spec_plan['counts'] ) );

MCC_Price_Data::apply( $spec_plan );

$specialist = MCC_Price_Data::find_specialist( 0, 'Проверка Специалист' );

mete_check_assert( 'Специалист создан', $specialist instanceof WP_Post );

if ( $specialist instanceof WP_Post ) {
	$created[] = (int) $specialist->ID;

	mete_check_assert(
		'Опыт специалиста записан',
		'5 лет' === (string) get_post_meta( $specialist->ID, MCC_Fields::META_EXPERIENCE, true ),
		(string) get_post_meta( $specialist->ID, MCC_Fields::META_EXPERIENCE, true )
	);
	mete_check_assert(
		'Образование сохранило переносы строк',
		false !== mb_strpos( (string) get_post_meta( $specialist->ID, MCC_Fields::META_EDUCATION, true ), "\n" )
	);
	mete_check_assert( 'Порядок специалиста записан', 2 === (int) $specialist->menu_order, (string) $specialist->menu_order );
}

/* Выгрузка -------------------------------------------------------------- */

$export = MCC_Price_Data::export_rows( MCC_Price_Data::SERVICE );

mete_check_assert( 'Выгрузка услуг не пустая', count( $export ) > 0 );
mete_check_assert(
	'Ширина строк выгрузки совпадает с шапкой',
	isset( $export[0] ) && count( (array) $export[0] ) === count( $header )
);

$ids = array_map( 'intval', wp_list_pluck( $export, 0 ) );
mete_check_assert( 'Выгрузка содержит ID услуги', in_array( (int) $service_b, $ids, true ) );

/* Очистка --------------------------------------------------------------- */

foreach ( array_unique( array_filter( array_map( 'intval', $created ) ) ) as $post_id ) {
	wp_delete_post( $post_id, true );
}

if ( ! empty( $created_terms ) ) {
	foreach ( $created_terms as $created_term ) {
		wp_delete_term( (int) $created_term, MCC_Post_Types::TAX );
	}
}

if ( $term_id > 0 ) {
	wp_delete_term( $term_id, MCC_Post_Types::TAX );
}

mete_check_say( '' );
mete_check_say( sprintf( 'Проверок: %d, ошибок: %d', (int) $GLOBALS['mete_check_count'], (int) $GLOBALS['mete_check_failures'] ) );

if ( (int) $GLOBALS['mete_check_failures'] > 0 && ! defined( 'MCC_CHECKS_COMBINED' ) ) {
	exit( 1 );
}
