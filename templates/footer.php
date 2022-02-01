
      </div>
    </div>

      <footer class="m-grid__item		m-footer " style="border: 1px solid #999999, box-shadow: 0px 4px 4px 0px #00000040 inset;
">
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


    <!-- begin::Scroll Top -->
    <div id="m_scroll_top" class="m-scroll-top">
      <i class="la la-arrow-up"></i>
    </div>

  </body>
</html>

<script>
  $.get('<?= $CFG_GLPI['root_doc']?>/ajax/theme.php', { _glpi_csrf_token:'<?= Session::getNewCSRFToken() ?>'}, function() {
  }).done(function(succes) {
    const theme = JSON.parse(succes);
    $("#mark_nananger").text(theme.mark);    
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

  function textAreaAdjust(element) {
    element.style.height = "1px";
    element.style.height = (25+element.scrollHeight)+"px";
  }
  
  if ( window.history.replaceState ) {
    window.history.replaceState( null, null, window.location.href );
  }
  // $('textarea').on('input', function() {
  //   console.log(this);
  //   this.style.height = (5+this.scrollHeight)+"px";
  // });

</script>


<?php PluginServicesHtml::nullFooter(); ?>