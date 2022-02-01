<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Criteria Rule class
class PluginServicesRuleCriteria extends RuleCriteria {

   // From CommonDBChild

   static function getFormURL($full = false) {
      return PluginServicesToolbox::getItemTypeFormURL(get_called_class(), $full);
   }
   /**
    * @since 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   static function getTable($classname = null) {
      return "glpi_rulecriterias";
   }


   /**
    * @param $rule_type (default 'Rule)
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
    * @param $nb  integer  for singular or plural (default 0)
    *
    * @return Title of the rule
   **/
   static function getTypeName($nb = 0) {
      return _n('Criterion', 'Criteria', $nb);
   }

   protected function computeFriendlyName() {

      if ($rule = getItemForItemtype(static::$itemtype)) {
         return Html::clean($rule->getMinimalCriteriaText($this->fields));
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

   function postForm($post){
      $rule = new Rule;
      $rule->getFromDB(intval($_POST['rules_id']));
      $criteria = new RuleCriteria($rule->fields['sub_type']);
      if (isset($_POST["add"])) {
         $criteria->check(-1, CREATE, $_POST);
         $criteria->add($_POST);
         $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $rule->fields['sub_type']);
         Html::redirect($backurl);

      } else if (isset($_POST["update"])) {
         $criteria->check($_POST['id'], UPDATE);
         $criteria->update($_POST);

         $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $rule->fields['sub_type']);
         Html::redirect($backurl);

      } else if (isset($_POST["purge"])) {
         $criteria->check($_POST['id'], PURGE);
         $criteria->delete($_POST, 1);

         $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $rule->fields['sub_type']);
         Html::redirect($backurl);
      }

   }


   /**
    * @since 0.84
   **/
   function prepareInputForAdd($input) {

      if (!isset($input['criteria']) || empty($input['criteria'])) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   function rawSearchOptions() {
      $tab = [];
      
      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'criteria',
         'name'               => __('Name'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'additionalfields'   => ['rules_id']
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'condition',
         'name'               => __('Condition'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'additionalfields'   => ['rules_id', 'criteria']
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'pattern',
         'name'               => __('Reason'),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'additionalfields'   => ['rules_id', 'criteria', 'condition'],
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
         case 'criteria' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  return $rule->getCriteriaName($values[$field]);
               }
            }
            break;

         case 'condition' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if (isset($values['criteria']) && !empty($values['criteria'])) {
                  $criterion = $values['criteria'];
               }
               return self::getConditionByID($values[$field], $generic_rule->fields["sub_type"], $criterion);
            }
            break;

         case 'pattern' :
            if (!isset($values["criteria"]) || !isset($values["condition"])) {
               return NOT_AVAILABLE;
            }
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  return $rule->getCriteriaDisplayPattern($values["criteria"], $values["condition"],
                                                          $values[$field]);
               }
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
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
      echo $field;
      switch ($field) {
         case 'criteria' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  $options['value'] = $values[$field];
                  $options['name']  = $name;
                  return $rule->dropdownCriteria($options);
               }
            }
            break;

         case 'condition' :
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if (isset($values['criteria']) && !empty($values['criteria'])) {
                  $options['criterion'] = $values['criteria'];
               }
               $options['value'] = $values[$field];
               $options['name']  = $name;
               return $rule->dropdownConditions($generic_rule->fields["sub_type"], $options);
            }
            break;

         case 'pattern' :
            if (!isset($values["criteria"]) || !isset($values["condition"])) {
               return NOT_AVAILABLE;
            }
            $generic_rule = new Rule;
            if (isset($values['rules_id'])
                && !empty($values['rules_id'])
                && $generic_rule->getFromDB($values['rules_id'])) {
               if ($rule = getItemForItemtype($generic_rule->fields["sub_type"])) {
                  /// TODO : manage display param to this function : need to send ot to all under functions
                  $rule->displayCriteriaSelectPattern($name, $values["criteria"],
                                                      $values["condition"], $values[$field]);
               }
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Get all criterias for a given rule
    *
    * @param $rules_id the rule ID
    *
    * @return an array of RuleCriteria objects
   **/
   function getRuleCriterias($rules_id) {
      global $DB;

      $rules_list = [];
      $params = ['FROM'  => $this->getTable(),
                 'WHERE' => [static::$items_id => $rules_id],
                 'ORDER' => 'id'
                ];
      foreach ($DB->request($params) as $rule) {
         $tmp          = new self();
         $tmp->fields  = $rule;
         $rules_list[] = $tmp;
      }
      return $rules_list;
   }

   /**
    * Return the condition label by giving his ID
    *
    * @param $ID        condition's ID
    * @param $itemtype  itemtype
    * @param $criterion (default '')
    *
    * @return condition's label
   **/
   static function getConditionByID($ID, $itemtype, $criterion = '') {

      $conditions = self::getConditions($itemtype, $criterion);
      if (isset($conditions[$ID])) {
         return $conditions[$ID];
      }
      return "";
   }


   /**
    * @param $itemtype  itemtype
    * @param $criterion (default '')
    *
    * @return array of criteria
   **/
   static function getConditions($itemtype, $criterion = '') {

      $criteria =  [Rule::PATTERN_IS              => __('is'),
                         Rule::PATTERN_IS_NOT          => __('is not'),
                         Rule::PATTERN_CONTAIN         => __('contains'),
                         Rule::PATTERN_NOT_CONTAIN     => __('does not contain'),
                         Rule::PATTERN_BEGIN           => __('starting with'),
                         Rule::PATTERN_END             => __('finished by'),
                         Rule::REGEX_MATCH             => __('regular expression matches'),
                         Rule::REGEX_NOT_MATCH         => __('regular expression does not match'),
                         Rule::PATTERN_EXISTS          => __('exists'),
                         Rule::PATTERN_DOES_NOT_EXISTS => __('does not exist')];

      $extra_criteria = call_user_func([$itemtype, 'addMoreCriteria'], $criterion);

      foreach ($extra_criteria as $key => $value) {
         $criteria[$key] = $value;
      }

      /// Add Under criteria if tree dropdown table used
      if ($item = getItemForItemtype($itemtype)) {
         $crit = $item->getCriteria($criterion);

         if (isset($crit['type']) && ($crit['type'] == 'dropdown')) {
            $crititemtype = getItemTypeForTable($crit['table']);

            if (($item = getItemForItemtype($crititemtype))
                && $item instanceof CommonTreeDropdown) {
               $criteria[Rule::PATTERN_UNDER]     = __('under');
               $criteria[Rule::PATTERN_NOT_UNDER] = __('not under');
            }
         }
      }

      return $criteria;
   }


   /**
    * Display a dropdown with all the criterias
    *
    * @param $itemtype
    * @param $params    array
   **/
   static function dropdownConditions($itemtype, $params = []) {

      $p['name']             = 'condition';
      $p['criterion']        = '';
      $p['allow_conditions'] = [];
      $p['value']            = '';
      $p['display']          = true;

      foreach ($params as $key => $value) {
         $p[$key] = $value;
      }
      $elements = [];
      foreach (self::getConditions($itemtype, $p['criterion']) as $pattern => $label) {
         if (empty($p['allow_conditions'])
             || (!empty($p['allow_conditions']) && in_array($pattern, $p['allow_conditions']))) {
            $elements[$pattern] = $label;
         }
      }
      return PluginServicesDropdown::showFromArray($p['name'], $elements, ['value' => $p['value']]);
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


   /** form for rule criteria
    *
    * @since 0.85
    *
    * @param $ID      integer  Id of the criteria
    * @param $options array    of possible options:
    *     - rule Object : the rule
   **/
   function showForm1($ID, $options = []) {
      global $CFG_GLPI;

      // Yllen: you always have parent for criteria
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

      echo "<tr class='tab_bg_1'>";
      echo "<td width='20%' class='center'>"._n('Criterion', 'Criteria', 1) . "</td><td colspan='3'>";
      echo "<input type='hidden' name='".$rule->getRuleIdField()."' value='".
            $this->fields[$rule->getRuleIdField()]."'>";

      $rand   = $rule->dropdownCriteria(['value' => $this->fields['criteria']]);
      $params = ['criteria' => '__VALUE__',
                     'rand'     => $rand,
                     'sub_type' => $rule->getType()];

      Ajax::updateItemOnSelectEvent("dropdown_criteria$rand", "criteria_span",
                                    $CFG_GLPI["root_doc"]."/ajax/rulecriteria.php", $params);

      if (isset($this->fields['criteria']) && !empty($this->fields['criteria'])) {
         $params['criteria']  = $this->fields['criteria'];
         $params['condition'] = $this->fields['condition'];
         $params['pattern']   = $this->fields['pattern'];
         echo "<script type='text/javascript' >\n";
         echo "$(function() {";
         Ajax::updateItemJsCode("criteria_span",
                                 $CFG_GLPI["root_doc"]."/ajax/rulecriteria.php",
                                 $params);
         echo '});</script>';
      }

      if ($rule->specific_parameters) {
         $itemtype = get_class($rule).'Parameter';
         echo "<span title=\"".__s('Add a criterion')."\" class='fa fa-plus pointer' " .
                  " onClick=\"".Html::jsGetElementbyID('addcriterion'.$rand).".dialog('open');\">".
                  "<span class='sr-only'>" . __s('Add a criterion') . "</span></span>";
         Ajax::createIframeModalWindow('addcriterion'.$rand,
                                       $itemtype::getFormURL(),
                                       ['reloadonclose' => true]);
      }

      echo "</td></tr>";
      echo "<tr><td colspan='4'><span id='criteria_span'>\n";
      echo "</span></td></tr>\n";
      $this->showFormButtons($options);

      echo"<style>
         // .viewcriteria .select2-container {
         //    max-width: 50% !important;
         //    min-width: 25% !important;
         // }
         // .viewcriteria select[name*='pattern']~span.select2-container {
         //    width:  50%  !important;
         // }
      </style>";
   }

   function showForm($ID,  $options = []) {
      echo'<div class="m-content">';
         echo'<div class="row">';
            echo'<div class="col-lg-12">';
               echo'<div class="m-portlet">';
                  echo'<div class="m-portlet__head">';
                     echo'<div class="m-portlet__head-caption">';
                     echo'<div class="m-portlet__head-title">';
                        echo'<h3 class="m-portlet__head-text">Ajouter un nouveau critère</h3>';
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
