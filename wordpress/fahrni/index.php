<?php
/**
 * Rückfall-Vorlage (z. B. Suche, Kategorien): einfache Kachelliste.
 */
get_header();
fahrni_head( is_search() ? 'Suche: ' . get_search_query() : wp_strip_all_tags( get_the_archive_title() ) ); ?>
<div class="grid">
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
	<a class="tile span-4 reveal" href="<?php the_permalink(); ?>"><div class="tile-top"><span class="eyebrow"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?></span><?php echo fahrni_arrow(); ?></div><h3><?php the_title(); ?></h3></a>
<?php endwhile; else : ?>
	<p class="empty">Nichts gefunden.</p>
<?php endif; ?>
</div>
<?php get_footer();
