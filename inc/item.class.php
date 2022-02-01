

<?php  


use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Item;
use Glpi\Dashboard\Widget;


if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginServicesItem extends Item{

    static $rightname = 'plugin_services_item_cards';
    
    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }
    static function getClassName() {
        return get_called_class();
    }

    static function canCreate() {
        return Session::haveRight(self::$rightname, CREATE);
    }
  
    static function canUpdate() {
        return true;
    }
    static function getType() {
        return "PluginServicesItem";
    }

    static function getTable($classname = null){
        return "glpi_dashboards_items";
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
    
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        
        if ($item->getType() == "PluginServicesItem" && $item->fields['id']) {
            return [
                self::createTabEntry(__("Utilisateurs")),
            ];
        }
        return '';
    }

    // Cette fonction va afficher le contenu des éléments

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $criteria = [
            "is_deleted"=> 0, 
            "as_map"=> 0, 
            "criteria"=> [
                [
                    "link" => "AND", 
                    "field" => 100, 
                    "searchtype" =>"equals", 
                    "value" => $item->fields['id'],
                    "search" => "Rechercher", 
                    "itemtype" => "PluginServicesUser",
                    "start" => 0,
                ],
            ]
          ];
        if ($item->getType() == "PluginServicesItem") {
            switch ($tabnum) {
                case 0:
                    PluginServicesHtml::showList("PluginServicesUser", $criteria);
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
        if ((isset(self::$othertabs[$this->getType()])
            && (!$this->isNewItem() || isset($this->fields['id']) && $this->fields['id']==0))) {
                
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

    function postForm($post) {
        // print_r($post);
        // return ;
        global $CFG_GLPI;
        $rand = mt_rand();
        $item_ = new self();
        if (isset($post["update"])) {
            // $item_->check($post["id"], UPDATE);
            $item_->update($post);
         
        } else if (isset($post["add"])) {
            $post['x'] = 0;
            $post['y'] = 0;
            $post['width'] = 4;
            $post['height'] = 4;
            $post['card_id'] = 'bn_count_'.$post['itemtype'].'_'.$post['field'];
            $post['gridstack_id'] = 'bn_count_'.$post['itemtype'].'_'.$post['field'].'_'.$rand;
            // if (isset($post['itemtype']) && ($post['itemtype']=== "Ticket") && ($post['field'] == 'status')) {
            //     $post['card_id'] = $post['field'];
            //     $post['width'] = 13;
            //     $post['height'] = 5;
            //     $post['gridstack_id'] = 'ticket_'.$post['field'].'_'.$rand;
            // }
            // if (isset($post['itemtype']) && ($post['itemtype']=== "Ticket") && ($post['field'] == 'closedate')) {
            //     $post['card_id'] = 'closed';
            //     $post['gridstack_id'] = 'closed_'.$rand;
            //     $post['width'] = 20;
            //     $post['height'] = 3;
            //     $post['widgettype']   = "searchShowList";
            // }
            // if (isset($post['itemtype']) && ($post['itemtype']=== "Ticket") && ($post['field'] == 'requesttypes_id ')) {
            //     $post['card_id'] = 'top_ticket_user_requester';
            //     $post['gridstack_id'] = 'top_ticket_user_requester_'.$rand;
            // }
            
            $datas = [
                "color" => $post['color'],
                'widgettype' => $post['widgettype'],
                "use_gradient"=> 0,
                "limit" => 7
            ];
            $post['card_options'] = json_encode($datas);
            $post['dashboards_dashboards_id'] = $post['dash'];
            if ($id = $item_->add($post)) {
                Html::back();
                // PluginServicesHtml::redirect($item_->getLinkURL());
            }  
        }
        Html::back();
    }

    static function getAllDashboard($options = []){ 
        global  $DB;
        $dash = new Dashboard();
        $result = $dash->find();
        $res = [];
        foreach($result as $key => $line) { 
            $res[$key] = $line['name'];
        }       
        PluginServicesDropdown::showFromArray("dash", $res, $options);
    }

    static function dropdownDashboard($options = []) {
        $rand = mt_rand();
        self::getAllDashboard([
            'display_emptychoice' => true,
            'rand'                => $rand,
        ]);
    }

    /**
    * This function is used to get The widget of specific field
    *
    * @return list of widget
    */
    static function getWidgetype($options = []){
        $widgettypes  = Widget::getAllTypes();
        $list_widget = [];
        foreach ($widgettypes as $key => $value) {
            $list_widget[$key] = $key;
        }
        PluginServicesDropdown::showFromArray("widgettype", $list_widget, $options);
    }

    static function getListwidget($options = []) {
        $rand = mt_rand();
        self::getWidgetype([
            'display_emptychoice' => true,
            'rand'                => $rand,
        ]);
    }


    static function getAllTable($options = []){ 
        global  $DB;
        $result = $DB->listTables();
        $res = [];
        while ($line = $result->next()) {
           $itemtype = getItemTypeForTable($line['TABLE_NAME']);
           if ((getItemForItemtype($itemtype) instanceof CommonDBRelation)) {
              continue;
           }
  
           if (class_exists( $itemtype )) {
              $res[$itemtype] = $itemtype.'['.getTableForItemType($itemtype).']';
           }
        }
        PluginServicesDropdown::showFromArray("itemtype", $res, $options);
    }

    static function getListItems($options = []) {
        $rand = mt_rand();
        self::getAllTable([
            'display_emptychoice' => true,
            'rand'                => $rand,
         ]);
    }

    static function getColors($options = []){
        $rand = mt_rand();
        PluginServicesHtml::showColorField('color', [
            'rand'  => $rand,
            // 'value' => $color,
         ]);
    }

    // This function is used to get all attribut of the selected item
    static function getAttributTable($options = []){
        $options [] = [
            'display_emptychoice' => true,
            'rand'                => mt_rand(),
        ];
        PluginServicesDropdown::showFromArray("field", [], $options);
    }

    static function getAttributeForTable($tableName = "glpi_tickets") {
        $rand = mt_rand();
        global $DB;
        $options = [];
        $datas = $DB->listFields($tableName);
        $list_datas = [];
        if (is_array($datas) && count($datas)){
            foreach ($datas as $value) {
                $list_datas[$value['Field']] = $value['Field'];
            }
        }
        $options [] = [
            'display_emptychoice' => true,
            'rand'                => $rand,
        ];
        PluginServicesDropdown::showFromArray("field", $list_datas, $options);
    }

    public function showForm($ID = -1, $options = []) {
    
        global $CFG_GLPI;
        $item = new self();
        if(!empty($ID)){
            $item->getFromDB($ID);
            $this->fields = $item->fields;
        }

        $this->initForm($ID, $options);
        // $this->showFormHeader($options);
        $all_fields = [
            [
                'label'=> 'Dashboard',
                'name'=> 'dropdownDashboard',
                'itemtype' => "PluginServicesItem",
                'type'=> 'function',
                'params' => [
                    'name'=> 'name',
                    // 'value'=> $this->fields['dashboards_dashboards_id'],
                    'rand' => mt_rand(),
                    'right' =>  'all',
                    'display_emptychoice' =>  true,
                ]
            ],

            [
                'label'=> 'Table',
                'name'=> 'getListItems',
                'itemtype' => "PluginServicesItem",
                'type'=> 'function',
                'params' => [
                    'name'=> 'itemtype',
                    // 'value'=> $this->fields['card_id'],
                ],
                'events' => [
                    'type'  => ['change'],
                    'input_type' => 'dropdown',
                    'action' => 'setInputData',
                    'input_cible' => 'field',
                    'url' =>   $CFG_GLPI["root_doc"]."/ajax/getListAttribute.php",
                    'params' => [
                        'rand' => mt_rand(),
                        'right' =>  'all',
                        'display_emptychoice' =>  true,
                    ]
                ]
            ],

            [
                'label'=> 'Champ',
                'name'=> 'getAttributTable',
                'type'=> 'function',
                'params' => [
                    'name'=> 'field',
                    'display_emptychoice' => true
                    // 'value'=> $this->fields['gridstack_id'],
                ]
            ],
            [
                'label'=> 'widgetType',
                'name'=> 'getListwidget',
                'itemtype' => "PluginServicesItem",
                'type'=> 'function',
                'params' => [
                    'name'=> 'widgettype',
                    // 'value'=> $this->fields['width'],
                ]
            ],

            [
                'label'=> 'Background Color',
                'name'=> 'getColors',
                'itemtype' => "PluginServicesItem",
                'type'=> 'function',
                'params' => [
                    'name'=> 'color',
                    // 'value'=> $this->fields['height'],
                ]
            ],
            
            // [
            //     'label'=> 'Longueur',
            //     'name'=> 'width',
            //     'type'=> 'number'
            // ],
            // [
            //     'label'=> 'Hauteur',
            //     'name'=> 'height',
            //     'type'=> 'number'
            // ],
            
        ];
        PluginServicesHtml::generateForm($ID, $item, $options, $all_fields);
    }

    public function showList($itemtype, $params){
        PluginServicesHtml::showList($itemtype, $params);
    }

    function rawSearchOptions() {
        global $DB;
    
        $tab = [];
    
        // $tab[] = [
        //     'id'                 => 'common',
        //     'name'               => __('Characteristics')
        // ];
    
        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false
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
          'table'              => 'glpi_plugin_services_companies',
          'field'              => 'name',
          'linkfield'              => 'companies_id',
          'name'               => __('Company'),
          'datatype'           => 'dropdown',
          'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'description',
            'linkfield'              => '',
            'name'               => __('Description'),
            'datatype'           => 'Text',
            'massiveaction'      => false
        ];

        return $tab;
    }
}

?>