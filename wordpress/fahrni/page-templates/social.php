<?php
/**
 * Template Name: Social Media
 * Slider mit allen Social-Beiträgen (Video + animierter Text), Übersicht, Kanäle aus dem Customizer.
 */
get_header();
the_post();
$posts = get_posts( [ 'post_type' => 'social', 'numberposts' => -1, 'orderby' => 'menu_order date', 'order' => 'ASC' ] );
$n = count( $posts );
$names = [ 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'youtube' => 'YouTube' ];
fahrni_head( get_the_title(), has_excerpt() ? get_the_excerpt() : 'Neue Aufträge, Referenzen und Einblicke in Werkstatt und Montage, direkt aus unseren Kanälen.', [ [ get_the_title(), '' ] ] );
if ( $posts ) : ?>
<section class="sl" id="slider" aria-roledescription="Karussell" aria-label="Social-Media-Beiträge" tabindex="-1">
	<div class="sl-track" id="sl-track">
	<?php foreach ( $posts as $k => $p ) :
		$ch = $names[ fahrni_meta( 'kanal', $p ) ] ?? 'Social';
		$vid = wp_get_attachment_url( (int) fahrni_meta( 'video', $p ) );
		$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $p->post_content ) ) );
		$orig = (string) fahrni_meta( 'original', $p ); $more = (string) fahrni_meta( 'seite', $p ); ?>
		<article class="sl-slide" id="post-<?php echo esc_attr( $p->post_name ); ?>" role="group" aria-roledescription="Beitrag" aria-label="<?php echo esc_attr( ( $k + 1 ) . ' von ' . $n . ': ' . get_the_title( $p ) ); ?>" data-i="<?php echo (int) $k; ?>">
			<div class="sl-phone tile" data-light><video muted playsinline preload="metadata" poster="<?php echo esc_url( fahrni_thumb( $p ) ); ?>" aria-label="<?php echo esc_attr( 'Video zum Beitrag ' . get_the_title( $p ) ); ?>"><?php if ( $vid ) : ?><source src="<?php echo esc_url( $vid ); ?>" type="video/mp4"><?php endif; ?></video>
				<div class="sl-bar" aria-hidden="true"><b></b></div><span class="sl-chan"><?php echo esc_html( $ch ); ?></span></div>
			<div class="sl-copy"><p class="eyebrow"><b><?php echo esc_html( $ch ); ?></b><?php echo fahrni_meta( 'zeile', $p ) ? ' · ' . esc_html( (string) fahrni_meta( 'zeile', $p ) ) : ''; ?></p>
				<h2 class="sl-title"><?php echo esc_html( get_the_title( $p ) ); ?></h2>
				<p class="sl-text"><?php foreach ( $words as $i => $w ) echo '<span class="w" style="--i:' . (int) $i . '">' . esc_html( $w ) . '</span> '; ?></p>
				<ul class="sl-tags"><?php foreach ( array_filter( preg_split( '/[\s,#]+/', (string) fahrni_meta( 'hashtags', $p ) ) ) as $t ) echo '<li>#' . esc_html( $t ) . '</li>'; ?></ul>
				<div class="sl-actions">
					<?php if ( $more ) : ?><a class="btn btn-primary" href="<?php echo esc_url( $more ); ?>">Mehr dazu <?php echo fahrni_ic(); ?></a><?php endif; ?>
					<?php if ( $orig ) : ?><a class="btn btn-ghost" href="<?php echo esc_url( $orig ); ?>" target="_blank" rel="noopener">Auf <?php echo esc_html( $ch ); ?> ansehen <?php echo fahrni_ic( 'out' ); ?></a><?php endif; ?>
				</div></div>
		</article>
	<?php endforeach; ?>
	</div>
	<div class="sl-controls"><button class="sl-btn" type="button" id="sl-prev" aria-label="Vorheriger Beitrag"><?php echo fahrni_ic( 'back' ); ?></button>
		<button class="sl-btn" type="button" id="sl-play" aria-label="Automatisches Weiterschalten pausieren"><?php echo fahrni_ic( 'pause' ); ?></button>
		<div class="sl-dots"><?php foreach ( $posts as $k => $p ) : ?><button class="sl-dot" type="button" data-go="<?php echo (int) $k; ?>" aria-label="<?php echo esc_attr( 'Beitrag ' . ( $k + 1 ) . ': ' . get_the_title( $p ) ); ?>"><i></i></button><?php endforeach; ?></div>
		<span class="sl-count" id="sl-count" aria-live="polite">01 / <?php echo esc_html( sprintf( '%02d', $n ) ); ?></span>
		<button class="sl-btn" type="button" id="sl-next" aria-label="Nächster Beitrag"><?php echo fahrni_ic(); ?></button></div>
</section>
<section aria-labelledby="all-title"><div class="section-head reveal"><h2 id="all-title">Alle Beiträge.</h2></div><div class="sl-thumbs">
	<?php foreach ( $posts as $k => $p ) : ?><button class="sl-thumb tile" type="button" data-go="<?php echo (int) $k; ?>" aria-label="<?php echo esc_attr( 'Beitrag ' . ( $k + 1 ) . ' zeigen: ' . get_the_title( $p ) ); ?>"><div class="media"><img src="<?php echo esc_url( fahrni_thumb( $p ) ); ?>" alt="" loading="lazy"></div><span class="sl-thumb-ch"><?php echo esc_html( $names[ fahrni_meta( 'kanal', $p ) ] ?? '' ); ?></span><span class="sl-thumb-t"><?php echo esc_html( get_the_title( $p ) ); ?></span></button><?php endforeach; ?>
</div></section>
<?php endif; ?>
<section aria-labelledby="ch-title"><div class="section-head reveal"><h2 id="ch-title">Folgen Sie uns.</h2></div><div class="grid">
	<?php $k = 0; foreach ( $names as $key => $label ) : $url = fahrni_opt( $key ); ?>
		<<?php echo $url ? 'a href="' . esc_url( $url ) . '" target="_blank" rel="noopener"' : 'div data-light'; ?> class="tile tile-dark span-4 reveal" style="--d:<?php echo ( $k++ ) * .06; ?>s">
			<div class="tile-top"><span class="eyebrow"><b><?php echo esc_html( $label ); ?></b></span><?php echo $url ? fahrni_arrow( 'out' ) : ''; ?></div>
			<div><h3><?php echo esc_html( $label ); ?></h3><p><?php echo $url ? 'Folgen' : '<span class="sl-todo">Adresse unter Design → Anpassen → FAHRNI eintragen</span>'; ?></p></div>
		</<?php echo $url ? 'a' : 'div'; ?>>
	<?php endforeach; ?>
</div></section>
<?php get_footer();
