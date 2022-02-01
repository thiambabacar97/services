<?php 	
  PluginServicesHtml::helpHeaderPortal(__('Home'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]); 
  $userID = $_SESSION['glpiID'];
  $user = new User();
  $user->getFromDB($userID);
  $photo_url = User::getURLForPicture($user->fields['picture']);
  $username = formatUserName(0, $_SESSION["glpiname"], $_SESSION["glpirealname"], $_SESSION["glpifirstname"], 0, 20);
?>

<header id="m_header" class="m-grid__item    m-header " m-minimize-offset="200" m-minimize-mobile-offset="200">
  <div class="m-container m-container--fluid m-container--full-height">
    <div class="m-stack m-stack--ver m-stack--desktop">
      <div class="m-stack__item m-stack__item--fluid m-header-head" id="m_header_nav">
        <div id="m_header_menu" class="m-header-menu m-aside-header-menu-mobile m-aside-header-menu-mobile--offcanvas  m-header-menu--skin-light m-header-menu--submenu-skin-light m-aside-header-menu-mobile--skin-dark m-aside-header-menu-mobile--submenu-skin-dark ">
            <ul class="m-menu__nav  m-menu__nav--submenu-arrow ">
              <li class="logo-zone m-menu__item  m-menu__item--submenu m-menu__item--rel" m-menu-submenu-toggle="click" m-menu-link-redirect="1" aria-haspopup="true">
                  <div  class="m-brand__logo-wrapper  logo_manager">
                  </div>
              </li>
            </ul>
        </div>
        <div id="m_header_topbar" class="m-topbar  m-stack m-stack--ver m-stack--general m-stack--fluid">
          <div class="m-stack__item m-topbar__nav-wrapper">
            <ul class="m-topbar__nav m-nav m-nav--inline">
              <li class="m-nav__item">
                <a class="m-nav__link" href="/portal/requests/list" class="m-menu__link">
                  <span class="m-nav__link-text m-link m-widget11__label">
                    Mes Demandes
                    <span class="m-menu__link-badge">
                      <span class="badge badge-color badge-pill">
                        <?=PluginServicesTicket::countRequestNumberOfLoginUser()?>
                      </span>
                    </span>&nbsp;
                  </span>	
                </a>	
              </li>
              <li class="m-nav__item">
                <a class="m-nav__link" href="/portal/incidents/list" class="m-menu__link">
                  <span class="m-nav__link-text m-link m-widget11__label">
                    Mes Incidents
                    <span class="m-menu__link-badge">
                      <span class="badge badge-color badge-pill">
                        <?=PluginServicesTicket::countIncidentsNumberOfLoginUser()?>
                      </span>
                    </span>&nbsp;
                  </span>	
                </a>	
              </li>
              <!-- <?php
                if (Session::getLoginUserID()) {
                    $mn = new PluginServicesMenu();
                    $page = $mn->find();
                    if(!empty($page)){
                      // foreach($PLUGIN_HOOKS['pages'] as $part => $page){
                        foreach ($page as $part => $menu) {
                          if (isset($menu['type']) && $menu['type'] == "portal") {
                            if( $menu['display_menu'] == true){ 
                              // print_r($menu);
                              $path = $menu['path'];
                              $display_name = $menu["display_name"];
                              $main_root = $CFG_GLPI['root_doc'];?>
                                  <li class="m-nav__item">
                                    <a class="m-nav__link" href="<?=$main_root?>/<?=$path?>" class="m-menu__link">
                                      <span class="m-nav__link-text m-link m-widget11__label">
                                      <?= $display_name ?>
                                        <?php
                                            if ( isset($menu['display_badge']) && $menu['display_badge']== true) {
                                              echo '
                                                <span class="m-menu__link-badge">
                                                  <span   class="badge badge-color badge-pill">';
                                                    echo countElementsInTable($menu['item']::getTable());
                                                    echo'
                                                  </span>
                                                </span>&nbsp;
                                              ';
                                            }
                                        ?>
                                      
                                      </span>	
                                    </a>	
                                  </li>
                              <?php
                            }
                          }
                        }
                      // }
                    }
                }
              ?> -->
              <li class="m-nav__item m-topbar__user-profile m-topbar__user-profile--img  m-dropdown m-dropdown--medium m-dropdown--arrow m-dropdown--header-bg-fill m-dropdown--align-right m-dropdown--mobile-full-width m-dropdown--skin-light" m-dropdown-toggle="click">
                <a href="#" class="m-nav__link m-dropdown__toggle">
                  <span class="m-topbar__userpic">
                    <img src="<?=$photo_url ?>" class="m--img-rounded m--marginless" alt="" />
                  </span>
                  <span class="m-topbar__username m--hide"><?= $username; ?></span>
                </a>
                <div class="m-dropdown__wrapper">
                  <span class="m-dropdown__arrow m-dropdown__arrow--right m-dropdown__arrow--adjust"></span>
                  <div class="m-dropdown__inner">
                    <div class="m-dropdown__header m--align-center" id="m-dropdown__header">
                      <div class="m-card-user m-card-user--skin-dark">
                        <div class="m-card-user__pic">
                          <img src="<?=$photo_url ?>" class="m--img-rounded m--marginless" alt="" />
                        </div>
                        <div class="m-card-user__details">
                          <span class="m-card-user__name m--font-weight-500 text-white"><?= $username; ?></span>
                          <a href="" class="m-card-user__email m--font-weight-300 m-link text-white"><?= UserEmail::getDefaultForUser($userID)?></a>
                        </div>
                      </div>
                    </div>
                    <div class="m-dropdown__body">
                      <div class="m-dropdown__content">
                        <ul class="m-nav m-nav--skin-light">
                          <li class="m-nav__section m--hide">
                            <span class="m-nav__section-text">Section</span>
                          </li>
                          <?php 
                            if (Session::isImpersonateActive()) {
                                echo'
                                  <li class="m-nav__item">
                                    <a onclick="endimpersonate(event)"  href="javascript:void(0);" class="m-nav__link">
                                      <i class="m-nav__link-icon fas fa-user-secret fa-lg"></i>
                                      <span class="m-nav__link-title">
                                        <span class="m-nav__link-wrap">
                                          <span class="m-nav__link-text">'. __s('Stop impersonating').'</span>
                                        </span>
                                      </span>
                                    </a>
                                  </li>
                                ';
                            } 
                          ?>
                          <li class="m-nav__separator m-nav__separator--fit">
                          </li>
                          <li class="m-nav__item">
                            <?php
                              $logout_url = $CFG_GLPI['root_doc']
                              . '/logout/'
                              . (isset($_SESSION['glpiextauth']) && $_SESSION['glpiextauth'] ? '?noAUTO=1' : '' );
                              echo'
                                <a href="' . $logout_url . '" class="btn m-btn--pill    btn-secondary m-btn m-btn--custom m-btn--label-brand m-btn--bolder ">'.__('Logout').'</a>
                              ';
                            ?>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
<script>
    function endimpersonate (event){
      var target =  "<?= PluginServicesToolbox::getItemTypeFormURL('PluginServicesUser') ;?>";
      var tokenurl = "/ajax/generatetoken.php";
      var request;
        event.preventDefault();

        if (request) { // Abort any pending request
            request.abort();
        }

        $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
            var data = {
                impersonate: 0,
                _glpi_csrf_token: token
            }

            request = $.ajax({
                url: target,
                type: "post",
                data: data
            });

            request.done(function (response, textStatus, jqXHR){
              var res = JSON.parse(response);
              window.location.href = target+'?id='+res.response;
            });

            request.fail(function (jqXHR, textStatus, errorThrown){
                console.error(jqXHR, textStatus, errorThrown);
            });
        }); 
    }
</script>

    
