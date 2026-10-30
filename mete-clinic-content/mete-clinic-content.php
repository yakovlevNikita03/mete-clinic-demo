<?php
/**
 * Plugin Name: MÉTE CLINIC — Content
 * Plugin URI:  https://mete-clinic.local/
 * Description: Услуги, специалисты и настройки сайта MÉTE CLINIC. Данные хранятся в WordPress и не зависят от активной темы.
 * Version:     1.3.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      MÉTE CLINIC
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mete-clinic-content
 * Domain Path: /languages
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'MCC_VERSION' ) ) {
	define( 'MCC_VERSION', '1.3.1' );
}

if ( ! defined( 'MCC_FILE' ) ) {
	define( 'MCC_FILE', __FILE__ );
}

if ( ! defined( 'MCC_DIR' ) ) {
	define( 'MCC_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'MCC_URL' ) ) {
	define( 'MCC_URL', plugin_dir_url( __FILE__ ) );
}

require_once MCC_DIR . 'includes/class-mcc-sanitize.php';
require_once MCC_DIR . 'includes/class-mcc-post-types.php';
require_once MCC_DIR . 'includes/class-mcc-fields.php';
require_once MCC_DIR . 'includes/class-mcc-term-fields.php';
require_once MCC_DIR . 'includes/class-mcc-sheets.php';
require_once MCC_DIR . 'includes/class-mcc-price-data.php';
require_once MCC_DIR . 'includes/class-mcc-price-io.php';
require_once MCC_DIR . 'includes/class-mcc-dashboard.php';
require_once MCC_DIR . 'includes/class-mcc-settings.php';
require_once MCC_DIR . 'includes/class-mcc-admin-list.php';
require_once MCC_DIR . 'includes/class-mcc-admin-assets.php';
require_once MCC_DIR . 'includes/class-mcc-links.php';
require_once MCC_DIR . 'includes/class-mcc-content.php';
require_once MCC_DIR . 'includes/class-mcc-seeder.php';
require_once MCC_DIR . 'includes/class-mcc-catalog-import.php';

/**
 * Boot the plugin.
 *
 * @return void
 */
function mcc_boot() {
	load_plugin_textdomain(
		'mete-clinic-content',
		false,
		dirname( plugin_basename( MCC_FILE ) ) . '/languages'
	);

	MCC_Post_Types::init();
	MCC_Fields::init();
	MCC_Term_Fields::init();
	MCC_Dashboard::init();
	MCC_Settings::init();
	MCC_Admin_List::init();
	MCC_Admin_Assets::init();
	MCC_Links::init();
	MCC_Price_IO::init();
	MCC_Seeder::init();
	MCC_Catalog_Import::init();
}
add_action( 'plugins_loaded', 'mcc_boot' );

register_activation_hook( __FILE__, array( 'MCC_Seeder', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MCC_Seeder', 'deactivate' ) );

/**
 * Settings link on the plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function mcc_action_links( $links ) {
	$url = admin_url( 'admin.php?page=' . MCC_Dashboard::PAGE );

	array_unshift(
		$links,
		'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Обзор', 'mete-clinic-content' ) . '</a>'
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mcc_action_links' );
