<?php
/**
 * Hero section.
 *
 * @package Mete_Clinic
 */

$mete_phone    = mete_phone_href();
$mete_phone_tx = mete_phone_label();
$mete_max      = mete_max_url();
$mete_booking  = mete_booking_url();
$mete_hero_img = mete_asset_url( 'images/hero.svg' );
?>
<section class="hero" id="top" aria-labelledby="hero-title">
	<div class="container hero__inner">
		<div class="hero__copy">
			<p class="eyebrow"><?php esc_html_e( 'Клиника эпиляции и косметологии', 'mete-clinic' ); ?></p>
			<h1 class="hero__title" id="hero-title"><?php esc_html_e( 'Ваша красота', 'mete-clinic' ); ?><br><?php esc_html_e( 'в надёжных руках', 'mete-clinic' ); ?></h1>
			<p class="hero__lead"><?php esc_html_e( 'MÉTE CLINIC — профессиональный уход за кожей и эстетические процедуры с индивидуальным подбором.', 'mete-clinic' ); ?></p>

			<div class="hero__actions">
				<?php
				echo mete_link(
					$mete_booking,
					esc_html__( 'Записаться на процедуру', 'mete-clinic' ),
					array(
						'class'      => 'btn btn--primary',
						'icon_after' => 'arrow',
					)
				);
				?>

				<span class="hero__phone-group">
					<?php if ( '' !== $mete_phone ) : ?>
						<?php
						echo mete_link(
							$mete_phone,
							esc_html( $mete_phone_tx ),
							array(
								'class' => 'btn btn--ghost',
								'icon'  => 'phone',
							)
						);
						?>
					<?php endif; ?>

					<?php if ( '' !== $mete_max ) : ?>
						<span class="hero__max">
							<?php
							echo mete_link(
								$mete_max,
								'',
								array(
									'class'      => 'social-link',
									'aria_label' => __( 'Написать в MAX', 'mete-clinic' ),
									'title'      => __( 'Написать в MAX', 'mete-clinic' ),
									'icon'       => 'max',
								)
							);
							?>
						</span>
					<?php endif; ?>
				</span>
			</div>

			<ul class="hero__benefits">
				<li class="benefit">
					<span class="benefit__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.4">
							<path d="M12 3.5l1.7 5.2 5.3 1.8-5.3 1.8L12 17.5l-1.7-5.2L5 10.5l5.3-1.8L12 3.5Z" stroke-linejoin="round"/>
							<circle cx="18.5" cy="18" r="1.4"/>
						</svg>
					</span>
					<span class="benefit__text">
						<strong><?php esc_html_e( 'Индивидуальный подход', 'mete-clinic' ); ?></strong>
						<span><?php esc_html_e( 'Программа ухода под вашу задачу.', 'mete-clinic' ); ?></span>
					</span>
				</li>
				<li class="benefit">
					<span class="benefit__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.4">
							<path d="M4.5 5.5h15v10h-8l-4.5 3v-3H4.5Z" stroke-linejoin="round"/>
							<path d="M8.5 9.5h7M8.5 12.5h4.5" stroke-linecap="round"/>
						</svg>
					</span>
					<span class="benefit__text">
						<strong><?php esc_html_e( 'Консультация специалиста', 'mete-clinic' ); ?></strong>
						<span><?php esc_html_e( 'Обсуждаем процедуру до начала.', 'mete-clinic' ); ?></span>
					</span>
				</li>
				<li class="benefit">
					<span class="benefit__icon" aria-hidden="true">
						<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.4">
							<path d="M20 12.5 12.5 20a2 2 0 0 1-2.8 0L4 14.3V4h10.3l5.7 5.7a2 2 0 0 1 0 2.8Z" stroke-linejoin="round"/>
							<circle cx="9" cy="9" r="1.6"/>
						</svg>
					</span>
					<span class="benefit__text">
						<strong><?php esc_html_e( 'Прозрачные цены', 'mete-clinic' ); ?></strong>
						<span><?php esc_html_e( 'Ключевые позиции прайса — на странице.', 'mete-clinic' ); ?></span>
					</span>
				</li>
			</ul>
		</div>

		<div class="hero__visual">
			<?php if ( '' !== $mete_hero_img ) : ?>
				<img src="<?php echo esc_url( $mete_hero_img ); ?>" alt="" width="1000" height="1240" fetchpriority="high" decoding="async">
			<?php endif; ?>
			<span class="hero__script" aria-hidden="true">
				<span><?php esc_html_e( 'забота', 'mete-clinic' ); ?><br><?php esc_html_e( 'о вашей', 'mete-clinic' ); ?><br><?php esc_html_e( 'красоте', 'mete-clinic' ); ?></span>
				<svg viewBox="0 0 120 80" width="92" height="62" fill="none" stroke="currentColor" stroke-width="1">
					<path d="M6 8c26-2 54 14 66 44" stroke-linecap="round"/>
					<path d="M66 42l8 14 14-6" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</span>
		</div>
	</div>
</section>
