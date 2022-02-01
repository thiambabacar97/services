<?php
include('../../../inc/includes.php');

global $DB;


if(isset($_GET['chart']) && $_GET['chart']=='get_tickets_in_progess'){
    get_tickets_in_progess($DB, $_GET['entity']);

}else if(isset($_GET['chart']) && $_GET['chart']=='get_tickets_due_date'){
    get_tickets_due_date($DB, $_GET['entity']);

}else if(isset($_GET['chart']) && $_GET['chart']=='get_tickets_par_status'){
    get_tickets_par_status($DB, $_GET['entity']);

}else if(isset($_GET['chart']) && $_GET['chart']=='get_tickets_par_canal'){
    get_tickets_par_canal($DB, $_GET['entity']);

}


function get_tickets_in_progess($DB, $entity){

    $iterator = $DB->request([
    'SELECT' => [
    'glpi_tickets.id AS id'
    ],
    'FROM'      => 'glpi_tickets',
    'WHERE'     => [
        'glpi_tickets.type' => 1,
        'entities_id' => $entity,
        'glpi_tickets.status' => 2
    ],
    
    ]);
    
    $count = 0;

    $count = count($iterator);

    echo json_encode(['count'=> $count]);
}



function get_tickets_due_date($DB, $entity){

    $iterator = $DB->request([
    'SELECT' => [
    'glpi_tickets.id AS id'
    ],
    'FROM'      => 'glpi_tickets',
    'WHERE'     => [
        'glpi_tickets.type' => 1,
        'entities_id' => $entity,
        'glpi_tickets.time_to_resolve' => ['<', new QueryExpression('NOW()')]
    ]
    
    ]);
    
    $count = 0;

    $count = count($iterator);

    echo json_encode(['count'=> $count]);


}



function get_tickets_par_status($DB, $entity){

    $iterator = $DB->request([
    'SELECT' => [
    'COUNT'      => 'glpi_tickets.id as nbr_tickets',
    'glpi_tickets.status AS status'
    ],
    'FROM'      => 'glpi_tickets',
    'WHERE'     => [
        'glpi_tickets.type' => 1,
        'entities_id' => $entity
    ],
    'GROUPBY'   => 'glpi_tickets.status'
    
    ]);
    
    $keys = [];
    $values = [];

    $count = count($iterator);

    while ($data = $iterator->next()) {

        if($data["status"]==1){
            $status = 'Nouveau';  
        }else if($data["status"]==2){
            $status = 'En cours (Attribué)';  
        }else if($data["status"]==3){
            $status = 'En cours (Planifié)';  
        }else if($data["status"]==4){
            $status = 'En attente';  
        }else if($data["status"]==5){
            $status = 'Résolu';  
        }else if($data["status"]==6){
            $status = 'Clos';  
        }


        array_push($keys, $status);
        array_push($values, $data["nbr_tickets"]);
    }

    echo json_encode(['keys'=> $keys, 'values'=> $values, 'count'=> $count]);
}



function get_tickets_par_canal($DB, $entity){

    $iterator = $DB->request([
    'SELECT' => [
    'COUNT'      => 'glpi_tickets.id as nbr_tickets',
    'glpi_tickets.requesttypes_id AS canal'
    ],
    'FROM'      => 'glpi_tickets',
    'WHERE'     => [
        'glpi_tickets.type' => 1,
        'entities_id' => $entity
    ],
    'GROUPBY'   => 'glpi_tickets.requesttypes_id'
    
    ]);
    
    $keys = [];
    $values = [];
    $status = '';

    $count = count($iterator);

    while ($data = $iterator->next()) {

        if($data["canal"]==1){
            $status = 'Helpdesk';  
        }else if($data["canal"]==2){
            $status = 'E-Mail';  
        }else if($data["canal"]==3){
            $status = 'Phone';  
        }else if($data["canal"]==4){
            $status = 'Direct';  
        }else if($data["canal"]==5){
            $status = 'Written';  
        }else if($data["canal"]==6){
            $status = 'Other';  
        }


        array_push($keys, $status);
        array_push($values, $data["nbr_tickets"]);
    }

    echo json_encode(['keys'=> $keys, 'values'=> $values, 'count'=> $count]);
}

?>
