<?php 
  include ('../../../inc/includes.php');
  global $DB;
    $iterator = $DB->request("SELECT * FROM glpi_plugin_services_menus WHERE display_name LIKE '{$_POST['query']}%' AND display_menu = 1 AND menu_type = 'submenu' LIMIT 100");
    while ($data = $iterator->next()) {
      echo json_encode($data);
    }

  ?>