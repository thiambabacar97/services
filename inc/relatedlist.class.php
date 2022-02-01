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

class PluginServicesRelatedlist extends CommonDBTM {

    static $rightname = 'plugin_services_relatedlist';

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
    
    /**
    * Have I the global right to "create" the Object
    * May be overloaded if needed (ex KnowbaseItem)
    *
    * @return boolean
   **/
    static function canCreate() {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, CREATE);
        }
        return false;
    }


    /**
    * Have I the global right to "view" the Object
    *
    * Default is true and check entity if the objet is entity assign
    *
    * May be overloaded if needed
    *
    * @return boolean
    **/
    static function canView() {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, READ);
        }
        return false;
    }


    /**
    * Have I the global right to "update" the Object
    *
    * Default is calling canCreate
    * May be overloaded if needed
    *
    * @return boolean
    **/
    static function canUpdate() {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, UPDATE);
        }
    }


    /**
     * Have I the global right to "delete" the Object
    *
    * May be overloaded if needed
    *
    * @return boolean
    **/
    static function canDelete() {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, DELETE);
        }
        return false;
    }


    /**
  * Have I the global right to "purge" the Object
  *
  * May be overloaded if needed
  *
  * @return boolean
  **/
    static function canPurge() {
        if (static::$rightname) {
        return Session::haveRight(static::$rightname, PURGE);
        }
        return false;
    }

    static function getSearchOptionByFields($field, $value, $table = '', $linkedTable) {
        $fields = [];
        $itm = getItemTypeForTable($linkedTable);
        $customitem = 'PluginServices'.$itm;
        $itm = (class_exists($customitem)) ? $customitem :  $itm ;
        $item = new $itm();
        // print_r($item->searchOptions());
        foreach ($item->searchOptions() as $id => $searchOption) {
            if ((isset($searchOption['linkfield']) && ($searchOption['linkfield'] == $value))
                || (isset($searchOption[$field]) && ($searchOption[$field] == $value))) {
                if (($table == '')
                    || (($table != '') && ($searchOption['table'] == $table))) {
                    // Set ID;
                    $searchOption['id'] = $id;
                    array_push($fields,   $searchOption);
                }
            }
        }
        return $fields;
    }

    public function getItemLink($itemtype){
        $RELATION = getDbRelations();
        if (!class_exists($itemtype)) {
            PluginServicesHtml::displayNotFoundError();
        }
        $itemtable =  $itemtype::getTable($itemtype);
        $fields = [];
        if (isset($RELATION[$itemtable])) {
            foreach ($RELATION[$itemtable] as $tablename => $field) {
                $tablename = (str_starts_with($tablename, '_')) ? substr($tablename, 1) :$tablename ;
                $itemlink = getItemTypeForTable($tablename);
                // print_r($itemlink);
                if (getItemForItemtype($itemlink) instanceof CommonDBRelation) {
                    $link = explode('_', $itemlink);
                    foreach ($link as  $value) {
                        if ($value === getItemTypeForTable($itemtable)) {
                            continue;
                        }
                        if (class_exists($value)) {
                            $itemtype =  $value;
                        }
                    }
                    $customItem = 'PluginServices'.$itemtype;
                    if (class_exists($customItem)) { // verifions s'il exist un item custom pour cet item est si oui on l'utilise
                        $itemtype = $customItem;
                    }

                    $item = new $itemtype();
                    $colum = $item->rawSearchOptions();

                    if (!empty($colum)) {
                        foreach ($colum as $key => $fieldname) {
                            if (isset($fieldname['joinparams'])) {
                                if (isset($fieldname['joinparams']['beforejoin'])) {
                                    $array = $fieldname['joinparams']['beforejoin'];                             
                                    if ( (isset($array['table']) && $array['table'] == getTableForItemType($itemlink))) {
                                        if ((getItemForItemtype($itemtype) instanceof CommonITILObject)) { // on ommet les requesters et watchers
                                            if ($fieldname['name'] === _n('Requester group', 'Requester groups', 1)
                                            || $fieldname['name'] === _n('Watcher group', 'Watcher groups', 1)) {
                                                continue;
                                            }
                                        }
                                        $currentitem =  getItemForItemtype(getItemTypeForTable($itemtable));
                                        $optionname = $fieldname['name'].'->'.$item->getTypeName();
                                        $optionvalue = $itemlink.'**'.$itemtype.'**'.$fieldname['id'].'**'.$fieldname['name'];
                                        $fields[$optionvalue] = $optionname;
                                    }
                                    
                                }
                            }
                        }
                    }
                }else {
                    $itemtype = getItemTypeForTable($tablename);
                    $customitem = 'PluginServices'.$itemtype;
                    $itemtype = (class_exists($customitem)) ? $customitem :  $itemtype ;
                    $colums = static::getSearchOptionByFields('datatype', 'dropdown',  $itemtable, $tablename);
                    $item = new $itemtype();
                    if (!empty($colums)) {
                        foreach ($colums as $key => $fieldname) {
                            if (($item instanceof CommonITILObject)) { // on ommet les requesters et watchers
                                if ($fieldname['name'] === _n('Requester group', 'Requester groups', 1)
                                || $fieldname['name'] === _n('Watcher group', 'Watcher groups', 1)) {
                                    continue;
                                }
                            }
                            if ( ($item instanceof CommonITILObject) && ($fieldname['id']!== 22)
                            && ($fieldname['name'] === __('Writer'))) {
                                continue;
                            }

                            $currentitem =  getItemForItemtype(getItemTypeForTable($itemtable));
                            $optionname = $fieldname['name'].'->'.$item->getTypeName();

                            $optionvalue = $itemtype.'**'.$fieldname['id'].'**'.$fieldname['name'];
                            $fields[$optionvalue] =  $optionname;
                        }
                    }
                }
            }
        }
        return $fields;
    }

    public function showList($itp, $params){
        if (isset($_GET['type'])) {
            $itemtype = $_GET['type'];
            $item = new $itemtype();
        }else {
            PluginServicesHtml::displayErrorAndDie('Lost');
        }
        echo'
            <div class="m-content">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="m-portlet m-portlet--tab">';
                        echo'<div class="m-portlet__head">';
                            echo'<div class="m-portlet__head-caption">';
                                echo'<div class="m-portlet__head-title">';
                                    echo'<h3 class="m-portlet__head-text">Configuration des listes associées sur le formulaire '.$item->getTypeName(1).'</h3>';
                                echo'</div>';
                            echo'</div>';
                        echo'</div>';
                        echo'<div class="m-portlet__body">';
                                $this->showFormGlobal('/front/relatedlist.form.php', $itemtype);
                                echo'  
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }

    static function tabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $tab = new self();
        $tabs = $tab->find(['itemtype' =>$item->getType()], ['rank']);
        $arraytabs = [];
        foreach ($tabs as $value) {
            $itemlink = new $value['itemlink']();
            array_push($arraytabs, self::createTabEntry(__($itemlink->getTypeName())));
        }
        
        return $arraytabs;
    }

    static function tabcontent(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $tab = new self();
        $tabs = $tab->find(['itemtype' =>$item->getType()], ['rank ASC']);
        $arraytabs = [];
        foreach ($tabs as $value) {
            array_push($arraytabs, $value);
        }
        
        foreach ($arraytabs as $key => $value) {
            
            if ($tabnum == $key) {
                $criteria = [
                    "pivotitem"=> $value['pivotitem'],
                    "is_deleted"=> 0,
                    "as_map"=> 0,
                    "criteria"=> [
                        [
                            "link" => "AND",
                            "field" => $value['columId'],
                            "searchtype" =>"equals",
                            "value" => $item->fields['id'],
                            "search" => "Rechercher",
                            "itemtype" => $value['itemlink'],
                            "start" => 0,
                        ],
                    ]
                ];

                $classname = 'PluginServices'.$value['itemlink'];
                $class = (class_exists($classname)) ? $classname : $value['itemlink'] ;
                PluginServicesSearch::showRelatedList($class,  $criteria);
            }
        }
        return true;
    }

    /**
    * Print the search config form
    *
    * @param string $target    form target
    * @param string $itemtype  item type
    *
    * @return void|boolean (display) Returns false if there is a rights error.
   **/
    function showFormGlobal($target, $itemtype) {
        global $CFG_GLPI, $DB;
        $searchopt = $this->getItemLink($itemtype);
        // print_r($searchopt);
        $IDuser = Session::getLoginUserID();
        echo'<div class="row d-flex justify-content-center">';
                echo'<div class="col-lg-4">';
                    echo'<h5 class="font-weight-bold">Disponible</h5>';
                    echo'<input type="text" class="form-control m-input m-input--air m-input--pill mb-3" id="inputString" placeholder="search">';
                    // Defined items
                    $iterator = $DB->request([
                        'FROM'   => $this->getTable(),
                        'WHERE'  => [
                            'itemtype'  => $itemtype,
                            'users_id'  => $IDuser
                        ],
                        'ORDER'  => 'rank'
                    ]);
                    $numrows = count($iterator);
                    echo'<ul id="modules"  class="mt-4 connectedSortable form-control m-input m-input--air m-input--pill connectedSortable">';
                        foreach ($searchopt as $key => $value) {
                            $link = explode('**', $key) ; 
                            $post = [];
                            if (count($link) && count($link) === 3) {
                                $post['itemlink'] = (isset($link[0])) ? $link[0] : null;
                                $post['columId'] = (isset($link[1])) ? $link[1] : null;
                            }else {
                                $post['itemlink'] = (isset($link[1])) ? $link[1] : null;
                                $post['columId'] = (isset($link[2])) ? $link[2] : null;
                            }
                            $ifexist = $this->find(['itemlink' => $post['itemlink'],'columId' => $post['columId']]);
                            if ($ifexist) {
                                continue;
                            }

                            echo '<li class="drop-item">';
                                echo $value ;
                                echo "<input type='hidden' name='itemlinkAndColumid[]' value='".$key."'>";
                            echo '</li>';
                        }
                    echo'</ul>';
                echo'</div>';
                echo'<div class="col-lg-4 mt-3">';
                    echo'<h5 class="font-weight-bold">sélectionner</h5>';
                    echo"<form  method='post' action='$target'>";
                        echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                        echo "<input type='hidden' name='users_id' value='$IDuser'>";
                        echo'<ul id="modules1"  class="mt-5 connectedSortable form-control m-input m-input--air m-input--pill connectedSortable">';
                            while ($value = $iterator->next()) {
                                if (isset($value['pivotitem'])) {
                                    $optionvalue = $value['pivotitem'].'**'.$value['itemlink'].'**'.$value['columId'].'**'.$value['columName'];
                                }
                                $optionvalue = $value['itemlink'].'**'.$value['columId'].'**'.$value['columName'];

                                echo '<li class="drop-item">';
                                    $item = new $value['itemlink']();
                                    $optionname = $value['columName'].'->'.$item->getTypeName();
                                    echo  $optionname;
                                    echo "<input type='hidden' name='itemlinkAndColumid[]' value='".$optionvalue."'>";
                                echo '</li>';
                            }
                        echo'</ul>';
                        echo'<div class="d-flex justify-content-end">';
                            echo'<button  type="submit" class=" text-right btn m-btn--radius btn-md  btn-info mt-2" value="Soumettre" name="add">Soumettre</button>';
                        echo'</div>';
                    Html::closeForm();
                echo'</div>';
        echo "</div>";

        $JS = <<<JAVASCRIPT
            $( function() {
                $("li").click(function() {
                $(this).toggleClass("selected");
                });
                $("#modules, #modules1" ).sortable({
                    connectWith: ".connectedSortable",
                    dropOnEmpty: true,
                    scroll: true,
                    start: function(e, info) {
                    info.item.siblings(".selected").appendTo(info.item);
                    },
                    stop: function(e, info) {
                    info.item.after(info.item.find("li"))
                    }
                }).disableSelection();
            });

            jQuery("#inputString").keyup(function () {
                var filter = jQuery(this).val();
                jQuery("#modules li, #modules1 li").each(function () {
                if (jQuery(this).text().search(new RegExp(filter, "i")) < 0) {
                    jQuery(this).hide();
                } else {
                    jQuery(this).show()
                }
                });
            });
        JAVASCRIPT;
        echo Html::scriptBlock($JS);
    }

    function orderItem(array $input, $action) {
        global $DB;
    
        // Get current item
        $result = $DB->request([
            'SELECT' => 'rank',
            'FROM'   => $this->getTable(),
            'WHERE'  => ['id' => $input['id']]
        ])->next();
        $rank1  = $result['rank'];
    
        // Get previous or next item
        $where = [];
        $order = 'rank ';
        switch ($action) {
            case "up" :
                $where['rank'] = ['<', $rank1];
                $order .= 'DESC';
                break;
    
            case "down" :
                $where['rank'] = ['>', $rank1];
                $order .= 'ASC';
                break;
    
            default :
                return false;
        }
    
        $result = $DB->request([
            'SELECT' => ['id', 'rank'],
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $input['itemtype'],
                'users_id'  => $input["users_id"]
            ] + $where,
            'ORDER'  => $order,
            'LIMIT'  => 1
        ])->next();
        
        $rank2  = $result['rank'];
        $ID2    = $result['id'];
    
        // Update items
        $DB->update(
            $this->getTable(),
            ['rank' => $rank2],
            ['id' => $input['id']]
        );
    
        $DB->update(
            $this->getTable(),
            ['rank' => $rank1],
            ['id' => $ID2]
        );
        return $result; 
    }
}