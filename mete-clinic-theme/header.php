<?php
/**
 * Site header.
 *
 * @package Mete_Clinic
 */

$mete_phone    = mete_phone_href();
$mete_phone_tx = mete_phone_label();
$mete_max      = mete_max_url();
$mete_booking  = mete_booking_url();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $mete_hero = mete_asset_url( 'images/hero.svg' ); ?>
	<?php if ( '' !== $mete_hero ) : ?>
		<link rel="preload" as="image" href="<?php echo esc_url( $mete_hero ); ?>" fetchpriority="high">
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Перейти к содержанию', 'mete-clinic' ); ?></a>

<header class="site-header" data-header>
	<div class="site-header__bar container">
		<?php mete_brand(); ?>

		<nav class="site-nav" aria-label="<?php esc_attr_e( 'Основная навигация', 'mete-clinic' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'site-nav__list',
					'depth'          => 1,
					'fallback_cb'    => false,
				) );
			} else {
				mete_default_nav( 'site-nav__list' );
			}
			?>
		</nav>

		<div class="site-header__actions">
			<?php if ( '' !== $mete_max ) : ?>
				<span class="site-header__max">
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

			<?php if ( '' !== $mete_phone ) : ?>
				<?php
				echo mete_link(
					$mete_phone,
					esc_html( $mete_phone_tx ),
					array(
						'class' => 'site-header__phone',
						'icon'  => 'phone',
					)
				);
				?>
			<?php endif; ?>

			<?php
			echo mete_link(
				$mete_booking,
				esc_html__( 'Записаться', 'mete-clinic' ),
				array( 'class' => 'btn btn--primary site-header__cta' )
			);
			?>

			<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="<?php esc_attr_e( 'Открыть меню', 'mete-clinic' ); ?>" data-menu-toggle>
				<span class="menu-toggle__line"></span>
				<span class="menu-toggle__line"></span>
				<span class="menu-toggle__line"></span>
			</button>
		</div>
	</div>

	<div class="mobile-menu" id="mobile-menu" data-menu hidden>
		<div class="container mobile-menu__inner">
			<nav aria-label="<?php esc_attr_e( 'Мобильная навигация', 'mete-clinic' ); ?>">
				<?php
				if ( has_nav_menu( 'primary' ) ) {
					wp_nav_menu( array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'mobile-menu__list',
						'depth'          => 1,
						'fallback_cb'    => false,
					) );
				} else {
					mete_default_nav( 'mobile-menu__list' );
				}
				?>
			</nav>

			<?php if ( '' !== $mete_phone ) : ?>
				<?php
				echo mete_link(
					$mete_phone,
					esc_html( $mete_phone_tx ),
					array(
						'class' => 'mobile-menu__phone',
						'icon'  => 'phone',
					)
				);
				?>
			<?php endif; ?>

			<?php
			echo mete_link(
				$mete_booking,
				esc_html__( 'Записаться', 'mete-clinic' ),
				array( 'class' => 'btn btn--primary btn--block' )
			);
			?>
		</div>
	</div>
</header>
