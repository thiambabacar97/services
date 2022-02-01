<?php

include ('../../../../inc/includes.php');


   if (!isset($_SESSION["glpicookietest"]) || ($_SESSION["glpicookietest"] != 'testcookie')) {
      if (!is_writable(GLPI_SESSION_DIR)) {
         Html::redirect($CFG_GLPI['root_doc'] . "/plugins/services/front/index.php?error=2");
      } else {
         Html::redirect($CFG_GLPI['root_doc'] . "/plugins/services/front/index.php?error=1");
      }
   }
   
   $_POST = array_map('stripslashes', $_POST);
   //Do login and checks
   //$user_present = 1;
   if (isset($_SESSION['namfield']) && isset($_POST['login'])) {
      $login = $_POST['login'];
   } else {
      $login = '';
   }
   if (isset($_SESSION['pwdfield']) && isset($_POST['pwd'])) {
      $password = Toolbox::unclean_cross_side_scripting_deep($_POST['pwd']);
   } else {
      $password = '';
   }
   // Manage the selection of the auth source (local, LDAP id, MAIL id)
   if (isset($_POST['auth'])) {
      $login_auth = $_POST['auth'];
   } else {
      $login_auth = '';
   }
   
   $remember = isset($_SESSION['rmbfield']) && isset($_POST[$_SESSION['rmbfield']]) && $CFG_GLPI["login_remember_time"];
   
   // Redirect management
   $REDIRECT = "";
   if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
      $REDIRECT = "?redirect=" .rawurlencode($_POST['redirect']);
   
   } else if (isset($_GET['redirect']) && strlen($_GET['redirect'])>0) {
      $REDIRECT = "?redirect=" .rawurlencode($_GET['redirect']);
   }
   
   $auth = new PluginServicesAuth();
   
   
   // now we can continue with the process...
   if ($auth->login($login, $password, (isset($_REQUEST["noAUTO"])?$_REQUEST["noAUTO"]:false), $remember, $login_auth)) {
      PluginServicesAuth::redirectIfAuthenticated();
   } else {
      // we have done at least a good login? No, we exit.
      Html::nullHeader("Login", $CFG_GLPI["root_doc"] . '/home');
      echo '<div class="center b">' . $auth->getErr() . '<br><br>';
      // Logout whit noAUto to manage auto_login with errors
      echo '<a href="' . $CFG_GLPI["root_doc"] . '/logout/?noAUTO=1'.
            str_replace("?", "&", $REDIRECT).'">' .__('Log in again') . '</a></div>';
      Html::nullFooter();
      exit();
   }
