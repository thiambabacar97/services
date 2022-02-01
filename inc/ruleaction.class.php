<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginServicesRuleAction extends RuleAction {

   // From CommonDBChild
   static public $itemtype        = "Rule";
   static public $items_id        = 'rules_id';
   public $dohistory              = true;
   public $auto_message_on_action = false;

   /**
    * @since 0.84
   **/

    static function getFormURL($full = false) {
        return PluginServicesToolbox::getItemTypeFormURL(get_called_class(), $full);
    }
    function getForbiddenStandardMassiveAction() {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


   /**
    * @param $rule_type
   **/
   function __construct($rule_type = 'Rule') {
      static::$itemtype = $rule_type;
   }


   /**
    * @since 0.84.3
    *
    * @see CommonDBTM::post_getFromDB()
    */
   function post_getFromDB() {

      // Get correct itemtype if defult one is used
      if (static::$itemtype == 'Rule') {
         $rule = new Rule();
         if ($rule->getFromDB($this->fields['rules_id'])) {
            static::$itemtype = $rule->fields['sub_type'];
         }
      }
   }


   /**
    * Get title used in rule
    *
    * @param $nb  integer  (default 0)
    *
    * @return Title of the rule
   **/
   static function getTypeName($nb = 0) {
      return _n('Action', 'Actions', $nb);
   }
   static function getTable($classname = null) {
    return "glpi_ruleactions";
 }

   protected function computeFriendlyName() {

      if ($rule = getItemForItemtype(static::$itemtype)) {
         return Html::clean($rule->getMinimalActionText($this->fields));
      }
      return '';
   }

   /**
    * @since 0.84
    *
    * @see CommonDBChild::post_addItem()
   **/
   function post_addItem() {

      parent::post_addItem();
      if (isset($this->input['rules_id'])
          && ($realrule = Rule::getRuleObjectByID($this->input['rules_id']))) {
         $realrule->update(['id'       => $this->input['rules_id'],
                                 'date_mod' => $_SESSION['glpi_currenttime']]);
      }
   }


   /**
    * @since 0.84
    *
    * @see CommonDBTM::post_purgeItem()
   **/
   function post_purgeItem() {

      parent::post_purgeItem();
      if (isset($this->fields['rules_id'])
          && ($realrule = Rule::getRuleObjectByID($this->fields['rules_id']))) {
         $realrule->update(['id'       => $this->fields['rules_id'],
                                 'date_mod' => $_SESSION['glpi_currenttime']]);
      }
   }


   /**
    * @since 0.84
   **/
   function prepareInputForAdd($input) {

      if (!isset($input['field']) || empty($input['field'])) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'field',
         'name'               => _n('Field', 'Fields', Session::getPluralNumber()),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'additionalfields'   => ['rules_id', 'action_type']
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'action_type',
         'name'               => self::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'additionalfields'   => ['rules_id', 'field']
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'value',
         'name'               => __('Value'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'additionalfields'   => ['rules_id', 'action_type', 'field'],
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_rules',
         'field'              => 'name',
         'linkfield'          => 'rules_id',
         'name'               => Rule::getTypeName(Session::getPluralNumber()),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'itemlink'
      ];
      return $tab;
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
         case 'field' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                  && !empty($values['rules_id'])
                  && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  return $rule->getActionName($values[$field]);
                  // return $rule->getAction($values[$field]);
               }
            }
            break;

         case 'action_type' :
            return self::getActionByID($values[$field]);

         case 'value' :
            if (!isset($values["field"]) || !isset($values["action_type"])) {
               return NOT_AVAILABLE;
            }
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                  && !empty($values['rules_id'])
                  && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  return $rule->getActionValue($values["field"], $values['action_type'], $values["value"]);
                  // return $generic_rule->getActionValue($values["field"], $values['action_type'], $values["value"]);
               }
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * @param $options   array
   **/
   function displayActionSelectPattern($options = []) {

      $display = false;
      $param = [
         'value' => '',
      ];
      if (isset($options['value'])) {
         $param['value'] = $options['value'];
      }

      switch ($options["action_type"]) {
         //If a regex value is used, then always display an autocompletiontextfield
         case "regex_result" :
         case "append_regex_result" :
            PluginServicesHtml::autocompletionTextField($this, "value", $param);
            break;

         case 'fromuser' :
         case 'defaultfromuser' :
         case 'fromitem' :
            PluginServicesDropdown::showYesNo("value", $param['value'], 0);
            $display = true;
            break;

         default :
            $actions = Rule::getActionsByType($options["sub_type"]);
            if (isset($actions[$options["field"]]['type'])) {

               switch ($actions[$options["field"]]['type']) {
                  case "dropdown" :
                     $table   = $actions[$options["field"]]['table'];
                     $param['name'] = "value";
                     if (isset($actions[$options["field"]]['condition'])) {
                        $param['condition'] = $actions[$options["field"]]['condition'];
                     }
                     PluginServicesDropdown::show(getItemTypeForTable($table), $param);
                     $display = true;
                     break;

                  case "dropdown_tickettype" :
                     $param['width'] = '87%';
                     PluginServicesTicket::dropdownType('value', $param);
                     $display = true;
                     break;

                  case "dropdown_assign" :
                     $param['name']  = 'value';
                     $param['right'] = 'own_ticket';
                     PluginServicesUser::dropdown($param);
                     $display = true;
                     break;

                  case "dropdown_users" :
                     $param['name']  = 'value';
                     $param['right'] = 'all';
                     PluginServicesUser::dropdown($param);
                     $display = true;
                     break;

                  case "dropdown_urgency" :
                     $param['name']  = 'value';
                     $param['width'] = '74%';
                     PluginServicesTicket::dropdownUrgency($param);
                     $display = true;
                     break;

                  case "dropdown_impact" :
                     $param['name']  = 'value';
                     $param['width'] = '79%';
                     PluginServicesTicket::dropdownImpact($param);
                     $display = true;
                     break;

                  case "dropdown_priority" :
                     if ($_POST["action_type"] != 'compute') {
                        $param['name']  = 'value';
                        $param['width'] = '79%';
                        PluginServicesTicket::dropdownPriority($param);
                     }
                     $display = true;
                     break;

                  case "dropdown_status" :
                     $param['name']  = 'value';
                     $param['width'] = '79%';
                     PluginServicesTicket::dropdownStatus($param);
                     $display = true;
                     break;

                  case "yesonly" :
                     PluginServicesDropdown::showYesNo("value", $param['value'], 0);
                     $display = true;
                     break;

                  case "yesno" :
                     PluginServicesDropdown::showYesNo("value", $param['value']);
                     $display = true;
                     break;

                  case "dropdown_management":
                     $param['name']                 = 'value';
                     $param['management_restrict']  = 2;
                     $param['withtemplate']         = false;
                     PluginServicesDropdown::showGlobalSwitch(0, $param);
                     $display = true;
                     break;

                  case "dropdown_users_validate" :
                     $used = [];
                     if ($item = getItemForItemtype($options["sub_type"])) {
                        $rule_data = getAllDataFromTable(
                           self::getTable(), [
                              'action_type'           => 'add_validation',
                              'field'                 => 'users_id_validate',
                              $item->getRuleIdField() => $options[$item->getRuleIdField()]
                           ]
                        );

                        foreach ($rule_data as $data) {
                           $used[] = $data['value'];
                        }
                     }
                     $param['name']  = 'value';
                     $param['right'] = ['validate_incident', 'validate_request'];
                     $param['used']  = $used;
                     $param['width'] = '108%';
                     PluginServicesUser::dropdown($param);
                     $display        = true;
                     break;

                  case "dropdown_groups_validate" :
                     $used = [];
                     if ($item = getItemForItemtype($options["sub_type"])) {
                        $rule_data = getAllDataFromTable(
                           self::getTable(), [
                              'action_type'           => 'add_validation',
                              'field'                 => 'groups_id_validate',
                              $item->getRuleIdField() => $options[$item->getRuleIdField()]
                           ]
                        );
                        foreach ($rule_data as $data) {
                           $used[] = $data['value'];
                        }
                     }

                     $param['name']      = 'value';
                     $param['condition'] = [new QuerySubQuery([
                        'SELECT' => ['COUNT' => ['users_id']],
                        'FROM'   => 'glpi_groups_users',
                        'WHERE'  => ['groups_id' => new \QueryExpression('glpi_groups.id')]
                     ])];
                     $param['right']     = ['validate_incident', 'validate_request'];
                     $param['used']      = $used;
                     PluginServicesGroup::dropdown($param);
                     $display            = true;
                     break;

                  case "dropdown_validation_percent" :
                     $ticket = new Ticket();
                     echo $ticket->getValueToSelect('validation_percent', 'value', $param['value']);
                     $display       = true;
                     break;

                  default :
                     if ($rule = getItemForItemtype($options["sub_type"])) {
                        $display = $rule->displayAdditionalRuleAction($actions[$options["field"]], $param['value']);
                     }
                     break;
               }
            }

            if (!$display) {
               PluginServicesHtml::autocompletionTextField($this, "value", $param);
            }
      }
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'field' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  $options['value'] = $values[$field];
                  $options['name']  = $name;
                  return $rule->dropdownActions($options);
               }
            }
            break;

         case 'action_type' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               return self::dropdownActions(['subtype'     => $generic_rule->fields["sub_type"],
                                                  'name'        => $name,
                                                  'value'       => $values[$field],
                                                  'alreadyused' => false,
                                                  'display'     => false]);
            }
            break;

         case 'pattern' :
            if (!isset($values["field"]) || !isset($values["action_type"])) {
               return NOT_AVAILABLE;
            }
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  /// TODO review it : need to pass display param and others...
                  return $rule->displayActionSelectPattern($values);
               }
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Get all actions for a given rule
    *
    * @param $ID the rule_description ID
    *
    * @return an array of RuleAction objects
   **/
   function getRuleActions($ID) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => $this->getTable(),
         'WHERE'  => [static::$items_id => $ID],
         'ORDER'  => 'id'
      ]);

      $rules_actions = [];
      while ($rule = $iterator->next()) {
         $tmp             = new self();
         $tmp->fields     = $rule;
         $rules_actions[] = $tmp;
      }
      return $rules_actions;
   }


   /**
    * Add an action
    *
    * @param $action    action type
    * @param $ruleid    rule ID
    * @param $field     field name
    * @param $value     value
   **/
   function addActionByAttributes($action, $ruleid, $field, $value) {

      $input = [
         'action_type'     => $action,
         'field'           => $field,
         'value'           => $value,
         static::$items_id => $ruleid,
      ];
      $this->add($input);
   }


   /**
    * Display a dropdown with all the possible actions
    *
    * @param $options   array of possible options:
    *    - subtype
    *    - name
    *    - field
    *    - value
    *    - alreadyused
    *    - display
   **/
    static function dropdownActions($options = []) {

      $p = [
         'subtype'     => '',
         'name'        => '',
         'field'       => '',
         'value'       => '',
         'width'       => '50%',
         'alreadyused' => false,
         'display'     => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
               $p[$key] = $val;
         }
      }

      if ($rule = getItemForItemtype($p['subtype'])) {
         $actions_options = $rule->getAllActions();
         $actions         = ["assign"];
         // Manage permit several.
         $field = $p['field'];
         if ($p['alreadyused']) {
               if (!isset($actions_options[$field]['permitseveral'])) {
               return false;
               }
               $actions = $actions_options[$field]['permitseveral'];

         } else {
               if (isset($actions_options[$field]['force_actions'])) {
               $actions = $actions_options[$field]['force_actions'];
               }
         }

         $elements = [];
         foreach ($actions as $action) {
               $elements[$action] = self::getActionByID($action);
         }

         return PluginServicesDropdown::showFromArray($p['name'], $elements, ['value'   => $p['value'],
                                                                     'display' => $p['display']]);
      }
    }


   static function getActions() {

      return ['assign'              => __('Assign'),
                   'append'              => __('Add'),
                   'regex_result'        => __('Assign the value from regular expression'),
                   'append_regex_result' => __('Add the result of regular expression'),
                   'affectbyip'          => __('Assign: equipment by IP address'),
                   'affectbyfqdn'        => __('Assign: equipment by name + domain'),
                   'affectbymac'         => __('Assign: equipment by MAC address'),
                   'compute'             => __('Recalculate'),
                   'do_not_compute'      => __('Do not calculate'),
                   'send'                => __('Send'),
                   'add_validation'      => __('Send'),
                   'fromuser'            => __('Copy from user'),
                   'defaultfromuser'     => __('Copy default from user'),
                   'fromitem'            => __('Copy from item')];
   }


   /**
    * @param $ID
   **/
   static function getActionByID($ID) {

      $actions = self::getActions();
      if (isset($actions[$ID])) {
         return $actions[$ID];
      }
      return '';
   }


   /**
    * @param $action
    * @param $regex_result
   **/
   static function getRegexResultById($action, $regex_result) {

      $results = [];

      if (count($regex_result) > 0) {
         if (preg_match_all("/#([0-9])/", $action, $results) > 0) {
            foreach ($results[1] as $result) {
               $action = str_replace("#$result",
                                     (isset($regex_result[$result])?$regex_result[$result]:''),
                                     $action);
            }
         }
      }
      return $action;
   }

   function postForm($post){
      $rule = new Rule;
      $rule->getFromDB(intval($_POST['rules_id']));

      $action = new RuleAction($rule->fields['sub_type']);

      if (isset($_POST["add"])) {
         $action->check(-1, CREATE, $_POST);
         $action->add($_POST);
         $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $rule->fields['sub_type']);
         Html::redirect($backurl);
      } else if (isset($_POST["update"])) {
         $action->check($_POST['id'], UPDATE);
         $action->update($_POST);
         $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $rule->fields['sub_type']);
         Html::redirect($backurl);
      } else if (isset($_POST["purge"])) {
         $action->check($_POST['id'], PURGE);
         $action->delete($_POST, 1);

         $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $rule->fields['sub_type']);
         Html::redirect($backurl);
      }

   }


   /**
    * @param $rules_id
    * @param $sub_type
   **/
   function getAlreadyUsedForRuleID($rules_id, $sub_type) {
      global $DB;

      if ($rule = getItemForItemtype($sub_type)) {
         $actions_options = $rule->getAllActions();

         $actions = [];
         $iterator = $DB->request([
            'SELECT' => 'field',
            'FROM'   => $this->getTable(),
            'WHERE'  => [static::$items_id => $rules_id],
         ]);

         while ($action = $iterator->next()) {
            if (isset($actions_options[$action["field"]])
                 && ($action["field"] != 'groups_id_validate')
                 && ($action["field"] != 'users_id_validate')
                 && ($action["field"] != 'affectobject')) {
               $actions[$action["field"]] = $action["field"];
            }
         }
         return $actions;
      }
   }

      /**
    * Display a 2 columns Footer for Form buttons
    * Close the form is user can edit
    *
    * @param array $options array of possible options:
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *     - colspan for each column (default 2)
    *     - candel : set to false to hide "delete" button
    *     - canedit : set to false to hide all buttons
    *     - addbuttons : array of buttons to add
    *
    * @return void
   **/
   function showFormButtons($options = []) {

      // for single object like config
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      } else {
         $ID = 1;
      }

      $params = [
         'colspan'      => 2,
         'withtemplate' => '',
         'candel'       => true,
         'canedit'      => true,
         'addbuttons'   => [],
         'formfooter'   => null,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      Plugin::doHook("post_item_form", ['item' => $this, 'options' => &$params]);

      if ($params['formfooter'] === null) {
         $this->showDates($params);
      }

      if (!$params['canedit']
         || !$this->canEdit($ID)) {
         echo "</table></div>";
         // Form Header always open form
         Html::closeForm();
         return false;
      }

      // echo "<tr class='tab_bg_2'>";

      if ($params['withtemplate']
         ||$this->isNewID($ID)) {

         echo "<td class='center' colspan='".($params['colspan']*2)."'>";

         if (($ID <= 0) || ($params['withtemplate'] == 2)) {
            
            // echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='btsubmit'>";
            echo PluginServicesHtml::submit(
                  _x('button', 'Soumettre'),
               ['name' => 'add']
            );
         } else {
            //TRANS : means update / actualize
            
            echo PluginServicesHtml::submit(_x('button', 'Soumettre'),
               ['name' => 'update']
            );
         }

      } else {
         if ($params['candel']
            && !$this->can($ID, DELETE)
            && !$this->can($ID, PURGE)) {
            $params['candel'] = false;
         }

         if ($params['canedit'] && $this->can($ID, UPDATE)) {
            echo "<td class='center' colspan='".($params['colspan']*2)."'>\n";
            echo PluginServicesHtml::submit(_x('button', 'Soumettre'),
               ['name' => 'update']
            );
         }

         if ($params['candel']) {
            if ($params['canedit'] && $this->can($ID, UPDATE)) {
               echo "</td></tr><tr class='tab_bg_2'>\n";
            }

         }
         if ($this->isField('date_mod')) {
            echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
         }
      }

      if (!$this->isNewID($ID)) {
         echo "<input type='hidden' name='id' value='$ID'>";
      }
      echo "</td>";
      echo "</tr>\n";

      if ($params['canedit']
         && count($params['addbuttons'])) {
         echo "<tr class='tab_bg_2'>";
         echo "<td class='right' colspan='".($params['colspan']*2)."'>";
         foreach ($params['addbuttons'] as $key => $val) {
            echo "<button type='submit' class='vsubmit' name='$key' value='1'>
                  $val
               </button>&nbsp;";
         }
         echo "</td>";
         echo "</tr>";
      }

      // Close for Form
      echo "</table></div>";
      Html::closeForm();
   }


      /**
    *
    * Display a 2 columns Header 1 for ID, 1 for recursivity menu
    * Open the form is user can edit
    *
    * @param array $options array of possible options:
    *     - target for the Form
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *     - colspan for each column (default 2)
    *     - formoptions string (javascript p.e.)
    *     - canedit boolean edit mode of form ?
    *     - formtitle specific form title
    *     - noid Set to true if ID should not be append (eg. already done in formtitle)
    *
    * @return void
   **/
   function showFormHeader($options = []) {

      $ID     = $this->fields['id'];
      $params = [
         'target'       => $this->getFormURL(),
         'colspan'      => 2,
         'withtemplate' => '',
         'formoptions'  => '',
         'canedit'      => true,
         'formtitle'    => null,
         'noid'         => false
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      // Template case : clean entities data
      if (($params['withtemplate'] == 2)
         && $this->isEntityAssign()) {
         $this->fields['entities_id']  = $_SESSION['glpiactive_entity'];
      }

      $rand = mt_rand();
      if ($this->canEdit($ID)) {
         echo "<form name='form' method='post' action='' ".
               $params['formoptions']." enctype=\"multipart/form-data\">";

         //Should add an hidden entities_id field ?
         //If the table has an entities_id field
         if ($this->isField("entities_id")) {
            //The object type can be assigned to an entity
            if ($this->isEntityAssign()) {
               if (isset($params['entities_id'])) {
                  $entity = $this->fields['entities_id'] = $params['entities_id'];
               } else if (isset($this->fields['entities_id'])) {
                  //It's an existing object to be displayed
                  $entity = $this->fields['entities_id'];
               } else if ($this->isNewID($ID)
                        || ($params['withtemplate'] == 2)) {
                  //It's a new object to be added
                  $entity = $_SESSION['glpiactive_entity'];
               }

               echo "<input type='hidden' name='entities_id' value='$entity'>";

            } else if ($this->getType() != 'User') {
               // For Rules except ruleticket and slalevel
               echo "<input type='hidden' name='entities_id' value='0'>";

            }
         }
      }

      // echo "<div class='spaced' id='tabsbody'>";
      echo "<div class='spaced' id='tabsbody'>";
      // echo "<div class='col-xl-3 mt-2'>";
      echo "<table class='tab_cadre_fixe' id='mainformtable'>";

      if ($params['formtitle'] !== '' && $params['formtitle'] !== false) {

         if (!empty($params['withtemplate']) && ($params['withtemplate'] == 2)
            && !$this->isNewID($ID)) {

            echo "<input type='hidden' name='template_name' value='".$this->fields["template_name"]."'>";

            //TRANS: %s is the template name
            // printf(__('Created from the template %s'), $this->fields["template_name"]);

         } else if (!empty($params['withtemplate']) && ($params['withtemplate'] == 1)) {
            echo "<input type='hidden' name='is_template' value='1'>\n";
            // echo "<label for='textfield_template_name$rand'>" . __('Template name') . "</label>";
            // PluginServicesHtml::autocompletionTextField(
            //    $this,
            //    'template_name',
            //    [
            //       'size'      => 25,
            //       'required'  => true,
            //       'rand'      => $rand
            //    ]
            // );
         } else if ($this->isNewID($ID)) {
            $nametype = $params['formtitle'] !== null ? $params['formtitle'] : $this->getTypeName(1);
            // printf(__('%1$s - %2$s'), __('New item'), $nametype);
         } else {
            $nametype = $params['formtitle'] !== null ? $params['formtitle'] : $this->getTypeName(1);
            if (!$params['noid'] && ($_SESSION['glpiis_ids_visible'] || empty($nametype))) {
               //TRANS: %1$s is the Itemtype name and $2$d the ID of the item
               $nametype = sprintf(__('%1$s - ID %2$d'), $nametype, $ID);
            }
            // echo $nametype;
         }
         $entityname = '';
         if (isset($this->fields["entities_id"])
            && Session::isMultiEntitiesMode()
            && $this->isEntityAssign()) {
            $entityname = PluginServicesDropdown::getDropdownName("glpi_entities", $this->fields["entities_id"]);
         }

         // echo "</th><th colspan='".$params['colspan']."'>";
         if (get_class($this) != 'Entity') {
            if ($this->maybeRecursive()) {
               if (Session::isMultiEntitiesMode()) {
                  // echo "<table class='tab_format'><tr class='headerRow responsive_hidden'><th>".$entityname."</th>";
                  // echo "<th class='right'><label for='dropdown_is_recursive$rand'>".__('Child entities')."</label></th><th>";
                  // if ($params['canedit']) {
                  //    if ($this instanceof CommonDBChild) {
                  //       echo Dropdown::getYesNo($this->isRecursive());
                  //       if (isset($this->fields["is_recursive"])) {
                  //          echo "<input type='hidden' name='is_recursive' value='".$this->fields["is_recursive"]."'>";
                  //       }
                  //       $comment = __("Can't change this attribute. It's inherited from its parent.");
                  //       // CommonDBChild : entity data is get or copy from parent

                  //    } else if (!$this->can($ID, 'recursive')) {
                  //       echo Dropdown::getYesNo($this->fields["is_recursive"]);
                  //       $comment = __('You are not allowed to change the visibility flag for child entities.');

                  //    } else if (!$this->canUnrecurs()) {
                  //       echo Dropdown::getYesNo($this->fields["is_recursive"]);
                  //       $comment = __('Flag change forbidden. Linked items found.');

                  //    } else {
                  //       Dropdown::showYesNo("is_recursive", $this->fields["is_recursive"], -1, ['rand' => $rand]);
                  //       $comment = __('Change visibility in child entities');
                  //    }
                  //    echo " ";
                  //    Html::showToolTip($comment);
                  // } else {
                  //    echo Dropdown::getYesNo($this->fields["is_recursive"]);
                  // }
                  // echo "</th></tr></table>";
               } else {
                  echo $entityname;
                  echo "<input type='hidden' name='is_recursive' value='0'>";
               }
            } else {
               // echo $entityname;
            }
         }
         // echo "</th></tr>\n";
      }

      Plugin::doHook("pre_item_form", ['item' => $this, 'options' => &$params]);

      // If in modal : do not display link on message after redirect
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         echo "<input type='hidden' name='_no_message_link' value='1'>";
      }

   }


   /** form for rule action
    *
    * @since 0.85
    *
    * @param $ID      integer : Id of the action
    * @param $options array of possible options:
    *     - rule Object : the rule
   **/
   function showForm1($ID, $options = []) {
      global $CFG_GLPI;

      // Yllen: you always have parent for action
      $rule = new PluginServicesRuleTicket();
      $parentid = (isset($_GET['parentid'])) ? $_GET['parentid'] : $_GET['id'] ;
      $rule->getFromDB($parentid);

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[static::$items_id] = $rule->getField('id');

         //force itemtype of parent
         static::$itemtype = get_class($rule);

         $this->check(-1, CREATE, $options);
      }
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1 center'>";
      echo "<td width='20%'>"._n('Action', 'Actions', 1) . "</td><td colspan='3'>";
      echo "<input type='hidden' name='".$rule->getRuleIdField()."' value='".
            $this->fields[static::$items_id]."'>";
      $used = $this->getAlreadyUsedForRuleID($this->fields[static::$items_id], $rule->getType());
      // On edit : unset selected value
      if ($ID
         && isset($used[$this->fields['field']])) {
         unset($used[$this->fields['field']]);
      }
      $rand   = $rule->dropdownActions(['value' => $this->fields['field'],
                                             'used'  => $used]);
      $params = ['field'                 => '__VALUE__',
                     'sub_type'              => $rule->getType(),
                     'ruleactions_id'        => $this->getID(),
                     $rule->getRuleIdField() => $this->fields[static::$items_id]];

      Ajax::updateItemOnSelectEvent("dropdown_field$rand", "action_span",
                                    $CFG_GLPI["root_doc"]."/ajax/ruleaction.php", $params);

      if (isset($this->fields['field']) && !empty($this->fields['field'])) {
         $params['field']       = $this->fields['field'];
         $params['action_type'] = $this->fields['action_type'];
         $params['value']       = $this->fields['value'];
         echo "<script type='text/javascript' >\n";
         echo "$(function() {";
         Ajax::updateItemJsCode("action_span",
                                 $CFG_GLPI["root_doc"]."/ajax/ruleaction.php",
                                 $params);
         echo '});</script>';
      }
      echo "</td></tr>";
      echo "<tr><td colspan='4'><span id='action_span'>\n";
      echo "</span></td>\n";
      echo "</tr>\n";
      $this->showFormButtons($options);
   }

   function showForm($ID,  $options = []) {
      echo'<div class="m-content">';
         echo'<div class="row">';
            echo'<div class="col-lg-12">';
               echo'<div class="m-portlet">';
                  echo'<div class="m-portlet__head">';
                     echo'<div class="m-portlet__head-caption">';
                     echo'<div class="m-portlet__head-title">';
                        echo'<h3 class="m-portlet__head-text">Ajouter une nouvelle action</h3>';
                     echo'</div>';
                     echo'</div>';
                  echo'</div>';
                  echo'<div class="m-portlet__body">';
                     echo'<div class="row justify-content-center">';
                        echo'<div class="col-sm-8">';
                           $this->showForm1($ID, $options);
                        echo'</div>';
                     echo'</div>';
                  echo'</div>';
               echo'</div>';
            echo'</div>';
         echo'</div>';
      echo'</div>';
   }

}
