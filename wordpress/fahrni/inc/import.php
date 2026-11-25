<?php
/**
 * Einmaliger Import aller Inhalte aus dem Website-Entwurf.
 *
 * Erwartet ein Inhaltspaket (Ordner) mit:
 *   content/content.json, content/images.json, content/social.json, content/geschichte.json
 *   assets/fotos/…, assets/presse/…, assets/video/…
 * Das ist der Ordner «website» aus dem Entwurf bzw. das Paket «fahrni-inhalte.zip», entpackt nach
 * wp-content/uploads/fahrni-import/.
 *
 * Start: Werkzeuge → FAHRNI-Import (Administratoren). Mehrfach ausführbar: Vorhandenes wird aktualisiert, nicht doppelt angelegt.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_management_page( 'FAHRNI-Import', 'FAHRNI-Import', 'manage_options', 'fahrni-import', 'fahrni_import_page' );
} );

function fahrni_import_default_dir(): string {
	return trailingslashit( wp_upload_dir()['basedir'] ) . 'fahrni-import';
}

function fahrni_import_page() {
	$dir = fahrni_import_default_dir();
	echo '<div class="wrap"><h1>FAHRNI-Import</h1>';
	echo '<p>Übernimmt alle Inhalte des Website-Entwurfs: Standorte, Leistungen, 115 Referenzen mit Bildern, News, Stellen, Presseberichte (PDF), Social-Beiträge (Video), Geschichte und die Seiten.</p>';
	echo '<p>Paket erwartet in: <code>' . esc_html( $dir ) . '</code> (Ordner mit <code>content/</code> und <code>assets/</code>).</p>';
	if ( isset( $_POST['fahrni_import'] ) && check_admin_referer( 'fahrni_import' ) ) {
		@set_time_limit( 0 ); // phpcs:ignore
		echo '<h2>Protokoll</h2><pre style="background:#fff;padding:12px;max-height:480px;overflow:auto">';
		try { fahrni_import( $dir, function ( $m ) { echo esc_html( $m ) . "\n"; @ob_flush(); flush(); } ); } // phpcs:ignore
		catch ( Throwable $e ) { echo 'Fehler: ' . esc_html( $e->getMessage() ); }
		echo '</pre>';
	}
	echo '<form method="post">';
	wp_nonce_field( 'fahrni_import' );
	echo '<p><button class="button button-primary" name="fahrni_import" value="1"' . ( is_dir( $dir . '/content' ) ? '' : ' disabled' ) . '>Import starten</button></p></form></div>';
}

/** Hauptfunktion. $log erhält Textzeilen. */
function fahrni_import( string $dir, callable $log ): void {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$dir = untrailingslashit( $dir );
	$read = function ( $f ) use ( $dir ) { $p = "$dir/content/$f"; if ( ! is_file( $p ) ) throw new RuntimeException( "$p fehlt" ); return json_decode( file_get_contents( $p ), true ); };
	$C = $read( 'content.json' ); $IMG = $read( 'images.json' ); $S = $read( 'social.json' ); $G = $read( 'geschichte.json' );
	$asset = function ( ?string $rel ) use ( $dir ) { return $rel ? "$dir/assets/$rel" : ''; };
	$img = function ( ?string $orig, int $parent = 0, string $title = '' ) use ( $IMG, $asset ) {
		if ( ! $orig || empty( $IMG[ $orig ] ) ) return 0;
		return fahrni_import_file( $asset( $IMG[ $orig ] ), $parent, $title );
	};
	$links = function ( string $html ): string {
		return preg_replace_callback( '#href="(?:https?://www\.fahrni\.com)?(/de/[^"]*)"#', function ( $m ) {
			$t = fahrni_redirect_target( trim( $m[1], '/' ) );
			return 'href="' . esc_url( $t ?: home_url( '/' ) ) . '"';
		}, $html );
	};
	update_option( 'permalink_structure', '/news/%postname%/' );

	/* 1. Standorte */
	$standorte = [];
	foreach ( $C['kontakt']['locations'] as $i => $l ) {
		$city = trim( preg_replace( '/^(CH-)?\d{4}\s*/', '', end( $l['address'] ) ) );
		$id = fahrni_import_post( 'standort', 'standort-' . $i, [ 'post_title' => $city, 'menu_order' => $i ], [
			'art' => $l['name'], 'adresse' => implode( "\n", $l['address'] ), 'telefon' => $l['tel'], 'hauptsitz' => $i === 0 ] );
		$standorte[ $l['name'] ] = $id;
	}
	$log( count( $standorte ) . ' Standorte' );

	/* 2. Leistungen */
	$leist = [];
	foreach ( $C['leistungen']['sections'] as $i => $s ) {
		$id = fahrni_import_post( 'leistung', 'leistung-' . $s['id'], [ 'post_title' => $s['title'], 'post_content' => $links( $s['html'] ), 'menu_order' => $i, 'post_name' => $s['id'] ], [ 'anker' => $s['id'] ] );
		if ( $a = $img( $s['img'], $id, $s['title'] ) ) set_post_thumbnail( $id, $a );
		$leist[ $s['id'] ] = $id;
	}
	$log( count( $leist ) . ' Leistungen' );

	/* 3. Referenzen (neueste zuerst; Datum so gesetzt, dass die Reihenfolge stimmt) */
	$n = 0; $base = strtotime( '2026-11-01' );
	foreach ( $C['referenzen']['items'] as $i => $r ) {
		$meta = [ 'ort' => $r['place'] ]; $other = [];
		foreach ( $r['facts'] as [ $k, $v ] ) {
			$key = [ 'Bauherr' => 'bauherr', 'Architekt' => 'architekt', 'Generalunternehmer' => 'generalunternehmer' ][ $k ] ?? null;
			if ( $key && empty( $meta[ $key ] ) ) $meta[ $key ] = $v; else $other[] = "$k: $v";
		}
		$meta['beteiligte'] = implode( "\n", $other );
		$id = fahrni_import_post( 'referenz', 'ref-' . $r['slug'], [ 'post_title' => $r['name'], 'post_content' => $links( $r['html'] ),
			'post_name' => preg_replace( '/^\d{4}-\d{2}-\d{2}-/', '', $r['slug'] ), 'post_date' => gmdate( 'Y-m-d H:i:s', $base - $i * 3600 ) ], $meta );
		wp_set_object_terms( $id, [ $r['group'] ], 'referenz_jahr' );
		$gal = [];
		foreach ( $r['gallery'] as $k => $g ) if ( $a = $img( $g, $id, $r['name'] . ', Bild ' . ( $k + 1 ) ) ) $gal[] = $a;
		update_post_meta( $id, 'fahrni_galerie', implode( ',', $gal ) );
		if ( $t = $img( $r['thumb'], $id, $r['name'] ) ) set_post_thumbnail( $id, $t );
		if ( ++$n % 10 === 0 ) $log( "$n Referenzen …" );
	}
	$log( "$n Referenzen" );

	/* 4. News (Beiträge) */
	foreach ( $C['news'] as $i => $nw ) {
		$ort = str_contains( $nw['title'], ',' ) ? trim( substr( $nw['title'], strrpos( $nw['title'], ',' ) + 1 ) ) : '';
		$id = fahrni_import_post( 'post', 'news-' . $nw['slug'], [ 'post_title' => $nw['title'], 'post_excerpt' => $nw['abstract'] ?: $nw['teaser'],
			'post_content' => $links( $nw['html'] ), 'post_name' => preg_replace( '/^\d+-/', '', $nw['slug'] ), 'post_date' => gmdate( 'Y-m-d H:i:s', $base - $i * 86400 ) ], [ 'ort' => $ort ] );
		if ( ! empty( $nw['imgs'][0] ) && ( $a = $img( $nw['imgs'][0], $id, $nw['title'] ) ) ) set_post_thumbnail( $id, $a );
	}
	$log( count( $C['news'] ) . ' News' );

	/* 5. Stellen */
	foreach ( $C['jobs'] as $i => $j ) {
		fahrni_import_post( 'stelle', 'job-' . $j['slug'], [ 'post_title' => $j['title'], 'post_content' => $links( $j['html'] ),
			'post_name' => preg_replace( '/^\d+-/', '', $j['slug'] ), 'post_date' => gmdate( 'Y-m-d H:i:s', $base - $i * 60 ) ],
			[ 'ort' => str_contains( $j['title'], 'Lyss' ) ? 'Lyss' : '' ] );
	}
	$log( count( $C['jobs'] ) . ' Stellen' );

	/* 6. Presse */
	foreach ( $C['presse'] as $i => $p ) {
		$exact = (bool) preg_match( '/nr|ausgabe/i', $p['pdf'] );
		$id = fahrni_import_post( 'presse', 'presse-' . $p['file'], [ 'post_title' => $p['title'], 'menu_order' => $i ], [ 'jahr' => (int) $p['year'], 'ungefaehr' => ! $exact ] );
		if ( $a = fahrni_import_file( $asset( $p['file'] ), $id, $p['title'] ) ) update_post_meta( $id, 'fahrni_pdf', (string) $a );
	}
	$log( count( $C['presse'] ) . ' Presseberichte' );

	/* 7. Social */
	$page_link = function ( string $rel ): string {
		$rel = preg_replace( '/\.html(#.*)?$/', '$1', $rel );
		if ( preg_match( '#^(referenzen|news|jobs)/(.+)$#', $rel, $m ) ) {
			$type = [ 'referenzen' => 'referenz', 'news' => 'post', 'jobs' => 'stelle' ][ $m[1] ];
			$slug = $m[1] === 'referenzen' ? preg_replace( '/^\d{4}-\d{2}-\d{2}-/', '', $m[2] ) : preg_replace( '/^\d+-/', '', $m[2] );
			$p = get_page_by_path( $slug, OBJECT, $type );
			return $p ? get_permalink( $p ) : home_url( '/' );
		}
		[ $page, $hash ] = array_pad( explode( '#', $rel, 2 ), 2, '' );
		return fahrni_url( $page === 'jobs' ? 'karriere' : $page ) . ( $hash ? "#$hash" : '' );
	};
	foreach ( $S['posts'] as $i => $p ) {
		$id = fahrni_import_post( 'social', 'social-' . $p['id'], [ 'post_title' => $p['title'], 'post_content' => $p['text'], 'post_name' => $p['id'], 'menu_order' => $i ], [
			'kanal' => $p['channel'], 'zeile' => $p['date'], 'hashtags' => implode( ' ', $p['tags'] ), 'original' => $p['url'] ] );
		$vid = ! empty( $p['video'] ) ? $asset( $p['video'] ) : $asset( 'video/social/' . $p['id'] . '.mp4' );
		$pos = ! empty( $p['poster'] ) ? $asset( $p['poster'] ) : $asset( 'video/social/' . $p['id'] . '.jpg' );
		if ( $a = fahrni_import_file( $vid, $id, $p['title'] ) ) update_post_meta( $id, 'fahrni_video', (string) $a );
		if ( $a = fahrni_import_file( $pos, $id, $p['title'] ) ) set_post_thumbnail( $id, $a );
		$social_pages[ $id ] = $p['page'];
	}
	$log( count( $S['posts'] ) . ' Social-Beiträge' );

	/* 8. Seiten */
	$intro = $C['home']['intro'];
	$pages = [
		'startseite'  => [ 'Startseite', implode( "\n\n", $intro ), '', '' ],
		'leistungen'  => [ 'Leistungen', '', $C['leistungen']['lead'], 'page-templates/leistungen.php' ],
		'unternehmen' => [ 'Unternehmen', '', $C['unternehmen']['title'], 'page-templates/unternehmen.php' ],
		'prinzipien'  => [ 'Prinzipien', '<p>' . implode( '</p><p>', array_map( 'esc_html', $C['prinzipien']['paras'] ) ) . '</p>', '', '' ],
		'news'        => [ 'News', '', '', '' ],
		'kontakt'     => [ 'Kontakt', '', $C['kontakt']['lead'], 'page-templates/kontakt.php' ],
		'social'      => [ 'Social Media', '', '', 'page-templates/social.php' ],
		'datenschutz' => [ 'Datenschutz', $links( $C['dsgvo']['html'] ), 'Datenschutzerklärung', '' ],
		'beschaffungsbedingungen' => [ 'Allgemeine Beschaffungsbedingungen', $links( $C['abb']['html'] ), $C['abb']['lead'], '' ],
	];
	$page_ids = [];
	foreach ( $pages as $slug => [ $title, $content, $excerpt, $tpl ] ) {
		$excerpt = preg_replace( '/^plugin-text-header-slave">\s*/', '', (string) $excerpt );
		$page_ids[ $slug ] = fahrni_import_post( 'page', 'page-' . $slug, [ 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'post_excerpt' => $excerpt ], [] );
		if ( $tpl ) update_post_meta( $page_ids[ $slug ], '_wp_page_template', $tpl );
	}
	add_post_type_support( 'page', 'excerpt' );
	$t = []; foreach ( $C['home']['teasers'] as $x ) $t[ $x['href'] ] = $x['img'];
	if ( $a = $img( $C['referenzen']['items'][0]['gallery'][0] ?? null, $page_ids['startseite'] ) ) set_post_thumbnail( $page_ids['startseite'], $a );
	if ( $a = $img( $t['/de/kontakt/'] ?? null, $page_ids['kontakt'] ) ) set_post_thumbnail( $page_ids['kontakt'], $a );
	if ( $a = $img( $C['prinzipien']['imgs'][0] ?? null, $page_ids['prinzipien'] ) ) set_post_thumbnail( $page_ids['prinzipien'], $a );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $page_ids['startseite'] );
	update_option( 'page_for_posts', $page_ids['news'] );
	$log( count( $page_ids ) . ' Seiten' );

	/* Social: «Mehr dazu» erst jetzt verlinken (Seiten existieren) */
	foreach ( $social_pages ?? [] as $id => $rel ) update_post_meta( $id, 'fahrni_seite', esc_url_raw( $page_link( $rel ) ) );

	/* 9. Geschichte */
	$TL = $C['unternehmen']['timeline'];
	foreach ( $TL as $i => $y ) {
		$g = $G[ $y['year'] ] ?? [];
		$content = ''; $cap = '';
		if ( ! empty( $g['home'] ) ) $content .= '<h3>Aus «Startseite»</h3><p>' . implode( '</p><p>', array_map( 'esc_html', $intro ) ) . '</p>';
		if ( ! empty( $g['prinzipien'] ) ) $content .= '<h3>Aus «Prinzipien»</h3><p>' . implode( '</p><p>', array_map( fn( $k ) => esc_html( $C['prinzipien']['paras'][ $k ] ?? '' ), $g['prinzipien'] ) ) . '</p>';
		$id = fahrni_import_post( 'meilenstein', 'ms-' . $y['year'], [ 'post_title' => $g['thema'] ?? $y['text'][0], 'post_content' => $content ], [
			'jahr' => (int) $y['year'], 'ereignisse' => implode( "\n", $y['text'] ),
			'leistungen' => implode( ',', array_filter( array_map( fn( $k ) => $leist[ $k ] ?? 0, $g['leistungen'] ?? [] ) ) ),
			'standorte' => implode( ',', array_filter( array_map( fn( $k ) => $standorte[ $k ] ?? 0, $g['standorte'] ?? [] ) ) ),
			'ref_suche' => $g['referenzen'] ?? '', 'ref_neueste' => $g['neueste_referenzen'] ?? '', 'news' => $g['news'] ?? '' ] );
		$bild = $g['bild'] ?? '';
		$src = null;
		if ( isset( $leist[ $bild ] ) ) { $th = get_post_thumbnail_id( $leist[ $bild ] ); if ( $th ) set_post_thumbnail( $id, $th ); continue; }
		if ( $bild === 'unternehmen' ) $src = $t['/de/unternehmen/'] ?? null;
		if ( $bild === 'referenzen' ) $src = $t['/de/referenzen/'] ?? null;
		if ( $bild === 'kontakt' ) $src = $t['/de/kontakt/'] ?? null;
		if ( $bild === 'prinzipien-2' ) $src = $C['prinzipien']['imgs'][1] ?? null;
		if ( $bild === 'neueste' ) $src = $C['referenzen']['items'][0]['gallery'][0] ?? null;
		foreach ( [ 'london' => 'London|LONDON', 'basel' => 'Basel|BASEL', 'biel' => 'Biel|BIEL|Bienne' ] as $k => $pat ) {
			if ( $bild === $k ) foreach ( $C['referenzen']['items'] as $r ) if ( preg_match( "/$pat/", $r['place'] ) ) { $src = $r['gallery'][0] ?? $r['thumb']; $cap = $r['name'] . ', ' . $r['place']; break; }
		}
		if ( $src && ( $a = $img( $src, $id ) ) ) { set_post_thumbnail( $id, $a ); if ( ! empty( $cap ) ) wp_update_post( [ 'ID' => $a, 'post_excerpt' => $cap ] ); }
	}
	$log( count( $TL ) . ' Meilensteine' );

	/* 10. Bilder im grossen Menü */
	foreach ( [ 'leistungen' => '/de/leistungen/', 'referenzen' => '/de/referenzen/', 'unternehmen' => '/de/unternehmen/', 'news' => '/de/news/', 'karriere' => '/de/jobs/', 'kontakt' => '/de/kontakt/' ] as $k => $href ) {
		if ( $a = $img( $t[ $href ] ?? null ) ) set_theme_mod( 'fahrni_menubild_' . $k, $a );
	}
	if ( $first = get_posts( [ 'post_type' => 'social', 'numberposts' => 1, 'orderby' => 'menu_order', 'order' => 'ASC' ] ) ) set_theme_mod( 'fahrni_menubild_social', get_post_thumbnail_id( $first[0] ) );

	flush_rewrite_rules();
	$log( 'Fertig.' );
}

/** Beitrag anlegen oder aktualisieren (erkannt am Schlüssel _fahrni_src). */
function fahrni_import_post( string $type, string $src, array $data, array $meta ): int {
	$found = get_posts( [ 'post_type' => $type, 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_fahrni_src', 'meta_value' => $src, 'fields' => 'ids' ] );
	$data += [ 'post_type' => $type, 'post_status' => 'publish' ];
	if ( $found ) { $data['ID'] = $found[0]; $id = wp_update_post( wp_slash( $data ), true ); }
	else { $id = wp_insert_post( wp_slash( $data ), true ); }
	if ( is_wp_error( $id ) ) throw new RuntimeException( $id->get_error_message() );
	update_post_meta( $id, '_fahrni_src', $src );
	foreach ( $meta as $k => $v ) {
		if ( $v === '' || $v === null || $v === false ) delete_post_meta( $id, 'fahrni_' . $k ); else update_post_meta( $id, 'fahrni_' . $k, $v );
	}
	return (int) $id;
}

/** Datei in die Mediathek übernehmen (einmal je Quelldatei). */
function fahrni_import_file( string $path, int $parent = 0, string $title = '' ): int {
	if ( ! $path || ! is_file( $path ) ) return 0;
	$key = md5( $path );
	$found = get_posts( [ 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'meta_key' => '_fahrni_file', 'meta_value' => $key, 'fields' => 'ids' ] );
	if ( $found ) return (int) $found[0];
	$tmp = wp_tempnam( basename( $path ) );
	copy( $path, $tmp );
	$id = media_handle_sideload( [ 'name' => basename( $path ), 'tmp_name' => $tmp ], $parent, $title ?: null );
	if ( is_wp_error( $id ) ) { @unlink( $tmp ); return 0; } // phpcs:ignore
	update_post_meta( $id, '_fahrni_file', $key );
	if ( $title && wp_attachment_is_image( $id ) ) update_post_meta( $id, '_wp_attachment_image_alt', $title );
	return (int) $id;
}
