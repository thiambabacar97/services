<?php 

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginServicesRuleTicket extends RuleTicket{

   static $rightname = 'rule_ticket';
   protected $rulecriteriaclass  = 'RuleCriteria';

   static function getTable($classname = null) {
      return "glpi_rules";
   }

   static function getClassName() {
      return get_called_class();
   }

   function getLinkURL() {
      global $CFG_GLPI;
      
      if (!isset($this->fields['id'])) {
            return '';
      }

      $link_item = $this->getFormURL();
      
      $link  = $link_item;
      $link .= (strpos($link,'?') ? '&amp;':'?').'id=' . $this->fields['id'];
      $link .= ($this->isTemplate() ? "&amp;withtemplate=1" : "");

      return $link;
   }

   static function getFormURL($full = false) {
      return PluginServicesToolbox::getItemTypeFormURL(get_called_class(), $full);
   }
   
   static function getSearchURL($full = true) {
      return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
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
            echo 'here';
            Session::redirectIfNotLoggedIn();
            PluginServicesHtml::displayRightError();
      }
      }
   }

   
   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRight(self::$rightname, CREATE);
   }

   static function canUpdate() {
      return true;
   }

   static function getType() {
      return "PluginServicesRuleTicket";
   }


   /**
    * Display the dropdown of the actions for the rule
    *
    * @param $options already used actions
    *
    * @return the initial value (first non used)
   **/
   function dropdownActions($options = []) {
      $p['name']                = 'field';
      $p['display']             = true;
      $p['used']                = [];
      $p['value']               = '';
      $p['display_emptychoice'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $actions = $this->getAllActions();

      // For each used actions see if several set is available
      // Force actions to available actions for several
      foreach ($p['used'] as $key => $ID) {
         if (isset($actions[$ID]['permitseveral'])) {
            unset($p['used'][$key]);
         }
      }

      // Complete used array with duplicate items
      // add duplicates of used items
      foreach ($p['used'] as $ID) {
         if (isset($actions[$ID]['duplicatewith'])) {
            $p['used'][$actions[$ID]['duplicatewith']] = $actions[$ID]['duplicatewith'];
         }
      }

      // Parse for duplicates of already used items
      foreach ($actions as $ID => $act) {
         if (isset($actions[$ID]['duplicatewith'])
            && in_array($actions[$ID]['duplicatewith'], $p['used'])) {
            $p['used'][$ID] = $ID;
         }
      }

      $value = '';

      foreach ($actions as $ID => $act) {
         $items[$ID] = $act['name'];

         if (empty($value) && !isset($used[$ID])) {
            $value = $ID;
         }
      }
      return PluginServicesDropdown::showFromArray($p['name'], $items, $p);
   }

   /**
    * Display the dropdown of the criteria for the rule
    *
    * @since 0.84 new proto
    *
    * @param $options   array of options : may be readonly
    *
    * @return the initial value (first)
   **/
   function dropdownCriteria($options = []) {
      $p['name']                = 'criteria';
      $p['display']             = true;
      $p['value']               = '';
      $p['display_emptychoice'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $group      = [];
      $groupname  = _n('Criterion', 'Criteria', Session::getPluralNumber());
      foreach ($this->getAllCriteria() as $ID => $crit) {
         // Manage group system
         if (!is_array($crit)) {
            if (count($group)) {
               asort($group);
               $items[$groupname] = $group;
            }
            $group     = [];
            $groupname = $crit;
         } else {
            $group[$ID] = $crit['name'];
         }
      }
      if (count($group)) {
         asort($group);
         $items[$groupname] = $group;
      }
      return PluginServicesDropdown::showFromArray($p['name'], $items, $p);
   }
   
   function showNewRuleForm($ID) {
      echo "<form method='post' action='".Toolbox::getItemTypeFormURL('Entity')."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>" . $this->getTitle() . "</th></tr>\n";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name') . "</td><td>";
      PluginServicesHtml::autocompletionTextField($this, "name", ['value' => '',
                                                         'size'  => 33]);
      echo "</td><td>".__('Description') . "</td><td>";
      PluginServicesHtml::autocompletionTextField($this, "description", ['value' => '',
                                                               'size'  => 33]);
      echo "</td><td>".__('Logical operator') . "</td><td>";
      $this->dropdownRulesMatch();
      echo "</td><td class='tab_bg_2 center'>";
      echo "<input type=hidden name='sub_type' value='".get_class($this)."'>";
      echo "<input type=hidden name='entities_id' value='-1'>";
      echo "<input type=hidden name='affectentity' value='$ID'>";
      echo "<input type=hidden name='_method' value='AddRule'>";
      echo "<input type='submit' name='execute' value=\""._sx('button', 'Add')."\" class='submit'>";
      echo "</td></tr>\n";
      echo "</table>";
      PluginServicesHtml::closeForm();
   }

   function showAndAddRuleForm($item) {

      $rand    = mt_rand();
      $canedit = self::canUpdate();

      if ($canedit
          && ($item->getType() == 'Entity')) {
         $this->showNewRuleForm($item->getField('id'));
      }

         //Get all rules and actions
      $crit = ['field' => getForeignKeyFieldForTable($item->getTable()),
                    'value' => $item->getField('id')];

      $rules = $this->getRulesForCriteria($crit);
      $nb    = count($rules);
      echo "<div class='spaced'>";

      if (!$nb) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>" . __('No item found') . "</th>";
         echo "</tr>\n";
         echo "</table>\n";

      } else {
         if ($canedit) {
            PluginServicesHtml::openMassiveActionsForm('mass'.get_called_class().$rand);
            $massiveactionparams
               = ['num_displayed'
                           => min($_SESSION['glpilist_limit'], $nb),
                       'specific_actions'
                           => ['update' => _x('button', 'Update'),
                                    'purge'  => _x('button', 'Delete permanently')]];
                  //     'extraparams'
                //           => array('rule_class_name' => $this->getRuleClassName()));
                PluginServicesHtml::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.get_called_class().$rand);
            $header_bottom .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.get_called_class().$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>" . $this->getTitle() . "</th>";
         $header_end .= "<th>" . __('Description') . "</th>";
         $header_end .= "<th>" . __('Active') . "</th>";
         $header_end .= "</tr>\n";
         echo $header_begin.$header_top.$header_end;

         Session::initNavigateListItems(get_class($this),
                              //TRANS: %1$s is the itemtype name,
                              //       %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));

         foreach ($rules as $rule) {
            Session::addToNavigateListItems(get_class($this), $rule->fields["id"]);
            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td width='10'>";
               PluginServicesHtml::showMassiveActionCheckBox(__CLASS__, $rule->fields["id"]);
               echo "</td>";
               echo "<td><a href='".$this->getFormURLWithID($rule->fields["id"])
                                   . "&amp;onglet=1'>" .$rule->fields["name"] ."</a></td>";

            } else {
               echo "<td>" . $rule->fields["name"] . "</td>";
            }

            echo "<td>" . $rule->fields["description"] . "</td>";
            echo "<td>" . PluginServicesDropdown::getYesNo($rule->fields["is_active"]) . "</td>";
            echo "</tr>\n";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>\n";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            PluginServicesHtml::showMassiveActions($massiveactionparams);
            PluginServicesHtml::closeForm();
         }
      }
      echo "</div>";
   }
   
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $relatedListabName = PluginServicesRelatedlist::tabNameForItem($item, $withtemplate);
      $tabNam = [
         self::createTabEntry(PluginServicesLog::getTypeName()) 
      ];
      $tab = array_merge($relatedListabName,  $tabNam);
      return $tab;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $lastIndex = count(PluginServicesRelatedlist::tabNameForItem($item, $withtemplate));
      PluginServicesRelatedlist::tabcontent($item, $tabnum, $withtemplate);
      switch ($tabnum) {
            case $lastIndex:
               PluginServicesLog::showForitem($item, $withtemplate);
               break;
      }
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
      // if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
      //     && (!$this->isNewItem() || $this->showdebug)
      //     && (method_exists($class, 'showDebug')
      //         || Infocom::canApplyOn($class)
      //         || in_array($class, $CFG_GLPI["reservation_types"]))) {

      //       $onglets[-2] = __('Debug');
      // }
      
      return $onglets;
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
            $cleaned_options = PluginServicesToolbox::stripslashes_deep($cleaned_options);

            $extraparamhtml = "&amp;".PluginServicesToolbox::append_params($cleaned_options, '&amp;');
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

   /**
    * Show the minimal form for the criteria rule
    *
    * @param $fields    datas used to display the criteria
    * @param $canedit   can edit the criteria rule?
    * @param $rand      random value of the form
   **/
   function showMinimalCriteriaForm_old($fields, $canedit, $rand) {
      global $CFG_GLPI;

      $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditCriteria".
                        $fields[$this->rules_id_field].$fields["id"]."$rand();\""
                        : '');
      echo "<tr class='tab_bg_1' >";
      $prt = $this->getType();
      echo $prt->getForeignKeyField();
      if ($canedit) {
         echo "<td width='10'>";
         PluginServicesHtml::showMassiveActionCheckBox($this->rulecriteriaclass, $fields["id"]);
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditCriteria". $fields[$this->rules_id_field].$fields["id"]."$rand() {\n";
         $params = ['type'               => $this->rulecriteriaclass,
                        'parenttype'          => $this->getType(),
                        $this->rules_id_field => $fields[$this->rules_id_field],
                        'id'                  => $fields["id"]];
         Ajax::updateItemJsCode("viewcriteria" . $fields[$this->rules_id_field] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "</td>";
      }

      echo $this->getMinimalCriteriaText($fields, $edit);
      echo "</tr>\n";
   }
   
   /**
    * Show the minimal form for the criteria rule
    *
    * @param $fields    datas used to display the criteria
    * @param $canedit   can edit the criteria rule?
    * @param $rand      random value of the form
   **/
   function showMinimalCriteriaForm($fields, $canedit, $rand) {
      global $CFG_GLPI;

      $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditCriteria".
                        $fields[$this->rules_id_field].$fields["id"]."$rand();\""
                        : '');
      echo "<tr class='tab_bg_1' >";
      if ($canedit) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox($this->rulecriteriaclass, $fields["id"]);
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditCriteria". $fields[$this->rules_id_field].$fields["id"]."$rand() {\n";
         $params = ['type'               => 'PluginServicesRuleCriteria',
                        'parenttype'          => $this->getType(),
                        $this->rules_id_field => $fields[$this->rules_id_field],
                        'id'                  => $fields["id"]];
         Ajax::updateItemJsCode("viewcriteria" . $fields[$this->rules_id_field] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "</td>";
      }

      echo $this->getMinimalCriteriaText($fields, $edit);
      echo "</tr>\n";
   }

      /**
    * Show the minimal form for the action rule
    *
    * @param $fields    datas used to display the action
    * @param $canedit   can edit the actions rule ?
    * @param $rand      random value of the form
   **/
   function showMinimalActionForm($fields, $canedit, $rand) {
      global $CFG_GLPI;

      $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditAction".
                        $fields[$this->rules_id_field].$fields["id"]."$rand();\""
                        : '');
      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox($this->ruleactionclass, $fields["id"]);
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditAction". $fields[$this->rules_id_field].$fields["id"]."$rand() {\n";
         $params = ['type'                => 'PluginServicesRuleAction',
                        'parenttype'          => $this->getType(),
                        $this->rules_id_field => $fields[$this->rules_id_field],
                        'id'                  => $fields["id"]];
         Ajax::updateItemJsCode("viewaction" . $fields[$this->rules_id_field] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "</td>";
      }
      echo $this->getMinimalActionText($fields, $edit);
      echo "</tr>\n";
   }

   function showCriteriasList_old($rules_id, $options = []) {
      global $CFG_GLPI;

      $rand = mt_rand();
      $p['readonly'] = false;
      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $canedit = $this->canEdit($rules_id);
      $style   = "class='m-0 tab_cadrehov table table-striped m-table mt-3'";

      if ($p['readonly']) {
         $canedit = false;
         $style   = "class='tab_cadrehov'";
      }
      if ($canedit) {
         echo "<div id='viewcriteria" . $rules_id . "$rand'></div>\n";
         echo "<script type='text/javascript' >\n";
         //echo "function viewAddCriteria" . $rules_id . "$rand() {\n";
         $params = ['type'                => $this->rulecriteriaclass,
                        'parenttype'          => $this->getType(),
                        $this->rules_id_field => $rules_id,
                        'id'                  => -1];
         Ajax::updateItemJsCode("viewcriteria" . $rules_id . "$rand",
                                 $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         //echo "};";
         echo "</script>\n";
         echo "<div style='margin-left:449px' class='firstbloc'>".
               "<a class='btsubmit' href='javascript:viewAddCriteria".$rules_id."$rand();'>";
         echo __('Add a new criterion')."</a></div>\n";
      }

      echo "<div class='spaced'>";

      $nb = sizeof($this->criterias);
      if ($canedit && $nb) {
         PluginServicesHtml::openMassiveActionsForm('mass'.$this->rulecriteriaclass.$rand);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $nb),
                                       'check_itemtype' => get_class($this),
                                       'check_items_id' => $rules_id,
                                       'container'      => 'mass'.$this->rulecriteriaclass.$rand,
                                       'extraparams'    => ['rule_class_name' => $this->getType()]];
         PluginServicesHtml::showMassiveActionsForSpecifiqueItem($massiveactionparams);
      }

      echo "<table $style>";
      echo "<tr class='noHover'>".
            "<th colspan='".($canedit&&$nb?" 4 ":"3")."'>". _n('Criterion', 'Criteria', Session::getPluralNumber())."</th>".
            "</tr>\n";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $nb) {
         $header_top    .= "<th width='10'>";
         $header_top    .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.$this->rulecriteriaclass.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>";
         $header_bottom .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.$this->rulecriteriaclass.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th class='center b'>"._n('Criterion', 'Criteria', 1)."</th>\n";
      $header_end .= "<th class='center b'>".__('Condition')."</th>\n";
      $header_end .= "<th class='center b'>".__('Reason')."</th>\n";

      $header_end .= "</tr>\n";
      echo $header_begin.$header_top.$header_end;

      foreach ($this->criterias as $criterion) {
         print_r($criterion);
         return ;
         $this->showMinimalCriteriaForm($criterion->fields, $canedit, $rand);
      }

      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         PluginServicesHtml::showMassiveActionsForSpecifiqueItem($massiveactionparams);
         PluginServicesHtml::closeForm();
      }

      echo "</div>\n";
      echo "
         <style>
            .btsubmit{
               padding: 5px;
               cursor: pointer;
               height: auto;
               font: bold 12px Arial, Helvetica;
               color: #8f5a0a;
               background-color: #FEC95C;
               border: 0;
               white-space: nowrap;
               display: inline-block;

            }
         </style>";
   }

   function showCriteriasList($rules_id, $options = []) {
      global $CFG_GLPI;

      $rand = mt_rand();
      $p['readonly'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $canedit = $this->canEdit($rules_id);
      $style   = "class='tab_cadre_fixehov'";

      if ($p['readonly']) {
         $canedit = false;
         $style   = "class='tab_cadrehov'";
      }

      if ($canedit) {
         echo "<div style='width:60%; margin:auto' class='viewcriteria' id='viewcriteria" . $rules_id . "$rand'></div>\n";

         echo "<script type='text/javascript' >\n";
         // echo "function viewAddCriteria" . $rules_id . "$rand() {\n";
         $params = ['type'                => 'PluginServicesRuleCriteria',
                         'parenttype'          => $this->getType(),
                         $this->rules_id_field => $rules_id,
                         'id'                  => -1];
         Ajax::updateItemJsCode("viewcriteria" . $rules_id . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         // echo "};";
         echo "</script>\n";
         // echo "<div class='center firstbloc'>".
         //       "<a class='vsubmit' href='javascript:viewAddCriteria".$rules_id."$rand();'>";
         // echo __('Add a new criterion')."</a></div>\n";
      }

      echo "<div class='spaced'>";

      $nb = sizeof($this->criterias);

      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.$this->rulecriteriaclass.$rand);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $nb),
                                      'check_itemtype' => get_class($this),
                                      'check_items_id' => $rules_id,
                                      'container'      => 'mass'.$this->rulecriteriaclass.$rand,
                                      'extraparams'    => ['rule_class_name'
                                                                    => $this->getType()]];
         PluginServicesHtml::showMassiveActionsFago($massiveactionparams);
      }

      echo "<table $style>";
      echo "<tr class='noHover'>".
           "<th colspan='".($canedit&&$nb?" 4 ":"3")."'>". _n('Criterion', 'Criteria', Session::getPluralNumber())."</th>".
           "</tr>\n";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit && $nb) {
         $header_top    .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.$this->rulecriteriaclass.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>";
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.$this->rulecriteriaclass.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th class='center b'>"._n('Criterion', 'Criteria', 1)."</th>\n";
      $header_end .= "<th class='center b'>".__('Condition')."</th>\n";
      $header_end .= "<th class='center b'>".__('Reason')."</th>\n";
      $header_end .= "</tr>\n";
      echo $header_begin.$header_top.$header_end;

      foreach ($this->criterias as $criterion) {
         $this->showMinimalCriteriaForm($criterion->fields, $canedit, $rand);
      }

      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>\n";
   }
   
   /**
    * display title for action form
    *
    * @since 0.84.3
   **/
   function getTitleAction() {
      
      //parent::getTitleAction();
      $showwarning = false;
      if (isset($this->actions)) {
         foreach ($this->actions as $key => $val) {
            if (isset($val->fields['field'])) {
               if (in_array($val->fields['field'], ['impact', 'urgency'])) {
                  $showwarning = true;
               }
            }
         }
      }
      if ($showwarning) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><td>".
               __('Urgency or impact used in actions, think to add Priority: recompute action if needed.').
               "</td></tr>\n";
         echo "</table><br>";
      }
   }

   function showActionsList_old($rules_id, $options = []) {
      global $CFG_GLPI;

      $rand = mt_rand();
      $p['readonly'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $canedit = $this->canEdit($rules_id);
      $style   = 'class="m-0 tab_cadrehov table table-striped m-table mt-3"';

      if ($p['readonly']) {
         $canedit = false;
         $style   = "class='tab_cadrehov'";
      }
      //$this->getTitleAction();

      if ($canedit) {
         echo "<div id='viewaction" . $rules_id . "$rand'></div>\n";
      }

      if ($canedit
         && (($this->maxActionsCount() == 0)
               || (sizeof($this->actions) < $this->maxActionsCount()))) {

         echo "<script type='text/javascript' >\n";
         echo "function viewAddAction" . $rules_id . "$rand() {\n";
         $params = ['type'                => "PluginServicesRuleAction",
                        'parenttype'          => $this->getType(),
                        $this->rules_id_field => $rules_id,
                        'id'                  => -1];
         Ajax::updateItemJsCode("viewaction" . $rules_id . "$rand",
                                 $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "<div style='margin-left:449px' class='center firstbloc'>".
               "<a class='btsubmit' href='javascript:viewAddAction".$rules_id."$rand();'>";
         echo __('Add a new action')."</a></div>\n";
      }
      $nb = count($this->actions);
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         PluginServicesHtml::openMassiveActionsForm('mass'.$this->ruleactionclass.$rand);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $nb),
                                       'check_itemtype' => get_class($this),
                                       'check_items_id' => $rules_id,
                                       'container'      => 'mass'.$this->ruleactionclass.$rand,
                                       'extraparams'    => ['rule_class_name'
                                                                     => $this->getType()]];
         PluginServicesHtml::showMassiveActionsForSpecifiqueItem($massiveactionparams);
      }
      echo "<table $style>";
      echo "<tr class='noHover'>";
      echo "<th colspan='".($canedit && $nb?'4':'3')."'>" . _n('Action', 'Actions', Session::getPluralNumber()) . "</th></tr>";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit && $nb) {
         $header_top    .= "<th width='10'>";
         $header_top    .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.$this->ruleactionclass.$rand)."</th>";
         $header_bottom .= "<th width='10'>";
         $header_bottom .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.$this->ruleactionclass.$rand)."</th>";
      }

      $header_end .= "<th class='center b'>"._n('Field', 'Fields', Session::getPluralNumber())."</th>";
      $header_end .= "<th class='center b'>".__('Action type')."</th>";
      $header_end .= "<th class='center b'>".__('Value')."</th>";
      $header_end .= "</tr>\n";
      echo $header_begin.$header_top.$header_end;

      foreach ($this->actions as $action) {
         $this->showMinimalActionForm($action->fields, $canedit, $rand);
      }
      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         PluginServicesHtml::showMassiveActionsForSpecifiqueItem($massiveactionparams);
         PluginServicesHtml::closeForm();
      }
      echo "</div>";
      echo "
      <style>
         .btsubmit{
            padding: 5px;
            cursor: pointer;
            height: auto;
            font: bold 12px Arial, Helvetica;
            color: #8f5a0a;
            background-color: #FEC95C;
            border: 0;
            white-space: nowrap;
            display: inline-block;

         }
      </style>";
   }


      /**
    * Display all rules actions
    *
    * @param $rules_id        rule ID
    * @param $options   array of options : may be readonly
   **/
   function showActionsList($rules_id, $options = []) {
      global $CFG_GLPI;

      $rand = mt_rand();
      $p['readonly'] = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $canedit = $this->canEdit($rules_id);
      $style   = "class='tab_cadre_fixehov'";

      if ($p['readonly']) {
         $canedit = false;
         $style   = "class='tab_cadrehov'";
      }
      $this->getTitleAction();

      if ($canedit) {
         echo "<div style='width:60%; margin:auto' id='viewaction" . $rules_id . "$rand'></div>\n";
      }

      if ($canedit
         && (($this->maxActionsCount() == 0)
            || (sizeof($this->actions) < $this->maxActionsCount()))) {
         echo "<script type='text/javascript' >\n";
         // echo "function viewAddAction" . $rules_id . "$rand() {\n";
         $params = ['type'                => 'PluginServicesRuleAction',
                        'parenttype'          => $this->getType(),
                        $this->rules_id_field => $rules_id,
                        'id'                  => -1];
         Ajax::updateItemJsCode("viewaction" . $rules_id . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         // echo "};";
         echo "</script>\n";
         // echo "<div class='center firstbloc'>".
         //       "<a class='vsubmit' href='javascript:viewAddAction".$rules_id."$rand();'>";
         // echo __('Add a new action')."</a></div>\n";
      }

      $nb = count($this->actions);

      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.$this->ruleactionclass.$rand);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $nb),
                                    'check_itemtype' => get_class($this),
                                    'check_items_id' => $rules_id,
                                    'container'      => 'mass'.$this->ruleactionclass.$rand,
                                    'extraparams'    => ['rule_class_name'
                                                                  => $this->getType()]];
         PluginServicesHtml::showMassiveActionsFago($massiveactionparams);
      }

      echo "<table $style>";
      echo "<tr class='noHover'>";
      echo "<th colspan='".($canedit && $nb?'4':'3')."'>" . _n('Action', 'Actions', Session::getPluralNumber()) . "</th></tr>";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit && $nb) {
         $header_top    .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.$this->ruleactionclass.$rand)."</th>";
         $header_bottom .= "<th width='10'>";
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.$this->ruleactionclass.$rand)."</th>";
      }

      $header_end .= "<th class='center b'>"._n('Field', 'Fields', Session::getPluralNumber())."</th>";
      $header_end .= "<th class='center b'>".__('Action type')."</th>";
      $header_end .= "<th class='center b'>".__('Value')."</th>";
      $header_end .= "</tr>\n";
      echo $header_begin.$header_top.$header_end;

      foreach ($this->actions as $action) {
         $this->showMinimalActionForm($action->fields, $canedit, $rand);
      }
      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>\n";

      if ($canedit && $nb) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }

   function postForm($post) {

      global $CFG_GLPI;
      $rule = new self();
      if (isset($post["update"])) {
         $rule->check($post["id"], UPDATE);
         $rule->update($post);
         PluginServicesFagoUtils::returnResponse();
      } else if (isset($post["add"])) {
         $rule->check(-1, CREATE, $post);
         if ($newID=$rule->add($post)) {
            PluginServicesFagoUtils::returnResponse($newID);
         }
         PluginServicesFagoUtils::returnResponse();
      }
      PluginServicesHtml::back();
   }

   function dropdownRulesMatch($options = []) {

      $p['name']     = 'match';
      $p['value']    = '';
      $p['restrict'] = $this->restrict_matching;
      $p['display']  = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if (!$p['restrict'] || ($p['restrict'] == self::AND_MATCHING)) {
         $elements[self::AND_MATCHING] = __('and');
      }

      if (!$p['restrict'] || ($p['restrict'] == self::OR_MATCHING)) {
         $elements[self::OR_MATCHING]  = __('or');
      }

      return PluginServicesDropdown::showFromArray($p['name'], $elements, $p);
   }


   static function getConditionsArray() {

      return [static::ONADD                   => __('Add'),
                  static::ONUPDATE                => __('Update'),
                  static::ONADD|static::ONUPDATE  => sprintf(__('%1$s / %2$s'), __('Add'),
                                                            __('Update'))];
   }

   static function dropdownConditions($options = []) {

      $p['name']      = 'condition';
      $p['value']     = 0;
      $p['display']   = true;
      $p['on_change'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $elements = static::getConditionsArray();
      
      if (count($elements)) {
         return PluginServicesDropdown::showFromArray($p['name'], $elements, $p);
      }

      return false;
   }

   static function getConditionName($value) {

      $cond = static::getConditionsArray();

      if (isset($cond[$value])) {
         return $cond[$value];
      }

      return NOT_AVAILABLE;
   }

   function useConditions() {
      return (count($this->getConditionsArray()) > 0);
   }

   function showForm($ID, $options = []){
      $item = new PluginServicesRule();
      $item->showForm($ID, $options, $this);
      $target = $this->getFormURL();
      $jsScript = '
         $(document).ready(function() {
               var tokenurl = "/ajax/generatetoken.php";
               var form = $("#pluginservicesruleticketform");
               var request;
               form.validate({
                  rules: {
                     name : {
                           required: true
                     }
                  }
               });
               $("#addForm").click(function(e){
                  event.preventDefault();
                  if (!form.valid()) { // stop script where form is invalid
                     return false;
                  }
                  if (request) { // Abort any pending request
                     request.abort();
                  }

                  $("button[name=add]").addClass("m-loader m-loader--light m-loader--right"); // add loader
                  $("button[name=add]").prop("disabled", true);

                  var serializedData = form.serializeArray();
                  
                  $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
                     serializedData[serializedData.length] = { name: "add", value:"add" };
                     serializedData[serializedData.length] = {  name: "_glpi_csrf_token", value:token };
                     request = $.ajax({
                           url: "'.$target.'",
                           type: "post",
                           data: serializedData
                     });

                     request.done(function (response, textStatus, jqXHR){
                        var res = JSON.parse(response);
                        showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
                        $("button[name=add]").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                        $("button[name=add]").prop("disabled", false);
                     });

                     request.fail(function (jqXHR, textStatus, errorThrown){
                           removeSubmitFormLoader("add");
                           console.error(jqXHR, textStatus, errorThrown);

                           $("button[name=add]").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                           $("button[name=add]").prop("disabled", false);
                     });
                  }); 
               })

               $("#editForm").click(function(e){
                  event.preventDefault();
      
                  if (!form.valid()) { // stop script where form is invalid
                     return false;
                  }
                  
                  if (request) { // Abort any pending request
                     request.abort();
                  }

                  $("#editForm").addClass("m-loader m-loader--light m-loader--right"); // add loader
                  $("#editForm").prop("disabled", true);

                  var serializedData = form.serializeArray();

                  $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
                     serializedData[serializedData.length] = { name: "update", value:"update" };
                     serializedData[serializedData.length] = {  name: "_glpi_csrf_token", value:token };
                     request = $.ajax({
                           url: "'.$target.'",
                           type: "post",
                           data: serializedData
                     });

                     request.done(function (response, textStatus, jqXHR){
                           var res = JSON.parse(response);
                           console.log( Object.values(res.message)[0]);
                           showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
                           $("#editForm").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                           $("#editForm").prop("disabled", false);
                     });

                     request.fail(function (jqXHR, textStatus, errorThrown){
                           removeSubmitFormLoader("add");
                           console.error(jqXHR, textStatus, errorThrown);

                           $("#editForm").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                           $("#editForm").prop("disabled", false);
                     });
                  }); 
               })
         })  
      ';
      echo Html::scriptBlock($jsScript);
      return true;
   }

   // function showForm($ID, $options = []) {
   //    if (!$this->isNewID($ID)) {
   //       $this->check($ID, READ);
   //    } else {
   //       // Create item
   //       $this->checkGlobal(UPDATE);
   //    }

   //    $canedit = $this->canEdit(static::$rightname);
   //    $rand = mt_rand();

   //    $all_fields = [
   //       [
   //          'label'=> 'Name',
   //          'name'=> 'name',
   //          'type'=> 'text'
   //       ],[
   //          'label'=> 'Logical operator',
   //          'type'=> 'function',
   //          'name'=> 'dropdownRulesMatch',
   //          'params'=> [
   //             'name' => 'match',
   //             'value' => $this->fields["match"]
   //          ]
   //       ],
   //       [
   //          'label'=> 'Active',
   //          'name'=> 'is_active',
   //          'type'=> 'boolean'
   //       ],
   //       [
   //          'label'=> 'Use rule for',
   //          'type'=> 'function',
   //          'name'=> 'dropdownConditions',
   //          'cond' => $this->useConditions(),
   //          'params'=> [
   //             'name' => 'condition',
   //             'value' => $this->fields["condition"]
   //          ]
   //       ],[
   //          'label'=> 'Courte description ',
   //          'name'=> 'description',
   //          'type'=> 'text',
   //          'full'=> true
   //       ],
   //       [
   //          'label'=> 'Description',
   //          'name'=> 'comment',
   //          'type'=> 'textarea',
   //          'full'=> true
   //       ],[
   //          'name'=> 'ranking',
   //          'type'=> 'hidden',
   //          'value' => get_class($this),
   //          'cond' => $canedit && !$this->isNewID($ID)
   //       ],[
   //          'name'=> 'sub_type',
   //          'value' => get_class($this),
   //          'type'=> 'hidden',
   //          'cond' => $canedit && !$this->isNewID($ID)
   //       ]
   //    ];
   //    PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
   //    $target = $this->getFormURL();
   //      $jsScript = '
   //          $(document).ready(function() {
   //              var tokenurl = "/ajax/generatetoken.php";
   //              var form = $("#pluginservicesruleticketform");
   //              var request;
   //              form.validate({
   //                  rules: {
   //                      name : {
   //                          required: true
   //                      }
   //                  }
   //              });
   //              $("#addForm").click(function(e){
   //                  event.preventDefault();
   //                  if (!form.valid()) { // stop script where form is invalid
   //                      return false;
   //                  }
   //                  if (request) { // Abort any pending request
   //                      request.abort();
   //                  }

   //                  $("button[name=add]").addClass("m-loader m-loader--light m-loader--right"); // add loader
   //                  $("button[name=add]").prop("disabled", true);

   //                  var serializedData = form.serializeArray();
                    
   //                  $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
   //                      serializedData[serializedData.length] = { name: "add", value:"add" };
   //                      serializedData[serializedData.length] = {  name: "_glpi_csrf_token", value:token };
   //                      request = $.ajax({
   //                          url: "'.$target.'",
   //                          type: "post",
   //                          data: serializedData
   //                      });

   //                      request.done(function (response, textStatus, jqXHR){
   //                          var res = JSON.parse(response);
   //                          showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
   //                          $("button[name=add]").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
   //                          $("button[name=add]").prop("disabled", false);
   //                      });

   //                      request.fail(function (jqXHR, textStatus, errorThrown){
   //                          removeSubmitFormLoader("add");
   //                          console.error(jqXHR, textStatus, errorThrown);

   //                          $("button[name=add]").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
   //                          $("button[name=add]").prop("disabled", false);
   //                      });
   //                  }); 
   //              })

   //              $("#editForm").click(function(e){
   //                  event.preventDefault();
        
   //                  if (!form.valid()) { // stop script where form is invalid
   //                      return false;
   //                  }
                    
   //                  if (request) { // Abort any pending request
   //                      request.abort();
   //                  }

   //                  $("#editForm").addClass("m-loader m-loader--light m-loader--right"); // add loader
   //                  $("#editForm").prop("disabled", true);

   //                  var serializedData = form.serializeArray();

   //                  $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
   //                      serializedData[serializedData.length] = { name: "update", value:"update" };
   //                      serializedData[serializedData.length] = {  name: "_glpi_csrf_token", value:token };
   //                      request = $.ajax({
   //                          url: "'.$target.'",
   //                          type: "post",
   //                          data: serializedData
   //                      });

   //                      request.done(function (response, textStatus, jqXHR){
   //                          var res = JSON.parse(response);
   //                          console.log( Object.values(res.message)[0]);
   //                          showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
   //                          $("#editForm").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
   //                          $("#editForm").prop("disabled", false);
   //                      });

   //                      request.fail(function (jqXHR, textStatus, errorThrown){
   //                          removeSubmitFormLoader("add");
   //                          console.error(jqXHR, textStatus, errorThrown);

   //                          $("#editForm").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
   //                          $("#editForm").prop("disabled", false);
   //                      });
   //                  }); 
   //              })
   //          })  
   //      ';
   //      echo Html::scriptBlock($jsScript);
   //      return true;
   // }

      /**
    * Display item used to select a pattern for a criteria
    *
    * @param $name      criteria name
    * @param $ID        the given criteria
    * @param $condition condition used
    * @param $value     the pattern (default '')
    * @param $test      Is to test rule ? (false by default)
   **/
   function displayCriteriaSelectPattern($name, $ID, $condition, $value = "", $test = false) {
      global $CFG_GLPI;

      $crit    = $this->getCriteria($ID);
      $display = false;
      $tested  = false;

      if (isset($crit['type'])
         && ($test
            || in_array($condition, [self::PATTERN_IS, self::PATTERN_IS_NOT,
                                          self::PATTERN_NOT_UNDER, self::PATTERN_UNDER]))) {

         $tested = true;
         switch ($crit['type']) {
            case "yesonly" :
               PluginServicesDropdown::showYesNo($name, $crit['table'], 0);
               $display = true;
               break;

            case "yesno" :
               PluginServicesDropdown::showYesNo($name, $value);
               $display = true;
               break;

            case "dropdown" :
               $param = ['name'  => $name,
                              'value' => $value];
               if (isset($crit['condition'])) {
                  $param['condition'] = $crit['condition'];
               }
               PluginServicesDropdown::show(getItemTypeForTable($crit['table']), $param);

               $display = true;
               break;

            case "dropdown_users" :
               PluginServicesUser::dropdown(['value'  => $value,
                                    'name'   => $name,
                                    'right'  => 'all']);
               $display = true;
               break;

            case "dropdown_tracking_itemtype" :
               PluginServicesDropdown::showItemTypes($name, array_keys(Ticket::getAllTypesForHelpdesk()));
               $display = true;
               break;

            case "dropdown_assets_itemtype" :
               PluginServicesDropdown::showItemTypes($name, $CFG_GLPI['asset_types'], ['value' => $value]);
               $display = true;
               break;

            case "dropdown_import_type" :
               RuleAsset::dropdownImportType($name, $value);
               $display = true;
               break;

            case "dropdown_urgency" :
               PluginServicesTicket::dropdownUrgency(['name'  => $name,
                                             'value' => $value]);
               $display = true;
               break;

            case "dropdown_impact" :
               PluginServicesTicket::dropdownImpact(['name'  => $name,
                                          'value' => $value]);
               $display = true;
               break;

            case "dropdown_priority" :
               PluginServicesTicket::dropdownPriority(['name'  => $name,
                                             'value' => $value,
                                             'withmajor' => true]);
               $display = true;
               break;

            case "dropdown_status" :
               PluginServicesTicket::dropdownStatus(['name'  => $name,
                                          'value' => $value]);
               $display = true;
               break;

            case "dropdown_tickettype" :
               PluginServicesTicket::dropdownType($name, ['value' => $value]);
               $display = true;
               break;

            default:
               $tested = false;
               break;
         }
      }
      //Not a standard condition
      if (!$tested) {
         $display = $this->displayAdditionalRuleCondition($condition, $crit, $name, $value, $test);
      }

      if (($condition == self::PATTERN_EXISTS)
         || ($condition == self::PATTERN_DOES_NOT_EXISTS)) {
         echo "<input type='hidden' name='$name' value='1'>";
         $display = true;
      }

      if (!$display
         && ($rc = getItemForItemtype($this->rulecriteriaclass))) {
         PluginServicesHtml::autocompletionTextField($rc, "pattern", ['name'  => $name,
                                                            'value' => $value,
                                                            'size'  => 70]);
      }
   }

   public function showList($itemtype, $params){
      $params = [
         "is_deleted"=> 0, 
         "as_map"=> 0, 
         "criteria"=> [
            [
               "link" => "AND", 
               "field" => 122,
               "searchtype" =>"equals", 
               "search" => "Rechercher",
               "value"=> 'PluginServicesRuleTicket',  
               "itemtype" => "PluginServicesRuleTicket",
            ]
         ]
      ];
      PluginServicesHtml::showList($itemtype, $params);
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'ranking',
         'name'               => __('Ranking'),
         'datatype'           => 'number',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'description',
         'name'               => __('Description'),
         'datatype'           => 'text',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'match',
         'name'               => __('Logical operator'),
         'datatype'           => 'specific',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => Entity::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '122',
         'table'              => $this->getTable(),
         'field'              => 'sub_type',
         'name'               => __('Item'),
         'datatype'           => 'text',
         'massiveaction'      => false,
         'autocomplete'       => true,
      ];

      return $tab;
   }
}

?>