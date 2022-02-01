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

   static function getItemTypeFormURL($itemtype, $full = true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

      if ($plug = isPluginItemType($itemtype)) {
         $item = str_replace('\\', '/', strtolower($plug['class']));
      } else { // Standard case
         $item = strtolower($itemtype);
         if (substr($itemtype, 0, \strlen(NS_GLPI)) === NS_GLPI) {
            $item = str_replace('\\', '/', substr($item, \strlen(NS_GLPI)));
         }
      }
      return "$dir/$item/form";
   }

   /**
    * Cette fonction est naicessaire pour la redirection vers la view  formulaire aprés sa création;
    * elle doit être appeler sur la methode getFormURL() dans toutes les classes custom.
   */
   static function redirectItemTypeFormURL($itemtype, $full = true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

      if ($plug = isPluginItemType($itemtype)) {
         // $dir.= "plugins/services";
         $item = str_replace('\\', '/', strtolower($plug['class']));

      } else { // Standard case
         $item = strtolower($itemtype);
         if (substr($itemtype, 0, \strlen(NS_GLPI)) === NS_GLPI) {
            $item = str_replace('\\', '/', substr($item, \strlen(NS_GLPI)));
         }
      }
      return "$dir/$item/form";
   }

   static function getItemTypeSearchURL($itemtype, $full = true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');

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
      return "$dir/$item/";
   }

   static function getFormURLWithID($id = 0, $full = true, $itemtype) {
      //$itemtype = get_called_class();
      $link     = self::getFormURL($full, $itemtype);
      $link    .= (strpos($link, '/?') ? '&':'/?').'id=' . $id;
      return $link;
   }

   static function getFormURL($full = true, $itemtype) {
      return self::getItemTypeFormURL($itemtype, $full);
   }

}
