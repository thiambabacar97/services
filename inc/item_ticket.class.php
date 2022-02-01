<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Item_Ticket Class
 *
 *  Relation between Tickets and Items
**/
class PluginServicesItem_Ticket extends Item_Ticket {

   static function itemAddForm(Ticket $ticket, $options = []) {
      global $CFG_GLPI;

      $params = ['id'                  => (isset($ticket->fields['id'])
                                                && $ticket->fields['id'] != '')
                                                   ? $ticket->fields['id']
                                                   : 0,
                      '_users_id_requester' => 0,
                      'items_id'            => [],
                      'itemtype'            => '',
                      '_canupdate'          => false];

      $opt = [];

      foreach ($options as $key => $val) {
         if (!empty($val)) {
            $params[$key] = $val;
         }
      }

      if (!$ticket->can($params['id'], READ)) {
         return false;
      }

      $canedit = ($ticket->can($params['id'], UPDATE)
                  && $params['_canupdate']);

      // Ticket update case
      if ($params['id'] > 0) {
         // Get requester
         $class        = new $ticket->userlinkclass();
         $tickets_user = $class->getActors($params['id']);
         if (isset($tickets_user[CommonITILActor::REQUESTER])
             && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)) {
            foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
               $params['_users_id_requester'] = $user_id_single['users_id'];
            }
         }

         // Get associated elements for ticket
         $used = self::getUsedItems($params['id']);
         $usedcount = 0;
         foreach ($used as $itemtype => $items) {
            foreach ($items as $items_id) {
               if (!isset($params['items_id'][$itemtype])
                   || !in_array($items_id, $params['items_id'][$itemtype])) {
                  $params['items_id'][$itemtype][] = $items_id;
               }
               ++$usedcount;
            }
         }
      }

      // Get ticket template
      $tt = new TicketTemplate();
      if (isset($options['_tickettemplate'])) {
         $tt                  = $options['_tickettemplate'];
         if (isset($tt->fields['id'])) {
            $opt['templates_id'] = $tt->fields['id'];
         }
      } else if (isset($options['templates_id'])) {
         $tt->getFromDBWithData($options['templates_id']);
         if (isset($tt->fields['id'])) {
            $opt['templates_id'] = $tt->fields['id'];
         }
      }

      $rand  = mt_rand();
      $count = 0;

      echo "<div class='form-group m-form__group row' id='itemAddForm$rand'>";

         // Show associated item dropdowns
         if ($canedit) {
               $p = ['used'       => $params['items_id'],
                        'rand'       => $rand,
                        'tickets_id' => $params['id']];
               // My items
               if ($params['_users_id_requester'] > 0) {
                  echo'<div class="col-lg-10">';
                     echo'<label>Mes équipements</label>';
                     self::dropdownMyDevices($params['_users_id_requester'], $ticket->fields["entities_id"], $params['itemtype'], 0, $p);
                     echo "<span id='item_ticket_selection_information$rand'></span>";
                     // Display list
                  echo "<span style='clear:both;'>";
                     if (!empty($params['items_id'])) {
                        // No delete if mandatory and only one item
                        $delete = $ticket->canAddItem(__CLASS__);
                        $cpt = 0;
                        foreach ($params['items_id'] as $itemtype => $items) {
                           $cpt += count($items);
                        }

                        if ($cpt == 1 && isset($tt->mandatory['items_id'])) {
                           $delete = false;
                        }
                        foreach ($params['items_id'] as $itemtype => $items) {
                           foreach ($items as $items_id) {
                              $count++;
                              echo self::showItemToAdd(
                                 $params['id'],
                                 $itemtype,
                                 $items_id,
                                 [
                                    'rand'      => $rand,
                                    'delete'    => $delete,
                                    'visible'   => ($count <= 5)
                                 ]
                              );
                           }
                        }
                     }

                     if ($count == 0) {
                        echo "<input type='hidden' value='0' name='items_id'>";
                     }

                     if ($params['id'] > 0 && $usedcount != $count) {
                        $count_notsaved = $count - $usedcount;
                        echo "<i>" . sprintf(_n('%1$s item not saved', '%1$s items not saved', $count_notsaved), $count_notsaved)  . "</i>";
                     }
                     if ($params['id'] > 0 && $usedcount > 5) {
                        echo "<i><a href='".$ticket->getFormURLWithID($params['id'])."&amp;forcetab=Item_Ticket$1'>"
                                 .__('Display all items')." (".$usedcount.")</a></i>";
                     }
                  echo "</span>";
                  echo'</div>';
                  echo'<div class="col-lg-2 mt-3">';
                     echo"
                        <a style='box-shadow: #3da8a9 0px 0px 1px 0px !important' href='javascript:itemAction$rand(\"add\");' class='mt-3 btn btn-secondary m-btn m-btn--icon m-btn--wide'>
                           <span>
                           <i class='fa fa-plus-square'></i>
                              <span>"._sx('button', 'Add')."</span>
                           </span>
                        </a>
                     ";
                     // echo "<a href='javascript:itemAction$rand(\"add\");' class=' mt-3 btn btn-secondary m-btn--wide vsubmit'>"._sx('button', 'Add')."</a>";
                  echo'</div>';
               }
               // Global search
               // Item_Ticket::dropdownAllDevices("itemtype", $params['itemtype'], 0, 1, $params['_users_id_requester'], $ticket->fields["entities_id"], $p);
            // Add button
         }

         

         foreach (['id', '_users_id_requester', 'items_id', 'itemtype', '_canupdate'] as $key) {
            $opt[$key] = $params[$key];
         }

         $js  = " function itemAction$rand(action, itemtype, items_id) {";
         $js .= "    $.ajax({
                        url: '".$CFG_GLPI['root_doc']."/ajax/itemTicket.php',
                        dataType: 'html',
                        data: {'action'     : action,
                              'rand'       : $rand,
                              'params'     : ".json_encode($opt).",
                              'my_items'   : $('#dropdown_my_items$rand').val(),
                              'itemtype'   : (itemtype === undefined) ? $('#dropdown_itemtype$rand').val() : itemtype,
                              'items_id'   : (items_id === undefined) ? $('#dropdown_add_items_id$rand').val() : items_id},
                        success: function(response) {";
         $js .= "          $(\"#itemAddForm$rand\").replaceWith(response);";
         $js .= "       }";
         $js .= "    });";
         $js .= " }";
         echo Html::scriptBlock($js);
      echo "</div>";
   }

   static function dropdownMyDevices($userID = 0, $entity_restrict = -1, $itemtype = 0, $items_id = 0, $options = []) {
      global $DB, $CFG_GLPI;

      $params = ['tickets_id' => 0,
                     'used'       => [],
                     'multiple'   => false,
                     'rand'       => mt_rand()];

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      if ($userID == 0) {
         $userID = Session::getLoginUserID();
      }

      $rand        = $params['rand'];
      $already_add = $params['used'];

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2, Ticket::HELPDESK_MY_HARDWARE)) {
         $my_devices = ['' => Dropdown::EMPTY_VALUE];
         $devices    = [];

         // My items
         foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
            if (($item = getItemForItemtype($itemtype))
               && Ticket::isPossibleToAssignType($itemtype)) {
               $itemtable = getTableForItemType($itemtype);

               $criteria = [
                  'FROM'   => $itemtable,
                  'WHERE'  => [
                     'users_id' => $userID
                  ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                  'ORDER'  => $item->getNameField()
               ];

               if ($item->maybeDeleted()) {
                  $criteria['WHERE']['is_deleted'] = 0;
               }
               if ($item->maybeTemplate()) {
                  $criteria['WHERE']['is_template'] = 0;
               }
               if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                  $criteria['WHERE']['is_helpdesk_visible'] = 1;
               }

               $iterator = $DB->request($criteria);
               $nb = count($iterator);
               if ($nb > 0) {
                  $type_name = $item->getTypeName($nb);

                  while ($data = $iterator->next()) {
                     if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                        $output = $data[$item->getNameField()];
                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                           $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                        }
                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                        if ($itemtype != 'Software') {
                           if (!empty($data['serial'])) {
                              $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                           }
                           if (!empty($data['otherserial'])) {
                              $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                           }
                        }
                        $devices[$itemtype."_".$data["id"]] = $output;

                        $already_add[$itemtype][] = $data["id"];
                     }
                  }
               }
            }
         }

         if (count($devices)) {
            $my_devices[__('My devices')] = $devices;
         }
         // My group items
         if (Session::haveRight("show_group_hardware", "1")) {
            $iterator = $DB->request([
               'SELECT'    => [
                  'glpi_groups_users.groups_id',
                  'glpi_groups.name'
               ],
               'FROM'      => 'glpi_groups_users',
               'LEFT JOIN' => [
                  'glpi_groups'  => [
                     'ON' => [
                        'glpi_groups_users'  => 'groups_id',
                        'glpi_groups'        => 'id'
                     ]
                  ]
               ],
               'WHERE'     => [
                  'glpi_groups_users.users_id'  => $userID
               ] + getEntitiesRestrictCriteria('glpi_groups', '', $entity_restrict, true)
            ]);

            $devices = [];
            $groups  = [];
            if (count($iterator)) {
               while ($data = $iterator->next()) {
                  $a_groups                     = getAncestorsOf("glpi_groups", $data["groups_id"]);
                  $a_groups[$data["groups_id"]] = $data["groups_id"];
                  $groups = array_merge($groups, $a_groups);
               }

               foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                  if (($item = getItemForItemtype($itemtype))
                     && Ticket::isPossibleToAssignType($itemtype)) {
                     $itemtable  = getTableForItemType($itemtype);
                     $criteria = [
                        'FROM'   => $itemtable,
                        'WHERE'  => [
                           'groups_id' => $groups
                        ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict, $item->maybeRecursive()),
                        'ORDER'  => $item->getNameField()
                     ];

                     if ($item->maybeDeleted()) {
                        $criteria['WHERE']['is_deleted'] = 0;
                     }
                     if ($item->maybeTemplate()) {
                        $criteria['WHERE']['is_template'] = 0;
                     }

                     $iterator = $DB->request($criteria);
                     if (count($iterator)) {
                        $type_name = $item->getTypeName();
                        if (!isset($already_add[$itemtype])) {
                           $already_add[$itemtype] = [];
                        }
                        while ($data = $iterator->next()) {
                           if (!in_array($data["id"], $already_add[$itemtype])) {
                              $output = '';
                              if (isset($data["name"])) {
                                 $output = $data["name"];
                              }
                              if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                 $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                              }
                              $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                              if (isset($data['serial'])) {
                                 $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                              }
                              if (isset($data['otherserial'])) {
                                 $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                              }
                              $devices[$itemtype."_".$data["id"]] = $output;

                              $already_add[$itemtype][] = $data["id"];
                           }
                        }
                     }
                  }
               }
               if (count($devices)) {
                  $my_devices[__('Devices own by my groups')] = $devices;
               }
            }
         }
         // Get software linked to all owned items
         if (in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
            $software_helpdesk_types = array_intersect($CFG_GLPI['software_types'], $_SESSION["glpiactiveprofile"]["helpdesk_item_type"]);
            foreach ($software_helpdesk_types as $itemtype) {
               if (isset($already_add[$itemtype]) && count($already_add[$itemtype])) {
                  $iterator = $DB->request([
                     'SELECT'          => [
                        'glpi_softwareversions.name AS version',
                        'glpi_softwares.name AS name',
                        'glpi_softwares.id'
                     ],
                     'DISTINCT'        => true,
                     'FROM'            => 'glpi_items_softwareversions',
                     'LEFT JOIN'       => [
                        'glpi_softwareversions'  => [
                           'ON' => [
                              'glpi_items_softwareversions' => 'softwareversions_id',
                              'glpi_softwareversions'       => 'id'
                           ]
                        ],
                        'glpi_softwares'        => [
                           'ON' => [
                              'glpi_softwareversions' => 'softwares_id',
                              'glpi_softwares'        => 'id'
                           ]
                        ]
                     ],
                     'WHERE'        => [
                           'glpi_items_softwareversions.items_id' => $already_add[$itemtype],
                           'glpi_items_softwareversions.itemtype' => $itemtype,
                           'glpi_softwares.is_helpdesk_visible'   => 1
                        ] + getEntitiesRestrictCriteria('glpi_softwares', '', $entity_restrict),
                     'ORDERBY'      => 'glpi_softwares.name'
                  ]);

                  $devices = [];
                  if (count($iterator)) {
                     $item       = new Software();
                     $type_name  = $item->getTypeName();
                     if (!isset($already_add['Software'])) {
                        $already_add['Software'] = [];
                     }
                     while ($data = $iterator->next()) {
                        if (!in_array($data["id"], $already_add['Software'])) {
                           $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                           $output = sprintf(__('%1$s (%2$s)'), $output,
                              sprintf(__('%1$s: %2$s'), __('version'),
                                 $data["version"]));
                           if ($_SESSION["glpiis_ids_visible"]) {
                              $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                           }
                           $devices["Software_".$data["id"]] = $output;

                           $already_add['Software'][] = $data["id"];
                        }
                     }
                     if (count($devices)) {
                        $my_devices[__('Installed software')] = $devices;
                     }
                  }
               }
            }
         }
         // Get linked items to computers
         if (isset($already_add['Computer']) && count($already_add['Computer'])) {
            $devices = [];

            // Direct Connection
            $types = ['Monitor', 'Peripheral', 'Phone', 'Printer'];
            foreach ($types as $itemtype) {
               if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                  && ($item = getItemForItemtype($itemtype))) {
                  $itemtable = getTableForItemType($itemtype);
                  if (!isset($already_add[$itemtype])) {
                     $already_add[$itemtype] = [];
                  }
                  $criteria = [
                     'SELECT'          => "$itemtable.*",
                     'DISTINCT'        => true,
                     'FROM'            => 'glpi_computers_items',
                     'LEFT JOIN'       => [
                        $itemtable  => [
                           'ON' => [
                              'glpi_computers_items'  => 'items_id',
                              $itemtable              => 'id'
                           ]
                        ]
                     ],
                     'WHERE'           => [
                        'glpi_computers_items.itemtype'     => $itemtype,
                        'glpi_computers_items.computers_id' => $already_add['Computer']
                     ] + getEntitiesRestrictCriteria($itemtable, '', $entity_restrict),
                     'ORDERBY'         => "$itemtable.name"
                  ];

                  if ($item->maybeDeleted()) {
                     $criteria['WHERE']["$itemtable.is_deleted"] = 0;
                  }
                  if ($item->maybeTemplate()) {
                     $criteria['WHERE']["$itemtable.is_template"] = 0;
                  }

                  $iterator = $DB->request($criteria);
                  if (count($iterator)) {
                     $type_name = $item->getTypeName();
                     while ($data = $iterator->next()) {
                        if (!in_array($data["id"], $already_add[$itemtype])) {
                           $output = $data["name"];
                           if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                              $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                           }
                           $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                           if ($itemtype != 'Software') {
                              $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                           }
                           $devices[$itemtype."_".$data["id"]] = $output;

                           $already_add[$itemtype][] = $data["id"];
                        }
                     }
                  }
               }
            }
            if (count($devices)) {
               $my_devices[__('Connected devices')] = $devices;
            }
         }
         echo "<div id='tracking_my_devices'>";
         // echo __('My devices')."&nbsp;";
         Dropdown::showFromArray('my_items', $my_devices, ['rand' => $rand]);
         echo "</div>";

         // Auto update summary of active or just solved tickets
         $params = ['my_items' => '__VALUE__'];

         Ajax::updateItemOnSelectEvent("dropdown_my_items$rand", "item_ticket_selection_information$rand",
                                       $CFG_GLPI["root_doc"]."/ajax/ticketiteminformation.php",
                                       $params);
      }
   }

   static function showItemToAdd($tickets_id, $itemtype, $items_id, $options) {
      $params = [
         'rand'      => mt_rand(),
         'delete'    => true,
         'visible'   => true
      ];

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $result = "";

      if ($item = getItemForItemtype($itemtype)) {
         if ($params['visible']) {
            $item->getFromDB($items_id);
            $result =  "<div id='{$itemtype}_$items_id'>";
            $result .= $item->getTypeName(1)." : ".$item->getLink(['comments' => true]);
            $result .= Html::hidden("items_id[$itemtype][$items_id]", ['value' => $items_id]);
            if ($params['delete']) {
               $result .= " <span class='fa fa-times-circle pointer' onclick=\"itemAction".$params['rand']."('delete', '$itemtype', '$items_id');\"></span>";
            }
            $result .= "</div>";
         } else {
            $result .= Html::hidden("items_id[$itemtype][$items_id]", ['value' => $items_id]);
         }
      }

      return $result;
   }

   static function showForTicket(Ticket $ticket) {
      
      $instID = $ticket->fields['id'];

      if (!$ticket->can($instID, READ)) {
         return false;
      }

      $canedit = $ticket->canAddItem($instID);
      $rand    = mt_rand();

      $types_iterator = self::getDistinctTypes($instID);
      $number = count($types_iterator);

      if ($canedit
         && !in_array($ticket->fields['status'], array_merge($ticket->getClosedStatusArray(),
                                                            $ticket->getSolvedStatusArray()))) {
         echo "<div class='firstbloc'>";
            echo "<form name='ticketitem_form$rand' class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashe' id='ticketitem_form$rand' method='post'
                     action='".self::getFormURL()."'>";
            
                     echo'  
                        <div class="form-group m-form__group row" id="mainform">
                           <div class="col-lg-6">';
                              $class        = new $ticket->userlinkclass();
                              $tickets_user = $class->getActors($instID);
                              $dev_user_id = 0;
                              if (isset($tickets_user[CommonITILActor::REQUESTER])
                                    && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)) {
                                 foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
                                    $dev_user_id = $user_id_single['users_id'];
                                 }
                              }
                           
                              if ($dev_user_id > 0) {
                                 self::dropdownMyDevices($dev_user_id, $ticket->fields["entities_id"], null, 0, ['tickets_id' => $instID]);
                              }                                                    
               
                              echo'
                           </div>
                           <div class="col-lg-3">';
                              echo "<span id='item_ticket_selection_information$rand'></span>";
                              echo'<button type="submit"  name="add" class="btn btn-primary mb-2" style="min-height: 39px">Ajouter</button>';
                              echo "<input type='hidden' name='tickets_id' value='$instID'>";
                              echo'
                           </div>
                        </div>
                     ';
            Html::closeForm();
            echo Html::css('assets/css/customer.glpi.form.css');
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['container' => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $number) {
         $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th>"._n('Type', 'Types', 1)."</th>";
      $header_end .= "<th>".Entity::getTypeName(1)."</th>";
      $header_end .= "<th>".__('Name')."</th>";
      $header_end .= "<th>".__('Serial number')."</th>";
      $header_end .= "<th>".__('Inventory number')."</th>";
      echo "<tr>";
      echo $header_begin.$header_top.$header_end;

      $totalnb = 0;
      while ($row = $types_iterator->next()) {
         $itemtype = $row['itemtype'];
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
            $iterator = self::getTypeItems($instID, $itemtype);
            $nb = count($iterator);

            $prem = true;
            while ($data = $iterator->next()) {
               $name = $data["name"];
               if ($_SESSION["glpiis_ids_visible"]
                   || empty($data["name"])) {
                  $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
               }
               if ((Session::getCurrentInterface() != 'helpdesk') && $item::canView()) {
                  $link     = $itemtype::getFormURLWithID($data['id']);
                  $namelink = "<a href=\"".$link."\">".$name."</a>";
               } else {
                  $namelink = $name;
               }

               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
                  echo "</td>";
               }
               if ($prem) {
                  $typename = $item->getTypeName($nb);
                  echo "<td class='center top' rowspan='$nb'>".
                         (($nb > 1) ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename)."</td>";
                  $prem = false;
               }
               echo "<td class='center'>";
               echo Dropdown::getDropdownName("glpi_entities", $data['entity'])."</td>";
               echo "<td class='center".
                        (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
               echo ">".$namelink."</td>";
               echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-").
                    "</td>";
               echo "<td class='center'>".
                      (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
               echo "</tr>";
            }
            $totalnb += $nb;
         }
      }

      if ($number) {
         echo $header_begin.$header_bottom.$header_end;
      }

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
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

   static function getSearchURL($full = true) {
      return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
   }
}


