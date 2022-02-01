<?php
//include ('../../../inc/includes.php');
//global $CFG_GLPI;
//PluginServicesHtml::nullHeader("Login", $CFG_GLPI["root_doc"] . '/index.php');
use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * Ticket Class
**/

class PluginServicesTicket extends Ticket {
   
    static $rightname = 'tickets';
    function showForm($ID, $options = []) {
        global $CFG_GLPI;
  
        $this->forceTable("glpi_tickets");

      //   if (isset($options['_add_fromitem']) && isset($options['itemtype'])) {
           $item = new Ticket();
           $item->getFromDB($ID);
           $this->fields = $item->fields;
           $options['entities_id'] = $item->fields['entities_id'];
      //   }
  
        $default_values = self::getDefaultValues();
  
        // Restore saved value or override with page parameter
        $saved = $this->restoreInput();
  
        foreach ($default_values as $name => $value) {
           if (!isset($options[$name])) {
              if (isset($saved[$name])) {
                 $options[$name] = $saved[$name];
              } else {
                 $options[$name] = $value;
              }
           }
        }
  
        if (isset($options['content'])) {
           // Clean new lines to be fix encoding
           $order              = ['\\r', '\\n', "\\'", '\\"', "\\\\"];
           $replace            = ["", "", "'", '"', "\\"];
           $options['content'] = str_replace($order, $replace, $options['content']);
        }
        if (isset($options['name'])) {
           $order           = ["\\'", '\\"', "\\\\"];
           $replace         = ["'", '"', "\\"];
           $options['name'] = str_replace($order, $replace, $options['name']);
        }
  
        if (!isset($options['_skip_promoted_fields'])) {
           $options['_skip_promoted_fields'] = false;
        }
  
        if (!$ID) {
           // Override defaut values from projecttask if needed
           if (isset($options['_projecttasks_id'])) {
              $pt = new ProjectTask();
              if ($pt->getFromDB($options['_projecttasks_id'])) {
                 $options['name'] = $pt->getField('name');
                 $options['content'] = $pt->getField('name');
              }
           }
           // Override defaut values from followup if needed
           if (isset($options['_promoted_fup_id']) && !$options['_skip_promoted_fields']) {
              $fup = new ITILFollowup();
              if ($fup->getFromDB($options['_promoted_fup_id'])) {
                 $options['content'] = $fup->getField('content');
                 $options['_users_id_requester'] = $fup->fields['users_id'];
                 $options['_link'] = [
                    'link'         => Ticket_Ticket::SON_OF,
                    'tickets_id_2' => $fup->fields['items_id']
                 ];
              }
              //Allow overriding the default values
              $options['_skip_promoted_fields'] = true;
           }
        }
  
        // Check category / type validity
        if ($options['itilcategories_id']) {
           $cat = new ITILCategory();
           if ($cat->getFromDB($options['itilcategories_id'])) {
              switch ($options['type']) {
                 case self::INCIDENT_TYPE :
                    if (!$cat->getField('is_incident')) {
                       $options['itilcategories_id'] = 0;
                    }
                    break;
  
                 case self::DEMAND_TYPE :
                    if (!$cat->getField('is_request')) {
                       $options['itilcategories_id'] = 0;
                    }
                    break;
  
                 default :
                    break;
              }
           }
        }
      
        // Default check
        if ($ID > 0) {
           $this->check($ID, READ);
        } else {
           // Create item
           $this->check(-1, CREATE, $options);
        }
  
        if (!$ID) {
           $this->userentities = [];
           if ($options["_users_id_requester"]) {
              //Get all the user's entities
              $requester_entities = Profile_User::getUserEntities($options["_users_id_requester"], true,
                                                            true);
              $user_entities = $_SESSION['glpiactiveentities'];
              $this->userentities = array_intersect($requester_entities, $user_entities);
           }
           $this->countentitiesforuser = count($this->userentities);
  
           if (($this->countentitiesforuser > 0)
               && !in_array($this->fields["entities_id"], $this->userentities)) {
              // If entity is not in the list of user's entities,
              // then use as default value the first value of the user's entites list
              $this->fields["entities_id"] = $this->userentities[0];
              // Pass to values
              $options['entities_id']       = $this->userentities[0];
           }
        }
  
        if ($options['type'] <= 0) {
           $options['type'] = Entity::getUsedConfig('tickettype', $options['entities_id'], '',
                                                   Ticket::INCIDENT_TYPE);
        }
  
        if (!isset($options['template_preview'])) {
           $options['template_preview'] = 0;
        }
  
        if (!isset($options['_promoted_fup_id'])) {
           $options['_promoted_fup_id'] = 0;
        }
  
        // Load template if available :
        $tt = $item->getITILTemplateToUse(
           $options['template_preview'],
           $this->fields['type'],
           ($ID ? $this->fields['itilcategories_id'] : $options['itilcategories_id']),
           ($ID ? $this->fields['entities_id'] : $options['entities_id'])
        );
     
        // Predefined fields from template : reset them
        if (isset($options['_predefined_fields'])) {
           $options['_predefined_fields']
                          = Toolbox::decodeArrayFromInput($options['_predefined_fields']);
        } else {
           $options['_predefined_fields'] = [];
        }
  

        // Store predefined fields to be able not to take into account on change template
        // Only manage predefined values on ticket creation
        $predefined_fields = [];
        $tpl_key = $item->getTemplateFormFieldName();
        
        if (!$ID) {
  
           if (isset($tt->predefined) && count($tt->predefined)) {
              foreach ($tt->predefined as $predeffield => $predefvalue) {
                 if (isset($default_values[$predeffield])) {
                    // Is always default value : not set
                    // Set if already predefined field
                    // Set if ticket template change
                    if (((count($options['_predefined_fields']) == 0)
                         && ($options[$predeffield] == $default_values[$predeffield]))
                        || (isset($options['_predefined_fields'][$predeffield])
                            && ($options[$predeffield] == $options['_predefined_fields'][$predeffield]))
                        || (isset($options[$tpl_key])
                            && ($options[$tpl_key] != $tt->getID()))
                        // user pref for requestype can't overwrite requestype from template
                        // when change category
                        || (($predeffield == 'requesttypes_id')
                            && empty($saved))) {
  
                       // Load template data
                       $options[$predeffield]            = $predefvalue;
                       $this->fields[$predeffield]      = $predefvalue;
                       $predefined_fields[$predeffield] = $predefvalue;
                    }
                 }
              }
              // All predefined override : add option to say predifined exists
              if (count($predefined_fields) == 0) {
                 $predefined_fields['_all_predefined_override'] = 1;
              }
  
         } else { // No template load : reset predefined values
              if (count($options['_predefined_fields'])) {
                 foreach ($options['_predefined_fields'] as $predeffield => $predefvalue) {
                    if ($options[$predeffield] == $predefvalue) {
                       $options[$predeffield] = $default_values[$predeffield];
                    }
                 }
              }
           }
      }
        // Put ticket template on $options for actors
        $options[str_replace('s_id', '', $tpl_key)] = $tt;
  
        // check right used for this ticket
        $canupdate     = !$ID
                          || (Session::getCurrentInterface() == "central"
                              && $item->canUpdateItem());
        $can_requester = $item->canRequesterUpdateItem();
        $canpriority   = Session::haveRight($item::$rightname, $item::CHANGEPRIORITY);
        $canassign     = $item->canAssign();
        $canassigntome = $item->canAssignTome();
  
        if ($ID && in_array($this->fields['status'], $this->getClosedStatusArray())) {
           $canupdate = false;
           // No update for actors
           $options['_noupdate'] = true;
        }
  
        $showuserlink              = 0;
        if (Session::haveRight('user', READ)) {
           $showuserlink = 1;
        }
  
        if ($options['template_preview']) {
           // Add all values to fields of tickets for template preview
           foreach ($options as $key => $val) {
              if (!isset($this->fields[$key])) {
                 $this->fields[$key] = $val;
              }
           }
        }
  
        
        // In percent
        $colsize1 = '13';
        $colsize2 = '29';
        $colsize3 = '13';
        $colsize4 = '45';
  
        //$this->showFormHeader($options);

        echo '<div class="m-portlet">
                  <div class="m-portlet__head">
                     <div class="m-portlet__head-caption">
                        <div class="m-portlet__head-title">
                           <span class="m-portlet__head-icon m--hide">
                              <i class="la la-gear"></i>
                           </span>
                           <h3 class="m-portlet__head-text">';
                           
                              if($this->fields["type"]==1){
                                 echo '<span class="m-badge m-badge--warning m-badge--wide mr-3" style="font-size:14px;"> <i class="flaticon-warning-sign"></i> Incident </span> ';
                              }else if($this->fields["type"]==2){
                                 echo '<span class="m-badge m-badge--accent m-badge--wide mr-3" style="font-size:14px;"><i class="flaticon-shopping-basket"></i> Demande </span> ';
                              }

                              echo $this->fields["name"];
                           echo'</h3>
                        </div>
                     </div>
                     <form class="m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed" method="post" action="'. $CFG_GLPI["root_doc"] .'/dash/update_ticket.php">
                     <div class="m-portlet__head-tools">';
                     $rand = mt_rand();
                     $this->showTimelineForm($rand);

                     echo "<input type='hidden' name='id' value='$ID'>";
                     echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);
                     echo'<ul class="m-portlet__nav">';
                     //echo "<li ><button class='btn btn-secondary ml-3 active btn-md'>Sauvegarder</button></li>";
                     echo'</ul>';

                     // <ul class="m-portlet__nav">
                        // <li class="m-portlet__nav-item">
                        //    <a href="" class="m-portlet__nav-link m-portlet__nav-link--icon">
                        //       <i style="color:#000;" class="flaticon-attachment"></i>
                        //    </a>
                        // </li>
                        // <li class="m-portlet__nav-item">
                        //    <button type="button" class="btn btn-sm btn-primary active" data-toggle="modal" data-target="#modal_suivi">Suivi</button>
                        //    <div class="modal fade" id="modal_suivi" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
                        //       <div class="modal-dialog" role="document">
                        //          <div class="modal-content">
                        //             <div class="modal-header">
                        //                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                        //                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        //                   <span aria-hidden="true">×</span>
                        //                </button>
                        //             </div>
                        //             <div class="modal-body">
                        //                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry</p>
                        //             </div>
                        //             <div class="modal-footer">
                        //                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        //                <button type="button" class="btn btn-primary">Save changes</button>
                        //             </div>
                        //          </div>
                        //       </div>
                        //    </div>
                        // </li>
                        // <li class="m-portlet__nav-item">
                        //    <button type="button" class="btn btn-sm btn-success active">Résoudre</button>
                        // </li>
                     // </ul>
                  echo'</div>
                  </div>

                  <!--begin::Form-->
                  
                     <div class="m-portlet__body">';

                     // echo '<div class="form-group m-form__group row d-flex justify-content-center">';
                     //    echo"<div class='col-lg-12'>";

                     //       echo"</div>";
                     //    echo"</div>";
                     
                     echo'<div class="form-group m-form__group row d-flex justify-content-center">';

                        echo '<label class="col-lg-1 col-form-label">';
                           echo $tt->getBeginHiddenFieldText('date');
                           if (!$ID) {
                              printf(__('%1$s%2$s'), __('Opening date'), $tt->getMandatoryMark('date'));
                           } else {
                              echo __('Opening date');
                           }
                           echo $tt->getEndHiddenFieldText('date');
                        echo '</label>';

                        echo'<div class="col-lg-3">';

                           echo $tt->getBeginHiddenFieldValue('date');
                           $date = $this->fields["date"];
                     
                           if ($canupdate) {
                              PluginServicesHtml::showDateTimeField("date", ['value'      => $date,
                                                               'canedit'    => false,
                                                               'maybeempty' => false,
                                                               'required'   => ($tt->isMandatoryField('date') && !$ID)]);
                           } else {
                              echo Html::convDateTime($date);
                           }
                           echo $tt->getEndHiddenFieldValue('date', $this);
                           
                        echo'</div>';


                        echo"<label class='col-lg-1 col-form-label'>";
                           $tt->getBeginHiddenFieldText('name');
                           printf(__('%1$s%2$s'), __('Title'), $tt->getMandatoryMark('name'));
                           echo $tt->getEndHiddenFieldText('name');
                        echo"</label>";

                        echo"<div class='col-lg-3 pr-0'>";
                           if ($canupdate || $can_requester) {
                              echo $tt->getBeginHiddenFieldValue('name');
                              echo "<input type='text' style='width:98%' class='form-control m-input' maxlength=250 name='name' ".
                                    ($tt->isMandatoryField('name') ? " required='required'" : '') .
                                    " value=\"".PluginServicesHtml::cleanInputText($this->fields["name"])."\">";
                              echo $tt->getEndHiddenFieldValue('name', $this);
                           } else {
                              if (empty($this->fields["name"])) {
                                 echo __('Without title');
                              } else {
                                 echo $this->fields["name"];
                              }
                           }
                        echo"</div>";

                     echo'</div>';


                     echo'<div class="form-group m-form__group row d-flex justify-content-center">';

                           echo"<label class='col-lg-1 col-form-label'>
                           ".$tt->getBeginHiddenFieldText('status');
                           printf(__('%1$s%2$s'), __('Status'), $tt->getMandatoryMark('status'));
                           echo $tt->getEndHiddenFieldText('status')."
                           </label>";


                           echo"<div class='col-lg-3'>";
                           echo $tt->getBeginHiddenFieldValue('status');
                           if ($canupdate) {
                              self::dropdownStatus(['value'     => $this->fields["status"],
                                                         'showtype'  => 'allowed']);
                              TicketValidation::alertValidation($this, 'status');
                           } else {
                              echo self::getStatus($this->fields["status"]);
                              if ($this->canReopen()) {
                                 $link = $this->getLinkURL(). "&amp;_openfollowup=1&amp;forcetab=";
                                 $link .= "Ticket$1";
                                 echo "&nbsp;<a class='vsubmit' href='$link'>". __('Reopen')."</a>";
                              }
                           }
                           echo $tt->getEndHiddenFieldValue('status', $this);

                           echo "</div>";


                           echo"<label class='col-lg-1 col-form-label'>";
                              $tt->getBeginHiddenFieldText('urgency');
                              printf(__('%1$s%2$s'), __('Urgency'), $tt->getMandatoryMark('urgency'));
                              echo $tt->getEndHiddenFieldText('urgency');
                           echo"</label>";

                           echo"<div class='col-lg-3'>";
                              if ($canupdate || $can_requester) {
                                 echo $tt->getBeginHiddenFieldValue('urgency');
                                 $idurgency = self::dropdownUrgency(['value' => $this->fields["urgency"]]);
                                 echo $tt->getEndHiddenFieldValue('urgency', $this);
                        
                              } else {
                                 $idurgency = "value_urgency".mt_rand();
                                 echo "<input id='$idurgency' type='hidden' name='urgency' value='".
                                       $this->fields["urgency"]."'>";
                                 echo $tt->getBeginHiddenFieldValue('urgency');
                                 echo parent::getUrgencyName($this->fields["urgency"]);
                                 echo $tt->getEndHiddenFieldValue('urgency', $this);
                              }
                           echo"</div>";

                        echo"</div>";


                           echo'<div class="form-group m-form__group row d-flex justify-content-center">';


                              echo"<label class='col-lg-1 col-form-label'>";
                                 $tt->getBeginHiddenFieldText('impact');
                                 printf(__('%1$s%2$s'), __('Impact'), $tt->getMandatoryMark('impact'));
                                 echo $tt->getEndHiddenFieldText('impact');
                              echo"</label>";

                              echo"<div class='col-lg-3'>";
                                 echo $tt->getBeginHiddenFieldValue('impact');
                              
                                 if ($canupdate) {
                                    $idimpact = self::dropdownImpact(['value' => $this->fields["impact"]]);
                                 } else {
                                    $idimpact = "value_impact".mt_rand();
                                    echo "<input id='$idimpact' type='hidden' name='impact' value='".$this->fields["impact"]."'>";
                                    echo parent::getImpactName($this->fields["impact"]);
                                 }
                                 echo $tt->getEndHiddenFieldValue('impact', $this);
                              echo"</div>";

                              //echo'</div>';

                        


                              echo"<label class='col-lg-1 col-form-label'>";
                                 $tt->getBeginHiddenFieldText('priority');
                                 printf(__('%1$s%2$s'), __('Priority'), $tt->getMandatoryMark('priority'));
                                 echo $tt->getEndHiddenFieldText('priority');
                              echo"</label>";

                              echo"<div class='col-lg-3'>";
                                 $idajax = 'change_priority_' . mt_rand();
                           
                                 if ($canpriority
                                    && !$tt->isHiddenField('priority')) {
                                    $idpriority = parent::dropdownPriority(['value'     => $this->fields["priority"],
                                                                                 'withmajor' => true]);
                                    $idpriority = 'dropdown_priority'.$idpriority;
                                    echo "&nbsp;<span id='$idajax' style='display:none'></span>";
                           
                                 } else {
                                    $idpriority = 0;
                                    echo $tt->getBeginHiddenFieldValue('priority');
                                    echo "<span id='$idajax'>".parent::getPriorityName($this->fields["priority"])."</span>";
                                    echo "<input id='$idajax' type='hidden' name='priority' value='".$this->fields["priority"]."'>";
                                    echo $tt->getEndHiddenFieldValue('priority', $this);
                                 }
                           
                                 if ($canupdate || $can_requester) {
                                    $params = ['urgency'  => '__VALUE0__',
                                             'impact'   => '__VALUE1__',
                                             'priority' => $idpriority];
                                    Ajax::updateItemOnSelectEvent(['dropdown_urgency'.$idurgency,
                                                                        'dropdown_impact'.$idimpact],
                                                                  $idajax,
                                                                  $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
                                 }
                              echo"</div>";

                           echo"</div>";


                           echo '<div class="form-group m-form__group row d-flex justify-content-center">';


                              echo '<label class="col-lg-1 col-form-label">Groupe d\'assignation</label>
                                 <div class="col-lg-3">
                                    <div class="m-input-icon m-input-icon--right">';
                                       PluginServicesGroup::dropdown(['value'=> $this->assignment_group($ID), 'class' => 'form-control m-input', 'right'  => 'all']);
                              echo'</div>
                              </div>';

                              echo '<label class="col-lg-1 col-form-label">Attribué à</label>
                                 <div class="col-lg-3">
                                    <div class="m-input-icon m-input-icon--right">';
                                       PluginServicesUser::dropdown(['value'=> $this->get_assigned_user($ID), 'class' => 'form-control m-input', 'right'  => 'all']);
                              echo'</div>
                              </div>';

                           echo'</div>';

                        echo'<div class="form-group m-form__group row d-flex justify-content-center">';

                           if ($ID && (in_array($this->fields["status"], $this->getSolvedStatusArray())
                           || in_array($this->fields["status"], $this->getClosedStatusArray()))) {


                              echo '<label class="col-lg-2 col-form-label">'.__('Resolution date').'</label>';
                                 echo '<div class="col-lg-3">';
                                 PluginServicesHtml::showDateTimeField("solvedate", ['value'      => $this->fields["solvedate"],
                                 'maybeempty' => false,
                                 'canedit'    => false]);
                                 echo'</div>';

                              echo '<label class="col-lg-2 col-form-label">'.__('Close date').'</label>';
                              echo'<div class="col-lg-3">';
                              PluginServicesHtml::showDateTimeField("closedate", ['value'      => $this->fields["closedate"],
                                 'maybeempty' => false,
                                 'canedit'    => false]);
                                 echo'</div>
                                 </div>';
                           }else{
                              echo '<label class="col-lg-1 col-form-label">'.__('Resolution date').'</label>';
                                 echo '<div class="col-lg-3">';
                                 PluginServicesHtml::showDateTimeField("solvedate", [
                                 'maybeempty' => false,
                                 'canedit'    => false]);
                                 echo'</div>';

                              echo '<label class="col-lg-1 col-form-label">'.__('Close date').'</label>';
                              echo'<div class="col-lg-3">';
                              PluginServicesHtml::showDateTimeField("closedate", [
                                 'maybeempty' => false,
                                 'canedit'    => false]);
                                 echo'</div>
                                 </div>';
                           }

                           if($this->fields["type"]==1){
                              echo'<div class="form-group m-form__group row d-flex justify-content-center">';
                                 $sla = new SLA();
                                 echo"<label class='col-lg-1 col-form-label'>";
      
                                    $tt->getBeginHiddenFieldText('time_to_resolve');
                                    if (!$ID) {
                                       printf(__('%1$s%2$s'), __('Time to resolve'), $tt->getMandatoryMark('time_to_resolve'));
                                    } else {
                                       echo "Temps de résolution";
                                    }
                                    echo $tt->getEndHiddenFieldText('time_to_resolve');
      
                                 echo"</label>";
      
                                 echo"<div class='col-lg-3 pr-0'>";
                                 echo"<span class='m-badge m-badge--warning m-badge--wide mt-3'>";
                                    $sla->showForTicket($this, SLM::TTR, $tt, $canupdate);
                                 echo"</span>";
                                 echo'</div>';


                              echo"<label class='col-lg-2 col-form-label'>";
         
                              echo "Catégorie";

                              echo"</label>";

                              echo"<div class='col-lg-3 pr-0'>";

                                 if ($canupdate || $can_requester) {
                                    $conditions = [];
                           
                                    $opt = ['value'  => $this->fields["itilcategories_id"],
                                                'entity' => $this->fields["entities_id"]];
                                    if (Session::getCurrentInterface() == "helpdesk") {
                                       $conditions['is_helpdeskvisible'] = 1;
                                    }
                                    /// Auto submit to load template
                                    if (!$ID) {
                                       $opt['on_change'] = 'this.form.submit()';
                                    }
                                    /// if category mandatory, no empty choice
                                    /// no empty choice is default value set on ticket creation, else yes
                                    if (($ID || $options['itilcategories_id'])
                                       && $tt->isMandatoryField("itilcategories_id")
                                       && ($this->fields["itilcategories_id"] > 0)) {
                                       $opt['display_emptychoice'] = false;
                                    }
                           
                                    switch ($this->fields["type"]) {
                                       case self::INCIDENT_TYPE :
                                          $conditions['is_incident'] = 1;
                                          break;
                           
                                       case self::DEMAND_TYPE :
                                          $conditions['is_request'] = 1;
                                          break;
                           
                                       default :
                                          break;
                                    }
                                    echo "<span id='show_category_by_type'>";
                                    $opt['condition'] = $conditions;
                                    $opt['readonly'] = true;
                                    ITILCategory::dropdown($opt);
                                    echo "</span>";
                                 } else {
                                    echo Dropdown::getDropdownName("glpi_itilcategories", $this->fields["itilcategories_id"]);
                                 }

                              echo'</div>';


                              echo'</div>';

                           }
                        
                        echo '<div class="form-group m-form__group row d-flex justify-content-center">';


                           echo"<label class='col-lg-1 col-form-label'>";
                              $tt->getBeginHiddenFieldText('content');
                              printf(__('%1$s%2$s'), __('Description'), $tt->getMandatoryMark('content'));
                              if ($canupdate || $can_requester) {
                                 $content = Toolbox::unclean_cross_side_scripting_deep(Html::entity_decode_deep($this->fields['content']));
                                 Html::showTooltip(nl2br(Html::Clean($content)));
                              }
                              echo $tt->getEndHiddenFieldText('content');
                           echo"</label>";

                           echo"<div class='col-lg-7 pr-0'>";
                              echo $tt->getBeginHiddenFieldValue('content');
                              $rand       = mt_rand();
                              $rand_text  = mt_rand();
                              $rows       = 5;
                              $content_id = "content$rand";
                        
                              $content = $this->fields['content'];
                              if (!isset($options['template_preview'])) {
                                 $content = Html::cleanPostForTextArea($content);
                              }
                        
                              $content = Html::setRichTextContent(
                                 $content_id,
                                 $content,
                                 $rand,
                                 !$canupdate
                              );
                        
                              echo "<div id='content$rand_text'>";
                              if ($canupdate || $can_requester) {
                                 $uploads = [];
                                 if (isset($this->input['_content'])) {
                                    $uploads['_content'] = $this->input['_content'];
                                    $uploads['_tag_content'] = $this->input['_tag_content'];
                                 }
                                 Html::textarea([
                                    'name'            => 'content',
                                    'filecontainer'   => 'content_info',
                                    'editor_id'       => $content_id,
                                    'required'        => $tt->isMandatoryField('content'),
                                    'rows'            => $rows,
                                    'enable_richtext' => true,
                                    'value'           => $content,
                                    'uploads'         => $uploads,
                                 ]);
                                 echo "</div>";
                              } else {
                                 echo Toolbox::getHtmlToDisplay($content);
                              }
                              echo $tt->getEndHiddenFieldValue('content', $this);
                           echo"</div>";
                           
                        echo"</div>";

                        echo"</form>";

                        $rand = mt_rand();
                        $this->showTimelineFormMiddle($rand);

                        echo"</div>";
                        
                     echo'</div>
                     <div class="m-portlet__foot m-portlet__no-border bg-light">
                        <div class="m-form__actions m-form__actions--solid">
                           <div class="row">
                              <div  class="col-lg-12 px-0">
                                 <ul class="nav nav-tabs mb-0" role="tablist">
                                    <li class="nav-item">
                                       <a style="font-size: 14px !important;"  class="nav-link active show" data-toggle="tab"  href="#follow_item">
                                          <i class="far fa-comment"></i>'.__("Followup").'
                                       </a>
                                    </li>
                                    <li class="nav-item">
                                       <a style="font-size: 14px !important;" class="nav-link" data-toggle="tab" href="#task_item">
                                          <i class="far fa-check-square"></i>'.__('Task').'
                                       </a>
                                    </li>
                                    <li class="nav-item ml-0">
                                       <a style="font-size: 14px !important;" class="nav-link" data-toggle="tab" href="#doc_item">
                                       <i class="fa fa-paperclip"></i>'.__("Documents").'
                                       </a>
                                    </li>';

                                    if($this->fields['type']==2){
                                       echo '<li class="nav-item ml-0">
                                          <a style="font-size: 14px !important;" class="nav-link" data-toggle="tab" href="#validation_item">
                                          <i class="far fa-thumbs-up"></i>'.__("Validations").'
                                          </a>
                                       </li>';
                                    }
                                    if($this->fields['type']==2){
                                       echo '<li class="nav-item ml-0">
                                          <a style="font-size: 14px !important;" class="nav-link" data-toggle="tab" href="#variables_item">
                                          <i class=""></i>'.__("Variables").'
                                          </a>
                                       </li>';
                                    }

                                 echo'</ul>';

                                 // <div class="tab-content bg-white">
                                 //    <div style="font-size: 14px !important;" class="tab-pane active" id="m_tabs_2_1" role="tabpanel">
                                 //          <div id="viewitem' . $this->fields['id'] . ''.$rand.'">
                                          
                                 //          </div>';

                                 //   echo'</div>
                                 //    <div style="font-size: 14px !important;" class="tab-pane" id="m_tabs_2_2" role="tabpanel">

                                 //    </div>
                                 // </div>
                                 $rand = mt_rand();
                                 //$this->showTimelineFormNav($rand);
                                 echo "<div class='tab-content' >";

                                   // follow up
                                    echo'<div id="follow_item" style="margin-top: -1px;" class="tab-pane active bg-white">';
                                       echo'<span class="mt-3">';
                                          $this->showTimelineFormNav($rand);
                                       echo'</span>';
                                       $this->showTimelineFollow($rand);
                                    echo'</div>';


                                    // Task
                                    echo'<div id="task_item" class="tab-pane bg-white">';
                                       echo'<span class="mt-3">';
                                          $this->showTimelineFormTask($rand);
                                       echo'</span>';
                                       $this->showTimelineTask($rand);
                                    echo'</div>';

                                    // Docs
                                    echo'<div id="doc_item" style="margin-top: -1px;" class="tab-pane bg-white">';
                                       echo'<div class="d-flex justify-content-end">';
                                       echo "<button type='button' class='btn btn-secondary active btn-md m-2' data-toggle='modal' data-target='#viewitem_docs'>Nouveau</button>";
                                       
                                       echo"<div class='modal fade' id='viewitem_docs' tabindex='-1' role='dialog' aria-labelledby='viewitem_docs' aria-hidden='true' style='display: none;'>
                                             <div class='modal-dialog modal-lg' role='document'>
                                                <div class='modal-content'>
                                                <form method='post' name='form_ticket' enctype='multipart/form-data' action='".$CFG_GLPI['root_doc']."/plugins/services/inc/document.form.php'>
                                                   <div class='modal-header'>
                                                      <h5 class='modal-title'>Ajouter une nouvelle tâche</h5>
                                                      <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                                                         <span aria-hidden='true'>×</span>
                                                      </button>
                                                   </div>
                                                   <div class='modal-body' id='viewitem_task'>";
                                                   echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);

                                                   $ID = $this->getID();

                                                   echo "<input type='hidden' name='entities_id' value='".$this->fields['entities_id']."'>";
                                                   echo "<input type='hidden' name='is_recursive' value='".$this->isRecursive()."'>";
                                                   echo "<input type='hidden' name='itemtype' value='".$this->getType()."'>";
                                                   echo "<input type='hidden' name='items_id' value='".$ID."'>";
                                                   echo "<input type='hidden' name='tickets_id' value='".$ID."'>";

                                                   Html::file(); 
                                                   echo"</div>
                                                   <div class='modal-footer'>
                                                      <button type='button' class='btn btn-secondary btn-md' data-dismiss='modal'>Fermer</button>
                                                      <button type='submit' class='btn btn-primary btn-md'>Enregistrer</button>
                                                   </div>
                                                </form>
                                                </div>
                                             </div>
                                          </div>";

                                       echo'</div>';
                                       $this->showTimelineDocs($rand);
                                    
                                    echo'</div>';

                                    echo'<div id="validation_item" class="tab-pane bg-white">';
                                       echo'<div class="mt-3">';
                                          $ticketValidation = new TicketValidation();
                                          $ticketValidation::displayTabContentForItemNew($item);
                                       echo'</div>';
                                    echo'</div>';

                                    // variables
                                    echo'<div id="variables_item" class="tab-pane bg-white">';
                                       echo'<span class="mt-3">';
                                          //$this->get_variables($ID);
                                       echo'</span>';
                                    echo'</div>';
                                    //end variable

                                 echo"</div>";
                           echo'</div>
                           </div>
                        </div>
                     </div>
                  

                  <!--end::Form-->
               </div>';
  
  
         if (!$options['template_preview']) {
           Html::closeForm();
         }
  
        return true;
     }
     function get_variables($ID){
      global $DB;
      $result = [];
      $iterator = $DB->request([
          'SELECT' => [
              'glpi_plugin_services_variables.id',
              'glpi_plugin_ywz_variable_items.ticket',
              'glpi_plugin_ywz_variable_items.variable',
              'glpi_plugin_ywz_variable_items.value',
              'glpi_plugin_ywz_variable_items.item',
              'glpi_plugin_services_variables.name',
              'glpi_plugin_services_variables.display_name',
              'glpi_plugin_services_variables.type',
          ],
          'FROM'      => 'glpi_plugin_ywz_variable_items',
          'INNER JOIN' => [
              'glpi_plugin_services_variables' => [
                  'ON' => [
                      'glpi_plugin_services_variables' => 'id',
                      'glpi_plugin_ywz_variable_items'    => 'variable'
                  ]
              ]
              ],
          'WHERE'     => [
              'glpi_plugin_ywz_variable_items.ticket' => $ID
          ],
          'ORDER'    => 'id ASC'
       ]);

      echo     '<div class="m-content">
                      <div class="form-group m-form__group row">';    

                          while ($data = $iterator->next()) {
                      
                              $id=$data['id'];
                              $name=$data['name'];
                              $displayName=$data['display_name'];
                              $value = stripslashes($data['value']);
                              $type=$data['type'];
                              if ($type=='Checkbox') {
                                  echo "<div class='col-lg-12 col-xl-12 m-form__group-sub'>
                                          <div class='m-checkbox-inline'>
                                          <label class='m-checkbox m-checkbox--solid m-checkbox--brand' for='$name'>
                                              <input value=\"".$value." \" disabled type='checkbox' id='$name' name='".$name."_isitemvariable-$id' > $displayName
                                              <span></span>
                                          </label>
                                          </div>
                                      </div>";
                              }else if ($type=='choice') {
                                  echo"<div class='col-xl-6 col-lg-6'>
                                      <label class='col-form-label label-select'>$displayName:</label>
                                      <select disabled class='form-control' name='".$name."_isitemvariable-$id'>";
                                      echo "<option value=\"".$value." \">$value</option>"; 
                                  echo"</select>
                                  </div>";

                              }else if ($type=='Reference') {
                                  echo"<div class='col-xl-6 col-lg-6'>
                                          <label class='form-control-label label-select'>$displayName:</label>
                                          <select  disabled class='form-control' name='".$name."_isitemvariable-$id'>";

                                          echo "<option value=\"".$value." \">$name</option>"; 
                                  echo"</select>
                                      </div>";
                                  
                              }else if ($type=='File') {
                                  echo"<div class='col-xl-6 col-lg-6'>
                                              <label class='col-form-label'>$displayName:</label>
                                              <input value=\"".$value." \" disabled type='file' id='$id' name='".$name."_isitemvariable-$id' class='form-control m-input'>
                                      </div>";
                                  }elseif ($type=='Date') {
                                      echo"<div class='col-xl-6 col-lg-6'>
                                                  <label class='col-form-label'>$displayName:</label>
                                                  <input value=\"".$value." \" disabled type='date' id='$id' name='".$name."_isitemvariable-$id' class='form-control m-input'>
                                              </div>";
                                  }elseif ($type=='Email') {
                                      echo"<div class='col-xl-6 col-lg-6'>
                                                  <label class='col-form-label'>$displayName:</label>
                                                  <input value=\"".$value." \" disabled type='email' id='$id' name='".$name."_isitemvariable-$id' class='form-control m-input'>
                                              </div>";
                                  }elseif ($type=='Number') {
                                      echo"<div class='col-xl-6 col-lg-6'>
                                                  <label class='col-form-label'>$displayName:</label>
                                                  <input value=\"".$value." \" disabled type='number' id='$id' name='".$name."_isitemvariable-$id' class='form-control m-input'>
                                              </div>";
                                  }else {
                                      echo"<div class='col-xl-6 col-lg-6'>
                                                  <label class='col-form-label'>$displayName:</label>
                                                  <input value=\"".$value." \" disabled type='text' id='$id' name='".$name."_isitemvariable-$id' class='form-control m-input'>
                                          </div>";
                                  }

                              }

                      echo"</div>
                  </div>";
   
     }


   static function assignment_group($ID){

      global $DB;

      $result = [];

      $iterator = $DB->request([
         'SELECT' => [
         'glpi_groups_tickets.groups_id as groups_id',
         ],
         'FROM'      => 'glpi_tickets',
         'INNER JOIN' => [
            'glpi_groups_tickets' => [
               'ON' => [
                  'glpi_groups_tickets' => 'tickets_id',
                  'glpi_tickets'    => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_tickets.type' => 1,
            'glpi_tickets.id' => $ID,
         ],
      ]);

      $assignment_group = "";
      if ($data = $iterator->next()) {
         $assignment_group = $data["groups_id"];
      }
       return $assignment_group;
   }
   


   function showDocumentInputFile() {
      $colsize1 = 50;
      $tt = $this->getITILTemplateToUse();
         // View files added
         echo "<tr class='tab_bg_1'>";
         // Permit to add doc when creating a ticke
         echo "<td colspan='3'>";
         // Do not set values
         echo $tt->getEndHiddenFieldValue('_documents_id');
         if ($tt->isPredefinedField('_documents_id')) {
            if (isset($options['_documents_id'])
               && is_array($options['_documents_id'])
               && count($options['_documents_id'])) {

               echo "<span class='b'>".__('Default documents:').'</span>';
               echo "<br>";
               $doc = new Document();
               foreach ($options['_documents_id'] as $key => $val) {
                  if ($doc->getFromDB($val)) {
                     echo "<input type='hidden' name='_documents_id[$key]' value='$val'>";
                     echo "- ".$doc->getNameID()."<br>";
                  }
               }
            }
         }
         if (!$tt->isHiddenField('_documents_id')) {
            $uploads = [];
            if (isset($this->input['_filename'])) {
               $uploads['_filename'] = $this->input['_filename'];
               $uploads['_tag_filename'] = $this->input['_tag_filename'];
            }
            Html::file([
               'filecontainer' => 'fileupload_info_ticket',
               // 'editor_id'     => $content_id,
               'showtitle'     => false,
               'multiple'      => true,
               'uploads'       => $uploads,
            ]);
         }
         echo "</td>";
         echo "</tr>";
   }

   static function canCreate() {
      return Session::haveRight(self::$rightname, CREATE);
  }

  static function canView() {
      return Session::haveRight(self::$rightname, READ);
  }


   function showImpactUrgencePriority($ID, $options = []) {

      self::forceTable("glpi_tickets");

      global $CFG_GLPI;

      if (isset($options['_add_fromitem']) && isset($options['itemtype'])) {
         $item = new $options['itemtype'];
         $item->getFromDB($options['items_id'][$options['itemtype']][0]);
         $options['entities_id'] = $item->fields['entities_id'];
      }

      $default_values = self::getDefaultValues();

      // Restore saved value or override with page parameter
      $saved = $this->restoreInput();  

      foreach ($default_values as $name => $value) {
         if (!isset($options[$name])) {
            if (isset($saved[$name])) {
               $options[$name] = $saved[$name];
            } else {
               $options[$name] = $value;
            }
         }
      }

      if (isset($options['content'])) {
         // Clean new lines to be fix encoding
         $order              = ['\\r', '\\n', "\\'", '\\"', "\\\\"];
         $replace            = ["", "", "'", '"', "\\"];
         $options['content'] = str_replace($order, $replace, $options['content']);
      }
      if (isset($options['name'])) {
         $order           = ["\\'", '\\"', "\\\\"];
         $replace         = ["'", '"', "\\"];
         $options['name'] = str_replace($order, $replace, $options['name']);
      }

      if (!isset($options['_skip_promoted_fields'])) {
         $options['_skip_promoted_fields'] = false;
      }

      if (!$ID) {
         // Override defaut values from projecttask if needed
         if (isset($options['_projecttasks_id'])) {
            $pt = new ProjectTask();
            if ($pt->getFromDB($options['_projecttasks_id'])) {
               $options['name'] = $pt->getField('name');
               $options['content'] = $pt->getField('name');
            }
         }
         // Override defaut values from followup if needed
         if (isset($options['_promoted_fup_id']) && !$options['_skip_promoted_fields']) {
            $fup = new ITILFollowup();
            if ($fup->getFromDB($options['_promoted_fup_id'])) {
               $options['content'] = $fup->getField('content');
               $options['_users_id_requester'] = $fup->fields['users_id'];
               $options['_link'] = [
                  'link'         => Ticket_Ticket::SON_OF,
                  'tickets_id_2' => $fup->fields['items_id']
               ];
            }
            //Allow overriding the default values
            $options['_skip_promoted_fields'] = true;
         }
      }

      // Check category / type validity
      if ($options['itilcategories_id']) {
         $cat = new ITILCategory();
         if ($cat->getFromDB($options['itilcategories_id'])) {
            switch ($options['type']) {
               case self::INCIDENT_TYPE :
                  if (!$cat->getField('is_incident')) {
                     $options['itilcategories_id'] = 0;
                  }
                  break;

               case self::DEMAND_TYPE :
                  if (!$cat->getField('is_request')) {
                     $options['itilcategories_id'] = 0;
                  }
                  break;

               default :
                  break;
            }
         }
      }

      // Default check
      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE, $options);
      }

      if (!$ID) {
         $this->userentities = [];
         if ($options["_users_id_requester"]) {
            //Get all the user's entities
            $requester_entities = Profile_User::getUserEntities($options["_users_id_requester"], true,
                                                          true);
            $user_entities = $_SESSION['glpiactiveentities'];
            $this->userentities = array_intersect($requester_entities, $user_entities);
         }
         $this->countentitiesforuser = count($this->userentities);

         if (($this->countentitiesforuser > 0)
            && !in_array($this->fields["entities_id"], $this->userentities)) {
            // If entity is not in the list of user's entities,
            // then use as default value the first value of the user's entites list
            $this->fields["entities_id"] = $this->userentities[0];
            // Pass to values
            $options['entities_id']       = $this->userentities[0];
         }
      }

      if ($options['type'] <= 0) {
         $options['type'] = Entity::getUsedConfig('tickettype', $options['entities_id'], '',
                                                Ticket::INCIDENT_TYPE);
      }

      if (!isset($options['template_preview'])) {
         $options['template_preview'] = 0;
      }

      if (!isset($options['_promoted_fup_id'])) {
         $options['_promoted_fup_id'] = 0;
      }

      // Load template if available :
      $tt = $this->getITILTemplateToUse(
         $options['template_preview'],
         $this->fields['type'],
         ($ID ? $this->fields['itilcategories_id'] : $options['itilcategories_id']),
         ($ID ? $this->fields['entities_id'] : $options['entities_id'])
      );

      // Predefined fields from template : reset them
      if (isset($options['_predefined_fields'])) {
         $options['_predefined_fields']
                        = Toolbox::decodeArrayFromInput($options['_predefined_fields']);
      } else {
         $options['_predefined_fields'] = [];
      }

      // Store predefined fields to be able not to take into account on change template
      // Only manage predefined values on ticket creation
      $predefined_fields = [];
      $tpl_key = $this->getTemplateFormFieldName();
      if (!$ID) {

         if (isset($tt->predefined) && count($tt->predefined)) {
            foreach ($tt->predefined as $predeffield => $predefvalue) {
               if (isset($default_values[$predeffield])) {
                  // Is always default value : not set
                  // Set if already predefined field
                  // Set if ticket template change
                  if (((count($options['_predefined_fields']) == 0)
                       && ($options[$predeffield] == $default_values[$predeffield]))
                      || (isset($options['_predefined_fields'][$predeffield])
                          && ($options[$predeffield] == $options['_predefined_fields'][$predeffield]))
                      || (isset($options[$tpl_key])
                          && ($options[$tpl_key] != $tt->getID()))
                      // user pref for requestype can't overwrite requestype from template
                      // when change category
                      || (($predeffield == 'requesttypes_id')
                     && empty($saved))) {

                     // Load template data
                     $options[$predeffield]            = $predefvalue;
                     $this->fields[$predeffield]      = $predefvalue;
                     $predefined_fields[$predeffield] = $predefvalue;
                  }
               }
            }
            // All predefined override : add option to say predifined exists
            if (count($predefined_fields) == 0) {
               $predefined_fields['_all_predefined_override'] = 1;
            }

         } else { // No template load : reset predefined values
            if (count($options['_predefined_fields'])) {
               foreach ($options['_predefined_fields'] as $predeffield => $predefvalue) {
                  if ($options[$predeffield] == $predefvalue) {
                     $options[$predeffield] = $default_values[$predeffield];
                  }
               }
            }
         }
      }
      // Put ticket template on $options for actors
      $options[str_replace('s_id', '', $tpl_key)] = $tt;

      // check right used for this ticket
      $canupdate     = !$ID
                        || (Session::getCurrentInterface() == "central"
                           && $this->canUpdateItem());
      $can_requester = $this->canRequesterUpdateItem();
      $canpriority   = Session::haveRight(self::$rightname, self::CHANGEPRIORITY);
      $canassign     = $this->canAssign();
      $canassigntome = $this->canAssignTome();

      if ($ID && in_array($this->fields['status'], $this->getClosedStatusArray())) {
         $canupdate = false;
         // No update for actors
         $options['_noupdate'] = true;
      }

      $showuserlink              = 0;
      if (Session::haveRight('user', READ)) {
         $showuserlink = 1;
      }

      if ($options['template_preview']) {
         // Add all values to fields of tickets for template preview
         foreach ($options as $key => $val) {
            if (!isset($this->fields[$key])) {
               $this->fields[$key] = $val;
            }
         }
      }

      // In percent
      $colsize1 = '13';
      $colsize2 = '29';
      $colsize3 = '13';
      $colsize4 = '45';

      echo'<div class="row custom_select d-flex justify-content-center">';
         echo '<div class="col-xl-4">';

            echo "<div class='form-group'>
            <label for='description'>";
               printf(__('%1$s%2$s'), __('Urgency'), $tt->getMandatoryMark('urgency'));
            echo"</label>";
            if ($canupdate || $can_requester) {
               echo $tt->getBeginHiddenFieldValue('urgency');
               $idurgency = self::dropdownUrgency([
                  'value' => $this->fields["urgency"],
                  'class' => 'form-control m-input m-input--square'
                  ]);
               echo $tt->getEndHiddenFieldValue('urgency', $this);
      
            } else {
               $idurgency = "value_urgency".mt_rand();
               echo "<input id='$idurgency' type='hidden' style='display:block' class='form-control m-input m-input--square' name='urgency' value='".
                     $this->fields["urgency"]."'>";
               echo $tt->getBeginHiddenFieldValue('urgency');
               echo parent::getUrgencyName($this->fields["urgency"]);
               echo $tt->getEndHiddenFieldValue('urgency', $this);
            }
            echo"</div>";

         echo'</div>';   
         
         echo '<div class="col-xl-4">';
         echo "<div class='form-group'>
                  <label for='description'>";
                   printf(__('%1$s%2$s'), __('Impact'), $tt->getMandatoryMark('impact'));
            echo"</label>";
            if ($canupdate) {
               $idimpact = self::dropdownImpact(['value' => $this->fields["impact"], 'disabled'=> true]);
            } else {
               $idimpact = "value_impact".mt_rand();
               echo "<input disabled='true' id='$idimpact' type='hidden' style='display:block' name='impact' value='".$this->fields["impact"]."'>";
               echo parent::getImpactName($this->fields["impact"]);
            }
         echo"</div>";
         echo'</div>'; 

         echo'</div>'; 
         
         echo'<div class="row custom_select d-flex justify-content-center">';

         echo '<div class="col-xl-4">';

         echo "<div class='form-group'>
         <label for='description'>";
            printf(__('%1$s%2$s'), __('Priority'), $tt->getMandatoryMark('priority'));
         echo" : </label> ";

         // $idajax = 'change_priority_' . mt_rand();

         // $idpriority = parent::dropdownPriority([ 'readonly' => true, 'name' => 'priority', 'value'  => $this->fields["priority"], 'withmajor' => true]);
         // $idpriority = 'dropdown_priority'.$idpriority;
         // echo "&nbsp;<span id='$idajax' style='display:none'></span>";


         $idajax = 'change_priority_' . mt_rand();

         $idpriority = 0;
         echo $tt->getBeginHiddenFieldValue('priority');
         echo "<span class='badge badge-pill badge-info'><span id='$idajax'>".parent::getPriorityName($this->fields["priority"])."</span></span>";
         echo "<input id='$idajax' type='hidden' name='priority' value='".$this->fields["priority"]."'>";
         echo '<span class="badge badge-pill badge-info">'.$tt->getEndHiddenFieldValue('priority', $this).'</span>';

      echo"</div>";

         echo'</div>'; 

         echo '<div class="col-xl-4">';
         echo'</div>';

      echo'</div>';   


      if ($canupdate || $can_requester) {
         $params = ['urgency'  => '__VALUE0__',
                    'impact'   => '__VALUE1__',
                    'priority' => $idpriority];
         Ajax::updateItemOnSelectEvent(['dropdown_urgency'.$idurgency,
                                             'dropdown_impact'.$idimpact],
                                       $idajax,
                                       $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      }
   
   }    


   function get_assigned_user($ID){

      global $DB;
  
      $result = [];

      $iterator = $DB->request([
         'SELECT' => [
         'glpi_tickets_users.users_id as assigned_to',
         ],
         'FROM'      => 'glpi_tickets',
         'INNER JOIN' => [
            'glpi_tickets_users' => [
               'ON' => [
                  'glpi_tickets_users' => 'tickets_id',
                  'glpi_tickets'    => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_tickets_users.users_id' => Session::getLoginUserID(),
            'glpi_tickets.id' => $ID,
            'glpi_tickets.type' => 1,
            'glpi_tickets_users.type' => 2
         ],
      ]);

      $assigned_to = "";
      if ($data = $iterator->next()) {
         $assigned_to = $data["assigned_to"];
      }

      return $assigned_to;
   }

   static function dropdownImpact(array $options = []) {
      global $CFG_GLPI;

      $p = [
         'name'     => 'impact',
         'value'    => 0,
         'showtype' => 'normal',
         'display'  => true,
         'class'    => 'form-control m-select2 select2-hidden-accessible'
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $values = [];

      if ($p['showtype'] == 'search') {
         $values[0]  = static::getImpactName(0);
         $values[-5] = static::getImpactName(-5);
         $values[-4] = static::getImpactName(-4);
         $values[-3] = static::getImpactName(-3);
         $values[-2] = static::getImpactName(-2);
         $values[-1] = static::getImpactName(-1);
      }

      if (isset($CFG_GLPI[static::IMPACT_MASK_FIELD])) {
         if (($p['showtype'] == 'search')
             || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1<<5))) {
            $values[5]  = static::getImpactName(5);
         }

         if (($p['showtype'] == 'search')
             || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1<<4))) {
            $values[4]  = static::getImpactName(4);
         }

         $values[3]  = static::getImpactName(3);

         if (($p['showtype'] == 'search')
             || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1<<2))) {
            $values[2]  = static::getImpactName(2);
         }

         if (($p['showtype'] == 'search')
             || ($CFG_GLPI[static::IMPACT_MASK_FIELD] & (1<<1))) {
            $values[1]  = static::getImpactName(1);
         }
      }

      return PluginServicesDropdown::showFromArray($p['name'], $values, $p);
   }

   function showList() {
      global $CFG_GLPI;
      $form_url = $CFG_GLPI['root_doc']."/plugins/services/interface/front/timecard/form";
      echo "<div class='m-portlet m-portlet--rounded'>
          <div class='m-portlet__head'>
              <div class='m-portlet__head-caption'>
                  <div class='m-portlet__head-title'>
                      <h3 class='m-portlet__head-text'>
                          Liste timecards
                      </h3>
                  </div>
              </div>
              <div class='m-portlet__head-tools'>
                  <ul class='m-portlet__nav'>
                      <li class='m-portlet__nav-item'>
                          <a href='".$form_url."' class='m-portlet__nav-link m-portlet__nav-link--icon'>
                              <i class='flaticon-plus'></i>
                          </a>
                      </li>
                  </ul>
              </div>
          </div>
      </div>";
      PluginServicesSearch::show("PluginServicesTicket");
  }

   
   function showTimelineFormNav($rand) {

      global $CFG_GLPI, $DB;

      $objType = "Ticket";
      $foreignKey = "tickets_id";

      //check sub-items rights
      $tmp = [$foreignKey => $this->getID()];
      $fupClass = "ITILFollowup";
      $fup = new $fupClass;
      $fup->getEmpty();
      $fup->fields['itemtype'] = $objType;
      $fup->fields['items_id'] = $this->getID();

      $taskClass = "TicketTask";
      $task = new $taskClass;

      $canadd_fup = $fup->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                        array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                         array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_document = $canadd_fup || $this->canAddItem('Document') && !in_array($this->fields["status"],
                         array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_solution = $objType::canUpdate() && $this->canSolve() && !in_array($this->fields["status"], $this->getSolvedStatusArray());

      $validation_class = $objType.'Validation';
      $canadd_validation = false;
      if (class_exists($validation_class)) {
         $validation = new $validation_class();
         $canadd_validation = $validation->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
               array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      }

      // javascript function for add and edit items
      echo "<script type='text/javascript' >
      function change_task_state(tasks_id, target) {
         $.post('".$CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php',
                {'action':     'change_task_state',
                  'tasks_id':   tasks_id,
                  'parenttype': 'PluginServicesTicket',
                  '$foreignKey': ".$this->fields['id']."
                })
                .done(function(response) {
                  $(target).removeClass('state_1 state_2')
                           .addClass('state_'+response.state)
                           .attr('title', response.label);
                });
      }

      function viewEditSubitem" . $this->fields['id'] . "$rand(e, itemtype, items_id, o, domid) {
               domid = (typeof domid === 'undefined')
                         ? 'viewitem".$this->fields['id'].$rand."'
                         : domid;
               var target = e.target || window.event.srcElement;
               if (target.nodeName == 'a') return;
               if (target.className == 'read_more_button') return;

               var _eltsel = '[data-uid='+domid+']';
               var _elt = $(_eltsel);
               _elt.addClass('edited');
               $(_eltsel + ' .displayed_content').hide();
               $(_eltsel + ' .cancel_edit_item_content').show()
                                                        .click(function() {
                                                            $(this).hide();
                                                            _elt.removeClass('edited');
                                                            $(_eltsel + ' .edit_item_content').empty().hide();
                                                            $(_eltsel + ' .displayed_content').show();
                                                        });
               $(_eltsel + ' .edit_item_content').show()
                                                 .load('".$CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php',
                                                       {'action'    : 'viewsubitem',
                                                        'type'      : itemtype,
                                                        'parenttype': '$objType',
                                                        '$foreignKey': ".$this->fields['id'].",
                                                        'id'        : items_id
                                                       });
      };
      </script>";

      // if (!$canadd_fup && !$canadd_task && !$canadd_document && !$canadd_solution && !$this->canReopen()) {
      //    return false;
      // }

      echo "<script type='text/javascript' >\n";
      echo "function viewAddSubitem" . $this->fields['id'] . "$rand(itemtype) {\n";
      $params = ['action'     => 'viewsubitem',
                      'type'       => 'itemtype',
                      'parenttype' => $objType,
                      $foreignKey => $this->fields['id'],
                      'id'         => -1];
      if (isset($_GET['load_kb_sol'])) {
         $params['load_kb_sol'] = $_GET['load_kb_sol'];
      }
      $out = Ajax::updateItemJsCode("viewitem" . $this->fields['id'] . "$rand",
                                    $CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php",
                                    $params, "", false);
      echo str_replace("\"itemtype\"", "itemtype", $out);
      echo "$('#approbation_form$rand').remove()";
      echo "};";

      if (isset($_GET['load_kb_sol'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('Solution');";
      }

      if (isset($_GET['_openfollowup'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('ITILFollowup')";
      }
      echo "</script>\n";

      //show choices
      echo "<div class='timeline_form pl-0'>";
      echo "<ul class='nav nav-tabs mb-0'>";

      // if ($canadd_fup || $canadd_task || $canadd_document || $canadd_solution) {
      //    echo "<h2>"._sx('button', 'Add')." : </h2>";
      // }


      // if ($canadd_fup || $canadd_task || $canadd_document || $canadd_solution) {
      //    echo "<h2>"._sx('button', 'Add')." : </h2>";
      // }
      // if ($canadd_fup) {
      //    echo "<li data-toggle='modal'  data-target='#viewitem_modal' class='followup' onclick='".
      //         "javascript:viewAddSubitem".$this->fields['id']."$rand(\"ITILFollowup\");'>"
      //         . "<i class='far fa-comment'></i>".__("Followup")."</li>";
      // }

      // if ($canadd_task) {
      //    echo "<li class='task' onclick='".
      //         "javascript:viewAddSubitem".$this->fields['id']."$rand(\"$taskClass\");'>"
      //         ."<i class='far fa-check-square'></i>".__("Task")."</li>";
      // }
      // if ($canadd_document) {
      //    echo "<li class='document' onclick='".
      //         "javascript:viewAddSubitem".$this->fields['id']."$rand(\"Document_Item\");'>"
      //         ."<i class='fa fa-paperclip'></i>".__("Document")."</li>";
      // }
      // if ($canadd_validation) {
      //    echo "<li class='validation' onclick='".
      //       "javascript:viewAddSubitem".$this->fields['id']."$rand(\"$validation_class\");'>"
      //       ."<i class='far fa-thumbs-up'></i>".__("Approval")."</li>";
      // }

      if ($canadd_fup) {
         echo"<script>
         $( document ).ready(function() {viewAddSubitem".$this->fields['id']."$rand(\"ITILFollowup\");});
         </script>";
      }

      // if ($canadd_fup) {
      //    echo "<li class='nav-item'>
      //          <a style='font-size: 14px !important;' onclick='"."javascript:viewAddSubitem".$this->fields['id']."$rand(\"ITILFollowup\");' class='nav-link active show' data-toggle='tab'  href='#follow_item'>
      //          <i class='far fa-comment'></i>".__('Followup')."
      //          <script>
      //          $( document ).ready(function() {viewAddSubitem".$this->fields['id']."$rand(\"ITILFollowup\");});
      //          </script>
      //          </a>
      //       </li>";
      // }else{
      //    echo "<li class='nav-item'>
      //       <a style='font-size: 14px !important;' class='nav-link active show' data-toggle='tab'  href='#follow_item'>
      //       <i class='far fa-comment'></i>".__('Followup')."
      //       </a>
      //    </li>";
      // }


      // if ($canadd_task) {
      //    echo "<li class='nav-item ml-0'>
      //             <a style='font-size: 14px !important;' onclick='"."javascript:viewAddSubitem".$this->fields['id']."$rand(\"$taskClass\");' class='nav-link' data-toggle='tab' href='#task_item'>
      //             <i class='far fa-check-square'></i>".__("Task")."
      //             </a>
      //          </li>";
      // }else{
      //    echo "<li class='nav-item ml-0'>
      //       <a style='font-size: 14px !important;' class='nav-link' data-toggle='tab' href='#task_item'>
      //       <i class='far fa-check-square'></i>".__("Task")."
      //       </a>
      //    </li>"; 
      // }


      // if ($canadd_document) {
      //    echo "<li class='nav-item ml-0'>
      //             <a style='font-size: 14px !important;' onclick='".
      //             "javascript:viewAddSubitem".$this->fields['id']."$rand(\"Document_Item\");' class='nav-link' data-toggle='tab' href='#doc_item'>
      //             <i class='fa fa-paperclip'></i>".__('Documents')."
      //             </a>
      //          </li>";
      // }else{
      //    echo "<li class='nav-item ml-0'>
      //       <a style='font-size: 14px !important;' class='nav-link' data-toggle='tab' href='#doc_item'>
      //       <i class='fa fa-paperclip'></i>".__('Documents')."
      //       </a>
      //    </li>";  
      // }
      // if ($canadd_validation) {
      //    echo "<li class='validation' onclick='".
      //       "javascript:viewAddSubitem".$this->fields['id']."$rand(\"$validation_class\");'>"
      //       ."<i class='far fa-thumbs-up'></i>".__("Approval")."</li>";
      // }
      // if ($canadd_solution) {
      //    echo "<li>"
      //         ."<button onclick='".
      //         "javascript:viewAddSubitem".$this->fields['id']."$rand(\"Solution\");' class='btn btn-secondary active btn-md'>Résoudre</button></li>";
      // }

      // echo "<li ><button class='btn btn-secondary ml-3 active btn-md'>Sauvegarder</button></li>";

      
      Plugin::doHook('timeline_actions', ['item' => $this, 'rand' => $rand]);

      echo "</ul>"; // timeline_choices
      //echo "<div class='clear'>&nbsp;</div>";
      //total_actiontime stat
      // if (Session::getCurrentInterface() != 'helpdesk') {
      //    echo "<div class='timeline_stats'>";

      //    $taskClass  = "TicketTask";
      //    $task_table = getTableForItemType($taskClass);
      //    $foreignKey = "tickets_id";

      //    $total_actiontime = 0;

      //    $criteria = [
      //       'SELECT'   => 'actiontime',
      //       'DISTINCT' => true,
      //       'FROM'     => $task_table,
      //       'WHERE'    => [$foreignKey => $this->fields['id']]
      //    ];

      //    $iterator = $DB->request($criteria);
      //    foreach ($iterator as $req) {
      //       $total_actiontime += $req['actiontime'];
      //    }
      //    if ($total_actiontime > 0) {
      //       echo "<h3>";
      //       $total   = Html::timestampToString($total_actiontime, false);
      //       $message = sprintf(__('Total duration: %s'),
      //                          $total);
      //       echo $message;
      //       echo "</h3>";
      //    }

      //    $criteria    = [$foreignKey => $this->fields['id']];
      //    $total_tasks = countElementsInTable($task_table, $criteria);
      //    if ($total_tasks > 0) {
      //       $states = [Planning::INFO => __('Information tasks: %s %%'),
      //                  Planning::TODO => __('Todo tasks: %s %%'),
      //                  Planning::DONE => __('Done tasks: %s %% ')];
      //       echo "<h3>";
      //       foreach ($states as $state => $string) {
      //          $criteria = [$foreignKey => $this->fields['id'],
      //                       "state"     => $state];
      //          $tasks    = countElementsInTable($task_table, $criteria);
      //          if ($tasks > 0) {
      //             $percent_todotasks = Html::formatNumber((($tasks * 100) / $total_tasks));
      //             $message           = sprintf($string,
      //                                          $percent_todotasks);
      //             echo "&nbsp;";
      //             echo $message;
      //          }
      //       }
      //       echo "</h3>";
      //    }
      //    echo "</div>";
      // }
      echo "</div>"; //end timeline_form


      echo"<div class='bg-white' id='viewitem" . $this->fields['id'] . "$rand'>

         </div>";

      // echo "<div class='ajax_box' id='viewitem" . $this->fields['id'] . "$rand'></div>\n";
   }




   function showTimelineFormTask($rand) {

      global $CFG_GLPI, $DB;

      $objType = "Ticket";
      $foreignKey = "tickets_id";

      //check sub-items rights
      $tmp = [$foreignKey => $this->getID()];
      $fupClass = "ITILFollowup";
      $fup = new $fupClass;
      $fup->getEmpty();
      $fup->fields['itemtype'] = $objType;
      $fup->fields['items_id'] = $this->getID();

      $taskClass = "TicketTask";
      $task = new $taskClass;

      $canadd_fup = $fup->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                        array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                         array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_document = $canadd_fup || $this->canAddItem('Document') && !in_array($this->fields["status"],
                         array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_solution = $objType::canUpdate() && $this->canSolve() && !in_array($this->fields["status"], $this->getSolvedStatusArray());

      $validation_class = $objType.'Validation';
      $canadd_validation = false;
      if (class_exists($validation_class)) {
         $validation = new $validation_class();
         $canadd_validation = $validation->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
               array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      }

      // javascript function for add and edit items
      echo "<script type='text/javascript' >
      function change_task_state(tasks_id, target) {
         $.post('".$CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php',
                {'action':     'change_task_state',
                  'tasks_id':   tasks_id,
                  'parenttype': 'PluginServicesTicket',
                  '$foreignKey': ".$this->fields['id']."
                })
                .done(function(response) {
                  $(target).removeClass('state_1 state_2')
                           .addClass('state_'+response.state)
                           .attr('title', response.label);
                });
      }

      function viewEditSubitem" . $this->fields['id'] . "$rand(e, itemtype, items_id, o, domid) {
               domid = (typeof domid === 'undefined')
                         ? 'viewitem".$this->fields['id'].$rand."'
                         : domid;
               var target = e.target || window.event.srcElement;
               if (target.nodeName == 'a') return;
               if (target.className == 'read_more_button') return;

               var _eltsel = '[data-uid='+domid+']';
               var _elt = $(_eltsel);
               _elt.addClass('edited');
               $(_eltsel + ' .displayed_content').hide();
               $(_eltsel + ' .cancel_edit_item_content').show()
                                                        .click(function() {
                                                            $(this).hide();
                                                            _elt.removeClass('edited');
                                                            $(_eltsel + ' .edit_item_content').empty().hide();
                                                            $(_eltsel + ' .displayed_content').show();
                                                        });
               $(_eltsel + ' .edit_item_content').show()
                                                 .load('".$CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php',
                                                       {'action'    : 'viewsubitem',
                                                        'type'      : itemtype,
                                                        'parenttype': '$objType',
                                                        '$foreignKey': ".$this->fields['id'].",
                                                        'id'        : items_id
                                                       });
      };
      </script>";

      // if (!$canadd_fup && !$canadd_task && !$canadd_document && !$canadd_solution && !$this->canReopen()) {
      //    return false;
      // }

      echo "<script type='text/javascript' >\n";
      echo "function viewAddSubitem" . $this->fields['id'] . "$rand(itemtype) {\n";
      $params = ['action'     => 'viewsubitem',
                      'type'       => 'itemtype',
                      'parenttype' => $objType,
                      $foreignKey => $this->fields['id'],
                      'id'         => -1];
      if (isset($_GET['load_kb_sol'])) {
         $params['load_kb_sol'] = $_GET['load_kb_sol'];
      }
      $out = Ajax::updateItemJsCode("viewitem" . $this->fields['id'] . "$rand",
                                    $CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php",
                                    $params, "", false);
      echo str_replace("\"itemtype\"", "itemtype", $out);
      echo "$('#approbation_form$rand').remove()";
      echo "};";

      if (isset($_GET['load_kb_sol'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('Solution');";
      }

      if (isset($_GET['_openfollowup'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('ITILFollowup')";
      }
      echo "</script>\n";

      //show choices
      echo "<div class='timeline_form pl-0'>";
      echo "<div class='d-flex justify-content-end'>";

      if ($canadd_task) {
         echo "<button type='button' class='btn btn-secondary active btn-md m-2' data-toggle='modal' data-target='#viewitem_task'>Nouveau</button>
                  <div class='modal fade' id='viewitem_task' tabindex='-1' role='dialog' aria-labelledby='exampleModalLabel' aria-hidden='true' style='display: none;'>
							<div class='modal-dialog modal-lg' role='document'>
								<div class='modal-content'>
                        <form method='post' name='form_ticket' enctype='multipart/form-data' action='".$CFG_GLPI['root_doc']."/plugins/services/inc/tickettask.form.php'>
									<div class='modal-header'>
										<h5 class='modal-title'>Ajouter une nouvelle tâche</h5>
										<button type='button' class='close' data-dismiss='modal' aria-label='Close'>
											<span aria-hidden='true'>×</span>
										</button>
									</div>
									<div class='modal-body' id='viewitem_task'>";
                           echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);

                           $ID = $this->getID();

                           echo "<input type='hidden' name='tickets_id' value='$ID'>";
                        
                           $rand_text  = mt_rand();
                           $content_id = "content$rand_text";
                           $cols       = 100;
                           $rows       = 10;
                     
                           Html::textarea(['name'              => 'content',
                                           'value'             => "",
                                           'rand'              => $rand_text,
                                           'editor_id'         => $content_id,
                                           'enable_fileupload' => true,
                                           'enable_richtext'   => true,
                                           'cols'              => $cols,
                                           'rows'              => $rows]);

									echo"</div>
									<div class='modal-footer'>
										<button type='button' class='btn btn-secondary btn-md' data-dismiss='modal'>Fermer</button>
										<button type='submit' class='btn btn-primary btn-md'>Enregistrer</button>
									</div>
                        </form>
								</div>
							</div>
						</div>";
      }else{
         echo "<button type='button' class='btn btn-primary' data-toggle='modal' data-target='#m_modal_1'>Nouveau</button>"; 
      }
      

      echo '</div>';
      
      Plugin::doHook('timeline_actions', ['item' => $this, 'rand' => $rand]);

      //echo "<div class='clear'>&nbsp;</div>";
      //total_actiontime stat
      if (Session::getCurrentInterface() != 'helpdesk') {
         echo "<div style='opacity: 1;' class='timeline_stats d-flex justify-content-center'>";

         $taskClass  = "TicketTask";
         $task_table = getTableForItemType($taskClass);
         $foreignKey = "tickets_id";

         $total_actiontime = 0;

         $criteria = [
            'SELECT'   => 'actiontime',
            'DISTINCT' => true,
            'FROM'     => $task_table,
            'WHERE'    => [$foreignKey => $this->fields['id']]
         ];

         $iterator = $DB->request($criteria);
         foreach ($iterator as $req) {
            $total_actiontime += $req['actiontime'];
         }
         if ($total_actiontime > 0) {
            echo "<h3>";


            $total   = Html::timestampToString($total_actiontime, false);
            $message = sprintf(__('Total duration: %s'),
                               $total);
            echo $message;
            echo "</h3>";
         }

         $criteria    = [$foreignKey => $this->fields['id']];
         $total_tasks = countElementsInTable($task_table, $criteria);
         if ($total_tasks > 0) {
            $states = [Planning::INFO => __('Information tasks: %s %%'),
                       Planning::TODO => __('Todo tasks: %s %%'),
                       Planning::DONE => __('Done tasks: %s %% ')];
            echo "<h3>";
            foreach ($states as $state => $string) {
               $criteria = [$foreignKey => $this->fields['id'],
                            "state"     => $state];
               $tasks    = countElementsInTable($task_table, $criteria);
               if ($tasks > 0) {
                  $percent_todotasks = Html::formatNumber((($tasks * 100) / $total_tasks));
                  $message           = sprintf($string,
                                               $percent_todotasks);
                  echo "&nbsp;";
                  echo $message;
               }
            }
            echo"<div class='progress mt-3 ml-1'>
               <div class='progress-bar progress-bar-striped progress-bar-animated ' role='progressbar' aria-valuenow='75' aria-valuemin='0' aria-valuemax='100' style='width: $percent_todotasks%'></div>
            </div>";

            echo "</h3>";
         }
         echo "</div>";
      }
      echo "</div>"; //end timeline_form


      // echo"<div class='bg-white' id='viewitem" . $this->fields['id'] . "$rand'>

      //    </div>";

      // echo "<div class='ajax_box' id='viewitem" . $this->fields['id'] . "$rand'></div>\n";
   }




   function showTimelineForm($rand) {

      

      global $CFG_GLPI, $DB;

      $objType = "Ticket";
      $foreignKey = "tickets_id";

      //check sub-items rights
      $tmp = [$foreignKey => $this->getID()];
      $fupClass = "ITILFollowup";
      $fup = new $fupClass;
      $fup->getEmpty();
      $fup->fields['itemtype'] = $objType;
      $fup->fields['items_id'] = $this->getID();

      $taskClass = "TicketTask";
      $task = new $taskClass;

      $canadd_fup = $fup->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                        array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_task = $task->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
                         array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_document = $canadd_fup || $this->canAddItem('Document') && !in_array($this->fields["status"],
                         array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      $canadd_solution = $objType::canUpdate() && $this->canSolve() && !in_array($this->fields["status"], $this->getSolvedStatusArray());

      $validation_class = $objType.'Validation';
      $canadd_validation = false;
      if (class_exists($validation_class)) {
         $validation = new $validation_class();
         $canadd_validation = $validation->can(-1, CREATE, $tmp) && !in_array($this->fields["status"],
               array_merge($this->getSolvedStatusArray(), $this->getClosedStatusArray()));
      }

      // javascript function for add and edit items
      echo "<script type='text/javascript' >
      function change_task_state(tasks_id, target) {
         $.post('".$CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php',
                {'action':     'change_task_state',
                  'tasks_id':   tasks_id,
                  'parenttype': 'PluginServicesTicket',
                  '$foreignKey': ".$this->fields['id']."
                })
                .done(function(response) {
                  $(target).removeClass('state_1 state_2')
                           .addClass('state_'+response.state)
                           .attr('title', response.label);
                });
      }

      function viewEditSubitem" . $this->fields['id'] . "$rand(e, itemtype, items_id, o, domid) {
               domid = (typeof domid === 'undefined')
                         ? 'viewitem".$this->fields['id'].$rand."'
                         : domid;
               var target = e.target || window.event.srcElement;
               if (target.nodeName == 'a') return;
               if (target.className == 'read_more_button') return;

               var _eltsel = '[data-uid='+domid+']';
               var _elt = $(_eltsel);
               _elt.addClass('edited');
               $(_eltsel + ' .displayed_content').hide();
               $(_eltsel + ' .cancel_edit_item_content').show()
                                                        .click(function() {
                                                            $(this).hide();
                                                            _elt.removeClass('edited');
                                                            $(_eltsel + ' .edit_item_content').empty().hide();
                                                            $(_eltsel + ' .displayed_content').show();
                                                        });
               $(_eltsel + ' .edit_item_content').show()
                                                 .load('".$CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php',
                                                       {'action'    : 'viewsubitem',
                                                        'type'      : itemtype,
                                                        'parenttype': '$objType',
                                                        '$foreignKey': ".$this->fields['id'].",
                                                        'id'        : items_id
                                                       });
      };
      </script>";

      if (!$canadd_fup && !$canadd_task && !$canadd_document && !$canadd_solution && !$this->canReopen()) {
         return false;
      }

      echo "<script type='text/javascript' >\n";
      echo "function viewAddSubitem" . $this->fields['id'] . "$rand(itemtype) {\n";
      $params = ['action'     => 'viewsubitem',
                      'type'       => 'itemtype',
                      'parenttype' => $objType,
                      $foreignKey => $this->fields['id'],
                      'id'         => -1];
      if (isset($_GET['load_kb_sol'])) {
         $params['load_kb_sol'] = $_GET['load_kb_sol'];
      }
      $out = Ajax::updateItemJsCode("viewitem" . $this->fields['id'] . "$rand",
                                    $CFG_GLPI["root_doc"]."/plugins/services/ajax/timeline.php",
                                    $params, "", false);
      echo str_replace("\"itemtype\"", "itemtype", $out);
      echo "$('#approbation_form$rand').remove()";
      echo "};";

      if (isset($_GET['load_kb_sol'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('Solution');";
      }

      if (isset($_GET['_openfollowup'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('ITILFollowup')";
      }
      echo "</script>\n";

      //show choices
      echo "<div class='timeline_form pl-0 mt-3'>";
      echo "<ul class='m-portlet__nav'>";

      // if ($canadd_fup || $canadd_task || $canadd_document || $canadd_solution) {
      //    echo "<h2>"._sx('button', 'Add')." : </h2>";
      // }
      // if ($canadd_fup) {
      //    echo "<li data-toggle='modal'  data-target='#viewitem_modal' class='followup' onclick='".
      //         "javascript:viewAddSubitem".$this->fields['id']."$rand(\"ITILFollowup\");'>"
      //         . "<i class='far fa-comment'></i>".__("Followup")."</li>";
      // }

      // if ($canadd_task) {
      //    echo "<li class='task' onclick='".
      //         "javascript:viewAddSubitem".$this->fields['id']."$rand(\"$taskClass\");'>"
      //         ."<i class='far fa-check-square'></i>".__("Task")."</li>";
      // }
      // if ($canadd_document) {
      //    echo "<li class='document' onclick='".
      //         "javascript:viewAddSubitem".$this->fields['id']."$rand(\"Document_Item\");'>"
      //         ."<i class='fa fa-paperclip'></i>".__("Document")."</li>";
      // }
      // if ($canadd_validation) {
      //    echo "<li class='validation' onclick='".
      //       "javascript:viewAddSubitem".$this->fields['id']."$rand(\"$validation_class\");'>"
      //       ."<i class='far fa-thumbs-up'></i>".__("Approval")."</li>";
      // }
      if ($canadd_solution) {
         echo "<li>"
              ."<button type='button' onclick='".
              "javascript:viewAddSubitem".$this->fields['id']."$rand(\"Solution\");' class='btn btn-secondary active btn-md' data-toggle='modal' data-target='#resolv_modal' >Résoudre</button></li>";
      }

      echo "<li ><button type='submit' class='btn btn-secondary ml-3 active btn-md'>Sauvegarder</button></li>";

      
      Plugin::doHook('timeline_actions', ['item' => $this, 'rand' => $rand]);

      echo "</ul>"; // timeline_choices
      //echo "<div class='clear'>&nbsp;</div>";
      //total_actiontime stat
      // if (Session::getCurrentInterface() != 'helpdesk') {
      //    echo "<div class='timeline_stats'>";

      //    $taskClass  = "TicketTask";
      //    $task_table = getTableForItemType($taskClass);
      //    $foreignKey = "tickets_id";

      //    $total_actiontime = 0;

      //    $criteria = [
      //       'SELECT'   => 'actiontime',
      //       'DISTINCT' => true,
      //       'FROM'     => $task_table,
      //       'WHERE'    => [$foreignKey => $this->fields['id']]
      //    ];

      //    $iterator = $DB->request($criteria);
      //    foreach ($iterator as $req) {
      //       $total_actiontime += $req['actiontime'];
      //    }
      //    if ($total_actiontime > 0) {
      //       echo "<h3>";
      //       $total   = Html::timestampToString($total_actiontime, false);
      //       $message = sprintf(__('Total duration: %s'),
      //                          $total);
      //       echo $message;
      //       echo "</h3>";
      //    }

      //    $criteria    = [$foreignKey => $this->fields['id']];
      //    $total_tasks = countElementsInTable($task_table, $criteria);
      //    if ($total_tasks > 0) {
      //       $states = [Planning::INFO => __('Information tasks: %s %%'),
      //                  Planning::TODO => __('Todo tasks: %s %%'),
      //                  Planning::DONE => __('Done tasks: %s %% ')];
      //       echo "<h3>";
      //       foreach ($states as $state => $string) {
      //          $criteria = [$foreignKey => $this->fields['id'],
      //                       "state"     => $state];
      //          $tasks    = countElementsInTable($task_table, $criteria);
      //          if ($tasks > 0) {
      //             $percent_todotasks = Html::formatNumber((($tasks * 100) / $total_tasks));
      //             $message           = sprintf($string,
      //                                          $percent_todotasks);
      //             echo "&nbsp;";
      //             echo $message;
      //          }
      //       }
      //       echo "</h3>";
      //    }
      //    echo "</div>";
      // }
      echo "</div>"; //end timeline_form

      // echo "<div class='ajax_box' id='viewitem" . $this->fields['id'] . "$rand'></div>\n";

      echo "<div class='modal fade ajax_box' id='resolv_modal' tabindex='-1' role='dialog' aria-labelledby='exampleModalLabel' aria-hidden='true'>
         <div class='modal-dialog modal-lg' role='document'>
            <div class='modal-content'>
               <div class='modal-header'>
                  <h5 class='modal-title' id='exampleModalLabel'>Résoudre le ticket</h5>
                  <button type='button' class='close' data-dismiss='modal' aria-label='Close'>
                     <span aria-hidden='true'>×</span>
                  </button>
               </div>
               <div class='modal-body' id='viewitem" . $this->fields['id'] . "$rand'>
                  
               </div>
               <div class='modal-footer'>
                  <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
                  <button type='button' onclick='submit_form()' class='btn btn-primary'>Save changes</button>
                  <script>
                     function submit_form(){
                        alert('dzdez');
                        $('#submit_form').click();
                     }
                  </script>
               </div>
            </div>
         </div>
      </div>";
   }


   static function getSplittedSubmitButtonHtml($tickets_id, $action = "add") {

      self::forceTable("glpi_tickets");

      $locale = _sx('button', 'Add');
      if ($action == 'update') {
         $locale = _x('button', 'Save');
      }
      $ticket       = new self();
      $ticket->getFromDB($tickets_id);
      $all_status   = Ticket::getAllowedStatusArray($ticket->fields['status']);
      $rand = mt_rand();

      //<input type='submit' value='$locale' name='$action' class='x-button x-button-main'>
      $html = "<div class='x-split-button' id='x-split-button'>
               
               <button type='submit' value='$locale' name='$action' class='btn btn-secondary active btn-md'>$locale</button>
          
               <ul class='x-button-drop-menu'>";
      // foreach ($all_status as $status_key => $status_label) {
      //    $checked = "";
      //    if ($status_key == $ticket->fields['status']) {
      //       $checked = "checked='checked'";
      //    }
      //    $html .= "<li data-status='".self::getStatusKey($status_key)."'>";
      //    $html .= "<input type='radio' id='status_radio_$status_key$rand' name='_status'
      //               $checked value='$status_key'>";
      //    $html .= "<label for='status_radio_$status_key$rand'>";
      //    $html .= Ticket::getStatusIcon($status_key) . "&nbsp;";
      //    $html .= $status_label;
      //    $html .= "</label>";
      //    $html .= "</li>";
      // }
      $html .= "</ul></div>";

      $html.= "<script type='text/javascript'>$(function() {split_button();});</script>";
      return $html;
   }


   function getTimelineItems() {

      $objType = static::getType();
      $foreignKey = static::getForeignKeyField();
      $supportsValidation = $objType === "Ticket" || $objType === "Change";

      $timeline = [];

      $user = new User();

      $fupClass           = 'ITILFollowup';
      $followup_obj       = new $fupClass;
      $taskClass             = $objType."Task";
      $task_obj              = new $taskClass;
      $document_item_obj     = new Document_Item();
      if ($supportsValidation) {
         $validation_class    = $objType."Validation";
         $valitation_obj     = new $validation_class;
      }

      //checks rights
      $restrict_fup = $restrict_task = [];
      if (!Session::haveRight("followup", ITILFollowup::SEEPRIVATE)) {
         $restrict_fup = [
            'OR' => [
               'is_private'   => 0,
               'users_id'     => Session::getLoginUserID()
            ]
         ];
      }

      $restrict_fup['itemtype'] = static::getType();
      $restrict_fup['items_id'] = $this->getID();

      if ($task_obj->maybePrivate() && !Session::haveRight("task", CommonITILTask::SEEPRIVATE)) {
         $restrict_task = [
            'OR' => [
               'is_private'   => 0,
               'users_id'     => Session::getLoginUserID()
            ]
         ];
      }

      //add followups to timeline
      if ($followup_obj->canview()) {
         $followups = $followup_obj->find(['items_id'  => $this->getID()] + $restrict_fup, ['date DESC', 'id DESC']);
         foreach ($followups as $followups_id => $followup) {
            $followup_obj->getFromDB($followups_id);
            $followup['can_edit']                                   = $followup_obj->canUpdateItem();;
            $timeline[$followup['date']."_followup_".$followups_id] = ['type' => $fupClass,
                                                                            'item' => $followup,
                                                                            'itiltype' => 'Followup'];
         }
      }

      //add tasks to timeline
      if ($task_obj->canview()) {
         $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task, 'date DESC');
         foreach ($tasks as $tasks_id => $task) {
            $task_obj->getFromDB($tasks_id);
            $task['can_edit']                           = $task_obj->canUpdateItem();
            $timeline[$task['date']."_task_".$tasks_id] = ['type' => $taskClass,
                                                                'item' => $task,
                                                                'itiltype' => 'Task'];
         }
      }

      //add documents to timeline
      $document_obj   = new Document();
      $document_items = $document_item_obj->find([
         $this->getAssociatedDocumentsCriteria(),
         'timeline_position'  => ['>', self::NO_TIMELINE]
      ]);
      foreach ($document_items as $document_item) {
         $document_obj->getFromDB($document_item['documents_id']);

         $item = $document_obj->fields;
         $item['date']     = $document_item['date_creation'];
         // #1476 - set date_mod and owner to attachment ones
         $item['date_mod'] = $document_item['date_mod'];
         $item['users_id'] = $document_item['users_id'];
         $item['documents_item_id'] = $document_item['id'];

         $item['timeline_position'] = $document_item['timeline_position'];

         $timeline[$document_item['date_creation']."_document_".$document_item['documents_id']]
            = ['type' => 'Document_Item', 'item' => $item];
      }

      $solution_obj = new ITILSolution();
      $solution_items = $solution_obj->find([
         'itemtype'  => static::getType(),
         'items_id'  => $this->getID()
      ]);
      foreach ($solution_items as $solution_item) {
         // fix trouble with html_entity_decode who skip accented characters (on windows browser)
         $solution_content = preg_replace_callback("/(&#[0-9]+;)/", function($m) {
            return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
         }, $solution_item['content']);

         $timeline[$solution_item['date_creation']."_solution_" . $solution_item['id'] ] = [
            'type' => 'Solution',
            'item' => [
               'id'                 => $solution_item['id'],
               'content'            => Toolbox::unclean_cross_side_scripting_deep($solution_content),
               'date'               => $solution_item['date_creation'],
               'users_id'           => $solution_item['users_id'],
               'solutiontypes_id'   => $solution_item['solutiontypes_id'],
               'can_edit'           => $objType::canUpdate() && $this->canSolve(),
               'timeline_position'  => self::TIMELINE_RIGHT,
               'users_id_editor'    => $solution_item['users_id_editor'],
               'date_mod'           => $solution_item['date_mod'],
               'users_id_approval'  => $solution_item['users_id_approval'],
               'date_approval'      => $solution_item['date_approval'],
               'status'             => $solution_item['status']
            ]
         ];
      }

      if ($supportsValidation and $validation_class::canView()) {
         $validations = $valitation_obj->find([$foreignKey => $this->getID()]);
         foreach ($validations as $validations_id => $validation) {
            $canedit = $valitation_obj->can($validations_id, UPDATE);
            $cananswer = ($validation['users_id_validate'] === Session::getLoginUserID() &&
               $validation['status'] == CommonITILValidation::WAITING);
            $user->getFromDB($validation['users_id_validate']);
            $timeline[$validation['submission_date']."_validation_".$validations_id] = [
               'type' => $validation_class,
               'item' => [
                  'id'        => $validations_id,
                  'date'      => $validation['submission_date'],
                  'content'   => __('Validation request')." => ".$user->getlink().
                                                 "<br>".$validation['comment_submission'],
                  'users_id'  => $validation['users_id'],
                  'can_edit'  => $canedit,
                  'can_answer'   => $cananswer,
                  'users_id_validate'  => $validation['users_id_validate'],
                  'timeline_position' => $validation['timeline_position']
               ],
               'itiltype' => 'Validation'
            ];

            if (!empty($validation['validation_date'])) {
               $timeline[$validation['validation_date']."_validation_".$validations_id] = [
                  'type' => $validation_class,
                  'item' => [
                     'id'        => $validations_id,
                     'date'      => $validation['validation_date'],
                     'content'   => __('Validation request answer')." : ". _sx('status',
                                                 ucfirst($validation_class::getStatus($validation['status'])))
                                                   ."<br>".$validation['comment_validation'],
                     'users_id'  => $validation['users_id_validate'],
                     'status'    => "status_".$validation['status'],
                     'can_edit'  => $canedit,
                     'timeline_position' => $validation['timeline_position']
                  ],
                  'itiltype' => 'Validation'
               ];
            }
         }
      }

      //reverse sort timeline items by key (date)
      krsort($timeline);

      return $timeline;
   }

   static function getType() {
      return "Ticket";
   }



   function showTimelineFormMiddle($rand) {

      self::forceTable("glpi_tickets");
      
      global $DB, $CFG_GLPI, $autolink_options;

      $user              = new User();
      $group             = new Group();
      $pics_url          = $CFG_GLPI['root_doc']."/pics/timeline";
      $timeline          = $this->getTimelineItems();
      
      
      $autolink_options['strip_protocols'] = false;

      $objType = static::getType();
      $foreignKey = static::getForeignKeyField();

      //display timeline
      echo "<div class='timeline_history'>";

      $followup_class    = 'ITILFollowup';
      $followup_obj      = new $followup_class();
      $followup_obj->getEmpty();
      $followup_obj->fields['itemtype'] = $objType;

      // show approbation form on top when ticket/change is solved
      if ($this->fields["status"] == CommonITILObject::SOLVED) {
         echo "<div class='approbation_form' id='approbation_form$rand'>";
         $this->showApprobationForm($this);
         echo "</div>";
      }
   }

   function showTimelineFollow($rand) {

      self::forceTable("glpi_tickets");
      
      global $DB, $CFG_GLPI, $autolink_options;

      $user              = new User();
      $group             = new Group();
      $pics_url          = $CFG_GLPI['root_doc']."/pics/timeline";
      $timeline          = $this->getTimelineItems();
      
      
      $autolink_options['strip_protocols'] = false;

      $objType = static::getType();
      $foreignKey = static::getForeignKeyField();

      //display timeline
      echo "<div class='timeline_history'>";

      $followup_class    = 'ITILFollowup';
      $followup_obj      = new $followup_class();
      $followup_obj->getEmpty();
      $followup_obj->fields['itemtype'] = $objType;

      // show approbation form on top when ticket/change is solved
      // if ($this->fields["status"] == CommonITILObject::SOLVED) {
      //    echo "<div class='approbation_form' id='approbation_form$rand'>";
      //    $followup_obj->showApprobationForm($this);
      //    echo "</div>";
      // }
      

      // show title for timeline
      //static::showTimelineHeader();

      $timeline_index = 0;
      foreach ($timeline as $item) {

         if($item['type'] != "ITILFollowup"){
            continue;
         }

         $options = [ 'parent' => $this,
                           'rand' => $rand
                           ];
         if ($obj = getItemForItemtype($item['type'])) {
            $obj->fields = $item['item'];
         } else {
            $obj = $item;
         }
         Plugin::doHook('pre_show_item', ['item' => $obj, 'options' => &$options]);

         if (is_array($obj)) {
            $item_i = $obj['item'];
         } else {
            $item_i = $obj->fields;
         }

         $date = "";
         if (isset($item_i['date'])) {
            $date = $item_i['date'];
         } else if (isset($item_i['date_mod'])) {
            $date = $item_i['date_mod'];
         }

         // set item position depending on field timeline_position
         $user_position = 'left'; // default position
         if (isset($item_i['timeline_position'])) {
            switch ($item_i['timeline_position']) {
               case self::TIMELINE_LEFT:
                  $user_position = 'left';
                  break;
               case self::TIMELINE_MIDLEFT:
                  $user_position = 'left middle';
                  break;
               case self::TIMELINE_MIDRIGHT:
                  $user_position = 'right middle';
                  break;
               case self::TIMELINE_RIGHT:
                  $user_position = 'right';
                  break;
            }
         }

         //display solution in middle
         // if (($item['type'] == "Solution") && $item_i['status'] != CommonITILValidation::REFUSED
         //      && in_array($this->fields["status"], [CommonITILObject::SOLVED, CommonITILObject::CLOSED])) {
         //    $user_position.= ' middle';
         // }

         
         

         echo "<div class='d-flex justify-content-center py-5 h_item $user_position'>";

         echo "<div class='h_info'>";

         
         if ($item_i['users_id'] !== false) {
            echo "<div class='h_user' style='font-size:14px;'>";
            if (isset($item_i['users_id']) && ($item_i['users_id'] != 0)) {
               $user->getFromDB($item_i['users_id']);

               echo "<div class='tooltip_picture_border'>";
               echo "<img style='width: 40px; height: 37px;' class='user_picture' alt=\"".__s('Picture')."\" src='".
                      User::getThumbnailURLForPicture($user->fields['picture'])."'>";
               echo "</div>";

               echo "<span class='h_user_name'>";
               $userdata = getUserName($item_i['users_id'], 2);
               $entity = $this->getEntityID();
               if (Entity::getUsedConfig('anonymize_support_agents', $entity)
                  && Session::getCurrentInterface() == 'helpdesk'
                  && (
                     $item['type'] == "Solution"
                     || is_subclass_of($item['type'], "CommonITILTask")
                     || ($item['type'] == "ITILFollowup"
                        && ITILFollowup::getById($item_i['id'])->isFromSupportAgent()
                     )
                     || ($item['type'] == "Document_Item"
                        && Document_Item::getById($item_i['documents_item_id'])->isFromSupportAgent()
                     )
                  )
               ) {
                  echo __("Helpdesk");
               } else {
                  echo $user->getLink()."&nbsp;";
                  // echo Html::showToolTip(
                  //    $userdata["comment"],
                  //    ['link' => $userdata['link']]
                  // );
               }
               echo "</span>";
               echo "<div class='h_date text-center'>".Html::convDateTime($date)."</div>";
            } else {
               echo __("Requester");
            }
            echo "</div>"; // h_user
         }

         echo "</div>"; //h_info

         $domid = "viewitem{$item['type']}{$item_i['id']}";
         if ($item['type'] == $objType.'Validation' && isset($item_i['status'])) {
            $domid .= $item_i['status'];
         }
         $randdomid = $domid . $rand;
         $domid = Toolbox::slugify($domid);

         $fa = null;
         $class = "h_content";
         if (isset($item['itiltype'])) {
            $class .= " ITIL{$item['itiltype']}";
         } else {
            $class .= " {$item['type']}";
         }
         if ($item['type'] == 'Solution') {
            switch ($item_i['status']) {
               case CommonITILValidation::WAITING:
                  $fa = 'question';
                  $class .= ' waiting';
                  break;
               case CommonITILValidation::ACCEPTED:
                  $fa = 'thumbs-up';
                  $class .= ' accepted';
                  break;
               case CommonITILValidation::REFUSED:
                  $fa = 'thumbs-down';
                  $class .= ' refused';
                  break;
            }
         } else if (isset($item_i['status'])) {
            $class .= " {$item_i['status']}";
         }

         echo "<div class='$class bg-white' style='font-size:14px; border-left: 6px solid #c78036; box-shadow: rgba(0, 0, 0, 0.08) 0px 4px 12px; border-radius:7px;' id='$domid' data-uid='$randdomid'>";
         if ($fa !== null) {
            echo "<i class='solimg fa fa-$fa fa-5x'></i>";
         }
         if (isset($item_i['can_edit']) && $item_i['can_edit']) {
            echo "<div class='edit_item_content'></div>";
            echo "<span class='cancel_edit_item_content'></span>";
         }
         echo "<div class='displayed_content' style='font-size:14px; border-radius:15px;'>";
         // echo "<div class='h_controls'>";
         // if (!in_array($item['type'], ['Document_Item', 'Assign'])
         //    && $item_i['can_edit']
         //    && !in_array($this->fields['status'], $this->getClosedStatusArray())
         // ) {
         //    // merge/split icon
         //    if ($objType == 'Ticket' && $item['type'] == ITILFollowup::getType()) {
         //       if (isset($item_i['sourceof_items_id']) && $item_i['sourceof_items_id'] > 0) {
         //          echo Html::link('', Ticket::getFormURLWithID($item_i['sourceof_items_id']), [
         //             'class' => 'fa fa-code-branch control_item disabled',
         //             'title' => __('Followup was already promoted')
         //          ]);
         //       } else {
         //          echo Html::link('', Ticket::getFormURL()."?_promoted_fup_id=".$item_i['id'], [
         //             'class' => 'fa fa-code-branch control_item',
         //             'title' => __('Promote to Ticket')
         //          ]);
         //       }
         //    }
         //    // edit item
         //    echo "<span class='far fa-edit control_item' title='".__('Edit')."'";
         //    echo "onclick='javascript:viewEditSubitem".$this->fields['id']."$rand(event, \"".$item['type']."\", ".$item_i['id'].", this, \"$randdomid\")'";
         //    echo "></span>";
         // }

         // // show "is_private" icon
         // if (isset($item_i['is_private']) && $item_i['is_private']) {
         //    echo "<span class='private'><i class='fas fa-lock control_item' title='" . __s('Private') .
         //       "'></i><span class='sr-only'>".__('Private')."</span></span>";
         // }

         // echo "</div>";

         if (isset($item_i['requesttypes_id'])
             && file_exists("$pics_url/".$item_i['requesttypes_id'].".png")) {
            echo "<img src='$pics_url/".$item_i['requesttypes_id'].".png' class='h_requesttype' />";
         }

         if (isset($item_i['content'])) {
            $content = $item_i['content'];
            $content = Toolbox::getHtmlToDisplay($content);
            $content = autolink($content, false);

            $long_text = "";
            if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";
            echo "<p>";
            if (isset($item_i['state'])) {
               $onClick = "onclick='change_task_state(".$item_i['id'].", this)'";
               if (!$item_i['can_edit']) {
                  $onClick = "style='cursor: not-allowed;'";
               }
               echo "<span class='state state_".$item_i['state']."'
                           $onClick
                           title='".Planning::getState($item_i['state'])."'>";
               echo "</span>";
            }
            echo "</p>";

            echo "<div class='rich_text_container'>";
            $richtext = Html::setRichTextContent('', $content, '', true);
            $richtext = Html::replaceImagesByGallery($richtext);
            echo $richtext;
            echo "</div>";

            if (!empty($long_text)) {
               echo "<p class='read_more'>";
               echo "<a class='read_more_button'>.....</a>";
               echo "</p>";
            }
            echo "</div>";
         }

         $entity = $this->getEntityID();
         echo "<div class='b_right'>";
         if (isset($item_i['solutiontypes_id']) && !empty($item_i['solutiontypes_id'])) {
            echo Dropdown::getDropdownName("glpi_solutiontypes", $item_i['solutiontypes_id'])."<br>";
         }
         if (isset($item_i['taskcategories_id']) && !empty($item_i['taskcategories_id'])) {
            echo Dropdown::getDropdownName("glpi_taskcategories", $item_i['taskcategories_id'])."<br>";
         }
         if (isset($item_i['requesttypes_id']) && !empty($item_i['requesttypes_id'])) {
            echo Dropdown::getDropdownName("glpi_requesttypes", $item_i['requesttypes_id'])."<br>";
         }

         if (isset($item_i['actiontime']) && !empty($item_i['actiontime'])) {
            echo "<span class='actiontime'>";
            echo Html::timestampToString($item_i['actiontime'], false);
            echo "</span>";
         }
         if (isset($item_i['begin'])) {
            echo "<span class='planification'>";
            echo Html::convDateTime($item_i["begin"]);
            echo " &rArr; ";
            echo Html::convDateTime($item_i["end"]);
            echo "</span>";
         }
         if (isset($item_i['users_id_tech']) && ($item_i['users_id_tech'] > 0)) {
            echo "<div class='users_id_tech' id='users_id_tech_".$item_i['users_id_tech']."'>";
            $user->getFromDB($item_i['users_id_tech']);

            if (Entity::getUsedConfig('anonymize_support_agents', $entity)
               && Session::getCurrentInterface() == 'helpdesk'
            ) {
               echo __("Helpdesk");
            } else {
               echo "<i class='fas fa-user'></i> ";
               $userdata = getUserName($item_i['users_id_tech'], 2);
               echo $user->getLink()."&nbsp;";
               echo Html::showToolTip(
                  $userdata["comment"],
                  ['link' => $userdata['link']]
               );
            }
            echo "</div>";
         }
         if (isset($item_i['groups_id_tech']) && ($item_i['groups_id_tech'] > 0)) {
            echo "<div class='groups_id_tech'>";
            $group->getFromDB($item_i['groups_id_tech']);
            echo "<i class='fas fa-users' aria-hidden='true'></i>&nbsp;";
            echo $group->getLink(['comments' => true]);
            echo "</div>";
         }
         if (isset($item_i['users_id_editor']) && $item_i['users_id_editor'] > 0) {
            echo "<div class='users_id_editor' id='users_id_editor_".$item_i['users_id_editor']."'>";

            if (Entity::getUsedConfig('anonymize_support_agents', $entity)
               && Session::getCurrentInterface() == 'helpdesk'
            ) {
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  Html::convDateTime($item_i['date_mod']),
                  __("Helpdesk")
               );
            } else {
               $user->getFromDB($item_i['users_id_editor']);
               $userdata = getUserName($item_i['users_id_editor'], 2);
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  Html::convDateTime($item_i['date_mod']),
                  $user->getLink()
               );
               echo Html::showToolTip($userdata["comment"],
                                      ['link' => $userdata['link']]);
            }

            echo "</div>";
         }
         if ($objType == 'Ticket' && isset($item_i['sourceitems_id']) && $item_i['sourceitems_id'] > 0) {
            echo "<div id='sourceitems_id_".$item_i['sourceitems_id']."'>";
            echo sprintf(
               __('Merged from Ticket %1$s'),
               Html::link($item_i['sourceitems_id'], Ticket::getFormURLWithID($item_i['sourceitems_id']))
            );
            echo "</div>";
         }
         if ($objType == 'Ticket' && isset($item_i['sourceof_items_id']) && $item_i['sourceof_items_id'] > 0) {
            echo "<div id='sourceof_items_id_".$item_i['sourceof_items_id']."'>";
            echo sprintf(
               __('Promoted to Ticket %1$s'),
               Html::link($item_i['sourceof_items_id'], Ticket::getFormURLWithID($item_i['sourceof_items_id']))
            );
            echo "</div>";
         }
         if (strpos($item['type'], 'Validation') > 0 &&
            (isset($item_i['can_answer']) && $item_i['can_answer'])) {
            $form_url = $item['type']::getFormURL();
            echo "<form id='validationanswers_id_{$item_i['id']}' class='center' action='$form_url' method='post'>";
            echo Html::hidden('id', ['value' => $item_i['id']]);
            echo Html::hidden('users_id_validate', ['value' => $item_i['users_id_validate']]);
            Html::textarea([
               'name'   => 'comment_validation',
               'rows'   => 5
            ]);
            echo "<button type='submit' class='submit approve' name='approval_action' value='approve'>";
            echo "<i class='far fa-thumbs-up'></i>&nbsp;&nbsp;".__('Approve')."</button>";

            echo "<button type='submit' class='submit refuse very_small_space' name='approval_action' value='refuse'>";
            echo "<i class='far fa-thumbs-down'></i>&nbsp;&nbsp;".__('Refuse')."</button>";
            Html::closeForm();
         }
         if ($item['type'] == 'Solution' && $item_i['status'] != CommonITILValidation::WAITING && $item_i['status'] != CommonITILValidation::NONE) {
            echo "<div class='users_id_approval' id='users_id_approval_".$item_i['users_id_approval']."'>";
            $user->getFromDB($item_i['users_id_approval']);
            $userdata = getUserName($item_i['users_id_editor'], 2);
            $message = __('%1$s on %2$s by %3$s');
            $action = $item_i['status'] == CommonITILValidation::ACCEPTED ? __('Accepted') : __('Refused');
            echo sprintf(
               $message,
               $action,
               Html::convDateTime($item_i['date_approval']),
               $user->getLink()
            );
            echo Html::showToolTip($userdata["comment"],
                                   ['link' => $userdata['link']]);
            echo "</div>";
         }

         echo "</div>"; // b_right

         if ($item['type'] == 'Document_Item') {
            if ($item_i['filename']) {
               $filename = $item_i['filename'];
               $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
               echo "<img src='";
               if (empty($filename)) {
                  $filename = $item_i['name'];
               }
               if (file_exists(GLPI_ROOT."/pics/icones/$ext-dist.png")) {
                  echo $CFG_GLPI['root_doc']."/pics/icones/$ext-dist.png";
               } else {
                  echo "$pics_url/file.png";
               }
               echo "'/>&nbsp;";

               $docsrc = $CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                      ."&$foreignKey=".$this->getID();
               echo Html::link($filename, $docsrc, ['target' => '_blank']);
               $docpath = GLPI_DOC_DIR . '/' . $item_i['filepath'];
               if (Document::isImage($docpath)) {
                  $imgsize = getimagesize($docpath);
                  echo Html::imageGallery([
                     [
                        'src' => $docsrc,
                        'w'   => $imgsize[0],
                        'h'   => $imgsize[1]
                     ]
                  ]);
               }
            }
            if ($item_i['link']) {
               echo "<a href='{$item_i['link']}' target='_blank'><i class='fa fa-external-link'></i>{$item_i['name']}</a>";
            }
            if (!empty($item_i['mime'])) {
               echo "&nbsp;(".$item_i['mime'].")";
            }
            echo "<span class='buttons'>";
            echo "<a href='".Document::getFormURLWithID($item_i['id'])."' class='edit_document fa fa-eye pointer' title='".
                   _sx("button", "Show")."'>";
            echo "<span class='sr-only'>" . _sx('button', 'Show') . "</span></a>";

            $doc = new Document();
            $doc->getFromDB($item_i['id']);
            if ($doc->can($item_i['id'], UPDATE)) {
               echo "<a href='".static::getFormURL().
                     "?delete_document&documents_id=".$item_i['id'].
                     "&$foreignKey=".$this->getID()."' class='delete_document fas fa-trash-alt pointer' title='".
                     _sx("button", "Delete permanently")."'>";
               echo "<span class='sr-only'>" . _sx('button', 'Delete permanently')  . "</span></a>";
            }
            echo "</span>";
         }

         echo "</div>"; // displayed_content
         echo "</div>"; //end h_content

         echo "</div>"; //end  h_info

         $timeline_index++;

         Plugin::doHook('post_show_item', ['item' => $obj, 'options' => $options]);

      } // end foreach timeline

      // echo "<div class='break'></div>";

      // // recall content
      // echo "<div class='h_item middle'>";

      // echo "<div class='h_info'>";
      // echo "<div class='h_date'><i class='far fa-clock'></i>".Html::convDateTime($this->fields['date'])."</div>";
      // echo "<div class='h_user'>";

      // $user = new User();
      // $display_requester = false;
      // $requesters = $this->getUsers(CommonITILActor::REQUESTER);
      // if (count($requesters) === 1) {
      //    $requester = reset($requesters);
      //    if ($requester['users_id'] > 0) {
      //       // Display requester identity only if there is only one requester
      //       // and only if it is not an anonymous user
      //       $display_requester = $user->getFromDB($requester['users_id']);
      //    }
      // }

      // echo "<div class='tooltip_picture_border'>";
      // $picture = "";
      // if ($display_requester && isset($user->fields['picture'])) {
      //    $picture = $user->fields['picture'];
      // }
      // echo "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
      // User::getThumbnailURLForPicture($picture)."'>";
      // echo "</div>";

      // if ($display_requester) {
      //    echo $user->getLink()."&nbsp;";
      //    $reqdata = getUserName($user->getID(), 2);
      //    echo Html::showToolTip(
      //       $reqdata["comment"],
      //       ['link' => $reqdata['link']]
      //    );
      // } else {
      //    echo _n('Requester', 'Requesters', count($requesters));
      // }

      // echo "</div>"; // h_user
      // echo "</div>"; //h_info

      // echo "<div class='h_content ITILContent'>";
      // echo "<div class='displayed_content'>";
      // echo "<div class='b_right'>";

      // if ($objType == 'Ticket') {
      //    $result = $DB->request([
      //       'SELECT' => ['id', 'itemtype', 'items_id'],
      //       'FROM'   => ITILFollowup::getTable(),
      //       'WHERE'  => [
      //          'sourceof_items_id'  => $this->fields['id'],
      //          'itemtype'           => static::getType()
      //       ]
      //    ])->next();
      //    if ($result) {
      //       echo Html::link(
      //          '',
      //          static::getFormURLWithID($result['items_id']) . '&forcetab=Ticket$1#viewitemitilfollowup' . $result['id'], [
      //             'class' => 'fa fa-code-branch control_item disabled',
      //             'title' => __('Followup promotion source')
      //          ]
      //       );
      //    }
      // }
      // echo sprintf(__($objType."# %s description"), $this->getID());
      // echo "</div>";

      // echo "<div class='title'>";
      // echo Html::setSimpleTextContent($this->fields['name']);
      // echo "</div>";

      // echo "<div class='rich_text_container'>";
      // $richtext = Html::setRichTextContent('', $this->fields['content'], '', true);
      // $richtext = Html::replaceImagesByGallery($richtext);
      // echo $richtext;
      // echo "</div>";

      // echo "</div>"; // h_content ITILContent

      // echo "</div>"; // .displayed_content
      // echo "</div>"; // h_item middle

      echo "<div class='break'></div>";

      // end timeline
      echo "</div>"; // h_item $user_position

      echo "<script type='text/javascript'>$(function() {read_more();});</script>";
   }




   function showTimelineTask($rand) {

      self::forceTable("glpi_tickets");
      
      global $DB, $CFG_GLPI, $autolink_options;

      $user              = new User();
      $group             = new Group();
      $pics_url          = $CFG_GLPI['root_doc']."/pics/timeline";
      $timeline          = $this->getTimelineItems();
      
      
      $autolink_options['strip_protocols'] = false;

      $objType = static::getType();
      $foreignKey = static::getForeignKeyField();

      //display timeline
      echo "<div class='timeline_history'>";

      $followup_class    = 'ITILFollowup';
      $followup_obj      = new $followup_class();
      $followup_obj->getEmpty();
      $followup_obj->fields['itemtype'] = $objType;

      // show approbation form on top when ticket/change is solved
      // if ($this->fields["status"] == CommonITILObject::SOLVED) {
      //    echo "<div class='approbation_form' id='approbation_form$rand'>";
      //    $followup_obj->showApprobationForm($this);
      //    echo "</div>";
      // }
      

      // show title for timeline
      //static::showTimelineHeader();

      $timeline_index = 0;
      foreach ($timeline as $item) {

         if($item['type'] != "TicketTask"){
            continue;
         }

         $options = [ 'parent' => $this,
                           'rand' => $rand
                           ];
         if ($obj = getItemForItemtype($item['type'])) {
            $obj->fields = $item['item'];
         } else {
            $obj = $item;
         }
         Plugin::doHook('pre_show_item', ['item' => $obj, 'options' => &$options]);

         if (is_array($obj)) {
            $item_i = $obj['item'];
         } else {
            $item_i = $obj->fields;
         }

         $date = "";
         if (isset($item_i['date'])) {
            $date = $item_i['date'];
         } else if (isset($item_i['date_mod'])) {
            $date = $item_i['date_mod'];
         }

         // set item position depending on field timeline_position
         $user_position = 'left'; // default position
         if (isset($item_i['timeline_position'])) {
            switch ($item_i['timeline_position']) {
               case self::TIMELINE_LEFT:
                  $user_position = 'left';
                  break;
               case self::TIMELINE_MIDLEFT:
                  $user_position = 'left middle';
                  break;
               case self::TIMELINE_MIDRIGHT:
                  $user_position = 'right middle';
                  break;
               case self::TIMELINE_RIGHT:
                  $user_position = 'right';
                  break;
            }
         }

         //display solution in middle
         // if (($item['type'] == "Solution") && $item_i['status'] != CommonITILValidation::REFUSED
         //      && in_array($this->fields["status"], [CommonITILObject::SOLVED, CommonITILObject::CLOSED])) {
         //    $user_position.= ' middle';
         // }

         
         

         echo "<div class='d-flex justify-content-center py-5 h_item $user_position'>";

         echo "<div class='h_info'>";

         
         if ($item_i['users_id'] !== false) {
            echo "<div class='h_user' style='font-size:14px;'>";
            if (isset($item_i['users_id']) && ($item_i['users_id'] != 0)) {
               $user->getFromDB($item_i['users_id']);

               echo "<div class='tooltip_picture_border'>";
               echo "<img style='width: 40px; height: 37px;' class='user_picture' alt=\"".__s('Picture')."\" src='".
                      User::getThumbnailURLForPicture($user->fields['picture'])."'>";
               echo "</div>";

               echo "<span class='h_user_name'>";
               $userdata = getUserName($item_i['users_id'], 2);
               $entity = $this->getEntityID();
               if (Entity::getUsedConfig('anonymize_support_agents', $entity)
                  && Session::getCurrentInterface() == 'helpdesk'
                  && (
                     $item['type'] == "Solution"
                     || is_subclass_of($item['type'], "CommonITILTask")
                     || ($item['type'] == "ITILFollowup"
                        && ITILFollowup::getById($item_i['id'])->isFromSupportAgent()
                     )
                     || ($item['type'] == "Document_Item"
                        && Document_Item::getById($item_i['documents_item_id'])->isFromSupportAgent()
                     )
                  )
               ) {
                  echo __("Helpdesk");
               } else {
                  echo $user->getLink()."&nbsp;";
                  // echo Html::showToolTip(
                  //    $userdata["comment"],
                  //    ['link' => $userdata['link']]
                  // );
               }
               echo "</span>";
               echo "<div class='h_date text-center'>".Html::convDateTime($date)."</div>";
            } else {
               echo __("Requester");
            }
            echo "</div>"; // h_user
         }

         echo "</div>"; //h_info

         $domid = "viewitem{$item['type']}{$item_i['id']}";
         if ($item['type'] == $objType.'Validation' && isset($item_i['status'])) {
            $domid .= $item_i['status'];
         }
         $randdomid = $domid . $rand;
         $domid = Toolbox::slugify($domid);

         $fa = null;
         $class = "h_content";
         if (isset($item['itiltype'])) {
            $class .= " ITIL{$item['itiltype']}";
         } else {
            $class .= " {$item['type']}";
         }
         if ($item['type'] == 'Solution') {
            switch ($item_i['status']) {
               case CommonITILValidation::WAITING:
                  $fa = 'question';
                  $class .= ' waiting';
                  break;
               case CommonITILValidation::ACCEPTED:
                  $fa = 'thumbs-up';
                  $class .= ' accepted';
                  break;
               case CommonITILValidation::REFUSED:
                  $fa = 'thumbs-down';
                  $class .= ' refused';
                  break;
            }
         } else if (isset($item_i['status'])) {
            $class .= " {$item_i['status']}";
         }

         echo "<div class='$class bg-white' style='font-size:14px; border-left: 6px solid #c78036; box-shadow: rgba(0, 0, 0, 0.08) 0px 4px 12px; border-radius:7px;' id='$domid' data-uid='$randdomid'>";
         if ($fa !== null) {
            echo "<i class='solimg fa fa-$fa fa-5x'></i>";
         }
         if (isset($item_i['can_edit']) && $item_i['can_edit']) {
            echo "<div class='edit_item_content'></div>";
            echo "<span class='cancel_edit_item_content'></span>";
         }
         echo "<div class='displayed_content' style='font-size:14px; border-radius:15px;'>";
         echo "<div class='h_controls'>";
         if (!in_array($item['type'], ['Document_Item', 'Assign'])
            && $item_i['can_edit']
            && !in_array($this->fields['status'], $this->getClosedStatusArray())
         ) {
            // merge/split icon
            if ($objType == 'Ticket' && $item['type'] == ITILFollowup::getType()) {
               if (isset($item_i['sourceof_items_id']) && $item_i['sourceof_items_id'] > 0) {
                  echo Html::link('', Ticket::getFormURLWithID($item_i['sourceof_items_id']), [
                     'class' => 'fa fa-code-branch control_item disabled',
                     'title' => __('Followup was already promoted')
                  ]);
               } else {
                  echo Html::link('', Ticket::getFormURL()."?_promoted_fup_id=".$item_i['id'], [
                     'class' => 'fa fa-code-branch control_item',
                     'title' => __('Promote to Ticket')
                  ]);
               }
            }
            // edit item
            echo "<span class='far fa-edit control_item' title='".__('Edit')."'";
            echo "onclick='javascript:viewEditSubitem".$this->fields['id']."$rand(event, \"".$item['type']."\", ".$item_i['id'].", this, \"$randdomid\")'";
            echo "></span>";
         }

         // show "is_private" icon
         if (isset($item_i['is_private']) && $item_i['is_private']) {
            echo "<span class='private'><i class='fas fa-lock control_item' title='" . __s('Private') .
               "'></i><span class='sr-only'>".__('Private')."</span></span>";
         }

         echo "</div>";

         if (isset($item_i['requesttypes_id'])
             && file_exists("$pics_url/".$item_i['requesttypes_id'].".png")) {
            echo "<img src='$pics_url/".$item_i['requesttypes_id'].".png' class='h_requesttype' />";
         }

         if (isset($item_i['content'])) {
            $content = $item_i['content'];
            $content = Toolbox::getHtmlToDisplay($content);
            $content = autolink($content, false);

            $long_text = "";
            if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";
            echo "<p>";
            if (isset($item_i['state'])) {
               $onClick = "onclick='change_task_state(".$item_i['id'].", this)'";
               if (!$item_i['can_edit']) {
                  $onClick = "style='cursor: not-allowed;'";
               }
               echo "<span class='state state_".$item_i['state']."'
                           $onClick
                           title='".Planning::getState($item_i['state'])."'>";
               echo "</span>";
            }
            echo "</p>";

            echo "<div class='rich_text_container'>";
            $richtext = Html::setRichTextContent('', $content, '', true);
            $richtext = Html::replaceImagesByGallery($richtext);
            echo $richtext;
            echo "</div>";

            if (!empty($long_text)) {
               echo "<p class='read_more'>";
               echo "<a class='read_more_button'>.....</a>";
               echo "</p>";
            }
            echo "</div>";
         }

         $entity = $this->getEntityID();
         echo "<div class='b_right'>";
         if (isset($item_i['solutiontypes_id']) && !empty($item_i['solutiontypes_id'])) {
            echo Dropdown::getDropdownName("glpi_solutiontypes", $item_i['solutiontypes_id'])."<br>";
         }
         if (isset($item_i['taskcategories_id']) && !empty($item_i['taskcategories_id'])) {
            echo Dropdown::getDropdownName("glpi_taskcategories", $item_i['taskcategories_id'])."<br>";
         }
         if (isset($item_i['requesttypes_id']) && !empty($item_i['requesttypes_id'])) {
            echo Dropdown::getDropdownName("glpi_requesttypes", $item_i['requesttypes_id'])."<br>";
         }

         if (isset($item_i['actiontime']) && !empty($item_i['actiontime'])) {
            echo "<span class='actiontime'>";
            echo Html::timestampToString($item_i['actiontime'], false);
            echo "</span>";
         }
         if (isset($item_i['begin'])) {
            echo "<span class='planification'>";
            echo Html::convDateTime($item_i["begin"]);
            echo " &rArr; ";
            echo Html::convDateTime($item_i["end"]);
            echo "</span>";
         }
         if (isset($item_i['users_id_tech']) && ($item_i['users_id_tech'] > 0)) {
            echo "<div class='users_id_tech' id='users_id_tech_".$item_i['users_id_tech']."'>";
            $user->getFromDB($item_i['users_id_tech']);

            if (Entity::getUsedConfig('anonymize_support_agents', $entity)
               && Session::getCurrentInterface() == 'helpdesk'
            ) {
               echo __("Helpdesk");
            } else {
               echo "<i class='fas fa-user'></i> ";
               $userdata = getUserName($item_i['users_id_tech'], 2);
               echo $user->getLink()."&nbsp;";
               echo Html::showToolTip(
                  $userdata["comment"],
                  ['link' => $userdata['link']]
               );
            }
            echo "</div>";
         }
         if (isset($item_i['groups_id_tech']) && ($item_i['groups_id_tech'] > 0)) {
            echo "<div class='groups_id_tech'>";
            $group->getFromDB($item_i['groups_id_tech']);
            echo "<i class='fas fa-users' aria-hidden='true'></i>&nbsp;";
            echo $group->getLink(['comments' => true]);
            echo "</div>";
         }
         if (isset($item_i['users_id_editor']) && $item_i['users_id_editor'] > 0) {
            echo "<div class='users_id_editor' id='users_id_editor_".$item_i['users_id_editor']."'>";

            if (Entity::getUsedConfig('anonymize_support_agents', $entity)
               && Session::getCurrentInterface() == 'helpdesk'
            ) {
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  Html::convDateTime($item_i['date_mod']),
                  __("Helpdesk")
               );
            } else {
               $user->getFromDB($item_i['users_id_editor']);
               $userdata = getUserName($item_i['users_id_editor'], 2);
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  Html::convDateTime($item_i['date_mod']),
                  $user->getLink()
               );
               echo Html::showToolTip($userdata["comment"],
                                      ['link' => $userdata['link']]);
            }

            echo "</div>";
         }
         if ($objType == 'Ticket' && isset($item_i['sourceitems_id']) && $item_i['sourceitems_id'] > 0) {
            echo "<div id='sourceitems_id_".$item_i['sourceitems_id']."'>";
            echo sprintf(
               __('Merged from Ticket %1$s'),
               Html::link($item_i['sourceitems_id'], Ticket::getFormURLWithID($item_i['sourceitems_id']))
            );
            echo "</div>";
         }
         if ($objType == 'Ticket' && isset($item_i['sourceof_items_id']) && $item_i['sourceof_items_id'] > 0) {
            echo "<div id='sourceof_items_id_".$item_i['sourceof_items_id']."'>";
            echo sprintf(
               __('Promoted to Ticket %1$s'),
               Html::link($item_i['sourceof_items_id'], Ticket::getFormURLWithID($item_i['sourceof_items_id']))
            );
            echo "</div>";
         }
         if (strpos($item['type'], 'Validation') > 0 &&
            (isset($item_i['can_answer']) && $item_i['can_answer'])) {
            $form_url = $item['type']::getFormURL();
            echo "<form id='validationanswers_id_{$item_i['id']}' class='center' action='$form_url' method='post'>";
            echo Html::hidden('id', ['value' => $item_i['id']]);
            echo Html::hidden('users_id_validate', ['value' => $item_i['users_id_validate']]);
            Html::textarea([
               'name'   => 'comment_validation',
               'rows'   => 5
            ]);
            echo "<button type='submit' class='submit approve' name='approval_action' value='approve'>";
            echo "<i class='far fa-thumbs-up'></i>&nbsp;&nbsp;".__('Approve')."</button>";

            echo "<button type='submit' class='submit refuse very_small_space' name='approval_action' value='refuse'>";
            echo "<i class='far fa-thumbs-down'></i>&nbsp;&nbsp;".__('Refuse')."</button>";
            Html::closeForm();
         }
         if ($item['type'] == 'Solution' && $item_i['status'] != CommonITILValidation::WAITING && $item_i['status'] != CommonITILValidation::NONE) {
            echo "<div class='users_id_approval' id='users_id_approval_".$item_i['users_id_approval']."'>";
            $user->getFromDB($item_i['users_id_approval']);
            $userdata = getUserName($item_i['users_id_editor'], 2);
            $message = __('%1$s on %2$s by %3$s');
            $action = $item_i['status'] == CommonITILValidation::ACCEPTED ? __('Accepted') : __('Refused');
            echo sprintf(
               $message,
               $action,
               Html::convDateTime($item_i['date_approval']),
               $user->getLink()
            );
            echo Html::showToolTip($userdata["comment"],
                                   ['link' => $userdata['link']]);
            echo "</div>";
         }

         echo "</div>"; // b_right

         if ($item['type'] == 'Document_Item') {
            if ($item_i['filename']) {
               $filename = $item_i['filename'];
               $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
               echo "<img src='";
               if (empty($filename)) {
                  $filename = $item_i['name'];
               }
               if (file_exists(GLPI_ROOT."/pics/icones/$ext-dist.png")) {
                  echo $CFG_GLPI['root_doc']."/pics/icones/$ext-dist.png";
               } else {
                  echo "$pics_url/file.png";
               }
               echo "'/>&nbsp;";

               $docsrc = $CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                      ."&$foreignKey=".$this->getID();
               echo Html::link($filename, $docsrc, ['target' => '_blank']);
               $docpath = GLPI_DOC_DIR . '/' . $item_i['filepath'];
               if (Document::isImage($docpath)) {
                  $imgsize = getimagesize($docpath);
                  echo Html::imageGallery([
                     [
                        'src' => $docsrc,
                        'w'   => $imgsize[0],
                        'h'   => $imgsize[1]
                     ]
                  ]);
               }
            }
            if ($item_i['link']) {
               echo "<a href='{$item_i['link']}' target='_blank'><i class='fa fa-external-link'></i>{$item_i['name']}</a>";
            }
            if (!empty($item_i['mime'])) {
               echo "&nbsp;(".$item_i['mime'].")";
            }
            echo "<span class='buttons'>";
            echo "<a href='".Document::getFormURLWithID($item_i['id'])."' class='edit_document fa fa-eye pointer' title='".
                   _sx("button", "Show")."'>";
            echo "<span class='sr-only'>" . _sx('button', 'Show') . "</span></a>";

            $doc = new Document();
            $doc->getFromDB($item_i['id']);
            if ($doc->can($item_i['id'], UPDATE)) {
               echo "<a href='".static::getFormURL().
                     "?delete_document&documents_id=".$item_i['id'].
                     "&$foreignKey=".$this->getID()."' class='delete_document fas fa-trash-alt pointer' title='".
                     _sx("button", "Delete permanently")."'>";
               echo "<span class='sr-only'>" . _sx('button', 'Delete permanently')  . "</span></a>";
            }
            echo "</span>";
         }

         echo "</div>"; // displayed_content
         echo "</div>"; //end h_content

         echo "</div>"; //end  h_info

         $timeline_index++;

         Plugin::doHook('post_show_item', ['item' => $obj, 'options' => $options]);

      } // end foreach timeline

      // echo "<div class='break'></div>";

      // // recall content
      // echo "<div class='h_item middle'>";

      // echo "<div class='h_info'>";
      // echo "<div class='h_date'><i class='far fa-clock'></i>".Html::convDateTime($this->fields['date'])."</div>";
      // echo "<div class='h_user'>";

      // $user = new User();
      // $display_requester = false;
      // $requesters = $this->getUsers(CommonITILActor::REQUESTER);
      // if (count($requesters) === 1) {
      //    $requester = reset($requesters);
      //    if ($requester['users_id'] > 0) {
      //       // Display requester identity only if there is only one requester
      //       // and only if it is not an anonymous user
      //       $display_requester = $user->getFromDB($requester['users_id']);
      //    }
      // }

      // echo "<div class='tooltip_picture_border'>";
      // $picture = "";
      // if ($display_requester && isset($user->fields['picture'])) {
      //    $picture = $user->fields['picture'];
      // }
      // echo "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
      // User::getThumbnailURLForPicture($picture)."'>";
      // echo "</div>";

      // if ($display_requester) {
      //    echo $user->getLink()."&nbsp;";
      //    $reqdata = getUserName($user->getID(), 2);
      //    echo Html::showToolTip(
      //       $reqdata["comment"],
      //       ['link' => $reqdata['link']]
      //    );
      // } else {
      //    echo _n('Requester', 'Requesters', count($requesters));
      // }

      // echo "</div>"; // h_user
      // echo "</div>"; //h_info

      // echo "<div class='h_content ITILContent'>";
      // echo "<div class='displayed_content'>";
      // echo "<div class='b_right'>";

      // if ($objType == 'Ticket') {
      //    $result = $DB->request([
      //       'SELECT' => ['id', 'itemtype', 'items_id'],
      //       'FROM'   => ITILFollowup::getTable(),
      //       'WHERE'  => [
      //          'sourceof_items_id'  => $this->fields['id'],
      //          'itemtype'           => static::getType()
      //       ]
      //    ])->next();
      //    if ($result) {
      //       echo Html::link(
      //          '',
      //          static::getFormURLWithID($result['items_id']) . '&forcetab=Ticket$1#viewitemitilfollowup' . $result['id'], [
      //             'class' => 'fa fa-code-branch control_item disabled',
      //             'title' => __('Followup promotion source')
      //          ]
      //       );
      //    }
      // }
      // echo sprintf(__($objType."# %s description"), $this->getID());
      // echo "</div>";

      // echo "<div class='title'>";
      // echo Html::setSimpleTextContent($this->fields['name']);
      // echo "</div>";

      // echo "<div class='rich_text_container'>";
      // $richtext = Html::setRichTextContent('', $this->fields['content'], '', true);
      // $richtext = Html::replaceImagesByGallery($richtext);
      // echo $richtext;
      // echo "</div>";

      // echo "</div>"; // h_content ITILContent

      // echo "</div>"; // .displayed_content
      // echo "</div>"; // h_item middle

      echo "<div class='break'></div>";

      // end timeline
      echo "</div>"; // h_item $user_position

      echo "<script type='text/javascript'>$(function() {read_more();});</script>";
   }




   function showTimelineDocs($rand) {

      self::forceTable("glpi_tickets");
      
      global $DB, $CFG_GLPI, $autolink_options;

      $user              = new User();
      $group             = new Group();
      $pics_url          = $CFG_GLPI['root_doc']."/pics/timeline";
      $timeline          = $this->getTimelineItems();
      
      
      $autolink_options['strip_protocols'] = false;

      $objType = static::getType();
      $foreignKey = static::getForeignKeyField();

      //display timeline
      echo "<div class='timeline_history'>";

      $followup_class    = 'ITILFollowup';
      $followup_obj      = new $followup_class();
      $followup_obj->getEmpty();
      $followup_obj->fields['itemtype'] = $objType;

      // show approbation form on top when ticket/change is solved
      // if ($this->fields["status"] == CommonITILObject::SOLVED) {
      //    echo "<div class='approbation_form' id='approbation_form$rand'>";
      //    $followup_obj->showApprobationForm($this);
      //    echo "</div>";
      // }
      

      // show title for timeline
      //static::showTimelineHeader();

      $timeline_index = 0;
      foreach ($timeline as $item) {

         if($item['type'] != "Document_Item"){
            continue;
         }

         $options = [ 'parent' => $this,
                           'rand' => $rand
                           ];
         if ($obj = getItemForItemtype($item['type'])) {
            $obj->fields = $item['item'];
         } else {
            $obj = $item;
         }
         Plugin::doHook('pre_show_item', ['item' => $obj, 'options' => &$options]);

         if (is_array($obj)) {
            $item_i = $obj['item'];
         } else {
            $item_i = $obj->fields;
         }

         $date = "";
         if (isset($item_i['date'])) {
            $date = $item_i['date'];
         } else if (isset($item_i['date_mod'])) {
            $date = $item_i['date_mod'];
         }

         // set item position depending on field timeline_position
         $user_position = 'left'; // default position
         if (isset($item_i['timeline_position'])) {
            switch ($item_i['timeline_position']) {
               case self::TIMELINE_LEFT:
                  $user_position = 'left';
                  break;
               case self::TIMELINE_MIDLEFT:
                  $user_position = 'left middle';
                  break;
               case self::TIMELINE_MIDRIGHT:
                  $user_position = 'right middle';
                  break;
               case self::TIMELINE_RIGHT:
                  $user_position = 'right';
                  break;
            }
         }

         //display solution in middle
         // if (($item['type'] == "Solution") && $item_i['status'] != CommonITILValidation::REFUSED
         //      && in_array($this->fields["status"], [CommonITILObject::SOLVED, CommonITILObject::CLOSED])) {
         //    $user_position.= ' middle';
         // }

         
         

         echo "<div class='d-flex justify-content-center py-5 h_item $user_position'>";

         echo "<div class='h_info'>";

         
         if ($item_i['users_id'] !== false) {
            echo "<div class='h_user' style='font-size:14px;'>";
            if (isset($item_i['users_id']) && ($item_i['users_id'] != 0)) {
               $user->getFromDB($item_i['users_id']);

               echo "<div class='tooltip_picture_border'>";
               echo "<img style='width: 40px; height: 37px;' class='user_picture' alt=\"".__s('Picture')."\" src='".
                      User::getThumbnailURLForPicture($user->fields['picture'])."'>";
               echo "</div>";

               echo "<span class='h_user_name'>";
               $userdata = getUserName($item_i['users_id'], 2);
               $entity = $this->getEntityID();
               if (Entity::getUsedConfig('anonymize_support_agents', $entity)
                  && Session::getCurrentInterface() == 'helpdesk'
                  && (
                     $item['type'] == "Solution"
                     || is_subclass_of($item['type'], "CommonITILTask")
                     || ($item['type'] == "ITILFollowup"
                        && ITILFollowup::getById($item_i['id'])->isFromSupportAgent()
                     )
                     || ($item['type'] == "Document_Item"
                        && Document_Item::getById($item_i['documents_item_id'])->isFromSupportAgent()
                     )
                  )
               ) {
                  echo __("Helpdesk");
               } else {
                  echo $user->getLink()."&nbsp;";
                  // echo Html::showToolTip(
                  //    $userdata["comment"],
                  //    ['link' => $userdata['link']]
                  // );
               }
               echo "</span>";
               echo "<div class='h_date text-center'>".Html::convDateTime($date)."</div>";
            } else {
               echo __("Requester");
            }
            echo "</div>"; // h_user
         }

         echo "</div>"; //h_info

         $domid = "viewitem{$item['type']}{$item_i['id']}";
         if ($item['type'] == $objType.'Validation' && isset($item_i['status'])) {
            $domid .= $item_i['status'];
         }
         $randdomid = $domid . $rand;
         $domid = Toolbox::slugify($domid);

         $fa = null;
         $class = "h_content";
         if (isset($item['itiltype'])) {
            $class .= " ITIL{$item['itiltype']}";
         } else {
            $class .= " {$item['type']}";
         }
         if ($item['type'] == 'Solution') {
            switch ($item_i['status']) {
               case CommonITILValidation::WAITING:
                  $fa = 'question';
                  $class .= ' waiting';
                  break;
               case CommonITILValidation::ACCEPTED:
                  $fa = 'thumbs-up';
                  $class .= ' accepted';
                  break;
               case CommonITILValidation::REFUSED:
                  $fa = 'thumbs-down';
                  $class .= ' refused';
                  break;
            }
         } else if (isset($item_i['status'])) {
            $class .= " {$item_i['status']}";
         }

         echo "<div class='$class bg-white pl-3' style='font-size:14px; border-left: 6px solid #c78036; box-shadow: rgba(0, 0, 0, 0.08) 0px 4px 12px; border-radius:7px;' id='$domid' data-uid='$randdomid'>";
         if ($fa !== null) {
            echo "<i class='solimg fa fa-$fa fa-5x'></i>";
         }
         if (isset($item_i['can_edit']) && $item_i['can_edit']) {
            echo "<div class='edit_item_content'></div>";
            echo "<span class='cancel_edit_item_content'></span>";
         }
         echo "<div class='displayed_content' style='font-size:14px; border-radius:15px;'>";
         echo "<div class='h_controls'>";
         if (!in_array($item['type'], ['Document_Item', 'Assign'])
            && $item_i['can_edit']
            && !in_array($this->fields['status'], $this->getClosedStatusArray())
         ) {
            // merge/split icon
            if ($objType == 'Ticket' && $item['type'] == ITILFollowup::getType()) {
               if (isset($item_i['sourceof_items_id']) && $item_i['sourceof_items_id'] > 0) {
                  echo Html::link('', Ticket::getFormURLWithID($item_i['sourceof_items_id']), [
                     'class' => 'fa fa-code-branch control_item disabled',
                     'title' => __('Followup was already promoted')
                  ]);
               } else {
                  echo Html::link('', Ticket::getFormURL()."?_promoted_fup_id=".$item_i['id'], [
                     'class' => 'fa fa-code-branch control_item',
                     'title' => __('Promote to Ticket')
                  ]);
               }
            }
            // edit item
            echo "<span class='far fa-edit control_item' title='".__('Edit')."'";
            echo "onclick='javascript:viewEditSubitem".$this->fields['id']."$rand(event, \"".$item['type']."\", ".$item_i['id'].", this, \"$randdomid\")'";
            echo "></span>";
         }

         // show "is_private" icon
         if (isset($item_i['is_private']) && $item_i['is_private']) {
            echo "<span class='private'><i class='fas fa-lock control_item' title='" . __s('Private') .
               "'></i><span class='sr-only'>".__('Private')."</span></span>";
         }

         echo "</div>";

         if (isset($item_i['requesttypes_id'])
             && file_exists("$pics_url/".$item_i['requesttypes_id'].".png")) {
            echo "<img src='$pics_url/".$item_i['requesttypes_id'].".png' class='h_requesttype' />";
         }

         if (isset($item_i['content'])) {
            $content = $item_i['content'];
            $content = Toolbox::getHtmlToDisplay($content);
            $content = autolink($content, false);

            $long_text = "";
            if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";
            echo "<p>";
            if (isset($item_i['state'])) {
               $onClick = "onclick='change_task_state(".$item_i['id'].", this)'";
               if (!$item_i['can_edit']) {
                  $onClick = "style='cursor: not-allowed;'";
               }
               echo "<span class='state state_".$item_i['state']."'
                           $onClick
                           title='".Planning::getState($item_i['state'])."'>";
               echo "</span>";
            }
            echo "</p>";

            echo "<div class='rich_text_container'>";
            $richtext = Html::setRichTextContent('', $content, '', true);
            $richtext = Html::replaceImagesByGallery($richtext);
            echo $richtext;
            echo "</div>";

            if (!empty($long_text)) {
               echo "<p class='read_more'>";
               echo "<a class='read_more_button'>.....</a>";
               echo "</p>";
            }
            echo "</div>";
         }

         $entity = $this->getEntityID();
         echo "<div class='b_right'>";
         if (isset($item_i['solutiontypes_id']) && !empty($item_i['solutiontypes_id'])) {
            echo Dropdown::getDropdownName("glpi_solutiontypes", $item_i['solutiontypes_id'])."<br>";
         }
         if (isset($item_i['taskcategories_id']) && !empty($item_i['taskcategories_id'])) {
            echo Dropdown::getDropdownName("glpi_taskcategories", $item_i['taskcategories_id'])."<br>";
         }
         if (isset($item_i['requesttypes_id']) && !empty($item_i['requesttypes_id'])) {
            echo Dropdown::getDropdownName("glpi_requesttypes", $item_i['requesttypes_id'])."<br>";
         }

         if (isset($item_i['actiontime']) && !empty($item_i['actiontime'])) {
            echo "<span class='actiontime'>";
            echo Html::timestampToString($item_i['actiontime'], false);
            echo "</span>";
         }
         if (isset($item_i['begin'])) {
            echo "<span class='planification'>";
            echo Html::convDateTime($item_i["begin"]);
            echo " &rArr; ";
            echo Html::convDateTime($item_i["end"]);
            echo "</span>";
         }
         if (isset($item_i['users_id_tech']) && ($item_i['users_id_tech'] > 0)) {
            echo "<div class='users_id_tech' id='users_id_tech_".$item_i['users_id_tech']."'>";
            $user->getFromDB($item_i['users_id_tech']);

            if (Entity::getUsedConfig('anonymize_support_agents', $entity)
               && Session::getCurrentInterface() == 'helpdesk'
            ) {
               echo __("Helpdesk");
            } else {
               echo "<i class='fas fa-user'></i> ";
               $userdata = getUserName($item_i['users_id_tech'], 2);
               echo $user->getLink()."&nbsp;";
               echo Html::showToolTip(
                  $userdata["comment"],
                  ['link' => $userdata['link']]
               );
            }
            echo "</div>";
         }
         if (isset($item_i['groups_id_tech']) && ($item_i['groups_id_tech'] > 0)) {
            echo "<div class='groups_id_tech'>";
            $group->getFromDB($item_i['groups_id_tech']);
            echo "<i class='fas fa-users' aria-hidden='true'></i>&nbsp;";
            echo $group->getLink(['comments' => true]);
            echo "</div>";
         }
         if (isset($item_i['users_id_editor']) && $item_i['users_id_editor'] > 0) {
            echo "<div class='users_id_editor' id='users_id_editor_".$item_i['users_id_editor']."'>";

            if (Entity::getUsedConfig('anonymize_support_agents', $entity)
               && Session::getCurrentInterface() == 'helpdesk'
            ) {
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  Html::convDateTime($item_i['date_mod']),
                  __("Helpdesk")
               );
            } else {
               $user->getFromDB($item_i['users_id_editor']);
               $userdata = getUserName($item_i['users_id_editor'], 2);
               echo sprintf(
                  __('Last edited on %1$s by %2$s'),
                  Html::convDateTime($item_i['date_mod']),
                  $user->getLink()
               );
               echo Html::showToolTip($userdata["comment"],
                                      ['link' => $userdata['link']]);
            }

            echo "</div>";
         }
         if ($objType == 'Ticket' && isset($item_i['sourceitems_id']) && $item_i['sourceitems_id'] > 0) {
            echo "<div id='sourceitems_id_".$item_i['sourceitems_id']."'>";
            echo sprintf(
               __('Merged from Ticket %1$s'),
               Html::link($item_i['sourceitems_id'], Ticket::getFormURLWithID($item_i['sourceitems_id']))
            );
            echo "</div>";
         }
         if ($objType == 'Ticket' && isset($item_i['sourceof_items_id']) && $item_i['sourceof_items_id'] > 0) {
            echo "<div id='sourceof_items_id_".$item_i['sourceof_items_id']."'>";
            echo sprintf(
               __('Promoted to Ticket %1$s'),
               Html::link($item_i['sourceof_items_id'], Ticket::getFormURLWithID($item_i['sourceof_items_id']))
            );
            echo "</div>";
         }
         if (strpos($item['type'], 'Validation') > 0 &&
            (isset($item_i['can_answer']) && $item_i['can_answer'])) {
            $form_url = $item['type']::getFormURL();
            echo "<form id='validationanswers_id_{$item_i['id']}' class='center' action='$form_url' method='post'>";
            echo Html::hidden('id', ['value' => $item_i['id']]);
            echo Html::hidden('users_id_validate', ['value' => $item_i['users_id_validate']]);
            Html::textarea([
               'name'   => 'comment_validation',
               'rows'   => 5
            ]);
            echo "<button type='submit' class='submit approve' name='approval_action' value='approve'>";
            echo "<i class='far fa-thumbs-up'></i>&nbsp;&nbsp;".__('Approve')."</button>";

            echo "<button type='submit' class='submit refuse very_small_space' name='approval_action' value='refuse'>";
            echo "<i class='far fa-thumbs-down'></i>&nbsp;&nbsp;".__('Refuse')."</button>";
            Html::closeForm();
         }
         if ($item['type'] == 'Solution' && $item_i['status'] != CommonITILValidation::WAITING && $item_i['status'] != CommonITILValidation::NONE) {
            echo "<div class='users_id_approval' id='users_id_approval_".$item_i['users_id_approval']."'>";
            $user->getFromDB($item_i['users_id_approval']);
            $userdata = getUserName($item_i['users_id_editor'], 2);
            $message = __('%1$s on %2$s by %3$s');
            $action = $item_i['status'] == CommonITILValidation::ACCEPTED ? __('Accepted') : __('Refused');
            echo sprintf(
               $message,
               $action,
               Html::convDateTime($item_i['date_approval']),
               $user->getLink()
            );
            echo Html::showToolTip($userdata["comment"],
                                   ['link' => $userdata['link']]);
            echo "</div>";
         }

         echo "</div>"; // b_right

         if ($item['type'] == 'Document_Item') {

            
            if ($item_i['filename']) {
               $filename = $item_i['filename'];
               $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
               echo "<img src='";
               if (empty($filename)) {
                  $filename = $item_i['name'];
               }
               if (file_exists(GLPI_ROOT."/pics/icones/$ext-dist.png")) {
                  echo $CFG_GLPI['root_doc']."/pics/icones/$ext-dist.png";
               } else {
                  echo "$pics_url/file.png";
               }
               echo "'/>&nbsp;";
               echo '<div class="thumbnail">';

               $docsrc = $CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                      ."&$foreignKey=".$this->getID();
               echo Html::link($filename, $docsrc, ['target' => '_blank']);
               $docpath = GLPI_DOC_DIR . '/' . $item_i['filepath'];
               if (Document::isImage($docpath)) {
                  $imgsize = getimagesize($docpath);
                  echo Html::imageGallery([
                     [
                        'src' => $docsrc,
                        'w'   => $imgsize[0],
                        'h'   => $imgsize[1],
                     ]
                  ]);
               }
               echo '</div>';
            }
            
            // if ($item_i['link']) {
            //    echo "<a href='{$item_i['link']}' target='_blank'><i class='fa fa-external-link'></i>{$item_i['name']}</a>";
            // }
            // if (!empty($item_i['mime'])) {
            //    echo "&nbsp;(".$item_i['mime'].")";
            // }
            // echo "<span class='buttons'>";
            // echo "<a href='".Document::getFormURLWithID($item_i['id'])."' class='edit_document fa fa-eye pointer' title='".
            //        _sx("button", "Show")."'>";
            // echo "<span class='sr-only'>" . _sx('button', 'Show') . "</span></a>";

            // $doc = new Document();
            // $doc->getFromDB($item_i['id']);
            // if ($doc->can($item_i['id'], UPDATE)) {
            //    echo "<a href='".static::getFormURL().
            //          "?delete_document&documents_id=".$item_i['id'].
            //          "&$foreignKey=".$this->getID()."' class='delete_document fas fa-trash-alt pointer' title='".
            //          _sx("button", "Delete permanently")."'>";
            //    echo "<span class='sr-only'>" . _sx('button', 'Delete permanently')  . "</span></a>";
            // }
            // echo "</span>";
         }

         echo "</div>"; // displayed_content
         echo "</div>"; //end h_content

         echo "</div>"; //end  h_info

         $timeline_index++;

         Plugin::doHook('post_show_item', ['item' => $obj, 'options' => $options]);

      } // end foreach timeline

      // echo "<div class='break'></div>";

      // // recall content
      // echo "<div class='h_item middle'>";

      // echo "<div class='h_info'>";
      // echo "<div class='h_date'><i class='far fa-clock'></i>".Html::convDateTime($this->fields['date'])."</div>";
      // echo "<div class='h_user'>";

      // $user = new User();
      // $display_requester = false;
      // $requesters = $this->getUsers(CommonITILActor::REQUESTER);
      // if (count($requesters) === 1) {
      //    $requester = reset($requesters);
      //    if ($requester['users_id'] > 0) {
      //       // Display requester identity only if there is only one requester
      //       // and only if it is not an anonymous user
      //       $display_requester = $user->getFromDB($requester['users_id']);
      //    }
      // }

      // echo "<div class='tooltip_picture_border'>";
      // $picture = "";
      // if ($display_requester && isset($user->fields['picture'])) {
      //    $picture = $user->fields['picture'];
      // }
      // echo "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
      // User::getThumbnailURLForPicture($picture)."'>";
      // echo "</div>";

      // if ($display_requester) {
      //    echo $user->getLink()."&nbsp;";
      //    $reqdata = getUserName($user->getID(), 2);
      //    echo Html::showToolTip(
      //       $reqdata["comment"],
      //       ['link' => $reqdata['link']]
      //    );
      // } else {
      //    echo _n('Requester', 'Requesters', count($requesters));
      // }

      // echo "</div>"; // h_user
      // echo "</div>"; //h_info

      // echo "<div class='h_content ITILContent'>";
      // echo "<div class='displayed_content'>";
      // echo "<div class='b_right'>";

      // if ($objType == 'Ticket') {
      //    $result = $DB->request([
      //       'SELECT' => ['id', 'itemtype', 'items_id'],
      //       'FROM'   => ITILFollowup::getTable(),
      //       'WHERE'  => [
      //          'sourceof_items_id'  => $this->fields['id'],
      //          'itemtype'           => static::getType()
      //       ]
      //    ])->next();
      //    if ($result) {
      //       echo Html::link(
      //          '',
      //          static::getFormURLWithID($result['items_id']) . '&forcetab=Ticket$1#viewitemitilfollowup' . $result['id'], [
      //             'class' => 'fa fa-code-branch control_item disabled',
      //             'title' => __('Followup promotion source')
      //          ]
      //       );
      //    }
      // }
      // echo sprintf(__($objType."# %s description"), $this->getID());
      // echo "</div>";

      // echo "<div class='title'>";
      // echo Html::setSimpleTextContent($this->fields['name']);
      // echo "</div>";

      // echo "<div class='rich_text_container'>";
      // $richtext = Html::setRichTextContent('', $this->fields['content'], '', true);
      // $richtext = Html::replaceImagesByGallery($richtext);
      // echo $richtext;
      // echo "</div>";

      // echo "</div>"; // h_content ITILContent

      // echo "</div>"; // .displayed_content
      // echo "</div>"; // h_item middle

      echo "<div class='break'></div>";

      // end timeline
      echo "</div>"; // h_item $user_position

      echo "<script type='text/javascript'>$(function() {read_more();});</script>";
   }

   function showApprobationForm($itilobject) {

      if (($itilobject->fields["status"] == CommonITILObject::SOLVED)
          && $itilobject->canApprove()
          && $itilobject->isAllowedStatus($itilobject->fields['status'], CommonITILObject::CLOSED)) {


         echo "<form class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed' name='form' method='post' action='../../../../front/itilfollowup.form.php'>";

         echo"<div class='form-group m-form__group row d-flex justify-content-center'>";

            echo"<label class='col-lg12 col-form-label'>".__('Approval of the solution')."</label>";

            echo"<div class='col-lg-12'>";
               echo "<textarea class='form-control m-input' name='content' cols='70' rows='6'></textarea>";
               echo "<input type='hidden' name='itemtype' value='".$itilobject->getType()."'>";
               echo "<input type='hidden' name='items_id' value='".$itilobject->getField('id')."'>";
               echo "<input type='hidden' name='requesttypes_id' value='".
                     RequestType::getDefault('followup')."'>";
            echo"</div>";

            echo"<div class='col-lg-6 d-flex justify-content-end pt-3'>";
            echo "<input type='submit' name='add_reopen' value=\"".__('Refuse the solution')."\"
            class='submit'>";
            echo"</div>";


            echo"<div class='col-lg-6 d-flex justify-content-start pt-3'>";
            echo "<input type='submit' name='add_close' value=\"".__('Approve the solution')."\"
            class='submit'>";
            echo"</div>";


         echo"</div>";
            
            // echo'<div class="form-group m-form__group row d-flex justify-content-center">';

            //    echo '<label class="col-lg-1 col-form-label">';


         // echo "<table class='tab_cadre_fixe'>";
         // echo "<tr><th colspan='4'>". __('Approval of the solution')."</th></tr>";

         // echo "<tr class='tab_bg_1'>";
         // echo "<td colspan='2'>".__('Comments')."<br>(".__('Optional when approved').")</td>";
         // echo "<td class='center middle' colspan='2'>";
         // echo "<textarea class='form-control m-input' name='content' cols='70' rows='6'></textarea>";
         // echo "<input type='hidden' name='itemtype' value='".$itilobject->getType()."'>";
         // echo "<input type='hidden' name='items_id' value='".$itilobject->getField('id')."'>";
         // echo "<input type='hidden' name='requesttypes_id' value='".
         //        RequestType::getDefault('followup')."'>";
         // echo "</td></tr>\n";

         // echo "<tr class='tab_bg_2'>";
         // echo "<td class='tab_bg_2 center' colspan='2' width='200'>\n";
         // echo "<input type='submit' name='add_reopen' value=\"".__('Refuse the solution')."\"
         //        class='submit'>";
         // echo "</td>\n";
         // echo "<td class='tab_bg_2 center' colspan='2'>\n";
         // echo "<input type='submit' name='add_close' value=\"".__('Approve the solution')."\"
         //        class='submit'>";
         // echo "</td></tr>\n";
         // echo "</table>";
         Html::closeForm();
      }

      return true;
   }

}