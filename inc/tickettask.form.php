<?php
include('../../../inc/includes.php');

 $task = new TicketTask();

 $task->check(-1, CREATE, $_POST);
 $task->add($_POST);
 HTML::back();
?>