<?php
/**
 * Grundeinstellungen, Assets und Customizer.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'fahrni', FAHRNI_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script' ] );
	add_theme_support( 'responsive-embeds' );
	add_image_size( 'fahrni-tile', 900, 900, true );
	add_image_size( 'fahrni-wide', 1600, 0, false );
	register_nav_menus( [
		'haupt'  => __( 'Hauptnavigation', 'fahrni' ),
		'footer' => __( 'Fusszeile', 'fahrni' ),
	] );
} );

/* Weniger Ballast im Kopf der Seite (Emojis, oEmbed von Fremdseiten) */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'fahrni', FAHRNI_URI . '/assets/site.css', [], FAHRNI_VERSION );
	wp_enqueue_script( 'fahrni', FAHRNI_URI . '/assets/site.js', [], FAHRNI_VERSION, true );
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
} );

add_filter( 'body_class', function ( $classes ) {
	if ( fahrni_nav_variant() === 'burger' ) $classes[] = 'nav-burger';
	return $classes;
} );

/** Wert aus dem Customizer (Design → Anpassen → FAHRNI) mit Vorgabe. */
function fahrni_opt( string $key ) {
	$defaults = [
		'nav'           => 'kopfleiste',
		'telefon'       => '+41 32 387 25 25',
		'adresse'       => "Fahrni Fassadensysteme AG\nBernstrasse 84, CH-3250 Lyss",
		'email'         => '',
		'hero_titel_1'  => 'Ihre Vision,',
		'hero_titel_2'  => 'unsere Motivation.',
		'hero_text'     => 'Bauen Sie mit Fahrni! Aluminium-, Glas- und Elementfassaden, innovativ und energieeffizient, in der Schweiz und ausgewählten Ländern Europas.',
		'claim'         => 'Vom Einzelunternehmen zum führenden Fassadenbauer der Schweiz!',
		'footer_text'   => 'Praktisch schön und effizient, wir verhelfen Funktionalität und einzigartigem Design zum Leben.',
		'ticker'        => 'Aluminiumfassaden, Glasfassaden, Elementfassaden, Engineering, Testanlage, Produktion, Montage, Service & Unterhalt, Seit 1923, Lyss · Subingen · Dietikon · Reinach',
		'linkedin'      => '',
		'instagram'     => '',
		'youtube'       => '',
	];
	return get_theme_mod( 'fahrni_' . $key, $defaults[ $key ] ?? '' );
}

function fahrni_nav_variant(): string {
	return fahrni_opt( 'nav' ) === 'burger' ? 'burger' : 'kopfleiste';
}

function fahrni_tel_href(): string {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', fahrni_opt( 'telefon' ) );
}

add_action( 'customize_register', function ( WP_Customize_Manager $c ) {
	$c->add_section( 'fahrni', [ 'title' => 'FAHRNI', 'priority' => 30,
		'description' => 'Navigation, Kontaktangaben und Texte der Startseite.' ] );
	$fields = [
		'nav'          => [ 'Navigation', 'select', [ 'kopfleiste' => 'Kopfleiste', 'burger' => 'Burger-Menü (ohne Kopfleiste)' ] ],
		'telefon'      => [ 'Telefon Hauptsitz', 'text' ],
		'adresse'      => [ 'Adresse Hauptsitz (zwei Zeilen)', 'textarea' ],
		'email'        => [ 'E-Mail allgemein', 'email' ],
		'hero_titel_1' => [ 'Startseite: Titel Zeile 1', 'text' ],
		'hero_titel_2' => [ 'Startseite: Titel Zeile 2', 'text' ],
		'hero_text'    => [ 'Startseite: Text unter dem Titel', 'textarea' ],
		'claim'        => [ 'Startseite: Leitsatz (blaue Kachel)', 'text' ],
		'ticker'       => [ 'Laufband (durch Komma getrennt)', 'textarea' ],
		'footer_text'  => [ 'Fusszeile: Text unter dem Logo', 'textarea' ],
		'linkedin'     => [ 'LinkedIn-Adresse', 'url' ],
		'instagram'    => [ 'Instagram-Adresse', 'url' ],
		'youtube'      => [ 'YouTube-Adresse', 'url' ],
	];
	foreach ( $fields as $key => $f ) {
		$c->add_setting( 'fahrni_' . $key, [ 'default' => fahrni_opt( $key ),
			'sanitize_callback' => $f[1] === 'url' ? 'esc_url_raw' : ( $f[1] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field' ) ] );
		$args = [ 'label' => $f[0], 'section' => 'fahrni', 'type' => $f[1] ];
		if ( isset( $f[2] ) ) $args['choices'] = $f[2];
		$c->add_control( 'fahrni_' . $key, $args );
	}
} );
