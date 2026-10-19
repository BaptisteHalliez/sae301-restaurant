<?php
/** Liste de l'équipe — archive native du type de contenu « membre ». */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div id="primary" class="content-area team-page__content">
    <main id="main" class="site-main team-page__main team-page__main--archive">
        <header class="team-heading">
            <h1 class="team-heading__title">L’Équipe du Restaurant</h1>
            <p class="team-heading__description">Venez faire connaissance avec l’équipe passionnée qui travaille sans relâche afin de vous préparer les meilleurs repas</p>
        </header>
        <?php if ( have_posts() ) : ?>
            <ul class="team-grid" aria-label="Les membres de l’équipe">
                <?php while ( have_posts() ) : the_post(); ?>
                    <li class="team-grid__item"><?php get_template_part( 'template-parts/team/member-card', null, array( 'member' => get_post() ) ); ?></li>
                <?php endwhile; ?>
            </ul>
            <div class="team-page__pagination"><?php the_posts_pagination( array( 'prev_text' => 'Précédent', 'next_text' => 'Suivant' ) ); ?></div>
        <?php else : ?>
            <p class="team-page__empty">Les membres de l’équipe seront présentés ici prochainement.</p>
        <?php endif; ?>
    </main>
</div>
<?php get_footer(); ?>
