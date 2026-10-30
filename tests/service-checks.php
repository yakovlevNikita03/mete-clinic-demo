<?php
/**
 * MÉTE CLINIC — интеграционные проверки услуг.
 *
 * Запуск на тестовом сайте (не на рабочем):
 *
 *   wp eval-file tests/service-checks.php
 *
 * Скрипт создаёт временную категорию и услуги, проверяет сохранение цены,
 * типы цены, порядок популярных услуг и фильтр каталога, затем удаляет
 * созданные записи. Рабочие данные не изменяются.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Запускайте через WP-CLI: wp eval-file tests/service-checks.php\n" );
	exit( 1 );
}

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

/**
 * Normalize a price label for comparison.
 *
 * @param string $value Label.
 * @return string
 */
function mete_check_price( $value ) {
	$value = str_replace( "\xC2\xA0", ' ', (string) $value );

	return trim( preg_replace( '/\s+/u', ' ', $value ) );
}

/**
 * Create a test service.
 *
 * @param string $title  Title.
 * @param string $type   Price type.
 * @param string $price  Price.
 * @param int    $order  Home order.
 * @param int    $term   Category term ID.
 * @return int
 */
function mete_check_service( $title, $type, $price, $order, $term ) {
	$post_id = wp_insert_post(
		array(
			'post_type'    => 'mete_service',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => 'проверка',
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
		wp_set_object_terms( $post_id, array( (int) $term ), 'mete_service_cat' );
	}

	return (int) $post_id;
}

mete_check_say( 'MÉTE CLINIC — проверки услуг' );

mete_check_assert( 'Тип записи mete_service зарегистрирован', post_type_exists( 'mete_service' ) );
mete_check_assert( 'Таксономия mete_service_cat зарегистрирована', taxonomy_exists( 'mete_service_cat' ) );

$term = wp_insert_term(
	'Проверка категории',
	'mete_service_cat',
	array( 'slug' => 'mete-check-cat' )
);

if ( is_wp_error( $term ) ) {
	$existing = get_term_by( 'slug', 'mete-check-cat', 'mete_service_cat' );
	$term_id  = $existing instanceof WP_Term ? (int) $existing->term_id : 0;
} else {
	$term_id = (int) $term['term_id'];
}

mete_check_assert( 'Категория создаётся', $term_id > 0 );

$created   = array();
$service_a = mete_check_service( 'Проверка — фиксированная', 'fixed', '1500', 5, $term_id );
$service_b = mete_check_service( 'Проверка — от', 'from', '2500', 1, $term_id );

$created[] = $service_a;
$created[] = $service_b;

mete_check_assert( 'Услуга создаётся', $service_a > 0 && $service_b > 0 );

$price = mete_check_price( MCC_Content::format_price( $service_a ) );
mete_check_assert(
	'Фиксированная цена',
	'1500' === preg_replace( '/[^0-9]/', '', $price ) && false !== mb_strpos( $price, '₽' ),
	$price
);

update_post_meta( $service_a, MCC_Fields::META_PRICE_TYPE, 'from' );
$price = mete_check_price( MCC_Content::format_price( $service_a ) );
mete_check_assert(
	'Цена «от»',
	0 === mb_strpos( $price, 'от ' ) && '1500' === preg_replace( '/[^0-9]/', '', $price ),
	$price
);

update_post_meta( $service_a, MCC_Fields::META_PRICE_TYPE, 'range' );
update_post_meta( $service_a, MCC_Fields::META_PRICE_MAX, '3000' );
$price = mete_check_price( MCC_Content::format_price( $service_a ) );
mete_check_assert( 'Диапазон', false !== mb_strpos( $price, '–' ), $price );

update_post_meta( $service_a, MCC_Fields::META_PRICE_MAX, '1000' );
$price = mete_check_price( MCC_Content::format_price( $service_a ) );
mete_check_assert( 'Диапазон с максимумом меньше минимума показывает одну цену', false === mb_strpos( $price, '–' ), $price );

update_post_meta( $service_a, MCC_Fields::META_PRICE, '' );
mete_check_assert( 'Пустая цена не даёт «0 ₽»', '' === MCC_Content::format_price( $service_a ) );

update_post_meta( $service_a, MCC_Fields::META_PRICE, '1500' );
update_post_meta( $service_a, MCC_Fields::META_DURATION, '60' );
mete_check_assert( 'Продолжительность выводится в минутах', '60 мин' === MCC_Content::get_duration( $service_a ), MCC_Content::get_duration( $service_a ) );

$popular = MCC_Content::get_popular_services();
$ids     = wp_list_pluck( $popular->posts, 'ID' );

mete_check_assert( 'Популярные услуги содержат отмеченную услугу', in_array( $service_b, array_map( 'intval', $ids ), true ) );

$ids        = array_map( 'intval', $ids );
$position_b = array_search( $service_b, $ids, true );
$position_a = array_search( $service_a, $ids, true );

mete_check_assert(
	'Порядок популярных услуг соблюдается',
	false !== $position_b && false !== $position_a && $position_b < $position_a,
	'позиции: ' . var_export( array( $position_b, $position_a ), true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
);

update_post_meta( $service_b, MCC_Fields::META_SHOW_HOME, '' );
$popular = MCC_Content::get_popular_services();
$ids     = array_map( 'intval', wp_list_pluck( $popular->posts, 'ID' ) );
mete_check_assert( 'Снятый флаг убирает услугу с главной', ! in_array( $service_b, $ids, true ) );

$catalog = new WP_Query(
	array(
		'post_type'      => 'mete_service',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => 'mete_service_cat',
				'field'    => 'term_id',
				'terms'    => $term_id,
			),
		),
	)
);

mete_check_assert( 'Фильтр каталога находит услугу категории', in_array( $service_a, array_map( 'intval', $catalog->posts ), true ) );

foreach ( $created as $post_id ) {
	if ( $post_id > 0 ) {
		wp_delete_post( $post_id, true );
	}
}

if ( $term_id > 0 ) {
	wp_delete_term( $term_id, 'mete_service_cat' );
}

mete_check_say( '' );
mete_check_say( sprintf( 'Проверок: %d, ошибок: %d', (int) $GLOBALS['mete_check_count'], (int) $GLOBALS['mete_check_failures'] ) );

if ( (int) $GLOBALS['mete_check_failures'] > 0 ) {
	exit( 1 );
}
