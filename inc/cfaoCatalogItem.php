<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class CfaoItem {

    static function getUserManager($category){
    
    }

    static function ticketsAssignedToMe(){
      Session::checkLoginUser();
      global $DB;

      $result = [];

      $iterator = $DB->request([
         'SELECT' => [
         'COUNT'      => 'glpi_tickets.id as nbr_tickets',
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
            'glpi_tickets.type' => 2,
            'glpi_tickets_users.type' => 2
         ],
      ]);

       $totalItems = array();
       while ($data = $iterator->next()) {
          array_push($totalItems, ['nbr_tickets'=> $data["nbr_tickets"]]);
       }
       return $totalItems;
    }


    static function incidentsAssignedToMe(){
      Session::checkLoginUser();
      global $DB;

      $result = [];

      $iterator = $DB->request([
         'SELECT' => [
         'COUNT'      => 'glpi_tickets.id as nbr_incidents',
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
            'glpi_tickets.type' => 1,
            'glpi_tickets_users.type' => 2
         ],
      ]);

       $totalItems = array();
       while ($data = $iterator->next()) {
          array_push($totalItems, ['nbr_incidents'=> $data["nbr_incidents"]]);
       }
       return $totalItems;
    }


    static function ticketsAssignedToMyGroup(){
      Session::checkLoginUser();
      global $DB;

      $result = [];

      $user = new User();

      $user->getFromDB(Session::getLoginUserID());

      $userGroupId = $user->getField('groups_id');

      $iterator = $DB->request([
         'SELECT' => [
         'COUNT'      => 'glpi_tickets.id as nbr_tickets',
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
            'glpi_groups_tickets.groups_id' => $userGroupId,
            'glpi_tickets.type' => 2,
         ],
      ]);

       $totalItems = array();
       while ($data = $iterator->next()) {
          array_push($totalItems, ['nbr_tickets'=> $data["nbr_tickets"]]);
       }
       return $totalItems;
    }


    static function incidentsAssignedToMyGroup(){
      Session::checkLoginUser();
      global $DB;

      $result = [];

      $user = new User();

      $user->getFromDB(Session::getLoginUserID());

      $userGroupId = $user->getField('groups_id');

      $iterator = $DB->request([
         'SELECT' => [
         'COUNT'      => 'glpi_tickets.id as nbr_tickets',
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
            'glpi_groups_tickets.groups_id' => $userGroupId,
            'glpi_tickets.type' => 1,
         ],
      ]);

       $totalItems = array();
       while ($data = $iterator->next()) {
          array_push($totalItems, ['nbr_tickets'=> $data["nbr_tickets"]]);
       }
       return $totalItems;
    }

    static function ticketsNotAssigned(){
      Session::checkLoginUser();
      global $DB;
   
      $result = [];

      $subq =new \QuerySubQuery([
         'SELECT'    => ['glpi_tickets_users.tickets_id'],
         'FROM'   => 'glpi_tickets_users',
         'WHERE'  => [
         'glpi_tickets_users.type'   => 2
         ]
     ]);

     $expr = 'glpi_tickets.id NOT IN ' . $subq->getQuery();

      $iterator = $DB->request([
         'SELECT' => [
            'COUNT'      => 'glpi_tickets.id as nbr_tickets',
            'glpi_tickets.type'
         ],
         'FROM'      => 'glpi_tickets',
         'LEFT JOIN' => [
            'glpi_tickets_users' => [
               'ON' => [
                  'glpi_tickets_users' => 'tickets_id',
                  'glpi_tickets'    => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_tickets.type' => 2,
            new \QueryExpression($expr)
        ],
      ]);

      $totalItems = array();
      while ($data = $iterator->next()) {
         array_push($totalItems, ['nbr_tickets'=> $data["nbr_tickets"]]);
      }
      return $totalItems;
    }


    static function incidentsNotAssigned(){
      Session::checkLoginUser();
      global $DB;
   
      $result = [];

      $subq =new \QuerySubQuery([
         'SELECT'    => ['glpi_tickets_users.tickets_id'],
         'FROM'   => 'glpi_tickets_users',
         'WHERE'  => [
         'glpi_tickets_users.type'   => 2
         ]
      ]);

      $expr = 'glpi_tickets.id NOT IN ' . $subq->getQuery();

      $iterator = $DB->request([
         'SELECT' => [
            'COUNT'      => 'glpi_tickets.id as nbr_tickets',
            'glpi_tickets.type'
         ],
         'FROM'      => 'glpi_tickets',
         'LEFT JOIN' => [
            'glpi_tickets_users' => [
               'ON' => [
                  'glpi_tickets_users' => 'tickets_id',
                  'glpi_tickets'    => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_tickets.type' => 1,
            new \QueryExpression($expr)
         ],
      ]);

      $totalItems = array();
      while ($data = $iterator->next()) {
         array_push($totalItems, ['nbr_tickets'=> $data["nbr_tickets"]]);
      }
      return $totalItems;
    }

    static function getListItems($category, $offset){
         Session::checkLoginUser();
        if($category==0){
            global $DB;

            $result = [];
    
            $criteria = [
                'FROM'      => 'glpi_plugin_services_items',
                'ORDER'    => 'name DESC',
                
             ];

             $totalRows = $DB->request($criteria);
             $rowCount = count($totalRows);
             $nbrPage = ceil($rowCount/10);
             
    
             if($offset>0){
                 $page = $offset/10;
             }else{
                 $page = 0;
             }
             
       
             $criteria['START'] = (int)$offset;
             $criteria['LIMIT'] = 6;
    
             $main_iterator = $DB->request($criteria);
       
    
          $resultArray = array();
          $totalIncident = array();
          $arrayParams  = ['page'=> $page, 'nbr_page'=> $nbrPage];
          while ($data = $main_iterator->next()) {
            array_push($totalIncident, ['is_active'=> $data["is_active"], 'id'=> $data["id"], 'name'=> $data["name"], 'description'=> $data["description"], 'short_description'=> $data["short_description"], 'image'=> $data["path"]]);
          }
    
          array_push($resultArray,$arrayParams);
          array_push($resultArray,$totalIncident);
          return $resultArray;
    
    
            //  $totalItems = array();
            //  while ($data = $iterator->next()) {
            //     array_push($totalItems, ['id'=> $data["id"], 'name'=> $data["name"], 'description'=> $data["description"], 'short_description'=> $data["short_description"], 'image'=> $data["image"]]);
            //  }
            //  return $totalItems;
        }else if($category>0){
            global $DB;

            $result = [];
    
            $criteria = [
                'FROM'      => 'glpi_plugin_services_items',
                'ORDER'    => 'id DESC',
                'WHERE'     => [
                    'glpi_plugin_services_items.category' => $category
                ],
             ];

             $totalRows = $DB->request($criteria);
             $rowCount = count($totalRows);
             $nbrPage = ceil($rowCount/6);
             
    
             if($offset>0){
                 $page = $offset/6;
             }else{
                 $page = 0;
             }
             
       
             $criteria['START'] = (int)$offset;
             $criteria['LIMIT'] = 6;
    
             $main_iterator = $DB->request($criteria);
       
    
          $resultArray = array();
          $totalIncident = array();
          $arrayParams  = ['page'=> $page, 'nbr_page'=> $nbrPage];
          while ($data = $main_iterator->next()) {
            array_push($totalIncident, ['is_active'=> $data["is_active"], 'id'=> $data["id"], 'name'=> $data["name"], 'description'=> $data["description"], 'short_description'=> $data["short_description"], 'image'=> $data["path"]]);
          }
    
          array_push($resultArray,$arrayParams);
          array_push($resultArray,$totalIncident);
          return $resultArray;             

            //  $totalItems = array();
            //  while ($data = $iterator->next()) {
            //     array_push($totalItems, ['id'=> $data["id"], 'name'=> $data["name"], 'description'=> $data["description"], 'short_description'=> $data["short_description"], 'image'=> $data["image"]]);
            //  }
            //  return $totalItems;
        }

    }


    static function getUserList(){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'FROM'      => 'glpi_users',
            'WHERE'     => [
                'glpi_users.is_active' => 1
            ],
            'ORDER'    => 'id DESC',
            'LIMIT'    => 10
         ]);

         $totalUser = array();
         while ($data = $iterator->next()) {
            array_push($totalUser, ['id'=> $data["id"], 'name'=> $data["name"]]);
         }
         return $totalUser;
    }

    static function getListType(){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'FROM'      => 'glpi_plugin_services_type_variables',
            'ORDER'    => 'name ASC'
         ]);

         $totalItems = array();
         while ($data = $iterator->next()) {
            array_push($totalItems, ['id'=> $data["id"], 'name'=> $data["name"], 'display_name'=> $data["display_name"]]);
         }
         return $totalItems;
    }


    static function getAllMateriel(){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'FROM'      => 'glpi_computers',
            'ORDER'    => 'name ASC'
         ]);

         $totalItems = array();
         while ($data = $iterator->next()) {
            array_push($totalItems, ['id'=> $data["id"], 'name'=> $data["name"]]);
         }
         return $totalItems;
    }

   static function showItemsByUserID($tech, $ID) {
      
      global $DB, $CFG_GLPI;
      
      if ($tech) {
         $type_user   = $CFG_GLPI['linkuser_tech_types'];
         $type_group  = $CFG_GLPI['linkgroup_tech_types'];
         $field_user  = 'users_id_tech';
         $field_group = 'groups_id_tech';
      } else {
         $type_user   = $CFG_GLPI['linkuser_types'];
         $type_group  = $CFG_GLPI['linkgroup_types'];
         $field_user  = 'users_id';
         $field_group = 'groups_id';
      }
      // Dans la variable $type_user, on récupére l'ensemble des équipements existant dans le parc.
      
      $group_where = "";
      $groups      = [];
      
      $iterator = $DB->request([
         'SELECT'    => [
            'glpi_groups_users.groups_id',
            'glpi_groups.name'
         ],
         'FROM'      => 'glpi_groups_users',
         'LEFT JOIN' => [
            'glpi_groups' => [
               'FKEY' => [
                  'glpi_groups_users'  => 'groups_id',
                  'glpi_groups'        => 'id'
               ]
            ]
         ],
         'WHERE'     => ['glpi_groups_users.users_id' => $ID]
      ]);
      $number = count($iterator);
      
      $group_where = [];
      while ($data = $iterator->next()) {
         $group_where[$field_group][] = $data['groups_id'];
         $groups[$data["groups_id"]] = $data["name"];
      }


      foreach ($type_user as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         if ($item->canView()) {
            $itemtable = getTableForItemType($itemtype);
            $iterator_params = [
               'FROM'   => $itemtable, // Ici on fait un select sur la premiere table item => glpi_computers
               'WHERE'  => [$field_user => $ID] // telque users_id= $ID
            ];
            // On récupére tous les éléments associés à l'utilisateur $ID de la table computer
            if ($item->maybeTemplate()) {
               $iterator_params['WHERE']['is_template'] = 0;
            }
            if ($item->maybeDeleted()) {
               $iterator_params['WHERE']['is_deleted'] = 0;
            }

            $item_iterator = $DB->request($iterator_params);
            
            $type_name = $item->getTypeName();
            $totalItems = array();
            while ($data = $iterator->next()) {
               array_push($totalItems, ['id'=> $data["id"], 'name'=> $data["name"]]);
            }
            return $totalItems;
         }
      }
      if ($number) {
         echo $header;
      }
     
   }


   static function getUserSupervisor($userId){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'SELECT' => [
                'users_id_supervisor',
             ],
            'FROM'      => 'glpi_users',
            'WHERE'     => [
                'glpi_users.id' => $userId
            ],
         ]);

         $supervisor = array();
         if ($data = $iterator->next()) {

            $iteratorSupervisor = $DB->request([
                'SELECT' => [
                    'id',
                    'firstname',
                    'realname',
                 ],
                'FROM'      => 'glpi_users',
                'WHERE'     => [
                    'glpi_users.id' => $data['users_id_supervisor']
                ],
             ]);

             
             if ($data = $iteratorSupervisor->next()) {
                array_push($supervisor, ['id'=> $data["id"], 'firstname'=> $data["firstname"], 'realname'=> $data["realname"]]);
             }
             
            
        }

        return $supervisor;
    }
    static function getItems(){
      Session::checkLoginUser();
      global $DB;

      $result = [];

      $iterator = $DB->request([
          'SELECT' => [
              'glpi_plugin_services_items.id',
              'glpi_plugin_services_items.name',
              'glpi_plugin_services_items.description',
              'glpi_plugin_services_items.short_description',
              'glpi_plugin_services_items.actif',
           ],
          'FROM'      => 'glpi_plugin_services_items',
          'ORDER'    => 'name ASC'
       ]);
       $totalItems = array();
       while ($data = $iterator->next()) {
          array_push($totalItems, ['id'=> $data["id"], 'name'=> $data["name"], 
          'description'=> $data["description"], 'short_description'=> $data["short_description"], 'actif' => $data["actif"]]);
       }
       return $totalItems;
  }

    static function getItemListVariables($id){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_plugin_services_variables.id',
                'glpi_plugin_services_variables.name',
                'glpi_plugin_services_variables.display_name',
                'glpi_plugin_services_variables.type',
                'glpi_plugin_services_variables.mandatory',
                'glpi_plugin_services_variables.display_name AS typeVAR'
             ],
            'FROM'      => 'glpi_plugin_services_variables',
            
            
            'WHERE'     => [
                'glpi_plugin_services_variables.item' => $id
            ],
            'ORDER'    => 'id ASC'
         ]);
         $totalItems = array();
         while ($data = $iterator->next()) {
            array_push($totalItems, ['id'=> $data["id"], 'name'=> $data["name"], 
            'display_name'=> $data["display_name"], 'type'=> $data["typeVAR"], 'mandatory' => $data["mandatory"]]);
         }
         return $totalItems;
    }

    static function getSingleItem($id){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'FROM'      => 'glpi_plugin_services_items',
            'WHERE'     => [
                'glpi_plugin_services_items.id' => $id
             ]
         ]);
   
         
         $totalItems = array();
         if ($data = $iterator->next()) {
            array_push($totalItems, ['is_active'=> $data["is_active"], 'id'=> $data["id"], 'name'=> $data["name"], 'description'=> $data["description"], 'short_description'=> $data["short_description"], 'image'=> $data["path"]]);
         }
         return $totalItems;
    }

    static function displayListVariables($id){
      
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_plugin_services_variables.id',
                'glpi_plugin_services_variables.name',
                'glpi_plugin_services_variables.display_name',
                'glpi_plugin_services_variables.type',
                'glpi_plugin_services_variables.mandatory',
                'glpi_plugin_services_type_variables.display_name AS typeVAR'
             ],
            'FROM'      => 'glpi_plugin_services_variables',
            'INNER JOIN' => [
                'glpi_plugin_services_type_variables' => [
                   'ON' => [
                      'glpi_plugin_services_variables' => 'type',
                      'glpi_plugin_services_type_variables'    => 'name'
                   ]
                ]
             ],
            'WHERE'     => [
                'glpi_plugin_services_variables.item' => intval($id)
            ],
            'ORDER'    => 'id ASC'
         ]);

         $totalItems = array();
         while ($data = $iterator->next()) {
            array_push($totalItems, ['id'=> $data["id"], 'name'=> $data["name"], 'display_name'=> $data["display_name"], 'type'=> $data["typeVAR"], 'mandatory'=>$data["mandatory"]]);
         }
         /* print_r($totalItems); */
         
         return $totalItems;
    }

    static function getChoiceListVariable($id){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'FROM'      => 'glpi_plugin_ywz_choice_list_variables',
            'WHERE'     => [
                'glpi_plugin_ywz_choice_list_variables.variable' => $id
             ]
         ]);

         $totalChoice = array();
         while ($data = $iterator->next()) {
            array_push($totalChoice, ['id'=> $data["id"], 'label'=> $data["label"], 'value'=> $data["value"]]);
         }
         return $totalChoice;
    }

    static function getListeTables(){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'FROM'      => 'glpi_plugin_services_tables',
            'ORDER'    => 'name ASC'
         ]);

         $totalTables = array();
         while ($data = $iterator->next()) {
            array_push($totalTables, ['id'=> $data["id"], 'name'=> $data["name"], 'display_name'=> $data["display_name"]]);
         }
         return $totalTables;
    }


    static function getDataTable($idVariable){
        //print_r("hello");
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'SELECT' => [
                'glpi_plugin_services_tables.name AS name_table',
             ],
            'FROM'      => 'glpi_plugin_ywz_reference_variables',
            'INNER JOIN' => [
                'glpi_plugin_services_tables' => [
                   'ON' => [
                      'glpi_plugin_ywz_reference_variables' => 'table_ref',
                      'glpi_plugin_services_tables'    => 'id'
                   ]
                ]
             ],
             'WHERE'     => [
                'glpi_plugin_ywz_reference_variables.variable' => $idVariable
             ]
         ]);

        
         $totalData = array();
         if ($data = $iterator->next()) {

            $iterator = $DB->request([
                'FROM'      => $data['name_table'],
                'ORDER'    => 'name ASC'
             ]);
             
             while ($data = $iterator->next()) {
                array_push($totalData, ["id"=> $data["id"], "name"=> $data["name"]]);
             }
            
         }
         return $totalData;
    }



    static function getDemandes(){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'SELECT'    => ['glpi_tickets.id',
                            'name',
                            'date_creation',
                            'status'],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_tickets_users',
            'INNER JOIN' => [
               'glpi_tickets' => [
                  'ON' => [
                     'glpi_tickets_users' => 'tickets_id',
                     'glpi_tickets'    => 'id'
                  ]
               ]
            ],
            'WHERE'     => [
               'glpi_tickets.type' => 2,
               'glpi_tickets_users.users_id' => Session::getLoginUserID()
            ],
            'ORDER'    => 'date_creation DESC',
            'LIMIT'    => 5
         ]);

         $totalIncident = array();
         while ($data = $iterator->next()) {
            array_push($totalIncident, ['id'=> $data["id"], 'name'=> $data["name"], 'date_creation'=> $data["date_creation"], 'status'=> $data["status"]]);
         }
         return $totalIncident;
    }


    
    static function getAllDemandes($offset){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $criteria = [
            'SELECT'    => ['glpi_tickets.id',
            'name',
            'date_creation',
            'status',],
            'DISTINCT'  => true,
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
               'glpi_tickets.type' => 2,
               'glpi_tickets_users.users_id' => Session::getLoginUserID()
            ],
            'ORDER'    => 'date_creation DESC',
         ];

            $totalRows = $DB->request($criteria);
            $rowCount = count($totalRows);
            $nbrPage = ceil($rowCount/10);
            

            if($offset>0){
                $page = $offset/10;
            }else{
                $page = 0;
            }
            
      
            $criteria['START'] = (int)$offset;
            $criteria['LIMIT'] = 10;

            $main_iterator = $DB->request($criteria);
      

         $resultArray = array();
         $totalIncident = array();
         $arrayParams  = ['page'=> $page, 'nbr_page'=> $nbrPage];
         while ($data = $main_iterator->next()) {
            array_push($totalIncident, ['id'=> $data["id"], 'name'=> $data["name"], 'date_creation'=> $data["date_creation"], 'status'=> $data["status"]]);
         }

         array_push($resultArray,$arrayParams);
         array_push($resultArray,$totalIncident);
         return $resultArray;
    }

    static function getAllTickets($offset){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $criteria = [
            'SELECT' => [
                'glpi_tickets.id as ticket_id',
                'glpi_tickets.name as ticket_name',
                'glpi_tickets.date_creation',
                'glpi_tickets.status',
                'glpi_tickets.priority'
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
               'glpi_tickets.type' => 1,
               'glpi_tickets_users.users_id' => Session::getLoginUserID()
            ],
            'ORDER'    => 'date_creation DESC',
         ];

         $totalRows = $DB->request($criteria);
         $rowCount = count($totalRows);
         $nbrPage = ceil($rowCount/10);
         

         if($offset>0){
             $page = $offset/10;
         }else{
             $page = 0;
         }
         
   
         $criteria['START'] = (int)$offset;
         $criteria['LIMIT'] = 10;

         $main_iterator = $DB->request($criteria);
   

      $resultArray = array();
      $totalIncident = array();
      $arrayParams  = ['page'=> $page, 'nbr_page'=> $nbrPage];
      while ($data = $main_iterator->next()) {
         array_push($totalIncident, ['ticket_id'=> $data["ticket_id"], 'ticket_name'=> $data["ticket_name"], 'date_creation'=> $data["date_creation"], 'status'=> $data["status"], 'priority'=> $data["priority"]]);
      }

      array_push($resultArray,$arrayParams);
      array_push($resultArray,$totalIncident);
      return $resultArray;
    }


    static function getTickets(){
      Session::checkLoginUser();
      global $DB;

      $result = [];

      $iterator = $DB->request([
          'SELECT' => [
              'glpi_tickets.id as ticket_id',
              'glpi_tickets.name as ticket_name',
              'glpi_tickets.date_creation',
              'glpi_tickets.status',
              'glpi_tickets.priority'
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
             'glpi_tickets.type' => 1,
             'glpi_tickets_users.users_id' => Session::getLoginUserID()
          ],
          'ORDER'    => 'date_creation DESC',
          'LIMIT'    => 5
       ]);

       $totalIncident = array();
       while ($data = $iterator->next()) {
          array_push($totalIncident, ['ticket_id'=> $data["ticket_id"], 'ticket_name'=> $data["ticket_name"], 'date_creation'=> $data["date_creation"], 'status'=> $data["status"], 'priority'=> $data["priority"]]);
       }
       return $totalIncident;
  }


    static function getItemCategory(){
        Session::checkLoginUser();
        global $DB;
        $result = [];
        $iterator = $DB->request([
            'FROM'      => 'glpi_plugin_services_category',
            'ORDER'    => 'short_description ASC'
         ]);

         $totalCategory = array();
         while ($data = $iterator->next()) {
            array_push($totalCategory, ['id'=> $data["id"], 'name'=> $data["short_description"]]);
         }
         return $totalCategory;
    }

    static function getAllCategory(){
        Session::checkLoginUser();
        global $DB;

        $result = [];

        $iterator = $DB->request([
            'FROM'      => 'glpi_plugin_services_category',
            'ORDER'    => 'short_description ASC'
         ]);

         $totalTables = array();
         while ($data = $iterator->next()) {

            $iteratorSubs = $DB->request([
                'SELECT' => [
                    'glpi_plugin_services_items.category',
                    'COUNT'  => 'glpi_plugin_services_items.id AS nb_subs'
                 ],
                'FROM'      => 'glpi_plugin_services_items',
                'WHERE'     => ['glpi_plugin_services_items.category' => $data["id"]],
                'ORDER'    => 'short_description ASC'
             ]);
            $dataSubs = $iteratorSubs->next();
            array_push($totalTables, ['id'=> $data["id"], 'nb_subs'=> $dataSubs["nb_subs"], 'name'=> $data["short_description"]]);
         }
         return $totalTables;
    }
}