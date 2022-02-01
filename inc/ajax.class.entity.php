<?php
include('../../../inc/includes.php');

global $DB;


if(isset($_GET['chart']) && $_GET['chart']=='get_tickets_not_assigned'){
    get_tickets_not_assigned($DB, $_GET['entity']);

}else if(isset($_GET['chart']) && $_GET['chart']=='get_tickets_par_categ'){
    get_tickets_par_categ($DB, $_GET['entity']);

}else if(isset($_GET['chart']) && $_GET['chart']=='get_tickets_par_status'){
    get_tickets_par_status($DB, $_GET['entity']);

}else if(isset($_GET['chart']) && $_GET['chart']=='get_tickets_par_canal'){
    get_tickets_par_canal($DB, $_GET['entity']);

}


function get_tickets_not_assigned($DB, $entity){

    $iterator = $DB->request([
    'SELECT' => [
    'COUNT'      => 'glpi_tickets.id as nbr_tickets',
    'glpi_tickets.priority AS priority'
    ],
    'FROM'      => 'glpi_tickets',
    'WHERE'     => [
        'glpi_tickets.type' => 1,
        'entities_id' => $entity,
        [
            'OR' => [
                ['glpi_tickets.status' => 1],
                ['glpi_tickets.status' => 4],
            ]
            ],
    ],
    'GROUPBY'   => 'glpi_tickets.priority'
    
    ]);
    
    $keys = [];
    $values = [];

    $basse = 0;
    $moyenne = 0;
    $majeure = 0;
    $count = 0;
    while ($data = $iterator->next()) {
        $count = $count + $data["nbr_tickets"];
        $priority = '';
        if($data["priority"]==2 || $data["priority"]==1){
            $basse = $basse + $data["nbr_tickets"];
        }else if($data["priority"]==3 || $data["priority"]==4){
            $moyenne = $moyenne + $data["nbr_tickets"];
        }else if($data["priority"]==5 || $data["priority"]==6){
            $majeure = $majeure + $data["nbr_tickets"];
        }
    }

    $keys = ['Basse', 'Moyenne', 'Majeure'];
    $values = [$basse, $moyenne, $majeure];

    echo json_encode(['keys'=> $keys, 'values'=> $values, 'count'=> $count]);
}



function get_tickets_par_categ($DB, $entity){

    $iterator = $DB->request([
    'SELECT' => [
    'COUNT'      => 'glpi_tickets.id as nbr_tickets',
    'glpi_tickets.itilcategories_id AS categories_id'
    ],
    'FROM'      => 'glpi_tickets',
    'WHERE'     => [
        'glpi_tickets.type' => 1,
        'entities_id' => $entity
    ],
    'GROUPBY'   => 'glpi_tickets.itilcategories_id'
    
    ]);
    
    $keys = [];
    $values = [];

    $count = count($iterator);

    if($count<=0){
        echo json_encode(['keys'=> [], 'values'=> [], 'count'=> 0]);
    }else{

        while ($data = $iterator->next()) {


            $cat = new ITILCategory();
            $cat->getFromDB($data["categories_id"]);
    
            array_push($keys, $cat->getField('name'));
            array_push($values, $data["nbr_tickets"]);
        }
    
        echo json_encode(['keys'=> $keys, 'values'=> $values, 'count'=> $count]);
    }


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
