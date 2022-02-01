<?php

class PluginServicesStat extends \CommonGLPI {

    static $rightname = "plugin_services_stats";
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

    static function canView() {
        return Session::haveRight("plugin_services_user", READ);
    }

    static function canCreate() {
        return Session::haveRight(self::$rightname, CREATE);
    }

    static function canUpdate() {
        return true;
    }

    static function getType() {
        return "User";
    }

    public function showList($itemtype, $params){
        global $CFG_GLPI;
        $default = Glpi\Dashboard\Grid::getDefaultDashboardForMenu('assets');
        
        $dashboard = new Glpi\Dashboard\Grid($default);
        $dashboard->showDefault();
        return ;
        echo'<div class="m-content">
                <div class="row">
                    <div class="col-xl-12 ">
                    <div class="m-portlet m-portlet--tab">
                        <div class="m-portlet__head">
                            <div class="m-portlet__head-caption">
                                <div class="m-portlet__head-title">
                                    <span class="m-portlet__head-icon">
                                        <i class="flaticon-list"></i>
                                    </span>
                                    <h3 class="m-portlet__head-text">
                                        Mes rapports
                                    </h3>
                                </div>
                            </div>
                            <div class="m-portlet__head-tools">
                                
                            </div>
                        </div>
                        <div class="m-portlet__body">';
                            echo $dashboard->showDefault();
                        echo'  
                        </div>
                    </div>
                    </div>
                </div>
            </div>';
    }

}