<?php  
// namespace Glpi\Dashboard;
// use Glpi\Dashboard\Dashboard;
// use Ramsey\Uuid\Uuid;

// if (!defined('GLPI_ROOT')) {
//     die("Sorry. You can't access this file directly");
// }

// class PluginServicesDashboard extends CommonDBTM{

//     static $rightname = "plugin_services";
    
//     static function canView() {
//         return Session::haveRight(self::$rightname, READ);
//     }

//     static function getClassName() {
//         return get_called_class();
//     }

//     static function canCreate() {
//         return Session::haveRight(self::$rightname, CREATE);
//     }

//     static function canUpdate() {
//         return true;
//     }

//     static function getType() {
//         return "PluginServicesDashboard";
//     }

//     static function getTable($classname = null){
//         return "glpi_dashboards_dashboards";
//     }

//     function getLinkURL(){
//         global $CFG_GLPI;
        
//         if (!isset($this->fields['id'])) {
//               return '';
//         }
  
//         $link_item = $this->getFormURL();
//         $link  = $link_item;
//         $link .= (strpos($link,'?') ? '&amp;':'?').'id=' . $this->fields['id'];
//         $link .= ($this->isTemplate() ? "&amp;withtemplate=1" : "");
  
//         return $link;
//     }
  
//     static function getFormURL($full = false) {
//         return PluginServicesToolbox::getItemTypeFormURL(get_called_class(), $full);
//     }
      
//     static function getSearchURL($full = true) {
//         return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
//     }

//     // Cette fonction va afficher les relateds list(nom des onglets) sur le formulaire de company

//     // public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

//     //     if ($item->getType() == "PluginServicesCompany" && $item->fields['id']) {
//     //         return [
//     //             self::createTabEntry(__("Utilisateurs")),
//     //             self::createTabEntry(__("Groupes")),
//     //         ];
//     //     }
//     //     return '';
//     // }

//     // Cette fonction va afficher le contenu des éléments

//     // static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
//     //     $criteria = [
//     //         "is_deleted"=> 0, 
//     //         "as_map"=> 0, 
//     //         "criteria"=> [
//     //             [
//     //                 "link" => "AND", 
//     //                 "field" => 100, 
//     //                 "searchtype" =>"equals", 
//     //                 "value" => $item->fields['id'],
//     //                 "search" => "Rechercher", 
//     //                 "itemtype" => "PluginServicesUser",
//     //                 "start" => 0,
//     //             ],
//     //         ]
//     //       ];
//     //     if ($item->getType() == "PluginServicesCompany") {
//     //         switch ($tabnum) {
//     //             case 0:
//     //                 PluginServicesHtml::showList("PluginServicesUser", $criteria);
//     //             break;
//     //             case 1:
//     //                 PluginServicesHtml::showList("PluginServicesGroup", $criteria);
//     //             break;
//     //         }
//     //     }

//     //     return true;
//     // }
    
//     function showTabsContent($options = []) {

//         // for objects not in table like central
//         if (isset($this->fields['id'])) {
//             $ID = $this->fields['id'];
//         } else {
//             if (isset($options['id'])) {
//                 $ID = $options['id'];
//             } else {
//                 $ID = 0;
//             }
//         }

//         $target         = $_SERVER['PHP_SELF'];
//         $extraparamhtml = "";
//         $withtemplate   = "";
//         if (is_array($options) && count($options)) {
//             if (isset($options['withtemplate'])) {
//                 $withtemplate = $options['withtemplate'];
//             }
//             $cleaned_options = $options;
//             if (isset($cleaned_options['id'])) {
//                 unset($cleaned_options['id']);
//             }
//             if (isset($cleaned_options['stock_image'])) {
//                 unset($cleaned_options['stock_image']);
//             }
//             if ($this instanceof CommonITILObject && $this->isNewItem()) {
//                 $this->input = $cleaned_options;
//                 $this->saveInput();
//                 // $extraparamhtml can be tool long in case of ticket with content
//                 // (passed in GET in ajax request)
//                 unset($cleaned_options['content']);
//             }

//             // prevent double sanitize, because the includes.php sanitize all data
//             $cleaned_options = Toolbox::stripslashes_deep($cleaned_options);

//             $extraparamhtml = "&amp;".Toolbox::append_params($cleaned_options, '&amp;');
//         }
//         echo "<div style='width:100%;' class='glpi_tabs ".($this->isNewID($ID)?"new_form_tabs":"")."'>";
//         echo "<div id='tabspanel' class='center-h'></div>";
//         $onglets     = $this->defineAllTabsFago($options);
//         $display_all = false;
//         if (isset($onglets['no_all_tab'])) {
//             $display_all = false;
//             unset($onglets['no_all_tab']);
//         }

//         if (count($onglets)) {
//             $tabpage = $this->getTabsURL();
//             $tabs    = [];

//             foreach ($onglets as $key => $val) {
//                 $tabs[$key] = ['title'  => $val,
//                                     'url'    => $tabpage,
//                                     'params' => "_target=$target&amp;_itemtype=".$this->getType().
//                                                 "&amp;_glpi_tab=$key&amp;id=$ID$extraparamhtml"];
//             }

//             // Not all tab for templates and if only 1 tab
//             if ($display_all
//                 && empty($withtemplate)
//                 && (count($tabs) > 1)) {
//                 $tabs[-1] = ['title'  => __('All'),
//                                 'url'    => $tabpage,
//                                 'params' => "_target=$target&amp;_itemtype=".$this->getType().
//                                             "&amp;_glpi_tab=-1&amp;id=$ID$extraparamhtml"];
//             }

//             PluginServicesAjax::createTabs('tabspanel', 'tabcontent', $tabs, $this->getType(), $ID,
//                             "horizontal", $options);
//         }
//         echo "</div>";
//     }

//     function defineAllTabsFago($options = []) {
//         global $CFG_GLPI;
//         $onglets = [];
//         // Tabs known by the object
//         // if ($this->isNewItem()) {
//         //    $this->addDefaultFormTab($onglets);
//         // } else {
//         //    $onglets = $this->defineTabs($options);
//         // }

//         // Object with class with 'addtabon' attribute
//         if ((isset(self::$othertabs[$this->getType()])
//             && (!$this->isNewItem() || isset($this->fields['id']) && $this->fields['id']==0))) {
                
//             foreach (self::$othertabs[$this->getType()] as $typetab) {
//                 $this->addStandardTab($typetab, $onglets, $options);
//             }
//         }

//         $class = $this->getType();
//         // if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
//         //     && (!$this->isNewItem() || $this->showdebug)
//         //     && (method_exists($class, 'showDebug')
//         //         || Infocom::canApplyOn($class)
//         //         || in_array($class, $CFG_GLPI["reservation_types"]))) {

//         //       $onglets[-2] = __('Debug');
//         // }
//         return $onglets;
//     }

//     /**
//     * Get departement for a company
//     *
//     * @since 0.84
//     *
//     * @param integer $companies_id Company ID
//     *
//     * @return array
//    **/
//     static function getCompanyDepartement($companies_id) {
//         global $DB;

//         $departements = [];

//         $iterator = $DB->request([
//         'SELECT' => [
//             'glpi_plugin_services_departements.*',
//             // 'glpi_plugin_services_companies.id',
//             // 'glpi_plugin_services_companies.name'
//         ],
//         'FROM'   => self::getTable(),
//         'LEFT JOIN'    => [
//             PluginServicesDepartement::getTable() => [
//                 'FKEY' => [
//                     PluginServicesDepartement::getTable() => 'companies_id',
//                     self::getTable()  => 'id'
//                 ]
//             ]
//         ],
//         'WHERE'        => [
//             'glpi_plugin_services_departements.companies_id' => $companies_id
//         ],
//         'ORDER'        => 'glpi_plugin_services_departements.name'
//         ]);
//         while ($row = $iterator->next()) {
//         $departements[] = $row;
//         }

//         return $departements;
//     }

//     function postForm($post) {
//         print_r($post);
//         return ;
//         global $CFG_GLPI;
//         $item_ = new self();
//         if (isset($post["update"])) {
//             $item_->check($post["id"], UPDATE);
         
//             $item_->update($post);
         
//         } else if (isset($post["add"])) {
            
//             $item_->check(-1, CREATE, $post);
            
//             if ($id = $item_->add($post)) {
                
//                 PluginServicesHtml::redirect($item_->getLinkURL());
//             }   
//         }
//         Html::back();
//     }

//     public function showForm($ID, $options = []) {
    
//         global $CFG_GLPI;
//         $item = new self();
//         if(!empty($ID)){
//             $item->getFromDB($ID);
//             $this->fields = $item->fields;
//         }

//         $this->initForm($ID, $options);
//         // $this->showFormHeader($options);
//         $all_fields = [
//             [
//                 'label'=> 'Key',
//                 'name'=> 'key',
//                 'type'=> 'text',
//             ],
//             [
//                 'label'=> 'Name',
//                 'name'=> 'name',
//                 'type'=> 'text'
//             ],
//             [
//                 'label' => 'Context',
//                 'name'  => "context",
//                 'type'  => 'text',
//             ],
            
            
//         ];
//         PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
//     }

//     public function showList($itemtype, $params){
//         // print_r($itemtype);
//         PluginServicesHtml::showList($itemtype, $params);
//     }


//     function rawSearchOptions() {
//         global $DB;
    
//         $tab = [];
    
//         // $tab[] = [
//         //     'id'                 => 'common',
//         //     'name'               => __('Characteristics')
//         // ];
    
//         $tab[] = [
//             'id'                 => '1',
//             'table'              => $this->getTable(),
//             'field'              => 'name',
//             'name'               => __('Name'),
//             'datatype'           => 'itemlink',
//             'massiveaction'      => false
//         ];
//         $tab[] = [
//             'id'                 => '2',
//             'table'              => $this->getTable(),
//             'field'              => 'id',
//             'name'               => __('ID'),
//             'massiveaction'      => false,
//             'datatype'           => 'number'
//           ];
    
//         $tab[] = [
//           'id'                 => '4',
//           'table'              => $this->getTable(),
//           'field'              => 'key',
//           'name'               => __('Key'),
//           'datatype'           => 'Text',
//           'massiveaction'      => false
//         ];

//         $tab[] = [
//             'id'                 => '5',
//             'table'              => $this->getTable(),
//             'field'              => 'context',
//             'linkfield'              => '',
//             'name'               => __('Context'),
//             'datatype'           => 'Text',
//             'massiveaction'      => false
//         ];
//         return $tab;
//     }

// }

?>