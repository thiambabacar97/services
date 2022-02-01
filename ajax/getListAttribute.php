<?php
use Glpi\Dashboard\Item;

$AJAX_INCLUDE = 1;
include ('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();
if (!empty($_POST["value"])) {
   
   $data_departement  = PluginServicesItem::getAttributeForTable(getTableForItemType($_POST["value"]));
   
   $departements           = [];
   $param['values'] = [];
   $values          = [];
   if (is_array($data_departement) && count($data_departement)){
      foreach ($data_departement as $key =>$data) {
         $departements[$data['id']] = $data['name'];                
         if (in_array($data['id'], $values)) {
            $param['values'][] = $data['id'];
         }
      }
      $param['display'] = true;
      $param['display_emptychoice'] = true;
      $departements = Toolbox::stripslashes_deep($departements);
      $rand  = PluginServicesDropdown::showFromArray('field', $departements, $param);
   }
   
}else{
   $rand  = PluginServicesDropdown::showFromArray('field', [], ['display_emptychoice' => true]);
}

