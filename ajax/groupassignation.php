<?php 
    include ('../../../inc/includes.php');

    if(isset($_POST['group_id'])) {
      $users = Group_User::getGroupUsers($_POST['group_id']);
      echo json_encode($users);
    }
  ?>