<?php
/**
 * Home page: service directions.
 *
 * Карточки не кликабельные и не зависят от таксономии: состав и порядок
 * задаются на вкладке «Главная» в настройках плагина. Так направления можно
 * переименовать и переставить, не перенося при этом услуги.
 *
 * @package Mete_Clinic
 */

if ( ! mete_plugin_active() ) {
	return;
}

$mete_directions = mete_home_directions();

if ( empty( $mete_directions ) ) {
	return;
}

$mete_count    = count( $mete_directions );
$mete_modifier = $mete_count >= 7 ? 'services-grid--count-many' : 'services-grid--count-' . (int) $mete_count;
?>
<section class="section" id="services" aria-labelledby="services-title">
	<div class="container">
		<div class="section__head reveal">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'Наши услуги', 'mete-clinic' ); ?></p>
				<h2 class="section__title" id="services-title"><?php esc_html_e( 'Основные направления', 'mete-clinic' ); ?></h2>
			</div>
		</div>

		<ul class="services-grid <?php echo esc_attr( $mete_modifier ); ?>" role="list" data-reveal-group>
			<?php foreach ( $mete_directions as $mete_item ) : ?>
				<?php $mete_image = mete_direction_image_url( $mete_item ); ?>
				<li class="services-grid__item">
					<article class="service-card service-card--static reveal">
						<div class="service-card__media">
							<?php if ( '' !== $mete_image ) : ?>
								<img src="<?php echo esc_url( $mete_image ); ?>" alt="" width="800" height="600" loading="lazy" decoding="async">
							<?php endif; ?>
						</div>
						<div class="service-card__body">
							<h3 class="service-card__title"><?php echo esc_html( $mete_item['title'] ); ?></h3>
							<?php if ( '' !== $mete_item['text'] ) : ?>
								<p class="service-card__text"><?php echo esc_html( $mete_item['text'] ); ?></p>
							<?php endif; ?>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
