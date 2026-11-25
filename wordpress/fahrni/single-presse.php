<?php
/**
 * Pressebericht: PDF im Website-Rahmen, mit Zurück, Startseite, Karriere und Download.
 */
get_header();
while ( have_posts() ) : the_post();
	$pdf = wp_get_attachment_url( (int) fahrni_meta( 'pdf' ) );
	$jahr = fahrni_meta( 'jahr' );
	fahrni_head( get_the_title(), 'Fachbericht' . ( $jahr ? ' · ' . ( fahrni_meta( 'ungefaehr' ) ? 'ca. ' : '' ) . $jahr : '' ),
		[ [ 'Unternehmen', fahrni_url( 'unternehmen' ) ], [ 'Presse', fahrni_url( 'presse' ) ], [ get_the_title(), '' ] ] ); ?>
	<div class="pdf-bar">
		<a class="btn btn-primary" href="<?php echo esc_url( fahrni_url( 'presse' ) ); ?>" data-back>← Zurück zur Übersicht</a>
		<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Startseite</a>
		<a class="btn btn-ghost" href="<?php echo esc_url( fahrni_url( 'karriere' ) ); ?>">Karriere</a>
		<?php if ( $pdf ) : ?><a class="btn btn-ghost pdf-dl" href="<?php echo esc_url( $pdf ); ?>" download>PDF herunterladen</a><?php endif; ?>
	</div>
	<?php if ( $pdf ) : ?>
	<div class="pdf-frame"><iframe src="<?php echo esc_url( $pdf ); ?>#view=FitH" title="<?php the_title_attribute(); ?> (PDF)" loading="lazy"></iframe>
		<p class="pdf-fallback">Ihr Browser zeigt das PDF hier nicht an. <a href="<?php echo esc_url( $pdf ); ?>" download>PDF herunterladen</a></p></div>
	<?php else : ?><p class="empty">Zu diesem Bericht ist noch kein PDF hinterlegt.</p><?php endif; ?>
	<?php echo fahrni_pager( get_adjacent_post( false, '', true ), get_adjacent_post( false, '', false ), '← Vorheriger Bericht', 'Nächster Bericht →' );
endwhile;
get_footer();
