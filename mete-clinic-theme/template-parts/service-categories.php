<?php
/**
 * Home page: service directions (categories).
 *
 * Карточки направлений строятся из таксономии услуг, а не из кода шаблона.
 * На главной показываем первые 8 направлений, остальные раскрываются кнопкой.
 *
 * @package Mete_Clinic
 */

if ( ! mete_plugin_active() ) {
	return;
}

$mete_terms = mete_service_categories();

if ( empty( $mete_terms ) ) {
	return;
}

$mete_visible_limit = 8;
$mete_total         = count( $mete_terms );
$mete_has_more      = $mete_total > $mete_visible_limit;
$mete_more_label    = sprintf(
	/* translators: %s: number of service categories. */
	__( 'Показать все направления (%s)', 'mete-clinic' ),
	number_format_i18n( $mete_total )
);
?>
<section class="section" id="services" aria-labelledby="services-title">
	<div class="container">
		<div class="section__head">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'Основные направления', 'mete-clinic' ); ?></p>
				<h2 class="section__title" id="services-title"><?php esc_html_e( 'Наши услуги', 'mete-clinic' ); ?></h2>
			</div>
			<?php
			echo mete_link(
				mete_catalog_url(),
				esc_html__( 'Полный каталог', 'mete-clinic' ),
				array(
					'class'      => 'link-arrow',
					'icon_after' => 'arrow',
				)
			);
			?>
		</div>

		<div class="services-grid" id="services-grid">
			<?php foreach ( $mete_terms as $mete_index => $mete_term ) : ?>
				<?php
				$mete_image = mete_category_image_url( $mete_term );
				$mete_text  = wp_strip_all_tags( (string) $mete_term->description );
				$mete_extra = $mete_has_more && $mete_index >= $mete_visible_limit;
				?>
				<a class="service-card reveal<?php echo $mete_extra ? ' service-card--extra' : ''; ?>" href="<?php echo esc_url( mete_category_link( $mete_term ) ); ?>"<?php echo $mete_extra ? ' hidden' : ''; ?>>
					<div class="service-card__media">
						<?php if ( '' !== $mete_image ) : ?>
							<img src="<?php echo esc_url( $mete_image ); ?>" alt="" width="800" height="600" loading="lazy" decoding="async">
						<?php endif; ?>
					</div>
					<div class="service-card__body">
						<h3 class="service-card__title"><?php echo esc_html( $mete_term->name ); ?></h3>
						<?php if ( '' !== $mete_text ) : ?>
							<p class="service-card__text"><?php echo esc_html( $mete_text ); ?></p>
						<?php endif; ?>
						<span class="service-card__arrow" aria-hidden="true"><?php echo mete_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>

		<?php if ( $mete_has_more ) : ?>
			<p class="services-more">
				<a
					class="btn btn--ghost services-more__toggle"
					href="<?php echo esc_url( mete_catalog_url() ); ?>"
					data-services-toggle
					data-label-more="<?php echo esc_attr( $mete_more_label ); ?>"
					data-label-less="<?php esc_attr_e( 'Свернуть направления', 'mete-clinic' ); ?>"
					aria-controls="services-grid"
					aria-expanded="false"
				>
					<?php echo esc_html( $mete_more_label ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</section>
