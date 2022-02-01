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
 * Template for task
 * @since 9.1
**/
class PluginServicesTaskTemplate extends TaskTemplate {

   // From CommonDBTM
  public $dohistory          = true;
  public $can_be_translated  = true;

  static $rightname          = 'taskcategory';


  static function getTable($classname = null) {
    return "glpi_tasktemplates";
  }

  static function getTypeName($nb = 0) {
    return _n('Task template', 'Task templates', $nb);
  }

  function getAdditionalFields() {

    return [['name'  => 'content',
                        'label' => __('Content'),
                        'type'  => 'tinymce',
                        'rows' => 10],
                  ['name'  => 'taskcategories_id',
                        'label' => TaskCategory::getTypeName(1),
                        'type'  => 'dropdownValue',
                        'list'  => true],
                  ['name'  => 'state',
                        'label' => __('Status'),
                        'type'  => 'state'],
                  ['name'  => 'is_private',
                        'label' => __('Private'),
                        'type'  => 'bool'],
                  ['name'  => 'actiontime',
                        'label' => __('Duration'),
                        'type'  => 'actiontime'],
                  ['name'  => 'users_id_tech',
                        'label' => __('By'),
                        'type'  => 'users_id_tech'],
                  ['name'  => 'groups_id_tech',
                        'label' => Group::getTypeName(1),
                        'type'  => 'groups_id_tech'],
                ];
  }

  function rawSearchOptions() {
    $tab = parent::rawSearchOptions();
    $tab = array_merge($tab, self::rawSearchOptionsToAdd());
    return $tab;
  }

  /**
  * @since 0.85
  **/
  static function rawSearchOptionsToAdd($itemtype = null) {

    $task = new static();
    $tab = [];
    $name = _n('Task', 'Tasks', Session::getPluralNumber());

    $task_condition = '';
    if ($task->maybePrivate() && !Session::haveRight("task", CommonITILTask::SEEPRIVATE)) {
      $task_condition = "AND (`NEWTABLE`.`is_private` = 0
                              OR `NEWTABLE`.`users_id` = '".Session::getLoginUserID()."')";
    }

    $tab[] = [
      'id'                 => '5',
      'table'              => 'glpi_groups',
      'field'              => 'completename',
      'linkfield'          => 'groups_id_tech',
      'name'               => __('Group in charge'),
      'datatype'           => 'itemlink',
      'condition'          => ['is_task' => 1],
      'forcegroupby'       => true,
      'massiveaction'      => false,
      'joinparams'         => [
        'beforejoin'         => [
            'table'              => static::getTable(),
            'joinparams'         => [
              'jointype'           => 'child',
              'condition'          => $task_condition,
            ]
        ]
      ]
    ];

    return $tab;
  }
  
  /**
  * @see CommonDropdown::displaySpecificTypeField()
  **/
  function displaySpecificTypeField($ID, $field = []) {

    switch ($field['type']) {
        case 'state' :
          Planning::dropdownState("state", $this->fields["state"]);
          break;
        case 'users_id_tech' :
          User::dropdown([
              'name'   => "users_id_tech",
              'right'  => "own_ticket",
              'value'  => $this->fields["users_id_tech"],
              'entity' => $this->fields["entities_id"],
          ]);
          break;
        case 'groups_id_tech' :
          Group::dropdown([
              'name'     => "groups_id_tech",
              'condition' => ['is_task' => 1],
              'value'     => $this->fields["groups_id_tech"],
              'entity'    => $this->fields["entities_id"],
          ]);
          break;
        case 'actiontime' :
          $toadd = [];
          for ($i=9; $i<=100; $i++) {
              $toadd[] = $i*HOUR_TIMESTAMP;
          }
          Dropdown::showTimeStamp(
              "actiontime", [
                'min'             => 0,
                'max'             => 8*HOUR_TIMESTAMP,
                'value'           => $this->fields["actiontime"],
                'addfirstminutes' => true,
                'inhours'         => true,
                'toadd'           => $toadd
              ]
          );
          break;
    }
  }
}
