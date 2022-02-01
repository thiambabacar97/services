<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginServicesTicket extends CommonGLPI {
    /**
     * This function is called from GLPI to allow the plugin to insert one or more item
     *  inside the left menu of a Itemtype.
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate=0){
        
        /* $val = 'Tacket';
        if($item->getType() == $val){ */
        return self::createTabEntry("Variables");
    }

    /**
     * This function is called from GLPI to render the form when the user click
     *  on the menu item generated from getTabNameForItem()
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0){

        global $DB;
    
        $result = [];
        
        $iterator = $DB->request([
            'SELECT' => [
                'glpi_plugin_ywz_variables.id',
                'glpi_plugin_ywz_variable_items.ticket',
                'glpi_plugin_ywz_variable_items.variable',
                'glpi_plugin_ywz_variable_items.value',
                'glpi_plugin_ywz_variable_items.item',
                'glpi_plugin_ywz_variables.name',
                'glpi_plugin_ywz_variables.display_name',
                'glpi_plugin_ywz_variables.type',
            ],
            'FROM'      => 'glpi_plugin_ywz_variable_items',
            'INNER JOIN' => [
                'glpi_plugin_ywz_variables' => [
                    'ON' => [
                        'glpi_plugin_ywz_variables' => 'id',
                        'glpi_plugin_ywz_variable_items'    => 'variable'
                    ]
                ]
                ],
            'WHERE'     => [
                'glpi_plugin_ywz_variable_items.ticket' => $item->fields['id']
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
    
    
}

?>