<?php


include('../../../inc/includes.php');

//@session_start();

if ($CFG_GLPI["ssovariables_id"] > 0
    && strlen($CFG_GLPI['ssologout_url']) > 0) {
   Html::redirect($CFG_GLPI["ssologout_url"]);
}

if (!isset($_SESSION["noAUTO"])
    && isset($_SESSION["glpiauthtype"])
    && $_SESSION["glpiauthtype"] == Auth::CAS
    && Toolbox::canUseCAS()) {

   phpCAS::client(CAS_VERSION_2_0, $CFG_GLPI["cas_host"], intval($CFG_GLPI["cas_port"]),
                  $CFG_GLPI["cas_uri"], false);
   phpCAS::setServerLogoutURL(strval($CFG_GLPI["cas_logout"]));
   phpCAS::logout();
}

$toADD = "";

// Redirect management
if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
   $toADD = "?redirect=" .$_POST['redirect'];

} else if (isset($_GET['redirect']) && (strlen($_GET['redirect']) > 0)) {
   $toADD = "?redirect=" .$_GET['redirect'];
}

if (isset($_SESSION["noAUTO"]) || isset($_GET['noAUTO'])) {
   if (empty($toADD)) {
      $toADD .= "?";
   } else {
      $toADD .= "&";
   }
   $toADD .= "noAUTO=1";
}

Session::destroy();

//Remove cookie to allow new login
$cookie_name = session_name() . '_rememberme';
$cookie_path = ini_get('session.cookie_path');

if (isset($_COOKIE[$cookie_name])) {
   setcookie($cookie_name, '', time() - 3600, $cookie_path);
   unset($_COOKIE[$cookie_name]);
}

// Redirect to the login-page
Html::redirect($CFG_GLPI["root_doc"]."/login".$toADD);
