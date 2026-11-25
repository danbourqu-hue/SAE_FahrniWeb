<?php
/**
 * Übersicht News (Seite, die unter Einstellungen → Lesen als «Beitragsseite» gewählt ist).
 */
get_header();
fahrni_head( 'News', fahrni_menu_text( 'news' ), [ [ 'News', '' ] ] ); ?>
<div class="grid">
<?php $i = 0; while ( have_posts() ) : the_post(); echo fahrni_news_tile( get_post(), 'span-4', ( $i++ % 3 ) * .06 ); endwhile; ?>
</div>
<?php the_posts_pagination( [ 'prev_text' => '← Neuer', 'next_text' => 'Älter →' ] );
get_footer();
