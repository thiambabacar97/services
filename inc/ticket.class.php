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

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Ticket Class
**/
class PluginServicesTicket extends Ticket {

   // From CommonDBTM
   public $dohistory                   = true;
   static protected $forward_entity_to = ['TicketValidation', 'TicketCost'];

   // From CommonITIL
   public $userlinkclass               = 'Ticket_User';
   public $grouplinkclass              = 'Group_Ticket';
   public $supplierlinkclass           = 'Supplier_Ticket';

   static $rightname                   = 'ticket';

   protected $userentity_oncreate      = true;

   const MATRIX_FIELD                  = 'priority_matrix';
   const URGENCY_MASK_FIELD            = 'urgency_mask';
   const IMPACT_MASK_FIELD             = 'impact_mask';
   const STATUS_MATRIX_FIELD           = 'ticket_status';

   // HELPDESK LINK HARDWARE DEFINITION : CHECKSUM SYSTEM : BOTH=1*2^0+1*2^1=3
   const HELPDESK_MY_HARDWARE  = 0;
   const HELPDESK_ALL_HARDWARE = 1;

   // Specific ones
   /// Hardware datas used by getFromDBwithData
   public $hardwaredatas = [];
   /// Is a hardware found in getHardwareData / getFromDBwithData : hardware link to the job
   public $computerfound = 0;

   // Request type
   const INCIDENT_TYPE = 1;
   // Demand type
   const DEMAND_TYPE   = 2;

   const READMY           =      1;
   const READALL          =   1024;
   const READGROUP        =   2048;
   const READASSIGN       =   4096;
   const ASSIGN           =   8192;
   const STEAL            =  16384;
   const OWN              =  32768;
   const CHANGEPRIORITY   =  65536;
   const SURVEY           = 131072;


   function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();

      if (!Session::haveRightsOr(self::$rightname, [DELETE, PURGE])) {
         $forbidden[] = 'delete';
         $forbidden[] = 'purge';
         $forbidden[] = 'restore';
      }
      return $forbidden;
   }

   static function getClassName() {
      return get_called_class();
   }

   static function getTable($classname = null) {
      return "glpi_tickets";
   }
   
   static function getTypeName($nb = 0) {
      return _n('Ticket', 'Tickets', $nb);
   }

   /**
  * Check right on an item with block
  *
  * @param integer $ID    ID of the item (-1 if new item)
  * @param mixed   $right Right to check : r / w / recursive
  * @param array   $input array of input data (used for adding item) (default NULL)
  *
  * @return void
  **/
   function check($ID, $right, array &$input = null) {
      // Check item exists
      if (!$this->isNewID($ID)
         && (!isset($this->fields['id']) || $this->fields['id'] != $ID)
         && !$this->getFromDB($ID)) {
      // Gestion timeout session
      Session::redirectIfNotLoggedIn();
      PluginServicesHtml::displayNotFoundError();

      } else {
      if (!$this->can($ID, $right, $input)) {
            // Gestion timeout session
            Session::redirectIfNotLoggedIn();
            PluginServicesHtml::displayRightError();
      }
      }
   }

   /**
  * @since 0.84
  *
  * @param $field
  * @param $name            (default '')
  * @param $values          (default '')
  * @param $options   array
  **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'content' :
            return "<textarea class='form-control' cols='90' rows='6' name='$name'>".$values['content']."</textarea>";

         case 'type':
            $options['value'] = $values[$field];
            return self::dropdownType($name, $options);
         case 'status' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return static::dropdownStatus($options);

         case 'impact' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return static::dropdownImpact($options);

         case 'urgency' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return static::dropdownUrgency($options);

         case 'priority' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return static::dropdownPriority($options);

         case 'global_validation' :
            $options['global'] = true;
            $options['value']  = $values[$field];
            return PluginServicesTicketValidation::dropdownStatus($name, $options);
      }
      return self::getSpecificValueToSelect($field, $name, $values, $options);
   }



   static function getSpecificValueName($field, $value = '') {
      switch ($field) {
         case 'type':
            return self::getTicketTypeName($value);
         case 'status' :
            return static::getStatus($value);
         case 'impact' :
            return static::getImpactName($value);
         case 'urgency' :
            return static::getUrgencyName($value);
         case 'priority' :
            return static::getPriorityName($value);
         case 'global_validation' :
            return PluginServicesTicketValidation::getStatus($value);
      }
      return $value;
   }
   
      /**
    * Dropdown of ticket type
    *
    * @param string $name     Select name
    * @param array  $options  Array of options:
    *    - value     : integer / preselected value (default 0)
    *    - toadd     : array / array of specific values to add at the beginning
    *    - on_change : string / value to transmit to "onChange"
    *    - display   : boolean / display or get string (default true)
    *
    * @return string id of the select
   **/
   static function dropdownType($name, $options = []) {

      $params = [
         'value'     => 0,
         'toadd'     => [],
         'on_change' => '',
         'display'   => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $items = [];
      if (count($params['toadd']) > 0) {
         $items = $params['toadd'];
      }

      $items += self::getTypes();

      return PluginServicesDropdown::showFromArray($name, $items, $params);
   }

   /**
    * Dropdown of object status
    *
    * @since 0.84 new proto
    *
    * @param $options   array of options
    *  - name     : select name (default is status)
    *  - value    : default value (default self::INCOMING)
    *  - showtype : list proposed : normal, search or allowed (default normal)
    *  - display  : boolean if false get string
    *
    * @return string|integer Output string if display option is set to false,
    *                        otherwise random part of dropdown id
   **/
   static function dropdownStatus(array $options = []) {

      $p = [
         'name'     => 'status',
         'showtype' => 'normal',
         'display'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if (!isset($p['value']) || empty($p['value'])) {
         $p['value']     = self::INCOMING;
      }

      switch ($p['showtype']) {
         case 'allowed' :
            $tab = static::getAllowedStatusArray($p['value']);
            break;

         case 'search' :
            $tab = static::getAllStatusArray(true);
            break;

         default :
            $tab = static::getAllStatusArray(false);
            break;
      }

      return PluginServicesDropdown::showFromArray($p['name'], $tab, $p);
   }


   /**
    * Dropdown of ITIL object Impact
    *
    * @since 0.84 new proto
    *
    * @param $options   array of options
    *  - name     : select name (default is impact)
    *  - value    : default value (default 0)
    *  - showtype : list proposed : normal, search (default normal)
    *  - display  : boolean if false get string
    *
    * \
    * @return string id of the select
   **/
   static function dropdownImpact(array $options = []) {
      global $CFG_GLPI;

      $p = [
         'name'     => 'impact',
         'value'    => 0,
         'showtype' => 'normal',
         'display'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $values = [];

      if ($p['showtype'] == 'search') {
         $values[0]  = static::getImpactName(0);
         $values[-5] = static::getImpactName(-5);
         $values[-4] = static::getImpactName(-4);
         $values[-3] = static::getImpactName(-3);
         $values[-2] = static::getImpactName(-2);
         $values[-1] = static::getImpactName(-1);
      }

      if (isset($CFG_GLPI[static::IMPACT_MASK_FIELD])) {
         if (($p['showtype'] == 'search')
            || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1<<5))) {
            $values[5]  = static::getImpactName(5);
         }

         if (($p['showtype'] == 'search')
            || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1<<4))) {
            $values[4]  = static::getImpactName(4);
         }

         $values[3]  = static::getImpactName(3);

         if (($p['showtype'] == 'search')
            || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1<<2))) {
            $values[2]  = static::getImpactName(2);
         }

         if (($p['showtype'] == 'search')
            || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1<<1))) {
            $values[1]  = static::getImpactName(1);
         }
      }

      return PluginServicesDropdown::showFromArray($p['name'], $values, $p);
   }
   
      /**
    * Dropdown of ITIL object Urgency
    *
    * @since 0.84 new proto
    *
    * @param $options array of options
    *       - name     : select name (default is urgency)
    *       - value    : default value (default 0)
    *       - showtype : list proposed : normal, search (default normal)
    *       - display  : boolean if false get string
    *
    * @return string id of the select
   **/
   static function dropdownUrgency(array $options = []) {
      global $CFG_GLPI;

      $p = [
         'name'     => 'urgency',
         'value'    => 0,
         'showtype' => 'normal',
         'display'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $values = [];

      if ($p['showtype'] == 'search') {
         $values[0]  = static::getUrgencyName(0);
         $values[-5] = static::getUrgencyName(-5);
         $values[-4] = static::getUrgencyName(-4);
         $values[-3] = static::getUrgencyName(-3);
         $values[-2] = static::getUrgencyName(-2);
         $values[-1] = static::getUrgencyName(-1);
      }

      if (isset($CFG_GLPI[static::URGENCY_MASK_FIELD])) {
         if (($p['showtype'] == 'search')
            || ($CFG_GLPI[static::URGENCY_MASK_FIELD] & (1<<5))) {
            $values[5]  = static::getUrgencyName(5);
         }

         if (($p['showtype'] == 'search')
            || ($CFG_GLPI[static::URGENCY_MASK_FIELD] & (1<<4))) {
            $values[4]  = static::getUrgencyName(4);
         }

         $values[3]  = static::getUrgencyName(3);

         if (($p['showtype'] == 'search')
            || ($CFG_GLPI[static::URGENCY_MASK_FIELD] & (1<<2))) {
            $values[2]  = static::getUrgencyName(2);
         }

         if (($p['showtype'] == 'search')
            || ($CFG_GLPI[static::URGENCY_MASK_FIELD] & (1<<1))) {
            $values[1]  = static::getUrgencyName(1);
         }
      }

      return PluginServicesDropdown::showFromArray($p['name'], $values, $p);
   }

      /**
    * Dropdown of ITIL object priority
    *
    * @since  version 0.84 new proto
    *
    * @param $options array of options
    *       - name     : select name (default is urgency)
    *       - value    : default value (default 0)
    *       - showtype : list proposed : normal, search (default normal)
    *       - wthmajor : boolean with major priority ?
    *       - display  : boolean if false get string
    *
    * @return string id of the select
   **/
   static function dropdownPriority(array $options = []) {

      $p = [
         'name'      => 'priority',
         'value'     => 0,
         'showtype'  => 'normal',
         'display'   => true,
         'withmajor' => false,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $values = [];

      if ($p['showtype'] == 'search') {
         $values[0]  = static::getPriorityName(0);
         $values[-5] = static::getPriorityName(-5);
         $values[-4] = static::getPriorityName(-4);
         $values[-3] = static::getPriorityName(-3);
         $values[-2] = static::getPriorityName(-2);
         $values[-1] = static::getPriorityName(-1);
      }

      if (($p['showtype'] == 'search')
         || $p['withmajor']) {
         $values[6] = static::getPriorityName(6);
      }
      $values[5] = static::getPriorityName(5);
      $values[4] = static::getPriorityName(4);
      $values[3] = static::getPriorityName(3);
      $values[2] = static::getPriorityName(2);
      $values[1] = static::getPriorityName(1);

      return PluginServicesDropdown::showFromArray($p['name'], $values, $p);
   }


   function getValueToSelect($field_id_or_search_options, $name = '', $values = '', $options = []) {
      global $CFG_GLPI;

      $param = [
         'comments' => false,
         'html'     => false,
      ];
      foreach ($param as $key => $val) {
            if (!isset($options[$key])) {
            $options[$key] = $val;
            }
      }

      $searchoptions = [];
      if (is_array($field_id_or_search_options)) {
            $searchoptions = $field_id_or_search_options;
      } else {
            $searchopt = $this->searchOptions();

            // Get if id of search option is passed
            if (is_numeric($field_id_or_search_options)) {
            if (isset($searchopt[$field_id_or_search_options])) {
                  $searchoptions = $searchopt[$field_id_or_search_options];
            }
            } else { // Get if field name is passed
            $searchoptions = $this->getSearchOptionByField('field', $field_id_or_search_options,
                                                            $this->getTable());
            }
      }
      if (count($searchoptions)) {
         $field = $searchoptions['field'];
         // Normalize option
         if (is_array($values)) {
            $value = $values[$field];
         } else {
            $value  = $values;
            $values = [$field => $value];
         }

         if (empty($name)) {
            $name = $searchoptions['linkfield'];
         }
         // If not set : set to specific
         if (!isset($searchoptions['datatype'])) {
            $searchoptions['datatype'] = 'specific';
         }

         $options['display'] = false;

         if (isset($options[$searchoptions['table'].'.'.$searchoptions['field']])) {
            $options = array_merge($options,
                                 $options[$searchoptions['table'].'.'.$searchoptions['field']]);
         }

            switch ($searchoptions['datatype']) {
            case "count" :
            case "number" :
            case "integer" :
               $copytooption = ['min', 'max', 'step', 'toadd', 'unit'];
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return PluginServicesDropdown::showNumber($name, $options);

            case "decimal" :
            case "mac" :
            case "ip" :
            case "string" :
            case "email" :
            case "weblink" :
               $this->fields[$name] = $value;
               return PluginServicesHtml::autocompletionTextField($this, $name, $options);

            case "text" :
               $out = '';
               if (isset($searchoptions['htmltext']) && $searchoptions['htmltext']) {
                  $out = PluginServicesHtml::initEditorSystem($name, '', false);
               }
               return $out."<textarea class='form-control' cols='45' rows='5' name='$name'>$value</textarea>";

            case "bool" :
            //   $options['width'] = '200px';
               return PluginServicesDropdown::showYesNo($name, $value, -1, $options);

            case "color" :
               return PluginServicesHtml::showColorField($name, $options);

            case "date" :
            case "date_delay" :
               if (isset($options['relative_dates']) && $options['relative_dates']) {
                  if (isset($searchoptions['maybefuture']) && $searchoptions['maybefuture']) {
                     $options['with_future'] = true;
                  }
                  return PluginServicesHtml::showGenericDateTimeSearch($name, $value, $options);
               }
               $copytooption = ['min', 'max', 'maybeempty', 'showyear'];
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return PluginServicesHtml::showDateField($name, $options);

            case "datetime" :
               if (isset($options['relative_dates']) && $options['relative_dates']) {
                  if (isset($searchoptions['maybefuture']) && $searchoptions['maybefuture']) {
                     $options['with_future'] = true;
                  }
                  $options['with_time'] = true;
                  return PluginServicesHtml::showGenericDateTimeSearch($name, $value, $options);
               }
               $copytooption = ['mindate', 'maxdate', 'mintime', 'maxtime',
                                       'maybeempty', 'timestep'];
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return PluginServicesHtml::showDateTimeField($name, $options);

            case "timestamp" :
               $copytooption = ['addfirstminutes', 'emptylabel', 'inhours',  'max', 'min',
                                       'step', 'toadd', 'display_emptychoice'];
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return PluginServicesDropdown::showTimeStamp($name, $options);

            case "itemlink" :
               // Do not use dropdown if wanted to select string value instead of ID
               if (isset($options['itemlink_as_string']) && $options['itemlink_as_string']) {
                  break;
               }

            case "dropdown" :
               $copytooption     = ['condition', 'displaywith', 'emptylabel',
                                          'right', 'toadd'];
               $options['name']  = $name;
               $options['value'] = $value;
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               if (!isset($options['entity'])) {
                  $options['entity'] = $_SESSION['glpiactiveentities'];
               }
               $itemtype = getItemTypeForTable($searchoptions['table']);
                  return PluginServicesDropdown::showForSearchCriteria($itemtype, $options);
            case "right" :
                  return Profile::dropdownRights(Profile::getRightsFor($searchoptions['rightclass']),
                                                $name, $value, ['multiple' => false,
                                                                     'display'  => false]);

            case "itemtypename" :
               if (isset($searchoptions['itemtype_list'])) {
                  $options['types'] = $CFG_GLPI[$searchoptions['itemtype_list']];
               }
               $copytooption     = ['types'];
               $options['value'] = $value;
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               if (isset($options['types'])) {
                  return PluginServicesDropdown::showItemTypes($name, $options['types'],
                                                   $options);
               }
               return false;

            case "language" :
               $copytooption = ['emptylabel', 'display_emptychoice'];
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return PluginServicesDropdown::showLanguages($name, $options);

         }
         // Get specific display if available
         $itemtype = getItemTypeForTable($searchoptions['table']);
      
            if ($item = getItemForItemtype($itemtype)) {
            $specific = self::getSpecificValueToSelect($searchoptions['field'], $name, $values, $options);
            // $specific = $item->getSpecificValueToSelect($searchoptions['field'], $name,
            //                                              $values, $options);
            if (strlen($specific)) {
               return $specific;
            }
         }
      }
      // default case field text
      $this->fields[$name] = $value;
      return PluginServicesHtml::autocompletionTextField($this, $name, $options);
   }

      /**
    * Displays the timeline of items for this ITILObject
    *
    * @since 9.4.0
    *
    * @param integer $rand random value used by div
    *
    * @return void
    */
   function showTimelineFago($param=[], $rand) {
      global $DB, $CFG_GLPI, $autolink_options;

      $user              = new PluginServicesUser();
      $group             = new PluginServicesGroup();
      $pics_url          = $CFG_GLPI['root_doc']."/pics/timeline";
      $timeline          = $this->getTimelineItems();

      $autolink_options['strip_protocols'] = false;

      $objType = static::getType();
      $foreignKey = static::getForeignKeyField();

      //display timeline
      echo "<div class='timeline_history'>";

      $followup_class    = 'PluginServicesITILFollowup';
      $followup_obj      = new $followup_class();
      $followup_obj->getEmpty();
      $followup_obj->fields['itemtype'] = $objType;

      // show approbation form on top when ticket/change is solved
      if ($this->fields["status"] == CommonITILObject::SOLVED) {
         echo "<div class='approbation_form' id='approbation_form$rand'>";
         $followup_obj->showApprobationForm($this);
         echo "</div>";
      }

      // show title for timeline
      // $this->showTimelineHeader();

      $timeline_index = 0;
      foreach ($timeline as $item) {
         // display Solution only
         if (isset($param['solution_only']) && $param['solution_only']) {
            if ($item['type'] !== 'Solution') {
               continue;
            }
         }
         // display documents only
         if (isset($param['document_only']) && $param['document_only']) {
            if ($item['type'] !== 'Document_Item') {
               continue;
            }
         }

         // display task only
         if (isset($param['task_only']) && $param['task_only']) {
            if ($item['type'] !== 'PluginAssistancesTicketTask') {
               continue;
            }
         }

         $options = [ 'parent' => $this,
                           'rand' => $rand
                           ];
         if ($obj = getItemForItemtype($item['type'])) {
            $obj->fields = $item['item'];
         } else {
            $obj = $item;
         }
         Plugin::doHook('pre_show_item', ['item' => $obj, 'options' => &$options]);

         if (is_array($obj)) {
            $item_i = $obj['item'];
         } else {
            $item_i = $obj->fields;
         }

         $date = "";
         if (isset($item_i['date'])) {
            $date = $item_i['date'];
         } else if (isset($item_i['date_mod'])) {
            $date = $item_i['date_mod'];
         }

         // set item position depending on field timeline_position
         $user_position = 'left'; // default position

         //display solution in middle
         if (($item['type'] == "Solution") && $item_i['status'] != CommonITILValidation::REFUSED
               && in_array($this->fields["status"], [CommonITILObject::SOLVED, CommonITILObject::CLOSED])) {
            $user_position.= ' middle';
         }

         echo "<div class='h_item $user_position'>";

         echo "<div class='h_info'>";

         echo "<div class='h_date'><i class='far fa-clock'></i>".Html::convDateTime($date)."</div>";
         if ($item_i['users_id'] !== false) {
            echo "<div class='h_user'>";
            if (isset($item_i['users_id']) && ($item_i['users_id'] != 0)) {
               $user->getFromDB($item_i['users_id']);

               echo "<div class='tooltip_picture_border'>";
               echo "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
                        User::getThumbnailURLForPicture($user->fields['picture'])."'>";
               echo "</div>";

               echo "<span class='h_user_name'>";
               $userdata = getUserName($item_i['users_id'], 2);
               $entity = $this->getEntityID();
               if (Entity::getUsedConfig('anonymize_support_agents', $entity)
                  && Session::getCurrentInterface() == 'helpdesk'
                  && (
                     $item['type'] == "Solution"
                     || is_subclass_of($item['type'], "CommonITILTask")
                     || ($item['type'] == "ITILFollowup"
                        && ITILFollowup::getById($item_i['id'])->isFromSupportAgent()
                     )
                     || ($item['type'] == "Document_Item"
                        && Document_Item::getById($item_i['documents_item_id'])->isFromSupportAgent()
                     )
                  )
               ) {
                  echo __("Helpdesk");
               } else {
                  echo $user->getLink()."&nbsp;";
                  // echo PluginServicesHtml::showToolTip(
                  //    $userdata["comment"],
                  //    ['link' => $userdata['link']]
                  // );
               }
               echo "</span>";
            } else {
               echo _n('Requester', 'Requesters', 1);
            }
            echo "</div>"; // h_user
         }

         echo "</div>"; //h_info

         $domid = "viewitem{$item['type']}{$item_i['id']}";
         if ($item['type'] == $objType.'Validation' && isset($item_i['status'])) {
            $domid .= $item_i['status'];
         }
         $randdomid = $domid . $rand;
         $domid = Toolbox::slugify($domid);

         $fa = null;
         $class = "h_content";
         if (isset($item['itiltype'])) {
            $class .= " ITIL{$item['itiltype']}";
         } else {
            $class .= " {$item['type']}";
         }
         if ($item['type'] == 'Solution') {
            switch ($item_i['status']) {
               case CommonITILValidation::WAITING:
                  $fa = 'question';
                  $class .= ' waiting';
                  break;
               case CommonITILValidation::ACCEPTED:
                  $fa = 'thumbs-up';
                  $class .= ' accepted';
                  break;
               case CommonITILValidation::REFUSED:
                  $fa = 'thumbs-down';
                  $class .= ' refused';
                  break;
            }
         } else if (isset($item_i['status'])) {
            $class .= " {$item_i['status']}";
         }

         echo "<div class='$class' id='$domid' data-uid='$randdomid'>";
         if ($fa !== null) {
            echo "<i class='solimg fa fa-$fa fa-5x'></i>";
         }
         if (isset($item_i['can_edit']) && $item_i['can_edit']) {
            echo "<div class='edit_item_content'></div>";
            echo "<span class='cancel_edit_item_content'></span>";
         }
         echo "<div class='displayed_content'>";
         echo "<div class='h_controls'>";
         if (!in_array($item['type'], ['Document_Item', 'Assign'])
            && $item_i['can_edit']
            && !in_array($this->fields['status'], $this->getClosedStatusArray())
         ) {
            // merge/split icon
            if ($objType == 'Ticket' && $item['type'] == ITILFollowup::getType()) {
               if (isset($item_i['sourceof_items_id']) && $item_i['sourceof_items_id'] > 0) {
                  echo PluginServicesHtml::link('', Ticket::getFormURLWithID($item_i['sourceof_items_id']), [
                     'class' => 'fa fa-code-branch control_item disabled',
                     'title' => __('Followup was already promoted')
                  ]);
               } else {
                  echo PluginServicesHtml::link('', Ticket::getFormURL()."?_promoted_fup_id=".$item_i['id'], [
                     'class' => 'fa fa-code-branch control_item',
                     'title' => __('Promote to Ticket')
                  ]);
               }
            }
            // edit item
            echo "<span class='far fa-edit control_item' title='".__('Edit')."'";
            echo "onclick='javascript:viewEditSubitem".$this->fields['id']."$rand(event, \"".$item['type']."\", ".$item_i['id'].", this, \"$randdomid\")'";
            echo "></span>";
         }

         // show "is_private" icon
         if (isset($item_i['is_private']) && $item_i['is_private']) {
            echo "<span class='private'><i class='fas fa-lock control_item' title='" . __s('Private') .
               "'></i><span class='sr-only'>".__('Private')."</span></span>";
         }

         echo "</div>";
         if (isset($item_i['requesttypes_id'])
               && file_exists("$pics_url/".$item_i['requesttypes_id'].".png")) {
            echo "<img src='$pics_url/".$item_i['requesttypes_id'].".png' class='h_requesttype' />";
         }

         if (isset($item_i['content'])) {
            $content = $item_i['content'];
            $content = Toolbox::getHtmlToDisplay($content);
            $content = autolink($content, false);

            $long_text = "";
            if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";
            echo "<p>";
            if (isset($item_i['state'])) {
               $onClick = "onclick='change_task_state(".$item_i['id'].", this)'";
               if (!$item_i['can_edit']) {
                  $onClick = "style='cursor: not-allowed;'";
               }
               echo "<span class='state state_".$item_i['state']."'
                           $onClick
                           title='".Planning::getState($item_i['state'])."'>";
               echo "</span>";
            }
            echo "</p>";

            echo "<div class='rich_text_container'>";
            $richtext = PluginServicesHtml::setRichTextContent('', $content, '', true);
            $richtext = PluginServicesHtml::replaceImagesByGallery($richtext);
            echo $richtext;
            echo "</div>";

            if (!empty($long_text)) {
               echo "<p class='read_more'>";
               echo "<a class='read_more_button'>.....</a>";
               echo "</p>";
            }
            echo "</div>";
         }

         $entity = $this->getEntityID();
         echo "<div class='b_right'>";
         if (isset($item_i['solutiontypes_id']) && !empty($item_i['solutiontypes_id'])) {
            echo Dropdown::getDropdownName("glpi_solutiontypes", $item_i['solutiontypes_id'])."<br>";
         }
         if (isset($item_i['taskcategories_id']) && !empty($item_i['taskcategories_id'])) {
            echo Dropdown::getDropdownName("glpi_taskcategories", $item_i['taskcategories_id'])."<br>";
         }
         if (isset($item_i['requesttypes_id']) && !empty($item_i['requesttypes_id'])) {
            echo Dropdown::getDropdownName("glpi_requesttypes", $item_i['requesttypes_id'])."<br>";
         }

         if (isset($item_i['actiontime']) && !empty($item_i['actiontime'])) {
            echo "<span class='actiontime'>";
            echo PluginServicesHtml::timestampToString($item_i['actiontime'], false);
            echo "</span>";
         }
         if (isset($item_i['begin'])) {
            echo "<span class='planification'>";
            echo PluginServicesHtml::convDateTime($item_i["begin"]);
            echo " &rArr; ";
            echo PluginServicesHtml::convDateTime($item_i["end"]);
            echo "</span>";
         }
         if (isset($item_i['users_id_tech']) && ($item_i['users_id_tech'] > 0)) {
            echo "<div class='users_id_tech' id='users_id_tech_".$item_i['users_id_tech']."'>";
            $user->getFromDB($item_i['users_id_tech']);

            if (Entity::getUsedConfig('anonymize_support_agents', $entity)
               && Session::getCurrentInterface() == 'helpdesk'
            ) {
               echo __("Helpdesk");
            } else {
               echo "<i class='fas fa-user'></i> ";
               $userdata = getUserName($item_i['users_id_tech'], 2);
               echo $user->getLink()."&nbsp;";
               // echo PluginServicesHtml::showToolTip(
               //    $userdata["comment"],
               //    ['link' => $userdata['link']]
               // );
            }
            echo "</div>";
         }
         if (isset($item_i['groups_id_tech']) && ($item_i['groups_id_tech'] > 0)) {
            echo "<div class='groups_id_tech'>";
            $group->getFromDB($item_i['groups_id_tech']);
            echo "<i class='fas fa-users' aria-hidden='true'></i>&nbsp;";
            echo $group->getLink();
            echo "</div>";
         }
         if (isset($item_i['users_id_editor']) && $item_i['users_id_editor'] > 0) {
            echo "<div class='users_id_editor' id='users_id_editor_".$item_i['users_id_editor']."'>";

            if (Entity::getUsedConfig('anonymize_support_agents', $entity)
               && Session::getCurrentInterface() == 'helpdesk'
            ) {
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  PluginServicesHtml::convDateTime($item_i['date_mod']),
                  __("Helpdesk")
               );
            } else {
               $user->getFromDB($item_i['users_id_editor']);
               $userdata = getUserName($item_i['users_id_editor'], 2);
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  PluginServicesHtml::convDateTime($item_i['date_mod']),
                  $user->getLink()
               );
               // echo PluginServicesHtml::showToolTip($userdata["comment"],
               //                        ['link' => $userdata['link']]);
            }

            echo "</div>";
         }
         if ($objType == 'Ticket' && isset($item_i['sourceitems_id']) && $item_i['sourceitems_id'] > 0) {
            echo "<div id='sourceitems_id_".$item_i['sourceitems_id']."'>";
            echo sprintf(
               __('Merged from Ticket %1$s'),
               PluginServicesHtml::link($item_i['sourceitems_id'], Ticket::getFormURLWithID($item_i['sourceitems_id']))
            );
            echo "</div>";
         }
         if ($objType == 'Ticket' && isset($item_i['sourceof_items_id']) && $item_i['sourceof_items_id'] > 0) {
            echo "<div id='sourceof_items_id_".$item_i['sourceof_items_id']."'>";
            echo sprintf(
               __('Promoted to Ticket %1$s'),
               PluginServicesHtml::link($item_i['sourceof_items_id'], Ticket::getFormURLWithID($item_i['sourceof_items_id']))
            );
            echo "</div>";
         }
         if (strpos($item['type'], 'Validation') > 0 &&
            (isset($item_i['can_answer']) && $item_i['can_answer'])) {
            $form_url = $item['type']::getFormURL();
            echo "<form id='validationanswers_id_{$item_i['id']}' class='center' action='$form_url' method='post'>";
            echo PluginServicesHtml::hidden('id', ['value' => $item_i['id']]);
            echo PluginServicesHtml::hidden('users_id_validate', ['value' => $item_i['users_id_validate']]);
            PluginServicesHtml::textarea([
               'name'   => 'comment_validation',
               'rows'   => 5
            ]);
            echo "<div class='d-flex justify-content-center mt-1'>";
               echo "<button type='submit' class='submit approve' name='approval_action' value='approve'>";
               echo "<i class='far fa-thumbs-up'></i>&nbsp;&nbsp;".__('Approve')."</button>&nbsp;&nbsp;&nbsp;";

               echo "<button type='submit' class='submit refuse very_small_space' name='approval_action' value='refuse'>";
               echo "<i class='far fa-thumbs-down'></i>&nbsp;&nbsp;".__('Refuse')."</button>";
            echo"</div>";
            PluginServicesHtml::closeForm();
         }
         if ($item['type'] == 'Solution' && $item_i['status'] != CommonITILValidation::WAITING && $item_i['status'] != CommonITILValidation::NONE) {
            echo "<div class='users_id_approval' id='users_id_approval_".$item_i['users_id_approval']."'>";
            $user->getFromDB($item_i['users_id_approval']);
            $userdata = getUserName($item_i['users_id_editor'], 2);
            $message = __('%1$s on %2$s by %3$s');
            $action = $item_i['status'] == CommonITILValidation::ACCEPTED ? __('Accepted') : __('Refused');
            echo sprintf(
               $message,
               $action,
               PluginServicesHtml::convDateTime($item_i['date_approval']),
               $user->getLink()
            );
            // echo PluginServicesHtml::showToolTip($userdata["comment"],
            //                        ['link' => $userdata['link']]);
            echo "</div>";
         }

         echo "</div>"; // b_right

         if ($item['type'] == 'Document_Item') {
            if ($item_i['filename']) {
               $filename = $item_i['filename'];
               $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
               echo "<img src='";
               if (empty($filename)) {
                  $filename = $item_i['name'];
               }
               if (file_exists(GLPI_ROOT."/pics/icones/$ext-dist.png")) {
                  echo $CFG_GLPI['root_doc']."/pics/icones/$ext-dist.png";
               } else {
                  echo "$pics_url/file.png";
               }
               echo "'/>&nbsp;";

               $docsrc = $CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                        ."&$foreignKey=".$this->getID();
               echo PluginServicesHtml::link($filename, $docsrc, ['target' => '_blank']);
               $docpath = GLPI_DOC_DIR . '/' . $item_i['filepath'];
               if (Document::isImage($docpath)) {
                  $imgsize = getimagesize($docpath);
                  echo PluginServicesHtml::imageGallery([
                     [
                        'src'             => $docsrc,
                        'thumbnail_src'   => $docsrc . '&context=timeline',
                        'w'               => $imgsize[0],
                        'h'               => $imgsize[1]
                     ]
                  ], [
                     'gallery_item_class' => 'timeline_img_preview'
                  ]);
               }
            }
            if ($item_i['link']) {
               echo "<a href='{$item_i['link']}' target='_blank'><i class='fa fa-external-link'></i>{$item_i['name']}</a>";
            }
            if (!empty($item_i['mime'])) {
               echo "&nbsp;(".$item_i['mime'].")";
            }
            echo "<span class='buttons'>";
            // echo "<a href='".Document::getFormURLWithID($item_i['id'])."' class='edit_document fa fa-eye pointer' title='".
            //        _sx("button", "Show")."'>";
            // echo "<span class='sr-only'>" . _sx('button', 'Show') . "</span></a>";

            $doc = new Document();
            $doc->getFromDB($item_i['id']);
            if ($doc->can($item_i['id'], UPDATE)) {
               echo "<a href='".Ticket::getFormURL().
                     "?delete_document&documents_id=".$item_i['id'].
                     "&$foreignKey=".$this->getID()."' class='delete_document fas fa-trash-alt pointer' title='".
                     _sx("button", "Delete permanently")."'>";
               echo "<span class='sr-only'>" . _sx('button', 'Delete permanently')  . "</span></a>";
            }
            echo "</span>";
         }

         echo "</div>"; // displayed_content
         echo "</div>"; //end h_content

         echo "</div>"; //end  h_info

         $timeline_index++;

         Plugin::doHook('post_show_item', ['item' => $obj, 'options' => $options]);

      } // end foreach timeline

      echo "<div class='break'></div>";

      // recall content
      echo "<div class='h_item middle'>";

      echo "<div class='h_info'>";
         echo "<div class='h_date'><i class='far fa-clock'></i>".PluginServicesHtml::convDateTime($this->fields['date'])."</div>";
            echo "<div class='h_user'>";

               $user = new User();
               $display_requester = false;
               $requesters = $this->getUsers(CommonITILActor::REQUESTER);
               if (count($requesters) === 1) {
                  $requester = reset($requesters);
                  if ($requester['users_id'] > 0) {
                     // Display requester identity only if there is only one requester
                     // and only if it is not an anonymous user
                     $display_requester = $user->getFromDB($requester['users_id']);
                  }
               }

               echo "<div class='tooltip_picture_border'>";
                  $picture = "";
                  if ($display_requester && isset($user->fields['picture'])) {
                     $picture = $user->fields['picture'];
                  }
                  echo "<img class='user_picture' alt=\"".__s('Picture')."\" src='".User::getThumbnailURLForPicture($picture)."'>";
               echo "</div>";

               if ($display_requester) {
                  echo $user->getLink()."&nbsp;";
                  $reqdata = getUserName($user->getID(), 2);
                  // echo PluginServicesHtml::showToolTip(
                  //    $reqdata["comment"],
                  //    ['link' => $reqdata['link']]
                  // );
               } else {
                  echo _n('Requester', 'Requesters', count($requesters));
               }

            echo "</div>"; // h_user
         echo "</div>"; //h_info

         echo "<div class='h_content ITILContent'>";
            echo "<div class='displayed_content'>";
               echo "<div class='b_right'>";
                  if ($objType == 'Ticket') {
                     $result = $DB->request([
                        'SELECT' => ['id', 'itemtype', 'items_id'],
                        'FROM'   => ITILFollowup::getTable(),
                        'WHERE'  => [
                           'sourceof_items_id'  => $this->fields['id'],
                           'itemtype'           => static::getType()
                        ]
                     ])->next();
                     if ($result) {
                        echo PluginServicesHtml::link(
                           '',
                           static::getFormURLWithID($result['items_id']) . '&forcetab=Ticket$1#viewitemitilfollowup' . $result['id'], [
                              'class' => 'fa fa-code-branch control_item disabled',
                              'title' => __('Followup promotion source')
                           ]
                        );
                     }
                  }
                  echo sprintf(__($objType."# %s description"), $this->getID());
               echo "</div>";

               echo "<div class='title'>";
                  echo PluginServicesHtml::setSimpleTextContent($this->fields['name']);
               echo "</div>";

               echo "<div class='rich_text_container'>";
                  $richtext = PluginServicesHtml::setRichTextContent('', $this->fields['content'], '', true);
                  $richtext = PluginServicesHtml::replaceImagesByGallery($richtext);
                  echo $richtext;
               echo "</div>";

            echo "</div>"; // h_content ITILContent

            echo "</div>"; // .displayed_content
            echo "</div>"; // h_item middle

            echo "<div class='break'></div>";

            // end timeline
         echo "</div>"; // h_item $user_position
      echo "<script type='text/javascript'>$(function() {read_more();});</script>";
   }


   /**
    * Retrieves all timeline items for this ITILObject
    *
    * @since 9.4.0
    *
    * @return mixed[] Timeline items
    */
   function getTimelineItems() {

      $objType = 'Ticket';
      $foreignKey = static::getForeignKeyField();
      $supportsValidation = $objType === "Ticket" || $objType === "Change";

      $timeline = [];

      $user = new PluginServicesUser();

      $fupClass           = 'ITILFollowup';
      $followup_obj       = new $fupClass;
      $taskClass             = $objType."Task";
      $task_obj              = new $taskClass;
      $document_item_obj     = new Document_Item();
      if ($supportsValidation) {
         $validation_class    = $objType."Validation";
         $valitation_obj     = new $validation_class;
      }

      //checks rights
      $restrict_fup = $restrict_task = [];
      if (!Session::haveRight("followup", ITILFollowup::SEEPRIVATE)) {
         $restrict_fup = [
            'OR' => [
               'is_private'   => 0,
               'users_id'     => Session::getLoginUserID()
            ]
         ];
      }

      $restrict_fup['itemtype'] = 'Ticket';
      $restrict_fup['items_id'] = $this->getID();

      if ($task_obj->maybePrivate() && !Session::haveRight("task", CommonITILTask::SEEPRIVATE)) {
         $restrict_task = [
            'OR' => [
               'is_private'   => 0,
               'users_id'     => Session::getCurrentInterface() == "central"
                                    ? Session::getLoginUserID()
                                    : 0
            ]
         ];
      }

      //add followups to timeline
      if ($followup_obj->canview()) {
         $followups = $followup_obj->find(['items_id'  => $this->getID()] + $restrict_fup, ['date DESC', 'id DESC']);
         foreach ($followups as $followups_id => $followup) {
            $followup_obj->getFromDB($followups_id);
            $followup['can_edit']                                   = $followup_obj->canUpdateItem();;
            $timeline[$followup['date']."_followup_".$followups_id] = ['type' => $fupClass,
                                                                           'item' => $followup,
                                                                           'itiltype' => 'Followup'];
         }
      }

      //add tasks to timeline
      if ($task_obj->canview()) {
         $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task, 'date DESC');
         foreach ($tasks as $tasks_id => $task) {
            $task_obj->getFromDB($tasks_id);
            $task['can_edit']                           = $task_obj->canUpdateItem();
            $timeline[$task['date']."_task_".$tasks_id] = ['type' => $taskClass,
                                                               'item' => $task,
                                                               'itiltype' => 'Task'];
         }
      }

      //add documents to timeline
      $document_obj   = new Document();
      $document_items = $document_item_obj->find([
         $this->getAssociatedDocumentsCriteria(),
         'timeline_position'  => ['>', self::NO_TIMELINE]
      ]);
      foreach ($document_items as $document_item) {
         $document_obj->getFromDB($document_item['documents_id']);

         $item = $document_obj->fields;
         $item['date']     = $document_item['date_creation'];
         // #1476 - set date_mod and owner to attachment ones
         $item['date_mod'] = $document_item['date_mod'];
         $item['users_id'] = $document_item['users_id'];
         $item['documents_item_id'] = $document_item['id'];

         $item['timeline_position'] = $document_item['timeline_position'];

         $timeline[$document_item['date_creation']."_document_".$document_item['documents_id']]
            = ['type' => 'Document_Item', 'item' => $item];
      }

      $solution_obj = new ITILSolution();
      $solution_items = $solution_obj->find([
         'itemtype'  => static::getType(),
         'items_id'  => $this->getID()
      ]);
      foreach ($solution_items as $solution_item) {
         // fix trouble with html_entity_decode who skip accented characters (on windows browser)
         $solution_content = preg_replace_callback("/(&#[0-9]+;)/", function($m) {
            return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
         }, $solution_item['content']);

         $timeline[$solution_item['date_creation']."_solution_" . $solution_item['id'] ] = [
            'type' => 'Solution',
            'item' => [
               'id'                 => $solution_item['id'],
               'content'            => Toolbox::unclean_cross_side_scripting_deep($solution_content),
               'date'               => $solution_item['date_creation'],
               'users_id'           => $solution_item['users_id'],
               'solutiontypes_id'   => $solution_item['solutiontypes_id'],
               'can_edit'           => $objType::canUpdate() && $this->canSolve(),
               'timeline_position'  => self::TIMELINE_RIGHT,
               'users_id_editor'    => $solution_item['users_id_editor'],
               'date_mod'           => $solution_item['date_mod'],
               'users_id_approval'  => $solution_item['users_id_approval'],
               'date_approval'      => $solution_item['date_approval'],
               'status'             => $solution_item['status']
            ]
         ];
      }

      if ($supportsValidation and $validation_class::canView()) {
         $validations = $valitation_obj->find([$foreignKey => $this->getID()]);
         foreach ($validations as $validations_id => $validation) {
            $canedit = $valitation_obj->can($validations_id, UPDATE);
            $cananswer = ($validation['users_id_validate'] === Session::getLoginUserID() &&
               $validation['status'] == CommonITILValidation::WAITING);
            $user->getFromDB($validation['users_id_validate']);
            $timeline[$validation['submission_date']."_validation_".$validations_id] = [
               'type' => $validation_class,
               'item' => [
                  'id'        => $validations_id,
                  'date'      => $validation['submission_date'],
                  'content'   => __('Validation request')." => ".$user->getlink().
                                                "<br>".$validation['comment_submission'],
                  'users_id'  => $validation['users_id'],
                  'can_edit'  => $canedit,
                  'can_answer'   => $cananswer,
                  'users_id_validate'  => $validation['users_id_validate'],
                  'timeline_position' => $validation['timeline_position']
               ],
               'itiltype' => 'Validation'
            ];

            if (!empty($validation['validation_date'])) {
               $timeline[$validation['validation_date']."_validation_".$validations_id] = [
                  'type' => $validation_class,
                  'item' => [
                     'id'        => $validations_id,
                     'date'      => $validation['validation_date'],
                     'content'   => __('Validation request answer')." : ". _sx('status',
                                                   ucfirst($validation_class::getStatus($validation['status'])))
                                                   ."<br>".$validation['comment_validation'],
                     'users_id'  => $validation['users_id_validate'],
                     'status'    => "status_".$validation['status'],
                     'can_edit'  => $canedit,
                     'timeline_position' => $validation['timeline_position']
                  ],
                  'itiltype' => 'Validation'
               ];
            }
         }
      }

      //reverse sort timeline items by key (date)
      krsort($timeline);

      return $timeline;
   }


   function showTimelineFormFago($param=[], $rand) {
      global $CFG_GLPI, $DB;

      $objType = 'Ticket';
      $foreignKey = static::getForeignKeyField();

      //check sub-items rights
      $tmp = [$foreignKey => $this->getID()];
      $fupClass = "ITILFollowup";
      $fup = new $fupClass;
      $fup->getEmpty();
      $fup->fields['itemtype'] = $objType;
      $fup->fields['items_id'] = $this->getID();

      $taskClass = $objType."Task";
      $task = new $taskClass;

      $canadd_fup = $fup->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                        array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                         array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_document = $canadd_fup || $this->canAddItem('Document') && !in_array($this->fields["status"],
                         array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_solution = $objType::canUpdate() && $this->canSolve() && !in_array($this->fields["status"], $this->getSolvedStatusArray());

      $validation_class = $objType.'Validation';
      $canadd_validation = false;
      if (class_exists($validation_class)) {
         $validation = new $validation_class();
         $canadd_validation = $validation->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
               array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      }

      // javascript function for add and edit items
      echo "<script type='text/javascript' >
         function change_task_state(tasks_id, target) {
            $.post('".$CFG_GLPI["root_doc"]."/ajax/timeline.php',
                  {'action':     'change_task_state',
                     'tasks_id':   tasks_id,
                     'parenttype': '$objType',
                     '$foreignKey': ".$this->fields['id']."
                  })
                  .done(function(response) {
                     $(target).removeClass('state_1 state_2')
                              .addClass('state_'+response.state)
                              .attr('title', response.label);
                  });
         }

         function viewEditSubitem" . $this->fields['id'] . "$rand(e, itemtype, items_id, o, domid) {
                  domid = (typeof domid === 'undefined')
                           ? 'viewitem".$this->fields['id'].$rand."'
                           : domid;
                  var target = e.target || window.event.srcElement;
                  if (target.nodeName == 'a') return;
                  if (target.className == 'read_more_button') return;

                  var _eltsel = '[data-uid='+domid+']';
                  var _elt = $(_eltsel);
                  _elt.addClass('edited');
                  $(_eltsel + ' .displayed_content').hide();
                  $(_eltsel + ' .cancel_edit_item_content').show()
                                                         .click(function() {
                                                               $(this).hide();
                                                               _elt.removeClass('edited');
                                                               $(_eltsel + ' .edit_item_content').empty().hide();
                                                               $(_eltsel + ' .displayed_content').show();
                                                         });
                  $(_eltsel + ' .edit_item_content').show()
                                                   .load('".$CFG_GLPI["root_doc"]."/ajax/timeline.php',
                                                         {'action'    : 'viewsubitem',
                                                         'type'      : itemtype,
                                                         'parenttype': '$objType',
                                                         '$foreignKey': ".$this->fields['id'].",
                                                         'id'        : items_id
                                                         });
         };
      </script>";

      if (!$canadd_fup && !$canadd_task && !$canadd_document && !$canadd_solution && !$this->canReopen()) {
         return false;
      }

      echo "<script type='text/javascript' >\n";
      echo "function viewAddSubitem" . $this->fields['id'] . "$rand(itemtype) {\n";
      $params = ['action'     => 'viewsubitem',
                      'type'       => 'itemtype',
                      'parenttype' => $objType,
                      $foreignKey => $this->fields['id'],
                      'id'         => -1];
      if (isset($_GET['load_kb_sol'])) {
         $params['load_kb_sol'] = $_GET['load_kb_sol'];
      }
      $out = Ajax::updateItemJsCode("viewitem" . $this->fields['id'] . "$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/timeline.php",
                                    $params, "", false);
      echo str_replace("\"itemtype\"", "itemtype", $out);
      echo "$('#approbation_form$rand').remove()";
      echo "};";

      if (isset($_GET['load_kb_sol'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('Solution');";
      }

      if (isset($_GET['_openfollowup'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('ITILFollowup')";
      }
      echo "</script>\n";
      $row_number = (isset($param['row_number'])) ?  $param['row_number'] : 12 ;
      echo"<div class='row d-flex justify-content-center  form-group m-form__group'>";
         echo "<div class='ajax_box col-lg-".$row_number."' id='viewitem" . $this->fields['id'] . "$rand'></div>\n";
      echo"</div>";

      if ((isset($param['task_only']) && $param['task_only']) && $canadd_task) {
         echo"<script>
            $( document ).ready(function() {viewAddSubitem".$this->fields['id']."$rand(\"$taskClass\");});
         </script>";
      }elseif ((isset($param['document_only']) && $param['document_only']) && $canadd_document) {
         echo"<script>
            $( document ).ready(function() {viewAddSubitem".$this->fields['id']."$rand(\"Document_Item\");});
         </script>";
      }elseif ((isset($param['validation_only']) && $param['validation_only']) && $canadd_validation) {
         echo"<script>
            $( document ).ready(function() {viewAddSubitem".$this->fields['id']."$rand(\"$validation_class\");});
         </script>";
      }elseif ((isset($param['solution_only']) && $param['solution_only']) && $canadd_solution) {
         echo"<script>
            $( document ).ready(function() {viewAddSubitem".$this->fields['id']."$rand(\"Solution\");});
         </script>";
      }else {
         if ($canadd_fup) {
            echo"<script>
            $( document ).ready(function() {viewAddSubitem".$this->fields['id']."$rand(\"ITILFollowup\");});
            </script>";
         }
      }
   }

   static function  countRequestNumberOfLoginUser(){
      return 0;
   }

   static function  countIncidentsNumberOfLoginUser(){
      return 0;
   }
   
      /**
    * Display a line for an object
    *
    * @since 0.85 (befor in each object with differents parameters)
    *
    * @param $id                 Integer  ID of the object
    * @param $options            array of options
    *      output_type            : Default output type (see Search class / default Search::HTML_OUTPUT)
    *      row_num                : row num used for display
    *      type_for_massiveaction : itemtype for massive action
    *      id_for_massaction      : default 0 means no massive action
    *      followups              : show followup columns
    */
   static function showShort($id, $options = []) {
      global $DB;

      $p = [
         'output_type'            => Search::HTML_OUTPUT,
         'row_num'                => 0,
         'type_for_massiveaction' => 0,
         'id_for_massiveaction'   => 0,
         'followups'              => false,
      ];

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $rand = mt_rand();

      /// TODO to be cleaned. Get datas and clean display links

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $item         = new static();

      $candelete   = static::canDelete();
      $canupdate   = Session::haveRight(static::$rightname, UPDATE);
      $showprivate = Session::haveRight('followup', ITILFollowup::SEEPRIVATE);
      $align       = "class='left'";
      $align_desc  = "class='left";

      if ($p['followups']) {
         $align      .= " top'";
         $align_desc .= " top'";
      } else {
         $align      .= "'";
         $align_desc .= "'";
      }

      if ($item->getFromDB($id)) {
         $item_num = 1;
         $bgcolor  = $_SESSION["glpipriority_".$item->fields["priority"]];

         echo Search::showNewLine($p['output_type'], $p['row_num']%2, $item->isDeleted());

         $check_col = '';
         if (($candelete || $canupdate)
             && ($p['output_type'] == Search::HTML_OUTPUT)
             && $p['id_for_massiveaction']) {

            $check_col = Html::getMassiveActionCheckBox($p['type_for_massiveaction'], $p['id_for_massiveaction']);
         }
         echo Search::showItem($p['output_type'], $check_col, $item_num, $p['row_num'], $align);

         // First column
         $first_col = sprintf(__('%1$s: %2$s'), __('ID'), $item->fields["id"]);
         if ($p['output_type'] == Search::HTML_OUTPUT) {
            $first_col .= "&nbsp;".static::getStatusIcon($item->fields["status"]);
         } else {
            $first_col = sprintf(__('%1$s - %2$s'), $first_col,
                                 static::getStatus($item->fields["status"]));
         }

         echo Search::showItem($p['output_type'], $first_col, $item_num, $p['row_num'], $align);

         // Second column
         if ($item->fields['status'] == static::CLOSED) {
            $second_col = sprintf(__('Closed on %s'),
                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
                                    Html::convDateTime($item->fields['closedate']));
         } else if ($item->fields['status'] == static::SOLVED) {
            $second_col = sprintf(__('Solved on %s'),
                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
                                    Html::convDateTime($item->fields['solvedate']));
         } else if ($item->fields['begin_waiting_date']) {
            $second_col = sprintf(__('Put on hold on %s'),
                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
                                    Html::convDateTime($item->fields['begin_waiting_date']));
         } else if ($item->fields['time_to_resolve']) {
            $second_col = sprintf(__('%1$s: %2$s'), __('Time to resolve'),
                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
                                    Html::convDateTime($item->fields['time_to_resolve']));
         } else {
            $second_col = sprintf(__('Opened on %s'),
                                  ($p['output_type'] == Search::HTML_OUTPUT?'<br>':'').
                                    Html::convDateTime($item->fields['date']));
         }

         echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align." width=130");

         // Second BIS column
         $second_col = Html::convDateTime($item->fields["date_mod"]);
         echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'], $align." width=90");

         // Second TER column
         if (count($_SESSION["glpiactiveentities"]) > 1) {
            $second_col = Dropdown::getDropdownName('glpi_entities', $item->fields['entities_id']);
            echo Search::showItem($p['output_type'], $second_col, $item_num, $p['row_num'],
                                  $align." width=100");
         }

         // Third Column
         echo Search::showItem($p['output_type'],
                               "<span class='b'>".static::getPriorityName($item->fields["priority"]).
                                 "</span>",
                               $item_num, $p['row_num'], "$align bgcolor='$bgcolor'");

         // Fourth Column
         $fourth_col = "";

         foreach ($item->getUsers(CommonITILActor::REQUESTER) as $d) {
            $userdata    = getUserName($d["users_id"], 2);
            $fourth_col .= sprintf(__('%1$s %2$s'),
                                    "<span class='b'>".$userdata['name']."</span>",
                                 '');
            $fourth_col .= "<br>";
         }

         foreach ($item->getGroups(CommonITILActor::REQUESTER) as $d) {
            $fourth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
            $fourth_col .= "<br>";
         }

         echo Search::showItem($p['output_type'], $fourth_col, $item_num, $p['row_num'], $align);

         // Fifth column
         $fifth_col = "";

         $entity = $item->getEntityID();
         $anonymize_helpdesk = Entity::getUsedConfig('anonymize_support_agents', $entity)
            && Session::getCurrentInterface() == 'helpdesk';

         foreach ($item->getUsers(CommonITILActor::ASSIGN) as $d) {
            if ($anonymize_helpdesk) {
               $fifth_col .= __("Helpdesk");
            } else {
               $userdata   = getUserName($d["users_id"], 2);
               $fifth_col .= sprintf(__('%1$s %2$s'),
                                    "<span class='b'>".$userdata['name']."</span>",
                                   '');
            }

            $fifth_col .= "<br>";
         }

         foreach ($item->getGroups(CommonITILActor::ASSIGN) as $d) {
            if ($anonymize_helpdesk) {
               $fifth_col .= __("Helpdesk group");
            } else {
               $fifth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
            }
            $fifth_col .= "<br>";
         }

         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $d) {
            $fifth_col .= Dropdown::getDropdownName("glpi_suppliers", $d["suppliers_id"]);
            $fifth_col .= "<br>";
         }

         echo Search::showItem($p['output_type'], $fifth_col, $item_num, $p['row_num'], $align);

         // Sixth Colum
         // Ticket : simple link to item
         $sixth_col  = "";
         $is_deleted = false;
         $item_ticket = new Item_Ticket();
         $data = $item_ticket->find(['tickets_id' => $item->fields['id']]);

         if ($item->getType() == 'Ticket') {
            if (!empty($data)) {
               foreach ($data as $val) {
                  if (!empty($val["itemtype"]) && ($val["items_id"] > 0)) {
                     if ($object = getItemForItemtype($val["itemtype"])) {
                        if ($object->getFromDB($val["items_id"])) {
                           $is_deleted = $object->isDeleted();

                           $sixth_col .= $object->getTypeName();
                           $sixth_col .= " - <span class='b'>";
                           if ($item->canView()) {
                              $sixth_col .= $object->getLink();
                           } else {
                              $sixth_col .= $object->getNameID();
                           }
                           $sixth_col .= "</span><br>";
                        }
                     }
                  }
               }
            } else {
               $sixth_col = __('General');
            }

            echo Search::showItem($p['output_type'], $sixth_col, $item_num, $p['row_num'], ($is_deleted ? " class='center deleted' " : $align));
         }

         // Seventh column
         echo Search::showItem($p['output_type'],
                               "<span class='b'>".
                                 Dropdown::getDropdownName('glpi_itilcategories',
                                                           $item->fields["itilcategories_id"]).
                               "</span>",
                               $item_num, $p['row_num'], $align);

         // Eigth column
         $eigth_column = "<span class='b'>".$item->getName()."</span>&nbsp;";

         // Add link
         if ($item->canViewItem()) {
            $eigth_column = "<a id='".$item->getType().$item->fields["id"]."$rand' href=\"".$item->getLinkURL()
                              ."\">$eigth_column</a>";

            if ($p['followups']
                && ($p['output_type'] == Search::HTML_OUTPUT)) {
               $eigth_column .= ITILFollowup::showShortForITILObject($item->fields["id"], static::class);
            } else {
               $eigth_column  = sprintf(
                  __('%1$s (%2$s)'),
                  $eigth_column,
                  sprintf(
                     __('%1$s - %2$s'),
                     $item->numberOfFollowups($showprivate),
                     $item->numberOfTasks($showprivate)
                  )
               );
            }
         }

         if ($p['output_type'] == Search::HTML_OUTPUT) {
            $eigth_column = sprintf(__('%1$s %2$s'), $eigth_column,
                                    Html::showToolTip(Html::clean(Html::entity_decode_deep($item->fields["content"])),
                                                      ['display' => false,
                                                            'applyto' => $item->getType().$item->fields["id"].
                                                                           $rand]));
         }

         echo Search::showItem($p['output_type'], $eigth_column, $item_num, $p['row_num'],
                               $align_desc." width='200'");

         //tenth column
         $tenth_column  = '';
         $planned_infos = '';

         $tasktype      = $item->getType()."Task";
         $plan          = new $tasktype();
         $items         = [];

         $result = $DB->request(
            [
               'FROM'  => $plan->getTable(),
               'WHERE' => [
                  $item->getForeignKeyField() => $item->fields['id'],
               ],
            ]
         );
         foreach ($result as $plan) {

            if (isset($plan['begin']) && $plan['begin']) {
               $items[$plan['id']] = $plan['id'];
               $planned_infos .= sprintf(__('From %s').
                                            ($p['output_type'] == Search::HTML_OUTPUT?'<br>':''),
                                         Html::convDateTime($plan['begin']));
               $planned_infos .= sprintf(__('To %s').
                                            ($p['output_type'] == Search::HTML_OUTPUT?'<br>':''),
                                         Html::convDateTime($plan['end']));
               if ($plan['users_id_tech']) {
                  $planned_infos .= sprintf(__('By %s').
                                               ($p['output_type'] == Search::HTML_OUTPUT?'<br>':''),
                                            getUserName($plan['users_id_tech']));
               }
               $planned_infos .= "<br>";
            }

         }

         $tenth_column = count($items);
         if ($tenth_column) {
            $tenth_column = "<span class='pointer'
                              id='".$item->getType().$item->fields["id"]."planning$rand'>".
                              $tenth_column.'</span>';
            $tenth_column = sprintf(__('%1$s %2$s'), $tenth_column,
                                    Html::showToolTip($planned_infos,
                                                      ['display' => false,
                                                            'applyto' => $item->getType().
                                                                           $item->fields["id"].
                                                                           "planning".$rand]));
         }
         echo Search::showItem($p['output_type'], $tenth_column, $item_num, $p['row_num'],
                               $align_desc." width='150'");

         // Finish Line
         echo Search::showEndLine($p['output_type']);
      } else {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='6' ><i>".__('No item in progress.')."</i></td></tr>";
      }
   }

   function rawSearchOptions() {
      global $DB;

      $tab = [];

      $tab = array_merge($tab, $this->getSearchOptionsMain());

      $tab[] = [
         'id'                 => '155',
         'table'              => $this->getTable(),
         'field'              => 'time_to_own',
         'name'               => __('Time to own'),
         'datatype'           => 'datetime',
         'maybefuture'        => true,
         'massiveaction'      => false,
         'additionalfields'   => ['status']
      ];

      $tab[] = [
         'id'                 => '158',
         'table'              => $this->getTable(),
         'field'              => 'time_to_own',
         'name'               => __('Time to own + Progress'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'additionalfields'   => ['status']
      ];

      $tab[] = [
         'id'                 => '159',
         'table'              => 'glpi_tickets',
         'field'              => 'is_late',
         'name'               => __('Time to own exceedeed'),
         'datatype'           => 'bool',
         'massiveaction'      => false,
         'computation'        => self::generateSLAOLAComputation('time_to_own')
      ];

      $tab[] = [
         'id'                 => '180',
         'table'              => $this->getTable(),
         'field'              => 'internal_time_to_resolve',
         'name'               => __('Internal time to resolve'),
         'datatype'           => 'datetime',
         'maybefuture'        => true,
         'massiveaction'      => false,
         'additionalfields'   => ['status']
      ];

      $tab[] = [
         'id'                 => '181',
         'table'              => $this->getTable(),
         'field'              => 'internal_time_to_resolve',
         'name'               => __('Internal time to resolve + Progress'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'additionalfields'   => ['status']
      ];

      $tab[] = [
         'id'                 => '182',
         'table'              => $this->getTable(),
         'field'              => 'is_late',
         'name'               => __('Internal time to resolve exceedeed'),
         'datatype'           => 'bool',
         'massiveaction'      => false,
         'computation'        => self::generateSLAOLAComputation('internal_time_to_resolve')
      ];

      $tab[] = [
         'id'                 => '185',
         'table'              => $this->getTable(),
         'field'              => 'internal_time_to_own',
         'name'               => __('Internal time to own'),
         'datatype'           => 'datetime',
         'maybefuture'        => true,
         'massiveaction'      => false,
         'additionalfields'   => ['status']
      ];

      $tab[] = [
         'id'                 => '186',
         'table'              => $this->getTable(),
         'field'              => 'internal_time_to_own',
         'name'               => __('Internal time to own + Progress'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'additionalfields'   => ['status']
      ];

      $tab[] = [
         'id'                 => '187',
         'table'              => 'glpi_tickets',
         'field'              => 'is_late',
         'name'               => __('Internal time to own exceedeed'),
         'datatype'           => 'bool',
         'massiveaction'      => false,
         'computation'        => self::generateSLAOLAComputation('internal_time_to_own')
      ];

      $max_date = '99999999';
      $tab[] = [
         'id'                 => '188',
         'table'              => $this->getTable(),
         'field'              => 'next_escalation_level',
         'name'               => __('Next escalation level'),
         'datatype'           => 'datetime',
         'usehaving'          => true,
         'maybefuture'        => true,
         'massiveaction'      => false,
         // Get least value from TTO/TTR fields:
         // - use TTO fields only if ticket not already taken into account,
         // - use TTR fields only if ticket not already solved,
         // - replace NULL or not kept values with 99999999 to be sure that they will not be returned by the LEAST function,
         // - replace 99999999 by empty string to keep only valid values.
         'computation'        => "REPLACE(
            LEAST(
               IF(".$DB->quoteName('TABLE.takeintoaccount_delay_stat')." <= 0,
                  COALESCE(".$DB->quoteName('TABLE.time_to_own').", $max_date),
                  $max_date),
               IF(".$DB->quoteName('TABLE.takeintoaccount_delay_stat')." <= 0,
                  COALESCE(".$DB->quoteName('TABLE.internal_time_to_own').", $max_date),
                  $max_date),
               IF(".$DB->quoteName('TABLE.solvedate')." IS NULL,
                  COALESCE(".$DB->quoteName('TABLE.time_to_resolve').", $max_date),
                  $max_date),
               IF(".$DB->quoteName('TABLE.solvedate')." IS NULL,
                  COALESCE(".$DB->quoteName('TABLE.internal_time_to_resolve').", $max_date),
                  $max_date)
            ), $max_date, '')"
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'type',
         'name'               => _n('Type', 'Types', 1),
         'searchtype'         => 'equals',
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_items_tickets',
         'field'              => 'items_id',
         'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
         'datatype'           => 'specific',
         'comments'           => true,
         'nosort'             => true,
         'nosearch'           => true,
         'additionalfields'   => ['itemtype'],
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '131',
         'table'              => 'glpi_items_tickets',
         'field'              => 'itemtype',
         'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber()),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'ticket_types',
         'nosort'             => true,
         'additionalfields'   => ['itemtype'],
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => 'glpi_requesttypes',
         'field'              => 'name',
         'name'               => RequestType::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $location_so = Location::rawSearchOptionsToAdd();
      foreach ($location_so as &$so) {
         //duplicated search options :(
         switch ($so['id']) {
            case 3:
               $so['id'] = 83;
               break;
            case 91:
               $so['id'] = 84;
               break;
            case 92:
               $so['id'] = 85;
               break;
            case 93:
               $so['id'] = 86;
               break;
         }
      }
      $tab = array_merge($tab, $location_so);

      $tab = array_merge($tab, $this->getSearchOptionsActors());

      $tab[] = [
         'id'                 => 'sla',
         'name'               => __('SLA')
      ];

      $tab[] = [
         'id'                 => '37',
         'table'              => 'glpi_slas',
         'field'              => 'name',
         'linkfield'          => 'slas_id_tto',
         'name'               => __('SLA')."&nbsp;".__('Time to own'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'condition'          => "AND NEWTABLE.`type` = '".SLM::TTO."'"
         ],
         'condition'          => ['glpi_slas.type' => SLM::TTO],
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => 'glpi_slas',
         'field'              => 'name',
         'linkfield'          => 'slas_id_ttr',
         'name'               => __('SLA')."&nbsp;".__('Time to resolve'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'condition'          => "AND NEWTABLE.`type` = '".SLM::TTR."'"
         ],
         'condition'          => ['glpi_slas.type' => SLM::TTR],
      ];

      $tab[] = [
         'id'                 => '32',
         'table'              => 'glpi_slalevels',
         'field'              => 'name',
         'name'               => __('SLA')."&nbsp;"._n('Escalation level', 'Escalation levels', 1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_slalevels_tickets',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ],
         'forcegroupby'       => true
      ];

      $tab[] = [
         'id'                 => 'ola',
         'name'               => __('OLA')
      ];

      $tab[] = [
         'id'                 => '190',
         'table'              => 'glpi_olas',
         'field'              => 'name',
         'linkfield'          => 'olas_id_tto',
         'name'               => __('OLA')."&nbsp;".__('Internal time to own'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'condition'          => "AND NEWTABLE.`type` = '".SLM::TTO."'"
         ],
         'condition'          => ['glpi_olas.type' => SLM::TTO],
      ];

      $tab[] = [
         'id'                 => '191',
         'table'              => 'glpi_olas',
         'field'              => 'name',
         'linkfield'          => 'olas_id_ttr',
         'name'               => __('OLA')."&nbsp;".__('Internal time to resolve'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'condition'          => "AND NEWTABLE.`type` = '".SLM::TTR."'"
         ],
         'condition'          => ['glpi_olas.type' => SLM::TTR],
      ];

      $tab[] = [
         'id'                 => '192',
         'table'              => 'glpi_olalevels',
         'field'              => 'name',
         'name'               => __('OLA')."&nbsp;"._n('Escalation level', 'Escalation levels', 1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_olalevels_tickets',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ],
         'forcegroupby'       => true
      ];

      $validation_options = TicketValidation::rawSearchOptionsToAdd();
      if (!Session::haveRightsOr(
         'ticketvalidation',
         [
            TicketValidation::CREATEINCIDENT,
            TicketValidation::CREATEREQUEST
         ]
      )) {
         foreach ($validation_options as &$validation_option) {
            if (isset($validation_option['table'])) {
               $validation_option['massiveaction'] = false;
            }
         }
      }
      $tab = array_merge($tab, $validation_options);

      $tab[] = [
         'id'                 => 'satisfaction',
         'name'               => __('Satisfaction survey')
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_ticketsatisfactions',
         'field'              => 'type',
         'name'               => _n('Type', 'Types', 1),
         'massiveaction'      => false,
         'searchtype'         => ['equals', 'notequals'],
         'searchequalsonfield' => true,
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '60',
         'table'              => 'glpi_ticketsatisfactions',
         'field'              => 'date_begin',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => 'glpi_ticketsatisfactions',
         'field'              => 'date_answered',
         'name'               => __('Response date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '62',
         'table'              => 'glpi_ticketsatisfactions',
         'field'              => 'satisfaction',
         'name'               => __('Satisfaction'),
         'datatype'           => 'number',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '63',
         'table'              => 'glpi_ticketsatisfactions',
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab = array_merge($tab, ITILFollowup::rawSearchOptionsToAdd());

      $tab = array_merge($tab, TicketTask::rawSearchOptionsToAdd());

      $tab = array_merge($tab, $this->getSearchOptionsStats());

      $tab[] = [
         'id'                 => '150',
         'table'              => $this->getTable(),
         'field'              => 'takeintoaccount_delay_stat',
         'name'               => __('Take into account time'),
         'datatype'           => 'timestamp',
         'forcegroupby'       => true,
         'massiveaction'      => false
      ];

      if (Session::haveRightsOr(self::$rightname,
                                [self::READALL, self::READASSIGN, self::OWN])) {
         $tab[] = [
            'id'                 => 'linktickets',
            'name'               => _n('Linked ticket', 'Linked tickets', Session::getPluralNumber())
         ];

         $tab[] = [
            'id'                 => '40',
            'table'              => 'glpi_tickets_tickets',
            'field'              => 'tickets_id_1',
            'name'               => __('All linked tickets'),
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'searchtype'         => 'equals',
            'joinparams'         => [
               'jointype' => 'item_item'
            ],
            'additionalfields'   => ['tickets_id_2']
         ];

         $tab[] = [
            'id'                 => '47',
            'table'              => 'glpi_tickets_tickets',
            'field'              => 'tickets_id_1',
            'name'               => __('Duplicated tickets'),
            'massiveaction'      => false,
            'searchtype'         => 'equals',
            'joinparams'         => [
               'jointype'           => 'item_item',
               'condition'          => 'AND NEWTABLE.`link` = '.Ticket_Ticket::DUPLICATE_WITH
            ],
            'additionalfields'   => ['tickets_id_2'],
            'forcegroupby'       => true
         ];

         $tab[] = [
            'id'                 => '41',
            'table'              => 'glpi_tickets_tickets',
            'field'              => 'id',
            'name'               => __('Number of all linked tickets'),
            'massiveaction'      => false,
            'datatype'           => 'count',
            'usehaving'          => true,
            'joinparams'         => [
               'jointype'           => 'item_item'
            ]
         ];

         $tab[] = [
            'id'                 => '46',
            'table'              => 'glpi_tickets_tickets',
            'field'              => 'id',
            'name'               => __('Number of duplicated tickets'),
            'massiveaction'      => false,
            'datatype'           => 'count',
            'usehaving'          => true,
            'joinparams'         => [
               'jointype'           => 'item_item',
               'condition'          => 'AND NEWTABLE.`link` = '.Ticket_Ticket::DUPLICATE_WITH
            ]
         ];

         $tab[] = [
            'id'                 => '50',
            'table'              => 'glpi_tickets',
            'field'              => 'id',
            'linkfield'          => 'tickets_id_2',
            'name'               => __('Parent tickets'),
            'massiveaction'      => false,
            'searchtype'         => 'equals',
            'datatype'           => 'itemlink',
            'usehaving'          => true,
            'joinparams'         => [
               'beforejoin'         => [
                  'table'              => 'glpi_tickets_tickets',
                  'joinparams'         => [
                     'jointype'           => 'child',
                     'linkfield'          => 'tickets_id_1',
                     'condition'          => 'AND NEWTABLE.`link` = '.Ticket_Ticket::SON_OF,
                  ]
               ]
            ],
            'forcegroupby'       => true
         ];

         $tab[] = [
            'id'                 => '67',
            'table'              => 'glpi_tickets',
            'field'              => 'id',
            'linkfield'          => 'tickets_id_1',
            'name'               => __('Child tickets'),
            'massiveaction'      => false,
            'searchtype'         => 'equals',
            'datatype'           => 'itemlink',
            'usehaving'          => true,
            'joinparams'         => [
               'beforejoin'         => [
                  'table'              => 'glpi_tickets_tickets',
                  'joinparams'         => [
                     'jointype'           => 'child',
                     'linkfield'          => 'tickets_id_2',
                     'condition'          => 'AND NEWTABLE.`link` = '.Ticket_Ticket::SON_OF,
                  ]
               ]
            ],
            'forcegroupby'       => true
         ];

         $tab[] = [
            'id'                 => '68',
            'table'              => 'glpi_tickets_tickets',
            'field'              => 'id',
            'name'               => __('Number of sons tickets'),
            'massiveaction'      => false,
            'datatype'           => 'count',
            'usehaving'          => true,
            'joinparams'         => [
               'linkfield'          => 'tickets_id_2',
               'jointype'           => 'child',
               'condition'          => 'AND NEWTABLE.`link` = '.Ticket_Ticket::SON_OF
            ],
            'forcegroupby'       => true
         ];

         $tab[] = [
            'id'                 => '69',
            'table'              => 'glpi_tickets_tickets',
            'field'              => 'id',
            'name'               => __('Number of parent tickets'),
            'massiveaction'      => false,
            'datatype'           => 'count',
            'usehaving'          => true,
            'joinparams'         => [
               'linkfield'          => 'tickets_id_1',
               'jointype'           => 'child',
               'condition'          => 'AND NEWTABLE.`link` = '.Ticket_Ticket::SON_OF
            ],
            'additionalfields'   => ['tickets_id_2']
         ];

         $tab = array_merge($tab, $this->getSearchOptionsSolution());

         if (Session::haveRight('ticketcost', READ)) {
            $tab = array_merge($tab, TicketCost::rawSearchOptionsToAdd());
         }
      }

      if (Session::haveRight('problem', READ)) {
         $tab[] = [
            'id'                 => 'problem',
            'name'               => __('Problems')
         ];

         $tab[] = [
            'id'                 => '141',
            'table'              => 'glpi_problems',
            'field'              => 'name',
            'name'               => __('Problem'),
            'datatype'           => 'dropdown',
            'right'              => 'all',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
               'beforejoin'         => [
                  'table'              => 'glpi_problems_tickets',
                  'joinparams'         => [
                     'jointype'           => 'child',
                  ]
               ]
            ]
         ];

         $tab[] = [
            'id'                 => '242',
            'table'              => 'glpi_changes',
            'field'              => 'name',
            'name'               => Change::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'dropdown',
            'right'              => 'all',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
               'beforejoin'         => [
                  'table'              => 'glpi_changes_tickets',
                  'joinparams'         => [
                     'jointype'           => 'child',
                  ]
               ]
            ]
         ];
      }

      // Filter search fields for helpdesk
      if (!Session::isCron() // no filter for cron
            && (Session::getCurrentInterface() != 'central')) {
         $tokeep = ['common', 'requester','satisfaction'];
         if (Session::haveRightsOr('ticketvalidation',
                                 array_merge(TicketValidation::getValidateRights(),
                                             TicketValidation::getCreateRights()))) {
            $tokeep[] = 'validation';
         }
         $keep = false;
         foreach ($tab as $key => &$val) {
            if (!isset($val['table'])) {
               $keep = in_array($val['id'], $tokeep);
            }
            if (!$keep) {
               if (isset($val['table'])) {
                  $val['nosearch'] = true;
               }
            }
         }
      }
      return $tab;
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;
  
      // show full create form only to tech users
      if ($ID <= 0 && Session::getCurrentInterface() !== "central") {
          return;
      }
  
      if (isset($options['_add_fromitem']) && isset($options['itemtype'])) {
          $item = new $options['itemtype'];
          $item->getFromDB($options['items_id'][$options['itemtype']][0]);
          $options['entities_id'] = $item->fields['entities_id'];
      }
  
      $default_values = self::getDefaultValues();
  
      // Restore saved value or override with page parameter
      $saved = $this->restoreInput();
  
      // Restore saved values and override $this->fields
      $this->restoreSavedValues($saved);
  
      foreach ($default_values as $name => $value) {
          if (!isset($options[$name])) {
            if (isset($saved[$name])) {
                $options[$name] = $saved[$name];
            } else {
                $options[$name] = $value;
            }
          }
      }
  
      if (isset($options['content'])) {
          // Clean new lines to be fix encoding
          $order              = ['\\r', '\\n', "\\'", '\\"', "\\\\"];
          $replace            = ["", "", "'", '"', "\\"];
          $options['content'] = str_replace($order, $replace, $options['content']);
      }
      if (isset($options['name'])) {
          $order           = ["\\'", '\\"', "\\\\"];
          $replace         = ["'", '"', "\\"];
          $options['name'] = str_replace($order, $replace, $options['name']);
      }
  
      if (!isset($options['_skip_promoted_fields'])) {
          $options['_skip_promoted_fields'] = false;
      }
  
      if (!$ID) {
          // Override defaut values from projecttask if needed
          if (isset($options['_projecttasks_id'])) {
            $pt = new ProjectTask();
            if ($pt->getFromDB($options['_projecttasks_id'])) {
                $options['name'] = $pt->getField('name');
                $options['content'] = $pt->getField('name');
            }
          }
          // Override defaut values from followup if needed
          if (isset($options['_promoted_fup_id']) && !$options['_skip_promoted_fields']) {
            $fup = new ITILFollowup();
            if ($fup->getFromDB($options['_promoted_fup_id'])) {
                $options['content'] = $fup->getField('content');
                $options['_users_id_requester'] = $fup->fields['users_id'];
                $options['_link'] = [
                  'link'         => Ticket_Ticket::SON_OF,
                  'tickets_id_2' => $fup->fields['items_id']
                ];
            }
            //Allow overriding the default values
            $options['_skip_promoted_fields'] = true;
          }
      }
  
      // Check category / type validity
      if ($options['itilcategories_id']) {
          $cat = new ITILCategory();
          if ($cat->getFromDB($options['itilcategories_id'])) {
            switch ($options['type']) {
                case self::INCIDENT_TYPE :
                  if (!$cat->getField('is_incident')) {
                      $options['itilcategories_id'] = 0;
                  }
                  break;
  
                case self::DEMAND_TYPE :
                  if (!$cat->getField('is_request')) {
                      $options['itilcategories_id'] = 0;
                  }
                  break;
  
                default :
                  break;
            }
          }
      }
  
      // Default check
      if ($ID > 0) {
          $this->check($ID, READ);
      } else {
          // Create item
          $this->check(-1, CREATE, $options);
      }
  
      if (!$ID) {
          $this->userentities = [];
          if ($options["_users_id_requester"]) {
            //Get all the user's entities
            $requester_entities = Profile_User::getUserEntities($options["_users_id_requester"], true,
                                                          true);
            $user_entities = $_SESSION['glpiactiveentities'];
            $this->userentities = array_intersect($requester_entities, $user_entities);
          }
          $this->countentitiesforuser = count($this->userentities);
  
          if (($this->countentitiesforuser > 0)
              && !in_array($this->fields["entities_id"], $this->userentities)) {
            // If entity is not in the list of user's entities,
            // then use as default value the first value of the user's entites list
            $this->fields["entities_id"] = $this->userentities[0];
            // Pass to values
            $options['entities_id']       = $this->userentities[0];
          }
      }
  
      if ($options['type'] <= 0) {
          $options['type'] = Entity::getUsedConfig('tickettype', $options['entities_id'], '',
                                                  Ticket::INCIDENT_TYPE);
      }
  
      if (!isset($options['template_preview'])) {
          $options['template_preview'] = 0;
      }
  
      if (!isset($options['_promoted_fup_id'])) {
          $options['_promoted_fup_id'] = 0;
      }
  
      // Load template if available :
      $tt = $this->getITILTemplateToUse(
          $options['template_preview'],
          $this->fields['type'],
          ($ID ? $this->fields['itilcategories_id'] : $options['itilcategories_id']),
          ($ID ? $this->fields['entities_id'] : $options['entities_id'])
      );
  
      // Predefined fields from template : reset them
      if (isset($options['_predefined_fields'])) {
          $options['_predefined_fields']
                        = Toolbox::decodeArrayFromInput($options['_predefined_fields']);
      } else {
          $options['_predefined_fields'] = [];
      }
  
      // Store predefined fields to be able not to take into account on change template
      // Only manage predefined values on ticket creation
      $predefined_fields = [];
      $tpl_key = $this->getTemplateFormFieldName();
      if (!$ID) {
  
          if (isset($tt->predefined) && count($tt->predefined)) {
            foreach ($tt->predefined as $predeffield => $predefvalue) {
                if (isset($default_values[$predeffield])) {
                  // Is always default value : not set
                  // Set if already predefined field
                  // Set if ticket template change
                  if (((count($options['_predefined_fields']) == 0)
                        && ($options[$predeffield] == $default_values[$predeffield]))
                      || (isset($options['_predefined_fields'][$predeffield])
                          && ($options[$predeffield] == $options['_predefined_fields'][$predeffield]))
                      || (isset($options[$tpl_key])
                          && ($options[$tpl_key] != $tt->getID()))
                      // user pref for requestype can't overwrite requestype from template
                      // when change category
                      || (($predeffield == 'requesttypes_id')
                          && empty($saved))) {
  
                      // Load template data
                      $options[$predeffield]            = $predefvalue;
                      $this->fields[$predeffield]      = $predefvalue;
                      $predefined_fields[$predeffield] = $predefvalue;
                  }
                }
            }
            // All predefined override : add option to say predifined exists
            if (count($predefined_fields) == 0) {
                $predefined_fields['_all_predefined_override'] = 1;
            }
  
          } else { // No template load : reset predefined values
            if (count($options['_predefined_fields'])) {
                foreach ($options['_predefined_fields'] as $predeffield => $predefvalue) {
                  if ($options[$predeffield] == $predefvalue) {
                      $options[$predeffield] = $default_values[$predeffield];
                  }
                }
            }
          }
      }
      // Put ticket template on $options for actors
      $options[str_replace('s_id', '', $tpl_key)] = $tt;
  
      // check right used for this ticket
      $canupdate     = !$ID
                        || (Session::getCurrentInterface() == "central"
                            && $this->canUpdateItem());
      $can_requester = $this->canRequesterUpdateItem();
      $canpriority   = Session::haveRight(self::$rightname, self::CHANGEPRIORITY);
      $canassign     = $this->canAssign();
      $canassigntome = $this->canAssignTome();
  
      if ($ID && in_array($this->fields['status'], $this->getClosedStatusArray())) {
          $canupdate = false;
          // No update for actors
          $options['_noupdate'] = true;
      }
  
      $showuserlink              = 0;
      if (Session::haveRight('user', READ)) {
          $showuserlink = 1;
      }
  
      if ($options['template_preview']) {
          // Add all values to fields of tickets for template preview
          foreach ($options as $key => $val) {
            if (!isset($this->fields[$key])) {
                $this->fields[$key] = $val;
            }
          }
      }
  
      $all_fields = [
          [
            'label'=> 'Opening date',
            'name'=> 'date',
            'type'=> 'datetime',
            'maybeempty' => false,
            'is_new_id' => false
          ],[
            'label'=> 'Par',
            'name'=> 'users_id_recipient',
            'type'=> 'dropdown',
            'readOnly' => true,
            'is_new_id' => false
          ],[
            'label'=> 'Last update',
            'name'=> 'date_mod',
            'type'=> 'datetime',
            'maybeempty' => false,
            'readOnly'   => true,
            'is_new_id' => false
          ],[
            'label'=> 'Par',
            'name'=> 'users_id_lastupdater',
            'type'=> 'dropdown',
            'readOnly' => true,
            'is_new_id' => false
          ],[
            'label'=> 'Type',
            'type'=> 'function',
            'name'=> 'dropdownTypeFago',
            'params' => [
              'name' => 'type',
              'value' => $this->fields["type"]
            ]
          ],[
            'label'=> 'Category',
            'name'=> 'itilcategories_id',
            'type'=> 'dropdown'
          ],[
            'label'=> 'Status',
            'type'=> 'function',
            'name'=> 'dropdownStatus',
            'params'=>[
              'name' => 'status',
              'value' => $this->fields["status"],
              'showtype'  => 'allowed'
            ]
          ],[
            'label'=> 'Urgency',
            'type'=> 'function',
            'name'=> 'dropdownUrgency',
            'params' => [
              'name'=> 'urgency',
              'value' => $this->fields["urgency"]
            ],
            'events' => [
              'type'  => ['change'],
              'input_type' => 'dropdown',
              'action' => 'setInputValue',
              'input_cible' => 'priority',
              'url' =>   $CFG_GLPI["root_doc"]."/ajax/priority.php",
              'params' => [
                'impact' => $this->fields["impact"]
              ]
            ]
          ],[
            'label'=> 'Impact',
            'type'=> 'function',
            'name'=> 'dropdownImpact',
            'params' => [
              'name'=> 'impact',
              'value' => $this->fields["impact"]
            ],
            'events' => [
              'type'  => ['change'],
              'input_type' => 'dropdown',
              'action' => 'setInputValue',
              'input_cible' => 'priority',
              'url' =>   $CFG_GLPI["root_doc"]."/ajax/priority.php",
              'params' =>   [
                'urgency' => $this->fields["urgency"]
              ]
            ]
          ],[
            'label'=> 'Priority',
            'type'=> 'function',
            'name'=> 'dropdownPriority',
            'params' => [
              'name'=> 'priority',
              'value' => $this->fields["priority"]
            ]
          ],[
            'label'=> 'Demandeur',
            'type'=> 'function',
            'name' => 'itilActors',
            'params'=> [ 
              'id'            => $ID,
              'name'            => '_users_id_requester',
              'actornumber'   => CommonITILActor::REQUESTER,
              'type'            => 'user',
              'actortype'       => static::getActorFieldNameType(CommonITILActor::REQUESTER),
              'itemtype'        => $this->getType(),
              'disabled'     => false,
              'allow_email'     => true,
              'entity_restrict' => $this->fields['entities_id'],
              'use_notif'       => Entity::getUsedConfig('is_notif_enable_default',  $this->fields['entities_id'], '', 1)
            ]
          ],[
            'label'=> 'Observateur',
            'type'=> 'function',
            'name'=> 'itilActors',
            'params'=> [
              'id'            => $ID,
              'name'            => '_users_id_observer[]',
              'actornumber'   => CommonITILActor::OBSERVER,
              'type'            => 'user',
              'actortype'       => static::getActorFieldNameType(CommonITILActor::OBSERVER),
              'itemtype'        => $this->getType(),
              'allow_email'     => true,
              'entity_restrict' => $this->fields['entities_id'],
              'use_notif'       => Entity::getUsedConfig('is_notif_enable_default',  $this->fields['entities_id'], '', 1)
            ],
          ],[
            'label'=> 'Groupe d\'assignation',
            'type'=> 'function',
            'name'=> 'itilActors',
            'params'=> [
              'name'=> '_groups_id_assign',
              'id'            => $ID,
              'type'            => 'group',
              'actortype'       => static::getActorFieldNameType(CommonITILActor::ASSIGN),
              'itemtype'        => $this->getType(),
              'allow_email'     => true,
              'entity_restrict' => $this->fields['entities_id'],
              'use_notif'       => Entity::getUsedConfig('is_notif_enable_default',  $this->fields['entities_id'], '', 1)
            ],
            'events' => [
              'type'  => ['change'],
              'input_type' => 'dropdown',
              'action' => 'setInputData',
              'input_cible' => '_users_id_assign',
              'url' =>   $CFG_GLPI["root_doc"]."/ajax/dropdownassigngroup.php",
              'params' => [
                'condition' => ['is_assign' => 1],
                'rand' => mt_rand(),
                'entity' =>  $this->fields['entities_id'],
                'right' =>  'own_ticket',
                'ldap_import' =>  true,
                'display_emptychoice' =>  true,
              ]
            ]
          ],[
            'label'=> 'Asigner à',
            'type'=> 'function',
            'name'=> 'itilActors',
            'params'=> [
                'id'            => $ID,
                'name'            => '_users_id_assign',
                'actornumber'   => CommonITILActor::ASSIGN,
                'type'            => 'user',
                'actortype'       => static::getActorFieldNameType(CommonITILActor::ASSIGN),
                'itemtype'        => $this->getType(),
                'allow_email'     => true,
                'entity_restrict' => $this->fields['entities_id'],
                'use_notif'       => Entity::getUsedConfig('is_notif_enable_default',  $this->fields['entities_id'], '', 1)
            ],
          ],[
            'label'=> 'Title',
            'name'=> 'name',
            'type'=> 'text',
            'mandatory'=> true,
            'full'=> true,
          ],[
            'name'=> 'content',
            'type'=> 'textarea',
            'label'=> 'Comments',
            'full'=> true
          ]
      ];
  
      PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
    }

    public function itilActors($params = []) {
      global $CFG_GLPI;
      Session::checkCentralAccess();
      if (isset($params["type"])
          && isset($params["actortype"])
          && isset($params["itemtype"])) {
          $rand = mt_rand();
          if ($item = getItemForItemtype($params["itemtype"])) {
            switch ($params["type"]) {
              case "user" :
                $right = 'all';
                // Only steal or own ticket whit empty assign
                if ($params["actortype"] == 'assign') {
                    $right = "own_ticket";
                    if (!$item->canAssign()) {
                      $right = 'id';
                    }
                }
  
                $options = ['name'        => '_users_id_'.$params["actortype"],
                                  'entity'      => $params['entity_restrict'],
                                  'right'       => $right,
                                  'disabled'        => (isset($params['disabled'])) ? $params['disabled'] : false ,
                                  'rand'        => $rand,
                                  'ldap_import' => true];
  
                if ($CFG_GLPI["notifications_mailing"]) {
                    $withemail     = (isset($params["allow_email"]) ? $params["allow_email"] : false);
                    $paramscomment = ['value'       => '__VALUE__',
                                          'allow_email' => $withemail,
                                          'field'       => "_itil_".$params["actortype"],
                                          'use_notification' => $params["use_notif"]];
                    // Fix rand value
                    $options['rand']     = $rand;
                    $options['toupdate'] = ['value_fieldname' => 'value',
                                                'to_update'       => "notif_user_$rand",
                                                'url'             => $CFG_GLPI["root_doc"].
                                                                          "/ajax/uemailUpdate.php",
                                                'moreparams'      => $paramscomment];
                }
  
                if (($params["itemtype"] == 'Ticket')
                    && ($params["actortype"] == 'assign')) {
                    $toupdate = [];
                    if (isset($options['toupdate']) && is_array($options['toupdate'])) {
                      $toupdate[] = $options['toupdate'];
                    }
                    $toupdate[] = ['value_fieldname' => 'value',
                                        'to_update'       => "countassign_$rand",
                                        'url'             => $CFG_GLPI["root_doc"].
                                                                "/ajax/ticketassigninformation.php",
                                        'moreparams'      => ['users_id_assign' => '__VALUE__']];
                    $options['toupdate'] = $toupdate;
                }
  
                if ($params['actortype'] === 'observer') {
                  if (isset($params['id']) && $params['id'] > 0) {
                    $user_observer = new Ticket_User();
                    $has_user_observer = $user_observer->find(['tickets_id'=>$params['id'], 'type'=> $params['actornumber']]);
                    $users_id = [];
                    foreach ($has_user_observer as $value) {
                        array_push($users_id, $value["users_id"]);
                    }
                    $rand = PluginServicesUser::dropdownMultiple($options['name'], [
                      'values' => $users_id
                    ]);
                  }else{
                    $rand = PluginServicesUser::dropdownMultiple($options['name'], [
                      'values' => []
                    ]);
                  }
                }else {
                  if (isset($params['id']) && $params['id'] > 0) {
                    $user = new Ticket_User();
                    $has_user = $user->getFromDBByCrit(['tickets_id'=>$params['id'], 'type'=> $params['actornumber']]);
                    if($has_user){
                        $options['value'] = $user->fields['users_id'];
                        
                        $rand = PluginServicesUser::dropdown($options);
                    }else{
                      $rand = PluginServicesUser::dropdown($options);   
                    }
                  }else{
                    if ($params["actortype"] == 'assign') {
                      //si le type est assign usser on affiche un dropdown vide
                      // $rand = PluginServicesUser::dropdown($options); 
                      $rand = PluginServicesDropdown::showFromArray('_users_id_assign', [], ['display_emptychoice' => true]);
                    }else {
                      $rand = PluginServicesUser::dropdown($options); 
                    }
                  }
                }
                break;
              case "group" :
                $cond = ['is_requester' => 1];
                if ($params["actortype"] == 'assign') {
                  $cond = ['is_assign' => 1];
                }
                if ($params["actortype"] == 'observer') {
                  $cond = ['is_watcher' => 1];
                }
    
                $param = [
                  'name'      => '_groups_id_assign',
                  'entity'    => $params['entity_restrict'],
                  'condition' => $cond,
                  'rand'      => $rand
                ];
                
                if (($params["itemtype"] == 'Ticket')
                    && ($params["actortype"] == 'assign')) {
                    $param['toupdate'] = ['value_fieldname' => 'value',
                                              'to_update'       => "countgroupassign_$rand",
                                              'url'             => $CFG_GLPI["root_doc"].
                                                                      "/ajax/ticketassigninformation.php",
                                              'moreparams'      => ['groups_id_assign'
                                                                            => '__VALUE__']];
                }
  
                if (isset($params['id']) && $params['id'] > 0) {
                  $group_assign = new Group_Ticket();
                  $has_group_assign = $group_assign->find(['tickets_id'=>$params['id'], 'type'=> 2], 'id DESC');
                  if(count($has_group_assign)>=1){
                      foreach ($has_group_assign as $group) {
                        $param['value'] = $group['groups_id']; 
                      }
                      $rand = PluginServicesGroup::dropdown($param);
                  }else{
                    $rand = PluginServicesGroup::dropdown($param);   
                  }
                }else{
                  $rand = PluginServicesGroup::dropdown($param);    
                }
                // $param['_groups_id_assign'] = '__VALUE__';
                // $param['applyto'] = 'assignTo';
                // $param['right'] = 'own_ticket';
                // $param['ldap_import'] = true;
                // $param['display_emptychoice'] = true;  
                // Ajax::updateItemOnSelectEvent("dropdown__groups_id_assign$rand", $param['applyto'],
                //                                 $CFG_GLPI["root_doc"]."/ajax/dropdownassigngroup.php", $param);                  
                break;
            }
          }
          return $rand;
      }
    }

    public function showTabsContent($options = []) {
      // for objects not in table like central
      if (isset($this->fields['id'])) {
        $ID = $this->fields['id'];
      } else {
        if (isset($options['id'])) {
            $ID = $options['id'];
        } else {
            $ID = 0;
        }
      }

      $target         = $_SERVER['PHP_SELF'];
      $extraparamhtml = "";
      $withtemplate   = "";
      if (is_array($options) && count($options)) {
        if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
        }
        $cleaned_options = $options;
        if (isset($cleaned_options['id'])) {
            unset($cleaned_options['id']);
        }
        if (isset($cleaned_options['stock_image'])) {
            unset($cleaned_options['stock_image']);
        }
        if ($this instanceof CommonITILObject && $this->isNewItem()) {
            $this->input = $cleaned_options;
            $this->saveInput();
            // $extraparamhtml can be tool long in case of ticket with content
            // (passed in GET in ajax request)
            unset($cleaned_options['content']);
        }

        // prevent double sanitize, because the includes.php sanitize all data
        $cleaned_options = Toolbox::stripslashes_deep($cleaned_options);

        $extraparamhtml = "&amp;".Toolbox::append_params($cleaned_options, '&amp;');
      }
      echo "<div style='width:100%;' class='glpi_tabs ".($this->isNewID($ID)?"new_form_tabs":"")."'>";
      echo "<div id='tabspanel' class='center-h'></div>";
      $onglets     = $this->defineAllTabsFago($options);
      $display_all = false;
      if (isset($onglets['no_all_tab'])) {
        $display_all = false;
        unset($onglets['no_all_tab']);
      }

      if (count($onglets)) {
        $tabpage = $this->getTabsURL();
        $tabs    = [];

        foreach ($onglets as $key => $val) {
            $tabs[$key] = ['title'  => $val,
                              'url'    => $tabpage,
                              'params' => "_target=$target&amp;_itemtype=".$this->getType().
                                          "&amp;_glpi_tab=$key&amp;id=$ID$extraparamhtml"];
        }

        // Not all tab for templates and if only 1 tab
        if ($display_all
            && empty($withtemplate)
            && (count($tabs) > 1)) {
            $tabs[-1] = ['title'  => __('All'),
                              'url'    => $tabpage,
                              'params' => "_target=$target&amp;_itemtype=".$this->getType().
                                          "&amp;_glpi_tab=-1&amp;id=$ID$extraparamhtml"];
        }

        PluginServicesAjax::createTabs('tabspanel', 'tabcontent', $tabs, $this->getType(), $ID,
                        "horizontal", $options);
      }
      echo "</div>";
  }

  public function defineAllTabsFago($options = []) {
    global $CFG_GLPI;
    $onglets = [];
    // Object with class with 'addtabon' attribute
    if (isset(self::$othertabs[$this->getType()])
        && !$this->isNewItem()) {

        foreach (self::$othertabs[$this->getType()] as $typetab) {
          $this->addStandardTab($typetab, $onglets, $options);
        }
    }

    $class = $this->getType();
    return $onglets;
  }
}