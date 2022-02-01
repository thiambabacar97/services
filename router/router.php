<?php
if (empty($_GET["id"]) && !(isset($_GET["id"]) && $_GET["id"]==0)) {
    $_GET["id"] = "";
}

$path = (str_ends_with($_GET['url'], '/')) ? substr($_GET['url'], 0, -1) : $_GET['url'] ;

$found = false;
if ($path =='sso' ) {
    include(GLPI_ROOT . '/plugins/phpsaml/front/acs.php');
}
if(empty($_GET['url']) && Session::getLoginUserID()) {
    if (Session::getCurrentInterface() == 'helpdesk') {
        header("Location: /portal");
    }else {
        header("Location: /home");
    }
}elseif (empty($_GET['url']) && !Session::getLoginUserID()) {
    
    header("Location: /login"); 
} 

if(Session::getLoginUserID()) {
    $mn = new PluginServicesMenu();
    $menu = $mn->find();
    foreach ($menu as  $data_page) {
        $array_path = explode("/", $path);
        $criteria = isset($data_page['criteria']) ? json_decode($data_page['criteria'], true) : [];
        //Ceci permet de recuperer l'id de l'utilisateur connecter si le valeur du criteria == 'Sessi;on::getLoginUserID()')
        if ( !empty($criteria['criteria'])) {
            foreach ($criteria['criteria'] as $key => $filtre) {
                if ($filtre['value'] == 'Session::getLoginUserID()') {
                    $criteria['criteria'][$key]['value'] = Session::getLoginUserID();
                }
            }
        }
        
        $url_path = $array_path[count($array_path)-1];

        if($url_path==''){
            $url_path = $array_path[count($array_path)-2];
        }

        if(true ){
            if($data_page['path'] == $path && $data_page['type'] == "core"){
                include(GLPI_ROOT . '/plugins/services/templates/template.php');
                    $bill = new $data_page['item']();
                    $bill->showForm($_GET["id"], []);
                include(GLPI_ROOT . '/plugins/services/templates/footer.php');
                $found=true;
                break;
            }else if ($data_page['path'] == $path && $data_page['type'] == "module") {
                
                if ($data_page['path']==$path && $url_path=="form") {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $bill = new $data_page['item']();
                        $bill->postForm($_POST);
                        $found=true;
                        break;
                    }else if ($_SERVER['REQUEST_METHOD'] == 'GET'){
                        if (isset($_GET['_in_modal'])) {
                            PluginServicesHtml::popHeader($data_page['item']::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF']);
                                $bill = new $data_page['item']();
                                $bill->showFormModal([]);
                            PluginServicesHtml::popFooter();
                            $found=true;
                            break;
                        }else {
                            if (isset($_GET['type']) && $_GET['type']==='ajax') {
                                $bill = new $data_page['item']();
                                $bill->showForm($_GET["id"], []);
                            }else {
                                include(GLPI_ROOT . '/plugins/services/templates/template.php');
                                    $bill = new $data_page['item']();
                                    $bill->showForm($_GET["id"], []);
                                include(GLPI_ROOT . '/plugins/services/templates/footer.php');
                            }
                            $found=true;
                            break;
                        }
                    }
                }else {
                    if (isset($_GET['type']) && $_GET['type']==='ajax') {
                        $bill = new $data_page['item']();
                        $bill->showList($data_page['item'], $criteria);
                    }else {
                        include(GLPI_ROOT . '/plugins/services/templates/template.php');
                            $bill = new $data_page['item']();
                            $bill->showList($data_page['item'], $criteria);
                        include(GLPI_ROOT . '/plugins/services/templates/footer.php');
                    }
                    $found=true;
                    break;
                }
            }else if( $data_page['path'] == $path && $data_page['type'] == "ajax"){
                if ($data_page['path']==$path && $url_path=="form") {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $bill = new $data_page['item']();
                        $bill->postForm($_POST);
                        $found=true;
                        break;
                    }else if ($_SERVER['REQUEST_METHOD'] == 'GET'){
                        $bill = new $data_page['item']();
                        $bill->showForm($_GET["id"], []);
                        $found=true;
                        break;
                    }
                }else {
                    $bill = new $data_page['item']();
                    $bill->showList($data_page['item'], $criteria);
                    $found=true;
                    break;
                }
            }else if( $data_page['path'] == $path && $data_page['type'] == "portal"){
                if ($url_path=="form") {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $bill = new $data_page['item']();
                        $bill->postForm($_POST);
                        $found=true;
                        break;
                    }else if ($_SERVER['REQUEST_METHOD'] == 'GET'){
                        include(GLPI_ROOT . '/plugins/services/templates/frontoffice/template.php');
                            $bill = new $data_page['item']();
                            $methode = (empty($_GET["id"])) ? 'showFormHelpdesk' : 'showForm' ;
                            $bill->$methode($_GET["id"], []);
                        include(GLPI_ROOT . '/plugins/services/templates/frontoffice/footer.php');
                        $found=true;
                        break;
                    }
                }else if($url_path=="list") {
                    include(GLPI_ROOT . '/plugins/services/templates/frontoffice/template.php');
                        $bill = new $data_page['item']();
                        $bill->showListPortal($data_page['item'], $criteria);
                    include(GLPI_ROOT . '/plugins/services/templates/frontoffice/footer.php');
                    $found=true;
                    break;
                }else {
                    include(GLPI_ROOT . '/plugins/services/templates/frontoffice/template.php');
                        $bill = new $data_page['item']();
                        $bill->showContent();
                    include(GLPI_ROOT . '/plugins/services/templates/frontoffice/footer.php');
                    $found=true;
                    break;
                }

            }
        }else {
            if($data_page['path'] == $path) {
                include(GLPI_ROOT . '/plugins/services/404/403.php');
                return;
            }
        }
        if($found){
            break;
        }
    }
}else{
    if ($path =='sso' ) {
        include(GLPI_ROOT . '/plugins/phpsaml/front/acs.php');
    }
    else {
        PluginServicesHtml::redirect("/services/login/");
    }
}

if(!$found){
    include(GLPI_ROOT . '/plugins/services/404/404.php');
}
