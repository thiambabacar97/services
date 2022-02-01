<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginservicesProfile
 */
class PluginServicesProfile extends Profile {

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
      return "Profile";
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

      if ($item->getType()=='Profile') {
         return 'FAGO - Services';
      }
      return '';
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

      if ($item->getType()=='Profile') {
         $ID = $item->getID();
         $prof = new self();

         self::addDefaultProfileInfos($ID,
                                    ['plugin_services'=>0,
                                    'plugin_services_apparences'=>0,
                                    'plugin_services_themes'=> 0,
                                    'plugin_services_users'=> 0,
                                    'plugin_services_entity' => 0,
                                    'plugin_services_group_user' => 0,
                                    'plugin_services_ruleticket' => 0,
                                    'plugin_services_slm'       => 0,
                                    'plugin_services_slalevel'  => 0,
                                    'plugin_services_sla'    => 0,
                                    'plugin_services_ola'    => 0,
                                    'plugin_services_itil_solution' =>0,
                                    'plugin_services' => 0,
                                    'plugin_services_grid' => 0,
                                    'plugin_services_auth_ldap'=> 0,
                                    'plugin_services_group' => 0,
                                    'plugin_services_location' => 0,
                                    'plugin_services_grid_tickets' => 0,
                                    'plugin_services_item_cards' => 0,
                                    'plugin_services_relatedlist' => 0,
                                    'plugin_services_changevalidation' =>0,
                                    'plugin_services_changetask' => 0,
                                    // 'plugin_services_provider'    =>0
                                    ]);
         $prof->showForm($ID);
      }
      return true;
   }


   /**
    * @param $ID
    */
   static function createFirstAccess($ID) {
      //85
      self::addDefaultProfileInfos($ID,
                                    ['plugin_services_apparences' => 23,
                                    'plugin_services_themes' => 23,
                                    'plugin_services' => 23, 
                                    'plugin_services_group' => 23,
                                    'plugin_services_location' => 23,
                                    'plugin_services_users' => 23,
                                    'plugin_services_entity' => 23,
                                    'plugin_services_group_user' => 23,
                                    'plugin_services_ruleticket' => 23,
                                    'plugin_services_slm'       => 23,
                                    'plugin_services_slalevel'  => 23,
                                    'plugin_services_sla'    => 23,
                                    'plugin_services_ola'    => 23,
                                    'plugin_services_itil_solution' => 23,
                                    'plugin_services' => 23,
                                    'plugin_services_grid' => 23,
                                    'plugin_services_auth_ldap' => 23,
                                    'plugin_services_grid_tickets' => 23,
                                    'plugin_services_item_cards' => 23,
                                    'plugin_services_relatedlist' => 23,
                                    'plugin_services_changevalidation' => 23,
                                    'plugin_services_changetask' => 23,
                                    // 'plugin_services_provider' => 23
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
   function showForm($profiles_id = 0, $openform = true, $closeform = true) {

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
               'field'     => 'plugin_services'
         ],[
            'itemtype'  => 'PluginServicesService',
            'label'     => _n('Administration', 'Administration', 2, 'Administration'),
            'field'     => 'plugin_services'
         ],[
            'itemtype'  => 'PluginServicesGroup',
            'label'     => _n('Groupe', 'Groupe', 2, 'Groupe'),
            'field'     => 'plugin_services_group'
         ],[
            'itemtype'  => 'PluginServicesLocation',
            'label'     => _n('Localisation', 'Localisation', 2, 'Localisation'),
            'field'     => 'plugin_services_location'
         ],[
            'itemtype'  => 'PluginServicesUser',
            'label'     => _n('Utilisateur', 'Utilisateur', 2, 'Utilisateur'),
            'field'     => 'plugin_services_users'
         ],[
            'itemtype'  => 'PluginServicesEntity',
            'label'     => _n('Entity', 'Entity', 2, 'Entity'),
            'field'     => 'plugin_services_entity'
         ],[
            'itemtype'  => 'PluginServicesGroup_User',
            'label'     => _n('Group_User', 'Group_User', 2, 'Group_User'),
            'field'     => 'plugin_services_group_user'
         ],[
            'itemtype'  => 'PluginServicesRuleTicket',
            'label'     => _n('RuleTicket', 'RuleTicket', 2, 'RuleTicket'),
            'field'     => 'plugin_services_ruleticket'
         ],[
            'itemtype'  => 'PluginServicesSLM',
            'label'     => _n('SLM', 'SLM', 2, 'SLM'),
            'field'     => 'plugin_services_slm'
         ],[
            'itemtype'  => 'PluginServicesSlalevel',
            'label'     => _n('Slalevel', 'Slalevel', 2, 'Slalevel'),
            'field'     => 'plugin_services_slalevel'
         ],[ 
            'itemtype'  => 'PluginServicesOLA',
            'label'     => _n('OLA', 'OLA', 2, 'OLA'),
            'field'     => 'plugin_services_ola'
         ],[ 
            'itemtype'  => 'PluginServicesSla',
            'label'     => _n('SLA', 'SLA', 2, 'SLA'),
            'field'     => 'plugin_services_sla'
         ],[ 
            'itemtype'  => 'PluginServicesOLA',
            'label'     => _n('OLA', 'OLA', 2, 'OLA'),
            'field'     => 'plugin_services_ola'
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
            'itemtype'  => 'PluginServicesAuthLDAP',
            'label'     => _n('AuthLDAP', 'AuthLDAP', 2, 'AuthLDAP'),
            'field'     => 'plugin_services_auth_ldap'
         ],[  
            'itemtype'  => 'PluginServicesRelatedlist',
            'label'     => _n('Relatedlists', 'Relatedlists', 2, 'Relatedlists'),
            'field'     => 'plugin_services_relatedlist'
         ],[  
            'itemtype'  => 'PluginServicesChangeValidation',
            'label'     => _n('Change validation', 'Change validation', 2, 'Change validation'),
            'field'     => 'plugin_services_changevalidation'
         ],[  
            'itemtype'  => 'PluginServicesChangeTask',
            'label'     => _n('Change task', 'Change task', 2, 'Change task'),
            'field'     => 'plugin_services_changetask'
         ],[  
            'itemtype'  => 'PluginServicesPlugin',
            'label'     => _n('Plugins', 'Plugins', 2, 'Plugins'),
            'field'     => 'plugin_services'
         ],[  
            'itemtype'  => 'PluginServicesGrid',
            'label'     => _n('Dashboard', 'Dashboard', 2, 'Dashboard'),
            'field'     => 'plugin_services_grid'
         ],[  
            'itemtype'  => 'PluginServicesItem',
            'label'     => _n('Item Card', 'Item Card', 2, 'Item Card'),
            'field'     => 'plugin_services_item_cards'
         ],[  
            'itemtype'  => 'PluginServicesGrid_Ticket',
            'label'     => _n('Grid_Ticket', 'Grid_Ticket', 2, 'Grid_Ticket'),
            'field'     => 'plugin_services_grid_tickets'
         ],
         // [  
         //    'itemtype'  => 'PluginServicesProvider',
         //    'label'     => _n('Provider', 'Provider', 2, 'Provider'),
         //    'field'     => 'plugin_services_provider'
         // ]
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
      echo'<div class="m-content">
          <div class="row">
              <div class="col-xl-12 ">
              <div class="m-portlet m-portlet--tab">
                  <div class="m-portlet__head">
                  <div class="m-portlet__head-caption">
                      <div class="m-portlet__head-title">
                      <span class="m-portlet__head-icon m--hide">
                          <i class="la la-gear"></i>
                      </span>
                      <h3 class="m-portlet__head-text">
                          '.__('Profiles').'
                      </h3>
                      </div>
                  </div>
                  </div>
                  <div class="m-portlet__body">';
                      PluginServicesSearch::showFago("PluginServicesProfile", $params);
                  echo'  
                      </div>
                  </div>
              </div>
          </div>
      </div>';
   }
}
