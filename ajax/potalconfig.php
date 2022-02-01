<?php 
    include ('../../../inc/includes.php');
    $appr = new PluginPortailHome();
    $theme = $appr->find();
    if (!empty($theme)) {
      foreach ($theme as  $value) {
        echo json_encode([
          "logo"=> $value['logo'],
          "banner"=> $value['banner'],
          "textBanner" => $value['textBanner']
        ]);  
      }
    }else {
      echo json_encode([
        'logo'=> 'logo_yw.png',
        'banner'=> 'defaulft_banner.png',
        'mark'=>  'fago',
        'textBanner' => 'BIENVENUE SUR FAGO'
      ]);  
    }
  ?>