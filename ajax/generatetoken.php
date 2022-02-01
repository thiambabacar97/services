<?php
if (!defined('GLPI_ROOT')) {
  include ('../../../inc/includes.php');
}
echo Session::getNewCSRFToken();
return ;
