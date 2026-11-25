<?php
/**
 * Referenz: Leistungen (Text), Beteiligte, Ort/Jahr, Bildergalerie mit Grossansicht, Blättern.
 */
get_header();
while ( have_posts() ) : the_post();
	$ort = (string) fahrni_meta( 'ort' );
	$terms = get_the_terms( get_the_ID(), 'referenz_jahr' );
	$facts = [];
	foreach ( [ 'bauherr' => 'Bauherr', 'architekt' => 'Architekt', 'generalunternehmer' => 'Generalunternehmer' ] as $k => $l ) {
		if ( $v = fahrni_meta( $k ) ) $facts[] = [ $l, $v ];
	}
	foreach ( fahrni_lines( (string) fahrni_meta( 'beteiligte' ) ) as $line ) {
		$p = array_map( 'trim', explode( ':', $line, 2 ) );
		$facts[] = count( $p ) === 2 ? $p : [ 'Beteiligt', $p[0] ];
	}
	fahrni_head( get_the_title(), $ort, [ [ 'Referenzen', fahrni_url( 'referenzen' ) ], [ get_the_title(), '' ] ] ); ?>
	<div class="detail">
		<div class="prose prose-card tile" data-light><?php the_content(); ?></div>
		<div class="facts">
			<?php if ( $facts ) : ?><div class="prose-card tile" data-light><dl><?php foreach ( $facts as [ $l, $v ] ) echo '<dt>' . esc_html( $l ) . '</dt><dd>' . esc_html( $v ) . '</dd>'; ?></dl></div><?php endif; ?>
			<div class="prose-card tile tile-grey" data-light><dl><dt>Ort</dt><dd><?php echo esc_html( $ort ); ?></dd><?php if ( $terms ) : ?><dt>Jahr</dt><dd><?php echo esc_html( $terms[0]->name ); ?></dd><?php endif; ?></dl></div>
		</div>
	</div>
	<div class="gallery">
		<?php foreach ( fahrni_meta_ids( 'galerie' ) as $k => $id ) : ?>
			<button type="button" aria-label="Bild <?php echo $k + 1; ?> vergrössern"><img src="<?php echo esc_url( fahrni_img( $id, 'fahrni-wide' ) ); ?>" alt="<?php echo esc_attr( get_the_title() . ', Bild ' . ( $k + 1 ) ); ?>" loading="<?php echo $k ? 'lazy' : 'eager'; ?>"></button>
		<?php endforeach; ?>
	</div>
	<?php echo fahrni_pager( get_next_post(), get_previous_post() ); // neuere zuerst: «Vorherige» = neuer ?>
	<?php echo fahrni_lightbox();
endwhile;
get_footer();
