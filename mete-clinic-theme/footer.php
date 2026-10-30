<?php
/**
 * Site footer.
 *
 * @package Mete_Clinic
 */

$mete_messengers = mete_messengers();
?>
<footer class="site-footer">
	<div class="container site-footer__top">
		<div class="site-footer__brand">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php mete_footer_logo(); ?>
			</a>
		</div>

		<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Навигация в подвале', 'mete-clinic' ); ?>">
			<?php
			foreach ( mete_nav_items() as $mete_item ) {
				echo '<a href="' . esc_url( $mete_item['url'] ) . '">' . esc_html( $mete_item['label'] ) . '</a>';
			}
			?>
		</nav>

		<?php if ( ! empty( $mete_messengers ) ) : ?>
			<div class="site-footer__social">
				<?php foreach ( $mete_messengers as $mete_messenger ) : ?>
					<?php
					echo mete_link(
						$mete_messenger['url'],
						esc_html( $mete_messenger['name'] ),
						array( 'class' => 'social-link' )
					);
					?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="container site-footer__bottom">
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'Все права защищены.', 'mete-clinic' ); ?></p>
		<p><?php esc_html_e( 'Информация на сайте не является публичной офертой.', 'mete-clinic' ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
