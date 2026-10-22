<?php
/** Habillage commun Thaï Moon — Figma V1.0, thème enfant Storefront. */
defined( 'ABSPATH' ) || exit;

// Storefront charge le parent. Le CSS enfant vient ensuite, avec les variables et le reset.
add_action( 'wp_enqueue_scripts', 'thaimoon_assets', 40 );
function thaimoon_assets() {
    wp_dequeue_style( 'storefront-fonts' );
    wp_dequeue_style( 'thaimoon-fonts' );
    wp_deregister_style( 'storefront-child-style' );
    wp_enqueue_style( 'storefront-child-style', get_stylesheet_uri(), array( 'storefront-style' ), (string) filemtime( get_stylesheet_directory() . '/style.css' ) );
}


// Charger le parent dans sa couche : un CSS hors layer prendrait le dessus sur le thème enfant.
add_action( 'wp_head', 'thaimoon_layer_order', 1 );
function thaimoon_layer_order() {
    echo '<style id="thaimoon-layer-order">@layer reset, storefront, tokens, base, layout, components;</style>' . "\n";
}

add_action( 'wp_print_styles', 'thaimoon_storefront_inline_layers', PHP_INT_MAX );
function thaimoon_storefront_inline_layers() {
    if ( is_admin() ) {
        return;
    }
    $styles = wp_styles();
    foreach ( $styles->registered as $handle => $style ) {
        if ( 0 !== strpos( $handle, 'storefront-' ) || 'storefront-child-style' === $handle ) {
            continue;
        }
        $inline = $styles->get_data( $handle, 'after' );
        if ( ! empty( $inline ) && ! $styles->get_data( $handle, 'thaimoon-layered' ) ) {
            $styles->add_data( $handle, 'after', array( "@layer storefront {\n" . implode( "\n", $inline ) . "\n}" ) );
            $styles->add_data( $handle, 'thaimoon-layered', true );
        }
    }
}

add_filter( 'style_loader_tag', 'thaimoon_storefront_style_layer', 10, 4 );
function thaimoon_storefront_style_layer( $tag, $handle, $href, $media ) {
    if ( is_admin() || 0 !== strpos( $handle, 'storefront-' ) || 'storefront-child-style' === $handle ) {
        return $tag;
    }
    $import = '@import url(' . wp_json_encode( esc_url_raw( $href ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ') layer(storefront);';
    return '<style id="' . esc_attr( $handle . '-css' ) . '" media="' . esc_attr( $media ) . '">' . $import . '</style>' . "\n";
}

add_action( 'after_setup_theme', 'thaimoon_setup', 20 );
function thaimoon_setup() {
    register_nav_menu( 'thaimoon-footer', 'Thaï Moon : liens du pied de page' );
    $header_hooks = array(
        'storefront_header_container' => 0,
        'storefront_site_branding' => 20,
        'storefront_secondary_navigation' => 30,
        'storefront_product_search' => 40,
        'storefront_header_container_close' => 41,
        'storefront_primary_navigation_wrapper' => 42,
        'storefront_primary_navigation' => 50,
        'storefront_header_cart' => 60,
        'storefront_primary_navigation_wrapper_close' => 68,
    );
    foreach ( $header_hooks as $callback => $priority ) {
        remove_action( 'storefront_header', $callback, $priority );
    }
    add_action( 'storefront_header', 'thaimoon_header', 20 );
    remove_action( 'storefront_footer', 'storefront_footer_widgets', 10 );
    remove_action( 'storefront_footer', 'storefront_credit', 20 );
    remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );
    add_action( 'storefront_footer', 'thaimoon_footer', 20 );
    remove_action( 'storefront_sidebar', 'storefront_get_sidebar', 10 );
}

add_filter( 'body_class', 'thaimoon_body_class' );
function thaimoon_body_class( $classes ) {
    $classes[] = 'restaurant';
    return $classes;
}

// Le fond de navigation provient du design system, même si une image d'en-tête est configurée.
add_filter( 'storefront_header_styles', 'thaimoon_header_styles' );
function thaimoon_header_styles( $styles ) {
    unset( $styles['background-image'] );
    return $styles;
}

function thaimoon_image_url( $file ) {
    return get_stylesheet_directory_uri() . '/assets/images/' . $file;
}

function thaimoon_directions_url() {
    $address = get_theme_mod( 'thaimoon_address', "8 rue des Chevaliers\n45190 Beaugency" );
    return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( str_replace( "\n", ', ', $address ) );
}

function thaimoon_shop_url() {
    $url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
    return $url ? $url : home_url( '/' );
}

function thaimoon_phone_url() {
    return 'tel:' . preg_replace( '/[^0-9+]/', '', get_theme_mod( 'thaimoon_phone', '09 83 63 09 84' ) );
}

function thaimoon_brand() {
    ?>
    <a class="header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Thaï Moon — Accueil">
        <img class="header__moon" src="<?php echo esc_url( thaimoon_image_url( 'moon.png' ) ); ?>" width="42" height="42" alt="">
        <span class="header__identity">
            <img class="header__wordmark" src="<?php echo esc_url( thaimoon_image_url( 'wordmark.png' ) ); ?>" width="166" height="30" alt="Thaï Moon">
            <span class="header__tagline">Restaurant Thaïlandais</span>
        </span>
    </a>
    <?php
}

function thaimoon_default_menu() {
    ?>
    <ul class="navigation__list">
        <li class="navigation__item"><a class="navigation__link" href="<?php echo esc_url( home_url( '/' ) ); ?>">Carte</a></li>
        <li class="navigation__item"><a class="navigation__link" href="<?php echo esc_url( thaimoon_directions_url() ); ?>">Itinéraire</a></li>
        <li class="navigation__item"><a class="navigation__link" href="<?php echo esc_url( thaimoon_phone_url() ); ?>">Appeler</a></li>
    </ul>
    <?php
}

// Ajout des classes BEM uniquement à nos menus, sans modifier ceux des extensions.
add_filter( 'nav_menu_css_class', 'thaimoon_menu_item_classes', 10, 4 );
function thaimoon_menu_item_classes( $classes, $item, $args, $depth ) {
    if ( ! empty( $args->thaimoon_menu ) ) {
        $classes[] = 'navigation__item';
    }
    return $classes;
}
add_filter( 'nav_menu_link_attributes', 'thaimoon_menu_link_attributes', 10, 4 );
function thaimoon_menu_link_attributes( $attributes, $item, $args, $depth ) {
    if ( ! empty( $args->thaimoon_menu ) ) {
        $attributes['class'] = trim( ( isset( $attributes['class'] ) ? $attributes['class'] : '' ) . ' navigation__link' );
    }
    return $attributes;
}
add_filter( 'nav_menu_item_id', 'thaimoon_menu_item_id', 10, 4 );
function thaimoon_menu_item_id( $id, $item, $args, $depth ) {
    return ! empty( $args->thaimoon_menu ) ? '' : $id;
}

function thaimoon_navigation() {
    $account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
    $account_label = is_user_logged_in() ? 'Mon compte' : 'Se connecter';
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';
    ?>
    <nav class="navigation" aria-label="Navigation principale">
        <?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'navigation__list', 'items_wrap' => '<ul class="%2$s">%3$s</ul>', 'depth' => 1, 'fallback_cb' => 'thaimoon_default_menu', 'thaimoon_menu' => true ) ); ?>
        <a class="navigation__order" href="<?php echo esc_url( thaimoon_shop_url() ); ?>">
            Commander en ligne
            <img class="navigation__arrow" src="<?php echo esc_url( thaimoon_image_url( 'arrow.svg' ) ); ?>" width="23" height="21" alt="">
        </a>
        <div class="navigation__actions">
            <a class="navigation__icon-link" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php echo esc_attr( $account_label ); ?>" title="<?php echo esc_attr( $account_label ); ?>"<?php if ( function_exists( 'is_account_page' ) && is_account_page() ) : ?> aria-current="page"<?php endif; ?>>
                <img class="navigation__icon" src="<?php echo esc_url( thaimoon_image_url( 'nav-account.svg' ) ); ?>" width="32" height="32" alt="">
            </a>
            <?php if ( $cart_url ) : ?>
                <a class="navigation__icon-link" href="<?php echo esc_url( $cart_url ); ?>" aria-label="Panier" title="Panier"<?php if ( is_cart() ) : ?> aria-current="page"<?php endif; ?>>
                    <img class="navigation__icon" src="<?php echo esc_url( thaimoon_image_url( 'nav-cart.svg' ) ); ?>" width="32" height="32" alt="">
                </a>
            <?php endif; ?>
        </div>
    </nav>
    <?php
}

function thaimoon_header() {
    ?>
    <div class="header">
        <?php thaimoon_brand(); ?>
        <div class="header__desktop"><?php thaimoon_navigation(); ?></div>
        <details class="header__mobile">
            <summary class="header__toggle"><span class="header__burger" aria-hidden="true"></span><span class="screen-reader-text">Menu principal</span></summary>
            <div class="header__panel"><?php thaimoon_navigation(); ?></div>
        </details>
    </div>
    <?php
}

function thaimoon_footer_icon( $name ) {
    $files = array( 'location' => 'location.svg', 'phone' => 'phone.svg', 'clock' => 'clock.svg' );
    if ( isset( $files[ $name ] ) ) {
        echo '<span class="footer__icon" aria-hidden="true"><img src="' . esc_url( thaimoon_image_url( $files[ $name ] ) ) . '" alt=""></span>';
    }
}

function thaimoon_footer_links() {
    $legal = get_page_by_path( 'mentions-legales' );
    $terms = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'terms' ) : 0;
    ?>
    <ul class="footer__links">
        <?php if ( $legal && 'publish' === $legal->post_status ) : ?>
            <li><a href="<?php echo esc_url( get_permalink( $legal ) ); ?>">Mentions légales</a></li>
        <?php endif; ?>
        <?php if ( $terms > 0 && 'publish' === get_post_status( $terms ) ) : ?>
            <li><a href="<?php echo esc_url( get_permalink( $terms ) ); ?>">Conditions générales de vente</a></li>
        <?php endif; ?>
        <?php if ( get_privacy_policy_url() ) : ?>
            <li><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Politique de confidentialité</a></li>
        <?php endif; ?>
    </ul>
    <?php
}

function thaimoon_footer() {
    ?>
    <div class="footer">
        <div class="footer__content">
            <div class="footer__brand">
                <a class="footer__home" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Thaï Moon — Accueil"><img class="footer__logo" src="<?php echo esc_url( thaimoon_image_url( 'logo.png' ) ); ?>" width="156" height="156" alt="Thaï Moon" loading="lazy"></a>
                <p class="footer__description"><?php echo esc_html( get_theme_mod( 'thaimoon_description', "Plongez dans l'univers de la cuisine thaïlandaise sous la lueur de la lune. Chaque plat raconte une histoire, chaque saveur éveille vos sens." ) ); ?></p>
            </div>
            <div class="footer__information">
            <section class="footer__section" aria-labelledby="footer-information">
                <h2 class="footer__title" id="footer-information">Informations</h2>
                <p class="footer__detail"><?php thaimoon_footer_icon( 'location' ); ?><a class="footer__link" href="<?php echo esc_url( thaimoon_directions_url() ); ?>"><?php echo nl2br( esc_html( get_theme_mod( 'thaimoon_address', "8 rue des Chevaliers\n45190 Beaugency" ) ) ); ?></a></p>
                <p class="footer__detail"><?php thaimoon_footer_icon( 'phone' ); ?><a class="footer__link" href="<?php echo esc_url( thaimoon_phone_url() ); ?>"><?php echo esc_html( get_theme_mod( 'thaimoon_phone', '09 83 63 09 84' ) ); ?></a></p>
            </section>
            <section class="footer__section" aria-labelledby="footer-hours">
                <h2 class="footer__title" id="footer-hours">Horaires d'ouverture</h2>
                <p class="footer__detail"><?php thaimoon_footer_icon( 'clock' ); ?><span class="footer__schedule"><strong class="footer__day"><?php echo esc_html( get_theme_mod( 'thaimoon_days', 'Lundi – Dimanche' ) ); ?></strong><span class="footer__hours"><?php echo esc_html( get_theme_mod( 'thaimoon_hours', '12h – 14h30 • 18h – 22h30' ) ); ?></span></span></p>
                <p class="footer__detail"><?php thaimoon_footer_icon( 'clock' ); ?><span class="footer__schedule"><strong class="footer__day">Samedi</strong><span class="footer__hours"><?php echo esc_html( get_theme_mod( 'thaimoon_saturday_hours', '11h – 14h30 • 18h – 22h30' ) ); ?></span></span></p>
            </section>
                <?php $team_url = get_post_type_archive_link( 'membre' ); ?>
                <?php if ( $team_url ) : ?>
                    <section class="footer__section footer__section--team" aria-labelledby="footer-team">
                        <h2 class="footer__title" id="footer-team">Infos Bonus</h2>
                        <a class="footer__link footer__team-link" href="<?php echo esc_url( $team_url ); ?>">
                            <img class="footer__team-icon" src="<?php echo esc_url( thaimoon_image_url( 'team.svg' ) ); ?>" width="32" height="32" alt="" loading="lazy">
                            <span>L’Équipe</span>
                        </a>
                    </section>
                <?php endif; ?>
            </div>
        </div>
        <div class="footer__bottom">
            <p class="footer__copyright">© <?php echo esc_html( wp_date( 'Y' ) ); ?> Thaï Moon. Tous droits réservés.</p>
            <p class="footer__credit">Site créé par <strong>Oyaz</strong></p>
            <nav class="footer__navigation" aria-label="Liens du pied de page">
                <?php
                $footer_menu = wp_nav_menu( array( 'theme_location' => 'thaimoon-footer', 'container' => false, 'menu_class' => 'footer__links', 'depth' => 1, 'fallback_cb' => false, 'echo' => false ) );
                // Un menu affecté mais vide ne déclenche pas toujours le fallback de WordPress.
                if ( $footer_menu ) {
                    echo $footer_menu;
                } else {
                    thaimoon_footer_links();
                }
                ?>
            </nav>
        </div>
    </div>
    <?php
}

add_action( 'customize_register', 'thaimoon_customize' );
function thaimoon_customize( $customizer ) {
    $customizer->add_section( 'thaimoon_restaurant', array( 'title' => 'Thaï Moon : informations du restaurant', 'priority' => 35 ) );
    $fields = array(
        'phone' => array( 'Téléphone', '09 83 63 09 84', 'text' ),
        'address' => array( 'Adresse', "8 rue des Chevaliers\n45190 Beaugency", 'textarea' ),
        'days' => array( 'Jours d’ouverture', 'Lundi – Dimanche', 'text' ),
        'hours' => array( 'Horaires', '12h – 14h30 • 18h – 22h30', 'text' ),
        'saturday_hours' => array( 'Horaires du samedi', '11h – 14h30 • 18h – 22h30', 'text' ),
        'description' => array( 'Présentation du restaurant', "Plongez dans l'univers de la cuisine thaïlandaise sous la lueur de la lune. Chaque plat raconte une histoire, chaque saveur éveille vos sens.", 'textarea' ),
    );
    foreach ( $fields as $key => $field ) {
        $setting = 'thaimoon_' . $key;
        $customizer->add_setting( $setting, array( 'default' => $field[1], 'sanitize_callback' => 'textarea' === $field[2] ? 'sanitize_textarea_field' : 'sanitize_text_field' ) );
        $customizer->add_control( $setting, array( 'label' => $field[0], 'section' => 'thaimoon_restaurant', 'type' => $field[2] ) );
    }
}

// Cartes de la boutique : réglages séparés pour garder ce fichier lisible.
require_once get_stylesheet_directory() . '/inc/woocommerce.php';

// Équipe et fiches membres, maquette Figma V1.4.
require_once get_stylesheet_directory() . '/inc/team.php';

// Page d’accueil V1.6 et nouveautés du catalogue.
require_once get_stylesheet_directory() . '/inc/homepage.php';

// Mentions légales et conditions générales de vente — maquette Figma V1.9.
require_once get_stylesheet_directory() . '/inc/legal-pages.php';
