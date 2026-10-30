<?php
/**
 * Static page.
 *
 * @package Mete_Clinic
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="main" tabindex="-1">
		<section class="section">
			<div class="container container--narrow">
				<h1 class="section__title"><?php the_title(); ?></h1>
				<div class="page-content">
					<?php the_content(); ?>
				</div>
			</div>
		</section>
	</main>
	<?php
endwhile;

get_footer();
