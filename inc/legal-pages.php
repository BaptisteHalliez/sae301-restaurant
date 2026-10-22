<?php
/** Présentation des pages légales, avec les contenus administrés dans WordPress. */
defined( 'ABSPATH' ) || exit;

function thaimoon_legal_page_type() {
    if ( is_page( 'mentions-legales' ) ) {
        return 'mentions';
    }

    $terms_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'terms' ) : 0;
    if ( is_page( 'conditions-generales-de-vente' ) || ( $terms_id > 0 && is_page( $terms_id ) ) ) {
        return 'terms';
    }

    return '';
}

add_filter( 'body_class', 'thaimoon_legal_body_classes' );
function thaimoon_legal_body_classes( $classes ) {
    $type = thaimoon_legal_page_type();
    if ( $type ) {
        $classes[] = 'legal-page';
        $classes[] = 'legal-page--' . $type;
    }
    return $classes;
}

add_action( 'wp', 'thaimoon_legal_layout' );
function thaimoon_legal_layout() {
    if ( thaimoon_legal_page_type() ) {
        // Le gabarit fournit son fil d’Ariane BEM, à l’intérieur du contenu.
        remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
    }
}

add_filter( 'template_include', 'thaimoon_legal_template' );
function thaimoon_legal_template( $template ) {
    if ( thaimoon_legal_page_type() && ! is_embed() ) {
        return get_stylesheet_directory() . '/template-parts/legal/page.php';
    }
    return $template;
}

function thaimoon_legal_content( $content ) {
    $type = thaimoon_legal_page_type();

    // Les textes actuels utilisent des paragraphes pour leurs intertitres.
    // Leur donner une structure de titres au rendu, sans réécrire la base.
    if ( 'mentions' === $type ) {
        $content = preg_replace( '~<p\b([^>]*)>\s*<strong>([^<]+)</strong>\s*</p>~iu', '<h2$1>$2</h2>', $content );
    } elseif ( 'terms' === $type ) {
        $content = preg_replace_callback( '~<p\b([^>]*)>(.*?)</p>~is', static function ( $match ) {
            $text = trim( html_entity_decode( wp_strip_all_tags( $match[2] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
            if ( preg_match( '/^\d+\.\s+[^\p{Ll}]+$/u', $text ) ) {
                return '<h2' . $match[1] . '>' . $match[2] . '</h2>';
            }
            return $match[0];
        }, $content );
    }

    // Garder les blocs, liens et attributs de l’éditeur ; ajouter seulement les classes.
    $html = new WP_HTML_Tag_Processor( $content );
    $classes = array(
        'P' => 'legal-page__paragraph',
        'H2' => 'legal-page__heading',
        'H3' => 'legal-page__subheading',
        'H4' => 'legal-page__subheading',
        'UL' => 'legal-page__list',
        'OL' => 'legal-page__list',
        'LI' => 'legal-page__item',
        'A' => 'legal-page__link',
    );
    while ( $html->next_tag() ) {
        $tag = $html->get_tag();
        if ( isset( $classes[ $tag ] ) ) {
            $html->add_class( $classes[ $tag ] );
        }
    }
    return $html->get_updated_html();
}
