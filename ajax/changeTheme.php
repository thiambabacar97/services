<?php
include ('../../../inc/includes.php');
$th = new PluginServicesTheme();
$currentTheme = $th->find(['active' => 1]);

if ($currentTheme) {
  foreach($currentTheme as $value) {

    $ressetCurrentTheme = $th->update([
      'id' =>  $value['id'],
      'active' => 0
    ]);

    if ($ressetCurrentTheme) {
      $th->update($_POST);
    }

  }
}else {
  $th->update($_POST);
}
