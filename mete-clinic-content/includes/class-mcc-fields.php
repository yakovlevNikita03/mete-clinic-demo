<?php
/**
 * Meta boxes for services and specialists.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and saves custom fields.
 */
class MCC_Fields {

	/**
	 * Price type meta key.
	 */
	const META_PRICE_TYPE = '_mcc_price_type';

	/**
	 * Price meta key.
	 */
	const META_PRICE = '_mcc_price';

	/**
	 * Max price meta key.
	 */
	const META_PRICE_MAX = '_mcc_price_max';

	/**
	 * Duration meta key.
	 */
	const META_DURATION = '_mcc_duration';

	/**
	 * Booking URL meta key.
	 */
	const META_BOOKING_URL = '_mcc_booking_url';

	/**
	 * Yclients service ID meta key.
	 */
	const META_YCLIENTS_SERVICE = '_mcc_yclients_service_id';

	/**
	 * Price note meta key ("Скидка 10% по абонементу").
	 */
	const META_PRICE_NOTE = '_mcc_price_note';

	/**
	 * "Show on home" flag meta key.
	 */
	const META_SHOW_HOME = '_mcc_show_on_home';

	/**
	 * Home order meta key.
	 */
	const META_HOME_ORDER = '_mcc_home_order';

	/**
	 * Specialist role meta key.
	 */
	const META_ROLE = '_mcc_role';

	/**
	 * Specialist education meta key.
	 */
	const META_EDUCATION = '_mcc_education';

	/**
	 * Specialist experience meta key.
	 */
	const META_EXPERIENCE = '_mcc_experience';

	/**
	 * Yclients staff ID meta key.
	 */
	const META_YCLIENTS_STAFF = '_mcc_yclients_staff_id';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_boxes' ) );
		add_action( 'save_post_' . MCC_Post_Types::SERVICE, array( __CLASS__, 'save_service' ), 10, 2 );
		add_action( 'save_post_' . MCC_Post_Types::SPECIALIST, array( __CLASS__, 'save_specialist' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/**
	 * Register meta boxes.
	 *
	 * @return void
	 */
	public static function add_boxes() {
		/*
		 * Категория — штатная таксономия WordPress. Переносим её блоки в
		 * верх боковой колонки, чтобы категория выбиралась рядом с остальными
		 * полями услуги. Колбэк остаётся ядровым: сохранение, чекбоксы и
		 * «Добавить новую категорию» работают штатно.
		 */
		remove_meta_box( MCC_Post_Types::TAX . 'div', MCC_Post_Types::SERVICE, 'side' );

		add_meta_box(
			'mcc-service-category',
			__( 'Категория услуги', 'mete-clinic-content' ),
			'post_categories_meta_box',
			MCC_Post_Types::SERVICE,
			'side',
			'high',
			array( 'taxonomy' => MCC_Post_Types::TAX )
		);

		add_meta_box(
			'mcc-service-details',
			__( 'Параметры услуги', 'mete-clinic-content' ),
			array( __CLASS__, 'service_box' ),
			MCC_Post_Types::SERVICE,
			'normal',
			'high'
		);

		add_meta_box(
			'mcc-specialist-details',
			__( 'Данные специалиста', 'mete-clinic-content' ),
			array( __CLASS__, 'specialist_box' ),
			MCC_Post_Types::SPECIALIST,
			'normal',
			'high'
		);
	}

	/**
	 * Service fields.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function service_box( $post ) {
		wp_nonce_field( 'mcc_service_meta', 'mcc_service_meta_nonce' );

		$type = (string) get_post_meta( $post->ID, self::META_PRICE_TYPE, true );

		if ( ! in_array( $type, array( 'fixed', 'from', 'range', 'free' ), true ) ) {
			$type = 'fixed';
		}

		$price      = (string) get_post_meta( $post->ID, self::META_PRICE, true );
		$price_max  = (string) get_post_meta( $post->ID, self::META_PRICE_MAX, true );
		$duration   = (string) get_post_meta( $post->ID, self::META_DURATION, true );
		$booking    = (string) get_post_meta( $post->ID, self::META_BOOKING_URL, true );
		$yclients   = (string) get_post_meta( $post->ID, self::META_YCLIENTS_SERVICE, true );
		$show_home  = (string) get_post_meta( $post->ID, self::META_SHOW_HOME, true );
		$home_order = (string) get_post_meta( $post->ID, self::META_HOME_ORDER, true );
		$price_note = (string) get_post_meta( $post->ID, self::META_PRICE_NOTE, true );

		$legacy_duration = '' !== $duration && ! preg_match( '/^[0-9]+$/', $duration );

		if ( '' === $home_order && isset( $post->post_status ) && 'auto-draft' === $post->post_status ) {
			$home_order = (string) self::next_home_order();
		}
		?>
		<div class="mcc-box">
			<p class="mcc-box__intro">
				<?php esc_html_e( 'Название — заголовок карточки, краткое описание — поле «Отрывок», полное — основной редактор, изображение — «Изображение записи». Категория выбирается в блоке «Категория услуги» справа.', 'mete-clinic-content' ); ?>
			</p>

			<div class="mcc-grid mcc-grid--price">
				<p class="mcc-field">
					<label for="mcc-price-type"><?php esc_html_e( 'Тип цены', 'mete-clinic-content' ); ?></label>
					<select id="mcc-price-type" name="mcc_price_type" data-mcc-price-type>
						<option value="fixed" <?php selected( $type, 'fixed' ); ?>><?php esc_html_e( 'Фиксированная', 'mete-clinic-content' ); ?></option>
						<option value="from" <?php selected( $type, 'from' ); ?>><?php esc_html_e( '«От»', 'mete-clinic-content' ); ?></option>
						<option value="range" <?php selected( $type, 'range' ); ?>><?php esc_html_e( 'Диапазон', 'mete-clinic-content' ); ?></option>
						<option value="free" <?php selected( $type, 'free' ); ?>><?php esc_html_e( 'Бесплатно', 'mete-clinic-content' ); ?></option>
					</select>
					<span class="mcc-hint"><?php esc_html_e( 'От типа зависит, сколько полей цены заполняется.', 'mete-clinic-content' ); ?></span>
				</p>

				<p class="mcc-field" data-mcc-price-field<?php echo 'free' === $type ? ' hidden' : ''; ?>>
					<label for="mcc-price" data-mcc-price-label>
						<?php echo esc_html( 'fixed' === $type ? __( 'Цена, ₽', 'mete-clinic-content' ) : __( 'Цена «от», ₽', 'mete-clinic-content' ) ); ?>
					</label>
					<input type="text" id="mcc-price" name="mcc_price" value="<?php echo esc_attr( $price ); ?>" inputmode="decimal" placeholder="2290" aria-describedby="mcc-price-hint">
					<span class="mcc-hint" id="mcc-price-hint" data-mcc-price-hint>
						<?php echo esc_html( 'fixed' === $type ? __( 'Одно значение: 2290 или 2290,50. Пустое поле — цена по запросу.', 'mete-clinic-content' ) : __( 'Минимальная цена. На сайте выводится как «от 2 290 ₽».', 'mete-clinic-content' ) ); ?>
					</span>
				</p>

				<p class="mcc-field" data-mcc-price-extra="range"<?php echo 'range' === $type ? '' : ' hidden'; ?>>
					<label for="mcc-price-max"><?php esc_html_e( 'Цена «до», ₽', 'mete-clinic-content' ); ?></label>
					<input type="text" id="mcc-price-max" name="mcc_price_max" value="<?php echo esc_attr( $price_max ); ?>" inputmode="decimal" placeholder="3990">
					<span class="mcc-hint"><?php esc_html_e( 'Только для диапазона и только больше цены «от».', 'mete-clinic-content' ); ?></span>
				</p>

				<p class="mcc-field mcc-field--wide">
					<label for="mcc-price-note"><?php esc_html_e( 'Примечание к цене', 'mete-clinic-content' ); ?></label>
					<input type="text" id="mcc-price-note" name="mcc_price_note" value="<?php echo esc_attr( $price_note ); ?>" placeholder="Скидка 10% по абонементу">
					<span class="mcc-hint"><?php esc_html_e( 'Необязательно. Выводится под ценой: скидки по абонементу, условия курса.', 'mete-clinic-content' ); ?></span>
				</p>
			</div>

			<div class="mcc-grid">
				<p class="mcc-field">
					<label for="mcc-duration"><?php esc_html_e( 'Продолжительность, минут', 'mete-clinic-content' ); ?></label>
					<input type="number" id="mcc-duration" name="mcc_duration" value="<?php echo esc_attr( $legacy_duration ? '' : $duration ); ?>" min="1" max="1440" step="1" inputmode="numeric" placeholder="60">
					<span class="mcc-hint"><?php esc_html_e( 'Целое число минут. Пусто — продолжительность на сайте не выводится.', 'mete-clinic-content' ); ?></span>
					<?php if ( $legacy_duration ) : ?>
						<span class="mcc-hint mcc-hint--warning">
							<?php
							printf(
								/* translators: %s: stored free-form duration text. */
								esc_html__( 'Сейчас сохранено текстом: «%s». Оно останется на сайте, пока вы не введёте число минут.', 'mete-clinic-content' ),
								esc_html( $duration )
							);
							?>
						</span>
					<?php endif; ?>
				</p>
			</div>

			<p class="mcc-field">
				<label for="mcc-booking-url"><?php esc_html_e( 'Ссылка записи в Yclients', 'mete-clinic-content' ); ?></label>
				<input type="url" id="mcc-booking-url" name="mcc_booking_url" value="<?php echo esc_attr( $booking ); ?>" class="widefat" placeholder="https://">
				<span class="mcc-hint"><?php esc_html_e( 'Необязательно. Если пусто, используется общая ссылка записи из настроек сайта.', 'mete-clinic-content' ); ?></span>
			</p>

			<details class="mcc-details">
				<summary><?php esc_html_e( 'Дополнительно', 'mete-clinic-content' ); ?></summary>
				<p class="mcc-field">
					<label for="mcc-yclients-id"><?php esc_html_e( 'ID услуги в Yclients', 'mete-clinic-content' ); ?></label>
					<input type="text" id="mcc-yclients-id" name="mcc_yclients_service_id" value="<?php echo esc_attr( $yclients ); ?>" maxlength="64">
					<span class="mcc-hint"><?php esc_html_e( 'Необязательно. Нужно только для сверки с Yclients.', 'mete-clinic-content' ); ?></span>
				</p>
			</details>

			<fieldset class="mcc-fieldset">
				<legend class="mcc-fieldset__legend"><?php esc_html_e( 'Главная страница', 'mete-clinic-content' ); ?></legend>

				<p class="mcc-field mcc-field--check">
					<input type="checkbox" id="mcc-show-home" name="mcc_show_on_home" value="1" <?php checked( '1', $show_home ); ?>>
					<label for="mcc-show-home"><?php esc_html_e( 'Показывать на главной', 'mete-clinic-content' ); ?></label>
				</p>

				<p class="mcc-field mcc-field--order">
					<label for="mcc-home-order"><?php esc_html_e( 'Порядок среди популярных услуг', 'mete-clinic-content' ); ?></label>
					<input type="number" id="mcc-home-order" name="mcc_home_order" value="<?php echo esc_attr( '' !== $home_order ? $home_order : '0' ); ?>" min="0" max="9999" step="1">
					<span class="mcc-hint"><?php esc_html_e( 'Меньше число — выше в блоке «Популярные услуги».', 'mete-clinic-content' ); ?></span>
				</p>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * Specialist fields.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function specialist_box( $post ) {
		wp_nonce_field( 'mcc_specialist_meta', 'mcc_specialist_meta_nonce' );

		$role       = (string) get_post_meta( $post->ID, self::META_ROLE, true );
		$experience = (string) get_post_meta( $post->ID, self::META_EXPERIENCE, true );
		$education  = (string) get_post_meta( $post->ID, self::META_EDUCATION, true );
		$booking    = (string) get_post_meta( $post->ID, self::META_BOOKING_URL, true );
		$yclients   = (string) get_post_meta( $post->ID, self::META_YCLIENTS_STAFF, true );
		?>
		<div class="mcc-box">
			<p class="mcc-box__intro">
				<?php esc_html_e( 'ФИО — заголовок. Фотография — «Изображение записи». Описание — основной редактор. Порядок — блок «Атрибуты». Активность — статус записи: «Опубликовано» или «Черновик».', 'mete-clinic-content' ); ?>
			</p>

			<p class="mcc-field">
				<label for="mcc-role"><?php esc_html_e( 'Специализация', 'mete-clinic-content' ); ?></label>
				<input type="text" id="mcc-role" name="mcc_role" value="<?php echo esc_attr( $role ); ?>" maxlength="120" class="widefat" placeholder="<?php esc_attr_e( 'например, врач дерматолог-косметолог', 'mete-clinic-content' ); ?>">
			</p>

			<div class="mcc-grid">
				<p class="mcc-field">
					<label for="mcc-experience"><?php esc_html_e( 'Опыт работы', 'mete-clinic-content' ); ?></label>
					<input type="text" id="mcc-experience" name="mcc_experience" value="<?php echo esc_attr( $experience ); ?>" maxlength="60" placeholder="<?php esc_attr_e( 'например, 12 лет', 'mete-clinic-content' ); ?>">
					<span class="mcc-hint"><?php esc_html_e( 'Только подтверждённые данные.', 'mete-clinic-content' ); ?></span>
				</p>
			</div>

			<p class="mcc-field">
				<label for="mcc-education"><?php esc_html_e( 'Образование и квалификация', 'mete-clinic-content' ); ?></label>
				<textarea id="mcc-education" name="mcc_education" rows="3" class="widefat"><?php echo esc_textarea( $education ); ?></textarea>
				<span class="mcc-hint"><?php esc_html_e( 'Оставьте пустым, если подтверждённых данных нет.', 'mete-clinic-content' ); ?></span>
			</p>

			<p class="mcc-field">
				<label for="mcc-specialist-booking"><?php esc_html_e( 'Ссылка записи в Yclients', 'mete-clinic-content' ); ?></label>
				<input type="url" id="mcc-specialist-booking" name="mcc_booking_url" value="<?php echo esc_attr( $booking ); ?>" class="widefat" placeholder="https://">
				<span class="mcc-hint"><?php esc_html_e( 'Профиль специалиста в Yclients. Если пусто, используется общая ссылка записи.', 'mete-clinic-content' ); ?></span>
			</p>

			<details class="mcc-details">
				<summary><?php esc_html_e( 'Дополнительно', 'mete-clinic-content' ); ?></summary>
				<p class="mcc-field">
					<label for="mcc-yclients-staff"><?php esc_html_e( 'ID сотрудника в Yclients', 'mete-clinic-content' ); ?></label>
					<input type="text" id="mcc-yclients-staff" name="mcc_yclients_staff_id" value="<?php echo esc_attr( $yclients ); ?>" maxlength="64">
					<span class="mcc-hint"><?php esc_html_e( 'Необязательно. Нужно только для сверки с Yclients.', 'mete-clinic-content' ); ?></span>
				</p>
			</details>
		</div>
		<?php
	}

	/**
	 * Persist service fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save_service( $post_id, $post ) {
		unset( $post );

		if ( ! self::can_save( $post_id, 'mcc_service_meta', 'mcc_service_meta_nonce' ) ) {
			return;
		}

		$type = sanitize_key( self::post_string( 'mcc_price_type' ) );

		if ( ! in_array( $type, array( 'fixed', 'from', 'range', 'free' ), true ) ) {
			$type = 'fixed';
		}

		update_post_meta( $post_id, self::META_PRICE_TYPE, $type );

		$price_raw = 'free' === $type ? '' : self::post_string( 'mcc_price' );
		$price     = MCC_Sanitize::price( $price_raw );

		if ( MCC_Sanitize::price_is_invalid( $price_raw ) ) {
			self::add_notice( 'price_invalid' );
		}

		self::update_or_delete( $post_id, self::META_PRICE, $price );

		if ( 'range' === $type ) {
			$max_raw   = self::post_string( 'mcc_price_max' );
			$price_max = MCC_Sanitize::price( $max_raw );

			if ( MCC_Sanitize::price_is_invalid( $max_raw ) ) {
				self::add_notice( 'price_max_invalid' );
			}

			if ( '' !== $price && '' !== $price_max && (float) $price_max <= (float) $price ) {
				self::add_notice( 'price_range_invalid' );
			}

			self::update_or_delete( $post_id, self::META_PRICE_MAX, $price_max );
		} else {
			delete_post_meta( $post_id, self::META_PRICE_MAX );
		}

		self::update_or_delete(
			$post_id,
			self::META_PRICE_NOTE,
			sanitize_text_field( self::post_string( 'mcc_price_note' ) )
		);

		$duration_raw = trim( self::post_string( 'mcc_duration' ) );
		$duration     = MCC_Sanitize::duration( $duration_raw );
		$stored       = (string) get_post_meta( $post_id, self::META_DURATION, true );

		if ( '' !== $duration_raw && '' === $duration ) {
			/* Некорректное значение не затирает уже сохранённое. */
			self::add_notice( 'duration_invalid' );
		} elseif ( '' === $duration_raw && '' !== $stored && ! preg_match( '/^[0-9]+$/', $stored ) ) {
			/* Старый свободный текст сохраняем, пока владелец не введёт минуты. */
			unset( $stored );
		} else {
			self::update_or_delete( $post_id, self::META_DURATION, $duration );
		}

		$booking_raw = self::post_string( 'mcc_booking_url' );
		$booking     = MCC_Sanitize::url( $booking_raw );

		if ( '' !== trim( $booking_raw ) && '' === $booking ) {
			self::add_notice( 'booking_invalid' );
		}

		self::update_or_delete( $post_id, self::META_BOOKING_URL, $booking );

		$yclients_raw = trim( self::post_string( 'mcc_yclients_service_id' ) );
		$yclients     = MCC_Sanitize::identifier( $yclients_raw );

		if ( '' !== $yclients_raw && '' === $yclients ) {
			self::add_notice( 'yclients_invalid' );
		}

		self::update_or_delete( $post_id, self::META_YCLIENTS_SERVICE, $yclients );

		$show_home = isset( $_POST['mcc_show_on_home'] ) ? '1' : '';
		self::update_or_delete( $post_id, self::META_SHOW_HOME, $show_home );

		$home_order = MCC_Sanitize::order( self::post_string( 'mcc_home_order' ) );
		update_post_meta( $post_id, self::META_HOME_ORDER, $home_order );
	}

	/**
	 * Persist specialist fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save_specialist( $post_id, $post ) {
		unset( $post );

		if ( ! self::can_save( $post_id, 'mcc_specialist_meta', 'mcc_specialist_meta_nonce' ) ) {
			return;
		}

		$role = isset( $_POST['mcc_role'] )
			? mb_substr( sanitize_text_field( wp_unslash( $_POST['mcc_role'] ) ), 0, 120 )
			: '';
		self::update_or_delete( $post_id, self::META_ROLE, $role );

		$experience = isset( $_POST['mcc_experience'] )
			? mb_substr( sanitize_text_field( wp_unslash( $_POST['mcc_experience'] ) ), 0, 60 )
			: '';
		self::update_or_delete( $post_id, self::META_EXPERIENCE, $experience );

		$education = isset( $_POST['mcc_education'] )
			? sanitize_textarea_field( wp_unslash( $_POST['mcc_education'] ) )
			: '';
		self::update_or_delete( $post_id, self::META_EDUCATION, $education );

		$booking = isset( $_POST['mcc_booking_url'] ) ? MCC_Sanitize::url( wp_unslash( $_POST['mcc_booking_url'] ) ) : '';
		self::update_or_delete( $post_id, self::META_BOOKING_URL, $booking );

		$yclients = isset( $_POST['mcc_yclients_staff_id'] )
			? MCC_Sanitize::identifier( wp_unslash( $_POST['mcc_yclients_staff_id'] ) )
			: '';
		self::update_or_delete( $post_id, self::META_YCLIENTS_STAFF, $yclients );
	}

	/**
	 * Shared save guard.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Nonce action.
	 * @param string $field   Nonce field name.
	 * @return bool
	 */
	private static function can_save( $post_id, $action, $field ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		if ( ! isset( $_POST[ $field ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );

		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Store a value or remove the meta when it is empty.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Meta value.
	 * @return void
	 */
	private static function update_or_delete( $post_id, $key, $value ) {
		if ( '' === (string) $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Read a scalar value from the submitted form.
	 *
	 * @param string $key Field name.
	 * @return string
	 */
	private static function post_string( $key ) {
		if ( ! isset( $_POST[ $key ] ) || ! is_string( $_POST[ $key ] ) ) {
			return '';
		}

		return (string) wp_unslash( $_POST[ $key ] );
	}

	/**
	 * Transient key for the current administrator.
	 *
	 * @return string
	 */
	private static function notice_key() {
		return 'mcc_admin_notices_' . (int) get_current_user_id();
	}

	/**
	 * Queue a validation message for the next screen.
	 *
	 * @param string $code Message code.
	 * @return void
	 */
	private static function add_notice( $code ) {
		$key     = self::notice_key();
		$notices = get_transient( $key );
		$notices = is_array( $notices ) ? $notices : array();

		if ( ! in_array( $code, $notices, true ) ) {
			$notices[] = $code;
		}

		set_transient( $key, $notices, 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Validation messages.
	 *
	 * @return array
	 */
	private static function notice_messages() {
		return array(
			'price_invalid'       => __( 'Цена не сохранена: укажите положительное число, например 2290 или 2290,50.', 'mete-clinic-content' ),
			'price_max_invalid'   => __( 'Цена «до» не сохранена: укажите положительное число, например 3990.', 'mete-clinic-content' ),
			'price_range_invalid' => __( 'Цена «до» должна быть больше цены «от». На сайте будет показана одна цена.', 'mete-clinic-content' ),
			'duration_invalid'    => __( 'Продолжительность не изменена: укажите целое число минут от 1 до 1440.', 'mete-clinic-content' ),
			'booking_invalid'     => __( 'Ссылка записи не сохранена: нужен полный адрес, начинающийся с http:// или https://.', 'mete-clinic-content' ),
			'yclients_invalid'    => __( 'ID Yclients не сохранён: допустимы латинские буквы, цифры, дефис и подчёркивание.', 'mete-clinic-content' ),
		);
	}

	/**
	 * Show queued validation messages on the post screen.
	 *
	 * @return void
	 */
	public static function notices() {
		$screen = get_current_screen();

		if ( ! $screen instanceof WP_Screen || 'post' !== $screen->base ) {
			return;
		}

		$types = array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST );

		if ( ! in_array( (string) $screen->post_type, $types, true ) ) {
			return;
		}

		$key     = self::notice_key();
		$notices = get_transient( $key );

		if ( ! is_array( $notices ) || empty( $notices ) ) {
			return;
		}

		delete_transient( $key );

		$messages = self::notice_messages();

		foreach ( $notices as $code ) {
			if ( ! isset( $messages[ $code ] ) ) {
				continue;
			}

			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				esc_html( $messages[ $code ] )
			);
		}
	}

	/**
	 * Suggested order for a new popular service.
	 *
	 * @return int
	 */
	private static function next_home_order() {
		$query = new WP_Query(
			array(
				'post_type'      => MCC_Post_Types::SERVICE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_HOME_ORDER,
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $query->posts ) ) {
			return 1;
		}

		return (int) get_post_meta( (int) $query->posts[0], self::META_HOME_ORDER, true ) + 1;
	}
}
