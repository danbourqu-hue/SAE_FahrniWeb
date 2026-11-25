<?php
/**
 * Startseite. Texte aus «Design → Anpassen → FAHRNI», Inhalt der Seite «Startseite» als Unternehmenstext,
 * Beitragsbild der Startseite als grosses Bild. Alles andere kommt automatisch aus den Inhaltstypen.
 */
get_header();
$front   = get_post( (int) get_option( 'page_on_front' ) );
$refs    = get_posts( [ 'post_type' => 'referenz', 'numberposts' => 6 ] );
$hero    = $front ? fahrni_thumb( $front, 'fahrni-wide' ) : '';
if ( ! $hero && $refs ) $hero = fahrni_thumb( $refs[0], 'fahrni-wide' );
$leist   = get_posts( [ 'post_type' => 'leistung', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
$news    = get_posts( [ 'post_type' => 'post', 'numberposts' => 3 ] );
$social  = get_posts( [ 'post_type' => 'social', 'numberposts' => 3, 'orderby' => 'menu_order date', 'order' => 'ASC' ] );
$jobs    = get_posts( [ 'post_type' => 'stelle', 'numberposts' => -1 ] );
$ms      = get_posts( [ 'post_type' => 'meilenstein', 'numberposts' => -1, 'meta_key' => 'fahrni_jahr', 'orderby' => 'meta_value_num', 'order' => 'ASC' ] );
$first   = $ms ? $ms[0] : null; $last = $ms ? end( $ms ) : null;
$intro   = $front ? wp_strip_all_tags( str_replace( '</p>', '</p> ', $front->post_content ) ) : '';
?>
<section aria-labelledby="hero-title">
  <div class="grid">
    <article class="tile t-hero" data-light>
      <?php if ( $hero ) : ?><div class="media"><img src="<?php echo esc_url( $hero ); ?>" alt="" fetchpriority="high"></div><?php endif; ?>
      <div class="hero-copy">
        <p class="eyebrow fade-in"><b><?php bloginfo( 'name' ); ?></b> · Lyss</p>
        <h1 id="hero-title"><span class="line"><span><?php echo esc_html( fahrni_opt( 'hero_titel_1' ) ); ?></span></span><span class="line"><span><?php echo esc_html( fahrni_opt( 'hero_titel_2' ) ); ?></span></span></h1>
        <p class="lead"><?php echo esc_html( fahrni_opt( 'hero_text' ) ); ?></p>
        <div class="actions"><a class="btn btn-primary" href="<?php echo esc_url( fahrni_url( 'referenzen' ) ); ?>">Referenzen ansehen <?php echo fahrni_ic(); ?></a><a class="btn btn-ghost" href="<?php echo esc_url( fahrni_url( 'leistungen' ) ); ?>">Leistungen</a></div>
      </div>
    </article>
    <a class="tile t-blue reveal" href="<?php echo esc_url( fahrni_url( 'unternehmen' ) ); ?>">
      <div class="tile-top"><img class="fa" src="<?php echo esc_url( FAHRNI_URI . '/assets/img/fa-icon.png' ); ?>" alt="" width="68" height="68"><?php echo fahrni_arrow(); ?></div>
      <div class="claim"><h2><?php echo esc_html( fahrni_opt( 'claim' ) ); ?></h2><span class="link-line">Unternehmen <?php echo fahrni_ic(); ?></span></div>
    </a>
    <a class="tile tile-dark t-contact reveal" href="<?php echo esc_url( fahrni_url( 'kontakt' ) ); ?>" style="--d:.08s">
      <div class="tile-top"><span class="eyebrow"><?php echo esc_html( fahrni_menu_text( 'kontakt' ) ); ?></span><?php echo fahrni_arrow(); ?></div>
      <div class="claim"><span class="status"><i></i>Hauptsitz Lyss</span><span class="phone"><?php echo esc_html( fahrni_opt( 'telefon' ) ); ?></span><p><?php echo esc_html( str_replace( "\n", ', ', explode( "\n", fahrni_opt( 'adresse' ) )[1] ?? '' ) ); ?></p></div>
    </a>
  </div>
</section>

<?php if ( $leist ) : ?>
<section aria-labelledby="l-title">
  <div class="section-head reveal"><h2 id="l-title">Leistungen. <span>Alles aus einer Hand.</span></h2><p><?php echo esc_html( fahrni_menu_text( 'leistungen' ) ); ?></p></div>
  <div class="grid">
    <?php foreach ( $leist as $i => $l ) : $src = fahrni_thumb( $l ); ?>
      <a class="tile span-3 reveal" style="--d:<?php echo ( $i % 4 ) * .05; ?>s" href="<?php echo esc_url( fahrni_url( 'leistungen' ) . '#' . ( fahrni_meta( 'anker', $l ) ?: $l->post_name ) ); ?>">
        <?php if ( $src ) : ?><div class="media"><img src="<?php echo esc_url( $src ); ?>" alt="" loading="lazy"></div><?php endif; ?>
        <div class="tile-top"><span class="num"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><?php echo fahrni_arrow(); ?></div><h3><?php echo esc_html( get_the_title( $l ) ); ?></h3>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ( $refs ) : ?>
<section aria-labelledby="r-title">
  <div class="section-head reveal"><h2 id="r-title">Referenzen. <span>Praktisch schön und effizient.</span></h2><a class="btn btn-ghost" href="<?php echo esc_url( fahrni_url( 'referenzen' ) ); ?>">Alle <?php echo (int) wp_count_posts( 'referenz' )->publish; ?> Referenzen <?php echo fahrni_ic(); ?></a></div>
  <div class="grid"><?php foreach ( $refs as $i => $r ) echo fahrni_ref_tile( $r, ' style="--d:' . ( $i % 3 ) * .06 . 's"' ); ?></div>
</section>
<?php endif; ?>

<section aria-labelledby="u-title">
  <div class="grid">
    <a class="tile tile-grey t-about span-7 x-tall reveal" href="<?php echo esc_url( fahrni_url( 'unternehmen' ) ); ?>">
      <p class="eyebrow"><b>Unternehmen</b><?php echo $first ? ' · seit ' . esc_html( (string) fahrni_meta( 'jahr', $first ) ) : ''; ?></p>
      <h2 id="u-title">Vom Einzel&shy;unternehmen zum <em>führenden</em> Fassadenbauer der Schweiz.</h2>
      <?php if ( $intro ) : ?><p style="max-width:60ch"><?php echo esc_html( wp_trim_words( $intro, 60 ) ); ?></p><?php endif; ?>
      <span class="link-line">Geschichte ansehen <?php echo fahrni_ic(); ?></span>
    </a>
    <?php if ( $last ) : ?>
    <a class="tile tile-blue span-5 x-tall reveal" style="--d:.08s" href="<?php echo esc_url( fahrni_url( 'unternehmen' ) ); ?>">
      <div class="tile-top"><span class="eyebrow"><?php echo esc_html( fahrni_lines( (string) fahrni_meta( 'ereignisse', $first ) )[0] ?? '' ); ?></span><?php echo fahrni_arrow(); ?></div>
      <div class="big-num"><?php echo esc_html( (string) fahrni_meta( 'jahr', $last ) ); ?><small><?php echo esc_html( fahrni_lines( (string) fahrni_meta( 'ereignisse', $last ) )[0] ?? '' ); ?></small></div>
    </a>
    <?php endif; ?>
  </div>
</section>

<section aria-labelledby="s-title">
  <div class="section-head reveal"><h2 id="s-title">Vier Standorte. <span>Kurze Wege.</span></h2></div>
  <div class="grid"><?php echo fahrni_site_tiles(); ?></div>
</section>

<?php if ( $news ) : ?>
<section aria-labelledby="n-title">
  <div class="section-head reveal"><h2 id="n-title">Neue Aufträge.</h2><a class="btn btn-ghost" href="<?php echo esc_url( fahrni_url( 'news' ) ); ?>">Alle News <?php echo fahrni_ic(); ?></a></div>
  <div class="grid"><?php foreach ( $news as $i => $n ) echo fahrni_news_tile( $n, 'span-4', $i * .06 ); ?></div>
</section>
<?php endif; ?>

<?php if ( $social ) : ?>
<section aria-labelledby="sm-title">
  <div class="section-head reveal"><h2 id="sm-title">Social Media. <span>Neu aus unseren Kanälen.</span></h2><a class="btn btn-ghost" href="<?php echo esc_url( fahrni_url( 'social' ) ); ?>">Alle Beiträge <?php echo fahrni_ic(); ?></a></div>
  <div class="grid">
    <?php foreach ( $social as $i => $s ) : $vid = wp_get_attachment_url( (int) fahrni_meta( 'video', $s ) ); ?>
      <a class="tile span-4 sm-tile reveal" style="--d:<?php echo $i * .06; ?>s" href="<?php echo esc_url( fahrni_url( 'social' ) . '#post-' . $s->post_name ); ?>">
        <div class="media"><video muted playsinline loop preload="none" poster="<?php echo esc_url( fahrni_thumb( $s ) ); ?>"><?php if ( $vid ) : ?><source src="<?php echo esc_url( $vid ); ?>" type="video/mp4"><?php endif; ?></video></div>
        <div class="tile-top"><span class="caption"><?php echo esc_html( ucfirst( (string) fahrni_meta( 'kanal', $s ) ) ); ?></span><?php echo fahrni_arrow(); ?></div>
        <div><h3><?php echo esc_html( get_the_title( $s ) ); ?></h3><p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $s->post_content ), 14 ) ); ?></p></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section aria-labelledby="j-title">
  <div class="grid">
    <a class="tile span-8 x-tall reveal" href="<?php echo esc_url( fahrni_url( 'karriere' ) ); ?>" style="justify-content:flex-end">
      <?php $jimg = fahrni_menu_image( 'karriere' ); if ( $jimg ) : ?><div class="media"><img src="<?php echo esc_url( $jimg ); ?>" alt="" loading="lazy" class="pos-top"></div><?php endif; ?>
      <div style="display:grid; gap:14px; max-width:60ch"><span class="caption accent">Karriere</span><h3 id="j-title" style="font-size:clamp(36px,4.4vw,64px)">Bauen Sie mit Fahrni.</h3><p>Offene Stellen in Produktion, Montage, Planung und Logistik.</p><span class="link-line">Offene Stellen <?php echo fahrni_ic(); ?></span></div>
    </a>
    <div class="tile tile-grey span-4 reveal" style="--d:.08s" data-light>
      <span class="eyebrow"><b><?php echo count( $jobs ); ?> offene Stellen</b></span>
      <ul class="steps"><?php foreach ( $jobs as $i => $j ) : ?><li><a href="<?php echo esc_url( get_permalink( $j ) ); ?>"><?php echo esc_html( get_the_title( $j ) ); ?></a> <span><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span></li><?php endforeach; ?></ul>
    </div>
  </div>
</section>
<?php get_footer();
