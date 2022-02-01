<?php

class PluginServicesService extends CommonDBTM {

    static $rightname = "plugin_services";

    function showForm($ID, $options = []) {


        global $CFG_GLPI;

        $year          = date("Y")-1;
        $_GET["date1"] = date("Y-m-d", mktime(1, 0, 0, (int)date("m"), (int)date("d"), $year));
        $_GET["date2"] = date("Y-m-d");

        $stat = new Stat();

        if (!$item = getItemForItemtype("Ticket")) {
            exit;
        }

        ///////// Stats nombre intervention
        $values = [];
        // Total des interventions
        $values['total']   = Stat::constructEntryValues("Ticket", "inter_total", $_GET["date1"],
                                                        $_GET["date2"]);
        // Total des interventions résolues
        $values['solved']  = Stat::constructEntryValues("Ticket", "inter_solved", $_GET["date1"],
                                                        $_GET["date2"]);
        // Total des interventions closes
        $values['closed']  = Stat::constructEntryValues("Ticket", "inter_closed", $_GET["date1"],
                                                        $_GET["date2"]);
        // Total des interventions closes
        $values['late']    = Stat::constructEntryValues("Ticket", "inter_solved_late",
                                                        $_GET["date1"], $_GET["date2"]);

        if (!isset($_GET["start"])) {
            $_GET["start"] = 0;
        }

        if (!isset($_GET["type"])) {
            $_GET["type"] = "groups_id_assign";
        }

        $_GET["value2"] = 0;
        $_GET['showgraph'] = 0;

        $stat = new Stat();

        $requester = ['user'               => ['title' => _n('Requester', 'Requesters', 1)],
                        'users_id_recipient' => ['title' => __('Writer')],
                        'group'              => ['title' => Group::getTypeName(1)],
                        'group_tree'         => ['title' => __('Group tree')],
                        'usertitles_id'      => ['title' => _x('person', 'Title')],
                        'usercategories_id'  => ['title' => __('Category')]];
        
        $caract    = ['itilcategories_id'   => ['title' => __('Category')],
                        'itilcategories_tree' => ['title' => __('Category tree')],
                        'urgency'             => ['title' => __('Urgency')],
                        'impact'              => ['title' => __('Impact')],
                        'priority'            => ['title' => __('Priority')],
                        'solutiontypes_id'    => ['title' => SolutionType::getTypeName(1)]];
        
        if ("Ticket" == 'Ticket') {
            $caract['type']            = ['title' => _n('Type', 'Types', 1)];
            $caract['requesttypes_id'] = ['title' => RequestType::getTypeName(1)];
            $caract['locations_id']    = ['title' => Location::getTypeName(1)];
            $caract['locations_tree']  = ['title' => __('Location tree')];
        }
        
        
        $items =[ _n('Requester', 'Requesters', 1)       => $requester,
                    __('Characteristics') => $caract,
                    __('Assigned to')     => ['technicien'
                        => ['title' => __('Technician as assigned')],
                    'technicien_followup'
                        => ['title' => __('Technician in tasks')],
                    'groups_id_assign'
                        => ['title' => Group::getTypeName(1)],
                    'groups_tree_assign'
                        => ['title' => __('Group tree')],
                    'suppliers_id_assign'
                        => ['title' => Supplier::getTypeName(1)]]
                ];
        
        $values2 = [];
        foreach ($items as $label => $tab) {
            foreach ($tab as $key => $val) {
                $values2[$label][$key] = $val['title'];
            }
        }


        $val    = Stat::getItems("Ticket", $_GET["date1"], $_GET["date2"], $_GET["type"],
                         $_GET["value2"]);
        $params = ['type'   => $_GET["type"],
                        'date1'  => $_GET["date1"],
                        'date2'  => $_GET["date2"],
                        'value2' => $_GET["value2"],
                        'start'  => $_GET["start"]];
                
        
        echo '<div class="m-content">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="m-portlet m-portlet--mobile m-portlet--body-progress-">
                                <div class="m-portlet__body">
                                    <div class="m-portlet__body-progress">Loading</div>';

                                    $stat->displayLineGraph(
                                        _x('Quantity', 'Number') . " - " . $item->getTypeName(Session::getPluralNumber()),
                                        array_keys($values['total']), [
                                            [
                                                'name' => _nx('ticket', 'Opened', 'Opened', Session::getPluralNumber()),
                                                'data' => $values['total']
                                            ], [
                                                'name' => _nx('ticket', 'Solved', 'Solved', Session::getPluralNumber()),
                                                'data' => $values['solved']
                                            ], [
                                                'name' => __('Late'),
                                                'data' => $values['late']
                                            ], [
                                                'name' => __('Closed'),
                                                'data' => $values['closed']
                                            ]
                                        ]
                                    );

                                echo'</div>
                            </div>

                            <!--end::Portlet-->
                        </div>


                        <div class="col-lg-12">

                            <!--begin::Portlet-->
                            <div class="m-portlet m-portlet--mobile m-portlet--body-progress-">';
                                echo'<div class="m-portlet__head">';
                                    
                                echo'</div>';
                                echo "</form>";
                                echo'<div class="m-portlet__body">';

                                if ("Ticket" == 'Ticket') {

                                    ///////// Satisfaction
                                    $values = [];
                                    $values['opensatisfaction']   = Stat::constructEntryValues("Ticket",
                                                                                               "inter_opensatisfaction",
                                                                                               $_GET["date1"], $_GET["date2"]);
                                 
                                    $values['answersatisfaction'] = Stat::constructEntryValues("Ticket",
                                                                                               "inter_answersatisfaction",
                                                                                               $_GET["date1"], $_GET["date2"]);
                                 
                                    $stat->displayLineGraph(
                                       __('Satisfaction survey') . " - " .  __('Tickets'),
                                       array_keys($values['opensatisfaction']), [
                                          [
                                             'name' => _nx('survey', 'Opened', 'Opened', Session::getPluralNumber()),
                                             'data' => $values['opensatisfaction']
                                          ], [
                                             'name' => _nx('survey', 'Answered', 'Answered', Session::getPluralNumber()),
                                             'data' => $values['answersatisfaction']
                                          ]
                                       ]
                                    );
                                 
                                    $values = [];
                                    $values['avgsatisfaction'] = Stat::constructEntryValues("Ticket",
                                                                                            "inter_avgsatisfaction",
                                                                                            $_GET["date1"], $_GET["date2"]);
                                 
                                    $stat->displayLineGraph(
                                       __('Satisfaction'),
                                       array_keys($values['avgsatisfaction']), [
                                          [
                                             'name' => __('Satisfaction'),
                                             'data' => $values['avgsatisfaction']
                                          ]
                                       ]
                                    );
                                 }

                                echo'</div>
                            </div>

                            <!--end::Portlet-->
                        </div>



                        <div class="col-lg-12">

                            <!--begin::Portlet-->
                            <div class="m-portlet m-portlet--mobile m-portlet--body-progress-">';
                            echo"<form method='get' id='mainform' name='form' action='". $CFG_GLPI["root_doc"] ."/home/'>";
                                echo'<div class="m-portlet__head">';
                                
                                    echo'<div class="m-portlet__head-caption">';
                                        Dropdown::showFromArray('type', $values2, ['value' => $_GET['type']]);
                                    echo'</div>';
                                    echo'<div class="m-portlet__head-tools">
                                        <ul class="m-portlet__nav">
                                            <li class="m-portlet__nav-item">';
                                            echo"<input type='hidden' name='itemtype' value=\"". "Ticket" ."\">";
                                            echo'<button style="font-size:14px;border-radius:5px;" name="submit" type="submit" class="btn btm-sm submit  btn-primary active m-btn m-btn--custom">'.__s('Display report').'</button>';
                                            echo'</li>
                                        </ul>
                                    </div>';
                                    
                                echo'</div>';
                                echo "</form>";
                                echo'<div class="m-portlet__body">';
                                    
                                $data = Stat::getData("Ticket", $_GET["type"], $_GET["date1"], $_GET["date2"],
                                $_GET['start'], $val, $_GET['value2']);
      
                                if (isset($data['opened']) && is_array($data['opened'])) {
                                    $count = 0;
                                    $labels = [];
                                    $series = [];
                                    foreach ($data['opened'] as $key => $val) {
                                    $newkey             = Toolbox::unclean_cross_side_scripting_deep(Html::clean($key));
                                    if ($val > 0) {
                                        $labels[] = $newkey;
                                        $series[] = ['name' => $newkey, 'data' => $val];
                                        $count += $val;
                                    }
                                    }
                            
                                    if (count($series)) {
                                    $stat->displayPieGraph(
                                        sprintf(
                                            __('Opened %1$s (%2$s)'),
                                            $item->getTypeName(Session::getPluralNumber()),
                                            $count
                                        ),
                                        $labels,
                                        $series
                                    );
                                    }
                                }

                                echo'</div>
                            </div>

                            <!--end::Portlet-->
                        </div>

                    </div>


                </div>';
    }
}