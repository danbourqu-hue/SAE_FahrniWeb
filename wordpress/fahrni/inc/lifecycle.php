<?php
/**
 * Lebenszyklus der Inhalte und Weiterleitungen.
 *
 * - Stellen: Am Tag nach dem Ablaufdatum werden sie automatisch archiviert (offline, wiederherstellbar).
 * - News: Älter als die eingestellte Anzahl Jahre (Einstellungen → Lesen, Vorgabe 2) werden archiviert.
 * - Archivierte Inhalte und alte fahrni.com-Adressen leiten auf eine passende Seite weiter, statt «Seite nicht gefunden».
 */
defined( 'ABSPATH' ) || exit;

add_action( 'after_switch_theme', function () {
	if ( ! wp_next_scheduled( 'fahrni_daily' ) ) wp_schedule_event( strtotime( 'tomorrow 02:00' ), 'daily', 'fahrni_daily' );
	flush_rewrite_rules();
} );
add_action( 'switch_theme', function () { wp_clear_scheduled_hook( 'fahrni_daily' ); } );

add_action( 'fahrni_daily', 'fahrni_archive_expired' );
function fahrni_archive_expired(): int {
	$n = 0;
	$today = wp_date( 'Y-m-d' );
	foreach ( get_posts( [ 'post_type' => 'stelle', 'post_status' => 'publish', 'numberposts' => -1,
		'meta_query' => [ [ 'key' => 'fahrni_ablaufdatum', 'value' => $today, 'compare' => '<', 'type' => 'DATE' ] ] ] ) as $p ) {
		wp_update_post( [ 'ID' => $p->ID, 'post_status' => 'archiviert' ] ); $n++;
	}
	$years = (int) get_option( 'fahrni_news_jahre', 2 );
	if ( $years > 0 ) {
		foreach ( get_posts( [ 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1,
			'date_query' => [ [ 'before' => "-$years years" ] ] ] ) as $p ) {
			wp_update_post( [ 'ID' => $p->ID, 'post_status' => 'archiviert' ] ); $n++;
		}
	}
	return $n;
}

/* Einstellung: News nach wie vielen Jahren archivieren (Einstellungen → Lesen) */
add_action( 'admin_init', function () {
	register_setting( 'reading', 'fahrni_news_jahre', [ 'type' => 'integer', 'default' => 2, 'sanitize_callback' => 'absint' ] );
	add_settings_field( 'fahrni_news_jahre', 'News automatisch archivieren nach', function () {
		echo '<input type="number" min="0" name="fahrni_news_jahre" value="' . (int) get_option( 'fahrni_news_jahre', 2 ) . '" class="small-text"> Jahren <p class="description">0 = nie automatisch archivieren.</p>';
	}, 'reading' );
} );

/**
 * Weiterleitungen: alte fahrni.com-Adressen (/de/…, /fr/…) und archivierte Inhalte.
 * Nur wenn WordPress sonst «Seite nicht gefunden» zeigen würde.
 */
add_action( 'template_redirect', function () {
	if ( ! is_404() ) return;
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore
	$to = fahrni_redirect_target( $path );
	if ( $to ) { wp_safe_redirect( $to, 301 ); exit; }
} );

function fahrni_redirect_target( string $path ): ?string {
	$map = [
		'de' => '/', 'de/leistungen' => '/leistungen/', 'de/unternehmen' => '/unternehmen/', 'de/unternehmen/prinzipien' => '/prinzipien/',
		'de/unternehmen/presse' => '/presse/', 'de/unternehmen/dsgvo' => '/datenschutz/', 'de/unternehmen/allgemeine-beschaffungsbedingungen' => '/beschaffungsbedingungen/',
		'de/jobs' => '/karriere/', 'de/referenzen' => '/referenzen/', 'de/referenzen/archive' => '/referenzen/', 'de/news' => '/news/', 'de/kontakt' => '/kontakt/',
	];
	if ( isset( $map[ $path ] ) ) return home_url( $map[ $path ] );
	if ( preg_match( '#^de/(referenzen|news|jobs)/([^/]+)$#', $path, $m ) ) {
		$type = [ 'referenzen' => 'referenz', 'news' => 'post', 'jobs' => 'stelle' ][ $m[1] ];
		$slug = $m[1] === 'referenzen' ? preg_replace( '/^\d{4}-\d{2}-\d{2}-/', '', $m[2] ) : preg_replace( '/^\d+-/', '', $m[2] );
		$p = get_page_by_path( $slug, OBJECT, $type );
		if ( $p && $p->post_status === 'publish' ) return get_permalink( $p );
		return get_post_type_archive_link( $type ) ?: home_url( '/news/' );
	}
	if ( str_starts_with( $path, 'fr' ) ) return home_url( '/' );
	/* Archivierter Inhalt unter seiner neuen Adresse → Übersicht des Inhaltstyps */
	foreach ( [ 'referenzen' => 'referenz', 'karriere' => 'stelle', 'presse' => 'presse', 'news' => 'post' ] as $base => $type ) {
		if ( str_starts_with( $path, $base . '/' ) ) return $type === 'post' ? home_url( '/news/' ) : get_post_type_archive_link( $type );
	}
	return null;
}
