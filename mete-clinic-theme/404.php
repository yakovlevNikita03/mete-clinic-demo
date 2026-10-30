<?php
/**
 * 404 page.
 *
 * @package Mete_Clinic
 */

get_header();
?>
<main id="main" tabindex="-1">
	<section class="section">
		<div class="container container--narrow">
			<p class="eyebrow"><?php esc_html_e( 'Страница не найдена', 'mete-clinic' ); ?></p>
			<h1 class="section__title"><?php esc_html_e( 'Такой страницы нет', 'mete-clinic' ); ?></h1>
			<p class="page-head__lead"><?php esc_html_e( 'Возможно, страница переехала. Вернитесь на главную или посмотрите каталог услуг.', 'mete-clinic' ); ?></p>
			<div class="single-actions">
				<?php
				echo mete_link(
					home_url( '/' ),
					esc_html__( 'На главную', 'mete-clinic' ),
					array( 'class' => 'btn btn--primary' )
				);
				echo mete_link(
					mete_catalog_url(),
					esc_html__( 'Каталог услуг', 'mete-clinic' ),
					array( 'class' => 'btn btn--ghost' )
				);
				?>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
