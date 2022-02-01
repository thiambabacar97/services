<?php

class PluginServicesCommonDBTM extends CommonDBTM {

    static function dropdown($options = []) {
        echo get_called_class();
        return PluginServicesDropdown::show(get_called_class(), $options);
    }



   function showFormHeader($options = []) {

         $ID     = $this->fields['id'];
   
         $params = [
            'target'       => $this->getFormURL(),
            'colspan'      => 2,
            'withtemplate' => '',
            'formoptions'  => '',
            'canedit'      => true,
            'formtitle'    => null,
            'noid'         => false
         ];
   
         if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
               $params[$key] = $val;
            }
         }
   
         // Template case : clean entities data
         if (($params['withtemplate'] == 2)
               && $this->isEntityAssign()) {
            $this->fields['entities_id']  = $_SESSION['glpiactive_entity'];
         }
   
         $rand = mt_rand();
         if ($this->canEdit($ID)) {
            echo "<form name='form' method='post' action='".$params['target']."' ".
                     $params['formoptions']." enctype=\"multipart/form-data\">";
   
            //Should add an hidden entities_id field ?
            //If the table has an entities_id field
            if ($this->isField("entities_id")) {
               //The object type can be assigned to an entity
               if ($this->isEntityAssign()) {
                  if (isset($params['entities_id'])) {
                     $entity = $this->fields['entities_id'] = $params['entities_id'];
                  } else if (isset($this->fields['entities_id'])) {
                     //It's an existing object to be displayed
                     $entity = $this->fields['entities_id'];
                  } else if ($this->isNewID($ID)
                              || ($params['withtemplate'] == 2)) {
                     //It's a new object to be added
                     $entity = $_SESSION['glpiactive_entity'];
                  }
   
                  echo "<input type='hidden' name='entities_id' value='$entity'>";
   
               } else if ($this->getType() != 'User') {
                  // For Rules except ruleticket and slalevel
                  echo "<input type='hidden' name='entities_id' value='0'>";
   
               }
            }
         }
   
         echo "<div class='spaced' id='tabsbody'>";
         echo "<table class='tab_cadre_fixe' id='mainformtable'>";
   
         if ($params['formtitle'] !== '' && $params['formtitle'] !== false) {
            echo "<tr class='headerRow'><th colspan='".$params['colspan']."'>";
   
            if (!empty($params['withtemplate']) && ($params['withtemplate'] == 2)
               && !$this->isNewID($ID)) {
   
               echo "<input type='hidden' name='template_name' value='".$this->fields["template_name"]."'>";
   
               //TRANS: %s is the template name
               printf(__('Created from the template %s'), $this->fields["template_name"]);
   
            } else if (!empty($params['withtemplate']) && ($params['withtemplate'] == 1)) {
               echo "<input type='hidden' name='is_template' value='1'>\n";
               echo "<label for='textfield_template_name$rand'>" . __('Template name') . "</label>";
               Html::autocompletionTextField(
                  $this,
                  'template_name',
                  [
                     'size'      => 25,
                     'required'  => true,
                     'rand'      => $rand
                  ]
               );
            } else if ($this->isNewID($ID)) {
               $nametype = $params['formtitle'] !== null ? $params['formtitle'] : $this->getTypeName(1);
               printf(__('%1$s - %2$s'), __('New item'), $nametype);
            } else {
               $nametype = $params['formtitle'] !== null ? $params['formtitle'] : $this->getTypeName(1);
               if (!$params['noid'] && ($_SESSION['glpiis_ids_visible'] || empty($nametype))) {
                  //TRANS: %1$s is the Itemtype name and $2$d the ID of the item
                  $nametype = sprintf(__('%1$s - ID %2$d'), $nametype, $ID);
               }
               echo $nametype;
            }
            $entityname = '';
            if (isset($this->fields["entities_id"])
               && Session::isMultiEntitiesMode()
               && $this->isEntityAssign()) {
               $entityname = Dropdown::getDropdownName("glpi_entities", $this->fields["entities_id"]);
            }
   
            echo "</th><th colspan='".$params['colspan']."'>";
            if (get_class($this) != 'Entity') {
               if ($this->maybeRecursive()) {
                  if (Session::isMultiEntitiesMode()) {
                     echo "<table class='tab_format'><tr class='headerRow responsive_hidden'><th>".$entityname."</th>";
                     echo "<th class='right'><label for='dropdown_is_recursive$rand'>".__('Child entities')."</label></th><th>";
                     if ($params['canedit']) {
                        if ($this instanceof CommonDBChild) {
                           echo Dropdown::getYesNo($this->isRecursive());
                           if (isset($this->fields["is_recursive"])) {
                              echo "<input type='hidden' name='is_recursive' value='".$this->fields["is_recursive"]."'>";
                           }
                           $comment = __("Can't change this attribute. It's inherited from its parent.");
                           // CommonDBChild : entity data is get or copy from parent
   
                        } else if (!$this->can($ID, 'recursive')) {
                           echo Dropdown::getYesNo($this->fields["is_recursive"]);
                           $comment = __('You are not allowed to change the visibility flag for child entities.');
   
                        } else if (!$this->canUnrecurs()) {
                           echo Dropdown::getYesNo($this->fields["is_recursive"]);
                           $comment = __('Flag change forbidden. Linked items found.');
   
                        } else {
                           Dropdown::showYesNo("is_recursive", $this->fields["is_recursive"], -1, ['rand' => $rand]);
                           $comment = __('Change visibility in child entities');
                        }
                        echo " ";
                        Html::showToolTip($comment);
                     } else {
                        echo Dropdown::getYesNo($this->fields["is_recursive"]);
                     }
                     echo "</th></tr></table>";
                  } else {
                     echo $entityname;
                     echo "<input type='hidden' name='is_recursive' value='0'>";
                  }
               } else {
                  echo $entityname;
               }
            }
            echo "</th></tr>\n";
         }
   
         Plugin::doHook("pre_item_form", ['item' => $this, 'options' => &$params]);
   
         // If in modal : do not display link on message after redirect
         if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
            echo "<input type='hidden' name='_no_message_link' value='1'>";
         }
   }
}