<?php
/**
 * Normale Seite (z. B. Prinzipien, Datenschutz, Beschaffungsbedingungen).
 * Kurztext unter dem Titel = Textauszug der Seite. Beitragsbild erscheint neben dem Text.
 */
get_header();
while ( have_posts() ) : the_post();
	$parent = wp_get_post_parent_id( get_the_ID() );
	$crumbs = $parent ? [ [ get_the_title( $parent ), get_permalink( $parent ) ], [ get_the_title(), '' ] ] : [ [ get_the_title(), '' ] ];
	fahrni_head( get_the_title(), has_excerpt() ? get_the_excerpt() : '', $crumbs );
	$img = fahrni_thumb( null, 'fahrni-wide' ); ?>
	<div class="detail">
		<div class="prose prose-card"<?php echo $img ? '' : ' style="grid-column:1/-1; max-width:900px"'; ?>><?php the_content(); ?></div>
		<?php if ( $img ) : ?><div class="facts"><div class="tile s-img" style="min-height:420px" data-light><div class="media"><img src="<?php echo esc_url( $img ); ?>" alt=""></div></div></div><?php endif; ?>
	</div>
<?php endwhile;
get_footer();
