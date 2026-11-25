<?php
/**
 * FAHRNI Theme: lädt die Bausteine aus inc/.
 *
 * setup.php          Grundeinstellungen, Assets, Customizer (Navigation, Kontakt, Startseite)
 * content-types.php  Inhaltstypen: Referenzen, Stellen, Presse, Social, Meilensteine, Leistungen, Standorte
 * fields.php         Formularfelder (Meta-Boxen) für diese Inhaltstypen, inkl. Bildergalerie
 * lifecycle.php      Archivieren abgelaufener Stellen und alter News, Weiterleitungen alter fahrni.com-Adressen
 * template-tags.php  Wiederverwendbare Bausteine der Seiten (Kacheln, Seitenkopf, Pfeile, Orientierung)
 * import.php         Einmaliger Import aller Inhalte aus dem Website-Entwurf
 */
defined( 'ABSPATH' ) || exit;

define( 'FAHRNI_VERSION', '0.1.0' );
define( 'FAHRNI_DIR', get_template_directory() );
define( 'FAHRNI_URI', get_template_directory_uri() );

foreach ( [ 'setup', 'content-types', 'fields', 'lifecycle', 'template-tags', 'import' ] as $fahrni_file ) {
	require_once FAHRNI_DIR . "/inc/{$fahrni_file}.php";
}
