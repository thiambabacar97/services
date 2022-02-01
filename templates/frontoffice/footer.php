
              </div>
            </div>
            <footer class="m-footer" style="width:100%; margin-left: 0;">
              <div class="m-container m-container--fluid m-container--full-height m-page__container">
                <div class="m-stack m-stack--flex-tablet-and-mobile m-stack--ver m-stack--desktop">
                  <div class="m-stack__item m-stack__item--left m-stack__item--middle m-stack__item--last">
                    <span class="m-footer__copyright">
                      <a href="https://yawize.com/" class="m-link">  YAWIZE SARL </a> -  &copy; COPYRIGHT 2021 TOUS DROITS RÉSERVÉS
                    </span>
                  </div>
                  <div class="m-stack__item m-stack__item--right m-stack__item--middle m-stack__item--first">
                    <ul class="m-footer__nav m-nav m-nav--inline m--pull-right">
                      <li class="m-nav__item m-nav__item">
                        <a href="#" class="m-nav__link" data-toggle="m-tooltip" title="Suivez nous !" data-placement="left">
                          <i class=" fab fa-linkedin fa-2x"></i>
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </footer>
        </div>
        <div id="m_scroll_top" class="m-scroll-top">
          <i class="la la-arrow-up"></i>
        </div>
      </body>
    </html>
    <style>
      .m-footer{
        box-shadow: 0px 4px 4px 0px #00000040 inset;
      }
      .cirle {
        height: 25px;
        width: 25px;
        border-radius: 50%;
        display: inline-block;
        cursor: pointer;
      }

      .mainform table td {
          display: block !important;
          width: 100%;
      }

      .mainform .timeline_history .h_content:after,
      .timeline_history .h_content:before {
          display: none !important;
      }

      .mainform .timeline_history .h_content.ITILFollowup {
          background-position: right !important;
      }

      .mainform .timeline_history .h_item,
      .timeline_history {
          border-top: 0px dashed #fff;
      }

      .mainform .thumbnail img {
          width: 200px !important;
          border: 3px solid #4A8865;
          border-radius: 3px;
      }

      .mainform .fileupload {
          margin: 0px !important;
          max-width: 100%;
      }

      .mainform input:required {
          border: 1px solid rgb(211, 211, 211) !important;
          border-left: 1px rgba(255, 0, 0, 0.6) solid !important;
          border-left-width: 3px !important;
          padding-right: 0 !important;
          box-shadow: none;
      }

      .mainform select:required {
          border-left: 1px rgba(255, 0, 0, 0.6) solid !important;
          border-left-width: 3px !important;
          padding-right: 0 !important;
          box-shadow: none;
      }

      .mainform .mce-edit-area.required {
          border: 1px solid rgb(211, 211, 211) !important;
          border-left: 1px rgba(255, 0, 0, 0.6) solid !important;
          border-left-width: 3px !important;
          padding-right: 0 !important;
          box-shadow: none;
      }

      .mainform .fileupload {
          text-align: center;
          border: 1px solid #cccccc;
          min-height: 65px;
          background-color: #fff;
          border-radius: 5px;
          width: 100% !important;
          max-width: 100%;
          margin: 0.5em auto;
          padding: 0.5em;
          margin-top: 5px;
      }
    </style>
    <script>
      $.get( '<?= $CFG_GLPI['root_doc']?>/ajax/portalconfig.php', { _glpi_csrf_token:'<?= Session::getNewCSRFToken() ?>'}, function() {
      }).done(function(succes) {
        const theme = JSON.parse(succes);
        $(".logo_manager").css({
          "background":"url(<?= $CFG_GLPI['root_doc']?>/assets/img/logo/"+theme.logo+") no-repeat center",
          "width": "100%",
          "height": "100%",
          "background-size":"contain",
          "cursor": "pointer"
        });

        $(".home-img-banier").css({
          "background":"url(<?= $CFG_GLPI['root_doc']?>/assets/img/banner/"+theme.banner+")",
          "background-repeat": "no-repeat",
          "background-position": "contain",
          "height": " 300px",
          "width": "100%",
          "background-size":"cover",
          "margin-top": " 70px",
        });

        $(".sub-page-banner").css({
          "background":"url(<?= $CFG_GLPI['root_doc']?>/assets/img/banner/"+theme.banner+")",
          "background-repeat": "no-repeat",
          "background-position": "bottom",
          "height": " 150px",
          "width": "100%",
          "background-size":"cover",
          "margin-top": " 70px",
        });

        $(".banner-text").text(theme.textBanner);

      }).fail(function(error) {
        console.log(error);
      })


      $.get( '<?= $CFG_GLPI['root_doc']?>/ajax/theme.php', { _glpi_csrf_token:'<?= Session::getNewCSRFToken() ?>'}, function() {
      }).done(function(succes) {
        const theme = JSON.parse(succes);
        $("#card-header").css({
          "background-color": theme.sidebar_color,
          "font-family": "Roboto, sans-serif",
          "font-size": "16px",
          "font-weight": "400"
        });

        $("#m-dropdown__header").css({
          "background-size": "cover",
          "background-color": theme.sidebar_color,
          "color": theme.sidebar_menu_color
        });

        $(".badge-color").css({
          "background-color": theme.sidebar_color,
          "color": " #fff"
        });
        
        $('.logo_manager').click(function() {
          window.location.href = "<?= $CFG_GLPI['root_doc']?>/portal";
        });
        
        $(".m-topbar .m-topbar__nav.m-nav>.m-nav__item.m-topbar__user-profile.m-topbar__user-profile--img.m-dropdown--arrow .m-dropdown__arrow").css({
          /* couleur des items sur la sidebar*/
          "color": theme.sidebar_color
        });

        $(".btn.m-btn--label-brand").css({
          /* couleur de l'icone du modal de deconnection*/
          "color": theme.sidebar_menu_color,
          "background-color": theme.sidebar_color
        });

        $("#mainform button[type=submit]").css({
          /* couleur des button de type submit */
          "background-color": theme.sidebar_color,
          "border-color":  theme.sidebar_color
        });
      }).fail(function(error) {
        console.log(error);
      })


      $("#searchcriteria").hide();
      $("#togglesearchcriteria" ).click(function() {
        $( "#searchcriteria" ).toggle();
      });
  
      $('#resetsearchcriteria').click(function(){
        $('#blanksearchcriteria').click();
      });

      $('#clickmassiveactionhidebutton').click(function(event){
          $('#massiveaction').click();
      });
    </script>
    <?php PluginServicesHtml::nullFooter();?>



