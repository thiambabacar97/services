<?php


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginServicesBookings extends CommonDBTM {

    static $rightname = 'plugin_services_bookings';

    function getLinkURL() {
        global $CFG_GLPI;
  
        if (!isset($this->fields['id'])) {
           return '';
        }
  
        $link_item = $this->getFormURL();
  
        $link  = $link_item;
        $link .= (strpos($link,'?') ? '&amp;':'?').'id=' . $this->fields['id'];
        $link .= ($this->isTemplate() ? "&amp;withtemplate=1" : "");
  
        return $CFG_GLPI['root_doc']."/".$link;
     }

    static function getFormURL($full = true) {
        return $CFG_GLPI['root_doc']."plugins/services/interface/front/bookings/";
    }

    static function getSearchURL($full = true) {
        return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
    }
  
    public static function getTypeName($nb = 0) {

        return _n('Bookings', 'Bookings', $nb, 'Bookings');
    }

    function showForm($ID, $options = []) {

        global $CFG_GLPI;

        if(!empty($ID)){
            $item = new PluginServicesApparence();
            $item->getFromDB($ID);
            $this->fields = $item->fields;
        }

        echo "<div class='m-portlet'>
                <form action='".$CFG_GLPI['root_doc']."/plugins/services/crud/apparence.form.php' method='post'  class='m-form m-form--fit m-form--label-align-right m-form--group-seperator-dashed'>";


                    if(!empty($ID)){
                        echo"<input class='form-control m-input' value='update' type='hidden' name='update'>";
                        echo"<input class='form-control m-input' value='".$ID."' type='hidden' name='id'>";
                    }else{
                        echo"<input class='form-control m-input' value='add' type='hidden' name='add'>";
                    }
                    echo Html::hidden("_glpi_csrf_token", ["value" => Session::getNewCSRFToken()]);
                    echo "<div class='m-portlet__body '>

                        <div class='form-group m-form__group row'>
                            <label class='col-lg-4 col-form-label'>Nom ou marque </label>
                            <div class='col-lg-4'>";
                                if(!empty($ID)){
                                    echo "<input class='form-control' value='".$this->fields['brand']."' m-input' type='text' name='brand'>";
                                }else{
                                    echo "<input class='form-control' m-input' type='text' name='brand'>";
                                }
                                
                            echo"</div>
                        </div>
                        
                        <div class='form-group m-form__group row'>
                            <label class='col-lg-4 col-form-label'>Couleur primaire</label>
                            <div class='col-lg-4'>";
                                if(!empty($ID)){
                                    echo "<input style='width: 100%; padding: .85rem 1.15rem;' class='form-control m-input' type='color' value='".$this->fields['header_color']."' name='header_color'>";
                                }else{
                                    echo "<input style='width: 100%; padding: .85rem 1.15rem;' class='form-control m-input' type='color' name='header_color'>";
                                }
                                
                            echo"</div>
                        </div>

                        <div class='form-group m-form__group row'>
                            <label class='col-lg-4 col-form-label'>Nom de l'élément:</label>
                            <div class='col-lg-4'>
                                <input class='form-control m-input' type='text' id='example-text-input'>
                            </div>
                        </div>

                        <div class='form-group m-form__group row'>
                            <label class='col-lg-4 col-form-label'>Nom de l'élément:</label>
                            <div class='col-lg-4'>
                                <input class='form-control m-input' type='text' id='example-text-input'>
                            </div>
                        </div>

                    </div>
                    <div class='m-portlet__foot m-portlet__no-border m-portlet__foot--fit'>
                    <div class='m-form__actions m-form__actions--solid'>
                        <div class='row'>
                            <div class='col-lg-6'></div>
                            <div class='col-lg-6 d-flex justify-content-end'>
                                <button type='submit' class='btn btn-brand'>Sauvegarder</button>
                            </div>
                        </div>
                    </div>
                </div>
                </form>

                <!--end::Form-->
            </div>";
    }

    function showList() {
        
    }

    static function canCreate() {
        return Session::haveRight(self::$rightname, CREATE);
    }

    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }
}