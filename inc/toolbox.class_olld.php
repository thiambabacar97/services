<?php


use Glpi\Event;
use Glpi\Mail\Protocol\ProtocolInterface;
use Glpi\System\RequirementsManager;
use Laminas\Mail\Storage\AbstractStorage;
use Monolog\Logger;
use Mexitek\PHPColors\Color;
use Psr\Log\InvalidArgumentException;

if (!defined('GLPI_ROOT')) {
   die('Sorry. You can\'t access this file directly');
}


/**
 * Toolbox Class
**/
class PluginServicesToolbox extends Toolbox{

   /**
    * Cette fonction est naicessaire pour la redirection vers la view  formulaire aprés sa création;
    * elle doit être appeler sur la methode getFormURL() dans toutes les classes custom.
   */
   static function getItemTypeFormURL($itemtype, $full = true) {
      global $CFG_GLPI;
      // getItemForItemtype("Ticket");
      $dir = ($full ? $CFG_GLPI['root_doc'] : '');
      if ($plug = isPluginItemType($itemtype)) {
         $item = str_replace('\\', '/', strtolower($plug['class']));
      } else { // Standard case
         $item = strtolower($itemtype);
         if (substr($itemtype, 0, \strlen(NS_GLPI)) === NS_GLPI) {
            $item = str_replace('\\', '/', substr($item, \strlen(NS_GLPI)));
         }
      }
      $plug = substr($itemtype, 6, -(strlen($item)));
      $plugin_name = lcfirst($plug);
      // echo "The item is: ".$itemtype;
      // echo "Et le plugin est :".$plugin_name;
      return "$dir/$plugin_name/$item/form";
      
   }
   
   static function getItemTypeSearchURL($itemtype, $full = true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');
      // print_r($dir);
      
      if ($plug = isPluginItemType($itemtype)) {
         $item = str_replace('\\', '/', strtolower($plug['class']));
      } else { // Standard case
         if ($itemtype == 'Cartridge') {
            $itemtype = 'CartridgeItem';
         }
         if ($itemtype == 'Consumable') {
            $itemtype = 'ConsumableItem';
         }
         $item = strtolower($itemtype);
         if (substr($itemtype, 0, \strlen(NS_GLPI)) === NS_GLPI) {
            $item = str_replace('\\', '/', substr($item, \strlen(NS_GLPI)));
         }
      }
      
      $plug = substr($itemtype, 6, -(strlen($item)));
      $plugin_name = lcfirst($plug);
      
      return "$dir/$plugin_name/$item";
   }

   static function getFormURLWithID($id = 0, $full = true, $itemtype) {
      //$itemtype = get_called_class();

      // print_r($itemtype);

      $link     = self::getFormURL($full, $itemtype);
      $link    .= (strpos($link, '/?') ? '&':'/?').'id=' . $id;
      // echo $link ;
      return $link;
   }

   static function getFormURL($full = true, $itemtype) {
      return self::getItemTypeFormURL($itemtype, $full);
   }

}
