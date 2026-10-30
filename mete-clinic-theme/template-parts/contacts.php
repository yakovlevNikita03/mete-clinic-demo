<?php
/**
 * Contacts section: contact card, messengers and map.
 *
 * @package Mete_Clinic
 */

$mete_address   = trim( mete_setting( 'address' ) );
$mete_hours     = trim( mete_setting( 'working_hours' ) );
$mete_phone     = mete_phone_href();
$mete_phone_tx  = mete_phone_label();
$mete_max       = mete_max_url();
$mete_messengers = mete_messengers();
$mete_booking   = mete_booking_url();
$mete_map_embed = trim( mete_setting( 'map_embed_url' ) );
$mete_map_url   = trim( mete_setting( 'map_url' ) );
$mete_map_ok    = mete_is_http_url( $mete_map_embed );

$mete_rows = array();

if ( '' !== $mete_address ) {
	$mete_rows[] = array(
		'icon'  => 'pin',
		'label' => __( 'Адрес', 'mete-clinic' ),
		'value' => esc_html( $mete_address ),
		'href'  => '',
		'aria'  => '',
	);
}

if ( '' !== $mete_hours ) {
	$mete_rows[] = array(
		'icon'  => 'clock',
		'label' => __( 'Часы работы', 'mete-clinic' ),
		'value' => esc_html( $mete_hours ),
		'href'  => '',
		'aria'  => '',
	);
}

if ( '' !== $mete_phone ) {
	$mete_rows[] = array(
		'icon'  => 'phone',
		'label' => __( 'Телефон', 'mete-clinic' ),
		'value' => esc_html( $mete_phone_tx ),
		'href'  => $mete_phone,
		'aria'  => '',
	);
}

if ( '' !== $mete_max ) {
	$mete_rows[] = array(
		'icon'  => 'max',
		'label' => __( 'Мессенджер', 'mete-clinic' ),
		'value' => esc_html__( 'Написать нам', 'mete-clinic' ),
		'href'  => $mete_max,
		'aria'  => __( 'Написать нам в MAX', 'mete-clinic' ),
	);
}
?>
<section class="section contacts" id="contacts" aria-labelledby="contacts-title">
	<div class="container">
		<div class="section__head">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'Контакты', 'mete-clinic' ); ?></p>
				<h2 class="section__title" id="contacts-title"><?php esc_html_e( 'Как нас найти', 'mete-clinic' ); ?></h2>
			</div>
		</div>

		<div class="contacts__grid<?php echo $mete_map_ok ? '' : ' contacts__grid--single'; ?>">
			<div class="contact-card">
				<p class="contact-card__lead"><?php esc_html_e( 'Запишитесь удобным способом — онлайн, по телефону или в мессенджере.', 'mete-clinic' ); ?></p>

				<?php if ( ! empty( $mete_rows ) ) : ?>
					<div class="contact-list">
						<?php foreach ( $mete_rows as $mete_row ) : ?>
							<div class="contact-row">
								<span class="contact-row__icon" aria-hidden="true"><?php echo mete_icon( $mete_row['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<div class="contact-row__body">
									<span class="contact-row__label"><?php echo esc_html( $mete_row['label'] ); ?></span>
									<span class="contact-row__value">
										<?php
										if ( '' !== $mete_row['href'] ) {
											echo mete_link(
												$mete_row['href'],
												$mete_row['value'],
												array( 'aria_label' => $mete_row['aria'] )
											);
										} else {
											echo $mete_row['value']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped on build.
										}
										?>
									</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="contact-empty"><?php esc_html_e( 'Контактные данные уточняются.', 'mete-clinic' ); ?></p>
				<?php endif; ?>

				<div class="contact-actions">
					<?php
					echo mete_link(
						$mete_booking,
						esc_html__( 'Записаться на процедуру', 'mete-clinic' ),
						array( 'class' => 'btn btn--primary' )
					);
					?>

					<?php if ( ! empty( $mete_messengers ) ) : ?>
						<div class="messengers">
							<?php foreach ( $mete_messengers as $mete_messenger ) : ?>
								<?php
								echo mete_link(
									$mete_messenger['url'],
									mete_icon( 'chat' ) . esc_html( $mete_messenger['name'] ),
									array(
										'class'      => 'chip',
										'aria_label' => $mete_messenger['label'],
									)
								);
								?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! mete_has_booking() ) : ?>
					<p class="booking-note"><?php esc_html_e( 'Онлайн-запись Yclients подключается. Пока можно записаться по телефону или написать в мессенджере.', 'mete-clinic' ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $mete_map_ok ) : ?>
				<div class="map-card">
					<div class="map-frame">
						<iframe
							src="<?php echo esc_url( $mete_map_embed ); ?>"
							loading="lazy"
							title="<?php echo esc_attr( '' !== $mete_address ? sprintf( /* translators: %s: clinic address. */ __( 'Карта: MÉTE CLINIC, %s', 'mete-clinic' ), $mete_address ) : __( 'Карта: MÉTE CLINIC', 'mete-clinic' ) ); ?>"
							sandbox="allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-top-navigation-by-user-activation"
							referrerpolicy="no-referrer-when-downgrade"
							allowfullscreen></iframe>
					</div>
					<div class="map-card__foot">
						<span class="map-card__provider"><?php echo esc_html( mete_map_provider_label() ); ?></span>
						<div class="map-links">
							<?php if ( mete_is_http_url( $mete_map_url ) ) : ?>
								<?php
								echo mete_link(
									$mete_map_url,
									esc_html__( 'Открыть в 2ГИС', 'mete-clinic' ),
									array( 'class' => 'map-link' )
								);
								?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
