<?php 
    include ('../../../inc/includes.php');
    $th = new PluginServicesTheme();
    $theme = $th->find([
        'active'  => 1
    ]);

    if (!empty($theme)) {
      foreach ($theme as  $value) {
        echo json_encode([
          "sidebar_header_color"=> $value['sidebar_header_color'],
          "sidebar_color"=> $value['sidebar_color'],
          "sidebar_menu_color"=> $value['sidebar_menu_color'],
          "mark"=> $value['mark'],
        ]);  
      }
    }else {
      echo json_encode([
        "sidebar_header_color"=> '#399fa0',
        "sidebar_color"=> '#3DA8A9',
        "sidebar_menu_color"=> '#f2eeee',
        "mark"=> 'FAGO'
      ]);  
    }
  ?>