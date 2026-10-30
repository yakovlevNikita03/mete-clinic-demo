<?php
/**
 * Template helpers for the MÉTE CLINIC theme.
 *
 * Вспомогательные функции отображения. Бизнес-логика (запросы, форматирование
 * цен, разбор настроек) живёт в плагине mete-clinic-content; здесь — тонкие
 * обёртки и разметка, чтобы шаблоны оставались читаемыми.
 *
 * @package Mete_Clinic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the content plugin is available.
 *
 * @return bool
 */
function mete_plugin_active() {
	return function_exists( 'mete_content_get_settings' );
}

/**
 * Site settings provided by the plugin.
 *
 * @return array
 */
function mete_settings() {
	if ( mete_plugin_active() ) {
		$settings = mete_content_get_settings();

		return is_array( $settings ) ? $settings : array();
	}

	return array();
}

/**
 * One setting value.
 *
 * @param string $key Setting key.
 * @return string
 */
function mete_setting( $key ) {
	$settings = mete_settings();

	return isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
}

/**
 * Validate an absolute http(s) URL.
 *
 * @param string $url Raw value.
 * @return bool
 */
function mete_is_http_url( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return false;
	}

	$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
	$host   = wp_parse_url( $url, PHP_URL_HOST );

	if ( empty( $host ) ) {
		return false;
	}

	return in_array( strtolower( (string) $scheme ), array( 'http', 'https' ), true );
}

/**
 * URL of a file inside theme assets, or an empty string when it is missing.
 *
 * @param string $relative Path relative to assets/.
 * @return string
 */
function mete_asset_url( $relative ) {
	$relative = ltrim( (string) $relative, '/' );

	if ( '' === $relative || ! file_exists( METE_THEME_DIR . '/assets/' . $relative ) ) {
		return '';
	}

	return METE_THEME_URI . '/assets/' . $relative;
}

/**
 * Inline SVG icon.
 *
 * Возвращает статическую строку из фиксированного набора, поэтому её можно
 * выводить без экранирования: пользовательских данных внутри нет.
 *
 * @param string $name Icon name.
 * @return string
 */
function mete_icon( $name ) {
	$icons = array(
		'pin'   => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.6"/></svg>',
		'clock' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8"/><path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'phone' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'chat'  => '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="M4.5 5.5h15v10h-8l-4.5 3v-3H4.5Z" stroke-linejoin="round"/></svg>',
		'arrow' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false"><path d="M4 12h15M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'max'   => '<svg class="max-icon" viewBox="0 0 720 720" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false"><path d="M350.4,9.6C141.8,20.5,4.1,184.1,12.8,390.4c3.8,90.3,40.1,168,48.7,253.7,2.2,22.2-4.2,49.6,21.4,59.3,31.5,11.9,79.8-8.1,106.2-26.4,9-6.1,17.6-13.2,24.2-22,27.3,18.1,53.2,35.6,85.7,43.4,143.1,34.3,299.9-44.2,369.6-170.3C799.6,291.2,622.5-4.6,350.4,9.6h0ZM269.4,504c-11.3,8.8-22.2,20.8-34.7,27.7-18.1,9.7-23.7-.4-30.5-16.4-21.4-50.9-24-137.6-11.5-190.9,16.8-72.5,72.9-136.3,150-143.1,78-6.9,150.4,32.7,183.1,104.2,72.4,159.1-112.9,316.2-256.4,218.6h0Z"/></svg>',
	);

	return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
}

/**
 * Build an anchor tag from already escaped parts.
 *
 * @param string $url  Target URL.
 * @param string $text Already escaped inner HTML.
 * @param array  $args {
 *     Optional. class, aria_label, title, icon, icon_after.
 * }
 * @return string
 */
function mete_link( $url, $text, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'class'      => '',
		'aria_label' => '',
		'title'      => '',
		'icon'       => '',
		'icon_after' => '',
	) );

	$url = trim( (string) $url );

	if ( '' === $url ) {
		$url = mete_contacts_url();
	}

	$out = '<a href="' . esc_url( $url ) . '"';

	if ( '' !== $args['class'] ) {
		$out .= ' class="' . esc_attr( $args['class'] ) . '"';
	}

	if ( '' !== $args['aria_label'] ) {
		$out .= ' aria-label="' . esc_attr( $args['aria_label'] ) . '"';
	}

	if ( '' !== $args['title'] ) {
		$out .= ' title="' . esc_attr( $args['title'] ) . '"';
	}

	if ( mete_is_http_url( $url ) ) {
		$out .= ' target="_blank" rel="noopener noreferrer"';
	}

	$out .= '>';

	if ( '' !== $args['icon'] ) {
		$out .= mete_icon( $args['icon'] );
	}

	$out .= $text;

	if ( '' !== $args['icon_after'] ) {
		$out .= mete_icon( $args['icon_after'] );
	}

	$out .= '</a>';

	return $out;
}

/**
 * Site logo markup for the header.
 *
 * @return void
 */
function mete_brand() {
	$logo_id  = (int) get_theme_mod( 'custom_logo' );
	$logo_url = mete_asset_url( 'brand/mete-clinic-emblem.webp' );
	$name     = get_bloginfo( 'name' );
	$tagline  = get_bloginfo( 'description' );

	if ( '' === $name ) {
		$name = 'MÉTE CLINIC';
	}

	if ( '' === $tagline ) {
		$tagline = __( 'Клиника эпиляции и косметологии', 'mete-clinic' );
	}
	?>
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<?php if ( $logo_id > 0 ) : ?>
			<?php
			echo wp_get_attachment_image(
				$logo_id,
				'full',
				false,
				array(
					'class'    => 'brand__logo',
					'alt'      => $name,
					'loading'  => 'eager',
					'decoding' => 'async',
				)
			);
			?>
		<?php elseif ( '' !== $logo_url ) : ?>
			<img class="brand__logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="52" height="53" decoding="async">
		<?php else : ?>
			<span class="brand__mark" aria-hidden="true">M</span>
		<?php endif; ?>
		<span class="brand__text">
			<span class="brand__name"><?php echo esc_html( $name ); ?></span>
			<span class="brand__tagline"><?php echo esc_html( $tagline ); ?></span>
		</span>
	</a>
	<?php
}

/**
 * Full logo markup for the footer.
 *
 * @return void
 */
function mete_footer_logo() {
	$logo_id  = (int) get_theme_mod( 'custom_logo' );
	$logo_url = mete_asset_url( 'brand/mete-clinic-logo-full.webp' );

	if ( '' !== $logo_url ) {
		?>
		<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="420" height="264" loading="lazy" decoding="async">
		<?php
		return;
	}

	if ( $logo_id > 0 ) {
		echo wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'   => 'site-footer__logo',
				'alt'     => get_bloginfo( 'name' ),
				'loading' => 'lazy',
			)
		);
		return;
	}
	?>
	<span class="site-footer__brand-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
	<?php
}

/**
 * Default navigation items used when no menu is assigned.
 *
 * @return array
 */
function mete_nav_items() {
	return array(
		array(
			'label' => __( 'Услуги', 'mete-clinic' ),
			'url'   => home_url( '/#services' ),
		),
		array(
			'label' => __( 'Каталог', 'mete-clinic' ),
			'url'   => mete_catalog_url(),
		),
		array(
			'label' => __( 'Специалисты', 'mete-clinic' ),
			'url'   => home_url( '/#team' ),
		),
		array(
			'label' => __( 'О клинике', 'mete-clinic' ),
			'url'   => home_url( '/#about' ),
		),
		array(
			'label' => __( 'Контакты', 'mete-clinic' ),
			'url'   => home_url( '/#contacts' ),
		),
	);
}

/**
 * Render the fallback navigation list.
 *
 * @param string $class List class name.
 * @return void
 */
function mete_default_nav( $class = 'site-nav__list' ) {
	echo '<ul class="' . esc_attr( $class ) . '">';

	foreach ( mete_nav_items() as $item ) {
		echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
	}

	echo '</ul>';
}

/**
 * Catalog archive URL.
 *
 * @return string
 */
function mete_catalog_url() {
	$url = get_post_type_archive_link( 'mete_service' );

	return $url ? $url : home_url( '/#services' );
}

/**
 * Front page contacts anchor.
 *
 * @return string
 */
function mete_contacts_url() {
	return home_url( '/#contacts' );
}

/**
 * tel: link for the configured phone.
 *
 * @return string
 */
function mete_phone_href() {
	$raw = trim( mete_setting( 'phone_tel' ) );

	if ( '' === $raw ) {
		$raw = trim( mete_setting( 'phone' ) );
	}

	$raw    = preg_replace( '/^tel:/i', '', $raw );
	$digits = preg_replace( '/[^0-9+]/', '', (string) $raw );

	if ( ! preg_match( '/^\+?[0-9]{10,15}$/', (string) $digits ) ) {
		return '';
	}

	return 'tel:' . $digits;
}

/**
 * Human readable phone.
 *
 * @return string
 */
function mete_phone_label() {
	$display = trim( mete_setting( 'phone' ) );

	if ( '' !== $display ) {
		return $display;
	}

	return trim( str_replace( 'tel:', '', mete_phone_href() ) );
}

/**
 * MAX messenger URL.
 *
 * @return string
 */
function mete_max_url() {
	$url = trim( mete_setting( 'max_url' ) );

	return mete_is_http_url( $url ) ? $url : '';
}

/**
 * Extra messengers configured in the plugin settings.
 *
 * @return array
 */
function mete_messengers() {
	if ( function_exists( 'mete_content_get_messengers' ) ) {
		return mete_content_get_messengers();
	}

	return array();
}

/**
 * Booking URL for a service (or the site-wide one).
 *
 * @param int $service_id Optional service ID.
 * @return string
 */
function mete_booking_url( $service_id = 0 ) {
	if ( function_exists( 'mete_content_booking_url' ) ) {
		return mete_content_booking_url( (int) $service_id );
	}

	return mete_contacts_url();
}

/**
 * Whether a real online booking URL is configured.
 *
 * @return bool
 */
function mete_has_booking() {
	return mete_is_http_url( mete_booking_url() );
}

/**
 * Services query.
 *
 * @param array $args Extra WP_Query arguments.
 * @return WP_Query
 */
function mete_services( $args = array() ) {
	if ( function_exists( 'mete_content_get_services' ) ) {
		return mete_content_get_services( $args );
	}

	return new WP_Query( array( 'post_type' => 'mete_service', 'post__in' => array( 0 ) ) );
}

/**
 * Services flagged for the home page.
 *
 * @param int $limit Optional limit.
 * @return WP_Query
 */
function mete_popular_services( $limit = 0 ) {
	if ( function_exists( 'mete_content_get_popular_services' ) ) {
		return mete_content_get_popular_services( (int) $limit );
	}

	return new WP_Query( array( 'post_type' => 'mete_service', 'post__in' => array( 0 ) ) );
}

/**
 * Specialists query.
 *
 * @return WP_Query
 */
function mete_specialists() {
	if ( function_exists( 'mete_content_get_specialists' ) ) {
		return mete_content_get_specialists();
	}

	return new WP_Query( array( 'post_type' => 'mete_specialist', 'post__in' => array( 0 ) ) );
}

/**
 * Service categories (home page directions).
 *
 * @param array $args Optional query arguments.
 * @return WP_Term[]
 */
function mete_service_categories( $args = array() ) {
	if ( function_exists( 'mete_content_get_service_categories' ) ) {
		$terms = mete_content_get_service_categories( $args );

		return is_array( $terms ) ? $terms : array();
	}

	return array();
}

/**
 * Categories for the catalog filter, including nested ones.
 *
 * @return WP_Term[]
 */
function mete_service_filters() {
	if ( function_exists( 'mete_content_get_service_filters' ) ) {
		$terms = mete_content_get_service_filters();

		return is_array( $terms ) ? $terms : array();
	}

	return array();
}

/**
 * Number of published services.
 *
 * @return int
 */
function mete_published_services_count() {
	$counts = wp_count_posts( 'mete_service' );

	if ( ! is_object( $counts ) || ! isset( $counts->publish ) ) {
		return 0;
	}

	return (int) $counts->publish;
}

/**
 * Categories of one service.
 *
 * @param int $post_id Service ID.
 * @return WP_Term[]
 */
function mete_service_categories_for( $post_id ) {
	if ( function_exists( 'mete_content_get_service_categories_for' ) ) {
		$terms = mete_content_get_service_categories_for( (int) $post_id );

		return is_array( $terms ) ? $terms : array();
	}

	return array();
}

/**
 * Formatted price of a service.
 *
 * @param int $post_id Service ID.
 * @return string
 */
function mete_price_text( $post_id ) {
	if ( function_exists( 'mete_content_format_price' ) ) {
		return mete_content_format_price( (int) $post_id );
	}

	return '';
}

/**
 * Price note of a service.
 *
 * @param int $post_id Service ID.
 * @return string
 */
function mete_price_note( $post_id ) {
	if ( function_exists( 'mete_content_price_note' ) ) {
		return mete_content_price_note( (int) $post_id );
	}

	return '';
}

/**
 * Duration of a service.
 *
 * @param int $post_id Service ID.
 * @return string
 */
function mete_duration_text( $post_id ) {	if ( function_exists( 'mete_content_get_duration' ) ) {
		return mete_content_get_duration( (int) $post_id );
	}

	return '';
}

/**
 * Specialization of a specialist.
 *
 * @param int $post_id Specialist ID.
 * @return string
 */
function mete_specialist_role( $post_id ) {
	if ( function_exists( 'mete_content_get_role' ) ) {
		return mete_content_get_role( (int) $post_id );
	}

	return '';
}

/**
 * Experience of a specialist.
 *
 * @param int $post_id Specialist ID.
 * @return string
 */
function mete_specialist_experience( $post_id ) {
	if ( function_exists( 'mete_content_get_experience' ) ) {
		return mete_content_get_experience( (int) $post_id );
	}

	return '';
}

/**
 * Education of a specialist.
 *
 * @param int $post_id Specialist ID.
 * @return string
 */
function mete_specialist_education( $post_id ) {
	if ( function_exists( 'mete_content_get_education' ) ) {
		return mete_content_get_education( (int) $post_id );
	}

	return '';
}

/**
 * Initials for the monogram placeholder.
 *
 * @param string $name Full name.
 * @return string
 */
function mete_initials( $name ) {
	$words = preg_split( '/\s+/u', trim( (string) $name ), -1, PREG_SPLIT_NO_EMPTY );

	if ( empty( $words ) ) {
		return '';
	}

	if ( count( $words ) > 1 ) {
		return mb_strtoupper( mb_substr( $words[1], 0, 1 ) . mb_substr( $words[0], 0, 1 ) );
	}

	return mb_strtoupper( mb_substr( $words[0], 0, 1 ) );
}

/**
 * Illustration for a service category.
 *
 * Порядок: изображение из метаданных термина, затем встроенная иллюстрация
 * направления, затем пустая строка (карточка покажет бежевую подложку).
 *
 * @param WP_Term $term Category term.
 * @return string
 */
function mete_category_image_url( $term ) {
	$image_id = (int) get_term_meta( $term->term_id, 'mcc_cat_image', true );

	if ( $image_id > 0 ) {
		$url = wp_get_attachment_image_url( $image_id, 'mete-card' );

		if ( $url ) {
			return $url;
		}
	}

	$map = array(
		'lazernaya-epilyaciya'           => 'images/service-laser.svg',
		'kosmetologiya-lica'             => 'images/service-face.svg',
		'apparatnaya-korrekciya-figury'  => 'images/service-body.svg',
		'inekczionnaya-kosmetologiya'    => 'images/service-injection.svg',
	);

	if ( isset( $map[ $term->slug ] ) ) {
		return mete_asset_url( $map[ $term->slug ] );
	}

	return '';
}

/**
 * Catalog URL filtered by category.
 *
 * @param WP_Term $term Category term.
 * @return string
 */
function mete_category_link( $term ) {
	return add_query_arg( 'mete_cat', $term->slug, mete_catalog_url() );
}

/**
 * Human readable map provider label.
 *
 * @return string
 */
function mete_map_provider_label() {
	$provider = mete_setting( 'map_provider' );

	if ( '2gis' === $provider ) {
		return __( '2ГИС', 'mete-clinic' );
	}

	if ( 'yandex' === $provider ) {
		return __( 'Яндекс Карты', 'mete-clinic' );
	}

	return '';
}

/**
 * Current catalog category slug from the query string.
 *
 * @return string
 */
function mete_current_category_slug() {
	if ( ! isset( $_GET['mete_cat'] ) ) {
		return '';
	}

	$raw = wp_unslash( $_GET['mete_cat'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! is_string( $raw ) ) {
		return '';
	}

	$slug = sanitize_title( $raw );

	if ( '' === $slug ) {
		return '';
	}

	$term = get_term_by( 'slug', $slug, 'mete_service_cat' );

	return ( $term instanceof WP_Term ) ? (string) $term->slug : '';
}
