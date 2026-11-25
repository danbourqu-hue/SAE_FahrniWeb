<?php
/**
 * Übersicht Referenzen: Filter nach Jahr (Taxonomie «Jahre»), Suche nach Projekt oder Ort.
 */
get_header();
fahrni_head( 'Referenzen', fahrni_menu_text( 'referenzen' ), [ [ 'Referenzen', '' ] ] );
$terms = get_terms( [ 'taxonomy' => 'referenz_jahr', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'DESC' ] );
usort( $terms, fn( $a, $b ) => ( is_numeric( $b->name ) <=> is_numeric( $a->name ) ) ?: strcmp( $b->name, $a->name ) );
$total = (int) $GLOBALS['wp_query']->found_posts; ?>
<div class="filters" role="group" aria-label="Referenzen filtern">
	<button class="chip" data-group="alle" aria-pressed="true">Alle <small><?php echo $total; ?></small></button>
	<?php foreach ( $terms as $t ) : ?><button class="chip" data-group="<?php echo esc_attr( $t->name ); ?>" aria-pressed="false"><?php echo esc_html( $t->name ); ?> <small><?php echo (int) $t->count; ?></small></button><?php endforeach; ?>
	<input id="ref-search" class="search" type="search" placeholder="Projekt oder Ort suchen" aria-label="Projekt oder Ort suchen">
</div>
<div class="grid" id="ref-grid"><?php while ( have_posts() ) : the_post(); echo fahrni_ref_tile( get_post() ); endwhile; ?></div>
<p class="empty" id="ref-empty" hidden>Keine Referenz gefunden.</p>
<?php get_footer();
