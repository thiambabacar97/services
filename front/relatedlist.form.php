<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   include ('../../../inc/includes.php');
}

$setupdisplay = new PluginServicesRelatedlist();
if (isset($_POST["add"])) {
   $datas = $setupdisplay->find(['itemtype'=>$_POST['itemtype']]);
   $newlist_columId = [];
   $existinglist_columId = [];
   foreach($datas as $exist){
      $existinglist_columId[$exist['columId'].'***'.$exist['itemlink']] = $exist['columId'].'***'.$exist['itemlink'];
   }

   if (isset($_POST['itemlinkAndColumid']) && is_array($_POST['itemlinkAndColumid'])) {
      foreach ($_POST['itemlinkAndColumid'] as $value) {
         $link = explode('**', $value) ; 
         $test = [];
         $post = [];
         if (count($link) && count($link) === 3) {
            $post['itemtype'] = $_POST["itemtype"];
            $post['users_id'] = $_POST["users_id"];
            $post['itemlink'] = (isset($link[0])) ? $link[0] : null;
            $post['columId'] = (isset($link[1])) ? $link[1] : null;
            $post['columName'] = (isset($link[2])) ? $link[2] : null;
            
         }else {
            $post['itemtype'] = $_POST["itemtype"];
            $post['users_id'] = $_POST["users_id"];
            $post['pivotitem'] = (isset($link[0])) ? $link[0] : null;
            $post['itemlink'] = (isset($link[1])) ? $link[1] : null;
            $post['columId'] = (isset($link[2])) ? $link[2] : null;
            $post['columName'] = (isset($link[3])) ? $link[3] : null;
         }
         foreach($datas as $exist){
            if(in_array($exist['itemlink'], $post) && in_array($exist['columId'], $post)){
               $newlist_columId[$exist['columId'].'***'.$exist['itemlink']] = $exist['columId'].'***'.$exist['itemlink'];
            }
         }
         
         $ifexist = $setupdisplay->find(['itemlink' => $post['itemlink'],'columId' => $post['columId']]);
         if ($ifexist) {
            continue;
         }
         $setupdisplay->add($post);
      }

      $res = array_diff($existinglist_columId, $newlist_columId);
      if(count($res)){
         $link = '';
         $key = '';
         foreach($res as $val){
            $itemlink = explode('***', $val);
            $link = (count($itemlink) && count($itemlink)== 2) ? $itemlink[1] : null;
            $key = (count($itemlink) && count($itemlink)== 2) ? $itemlink[0] : null;
            if($link && $key){
               $setupdisplay->getFromDBByCrit(['itemlink' => $link, 'columId'=> $key]);
               $id = $setupdisplay->fields["id"];
               $setupdisplay->delete(["id" => $id]);
            }
         }
         PluginServicesHtml::back();
      }
      
      PluginServicesHtml::back();
   }else{
      PluginServicesHtml::back();
   }
} 
