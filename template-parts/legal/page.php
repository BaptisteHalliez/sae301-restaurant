<?php
/** Gabarit commun des pages légales — Figma V1.9, ordinateur et mobile. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div id="primary" class="content-area legal-page__area">
    <main id="main" class="site-main legal-page__main">
        <?php while ( have_posts() ) : the_post(); ?>
            <nav class="legal-page__breadcrumbs" aria-label="Fil d’Ariane">
                <ol class="legal-page__trail">
                    <li class="legal-page__crumb">
                        <a class="legal-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
                    </li>
                    <li class="legal-page__crumb">
                        <span class="legal-page__separator" aria-hidden="true">/</span>
                        <span class="legal-page__current" aria-current="page"><?php the_title(); ?></span>
                    </li>
                </ol>
            </nav>
            <article class="legal-page__article" aria-labelledby="legal-page-title">
                <header class="legal-page__header">
                    <h1 class="legal-page__title" id="legal-page-title"><?php the_title(); ?></h1>
                </header>
                <div class="legal-page__body">
                    <?php
                    echo thaimoon_legal_content( apply_filters( 'the_content', get_the_content() ) );
                    wp_link_pages( array( 'before' => '<nav class="legal-page__pagination" aria-label="Pages du document">', 'after' => '</nav>' ) );
                    ?>
                </div>
            </article>
        <?php endwhile; ?>
    </main>
</div>
<?php get_footer(); ?>
