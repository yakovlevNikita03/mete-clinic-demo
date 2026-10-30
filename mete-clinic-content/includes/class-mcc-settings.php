<?php
/**
 * Site settings page, tools page and the MÉTE CLINIC admin menu.
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
	 * Settings page slug.
	 */
	const PAGE = 'mcc-settings';

	/**
	 * Tools page slug.
	 */
	const TOOLS = 'mcc-tools';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
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
	 * «Обзор» — родительский пункт, который регистрирует MCC_Dashboard.
	 *
	 * @return void
	 */
	public static function menu() {
		$parent = MCC_Dashboard::PAGE;

		add_submenu_page(
			$parent,
			__( 'Услуги', 'mete-clinic-content' ),
			__( 'Услуги', 'mete-clinic-content' ),
			'edit_posts',
			'edit.php?post_type=' . MCC_Post_Types::SERVICE
		);

		add_submenu_page(
			$parent,
			__( 'Категории услуг', 'mete-clinic-content' ),
			__( 'Категории услуг', 'mete-clinic-content' ),
			'manage_categories',
			'edit-tags.php?taxonomy=' . MCC_Post_Types::TAX . '&post_type=' . MCC_Post_Types::SERVICE
		);

		add_submenu_page(
			$parent,
			__( 'Специалисты', 'mete-clinic-content' ),
			__( 'Специалисты', 'mete-clinic-content' ),
			'edit_posts',
			'edit.php?post_type=' . MCC_Post_Types::SPECIALIST
		);

		add_submenu_page(
			$parent,
			__( 'Прайс (Excel)', 'mete-clinic-content' ),
			__( 'Прайс (Excel)', 'mete-clinic-content' ),
			'manage_options',
			MCC_Price_IO::PAGE,
			array( 'MCC_Price_IO', 'render' )
		);

		add_submenu_page(
			$parent,
			__( 'Настройки', 'mete-clinic-content' ),
			__( 'Настройки', 'mete-clinic-content' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);

		add_submenu_page(
			$parent,
			__( 'Инструменты', 'mete-clinic-content' ),
			__( 'Инструменты', 'mete-clinic-content' ),
			'manage_options',
			self::TOOLS,
			array( __CLASS__, 'render_tools' )
		);
	}

	/**
	 * URL of a settings tab.
	 *
	 * @param string $tab Tab slug.
	 * @return string
	 */
	public static function tab_url( $tab ) {
		return add_query_arg(
			array(
				'page' => self::PAGE,
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Settings tabs.
	 *
	 * @return array
	 */
	private static function tabs() {
		return array(
			'contacts' => __( 'Контакты', 'mete-clinic-content' ),
			'booking'  => __( 'Запись и мессенджеры', 'mete-clinic-content' ),
			'map'      => __( 'Карта', 'mete-clinic-content' ),
			'reviews'  => __( 'Отзывы', 'mete-clinic-content' ),
		);
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

		$tabs   = self::tabs();
		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'contacts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $tabs[ $active ] ) ) {
			$active = 'contacts';
		}
		?>
		<div class="wrap mcc-settings">
			<h1><?php esc_html_e( 'Настройки сайта MÉTE CLINIC', 'mete-clinic-content' ); ?></h1>

			<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Настройки сохранены.', 'mete-clinic-content' ); ?></p></div>
			<?php endif; ?>

			<p class="mcc-settings__intro">
				<?php esc_html_e( 'Контакты, ссылка записи, карта и блок отзывов. Услуги и специалисты редактируются в отдельных разделах меню.', 'mete-clinic-content' ); ?>
			</p>

			<nav class="nav-tab-wrapper mcc-tabs" aria-label="<?php esc_attr_e( 'Разделы настроек', 'mete-clinic-content' ); ?>">
				<?php foreach ( $tabs as $tab => $label ) : ?>
					<a
						href="<?php echo esc_url( self::tab_url( $tab ) ); ?>"
						class="nav-tab<?php echo $tab === $active ? ' nav-tab-active' : ''; ?>"
						data-mcc-tab="<?php echo esc_attr( $tab ); ?>"
					><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php settings_fields( self::GROUP ); ?>

				<div class="mcc-panel" data-mcc-panel="contacts"<?php echo 'contacts' === $active ? '' : ' hidden'; ?>>
					<h2 class="title"><?php esc_html_e( 'Контакты', 'mete-clinic-content' ); ?></h2>
					<table class="form-table" role="presentation">
						<?php
						self::field( 'phone', __( 'Телефон (как показывать)', 'mete-clinic-content' ), __( 'Например: +7 (922) 399-14-46', 'mete-clinic-content' ) );
						self::field( 'address', __( 'Адрес', 'mete-clinic-content' ) );
						self::field( 'working_hours', __( 'График работы', 'mete-clinic-content' ), __( 'Если поле пустое, строка на сайте не выводится.', 'mete-clinic-content' ) );
						?>
					</table>

					<details class="mcc-details">
						<summary><?php esc_html_e( 'Дополнительно', 'mete-clinic-content' ); ?></summary>
						<table class="form-table" role="presentation">
							<?php
							self::field( 'phone_tel', __( 'Телефон для ссылки tel:', 'mete-clinic-content' ), __( 'Только цифры и плюс. Если оставить пустым, значение соберётся из телефона выше.', 'mete-clinic-content' ) );
							?>
						</table>
					</details>

					<p class="mcc-preview-line">
						<?php esc_html_e( 'На сайте:', 'mete-clinic-content' ); ?>
						<span class="mcc-preview-value" data-mcc-phone-preview>
							<?php echo esc_html( (string) self::get()['phone'] ); ?>
						</span>
					</p>
				</div>

				<div class="mcc-panel" data-mcc-panel="booking"<?php echo 'booking' === $active ? '' : ' hidden'; ?>>
					<h2 class="title"><?php esc_html_e( 'Онлайн-запись Yclients', 'mete-clinic-content' ); ?></h2>
					<table class="form-table" role="presentation">
						<?php
						self::field( 'booking_url', __( 'Основная ссылка записи', 'mete-clinic-content' ), __( 'Используется во всех кнопках записи, если у услуги не задана своя ссылка.', 'mete-clinic-content' ), 'url' );
						?>
					</table>

					<h2 class="title"><?php esc_html_e( 'MAX и другие мессенджеры', 'mete-clinic-content' ); ?></h2>
					<table class="form-table" role="presentation">
						<?php
						self::field( 'max_url', __( 'Ссылка на MAX', 'mete-clinic-content' ), __( 'Если пусто, кнопки MAX не показываются.', 'mete-clinic-content' ), 'url' );
						self::textarea( 'messengers', __( 'Другие мессенджеры', 'mete-clinic-content' ), __( 'По одному в строке: Название | https://ссылка', 'mete-clinic-content' ), 4 );
						?>
					</table>
				</div>

				<div class="mcc-panel" data-mcc-panel="map"<?php echo 'map' === $active ? '' : ' hidden'; ?>>
					<h2 class="title"><?php esc_html_e( 'Карта', 'mete-clinic-content' ); ?></h2>
					<table class="form-table" role="presentation">
						<?php
						self::field( 'map_embed_url', __( 'URL карты (src iframe)', 'mete-clinic-content' ), __( 'Только адрес из официального кода встраивания карты, не HTML. Пример: https://makemap.2gis.ru/widget?data=…', 'mete-clinic-content' ), 'url' );

						$settings = self::get();
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
				</div>

				<div class="mcc-panel" data-mcc-panel="reviews"<?php echo 'reviews' === $active ? '' : ' hidden'; ?>>
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
				</div>

				<?php submit_button( __( 'Сохранить настройки', 'mete-clinic-content' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the tools page.
	 *
	 * @return void
	 */
	public static function render_tools() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав для просмотра этой страницы.', 'mete-clinic-content' ) );
		}

		$counts = self::catalog_counts();
		?>
		<div class="wrap mcc-tools">
			<h1><?php esc_html_e( 'Инструменты', 'mete-clinic-content' ); ?></h1>

			<?php if ( isset( $_GET['mcc-imported'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Исходные данные обработаны. Существующие записи не дублировались.', 'mete-clinic-content' ); ?></p></div>
			<?php endif; ?>

			<?php
			if ( isset( $_GET['mcc-catalog'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$result = get_transient( MCC_Catalog_Import::RESULT . get_current_user_id() );

				if ( is_array( $result ) ) {
					delete_transient( MCC_Catalog_Import::RESULT . get_current_user_id() );
					?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php
							printf(
								/* translators: 1: categories created, 2: categories existed, 3: services created, 4: services skipped. */
								esc_html__( 'Каталог обработан. Категорий создано: %1$d, уже было: %2$d. Услуг создано: %3$d, пропущено: %4$d.', 'mete-clinic-content' ),
								(int) $result['categories_created'],
								(int) $result['categories_exists'],
								(int) $result['services_created'],
								(int) $result['services_skipped']
							);
							?>
						</p>
						<?php if ( ! empty( $result['errors'] ) ) : ?>
							<ul>
								<?php foreach ( $result['errors'] as $error ) : ?>
									<li><?php echo esc_html( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<?php
				}
			}
			?>

			<p class="mcc-settings__intro">
				<?php esc_html_e( 'Кнопки на этой странице нужны при первом запуске. Повторный запуск безопасен: существующие записи не изменяются и не дублируются.', 'mete-clinic-content' ); ?>
			</p>

			<div class="card mcc-card">
				<h2><?php esc_html_e( 'Исходные данные сайта', 'mete-clinic-content' ); ?></h2>
				<p><?php esc_html_e( 'Категории, услуги и специалисты из утверждённой версии сайта. Добавляются только отсутствующие записи.', 'mete-clinic-content' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'mcc_seed_demo' ); ?>
					<input type="hidden" name="action" value="mcc_seed_demo">
					<?php submit_button( __( 'Импортировать исходные данные', 'mete-clinic-content' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<div class="card mcc-card">
				<h2><?php esc_html_e( 'Полный каталог услуг', 'mete-clinic-content' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: 1: number of categories, 2: number of services. */
						esc_html__( 'Прайс из файла данных: %1$d категорий и %2$d услуг с ценами, длительностью и описаниями. Повторный запуск не создаёт дублей и не изменяет уже существующие записи.', 'mete-clinic-content' ),
						(int) $counts['categories'],
						(int) $counts['services']
					);
					?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( MCC_Catalog_Import::ACTION ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( MCC_Catalog_Import::ACTION ); ?>">
					<?php submit_button( __( 'Импортировать каталог услуг', 'mete-clinic-content' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<div class="card mcc-card">
				<h2><?php esc_html_e( 'Служебная информация', 'mete-clinic-content' ); ?></h2>
				<table class="widefat striped mcc-table">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Версия плагина', 'mete-clinic-content' ); ?></td>
							<td><?php echo esc_html( MCC_VERSION ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Версия WordPress', 'mete-clinic-content' ); ?></td>
							<td><?php echo esc_html( (string) get_bloginfo( 'version' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Импорт и выгрузка XLSX', 'mete-clinic-content' ); ?></td>
							<td>
								<?php
								echo MCC_Sheets::xlsx_available()
									? esc_html__( 'Доступны', 'mete-clinic-content' )
									: esc_html__( 'Недоступны: на сервере нет расширения zip. Работает CSV.', 'mete-clinic-content' );
								?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Файл каталога', 'mete-clinic-content' ); ?></td>
							<td><code><?php echo esc_html( MCC_DIR . 'data/catalog.json' ); ?></code></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Category and service counts in the catalog file.
	 *
	 * @return array
	 */
	private static function catalog_counts() {
		$counts = array(
			'categories' => 0,
			'services'   => 0,
		);

		$file = MCC_DIR . 'data/catalog.json';

		if ( ! file_exists( $file ) ) {
			return $counts;
		}

		$raw  = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		$data = json_decode( (string) $raw, true );

		if ( ! is_array( $data ) ) {
			return $counts;
		}

		$counts['categories'] = count( $data );

		foreach ( $data as $category ) {
			$counts['services'] += isset( $category['services'] ) && is_array( $category['services'] ) ? count( $category['services'] ) : 0;
		}

		return $counts;
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
				<span class="mcc-field-row">
					<input
						type="<?php echo esc_attr( $type ); ?>"
						id="mcc-<?php echo esc_attr( $key ); ?>"
						name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="regular-text"
						data-mcc-setting="<?php echo esc_attr( $key ); ?>"
					>
					<?php if ( 'url' === $type ) : ?>
						<button
							type="button"
							class="button mcc-check-link"
							data-mcc-link-source="mcc-<?php echo esc_attr( $key ); ?>"
						><?php esc_html_e( 'Проверить', 'mete-clinic-content' ); ?></button>
						<span class="mcc-link-result" data-mcc-link-result aria-live="polite"></span>
					<?php endif; ?>
				</span>
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

		$text_fields = array( 'phone', 'address', 'working_hours', 'reviews_title' );

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

		$clean['phone_tel'] = isset( $input['phone_tel'] )
			? self::phone_digits( sanitize_text_field( wp_unslash( $input['phone_tel'] ) ) )
			: '';

		if ( '' === $clean['phone_tel'] && '' !== $clean['phone'] ) {
			$clean['phone_tel'] = self::phone_digits( $clean['phone'] );
		}

		return wp_parse_args( $clean, self::defaults() );
	}

	/**
	 * Keep only digits and the leading plus sign.
	 *
	 * @param string $value Phone.
	 * @return string
	 */
	private static function phone_digits( $value ) {
		$value = preg_replace( '/[^0-9+]/', '', (string) $value );
		$value = (string) $value;

		if ( '' !== $value && '+' !== $value[0] ) {
			$value = '+' . ltrim( $value, '+' );
		}

		return $value;
	}
}
