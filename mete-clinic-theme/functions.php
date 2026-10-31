<?php
/**
 * MÉTE CLINIC — theme bootstrap.
 *
 * Тема отвечает только за отображение. Данные (услуги, специалисты, настройки)
 * предоставляет плагин mete-clinic-content.
 *
 * @package Mete_Clinic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'METE_THEME_VERSION' ) ) {
	define( 'METE_THEME_VERSION', '1.3.0' );
}

if ( ! defined( 'METE_THEME_DIR' ) ) {
	define( 'METE_THEME_DIR', get_template_directory() );
}

if ( ! defined( 'METE_THEME_URI' ) ) {
	define( 'METE_THEME_URI', get_template_directory_uri() );
}

require_once METE_THEME_DIR . '/inc/template-tags.php';

/**
 * Theme supports, image sizes and menus.
 *
 * @return void
 */
function mete_setup() {
	load_theme_textdomain( 'mete-clinic', METE_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array(
		'height'      => 104,
		'width'       => 104,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
		'navigation-widgets',
	) );

	add_image_size( 'mete-card', 800, 600, true );
	add_image_size( 'mete-portrait', 480, 600, true );

	register_nav_menus( array(
		'primary' => __( 'Основная навигация', 'mete-clinic' ),
	) );
}
add_action( 'after_setup_theme', 'mete_setup' );

/**
 * Content width for oEmbeds and wide images.
 *
 * @return void
 */
function mete_set_content_width() {
	$GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'mete_set_content_width', 0 );

/**
 * Front-end assets.
 *
 * @return void
 */
function mete_enqueue_assets() {
	wp_enqueue_style(
		'mete-clinic',
		get_stylesheet_uri(),
		array(),
		METE_THEME_VERSION
	);

	wp_enqueue_script(
		'mete-clinic',
		METE_THEME_URI . '/assets/js/main.js',
		array(),
		METE_THEME_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'mete_enqueue_assets' );

/**
 * Progressively enhance reveal animations without a flash of visible content.
 *
 * @return void
 */
function mete_js_class() {
	echo '<script>document.documentElement.classList.add("js");</script>' . "\n";
}
add_action( 'wp_head', 'mete_js_class', 0 );

/**
 * Short excerpts for service cards.
 *
 * @param int $length Current length.
 * @return int
 */
function mete_excerpt_length( $length ) {
	if ( is_admin() ) {
		return $length;
	}

	return 26;
}
add_filter( 'excerpt_length', 'mete_excerpt_length' );

/**
 * Excerpt ellipsis.
 *
 * @param string $more Current "more" string.
 * @return string
 */
function mete_excerpt_more( $more ) {
	if ( is_admin() ) {
		return $more;
	}

	return '…';
}
add_filter( 'excerpt_more', 'mete_excerpt_more' );

/**
 * Warn the administrator when the content plugin is missing.
 *
 * @return void
 */
function mete_plugin_notice() {
	if ( ! current_user_can( 'activate_plugins' ) || mete_plugin_active() ) {
		return;
	}

	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'Тема MÉTE CLINIC работает без плагина MÉTE CLINIC — Content: услуги, специалисты и контактные данные не выводятся. Установите и активируйте плагин mete-clinic-content.', 'mete-clinic' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'mete_plugin_notice' );
