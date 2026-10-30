<?php
/**
 * Input sanitizers shared by meta boxes and settings.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes plugin input values.
 */
class MCC_Sanitize {

	/**
	 * Absolute http(s) URL or an empty string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function url( $value ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		$url = trim( (string) $value );

		if ( '' === $url ) {
			return '';
		}

		$url = esc_url_raw( $url, array( 'http', 'https' ) );

		if ( '' === $url || ! self::is_http_url( $url ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Whether a value is an absolute http(s) URL.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function is_http_url( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$url = trim( (string) $value );

		if ( '' === $url ) {
			return false;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $url, PHP_URL_HOST );

		if ( empty( $host ) ) {
			return false;
		}

		return in_array( strtolower( (string) $scheme ), array( 'http', 'https' ), true );
	}

	/**
	 * Price value: positive decimal number or an empty string.
	 *
	 * Ноль и отрицательные значения считаются незаданной ценой: на сайте
	 * не должно появляться «0 ₽».
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function price( $value ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		$value = str_replace( array( ' ', "\xC2\xA0", '₽' ), '', $value );
		$value = str_replace( ',', '.', $value );

		if ( '' === $value || ! is_numeric( $value ) ) {
			return '';
		}

		$number = (float) $value;

		if ( $number <= 0 || $number > 10000000 ) {
			return '';
		}

		return (string) round( $number, 2 );
	}

	/**
	 * Whether a submitted price is unusable.
	 *
	 * Пустая строка — это «цена не задана», а не ошибка.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function price_is_invalid( $value ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return true;
		}

		if ( '' === trim( (string) $value ) ) {
			return false;
		}

		return '' === self::price( $value );
	}

	/**
	 * Duration in minutes: 1…1440 or an empty string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function duration( $value ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		if ( ! preg_match( '/^[0-9]{1,4}$/', $value ) ) {
			return '';
		}

		$minutes = (int) $value;

		if ( $minutes < 1 || $minutes > 1440 ) {
			return '';
		}

		return (string) $minutes;
	}

	/**
	 * External identifier: letters, digits, underscore and dash.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function identifier( $value ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( (string) $value );
		$value = preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $value );

		return mb_substr( (string) $value, 0, 64 );
	}

	/**
	 * Non-negative integer within a range.
	 *
	 * @param mixed $value Raw value.
	 * @param int   $max   Upper bound.
	 * @return int
	 */
	public static function order( $value, $max = 9999 ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return 0;
		}

		$number = absint( $value );

		if ( $number > $max ) {
			$number = $max;
		}

		return $number;
	}

	/**
	 * Official widget code.
	 *
	 * Код официального виджета может содержать script/iframe, поэтому он
	 * принимается только от пользователей с правом unfiltered_html. Для
	 * остальных выполняется обычная очистка wp_kses_post.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function widget_html( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$html = trim( (string) $value );

		if ( '' === $html ) {
			return '';
		}

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			return wp_kses_post( $html );
		}

		return str_replace( array( '<?php', '<?=' ), '', $html );
	}
}
