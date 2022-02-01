

<header id="m_header" class="m-grid__item    m-header " m-minimize-offset="200" m-minimize-mobile-offset="200">
  <div class="m-container m-container--fluid m-container--full-height">
    <div class="m-stack m-stack--ver m-stack--desktop">
      <div class="m-stack__item m-brand  m-brand--skin-dark ">
        <div class="m-stack m-stack--ver m-stack--general">
          <div class="m-stack__item m-stack__item--middle m-brand__logo">
            <a  id="mark_nananger" href="/portal" class="logo m-brand__logo-wrapper">
            </a>
          </div>
          <div class="m-stack__item m-stack__item--middle m-brand__tools">
            <a href="javascript:;" id="m_aside_left_minimize_toggle" class="m-brand__icon m-brand__toggler m-brand__toggler--left m--visible-desktop-inline-block  ">
              <span></span>
            </a>
            <a href="javascript:;" id="m_aside_left_offcanvas_toggle" class="m-brand__icon m-brand__toggler m-brand__toggler--left m--visible-tablet-and-mobile-inline-block">
              <span></span>
            </a>
            <a id="m_aside_header_menu_mobile_toggle" href="javascript:;" class="m-brand__icon m-brand__toggler m--visible-tablet-and-mobile-inline-block">
              <span></span>
            </a>
            <a id="m_aside_header_topbar_mobile_toggle" href="javascript:;" class="m-brand__icon m--visible-tablet-and-mobile-inline-block">
              <i class="flaticon-more"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- END: Brand -->
      <div class="m-stack__item m-stack__item--fluid bg-white m-header-head" id="m_header_nav">

        <!-- BEGIN: Horizontal Menu -->
        <button class="m-aside-header-menu-mobile-close  m-aside-header-menu-mobile-close--skin-dark " id="m_aside_header_menu_mobile_close_btn">
          <i class="la la-close"></i>
        </button>

        <div id="m_header_topbar" class="m-topbar  m-stack m-stack--ver m-stack--general m-stack--fluid">
          <div class="m-stack__item m-topbar__nav-wrapper">
            <ul class="m-topbar__nav m-nav m-nav--inline">
              <li class="m-nav__item m-topbar__user-profile m-topbar__user-profile--img  m-dropdown m-dropdown--medium m-dropdown--arrow m-dropdown--header-bg-fill m-dropdown--align-right m-dropdown--mobile-full-width m-dropdown--skin-light" m-dropdown-toggle="click">
                <a href="#" class="m-nav__link m-dropdown__toggle">
                  <span style='color:#000;' class="m-topbar__username">
                    <?php
                      $username = '';
                      if (Session::getLoginUserID()) {
                        $username = formatUserName(0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                                      $_SESSION["glpifirstname"], 0, 20);
                      }
                      echo "<span class='m-card-user__name m--font-weight-500'>{$username}</span>";
                    ?>
                  </span>
                </a>
                <div class="m-dropdown__wrapper">
                  <span class="m-dropdown__arrow m-dropdown__arrow--right m-dropdown__arrow--adjust"></span>
                  <div class="m-dropdown__inner">
                    <div class="m-dropdown__header m--align-center">
                      <div class="m-card-user m-card-user--skin-dark">
                        <div class="m-card-user__details">
                          <?php
                              $username = '';
                              if (Session::getLoginUserID()) {
                                $username = formatUserName(0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                                              $_SESSION["glpifirstname"], 0, 20);
                              }
                              echo "<span class='m-card-user__name m--font-weight-500'>{$username}</span>";
                          ?>
                        </div>
                      </div>
                    </div>
                    <div class="m-dropdown__body">
                      <div class="m-dropdown__content">
                        <ul class="m-nav m-nav--skin-light">
                          <li class="m-nav__section m--hide">
                            <span class="m-nav__section-text">Section</span>
                          </li>
                          <li class="m-nav__separator m-nav__separator--fit">
                          </li>
                          <li class="m-nav__separator m-nav__separator--fit">
                          </li>
                          <?php
                            $logout_url = $CFG_GLPI['root_doc']
                            . '/logout/'
                            . (isset($_SESSION['glpiextauth']) && $_SESSION['glpiextauth'] ? '?noAUTO=1' : '' );
                            echo'
                              <li class="m-nav__item">
                                <a href="' . $logout_url . '"  class="btn m-btn--pill  m-btn m-btn--custom m-btn--label-brand m-btn--bolder ">'.__('Logout').'</a>
                              </li>
                            ';
                          ?>
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

  <!-- /*style pour le backoffice*/ -->
  <?php 
      $th = new PluginServicesTheme();
      $themes = $th->find(['active' => 1]);
      if (!empty($themes)) {
        foreach ($themes as  $value) {
            $theme = $value; 
        }
      }else {
        $theme = [
          "sidebar_header_color"=> '#399fa0',
          "sidebar_color"=> '#3DA8A9',
          "sidebar_menu_color"=> '#f2eeee'
        ];
      }
  ?>
  <style>
      .btn-info{
        background: <?= $theme['sidebar_color'] ?>;
        border-color: <?= $theme['sidebar_color'] ?>;
      }

      .btn-info:hover {
        color: #fff;
        background-color: <?= $theme['sidebar_color'] ?>;
        border-color: <?= $theme['sidebar_color'] ?>;
      }

      .m-brand.m-brand--skin-dark {
        /* background: #399fa0; */
        background: <?= $theme['sidebar_header_color'] ?>;
        /* bg de la zone du logo */
      }

      .m-aside-left.m-aside-left--skin-dark {
        /* background-color: #3DA8A9; */
        background-color: <?= $theme['sidebar_color'] ?>;
        /* bg de la barre laterale */
      }

      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__heading .m-menu__link-icon, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__link .m-menu__link-icon {
        color: <?= $theme['sidebar_menu_color'] ?>;
        /* couleur des icones sur la sidebar*/
      }

      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler span::before, .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler span::after {
        background: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler:hover span {
        background: <?= $theme['sidebar_menu_color'] ?>;
      }
      
      label{
        font-size:14px !important;
      }
      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler:hover span::before,
      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler:hover span::after {
        background: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler span {
        background: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__icon>i {
        color: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__icon:hover>i {
        color: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__heading .m-menu__link-icon,
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__link .m-menu__link-icon {
        color: <?= $theme['sidebar_menu_color'] ?>;
        /* couleur des icones sur la sidebar (hover)*/
      }

      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler.m-brand__toggler--active span {
        background:<?= $theme['sidebar_menu_color'] ?>;
      }
      .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler.m-brand__toggler--active span::before, .m-brand.m-brand--skin-dark .m-brand__tools .m-brand__toggler.m-brand__toggler--active span::after {
        background: <?= $theme['sidebar_menu_color'] ?>;
      }
      @media (min-width: 1025px);{
        .m-aside-left--minimize .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__link>.m-menu__link-icon {
        color: <?= $theme['sidebar_menu_color'] ?>;
      }
      }

      
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__heading .m-menu__link-text, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__link .m-menu__link-text {
        color: <?= $theme['sidebar_menu_color'] ?>;
        /* couleur des items du menu */
      }
      .m-topbar .m-topbar__nav.m-nav>.m-nav__item.m-topbar__user-profile.m-topbar__user-profile--img.m-dropdown--arrow .m-dropdown__arrow{
        color: <?= $theme['sidebar_color'] ?>;
      }
      .btn.m-btn--label-brand{
        color: <?= $theme['sidebar_menu_color'] ?>;
        background-color: <?= $theme['sidebar_color'] ?>;
      }
      .btn.m-btn--label-brand:hover{
        color: <?= $theme['sidebar_menu_color'] ?>;
        background-color: <?= $theme['sidebar_color'] ?>;
      }

      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__heading .m-menu__link-text,
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__link .m-menu__link-text {
        color: <?= $theme['sidebar_menu_color'] ?>;
        /* couleur des items du menu (hover)*/
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover {
        -webkit-transition: background-color 0.3s;
        transition: background-color 0.3s;
        background: <?= $theme['sidebar_header_color'] ?>;
        /* couleur du background (hover)*/
      }

      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item.m-menu__item--open {
        -webkit-transition: background-color 0.3s;
        transition: background-color 0.3s;
        background-color: <?= $theme['sidebar_header_color'] ?>;;
      }

      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item>.m-menu__heading .m-menu__link-text, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item>.m-menu__link .m-menu__link-text {
        color: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item.m-menu__item--open>.m-menu__heading .m-menu__link-text, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item.m-menu__item--open>.m-menu__link .m-menu__link-text {
        color: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item.m-menu__item--open>.m-menu__heading .m-menu__link-icon, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item.m-menu__item--open>.m-menu__link .m-menu__link-icon {
        color:<?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item.m-menu__item--open>.m-menu__heading .m-menu__ver-arrow, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item.m-menu__item--open>.m-menu__link .m-menu__ver-arrow {
        color:<?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__heading .m-menu__ver-arrow, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__link .m-menu__ver-arrow {
        color:<?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item>.m-menu__heading .m-menu__link-bullet.m-menu__link-bullet--dot>span, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item>.m-menu__link .m-menu__link-bullet.m-menu__link-bullet--dot>span {
        background-color: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__heading .m-menu__link-text, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__link .m-menu__link-text {
        color: <?= $theme['sidebar_menu_color'] ?> ;
        text-decoration: underline<?= $theme['sidebar_menu_color'] ?>;  
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__heading .m-menu__link-bullet.m-menu__link-bullet--dot>span, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__link .m-menu__link-bullet.m-menu__link-bullet--dot>span {
        background-color: <?= $theme['sidebar_menu_color'] ?>;
        text-decoration: underline <?= $theme['sidebar_menu_color'] ?>;  
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__heading .m-menu__ver-arrow, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item:not(.m-menu__item--parent):not(.m-menu__item--open):not(.m-menu__item--expanded):not(.m-menu__item--active):hover>.m-menu__link .m-menu__ver-arrow {
        color: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item.m-menu__item--active>.m-menu__heading .m-menu__link-text, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item.m-menu__item--active>.m-menu__link .m-menu__link-text {
        color: #716aca;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item.m-menu__item--active>.m-menu__heading .m-menu__link-text, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item.m-menu__item--active>.m-menu__link .m-menu__link-text {
        color: #ffffff;
        text-decoration: underline;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__heading .m-menu__link-bullet.m-menu__link-bullet--dot>span, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item>.m-menu__link .m-menu__link-bullet.m-menu__link-bullet--dot>span {
        background-color: <?= $theme['sidebar_menu_color'] ?>;
      }
      .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item.m-menu__item--active>.m-menu__heading .m-menu__link-bullet.m-menu__link-bullet--dot>span, .m-aside-menu.m-aside-menu--skin-dark .m-menu__nav>.m-menu__item .m-menu__submenu .m-menu__item.m-menu__item--active>.m-menu__link .m-menu__link-bullet.m-menu__link-bullet--dot>span {
        background-color: #ffffff;
      }
      .theme {
        height: 25px;
        width: 25px;
        background-color: red;
        border-radius: 50%;
        display: inline-block;
      }

      .ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default {
        border: 1px solid #FFF;
        background-color: #f2f3f8;
        color: #555555;
      }
      .ui-state-active, .ui-widget-content .ui-state-active, .ui-widget-header .ui-state-active {
        border-top: 4px solid  <?= $theme['sidebar_header_color'] ?> !important;
        background-color: #ffffff !important;
        color: #212121 !important;
      }

      #mark_nananger {
        color:#fff !important;
        font-size: 1.75rem !important;
        text-decoration:none !important; 
        text-shadow: 2px 2px 4px #000000;
      }
  
      #mainform button[name=add]{
        background-color:  <?= $theme['sidebar_color'] ?>;
        border-color: <?= $theme['sidebar_color'] ?>;
        color: <?= $theme['sidebar_menu_color'] ?>
      }

      #mainform button[name=update]{
        background-color:  <?= $theme['sidebar_color'] ?>;
        border-color: <?= $theme['sidebar_color'] ?>;
        color: <?= $theme['sidebar_menu_color'] ?>
      }
      .newValidation{
        border: 1px solid #dddddd !important;
        background: #e9e9e9 !important;
        color: #333333 !important;
      }
      .newValidation i{
        color: #333333 !important;
      }

      #mainform button[name=delete]{
        background-color:  #f4516c !important;
        border-color: #f4516c !important;
        color: #fff;
      }
  
      #mainform button[name=purge]{
        background-color: #f4516c !important;
        border-color: #f4516c !important ;
        color: #fff;
      }
      input, select {
        font-size:14px !important;
      }
      li.tab{
        background-color:  #e9e9e9 !important;
      }

      .form-control[readonly], .form-control {
        border-color: #cccccc;
        color: #575962;
      }

      #mainform input, #mainform textarea, #mainform select, #mainform .select2-selection, #mainform .select2-selection--single, .form-control-fago{
        background: #fff !important;
        box-shadow: <?php echo $theme['sidebar_color']; ?> 0px 0px 1px 0px !important;
      }

      #mainform input[type=search]{
        background: #fff !important;
        box-shadow:  0px 0px 0px 0px !important;
      }

  </style>


