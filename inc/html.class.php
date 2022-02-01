<?php

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginServicesHtml extends Html {


   static function autocompletionTextField(CommonDBTM $item, $field, $options = []) {
      global $CFG_GLPI;

      $params['name']   = $field;
      $params['value']  = '';
      if (array_key_exists($field, $item->fields)) {
         $params['value'] = $item->fields[$field];
      }
      $params['entity'] = -1;

      if (array_key_exists('entities_id', $item->fields)) {
         $params['entity'] = $item->fields['entities_id'];
      }
      $params['user']   = -1;
      $params['option'] = '';
      $params['type']   = 'text';
      $params['required']  = false;
      $params['disabled']  = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $rand = (isset($params['rand']) ? $params['rand'] : mt_rand());
      $name    = "field_".$params['name'].$rand;

      // Check if field is allowed
      $field_so = $item->getSearchOptionByField('field', $field, $item->getTable());
      $can_autocomplete = array_key_exists('autocomplete', $field_so) && $field_so['autocomplete'];

      $output = '';
      if ($can_autocomplete && $CFG_GLPI["use_ajax_autocompletion"]) {
         $output .=  "<input class='form-control m-input' ".$params['option']." id='text$name' type='{$params['type']}' name='".
                     $params['name']."' value=\"".self::cleanInputText($params['value'])."\"
                     class='autocompletion-text-field'";

         if ($params['required'] == true) {
            $output .= " required='required'";
         }
      
         if ($params['disabled'] == true) {
            $output .= " disabled='disabled'";
         }
         if (isset($params['attrs'])) {
            foreach ($params['attrs'] as $attr => $value) {
               $output .= " $attr='$value'";
            }
         }

         $output .= ">";

         $parameters['itemtype'] = $item->getType();
         $parameters['field']    = $field;

         if ($params['entity'] >= 0) {
            $parameters['entity_restrict']    = $params['entity'];
         }
         if ($params['user'] >= 0) {
            $parameters['user_restrict']    = $params['user'];
         }

         $js = "  $( '#text$name' ).autocomplete({
                        source: '".$CFG_GLPI["root_doc"]."/ajax/autocompletion.php?".Toolbox::append_params($parameters, '&')."',
                        minLength: 3,
                        });";

         $output .= Html::scriptBlock($js);

      } else {
         $output .=  "<input class='form-control m-input' ".$params['option']." type='text' id='text$name' name='".$params['name']."'";
         if ($params['required'] == true) {
            $output .= " required='required'";
         }
         if ($params['disabled'] == true) {
            $output .= " disabled='disabled'";
         }
         $output .=  "value=\"".self::cleanInputText($params['value'])."\">\n";
      }

      if (!isset($options['display']) || $options['display']) {
         echo $output;
      } else {
         return $output;
      }
   }

   static function includeHeader($title = '', $sector = 'none', $item = 'none', $option = '') {
      global $CFG_GLPI, $DB, $PLUGIN_HOOKS;

      // complete title with id if exist
      if (isset($_GET['id']) && $_GET['id']) {
         $title = sprintf(__('%1$s - %2$s'), $title, $_GET['id']);
      }

      // Send UTF8 Headers
      header("Content-Type: text/html; charset=UTF-8");
      // Allow only frame from same server to prevent click-jacking
      header('x-frame-options:SAMEORIGIN');

      // Send extra expires header
      self::header_nocache();
      $previous_encoding = mb_internal_encoding();

      //Set the encoding to UTF-8, so when reading files it ignores the BOM       
      mb_internal_encoding('UTF-8');
   
      //Process the CSS files...
   
      //Finally, return to the previous encoding
      mb_internal_encoding($previous_encoding);
      echo "<!DOCTYPE html>\n";
      echo "<html lang=\"{$CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]}\">";
      echo '<meta charset="utf-8" />';
        //prevent IE to turn into compatible mode...
         echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";

         // auto desktop / mobile viewport
         echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
      echo'<title>FAGO - '.$title.'</title>';
      echo'
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">

      <!--begin::Web font -->
      <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js"></script>
      <script>
         WebFont.load({
            google: {
            "families": ["Poppins:300,400,500,600,700", "Roboto:300,400,500,600,700"]
            },
            active: function() {
            sessionStorage.fonts = true;
            }
         });
         (function(i, s, o, g, r, a, m) {
            i["GoogleAnalyticsObject"] = r;
            i[r] = i[r] || function() {
            (i[r].q = i[r].q || []).push(arguments)
            }, i[r].l = 1 * new Date();
            a = s.createElement(o),
            m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
         })(window, document, "script", "https://www.google-analytics.com/analytics.js", "ga");
         ga("create", "UA-37564768-1", "auto");
         ga("send", "pageview");
      </script>';

      if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax']) {
         Html::requireJs('notifications_ajax');
      }

      echo Html::css('services/public/lib/leaflet.css');
      Html::requireJs('leaflet');

      echo Html::css('services/public/lib/flatpickr.css');
      echo Html::css('services/public/lib/flatpickr/themes/light.css');
      Html::requireJs('flatpickr');

      if ($sector != 'none' || $item != 'none' || $option != '') {
         $jslibs = [];
         if (isset($CFG_GLPI['javascript'][$sector])) {
            if (isset($CFG_GLPI['javascript'][$sector][$item])) {
               if (isset($CFG_GLPI['javascript'][$sector][$item][$option])) {
                  $jslibs = $CFG_GLPI['javascript'][$sector][$item][$option];
               } else {
                  $jslibs = $CFG_GLPI['javascript'][$sector][$item];
               }
            } else {
               $jslibs = $CFG_GLPI['javascript'][$sector];
            }
         }

         if (in_array('planning', $jslibs)) {
            Html::requireJs('planning');
         }

         if (in_array('fullcalendar', $jslibs)) {
            echo Html::css('services/public/lib/fullcalendar.css',
                           ['media' => '']);
            Html::requireJs('fullcalendar');
         }

         if (in_array('gantt', $jslibs)) {
            echo Html::css('services/public/lib/jquery-gantt.css');
            Html::requireJs('gantt');
         }

         if (in_array('kanban', $jslibs)) {
            Html::requireJs('kanban');
         }

         if (in_array('rateit', $jslibs)) {
            echo Html::css('services/public/lib/jquery.rateit.css');
            Html::requireJs('rateit');
         }

         if (in_array('dashboard', $jslibs)) {
            echo Html::scss('services/css/dashboard');
            Html::requireJs('dashboard');
         }

         if (in_array('marketplace', $jslibs)) {
            echo Html::scss('services/css/marketplace');
            Html::requireJs('marketplace');
         }

         if (in_array('rack', $jslibs)) {
            Html::requireJs('rack');
         }

         if (in_array('gridstack', $jslibs)) {
            echo Html::css('services/public/lib/gridstack.css');
            Html::requireJs('gridstack');
         }

         if (in_array('sortable', $jslibs)) {
            Html::requireJs('sortable');
         }

         if (in_array('tinymce', $jslibs)) {
            Html::requireJs('tinymce');
         }

         if (in_array('clipboard', $jslibs)) {
            Html::requireJs('clipboard');
         }

         if (in_array('jstree', $jslibs)) {
            Html::requireJs('jstree');
         }

         if (in_array('charts', $jslibs)) {
            echo Html::css('services/public/lib/chartist.css');
            echo Html::css('services/css/chartists-glpi.css');
            Html::requireJs('charts');
         }

         if (in_array('codemirror', $jslibs)) {
            echo Html::css('services/public/lib/codemirror.css');
            Html::requireJs('codemirror');
         }

         if (in_array('photoswipe', $jslibs)) {
            echo Html::css('services/public/lib/photoswipe.css');
            Html::requireJs('photoswipe');
         }
      }
      if (Session::getCurrentInterface() == "helpdesk") {
         echo Html::css('services/public/lib/jquery.rateit.css');
         Html::requireJs('rateit');
      }
      //file upload is required... almost everywhere.
      Html::requireJs('fileupload');

      // load fuzzy search everywhere
      Html::requireJs('fuzzy');

      // load log filters everywhere
      Html::requireJs('log_filters');
      // AJAX library
      echo Html::script('services/public/lib/base.js');

      // Locales
      $locales_domains = ['glpi' => GLPI_VERSION]; // base domain
      $plugins = Plugin::getPlugins();
      foreach ($plugins as $plugin) {
         $locales_domains[$plugin] = Plugin::getInfo($plugin, 'version');
      }
      if (isset($_SESSION['glpilanguage'])) {
         echo Html::scriptBlock(<<<JAVASCRIPT
            $(function() {
               i18n.setLocale('{$_SESSION['glpilanguage']}');
            });
   JAVASCRIPT
            );
               foreach ($locales_domains as $locale_domain => $locale_version) {
                  $locales_url = $CFG_GLPI['root_doc'] . '/front/locale.php'
                     . '?domain=' . $locale_domain
                     . '&version=' . $locale_version
                     . ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE ? '&debug' : '');
                  $locale_js = <<<JAVASCRIPT
                     $(function() {
                        $.ajax({
                           type: 'GET',
                           url: '{$locales_url}',
                           success: function(json) {
                              i18n.loadJSON(json, '{$locale_domain}');
                           }
                        });
                     });
      JAVASCRIPT;
                  echo Html::scriptBlock($locale_js);
            }
         }

         // layout
         if (CommonGLPI::isLayoutWithMain()
            && !CommonGLPI::isLayoutExcludedPage()) {
            echo Html::script('services/public/lib/scrollable-tabs.js');
         }

         echo Html::css('services/assets/vendors/base/vendors.bundle.css');
         echo Html::css('services/assets/demo/default/base/style.bundle.css');
         echo Html::css('services/assets/vendors/custom/jquery-ui/jquery-ui.bundle.css');
         echo Html::css('services/assets/vendors/custom/jquery-ui/jquery-ui.bundle.rtl.css');
         echo Html::css('services/assets/css/style.fago.css');

         // jquery validation plugin
         
         // echo Html::script('services/assets/jquery_validation/jquery.validate.js');
         // echo Html::script('services/assets/jquery_validation/localization/messages_fr.js');

         // End of Head
         echo "</head>\n";
         self::glpi_flush();
   }

   static private function loadJavascript() {
      global $CFG_GLPI, $PLUGIN_HOOKS;
      //load on demand scripts
      if (isset($_SESSION['glpi_js_toload'])) {
         foreach ($_SESSION['glpi_js_toload'] as $key => $script) {
            if (is_array($script)) {
               foreach ($script as $s) {
                  echo Html::script($s);
               }
            } else {
               echo Html::script($script);
            }
            unset($_SESSION['glpi_js_toload'][$key]);
         }
      }

      //locales for js libraries
      // if (isset($_SESSION['glpilanguage'])) {
      //    // select2
      //    $filename = "public/lib/select2/js/i18n/".
      //                $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2].".js";
      //    if (file_exists(GLPI_ROOT.'/'.$filename)) {
      //       echo Html::script($filename);
      //    }
      // }

      // transfer core variables to javascript side
      echo self::getCoreVariablesForJavascript(true);

      // Some Javascript-Functions which we may need later
      echo Html::script('services/js/common.js');
      self::redefineAlert();
      self::redefineConfirm();

      if (isset($CFG_GLPI['notifications_ajax']) && $CFG_GLPI['notifications_ajax']) {
         $options = [
            'interval'  => ($CFG_GLPI['notifications_ajax_check_interval'] ? $CFG_GLPI['notifications_ajax_check_interval'] : 5) * 1000,
            'sound'     => $CFG_GLPI['notifications_ajax_sound'] ? $CFG_GLPI['notifications_ajax_sound'] : false,
            'icon'      => ($CFG_GLPI["notifications_ajax_icon_url"] ? $CFG_GLPI['root_doc'] . $CFG_GLPI['notifications_ajax_icon_url'] : false),
            'user_id'   => Session::getLoginUserID()
         ];
         $js = "$(function() {
            notifications_ajax = new GLPINotificationsAjax(". json_encode($options) . ");
            notifications_ajax.start();
         });";
         echo Html::scriptBlock($js);
      }

      // add Ajax display message after redirect
      Html::displayAjaxMessageAfterRedirect();

      // Add specific javascript for plugins
      if (isset($PLUGIN_HOOKS['add_javascript']) && count($PLUGIN_HOOKS['add_javascript'])) {
         foreach ($PLUGIN_HOOKS["add_javascript"] as $plugin => $files) {
            $plugin_root_dir = Plugin::getPhpDir($plugin, true);
            $plugin_web_dir  = Plugin::getWebDir($plugin, false);
            if (!Plugin::isPluginActive($plugin)) {
               continue;
            }
            $version = Plugin::getInfo($plugin, 'version');
            if (!is_array($files)) {
               $files = [$files];
            }
            foreach ($files as $file) {
               if (file_exists($plugin_root_dir."/$file")) {
                  echo Html::script("$plugin_web_dir/$file", ['version' => $version]);
               } else {
                  Toolbox::logWarning("$file file not found from plugin $plugin!");
               }
            }
         }
      }

      if (file_exists(GLPI_ROOT."/js/analytics.js")) {
         echo Html::script("js/analytics.js");
      }
   }

   static function helpHeader($title, $url = '') {
      global $CFG_GLPI, $HEADER_LOADED;
      self::includeHeader($title, 'self-service');
      echo Html::css('services/public/lib/chartist.css');
      echo Html::css('services/css/chartists-glpi.css');
      Html::requireJs('charts');


      // call static function callcron() every 5min
      CronTask::callCron();
      // self::displayMessageAfterRedirect();
   }

   static function nullLogin() {
      global $FOOTER_LOADED;

      // Print foot for null page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      if (!isCommandLine()) {  
         self::loadJavascript();

         echo Html::script('services/assets/vendors/base/vendors.bundle.js');
         echo Html::script('services/assets/demo/default/base/scripts.bundle.js');
         echo Html::script('services/assets/vendors/custom/fullcalendar/fullcalendar.bundle.js');
         echo Html::script('services/assets/snippets/custom/pages/user/login.js');
         

         echo "</body></html>";
      }
      closeDBConnections();
   }

   static function nullHeader($title, $url = '') {
      global $HEADER_LOADED;

      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;
      // Print a nice HTML-head with no controls

      // Detect root_doc in case of error
      Config::detectRootDoc();

      // Send UTF8 Headers
      header("Content-Type: text/html; charset=UTF-8");

      // Send extra expires header if configured
      self::header_nocache();

      if (isCommandLine()) {
         return true;
      }

      self::includeHeader($title);

      // Body with configured stuff
      echo "<body class='m--skin- m-header--fixed m-header--fixed-mobile m-aside-left--enabled m-aside-left--skin-dark m-aside-left--fixed m-aside-left--offcanvas m-footer--push m-aside--offcanvas-default'>";
   }

   static function showMassiveActionsFago($options = []) {
      global $CFG_GLPI;

      /// TODO : permit to pass several itemtypes to show possible actions of all types : need to clean visibility management after

      $p['ontop']             = true;
      $p['num_displayed']     = -1;
      $p['fixed']             = true;
      $p['forcecreate']       = false;
      $p['check_itemtype']    = '';
      $p['check_items_id']    = '';
      $p['is_deleted']        = false;
      $p['extraparams']       = [];
      $p['width']             = 550;
      $p['height']            = 400;
      $p['specific_actions']  = [];
      $p['add_actions']       = [];
      $p['confirm']           = '';
      $p['rand']              = '';
      $p['container']         = '';
      $p['display_arrow']     = true;
      $p['title']             = _n('Action', 'Actions', Session::getPluralNumber());
      $p['item']              = false;
      $p['tag_to_send']       = 'common';
      $p['display']           = true;

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      $url = $CFG_GLPI['root_doc']."/ajax/massiveaction.php";
      if ($p['container']) {
         $p['extraparams']['container'] = $p['container'];
      }
      if ($p['is_deleted']) {
         $p['extraparams']['is_deleted'] = 1;
      }
      if (!empty($p['check_itemtype'])) {
         $p['extraparams']['check_itemtype'] = $p['check_itemtype'];
      }
      if (!empty($p['check_items_id'])) {
         $p['extraparams']['check_items_id'] = $p['check_items_id'];
      }
      if (is_array($p['specific_actions']) && count($p['specific_actions'])) {
         $p['extraparams']['specific_actions'] = $p['specific_actions'];
      }
      if (is_array($p['add_actions']) && count($p['add_actions'])) {
         $p['extraparams']['add_actions'] = $p['add_actions'];
      }
      if ($p['item'] instanceof CommonDBTM) {
         $p['extraparams']['item_itemtype'] = $p['item']->getType();
         $p['extraparams']['item_items_id'] = $p['item']->getID();
      }

      // Manage modal window
      if (isset($_REQUEST['_is_modal']) && $_REQUEST['_is_modal']) {
         $p['extraparams']['hidden']['_is_modal'] = 1;
      }

      if ($p['fixed']) {
         $width= '950px';
      } else {
         $width= '95%';
      }

      $identifier = md5($url.serialize($p['extraparams']).$p['rand']);
      $max        = Toolbox::get_max_input_vars();
      $out = '';

      if (($p['num_displayed'] >= 0)
          && ($max > 0)
          && ($max < ($p['num_displayed']+10))) {
         if (!$p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            $out .= "<table class='tab_cadre' width='$width'><tr class='tab_bg_1'>".
                    "<td><span class='b'>";
            $out .= __('Selection too large, massive action disabled.')."</span>";
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               $out .= "<br>".__('To increase the limit: change max_input_vars or suhosin.post.max_vars in php configuration.');
            }
            $out .= "</td></tr></table>";
         }
      } else {
         // Create Modal window on top
         if ($p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
                $out .= "<div id='massiveactioncontent$identifier'></div>";

            if (!empty($p['tag_to_send'])) {
               $js_modal_fields  = "            var items = $('";
               if (!empty($p['container'])) {
                  $js_modal_fields .= '[id='.$p['container'].'] ';
               }
               $js_modal_fields .= "[data-glpicore-ma-tags~=".$p['tag_to_send']."]')";
               $js_modal_fields .= ".each(function( index ) {\n";
               $js_modal_fields .= "              fields[$(this).attr('name')] = $(this).attr('value');\n";
               $js_modal_fields .= "              if (($(this).attr('type') == 'checkbox') && (!$(this).is(':checked'))) {\n";
               $js_modal_fields .= "                 fields[$(this).attr('name')] = 0;\n";
               $js_modal_fields .= "              }\n";
               $js_modal_fields .= "            });";
            } else {
               $js_modal_fields = "";
            }

            $out .= Ajax::createModalWindow('massiveaction_window'.$identifier,
                                            $url,
                                            ['title'           => $p['title'],
                                                  'container'       => 'massiveactioncontent'.$identifier,
                                                  'extraparams'     => $p['extraparams'],
                                                  'width'           => $p['width'],
                                                  'height'          => $p['height'],
                                                  'js_modal_fields' => $js_modal_fields,
                                                  'display'         => false]);
         }
         $out .= "<table class='tab_glpi'><tr>";
         if ($p['display_arrow']) {
            $out .= "<td width='30px'><img src='".$CFG_GLPI["root_doc"]."/pics/arrow-left".
                   ($p['ontop']?'-top':'').".png' alt=''></td>";
         }
         $out .= "<td width='100%' class='left'>";
         $out .= "<a class='vsubmit' ";
         if (is_array($p['confirm'] || strlen($p['confirm']))) {
            $out .= self::addConfirmationOnAction($p['confirm'], "massiveaction_window$identifier.dialog(\"open\");");
         } else {
            $out .= "onclick='massiveaction_window$identifier.dialog(\"open\");'";
         }
         $out .= " href='#modal_massaction_content$identifier' title=\"".htmlentities($p['title'], ENT_QUOTES, 'UTF-8')."\">";
         $out .= $p['title']."</a>";
         $out .= "</td>";

         $out .= "</tr></table>";
         if (!$p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            // Clean selection
            $_SESSION['glpimassiveactionselected'] = [];
         }
      }

      if ($p['display']) {
         echo $out;
         return true;
      } else {
         return $out;
      }
   }

   static function showMassiveActions($options = []) {
      global $CFG_GLPI;

      /// TODO : permit to pass several itemtypes to show possible actions of all types : need to clean visibility management after

      $p['ontop']             = true;
      $p['num_displayed']     = -1;
      $p['fixed']             = true;
      $p['forcecreate']       = false;
      $p['check_itemtype']    = '';
      $p['check_items_id']    = '';
      $p['is_deleted']        = false;
      $p['extraparams']       = [];
      $p['width']             = 550;
      $p['height']            = 400;
      $p['specific_actions']  = [];
      $p['add_actions']       = [];
      $p['confirm']           = '';
      $p['rand']              = '';
      $p['container']         = '';
      $p['display_arrow']     = true;
      $p['title']             = _n('Action', 'Actions', Session::getPluralNumber());
      $p['item']              = false;
      $p['tag_to_send']       = 'common';
      $p['display']           = true;

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      $url = $CFG_GLPI['root_doc']."/ajax/massiveaction.php";
      if ($p['container']) {
         $p['extraparams']['container'] = $p['container'];
      }
      if ($p['is_deleted']) {
         $p['extraparams']['is_deleted'] = 1;
      }
      if (!empty($p['check_itemtype'])) {
         $p['extraparams']['check_itemtype'] = $p['check_itemtype'];
      }
      if (!empty($p['check_items_id'])) {
         $p['extraparams']['check_items_id'] = $p['check_items_id'];
      }
      if (is_array($p['specific_actions']) && count($p['specific_actions'])) {
         $p['extraparams']['specific_actions'] = $p['specific_actions'];
      }
      if (is_array($p['add_actions']) && count($p['add_actions'])) {
         $p['extraparams']['add_actions'] = $p['add_actions'];
      }
      if ($p['item'] instanceof CommonDBTM) {
         $p['extraparams']['item_itemtype'] = $p['item']->getType();
         $p['extraparams']['item_items_id'] = $p['item']->getID();
      }

      // Manage modal window
      if (isset($_REQUEST['_is_modal']) && $_REQUEST['_is_modal']) {
         $p['extraparams']['hidden']['_is_modal'] = 1;
      }

      if ($p['fixed']) {
         $width= '950px';
      } else {
         $width= '95%';
      }

      $identifier = md5($url.serialize($p['extraparams']).$p['rand']);
      $max        = Toolbox::get_max_input_vars();
      $out = '';

      if (($p['num_displayed'] >= 0)
          && ($max > 0)
          && ($max < ($p['num_displayed']+10))) {
         if (!$p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            $out .= "<table class='tab_cadre' width='$width'><tr class='tab_bg_1'>".
                    "<td><span class='b'>";
            $out .= __('Selection too large, massive action disabled.')."</span>";
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               $out .= "<br>".__('To increase the limit: change max_input_vars or suhosin.post.max_vars in php configuration.');
            }
            $out .= "</td></tr></table>";
         }
      } else {
         // Create Modal window on top
         if ($p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
                $out .= "<div id='massiveactioncontent$identifier'></div>";

            if (!empty($p['tag_to_send'])) {
               $js_modal_fields  = "            var items = $('";
               if (!empty($p['container'])) {
                  $js_modal_fields .= '[id='.$p['container'].'] ';
               }
               $js_modal_fields .= "[data-glpicore-ma-tags~=".$p['tag_to_send']."]')";
               $js_modal_fields .= ".each(function( index ) {\n";
               $js_modal_fields .= "              fields[$(this).attr('name')] = $(this).attr('value');\n";
               $js_modal_fields .= "              if (($(this).attr('type') == 'checkbox') && (!$(this).is(':checked'))) {\n";
               $js_modal_fields .= "                 fields[$(this).attr('name')] = 0;\n";
               $js_modal_fields .= "              }\n";
               $js_modal_fields .= "            });";
            } else {
               $js_modal_fields = "";
            }

            $out .= Ajax::createModalWindow('massiveaction_window'.$identifier,
                                            $url,
                                            ['title'           => $p['title'],
                                                  'container'       => 'massiveactioncontent'.$identifier,
                                                  'extraparams'     => $p['extraparams'],
                                                  'width'           => $p['width'],
                                                  'height'          => $p['height'],
                                                  'js_modal_fields' => $js_modal_fields,
                                                  'display'         => false]);
         }
         //width='$width'
         $out .= "<table class='tab_glpi mx-0 mt-3' ><tr>";
         // if ($p['display_arrow']) {
         //    $out .= "<td width='30px'><img src='".$CFG_GLPI["root_doc"]."/pics/arrow-left".
         //           ($p['ontop']?'-top':'').".png' alt=''></td>";
         // }
         $out .= "<td width='100%' class='left'>";
         
         $out .= "<a  id='massiveaction' style='display:none; font-weight: 500; font-size: 12px; color:#000;' class='btn btn-secondary btn-sm my-2 m-btn m-btn--custom m-btn--icon' ";
         if (is_array($p['confirm'] || strlen($p['confirm']))) {
            $out .= self::addConfirmationOnAction($p['confirm'], "massiveaction_window$identifier.dialog(\"open\");");
         } else {
            $out .= "onclick='massiveaction_window$identifier.dialog(\"open\");'";
         }
         // $out .= " href='#modal_massaction_content$identifier' title=\"".htmlentities($p['title'], ENT_QUOTES, 'UTF-8')."\">";
         // $out .= "<i style='font-size:20px;' class='flaticon-cogwheel-1'></i></a>";

         $out .= " href='#modal_massaction_content$identifier' title=\"".htmlentities($p['title'], ENT_QUOTES, 'UTF-8')."\"> <i class='flaticon-cogwheel-2'></i> ";
         $out .= $p['title']."</a>";

         $out .= "</td>";

         $out .= "</tr></table>";
         if (!$p['ontop']
            || (isset($p['forcecreate']) && $p['forcecreate'])) {
            // Clean selection
            $_SESSION['glpimassiveactionselected'] = [];
         }
      }

      if ($p['display']) {
         echo $out;
         return true;
      } else {
         return $out;
      }
   }

   static function showMassiveActionsForSpecifiqueItem($options = []) {
      global $CFG_GLPI;

      /// TODO : permit to pass several itemtypes to show possible actions of all types : need to clean visibility management after

      $p['ontop']             = true;
      $p['num_displayed']     = -1;
      $p['fixed']             = true;
      $p['forcecreate']       = false;
      $p['check_itemtype']    = '';
      $p['check_items_id']    = '';
      $p['is_deleted']        = false;
      $p['extraparams']       = [];
      $p['width']             = 800;
      $p['height']            = 400;
      $p['specific_actions']  = [];
      $p['add_actions']       = [];
      $p['confirm']           = '';
      $p['rand']              = '';
      $p['container']         = '';
      $p['display_arrow']     = true;
      $p['title']             = _n('Action', 'Actions', Session::getPluralNumber());
      $p['item']              = false;
      $p['tag_to_send']       = 'common';
      $p['display']           = true;

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      $url = $CFG_GLPI['root_doc']."/ajax/massiveaction.php";
      if ($p['container']) {
         $p['extraparams']['container'] = $p['container'];
      }
      if ($p['is_deleted']) {
         $p['extraparams']['is_deleted'] = 1;
      }
      if (!empty($p['check_itemtype'])) {
         $p['extraparams']['check_itemtype'] = $p['check_itemtype'];
      }
      if (!empty($p['check_items_id'])) {
         $p['extraparams']['check_items_id'] = $p['check_items_id'];
      }
      if (is_array($p['specific_actions']) && count($p['specific_actions'])) {
         $p['extraparams']['specific_actions'] = $p['specific_actions'];
      }
      if (is_array($p['add_actions']) && count($p['add_actions'])) {
         $p['extraparams']['add_actions'] = $p['add_actions'];
      }
      if ($p['item'] instanceof CommonDBTM) {
         $p['extraparams']['item_itemtype'] = $p['item']->getType();
         $p['extraparams']['item_items_id'] = $p['item']->getID();
      }

      // Manage modal window
      if (isset($_REQUEST['_is_modal']) && $_REQUEST['_is_modal']) {
         $p['extraparams']['hidden']['_is_modal'] = 1;
      }

      if ($p['fixed']) {
         $width= '950px';
      } else {
         $width= '95%';
      }

      $identifier = md5($url.serialize($p['extraparams']).$p['rand']);
      $max        = Toolbox::get_max_input_vars();
      $out = '';

      if (($p['num_displayed'] >= 0)
          && ($max > 0)
          && ($max < ($p['num_displayed']+10))) {
         if (!$p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
            $out .= "<table class='tab_cadre' width='$width'><tr class='tab_bg_1'>".
                    "<td><span class='b'>";
            $out .= __('Selection too large, massive action disabled.')."</span>";
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               $out .= "<br>".__('To increase the limit: change max_input_vars or suhosin.post.max_vars in php configuration.');
            }
            $out .= "</td></tr></table>";
         }
      } else {
         // Create Modal window on top
         if ($p['ontop']
             || (isset($p['forcecreate']) && $p['forcecreate'])) {
                $out .= "<div id='massiveactioncontent$identifier'></div>";

            if (!empty($p['tag_to_send'])) {
               $js_modal_fields  = "            var items = $('";
               if (!empty($p['container'])) {
                  $js_modal_fields .= '[id='.$p['container'].'] ';
               }
               $js_modal_fields .= "[data-glpicore-ma-tags~=".$p['tag_to_send']."]')";
               $js_modal_fields .= ".each(function( index ) {\n";
               $js_modal_fields .= "              fields[$(this).attr('name')] = $(this).attr('value');\n";
               $js_modal_fields .= "              if (($(this).attr('type') == 'checkbox') && (!$(this).is(':checked'))) {\n";
               $js_modal_fields .= "                 fields[$(this).attr('name')] = 0;\n";
               $js_modal_fields .= "              }\n";
               $js_modal_fields .= "            });";
            } else {
               $js_modal_fields = "";
            }

            $out .= Ajax::createModalWindow('massiveaction_window'.$identifier,
                                            $url,
                                            ['title'           => $p['title'],
                                                  'container'       => 'massiveactioncontent'.$identifier,
                                                  'extraparams'     => $p['extraparams'],
                                                  'width'           => $p['width'],
                                                  'height'          => $p['height'],
                                                  'js_modal_fields' => $js_modal_fields,
                                                  'display'         => false]);
         }
         //width='$width'
         $out .= "<table class='tab_glpi mx-0 mt-3' ><tr>";
         $out .= "<td width='100%' class='left'>";
         
         $out .= "<a  id='massiveaction'  font-weight: 500; font-size: 12px; color:#000;' class='ml-3 btn bg-secondary btn-sm btn-default' ";
         if (is_array($p['confirm'] || strlen($p['confirm']))) {
            $out .= self::addConfirmationOnAction($p['confirm'], "massiveaction_window$identifier.dialog(\"open\");");
         } else {
            $out .= "onclick='massiveaction_window$identifier.dialog(\"open\");'";
         }
         $out .= " style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;' href='#modal_massaction_content$identifier' title=\"".htmlentities($p['title'], ENT_QUOTES, 'UTF-8')."\"> <i class='flaticon-cogwheel-2'></i> ";
         // $out .= $p['title']."</a>";
         $out .= "</a>";

         $out .= "</td>";

         $out .= "</tr></table>";
         if (!$p['ontop']
            || (isset($p['forcecreate']) && $p['forcecreate'])) {
            // Clean selection
            $_SESSION['glpimassiveactionselected'] = [];
         }
      }

      if ($p['display']) {
         echo $out;
         return true;
      } else {
         return $out;
      }
   }

   static function printPager($start, $numrows, $target, $parameters, $item_type_output = 0, $item_type_output_param = 0, $additional_info = '') {
      global $CFG_GLPI;
      $list_limit = $_SESSION['glpilist_limit'];
      // Forward is the next step forward
      
      $forward = $start+$list_limit;

      // This is the end, my friend
      $end = $numrows-$list_limit;

      // Human readable count starts here

      $current_start = $start+1;

      // And the human is viewing from start to end
      $current_end = $current_start+$list_limit-1;
      if ($current_end > $numrows) {
         $current_end = $numrows;
      }

      // Empty case
      if ($current_end == 0) {
         $current_start = 0;
      }

      // Backward browsing
      if ($current_start-$list_limit <= 0) {
         $back = 0;
      } else {
         $back = $start-$list_limit;
      }

      // Print it

      echo "<div class='col-md-6  col-lg-6  p-0'>";
            echo"<table style='width:100%' class='tab_cadre_pager'>";
               echo"<tr>";
                  if (!empty($item_type_output)
                     && isset($_SESSION["glpiactiveprofile"])
                     && (Session::getCurrentInterface() == "central")) {
                     echo "<td class='tab_bg_2 responsive_hidden' width='30%'>";
                        echo "<form method='GET' id='export' class='d-flex justify-content-start' action='".$CFG_GLPI["root_doc"]."/front/report.dynamic.php'>";
                           echo Html::hidden('item_type', ['value' => $item_type_output]);

                           if ($item_type_output_param != 0) {
                           echo Html::hidden('item_type_param',
                              ['value' => Toolbox::prepareArrayForInput($item_type_output_param)]);
                           }

                           $parameters = trim($parameters, '&amp;');
                           if (strstr($parameters, 'start') === false) {
                           $parameters .= "&amp;start=$start";
                           }

                           $split = explode("&amp;", $parameters);

                           $count_split = count($split);
                           for ($i=0; $i < $count_split; $i++) {
                           $pos    = Toolbox::strpos($split[$i], '=');
                           $length = Toolbox::strlen($split[$i]);
                           echo Html::hidden(Toolbox::substr($split[$i], 0, $pos), ['value' => urldecode(Toolbox::substr($split[$i], $pos+1))]);
                           }
                           echo Html::hidden('display_type');
                           if (!empty($additional_info)) {
                              echo $additional_info;
                           }
                           PluginServicesDropdown::showOutputFormat();
                        Html::closeForm();
                     echo "</td>";
                  }
               echo "</tr>";
            echo"</table>";
      echo"</div>";

      if (strpos($target, '?') == false) {
         $fulltarget = $target."?".$parameters;
         } else {
         $fulltarget = $target."&".$parameters;
      }

      echo"<div class='col-lg-3 col-md-3 col-sm-6 col-xs-12  mt-3'>"; echo "</div>";
      
      echo "<div class='col-lg-3 col-md-3 col-sm-6 col-xs-12 d-flex justify-content-end m-0 p-0'>";
         echo "<table style='max-width:100%; white-space:nowrap;' class='tab_cadre_pager'>";
            echo "<tr>";
               if (strpos($target, '?') == false) {
                  $fulltarget = $target."?".$parameters;
               } else {
                  $fulltarget = $target."&".$parameters;
               }
                  
               // Back and fast backward button
               $isActive = (!$start == 0) ?'' : 'pointer-events: none; display: inline-block;' ;
                  echo "<th class='left' >";
                     echo "<a href=\"javascript:void(0);\" style='$isActive'  onclick=\"submitSearchPage('$fulltarget&amp;start=0')\">";
                        echo "<i class='fa fa-step-backward' title=\"".__s('Start')."\"></i>";
                     echo "</a>&nbsp;";
                     echo "<a style='$isActive' href=\"javascript:void(0);\" onclick=\"submitSearchPage('$fulltarget&amp;start=$back')\">";
                           echo "<i class='fa fa-chevron-left' title=\"".__s('Previous')."\"></i>";
                     echo "</a>&nbsp;";



               // if (!empty($additional_info)) {
               // echo "<td class='tab_bg_2'>";
               //          echo $additional_info;
               // echo "</td>";
               // }
               // Print the "where am I?"
               echo "<td class='tab_bg_2'>";
                  self::printPagerForm("$fulltarget&amp;start=$start");
               echo "</td>";
               echo "<td class='tab_bg_2 b'>";
                  //TRANS: %1$d, %2$d, %3$d are page numbers
                  printf(__('From %1$d to %2$d of %3$d'), $start, $current_end, $numrows);
               echo "</td>\n";

               // Forward and fast forward button
               $isActive = ($forward<$numrows) ?'text-decoration: none;' : 'pointer-events: none; display: inline-block;' ;
                  echo "<th class='right' >";
                     echo "&nbsp;<a style='$isActive' href=\"javascript:void(0);\" onclick=\"submitSearchPage('$fulltarget&amp;start=$forward')\">";
                           echo" <i class='fa fa-chevron-right fs' title=\"".__s('Next')."\"></i>";
                     echo "</a>&nbsp;";
                     
                     echo "<a  style='$isActive'  href=\"javascript:void(0);\" onclick=\"submitSearchPage('$fulltarget&amp;start=$end')\">";
                        echo "<i class='fa fa-step-forward' title=\"".__s('End')."\"></i>";
                     echo "</a>\n";
                  echo "</th>\n";

               // End pager
            echo "</tr>";
         echo "</table>";
      echo "</div>";
   }

   /**
 * Print Ajax pager for list in tab panel
   *
   * @param string  $title              displayed above
   * @param integer $start              from witch item we start
   * @param integer $numrows            total items
   * @param string  $additional_info    Additional information to display (default '')
   * @param boolean $display            display if true, return the pager if false
   * @param string  $additional_params  Additional parameters to pass to tab reload request (default '')
   *
   * @return void|string
   **/

   static function printAjaxPager($title, $start, $numrows, $additional_info = '', $display = true, $additional_params = '') {
      $list_limit = $_SESSION['glpilist_limit'];
      // Forward is the next step forward
      $forward = $start+$list_limit;

      // This is the end, my friend
      $end = $numrows-$list_limit;

      // Human readable count starts here
      $current_start = $start+1;
      
      // And the human is viewing from start to end
      $current_end = $current_start+$list_limit-1;
      if ($current_end > $numrows) {
         $current_end = $numrows;
      }
      // Empty case
      if ($current_end == 0) {
         $current_start = 0;
      }
      // Backward browsing
      if ($current_start-$list_limit <= 0) {
         $back = 0;
      } else {
         $back = $start-$list_limit;
      }

      if (!empty($additional_params) && strpos($additional_params, '&') !== 0) {
         $additional_params = '&' . $additional_params;
      }

      $out = '';
      // Print it
      $out .="<div  class='col-lg-6 col-md-6 col-sm-6 col-xs-12  p-0'>";
         if (!empty($additional_info)) {
            $out .= $additional_info;
         }
      $out .= "</div>";
      $out .= "<div  class='col-md-3  col-lg-3  p-0'></div>";
      $out .= "<div  class='col-lg-3 col-md-3 col-sm-6 col-xs-12 d-flex justify-content-end m-0 p-0'><table   style='max-width:100%; white-space:nowrap;' class='tab_cadre_pager'>";
      $out .= "<tr>\n";

      // Back and fast backward button
      if (!$start == 0) {
         $out .= "<th class='left'><a href='javascript:reloadTab(\"start=0$additional_params\");'>
                     <i class='fa fa-step-backward' title=\"".__s('Start')."\"></i></a>";
         $out .= "<a href='javascript:reloadTab(\"start=$back$additional_params\");'>
                     <i class='fa fa-chevron-left' title=\"".__s('Previous')."\"></i></a></th>";
      }else {
         $out .= "<th class='left'><a style='pointer-events: none; display: inline-block;' href='javascript:reloadTab(\"start=0$additional_params\");'>
                     <i class='fa fa-step-backward' title=\"".__s('Start')."\"></i></a>";
         $out .= "<a style='pointer-events: none; display: inline-block;' href='javascript:reloadTab(\"start=$back$additional_params\");'>
                     <i class='fa fa-chevron-left' title=\"".__s('Previous')."\"></i></a></th>";
      }

      $out .= "<td class='tab_bg_2'>";
      $out .= self::printPagerForm('', false, $additional_params);
      $out .= "</td>";
      // Print the "where am I?"
      $out .= "<td  class='tab_bg_2 b'>";
      //TRANS: %1$d, %2$d, %3$d are page numbers
      $out .= sprintf(__('From %1$d to %2$d of %3$d'), $current_start, $current_end, $numrows);
      $out .= "</td>\n";

      // Forward and fast forward button
      if ($forward < $numrows) {
         $out .= "<th class='right'><a class='text-decoration-none' href='javascript:reloadTab(\"start=$forward$additional_params\");'>
                     <i class='fa fa-chevron-right' title=\"".__s('Next')."\"></i></a>";
         $out .= "<a class='text-decoration-none' href='javascript:reloadTab(\"start=$end$additional_params\");'>
                     <i class='fa fa-step-forward' title=\"".__s('End')."\"></i></a></th>";
      }else {
         $out .= "<th class='right'><a class='text-decoration-none' style='pointer-events: none; display: inline-block;' href='javascript:reloadTab(\"start=$forward$additional_params\");'>
            <i class='fa fa-chevron-right' title=\"".__s('Next')."\"></i></a>";
         $out .= "<a class='text-decoration-none' style='pointer-events: none; display: inline-block;' href='javascript:reloadTab(\"start=$end$additional_params\");'>
            <i class='fa fa-step-forward' title=\"".__s('End')."\"></i></a></th>";
      }
      // End pager
      $out .= "</tr></table></div>";

      if ($display) {
         echo $out;
         return;
      }

      return $out;
   }

   static function printPagerForm($action = "", $display = true, $additional_params = '') {
      if (!empty($additional_params) && strpos($additional_params, '&') !== 0) {
         $additional_params = '&' . $additional_params;
      }

      $out = '';
      if ($action) {
         $out .= "<form method='POST' action=\"$action\">";
         // $out .= "<span class='responsive_hidden'>".__('Display (number of items)')."</span>&nbsp;";
         $out .= PluginServicesDropdown::showListLimit("submitSearchPage(\"$action\"+\"&glpilist_limit=\"+this.value)", false);

      } else {
         $out .= "<form method='POST' action =''>\n";
         // $out .= "<span class='responsive_hidden'>".__('Display (number of items)')."</span>&nbsp;";
         $out .= PluginServicesDropdown::showListLimit("reloadTab(\"glpilist_limit=\"+this.value+\"$additional_params\")", false);
      }
      $out .= Html::closeForm(false);

      if ($display) {
         echo $out;
         return;
      }
      return $out;
   }

   static function jsAjaxDropdown($name, $field_id, $url, $params = []) {
      global $CFG_GLPI;

      if (!isset($params['value'])) {
         $value = 0;
      } else {
         $value = $params['value'];
      }
      if (!isset($params['value'])) {
         $valuename = PluginServicesDropdown::EMPTY_VALUE;
      } else {
         $valuename = $params['valuename'];
      }
      $on_change = '';
      if (isset($params["on_change"])) {
         $on_change = $params["on_change"];
         unset($params["on_change"]);
      }
      $width = '100%';
      if (isset($params["width"])) {
         $width = $params["width"];
         unset($params["width"]);
      }
      unset($params['value']);
      unset($params['valuename']);
      
      $disabled = (isset($params['disabled'])) ? $params['disabled'] : false ;
   
      $options = [
         'id'        => $field_id,
         'selected'  => $value,
         'disabled'  => $disabled
      ];
      if (!empty($params['specific_tags'])) {
         foreach ($params['specific_tags'] as $tag => $val) {
            if (is_array($val)) {
               $val = implode(' ', $val);
            }
            $options[$tag] = $val;
         }
      }

      // manage multiple select (with multiple values)
      if (isset($params['values']) && count($params['values'])) {
         $values = array_combine($params['values'], $params['valuesnames']);
         $options['multiple'] = 'multiple';
         $options['selected'] = $params['values'];
      } else {
         // simple select (multiple = no)
         $values = ["$value" => $valuename];
      }

      // display select tag
      $output = self::select($name, $values, $options);

      $js = "
         var params_$field_id = {";
      foreach ($params as $key => $val) {
         // Specific boolean case
         if (is_bool($val)) {
            $js .= "$key: ".($val?1:0).",\n";
         } else {
            $js .= "$key: ".json_encode($val).",\n";
         }
      }
      $js.= "};

         $('#$field_id').select2({
            width: '$width',
            minimumInputLength: 0,
            quietMillis: 100,
            dropdownAutoWidth: true,
            minimumResultsForSearch: ".$CFG_GLPI['ajax_limit_count'].",
            ajax: {
               url: '$url',
               dataType: 'json',
               type: 'POST',
               data: function (params) {
                  query = params;
                  return $.extend({}, params_$field_id, {
                     searchText: params.term,
                     page_limit: ".$CFG_GLPI['dropdown_max'].", // page size
                     page: params.page || 1, // page number
                  });
               },
               processResults: function (data, params) {
                  params.page = params.page || 1;
                  var more = (data.count >= ".$CFG_GLPI['dropdown_max'].");

                  return {
                     results: data.results,
                     pagination: {
                           more: more
                     }
                  };
               }
            },
            templateResult: templateResult,
            templateSelection: templateSelection
         })
         .bind('setValue', function(e, value) {
            $.ajax('$url', {
               data: $.extend({}, params_$field_id, {
                  _one_id: value,
               }),
               dataType: 'json',
               type: 'POST',
            }).done(function(data) {

               var iterate_options = function(options, value) {
                  var to_return = false;
                  $.each(options, function(index, option) {
                     if (option.hasOwnProperty('id')
                        && option.id == value) {
                        to_return = option;
                        return false; // act as break;
                     }

                     if (option.hasOwnProperty('children')) {
                        to_return = iterate_options(option.children, value);
                     }
                  });

                  return to_return;
               };

               var option = iterate_options(data.results, value);
               if (option !== false) {
                  var newOption = new Option(option.text, option.id, true, true);
                   $('#$field_id').append(newOption).trigger('change');
               }
            });
         });
         ";
      if (!empty($on_change)) {
         $js .= " $('#$field_id').on('change', function(e) {".
                  stripslashes($on_change)."});";
      }

      $js .= " $('label[for=$field_id]').on('click', function(){ $('#$field_id').select2('open'); });";

      $output .= Html::scriptBlock('$(function() {' . $js . '});');
      return $output;
   }
   
   static function submit($caption, $options = []) {
      $image = false;
      if (isset($options['image'])) {
         if (preg_match('/\.(jpg|jpe|jpeg|gif|png|ico)$/', $options['image'])) {
            $image = $options['image'];
         }
         unset($options['image']);
      }

      // Set default class to submit
      if (!isset($options['class'])) {
         $options['class'] = 'vsubmit';
      }
      if (isset($options['confirm'])) {
         if (!empty($options['confirm'])) {
            $confirmAction  = '';
            if (isset($options['confirmaction'])) {
               if (!empty($options['confirmaction'])) {
                  $confirmAction = $options['confirmaction'];
               }
               unset($options['confirmaction']);
            }
            $options['onclick'] = self::getConfirmationOnActionScript($options['confirm'],
                                                                     $confirmAction);
         }
         unset($options['confirm']);
      }

      if ($image) {
         $options['title'] = $caption;
         $options['alt']   = $caption;
         return sprintf('<input type="image" src="%s" %s />',
               Html::cleanInputText($image), Html::parseAttributes($options));
      }
      $button = "<button type='submit'  class='btn m-btn--radius btn-md  btn-info ml-2' value='%s' %s>$caption</button>";

      return sprintf($button, Html::cleanInputText($caption), Html::parseAttributes($options));
   }

   /**
    * Get confirmation on button or link before action
    *
    * @since 0.85
    *
    * @param $string             string   to display or array of string for using multilines
    * @param $additionalactions  string   additional actions to do on success confirmation
    *                                     (default '')
    *
    * @return string confirmation script
   **/
   static function getConfirmationOnActionScript($string, $additionalactions = '') {

      if (!is_array($string)) {
         $string = [$string];
      }
      $string            = Toolbox::addslashes_deep($string);
      $additionalactions = trim($additionalactions);
      $out               = "";
      $multiple          = false;
      $close_string      = '';
      // Manage multiple confirmation
      foreach ($string as $tab) {
         if (is_array($tab)) {
            $multiple      = true;
            $out          .="if (window.confirm('";
            $out          .= implode('\n', $tab);
            $out          .= "')){ ";
            $close_string .= "return true;} else { return false;}";
         }
      }
      // manage simple confirmation
      if (!$multiple) {
            $out          .="if (window.confirm('";
            $out          .= implode('\n', $string);
            $out          .= "')){ ";
            $close_string .= "return true;} else { return false;}";
      }
      $out .= $additionalactions.(substr($additionalactions, -1)!=';'?';':'').$close_string;
      return $out;
   }

   static function select($name, array $values = [], $options = []) {
      $selected = false;
      if (isset($options['selected'])) {
         $selected = $options['selected'];
         unset ($options['selected']);
      }
      if (isset($options['disabled']) && !$options['disabled']) {
         unset($options['disabled']);
      }
      $select = sprintf(
         '<select class="form-control" name="%1$s" %2$s>',
         self::cleanInputText($name),
         self::parseAttributes($options)
      );
      foreach ($values as $key => $value) {
         $select .= sprintf(
            '<option value="%1$s"%2$s>%3$s</option>',
            self::cleanInputText($key),
            ($selected != false && (
               $key == $selected
               || is_array($selected) && in_array($key, $selected))
            ) ? ' selected="selected"' : '',
            $value
         );
      }
      $select .= '</select>';
      return $select;
   }

   /**
    * Creates a text input field.
    *
    * @since 0.85
    *
    * @param string $fieldName  Name of a field
    * @param array  $options    Array of HTML attributes.
    *
    * @return string A generated hidden input
   **/
   static function input($fieldName, $options = []) {
      $type = 'text';
      if (isset($options['type'])) {
         $type = $options['type'];
         unset($options['type']);
      }
      return sprintf('<input type="%1$s" class="form-control" name="%2$s" %3$s />',
                     $type, Html::cleanInputText($fieldName), Html::parseAttributes($options));
   }

   static function showToolTip($content, $options = []) {
      $param['applyto']    = '';
      $param['title']      = '';
      $param['contentid']  = '';
      $param['link']       = '';
      $param['linkid']     = '';
      $param['linktarget'] = '';
      $param['awesome-class'] = 'fa-info';
      $param['popup']      = '';
      $param['ajax']       = '';
      $param['display']    = true;
      $param['autoclose']  = true;
      $param['onclick']    = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      // No empty content to have a clean display
      if (empty($content)) {
         $content = "&nbsp;";
      }
      $rand = mt_rand();
      $out  = '';

      // Force link for popup
      if (!empty($param['popup'])) {
         $param['link'] = '#';
      }


      if (empty($param['applyto'])) {
         if (!empty($param['link'])) {
            $out .= "<a data-container='body' data-toggle='m-popover' data-placement='bottom' data-content=\"$content\" ";

            if (!empty($param['linktarget'])) {
               $out .= " target='".$param['linktarget']."' ";
            }
            $out .= "onclick='loadPage(\"$page\")'  href='javascript:void(0);'";
            $out .= '>';
         }
         if (isset($param['img'])) {
            //for compatibility. Use fontawesome instead.
            $out .= "<img id='tooltip$rand' src='".$param['img']."' class='pointer'>";
         } else {
            $out .= "<span id='tooltip$rand' class='fas {$param['awesome-class']} pointer'></span>";
         }

         if (!empty($param['link'])) {
            $out .= "</a>";
         }

         $param['applyto'] = "tooltip$rand";
      }

      if (empty($param['contentid'])) {
         $param['contentid'] = "content".$param['applyto'];
      }

      $jsScript = "
         $('[data-toggle=\"tooltip\"]').tooltip({
            html:true,
            trigger : 'hover',
            position: 'bottom'
         })
         $('#m-content').tooltip('hide')
      ";
      $out .= Html::scriptBlock($jsScript);
      if ($param['display']) {
         echo $out;
      } else {
         return $out;
      }
   }

   static function displayNotFoundError() {
      global $CFG_GLPI, $HEADER_LOADED;
         echo "<div  class='container text-center'>";
            echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='".__s('Warning')."'>";
            echo "<br><br><span class='b'>" . __('Item not found') . "</span>";
         echo "</div>";

         // Print foot
         self::loadJavascript();
         echo Html::script('services/assets/vendors/base/vendors.bundle.js');
         echo Html::script('services/assets/demo/default/base/scripts.bundle.js');  
      exit ();
   }

   static function header($title, $url = '', $sector = "none", $item = "none", $option = "") {
      global $CFG_GLPI, $HEADER_LOADED, $DB;

      // If in modal : display popHeader
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         return self::popHeader($title, $url, false, $sector, $item, $option);
      }
      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;
      // Force lower case for sector and item
      $sector = strtolower($sector);
      $item   = strtolower($item);

      self::includeHeader($title, $sector, $item, $option);

      echo Html::css('services/assets/vendors/base/vendors.bundle.css');
      echo Html::css('services/assets/demo/default/base/style.bundle.css');
      echo '<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>';

      $body_class = "layout_".$_SESSION['glpilayout'];
      if ((strpos($_SERVER['REQUEST_URI'], ".form.php") !== false)
         && isset($_GET['id']) && ($_GET['id'] > 0)) {
         if (!CommonGLPI::isLayoutExcludedPage()) {
            $body_class.= " form";
         } else {
            $body_class = "";
         }
      }

   }

   /**
    * This function is use to Print a nice HTML head for every page
    *
    * @param string $title   title of the page
    * @param string $url     not used anymore
    * @param string $sector  sector in which the page displayed is
    * @param string $item    item corresponding to the page displayed
    * @param string $option  option corresponding to the page displayed
   **/

   static function headerDash($title, $url = '', $sector = "none", $item = "none", $option = "") {
      global $CFG_GLPI, $HEADER_LOADED, $DB;
      // If in modal : display popHeader
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         return self::popHeader($title, $url, false, $sector, $item, $option);
      }
      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;
      // Force lower case for sector and item
      $sector = strtolower($sector);
      $item   = strtolower($item);
      $theme = isset($_SESSION['glpipalette']) ? $_SESSION['glpipalette'] : 'auror';
      // Begin include header
      // self::includeHeaderDash($title, $sector, $item, $option);
      if ($sector != 'none' || $item != 'none' || $option != '') {
         $jslibs = [];
         if (isset($CFG_GLPI['javascript'][$sector])) {
            if (isset($CFG_GLPI['javascript'][$sector][$item])) {
               if (isset($CFG_GLPI['javascript'][$sector][$item][$option])) {
                  $jslibs = $CFG_GLPI['javascript'][$sector][$item][$option];
               } else {
                  $jslibs = $CFG_GLPI['javascript'][$sector][$item];
               }
            } else {
               $jslibs = $CFG_GLPI['javascript'][$sector];
            }
         }
   
         if (in_array('planning', $jslibs)) {
            Html::requireJs('planning');
         }
   
         if (in_array('fullcalendar', $jslibs)) {
            echo Html::css('services/public/lib/fullcalendar.css',
                           ['media' => '']);
            Html::requireJs('fullcalendar');
         }
   
         if (in_array('gantt', $jslibs)) {
            echo Html::css('services/public/lib/jquery-gantt.css');
            Html::requireJs('gantt');
         }
   
         if (in_array('kanban', $jslibs)) {
            Html::requireJs('kanban');
         }
   
         if (in_array('rateit', $jslibs)) {
            echo Html::css('services/public/lib/jquery.rateit.css');
            Html::requireJs('rateit');
         }
   
         if (in_array('dashboard', $jslibs)) {
            echo Html::scss('services/css/dashboard');
            Html::requireJs('dashboard');
         }
   
         if (in_array('marketplace', $jslibs)) {
            echo Html::scss('services/css/marketplace');
            Html::requireJs('marketplace');
         }
   
         if (in_array('rack', $jslibs)) {
            Html::requireJs('rack');
         }
   
         if (in_array('gridstack', $jslibs)) {
            echo Html::css('services/public/lib/gridstack.css');
            Html::requireJs('gridstack');
         }
   
         if (in_array('sortable', $jslibs)) {
            Html::requireJs('sortable');
         }
   
         if (in_array('tinymce', $jslibs)) {
            Html::requireJs('tinymce');
         }
   
         if (in_array('clipboard', $jslibs)) {
            Html::requireJs('clipboard');
         }
   
         if (in_array('jstree', $jslibs)) {
            Html::requireJs('jstree');
         }
   
         if (in_array('charts', $jslibs)) {
            echo Html::css('services/public/lib/chartist.css');
            echo Html::css('services/css/chartists-glpi.css');
            Html::requireJs('charts');
         }
   
         if (in_array('codemirror', $jslibs)) {
            echo Html::css('services/public/lib/codemirror.css');
            Html::requireJs('codemirror');
         }
   
         if (in_array('photoswipe', $jslibs)) {
            echo Html::css('services/public/lib/photoswipe.css');
            Html::requireJs('photoswipe');
         }
      }
      echo Html::scss('services/css/styles');
      if (isset($_SESSION['glpihighcontrast_css']) && $_SESSION['glpihighcontrast_css']) {
         echo Html::scss('services/css/highcontrast');
      }
      echo Html::scss('services/css/palettes/' . $theme);

      echo Html::css('services/css/print.css', ['media' => 'print']);
      echo "<link rel='shortcut icon' type='images/x-icon' href='".
             $CFG_GLPI["root_doc"]."/pics/favicon.ico' >\n";

      // End include header
      $body_class = "layout_".$_SESSION['glpilayout'];
      if ((strpos($_SERVER['REQUEST_URI'], ".form.php") !== false)
          && isset($_GET['id']) && ($_GET['id'] > 0)) {
         if (!CommonGLPI::isLayoutExcludedPage()) {
            $body_class.= " form";
         } else {
            $body_class = "";
         }
      }

      // Body
      echo "<body class='$body_class'>";

      Html::displayImpersonateBanner();

      // echo "<div id='header'>";
      // echo "<header role='banner' id='header_top'>";
      // echo "<div id='c_logo'>";
      // echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/central.php'
      //          accesskey='1'
      //          title='" . __s('Home') . "'><span class='sr-only'>" . __s('Home') . "</span></a>";
      // echo "</div>";

      // Preferences and logout link
      // self::displayTopMenu(false);
      // echo "</header>"; // header_top

      //Main menu
      // self::displayMainMenu(
      //    true, [
      //       'sector' => $sector,
      //       'item'   => $item,
      //       'option' => $option
      //    ]
      // );

      // echo "</div>\n"; // fin header

      // Back to top button
      // echo "<span class='fa-stack fa-lg' id='backtotop' style='display: none'>".
      //      "<i class='fa fa-circle fa-stack-2x primary-fg-inverse'></i>".
      //      "<a href='#' class='fa fa-arrow-up fa-stack-1x primary-fg' title='".
      //         __s('Back to top of the page')."'>".
      //      "<span class='sr-only'>Top of the page</span>".
      //      "</a></span>";

      echo "<main role='main' id='page'>";

      if ($DB->isSlave()
         && !$DB->first_connection) {
         echo "<div id='dbslave-float'>";
         echo "<a href='#see_debug'>".__('SQL replica: read only')."</a>";
         echo "</div>";
      }

      // call static function callcron() every 5min
      CronTask::callCron();
      self::displayMessageAfterRedirect();
   }

   static function footerDash($keepDB = false) {
      global $CFG_GLPI, $FOOTER_LOADED, $TIMER_DEBUG;

      // If in modal : display popFooter
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         return self::popFooter();
      }

      // Print foot for every page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;
      echo "</main>"; // end of "main role='main'"

      echo "<footer role='contentinfo' id='footer'>";
      echo "<table role='presentation'><tr>";

         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) { // mode debug
            echo "<td class='left'><span class='copyright'>";
            $timedebug = sprintf(_n('%s second', '%s seconds', $TIMER_DEBUG->getTime()),
                                 $TIMER_DEBUG->getTime());

            if (function_exists("memory_get_usage")) {
               $timedebug = sprintf(__('%1$s - %2$s'), $timedebug, Toolbox::getSize(memory_get_usage()));
            }
            echo $timedebug;
            echo "</span></td>";
         }

      $currentVersion = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
      $foundedNewVersion = array_key_exists('founded_new_version', $CFG_GLPI)
         ? $CFG_GLPI['founded_new_version']
         : '';
      if (!empty($foundedNewVersion) && version_compare($currentVersion, $foundedNewVersion, '<')) {
         echo "<td class='copyright'>";
         $latest_version = "<a href='http://www.glpi-project.org' target='_blank' title=\""
             . __s('You will find it on the GLPI-PROJECT.org site.')."\"> "
             . $foundedNewVersion
             . "</a>";
         printf(__('A new version is available: %s.'), $latest_version);

         echo "</td>";
      }
      echo "<br />";
      echo "<br />";
      // echo "<td class='right'>" . self::getCopyrightMessage() . "</td>";
      echo "</tr></table></footer>";

      if ($CFG_GLPI['maintenance_mode']) { // mode maintenance
         echo "<div id='maintenance-float'>";
         echo "<a href='#see_maintenance'>GLPI MAINTENANCE MODE</a>";
         echo "</div>";
      }
      self::displayDebugInfos();
      
      // echo Html::script('services/assets/vendors/base/vendors.bundle.js');
      echo Html::script('services/assets/demo/default/base/scripts.bundle.js');
      echo Html::script('services/assets/demo/default/base/chart.min.js'); 
      echo Html::script('services/assets/demo/default/custom/crud/forms/widgets/select2.js');         

      echo '<script
      src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"
      integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU="
      crossorigin="anonymous"></script>';

      self::loadJavascript();
      

      echo "</body></html>";

      if (!$keepDB) {
         closeDBConnections();
      }
   }

   static function popHeader(
      $title,
      $url = '',
      $iframed = false,
      $sector = "none",
      $item = "none",
      $option = ""
   ) {
      global $HEADER_LOADED;

      // Print a nice HTML-head for every page
      if ($HEADER_LOADED) {
         return;
      }
      $HEADER_LOADED = true;
      Html::includeHeader($title, $sector, $item, $option); // Body
      echo "<body class='".($iframed? "iframed": "")."'>";
      self::displayMessageAfterRedirect();

   }

   /**
    * Print footer for a modal window
   **/
   static function popFooter() {
      global $FOOTER_LOADED;

      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      // Print foot
      self::loadJavascript();
      echo Html::script('services/assets/vendors/base/vendors.bundle.js');
      echo Html::script('services/assets/demo/default/base/scripts.bundle.js');  
      echo "</body></html>";
   }


   static function showDateTimeField($name, $options = []) {
      global $CFG_GLPI;

      $p = [
         'value'      => '',
         'maybeempty' => true,
         'canedit'    => true,
         'mindate'    => '',
         'maxdate'    => '',
         'mintime'    => '',
         'maxtime'    => '',
         'timestep'   => -1,
         'showyear'   => true,
         'display'    => true,
         'rand'       => mt_rand(),
         'required'   => false,
      ];

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      if ($p['timestep'] < 0) {
         $p['timestep'] = $CFG_GLPI['time_step'];
      }

      $date_value = '';
      $hour_value = '';
      if (!empty($p['value'])) {
         list($date_value, $hour_value) = explode(' ', $p['value']);
      }

      if (!empty($p['mintime'])) {
         // Check time in interval
         if (!empty($hour_value) && ($hour_value < $p['mintime'])) {
            $hour_value = $p['mintime'];
         }
      }

      if (!empty($p['maxtime'])) {
         // Check time in interval
         if (!empty($hour_value) && ($hour_value > $p['maxtime'])) {
            $hour_value = $p['maxtime'];
         }
      }

      // reconstruct value to be valid
      if (!empty($date_value)) {
         $p['value'] = $date_value.' '.$hour_value;
      }

      $required = $p['required'] == true
         ? " required='required'"
         : "";
      $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";
      $clear    = $p['maybeempty'] && $p['canedit']
         ? ""
         : "";
      $rand_id = "id_date_" . mt_rand();
      $output = <<<HTML
      
         <div class="no-wrap flatpickr" id="showdate{$p['rand']}">
            <input id="$rand_id" class='form-control m-input ui-autocomplete-input' type="text" name="{$name}" value="{$p['value']}"
                  {$required} {$disabled} data-input>
            <!-- <a class="input-button" data-toggle>
               <i class="far fa-calendar-alt fa-lg pointer"></i>
            </a> -->
            $clear
         </div>
         <script>    
            jQuery(document).ready(function(){
               $("#$rand_id").datetimepicker({todayHighlight:!0,autoclose:!0,format:"yyyy-mm-dd hh:ii"});
            });
         </script>
      HTML;

            $date_format = Toolbox::getDateFormat('js')." H:i:S";

            $min_attr = !empty($p['min'])
               ? "minDate: '{$p['min']}',"
               : "";
            $max_attr = !empty($p['max'])
               ? "maxDate: '{$p['max']}',"
               : "";

            $js = <<<JS
            $(function() {
               $("#showdate{$p['rand']}").flatpickr({
                  altInput: true, // Show the user a readable date (as per altFormat), but return something totally different to the server.
                  altFormat: "{$date_format}",
                  dateFormat: 'Y-m-d H:i:S',
                  wrap: true, // permits to have controls in addition to input (like clear or open date buttons)
                  enableTime: true,
                  enableSeconds: true,
                  weekNumbers: true,
                  locale: "{$CFG_GLPI['languages'][$_SESSION['glpilanguage']][3]}",
                  minuteIncrement: "{$p['timestep']}",
                  {$min_attr}
                  {$max_attr}
               });
            });
      JS;
      // $output .= Html::scriptBlock($js);

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }

   /**
    * Show generic date search
    *
    * @param string $element  name of the html element
    * @param string $value    default value
    * @param $options   array of possible options:
    *      - with_time display with time selection ? (default false)
    *      - with_future display with future date selection ? (default false)
    *      - with_days display specific days selection TODAY, BEGINMONTH, LASTMONDAY... ? (default true)
    *
    * @return integer|string
    *    integer if option display=true (random part of elements id)
    *    string if option display=false (HTML code)
   **/
   static function showGenericDateTimeSearch($element, $value = '', $options = []) {
      global $CFG_GLPI;

      $p['with_time']          = false;
      $p['with_future']        = false;
      $p['with_days']          = true;
      $p['with_specific_date'] = true;
      $p['display']            = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $rand   = mt_rand();
      $output = '';
      // Validate value
      if (($value != 'NOW')
         && ($value != 'TODAY')
         && !preg_match("/\d{4}-\d{2}-\d{2}.*/", $value)
         && !strstr($value, 'HOUR')
         && !strstr($value, 'MINUTE')
         && !strstr($value, 'DAY')
         && !strstr($value, 'WEEK')
         && !strstr($value, 'MONTH')
         && !strstr($value, 'YEAR')) {

         $value = "";
      }

      if (empty($value)) {
         $value = 'NOW';
      }
      $specific_value = date("Y-m-d H:i:s");

      if (preg_match("/\d{4}-\d{2}-\d{2}.*/", $value)) {
         $specific_value = $value;
         $value          = 0;
      }
      $output    .= "<table width='100%'><tr><td width='50%'>";

      $dates      = Html::getGenericDateTimeSearchItems($p);

      $output    .= PluginServicesDropdown::showFromArray("_select_$element", $dates,
                                                ['value'   => $value,
                                                   'width' => '230px',
                                                      'display' => false,
                                                      'rand'    => $rand]);
      $field_id   = Html::cleanId("dropdown__select_$element$rand");

      $output    .= "</td><td width='50%'>";
      $contentid  = Html::cleanId("displaygenericdate$element$rand");
      $output    .= "<span id='$contentid'></span>";

      $params     = ['value'         => '__VALUE__',
                        'name'          => $element,
                        'withtime'      => $p['with_time'],
                        'specificvalue' => $specific_value];

      $output    .= Ajax::updateItemOnSelectEvent($field_id, $contentid,
                                                $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
                                                $params, false);
      $params['value']  = $value;
      $output    .= Ajax::updateItem($contentid, $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
                                          $params, '', false);
      $output    .= "</td></tr></table>";

      if ($p['display']) {
         echo $output;
         return $rand;
      }
      return $output;
   }

   static function displayRightError() {
      self::displayErrorAndDie(__("You don't have permission to perform this action."));
   }

   static function displayErrorAndDie ($message, $minimal = false) {
      global $CFG_GLPI, $HEADER_LOADED;
      echo "<div class='container text-center'><br><br>";
         echo Html::image($CFG_GLPI["root_doc"] . "/pics/warning.png", ['alt' => __('Warning')]);
      echo "<br><br><span class='b'>$message</span></div>";
      // Print foot
      echo Html::script('services/assets/vendors/base/vendors.bundle.js');
      echo Html::script('services/assets/demo/default/base/scripts.bundle.js'); 
      exit ();
   }

   static function nullFooter() {
      global $FOOTER_LOADED;

      // Print foot for null page
      if ($FOOTER_LOADED) {
         return;
      }
      $FOOTER_LOADED = true;

      if (!isCommandLine()) {  
         $jsScript = "  

            function loadPage(url) { 
               $('#searchcriteria').hide();
               $('#spin').css('services/display','block');
               var _loader = $('<div id=\'loadingslide\'><div class=\'loadingindicator\'>" . __s('Loading...') . "</div></div>');
               // $('#m-content').html(_loader); 
            
               $('#m-content').load(url+'&type=ajax', function(){
                  history.pushState(null, '', url);
                  $('#spin').css('services/display','none');
               });
            }

            function submitSearchPage(url) { 
               $('#searchcriteria').hide();
               $('#spin').css('services/display','block');
            
               $('#m-content').load(url+'&type=ajax', function(){
                  $('#spin').css('services/display','none');
                  history.pushState(null, '', url);
               });
            }

            function loadPageModal(url) { 
               var _loader = $('<div id=\'loadingslide\'><div class=\'loadingindicator\'>" . __s('Loading...') . "</div></div>');
               $('#modal-body').html(_loader); 
               $('#modal-body').load(url);
            }

            function showAlertMessage(msgType = 'info', msgContent){
               if(!msgContent){
                  return;
               }
               switch(msgType) {
                  case 'info':
                        toastr.success(msgContent);
                        break;
                  case 'error':
                        toastr.error(msgContent);
                        break;
                  case 'warning':
                        toastr.warning(msgContent);
                        break;
               }
            }

            function addSubmitFormLoader(buttonName){
               $('button[name=buttonName]').addClass('m-loader m-loader--light m-loader--right');
               console.log(buttonName);
               disableButton(buttonName);
            }

            function removeSubmitFormLoader(buttonName){
               $('button[name=buttonName]').removeClass('m-loader m-loader--light m-loader--right');
               enableButton(buttonName);
            }

            function enableButton(buttonName){
               $('button[name=buttonName]').prop('disabled', false);
            }
            
            function disableButton(buttonName){
               $('button[name=buttonName]').prop('disabled', true);
            }


            $( document ).ready(function() {
               toastr.options = {
                  'closeButton': false,
                  'debug': false,
                  'newestOnTop': false,
                  'progressBar': true,
                  'positionClass': 'toast-top-right',
                  'preventDuplicates': false,
                  'showDuration': '300',
                  'hideDuration': '1000',
                  'timeOut': '5000',
                  'extendedTimeOut': '1000',
                  'showEasing': 'swing',
                  'hideEasing': 'linear',
                  'showMethod': 'fadeIn',
                  'hideMethod': 'fadeOut'
               };

               $('#togglesearchcriteria').click(function() {
                  $('#searchcriteria').toggle();
                  console.log($('#searchcriteria'));
               });

               $('#resetsearchcriteria').click(function(){
                  $('#blanksearchcriteria').click();
               });
   
               $('#clickmassiveactionhidebutton').click(function(event){
                     $('#massiveaction').click();
               });

               // $('[data-toggle=\"tooltip\"]').tooltip({
               //    html:true,
               //    trigger : 'hover'
               // })   

               window.addEventListener(\"popstate\", function(e) {
                  window.location.href = location.href;
               });
               
               
            });
      
            
         ";
         echo Html::scriptBlock($jsScript);
         echo Html::script('services/js/fileupload.min.js');
         echo Html::script('services/assets/vendors/base/vendors.bundle.js');
         echo Html::script('services/assets/demo/default/base/scripts.bundle.js');
         echo Html::script('services/assets/vendors/custom/jquery-ui/jquery-ui.bundle.js');
         echo Html::script('services/assets/demo/default/custom/crud/forms/widgets/select2.js'); 
         echo Html::script('services/assets/jquery_validation/jquery.validate.js');
         echo Html::script('services/assets/jquery_validation/localization/messages_fr.js');
         echo'<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.9/flatpickr.min.js" integrity="sha512-+ruHlyki4CepPr07VklkX/KM5NXdD16K1xVwSva5VqOVbsotyCQVKEwdQ1tAeo3UkHCXfSMtKU/mZpKjYqkxZA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
         echo'<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.ns-autogrow/1.1.6/jquery.ns-autogrow.min.js" integrity="sha512-DbWEvjns5r0MXa/VzcLSzT1W3vDXyYBMER2fzhsQCf/Yhehcbia0ctHKhy5eKfNlBKvl2DGX2lgxAPkatkkPJw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
         echo'<script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/10.32.0/js/jquery.fileupload.min.js" integrity="sha512-P7EUiLYW7QUrhYrLgaJ++ok2j2I7Pu0UgGnrpLowujPZicu7mIR0V/Trq+7kl/0nEkp6yNGh8eFJY1JUv3dkPA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
         self::loadJavascript();

         echo "</body></html>";
      }
      closeDBConnections();
   }

   static function helpHeaderPortal($title, $url = '') {
      global $CFG_GLPI, $HEADER_LOADED;
      self::includeHeader($title, 'self-service'); 
      $th = new PluginServicesTheme();
      $themes = $th->find(['active' => 1]);
      if (!empty($themes)) {
         foreach ($themes as  $value) {
            $theme = $value; 
         }
      }else {
         $theme = [
            "sidebar_header_color"=> '#399fa0',
            "sidebar_color"=> '#3DA8A9',
            "sidebar_menu_color"=> '#f2eeee'
         ];
      }
      ?>
      <style>
         .logo-zone{
            width: 100px;
         }
         .banner-text {
            color: #fff;
            font-size: 2.75rem;
            color: white;
            text-shadow: 2px 2px 4px #000000;
            text-transform: uppercase;
         }

   
         .ui-corner-all, .ui-corner-bottom, .ui-corner-right, .ui-corner-br {
            border-bottom-right-radius: 0px;
         }
         .banner-text-zone{
            padding: 50px;
            border-radius: 15px;
            background: rgba(20, 20, 20, 0.44);
         }
         .m-nav .m-nav__item>.m-nav__link .m-nav__link-text:hover {
            color: <?= $theme['sidebar_color'] ?> !important;
         }
         
         .m-link:hover:after {
            border-bottom: 1px solid <?= $theme['sidebar_color'] ?> !important;
            filter: alpha(opacity=30)
         }

         .m-link:hover {
               color: <?= $theme['sidebar_color'] ?> !important;
         }

         #card-header{
            background-color: <?= $theme['sidebar_color'] ?>,
            font-family: Roboto, sans-serif;
            font-size: 16px;
            font-weight: 400;
         }
         
         /* .m-portlet .m-portlet__head .m-portlet__head-text {
            color: <?= $theme['sidebar_menu_color'] ?> !important;
         } */
         .m-portlet .m-portlet__head .m-portlet__head-text small {
            color: <?= $theme['sidebar_menu_color'] ?> !important;
         }
         .m-portlet .m-portlet__head .m-portlet__head-icon {
            color: <?= $theme['sidebar_menu_color'] ?> !important;
         }
         /* .m-portlet__head{
            background-color: <?= $theme['sidebar_color'] ?> !important;
         } */
         .m-subheader {
            padding: 30px 30px 0 0px;
         }
         .ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default {
            border: 1px solid #FFF;
            background-color: #f2f3f8;
            color: #555555;
         } 
         .m-footer {
            padding: 7px 30px;
            height: 60px;
            min-height: 60px;
         }
         .ui-corner-all, .ui-corner-bottom, .ui-corner-right, .ui-corner-br {
               border-bottom-right-radius: 0px !important;
         }
         .ui-corner-all, .ui-corner-bottom, .ui-corner-left, .ui-corner-bl {
               border-bottom-left-radius: 0px !important;
         }
         .ui-corner-all, .ui-corner-top, .ui-corner-right, .ui-corner-tr {
               border-top-right-radius: 0px !important;
         }
         .ui-corner-all, .ui-corner-top, .ui-corner-left, .ui-corner-tl {
               border-top-left-radius: 0px !important;
         }
         .ui-state-default a, .ui-state-default a:link, .ui-state-default a:visited, a.ui-button, a:link.ui-button, a:visited.ui-button, .ui-button {
               color: unset !important;
               text-decoration: none;
         }
         #mainform button[name=add]{
            background-color:  <?= $theme['sidebar_color'] ?>;
            border-color: <?= $theme['sidebar_color'] ?>;
            color: <?= $theme['sidebar_menu_color'] ?>
         }
         #mainform button[name=update]{
            background-color:  <?= $theme['sidebar_color'] ?>;
            border-color: <?= $theme['sidebar_color'] ?>;
            color: <?= $theme['sidebar_menu_color'] ?>
         }
         #mainform button[name=delete]{
            background-color:  #f4516c !important;
            border-color: #f4516c !important;
            color: #fff;
         }
         #mainform button[name=purge]{
            background-color: #f4516c !important;
            border-color: #f4516c !important ;
            color: #fff;
         }
         .newValidation{
            border: 1px solid #dddddd !important;
            background: #e9e9e9 !important;
            color: #333333 !important;
         }

         .newValidation i{
            color: #333333 !important;
         }
         
         input, select {
            font-size:14px !important;
         }
         .form-control[readonly], .form-control {
               border-color: #cccccc;
               color: #575962;
         }
         .page-item.active .page-link {
            z-index: 1;
            background-color:  <?= $theme['sidebar_color'] ?>;
            border-color: <?= $theme['sidebar_color'] ?>;
            color: <?= $theme['sidebar_menu_color'] ?>
         }
         #mainform input, #mainform textarea, #mainform select, #mainform .select2-selection, #mainform .select2-selection--single, .form-control-fago{
            background: #fff !important;
            box-shadow: <?php echo $theme['sidebar_color']; ?> 0px 0px 1px 0px !important;
         }

         #mainform input[type=search]{
            background: #fff !important;
            box-shadow:  0px 0px 0px 0px !important;
         }
      </style>
      <?php
      echo Html::css('services/public/lib/chartist.css');
      echo Html::css('services/css/chartists-glpi.css');
      Html::requireJs('charts');

      // Body
      // $body_class = "layout_".$_SESSION['glpilayout'];
      // if ((strpos($_SERVER['REQUEST_URI'], "form.php") !== false)
      //     && isset($_GET['id']) && ($_GET['id'] > 0)) {
      //    if (!CommonGLPI::isLayoutExcludedPage()) {
      //       $body_class.= " form";
      //    } else {
      //       $body_class = "";
      //    }
      // }
      // echo "<body class='$body_class'>";

      //Html::displayImpersonateBanner();

      // Main Headline
      // echo "<div id='header'>";
      // echo "<header role='banner' id='header_top'>";

      // echo "<div id='c_logo'>";
      // echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' accesskey='1' title=\"".
      //        __s('Home')."\"><span class='invisible'>Logo</span></a>";
      // echo "</div>";

      //Preferences and logout link
      //self::displayTopMenu(false);
      //echo "</header>"; // header_top

      //Main menu
      //self::displayMainMenu(false);

      // echo "</div>"; // fin header
      // echo "<main role='main' id='page'>";

      // call static function callcron() every 5min
      CronTask::callCron();
      // self::displayMessageAfterRedirect();
   }

   static function textarea($options = []) {
      //default options
      $p['name']              = 'text';
      $p['filecontainer']     = 'fileupload_info';
      $p['rand']              = mt_rand();
      $p['editor_id']         = 'text'.$p['rand'];
      $p['value']             = '';
      $p['enable_richtext']   = false;
      $p['enable_fileupload'] = false;
      $p['display']           = true;
      $p['cols']              = 100;
      $p['rows']              = 5;
      $p['multiple']          = true;
      $p['required']          = false;
      $p['uploads']           = [];
      $p['disabled']           = false;

      //merge default options with options parameter
      $p = array_merge($p, $options);

      $required = $p['required'] ? 'required="required"' : '';
      $disabled = $p['disabled'] ? 'disabled' : '';
      $display = '';
      $display .= "<textarea   style='overflow:hidden' onkeyup='textAreaAdjust(this)' class='form-control' name='".$p['name']."' id='".$p['editor_id']."'
                     rows='".$p['rows']."' cols='".$p['cols']."' $required   $disabled >".
                  $p['value']."</textarea>";

      if ($p['enable_richtext']) {
         $display .= Html::initEditorSystem($p['editor_id'], $p['rand'], false);
      } else {
         $display .= Html::scriptBlock("
                        $(document).ready(function() {
                           $('".$p['editor_id']."').autogrow();
                        });
                     ");
      }
      if (!$p['enable_fileupload'] && $p['enable_richtext']) {
         $display .= self::uploadedFiles([
            'filecontainer' => $p['filecontainer'],
            'name'          => $p['name'],
            'display'       => false,
            'uploads'       => $p['uploads'],
            'editor_id'     => $p['editor_id'],
         ]);
      }

      if ($p['enable_fileupload']) {
         $p_rt = $p;
         unset($p_rt['name']);
         $p_rt['display'] = false;
         $display .= Html::file($p_rt);
      }

      if ($p['display']) {
         echo $display;
         return true;
      } else {
         return $display;
      }
   }

   static function file($options = []) {
      global $CFG_GLPI;

      $randupload             = mt_rand();

      $p['name']              = 'filename';
      $p['onlyimages']        = false;
      $p['filecontainer']     = 'fileupload_info';
      $p['showfilesize']      = true;
      $p['showtitle']         = true;
      $p['enable_richtext']   = false;
      $p['pasteZone']         = false;
      $p['dropZone']          = 'dropdoc'.$randupload;
      $p['rand']              = $randupload;
      $p['values']            = [];
      $p['display']           = true;
      $p['multiple']          = false;
      $p['uploads']           = [];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $display = "";
      $display .= "<div class='mb-0 fileupload draghoverable'>";

      if ($p['showtitle']) {
         $display .= "<b>";
         $display .= sprintf(__('%1$s (%2$s)'), __('File(s)'), Document::getMaxUploadSize());
         // $display .= DocumentType::showAvailableTypesLink(['display' => false]);
         $display .= "</b>";
      }

      $display .= self::uploadedFiles([
         'filecontainer' => $p['filecontainer'],
         'name'          => $p['name'],
         'display'       => false,
         'uploads'       => $p['uploads'],
      ]);

      if (!empty($p['editor_id'])
          && $p['enable_richtext']) {
         $options_rt = $options;
         $options_rt['display'] = false;
         $display .= self::fileForRichText($options_rt);
      } else {

         // manage file upload without tinymce editor
         $display .= "<div id='{$p['dropZone']}'>";
         $display .= "<span class='b'>".__('Drag and drop your file here, or').'</span><br>';
         $display .= "<input id='fileupload{$p['rand']}' type='file' name='".$p['name']."[]'
                         data-url='".$CFG_GLPI["root_doc"]."/ajax/fileupload.php'
                         data-form-data='{\"name\": \"".$p['name']."\",
                                          \"showfilesize\": \"".$p['showfilesize']."\"}'"
                         .($p['multiple']?" multiple='multiple'":"")
                         .($p['onlyimages']?" accept='.gif,.png,.jpg,.jpeg'":"").">";
         $display .= "<div id='progress{$p['rand']}' style='display:none'>".
                 "<div class='uploadbar' style='width: 0%;'></div></div>";
         $display .= "</div>";

         $display .= Html::scriptBlock("
         $(function() {
            var fileindex{$p['rand']} = 0;
            $('#fileupload{$p['rand']}').fileupload({
               dataType: 'json',
               pasteZone: ".($p['pasteZone'] !== false
                              ? "$('#{$p['pasteZone']}')"
                              : "false").",
               dropZone:  ".($p['dropZone'] !== false
                              ? "$('#{$p['dropZone']}')"
                              : "false").",
               acceptFileTypes: ".($p['onlyimages']
                                    ? "/(\.|\/)(gif|jpe?g|png)$/i"
                                    : "undefined").",
               progressall: function(event, data) {
                  var progress = parseInt(data.loaded / data.total * 100, 10);
                  $('#progress{$p['rand']}')
                     .show()
                  .filter('.uploadbar')
                     .css({
                        width: progress + '%'
                     })
                     .text(progress + '%')
                     .show();
               },
               done: function (event, data) {
                  var filedata = data;
                  // Load image tag, and display image uploaded
                  $.ajax({
                     type: 'POST',
                     url: '".$CFG_GLPI['root_doc']."/ajax/getFileTag.php',
                     data: {
                        data: data.result.{$p['name']}
                     },
                     dataType: 'JSON',
                     success: function(tag) {
                        $.each(filedata.result.{$p['name']}, function(index, file) {
                           if (file.error === undefined) {
                              //create a virtual editor to manage filelist, see displayUploadedFile()
                              var editor = {
                                 targetElm: $('#fileupload{$p['rand']}')
                              };
                              displayUploadedFile(file, tag[index], editor, '{$p['name']}');

                              $('#progress{$p['rand']} .uploadbar')
                                 .text('".addslashes(__('Upload successful'))."')
                                 .css('services/width', '100%')
                                 .delay(2000)
                                 .fadeOut('slow');
                           } else {
                              $('#progress{$p['rand']} .uploadbar')
                                 .text(file.error)
                                 .css('services/width', '100%');
                           }
                        });
                     }
                  });
               }
            });
         });");
      }
      $display .= "</div>"; // .fileupload

      if ($p['display']) {
         echo $display;
      } else {
         return $display;
      }
   }

   private static function uploadedFiles($options = []) {
      global $CFG_GLPI;

      //default options
      $p['filecontainer']     = 'fileupload_info';
      $p['name']              = 'filename';
      $p['editor_id']         = '';
      $p['display']           = true;
      $p['uploads']           = [];

      //merge default options with options parameter
      $p = array_merge($p, $options);

      // div who will receive and display file list
      $display = "<div id='".$p['filecontainer']."' class='fileupload_info'>";
      if (isset($p['uploads']['_' . $p['name']])) {
         foreach ($p['uploads']['_' . $p['name']] as $uploadId => $upload) {
            $prefix  = substr($upload, 0, 23);
            $displayName = substr($upload, 23);

            // get the extension icon
            $extension = pathinfo(GLPI_TMP_DIR . '/' . $upload, PATHINFO_EXTENSION);
            $extensionIcon = '/pics/icones/' . $extension . '-dist.png';
            if (!is_readable(GLPI_ROOT . $extensionIcon)) {
               $extensionIcon = '/pics/icones/defaut-dist.png';
            }
            $extensionIcon = $CFG_GLPI['root_doc'] . $extensionIcon;

            // Rebuild the minimal data to show the already uploaded files
            $upload = [
               'name'    => $upload,
               'id'      => 'doc' . $p['name'] . mt_rand(),
               'display' => $displayName,
               'size'    => filesize(GLPI_TMP_DIR . '/' . $upload),
               'prefix'  => $prefix,
            ];
            $tag = $p['uploads']['_tag_' . $p['name']][$uploadId];
            $tag = [
               'name' => $tag,
               'tag'  => "#$tag#",
            ];

            // Show the name and size of the upload
            $display .= "<p id='" . $upload['id'] . "'>&nbsp;";
            $display .= "<img src='$extensionIcon' title='$extension'>&nbsp;";
            $display .= "<b>" . $upload['display'] . "</b>&nbsp;(" . Toolbox::getSize($upload['size']) . ")";

            $name = '_' . $p['name'] . '[' . $uploadId . ']';
            $display .= Html::hidden($name, ['value' => $upload['name']]);

            $name = '_prefix_' . $p['name'] . '[' . $uploadId . ']';
            $display .= Html::hidden($name, ['value' => $upload['prefix']]);

            $name = '_tag_' . $p['name'] . '[' . $uploadId . ']';
            $display .= Html::hidden($name, ['value' => $tag['name']]);

            // show button to delete the upload
            $getEditor = 'null';
            if ($p['editor_id'] != '') {
               $getEditor = "tinymce.get('" . $p['editor_id'] . "')";
            }
            $textTag = $tag['tag'];
            $domItems = "{0:'" . $upload['id'] . "', 1:'" . $upload['id'] . "'+'2'}";
            $deleteUpload = "deleteImagePasted($domItems, '$textTag', $getEditor)";
            $display .= '<span class="fa fa-times-circle pointer" onclick="' . $deleteUpload . '"></span>';

            $display .= "</p>";
         }
      }
      $display .= "</div>";

      if ($p['display']) {
         echo $display;
         return true;
      } else {
         return $display;
      }
   }

   static function showDateField($name, $options = []) {
      global $CFG_GLPI;

      $p = [
         'value'        => '',
         'defaultDate'  => '',
         'maybeempty'   => true,
         'canedit'      => true,
         'min'          => '',
         'max'          => '',
         'showyear'     => false,
         'display'      => true,
         'range'        => false,
         'rand'         => mt_rand(),
         'calendar_btn' => true,
         'clear_btn'    => true,
         'yearrange'    => '',
         'multiple'     => false,
         'size'         => 10,
         'required'     => false,
         'placeholder'  => '',
         'on_change'    => '',
      ];

      foreach ($options as $key => $val) {
         if (isset($p[$key])) {
            $p[$key] = $val;
         }
      }

      $required = $p['required'] == true
         ? " required='required'"
         : "";
      $disabled = !$p['canedit']
         ? " disabled='disabled'"
         : "";

      $calendar_btn = $p['calendar_btn']
         ? "<a class='input-button' data-toggle>
               
            </a>"
         : "";
      $clear_btn = '';
      // $p['clear_btn'] && $p['maybeempty'] && $p['canedit']
      //    ? "<a data-clear  title='".__s('Clear')."'>
      //          <i class='fa fa-times-circle pointer'></i>
      //       </a>"
      //    : "";

      $mode = $p['range']
         ? "mode: 'range',"
         : "";

      $output = <<<HTML
      <div class="no-wrap flatpickr" id="showdate{$p['rand']}">
         <input type="text" name="{$name}" size="{$p['size']}"
               {$required} {$disabled} data-input placeholder="{$p['placeholder']}">
         $calendar_btn
         $clear_btn
      </div>
      HTML;

            $date_format = Toolbox::getDateFormat('js');

            $min_attr = !empty($p['min'])
               ? "minDate: '{$p['min']}',"
               : "";
            $max_attr = !empty($p['max'])
               ? "maxDate: '{$p['max']}',"
               : "";
            $multiple_attr = $p['multiple']
               ? "mode: 'multiple',"
               : "";

            $value = is_array($p['value'])
               ? json_encode($p['value'])
               : "'{$p['value']}'";

            $locale = Locale::parseLocale($_SESSION['glpilanguage']);
            $js = <<<JS
            $(function() {
               $("#showdate{$p['rand']}").flatpickr({
                  defaultDate: {$value},
                  altInput: true, // Show the user a readable date (as per altFormat), but return something totally different to the server.
                  altFormat: '{$date_format}',
                  dateFormat: 'Y-m-d',
                  wrap: true, // permits to have controls in addition to input (like clear or open date buttons
                  weekNumbers: true,
                  locale: getFlatPickerLocale("{$locale['language']}", "{$locale['region']}"),
                  {$min_attr}
                  {$max_attr}
                  {$multiple_attr}
                  {$mode}
                  onChange: function(selectedDates, dateStr, instance) {
                     {$p['on_change']}
                  },
                  allowInput: true,
                  onClose(dates, currentdatestring, picker){
                     picker.setDate(picker.altInput.value, true, picker.config.altFormat)
                  }
               });
            });
      JS;

      $output .= Html::scriptBlock($js);

      if ($p['display']) {
         echo $output;
         return $p['rand'];
      }
      return $output;
   }
   
   /**
    * Get javascript code to get item by id
    *
    * @param $id string id of the dom element
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function jsGetElementbyID($id) {
      return "$('#$id')";
   }

   /**
    * Get javascript code to get item by iput name
    *
    * @param $id string id of the dom element
    * @return String
   **/
   static function jsGetElementbyName($id) {
      return "$('[name=\"$id\"]')";
   }

   /**
    * Get javascript code for hide an item
    *
    * @param $toobserve string id dom de l'élement à ecouter
    * @param $tohide string id dom de l'élement à masquer
    * @param $events array liste d'evenement a ecouter 
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function jsHideFago($toobserve, $tohide, $events = ['change']) {
      foreach ($events as $envent) {
         echo "<script type='text/javascript' >\n";
            echo"$(function() {\n";
               echo"$('[name=\"$toobserve\"]').on(\n";
                  echo "\"$envent\",\n";
                  echo"function(event) {;\n";
                     echo"$('#$tohide').hide();";
               echo"})\n";
            echo"});";
         echo "\n</script>";
      }
   }

   
   /**
    * Get javascript code for disable input
    *
    * @param $toobserve string id dom de l'élement à ecouter
    * @param $todisable string id dom de l'input à désactiver
    * @param $events array liste d'evenements à ecouter 
    *
    * @since 0.85.
    *
    * @return String
   **/
   static function jsDisableFago($toobserve, $todisable, $events = ['change']) {
      foreach ($events as $envent) {
         echo "<script type='text/javascript' >\n";
            echo"$(function() {\n";
               echo"$('[name=\"$toobserve\"]').on(\n";
                  echo "\"$envent\",\n";
                  echo"function(event) {;\n";
                     echo"$('[name=\"$todisable\"]').attr('disabled', true);";
               echo"})\n";
            echo"});";
         echo "\n</script>";
      }
   }

   static function jsChangeHtmlAttr($inputname, $attrname, $attrvalue) {
      echo "<script type='text/javascript' >\n";
         echo"$(function() {\n";
            echo"$('[name=\"$inputname\"]').attr(\"$attrname\",  $attrvalue);";
         echo"});";
      echo "\n</script>";
   }

   static function MakeInputToreadOnly($name) {
      echo "<script type='text/javascript' >\n";
         echo"$(function() {\n";
               echo"$('[name=\"$name\"]').attr('disabled', true);";
         echo"});";
      echo "\n</script>";
   }

   static function MakeAllInputToreadOnly() {
      echo "<script type='text/javascript' >\n";
         echo"$(function() {\n";
            echo"$('input, select, textarea').attr('disabled', true);";
         echo"});";
      echo "\n</script>";
   }

   /**
    * Get javascript code for hide an item 
    *
    * @param $toobserve string id dom de l'élement à ecouter
    * @param $toobserve string id dom de l'élement à aficher
    * @param $events array liste d'evenements à ecouter 
    * @since 0.85.
    *
    * @return String
   **/
   static function jsShowFago($toobserve, $toshow, $events = ['change']) {
      foreach ($events as $envent) {
         echo "<script type='text/javascript' >\n";
            echo"$(function() {\n";
               echo"$('[name=\"$toobserve\"]').on(\n";
                  echo "\"$envent\",\n";
                  echo"function(event) {;\n";
                     echo"$('#$toshow').show();";
               echo"})\n";
            echo"});";
         echo "\n</script>";
      }
   }

   /**
    * Set dropdown value
    *
    * @param $id      string   id of the dom element
    * @param $value   string   value to set
    *
    * @since 0.85.
    *
    * @return string
   **/
   static function jsSetDropdownValue($id, $value) {
      return self::jsGetElementbyID($id).".trigger('setValue', '$value');";
   }

   static function jsReplaceDropdownValue($id, $value) {
      return self::jsGetElementbyID($id).".replaceWith('$value');";
   }
   static function jsSetDropdownValueFago($id, $value) {
      return self::jsGetElementbyName($id).".trigger('setValue', '$value');";
   }

   static function jsSetInputValueFago($id, $value) {
      return self::jsGetElementbyName($id).".val('$value');";
   }

   static function jsReomveDropdownValueFago($domid, $name, $id) {
      return self::jsGetElementbyName($domid).".val(null).trigger('change');";
   }
   
   static function jsAddDropdownValueFago($domid, $name, $id) {
      echo "var newOption = new Option($name, $id, false, false);>\n";
      return self::jsGetElementbyName($domid).".append(newOption).trigger('change');";
   }
   /**
    * Create a close form part including CSRF token
    *
    * @param $display boolean Display or return string (default true)
    *
    * @since 0.83.
    *
    * @return String
   **/
   static function closeForm ($display = true) {
      global $CFG_GLPI;

      $out = "\n";
      if (GLPI_USE_CSRF_CHECK) {
         $out .= Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()])."\n";
      }

      if (isset($CFG_GLPI['checkbox-zero-on-empty']) && $CFG_GLPI['checkbox-zero-on-empty']) {
         $js = "   $('form').submit(function() {
         $('input[type=\"checkbox\"][data-glpicore-cb-zero-on-empty=\"1\"]:not(:checked)').each(function(index){
            // If the checkbox is not validated, we add a hidden field with '0' as value
            if ($(this).attr('name')) {
               $('<input>').attr({
                  type: 'hidden',
                  name: $(this).attr('name'),
                  value: '0'
               }).insertAfter($(this));
            }
         });
      });";
         $out .= Html::scriptBlock($js)."\n";
         unset($CFG_GLPI['checkbox-zero-on-empty']);
      }

      $out .= "</form>\n";
      if ($display) {
         echo $out;
         return true;
      }
      return $out;
   }
   
   /**
    * Display a div containing messages set in session in the previous page
   **/
   static function displayMessageAfterRedirect() {

      // Affichage du message apres redirection
      if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])
         && count($_SESSION["MESSAGE_AFTER_REDIRECT"]) > 0) {

         foreach ($_SESSION['MESSAGE_AFTER_REDIRECT'] as $msgtype => $messages) {
            //get messages
            if (count($messages) > 0) {
               $html_messages = implode('<br/>', $messages);
            } else {
               continue;
            }

            //set title and css class
            switch ($msgtype) {
               case ERROR:
                  $title = __s('Error');
                  $class = 'err_msg';
                  break;
               case WARNING:
                  $title = __s('Warning');
                  $class = 'warn_msg';
                  break;
               case INFO:
                  $title = _sn('Information', 'Information', 1);
                  $class = 'info_msg';
                  break;
            }

            echo "<div id=\"message_after_redirect_$msgtype\" title=\"$title\">";
            echo $html_messages;
            echo "</div>";

            $scriptblock = "
               $(function() {
                  var _of = window;
                  var _at = 'right-20 bottom-20';
                  //calculate relative dialog position
                  $('.message_after_redirect').each(function() {
                     var _this = $(this);
                     if (_this.attr('aria-describedby') != 'message_after_redirect_$msgtype') {
                        _of = _this;
                        _at = 'right top-' + (10 + _this.outerHeight());
                     }
                  });

                  $('#message_after_redirect_$msgtype').dialog({
                     dialogClass: 'message_after_redirect $class',
                     minHeight: 40,
                     minWidth: 200,
                     position: {
                        my: 'right bottom',
                        at: _at,
                        of: _of,
                        collision: 'none'
                     },
                     autoOpen: false,
                     show: {
                     effect: 'slide',
                     direction: 'down',
                     'duration': 800
                     }
                  })
                  .dialog('open');";

            //do not autoclose errors
            if ($msgtype != ERROR) {
               $scriptblock .= "

                  // close dialog on outside click
                  $(document.body).on('click', function(e){
                     if ($('#message_after_redirect_$msgtype').dialog('isOpen')
                        && !$(e.target).is('.ui-dialog, a')
                        && !$(e.target).closest('.ui-dialog').length) {
                        $('#message_after_redirect_$msgtype').remove();
                        // redo focus on initial element
                        e.target.focus();
                     }
                  });";
            }

            $scriptblock .= "

               });
            ";

            echo Html::scriptBlock($scriptblock);
         }
      }

      // Clean message
      $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
   }


   static function showFormHeader($item, $options = []) {

      $ID     = $item->fields['id'];
      $params = [
         'target'       => $item->getFormURL(),
         'colspan'      => 2,
         'withtemplate' => '',
         'formoptions'  => '',
         'canedit'      => true,
         'formtitle'    => null,
         'noid'         => false
      ];
      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      // Template case : clean entities data
      if (($params['withtemplate'] == 2)
            && $item->isEntityAssign()) {
         $item->fields['entities_id']  = $_SESSION['glpiactive_entity'];
      }

      $rand = mt_rand();
      if ($item->canEdit($ID)) {
         // echo strtolower(get_class($item));
         echo "<form name='form' id='".strtolower(get_class($item))."form' class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed'   ".$params['formoptions']." enctype=\"multipart/form-data\">";
         // echo "<form name='form' id='ticketform' class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed'  
         //          method='post' action='".$params['target']."' ".
         //          $params['formoptions']." enctype=\"multipart/form-data\">";

         //Should add an hidden entities_id field ?
         //If the table has an entities_id field
         if ($item->isField("entities_id")) {
            //The object type can be assigned to an entity
            if ($item->isEntityAssign()) {
               if (isset($params['entities_id'])) {
                  $entity = $item->fields['entities_id'] = $params['entities_id'];
               } else if (isset($item->fields['entities_id'])) {
                  //It's an existing object to be displayed
                  $entity = $item->fields['entities_id'];
               } else if ($item->isNewID($ID)
                        || ($params['withtemplate'] == 2)) {
                  //It's a new object to be added
                  $entity = $_SESSION['glpiactive_entity'];
               }
               echo "<input type='hidden' name='entities_id' value='$entity'>";
            } else if ($item->getType() != 'User') {
               // For Rules except ruleticket and slalevel
               echo "<input type='hidden' name='entities_id' value='0'>";

            }
         }
      }

      // echo "<div class='spaced' id='tabsbody'>";
      // echo "<table class='tab_cadre_fixe' id='mainformtable'>";

      if ($params['formtitle'] !== '' && $params['formtitle'] !== false) {
         // echo "<tr class='headerRow'><th colspan='".$params['colspan']."'>";

         if (!empty($params['withtemplate']) && ($params['withtemplate'] == 2)
            && !$item->isNewID($ID)) {

            echo "<input type='hidden' name='template_name' value='".$item->fields["template_name"]."'>";

            //TRANS: %s is the template name
            // printf(__('Created from the template %s'), $item->fields["template_name"]);

         } else if (!empty($params['withtemplate']) && ($params['withtemplate'] == 1)) {
            echo "<input type='hidden' name='is_template' value='1'>\n";
            // echo "<label for='textfield_template_name$rand'>" . __('Template name') . "</label>";
            // Html::autocompletionTextField(
            //    $item,
            //    'template_name',
            //    [
            //       'size'      => 25,
            //       'required'  => true,
            //       'rand'      => $rand
            //    ]
            // );
         }
         $entityname = '';
         if (isset($item->fields["entities_id"])
            && Session::isMultiEntitiesMode()
            && $item->isEntityAssign()) {
            $entityname = Dropdown::getDropdownName("glpi_entities", $item->fields["entities_id"]);
         }
         if (get_class($item) != 'Entity' && get_class($item) != 'PluginServicesEntity') {
            if ($item->maybeRecursive()) {
               if (Session::isMultiEntitiesMode()) {
                  // echo "<table class='tab_format'><tr class='headerRow responsive_hidden'><th>".$entityname."</th>";
                  // echo "<th class='right'><label for='dropdown_is_recursive$rand'>".__('Child entities')."</label></th><th>";
                  if ($params['canedit']) {
                     if ($item instanceof CommonDBChild) {
                        // echo Dropdown::getYesNo($item->isRecursive());
                        if (isset($item->fields["is_recursive"])) {
                           echo "<input type='hidden' name='is_recursive' value='".$item->fields["is_recursive"]."'>";
                        }
                        $comment = __("Can't change this attribute. It's inherited from its parent.");
                        // CommonDBChild : entity data is get or copy from parent

                     } else if (!$item->can($ID, 'recursive')) {
                        // echo Dropdown::getYesNo($item->fields["is_recursive"]);
                        $comment = __('You are not allowed to change the visibility flag for child entities.');

                     } else if (!$item->canUnrecurs()) {
                        echo Dropdown::getYesNo($item->fields["is_recursive"]);
                        $comment = __('Flag change forbidden. Linked items found.');

                     } else {
                        // Dropdown::showYesNo("is_recursive", $item->fields["is_recursive"], -1, ['rand' => $rand]);
                        $comment = __('Change visibility in child entities');
                     }
                     // echo " ";
                     // Html::showToolTip($comment);
                  } else {
                     // echo Dropdown::getYesNo($item->fields["is_recursive"]);
                  }
                  // echo "</th></tr></table>";
               } else {
                  // echo $entityname;
                  echo "<input type='hidden' name='is_recursive' value='0'>";
               }
            } else {
               // echo $entityname;
            }
         }
      }

      Plugin::doHook("pre_item_form", ['item' => $item, 'options' => &$params]);

      // If in modal : do not display link on message after redirect
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         echo "<input type='hidden' name='_no_message_link' value='1'>";
      }

   }


   static function showFormButtonsFooter($item, $options = []) {

      // for single object like config
      if (isset($item->fields['id'])) {
         $ID = $item->fields['id'];
      } else {
         $ID = 1;
      }

      $params = [
         'colspan'      => 2,
         'withtemplate' => '',
         'candel'       => true,
         'canedit'      => true,
         'addbuttons'   => [],
         'formfooter'   => null,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      Plugin::doHook("post_item_form", ['item' => $item, 'options' => &$params]);

      // if ($params['formfooter'] === null) {
      //    $item->showDates($params);
      // }

      if (!$params['canedit']
            || !$item->canEdit($ID)) {
         // Form Header always open form
         self::closeForm();
         return false;
      }

      if ($params['withtemplate']
            ||$item->isNewID($ID)) {

         if (($ID <= 0) || ($params['withtemplate'] == 2)) {
            echo self::submit(
                  _x('button', 'Soumettre'),
               [
                  'name' => 'add',
                  'id' => 'addForm'
               ]
            );
         } else {
            //TRANS : means update / actualize
            echo self::submit(
               _x('button', 'Soumettre'),
               [
                  'name' => 'update',
                  'id' => 'editForm'
               ]
            );
         }

      } else {
         if ($params['canedit'] && $item->can($ID, UPDATE)) {
            echo self::submit(
                  _x('button', 'Soumettre'),
               [
                  'name' => 'update',
                  'id' => 'editForm'
               ]
            );
         }
         if ($item->isField('date_mod')) {
            echo "<input type='hidden' name='_read_date_mod' value='".$item->getField('date_mod')."'>";
         }
      }

      if (!$item->isNewID($ID)) {
         echo "<input type='hidden' name='id' value='$ID'>";
      }


      if ($params['canedit']
         && count($params['addbuttons'])) {
         foreach ($params['addbuttons'] as $key => $val) {
            echo "<button type='submit' class='form-control' name='$key' value='1'>
                  $val
               </button>&nbsp;";
         }
      }
      self::closeForm();
   }
   
   static function showFormButtonsHeader($item, $options = []) {

      // for single object like config
      if (isset($item->fields['id'])) {
         $ID = $item->fields['id'];
      } else {
         $ID = 1;
      }

      $params = [
         'withtemplate' => '',
         'candel'       => true,
         'canedit'      => true,
         'addbuttons'   => [],
         'formfooter'   => null,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      Plugin::doHook("post_item_form", ['item' => $item, 'options' => &$params]);
      if (!$params['canedit']
            || !$item->canEdit($ID)) {
         // Form Header always open form
         return false;
      }

      if ($params['withtemplate']
            ||$item->isNewID($ID)) {

         if (($ID <= 0) || ($params['withtemplate'] == 2)) {
            echo self::submit(
                  _x('button', 'Soumettre'),
               ['name' => 'add']
            );
         } else {
            //TRANS : means update / actualize
            echo self::submit(
               _x('button', 'Soumettre'),
               ['name' => 'update']
            );
         }

      } else {
         if ($params['canedit'] && $item->can($ID, UPDATE)) {
            echo self::submit(
                  _x('button', 'Soumettre'),
               ['name' => 'update']
            );
         }
      }

      echo"
         <script>
            $('#m-portlet-head, button[name=update]').click(function(){
               $('#ticketform button[name=update]').click();
            });

            $('#m-portlet-head, button[name=add]').click(function(){
               $('#ticketform button[name=add]').click();
            });

            $('#m-portlet-head, button[name=delete]').click(function(){
               $('#ticketform button[name=delete]').click();
            });

            $('#m-portlet-head, button[name=purge]').click(function(){
               $('#ticketform button[name=purge]').click();
            });
         </script>
      ";
   }
   /**
    * Initialize item and check right before managing the edit form
    *
    * @since 0.84
    *
    * @param integer $ID      ID of the item/template
    * @param array   $options Array of possible options:
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *
    * @return integer|void value of withtemplate option (exit of no right)
   **/
   static function initForm($ID, $item, Array $options = []) {

      if (isset($options['withtemplate'])
         && ($options['withtemplate'] == 2)
         && !$item->isNewID($ID)) {
         // Create item from template
         // Check read right on the template
         $item->check($ID, READ);

         // Restore saved input or template data
         $input = $item->restoreInput($item->fields);

         // If entity assign force current entity to manage recursive templates
         if ($item->isEntityAssign()) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
         }

         // Check create right
         $item->check(-1, CREATE, $input);

      } else if ($item->isNewID($ID)) {
         // Restore saved input if available
         $input = $item->restoreInput($options);
         // Create item
         $item->check(-1, CREATE, $input);
      } else {
         // Existing item
         $item->check($ID, READ);
      }

      return (isset($options['withtemplate']) ? $options['withtemplate'] : '');
   }

   static function subHeader($page_title) {
      global $CFG_GLPI;
      echo'
         <div class="m-subheader ">
            <div class="d-flex d-flex align-items-center">
               <div class="mr-auto">
                  <h3 class="m-subheader__title m-subheader__title--separator">'; echo $page_title; echo'</h3>
                  <ul class="m-subheader__breadcrumbs m-nav m-nav--inline">
                     <li class="m-nav__item m-nav__item--home">
                        <a href="'.$CFG_GLPI['root_doc'].'/portal" class="m-nav__link m-nav__link--icon">
                           <i class="m-nav__link-icon la la-home"></i>
                        </a>
                     </li>';
                     $path = (str_ends_with($_GET['url'], '/')) ? substr($_GET['url'], 0, -1) : $_GET['url'] ;
                     $array_path = explode("/",  $path);
                     
                     if (str_ends_with($path, 'form') || str_ends_with($path, 'list')) {
                        //list or form
                        $view = array_pop($array_path);
                     }
                     // print_r($array_path);
                     // exit(0);
                     foreach ($array_path as $key =>  $value):
                        if ($key !=0) {
                           $last = (isset($view)) ?  explode("/", $_GET['url'])[$key].'/'.$view :  explode("/", $_GET['url'])[$key] ;
                           $first = explode("/", $_GET['url'])[$key-1];
                           // print_r( $last);
                           // exit(0);
                           echo'
                              <li class="m-nav__separator">-</li>
                              <li class="m-nav__item">
                                 <a href="'.$CFG_GLPI['root_doc'].'/'. $first.'/'. $last.'" class="m-nav__link">
                                    <span class="m-nav__link-text"> '.$value. '</span>
                                 </a>
                              </li>
                           ';
                        }else {
                           echo'
                              <li class="m-nav__separator">-</li>
                              <li class="m-nav__item">
                                 <a href="'.$CFG_GLPI['root_doc'].'/'. $value.'" class="m-nav__link">
                                    <span class="m-nav__link-text"> '.$value. '</span>
                                 </a>
                              </li>
                           ';
                        }
                        
                     endforeach;
                        echo'
                  </ul>
               </div>
            </div>
         </div>
      ';
   }

   static function generateForm($ID, $item, $options, $all_fields = []) {
      if ($ID > 0 || (get_class($item) == 'PluginServicesEntity' && $ID == 0)) {
         //Vérifie si l'utilisateur a le droit de lire sur la table
         $item->check($ID, READ);
         //si l'utilisateur n'a pas le droit de modifier on grise les champs du formulaire
         if (!$item->canUpdateItem()) {
            self::MakeAllInputToreadOnly();
         }
      } else {
         // Create item
         $item->check(-1, CREATE, $options);
      }
      echo'<div class="m-content">';
         echo'<div class="row">';
            echo'<div class="col-lg-12">';
               echo'<div class="m-portlet">';
                  echo'<div class="m-portlet__head">';
                     echo'<div class="m-portlet__head-caption">';
                     echo'<div class="m-portlet__head-title">';
                     echo'</div>';
                     echo'</div>';
                     echo'<div class="m-portlet__head-tools" id="m-portlet-head">';
                        // afficher la fonction showFormHeader de la classe appelant
                        // self::showFormButtonsHeader($item, $options);
                        if (!$item->isNewID($ID)) {
                           echo'
                              <div class="m-portlet__head-tools">
                                 <ul class="m-portlet__nav">
                                    <li class="m-portlet__nav-item">
                                       <a data-offset="20px 20px" data-toggle="m-tooltip" data-placement="bottom" title="" data-original-title="Retourner sur la liste" href="'.PluginServicesToolbox::getItemTypeSearchURL(get_class($item)).'" class="m-portlet__nav-link btn btn-secondary m-btn m-btn--hover-brand m-btn--icon m-btn--icon-only m-btn--pill">
                                          <i class="m-nav__link-icon fas fa-list"></i>
                                       </a>
                                    </li>
                                    <li class="m-portlet__nav-item m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push" m-dropdown-toggle="hover" aria-expanded="true">
                                       <a href="#" class="m-portlet__nav-link btn btn-secondary  m-btn m-btn--hover-brand m-btn--icon m-btn--icon-only m-btn--pill   m-dropdown__toggle">
                                          <i class="la la-cog"></i>
                                       </a>
                                       <div class="m-dropdown__wrapper" style="z-index: 101;">
                                          <span class="m-dropdown__arrow m-dropdown__arrow--right m-dropdown__arrow--adjust" style="left: auto; right: 21.5px;"></span>
                                          <div class="m-dropdown__inner">
                                             <div class="m-dropdown__body">
                                                <div class="m-dropdown__content">
                                                   <ul class="m-nav">
                                                      <li class="m-nav__item">
                                                         <a href="'.PluginServicesToolbox::getItemTypeSearchURL("PluginServicesRelatedlist").'?type='.$item->getClassName().'" class="m-nav__link">
                                                            <i class="m-nav__link-icon fas fa-list"></i>
                                                            <span class="m-nav__link-text">Listes associées</span>
                                                         </a>
                                                      </li>';
                                                      if (Session::canImpersonate($ID)) {
                                                         echo'<li class="m-nav__item">
                                                            <a  onclick="impersonate(event)"  href="javascript:void(0);"  class="m-nav__link">
                                                               <i class="m-nav__link-icon  fas fa-user-secret fa-lg" title="' . __s('Impersonate') . '"></i> 
                                                               <span class="m-nav__link-text">' . __s('Impersonate') . '</span>
                                                            </a>
                                                         </li>';
                                                      };
                                                      echo'
                                                   </ul>
                                                </div>
                                             </div>
                                          </div>
                                       </div>
                                    </li>
                                 </ul>
                              </div>
                           ';
                        }
                     echo'</div>';
                  echo'</div>';
                  // afficher la fonction showFormHeader de la classe appelant
                  self::showFormHeader($item, $options);
                  echo'<div class="m-portlet__body">';
                     echo'<div style="padding-top: 10px;" class="row">';
                        foreach ($all_fields as $field) {
                           if ($item->isNewID($ID)) {
                              if (isset($field['is_new_id']) && $field['is_new_id'] === false ) {
                                 continue;
                              }
                           }else {
                              if (isset($field['is_new_id']) && $field['is_new_id'] === true ) {
                                 continue;
                              }
                           }
                           
                           // controler la condition avant d'afficher le champ
                           if (isset($field['cond'])) {
                              if (!$field['cond']) {
                                 continue;
                              }
                           }

                           $class = (isset($field['full']) && $field['full'] == true) ? 'class="col-xs-10 col-md-8 col-lg-8"' : 'class="col-xs-10 col-sm-9 col-md-5 col-lg-5"' ;
                           $classlabel = (isset($field['full']) && $field['full'] == true) ? 'class="col-xs-12 col-md-1_5 col-lg-2 control-label"' : 'class="col-xs-12 col-md-4 col-lg-4 control-label"' ;
                           $classcontainer = (isset($field['full']) && $field['full'] == true) ? 'class="col-sm-12 mb-3"' : 'class="col-sm-6 mb-3"' ;
                        
                           echo'<div  id="'.$field['name'].'" style="display: table;" '.$classcontainer.'>';
                              if ($field['type'] !== 'hidden') {
                                 echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  '.$classlabel.'>'; 
                                    echo (isset($field['label'])) ? __($field['label']) : '' ; 
                                    echo (isset($field['mandatory']) && $field['mandatory'])? '&nbsp;<span class="text-danger">*</span>': '' ; 
                                 echo'</label>';
                              }
                                    
                              echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  '.$class.'>';
                                 switch ($field['type']) {
                                    case 'datetime':
                                       $params =  ['value' => $item->fields[$field['name']]];
                                       if (isset($field['maybeempty'])) {
                                          $params['maybeempty'] = $field['maybeempty'];
                                       }

                                       if (isset($field['readOnly'])) {
                                          $params['canedit'] = ($field['readOnly']) ? false : true ;
                                       }
                                       PluginServicesHtml::showDateTimeField($field['name'], $params);
                                       break;
                                    case 'text':
                                       $params = ['value' => $item->fields[$field['name']]];
                                       if (isset($field['readOnly'])) {
                                          $params['readonly'] = 'readonly';
                                       }
                                       if (isset($field['mandatory'])) {
                                          $params['required'] = $field['mandatory'];
                                       }
                                       if (isset($field['value'])) {
                                          $params['value'] = $field['value'];
                                       }
                                       $params['type'] = 'text';
                                       echo PluginServicesHtml::input($field['name'], $params);
                                       break;
                                    case 'number' :
                                       $params = ['value' => $item->fields[$field['name']]];
                                       if (isset($field['min'])) {
                                          $params['min'] = $field['min'];
                                       }
                                       if (isset($field['step'])) {
                                          $params['step'] = $field['step'];
                                       }
                                       if (isset($field['max'])) {
                                          $params['max'] = $field['max'];
                                       }
                                       if (isset($field['mandatory'])) {
                                          $params['required'] = $field['mandatory'];
                                       }
                                       if (isset($field['readOnly'])) {
                                          $params['disabled'] = $field['readOnly'];
                                       }
                                       $params['type'] = 'number';
                                       echo PluginServicesHtml::input($field['name'], $params);
                                       break;
                                    case 'textarea':
                                       $isMandatory = (isset($field['mandatory'])) ? $field['mandatory'] : false;
                                       $readOnly = (isset($field['readOnly'])) ? $field['readOnly'] : false;
                                       PluginServicesHtml::textarea([
                                          'name'  =>$field['name'],
                                          'value'  =>  $item->fields[$field['name']],
                                          'required'  => $isMandatory,
                                          'disabled'  => $readOnly,
                                       ]);
                                       break;
                                    case 'file':
                                       $params = [
                                          'name'   => $field['name'],
                                          'entity' => $item->getEntityID()
                                       ];
                                       if (is_array($field) && count($field)) {
                                          foreach ($field as $key => $val) {
                                             $params[$key] = $val;
                                          }
                                       }
                                       echo PluginServicesHtml::file($params);
                                       break;
                                    case 'boolean' :
                                       PluginServicesDropdown::showYesNo($field['name'], $item->fields[$field['name']]);
                                       break;
                                    case 'dropdownNumber' :
                                       PluginServicesDropdown::showNumber($field['name'], [
                                          'value'  =>  $item->fields[$field['name']],
                                          'min'   => 0,
                                          'max'   => 100,
                                          'step'  => 1,
                                          // 'toadd' => ['-1' => __('Never')]
                                       ]);
                                       break;
                                    case 'dropdown':
                                       $ForeignKeyField = (str_starts_with($field['name'], '_')) ? substr($field['name'], 1) : $field['name'];
                                       $params = [
                                          'value'  => $item->fields[$ForeignKeyField],
                                          'name'   => $field['name'],
                                          'entity' => $item->getEntityID(),
                                          'display_emptychoice' => true
                                       ];
                                       if (isset($field['condition'])) {
                                          $params['condition'] = $field['condition'];
                                       }
                                                                                                                                                                                                               
                                       // if (isset($field['readOnly'])) {
                                       //    $params['disabled'] = $field['readOnly'];
                                       // }
                                       if (isset($field['mandatory'])) {
                                          $params['required'] = $field['mandatory'];
                                       }
                                       
                                       if (is_array($field) && count($field)) {
                                          foreach ($field as $key => $val) {
                                             $params[$key] = $val;
                                          }
                                       }
                                       PluginServicesDropdown::show(getItemTypeForTable(getTableNameForForeignKeyField($ForeignKeyField)), $params);
                                       break;
                                    case 'function':
                                       
                                       $class = (isset($field['itemtype'])) ? $field['itemtype']: $item;
                                       if (method_exists($class,  $field['name'])) {
                                          call_user_func_array([$class, $field['name']],[$field['params']]);
                                       }
                                       break; 
                                    case 'password':
                                       $name = $field['name'];
                                       echo "<input class='form-control' name='$name' type='password' value=''  autocomplete='new-password'>";
                                       break;
                                       case 'parent' :
                                          if ($field['name'] == 'entities_id') {
                                             $restrict = -1;
                                          } else {
                                             $restrict = $item->getEntityID();
                                          }
                                          
                                          PluginServicesDropdown::show(getItemTypeForTable($item->getTable()),
                                                         ['value'  => $item->fields[$field['name']],
                                                            'name'   => $field['name'],
                                                            'entity' => $restrict,
                                                            'used'   => ($ID>0 ? getSonsOf($item->getTable(), $ID)
                                                                                    : [])]);
                                          break;
                                       case 'checkbox':
                                          pluginServicesHtml::showCheckbox([
                                             'name' => $field['name'], 
                                             'checked' => $item->fields[$field['name']]
                                          ]);
                                          break;
                                 }
                              echo'</div>';

                              echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                              echo'</div>';
                           echo'</div>';
                           
                           // afficher les champ hhiden
                           if ($field['type'] === 'hidden') {
                              $name = $field['name'];
                              $value =  (isset($field['value'])) ? $field['value'] : $item->fields[ $field['name']] ;
                              echo "<input class='form-control' type='hidden' name='$name' value='$value'  >";
                           }

                           // add events
                           if (isset($field['events']) && $field['events']) {
                              if (isset($field['events']['params'])) {
                                 $option = $field['events']['params'];
                              } else {
                                 $option = [];
                              }
                              
                              if (isset($field['events']['input_type'])) {
                                 $option['input_type'] = $field['events']['input_type'];
                              }
                              $option['action'] = $field['events']['action'];
                              $inputcible =  $field['events']['input_cible'];
                              $events =  $field['events']['type'];
                              $url = (isset($field['events']['url'])) ? $field['events']['url'] : null ;
                              $name = ($field['type'] === 'function') ? $field['params']['name'] : $field['name'] ;
                              static::updateInput($name, $inputcible, $url, $option, $events);
                           }

                           // control right befor display input
                           if (isset($field['type'])) {
                              $name = ($field['type'] === 'function') ? $field['params']['name'] : $field['name'] ;
                              if (method_exists($item,'canUpdateField')) {
                                 if (! $item->canUpdateField($name, $ID)) {
                                    self::MakeInputToreadOnly($name);
                                 }
                              }
                           }
                           if (isset($field['name'])) {
                              $name = ($field['type'] === 'function') ? $field['params']['name'] : $field['name'] ;
                              if (isset($field['readOnly']) && $field['readOnly']) {
                                 self::jsChangeHtmlAttr($name, 'readonly', true);
                              }elseif (isset($field['mandatory']) && $field['mandatory']) {
                                 self::jsChangeHtmlAttr($name, 'required', true);
                              }
                           }
                        }
                     echo'</div>';
                  echo '</div>';
                  echo'
                     <div class="m-portlet__foot m-portlet__no-border m-portlet__foot--fit">
                        <div class="m-form__actions d-flex justify-content-end">';
                           self::showFormButtonsFooter($item, $options);
                           echo'
                        </div>
                     </div>
                  ';

                  echo'
                     <div class="m-portlet__foot m-portlet__no-border m-portlet__foot--fit">
                        <div class=" m-form__actions--solid d-flex justify-content-end">';
                           $item->showTabsContent([]);
                           echo'
                        </div>
                     </div>
                  ';
               echo'</div>';
            echo'</div>';
         echo'</div>';
      echo'</div>';

      echo "
         <style>
            .dropdown-relatedlist {
               padding: 0.5rem 0;
               margin: 0.125rem 0 0;
               font-size: 1rem;
               color: #212529;
               text-align: left;
               list-style: none;
               background-color: #fff;
               background-clip: padding-box;
               border: 1px solid rgba(0, 0, 0, 0.15);
               border-radius: 0.25rem;
            }
            .col-lg-8 {
               max-width: 71.4%;
            }
            .col-lg-2 {
               max-width: 16.4%;
            }
            .col-lg-9 {
               max-width: 73.4%;
            }
         </style>
      ";
   }

   static function generateSimpleForm($ID, $item, $options, $all_fields = []) {
      if ($ID > 0) {
         //Vérifie si l'utilisateur a le droit de lire sur la table
         echo  $item->check($ID, READ);
         //si l'utilisateur n'a pas le droit de modifier on grise les champs du formulaire
         if (!$item->canUpdateItem()) {
            self::MakeAllInputToreadOnly();
         }
      } else {
         // Create item
         $item->check(-1, CREATE, $options);
      }

      self::showFormHeader($item, $options);

      echo'<div style="padding-top: 10px;" class="row">';
         foreach ($all_fields as $field) {
            if ($item->isNewID($ID)) {
               if (isset($field['is_new_id']) && $field['is_new_id'] === false ) {
                  continue;
               }
            }else {
               if (isset($field['is_new_id']) && $field['is_new_id'] === true ) {
                  continue;
               }
            }
            
            // controler la condition avant d'afficher le champ
            if (isset($field['cond'])) {
               if (!$field['cond']) {
                  continue;
               }
            }

             // afficher les champ hhiden
            if ($field['type'] === 'hidden') {
               $name = $field['name'];
               $value =  (isset($field['value'])) ? $field['value'] : $item->fields[ $field['name']] ;
               echo "<input class='form-control' type='hidden' name='$name' value='$value'  >";
               continue;
            }
            $class = (isset($field['full']) && $field['full'] == true) ? 'class="col-xs-10 col-md-8 col-lg-8"' : 'class="col-xs-10 col-sm-9 col-md-5 col-lg-5"' ;
            $classlabel = (isset($field['full']) && $field['full'] == true) ? 'class="col-xs-12 col-md-1_5 col-lg-2 control-label"' : 'class="col-xs-12 col-md-4 col-lg-4 m-form labe"' ;
            $classcontainer = (isset($field['full']) && $field['full'] == true) ? 'class="col-sm-12 mb-3"' : 'class="col-sm-6 mb-3"' ;
         
            echo'<div  id="'.$field['name'].'" style="display: table;" '.$classcontainer.'>';

            if ($field['type'] !== 'hidden') {
               echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  '.$classlabel.'>'; 
                  echo (isset($field['label'])) ? __($field['label']) : '' ; 
                  echo (isset($field['mandatory']) && $field['mandatory'])? '&nbsp;<span class="text-danger">*</span>': '' ; 
               echo'</label>';
            }
                  
                     
               echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  '.$class.'>';
                  switch ($field['type']) {
                     case 'datetime':
                        $params =  ['value' => $item->fields[$field['name']]];
                        if (isset($field['maybeempty'])) {
                           $params['maybeempty'] = $field['maybeempty'];
                        }

                        if (isset($field['readOnly'])) {
                           $params['canedit'] = ($field['readOnly']) ? false : true ;
                        }
                        PluginServicesHtml::showDateTimeField($field['name'], $params);
                        break;
                     case 'text':
                        $params = [];
                        if (isset($field['readOnly'])) {
                           $params['disabled'] = $field['readOnly'];
                        }
                        if (isset($field['isMandatory'])) {
                           $params['required'] = $field['isMandatory'];
                        }
                        if (isset($field['value'])) {
                           $params['value'] = $field['value'];
                        }
                        PluginServicesHtml::autocompletionTextField($item, $field['name'], $params);
                        break;
                     case 'number' :
                        $params = ['value' => $item->fields[$field['name']]];
                        if (isset($field['min'])) {
                           $params['min'] = $field['min'];
                        }
                        if (isset($field['step'])) {
                           $params['step'] = $field['step'];
                        }
                        if (isset($field['max'])) {
                           $params['max'] = $field['max'];
                        }
                        if (isset($field['isMandatory'])) {
                           $params['required'] = $field['isMandatory'];
                        }
                        if (isset($field['readOnly'])) {
                           $params['disabled'] = $field['readOnly'];
                        }
                     

                        echo PluginServicesHtml::input(
                           $field['name'], [
                              'type'   => 'number'
                           ] + $params
                        );
                        break;
                     case 'textarea':
                        $isMandatory = (isset($field['isMandatory'])) ? $field['isMandatory'] : false;
                        $readOnly = (isset($field['readOnly'])) ? $field['readOnly'] : false;
                        PluginServicesHtml::textarea([
                           'name'  =>$field['name'],
                           'value'  =>  $item->fields[$field['name']],
                           'required'  => $isMandatory,
                           'disabled'  => $readOnly,
                        ]);
                        break;
                     case 'file':
                        echo PluginServicesHtml::file([
                           'name'       => $field['name']
                        ]);
                        break;
                     case 'boolean' :
                        PluginServicesDropdown::showYesNo($field['name'], $item->fields[$field['name']]);
                        break;
                     case 'dropdown':
                        $ForeignKeyField = (str_starts_with($field['name'], '_')) ? substr($field['name'], 1) : $field['name'];
                        $params = [
                           'value'  => $item->fields[$ForeignKeyField],
                           'name'   => $field['name'],
                           'entity' => $item->getEntityID(),
                           'display_emptychoice' => true
                        ];
                        if (isset($field['condition'])) {
                           $params['condition'] = $field['condition'];
                        }

                        if (isset($field['readOnly'])) {
                           $params['disabled'] = $field['readOnly'];
                        }
                        if (isset($field['mandatory'])) {
                           $params['required'] = $field['mandatory'];
                        }
                        
                        if (is_array($field) && count($field)) {
                           foreach ($field as $key => $val) {
                              $params[$key] = $val;
                           }
                        }
                        PluginServicesDropdown::show(getItemTypeForTable(getTableNameForForeignKeyField($ForeignKeyField)), $params);
                        break;
                     case 'function':
                        $class = (isset($field['itemtype'])) ? $field['itemtype']: $item;
                        if (method_exists($class,  $field['name'])) {
                           call_user_func_array([$class, $field['name']],   [$field['params']]);
                        }
                        break;
                     case 'password':
                        $name = $field['name'];
                        echo "<input class='form-control' name='$name' type='password' value=''  autocomplete='new-password'>";
                        break;
                        case 'parent' :
                           if ($field['name'] == 'entities_id') {
                              $restrict = -1;
                           } else {
                              $restrict = $item->getEntityID();
                           }
                           
                           PluginServicesDropdown::show(getItemTypeForTable($item->getTable()),
                                          ['value'  => $item->fields[$field['name']],
                                             'name'   => $field['name'],
                                             'entity' => $restrict,
                                             'used'   => ($ID>0 ? getSonsOf($item->getTable(), $ID)
                                                                     : [])]);
                           break;
                  }
               echo'</div>';

               echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
               echo'</div>';
            echo'</div>';
            

            // add events
            if (isset($field['events']) && $field['events']) {
               if (isset($field['events']['params'])) {
                  $option = $field['events']['params'];
               } else {
                  $option = [];
               }
               
               if (isset($field['events']['input_type'])) {
                  $option['input_type'] = $field['events']['input_type'];
               }
               $option['action'] = $field['events']['action'];
               $inputcible =  $field['events']['input_cible'];
               $events =  $field['events']['type'];
               $url = (isset($field['events']['url'])) ? $field['events']['url'] : null ;
               $name = ($field['type'] === 'function') ? $field['params']['name'] : $field['name'] ;
               static::updateInput($name, $inputcible, $url, $option, $events);
            }

            // control right befor display input
            if (isset($field['name'])) {
               if (method_exists($item,'canUpdateField')) {
                  if (! $item->canUpdateField($field['name'])) {
                     self::MakeInputToreadOnly($field['name']);
                  }
               }
            }
         }
      echo'</div>';
      echo'<div class="row justify-content-between">';
         echo'<div  style="display: table"  class="col-lg-12">';
            echo'<div style="float: left; padding-left: 0px;" class="col-xs-12 col-md-1_5 col-lg-2 control-label"></div>';
            echo'<div class=" p-0 d-flex justify-content-end col-xs-10 col-md-8 col-lg-8">';
               self::showFormButtonsFooter($item, $options);
            echo'</div>';
         echo'</div>';
      echo'</div>';
      echo'
         <style>
            .col-lg-8 {
               max-width: 71.4%;
            }
            .col-lg-2 {
                  max-width: 16.4%;
            }
      
            .col-lg-9 {
                  max-width: 73.4%;
            }
         </style>
      ';
   }


   static function showList($itemtype, $params=[]){
      if ($itemtype::canView()) {
         echo'
            <div class="m-content">
               <div class="row">
                  <div class="col-xl-12">
                     <div class="m-portlet m-portlet--tab">';
                        echo'<div class="m-portlet__head">';
                           echo'<div class="m-portlet__head-caption">';
                              echo'<div class="m-portlet__head-title">';
                                 echo'<span class="m-portlet__head-icon ">';
                                 echo'</span>';
                              echo'</div>';
                           echo'</div>';
                           echo'<div class="m-portlet__head-tools">';
                           if($itemtype::canCreate()){
                              echo' <a  href="'.PluginServicesToolbox::getItemTypeFormURL($itemtype).'"   class="btn m-btn--radius btn-md  btn-info">
                                    '.__s("NOUVEAU").'
                              </a>';
                           }
                           echo'</div>';
                        echo'</div>';
                        echo'<div class="m-portlet__bodys">';
                           echo'<div style="padding:10px; margin:auto;" class="bg-light">';
                              echo"<button id='togglesearchcriteria' style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;'  type='button' class='btn btn-sm btn-default '><i class='fa fa-filter'></i></button>";
                              echo"<button id='resetsearchcriteria' style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;'  type='button' class='btn btn-sm btn-default '>ALL</button>";
                           echo'</div>';
                           echo'<div style="padding:10px; margin:auto;" class="bg-light">';
                           // print_r($params);
                                 self::searchlist($itemtype, $params);
                           echo'</div>';
                              PluginServicesSearch::showFago($itemtype, $params);
                           echo'  
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         ';
      } else {
         //View is not granted.
         PluginServicesHtml::displayRightError();
      }
   }

   static function searchlist($itemtype, $params=[]) {
      $params = PluginServicesSearch::manageParams($itemtype, $params);
      $criterias = $params['criteria'];
      $notdropdownSearchType = [
         'notcontains',
         'contains'
      ];
      $searchtypes = [
         'contains' =>  __('contains'),
         'notcontains' => __('not contains'),
         'equals' =>  __('is'),
         'notequals' =>  __('is not'),
         'lessthan' => __('before'),
         'morethan' => __('after'),
         'under' => __('under'),
         'notunder' => __('not under')
      ];
      $logiqueoperator = PluginServicesSearch::getLogicalOperators();
      foreach ($criterias as $key => $value) {
         if (isset($value['criteria'])) {
            continue;
         }
         
         if($value["field"]!="view"){
            $link = (isset($value["link"])) ?  $logiqueoperator[$value["link"]] : '' ;
            $actions = PluginServicesSearch::getActionsFor($itemtype, $value["field"]);
            $searchopt = $actions["searchopt"];
            $searchvalue = $value["value"];
            $searchtype =  $searchtypes[$value["searchtype"]];
            
            //display value according to type 
            if (!in_array($value["searchtype"], $notdropdownSearchType)) {
               if (in_array($searchopt["datatype"], ['dropdown', 'itemlink'])) {
                  $searchvalue = Dropdown::getDropdownName($searchopt["table"], $value["value"]);
               }elseif ($searchopt["datatype"] === 'specific') {
                  $searchvalue = $itemtype::getSpecificValueToDisplay($searchopt["field"],  $value["value"]);
               }elseif ($searchopt["datatype"] === 'datetime') {
                  $dates = self::getGenericDateTimeSearchItems(["with_time" => true, "with_future" => true]);
                  $searchvalue = array_key_exists($searchvalue, $dates) ? $dates[$searchvalue] :  $searchvalue ;
               }
            }
            
            if($key==0){
               $field_name = PluginServicesFagoUtils::getSearchOptions($itemtype, $value["field"]);
               $field_name = str_replace("Caractéristiques / ", "", $field_name);
               $field_name = str_replace("N/A /", "", $field_name); 
               echo'<span style="background-color: #ffffff; color: #000; border: 1px solid #36a3f7;" class="m-badge m-badge--info m-badge--wide m-badge--rounded">'.$field_name.' > '.$searchtype.' > '.$searchvalue.'</span> ';
            }else{
               $field_name = PluginServicesFagoUtils::getSearchOptions($itemtype, $value["field"]);
               $field_name = str_replace("Caractéristiques / ", "", $field_name);
               $field_name = str_replace("N/A /", "", $field_name); 
               echo ''.$link.' <span style="background-color: #ffffff; color: #000; border: 1px solid #36a3f7;"  class="m-badge m-badge--info m-badge--wide m-badge--rounded">'.$field_name.' > '.$searchtype.' > '.$searchvalue.'</span> ';
            }
         }
            
      }
   }

   static function showRelatedList($itemtype, $params=[]){
      global $CFG_GLPI;
      if ($itemtype::canView()) {
         echo'
            <div class="row">
               <div class="col-xl-12">
                  <div class="m-portlet m-portlet--tab">';
                     echo'<div class="m-portlet__head">';
                        echo'<div class="m-portlet__head-caption">';
                           echo'<div class="m-portlet__head-title">';
                              echo'<span class="m-portlet__head-icon ">';
                              echo'</span>';
                           echo'</div>';
                        echo'</div>';
                        echo'<div class="m-portlet__head-tools">';
                           echo' <a href="'.PluginServicesToolbox::getItemTypeFormURL($itemtype).'" class="btn m-btn--radius btn-md  btn-info">
                                 '.__s("NOUVEAU").'
                           </a>';
                        echo'</div>';
                     echo'</div>';
                     echo'<div class="m-portlet__bodys">';
                        echo'<div style="padding:10px; margin:auto;" class="bg-light">';
                           echo"<button id='togglesearchcriteria' style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;'  type='button' class='btn btn-sm btn-default '><i class='fa fa-filter'></i></button>";
                           echo"<button id='resetsearchcriteria' style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;'  type='button' class='btn btn-sm btn-default '>ALL</button>";
                        echo'</div>';
                        echo'<div style="padding:10px; margin:auto;" class="bg-light">';
                        echo'</div>';
                           PluginServicesSearch::showDataRelatedList($itemtype, $params);
                        echo'  
                     </div>
                  </div>
               </div>
            </div>
         ';
      } else {
         //View is not granted.
         PluginServicesHtml::displayRightError();
      }
   }

   /**
    * Modifier un dropdown lorsqu'un autre est modifié
    * @param array  $toobserve  tableau d'information du dropdown à ecouter:
    *     - `name` name du dropdown à ecouter.
    *     - `rand` radom retour par le dropdow =>(sur glpi tous les dropdows retourn 
    *                                     un idenfiant uqnique utilisé pour identifer le dronpdown)

    * @param array  $toupdate  tableau d'information du dropdown à modifer:
    *     - `name` name du dropdown à modifer.
    *     - `rand` radom retour par le dropdow =>(sur glpi tous les dropdows retourn 
    *                                     un idenfiant uqnique utilisé pour identifer le dronpdown)
    * @return string url  l'url de la page ou la requete est traitée.
    * @return array  liste des parametres à passer a la requête.
   **/
   static function updateInput($toobserve, $toupdate, $url = null, $option = [], $events = ['change']) {
      global $CFG_GLPI;
      $url = ($url === null) ? $CFG_GLPI["root_doc"]."/ajax/dropdowsetInpu.php" : $url ;
      $option['to_update'] = $toupdate;
      $option['value'] = '__VALUE__';
      if (isset($option['action']) && $option['action']) {
         switch ($option['action']) {
            case 'hideInput':
            PluginServicesHtml::jsHideFago($toobserve, $toupdate, $events);
            break;
            case 'showInput':
            PluginServicesHtml::jsShowFago($toobserve, $toupdate);
            break;
            case 'disableInput':
            PluginServicesHtml::jsDisableFago($toobserve, $toupdate);
            break;
            case 'setInputValue':
            echo "<span id='idajax' style='display:none'></span>";
            PluginServicesAjax::updateItemOnSelectEventFago($toobserve, 'idajax', $url, $option, $events);
            break;
            case 'setInputData':
            echo "<span id='idajax' style='display:none'></span>";
            PluginServicesAjax::updateItemOnSelectEventFago($toobserve, 'idajax', $url, $option, $events);
            break;
         }
      }
   }

   /**
    * Display choice matrix
    *
    * @since 0.85
    * @param $columns   array   of column field name => column label
    * @param $rows      array    of field name => array(
    *      'label' the label of the row
    *      'columns' an array of specific information regaring current row
    *                and given column indexed by column field_name
    *                 * a string if only have to display a string
    *                 * an array('value' => ???, 'readonly' => ???) that is used to Dropdown::showYesNo()
    * @param $options   array   possible:
    *       'title'         of the matrix
    *       'first_cell'    the content of the upper-left cell
    *       'row_check_all' set to true to display a checkbox to check all elements of the row
    *       'col_check_all' set to true to display a checkbox to check all elements of the col
    *       'rand'          random number to use for ids
    *
    * @return integer random value used to generate the ids
   **/
   static function showCheckboxMatrix(array $columns, array $rows, array $options = []) {

      $param['title']                = '';
      $param['first_cell']           = '&nbsp;';
      $param['row_check_all']        = false;
      $param['col_check_all']        = false;
      $param['rotate_column_titles'] = false;
      $param['rand']                 = mt_rand();
      $param['table_class']          = 'tab_cadre_fixehov';
      $param['cell_class_method']    = null;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      $cb_options = ['title' => __s('Check/uncheck all')];

      $number_columns = (count($columns) + 1);
      if ($param['row_check_all']) {
         $number_columns += 1;
      }
      $width = round(100/$number_columns);
      echo "\n<table style='z-index: 1; text-align: left;' class='m-0 tab_cadrehov table table-striped m-table'>\n";

      if (!empty($param['title'])) {
         echo "\t<tr>\n";
         echo "\t\t<th colspan='$number_columns'>".$param['title']."</th>\n";
         echo "\t</tr>\n";
      }

      echo "\t<tr class='tab_bg_1'>\n";
      echo "\t\t<td>".$param['first_cell']."</td>\n";
      foreach ($columns as $col_name => $column) {
         $nb_cb_per_col[$col_name] = ['total'   => 0,
                                          'checked' => 0];
         $col_id                   = Html::cleanId('col_label_'.$col_name.'_'.$param['rand']);

         echo "\t\t<td class='center b";
         if ($param['rotate_column_titles']) {
            echo " rotate";
         }
         echo "' id='$col_id' width='$width%'>";
         if (!is_array($column)) {
            $columns[$col_name] = $column = ['label' => $column];
         }
         if (isset($column['short'])
            && isset($column['long'])) {
            echo $column['short'];
            self::showToolTip($column['long'], ['applyto' => $col_id]);
         } else {
            echo $column['label'];
         }
         echo "</td>\n";
      }
      if ($param['row_check_all']) {
         $col_id = Html::cleanId('col_of_table_'.$param['rand']);
         echo "\t\t<td class='center";
         if ($param['rotate_column_titles']) {
            echo "rotate";
         }
         echo "' id='$col_id'>".__('Select/unselect all')."</td>\n";
      }
      echo "\t</tr>\n";

      foreach ($rows as $row_name => $row) {

         if ((!is_string($row)) && (!is_array($row))) {
            continue;
         }

         echo "\t<tr class='tab_bg_1'>\n";

         if (is_string($row)) {
            echo "\t\t<th colspan='$number_columns'>$row</th>\n";
         } else {

            $row_id = Html::cleanId('row_label_'.$row_name.'_'.$param['rand']);
            if (isset($row['class'])) {
               $class = $row['class'];
            } else {
               $class = '';
            }
            echo "\t\t<td class='b $class' id='$row_id'>";
            if (!empty($row['label'])) {
               echo $row['label'];
            } else {
               echo "&nbsp;";
            }
            echo "</td>\n";

            $nb_cb_per_row = ['total'   => 0,
                                 'checked' => 0];

            foreach ($columns as $col_name => $column) {
               $class = '';
               if ((!empty($row['class'])) && (!empty($column['class']))) {
                  if (is_callable($param['cell_class_method'])) {
                     $class = $param['cell_class_method']($row['class'], $column['class']);
                  }
               } else if (!empty($row['class'])) {
                  $class = $row['class'];
               } else if (!empty($column['class'])) {
                  $class = $column['class'];
               }

               echo "\t\t<td class='center $class'>";

               // Warning: isset return false if the value is NULL ...
               if (array_key_exists($col_name, $row['columns'])) {
                  $content = $row['columns'][$col_name];
                  if (is_array($content)
                     && array_key_exists('checked', $content)) {
                     if (!array_key_exists('readonly', $content)) {
                        $content['readonly'] = false;
                     }
                     $content['massive_tags'] = [];
                     if ($param['row_check_all']) {
                        $content['massive_tags'][] = 'row_'.$row_name.'_'.$param['rand'];
                     }
                     if ($param['col_check_all']) {
                        $content['massive_tags'][] = 'col_'.$col_name.'_'.$param['rand'];
                     }
                     if ($param['row_check_all'] && $param['col_check_all']) {
                        $content['massive_tags'][] = 'table_'.$param['rand'];
                     }
                     $content['name'] = $row_name."[$col_name]";
                     $content['id']   = Html::cleanId('cb_'.$row_name.'_'.$col_name.'_'.
                                                      $param['rand']);
                     self::showCheckbox($content);
                     $nb_cb_per_col[$col_name]['total'] ++;
                     $nb_cb_per_row['total'] ++;
                     if ($content['checked']) {
                        $nb_cb_per_col[$col_name]['checked'] ++;
                        $nb_cb_per_row['checked'] ++;
                     }
                  } else if (is_string($content)) {
                     echo $content;
                  } else {
                     echo "&nbsp;";
                  }
               } else {
                  echo "&nbsp;";
               }

               echo "</td>\n";
            }
         }
         if (($param['row_check_all'])
            && (!is_string($row))
            && ($nb_cb_per_row['total'] > 1)) {
            $cb_options['criterion']    = ['tag_for_massive' => 'row_'.$row_name.'_'.
                                                $param['rand']];
            $cb_options['massive_tags'] = 'table_'.$param['rand'];
            $cb_options['id']           = Html::cleanId('cb_checkall_row_'.$row_name.'_'.
                                                      $param['rand']);
            $cb_options['checked']      = ($nb_cb_per_row['checked']
                                             > ($nb_cb_per_row['total'] / 2));
            echo "\t\t<td class='center'>".self::getCheckbox($cb_options)."</td>\n";
         }

         echo "\t</tr>\n";
      }

      if ($param['col_check_all']) {
         echo "\t<tr class='tab_bg_1'>\n";
         echo "\t\t<td>".__('Select/unselect all')."</td>\n";
         foreach ($columns as $col_name => $column) {
            echo "\t\t<td class='center'>";
            if ($nb_cb_per_col[$col_name]['total'] > 1) {
               $cb_options['criterion']    = ['tag_for_massive' => 'col_'.$col_name.'_'.
                                                   $param['rand']];
               $cb_options['massive_tags'] = 'table_'.$param['rand'];
               $cb_options['id']           = Html::cleanId('cb_checkall_col_'.$col_name.'_'.
                                                         $param['rand']);
               $cb_options['checked']      = ($nb_cb_per_col[$col_name]['checked']
                                                > ($nb_cb_per_col[$col_name]['total'] / 2));
               echo self::getCheckbox($cb_options);
            } else {
               echo "&nbsp;";
            }
            echo "</td>\n";
         }

         if ($param['row_check_all']) {
            $cb_options['criterion']    = ['tag_for_massive' => 'table_'.$param['rand']];
            $cb_options['massive_tags'] = '';
            $cb_options['id']           = Html::cleanId('cb_checkall_table_'.$param['rand']);
            echo "\t\t<td class='center'>".self::getCheckbox($cb_options)."</td>\n";
         }
         echo "\t</tr>\n";
      }

      echo "</table>\n";

      return $param['rand'];
   }

   static function getCheckAllAsCheckbox($container_id, $rand = '') {

      if (empty($rand)) {
         $rand = mt_rand();
      }
      $out  ="
      <label class='m-checkbox m-checkbox--state-success'  for='checkall_$rand' title='".__s('Check all as')."'>
      <input title='".__s('Check all as')."' type='checkbox' class='new_checkbox' ".
      "name='_checkall_$rand' id='checkall_$rand' ".
      "onclick= \"if ( checkAsCheckboxes('checkall_$rand', '$container_id'))
                                    {return true;}\">
         <span></span>
      </label>

      ";

      // permit to shift select checkboxes
      $out.= Html::scriptBlock("\$(function() {\$('#$container_id input[type=\"checkbox\"]').shiftSelectable();});");

      return $out;
   }

   /**
    * Get a checkbox.
   *
   * @since 0.85
   *
   * @param array $options  array of parameters:
   *    - title         its title
   *    - name          its name
   *    - id            its id
   *    - value         the value to set when checked
   *    - readonly      can we edit it ?
   *    - massive_tags  the tag to set for massive checkbox update
   *    - checked       is it checked or not ?
   *    - zero_on_empty do we send 0 on submit when it is not checked ?
   *    - specific_tags HTML5 tags to add
   *    - criterion     the criterion for massive checkbox
   *
   * @return string  the HTML code for the checkbox
   **/
   static function getCheckbox(array $options) {
      global $CFG_GLPI;

      $params                    = [];
      $params['title']           = '';
      $params['name']            = '';
      $params['rand']            = mt_rand();
      $params['id']              = "check_".$params['rand'];
      $params['value']           = 1;
      $params['readonly']        = false;
      $params['massive_tags']    = '';
      $params['checked']         = false;
      $params['zero_on_empty']   = true;
      $params['specific_tags']   = [];
      $params['criterion']       = [];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }
      // $out  ="
      // <label class='m-checkbox m-checkbox--state-success'  for='checkall_$rand' title='".__s('Check all as')."'>
      // <input title='".__s('Check all as')."' type='checkbox' class='new_checkbox' ".
      // "name='_checkall_$rand' id='checkall_$rand' ".
      //  "onclick= \"if ( checkAsCheckboxes('checkall_$rand', '$container_id'))
      //                                 {return true;}\">
      //    <span></span>
      // </label>

      // ";
      $out  ="<label class='m-checkbox m-checkbox--state-success'  title=\"".$params['title']."\" for='".$params['id']."'>";
      // $out = "<span class='form-group-checkbox'>";
      $out.= "<input type='checkbox' class='new_checkbox' ";

      foreach (['id', 'name', 'title', 'value'] as $field) {
         if (!empty($params[$field])) {
            $out .= " $field='".$params[$field]."'";
         }
      }

      $criterion = self::getCriterionForMassiveCheckboxes($params['criterion']);
      if (!empty($criterion)) {
         $out .= " onClick='massiveUpdateCheckbox(\"$criterion\", this)'";
      }

      if ($params['zero_on_empty']) {
         $out                               .= " data-glpicore-cb-zero-on-empty='1'";
         $CFG_GLPI['checkbox-zero-on-empty'] = true;

      }

      if (!empty($params['massive_tags'])) {
         $params['specific_tags']['data-glpicore-cb-massive-tags'] = $params['massive_tags'];
      }

      if (!empty($params['specific_tags'])) {
         foreach ($params['specific_tags'] as $tag => $values) {
            if (is_array($values)) {
               $values = implode(' ', $values);
            }
            $out .= " $tag='$values'";
         }
      }

      if ($params['readonly']) {
         $out .= " disabled='disabled'";
      }

      if ($params['checked']) {
         $out .= " checked";
      }

      $out .= ">";
      // $out .= "<label class='label-checkbox' title=\"".$params['title']."\" for='".$params['id']."'>";
      // $out .= " <span class='check'></span>";
      $out .= " <span class='box'";
      if (isset($params['onclick'])) {
         $params['onclick'] = htmlspecialchars($params['onclick'], ENT_QUOTES);
         $out .= " onclick='{$params['onclick']}'";
      }
      $out .= "></span>";
      $out .= "&nbsp;";
      $out .= "</label>";
      // $out .= "</span>";

      if (!empty($criterion)) {
         $out .= Html::scriptBlock("\$(function() {\$('$criterion').shiftSelectable();});");
      }

      return $out;
   }


   /**
    * @brief display a checkbox that $_POST 0 or 1 depending on if it is checked or not.
    * @see Html::getCheckbox()
    *
    * @since 0.85
    *
    * @param $options   array
    *
    * @return void
   **/
   static function showCheckbox(array $options = []) {
      echo self::getCheckbox($options);
   }


      /**
    * Get the massive action checkbox
    *
    * @since 0.84
    *
    * @param string  $itemtype  Massive action itemtype
    * @param integer $id        ID of the item
    * @param array   $options
    *
    * @return string
   **/
   static function getMassiveActionCheckBox($itemtype, $id, array $options = []) {

      $options['checked']       = (isset($_SESSION['glpimassiveactionselected'][$itemtype][$id]));
      if (!isset($options['specific_tags']['data-glpicore-ma-tags'])) {
         $options['specific_tags']['data-glpicore-ma-tags'] = 'common';
      }

      // encode quotes and brackets to prevent maformed name attribute
      $id = htmlspecialchars($id, ENT_QUOTES);
      $id = str_replace(['[', ']'], ['&amp;#91;', '&amp;#93;'], $id);
      $options['name']          = "item[$itemtype][".$id."]";

      $options['zero_on_empty'] = false;

      return self::getCheckbox($options);
   }


   /**
    * Show the massive action checkbox
    *
    * @since 0.84
    *
    * @param string  $itemtype  Massive action itemtype
    * @param integer $id        ID of the item
    * @param array   $options
    *
    * @return void
   **/
   static function showMassiveActionCheckBox($itemtype, $id, array $options = []) {
      echo self::getMassiveActionCheckBox($itemtype, $id, $options);
   }

      /**
    *  Resume text for followup
    *
    * @param string  $string  string to resume
    * @param integer $length  resume length (default 255)
    *
    * @return string
   **/
   static function resume_text($string, $length = 100) {
      $length = 50;
      if (Toolbox::strlen($string) > $length) {
         $string = Toolbox::substr($string, 0, $length)."...";
      }

      return $string;
   }
}
