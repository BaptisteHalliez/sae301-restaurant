<?php
/**
 * Pied de page commun Thaï Moon ; le texte WooCommerce et son filtre sont conservés.
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */
defined( 'ABSPATH' ) || exit;
$email = $email ?? null;
$thaimoon_email = require __DIR__ . '/thaimoon-email-settings.php';
$email_footer_text = get_option( 'woocommerce_email_footer_text' );
if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
    $pending_footer = get_transient( 'woocommerce_email_footer_text' );
    $email_footer_text = false !== $pending_footer ? $pending_footer : $email_footer_text;
}
$phone_url = 'tel:' . preg_replace( '/[^0-9+]/', '', $thaimoon_email['phone'] );
?>
                            </div></td></tr>
                        </table>
                    </td></tr>
                </table>
            </td></tr>
        </table>
    </td></tr>
    <tr><td>
        <table id="template_footer" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="<?php echo esc_attr( $thaimoon_email['footer'] ); ?>">
            <tr><td id="credit">
                <p class="email-footer__name">THAÏ MOON</p>
                <p class="email-footer__detail"><?php echo esc_html( $thaimoon_email['address'] ); ?></p>
                <p class="email-footer__detail"><a href="<?php echo esc_url( $phone_url ); ?>"><?php echo esc_html( $thaimoon_email['phone'] ); ?></a></p>
                <p class="email-footer__detail"><?php echo esc_html( $thaimoon_email['days'] . ' : ' . $thaimoon_email['hours'] ); ?><br>Samedi : <?php echo esc_html( $thaimoon_email['saturday_hours'] ); ?></p>
                <p class="email-footer__links"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Voir la carte</a> &nbsp;·&nbsp; <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Mon compte</a></p>
                <div class="email-footer__custom"><?php echo wp_kses_post( wpautop( wptexturize( apply_filters( 'woocommerce_email_footer_text', $email_footer_text, $email ) ) ) ); ?></div>
            </td></tr>
        </table>
    </td></tr>
</table>
</div>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr>
</table>
</body>
</html>
