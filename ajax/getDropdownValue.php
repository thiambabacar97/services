<?php

// Direct access to file
if (strpos($_SERVER['PHP_SELF'], "getDropdownValue.php")) {
   include ('../../../inc/includes.php');
   header("Content-Type: application/json; charset=UTF-8");
   Html::header_nocache();
} else if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

Session::checkLoginUser();

echo PluginServicesDropdown::getDropdownValue($_POST);
