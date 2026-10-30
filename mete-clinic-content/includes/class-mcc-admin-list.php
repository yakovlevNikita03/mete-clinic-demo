<?php
/**
 * Service and specialist list table improvements.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters, sorting, quick edit, bulk actions and drag ordering.
 */
class MCC_Admin_List {

	/**
	 * Nonce action for drag ordering.
	 */
	const REORDER = 'mcc_reorder';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'restrict_manage_posts', array( __CLASS__, 'filters' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'query' ) );
		add_filter( 'posts_where', array( __CLASS__, 'where' ), 10, 2 );

		add_filter( 'manage_edit-' . MCC_Post_Types::SERVICE . '_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_filter( 'manage_edit-' . MCC_Post_Types::SPECIALIST . '_sortable_columns', array( __CLASS__, 'sortable' ) );

		add_action( 'quick_edit_custom_box', array( __CLASS__, 'quick_edit' ), 10, 2 );
		add_action( 'save_post_' . MCC_Post_Types::SERVICE, array( __CLASS__, 'save_quick_edit' ), 20, 2 );

		add_filter( 'bulk_actions-edit-' . MCC_Post_Types::SERVICE, array( __CLASS__, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-' . MCC_Post_Types::SERVICE, array( __CLASS__, 'handle_bulk' ), 10, 3 );

		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'admin_action_mcc_duplicate', array( __CLASS__, 'duplicate' ) );

		add_filter( 'post_class', array( __CLASS__, 'row_class' ), 10, 3 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );

		add_action( 'wp_ajax_' . self::REORDER, array( __CLASS__, 'reorder' ) );
	}

	/**
	 * Whether the current list screen belongs to the plugin.
	 *
	 * @return string Empty or post type.
	 */
	private static function current_type() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen instanceof WP_Screen || 'edit' !== $screen->base ) {
			return '';
		}

		$type = (string) $screen->post_type;

		return in_array( $type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true ) ? $type : '';
	}

	/**
	 * Filter controls above the list.
	 *
	 * @param string $post_type Post type.
	 * @return void
	 */
	public static function filters( $post_type ) {
		if ( MCC_Post_Types::SERVICE !== $post_type ) {
			return;
		}

		$current = isset( $_GET['mcc_filter'] ) ? sanitize_key( wp_unslash( $_GET['mcc_filter'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$options = array(
			''               => __( 'Все услуги', 'mete-clinic-content' ),
			'no_price'       => __( 'Без цены', 'mete-clinic-content' ),
			'no_category'    => __( 'Без категории', 'mete-clinic-content' ),
			'no_description' => __( 'Без описания', 'mete-clinic-content' ),
			'home'           => __( 'На главной', 'mete-clinic-content' ),
			'has_booking'    => __( 'Со своей ссылкой записи', 'mete-clinic-content' ),
		);
		?>
		<select name="mcc_filter" id="mcc-filter">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<?php
		$cat = isset( $_GET['mcc_cat'] ) ? sanitize_title( wp_unslash( $_GET['mcc_cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		wp_dropdown_categories(
			array(
				'taxonomy'        => MCC_Post_Types::TAX,
				'name'            => 'mcc_cat',
				'id'              => 'mcc-cat',
				'value_field'     => 'slug',
				'show_option_all' => __( 'Все категории', 'mete-clinic-content' ),
				'selected'        => $cat,
				'hide_empty'      => false,
				'hierarchical'    => true,
				'orderby'         => 'name',
			)
		);
	}

	/**
	 * Apply filters, default order and sorting.
	 *
	 * @param WP_Query $query Query.
	 * @return void
	 */
	public static function query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$type = $query->get( 'post_type' );

		if ( is_array( $type ) ) {
			$type = reset( $type );
		}

		$type = (string) $type;

		if ( ! in_array( $type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen instanceof WP_Screen && 'edit' !== $screen->base ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list filters.
		$filter = isset( $_GET['mcc_filter'] ) ? sanitize_key( wp_unslash( $_GET['mcc_filter'] ) ) : '';
		$cat    = isset( $_GET['mcc_cat'] ) ? sanitize_title( wp_unslash( $_GET['mcc_cat'] ) ) : '';
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$order   = isset( $_GET['order'] ) && 'desc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ? 'DESC' : 'ASC';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( MCC_Post_Types::SERVICE === $type && '' !== $filter ) {
			self::apply_filter( $query, $filter );
		}

		if ( MCC_Post_Types::SERVICE === $type && '' !== $cat ) {
			$query->set(
				'tax_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					array(
						'taxonomy' => MCC_Post_Types::TAX,
						'field'    => 'slug',
						'terms'    => array( $cat ),
					),
				)
			);
		}

		if ( 'mcc_price' === $orderby ) {
			$query->set( 'meta_key', MCC_Fields::META_PRICE ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', $order );
			return;
		}

		if ( 'mcc_home_order' === $orderby ) {
			$query->set( 'meta_key', MCC_Fields::META_HOME_ORDER ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', $order );
			return;
		}

		if ( 'mcc_order' === $orderby ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', $order );
			return;
		}

		/* По умолчанию список идёт в том же порядке, что и на сайте. */
		if ( '' === $orderby ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}

	/**
	 * Filter-specific query parts.
	 *
	 * @param WP_Query $query  Query.
	 * @param string   $filter Filter key.
	 * @return void
	 */
	private static function apply_filter( WP_Query $query, $filter ) {
		if ( 'home' === $filter ) {
			$query->set(
				'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					array(
						'key'     => MCC_Fields::META_SHOW_HOME,
						'value'   => '1',
						'compare' => '=',
					),
				)
			);

			return;
		}

		if ( 'has_booking' === $filter ) {
			$query->set(
				'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					array(
						'key'     => MCC_Fields::META_BOOKING_URL,
						'value'   => '',
						'compare' => '!=',
					),
				)
			);

			return;
		}

		if ( 'no_price' === $filter ) {
			$query->set(
				'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => MCC_Fields::META_PRICE_TYPE,
							'value'   => 'free',
							'compare' => '!=',
						),
						array(
							'key'     => MCC_Fields::META_PRICE_TYPE,
							'compare' => 'NOT EXISTS',
						),
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => MCC_Fields::META_PRICE,
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => MCC_Fields::META_PRICE,
							'value'   => '',
							'compare' => '=',
						),
					),
				)
			);

			return;
		}

		if ( 'no_category' === $filter ) {
			$terms = get_terms(
				array(
					'taxonomy'   => MCC_Post_Types::TAX,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return;
			}

			$query->set(
				'tax_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					array(
						'taxonomy' => MCC_Post_Types::TAX,
						'field'    => 'term_id',
						'terms'    => $terms,
						'operator' => 'NOT IN',
					),
				)
			);

			return;
		}

		if ( 'no_description' === $filter ) {
			$query->set( 'mcc_no_description', 1 );
		}
	}

	/**
	 * Add the "no description" clause.
	 *
	 * @param string   $where WHERE clause.
	 * @param WP_Query $query Query.
	 * @return string
	 */
	public static function where( $where, $query ) {
		if ( ! $query instanceof WP_Query || ! $query->get( 'mcc_no_description' ) ) {
			return $where;
		}

		global $wpdb;

		return $where . " AND ( {$wpdb->posts}.post_content = '' OR {$wpdb->posts}.post_content IS NULL )";
	}

	/**
	 * Sortable columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function sortable( $columns ) {
		$columns['mcc_price']      = 'mcc_price';
		$columns['mcc_home_order'] = 'mcc_home_order';
		$columns['mcc_order']      = 'mcc_order';

		return $columns;
	}

	/**
	 * Quick edit fields.
	 *
	 * @param string $column    Column key.
	 * @param string $post_type Post type.
	 * @return void
	 */
	public static function quick_edit( $column, $post_type ) {
		if ( MCC_Post_Types::SERVICE !== $post_type ) {
			return;
		}
		?>
		<?php if ( 'mcc_price' === $column ) : ?>
			<fieldset class="inline-edit-col mcc-quick">
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Тип цены', 'mete-clinic-content' ); ?></span>
					<select name="mcc_qe_price_type">
						<option value="fixed"><?php esc_html_e( 'Фиксированная', 'mete-clinic-content' ); ?></option>
						<option value="from"><?php esc_html_e( '«От»', 'mete-clinic-content' ); ?></option>
						<option value="range"><?php esc_html_e( 'Диапазон', 'mete-clinic-content' ); ?></option>
						<option value="free"><?php esc_html_e( 'Бесплатно', 'mete-clinic-content' ); ?></option>
					</select>
				</label>
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Цена, ₽', 'mete-clinic-content' ); ?></span>
					<input type="text" name="mcc_qe_price" value="" inputmode="decimal">
				</label>
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Цена до, ₽', 'mete-clinic-content' ); ?></span>
					<input type="text" name="mcc_qe_price_max" value="" inputmode="decimal">
				</label>
			</fieldset>
		<?php endif; ?>

		<?php if ( 'mcc_home_order' === $column ) : ?>
			<fieldset class="inline-edit-col mcc-quick">
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Продолжительность, минут', 'mete-clinic-content' ); ?></span>
					<input type="number" name="mcc_qe_duration" value="" min="1" max="1440" step="1">
				</label>
				<label class="inline-edit-group mcc-quick__check">
					<input type="checkbox" name="mcc_qe_show_home" value="1">
					<span class="checkbox-title"><?php esc_html_e( 'Показывать на главной', 'mete-clinic-content' ); ?></span>
				</label>
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Порядок на главной', 'mete-clinic-content' ); ?></span>
					<input type="number" name="mcc_qe_home_order" value="0" min="0" max="9999" step="1">
				</label>
			</fieldset>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save quick edit fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	public static function save_quick_edit( $post_id, $post ) {
		unset( $post );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['_inline_edit'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) );

		if ( ! wp_verify_nonce( $nonce, 'inlineeditnonce' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['mcc_qe_price_type'] ) ) {
			return;
		}

		$type = sanitize_key( wp_unslash( $_POST['mcc_qe_price_type'] ) );

		if ( ! in_array( $type, array( 'fixed', 'from', 'range', 'free' ), true ) ) {
			$type = 'fixed';
		}

		update_post_meta( $post_id, MCC_Fields::META_PRICE_TYPE, $type );

		if ( 'free' === $type ) {
			delete_post_meta( $post_id, MCC_Fields::META_PRICE );
			delete_post_meta( $post_id, MCC_Fields::META_PRICE_MAX );
		} else {
			$price_raw = isset( $_POST['mcc_qe_price'] ) ? (string) wp_unslash( $_POST['mcc_qe_price'] ) : '';
			$price     = MCC_Sanitize::price( $price_raw );

			if ( '' === trim( $price_raw ) || '' !== $price ) {
				self::store( $post_id, MCC_Fields::META_PRICE, $price );
			}

			$max_raw = isset( $_POST['mcc_qe_price_max'] ) ? (string) wp_unslash( $_POST['mcc_qe_price_max'] ) : '';
			$max     = MCC_Sanitize::price( $max_raw );

			if ( 'range' === $type && ( '' === trim( $max_raw ) || '' !== $max ) ) {
				self::store( $post_id, MCC_Fields::META_PRICE_MAX, $max );
			} elseif ( 'range' !== $type ) {
				delete_post_meta( $post_id, MCC_Fields::META_PRICE_MAX );
			}
		}

		if ( isset( $_POST['mcc_qe_duration'] ) ) {
			$duration_raw = trim( (string) wp_unslash( $_POST['mcc_qe_duration'] ) );

			if ( '' === $duration_raw ) {
				delete_post_meta( $post_id, MCC_Fields::META_DURATION );
			} else {
				$duration = MCC_Sanitize::duration( $duration_raw );

				if ( '' !== $duration ) {
					update_post_meta( $post_id, MCC_Fields::META_DURATION, $duration );
				}
			}
		}

		$show_home = isset( $_POST['mcc_qe_show_home'] ) ? '1' : '';
		self::store( $post_id, MCC_Fields::META_SHOW_HOME, $show_home );

		if ( isset( $_POST['mcc_qe_home_order'] ) ) {
			update_post_meta(
				$post_id,
				MCC_Fields::META_HOME_ORDER,
				MCC_Sanitize::order( wp_unslash( $_POST['mcc_qe_home_order'] ) )
			);
		}
	}

	/**
	 * Store meta or delete it for empty values.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param string $value   Value.
	 * @return void
	 */
	private static function store( $post_id, $key, $value ) {
		if ( '' === (string) $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Bulk actions.
	 *
	 * @param array $actions Actions.
	 * @return array
	 */
	public static function bulk_actions( $actions ) {
		$actions['mcc_show_home'] = __( 'Показать на главной', 'mete-clinic-content' );
		$actions['mcc_hide_home'] = __( 'Убрать с главной', 'mete-clinic-content' );
		$actions['mcc_change_category'] = __( 'Изменить категорию на…', 'mete-clinic-content' );

		return $actions;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param string $redirect Redirect URL.
	 * @param string $action   Action.
	 * @param array  $ids      Post IDs.
	 * @return string
	 */
	public static function handle_bulk( $redirect, $action, $ids ) {
		if ( ! in_array( $action, array( 'mcc_show_home', 'mcc_hide_home', 'mcc_change_category' ), true ) ) {
			return $redirect;
		}

		$ids = array_filter( array_map( 'absint', (array) $ids ) );

		if ( empty( $ids ) ) {
			return $redirect;
		}

		$done = 0;

		if ( 'mcc_change_category' === $action ) {
			$term_id = isset( $_REQUEST['mcc_bulk_cat'] ) ? absint( wp_unslash( $_REQUEST['mcc_bulk_cat'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $term_id <= 0 ) {
				return add_query_arg( 'mcc_bulk_error', 'category', $redirect );
			}

			$term = get_term( $term_id, MCC_Post_Types::TAX );

			if ( ! $term instanceof WP_Term ) {
				return add_query_arg( 'mcc_bulk_error', 'category', $redirect );
			}

			foreach ( $ids as $id ) {
				if ( ! current_user_can( 'edit_post', $id ) ) {
					continue;
				}

				wp_set_object_terms( $id, array( $term_id ), MCC_Post_Types::TAX, false );
				$done++;
			}
		} else {
			$value = 'mcc_show_home' === $action ? '1' : '';

			foreach ( $ids as $id ) {
				if ( ! current_user_can( 'edit_post', $id ) ) {
					continue;
				}

				self::store( $id, MCC_Fields::META_SHOW_HOME, $value );
				$done++;
			}
		}

		return add_query_arg(
			array(
				'mcc_bulk_done' => $done,
				'mcc_bulk_type' => sanitize_key( $action ),
			),
			$redirect
		);
	}

	/**
	 * Row actions: duplicate.
	 *
	 * @param array   $actions Actions.
	 * @param WP_Post $post    Post.
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( ! $post instanceof WP_Post ) {
			return $actions;
		}

		if ( ! in_array( $post->post_type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true ) ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'mcc_duplicate',
					'post'   => $post->ID,
				),
				admin_url( 'admin.php' )
			),
			'mcc_duplicate_' . $post->ID
		);

		$actions['mcc_duplicate'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Дублировать', 'mete-clinic-content' )
		);

		return $actions;
	}

	/**
	 * Duplicate a service or a specialist as a draft.
	 *
	 * @return void
	 */
	public static function duplicate() {
		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;

		if ( $post_id <= 0 ) {
			wp_die( esc_html__( 'Запись не найдена.', 'mete-clinic-content' ) );
		}

		check_admin_referer( 'mcc_duplicate_' . $post_id );

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true ) ) {
			wp_die( esc_html__( 'Эту запись нельзя дублировать.', 'mete-clinic-content' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'mete-clinic-content' ), '', array( 'response' => 403 ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => $post->post_type,
				'post_status'  => 'draft',
				'post_title'   => $post->post_title . ' ' . __( '(копия)', 'mete-clinic-content' ),
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'menu_order'   => (int) $post->menu_order + 1,
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}

		$new_id = (int) $new_id;

		$meta = get_post_meta( $post_id );

		foreach ( $meta as $key => $values ) {
			if ( '_mcc_import_key' === $key ) {
				continue;
			}

			foreach ( (array) $values as $value ) {
				update_post_meta( $new_id, $key, maybe_unserialize( $value ) );
			}
		}

		$terms = wp_get_object_terms( $post_id, MCC_Post_Types::TAX, array( 'fields' => 'ids' ) );

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			wp_set_object_terms( $new_id, array_map( 'intval', $terms ), MCC_Post_Types::TAX, false );
		}

		wp_safe_redirect( admin_url( 'post.php?post=' . $new_id . '&action=edit' ) );
		exit;
	}

	/**
	 * Highlight rows with missing data.
	 *
	 * @param array   $classes Row classes.
	 * @param string  $class   Additional class.
	 * @param int     $post_id Post ID.
	 * @return array
	 */
	public static function row_class( $classes, $class, $post_id ) {
		unset( $class );

		if ( ! is_admin() || MCC_Post_Types::SERVICE !== get_post_type( $post_id ) ) {
			return $classes;
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return $classes;
		}

		$type  = (string) get_post_meta( $post_id, MCC_Fields::META_PRICE_TYPE, true );
		$price = MCC_Sanitize::price( get_post_meta( $post_id, MCC_Fields::META_PRICE, true ) );
		$terms = get_the_terms( $post_id, MCC_Post_Types::TAX );

		if ( ( 'free' !== $type && '' === $price ) || ! is_array( $terms ) || empty( $terms ) ) {
			$classes[] = 'mcc-row-warning';
		}

		return $classes;
	}

	/**
	 * Notices for bulk actions, publish warnings and reorder results.
	 *
	 * @return void
	 */
	public static function notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only notices.
		if ( isset( $_GET['mcc_bulk_done'] ) ) {
			$done = absint( wp_unslash( $_GET['mcc_bulk_done'] ) );

			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %d: number of records. */
						__( 'Обновлено записей: %d.', 'mete-clinic-content' ),
						$done
					)
				)
			);
		}

		if ( isset( $_GET['mcc_bulk_error'] ) ) {
			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				esc_html__( 'Выберите категорию для массового изменения.', 'mete-clinic-content' )
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen instanceof WP_Screen || 'post' !== $screen->base || MCC_Post_Types::SERVICE !== (string) $screen->post_type ) {
			return;
		}

		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $post_id <= 0 || 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		$type  = (string) get_post_meta( $post_id, MCC_Fields::META_PRICE_TYPE, true );
		$price = MCC_Sanitize::price( get_post_meta( $post_id, MCC_Fields::META_PRICE, true ) );
		$terms = get_the_terms( $post_id, MCC_Post_Types::TAX );

		$missing = array();

		if ( 'free' !== $type && '' === $price ) {
			$missing[] = __( 'цена', 'mete-clinic-content' );
		}

		if ( ! is_array( $terms ) || empty( $terms ) ) {
			$missing[] = __( 'категория', 'mete-clinic-content' );
		}

		if ( empty( $missing ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: missing fields. */
					__( 'Услуга опубликована, но не заполнено: %s. На сайте карточка будет выглядеть неполной.', 'mete-clinic-content' ),
					implode( ', ', $missing )
				)
			)
		);
	}

	/**
	 * Save the drag order.
	 *
	 * @return void
	 */
	public static function reorder() {
		check_ajax_referer( self::REORDER, 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Недостаточно прав.', 'mete-clinic-content' ) ), 403 );
		}

		$type  = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';
		$field = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : 'menu_order';
		$ids   = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array();

		if ( ! in_array( $type, array( MCC_Post_Types::SERVICE, MCC_Post_Types::SPECIALIST ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Неизвестный тип записи.', 'mete-clinic-content' ) ), 400 );
		}

		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Нет записей для сортировки.', 'mete-clinic-content' ) ), 400 );
		}

		$paged    = isset( $_POST['paged'] ) ? max( 1, absint( wp_unslash( $_POST['paged'] ) ) ) : 1;
		$per_page = (int) get_user_option( 'edit_' . $type . '_per_page' );
		$per_page = $per_page > 0 ? $per_page : 20;
		$position = ( $paged - 1 ) * $per_page;

		foreach ( $ids as $id ) {
			if ( ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}

			$position++;

			if ( MCC_Post_Types::SPECIALIST === $type ) {
				wp_update_post(
					array(
						'ID'         => $id,
						'menu_order' => $position,
					)
				);
				continue;
			}

			if ( 'home_order' === $field ) {
				update_post_meta( $id, MCC_Fields::META_HOME_ORDER, $position );
			} else {
				wp_update_post(
					array(
						'ID'         => $id,
						'menu_order' => $position,
					)
				);
			}
		}

		wp_send_json_success( array( 'updated' => $position ) );
	}
}
