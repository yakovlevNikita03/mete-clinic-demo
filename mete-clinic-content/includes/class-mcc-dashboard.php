<?php
/**
 * Overview screen for the clinic administrator.
 *
 * @package Mete_Clinic_Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Start screen with counters, checklist and quick actions.
 */
class MCC_Dashboard {

	/**
	 * Page slug.
	 */
	const PAGE = 'mcc-dashboard';

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
	}

	/**
	 * Register the top-level menu.
	 *
	 * Пункт «Обзор» — родительское меню для остальных разделов плагина.
	 *
	 * @return void
	 */
	public static function menu() {
		add_menu_page(
			__( 'MÉTE CLINIC', 'mete-clinic-content' ),
			__( 'MÉTE CLINIC', 'mete-clinic-content' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' ),
			'dashicons-heart',
			58
		);

		add_submenu_page(
			self::PAGE,
			__( 'Обзор', 'mete-clinic-content' ),
			__( 'Обзор', 'mete-clinic-content' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Statistics for the dashboard.
	 *
	 * @return array
	 */
	public static function stats() {
		$services = get_posts(
			array(
				'post_type'        => MCC_Post_Types::SERVICE,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'   => -1,
				'suppress_filters' => true,
			)
		);

		$stats = array(
			'services'        => 0,
			'services_draft'  => 0,
			'without_price'   => 0,
			'without_cat'     => 0,
			'without_desc'    => 0,
			'specialists'     => 0,
			'specialists_off' => 0,
			'categories'      => 0,
		);

		foreach ( $services as $service ) {
			$published = 'publish' === $service->post_status;

			if ( $published ) {
				$stats['services']++;
			} else {
				$stats['services_draft']++;
			}

			if ( ! $published ) {
				continue;
			}

			$type = (string) get_post_meta( $service->ID, MCC_Fields::META_PRICE_TYPE, true );
			$price = MCC_Sanitize::price( get_post_meta( $service->ID, MCC_Fields::META_PRICE, true ) );

			if ( 'free' !== $type && '' === $price ) {
				$stats['without_price']++;
			}

			$terms = get_the_terms( $service->ID, MCC_Post_Types::TAX );

			if ( ! is_array( $terms ) || empty( $terms ) ) {
				$stats['without_cat']++;
			}

			if ( '' === trim( (string) $service->post_content ) ) {
				$stats['without_desc']++;
			}
		}

		$specialists = get_posts(
			array(
				'post_type'        => MCC_Post_Types::SPECIALIST,
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'   => -1,
				'suppress_filters' => true,
			)
		);

		foreach ( $specialists as $specialist ) {
			if ( 'publish' === $specialist->post_status ) {
				$stats['specialists']++;
			} else {
				$stats['specialists_off']++;
			}
		}

		$terms = get_terms(
			array(
				'taxonomy'   => MCC_Post_Types::TAX,
				'hide_empty' => false,
			)
		);

		$stats['categories'] = is_wp_error( $terms ) || ! is_array( $terms ) ? 0 : count( $terms );

		return $stats;
	}

	/**
	 * Checklist items.
	 *
	 * @return array List of array( 'label' => string, 'ok' => bool, 'url' => string, 'hint' => string ).
	 */
	public static function checklist() {
		$settings = MCC_Settings::get();
		$items    = array();

		$items[] = array(
			'label' => __( 'Телефон клиники', 'mete-clinic-content' ),
			'ok'    => '' !== trim( (string) $settings['phone'] ),
			'url'   => MCC_Settings::tab_url( 'contacts' ),
			'hint'  => __( 'Показывается в шапке, hero и контактах.', 'mete-clinic-content' ),
		);

		$items[] = array(
			'label' => __( 'Ссылка онлайн-записи', 'mete-clinic-content' ),
			'ok'    => '' !== MCC_Sanitize::url( $settings['booking_url'] ),
			'url'   => MCC_Settings::tab_url( 'booking' ),
			'hint'  => __( 'Используется во всех кнопках «Записаться».', 'mete-clinic-content' ),
		);

		$items[] = array(
			'label' => __( 'Ссылка MAX', 'mete-clinic-content' ),
			'ok'    => '' !== MCC_Sanitize::url( $settings['max_url'] ),
			'url'   => MCC_Settings::tab_url( 'booking' ),
			'hint'  => __( 'Если пусто, кнопка MAX на сайте не выводится.', 'mete-clinic-content' ),
		);

		$items[] = array(
			'label' => __( 'Карта', 'mete-clinic-content' ),
			'ok'    => '' !== MCC_Sanitize::url( $settings['map_embed_url'] ),
			'url'   => MCC_Settings::tab_url( 'map' ),
			'hint'  => __( 'Нужен адрес iframe из официального кода карты.', 'mete-clinic-content' ),
		);

		$reviews = '' !== MCC_Sanitize::url( $settings['reviews_embed_url'] ) || '' !== trim( (string) $settings['reviews_widget_html'] );

		$items[] = array(
			'label' => __( 'Блок отзывов', 'mete-clinic-content' ),
			'ok'    => $reviews,
			'url'   => MCC_Settings::tab_url( 'reviews' ),
			'hint'  => __( 'Без URL виджета и кода блок на сайте не выводится.', 'mete-clinic-content' ),
		);

		return $items;
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

		$stats  = self::stats();
		$checks = self::checklist();
		$ready  = 0;

		foreach ( $checks as $check ) {
			if ( $check['ok'] ) {
				$ready++;
			}
		}
		?>
		<div class="wrap mcc-dashboard">
			<h1><?php esc_html_e( 'MÉTE CLINIC', 'mete-clinic-content' ); ?></h1>
			<p class="mcc-dashboard__intro">
				<?php esc_html_e( 'Коротко о сайте: что заполнено, что требует внимания и с чего начать.', 'mete-clinic-content' ); ?>
			</p>

			<div class="mcc-cards">
				<div class="mcc-stat">
					<span class="mcc-card__value"><?php echo (int) $stats['services']; ?></span>
					<span class="mcc-card__label"><?php esc_html_e( 'услуг опубликовано', 'mete-clinic-content' ); ?></span>
					<?php if ( $stats['services_draft'] > 0 ) : ?>
						<span class="mcc-hint">
							<?php
							printf(
								/* translators: %d: number of drafts. */
								esc_html__( 'и %d в черновиках', 'mete-clinic-content' ),
								(int) $stats['services_draft']
							);
							?>
						</span>
					<?php endif; ?>
				</div>

				<div class="mcc-stat">
					<span class="mcc-card__value"><?php echo (int) $stats['categories']; ?></span>
					<span class="mcc-card__label"><?php esc_html_e( 'категорий услуг', 'mete-clinic-content' ); ?></span>
				</div>

				<div class="mcc-stat">
					<span class="mcc-card__value"><?php echo (int) $stats['specialists']; ?></span>
					<span class="mcc-card__label"><?php esc_html_e( 'специалистов', 'mete-clinic-content' ); ?></span>
					<?php if ( $stats['specialists_off'] > 0 ) : ?>
						<span class="mcc-hint">
							<?php
							printf(
								/* translators: %d: number of hidden specialists. */
								esc_html__( 'и %d скрыто', 'mete-clinic-content' ),
								(int) $stats['specialists_off']
							);
							?>
						</span>
					<?php endif; ?>
				</div>

				<div class="mcc-stat">
					<span class="mcc-card__value"><?php echo (int) $ready; ?> / <?php echo (int) count( $checks ); ?></span>
					<span class="mcc-card__label"><?php esc_html_e( 'настроек заполнено', 'mete-clinic-content' ); ?></span>
				</div>
			</div>

			<div class="mcc-columns">
				<div class="card mcc-card">
					<h2><?php esc_html_e( 'Проверка сайта', 'mete-clinic-content' ); ?></h2>
					<ul class="mcc-checklist">
						<?php foreach ( $checks as $check ) : ?>
							<li class="<?php echo $check['ok'] ? 'is-ok' : 'is-missing'; ?>">
								<span class="mcc-checklist__icon" aria-hidden="true"></span>
								<span class="mcc-checklist__text">
									<?php echo esc_html( $check['label'] ); ?>
									<span class="mcc-hint"><?php echo esc_html( $check['hint'] ); ?></span>
								</span>
								<a class="mcc-checklist__link" href="<?php echo esc_url( $check['url'] ); ?>">
									<?php echo $check['ok'] ? esc_html__( 'Открыть', 'mete-clinic-content' ) : esc_html__( 'Заполнить', 'mete-clinic-content' ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="card mcc-card">
					<h2><?php esc_html_e( 'Требует внимания', 'mete-clinic-content' ); ?></h2>

					<?php if ( 0 === $stats['without_price'] && 0 === $stats['without_cat'] && 0 === $stats['without_desc'] ) : ?>
						<p><?php esc_html_e( 'Опубликованные услуги заполнены: цена, категория и описание на месте.', 'mete-clinic-content' ); ?></p>
					<?php else : ?>
						<ul class="mcc-issues">
							<?php if ( $stats['without_price'] > 0 ) : ?>
								<li>
									<a href="<?php echo esc_url( self::services_link( 'no_price' ) ); ?>">
										<?php
										printf(
											/* translators: %d: number of services. */
											esc_html__( 'Без цены: %d', 'mete-clinic-content' ),
											(int) $stats['without_price']
										);
										?>
									</a>
								</li>
							<?php endif; ?>

							<?php if ( $stats['without_cat'] > 0 ) : ?>
								<li>
									<a href="<?php echo esc_url( self::services_link( 'no_category' ) ); ?>">
										<?php
										printf(
											/* translators: %d: number of services. */
											esc_html__( 'Без категории: %d', 'mete-clinic-content' ),
											(int) $stats['without_cat']
										);
										?>
									</a>
								</li>
							<?php endif; ?>

							<?php if ( $stats['without_desc'] > 0 ) : ?>
								<li>
									<a href="<?php echo esc_url( self::services_link( 'no_description' ) ); ?>">
										<?php
										printf(
											/* translators: %d: number of services. */
											esc_html__( 'Без описания: %d', 'mete-clinic-content' ),
											(int) $stats['without_desc']
										);
										?>
									</a>
								</li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>

					<h3><?php esc_html_e( 'Быстрые действия', 'mete-clinic-content' ); ?></h3>
					<p class="mcc-actions">
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . MCC_Post_Types::SERVICE ) ); ?>">
							<?php esc_html_e( 'Добавить услугу', 'mete-clinic-content' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . MCC_Post_Types::SPECIALIST ) ); ?>">
							<?php esc_html_e( 'Добавить специалиста', 'mete-clinic-content' ); ?>
						</a>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . MCC_Price_IO::PAGE ) ); ?>">
							<?php esc_html_e( 'Загрузить прайс', 'mete-clinic-content' ); ?>
						</a>
						<a class="button button-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
							<?php esc_html_e( 'Открыть сайт', 'mete-clinic-content' ); ?>
						</a>
					</p>
				</div>
			</div>

			<div class="card mcc-card">
				<h2><?php esc_html_e( 'Порядок работы', 'mete-clinic-content' ); ?></h2>
				<ol class="mcc-steps">
					<li><?php esc_html_e( 'Услуги и специалисты — в разделах меню слева. Название, описание и фото задаются в карточке.', 'mete-clinic-content' ); ?></li>
					<li><?php esc_html_e( 'Массовые изменения цен удобнее делать через «Прайс (Excel)»: выгрузить, поправить и загрузить обратно.', 'mete-clinic-content' ); ?></li>
					<li><?php esc_html_e( 'Контакты, запись, карта и отзывы — в разделе «Настройки».', 'mete-clinic-content' ); ?></li>
					<li><?php esc_html_e( '«Инструменты» нужны для первого запуска: импорт исходных данных и полного каталога.', 'mete-clinic-content' ); ?></li>
				</ol>
			</div>
		</div>
		<?php
	}

	/**
	 * Filtered services list URL.
	 *
	 * @param string $filter Filter key.
	 * @return string
	 */
	public static function services_link( $filter ) {
		return add_query_arg(
			array(
				'post_type'  => MCC_Post_Types::SERVICE,
				'mcc_filter' => $filter,
			),
			admin_url( 'edit.php' )
		);
	}
}
