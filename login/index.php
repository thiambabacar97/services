<!DOCTYPE html>
<?php
    include('../../../inc/includes.php');

    if (version_compare(PHP_VERSION, '7.2.0') < 0) {
      die('PHP >= 7.2.0 required');
    }

    $TRY_OLD_CONFIG_FIRST = true;
    $_SESSION["glpicookietest"] = 'testcookie';
  
    // For compatibility reason
    if (isset($_GET["noCAS"])) {
      $_GET["noAUTO"] = $_GET["noCAS"];
    }
  
    if (!isset($_GET["noAUTO"])) {
      PluginServicesAuth::redirectIfAuthenticated();
    }

    PluginServicesHtml::nullHeader("Login", $CFG_GLPI["root_doc"] . '/index.php');
?>
<?php   
    $th = new PluginServicesTheme();
    $themes = $th->find(['active' => 1]);
    if (!empty($themes)) {
      foreach ($themes as  $value) {
        $theme = $value; 
      }
    }else {
      $theme = [
        "sidebar_header_color"=> '#399fa0',
        "sidebar_color"=> '#3DA8A9',
        "sidebar_menu_color"=> '#f2eeee'
      ];
    }
?>	
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="images/icons/favicon.ico"/>
  <?=Html::css('services/assets/login_css/util.css');?>
  <?=Html::css('services/assets/login_css/main.css');?>
  <?=Html::css('services/assets/login_css/font-awesome.min.css');?>
  <?=Html::css('services/assets/login_css/icon-font.min.css');?>
  <style>
    .login100-form-btn {
      background-color:  <?= $theme['sidebar_color'] ?>;
      color: <?= $theme['sidebar_menu_color'] ?>;
      margin-left: 30%;
    }
    .m-checkbox.m-checkbox--focus>input:checked~span {
      border: 1px solid  <?= $theme['sidebar_color'] ?>;
    }
    .m-checkbox.m-checkbox--focus>span:after {
      border: solid <?= $theme['sidebar_color'] ?>;
    } 
    input{
      background: #fff !important;
      box-shadow: <?php echo $theme['sidebar_color']; ?> 0px 0px 1px 0px !important;
    }
    textarea:not([disabled]), input:not(.submit):not([type=submit]):not([type=reset]):not([type=checkbox]):not([type=radio]):not(.select2-search__field):not(.numInput):not([disabled]) {
        background-color: #FCFCFC;
        color: <?php echo $theme['sidebar_color']; ?> 0px 0px 1px 0px !important;;
    }
    .login100-form-btn:hover {
        background-color: <?= $theme['sidebar_header_color'] ?>;
    }
    .login100-form-title-1 {
        padding: 10px;
        border-radius: 15px;
        font-size: 25px;
        background: rgba(20, 20, 20, 0.44);
    }
    textarea, input:not(.submit):not([type=submit]):not([type=reset]):not([type=checkbox]):not([type=radio]):not(.select2-search__field):not(.numInput) {
      border: 1px solid #D3D3D3;
      font-size: 16px;
      border-radius: 3px;
      padding: 0 10px;
    }
    input:focus-visible {
      border: 1px solid  <?= $theme['sidebar_color'] ?> !important;
    }
  </style>
<!--===============================================================================================-->
</head>
<body>
	
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form-title" style="background-image: url(<?= $CFG_GLPI['root_doc']?>/assets/img/banner/default-login-banner.jpg);">
					<span class="login100-form-title-1">
              Connectez-vous à votre compte
					</span>
				</div>
        <?php
          echo "<div class='error'>";	
          if (isset($_GET['error'])) {
            switch ($_GET['error']) {
            case 1 : // cookie error
              echo 'You must accept cookies to reach this application';
              break;
        
            case 2 : // GLPI_SESSION_DIR not writable
              echo 'Checking write permissions for session files';
              break;
        
            case 3 :
              echo 'Invalid use of session ID';
              break;
            }
          }
          echo "</div>";
        ?>
				<form method="post" id="mainform"  method="post" action="<?= $CFG_GLPI['root_doc']?>/services/login/auth/" class="login100-form validate-form">
          <?= Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]) ?>
          <?php
            $_SESSION['namfield'] = $namfield = 'login';
            $_SESSION['pwdfield'] = $pwdfield = 'pwd';
            $_SESSION['rmbfield'] = $rmbfield = uniqid('fieldc');
          ?>
          <?php
              // Other CAS
              if (isset($_GET["noAUTO"])) {
                echo "<input type='hidden' name='noAUTO' value='1' />";
              }
              // redirect to ticket
              if (isset($_GET["redirect"])) {
                Toolbox::manageRedirect($_GET["redirect"]);
                echo '<input type="hidden" name="redirect" value="'.Html::entities_deep($_GET['redirect']).'"/>';
              }
          ?>
					<div class="wrap-input100 validate-input m-b-26" data-validate="L'identifiant est obligatoire">
						<input class="input100" type="text" name="<?= $namfield ?>" placeholder="Identifiant">
						<span class="focus-input100"></span>
					</div>

					<div class="wrap-input100 validate-input m-b-18" data-validate = "Le mot de passe est obligatoire">
						<input class="input100" type="password" name="<?= $pwdfield ?>" placeholder="Mot de passe">
						<span class="focus-input100"></span>
					</div>
          <!-- Add dropdown for auth (local, LDAPxxx, LDAPyyy, imap...) -->
          <?php  if ($CFG_GLPI['display_login_source']) { ?>
            <div class="wrap-input100 validate-input m-b-18" >
                <?=  Auth::dropdownLogin(); ?>
            </div>
          <?php  }?>

					<div class="flex-sb-m w-full p-b-30">
						<div class="contact100-form-checkbox">
              <label class="m-checkbox m-checkbox--focus">											
                <?php
                  if ($CFG_GLPI["login_remember_time"]) {
                    echo '<input type="checkbox" name="'.$rmbfield.'" id="login_remember"
                        '.($CFG_GLPI['login_remember_default']?'checked="checked"':'').' />
                        <span></span>
                      '.__('Remember me').'';
                  }
                ?>
              </label>
						</div>
					</div>

					<div class="container-login100-form-btn d-flex align-items-center">
						<button class="login100-form-btn mb-3">
              Se connecter
						</button>
            <?php Plugin::doHook('display_login'); ?>
					</div>
          
				</form>
			</div>
		</div>
	</div>
	
<!--===============================================================================================-->
  <?=Html::script('services/assets/login_js/main.js');?>
  <?=Html::script('services/assets/login_js/jquery-3.2.1.min.js');?>
  <?=Html::script('services/assets/login_js/popper.min.js');?>
  <?=Html::script('services/assets/login_js/bootstrap.min.js');?>
  <?=Html::css('services/assets/css/customer.glpi.form.css');?>
  <!-- <?php PluginServicesHtml::nullLogin();?> -->

</body>
</html>