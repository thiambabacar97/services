<!DOCTYPE html>
<?php 	PluginServicesHtml::helpHeader(__('Page non trouvée'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]); ?>
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
	
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Fago | Accès interdit</title>
		<meta name="description" content="Latest updates and statistic charts">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">

		<!--begin::Web font -->
		<script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.16/webfont.js"></script>
		<script>
			WebFont.load({
				google: {
					"families": ["Poppins:300,400,500,600,700", "Roboto:300,400,500,600,700"]
				},
				active: function() {
					sessionStorage.fonts = true;
				}
			});
		</script>
    
		<!--begin::Base Styles -->
		<link href="../../../assets/vendors/base/vendors.bundle.css" rel="stylesheet" type="text/css" />
		<link href="../../../assets/demo/default/base/style.bundle.css" rel="stylesheet" type="text/css" />
		<!-- <link rel="shortcut icon" href="../../../assets/demo/default/media/img/logo/favicon.ico" /> -->
		<script>
			(function(i, s, o, g, r, a, m) {
				i['GoogleAnalyticsObject'] = r;
				i[r] = i[r] || function() {
					(i[r].q = i[r].q || []).push(arguments)
				}, i[r].l = 1 * new Date();
				a = s.createElement(o),
					m = s.getElementsByTagName(o)[0];
				a.async = 1;
				a.src = g;
				m.parentNode.insertBefore(a, m)
			})(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');
			ga('create', 'UA-37564768-1', 'auto');
			ga('send', 'pageview');
		</script>
	</head>
	<body class="m--skin- m-header--fixed m-header--fixed-mobile m-aside-left--enabled m-aside-left--skin-dark m-aside-left--fixed m-aside-left--offcanvas m-footer--push m-aside--offcanvas-default">
		<div class="m-grid m-grid--hor m-grid--root m-page">
			<div class="m-grid__item m-grid__item--fluid m-grid  m-error-5">
				<div class="m-error_container">
          <div class="base io"> 
            <h1 class="io">403</h1>
            <h3>Vous n'avez pas les droits requis pour réaliser cette action.</h3>
            <br><br> 
              <?php
                if (Session::getCurrentInterface() == 'helpdesk') {
                  echo'
                    <a href="'.$CFG_GLPI['root_doc'].'/portal" class="btn btn-primary m-btn m-btn--custom m-btn--icon m-btn--pill m-btn--air">
                      <span>
                        <span>Retour à la page d\'accueil</span>
                      </span>
                    </a>
                  ';
                  // header("Location: /portal");
                }else {
                  echo'
                    <a href="'.$CFG_GLPI['root_doc'].'/home" class="btn btn-primary m-btn m-btn--custom m-btn--icon m-btn--pill m-btn--air">
                      <span>
                        <span>Retour à la page d\'accueil</span>
                      </span>
                    </a>
                  ';
                    // header("Location: /home");
                }
              ?>
          </div>
				</div>
			</div>
		</div>

		<!-- end:: Page -->

		<!--begin::Base Scripts -->
		<script src="../../../assets/vendors/base/vendors.bundle.js" type="text/javascript"></script>
		<script src="../../../assets/demo/default/base/scripts.bundle.js" type="text/javascript"></script>
  
		<!--end::Base Scripts -->
	</body>
  <style>

      body .base {
        width: 100%;
        height: 100vh;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-direction: column;
        -webkit-tap-highlight-color: <?= $theme['sidebar_color'] ?>;
      }

      body .base h1 {
        -webkit-tap-highlight-color: <?= $theme['sidebar_color'] ?>;
        font-family: "Ubuntu", sans-serif;
        text-transform: uppercase;
        text-align: center;
        font-size: 30vw;
        display: block;
        margin: 0;
        color: <?= $theme['sidebar_color'] ?>;
        position: relative;
        z-index: 0;
        animation: colors 0.4s ease-in-out forwards;
        animation-delay: 1.7s;
      }

      body .base h1:before {
        content: "U";
        position: absolute;
        top: -9%;
        right: 40%;
        transform: rotate(180deg);
        font-size: 15vw;
        color: #f6c667;
        z-index: -1;
        text-align: center;
        animation: lock 0.2s ease-in-out forwards;
        animation-delay: 1.5s;
      }
      body .base h3 {
        font-family: "Cabin", sans-serif;
        color: <?= $theme['sidebar_color'] ?>;
        margin: 0;
        /* text-transform: uppercase; */
        text-align: center;
        animation: colors 0.4s ease-in-out forwards;
        animation-delay: 2s;
        -webkit-tap-highlight-color: <?= $theme['sidebar_color'] ?>;
      }
      body .base h5 {
        font-family: "Cabin", sans-serif;
        color: <?= $theme['sidebar_color'] ?>;
        font-size: 2vw;
        margin: 0;
        text-align: center;
        opacity: 0;
        animation: show 2s ease-in-out forwards;
        color: <?= $theme['sidebar_color'] ?>;
        animation-delay: 3s;
        -webkit-tap-highlight-color: <?= $theme['sidebar_color'] ?>;
      }

      @keyframes lock {
        50% {
          top: -4%;
        }
        100% {
          top: -6%;
        }
      }
      @keyframes colors {
        50% {
          transform: scale(1.1);
        }
        100% {
          color: <?= $theme['sidebar_color'] ?>;
        }
      }
      @keyframes show {
        100% {
          opacity: 1;
        }
      }
      .btn-primary {
				color: <?= $theme['sidebar_menu_color'] ?>;
				background-color: <?= $theme['sidebar_color'] ?>;;
				border-color: <?= $theme['sidebar_color'] ?>;;
			}
  </style>
</html>