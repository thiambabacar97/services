<?php


if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginServicesTheme extends CommonDBTM {


    static function install(Migration $migration) {
        $menu = new self();
        $params = [
            ["sidebar_header_color"=> "#003170", 
            "sidebar_color"=> "#074ca6", 
            "sidebar_menu_color"=> "#d1d1d1"],

            ["sidebar_header_color"=> "#056303", 
            "sidebar_color"=> "#078505", 
            "sidebar_menu_color"=> "#d1d1d1"],

            ["sidebar_header_color"=> "#024e7e", 
            "sidebar_color"=> "#0a6299", 
            "sidebar_menu_color"=> "#d1d1d1"],

            ["sidebar_header_color"=> "#b5770d", 
            "sidebar_color"=> "#cb850b", 
            "sidebar_menu_color"=> "#d1d1d1"]
        ];

        foreach ($params as  $param) {
            $menu->add($param);
        }
    }

    static $rightname = 'plugin_services_themes';


    function postForm($post) {
        $item = new PluginServicesTheme();

        if(isset($_POST["update"])) {
            $item->check($_POST["id"], UPDATE);
            $item->update($_POST);
            Html::back();
        }else if(isset($_POST["add"])) {
            $item->check(-1, CREATE, $_POST);
            if ($item->add($_POST)) {
                Html::redirect($item->getLinkURL());
            }
            Html::back();
        }else if(isset($_POST["delete"])) {
            // $item->check($_POST['id'], DELETE);
            if($item->delete($_POST)) {
                $item->redirectToList();
            }
            PluginServicesHtml::back();
        }
    }

    function getLinkURL() {
        global $CFG_GLPI;

        if (!isset($this->fields['id'])) {
            return '';
        }

        $link_item = $this->getFormURL();
        $link  = $link_item;
        $link .= (strpos($link,'?') ? '&amp;':'?').'id=' . $this->fields['id'];
        $link .= ($this->isTemplate() ? "&amp;withtemplate=1" : "");

        return $link;
    }

    static function getFormURL($full = false) {
        return "/theme/form/";
    }

    static function getSearchURL($full = true) {
        return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
    }

    public static function getTypeName($nb = 0) {
        return _n('Theme', 'Theme', $nb, 'theme');
    }


    function showForm($ID, $options = []) {
        global $CFG_GLPI;

        if(!empty($ID)){
            $item = new self();
            $item->getFromDB($ID);
            $this->fields = $item->fields;
        }
        echo'
            <div class="m-content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="m-portlet m-portlet--tab">
                            <div class="m-portlet__head">
                                <div class="m-portlet__head-caption">
                                    <div class="m-portlet__head-title">
                                        <span class="m-portlet__head-icon">
                                            <i class="flaticon-plus"></i>
                                        </span>
                                        <h3 class="m-portlet__head-text">
                                            Thème
                                        </h3>
                                    </div>
                                </div>
                            </div>';
                            echo"
                                <form id='mainform' action='".$CFG_GLPI['root_doc']."/theme/form/' method='post'  class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed'>";
                                    if(!empty($ID)){
                                        // echo"<input class='form-control m-input' value='update' type='hidden' name='update'>";
                                        echo"<input class='form-control m-input' value='".$ID."' type='hidden' name='id'>";
                                    }else{
                                        // echo"<input class='form-control m-input' value='add' type='hidden' name='add'>";
                                    }
                                    echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);
                                        echo "
                                    <div class='form-group m-form__group row'>
                                        <label class='col-lg-4 col-form-label'>Marque</label>
                                        <div class='col-lg-4'>";
                                            if(!empty($ID)){
                                                echo "<input   class='form-control m-input' type='text' name='mark' value='".$this->fields['mark']."' >";
                                            }else{
                                                echo "<input   class='form-control m-input' type='text' name='mark'>";
                                            }
                                            
                                            echo"
                                        </div>
                                    </div>

                                    <div class='form-group m-form__group row'>
                                        <label class='col-lg-4 col-form-label'>Couleur entête bare latérale</label>
                                        <div class='col-lg-4'>";
                                            if(!empty($ID)){
                                                echo "<input id='sidebar_header_color' style='width: 100%; padding: .85rem 1.15rem;' class='form-control m-input' type='color' value='".$this->fields['sidebar_header_color']."' name='sidebar_header_color'>";
                                            }else{
                                                echo "<input id='sidebar_header_color' style='width: 100%; padding: .85rem 1.15rem;' class='form-control m-input' type='color' name='sidebar_header_color'>";
                                            }
                                            
                                            echo"
                                        </div>
                                    </div>

                                    <div class='form-group m-form__group row'>
                                        <label class='col-lg-4 col-form-label'>Couleur bare latérale</label>
                                        <div class='col-lg-4'>";
                                            if(!empty($ID)){
                                                echo "<input id='sidebar_color' style='width: 100%; padding: .85rem 1.15rem;' class='form-control m-input' type='color' value='".$this->fields['sidebar_color']."' name='sidebar_color'>";
                                            }else{
                                                echo "<input <input id='sidebar_color' style='width: 100%; padding: .85rem 1.15rem;' class='form-control m-input' type='color' name='sidebar_color'>";
                                            }
                                            
                                            echo"
                                        </div>
                                    </div>

                                    <div class='form-group m-form__group row'>
                                        <label class='col-lg-4 col-form-label'>Couleur bare latérale menu</label>
                                        <div class='col-lg-4'>";
                                            if(!empty($ID)){
                                                echo "<input  id='sidebar_menu_color' style='width: 100%; padding: .85rem 1.15rem;' class='form-control m-input' type='color' value='".$this->fields['sidebar_menu_color']."' name='sidebar_menu_color'>";
                                            }else{
                                                echo "<input  id='sidebar_menu_color' style='width: 100%; padding: .85rem 1.15rem;' class='form-control m-input' type='color' name='sidebar_menu_color'>";
                                            }
                                            
                                            echo"
                                        </div>
                                    </div>

                                    <div class='m-portlet__foot m-portlet__no-border m-portlet__foot--fit'>
                                        <div class='m-form__actions m-form__actions--solid'>
                                            <div class='row mt-5'>
                                                <div class='col-lg-4'></div>
                                                <div class='col-lg-4 d-flex justify-content-center'>";
                                                    if(!empty($ID)){
                                                        echo PluginServicesHtml::submit("<i class='fas fa-edit'></i>&nbsp;"._x('button', 'Save'), [
                                                        'name'    => 'update',
                                                        ]);
                                                        echo PluginServicesHtml::submit("<i class='fas fa-trash-alt'></i>&nbsp;"._x('button', 'Delete'), [
                                                        'name'    => 'delete',
                                                        'confirm' => __('Confirm the final deletion?')
                                                        ]);
                                                    }else {
                                                        echo PluginServicesHtml::submit("<i class='fas fa-plus'></i>&nbsp;"._x('button', 'Add'), [
                                                        'name'    => 'add',
                                                        ]);
                                                    }
                                                    echo"
                                                </div>
                                                <div class='col-lg-4'></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <script>
                                    $('#sidebar_header_color').change(function(){
                                    $('.m-brand.m-brand--skin-dark').css('background-color', $('#sidebar_header_color').val());

                                    $( '.m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active)' ).hover(
                                        function() {
                                        $(this).css('background', $('#sidebar_header_color').val());
                                        }, function() {
                                        $(this).css('background', $('#sidebar_color').val());
                                        }
                                    );
                                    });

                                    $('#sidebar_color').change(function(){
                                    $('.m-aside-left.m-aside-left--skin-dark').css('background-color', $('#sidebar_color').val());
                                    });

                                    $('#sidebar_menu_color').change(function(){
                                    $('.m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__heading .m-menu__link-text, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__link .m-menu__link-text').css('color', $('#sidebar_menu_color').val());
                                    $('.m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__heading .m-menu__link-icon, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__link .m-menu__link-icon').css('color', $('#sidebar_menu_color').val());
                                    });

                                
                                </script>
                            ";
                        echo'</div>
                    </div>
                </div>
            </div>
        ';
    }

    static function showList($itemtype){
        
            global $CFG_GLPI;

            echo"<div class='m-content'>

                    <div class='row'>
                        <div class='col-xl-12 '>
                            <div class='m-portlet m-portlet--tab'>
                                <div class='m-portlet__head'>
                                    <div class='m-portlet__head-caption'>
                                        <div class='m-portlet__head-title'>
                                            <span class='m-portlet__head-icon'>
                                                <i class='flaticon-list'></i>
                                            </span>
                                            <h3 class='m-portlet__head-text'>
                                                <small>". __(' Liste des thémes')."</small>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class='m-portlet__body'>
                                    <div class='m-form__section m-form__section--first'>
                                        <div class='form-group m-form__group'>


                                        <form action='".$CFG_GLPI['root_doc']."/theme/form/' method='post'  class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed'>";
                                            if(!empty($ID)){
                                                echo"<input class='form-control m-input' value='update' type='hidden' name='update'>";
                                                echo"<input class='form-control m-input' value='".$ID."' type='hidden' name='id'>";
                                            }else{
                                                echo"<input class='form-control m-input' value='add' type='hidden' name='add'>";
                                            }
                                            echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);
                                            echo "<div class='form-group m-form__group row pt-0'>
                                                        <label class='col-lg-4 col-form-label'>".__('Société')."</label>
                                                        <div class='col-lg-4'>";
                                                            if(!empty($ID)){
                                                                // echo "<input   class='form-control m-input' type='text' name='mark' value='".$this->fields['mark']."' >";
                                                            }else{
                                                                echo "<input   class='form-control m-input' type='text' name='mark'>";
                                                            }
                                                        echo"</div>
                                                    </div>

                                            <div class='m-form__actions m-form__actions--solid m-form__actions--right'>
                                                    <button type='submit' class='btn btn-primary btn-md'>Sauvegarder</button>
                                              </div>
                                        </form>

                                            <div class='row mt-4'>";

                                                
                                                $thms = new self();
                                                $thms = $thms->find();
                                                foreach ($thms as  $thm) {
                                                    $id = $thm['id'];
                                                    $active = $thm["active"]==1 ? 'checked' : '';
                                                    echo "
                                                        <div style='cursor:pointer;' onclick='' id='theme$id' class='col-lg-2 col-xl-2 mb-5'>
                                                            <div style='overflow:hidden; box-shadow: rgba(17, 17, 26, 0.05) 0px 1px 0px, rgba(17, 17, 26, 0.1) 0px 0px 8px; border-radius:4px;' class='d-flex align-content-stretch flex-wrap'>
                                                                <div style='background:".$thm['sidebar_header_color']."; height:50px; width:100%'>
                
                                                                </div>
                                                                <div class='d-flex justify-content-start' style='width:100%'>
                                                                    <div style='height:50px; width: 50%' class='d-flex justify-content-center'>
                                                                        <label>
                                                                            <span class='m-option__control'>
                                                                                <span style='left: 30%; margin:auto;' class='m-radio m-radio--brand m-radio--check-bold'>
                                                                                    <input  id='input$id' type='radio' name='m_option_1' value='1' $active>
                                                                                    <span></span>
                                                                                </span>
                                                                            </span>
                                                                        </label>
                                                                    </div>
                                                                    <div class='m-option__label' style='background:".$thm['sidebar_color']."; height:50px; width:50%'>
            
                                                                    </div>
                                                                    <div id='setting$id' style='position:absolute; display:none; top:0px;margin:5px;'>
                                                                        <div class='btn-group' style='' role='group' aria-label='Button group with nested dropdown'>
                                                                            <a href='".$CFG_GLPI['root_doc']."/theme/form/?id=$id' class='m-btn btn btn-secondary'>
                                                                                <i class='flaticon-edit'></i>
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                </div>
                                                            </div>
                                                            <script>



                                                                $('#input$id').click(function(){
                                                                    var radioValue = $('#input$id:checked').val();
                                                                    if(radioValue){
                                                                        $.post( '". $CFG_GLPI["root_doc"]."/ajax/changeTheme.php', {id: '$id',  active: 1,  _glpi_csrf_token:'".Session::getNewCSRFToken()."'}, function() {
                                                                            document.location.reload(true);
                                                                        });
                                                                    }
                                                                });
                                                                $('#setting$id').click(function(e) {
                                                                        e.stopPropagation();
                                                                });
                                                                $('#theme$id').mouseover(function(){
                                                                    $('#setting$id').show();
                                                                });
                                                                $('#theme$id').mouseout(function(){
                                                                    $('#setting$id').hide();
                                                                });
                                                                $('#theme$id').click(function(){
                                                                    $('#input$id').click();
                                                                });
                                                            </script>
                                                        </div>";
                                                }
                                            echo"
                                            <div class='col-lg-2 mb-5'>
                                                <div class='d-flex align-content-stretch flex-wrap bg-light' style='box-shadow: rgba(17, 17, 26, 0.05) 0px 1px 0px, rgba(17, 17, 26, 0.1) 0px 0px 8px; height:100px; border-radius:4px;'>
                                                    <div class='d-flex justify-content-center' style='width:100%'>
                                                        <a style='margin:auto;' href='".$CFG_GLPI['root_doc']."/theme/form' class='btn btn-outline-success m-btn m-btn--icon btn-lg m-btn--icon-only m-btn--pill m-btn--air'>
                                                            <i class='fa flaticon-app'></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>";
    }

    static function canCreate() {
        return Session::haveRight(self::$rightname, CREATE);
    }

    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }
}