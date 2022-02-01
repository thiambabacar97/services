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
 * Profile_User Class
**/
class PluginServicesProfile_User extends Profile_User {


       // From CommonDBTM
   public $auto_message_on_action               = false;

   // From CommonDBRelation
   static public $itemtype_1                    = 'User';
   static public $items_id_1                    = 'users_id';

   static public $itemtype_2                    = 'Profile';
   static public $items_id_2                    = 'profiles_id';
   static public $checkItem_2_Rights            = self::DONT_CHECK_ITEM_RIGHTS;

   // Specific log system
   static public $logs_for_item_2               = false;
   static public $logs_for_item_1               = true;
   static public $log_history_1_add             = Log::HISTORY_ADD_SUBITEM;
   static public $log_history_1_delete          = Log::HISTORY_DELETE_SUBITEM;

   // Manage Entity properties forwarding
   static public $disableAutoEntityForwarding   = true;


   static function showForEntity(Entity $entity) {
        global $DB;
  
        $ID = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
           return false;
        }
  
        $canedit     = $entity->canEdit($ID);
        $canshowuser = User::canView();
        $nb_per_line = 3;
        $rand        = mt_rand();
  
        if ($canedit) {
           $headerspan = $nb_per_line*2;
        } else {
           $headerspan = $nb_per_line;
        }
  
        if ($canedit) {
           echo "<div class='firstbloc m-0'>";
           echo "<form name='entityuser_form$rand' id='mainform' method='post' action='../../../../front/profile_user.form.php'>";


           echo '<div class="form-group m-form__group row pb-à">';


            echo "<label class='col-lg-1 col-form-label'>".__('User')."</label>";
                echo "<div class='col-lg-3'>";
                echo "<input type='hidden' name='entities_id' value='$ID'>";
                PluginServicesUser::dropdown(['right' => 'all']);
                echo "</div>";


                echo "<label class='col-lg-1 col-form-label'>".self::getTypeName(1)."</label>";
                echo "<div class='col-lg-3'>";
                Profile::dropdownUnder(['value' => Profile::getDefault()]);
                echo "</div>";


                echo "<label class='col-lg-1 col-form-label'>".__('Recursive')."</label>";
                echo "<div class='col-lg-3'>";
                Dropdown::showYesNo("is_recursive", 0);
                echo "</div>";

                echo '<div class="col-lg-12 mt-3 d-flex justify-content-end">';
                echo "<button type='submit' name='add' class='btn btn-secondary active my-2 btn-sm'>"._sx('button', 'Add')."</button>";
                echo'</div>';
                Html::closeForm();
           echo'</div>';


        //    echo "<table style='max-width: 100%;' class='tab_cadre_fixe'>";
        //    echo "<tr class='tab_bg_1'><th colspan='6'>".__('Add an authorization to a user')."</tr>";
        //    echo "<tr class='tab_bg_1'><td class='tab_bg_2 center'>".__('User')."&nbsp;";
        //    echo "<input type='hidden' name='entities_id' value='$ID'>";
        //    User::dropdown(['right' => 'all']);
        //    echo "</td><td class='tab_bg_2 center'>".self::getTypeName(1)."</td><td>";
        //    Profile::dropdownUnder(['value' => Profile::getDefault()]);
        //    echo "</td><td class='tab_bg_2 center'>".__('Recursive')."</td><td>";
        //    Dropdown::showYesNo("is_recursive", 0);
        //    echo "</td><td class='tab_bg_2 center'>";
        //    echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
        //    echo "</td></tr>";
        //    echo "</table>";
        //    Html::closeForm();
        //    echo "</div>";
        }
  
        $putable = Profile_User::getTable();
        $ptable = Profile::getTable();
        $utable = User::getTable();
  
        $iterator = $DB->request([
           'SELECT'       => [
              "glpi_users.*",
              "$putable.id AS linkid",
              "$putable.is_recursive",
              "$putable.is_dynamic",
              "$ptable.id AS pid",
              "$ptable.name AS pname"
           ],
           'FROM'         => $putable,
           'INNER JOIN'   => [
              $utable => [
                 'ON' => [
                    $putable => 'users_id',
                    $utable  => 'id'
                 ]
              ],
              $ptable  => [
                 'ON' => [
                    $putable => 'profiles_id',
                    $ptable  => 'id'
                 ]
              ]
           ],
           'WHERE'        => [
              "$utable.is_deleted"    => 0,
              "$putable.entities_id"  => $ID
           ],
           'ORDERBY'      => [
              "$putable.profiles_id",
              "$utable.name",
              "$utable.realname",
              "$utable.firstname"
           ]
        ]);
  
        $nb = count($iterator);
  
        echo "<div class='spaced'>";
        if ($canedit && $nb) {
            PluginServicesHtml::openMassiveActionsForm('massProfile_User'.$rand);
           $massiveactionparams
              = ['container'
                          => 'massProfile_User'.$rand,
                      'specific_actions'
                          => ['purge' => _x('button', 'Delete permanently')]];
           PluginServicesHtml::showMassiveActions($massiveactionparams);
        }
        echo "<table style='max-width:100%; box-shadow:none;' class='tab_cadre_fixehov'>";
        echo "<thead><tr>";
  
        echo "<th class='noHover' colspan='$headerspan'>";
        printf(__('%1$s (%2$s)'), _n('User', 'Users', Session::getPluralNumber()), __('D=Dynamic, R=Recursive'));
        echo "</th></tr></thead>";
  
        if ($nb) {
           Session::initNavigateListItems(
              'User',
              //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
              sprintf(
                 __('%1$s = %2$s'),
                 Entity::getTypeName(1),
                 $entity->getName()
              )
           );
  
           $current_pid = null;
           while ($data = $iterator->next()) {
              if ($data['pid'] != $current_pid) {
                 echo "<tbody><tr class='noHover'>";
                 $reduce_header = 0;
                 if ($canedit && $nb) {
                    echo "<th width='10'>";
                    echo Html::getCheckAllAsCheckbox("profile".$data['pid']."_$rand");
                    echo "</th>";
                    $reduce_header++;
                 }
                 echo "<th colspan='".($headerspan-$reduce_header)."'>";
                 printf(__('%1$s: %2$s'), __('Profile'), $data["pname"]);
                 echo "</th></tr></tbody>";
                 echo "<tbody id='profile".$data['pid']."_$rand'>";
                 $i = 0;
              }
  
              Session::addToNavigateListItems('User', $data["id"]);
  
              if (($i%$nb_per_line) == 0) {
                 if ($i  !=0) {
                    echo "</tr>";
                 }
                 echo "<tr class='tab_bg_1'>";
              }
              if ($canedit) {
                 echo "<td width='10'>";
                 Html::showMassiveActionCheckBox('Profile_User', $data["linkid"]);
                 echo "</td>";
              }
  
              $username = formatUserName($data["id"], $data["name"], $data["realname"],
                                         $data["firstname"], $canshowuser);
  
              if ($data["is_dynamic"] || $data["is_recursive"]) {
                 $username = sprintf(__('%1$s %2$s'), $username, "<span class='b'>(");
                 if ($data["is_dynamic"]) {
                    $username = sprintf(__('%1$s%2$s'), $username, __('D'));
                 }
                 if ($data["is_dynamic"] && $data["is_recursive"]) {
                    $username = sprintf(__('%1$s%2$s'), $username, ", ");
                 }
                 if ($data["is_recursive"]) {
                    $username = sprintf(__('%1$s%2$s'), $username, __('R'));
                 }
                 $username = sprintf(__('%1$s%2$s'), $username, ")</span>");
              }
              echo "<td>".$username."</td>";
              $i++;
  
              $current_pid = $data['pid'];
              if ($data['pid'] != $current_pid) {
                 echo "</tr>";
                 echo "</tbody>";
              }
           }
        }
        echo "</table>";
        if ($canedit && $nb) {
           $massiveactionparams['ontop'] = false;
           //Html::showMassiveActions($massiveactionparams);
           Html::closeForm();
        }
        echo "</div>";
        echo Html::css('assets/css/customer.glpi.form.css');
   }

   /**
    * Get entities for which a user have a right
    *
    * @param $user_ID         user ID
    * @param $is_recursive    check also using recursive rights (true by default)
    * @param $default_first   user default entity first (false by default)
    *
    * @return array of entities ID
   **/
   static function getUserEntities($user_ID, $is_recursive = true, $default_first = false) {
      global $DB;

      $iterator = $DB->request([
         'SELECT'          => [
            'entities_id',
            'is_recursive'
         ],
         'DISTINCT'        => true,
         'FROM'            => 'glpi_profiles_users',
         'WHERE'           => ['users_id' => $user_ID]
      ]);
      $entities = [];

      while ($data = $iterator->next()) {
         if ($data['is_recursive'] && $is_recursive) {
            $tab      = getSonsOf('glpi_entities', $data['entities_id']);
            $entities = array_merge($tab, $entities);
         } else {
            $entities[] = $data['entities_id'];
         }
      }

      // Set default user entity at the begin
      if ($default_first) {
         $user = new User();
         if ($user->getFromDB($user_ID)) {
            $ent = $user->getField('entities_id');
            if (in_array($ent, $entities)) {
               array_unshift($entities, $ent);
            }
         }
      }

      return array_unique($entities);
   }


   /**
    * Get entities for which a user have a right
    *
    * @since 0.84
    * @since 9.2  Add $rightname parameter
    *
    * @param integer $user_ID      user ID
    * @param string  $rightname    name of the rights to check (CommonDBTM::$rightname)
    * @param integer $rights       rights to check (may be a OR combinaison of several rights)
    *                              (exp: CommonDBTM::READ | CommonDBTM::UPDATE ...)
    * @param boolean $is_recursive check also using recursive rights (true by default)
    *
    * @return array of entities ID
   **/
   static function getUserEntitiesForRight($user_ID, $rightname, $rights, $is_recursive = true) {
      global $DB;

      $putable = Profile_User::getTable();
      $ptable = Profile::getTable();
      $prtable = ProfileRight::getTable();
      $iterator = $DB->request([
         'SELECT'          => [
            "$putable.entities_id",
            "$putable.is_recursive"
         ],
         'DISTINCT'        => true,
         'FROM'            => $putable,
         'INNER JOIN'      => [
            $ptable  => [
               'ON' => [
                  $putable => 'profiles_id',
                  $ptable  => 'id'
               ]
            ],
            $prtable => [
               'ON' => [
                  $prtable => 'profiles_id',
                  $ptable  => 'id'
               ]
            ]
         ],
         'WHERE'           => [
            "$putable.users_id"  => $user_ID,
            "$prtable.name"      => $rightname,
            "$prtable.rights"    => ['&', $rights]
         ]
      ]);

      if (count($iterator) > 0) {
         $entities = [];

         while ($data = $iterator->next()) {
            if ($data['is_recursive'] && $is_recursive) {
               $tab      = getSonsOf('glpi_entities', $data['entities_id']);
               $entities = array_merge($tab, $entities);
            } else {
               $entities[] = $data['entities_id'];
            }
         }

         return array_unique($entities);
      }

      return [];
   }


   /**
    * Get user profiles (no entity association, use sqlfilter if needed)
    *
    * @since 9.3 can pass sqlfilter as a parameter
    *
    * @param $user_ID            user ID
    * @param $sqlfilter  string  additional filter (default [])
    *
    * @return array of the IDs of the profiles
   **/
   static function getUserProfiles($user_ID, $sqlfilter = []) {
      global $DB;

      $profiles = [];

      $where = ['users_id' => $user_ID];
      if (count($sqlfilter) > 0) {
         $where = $where + $sqlfilter;
      }

      $iterator = $DB->request([
         'SELECT'          => 'profiles_id',
         'DISTINCT'        => true,
         'FROM'            => 'glpi_profiles_users',
         'WHERE'           => $where
      ]);

      while ($data = $iterator->next()) {
         $profiles[$data['profiles_id']] = $data['profiles_id'];
      }

      return $profiles;
   }


   /**
    * retrieve the entities allowed to a user for a profile
    *
    * @param $users_id     Integer  ID of the user
    * @param $profiles_id  Integer  ID of the profile
    * @param $child        Boolean  when true, include child entity when recursive right
    *                               (false by default)
    *
    * @return Array of entity ID
   **/
   static function getEntitiesForProfileByUser($users_id, $profiles_id, $child = false) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => ['entities_id', 'is_recursive'],
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'users_id'     => $users_id,
            'profiles_id'  => $profiles_id
         ]
      ]);

      $entities = [];
      while ($data = $iterator->next()) {
         if ($child
             && $data['is_recursive']) {
            foreach (getSonsOf('glpi_entities', $data['entities_id']) as $id) {
               $entities[$id] = $id;
            }
         } else {
            $entities[$data['entities_id']] = $data['entities_id'];
         }
      }
      return $entities;
   }


   /**
    * retrieve the entities associated to a user
    *
    * @param $users_id     Integer  ID of the user
    * @param $child        Boolean  when true, include child entity when recursive right
    *                               (false by default)
    *
    * @since 0.85
    *
    * @return Array of entity ID
   **/
   static function getEntitiesForUser($users_id, $child = false) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => ['entities_id', 'is_recursive'],
         'FROM'   => 'glpi_profiles_users',
         'WHERE'  => ['users_id' => $users_id]
      ]);

      $entities = [];
      while ($data = $iterator->next()) {
         if ($child
             && $data['is_recursive']) {
            foreach (getSonsOf('glpi_entities', $data['entities_id']) as $id) {
               $entities[$id] = $id;
            }
         } else {
            $entities[$data['entities_id']] = $data['entities_id'];
         }
      }
      return $entities;
   }


   /**
    * Get entities for which a user have a right
    *
    * @param $user_ID         user ID
    * @param $only_dynamic    get only recursive rights (false by default)
    *
    * @return array of entities ID
   **/
   static function getForUser($user_ID, $only_dynamic = false) {
      $condition = ['users_id' => $user_ID];

      if ($only_dynamic) {
         $condition['is_dynamic'] = 1;
      }

      return getAllDataFromTable('glpi_profiles_users', $condition);
   }


   /**
    * @param $user_ID
    * @param $profile_id
   **/
   static function haveUniqueRight($user_ID, $profile_id) {
      global $DB;

      $result = $DB->request([
         'COUNT'  => 'cpt',
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'users_id'     => $user_ID,
            'profiles_id'  => $profile_id
         ]
      ])->next();
      return $result['cpt'];
   }


   /**
    * @param $user_ID
    * @param $only_dynamic    (false by default)
   **/
   static function deleteRights($user_ID, $only_dynamic = false) {

      $crit = [
         'users_id' => $user_ID,
      ];

      if ($only_dynamic) {
         $crit['is_dynamic'] = '1';
      }

      $obj = new self();
      $obj->deleteByCriteria($crit);
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
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
         'field'              => 'is_dynamic',
         'name'               => __('Dynamic'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_profiles',
         'field'              => 'name',
         'name'               => self::getTypeName(1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => User::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => Entity::getTypeName(1),
         'massiveaction'      => true,
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

      return $tab;
   }


   static function getTypeName($nb = 0) {
      return _n('Profile', 'Profiles', $nb);
   }

   protected function computeFriendlyName() {

      $name = sprintf(__('%1$s, %2$s'),
                      Dropdown::getDropdownName('glpi_profiles', $this->fields['profiles_id']),
                      Dropdown::getDropdownName('glpi_entities', $this->fields['entities_id']));

      if (isset($this->fields['is_dynamic']) && $this->fields['is_dynamic']) {
         //TRANS: D for Dynamic
         $dyn  = __('D');
         $name = sprintf(__('%1$s, %2$s'), $name, $dyn);
      }
      if (isset($this->fields['is_recursive']) && $this->fields['is_recursive']) {
         //TRANS: R for Recursive
         $rec  = __('R');
         $name = sprintf(__('%1$s, %2$s'), $name, $rec);
      }
      return $name;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $DB;

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Entity' :
               if (Session::haveRight('user', READ)) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $count = $DB->request([
                        'COUNT'     => 'cpt',
                        'FROM'      => $this->getTable(),
                        'LEFT JOIN' => [
                           User::getTable() => [
                              'FKEY' => [
                                 $this->getTable() => 'users_id',
                                 User::getTable()  => 'id'
                              ]
                           ]
                        ],
                        'WHERE'     => [
                           User::getTable() . '.is_deleted'    => 0,
                           $this->getTable() . '.entities_id'  => $item->getID()
                        ]
                     ])->next();
                     $nb        = $count['cpt'];
                  }
                  return self::createTabEntry(User::getTypeName(Session::getPluralNumber()), $nb);
               }
               break;

            case 'Profile' :
               if (Session::haveRight('user', READ)) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = self::countForItem($item);
                  }
                  return self::createTabEntry(User::getTypeName(Session::getPluralNumber()), $nb);
               }
               break;

            case 'User' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForItem($item);
               }
               return self::createTabEntry(_n('Authorization', 'Authorizations',
                                           Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Entity' :
            self::showForEntity($item);
            break;

         case 'Profile' :
            self::showForProfile($item);
            break;

         case 'User' :
            self::showForUser($item);
            break;
      }
      return true;
   }


   /**
    * @since 0.85
    *
    * @see CommonDBRelation::getRelationMassiveActionsSpecificities()
   **/
   static function getRelationMassiveActionsSpecificities() {
      $specificities                            = parent::getRelationMassiveActionsSpecificities();

      $specificities['dropdown_method_2']       = 'dropdownUnder';
      $specificities['can_remove_all_at_once']  = false;

      return $specificities;
   }


   /**
    * @since 0.85
    *
    * @see CommonDBRelation::showRelationMassiveActionsSubForm()
   **/
   static function showRelationMassiveActionsSubForm(MassiveAction $ma, $peer_number) {

      if (($ma->getAction() == 'add')
          && ($peer_number == 2)) {
         echo "<br><br>".sprintf(__('%1$s: %2$s'), Entity::getTypeName(1), '');
         Entity::dropdown(['entity' => $_SESSION['glpiactiveentities']]);
         echo "<br><br>".sprintf(__('%1$s: %2$s'), __('Recursive'), '');
         Html::showCheckbox(['name' => 'is_recursive']);
      }
   }


   /**
    * @since 0.85
    *
    * @see CommonDBRelation::getRelationInputForProcessingOfMassiveActions()
   **/
   static function getRelationInputForProcessingOfMassiveActions($action, CommonDBTM $item,
                                                                 array $ids, array $input) {
      $result = [];
      if (isset($input['entities_id'])) {
         $result['entities_id'] = $input['entities_id'];
      }
      if (isset($input['is_recursive'])) {
         $result['is_recursive'] = $input['is_recursive'];
      }

      return $result;
   }

   /**
    * Get linked items list for specified item
    *
    * @since 9.3.1
    *
    * @param CommonDBTM $item  Item instance
    * @param boolean    $noent Flag to not compute entity informations (see Document_Item::getListForItemParams)
    *
    * @return array
    */
   protected static function getListForItemParams(CommonDBTM $item, $noent = false) {
      $params = parent::getListForItemParams($item, $noent);
      $params['SELECT'][] = self::getTable() . '.entities_id';
      $params['SELECT'][] = self::getTable() . '.is_recursive';
      $params['SELECT'][] = 'glpi_entities.completename AS completename';
      $params['LEFT JOIN']['glpi_entities'] = [
         'FKEY'   => [
            self::getTable()  => 'entities_id',
            'glpi_entities'   => 'id'
         ]
      ];
      return $params;
   }

      /**
    * Get user profiles (no entity association, use sqlfilter if needed)
    *
    * @since 9.3 can pass sqlfilter as a parameter
    *
    * @param $user_ID            user ID
    * @param $sqlfilter  string  additional filter (default [])
    *
    * @return array of the IDs of the profiles
   **/
   static function getProfileByUser($user_ID, $sqlfilter = []) {
      global $DB;
      $profiles = [];

      $where = ['users_id' => $user_ID];
      if (count($sqlfilter) > 0) {
         $where = $where + $sqlfilter;
      }

      $iterator =   $DB->request([
         'FROM'       => 'glpi_profiles',
         'DISTINCT'        => true,
         'INNER JOIN' => [
            'glpi_profiles_users' => [
               'FKEY' => [
                  'glpi_profiles'     => 'id',
                  'glpi_profiles_users' => 'profiles_id',
               ]
            ]
         ],
         'WHERE'  => $where
      ]);

      while ($data = $iterator->next()) {
         $profiles =  $data;
      }
      return $profiles;
   }
}
