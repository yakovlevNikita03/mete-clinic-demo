<?php
/**
 * Site settings page and the MÉTE CLINIC admin menu.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings storage, admin menu and sanitizing.
 */
class MCC_Settings {

	/**
	 * Option name.
	 */
	const OPTION = 'mcc_settings';

	/**
	 * Settings group.
	 */
	const GROUP = 'mcc_settings_group';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Default values.
	 *
	 * Значения перенесены из утверждённой статической версии сайта.
	 * Владелец клиники подтверждает их на странице настроек.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'phone'               => '+7 (922) 399-14-46',
			'phone_tel'           => '+79223991446',
			'address'             => 'ул. Фармана Салманова, 18, Тюмень',
			'working_hours'       => '',
			'booking_url'         => 'https://n1801245.yclients.ru/company/1599669/personal/menu?o=',
			'max_url'             => 'https://max.ru/u/f9LHodD0cOJpLifZEh6TFYIMu_rolVQOTrApxpIofPMF6MgW25Htu44yCXU',
			'messengers'          => '',
			'map_embed_url'       => 'https://makemap.2gis.ru/widget?data=eJw1jsFuhDAMRP_FvUYrQkgg-YCtetvbSq32gMBtIwWMglfaLeLfa6D1yfKMZ94ClHvM2L8iDcg54gzhYwF-TggBztjyPSMomDJNmHnXF-goURb9pSgb_VmKzpHT9iFrj3OX48SRxuPw8zb2-ICgi_9ZFXwdhc8t7q_tQnFk8XckUHFseYdx9lR5b41Xtj7psjDG3OQ_9hC8K9ebgqGdLjTHo3CB1DKEw1vZxpamdrVTkDZVwmyhK-9qrxvjnRY6okHYnGQKN6V0_UZM7_uV8x3XX3SKWPg',
			'map_provider'        => '2gis',
			'map_url'             => '',
			'reviews_title'       => '',
			'reviews_text'        => '',
			'reviews_embed_url'   => '',
			'reviews_url'         => '',
			'reviews_widget_html' => '',
		);
	}

	/**
	 * Current settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::defaults() );
	}

	/**
	 * Register the setting.
	 *
	 * @return void
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'description'       => __( 'Контакты, запись, карта и отзывы MÉTE CLINIC.', 'mete-clinic-content' ),
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Admin menu.
	 *
	 * @return void
	 */
	public static function menu() {
		add_menu_page(
			__( 'MÉTE CLINIC', 'mete-clinic-content' ),
			__( 'MÉTE CLINIC', 'mete-clinic-content' ),
			'manage_options',
			'mcc-settings',
			array( __CLASS__, 'render' ),
			'dashicons-heart',
			58
		);

		add_submenu_page(
			'mcc-settings',
			__( 'Настройки сайта', 'mete-clinic-content' ),
			__( 'Настройки сайта', 'mete-clinic-content' ),
			'manage_options',
			'mcc-settings',
			array( __CLASS__, 'render' )
		);

		add_submenu_page(
			'mcc-settings',
			__( 'Услуги', 'mete-clinic-content' ),
			__( 'Услуги', 'mete-clinic-content' ),
			'edit_posts',
			'edit.php?post_type=' . MCC_Post_Types::SERVICE
		);

		add_submenu_page(
			'mcc-settings',
			__( 'Категории услуг', 'mete-clinic-content' ),
			__( 'Категории услуг', 'mete-clinic-content' ),
			'manage_categories',
			'edit-tags.php?taxonomy=' . MCC_Post_Types::TAX . '&post_type=' . MCC_Post_Types::SERVICE
		);

		add_submenu_page(
			'mcc-settings',
			__( 'Специалисты', 'mete-clinic-content' ),
			__( 'Специалисты', 'mete-clinic-content' ),
			'edit_posts',
			'edit.php?post_type=' . MCC_Post_Types::SPECIALIST
		);
	}

	/**
	 * Settings page CSS.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public static function assets( $hook ) {
		if ( 'toplevel_page_mcc-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'mcc-admin', MCC_URL . 'assets/css/admin.css', array(), MCC_VERSION );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав для просмотра этой страницы.', 'mete-clinic-content' ) );
		}

		$settings = self::get();
		?>
		<div class="wrap mcc-settings">
			<h1><?php esc_html_e( 'Настройки сайта MÉTE CLINIC', 'mete-clinic-content' ); ?></h1>

			<?php if ( isset( $_GET['mcc-imported'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Исходные данные обработаны. Существующие записи не дублировались.', 'mete-clinic-content' ); ?></p></div>
			<?php endif; ?>

			<?php
			if ( isset( $_GET['mcc-catalog'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$mcc_result = get_transient( MCC_Catalog_Import::RESULT . get_current_user_id() );

				if ( is_array( $mcc_result ) ) {
					delete_transient( MCC_Catalog_Import::RESULT . get_current_user_id() );
					?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php
							printf(
								/* translators: 1: categories created, 2: categories existed, 3: services created, 4: services skipped. */
								esc_html__( 'Каталог обработан. Категорий создано: %1$d, уже было: %2$d. Услуг создано: %3$d, пропущено: %4$d.', 'mete-clinic-content' ),
								(int) $mcc_result['categories_created'],
								(int) $mcc_result['categories_exists'],
								(int) $mcc_result['services_created'],
								(int) $mcc_result['services_skipped']
							);
							?>
						</p>
						<?php if ( ! empty( $mcc_result['errors'] ) ) : ?>
							<ul>
								<?php foreach ( $mcc_result['errors'] as $mcc_error ) : ?>
									<li><?php echo esc_html( $mcc_error ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<?php
				}
			}
			?>

			<p class="mcc-settings__intro">
				<?php esc_html_e( 'Услуги и специалисты редактируются в разделах меню слева. Здесь — контакты, ссылка записи, карта и блок отзывов.', 'mete-clinic-content' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php settings_fields( self::GROUP ); ?>

				<h2 class="title"><?php esc_html_e( 'Контакты', 'mete-clinic-content' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'phone', __( 'Телефон (как показывать)', 'mete-clinic-content' ), __( 'Например: +7 (922) 399-14-46', 'mete-clinic-content' ) );
					self::field( 'phone_tel', __( 'Телефон для ссылки tel:', 'mete-clinic-content' ), __( 'Только цифры и плюс: +79223991446', 'mete-clinic-content' ) );
					self::field( 'address', __( 'Адрес', 'mete-clinic-content' ) );
					self::field( 'working_hours', __( 'График работы', 'mete-clinic-content' ), __( 'Если поле пустое, строка на сайте не выводится.', 'mete-clinic-content' ) );
					self::field( 'max_url', __( 'Ссылка на MAX', 'mete-clinic-content' ), __( 'Если пусто, кнопки MAX не показываются.', 'mete-clinic-content' ), 'url' );
					self::textarea( 'messengers', __( 'Другие мессенджеры', 'mete-clinic-content' ), __( 'По одному в строке: Название | https://ссылка', 'mete-clinic-content' ), 4 );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Онлайн-запись Yclients', 'mete-clinic-content' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'booking_url', __( 'Основная ссылка записи', 'mete-clinic-content' ), __( 'Используется во всех кнопках записи, если у услуги не задана своя ссылка.', 'mete-clinic-content' ), 'url' );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Карта', 'mete-clinic-content' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'map_embed_url', __( 'URL карты (src iframe)', 'mete-clinic-content' ), __( 'Только адрес из официального кода встраивания карты, не HTML.', 'mete-clinic-content' ), 'url' );

					$provider = isset( $settings['map_provider'] ) ? (string) $settings['map_provider'] : '2gis';
					?>
					<tr>
						<th scope="row"><label for="mcc-map-provider"><?php esc_html_e( 'Подпись под картой', 'mete-clinic-content' ); ?></label></th>
						<td>
							<select id="mcc-map-provider" name="<?php echo esc_attr( self::OPTION . '[map_provider]' ); ?>">
								<option value="2gis" <?php selected( $provider, '2gis' ); ?>><?php esc_html_e( '2ГИС', 'mete-clinic-content' ); ?></option>
								<option value="yandex" <?php selected( $provider, 'yandex' ); ?>><?php esc_html_e( 'Яндекс Карты', 'mete-clinic-content' ); ?></option>
								<option value="none" <?php selected( $provider, 'none' ); ?>><?php esc_html_e( 'Без подписи', 'mete-clinic-content' ); ?></option>
							</select>
						</td>
					</tr>
					<?php
					self::field( 'map_url', __( 'Ссылка на карточку в 2ГИС', 'mete-clinic-content' ), __( 'Если пусто, ссылка «Открыть в 2ГИС» не выводится.', 'mete-clinic-content' ), 'url' );
					?>
				</table>

				<h2 class="title"><?php esc_html_e( 'Отзывы и рейтинг', 'mete-clinic-content' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'reviews_title', __( 'Заголовок блока', 'mete-clinic-content' ), __( 'По умолчанию: «Отзывы и рейтинг».', 'mete-clinic-content' ) );
					self::textarea( 'reviews_text', __( 'Текст блока', 'mete-clinic-content' ), '', 3 );
					self::field( 'reviews_embed_url', __( 'URL официального виджета (src iframe)', 'mete-clinic-content' ), __( 'Предпочтительный способ: адрес iframe официального виджета 2ГИС.', 'mete-clinic-content' ), 'url' );
					self::field( 'reviews_url', __( 'Ссылка на карточку с отзывами', 'mete-clinic-content' ), '', 'url' );
					self::textarea( 'reviews_widget_html', __( 'Код официального виджета', 'mete-clinic-content' ), __( 'Используется, только если не заполнен URL виджета. Код принимается от администратора и выводится как есть.', 'mete-clinic-content' ), 5 );
					?>
				</table>

				<p class="mcc-settings__note">
					<?php esc_html_e( 'Если не заполнены ни URL виджета, ни его код, блок отзывов на сайте не выводится.', 'mete-clinic-content' ); ?>
				</p>

				<?php submit_button( __( 'Сохранить настройки', 'mete-clinic-content' ) ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Исходные данные сайта', 'mete-clinic-content' ); ?></h2>
			<p><?php esc_html_e( 'Повторный импорт добавляет только отсутствующие услуги и специалистов. Существующие записи не изменяются.', 'mete-clinic-content' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'mcc_seed_demo' ); ?>
				<input type="hidden" name="action" value="mcc_seed_demo">
				<?php submit_button( __( 'Импортировать исходные данные', 'mete-clinic-content' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Каталог услуг', 'mete-clinic-content' ); ?></h2>
			<p><?php esc_html_e( 'Полный прайс: 16 категорий и 146 услуг с ценами, длительностью и описаниями. Повторный запуск не создаёт дублей и не изменяет уже существующие записи.', 'mete-clinic-content' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( MCC_Catalog_Import::ACTION ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( MCC_Catalog_Import::ACTION ); ?>">
				<?php submit_button( __( 'Импортировать каталог услуг', 'mete-clinic-content' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Text or URL input row.
	 *
	 * @param string $key         Setting key.
	 * @param string $label       Label.
	 * @param string $description Description.
	 * @param string $type        Input type.
	 * @return void
	 */
	private static function field( $key, $label, $description = '', $type = 'text' ) {
		$settings = self::get();
		$value    = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
		?>
		<tr>
			<th scope="row"><label for="mcc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input
					type="<?php echo esc_attr( $type ); ?>"
					id="mcc-<?php echo esc_attr( $key ); ?>"
					name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					class="regular-text"
				>
				<?php if ( '' !== $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Textarea row.
	 *
	 * @param string $key         Setting key.
	 * @param string $label       Label.
	 * @param string $description Description.
	 * @param int    $rows        Rows.
	 * @return void
	 */
	private static function textarea( $key, $label, $description = '', $rows = 4 ) {
		$settings = self::get();
		$value    = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
		?>
		<tr>
			<th scope="row"><label for="mcc-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<textarea
					id="mcc-<?php echo esc_attr( $key ); ?>"
					name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>"
					rows="<?php echo esc_attr( (string) $rows ); ?>"
					class="large-text code"
				><?php echo esc_textarea( $value ); ?></textarea>
				<?php if ( '' !== $description ) : ?>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param mixed $input Submitted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = array();

		$text_fields = array( 'phone', 'phone_tel', 'address', 'working_hours', 'reviews_title' );

		foreach ( $text_fields as $key ) {
			$clean[ $key ] = isset( $input[ $key ] )
				? sanitize_text_field( wp_unslash( $input[ $key ] ) )
				: '';
		}

		$textarea_fields = array( 'messengers', 'reviews_text' );

		foreach ( $textarea_fields as $key ) {
			$clean[ $key ] = isset( $input[ $key ] )
				? sanitize_textarea_field( wp_unslash( $input[ $key ] ) )
				: '';
		}

		$url_fields = array( 'booking_url', 'max_url', 'map_embed_url', 'map_url', 'reviews_embed_url', 'reviews_url' );

		foreach ( $url_fields as $key ) {
			$clean[ $key ] = isset( $input[ $key ] )
				? MCC_Sanitize::url( wp_unslash( $input[ $key ] ) )
				: '';
		}

		$provider = isset( $input['map_provider'] )
			? sanitize_key( wp_unslash( $input['map_provider'] ) )
			: '2gis';

		$clean['map_provider'] = in_array( $provider, array( '2gis', 'yandex', 'none' ), true )
			? $provider
			: '2gis';

		$clean['reviews_widget_html'] = isset( $input['reviews_widget_html'] )
			? MCC_Sanitize::widget_html( wp_unslash( $input['reviews_widget_html'] ) )
			: '';

		return wp_parse_args( $clean, self::defaults() );
	}
}
