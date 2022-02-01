<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.4.0
 */
class PluginServicesITILFollowup  extends ITILFollowup {

   // From CommonDBTM
   public $auto_message_on_action = false;
   static $rightname              = 'followup';
   private $item                  = null;

   static public $log_history_add    = Log::HISTORY_LOG_SIMPLE_MESSAGE;
   static public $log_history_update = Log::HISTORY_LOG_SIMPLE_MESSAGE;
   static public $log_history_delete = Log::HISTORY_LOG_SIMPLE_MESSAGE;

   const SEEPUBLIC       =    1;
   const UPDATEMY        =    2;
   const ADDMYTICKET     =    4;
   const UPDATEALL       = 1024;
   const ADDGROUPTICKET  = 2048;
   const ADDALLTICKET    = 4096;
   const SEEPRIVATE      = 8192;

   static public $itemtype = 'itemtype';
   static public $items_id = 'items_id';


   function getItilObjectItemType() {
      return str_replace('Followup', '', $this->getType());
   }


   static function getTypeName($nb = 0) {
      return _n('Followup', 'Followups', $nb);
   }

   static function getTable($classname = null) {
      return "glpi_itilfollowups";
   }


   /**
    * can read the parent ITIL Object ?
    *
    * @return boolean
    */
   function canReadITILItem() {

      $itemtype = $this->getItilObjectItemType();
      $item     = new $itemtype();
      if (!$item->can($this->getField($item->getForeignKeyField()), READ)) {
         return false;
      }
      return true;
   }


   static function canView() {
      return (Session::haveRightsOr(self::$rightname, [self::SEEPUBLIC, self::SEEPRIVATE])
               || Session::haveRight('ticket', Ticket::OWN))
               || Session::haveRight('ticket', READ)
               || Session::haveRight('change', READ)
               || Session::haveRight('problem', READ);
   }


   static function canCreate() {
      return Session::haveRight('change', UPDATE)
            || Session::haveRight('problem', UPDATE)
            || (Session::haveRightsOr(self::$rightname,
                  [self::ADDALLTICKET, self::ADDMYTICKET, self::ADDGROUPTICKET])
            || Session::haveRight('ticket', Ticket::OWN));
   }


   function canViewItem() {

      $itilobject = new $this->fields['itemtype'];
      if (!$itilobject->can($this->getField('items_id'), READ)) {
         return false;
      }
      if (Session::haveRight(self::$rightname, self::SEEPRIVATE)) {
         return true;
      }
      if (!$this->fields['is_private']
         && Session::haveRight(self::$rightname, self::SEEPUBLIC)) {
         return true;
      }
      if ($itilobject instanceof Ticket) {
         if ($this->fields["users_id"] === Session::getLoginUserID()) {
            return true;
         }
      } else {
         return Session::haveRight($itilobject::$rightname, READ);
      }
      return false;
   }


   function canCreateItem() {
      if (!isset($this->fields['itemtype'])
         || strlen($this->fields['itemtype']) == 0) {
         return false;
      }

      $itilobject = new $this->fields['itemtype'];
      if (!$itilobject->can($this->getField('items_id'), READ)
        // No validation for closed tickets
         || in_array($itilobject->fields['status'], $itilobject->getClosedStatusArray())
            && !$itilobject->isAllowedStatus($itilobject->fields['status'], CommonITILObject::INCOMING)) {
         return false;
      }
      return $itilobject->canAddFollowups();
   }


   function canPurgeItem() {

      $itilobject = new $this->fields['itemtype'];
      if (!$itilobject->can($this->getField('items_id'), READ)) {
         return false;
      }

      if (Session::haveRight(self::$rightname, PURGE)) {
         return true;
      }

      return false;
   }


   function canUpdateItem() {

      if (($this->fields["users_id"] != Session::getLoginUserID())
         && !Session::haveRight(self::$rightname, self::UPDATEALL)) {
         return false;
      }

      $itilobject = new $this->fields['itemtype'];
      if (!$itilobject->can($this->getField('items_id'), READ)) {
         return false;
      }

      if ($this->fields["users_id"] === Session::getLoginUserID()) {
         if (!Session::haveRight(self::$rightname, self::UPDATEMY)) {
            return false;
         }
         return true;
      }

      // Only the technician
      return (Session::haveRight(self::$rightname, self::UPDATEALL)
               || $itilobject->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                  && $itilobject->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));
   }


   function post_getEmpty() {
      if (isset($_SESSION['glpifollowup_private']) && $_SESSION['glpifollowup_private']) {
         $this->fields['is_private'] = 1;
      }

      if (isset($_SESSION["glpiname"])) {
         $this->fields['requesttypes_id'] = RequestType::getDefault('followup');
      }
   }


   function post_addItem() {

      global $CFG_GLPI;

      // Add screenshots if needed, without notification
      $this->input = $this->addFiles($this->input, [
         'force_update'  => true,
         'name'          => 'content',
         'content_field' => 'content',
      ]);

      // Add documents if needed, without notification
      $this->input = $this->addFiles($this->input, [
         'force_update'  => true,
      ]);

      $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

      // Check if stats should be computed after this change
      $no_stat = isset($this->input['_do_not_compute_takeintoaccount']);

      $parentitem = $this->input['_job'];
      $parentitem->updateDateMod(
         $this->input["items_id"],
         $no_stat,
         $this->input["users_id"]
      );

      if (isset($this->input["_close"])
         && $this->input["_close"]
         && ($parentitem->fields["status"] == CommonITILObject::SOLVED)) {

         $update = [
            'id'        => $parentitem->fields['id'],
            'status'    => CommonITILObject::CLOSED,
            'closedate' => $_SESSION["glpi_currenttime"],
            '_accepted' => true,
         ];

         // Use update method for history
         $this->input["_job"]->update($update);
         $donotif = false; // Done for ITILObject update (new status)
      }

      //manage reopening of ITILObject
      $reopened = false;
      if (!isset($this->input['_status'])) {
         $this->input['_status'] = $parentitem->fields["status"];
      }
      // if reopen set (from followup form or mailcollector)
      // and status is reopenable and not changed in form
      if (isset($this->input["_reopen"])
         && $this->input["_reopen"]
         && in_array($parentitem->fields["status"], $parentitem::getReopenableStatusArray())
         && $this->input['_status'] == $parentitem->fields["status"]) {

         if (($parentitem->countUsers(CommonITILActor::ASSIGN) > 0)
            || ($parentitem->countGroups(CommonITILActor::ASSIGN) > 0)
            || ($parentitem->countSuppliers(CommonITILActor::ASSIGN) > 0)) {
            $update['status'] = CommonITILObject::ASSIGNED;
         } else {
            $update['status'] = CommonITILObject::INCOMING;
         }

         $update['id'] = $parentitem->fields['id'];

         // Use update method for history
         $parentitem->update($update);
         $reopened     = true;
      }

      //change ITILObject status only if imput change
      if (!$reopened
            && $this->input['_status'] != $parentitem->fields['status']) {

         $update['status'] = $this->input['_status'];
         $update['id']     = $parentitem->fields['id'];

         // don't notify on ITILObject - update event
         $update['_disablenotif'] = true;

         // Use update method for history
         $parentitem->update($update);
      }

      if ($donotif) {
         $options = ['followup_id' => $this->fields["id"],
                        'is_private'  => $this->fields['is_private']];
         NotificationEvent::raiseEvent("add_followup", $parentitem, $options);
      }

      // Add log entry in the ITILObject
      $changes = [
         0,
         '',
         $this->fields['id'],
      ];
      Log::history($this->getField('items_id'), get_class($parentitem), $changes, $this->getType(),
                  Log::HISTORY_ADD_SUBITEM);
   }


   function post_deleteFromDB() {
      global $CFG_GLPI;

      $donotif = $CFG_GLPI["use_notifications"];
      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      $job = new $this->fields['itemtype'];
      $job->getFromDB($this->fields[self::$items_id]);
      $job->updateDateMod($this->fields[self::$items_id]);

      // Add log entry in the ITIL Object
      $changes = [
         0,
         '',
         $this->fields['id'],
      ];
      Log::history($this->getField(self::$items_id), $this->fields['itemtype'], $changes, $this->getType(),
                  Log::HISTORY_DELETE_SUBITEM);

      if ($donotif) {
         $options = ['followup_id' => $this->fields["id"],
                           // Force is_private with data / not available
                        'is_private'  => $this->fields['is_private']];
         NotificationEvent::raiseEvent('delete_followup', $job, $options);
      }
   }


   function prepareInputForAdd($input) {

      $input["_job"] = new $input['itemtype']();

      if (empty($input['content'])
            && !isset($input['add_close'])
            && !isset($input['add_reopen'])) {
         Session::addMessageAfterRedirect(__("You can't add a followup without description"),
                                          false, ERROR);
         return false;
      }
      if (!$input["_job"]->getFromDB($input["items_id"])) {
         return false;
      }

      $input['_close'] = 0;

      if (!isset($input["users_id"])) {
         $input["users_id"] = 0;
         if ($uid = Session::getLoginUserID()) {
            $input["users_id"] = $uid;
         }
      }
      // if ($input["_isadmin"] && $input["_type"]!="update") {
      if (isset($input["add_close"])) {
         $input['_close'] = 1;
         if (empty($input['content'])) {
            $input['content'] = __('Solution approved');
         }
      }

      unset($input["add_close"]);

      if (!isset($input["is_private"])) {
         $input['is_private'] = 0;
      }

      if (isset($input["add_reopen"])) {
         if ($input["content"] == '') {
            if (isset($input["_add"])) {
               // Reopen using add form
               Session::addMessageAfterRedirect(__('If you want to reopen this item, you must specify a reason'),
                                                false, ERROR);
            } else {
               // Refuse solution
               Session::addMessageAfterRedirect(__('If you reject the solution, you must specify a reason'),
                                                false, ERROR);
            }
            return false;
         }
         $input['_reopen'] = 1;
      }
      unset($input["add_reopen"]);
      // }
      unset($input["add"]);

      $itemtype = $input['itemtype'];
      $input['timeline_position'] = $itemtype::getTimelinePosition($input["items_id"], $this->getType(), $input["users_id"]);

      if (!isset($input['date'])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }
      return $input;
   }


   function prepareInputForUpdate($input) {
      if (!isset($this->fields['itemtype'])) {
         return false;
      }
      $input["_job"] = new $this->fields['itemtype']();
      if (!$input["_job"]->getFromDB($this->fields["items_id"])) {
         return false;
      }

      // update last editor if content change
      if (($uid = Session::getLoginUserID())
            && isset($input['content']) && ($input['content'] != $this->fields['content'])) {
         $input["users_id_editor"] = $uid;
      }

      return $input;
   }


   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      $job      = new $this->fields['itemtype']();

      if (!$job->getFromDB($this->fields['items_id'])) {
         return;
      }

      // Add screenshots if needed, without notification
      $this->input = $this->addFiles($this->input, [
         'force_update' => true,
         'name'          => 'content',
         'content_field' => 'content',
      ]);

      // Add documents if needed, without notification
      $this->input = $this->addFiles($this->input, [
         'force_update' => true,
      ]);

      //Get user_id when not logged (from mailgate)
      $uid = Session::getLoginUserID();
      if ($uid === false) {
         if (isset($this->fields['users_id_editor'])) {
            $uid = $this->fields['users_id_editor'];
         } else {
            $uid = $this->fields['users_id'];
         }
      }
      $job->updateDateMod($this->fields['items_id'], false, $uid);

      if (count($this->updates)) {
         if (!isset($this->input['_disablenotif'])
             && $CFG_GLPI["use_notifications"]
             && (in_array("content", $this->updates)
                 || isset($this->input['_need_send_mail']))) {
            //FIXME: _need_send_mail does not seems to be used

            $options = ['followup_id' => $this->fields["id"],
                             'is_private'  => $this->fields['is_private']];

            NotificationEvent::raiseEvent("update_followup", $job, $options);
         }
      }

      // change ITIL Object status (from splitted button)
      if (isset($this->input['_status'])
          && ($this->input['_status'] != $this->input['_job']->fields['status'])) {
          $update = [
             'status'        => $this->input['_status'],
             'id'            => $this->input['_job']->fields['id'],
             '_disablenotif' => true,
          ];
          $this->input['_job']->update($update);
      }

      // Add log entry in the ITIL Object
      $changes = [
         0,
         '',
         $this->fields['id'],
      ];
      Log::history($this->getField('items_id'), $this->fields['itemtype'], $changes, $this->getType(),
                   Log::HISTORY_UPDATE_SUBITEM);
   }


   function post_getFromDB() {

      $this->item = new $this->fields['itemtype'];
      $this->item->getFromDB($this->fields['items_id']);
   }


   protected function computeFriendlyName() {

      if (isset($this->fields['requesttypes_id'])) {
         if ($this->fields['requesttypes_id']) {
            return Dropdown::getDropdownName('glpi_requesttypes', $this->fields['requesttypes_id']);
         }
         return $this->getTypeName();
      }
      return '';
   }


   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'content',
         'name'               => __('Description'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_requesttypes',
         'field'              => 'name',
         'name'               => __('Request source'),
         'forcegroupby'       => true,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'date',
         'name'               => __('Date'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'is_private',
         'name'               => __('Private'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('User'),
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'itemtype',
         'name'               => __('Request source'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   static function rawSearchOptionsToAdd($itemtype = null) {

      $tab = [];

      $tab[] = [
         'id'                 => 'followup',
         'name'               => _n('Followup', 'Followups', Session::getPluralNumber())
      ];

      $followup_condition = '';
      if (!Session::haveRight('followup', self::SEEPRIVATE)) {
         $followup_condition = "AND (`NEWTABLE`.`is_private` = 0
                                     OR `NEWTABLE`.`users_id` = '".Session::getLoginUserID()."')";
      }

      $tab[] = [
         'id'                 => '25',
         'table'              => static::getTable(),
         'field'              => 'content',
         'name'               => __('Description'),
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'condition'          => $followup_condition
         ],
         'datatype'           => 'text',
         'htmltext'           => true
      ];

      $tab[] = [
         'id'                 => '36',
         'table'              => static::getTable(),
         'field'              => 'date',
         'name'               => __('Date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'condition'          => $followup_condition
         ]
      ];

      $tab[] = [
         'id'                 => '27',
         'table'              => static::getTable(),
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of followups'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'condition'          =>$followup_condition
         ]
      ];

      $tab[] = [
         'id'                 => '29',
         'table'              => 'glpi_requesttypes',
         'field'              => 'name',
         'name'               => __('Request source'),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'condition'          => $followup_condition
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '91',
         'table'              => static::getTable(),
         'field'              => 'is_private',
         'name'               => __('Private followup'),
         'datatype'           => 'bool',
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'condition'          => $followup_condition
         ]
      ];

      $tab[] = [
         'id'                 => '93',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Writer'),
         'datatype'           => 'itemlink',
         'right'              => 'all',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'condition'          => $followup_condition
               ]
            ]
         ]
      ];

      return $tab;
   }


   /**
    * form for soluce's approbation
    *
    * @param CommonITILObject $itilobject
    */
   function showApprobationForm($itilobject) {
      // print_r($itilobject);
      if (($itilobject->fields["status"] == CommonITILObject::SOLVED)
            && $itilobject->canApprove()
            && $itilobject->isAllowedStatus($itilobject->fields['status'], CommonITILObject::CLOSED)) {
         echo "<form name='form' method='post' action='".$this->getFormURL()."' class='m-form m-form--fit m-form--label-align-right '>";
         echo"<div class='form-group m-form__group row justify-content-center'>";
               echo"<div class='col-lg-8'>";
                  echo" <label>".__('Comments').": ";  echo'<span class="m-form__help">('.__("Optional when approved").')</span>'; echo"</label>";
                  echo "<textarea class='form-control m-input' name='content' cols='70' rows='6'></textarea>";
                  echo "<input type='hidden' name='itemtype' value='".$itilobject->getType()."'>";
                  echo "<input type='hidden' name='items_id' value='".$itilobject->getField('id')."'>";
                  echo "<input type='hidden' name='requesttypes_id' value='".
                        RequestType::getDefault('followup')."'>";
               echo"</div>";

               echo"<div class='col-lg-8 d-flex justify-content-center mt-1'>";
                  echo "<button type='submit' class=' btn btn-default' name='add_close' value='".__('Approve the solution')."'>";
                  echo "<i class='far fa-thumbs-up'></i>&nbsp;&nbsp;".__('Approve')."</button> &nbsp;&nbsp;";
      
                  echo "<button type='submit' class=' btn btn-default' name='add_reopen' value='".__('Refuse the solution')."'>";
                  echo "<i class='far fa-thumbs-down'></i>&nbsp;&nbsp;".__('Refuse')."</button>";
                  echo"
                  
               </div>
            </div>
            
         ";
         Html::closeForm();
      }

      return true;
   }


   static function getFormURL($full = true) {
      return Toolbox::getItemTypeFormURL("ITILFollowup", $full);
   }


   /** form for Followup
    *
    *@param $ID      integer : Id of the followup
    *@param $options array of possible options:
    *     - item Object : the ITILObject parent
   **/
   function showForm_old($ID, $options = []) {
      global $CFG_GLPI;
      
      $this->forceTable("glpi_itilfollowups");

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

     

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         //ITILFollowup
         
         $options['itemtype'] = $item->getType();
         $options['items_id'] = $item->getField('id');
         $this->check(-1, CREATE, $options);
      }
      
      $tech = (Session::haveRight(self::$rightname, self::ADDALLTICKET)
               || $item->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $item->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));

      $requester = ($item->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                    || (isset($_SESSION["glpigroups"])
                        && $item->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])));

      $reopen_case = false;
      if ($this->isNewID($ID)) {
         if ($item->canReopen()) {
            $reopen_case = true;
            echo "<div class='center b'>".__('If you want to reopen the ticket, you must specify a reason')."</div>";
         }

         // the reqester triggers the reopening on close/solve/waiting status
         if ($requester
             && in_array($item->fields['status'], $item::getReopenableStatusArray())) {
            $reopen_case = true;
         }
      }

      $cols    = 100;
      $rows    = 3;

      if ($tech) {
         $this->showFormHeader($options);

         $rand       = mt_rand();
         $content_id = "content$rand";

         echo "<tr>";
         echo "<td >";

         PluginServicesHtml::textarea(['name'              => 'content',
                        'value'             => $this->fields["content"],
                        'rand'              => $rand,
                        'editor_id'         => $content_id,
                        'enable_fileupload' => false,
                        'enable_richtext'   => false,
                        'cols'              => $cols,
                        'rows'              => $rows]);

         echo Html::hidden('itemtype', ['value' => $item->getType()]);
         echo Html::hidden('items_id', ['value' => $item->getID()]);
         // Reopen case
         if ($reopen_case) {

            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td>";
         if ($this->fields["date"]) {
            echo "</td><td>".__('Date')."</td>";
            echo "<td>".Html::convDateTime($this->fields["date"]);
         } else {

            echo "</td><td colspan='2'>&nbsp;";
         }


         echo "</tr>\n";

         echo "<tr class='tab_bg_1'></tr>";
         echo "<tr class='tab_bg_1' style='vertical-align: top'>";
                  echo "<td colspan='4'>";
                  echo "<div class='fa-label'>
                     <i class='fas fa-reply fa-fw'
                        title='"._n('Followup template', 'Followup templates', 2)."'></i>";
                  $this->fields['itilfollowuptemplates_id'] = 0;
                  ITILFollowupTemplate::dropdown([
                     'value'     => $this->fields['itilfollowuptemplates_id'],
                     'entity'    => $this->getEntityID(),
                     'on_change' => "itilfollowuptemplate_update$rand(this.value)"
                  ]);
                  echo "</div>";

                  $ajax_url = $CFG_GLPI["root_doc"]."/ajax/itilfollowup.php";
                  $JS = <<<JAVASCRIPT
                     function itilfollowuptemplate_update{$rand}(value) {
                        $.ajax({
                           url: '{$ajax_url}',
                           type: 'POST',
                           data: {
                              itilfollowuptemplates_id: value
                           }
                        }).done(function(data) {
                           var requesttypes_id = isNaN(parseInt(data.requesttypes_id))
                              ? 0
                              : parseInt(data.requesttypes_id);

                           // set textarea content
                           if (tasktinymce = tinymce.get("{$content_id}")) {
                              tasktinymce.setContent(data.content);
                           }
                           // set category
                           $("#dropdown_requesttypes_id{$rand}").trigger("setValue", requesttypes_id);
                           // set is_private
                           $("#is_privateswitch{$rand}")
                              .prop("checked", data.is_private == "0"
                                 ? false
                                 : true);
                        });
                     }
         JAVASCRIPT;
                  echo Html::scriptBlock($JS);

                  echo "<div class='fa-label'>
                     <i class='fas fa-inbox fa-fw'
                        title='".__('Source of followup')."'></i>";
                  RequestType::dropdown([
                     'value'     => $this->fields["requesttypes_id"],
                     'condition' => ['is_active' => 1, 'is_itilfollowup' => 1],
                     'rand'      => $rand,
                  ]);
                  echo "</div>";

                  echo "<div class='fa-label'>
                     <i class='fas fa-lock fa-fw' title='".__('Private')."'></i>";
                  echo "<span class='switch pager_controls'>
                     <label for='is_privateswitch$rand' title='".__('Private')."'>
                        <input type='hidden' name='is_private' value='0'>
                        <input type='checkbox' id='is_privateswitch$rand' name='is_private' value='1'".
                              ($this->fields["is_private"]
                                 ? "checked='checked'"
                                 : "")."
                        >
                        <span class='lever'></span>
                     </label>
                  </span>";
                  echo "</div></td>";
         echo"</tr>";

        $this->showFormButtons($options);

      } else {
         $options['colspan'] = 1;

         $this->showFormHeader($options);

         $rand = mt_rand();
         $rand_text = mt_rand();
         $content_id = "content$rand";
         echo "<tr class='tab_bg_1'>";
         // echo "<td class='middle right'>".__('Description')."</td>";
         echo "<td class='center middle'>";

         Html::textarea(['name'              => 'content',
                        'value'             => $this->fields["content"],
                        'rand'              => $rand_text,
                        'editor_id'         => $content_id,
                        'enable_fileupload' => false,
                        'enable_richtext'   => false,
                        'cols'              => $cols,
                        'rows'              => $rows]);

         echo Html::hidden('itemtype', ['value' => $item->getType()]);
         echo Html::hidden('items_id', ['value' => $item->getID()]);
         echo Html::hidden('requesttypes_id', ['value' => RequestType::getDefault('followup')]);
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         $this->showFormButtons($options);
      }
      return true;
   }

   public function showForm($ID, $options = []) {
         global $CFG_GLPI;
         
         $this->forceTable("glpi_itilfollowups");

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

         

         if ($ID > 0) {
            $this->check($ID, READ);
         } else {
            // Create item
            //ITILFollowup
            
            $options['itemtype'] = $item->getType();
            $options['items_id'] = $item->getField('id');
            $this->check(-1, CREATE, $options);
         }
         
         $tech = (Session::haveRight(self::$rightname, self::ADDALLTICKET)
                  || $item->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                  || (isset($_SESSION["glpigroups"])
                     && $item->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));

         $requester = ($item->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                     || (isset($_SESSION["glpigroups"])
                           && $item->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])));
   
         $reopen_case = false;
         if ($this->isNewID($ID)) {
            if ($item->canReopen()) {
               $reopen_case = true;
               echo "<div class='center b'>".__('If you want to reopen the ticket, you must specify a reason')."</div>";
            }

            // the reqester triggers the reopening on close/solve/waiting status
            if ($requester
               && in_array($item->fields['status'], $item::getReopenableStatusArray())) {
               $reopen_case = true;
            }
         }



         if ($tech) {
            $this->showFormHeader($options);
            $rand       = mt_rand();
            $content_id = "content$rand";
            PluginServicesHtml::textarea([
               'name'  =>'content',
               'value'  => $this->fields["content"],
               'rows'  => 3,
               'required'  => true,
            ]);
            echo Html::hidden('itemtype', ['value' => $item->getType()]);
            echo Html::hidden('items_id', ['value' => $item->getID()]);
            // Reopen case
            if ($reopen_case) {
               echo "<input type='hidden' name='add_reopen' value='1'>";
            }
            
            echo"<div class='d-flex justify-content-end mt-2'>";
               echo "<div class='fa-label mr-2'>";
                     echo"<label  for='is_privateswitch$rand' title='".__('Private')."' style='padding-left: 22px;' class='m-checkbox mt-2'>
                     <input type='hidden' name='is_private' value='0'>
                     <input type='checkbox' id='is_privateswitch$rand' name='is_private' value='1'".
                     ($this->fields["is_private"]
                        ? "checked='checked'"
                        : "").">
                        Work notes<span></span>
                  </label>";
               echo "</div>";
               echo "<div class='ml-2'>";
                  $this->showFormButtons($options);
               echo"</div>";
            echo"</div>";
         } else {
            $this->showFormHeader($options);
            PluginServicesHtml::textarea([
               'name'  =>'content',
               'value'  => $this->fields["content"],
               'rows'  => 5,
               'required'  => true,
            ]);
            echo Html::hidden('itemtype', ['value' => $item->getType()]);
            echo Html::hidden('items_id', ['value' => $item->getID()]);
            echo Html::hidden('requesttypes_id', ['value' => RequestType::getDefault('followup')]);
            // Reopen case
            if ($reopen_case) {
               echo "<input type='hidden' name='add_reopen' value='1'>";
            }

            echo"<div class='d-flex justify-content-center'>";
               echo"<div class='p-2 bd-highlight'>";
                  $this->showFormButtons($options);
               echo"</div>";
            echo"</div>";
   
         }
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

   function showFormButtons($options = []) {

      // for single object like config
      $ID = 1;
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      }

      $params = [
         'colspan'  => 2,
         'candel'   => true,
         'canedit'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!$this->isNewID($ID)) {
         echo "<input type='hidden' name='id' value='$ID'>";
      }

      Plugin::doHook("post_item_form", ['item' => $this, 'options' => &$params]);

   
      if ($this->isNewID($ID)) {
         echo static::getSplittedSubmitButtonHtml($this->fields['items_id'], 'add');
      } else {
         if ($params['candel']
            && !$this->can($ID, DELETE)
            && !$this->can($ID, PURGE)) {
            $params['candel'] = false;
         }

         if ($params['canedit'] && $this->can($ID, UPDATE)) {
            echo static::getSplittedSubmitButtonHtml($this->fields['items_id'], 'update');
         }

         if ($params['candel']) {
            if ($this->can($ID, PURGE)) {
               echo PluginServicesHtml::submit(_x('button', 'Delete permanently'),
                                 ['name'    => 'purge',
                                       'confirm' => __('Confirm the final deletion?')]);
            }
         }

         if ($this->isField('date_mod')) {
            echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
         }
      }

      Html::closeForm();
   }


   static function getSplittedSubmitButtonHtml($items_id, $action = "add") {
      self::forceTable("glpi_tickets");

      $locale = _sx('button', 'Post');
      $icone = 'fa-plus-circle';
      if ($action == 'update') {
         $locale = _x('button', 'Save');
         $icone = ' fa-edit';
      }
      $rand = mt_rand();
      $html = "
         <button   value='$locale' name='$action' class=' btn-sm btn btn-secondary m-btn m-btn--icon'>
         $locale  
         </button>
      ";
      // $html = "<button type='submit' value='$locale' name='$action' class='btn btn-success  btn-sm'>$locale</button>";
      

      $html.= "<script type='text/javascript'>$(function() {split_button();});</script>";
      return $html;
   }


   /**
    * @param $ID  integer  ID of the ITILObject
    * @param $itemtype  string   parent itemtype
   **/
   static function showShortForITILObject($ID, $itemtype) {

      global $DB, $CFG_GLPI;

      // Print Followups for a job
      $showprivate = Session::haveRight(self::$rightname, self::SEEPRIVATE);

      $where = [
         'itemtype'  => $itemtype,
         'items_id'  => $ID
      ];
      if (!$showprivate) {
         $where['OR'] = [
            'is_private'   => 0,
            'users_id'     => Session::getLoginUserID()
         ];
      }

      // Get Followups
      $iterator = $DB->request([
         'FROM'   => 'glpi_itilfollowups',
         'WHERE'  => $where,
         'ORDER'  => 'date DESC'
      ]);

      $out = "";
      if (count($iterator)) {
         $out .= "<div class='center'><table class='tab_cadre' width='100%'>\n
                  <tr><th>".__('Date')."</th><th>".__('Requester')."</th>
                  <th>".__('Description')."</th></tr>\n";

         $showuserlink = 0;
         if (Session::haveRight('user', READ)) {
            $showuserlink = 1;
         }
         while ($data = $iterator->next()) {
            $out .= "<tr class='tab_bg_3'>
                     <td class='center'>".Html::convDateTime($data["date"])."</td>
                     <td class='center'>".getUserName($data["users_id"], $showuserlink)."</td>
                     <td width='70%' class='b'>".Html::resume_text($data["content"],
                                                                   $CFG_GLPI["cut"])."
                     </td></tr>";
         }
         $out .= "</table></div>";
      }
      return $out;
   }


   /**
    * @since 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[UPDATE], $values[CREATE], $values[READ]);

      if ($interface == 'central') {
         $values[self::UPDATEALL]      = __('Update all');
         $values[self::ADDALLTICKET]   = __('Add to all tickets');
         $values[self::SEEPRIVATE]     = __('See private ones');
      }

      $values[self::ADDGROUPTICKET]
                                 = ['short' => __('Add followup (associated groups)'),
                                         'long'  => __('Add a followup to tickets of associated groups')];
      $values[self::UPDATEMY]    = __('Update followups (author)');
      $values[self::ADDMYTICKET] = ['short' => __('Add followup (requester)'),
                                         'long'  => __('Add a followup to tickets (requester)')];
      $values[self::SEEPUBLIC]   = __('See public ones');

      if ($interface == 'helpdesk') {
         unset($values[PURGE]);
      }

      return $values;
   }

   static function showMassiveActionAddFollowupForm() {
      echo "<table class='tab_cadre_fixe'>";
      echo '<tr><th colspan=4>'.__('Add a new followup').'</th></tr>';

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Source of followup')."</td>";
      echo "<td>";
      RequestType::dropdown(
         [
            'value' => RequestType::getDefault('followup'),
            'condition' => ['is_active' => 1, 'is_itilfollowup' => 1]
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Description')."</td>";
      echo "<td><textarea name='content' cols='50' rows='6'></textarea></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>";
      echo "<input type='hidden' name='is_private' value='".$_SESSION['glpifollowup_private']."'>";
      echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
   }

   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'add_followup' :
            static::showMassiveActionAddFollowupForm();
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }

   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      switch ($ma->getAction()) {
         case 'add_followup' :
            $input = $ma->getInput();
            $fup   = new self();
            foreach ($ids as $id) {
               if ($item->getFromDB($id)) {
                  $input2 = [
                     'items_id'        => $id,
                     'itemtype'        => $item->getType(),
                     'is_private'      => $input['is_private'],
                     'requesttypes_id' => $input['requesttypes_id'],
                     'content'         => $input['content']
                  ];
                  if ($fup->can(-1, CREATE, $input2)) {
                     if ($fup->add($input2)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
               }
            }
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   /**
    * Build parent condition for ITILFollowup, used in addDefaultWhere
    *
    * @param string $itemtype
    * @param string $target
    * @param string $user_table
    * @param string $group_table keys
    *
    * @return string
    *
    * @throws InvalidArgumentException
    */
   public static function buildParentCondition(
      $itemtype,
      $target = "",
      $user_table = "",
      $group_table = ""
   ) {
      // An ITILFollowup parent can only by a CommonItilObject
      if (!is_a($itemtype, "CommonITILObject", true)) {
         throw new InvalidArgumentException(
            "'$itemtype' is not a CommonITILObject"
         );
      }

      $rightname = $itemtype::$rightname;
      // Can see all items, no need to go further
      if (Session::haveRight($rightname, $itemtype::READALL)) {
         return "(`itemtype` = '$itemtype') ";
      }

      $user   = Session::getLoginUserID();
      $groups = "'" . implode("','", $_SESSION['glpigroups']) . "'";
      $table = getTableNameForForeignKeyField(
         getForeignKeyFieldForItemType($itemtype)
      );

      // Avoid empty IN ()
      if ($groups == "''") {
         $groups = '-1';
      }

      // We need to do some specific checks for tickets
      if ($itemtype == "Ticket") {
         // Default condition
         $condition = "(`itemtype` = '$itemtype' AND (0 = 1 ";
         return $condition . Ticket::buildCanViewCondition("items_id") . ")) ";
      } else {
         if (Session::haveRight($rightname, $itemtype::READMY)) {
            // Subquery for affected/assigned/observer user
            $user_query = "SELECT `$target`
               FROM `$user_table`
               WHERE `users_id` = '$user'";

            // Subquery for affected/assigned/observer group
            $group_query = "SELECT `$target`
               FROM `$group_table`
               WHERE `groups_id` IN ($groups)";

            // Subquery for recipient
            $recipient_query = "SELECT `id`
               FROM `$table`
               WHERE `users_id_recipient` = '$user'";

            return "(
               `itemtype` = '$itemtype' AND (
                  `items_id` IN ($user_query) OR
                  `items_id` IN ($group_query) OR
                  `items_id` IN ($recipient_query)
               )
            ) ";
         } else {
            // Can't see any items
            return "(`itemtype` = '$itemtype' AND 0 = 1) ";
         }
      }
   }

   public static function getNameField() {
      return 'id';
   }

   /**
    * Check if this item author is a support agent
    *
    * @return bool
    */
   public function isFromSupportAgent() {
      // Get parent item
      $commonITILObject = new $this->fields['itemtype']();
      $commonITILObject->getFromDB($this->fields['items_id']);

      $actors = $commonITILObject->getITILActors();
      $user_id = $this->fields['users_id'];
      $roles = $actors[$user_id] ?? [];

      if (in_array(CommonITILActor::ASSIGN, $roles)) {
         // The author is assigned -> support agent
         return true;
      } else if (in_array(CommonITILActor::OBSERVER, $roles)
         || in_array(CommonITILActor::REQUESTER, $roles)
      ) {
         // The author is an observer or a requester -> not a support agent
         return false;
      } else {
         // The author is not an actor of the ticket -> he was most likely a
         // support agent that is no longer assigned to the ticket
         return true;
      }
   }


function showFormHeader($options = []) {
       
    $ID   = $this->fields['id'];
    $rand = mt_rand();

    //if (isset($options['template_preview']) && !$options['template_preview']) {
       $output = "<form method='post' id='mainform' class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed' name='form_ticket' enctype='multipart/form-data' action='".static::getFormURL()."''";
       if ($ID) {
          $output .= "data-track-changes='true'";
       }
       $output .= '>';
       echo $output;

       if (isset($options['_projecttasks_id'])) {
          echo "<input type='hidden' name='_projecttasks_id' value='".$options['_projecttasks_id']."'>";
       }
       if (isset($this->fields['_tasktemplates_id'])) {
          foreach ($this->fields['_tasktemplates_id'] as $tasktemplates_id) {
             echo "<input type='hidden' name='_tasktemplates_id[]' value='$tasktemplates_id'>";
          }
       }
    //}
   //  echo "<div class='spaced' id='tabsbody'>";

   //  echo "<table class='tab_cadre_fixe' id='mainformtable'>";

    // Optional line
    // $ismultientities = Session::isMultiEntitiesMode();
    // echo "<tr class='headerRow responsive_hidden'>";
    // echo "<th colspan='4'>";

    // if ($ID) {
    //    $text = sprintf(__('%1$s - ID %2$d'), $this->getTypeName(1), $ID);
    //    if ($ismultientities) {
    //       $text = sprintf(__('%1$s (%2$s)'), $text,
    //                       Dropdown::getDropdownName('glpi_entities',
    //                                                 $this->fields['entities_id']));
    //    }
    //    echo $text;
    // } else {
    //    if ($ismultientities) {
    //       printf(
    //          __('The %s will be added in the entity %s'),
    //          strtolower(static::getTypeName()),
    //          Dropdown::getDropdownName("glpi_entities", $this->fields['entities_id'])
    //       );
    //    } else {
    //       echo sprintf(
    //          __('New %s'),
    //          strtolower(static::getTypeName())
    //       );
    //    }
    // }

    // if ($this->maybeRecursive()) {
    //    echo "&nbsp;<label for='dropdown_is_recursive$rand'>".__('Child entities')."</label>&nbsp;";
    //    Dropdown::showYesNo("is_recursive", $this->fields["is_recursive"], -1, ['rand' => $rand]);
    // }
    // echo "</th>";
    // echo "</tr>";

    Plugin::doHook("pre_item_form", ['item' => $this, 'options' => &$options]);
 }

}
