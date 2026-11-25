<?php
/**
 * Presse: Liste der Fachberichte. Jeder öffnet sich auf einer eigenen Seite im Website-Rahmen.
 */
get_header();
fahrni_head( 'Presse', 'Fahrni-Projekte in der Fachpresse. Jeder Bericht öffnet sich hier auf der Website.', [ [ 'Unternehmen', fahrni_url( 'unternehmen' ) ], [ 'Presse', '' ] ] ); ?>
<div class="list-tiles">
<?php $k = 0; while ( have_posts() ) : the_post(); $jahr = fahrni_meta( 'jahr' ); ?>
	<a class="tile reveal" style="--d:<?php echo ( $k++ % 3 ) * .05; ?>s" href="<?php the_permalink(); ?>"><div class="tile-top"><span class="pdf">PDF<?php echo $jahr ? ' · ' . esc_html( (string) $jahr ) : ''; ?></span><?php echo fahrni_arrow(); ?></div><h3><?php the_title(); ?></h3></a>
<?php endwhile; ?>
</div>
<?php get_footer();
