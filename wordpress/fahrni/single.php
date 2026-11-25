<?php
/**
 * Einzelne News: Kurztext (Textauszug), Text, Bild, Blättern.
 */
get_header();
while ( have_posts() ) : the_post();
	$img = fahrni_thumb( null, 'fahrni-wide' );
	fahrni_head( preg_replace( '/^Neuer Auftrag\s+/u', '', get_the_title() ), has_excerpt() ? get_the_excerpt() : '', [ [ 'News', fahrni_url( 'news' ) ], [ 'Neuer Auftrag', '' ] ] ); ?>
	<div class="detail">
		<div class="prose prose-card tile" data-light><p class="eyebrow"><b>Neuer Auftrag</b><?php echo fahrni_meta( 'ort' ) ? ' · ' . esc_html( (string) fahrni_meta( 'ort' ) ) : ''; ?></p><?php the_content(); ?></div>
		<div class="facts"><a class="tile tile-dark" href="<?php echo esc_url( fahrni_url( 'referenzen' ) ); ?>"><div class="tile-top"><span class="eyebrow">Weitere Projekte</span><?php echo fahrni_arrow(); ?></div><h3>Alle Referenzen</h3></a></div>
	</div>
	<?php if ( $img ) : ?><div class="gallery"><button type="button" aria-label="Bild vergrössern"><img src="<?php echo esc_url( $img ); ?>" alt="<?php the_title_attribute(); ?>"></button></div><?php endif; ?>
	<?php echo fahrni_pager( get_next_post(), get_previous_post(), '← Neuer', 'Älter →' ); ?>
	<?php echo fahrni_lightbox();
endwhile;
get_footer();
