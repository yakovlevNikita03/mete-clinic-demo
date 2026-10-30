<?php
/**
 * Fallback template.
 *
 * @package Mete_Clinic
 */

get_header();
?>
<main id="main" tabindex="-1">
	<section class="section">
		<div class="container container--narrow">
			<?php if ( have_posts() ) : ?>
				<h1 class="section__title"><?php echo esc_html( wp_get_document_title() ); ?></h1>
				<div class="post-list">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<article class="post-list__item">
							<h2 class="post-list__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<div class="page-content"><?php the_excerpt(); ?></div>
						</article>
						<?php
					endwhile;
					?>
				</div>

				<?php
				the_posts_pagination( array(
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
				) );
				?>
			<?php else : ?>
				<h1 class="section__title"><?php esc_html_e( 'Записи не найдены', 'mete-clinic' ); ?></h1>
				<p class="page-head__lead"><?php esc_html_e( 'Попробуйте вернуться на главную страницу.', 'mete-clinic' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php
get_footer();
