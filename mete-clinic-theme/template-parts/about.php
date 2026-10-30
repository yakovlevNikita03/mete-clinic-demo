<?php
/**
 * About section.
 *
 * @package Mete_Clinic
 */

$mete_clinic_img = mete_asset_url( 'images/clinic.svg' );
?>
<section class="section about" id="about" aria-labelledby="about-title">
	<div class="container about__inner">
		<div class="about__visual">
			<?php if ( '' !== $mete_clinic_img ) : ?>
				<img src="<?php echo esc_url( $mete_clinic_img ); ?>" alt="" width="1200" height="900" loading="lazy" decoding="async">
			<?php endif; ?>
		</div>
		<div class="about__copy">
			<p class="eyebrow"><?php esc_html_e( 'О клинике', 'mete-clinic' ); ?></p>
			<h2 class="section__title" id="about-title"><?php esc_html_e( 'Клиника эпиляции и косметологии в Тюмени', 'mete-clinic' ); ?></h2>
			<p class="about__text"><?php esc_html_e( 'MÉTE CLINIC объединяет направления по уходу за кожей и телом: от лазерной эпиляции и чисток лица до аппаратных и инъекционных процедур. Программу и подходящий формат визита обсуждаем индивидуально, а полный перечень услуг и расписание ведём в Yclients.', 'mete-clinic' ); ?></p>
			<ul class="about__list">
				<li><?php esc_html_e( 'Четыре направления услуг и команда специалистов.', 'mete-clinic' ); ?></li>
				<li><?php esc_html_e( 'Запись на процедуру — по телефону или онлайн.', 'mete-clinic' ); ?></li>
				<li><?php esc_html_e( 'Адрес и карта — в блоке контактов.', 'mete-clinic' ); ?></li>
			</ul>
			<?php
			echo mete_link(
				mete_booking_url(),
				esc_html__( 'Записаться на процедуру', 'mete-clinic' ),
				array( 'class' => 'btn btn--primary' )
			);
			?>
		</div>
	</div>
</section>
