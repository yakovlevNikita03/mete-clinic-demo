<?php
/**
 * Reviews section: official widget placeholder or iframe configured in the plugin.
 *
 * @package Mete_Clinic
 */

$mete_embed  = trim( mete_setting( 'reviews_embed_url' ) );
$mete_widget = trim( mete_setting( 'reviews_widget_html' ) );
$mete_url    = trim( mete_setting( 'reviews_url' ) );

if ( '' === $mete_embed && '' === $mete_widget ) {
	return;
}

$mete_title = trim( mete_setting( 'reviews_title' ) );
$mete_text  = trim( mete_setting( 'reviews_text' ) );

if ( '' === $mete_title ) {
	$mete_title = __( 'Отзывы и рейтинг', 'mete-clinic' );
}

if ( '' === $mete_text ) {
	$mete_text = __( 'Отзывы посетителей опубликованы в официальной карточке клиники. Мы не редактируем их и не переносим оценки на сайт — актуальный рейтинг и все отзывы смотрите в официальном источнике.', 'mete-clinic' );
}
?>
<section class="section section--tight" id="reviews" aria-labelledby="reviews-title">
	<div class="container reviews">
		<div class="reviews__copy">
			<p class="eyebrow"><?php esc_html_e( 'Отзывы', 'mete-clinic' ); ?></p>
			<h2 class="section__title" id="reviews-title"><?php echo esc_html( $mete_title ); ?></h2>
			<p class="reviews__text"><?php echo esc_html( $mete_text ); ?></p>

			<?php if ( mete_is_http_url( $mete_url ) ) : ?>
				<?php
				echo mete_link(
					$mete_url,
					esc_html__( 'Смотреть все отзывы', 'mete-clinic' ),
					array(
						'class'      => 'link-arrow',
						'icon_after' => 'arrow',
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div class="reviews__widget">
			<?php if ( mete_is_http_url( $mete_embed ) ) : ?>
				<iframe
					src="<?php echo esc_url( $mete_embed ); ?>"
					title="<?php echo esc_attr( $mete_title ); ?>"
					loading="lazy"
					referrerpolicy="no-referrer-when-downgrade"
					allowfullscreen></iframe>
			<?php else : ?>
				<?php
				/*
				 * Код официального виджета вставляется только администратором
				 * с правом unfiltered_html и сохраняется в настройках плагина.
				 */
				echo $mete_widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			<?php endif; ?>
		</div>
	</div>
</section>
