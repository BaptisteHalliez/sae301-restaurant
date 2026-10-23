<?php
defined( 'ABSPATH' ) || exit;
get_header();
$home_products = thaimoon_home_latest_products();
$home_images = array( 'home-kao-pad.png', 'home-noodles.png', 'home-pad-thai.png', 'home-boeuf-loc-lac.png', 'home-kai-tod.png', 'home-coco-curry.png' );
?>
<div id="primary" class="content-area home-page__content">
    <main id="main" class="site-main home-page__main">
        <div class="home-carousel" aria-hidden="true">
            <div class="home-carousel__track">
                <?php for ( $copy = 0; $copy < 2; $copy++ ) : ?>
                    <?php foreach ( $home_images as $image ) : ?>
                        <div class="home-carousel__item<?php echo $copy ? ' home-carousel__item--copy' : ''; ?>">
                            <img class="home-carousel__image" src="<?php echo esc_url( thaimoon_image_url( $image ) ); ?>" alt="" width="210" height="210" decoding="async">
                        </div>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>

        <div class="home-page__body">
            <header class="home-welcome">
                <h1 class="home-welcome__title">Bienvenue chez THÁI MOON</h1>
                <p class="home-welcome__description">Découvrez la cuisine thaïlandaise. Épicez vos plats comme vous le souhaitez.</p>
            </header>

            <section class="home-latest" aria-labelledby="home-latest-title">
                <header class="home-latest__heading">
                    <h2 class="home-latest__title" id="home-latest-title">Les Nouveautés</h2>
                    <span class="home-latest__underline" aria-hidden="true"></span>
                    <p class="home-latest__description">Découvrez les derniers produits ajoutés au menu, à commander dès maintenant !</p>
                </header>
                <?php if ( $home_products ) : ?>
                    <div class="home-latest__products"><?php thaimoon_home_render_products( $home_products ); ?></div>
                <?php else : ?>
                    <p class="home-latest__empty">Les nouveautés arrivent bientôt. Revenez découvrir les prochains ajouts à notre carte !</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
<?php get_footer(); ?>
