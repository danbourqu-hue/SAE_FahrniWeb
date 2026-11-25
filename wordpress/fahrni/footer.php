<?php
/**
 * Fuss jeder Seite: feste Wege, Fusszeile in Anthrazit mit Standorten.
 */
defined( 'ABSPATH' ) || exit;
fahrni_wayfinding();
$top = array_values( array_filter( fahrni_nav_items(), fn( $i ) => ! $i['parent'] ) );
?>
</main>
<footer class="footer"><div class="foot-band"><div class="wrap">
  <div class="foot-grid">
    <div class="foot-brand"><img src="<?php echo esc_url( FAHRNI_URI . '/assets/img/logo-footer.png' ); ?>" alt="FAHRNI façade systems" width="720" height="282">
      <p class="claim-text"><?php echo esc_html( fahrni_opt( 'footer_text' ) ); ?></p>
      <a class="foot-phone" href="<?php echo esc_url( fahrni_tel_href() ); ?>"><?php echo esc_html( fahrni_opt( 'telefon' ) ); ?></a></div>
    <div><h4>Seiten</h4><ul><?php foreach ( $top as $i ) : ?><li><a href="<?php echo esc_url( $i['url'] ); ?>"><?php echo esc_html( $i['label'] ); ?></a></li><?php endforeach; ?></ul></div>
    <div><h4>Unternehmen</h4><ul>
      <li><a href="<?php echo esc_url( fahrni_url( 'prinzipien' ) ); ?>">Prinzipien</a></li>
      <li><a href="<?php echo esc_url( fahrni_url( 'presse' ) ); ?>">Presse</a></li>
      <li><a href="<?php echo esc_url( fahrni_url( 'datenschutz' ) ); ?>">Datenschutz</a></li>
      <li><a href="<?php echo esc_url( fahrni_url( 'beschaffungsbedingungen' ) ); ?>">Allgemeine Beschaffungsbedingungen</a></li>
    </ul></div>
    <div><h4>Standorte</h4><ul class="foot-sites"><?php foreach ( fahrni_standorte() as $s ) :
      $tel = (string) fahrni_meta( 'telefon', $s ); ?>
      <li><b><?php echo esc_html( (string) fahrni_meta( 'art', $s ) ); ?></b><br><?php echo nl2br( esc_html( (string) fahrni_meta( 'adresse', $s ) ) ); ?>
        <?php if ( $tel ) : ?><br><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a><?php endif; ?></li>
    <?php endforeach; ?></ul></div>
  </div>
  <div class="foot-base"><span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Fahrni Fassadensysteme AG</span><span><a class="theme-switch" href="<?php echo esc_url( fahrni_url( 'datenschutz' ) ); ?>">Datenschutz</a></span></div>
</div></div></footer>
<?php wp_footer(); ?>
</body>
</html>
