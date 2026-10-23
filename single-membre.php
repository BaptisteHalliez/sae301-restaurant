<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div id="primary" class="content-area team-page__content">
    <main id="main" class="site-main team-page__main">
        <?php while ( have_posts() ) : the_post(); ?>
            <?php if ( post_password_required() ) : ?>
                <header class="team-heading"><h1 class="team-heading__title"><?php the_title(); ?></h1></header>
                <?php echo get_the_password_form(); ?>
            <?php else : ?>
                <?php
                $member_id = get_the_ID();
                $name = get_the_title();
                $role = thaimoon_member_text( $member_id, 'poste' );
                $tagline = thaimoon_member_text( $member_id, 'accroche' );
                $presentation = esc_html( thaimoon_member_presentation( $member_id ) );
                $safe_name = esc_html( $name );
                if ( '' !== $safe_name ) {
                    $presentation = str_replace( $safe_name, '<strong>' . $safe_name . '</strong>', $presentation );
                }
                $products = thaimoon_member_products( $member_id );
                $others = get_posts( array( 'post_type' => 'membre', 'post_status' => 'publish', 'posts_per_page' => -1, 'post__not_in' => array( $member_id ), 'has_password' => false, 'orderby' => array( 'date' => 'ASC', 'ID' => 'ASC' ) ) );
                ?>
                <header class="team-heading">
                    <h1 class="team-heading__title"><?php echo esc_html( $role ? $role : $name ); ?></h1>
                    <?php if ( $tagline ) : ?><p class="team-heading__description"><?php echo esc_html( $tagline ); ?></p><?php endif; ?>
                </header>
                <section class="member-profile" aria-label="<?php echo esc_attr( 'Présentation de ' . $name ); ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'full', array( 'class' => 'member-profile__portrait', 'alt' => $name, 'loading' => 'eager' ) ); ?>
                    <?php endif; ?>
                    <div class="member-profile__biography"><?php echo wpautop( $presentation ); ?></div>
                </section>
                <?php if ( $products ) : ?>
                    <section class="member-recommendations" aria-labelledby="member-recommendations-title">
                        <header class="team-heading">
                            <h2 class="team-heading__subtitle" id="member-recommendations-title">Recommandations</h2>
                            <p class="team-heading__description"><?php echo esc_html( thaimoon_member_recommendation_intro( $member_id ) ); ?></p>
                        </header>
                        <ul class="products product-grid member-recommendations__products"><?php thaimoon_member_render_products( $products ); ?></ul>
                    </section>
                <?php endif; ?>
                <?php if ( $others ) : ?>
                    <section class="team-page__others" aria-labelledby="other-members-title">
                        <header class="team-heading">
                            <h2 class="team-heading__subtitle" id="other-members-title">Découvrez les autres membres</h2>
                            <p class="team-heading__description">Vous voulez connaître les autres membres de l’équipe ?</p>
                        </header>
                        <ul class="team-grid">
                            <?php foreach ( $others as $other ) : ?>
                                <li class="team-grid__item"><?php get_template_part( 'template-parts/team/member-card', null, array( 'member' => $other, 'heading_level' => 3 ) ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        <?php endwhile; ?>
    </main>
</div>
<?php get_footer(); ?>
