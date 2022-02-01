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
    die("Sorry. You can't access this file directly");
}

/**
 * UserEmail class
**/
class PluginServicesUserEmail  extends UserEmail {

   // From CommonDBTM
    public $auto_message_on_action = false;

    // From CommonDBChild
    static public $itemtype        = 'User';
    static public $items_id        = 'users_id';
    public $dohistory              = true;


    static function getTypeName($nb = 0) {
      return _n('Email', 'Emails', $nb);
    }

    static function getTable($classname = null){
      return 'glpi_useremails';
    }

    static function getClassName() {
      return get_called_class();
    }

    /**
    * @since 0.85 (since 0.85 but param $id since 0.85)
    *
    * @param $canedit
    * @param $field_name
    * @param $id
   **/
    function showChildForItemForm($canedit, $field_name, $id) {

      if ($this->isNewID($this->getID())) {
        $value = '';
      } else {
        $value = Html::entities_deep($this->fields['email']);
      }

      $field_name = $field_name."[$id]";
      echo "<input title='".__s('Default email')."' type='hidden' name='_default_email'
              value='".$this->getID()."'";
      if (!$canedit) {
          echo " disabled";
      }
      if ($this->fields['is_default']) {
          echo " checked";
      }
      echo ">";
      if (!$canedit || $this->fields['is_dynamic']) {
          echo "<input type='hidden' class='form-control' name='$field_name' value='$value'>";
          printf(__('%1$s %2$s'), $value, "<span class='b'>(". __('D').")</span>");
      } else {
          echo "<input type='text' class='form-control' size=30 name='$field_name' value='$value' >";
      }
    }


    /**
    * Show emails of a user
    *
    * @param $user User object
    *
    * @return void
   **/
    static function showForUser(User $user) {
      
      $users_id = $user->getID();

      if (!$user->can($users_id, READ)
          && ($users_id != Session::getLoginUserID())) {
          return false;
      }
      
      $canedit = ($user->can($users_id, UPDATE) || ($users_id == Session::getLoginUserID()));
      
      parent::showChildsForItemForm($user, '_useremails', $canedit);

    }
}