


<?php
	include('../../../../inc/includes.php');
	PluginServicesHtml::nullHeader("Login", $CFG_GLPI["root_doc"] . '/index.php');
?>	
<?php
    include ('aside_menu.php');
?>

<div class="m-grid__item m-grid__item--fluid m-grid m-grid--ver-desktop m-grid--desktop m-body">
<?php
    include('header_dash.php');
?>
<?php
    global $DB;
    $result = [];
    
    $iterator = $DB->request([
        'SELECT' => [
            'glpi_plugin_services_facturation.prix',
            'glpi_itilcategories.name',
            'glpi_itilcategories.id',
            
        ],
        'DISTINCT'  => true,
        'FROM' => 'glpi_plugin_services_facturation',
        'INNER JOIN' => [
            'glpi_itilcategories'=>[
                'ON' => [
                    'glpi_plugin_services_facturation' => 'id_categorie',
                    'glpi_itilcategories' => 'id',
                ]
            ]
        ]
    ]);
    
    while($data = $iterator->next()){
        
        array_push($result, ['prix' => $data['prix'],'id' => $data['id'],'name' => $data['name']]);
    }
?>
<div class="m-grid__item m-grid__item--fluid m-wrapper">
    
    <div class="m-content">
        <div class="m-portlet">
            <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                    <div class="m-portlet__head-title">
                        <h3 class="m-portlet__head-text">
                            Déclarer un incident
                        </h3>
                    </div>
                </div>
                <div class="m-portlet__head-tools">
                    <ul class="m-portlet__nav">

                    </ul>
                </div>
            </div>
        </div>

        <div class="m-portlet" id='list_view'>
            <div class="m-portlet__body">

            <?php
                
            
                $entities = Profile_User::getUserEntities(Session::getLoginUserID(), true);
                $entity = new Entity();
                echo '<form method="post" action="'. $CFG_GLPI["root_doc"] .'/plugins/services/crud/ticket.form.php">';
                echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);
                $ticket = new PluginServicesTicket();
                $ticket->showImpactUrgencePriority('',[]); 
                echo'<div class="row d-flex custom_select d-flex justify-content-center">

                        <div class="col-xl-4">';
                            echo "<div class='form-group m-form__group'>
                                <label for='exampleSelect1'>Catégories</label>
                                <select class='form-control m-input m-input--square' name='categorie' id='exampleSelect1'>";
                                    foreach($result as $values){
                                        $name = $values['name'];
                                        $id = $values['id'];
                                        $prix = $values['prix'];
                                       
                                        echo "
                                                <option name='categorie' value=$id >$name</option>
                                            ";
                                                            
                                    }     
                                    
                                echo "</select>
                            </div>
                        </div>"; 

                    echo'<div class="col-xl-4">
                            <div class="form-group">
                            <label for="nom">Observateur</label>';
                            PluginServicesUser::dropdown(['entity' => 0, 'right'  => 'all']);
                        echo '</div>
                    </div>';
                echo '</div>';

                echo"<div class='row d-flex custom_select d-flex justify-content-center'>
                        <div class='col-xl-8'>
                            <div class='form-group'>
                                <label for='description'>Description courte</label>
                                <input type='text' name='short_description' class='form-control m-input' >
                            </div>
                        </div>
                        <div class='col-xl-4' style='display:none'>
                            <div class='form-group m-form__group'>
                            <label for='exampleSelect1'>Entité</label>";
                            Entity::dropdown(['name' => 'entity','type'=> 'hidden','entity' => $_SESSION['glpiactiveentities']]);
                        echo '</div>
                        </div>
                        
                    </div>';

                echo'<div class="row d-flex justify-content-center">
                        <div class="col-xl-8">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="4"></textarea>
                            </div>
                        </div>
                    </div>';

                echo '<div class="row d-flex justify-content-center">
                    <div class="col-xl-8">
                        <div class="form-group">
                            <label for="description">Fichiers (2 Mio maximum)</label>';
                            $ticket->showDocumentInputFile();
                    echo' </div>
                </div></div>';

                echo '</div>
                <div class="m-portlet__foot">
                <div class="row align-items-center">
                    <div class="col-lg-12 m--align-right">
                        <div class="form-group">
                            <button type="submit" style="color: #fff; background-color:#1d6fa8 !important; border-color: #3da8a9" class="btn btn-md pull-right btn-primary">Soumettre</button>
                            
                        </div>
                    </div>
                </div>
                </div>';
    
                echo'</form>';

                

                ?>

            </div>
        </div>
    </div>

</div>
</div>


<?php
    PluginServicesHtml::nullFooter();
?>



