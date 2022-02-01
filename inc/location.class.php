<?php 

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}
use Glpi\Event;
class PluginServicesLocation extends Location{
   
   static $rightname = 'location';

   static function getTable($classname = null){
      return "glpi_locations";
   }

   static function getClassName() {
      return get_called_class();
   }

   function getLinkURL(){
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

   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRight(self::$rightname, CREATE);
   }

   static function canUpdate() {
      return Session::haveRightsOr(self::$rightname, [UPDATE]);
   }

   static function getType() {
      return "PluginServicesLocation";
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == "Location") {
         if (Session::getCurrentInterface() != "helpdesk" ) {
            return [
               self::createTabEntry(__("Elements")),
            ];
         }
      }

      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == "Location") {
           
           switch ($tabnum) {
               case 0:
                  $item->showItems();
                  break;
           }
        }
  
        return true;
   }

   function showItems() {
      global $DB, $CFG_GLPI;
      $locations_id = $this->fields['id'];
      $current_itemtype     = Session::getSavedOption(__CLASS__, 'criterion', '');

      if (!$this->can($locations_id, READ)) {
         return false;
      }

      $queries = [];
      $itemtypes = $current_itemtype ? [$current_itemtype] : $CFG_GLPI['location_types'];
      foreach ($itemtypes as $itemtype) {
         $item = new $itemtype();
         if (!$item->maybeLocated()) {
            continue;
         }
         $table = getTableForItemType($itemtype);
         $itemtype_criteria = [
            'SELECT' => [
               "$table.id",
               new \QueryExpression($DB->quoteValue($itemtype) . ' AS ' . $DB->quoteName('type')),
            ],
            'FROM'   => $table,
            'WHERE'  => [
               "$table.locations_id"   => $locations_id,
            ] + getEntitiesRestrictCriteria($table, 'entities_id')
         ];
         if ($item->maybeDeleted()) {
            $itemtype_criteria['WHERE']['is_deleted'] = 0;
         }
         $queries[] = $itemtype_criteria;
      }
      $criteria = count($queries) === 1 ? $queries[0] : ['FROM' => new \QueryUnion($queries)];

      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      $criteria['START'] = $start;
      $criteria['LIMIT'] = $_SESSION['glpilist_limit'];

      $iterator = $DB->request($criteria);

      // Execute a second request to get the total number of rows
      unset($criteria['SELECT']);
      unset($criteria['START']);
      unset($criteria['LIMIT']);

      $criteria['COUNT'] = 'total';
      $number = $DB->request($criteria)->next()['total'];

      // Mini Search engine
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>"._n('Type', 'Types', 1)."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo _n('Type', 'Types', 1)."&nbsp;";
      $all_types = array_merge(['0' => '---'], $CFG_GLPI['location_types']);
      Dropdown::showItemType(
         $all_types, [
            'value'      => $current_itemtype,
            'on_change'  => 'reloadTab("start=0&criterion="+this.value)'
         ]
      );
      echo "</td></tr></table>";

      if ($number) {
         echo "<div class='spaced'>";
         Html::printAjaxPager('', $start, $number);

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>"._n('Type', 'Types', 1)."</th>";
         echo "<th>".Entity::getTypeName(1)."</th>";
         echo "<th>".__('Name')."</th>";
         echo "<th>".__('Serial number')."</th>";
         echo "<th>".__('Inventory number')."</th>";
         echo "</tr>";

         while ($data = $iterator->next()) {
            $item = getItemForItemtype($data['type']);
            $item->getFromDB($data['id']);
            echo "<tr class='tab_bg_1'><td class='center top'>".$item->getTypeName()."</td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                                 $item->getEntityID());
            echo "</td><td class='center'>".$item->getLink()."</td>";
            echo "<td class='center'>".
                  (isset($item->fields["serial"])? "".$item->fields["serial"]."" :"-");
            echo "</td>";
            echo "<td class='center'>".
                  (isset($item->fields["otherserial"])? "".$item->fields["otherserial"]."" :"-");
            echo "</td></tr>";
         }
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
      echo "</table></div>";

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

   static function dropdown($options = []) {
      return PluginServicesDropdown::show('Location', $options);
   }

   function postForm($post) {
      $dropdown = new self();
      if (isset($_POST["add"])) {
         $dropdown->check(-1, CREATE, $_POST);
      
         if ($newID=$dropdown->add($_POST)) {
            if ($dropdown instanceof CommonDevice) {
               Event::log($newID, get_class($dropdown), 4, "inventory",
                          sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"],
                                  $_POST["designation"]));
            } else {
               Event::log($newID, get_class($dropdown), 4, "setup",
                          sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
            }
            if ($_SESSION['glpibackcreated']) {
               $url = $dropdown->getLinkURL();
               if (isset($_REQUEST['_in_modal'])) {
                  $url.="&_in_modal=1";
               }
               PluginServicesHtml::redirect($url);
            }
         }
         PluginServicesHtml::back();
      
      } else if (isset($_POST["purge"])) {
         $dropdown->check($_POST["id"], PURGE);
         if ($dropdown->isUsed()
             && empty($_POST["forcepurge"])) {
            $dropdown->showDeleteConfirmForm($_SERVER['PHP_SELF']);
         } else {
            $dropdown->delete($_POST, 1);
      
            Event::log($_POST["id"], get_class($dropdown), 4, "setup",
                       //TRANS: %s is the user login
                       sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
            $dropdown->redirectToList();
         }
      
      } else if (isset($_POST["replace"])) {
         $dropdown->check($_POST["id"], PURGE);
         $dropdown->delete($_POST, 1);
      
         Event::log($_POST["id"], get_class($dropdown), 4, "setup",
                    //TRANS: %s is the user login
                    sprintf(__('%s replaces an item'), $_SESSION["glpiname"]));
         $dropdown->redirectToList();
      
      } else if (isset($_POST["update"])) {
         $dropdown->check($_POST["id"], UPDATE);
         $dropdown->update($_POST);
      
         Event::log($_POST["id"], get_class($dropdown), 4, "setup",
                    //TRANS: %s is the user login
                    sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
         PluginServicesHtml::back();
      
      } else if (isset($_POST['execute'])
                 && isset($_POST['_method'])) {
         $method = 'execute'.$_POST['_method'];
         if (method_exists($dropdown, $method)) {
            call_user_func([&$dropdown, $method], $_POST);
            PluginServicesHtml::back();
         } else {
            PluginServicesHtml::displayErrorAndDie(__('No selected element or badly defined operation'));
         }
      
      }
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $all_fields = [
         [
            'name'   => 'name',
            'label'  => __('Name'),
            'type'   => 'text',
         ],
         [
            'name'  => $this->getForeignKeyField(),
            'label' => __('As child of'),
            'type'  => 'parent',
         ], [
            'name'   => 'address',
            'label'  => __('Address'),
            'type'   => 'text',
         ], [
            'name'   => 'postcode',
            'label'  => __('Postal code'),
            'type'   => 'text',
         ], [
            'name'   => 'town',
            'label'  => __('Town'),
            'type'   => 'text',
         ], [
            'name'   => 'state',
            'label'  => _x('location', 'State'),
            'type'   => 'text',
         ], [
            'name'   => 'country',
            'label'  => __('Country'),
            'type'   => 'text',
         ], [
            'name'  => 'building',
            'label' => __('Building number'),
            'type'  => 'text',
         ], [
            'name'  => 'room',
            'label' => __('Room number'),
            'type'  => 'text',
         ],[
            'name'   => 'comment',
            'label'  => __('Comments'),
            'type'   => 'textarea',
            'full'   => true,
         ]
      ];
      PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
   }

   public function showList($itemtype, $params){
      PluginServicesHtml::showList($itemtype,  $params);
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
}

?>