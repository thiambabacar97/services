
<?php


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SLA Class
**/
use Glpi\Event;

class PluginServicesSlalevel extends SlaLevel {

   protected $rules_id_field     = 'slalevels_id';
   protected $ruleactionclass    = 'SlaLevelAction';
   static protected $parentclass = 'SLA';
   static protected $fkparent    = 'slas_id';
   // No criteria
   protected $rulecriteriaclass = 'SlaLevelCriteria';
   

    static $rightname = "plugin_services_slalevels";


   static function getTypeName($nb = 0) {
      // Acronymous, no plural
      return __('SlaLevel');
   }
   static function getClassName() {
      return get_called_class();
   }

  
   static function getType() {
      return "SlaLevel";
   }

   static function canView() {
      return Session::haveRight("plugin_services_slms", READ);
   }

   static function canCreate() {
      return Session::haveRight(self::$rightname, CREATE);
   }

   static function canUpdate() {
      return true;
   }
  
   static function getSearchURL($full = true) {
      return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
   }

   static function getTable($classname = null) {
      return "glpi_slalevels";
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {


      if ($item->getType() == "SlaLevel") {
         if (Session::getCurrentInterface() != "helpdesk" ) {
            return [
               self::createTabEntry(__("Critères")),
               self::createTabEntry(__("Actions")),

            ];
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == "SlaLevel") {
         switch ($tabnum) {
            case 0:
            $item->getRuleWithCriteriasAndActions($item->getID(), 1, 1);
            $item->showCriteriasList($item->getID());
            break;
            case 1:
            $item->getRuleWithCriteriasAndActions($item->getID(), 1, 1);
            $item->showActionsList($item->getID());
            break;

         }
      }

      return true;
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

    function showForParent(SLA $sla) {
        return $this->showForSLA($sla);
    }

    function showForSLA(SLA $sla) {

        global $DB;
        $ID = $sla->getField('id');
        if (!$sla->can($ID, READ)) {
           return false;
        }
  
        $canedit = $sla->can($ID, UPDATE);
  
        $rand    = mt_rand();
  
        if ($canedit) {
           echo "<div class='center first-bloc'>";
           echo "<form name='slalevel_form$rand' id='slalevel_form$rand' method='post' action='";
           echo PluginServicesToolbox::getItemTypeFormURL(__CLASS__)."'>";
  
           echo "<table class='tab_cadre_fixe'>";
           echo "<tr class='tab_bg_1'><th colspan='7'>".__('Add an escalation level')."</tr>";
  
           echo "<tr class='tab_bg_2'><td class='center'>".__('Name')."";
           echo "<input type='hidden' name='slas_id' value='$ID'>";
           echo "<input type='hidden' name='entities_id' value='".$sla->getEntityID()."'>";
           echo "<input type='hidden' name='is_recursive' value='".$sla->isRecursive()."'>";
           echo "<input type='hidden' name='match' value='AND'>";
           echo "</td><td><input  name='name' value=''>";
           echo "</td><td class='center'>".__('Execution')."</td><td>";
  
           $delay = $sla->getTime();
           self::dropdownExecutionTime('execution_time',
                                       ['max_time' => $delay,
                                        'used'     => self::getAlreadyUsedExecutionTime($sla->fields['id']),
                                        'type'     => $sla->fields['type']]);
  
           echo "</td><td class='center'>".__('Active')."</td><td>";
           Dropdown::showYesNo("is_active", 1);
           echo "</td><td class='center'>";
           echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
           echo "</td></tr>";
  
           echo "</table>";
           Html::closeForm();
           echo "</div>";
        }
  
        $iterator = $DB->request([
           'FROM'   => self::getTable(),
           'WHERE'  => [
              'slas_id'   => $ID
           ],
           'ORDER'  => 'execution_time'
        ]);
        $numrows = count($iterator);
  
        echo "<div class='spaced'>";
        if ($canedit && $numrows) {
         PluginServicesHtml::openMassiveActionsForm('mass'.__CLASS__.$rand);
           $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $numrows),
                                        'container'      => 'mass'.__CLASS__.$rand];
                                        PluginServicesHtml::showMassiveActions($massiveactionparams);
        }
  
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr>";
        if ($canedit && $numrows) {
           echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
        }
        echo "<th>".__('Name')."</th>";
        echo "<th>".__('Execution')."</th>";
        echo "<th>".__('Active')."</th>";
        echo "</tr>";
        Session::initNavigateListItems('SlaLevel',
        //TRANS: %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                       sprintf(__('%1$s = %2$s'), SLA::getTypeName(1),
                                               $sla->getName()));
  
        while ($data = $iterator->next()) {
           Session::addToNavigateListItems('SlaLevel', $data["id"]);
  
           echo "<tr class='tab_bg_2'>";
           if ($canedit) {
              echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
           }
  
           echo "<td>";
           if ($canedit) {
              echo "<a href='".PluginServicesToolbox::getItemTypeFormURL('SlaLevel')."?id=".$data["id"]."'>";
           }
           echo $data["name"];
           if (empty($data["name"])) {
              echo "(".$data['id'].")";
           }
           if ($canedit) {
              echo "</a>";
           }
           echo "</td>";
           echo "<td>".($data["execution_time"] != 0
                          ? Html::timestampToString($data["execution_time"], false)
                          : ($sla->fields['type'] == 1
                                ? __('Time to own')
                                : __('Time to resolve'))).
                "</td>";
           echo "<td>".Dropdown::getYesNo($data["is_active"])."</td>";
           echo "</tr>";
  
           echo "<tr class='tab_bg_1'><td colspan='2'>";
           $this->getRuleWithCriteriasAndActions($data['id'], 1, 1);
           $this->showCriteriasList($data["id"], ['readonly' => true]);
           echo "</td><td colspan='2'>";
           $this->showActionsList($data["id"], ['readonly' => true]);
           echo "</td></tr>";
        }
  
        echo "</table>";
        if ($canedit && $numrows) {
           $massiveactionparams['ontop'] = false;
           PluginServicesHtml::showMassiveActions($massiveactionparams);
           Html::closeForm();
        }
        echo "</div>";
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

   function postForm($post) {

      global $CFG_GLPI;
      $slalevel = new self();
      // print_r($post);
      // return ;
   
      if (isset($post["update"])) {
         $slalevel->check($post["id"], UPDATE);
      
         $slalevel->update($post);
      
         Event::log($post["id"], "slas", 4, "setup",
                     //TRANS: %s is the user login
                     sprintf(__('%s updates a sla level'), $_SESSION["glpiname"]));
      
         // Html::back();
      
      } else if (isset($post["add"])) {
      
         $slalevel->check(-1, CREATE, $post);

         if ($slalevel->add($post)) {
            Event::log($post["slas_id"], "slas", 4, "setup",
                        //TRANS: %s is the user login
                        sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"]));
            if ($_SESSION['glpibackcreated']) {
               Html::redirect($slalevel->getLinkURL());
            }
         }
      }
      Html::back();
   }


   function showForm($ID, $options = []) {

      global $CFG_GLPI;

      $canedit = $this->can('sla', UPDATE);

      $item = new self();
      if(!empty($ID)){
         $item->getFromDB($ID);
         $this->fields = $item->fields;
      }

      $this->initForm($ID, $options);
      

      $this->initForm($ID, $options);
      
      $sla = new SLA();
      $sla->getFromDB($this->fields['slas_id']);

      echo '<div class="m-content">
                  <div class="m-portlet mb-0">
                     <div class="m-portlet__head">
                        <div class="m-portlet__head-caption">
                              <div class="m-portlet__head-title">
                                 <span class="m-portlet__head-icon m--hide">
                                    <i class="la la-gear"></i>
                                 </span>
                                 <h3 class="m-portlet__head-text">';
                                    echo $this->fields["name"];
                                 echo'</h3>
                              </div>
                        </div>

                        <div class="m-portlet__head-tools">';

                        echo'</div>
                     </div>

                  <!--begin::Form-->
                  
                  <div class="m-portlet__body">';

                     echo '<form class="m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed" method="POST" action="'. $CFG_GLPI["root_doc"] .'/slalevel/form/">';
                     echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);

                        echo'<div class="form-group m-form__group row d-flex justify-content-center">';
                              echo '<label class="col-lg-2 col-form-label">';
                                 echo __('Name');
                              echo '</label>';

                              echo'<div class="col-lg-3">';
                                 PluginServicesHtml::autocompletionTextField($item, "name");
                              echo'</div>';

                              echo '<label class="col-lg-2 col-form-label">';
                                 echo __('Active');
                              echo '</label>';

                              echo'<div class="col-lg-3">';
                                 Dropdown::showYesNo("is_active", $this->fields["is_active"]);
                              echo'</div>';

                        echo'</div>';   


                        echo'<div class="form-group m-form__group row d-flex justify-content-center">';
                              echo '<label class="col-lg-2 col-form-label">';
                                 echo __('Execution');
                              echo '</label>';

                              echo'<div class="col-lg-3">';
                                 $delay = $sla->getTime();

                                 self::dropdownExecutionTime('execution_time',
                                    ['max_time'
                                             => $delay,
                                          'used'
                                             => self::getAlreadyUsedExecutionTime($sla->fields['id']),
                                          'value'
                                             => $this->fields['execution_time'],
                                          'type'
                                             => $sla->fields['type']]);

                              echo'</div>';

                              echo '<label class="col-lg-2 col-form-label">';
                                 echo __('Logical operator');
                              echo '</label>';

                              echo'<div class="col-lg-3">';
                                 $this->dropdownRulesMatch(['value' => $this->fields["match"]]);
                              echo'</div>';

                        echo'</div>';   
                     
                        echo '<div class="m-form__actions m-form__actions--solid m-form__actions--right">
                        <button style="font-size:14px;" type="submit" class="btn btn-brand">Sauvegarder</button>';
                        echo'</div>';

                        if(!empty($ID)){
                           echo"<input class='form-control m-input' value='update' type='hidden' name='update'>";
                           echo"<input class='form-control m-input' value='".$ID."' type='hidden' name='id'>";
                        }else{
                           echo"<input class='form-control m-input' value='add' type='hidden' name='add'>";
                        }

                     echo"</form>";

                  echo"</div>";

                  echo"</div>";

                  echo '<div class="m-portlet__foot m-portlet__no-border m-portlet__foot">
                        <div class="m-form__actions m-form__actions--solid m-form__actions--right">';
                        $this->showTabsContent([]);
                        echo'</div>
                     </div>';

                     
                  echo'</div>
                  
            </div>
            
            </div>';
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
                                 Slalevel
                           </h3>
                        </div>
                     </div>
                     <div class="m-portlet__head-tools">
                           <ul class="m-portlet__nav">
                              <li class="m-portlet__nav-item">
                                 <a href="'.$CFG_GLPI['root_doc'].'/slalevel/form" class="btn btn-secondary btn-lg m-btn m-btn--icon m-btn--icon-only bg-light">
                                    <i class="flaticon-add-circular-button"></i>
                                 </a>
                              </li>
                           </ul>
                     </div>


                  </div>
                  <div class="m-portlet__body">';
                  PluginServicesSearch::showFago("PluginAssistancesSlalevel", $params);
                  echo'  
                  </div>
               </div>
               </div>
         </div>
      </div>';
   }


}