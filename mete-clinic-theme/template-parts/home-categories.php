<?php
/**
 * Home page: popular category list.
 *
 * Кликабельный список под направлениями. Состав, порядок и подписи
 * задаются на вкладке «Главная» в настройках плагина; категории без услуг
 * на сайте пропускаются.
 *
 * @package Mete_Clinic
 */

if ( ! mete_plugin_active() ) {
	return;
}

$mete_categories = mete_home_categories();

if ( empty( $mete_categories ) ) {
	return;
}
?>
<section class="section section--tight" id="categories" aria-labelledby="categories-title">
	<div class="container">
		<div class="section__head reveal">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'Каталог', 'mete-clinic' ); ?></p>
				<h2 class="section__title" id="categories-title"><?php esc_html_e( 'Популярные категории', 'mete-clinic' ); ?></h2>
			</div>
		</div>

		<div class="category-panel">
			<ul class="category-list" role="list" data-reveal-group>
				<?php foreach ( $mete_categories as $mete_index => $mete_item ) : ?>
					<li class="category-list__item">
						<a class="category-list__link reveal" href="<?php echo esc_url( mete_category_link( $mete_item['term'] ) ); ?>">
							<span class="category-list__index" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', (int) $mete_index + 1 ) ); ?></span>
							<span class="category-list__body">
								<span class="category-list__name"><?php echo esc_html( $mete_item['label'] ); ?></span>
								<span class="category-list__count"><?php echo esc_html( mete_service_count_text( $mete_item['count'] ) ); ?></span>
							</span>
							<span class="category-list__arrow" aria-hidden="true"><?php echo mete_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<p class="category-panel__foot">
				<?php
				echo mete_link(
					mete_catalog_url(),
					esc_html__( 'Перейти в полный каталог', 'mete-clinic' ),
					array(
						'class'      => 'btn btn--primary category-panel__button',
						'icon_after' => 'arrow',
					)
				);
				?>
			</p>
		</div>
	</div>
</section>
