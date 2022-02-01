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

/**
 *  Class used to manage Auth LDAP config
 */
class PluginServicesAuthLDAP extends AuthLDAP {
  static $rightname = 'plugin_services_auth_ldap';

  static function getTable($classname = null) {
    return "glpi_authldaps";
  }
  static function getType(){
    return "AuthLDAP";
  }

  public function postForm($post) {
    Session::checkRight("config", UPDATE);

    $config_ldap = new AuthLDAP();

    if (!isset($_GET['id'])) {
      $_GET['id'] = "";
    }
    //LDAP Server add/update/delete
    if (isset($_POST["update"])) {
      if (array_key_exists('rootdn_passwd', $_POST)) {
          // Password must not be altered, it will be encrypted and never displayed, so sanitize is not necessary.
          $_POST['rootdn_passwd'] = $_UPOST['rootdn_passwd'];
      }
      $config_ldap->update($_POST);
      Html::back();

    } else if (isset($_POST["add"])) {
      if (array_key_exists('rootdn_passwd', $_POST)) {
          // Password must not be altered, it will be encrypt and never displayed, so sanitize is not necessary.
          $_POST['rootdn_passwd'] = $_UPOST['rootdn_passwd'];
      }
      //If no name has been given to this configuration, then go back to the page without adding
      if ($_POST["name"] != "") {
          if ($newID = $config_ldap->add($_POST)) {
            if (AuthLDAP::testLDAPConnection($newID)) {
                Session::addMessageAfterRedirect(__('Test successful'));
            } else {
                Session::addMessageAfterRedirect(__('Test failed'), false, ERROR);
                GLPINetwork::addErrorMessageAfterRedirect();
            }
            Html::redirect($CFG_GLPI["root_doc"] . "/front/authldap.php?next=extauth_ldap&id=".$newID);
          }
      }
      Html::back();

    } else if (isset($_POST["purge"])) {
      $config_ldap->delete($_POST, 1);
      $_SESSION['glpi_authconfig'] = 1;
      $config_ldap->redirectToList();

    } else if (isset($_POST["test_ldap"])) {
      $config_ldap->getFromDB($_POST["id"]);

      if (AuthLDAP::testLDAPConnection($_POST["id"])) {
                                          //TRANS: %s is the description of the test
          $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(__('Test successful: %s'),
                                                  //TRANS: %s is the name of the LDAP main server
                                                  sprintf(__('Main server %s'), $config_ldap->fields["name"]));
      } else {
                                          //TRANS: %s is the description of the test
          $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(__('Test failed: %s'),
                                                  //TRANS: %s is the name of the LDAP main server
                                                  sprintf(__('Main server %s'), $config_ldap->fields["name"]));
          GLPINetwork::addErrorMessageAfterRedirect();
      }
      Html::back();

    } else if (isset($_POST["test_ldap_replicate"])) {
      $replicate = new AuthLdapReplicate();
      $replicate->getFromDB($_POST["ldap_replicate_id"]);

      if (AuthLDAP::testLDAPConnection($_POST["id"], $_POST["ldap_replicate_id"])) {
                                          //TRANS: %s is the description of the test
          $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(__('Test successful: %s'),
                                                  //TRANS: %s is the name of the LDAP replica server
                                                  sprintf(__('Replicate %s'), $replicate->fields["name"]));
      } else {
                                            //TRANS: %s is the description of the test
          $_SESSION["LDAP_TEST_MESSAGE"] = sprintf(__('Test failed: %s'),
                                                  //TRANS: %s is the name of the LDAP replica server
                                                  sprintf(__('Replicate %s'), $replicate->fields["name"]));
          GLPINetwork::addErrorMessageAfterRedirect();
      }
      Html::back();

    } else if (isset($_POST["add_replicate"])) {
      $replicate = new AuthLdapReplicate();
      unset($_POST["next"]);
      unset($_POST["id"]);
      $replicate->add($_POST);
      Html::back();
    }
    Html::back();
  }

  function showForm($ID, $options = []) {
    if (!Config::canUpdate()) {
      return false;
    }
    if (empty($ID)) {
        $this->getEmpty();
        if (isset($options['preconfig'])) {
          $this->preconfig($options['preconfig']);
        }
    } else {
        $this->getFromDB($ID);
    }
    echo'
      <div class="m-content">
        <div class="row">
          <div class="col-lg-12">
            <div class="m-portlet">';
              if(Toolbox::canUseLdap()) {
                echo'
                  <div class="m-portlet__head">
                    <div class="m-portlet__head-caption">
                      <div class="m-portlet__head-title">
                        <span class="m-portlet__head-icon">
                            <i class="la la-gear"></i>
                        </span>
                        <h3 class="m-portlet__head-text">';
                        
                          echo'
                        </h3>
                      </div>
                    </div>
                  </div>
                  <div class="m-portlet__body">';
                    $this->showFormHeader($options);
              
                    echo'<div class="form-group m-form__group row">';
                          $defaultrand = mt_rand();
                          echo'<label for="dropdown_is_default'.$defaultrand.'" class="col-lg-2 col-form-label">'; 
                              echo __('Default server') ;
                          echo'</label>';
                          echo'<div class="col-lg-3">';
                              PluginServicesDropdown::showYesNo('is_default', $this->fields['is_default'], -1, ['rand' => $defaultrand]);
                          echo'</div>';

                          $activerand = mt_rand();
                          echo'<label for="dropdown_is_default'.$activerand.'" class="col-lg-2 col-form-label">'; 
                            echo __('Active') ;
                          echo'</label>';
                          echo'<div class="col-lg-3">';
                            PluginServicesDropdown::showYesNo('is_active', $this->fields['is_active'], -1, ['rand' => $activerand]);
                          echo'</div>';
                    echo'</div>';

                    echo'<div class="form-group m-form__group row">';
                          $defaultrand = mt_rand();
                          echo'<label for="host" class="col-lg-2 col-form-label">'; 
                              echo __('Server') ;
                          echo'</label>';
                          echo'<div class="col-lg-3">';
                              echo"<input type='text' class='form-control' id='host' name='host' value='" . $this->fields["host"] . "'>";
                          echo'</div>';

                          $activerand = mt_rand();
                          echo'<label for="port" class="col-lg-2 col-form-label">'; 
                            echo __('Port') ;
                          echo'</label>';
                          echo'<div class="col-lg-3">';
                            echo"<input class='form-control' id='port' type='text' id='port' name='port' value='".$this->fields["port"]."'>";
                          echo'</div>';
                    echo'</div>';

                    echo'<div class="form-group m-form__group row">';
                          echo'<label for="rootdn" class="col-lg-2 col-form-label">'; 
                              echo __('DN du compte') ;
                          echo'</label>';
                          echo'<div class="col-lg-3">';
                          echo "<input type='text' class='form-control' name='rootdn' id='rootdn'  value=\"".$this->fields["rootdn"]."\">";
                          echo'</div>';

                          echo'<label for="rootdn_passwd" class="col-lg-2 col-form-label">'; 
                            echo __('Mot de passe du compte ') ;
                          echo'</label>';
                          echo'<div class="col-lg-3">';
                            echo "<input class='form-control' type='password' id='rootdn_passwd' name='rootdn_passwd' value='' autocomplete='new-password'>";
                          echo'</div>';
                    echo'</div>';

                    echo'<div class="form-group m-form__group row">';
                          echo'<label for="login_field" class="col-lg-2 col-form-label">'; 
                              echo  __('Login field') ;
                          echo'</label>';
                          echo'<div class="col-lg-3">';
                            echo "<input class='form-control' type='text' id='login_field' name='login_field' value='".$this->fields["login_field"]."'>";
                          echo'</div>';

                          $info_message = __s('Synchronization field cannot be changed once in use.');
                          echo'<label for="sync_field" class="col-lg-2 col-form-label">'; 
                            echo __('Synchronization field') ;
                            echo"<i class='pointer fa fa-info' title='$info_message'></i>";
                          echo'</label>';
                          echo'<div class="col-lg-3">';
                            echo "
                              <input  class='form-control' type='text' id='sync_field' name='sync_field' value='{$this->fields["sync_field"]}' title='$info_message'";
                                if ($this->isSyncFieldEnabled() && $this->isSyncFieldUsed()) {
                                  echo " disabled='disabled'";
                                }
                              echo ">
                            ";
                          echo'</div>';
                    echo'</div>';
                    echo'<div class="form-group m-form__group row">';
                          $defaultrand = mt_rand();
                          echo'<label for="condition" class="col-lg-2 col-form-label">'; 
                              echo __('Connection filter') ;
                          echo'</label>';
                          echo'<div class="col-lg-8">';
                            echo"<input class='form-control'  type='text' id='condition' name='condition' value='".$this->fields["condition"]."'>";
                          echo'</div>';
                    echo'</div>';
                    echo'<div class="form-group m-form__group row">';
                      echo'<label for="basedn" class="col-lg-2 col-form-label">'; 
                        echo __('BaseDN') ;
                      echo'</label>';
                      echo'<div class="col-lg-8">';
                        echo "<input type='text' class='form-control' id='basedn' name='basedn'  value=\"".$this->fields["basedn"]."\">";
                      echo'</div>';
                    echo'</div>';
                    echo'<div class="form-group m-form__group row">';
                        echo'<label for="name" class="col-lg-2 col-form-label">'; 
                            echo  __('Name') ;
                        echo'</label>';
                        echo'<div class="col-lg-8">';
                            echo"<input class='form-control' type='text' id='name' name='name' value='". $this->fields["name"] ."'>";
                        echo'</div>';
                    echo'</div>';

                    echo'<div class="form-group m-form__group row">';
                      echo'<label for="comment" class="col-lg-2 col-form-label">'; 
                        echo  __('Comments') ;
                      echo'</label>';
                      echo'<div class="col-lg-8">';
                      echo "<textarea class='form-control m-input'  rows='4' name='comment' id='comment'>".$this->fields["comment"]."</textarea>";
                      echo'</div>';
                    echo'</div>';
                      //Fill fields when using preconfiguration models
                      if (!$ID) {
                        $hidden_fields = ['comment_field', 'email1_field', 'email2_field',
                                              'email3_field', 'email4_field', 'entity_condition',
                                              'entity_field', 'firstname_field', 'group_condition',
                                              'group_field', 'group_member_field', 'group_search_type',
                                              'mobile_field', 'phone_field', 'phone2_field',
                                              'realname_field', 'registration_number_field', 'title_field',
                                              'use_dn', 'use_tls', 'responsible_field'];

                        foreach ($hidden_fields as $hidden_field) {
                          echo "<input type='hidden' name='$hidden_field' value='".
                                  $this->fields[$hidden_field]."'>";
                        }
                      }
                      echo"<div class='col-lg-12 mt-1 d-flex justify-content-center'>";
                        $this->showFormButtons($options);
                      echo"</div>";
                    echo'
                  </div> ';
                  echo Html::css('assets/css/customer.glpi.form.css');
                  echo'
                    <div class="m-portlet__foot m-portlet__no-border m-portlet__foot">
                      <div class="m-form__actions m-form__actions--solid m-form__actions--right">';
                        $this->showTabsContent([]);
                        echo'
                      </div>
                    </div>
                  ';
              }
              echo'
            </div>';
          echo'</div>
        </div>
      </div>
    ';
  }

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
      echo "<form name='form' method='post' id='mainform' class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed' name='form_ticket' action='".$params['target']."' ".
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
    // echo "<table class='tab_cadre_fixe' id='mainformtable'>";

    if ($params['formtitle'] !== '' && $params['formtitle'] !== false) {
      //  echo "<tr class='headerRow'><th colspan='".$params['colspan']."'>";

      if (!empty($params['withtemplate']) && ($params['withtemplate'] == 2)
          && !$this->isNewID($ID)) {

          echo "<input type='hidden' name='template_name' value='".$this->fields["template_name"]."'>";

          //TRANS: %s is the template name
          // printf(__('Created from the template %s'), $this->fields["template_name"]);

      } else if (!empty($params['withtemplate']) && ($params['withtemplate'] == 1)) {
          echo "<input type='hidden' name='is_template' value='1'>\n";
          echo "<label for='textfield_template_name$rand'>" . __('Template name') . "</label>";
          Html::autocompletionTextField(
            $this,
            'template_name',
            [
              'size'      => 25,
              'required'  => true,
              'rand'      => $rand
            ]
          );
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
          $entityname = Dropdown::getDropdownName("glpi_entities", $this->fields["entities_id"]);
       }

      //  echo "</th><th colspan='".$params['colspan']."'>";
       if (get_class($this) != 'Entity') {
          if ($this->maybeRecursive()) {
             if (Session::isMultiEntitiesMode()) {
                // echo "<table class='tab_format'><tr class='headerRow responsive_hidden'><th>".$entityname."</th>";
                // echo "<th class='right'><label for='dropdown_is_recursive$rand'>".__('Child entities')."</label></th><th>";
                if ($params['canedit']) {
                  if ($this instanceof CommonDBChild) {
                      echo Dropdown::getYesNo($this->isRecursive());
                      if (isset($this->fields["is_recursive"])) {
                        echo "<input type='hidden' name='is_recursive' value='".$this->fields["is_recursive"]."'>";
                      }
                      $comment = __("Can't change this attribute. It's inherited from its parent.");
                      // CommonDBChild : entity data is get or copy from parent

                  } else if (!$this->can($ID, 'recursive')) {
                      echo Dropdown::getYesNo($this->fields["is_recursive"]);
                      $comment = __('You are not allowed to change the visibility flag for child entities.');

                   } else if (!$this->canUnrecurs()) {
                      echo Dropdown::getYesNo($this->fields["is_recursive"]);
                      $comment = __('Flag change forbidden. Linked items found.');

                   } else {
                      Dropdown::showYesNo("is_recursive", $this->fields["is_recursive"], -1, ['rand' => $rand]);
                      $comment = __('Change visibility in child entities');
                   }
                   echo " ";
                   Html::showToolTip($comment);
                } else {
                   echo Dropdown::getYesNo($this->fields["is_recursive"]);
                }
                // echo "</th></tr></table>";
             } else {
                echo $entityname;
                echo "<input type='hidden' name='is_recursive' value='0'>";
             }
          } else {
             echo $entityname;
          }
       }
      //  echo "</th></tr></table>\n";
    }

    Plugin::doHook("pre_item_form", ['item' => $this, 'options' => &$params]);

    // If in modal : do not display link on message after redirect
    if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
      echo "<input type='hidden' name='_no_message_link' value='1'>";
    }

  }

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

    // if ($params['formfooter'] === null) {
    //    $this->showDates($params);
    // }

    if (!$params['canedit']
       || !$this->canEdit($ID)) {
       // echo "</table></div>";
       // Form Header always open form
       PluginServicesHtml::closeForm();
       return false;
    }

    if ($params['withtemplate']
       ||$this->isNewID($ID)) {
       if (($ID <= 0) || ($params['withtemplate'] == 2)) {
          echo PluginServicesHtml::submit(
             "<i class='fas fa-plus'></i>&nbsp;"._x('button', 'Add'),
             ['name' => 'add']
          );
       } else {
          //TRANS : means update / actualize
          echo PluginServicesPluginServicesHtml::submit(
             "<i class='fas fa-save'></i>&nbsp;"._x('button', 'Save'),
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
             echo PluginServicesHtml::submit(
                "<i class='fas fa-save'></i>&nbsp;"._x('button', 'Save'),
                ['name' => 'update']
             );
          }

          if ($params['candel']) {
             if ($params['canedit'] && $this->can($ID, UPDATE)) {
             }
             if ($this->isDeleted()) {
                if ($this->can($ID, DELETE)) {
                   echo PluginServicesHtml::submit(
                      "<i class='fas fa-trash-restore'></i>&nbsp;"._x('button', 'Restore'),
                      ['name' => 'restore']
                   );
                }

                if ($this->can($ID, PURGE)) {
                   echo "<span class='very_small_space'>";
                   if (in_array($this->getType(), Item_Devices::getConcernedItems())) {
                      PluginServicesHtml::showToolTip(__('Check to keep the devices while deleting this item'));
                      echo "&nbsp;";
                      echo "<input type='checkbox' name='keep_devices' value='1'";
                      if (!empty($_SESSION['glpikeep_devices_when_purging_item'])) {
                         echo " checked";
                      }
                      echo ">&nbsp;";
                   }
                   echo PluginServicesHtml::submit(
                      "<i class='fas fa-trash-alt'></i>&nbsp;"._x('button', 'Delete permanently'),
                      ['name' => 'purge']
                   );
                   echo "</span>";
                }

             } else {
                // If maybe dynamic : do not take into account  is_deleted  field
                if (!$this->maybeDeleted()
                   || $this->useDeletedToLockIfDynamic()) {
                   if ($this->can($ID, PURGE)) {
                      echo PluginServicesHtml::submit(
                         "<i class='fas fa-trash-alt'></i>&nbsp;"._x('button', 'Delete permanently'),
                         [
                            'name'    => 'purge',
                            'confirm' => __('Confirm the final deletion?')
                         ]
                      );
                   }
                } else if (!$this->isDeleted()
                         && $this->can($ID, DELETE)) {
                   echo PluginServicesHtml::submit(
                      "<i class='fas fa-trash-alt'></i>&nbsp;"._x('button', 'Put in trashbin'),
                      ['name' => 'delete']
                   );
                }
             }

          }
          if ($this->isField('date_mod')) {
             echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
          }
       }

       if (!$this->isNewID($ID)) {
          echo "<input type='hidden' name='id' value='$ID'>";
       }


    if ($params['canedit']
       && count($params['addbuttons'])) {
       foreach ($params['addbuttons'] as $key => $val) {
          echo "<button type='submit' class='vsubmit' name='$key' value='1'>
                $val
             </button>&nbsp;";
       }
    }
    PluginServicesHtml::closeForm();
  }

  function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
    
    if ($item->getType() == "AuthLDAP") {
      if (!$withtemplate
      && $item->can($item->getField('id'), READ)) {
        return [
          self::createTabEntry(__("Teste")),
          self::createTabEntry(__("Utilisateurs")),
        ];
      }
    }
    return '';
  }

  static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
    if($item->getType() == "AuthLDAP"){
      switch ($tabnum) {
        case 0:
          $item->showFormTestLDAP();
          break;
        case 1:
          $item->showFormUserConfig();
          break;
      }
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
      // Tabs known by the object
      // if ($this->isNewItem()) {
      //    $this->addDefaultFormTab($onglets);
      // } else {
      //    $onglets = $this->defineTabs($options);
      // }

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
  function showFormUserConfig() {

    $ID = $this->getField('id');

    echo "<div class='center'>";
    echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
    echo "<input type='hidden' name='id' value='$ID'>";
    echo "<table class='tab_cadre_fixe'>";

    echo "<tr class='tab_bg_1'>";
    echo "<th class='center' colspan='4'>" . __('Binding to the LDAP directory') . "</th></tr>";

    echo "<tr class='tab_bg_2'><td>" . __('Surname') . "</td>";
    echo "<td><input type='text' class='form-control' name='realname_field' value='".
              $this->fields["realname_field"]."'></td>";
    echo "<td>" . __('First name') . "</td>";
    echo "<td><input class='form-control' type='text' name='firstname_field' value='".
               $this->fields["firstname_field"]."'></td></tr>";

    echo "<tr class='tab_bg_2'><td>" . __('Comments') . "</td>";
    echo "<td><input class='form-control' type='text' name='comment_field' value='".$this->fields["comment_field"]."'>";
    echo "</td>";
    echo "<td>" . __('Administrative number') . "</td>";
    echo "<td>";
    echo "<input type='text' name='registration_number_field' value='".
           $this->fields["registration_number_field"]."'>";
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'>";
    echo "<td>" . _n('Email', 'Emails', 1) . "</td>";
    echo "<td><input class='form-control' type='text' name='email1_field' value='".$this->fields["email1_field"]."'>";
    echo "</td>";
    echo "<td>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '2') . "</td>";
    echo "<td><input class='form-control' type='text' name='email2_field' value='".$this->fields["email2_field"]."'>";
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'>";
    echo "<td>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '3') . "</td>";
    echo "<td><input class='form-control' type='text' name='email3_field' value='".$this->fields["email3_field"]."'>";
    echo "</td>";
    echo "<td>" . sprintf(__('%1$s %2$s'), _n('Email', 'Emails', 1), '4') . "</td>";
    echo "<td><input class='form-control' type='text' name='email4_field' value='".$this->fields["email4_field"]."'>";
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'><td>" . _x('ldap', 'Phone') . "</td>";
    echo "<td><input class='form-control' type='text' name='phone_field'value='".$this->fields["phone_field"]."'>";
    echo "</td>";
    echo "<td>" .  __('Phone 2') . "</td>";
    echo "<td><input class='form-control' type='text' name='phone2_field'value='".$this->fields["phone2_field"]."'>";
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'><td>" . __('Mobile phone') . "</td>";
    echo "<td><input class='form-control' type='text' name='mobile_field'value='".$this->fields["mobile_field"]."'>";
    echo "</td>";
    echo "<td>" . _x('person', 'Title') . "</td>";
    echo "<td><input class='form-control' type='text' name='title_field' value='".$this->fields["title_field"]."'>";
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'><td>" . __('Category') . "</td>";
    echo "<td><input class='form-control' type='text' name='category_field' value='".
               $this->fields["category_field"]."'></td>";
    echo "<td>" . __('Language') . "</td>";
    echo "<td><input class='form-control' type='text' name='language_field' value='".
               $this->fields["language_field"]."'></td></tr>";

    echo "<tr class='tab_bg_2'><td>" . __('Picture') . "</td>";
    echo "<td><input class='form-control' type='text' name='picture_field' value='".
               $this->fields["picture_field"]."'></td>";
    echo "<td>" . Location::getTypeName(1) . "</td>";
    echo "<td><input class='form-control' type='text' name='location_field' value='".$this->fields["location_field"]."'>";
    echo "</td></tr>";

    echo "<tr class='tab_bg_2'><td>" . __('Responsible') . "</td>";
    echo "<td><input class='form-control' type='text' name='responsible_field' value='".
         $this->fields["responsible_field"]."'></td>";
    echo "<td colspan='2'></td></tr>";

    echo "<tr><td colspan=4 class='center green'>".__('You can use a field name or an expression using various %{fieldname}').
         " <br />".__('Example for location: %{city} > %{roomnumber}')."</td></tr>";

    echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
    echo "<input type='submit' name='update' class='submit' value=\"".__s('Save')."\">";
    echo "</td></tr>";
    echo "</table>";
    Html::closeForm();
    echo "</div>";
  }
  static function getFormURL($full = false) {
    return PluginServicesToolbox::getItemTypeFormURL(get_called_class(), $full);;
  }
  
  static function getSearchURL($full = true) {
      return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
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
  
  public function showList($itemtype, $params){
    global $CFG_GLPI;
    echo'
      <div class="m-content">
        <div class="row">
            <div class="col-xl-12 ">
              <div class="m-portlet m-portlet--tab">
                    <div class="m-portlet__head">
                        <div class="m-portlet__head-caption">
                          <div class="m-portlet__head-title">
                              <h3 class="m-portlet__head-text">
                                Règles métiers
                              </h3>
                          </div>
                        </div>

                        <div class="m-portlet__head-tools">
                          <ul class="m-portlet__nav">
                              <li class="m-portlet__nav-item">
                                    <a href="'.$CFG_GLPI['root_doc'].'/ruleticket/form" class="btn btn-secondary btn-lg m-btn m-btn--icon m-btn--icon-only bg-light">
                                      <i class="flaticon-add-circular-button"></i>
                                    </a>
                              </li>
                          </ul>
                        </div>
                    </div>
                    <div class="m-portlet__body">';
                        PluginServicesSearch::showFago("PluginServicesAuthLDAP", $params);
                    echo'  
                    </div>
              </div>
            </div>
        </div>
      </div>
    ';
  }


  static function showUserImportForm(AuthLDAP $authldap) {
    //Get data related to entity (directory and ldap filter)
    $authldap->getFromDB($_SESSION['ldap_import']['authldaps_id']);
    echo "<form method='post' action='' id='mainform' class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed' name='form_ticket' >";
    switch ($_SESSION['ldap_import']['interface']) {
      default :
        if (self::getNumberOfServers() > 1) {
          $rand = mt_rand();
          echo'<div class="form-group m-form__group row">';
            echo"<label class='col-lg-2 col-form-label' for='dropdown_authldaps_id$rand'>".__('LDAP directory choice')."</label>";
            echo'<div class="col-lg-3">';
                self::dropdown(['name'                 => 'authldaps_id',
                'value'                => $_SESSION['ldap_import']['authldaps_id'],
                'condition'            => ['is_active' => 1],
                'display_emptychoice'  => false,
                'rand'                 => $rand]);

                echo "&nbsp;<input class='submit' type='submit' name='change_directory'
                value=\""._sx('button', 'Change')."\">";
            echo'</div>';
          echo'</div>';
        }
        //If multi-entity mode and more than one entity visible
        //else no need to select entity
        if (Session::isMultiEntitiesMode()
            && (count($_SESSION['glpiactiveentities']) > 1)) {
            // echo'<div class="form-group m-form__group row">';
            //   echo"<label class='col-lg-2 col-form-label' >".__('Select the desired entity')."</label>"; 
            //   echo'<div class="col-lg-8">';
            //     PluginServicesEntity::dropdown(['value'       => $_SESSION['ldap_import']['entities_id'],
            //     'entity'      => $_SESSION['glpiactiveentities'],
            //     'on_change'    => 'this.form.submit()']);
            //   echo'</div>';
            // echo'</div>';
        } else {
            //Only one entity is active, store it
            echo "<input type='hidden' name='entities_id' value='".
            $_SESSION['glpiactive_entity']."'>";
        }
        if ((isset($_SESSION['ldap_import']['begin_date'])
              && !empty($_SESSION['ldap_import']['begin_date']))
            || (isset($_SESSION['ldap_import']['end_date'])
                && !empty($_SESSION['ldap_import']['end_date']))) {
            $enabled = 1;
        } else {
            $enabled = 0;
        }
        // PluginServicesDropdown::showAdvanceDateRestrictionSwitch($enabled);
        // echo "<table class='tab_cadre_fixe'>";
        if (($_SESSION['ldap_import']['authldaps_id'] !=  NOT_AVAILABLE)
            && ($_SESSION['ldap_import']['authldaps_id'] > 0)) {

          $field_counter = 0;
          $fields        = ['login_field'     => __('Login'),
                            'sync_field'      => __('Synchronization field'),
                            'email1_field'    => _n('Email', 'Emails', 1),
                            'phone_field'     => _x('ldap', 'Phone'),
                            // 'email2_field'    => sprintf(__('%1$s %2$s'),
                            //                             _n('Email', 'Emails', 1), '2'),
                            // 'email3_field'    => sprintf(__('%1$s %2$s'),
                            //                             _n('Email', 'Emails', 1), '3'),
                            // 'email4_field'    => sprintf(__('%1$s %2$s'),
                            //                             _n('Email', 'Emails', 1), '4'),
                            'realname_field'  => __('Surname'),
                            'firstname_field' => __('First name'),
                            // 'phone_field'     => _x('ldap', 'Phone'),
                            // 'phone2_field'    => __('Phone 2'),
                            // 'mobile_field'    => __('Mobile phone'),
                            // 'title_field'     => _x('person', 'Title'),
                            'category_field'  => __('Category'),
                            'picture_field'   => __('Picture')];
          $available_fields = [];
          foreach ($fields as $field => $label) {
            if (isset($authldap->fields[$field]) && ($authldap->fields[$field] != '')) {
                $available_fields[$field] = $label;
            }
          }
          // echo "<div class='form-group m-form__group row'>" . __('Search criteria for users') . "</div>";
          foreach ($available_fields as $field => $label) {
              if ($field_counter == 0) {
                echo "<div class='form-group m-form__group row'>";
              }
              echo"<label class='col-lg-2 col-form-label' for='criterias$field'>$label</label>";
              echo "<div class='col-lg-3'>";
                    $field_counter++;
                    $field_value = '';
                    if (isset($_SESSION['ldap_import']['criterias'][$field])) {
                      $field_value = Html::entities_deep(Toolbox::unclean_cross_side_scripting_deep(Toolbox::stripslashes_deep($_SESSION['ldap_import']['criterias'][$field])));
                    }
                    echo "<input type='text' class='form-control' id='criterias$field' name='criterias[$field]' value='$field_value'>";
              echo "</div>";
              
              if ($field_counter == 2) {
                echo "</div>";
                $field_counter = 0;
              }
          }
          if ($field_counter > 0) {
            while ($field_counter < 2) {
              $field_counter++;
            }
            $field_counter = 0;
            
            echo "</div>";
          }
        }
        break;
    }

    if (($_SESSION['ldap_import']['authldaps_id'] !=  NOT_AVAILABLE)
        && ($_SESSION['ldap_import']['authldaps_id'] > 0)) {
      if ($_SESSION['ldap_import']['authldaps_id']) {
        echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
        echo "<input class='submit' type='submit' name='search' value=\"".
                _sx('button', 'Search')."\">";
        echo "</td></tr>";
      } else {
        echo "<tr class='tab_bg_2'><".
              "td colspan='4' class='center'>".__('No directory selected')."</td></tr>";
      }

    } else {
      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>".
              __('No directory associated to entity: impossible search')."</td></tr>";
    }
    echo "</table>";
    Html::closeForm();
  }

  static function searchUser(AuthLDAP $authldap) {
    if (self::connectToServer($authldap->getField('host'), $authldap->getField('port'),
                              $authldap->getField('rootdn'),
                              Toolbox::sodiumDecrypt($authldap->getField('rootdn_passwd')),
                              $authldap->getField('use_tls'),
                              $authldap->getField('deref_option'))) {
      return self::showLdapUsers();

    } else {
      echo "<div class='center b firstbloc'>".__('Unable to connect to the LDAP directory');
    }
  }

  static function showLdapUsers() {
    $values = [
      'order' => 'DESC',
      'start' => 0,
    ];

    foreach ($_SESSION['ldap_import'] as $option => $value) {
      $values[$option] = $value;
    }

    $rand          = mt_rand();
    $results       = [];
    $limitexceeded = false;
    $ldap_users    = self::getUsers($values, $results, $limitexceeded);
    return $ldap_users;
    
  }

  static function cronInfo($name) {
    switch ($name) {
      case 'importuserldap':
          return [
            'description' => __('Impoter les utilisateurs de la base'),
            'parameter'   => __('Maximum emails to send at once')
        ];
    }
    return [];
  }

  static function cronImportUserLdap($task) {
    self::importuserldap([
      'action' => 2,
      'ldapservers_id' => 1
    ]);
  }

  static function importuserldap(array $options) {
    
    global $CFG_GLPI;
 
    $results = [AuthLDAP::USER_IMPORTED     => 0,
                     AuthLDAP::USER_SYNCHRONIZED => 0,
                     AuthLDAP::USER_DELETED_LDAP => 0];
    //The ldap server id is passed in the script url (parameter server_id)
    $limitexceeded = false;
    $actions_to_do = [];
 
    switch ($options['action']) {
       case AuthLDAP::ACTION_IMPORT :
          $actions_to_do = [AuthLDAP::ACTION_IMPORT];
         break;
 
       case AuthLDAP::ACTION_SYNCHRONIZE :
          $actions_to_do = [AuthLDAP::ACTION_SYNCHRONIZE];
         break;
 
       case AuthLDAP::ACTION_ALL :
          $actions_to_do = [AuthLDAP::ACTION_IMPORT, AuthLDAP::ACTION_ALL];
         break;
    }
 
    foreach ($actions_to_do as $action_to_do) {
       $options['mode']         = $action_to_do;
       $options['authldaps_id'] = $options['ldapservers_id'];
       $authldap = new \AuthLDAP();
       $authldap->getFromDB($options['authldaps_id']);
       $users                   = AuthLDAP::getAllUsers($options, $results, $limitexceeded);
      //  print_r($authldap);
      //  return;
       $contact_ok              = true;
 
       if (is_array($users)) {
          foreach ($users as $user) {
             //check if user exists
             $user_sync_field = null;
             if ($authldap->isSyncFieldEnabled()) {
                $sync_field = $authldap->fields['sync_field'];
                if (isset($user[$sync_field])) {
                   $user_sync_field = $authldap::getFieldValue($user, $sync_field);
                }
             }
             $dbuser = $authldap->getLdapExistingUser(
                $user['user'],
                $options['authldaps_id'],
                $user_sync_field
             );
 
             if ($dbuser && $action_to_do == AuthLDAP::ACTION_IMPORT) {
                continue;
             }
 
             $user_field = 'name';
             $id_field = $authldap->fields['login_field'];
             $value = $user['user'];
             if ($authldap->isSyncFieldEnabled() && (!$dbuser || !empty($dbuser->fields['sync_field']))) {
                $value = $user_sync_field;
                $user_field = 'sync_field';
                $id_field   = $authldap->fields['sync_field'];
             }
 
             $result = AuthLDAP::ldapImportUserByServerId(
                [
                   'method'             => AuthLDAP::IDENTIFIER_LOGIN,
                   'value'              => $value,
                   'identifier_field'   => $id_field,
                   'user_field'         => $user_field
                ],
                $action_to_do,
                $options['ldapservers_id']
             );
 
             if ($result) {
                $results[$result['action']] += 1;
             }
             echo ".";
          }
       } else if (!$users) {
          $contact_ok = false;
       }
    }
 
    if ($limitexceeded) {
       echo "\nLDAP Server size limit exceeded";
       if ($CFG_GLPI['user_deleted_ldap']) {
          echo ": user deletion disabled\n";
       }
       echo "\n";
    }
    if ($contact_ok) {
       echo "\nImported: ".$results[AuthLDAP::USER_IMPORTED]."\n";
       echo "Synchronized: ".$results[AuthLDAP::USER_SYNCHRONIZED]."\n";
       echo "Deleted from LDAP: ".$results[AuthLDAP::USER_DELETED_LDAP]."\n";
    } else {
       echo "Cannot contact LDAP server!\n";
    }
    echo "\n\n";
  }
  
}
