<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
  die("Sorry. You can't access this file directly");
}

/**
 * OLA Class
 * @since 9.2
**/
use Glpi\Event;
class PluginServicesOLA extends OLA {
  static $rightname = 'plugin_services_ola';
  static function getType() {
    return "OLA";
  }

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
    return "glpi_olas";
  }

  public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
    if ($item->getType() == "SLA") {
        if (Session::getCurrentInterface() != "helpdesk" ) {
          return [
              self::createTabEntry(__("Niveaux d'escalade")),
          ];
        }
    }
    return '';
  }

  static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
    if ($item->getType() == "SLA") {
        switch ($tabnum) {
          case 0:
              $slalevel = new PluginAssistancesSlalevel();
              $slalevel->showForParent($item);
          break;
        }
    }

    return true;
  }

  function postForm($post) {
    Session::checkRight("slm", READ);

    if (empty($_GET["id"])) {
        $_GET["id"] = "";
    }

    $sla = new OLA();

    if (isset($_POST["add"])) {
        $backurl = PluginServicesToolbox::getFormURLWithID($_GET['parentid'], true, 'SLM');
        $sla->check(-1, CREATE, $_POST);

        if ($newID = $sla->add($_POST)) {
          Event::log($newID, "slas", 4, "setup",
                    sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
        }
        Html::redirect($backurl);
    } else if (isset($_POST["purge"])) {
        $backurl = PluginServicesToolbox::getFormURLWithID($post['slms_id'], true, 'SLM');
        $sla->check($_POST["id"], PURGE);
        $sla->delete($_POST, 1);

        Event::log($_POST["id"], "slas", 4, "setup",
                //TRANS: %s is the user login
                sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
        Html::redirect($backurl);

    } else if (isset($_POST["update"])) {
        $backurl = PluginServicesToolbox::getFormURLWithID($post['slms_id'], true, 'SLM');
        $sla->check($_POST["id"], UPDATE);
        $sla->update($_POST);

        Event::log($_POST["id"], "slas", 4, "setup",
                //TRANS: %s is the user login
                sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
        Html::redirect($backurl);
    }
  }

  public function showTabsContent($options = []) {

              
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
      $extraparamPluginServicesHtml = "";
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
            // $extraparamPluginServicesHtml can be tool long in case of ticket with content
            // (passed in GET in ajax request)
            unset($cleaned_options['content']);
        }
        // prevent double sanitize, because the includes.php sanitize all data
        $cleaned_options = Toolbox::stripslashes_deep($cleaned_options);

        $extraparamPluginServicesHtml = "&amp;".Toolbox::append_params($cleaned_options, '&amp;');
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
                                          "&amp;_glpi_tab=$key&amp;id=$ID$extraparamPluginServicesHtml"];
        }

        // Not all tab for templates and if only 1 tab
        if ($display_all
            && empty($withtemplate)
            && (count($tabs) > 1)) {
            $tabs[-1] = ['title'  => __('All'),
                              'url'    => $tabpage,
                              'params' => "_target=$target&amp;_itemtype=".$this->getType().
                                          "&amp;_glpi_tab=-1&amp;id=$ID$extraparamPluginServicesHtml"];
        }

        PluginServicesAjax::createTabs('tabspanel', 'tabcontent', $tabs, $this->getType(), $ID,
                        "horizontal", $options);
      }
      echo "</div>";
  }

  public function defineAllTabsFago($options = []) {
    global $CFG_GLPI;

    $onglets = [];
    // Object with class with 'addtabon' attribute
    if (isset(self::$othertabs[$this->getType()])
        && !$this->isNewItem()) {

        foreach (self::$othertabs[$this->getType()] as $typetab) {
          $this->addStandardTab($typetab, $onglets, $options);
        }
    }

    $class = $this->getType();
    return $onglets;
  }

  function showFormHeader($options = []) {

    $ID     = $this->fields['id'];

    $params = [
        'target'       => $this->getFormURL(),
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
        && $this->isEntityAssign()) {
        $this->fields['entities_id']  = $_SESSION['glpiactive_entity'];
    }

    $rand = mt_rand();
    if ($this->canEdit($ID)) {
        echo "<form name='form' method='post' action='' ".
              $params['formoptions']." enctype=\"multipart/form-data\">";

        //Should add an hidden entities_id field ?
        //If the table has an entities_id field
        if ($this->isField("entities_id")) {
          //The object type can be assigned to an entity
          if ($this->isEntityAssign()) {
              if (isset($params['entities_id'])) {
                $entity = $this->fields['entities_id'] = $params['entities_id'];
              } else if (isset($this->fields['entities_id'])) {
                //It's an existing object to be displayed
                $entity = $this->fields['entities_id'];
              } else if ($this->isNewID($ID)
                        || ($params['withtemplate'] == 2)) {
                //It's a new object to be added
                $entity = $_SESSION['glpiactive_entity'];
              }

              echo "<input type='hidden' name='entities_id' value='$entity'>";

          } else if ($this->getType() != 'User') {
              // For Rules except ruleticket and slalevel
              echo "<input type='hidden' name='entities_id' value='0'>";

          }
        }
    }
  }

  function showFormButtons($options = []) {

    // for single object like config
    if (isset($this->fields['id'])) {
        $ID = $this->fields['id'];
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

    Plugin::doHook("post_item_form", ['item' => $this, 'options' => &$params]);

    if ($params['formfooter'] === null) {
        // $this->showDates($params);
    }

    if (!$params['canedit']
          || !$this->canEdit($ID)) {
        echo "</table></div>";
        // Form Header always open form
        Html::closeForm();
        return false;
    }

    echo "<tr class='tab_bg_2'>";

    if ($params['withtemplate']
          ||$this->isNewID($ID)) {

        echo "<td class='center' colspan='".($params['colspan']*2)."'>";

        if (($ID <= 0) || ($params['withtemplate'] == 2)) {
          echo PluginServicesHtml::submit(
                _x('button', 'Soumettre'),
              ['name' => 'add']
          );
        } else {
          //TRANS : means update / actualize
          echo PluginServicesHtml::submit(
                _x('button', 'Soumettre'),
              ['name' => 'update']
          );
        }

    } else {
        if ($params['candel']
          && !$this->can($ID, DELETE)
          && !$this->can($ID, PURGE)) {
          $params['candel'] = false;
        }

        if ($params['canedit'] && $this->can($ID, UPDATE)) {
          echo "<td class='center' colspan='".($params['colspan']*2)."'>\n";
          echo PluginServicesHtml::submit(
                _x('button', 'Soumettre'),
              ['name' => 'update']
          );
        }

        if ($params['candel']) {
          if ($params['canedit'] && $this->can($ID, UPDATE)) {
              echo "</td></tr><tr class='tab_bg_2'>\n";
          }
          if ($this->isDeleted()) {
              if ($this->can($ID, DELETE)) {
                echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
                echo PluginServicesHtml::submit(
                    _x('button', 'Restore'),
                    ['name' => 'restore']
                );
              }

              if ($this->can($ID, PURGE)) {
                echo "<span class='very_small_space'>";
                if (in_array($this->getType(), Item_Devices::getConcernedItems())) {
                    PluginServicesHtml::showToolTip(__('Check to keep the devices while deleting this item'));
                    echo "&nbsp;";
                    echo "<input type='checkbox' name='keep_devices' value='1'";
                    if (!empty($_SESSION['glpikeep_devices_when_purging_item'])) {
                      echo " checked";
                    }
                    echo ">&nbsp;";
                }
                echo PluginServicesHtml::submit(
                    _x('button', 'Delete permanently'),
                    ['name' => 'purge']
                );
                echo "</span>";
              }

          } else {
              echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
              // If maybe dynamic : do not take into account  is_deleted  field
              if (!$this->maybeDeleted()
                || $this->useDeletedToLockIfDynamic()) {
                if ($this->can($ID, PURGE)) {
                    echo PluginServicesHtml::submit(
                      _x('button', 'Delete permanently'),
                      [
                          'name'    => 'purge',
                          'confirm' => __('Confirm the final deletion?')
                      ]
                    );
                }
              } else if (!$this->isDeleted()
                          && $this->can($ID, DELETE)) {
                echo PluginServicesHtml::submit(
                    _x('button', 'Put in trashbin'),
                    ['name' => 'delete']
                );
              }
          }

        }
        if ($this->isField('date_mod')) {
          echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
        }
    }

    if (!$this->isNewID($ID)) {
        echo "<input type='hidden' name='id' value='$ID'>";
    }
    echo "</td>";
    echo "</tr>\n";

    if ($params['canedit']
          && count($params['addbuttons'])) {
        echo "<tr class='tab_bg_2'>";
        echo "<td class='right' colspan='".($params['colspan']*2)."'>";
        foreach ($params['addbuttons'] as $key => $val) {
          echo "<button type='submit' class='vsubmit' name='$key' value='1'>
                $val
              </button>&nbsp;";
        }
        echo "</td>";
        echo "</tr>";
    }

    // Close for Form
    echo "</table></div>";
    Html::closeForm();
  }

  function showForm($ID, $options = []) {
    $rowspan = 3;
    if ($ID > 0) {
        $rowspan = 5;
    }

    // Get SLM object
    $slm = new SLM();
    if (isset($options['parent'])) {
        $slm = $options['parent'];
    } else {
        $slm->getFromDB((isset($_GET['parentid'])) ? $_GET['parentid'] : $_GET['id']);
    }

    if ($ID > 0) {
        $this->check($ID, READ);
    } else {
        // Create item
        $options[static::$items_id] = $slm->getField('id');

        //force itemtype of parent
        static::$itemtype = get_class($slm);

        $this->check(-1, CREATE, $options);
    }

    $this->showFormHeader($options);

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
                    echo'</div>';
                echo'</div>';

                echo'<div class="m-portlet__body">';
                    echo'<div style="padding-top: 10px;" class="row">';
                      echo'<div  style="display: table;" class="col-sm-6 mb-3">';
                          echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                            echo __('Name');
                          echo'</label>';
                          echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                            PluginServicesHtml::autocompletionTextField($this, "name", ['value' => $this->fields["name"]]);
                          echo'</div>';
                          echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                          echo'</div>';
                      echo'</div>';

                      echo'<div  style="display: table;" class="col-sm-6 mb-3">';
                          echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                            echo __('SLM');
                          echo'</label>';
                          echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                            PluginServicesDropdown::show('SLM', ['value'  => $this->fields['slms_id'], 'name' => 'slms_id', 'disabled' => true]);
                            echo "<input type='hidden' name='slms_id' value='".$this->fields['slms_id']."'>";
                          echo'</div>';
                          echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                          echo'</div>';
                      echo'</div>';

                      echo'<div  style="display: table;" class="col-sm-6 mb-3">';
                          echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                            echo _n('Type', 'Types', 1);
                          echo'</label>';
                          echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                            self::getTypeDropdown(['value' => $this->fields["type"]]);
                          echo'</div>';
                          echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                          echo'</div>';
                      echo'</div>';

                      echo'<div  style="display: table;" class="col-sm-6 mb-3">';
                          echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                            echo __('Maximum time');
                          echo'</label>';
                          echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                            pluginServicesDropdown::showNumber("number_time", ['value' => $this->fields["number_time"], 'min'   => 0, 'width' => '40%']);
                            echo'&nbsp;';

                            $possible_values = ['minute' => _n('Minute', 'Minutes', Session::getPluralNumber()),
                            'hour'   => _n('Hour', 'Hours', Session::getPluralNumber()),
                            'day'    => _n('Day', 'Days', Session::getPluralNumber())];
                            $rand = pluginServicesDropdown::showFromArray('definition_time', $possible_values, [
                                                                          'value' => $this->fields["definition_time"],
                                                                          'width' => '50%',
                                                                          'on_change' => 'appearhideendofworking()']);
                            
                            echo "\n<script type='text/javascript'>\n";
                                echo "function appearhideendofworking() {\n";
                                      echo "if ($('#dropdown_definition_time$rand option:selected').val() === 'day') {
                                              $('#dropdown_endworkingday').css('display', 'table');
                                            } else {
                                              $('#dropdown_endworkingday').css('display', 'none');
                                            }";
                                echo "}\n";
                                echo "appearhideendofworking();\n";
                            echo "</script>\n";
                          echo'</div>';
                          echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                          echo'</div>';
                      echo'</div>';

                      echo'<div  style="display: none;" class="col-sm-6 mb-3" id="dropdown_endworkingday">';
                          echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0; padding-top: 7px;"  class="col-xs-12 col-md-4 col-lg-4 control-label">'; 
                            echo __('End of working day');
                          echo'</label>';
                          echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;"  class="col-xs-10 col-sm-9 col-md-5 col-lg-5">';
                            pluginServicesDropdown::showYesNo("end_of_working_day", $this->fields["end_of_working_day"]);
                          echo'</div>';
                          echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                          echo'</div>';
                      echo'</div>';
                
                      echo'<div  style="display: table;" class="col-sm-12 mb-3" >';
                          echo'<label style="float: left; text-align: right; padding-left: 0px; margin-bottom: 0px; padding-top: 7px;" class="col-xs-12 col-md-1_5 col-lg-2 control-label">'; 
                            echo __('Comments');
                          echo'</label>';
                          echo'<div  style="float: left; padding-right: 0px;  padding-left: 0px;" class="col-xs-10 col-md-8 col-lg-8">';
                            echo'<textarea cols="100" rows="6" name="comment" class="form-control" >'.$this->fields["comment"].'</textarea>';
                          echo'</div>';
                          echo'<div style="float: left;  padding-left: 4px; " class="col-xs-2 col-sm-2 col-lg-2">';
                          echo'</div>';
                      echo'</div>';
                    echo'</div>';
                echo'</div>';
              echo'<div class="m-portlet__foot m-portlet__no-border m-portlet__foot--fit">
                    <div class="m-form__actions d-flex justify-content-end">';
                    $this->showFormButtons($options);                    
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