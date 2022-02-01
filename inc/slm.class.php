<?php


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SLM Class
**/
class PluginServicesSlm extends SLM {

   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = ['SLA', 'OLA'];

   static $rightname                   = 'slm';

   const TTR = 0; // Time to resolve
   const TTO = 1; // Time to own

   static function getTypeName($nb = 0) {
      return _n('Service level', 'Service levels', $nb);
   }

   static function getClassName() {
      return get_called_class();
   }

   static function getTable($classname = null) {
      return "glpi_slms";
   }

   /**
    * Force calendar of the SLM if value -1: calendar of the entity
    *
    * @param integer $calendars_id calendars_id of the ticket
   **/
   function setTicketCalendar($calendars_id) {

      if ($this->fields['calendars_id'] == -1) {
         $this->fields['calendars_id'] = $calendars_id;
      }
   }

   static function canView() {
      return Session::haveRight("plugin_services_slm", READ);
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

   static function getType() {
      return "PluginServicesSLM";
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

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $relatedListabName = PluginServicesRelatedlist::tabNameForItem($item, $withtemplate);
      $tabNam = [
         self::createTabEntry(PluginServicesLog::getTypeName()) 
      ];
      $tab = array_merge($relatedListabName,  $tabNam);
      return $tab;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $lastIndex = count(PluginServicesRelatedlist::tabNameForItem($item, $withtemplate));
      PluginServicesRelatedlist::tabcontent($item, $tabnum, $withtemplate);
      switch ($tabnum) {
            case $lastIndex:
               PluginServicesLog::showForitem($item, $withtemplate);
               break;
      }
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
            $cleaned_options = PluginServicesToolbox::stripslashes_deep($cleaned_options);

            $extraparamhtml = "&amp;".PluginServicesToolbox::append_params($cleaned_options, '&amp;');
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
   
   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            SLA::class,
            OLA::class,
         ]
      );
   }

   function postForm($post) {

      global $CFG_GLPI;
      $slm = new self();
      if (isset($post["update"])) {
         $slm->check($post["id"], UPDATE);
         $slm->update($post);
         PluginServicesFagoUtils::returnResponse();
      }else if (isset($post["add"])) {
         $slm->check(-1, CREATE, $post);
         if ($newID = $slm->add($post)) {
            PluginServicesFagoUtils::returnResponse($newID);
         }
         PluginServicesFagoUtils::returnResponse();
      }
      PluginServicesHtml::back();
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $all_fields = [
         [
            'label'=> 'Name',
            'name'=> 'name',
            'type'=> 'text'
         ],[
            'label'=> 'Calendarier',
            'name'=> 'calendars_id',
            'type'=> 'dropdown',
            'emptylabel' => __('24/7'),
            'toadd'      => ['-1' => __('Calendar of the ticket')]
         ],[
            'label' => 'Comments',
            'name' => 'comment',
            'type' => 'textarea',
            'rows' => 5,
            'full' => true
         ]
      ];
      PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
      $target = $this->getFormURL();
         $jsScript = '
            $(document).ready(function() {
                var tokenurl = "/ajax/generatetoken.php";
                var form = $("#pluginservicesslmform");
                var request;
                form.validate({
                    rules: {
                        name : {
                            required: true
                        }
                    }
                });
                $("#addForm").click(function(e){
                    event.preventDefault();
                    if (!form.valid()) { // stop script where form is invalid
                        return false;
                    }
                    if (request) { // Abort any pending request
                        request.abort();
                    }

                    $("button[name=add]").addClass("m-loader m-loader--light m-loader--right"); // add loader
                    $("button[name=add]").prop("disabled", true);

                    var serializedData = form.serializeArray();
                    
                    $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
                        serializedData[serializedData.length] = { name: "add", value:"add" };
                        serializedData[serializedData.length] = {  name: "_glpi_csrf_token", value:token };
                        request = $.ajax({
                            url: "'.$target.'",
                            type: "post",
                            data: serializedData
                        });

                        request.done(function (response, textStatus, jqXHR){
                            var res = JSON.parse(response);
                            showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
                            $("button[name=add]").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                            $("button[name=add]").prop("disabled", false);
                        });

                        request.fail(function (jqXHR, textStatus, errorThrown){
                            removeSubmitFormLoader("add");
                            console.error(jqXHR, textStatus, errorThrown);

                            $("button[name=add]").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                            $("button[name=add]").prop("disabled", false);
                        });
                    }); 
                })

                $("#editForm").click(function(e){
                    event.preventDefault();
        
                    if (!form.valid()) { // stop script where form is invalid
                        return false;
                    }
                    
                    if (request) { // Abort any pending request
                        request.abort();
                    }

                    $("#editForm").addClass("m-loader m-loader--light m-loader--right"); // add loader
                    $("#editForm").prop("disabled", true);

                    var serializedData = form.serializeArray();

                    $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
                        serializedData[serializedData.length] = { name: "update", value:"update" };
                        serializedData[serializedData.length] = {  name: "_glpi_csrf_token", value:token };
                        request = $.ajax({
                            url: "'.$target.'",
                            type: "post",
                            data: serializedData
                        });

                        request.done(function (response, textStatus, jqXHR){
                            var res = JSON.parse(response);
                            console.log( Object.values(res.message)[0]);
                            showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
                            $("#editForm").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                            $("#editForm").prop("disabled", false);
                        });

                        request.fail(function (jqXHR, textStatus, errorThrown){
                            removeSubmitFormLoader("add");
                            console.error(jqXHR, textStatus, errorThrown);

                            $("#editForm").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                            $("#editForm").prop("disabled", false);
                        });
                    }); 
                })
            })  
         ';
         echo Html::scriptBlock($jsScript);
         return true;
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
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'autocomplete'       => true,
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
         'id'                 => '4',
         'table'              => 'glpi_calendars',
         'field'              => 'name',
         'name'               => _n('Calendar', 'Calendars', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      return $tab;
   }

   public function showList($itemtype, $params){
      PluginServicesHtml::showList($itemtype, $params);
   }


   static function getMenuContent() {

      $menu = [];
      if (static::canView()) {
         $menu['title']           = self::getTypeName(2);
         $menu['page']            = static::getSearchURL(false);
         $menu['icon']            = static::getIcon();
         $menu['links']['search'] = static::getSearchURL(false);
         if (static::canCreate()) {
            $menu['links']['add'] = SLM::getFormURL(false);
         }

         $menu['options']['sla']['title']           = SLA::getTypeName(1);
         $menu['options']['sla']['page']            = SLA::getSearchURL(false);
         $menu['options']['sla']['links']['search'] = SLA::getSearchURL(false);

         $menu['options']['ola']['title']           = OLA::getTypeName(1);
         $menu['options']['ola']['page']            = OLA::getSearchURL(false);
         $menu['options']['ola']['links']['search'] = OLA::getSearchURL(false);

         $menu['options']['slalevel']['title']           = SlaLevel::getTypeName(Session::getPluralNumber());
         $menu['options']['slalevel']['page']            = SlaLevel::getSearchURL(false);
         $menu['options']['slalevel']['links']['search'] = SlaLevel::getSearchURL(false);

         $menu['options']['olalevel']['title']           = OlaLevel::getTypeName(Session::getPluralNumber());
         $menu['options']['olalevel']['page']            = OlaLevel::getSearchURL(false);
         $menu['options']['olalevel']['links']['search'] = OlaLevel::getSearchURL(false);

      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   static function getIcon() {
      return "fas fa-file-contract";
   }

}
