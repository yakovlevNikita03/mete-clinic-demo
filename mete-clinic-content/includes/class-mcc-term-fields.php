<?php
/**
 * Service category fields: display order and category image.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds order and image controls to the service category taxonomy.
 *
 * Таксономия остаётся штатной: создание, переименование и удаление категорий
 * выполняется стандартными экранами WordPress, здесь добавляются только два
 * поля, которые использует тема.
 */
class MCC_Term_Fields {

	/**
	 * Category order meta key.
	 */
	const META_ORDER = 'mcc_cat_order';

	/**
	 * Category image meta key (attachment ID).
	 */
	const META_IMAGE = 'mcc_cat_image';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( MCC_Post_Types::TAX . '_add_form_fields', array( __CLASS__, 'add_fields' ) );
		add_action( MCC_Post_Types::TAX . '_edit_form_fields', array( __CLASS__, 'edit_fields' ) );

		add_action( 'created_' . MCC_Post_Types::TAX, array( __CLASS__, 'save' ) );
		add_action( 'edited_' . MCC_Post_Types::TAX, array( __CLASS__, 'save' ) );

		add_filter( 'manage_edit-' . MCC_Post_Types::TAX . '_columns', array( __CLASS__, 'columns' ) );
		add_filter( 'manage_' . MCC_Post_Types::TAX . '_custom_column', array( __CLASS__, 'column' ), 10, 3 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Fields on the "add category" screen.
	 *
	 * @param string $taxonomy Current taxonomy.
	 * @return void
	 */
	public static function add_fields( $taxonomy ) {
		unset( $taxonomy );

		wp_nonce_field( 'mcc_term_meta', 'mcc_term_meta_nonce' );
		?>
		<div class="form-field mcc-term-field">
			<label for="mcc-cat-order"><?php esc_html_e( 'Порядок категории', 'mete-clinic-content' ); ?></label>
			<input type="number" name="mcc_cat_order" id="mcc-cat-order" value="0" min="0" max="9999" step="1">
			<p><?php esc_html_e( 'Меньше число — раньше карточка направления на главной.', 'mete-clinic-content' ); ?></p>
		</div>

		<div class="form-field mcc-term-field">
			<label for="mcc-cat-image"><?php esc_html_e( 'Изображение категории', 'mete-clinic-content' ); ?></label>
			<?php self::image_control( 0 ); ?>
			<p><?php esc_html_e( 'Необязательно. Если пусто, используется встроенная иллюстрация направления.', 'mete-clinic-content' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Fields on the "edit category" screen.
	 *
	 * @param WP_Term $term     Current term.
	 * @param string  $taxonomy Current taxonomy.
	 * @return void
	 */
	public static function edit_fields( $term, $taxonomy ) {
		unset( $taxonomy );

		$order = (string) get_term_meta( $term->term_id, self::META_ORDER, true );
		$image = (int) get_term_meta( $term->term_id, self::META_IMAGE, true );
		?>
		<tr class="form-field mcc-term-field">
			<th scope="row"><label for="mcc-cat-order"><?php esc_html_e( 'Порядок категории', 'mete-clinic-content' ); ?></label></th>
			<td>
				<input type="number" name="mcc_cat_order" id="mcc-cat-order" value="<?php echo esc_attr( '' !== $order ? $order : '0' ); ?>" min="0" max="9999" step="1">
				<p class="description"><?php esc_html_e( 'Меньше число — раньше карточка направления на главной.', 'mete-clinic-content' ); ?></p>
			</td>
		</tr>

		<tr class="form-field mcc-term-field">
			<th scope="row"><label for="mcc-cat-image"><?php esc_html_e( 'Изображение категории', 'mete-clinic-content' ); ?></label></th>
			<td>
				<?php wp_nonce_field( 'mcc_term_meta', 'mcc_term_meta_nonce' ); ?>
				<?php self::image_control( $image ); ?>
				<p class="description"><?php esc_html_e( 'Необязательно. Если пусто, используется встроенная иллюстрация направления.', 'mete-clinic-content' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Image picker markup.
	 *
	 * @param int $image_id Attachment ID.
	 * @return void
	 */
	private static function image_control( $image_id ) {
		$image_id = (int) $image_id;
		$url      = $image_id > 0 ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		$url      = $url ? (string) $url : '';
		?>
		<div class="mcc-term-image" data-mcc-media>
			<input type="hidden" name="mcc_cat_image" id="mcc-cat-image" value="<?php echo esc_attr( $image_id > 0 ? (string) $image_id : '' ); ?>" data-mcc-media-input>

			<img
				class="mcc-term-image__preview"
				data-mcc-media-preview
				<?php echo '' !== $url ? 'src="' . esc_url( $url ) . '"' : ''; ?>
				alt=""
				width="120"
				height="90"
				<?php echo '' !== $url ? '' : ' hidden'; ?>
			>

			<p class="mcc-term-image__actions">
				<button type="button" class="button" data-mcc-media-select><?php esc_html_e( 'Выбрать изображение', 'mete-clinic-content' ); ?></button>
				<button type="button" class="button-link mcc-term-image__remove" data-mcc-media-remove<?php echo '' !== $url ? '' : ' hidden'; ?>><?php esc_html_e( 'Удалить', 'mete-clinic-content' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Persist category fields.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public static function save( $term_id ) {
		$term_id = (int) $term_id;

		if ( $term_id <= 0 || ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		if ( ! isset( $_POST['mcc_term_meta_nonce'] ) || ! is_string( $_POST['mcc_term_meta_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['mcc_term_meta_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'mcc_term_meta' ) ) {
			return;
		}

		$order = isset( $_POST['mcc_cat_order'] ) && is_string( $_POST['mcc_cat_order'] )
			? MCC_Sanitize::order( wp_unslash( $_POST['mcc_cat_order'] ) )
			: 0;

		update_term_meta( $term_id, self::META_ORDER, $order );

		$image_raw = isset( $_POST['mcc_cat_image'] ) && is_string( $_POST['mcc_cat_image'] )
			? sanitize_text_field( wp_unslash( $_POST['mcc_cat_image'] ) )
			: '';

		$image = absint( $image_raw );

		if ( $image > 0 && 'attachment' === get_post_type( $image ) ) {
			update_term_meta( $term_id, self::META_IMAGE, $image );
			return;
		}

		delete_term_meta( $term_id, self::META_IMAGE );
	}

	/**
	 * List table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'name' === $key ) {
				$new['mcc_order'] = __( 'Порядок', 'mete-clinic-content' );
				$new['mcc_image'] = __( 'Изображение', 'mete-clinic-content' );
			}
		}

		return $new;
	}

	/**
	 * List table column content.
	 *
	 * @param string $content Existing content.
	 * @param string $column  Column key.
	 * @param int    $term_id Term ID.
	 * @return string
	 */
	public static function column( $content, $column, $term_id ) {
		if ( 'mcc_order' === $column ) {
			$order = (string) get_term_meta( (int) $term_id, self::META_ORDER, true );

			return esc_html( '' !== $order ? $order : '0' );
		}

		if ( 'mcc_image' === $column ) {
			$image = (int) get_term_meta( (int) $term_id, self::META_IMAGE, true );
			$url   = $image > 0 ? wp_get_attachment_image_url( $image, 'thumbnail' ) : '';

			if ( ! $url ) {
				return '&mdash;';
			}

			return '<img src="' . esc_url( $url ) . '" alt="" width="40" height="40" style="border-radius:4px;object-fit:cover;display:block;">';
		}

		return $content;
	}

	/**
	 * Term screen assets.
	 *
	 * @return void
	 */
	public static function assets() {
		$screen = get_current_screen();

		if ( ! $screen instanceof WP_Screen || MCC_Post_Types::TAX !== (string) $screen->taxonomy ) {
			return;
		}

		if ( ! in_array( (string) $screen->base, array( 'edit-tags', 'term' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'mcc-admin', MCC_URL . 'assets/css/admin.css', array(), MCC_VERSION );
		wp_enqueue_media();
		wp_enqueue_script( 'mcc-admin', MCC_URL . 'assets/js/admin.js', array(), MCC_VERSION, true );

		wp_localize_script(
			'mcc-admin',
			'mccAdmin',
			array(
				'mediaTitle'  => __( 'Изображение категории', 'mete-clinic-content' ),
				'mediaButton' => __( 'Использовать изображение', 'mete-clinic-content' ),
			)
		);
	}
}
