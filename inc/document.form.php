<?php
// print_r($_POST);
// return;
include('../../../inc/includes.php');
$doc = new Document();
$newID = $doc->add($_POST);
html::back();