<?php
/**
 * Formularfelder (Meta-Boxen) ohne zusätzliches Plugin.
 *
 * Feldarten: text, textarea, number, date, url, email, checkbox, select, file (eine Datei aus der Mediathek),
 * gallery (mehrere Bilder, sortierbar), posts (Auswahl anderer Inhalte als Kästchen).
 * Gespeichert wird jedes Feld als Post-Meta mit dem Schlüssel "fahrni_<name>".
 */
defined( 'ABSPATH' ) || exit;

function fahrni_field_groups(): array {
	return [
		'referenz' => [ 'Angaben zur Referenz', [
			'ort'                => [ 'Ort', 'text', 'z. B. Baden Ost' ],
			'bauherr'            => [ 'Bauherr', 'text' ],
			'architekt'          => [ 'Architekt', 'text' ],
			'generalunternehmer' => [ 'Generalunternehmer', 'text' ],
			'beteiligte'         => [ 'Weitere Beteiligte', 'textarea', 'Eine Zeile pro Eintrag: «Rolle: Name»' ],
			'galerie'            => [ 'Bildergalerie', 'gallery', 'Erstes Bild = grosses Bild. Reihenfolge per Ziehen ändern.' ],
		] ],
		'post' => [ 'Angaben zur News', [
			'ort' => [ 'Ort des Projekts', 'text', 'Erscheint auf der Kachel, z. B. Baden' ],
		] ],
		'stelle' => [ 'Angaben zur Stelle', [
			'ort'         => [ 'Arbeitsort', 'text', 'z. B. Lyss' ],
			'pensum'      => [ 'Pensum', 'text', 'z. B. 100 %' ],
			'ablaufdatum' => [ 'Ablaufdatum', 'date', 'Am Tag danach wird die Stelle automatisch archiviert (offline).' ],
			'kontakt'     => [ 'Kontakt für Bewerbungen', 'textarea', 'Name, Telefon, E-Mail' ],
		] ],
		'presse' => [ 'Pressebericht', [
			'pdf'       => [ 'PDF-Datei', 'file', 'Wird auf der Website im Rahmen angezeigt, mit Zurück-Button.' ],
			'jahr'      => [ 'Jahr', 'number', 'Erscheinungsjahr' ],
			'ungefaehr' => [ 'Jahr nur ungefähr bekannt', 'checkbox' ],
		] ],
		'social' => [ 'Social-Media-Beitrag', [
			'kanal'    => [ 'Kanal', 'select', '', [ 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'youtube' => 'YouTube' ] ],
			'video'    => [ 'Video (MP4, Hochformat 9:16)', 'file', 'Vorschaubild = Beitragsbild. Text = Inhalt oben.' ],
			'zeile'    => [ 'Kurzzeile über dem Titel', 'text', 'z. B. Neuer Auftrag, Referenz, Jobs' ],
			'hashtags' => [ 'Hashtags', 'text', 'Durch Leerzeichen getrennt, ohne #' ],
			'original' => [ 'Link zum Originalbeitrag', 'url' ],
			'seite'    => [ 'Link «Mehr dazu»', 'url', 'Seite auf dieser Website, z. B. die Referenz' ],
		] ],
		'meilenstein' => [ 'Meilenstein', [
			'jahr'        => [ 'Jahr', 'number' ],
			'ereignisse'  => [ 'Ereignisse', 'textarea', 'Eine Zeile pro Ereignis, erscheint auf der Zeitachse' ],
			'leistungen'  => [ 'Passende Leistungen', 'posts', 'Text der Leistung erscheint in der Detailansicht', 'leistung' ],
			'standorte'   => [ 'Passende Standorte', 'posts', '', 'standort' ],
			'ref_suche'   => [ 'Referenzen nach Ort', 'text', 'Suchwörter mit | getrennt, z. B. London|Cambridge' ],
			'ref_neueste' => [ 'Neueste Referenzen zeigen', 'number', 'Anzahl, statt Suche nach Ort' ],
			'news'        => [ 'Neueste News zeigen', 'number', 'Anzahl' ],
		] ],
		'leistung' => [ 'Leistung', [
			'anker' => [ 'Sprungmarke', 'text', 'Kleinbuchstaben ohne Leerzeichen, z. B. testanlage' ],
		] ],
		'standort' => [ 'Standort', [
			'art'       => [ 'Bezeichnung', 'text', 'z. B. Hauptsitz und Werk 1' ],
			'adresse'   => [ 'Adresse', 'textarea', 'Strasse, PLZ Ort' ],
			'telefon'   => [ 'Telefon', 'text' ],
			'email'     => [ 'E-Mail', 'email' ],
			'hauptsitz' => [ 'Hauptsitz', 'checkbox' ],
		] ],
	];
}

/** Feldwert lesen. */
function fahrni_meta( string $name, $post = null ) {
	$post = get_post( $post );
	return $post ? get_post_meta( $post->ID, 'fahrni_' . $name, true ) : '';
}

/** Galerie-/Auswahlfeld als Liste von IDs. */
function fahrni_meta_ids( string $name, $post = null ): array {
	$v = fahrni_meta( $name, $post );
	return array_values( array_filter( array_map( 'intval', is_array( $v ) ? $v : explode( ',', (string) $v ) ) ) );
}

add_action( 'init', function () {
	foreach ( fahrni_field_groups() as $type => [ $title, $fields ] ) {
		foreach ( $fields as $name => $f ) {
			register_post_meta( $type, 'fahrni_' . $name, [
				'type' => in_array( $f[1], [ 'number' ], true ) ? 'integer' : ( $f[1] === 'checkbox' ? 'boolean' : 'string' ),
				'single' => true, 'show_in_rest' => true,
				'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
			] );
		}
	}
} );

add_action( 'add_meta_boxes', function () {
	foreach ( fahrni_field_groups() as $type => [ $title, $fields ] ) {
		add_meta_box( 'fahrni-' . $type, $title, 'fahrni_render_box', $type, 'normal', 'high', [ 'fields' => $fields ] );
	}
} );

function fahrni_render_box( WP_Post $post, array $box ) {
	wp_nonce_field( 'fahrni_save', 'fahrni_nonce' );
	echo '<div class="fahrni-fields">';
	foreach ( $box['args']['fields'] as $name => $f ) {
		[ $label, $kind ] = $f; $help = $f[2] ?? ''; $extra = $f[3] ?? null;
		$key = 'fahrni_' . $name; $val = get_post_meta( $post->ID, $key, true );
		echo '<div class="fahrni-field fahrni-' . esc_attr( $kind ) . '"><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label>';
		switch ( $kind ) {
			case 'textarea':
				echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" rows="4">' . esc_textarea( $val ) . '</textarea>'; break;
			case 'checkbox':
				echo '<input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1"' . checked( $val, true, false ) . '>'; break;
			case 'select':
				echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
				foreach ( $extra as $k => $v ) echo '<option value="' . esc_attr( $k ) . '"' . selected( $val, $k, false ) . '>' . esc_html( $v ) . '</option>';
				echo '</select>'; break;
			case 'file':
				$url = $val ? wp_get_attachment_url( (int) $val ) : '';
				echo '<div class="fahrni-file"><input type="hidden" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '">'
					. '<span class="fahrni-file-name">' . ( $url ? esc_html( wp_basename( $url ) ) : 'Keine Datei gewählt' ) . '</span> '
					. '<button type="button" class="button fahrni-pick-file">Datei wählen</button> <button type="button" class="button-link fahrni-clear">Entfernen</button></div>';
				break;
			case 'gallery':
				$ids = fahrni_meta_ids( $name, $post );
				echo '<div class="fahrni-gallery"><input type="hidden" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '"><ul class="fahrni-thumbs">';
				foreach ( $ids as $id ) echo '<li data-id="' . (int) $id . '">' . wp_get_attachment_image( $id, 'thumbnail' ) . '<button type="button" class="fahrni-remove" aria-label="Bild entfernen">×</button></li>';
				echo '</ul><button type="button" class="button fahrni-pick-gallery">Bilder hinzufügen</button></div>';
				break;
			case 'posts':
				$sel = fahrni_meta_ids( $name, $post );
				$items = get_posts( [ 'post_type' => $extra, 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'post_status' => 'publish' ] );
				echo '<input type="hidden" name="' . esc_attr( $key ) . '[]" value=""><div class="fahrni-checks">';
				foreach ( $items as $it ) echo '<label><input type="checkbox" name="' . esc_attr( $key ) . '[]" value="' . (int) $it->ID . '"' . checked( in_array( $it->ID, $sel, true ), true, false ) . '> ' . esc_html( get_the_title( $it ) ) . '</label>';
				echo '</div>'; break;
			default:
				$type = in_array( $kind, [ 'number', 'date', 'url', 'email' ], true ) ? $kind : 'text';
				echo '<input type="' . $type . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '">';
		}
		if ( $help ) echo '<p class="description">' . esc_html( $help ) . '</p>';
		echo '</div>';
	}
	echo '</div>';
}

add_action( 'save_post', function ( int $post_id, WP_Post $post ) {
	if ( ! isset( $_POST['fahrni_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['fahrni_nonce'] ), 'fahrni_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	$groups = fahrni_field_groups();
	if ( ! isset( $groups[ $post->post_type ] ) ) return;
	foreach ( $groups[ $post->post_type ][1] as $name => $f ) {
		$key = 'fahrni_' . $name; $kind = $f[1];
		$raw = $_POST[ $key ] ?? null; // phpcs:ignore -- wird unten je Feldart bereinigt
		switch ( $kind ) {
			case 'checkbox': $val = ! empty( $raw ); break;
			case 'number':   $val = ( $raw === '' || $raw === null ) ? '' : (int) $raw; break;
			case 'url':      $val = esc_url_raw( wp_unslash( (string) $raw ) ); break;
			case 'email':    $val = sanitize_email( wp_unslash( (string) $raw ) ); break;
			case 'textarea': $val = sanitize_textarea_field( wp_unslash( (string) $raw ) ); break;
			case 'posts':    $val = implode( ',', array_filter( array_map( 'intval', (array) $raw ) ) ); break;
			case 'gallery':
			case 'file':     $val = implode( ',', array_filter( array_map( 'intval', explode( ',', (string) $raw ) ) ) ); break;
			default:         $val = sanitize_text_field( wp_unslash( (string) $raw ) );
		}
		if ( $val === '' || $val === false ) delete_post_meta( $post_id, $key ); else update_post_meta( $post_id, $key, $val );
	}
}, 10, 2 );

/* Mediathek-Auswahl für Datei- und Galeriefelder */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_add_inline_style( 'common', '
		.fahrni-fields { display: grid; gap: 16px; }
		.fahrni-field label { display: block; margin-bottom: 4px; }
		.fahrni-field input[type=text], .fahrni-field input[type=url], .fahrni-field input[type=email], .fahrni-field input[type=date], .fahrni-field input[type=number], .fahrni-field textarea, .fahrni-field select { width: 100%; max-width: 640px; }
		.fahrni-field.fahrni-checkbox label { display: inline; margin-right: 8px; }
		.fahrni-thumbs { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 8px; }
		.fahrni-thumbs li { position: relative; width: 96px; height: 96px; margin: 0; cursor: move; border-radius: 6px; overflow: hidden; background: #f0f0f1; }
		.fahrni-thumbs li img { width: 100%; height: 100%; object-fit: cover; display: block; }
		.fahrni-remove { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border-radius: 50%; border: 0; background: rgba(0,0,0,.65); color: #fff; cursor: pointer; line-height: 1; }
		.fahrni-checks { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 4px 16px; max-width: 900px; }
	' );
	wp_add_inline_script( 'jquery-ui-sortable', '
	jQuery(function ($) {
		function sync($g) { $g.find("input[type=hidden]").val($g.find(".fahrni-thumbs li").map(function () { return $(this).data("id"); }).get().join(",")); }
		$(".fahrni-thumbs").sortable({ update: function () { sync($(this).closest(".fahrni-gallery")); } });
		$(document).on("click", ".fahrni-remove", function () { var $g = $(this).closest(".fahrni-gallery"); $(this).parent().remove(); sync($g); });
		$(document).on("click", ".fahrni-pick-gallery", function () {
			var $g = $(this).closest(".fahrni-gallery"), frame = wp.media({ title: "Bilder wählen", multiple: "add", library: { type: "image" }, button: { text: "Hinzufügen" } });
			frame.on("select", function () {
				frame.state().get("selection").each(function (a) {
					var t = (a.get("sizes") && a.get("sizes").thumbnail) ? a.get("sizes").thumbnail.url : a.get("url");
					$g.find(".fahrni-thumbs").append($("<li>").attr("data-id", a.id).append($("<img>").attr("src", t)).append("<button type=\"button\" class=\"fahrni-remove\" aria-label=\"Bild entfernen\">×</button>"));
				});
				sync($g);
			});
			frame.open();
		});
		$(document).on("click", ".fahrni-pick-file", function () {
			var $f = $(this).closest(".fahrni-file"), frame = wp.media({ title: "Datei wählen", multiple: false, button: { text: "Übernehmen" } });
			frame.on("select", function () { var a = frame.state().get("selection").first(); $f.find("input").val(a.id); $f.find(".fahrni-file-name").text(a.get("filename")); });
			frame.open();
		});
		$(document).on("click", ".fahrni-clear", function () { var $f = $(this).closest(".fahrni-file"); $f.find("input").val(""); $f.find(".fahrni-file-name").text("Keine Datei gewählt"); });
	});' );
} );

/* Admin-Listen: nützliche Spalten */
add_filter( 'manage_stelle_posts_columns', function ( $c ) { return array_slice( $c, 0, 2 ) + [ 'ablauf' => 'Ablaufdatum' ] + $c; } );
add_action( 'manage_stelle_posts_custom_column', function ( $col, $id ) { if ( $col === 'ablauf' ) echo esc_html( fahrni_meta( 'ablaufdatum', $id ) ?: '–' ); }, 10, 2 );
add_filter( 'manage_referenz_posts_columns', function ( $c ) { return array_slice( $c, 0, 2 ) + [ 'ort' => 'Ort' ] + $c; } );
add_action( 'manage_referenz_posts_custom_column', function ( $col, $id ) { if ( $col === 'ort' ) echo esc_html( fahrni_meta( 'ort', $id ) ); }, 10, 2 );
add_filter( 'manage_meilenstein_posts_columns', function ( $c ) { return array_slice( $c, 0, 2 ) + [ 'jahr' => 'Jahr' ] + $c; } );
add_action( 'manage_meilenstein_posts_custom_column', function ( $col, $id ) { if ( $col === 'jahr' ) echo esc_html( fahrni_meta( 'jahr', $id ) ); }, 10, 2 );
