<?php


if (!defined('GLPI_ROOT')) {
  die("Sorry. You can't access this file directly");
}

class PluginServicesMenu extends CommonDBTM {

  static $table = "glpi_plugin_services_menus";
  static $order = 100;
  static $plugin_name = 'services';

  static function install(Migration $migration) {
      global $DB;

      $menus = new self();
      $ruleticketcriteria = [
        "is_deleted"=> 0,
        "as_map"=> 0, 
        "criteria"=> [
          [
            "link" => "AND", 
            "field" => 122,
            "searchtype" =>"equals", 
            "search" => "Rechercher",
            "value"=> 'PluginServicesRuleTicket',  
            "itemtype" => "PluginServicesRuleTicket",
          ]
        ]
      ];

      $params = [
          [
            'item' => 'PluginServicesService',
            'is_parent' => true,
            'path' => '----',
            'rightname' => 'plugin_services',
            'type' => 'core',
            'menu_type' => 'main',
            'display_name' => 'Administration',
            'display_menu' => true,
            'plugin_name' => self::$plugin_name,
            'icon'        => 'flaticon-share',
            'order'       => 2,
            'submenu' => [
                    [
                      'item' => 'PluginServicesTheme', 
                      'is_parent' => false,
                      'path' => 'theme',
                      'rightname' => 'plugin_services_themes',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Thèmes',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name,
                    ],
                    [
                      'item' => 'PluginServicesTheme', 
                      'is_parent' => false,
                      'path' => 'theme/form',
                      'rightname' => 'plugin_services_themes',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Thèmes',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesEntity', 
                      'is_parent' => false,
                      'path' => 'entity',
                      'rightname' => 'plugin_services_entity',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Entités',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesEntity', 
                      'is_parent' => false,
                      'path' => 'entity/form',
                      'rightname' => 'plugin_services_entity',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Entités',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesPlugin', 
                      'is_parent' => false,
                      'path' => 'plugin',
                      'rightname' => 'config',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Modules',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesProfile', 
                      'is_parent' => false,
                      'path' => 'profile',
                      'rightname' => 'profile',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Profile',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesProfile', 
                      'is_parent' => false,
                      'path' => 'profile/form',
                      'rightname' => 'profile',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Profile',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesUser', 
                      'is_parent' => false,
                      'path' => 'user',
                      'rightname' => 'plugin_services_users',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Utilisateurs',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesUser', 
                      'is_parent' => false,
                      'path' => 'user/form',
                      'rightname' => 'plugin_services_users',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Utilisateurs',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesGroup_User', 
                      'is_parent' => false,
                      'path' => 'group_user/form',
                      'rightname' => 'plugin_services_users',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '----',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],
                    [
                      'item' => 'PluginServicesCompany', 
                      'is_parent' => false,
                      'path' => 'company/form',
                      'rightname' => 'plugin_services_company',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '____',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesCompany', 
                      'is_parent' => false,
                      'path' => 'company',
                      'rightname' => 'plugin_services_company',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Société',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesDepartement', 
                      'is_parent' => false,
                      'path' => 'departement',
                      'rightname' => 'plugin_services_departement',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Département',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name,
                    ],
                    [
                      'item' => 'PluginServicesDepartement', 
                      'is_parent' => false,
                      'path' => 'departement/form',
                      'rightname' => 'plugin_services_departement',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '______',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],

                    [
                      'item' => 'PluginServicesGroup', 
                      'is_parent' => false,
                      'path' => 'group',
                      'rightname' => 'group',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Groupes',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesGroup', 
                      'is_parent' => false,
                      'path' => 'group/form',
                      'rightname' => 'group',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Groupes',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesAuthLDAP', 
                      'is_parent' => false,
                      'path' => 'authldap/form',
                      'rightname' => 'plugin_services_auth_ldap',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '----',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],
                    // [
                    //   'item' => 'PluginServicesAuthLDAP', 
                    //   'is_parent' => false,
                    //   'path' => 'authldap',
                    //   'rightname' => 'plugin_services_auth_ldap',
                    //   'type' => 'module',
                    //   'menu_type' => 'submenu',
                    //   'display_name' => 'Annuaires LDAP',
                    //   'display_menu' => false,
                    //   'plugin_name' => self::$plugin_name,
                    // ],
                    [
                      'item' => 'PluginServicesPlugin', 
                      'is_parent' => false,
                      'path' => 'plugin/form',
                      'rightname' => 'config',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Plugin',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesDocument_Item', 
                      'is_parent' => false,
                      'path' => 'document/form',
                      'rightname' => 'plugin_services_company',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Plugin',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name,
                    ],[
                      'item' => 'PluginServicesRuleTicket', 
                      'is_parent' => false,
                      'path' => 'ruleticket',
                      'rightname' => 'rule_ticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Règles métier pour les tickets',
                      'display_menu' => true,
                      'criteria' =>  json_encode($ruleticketcriteria),
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesRuleTicket', 
                      'is_parent' => false,
                      'path' => 'ruleticket/form',
                      'rightname' => 'rule_ticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Régle',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],
                    [
                      'item' => 'PluginServicesRuleCriteria', 
                      'is_parent' => false,
                      'path' => 'rulecriteria/form',
                      'rightname' => 'plugin_services_ruleticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '----',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],
                    [
                      'item' => 'PluginServicesRuleAction', 
                      'is_parent' => false,
                      'path' => 'ruleaction/form',
                      'rightname' => 'plugin_services_ruleticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '--------',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],
                    [
                      'item' => 'PluginServicesItem', 
                      'is_parent' => false,
                      'path' => 'item/form',
                      'rightname' => 'plugin_services_ruleticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Dashboard',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],
                    [
                      'item' => 'PluginServicesProvider', 
                      'is_parent' => false,
                      'path' => 'provider/form',
                      'rightname' => 'plugin_services_ruleticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Provider',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesGrid_Ticket', 
                      'is_parent' => false,
                      'path' => 'gridticket/form',
                      'rightname' => 'plugin_services_grid_tickets',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Grid Ticket',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesDashboard', 
                      'is_parent' => false,
                      'path' => 'dashboard/form',
                      'rightname' => 'plugin_services_ruleticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Dashboard',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],
                    [
                      'item' => 'PluginServicesDashboard', 
                      'is_parent' => false,
                      'path' => 'dashboard',
                      'rightname' => 'plugin_services_ruleticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Dashboard',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],
                    [
                      'item' => 'PluginServicesRuleTicket', 
                      'is_parent' => false,
                      'path' => 'rule/form',
                      'rightname' => 'plugin_services_ruleticket',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '-------',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesSLM',
                      'is_parent' => false,
                      'path' => 'slm',
                      'rightname' => 'plugin_services_slms',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Niveaux de services',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesSLM',
                      'is_parent' => false,
                      'path' => 'slm/form',
                      'rightname' => 'plugin_services',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '-------',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesSla',
                      'is_parent' => false,
                      'path' => 'sla/form',
                      'rightname' => 'plugin_services_sla',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '-------',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesOLA',
                      'is_parent' => false,
                      'path' => 'ola/form',
                      'rightname' => 'plugin_services_ola',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '-------',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesSlalevel', 
                      'is_parent' => false,
                      'path' => 'slalevel',
                      'rightname' => 'plugin_services_slalevels',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '----',
                      'display_menu' => false,
                    ],[
                      'item' => 'PluginServicesSlalevel', 
                      'is_parent' => false,
                      'path' => 'slalevel/form',
                      'rightname' => 'plugin_services_slalevels',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '----',
                      'display_menu' => false,
                    ],
                    [
                      'item' => 'PluginServicesLocation', 
                      'is_parent' => false,
                      'path' => 'location/form',
                      'rightname' => 'location',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '-----------',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesLocation', 
                      'is_parent' => false,
                      'path' => 'location',
                      'rightname' => 'location',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => 'Localisations',
                      'display_menu' => true,
                      'plugin_name' => self::$plugin_name
                    ],[
                      'item' => 'PluginServicesRelatedlist', 
                      'is_parent' => false,
                      'path' => 'relatedlist',
                      'rightname' => 'plugin_services',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '----',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],
                    [
                      'item' => 'PluginServicesState', 
                      'is_parent' => false,
                      'path' => 'state',
                      'rightname' => 'plugin_services',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '----',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ],
                    [
                      'item' => 'PluginServicesDocument', 
                      'is_parent' => false,
                      'path' => 'document',
                      'rightname' => 'plugin_services',
                      'type' => 'module',
                      'menu_type' => 'submenu',
                      'display_name' => '----',
                      'display_menu' => false,
                      'plugin_name' => self::$plugin_name
                    ]
            ]
          ]
      ];

      $id_parent = "";
      foreach ($params as $value) {
        if($value['is_parent']==true){
          $id_parent = $menus->add($value);
          if (isset($value['submenu'])) {
            foreach ($value['submenu'] as  $submenu) {
              $submenu['menus_id'] = $id_parent;
              $menus->add($submenu);
            }
          }
        }
      }
  }

  static function uninstall() {
    $menus = new self();
    $menus->deleteByCriteria(['plugin_name' => 'services']);
  }
}