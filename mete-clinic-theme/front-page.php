<?php
/**
 * Front page: landing built from plugin data.
 *
 * @package Mete_Clinic
 */

get_header();
?>
<main id="main" tabindex="-1">
	<?php
	get_template_part( 'template-parts/hero' );
	get_template_part( 'template-parts/service-categories' );
	get_template_part( 'template-parts/home-categories' );
	get_template_part( 'template-parts/popular-services' );
	get_template_part( 'template-parts/specialists' );
	get_template_part( 'template-parts/reviews' );
	get_template_part( 'template-parts/about' );
	get_template_part( 'template-parts/contacts' );
	?>
</main>
<?php
get_footer();
