<?php
/**
 * Kopf jeder Seite: Kopfleiste mit Menüband, Kontakt-Button und Burger-Button; grosses Menü mit Bildern.
 */
defined( 'ABSPATH' ) || exit;
$items  = fahrni_nav_items();
$top    = array_values( array_filter( $items, fn( $i ) => ! $i['parent'] ) );
$active = fahrni_active();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#f3f4f4">
<link rel="icon" href="<?php echo esc_url( FAHRNI_URI . '/assets/img/favicon.png' ); ?>">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="ambient" aria-hidden="true"></div>
<div class="cone" aria-hidden="true"><div class="cone__halo"></div><div class="cone__dot"></div></div>
<?php fahrni_icons(); ?>
<header class="header" id="header"><div class="bar">
  <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="FAHRNI façade systems, zur Startseite"><img src="<?php echo esc_url( FAHRNI_URI . '/assets/img/logo-blau.png' ); ?>" alt="FAHRNI façade systems" width="720" height="282"></a>
  <nav class="nav" aria-label="Hauptnavigation">
    <?php foreach ( $top as $i ) : if ( $i['key'] === 'kontakt' ) continue; ?>
      <a href="<?php echo esc_url( $i['url'] ); ?>"<?php echo $i['key'] === $active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $i['label'] ); ?></a>
    <?php endforeach; ?>
  </nav>
  <a class="btn btn-primary" href="<?php echo esc_url( fahrni_url( 'kontakt' ) ); ?>">Kontakt <?php echo fahrni_ic(); ?></a>
  <button class="mburger" id="burger" aria-expanded="false" aria-controls="menu" aria-label="Menü öffnen"><span class="mburger-label">Menü</span><span class="mburger-icon" aria-hidden="true"><i></i><i></i><i></i></span></button>
</div></header>
<div class="menu mmenu" id="menu">
  <div class="mm-inner">
    <nav class="mm-nav" aria-label="Hauptnavigation, ausführlich"><ol>
      <?php foreach ( $top as $k => $i ) :
        $subs = array_filter( $items, fn( $s ) => $s['parent'] && ( $s['parent'] === $i['id'] || $s['parent'] === $i['key'] ) ); ?>
        <li style="--k:<?php echo (int) $k; ?>"><a class="mm-link" href="<?php echo esc_url( $i['url'] ); ?>" data-pic="<?php echo (int) $k; ?>"<?php echo $i['key'] === $active ? ' aria-current="page"' : ''; ?>>
          <span class="mm-num"><?php echo esc_html( sprintf( '%02d', $k + 1 ) ); ?></span><span class="mm-label"><?php echo esc_html( $i['label'] ); ?></span><span class="mm-desc"><?php echo esc_html( fahrni_menu_text( $i['key'] ) ); ?></span></a>
          <?php if ( $subs ) : ?><span class="mm-sub"><?php foreach ( $subs as $s ) : ?><a href="<?php echo esc_url( $s['url'] ); ?>"><?php echo esc_html( $s['label'] ); ?></a><?php endforeach; ?></span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol></nav>
    <div class="mm-side">
      <div class="mm-visual" aria-hidden="true">
        <?php foreach ( $top as $k => $i ) : $src = fahrni_menu_image( $i['key'] ); if ( ! $src ) continue; ?>
          <img src="<?php echo esc_url( $src ); ?>" alt="" data-pic="<?php echo (int) $k; ?>"<?php echo $k === 0 ? ' class="is-on"' : ''; ?> loading="lazy">
        <?php endforeach; ?>
      </div>
      <div class="mm-meta">
        <div><span class="eyebrow">Hauptsitz</span><a class="mm-phone" href="<?php echo esc_url( fahrni_tel_href() ); ?>"><?php echo esc_html( fahrni_opt( 'telefon' ) ); ?></a><p><?php echo nl2br( esc_html( fahrni_opt( 'adresse' ) ) ); ?></p></div>
        <div><span class="eyebrow">Standorte</span><p><?php echo implode( '<br>', array_map( fn( $s ) => esc_html( fahrni_meta( 'art', $s ) . ', ' . get_the_title( $s ) ), fahrni_standorte() ) ); ?></p></div>
      </div>
    </div>
  </div>
</div>
<main class="wrap" id="top">
