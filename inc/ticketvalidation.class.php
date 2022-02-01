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
use Glpi\Event;
/**
 * TicketValidation class
 */
class PluginServicesTicketValidation  extends TicketValidation {

  // From CommonDBChild
  static public $itemtype           = 'Ticket';
  static public $items_id           = 'tickets_id';

  static $rightname                 = 'ticketvalidation';

  const CREATEREQUEST               = 1024;
  const CREATEINCIDENT              = 2048;
  const VALIDATEREQUEST             = 4096;
  const VALIDATEINCIDENT            = 8192;



  static function getCreateRights() {
    return [static::CREATEREQUEST, static::CREATEINCIDENT];
  }


  static function getValidateRights() {
    return [static::VALIDATEREQUEST, static::VALIDATEINCIDENT];
  }

  static function getTable($classname = null) {
    return "glpi_ticketvalidations";
  }
  
  /**
    * @since 0.85
   **/
  function canCreateItem() {

    if ($this->canChildItem('canViewItem', 'canView')) {
        $ticket = new Ticket();
        if ($ticket->getFromDB($this->fields['tickets_id'])) {
          // No validation for closed tickets
          if (in_array($ticket->fields['status'], $ticket->getClosedStatusArray())) {
              return false;
          }

          if ($ticket->fields['type'] == Ticket::INCIDENT_TYPE) {
              return Session::haveRight(self::$rightname, self::CREATEINCIDENT);
          }
          if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
              return Session::haveRight(self::$rightname, self::CREATEREQUEST);
          }
        }
    }
  }

  /**
  * @since 0.85
  *
  * @see commonDBTM::getRights()
  **/
  function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[UPDATE], $values[CREATE], $values[READ]);

      $values[self::CREATEREQUEST]
                              = ['short' => __('Create for request'),
                                      'long'  => __('Create a validation request for a request')];
      $values[self::CREATEINCIDENT]
                              = ['short' => __('Create for incident'),
                                      'long'  => __('Create a validation request for an incident')];
      $values[self::VALIDATEREQUEST]
                              = __('Validate a request');
      $values[self::VALIDATEINCIDENT]
                              = __('Validate an incident');

      if ($interface == 'helpdesk') {
        unset($values[PURGE]);
      }

      return $values;
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

  function showForm($ID, $options = []) {
    if ($ID > 0) {
        $this->canEdit($ID);
    } else {
        $options[static::$items_id] = $_GET['parentid'];
        $this->check(-1, CREATE, $options);
    }

    // No update validation is answer set
    $validation_admin   = (($this->fields["users_id"] == Session::getLoginUserID())
                            && static::canCreate()
                            && ($this->fields['status'] == self::WAITING));

    $validator = ($this->fields["users_id_validate"] == Session::getLoginUserID());

    echo'<div class="m-content">';
      echo'<div class="row">';
          echo'<div class="col-lg-12">';
            echo'<div class="m-portlet">';
              echo'<div class="m-portlet__head">';
                echo'<div class="m-portlet__head-caption">';
                  echo'<div class="m-portlet__head-title">';
                      echo'<h3 class="m-portlet__head-text">Envoyer une demande de validation</h3>';
                  echo'</div>';
                echo'</div>';
              echo'</div>';
              echo'<div class="m-portlet__body">';

                  $this->showFormHeader($options);
                  if ($validation_admin) {
                      if ($this->getType() == 'ChangeValidation') {
                        $validation_right = 'validate';
                      } else if ($this->getType() == 'PluginServicesTicketValidation') {
                        $ticket = new Ticket();
                        $ticket->getFromDB($this->fields[static::$items_id]);

                        $validation_right = 'validate_incident';
                        if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
                            $validation_right = 'validate_request';
                        }
                      }


                        echo "<input type='hidden' name='".static::$items_id."' value='".
                        $this->fields[static::$items_id]."'>";
                        echo"<div class='form-group m-form__group row  pt-0'>";
                              echo"<label  class='col-form-label col-lg-3 col-sm-12'>".__('Approver')." </label>";
                              echo"<div class='col-lg-6 col-md-6 col-sm-12'>";
                                    if ($ID > 0) {
                                        echo'<input type="text" class="form-control" disabled="" value="'.getUserName($this->fields["users_id_validate"]).'">';
                                        echo "<input type='hidden' name='users_id_validate' value='".$this->fields['users_id_validate']."'>";
                                    } else {
                                          $params = [
                                                      'id'        => $this->fields["id"],
                                                      'entity'    => $this->getEntityID(),
                                                      'right'     => $validation_right
                                                    ];
                                        if (!is_null($this->fields['users_id_validate'])) {
                                          $params['users_id_validate'] = $this->fields['users_id_validate'];
                                        }
                                        self::dropdownValidator($params);
                                    }
                              echo "</div>";
                        echo "</div>";
                        echo"<div class='form-group m-form__group row  pt-0'>";
                            echo"<label class='col-form-label col-lg-3 col-sm-12'>".__('Comments')." </label>";
                            echo"<div class='col-lg-6 col-md-6 col-sm-12'>";
                              PluginServicesHtml::textarea([
                                  'name'  =>'comment_submission',
                                  'value' =>  $this->fields["comment_submission"],
                                  'rows'    => 5,
                              ]);
                            echo "</div>";
                        echo"</div>";
                  } else{
                      // echo "<tr class='tab_bg_1'>";
                      // echo "<td>".__('Approval requester')."</td>";
                      // echo "<td>".getUserName($this->fields["users_id"])."</td></tr>";

                      // echo "<tr class='tab_bg_1'><td>".__('Approver')."</td>";
                      // echo "<td>".getUserName($this->fields["users_id_validate"])."</td></tr>";
                      // echo "</td></tr>";

                      // echo "<tr class='tab_bg_1'>";
                      // echo "<td>".__('Comments')."</td>";
                      // echo "<td>";
                      // echo $this->fields["comment_submission"];
                      // echo "</td></tr>";
                  }
    
                  if ($ID > 0) {
                      if ($validator) {
                        echo"<div class='form-group m-form__group row pt-0'>";
                            echo"<label class='col-form-label col-lg-3 col-sm-12'>".__('Status of my validation')." </label>";
                            echo"<div class='col-lg-6 col-md-6 col-sm-12'>";
                              self::dropdownStatus("status", ['value' => $this->fields["status"]]);
                            echo "</div>";
                        echo"</div>";

                        echo"<div class='form-group m-form__group row pt-0'>";
                            echo"<label class='col-form-label col-lg-3 col-sm-12'>".__('Approval comments')." </label>";
                            echo"<div class='col-lg-6 col-md-6 col-sm-12'>";
                              PluginServicesHtml::textarea([
                                  'name'  =>'comment_validation',
                                  'value' =>  $this->fields["comment_validation"]
                              ]);
                            echo "</div>";
                        echo"</div>";
                      } else {
                        $status = [self::REFUSED,self::ACCEPTED];
                        if (in_array($this->fields["status"], $status)) {
                            echo "<tr class='tab_bg_1'>";
                            echo "<td>".__('Approval comments')."</td>";
                            echo "<td>".$this->fields["comment_validation"]."</td></tr>";
                        }
                      }
                  }
                  echo"<div class='form-group m-form__group row  pt-0'>";
                      echo"<div class='col-lg-9 d-flex justify-content-end'>";
                            $this->showFormButtons($options);
                      echo"</div>";
                  echo"</div>";
              echo"</div>";
            echo"</div>";
          echo"</div>";
      echo"</div>";
    echo"</div>";
    return true;
  }

  function showForm_old($ID, $options = []) {
    if ($ID > 0) {
        $this->canEdit($ID);
    } else {
        $options[static::$items_id] = $options['parent']->fields["id"];
        $this->check(-1, CREATE, $options);
    }

    // No update validation is answer set
    $validation_admin   = (($this->fields["users_id"] == Session::getLoginUserID())
                            && static::canCreate()
                            && ($this->fields['status'] == self::WAITING));

    $validator = ($this->fields["users_id_validate"] == Session::getLoginUserID());

    $this->showFormHeader($options);

    if ($validation_admin) {
        if ($this->getType() == 'ChangeValidation') {
          $validation_right = 'validate';
        } else if ($this->getType() == 'PluginServicesTicketValidation') {
          $ticket = new Ticket();
          $ticket->getFromDB($this->fields[static::$items_id]);

          $validation_right = 'validate_incident';
          if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
              $validation_right = 'validate_request';
          }
        }

          echo "<input type='hidden' name='".static::$items_id."' value='".
          $this->fields[static::$items_id]."'>";
          echo"<div class='form-group m-form__group row  pt-0'>";
                echo"<label  class='col-form-label col-lg-3 col-sm-12'>".__('Approver')." </label>";
                echo"<div class='col-lg-6 col-md-6 col-sm-12'>";
                      if ($ID > 0) {
                          echo'<input type="text" class="form-control" disabled="" value="'.getUserName($this->fields["users_id_validate"]).'">';
                          echo "<input type='hidden' name='users_id_validate' value='".$this->fields['users_id_validate']."'>";
                      } else {
                            $params = [
                                        'id'        => $this->fields["id"],
                                        'entity'    => $this->getEntityID(),
                                        'right'     => $validation_right
                                      ];
                          if (!is_null($this->fields['users_id_validate'])) {
                            $params['users_id_validate'] = $this->fields['users_id_validate'];
                          }
                          self::dropdownValidator($params);
                      }
                echo "</div>";
          echo "</div>";
          echo"<div class='form-group m-form__group row  pt-0'>";
              echo"<label class='col-form-label col-lg-3 col-sm-12'>".__('Comments')." </label>";
              echo"<div class='col-lg-6 col-md-6 col-sm-12'>";
                PluginServicesHtml::textarea([
                    'name'  =>'comment_submission',
                    'value' =>  $this->fields["comment_submission"],
                    'rows'    => 5,
                ]);
              echo "</div>";
          echo"</div>";
    } else{
        // echo "<tr class='tab_bg_1'>";
        // echo "<td>".__('Approval requester')."</td>";
        // echo "<td>".getUserName($this->fields["users_id"])."</td></tr>";

        // echo "<tr class='tab_bg_1'><td>".__('Approver')."</td>";
        // echo "<td>".getUserName($this->fields["users_id_validate"])."</td></tr>";
        // echo "</td></tr>";

        // echo "<tr class='tab_bg_1'>";
        // echo "<td>".__('Comments')."</td>";
        // echo "<td>";
        // echo $this->fields["comment_submission"];
        // echo "</td></tr>";
    }


    
    if ($ID > 0) {
        if ($validator) {
          echo"<div class='form-group m-form__group row pt-0'>";
              echo"<label class='col-form-label col-lg-3 col-sm-12'>".__('Status of my validation')." </label>";
              echo"<div class='col-lg-6 col-md-6 col-sm-12'>";
                self::dropdownStatus("status", ['value' => $this->fields["status"]]);
              echo "</div>";
          echo"</div>";

          echo"<div class='form-group m-form__group row pt-0'>";
              echo"<label class='col-form-label col-lg-3 col-sm-12'>".__('Approval comments')." </label>";
              echo"<div class='col-lg-6 col-md-6 col-sm-12'>";
                PluginServicesHtml::textarea([
                    'name'  =>'comment_validation',
                    'value' =>  $this->fields["comment_validation"]
                ]);
              echo "</div>";
          echo"</div>";
        } else {
          $status = [self::REFUSED,self::ACCEPTED];
          if (in_array($this->fields["status"], $status)) {
              echo "<tr class='tab_bg_1'>";
              echo "<td>".__('Approval comments')."</td>";
              echo "<td>".$this->fields["comment_validation"]."</td></tr>";
          }
        }
    }
    echo"<div class='row mb-3'>";
        echo"<div class='col-lg-9 d-flex justify-content-end'>";
              $this->showFormButtons($options);
        echo"</div>";
    echo"</div>";
    return true;
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

  function showFormHeader($options = []) {

    $ID   =  $this->fields['id'];

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
        echo "<form class='m-form m-form--fit m-form--label-align-right ' name='form' method='post' action='".$params['target']."' ".
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
        // echo "<tr class='headerRow'><th colspan='".$params['colspan']."'>";

        if (!empty($params['withtemplate']) && ($params['withtemplate'] == 2)
          && !$this->isNewID($ID)) {

          echo "<input type='hidden' name='template_name' value='".$this->fields["template_name"]."'>";

          //TRANS: %s is the template name
          // printf(__('Created from the template %s'), $this->fields["template_name"]);

        } else if (!empty($params['withtemplate']) && ($params['withtemplate'] == 1)) {
          echo "<input type='hidden' name='is_template' value='1'>\n";
          // echo "<label for='textfield_template_name$rand'>" . __('Template name') . "</label>";
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
          $entityname = PluginServicesDropdown::getDropdownName("glpi_entities", $this->fields["entities_id"]);
        }

        // echo "</th><th colspan='".$params['colspan']."'>";
        if (get_class($this) != 'Entity') {
          if ($this->maybeRecursive()) {
              if (Session::isMultiEntitiesMode()) {
                // echo "<table class='tab_format'><tr class='headerRow responsive_hidden'><th>".$entityname."</th>";
                // echo "<th class='right'><label for='dropdown_is_recursive$rand'>".__('Child entities')."</label></th><th>";
                if ($params['canedit']) {
                    if ($this instanceof CommonDBChild) {
                      echo PluginServicesDropdown::getYesNo($this->isRecursive());
                      if (isset($this->fields["is_recursive"])) {
                          echo "<input type='hidden' name='is_recursive' value='".$this->fields["is_recursive"]."'>";
                      }
                      $comment = __("Can't change this attribute. It's inherited from its parent.");
                      // CommonDBChild : entity data is get or copy from parent

                    } else if (!$this->can($ID, 'recursive')) {
                      echo PluginServicesDropdown::getYesNo($this->fields["is_recursive"]);
                      $comment = __('You are not allowed to change the visibility flag for child entities.');
                    } else if (!$this->canUnrecurs()) {
                      echo PluginServicesDropdown::getYesNo($this->fields["is_recursive"]);
                      $comment = __('Flag change forbidden. Linked items found.');

                    } else {
                      PluginServicesDropdown::showYesNo("is_recursive", $this->fields["is_recursive"], -1, ['rand' => $rand]);
                      $comment = __('Change visibility in child entities');
                    }
                    echo " ";
                    Html::showToolTip($comment);
                } else {
                    echo PluginServicesDropdown::getYesNo($this->fields["is_recursive"]);
                }
                // echo "</th></tr></table>";
              } else {
                // echo $entityname;
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
        PluginServicesHtml::closeForm();
        return false;
    }

    echo "<tr class='tab_bg_2'>";

    if ($params['withtemplate']
        ||$this->isNewID($ID)) {

        echo "<td class='center' colspan='".($params['colspan']*2)."'>";

        if (($ID <= 0) || ($params['withtemplate'] == 2)) {
          echo PluginServicesHtml::submit(_x('button', 'Soumettre'),
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
          if ($this->isDeleted()) {
              if ($this->can($ID, DELETE)) {
                echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
                echo PluginServicesHtml::submit(_x('button', 'Restore'),
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
                echo PluginServicesHtml::submit(_x('button', 'Delete permanently'),
                    ['name' => 'purge']
                );
                echo "</span>";
              }

          } else {
              echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
              // If maybe dynamic : do not take into account  is_deleted  field
              if (!$this->maybeDeleted()
                  || $this->useDeletedToLockIfDynamic()) {
                if ($this->can($ID, PURGE)) {
                    echo PluginServicesHtml::submit(_x('button', 'Delete permanently'),
                      [
                          'name'    => 'purge',
                          'confirm' => __('Confirm the final deletion?')
                      ]
                    );
                }
              } else if (!$this->isDeleted()
                      && $this->can($ID, DELETE)) {
                echo PluginServicesHtml::submit(_x('button', 'Put in trashbin'),
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
    PluginServicesHtml::closeForm();
  }

  static function dropdownValidator(array $options = []) {
    global $CFG_GLPI;

    $params = [
        'name'              => '' ,
        'id'                => 0,
        'entity'            => $_SESSION['glpiactive_entity'],
        'right'             => ['validate_request', 'validate_incident'],
        'groups_id'         => 0,
        'users_id_validate' => [],
        'applyto'           => 'show_validator_field',
    ];

    foreach ($options as $key => $val) {
        $params[$key] = $val;
    }

    $types = ['user'  => User::getTypeName(1),
                    'group' => Group::getTypeName(1)];

    $type  = '';
    if (isset($params['users_id_validate']['groups_id'])) {
        $type = 'group';
    } else if (!empty($params['users_id_validate'])) {
        $type = 'user';
    }

    $rand = PluginServicesDropdown::showFromArray("validatortype", $types,
                                  ['value'               => $type,
                                        'display_emptychoice' => true]);

    if ($type) {
        $params['validatortype'] = $type;
        Ajax::updateItem($params['applyto'], $CFG_GLPI["root_doc"]."/ajax/dropdownValidator.php",
                          $params);
    }
    $params['validatortype'] = '__VALUE__';
    Ajax::updateItemOnSelectEvent("dropdown_validatortype$rand", $params['applyto'],
                                  $CFG_GLPI["root_doc"]."/ajax/dropdownValidator.php", $params);

    if (!isset($options['applyto'])) {
        echo "<div class='mt-1 mb-1' id='".$params['applyto']."'></div>\n";
    }
  }

  function postForm($post) {
    $validation = new self();
    Session::checkLoginUser();

    if (!$validation->canView()) {
      PluginServicesHtml::displayRightError();
    }

    $itemtype = $validation->getItilObjectItemType();
    $fk       = 'tickets_id';
    if (isset($_POST["add"])) {
      $validation->check(-1, CREATE, $_POST);
      if (isset($_POST['users_id_validate'])
          && (count($_POST['users_id_validate']) > 0)) {
          $users = $_POST['users_id_validate'];
          foreach ($users as $user) {
            $_POST['users_id_validate'] = $user;
            $validation->add($_POST);
            Event::log($validation->getField($fk), strtolower($itemtype), 4, "tracking",
                        //TRANS: %s is the user login
                        sprintf(__('%s adds an approval'), $_SESSION["glpiname"]));
          }
      }
      $backurl = PluginServicesToolbox::getFormURLWithID($validation->getField($fk), true, $itemtype);
      Html::redirect($backurl);
    } else if (isset($_POST["update"])) {
      $validation->check($_POST['id'], UPDATE);
      $validation->update($_POST);
      Event::log($validation->getField($fk), strtolower($itemtype), 4, "tracking",
            //TRANS: %s is the user login
            sprintf(__('%s updates an approval'), $_SESSION["glpiname"]));

      $backurl = PluginServicesToolbox::getFormURLWithID($validation->getField($fk), true, $itemtype);
      Html::redirect($backurl);
    } else if (isset($_POST["purge"])) {
      $validation->check($_POST['id'], PURGE);
      $validation->delete($_POST, 1);

      Event::log($validation->getField($fk), strtolower($itemtype), 4, "tracking",
                  //TRANS: %s is the user login
                  sprintf(__('%s purges an approval'), $_SESSION["glpiname"]));
      $backurl = PluginServicesToolbox::getFormURLWithID($validation->getField($fk), true, $itemtype);
      Html::redirect($backurl);
    } else if (isset($_POST['approval_action'])) {
      if ($_POST['users_id_validate'] == Session::getLoginUserID()) {
          $validation->update($_POST + [
            'status' => ($_POST['approval_action'] === 'approve') ? CommonITILValidation::ACCEPTED : CommonITILValidation::REFUSED
          ]);
          $backurl = PluginServicesToolbox::getFormURLWithID($validation->getField($fk), true, $itemtype);
          Html::redirect($backurl);
      }
    }
    PluginServicesHtml::displayErrorAndDie('Lost');
  }

  function showSummary(CommonDBTM $item) {
    global $DB, $CFG_GLPI;

    if (!Session::haveRightsOr(static::$rightname,
                                array_merge(static::getCreateRights(),
                                            static::getValidateRights(),
                                            static::getPurgeRights()))) {
        return false;
    }

    $tID    = $item->fields['id'];

    $tmp    = [static::$items_id => $tID];
    $canadd = $this->can(-1, CREATE, $tmp);
    $rand   = mt_rand();

    $itemtype = static::$itemtype;
    $sticket = new PluginServicesTicket();
  
    echo"<div class='row justify-content-center mt-2'>";
        echo "<div class='col-lg-12 col-md-12 col-sm-12' id='viewvalidation" . $tID . "$rand'></div>\n";
        if ($canadd) {
          if (!in_array($item->fields['status'], array_merge($item->getSolvedStatusArray(),
              $item->getClosedStatusArray()))) {
              echo"
                <script>
                  $(document).ready(function() {viewAddValidation".$tID."$rand();});
                </script>
              ";
          }
        }
    echo"</div>";
    if ($canadd) {
      echo "<script type='text/javascript' >\n";
        echo "function viewAddValidation" . $tID . "$rand() {\n";
        $params = [
                    'type'             => 'PluginServicesTicketValidation',
                    'parenttype'       => static::$itemtype,
                    static::$items_id  => $tID,
                    'id'               => -1
                ];
        Ajax::updateItemJsCode("viewvalidation" . $tID . "$rand",
                            $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php",
                            $params);
        echo "};";
      echo "</script>\n";
    }

    $iterator = $DB->Request([
        'FROM'   => $this->getTable(),
        'WHERE'  => [static::$items_id => $item->getField('id')],
        'ORDER'  => 'submission_date DESC'
    ]);

    $colonnes = [_x('item', 'State'), __('Request date'), __('Approval requester'),
                    __('Request comments'), __('Approval date'),
                    __('Approver'), __('Approval comments')];
    $nb_colonnes = count($colonnes);

    echo "<table class='tab_cadre_fixehov'>"; 
          $header = "<thead>";
            // $header = "<tr>";
            foreach ($colonnes as $colonne) {
                $header .= "<th>".$colonne."</th>";
            }
            // $header .= "</tr>";
          $header .= "</thead>";
          echo $header;
          if (count($iterator)) {
            
              Session::initNavigateListItems($this->getType(),
                    //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                            sprintf(__('%1$s = %2$s'), $item->getTypeName(1),
                                                    $item->fields["name"]));

              while ($row = $iterator->next()) {
                $canedit = $this->canEdit($row["id"]);
                Session::addToNavigateListItems($this->getType(), $row["id"]);
                $bgcolor = self::getStatusColor($row['status']);
                $status  = self::getStatus($row['status']);

                echo "<tr class='tab_bg_1' ".
                      ($canedit ? "style='cursor:pointer' onClick=\"viewEditValidation".
                                  $item->fields['id'].$row["id"]."$rand();\""
                                : '') .
                      " id='viewvalidation" . $this->fields[static::$items_id] . $row["id"] . "$rand'>";
                echo "<td>";
                if ($canedit) {
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditValidation" .$item->fields['id']. $row["id"]. "$rand() {\n";
                    $params = ['type'             => 'PluginServicesTicketValidation',
                                  'parenttype'       => static::$itemtype,
                                  static::$items_id  => $this->fields[static::$items_id],
                                  'id'               => $row["id"]];
                    Ajax::updateItemJsCode("viewvalidation" . $item->fields['id'] . "$rand",
                                        $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php",
                                        $params);
                    echo "};";
                    echo "</script>\n";
                }

                echo "<div style='background-color:".$bgcolor.";'>".$status."</div></td>";

                echo "<td>".Html::convDateTime($row["submission_date"])."</td>";
                echo "<td>".getUserName($row["users_id"])."</td>";
                echo "<td>".PluginServicesHtml::resume_text($row["comment_submission"])."</td>";
                echo "<td>".Html::convDateTime($row["validation_date"])."</td>";
                echo "<td>".getUserName($row["users_id_validate"])."</td>";
                echo "<td>".$row["comment_validation"]."</td>";
                echo "</tr>";
              }
              echo $header;
          } else {
              echo "<tr class=''><td class='text-center' colspan='" . $nb_colonnes . "'>";
              echo __('No item found')."</td></tr>\n";
          }
    echo "</table>";
  }

  static function alertValidation(CommonITILObject $item, $type) {
    global $CFG_GLPI;

    // No alert for new item
    if ($item->isNewID($item->getID())) {
      return;
    }
    $status  = array_merge($item->getClosedStatusArray(), $item->getSolvedStatusArray());

    $message = __s("This item is waiting for approval, do you really want to resolve or close it?");

    switch ($type) {
       case 'status' :
          $jsScript = "
             $(document).ready(
                function() {
                   $('[name=\"status\"]').change(function() {
                      var status_ko = 0;
                      var input_status = $(this).val();
                      if (input_status != undefined) {
                         if ((";
          $first = true;
          foreach ($status as $val) {
             if (!$first) {
                $jsScript .= "||";
             }
             $jsScript .= "input_status == $val";
             $first = false;
          }
          $jsScript .= "           )
                               && input_status != ".$item->fields['status']."){
                            status_ko = 1;
                         }
                      }
                      if ((status_ko == 1)
                          && ('".($item->fields['global_validation'] ?? '')."' == '".self::WAITING."')) {
                         alert('".$message."');
                      }
                   });
                }
             );";
          echo Html::scriptBlock($jsScript);
          break;

       case 'solution' :
          if (!in_array($item->fields['status'], $status)
             && isset($item->fields['global_validation'])
             && $item->fields['global_validation'] == self::WAITING) {
             PluginServicesHtml::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
          }
          break;
    }
  }

      /**
    * Dropdown of validation status
    *
    * @param string $name    select name
    * @param array  $options possible options:
    *      - value    : default value (default waiting)
    *      - all      : boolean display all (default false)
    *      - global   : for global validation (default false)
    *      - display  : boolean display or get string ? (default true)
    *
    * @return string|integer Output string if display option is set to false,
    *                        otherwise random part of dropdown id
   **/
  static function dropdownStatus($name, $options = []) {

    $p = [
        'value'    => self::WAITING,
        'global'   => false,
        'all'      => false,
        'display'  => true,
    ];

    if (is_array($options) && count($options)) {
        foreach ($options as $key => $val) {
          $p[$key] = $val;
        }
    }

    $tab = self::getAllStatusArray($p['all'], $p['global']);
    unset($p['all']);
    unset($p['global']);

    return PluginServicesDropdown::showFromArray($name, $tab, $p);
  }

  function rawSearchOptions() {
    $tab = [];

    $tab[] = [
      'id'                 => 'validation',
      'name'               => CommonITILValidation::getTypeName(1)
    ];

    // $tab[] = [
    //     'id'                 => '51',
    //     'table'              => getTableForItemType(static::$itemtype),
    //     'field'              => 'validation_percent',
    //     'name'               => __('Minimum validation required'),
    //     'datatype'           => 'number',
    //     'unit'               => '%',
    //     'min'                => 0,
    //     'max'                => 100,
    //     'step'               => 50
    // ];

    $tab[] = [
      'id'                 => '52',
      'table'              => getTableForItemType(static::$itemtype),
      'field'              => 'global_validation',
      'name'               => CommonITILValidation::getTypeName(1),
      'searchtype'         => 'equals',
      'datatype'           => 'specific'
    ];

    $tab[] = [
        'id'                 => '1',
        'table'              => static::getTable(),
        'field'              => 'comment_submission',
        'name'               => __('Request comments'),
        'datatype'           => 'itemlink',
        'searchtype'         => 'contains',
        'forcegroupby'       => true,
        'massiveaction'      => true,
        'joinparams'         => [
          'jointype'           => 'child'
        ]
    ];

    $tab[] = [
        'id'                 => '54',
        'table'              => static::getTable(),
        'field'              => 'comment_validation',
        'name'               => __('Approval comments'),
        'datatype'           => 'text',
        'forcegroupby'       => true,
        'massiveaction'      => false,
        'joinparams'         => [
          'jointype'           => 'child'
        ]
    ];

    $tab[] = [
        'id'                 => '55',
        'table'              => static::getTable(),
        'field'              => 'status',
        'datatype'           => 'specific',
        'name'               => __('Approval status'),
        'searchtype'         => 'equals',
        'forcegroupby'       => true,
        'massiveaction'      => false,
        'joinparams'         => [
          'jointype'           => 'child'
        ]
    ];

    $tab[] = [
      'id'                 => '56',
      'table'              => static::getTable(),
      'field'              => 'submission_date',
      'name'               => __('Request date'),
      'datatype'           => 'datetime',
      'forcegroupby'       => true,
      'massiveaction'      => false,
      'joinparams'         => [
        'jointype'           => 'child'
      ]
    ];

    $tab[] = [
        'id'                 => '57',
        'table'              => static::getTable(),
        'field'              => 'validation_date',
        'name'               => __('Approval date'),
        'datatype'           => 'datetime',
        'forcegroupby'       => true,
        'massiveaction'      => false,
        'joinparams'         => [
          'jointype'           => 'child'
        ]
    ];

    $tab[] = [
        'id'                 => '58',
        'table'              => 'glpi_users',
        'field'              => 'name',
        'name'               => _n('Requester', 'Requesters', 1),
        'datatype'           => 'itemlink',
        'right'              => (static::$itemtype == 'Ticket' ? 'create_ticket_validate' : 'create_validate'),
        'forcegroupby'       => true,
        'massiveaction'      => false,
        'joinparams'         => [
          'beforejoin'         => [
              'table'              => static::getTable(),
              'joinparams'         => [
                'jointype'           => 'child'
              ]
          ]
        ]
    ];

    $tab[] = [
        'id'                 => '59',
        'table'              => 'glpi_users',
        'field'              => 'name',
        'linkfield'          => 'users_id_validate',
        'name'               => __('Approver'),
        'datatype'           => 'itemlink',
        'right'              => (static::$itemtype == 'Ticket' ?
          ['validate_request', 'validate_incident'] :
          'validate'
        ),
        'forcegroupby'       => true,
        'massiveaction'      => false,
        'joinparams'         => [
          'beforejoin'         => [
              'table'              => static::getTable(),
              'joinparams'         => [
                'jointype'           => 'child'
              ]
          ]
        ]
    ];

    $tab[] = [
      'id'                 => '60',
      'table'              => Ticket::getTable(),
      'field'              => 'name',
      'linkfield'          => 'tickets_id',
      'name'               => Ticket::getTypeName(1),
      'datatype'           => 'dropdown',
      'massiveaction'      => false,
    ];
    return $tab;
  }
  
}
