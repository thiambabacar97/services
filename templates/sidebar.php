<?php

	use Glpi\Cache\SimpleCache;
	use ScssPhp\ScssPhp\Compiler;

	if (!defined('GLPI_ROOT')) {
		die('Sorry. You can\'t access this file directly');
	}
	// $title = ''; $sector = 'none'; $item = 'none'; $option = '';
  global $CFG_GLPI;
?>

<div id="m_ver_menu" class="m-aside-menu  m-aside-menu--skin-dark m-aside-menu--submenu-skin-dark " m-menu-vertical="1" m-menu-scrollable="1" m-menu-dropdown-timeout="500" style="position: relative;">
  <div class="container mt-3" style="max-width: 555px">
    <input  style="max-height: 33px" type="text" class="form-control" name="live_search" id="live_search" autocomplete="off" placeholder="Search ...">
    <!-- <i id="loader" class="mt-3 text-white fa fa-spinner fa-spin fa-2x fa-fw" style="display: none;  width: 100%;text-align:center;"></i> -->
  </div>
  <ul class="m-menu__nav  m-menu__nav--dropdown-submenu-arrow" id="seachingMenu" style="display: none">
  </ul>

  <ul class="m-menu__nav  m-menu__nav--dropdown-submenu-arrow" id="allMenu">
    <?php
      if (Session::getLoginUserID()) :
        $mn = new PluginServicesMenu();
        $smn = new PluginServicesMenu();
        $page = $mn->find([], ['order']);
        $main_root = $CFG_GLPI['root_doc'];

        $array = array();
        if(!empty($page)):
          foreach ($page as $menu) :
            array_push($array, $menu);
            if ($menu['type'] !== "portal" && $menu['menu_type'] !== "main"):
              if((isset($menu['rightname']) && Session::haveRight($menu['rightname'], READ))): 
                
                if (empty($menu['menus_id']) && $menu['display_menu'] == true):
                  $path = $menu['path'];
                  $icon = (isset($menu['icon'])) ? $menu['icon'] : 'flaticon-home-1';
                  $display_name = $menu["display_name"];?>
                  <li class="m-menu__item" aria-haspopup='true'>
                    <a href="<?=$main_root?>/<?=$path?>" class="m-menu__link">
                      <i class="m-menu__link-icon <?=$icon?>"></i>
                      <span class="m-menu__link-title">
                        <span class="m-menu__link-wrap">
                          <span class="m-menu__link-text"><?= $display_name ?></span>
                        </span>
                      </span>
                    </a>
                  </li>
                  <?php
                endif;
              endif;
            endif;
            if (($menu['type'] !== "portal")  && ($menu['menu_type'] == "main")) :
              $subpage = $smn->find(['menus_id' => $menu['id']], ['order']);
              $icon = (isset($menu['icon'])) ? $menu['icon'] : 'flaticon-share';
            
              if ((isset($menu['rightname']) && Session::haveRight($menu['rightname'], READ))):
                if (!empty($subpage)) :
                  $asctive = ($_GET['url'] == 'group') ? 'm-menu__item m-menu__item--submenu m-menu__item--open' : 'm-menu__item m-menu__item--submenu' ;
                  echo'
                    <li class="m-menu__item m-menu__item--submenu" aria-haspopup="true" m-menu-submenu-toggle="hover">
                      <a href="javascript:;" class="m-menu__link m-menu__toggle">
                        <i class="m-menu__link-icon '.$icon.'"></i>
                        <span class="m-menu__link-text">'.$menu["display_name"].'</span>
                        <i class="m-menu__ver-arrow la la-angle-right"></i>
                      </a>';
                      foreach ($subpage as $value) :
                        if ((isset($value['rightname']) && Session::haveRight($value['rightname'], READ))):
                          if ($value['display_menu']) :
                            $active = ($_GET['url'] == $value["path"]) ? 'm-menu__item--active' : '' ;
                            $path = $main_root.'/'.$value["path"];
                            $array_path = explode("/", $path);
                            $criteria = isset($value['criteria']) ? json_decode($value['criteria'], true) : [];
                            //Ceci permet de recuperer l'id de l'utilisateur connecter si le valeur du criteria == 'Sessi;on::getLoginUserID()')
                            if ( !empty($criteria['criteria'])) {
                                foreach ($criteria['criteria'] as $key => $filtre) {
                                    if ($filtre['value'] == 'Session::getLoginUserID()') {
                                        $criteria['criteria'][$key]['value'] = Session::getLoginUserID();
                                    }
                                }
                            }
                            // print_r(Toolbox::append_params($criteria, '&amp;'));
                            $item = Toolbox::getItemTypeSearchURL($value["item"], true)."?".Toolbox::append_params($criteria, '&amp;');
                            // $item = Toolbox::getItemTypeFormURL('Ticket', true); 
                            echo'
                              <div class="m-menu__submenu ">
                                <span class="m-menu__arrow"></span>
                                <ul class="m-menu__subnav">
                                  <li class="m-menu__item  '.$active.' " aria-haspopup="true">
                                    <a onclick="loadPage(\''.$path.'\')"  href="javascript:void(0);"  class="m-menu__link ">
                                      <i class="m-menu__link-bullet m-menu__link-bullet--dot">
                                        <span></span>
                                      </i>
                                      <span class="m-menu__link-text">'.$value["display_name"].'</span>
                                    </a>
                                  </li>
                                </ul>
                              </div>
                            ';
                          endif;
                        endif;
                      endforeach;
                      echo'
                    </li>
                  ';
                endif;
              endif;
            endif;
          endforeach;
        endif;
      endif;
    ?>

  </ul>
</div>
<style>
  .m-aside-menu .m-menu__nav {
    list-style: none;
    padding: 15px 0 15px 0;
  }
</style>
<script>
  var pages = <?php echo json_encode($array); ?>;
  $(document).ready(function () {;
      $('.m-menu__item--active').closest("li.m-menu__item--submenu").addClass( "m-menu__item--open");
      $.ajaxSetup({ cache: false });
      $("#live_search").keyup(function () {
        $('#seachingMenu').html('');
          var query = $(this).val();
          if(query === ''){
            $('#seachingMenu').css('display', 'none');
            $('#allMenu').css('display', 'block');
            return;
          }
          
          var regex = new RegExp(query, "i");
          $.each(pages, function(key, menu){
            if(menu.display_name.search(regex) !== -1) {
              if( menu.type !== "portal"  &&  menu.menu_type == 'submenu') {
                if (menu.display_menu) {
                  $('#allMenu').css('display', 'none');
                  $("#seachingMenu").prepend('<li class="m-menu__item" aria-haspopup="true"><a href="<?= $CFG_GLPI['root_doc']?>/'+menu.path+'" class="m-menu__link"><i class="m-menu__link-bullet m-menu__link-bullet--dot"><span></span></i><span class="m-menu__link-title"><span class="m-menu__link-wrap"><span class="m-menu__link-text">'+menu.display_name+'</span></span></span></a></li>');
                  $('#seachingMenu').css('display', 'block');
                }
              }else if( menu.type !== "portal" && menu.menu_type == "main"){
                  var submenus = pages.filter( page => page.menus_id == menu.id); //get all submenu
                  console.log(menu);
                  var output ='<li class="m-menu__item  m-menu__item--submenu" aria-haspopup="true" m-menu-submenu-toggle="hover">';
                      output +='<a href="javascript:;" class="m-menu__link m-menu__toggle">';
                      output +='<i class="m-menu__link-icon '+menu.icon+'"></i>';
                      output +='<span class="m-menu__link-text">'+menu.display_name+'</span>';
                      output +='<i class="m-menu__ver-arrow la la-angle-right"></i>';
                      output +='</a>';
                  submenus.forEach( submenu=> {
                    if(submenu.display_menu) {
                      output +='<div class="m-menu__submenu ">';
                      output +='<span class="m-menu__arrow"></span>';
                      output +='<ul class="m-menu__subnav">';
                      output +='<li class="m-menu__item " aria-haspopup="true">';
                      output +='<a href="/'+submenu.path+'" class="m-menu__link ">';
                      output +='<i class="m-menu__link-bullet m-menu__link-bullet--dot">';
                      output +='<span></span></i>';
                      output +='<span class="m-menu__link-text">'+submenu.display_name+'</span></a></li></ul></div>';
                    }
                  });
                  $("#seachingMenu").prepend(output);
                  $('#seachingMenu').css('display', 'block');
                  $('#allMenu').css('display', 'none');
              }
            }else{
               // break;
            }
          });
        
      });
  });
</script>