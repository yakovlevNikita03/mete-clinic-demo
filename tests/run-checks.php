<?php
/**
 * Браузерный запуск проверок для хостинга без SSH.
 *
 * Как пользоваться:
 *
 * 1. Загрузите ЧЕТЫРЕ файла в корень WordPress (рядом с wp-load.php):
 *      run-checks.php
 *      service-checks.php
 *      price-checks.php
 *      home-checks.php
 * 2. Войдите в админку под администратором.
 * 3. Откройте https://ваш-сайт/run-checks.php
 * 4. Скопируйте или сохраните вывод (Ctrl+A, Ctrl+C).
 * 5. УДАЛИТЕ все четыре файла с сервера.
 *
 * Скрипт работает только для вошедшего администратора. Он создаёт временную
 * категорию, услуги и специалиста и удаляет их в конце.
 *
 * @package Mete_Clinic_Content
 */

define( 'WP_USE_THEMES', false );

$mcc_root = __DIR__;

if ( ! file_exists( $mcc_root . '/wp-load.php' ) ) {
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "Положите run-checks.php в корень WordPress, рядом с wp-load.php.\n";
	exit( 1 );
}

require $mcc_root . '/wp-load.php';

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	status_header( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "Нужно войти в админку под администратором.\n";
	exit;
}

header( 'Content-Type: text/plain; charset=utf-8' );

define( 'MCC_CHECKS_COMBINED', true );

require $mcc_root . '/service-checks.php';
require $mcc_root . '/price-checks.php';

if ( file_exists( $mcc_root . '/home-checks.php' ) ) {
	require $mcc_root . '/home-checks.php';
}

$mcc_failures = (int) $GLOBALS['mete_check_failures'] + (int) ( isset( $GLOBALS['mete_home_check_failures'] ) ? $GLOBALS['mete_home_check_failures'] : 0 );

if ( $mcc_failures > 0 ) {
	status_header( 500 );
	exit( 1 );
}
