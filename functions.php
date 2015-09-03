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

if ( defined("USE_CUSTOM_SIGNUP") && USE_CUSTOM_SIGNUP == true ) {
    add_action('register_url', 'pensandoodireito_register_url');

    function pensandoodireito_register_url() {
        return network_site_url('/cadastro/' );
    }

    add_action( 'signup_header', 'pensandoodireito_signup_header' );

    function pensandoodireito_signup_header() {
        if (!isset($_REQUEST['stage'])) {
            wp_redirect( network_site_url( '/cadastro/' ) );
        }
    }
}

add_action('wp_footer', 'pensandoodireito_login_modal');

function pensandoodireito_login_modal() {
?>
    <!-- Modal -->
    <div class="modal fade" id="modalcadastro" tabindex="-1" role="dialog" aria-labelledby="modalcadastro">
        <div class="modal-dialog">
            <form action="" method="post">
            <div class="modal-content modal-sm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title red font-roboto">Login</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="email">Usuário</label>
                        <input type="text" class="form-control" id="username"
                               placeholder="Seu e-mail" required>
                    </div>
                    <div class="form-group mt-md">
                        <label for="senha">Sua senha:</label>
                        <input type="password" class="form-control" id="senha"
                               placeholder="Sua senha" required>
                    </div>
                    <button type="submit" class="btn btn-danger">Entrar</button>
                </div>
                <div class="modal-footer">
                    <p><a href="#" class="remember_me">Esqueceu a senha?</a> | <a href="/cadastro">Cadastre-se</a></p>
                </div>
            </div>
            </form>
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

add_action( 'phpmailer_init', 'pensandoodireito_configuracoes_email' );
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
    if (!defined("BITLY_LOGIN") || !defined("BITLY_APIKEY")) { return $url; }

    $bitly_login = BITLY_LOGIN;
    $bitly_api = BITLY_APIKEY;

    $bitly = 'http://api.bit.ly/shorten?version='.$version.'&longUrl='.urlencode($url).'&login='.$bitly_login.'&apiKey='.$bitly_api.'&format='.$format;

    $ctx = stream_context_create(array('http'=>
    array(
                'timeout' => 5, // 10 Seconds
            )
        ));

    $response = @file_get_contents($bitly,false,$ctx);

    if ($response == FALSE) { return $url; }

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


/**
 * Class relativa à nova tela de cadastro
 */
class Pensando_registration_form {
    // form properties
    private $username;
    private $email;
    private $password;
    private $nice_name; // Full Name
    private $ref_url;

    function __construct(){
        add_shortcode('pd_registration_form', array($this, 'shortcode'));
        $this->addFunctionToAction('email_exists_ajax');
        $this->addFunctionToAction('username_valid_ajax');

    }

    private function addFunctionToAction($method){
        add_action( 'wp_ajax_' . $method, array($this,  $method));
        add_action( 'wp_ajax_nopriv_' . $method, array($this, $method));
    }

    function email_exists_ajax()
    {
        $email = isset($_POST['email'])?$_POST['email']:'';
        $exists = email_exists($email) !== false;
        $json = json_encode($exists);

        header("Content-type: application/json", true);
        die($json);
    }

    function username_valid_ajax(){
        $username = isset($_GET['username'])?$_GET['username']:'';
        $json = array();
        if(!validate_username($username)){
            $json['error'] = 'user_invalid';
        }elseif(username_exists($username)){
            $json['error'] = 'user_exists';
        }else{
            $json['success'] = true;
        }
        header("Content-type: application/json", true);
        die(json_encode($json));

    }

    public function registration_form() {

?>

        <div id="cadastro">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="font-roboto red">Cadastre-se</h1>
                    </div>
                </div>
                <div class="row mt-md">
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div id="form-cadastro">
                                    <form>
                                        <div class="form-group">
                                            <label for="nomeUser">Nome de <span class="red">usuário</span>:</label>
                                            <input type="text" class="form-control" id="nomeUser"
                                                   placeholder="Nome de usuário">
                                        </div>
                                        <div class="form-group mt-md">
                                            <label for="nomeApres">Nome de <span class="red">apresentação:</span></label>
                                            <input type="text" class="form-control" id="nomeApres"
                                                   placeholder="Nome de apresentação">
                                        </div>
                                        <div class="form-group mt-md">
                                            <label for="email">E-mail</label>
                                            <input type="text" class="form-control" id="email"
                                                   placeholder="Seu e-mail">
                                        </div>
                                        <div class="form-group mt-md">
                                            <label for="senha">Sua senha:</label>
                                            <input type="password" class="form-control" id="senha"
                                                   placeholder="Sua senha">
                                        </div>
                                        <div class="form-group mt-md">
                                            <input type="checkbox" name="termos_uso" id="termos_uso"> Li e aceito os <a href="<?php echo site_url('/termos-de-uso/'); ?>" target="_blank">termos de uso</a>.
                                        </div>
                                        <button type="submit" class="btn btn-danger mt-md">Cadastrar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-body bg-success pt-lg text-center">
                                <h3 class="font-roboto text-success"><i class="fa fa-check "></i> Cadastro realizado com
                                    sucesso!
                                </h3>

                                <p class="mt-md h4"><strong>Agora verifique seu e-mail.</strong></p>

                                <p>Você receberá um e-mail de confirmação, basta clicar no link e você poderá participar de
                                    qualquer debate do projeto! Obrigado!</p>
                            </div>
                        </div>
                        <div class="panel panel-default">
                            <div class="panel-body bg-danger pt-lg text-center">
                                <h3 class="font-roboto red"><i class="fa fa-exclamation-circle"></i> Ooops!</h3>

                                <p class="mt-md h4"><strong>Ocorreu um erro durante o seu cadastro.</strong></p>

                                <p>Tente novamente em alguns instantes</p>
                            </div>
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-sm-4 col-xs-12">
                                <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/oquee/oquee-001.png"
                                     class="img-adptive"
                                     alt="Proteção de Dados Pessoais">
                            </div>
                            <div class="col-sm-8 col-xs-12">
                                <h3 class="font-roboto red">Mais de <strong><?php echo get_user_count(); ?></strong> participantes!</h3>
                                <ul class="list-unstyled text-left h5">
                                    <li class="mt-sm text-success"><i class="fa fa-check "></i> <strong>contribua</strong>
                                        com suas ideias e opiniões
                                    </li>
                                    <li class="mt-sm text-success"><i class="fa fa-check"></i> fique por dentro das
                                        <strong>leis em elaboração</strong></li>
                                    <li class="mt-sm text-success"><i class="fa fa-check"></i> <strong>participe</strong>
                                        do processo legislativo
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-md">
                            <div class="col-md-12">
                                <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                                    <div class="panel panel-default">
                                        <div class="panel-heading" role="tab" id="headingOne">
                                            <h4 class="panel-title">
                                                <a role="button" data-toggle="collapse" data-parent="#accordion"
                                                   href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                                    Por que devo me cadastrar?
                                                </a>
                                            </h4>
                                        </div>
                                        <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel"
                                             aria-labelledby="headingOne">
                                            <div class="panel-body">
                                                <p>Porque esta é mais uma oportunidade de diálogo aberta pelo governo para ouvir
                                                    a
                                                    sociedade sobre temas importantes.</p>

                                                <p>Após realizar o seu cadastro, você poderá
                                                    comentar os debates públicos abertos no site, concordar ou discordar de
                                                    outros
                                                    comentários, criar novas pautas e responder à pautas criadas por outros
                                                    usuários.</p>

                                                <p>Por isso, ao se cadastrar, você será uma parte importante do processo,
                                                    e sua opinião pode influenciar leis, decretos, portarias, e outras peças
                                                    normativas sobre assuntos relevantes ao nosso país.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel panel-default">
                                        <div class="panel-heading" role="tab" id="headingTwo">
                                            <h4 class="panel-title">
                                                <a class="collapsed" role="button" data-toggle="collapse"
                                                   data-parent="#accordion" href="#collapseTwo" aria-expanded="false"
                                                   aria-controls="collapseTwo">
                                                    O que são "debates"?
                                                </a>
                                            </h4>
                                        </div>
                                        <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel"
                                             aria-labelledby="headingTwo">
                                            <div class="panel-body">
                                                Na plataforma, os "debates" podem ser projetos, anteprojetos de lei, textos de
                                                decreto ou portarias que estão abertos à participação social para sua
                                                consolidação. Eles se destinam a coletar opiniões diversas e qualificadas sobre
                                                os temas em discussão.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="panel panel-default">
                                        <div class="panel-heading" role="tab" id="headingThree">
                                            <h4 class="panel-title">
                                                <a class="collapsed" role="button" data-toggle="collapse"
                                                   data-parent="#accordion" href="#collapseThree" aria-expanded="false"
                                                   aria-controls="collapseThree">
                                                    Quem promove esta iniciativa?
                                                </a>
                                            </h4>
                                        </div>
                                        <div id="collapseThree" class="panel-collapse collapse" role="tabpanel"
                                             aria-labelledby="headingThree">
                                            <div class="panel-body">
                                                Esta plataforma é uma iniciativa da Secretaria de Assuntos
                                                Legislativos do Ministério da Justiça e do projeto Pensando o Direito.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

      <?php
    }

    function validation() {
      if (empty($this->username) || empty($this->email) || empty($this->password) || empty($this->nice_name) ){
          return new WP_Error('field', 'Você deixou de preencher pelo menos um campo obrigatório.');
      }

      if (empty($this->termos_uso)) {
          return new WP_Error('termos_nao_aceitos', 'Você deve aceitar os termos de uso para se cadastrar.');
      }

      if (strlen($this->username) < 4 ) {
          return new WP_Error('username_length', 'Seu nome de usuário deve ter ao menos 4 caracteres.');
      }

      if (strlen($this->password) < 8 ) {
          return new WP_Error('password', 'Sua senha deve ter pelo menos 8 caracteres.');
      }

      if (!is_email($this->email)) {
          return new WP_Error('email_invalid', 'Email inválido.');
      }

      if (email_exists($this->email)) {
          return new WP_error('email', 'Email já cadastrado.');
      }

      $details = array('nome de usuário' => $this->username
      );

      foreach ($details as $field => $detail) {
          if (!validate_username($detail)) {
              return new WP_Error('name_invalid', 'Verifique o "' . $field . '". Os caracteres devem ser minúsculos, não deve haver espaços, nem caracteres especiais.');
          }
      }

    }

    function registration() {
        $userdata = array(
            'user_login' => esc_attr($this->username),
            'user_email' => esc_attr($this->email),
            'user_nice_name' => esc_attr($this->nice_name),
            'user_pass' => esc_attr($this->password),
            'ref_url' => $this->ref_url
        );

        if (is_wp_error($this->validation())) {
            echo '<div style="margin-bottom: 6px;" class="btn btn-block btn-lg btn-danger">';
            echo '<strong>' . $this->validation()->get_error_message() . '</strong>';
            echo '</div>';
        } else {
            $user_meta = array( 'user_nice_name' => $userdata['user_nice_name'],
                'user_pass' => wp_hash_password($userdata['user_pass']),
                'ref_url' => $userdata['ref_url']);
            wpmu_signup_user( $userdata['user_login'], $userdata['user_email'], $user_meta );
            echo '<div style="margin-bottom: 6px" class="btn btn-block btn-lg btn-danger">';
            echo '<strong>Clique no link enviado por email para confirmar seu cadastro.</strong>';
            echo '</div>';
        }
    }

    /**
     * Persiste os campos especiais salvos no signup
     *
     * @param $user_id
     * @param $password
     * @param $meta
     */
    function participacao_salvar_campos_usuario ($user_id, $password, $meta) {

        global $wpdb;

        wp_update_user( array ('ID' => $user_id, 'display_name' => $meta['user_nice_name']));

        $wpdb->query( $wpdb->prepare("UPDATE " . $wpdb->base_prefix . "users SET user_pass = %s WHERE ID=%d", $meta['user_pass'], $user_id ));

        wp_set_auth_cookie ( $user_id );
        wp_safe_redirect( $meta['ref_url'] );

        exit;
    }

    /**
     * Função que captura todo fluxo de saída do php para
     * poder fazer posterior login e redirecionamento do usuário
     * após sua ativação (ao clicar no link do email).
     * 'Palmas' para o dev do wpmu .....
     **/
    function participacao_controle_fluxo () {
        ob_start();
    }

    function shortcode(){
        ob_start();

        if (isset($_POST['reg_submit'])) {
            $this->username = $_POST['reg_username'];
            $this->email = $_POST['reg_email'];
            $this->password = $_POST['reg_password'];
            $this->nice_name = $_POST['reg_nice_name'];
            $this->termos_uso = $_POST['termos_uso'];
            $this->ref_url = $_POST['ref_url'];

            $this->validation();
            $this->registration();
         }

        $this->registration_form();
        return ob_get_clean();
    }
}
$reg_form = new Pensando_registration_form;
add_action('wpmu_activate_user', array(&$reg_form, 'participacao_salvar_campos_usuario'), 10, 3 );
add_action('activate_wp_head', array(&$reg_form, 'participacao_controle_fluxo'), 100, 0 );

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


/**
 * Função para verificar se a página já existe
 **/
function get_page_by_name($pagename){
    $pages = get_pages();
    $pagename_sanitized = sanitize_title($pagename);
    foreach ($pages as $page) if ($page->post_name == $pagename_sanitized) return true;
    return false;
}

/**
 *  Função que cria uma determinada página caso ela não exista
 * @titulo: Título da página (e que será utilizado como url da mesma)
 * @nome: url da página. Caso não haja, será utilizado o título ''sanitizado'
 * @conteudo: conteúdo a ser adicionado à página
 **/
function pd_create_page($args) {
  if (!isset($args['titulo'])) return false;

  if( !isset($args['nome']) ) $args['nome'] = sanitize_title($args['titulo']);

  if (!get_page_by_name($args['nome'])) {

     $new_page = array(
         'post_type' => 'page',
         'post_title' => $args['titulo'],
         'post_name' => $args['nome'],
         'post_status' => 'publish',
         'post_author' => 1,
     );

     if ( isset($args['conteudo']) ) $new_page['post_content'] = $args['conteudo'];

     wp_insert_post($new_page);

  }
}

add_shortcode('youtube', 'wp_youtube_video');
function wp_youtube_video($atts) {
    extract(shortcode_atts(array('id' => ''), $atts));
    return '<iframe title="YouTube video player" class="youtube-player" type="text/html" width="640" height="390" src="http://www.youtube.com/embed/'.$id.'" frameborder="0" allowFullScreen></iframe>';
}

/**
 * Get Youtube video ID from URL
 *
 * @author http://stackoverflow.com/users/778669/kus
 * @reference http://stackoverflow.com/a/17799714
 * @param string $url
 * @return mixed Youtube video ID or FALSE if not found
 */
function getYoutubeIdFromUrl($url) {
    $parts = parse_url($url);
    if(isset($parts['query'])){
        parse_str($parts['query'], $qs);
        if(isset($qs['v'])){
            return $qs['v'];
        }else if(isset($qs['vi'])){
            return $qs['vi'];
        }
    }
    if(isset($parts['path'])){
        $path = explode('/', trim($parts['path'], '/'));
        return $path[count($path)-1];
    }
    return false;
}
