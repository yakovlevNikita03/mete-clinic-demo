<?php
/**
 * Read API used by the theme.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Queries and formatting for services and specialists.
 */
class MCC_Content {

	/**
	 * Settings array.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return MCC_Settings::get();
	}

	/**
	 * Services query.
	 *
	 * @param array $args Extra WP_Query arguments.
	 * @return WP_Query
	 */
	public static function get_services( $args = array() ) {
		$defaults = array(
			'post_type'           => MCC_Post_Types::SERVICE,
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		return new WP_Query( wp_parse_args( $args, $defaults ) );
	}

	/**
	 * Published services flagged for the home page.
	 *
	 * @param int $limit Optional number of items.
	 * @return WP_Query
	 */
	public static function get_popular_services( $limit = 0 ) {
		$query = self::get_services(
			array(
				'meta_query' => array(
					array(
						'key'     => MCC_Fields::META_SHOW_HOME,
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);

		$posts = $query->posts;

		if ( is_array( $posts ) && count( $posts ) > 1 ) {
			usort( $posts, array( __CLASS__, 'compare_home_order' ) );
		}

		if ( $limit > 0 && is_array( $posts ) ) {
			$posts = array_slice( $posts, 0, (int) $limit );
		}

		$query->posts      = is_array( $posts ) ? $posts : array();
		$query->post_count = count( $query->posts );

		return $query;
	}

	/**
	 * Compare two services by the home page order.
	 *
	 * @param WP_Post $a First post.
	 * @param WP_Post $b Second post.
	 * @return int
	 */
	public static function compare_home_order( $a, $b ) {
		$order_a = (int) get_post_meta( $a->ID, MCC_Fields::META_HOME_ORDER, true );
		$order_b = (int) get_post_meta( $b->ID, MCC_Fields::META_HOME_ORDER, true );

		if ( $order_a === $order_b ) {
			$menu_a = (int) $a->menu_order;
			$menu_b = (int) $b->menu_order;

			if ( $menu_a === $menu_b ) {
				return strcasecmp( (string) $a->post_title, (string) $b->post_title );
			}

			return $menu_a < $menu_b ? -1 : 1;
		}

		return $order_a < $order_b ? -1 : 1;
	}

	/**
	 * Specialists query.
	 *
	 * @param array $args Extra WP_Query arguments.
	 * @return WP_Query
	 */
	public static function get_specialists( $args = array() ) {
		$defaults = array(
			'post_type'           => MCC_Post_Types::SPECIALIST,
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		return new WP_Query( wp_parse_args( $args, $defaults ) );
	}

	/**
	 * Service categories used as the home page directions.
	 *
	 * @param array $args {
	 *     Optional. parent (int), hide_empty (bool).
	 * }
	 * @return WP_Term[]
	 */
	public static function get_service_categories( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'parent'     => 0,
				'hide_empty' => true,
			)
		);

		$terms = get_terms(
			array(
				'taxonomy'   => MCC_Post_Types::TAX,
				'hide_empty' => (bool) $args['hide_empty'],
				'parent'     => (int) $args['parent'],
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		usort( $terms, array( __CLASS__, 'compare_term_order' ) );

		return $terms;
	}

	/**
	 * Every non-empty category for the catalog filter.
	 *
	 * Вложенные категории тоже попадают в фильтр: новая категория появляется
	 * в каталоге автоматически, как только к ней отнесена опубликованная услуга.
	 *
	 * @return WP_Term[]
	 */
	public static function get_service_filters() {
		$terms = get_terms(
			array(
				'taxonomy'   => MCC_Post_Types::TAX,
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		usort( $terms, array( __CLASS__, 'compare_term_order' ) );

		return $terms;
	}

	/**
	 * Compare categories by the configured order.
	 *
	 * @param WP_Term $a First term.
	 * @param WP_Term $b Second term.
	 * @return int
	 */
	public static function compare_term_order( $a, $b ) {
		$order_a = (int) get_term_meta( $a->term_id, 'mcc_cat_order', true );
		$order_b = (int) get_term_meta( $b->term_id, 'mcc_cat_order', true );

		if ( $order_a === $order_b ) {
			return strcasecmp( (string) $a->name, (string) $b->name );
		}

		return $order_a < $order_b ? -1 : 1;
	}

	/**
	 * Categories of one service.
	 *
	 * @param int $post_id Service ID.
	 * @return WP_Term[]
	 */
	public static function get_service_categories_for( $post_id ) {
		$terms = get_the_terms( (int) $post_id, MCC_Post_Types::TAX );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		usort( $terms, array( __CLASS__, 'compare_term_order' ) );

		return $terms;
	}

	/**
	 * Price label for a service.
	 *
	 * @param int $post_id Service ID.
	 * @return string
	 */
	public static function format_price( $post_id ) {
		$type = (string) get_post_meta( (int) $post_id, MCC_Fields::META_PRICE_TYPE, true );

		if ( 'free' === $type ) {
			return __( 'Бесплатно', 'mete-clinic-content' );
		}

		$min = MCC_Sanitize::price( get_post_meta( (int) $post_id, MCC_Fields::META_PRICE, true ) );

		if ( '' === $min ) {
			return '';
		}

		$max  = MCC_Sanitize::price( get_post_meta( (int) $post_id, MCC_Fields::META_PRICE_MAX, true ) );

		$min_value = (float) $min;

		if ( $min_value <= 0 ) {
			return '';
		}

		if ( 'from' === $type ) {
			return sprintf(
				/* translators: %s: price. */
				__( 'от %s', 'mete-clinic-content' ),
				self::money( $min_value )
			);
		}

		if ( 'range' === $type && '' !== $max && (float) $max > $min_value ) {
			return sprintf(
				/* translators: 1: minimum price, 2: maximum price. */
				__( '%1$s – %2$s', 'mete-clinic-content' ),
				self::money( $min_value ),
				self::money( (float) $max )
			);
		}

		return self::money( $min_value );
	}

	/**
	 * Price note for a service ("Скидка 10% по абонементу").
	 *
	 * @param int $post_id Service ID.
	 * @return string
	 */
	public static function price_note( $post_id ) {
		return (string) get_post_meta( (int) $post_id, MCC_Fields::META_PRICE_NOTE, true );
	}

	/**
	 * Format money with the site locale.
	 *
	 * @param float $number Amount.
	 * @return string
	 */
	private static function money( $number ) {
		$decimals = ( floor( $number ) === (float) $number ) ? 0 : 2;

		return number_format_i18n( $number, $decimals ) . ' ₽';
	}

	/**
	 * Duration of a service.
	 *
	 * Хранится числом минут. Старое свободное текстовое значение выводится
	 * как есть, чтобы перенос данных не ломал существующие карточки.
	 *
	 * @param int $post_id Service ID.
	 * @return string
	 */
	public static function get_duration( $post_id ) {
		$raw = trim( (string) get_post_meta( (int) $post_id, MCC_Fields::META_DURATION, true ) );

		if ( '' === $raw ) {
			return '';
		}

		if ( preg_match( '/^[0-9]{1,4}$/', $raw ) ) {
			$minutes = (int) $raw;

			if ( $minutes < 1 ) {
				return '';
			}

			return sprintf(
				/* translators: %s: number of minutes. */
				__( '%s мин', 'mete-clinic-content' ),
				number_format_i18n( $minutes )
			);
		}

		return mb_substr( $raw, 0, 60 );
	}

	/**
	 * Booking URL for a service or a specialist.
	 *
	 * @param int $post_id Optional post ID.
	 * @return string
	 */
	public static function booking_url( $post_id = 0 ) {
		$post_id = (int) $post_id;

		if ( $post_id > 0 ) {
			$custom = MCC_Sanitize::url( get_post_meta( $post_id, MCC_Fields::META_BOOKING_URL, true ) );

			if ( '' !== $custom ) {
				return $custom;
			}
		}

		$settings = self::get_settings();

		return MCC_Sanitize::url( isset( $settings['booking_url'] ) ? $settings['booking_url'] : '' );
	}

	/**
	 * Whether a site-wide booking URL is configured.
	 *
	 * @return bool
	 */
	public static function has_booking() {
		return '' !== self::booking_url();
	}

	/**
	 * Messengers configured in the settings.
	 *
	 * @return array
	 */
	public static function get_messengers() {
		$settings = self::get_settings();
		$raw      = isset( $settings['messengers'] ) ? (string) $settings['messengers'] : '';

		if ( '' === trim( $raw ) ) {
			return array();
		}

		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$items = array();

		foreach ( (array) $lines as $line ) {
			$line = trim( (string) $line );

			if ( '' === $line ) {
				continue;
			}

			$parts = explode( '|', $line, 2 );
			$name  = trim( $parts[0] );
			$url   = isset( $parts[1] ) ? trim( $parts[1] ) : '';

			if ( ! MCC_Sanitize::is_http_url( $url ) ) {
				continue;
			}

			if ( '' === $name ) {
				$name = $url;
			}

			$items[] = array(
				'name'  => mb_substr( $name, 0, 40 ),
				'url'   => $url,
				'label' => sprintf(
					/* translators: %s: messenger name. */
					__( 'Написать в %s', 'mete-clinic-content' ),
					$name
				),
			);
		}

		return $items;
	}

	/**
	 * Specialist specialization.
	 *
	 * @param int $post_id Specialist ID.
	 * @return string
	 */
	public static function get_role( $post_id ) {
		return (string) get_post_meta( (int) $post_id, MCC_Fields::META_ROLE, true );
	}

	/**
	 * Specialist experience.
	 *
	 * @param int $post_id Specialist ID.
	 * @return string
	 */
	public static function get_experience( $post_id ) {
		return (string) get_post_meta( (int) $post_id, MCC_Fields::META_EXPERIENCE, true );
	}

	/**
	 * Specialist education.
	 *
	 * @param int $post_id Specialist ID.
	 * @return string
	 */
	public static function get_education( $post_id ) {
		return (string) get_post_meta( (int) $post_id, MCC_Fields::META_EDUCATION, true );
	}
}

/**
 * Site settings.
 *
 * @return array
 */
function mete_content_get_settings() {
	return MCC_Content::get_settings();
}

/**
 * Services query.
 *
 * @param array $args Extra query arguments.
 * @return WP_Query
 */
function mete_content_get_services( $args = array() ) {
	return MCC_Content::get_services( $args );
}

/**
 * Popular services query.
 *
 * @param int $limit Optional limit.
 * @return WP_Query
 */
function mete_content_get_popular_services( $limit = 0 ) {
	return MCC_Content::get_popular_services( (int) $limit );
}

/**
 * Specialists query.
 *
 * @param array $args Extra query arguments.
 * @return WP_Query
 */
function mete_content_get_specialists( $args = array() ) {
	return MCC_Content::get_specialists( $args );
}

/**
 * Service categories.
 *
 * @param array $args Optional query arguments.
 * @return WP_Term[]
 */
function mete_content_get_service_categories( $args = array() ) {
	return MCC_Content::get_service_categories( $args );
}

/**
 * Categories for the catalog filter.
 *
 * @return WP_Term[]
 */
function mete_content_get_service_filters() {
	return MCC_Content::get_service_filters();
}

/**
 * Categories of one service.
 *
 * @param int $post_id Service ID.
 * @return WP_Term[]
 */
function mete_content_get_service_categories_for( $post_id ) {
	return MCC_Content::get_service_categories_for( (int) $post_id );
}

/**
 * Formatted price.
 *
 * @param int $post_id Service ID.
 * @return string
 */
function mete_content_format_price( $post_id ) {
	return MCC_Content::format_price( (int) $post_id );
}

/**
 * Price note.
 *
 * @param int $post_id Service ID.
 * @return string
 */
function mete_content_price_note( $post_id ) {
	return MCC_Content::price_note( (int) $post_id );
}

/**
 * Service duration.
 *
 * @param int $post_id Service ID.
 * @return string
 */
function mete_content_get_duration( $post_id ) {
	return MCC_Content::get_duration( (int) $post_id );
}

/**
 * Booking URL.
 *
 * @param int $post_id Optional post ID.
 * @return string
 */
function mete_content_booking_url( $post_id = 0 ) {
	return MCC_Content::booking_url( (int) $post_id );
}

/**
 * Whether a booking URL is configured.
 *
 * @return bool
 */
function mete_content_has_booking() {
	return MCC_Content::has_booking();
}

/**
 * Messenger list.
 *
 * @return array
 */
function mete_content_get_messengers() {
	return MCC_Content::get_messengers();
}

/**
 * Specialist role.
 *
 * @param int $post_id Specialist ID.
 * @return string
 */
function mete_content_get_role( $post_id ) {
	return MCC_Content::get_role( (int) $post_id );
}

/**
 * Specialist experience.
 *
 * @param int $post_id Specialist ID.
 * @return string
 */
function mete_content_get_experience( $post_id ) {
	return MCC_Content::get_experience( (int) $post_id );
}

/**
 * Specialist education.
 *
 * @param int $post_id Specialist ID.
 * @return string
 */
function mete_content_get_education( $post_id ) {
	return MCC_Content::get_education( (int) $post_id );
}
