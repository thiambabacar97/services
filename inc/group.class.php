<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}


class PluginServicesGroup extends Group {

    static $rightname = 'group';
    
    static function getType() {
        return "PluginServicesGroup";
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

    static function getTable($classname = null) {
        return "glpi_groups";
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
        return PluginServicesDropdown::show('Group', $options);
    }

    public function showForm($ID=0, $options = []) {
        $this->initForm($ID, $options);
        $all_fields = [
            [
                'label'=> 'Name',
                'name'=> 'name',
                'type'=> 'text'
            ],[
                'label'=> 'Requester',
                'name'=> 'is_requester',
                'type'=> 'boolean'
            ],[
                'label'=> 'Watcher',
                'name'=> 'is_watcher',
                'type'=> 'boolean'
            ],[
                'label'=> 'Assigned to',
                'name'=> 'is_assign',
                'type'=> 'boolean'
            ],[
                'label'=> 'Task',
                'name'=> 'is_task',
                'type'=> 'boolean'
            ],[
                'label'=> 'Can be notified',
                'name'=> 'is_notify',
                'type'=> 'boolean'
            ],[
                'name'  => $this->getForeignKeyField(),
                'label' => __('As child of'),
                'type'  => 'parent',
                'list'  => false
            ],
            [
                'label'=> 'Soci??t??',
                'name'=> 'dropdownCompany',
                'itemtype'=> "PluginServicesCompany",
                'type'=> 'function',
                'params' => [
                    'name'=> 'companies_id',
                    'value' => $this->fields['companies_id'],
                ]
            ],
            [
                'name'  => 'comment',
                'label' => 'Comments',
                'type'  => 'textarea',
                'full' => true
            ]
        ];
        PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
        $target = $this->getFormURL();
        $jsScript = '
            $(document).ready(function() {
                var tokenurl = "/ajax/generatetoken.php";
                var form = $("#pluginservicesgroupform");
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

    public function showList($itemtype, $params){
        PluginServicesHtml::showList($itemtype, $params);
    }

    function rawSearchOptions() {
        $tab = parent::rawSearchOptions();
        $tab[] = [
            'id'                 => '100',
            'table'              => 'glpi_plugin_services_companies',
            'field'              => 'name',
            'linkfield'          => 'companies_id',
            'name'               => __('Soci??t??'),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];
        $tab[] = [
            'id'                 => '101',
            'table'              => 'glpi_plugin_services_departements',
            'field'              => 'name',
            'linkfield'          => 'departements_id',
            'name'               => __('D??partement'),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '102',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => PluginServicesUser::getTypeName(Session::getPluralNumber()),
            'datatype'           => 'dropdown',
            'right'              => 'all',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_users',
                    'joinparams'         => [
                    'jointype'           => 'child'
                    ]
                ]
            ]
        ];
        return $tab;
    }
    
    

}