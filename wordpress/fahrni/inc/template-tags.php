<?php
/**
 * Bausteine der Seiten (aus dem Website-Entwurf übernommen).
 */
defined( 'ABSPATH' ) || exit;

function fahrni_ic( string $kind = 'arrow' ): string {
	return '<svg aria-hidden="true"><use href="#i-' . esc_attr( $kind ) . '"/></svg>';
}
function fahrni_arrow( string $kind = 'arrow' ): string {
	return '<span class="arrow" aria-hidden="true">' . fahrni_ic( $kind ) . '</span>';
}
function fahrni_icons(): void { ?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M4 12h15M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-out" viewBox="0 0 24 24"><path d="M7 17 17 7M8 7h9v9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-back" viewBox="0 0 24 24"><path d="M20 12H5M11 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-pause" viewBox="0 0 24 24"><path d="M8 5v14M16 5v14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></symbol>
  <symbol id="i-play" viewBox="0 0 24 24"><path d="M7 5v14l12-7z" fill="currentColor"/></symbol>
</svg>
<?php }

/** Bild-URL eines Anhangs oder Beitragsbilds. */
function fahrni_img( $id, string $size = 'large' ): string {
	return $id ? (string) wp_get_attachment_image_url( (int) $id, $size ) : '';
}
function fahrni_thumb( $post = null, string $size = 'fahrni-tile' ): string {
	$post = get_post( $post );
	$id = get_post_thumbnail_id( $post );
	if ( ! $id && $post && $post->post_type === 'referenz' ) $id = fahrni_meta_ids( 'galerie', $post )[0] ?? 0;
	return fahrni_img( $id, $size );
}

/** Adressen der Hauptseiten (Seiten mit Vorlage, Archive). */
function fahrni_url( string $key ): string {
	switch ( $key ) {
		case 'start':      return home_url( '/' );
		case 'referenzen': return (string) get_post_type_archive_link( 'referenz' );
		case 'karriere':   return (string) get_post_type_archive_link( 'stelle' );
		case 'presse':     return (string) get_post_type_archive_link( 'presse' );
		case 'news':       return get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/news/' );
	}
	$page = get_page_by_path( $key );
	return $page ? get_permalink( $page ) : home_url( '/' . $key . '/' );
}

/** Hauptnavigation: Menü «Hauptnavigation» aus dem Admin, sonst die Standardpunkte. */
function fahrni_nav_items(): array {
	$loc = get_nav_menu_locations();
	if ( ! empty( $loc['haupt'] ) ) {
		$items = wp_get_nav_menu_items( $loc['haupt'] ) ?: [];
		return array_map( fn( $i ) => [ 'url' => $i->url, 'label' => $i->title, 'key' => sanitize_title( $i->title ), 'parent' => (int) $i->menu_item_parent, 'id' => $i->ID ], $items );
	}
	$d = [ 'leistungen' => 'Leistungen', 'referenzen' => 'Referenzen', 'unternehmen' => 'Unternehmen', 'news' => 'News', 'karriere' => 'Karriere', 'social' => 'Social', 'kontakt' => 'Kontakt' ];
	$out = [];
	foreach ( $d as $k => $v ) $out[] = [ 'url' => fahrni_url( $k ), 'label' => $v, 'key' => $k, 'parent' => 0, 'id' => $k ];
	$out[] = [ 'url' => fahrni_url( 'prinzipien' ), 'label' => 'Prinzipien', 'key' => 'prinzipien', 'parent' => 'unternehmen', 'id' => 'p' ];
	$out[] = [ 'url' => fahrni_url( 'presse' ), 'label' => 'Presse', 'key' => 'presse', 'parent' => 'unternehmen', 'id' => 'pr' ];
	return $out;
}

/** Aktive Rubrik der aktuellen Seite (für die Markierung im Menü). */
function fahrni_active(): string {
	if ( is_post_type_archive( 'referenz' ) || is_singular( 'referenz' ) ) return 'referenzen';
	if ( is_post_type_archive( 'stelle' ) || is_singular( 'stelle' ) ) return 'karriere';
	if ( is_post_type_archive( 'presse' ) || is_singular( 'presse' ) ) return 'unternehmen';
	if ( is_home() || is_singular( 'post' ) ) return 'news';
	if ( is_page() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		return in_array( $slug, [ 'prinzipien', 'datenschutz', 'beschaffungsbedingungen' ], true ) ? 'unternehmen' : $slug;
	}
	return '';
}

/** Seitenkopf mit Pfad, grossem Titel und Lead. */
function fahrni_head( string $title, string $lead = '', array $crumbs = [] ): void {
	echo '<header class="page-head">';
	if ( $crumbs ) {
		$parts = [ '<a href="' . esc_url( home_url( '/' ) ) . '">Start</a>' ];
		foreach ( $crumbs as [ $t, $u ] ) $parts[] = $u ? '<a href="' . esc_url( $u ) . '">' . esc_html( $t ) . '</a>' : '<span>' . esc_html( $t ) . '</span>';
		echo '<nav class="crumbs fade-in" aria-label="Pfad">' . implode( '<span aria-hidden="true">/</span>', $parts ) . '</nav>';
	}
	echo '<h1><span class="rise"><span>' . esc_html( $title ) . '</span></span></h1>';
	if ( $lead ) echo '<p class="lead fade-in">' . esc_html( $lead ) . '</p>';
	echo '</header>';
}

function fahrni_ref_tile( WP_Post $r, string $extra = '' ): string {
	$terms = get_the_terms( $r, 'referenz_jahr' );
	$group = $terms ? $terms[0]->name : '';
	$ort = (string) fahrni_meta( 'ort', $r );
	$src = fahrni_thumb( $r );
	return '<a class="tile t-ref reveal" href="' . esc_url( get_permalink( $r ) ) . '" data-group="' . esc_attr( $group ) . '" data-search="' . esc_attr( mb_strtolower( get_the_title( $r ) . ' ' . $ort ) ) . '"' . $extra . '>'
		. ( $src ? '<div class="media"><img src="' . esc_url( $src ) . '" alt="" loading="lazy"></div>' : '' )
		. '<div class="tile-top">' . fahrni_arrow() . '</div><div><h3>' . esc_html( get_the_title( $r ) ) . '</h3><p class="place">' . esc_html( $ort ) . '</p></div></a>';
}

function fahrni_news_tile( WP_Post $n, string $cls = 'span-4', float $d = 0 ): string {
	$src = fahrni_thumb( $n, 'fahrni-tile' );
	$title = preg_replace( '/^Neuer Auftrag\s+/u', '', get_the_title( $n ) );
	return '<a class="tile t-news ' . esc_attr( $cls ) . ' reveal" style="--d:' . $d . 's" href="' . esc_url( get_permalink( $n ) ) . '">'
		. ( $src ? '<div class="media"><img src="' . esc_url( $src ) . '" alt="" loading="lazy"></div>' : '' )
		. '<div class="meta"><b>Neuer Auftrag</b><span>' . esc_html( (string) fahrni_meta( 'ort', $n ) ) . '</span></div>'
		. '<div class="tile-top" style="align-items:end"><div><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( get_the_excerpt( $n ) ) . '</p></div>' . fahrni_arrow() . '</div></a>';
}

function fahrni_standorte(): array {
	return get_posts( [ 'post_type' => 'standort', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
}
function fahrni_site_tiles(): string {
	$out = '';
	foreach ( fahrni_standorte() as $i => $s ) {
		$adr = array_filter( array_map( 'trim', explode( "\n", (string) fahrni_meta( 'adresse', $s ) ) ) );
		$tel = (string) fahrni_meta( 'telefon', $s );
		$main = fahrni_meta( 'hauptsitz', $s );
		$out .= '<article class="tile t-site reveal' . ( $main ? ' main' : ( $i % 2 ? ' tile-grey' : '' ) ) . '" style="--d:' . ( $i * .06 ) . 's" data-light>'
			. '<div class="tile-top"><span class="kind">' . esc_html( (string) fahrni_meta( 'art', $s ) ) . '</span><span class="pin"></span></div>'
			. '<div><h3>' . esc_html( get_the_title( $s ) ) . '</h3><address>' . implode( '<br>', array_map( 'esc_html', $adr ) ) . '</address>'
			. ( $tel ? '<p style="margin-top:10px"><a class="tel" href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ) . '">' . esc_html( $tel ) . '</a></p>' : '' )
			. '</div></article>';
	}
	return $out;
}

function fahrni_lightbox(): string {
	return '<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Bildansicht"><img alt=""><button class="lb-close" aria-label="Schliessen">×</button>'
		. '<button class="lb-prev" aria-label="Vorheriges Bild">‹</button><button class="lb-next" aria-label="Nächstes Bild">›</button><div class="lb-count"></div></div>';
}

/** Vorher/Nachher-Kacheln unter Detailseiten. */
function fahrni_pager( ?WP_Post $prev, ?WP_Post $next, string $l_prev = '← Vorherige', string $l_next = 'Nächste →' ): string {
	$h = '<nav class="pager" aria-label="Weitere">';
	$h .= $prev ? '<a class="tile tile-grey" href="' . esc_url( get_permalink( $prev ) ) . '"><span class="dir">' . esc_html( $l_prev ) . '</span><h3>' . esc_html( get_the_title( $prev ) ) . '</h3></a>' : '<span></span>';
	$h .= $next ? '<a class="tile tile-grey next" href="' . esc_url( get_permalink( $next ) ) . '"><span class="dir">' . esc_html( $l_next ) . '</span><h3>' . esc_html( get_the_title( $next ) ) . '</h3></a>' : '<span></span>';
	return $h . '</nav>';
}

/** Feste Wege zurück am Seitenende (nicht auf der Startseite). */
function fahrni_wayfinding(): void {
	if ( is_front_page() ) return;
	$active = fahrni_active();
	$count = wp_count_posts( 'referenz' )->publish ?? 0;
	$ways = [ [ 'start', '', 'Startseite', 'Zurück zum Überblick' ], [ 'karriere', 'karriere', 'Karriere', 'Offene Stellen und Lehrstellen' ],
		[ 'referenzen', 'referenzen', 'Referenzen', $count . ' Projekte' ], [ 'kontakt', 'kontakt', 'Kontakt', fahrni_opt( 'telefon' ) ] ];
	echo '<nav class="ways" aria-label="Weiter auf der Website"><h2 class="sr-only">Weiter auf der Website</h2>';
	foreach ( $ways as [ $u, $key, $lbl, $sub ] ) {
		if ( $key && $key === $active ) continue;
		echo '<a class="tile way' . ( $key ? '' : ' way-home' ) . '" href="' . esc_url( fahrni_url( $u ) ) . '"><div class="tile-top"><span class="eyebrow">' . esc_html( $sub ) . '</span>' . fahrni_arrow() . '</div><h3>' . esc_html( $lbl ) . '</h3></a>';
	}
	echo '</nav>';
}

/** Absätze aus einem Textfeld (eine Zeile = ein Absatz). */
function fahrni_lines( string $text ): array {
	return array_values( array_filter( array_map( 'trim', explode( "\n", $text ) ) ) );
}
