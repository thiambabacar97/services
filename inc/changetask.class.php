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

class PluginServicesChangeTask extends CommonITILTask {

  static $rightname = 'plugin_services_changetask';

  static function getTable($classname = null) {
    return "glpi_changetasks";
  }

  static function getTypeName($nb = 0) {
    return _n('Change task', 'Change tasks', $nb);
  }

  static function canCreate() {
    return Session::haveRight('change', UPDATE)
        || Session::haveRight(self::$rightname, parent::ADDALLITEM);
  }


  static function canView() {
    return Session::haveRightsOr('change', [Change::READALL, Change::READMY]);
  }


  static function canUpdate() {
    return Session::haveRight('change', UPDATE)
        || Session::haveRight(self::$rightname, parent::UPDATEALL);
  }


  function canViewPrivates() {
    return true;
  }


  function canEditAll() {
    return Session::haveRightsOr('change', [CREATE, UPDATE, DELETE, PURGE]);
  }


  /**
  * Does current user have right to show the current task?
  *
  * @return boolean
  **/
  function canViewItem() {
    return $this->canReadITILItem();
  }


  /**
  * Does current user have right to create the current task?
  *
  * @return boolean
  **/
  function canCreateItem() {

    if (!$this->canReadITILItem()) {
        return false;
    }

    $change = new Change();
    if ($change->getFromDB($this->fields['changes_id'])) {
        return (Session::haveRight(self::$rightname, parent::ADDALLITEM)
                || Session::haveRight('change', UPDATE)
                || (Session::haveRight('change', Change::READMY)
                    && ($change->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                        || (isset($_SESSION["glpigroups"])
                            && $change->haveAGroup(CommonITILActor::ASSIGN,
                                                  $_SESSION['glpigroups'])))));
    }
    return false;

  }


  /**
  * Does current user have right to update the current task?
  *
  * @return boolean
  **/
  function canUpdateItem() {

    if (!$this->canReadITILItem()) {
        return false;
    }

    if (($this->fields["users_id"] != Session::getLoginUserID())
        && !Session::haveRight('change', UPDATE)
        && !Session::haveRight(self::$rightname, parent::UPDATEALL)) {
        return false;
    }

    return true;
  }


  /**
  * Does current user have right to purge the current task?
  *
  * @return boolean
  **/
  function canPurgeItem() {
    return $this->canUpdateItem();
  }


  /**
  * Populate the planning with planned ticket tasks
  *
  * @param $options array of possible options:
  *    - who ID of the user (0 = undefined)
  *    - whogroup ID of the group of users (0 = undefined)
  *    - begin Date
  *    - end Date
  *
  * @return array of planning item
  **/
  static function populatePlanning($options = []) :array {
    return parent::genericPopulatePlanning(__CLASS__, $options);
  }


  /**
  * Display a Planning Item
  *
  * @param array           $val       array of the item to display
  * @param integer         $who       ID of the user (0 if all)
  * @param string          $type      position of the item in the time block (in, through, begin or end)
  * @param integer|boolean $complete  complete display (more details)
  *
  * @return string
  */
  static function displayPlanningItem(array $val, $who, $type = "", $complete = 0) {
    return parent::genericDisplayPlanningItem(__CLASS__, $val, $who, $type, $complete);
  }

  /**
  * Populate the planning with not planned ticket tasks
  *
  * @param $options array of possible options:
  *    - who ID of the user (0 = undefined)
  *    - whogroup ID of the group of users (0 = undefined)
  *    - begin Date
  *    - end Date
  *
  * @return array of planning item
  **/
  static function populateNotPlanned($options = []) :array {
    return parent::genericPopulateNotPlanned(__CLASS__, $options);
  }

  function rawSearchOptions() {
    return self::rawSearchOptionsToAdd();
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
        'id'                 => 'task',
        'name'               => $name
    ];

    $tab[] = [
      'id'                 => '1',
      'table'              => static::getTable(),
      'field'              => 'name',
      'name'               => __('Name'),
      'datatype'           => 'itemlink'
    ];

    $tab[] = [
        'id'                 => '26',
        'table'              => static::getTable(),
        'field'              => 'content',
        'name'               => __('Description'),
        'datatype'           => 'text',
        'forcegroupby'       => true,
        'splititems'         => true,
        'massiveaction'      => false,
        'htmltext'           => true,
        'joinparams'         => [
          'jointype'           => 'child',
          'condition'          => $task_condition,
        ]
    ];

    $tab[] = [
        'id'                 => '28',
        'table'              => static::getTable(),
        'field'              => 'id',
        'name'               => _x('quantity', 'Number of tasks'),
        'forcegroupby'       => true,
        'usehaving'          => true,
        'datatype'           => 'count',
        'massiveaction'      => false,
        'joinparams'         => [
          'jointype'           => 'child',
          'condition'          => $task_condition,
        ]
    ];

    $tab[] = [
        'id'                 => '20',
        'table'              => 'glpi_taskcategories',
        'field'              => 'name',
        'datatype'           => 'dropdown',
        'name'               => __('Category'),
        'forcegroupby'       => true,
        'splititems'         => true,
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

    if ($task->maybePrivate()) {

        $tab[] = [
          'id'                 => '92',
          'table'              => static::getTable(),
          'field'              => 'is_private',
          'name'               => __('Private task'),
          'datatype'           => 'bool',
          'forcegroupby'       => true,
          'splititems'         => true,
          'massiveaction'      => false,
          'joinparams'         => [
              'jointype'           => 'child',
              'condition'          => $task_condition,
          ]
        ];
    }

    $tab[] = [
        'id'                 => '94',
        'table'              => 'glpi_users',
        'field'              => 'name',
        'name'               => __('Writer'),
        'datatype'           => 'itemlink',
        'right'              => 'all',
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

    $tab[] = [
        'id'                 => '95',
        'table'              => 'glpi_users',
        'field'              => 'name',
        'linkfield'          => 'users_id_tech',
        'name'               => __('Technician in charge'),
        'datatype'           => 'itemlink',
        'right'              => 'own_ticket',
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

    $tab[] = [
        'id'                 => '112',
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

    $tab[] = [
        'id'                 => '96',
        'table'              => static::getTable(),
        'field'              => 'actiontime',
        'name'               => __('Duration'),
        'datatype'           => 'timestamp',
        'massiveaction'      => false,
        'forcegroupby'       => true,
        'joinparams'         => [
          'jointype'           => 'child',
          'condition'          => $task_condition,
        ]
    ];

    $tab[] = [
        'id'                 => '97',
        'table'              => static::getTable(),
        'field'              => 'date',
        'name'               => _n('Date', 'Dates', 1),
        'datatype'           => 'datetime',
        'massiveaction'      => false,
        'forcegroupby'       => true,
        'joinparams'         => [
          'jointype'           => 'child',
          'condition'          => $task_condition,
        ]
    ];

    $tab[] = [
        'id'                 => '33',
        'table'              => static::getTable(),
        'field'              => 'state',
        'name'               => __('Status'),
        'datatype'           => 'specific',
        'searchtype'         => 'equals',
        'searchequalsonfield' => true,
        'massiveaction'      => false,
        'forcegroupby'       => true,
        'joinparams'         => [
          'jointype'           => 'child',
          'condition'          => $task_condition,
        ]
    ];

    $tab[] = [
        'id'                 => '173',
        'table'              => static::getTable(),
        'field'              => 'begin',
        'name'               => __('Begin date'),
        'datatype'           => 'datetime',
        'massiveaction'      => false,
        'forcegroupby'       => true,
        'joinparams'         => [
          'jointype'           => 'child',
          'condition'          => $task_condition,
        ]
    ];

    $tab[] = [
        'id'                 => '174',
        'table'              => static::getTable(),
        'field'              => 'end',
        'name'               => __('End date'),
        'datatype'           => 'datetime',
        'massiveaction'      => false,
        'forcegroupby'       => true,
        'joinparams'         => [
          'jointype'           => 'child',
          'condition'          => $task_condition,
        ]
    ];

    $tab[] = [
        'id'                 => '175',
        'table'              => TaskTemplate::getTable(),
        'field'              => 'name',
        'linkfield'          => 'tasktemplates_id',
        'name'               => TaskTemplate::getTypeName(1),
        'datatype'           => 'dropdown',
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

    $tab[] = [
      'id'                 => '176',
      'table'              => Change::getTable(),
      'field'              => 'name',
      'linkfield'          => 'changes_id',
      'name'               => Change::getTypeName(1),
      'datatype'           => 'dropdown',
      'massiveaction'      => false,
    ];
    return $tab;
  }

  static function dropdownState($options = []) {
    $p = [
        'name' => 'state',
        'rand' => mt_rand()
    ];
    foreach ($options as $key => $val) {
        $p[$key] = $val;
    }
    PluginServicesPlanning::dropdownState($p['name'],  $p['value'], true, ['rand' =>  $p['rand']]);
  }


}
