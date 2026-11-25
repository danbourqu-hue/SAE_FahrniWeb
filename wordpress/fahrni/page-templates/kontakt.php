<?php
/**
 * Template Name: Kontakt
 * Telefon und Adresse aus «Design → Anpassen → FAHRNI», Standorte aus «Standorte», Bild = Beitragsbild der Seite.
 */
get_header();
the_post();
$img = fahrni_thumb( null, 'fahrni-wide' );
$mail = fahrni_opt( 'email' );
fahrni_head( get_the_title(), has_excerpt() ? get_the_excerpt() : fahrni_menu_text( 'kontakt' ), [ [ get_the_title(), '' ] ] ); ?>
<div class="grid" style="margin-bottom:var(--gap)">
	<a class="tile span-8 tall reveal" href="<?php echo esc_url( fahrni_tel_href() ); ?>" style="justify-content:flex-end">
		<?php if ( $img ) : ?><div class="media"><img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy"></div><?php endif; ?>
		<div style="display:grid;gap:10px"><span class="eyebrow"><b>Hauptsitz</b> · Lyss</span><span style="font-family:var(--head);font-size:clamp(40px,5vw,76px);line-height:1"><?php echo esc_html( fahrni_opt( 'telefon' ) ); ?></span><p><?php echo esc_html( str_replace( "\n", ', ', fahrni_opt( 'adresse' ) ) ); ?></p></div>
	</a>
	<div class="tile tile-blue span-4 tall reveal" style="--d:.08s" data-light><span class="eyebrow">E-Mail</span><div><h3>Schreiben Sie uns</h3>
		<p><?php echo $mail ? '<a href="mailto:' . esc_attr( antispambot( $mail ) ) . '">' . esc_html( antispambot( $mail ) ) . '</a>' : '<span class="email-todo">E-Mail-Adresse unter Design → Anpassen → FAHRNI eintragen</span>'; ?></p></div></div>
</div>
<div class="grid"><?php echo fahrni_site_tiles(); ?></div>
<?php if ( trim( get_the_content() ) ) : ?><div class="detail" style="margin-top:var(--gap)"><div class="prose prose-card" style="grid-column:1/-1"><?php the_content(); ?></div></div><?php endif;
get_footer();
