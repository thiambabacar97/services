<?php
if (!defined('GLPI_ROOT')) {
   include ('../../../inc/includes.php');
}
use Ramsey\Uuid\Uuid;
use Glpi\Dashboard\Grid;
use Glpi\Dashboard\Widget;
use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Provider;
use Glpi\Dashboard\Item;

// use CommonGLPI;
// use Dropdown;
// use DBConnection;
// use Html;
// use Plugin;
// use Profile;
// use Session;
// use Toolbox;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginServicesGrid extends Grid {
   protected $cell_margin     = 6;
   protected $grid_cols       = 26;
   protected $grid_rows       = 24;
   protected $current         = "";
   protected $dashboard       = null;
   protected $items           = [];

   static $embed              = false;
   static $context            = '';
   static $all_dashboards     = [];

   // static $rightname = 'plugin_services_grid';

   public function __construct(
      string $dashboard_key = "central",
      int $grid_cols = 26,
      int $grid_rows = 24,
      string $context = "core"
   ) {

      $this->current   = $dashboard_key;
      $this->grid_cols = $grid_cols;
      $this->grid_rows = $grid_rows;

      $this->dashboard = new Dashboard($dashboard_key);
      self::$context   = $context;
   }

   static function getType() {
      return "PluginServicesGrid";
   }
   static function canView() {
      return Session::haveRight(self::$rightname, READ);
   }

   static function canCreate() {
      return Session::haveRight(self::$rightname, CREATE);
   }
   
   static function canUpdate() {
      return true;
   }

   /**
    * Return the instance of current dasbhoard
    *
    * @return Dashboard
    */
   public function getDashboard() {
      return $this->dashboard;
   }


   /**
    * load all existing dashboards from DB into a static property for caching data
    *
    * @param bool $force, if false, don't use cache
    *
    * @return bool
    */
   static function loadAllDashboards(bool $force = true): bool {
      if (!is_array(self::$all_dashboards)
          || count(self::$all_dashboards) === 0
          || $force) {
         self::$all_dashboards = Dashboard::getAll($force, !self::$embed, self::$context);
      }

      return is_array(self::$all_dashboards);
   }


   /**
    * Init dashboards cards
    * A define.php constant (GLPI_AJAX_DASHBOARD) exists to control how the cards should be loaded
    *  - if true: only the parent block will be initialized and the content will be load by ajax
    *    pros: if a widget fails, only this one will crash
    * - else: load all html
    *    pros: better perfs
    *
    * @return void
    */
   public function getCards() {
      self::loadAllDashboards();

      if (!isset(self::$all_dashboards[$this->current])
          || !isset(self::$all_dashboards[$this->current]['items'])) {
         self::$all_dashboards[$this->current] = [
            'items' => []
         ];
      }

      foreach (self::$all_dashboards[$this->current]['items'] as $specs) {
         $card_id      = $specs['card_id'] ?? $specs['gridstack_id'] ?? $specs['id'];
         $gridstack_id = $specs['gridstack_id']   ?? $specs['id'];
         $card_options = ($specs['card_options'] ?? []) + [
            'card_id' => $card_id
         ];

         if (GLPI_AJAX_DASHBOARD) {
            $card_html    = <<<HTML
            <div class="loading-card">
               <i class="fas fa-spinner fa-spin fa-3x"></i>
            </div>
HTML;
         } else {
            $card_html = $this->getCardHtml($card_id, ['args' => $card_options]);
         }

         $this->addGridItem(
            $card_html,
            $gridstack_id,
            $specs['x'] ?? -1,
            $specs['y'] ?? -1,
            $specs['width'] ?? 2,
            $specs['height'] ?? 2,
            $card_options
         );
      }
   }


   /**
    * Do we have the right to view at least one dashboard int the current collection
    *
    * @return bool
    */
   public function canViewCurrent(): bool {
      // check global (admin) right
      if (Dashboard::canView()) {
         return true;
      }

      return $this->dashboard->canViewCurrent();
   }


   /**
    * Do we have the right to view at least one dashboard in the current collection
    *
    * @return bool
    */
   static function canViewOneDashboard(): bool {
      // check global (admin) right
      if (Dashboard::canView()) {
         return true;
      }

      self::loadAllDashboards();

      return (count(self::$all_dashboards) > 0);
   }


   /**
    * Do we have the right to view the specified dashboard int the current collection
    *
    * @param string $key the dashboard to check
    *
    * @return bool
    */
   static function canViewSpecificicDashboard($key): bool {
      // check global (admin) right
      if (Dashboard::canView()) {
         return true;
      }

      self::loadAllDashboards();

      return isset(self::$all_dashboards[$key]);
   }
   // this function is use to get all ItemType and table name for each item

   function getAllTable($options = []){ 
      global  $DB;
      $result = $DB->listTables();
      $res = [];
      
      while ($line = $result->next()) {
         $itemtype = getItemTypeForTable($line['TABLE_NAME']);
         if ((getItemForItemtype($itemtype) instanceof CommonDBRelation)) {
            continue;
         }

         if (class_exists( $itemtype )) {
            $res[$itemtype] = $itemtype.'====>'.getTableForItemType($itemtype);
         }
      }
      PluginServicesDropdown::showFromArray("itemtype", $res, $options);
   }


   // This function is used to get all attribut of the selected item
   public function getAttributItem($item = 'glpi_tickets'){
      global $DB;
      $datas = $DB->listFields($item);
      $list_datas = array();
      foreach ($datas as $value) {
         $list_datas[$value['Field']] = $value['Field'];
         // array_push($list_datas, [$value $value['Field']]);
      }
      return $list_datas;
   }

   /**
    * This function is used to get The widget of specific field
    *
    * @return list of widget
    */
   public function getWidgetype(){
      $widgettypes  = Widget::getAllTypes();
      $list_widget = [];
      foreach ($widgettypes as $key => $value) {
         $list_widget[$key] = $key;
         //array_push($list_widget, $key);
      }
      return $list_widget;
   }

   /**
    * Display grid for the current dashboard
    *
    * @return void display html of the grid
    */
    public function show(bool $mini = false) {
      $rand = mt_rand();
      if (!self::$embed && !$this->dashboard->canViewCurrent()) {
         return;
      }
      // $ticket = getAllFields('glpi_tickets');
      // print_r($this->getMenuItemtypes());
      self::loadAllDashboards();
      $this->restoreLastDashboard();
      
      if ($mini) {
         $this->cell_margin = 3;
      }

      $embed_str     = self::$embed ? "true" : "false";
      $embed_class   = self::$embed ? "embed" : "";
      $mini_class    = $mini ? "mini" : "";

      $nb_dashboards = count(self::$all_dashboards);

      $can_view_all  = Session::haveRight('dashboard', READ) || self::$embed;
      $can_create    = Session::haveRight('dashboard', CREATE);
      $can_edit      = Session::haveRight('dashboard', UPDATE) && $nb_dashboards;
      $can_purge     = Session::haveRight('dashboard', PURGE) && $nb_dashboards;
      $can_clone     = $can_create && $nb_dashboards;
      
      // prepare html for add controls
      $add_controls = "";
      for ($y = 0; $y < $this->grid_rows; $y++) {
         for ($x = 0; $x < $this->grid_cols; $x++) {
            $add_controls.= "<div class='cell-add' data-x='$x' data-y='$y'>&nbsp;</div>";
         }
      }
      
      // prepare all available cards
      $cards = $this->getAllDasboardCards();
      
      $cards_json = json_encode($cards);
      // prepare all available widgets
      $all_widgets = Widget::getAllTypes();
      $all_widgets_json = json_encode($all_widgets);
      
      // prepare labels
      
      $embed_label      = __("Share or embed this dashboard");
      $delete_label     = __("Delete this dashboard");
      $history_label    = __("Toggle auto-refresh");
      $night_label      = __("Toggle night mode");
      $fs_label         = __("Toggle fullscreen");
      $clone_label      = __("Clone this dashboard");
      $edit_label       = __("Toggle edit mode");
      $add_filter_lbl   = __("Add filter");
      $add_dash_label   = __("Add a new dashboard");
      $save_label       = _x('button', "Save");
      
      $gridstack_items = $this->getGridItemsHtml();
      
      $dropdown_dashboards = "";
      if ($nb_dashboards) {
         $dropdown_dashboards = self::dropdownDashboard("", [
            'value'        => $this->current,
            'display'      => false,
            'class'        => 'dashboard_select',
            'can_view_all' => $can_view_all,
            'noselect2'    => true,
         ]);
      }
      
      $dashboard_title = $this->dashboard->getTitle();
      
      $l_tb_icons   = "";
      $r_tb_icons   = "";
      $rename       = "";
      $left_toolbar = "";
      $grid_guide   = "";
      // $display_item = "";
      // $l_tb_widget = "";
      // $l_tb_widget.= "<span style='margin-left:20px'>Data</span><i class='fas fa-angle-double-right fs-toggle cell-add' style='margin-left:2px'></i>";
      // $display_item .= "<span style='margin-left:20px'>Item</span><i class='fas fa-angle-double-right fs-toggle add-item' style='margin-left:2px'></i>"; 
      if (!self::$embed) {
         if (!$mini && $can_create) {
            $l_tb_icons.=' <a href="'.PluginServicesToolbox::getItemTypeFormURL("PluginServicesDashboard").'" style="margin-left:20px" class="btn_bis m-btn--radius btn-md  btn-info ml-2">
                                    '.__s("NOUVEAU").'
                           </a>';
            // $l_tb_icons.= "<span style='margin-left:20px' class='btn_bis m-btn--radius btn-md  btn-info ml-2 add-dashboard'>Nouveau</span>";
         }
         if (!$mini && $can_clone) {
            $r_tb_icons.= "<i class='fas fa-clone fs-toggle clone-dashboard' title='$clone_label'></i>";
         }
         if (!$mini && $can_edit) {
            $r_tb_icons.= "<i class='fas fa-share-alt fs-toggle open-embed' title='$embed_label'></i>";
            $rename = "<div class='edit-dashboard-properties'>
               <input type='text' class='dashboard-name' value='{$dashboard_title}' size='1'>
               <i class='fas fa-save save-dashboard-name' title='{$save_label}'></i>
               <span class='display-message'></span>
            </div>";
         }
         if (!$mini && $can_purge) {
            $r_tb_icons.= "<i class='fas fa-trash fs-toggle delete-dashboard' title='$delete_label'></i>";
         }
         if ($can_edit) {
            $r_tb_icons.= "<i class='fas fa-edit fs-toggle edit-dashboard' title='$edit_label'></i>";
         }

         if (!$mini) {
            $r_tb_icons.= "<i class='fas fa-expand toggle-fullscreen' title='$fs_label'></i>";
         }
         // $this->displayConfigForm();
         // $this->displayForm();
         $item = new PluginServicesItem();
         $item->showForm();
         if (!$mini) {
            $left_toolbar = <<<HTML
               <span class="toolbar left-toolbar">
                  <div class="change-dashboard">
                     
                     <div class="mt-2">
                        $dropdown_dashboards
                        $l_tb_icons
                     
                     </div>
                  </div>
                  $rename
               </span>
HTML;
         }

         $grid_guide = <<<HTML
            <div class="grid-guide">
               $add_controls
            </div>
HTML;
      }
      echo "
         <style>
            .progress_bar {
               background-color: #d3d3d3;
               width: 850%;
               height: 1.5em;
               border-radius: 5px
            }
            .btn_bis{
               display: inline-block;
               font-weight: 400;
               text-align: center;
               white-space: nowrap;
               vertical-align: middle;
               border: 1px solid transparent;
               padding: 0.5rem 1.15rem;
               font-size: 1rem;
               line-height: 1.25;

            }
         </style>
      ";

      $toolbars = <<<HTML
         $left_toolbar
         <span class="toolbar">
            <i class="fas fa-history auto-refresh" title="$history_label"></i>
            <i class="fas fa-moon night-mode" title="$night_label"></i>
            $r_tb_icons
         </span>
HTML;

      $filters = "";
      if (!$mini) {
         $filters = <<<HTML
         <div class='filters_toolbar'>
            <span class='filters'></span>
            <span class='filters-control'>
               <i class="fas fa-plus-square plus-sign add-filter">
                  <span class='add-filter-lbl'>{$add_filter_lbl}</span>
               </i>
            </span>
         </div>
HTML;
      }

      $embed_watermark = "";  
      if (self::$embed) {
         $embed_watermark = "<span class='glpi_logo'></span>";
      }

      // display the grid
      $html = <<<HTML
      <div class="dashboard {$embed_class} {$mini_class}" id="dashboard-{$rand}">
         $embed_watermark
         $toolbars
         $filters
         $grid_guide
         <div class="grid-stack grid-stack-{$this->grid_cols}"
            id="grid-stack-$rand"
            style="width: 100%">
            $gridstack_items
         </div>
      </div>
HTML;

      $ajax_cards = GLPI_AJAX_DASHBOARD;
      $context    = self::$context;
      $cache_key  = sha1($_SESSION['glpiactiveentities_string '] ?? "");

      $js = <<<JAVASCRIPT
      $(function () {
         Dashboard.display({
            current:     '{$this->current}',
            cols:        {$this->grid_cols},
            rows:        {$this->grid_rows},
            cell_margin: {$this->cell_margin},
            rand:        '{$rand}',
            embed:       {$embed_str},
            ajax_cards:  {$ajax_cards},
            all_cards:   {$cards_json},
            all_widgets: {$all_widgets_json},
            context:     "{$context}",
            cache_key:   "{$cache_key}",
         })
      });
JAVASCRIPT;
      $js = Html::scriptBlock($js);

      echo $html.$js;
      echo "
      <style>
      .this_filters_toolbar {
         text-align: left;
         height: 11;
         margin: 0 5px 5px 2px;
         box-sizing: border-box;
         padding-top: 0.5em;
   
         @media screen and (max-width: 700px) {
            height: inherit;
         }
      </style>
      ";
   }


   public function showDefault() {   
      echo "<div class='default_dashboard' style='margin-top:1%'>";
         $grid = new self("new");
         $grid->show();
      echo "</div>";
   }


   /**
    * Show an embeded dashboard.
    * We must check token validity to avoid displaying dashboard to invalid users
    *
    * @param array $params contains theses keys:
    * - dashboard: the dashboard system name
    * - entities_id: entity to init in session
    * - is_recursive: do we need to display sub entities
    * - token: the token to check
    *
    * @return void (display)
    */
   public function embed(array $params = []) {
      $defaults = [
         'dashboard'    => '',
         'entities_id'  => 0,
         'is_recursive' => 0,
         'token'        => ''
      ];
      $params = array_merge($defaults, $params);

      if (!self::checkToken($params)) {
         \PluginServicesHtml::displayRightError();
         exit;
      }

      self::$embed = true;

      // load minimal session
      $_SESSION["glpiactive_entity"]           = $params['entities_id'];
      $_SESSION["glpiactive_entity_recursive"] = $params['is_recursive'];
      $_SESSION["glpiname"]                    = 'embed_dashboard';
      $_SESSION["glpigroups"]                  = [];
      if ($params['is_recursive']) {
         $entities = getSonsOf("glpi_entities", $params['entities_id']);
      } else {
         $entities = [$params['entities_id']];
      }
      $_SESSION['glpiactiveentities']        = $entities;
      $_SESSION['glpiactiveentities_string'] = "'".implode("', '", $entities)."'";

      // show embeded dashboard
      $this->show(true);
   }

   static function getToken(string $dasboard = "", int $entities_id = 0, int $is_recursive = 0): string {
      $seed         = $dasboard.$entities_id.$is_recursive.Telemetry::getInstanceUuid();
      $uuid         = Uuid::uuid5(Uuid::NAMESPACE_OID, $seed);
      $token        = $uuid->toString();

      return $token;
   }

   /**
    * Check token variables (compare it to `dashboard`, `entities_id` and `is_recursive` paramater)
    *
    * @param array $params contains theses keys:
    * - dashboard: the dashboard system name
    * - entities_id: entity to init in session
    * - is_recursive: do we need to display sub entities
    * - token: the token to check
    *
    * @return bool
    */
   static function checkToken(array $params = []):bool {
      $defaults = [
         'dashboard'    => '',
         'entities_id'  => 0,
         'is_recursive' => 0,
         'token'        => ''
      ];
      $params = array_merge($defaults, $params);

      $token = self::getToken(
         $params['dashboard'],
         $params['entities_id'],
         $params['is_recursive']
      );

      if ($token !== $params['token']) {
         return false;
         \PluginServicesHtml::displayRightError();
         exit;
      }

      return true;
   }


   /**
    * Return the html for all items for the current dashboard
    *
    * @param bool $with_lock if true, return also a locked bottom item (to fix grid height)
    *
    * @return string html of the grid items
    */
   public function getGridItemsHtml(bool $with_lock = true, bool $embed = false): string {
      if ($embed) {
         self::$embed = true;
      }

      $this->getCards();

      if ($with_lock) {
         $this->items[] = <<<HTML
         <div class="grid-stack-item lock-bottom"
            data-gs-no-resize="true"
            data-gs-no-move="true"
            data-gs-height="1"
            data-gs-width="{$this->grid_cols}"
            data-gs-x="0"
            data-gs-y="{$this->grid_rows}"></div>
HTML;
      }
      
      // append all elements to insert them in html
      return implode("", $this->items);
   }


   /**
    * Add a new grid item
    *
    * @param string $html content of the card
    * @param string $gridstack_id unique id identifying the card (used in gridstack)
    * @param int $x position in the grid
    * @param int $y position in the grid
    * @param int $width size in the grid
    * @param int $height size in the grid
    * @param array $data_option aditional options passed to the widget, contains at least thses keys:
    *                             - string 'color'
    * @return void
    */
   public function addGridItem(
      string $html = "",
      string $gridstack_id = "",
      int $x = -1,
      int $y = -1,
      int $width = 2,
      int $height = 2,
      array $data_option = []) {

      // let grid-stack to autoposition item
      $autoposition = 'data-gs-auto-position="true"';
      $coordinates  = '';
      if ((int) $x >= 0 && (int) $y >= 0) {
         $autoposition = "";
         $coordinates  = "data-gs-x='$x' data-gs-y='$y'";
      }

      $color    = $data_option['color'] ?? "#FFFFFF";
      $fg_color = \Toolbox::getFgColor($color, 100);

      // add card options in data attribute
      $data_option_attr = "";
      if (count($data_option)) {
         $data_option_attr = "data-card-options='".json_encode($data_option, JSON_HEX_APOS)."'";
      }

      $refresh_label = __("Refresh this card");
      $edit_label    = __("Edit this card");
      $delete_label  = __("Delete this card");

      $this->items[] = <<<HTML
         <div class="grid-stack-item"
               data-gs-id="{$gridstack_id}"
               data-gs-width="{$width}"
               data-gs-height="{$height}"
               {$coordinates}
               {$autoposition}
               {$data_option_attr}
               style="color: {$fg_color}">
            <span class="controls">
               <i class="refresh-item fas fa-sync-alt" title="{$refresh_label}"></i>
               <i class="edit-item fas fa-edit" title="{$edit_label}"></i>
               <i class="delete-item fas fa-times" title="{$delete_label}"></i>
            </span>
            <div class="grid-stack-item-content">{$html}</div>
         </div>
HTML;
   }

   /**
    * Display a the config form
    *
    * @return void (display)
    */
   public function displayForm($ID=-1, $options = []){
      global $CFG_GLPI;
      $item = new PluginServicesItem();
      $item->initForm($ID, $options);
        $all_fields = [
            [
                'label'=> 'Card id',
                'name'=> 'card_id',
                'type'=> 'text'
            ],
            
        ];
        PluginServicesHtml::generateForm($ID, "PluginServicesItem", $options, $all_fields);
   }

   /**
    * Display a the config form
    *
    * @return void (display)
    */
   public function displayConfigForm() {
      
      global $DB, $CFG_GLPI;
      $rand = mt_rand();
      // echo Html::script('assets/vendors/base/vendors.bundle.js');
      // echo Html::script('assets/demo/default/base/style.bundle.css');
      // echo Html::script('assets/demo/default/base/chart.min.js'); 
      // echo Html::script('assets/demo/default/custom/crud/forms/widgets/select2.js');
		
      // echo "<div class='default_dash'>";
      $itemtype = "Ticket";
      $IDuser = Session::getLoginUserID();
      $already_added = PluginServicesDisplayPreference::getForTypeUser($itemtype, $IDuser);
         echo "<form class='m-form m-form--fit' method='POST' action='". $CFG_GLPI['root_doc'] ."/provider/form' id='mainform'>";
            if(!empty($ID)){
               echo"<input class='form-control m-input' value='update' type='hidden' name='update'>";
               echo"<input class='form-control m-input' value='".$ID."' type='hidden' name='id'>";
            }else{
                  echo"<input class='form-control m-input' value='add' type='hidden' name='add'>";
            }
            echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);
            echo "<div class='m-portlet__body'>";
               
               echo "<div class='form-group m-form__group row'>";
                  echo "<label class='col-lg-2 col-form-label'>".__("Dashboard")."</label>";
                  echo "<div class='col-lg-3'>";
                     PluginServicesDashboard::dropdown(['name'=> 'dashboards_dashboards_id', 'value'=> '']);
                  echo "</div>";   

                  echo "<label class='col-lg-2 col-form-label'>".__("Table")."</label>";
                  echo "<div class='col-lg-3'>";
                     $this->getAllTable([
                        'display_emptychoice' => true,
                        'rand'                => $rand,
                     ]);
                     // PluginServicesDropdown::showFromArray('itemtype', $this->getItem(), [
                     //    'display_emptychoice' => true,
                     //    'rand'                => $rand,
                     //    'value'               => 1
                     // ]);
                  echo "</div>";
               echo "</div>";
               echo "<div class='form-group m-form__group row'>";
                  echo "<label class='col-lg-2 col-form-label'>".__("Type")."</label>";
                  echo "<div class='col-lg-3'>";
                  PluginServicesDropdown::showFromArray('widgettype', $this->getWidgetype(), [
                     'display_emptychoice' => true,
                     'rand'                => $rand,
                     'value'               => 1
                  ]);
                  echo "</div>";
                  echo "<label class='col-lg-2 col-form-label'>".__("Field")."</label>";
                  echo "<div class='col-lg-3'>";
                  PluginServicesDropdown::showFromArray('field', $this->getAttributItem(), [
                     'display_emptychoice' => true,
                     'rand'                => $rand,
                     'value'               => 1
                  ]);
                  echo "</div>";
               echo "</div>";
               echo "<div class='form-group m-form__group row'>";
                  echo "<label class='col-lg-2 col-form-label'>".__("Background color")."</label>";
                  echo "<div class='col-lg-3'>";
                     PluginServicesHtml::showColorField('color', [
                        'rand'  => $rand,
                        // 'value' => $color,
                     ]);
                  echo "</div>";

                  echo "<div class='col-lg-3'>";
                  echo "</div>";
               echo "</div>";
               echo '<div class="m-form__actions m-form__actions--solid m-form__actions--right">
                        <button type="submit" class="btn btn-brand btn-sm">Sauvegarder</button>
                     </div>';
            echo "</div>";

         echo "</form>"; // .card.display-widget-form
      // echo "</div>";
      echo Html::css('assets/css/customer.glpi.form.css');
      echo "
            <style>
               .default_dash{
                  padding: 1em 1.4em;
                  border: 1px solid #dddddd;
                  background: #FFF;
                  box-shadow: 0px 3px 1px #dfdfdf;
                  border-radius: 3px;
                  width: 90%;
                  margin: auto;
               }
            </style>
         ";
   }

   /**
    * Display a mini form fo adding a new dashboard
    *
    * @return void (display)
    */
    public function displayAddDashboardForm() {
      $rand = mt_rand();
      // $dashboard = new PluginServicesDashboard();
      // $dashboard->showForm();
      echo "<form class='card no-shadow display-add-dashboard-form'>";

      echo "<div class='field'>";
      echo "<label for='title_$rand'>".__("Title")."</label>";
      echo "<div>";
      echo PluginServicesHtml::input('title', ['id' => "title_$rand"]);
      echo "</div>";
      echo "</div>"; // .field

      echo Html::submit(_x('button', "Add"), [
         'class' => 'submit vsubmit submit-new-dashboard'
      ]);

      echo "</form>"; // .card.display-widget-form
   }

   

   public function showForm($ID=0, $options = []) {
    
      
      Session::checkCentralAccess();
      $default = Glpi\Dashboard\Grid::getDefaultDashboardForMenu('helpdesk');
      // print_r($default);
      // Redirect to "/front/ticket.php" if no dashboard found
      if ($default == "") {
         Html::redirect($CFG_GLPI["root_doc"] . "/ticket");
      }

      PluginServicesHtml::headerDash(__('Helpdesk Dashboard'), $_SERVER['PHP_SELF'], "helpdesk", "dashboard");
      // Html::header(__('Helpdesk Dashboard'), $_SERVER['PHP_SELF'], "helpdesk", "dashboard");
      
      $grid = new self();
      $grid_ticket = new PluginServicesGrid_Ticket();
      // $grid_ticket->showForm();
      // $this->displayConfigForm();
      $grid->showDefault();
      PluginServicesHtml::footerDash();
   }

   public function dropdownItemtype(array $list_item = []){

      $rand = mt_rand();
      $list_item = [
         'Ticket',
         'User',
         'Group'
      ];
      echo "<form class='card no-shadow display-add-item-form'>";
         echo "<div class='field'>";
         echo "<label for='dropdown_card_id$rand'>".__("Itemtype")."</label>";
         echo "<div>";
         \Dropdown::showFromArray('card_id', $list_item, [
            'display_emptychoice' => true,
            'rand'                => $rand,
            'value'               => ''
         ]);
         echo "</div>";
         echo "</div>"; // .field

         echo \Html::submit(_x('button', "Add"), [
            'class' => 'submit submit-new-dashboard'
         ]);
      echo "</form>";

   }


   /**
    * Display mini configuration form to add or edit a widget
    *
    * @param array $params with these keys:
    * - int    'gridstack_id': unique identifier of the card
    * - int    'x': position in the grid
    * - int    'y: position in the grid
    * - int    'width': size in the grid
    * - int    'height': size in the grid
    * - string 'rand': unique identifier for the dom
    * - string 'action': [display_add_widget|display_edit_widget] current action for the form
    * - array  'card_options': aditionnal options for the card, contains at least:
    *     - string 'card_id': identifier return by @see self::getAllDasboardCards
    *     - string 'color'
    *
    * @return void
    */
   public function displayWidgetForm(array $params = []) {
      
      $rand = mt_rand();
      $gridstack_id = $params['gridstack_id'] ?? "";
      $old_id       = $gridstack_id;
      $x            = (int) ($params['x'] ?? 0);
      $y            = (int) ($params['y'] ?? 0);
      $width        = (int) ($params['width'] ?? 2);
      $height       = (int) ($params['height'] ?? 2);
      $cardopt      = $params['card_options'] ?? ['color' => "#FAFAFA"];
      $card_id      = $cardopt['card_id'] ?? "";
      $widgettypes  = Widget::getAllTypes();
      $widgettype   = $cardopt['widgettype'] ?? "";
      $widget_def   = $widgettypes[$widgettype] ?? [];
      $use_gradient = $cardopt['use_gradient'] ?? 0;
      $limit        = $cardopt['limit'] ?? 7;
      $color        = $cardopt['color'];
      $edit         = $params['action'] === "display_edit_widget";
      $rand         = $params['rand'] ?? mt_rand();
      $cards        = $this->getAllDasboardCards();
      $card         = $cards[$card_id] ?? [];
      // print_r($cards);
      // return ;
      // append card id to options
      if (!isset($cardopt['card_id'] )) {
         $cardopt['card_id'] = $card_id;
      }
      $list_cards = [];
      // print_r($widgettypes);
      array_walk($cards, function($data, $index) use (&$list_cards) {
         
         $group = $data['group'] ?? __("others");
         $list_cards[$group][$index] = $data['label'] ?? $data['itemtype']::getTypeName();
      });

      echo "<form class='card no-shadow display-widget-form'>";

      echo "<div class='field'>";
      echo "<label for='color_color$rand'>".__("Background color")."</label>";
      echo "<div>";
      \Html::showColorField('color', [
         'rand'  => $rand,
         'value' => $color,
      ]);
      echo "</div>";
      echo "</div>"; // .field

      echo "<div class='field'>";
      echo "<label for='dropdown_card_id$rand'>".__("Data")."</label>";
      echo "<div>";
      \Dropdown::showFromArray('card_id', $list_cards, [
         'display_emptychoice' => true,
         'rand'                => $rand,
         'value'               => $card_id
      ]);
      echo "</div>";
      echo "</div>"; // .field

      // display widget list
      $displayed = "";
      if (!$edit) {
         $displayed = "style='display: none'";
      }
      echo "<div class='field widgettype_field' $displayed>";
      echo "<label>".__("Widget")."</label>";
      echo "<div class='widget-list'>";
      foreach (Widget::getAllTypes() as $key => $current) {
         $selected = '';
         if ($key === $widgettype) {
            $selected = 'checked';
         }
         $w_diplayed = "";
         if ($edit && isset($card['widgettype']) && in_array($key, $card['widgettype'])) {
            $w_diplayed = "style='display: inline-block;'";
         }
         echo "<input type='radio'
                      {$selected}
                      class='widget-select'
                      name='widgettype'
                      id='widgettype_{$key}_{$rand}'
                      value='{$key}'>
               <label for='widgettype_{$key}_{$rand}' {$w_diplayed}>
                  <div>{$current['label']}</div>
                  <img src='{$current['image']}'>
               </label>";
      }
      echo "</div>";
      echo "</div>"; // .field

      // display checkbox to use gradient palette or not
      $gradient_displayed = "";
      if (!$edit || !isset($widget_def['gradient']) || !$widget_def['gradient']) {
         $gradient_displayed = "style='display: none'";
      }
      echo "<div class='field gradient_field' $gradient_displayed>";
      echo "<label for='check_$rand'>".__("Use gradient palette")."</label>";
      echo "<div>";
      \Html::showCheckbox([
         'label'   => "&nbsp;",
         'name'    => "use_gradient",
         'id'      => "check_$rand",
         'checked' => $use_gradient,
      ]);
      echo "</div>";
      echo "</div>"; // .field

      // show limit dropdown
      $limit_displayed = "";
      if (!$edit || !isset($widget_def['limit']) || !$widget_def['limit']) {
         $limit_displayed = "style='display: none'";
      }
      echo "<div class='field limit_field' $limit_displayed>";
      echo "<label for='dropdown_limit$rand'>".__("Limit number of data")."</label>";
      echo "<div>";
      \Dropdown::showNumber('limit', [
         'value' => $limit,
         'rand'  => $rand,
      ]);
      echo "</div>";
      echo "</div>"; // .field

      $class_submit = "add-widget";
      $label_submit = _x('button', "Add");
      if ($edit) {
         $class_submit = "edit-widget";
         $label_submit = _x('button', "Update");
      }

      // manage autoescaping
      if (isset($cardopt['markdown_content'])) {
         $cardopt['markdown_content'] = \Html::cleanPostForTextArea($cardopt['markdown_content']);
      }

      echo \Html::submit($label_submit, [
         'class' => 'submit '.$class_submit
      ]);

      echo \Html::hidden('gridstack_id', ['value' => $gridstack_id]);
      echo \Html::hidden('old_id', ['value' => $old_id]);
      echo \Html::hidden('x', ['value' => $x]);
      echo \Html::hidden('y', ['value' => $y]);
      echo \Html::hidden('width', ['value' => $width]);
      echo \Html::hidden('height', ['value' => $height]);
      echo \Html::hidden('card_options', ['value' => json_encode($cardopt, JSON_HEX_APOS | JSON_HEX_QUOT)]);

      echo "</form>"; // .card.display-widget-form
   }


   /**
    * Display a mini form for embedding current dashboard in another application.
    * Also, display a select for sharing current dashboard to another users/groups/entities/profiles
    *
    * @return void
    */
   public function displayEmbedForm() {
      global $CFG_GLPI;

      $entities_id  = $_SESSION['glpiactive_entity'];
      $is_recursive = $_SESSION['glpiactive_entity_recursive'];
      $token        = self::getToken($this->current, $entities_id, $is_recursive);

      $embed_url    = $CFG_GLPI['url_base'].
         "/front/central.php?embed&dashboard=".$this->current.
         "&entities_id=$entities_id".
         "&is_recursive=$is_recursive".
         "&token=$token";

      echo "<label>".__("Embed in another application")."</label><br>";
      echo "<fieldset class='embed_block'>";
      echo __("Direct link");
      echo "<div class='copy_to_clipboard_wrapper'>";
      echo \Html::input('direct_link', [
         'value'    => $embed_url,
         'style'    => 'width: calc(100% - 38px)'
      ]);
      echo "</div><br>";

      $iframe = "<iframe src='$embed_url' frameborder='0' width='800' height='600' allowtransparency></iframe>";
      echo __("Iframe");
      echo "<div class='copy_to_clipboard_wrapper'>";
      echo \Html::input('iframe_code', [
         'value'    => $iframe,
         'style'    => 'width: calc(100% - 38px)'
      ]);
      echo "</div>";
      echo "</fieldset><br>";

      $this->displayEditRightsForm();
   }


   /**
    * Display a mini form for sharing current dashboard to another users/groups/entities/profiles.
    *
    * @return void
    */
   public function displayEditRightsForm() {
      self::loadAllDashboards();
      $rand   = mt_rand();
      $values = [];

      echo "<form class='card no-shadow display-rights-form'>";

      $profiles_value = self::$all_dashboards[$this->current]['rights']['profiles_id'] ?? [];
      $profile_item = new \Profile;
      $profiles = $profile_item->find();
      $profiles_items = [];
      foreach ($profiles as $profile) {
         $new_key = 'profiles_id-'.$profile['id'];
         $profiles_items[$new_key] = $profile['name'];
         if (in_array($profile['id'], $profiles_value)) {
            $values[$new_key] = $new_key;
         }
      }

      $entities_value = self::$all_dashboards[$this->current]['rights']['entities_id'] ?? [];
      $entity_item = new \Entity;
      $entities = $entity_item->find(getEntitiesRestrictCriteria(\Entity::getTable()));
      $entities_items = [];
      foreach ($entities as $entity) {
         $new_key = 'entities_id-'.$entity['id'];
         $entities_items[$new_key] = $entity['completename'];
         if (in_array($entity['id'], $entities_value)) {
            $values[$new_key] = $new_key;
         }
      }

      $users_value = self::$all_dashboards[$this->current]['rights']['users_id'] ?? [];
      $users = iterator_to_array(\User::getSqlSearchResult(false));
      $users_items = [];
      foreach ($users as $user) {
         $new_key = 'users_id-'.$user['id'];
         $users_items[$new_key] = $user['name'];
         if (in_array($user['id'], $users_value)) {
            $values[$new_key] = $new_key;
         }
      }

      $groups_value = self::$all_dashboards[$this->current]['rights']['groups_id'] ?? [];
      $group_item = new \Group;
      $groups = $group_item->find(getEntitiesRestrictCriteria(\Group::getTable()));
      $groups_items = [];
      foreach ($groups as $group) {
         $new_key = 'groups_id-'.$group['id'];
         $groups_items[$new_key] = $group['name'];
         if (in_array($group['id'], $groups_value)) {
            $values[$new_key] = $new_key;
         }
      }

      $possible_rights = [
         _n("Profile", "Profiles", 1) => $profiles_items,
         _n("Entity", "Entities", 1)  => $entities_items,
         _n("User", "Users", 1)       => $users_items,
         _n("Group", "Groups", 1)     => $groups_items,
      ];

      echo "<label for='dropdown_rights_id$rand'>".
           __("Or share the dashboard to these target objects:").
           "</label><br>";
      \Dropdown::showFromArray('rights_id', $possible_rights, [
         'values'   => $values,
         'multiple' => true,
         'rand'     => $rand,
         'width'    => '100%'
      ]);
      echo "<br><br>";

      echo "<a href='#' class='vsubmit save_rights'>".__("Save")."</a>";

      \Html::closeForm(true);
   }


   /**
    * Return the html for the given card_id
    *
    * @param string $card_id identifier return by @see self::getAllDasboardCards
    * @param array $card_options contains these keys:
    * - array 'args':
    *    - string 'gridstack_id' unique identifier of the card in the grid, used to return html by cache
    *    - bool 'force' if true, cache will be bypassed
    *    - bool 'embed' is the dashboard emebeded or not
    *
    * @return string html of the card
    */
   public function getCardHtml(string $card_id = "", array $card_options = []): string {
      global $GLPI_CACHE;

      $gridstack_id = $card_options['args']['gridstack_id'] ?? $card_id;
      $dashboard    = $card_options['dashboard'] ?? "";

      // manage cache
      $use_cache =
         !($card_options['args']['force'] ?? $card_options['force'] ?? false)
         && $_SESSION['glpi_use_mode'] != Session::DEBUG_MODE;
      $cache_key    = "dashboard_card_".$dashboard;
      $cache_age    = 40;
      if ($use_cache) {
         // browser cache
         if (GLPI_AJAX_DASHBOARD) {
            header_remove('Pragma');
            header('Cache-Control: public');
            header('Cache-Control: max-age=' . $cache_age);
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_age));
         }

         // server cache
         if ($GLPI_CACHE->has($cache_key)) {
            $dashboard_cards = $GLPI_CACHE->get($cache_key);
            if (isset($dashboard_cards[$gridstack_id])) {
               return (string) $dashboard_cards[$gridstack_id];
            }
         }
      }

      $notfound_html = "<div class='empty-card card-warning '>
         <i class='fas fa-exclamation-triangle'></i>".
         __('empty card !')."
      </div>";

      // retrieve card
      $html  = "";
      $start = microtime(true);
      $cards = $this->getAllDasboardCards();
      if (!isset($cards[$card_id])) {
         return $notfound_html;
      }
      $card  = $cards[$card_id];
      // print_r($card['provider']);
      
      // call provider to retrieve data
      if (isset($card['provider'])) {
         $provider_args = ($card['args'] ?? []) + [
            'params' => [
               'label' => $card['label'] ?? ""
            ]
         ];
         $widget_args = call_user_func_array($card['provider'], $provider_args);
      }
            
      $widget_args = array_merge($widget_args ?? [], $card_options['args'] ?? []);

      // call widget function to construct html
      $all_widgets = Widget::getAllTypes();
      $widgettype  = $card_options['args']['widgettype'] ?? "";
      $widgetfct   = $all_widgets[$widgettype]['function'] ?? "";
      if (strlen($widgetfct)) {
         // clean urls in embed mode
         if (isset($card_options['embed']) && $card_options['embed']) {
            unset($widget_args['url']);

            if (isset($widget_args['data'])) {
               $unset_url = function (&$array) use(&$unset_url) {
                  unset($array['url']);
                  foreach ($array as &$value) {
                     if (is_array($value)) {
                        $unset_url($value, 'url');
                     }
                  }
               };
               $unset_url($widget_args['data']);
            }
         }

         // call widget function
         $html = call_user_func($widgetfct, $widget_args);
      }

      // display a warning for empty card
      if (strlen($html) === 0) {
         return $notfound_html;
      }

      $execution_time = round(microtime(true) - $start, 3);

      // store server cache
      if (strlen($dashboard)) {
         $dashboard_cards = $GLPI_CACHE->get($cache_key) ?? [];
         $dashboard_cards[$gridstack_id] = $html;
         $GLPI_CACHE->set($cache_key, $dashboard_cards, new \DateInterval("PT".$cache_age."S"));
      }

      if ($_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE) {
         $html.= <<<HTML
         <span class='debug-card'>
            {$execution_time}s
         </span>
HTML;
      }

      return $html;
   }


   /**
    * Return all itemtypes possible for constructing cards.
    * User in @see self::getAllDasboardCards
    *
    * @return array [itemtype1, itemtype2]
    */
   protected function getMenuItemtypes(): array {
      $menu_itemtypes = [];
      $exclude   = [
         'Config',
      ];

      $menu = \Html::getMenuInfos();
      array_walk($menu, function($firstlvl) use (&$menu_itemtypes) {
         $key = $firstlvl['title'];
         if (isset($firstlvl['types'])) {
            $menu_itemtypes[$key] = array_merge($menu_itemtypes[$key] ?? [], $firstlvl['types']);
         }
      });

      foreach ($menu_itemtypes as &$firstlvl) {
         $firstlvl = array_filter($firstlvl, function($itemtype) use ($exclude) {
            if (in_array($itemtype, $exclude)
               || !is_subclass_of($itemtype, 'CommonDBTM')) {
               return false;
            }
            $testClass = new \ReflectionClass($itemtype);
            return !$testClass->isAbstract();
         });
      }

      return $menu_itemtypes;
   }


   /**
    * Construct catalog of all possible cards addable in a dashboard.
    *
    * @return array
    */
   public function getAllDasboardCards(): array {
      global $GLPI_CACHE, $CFG_GLPI;
      $add_filters_fct = function($itemtable) {
         $DB = DBConnection::getReadConnection();
         $add_filters = [];
         if ($DB->fieldExists($itemtable, "ititlcategories_id")) {
            $add_filters[] = "itilcategory";
         }
         if ($DB->fieldExists($itemtable, "requesttypes_id")) {
            $add_filters[] = "requesttype";
         }
         if ($DB->fieldExists($itemtable, "locations_id")) {
            $add_filters[] = "location";
         }
         if ($DB->fieldExists($itemtable, "manufacturers_id")) {
            $add_filters[] = "manufacturer";
         }
         if ($DB->fieldExists($itemtable, "groups_id_tech")) {
            $add_filters[] = "group_tech";
         }
         if ($DB->fieldExists($itemtable, "users_id_tech")) {
            $add_filters[] = "user_tech";
         }

         return $add_filters;
      };

      $cards = [];
      global  $DB;
      $result = $DB->listTables();
      $res = [];
      
      while ($line = $result->next()) {
         $itemtype = getItemTypeForTable($line['TABLE_NAME']);
         if ((getItemForItemtype($itemtype) instanceof CommonDBRelation)) {
            continue;
         }
         if (class_exists( $itemtype )) {
            $res[$itemtype] = $itemtype;
         }
      }
      $menu_itemtypes = $this->getMenuItemtypes();
      // foreach ($menu_itemtypes as $itemtypes) {
         foreach ($res as $itemtype) {
            $clean_itemtype = str_replace('\\', '_', $itemtype);
            $data = $this->getAttributItem(getTableForItemType($itemtype));
            foreach ($data as $key => $fields) {
               $cards["bn_count_$clean_itemtype".'_'.$fields] = [
                  'widgettype' => ['bigNumber', 'multipleNumber', 'pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                  // 'group'      => $firstlvl,
                  'itemtype'   => "\\$itemtype",
                  'label'      => sprintf(__("%s"), $itemtype::getTypeName()).'*'.$fields,
                  'provider'   => "PluginServicesProvider::bigNumber$itemtype",
               ];
            }
            
         }
      // }

      // add multiple width for Assets itemtypes grouped by their foreign keys
      // print_r($CFG_GLPI['asset_types']);
      $assets = array_merge($CFG_GLPI['asset_types'], ['Software']);
      foreach ($assets as $itemtype) {
         $fk_itemtypes = [
            'State',
            'User',
            'Entity',
            'Manufacturer',
            'Location',
         ];

         if (class_exists($itemtype.'Type')) {
            $fk_itemtypes[] = $itemtype.'Type';
         }
         if (class_exists($itemtype.'Model')) {
            $fk_itemtypes[] = $itemtype.'Model';
         }

         foreach ($fk_itemtypes as $fk_itemtype) {
            $label = sprintf(
               __("%s by %s"),
               $itemtype::getTypeName(Session::getPluralNumber()),
               $fk_itemtype::getFieldLabel()
            );

            $cards[$itemtype."_".$fk_itemtype] = [
               'widgettype' => ['multipleNumber', 'pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar', 'bigNumber'],
               'itemtype'   => "\\Computer",
               'group'      => __('Assets'),
               'label'      => $label,
               'provider'   => "Glpi\\Dashboard\\Provider::multipleNumber".$itemtype."By".$fk_itemtype
            ];
         }
      }

      $tickets_cases = [
         'late'               => __("Late tickets"),
         'waiting_validation' => __("Ticket waiting for your approval"),
         'notold'             => __('Not solved tickets'),
         'incoming'           => __("New tickets"),
         'waiting'            => __('Pending tickets'),
         'assigned'           => __('Assigned tickets'),
         'planned'            => __('Planned tickets'),
         'solved'             => __('Solved tickets'),
         'closed'             => __('Closed tickets'),
      ];
      foreach ($tickets_cases as $case => $label) {
         $cards[$case] = [
            'widgettype' => ["bigNumber", 'multipleNumber', 'pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
            'itemtype'   => "\\Ticket",
            'group'      => __('Assistance'),
            'label'      => sprintf(__("Number of %s"), $label),
            'provider'   => "Glpi\\Dashboard\\Provider::nbTicketsGeneric",
            'args'       => [
               'case' => $case,
            ]
         ];

         $cards[$case] = [
            'widgettype' => ["searchShowList", "bigNumber"],
            'itemtype'   => "\\Ticket",
            'group'      => __('Assistance'),
            'label'      => sprintf(__("List of %s"), $label),
            'provider'   => "Glpi\\Dashboard\\Provider::nbTicketsGeneric",
            'args'       => [
               'case' => $case,
            ]
         ];
      }

      // add specific ticket's cases
      $cards["nb_opened_ticket"] = [
         'widgettype' => ['line', 'bigNumber', 'area', 'bar', 'multipleNumber', 'pie', 'donut', 'halfpie', 'halfdonut', 'hbar'],
         'itemtype'   => "\\Ticket",
         'group'      => __('Assistance'),
         'label'      => __("Number of tickets by month"),
         'provider'   => "\\PluginServicesProvider::ticketsOpened"
      ];

      $cards["ticket_evolution"] = [
         'widgettype' => ['lines', 'areas', 'bars', 'stackedbars'],
         'itemtype'   => "\\Ticket",
         'group'      => __('Assistance'),
         'label'      => __("Evolution of ticket in the past year"),
         'provider'   => "Glpi\\Dashboard\\Provider::getTicketsEvolution"
      ];

      $cards["status"] = [
         'widgettype' => ['lines', 'areas', 'bars', 'stackedbars'],
         'itemtype'   => "\\Ticket",
         'group'      => __('Assistance'),
         'label'      => __("Tickets status by month"),
         'provider'   => "Glpi\\Dashboard\\Provider::getTicketsStatus"
      ];

      $cards["ticket_times"] = [
         'widgettype' => ['lines', 'areas', 'bars', 'stackedbars'],
         'itemtype'   => "\\Ticket",
         'group'      => __('Assistance'),
         'label'      => __("Tickets times (in hours)"),
         'provider'   => "Glpi\\Dashboard\\Provider::averageTicketTimes"
      ];

      foreach ([
         'ITILCategory' => __("Top ticket's categories"),
         'Entity'       => __("Top ticket's entities"),
         'RequestType'  => __("Top ticket's request types"),
         'Location'     => __("Top ticket's locations"),
      ] as $itemtype => $label) {
         $cards["top_ticket_$itemtype"] = [
            'widgettype' => ['pie', 'donut', 'halfpie', 'halfdonut', 'multipleNumber', 'bar', 'hbar'],
            'itemtype'   => "\\Ticket",
            'group'      => __('Assistance'),
            'label'      => $label,
            'provider'   => "Glpi\\Dashboard\\Provider::multipleNumberTicketBy$itemtype"
         ];
      }

      foreach ([
         'user_requester'  => __("Top ticket's requesters"),
         'group_requester' => __("Top ticket's requester groups"),
         'user_observer'   => __("Top ticket's observers"),
         'group_observer'  => __("Top ticket's observer groups"),
         'user_assign'     => __("Top ticket's assignees"),
         'group_assign'    => __("Top ticket's assignee groups"),
      ] as $type => $label) {
         
         $cards["top_ticket_$type"] = [
            'widgettype' => ['bigNumber','pie', 'donut', 'halfpie', 'halfdonut', 'multipleNumber', 'bar', 'hbar'],
            'itemtype'   => "\\Ticket",
            'group'      => __('Assistance'),
            'label'      => $label,
            'provider'   => "Glpi\\Dashboard\\Provider::nbTicketsActor",
            'args'       => [
               'case' => $type,
            ]
         ];
      }

      $cards["markdown_editable"] = [
         'widgettype'   => ["markdown"],
         'label'        => __("Editable markdown card"),
         'group'        => __('Others'),
         'card_options' => [
            'content' => __("Toggle edit mode to edit content"),
         ]
      ];

      $more_cards = \Plugin::doHookFunction("dashboard_cards");
      if (is_array($more_cards)) {
         $cards = array_merge($cards, $more_cards);
      }

      $GLPI_CACHE->set("dashboards_cards", $cards);

      return $cards;
   }


   function getRights($interface = 'central') {
      return [
         READ   => __('Read'),
         UPDATE => __('Update'),
         CREATE => __('Create'),
         PURGE  => [
            'short' => __('Purge'),
            'long'  => _x('button', 'Delete permanently')
         ]
      ];
   }


   /**
    * Save last dashboard viewed
    *
    * @param string $page current page
    * @param string $dashboard current dashboard
    *
    * @return void
    */
   function setLastDashboard(string $page = "", string $dashboard = "") {
      $_SESSION['last_dashboards'][$page] = $dashboard;
   }


   /**
    * Restore last viewed dashboard
    *
    * @return string the dashboard key
    */
   function restoreLastDashboard():string {
      global $CFG_GLPI;

      $new_key = "";
      $target = $_REQUEST['_target'] ?? $_SERVER['REQUEST_URI'] ?? "";
      if (isset($_SESSION['last_dashboards']) && strlen($target) > 0) {
         $target = str_replace($CFG_GLPI['root_doc'], '', $target);

         if (!isset($_SESSION['last_dashboards'][$target])) {
            return "";
         }

         $new_key   = $_SESSION['last_dashboards'][$target];
         $dashboard = new Dashboard($new_key);
         if (!$dashboard->canViewCurrent()) {
            return "";
         }

         $this->current = $new_key;
      }

      return $new_key;
   }


   /**
    * Retrieve the default dashboard for a specific menu entry
    * First try from session
    * then on config
    * And Fallback on the first dashboard found
    *
    * @param string $menu
    * @param bool $strict if true, do not provide a fallback
    *
    * @return string the dashboard key
    */
   static function getDefaultDashboardForMenu(string $menu = "", bool $strict = false): string {
      $grid = new self;

      if (!$strict) {
         $restored = $grid->restoreLastDashboard();
         if (strlen($restored) > 0) {
            return $restored;
         }
      }

      $config_key = 'default_dashboard_'.$menu;
      $config     = \Config::getConfigurationValues('core', [$config_key]);
      $default    = $config[$config_key] ?? "";
      if (strlen($default)) {
         $dasboard = new Dashboard($default);
         if ($dasboard->load()) {
            return $default;
         }
      }

      // if default not found, return first dashboards
      if (!$strict) {
         self::loadAllDashboards();
         $first_dashboard = array_shift(self::$all_dashboards);
         if (isset($first_dashboard['key'])) {
            return $first_dashboard['key'];
         }
      }

      return "";
   }


   static function dropdownDashboard(string $name = "", array $params = []): string {
      self::loadAllDashboards();
      $can_view_all = $params['can_view_all'] ?? false;

      $options_dashboards = [];
      foreach (self::$all_dashboards as $key => $dashboard) {
         if ($can_view_all
         || self::canViewSpecificicDashboard($key)) {
            $options_dashboards[$key] = $dashboard['name'] ?? $key;;
         }
      }

      return \Dropdown::showFromArray($name, $options_dashboards, $params);
   }
}
