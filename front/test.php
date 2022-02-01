<?php

include ('../../../inc/includes.php');

echo Html::css('assets/demo/default/base/style.bundle.css');
echo Html::css('assets/vendors/base/vendors.bundle.css');
//echo Html::css('assets/css/customer.glpi.form.css');
$rand_template   = mt_rand();
$rand_text       = mt_rand();
$rand_type       = mt_rand();
$rand_time       = mt_rand();
$rand_user       = mt_rand();
$rand_is_private = mt_rand();
$rand_group      = mt_rand();
$rand_state      = mt_rand();

//$this->check(-1, CREATE, $options);
$item = new TicketTask();
// Session::haveRight(self::$rightname, UPDATE);

//$rand = mt_rand();
//$this->showFormHeader($options);

echo "<form method='POST'  id='mainform' action='".$CFG_GLPI["root_doc"]."/tickettask/form/' class='m-form mainform m-form--fit m-form--label-align-right m-form--group-seperator-dashed'>
         <div class='m-portlet__body'>
            <div class='form-group m-form__group row'>
               <div class='col-lg-6'>
                     <label class='text-left'>Description courte</label>
                     <input type='text' class='form-control m-input' name='' >
               </div>";

               echo"<div class='col-lg-6'>
                        <label class='text-left'>".__('Category')."</label>";
                        TaskCategory::dropdown([
                           'condition' => ['is_active' => 1]
                        ]);
               echo"</div>";
            echo"</div>";

            echo"<div class='form-group m-form__group row'>";

                  echo"<div class='col-lg-6'>
                        <label class=''>".__('Status')."</label>";
                        Planning::dropdownState("state", 1, true, ['rand' => $rand_state]);
                  echo"</div>";

                  echo"<div class='col-lg-6'>
                        <label class=''>".__('Assigned to')."</label>";
                        $params  = ['name'   => "users_id_tech",
                                    'value'  => Session::getLoginUserID(),
                                    'right'  => self::$rightname,
                                    'rand'   => $rand_user,
                                    'width'  => ''];
                        User::dropdown($params);

                  echo"</div>
            </div>";


            echo"<div class='form-group m-form__group row'>";

                  echo"<div class='col-lg-6'>
                        <label class=''>".__('Assignment group')."</label>";
                        $params     = [
                           'name'      => "groups_id_tech",
                           'value'     => Dropdown::EMPTY_VALUE,
                           'condition' => ['is_task' => 1],
                           'rand'      => $rand_group
                        ];
                        Group::dropdown($params);
                  echo"</div>";
            echo"</div>

         </div>
         <input type='submit' id='submit_form' class='form-control m-input' name=''>
   </form>";
   echo Html::script('assets/vendors/base/vendors.bundle.js');
   echo Html::script('assets/demo/default/base/scripts.bundle.js');
echo 'hello';