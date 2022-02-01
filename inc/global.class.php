
<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}
trait PluginServicesGlobal{

    /**
    * This function is used to return the className of an specific item by his plugin
    *
    * @return an itemtype
    */
    public static function getSubItemForItemType($itemtype){
        $plug = new Plugin();
        $list_plug = $plug->find();
        foreach($list_plug as $values){
            $val = class_exists("Plugin".ucfirst($values['directory']).$itemtype) ? "Plugin".ucfirst($values['directory']).$itemtype: $itemtype;
            return $val;
        }
        return false;
    }

}

?>