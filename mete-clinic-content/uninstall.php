<?php
/**
 * Uninstall handler.
 *
 * Удаляются только служебные настройки плагина. Услуги, специалисты, категории
 * и загруженные изображения остаются: это данные клиники, их удаление должно
 * быть осознанным действием владельца сайта.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mcc_settings' );
delete_option( 'mcc_seeded' );
