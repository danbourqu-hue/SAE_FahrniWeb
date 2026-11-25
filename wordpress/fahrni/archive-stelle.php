<?php
/**
 * Karriere: offene Stellen.
 */
get_header();
fahrni_head( 'Karriere', 'Offene Stellen bei der Fahrni Fassadensysteme AG.', [ [ 'Karriere', '' ] ] );
$img = fahrni_menu_image( 'karriere' );
if ( $img ) : ?>
<div class="grid" style="margin-bottom:var(--gap)"><div class="tile span-12 x-tall reveal" data-light style="justify-content:flex-end"><div class="media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="pos-top"></div><h2 style="font-size:clamp(36px,5vw,72px)">Bauen Sie mit Fahrni.</h2></div></div>
<?php endif; ?>
<div class="grid">
<?php $k = 0; if ( have_posts() ) : while ( have_posts() ) : the_post(); $ort = fahrni_meta( 'ort' ); $pensum = fahrni_meta( 'pensum' ); ?>
	<a class="tile span-6 tall reveal" style="--d:<?php echo ( $k % 2 ) * .06; ?>s" href="<?php the_permalink(); ?>">
		<div class="tile-top"><span class="num">Stelle <?php echo esc_html( sprintf( '%02d', ++$k ) ); ?><?php echo $ort ? ' · ' . esc_html( $ort ) : ''; ?><?php echo $pensum ? ' · ' . esc_html( $pensum ) : ''; ?></span><?php echo fahrni_arrow(); ?></div>
		<h3 style="font-size:clamp(28px,3vw,44px)"><?php the_title(); ?></h3>
	</a>
<?php endwhile; else : ?>
	<div class="tile span-12" data-light><h3>Zurzeit sind keine Stellen offen.</h3><p>Spontanbewerbungen sind willkommen: <?php echo esc_html( fahrni_opt( 'telefon' ) ); ?></p></div>
<?php endif; ?>
</div>
<?php get_footer();
