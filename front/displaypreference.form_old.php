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


// Html::popHeader(__('Setup'), $_SERVER['PHP_SELF']);

Session::checkRightsOr('search_config', [PluginServicesDisplayPreference::PERSONAL,
                                              PluginServicesDisplayPreference::GENERAL]);

$setupdisplay = new PluginServicesDisplayPreference();

// print_r($_POST);
// return;
$arr = array('token' => Session::getNewCSRFToken());
if (isset($_POST["activate"])) {
   $setupdisplay->activatePerso($_POST);
   echo json_encode($arr);
   return;
} else if (isset($_POST["disable"])) {
   if ($_POST['users_id'] == Session::getLoginUserID()) {
       $setupdisplay->deleteByCriteria(['users_id' => $_POST['users_id'],
                                                       'itemtype' => $_POST['itemtype']]);
      echo json_encode($arr);
      return;
   }
} else if (isset($_POST["add"])) {
   if ($_POST['num']) {
         $arr['id'] = [];
      foreach ($_POST['num'] as $value) {
         $_POST['num'] = explode("-", $value)[0];
         $add = $setupdisplay->add($_POST);
         if ($add) {
            array_push($arr['id'], $add);
         }
      }
   }
   echo json_encode($arr);
   return;
} else if (isset($_POST["purge"]) || isset($_POST["purge_x"])) {
   if ($_POST['id']) {
      $arr['num'] = [];
      foreach ($_POST['id'] as $value) {
         $_POST['id'] = explode("-", $value)[0];
         $purge = $setupdisplay->delete($_POST, 1);
         if ( $purge ) {
            array_push($arr['num'], $setupdisplay->fields['num']);
         }
      }
   }
   echo json_encode($arr);
   return;
} else if (isset($_POST["up"]) || isset($_POST["up_x"])) {
   // $setupdisplay->orderItem($_POST, 'up');
   // echo json_encode($arr);
   // return;

   if ($_POST['id']) {
      $arr['up'] = [];
      foreach ($_POST['id'] as $value) {
         $_POST['id'] = explode("-", $value)[0];
         $setupdisplay->orderItem($_POST, 'up');
         // if ( $up ) {
            array_push($arr['up'],$_POST['id']);
         // }
      }
   }
   echo json_encode($arr);
   return;
} else if (isset($_POST["down"]) || isset($_POST["down_x"])) {
   // $setupdisplay->orderItem($_POST, 'down');
   // echo json_encode($arr);
   // return;

   if ($_POST['id']) {
      $arr['down'] = [];
      foreach ($_POST['id'] as $value) {
         $_POST['id'] = explode("-", $value)[0];
         $setupdisplay->orderItem($_POST, 'down');
      }
      echo json_encode($arr);
      return;
   }
   
}

// Datas may come from GET or POST : use REQUEST
if (isset($_REQUEST["itemtype"])) {
   $setupdisplay->display(['displaytype' => $_REQUEST['itemtype']]);
}

// Html::popFooter();
