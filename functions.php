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


/**
 * Class relativa à nova tela de cadastro
 */
class Pensando_registration_form {
    // form properties
    private $username;
    private $email;
    private $password;
    private $nice_name; // Full Name

    function __construct(){
        add_shortcode('pd_registration_form', array($this, 'shortcode'));
    }

    public function registration_form() {

      $reg_username = ( ! empty( $_POST['reg_username'] ) ) ? trim( $_POST['reg_username'] ) : '';
      $reg_email = ( ! empty( $_POST['reg_email'] ) ) ? trim( $_POST['reg_email'] ) : '';
      $reg_password = ( ! empty( $_POST['reg_password'] ) ) ? trim( $_POST['reg_password'] ) : '';
      $reg_nice_name = ( ! empty( $_POST['reg_nice_name'] ) ) ? trim( $_POST['reg_nice_name'] ) : '';

      ?>
      <div id="hello">
          <div class="col-lg-12 text-center">
              <h1 class="font-roboto red">Mais de <strong><?php echo get_user_count(); ?></strong> pessoas já estão participando!</h1>
          </div>
          <div class="container">
              <div class="row text-center">
                  <div class="col-md-6 col-md-offset-4">
                      <ul class="list-unstyled text-left h5">
                          <li class="mt-sm text-success"><span class="fa fa-check "></span> <strong>contribua</strong> com suas ideias e opiniões</li>
                          <li class="mt-sm text-success"><span class="fa fa-check"></span> fique por dentro das <strong>leis em elaboração</strong></li>
                          <li class="mt-sm text-success"><span class="fa fa-check"></span> <strong>participe</strong> do processo legislativo</li>
                   </ul>
                  </div>
              </div>
          </div>
      </div>
      <div id="register" class="pt-lg">
          <div class="container">
              <div class="row">
                  <div class="col-xs-6 pr-lg">
                       <dl>
                          <dt><h5 class="font-roboto red"><strong>Por que devo me cadastrar?</strong></h5></dt>
                          <dd class="mb-lg">Porque esta é uma plataforma que insere a sociedade no debate de projetos ou anteprojetos de lei no Brasil. Criar leis não é uma tarefa fácil e não deve estar somente nas mãos dos magistrados. Nosso objetivo é complementar - e em alguns casos até mesmo substituir - formas tradicionais de elaboração de leis. Você pode propor uma nova redação a cada artigo do texto normativo, contestar argumentos de outros participantes e agregar informações como artigos, textos e notícias. Por isso, ao se cadastrar, você será uma parte importante do processo, e sua opinião pode influenciar leis sobre assuntos que interessam ao nosso país.
                          </dd>
                          <dt><h5 class="font-roboto red"><strong>O que são "debates"?</strong></h5></dt>
                          <dd class="mb-lg">Na plataforma, os "debates" podem ser projetos ou anteprojetos de lei que estão abertos à participação social para sua consolidação. Eles não têm caráter plebiscitário, mas se destinam a coletar opiniões diversas e qualificadas sobre os temas em discussão. Depois dessa fase de discussão, as contribuições serão sistematizadas com a colaboração de acadêmicos, representantes da sociedade civil, membros do segmento empresarial e demais interessados.
                          </dd>
                          <dt><h5 class="font-roboto red"><strong>Quem promove esta iniciativa?</strong></h5></dt>
                          <dd class="mb-lg">Esta plataforma de participação da sociedade no processo legislativo é uma iniciativa da Secretaria de Assuntos Legislativos do Ministério da Justiça.
                          </dd>
                          <dt><h5 class="font-roboto red"><strong>Quais os termos de uso da plataforma?</strong></h5></dt>
                          <dd class="mb-lg">Nossos termos de uso são bem razoáveis. Leia os <a href="http://localhost/termos-de-uso/" target="_blank">termos de uso</a>.
                          </dd>
                       </dl>
                  </div>
                  <div class="col-xs-6 well">
                      <h4 class="font-roboto red">Comece a participar:</h4>
                      <form id="reg_form" method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                          <div class="form-group">
                              <label for="reg_nice_name" class="control-label">Nome Completo<span class="red">*</span></label>
                              <input type="text" class="form-control" id="reg_nice_name" name="reg_nice_name" value="<?php echo $reg_nice_name; ?>" required title="Insira seu nome">
                              <span class="help-block"></span>

                              <label for="reg_username" class="control-label">Nome de Usuário<span class="red">*</span></label>
                              <input type="text" class="form-control" id="reg_username" name="reg_username" value="<?php echo $reg_username; ?>" required title="Insira seu nome de usuário">
                              <span class="help-block">Este nome será visível para todos os usuários da Plataforma.</span>
                              <label for="reg_email" class="control-label">Email<span class="red">*</span></label>
                              <input type="email" class="form-control" id="reg_email" name="reg_email" value="<?php echo $reg_email; ?>" required title="Insira seu email">
                              <span class="help-block">Verifique se digitou corretamente, pois vamos te enviar um email de confirmação.</span>
                          </div>
                          <div class="form-group">
                              <label for="reg_password" class="control-label">Senha<span class="red">*</span></label>
                              <input type="password" class="form-control" id="reg_password" name="reg_password" value="<?php echo $reg_password; ?>" required title="Insira uma senha">
                              <label>
                                  <input type="checkbox" name="showpassword" id="showpassword"> Mostrar senha
                              </label>
                          </div>
                          <div class="form-group text-right">
                              <label for="termos_uso">
                                  <input type="checkbox" name="termos_uso" id="termos_uso"> Li e aceito os <a href="http://localhost/termos-de-uso/" target="_blank">termos de uso</a>.
                              </label>
                          </div>
                          <button type="submit" name="reg_submit" class="btn btn-success btn-lg btn-block"><i class="fa fa-check-square-o"></i> Cadastrar</button>
                      </form>
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

      $details = array('Username' => $this->username
      );

      foreach ($details as $field => $detail) {
          if (!validate_username($detail)) {
              return new WP_Error('name_invalid', 'Desculpe, mas "' . $field . '" não é uma entrada válida');
          }
      }

    }

    function registration() {
        $userdata = array(
            'user_login' => esc_attr($this->username),
            'user_email' => esc_attr($this->email),
            'user_nice_name' => esc_attr($this->nice_name),
            'user_pass' => esc_attr($this->password),
        );

        if (is_wp_error($this->validation())) {
            echo '<div style="margin-bottom: 6px;" class="btn btn-block btn-lg btn-danger">';
            echo '<strong>' . $this->validation()->get_error_message() . '</strong>';
            echo '</div>';
        } else {
            $register_user = wp_insert_user($userdata);
            if (!is_wp_error($register_user)) {
                echo '<div style="margin-bottom: 6px" class="btn btn-block btn-lg btn-danger">';
                echo '<strong>Registro completo. Vá para a <a href="' . wp_login_url() . '">página de login</a></strong>';
                echo '</div>';
            } else {
                echo '<div style="margin-bottom: 6px" class="btn btn-block btn-lg btn-danger">';
                echo '<strong>' . $register_user->get_error_message() . '</strong>';
                echo '</div>';
            }
        }
    }

    function shortcode(){
        ob_start();

        if (isset($_POST['reg_submit'])) {
            $this->username = $_POST['reg_username'];
            $this->email = $_POST['reg_email'];
            $this->password = $_POST['reg_password'];
            $this->nice_name = $_POST['reg_nice_name'];
            $this->termos_uso = $_POST['termos_uso'];

            $this->validation();
            $this->registration();
         }

        $this->registration_form();
        return ob_get_clean();
    }
}
new Pensando_registration_form;


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
