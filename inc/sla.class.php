<?php


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SLA Class
**/

use Glpi\Event;

class PluginServicesSla extends SLA {
   static protected $prefix            = 'sla';
   static protected $prefixticket      = '';
   static protected $levelclass        = 'SLALevel';
   static protected $levelticketclass  = 'SlaLevel_Ticket';
   static protected $forward_entity_to = ['SLALevel'];

   static $rightname                   = 'plugin_services_sla';

   static function getTypeName($nb = 0) {
      // Acronymous, no plural
      return __('SLA');
   }

   static function getType() {
      return "SLA";
   }

   static function getClassName() {
      return get_called_class();
   }

   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRight(self::$rightname, CREATE);
   }

   static function canUpdate() {
      return Session::haveRightsOr(self::$rightname, [UPDATE]);
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

   static function getFormURL($full = false) {
      return PluginServicesToolbox::getItemTypeFormURL(get_called_class(), $full);
   }

   function getAddConfirmation() {
      return [__("The assignment of a SLA to a ticket causes the recalculation of the date."),
            __("Escalations defined in the SLA will be triggered under this new date.")];
   }

   static function getTable($classname = null) {
      return "glpi_slas";
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {


      if ($item->getType() == "SLA") {
         if (Session::getCurrentInterface() != "helpdesk" ) {
            return [
               self::createTabEntry(__("Niveaux d'escalade")),
            ];
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == "SLA") {
         switch ($tabnum) {
            case 0:
               $slalevel = new PluginAssistancesSlalevel();
               $slalevel->showForParent($item);
            break;
         }
      }

      return true;
   }


   function postForm($post) {
      Session::checkRight("slm", READ);

      if (empty($_GET["id"])) {
         $_GET["id"] = "";
      }

      $sla = new SLA();

      if (isset($_POST["add"])) {
         $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, 'SLM');
         $sla->check(-1, CREATE, $_POST);

         if ($newID = $sla->add($_POST)) {
            Event::log($newID, "slas", 4, "setup",
                     sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
            PluginServicesFagoUtils::returnResponse($newID);
         }
         PluginServicesFagoUtils::returnResponse();
      } else if (isset($_POST["purge"])) {
         $backurl = PluginServicesToolbox::getFormURLWithID($post['slms_id'], true, 'SLM');
         $sla->check($_POST["id"], PURGE);
         $sla->delete($_POST, 1);

         Event::log($_POST["id"], "slas", 4, "setup",
                  //TRANS: %s is the user login
                  sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
         Html::redirect($backurl);

      } else if (isset($_POST["update"])) {
         $backurl = PluginServicesToolbox::getFormURLWithID($post['slms_id'], true, 'SLM');
         $sla->check($_POST["id"], UPDATE);
         $sla->update($_POST);

         Event::log($_POST["id"], "slas", 4, "setup",
                  //TRANS: %s is the user login
                  sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
         PluginServicesFagoUtils::returnResponse();
      }
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
         $extraparamPluginServicesHtml = "";
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
               // $extraparamPluginServicesHtml can be tool long in case of ticket with content
               // (passed in GET in ajax request)
               unset($cleaned_options['content']);
            }
            // prevent double sanitize, because the includes.php sanitize all data
            $cleaned_options = Toolbox::stripslashes_deep($cleaned_options);

            $extraparamPluginServicesHtml = "&amp;".Toolbox::append_params($cleaned_options, '&amp;');
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
                                             "&amp;_glpi_tab=$key&amp;id=$ID$extraparamPluginServicesHtml"];
            }

            // Not all tab for templates and if only 1 tab
            if ($display_all
               && empty($withtemplate)
               && (count($tabs) > 1)) {
               $tabs[-1] = ['title'  => __('All'),
                                 'url'    => $tabpage,
                                 'params' => "_target=$target&amp;_itemtype=".$this->getType().
                                             "&amp;_glpi_tab=-1&amp;id=$ID$extraparamPluginServicesHtml"];
            }

            PluginServicesAjax::createTabs('tabspanel', 'tabcontent', $tabs, $this->getType(), $ID,
                           "horizontal", $options);
         }
         echo "</div>";
   }

   public function defineAllTabsFago($options = []) {
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
          // $this->showDates($params);
      }

      if (!$params['canedit']
            || !$this->canEdit($ID)) {
         echo "</table></div>";
         // Form Header always open form
         Html::closeForm();
         return false;
      }

      echo "<tr class='tab_bg_2'>";

      if ($params['withtemplate']
            ||$this->isNewID($ID)) {

         echo "<td class='center' colspan='".($params['colspan']*2)."'>";

         if (($ID <= 0) || ($params['withtemplate'] == 2)) {
            echo PluginServicesHtml::submit(
                  _x('button', 'Soumettre'),
               ['name' => 'add']
            );
         } else {
            //TRANS : means update / actualize
            echo PluginServicesHtml::submit(
                  _x('button', 'Soumettre'),
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
            echo PluginServicesHtml::submit(
                  _x('button', 'Soumettre'),
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
                  echo PluginServicesHtml::submit(
                     _x('button', 'Restore'),
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
                     _x('button', 'Delete permanently'),
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
                     echo PluginServicesHtml::submit(
                        _x('button', 'Delete permanently'),
                        [
                           'name'    => 'purge',
                           'confirm' => __('Confirm the final deletion?')
                        ]
                     );
                  }
               } else if (!$this->isDeleted()
                           && $this->can($ID, DELETE)) {
                  echo PluginServicesHtml::submit(
                     _x('button', 'Put in trashbin'),
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
      Html::closeForm();
   }

   function showForm($ID, $options = []) {
      $rowspan = 3;
      if ($ID > 0) {
         $rowspan = 5;
      }

      // Get SLM object
      $slm = new SLM();
      if (isset($options['parent'])) {
         $slm = $options['parent'];
      } else {
         $slm->getFromDB((isset($_GET['parentid'])) ? $_GET['parentid'] : $_GET['id']);
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[static::$items_id] = $slm->getField('id');

         //force itemtype of parent
         static::$itemtype = get_class($slm);

         $this->check(-1, CREATE, $options);
      }

      $this->showFormHeader($options);

      echo'<div class="m-content">';
         echo'<div class="row">';
            echo'<div class="col-lg-12">';
               echo'<div class="m-portlet">';
                  echo'<div class="m-portlet__head">';
                     echo'<div class="m-portlet__head-caption">';
                     echo'<div class="m-portlet__head-title">';
                     echo'</div>';
                     echo'</div>';
                     echo'<div class="m-portlet__head-tools" id="m-portlet-head">';
                     echo'</div>';
                  echo'</div>';

                  echo'<div class="m-portlet__body">';
                     echo'<div style="padding-top: 10px;" class="row">';
                        echo'<div  style="display: table;" class="col-sm-6 mb-3">';
                           echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                              echo __('Name');
                           echo'</label>';
                           echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                              PluginServicesHtml::autocompletionTextField($this, "name", ['value' => $this->fields["name"]]);
                           echo'</div>';
                           echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                           echo'</div>';
                        echo'</div>';

                        echo'<div  style="display: table;" class="col-sm-6 mb-3">';
                           echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                              echo __('SLM');
                           echo'</label>';
                           echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                              PluginServicesDropdown::show('SLM', ['value'  => $this->fields['slms_id'], 'name' => 'slms_id', 'disabled' => true]);
                              echo "<input type='hidden' name='slms_id' value='".$this->fields['slms_id']."'>";
                           echo'</div>';
                           echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                           echo'</div>';
                        echo'</div>';

                        echo'<div  style="display: table;" class="col-sm-6 mb-3">';
                           echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                              echo _n('Type', 'Types', 1);
                           echo'</label>';
                           echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                              self::getTypeDropdown(['value' => $this->fields["type"]]);
                           echo'</div>';
                           echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                           echo'</div>';
                        echo'</div>';

                        echo'<div  style="display: table;" class="col-sm-6 mb-3">';
                           echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                              echo __('Maximum time');
                           echo'</label>';
                           echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                              pluginServicesDropdown::showNumber("number_time", ['value' => $this->fields["number_time"], 'min'   => 0, 'width' => '40%']);
                              echo'&nbsp;';

                              $possible_values = ['minute' => _n('Minute', 'Minutes', Session::getPluralNumber()),
                              'hour'   => _n('Hour', 'Hours', Session::getPluralNumber()),
                              'day'    => _n('Day', 'Days', Session::getPluralNumber())];
                              $rand = pluginServicesDropdown::showFromArray('definition_time', $possible_values, [
                                                                           'value' => $this->fields["definition_time"],
                                                                           'width' => '50%',
                                                                           'on_change' => 'appearhideendofworking()']);
                              
                              echo "\n<script type='text/javascript'>\n";
                                 echo "function appearhideendofworking() {\n";
                                       echo "if ($('#dropdown_definition_time$rand option:selected').val() === 'day') {
                                                $('#dropdown_endworkingday').css('display', 'table');
                                             } else {
                                                $('#dropdown_endworkingday').css('display', 'none');
                                             }";
                                 echo "}\n";
                                 echo "appearhideendofworking();\n";
                              echo "</script>\n";
                           echo'</div>';
                           echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                           echo'</div>';
                        echo'</div>';

                        echo'<div  style="display: none;" class="col-sm-6 mb-3" id="dropdown_endworkingday">';
                           echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                              echo __('End of working day');
                           echo'</label>';
                           echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                              pluginServicesDropdown::showYesNo("end_of_working_day", $this->fields["end_of_working_day"]);
                           echo'</div>';
                           echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                           echo'</div>';
                        echo'</div>';
                  
                        echo'<div  style="display: table;" class="col-sm-12 mb-3" >';
                           echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0px; padding-top: 7px;" class="col-xs-12 col-md-1_5 col-lg-2 control-label">'; 
                              echo __('Comments');
                           echo'</label>';
                           echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;" class="col-xs-10 col-md-8 col-lg-8">';
                              echo'<textarea cols="100" rows="6" name="comment" class="form-control" >'.$this->fields["comment"].'</textarea>';
                           echo'</div>';
                           echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                           echo'</div>';
                        echo'</div>';
                     echo'</div>';
                  echo'</div>';
               echo'<div class="m-portlet__foot m-portlet__no-border m-portlet__foot--fit">
                     <div class="m-form__actions d-flex justify-content-end">';
                     $this->showFormButtons($options);                    
                     echo'
                     </div>
                  </div>
               ';
               echo'</div>';
            echo'</div>';
         echo'</div>';
      echo'</div>';

      echo "
         <style>
            .col-lg-8 {
               max-width: 71.4%;
            }
            .col-lg-2 {
               max-width: 16.4%;
            }
            .col-lg-9 {
               max-width: 73.4%;
            }
         </style>
      ";
      return true;
   }

   /**
    * Get SLA types dropdown
    *
    * @param array $options
    *
    * @return string
    */
   static function getTypeDropdown($options = []) {

      $params = ['name'  => 'type'];

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      return PluginServicesDropdown::showFromArray($params['name'], self::getTypes(), $options);
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

      return Dropdown::showFromArray($p['name'], $elements, $p);
   }

   public function showList($itemtype, $params){

      global $CFG_GLPI;
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
                                    SLA
                              </h3>
                           </div>
                        </div>

                        <div class="m-portlet__head-tools">
                           <ul class="m-portlet__nav">
                              <li class="m-portlet__nav-item">
                                 <a href="'.$CFG_GLPI['root_doc'].'/sla/form" class="btn btn-secondary btn-lg m-btn m-btn--icon m-btn--icon-only bg-light">
                                    <i class="flaticon-add-circular-button"></i>
                                 </a>
                              </li>
                           </ul>
                        </div>
                     </div>
                     <div class="m-portlet__body">';
                        PluginServicesSearch::showFago("SLA", $params);
                        echo'  
                     </div>
                  </div>
               </div>
         </div>
      </div>';
   }

   static function showForSLM(SLM $slm) {
      global $CFG_GLPI;

      if (!$slm->can($slm->fields['id'], READ)) {
         return false;
      }

      $instID   = $slm->fields['id'];
      $la       = new static();
      $calendar = new Calendar();
      $rand     = mt_rand();
      $canedit  = ($slm->canEdit($instID)
                   && Session::getCurrentInterface() == "central");
      // To add a new element (SLA)
      if ($canedit) {
         echo "<div id='showLa$instID$rand'></div>\n";

         echo "<script type='text/javascript' >";
         echo "function viewAddLa$instID$rand() {";
         $params = ['type'                     => $la->getType(),
                    'parenttype'               => $slm->getType(),
                    $slm->getForeignKeyField() => $instID,
                    'id'                       => -1];
         Ajax::updateItemJsCode("showLa$instID$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "}";
         echo "</script>";
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='javascript:viewAddLa$instID$rand();'>";
         echo __('Add a new item')."</a></div>\n";
      }

      // list
      $laList = $la->find(['slms_id' => $instID]);
      Session::initNavigateListItems("__CLASS__",
                                     sprintf(__('%1$s = %2$s'),
                                             $slm::getTypeName(1),
                                             $slm->getName()));
      echo "<div class='spaced'>";
      if (count($laList)) {
         if ($canedit) {
            PluginServicesPluginServicesHtml::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['container' => 'mass'.__CLASS__.$rand];
            PluginServicesPluginServicesHtml::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_top .= "<th width='10'>".PluginServicesPluginServicesHtml::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_top .= "</th>";
            $header_bottom .= "<th width='10'>".PluginServicesPluginServicesHtml::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= "</th>";
         }
         
         $header_end .= "<th>".__('Name')."</th>";
         $header_end .= "<th>"._n('Type', 'Types', 1)."</th>";
         $header_end .= "<th>".__('Maximum time')."</th>";
         $header_end .= "<th>"._n('Calendar', 'Calendars', 1)."</th>";

         echo $header_begin.$header_top.$header_end;
         foreach ($laList as $val) {
            $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditLa".
                        $instID.$val["id"]."$rand();\""
                        : '');
            echo "<script type='text/javascript' >";
            echo "function viewEditLa".$instID.$val["id"]."$rand() {";
            $params = ['type'                     => $la->getType(),
                       'parenttype'               => $slm->getType(),
                       $slm->getForeignKeyField() => $instID,
                       'id'                       => $val["id"]];
            Ajax::updateItemJsCode("showLa$instID$rand",
                                   $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
            echo "};";
            echo "</script>\n";

            echo "<tr class='tab_bg_1'>";
            echo "<td width='10' $edit>";
            if ($canedit) {
               PluginServicesPluginServicesHtml::showMassiveActionCheckBox($la->getType(), $val['id']);
            }
            echo "</td>";
            $la->getFromDB($val['id']);
            echo "<td $edit>".$la->getLink()."</td>";
            echo "<td $edit>".$la->getSpecificValueToDisplay('type', $la->fields['type'])."</td>";
            echo "<td $edit>";
            echo $la->getSpecificValueToDisplay('number_time',
                  ['number_time'     => $la->fields['number_time'],
                   'definition_time' => $la->fields['definition_time']]);
            echo "</td>";
            if (!$slm->fields['calendars_id']) {
               $link =  __('24/7');
            } else if ($slm->fields['calendars_id'] == -1) {
               $link = __('Calendar of the ticket');
            } else if ($calendar->getFromDB($slm->fields['calendars_id'])) {
               $link = $calendar->getLink();
            }
            echo "<td $edit>".$link."</td>";
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            PluginServicesPluginServicesHtml::showMassiveActions($massiveactionparams);
            PluginServicesPluginServicesHtml::closeForm();
         }
      } else {
         echo __('No item to display');
      }
      echo "</div>";
   }
   
   function showForTicket(Ticket $ticket, $type, $tt, $canupdate) {
      list($dateField, $laField) = static::getFieldNames($type);
      $rand = mt_rand();
      $pre  = static::$prefix;
      // echo "<table width='100%'>";
      // echo "<tr class='tab_bg_1'>";

         if (!isset($ticket->fields[$dateField]) || $ticket->fields[$dateField] == 'NULL') {
            $ticket->fields[$dateField]='';
         }

         if ($ticket->fields['id']) {
            if ($this->getDataForTicket($ticket->fields['id'], $type)) {
               echo "<td style='width: 105px'>";
               echo $tt->getBeginHiddenFieldValue($dateField);
               echo PluginServicesHtml::convDateTime($ticket->fields[$dateField]);
               echo $tt->getEndHiddenFieldValue($dateField, $ticket);
               echo "</td>";
               echo "<td>";
               echo $tt->getBeginHiddenFieldText($laField);
               echo "<i class='fas fa-stopwatch slt'></i>";
               echo Dropdown::getDropdownName(static::getTable(),
                                             $ticket->fields[$laField])."&nbsp;";
               echo PluginServicesHtml::hidden($laField, ['value' => $ticket->fields[$laField]]);
               $obj = new static();
               $obj->getFromDB($ticket->fields[$laField]);
               $comment = isset($obj->fields['comment']) ? $obj->fields['comment'] : '';
               $level      = new static::$levelclass();
               $nextaction = new static::$levelticketclass();
               if ($nextaction->getFromDBForTicket($ticket->fields["id"], $type)) {
                  $comment .= '<br/><span class="b spaced">'.
                              sprintf(__('Next escalation: %s'),
                                       PluginServicesHtml::convDateTime($nextaction->fields['date'])).
                              '</span><br>';
                  if ($level->getFromDB($nextaction->fields[$pre.'levels_id'])) {
                     $comment .= '<span class="b spaced">'.
                                 sprintf(__('%1$s: %2$s'), _n('Escalation level', 'Escalation levels', 1),
                                          $level->getName()).
                                 '</span>';
                  }
               }

               $options = [];
               if (Session::haveRight('slm', READ)) {
                  $options['link'] = $this->getLinkURL();
               }
               PluginServicesHtml::showToolTip($comment, $options);
               if ($canupdate) {
                  $delete_field = strtolower(get_called_class())."_delete";
                  $fields = [$delete_field       => $delete_field,
                           'id'                => $ticket->getID(),
                           'type'              => $type,
                           '_glpi_csrf_token'  => Session::getNewCSRFToken(),
                           '_glpi_simple_form' => 1];
                  $ticket_url = $ticket->getFormURL();
                  echo PluginServicesHtml::scriptBlock("
                  function delete_date$type$rand(e) {
                     e.preventDefault();

                     if (nativeConfirm('".addslashes(__('Also delete date?'))."')) {
                        submitGetLink('$ticket_url',
                                    ".json_encode(array_merge($fields, ['delete_date' => 1])).");
                     } else {
                        submitGetLink('$ticket_url',
                                    ".json_encode(array_merge($fields, ['delete_date' => 0])).");
                     }
                  }");
                  echo "<a class='fa fa-times-circle pointer'
                           onclick='delete_date$type$rand(event)'
                           title='"._sx('button', 'Delete permanently')."'>";
                  echo "<span class='sr-only'>"._x('button', 'Delete permanently')."</span>";
                  echo "</a>";
               }
               echo $tt->getEndHiddenFieldText($laField);
               echo "</td>";

            } else {
               echo "<td width='200px'>";
               echo $tt->getBeginHiddenFieldValue($dateField);
               echo "<span class='assign_la'>";
               if ($canupdate) {
                  PluginServicesHtml::showDateTimeField($dateField, ['value'      => $ticket->fields[$dateField],
                                                      'maybeempty' => true]);
               } else {
                  echo PluginServicesHtml::convDateTime($ticket->fields[$dateField]);
               }
               echo "</span>";
               echo $tt->getEndHiddenFieldValue($dateField, $ticket);
               $data     = $this->find(
                  ['type' => $type] + getEntitiesRestrictCriteria('', '', $ticket->fields['entities_id'], true)
               );
               if ($canupdate
                  && !empty($data)) {
                  echo $tt->getBeginHiddenFieldText($laField);
                  echo "<span id='la_action$type$rand' class='assign_la'>";
                  echo "<a ".PluginServicesHtml::addConfirmationOnAction($this->getAddConfirmation(),
                           "cleanhide('la_action$type$rand');cleandisplay('la_choice$type$rand');").
                     " class='pointer' title='".static::getTypeName()."'>
                     <i class='fas fa-stopwatch slt'></i></a>";
                  echo "</span>";
                  echo "<span id='la_choice$type$rand' style='display:none' class='assign_la'>";
                  echo "<i class='fas fa-stopwatch slt'></i>";
                  echo "<span class='b'>".static::getTypeName()."</span>&nbsp;";
                  static::dropdown([
                     'name'      => $laField,
                     'entity'    => $ticket->fields["entities_id"],
                     'condition' => ['type' => $type]
                  ]);
                  echo "</span>";
                  echo $tt->getEndHiddenFieldText($laField);
               }
               echo "</td>";
            }

         } else { // New Ticket
            echo "<td>";
            echo $tt->getBeginHiddenFieldValue($dateField);
            PluginServicesHtml::showDateTimeField($dateField, ['value'      => $ticket->fields[$dateField],
                                                'maybeempty' => false,
                                                'canedit'    => $canupdate,
                                                'required'   => $tt->isMandatoryField($dateField)]);
            echo $tt->getEndHiddenFieldValue($dateField, $ticket);
            echo "</td>";
            $data     = $this->find(
               ['type' => $type] + getEntitiesRestrictCriteria('', '', $ticket->fields['entities_id'], true)
            );
            if ($canupdate
               && !empty($data)) {
               echo $tt->getBeginHiddenFieldText($laField);
               if (!$tt->isHiddenField($laField) || $tt->isPredefinedField($laField)) {
                  echo "<th>".sprintf(__('%1$s%2$s'),
                                    static::getTypeName(),
                                    $tt->getMandatoryMark($laField))."</th>";
               }
               echo $tt->getEndHiddenFieldText($laField);
               echo "<td class='nopadding'>".$tt->getBeginHiddenFieldValue($laField);
               static::dropdown([
                  'name'      => $laField,
                  'entity'    => $ticket->fields["entities_id"],
                  'value'     => isset($ticket->fields[$laField]) ? $ticket->fields[$laField] : 0,
                  'condition' => ['type' => $type]
               ]);
               echo $tt->getEndHiddenFieldValue($laField, $ticket);
               echo "</td>";
            }
         }

      // echo "</tr>";
      // echo "</table>";
   }

}
