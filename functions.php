<?php
/*
Plugin Name: Pensando o Direito Network Functions
Plugin URI: http://github.com/pensandoodireito/pensandoodireito-network-functions
Description: Plugin para reunir as funções de rede da plataforma de debates do Pensando o Direito
Author: Marco Antonio Konopacki
Version: 0.1
Author URI: http://github.com/marcoamarelo
*/

add_action('wp_logout', 'pensandoodireito_redirecionar_logout');
/**
 * Redirecionara para a capa depois do logout
 */
function pensandoodireito_redirecionar_logout() {
    wp_redirect( site_url("/") );
    exit();
}

add_action('login_form', 'pensandoodireito_redirecionar_login');
/**
 * Redirecionar para a capa depois do login
 */
function pensandoodireito_redirecionar_login() {
    global $redirect_to;
    if (!isset($_GET['redirect_to'])) {
        $redirect_to = site_url("/");
    }
}

add_action('wp_footer', 'pensandoodireito_login_modal');

function pensandoodireito_login_modal() {
?>
    <div class="modal fade" id="wp-login-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content login">
                ...
            </div>
        </div>
    </div>
<?php
}

add_action( 'login_enqueue_scripts', 'pensandoodireito_login_logo' );
/*
 * Função para substituir o logo padrão da tela de login
*/
function pensandoodireito_login_logo() { ?>

    <style type="text/css">
        #login h1 a {
            background-image: url(<?php echo get_template_directory_uri(); ?>/images/structure/logo-pd-branca.png);
            padding-bottom: 30px;
            background-size: initial;
            background-repeat: no-repeat;
            height: 80px;
            width: 225px;
        }
    </style>

<?php }

#add_action( 'phpmailer_init', 'pensandoodireito_configuracoes_email' );
/**
 * Função para forçar conexão no servidor de e-mails
 */
 function pensandoodireito_configuracoes_email( $phpmailer ) {

     $phpmailer->isSMTP();

     $phpmailer->SMTPAuth = false;

     $phpmailer->Port = SMTP_PORT;

     $phpmailer->Host = SMTP_HOST;

     $phpmailer->From = SMTP_FROM;

     $phpmailer->FromName = SMTP_FROMNAME;
 }

add_action( 'init', 'register_menu_principal' );
/**
 * Registrar os menus da plataforma
 */
function register_menu_principal() {
    register_nav_menu('menu-principal', 'Menu Principal' );
    register_nav_menu('menu-interno', 'Menu Interno' );
}

add_filter( 'pre_comment_user_ip', 'pensandoodireito_correcao_ip_proxy_reverso');
/**
 * Função para corrigir o ip de origem do usuário que comentar para evitar
 * a mensagem de "comentando rápido demais"
 * @return mixed
 */
function pensandoodireito_correcao_ip_proxy_reverso()
{
    $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['X_FORWARDED_FOR'])) {
        $X_FORWARDED_FOR = explode(',', $_SERVER['X_FORWARDED_FOR']);
        if (!empty($X_FORWARDED_FOR)) {
            $REMOTE_ADDR = trim($X_FORWARDED_FOR[0]);
        }
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $HTTP_X_FORWARDED_FOR= explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        if (!empty($HTTP_X_FORWARDED_FOR)) {
            $REMOTE_ADDR = trim($HTTP_X_FORWARDED_FOR[0]);
        }
    }
    return preg_replace('/[^0-9a-f:\., ]/si', '', $REMOTE_ADDR);
}

remove_filter('check_comment_flood', 'check_comment_flood_db');

/**
 * Função para encurtar URLs
 *
 * @param $url
 * @param string $format
 * @param string $version
 * @return string
 */
function pensandoodireito_bitly_url($url,$format = 'xml',$version = '2.0.1')
{
    if ( DOMAIN_CURRENT_SITE  == "localhost" ){
      return $url;
    }

    $bitly_login = BITLY_LOGIN;
    $bitly_api = BITLY_APIKEY;

    $bitly = 'http://api.bit.ly/shorten?version='.$version.'&longUrl='.urlencode($url).'&login='.$bitly_login.'&apiKey='.$bitly_api.'&format='.$format;

    $response = file_get_contents($bitly);

    if(strtolower($format) == 'json')
    {
        $json = @json_decode($response,true);
        return $json['results'][$url]['shortUrl'];
    }
    else //For XML
    {
        $xml = simplexml_load_string($response);
        return 'http://bit.ly/'.$xml->results->nodeKeyVal->hash;
    }
}

// Esconde a barra do wordpress
add_filter('show_admin_bar', '__return_false');

/*
 * Remove Blog Slug Functions
 * Based on:
 *     Plugin Name:    Remove Blog Slug
 *     Description:    This simple and small plugin removes the /blog/-Slug from your WordPress posts in the main blog of your MultiSite installation
 *     Version:        1.0.1
 *     Author:        HerrLlama for wpcoding.de
 *     Author URI:    http://wpcoding.de
 *     Licence:        GPLv3
 */
/**
 * This function rewrites all the permalinks to remove
 * the /blog from the structure
 *
 * @wp-hook    generate_rewrite_rules
 * @param    object $wp_rewrite
 * @return    void
 */
function remove_blog_slug( $wp_rewrite ) {

    // check multisite and main site
    if ( ! is_main_site() )
        return;

    // set checkup
    $rewrite = FALSE;

    // update_option
    $wp_rewrite->permalink_structure = preg_replace( '!^(/)?blog/!', '$1', $wp_rewrite->permalink_structure );
    update_option( 'permalink_structure', $wp_rewrite->permalink_structure );

    // update the rest of the rewrite setup
    $wp_rewrite->author_structure = preg_replace( '!^(/)?blog/!', '$1', $wp_rewrite->author_structure );
    $wp_rewrite->date_structure = preg_replace( '!^(/)?blog/!', '$1', $wp_rewrite->date_structure );
    $wp_rewrite->front = preg_replace( '!^(/)?blog/!', '$1', $wp_rewrite->front );

    // walk through the rules
    $new_rules = array();
    foreach ( $wp_rewrite->rules as $key => $rule )
        $new_rules[ preg_replace( '!^(/)?blog/!', '$1', $key ) ] = $rule;
    $wp_rewrite->rules = $new_rules;

    // walk through the extra_rules
    $new_rules = array();
    foreach ( $wp_rewrite->extra_rules as $key => $rule )
        $new_rules[ preg_replace( '!^(/)?blog/!', '$1', $key ) ] = $rule;
    $wp_rewrite->extra_rules = $new_rules;

    // walk through the extra_rules_top
    $new_rules = array();
    foreach ( $wp_rewrite->extra_rules_top as $key => $rule )
        $new_rules[ preg_replace( '!^(/)?blog/!', '$1', $key ) ] = $rule;
    $wp_rewrite->extra_rules_top = $new_rules;

    // walk through the extra_permastructs
    $new_structs = array();
    foreach ( $wp_rewrite->extra_permastructs as $extra_permastruct => $struct ) {
        $struct[ 'struct' ] = preg_replace( '!^(/)?blog/!', '$1', $struct[ 'struct' ] );
        $new_structs[ $extra_permastruct ] = $struct;
    }
    $wp_rewrite->extra_permastructs = $new_structs;
} add_action( 'generate_rewrite_rules', 'remove_blog_slug' );

/**
 * This function loads the textdomain for this plugin
 *
 * @wp-hook    plugins_loaded
 * @return    void
 */
function rbs_prepare_localization() {

    load_plugin_textdomain( 'remove-blog-slug', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} add_action( 'plugins_loaded', 'rbs_prepare_localization' );

/**
 * This function adds an admin notice to the permalink
 * settings page to describe what the plugin does.
 *
 * @wp-hook    admin_notices
 * @return    void
 */
function rbs_admin_notice() {

    // check if we are on the permalink page
    global $pagenow;
    if ( $pagenow != 'options-permalink.php' )
        return;

    echo '<div class="updated">';
        echo '<p>';
            echo '<strong>';
                _e( 'Please note:', 'remove-blog-slug' );
            echo '</strong> ';
            _e( 'You are using the plugin Remove Blog Slug. Even if here is "blog" in the structure, the plugin works. This is because WordPress has hard-coded "blog". Simply update the structure and the front end is "blog" is no longer displayed.', 'remove-blog-slug' );
        echo '</p>';
    echo '</div>';
} add_action( 'admin_notices', 'rbs_admin_notice' );
