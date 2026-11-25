<?php
/**
 * Einzelne Stelle mit Bewerbungskontakt und Blättern.
 */
get_header();
while ( have_posts() ) : the_post();
	$kontakt = (string) fahrni_meta( 'kontakt' );
	fahrni_head( get_the_title(), '', [ [ 'Karriere', fahrni_url( 'karriere' ) ], [ 'Stelle', '' ] ] ); ?>
	<div class="detail">
		<div class="prose prose-card tile" data-light><?php the_content(); ?></div>
		<div class="facts">
			<div class="tile tile-blue" data-light><span class="eyebrow">Bewerbung</span><h3><?php bloginfo( 'name' ); ?></h3>
				<p><?php echo $kontakt ? nl2br( esc_html( $kontakt ) ) : nl2br( esc_html( fahrni_opt( 'adresse' ) ) ); ?><br><a href="<?php echo esc_url( fahrni_tel_href() ); ?>"><?php echo esc_html( fahrni_opt( 'telefon' ) ); ?></a></p></div>
			<a class="tile tile-dark" href="<?php echo esc_url( fahrni_url( 'karriere' ) ); ?>"><div class="tile-top"><span class="eyebrow">Weitere Stellen</span><?php echo fahrni_arrow(); ?></div><h3>Alle offenen Stellen</h3></a>
		</div>
	</div>
	<?php echo fahrni_pager( get_next_post(), get_previous_post(), '← Vorherige Stelle', 'Nächste Stelle →' );
endwhile;
get_footer();
