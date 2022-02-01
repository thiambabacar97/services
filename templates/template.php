
    <!DOCTYPE html>
    <html lang="en">
      <?php
          global $CFG_GLPI;
          PluginServicesHtml::helpHeader(__('Home'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);  
      ?>

      <body class="m-page--fluid m--skin- m-content--skin-light2 m-header--fixed m-header--fixed-mobile m-aside-left--enabled m-aside-left--skin-dark m-aside-left--fixed m-aside-left--offcanvas m-footer--push m-aside--offcanvas-default">
        <div class="m-grid m-grid--hor m-grid--root m-page">

            <?php  include('header.php')?>

          <div class="m-grid__item m-grid__item--fluid m-grid m-grid--ver-desktop m-grid--desktop m-body">
            <button class="m-aside-left-close  m-aside-left-close--skin-dark " id="m_aside_left_close_btn">
              <i class="la la-close"></i>
            </button>

            <div id="m_aside_left" class="m-grid__item	m-aside-left  m-aside-left--skin-dark ">
              <?php  include('sidebar.php')?>
            </div>
            <div id='spin'></div>
            <div class="m-grid__item m-grid__item--fluid m-wrapper"  id="m-content">
    