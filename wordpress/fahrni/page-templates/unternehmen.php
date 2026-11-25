<?php
/**
 * Template Name: Unternehmen
 * Oben: Prinzipien und Presse. Darunter die Zeitachse aus «Geschichte» mit Detailansicht je Meilenstein.
 * Detailansicht: Thema (Titel), Ereignisse, eigener Text, Bild, verknüpfte Leistungen und Standorte,
 * Referenzen nach Ort oder die neuesten, News, Presseberichte aus dem Zeitraum bis zum nächsten Meilenstein.
 */
get_header();
the_post();
$ms = get_posts( [ 'post_type' => 'meilenstein', 'numberposts' => -1, 'meta_key' => 'fahrni_jahr', 'orderby' => 'meta_value_num', 'order' => 'ASC' ] );
$years = array_map( fn( $m ) => (int) fahrni_meta( 'jahr', $m ), $ms );
$prinz = get_page_by_path( 'prinzipien' );
$presse_count = (int) wp_count_posts( 'presse' )->publish;
fahrni_head( get_the_title(), has_excerpt() ? get_the_excerpt() : fahrni_opt( 'claim' ), [ [ get_the_title(), '' ] ] ); ?>
<section><div class="grid">
	<?php if ( $prinz ) : $paras = preg_split( '#</p>\s*<p>|\n\s*\n#', $prinz->post_content ); $p1 = wp_trim_words( wp_strip_all_tags( $paras[0] ?? '' ), 22 ); ?>
	<a class="tile t-about span-7 tall reveal" href="<?php echo esc_url( get_permalink( $prinz ) ); ?>"><p class="eyebrow"><b>Prinzipien</b></p><h2 style="font-size:clamp(30px,3.2vw,48px);max-width:22ch"><?php echo esc_html( $p1 ); ?></h2><span class="link-line">Unsere Prinzipien <?php echo fahrni_ic(); ?></span></a>
	<?php endif; ?>
	<a class="tile tile-dark span-5 tall reveal" style="--d:.08s" href="<?php echo esc_url( fahrni_url( 'presse' ) ); ?>"><div class="tile-top"><span class="eyebrow"><b>Presse</b></span><?php echo fahrni_arrow(); ?></div><div><h3><?php echo (int) $presse_count; ?> Fachberichte</h3><p>Projekte von Fahrni in der Fachpresse.</p></div></a>
</div></section>
<?php if ( $ms ) : $span = end( $years ) - $years[0]; ?>
<section class="tl-section"><div class="tl" id="tl" aria-labelledby="g-title"><div class="tl-sticky">
	<div class="tl-top wrap"><div class="section-head" style="margin:0"><h2 id="g-title">Geschichte. <span><?php echo (int) $years[0]; ?> bis heute.</span></h2>
		<p><?php echo count( $ms ); ?> Meilensteine aus <?php echo (int) $span; ?> Jahren Fassadenbau. Scrollen Sie, die Zeitachse läuft mit.</p></div>
		<div class="tl-nav"><button type="button" class="tl-arrow" id="tl-prev" aria-label="Vorheriger Meilenstein"><?php echo fahrni_ic( 'back' ); ?></button>
			<div class="tl-progress" aria-hidden="true"><span id="tl-year"><?php echo (int) $years[0]; ?></span><i><b></b></i><span><?php echo (int) end( $years ); ?></span></div>
			<button type="button" class="tl-arrow tl-arrow-next" id="tl-next" aria-label="Nächster Meilenstein"><?php echo fahrni_ic(); ?></button></div>
		<p class="tl-hint" id="tl-hint" aria-hidden="true"><span class="tl-mouse"><i></i></span>Scrollen oder Pfeile nutzen <span class="tl-hint-arrow">→</span></p></div>
	<div class="tl-viewport"><div class="tl-track"><div class="tl-axis" aria-hidden="true"><div class="tl-fill"></div><div class="tl-head"></div></div><ol class="tl-list">
	<?php foreach ( $ms as $k => $m ) : $y = $years[ $k ]; $last = $k === count( $ms ) - 1; ?>
		<li class="tl-item<?php echo $last ? ' tl-now' : ''; ?>" data-year="<?php echo (int) $y; ?>"><span class="tl-dot" aria-hidden="true"></span><span class="tl-link" aria-hidden="true"></span>
			<article class="tile tl-card<?php echo $last ? ' tile-blue' : ( $y < 2000 ? ' tile-grey' : '' ); ?>" data-light><span class="yr"><?php echo (int) $y; ?></span>
				<div><?php foreach ( fahrni_lines( (string) fahrni_meta( 'ereignisse', $m ) ) as $line ) echo '<p>' . esc_html( $line ) . '</p>'; ?></div>
				<button type="button" class="ms-open" data-ms-go="ms-<?php echo (int) $y; ?>">Mehr erfahren <span aria-hidden="true">+</span></button></article></li>
	<?php endforeach; ?>
	</ol></div></div></div></div></section>
	<?php
	/* Detailansichten */
	$presse = get_posts( [ 'post_type' => 'presse', 'numberposts' => -1 ] );
	foreach ( $ms as $k => $m ) :
		$y = $years[ $k ]; $next = $years[ $k + 1 ] ?? 9999;
		$img = fahrni_thumb( $m, 'fahrni-wide' ); $cap = $img ? get_the_post_thumbnail_caption( $m ) : '';
		$refs = [];
		if ( $q = trim( (string) fahrni_meta( 'ref_suche', $m ) ) ) {
			foreach ( get_posts( [ 'post_type' => 'referenz', 'numberposts' => -1 ] ) as $r ) {
				if ( preg_match( '/' . str_replace( '/', '\/', $q ) . '/u', get_the_title( $r ) . ' ' . fahrni_meta( 'ort', $r ) ) ) $refs[] = $r;
			}
		} elseif ( $nn = (int) fahrni_meta( 'ref_neueste', $m ) ) {
			$refs = get_posts( [ 'post_type' => 'referenz', 'numberposts' => $nn ] );
		}
		$pr = array_filter( $presse, function ( $p ) use ( $y, $next ) { $j = (int) fahrni_meta( 'jahr', $p ); return $j && $j >= $y && $j < $next; } );
		usort( $pr, fn( $a, $b ) => (int) fahrni_meta( 'jahr', $a ) <=> (int) fahrni_meta( 'jahr', $b ) ); ?>
	<dialog class="ms" id="ms-<?php echo (int) $y; ?>" aria-labelledby="ms-<?php echo (int) $y; ?>-t">
		<button type="button" class="ms-close" aria-label="Schliessen">×</button>
		<div class="ms-grid">
			<?php if ( $img ) : ?><figure class="ms-media"><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $cap ); ?>" loading="lazy"><?php echo $cap ? '<figcaption>' . esc_html( $cap ) . '</figcaption>' : ''; ?></figure><?php endif; ?>
			<div class="ms-body">
				<p class="eyebrow"><b>Meilenstein</b> · <?php echo esc_html( sprintf( '%02d / %02d', $k + 1, count( $ms ) ) ); ?></p>
				<div class="ms-year"><?php echo (int) $y; ?></div>
				<h2 id="ms-<?php echo (int) $y; ?>-t"><?php echo esc_html( get_the_title( $m ) ); ?></h2>
				<ul class="ms-facts"><?php foreach ( fahrni_lines( (string) fahrni_meta( 'ereignisse', $m ) ) as $line ) echo '<li>' . esc_html( $line ) . '</li>'; ?></ul>
				<?php if ( trim( $m->post_content ) ) : ?><section class="ms-sec"><div class="prose"><?php echo apply_filters( 'the_content', $m->post_content ); // phpcs:ignore ?></div></section><?php endif; ?>
				<?php foreach ( fahrni_meta_ids( 'leistungen', $m ) as $lid ) : $l = get_post( $lid ); if ( ! $l ) continue; ?>
					<section class="ms-sec"><h3>Aus «Leistungen»: <?php echo esc_html( get_the_title( $l ) ); ?></h3><div class="prose"><?php echo apply_filters( 'the_content', $l->post_content ); // phpcs:ignore ?></div>
						<a class="link-line" href="<?php echo esc_url( fahrni_url( 'leistungen' ) . '#' . ( fahrni_meta( 'anker', $l ) ?: $l->post_name ) ); ?>">Zur Leistung <?php echo esc_html( get_the_title( $l ) ); ?> <?php echo fahrni_ic(); ?></a></section>
				<?php endforeach; ?>
				<?php $st = array_filter( array_map( 'get_post', fahrni_meta_ids( 'standorte', $m ) ) ); if ( $st ) : ?>
					<section class="ms-sec"><h3><?php echo count( $st ) === 1 ? 'Standort' : 'Standorte heute'; ?></h3><div class="ms-sites">
					<?php foreach ( $st as $s ) : $tel = (string) fahrni_meta( 'telefon', $s ); ?>
						<div class="ms-site"><b><?php echo esc_html( (string) fahrni_meta( 'art', $s ) ); ?></b><br><?php echo nl2br( esc_html( (string) fahrni_meta( 'adresse', $s ) ) ); ?><?php if ( $tel ) : ?><br><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a><?php endif; ?></div>
					<?php endforeach; ?></div></section>
				<?php endif; ?>
				<?php if ( $refs ) : ?>
					<section class="ms-sec"><h3><?php echo $q ? 'Referenzen (' . count( $refs ) . ')' : 'Neueste Referenzen'; ?></h3><div class="ms-refs">
					<?php foreach ( array_slice( $refs, 0, 6 ) as $r ) : ?><a class="ms-ref" href="<?php echo esc_url( get_permalink( $r ) ); ?>"><img src="<?php echo esc_url( fahrni_thumb( $r ) ); ?>" alt="" loading="lazy"><span><b><?php echo esc_html( get_the_title( $r ) ); ?></b><?php echo esc_html( (string) fahrni_meta( 'ort', $r ) ); ?></span></a><?php endforeach; ?>
					</div><a class="link-line" href="<?php echo esc_url( add_query_arg( $q ? [ 'q' => explode( '|', $q )[0] ] : [], fahrni_url( 'referenzen' ) ) ); ?>"><?php echo $q && count( $refs ) > 6 ? 'Alle ' . count( $refs ) . ' Referenzen mit «' . esc_html( explode( '|', $q )[0] ) . '»' : 'Alle Referenzen'; ?> <?php echo fahrni_ic(); ?></a></section>
				<?php endif; ?>
				<?php if ( $nn = (int) fahrni_meta( 'news', $m ) ) : ?>
					<section class="ms-sec"><h3>Neue Aufträge</h3><ul class="ms-list"><?php foreach ( get_posts( [ 'numberposts' => $nn ] ) as $n ) : ?><li><a href="<?php echo esc_url( get_permalink( $n ) ); ?>"><?php echo esc_html( get_the_title( $n ) ); ?></a></li><?php endforeach; ?></ul></section>
				<?php endif; ?>
				<?php if ( $pr ) : ?>
					<section class="ms-sec"><h3>Fachpresse <?php echo (int) $y; ?><?php echo $next === 9999 ? ' bis heute' : ( $next - 1 === $y ? '' : '–' . ( $next - 1 ) ); ?></h3><ul class="ms-list">
					<?php foreach ( $pr as $p ) : ?><li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a> <span><?php echo (int) fahrni_meta( 'jahr', $p ); ?> · PDF</span></li><?php endforeach; ?></ul></section>
				<?php endif; ?>
				<nav class="ms-nav" aria-label="Weitere Meilensteine">
					<?php echo $k ? '<button type="button" class="btn btn-ghost" data-ms-go="ms-' . (int) $years[ $k - 1 ] . '">← ' . (int) $years[ $k - 1 ] . '</button>' : '<span></span>'; ?>
					<?php echo isset( $years[ $k + 1 ] ) ? '<button type="button" class="btn btn-ghost" data-ms-go="ms-' . (int) $years[ $k + 1 ] . '">' . (int) $years[ $k + 1 ] . ' →</button>' : '<span></span>'; ?>
				</nav>
			</div>
		</div>
	</dialog>
	<?php endforeach;
endif;
get_footer();
