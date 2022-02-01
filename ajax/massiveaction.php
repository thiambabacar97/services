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
 * @since 0.84
 */

include ('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

try {
   $ma = new PluginServicesMassiveAction($_POST, $_GET, 'initial');
} catch (Exception $e) {

   echo "<div class='d-flex justify-content-center'><img src='".$CFG_GLPI["root_doc"]."/pics/warning.png' alt='".
                              __s('Warning')."'>";
   echo "<span class='b'>".$e->getMessage()."</span><br>";
   echo "</div>";
   exit();

}

echo "<div class='container-fluid'>";
echo "<div class='row justify-content-center'>";
   echo "<div class='col-8'>";
      PluginServicesHtml::openMassiveActionsForm();
      $params = ['action' => '__VALUE__'];
      $input  = $ma->getInput();
      foreach ($input as $key => $val) {
         $params[$key] = $val;
      }

      $actions = $params['actions'];

      if (count($actions)) {
         if (isset($params['hidden']) && is_array($params['hidden'])) {
            foreach ($params['hidden'] as $key => $val) {
               echo PluginServicesHtml::hidden($key, ['value' => $val]);
            }
         }

            echo"<label>";
               echo _n('Action', 'Actions', 1);
            echo "</label>";

            $actions = ['-1' => PluginServicesDropdown::EMPTY_VALUE] + $actions;
            $rand    = PluginServicesDropdown::showFromArray('massiveaction', $actions);

         PluginServicesAjax::updateItemOnSelectEvent("dropdown_massiveaction$rand", "show_massiveaction$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",
                                       $params);

         echo "<span id='show_massiveaction$rand'>&nbsp;</span>\n";
      }

      // Force 'checkbox-zero-on-empty', because some massive actions can use checkboxes
      $CFG_GLPI['checkbox-zero-on-empty'] = true;
      PluginServicesHtml::closeForm();
      echo "</div>";
   echo "</div>";
   echo "</div>";
