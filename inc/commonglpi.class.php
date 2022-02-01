<?php


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  Common GLPI object
**/
class PluginServicesCommonGLPI {


    static function getFormURL($full = true) {
        return PluginServicesToolbox::getItemTypeFormURL(get_called_class(), $full);
    }
  

    static function getFormURLWithID($id = 0, $full = true) {
  
        $itemtype = get_called_class();
        $link     = $itemtype::getFormURL($full);
        $link    .= (strpos($link, '?') ? '&':'?').'id=' . $id;
        return $link;
    }


    
   /**
    * Show tabs content
    *
    * @since 0.85
    *
    * @param array $options parameters to add to URLs and ajax
    *     - withtemplate is a template view ?
    *
    * @return void
   **/
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
    echo "<div class='glpi_tabs ".($this->isNewID($ID)?"new_form_tabs":"")."'>";
    echo "<div id='tabspanel' class='center-h'></div>";
    $onglets     = $this->defineAllTabs($options);
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

}