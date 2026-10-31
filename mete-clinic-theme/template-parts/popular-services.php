<?php
/**
 * Home page: popular services flagged in the admin.
 *
 * @package Mete_Clinic
 */

if ( ! mete_plugin_active() ) {
	return;
}

$mete_popular = mete_popular_services();

if ( ! $mete_popular->have_posts() ) {
	wp_reset_postdata();

	return;
}
?>
<section class="section section--tight" id="prices" aria-labelledby="prices-title">
	<div class="container">
		<div class="price-panel">
			<div class="price-panel__head reveal">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'Популярные услуги', 'mete-clinic' ); ?></p>
					<h2 class="section__title" id="prices-title"><?php esc_html_e( 'Прайс — ключевые позиции', 'mete-clinic' ); ?></h2>
				</div>
				<?php
				echo mete_link(
					mete_catalog_url(),
					esc_html__( 'Полный прайс', 'mete-clinic' ),
					array(
						'class'      => 'link-arrow',
						'icon_after' => 'arrow',
					)
				);
				?>
			</div>

			<ul class="price-list">
				<?php
				while ( $mete_popular->have_posts() ) :
					$mete_popular->the_post();

					$mete_id       = get_the_ID();
					$mete_price    = mete_price_text( $mete_id );
					$mete_duration = mete_duration_text( $mete_id );
					$mete_excerpt  = trim( wp_strip_all_tags( get_the_excerpt() ) );
					?>
					<li class="price-row reveal">
						<a class="price-row__name" href="<?php the_permalink(); ?>">
							<?php the_title(); ?>
							<?php if ( '' !== $mete_excerpt ) : ?>
								<small><?php echo esc_html( $mete_excerpt ); ?></small>
							<?php elseif ( '' !== $mete_duration ) : ?>
								<small><?php echo esc_html( $mete_duration ); ?></small>
							<?php endif; ?>
						</a>
						<span class="price-row__value"><?php echo esc_html( '' !== $mete_price ? $mete_price : '—' ); ?></span>
					</li>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</ul>

			<div class="price-panel__foot">
				<p class="price-note"><?php esc_html_e( 'Цены — выборочные позиции из прайса клиники в Yclients. Актуальную стоимость и состав процедур уточняйте при записи.', 'mete-clinic' ); ?></p>
				<div class="price-panel__actions">
					<?php
					echo mete_link(
						mete_catalog_url(),
						esc_html__( 'Полный прайс и запись', 'mete-clinic' ),
						array( 'class' => 'btn btn--primary' )
					);
					?>
					<?php if ( ! mete_has_booking() ) : ?>
						<p class="booking-note"><?php esc_html_e( 'Онлайн-запись Yclients подключается. Пока можно записаться по телефону.', 'mete-clinic' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>
