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
 * ITILSolution Class
**/
class PluginServicesITILSolution extends ITILSolution {

   // From CommonDBTM
   static $rightname = 'plugin_services_itil_solution';
   public $dohistory                   = true;
   private $item                       = null;

   static public $itemtype = 'itemtype'; // Class name or field name (start with itemtype) for link to Parent
   static public $items_id = 'items_id'; // Field name

   public static function getNameField() {
      return 'id';
   }

   static function getTable($classname = null) {
      return "glpi_itilsolutions";
   }

   static function getTypeName($nb = 0) {
      return _n('Solution', 'Solutions', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->isNewItem()) {
         return;
      }
      if ($item->maySolve()) {
         $nb    = 0;
         $title = self::getTypeName(Session::getPluralNumber());
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::countFor($item->getType(), $item->getID());
         }
         return self::createTabEntry($title, $nb);
      }
   }

   static function showSolution($item) {
      $params = [
                  'action' => 'viewsubitem',
                  'type'       => 'itemtype',
                  'parenttype' => "Ticket",
                  'tickets_id' => $item->fields['id'],
                  'id'         => -1
               ];

      $item = getItemForItemtype($params['type']);
      $parent = getItemForItemtype($params['parenttype']);
      $parent->getFromDB($params[$parent->getForeignKeyField()]);

      if (!isset($params['load_kb_sol'])) {
         $params['load_kb_sol'] = 0;
      }

      $sol_params = [
         'item'         => $parent,
         'kb_id_toload' => $params['load_kb_sol']
      ];

      $solution = new PluginServicesITILSolution();
      $id = isset($params['id']) && (int)$params['id'] > 0 ? $params['id'] : null;
      if ($id) {
         $solution->getFromDB($id);
      }
      $solution->showForm($id, $sol_params);
   }

   static function canView() {
      return Session::haveRight('ticket', READ)
            || Session::haveRight('change', READ)
            || Session::haveRight('problem', READ);
   }

   public static function canUpdate() {
      //always true, will rely on ITILSolution::canUpdateItem
      return true;
   }

   public function canUpdateItem() {
      return $this->item->maySolve();
   }

   public static function canCreate() {
      //always true, will rely on ITILSolution::canCreateItem
      return true;
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
   public function canCreateItem() {
      // $item = new $this->fields['itemtype'];
      // $item->getFromDB($this->fields['items_id']);
      // return $item->canSolve();
      return $this->item->canSolve();
   }

   function canEdit($ID) {
      return $this->item->maySolve();
   }

   function post_getFromDB() {
      $this->item = new $this->fields['itemtype'];
      $this->item->getFromDB($this->fields['items_id']);
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

   function postForm($post){
      
      Session::checkLoginUser();

      $solution = new ITILSolution();
      $track = new $_POST['itemtype'];
      $track->getFromDB($_POST['items_id']);

      $redirect = null;
      $handled = false;

      if (isset($_POST["add"])) {
         $solution->check(-1, CREATE, $_POST);
         if (!$track->canSolve()) {
            Session::addMessageAfterRedirect(
               __('You cannot solve this item!'),
               false,
               ERROR
            );
            PluginServicesHtml::back();
         }

         if ($solution->add($_POST)) {
            if ($_SESSION['glpibackcreated']) {
               $redirect = $track->getLinkURL();
            }
            $handled = true;
         }
      } else if (isset($_POST['update'])) {
         $solution->getFromDB($_POST['id']);
         $solution->check($_POST['id'], UPDATE);
         $solution->update($_POST);
         $handled = true;
         $redirect = $track->getLinkURL();

         Event::log($_POST["id"], "solution", 4, "tracking",
                  //TRANS: %s is the user login
                  sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
         PluginServicesHtml::back();
      }

      if ($handled) {
         if (isset($_POST['kb_linked_id'])) {
            //if solution should be linked to selected KB entry
            $params = [
               'knowbaseitems_id' => $_POST['kb_linked_id'],
               'itemtype'         => $track->getType(),
               'items_id'         => $track->getID()
            ];
            $existing = $DB->request(
               'glpi_knowbaseitems_items',
               $params
            );
            if ($existing->numrows() == 0) {
               $kb_item_item = new KnowbaseItem_Item();
               $kb_item_item->add($params);
            }
         }

         if ($track->can($_POST["items_id"], READ)) {
            $toadd = '';
            // Copy solution to KB redirect to KB
            if (isset($_POST['_sol_to_kb']) && $_POST['_sol_to_kb']) {
               $toadd = "&_sol_to_kb=1";
            }
            $redirect = $track->getLinkURL() . $toadd;
         } else {
            Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this ticket'),
                                          true, ERROR);
            $redirect = $track->getSearchURL();
         }
      }

      if (null == $redirect) {
         PluginServicesHtml::back();
      } else {
         PluginServicesHtml::redirect($redirect);
      }
   }


   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if ($this->isNewItem()) {
         $this->getEmpty();
      }

      if (!isset($options['item']) && isset($options['parent'])) {
         //when we came from aja/viewsubitem.php
         $options['item'] = $options['parent'];
      }
      $options['formoptions'] = ($options['formoptions'] ?? '') . ' data-track-changes=true';

      $item = $options['item'];
      $this->item = $item;
      $item->check($item->getID(), READ);

      $entities_id = isset($options['entities_id']) ? $options['entities_id'] : $item->getEntityID();

      if ($item instanceof Ticket && $this->isNewItem()) {
         $ti = new Ticket_Ticket();
         $open_child = $ti->countOpenChildren($item->getID());
         if ($open_child > 0) {
            echo "<div class='tab_cadre_fixe warning'>" . __('Warning: non closed children tickets depends on current ticket. Are you sure you want to close it?')  . "</div>";
         }
      }

      $canedit = $item->maySolve();

      if (isset($options['kb_id_toload']) && $options['kb_id_toload'] > 0) {
         $kb = new KnowbaseItem();
         if ($kb->getFromDB($options['kb_id_toload'])) {
            $this->fields['content'] = $kb->getField('answer');
         }
      }

      echo'<div class="row">'; 
         echo'<div class="col-lg-2">';
         echo'</div>'; 
         echo'<div class="col-lg-8">';
            $validationtype = $item->getType().'Validation';
            if (method_exists($validationtype, 'alertValidation') && $this->isNewItem()) {
               $validationtype::alertValidation($item, 'solution');
            }
         echo'</div>'; 
      echo'</div>';

      if (!isset($options['noform'])) {
         $this->showFormHeader($options);
      }

      $show_template = $canedit;
      $rand_template = mt_rand();
      $rand_text     = $rand_type = 0;
      if ($canedit) {
         $rand_text = mt_rand();
         $rand_type = mt_rand();
      }
      $all_fields = [
         [
            'label'=> 'Resolution code',
            'name'=> 'solutiontypes_id',
            'type'=> 'dropdown',
            'rand'   => $rand_type,
            'entity' => $entities_id,
            'readOnly' => ($canedit) ? false : true
         ],[
            'label'=> 'Resolved by',
            'name'=> 'users_id',
            'type'=> 'dropdown',
            'type'=> 'dropdown',
            'value' => Session::getLoginUserID(),
            'readOnly' => true
         ],[
            'label' => 'Resolution notes',
            'name' => 'content',
            'type' => 'textarea',
            'rows' => 5,
            'mandatory'  => true,
            'readOnly' => ($canedit) ? false : true,
            'full' => true
         ],[
            'name' => 'itemtype',
            'type' => 'hidden',
            'value' => $item->getType()
         ],[
            'name' => 'items_id',
            'type' => 'hidden',
            'value' => $item->getID()
         ],[
            'name' => '_no_message_link',
            'type' => 'hidden',
            'value' => 1
         ]
      ];
      PluginServicesHtml::generateSimpleForm($ID, $this, $options, $all_fields);
      echo'<div class="d-flex bd-highlight">';
            echo'<div class="p-2 flex-grow-1 bd-highlight"></div>';
            echo'<div class="p-2 bd-highlight">';
               if (!isset($options['noform'])) {
                  $options['candel']   = false;
                  $options['canedit']  = $canedit;
                  $this->showFormButtons($options);
               }
            echo'</div>';
      echo'</div>';
   }


   function showApprobationForm($itilobject) {
      if (($itilobject->fields["status"] == CommonITILObject::SOLVED)
         && $itilobject->canApprove()
         && $itilobject->isAllowedStatus($itilobject->fields['status'], CommonITILObject::CLOSED)) {
         echo "<form name='form' method='post' action='".$this->getFormURL()."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>". __('Approval of the solution')."</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>".__('Comments')."<br>(".__('Optional when approved').")</td>";
         echo "<td class='center middle' colspan='2'>";
         echo "<textarea name='content' cols='70' rows='6'></textarea>";
         echo "<input type='hidden' name='itemtype' value='".$itilobject->getType()."'>";
         echo "<input type='hidden' name='items_id' value='".$itilobject->getField('id')."'>";
         echo "<input type='hidden' name='requesttypes_id' value='".
               RequestType::getDefault('followup')."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_2'>";
         echo "<td class='tab_bg_2 center' colspan='2' width='200'>\n";
         echo "<input type='submit' name='add_reopen' value=\"".__('Refuse the solution')."\"
                  class='submit'>";
         echo "</td>\n";
         echo "<td class='tab_bg_2 center' colspan='2'>\n";
         echo "<input type='submit' name='add_close' value=\"".__('Approve the solution')."\"
                  class='submit'>";
         echo "</td></tr>\n";
         echo "</table>";
         Html::closeForm();
      }
      return true;
   }

   public static function countFor($itemtype, $items_id) {
      return countElementsInTable(
         self::getTable(), [
            'WHERE' => [
               'itemtype'  => $itemtype,
               'items_id'  => $items_id
            ]
         ]
      );
   }

   function prepareInputForAdd($input) {
      $input['users_id'] = Session::getLoginUserID();

      if ($this->item == null
         || (isset($input['itemtype']) && isset($input['items_id']))
      ) {
         $this->item = new $input['itemtype'];
         $this->item->getFromDB($input['items_id']);
      }

      // check itil object is not already solved
      if (in_array($this->item->fields["status"], $this->item->getSolvedStatusArray())) {
         Session::addMessageAfterRedirect(__("The item is already solved, did anyone pushed a solution before you ?"),
                                          false, ERROR);
         return false;
      }

      //default status for global solutions
      $status = CommonITILValidation::ACCEPTED;

      //handle autoclose, for tickets only
      if ($input['itemtype'] == Ticket::getType()) {
         $autoclosedelay =  Entity::getUsedConfig(
            'autoclose_delay',
            $this->item->getEntityID(),
            '',
            Entity::CONFIG_NEVER
         );

         // 0 = immediatly
         if ($autoclosedelay != 0) {
            $status = CommonITILValidation::WAITING;
         }
      }

      //Accepted; store user and date
      if ($status == CommonITILValidation::ACCEPTED) {
         $input['users_id_approval'] = Session::getLoginUserID();
         $input['date_approval'] = $_SESSION["glpi_currenttime"];
      }

      $input['status'] = $status;

      return $input;
   }

   function post_addItem() {

      //adding a solution mean the ITIL object is now solved
      //and maybe closed (according to entitiy configuration)
      if ($this->item == null) {
         $this->item = new $this->fields['itemtype'];
         $this->item->getFromDB($this->fields['items_id']);
      }

      $item = $this->item;

      // Replace inline pictures
      $this->input["_job"] = $this->item;
      $this->input = $this->addFiles(
         $this->input, [
            'force_update' => true,
            'name' => 'content',
            'content_field' => 'content',
         ]
      );

      // Add solution to duplicates
      if ($this->item->getType() == 'Ticket' && !isset($this->input['_linked_ticket'])) {
         Ticket_Ticket::manageLinkedTicketsOnSolved($this->item->getID(), $this);
      }

      if (!isset($this->input['_linked_ticket'])) {
         $status = $item::SOLVED;

         //handle autoclose, for tickets only
         if ($item->getType() == Ticket::getType()) {
            $autoclosedelay =  Entity::getUsedConfig(
               'autoclose_delay',
               $this->item->getEntityID(),
               '',
               Entity::CONFIG_NEVER
            );

            // 0 = immediatly
            if ($autoclosedelay == 0) {
               $status = $item::CLOSED;
            }
         }

         $this->item->update([
            'id'     => $this->item->getID(),
            'status' => $status
         ]);
      }

      parent::post_addItem();
   }

   function prepareInputForUpdate($input) {

      if (!isset($this->fields['itemtype'])) {
         return false;
      }
      $input["_job"] = new $this->fields['itemtype']();
      if (!$input["_job"]->getFromDB($this->fields["items_id"])) {
         return false;
      }

      return $input;
   }

   function post_updateItem($history = 1) {
      // Replace inline pictures
      $options = [
         'force_update' => true,
         'name' => 'content',
         'content_field' => 'content',
      ];
      $this->input = $this->addFiles($this->input, $options);
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'status':
            $value = $values[$field];
            $statuses = self::getStatuses();

            return (isset($statuses[$value]) ? $statuses[$value] : $value);
            break;
      }

      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
   * {@inheritDoc}
   * @see CommonDBTM::getSpecificValueToSelect()
   */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'status':
            $options['display'] = false;
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, self::getStatuses(), $options);
            break;
      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * Return list of statuses.
      * Key as status values, values as labels.
      *
      * @return string[]
      */
   static function getStatuses() {
      return [
         CommonITILValidation::WAITING  => __('Waiting for approval'),
         CommonITILValidation::REFUSED  => __('Refused'),
         CommonITILValidation::ACCEPTED => __('Accepted'),
      ];
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
         echo "<form name='form' id='mainform' class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed' method='post' action='".$params['target']."' ".
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
            $entityname = Dropdown::getDropdownName("glpi_entities", $this->fields["entities_id"]);
         }

         // echo "</th><th colspan='".$params['colspan']."'>";
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
            echo PluginServicesHtml::submit(_x('button', 'Soumettre'),
               ['name' => 'add']
            );
         } else {
            //TRANS : means update / actualize
            echo PluginServicesPluginServicesHtml::submit(_x('button', 'Soumettre'),
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
               echo PluginServicesHtml::submit(_x('button', 'Soumettre'),
                  ['name' => 'update']
               );
            }

            if ($params['candel']) {
               if ($params['canedit'] && $this->can($ID, UPDATE)) {
               }
               if ($this->isDeleted()) {
                  if ($this->can($ID, DELETE)) {
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
}
