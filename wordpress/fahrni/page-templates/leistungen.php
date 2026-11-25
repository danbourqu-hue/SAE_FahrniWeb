<?php
/**
 * Template Name: Leistungen
 * Zeigt alle Einträge aus «Leistungen» (Reihenfolge = Feld «Reihenfolge»), Text und Bild im Wechsel.
 */
get_header();
the_post();
$leist = get_posts( [ 'post_type' => 'leistung', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
fahrni_head( get_the_title(), has_excerpt() ? get_the_excerpt() : fahrni_menu_text( 'leistungen' ), [ [ get_the_title(), '' ] ] ); ?>
<nav class="jump" aria-label="Leistungen"><?php foreach ( $leist as $l ) : ?><a class="chip" href="#<?php echo esc_attr( fahrni_meta( 'anker', $l ) ?: $l->post_name ); ?>"><?php echo esc_html( get_the_title( $l ) ); ?></a><?php endforeach; ?></nav>
<?php foreach ( $leist as $i => $l ) : $img = fahrni_thumb( $l, 'fahrni-wide' ); ?>
	<div class="service" id="<?php echo esc_attr( fahrni_meta( 'anker', $l ) ?: $l->post_name ); ?>">
		<article class="tile s-text reveal<?php echo $i % 2 ? ' tile-grey' : ''; ?>" data-light><span class="num"><?php echo esc_html( sprintf( '%02d / %02d', $i + 1, count( $leist ) ) ); ?></span><h2><?php echo esc_html( get_the_title( $l ) ); ?></h2><div class="prose" style="grid-column:auto"><?php echo apply_filters( 'the_content', $l->post_content ); // phpcs:ignore ?></div></article>
		<?php if ( $img ) : ?><div class="tile s-img reveal" style="--d:.08s" data-light><div class="media"><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title( $l ) ); ?>" loading="lazy"></div><span class="caption"><?php echo esc_html( get_the_title( $l ) ); ?></span></div><?php endif; ?>
	</div>
<?php endforeach;
get_footer();
