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

class PluginServicesTicketTask extends TicketTask {
   static function getTable($classname = null) {
      return "glpi_tickettasks";
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

   
   static function canCreate() {
      return (Session::haveRight(self::$rightname, parent::ADDALLITEM)
               || Session::haveRight('ticket', Ticket::OWN));
   }


   static function canView() {
      return (Session::haveRightsOr(self::$rightname, [parent::SEEPUBLIC, parent::SEEPRIVATE])
               || Session::haveRight('ticket', Ticket::OWN));
   }


   static function canUpdate() {
      return (Session::haveRight(self::$rightname, parent::UPDATEALL)
               || Session::haveRight('ticket', Ticket::OWN));
   }


   function canViewPrivates() {
      return Session::haveRight(self::$rightname, parent::SEEPRIVATE);
   }


   function canEditAll() {
      return Session::haveRight(self::$rightname, parent::UPDATEALL);
   }


   /**
    * Does current user have right to show the current task?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!$this->canReadITILItem()) {
         return false;
      }

      if (Session::haveRight(self::$rightname, parent::SEEPRIVATE)) {
         return true;
      }

      if (!$this->fields['is_private']
          && Session::haveRight(self::$rightname, parent::SEEPUBLIC)) {
         return true;
      }

      // see task created or affected to me
      if (Session::getCurrentInterface() == "central"
          && ($this->fields["users_id"] === Session::getLoginUserID())
              || ($this->fields["users_id_tech"] === Session::getLoginUserID())) {
         return true;
      }

      if ($this->fields["groups_id_tech"] && ($this->fields["groups_id_tech"] > 0)
          && isset($_SESSION["glpigroups"])
          && in_array($this->fields["groups_id_tech"], $_SESSION["glpigroups"])) {
         return true;
      }

      return false;
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

      $ticket = new Ticket();
      if ($ticket->getFromDB($this->fields['tickets_id'])
          // No validation for closed tickets
          && !in_array($ticket->fields['status'], $ticket->getClosedStatusArray())) {
         return (Session::haveRight(self::$rightname, parent::ADDALLITEM)
                 || $ticket->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                 || (isset($_SESSION["glpigroups"])
                     && $ticket->haveAGroup(CommonITILActor::ASSIGN,
                                            $_SESSION['glpigroups'])));
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

      $ticket = new Ticket();
      if ($ticket->getFromDB($this->fields['tickets_id'])
         && in_array($ticket->fields['status'], $ticket->getClosedStatusArray())) {
         return false;
      }

      if (($this->fields["users_id"] != Session::getLoginUserID())
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
      $ticket = new Ticket();
      if ($ticket->getFromDB($this->fields['tickets_id'])
         && in_array($ticket->fields['status'], $ticket->getClosedStatusArray())) {
         return false;
      }

      return Session::haveRight(self::$rightname, PURGE);
   }


   /**
    * Populate the planning with planned ticket tasks
    *
    * @param $options   array of possible options:
    *    - who          ID of the user (0 = undefined)
    *    - whogroup     ID of the group of users (0 = undefined)
    *    - begin        Date
    *    - end          Date
    *
    * @return array of planning item
   **/
   static function populatePlanning($options = []) :array {
      return parent::genericPopulatePlanning(__CLASS__, $options);
   }


   /**
    * Populate the planning with planned ticket tasks
    *
    * @param $options   array of possible options:
    *    - who          ID of the user (0 = undefined)
    *    - whogroup     ID of the group of users (0 = undefined)
    *    - begin        Date
    *    - end          Date
    *
    * @return array of planning item
   **/
   static function populateNotPlanned($options = []) :array {
      return parent::genericPopulateNotPlanned(__CLASS__, $options);
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
    * @since 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[UPDATE], $values[CREATE], $values[READ]);

      if ($interface == 'central') {
         $values[parent::UPDATEALL]      = __('Update all');
         $values[parent::ADDALLITEM  ]   = __('Add to all items');
         $values[parent::SEEPRIVATE]     = __('See private ones');
      }

      $values[parent::SEEPUBLIC]   = __('See public ones');

      if ($interface == 'helpdesk') {
         unset($values[PURGE]);
      }

      return $values;
   }


   /**
    * @since 0.90
    *
    * @see CommonDBTM::showFormButtons()
   **/
   function showFormButtons($options = []) {
      // for single object like config
      $ID = 1;
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      }

      $params['colspan']      = 2;
      $params['candel']       = true;
      $params['canedit']      = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!$this->isNewID($ID)) {
         echo "<input type='hidden' name='id' value='$ID'>";
      }

      Plugin::doHook("post_item_form", ['item' => $this, 'options' => &$params]);

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='".($params['colspan']*2)."'>";

      if ($this->isNewID($ID)) {
         echo Ticket::getSplittedSubmitButtonHtml($this->fields['tickets_id'], 'add');
      } else {
         if ($params['candel']
               // no trashbin in tickettask
          //   && !$this->can($ID, DELETE)
             && !$this->can($ID, PURGE)) {
            $params['candel'] = false;
         }

         if ($params['canedit'] && $this->canUpdateItem()) {
            echo Ticket::getSplittedSubmitButtonHtml($this->fields['tickets_id'], 'update');
            echo "</td></tr><tr class='tab_bg_2'>\n";
         }

         if ($params['candel']) {
            echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
            if ($this->can($ID, PURGE)) {
               echo PluginServicesHtml::submit(_x('button', 'Delete permanently'),
                                 ['name'    => 'purge',
                                       'confirm' => __('Confirm the final deletion?')]);
            }
         }

         if ($this->isField('date_mod')) {
            echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
         }
      }

      echo "</td></tr></table></div>";
      PluginServicesHtml::closeForm();
   }

   /**
    * Build parent condition for search
    *
    * @return string
    */
   public static function buildParentCondition() {
      return "(0 = 1 " . Ticket::buildCanViewCondition("tickets_id") . ") ";
   }

   /** form for Task
    *
    * @param $ID        Integer : Id of the task
    * @param $options   array
    *     -  parent Object : the object
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $rand_template   = mt_rand();
      $rand_text       = mt_rand();
      $rand_type       = mt_rand();
      $rand_time       = mt_rand();
      $rand_user       = mt_rand();
      $rand_is_private = mt_rand();
      $rand_group      = mt_rand();
      $rand_state      = mt_rand();

      $options['parent']= 'Ticket';
      if (isset($options['parent']) && !empty($options['parent'])) {
         $item = $options['parent'];
      }
      $options['formoptions'] = ($options['formoptions'] ?? '') . ' data-track-changes=true';

      $fkfield = $item->getForeignKeyField();

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[$fkfield] = $item->getField('id');
         $this->check(-1, CREATE, $options);
      }

      //prevent null fields due to getFromDB
      if (is_null($this->fields['begin'])) {
         $this->fields['begin'] = "";
      }

      $rand = mt_rand();
      $this->showFormHeader($options);

      $canplan = (!$item->isStatusExists(CommonITILObject::PLANNED)
                  || $item->isAllowedStatus($item->fields['status'], CommonITILObject::PLANNED));

      $rowspan = 5;
      if ($this->maybePrivate()) {
         $rowspan++;
      }
      if (isset($this->fields["state"])) {
         $rowspan++;
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='3' id='content$rand_text'>";

      $rand_text  = mt_rand();
      $content_id = "content$rand_text";
      $cols       = 100;
      $rows       = 10;

      Html::textarea(['name'              => 'content',
                     'value'             => $this->fields["content"],
                     'rand'              => $rand_text,
                     'editor_id'         => $content_id,
                     'enable_fileupload' => true,
                     'enable_richtext'   => true,
                     'cols'              => $cols,
                     'rows'              => $rows]);

      echo "<input type='hidden' name='$fkfield' value='".$this->fields[$fkfield]."'>";
      echo "</td>";

      echo "<td style='vertical-align: middle'>";
      echo "<div class='fa-label'>
            <i class='fas fa-reply fa-fw'
               title='".TaskTemplate::getTypeName(Session::getPluralNumber())."'></i>";
      TaskTemplate::dropdown(['value'     => $this->fields['tasktemplates_id'],
                                 'entity'    => $this->getEntityID(),
                                 'rand'      => $rand_template,
                                 'on_change' => 'tasktemplate_update(this.value)']);
      echo "</div>";
      echo Html::scriptBlock('
         function tasktemplate_update(value) {
            $.ajax({
               url: "' . $CFG_GLPI["root_doc"] . '/ajax/task.php",
               type: "POST",
               data: {
                  tasktemplates_id: value
               }
            }).done(function(data) {
               var taskcategories_id = isNaN(parseInt(data.taskcategories_id))
                  ? 0
                  : parseInt(data.taskcategories_id);
               var actiontime = isNaN(parseInt(data.actiontime))
                  ? 0
                  : parseInt(data.actiontime);
               var user_tech = isNaN(parseInt(data.users_id_tech))
                  ? 0
                  : parseInt(data.users_id_tech);
               var group_tech = isNaN(parseInt(data.groups_id_tech))
                  ? 0
                  : parseInt(data.groups_id_tech);

               // set textarea content
               if (tasktinymce = tinymce.get("content'.$rand_text.'")) {
                  tasktinymce.setContent(data.content);
               }
               // set category
               $("#dropdown_taskcategories_id'.$rand_type.'").trigger("setValue", taskcategories_id);
               // set action time
               $("#dropdown_actiontime'.$rand_time.'").trigger("setValue", actiontime);
               // set is_private
               $("#is_privateswitch'.$rand_is_private.'")
                  .prop("checked", data.is_private == "0"
                     ? false
                     : true);
               // set users_tech
               $("#dropdown_users_id_tech'.$rand_user.'").trigger("setValue", user_tech);
               // set group_tech
               $("#dropdown_groups_id_tech'.$rand_group.'").trigger("setValue", group_tech);
               // set state
               $("#dropdown_state'.$rand_state.'").trigger("setValue", data.state);
            });
         }
      ');

      if ($ID > 0) {
         echo "<div class='fa-label'>
         <i class='far fa-calendar fa-fw'
            title='"._n('Date', 'Dates', 1)."'></i>";
         Html::showDateTimeField("date", [
            'value'      => $this->fields["date"],
            'maybeempty' => false
         ]);
         echo "</div>";
      }

      echo "<div class='fa-label'>
         <i class='fas fa-tag fa-fw'
            title='".__('Category')."'></i>";
      TaskCategory::dropdown([
         'value'     => $this->fields["taskcategories_id"],
         'rand'      => $rand_type,
         'entity'    => $item->fields["entities_id"],
         'condition' => ['is_active' => 1]
      ]);
      echo "</div>";

      if (isset($this->fields["state"])) {
         echo "<div class='fa-label'>
            <i class='fas fa-tasks fa-fw'
               title='".__('Status')."'></i>";
         Planning::dropdownState("state", $this->fields["state"], true, ['rand' => $rand_state]);
         echo "</div>";
      }

      if ($this->maybePrivate()) {
         echo "<div class='fa-label'>
            <i class='fas fa-lock fa-fw' title='".__('Private')."'></i>
            <span class='switch pager_controls'>
               <label for='is_privateswitch$rand_is_private' title='".__('Private')."'>
                  <input type='hidden' name='is_private' value='0'>
                  <input type='checkbox' id='is_privateswitch$rand_is_private' name='is_private' value='1'".
                        ($this->fields["is_private"]
                           ? "checked='checked'"
                           : "")."
                  >
                  <span class='lever'></span>
               </label>
            </span>
         </div>";
      }

      echo "<div class='fa-label'>
         <i class='fas fa-stopwatch fa-fw'
            title='".__('Duration')."'></i>";

      $toadd = [];
      for ($i=9; $i<=100; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("actiontime", ['min'             => 0,
                                                'max'             => 8*HOUR_TIMESTAMP,
                                                'value'           => $this->fields["actiontime"],
                                                'rand'            => $rand_time,
                                                'addfirstminutes' => true,
                                                'inhours'         => true,
                                                'toadd'           => $toadd,
                                                'width'  => '']);

      echo "</div>";

      echo "<div class='fa-label'>";
      echo "<i class='fas fa-user fa-fw' title='".User::getTypeName(1)."'></i>";
      $params             = ['name'   => "users_id_tech",
                                 'value'  => (($ID > -1)
                                                ?$this->fields["users_id_tech"]
                                                :Session::getLoginUserID()),
                                 'right'  => "own_ticket",
                                 'rand'   => $rand_user,
                                 'entity' => $item->fields["entities_id"],
                                 'width'  => ''];

      $params['toupdate'] = ['value_fieldname'
                                             => 'users_id',
                                 'to_update' => "user_available$rand_user",
                                 'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php"];
      User::dropdown($params);

      echo " <a href='#' title=\"".__s('Availability')."\" onClick=\"".Html::jsGetElementbyID('planningcheck'.$rand).".dialog('open'); return false;\">";
      echo "<i class='far fa-calendar-alt'></i>";
      echo "<span class='sr-only'>".__('Availability')."</span>";
      echo "</a>";
      Ajax::createIframeModalWindow('planningcheck'.$rand,
                                    $CFG_GLPI["root_doc"].
                                          "/front/planning.php?checkavailability=checkavailability".
                                          "&itemtype=".$item->getType()."&$fkfield=".$item->getID(),
                                    ['title'  => __('Availability')]);
      echo "</div>";

      echo "<div class='fa-label'>";
      echo "<i class='fas fa-users fa-fw' title='".Group::getTypeName(1)."'></i>";
      $params     = [
         'name'      => "groups_id_tech",
         'value'     => (($ID > -1)
                        ?$this->fields["groups_id_tech"]
                        :Dropdown::EMPTY_VALUE),
         'condition' => ['is_task' => 1],
         'rand'      => $rand_group,
         'entity'    => $item->fields["entities_id"]
      ];

      $params['toupdate'] = ['value_fieldname' => 'users_id',
                                 'to_update' => "group_available$rand_group",
                                 'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php"];
      Group::dropdown($params);
      echo "</div>";

      if (!empty($this->fields["begin"])) {

         if (Session::haveRight('planning', Planning::READMY)) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$ID.$rand_text."() {\n";
            echo Html::jsHide("plan$rand_text");
            $params = ['action'    => 'add_event_classic_form',
                           'form'      => 'followups',
                           'users_id'  => $this->fields["users_id_tech"],
                           'groups_id' => $this->fields["groups_id_tech"],
                           'id'        => $this->fields["id"],
                           'begin'     => $this->fields["begin"],
                           'end'       => $this->fields["end"],
                           'rand_user' => $rand_user,
                           'rand_group' => $rand_group,
                           'entity'    => $item->fields["entities_id"],
                           'itemtype'  => $this->getType(),
                           'items_id'  => $this->getID()];
            Ajax::updateItemJsCode("viewplan$rand_text", $CFG_GLPI["root_doc"] . "/ajax/planning.php",
                                 $params);
            echo "}";
            echo "</script>\n";
            echo "<div id='plan$rand_text' onClick='showPlan".$ID.$rand_text."()'>\n";
            echo "<span class='showplan'>";
         }

         if (isset($this->fields["state"])) {
            echo Planning::getState($this->fields["state"])."<br>";
         }
         printf(__('From %1$s to %2$s'), Html::convDateTime($this->fields["begin"]),
               Html::convDateTime($this->fields["end"]));
         if (isset($this->fields["users_id_tech"]) && ($this->fields["users_id_tech"] > 0)) {
            echo "<br>".getUserName($this->fields["users_id_tech"]);
         }
         if (isset($this->fields["groups_id_tech"]) && ($this->fields["groups_id_tech"] > 0)) {
            echo "<br>".Dropdown::getDropdownName('glpi_groups', $this->fields["groups_id_tech"]);
         }
         if (Session::haveRight('planning', Planning::READMY)) {
            echo "</span>";
            echo "</div>\n";
            echo "<div id='viewplan$rand_text'></div>\n";
         }

      } else {
         if ($canplan) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlanUpdate$rand_text() {\n";
            echo Html::jsHide("plan$rand_text");
            $params = ['action'    => 'add_event_classic_form',
                           'form'      => 'followups',
                           'entity'    => $item->fields['entities_id'],
                           'rand_user' => $rand_user,
                           'rand_group' => $rand_group,
                           'itemtype'  => $this->getType(),
                           'items_id'  => $this->getID()];
            Ajax::updateItemJsCode("viewplan$rand_text", $CFG_GLPI["root_doc"]."/ajax/planning.php",
                                 $params);
            echo "};";
            echo "</script>";

            if ($canplan) {
               echo "<div id='plan$rand_text'  onClick='showPlanUpdate$rand_text()'>\n";
               echo "<span class='vsubmit'>".__('Plan this task')."</span>";
               echo "</div>\n";
               echo "<div id='viewplan$rand_text'></div>\n";
            }
         } else {
            echo __('None');
         }
      }

      echo "</td></tr>";

      if (!empty($this->fields["begin"])
         && PlanningRecall::isAvailable()) {

         echo "<tr class='tab_bg_1'><td>"._x('Planning', 'Reminder')."</td><td class='center'>";
         PlanningRecall::dropdown(['itemtype' => $this->getType(),
                                       'items_id' => $this->getID()]);
         echo "</td><td colspan='2'></td></tr>";
      }

      $this->showFormButtons($options);

      return true;
   }

    /**
    * Select a field using standard system
    *
    * @since 0.83
    *
    * @param integer|string|array $field_id_or_search_options id of the search option field
    *                                                             or field name
    *                                                             or search option array
    * @param string               $name                       name of the select (if empty use linkfield)
    *                                                         (default '')
    * @param mixed                $values                     default value to display
    *                                                         (default '')
    * @param array                $options                    array of possible options:
    * Parameters which could be used in options array :
    *    - comments : boolean / is the comments displayed near the value (default false)
    *    - any others options passed to specific display method
    *
    * @return string the string to display
   **/
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
               return  PluginServicesDropdown::show($itemtype, $options);
               // return $itemtype::dropdown($options);

            case "right" :
               return PluginServicesProfile::dropdownRights(Profile::getRightsFor($searchoptions['rightclass']),
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
            $classname = 'PluginServices'.$itemtype;
            if (class_exists($classname )) {
               $item = new $classname();
            }
            
            $specific = self::getSpecificValueToSelect($searchoptions['field'], $name, $values, $options);
            // $specific = $item->getSpecificValueToSelect($searchoptions['field'], $name,
            //                                           $values, $options);
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
    * @since 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'state':
            return PluginServicesPlanning::dropdownState($name, $values[$field], false);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * @since 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'state' :
            return PluginServicesPlanning::getState($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'content',
         'name'               => __('Description'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_taskcategories',
         'field'              => 'name',
         'name'               => _n('Task category', 'Task categories', 1),
         'forcegroupby'       => true,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'date',
         'name'               => _n('Date', 'Dates', 1),
         'datatype'           => 'datetime'
      ];

      if ($this->maybePrivate()) {
         $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'is_private',
            'name'               => __('Public followup'),
            'datatype'           => 'bool'
         ];
      }

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Technician'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'actiontime',
         'name'               => __('Total duration'),
         'datatype'           => 'actiontime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'state',
         'name'               => __('Status'),
         'datatype'           => 'specific'
      ];
      // $tab = array_merge($tab, self::rawSearchOptionsToAdd());
      $tab = self::rawSearchOptionsToAdd();
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
         'id'                 => 'task',
         'name'               => $name
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => static::getTable(),
         'field'              => 'name',
         'name'               => __('Title'),
         'datatype'           => 'itemlink',
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => true,
         'htmltext'           => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => $task_condition,
         ]
      ];

      $tab[] = [
         'id'                 => '26',
         'table'              => static::getTable(),
         'field'              => 'content',
         'name'               => __('Description'),
         'datatype'           => 'text',
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => true,
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
         'massiveaction'      => true,
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
            'massiveaction'      => true,
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
         'massiveaction'      => true,
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
         'massiveaction'      => true,
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
         'massiveaction'      => true,
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
         'massiveaction'      => true,
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
         'table'              => Ticket::getTable(),
         'field'              => 'name',
         'linkfield'          => 'tickets_id',
         'name'               => Ticket::getTypeName(1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
      ];
      return $tab;
   }


   static function getGroupUserHaveRights(array $options = []) {
      $params = [
         'entity' => $_SESSION['glpiactive_entity'],
         'right' => 'own_ticket'
      ];


      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $list       = [];
      $restrict   = [];

      $res = User::getSqlSearchResult(false, $params['right'], $params['entity']);
      while ($data = $res->next()) {
         $list[] = $data['id'];
      }
      if (count($list) > 0) {
         $restrict = ['glpi_users.id' => $list];
      }
      $users = Group_User::getGroupUsers($params['groups_id'], $restrict);
      return $users;
   }
}  
