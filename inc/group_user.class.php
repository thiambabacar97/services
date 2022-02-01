<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Group_User Class
 *
 *  Relation between Group and User
**/
use Glpi\Event;
class PluginServicesGroup_User extends Group_User{

   // From CommonDBRelation

   static $rightname                  = 'plugin_services_group_user';
   static $itemtype_1                 = 'User';
   static $items_id_1                 = 'users_id';

   static $itemtype_2                 = 'Group';
   static $items_id_2                 = 'groups_id';

   /**
   * Check if a user belongs to a group
   *
   * @since 9.4
   *
   * @param integer $users_id  the user ID
   * @param integer $groups_id the group ID
   *
   * @return boolean true if the user belongs to the group
   */
   static function isUserInGroup($users_id, $groups_id) {
      return countElementsInTable(
         'glpi_groups_users', [
            'users_id' => $users_id,
            'groups_id' => $groups_id
         ]
      ) > 0;
   }

   static function getTable($classname = null){
      return 'glpi_groups_users';
   }

   static function getClassName() {
      return get_called_class();
   }

   function showUserFago() {
      $datas = $this->find(['users_id' => $_GET['parentid']]);
      $groups_ids = [];
      foreach ($datas as $value) {
         array_push($groups_ids, $value['groups_id']);
      }
      return $groups_ids;
   }

   function showGroupFago() {
      $datas = $this->find(['groups_id' => $_GET['parentid']]);
      $users_ids = [];
      foreach ($datas as $value) {
         array_push($users_ids, $value['users_id']);
      }
      return $users_ids;
   }
   
   function showForUserFago($ID, array $options = []) {
      echo'<div class="m-content">';
         echo'<div class="row">';
            echo'<div class="col-lg-12">';
               echo'<div class="m-portlet">';
                  echo'<div class="m-portlet__head">';
                     echo'<div class="m-portlet__head-caption">';
                     echo'<div class="m-portlet__head-title">';
                        echo'<h3 class="m-portlet__head-text">Modifier les membres</h3>';
                     echo'</div>';
                     echo'</div>';
                  echo'</div>';
                  echo'<div class="m-portlet__body">';
                        
                  echo'<div class="row justify-content-center">';
                     echo'<div class="col-sm-4">';
                     echo'<h5 class="m-portlet__head-text">Collection</h5>';
                     echo' <input type="text" class="form-control m-input m-input--air m-input--pill mb-3" id="inputString" placeholder="search">';
                        echo'<ul id="modules"  class="form-control m-input m-input--air m-input--pill connectedSortable">';
                        $group = new PluginServicesGroup();
                        $groups = $group->find();
                        foreach ($groups as $value) {
                           if (in_array($value["id"], self::showUserFago())) {
                              continue;
                           }
                           echo'<li class="drop-item"><input type="hidden" name="groups_id[]" value="'.$value["id"].'"/>'.$value["completename"].'</li>';
                        }
                        echo'</ul>';
                     echo'</div>';
                     
                     echo'<div class="col-sm-4 mt-5">';
                     echo'<h5 class="m-portlet__head-text">Liste des membres du groupe</h5>';
                        echo'<form method="post" action="">';
                              echo'<ul id="modules1"  class="mt-4 connectedSortable form-control m-input m-input--air m-input--pill connectedSortable">';
                              $groups = self::getUserGroups($_GET['parentid']);   
                              foreach ($groups as $value) {
                                    echo'<li class="drop-item"><input type="hidden" name="groups_id[]" value="'.$value["id"].'"/>'.$value["completename"].'</li>';
                              }
                              echo'</ul>';
                              echo "<input type='hidden' name='users_id' value='".$_GET['parentid']."'>";
                              echo'<button type="submit" name="add" class="btn m-btn--radius btn-md  btn-info">Soumettre</button>';
                              Html::closeForm();
                        echo'</form>';
                     echo'</div>';
                     echo'</div>';
                  echo'</div>';
               echo'</div>';
            echo'</div>';
         echo'</div>';
      echo'</div>';
      $JS = <<<JAVASCRIPT
         $( function() {
            $( "#modules, #modules1" ).sortable({
               connectWith: ".connectedSortable",
               dropOnEmpty: true
            }).disableSelection();
         });

         jQuery("#inputString").keyup(function () {
            var filter = jQuery(this).val();
            jQuery("#modules li, #modules1 li").each(function () {
               if (jQuery(this).text().search(new RegExp(filter, "i")) < 0) {
                     jQuery(this).hide();
               } else {
                     jQuery(this).show()
               }
            });
         });
      JAVASCRIPT;
      echo Html::scriptBlock($JS);
      echo'
         <style>
            input:focus {
               outline: none !important;
               border:none !important;
            }

            #modules {
               padding: 20px;
               background: #f8f9fa;
               margin-bottom: 20px;
               min-height: 400px;
               height: 400px;
               overflow-y: scroll;
            }

            #modules1 {
               padding: 20px;
               background: #f8f9fa;
               margin-bottom: 20px;
               border-radius: 10px;
               height: 400px;
               overflow-y: scroll;
            }
   
            .drop-item {
               cursor: pointer;
               margin-bottom: 10px;
               background-color: rgb(255, 255, 255);
               padding: 5px 10px;
               border-radius: 3px;
               border: 1px solid rgb(204, 204, 204);
            }
            
         </style>
      ';
   }
   function showForGroupFago($ID, array $options = []) {
      echo'<div class="m-content">';
         echo'<div class="row">';
            echo'<div class="col-lg-12">';
               echo'<div class="m-portlet">';
                  echo'<div class="m-portlet__head">';
                     echo'<div class="m-portlet__head-caption">';
                     echo'<div class="m-portlet__head-title">';
                        echo'<h3 class="m-portlet__head-text">Modifier les membres</h3>';
                     echo'</div>';
                     echo'</div>';
                  echo'</div>';
                  echo'<div class="m-portlet__body">';
                     echo'<div class="row justify-content-center">';
                        echo'<div class="col-sm-4">';
                           echo'<h5 class="m-portlet__head-text">Collection</h5>';
                           echo'<input type="text" class="form-control m-input m-input--air m-input--pill mb-3" id="inputString" placeholder="search">';
                           echo'<ul id="modules"  class="form-control m-input m-input--air m-input--pill connectedSortable">';
                              $user = new PluginServicesUser();
                              $users = $user->find();
                              foreach ($users as $value) {
                                 if (in_array($value["id"], self::showGroupFago())) {
                                    continue;
                                 }
                                 $username = formatUserName($value["id"], $value["name"], $value["realname"], $value["firstname"], 0, 20);
                                 echo'<li class="drop-item"><input type="hidden" name="users_id[]" value="'.$value["id"].'"/>'.$username.'</li>';
                              }
                           echo'</ul>';
                        echo'</div>';
                        
                        echo'<div class="col-sm-4 mt-5">';
                           echo'<h5 class="m-portlet__head-text">Liste des membres du groupe</h5>';
                           echo'<form method="post" action="">';
                              echo'<ul id="modules1"  class="mt-4 connectedSortable form-control m-input m-input--air m-input--pill connectedSortable">';
                                 $groups = self::getGroupUsers($_GET['parentid']);   
                                 foreach ($groups as $value) {
                                       $username = formatUserName($value["id"], $value["name"], $value["realname"], $value["firstname"], 0, 20);
                                       echo'<li class="drop-item"><input type="hidden" name="users_id[]" value="'.$value["id"].'"/>'.$username.'</li>';
                                 }
                              echo'</ul>';
                              echo "<input type='hidden' name='groups_id' value='".$_GET['parentid']."'>";
                        echo'</div>';
                        echo'<div class="col-sm-8 text-right">';
                           echo'<button type="submit" name="add" class="btn m-btn--radius btn-md  btn-info">Soumettre</button>';
                        echo'</div>';
                        Html::closeForm();
                     echo'</div>';
                  echo'</div>';
               echo'</div>';
            echo'</div>';
         echo'</div>';
      echo'</div>';
      $JS = <<<JAVASCRIPT
         $( function() {
            $("li").click(function() {
               $(this).toggleClass("selected");
            });
            $( "#modules, #modules1" ).sortable({
               connectWith: ".connectedSortable",
               dropOnEmpty: true,
               scroll: true,
               start: function(e, info) {
                  info.item.siblings(".selected").appendTo(info.item);
               },
               stop: function(e, info) {
                  info.item.after(info.item.find("li"))
               }
            }).disableSelection();
         });

         jQuery("#inputString").keyup(function () {
            var filter = jQuery(this).val();
            jQuery("#modules li, #modules1 li").each(function () {
               if (jQuery(this).text().search(new RegExp(filter, "i")) < 0) {
                     jQuery(this).hide();
               } else {
                     jQuery(this).show()
               }
            });
         });
      JAVASCRIPT;
      echo Html::scriptBlock($JS);
   }

   function showForm($ID, array $options = []) {
      if (isset($_GET['parenttype']) && $_GET['parenttype']=="PluginServicesUser") {
         self::showForUserFago($ID,  $options);
      }elseif ((isset($_GET['parenttype']) && $_GET['parenttype']=="PluginServicesGroup")) {
         self::showForGroupFago($ID,  $options);
      }else {
         PluginServicesHtml::displayErrorAndDie("lost");
      }
   }
   /**
    * Get groups for a user
    *
    * @param integer $users_id  User id
    * @param array   $condition Query extra condition (default [])
    *
    * @return array
   **/
   static function getUserGroups($users_id, $condition = []) {
      global $DB;

      $groups = [];
      $iterator = $DB->request([
         'SELECT' => [
            'glpi_groups.*',
            'glpi_groups_users.id AS IDD',
            'glpi_groups_users.id AS linkid',
            'glpi_groups_users.is_dynamic AS is_dynamic',
            'glpi_groups_users.is_manager AS is_manager',
            'glpi_groups_users.is_userdelegate AS is_userdelegate'
         ],
         'FROM'   => self::getTable(),
         'LEFT JOIN'    => [
            Group::getTable() => [
               'FKEY' => [
                  Group::getTable() => 'id',
                  self::getTable()  => 'groups_id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_groups_users.users_id' => $users_id
         ] + $condition,
         'ORDER'        => 'glpi_groups.name'
      ]);
      while ($row = $iterator->next()) {
         $groups[] = $row;
      }

      return $groups;
   }

   function showUsersForGroups($item, $options=[]){
      self::showForGroup($item);
   }

   function showGroupsForUsers($item, $options=[]){
      global $CFG_GLPI;
      self::showForUser($item);
      $group = new PluginServicesGroup();
      $data = $this->find(['users_id'=>$item->fields['id']]);
      $canedit = true;
      $rand    = mt_rand();

      $number = count($data);
      $start  = (isset($_GET['start']) ? intval($_GET['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }
      if ($canedit) {
         PluginServicesHtml::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed'    => min($number-$start,
                                                                  $_SESSION['glpilist_limit']),
                                       'container'        => 'mass'.__CLASS__.$rand];
         echo "<div class='d-flex bd-highlight'>";
            echo "<div class='p-2 bd-highlight'>";
               Html::showMassiveActions($massiveactionparams);
            echo "</div>";
         echo"</div>";
      }
      
         echo "<table class='p-3 tab_cadrehov table table-striped m-table mt-3'>";
            // echo "<thead>";
               $header_begin  = "<tr class='tab_bg_2'>";
               $header_top    = '';
               $header_bottom = '';
               $header_end    = '';
               if ($canedit) {
                  $header_begin  .= "<th width='10'>";
                  $header_top    .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
                  $header_bottom .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
                  $header_end    .= "</th>";
               }
               $header_end .= "<th>".__('Name')."</th>";
               // $header_end .= "<th>".__('Dynamique')."</th>";
               $header_end .= "<th>".__('Superviseur')."</th>";
               $header_end .= "<th>".__('Délégataire')."</th>";
               echo $header_begin.$header_top.$header_end;
               foreach ($data as $item_i) {
                  $group->getFromDB($item_i['groups_id']);
                  echo"<tr class='tab_bg_2 rowHover'>";
                     if ($canedit) {
                        echo "<td width='10'>";
                        PluginServicesHtml::showMassiveActionCheckBox(__CLASS__, $item_i["id"]);
                        echo "</td>";
                     } 
                     echo "<td>";
                        echo"<a href='".$CFG_GLPI["root_doc"]."/group/form/?id=".$item_i['groups_id']."'>".$group->getField('name')."</a>";
                        // echo $group->getField('name');
                     echo "</td>";
                     echo "<td>";
                        if($item_i['is_manager']){
                           echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                              __('')."\">";
                        }
                     echo "</td>";
                     echo "<td>";
                        if($item_i['is_userdelegate']){
                           echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                              __('')."\">";
                        }
                     echo "</td>";
                     
                  echo"</tr>";
               }
         echo "</table>";
   }


   /**
    * Get users for a group
    *
    * @since 0.84
    *
    * @param integer $groups_id Group ID
    * @param array   $condition Query extra condition (default [])
    *
    * @return array
   **/
   static function getGroupUsers($groups_id, $condition = []) {
      global $DB;

      $users = [];

      $iterator = $DB->request([
         'SELECT' => [
            'glpi_users.*',
            'glpi_groups_users.id AS IDD',
            'glpi_groups_users.id AS linkid',
            'glpi_groups_users.is_dynamic AS is_dynamic',
            'glpi_groups_users.is_manager AS is_manager',
            'glpi_groups_users.is_userdelegate AS is_userdelegate'
         ],
         'FROM'   => self::getTable(),
         'LEFT JOIN'    => [
            User::getTable() => [
               'FKEY' => [
                  User::getTable() => 'id',
                  self::getTable()  => 'users_id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_groups_users.groups_id' => $groups_id
         ] + $condition,
         'ORDER'        => 'glpi_users.name'
      ]);
      while ($row = $iterator->next()) {
         $users[] = $row;
      }

      return $users;
   }

   function postForm($post) {
      $item_ = new self();
      if (isset($post["add"])) {
         if (isset($post["groups_id"]) && isset($post["users_id"]) ) {
            if (is_array($post["groups_id"]) && !empty($post["groups_id"])) {
               $exist_groups = $this->find(['users_id' => $post['users_id']]);
               if (!empty($exist_groups)) {
                  foreach ($exist_groups as $value) {
                     if (in_array($value['groups_id'], $post["groups_id"])) {
                        foreach ($post["groups_id"] as  $val) {
                           $params = [
                              "groups_id" =>  $val,
                              "users_id" =>  $post["users_id"]
                           ];
   
                           if (!empty($this->find($params))) { //verify if record is not duplicated
                              continue;
                           }
   
                           $item_->check(-1, CREATE, $params);
                           if ($item_->add($params)) {
                              Event::log($post["users_id"], "users", 4, "setup",
                                          //TRANS: %s is the user login
                                          sprintf(__('%s adds a user to a group'), $_SESSION["glpiname"]));
                           }
                        }
                     }else {
                        $item_->delete(['id' =>$value['id']], 1);
                     }
                  }
               }else {
                  foreach ($post["groups_id"] as  $val) {
                     $params = [
                        "groups_id" =>  $val,
                        "users_id" =>  $post["users_id"]
                     ];

                     if (!empty($this->find($params))) { //verify if record is not duplicated
                        continue;
                     }

                     $item_->check(-1, CREATE, $params);
                     if ($item_->add($params)) {
                        Event::log($post["users_id"], "users", 4, "setup",
                                    //TRANS: %s is the user login
                                    sprintf(__('%s adds a user to a group'), $_SESSION["glpiname"]));
                     }
                  }
               }
            
               $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $_GET['parenttype']);
               Html::redirect($backurl);
            }elseif (is_array($post["users_id"]) && !empty($post["users_id"])) {
               $exist_users = $this->find(['groups_id' => $post['groups_id']]);
               if (!empty($exist_users)) {
                  foreach ($exist_users as $value) {
                     if (in_array($value['users_id'], $post["users_id"])) {
                        foreach ($post["users_id"] as  $val) {
                           $params = [
                              "users_id" =>  $val,
                              "groups_id" =>  $post["groups_id"]
                           ];
   
                           if (!empty($this->find($params))) { //verify if record is not duplicated
                              continue;
                           }
   
                           $item_->check(-1, CREATE, $params);
                           if ($item_->add($params)) {
                              Event::log($post["users_id"], "users", 4, "setup",
                                          //TRANS: %s is the user login
                                          sprintf(__('%s adds a user to a group'), $_SESSION["glpiname"]));
                           }
                        }
                     }else {
                        $item_->delete(['id' =>$value['id']], 1);
                     }
                  }
               }else {
                  foreach ($post["users_id"] as  $val) {
                     $params = [
                        "users_id" =>  $val,
                        "groups_id" =>  $post["groups_id"]
                     ];

                     if (!empty($this->find($params))) { //verify if record is not duplicated
                        continue;
                     }

                     $item_->check(-1, CREATE, $params);
                     if ($item_->add($params)) {
                        Event::log($post["users_id"], "users", 4, "setup",
                                    //TRANS: %s is the user login
                                    sprintf(__('%s adds a user to a group'), $_SESSION["glpiname"]));
                     }
                  }
               }
               
               $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $_GET['parenttype']);
               Html::redirect($backurl);
            }
         }else {
            if (isset($post["groups_id"])) {
               $exist_users = $this->find(['groups_id' => $post['groups_id']]);
               foreach ($exist_users as $value) {
                  $item_->delete(['id' =>$value['id']], 1);
               }
               $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $_GET['parenttype']);
               Html::redirect($backurl);
            }elseif(isset($post["users_id"])) {
               $exist_groups = $this->find(['users_id' => $post['users_id']]);
               foreach ($exist_groups as $value) {
                  $item_->delete(['id' =>$value['id']], 1);
               }
               $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, $_GET['parenttype']);
               Html::redirect($backurl);
            }
         }
      }
   }

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

   static function showForUser(User $user) {
      global $CFG_GLPI;

      $ID = $user->fields['id'];
      if (!Group::canView()
         || !$user->can($ID, READ)) {
         return false;
      }

      $canedit = $user->can($ID, UPDATE);

      $rand    = mt_rand();

      // $iterator = self::getListForItem($user);
      $groups = [];
      $groups  = self::getUserGroups($ID);
      $used    = [];
      if ($canedit) {
         echo "<div class='firstbloc' id='searchcriteria'>";
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post'";
         echo " action='".Toolbox::getItemTypeFormURL('User')."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='6'>".__('Associate to a group')."</th></tr>";
         echo "<tr class='tab_bg_2'><td style ='white-space: nowrap;' class='center'>";
         echo "<input type='hidden' name='users_id' value='$ID'>";

         $params = [
            'used'      => $used,
            'width' => '200px',
            'condition' => [
               'is_usergroup' => 1,
            ] + getEntitiesRestrictCriteria(Group::getTable(), '', '', true)
         ];
         PluginServicesGroup::dropdown($params);
         echo "</td><td> &nbsp; &nbsp;".__('Manager')." &nbsp;</td><td>";
         PluginServicesDropdown::showYesNo('is_manager',0,  -1, ['width' => '80px']);

         echo "</td><td> &nbsp; &nbsp;".__('Delegatee')." &nbsp;</td><td>";
         PluginServicesDropdown::showYesNo('is_userdelegate',0,  -1, ['width' => '80px']);

         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='addgroup' value=\""._sx('button', 'Add')."\"
                  class='btn btn-sm  btn-info'>";

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='mt-5'>";
      if ($canedit && count($groups)) {
         $rand = mt_rand();
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         echo "<input type='hidden' name='users_id' value='".$user->fields['id']."'>";
         $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], count($used)),
                           'container'     => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';

      if ($canedit && count($groups)) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>".Group::getTypeName(1)."</th>";
      $header_end .= "<th>".__('Manager')."</th>";
      $header_end .= "<th>".__('Delegatee')."</th></tr>";
      echo $header_begin.$header_top.$header_end;

      $group = new PluginServicesGroup();
      if (!empty($groups)) {
         Session::initNavigateListItems('Group',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                User::getTypeName(1), $user->getName()));

         foreach ($groups as $data) {
            if (!$group->getFromDB($data["id"])) {
               continue;
            }
            Session::addToNavigateListItems('Group', $data["id"]);
            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
               echo "</td>";
            }
            echo "<td>".$group->getLink()."</td>";
            echo "<td class='center'>";
            if ($data['is_manager']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Manager')."\">";
            }
            echo "</td><td class='center'>";
            if ($data['is_userdelegate']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Delegatee')."\">";
            }
            echo "</td></tr>";
         }
         echo $header_begin.$header_bottom.$header_end;

      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='5' class='center'>".__('None')."</td></tr>";
      }
      echo "</table>";

      if ($canedit && count($groups)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Show form to add a user in current group
    *
    * @since 0.83
    *
    * @param $group                    Group object
    * @param $used_ids        Array    of already add users
    * @param $entityrestrict  Array    of entities
    * @param $crit            String   for criteria (for default dropdown)
   **/
   private static function showAddUserFormf(Group $group, $used_ids, $entityrestrict, $crit) {
      $rand = mt_rand();
      $res  = User::getSqlSearchResult(true, "all", $entityrestrict, 0, $used_ids);
      $nb = count($res);
      if ($nb) {
         echo "<form class='form-inline' name='groupuser_form$rand' id='groupuser_form$rand' method='post'
                action='".PluginServicesToolbox::getItemTypeFormURL(__CLASS__)."'>";
                  echo "<input type='hidden' name='groups_id' value='".$group->fields['id']."'>";
                  echo "<div class='form-group '>";
                        PluginServicesUser::dropdown(
                         [
                            'right'  => "all",
                            'entity' => $entityrestrict,
                            'width' => "410px",
                            'used'   => $used_ids
                         ]
                      );
                  echo "</div>";
                  echo "<div class='form-group ml-4'>";
                     echo "<label>".__('Manager')."</label>";
                     PluginServicesDropdown::showYesNo('is_manager', (($crit == 'is_manager') ? 1 : 0), -1, ['width' => "410px"]);
                  echo "</div>";
                  echo "<div class='form-group ml-4'>";
                     echo "<label>Délégataire</label>";
                     PluginServicesDropdown::showYesNo('is_userdelegate', (($crit == 'is_userdelegate') ? 1 : 0), -1, ['width' => "410px"]);
                  echo "</div>";
                  echo "<div class='form-group ml-4'>";
                  // echo "<input type='hidden' name'is_dynamic' value='0'>";
                  echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" style='width:97px;' class='btn m-btn--radius btn-md ml-2 btn-info'>";
                  echo "</div>";
               
               PluginServicesHtml::closeForm();
      }
   }

   private static function showAddUserForm(Group $group, $used_ids, $entityrestrict, $crit) {
      $rand = mt_rand();
      $res  = User::getSqlSearchResult(true, "all", $entityrestrict, 0, $used_ids);
      $nb = count($res);

      if ($nb) {
         echo "<form name='groupuser_form$rand' id='groupuser_form$rand' method='post'
                  action='".PluginServicesToolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<input type='hidden' name='groups_id' value='".$group->fields['id']."'>";

         echo "<div class='firstbloc' id='searchcriteria'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='6'>".__('Add a user')."</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";

         PluginServicesUser::dropdown(['right'  => "all",
                              'width' => '200px',
                              'entity' => $entityrestrict,
                              'used'   => $used_ids]);

         echo "</td><td> &nbsp;".__('Manager')." &nbsp;</td><td>";
         PluginServicesDropdown::showYesNo('is_manager', (($crit == 'is_manager') ? 1 : 0),  -1, ['width' => '80px']);

         echo "</td><td> &nbsp;".__('Delegatee')." &nbsp;</td><td>";
         PluginServicesDropdown::showYesNo('is_userdelegate', (($crit == 'is_userdelegate') ? 1 : 0),  -1, ['width' => '80px']);

         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name'is_dynamic' value='0'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit btn btn-sm btn-info'>";
         echo "</td></tr>";
         echo "</table></div>";
         Html::closeForm();
      }
   }
   /**
    * Retrieve list of member of a Group
    *
    * @since 0.83
    *
    * @param Group           $group    Group object
    * @param array           $members  Array filled on output of member (filtered)
    * @param array           $ids      Array of ids (not filtered)
    * @param string          $crit     Filter (is_manager, is_userdelegate) (default '')
    * @param boolean|integer $tree     True to include member of sub-group (default 0)
    *
    * @return String tab of entity for restriction
   **/
   static function getDataForGroup(Group $group, &$members, &$ids, $crit = '', $tree = 0) {
      global $DB;

      // Entity restriction for this group, according to user allowed entities
      if ($group->fields['is_recursive']) {
         $entityrestrict = getSonsOf('glpi_entities', $group->fields['entities_id']);

         // active entity could be a child of object entity
         if (($_SESSION['glpiactive_entity'] != $group->fields['entities_id'])
             && in_array($_SESSION['glpiactive_entity'], $entityrestrict)) {
            $entityrestrict = getSonsOf('glpi_entities', $_SESSION['glpiactive_entity']);
         }
      } else {
         $entityrestrict = $group->fields['entities_id'];
      }

      if ($tree) {
         $restrict = getSonsOf('glpi_groups', $group->getID());
      } else {
         $restrict = $group->getID();
      }

      // All group members
      $pu_table = Profile_User::getTable();
      $iterator = $DB->request([
         'SELECT' => [
            'glpi_users.id',
            'glpi_groups_users.id AS linkid',
            'glpi_groups_users.groups_id',
            'glpi_groups_users.is_dynamic AS is_dynamic',
            'glpi_groups_users.is_manager AS is_manager',
            'glpi_groups_users.is_userdelegate AS is_userdelegate'
         ],
         'DISTINCT'  => true,
         'FROM'      => self::getTable(),
         'LEFT JOIN' => [
            User::getTable() => [
               'ON' => [
                  self::getTable() => 'users_id',
                  User::getTable() => 'id'
               ]
            ],
            $pu_table => [
               'ON' => [
                  $pu_table        => 'users_id',
                  User::getTable() => 'id'
               ]
            ]
         ],
         'WHERE' => [
            self::getTable() . '.groups_id'  => $restrict,
            'OR' => [
               "$pu_table.entities_id" => null
            ] + getEntitiesRestrictCriteria($pu_table, '', $entityrestrict, 1)
         ],
         'ORDERBY' => [
            User::getTable() . '.realname',
            User::getTable() . '.firstname',
            User::getTable() . '.name'
         ]
      ]);

      while ($data = $iterator->next()) {
         // Add to display list, according to criterion
         if (empty($crit) || $data[$crit]) {
            $members[] = $data;
         }
         // Add to member list (member of sub-group are not member)
         if ($data['groups_id'] == $group->getID()) {
            $ids[]  = $data['id'];
         }
      }

      return $entityrestrict;
   }


   /**
    * Show users of a group
    *
    * @since 0.83
    *
    * @param $group  Group object: the group
   **/
   static function showForGroups(Group $group) {
      global $CFG_GLPI;

      $ID = $group->getID();
      if (!User::canView()
         || !$group->can($ID, READ)) {
         return false;
      }

      // Have right to manage members
      $canedit = self::canUpdate();
      $rand    = mt_rand();
      $user    = new PluginServicesUser();
      $crit    = Session::getSavedOption(__CLASS__, 'criterion', '');
      $tree    = Session::getSavedOption(__CLASS__, 'tree', 0);
      $used    = [];
      $ids     = [];

      // Retrieve member list
      // TODO: migrate to use CommonDBRelation::getListForItem()
      $entityrestrict = self::getDataForGroup($group, $used, $ids, $crit, $tree);

      if ($canedit) {
         self::showAddUserForm($group, $ids, $entityrestrict, $crit);
      }
      $number = count($used);
      $start  = (isset($_GET['start']) ? intval($_GET['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }

      // Display results
      if ($number) {
         echo "<div class='spaced'>";
         Session::initNavigateListItems('User',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                       sprintf(__('%1$s = %2$s'),
                                       PluginServicesGroup::getTypeName(1), $group->getName()));

         if ($canedit) {
            PluginServicesHtml::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed'    => min($number-$start,
                                                                  $_SESSION['glpilist_limit']),
                                       'container'        => 'mass'.__CLASS__.$rand];
            echo "<div class='bd-highlight'>";
               echo "<div class=''>";
                  PluginServicesHtml::showMassiveActionsForSpecifiqueItem($massiveactionparams);
               echo "</div>";
            echo"</div>";
         }

         // echo "<table class='tab_cadre_fixehov'>";
         echo "<table class='p-3 tab_cadrehov table table-striped m-table mt-3'>";

         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';

         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= PluginServicesHtml::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>".PluginServicesUser::getTypeName(1)."</th>";
         if ($tree) {
            $header_end .= "<th>".PluginServicesGroup::getTypeName(1)."</th>";
         }
         $header_end .= "<th>".__('Dynamic')."</th>";
         $header_end .= "<th>".__('Manager')."</th>";
         $header_end .= "<th>".__('Delegatee')."</th>";
         $header_end .= "<th>".__('Active')."</th></tr>";
         echo $header_begin.$header_top.$header_end;

         $tmpgrp = new PluginServicesGroup();

         for ($i=$start, $j=0; ($i < $number) && ($j < $_SESSION['glpilist_limit']); $i++, $j++) {
            $data = $used[$i];
            $user->getFromDB($data["id"]);
            Session::addToNavigateListItems('PluginServicesUser', $data["id"]);

            echo "\n<tr class='tab_bg_".($user->isDeleted() ? '1_2' : '1')."'>";
            if ($canedit) {
               echo "<td width='10'>";
               PluginServicesHtml::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
               echo "</td>";
            }
            echo "<td>".$user->getLink();
            if ($tree) {
               echo "</td><td>";
               if ($tmpgrp->getFromDB($data['groups_id'])) {
                  echo $tmpgrp->getLink(['comments' => true]);
               }
            }
            echo "</td><td class='center'>";
            if ($data['is_dynamic']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                     __('Dynamic')."\">";
            }
            echo "</td><td class='center'>";
            if ($data['is_manager']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                     __('Manager')."\">";
            }
            echo "</td><td class='center'>";
            if ($data['is_userdelegate']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                     __('Delegatee')."\">";
            }
            echo "</td><td class='center'>";
            if ($user->fields['is_active']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
               __('Active')."\">";
            }
            echo "</tr>";
         }
         // echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            PluginServicesHtml::showMassiveActions($massiveactionparams);
            PluginServicesHtml::closeForm();
         }
         // PluginServicesHtml::printAjaxPager(sprintf(__('%1$s (%2$s)'),
         // PluginServicesUser::getTypeName(Session::getPluralNumber()), __('D=Dynamic')),
         //                      $start, $number);

         echo "</div>";
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
   }


   static function showForGroup(Group $group) {
      global $CFG_GLPI;

      $ID = $group->getID();
      if (!User::canView()
         || !$group->can($ID, READ)) {
         return false;
      }

      // Have right to manage members
      $canedit = self::canUpdate();
      $rand    = mt_rand();
      $user    = new User();
      $crit    = Session::getSavedOption(__CLASS__, 'criterion', '');
      $tree    = Session::getSavedOption(__CLASS__, 'tree', 0);
      $used    = [];
      $ids     = [];

      // Retrieve member list
      // TODO: migrate to use CommonDBRelation::getListForItem()
      $entityrestrict = self::getDataForGroup($group, $used, $ids, $crit, $tree);

      if ($canedit) {
         self::showAddUserForm($group, $ids, $entityrestrict, $crit);
      }

      $number = count($used);
      $start  = (isset($_GET['start']) ? intval($_GET['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }

      // Display results
      if ($number) {
         echo "<div class='spaced mt-3'>";
         Session::initNavigateListItems('User',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                Group::getTypeName(1), $group->getName()));

         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed'    => min($number-$start,
                                                                   $_SESSION['glpilist_limit']),
                                         'container'        => 'mass'.__CLASS__.$rand];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";

         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';

         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>".User::getTypeName(1)."</th>";
         if ($tree) {
            $header_end .= "<th>".Group::getTypeName(1)."</th>";
         }
         $header_end .= "<th>".__('Dynamic')."</th>";
         $header_end .= "<th>".__('Manager')."</th>";
         $header_end .= "<th>".__('Delegatee')."</th>";
         $header_end .= "<th>".__('Active')."</th></tr>";
         echo $header_begin.$header_top.$header_end;

         $tmpgrp = new Group();

         for ($i=$start, $j=0; ($i < $number) && ($j < $_SESSION['glpilist_limit']); $i++, $j++) {
            $data = $used[$i];
            $user->getFromDB($data["id"]);
            Session::addToNavigateListItems('User', $data["id"]);

            echo "\n<tr class='tab_bg_".($user->isDeleted() ? '1_2' : '1')."'>";
            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
               echo "</td>";
            }
            echo "<td>".$user->getLink();
            if ($tree) {
               echo "</td><td>";
               if ($tmpgrp->getFromDB($data['groups_id'])) {
                  echo $tmpgrp->getLink(['comments' => true]);
               }
            }
            echo "</td><td class='center'>";
            if ($data['is_dynamic']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Dynamic')."\">";
            }
            echo "</td><td class='center'>";
            if ($data['is_manager']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Manager')."\">";
            }
            echo "</td><td class='center'>";
            if ($data['is_userdelegate']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
                      __('Delegatee')."\">";
            }
            echo "</td><td class='center'>";
            if ($user->fields['is_active']) {
               echo "<img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' width='14' height='14' alt=\"".
               __('Active')."\">";
            }
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
         // Html::printAjaxPager(sprintf(__('%1$s (%2$s)'),
         //                              User::getTypeName(Session::getPluralNumber()), __('D=Dynamic')),
         //                      $start, $number);

         echo "</div>";
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
   }
   /**
    * @since 0.85
    *
    * @see CommonDBRelation::getRelationMassiveActionsSpecificities()
   **/
   static function getRelationMassiveActionsSpecificities() {
      $specificities                           = parent::getRelationMassiveActionsSpecificities();

      $specificities['select_items_options_1'] = ['right'     => 'all'];
      $specificities['select_items_options_2'] = [
         'condition' => [
            'is_usergroup' => 1,
         ] + getEntitiesRestrictCriteria(Group::getTable(), '', '', true)
      ];

      // Define normalized action for add_item and remove_item
      $specificities['normalized']['add'][]    = 'add_supervisor';
      $specificities['normalized']['add'][]    = 'add_delegatee';

      $specificities['button_labels']['add_supervisor'] = $specificities['button_labels']['add'];
      $specificities['button_labels']['add_delegatee']  = $specificities['button_labels']['add'];

      $specificities['update_if_different'] = true;

      return $specificities;
   }


   static function getRelationInputForProcessingOfMassiveActions($action, CommonDBTM $item,
                                                               array $ids, array $input) {
      switch ($action) {
         case 'add_supervisor' :
            return ['is_manager' => 1];

         case 'add_delegatee' :
            return ['is_userdelegate' => 1];
      }

      return [];
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
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
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'name'               => Group::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
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
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'is_manager',
         'name'               => __('Manager'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'is_userdelegate',
         'name'               => __('Delegatee'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   /**
    * @param $user_ID
    * @param $only_dynamic (false by default
   **/
   static function deleteGroups($user_ID, $only_dynamic = false) {
      $crit['users_id'] = $user_ID;
      if ($only_dynamic) {
         $crit['is_dynamic'] = '1';
      }
      $obj = new self();
      $obj->deleteByCriteria($crit);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'User' :
               if (Group::canView()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = self::countForItem($item);
                  }
                  return self::createTabEntry(Group::getTypeName(Session::getPluralNumber()), $nb);
               }
               break;

            case 'Group' :
               if (User::canView()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = self::countForItem($item);
                  }
                  return self::createTabEntry(User::getTypeName(Session::getPluralNumber()), $nb);
               }
               break;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'User' :
            self::showForUser($item);
            break;

         case 'Group' :
            self::showForGroup($item);
            break;
      }
      return true;
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
      $params['SELECT'][] = self::getTable() . '.is_manager';
      $params['SELECT'][] = self::getTable() . '.is_userdelegate';
      return $params;
   }


   function post_addItem() {
      global $DB;

      // add new user to plannings
      $groups_id  = $this->fields['groups_id'];
      $planning_k = 'group_'.$groups_id.'_users';

      // find users with the current group in their plannings
      $user_inst = new User;
      $users = $user_inst->find([
         'plannings' => ['LIKE', "%$planning_k%"]
      ]);

      // add the new user to found plannings
      $query = $DB->buildUpdate(
         User::getTable(), [
            'plannings' => new QueryParam(),
         ], [
            'id'        => new QueryParam()
         ]
      );
      $stmt = $DB->prepare($query);
      $in_transaction = $DB->inTransaction();
      if (!$in_transaction) {
         $DB->beginTransaction();
      }
      foreach ($users as $user) {
         $users_id  = $user['id'];
         $plannings = importArrayFromDB($user['plannings']);
         $nb_users  = count($plannings['plannings'][$planning_k]['users']);

         // add the planning for the user
         $plannings['plannings'][$planning_k]['users']['user_'.$this->fields['users_id']]= [
            'color'   => Planning::getPaletteColor('bg', $nb_users),
            'display' => true,
            'type'    => 'user'
         ];

         // if current user logged, append also to its session
         if ($users_id == Session::getLoginUserID()) {
            $_SESSION['glpi_plannings'] = $plannings;
         }

         // save the planning completed to db
         $json_plannings = exportArrayToDB($plannings);
         $stmt->bind_param('si', $json_plannings, $users_id);
         $stmt->execute();
      }

      if (!$in_transaction) {
         $DB->commit();
      }
      $stmt->close();
   }


   function post_purgeItem() {
      global $DB;

      // remove user from plannings
      $groups_id  = $this->fields['groups_id'];
      $planning_k = 'group_'.$groups_id.'_users';

      // find users with the current group in their plannings
      $user_inst = new User;
      $users = $user_inst->find([
         'plannings' => ['LIKE', "%$planning_k%"]
      ]);

      // remove the deleted user to found plannings
      $query = $DB->buildUpdate(
         User::getTable(), [
            'plannings' => new QueryParam(),
         ], [
            'id'        => new QueryParam()
         ]
      );
      $stmt = $DB->prepare($query);
      $in_transaction = $DB->inTransaction();
      if (!$in_transaction) {
         $DB->beginTransaction();
      }
      foreach ($users as $user) {
         $users_id  = $user['id'];
         $plannings = importArrayFromDB($user['plannings']);

         // delete planning for the user
         unset($plannings['plannings'][$planning_k]['users']['user_'.$this->fields['users_id']]);

         // if current user logged, append also to its session
         if ($users_id == Session::getLoginUserID()) {
            $_SESSION['glpi_plannings'] = $plannings;
         }

         // save the planning completed to db
         $json_plannings = exportArrayToDB($plannings);
         $stmt->bind_param('si', $json_plannings, $users_id);
         $stmt->execute();
      }

      if (!$in_transaction) {
         $DB->commit();
      }
      $stmt->close();
   }
}
