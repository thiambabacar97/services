<?php

class PluginServicesSearch extends Search {

   static function showGenericSearchForRelatedList($itemtype, array $params) {

      global $CFG_GLPI;
      
      // Default values of parameters

      $p['sort']         = '';

      $p['is_deleted']   = 0;


      $p['as_map']       = 0;

      $p['criteria']     = [];

      $p['metacriteria'] = [];

      if (class_exists($itemtype)) {

         $p['target']       = $itemtype::getSearchURL();

      } else {

         $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);

      }

      $p['showreset']    = true;

      $p['showbookmark'] = true;

      $p['showfolding']  = true;

      $p['mainform']     = true;

      $p['prefix_crit']  = '';

      $p['addhidden']    = [];

      $p['actionname']   = 'search';

      $p['actionvalue']  = _sx('button', 'Search');

      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }
      $main_block_class = '';
   
      if ($p['mainform']) {
         echo "<form name='searchform$itemtype' id='searchform' class='bg-light'>";
      } else {
         $main_block_class = "sub_criteria";
      }

      echo "<div style='display:none' id='searchcriteria' class='bg-light py-2 $main_block_class'>";

      $nbsearchcountvar  = 'nbcriteria'.strtolower($itemtype).mt_rand();

      $searchcriteriatableid = 'criteriatable'.strtolower($itemtype).mt_rand();

      // init criteria count
      echo PluginServicesHtml::scriptBlock("
         var $nbsearchcountvar = ".count($p['criteria']).";
      ");
      echo "<ul id='$searchcriteriatableid'>";
      // Display normal search parameters

      $i = 0;

      foreach (array_keys($p['criteria']) as $i) {
         self::displayCriteria([

            'itemtype' => $itemtype,

            'num'      => $i,

            'p'        => $p

         ]);
      }
      $rand_criteria = mt_rand();

      echo "<li id='more-criteria$rand_criteria' class='normalcriteria headerRow' style='display: none;'>...</li>";
      echo "</ul>";

      echo "<div class='search_actions'>";

      $linked = self::getMetaItemtypeAvailable($itemtype);

      echo"<button id='addsearchcriteria$rand_criteria' style='vertical-align: top; margin: 0 2px;'  type='button' class=' btn btn-sm btn-default '> ".__s('RULE')."</button>";

      echo"<button id='addcriteriagroup$rand_criteria' style='vertical-align: top; margin: 0 2px;'  type='button' class=' btn btn-sm btn-default '> ".__s('GROUP')."</button>";


      $json_p = json_encode($p);

      if ($p['mainform']) {

         // Display submit button

         echo "<button type='submit' class='btn btn-primary btn-sm' name='".$p['actionname']."' >";
         echo $p['actionvalue'];
         echo "</button>";

         if ($p['showbookmark'] || $p['showreset']) {

            if ($p['showreset']) {
               echo"
               <button style='display:none' class='btn btn-default reset-search' id='blanksearchcriteria' onclick=\"location.href='"

                  .$p['target']

                  .(strpos($p['target'], '?') ? '&amp;' : '?')

                  ."reset=reset'\" type='button'>".__s('RESET')."</button>
               ";

            }

         }

      }

      echo "</div>"; //.search_actions

      // idor checks

      $idor_display_criteria       = Session::getNewIDORToken($itemtype);

      $idor_display_meta_criteria  = Session::getNewIDORToken($itemtype);

      $idor_display_criteria_group = Session::getNewIDORToken($itemtype);

      $JS = <<<JAVASCRIPT

         $("#searchcriteria").hide();
         $("#togglesearchcriteria").click(function() {
            $("#searchcriteria").toggle();
         });

         $('#resetsearchcriteria').click(function(event){
            event.preventDefault();
            reloadTab("reset=reset");
         });

         $("#searchform").on('submit', function(event) {
            event.preventDefault();
            reloadTab($("#searchform").serialize());
         });
         
         $('#addsearchcriteria$rand_criteria').on('click', function(event) {
            event.preventDefault();
            $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {

               'action': 'display_criteria',

               'itemtype': '$itemtype',

               'num': $nbsearchcountvar,

               'p': $json_p,

               '_idor_token': '$idor_display_criteria'

            })

            .done(function(data) {

               $(data).insertBefore('#more-criteria$rand_criteria');

               $nbsearchcountvar++;

            });

         });

            
         $('#addmetasearchcriteria$rand_criteria').on('click', function(event) {

            event.preventDefault();
            
            $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {

               'action': 'display_meta_criteria',

               'itemtype': '$itemtype',

               'meta': true,

               'num': $nbsearchcountvar,

               'p': $json_p,

               '_idor_token': '$idor_display_meta_criteria'

            })

            .done(function(data) {

               $(data).insertBefore('#more-criteria$rand_criteria');

               $nbsearchcountvar++;

            });

         });

         $('#addcriteriagroup$rand_criteria').on('click', function(event) {

            event.preventDefault();

            $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {

               'action': 'display_criteria_group',

               'itemtype': '$itemtype',

               'meta': true,

               'num': $nbsearchcountvar,

               'p': $json_p,

               '_idor_token': '$idor_display_criteria_group'

            })

            .done(function(data) {

               $(data).insertBefore('#more-criteria$rand_criteria');

               $nbsearchcountvar++;

            });

         });

JAVASCRIPT;
      if ($p['mainform']) {

         $JS .= <<<JAVASCRIPT

         $('.fold-search').on('click', function(event) {

            var search_criteria =  $('#searchcriteria ul li:not(:first-child)');

            event.preventDefault();

            $(this)

               .toggleClass('fa-angle-double-up')

               .toggleClass('fa-angle-double-down');

            search_criteria.toggle();

            window.localStorage.setItem(

               'show_full_searchcriteria',

               search_criteria.first().is(':visible')

            );

         });
         // Init search_criteria state

         var search_criteria_visibility = window.localStorage.getItem('show_full_searchcriteria');

         if (search_criteria_visibility !== undefined && search_criteria_visibility == 'false') {

            $('.fold-search').click();

         }
         $(document).on("click", ".remove-search-criteria", function() {
            var rowID = $(this).data('rowid');
            $('#' + rowID).remove();
            $('#searchcriteria ul li:first-child').addClass('headerRow').show();
         });

JAVASCRIPT;

      }

      echo Html::scriptBlock($JS);
      if (count($p['addhidden'])) {

         foreach ($p['addhidden'] as $key => $val) {

            echo Html::hidden($key, ['value' => $val]);

         }

      }

      if ($p['mainform']) {
         // For dropdown
         echo Html::hidden('itemtype', ['value' => $itemtype]);
         // Reset to start when submit new search
         echo Html::hidden('start', ['value'    => 0]);
      }
      echo "</div>";

      if ($p['mainform']) {
         Html::closeForm();
      }
      
   }

   static function showGenericSearch($itemtype, array $params) {

      global $CFG_GLPI;

      // Default values of parameters

      $p['sort']         = '';

      $p['is_deleted']   = 0;


      $p['as_map']       = 0;

      $p['criteria']     = [];

      $p['metacriteria'] = [];

      if (class_exists($itemtype)) {

         $p['target']       =  $itemtype::getSearchURL();

      } else {

         $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);

      }

      $p['showreset']    = true;

      $p['showbookmark'] = true;

      $p['showfolding']  = true;

      $p['mainform']     = true;

      $p['prefix_crit']  = '';

      $p['addhidden']    = [];

      $p['actionname']   = 'search';

      $p['actionvalue']  = _sx('button', 'Search');

      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }
      $main_block_class = '';

      if ($p['mainform']) {
         // echo "<form name='searchform$itemtype' class='bg-light' method='get' action='".$p['target']."'>";
         echo "<form id='searchform' name='searchform$itemtype' method='get' class='bg-light'>";
      } else {
         $main_block_class = "sub_criteria";
      }

      echo "<div  style='display:none' id='searchcriteria' class='bg-light py-2 $main_block_class'>";

      $nbsearchcountvar  = 'nbcriteria'.strtolower($itemtype).mt_rand();

      $searchcriteriatableid = 'criteriatable'.strtolower($itemtype).mt_rand();

      // init criteria count
      echo PluginServicesHtml::scriptBlock("
         var $nbsearchcountvar = ".count($p['criteria']).";
      ");
      echo "<ul id='$searchcriteriatableid'>";
      // Display normal search parameters

      $i = 0;

      foreach (array_keys($p['criteria']) as $i) {

         self::displayCriteria([

            'itemtype' => $itemtype,

            'num'      => $i,

            'p'        => $p

         ]);

      }
      $rand_criteria = mt_rand();

      echo "<li id='more-criteria$rand_criteria' class='normalcriteria headerRow' style='display: none;'>...</li>";
      echo "</ul>";

      echo "<div class='search_actions'>";

      $linked = self::getMetaItemtypeAvailable($itemtype);

      echo"<button id='addsearchcriteria$rand_criteria' style='vertical-align: top; margin: 0 2px;'  type='button' class=' btn btn-sm btn-default '> ".__s('RULE')."</button>";
      echo"<button id='addcriteriagroup$rand_criteria' style='vertical-align: top; margin: 0 2px;'  type='button' class=' btn btn-sm btn-default '> ".__s('GROUP')."</button>";


      $json_p = json_encode($p);

      if ($p['mainform']) {

         // Display submit button

         echo '<button  id="onsubmit"  class="btn btn-default btn-sm"  name='.$p['actionname'].' >';
         echo $p['actionvalue'];
         echo "</button>";

         if ($p['showbookmark'] || $p['showreset']) {
            if ($p['showreset']) {
               echo"
                  <button style='display:none' class='btn btn-default reset-search' id='blanksearchcriteria' onclick=\"location.href='"

                  .$p['target']

                  .(strpos($p['target'], '?') ? '&amp;' : '?')

                  ."reset=reset'\" type='button'></button>
               ";
               // echo "<a class='btn btn-default reset-search' id='blanksearchcriteria' href='"

               //    .$p['target']

               //    .(strpos($p['target'], '?') ? '&amp;' : '?')

               //    ."reset=reset' title=\"".__s('Blank')."\"

               //    >".__s('RESET')."</a>";

            }

            // if ($p['showfolding']) {

            //    echo "<a class='fa fa-angle-double-up fa-fw fold-search'

            //             href='#'

            //             title=\"".__("Fold search")."\"></a>";

            // }

         }

      }

      echo "</div>"; //.search_actions
      // idor checks
      $idor_display_criteria       = Session::getNewIDORToken($itemtype);

      $idor_display_meta_criteria  = Session::getNewIDORToken($itemtype);

      $idor_display_criteria_group = Session::getNewIDORToken($itemtype);

      $target = $p['target'];
      $JS = <<<JAVASCRIPT

      $( document ).ready(function() {

         $('#onsubmit').on('click', function(event) { //soumettre le formulaire de recherche
            event.preventDefault();
            var data = $('#searchform').serialize();
            submitSearchPage('$target'+'?'+data);
         })

         $('#togglesearchcriteria').click(function() { // afficher ou masquer le formmulaire
            $('#searchcriteria').toggle();
         });

         $('#resetsearchcriteria').click(function(){ // réinitialiser  le formmulaire
            $('#blanksearchcriteria').click();
         });

         $('#clickmassiveactionhidebutton').click(function(event){
               $('#massiveaction').click();
         });
         
      });
      
      $('#addsearchcriteria$rand_criteria').on('click', function(event) {
         
         event.preventDefault();

         $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {

            'action': 'display_criteria',

            'itemtype': '$itemtype',

            'num': $nbsearchcountvar,

            'p': $json_p,

            '_idor_token': '$idor_display_criteria'

         })

         .done(function(data) {

            $(data).insertBefore('#more-criteria$rand_criteria');

            $nbsearchcountvar++;

         });

      });

      $('#addmetasearchcriteria$rand_criteria').on('click', function(event) {

         event.preventDefault();
         
         $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {

            'action': 'display_meta_criteria',

            'itemtype': '$itemtype',

            'meta': true,

            'num': $nbsearchcountvar,

            'p': $json_p,

            '_idor_token': '$idor_display_meta_criteria'
         })

         .done(function(data) {
            $(data).insertBefore('#more-criteria$rand_criteria');
            $nbsearchcountvar++;
         });

      });

      $('#addcriteriagroup$rand_criteria').on('click', function(event) {

         event.preventDefault();

         $.post('{$CFG_GLPI['root_doc']}/ajax/search.php', {

            'action': 'display_criteria_group',

            'itemtype': '$itemtype',

            'meta': true,

            'num': $nbsearchcountvar,

            'p': $json_p,

            '_idor_token': '$idor_display_criteria_group'

         })

         .done(function(data) {

            $(data).insertBefore('#more-criteria$rand_criteria');

            $nbsearchcountvar++;

         });

      });

JAVASCRIPT;
      if ($p['mainform']) {

         $JS .= <<<JAVASCRIPT

         $('.fold-search').on('click', function(event) {

            var search_criteria =  $('#searchcriteria ul li:not(:first-child)');

            event.preventDefault();

            $(this)

               .toggleClass('fa-angle-double-up')

               .toggleClass('fa-angle-double-down');

            search_criteria.toggle();

            window.localStorage.setItem(
               'show_full_searchcriteria',
               search_criteria.first().is(':visible')
            );

         });
         // Init search_criteria state

         var search_criteria_visibility = window.localStorage.getItem('show_full_searchcriteria');
         if (search_criteria_visibility !== undefined && search_criteria_visibility == 'false') {
            $('.fold-search').click();
         }
         $(document).on("click", ".remove-search-criteria", function() {
            var rowID = $(this).data('rowid');
            $('#' + rowID).remove();
            $('#searchcriteria ul li:first-child').addClass('headerRow').show();
         });

JAVASCRIPT;

      }

      echo Html::scriptBlock($JS);
      if (count($p['addhidden'])) {
         foreach ($p['addhidden'] as $key => $val) {
            echo Html::hidden($key, ['value' => $val]);
         }
      }

      if ($p['mainform']) {
         // For dropdown
         echo Html::hidden('itemtype', ['value' => $itemtype]);
         // Reset to start when submit new search
         echo Html::hidden('start', ['value'    => 0]);
      }
      echo "</div>";

      if ($p['mainform']) {
         Html::closeForm();
      }
      
   }

   static function displayCriteria($request = []) {
      global $CFG_GLPI;

      if (!isset($request["itemtype"])
         || !isset($request["num"])) {
         return "";
      }

      $num         = (int) $request['num'];
      $p           = $request['p'];
      $options     = self::getCleanedOptions($request["itemtype"]);
      $randrow     = mt_rand();
      $rowid       = 'searchrow'.$request['itemtype'].$randrow;
      $addclass    = $num == 0 ? ' headerRow' : '';
      $prefix      = isset($p['prefix_crit']) ? $p['prefix_crit'] :'';
      $parents_num = isset($p['parents_num']) ? $p['parents_num'] : [];
      $criteria    = [];
      $from_meta   = isset($request['from_meta']) && $request['from_meta'];

      $sess_itemtype = $request["itemtype"];
      if ($from_meta) {
         $sess_itemtype = $request["parent_itemtype"];
      }

      if (!$criteria = self::findCriteriaInSession($sess_itemtype, $num, $parents_num)) {
         $criteria = self::getDefaultCriteria($request["itemtype"]);
      }

      if (isset($criteria['meta'])
            && $criteria['meta']
            && !$from_meta) {
         return self::displayMetaCriteria($request);
      }

      if (isset($criteria['criteria'])
            && is_array($criteria['criteria'])) {
         return self::displayCriteriaGroup($request);
      }

      echo "<li class='normalcriteria$addclass' id='$rowid'>";

      if (!$from_meta) {
         // First line display add / delete images for normal and meta search items
         if ($num == 0
            && isset($p['mainform'])
            && $p['mainform']) {
            // Instanciate an object to access method
            $item = null;
            if ($request["itemtype"] != 'AllAssets') {
               $item = getItemForItemtype($request["itemtype"]);
            }
            if ($item && $item->maybeDeleted()) {
               echo Html::hidden('is_deleted', [
                  'value' => $p['is_deleted'],
                  'id'    => 'is_deleted'
               ]);
            }
            echo Html::hidden('as_map', [
               'value' => $p['as_map'],
               'id'    => 'as_map'
            ]);
         }
         // echo "<i class='far fa-minus-square remove-search-criteria' alt='-' title=\"".
         //          __s('Delete a rule')."\" data-rowid='$rowid'></i>&nbsp;";

      }

      // Display link item
      $value = '';
      if (!$from_meta) {
         if (isset($criteria["link"])) {
            $value = $criteria["link"];
         }
         $operators = Search::getLogicalOperators(($num == 0));
         PluginServicesDropdown::showFromArray("criteria{$prefix}[$num][link]", $operators, [
            'value' => $value,
            'width' => '80px'
         ]);
      }

      $values   = [];
      // display select box to define search item
      if ($CFG_GLPI['allow_search_view'] == 2 && !isset($request['from_meta'])) {
         $values['view'] = __('Items seen');
      }

      reset($options);
      $group = '';

      foreach ($options as $key => $val) {
         // print groups
         if (!is_array($val)) {
            $group = $val;
         } else if (count($val) == 1) {
            $group = $val['name'];
         } else {
            if ((!isset($val['nosearch']) || ($val['nosearch'] == false))
               && (!$from_meta || !array_key_exists('nometa', $val) || $val['nometa'] !== true)) {
               $values[$group][$key] = $val["name"];
            }
         }
      }
      if ($CFG_GLPI['allow_search_view'] == 1 && !isset($request['from_meta'])) {
         $values['view'] = __('Items seen');
      }
      if ($CFG_GLPI['allow_search_all'] && !isset($request['from_meta'])) {
         $values['all'] = __('All');
      }
      $value = '';

      if (isset($criteria['field'])) {
         $value = $criteria['field'];
      }

      $rand = PluginServicesDropdown::showFromArray("criteria{$prefix}[$num][field]", $values, [
         'value' => $value,
         'width' => '170px'
      ]);
      $field_id = Html::cleanId("dropdown_criteria{$prefix}[$num][field]$rand");
      $spanid   = Html::cleanId('SearchSpan'.$request["itemtype"].$prefix.$num);
      echo "<span id='$spanid' class='d-flex flex-row'>";

      $used_itemtype = $request["itemtype"];
      // Force Computer itemtype for AllAssets to permit to show specific items
      if ($request["itemtype"] == 'AllAssets') {
         $used_itemtype = 'Computer';
      }

      $searchtype = isset($criteria['searchtype'])
                     ? $criteria['searchtype']
                     : "";
      $p_value    = isset($criteria['value'])
                     ? stripslashes($criteria['value'])
                     : "";

      $params = [
         'itemtype'    => $used_itemtype,
         '_idor_token' => Session::getNewIDORToken($used_itemtype),
         'field'       => $value,
         'searchtype'  => $searchtype,
         'value'       => $p_value,
         'num'         => $num,
         'p'           => $p,
      ];
      self::displaySearchoption($params);
      echo "</span>";
      echo"<button data-rowid='$rowid' style='vertical-align: top; margin: 0 2px;'  type='button' class='remove-search-criteria btn btn-sm btn-danger '> ";
         echo"<i alt='-' title=\"".__s('Delete a rule')."\"class='la la-remove'></i>";
      echo"</button>";

      Ajax::updateItemOnSelectEvent(
         $field_id,
         $spanid,
         $CFG_GLPI["root_doc"]."/ajax/search.php",
         [
            'action'     => 'display_searchoption',
            'field'      => '__VALUE__',
         ] + $params
      );

      echo "</li>";
   }

   static function displaySearchoption($request = []) {
      global $CFG_GLPI;
      if (!isset($request["itemtype"])
            || !isset($request["field"])
            || !isset($request["num"])) {
         return "";
      }

      $p      = $request['p'];
      $num    = (int) $request['num'];
      $prefix = isset($p['prefix_crit']) ? $p['prefix_crit'] : '';

      if (!is_subclass_of($request['itemtype'], 'CommonDBTM')) {
         throw new \RuntimeException('Invalid itemtype provided!');
      }

      if (isset($request['meta']) && $request['meta']) {
         $fieldname = 'metacriteria';
      } else {
         $fieldname = 'criteria';
         $request['meta'] = 0;
      }

      $actions = Search::getActionsFor($request["itemtype"], $request["field"]);

      // is it a valid action for type ?
      if (count($actions)
            && (empty($request['searchtype']) || !isset($actions[$request['searchtype']]))) {
         $tmp = $actions;
         unset($tmp['searchopt']);
         $request['searchtype'] = key($tmp);
         unset($tmp);
      }

      $rands = -1;
      $dropdownname = Html::cleanId("spansearchtype$fieldname".
                                    $request["itemtype"].
                                    $prefix.
                                    $num);
      $searchopt = [];
      if (count($actions)>0) {
         // get already get search options
         if (isset($actions['searchopt'])) {
            $searchopt = $actions['searchopt'];
            // No name for clean array with quotes
            unset($searchopt['name']);
            unset($actions['searchopt']);
         }
         $searchtype_name = "{$fieldname}{$prefix}[$num][searchtype]";
         $rands = PluginServicesDropdown::showFromArray($searchtype_name, $actions, [
            'value' => $request["searchtype"],
            'width' => '105px'
         ]);
         $fieldsearch_id = Html::cleanId("dropdown_$searchtype_name$rands");
      }

      echo "<span id='$dropdownname'>";
      $params = [
         'value'       => rawurlencode(stripslashes($request['value'])),
         'searchopt'   => $searchopt,
         'searchtype'  => $request["searchtype"],
         'num'         => $num,
         'itemtype'    => $request["itemtype"],
         '_idor_token' => Session::getNewIDORToken($request["itemtype"]),
         'from_meta'   => isset($request['from_meta'])
                           ? $request['from_meta']
                           : false,
         'field'       => $request["field"],
         'p'           => $p,
      ];
      self::displaySearchoptionValue($params);
      echo "</span>";

      Ajax::updateItemOnSelectEvent(
         $fieldsearch_id,
         $dropdownname,
         $CFG_GLPI["root_doc"]."/ajax/search.php",
         [
            'action'     => 'display_searchoption_value',
            'searchtype' => '__VALUE__',
         ] + $params
      );
   }

   static function displaySearchoptionValue($request = []) {
      if (!isset($request['searchtype'])) {
         return "";
      }

      $p                 = $request['p'];
      $prefix            = isset($p['prefix_crit']) ? $p['prefix_crit'] : '';
      $searchopt         = isset($request['searchopt']) ? $request['searchopt'] : [];
      $request['value']  = rawurldecode($request['value']);
      $fieldname         = isset($request['meta']) && $request['meta']
                              ? 'metacriteria'
                              : 'criteria';
      $inputname         = $fieldname.$prefix.'['.$request['num'].'][value]';
      $display           = false;
      $item              = getItemForItemtype($request['itemtype']);
      $options2          = [];
      $options2['value'] = $request['value'];
      $options2['width'] = '100%';
      // For tree dropdpowns
      $options2['permit_select_parent'] = true;

      switch ($request['searchtype']) {
         case "equals" :
         case "notequals" :
         case "morethan" :
         case "lessthan" :
         case "under" :
         case "notunder" :
            if (!$display && isset($searchopt['field'])) {
               // Specific cases
               switch ($searchopt['table'].".".$searchopt['field']) {
                  // Add mygroups choice to searchopt
                  case "glpi_groups.completename" :
                     $searchopt['toadd'] = ['mygroups' => __('My groups')];
                     break;

                  case "glpi_changes.status" :
                  case "glpi_changes.impact" :
                  case "glpi_changes.urgency" :
                  case "glpi_problems.status" :
                  case "glpi_problems.impact" :
                  case "glpi_problems.urgency" :
                  case "glpi_tickets.status" :
                  case "glpi_tickets.impact" :
                  case "glpi_tickets.urgency" :
                     $options2['showtype'] = 'search';
                     break;

                  case "glpi_changes.priority" :
                  case "glpi_problems.priority" :
                  case "glpi_tickets.priority" :
                     $options2['showtype']  = 'search';
                     $options2['withmajor'] = true;
                     break;

                  case "glpi_tickets.global_validation" :
                     $options2['all'] = true;
                     break;

                  case "glpi_ticketvalidations.status" :
                     $options2['all'] = true;
                     break;

                  case "glpi_users.name" :
                     $options2['right']            = (isset($searchopt['right']) ? $searchopt['right'] : 'all');
                     $options2['inactive_deleted'] = 1;
                     break;
               }

               // Standard datatype usage
               if (!$display && isset($searchopt['datatype'])) {
                  switch ($searchopt['datatype']) {

                     case "date" :
                     case "date_delay" :
                     case "datetime" :
                        $options2['relative_dates'] = true;
                        break;
                  }
               }

               $out = $item->getValueToSelect($searchopt, $inputname, $request['value'], $options2);
               if (strlen($out)) {
                  echo $out;
                  $display = true;
               }

               //Could display be handled by a plugin ?
               if (!$display
                     & $plug = isPluginItemType(getItemTypeForTable($searchopt['table']))) {
                  $display = Plugin::doOneHook(
                     $plug['plugin'],
                     'searchOptionsValues',
                     [
                        'name'           => $inputname,
                        'searchtype'     => $request['searchtype'],
                        'searchoption'   => $searchopt,
                        'value'          => $request['value']
                     ]
                  );
               }

            }
           break;
      }

      // Default case : text field
      if (!$display) {
           echo "<input type='text' class='form-control' size='13' name='$inputname' value=\"".
                  Html::cleanInputText($request['value'])."\">";
      }
   }

   static function displayCriteriaGroup($request = []) {
      $num         = (int) $request['num'];
      $p           = $request['p'];
      $randrow     = mt_rand();
      $rowid       = 'searchrow'.$request['itemtype'].$randrow;
      $addclass    = $num == 0 ? ' headerRow' : '';
      $prefix      = isset($p['prefix_crit']) ? $p['prefix_crit'] : '';
      $parents_num = isset($p['parents_num']) ? $p['parents_num'] : [];

      if (!$criteria = self::findCriteriaInSession($request['itemtype'], $num, $parents_num)) {
         $criteria = [
            'criteria' => self::getDefaultCriteria($request['itemtype']),
         ];
      }

      echo "<li class='normalcriteria$addclass' id='$rowid'>";
      echo "<i class='far fa-minus-square remove-search-criteria' alt='-' title=\"".
               __s('Delete a rule')."\" data-rowid='$rowid'></i>&nbsp;";
      PluginServicesDropdown::showFromArray("criteria{$prefix}[$num][link]", Search::getLogicalOperators(), [
         'value' => isset($criteria["link"]) ? $criteria["link"] : '',
         'width' => '80px'
      ]);

      $parents_num = isset($p['parents_num']) ? $p['parents_num'] : [];
      array_push($parents_num, $num);
      $params = [
         'mainform'    => false,
         'prefix_crit' => "{$prefix}[$num][criteria]",
         'parents_num' => $parents_num,
         'criteria'    => $criteria['criteria'],
      ];

      echo self::showGenericSearch($request['itemtype'], $params);
      echo "</li>";
   }

   static function prepareDatasForSearch($itemtype, array $params, array $forcedisplay = []) {
      global $CFG_GLPI;

      // Default values of parameters
      $p['criteria']            = [];
      $p['metacriteria']        = [];
      $p['sort']                = '1'; //
      $p['order']               = 'ASC';//
      $p['start']               = 0;//
      $p['is_deleted']          = 0;
      $p['export_all']          = 0;
      // if (class_exists($itemtype)) {
      //    $p['target']       = $itemtype::getSearchURL();
      // } else {
      //    $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);
      // }
      if (class_exists($itemtype)) {
         $p['target']       = $itemtype::getSearchURL();
      } else {
         $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);
      }
      $p['display_type']        = self::HTML_OUTPUT;
      $p['showmassiveactions']  = true;
      $p['dont_flush']          = false;
      $p['show_pager']          = true;
      $p['show_footer']         = true;
      $p['no_sort']             = false;
      $p['list_limit']          = $_SESSION['glpilist_limit'];
      $p['massiveactionparams'] = [];

      foreach ($params as $key => $val) {
         switch ($key) {
            case 'order':
               if (in_array($val, ['ASC', 'DESC'])) {
                  $p[$key] = $val;
               }
               break;
            case 'sort':
               $p[$key] = intval($val);
               if ($p[$key] < 0) {
                  $p[$key] = 1;
               }
               break;
            case 'is_deleted':
               if ($val == 1) {
                  $p[$key] = '1';
               }
               break;
            default:
               $p[$key] = $val;
               break;
         }
      }

      // Set display type for export if define
      if (isset($p['display_type'])) {
         // Limit to 10 element
         if ($p['display_type'] == self::GLOBAL_SEARCH) {
            $p['list_limit'] = self::GLOBAL_DISPLAY_COUNT;
         }
      }

      if ($p['export_all']) {
         $p['start'] = 0;
      }

      $data             = [];
      $data['search']   = $p;
      $data['itemtype'] = $itemtype;

      // Instanciate an object to access method
      $data['item'] = null;

      if ($itemtype != 'AllAssets') {
         $data['item'] = getItemForItemtype($itemtype);
      }

      $data['display_type'] = $data['search']['display_type'];

      if (!$CFG_GLPI['allow_search_all']) {
         foreach ($p['criteria'] as $val) {
            if (isset($val['field']) && $val['field'] == 'all') {
               PluginServicesHtml::displayRightError();
            }
         }
      }
      if (!$CFG_GLPI['allow_search_view']) {
         foreach ($p['criteria'] as $val) {
            if (isset($val['field']) && $val['field'] == 'view') {
               PluginServicesHtml::displayRightError();
            }
         }
      }

      /// Get the items to display
      // Add searched items

      $forcetoview = false;
      if (is_array($forcedisplay) && count($forcedisplay)) {
         $forcetoview = true;
      }
      $data['search']['all_search']  = false;
      $data['search']['view_search'] = false;
      // If no research limit research to display item and compute number of item using simple request
      $data['search']['no_search']   = true;

      $data['toview'] = self::addDefaultToView($itemtype, $params);
      $data['meta_toview'] = [];
      if (!$forcetoview) {
         // Add items to display depending of personal prefs
         $displaypref = DisplayPreference::getForTypeUser($itemtype, Session::getLoginUserID());
         if (count($displaypref)) {
            foreach ($displaypref as $val) {
               array_push($data['toview'], $val);
            }
         }
      } else {
         $data['toview'] = array_merge($data['toview'], $forcedisplay);
      }

      if (count($p['criteria']) > 0) {
         // use a recursive closure to push searchoption when using nested criteria
         $parse_criteria = function($criteria) use (&$parse_criteria, &$data) {
            foreach ($criteria as $criterion) {
               // recursive call
               if (isset($criterion['criteria'])) {
                  $parse_criteria($criterion['criteria']);
               } else {
                  // normal behavior
                  if (isset($criterion['field'])
                     && !in_array($criterion['field'], $data['toview'])) {
                     if ($criterion['field'] != 'all'
                        && $criterion['field'] != 'view'
                        && (!isset($criterion['meta'])
                           || !$criterion['meta'])) {
                        array_push($data['toview'], $criterion['field']);
                     } else if ($criterion['field'] == 'all') {
                        $data['search']['all_search'] = true;
                     } else if ($criterion['field'] == 'view') {
                        $data['search']['view_search'] = true;
                     }
                  }

                  if (isset($criterion['value'])
                     && (strlen($criterion['value']) > 0)) {
                     $data['search']['no_search'] = false;
                  }
               }
            }
         };

         // call the closure
         $parse_criteria($p['criteria']);
      }

      if (count($p['metacriteria'])) {
         $data['search']['no_search'] = false;
      }

      // Add order item
      if (!in_array($p['sort'], $data['toview'])) {
         array_push($data['toview'], $p['sort']);
      }

      // Special case for Ticket : put ID in front
      if ($itemtype == 'Ticket') {
         array_unshift($data['toview'], 2);
      }

      $limitsearchopt   = self::getCleanedOptions($itemtype);
      // Clean and reorder toview
      $tmpview = [];
      foreach ($data['toview'] as $val) {
         if (isset($limitsearchopt[$val]) && !in_array($val, $tmpview)) {
            $tmpview[] = $val;
         }
      }
      $data['toview']    = $tmpview;
      $data['tocompute'] = $data['toview'];

      // Force item to display
      if ($forcetoview) {
         foreach ($data['toview'] as $val) {
            if (!in_array($val, $data['tocompute'])) {
               array_push($data['tocompute'], $val);
            }
         }
      }
      return $data;
   }

   static function showDataRelatedList($itemtype,  $criterias) {
      $params = self::manageParams($itemtype, $criterias);
      echo "<div class='search_page'>";
         self::showGenericSearchForRelatedList($itemtype, $params);
      if ($params['as_map'] == 1) {
         self::showMap($itemtype, $params);
      } else {
         self::showList($itemtype, $params);
      }
      echo "</div>";
   }

   static function showRelatedList($itemtype, $params=[]){
      if (isset($_GET['criteria']) && $_GET['criteria']) {
         $params['criteria'] = array_merge($_GET['criteria'], $params['criteria']);
      }

      if ($itemtype::canView()) {
         echo'
            <div class="row">
               <div class="col-xl-12">
                  <div class="m-portlet m-portlet--tab">';
                     echo'<div class="m-portlet__head">';
                        echo'<div class="m-portlet__head-caption">';
                           echo'<div class="m-portlet__head-title">';
                              echo'<span class="m-portlet__head-icon ">';
                              echo'</span>';
                           echo'</div>';
                        echo'</div>';
                        echo'<div class="m-portlet__head-tools">';
                           echo'
                              <a href="'.PluginServicesToolbox::getItemTypeFormURL($itemtype).'?parentid='.$_GET['id'].'" class="btn m-btn--radius btn-md  btn-info">
                                 '.__s("NOUVEAU").'
                              </a>
                           ';
                           if (isset($params['pivotitem']) && $params['pivotitem']) {
                              echo'
                                 <a href="'.PluginServicesToolbox::getItemTypeFormURL($params['pivotitem']).'?parentid='.$_GET['id'].'&parenttype='.$_GET['_itemtype'].'" class="ml-2 btn m-btn--radius btn-md  btn-info">
                                    '.__s("EDITER").'
                                 </a>
                              ';
                           }
                        echo'</div>';
                     echo'</div>';
                     echo'<div class="m-portlet__bodys">';
                        echo'<div style="padding:10px; margin:auto;" class="bg-light">';
                           echo"<button id='togglesearchcriteria' style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;'  type='button' class='btn btn-sm btn-default '><i class='fa fa-filter'></i></button>";
                           echo"<button id='resetsearchcriteria' style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;'  type='button' class='btn btn-sm btn-default '>ALL</button>";
                        echo'</div>';
                        echo'<div style="padding:10px; margin:auto;" class="bg-light">';
                           PluginServicesHtml::searchlist($itemtype,  $params);
                        echo'</div>';
                           self::showDataRelatedList($itemtype, $params);
                        echo'  
                     </div>
                  </div>
               </div>
            </div>
         ';
      } else {
         //View is not granted.
         PluginServicesHtml::displayRightError();
      }
   }

   static function showFago($itemtype, $criterias) {
      if(!empty($criterias)){
         $_GET = $criterias;
      }

      $params = self::manageParams($itemtype, $_GET);
      echo "<div class='search_page'>";
         self::showGenericSearch($itemtype, $params);
         if ($params['as_map'] == 1) {
            self::showMap($itemtype, $params);
         } else {
            self::showListFago($itemtype, $params);
         }
      echo "</div>";
   }

   static function showCustom($itemtype, $_GET_DATA) {
      $params = self::manageParams($itemtype, $_GET_DATA);
      echo "<div class='search_page'>";
      self::showGenericSearch($itemtype, $params);
      if ($params['as_map'] == 1) {
         self::showMap($itemtype, $params);
      } else {
         self::showList($itemtype, $params);
      }
      echo "</div>";
   }
   
   /**
   * Print generic Header Column
   *
   * @param integer          $type     Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
   * @param string           $value    Value to display
   * @param integer          &$num     Column number
   * @param string           $linkto   Link display element (HTML specific) (default '')
   * @param boolean|integer  $issort   Is the sort column ? (default 0)
   * @param string           $order    Order type ASC or DESC (defaut '')
   * @param string           $options  Options to add (default '')
   *
   * @return string HTML to display
  **/
   static function showHeaderItem($type, $value, &$num, $linkto = "", $issort = 0, $order = "",
                                 $options = "") {
      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf

         case self::PDF_OUTPUT_PORTRAIT :
            global $PDF_TABLE;
            $PDF_TABLE .= "<th $options>";
            $PDF_TABLE .= Html::clean($value);
            $PDF_TABLE .= "</th>\n";
            break;

         case self::SYLK_OUTPUT : //sylk
            global $SYLK_HEADER,$SYLK_SIZE;
            $SYLK_HEADER[$num] = self::sylk_clean($value);
            $SYLK_SIZE[$num]   = Toolbox::strlen($SYLK_HEADER[$num]);
            break;

         case self::CSV_OUTPUT : //CSV
            $out = "\"".self::csv_clean($value)."\"".$_SESSION["glpicsv_delimiter"];
            break;

         default :
            $class = "";
            if ($issort) {
               $class = "order_$order";
            }
            $out = "<th $options class='$class'>";
            if (!empty($linkto)) {
               // $out .= "<a href=\"$linkto\">";
               $out .= "<a onclick='loadPage(\"$linkto\")' href='javascript:void(0);'>";
            }
            $out .= $value;
            if (!empty($linkto)) {
               $out .= "</a>";
            }
            $out .= "</th>\n";
      }
      $num++;
      return $out;
   }

   static function displayDataFago(array $data) {
      global $CFG_GLPI;

      $item = null;
      if (class_exists($data['itemtype'])) {
         $item = new $data['itemtype']();
      }

      if (!isset($data['data']) || !isset($data['data']['totalcount'])) {
         return false;
      }
      // Contruct Pager parameters
      $globallinkto
         = Toolbox::append_params(['criteria'
                                          => Toolbox::stripslashes_deep($data['search']['criteria']),
                                       'metacriteria'
                                          => Toolbox::stripslashes_deep($data['search']['metacriteria'])],
                                 '&amp;');
      $parameters = "sort=".$data['search']['sort']."&amp;order=".$data['search']['order'].'&amp;'.
                     $globallinkto;

      if (isset($_GET['_in_modal'])) {
         $parameters .= "&amp;_in_modal=1";
      }

      // Global search header
      if ($data['display_type'] == self::GLOBAL_SEARCH) {
         if ($data['item']) {
            echo "<div class='center'><h2>".$data['item']->getTypeName();
            // More items
            if ($data['data']['totalcount'] > ($data['search']['start'] + self::GLOBAL_DISPLAY_COUNT)) {
               echo " <a href='".$data['search']['target']."?$parameters'>".__('All')."</a>";
            }
            echo "</h2></div>\n";
         } else {
            return false;
         }
      }

      // If the begin of the view is before the number of items
      if ($data['data']['count'] > 0) {
         // Display pager only for HTML
         if ($data['display_type'] == self::HTML_OUTPUT) {
            // For plugin add new parameter if available
            if ($plug = isPluginItemType($data['itemtype'])) {
               $out = PluginServicesPlugin::doOneHook($plug['plugin'], 'addParamFordynamicReport', $data['itemtype']);
               if (is_array($out) && count($out)) {
                  $parameters .= Toolbox::append_params($out, '&amp;');
               }
            }
            $search_config_top    = "";
            $search_config_bottom = "";
            if (!isset($_GET['_in_modal'])) {

               $search_config_top = $search_config_bottom
                  = "<div class='pager_controls'>";

               $map_link = '';
               if (null == $item || $item->maybeLocated()) {
                  // $map_link = "<input type='checkbox' name='as_map' id='as_map' value='1'";
                  // if ($data['search']['as_map'] == 1) {
                  //    $map_link .= " checked='checked'";
                  // }
                  // $map_link .= "/>";
                  // $map_link .= "<label for='as_map'><span title='".__s('Show as map')."' class='pointer fa fa-globe-americas'
                  //    onClick=\"toogle('as_map','','','');
                  //                document.forms['searchform".$data["itemtype"]."'].submit();\"></span></label>";
               }
               $search_config_top .= $map_link;

               if (Session::haveRightsOr('search_config', [
                  DisplayPreference::PERSONAL,
                  DisplayPreference::GENERAL
               ])) {
                  $pref_url = $CFG_GLPI["root_doc"]."/front/displaypreference.form.php?itemtype=".
                  $data['itemtype'];
                  // $pref_url = Toolbox::getItemTypeFormURL('PluginServicesDisplayPreference', true)."?itemtype=".
                  // $data['itemtype'];
                  $options_link = "<button title='".
                     __s('Select default items to show')."' onClick=\"$('#%id').dialog('open');\" 
                     style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;' type='button' ata-toggle='modal' data-target='#exampleModal' class='btn btn-default btn-sm bg-secondary'><i class='fa fa-wrench pointer'></i></button>";
                  // $options_link = '
                  //    <button onclick="loadPageModal(\''.$pref_url.'\')"  style="vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;" type="button" class="btn btn-default btn-sm bg-secondary" data-toggle="modal" data-target="#m_modal_2">
                  //    <i class="fa fa-wrench pointer"></i>
                  //    </button>
                  // ';

                  $search_config_top .= str_replace('%id', 'search_config_top', $options_link);
                  $search_config_bottom .= str_replace('%id', 'search_config_bottom', $options_link);

                  $search_config_top .= PluginServicesAjax::createIframeModalWindow(
                     'search_config_top',
                     $pref_url,
                     [
                        'title'         => __('Select default items to show'),
                        'reloadonclose' => true,
                        'display'       => false,
                        'width'         => 550,
                     ]
                  );
                  $search_config_bottom .= PluginServicesAjax::createIframeModalWindow(
                     'search_config_bottom',
                     $pref_url,
                     [
                        'title'         => __('Select default items to show'),
                        'reloadonclose' => true,
                        'display'       => false,
                        'width'         => 550,
                     ]
                  );
               }
            }

            if ($item !== null && $item->maybeDeleted()) {
               $delete_ctrl        = self::isDeletedSwitch($data['search']['is_deleted'], $data['itemtype']);
               // $search_config_top .= $delete_ctrl;
            }

            if ($data['search']['show_pager']) {
               echo '<div class="row m-2 dataTables_wrapper" >';
                  PluginServicesHtml::printPager($data['search']['start'], $data['data']['totalcount'],
                              $data['search']['target'], $parameters, $data['itemtype'], 0,
                                 $search_config_top);
               echo '</div>';
            }

            $search_config_top    .= "</div>";
            $search_config_bottom .= "</div>";
         }

         // Define begin and end var for loop
         // Search case
         $begin_display = $data['data']['begin'];
         $end_display   = $data['data']['end'];

         // Form to massive actions
         $isadmin = ($data['item'] && $data['item']->canUpdate());
         if (!$isadmin
               && Infocom::canApplyOn($data['itemtype'])) {
            $isadmin = (Infocom::canUpdate() || Infocom::canCreate());
         }
         if ($data['itemtype'] != 'AllAssets') {
            $showmassiveactions = ($data['search']['showmassiveactions'] ?? true)
               && count(MassiveAction::getAllMassiveActions($data['item'],
                                                            $data['search']['is_deleted']));
         } else {
            $showmassiveactions = $data['search']['showmassiveactions'] ?? true;
         }
         if ($data['search']['as_map'] == 0) {
            $massformid = 'massform'.$data['itemtype'];
            if ($showmassiveactions
               && ($data['display_type'] == self::HTML_OUTPUT)) {

               Html::openMassiveActionsForm($massformid);
               $massiveactionparams          = $data['search']['massiveactionparams'];
               $massiveactionparams['num_displayed'] = $end_display-$begin_display;
               $massiveactionparams['fixed']         = false;
               $massiveactionparams['is_deleted']    = $data['search']['is_deleted'];
               $massiveactionparams['container']     = $massformid;

               PluginServicesHtml::showMassiveActions($massiveactionparams);
            }

            // Compute number of columns to display
            // Add toview elements
            $nbcols = count($data['data']['cols']);

            if (($data['display_type'] == self::HTML_OUTPUT)
               && $showmassiveactions) { // HTML display - massive modif
               $nbcols++;
            }

            // Display List Header
            echo self::showHeader($data['display_type'], $end_display-$begin_display+1, $nbcols);

            // New Line for Header Items Line
            $headers_line        = '';
            $headers_line_top    = '';
            $headers_line_bottom = '';

            $headers_line_top .= self::showBeginHeader($data['display_type']);
            $headers_line_top .= self::showNewLine($data['display_type']);

            if ($data['display_type'] == self::HTML_OUTPUT) {
               // $headers_line_bottom .= self::showBeginHeader($data['display_type']);
               $headers_line_bottom .= self::showNewLine($data['display_type']);
            }

            $header_num = 1;

            if (($data['display_type'] == self::HTML_OUTPUT)
                  && $showmassiveactions) { // HTML display - massive modif
               $headers_line_top
                  .= self::showHeaderItem($data['display_type'],
                  PluginServicesHtml::getCheckAllAsCheckbox($massformid),
                                          $header_num, "", 0, $data['search']['order']);
               if ($data['display_type'] == self::HTML_OUTPUT) {
                  $headers_line_bottom
                     .= self::showHeaderItem($data['display_type'],
                                             PluginServicesHtml::getCheckAllAsCheckbox($massformid),
                                             $header_num, "", 0, $data['search']['order']);
               }
            }

            // Display column Headers for toview items
            $metanames = [];
            foreach ($data['data']['cols'] as $val) {
               $linkto = '';
               if (!$val['meta']
                  && !$data['search']['no_sort']
                  && (!isset($val['searchopt']['nosort'])
                     || !$val['searchopt']['nosort'])) {

                  $linkto = $data['search']['target'].(strpos($data['search']['target'], '?') ? '&amp;' : '?').
                              "itemtype=".$data['itemtype']."&amp;sort=".
                              $val['id']."&amp;order=".
                              (($data['search']['order'] == "ASC") ?"DESC":"ASC").
                              "&amp;start=".$data['search']['start']."&amp;".$globallinkto;
               }

               $name = $val["name"];

               // prefix by group name (corresponding to optgroup in dropdown) if exists
               if (isset($val['groupname'])) {
                  $groupname = $val['groupname'];
                  if (is_array($groupname)) {
                     //since 9.2, getSearchOptions has been changed
                     $groupname = $groupname['name'];
                  }
                  $name  = "$groupname - $name";
               }

               // Not main itemtype add itemtype to display
               if ($data['itemtype'] != $val['itemtype']) {
                  if (!isset($metanames[$val['itemtype']])) {
                     if ($metaitem = getItemForItemtype($val['itemtype'])) {
                        $metanames[$val['itemtype']] = $metaitem->getTypeName();
                     }
                  }
                  $name = sprintf(__('%1$s - %2$s'), $metanames[$val['itemtype']],
                                 $val["name"]);
               }

               $headers_line .= self::showHeaderItem($data['display_type'],
                                                      $name,
                                                      $header_num, $linkto,
                                                      (!$val['meta']
                                                      && ($data['search']['sort'] == $val['id'])),
                                                      $data['search']['order']);
            }

            // Add specific column Header
            if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
               $headers_line .= self::showHeaderItem($data['display_type'], __('Item type'),
                                                      $header_num);
            }
            // End Line for column headers
            $headers_line        .= self::showEndLine($data['display_type']);

            $headers_line_top    .= $headers_line;
            if ($data['display_type'] == self::HTML_OUTPUT) {
               $headers_line_bottom .= $headers_line;
            }

            $headers_line_top    .= self::showEndHeader($data['display_type']);
            // $headers_line_bottom .= self::showEndHeader($data['display_type']);

            echo $headers_line_top;

            // Init list of items displayed
            if ($data['display_type'] == self::HTML_OUTPUT) {
               Session::initNavigateListItems($data['itemtype']);
            }

            // Num of the row (1=header_line)
            $row_num = 1;

            $massiveaction_field = 'id';
            if (($data['itemtype'] != 'AllAssets')
                  && isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
               $massiveaction_field = 'refID';
            }

            $typenames = [];
            // Display Loop
            foreach ($data['data']['rows'] as $rowkey => $row) {
               // Column num
               $item_num = 1;
               $row_num++;
               // New line
               echo self::showNewLine($data['display_type'], ($row_num%2),
                                    $data['search']['is_deleted']);

               $current_type       = (isset($row['TYPE']) ? $row['TYPE'] : $data['itemtype']);
               $massiveaction_type = $current_type;

               if (($data['itemtype'] != 'AllAssets')
                  && isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                  $massiveaction_type = $data['itemtype'];
               }

               // Add item in item list
               Session::addToNavigateListItems($current_type, $row["id"]);

               if (($data['display_type'] == self::HTML_OUTPUT)
                     && $showmassiveactions) { // HTML display - massive modif
                  $tmpcheck = "";

                  if (($data['itemtype'] == 'Entity')
                        && !in_array($row["id"], $_SESSION["glpiactiveentities"])) {
                     $tmpcheck = "&nbsp;";

                  } else if ($data['itemtype'] == 'PluginServicesUser'
                           && !Session::canViewAllEntities()
                           && !Session::haveAccessToOneOfEntities(Profile_User::getUserEntities($row["id"], false))) {
                     $tmpcheck = "&nbsp;";

                  } else if (($data['item'] instanceof CommonDBTM)
                              && $data['item']->maybeRecursive()
                              && !in_array($row["entities_id"], $_SESSION["glpiactiveentities"])) {
                     $tmpcheck = "&nbsp;";

                  } else {
                     $tmpcheck = PluginServicesHtml::getMassiveActionCheckBox($massiveaction_type,
                                                               $row[$massiveaction_field]);
                  }
                  echo self::showItem($data['display_type'], $tmpcheck, $item_num, $row_num,
                                       "width='10'");
               }

               // Print other toview items
               foreach ($data['data']['cols'] as $col) {
                  $colkey = "{$col['itemtype']}_{$col['id']}";
                  if (!$col['meta']) {
                     echo self::showItem($data['display_type'], $row[$colkey]['displayname'],
                                          $item_num, $row_num,
                                          self::displayConfigItem($data['itemtype'], $col['id'],
                                                                  $row, $colkey));
                  } else { // META case
                     echo self::showItem($data['display_type'], $row[$colkey]['displayname'],
                                       $item_num, $row_num);
                  }
               }

               if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                  if (!isset($typenames[$row["TYPE"]])) {
                     if ($itemtmp = getItemForItemtype($row["TYPE"])) {
                        $typenames[$row["TYPE"]] = $itemtmp->getTypeName();
                     }
                  }
                  echo self::showItem($data['display_type'], $typenames[$row["TYPE"]],
                                    $item_num, $row_num);
               }
               // End Line
               echo self::showEndLine($data['display_type']);
               // Flush ONLY for an HTML display (issue #3348)
               if ($data['display_type'] == self::HTML_OUTPUT
                     && !$data['search']['dont_flush']) {
                  Html::glpi_flush();
               }
            }

            // Create title
            $title = '';
            if (($data['display_type'] == self::PDF_OUTPUT_LANDSCAPE)
                  || ($data['display_type'] == self::PDF_OUTPUT_PORTRAIT)) {
               $title = self::computeTitle($data);
            }

            // if ($data['search']['show_footer']) {
            //    if ($data['display_type'] == self::HTML_OUTPUT) {
            //       echo $headers_line_bottom;
            //    }
            // }

            // Display footer (close table)
            echo self::showFooter($data['display_type'], $title, $data['data']['count']);
            // echo '<div class="mt-3 d-flex justify-content-start" style="border-radius:5px; box-shadow: 0px 1px 2px 1px #d2d2d2; padding:10px;">';
            echo '<div class="row m-3 pb-3" >';
            if ($data['search']['show_footer']) {
               // Delete selected item
               if ($data['display_type'] == self::HTML_OUTPUT) {
                  if ($showmassiveactions) {
                     $massiveactionparams['ontop'] = false;
                     // Html::showMassiveActions($massiveactionparams);
                     // End form for delete item
                     Html::closeForm();
                  } else {
                     echo "<br>";
                  }
               }
               if ($data['display_type'] == self::HTML_OUTPUT
                  && $data['search']['show_pager']) { // In case of HTML display
                     PluginServicesHtml::printPager($data['search']['start'], $data['data']['totalcount'],
                                 $data['search']['target'], $parameters, '', 0,
                                    $search_config_bottom);

               }
            }
            echo "</div>";
         }
      } else {
         if (!isset($_GET['_in_modal'])) {
            echo "<div class='center pager_controls'>";
            // if (null == $item || $item->maybeLocated()) {
            //    $map_link = "<input type='checkbox' name='as_map' id='as_map' value='1'";
            //    if ($data['search']['as_map'] == 1) {
            //       $map_link .= " checked='checked'";
            //    }
            //    $map_link .= "/>";
            //    $map_link .= "<label for='as_map'><span title='".__s('Show as map')."' class='pointer fa fa-globe-americas'
            //       onClick=\"toogle('as_map','','','');
            //                   document.forms['searchform".$data["itemtype"]."'].submit();\"></span></label>";
            //    echo $map_link;
            // }

            // if ($item !== null && $item->maybeDeleted()) {
            //    echo self::isDeletedSwitch($data['search']['is_deleted'], $data['itemtype']);
            // }
            echo "</div>";
         }

            // Define begin and end var for loop
            // Search case
            $begin_display = $data['data']['begin'];
            $end_display   = $data['data']['end'];

            $nbcols          = count($data['data']['cols']);

            // Display List Header
            echo self::showHeader($data['display_type'], $end_display-$begin_display+1, $nbcols);

            // New Line for Header Items Line
            $headers_line        = '';
            $headers_line_top    = '';
            $headers_line_bottom = '';

            $headers_line_top .= self::showBeginHeader($data['display_type']);
            $headers_line_top .= self::showNewLine($data['display_type']);

            if ($data['display_type'] == self::HTML_OUTPUT) {
               // $headers_line_bottom .= self::showBeginHeader($data['display_type']);
               $headers_line_bottom .= self::showNewLine($data['display_type']);
            }

            $header_num = 1;

         foreach ($data['data']['cols'] as $val) {
            $linkto = '';
            if (!$val['meta']
               && !$data['search']['no_sort']
               && (!isset($val['searchopt']['nosort'])
                  || !$val['searchopt']['nosort'])) {

               $linkto = $data['search']['target'].(strpos($data['search']['target'], '?') ? '&amp;' : '?').
                           "itemtype=".$data['itemtype']."&amp;sort=".
                           $val['id']."&amp;order=".
                           (($data['search']['order'] == "ASC") ?"DESC":"ASC").
                           "&amp;start=".$data['search']['start']."&amp;".$globallinkto;
            }

            $name = $val["name"];

            // prefix by group name (corresponding to optgroup in dropdown) if exists
            if (isset($val['groupname'])) {
               $groupname = $val['groupname'];
               if (is_array($groupname)) {
                  //since 9.2, getSearchOptions has been changed
                  $groupname = $groupname['name'];
               }
               $name  = "$groupname - $name";
            }

            // Not main itemtype add itemtype to display
            if ($data['itemtype'] != $val['itemtype']) {
               if (!isset($metanames[$val['itemtype']])) {
                  if ($metaitem = getItemForItemtype($val['itemtype'])) {
                     $metanames[$val['itemtype']] = $metaitem->getTypeName();
                  }
               }
               $name = sprintf(__('%1$s - %2$s'), $metanames[$val['itemtype']],
                              $val["name"]);
            }

            $headers_line .= self::showHeaderItem($data['display_type'],
                                                   $name,
                                                   $header_num, $linkto,
                                                   (!$val['meta']
                                                   && ($data['search']['sort'] == $val['id'])),
                                                   $data['search']['order']);
         }

         // Add specific column Header
         if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
            $headers_line .= self::showHeaderItem($data['display_type'], __('Item type'),
                                                   $header_num);
         }
         // End Line for column headers
         $headers_line        .= self::showEndLine($data['display_type']);

         $headers_line_top    .= $headers_line;
         if ($data['display_type'] == self::HTML_OUTPUT) {
            $headers_line_bottom .= $headers_line;
         }

         $headers_line_top    .= self::showEndHeader($data['display_type']);
         // $headers_line_bottom .= self::showEndHeader($data['display_type']);

         echo $headers_line_top;

         // Create title
         $title = '';
         if (($data['display_type'] == self::PDF_OUTPUT_LANDSCAPE)
               || ($data['display_type'] == self::PDF_OUTPUT_PORTRAIT)) {
            $title = self::computeTitle($data);
         }

         // Display footer (close table)
         echo self::showFooter($data['display_type'], $title, $data['data']['count']);

         echo self::showError($data['display_type']);

         echo "</div>";
      }
   }

   static function displayDataRelatedList(array $data) {
      global $CFG_GLPI;

      $item = null;
      if (class_exists($data['itemtype'])) {
         $item = new $data['itemtype']();
      }

      if (!isset($data['data']) || !isset($data['data']['totalcount'])) {
         return false;
      }
      // Contruct Pager parameters
      $globallinkto
         = Toolbox::append_params(['criteria'
                                          => Toolbox::stripslashes_deep($data['search']['criteria']),
                                       'metacriteria'
                                          => Toolbox::stripslashes_deep($data['search']['metacriteria'])],
                                 '&amp;');
      $parameters = "sort=".$data['search']['sort']."&amp;order=".$data['search']['order'].'&amp;'.
                     $globallinkto;

      if (isset($_GET['_in_modal'])) {
         $parameters .= "&amp;_in_modal=1";
      }

      // Global search header
      if ($data['display_type'] == self::GLOBAL_SEARCH) {
         if ($data['item']) {
            echo "<div class='center'><h2>".$data['item']->getTypeName();
            // More items
            if ($data['data']['totalcount'] > ($data['search']['start'] + self::GLOBAL_DISPLAY_COUNT)) {
               echo " <a href='".$data['search']['target']."?$parameters'>".__('All')."</a>";
            }
            echo "</h2></div>\n";
         } else {
            return false;
         }
      }

      // If the begin of the view is before the number of items
      if ($data['data']['count'] > 0) {
         // Display pager only for HTML
         if ($data['display_type'] == self::HTML_OUTPUT) {
            // For plugin add new parameter if available
            if ($plug = isPluginItemType($data['itemtype'])) {
               $out = PluginServicesPlugin::doOneHook($plug['plugin'], 'addParamFordynamicReport', $data['itemtype']);
               if (is_array($out) && count($out)) {
                  $parameters .= Toolbox::append_params($out, '&amp;');
               }
            }
            $search_config_top    = "";
            $search_config_bottom = "";
            if (!isset($_GET['_in_modal'])) {

               $search_config_top = $search_config_bottom
                  = "<div class='pager_controls'>";

               $map_link = '';
               if (null == $item || $item->maybeLocated()) {
                  // $map_link = "<input type='checkbox' name='as_map' id='as_map' value='1'";
                  // if ($data['search']['as_map'] == 1) {
                  //    $map_link .= " checked='checked'";
                  // }
                  // $map_link .= "/>";
                  // $map_link .= "<label for='as_map'><span title='".__s('Show as map')."' class='pointer fa fa-globe-americas'
                  //    onClick=\"toogle('as_map','','','');
                  //                document.forms['searchform".$data["itemtype"]."'].submit();\"></span></label>";
               }
               $search_config_top .= $map_link;

               if (Session::haveRightsOr('search_config', [
                  DisplayPreference::PERSONAL,
                  DisplayPreference::GENERAL
               ])) {
                  $options_link = "<button title='".
                     __s('Select default items to show')."' onClick=\"$('#%id').dialog('open');\" 
                     style='vertical-align: top; margin: 0 2px; border: 1px solid #dddfe5;' type='button' class='btn btn-default btn-sm bg-secondary'><i class='fa fa-wrench pointer'></i></button>";
                  // $options_link = "<span class='fa fa-wrench pointer' title='".
                  //       __s('Select default items to show')."' onClick=\"$('#%id').dialog('open');\">
                  //       <span class='sr-only'>" .  __s('Select default items to show') . "</span></span>";

                     $search_config_top .= str_replace('%id', 'search_config_top', $options_link);
                     $search_config_bottom .= str_replace('%id', 'search_config_bottom', $options_link);

                     $pref_url = $CFG_GLPI["root_doc"]."/front/displaypreference.form.php?itemtype=".
                                 $data['itemtype'];
                     $search_config_top .= PluginServicesAjax::createIframeModalWindow(
                        'search_config_top',
                        $pref_url,
                        [
                           'title'         => __('Select default items to show'),
                           'reloadonclose' => true,
                           'display'       => false,
                           'width'         => 800,
                        ]
                     );
                     $search_config_bottom .= PluginServicesAjax::createIframeModalWindow(
                        'search_config_bottom',
                        $pref_url,
                        [
                           'title'         => __('Select default items to show'),
                           'reloadonclose' => true,
                           'display'       => false,
                           'width'         => 800,
                        ]
                     );
               }
            }

            if ($item !== null && $item->maybeDeleted()) {
               $delete_ctrl        = self::isDeletedSwitch($data['search']['is_deleted'], $data['itemtype']);
               // $search_config_top .= $delete_ctrl;
            }
            $search_config_top .="</div>";
            if ($data['search']['show_pager']) {
               echo '<div class=" row m-2" >';
                  PluginServicesHtml::printAjaxPager('', $data['search']['start'], $data['data']['totalcount'], $search_config_top, true);
               echo '</div>';
            }

            $search_config_top    .= "</div>";
            $search_config_bottom .= "</div>";
         }

         // Define begin and end var for loop
         // Search case
         $begin_display = $data['data']['begin'];
         $end_display   = $data['data']['end'];

         // Form to massive actions
         $isadmin = ($data['item'] && $data['item']->canUpdate());
         if (!$isadmin
               && Infocom::canApplyOn($data['itemtype'])) {
            $isadmin = (Infocom::canUpdate() || Infocom::canCreate());
         }
         if ($data['itemtype'] != 'AllAssets') {
            $showmassiveactions = ($data['search']['showmassiveactions'] ?? true)
               && count(MassiveAction::getAllMassiveActions($data['item'],
                                                            $data['search']['is_deleted']));
         } else {
            $showmassiveactions = $data['search']['showmassiveactions'] ?? true;
         }
         if ($data['search']['as_map'] == 0) {
            $massformid = 'massform'.$data['itemtype'];
            if ($showmassiveactions
               && ($data['display_type'] == self::HTML_OUTPUT)) {

               Html::openMassiveActionsForm($massformid);
               $massiveactionparams          = $data['search']['massiveactionparams'];
               $massiveactionparams['num_displayed'] = $end_display-$begin_display;
               $massiveactionparams['fixed']         = false;
               $massiveactionparams['is_deleted']    = $data['search']['is_deleted'];
               $massiveactionparams['container']     = $massformid;

               PluginServicesHtml::showMassiveActions($massiveactionparams);
            }

            // Compute number of columns to display
            // Add toview elements
            $nbcols = count($data['data']['cols']);

            if (($data['display_type'] == self::HTML_OUTPUT)
               && $showmassiveactions) { // HTML display - massive modif
               $nbcols++;
            }

            // Display List Header
            echo self::showHeader($data['display_type'], $end_display-$begin_display+1, $nbcols);

            // New Line for Header Items Line
            $headers_line        = '';
            $headers_line_top    = '';
            $headers_line_bottom = '';

            $headers_line_top .= self::showBeginHeader($data['display_type']);
            $headers_line_top .= self::showNewLine($data['display_type']);

            if ($data['display_type'] == self::HTML_OUTPUT) {
               // $headers_line_bottom .= self::showBeginHeader($data['display_type']);
               $headers_line_bottom .= self::showNewLine($data['display_type']);
            }

            $header_num = 1;

            if (($data['display_type'] == self::HTML_OUTPUT)
                  && $showmassiveactions) { // HTML display - massive modif
               $headers_line_top
                  .= self::showHeaderItem($data['display_type'],
                  PluginServicesHtml::getCheckAllAsCheckbox($massformid),
                                          $header_num, "", 0, $data['search']['order']);
               if ($data['display_type'] == self::HTML_OUTPUT) {
                  $headers_line_bottom
                     .= self::showHeaderItem($data['display_type'],
                                             PluginServicesHtml::getCheckAllAsCheckbox($massformid),
                                             $header_num, "", 0, $data['search']['order']);
               }
            }

            // Display column Headers for toview items
            $metanames = [];
            foreach ($data['data']['cols'] as $val) {
               $linkto = '';
               if (!$val['meta']
                  && !$data['search']['no_sort']
                  && (!isset($val['searchopt']['nosort'])
                     || !$val['searchopt']['nosort'])) {

                  // $linkto = $data['search']['target'].(strpos($data['search']['target'], '?') ? '&amp;' : '?').
                  //             "itemtype=".$data['itemtype']."&amp;sort=".
                  //             $val['id']."&amp;order=".
                  //             (($data['search']['order'] == "ASC") ?"DESC":"ASC").
                  //             "&amp;start=".$data['search']['start']."&amp;".$globallinkto;
               }

               $name = $val["name"];

               // prefix by group name (corresponding to optgroup in dropdown) if exists
               if (isset($val['groupname'])) {
                  $groupname = $val['groupname'];
                  if (is_array($groupname)) {
                     //since 9.2, getSearchOptions has been changed
                     $groupname = $groupname['name'];
                  }
                  // $name  = "$groupname - $name";
                  $name  = "$name";
               }

               // Not main itemtype add itemtype to display
               if ($data['itemtype'] != $val['itemtype']) {
                  if (!isset($metanames[$val['itemtype']])) {
                     if ($metaitem = getItemForItemtype($val['itemtype'])) {
                        $metanames[$val['itemtype']] = $metaitem->getTypeName();
                     }
                  }
                  $name = sprintf(__('%1$s - %2$s'), $metanames[$val['itemtype']],
                                 $val["name"]);
               }

               $headers_line .= self::showHeaderItem($data['display_type'],
                                                      $name,
                                                      $header_num, $linkto,
                                                      (!$val['meta']
                                                      && ($data['search']['sort'] == $val['id'])),
                                                      $data['search']['order']);
            }

            // Add specific column Header
            if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
               $headers_line .= self::showHeaderItem($data['display_type'], __('Item type'),
                                                      $header_num);
            }
            // End Line for column headers
            $headers_line        .= self::showEndLine($data['display_type']);

            $headers_line_top    .= $headers_line;
            if ($data['display_type'] == self::HTML_OUTPUT) {
               $headers_line_bottom .= $headers_line;
            }

            $headers_line_top    .= self::showEndHeader($data['display_type']);
            // $headers_line_bottom .= self::showEndHeader($data['display_type']);

            echo $headers_line_top;

            // Init list of items displayed
            if ($data['display_type'] == self::HTML_OUTPUT) {
               Session::initNavigateListItems($data['itemtype']);
            }

            // Num of the row (1=header_line)
            $row_num = 1;

            $massiveaction_field = 'id';
            if (($data['itemtype'] != 'AllAssets')
                  && isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
               $massiveaction_field = 'refID';
            }

            $typenames = [];
            // Display Loop
            foreach ($data['data']['rows'] as $rowkey => $row) {
               // Column num
               $item_num = 1;
               $row_num++;
               // New line
               echo self::showNewLine($data['display_type'], ($row_num%2),
                                    $data['search']['is_deleted']);

               $current_type       = (isset($row['TYPE']) ? $row['TYPE'] : $data['itemtype']);
               $massiveaction_type = $current_type;

               if (($data['itemtype'] != 'AllAssets')
                  && isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                  $massiveaction_type = $data['itemtype'];
               }

               // Add item in item list
               Session::addToNavigateListItems($current_type, $row["id"]);

               if (($data['display_type'] == self::HTML_OUTPUT)
                     && $showmassiveactions) { // HTML display - massive modif
                  $tmpcheck = "";

                  if (($data['itemtype'] == 'Entity')
                        && !in_array($row["id"], $_SESSION["glpiactiveentities"])) {
                     $tmpcheck = "&nbsp;";

                  } else if ($data['itemtype'] == 'PluginServicesUser'
                           && !Session::canViewAllEntities()
                           && !Session::haveAccessToOneOfEntities(Profile_User::getUserEntities($row["id"], false))) {
                     $tmpcheck = "&nbsp;";

                  } else if (($data['item'] instanceof CommonDBTM)
                              && $data['item']->maybeRecursive()
                              && !in_array($row["entities_id"], $_SESSION["glpiactiveentities"])) {
                     $tmpcheck = "&nbsp;";

                  } else {
                     $tmpcheck = PluginServicesHtml::getMassiveActionCheckBox($massiveaction_type,
                                                               $row[$massiveaction_field]);
                  }
                  echo self::showItem($data['display_type'], $tmpcheck, $item_num, $row_num,
                                       "width='10'");
               }

               // Print other toview items
               foreach ($data['data']['cols'] as $col) {
                  $colkey = "{$col['itemtype']}_{$col['id']}";
                  if (!$col['meta']) {
                     echo self::showItem($data['display_type'], $row[$colkey]['displayname'],
                                          $item_num, $row_num,
                                          self::displayConfigItem($data['itemtype'], $col['id'],
                                                                  $row, $colkey));
                  } else { // META case
                     echo self::showItem($data['display_type'], $row[$colkey]['displayname'],
                                       $item_num, $row_num);
                  }
               }

               if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                  if (!isset($typenames[$row["TYPE"]])) {
                     if ($itemtmp = getItemForItemtype($row["TYPE"])) {
                        $typenames[$row["TYPE"]] = $itemtmp->getTypeName();
                     }
                  }
                  echo self::showItem($data['display_type'], $typenames[$row["TYPE"]],
                                    $item_num, $row_num);
               }
               // End Line
               echo self::showEndLine($data['display_type']);
               // Flush ONLY for an HTML display (issue #3348)
               if ($data['display_type'] == self::HTML_OUTPUT
                     && !$data['search']['dont_flush']) {
                  Html::glpi_flush();
               }
            }

            // Create title
            $title = '';
            if (($data['display_type'] == self::PDF_OUTPUT_LANDSCAPE)
                  || ($data['display_type'] == self::PDF_OUTPUT_PORTRAIT)) {
               $title = self::computeTitle($data);
            }

            // if ($data['search']['show_footer']) {
            //    if ($data['display_type'] == self::HTML_OUTPUT) {
            //       echo $headers_line_bottom;
            //    }
            // }

            // Display footer (close table)
            echo self::showFooter($data['display_type'], $title, $data['data']['count']);
            // echo '<div class="mt-3 d-flex justify-content-start" style="border-radius:5px; box-shadow: 0px 1px 2px 1px #d2d2d2; padding:10px;">';
            echo '<div class="row m-3 pb-3" >';
            if ($data['search']['show_footer']) {
               // Delete selected item
               if ($data['display_type'] == self::HTML_OUTPUT) {
                  if ($showmassiveactions) {
                     $massiveactionparams['ontop'] = false;
                     // Html::showMassiveActions($massiveactionparams);
                     // End form for delete item
                     Html::closeForm();
                  } else {
                     echo "<br>";
                  }
               }
               if ($data['display_type'] == self::HTML_OUTPUT
                  && $data['search']['show_pager']) { // In case of HTML display
                  PluginServicesHtml::printAjaxPager('', $data['search']['start'], $data['data']['totalcount']);

               }
            }
            echo "</div>";
         }
      } else {
         if (!isset($_GET['_in_modal'])) {
            echo "<div class='center pager_controls'>";
            // if (null == $item || $item->maybeLocated()) {
            //    $map_link = "<input type='checkbox' name='as_map' id='as_map' value='1'";
            //    if ($data['search']['as_map'] == 1) {
            //       $map_link .= " checked='checked'";
            //    }
            //    $map_link .= "/>";
            //    $map_link .= "<label for='as_map'><span title='".__s('Show as map')."' class='pointer fa fa-globe-americas'
            //       onClick=\"toogle('as_map','','','');
            //                   document.forms['searchform".$data["itemtype"]."'].submit();\"></span></label>";
            //    echo $map_link;
            // }

            // if ($item !== null && $item->maybeDeleted()) {
            //    echo self::isDeletedSwitch($data['search']['is_deleted'], $data['itemtype']);
            // }
            echo "</div>";
         }

            // Define begin and end var for loop
            // Search case
            $begin_display = $data['data']['begin'];
            $end_display   = $data['data']['end'];

            $nbcols          = count($data['data']['cols']);

            // Display List Header
            echo self::showHeader($data['display_type'], $end_display-$begin_display+1, $nbcols);

            // New Line for Header Items Line
            $headers_line        = '';
            $headers_line_top    = '';
            $headers_line_bottom = '';

            $headers_line_top .= self::showBeginHeader($data['display_type']);
            $headers_line_top .= self::showNewLine($data['display_type']);

            if ($data['display_type'] == self::HTML_OUTPUT) {
               // $headers_line_bottom .= self::showBeginHeader($data['display_type']);
               $headers_line_bottom .= self::showNewLine($data['display_type']);
            }

            $header_num = 1;

         foreach ($data['data']['cols'] as $val) {
            $linkto = '';
            if (!$val['meta']
               && !$data['search']['no_sort']
               && (!isset($val['searchopt']['nosort'])
                  || !$val['searchopt']['nosort'])) {

               $linkto = $data['search']['target'].(strpos($data['search']['target'], '?') ? '&amp;' : '?').
                           "itemtype=".$data['itemtype']."&amp;sort=".
                           $val['id']."&amp;order=".
                           (($data['search']['order'] == "ASC") ?"DESC":"ASC").
                           "&amp;start=".$data['search']['start']."&amp;".$globallinkto;
            }

            $name = $val["name"];

            // prefix by group name (corresponding to optgroup in dropdown) if exists
            if (isset($val['groupname'])) {
               $groupname = $val['groupname'];
               if (is_array($groupname)) {
                  //since 9.2, getSearchOptions has been changed
                  $groupname = $groupname['name'];
               }
               $name  = "$groupname - $name";
            }

            // Not main itemtype add itemtype to display
            if ($data['itemtype'] != $val['itemtype']) {
               if (!isset($metanames[$val['itemtype']])) {
                  if ($metaitem = getItemForItemtype($val['itemtype'])) {
                     $metanames[$val['itemtype']] = $metaitem->getTypeName();
                  }
               }
               $name = sprintf(__('%1$s - %2$s'), $metanames[$val['itemtype']],
                              $val["name"]);
            }

            $headers_line .= self::showHeaderItem($data['display_type'],
                                                   $name,
                                                   $header_num, $linkto,
                                                   (!$val['meta']
                                                   && ($data['search']['sort'] == $val['id'])),
                                                   $data['search']['order']);
         }

         // Add specific column Header
         if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
            $headers_line .= self::showHeaderItem($data['display_type'], __('Item type'),
                                                   $header_num);
         }
         // End Line for column headers
         $headers_line        .= self::showEndLine($data['display_type']);

         $headers_line_top    .= $headers_line;
         if ($data['display_type'] == self::HTML_OUTPUT) {
            $headers_line_bottom .= $headers_line;
         }

         $headers_line_top    .= self::showEndHeader($data['display_type']);
         // $headers_line_bottom .= self::showEndHeader($data['display_type']);

         echo $headers_line_top;

         // Create title
         $title = '';
         if (($data['display_type'] == self::PDF_OUTPUT_LANDSCAPE)
               || ($data['display_type'] == self::PDF_OUTPUT_PORTRAIT)) {
            $title = self::computeTitle($data);
         }

         // Display footer (close table)
         echo self::showFooter($data['display_type'], $title, $data['data']['count']);

         echo self::showError($data['display_type']);

         echo "</div>";
      }
   }

   /**
    * Print generic error
    *
    * @param integer $type     Display type (0=HTML, 1=Sylk, 2=PDF, 3=CSV)
    * @param string  $message  Message to display, if empty "no item found" will be displayed
    *
    * @return string HTML to display
   **/
   static function showError($type, $message = "") {
      if (strlen($message) == 0) {
         $message = __('No item found');
      }

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
         case self::PDF_OUTPUT_PORTRAIT :
         case self::SYLK_OUTPUT : //sylk
         case self::CSV_OUTPUT : //csv
            break;

         default :
            $out = "<div class='d-flex justify-content-center p-5'>$message</div>\n";
      }
      return $out;
   }

   static function showItem($type, $value, &$num, $row, $extraparam = '') {

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
         case self::PDF_OUTPUT_PORTRAIT :
            global $PDF_TABLE;
            $value = preg_replace('/'.self::LBBR.'/', '<br>', $value);
            $value = preg_replace('/'.self::LBHR.'/', '<hr>', $value);
            $PDF_TABLE .= "<td $extraparam valign='top'>";
            $PDF_TABLE .= Html::weblink_extract(Html::clean($value));
            $PDF_TABLE .= "</td>\n";

            break;

         case self::SYLK_OUTPUT : //sylk
            global $SYLK_ARRAY,$SYLK_SIZE;
            $value                  = Html::weblink_extract(Html::clean($value));
            $value = preg_replace('/'.self::LBBR.'/', '<br>', $value);
            $value = preg_replace('/'.self::LBHR.'/', '<hr>', $value);
            $SYLK_ARRAY[$row][$num] = self::sylk_clean($value);
            $SYLK_SIZE[$num]        = max($SYLK_SIZE[$num],
                                          Toolbox::strlen($SYLK_ARRAY[$row][$num]));
            break;

         case self::CSV_OUTPUT : //csv
            $value = preg_replace('/'.self::LBBR.'/', '<br>', $value);
            $value = preg_replace('/'.self::LBHR.'/', '<hr>', $value);
            $value = Html::weblink_extract(Html::clean($value));
            $out   = "\"".self::csv_clean($value)."\"".$_SESSION["glpicsv_delimiter"];
            break;

         default :
            global $CFG_GLPI;
            $out = "<td $extraparam valign='top'>";

            if (!preg_match('/'.self::LBHR.'/', $value)) {
               $values = preg_split('/'.self::LBBR.'/i', $value);
               $line_delimiter = '<br>';
            } else {
               $values = preg_split('/'.self::LBHR.'/i', $value);
               $line_delimiter = '<hr>';
            }

            if (count($values) > 1
               && Toolbox::strlen($value) > $CFG_GLPI['cut']) {
               $value = '';
               foreach ($values as $v) {
                  $value .= $v.$line_delimiter;
               }
               $value = preg_replace('/'.self::LBBR.'/', '<br>', $value);
               $value = preg_replace('/'.self::LBHR.'/', '<hr>', $value);
               $value = '<div class="fup-popup">'.$value.'</div>';
               // $valTip = "&nbsp;".Html::showToolTip(
               //    $value, [
               //       'awesome-class'   => 'fa-comments',
               //       'display'         => false,
               //       'autoclose'       => false,
               //       'onclick'         => true
               //    ]
               // );
               // $out .= $values[0] . $valTip;
               $out .= $values[0] ;
            } else {
               $value = preg_replace('/'.self::LBBR.'/', '<br>', $value);
               $value = preg_replace('/'.self::LBHR.'/', '<hr>', $value);
               $out .= $value;
            }
            $out .= "</td>\n";
      }
      $num++;
      return $out;
   }

   static function showList($itemtype, $params=[]) {
      self::displayDataRelatedList(self::getDatas($itemtype, $params));
   }

   static function showListFago($itemtype, $params=[]) {
      if(isset($params["add"])){
         self::displayDataFago(self::getDatas($itemtype, $params), $params["add"]);
      }else{
         self::displayDataFago(self::getDatas($itemtype, $params), []);
      }
   }

   static function showHeader($type, $rows, $cols, $fixed = 0) {
      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
         case self::PDF_OUTPUT_PORTRAIT :
            global $PDF_TABLE;
            $PDF_TABLE = "<table style='margin-top:0px;' cellspacing=\"0\" cellpadding=\"1\" border=\"1\" >";
            break;

         case self::SYLK_OUTPUT : // Sylk
            global $SYLK_ARRAY, $SYLK_HEADER, $SYLK_SIZE;
            $SYLK_ARRAY  = [];
            $SYLK_HEADER = [];
            $SYLK_SIZE   = [];
            // entetes HTTP
            header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
            header('Pragma: private'); /// IE BUG + SSL
            header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
            header("Content-disposition: filename=glpi.slk");
            header('Content-type: application/octetstream');
            // entete du fichier
            echo "ID;PGLPI_EXPORT\n"; // ID;Pappli
            echo "\n";
            // formats
            echo "P;PGeneral\n";
            echo "P;P#,##0.00\n";       // P;Pformat_1 (reels)
            echo "P;P#,##0\n";          // P;Pformat_2 (entiers)
            echo "P;P@\n";              // P;Pformat_3 (textes)
            echo "\n";
            // polices
            echo "P;EArial;M200\n";
            echo "P;EArial;M200\n";
            echo "P;EArial;M200\n";
            echo "P;FArial;M200;SB\n";
            echo "\n";
            // nb lignes * nb colonnes
            echo "B;Y".$rows;
            echo ";X".$cols."\n"; // B;Yligmax;Xcolmax
            echo "\n";
            break;

         case self::CSV_OUTPUT : // csv
            header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
            header('Pragma: private'); /// IE BUG + SSL
            header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
            header("Content-disposition: filename=glpi.csv");
            header('Content-type: text/csv');
            // zero width no break space (for excel)
            echo"\xEF\xBB\xBF";
            break;

         default :
            if ($fixed) {
               $out = "<div class='center'><table border='0' class='tab_cadre_fixehov'>\n";
            } else {
               $out = "<div class='table-responsive'><table border='0' class='m-0 tab_cadrehov table table-striped m-table'>\n";
            }
      }
      return $out;
   }

   /**
    * Generic Function to display Items
    *
    * @since 9.4: $num param has been dropped
    *
    * @param string  $itemtype        item type
    * @param integer $ID              ID of the SEARCH_OPTION item
    * @param array   $data            array containing data results
    * @param boolean $meta            is a meta item ? (default 0)
    * @param array   $addobjectparams array added parameters for union search
    * @param string  $orig_itemtype   Original itemtype, used for union_search_type
    *
    * @return string String to print
   **/
   

   static function giveItemorld($itemtype, $ID, array $data, $meta = 0,
                              array $addobjectparams = [], $orig_itemtype = null) {
      global $CFG_GLPI;

      $searchopt = &self::getOptions($itemtype);
      if ($itemtype == 'AllAssets' || isset($CFG_GLPI["union_search_type"][$itemtype])
            && ($CFG_GLPI["union_search_type"][$itemtype] == $searchopt[$ID]["table"])) {

         $oparams = [];
         if (isset($searchopt[$ID]['addobjectparams'])
               && $searchopt[$ID]['addobjectparams']) {
            $oparams = $searchopt[$ID]['addobjectparams'];
         }

         // Search option may not exists in subtype
         // This is the case for "Inventory number" for a Software listed from ReservationItem search
         $subtype_so = &self::getOptions($data["TYPE"]);
         if (!array_key_exists($ID, $subtype_so)) {
            return '';
         }

         return self::giveItem($data["TYPE"], $ID, $data, $meta, $oparams, $itemtype);
      }
      $so = $searchopt[$ID];
      $orig_id = $ID;
      $ID = ($orig_itemtype !== null ? $orig_itemtype : $itemtype) . '_' . $ID;

      if (count($addobjectparams)) {
         $so = array_merge($so, $addobjectparams);
      }
      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $out = Plugin::doOneHook(
            $plug['plugin'],
            'giveItem',
            $itemtype, $orig_id, $data, $ID
         );
         if (!empty($out)) {
            return $out;
         }
      }

      if (isset($so["table"])) {
         $table     = $so["table"];
         $field     = $so["field"];
         $linkfield = $so["linkfield"];

         /// TODO try to clean all specific cases using SpecificToDisplay

         switch ($table.'.'.$field) {
            case "glpi_users.name" :
               if ($itemtype == 'PluginAssistancesTicket'
                  && Session::getCurrentInterface() == 'helpdesk'
                  && $orig_id == 5
                  && Entity::getUsedConfig(
                     'anonymize_support_agents',
                     $itemtype::getById($data['id'])->getEntityId()
                  )
               ) {
                  return __("Helpdesk");
               }

               // USER search case
               if (($itemtype != 'User')
                     && isset($so["forcegroupby"]) && $so["forcegroupby"]) {
                  $out           = "";
                  $count_display = 0;
                  $added         = [];

                  $showuserlink = 0;
                  if (Session::haveRight('user', READ)) {
                     $showuserlink = 1;
                  }

                  for ($k=0; $k<$data[$ID]['count']; $k++) {

                     if ((isset($data[$ID][$k]['name']) && ($data[$ID][$k]['name'] > 0))
                           || (isset($data[$ID][$k][2]) && ($data[$ID][$k][2] != ''))) {
                        if ($count_display) {
                           $out .= self::LBBR;
                        }

                        if ($itemtype == 'PluginAssistancesTicket') {
                           if (isset($data[$ID][$k]['name'])
                                 && $data[$ID][$k]['name'] > 0) {
                              $userdata = getUserName($data[$ID][$k]['name'], 2);
                              $tooltip  = "";
                              $userdata["link"] = PluginServicesUser::getFormURLWithID($data[$ID][$k]['name']);
                              if (Session::haveRight('user', READ)) {
                                 $tooltip = PluginServicesHtml::showToolTip($userdata["comment"],
                                                            ['link'    => $userdata["link"],
                                                                  'display' => false]);
                              }
                              $out .= sprintf(__('%1$s %2$s'), $userdata['name'], $tooltip);
                              $count_display++;
                           }
                        } else {
                           $userdata = getUserName($data[$ID][$k]['name'], 2);
                           // $out .= getUserName($data[$ID][$k]['name'], $showuserlink);
                           $out .= "<a href='".PluginServicesToolbox::getFormURLWithID($data[$ID][$k]['name'],true, 'User')."'>".$userdata['name']."</a>";
                           $count_display++;
                        }

                        // Manage alternative_email for tickets_users
                        if (($itemtype == 'PluginAssistancesTicket')
                              && isset($data[$ID][$k][2])) {

                           $split = explode(self::LONGSEP, $data[$ID][$k][2]);
                           for ($l=0; $l<count($split); $l++) {
                              $split2 = explode(" ", $split[$l]);
                              if ((count($split2) == 2) && ($split2[0] == 0) && !empty($split2[1])) {
                                 if ($count_display) {
                                    $out .= self::LBBR;
                                 }
                                 $count_display++;
                                 $out .= "<a href='mailto:".$split2[1]."'>".$split2[1]."</a>";
                              }
                           }
                        }
                     }
                  }
                  return $out;
               }
               
               if ($itemtype != 'PluginServicesUser') {
                  $toadd = '';
                  if (($itemtype == 'PluginAssistancesTicket')
                        && ($data[$ID][0]['id'] > 0)) {
                     $userdata = getUserName($data[$ID][0]['id'], 2);
                     $toadd    = PluginServicesHtml::showToolTip($userdata["comment"],
                                                   ['link'    => $userdata["link"],
                                                         'display' => false]);
                  }
                  $usernameformat = formatUserName($data[$ID][0]['id'], $data[$ID][0]['name'],
                                                   $data[$ID][0]['realname'],
                                                   $data[$ID][0]['firstname'], 1);
                  return sprintf(__('%1$s %2$s'), $usernameformat, $toadd);
               }
               break;

            case "glpi_profiles.name" :
               if (($itemtype == 'PluginServicesUser')
                  && ($orig_id == 20)) {
                  $out           = "";

                  $count_display = 0;
                  $added         = [];
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0
                           && !in_array($data[$ID][$k]['name']."-".$data[$ID][$k]['entities_id'],
                                       $added)) {
                        $text = sprintf(__('%1$s - %2$s'), $data[$ID][$k]['name'],
                                          Dropdown::getDropdownName('glpi_entities',
                                                                  $data[$ID][$k]['entities_id']));
                        $comp = '';
                        if ($data[$ID][$k]['is_recursive']) {
                           $comp = __('R');
                           if ($data[$ID][$k]['is_dynamic']) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                           }
                        }
                        if ($data[$ID][$k]['is_dynamic']) {
                           $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                        }
                        if (!empty($comp)) {
                           $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                        }
                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        $out     .= $text;
                        $added[]  = $data[$ID][$k]['name']."-".$data[$ID][$k]['entities_id'];
                     }
                  }
                  return $out;
               }
               break;

            case "glpi_entities.completename" :
               if ($itemtype == 'PluginServicesUser') {

                  $out           = "";
                  $added         = [];
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (isset($data[$ID][$k]['name'])
                        && (strlen(trim($data[$ID][$k]['name'])) > 0)
                        && !in_array($data[$ID][$k]['name']."-".$data[$ID][$k]['profiles_id'],
                                    $added)) {
                     $text = sprintf(__('%1$s - %2$s'), $data[$ID][$k]['name'],
                                       Dropdown::getDropdownName('glpi_profiles',
                                                               $data[$ID][$k]['profiles_id']));
                        $comp = '';
                        if ($data[$ID][$k]['is_recursive']) {
                           $comp = __('R');
                           if ($data[$ID][$k]['is_dynamic']) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                           }
                        }
                        if ($data[$ID][$k]['is_dynamic']) {
                           $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                        }
                        if (!empty($comp)) {
                           $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                        }
                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        $out    .= $text;
                        $added[] = $data[$ID][$k]['name']."-".$data[$ID][$k]['profiles_id'];
                     }
                  }
                  return $out;
               }
               break;

            case "glpi_documenttypes.icon" :
               if (!empty($data[$ID][0]['name'])) {
                  return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".
                           $data[$ID][0]['name']."'>";
               }
               return "&nbsp;";

            case "glpi_documents.filename" :
               $doc = new Document();
               if ($doc->getFromDB($data['id'])) {
                  return $doc->getDownloadLink();
               }
               return NOT_AVAILABLE;

            case "glpi_tickets_tickets.tickets_id_1" :
               $out        = "";
               $displayed  = [];
               for ($k=0; $k<$data[$ID]['count']; $k++) {

                  $linkid = ($data[$ID][$k]['tickets_id_2'] == $data['id'])
                                 ? $data[$ID][$k]['name']
                                 : $data[$ID][$k]['tickets_id_2'];
                  if (($linkid > 0) && !isset($displayed[$linkid])) {
                     $text  = "<a ";
                     $text .= "href=\"".PluginAssistancesTicket::getFormURLWithID($linkid)."\">";
                     $text .= Dropdown::getDropdownName('glpi_tickets', $linkid)."</a>";
                     if (count($displayed)) {
                        $out .= self::LBBR;
                     }
                     $displayed[$linkid] = $linkid;
                     $out               .= $text;
                  }
               }
               return $out;

            case "glpi_problems.id" :
               if ($so["datatype"] == 'count') {
                  if (($data[$ID][0]['name'] > 0)
                      && Session::haveRight("problem", Problem::READALL)) {
                     if ($itemtype == 'ITILCategory') {
                        $options['criteria'][0]['field']      = 7;
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = $data['id'];
                        $options['criteria'][0]['link']       = 'AND';
                     } else {
                        $options['criteria'][0]['field']       = 12;
                        $options['criteria'][0]['searchtype']  = 'equals';
                        $options['criteria'][0]['value']       = 'all';
                        $options['criteria'][0]['link']        = 'AND';

                        $options['metacriteria'][0]['itemtype']   = $itemtype;
                        $options['metacriteria'][0]['field']      = self::getOptionNumber($itemtype,
                              'name');
                        $options['metacriteria'][0]['searchtype'] = 'equals';
                        $options['metacriteria'][0]['value']      = $data['id'];
                        $options['metacriteria'][0]['link']       = 'AND';
                     }

                     $options['reset'] = 'reset';

                     $out  = "<a id='problem$itemtype".$data['id']."' ";
                     $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                              Toolbox::append_params($options, '&amp;')."\">";
                     $out .= $data[$ID][0]['name']."</a>";
                     return $out;
                  }
               }
               break;

            case "glpi_tickets.id" :
               if ($so["datatype"] == 'count') {
                  if (($data[$ID][0]['name'] > 0)
                        && Session::haveRight("ticket", PluginAssistancesTicket::READALL)) {

                     if ($itemtype == 'PluginServicesUser') {
                        // Requester
                        if ($ID == 'User_60') {
                           $options['criteria'][0]['field']      = 4;
                           $options['criteria'][0]['searchtype']= 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';
                        }

                        // Writer
                        if ($ID == 'User_61') {
                           $options['criteria'][0]['field']      = 22;
                           $options['criteria'][0]['searchtype']= 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';
                        }
                        // Assign
                        if ($ID == 'User_64') {
                           $options['criteria'][0]['field']      = 5;
                           $options['criteria'][0]['searchtype']= 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';
                        }
                     } else if ($itemtype == 'ITILCategory') {
                        $options['criteria'][0]['field']      = 7;
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = $data['id'];
                        $options['criteria'][0]['link']       = 'AND';

                     } else {
                        $options['criteria'][0]['field']       = 12;
                        $options['criteria'][0]['searchtype']  = 'equals';
                        $options['criteria'][0]['value']       = 'all';
                        $options['criteria'][0]['link']        = 'AND';

                        $options['metacriteria'][0]['itemtype']   = $itemtype;
                        $options['metacriteria'][0]['field']      = self::getOptionNumber($itemtype,
                                                                                          'name');
                        $options['metacriteria'][0]['searchtype'] = 'equals';
                        $options['metacriteria'][0]['value']      = $data['id'];
                        $options['metacriteria'][0]['link']       = 'AND';
                     }

                     $options['reset'] = 'reset';

                     $out  = "<a id='ticket$itemtype".$data['id']."' ";
                     $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                              Toolbox::append_params($options, '&amp;')."\">";
                     $out .= $data[$ID][0]['name']."</a>";
                     return $out;
                  }
               }
               break;

            case "glpi_tickets.time_to_resolve" :
            case "glpi_problems.time_to_resolve" :
            case "glpi_changes.time_to_resolve" :
            case "glpi_tickets.time_to_own" :
            case "glpi_tickets.internal_time_to_own" :
            case "glpi_tickets.internal_time_to_resolve" :
               // Due date + progress
               if (in_array($orig_id, [151, 158, 181, 186])) {
                  $out = Html::convDateTime($data[$ID][0]['name']);

                  // No due date in waiting status
                  if ($data[$ID][0]['status'] == CommonITILObject::WAITING) {
                     return '';
                  }
                  if (empty($data[$ID][0]['name'])) {
                     return '';
                  }
                  if (($data[$ID][0]['status'] == PluginAssistancesTicket::SOLVED)
                        || ($data[$ID][0]['status'] == PluginAssistancesTicket::CLOSED)) {
                     return $out;
                  }

                  $itemtype = PluginServicesFagoUtils::getSubItemForItemType(getItemTypeForTable($table));
                  $item = new $itemtype();
                  $item->getFromDB($data['id']);
                  $percentage  = 0;
                  $totaltime   = 0;
                  $currenttime = 0;
                  $slaField    = 'slas_id';

                  // define correct sla field
                  switch ($table.'.'.$field) {
                     case "glpi_tickets.time_to_resolve" :
                        $slaField = 'slas_id_ttr';
                        break;
                     case "glpi_tickets.time_to_own" :
                        $slaField = 'slas_id_tto';
                        break;
                     case "glpi_tickets.internal_time_to_own" :
                        $slaField = 'olas_id_tto';
                        break;
                     case "glpi_tickets.internal_time_to_resolve" :
                        $slaField = 'olas_id_ttr';
                        break;
                  }

                  switch ($table.'.'.$field) {
                     // If ticket has been taken into account : no progression display
                     case "glpi_tickets.time_to_own" :
                     case "glpi_tickets.internal_time_to_own" :
                        if (($item->fields['takeintoaccount_delay_stat'] > 0)) {
                           return $out;
                        }
                        break;
                  }

                  if ($item->isField($slaField) && $item->fields[$slaField] != 0) { // Have SLA
                     $sla = new SLA();
                     $sla->getFromDB($item->fields[$slaField]);
                     $currenttime = $sla->getActiveTimeBetween($item->fields['date'],
                                                               date('Y-m-d H:i:s'));
                     $totaltime   = $sla->getActiveTimeBetween($item->fields['date'],
                                                               $data[$ID][0]['name']);
                  } else {
                     $calendars_id = Entity::getUsedConfig('calendars_id',
                                                            $item->fields['entities_id']);
                     if ($calendars_id != 0) { // Ticket entity have calendar
                        $calendar = new Calendar();
                        $calendar->getFromDB($calendars_id);
                        $currenttime = $calendar->getActiveTimeBetween($item->fields['date'],
                                                                        date('Y-m-d H:i:s'));
                        $totaltime   = $calendar->getActiveTimeBetween($item->fields['date'],
                                                                        $data[$ID][0]['name']);
                     } else { // No calendar
                        $currenttime = strtotime(date('Y-m-d H:i:s'))
                                                   - strtotime($item->fields['date']);
                        $totaltime   = strtotime($data[$ID][0]['name'])
                                                   - strtotime($item->fields['date']);
                     }
                  }
                  if ($totaltime != 0) {
                     $percentage  = round((100 * $currenttime) / $totaltime);
                  } else {
                     // Total time is null : no active time
                     $percentage = 100;
                  }
                  if ($percentage > 100) {
                     $percentage = 100;
                  }
                  $percentage_text = $percentage;

                  if ($_SESSION['glpiduedatewarning_unit'] == '%') {
                     $less_warn_limit = $_SESSION['glpiduedatewarning_less'];
                     $less_warn       = (100 - $percentage);
                  } else if ($_SESSION['glpiduedatewarning_unit'] == 'hour') {
                     $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * HOUR_TIMESTAMP;
                     $less_warn       = ($totaltime - $currenttime);
                  } else if ($_SESSION['glpiduedatewarning_unit'] == 'day') {
                     $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * DAY_TIMESTAMP;
                     $less_warn       = ($totaltime - $currenttime);
                  }

                  if ($_SESSION['glpiduedatecritical_unit'] == '%') {
                     $less_crit_limit = $_SESSION['glpiduedatecritical_less'];
                     $less_crit       = (100 - $percentage);
                  } else if ($_SESSION['glpiduedatecritical_unit'] == 'hour') {
                     $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * HOUR_TIMESTAMP;
                     $less_crit       = ($totaltime - $currenttime);
                  } else if ($_SESSION['glpiduedatecritical_unit'] == 'day') {
                     $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * DAY_TIMESTAMP;
                     $less_crit       = ($totaltime - $currenttime);
                  }

                  $color = $_SESSION['glpiduedateok_color'];
                  if ($less_crit < $less_crit_limit) {
                     $color = $_SESSION['glpiduedatecritical_color'];
                  } else if ($less_warn < $less_warn_limit) {
                     $color = $_SESSION['glpiduedatewarning_color'];
                  }

                  if (!isset($so['datatype'])) {
                     $so['datatype'] = 'progressbar';
                  }

                  $progressbar_data = [
                     'text'         => Html::convDateTime($data[$ID][0]['name']),
                     'percent'      => $percentage,
                     'percent_text' => $percentage_text,
                     'color'        => $color
                  ];
               }
               break;

            case "glpi_softwarelicenses.number" :
               if ($data[$ID][0]['min'] == -1) {
                  return __('Unlimited');
               }
               if (empty($data[$ID][0]['name'])) {
                  return 0;
               }
               return $data[$ID][0]['name'];

            case "glpi_auth_tables.name" :
               return Auth::getMethodName($data[$ID][0]['name'], $data[$ID][0]['auths_id'], 1,
                                          $data[$ID][0]['ldapname'].$data[$ID][0]['mailname']);

            case "glpi_reservationitems.comment" :
               if (empty($data[$ID][0]['name'])) {
                  $text = __('None');
               } else {
                  $text = Html::resume_text($data[$ID][0]['name']);
               }
               if (Session::haveRight('reservation', UPDATE)) {
                  return "<a title=\"".__s('Modify the comment')."\"
                           href='".ReservationItem::getFormURLWithID($data['refID'])."' >".$text."</a>";
               }
               return $text;

            case 'glpi_crontasks.description' :
               $tmp = new CronTask();
               return $tmp->getDescription($data[$ID][0]['name']);

            case 'glpi_changes.status':
               $status = Change::getStatus($data[$ID][0]['name']);
               return "<span class='no-wrap'>".
                        Change::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                        "</span>";

            case 'glpi_problems.status':
               $status = Problem::getStatus($data[$ID][0]['name']);
               return "<span class='no-wrap'>".
                        Problem::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                     "</span>";

            case 'glpi_tickets.status':
               $status = PluginAssistancesTicket::getStatus($data[$ID][0]['name']);
               return "<span class='no-wrap'>".
                     PluginAssistancesTicket::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                     "</span>";

            case 'glpi_projectstates.name':
               $out = '';
               $name = $data[$ID][0]['name'];
               if (isset($data[$ID][0]['trans'])) {
                  $name = $data[$ID][0]['trans'];
               }
               if ($itemtype == 'ProjectState') {
                  $out =   "<a href='".ProjectState::getFormURLWithID($data[$ID][0]["id"])."'>". $name."</a></div>";
               } else {
                  $out = $name;
               }
               return $out;

            case 'glpi_items_tickets.items_id' :
            case 'glpi_items_problems.items_id' :
            case 'glpi_changes_items.items_id' :
            case 'glpi_certificates_items.items_id' :
            case 'glpi_appliances_items.items_id' :
               if (!empty($data[$ID])) {
                  $items = [];
                  foreach ($data[$ID] as $key => $val) {
                     if (is_numeric($key)) {
                        if (!empty($val['itemtype'])
                              && ($item = getItemForItemtype($val['itemtype']))) {
                           if ($item->getFromDB($val['name'])) {
                              $items[] = $item->getLink(['comments' => true]);
                           }
                        }
                     }
                  }
                  if (!empty($items)) {
                     return implode("<br>", $items);
                  }
               }
               return '&nbsp;';

            case 'glpi_items_tickets.itemtype' :
            case 'glpi_items_problems.itemtype' :
               if (!empty($data[$ID])) {
                  $itemtypes = [];
                  foreach ($data[$ID] as $key => $val) {
                     if (is_numeric($key)) {
                        if (!empty($val['name'])
                              && ($item = getItemForItemtype($val['name']))) {
                           $item = new $val['name']();
                           $name = $item->getTypeName();
                           $itemtypes[] = __($name);
                        }
                     }
                  }
                  if (!empty($itemtypes)) {
                     return implode("<br>", $itemtypes);
                  }
               }

               return '&nbsp;';

            case 'glpi_tickets.name' :
            case 'glpi_problems.name' :
            case 'glpi_changes.name' :

               if (isset($data[$ID][0]['content'])
                     && isset($data[$ID][0]['id'])
                     && isset($data[$ID][0]['status'])) {
                  $link = $itemtype::getFormURLWithID($data[$ID][0]['id']);

                  $hdecode = Html::entity_decode_deep($data[$ID][0]['content']);
                  $content = Toolbox::unclean_cross_side_scripting_deep($hdecode);

                  $out  = "<a data-toggle='tooltip' data-placement='top' title=\"$content\" id='$itemtype".$data[$ID][0]['id']."' href=\"".$link;
                  // Force solution tab if solved
                  if ($item = getItemForItemtype($itemtype)) {
                     if (in_array($data[$ID][0]['status'], $item->getSolvedStatusArray())) {
                        $out .= "&amp;forcetab=$itemtype$2";
                     }
                  }
                  $out .= "\">";
                  $name = $data[$ID][0]['name'];
                  if ($_SESSION["glpiis_ids_visible"]
                        || empty($data[$ID][0]['name'])) {
                     $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][0]['id']);
                  }
                  $out    .= $name."</a>";
                  // $out     = sprintf(__('%1$s %2$s'), $out,
                  //                   PluginServicesHtml::showToolTip(nl2br(Html::Clean($content)),
                  //                                           ['applyto' => $itemtype.
                  //                                                             $data[$ID][0]['id'],
                  //                                                 'display' => false]));
                  return $out;
               }

            case 'glpi_ticketvalidations.status' :
               $out   = '';
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if ($data[$ID][$k]['name']) {
                     $status  = TicketValidation::getStatus($data[$ID][$k]['name']);
                     $bgcolor = TicketValidation::getStatusColor($data[$ID][$k]['name']);
                     $out    .= (empty($out)?'':self::LBBR).
                                 "<div style=\"background-color:".$bgcolor.";\">".$status.'</div>';
                  }
               }
               return $out;

            case 'glpi_ticketsatisfactions.satisfaction' :
               if (self::$output_type == self::HTML_OUTPUT) {
                  return TicketSatisfaction::displaySatisfaction($data[$ID][0]['name']);
               }
               break;

            case 'glpi_projects._virtual_planned_duration' :
               return Html::timestampToString(ProjectTask::getTotalPlannedDurationForProject($data["id"]),
                                                false);

            case 'glpi_projects._virtual_effective_duration' :
               return Html::timestampToString(ProjectTask::getTotalEffectiveDurationForProject($data["id"]),
                                                false);

            case 'glpi_cartridgeitems._virtual' :
               return Cartridge::getCount($data["id"], $data[$ID][0]['alarm_threshold'],
                                          self::$output_type != self::HTML_OUTPUT);

            case 'glpi_printers._virtual' :
               return Cartridge::getCountForPrinter($data["id"],
                                                      self::$output_type != self::HTML_OUTPUT);

            case 'glpi_consumableitems._virtual' :
               return Consumable::getCount($data["id"], $data[$ID][0]['alarm_threshold'],
                                          self::$output_type != self::HTML_OUTPUT);

            case 'glpi_links._virtual' :
               $out = '';
               $link = new Link();
               if (($item = getItemForItemtype($itemtype))
                     && $item->getFromDB($data['id'])
               ) {
                  $data = Link::getLinksDataForItem($item);
                  $count_display = 0;
                  foreach ($data as $val) {
                     $links = Link::getAllLinksFor($item, $val);
                     foreach ($links as $link) {
                        if ($count_display) {
                           $out .=  self::LBBR;
                        }
                        $out .= $link;
                        $count_display++;
                     }
                  }
               }
               return $out;

            case 'glpi_reservationitems._virtual' :
               if ($data[$ID][0]['is_active']) {
                  return "<a href='reservation.php?reservationitems_id=".
                                          $data["refID"]."' title=\"".__s('See planning')."\">".
                                          "<i class='far fa-calendar-alt'></i><span class='sr-only'>".__('See planning')."</span></a>";
               } else {
                  return "&nbsp;";
               }

            case "glpi_tickets.priority" :
            case "glpi_problems.priority" :
            case "glpi_changes.priority" :
            case "glpi_projects.priority" :
               $index = $data[$ID][0]['name'];
               $color = $_SESSION["glpipriority_$index"];
               $name  = CommonITILObject::getPriorityName($index);
               return "<div class='priority_block' style='border-color: $color'>
                        <span style='background: $color'></span>&nbsp;$name
                     </div>";
         }
      }

      //// Default case

      if ($itemtype == 'PluginAssistancesTicket'
         && Session::getCurrentInterface() == 'helpdesk'
         && $orig_id == 8
         && Entity::getUsedConfig(
            'anonymize_support_agents',
            $itemtype::getById($data['id'])->getEntityId()
         )
      ) {
         // Assigned groups
         return __("Helpdesk group");
      }

      // Link with plugin tables : need to know left join structure
      if (isset($table)) {
         if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table.'.'.$field, $matches)) {
            if (count($matches) == 2) {
               $plug     = $matches[1];
               $out = Plugin::doOneHook(
                  $plug,
                  'giveItem',
                  $itemtype, $orig_id, $data, $ID
               );
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
      $unit = '';
      if (isset($so['unit'])) {
         $unit = $so['unit'];
      }

      // Preformat items
      if (isset($so["datatype"])) {
         switch ($so["datatype"]) {
            case "itemlink" :
               $linkitemtype  = PluginServicesFagoUtils::getSubItemForItemType(getItemTypeForTable($so["table"]));
               $out           = "";
               $count_display = 0;
               $separate      = self::LBBR;
               if (isset($so['splititems']) && $so['splititems']) {
                  $separate = self::LBHR;
               }

               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (isset($data[$ID][$k]['id'])) {
                     if ($count_display) {
                        $out .= $separate;
                     }
                     $count_display++;
                     $page  = $linkitemtype::getFormURLWithID($data[$ID][$k]['id']);
                     $name  = Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                     if ($_SESSION["glpiis_ids_visible"] || empty($data[$ID][$k]['name'])) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][$k]['id']);
                     }
                     $out  .= "<a id='".$linkitemtype."_".$data['id']."_".
                                 $data[$ID][$k]['id']."' href='$page'>".
                                 $name."</a>";
                  }
               }
               return $out;

            case "text" :
               $separate = self::LBBR;
               if (isset($so['splititems']) && $so['splititems']) {
                  $separate = self::LBHR;
               }

               $out           = '';
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                     if ($count_display) {
                        $out .= $separate;
                     }
                     $count_display++;
                     $text = "";
                     if (isset($so['htmltext']) && $so['htmltext']) {
                        $text = Html::clean(Toolbox::unclean_cross_side_scripting_deep(nl2br($data[$ID][$k]['name'])));
                     } else {
                        $text = nl2br($data[$ID][$k]['name']);
                     }

                     if (self::$output_type == self::HTML_OUTPUT
                           && (Toolbox::strlen($text) > $CFG_GLPI['cut'])) {
                        $rand = mt_rand();
                        $popup_params = [
                           'display'   => false
                        ];
                        if (Toolbox::strlen($text) > $CFG_GLPI['cut']) {
                           $popup_params += [
                              'awesome-class'   => 'fa-comments',
                              'autoclose'       => false,
                              'onclick'         => true
                           ];
                        } else {
                           $popup_params += [
                              'applyto'   => "text$rand",
                           ];
                        }
                        $out .= sprintf(
                           __('%1$s %2$s'),
                           "<span id='text$rand'>". Html::resume_text($text, $CFG_GLPI['cut']).'</span>',
                           Html::showToolTip(
                              '<div class="fup-popup">'.$text.'</div>', $popup_params
                              )
                        );
                     } else {
                        $out .= $text;
                     }
                  }
               }
               return $out;

            case "date" :
            case "date_delay" :
               $out   = '';
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (is_null($data[$ID][$k]['name'])
                        && isset($so['emptylabel']) && $so['emptylabel']) {
                     $out .= (empty($out)?'':self::LBBR).$so['emptylabel'];
                  } else {
                     $out .= (empty($out)?'':self::LBBR).Html::convDate($data[$ID][$k]['name']);
                  }
               }
               return $out;

            case "datetime" :
               $out   = '';
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (is_null($data[$ID][$k]['name'])
                        && isset($so['emptylabel']) && $so['emptylabel']) {
                     $out .= (empty($out)?'':self::LBBR).$so['emptylabel'];
                  } else {
                     $out .= (empty($out)?'':self::LBBR).Html::convDateTime($data[$ID][$k]['name']);
                  }
               }
               return $out;

            case "timestamp" :
               $withseconds = false;
               if (isset($so['withseconds'])) {
                  $withseconds = $so['withseconds'];
               }
               $withdays = true;
               if (isset($so['withdays'])) {
                  $withdays = $so['withdays'];
               }

               $out   = '';
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  $out .= (empty($out)?'':'<br>').Html::timestampToString($data[$ID][$k]['name'],
                                                                           $withseconds,
                                                                           $withdays);
               }
               return $out;

            case "email" :
               $out           = '';
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if ($count_display) {
                     $out .= self::LBBR;
                  }
                  $count_display++;
                  if (!empty($data[$ID][$k]['name'])) {
                     $out .= (empty($out)?'':self::LBBR);
                     $out .= "<a href='mailto:".Html::entities_deep($data[$ID][$k]['name'])."'>".$data[$ID][$k]['name'];
                     $out .= "</a>";
                  }
               }
               return (empty($out) ? "&nbsp;" : $out);

            case "weblink" :
               $orig_link = trim($data[$ID][0]['name']);
               if (!empty($orig_link) && Toolbox::isValidWebUrl($orig_link)) {
                  // strip begin of link
                  $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/', '', $orig_link);
                  $link = preg_replace('/\/$/', '', $link);
                  if (Toolbox::strlen($link)>$CFG_GLPI["url_maxlength"]) {
                     $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"])."...";
                  }
                  return "<a href=\"".Toolbox::formatOutputWebLink($orig_link)."\" target='_blank'>$link</a>";
               }
               return "&nbsp;";

            case "count" :
            case "number" :
               $out           = "";
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                     if ($count_display) {
                        $out .= self::LBBR;
                     }
                     $count_display++;
                     if (isset($so['toadd'])
                           && isset($so['toadd'][$data[$ID][$k]['name']])) {
                        $out .= $so['toadd'][$data[$ID][$k]['name']];
                     } else {
                        $number = str_replace(' ', '&nbsp;',
                                              Html::formatNumber($data[$ID][$k]['name'], false, 0));
                        $out .= Dropdown::getValueWithUnit($number, $unit);
                     }
                  }
               }
               return $out;

            case "decimal" :
               $out           = "";
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (strlen(trim($data[$ID][$k]['name'])) > 0) {

                     if ($count_display) {
                        $out .= self::LBBR;
                     }
                     $count_display++;
                     if (isset($so['toadd'])
                           && isset($so['toadd'][$data[$ID][$k]['name']])) {
                        $out .= $so['toadd'][$data[$ID][$k]['name']];
                     } else {
                        $number = str_replace(' ', '&nbsp;',
                                              Html::formatNumber($data[$ID][$k]['name']));
                        $out   .= Dropdown::getValueWithUnit($number, $unit);
                     }
                  }
               }
               return $out;

            case "bool" :
               $out           = "";
               $count_display = 0;
               for ($k=0; $k<$data[$ID]['count']; $k++) {
                  if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                     if ($count_display) {
                        $out .= self::LBBR;
                     }
                     $count_display++;
                     $out .= Dropdown::getValueWithUnit(Dropdown::getYesNo($data[$ID][$k]['name']),
                                                        $unit);
                  }
               }
               return $out;

            case "itemtypename":
               if ($obj = getItemForItemtype($data[$ID][0]['name'])) {
                  return $obj->getTypeName();
               }
               return "";

            case "language":
               if (isset($CFG_GLPI['languages'][$data[$ID][0]['name']])) {
                  return $CFG_GLPI['languages'][$data[$ID][0]['name']][0];
               }
               return __('Default value');
            case 'progressbar':
               if (!isset($progressbar_data)) {
                  $bar_color = 'green';
                  $progressbar_data = [
                     'percent'      => $data[$ID][0]['name'],
                     'percent_text' => $data[$ID][0]['name'],
                     'color'        => $bar_color,
                     'text'         => ''
                  ];
               }

               $out = "{$progressbar_data['text']}<div class='center' style='background-color: #ffffff; width: 100%;
                        border: 1px solid #9BA563; position: relative;' >";
               $out .= "<div style='position:absolute;'>&nbsp;{$progressbar_data['percent_text']}%</div>";
               $out .= "<div class='center' style='background-color: {$progressbar_data['color']};
                        width: {$progressbar_data['percent']}%; height: 12px' ></div>";
               $out .= "</div>";

               return $out;
               break;
         }
      }
      // Manage items with need group by / group_concat
      $out           = "";
      $count_display = 0;
      $separate      = self::LBBR;
      if (isset($so['splititems']) && $so['splititems']) {
         $separate = self::LBHR;
      }
      for ($k=0; $k<$data[$ID]['count']; $k++) {
         if (strlen(trim($data[$ID][$k]['name'])) > 0) {
            if ($count_display) {
               $out .= $separate;
            }
            $count_display++;
            // Get specific display if available
            if (isset($table)) {
               $itemtype = PluginServicesFagoUtils::getSubItemForItemType(getItemTypeForTable($table));
               if ($item = getItemForItemtype($itemtype)) {
                  $tmpdata  = $data[$ID][$k];
                  // Copy name to real field
                  $tmpdata[$field] = $data[$ID][$k]['name'];

                  $specific = $item->getSpecificValueToDisplay(
                     $field,
                     $tmpdata, [
                        'html'      => true,
                        'searchopt' => $so,
                        'raw_data'  => $data
                     ]
                  );
               }
            }
            if (!empty($specific)) {
               $out .= $specific;
            } else {
               if (isset($so['toadd'])
                     && isset($so['toadd'][$data[$ID][$k]['name']])) {
                  $out .= $so['toadd'][$data[$ID][$k]['name']];
               } else {
                  // Empty is 0 or empty
                  if (empty($split[0])&& isset($so['emptylabel'])) {
                     $out .= $so['emptylabel'];
                  } else {
                     // Trans field exists
                     if (isset($data[$ID][$k]['trans']) && !empty($data[$ID][$k]['trans'])) {
                        $out .=  Dropdown::getValueWithUnit($data[$ID][$k]['trans'], $unit);
                     } else {
                        $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                     }
                  }
               }
            }
         }
      }
      return $out;
   }

   static function giveItem($itemtype, $ID, array $data, $meta = 0,
                              array $addobjectparams = [], $orig_itemtype = null) {
         global $CFG_GLPI;

         $searchopt = &self::getOptions($itemtype);
         if ($itemtype == 'AllAssets' || isset($CFG_GLPI["union_search_type"][$itemtype])
            && ($CFG_GLPI["union_search_type"][$itemtype] == $searchopt[$ID]["table"])) {

            $oparams = [];
            if (isset($searchopt[$ID]['addobjectparams'])
               && $searchopt[$ID]['addobjectparams']) {
               $oparams = $searchopt[$ID]['addobjectparams'];
            }

            // Search option may not exists in subtype
            // This is the case for "Inventory number" for a Software listed from ReservationItem search
            $subtype_so = &self::getOptions($data["TYPE"]);
            if (!array_key_exists($ID, $subtype_so)) {
               return '';
            }

            return self::giveItem($data["TYPE"], $ID, $data, $meta, $oparams, $itemtype);
         }
         $so = $searchopt[$ID];
         $orig_id = $ID;
         $ID = ($orig_itemtype !== null ? $orig_itemtype : $itemtype) . '_' . $ID;

         if (count($addobjectparams)) {
            $so = array_merge($so, $addobjectparams);
         }
         // Plugin can override core definition for its type
         if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
               $plug['plugin'],
               'giveItem',
               $itemtype, $orig_id, $data, $ID
            );
            if (!empty($out)) {
               return $out;
            }
         }

         if (isset($so["table"])) {
            $table     = $so["table"];
            $field     = $so["field"];
            $linkfield = $so["linkfield"];

            /// TODO try to clean all specific cases using SpecificToDisplay

            switch ($table.'.'.$field) {
               case "glpi_users.name" :
                  if ($itemtype == 'PluginAssistancesTicket'
                     && Session::getCurrentInterface() == 'helpdesk'
                     && $orig_id == 5
                     && Entity::getUsedConfig(
                        'anonymize_support_agents',
                        $itemtype::getById($data['id'])->getEntityId()
                     )
                  ) {
                     return __("Helpdesk");
                  }

                  // USER search case
                  if (($itemtype != 'User')
                        && isset($so["forcegroupby"]) && $so["forcegroupby"]) {
                     $out           = "";
                     $count_display = 0;
                     $added         = [];

                     $showuserlink = 0;
                     if (Session::haveRight('user', READ)) {
                        $showuserlink = 1;
                     }

                     for ($k=0; $k<$data[$ID]['count']; $k++) {

                        if ((isset($data[$ID][$k]['name']) && ($data[$ID][$k]['name'] > 0))
                              || (isset($data[$ID][$k][2]) && ($data[$ID][$k][2] != ''))) {
                           if ($count_display) {
                              $out .= self::LBBR;
                           }

                           if ($itemtype == 'PluginAssistancesTicket') {
                              if (isset($data[$ID][$k]['name'])
                                    && $data[$ID][$k]['name'] > 0) {
                                 $userdata = getUserName($data[$ID][$k]['name'], 2);
                                 $out .= $userdata['name'];
                                 if (Session::haveRight('user', READ)) {
                                    $link = PluginServicesUser::getFormURLWithID($data[$ID][$k]['name']);
                                    $out  .= "&nbsp;<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'><span class='fas fa-info pointer'></span><a/>";
                                 }
                                 $count_display++;
                              }
                           } else {
                              // $out .= getUserName($data[$ID][$k]['name']);
                              $userdata = getUserName($data[$ID][$k]['name'], 2);
                              // $out .= getUserName($data[$ID][$k]['name'], $showuserlink);
                              $out .= "<a  onclick='loadPage(\"".PluginServicesToolbox::getFormURLWithID($data[$ID][$k]['name'],true, 'User')."\")' href='javascript:void(0);'>".$userdata['name']."</a>";
                              $count_display++;
                           }

                           // Manage alternative_email for tickets_users
                           if (($itemtype == 'PluginAssistancesTicket')
                              && isset($data[$ID][$k][2])) {

                              $split = explode(self::LONGSEP, $data[$ID][$k][2]);
                              for ($l=0; $l<count($split); $l++) {
                                 $split2 = explode(" ", $split[$l]);
                                 if ((count($split2) == 2) && ($split2[0] == 0) && !empty($split2[1])) {
                                    if ($count_display) {
                                       $out .= self::LBBR;
                                    }
                                    $count_display++;
                                    $out .= "<a href='mailto:".$split2[1]."'>".$split2[1]."</a>";
                                 }
                              }
                           }
                        }
                     }
                     return $out;
                  }
                  if ($itemtype != 'User') {
                     $toadd = '';
                     if (($itemtype == 'User')
                           && ($data[$ID][0]['id'] > 0)) {
                        $out  = getUserName($data[$ID][0]['id']);
                        $link = PluginServicesUser::getFormURLWithID($data[$ID][0]['id']);
                        $out  .= "&nbsp;<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'><span class='fas fa-info pointer'></span><a/>";                                      
                     }
                     return $out ;
                  }
                  break;

               case "glpi_profiles.name" :
                  if (($itemtype == 'User')
                        && ($orig_id == 20)) {
                     $out           = "";

                     $count_display = 0;
                     $added         = [];
                     for ($k=0; $k<$data[$ID]['count']; $k++) {
                        if (strlen(trim($data[$ID][$k]['name'])) > 0
                           && !in_array($data[$ID][$k]['name']."-".$data[$ID][$k]['entities_id'],
                                       $added)) {
                           $text = sprintf(__('%1$s - %2$s'), $data[$ID][$k]['name'],
                                          Dropdown::getDropdownName('glpi_entities',
                                                                     $data[$ID][$k]['entities_id']));
                           $comp = '';
                           if ($data[$ID][$k]['is_recursive']) {
                              $comp = __('R');
                              if ($data[$ID][$k]['is_dynamic']) {
                                 $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                              }
                           }
                           if ($data[$ID][$k]['is_dynamic']) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                           }
                           if (!empty($comp)) {
                              $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                           }
                           if ($count_display) {
                              $out .= self::LBBR;
                           }
                           $count_display++;
                           $out     .= $text;
                           $added[]  = $data[$ID][$k]['name']."-".$data[$ID][$k]['entities_id'];
                        }
                     }
                     return $out;
                  }
                  break;

               case "glpi_entities.completename" :
                  if ($itemtype == 'PluginServicesUser') {

                     $out           = "";
                     $added         = [];
                     $count_display = 0;
                     for ($k=0; $k<$data[$ID]['count']; $k++) {
                        if (isset($data[$ID][$k]['name'])
                              && (strlen(trim($data[$ID][$k]['name'])) > 0)
                              && !in_array($data[$ID][$k]['name']."-".$data[$ID][$k]['profiles_id'],
                                          $added)) {
                           $text = sprintf(__('%1$s - %2$s'), $data[$ID][$k]['name'],
                                             Dropdown::getDropdownName('glpi_profiles',
                                                                     $data[$ID][$k]['profiles_id']));
                           $comp = '';
                           if ($data[$ID][$k]['is_recursive']) {
                              $comp = __('R');
                              if ($data[$ID][$k]['is_dynamic']) {
                                 $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                              }
                           }
                           if ($data[$ID][$k]['is_dynamic']) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                           }
                           if (!empty($comp)) {
                              $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                           }
                           if ($count_display) {
                              $out .= self::LBBR;
                           }
                           $count_display++;
                           $out    .= $text;
                           $added[] = $data[$ID][$k]['name']."-".$data[$ID][$k]['profiles_id'];
                        }
                     }
                     return $out;
                  }
                  break;

               case "glpi_documenttypes.icon" :
                  if (!empty($data[$ID][0]['name'])) {
                     return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".
                              $data[$ID][0]['name']."'>";
                  }
                  return "&nbsp;";

               case "glpi_documents.filename" :
                  $doc = new Document();
                  if ($doc->getFromDB($data['id'])) {
                     return $doc->getDownloadLink();
                  }
                  return NOT_AVAILABLE;

               case "glpi_tickets_tickets.tickets_id_1" :
                  $out        = "";
                  $displayed  = [];
                  for ($k=0; $k<$data[$ID]['count']; $k++) {

                     $linkid = ($data[$ID][$k]['tickets_id_2'] == $data['id'])
                                    ? $data[$ID][$k]['name']
                                    : $data[$ID][$k]['tickets_id_2'];
                     if (($linkid > 0) && !isset($displayed[$linkid])) {
                        $link = PluginAssistancesTicket::getFormURLWithID($linkid);
                        $text = "<a onclick='loadPage(\"$link\")'  href='javascript:void(0);'>";
                        $text .= Dropdown::getDropdownName('glpi_tickets', $linkid)."</a>";
                        if (count($displayed)) {
                           $out .= self::LBBR;
                        }
                        $displayed[$linkid] = $linkid;
                        $out               .= $text;
                     }
                  }
                  return $out;

               case "glpi_problems.id" :
                  if ($so["datatype"] == 'count') {
                     if (($data[$ID][0]['name'] > 0)
                        && Session::haveRight("problem", Problem::READALL)) {
                        if ($itemtype == 'ITILCategory') {
                           $options['criteria'][0]['field']      = 7;
                           $options['criteria'][0]['searchtype'] = 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';
                        } else {
                           $options['criteria'][0]['field']       = 12;
                           $options['criteria'][0]['searchtype']  = 'equals';
                           $options['criteria'][0]['value']       = 'all';
                           $options['criteria'][0]['link']        = 'AND';

                           $options['metacriteria'][0]['itemtype']   = $itemtype;
                           $options['metacriteria'][0]['field']      = self::getOptionNumber($itemtype,
                                 'name');
                           $options['metacriteria'][0]['searchtype'] = 'equals';
                           $options['metacriteria'][0]['value']      = $data['id'];
                           $options['metacriteria'][0]['link']       = 'AND';
                        }

                        $options['reset'] = 'reset';

                        $out  = "<a id='problem$itemtype".$data['id']."' ";
                        $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                                 Toolbox::append_params($options, '&amp;')."\">";
                        $out .= $data[$ID][0]['name']."</a>";
                        return $out;
                     }
                  }
                  break;

               case "glpi_tickets.id" :
                  if ($so["datatype"] == 'count') {
                     if (($data[$ID][0]['name'] > 0)
                        && Session::haveRight("ticket", PluginAssistancesTicket::READALL)) {

                        if ($itemtype == 'PluginServicesUser') {
                           // Requester
                           if ($ID == 'User_60') {
                              $options['criteria'][0]['field']      = 4;
                              $options['criteria'][0]['searchtype']= 'equals';
                              $options['criteria'][0]['value']      = $data['id'];
                              $options['criteria'][0]['link']       = 'AND';
                           }

                           // Writer
                           if ($ID == 'User_61') {
                              $options['criteria'][0]['field']      = 22;
                              $options['criteria'][0]['searchtype']= 'equals';
                              $options['criteria'][0]['value']      = $data['id'];
                              $options['criteria'][0]['link']       = 'AND';
                           }
                           // Assign
                           if ($ID == 'User_64') {
                              $options['criteria'][0]['field']      = 5;
                              $options['criteria'][0]['searchtype']= 'equals';
                              $options['criteria'][0]['value']      = $data['id'];
                              $options['criteria'][0]['link']       = 'AND';
                           }
                        } else if ($itemtype == 'ITILCategory') {
                           $options['criteria'][0]['field']      = 7;
                           $options['criteria'][0]['searchtype'] = 'equals';
                           $options['criteria'][0]['value']      = $data['id'];
                           $options['criteria'][0]['link']       = 'AND';

                        } else {
                           $options['criteria'][0]['field']       = 12;
                           $options['criteria'][0]['searchtype']  = 'equals';
                           $options['criteria'][0]['value']       = 'all';
                           $options['criteria'][0]['link']        = 'AND';

                           $options['metacriteria'][0]['itemtype']   = $itemtype;
                           $options['metacriteria'][0]['field']      = self::getOptionNumber($itemtype,
                                                                                             'name');
                           $options['metacriteria'][0]['searchtype'] = 'equals';
                           $options['metacriteria'][0]['value']      = $data['id'];
                           $options['metacriteria'][0]['link']       = 'AND';
                        }

                        $options['reset'] = 'reset';

                        $out  = "<a id='ticket$itemtype".$data['id']."' ";
                        $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                                 Toolbox::append_params($options, '&amp;')."\">";
                        $out .= $data[$ID][0]['name']."</a>";
                        return $out;
                     }
                  }
                  break;

               case "glpi_tickets.time_to_resolve" :
               case "glpi_problems.time_to_resolve" :
               case "glpi_changes.time_to_resolve" :
               case "glpi_tickets.time_to_own" :
               case "glpi_tickets.internal_time_to_own" :
               case "glpi_tickets.internal_time_to_resolve" :
                  // Due date + progress
                  if (in_array($orig_id, [151, 158, 181, 186])) {
                     $out = Html::convDateTime($data[$ID][0]['name']);

                     // No due date in waiting status
                     if ($data[$ID][0]['status'] == CommonITILObject::WAITING) {
                        return '';
                     }
                     if (empty($data[$ID][0]['name'])) {
                        return '';
                     }
                     if (($data[$ID][0]['status'] == PluginAssistancesTicket::SOLVED)
                        || ($data[$ID][0]['status'] == PluginAssistancesTicket::CLOSED)) {
                        return $out;
                     }

                     $itemtype = getItemTypeForTable($table);
                     $item = new $itemtype();
                     $item->getFromDB($data['id']);
                     $percentage  = 0;
                     $totaltime   = 0;
                     $currenttime = 0;
                     $slaField    = 'slas_id';

                     // define correct sla field
                     switch ($table.'.'.$field) {
                        case "glpi_tickets.time_to_resolve" :
                           $slaField = 'slas_id_ttr';
                           break;
                        case "glpi_tickets.time_to_own" :
                           $slaField = 'slas_id_tto';
                           break;
                        case "glpi_tickets.internal_time_to_own" :
                           $slaField = 'olas_id_tto';
                           break;
                        case "glpi_tickets.internal_time_to_resolve" :
                           $slaField = 'olas_id_ttr';
                           break;
                     }

                     switch ($table.'.'.$field) {
                        // If ticket has been taken into account : no progression display
                        case "glpi_tickets.time_to_own" :
                        case "glpi_tickets.internal_time_to_own" :
                           if (($item->fields['takeintoaccount_delay_stat'] > 0)) {
                              return $out;
                           }
                           break;
                     }

                     if ($item->isField($slaField) && $item->fields[$slaField] != 0) { // Have SLA
                        $sla = new SLA();
                        $sla->getFromDB($item->fields[$slaField]);
                        $currenttime = $sla->getActiveTimeBetween($item->fields['date'],
                                                                  date('Y-m-d H:i:s'));
                        $totaltime   = $sla->getActiveTimeBetween($item->fields['date'],
                                                                  $data[$ID][0]['name']);
                     } else {
                        $calendars_id = Entity::getUsedConfig('calendars_id',
                                                            $item->fields['entities_id']);
                        if ($calendars_id != 0) { // Ticket entity have calendar
                           $calendar = new Calendar();
                           $calendar->getFromDB($calendars_id);
                           $currenttime = $calendar->getActiveTimeBetween($item->fields['date'],
                                                                        date('Y-m-d H:i:s'));
                           $totaltime   = $calendar->getActiveTimeBetween($item->fields['date'],
                                                                        $data[$ID][0]['name']);
                        } else { // No calendar
                           $currenttime = strtotime(date('Y-m-d H:i:s'))
                                                   - strtotime($item->fields['date']);
                           $totaltime   = strtotime($data[$ID][0]['name'])
                                                   - strtotime($item->fields['date']);
                        }
                     }
                     if ($totaltime != 0) {
                        $percentage  = round((100 * $currenttime) / $totaltime);
                     } else {
                        // Total time is null : no active time
                        $percentage = 100;
                     }
                     if ($percentage > 100) {
                        $percentage = 100;
                     }
                     $percentage_text = $percentage;

                     if ($_SESSION['glpiduedatewarning_unit'] == '%') {
                        $less_warn_limit = $_SESSION['glpiduedatewarning_less'];
                        $less_warn       = (100 - $percentage);
                     } else if ($_SESSION['glpiduedatewarning_unit'] == 'hour') {
                        $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * HOUR_TIMESTAMP;
                        $less_warn       = ($totaltime - $currenttime);
                     } else if ($_SESSION['glpiduedatewarning_unit'] == 'day') {
                        $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * DAY_TIMESTAMP;
                        $less_warn       = ($totaltime - $currenttime);
                     }

                     if ($_SESSION['glpiduedatecritical_unit'] == '%') {
                        $less_crit_limit = $_SESSION['glpiduedatecritical_less'];
                        $less_crit       = (100 - $percentage);
                     } else if ($_SESSION['glpiduedatecritical_unit'] == 'hour') {
                        $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * HOUR_TIMESTAMP;
                        $less_crit       = ($totaltime - $currenttime);
                     } else if ($_SESSION['glpiduedatecritical_unit'] == 'day') {
                        $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * DAY_TIMESTAMP;
                        $less_crit       = ($totaltime - $currenttime);
                     }

                     $color = $_SESSION['glpiduedateok_color'];
                     if ($less_crit < $less_crit_limit) {
                        $color = $_SESSION['glpiduedatecritical_color'];
                     } else if ($less_warn < $less_warn_limit) {
                        $color = $_SESSION['glpiduedatewarning_color'];
                     }

                     if (!isset($so['datatype'])) {
                        $so['datatype'] = 'progressbar';
                     }

                     $progressbar_data = [
                        'text'         => Html::convDateTime($data[$ID][0]['name']),
                        'percent'      => $percentage,
                        'percent_text' => $percentage_text,
                        'color'        => $color
                     ];
                  }
                  break;

               case "glpi_softwarelicenses.number" :
                  if ($data[$ID][0]['min'] == -1) {
                     return __('Unlimited');
                  }
                  if (empty($data[$ID][0]['name'])) {
                     return 0;
                  }
                  return $data[$ID][0]['name'];

               case "glpi_auth_tables.name" :
                  return Auth::getMethodName($data[$ID][0]['name'], $data[$ID][0]['auths_id'], 1,
                                             $data[$ID][0]['ldapname'].$data[$ID][0]['mailname']);

               case "glpi_reservationitems.comment" :
                  if (empty($data[$ID][0]['name'])) {
                     $text = __('None');
                  } else {
                     $text = Html::resume_text($data[$ID][0]['name']);
                  }
                  if (Session::haveRight('reservation', UPDATE)) {
                     // return "<a title=\"".__s('Modify the comment')."\"
                     //          href='".ReservationItem::getFormURLWithID($data['refID'])."'  >".$text."</a>";
                              $link = ReservationItem::getFormURLWithID($data['refID']);
                              return "<a title=\"".__s('Modify the comment')."\"
                              href='javascript:void(0);' onclick='loadPage(\"$link\")' >".$text."</a>";
                  }
                  return $text;

               case 'glpi_crontasks.description' :
                  $tmp = new CronTask();
                  return $tmp->getDescription($data[$ID][0]['name']);

               case 'glpi_changes.status':
                  $status = Change::getStatus($data[$ID][0]['name']);
                  return "<span class='no-wrap'>".
                        Change::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                        "</span>";

               case 'glpi_problems.status':
                  $status = Problem::getStatus($data[$ID][0]['name']);
                  return "<span class='no-wrap'>".
                        Problem::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                        "</span>";

               case 'glpi_tickets.status':
                  $status = PluginAssistancesTicket::getStatus($data[$ID][0]['name']);
                  return "<span class='no-wrap'>".
                        PluginAssistancesTicket::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status".
                        "</span>";

               case 'glpi_projectstates.name':
                  $out = '';
                  $name = $data[$ID][0]['name'];
                  if (isset($data[$ID][0]['trans'])) {
                     $name = $data[$ID][0]['trans'];
                  }
                  if ($itemtype == 'ProjectState') {
                     $link = ProjectState::getFormURLWithID($data[$ID][0]["id"]);
                     $out = "<a  onclick='loadPage(\"$link\")' href='javascript:void(0);'>". $name."</a></div>";
                  } else {
                     $out = $name;
                  }
                  return $out;

               case 'glpi_items_tickets.items_id' :
               case 'glpi_items_problems.items_id' :
               case 'glpi_changes_items.items_id' :
               case 'glpi_certificates_items.items_id' :
               case 'glpi_appliances_items.items_id' :
                  if (!empty($data[$ID])) {
                     $items = [];
                     foreach ($data[$ID] as $key => $val) {
                        if (is_numeric($key)) {
                           if (!empty($val['itemtype'])
                                 && ($item = getItemForItemtype($val['itemtype']))) {
                              if ($item->getFromDB($val['name'])) {
                                 $items[] = $item->getLink(['comments' => true]);
                              }
                           }
                        }
                     }
                     if (!empty($items)) {
                        return implode("<br>", $items);
                     }
                  }
                  return '&nbsp;';

               case 'glpi_items_tickets.itemtype' :
               case 'glpi_items_problems.itemtype' :
                  if (!empty($data[$ID])) {
                     $itemtypes = [];
                     foreach ($data[$ID] as $key => $val) {
                        if (is_numeric($key)) {
                           if (!empty($val['name'])
                                 && ($item = getItemForItemtype($val['name']))) {
                              $item = new $val['name']();
                              $name = $item->getTypeName();
                              $itemtypes[] = __($name);
                           }
                        }
                     }
                     if (!empty($itemtypes)) {
                        return implode("<br>", $itemtypes);
                     }
                  }

                  return '&nbsp;';

               case 'glpi_tickets.name' :
               case 'glpi_problems.name' :
               case 'glpi_changes.name' :

                  if (isset($data[$ID][0]['content'])
                        && isset($data[$ID][0]['id'])
                        && isset($data[$ID][0]['status'])) {
                     $link = $itemtype::getFormURLWithID($data[$ID][0]['id']);
                     // Force solution tab if solved
                     if ($item = getItemForItemtype($itemtype)) {
                        if (in_array($data[$ID][0]['status'], $item->getSolvedStatusArray())) {
                           // $out .= "&amp;forcetab=$itemtype$2";
                           $link = $link."&amp;forcetab=$itemtype$2";
                        }
                     }
                     $hdecode = Html::entity_decode_deep($data[$ID][0]['content']);
                     $content = Toolbox::unclean_cross_side_scripting_deep($hdecode);
                     $out = "<a data-toggle='tooltip' data-placement='top' title='".$content."' onclick='loadPage(\"$link\")' id='$itemtype".$data[$ID][0]['id']."' href='javascript:void(0);'>";
                        $name = $data[$ID][0]['name'];
                        if ($_SESSION["glpiis_ids_visible"]
                           || empty($data[$ID][0]['name'])) {
                           $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][0]['id']);
                        }
                     $out    .= $name."</a>";
                     // $out     = sprintf(__('%1$s %2$s'), $out,
                     //                    Html::showToolTip(nl2br(Html::Clean($content)),
                     //                                            ['applyto' => $itemtype.
                     //                                                               $data[$ID][0]['id'],
                     //                                                  'display' => false]));
                     return $out;
                  }

               case 'glpi_ticketvalidations.status' :
                  $out   = '';
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if ($data[$ID][$k]['name']) {
                        $status  = TicketValidation::getStatus($data[$ID][$k]['name']);
                        $bgcolor = TicketValidation::getStatusColor($data[$ID][$k]['name']);
                        $out    .= (empty($out)?'':self::LBBR).
                                    "<div style=\"background-color:".$bgcolor.";\">".$status.'</div>';
                     }
                  }
                  return $out;

               case 'glpi_ticketsatisfactions.satisfaction' :
                  if (self::$output_type == self::HTML_OUTPUT) {
                     return TicketSatisfaction::displaySatisfaction($data[$ID][0]['name']);
                  }
                  break;

               case 'glpi_projects._virtual_planned_duration' :
                  return Html::timestampToString(ProjectTask::getTotalPlannedDurationForProject($data["id"]),
                                                false);

               case 'glpi_projects._virtual_effective_duration' :
                  return Html::timestampToString(ProjectTask::getTotalEffectiveDurationForProject($data["id"]),
                                                false);

               case 'glpi_cartridgeitems._virtual' :
                  return Cartridge::getCount($data["id"], $data[$ID][0]['alarm_threshold'],
                                             self::$output_type != self::HTML_OUTPUT);

               case 'glpi_printers._virtual' :
                  return Cartridge::getCountForPrinter($data["id"],
                                                      self::$output_type != self::HTML_OUTPUT);

               case 'glpi_consumableitems._virtual' :
                  return Consumable::getCount($data["id"], $data[$ID][0]['alarm_threshold'],
                                             self::$output_type != self::HTML_OUTPUT);

               case 'glpi_links._virtual' :
                  $out = '';
                  $link = new Link();
                  if (($item = getItemForItemtype($itemtype))
                     && $item->getFromDB($data['id'])
                  ) {
                     $data = Link::getLinksDataForItem($item);
                     $count_display = 0;
                     foreach ($data as $val) {
                        $links = Link::getAllLinksFor($item, $val);
                        foreach ($links as $link) {
                           if ($count_display) {
                              $out .=  self::LBBR;
                           }
                           $out .= $link;
                           $count_display++;
                        }
                     }
                  }
                  return $out;

               case 'glpi_reservationitems._virtual' :
                  if ($data[$ID][0]['is_active']) {
                     return "<a href='reservation.php?reservationitems_id=".
                                             $data["refID"]."' title=\"".__s('See planning')."\">".
                                             "<i class='far fa-calendar-alt'></i><span class='sr-only'>".__('See planning')."</span></a>";
                  } else {
                     return "&nbsp;";
                  }

               case "glpi_tickets.priority" :
               case "glpi_problems.priority" :
               case "glpi_changes.priority" :
               case "glpi_projects.priority" :
                  $index = $data[$ID][0]['name'];
                  $color = $_SESSION["glpipriority_$index"];
                  $name  = CommonITILObject::getPriorityName($index);
                  return "<div class='priority_block' style='border-color: $color'>
                           <span style='background: $color'></span>&nbsp;$name
                        </div>";
            }
         }

         //// Default case

         if ($itemtype == 'PluginAssistancesTicket'
            && Session::getCurrentInterface() == 'helpdesk'
            && $orig_id == 8
            && Entity::getUsedConfig(
               'anonymize_support_agents',
               $itemtype::getById($data['id'])->getEntityId()
            )
         ) {
            // Assigned groups
            return __("Helpdesk group");
         }

         // Link with plugin tables : need to know left join structure
         if (isset($table)) {
            if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table.'.'.$field, $matches)) {
               if (count($matches) == 2) {
                  $plug     = $matches[1];
                  $out = Plugin::doOneHook(
                     $plug,
                     'giveItem',
                     $itemtype, $orig_id, $data, $ID
                  );
                  if (!empty($out)) {
                     return $out;
                  }
               }
            }
         }
         $unit = '';
         if (isset($so['unit'])) {
            $unit = $so['unit'];
         }

         // Preformat items
         if (isset($so["datatype"])) {
            switch ($so["datatype"]) {
               case "itemlink" :
                  $linkitemtype  = getItemTypeForTable($so["table"]);
                  $pluginlinkitemtype = 'PluginServices'.$linkitemtype;
                  // verifions s'il exist une class custom
                  $linkitemtype = (class_exists($pluginlinkitemtype)) ? $pluginlinkitemtype :  $linkitemtype;

                  $out           = "";
                  $count_display = 0;
                  $separate      = self::LBBR;
                  if (isset($so['splititems']) && $so['splititems']) {
                     $separate = self::LBHR;
                  }

                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (isset($data[$ID][$k]['id'])) {
                        if ($count_display) {
                           $out .= $separate;
                        }
                        $count_display++;
                        $page  = $linkitemtype::getFormURLWithID($data[$ID][$k]['id']);
                        $name  = Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                        if ($_SESSION["glpiis_ids_visible"] || empty($data[$ID][$k]['name'])) {
                           $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][$k]['id']);
                        }
                        // $out  .= "<a id='".$linkitemtype."_".$data['id']."_".
                        //            $data[$ID][$k]['id']."' href='$page'>".
                        //           $name."</a>";

                        $out  .= "<a id='".$linkitemtype."_".$data['id']."_".
                           $data[$ID][$k]['id']."' onclick='loadPage(\"$page\")' href='javascript:void(0);'>".
                        $name."</a>";
                     }
                  }
                  return $out;

               case "text" :
                  $separate = self::LBBR;
                  if (isset($so['splititems']) && $so['splititems']) {
                     $separate = self::LBHR;
                  }

                  $out           = '';
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                        if ($count_display) {
                           $out .= $separate;
                        }
                        $count_display++;
                        $text = "";
                        if (isset($so['htmltext']) && $so['htmltext']) {
                           $text = Html::clean(Toolbox::unclean_cross_side_scripting_deep(nl2br($data[$ID][$k]['name'])));
                        } else {
                           $text = nl2br($data[$ID][$k]['name']);
                        }

                        if (self::$output_type == self::HTML_OUTPUT
                           && (Toolbox::strlen($text) > $CFG_GLPI['cut'])) {
                           $rand = mt_rand();
                           $popup_params = [
                              'display'   => false
                           ];
                           if (Toolbox::strlen($text) > $CFG_GLPI['cut']) {
                              $popup_params += [
                                 'awesome-class'   => 'fa-comments',
                                 'autoclose'       => false,
                                 'onclick'         => true
                              ];
                           } else {
                              $popup_params += [
                                 'applyto'   => "text$rand",
                              ];
                           }
                           $out .= sprintf(
                              __('%1$s %2$s'),
                              "<span id='text$rand'>". Html::resume_text($text, $CFG_GLPI['cut']).'</span>',
                              ''
                           );
                        } else {
                           $out .= $text;
                        }
                     }
                  }
                  return $out;

               case "date" :
               case "date_delay" :
                  $out   = '';
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (is_null($data[$ID][$k]['name'])
                        && isset($so['emptylabel']) && $so['emptylabel']) {
                        $out .= (empty($out)?'':self::LBBR).$so['emptylabel'];
                     } else {
                        $out .= (empty($out)?'':self::LBBR).Html::convDate($data[$ID][$k]['name']);
                     }
                  }
                  return $out;

               case "datetime" :
                  $out   = '';
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (is_null($data[$ID][$k]['name'])
                        && isset($so['emptylabel']) && $so['emptylabel']) {
                        $out .= (empty($out)?'':self::LBBR).$so['emptylabel'];
                     } else {
                        $out .= (empty($out)?'':self::LBBR).Html::convDateTime($data[$ID][$k]['name']);
                     }
                  }
                  return $out;

               case "timestamp" :
                  $withseconds = false;
                  if (isset($so['withseconds'])) {
                     $withseconds = $so['withseconds'];
                  }
                  $withdays = true;
                  if (isset($so['withdays'])) {
                     $withdays = $so['withdays'];
                  }

                  $out   = '';
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     $out .= (empty($out)?'':'<br>').Html::timestampToString($data[$ID][$k]['name'],
                                                                              $withseconds,
                                                                              $withdays);
                  }
                  return $out;

               case "email" :
                  $out           = '';
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if ($count_display) {
                        $out .= self::LBBR;
                     }
                     $count_display++;
                     if (!empty($data[$ID][$k]['name'])) {
                        $out .= (empty($out)?'':self::LBBR);
                        $out .= "<a href='mailto:".Html::entities_deep($data[$ID][$k]['name'])."'>".$data[$ID][$k]['name'];
                        $out .= "</a>";
                     }
                  }
                  return (empty($out) ? "&nbsp;" : $out);

               case "weblink" :
                  $orig_link = trim($data[$ID][0]['name']);
                  if (!empty($orig_link) && Toolbox::isValidWebUrl($orig_link)) {
                     // strip begin of link
                     $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/', '', $orig_link);
                     $link = preg_replace('/\/$/', '', $link);
                     if (Toolbox::strlen($link)>$CFG_GLPI["url_maxlength"]) {
                        $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"])."...";
                     }
                     return "<a href=\"".Toolbox::formatOutputWebLink($orig_link)."\" target='_blank'>$link</a>";
                  }
                  return "&nbsp;";

               case "count" :
               case "number" :
                  $out           = "";
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        if (isset($so['toadd'])
                              && isset($so['toadd'][$data[$ID][$k]['name']])) {
                           $out .= $so['toadd'][$data[$ID][$k]['name']];
                        } else {
                           $number = str_replace(' ', '&nbsp;',
                                                Html::formatNumber($data[$ID][$k]['name'], false, 0));
                           $out .= Dropdown::getValueWithUnit($number, $unit);
                        }
                     }
                  }
                  return $out;

               case "decimal" :
                  $out           = "";
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0) {

                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        if (isset($so['toadd'])
                              && isset($so['toadd'][$data[$ID][$k]['name']])) {
                           $out .= $so['toadd'][$data[$ID][$k]['name']];
                        } else {
                           $number = str_replace(' ', '&nbsp;',
                                                Html::formatNumber($data[$ID][$k]['name']));
                           $out   .= Dropdown::getValueWithUnit($number, $unit);
                        }
                     }
                  }
                  return $out;

               case "bool" :
                  $out           = "";
                  $count_display = 0;
                  for ($k=0; $k<$data[$ID]['count']; $k++) {
                     if (strlen(trim($data[$ID][$k]['name'])) > 0) {
                        if ($count_display) {
                           $out .= self::LBBR;
                        }
                        $count_display++;
                        $out .= Dropdown::getValueWithUnit(Dropdown::getYesNo($data[$ID][$k]['name']),
                                                         $unit);
                     }
                  }
                  return $out;

               case "itemtypename":
                  if ($obj = getItemForItemtype($data[$ID][0]['name'])) {
                     return $obj->getTypeName();
                  }
                  return "";

               case "language":
                  if (isset($CFG_GLPI['languages'][$data[$ID][0]['name']])) {
                     return $CFG_GLPI['languages'][$data[$ID][0]['name']][0];
                  }
                  return __('Default value');
               case 'progressbar':
                  if (!isset($progressbar_data)) {
                     $bar_color = 'green';
                     $progressbar_data = [
                        'percent'      => $data[$ID][0]['name'],
                        'percent_text' => $data[$ID][0]['name'],
                        'color'        => $bar_color,
                        'text'         => ''
                     ];
                  }

                  $out = "{$progressbar_data['text']}<div class='center' style='background-color: #ffffff; width: 100%;
                           border: 1px solid #9BA563; position: relative;' >";
                  $out .= "<div style='position:absolute;'>&nbsp;{$progressbar_data['percent_text']}%</div>";
                  $out .= "<div class='center' style='background-color: {$progressbar_data['color']};
                           width: {$progressbar_data['percent']}%; height: 12px' ></div>";
                  $out .= "</div>";

                  return $out;
                  break;
            }
         }
         // Manage items with need group by / group_concat
         $out           = "";
         $count_display = 0;
         $separate      = self::LBBR;
         if (isset($so['splititems']) && $so['splititems']) {
            $separate = self::LBHR;
         }
         for ($k=0; $k<$data[$ID]['count']; $k++) {
            if (strlen(trim($data[$ID][$k]['name'])) > 0) {
               if ($count_display) {
                  $out .= $separate;
               }
               $count_display++;
               // Get specific display if available
               if (isset($table)) {
                  $itemtype = getItemTypeForTable($table);
                  if ($item = getItemForItemtype($itemtype)) {
                     $tmpdata  = $data[$ID][$k];
                     // Copy name to real field
                     $tmpdata[$field] = $data[$ID][$k]['name'];

                     $specific = $item->getSpecificValueToDisplay(
                        $field,
                        $tmpdata, [
                           'html'      => true,
                           'searchopt' => $so,
                           'raw_data'  => $data
                        ]
                     );
                  }
               }
               if (!empty($specific)) {
                  $out .= $specific;
               } else {
                  if (isset($so['toadd'])
                     && isset($so['toadd'][$data[$ID][$k]['name']])) {
                     $out .= $so['toadd'][$data[$ID][$k]['name']];
                  } else {
                     // Empty is 0 or empty
                     if (empty($split[0])&& isset($so['emptylabel'])) {
                        $out .= $so['emptylabel'];
                     } else {
                        // Trans field exists
                        if (isset($data[$ID][$k]['trans']) && !empty($data[$ID][$k]['trans'])) {
                           $out .=  Dropdown::getValueWithUnit($data[$ID][$k]['trans'], $unit);
                        } else {
                           $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                        }
                     }
                  }
               }
            }
         }
         return $out;
   }
   /**
    * Create SQL search value
    *
    * @since 9.4
    *
    * @param string  $val value to search
    *
    * @return string|null
   **/
   static function makeTextSearchValue($val) {
      // Unclean to permit < and > search
      $val = PluginServicesToolbox::unclean_cross_side_scripting_deep($val);

      // escape _ char used as wildcard in mysql likes
      $val = str_replace('_', '\\_', $val);

      if ($val === 'NULL' || $val === 'null') {
         return null;
      }

      $val = trim($val);

      if ($val === '^') {
         // Special case, searching "^" means we are searching for a non empty/null field
         return '%';
      }

      if ($val === '' || $val === '^$' || $val === '$') {
         return '';
      }

      if (preg_match('/^\^/', $val)) {
         // Remove leading `^`
         $val = ltrim(preg_replace('/^\^/', '', $val));
      } else {
         // Add % wildcard before searched string if not begining by a `^`
         $val = '%' . $val;
      }

      if (preg_match('/\$$/', $val)) {
         // Remove trailing `$`
         $val = rtrim(preg_replace('/\$$/', '', $val));
      } else {
         // Add % wildcard after searched string if not ending by a `$`
         $val = $val . '%';
      }

      return $val;
   }


   static function constructData(array &$data, $onlycount = false) {
      if (!isset($data['sql']) || !isset($data['sql']['search'])) {
         return false;
      }
      $data['data'] = [];

      // Use a ReadOnly connection if available and configured to be used
      $DBread = DBConnection::getReadConnection();
      $DBread->query("SET SESSION group_concat_max_len = 16384;");

      // directly increase group_concat_max_len to avoid double query
      if (count($data['search']['metacriteria'])) {
         foreach ($data['search']['metacriteria'] as $metacriterion) {
            if ($metacriterion['link'] == 'AND NOT'
                || $metacriterion['link'] == 'OR NOT') {
               $DBread->query("SET SESSION group_concat_max_len = 4194304;");
               break;
            }
         }
      }

      $DBread->execution_time = true;
      $result = $DBread->query($data['sql']['search']);
      /// Check group concat limit : if warning : increase limit
      if ($result2 = $DBread->query('SHOW WARNINGS')) {
         if ($DBread->numrows($result2) > 0) {
            $res = $DBread->fetchAssoc($result2);
            if ($res['Code'] == 1260) {
               $DBread->query("SET SESSION group_concat_max_len = 8194304;");
               $DBread->execution_time = true;
               $result = $DBread->query($data['sql']['search']);
            }

            if ($res['Code'] == 1116) { // too many tables
               echo self::showError($data['search']['display_type'],
                                    __("'All' criterion is not usable with this object list, ".
                                       "sql query fails (too many tables). ".
                                       "Please use 'Items seen' criterion instead"));
               return false;
            }
         }
      }

      if ($result) {
         $data['data']['execution_time'] = $DBread->execution_time;
         if (isset($data['search']['savedsearches_id'])) {
            SavedSearch::updateExecutionTime(
               (int)$data['search']['savedsearches_id'],
               $DBread->execution_time
            );
         }

         $data['data']['totalcount'] = 0;
         // if real search or complete export : get numrows from request
         if (!$data['search']['no_search']
             || $data['search']['export_all']) {
            $data['data']['totalcount'] = $DBread->numrows($result);
         } else {
            if (!isset($data['sql']['count'])
               || (count($data['sql']['count']) == 0)) {
               $data['data']['totalcount'] = $DBread->numrows($result);
            } else {
               foreach ($data['sql']['count'] as $sqlcount) {
                  $result_num = $DBread->query($sqlcount);
                  $data['data']['totalcount'] += $DBread->result($result_num, 0, 0);
               }
            }
         }

         if ($onlycount) {
            //we just want to coutn results; no need to continue process
            return;
         }

         // Search case
         $data['data']['begin'] = $data['search']['start'];
         $data['data']['end']   = min($data['data']['totalcount'],
                                      $data['search']['start']+$data['search']['list_limit'])-1;
         //map case
         if (isset($data['search']['as_map'])  && $data['search']['as_map'] == 1) {
            $data['data']['end'] = $data['data']['totalcount']-1;
         }

         // No search Case
         if ($data['search']['no_search']) {
            $data['data']['begin'] = 0;
            $data['data']['end']   = min($data['data']['totalcount']-$data['search']['start'],
                                         $data['search']['list_limit'])-1;
         }
         // Export All case
         if ($data['search']['export_all']) {
            $data['data']['begin'] = 0;
            $data['data']['end']   = $data['data']['totalcount']-1;
         }

         // Get columns
         $data['data']['cols'] = [];

         $searchopt = &self::getOptions($data['itemtype']);

         foreach ($data['toview'] as $opt_id) {
            $data['data']['cols'][] = [
               'itemtype'  => $data['itemtype'],
               'id'        => $opt_id,
               'name'      => $searchopt[$opt_id]["name"],
               'meta'      => 0,
               'searchopt' => $searchopt[$opt_id],
            ];
         }

         // manage toview column for criteria with meta flag
         foreach ($data['meta_toview'] as $m_itemtype => $toview) {
            $searchopt = &self::getOptions($m_itemtype);
            foreach ($toview as $opt_id) {
               $data['data']['cols'][] = [
                  'itemtype'  => $m_itemtype,
                  'id'        => $opt_id,
                  'name'      => $searchopt[$opt_id]["name"],
                  'meta'      => 1,
                  'searchopt' => $searchopt[$opt_id],
               ];
            }
         }

         // Display columns Headers for meta items
         $already_printed = [];

         if (count($data['search']['metacriteria'])) {
            foreach ($data['search']['metacriteria'] as $metacriteria) {
               if (isset($metacriteria['itemtype']) && !empty($metacriteria['itemtype'])
                     && isset($metacriteria['value']) && (strlen($metacriteria['value']) > 0)) {

                  if (!isset($already_printed[$metacriteria['itemtype'].$metacriteria['field']])) {
                     $searchopt = &self::getOptions($metacriteria['itemtype']);

                     $data['data']['cols'][] = [
                        'itemtype'  => $metacriteria['itemtype'],
                        'id'        => $metacriteria['field'],
                        'name'      => $searchopt[$metacriteria['field']]["name"],
                        'meta'      => 1,
                        'searchopt' =>$searchopt[$metacriteria['field']]
                     ];

                     $already_printed[$metacriteria['itemtype'].$metacriteria['field']] = 1;
                  }
               }
            }
         }

         // search group (corresponding of dropdown optgroup) of current col
         foreach ($data['data']['cols'] as $num => $col) {
            // search current col in searchoptions ()
            while (key($searchopt) !== null
                   && key($searchopt) != $col['id']) {
               next($searchopt);
            }
            if (key($searchopt) !== null) {
               //search optgroup (non array option)
               while (key($searchopt) !== null
                      && is_numeric(key($searchopt))
                      && is_array(current($searchopt))) {
                  prev($searchopt);
               }
               if (key($searchopt) !== null
                   && key($searchopt) !== "common") {
                  $data['data']['cols'][$num]['groupname'] = current($searchopt);
               }

            }
            //reset
            reset($searchopt);
         }

         // Get rows

         // if real search seek to begin of items to display (because of complete search)
         if (!$data['search']['no_search']) {
            $DBread->dataSeek($result, $data['search']['start']);
         }

         $i = $data['data']['begin'];
         $data['data']['warning']
            = "For compatibility keep raw data  (ITEM_X, META_X) at the top for the moment. Will be drop in next version";

         $data['data']['rows']  = [];
         $data['data']['items'] = [];

         self::$output_type = $data['display_type'];

         while (($i < $data['data']['totalcount']) && ($i <= $data['data']['end'])) {
            $row = $DBread->fetchAssoc($result);
            $newrow        = [];
            $newrow['raw'] = $row;

            // Parse datas
            foreach ($newrow['raw'] as $key => $val) {
               if (preg_match('/ITEM(_(\w[^\d]+))?_(\d+)(_(.+))?/', $key, $matches)) {
                  $j = $matches[3];
                  if (isset($matches[2]) && !empty($matches[2])) {
                     $j = $matches[2] . '_' . $matches[3];
                  }
                  $fieldname = 'name';
                  if (isset($matches[5])) {
                     $fieldname = $matches[5];
                  }

                  // No Group_concat case
                  if ($fieldname == 'content' || strpos($val, self::LONGSEP) === false) {
                     $newrow[$j]['count'] = 1;

                     $handled = false;
                     if ($fieldname != 'content' && strpos($val, self::SHORTSEP) !== false) {
                        $split2                    = self::explodeWithID(self::SHORTSEP, $val);
                        if (is_numeric($split2[1])) {
                           $newrow[$j][0][$fieldname] = $split2[0];
                           $newrow[$j][0]['id']       = $split2[1];
                           $handled = true;
                        }
                     }

                     if (!$handled) {
                        if ($val === self::NULLVALUE) {
                           $newrow[$j][0][$fieldname] = null;
                        } else {
                           $newrow[$j][0][$fieldname] = $val;
                        }
                     }
                  } else {
                     if (!isset($newrow[$j])) {
                        $newrow[$j] = [];
                     }
                     $split               = explode(self::LONGSEP, $val);
                     $newrow[$j]['count'] = count($split);
                     foreach ($split as $key2 => $val2) {
                        $handled = false;
                        if (strpos($val2, self::SHORTSEP) !== false) {
                           $split2                  = self::explodeWithID(self::SHORTSEP, $val2);
                           if (is_numeric($split2[1])) {
                              $newrow[$j][$key2]['id'] = $split2[1];
                              if ($split2[0] == self::NULLVALUE) {
                                 $newrow[$j][$key2][$fieldname] = null;
                              } else {
                                 $newrow[$j][$key2][$fieldname] = $split2[0];
                              }
                              $handled = true;
                           }
                        }

                        if (!$handled) {
                           $newrow[$j][$key2][$fieldname] = $val2;
                        }
                     }
                  }
               } else {
                  if ($key == 'currentuser') {
                     if (!isset($data['data']['currentuser'])) {
                        $data['data']['currentuser'] = $val;
                     }
                  } else {
                     $newrow[$key] = $val;
                     // Add id to items list
                     if ($key == 'id') {
                        $data['data']['items'][$val] = $i;
                     }
                  }
               }
            }
            foreach ($data['data']['cols'] as $val) {
               $newrow[$val['itemtype'] . '_' . $val['id']]['displayname'] = self::giveItem(
                  $val['itemtype'],
                  $val['id'],
                  $newrow
               );
            }

            $data['data']['rows'][$i] = $newrow;
            $i++;
         }

         $data['data']['count'] = count($data['data']['rows']);
      } else {
         echo $DBread->error();
      }
   }

   static function getDatas($itemtype, $params, array $forcedisplay = []) {
      $data = self::prepareDatasForSearch($itemtype, $params, $forcedisplay);
      self::constructSQL($data);
      self::constructData($data);
      return $data;
   }
}