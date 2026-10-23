<?php
defined( 'ABSPATH' ) || exit;
$register = 'inscription' === thaimoon_account_view();
do_action( 'woocommerce_before_customer_login_form' );
?>
<div class="account-auth<?php echo $register ? ' account-auth--register' : ''; ?>">
<?php if ( ! $register ) : ?>
    <form class="woocommerce-form woocommerce-form-login login account-panel account-auth__form" method="post" novalidate>
        <?php do_action( 'woocommerce_login_form_start' ); ?>
        <p class="woocommerce-form-row form-row account-form__field">
            <label for="username">Email ou identifiant <span class="required" aria-hidden="true">*</span></label>
            <input type="text" class="woocommerce-Input input-text" name="username" id="username" autocomplete="username" placeholder="Votre email" value="<?php echo esc_attr( thaimoon_account_posted_text( 'username' ) ); ?>" required aria-required="true">
        </p>
        <p class="woocommerce-form-row form-row account-form__field">
            <label for="password">Mot de passe <span class="required" aria-hidden="true">*</span></label>
            <input class="woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="Saisir un mot de passe" required aria-required="true">
        </p>
        <?php do_action( 'woocommerce_login_form' ); ?>
        <div class="account-auth__options">
            <label class="woocommerce-form-login__rememberme account-auth__remember"><input name="rememberme" type="checkbox" id="rememberme" value="forever"><span>Se souvenir de moi</span></label>
            <a class="account-auth__link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Mot de passe oublié ?</a>
        </div>
        <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
        <button type="submit" class="woocommerce-button button woocommerce-form-login__submit account-panel__button" name="login" value="Connexion">Connexion</button>
        <?php do_action( 'woocommerce_login_form_end' ); ?>
    </form>
    <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
        <p class="account-auth__switch">Vous n’avez pas de compte ? <a href="<?php echo esc_url( thaimoon_account_view_url( 'inscription' ) ); ?>">S’inscrire</a></p>
    <?php endif; ?>
<?php else : ?>
    <form method="post" action="<?php echo esc_url( thaimoon_account_view_url( 'inscription' ) ); ?>" class="woocommerce-form woocommerce-form-register register account-panel account-auth__form" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
        <input type="hidden" name="thaimoon_account_register" value="1">
        <?php do_action( 'woocommerce_register_form_start' ); ?>
        <div class="account-auth__columns">
            <div class="account-auth__column">
                <p class="woocommerce-form-row form-row account-form__field"><label for="reg_last_name">Nom <span class="required" aria-hidden="true">*</span></label><input type="text" class="input-text" name="reg_last_name" id="reg_last_name" autocomplete="family-name" placeholder="Votre nom" value="<?php echo esc_attr( thaimoon_account_posted_text( 'reg_last_name' ) ); ?>" required aria-required="true"></p>
                <p class="woocommerce-form-row form-row account-form__field"><label for="reg_first_name">Prénom <span class="required" aria-hidden="true">*</span></label><input type="text" class="input-text" name="reg_first_name" id="reg_first_name" autocomplete="given-name" placeholder="Votre prénom" value="<?php echo esc_attr( thaimoon_account_posted_text( 'reg_first_name' ) ); ?>" required aria-required="true"></p>
                <p class="woocommerce-form-row form-row account-form__field"><label for="reg_phone">Numéro de téléphone <span class="required" aria-hidden="true">*</span></label><input type="tel" class="input-text" name="reg_phone" id="reg_phone" autocomplete="tel" placeholder="Votre téléphone" value="<?php echo esc_attr( thaimoon_account_posted_text( 'reg_phone' ) ); ?>" required aria-required="true"></p>
            </div>
            <div class="account-auth__column">
                <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
                    <p class="woocommerce-form-row form-row account-form__field"><label for="reg_username">Identifiant <span class="required" aria-hidden="true">*</span></label><input type="text" class="input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo esc_attr( thaimoon_account_posted_text( 'username' ) ); ?>" required aria-required="true"></p>
                <?php endif; ?>
                <p class="woocommerce-form-row form-row account-form__field"><label for="reg_email">Email <span class="required" aria-hidden="true">*</span></label><input type="email" class="woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email" placeholder="Votre email" value="<?php echo esc_attr( thaimoon_account_posted_text( 'email' ) ); ?>" required aria-required="true"></p>
                <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
                    <p class="woocommerce-form-row form-row account-form__field"><label for="reg_password">Mot de passe <span class="required" aria-hidden="true">*</span></label><input type="password" class="woocommerce-Input input-text" name="password" id="reg_password" autocomplete="new-password" placeholder="Saisir un mot de passe" required aria-required="true"></p>
                    <p class="woocommerce-form-row form-row account-form__field"><label for="reg_password_confirm">Confirmer votre mot de passe <span class="required" aria-hidden="true">*</span></label><input type="password" class="input-text" name="password_confirm" id="reg_password_confirm" autocomplete="new-password" placeholder="Votre mot de passe" required aria-required="true"></p>
                <?php else : ?>
                    <p class="account-panel__description">Un lien pour définir votre mot de passe vous sera envoyé par email.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php do_action( 'woocommerce_register_form' ); ?>
        <div class="account-panel__actions">
            <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
            <button type="submit" class="woocommerce-button button woocommerce-form-register__submit account-panel__button" name="register" value="Inscription">S’inscrire</button>
        </div>
        <?php do_action( 'woocommerce_register_form_end' ); ?>
    </form>
    <p class="account-auth__switch">Vous avez déjà un compte ? <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Se connecter</a></p>
<?php endif; ?>
</div>
<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
