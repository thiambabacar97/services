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

/**
 * @since 0.85
 */

$AJAX_INCLUDE = 1;
include ('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!empty($_POST["value"])) {
   $opt = [
      'groups_id' => $_POST["value"],
      'right'     => $_POST['right'],
      'entity'    => $_POST["entity"]
   ];
   $data_users  = PluginServicesTicketTask::getGroupUserHaveRights($opt);
   $users           = [];
   $param['values'] = [];
   $values          = [];
   foreach ($data_users as $data) {
      $users[$data['id']] = formatUserName($data['id'], $data['name'], $data['realname'],
                                          $data['firstname']);
      
      if (in_array($data['id'], $values)) {
         $param['values'][] = $data['id'];
      }
   }
   
   $param['display'] = true;
   $param['display_emptychoice'] = true;
   $users = Toolbox::stripslashes_deep($users);
   $rand  = PluginServicesDropdown::showFromArray('users_id_tech', $users, $param);
}else{
   $rand  = PluginServicesDropdown::showFromArray('users_id_tech', [], ['display_emptychoice' => true]);
}

