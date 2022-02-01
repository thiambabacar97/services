<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginservicesProfile
 */
class PluginServicesProfile extends Profile {
   static $rightname             = 'profile';
   // Specific ones
   /// Helpdesk fields of helpdesk profiles
   public static $helpdesk_rights = [
      'create_ticket_on_login',
      'changetemplates_id',
      'followup',
      'helpdesk_hardware',
      'helpdesk_item_type',
      'knowbase',
      'password_update',
      'personalization',
      'problemtemplates_id',
      'reminder_public',
      'reservation',
      'rssfeed_public',
      'show_group_hardware',
      'task',
      'ticket',
      'ticket_cost',
      'ticket_status',
      'tickettemplates_id',
      'ticketvalidation',
      'plugin_portail_item',
      'plugin_portail_home',
      'plugin_portail_tickets',
   ];

   static function getTable($classname = null) {
      return "glpi_profiles";
   }

   static function getClassName() {
      return get_called_class();
   }
   
   static function getType() {
      return "PluginServicesProfile";
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

   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRight(self::$rightname, CREATE);
   }

   static function canUpdate() {
      return Session::haveRight(self::$rightname, UPDATE);
   }

   /**
    * Get the default Profile for new user
    *
    * @return integer profiles_id
   **/
   static function getDefault() {
      global $DB;

      foreach ($DB->request('glpi_profiles', ['is_default'=>1]) as $data) {
         return $data['id'];
      }
      return 0;
   }

   /**
    * Is the current user have more right than all profiles in parameters
    *
    * @param $IDs array of profile ID to test
    *
    * @return boolean true if have more right
   **/
   static function currentUserHaveMoreRightThan($IDs = []) {
      global $DB;

      if (Session::isCron()) {
         return true;
      }
      if (count($IDs) == 0) {
         // Check all profiles (means more right than all possible profiles)
         return (countElementsInTable('glpi_profiles')
                     == countElementsInTable('glpi_profiles',
                                             self::getUnderActiveProfileRestrictCriteria()));
      }
      $under_profiles = [];

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => self::getUnderActiveProfileRestrictCriteria()
      ]);

      while ($data = $iterator->next()) {
         $under_profiles[$data['id']] = $data['id'];
      }

      foreach ($IDs as $ID) {
         if (!isset($under_profiles[$ID])) {
            return false;
         }
      }
      return true;
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $ong    = [];
      if (Plugin::isPluginActive('services')){
         $ong[0] =  self::createTabEntry(__("Administration"));
      }
      if (Plugin::isPluginActive('assistances')){
         $ong[1] =  self::createTabEntry(__("Assistance"));
      }
      if (Plugin::isPluginActive('portail')){
         $ong[2] =  self::createTabEntry(__("Portail"));
      }
      return $ong;
   }

   /**
    * show Tab content
    *
    * @since 0.83
    *
    * @param CommonGLPI $item         Item on which the tab need to be displayed
    * @param integer    $tabnum       tab number (default 1)
    * @param boolean    $withtemplate is a template object ? (default 0)
    *
    * @return boolean
    **/
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($tabnum) {
         case 0:
            if (Plugin::isPluginActive('services')){
               $item->showFormFagoTracking( $item->getID());
            }
            break;
         case 1:
            if (Plugin::isPluginActive('assistances')){
               $prof = new PluginAssistancesProfile();
               $prof->showFormFagoTracking($item->getID());
            }
            break;
         case 2: 
            if (Plugin::isPluginActive('portail')){
               $prof = new PluginPortailProfile();
               $prof->showFormFagoTracking($item->getID());
            } 
            break;
      }
      return true;
   }

   function showTabsContent($options = []) {
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

   function defineAllTabsFago($options = []) {
      global $CFG_GLPI;

      $onglets = [];
      // Object with class with 'addtabon' attribute
      if ((isset(self::$othertabs[$this->getType()])
            && !$this->isNewItem())) {
               
      foreach (self::$othertabs[$this->getType()] as $typetab) {
            $this->addStandardTab($typetab, $onglets, $options);
      }
      }

      $class = $this->getType();
      return $onglets;
   }

      /**
    * Show profile form
    *
    * @param $items_id integer id of the profile
    * @param $target value url of target
    *
    * @return nothing
    **/
   function showFormFagoTracking($profiles_id = 0, $openform = true, $closeform = true) {
      echo "<div class='table-responsive'>";
         if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
               && $openform) {
            $profile = new Profile();
            echo "<form method='post' action='".$profile->getFormURL()."'>";
         }

         $profile = new self();
         $profile->getFromDB($profiles_id);
         //if ($profile->getField('interface') == 'central') {
            $rights = $this->getAllRights();
            $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                            'default_class' => 'tab_bg_2',
                                                            'title'         => __('General')]);
         //}

            if ($canedit
                  && $closeform) {
               echo "<div class='mt-3'>";
               echo Html::hidden('id', ['value' => $profiles_id]);
               echo PluginServicesHtml::submit(_sx('button', 'Soumettre'), ['name' => 'update']);
               echo "</div>\n";
               Html::closeForm();
            }
      echo "</div>";
   }

   function displayRightsChoiceMatrixFago(array $rights, array $options = []) {
      $param                  = [];
      $param['title']         = '';
      $param['canedit']       = true;
      $param['default_class'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      // To be completed before display to avoid non available rights in DB
      $availablerights = ProfileRight::getAllPossibleRights();
     
      $column_labels = [];
      $columns       = [];
      $rows          = [];

      foreach ($rights as $info) {

         if (is_string($info)) {
            $rows[] = $info;
            continue;
         }
         if (is_array($info)
             && ((!empty($info['itemtype'])) || (!empty($info['rights'])))
             && (!empty($info['label']))
             && (!empty($info['field']))) {
            // Add right if it does not exists : security for update
            if (!isset($availablerights[$info['field']])) {
               ProfileRight::addProfileRights([$info['field']]);
            }

            $row = ['label'   => $info['label'],
                         'columns' => []];
            if (!empty($info['row_class'])) {
               $row['class'] = $info['row_class'];
            } else {
               $row['class'] = $param['default_class'];
            }
            if (isset($this->fields[$info['field']])) {
               $profile_right = $this->fields[$info['field']];
            } else {
               $profile_right = 0;
            }

            if (isset($info['rights'])) {
               $itemRights = $info['rights'];
            } else {
               $itemRights = self::getRightsFor($info['itemtype']);
              
            }
            foreach ($itemRights as $right => $label) {
               if (!isset($column_labels[$right])) {
                  $column_labels[$right] = [];
               }
               if (is_array($label)) {
                  $long_label = $label['long'];
               } else {
                  $long_label = $label;
               }
               if (!isset($column_labels[$right][$long_label])) {
                  $column_labels[$right][$long_label] = count($column_labels[$right]);
               }
               $right_value                  = $right.'_'.$column_labels[$right][$long_label];

               $columns[$right_value]        = $label;

               $checked                      = ((($profile_right & $right) == $right) ? 1 : 0);
               $row['columns'][$right_value] = ['checked' => $checked];
               if (!$param['canedit']) {
                  $row['columns'][$right_value]['readonly'] = true;
               }
            }
            if (!empty($info['html_field'])) {
               $rows[$info['html_field']] = $row;
            } else {
               $rows['_'.$info['field']] = $row;
            }
         }
      }
     
      uksort($columns, function ($a, $b) {
         $a = explode('_', $a);
         $b = explode('_', $b);

         // For standard rights sort by right
         if (($a[0] < 1024) || ($b[0] < 1024)) {
            if ($a[0] > $b[0]) {
                return 1;
            }
            if ($a[0] < $b[0]) {
                return -1;
            }
         }

         // For extra right sort by type
         if ($a[1] > $b[1]) {
             return 1;
         }
         if ($a[1] < $b[1]) {
             return -1;
         }
         return 0;
      });
      return $columns;
      // return Html::showCheckboxMatrix($columns, $rows,
      //                                 ['title'                => $param['title'],
      //                                       'row_check_all'        => count($columns) > 1,
      //                                       'col_check_all'        => count($rows) > 1,
      //                                       'rotate_column_titles' => false]);
   }

   function teste( $id){
      $rand = mt_rand();
      $all_fields = [
         [
            'label'=> 'Name',
            'name'=> 'name',
            'type'=> 'text'
         ],[
            'label'=> "Profile's interface",
            'type'=> 'function',
            'name'=> 'dropdownInterface',
            'params'=> [
               'name' => 'interface',
               'rand'  => mt_rand(),
               'display_emptychoice' => true
            ]
         ]
      ];
      $rights = $this->getAllRights();
      $rights = [['itemtype'  => 'Ticket',
      'label'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
      'field'     => 'ticket']];
      $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);
      $result = $this->displayRightsChoiceMatrixFago($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => __('General')]);

                                                      print_r( $result);
      // PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
   }

      /**
    * Display rights choice matrix
    *
    * @since 0.85
    *
    * @param $rights array    possible:
    *             'itemtype'   => the type of the item to check (as passed to self::getRightsFor())
    *             'rights'     => when use of self::getRightsFor() is impossible
    *             'label'      => the label for the right
    *             'field'      => the name of the field inside the DB and HTML form (prefixed by '_')
    *             'html_field' => when $html_field != '_'.$field
    * @param $options array   possible:
    *             'title'         the title of the matrix
    *             'canedit'
    *             'default_class' the default CSS class used for the row
    *
    * @return random value used to generate the ids
   **/
   function displayRightsChoiceMatrix(array $rights, array $options = []) {

      $param                  = [];
      $param['title']         = '';
      $param['canedit']       = true;
      $param['default_class'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      // To be completed before display to avoid non available rights in DB
      $availablerights = ProfileRight::getAllPossibleRights();

      $column_labels = [];
      $columns       = [];
      $rows          = [];

      foreach ($rights as $info) {

         if (is_string($info)) {
            $rows[] = $info;
            continue;
         }
         if (is_array($info)
            && ((!empty($info['itemtype'])) || (!empty($info['rights'])))
            && (!empty($info['label']))
            && (!empty($info['field']))) {
            // Add right if it does not exists : security for update
            if (!isset($availablerights[$info['field']])) {
               ProfileRight::addProfileRights([$info['field']]);
            }

            $row = ['label'   => $info['label'],
                        'columns' => []];
            if (!empty($info['row_class'])) {
               $row['class'] = $info['row_class'];
            } else {
               $row['class'] = $param['default_class'];
            }
            if (isset($this->fields[$info['field']])) {
               $profile_right = $this->fields[$info['field']];
            } else {
               $profile_right = 0;
            }

            if (isset($info['rights'])) {
               $itemRights = $info['rights'];
            } else {
               $itemRights = self::getRightsFor($info['itemtype']);
            }
            foreach ($itemRights as $right => $label) {
               if (!isset($column_labels[$right])) {
                  $column_labels[$right] = [];
               }
               if (is_array($label)) {
                  $long_label = $label['long'];
               } else {
                  $long_label = $label;
               }
               if (!isset($column_labels[$right][$long_label])) {
                  $column_labels[$right][$long_label] = count($column_labels[$right]);
               }
               $right_value                  = $right.'_'.$column_labels[$right][$long_label];

               $columns[$right_value]        = $label;

               $checked                      = ((($profile_right & $right) == $right) ? 1 : 0);
               $row['columns'][$right_value] = ['checked' => $checked];
               if (!$param['canedit']) {
                  $row['columns'][$right_value]['readonly'] = true;
               }
            }
            if (!empty($info['html_field'])) {
               $rows[$info['html_field']] = $row;
            } else {
               $rows['_'.$info['field']] = $row;
            }
         }
      }

      uksort($columns, function ($a, $b) {
         $a = explode('_', $a);
         $b = explode('_', $b);

         // For standard rights sort by right
         if (($a[0] < 1024) || ($b[0] < 1024)) {
            if ($a[0] > $b[0]) {
               return 1;
            }
            if ($a[0] < $b[0]) {
               return -1;
            }
         }

         // For extra right sort by type
         if ($a[1] > $b[1]) {
            return 1;
         }
         if ($a[1] < $b[1]) {
            return -1;
         }
         return 0;
      });

      return PluginServicesHtml::showCheckboxMatrix($columns, $rows,
                                    ['title'                => $param['title'],
                                          'row_check_all'        => count($columns) > 1,
                                          'col_check_all'        => count($rows) > 1,
                                          'rotate_column_titles' => false]);
   }

   /**
    * @param $ID
    */
   static function createFirstAccess($ID) {
      //85
      self::addDefaultProfileInfos($ID,
                                    ['plugin_services_apparences' => 23,
                                    'plugin_services_themes' => 23,
                                    'plugin_services_ticket_task' => 23,
                                    'plugin_services' => 23, 
                                    'plugin_services_user' => 23,
                                    'plugin_services_slms'       => 23,
                                    'plugin_services_slalevel'  => 23,
                                    'plugin_services_sla'    => 23,
                                    'plugin_services_itil_solution' => 23,
                                    'plugin_services' => 23,
                                    'plugin_services_relatedlist' => 23,
                                    ],
                                 true);
   }

   /**
    * Dropdown profiles which have rights under the active one
    *
    * @param $options array of possible options:
    *    - name : string / name of the select (default is profiles_id)
    *    - value : integer / preselected value (default 0)
    *
   **/
   static function dropdownUnder($options = []) {
      global $DB;

      $p['name']  = 'profiles_id';
      $p['value'] = '';
      $p['rand']  = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => self::getUnderActiveProfileRestrictCriteria(),
         'ORDER'  => 'name'
      ]);

      //New rule -> get the next free ranking
      while ($data = $iterator->next()) {
         $profiles[$data['id']] = $data['name'];
      }
      PluginServicesDropdown::showFromArray($p['name'], $profiles,
                              ['value'               => $p['value'],
                           'rand'                => $p['rand'],
                           'display_emptychoice' => true]);
   }


   /**
    * @param $profile
   **/
   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {
      $dbu          = new DbUtils();
      $profileRight = new ProfileRight();
      
      foreach ($rights as $right => $value) {
         if ($dbu->countElementsInTable('glpi_profilerights',
                                 ["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
            $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
         }
         if (!$dbu->countElementsInTable('glpi_profilerights',
                                 ["profiles_id" => $profiles_id, "name" => $right])) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

   /**
    * Show profile form
    *
    * @param $items_id integer id of the profile
    * @param $target value url of target
    *
    * @return nothing
    **/
   function dddd($profiles_id = 0, $openform = true, $closeform = true) {

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         $profile = new Profile();
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      //if ($profile->getField('interface') == 'central') {
         $rights = $this->getAllRights();
         $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                         'default_class' => 'tab_bg_2',
                                                         'title'         => __('General')]);
      //}

      if ($canedit
         && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }

   function dropdownInterface(array $options = []){
      $p = [
         'value' => $this->fields["interface"]
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      return PluginServicesDropdown::showFromArray('interface',self::getInterfaces(), $p);
   }
   
   function showForm($ID, $options = []) {
      $onfocus = "";
      $new     = false;
      $rowspan = 4;
      if ($ID > 0) {
         $rowspan++;
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE);
         $onfocus = "onfocus=\"if (this.value=='".$this->fields["name"]."') this.value='';\"";
         $new     = true;
      }

      $rand = mt_rand();
      $all_fields = [
         [
            'label'=> 'Name',
            'name'=> 'name',
            'type'=> 'text'
         ],[
            'label'=> "Profile's interface",
            'type'=> 'function',
            'name'=> 'dropdownInterface',
            'params'=> [
               'name' => 'interface',
               'rand'  => mt_rand(),
               'display_emptychoice' => true
            ]
         ],[
            'label'=> 'Default profile',
            'name'=> 'is_default',
            'type'=> 'boolean',
         ],[
            'label'=> "Update password",
            'name'=> 'password_update',
            'type'=> 'boolean',
         ],
         // [
         //    'label'=> "Ticket creation form on login",
         //    'name'=> 'create_ticket_on_login',
         //    'type'=> 'boolean',
         // ],
         [
            'label'=> 'Comments',
            'name'=> 'comment',
            'type'=> 'textarea',
            'full'=> true
         ]
      ];

      PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
   }

   /**
    * @param bool $all
    *
    * @return array
    */
   static function getAllRights($all = false) {
      $rights = [
         [
            'itemtype'  => 'PluginServicesTheme',
            'label'     => _n('Themes', 'Themes', 2, 'Themes'),
            'field'     => 'plugin_services_themes'
         ],[
            'itemtype'  => 'PluginServicesTicket',
            'label'     => _n('Tickets', 'Tickets', 2, 'tickets'),
            'field'     => 'tickets'
         ],[
            'itemtype'  => 'PluginServicesService',
            'label'     => _n('Administration', 'Administration', 2, 'Administration'),
            'field'     => 'plugin_services'
         ],[
            'itemtype'  => 'PluginServicesTicketTask',
            'label'     => _n('Ticket task', 'Ticket task', 2, 'Ticket task'),
            'field'     => 'plugin_services_ticket_task'
         ],[
            'itemtype'  => 'PluginServicesGroup',
            'label'     => _n('Group', 'Group', 2, 'Group'),
            'field'     => 'group'
         ],[
            'itemtype'  => 'PluginServicesUser',
            'label'     => _n('Utilisateur', 'Utilisateur', 2, 'Utilisateur'),
            'field'     => 'plugin_services_user'
         ],[    
            'itemtype'  => 'PluginServicesEntity',
            'label'     => _n('Entity', 'Entity', 2, 'Entity'),
            'field'     => 'entity'
         ],[
            'itemtype'  => 'PluginServicesRuleTicket',
            'label'     => __('Business rules for tickets'),
            'field'     => 'rule_ticket'
         ],[
            'itemtype'  => 'PluginServicesSLM',
            'label'     => _n('Service level', 'Service levels', 0),
            'field'     => 'slm'
         ],[  
            'itemtype'  => 'PluginServicesITILSolution',
            'label'     => _n('Solution', 'Solution', 2, 'Solution'),
            'field'     => 'plugin_services_itil_solution'
         ],[  
            'itemtype'  => 'PluginServicesCompany',
            'label'     => _n('Company', 'Company', 2, 'Company'),
            'field'     => 'plugin_services_company'
         ],[  
            'itemtype'  => 'PluginServicesDepartement',
            'label'     => _n('Departement', 'Departement', 2, 'Departement'),
            'field'     => 'plugin_services_departement'
         ],[  
            'itemtype'  => 'PluginServicesRelatedlist',
            'label'     => _n('Relatedlists', 'Relatedlists', 2, 'Relatedlists'),
            'field'     => 'plugin_services_relatedlist'
         ],[  
            'itemtype'  => 'PluginServicesPlugin',
            'label'     => _n('Plugin', 'Plugins', 0),
            'field'     => 'config'
         ],[  
            'itemtype'  => 'PluginServicesProfile',
            'label'     => _n('Profile', 'Profiles', 0),
            'field'     => 'profile'
         ],[  
            'itemtype'  => 'PluginServicesLocation',
            'label'     =>  _n('Location', 'Locations', 0),
            'field'     => 'location'
         ]
      ];
      return $rights;
   }

   /**
    * Init profiles
    *
   **/
   static function translateARight($old_right) {
      switch ($old_right) {
         case '':
            return 0;
         case 'r' :
            return READ;
         case 'w':
            return ALLSTANDARDRIGHT + READNOTE + UPDATENOTE;
         case '0':
         case '1':
            return $old_right;

         default :
            return 0;
      }
   }

   /**
   * @since 0.85
   * Migration rights from old system to the new one for one profile
   * @param $profiles_id the profile ID
   */
   static function migrateOneProfile($profiles_id) {
      global $DB;
      //Cannot launch migration if there's nothing to migrate...
      if (!$DB->tableExists('glpi_plugin_services_profiles')) {
         return true;
      }

      foreach ($DB->request('glpi_plugin_services_profiles',
                           "`profiles_id`='$profiles_id'") as $profile_data) {

         $matching = ['services'    => 'plugin_services'];
         $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
         foreach ($matching as $old => $new) {
            if (!isset($current_rights[$old])) {
               $query = "UPDATE `glpi_profilerights` 
                        SET `rights`='".self::translateARight($profile_data[$old])."' 
                        WHERE `name`='$new' AND `profiles_id`='$profiles_id'";
               $DB->query($query);
            }
         }
      }
   }

   /**
   * Initialize profiles, and migrate it necessary
   */
   static function initProfile() {
      global $DB;
      $profile = new self();
      $dbu     = new DbUtils();
      //Add new rights in glpi_profilerights table
      foreach ($profile->getAllRights(true) as $data) {
         if ($dbu->countElementsInTable("glpi_profilerights",
                                  ["name" => $data['field']]) == 0) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }

      //Migration old rights in new ones
      foreach ($DB->request("SELECT `id` FROM `glpi_profiles`") as $prof) {
         self::migrateOneProfile($prof['id']);
      }
      foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='".$_SESSION['glpiactiveprofile']['id']."' 
                              AND `name` LIKE '%plugin_services%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }
   }

   static function removeRightsFromSession() {
      foreach (self::getAllRights(true) as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
   }


   public function showList($itemtype, $params){
      PluginServicesHtml::showList($itemtype, $params);
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
}
