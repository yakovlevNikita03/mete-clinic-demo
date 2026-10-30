<?php
/**
 * Минимальные заглушки WordPress для автономных тестов.
 *
 * Нужны только для того, чтобы подключить классы плагина без wp-load.php.
 * Поведение функций повторяет ядро лишь в той части, которая используется
 * проверяемым кодом.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Translation stub.
	 *
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		unset( $domain );

		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Escaped translation stub.
	 *
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return htmlspecialchars( __( $text, $domain ), ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Merge arrays like WordPress.
	 *
	 * @param mixed $args     Arguments.
	 * @param array $defaults Defaults.
	 * @return array
	 */
	function wp_parse_args( $args, $defaults = array() ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		}

		$args = is_array( $args ) ? $args : array();

		return array_merge( $defaults, $args );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Lowercase key.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	function sanitize_key( $key ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Strip tags and extra whitespace.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function sanitize_text_field( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		$value = strip_tags( (string) $value );
		$value = preg_replace( '/[\r\n\t]+/', ' ', $value );
		$value = preg_replace( '/\s+/u', ' ', $value );

		return trim( (string) $value );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Strip tags but keep line breaks.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function sanitize_textarea_field( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return trim( strip_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Very small slugifier for tests.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function sanitize_title( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		$map = array(
			'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
			'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
			'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
			'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
			'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
		);

		$value = mb_strtolower( trim( (string) $value ), 'UTF-8' );
		$value = strtr( $value, $map );
		$value = preg_replace( '/[^a-z0-9]+/u', '-', $value );

		return trim( (string) $value, '-' );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Absolute integer.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	function absint( $value ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return abs( (int) $value );
	}
}
