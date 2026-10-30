<?php
/**
 * Price sheet schema, matching and applying.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns spreadsheet rows into WordPress records.
 *
 * Класс отвечает за три вещи: какие колонки есть в файле, как строка файла
 * сопоставляется с записью и какие изменения нужно внести. Применение
 * выполняется отдельно, после предпросмотра.
 */
class MCC_Price_Data {

	/**
	 * Service entity.
	 */
	const SERVICE = 'service';

	/**
	 * Specialist entity.
	 */
	const SPECIALIST = 'specialist';

	/**
	 * Category entity.
	 */
	const CATEGORY = 'category';

	/**
	 * Planner version stored together with the plan.
	 */
	const PLAN_VERSION = 1;

	/**
	 * Human labels for the entities.
	 *
	 * @return array
	 */
	public static function entities() {
		return array(
			self::SERVICE    => __( 'Услуги', 'mete-clinic-content' ),
			self::SPECIALIST => __( 'Специалисты', 'mete-clinic-content' ),
			self::CATEGORY   => __( 'Категории', 'mete-clinic-content' ),
		);
	}

	/**
	 * Column definitions: key => header.
	 *
	 * @param string $entity Entity.
	 * @return array
	 */
	public static function columns( $entity ) {
		$columns = array(
			self::SERVICE    => array(
				'id'           => __( 'ID', 'mete-clinic-content' ),
				'category'     => __( 'Категория', 'mete-clinic-content' ),
				'title'        => __( 'Название', 'mete-clinic-content' ),
				'price_type'   => __( 'Тип цены', 'mete-clinic-content' ),
				'price'        => __( 'Цена', 'mete-clinic-content' ),
				'price_max'    => __( 'Цена до', 'mete-clinic-content' ),
				'price_note'   => __( 'Примечание к цене', 'mete-clinic-content' ),
				'minutes'      => __( 'Минут', 'mete-clinic-content' ),
				'show_on_home' => __( 'На главной (да/нет)', 'mete-clinic-content' ),
				'home_order'   => __( 'Порядок на главной', 'mete-clinic-content' ),
				'status'       => __( 'Статус (опубликована/скрыта)', 'mete-clinic-content' ),
				'booking_url'  => __( 'Ссылка записи', 'mete-clinic-content' ),
				'description'  => __( 'Описание', 'mete-clinic-content' ),
			),
			self::SPECIALIST => array(
				'id'          => __( 'ID', 'mete-clinic-content' ),
				'title'       => __( 'ФИО', 'mete-clinic-content' ),
				'role'        => __( 'Специализация', 'mete-clinic-content' ),
				'experience'  => __( 'Опыт', 'mete-clinic-content' ),
				'education'   => __( 'Образование', 'mete-clinic-content' ),
				'order'       => __( 'Порядок', 'mete-clinic-content' ),
				'status'      => __( 'Статус (опубликован/скрыт)', 'mete-clinic-content' ),
				'booking_url' => __( 'Ссылка записи', 'mete-clinic-content' ),
			),
			self::CATEGORY   => array(
				'id'          => __( 'ID', 'mete-clinic-content' ),
				'slug'        => __( 'Слаг', 'mete-clinic-content' ),
				'name'        => __( 'Название', 'mete-clinic-content' ),
				'order'       => __( 'Порядок', 'mete-clinic-content' ),
				'description' => __( 'Описание', 'mete-clinic-content' ),
			),
		);

		return isset( $columns[ $entity ] ) ? $columns[ $entity ] : array();
	}

	/**
	 * Header aliases for import.
	 *
	 * @param string $entity Entity.
	 * @return array Normalized header => column key.
	 */
	public static function aliases( $entity ) {
		$common = array(
			'id'    => 'id',
			'номер' => 'id',
		);

		$aliases = array(
			self::SERVICE    => array(
				'категория'           => 'category',
				'категория услуги'    => 'category',
				'название'            => 'title',
				'название услуги'     => 'title',
				'услуга'              => 'title',
				'тип цены'            => 'price_type',
				'тип'                 => 'price_type',
				'цена'                => 'price',
				'цена от'             => 'price',
				'цена до'             => 'price_max',
				'примечание'          => 'price_note',
				'примечание к цене'   => 'price_note',
				'минут'               => 'minutes',
				'продолжительность'   => 'minutes',
				'на главной'          => 'show_on_home',
				'порядок на главной'  => 'home_order',
				'порядок'             => 'home_order',
				'статус'              => 'status',
				'ссылка записи'       => 'booking_url',
				'ссылка записи yclients' => 'booking_url',
				'ссылка yclients'     => 'booking_url',
				'описание'            => 'description',
			),
			self::SPECIALIST => array(
				'фио'                  => 'title',
				'название'             => 'title',
				'специализация'        => 'role',
				'опыт'                 => 'experience',
				'опыт работы'          => 'experience',
				'образование'          => 'education',
				'образование и квалификация' => 'education',
				'порядок'              => 'order',
				'статус'               => 'status',
				'ссылка записи'        => 'booking_url',
				'ссылка записи yclients' => 'booking_url',
			),
			self::CATEGORY   => array(
				'слаг'      => 'slug',
				'slug'      => 'slug',
				'название'  => 'name',
				'порядок'   => 'order',
				'описание'  => 'description',
			),
		);

		return wp_parse_args( isset( $aliases[ $entity ] ) ? $aliases[ $entity ] : array(), $common );
	}

	/**
	 * Example row for the template.
	 *
	 * @param string $entity Entity.
	 * @return array
	 */
	public static function example_row( $entity ) {
		$examples = array(
			self::SERVICE    => array(
				'удалите эту строку',
				'Консультация',
				'Консультация косметолога',
				'fixed',
				'2000',
				'',
				'Бесплатная консультация в день процедуры',
				'30',
				'нет',
				'0',
				'опубликована',
				'',
				'Короткое описание услуги для карточки.',
			),
			self::SPECIALIST => array(
				'удалите эту строку',
				'Иванова Мария Ивановна',
				'врач дерматолог-косметолог',
				'12 лет',
				'Оставьте пустым, если данных нет',
				'0',
				'опубликован',
				'',
			),
			self::CATEGORY   => array(
				'удалите эту строку',
				'konsultaciya',
				'Консультация',
				'10',
				'Оставьте пустым, если описания нет',
			),
		);

		return isset( $examples[ $entity ] ) ? $examples[ $entity ] : array();
	}

	/**
	 * Allowed values for the help sheet.
	 *
	 * @param string $entity Entity.
	 * @return array
	 */
	public static function help_rows( $entity ) {
		$rows = array(
			__( 'Колонка', 'mete-clinic-content' ),
			__( 'Что указывать', 'mete-clinic-content' ),
		);

		if ( self::SERVICE === $entity ) {
			$rows[] = array( 'ID', __( 'Число из этой же выгрузки. Для новой услуги оставьте пустым.', 'mete-clinic-content' ) );
			$rows[] = array( 'Категория', __( 'Название или слаг существующей категории. Новые категории импортируйте отдельно.', 'mete-clinic-content' ) );
			$rows[] = array( 'Тип цены', 'fixed, from, range, free' );
			$rows[] = array( 'Цена', __( 'Число, например 2290 или 2290,50. Для free оставьте пустым.', 'mete-clinic-content' ) );
			$rows[] = array( 'Цена до', __( 'Только для range и только больше цены «от».', 'mete-clinic-content' ) );
			$rows[] = array( 'На главной', __( 'да или нет.', 'mete-clinic-content' ) );
			$rows[] = array( 'Статус', __( 'опубликована или скрыта.', 'mete-clinic-content' ) );
		} elseif ( self::SPECIALIST === $entity ) {
			$rows[] = array( 'ID', __( 'Число из этой же выгрузки. Для нового специалиста оставьте пустым.', 'mete-clinic-content' ) );
			$rows[] = array( 'Статус', __( 'опубликован или скрыт.', 'mete-clinic-content' ) );
		} else {
			$rows[] = array( 'ID', __( 'Число из этой же выгрузки. Для новой категории оставьте пустым.', 'mete-clinic-content' ) );
			$rows[] = array( 'Слаг', __( 'Латиница, цифры и дефис. Пусто — WordPress создаст сам.', 'mete-clinic-content' ) );
		}

		return $rows;
	}

	/**
	 * Whether the entity is known.
	 *
	 * @param string $entity Entity.
	 * @return bool
	 */
	public static function is_entity( $entity ) {
		return array_key_exists( (string) $entity, self::entities() );
	}

	/**
	 * Normalize a header cell for matching.
	 *
	 * @param string $header Header.
	 * @return string
	 */
	public static function normalize_header( $header ) {
		$header = mb_strtolower( trim( (string) $header ), 'UTF-8' );
		$header = preg_replace( '/\([^)]*\)/u', ' ', $header );
		$header = str_replace( array( '*', ':', '«', '»', '"' ), ' ', (string) $header );
		$header = preg_replace( '/\s+/u', ' ', (string) $header );

		return trim( (string) $header, " \t\n\r\0\x0B." );
	}

	/**
	 * Map file columns to keys.
	 *
	 * @param string $entity Entity.
	 * @param array  $header Header row.
	 * @return array { map: array(index => key), unknown: array(index => header), missing: array(key) }
	 */
	public static function map_headers( $entity, array $header ) {
		$aliases = self::aliases( $entity );
		$map     = array();
		$unknown = array();

		foreach ( $header as $index => $label ) {
			$normalized = self::normalize_header( $label );

			if ( '' === $normalized ) {
				continue;
			}

			if ( isset( $aliases[ $normalized ] ) ) {
				$key = $aliases[ $normalized ];

				if ( ! in_array( $key, $map, true ) ) {
					$map[ $index ] = $key;
				}

				continue;
			}

			if ( array_key_exists( $normalized, self::columns( $entity ) ) && ! in_array( $normalized, $map, true ) ) {
				$map[ $index ] = $normalized;
				continue;
			}

			$unknown[ $index ] = (string) $label;
		}

		$required = self::CATEGORY === $entity ? array( 'name' ) : array( 'title' );
		$missing  = array();

		foreach ( $required as $key ) {
			if ( ! in_array( $key, $map, true ) ) {
				$missing[] = $key;
			}
		}

		return array(
			'map'     => $map,
			'unknown' => $unknown,
			'missing' => $missing,
		);
	}

	/**
	 * Convert a data row into keyed values.
	 *
	 * @param array $map  Column map.
	 * @param array $row  Row values.
	 * @return array
	 */
	public static function keyed_row( array $map, array $row ) {
		$values = array();

		foreach ( $map as $index => $key ) {
			$values[ $key ] = isset( $row[ $index ] ) ? trim( (string) $row[ $index ] ) : '';
		}

		return $values;
	}

	/**
	 * Build the change plan.
	 *
	 * @param string $entity      Entity.
	 * @param array  $sheet_rows  Parsed rows including the header row.
	 * @param string $mode        add, update or both.
	 * @param bool   $clear_empty Whether an empty cell clears the field.
	 * @return array
	 */
	public static function plan( $entity, array $sheet_rows, $mode, $clear_empty ) {
		$mode  = in_array( $mode, array( 'add', 'update', 'both' ), true ) ? $mode : 'add';
		$plan  = array(
			'version'     => self::PLAN_VERSION,
			'entity'      => (string) $entity,
			'mode'        => $mode,
			'clear_empty' => (bool) $clear_empty,
			'created'     => time(),
			'rows'        => array(),
			'counts'      => array(
				'create' => 0,
				'update' => 0,
				'same'   => 0,
				'error'  => 0,
			),
			'headers'     => array(),
		);

		if ( empty( $sheet_rows ) ) {
			return $plan;
		}

		$header   = array_shift( $sheet_rows );
		$mapping  = self::map_headers( $entity, (array) $header );

		$plan['headers'] = array(
			'unknown' => array_values( $mapping['unknown'] ),
			'missing' => $mapping['missing'],
		);

		if ( ! empty( $mapping['missing'] ) ) {
			return $plan;
		}

		$line = 1;

		foreach ( $sheet_rows as $row ) {
			$line++;
			$values = self::keyed_row( $mapping['map'], (array) $row );
			$entry  = self::plan_row( $entity, $line, $values, $mode, $clear_empty );

			$plan['rows'][] = $entry;
			$plan['counts'][ $entry['action'] ]++;
		}

		return $plan;
	}

	/**
	 * Plan one row.
	 *
	 * @param string $entity      Entity.
	 * @param int    $line        Line number in the file.
	 * @param array  $values      Keyed values.
	 * @param string $mode        Mode.
	 * @param bool   $clear_empty Clear empty cells.
	 * @return array
	 */
	private static function plan_row( $entity, $line, array $values, $mode, $clear_empty ) {
		$entry = array(
			'line'    => (int) $line,
			'id'      => 0,
			'title'   => '',
			'action'  => 'same',
			'changes' => array(),
			'errors'  => array(),
			'data'    => array(),
		);

		$id = self::parse_id( isset( $values['id'] ) ? $values['id'] : '' );

		if ( false === $id ) {
			$entry['errors'][] = __( 'ID должен быть числом. Строку-пример из шаблона удалите.', 'mete-clinic-content' );

			return self::fail( $entry );
		}

		$entry['id'] = (int) $id;

		if ( self::SERVICE === $entity ) {
			return self::plan_service( $entry, $values, $mode, $clear_empty );
		}

		if ( self::SPECIALIST === $entity ) {
			return self::plan_specialist( $entry, $values, $mode, $clear_empty );
		}

		return self::plan_category( $entry, $values, $mode, $clear_empty );
	}

	/**
	 * Mark an entry as failed.
	 *
	 * @param array $entry Entry.
	 * @return array
	 */
	private static function fail( array $entry ) {
		$entry['action'] = 'error';

		return $entry;
	}

	/**
	 * Parse an ID cell.
	 *
	 * @param string $value Cell value.
	 * @return int|false
	 */
	private static function parse_id( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return 0;
		}

		if ( ! preg_match( '/^[0-9]{1,10}$/', $value ) ) {
			return false;
		}

		return (int) $value;
	}

	/**
	 * Plan a service row.
	 *
	 * @param array  $entry       Entry.
	 * @param array  $values      Values.
	 * @param string $mode        Mode.
	 * @param bool   $clear_empty Clear empty.
	 * @return array
	 */
	private static function plan_service( array $entry, array $values, $mode, $clear_empty ) {
		$provided = array();
		$title    = isset( $values['title'] ) ? trim( (string) $values['title'] ) : '';
		$term     = null;

		if ( '' !== $title ) {
			$provided['title'] = $title;
		}

		if ( isset( $values['category'] ) && '' !== trim( (string) $values['category'] ) ) {
			$term = self::find_category( (string) $values['category'] );

			if ( ! $term instanceof WP_Term ) {
				$entry['errors'][] = sprintf(
					/* translators: %s: category from the file. */
					__( 'Категория «%s» не найдена. Импортируйте категории отдельно или проверьте название.', 'mete-clinic-content' ),
					(string) $values['category']
				);
			}
		}

		$price_type = isset( $values['price_type'] ) ? sanitize_key( (string) $values['price_type'] ) : '';

		if ( '' !== $price_type ) {
			if ( ! in_array( $price_type, array( 'fixed', 'from', 'range', 'free' ), true ) ) {
				$entry['errors'][] = __( 'Тип цены: допустимо fixed, from, range или free.', 'mete-clinic-content' );
			} else {
				$provided['price_type'] = $price_type;
			}
		}

		$price = self::price_cell( isset( $values['price'] ) ? $values['price'] : '', $entry );

		if ( null !== $price ) {
			$provided['price'] = $price;
		}

		$price_max = self::price_cell( isset( $values['price_max'] ) ? $values['price_max'] : '', $entry );

		if ( null !== $price_max ) {
			$provided['price_max'] = $price_max;
		}

		if ( isset( $provided['price'], $provided['price_max'] )
			&& '' !== $provided['price'] && '' !== $provided['price_max']
			&& (float) $provided['price_max'] <= (float) $provided['price'] ) {
			$entry['errors'][] = __( 'Цена «до» должна быть больше цены «от».', 'mete-clinic-content' );
		}

		if ( isset( $values['price_note'] ) ) {
			$note = trim( (string) $values['price_note'] );

			if ( '' !== $note ) {
				$provided['price_note'] = sanitize_text_field( $note );
			}
		}

		if ( isset( $values['minutes'] ) && '' !== trim( (string) $values['minutes'] ) ) {
			$minutes = MCC_Sanitize::duration( $values['minutes'] );

			if ( '' === $minutes ) {
				$entry['errors'][] = __( 'Продолжительность: целое число минут от 1 до 1440.', 'mete-clinic-content' );
			} else {
				$provided['minutes'] = $minutes;
			}
		}

		if ( isset( $values['show_on_home'] ) && '' !== trim( (string) $values['show_on_home'] ) ) {
			$flag = self::parse_flag( (string) $values['show_on_home'] );

			if ( null === $flag ) {
				$entry['errors'][] = __( 'Колонка «На главной»: укажите да или нет.', 'mete-clinic-content' );
			} else {
				$provided['show_on_home'] = $flag;
			}
		}

		if ( isset( $values['home_order'] ) && '' !== trim( (string) $values['home_order'] ) ) {
			if ( ! preg_match( '/^[0-9]{1,4}$/', trim( (string) $values['home_order'] ) ) ) {
				$entry['errors'][] = __( 'Порядок на главной: целое число от 0 до 9999.', 'mete-clinic-content' );
			} else {
				$provided['home_order'] = MCC_Sanitize::order( $values['home_order'] );
			}
		}

		if ( isset( $values['status'] ) && '' !== trim( (string) $values['status'] ) ) {
			$status = self::parse_status( (string) $values['status'] );

			if ( null === $status ) {
				$entry['errors'][] = __( 'Статус: укажите «опубликована» или «скрыта».', 'mete-clinic-content' );
			} else {
				$provided['status'] = $status;
			}
		}

		if ( isset( $values['booking_url'] ) && '' !== trim( (string) $values['booking_url'] ) ) {
			$url = MCC_Sanitize::url( $values['booking_url'] );

			if ( '' === $url ) {
				$entry['errors'][] = __( 'Ссылка записи должна начинаться с http:// или https://.', 'mete-clinic-content' );
			} else {
				$provided['booking_url'] = $url;
			}
		}

		if ( isset( $values['description'] ) ) {
			$description = trim( (string) $values['description'] );

			if ( '' !== $description || $clear_empty ) {
				$provided['description'] = $description;
			}
		}

		if ( ! empty( $entry['errors'] ) ) {
			return self::fail( $entry );
		}

		$current = 0;

		if ( $entry['id'] > 0 ) {
			$current = self::find_service( $entry['id'], '' );
		}

		if ( ! $current && '' !== $title && null !== $term ) {
			$current = self::find_service( 0, $title, (string) $term->slug );
		}

		if ( ! $current && '' !== $title ) {
			$current = self::find_service( 0, $title );
		}

		if ( ! $current && 'update' === $mode ) {
			$entry['errors'][] = __( 'Услуга не найдена, а выбран режим «Только обновить».', 'mete-clinic-content' );

			return self::fail( $entry );
		}

		if ( $current instanceof WP_Post ) {
			$entry['action'] = 'update';
			$entry['id']     = (int) $current->ID;
			$entry['title']  = (string) $current->post_title;

			if ( 'add' === $mode ) {
				$entry['action'] = 'same';
				$entry['data']   = array( 'reason' => 'exists' );

				return $entry;
			}

			$changes = self::diff_service( $current, $provided, $term );

			if ( empty( $changes ) ) {
				$entry['action'] = 'same';
				$entry['data']   = array( 'reason' => 'equal' );

				return $entry;
			}

			$entry['changes'] = $changes;
			$entry['data']    = array(
				'values'    => $provided,
				'term_id'   => $term instanceof WP_Term ? (int) $term->term_id : 0,
				'has_term'  => $term instanceof WP_Term,
				'is_update' => true,
			);

			return $entry;
		}

		if ( '' === $title ) {
			$entry['errors'][] = __( 'Для новой услуги нужно название.', 'mete-clinic-content' );

			return self::fail( $entry );
		}

		if ( 'update' === $mode ) {
			$entry['errors'][] = __( 'Услуга не найдена, а выбран режим «Только обновить».', 'mete-clinic-content' );

			return self::fail( $entry );
		}

		$entry['action'] = 'create';
		$entry['title']  = $title;
		$entry['data']   = array(
			'values'   => $provided,
			'term_id'  => $term instanceof WP_Term ? (int) $term->term_id : 0,
			'has_term' => $term instanceof WP_Term,
		);

		return $entry;
	}

	/**
	 * Plan a specialist row.
	 *
	 * @param array  $entry       Entry.
	 * @param array  $values      Values.
	 * @param string $mode        Mode.
	 * @param bool   $clear_empty Clear empty.
	 * @return array
	 */
	private static function plan_specialist( array $entry, array $values, $mode, $clear_empty ) {
		$provided = array();
		$title    = isset( $values['title'] ) ? trim( (string) $values['title'] ) : '';

		if ( '' !== $title ) {
			$provided['title'] = $title;
		}

		foreach ( array( 'role', 'experience', 'education' ) as $key ) {
			if ( ! isset( $values[ $key ] ) ) {
				continue;
			}

			$value = trim( (string) $values[ $key ] );

			if ( '' === $value && ! $clear_empty ) {
				continue;
			}

			$provided[ $key ] = 'education' === $key
				? sanitize_textarea_field( $value )
				: mb_substr( sanitize_text_field( $value ), 0, 'experience' === $key ? 60 : 120 );
		}

		if ( isset( $values['order'] ) && '' !== trim( (string) $values['order'] ) ) {
			if ( ! preg_match( '/^[0-9]{1,4}$/', trim( (string) $values['order'] ) ) ) {
				$entry['errors'][] = __( 'Порядок: целое число от 0 до 9999.', 'mete-clinic-content' );
			} else {
				$provided['order'] = MCC_Sanitize::order( $values['order'] );
			}
		}

		if ( isset( $values['status'] ) && '' !== trim( (string) $values['status'] ) ) {
			$status = self::parse_status( (string) $values['status'] );

			if ( null === $status ) {
				$entry['errors'][] = __( 'Статус: укажите «опубликован» или «скрыт».', 'mete-clinic-content' );
			} else {
				$provided['status'] = $status;
			}
		}

		if ( isset( $values['booking_url'] ) && '' !== trim( (string) $values['booking_url'] ) ) {
			$url = MCC_Sanitize::url( $values['booking_url'] );

			if ( '' === $url ) {
				$entry['errors'][] = __( 'Ссылка записи должна начинаться с http:// или https://.', 'mete-clinic-content' );
			} else {
				$provided['booking_url'] = $url;
			}
		}

		if ( ! empty( $entry['errors'] ) ) {
			return self::fail( $entry );
		}

		$current = self::find_specialist( $entry['id'], $title );

		if ( ! $current instanceof WP_Post ) {
			if ( '' === $title ) {
				$entry['errors'][] = __( 'Для нового специалиста нужно ФИО.', 'mete-clinic-content' );

				return self::fail( $entry );
			}

			if ( 'update' === $mode ) {
				$entry['errors'][] = __( 'Специалист не найден, а выбран режим «Только обновить».', 'mete-clinic-content' );

				return self::fail( $entry );
			}

			$entry['action'] = 'create';
			$entry['title']  = $title;
			$entry['data']   = array( 'values' => $provided );

			return $entry;
		}

		$entry['id']    = (int) $current->ID;
		$entry['title'] = (string) $current->post_title;

		if ( 'add' === $mode ) {
			$entry['action'] = 'same';
			$entry['data']   = array( 'reason' => 'exists' );

			return $entry;
		}

		$changes = self::diff_specialist( $current, $provided );

		if ( empty( $changes ) ) {
			$entry['action'] = 'same';
			$entry['data']   = array( 'reason' => 'equal' );

			return $entry;
		}

		$entry['action']  = 'update';
		$entry['changes'] = $changes;
		$entry['data']    = array(
			'values'    => $provided,
			'is_update' => true,
		);

		return $entry;
	}

	/**
	 * Plan a category row.
	 *
	 * @param array  $entry       Entry.
	 * @param array  $values      Values.
	 * @param string $mode        Mode.
	 * @param bool   $clear_empty Clear empty.
	 * @return array
	 */
	private static function plan_category( array $entry, array $values, $mode, $clear_empty ) {
		$provided = array();
		$name     = isset( $values['name'] ) ? trim( (string) $values['name'] ) : '';
		$slug     = isset( $values['slug'] ) ? sanitize_title( (string) $values['slug'] ) : '';

		if ( '' !== $name ) {
			$provided['name'] = sanitize_text_field( $name );
		}

		if ( '' !== $slug ) {
			$provided['slug'] = $slug;
		}

		if ( isset( $values['order'] ) && '' !== trim( (string) $values['order'] ) ) {
			if ( ! preg_match( '/^[0-9]{1,4}$/', trim( (string) $values['order'] ) ) ) {
				$entry['errors'][] = __( 'Порядок: целое число от 0 до 9999.', 'mete-clinic-content' );
			} else {
				$provided['order'] = MCC_Sanitize::order( $values['order'] );
			}
		}

		if ( isset( $values['description'] ) ) {
			$description = trim( (string) $values['description'] );

			if ( '' !== $description || $clear_empty ) {
				$provided['description'] = sanitize_textarea_field( $description );
			}
		}

		if ( ! empty( $entry['errors'] ) ) {
			return self::fail( $entry );
		}

		$current = self::find_category( $entry['id'] > 0 ? (int) $entry['id'] : $name, $slug );

		if ( $current instanceof WP_Term ) {
			$entry['id']    = (int) $current->term_id;
			$entry['title'] = (string) $current->name;

			if ( 'add' === $mode ) {
				$entry['action'] = 'same';
				$entry['data']   = array( 'reason' => 'exists' );

				return $entry;
			}

			$changes = self::diff_category( $current, $provided );

			if ( empty( $changes ) ) {
				$entry['action'] = 'same';
				$entry['data']   = array( 'reason' => 'equal' );

				return $entry;
			}

			$entry['action']  = 'update';
			$entry['changes'] = $changes;
			$entry['data']    = array(
				'values'    => $provided,
				'is_update' => true,
			);

			return $entry;
		}

		if ( '' === $name ) {
			$entry['errors'][] = __( 'Для новой категории нужно название.', 'mete-clinic-content' );

			return self::fail( $entry );
		}

		if ( 'update' === $mode ) {
			$entry['errors'][] = __( 'Категория не найдена, а выбран режим «Только обновить».', 'mete-clinic-content' );

			return self::fail( $entry );
		}

		$entry['action'] = 'create';
		$entry['title']  = $name;
		$entry['data']   = array( 'values' => $provided );

		return $entry;
	}

	/**
	 * Normalize a price cell, collecting a validation error.
	 *
	 * @param string $value Raw value.
	 * @param array  $entry Entry (by reference through array? no).
	 * @return string|null Null when the cell is empty.
	 */
	private static function price_cell( $value, array &$entry ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return null;
		}

		$price = MCC_Sanitize::price( $value );

		if ( '' === $price ) {
			$entry['errors'][] = sprintf(
				/* translators: 1: column name, 2: value. */
				__( '%1$s: «%2$s» не похоже на число.', 'mete-clinic-content' ),
				__( 'Цена', 'mete-clinic-content' ),
				$value
			);

			return null;
		}

		return $price;
	}

	/**
	 * Parse a yes/no cell.
	 *
	 * @param string $value Cell.
	 * @return int|null
	 */
	public static function parse_flag( $value ) {
		$value = mb_strtolower( trim( (string) $value ), 'UTF-8' );

		if ( in_array( $value, array( 'да', 'yes', '1', 'true', 'истина', 'y', '+' ), true ) ) {
			return 1;
		}

		if ( in_array( $value, array( 'нет', 'no', '0', 'false', 'ложь', 'n', '-' ), true ) ) {
			return 0;
		}

		return null;
	}

	/**
	 * Parse a status cell.
	 *
	 * @param string $value Cell.
	 * @return string|null
	 */
	public static function parse_status( $value ) {
		$value = mb_strtolower( trim( (string) $value ), 'UTF-8' );

		if ( in_array( $value, array( 'опубликована', 'опубликован', 'publish', 'published', '1', 'да' ), true ) ) {
			return 'publish';
		}

		if ( in_array( $value, array( 'скрыта', 'скрыт', 'draft', 'черновик', '0', 'нет' ), true ) ) {
			return 'draft';
		}

		return null;
	}

	/**
	 * Find a service by ID, import key or title.
	 *
	 * @param int    $id      Post ID.
	 * @param string $title   Title.
	 * @param string $slug    Optional category slug for the import key.
	 * @return WP_Post|null
	 */
	public static function find_service( $id, $title, $slug = '' ) {
		$id = (int) $id;

		if ( $id > 0 ) {
			$post = get_post( $id );

			if ( $post instanceof WP_Post && MCC_Post_Types::SERVICE === $post->post_type ) {
				return $post;
			}

			return null;
		}

		$title = trim( (string) $title );

		if ( '' === $title ) {
			return null;
		}

		if ( '' !== $slug ) {
			$key   = md5( $slug . '|' . $title );
			$posts = get_posts(
				array(
					'post_type'        => MCC_Post_Types::SERVICE,
					'post_status'      => 'any',
					'numberposts'      => 1,
					'meta_key'         => MCC_Catalog_Import::KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					'suppress_filters' => true,
				)
			);

			if ( ! empty( $posts ) && $posts[0] instanceof WP_Post ) {
				return $posts[0];
			}
		}

		$posts = get_posts(
			array(
				'post_type'        => MCC_Post_Types::SERVICE,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'title'            => $title,
				'suppress_filters' => true,
			)
		);

		return ! empty( $posts ) && $posts[0] instanceof WP_Post ? $posts[0] : null;
	}

	/**
	 * Find a specialist by ID or exact name.
	 *
	 * @param int    $id    Post ID.
	 * @param string $title Name.
	 * @return WP_Post|null
	 */
	public static function find_specialist( $id, $title ) {
		$id = (int) $id;

		if ( $id > 0 ) {
			$post = get_post( $id );

			return $post instanceof WP_Post && MCC_Post_Types::SPECIALIST === $post->post_type ? $post : null;
		}

		$title = trim( (string) $title );

		if ( '' === $title ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'        => MCC_Post_Types::SPECIALIST,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'title'            => $title,
				'suppress_filters' => true,
			)
		);

		return ! empty( $posts ) && $posts[0] instanceof WP_Post ? $posts[0] : null;
	}

	/**
	 * Find a category by ID, slug or name.
	 *
	 * @param mixed  $value ID, name or slug.
	 * @param string $slug  Optional slug hint.
	 * @return WP_Term|null
	 */
	public static function find_category( $value, $slug = '' ) {
		$taxonomy = MCC_Post_Types::TAX;

		if ( is_int( $value ) && $value > 0 ) {
			$term = get_term( $value, $taxonomy );

			return $term instanceof WP_Term ? $term : null;
		}

		$value = trim( (string) $value );
		$slug  = (string) $slug;

		foreach ( array( $slug, $value ) as $candidate ) {
			$candidate = trim( $candidate );

			if ( '' === $candidate ) {
				continue;
			}

			$term = get_term_by( 'slug', sanitize_title( $candidate ), $taxonomy );

			if ( $term instanceof WP_Term ) {
				return $term;
			}

			$term = get_term_by( 'name', $candidate, $taxonomy );

			if ( $term instanceof WP_Term ) {
				return $term;
			}
		}

		if ( '' === $value ) {
			return null;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return null;
		}

		foreach ( $terms as $term ) {
			if ( mb_strtolower( $term->name, 'UTF-8' ) === mb_strtolower( $value, 'UTF-8' ) ) {
				return $term;
			}
		}

		return null;
	}

	/**
	 * Changes for a service.
	 *
	 * @param WP_Post $post     Current post.
	 * @param array   $provided Provided values.
	 * @param WP_Term|null $term Target category.
	 * @return array
	 */
	private static function diff_service( WP_Post $post, array $provided, $term ) {
		$changes = array();
		$known   = self::service_state( $post );

		foreach ( $provided as $key => $value ) {
			if ( ! array_key_exists( $key, $known ) ) {
				continue;
			}

			if ( 'price_type' === $key ) {
				$value = (string) $value;
			}

			if ( (string) $known[ $key ] === (string) $value ) {
				continue;
			}

			$changes[] = array(
				'field' => self::field_label( $key ),
				'old'   => self::display_value( $key, $known[ $key ] ),
				'new'   => self::display_value( $key, $value ),
			);
		}

		if ( $term instanceof WP_Term && (int) $known['term_id'] !== (int) $term->term_id ) {
			$changes[] = array(
				'field' => __( 'Категория', 'mete-clinic-content' ),
				'old'   => (string) $known['category'],
				'new'   => (string) $term->name,
			);
		}

		return $changes;
	}

	/**
	 * Current values of a service for comparison.
	 *
	 * @param WP_Post $post Service.
	 * @return array
	 */
	private static function service_state( WP_Post $post ) {
		$terms = get_the_terms( $post->ID, MCC_Post_Types::TAX );
		$term  = is_array( $terms ) && ! empty( $terms ) && $terms[0] instanceof WP_Term ? $terms[0] : null;
		$type  = (string) get_post_meta( $post->ID, MCC_Fields::META_PRICE_TYPE, true );

		return array(
			'id'           => (int) $post->ID,
			'title'        => (string) $post->post_title,
			'status'       => (string) $post->post_status,
			'description'  => (string) $post->post_content,
			'price_type'   => '' === $type ? 'fixed' : $type,
			'price'        => (string) MCC_Sanitize::price( get_post_meta( $post->ID, MCC_Fields::META_PRICE, true ) ),
			'price_max'    => (string) MCC_Sanitize::price( get_post_meta( $post->ID, MCC_Fields::META_PRICE_MAX, true ) ),
			'price_note'   => (string) get_post_meta( $post->ID, MCC_Fields::META_PRICE_NOTE, true ),
			'minutes'      => (string) get_post_meta( $post->ID, MCC_Fields::META_DURATION, true ),
			'show_on_home' => '1' === (string) get_post_meta( $post->ID, MCC_Fields::META_SHOW_HOME, true ) ? 1 : 0,
			'home_order'   => (int) get_post_meta( $post->ID, MCC_Fields::META_HOME_ORDER, true ),
			'booking_url'  => (string) get_post_meta( $post->ID, MCC_Fields::META_BOOKING_URL, true ),
			'category'     => $term instanceof WP_Term ? (string) $term->name : '',
			'term_id'      => $term instanceof WP_Term ? (int) $term->term_id : 0,
		);
	}

	/**
	 * Changes for a specialist.
	 *
	 * @param WP_Post $post     Current post.
	 * @param array   $provided Provided values.
	 * @return array
	 */
	private static function diff_specialist( WP_Post $post, array $provided ) {
		$known = array(
			'title'       => (string) $post->post_title,
			'status'      => (string) $post->post_status,
			'role'        => (string) get_post_meta( $post->ID, MCC_Fields::META_ROLE, true ),
			'experience'  => (string) get_post_meta( $post->ID, MCC_Fields::META_EXPERIENCE, true ),
			'education'   => (string) get_post_meta( $post->ID, MCC_Fields::META_EDUCATION, true ),
			'order'       => (int) $post->menu_order,
			'booking_url' => (string) get_post_meta( $post->ID, MCC_Fields::META_BOOKING_URL, true ),
		);

		$changes = array();

		foreach ( $provided as $key => $value ) {
			if ( ! array_key_exists( $key, $known ) ) {
				continue;
			}

			if ( (string) $known[ $key ] === (string) $value ) {
				continue;
			}

			$changes[] = array(
				'field' => self::field_label( $key ),
				'old'   => self::display_value( $key, $known[ $key ] ),
				'new'   => self::display_value( $key, $value ),
			);
		}

		return $changes;
	}

	/**
	 * Changes for a category.
	 *
	 * @param WP_Term $term     Current term.
	 * @param array   $provided Provided values.
	 * @return array
	 */
	private static function diff_category( WP_Term $term, array $provided ) {
		$known = array(
			'name'        => (string) $term->name,
			'slug'        => (string) $term->slug,
			'description' => (string) $term->description,
			'order'       => (int) get_term_meta( $term->term_id, MCC_Term_Fields::META_ORDER, true ),
		);

		$changes = array();

		foreach ( $provided as $key => $value ) {
			if ( ! array_key_exists( $key, $known ) ) {
				continue;
			}

			if ( (string) $known[ $key ] === (string) $value ) {
				continue;
			}

			$changes[] = array(
				'field' => self::field_label( $key ),
				'old'   => self::display_value( $key, $known[ $key ] ),
				'new'   => self::display_value( $key, $value ),
			);
		}

		return $changes;
	}

	/**
	 * Human label of a field.
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	public static function field_label( $key ) {
		$labels = array(
			'id'           => __( 'ID', 'mete-clinic-content' ),
			'title'        => __( 'Название', 'mete-clinic-content' ),
			'name'         => __( 'Название', 'mete-clinic-content' ),
			'slug'         => __( 'Слаг', 'mete-clinic-content' ),
			'category'     => __( 'Категория', 'mete-clinic-content' ),
			'price_type'   => __( 'Тип цены', 'mete-clinic-content' ),
			'price'        => __( 'Цена', 'mete-clinic-content' ),
			'price_max'    => __( 'Цена до', 'mete-clinic-content' ),
			'price_note'   => __( 'Примечание к цене', 'mete-clinic-content' ),
			'minutes'      => __( 'Минут', 'mete-clinic-content' ),
			'show_on_home' => __( 'На главной', 'mete-clinic-content' ),
			'home_order'   => __( 'Порядок на главной', 'mete-clinic-content' ),
			'order'        => __( 'Порядок', 'mete-clinic-content' ),
			'status'       => __( 'Статус', 'mete-clinic-content' ),
			'booking_url'  => __( 'Ссылка записи', 'mete-clinic-content' ),
			'description'  => __( 'Описание', 'mete-clinic-content' ),
			'role'         => __( 'Специализация', 'mete-clinic-content' ),
			'experience'   => __( 'Опыт', 'mete-clinic-content' ),
			'education'    => __( 'Образование', 'mete-clinic-content' ),
		);

		return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}

	/**
	 * Readable value for the preview table.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Value.
	 * @return string
	 */
	private static function display_value( $key, $value ) {
		if ( 'show_on_home' === $key ) {
			return (int) $value ? __( 'да', 'mete-clinic-content' ) : __( 'нет', 'mete-clinic-content' );
		}

		if ( 'status' === $key ) {
			return 'publish' === (string) $value ? __( 'опубликована', 'mete-clinic-content' ) : __( 'скрыта', 'mete-clinic-content' );
		}

		$value = (string) $value;

		if ( mb_strlen( $value ) > 90 ) {
			return mb_substr( $value, 0, 90 ) . '…';
		}

		return $value;
	}

	/**
	 * Apply a stored plan.
	 *
	 * @param array $plan Plan.
	 * @return array Counts and errors.
	 */
	public static function apply( array $plan ) {
		$result = array(
			'create' => 0,
			'update' => 0,
			'skip'   => 0,
			'error'  => 0,
			'errors' => array(),
		);

		$entity = isset( $plan['entity'] ) ? (string) $plan['entity'] : '';
		$rows   = isset( $plan['rows'] ) && is_array( $plan['rows'] ) ? $plan['rows'] : array();

		foreach ( $rows as $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['action'] ) ) {
				continue;
			}

			if ( 'error' === $entry['action'] ) {
				$result['error']++;

				foreach ( (array) $entry['errors'] as $message ) {
					$result['errors'][] = self::line_error( $entry, (string) $message );
				}

				continue;
			}

			if ( 'same' === $entry['action'] ) {
				$result['skip']++;
				continue;
			}

			if ( 'create' === $entry['action'] ) {
				$done = self::create( $entity, $entry, $result );

				if ( $done ) {
					$result['create']++;
				}

				continue;
			}

			if ( 'update' === $entry['action'] ) {
				$done = self::update( $entity, $entry, $result );

				if ( $done ) {
					$result['update']++;
				}
			}
		}

		return $result;
	}

	/**
	 * Create one record.
	 *
	 * @param string $entity Entity.
	 * @param array  $entry  Plan entry.
	 * @param array  $result Result accumulator.
	 * @return bool
	 */
	private static function create( $entity, array $entry, array &$result ) {
		$values = isset( $entry['data']['values'] ) && is_array( $entry['data']['values'] ) ? $entry['data']['values'] : array();

		if ( self::SERVICE === $entity ) {
			return self::create_service( $entry, $values, $result );
		}

		if ( self::SPECIALIST === $entity ) {
			return self::create_specialist( $entry, $values, $result );
		}

		return self::create_category( $entry, $values, $result );
	}

	/**
	 * Create a service.
	 *
	 * @param array $entry  Entry.
	 * @param array $values Values.
	 * @param array $result Result accumulator.
	 * @return bool
	 */
	private static function create_service( array $entry, array $values, array &$result ) {
		$title = isset( $values['title'] ) ? (string) $values['title'] : '';

		if ( '' === $title ) {
			$result['errors'][] = self::line_error( $entry, __( 'нет названия', 'mete-clinic-content' ) );

			return false;
		}

		$status = isset( $values['status'] ) ? (string) $values['status'] : 'publish';

		$post_id = wp_insert_post(
			array(
				'post_type'    => MCC_Post_Types::SERVICE,
				'post_status'  => 'draft' === $status ? 'draft' : 'publish',
				'post_title'   => $title,
				'post_content' => isset( $values['description'] ) ? (string) $values['description'] : '',
				'post_excerpt' => '',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$result['errors'][] = self::line_error( $entry, $post_id->get_error_message() );

			return false;
		}

		$post_id = (int) $post_id;

		self::write_service_meta( $post_id, $values );

		$term_id = isset( $entry['data']['term_id'] ) ? (int) $entry['data']['term_id'] : 0;

		if ( $term_id > 0 ) {
			wp_set_object_terms( $post_id, array( $term_id ), MCC_Post_Types::TAX, false );

			$term = get_term( $term_id, MCC_Post_Types::TAX );

			if ( $term instanceof WP_Term ) {
				update_post_meta( $post_id, MCC_Catalog_Import::KEY, md5( $term->slug . '|' . $title ) );
			}
		}

		return true;
	}

	/**
	 * Create a specialist.
	 *
	 * @param array $entry  Entry.
	 * @param array $values Values.
	 * @param array $result Result accumulator.
	 * @return bool
	 */
	private static function create_specialist( array $entry, array $values, array &$result ) {
		$title = isset( $values['title'] ) ? (string) $values['title'] : '';

		if ( '' === $title ) {
			$result['errors'][] = self::line_error( $entry, __( 'нет ФИО', 'mete-clinic-content' ) );

			return false;
		}

		$status = isset( $values['status'] ) ? (string) $values['status'] : 'publish';

		$post_id = wp_insert_post(
			array(
				'post_type'   => MCC_Post_Types::SPECIALIST,
				'post_status' => 'draft' === $status ? 'draft' : 'publish',
				'post_title'  => $title,
				'menu_order'  => isset( $values['order'] ) ? (int) $values['order'] : 0,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$result['errors'][] = self::line_error( $entry, $post_id->get_error_message() );

			return false;
		}

		self::write_specialist_meta( (int) $post_id, $values );

		return true;
	}

	/**
	 * Create a category.
	 *
	 * @param array $entry  Entry.
	 * @param array $values Values.
	 * @param array $result Result accumulator.
	 * @return bool
	 */
	private static function create_category( array $entry, array $values, array &$result ) {
		$name = isset( $values['name'] ) ? (string) $values['name'] : '';

		if ( '' === $name ) {
			$result['errors'][] = self::line_error( $entry, __( 'нет названия', 'mete-clinic-content' ) );

			return false;
		}

		$args = array();

		if ( ! empty( $values['slug'] ) ) {
			$args['slug'] = (string) $values['slug'];
		}

		if ( isset( $values['description'] ) ) {
			$args['description'] = (string) $values['description'];
		}

		$created = wp_insert_term( $name, MCC_Post_Types::TAX, $args );

		if ( is_wp_error( $created ) ) {
			$result['errors'][] = self::line_error( $entry, $created->get_error_message() );

			return false;
		}

		$term_id = (int) $created['term_id'];

		if ( isset( $values['order'] ) ) {
			update_term_meta( $term_id, MCC_Term_Fields::META_ORDER, (int) $values['order'] );
		}

		return true;
	}

	/**
	 * Update one record.
	 *
	 * @param string $entity Entity.
	 * @param array  $entry  Entry.
	 * @param array  $result Result accumulator.
	 * @return bool
	 */
	private static function update( $entity, array $entry, array &$result ) {
		$values = isset( $entry['data']['values'] ) && is_array( $entry['data']['values'] ) ? $entry['data']['values'] : array();
		$id     = isset( $entry['id'] ) ? (int) $entry['id'] : 0;

		if ( $id <= 0 ) {
			$result['errors'][] = self::line_error( $entry, __( 'не найден ID записи', 'mete-clinic-content' ) );

			return false;
		}

		if ( self::CATEGORY === $entity ) {
			return self::update_category( $id, $entry, $values, $result );
		}

		$post = get_post( $id );

		if ( ! $post instanceof WP_Post ) {
			$result['errors'][] = self::line_error( $entry, __( 'запись не найдена', 'mete-clinic-content' ) );

			return false;
		}

		$postarr = array( 'ID' => $id );

		if ( isset( $values['title'] ) ) {
			$postarr['post_title'] = (string) $values['title'];
		}

		if ( isset( $values['status'] ) ) {
			$postarr['post_status'] = (string) $values['status'];
		}

		if ( self::SERVICE === $entity ) {
			if ( isset( $values['description'] ) ) {
				$postarr['post_content'] = (string) $values['description'];
			}
		} else {
			if ( isset( $values['order'] ) ) {
				$postarr['menu_order'] = (int) $values['order'];
			}
		}

		if ( count( $postarr ) > 1 ) {
			$updated = wp_update_post( $postarr, true );

			if ( is_wp_error( $updated ) ) {
				$result['errors'][] = self::line_error( $entry, $updated->get_error_message() );

				return false;
			}
		}

		if ( self::SERVICE === $entity ) {
			self::write_service_meta( $id, $values );

			$term_id = isset( $entry['data']['term_id'] ) ? (int) $entry['data']['term_id'] : 0;

			if ( $term_id > 0 ) {
				wp_set_object_terms( $id, array( $term_id ), MCC_Post_Types::TAX, false );
			}
		} else {
			self::write_specialist_meta( $id, array_merge( $values, array( 'keep_order' => true ) ) );
		}

		return true;
	}

	/**
	 * Update a category.
	 *
	 * @param int   $id     Term ID.
	 * @param array $entry  Entry.
	 * @param array $values Values.
	 * @param array $result Result accumulator.
	 * @return bool
	 */
	private static function update_category( $id, array $entry, array $values, array &$result ) {
		$args = array();

		if ( isset( $values['name'] ) ) {
			$args['name'] = (string) $values['name'];
		}

		if ( isset( $values['slug'] ) ) {
			$args['slug'] = (string) $values['slug'];
		}

		if ( isset( $values['description'] ) ) {
			$args['description'] = (string) $values['description'];
		}

		if ( ! empty( $args ) ) {
			$updated = wp_update_term( $id, MCC_Post_Types::TAX, $args );

			if ( is_wp_error( $updated ) ) {
				$result['errors'][] = self::line_error( $entry, $updated->get_error_message() );

				return false;
			}
		}

		if ( isset( $values['order'] ) ) {
			update_term_meta( $id, MCC_Term_Fields::META_ORDER, (int) $values['order'] );
		}

		return true;
	}

	/**
	 * Write service meta from normalized values.
	 *
	 * @param int   $post_id Service ID.
	 * @param array $values  Values.
	 * @return void
	 */
	private static function write_service_meta( $post_id, array $values ) {
		$post_id = (int) $post_id;
		$type    = isset( $values['price_type'] ) ? (string) $values['price_type'] : '';

		if ( '' !== $type ) {
			update_post_meta( $post_id, MCC_Fields::META_PRICE_TYPE, $type );
		}

		if ( 'free' === $type ) {
			delete_post_meta( $post_id, MCC_Fields::META_PRICE );
			delete_post_meta( $post_id, MCC_Fields::META_PRICE_MAX );
		} else {
			if ( isset( $values['price'] ) ) {
				self::store_meta( $post_id, MCC_Fields::META_PRICE, (string) $values['price'] );
			}

			if ( isset( $values['price_max'] ) ) {
				self::store_meta( $post_id, MCC_Fields::META_PRICE_MAX, (string) $values['price_max'] );
			}
		}

		if ( isset( $values['price_note'] ) ) {
			self::store_meta( $post_id, MCC_Fields::META_PRICE_NOTE, (string) $values['price_note'] );
		}

		if ( isset( $values['minutes'] ) ) {
			self::store_meta( $post_id, MCC_Fields::META_DURATION, (string) $values['minutes'] );
		}

		if ( isset( $values['show_on_home'] ) ) {
			self::store_meta( $post_id, MCC_Fields::META_SHOW_HOME, (int) $values['show_on_home'] ? '1' : '' );
		}

		if ( isset( $values['home_order'] ) ) {
			update_post_meta( $post_id, MCC_Fields::META_HOME_ORDER, (int) $values['home_order'] );
		}

		if ( isset( $values['booking_url'] ) ) {
			self::store_meta( $post_id, MCC_Fields::META_BOOKING_URL, (string) $values['booking_url'] );
		}
	}

	/**
	 * Write specialist meta from normalized values.
	 *
	 * @param int   $post_id Specialist ID.
	 * @param array $values  Values.
	 * @return void
	 */
	private static function write_specialist_meta( $post_id, array $values ) {
		$post_id = (int) $post_id;
		$map     = array(
			'role'        => MCC_Fields::META_ROLE,
			'experience'  => MCC_Fields::META_EXPERIENCE,
			'education'   => MCC_Fields::META_EDUCATION,
			'booking_url' => MCC_Fields::META_BOOKING_URL,
		);

		foreach ( $map as $key => $meta ) {
			if ( ! isset( $values[ $key ] ) ) {
				continue;
			}

			self::store_meta( $post_id, $meta, (string) $values[ $key ] );
		}
	}

	/**
	 * Update meta or delete it for an empty value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param string $value   Value.
	 * @return void
	 */
	private static function store_meta( $post_id, $key, $value ) {
		if ( '' === trim( (string) $value ) ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Format a line error.
	 *
	 * @param array  $entry   Entry.
	 * @param string $message Message.
	 * @return string
	 */
	private static function line_error( array $entry, $message ) {
		return sprintf(
			/* translators: 1: row number, 2: error message. */
			__( 'Строка %1$d: %2$s', 'mete-clinic-content' ),
			isset( $entry['line'] ) ? (int) $entry['line'] : 0,
			$message
		);
	}

	/**
	 * Rows of the current data, ready for export.
	 *
	 * @param string $entity Entity.
	 * @return array
	 */
	public static function export_rows( $entity ) {
		$columns = self::columns( $entity );
		$keys    = array_keys( $columns );
		$rows    = array();

		if ( self::SERVICE === $entity ) {
			foreach ( self::all_services() as $post ) {
				$state = self::service_state( $post );
				$item  = array();

				foreach ( $keys as $key ) {
					$value = isset( $state[ $key ] ) ? $state[ $key ] : '';

					if ( 'show_on_home' === $key ) {
						$value = $value ? 'да' : 'нет';
					} elseif ( 'status' === $key ) {
						$value = 'publish' === (string) $value ? 'опубликована' : 'скрыта';
					} elseif ( 'price_type' === $key && '' === (string) $value ) {
						$value = 'fixed';
					}

					$item[] = $value;
				}

				$rows[] = $item;
			}

			return $rows;
		}

		if ( self::SPECIALIST === $entity ) {
			$posts = get_posts(
				array(
					'post_type'        => MCC_Post_Types::SPECIALIST,
					'post_status'      => 'any',
					'posts_per_page'   => -1,
					'orderby'          => array(
						'menu_order' => 'ASC',
						'title'      => 'ASC',
					),
					'suppress_filters' => true,
				)
			);

			foreach ( $posts as $post ) {
				$item = array(
					$post->ID,
					$post->post_title,
					(string) get_post_meta( $post->ID, MCC_Fields::META_ROLE, true ),
					(string) get_post_meta( $post->ID, MCC_Fields::META_EXPERIENCE, true ),
					(string) get_post_meta( $post->ID, MCC_Fields::META_EDUCATION, true ),
					(int) $post->menu_order,
					'publish' === $post->post_status ? 'опубликован' : 'скрыт',
					(string) get_post_meta( $post->ID, MCC_Fields::META_BOOKING_URL, true ),
				);

				$rows[] = $item;
			}

			return $rows;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => MCC_Post_Types::TAX,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		usort(
			$terms,
			function ( $a, $b ) {
				return MCC_Content::compare_term_order( $a, $b );
			}
		);

		foreach ( $terms as $term ) {
			$rows[] = array(
				$term->term_id,
				$term->slug,
				$term->name,
				(int) get_term_meta( $term->term_id, MCC_Term_Fields::META_ORDER, true ),
				$term->description,
			);
		}

		return $rows;
	}

	/**
	 * All services sorted by category order and title.
	 *
	 * @return WP_Post[]
	 */
	private static function all_services() {
		$posts = get_posts(
			array(
				'post_type'        => MCC_Post_Types::SERVICE,
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);

		usort(
			$posts,
			function ( $a, $b ) {
				$terms_a = get_the_terms( $a->ID, MCC_Post_Types::TAX );
				$terms_b = get_the_terms( $b->ID, MCC_Post_Types::TAX );

				$order_a = is_array( $terms_a ) && isset( $terms_a[0] ) ? (int) get_term_meta( $terms_a[0]->term_id, MCC_Term_Fields::META_ORDER, true ) : 0;
				$order_b = is_array( $terms_b ) && isset( $terms_b[0] ) ? (int) get_term_meta( $terms_b[0]->term_id, MCC_Term_Fields::META_ORDER, true ) : 0;

				if ( $order_a === $order_b ) {
					return strcasecmp( (string) $a->post_title, (string) $b->post_title );
				}

				return $order_a < $order_b ? -1 : 1;
			}
		);

		return $posts;
	}

	/**
	 * Header row for a sheet.
	 *
	 * @param string $entity Entity.
	 * @return array
	 */
	public static function header_row( $entity ) {
		return array_values( self::columns( $entity ) );
	}
}
