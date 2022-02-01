<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 *  Class used to manage Auth LDAP config
 */
class PluginServicesImportLDAP extends PluginServicesAuthLDAP {
  // static $rightname = 'plugin_services_import_ldap';

  public function showList($itemtype, $params){
    global $CFG_GLPI;
    echo'
    <div class="m-content">
      <div class="row">
          <div class="col-xl-12 ">
            <div class="m-portlet m-portlet--tab">
              <div class="m-portlet__head">
                <div class="m-portlet__head-caption">
                  <div class="m-portlet__head-title">
                    <span class="m-portlet__head-icon">
                        <i class="flaticon-user-add"></i>
                    </span>
                    <h3 class="m-portlet__head-text">
                      <small>Importation de nouveaux utilisateurs LDAP</small>
                    </h3>
                  </div>
                </div>
                <div class="m-portlet__head-tools">
                  <ul class="m-portlet__nav">
                    <li class="m-portlet__nav-item">
                      <a href="'.$CFG_GLPI['root_doc'].'/ruleticket/form" class="btn btn-secondary btn-lg m-btn m-btn--icon m-btn--icon-only bg-light">
                        <i class="flaticon-add-circular-button"></i>
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
              <div class="m-portlet__body">';
                self::importUser();
                echo'  
              </div>
            </div>
          </div>
      </div>
    </div>
  ';
      
  }
}