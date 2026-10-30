<?php
/**
 * Server-side link check for settings URLs.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks Yclients, MAX and map URLs without leaving the admin page.
 *
 * Запрос выполняется только по http(s) и только к публичным адресам:
 * wp_http_validate_url отсекает localhost и приватные сети, чтобы форма
 * не превратилась в инструмент SSRF.
 */
class MCC_Links {

	/**
	 * AJAX action.
	 */
	const ACTION = 'mcc_check_link';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'check' ) );
	}

	/**
	 * Handle the AJAX request.
	 *
	 * @return void
	 */
	public static function check() {
		check_ajax_referer( 'mcc_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Недостаточно прав.', 'mete-clinic-content' ) ), 403 );
		}

		$raw = isset( $_POST['url'] ) ? (string) wp_unslash( $_POST['url'] ) : '';
		$url = MCC_Sanitize::url( $raw );

		if ( '' === $url ) {
			wp_send_json_error( array( 'message' => __( 'Нужен полный адрес, начинающийся с http:// или https://.', 'mete-clinic-content' ) ) );
		}

		$safe = wp_http_validate_url( $url );

		if ( false === $safe ) {
			wp_send_json_error( array( 'message' => __( 'Этот адрес нельзя проверить с сервера: он ведёт в локальную сеть.', 'mete-clinic-content' ) ) );
		}

		$response = wp_safe_remote_head(
			$safe,
			array(
				'timeout'     => 8,
				'redirection' => 3,
			)
		);

		if ( is_wp_error( $response ) || 405 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$response = wp_safe_remote_get(
				$safe,
				array(
					'timeout'     => 8,
					'redirection' => 3,
					'limit_response_size' => 1024,
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: error message. */
						__( 'Ссылка не отвечает: %s', 'mete-clinic-content' ),
						$response->get_error_message()
					),
				)
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 400 ) {
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %d: HTTP status code. */
						__( 'Ссылка отвечает, код %d.', 'mete-clinic-content' ),
						$code
					),
				)
			);
		}

		if ( 401 === $code || 403 === $code ) {
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %d: HTTP status code. */
						__( 'Сервер отвечает %d: ссылка существует, но требует входа или закрыта настройками.', 'mete-clinic-content' ),
						$code
					),
				)
			);
		}

		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %d: HTTP status code. */
					__( 'Сервер вернул код %d. Проверьте адрес.', 'mete-clinic-content' ),
					$code
				),
			)
		);
	}
}
