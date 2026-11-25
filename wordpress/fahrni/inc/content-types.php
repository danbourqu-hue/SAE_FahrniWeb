<?php
/**
 * Inhaltstypen, Taxonomie und der Status «Archiviert».
 *
 * News          = normale WordPress-Beiträge (im Admin «News»), Adresse /news/<name>/
 * referenz      = Referenzen, Adresse /referenzen/<name>/, gruppiert nach «Jahr» (2025, 2024, Früher)
 * stelle        = Stellen, Adresse /karriere/<name>/, mit Ablaufdatum
 * presse        = Presseberichte (PDF), Adresse /presse/<name>/ (PDF im Website-Rahmen)
 * social        = Social-Media-Beiträge (Video + Text), erscheinen im Slider der Seite «Social Media»
 * meilenstein   = Geschichte, erscheinen auf der Seite mit der Vorlage «Unternehmen»
 * leistung      = Leistungen, erscheinen auf der Seite mit der Vorlage «Leistungen» und auf der Startseite
 * standort      = Standorte mit Adresse und Telefon
 */
defined( 'ABSPATH' ) || exit;

function fahrni_labels( string $one, string $many, bool $female = true ): array {
	$new = $female ? 'Neue' : 'Neuer';
	return [
		'name' => $many, 'singular_name' => $one, 'menu_name' => $many, 'all_items' => "Alle $many",
		'add_new' => 'Hinzufügen', 'add_new_item' => "$new $one", 'edit_item' => "$one bearbeiten",
		'new_item' => "$new $one", 'view_item' => "$one ansehen", 'search_items' => "$many durchsuchen",
		'not_found' => "Keine $many gefunden", 'not_found_in_trash' => "Keine $many im Papierkorb",
	];
}

add_action( 'init', function () {
	add_post_type_support( 'page', 'excerpt' );
	$common = [ 'public' => true, 'show_in_rest' => true, 'has_archive' => true ];

	register_post_type( 'referenz', $common + [
		'labels' => fahrni_labels( 'Referenz', 'Referenzen' ),
		'menu_icon' => 'dashicons-building', 'menu_position' => 6,
		'rewrite' => [ 'slug' => 'referenzen', 'with_front' => false ],
		'supports' => [ 'title', 'editor', 'thumbnail', 'revisions' ],
	] );
	register_taxonomy( 'referenz_jahr', 'referenz', [
		'labels' => [ 'name' => 'Jahre', 'singular_name' => 'Jahr', 'menu_name' => 'Jahre' ],
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true,
		'rewrite' => [ 'slug' => 'referenzen-jahr', 'with_front' => false ],
	] );

	register_post_type( 'stelle', $common + [
		'labels' => fahrni_labels( 'Stelle', 'Stellen' ),
		'menu_icon' => 'dashicons-groups', 'menu_position' => 7,
		'rewrite' => [ 'slug' => 'karriere', 'with_front' => false ],
		'supports' => [ 'title', 'editor', 'revisions' ],
	] );

	register_post_type( 'presse', $common + [
		'labels' => fahrni_labels( 'Pressebericht', 'Presse', false ),
		'menu_icon' => 'dashicons-media-document', 'menu_position' => 8,
		'rewrite' => [ 'slug' => 'presse', 'with_front' => false ],
		'supports' => [ 'title', 'revisions' ],
	] );

	$intern = [ 'public' => false, 'show_ui' => true, 'show_in_rest' => true, 'has_archive' => false ];
	register_post_type( 'social', $intern + [
		'labels' => fahrni_labels( 'Social-Beitrag', 'Social Media', false ),
		'menu_icon' => 'dashicons-format-video', 'menu_position' => 9,
		'supports' => [ 'title', 'editor', 'thumbnail', 'page-attributes' ],
	] );
	register_post_type( 'meilenstein', $intern + [
		'labels' => fahrni_labels( 'Meilenstein', 'Geschichte', false ),
		'menu_icon' => 'dashicons-flag', 'menu_position' => 21,
		'supports' => [ 'title', 'editor', 'thumbnail' ],
	] );
	register_post_type( 'leistung', $intern + [
		'labels' => fahrni_labels( 'Leistung', 'Leistungen' ),
		'menu_icon' => 'dashicons-hammer', 'menu_position' => 22,
		'supports' => [ 'title', 'editor', 'thumbnail', 'page-attributes' ],
	] );
	register_post_type( 'standort', $intern + [
		'labels' => fahrni_labels( 'Standort', 'Standorte', false ),
		'menu_icon' => 'dashicons-location', 'menu_position' => 23,
		'supports' => [ 'title', 'page-attributes' ],
	] );

	/* Archiviert: nicht öffentlich, aber jederzeit wiederherstellbar */
	register_post_status( 'archiviert', [
		'label' => 'Archiviert', 'public' => false, 'internal' => false, 'protected' => true,
		'show_in_admin_all_list' => false, 'show_in_admin_status_list' => true,
		'label_count' => _n_noop( 'Archiviert <span class="count">(%s)</span>', 'Archiviert <span class="count">(%s)</span>' ),
	] );
} );

/* Beiträge heissen im Admin «News» */
add_action( 'admin_menu', function () {
	global $menu, $submenu;
	foreach ( $menu as &$m ) if ( ( $m[2] ?? '' ) === 'edit.php' ) $m[0] = 'News';
	if ( isset( $submenu['edit.php'][5] ) ) $submenu['edit.php'][5][0] = 'Alle News';
} );
add_action( 'init', function () {
	global $wp_post_types;
	$l = &$wp_post_types['post']->labels;
	$l->name = 'News'; $l->singular_name = 'News'; $l->add_new_item = 'Neue News'; $l->edit_item = 'News bearbeiten';
	$l->menu_name = 'News'; $l->all_items = 'Alle News'; $l->name_admin_bar = 'News';
}, 20 );

/* «Archiviert» im Bearbeiten-Dialog wählbar machen (klassischer Editor und Schnellbearbeitung) */
add_action( 'admin_footer-post.php', 'fahrni_status_dropdown' );
add_action( 'admin_footer-post-new.php', 'fahrni_status_dropdown' );
add_action( 'admin_footer-edit.php', 'fahrni_status_dropdown' );
function fahrni_status_dropdown() {
	global $post;
	$sel = ( $post && $post->post_status === 'archiviert' ) ? 'true' : 'false'; ?>
	<script>
	(function () {
		document.querySelectorAll('select[name="post_status"], select[name="_status"]').forEach(function (s) {
			if (s.querySelector('option[value="archiviert"]')) return;
			var o = document.createElement('option'); o.value = 'archiviert'; o.textContent = 'Archiviert';
			s.appendChild(o); if (<?php echo $sel; ?> && s.name === 'post_status') { s.value = 'archiviert'; var d = document.getElementById('post-status-display'); if (d) d.textContent = 'Archiviert'; }
		});
	})();
	</script>
	<?php
}

/* Reihenfolge in Listen: Leistungen, Standorte, Social nach «Reihenfolge», Meilensteine nach Jahr */
add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	if ( $q->is_post_type_archive( 'referenz' ) ) { $q->set( 'posts_per_page', -1 ); $q->set( 'orderby', 'date' ); $q->set( 'order', 'DESC' ); }
	if ( $q->is_post_type_archive( [ 'stelle', 'presse' ] ) ) $q->set( 'posts_per_page', -1 );
	if ( $q->is_post_type_archive( 'presse' ) ) { $q->set( 'orderby', 'menu_order date' ); $q->set( 'order', 'ASC' ); }
	if ( $q->is_home() ) $q->set( 'posts_per_page', 24 );
} );
