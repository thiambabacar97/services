<?php 

  static function giveItem_old($itemtype, $ID, array $data, $meta = 0,
                            array $addobjectparams = [], $orig_itemtype = null) {
      global $CFG_GLPI;

      $searchopt = &self::getOptions($itemtype);
      if ($itemtype == 'AllAssets' || isset($CFG_GLPI["union_search_type"][$itemtype])
          && ($CFG_GLPI["union_search_type"][$itemtype] == $searchopt[$ID]["table"])) {

         $oparams = [];
         if (isset($searchopt[$ID]['addobjectparams'])
             && $searchopt[$ID]['addobjectparams']) {
            $oparams = $searchopt[$ID]['addobjectparams'];
         }

         // Search option may not exists in subtype
         // This is the case for "Inventory number" for a Software listed from ReservationItem search
         $subtype_so = &self::getOptions($data["TYPE"]);
         if (!array_key_exists($ID, $subtype_so)) {
            return '';
         }

         return self::giveItem($data["TYPE"], $ID, $data, $meta, $oparams, $itemtype);
      }
      $so = $searchopt[$ID];
      $orig_id = $ID;
      $ID = ($orig_itemtype !== null ? $orig_itemtype : $itemtype) . '_' . $ID;

      if (count($addobjectparams)) {
         $so = array_merge($so, $addobjectparams);
      }
      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $out = Plugin::doOneHook(
            $plug['plugin'],
            'giveItem',
            $itemtype, $orig_id, $data, $ID
         );
         if (!empty($out)) {
            return $out;
         }
      }

      if (isset($so["table"])) {
         $table     = $so["table"];
         $field     = $so["field"];
         $linkfield = $so["linkfield"];

         /// TODO try to clean all specific cases using SpecificToDisplay

         switch ($table.'.'.$field) {
            case "glpi_users.name" :
               if ($itemtype == 'PluginAssistancesTicket'
                  && Session::getCurrentInterface() == 'helpdesk'
                  && $orig_id == 5
                  && Entity::getUsedConfig(
                     'anonymize_support_agents',
                     $itemtype::getById($data['id'])->getEntityId()
                  )
               ) {
                  return __("Helpdesk");
               }

               // USER search case
               if (($itemtype != 'PluginServicesUser')
                     && isset($so["forcegroupby"]) && $so["forcegroupby"]) {
                  $out           = "";
                  $count_display = 0;
                  $added         = [];

                  $showuserlink = 0;
                  if (Session::haveRight('user', READ)) {
                     $showuserlink = 1;
                  }

                  for ($k=0; $k<$data[$ID]['count']; $k++) {

                     if ((isset($data[$ID][$k]['name']) && ($data[$ID][$k]['name'] > 0))
                           || (isset($data[$ID][$k][2]) && ($data[$ID][$k][2] != ''))) {
                        if ($count_display) {
                           $out .= self::LBBR;
                        }

                        if ($itemtype == 'PluginAssistancesTicket') {
                           if (isset($data[$ID][$k]['name'])
                                 && $data[$ID][$k]['name'] > 0) {
                              $userdata = getUserName($data[$ID][$k]['name'], 2);
                              $tooltip  = "";
                              $out = sprintf(__('%1$s %2$s'), $userdata['name'], $tooltip);
                              if (Session::haveRight('user', READ)) {
                                 $link = PluginServicesUser::getFormURLWithID($data[$ID][$k]['name']);
                                 $out  .= "<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'><span class='fas fa-info pointer'></span><a/>";
                              }
                              $count_display++;
                           }
                        } else {
                           // $out .= getUserName($data[$ID][$k]['name'], $showuserlink);
                           $link = PluginServicesUser::getFormURLWithID($data[$ID][$k]['name']);
                           $out  = "<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'>".getUserName($data[$ID][$k]['name'])."<a/>";
                           $count_display++;
                        }

                        // Manage alternative_email for tickets_users
                        if (($itemtype == 'PluginAssistancesTicket')
                              && isset($data[$ID][$k][2])) {

                           $split = explode(self::LONGSEP, $data[$ID][$k][2]);
                           for ($l=0; $l<count($split); $l++) {
                              $split2 = explode(" ", $split[$l]);
                              if ((count($split2) == 2) && ($split2[0] == 0) && !empty($split2[1])) {
                                 if ($count_display) {
                                    $out .= self::LBBR;
                                 }
                                 $count_display++;
                                 $out .= "<a href='mailto:".$split2[1]."'>".$split2[1]."</a>";
                              }
                           }
                        }
                     }
                  }
                  return $out;
               }
               if ($itemtype != 'PluginServicesUser') {
                  $toadd = '';
                  if (($itemtype == 'PluginAssistancesTicket')
                        && ($data[$ID][0]['id'] > 0)) {
                     $userdata = getUserName($data[$ID][0]['id'], 2);
                     // $toadd    = Html::showToolTip($userdata["comment"],
                     //                               ['link'    => $userdata["link"],
                     //                                     'display' => false]);
                  }
                  
                  $usernameformat = formatUserName($data[$ID][0]['id'], $data[$ID][0]['name'],
                                                   $data[$ID][0]['realname'],
                                                   $data[$ID][0]['firstname']);
                  // return sprintf(__('%1$s %2$s'), $usernameformat, $toadd);
                  $link = PluginServicesUser::getFormURLWithID($data[$ID][0]['id']);
                  $out  = $usernameformat;
                  $out  .= "&nbsp;<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'><span class='fas fa-info pointer'></span><a/>";
                  return  $out;
               }
               break;

            case "glpi_profiles.name" :
               if (($itemtype == 'PluginServicesUser')
                     && ($orig_id == 20)) {
                  $out           = "";

                  $count_display = 0;
                  $added         = [];
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0
                           && !in_array($data[$ID][$k]['name']."-".$data[$ID][$k]['entities_id'],
                                       $added)) {
                        $text = sprintf(__('%1$s - %2$s'), $data[$ID][$k]['name'],
                                          Dropdown::getDropdownName('glpi_entities',
                                                                  $data[$ID][$k]['entities_id']));
                        $comp = '';
                        if ($data[$ID][$k]['is_recursive']) {
                           $comp = __('R');
                           if ($data[$ID][$k]['is_dynamic']) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                           }
                        }
                        if ($data[$ID][$k]['is_dynamic']) {
                           $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                        }
                        if (!empty($comp)) {
                           $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                        }
                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        $out     .= $text;
                        $added[]  = $data[$ID][$k]['name']."-".$data[$ID][$k]['entities_id'];
                     }
                  }
                  return $out;
               }
               break;

            case "glpi_entities.completename" :
               if ($itemtype == 'PluginServicesUser') {

                  $out           = "";
                  $added         = [];
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (isset($data[$ID][$k]['name'])
                         && (strlen(trim($data[$ID][$k]['name'])) > 0)
                         && !in_array($data[$ID][$k]['name']."-".$data[$ID][$k]['profiles_id'],
                                      $added)) {
                        $text = sprintf(__('%1$s - %2$s'), $data[$ID][$k]['name'],
                                        Dropdown::getDropdownName('glpi_profiles',
                                                                  $data[$ID][$k]['profiles_id']));
                        $comp = '';
                        if ($data[$ID][$k]['is_recursive']) {
                           $comp = __('R');
                           if ($data[$ID][$k]['is_dynamic']) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                           }
                        }
                        if ($data[$ID][$k]['is_dynamic']) {
                           $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                        }
                        if (!empty($comp)) {
                           $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                        }
                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        $out    .= $text;
                        $added[] = $data[$ID][$k]['name']."-".$data[$ID][$k]['profiles_id'];
                     }
                  }
                  return $out;
               }
               break;

            case "glpi_documenttypes.icon" :
               if (!empty($data[$ID][0]['name'])) {
                  return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".
                           $data[$ID][0]['name']."'>";
               }
               return "&nbsp;";

            case "glpi_documents.filename" :
               $doc = new Document();
               if ($doc->getFromDB($data['id'])) {
                  return $doc->getDownloadLink();
               }
               return NOT_AVAILABLE;

            case "glpi_tickets_tickets.tickets_id_1" :
               $out        = "";
               $displayed  = [];
               for ($k=0; $k<$data[$ID]['count']; $k++) {

                  $linkid = ($data[$ID][$k]['tickets_id_2'] == $data['id'])
                                 ? $data[$ID][$k]['name']
                                 : $data[$ID][$k]['tickets_id_2'];
                  if (($linkid > 0) && !isset($displayed[$linkid])) {
                     $link = PluginAssistancesTicket::getFormURLWithID($linkid);
                     // $text  = "<a ";
                     //$text .= "href=\"".Ticket::getFormURLWithID($linkid)."\">";
                     $text  = "<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'>";
                     $text .= Dropdown::getDropdownName('glpi_tickets', $linkid)."</a>";
                     if (count($displayed)) {
                        $out .= self::LBBR;
                     }
                     $displayed[$linkid] = $linkid;
                     $out               .= $text;
                  }
               }
               return $out;

            case "glpi_problems.id" :
               if ($so["datatype"] == 'count') {
                  if (($data[$ID][0]['name'] > 0)
                        && Session::haveRight("problem", Problem::READALL)) {
                     if ($itemtype == 'ITILCategory') {
                        $options['criteria'][0]['field']      = 7;
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = $data['id'];
                        $options['criteria'][0]['link']       = 'AND';
                     } else {
                        $options['criteria'][0]['field']       = 12;
                        $options['criteria'][0]['searchtype']  = 'equals';
                        $options['criteria'][0]['value']       = 'all';
                        $options['criteria'][0]['link']        = 'AND';

                        $options['metacriteria'][0]['itemtype']   = $itemtype;
                        $options['metacriteria'][0]['field']      = self::getOptionNumber($itemtype,
                              'name');
                        $options['metacriteria'][0]['searchtype'] = 'equals';
                        $options['metacriteria'][0]['value']      = $data['id'];
                        $options['metacriteria'][0]['link']       = 'AND';
                     }

                     $options['reset'] = 'reset';

                     $out  = "<a id='problem$itemtype".$data['id']."' ";
                     $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                              Toolbox::append_params($options, '&amp;')."\">";
                     $out .= $data[$ID][0]['name']."</a>";
                     return $out;
                  }
               }
               break;

            case "glpi_tickets.id" :
               if ($so["datatype"] == 'count') {
                  if (($data[$ID][0]['name'] > 0)
                        && Session::haveRight("ticket", Ticket::READALL)) {

                     if ($itemtype == 'PluginServicesUser') {
                        // Requester
                        if ($ID == 'User_60') {
                           $options['criteria'][0]['field']      = 4;
                           $options['criteria'][0]['searchtype']= 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';
                        }

                        // Writer
                        if ($ID == 'User_61') {
                           $options['criteria'][0]['field']      = 22;
                           $options['criteria'][0]['searchtype']= 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';
                        }
                        // Assign
                        if ($ID == 'User_64') {
                           $options['criteria'][0]['field']      = 5;
                           $options['criteria'][0]['searchtype']= 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';
                        }
                     } else if ($itemtype == 'ITILCategory') {
                        $options['criteria'][0]['field']      = 7;
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = $data['id'];
                        $options['criteria'][0]['link']       = 'AND';

                     } else {
                        $options['criteria'][0]['field']       = 12;
                        $options['criteria'][0]['searchtype']  = 'equals';
                        $options['criteria'][0]['value']       = 'all';
                        $options['criteria'][0]['link']        = 'AND';

                        $options['metacriteria'][0]['itemtype']   = $itemtype;
                        $options['metacriteria'][0]['field']      = self::getOptionNumber($itemtype,
                                                                                          'name');
                        $options['metacriteria'][0]['searchtype'] = 'equals';
                        $options['metacriteria'][0]['value']      = $data['id'];
                        $options['metacriteria'][0]['link']       = 'AND';
                     }

                     $options['reset'] = 'reset';

                     $out  = "<a id='ticket$itemtype".$data['id']."' ";
                     $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                              Toolbox::append_params($options, '&amp;')."\">";
                     $out .= $data[$ID][0]['name']."</a>";
                     return $out;
                  }
               }
               break;

            case "glpi_tickets.time_to_resolve" :
            case "glpi_problems.time_to_resolve" :
            case "glpi_changes.time_to_resolve" :
            case "glpi_tickets.time_to_own" :
            case "glpi_tickets.internal_time_to_own" :
            case "glpi_tickets.internal_time_to_resolve" :
               // Due date + progress
               if (in_array($orig_id, [151, 158, 181, 186])) {
                  $out = Html::convDateTime($data[$ID][0]['name']);

                  // No due date in waiting status
                  if ($data[$ID][0]['status'] == CommonITILObject::WAITING) {
                     return '';
                  }
                  if (empty($data[$ID][0]['name'])) {
                     return '';
                  }
                  if (($data[$ID][0]['status'] == Ticket::SOLVED)
                     || ($data[$ID][0]['status'] == Ticket::CLOSED)) {
                     return $out;
                  }

                  $itemtype = getItemTypeForTable($table);
                  $item = new $itemtype();
                  $item->getFromDB($data['id']);
                  $percentage  = 0;
                  $totaltime   = 0;
                  $currenttime = 0;
                  $slaField    = 'slas_id';

                  // define correct sla field
                  switch ($table.'.'.$field) {
                     case "glpi_tickets.time_to_resolve" :
                        $slaField = 'slas_id_ttr';
                        break;
                     case "glpi_tickets.time_to_own" :
                        $slaField = 'slas_id_tto';
                        break;
                     case "glpi_tickets.internal_time_to_own" :
                        $slaField = 'olas_id_tto';
                        break;
                     case "glpi_tickets.internal_time_to_resolve" :
                        $slaField = 'olas_id_ttr';
                        break;
                  }

                  switch ($table.'.'.$field) {
                     // If ticket has been taken into account : no progression display
                     case "glpi_tickets.time_to_own" :
                     case "glpi_tickets.internal_time_to_own" :
                        if (($item->fields['takeintoaccount_delay_stat'] > 0)) {
                           return $out;
                        }
                        break;
                  }

                  if ($item->isField($slaField) && $item->fields[$slaField] != 0) { // Have SLA
                     $sla = new SLA();
                     $sla->getFromDB($item->fields[$slaField]);
                     $currenttime = $sla->getActiveTimeBetween($item->fields['date'],
                                                               date('Y-m-d H:i:s'));
                     $totaltime   = $sla->getActiveTimeBetween($item->fields['date'],
                                                               $data[$ID][0]['name']);
                  } else {
                     $calendars_id = Entity::getUsedConfig('calendars_id',
                                                           $item->fields['entities_id']);
                     if ($calendars_id != 0) { // Ticket entity have calendar
                        $calendar = new Calendar();
                        $calendar->getFromDB($calendars_id);
                        $currenttime = $calendar->getActiveTimeBetween($item->fields['date'],
                                                                       date('Y-m-d H:i:s'));
                        $totaltime   = $calendar->getActiveTimeBetween($item->fields['date'],
                                                                       $data[$ID][0]['name']);
                     } else { // No calendar
                        $currenttime = strtotime(date('Y-m-d H:i:s'))
                                                 - strtotime($item->fields['date']);
                        $totaltime   = strtotime($data[$ID][0]['name'])
                                                 - strtotime($item->fields['date']);
                     }
                  }
                  if ($totaltime != 0) {
                     $percentage  = round((100 * $currenttime) / $totaltime);
                  } else {
                     // Total time is null : no active time
                     $percentage = 100;
                  }
                  if ($percentage > 100) {
                     $percentage = 100;
                  }
                  $percentage_text = $percentage;

                  if ($_SESSION['glpiduedatewarning_unit'] == '%') {
                     $less_warn_limit = $_SESSION['glpiduedatewarning_less'];
                     $less_warn       = (100 - $percentage);
                  } else if ($_SESSION['glpiduedatewarning_unit'] == 'hour') {
                     $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * HOUR_TIMESTAMP;
                     $less_warn       = ($totaltime - $currenttime);
                  } else if ($_SESSION['glpiduedatewarning_unit'] == 'day') {
                     $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * DAY_TIMESTAMP;
                     $less_warn       = ($totaltime - $currenttime);
                  }

                  if ($_SESSION['glpiduedatecritical_unit'] == '%') {
                     $less_crit_limit = $_SESSION['glpiduedatecritical_less'];
                     $less_crit       = (100 - $percentage);
                  } else if ($_SESSION['glpiduedatecritical_unit'] == 'hour') {
                     $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * HOUR_TIMESTAMP;
                     $less_crit       = ($totaltime - $currenttime);
                  } else if ($_SESSION['glpiduedatecritical_unit'] == 'day') {
                     $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * DAY_TIMESTAMP;
                     $less_crit       = ($totaltime - $currenttime);
                  }

                  $color = $_SESSION['glpiduedateok_color'];
                  if ($less_crit < $less_crit_limit) {
                     $color = $_SESSION['glpiduedatecritical_color'];
                  } else if ($less_warn < $less_warn_limit) {
                     $color = $_SESSION['glpiduedatewarning_color'];
                  }

                  if (!isset($so['datatype'])) {
                     $so['datatype'] = 'progressbar';
                  }

                  $progressbar_data = [
                     'text'         => Html::convDateTime($data[$ID][0]['name']),
                     'percent'      => $percentage,
                     'percent_text' => $percentage_text,
                     'color'        => $color
                  ];
               }
               break;

            case "glpi_softwarelicenses.number" :
               if ($data[$ID][0]['min'] == -1) {
                  return __('Unlimited');
               }
               if (empty($data[$ID][0]['name'])) {
                  return 0;
               }
               return $data[$ID][0]['name'];

            case "glpi_auth_tables.name" :
               return Auth::getMethodName($data[$ID][0]['name'], $data[$ID][0]['auths_id'], 1,
                                          $data[$ID][0]['ldapname'].$data[$ID][0]['mailname']);

            case "glpi_reservationitems.comment" :
               if (empty($data[$ID][0]['name'])) {
                  $text = __('None');
               } else {
                  $text = Html::resume_text($data[$ID][0]['name']);
               }
               if (Session::haveRight('reservation', UPDATE)) {
                  return "<a title=\"".__s('Modify the comment')."\"
                           href='".ReservationItem::getFormURLWithID($data['refID'])."' >".$text."</a>";
               }
               return $text;

            case 'glpi_crontasks.description' :
               $tmp = new CronTask();
               return $tmp->getDescription($data[$ID][0]['name']);

            case 'glpi_changes.status':
               $status = Change::getStatus($data[$ID][0]['name']);
               return "<span class='no-wrap'>".
                      Change::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                      "</span>";

            case 'glpi_problems.status':
               $status = Problem::getStatus($data[$ID][0]['name']);
               return "<span class='no-wrap'>".
                      Problem::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                      "</span>";

            case 'glpi_tickets.status':
               $status = Ticket::getStatus($data[$ID][0]['name']);
               return "<span class='no-wrap'>".
                        Ticket::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                        "</span>";

            case 'glpi_projectstates.name':
               $out = '';
               $name = $data[$ID][0]['name'];
               if (isset($data[$ID][0]['trans'])) {
                  $name = $data[$ID][0]['trans'];
               }
               if ($itemtype == 'ProjectState') {
                  $out =   "<a href='".ProjectState::getFormURLWithID($data[$ID][0]["id"])."'>". $name."</a></div>";
               } else {
                  $out = $name;
               }
               return $out;

            case 'glpi_items_tickets.items_id' :
            case 'glpi_items_problems.items_id' :
            case 'glpi_changes_items.items_id' :
            case 'glpi_certificates_items.items_id' :
            case 'glpi_appliances_items.items_id' :
               if (!empty($data[$ID])) {
                  $items = [];
                  foreach ($data[$ID] as $key => $val) {
                     if (is_numeric($key)) {
                        if (!empty($val['itemtype'])
                                && ($item = getItemForItemtype($val['itemtype']))) {
                           if ($item->getFromDB($val['name'])) {
                              $items[] = $item->getLink(['comments' => true]);
                           }
                        }
                     }
                  }
                  if (!empty($items)) {
                     return implode("<br>", $items);
                  }
               }
               return '&nbsp;';

            case 'glpi_items_tickets.itemtype' :
            case 'glpi_items_problems.itemtype' :
               if (!empty($data[$ID])) {
                  $itemtypes = [];
                  foreach ($data[$ID] as $key => $val) {
                     if (is_numeric($key)) {
                        if (!empty($val['name'])
                              && ($item = getItemForItemtype($val['name']))) {
                           $item = new $val['name']();
                           $name = $item->getTypeName();
                           $itemtypes[] = __($name);
                        }
                     }
                  }
                  if (!empty($itemtypes)) {
                     return implode("<br>", $itemtypes);
                  }
               }

               return '&nbsp;';

            case 'glpi_tickets.name' :
            case 'glpi_problems.name' :
            case 'glpi_changes.name' :

               if (isset($data[$ID][0]['content'])
                  && isset($data[$ID][0]['id'])
                  && isset($data[$ID][0]['status'])) {
                  $link = $itemtype::getFormURLWithID($data[$ID][0]['id']);

                  // Force solution tab if solved
                  if ($item = getItemForItemtype($itemtype)) {
                     if (in_array($data[$ID][0]['status'], $item->getSolvedStatusArray())) {
                        $link = $link."&amp;forcetab=$itemtype$2";
                     }
                  }

                  $out = "<a onclick='loadPage(\"$link\")' id='$itemtype".$data[$ID][0]['id']."' href='javascript:void(0);'>";
                     $name = $data[$ID][0]['name'];
                     if ($_SESSION["glpiis_ids_visible"]
                           || empty($data[$ID][0]['name'])) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][0]['id']);
                     }
                  $out.= $name."</a>";
                  $hdecode = Html::entity_decode_deep($data[$ID][0]['content']);
                  $content = Toolbox::unclean_cross_side_scripting_deep($hdecode);
                  return $out;
               }

            case 'glpi_ticketvalidations.status' :
               $out   = '';
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if ($data[$ID][$k]['name']) {
                     $status  = TicketValidation::getStatus($data[$ID][$k]['name']);
                     $bgcolor = TicketValidation::getStatusColor($data[$ID][$k]['name']);
                     $out    .= (empty($out)?'':self::LBBR).
                                 "<div style=\"background-color:".$bgcolor.";\">".$status.'</div>';
                  }
               }
               return $out;

            case 'glpi_ticketsatisfactions.satisfaction' :
               if (self::$output_type == self::HTML_OUTPUT) {
                  return TicketSatisfaction::displaySatisfaction($data[$ID][0]['name']);
               }
               break;

            case 'glpi_projects._virtual_planned_duration' :
               return Html::timestampToString(ProjectTask::getTotalPlannedDurationForProject($data["id"]),
                                              false);

            case 'glpi_projects._virtual_effective_duration' :
               return Html::timestampToString(ProjectTask::getTotalEffectiveDurationForProject($data["id"]),
                                                false);

            case 'glpi_cartridgeitems._virtual' :
               return Cartridge::getCount($data["id"], $data[$ID][0]['alarm_threshold'],
                                          self::$output_type != self::HTML_OUTPUT);

            case 'glpi_printers._virtual' :
               return Cartridge::getCountForPrinter($data["id"],
                                                      self::$output_type != self::HTML_OUTPUT);

            case 'glpi_consumableitems._virtual' :
               return Consumable::getCount($data["id"], $data[$ID][0]['alarm_threshold'],
                                             self::$output_type != self::HTML_OUTPUT);

            case 'glpi_links._virtual' :
               $out = '';
               $link = new Link();
               if (($item = getItemForItemtype($itemtype))
                     && $item->getFromDB($data['id'])
               ) {
                  $data = Link::getLinksDataForItem($item);
                  $count_display = 0;
                  foreach ($data as $val) {
                     $links = Link::getAllLinksFor($item, $val);
                     foreach ($links as $link) {
                        if ($count_display) {
                           $out .=  self::LBBR;
                        }
                        $out .= $link;
                        $count_display++;
                     }
                  }
               }
               return $out;

            case 'glpi_reservationitems._virtual' :
               if ($data[$ID][0]['is_active']) {
                  return "<a href='reservation.php?reservationitems_id=".
                                          $data["refID"]."' title=\"".__s('See planning')."\">".
                                          "<i class='far fa-calendar-alt'></i><span class='sr-only'>".__('See planning')."</span></a>";
               } else {
                  return "&nbsp;";
               }

            case "glpi_tickets.priority" :
            case "glpi_problems.priority" :
            case "glpi_changes.priority" :
            case "glpi_projects.priority" :
               $index = $data[$ID][0]['name'];
               $color = $_SESSION["glpipriority_$index"];
               $name  = CommonITILObject::getPriorityName($index);
               return "<div class='priority_block' style='border-color: $color'>
                        <span style='background: $color'></span>&nbsp;$name
                        </div>";
         }
      }

      //// Default case

      if ($itemtype == 'PluginAssistancesTicket'
         && Session::getCurrentInterface() == 'helpdesk'
         && $orig_id == 8
         && Entity::getUsedConfig(
            'anonymize_support_agents',
            $itemtype::getById($data['id'])->getEntityId()
         )
      ) {
         // Assigned groups
         return __("Helpdesk group");
      }

      // Link with plugin tables : need to know left join structure
      if (isset($table)) {
         if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table.'.'.$field, $matches)) {
            if (count($matches) == 2) {
               $plug     = $matches[1];
               $out = Plugin::doOneHook(
                  $plug,
                  'giveItem',
                  $itemtype, $orig_id, $data, $ID
               );
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
      $unit = '';
      if (isset($so['unit'])) {
         $unit = $so['unit'];
      }

      // Preformat items
      if (isset($so["datatype"])) {
         switch ($so["datatype"]) {
            case "itemlink" :
               $linkitemtype  = getItemTypeForTable($so["table"]);
               $out           = "";
               $count_display = 0;
               $separate      = self::LBBR;
               if (isset($so['splititems']) && $so['splititems']) {
                  $separate = self::LBHR;
               }

               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (isset($data[$ID][$k]['id'])) {
                     if ($count_display) {
                        $out .= $separate;
                     }
                     $count_display++;
                     $page  = $linkitemtype::getFormURLWithID($data[$ID][$k]['id']);
                     $name  = Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                     if ($_SESSION["glpiis_ids_visible"] || empty($data[$ID][$k]['name'])) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][$k]['id']);
                     }
                     // $out  .= "<a id='".$linkitemtype."_".$data['id']."_".
                     //            $data[$ID][$k]['id']."' href='$page'>".
                     //           $name."</a>";

                     $out  .= "<a id='".$linkitemtype."_".$data['id']."_".
                        $data[$ID][$k]['id']."' onclick='loadPage(\"$page\")' href='javascript:void(0);'>".
                     $name."</a>";
                  }
               }
               
               return $out;

            case "text" :
               $separate = self::LBBR;
               if (isset($so['splititems']) && $so['splititems']) {
                  $separate = self::LBHR;
               }

               $out           = '';
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                     if ($count_display) {
                        $out .= $separate;
                     }
                     $count_display++;
                     $text = "";
                     if (isset($so['htmltext']) && $so['htmltext']) {
                        $text = Html::clean(Toolbox::unclean_cross_side_scripting_deep(nl2br($data[$ID][$k]['name'])));
                     } else {
                        $text = nl2br($data[$ID][$k]['name']);
                     }

                     if (self::$output_type == self::HTML_OUTPUT
                           && (Toolbox::strlen($text) > $CFG_GLPI['cut'])) {
                        $rand = mt_rand();
                        $popup_params = [
                           'display'   => false
                        ];
                        if (Toolbox::strlen($text) > $CFG_GLPI['cut']) {
                           $popup_params += [
                              'awesome-class'   => 'fa-comments',
                              'autoclose'       => false,
                              'onclick'         => true
                           ];
                        } else {
                           $popup_params += [
                              'applyto'   => "text$rand",
                           ];
                        }
                        $out .= Html::resume_text($text, $CFG_GLPI['cut']);
                        // $out .= sprintf(
                        //    __('%1$s %2$s'),
                        //    "<span id='text$rand'>". Html::resume_text($text, $CFG_GLPI['cut']).'</span>',
                        //    Html::showToolTip(
                        //       '<div class="fup-popup">'.$text.'</div>', $popup_params
                        //       )
                        // );
                     } else {
                        $out .= $text;
                     }
                  }
               }
               return $out;

            case "date" :
            case "date_delay" :
               $out   = '';
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (is_null($data[$ID][$k]['name'])
                        && isset($so['emptylabel']) && $so['emptylabel']) {
                     $out .= (empty($out)?'':self::LBBR).$so['emptylabel'];
                  } else {
                     $out .= (empty($out)?'':self::LBBR).Html::convDate($data[$ID][$k]['name']);
                  }
               }
               return $out;

            case "datetime" :
               $out   = '';
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (is_null($data[$ID][$k]['name'])
                      && isset($so['emptylabel']) && $so['emptylabel']) {
                     $out .= (empty($out)?'':self::LBBR).$so['emptylabel'];
                  } else {
                     $out .= (empty($out)?'':self::LBBR).Html::convDateTime($data[$ID][$k]['name']);
                  }
               }
               return $out;

            case "timestamp" :
               $withseconds = false;
               if (isset($so['withseconds'])) {
                  $withseconds = $so['withseconds'];
               }
               $withdays = true;
               if (isset($so['withdays'])) {
                  $withdays = $so['withdays'];
               }

               $out   = '';
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                   $out .= (empty($out)?'':'<br>').Html::timestampToString($data[$ID][$k]['name'],
                                                                           $withseconds,
                                                                           $withdays);
               }
               return $out;

            case "email" :
               $out           = '';
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if ($count_display) {
                     $out .= self::LBBR;
                  }
                  $count_display++;
                  if (!empty($data[$ID][$k]['name'])) {
                     $out .= (empty($out)?'':self::LBBR);
                     $out .= "<a href='mailto:".Html::entities_deep($data[$ID][$k]['name'])."'>".$data[$ID][$k]['name'];
                     $out .= "</a>";
                  }
               }
               return (empty($out) ? "&nbsp;" : $out);

            case "weblink" :
               $orig_link = trim($data[$ID][0]['name']);
               if (!empty($orig_link) && Toolbox::isValidWebUrl($orig_link)) {
                  // strip begin of link
                  $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/', '', $orig_link);
                  $link = preg_replace('/\/$/', '', $link);
                  if (Toolbox::strlen($link)>$CFG_GLPI["url_maxlength"]) {
                     $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"])."...";
                  }
                  return "<a href=\"".Toolbox::formatOutputWebLink($orig_link)."\" target='_blank'>$link</a>";
               }
               return "&nbsp;";

            case "count" :
            case "number" :
               $out           = "";
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                     if ($count_display) {
                        $out .= self::LBBR;
                     }
                     $count_display++;
                     if (isset($so['toadd'])
                           && isset($so['toadd'][$data[$ID][$k]['name']])) {
                        $out .= $so['toadd'][$data[$ID][$k]['name']];
                     } else {
                        $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                     }
                  }
               }
               return $out;

            case "decimal" :
               $out           = "";
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (strlen(trim($data[$ID][$k]['name'])) > 0) {

                     if ($count_display) {
                        $out .= self::LBBR;
                     }
                     $count_display++;
                     if (isset($so['toadd'])
                           && isset($so['toadd'][$data[$ID][$k]['name']])) {
                        $out .= $so['toadd'][$data[$ID][$k]['name']];
                     } else {
                        $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit, $CFG_GLPI["decimal_number"]);
                     }
                  }
               }
               return $out;

            case "bool" :
               $out           = "";
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                     if ($count_display) {
                        $out .= self::LBBR;
                     }
                     $count_display++;
                     $out .= Dropdown::getValueWithUnit(Dropdown::getYesNo($data[$ID][$k]['name']),
                                                        $unit);
                  }
               }
               return $out;

            case "itemtypename":
               if ($obj = getItemForItemtype($data[$ID][0]['name'])) {
                  return $obj->getTypeName();
               }
               return "";

            case "language":
               if (isset($CFG_GLPI['languages'][$data[$ID][0]['name']])) {
                  return $CFG_GLPI['languages'][$data[$ID][0]['name']][0];
               }
               return __('Default value');
            case 'progressbar':
               if (!isset($progressbar_data)) {
                  $bar_color = 'green';
                  $progressbar_data = [
                     'percent'      => $data[$ID][0]['name'],
                     'percent_text' => $data[$ID][0]['name'],
                     'color'        => $bar_color,
                     'text'         => ''
                  ];
               }

               $out = "{$progressbar_data['text']}<div class='center' style='background-color: #ffffff; width: 100%;
                        border: 1px solid #9BA563; position: relative;' >";
               $out .= "<div style='position:absolute;'>&nbsp;{$progressbar_data['percent_text']}%</div>";
               $out .= "<div class='center' style='background-color: {$progressbar_data['color']};
                        width: {$progressbar_data['percent']}%; height: 12px' ></div>";
               $out .= "</div>";

               return $out;
               break;
         }
      }
      // Manage items with need group by / group_concat
      $out           = "";
      $count_display = 0;
      $separate      = self::LBBR;
      if (isset($so['splititems']) && $so['splititems']) {
         $separate = self::LBHR;
      }
      for ($k=0; $k<$data[$ID]['count']; $k++) {
         if (strlen(trim($data[$ID][$k]['name'])) > 0) {
            if ($count_display) {
               $out .= $separate;
            }
            $count_display++;
            // Get specific display if available
            if (isset($table)) {
               $itemtype = getItemTypeForTable($table);
               if ($item = getItemForItemtype($itemtype)) {
                  $tmpdata  = $data[$ID][$k];
                  // Copy name to real field
                  $tmpdata[$field] = $data[$ID][$k]['name'];

                  $specific = $item->getSpecificValueToDisplay(
                     $field,
                     $tmpdata, [
                        'html'      => true,
                        'searchopt' => $so,
                        'raw_data'  => $data
                     ]
                  );
               }
            }
            if (!empty($specific)) {
               $out .= $specific;
            } else {
               if (isset($so['toadd'])
                   && isset($so['toadd'][$data[$ID][$k]['name']])) {
                  $out .= $so['toadd'][$data[$ID][$k]['name']];
               } else {
                  // Empty is 0 or empty
                  if (empty($split[0])&& isset($so['emptylabel'])) {
                     $out .= $so['emptylabel'];
                  } else {
                     // Trans field exists
                     if (isset($data[$ID][$k]['trans']) && !empty($data[$ID][$k]['trans'])) {
                        $out .=  Dropdown::getValueWithUnit($data[$ID][$k]['trans'], $unit);
                     } else {
                        $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                     }
                  }
               }
            }
         }
      }
      return $out;
   }

   static function giveItem($itemtype, $ID, array $data, $meta = 0,
                              array $addobjectparams = [], $orig_itemtype = null) {
         global $CFG_GLPI;

         $searchopt = &self::getOptions($itemtype);
         if ($itemtype == 'AllAssets' || isset($CFG_GLPI["union_search_type"][$itemtype])
            && ($CFG_GLPI["union_search_type"][$itemtype] == $searchopt[$ID]["table"])) {

            $oparams = [];
            if (isset($searchopt[$ID]['addobjectparams'])
               && $searchopt[$ID]['addobjectparams']) {
               $oparams = $searchopt[$ID]['addobjectparams'];
            }

            // Search option may not exists in subtype
            // This is the case for "Inventory number" for a Software listed from ReservationItem search
            $subtype_so = &self::getOptions($data["TYPE"]);
            if (!array_key_exists($ID, $subtype_so)) {
               return '';
            }

            return self::giveItem($data["TYPE"], $ID, $data, $meta, $oparams, $itemtype);
         }
         $so = $searchopt[$ID];
         $orig_id = $ID;
         $ID = ($orig_itemtype !== null ? $orig_itemtype : $itemtype) . '_' . $ID;

         if (count($addobjectparams)) {
            $so = array_merge($so, $addobjectparams);
         }
         // Plugin can override core definition for its type
         if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
               $plug['plugin'],
               'giveItem',
               $itemtype, $orig_id, $data, $ID
            );
            if (!empty($out)) {
               return $out;
            }
         }

         if (isset($so["table"])) {
            $table     = $so["table"];
            $field     = $so["field"];
            $linkfield = $so["linkfield"];

            /// TODO try to clean all specific cases using SpecificToDisplay

            switch ($table.'.'.$field) {
               case "glpi_users.name" :
                  if ($itemtype == 'PluginAssistancesTicket'
                     && Session::getCurrentInterface() == 'helpdesk'
                     && $orig_id == 5
                     && Entity::getUsedConfig(
                        'anonymize_support_agents',
                        $itemtype::getById($data['id'])->getEntityId()
                     )
                  ) {
                     return __("Helpdesk");
                  }

                  // USER search case
                  if (($itemtype != 'User')
                        && isset($so["forcegroupby"]) && $so["forcegroupby"]) {
                     $out           = "";
                     $count_display = 0;
                     $added         = [];

                     $showuserlink = 0;
                     if (Session::haveRight('user', READ)) {
                        $showuserlink = 1;
                     }

                     for ($k=0; $k<$data[$ID]['count']; $k++) {

                        if ((isset($data[$ID][$k]['name']) && ($data[$ID][$k]['name'] > 0))
                              || (isset($data[$ID][$k][2]) && ($data[$ID][$k][2] != ''))) {
                           if ($count_display) {
                              $out .= self::LBBR;
                           }

                           if ($itemtype == 'PluginAssistancesTicket') {
                              if (isset($data[$ID][$k]['name'])
                                    && $data[$ID][$k]['name'] > 0) {
                                 $userdata = getUserName($data[$ID][$k]['name'], 2);
                                 $out .= $userdata['name'];
                                 if (Session::haveRight('user', READ)) {
                                    $link = PluginServicesUser::getFormURLWithID($data[$ID][$k]['name']);
                                    $out  .= "&nbsp;<a data-toggle='tooltip' data-placement='top' title='".$userdata['comment']."' onclick='loadPage(\"$link\")'  href='javascript:void(0);'><span class='fas fa-info pointer'></span><a/>";
                                 }
                                 $count_display++;
                              }
                           } else {
                              $out .= getUserName($data[$ID][$k]['name']);
                              $count_display++;
                           }

                           // Manage alternative_email for tickets_users
                           if (($itemtype == 'PluginAssistancesTicket')
                              && isset($data[$ID][$k][2])) {

                              $split = explode(self::LONGSEP, $data[$ID][$k][2]);
                              for ($l=0; $l<count($split); $l++) {
                                 $split2 = explode(" ", $split[$l]);
                                 if ((count($split2) == 2) && ($split2[0] == 0) && !empty($split2[1])) {
                                    if ($count_display) {
                                       $out .= self::LBBR;
                                    }
                                    $count_display++;
                                    $out .= "<a href='mailto:".$split2[1]."'>".$split2[1]."</a>";
                                 }
                              }
                           }
                        }
                     }
                     return $out;
                  }
                  if ($itemtype != 'User') {
                     $toadd = '';
                     if (($itemtype == 'User')
                           && ($data[$ID][0]['id'] > 0)) {
                        $out  = getUserName($data[$ID][0]['id']);
                        $link = PluginServicesUser::getFormURLWithID($data[$ID][0]['id']);
                        $out  .= "&nbsp;<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'><span class='fas fa-info pointer'></span><a/>";                                      
                     }
                     return $out ;
                  }
                  break;

               case "glpi_profiles.name" :
                  if (($itemtype == 'User')
                        && ($orig_id == 20)) {
                     $out           = "";

                     $count_display = 0;
                     $added         = [];
                     for ($k=0; $k<$data[$ID]['count']; $k++) {
                        if (strlen(trim($data[$ID][$k]['name'])) > 0
                           && !in_array($data[$ID][$k]['name']."-".$data[$ID][$k]['entities_id'],
                                       $added)) {
                           $text = sprintf(__('%1$s - %2$s'), $data[$ID][$k]['name'],
                                          Dropdown::getDropdownName('glpi_entities',
                                                                     $data[$ID][$k]['entities_id']));
                           $comp = '';
                           if ($data[$ID][$k]['is_recursive']) {
                              $comp = __('R');
                              if ($data[$ID][$k]['is_dynamic']) {
                                 $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                              }
                           }
                           if ($data[$ID][$k]['is_dynamic']) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                           }
                           if (!empty($comp)) {
                              $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                           }
                           if ($count_display) {
                              $out .= self::LBBR;
                           }
                           $count_display++;
                           $out     .= $text;
                           $added[]  = $data[$ID][$k]['name']."-".$data[$ID][$k]['entities_id'];
                        }
                     }
                     return $out;
                  }
                  break;

               case "glpi_entities.completename" :
                  if ($itemtype == 'PluginServicesUser') {

                     $out           = "";
                     $added         = [];
                     $count_display = 0;
                     for ($k=0; $k<$data[$ID]['count']; $k++) {
                        if (isset($data[$ID][$k]['name'])
                              && (strlen(trim($data[$ID][$k]['name'])) > 0)
                              && !in_array($data[$ID][$k]['name']."-".$data[$ID][$k]['profiles_id'],
                                          $added)) {
                           $text = sprintf(__('%1$s - %2$s'), $data[$ID][$k]['name'],
                                             Dropdown::getDropdownName('glpi_profiles',
                                                                     $data[$ID][$k]['profiles_id']));
                           $comp = '';
                           if ($data[$ID][$k]['is_recursive']) {
                              $comp = __('R');
                              if ($data[$ID][$k]['is_dynamic']) {
                                 $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                              }
                           }
                           if ($data[$ID][$k]['is_dynamic']) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                           }
                           if (!empty($comp)) {
                              $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                           }
                           if ($count_display) {
                              $out .= self::LBBR;
                           }
                           $count_display++;
                           $out    .= $text;
                           $added[] = $data[$ID][$k]['name']."-".$data[$ID][$k]['profiles_id'];
                        }
                     }
                     return $out;
                  }
                  break;

               case "glpi_documenttypes.icon" :
                  if (!empty($data[$ID][0]['name'])) {
                     return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".
                              $data[$ID][0]['name']."'>";
                  }
                  return "&nbsp;";

               case "glpi_documents.filename" :
                  $doc = new Document();
                  if ($doc->getFromDB($data['id'])) {
                     return $doc->getDownloadLink();
                  }
                  return NOT_AVAILABLE;

               case "glpi_tickets_tickets.tickets_id_1" :
                  $out        = "";
                  $displayed  = [];
                  for ($k=0; $k<$data[$ID]['count']; $k++) {

                     $linkid = ($data[$ID][$k]['tickets_id_2'] == $data['id'])
                                    ? $data[$ID][$k]['name']
                                    : $data[$ID][$k]['tickets_id_2'];
                     if (($linkid > 0) && !isset($displayed[$linkid])) {
                        $link = PluginAssistancesTicket::getFormURLWithID($linkid);
                        $text = "<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'>";
                        $text .= Dropdown::getDropdownName('glpi_tickets', $linkid)."</a>";
                        if (count($displayed)) {
                           $out .= self::LBBR;
                        }
                        $displayed[$linkid] = $linkid;
                        $out               .= $text;
                     }
                  }
                  return $out;

               case "glpi_problems.id" :
                  if ($so["datatype"] == 'count') {
                     if (($data[$ID][0]['name'] > 0)
                        && Session::haveRight("problem", Problem::READALL)) {
                        if ($itemtype == 'ITILCategory') {
                           $options['criteria'][0]['field']      = 7;
                           $options['criteria'][0]['searchtype'] = 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';
                        } else {
                           $options['criteria'][0]['field']       = 12;
                           $options['criteria'][0]['searchtype']  = 'equals';
                           $options['criteria'][0]['value']       = 'all';
                           $options['criteria'][0]['link']        = 'AND';

                           $options['metacriteria'][0]['itemtype']   = $itemtype;
                           $options['metacriteria'][0]['field']      = self::getOptionNumber($itemtype,
                                 'name');
                           $options['metacriteria'][0]['searchtype'] = 'equals';
                           $options['metacriteria'][0]['value']      = $data['id'];
                           $options['metacriteria'][0]['link']       = 'AND';
                        }

                        $options['reset'] = 'reset';

                        $out  = "<a id='problem$itemtype".$data['id']."' ";
                        $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                                 Toolbox::append_params($options, '&amp;')."\">";
                        $out .= $data[$ID][0]['name']."</a>";
                        return $out;
                     }
                  }
                  break;

               case "glpi_tickets.id" :
                  if ($so["datatype"] == 'count') {
                     if (($data[$ID][0]['name'] > 0)
                        && Session::haveRight("ticket", PluginAssistancesTicket::READALL)) {

                        if ($itemtype == 'PluginServicesUser') {
                           // Requester
                           if ($ID == 'User_60') {
                              $options['criteria'][0]['field']      = 4;
                              $options['criteria'][0]['searchtype']= 'equals';
                              $options['criteria'][0]['value']      = $data['id'];
                              $options['criteria'][0]['link']       = 'AND';
                           }

                           // Writer
                           if ($ID == 'User_61') {
                              $options['criteria'][0]['field']      = 22;
                              $options['criteria'][0]['searchtype']= 'equals';
                              $options['criteria'][0]['value']      = $data['id'];
                              $options['criteria'][0]['link']       = 'AND';
                           }
                           // Assign
                           if ($ID == 'User_64') {
                              $options['criteria'][0]['field']      = 5;
                              $options['criteria'][0]['searchtype']= 'equals';
                              $options['criteria'][0]['value']      = $data['id'];
                              $options['criteria'][0]['link']       = 'AND';
                           }
                        } else if ($itemtype == 'ITILCategory') {
                           $options['criteria'][0]['field']      = 7;
                           $options['criteria'][0]['searchtype'] = 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';

                        } else {
                           $options['criteria'][0]['field']       = 12;
                           $options['criteria'][0]['searchtype']  = 'equals';
                           $options['criteria'][0]['value']       = 'all';
                           $options['criteria'][0]['link']        = 'AND';

                           $options['metacriteria'][0]['itemtype']   = $itemtype;
                           $options['metacriteria'][0]['field']      = self::getOptionNumber($itemtype,
                                                                                             'name');
                           $options['metacriteria'][0]['searchtype'] = 'equals';
                           $options['metacriteria'][0]['value']      = $data['id'];
                           $options['metacriteria'][0]['link']       = 'AND';
                        }

                        $options['reset'] = 'reset';

                        $out  = "<a id='ticket$itemtype".$data['id']."' ";
                        $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                                 Toolbox::append_params($options, '&amp;')."\">";
                        $out .= $data[$ID][0]['name']."</a>";
                        return $out;
                     }
                  }
                  break;

               case "glpi_tickets.time_to_resolve" :
               case "glpi_problems.time_to_resolve" :
               case "glpi_changes.time_to_resolve" :
               case "glpi_tickets.time_to_own" :
               case "glpi_tickets.internal_time_to_own" :
               case "glpi_tickets.internal_time_to_resolve" :
                  // Due date + progress
                  if (in_array($orig_id, [151, 158, 181, 186])) {
                     $out = Html::convDateTime($data[$ID][0]['name']);

                     // No due date in waiting status
                     if ($data[$ID][0]['status'] == CommonITILObject::WAITING) {
                        return '';
                     }
                     if (empty($data[$ID][0]['name'])) {
                        return '';
                     }
                     if (($data[$ID][0]['status'] == PluginAssistancesTicket::SOLVED)
                        || ($data[$ID][0]['status'] == PluginAssistancesTicket::CLOSED)) {
                        return $out;
                     }

                     $itemtype = getItemTypeForTable($table);
                     $item = new $itemtype();
                     $item->getFromDB($data['id']);
                     $percentage  = 0;
                     $totaltime   = 0;
                     $currenttime = 0;
                     $slaField    = 'slas_id';

                     // define correct sla field
                     switch ($table.'.'.$field) {
                        case "glpi_tickets.time_to_resolve" :
                           $slaField = 'slas_id_ttr';
                           break;
                        case "glpi_tickets.time_to_own" :
                           $slaField = 'slas_id_tto';
                           break;
                        case "glpi_tickets.internal_time_to_own" :
                           $slaField = 'olas_id_tto';
                           break;
                        case "glpi_tickets.internal_time_to_resolve" :
                           $slaField = 'olas_id_ttr';
                           break;
                     }

                     switch ($table.'.'.$field) {
                        // If ticket has been taken into account : no progression display
                        case "glpi_tickets.time_to_own" :
                        case "glpi_tickets.internal_time_to_own" :
                           if (($item->fields['takeintoaccount_delay_stat'] > 0)) {
                              return $out;
                           }
                           break;
                     }

                     if ($item->isField($slaField) && $item->fields[$slaField] != 0) { // Have SLA
                        $sla = new SLA();
                        $sla->getFromDB($item->fields[$slaField]);
                        $currenttime = $sla->getActiveTimeBetween($item->fields['date'],
                                                                  date('Y-m-d H:i:s'));
                        $totaltime   = $sla->getActiveTimeBetween($item->fields['date'],
                                                                  $data[$ID][0]['name']);
                     } else {
                        $calendars_id = Entity::getUsedConfig('calendars_id',
                                                            $item->fields['entities_id']);
                        if ($calendars_id != 0) { // Ticket entity have calendar
                           $calendar = new Calendar();
                           $calendar->getFromDB($calendars_id);
                           $currenttime = $calendar->getActiveTimeBetween($item->fields['date'],
                                                                        date('Y-m-d H:i:s'));
                           $totaltime   = $calendar->getActiveTimeBetween($item->fields['date'],
                                                                        $data[$ID][0]['name']);
                        } else { // No calendar
                           $currenttime = strtotime(date('Y-m-d H:i:s'))
                                                   - strtotime($item->fields['date']);
                           $totaltime   = strtotime($data[$ID][0]['name'])
                                                   - strtotime($item->fields['date']);
                        }
                     }
                     if ($totaltime != 0) {
                        $percentage  = round((100 * $currenttime) / $totaltime);
                     } else {
                        // Total time is null : no active time
                        $percentage = 100;
                     }
                     if ($percentage > 100) {
                        $percentage = 100;
                     }
                     $percentage_text = $percentage;

                     if ($_SESSION['glpiduedatewarning_unit'] == '%') {
                        $less_warn_limit = $_SESSION['glpiduedatewarning_less'];
                        $less_warn       = (100 - $percentage);
                     } else if ($_SESSION['glpiduedatewarning_unit'] == 'hour') {
                        $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * HOUR_TIMESTAMP;
                        $less_warn       = ($totaltime - $currenttime);
                     } else if ($_SESSION['glpiduedatewarning_unit'] == 'day') {
                        $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * DAY_TIMESTAMP;
                        $less_warn       = ($totaltime - $currenttime);
                     }

                     if ($_SESSION['glpiduedatecritical_unit'] == '%') {
                        $less_crit_limit = $_SESSION['glpiduedatecritical_less'];
                        $less_crit       = (100 - $percentage);
                     } else if ($_SESSION['glpiduedatecritical_unit'] == 'hour') {
                        $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * HOUR_TIMESTAMP;
                        $less_crit       = ($totaltime - $currenttime);
                     } else if ($_SESSION['glpiduedatecritical_unit'] == 'day') {
                        $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * DAY_TIMESTAMP;
                        $less_crit       = ($totaltime - $currenttime);
                     }

                     $color = $_SESSION['glpiduedateok_color'];
                     if ($less_crit < $less_crit_limit) {
                        $color = $_SESSION['glpiduedatecritical_color'];
                     } else if ($less_warn < $less_warn_limit) {
                        $color = $_SESSION['glpiduedatewarning_color'];
                     }

                     if (!isset($so['datatype'])) {
                        $so['datatype'] = 'progressbar';
                     }

                     $progressbar_data = [
                        'text'         => Html::convDateTime($data[$ID][0]['name']),
                        'percent'      => $percentage,
                        'percent_text' => $percentage_text,
                        'color'        => $color
                     ];
                  }
                  break;

               case "glpi_softwarelicenses.number" :
                  if ($data[$ID][0]['min'] == -1) {
                     return __('Unlimited');
                  }
                  if (empty($data[$ID][0]['name'])) {
                     return 0;
                  }
                  return $data[$ID][0]['name'];

               case "glpi_auth_tables.name" :
                  return Auth::getMethodName($data[$ID][0]['name'], $data[$ID][0]['auths_id'], 1,
                                             $data[$ID][0]['ldapname'].$data[$ID][0]['mailname']);

               case "glpi_reservationitems.comment" :
                  if (empty($data[$ID][0]['name'])) {
                     $text = __('None');
                  } else {
                     $text = Html::resume_text($data[$ID][0]['name']);
                  }
                  if (Session::haveRight('reservation', UPDATE)) {
                     // return "<a title=\"".__s('Modify the comment')."\"
                     //          href='".ReservationItem::getFormURLWithID($data['refID'])."'  >".$text."</a>";
                              $link = ReservationItem::getFormURLWithID($data['refID']);
                              return "<a title=\"".__s('Modify the comment')."\"
                              href='javascript:void(0);' onclick='loadPage(\"$link\")' >".$text."</a>";
                  }
                  return $text;

               case 'glpi_crontasks.description' :
                  $tmp = new CronTask();
                  return $tmp->getDescription($data[$ID][0]['name']);

               case 'glpi_changes.status':
                  $status = Change::getStatus($data[$ID][0]['name']);
                  return "<span class='no-wrap'>".
                        Change::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                        "</span>";

               case 'glpi_problems.status':
                  $status = Problem::getStatus($data[$ID][0]['name']);
                  return "<span class='no-wrap'>".
                        Problem::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                        "</span>";

               case 'glpi_tickets.status':
                  $status = PluginAssistancesTicket::getStatus($data[$ID][0]['name']);
                  return "<span class='no-wrap'>".
                        PluginAssistancesTicket::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                        "</span>";

               case 'glpi_projectstates.name':
                  $out = '';
                  $name = $data[$ID][0]['name'];
                  if (isset($data[$ID][0]['trans'])) {
                     $name = $data[$ID][0]['trans'];
                  }
                  if ($itemtype == 'ProjectState') {
                     $link = ProjectState::getFormURLWithID($data[$ID][0]["id"]);
                     $out = "<a  onclick='loadPage(\"$link\")' href='javascript:void(0);'>". $name."</a></div>";
                  } else {
                     $out = $name;
                  }
                  return $out;

               case 'glpi_items_tickets.items_id' :
               case 'glpi_items_problems.items_id' :
               case 'glpi_changes_items.items_id' :
               case 'glpi_certificates_items.items_id' :
               case 'glpi_appliances_items.items_id' :
                  if (!empty($data[$ID])) {
                     $items = [];
                     foreach ($data[$ID] as $key => $val) {
                        if (is_numeric($key)) {
                           if (!empty($val['itemtype'])
                                 && ($item = getItemForItemtype($val['itemtype']))) {
                              if ($item->getFromDB($val['name'])) {
                                 $items[] = $item->getLink(['comments' => true]);
                              }
                           }
                        }
                     }
                     if (!empty($items)) {
                        return implode("<br>", $items);
                     }
                  }
                  return '&nbsp;';

               case 'glpi_items_tickets.itemtype' :
               case 'glpi_items_problems.itemtype' :
                  if (!empty($data[$ID])) {
                     $itemtypes = [];
                     foreach ($data[$ID] as $key => $val) {
                        if (is_numeric($key)) {
                           if (!empty($val['name'])
                                 && ($item = getItemForItemtype($val['name']))) {
                              $item = new $val['name']();
                              $name = $item->getTypeName();
                              $itemtypes[] = __($name);
                           }
                        }
                     }
                     if (!empty($itemtypes)) {
                        return implode("<br>", $itemtypes);
                     }
                  }

                  return '&nbsp;';

               case 'glpi_tickets.name' :
               case 'glpi_problems.name' :
               case 'glpi_changes.name' :

                  if (isset($data[$ID][0]['content'])
                        && isset($data[$ID][0]['id'])
                        && isset($data[$ID][0]['status'])) {
                     $link = $itemtype::getFormURLWithID($data[$ID][0]['id']);
                     // Force solution tab if solved
                     if ($item = getItemForItemtype($itemtype)) {
                        if (in_array($data[$ID][0]['status'], $item->getSolvedStatusArray())) {
                           // $out .= "&amp;forcetab=$itemtype$2";
                           $link = $link."&amp;forcetab=$itemtype$2";
                        }
                     }
                     $hdecode = Html::entity_decode_deep($data[$ID][0]['content']);
                     $content = Toolbox::unclean_cross_side_scripting_deep($hdecode);
                     $out = "<a data-toggle='tooltip' data-placement='top' title='".$content."' onclick='loadPage(\"$link\")' id='$itemtype".$data[$ID][0]['id']."' href='javascript:void(0);'>";
                        $name = $data[$ID][0]['name'];
                        if ($_SESSION["glpiis_ids_visible"]
                           || empty($data[$ID][0]['name'])) {
                           $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][0]['id']);
                        }
                     $out    .= $name."</a>";
                     // $out     = sprintf(__('%1$s %2$s'), $out,
                     //                    Html::showToolTip(nl2br(Html::Clean($content)),
                     //                                            ['applyto' => $itemtype.
                     //                                                               $data[$ID][0]['id'],
                     //                                                  'display' => false]));
                     return $out;
                  }

               case 'glpi_ticketvalidations.status' :
                  $out   = '';
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if ($data[$ID][$k]['name']) {
                        $status  = TicketValidation::getStatus($data[$ID][$k]['name']);
                        $bgcolor = TicketValidation::getStatusColor($data[$ID][$k]['name']);
                        $out    .= (empty($out)?'':self::LBBR).
                                    "<div style=\"background-color:".$bgcolor.";\">".$status.'</div>';
                     }
                  }
                  return $out;

               case 'glpi_ticketsatisfactions.satisfaction' :
                  if (self::$output_type == self::HTML_OUTPUT) {
                     return TicketSatisfaction::displaySatisfaction($data[$ID][0]['name']);
                  }
                  break;

               case 'glpi_projects._virtual_planned_duration' :
                  return Html::timestampToString(ProjectTask::getTotalPlannedDurationForProject($data["id"]),
                                                false);

               case 'glpi_projects._virtual_effective_duration' :
                  return Html::timestampToString(ProjectTask::getTotalEffectiveDurationForProject($data["id"]),
                                                false);

               case 'glpi_cartridgeitems._virtual' :
                  return Cartridge::getCount($data["id"], $data[$ID][0]['alarm_threshold'],
                                             self::$output_type != self::HTML_OUTPUT);

               case 'glpi_printers._virtual' :
                  return Cartridge::getCountForPrinter($data["id"],
                                                      self::$output_type != self::HTML_OUTPUT);

               case 'glpi_consumableitems._virtual' :
                  return Consumable::getCount($data["id"], $data[$ID][0]['alarm_threshold'],
                                             self::$output_type != self::HTML_OUTPUT);

               case 'glpi_links._virtual' :
                  $out = '';
                  $link = new Link();
                  if (($item = getItemForItemtype($itemtype))
                     && $item->getFromDB($data['id'])
                  ) {
                     $data = Link::getLinksDataForItem($item);
                     $count_display = 0;
                     foreach ($data as $val) {
                        $links = Link::getAllLinksFor($item, $val);
                        foreach ($links as $link) {
                           if ($count_display) {
                              $out .=  self::LBBR;
                           }
                           $out .= $link;
                           $count_display++;
                        }
                     }
                  }
                  return $out;

               case 'glpi_reservationitems._virtual' :
                  if ($data[$ID][0]['is_active']) {
                     return "<a href='reservation.php?reservationitems_id=".
                                             $data["refID"]."' title=\"".__s('See planning')."\">".
                                             "<i class='far fa-calendar-alt'></i><span class='sr-only'>".__('See planning')."</span></a>";
                  } else {
                     return "&nbsp;";
                  }

               case "glpi_tickets.priority" :
               case "glpi_problems.priority" :
               case "glpi_changes.priority" :
               case "glpi_projects.priority" :
                  $index = $data[$ID][0]['name'];
                  $color = $_SESSION["glpipriority_$index"];
                  $name  = CommonITILObject::getPriorityName($index);
                  return "<div class='priority_block' style='border-color: $color'>
                           <span style='background: $color'></span>&nbsp;$name
                        </div>";
            }
         }

         //// Default case

         if ($itemtype == 'PluginAssistancesTicket'
            && Session::getCurrentInterface() == 'helpdesk'
            && $orig_id == 8
            && Entity::getUsedConfig(
               'anonymize_support_agents',
               $itemtype::getById($data['id'])->getEntityId()
            )
         ) {
            // Assigned groups
            return __("Helpdesk group");
         }

         // Link with plugin tables : need to know left join structure
         if (isset($table)) {
            if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table.'.'.$field, $matches)) {
               if (count($matches) == 2) {
                  $plug     = $matches[1];
                  $out = Plugin::doOneHook(
                     $plug,
                     'giveItem',
                     $itemtype, $orig_id, $data, $ID
                  );
                  if (!empty($out)) {
                     return $out;
                  }
               }
            }
         }
         $unit = '';
         if (isset($so['unit'])) {
            $unit = $so['unit'];
         }

         // Preformat items
         if (isset($so["datatype"])) {
            switch ($so["datatype"]) {
               case "itemlink" :
                  $linkitemtype  = getItemTypeForTable($so["table"]);
                  $pluginlinkitemtype = 'PluginServices'.$linkitemtype;
                  // verifions s'il exist une class custom
                  $linkitemtype = (class_exists($pluginlinkitemtype)) ? $pluginlinkitemtype :  $linkitemtype;

                  $out           = "";
                  $count_display = 0;
                  $separate      = self::LBBR;
                  if (isset($so['splititems']) && $so['splititems']) {
                     $separate = self::LBHR;
                  }

                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (isset($data[$ID][$k]['id'])) {
                        if ($count_display) {
                           $out .= $separate;
                        }
                        $count_display++;
                        $page  = $linkitemtype::getFormURLWithID($data[$ID][$k]['id']);
                        $name  = Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                        if ($_SESSION["glpiis_ids_visible"] || empty($data[$ID][$k]['name'])) {
                           $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][$k]['id']);
                        }
                        // $out  .= "<a id='".$linkitemtype."_".$data['id']."_".
                        //            $data[$ID][$k]['id']."' href='$page'>".
                        //           $name."</a>";

                        $out  .= "<a id='".$linkitemtype."_".$data['id']."_".
                           $data[$ID][$k]['id']."' onclick='loadPage(\"$page\")' href='javascript:void(0);'>".
                        $name."</a>";
                     }
                  }
                  return $out;

               case "text" :
                  $separate = self::LBBR;
                  if (isset($so['splititems']) && $so['splititems']) {
                     $separate = self::LBHR;
                  }

                  $out           = '';
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                        if ($count_display) {
                           $out .= $separate;
                        }
                        $count_display++;
                        $text = "";
                        if (isset($so['htmltext']) && $so['htmltext']) {
                           $text = Html::clean(Toolbox::unclean_cross_side_scripting_deep(nl2br($data[$ID][$k]['name'])));
                        } else {
                           $text = nl2br($data[$ID][$k]['name']);
                        }

                        if (self::$output_type == self::HTML_OUTPUT
                           && (Toolbox::strlen($text) > $CFG_GLPI['cut'])) {
                           $rand = mt_rand();
                           $popup_params = [
                              'display'   => false
                           ];
                           if (Toolbox::strlen($text) > $CFG_GLPI['cut']) {
                              $popup_params += [
                                 'awesome-class'   => 'fa-comments',
                                 'autoclose'       => false,
                                 'onclick'         => true
                              ];
                           } else {
                              $popup_params += [
                                 'applyto'   => "text$rand",
                              ];
                           }
                           $out .= sprintf(
                              __('%1$s %2$s'),
                              "<span id='text$rand'>". Html::resume_text($text, $CFG_GLPI['cut']).'</span>',
                              ''
                           );
                        } else {
                           $out .= $text;
                        }
                     }
                  }
                  return $out;

               case "date" :
               case "date_delay" :
                  $out   = '';
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (is_null($data[$ID][$k]['name'])
                        && isset($so['emptylabel']) && $so['emptylabel']) {
                        $out .= (empty($out)?'':self::LBBR).$so['emptylabel'];
                     } else {
                        $out .= (empty($out)?'':self::LBBR).Html::convDate($data[$ID][$k]['name']);
                     }
                  }
                  return $out;

               case "datetime" :
                  $out   = '';
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (is_null($data[$ID][$k]['name'])
                        && isset($so['emptylabel']) && $so['emptylabel']) {
                        $out .= (empty($out)?'':self::LBBR).$so['emptylabel'];
                     } else {
                        $out .= (empty($out)?'':self::LBBR).Html::convDateTime($data[$ID][$k]['name']);
                     }
                  }
                  return $out;

               case "timestamp" :
                  $withseconds = false;
                  if (isset($so['withseconds'])) {
                     $withseconds = $so['withseconds'];
                  }
                  $withdays = true;
                  if (isset($so['withdays'])) {
                     $withdays = $so['withdays'];
                  }

                  $out   = '';
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     $out .= (empty($out)?'':'<br>').Html::timestampToString($data[$ID][$k]['name'],
                                                                              $withseconds,
                                                                              $withdays);
                  }
                  return $out;

               case "email" :
                  $out           = '';
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if ($count_display) {
                        $out .= self::LBBR;
                     }
                     $count_display++;
                     if (!empty($data[$ID][$k]['name'])) {
                        $out .= (empty($out)?'':self::LBBR);
                        $out .= "<a href='mailto:".Html::entities_deep($data[$ID][$k]['name'])."'>".$data[$ID][$k]['name'];
                        $out .= "</a>";
                     }
                  }
                  return (empty($out) ? "&nbsp;" : $out);

               case "weblink" :
                  $orig_link = trim($data[$ID][0]['name']);
                  if (!empty($orig_link) && Toolbox::isValidWebUrl($orig_link)) {
                     // strip begin of link
                     $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/', '', $orig_link);
                     $link = preg_replace('/\/$/', '', $link);
                     if (Toolbox::strlen($link)>$CFG_GLPI["url_maxlength"]) {
                        $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"])."...";
                     }
                     return "<a href=\"".Toolbox::formatOutputWebLink($orig_link)."\" target='_blank'>$link</a>";
                  }
                  return "&nbsp;";

               case "count" :
               case "number" :
                  $out           = "";
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        if (isset($so['toadd'])
                              && isset($so['toadd'][$data[$ID][$k]['name']])) {
                           $out .= $so['toadd'][$data[$ID][$k]['name']];
                        } else {
                           $number = str_replace(' ', '&nbsp;',
                                                Html::formatNumber($data[$ID][$k]['name'], false, 0));
                           $out .= Dropdown::getValueWithUnit($number, $unit);
                        }
                     }
                  }
                  return $out;

               case "decimal" :
                  $out           = "";
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0) {

                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        if (isset($so['toadd'])
                              && isset($so['toadd'][$data[$ID][$k]['name']])) {
                           $out .= $so['toadd'][$data[$ID][$k]['name']];
                        } else {
                           $number = str_replace(' ', '&nbsp;',
                                                Html::formatNumber($data[$ID][$k]['name']));
                           $out   .= Dropdown::getValueWithUnit($number, $unit);
                        }
                     }
                  }
                  return $out;

               case "bool" :
                  $out           = "";
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        $out .= Dropdown::getValueWithUnit(Dropdown::getYesNo($data[$ID][$k]['name']),
                                                         $unit);
                     }
                  }
                  return $out;

               case "itemtypename":
                  if ($obj = getItemForItemtype($data[$ID][0]['name'])) {
                     return $obj->getTypeName();
                  }
                  return "";

               case "language":
                  if (isset($CFG_GLPI['languages'][$data[$ID][0]['name']])) {
                     return $CFG_GLPI['languages'][$data[$ID][0]['name']][0];
                  }
                  return __('Default value');
               case 'progressbar':
                  if (!isset($progressbar_data)) {
                     $bar_color = 'green';
                     $progressbar_data = [
                        'percent'      => $data[$ID][0]['name'],
                        'percent_text' => $data[$ID][0]['name'],
                        'color'        => $bar_color,
                        'text'         => ''
                     ];
                  }

                  $out = "{$progressbar_data['text']}<div class='center' style='background-color: #ffffff; width: 100%;
                           border: 1px solid #9BA563; position: relative;' >";
                  $out .= "<div style='position:absolute;'>&nbsp;{$progressbar_data['percent_text']}%</div>";
                  $out .= "<div class='center' style='background-color: {$progressbar_data['color']};
                           width: {$progressbar_data['percent']}%; height: 12px' ></div>";
                  $out .= "</div>";

                  return $out;
                  break;
            }
         }
         // Manage items with need group by / group_concat
         $out           = "";
         $count_display = 0;
         $separate      = self::LBBR;
         if (isset($so['splititems']) && $so['splititems']) {
            $separate = self::LBHR;
         }
         for ($k=0; $k<$data[$ID]['count']; $k++) {
            if (strlen(trim($data[$ID][$k]['name'])) > 0) {
               if ($count_display) {
                  $out .= $separate;
               }
               $count_display++;
               // Get specific display if available
               if (isset($table)) {
                  $itemtype = getItemTypeForTable($table);
                  if ($item = getItemForItemtype($itemtype)) {
                     $tmpdata  = $data[$ID][$k];
                     // Copy name to real field
                     $tmpdata[$field] = $data[$ID][$k]['name'];

                     $specific = $item->getSpecificValueToDisplay(
                        $field,
                        $tmpdata, [
                           'html'      => true,
                           'searchopt' => $so,
                           'raw_data'  => $data
                        ]
                     );
                  }
               }
               if (!empty($specific)) {
                  $out .= $specific;
               } else {
                  if (isset($so['toadd'])
                     && isset($so['toadd'][$data[$ID][$k]['name']])) {
                     $out .= $so['toadd'][$data[$ID][$k]['name']];
                  } else {
                     // Empty is 0 or empty
                     if (empty($split[0])&& isset($so['emptylabel'])) {
                        $out .= $so['emptylabel'];
                     } else {
                        // Trans field exists
                        if (isset($data[$ID][$k]['trans']) && !empty($data[$ID][$k]['trans'])) {
                           $out .=  Dropdown::getValueWithUnit($data[$ID][$k]['trans'], $unit);
                        } else {
                           $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                        }
                     }
                  }
               }
            }
         }
         return $out;
   }