<?php


use Glpi\Event;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Entity class
 */
class PluginServicesEntity extends Entity {

    static $rightname = 'entity';

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

    static function getTable($classname = null){
        return "glpi_entities";
    }

    static function getType() {
        return "PluginServicesEntity";
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

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $lastIndex = count(PluginServicesRelatedlist::tabNameForItem($item, $withtemplate));
        PluginServicesRelatedlist::tabcontent($item, $tabnum, $withtemplate);
        switch ($tabnum) {
            case $lastIndex:
                PluginServicesLog::showForitem($item, $withtemplate);
            break;
        }
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $relatedListabName = PluginServicesRelatedlist::tabNameForItem($item, $withtemplate);
        $tabNam = [
            self::createTabEntry(__("Log")) 
        ];
        $tab = array_merge($relatedListabName,  $tabNam);
        return $tab;
    }

    static function getUsersList($item){
        PluginServicesProfile_User::showForEntity($item);
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
            && (!$this->isNewItem() || isset($this->fields['id']) && $this->fields['id']==0))) {
                
            foreach (self::$othertabs[$this->getType()] as $typetab) {
                $this->addStandardTab($typetab, $onglets, $options);
            }
        }

        $class = $this->getType();
        return $onglets;
    }

    public function showList($itemtype, $params){
        PluginServicesHtml::showList($itemtype,  $params);
    }

    static function isNewID($ID) {
        return (($ID < 0) || !strlen($ID));
    }

    function postForm($post) {
        global $DB;
        $dropdown = new self();
        //$slave_entity = new PluginServicesEntity();

        if (isset($_GET['id']) && ($_GET['id'] == 0)) {
            $options = ['canedit' => true,
                        'candel'  => false];
        }

        if (!($dropdown instanceof CommonDropdown)) {
            Html::displayErrorAndDie('');
        }
        if (!$dropdown->canView()) {
            // Gestion timeout session
            Session::redirectIfNotLoggedIn();
            PluginServicesHtml::displayRightError();
        }

        if (isset($post["id"])) {
            $_GET["id"] = $post["id"];
        } else if (!isset($_GET["id"])) {
            $_GET["id"] = -1;
        }
        if (isset($post["update"])) {
            $dropdown->check($post["id"], UPDATE);
            $dropdown->update($post);
            // $params = [
            //     'locations_id' => $post['locations_id'],
            // ];
            
            // $DB->update(
            //     "glpi_plugin_services_entities",
            //     $params,
            //     ['id' => $this->getIdEntity($post['id'], $DB)]
            //  );
            Event::log($post["id"], get_class($dropdown), 4, "setup",
                       //TRANS: %s is the user login
                       sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
            PluginServicesFagoUtils::returnResponse();
         
        }else if (isset($post["add"])) {
            $dropdown->check(-1, CREATE, $post);
            if ($newID = $dropdown->add($post)) {
                // $params = [
                //     'entities_id' => $newID,
                //     'locations_id' => $post['locations_id'],
                // ];
                // $DB->insert("glpi_plugin_services_entities", $params);
                
                if ($dropdown instanceof CommonDevice) {
                    Event::log($newID, get_class($dropdown), 4, "inventory",
                                sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"],
                                        $post["designation"]));
                } else {
                  Event::log($newID, get_class($dropdown), 4, "setup",
                             sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $post["name"]));
               }
               PluginServicesFagoUtils::returnResponse($newID);
            }
            PluginServicesFagoUtils::returnResponse();
        }
        // PluginServicesHtml::back();
    }


    function getIdEntity($id, $DB){
        $iterator = $DB->request(['FROM' => 'glpi_plugin_services_entities', 'WHERE' => ['entities_id' => $id]]);
        if ($data = $iterator->next()) {
            return $data['id'];
        }
        return false;
    }

    static function dropdown($options = []) {
        return PluginServicesDropdown::show('Entity', $options);
    }

    function showForm($ID, $options = []) {
        $this->initForm($ID, $options);
        $all_fields = [
            [
                'label'=> 'Name',
                'name'=> 'name',
                'type'=> 'text'
            ],[
                'name'  => $this->getForeignKeyField(),
                'label' => __('As child of'),
                'type'  => 'parent',
                'list'  => false
            ],[
                'name'  => 'comment',
                'label' => 'Comments',
                'type'  => 'textarea',
                'full' => true
            ],    
        ];

        PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
        $target = $this->getFormURL();
        $jsScript = '
            $(document).ready(function() {
                var tokenurl = "/ajax/generatetoken.php";
                var form = $("#pluginservicesentityform");
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