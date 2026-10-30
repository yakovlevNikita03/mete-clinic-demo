<?php
/**
 * Price export and import admin screen.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the "Прайс (Excel)" page.
 *
 * Схема работы: выгрузка текущих данных или шаблона, загрузка файла,
 * предпросмотр изменений и только потом применение. Импорт ничего
 * не удаляет, а пустая ячейка не стирает поле без отдельной галочки.
 */
class MCC_Price_IO {

	/**
	 * Page slug.
	 */
	const PAGE = 'mcc-price';

	/**
	 * Export action.
	 */
	const ACTION_EXPORT = 'mcc_price_export';

	/**
	 * Upload action.
	 */
	const ACTION_UPLOAD = 'mcc_price_upload';

	/**
	 * Apply action.
	 */
	const ACTION_APPLY = 'mcc_price_apply';

	/**
	 * Error report action.
	 */
	const ACTION_ERRORS = 'mcc_price_errors';

	/**
	 * Job transient prefix.
	 */
	const JOB = 'mcc_price_job_';

	/**
	 * Result transient prefix.
	 */
	const RESULT = 'mcc_price_result_';

	/**
	 * Job lifetime in seconds.
	 */
	const LIFETIME = 3600;

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_' . self::ACTION_EXPORT, array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_' . self::ACTION_UPLOAD, array( __CLASS__, 'handle_upload' ) );
		add_action( 'admin_post_' . self::ACTION_APPLY, array( __CLASS__, 'handle_apply' ) );
		add_action( 'admin_post_' . self::ACTION_ERRORS, array( __CLASS__, 'handle_errors' ) );
	}

	/**
	 * Page URL.
	 *
	 * @param array $args Extra query arguments.
	 * @return string
	 */
	public static function url( $args = array() ) {
		return add_query_arg(
			array_merge( array( 'page' => self::PAGE ), $args ),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Nonce-protected export link.
	 *
	 * @param string $entity Entity.
	 * @param string $kind   current or template.
	 * @param string $format csv or xlsx.
	 * @return string
	 */
	public static function export_link( $entity, $kind, $format ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => self::ACTION_EXPORT,
					'entity' => $entity,
					'kind'   => $kind,
					'format' => $format,
				),
				admin_url( 'admin-post.php' )
			),
			self::ACTION_EXPORT
		);
	}

	/**
	 * Send a generated file.
	 *
	 * @param string $filename File name.
	 * @param string $contents File contents.
	 * @param string $type     MIME type.
	 * @return void
	 */
	private static function send( $filename, $contents, $type ) {
		nocache_headers();
		header( 'Content-Type: ' . $type );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . strlen( $contents ) );
		echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary download.
		exit;
	}

	/**
	 * Handle the export form.
	 *
	 * @return void
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав для выгрузки прайса.', 'mete-clinic-content' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION_EXPORT );

		$entity = isset( $_GET['entity'] ) ? sanitize_key( wp_unslash( $_GET['entity'] ) ) : '';
		$kind   = isset( $_GET['kind'] ) ? sanitize_key( wp_unslash( $_GET['kind'] ) ) : 'current';
		$format = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : 'csv';
		$date   = gmdate( 'Y-m-d' );

		if ( ! MCC_Price_Data::is_entity( $entity ) ) {
			wp_die( esc_html__( 'Неизвестный тип данных.', 'mete-clinic-content' ) );
		}

		$label = MCC_Price_Data::entities()[ $entity ];
		$slug  = 'template' === $kind ? 'shablon' : 'price';

		if ( 'xlsx' === $format ) {
			if ( ! MCC_Sheets::xlsx_available() ) {
				wp_die( esc_html__( 'На сервере недоступен формат XLSX. Выберите CSV.', 'mete-clinic-content' ) );
			}

			$sheets = array();

			if ( 'template' === $kind ) {
				$sheet_entity = $entity;

				$sheets[] = array(
					'name' => $label,
					'rows' => array(
						MCC_Price_Data::header_row( $sheet_entity ),
						MCC_Price_Data::example_row( $sheet_entity ),
					),
				);
				$sheets[] = array(
					'name' => __( 'Подсказки', 'mete-clinic-content' ),
					'rows' => MCC_Price_Data::help_rows( $sheet_entity ),
				);
			} else {
				foreach ( array_keys( MCC_Price_Data::entities() ) as $sheet_entity ) {
					$sheets[] = array(
						'name' => MCC_Price_Data::entities()[ $sheet_entity ],
						'rows' => array_merge(
							array( MCC_Price_Data::header_row( $sheet_entity ) ),
							MCC_Price_Data::export_rows( $sheet_entity )
						),
					);
				}
			}

			$contents = MCC_Sheets::xlsx_build( $sheets );

			if ( false === $contents ) {
				wp_die( esc_html__( 'Не удалось собрать файл XLSX.', 'mete-clinic-content' ) );
			}

			self::send( 'mete-' . $slug . '-' . $entity . '-' . $date . '.xlsx', $contents, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		}

		$rows = array( MCC_Price_Data::header_row( $entity ) );

		if ( 'template' === $kind ) {
			$rows[] = MCC_Price_Data::example_row( $entity );
		} else {
			$rows = array_merge( $rows, MCC_Price_Data::export_rows( $entity ) );
		}

		self::send( 'mete-' . $slug . '-' . $entity . '-' . $date . '.csv', MCC_Sheets::csv_build( $rows ), 'text/csv; charset=utf-8' );
	}

	/**
	 * Parse an uploaded file into a job.
	 *
	 * @return void
	 */
	public static function handle_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав для импорта прайса.', 'mete-clinic-content' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION_UPLOAD );

		$entity      = isset( $_POST['mcc_entity'] ) ? sanitize_key( wp_unslash( $_POST['mcc_entity'] ) ) : '';
		$mode        = isset( $_POST['mcc_mode'] ) ? sanitize_key( wp_unslash( $_POST['mcc_mode'] ) ) : 'add';
		$clear_empty = isset( $_POST['mcc_clear_empty'] ) && '1' === $_POST['mcc_clear_empty'];

		if ( ! MCC_Price_Data::is_entity( $entity ) ) {
			self::redirect_error( __( 'Выберите тип данных.', 'mete-clinic-content' ) );
		}

		if ( ! isset( $_FILES['mcc_price_file'] ) || ! is_array( $_FILES['mcc_price_file'] ) ) {
			self::redirect_error( __( 'Файл не выбран.', 'mete-clinic-content' ) );
		}

		$file = $_FILES['mcc_price_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- checked below.

		if ( ! isset( $file['tmp_name'], $file['name'], $file['error'], $file['size'] ) ) {
			self::redirect_error( __( 'Файл не выбран.', 'mete-clinic-content' ) );
		}

		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			self::redirect_error( __( 'Не удалось загрузить файл. Попробуйте ещё раз.', 'mete-clinic-content' ) );
		}

		if ( (int) $file['size'] <= 0 || (int) $file['size'] > MCC_Sheets::MAX_BYTES ) {
			self::redirect_error( __( 'Файл пустой или больше 5 МБ.', 'mete-clinic-content' ) );
		}

		$tmp = (string) $file['tmp_name'];

		if ( ! is_uploaded_file( $tmp ) ) {
			self::redirect_error( __( 'Загрузка файла не подтверждена сервером.', 'mete-clinic-content' ) );
		}

		$ext = strtolower( (string) pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, array( 'csv', 'xlsx' ), true ) ) {
			self::redirect_error( __( 'Поддерживаются только файлы CSV и XLSX.', 'mete-clinic-content' ) );
		}

		$sheets = array();

		if ( 'xlsx' === $ext ) {
			if ( ! MCC_Sheets::xlsx_available() ) {
				self::redirect_error( __( 'На сервере недоступен формат XLSX. Сохраните файл как CSV.', 'mete-clinic-content' ) );
			}

			$parsed = MCC_Sheets::xlsx_read( $tmp );

			if ( false === $parsed ) {
				self::redirect_error( __( 'Не удалось прочитать XLSX. Проверьте файл или сохраните его как CSV.', 'mete-clinic-content' ) );
			}

			foreach ( $parsed as $name => $rows ) {
				$match = self::match_sheet( (string) $name );

				if ( null !== $match ) {
					$sheets[ $match ] = $rows;
				}
			}

			if ( empty( $sheets ) ) {
				self::redirect_error( __( 'В книге не найдены листы «Услуги», «Специалисты» или «Категории».', 'mete-clinic-content' ) );
			}
		} else {
			$raw  = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- uploaded temporary file.
			$rows = MCC_Sheets::csv_parse( (string) $raw );

			if ( empty( $rows ) ) {
				self::redirect_error( __( 'Файл пустой или не похож на CSV.', 'mete-clinic-content' ) );
			}

			$sheets[ $entity ] = $rows;
		}

		$job = array(
			'version'     => MCC_Price_Data::PLAN_VERSION,
			'created'     => time(),
			'mode'        => $mode,
			'clear_empty' => $clear_empty,
			'file'        => sanitize_file_name( (string) $file['name'] ),
			'plans'       => array(),
		);

		foreach ( $sheets as $sheet_entity => $rows ) {
			$job['plans'][ $sheet_entity ] = MCC_Price_Data::plan( $sheet_entity, $rows, $mode, $clear_empty );
		}

		$token = self::store_job( $job );

		wp_safe_redirect( self::url( array( 'mcc-preview' => $token ) ) );
		exit;
	}

	/**
	 * Match a sheet name to an entity.
	 *
	 * @param string $name Sheet name.
	 * @return string|null
	 */
	private static function match_sheet( $name ) {
		$normalized = MCC_Price_Data::normalize_header( $name );

		foreach ( MCC_Price_Data::entities() as $entity => $label ) {
			$candidate = MCC_Price_Data::normalize_header( $label );

			if ( $normalized === $candidate ) {
				return $entity;
			}

			$singular = array(
				'услуга'      => MCC_Price_Data::SERVICE,
				'специалист'  => MCC_Price_Data::SPECIALIST,
				'категория'   => MCC_Price_Data::CATEGORY,
				'услуги'      => MCC_Price_Data::SERVICE,
				'специалисты' => MCC_Price_Data::SPECIALIST,
				'категории'   => MCC_Price_Data::CATEGORY,
			);

			if ( isset( $singular[ $normalized ] ) ) {
				return $singular[ $normalized ];
			}
		}

		return null;
	}

	/**
	 * Store a job and return its token.
	 *
	 * @param array $job Job.
	 * @return string
	 */
	private static function store_job( array $job ) {
		$token = wp_generate_password( 16, false, false );
		$job['token'] = $token;

		set_transient( self::JOB . get_current_user_id() . '_' . $token, $job, self::LIFETIME );

		return $token;
	}

	/**
	 * Load a stored job.
	 *
	 * @param string $token Token.
	 * @return array|null
	 */
	private static function load_job( $token ) {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );

		if ( '' === $token ) {
			return null;
		}

		$job = get_transient( self::JOB . get_current_user_id() . '_' . $token );

		return is_array( $job ) && ! empty( $job['plans'] ) ? $job : null;
	}

	/**
	 * Delete a stored job.
	 *
	 * @param string $token Token.
	 * @return void
	 */
	private static function delete_job( $token ) {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );

		if ( '' !== $token ) {
			delete_transient( self::JOB . get_current_user_id() . '_' . $token );
		}
	}

	/**
	 * Apply the stored job.
	 *
	 * @return void
	 */
	public static function handle_apply() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав для импорта прайса.', 'mete-clinic-content' ), '', array( 'response' => 403 ) );
		}

		$token = isset( $_POST['mcc_token'] ) ? sanitize_text_field( wp_unslash( $_POST['mcc_token'] ) ) : '';
		check_admin_referer( self::ACTION_APPLY . '_' . $token );

		$job = self::load_job( $token );

		if ( null === $job ) {
			self::redirect_error( __( 'Черновик импорта устарел. Загрузите файл заново.', 'mete-clinic-content' ) );
		}

		$result = array(
			'created'  => time(),
			'file'     => isset( $job['file'] ) ? (string) $job['file'] : '',
			'entities' => array(),
			'errors'   => array(),
			'backups'  => array(),
		);

		$needs_update = false;

		foreach ( $job['plans'] as $plan ) {
			if ( ! empty( $plan['counts']['update'] ) ) {
				$needs_update = true;
			}
		}

		if ( $needs_update ) {
			$result['backups'] = self::backup( array_keys( $job['plans'] ) );
		}

		foreach ( $job['plans'] as $entity => $plan ) {
			$applied = MCC_Price_Data::apply( $plan );

			$result['entities'][ $entity ] = $applied;

			foreach ( $applied['errors'] as $message ) {
				$result['errors'][] = $message;
			}
		}

		set_transient( self::RESULT . get_current_user_id(), $result, self::LIFETIME );
		self::delete_job( $token );

		wp_safe_redirect( self::url( array( 'mcc-applied' => '1' ) ) );
		exit;
	}

	/**
	 * Save a backup of the current data.
	 *
	 * @param array $entities Entities to include.
	 * @return array List of array( 'name' => string, 'url' => string ).
	 */
	private static function backup( array $entities ) {
		$upload = wp_upload_dir();

		if ( ! empty( $upload['error'] ) ) {
			return array();
		}

		$dir = trailingslashit( $upload['basedir'] ) . 'mcc-backups';
		$base_url = trailingslashit( $upload['baseurl'] ) . 'mcc-backups';

		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return array();
		}

		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Options -Indexes\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- local file.
		}

		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- local file.
		}

		$stamp = gmdate( 'Y-m-d-His' );
		$token = wp_generate_password( 8, false, false );
		$files = array();

		foreach ( $entities as $entity ) {
			if ( ! MCC_Price_Data::is_entity( $entity ) ) {
				continue;
			}

			$rows  = array_merge( array( MCC_Price_Data::header_row( $entity ) ), MCC_Price_Data::export_rows( $entity ) );
			$name  = 'mete-backup-' . $entity . '-' . $stamp . '-' . $token . '.csv';
			$path  = $dir . '/' . $name;
			$saved = file_put_contents( $path, MCC_Sheets::csv_build( $rows ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- local file.

			if ( false === $saved ) {
				continue;
			}

			$files[] = array(
				'name' => $name,
				'url'  => $base_url . '/' . $name,
			);
		}

		return $files;
	}

	/**
	 * Download the error report.
	 *
	 * @return void
	 */
	public static function handle_errors() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'mete-clinic-content' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION_ERRORS );

		$result = get_transient( self::RESULT . get_current_user_id() );

		if ( ! is_array( $result ) || empty( $result['errors'] ) ) {
			wp_die( esc_html__( 'Отчёт об ошибках не найден.', 'mete-clinic-content' ) );
		}

		$rows = array( array( __( 'Ошибка', 'mete-clinic-content' ) ) );

		foreach ( $result['errors'] as $message ) {
			$rows[] = array( $message );
		}

		self::send( 'mete-price-errors-' . gmdate( 'Y-m-d-His' ) . '.csv', MCC_Sheets::csv_build( $rows ), 'text/csv; charset=utf-8' );
	}

	/**
	 * Redirect back with an error message.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private static function redirect_error( $message ) {
		set_transient( 'mcc_price_error_' . get_current_user_id(), (string) $message, 5 * MINUTE_IN_SECONDS );

		wp_safe_redirect( self::url() );
		exit;
	}

	/**
	 * Render the page.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав для просмотра этой страницы.', 'mete-clinic-content' ) );
		}

		echo '<div class="wrap mcc-price">';
		printf( '<h1 class="wp-heading-inline">%s</h1>', esc_html__( 'Прайс (Excel)', 'mete-clinic-content' ) );
		printf(
			'<p class="mcc-page-intro">%s</p>',
			esc_html__( 'Выгрузите текущий прайс, поправьте его в Excel и загрузите обратно. Записи изменятся только после предпросмотра и подтверждения.', 'mete-clinic-content' )
		);

		$notice = get_transient( 'mcc_price_error_' . get_current_user_id() );

		if ( is_string( $notice ) && '' !== $notice ) {
			delete_transient( 'mcc_price_error_' . get_current_user_id() );
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $notice ) );
		}

		$preview = isset( $_GET['mcc-preview'] ) ? sanitize_text_field( wp_unslash( $_GET['mcc-preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$applied = isset( $_GET['mcc-applied'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $applied ) {
			self::render_result();
		} elseif ( '' !== $preview ) {
			$job = self::load_job( $preview );

			if ( null === $job ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html__( 'Черновик импорта устарел. Загрузите файл заново.', 'mete-clinic-content' ) );
			} else {
				self::render_preview( $job );
			}
		}

		self::render_export();
		self::render_upload();
		echo '</div>';
	}

	/**
	 * Export block.
	 *
	 * @return void
	 */
	private static function render_export() {
		$xlsx = MCC_Sheets::xlsx_available();
		?>
		<div class="card mcc-card">
			<h2><?php esc_html_e( 'Выгрузка', 'mete-clinic-content' ); ?></h2>
			<p><?php esc_html_e( 'Скачайте текущий прайс, поправьте цены и загрузите файл обратно. Шаблон — пустой файл с заголовками и одной строкой-примером, которую нужно удалить.', 'mete-clinic-content' ); ?></p>

			<p class="mcc-actions">
				<?php if ( $xlsx ) : ?>
					<a class="button button-primary" href="<?php echo esc_url( self::export_link( MCC_Price_Data::SERVICE, 'current', 'xlsx' ) ); ?>">
						<?php esc_html_e( 'Скачать текущий прайс (XLSX)', 'mete-clinic-content' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( self::export_link( MCC_Price_Data::SERVICE, 'template', 'xlsx' ) ); ?>">
						<?php esc_html_e( 'Скачать шаблон (XLSX)', 'mete-clinic-content' ); ?>
					</a>
				<?php else : ?>
					<span class="mcc-hint"><?php esc_html_e( 'XLSX на этом сервере недоступен: нет расширения zip. Используйте CSV.', 'mete-clinic-content' ); ?></span>
				<?php endif; ?>
			</p>

			<table class="widefat striped mcc-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Данные', 'mete-clinic-content' ); ?></th>
						<th><?php esc_html_e( 'Скачать данные', 'mete-clinic-content' ); ?></th>
						<th><?php esc_html_e( 'Скачать шаблон', 'mete-clinic-content' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( MCC_Price_Data::entities() as $entity => $label ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $label ); ?></strong></td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( self::export_link( $entity, 'current', 'csv' ) ); ?>">CSV</a>
								<?php if ( $xlsx ) : ?>
									<a class="button button-small" href="<?php echo esc_url( self::export_link( $entity, 'current', 'xlsx' ) ); ?>">XLSX</a>
								<?php endif; ?>
							</td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( self::export_link( $entity, 'template', 'csv' ) ); ?>">CSV</a>
								<?php if ( $xlsx ) : ?>
									<a class="button button-small" href="<?php echo esc_url( self::export_link( $entity, 'template', 'xlsx' ) ); ?>">XLSX</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="mcc-hint">
				<?php esc_html_e( 'CSV открывается в Excel. Если русские буквы выглядят как «кракозябры», откройте файл через «Данные → Из текста» и выберите кодировку UTF-8 и разделитель «точка с запятой».', 'mete-clinic-content' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Upload block.
	 *
	 * @return void
	 */
	private static function render_upload() {
		?>
		<div class="card mcc-card">
			<h2><?php esc_html_e( 'Загрузка', 'mete-clinic-content' ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( self::ACTION_UPLOAD ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_UPLOAD ); ?>">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="mcc-price-file"><?php esc_html_e( 'Файл', 'mete-clinic-content' ); ?></label></th>
						<td>
							<input type="file" id="mcc-price-file" name="mcc_price_file" accept=".csv,.xlsx" required>
							<p class="description"><?php esc_html_e( 'CSV или XLSX, до 5 МБ. В книге XLSX листы называются «Услуги», «Специалисты» и «Категории»; обрабатываются все найденные листы.', 'mete-clinic-content' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mcc-price-entity"><?php esc_html_e( 'Тип данных для CSV', 'mete-clinic-content' ); ?></label></th>
						<td>
							<select id="mcc-price-entity" name="mcc_entity">
								<?php foreach ( MCC_Price_Data::entities() as $entity => $label ) : ?>
									<option value="<?php echo esc_attr( $entity ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Для XLSX не используется: тип определяется по названию листа.', 'mete-clinic-content' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Режим', 'mete-clinic-content' ); ?></th>
						<td>
							<fieldset class="mcc-radio">
								<label><input type="radio" name="mcc_mode" value="add" checked> <?php esc_html_e( 'Только добавить новые (ничего не перезаписывает)', 'mete-clinic-content' ); ?></label>
								<label><input type="radio" name="mcc_mode" value="update"> <?php esc_html_e( 'Только обновить существующие', 'mete-clinic-content' ); ?></label>
								<label><input type="radio" name="mcc_mode" value="both"> <?php esc_html_e( 'Добавить и обновить', 'mete-clinic-content' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Пустые ячейки', 'mete-clinic-content' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="mcc_clear_empty" value="1">
								<?php esc_html_e( 'Пустая ячейка очищает поле', 'mete-clinic-content' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'По умолчанию пустая ячейка означает «не менять». Включайте очистку только осознанно.', 'mete-clinic-content' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Проверить файл', 'mete-clinic-content' ), 'primary', 'submit', false ); ?>
				<span class="mcc-hint"><?php esc_html_e( 'Сначала покажем предпросмотр. Записи изменятся только после кнопки «Применить».', 'mete-clinic-content' ); ?></span>
			</form>
		</div>
		<?php
	}

	/**
	 * Preview block.
	 *
	 * @param array $job Job.
	 * @return void
	 */
	private static function render_preview( array $job ) {
		$token = isset( $job['token'] ) ? (string) $job['token'] : '';
		$total = array(
			'create' => 0,
			'update' => 0,
			'same'   => 0,
			'error'  => 0,
		);

		foreach ( $job['plans'] as $plan ) {
			foreach ( $total as $key => $value ) {
				$total[ $key ] = $value + (int) $plan['counts'][ $key ];
			}
		}
		?>
		<div class="card mcc-card mcc-card--preview">
			<h2><?php esc_html_e( 'Предпросмотр', 'mete-clinic-content' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: uploaded file name. */
					esc_html__( 'Файл: %s. Записи ещё не изменены.', 'mete-clinic-content' ),
					esc_html( isset( $job['file'] ) ? (string) $job['file'] : '' )
				);
				?>
			</p>

			<p class="mcc-summary">
				<span class="mcc-badge mcc-badge--create"><?php printf( esc_html__( 'Создастся: %d', 'mete-clinic-content' ), (int) $total['create'] ); ?></span>
				<span class="mcc-badge mcc-badge--update"><?php printf( esc_html__( 'Изменится: %d', 'mete-clinic-content' ), (int) $total['update'] ); ?></span>
				<span class="mcc-badge"><?php printf( esc_html__( 'Без изменений: %d', 'mete-clinic-content' ), (int) $total['same'] ); ?></span>
				<span class="mcc-badge mcc-badge--error"><?php printf( esc_html__( 'Ошибок: %d', 'mete-clinic-content' ), (int) $total['error'] ); ?></span>
			</p>

			<?php foreach ( $job['plans'] as $entity => $plan ) : ?>
				<?php self::render_plan_table( $entity, $plan ); ?>
			<?php endforeach; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::ACTION_APPLY . '_' . $token ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_APPLY ); ?>">
				<input type="hidden" name="mcc_token" value="<?php echo esc_attr( $token ); ?>">

				<?php if ( $total['create'] + $total['update'] > 0 ) : ?>
					<?php submit_button( __( 'Применить изменения', 'mete-clinic-content' ), 'primary', 'submit', false ); ?>
				<?php else : ?>
					<p class="mcc-hint"><?php esc_html_e( 'Менять нечего. Проверьте режим и содержимое файла.', 'mete-clinic-content' ); ?></p>
				<?php endif; ?>

				<a class="button button-link" href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Отменить', 'mete-clinic-content' ); ?></a>
			</form>

			<p class="mcc-hint">
				<?php esc_html_e( 'Перед обновлением существующих записей автоматически сохраняется резервная копия в папку mcc-backups в «Медиафайлах».', 'mete-clinic-content' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Table with planned changes for one entity.
	 *
	 * @param string $entity Entity.
	 * @param array  $plan   Plan.
	 * @return void
	 */
	private static function render_plan_table( $entity, array $plan ) {
		$label   = MCC_Price_Data::entities()[ $entity ];
		$rows    = isset( $plan['rows'] ) ? (array) $plan['rows'] : array();
		$headers = isset( $plan['headers'] ) ? (array) $plan['headers'] : array();
		$shown   = 0;
		?>
		<h3><?php echo esc_html( $label ); ?></h3>

		<?php if ( ! empty( $headers['missing'] ) ) : ?>
			<div class="notice notice-error inline">
				<p>
					<?php
					echo esc_html( sprintf(
						/* translators: %s: column names. */
						__( 'В файле нет обязательной колонки: %s.', 'mete-clinic-content' ),
						implode( ', ', array_map( array( 'MCC_Price_Data', 'field_label' ), (array) $headers['missing'] ) )
					) );
					?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $headers['unknown'] ) ) : ?>
			<p class="mcc-hint">
				<?php
				echo esc_html( sprintf(
					/* translators: %s: column names. */
					__( 'Неизвестные колонки пропущены: %s.', 'mete-clinic-content' ),
					implode( ', ', array_map( 'strval', (array) $headers['unknown'] ) )
				) );
				?>
			</p>
		<?php endif; ?>

		<?php if ( empty( $rows ) ) : ?>
			<p class="mcc-hint"><?php esc_html_e( 'Строк нет.', 'mete-clinic-content' ); ?></p>
			<?php return; ?>
		<?php endif; ?>

		<table class="widefat striped mcc-table mcc-table--preview">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Строка', 'mete-clinic-content' ); ?></th>
					<th><?php esc_html_e( 'Действие', 'mete-clinic-content' ); ?></th>
					<th><?php esc_html_e( 'Запись', 'mete-clinic-content' ); ?></th>
					<th><?php esc_html_e( 'Изменения и ошибки', 'mete-clinic-content' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$action = isset( $row['action'] ) ? (string) $row['action'] : 'same';

					if ( 'same' === $action && empty( $row['errors'] ) ) {
						continue;
					}

					if ( $shown >= 300 ) {
						break;
					}

					$shown++;
					?>
					<tr>
						<td><?php echo esc_html( (string) $row['line'] ); ?></td>
						<td>
							<?php
							$labels = array(
								'create' => __( 'Создать', 'mete-clinic-content' ),
								'update' => __( 'Изменить', 'mete-clinic-content' ),
								'same'   => __( 'Без изменений', 'mete-clinic-content' ),
								'error'  => __( 'Ошибка', 'mete-clinic-content' ),
							);

							printf(
								'<span class="mcc-badge mcc-badge--%1$s">%2$s</span>',
								esc_attr( $action ),
								esc_html( isset( $labels[ $action ] ) ? $labels[ $action ] : $action )
							);
							?>
						</td>
						<td>
							<?php echo esc_html( (string) $row['title'] ); ?>
							<?php if ( ! empty( $row['id'] ) ) : ?>
								<span class="mcc-hint">#<?php echo esc_html( (string) $row['id'] ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! empty( $row['errors'] ) ) : ?>
								<ul class="mcc-errors">
									<?php foreach ( (array) $row['errors'] as $message ) : ?>
										<li><?php echo esc_html( (string) $message ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<?php if ( ! empty( $row['changes'] ) ) : ?>
								<ul class="mcc-changes">
									<?php foreach ( (array) $row['changes'] as $change ) : ?>
										<li>
											<strong><?php echo esc_html( (string) $change['field'] ); ?>:</strong>
											<span class="mcc-old"><?php echo esc_html( '' === (string) $change['old'] ? '—' : (string) $change['old'] ); ?></span>
											<span aria-hidden="true">→</span>
											<span class="mcc-new"><?php echo esc_html( (string) $change['new'] ); ?></span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>

							<?php if ( 'same' === $action && empty( $row['errors'] ) && empty( $row['changes'] ) ) : ?>
								<span class="mcc-hint">
									<?php
									echo esc_html(
										isset( $row['data']['reason'] ) && 'exists' === $row['data']['reason']
											? __( 'Уже существует, режим «только добавить».', 'mete-clinic-content' )
											: __( 'Данные совпадают.', 'mete-clinic-content' )
									);
									?>
								</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $shown >= 300 ) : ?>
			<p class="mcc-hint"><?php esc_html_e( 'Показаны первые 300 строк.', 'mete-clinic-content' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Result block after applying.
	 *
	 * @return void
	 */
	private static function render_result() {
		$result = get_transient( self::RESULT . get_current_user_id() );

		if ( ! is_array( $result ) ) {
			return;
		}

		delete_transient( self::RESULT . get_current_user_id() );
		?>
		<div class="card mcc-card mcc-card--result">
			<h2><?php esc_html_e( 'Импорт завершён', 'mete-clinic-content' ); ?></h2>

			<?php
			printf(
				/* translators: %s: uploaded file name. */
				'<p>' . esc_html__( 'Файл: %s', 'mete-clinic-content' ) . '</p>',
				esc_html( isset( $result['file'] ) ? (string) $result['file'] : '' )
			);
			?>

			<table class="widefat striped mcc-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Данные', 'mete-clinic-content' ); ?></th>
						<th><?php esc_html_e( 'Создано', 'mete-clinic-content' ); ?></th>
						<th><?php esc_html_e( 'Обновлено', 'mete-clinic-content' ); ?></th>
						<th><?php esc_html_e( 'Без изменений', 'mete-clinic-content' ); ?></th>
						<th><?php esc_html_e( 'Ошибок', 'mete-clinic-content' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( (array) $result['entities'] as $entity => $counts ) : ?>
						<tr>
							<td><strong><?php echo esc_html( MCC_Price_Data::entities()[ $entity ] ); ?></strong></td>
							<td><?php echo (int) $counts['create']; ?></td>
							<td><?php echo (int) $counts['update']; ?></td>
							<td><?php echo (int) $counts['skip']; ?></td>
							<td><?php echo (int) $counts['error']; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! empty( $result['errors'] ) ) : ?>
				<h3><?php esc_html_e( 'Ошибки', 'mete-clinic-content' ); ?></h3>
				<ul class="mcc-errors">
					<?php foreach ( array_slice( (array) $result['errors'], 0, 50 ) as $message ) : ?>
						<li><?php echo esc_html( (string) $message ); ?></li>
					<?php endforeach; ?>
				</ul>

				<p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', self::ACTION_ERRORS, admin_url( 'admin-post.php' ) ), self::ACTION_ERRORS ) ); ?>">
						<?php esc_html_e( 'Скачать ошибки (CSV)', 'mete-clinic-content' ); ?>
					</a>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $result['backups'] ) ) : ?>
				<h3><?php esc_html_e( 'Резервная копия', 'mete-clinic-content' ); ?></h3>
				<p><?php esc_html_e( 'Состояние до импорта сохранено. Файлы лежат в папке mcc-backups в «Медиафайлах».', 'mete-clinic-content' ); ?></p>
				<ul>
					<?php foreach ( (array) $result['backups'] as $backup ) : ?>
						<li><a href="<?php echo esc_url( (string) $backup['url'] ); ?>"><?php echo esc_html( (string) $backup['name'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<p>
				<a class="button" href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Загрузить ещё файл', 'mete-clinic-content' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mcc-dashboard' ) ); ?>"><?php esc_html_e( 'К обзору', 'mete-clinic-content' ); ?></a>
			</p>
		</div>
		<?php
	}
}
