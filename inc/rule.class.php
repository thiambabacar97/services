<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginServicesRule extends Rule{

    static $rightname = 'config';
    
    static function getType() {
        return "PluginServicesRule";
    }

    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }

    static function canCreate() {
        return Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, DELETE]);
    }
    
    static function canUpdate() {
        return Session::haveRightsOr(self::$rightname, [UPDATE]);
    }

    static function getSearchURL($full = true) {
        return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
    }

    static function getTypeName($nb = 0) {
        return _n('Rule', 'Rules', $nb);
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

    static function getTable($classname = null) {
        return "glpi_rules";
    }

    static function getClassName() {
        return get_called_class();
    }

    static function getUsersForGroups($item){
        PluginServicesGroup_User::showForGroup($item);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $relatedListabName = PluginServicesRelatedlist::tabNameForItem($item, $withtemplate);
        $tabNam = [
            self::createTabEntry(__("Log")) 
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
        // Object with class with 'addtabon' attribute
        if ((isset(self::$othertabs[$this->getType()])
            && !$this->isNewItem())) {
                
        foreach (self::$othertabs[$this->getType()] as $typetab) {
            $this->addStandardTab($typetab, $onglets, $options);
        }
        }

        $class = $this->getType();
        return $onglets;
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

    function postForm($post) {
        // this class is use tu add the valuesx
        global $CFG_GLPI;
        $item = new self();
        if (isset($post["update"])) {
            $item->check($post["id"], UPDATE);
            $item->update($post);
            PluginServicesFagoUtils::returnResponse();
        } else if (isset($post["add"])) {    
            $item->check(-1, CREATE, $post);
            if($newID = $item->add($post)){
                PluginServicesFagoUtils::returnResponse($newID);
            }
            PluginServicesFagoUtils::returnResponse();
        }
        PluginServicesHtml::back();
    }
    
    static function dropdown($options = []) {
        return PluginServicesDropdown::show('Rule', $options);
    }

    function showForm($ID = -1, $options = [], $sub_item = "") {
        if (!$sub_item->isNewID($ID)) {
           $sub_item->check($ID, READ);
        } else {
           // Create item
           $sub_item->checkGlobal(UPDATE);
        }
  
        $canedit = $sub_item->canEdit(static::$rightname);
        $rand = mt_rand();
  
        $all_fields = [
           [
              'label'=> 'Name',
              'name'=> 'name',
              'type'=> 'text'
           ],[
              'label'=> 'Logical operator',
              'type'=> 'function',
              'name'=> 'dropdownRulesMatch',
              'params'=> [
                 'name' => 'match',
                 'value' => $sub_item->fields["match"]
              ]
           ],
           [
              'label'=> 'Active',
              'name'=> 'is_active',
              'type'=> 'boolean'
           ],
           [
              'label'=> 'Use rule for',
              'type'=> 'function',
              'name'=> 'dropdownConditions',
              'cond' => $sub_item->useConditions(),
              'params'=> [
                 'name' => 'condition',
                 'value' => $sub_item->fields["condition"]
              ]
           ],[
              'label'=> 'Courte description ',
              'name'=> 'description',
              'type'=> 'text',
              'full'=> true
           ],
           [
              'label'=> 'Description',
              'name'=> 'comment',
              'type'=> 'textarea',
              'full'=> true
           ],[
              'name'=> 'ranking',
              'type'=> 'hidden',
              'value' => get_class($sub_item),
              'cond' => $canedit && !$sub_item->isNewID($ID)
           ],[
              'name'=> 'sub_type',
              'value' => get_class($sub_item),
              'type'=> 'hidden',
              'cond' => $canedit && !$sub_item->isNewID($ID)
           ]
        ];
        PluginServicesHtml::generateForm($ID, $sub_item, $options, $all_fields);
     }

}

?>