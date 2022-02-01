<?php
/**
 * Install hook
 *
 * @return boolean
 */


function plugin_services_install() {
   
   global $DB;

   //instanciate migration with version
   $migration = new Migration(100);

   if (!$DB->tableExists('glpi_plugin_services_apparences')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_apparences` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `logo` VARCHAR(500) NOT NULL,
                  `banner` VARCHAR(500) NOT NULL,
                  `mark` VARCHAR(100) NOT NULL,
                  `textBanner` VARCHAR(250) NOT NULL,
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_entities')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_entities` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `entity_id` INT(11),
                  `location_id` INT(11),
                  FOREIGN KEY (entity_id) REFERENCES glpi_entities (id),
                  FOREIGN KEY (location_id) REFERENCES glpi_locations (id),
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_services')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_services` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_themes')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_themes` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `sidebar_header_color` VARCHAR(20) NOT NULL,
                  `sidebar_color` VARCHAR(20) NOT NULL,
                  `sidebar_menu_color` VARCHAR(20) NOT NULL,
                  `mark` VARCHAR(100) NOT NULL,
                  `name` VARCHAR(20) NOT NULL,
                  `active` INT(11)  DEFAULT 0,
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_entities')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_entities` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_menus')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_menus` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `menus_id` INT(11) DEFAULT NULL,
                  `item` VARCHAR(200) NOT NULL,
                  `rightname` VARCHAR(200) NOT NULL,
                  `type` VARCHAR(200) NOT NULL,
                  `menu_type` VARCHAR(200) NOT NULL,
                  `path` VARCHAR(100) NOT NULL,
                  `criteria` JSON CHECK (JSON_VALID(criteria) || criteria in(NULL) ),
                  `display_name` VARCHAR(100),
                  `display_badge` TINYINT DEFAULT 0,
                  `display_menu` TINYINT DEFAULT 1,
                  `plugin_name` VARCHAR(50) NOT NULL,
                  `icon` VARCHAR(50) ,
                  `order` INT(11) DEFAULT 200,
                  PRIMARY KEY  (`id`),
                  FOREIGN KEY (menus_id) REFERENCES glpi_plugin_services_menus (id)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_companies')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_companies` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(500) NOT NULL,
                  `phone` INT(250),
                  `street` VARCHAR(500) NOT NULL,
                  `fax_phone` INT(250),
                  `city` VARCHAR(250) NOT NULL,
                  `state` VARCHAR(250) NOT NULL,
                  `note` VARCHAR(500) NOT NULL,
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_grid_tickets')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_grid_tickets` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `itemtype` VARCHAR(500) NOT NULL,
                  `field` VARCHAR(500) NOT NULL,
                  `dashboards_dashboards_id` INT(11),
                  FOREIGN KEY (dashboards_dashboards_id) REFERENCES glpi_dashboards_dashboards (id),
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_departements')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_departements` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(500) NOT NULL,
                  `companies_id` INT(11),
                  `description` VARCHAR(500) NOT NULL,
                  FOREIGN KEY (companies_id) REFERENCES glpi_plugin_services_companies (id),
                  PRIMARY KEY  (`id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists('glpi_plugin_services_relatedlists')) {
      //table creation query
      $query = "CREATE TABLE `glpi_plugin_services_relatedlists` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `itemtype` VARCHAR(100) NOT NULL,
                  `users_id` INT(11) DEFAULT NULL,
                  `itemlink` VARCHAR(100) NOT NULL,
                  `columId` INT(11) DEFAULT NULL,
                  `pivotitem` VARCHAR(100) DEFAULT NULL,
                  `columName` VARCHAR(100) DEFAULT NULL,
                  `name` VARCHAR(100) DEFAULT NULL,
                  `rank` INT(11) DEFAULT 1,
                  PRIMARY KEY  (`id`),
                  FOREIGN KEY (users_id) REFERENCES glpi_users (id)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());
   }

   if ($DB->tableExists('glpi_groups')) {
      $migration->addField('glpi_groups', 'companies_id', "INT(11) DEFAULT NULL");
   }
   if ($DB->tableExists('glpi_users')) {
      $migration->addField('glpi_users', 'companies_id', "INT(11) DEFAULT NULL");
   }

   if ($DB->tableExists('glpi_users')) {
      $migration->addField('glpi_users', 'departements_id', "INT(11) DEFAULT NULL");
   }

   if ($DB->tableExists('glpi_groups')) {
      $migration->addField('glpi_groups', 'departements_id', "INT(11) DEFAULT NULL");
   }
   $migration->executeMigration();


   include_once("inc/menu.class.php");
   PluginServicesMenu::install($migration);

   return true;
}

/**
 * Uninstall hook
 *
 * @return boolean
 */
function plugin_services_uninstall() {
   global $DB;
   $migration = new Migration(100);
   $tables = [ 
      'apparences',
      'themes',
      'services',
      'menus',
      'relatedlists',
      'companies',
      'departements',
      'grid_tickets'
   ];

   foreach ($tables as $table) {
      $tablename = 'glpi_plugin_services_' . $table;
      //Create table only if it does not exists yet!
      if ($DB->tableExists($tablename)) {
         $DB->queryOrDie(
            "DROP TABLE `$tablename`",
            $DB->error()
         );
      }
   }

   $attributs_groups = [
      'companies_id',
   ];

   $attributs_users = [
      'companies_id',
      'departements_id',
   ];

   foreach ($attributs_groups as $key => $value) {
      if ($DB->tableExists('glpi_groups')) {
         $migration->dropField(
            'glpi_groups',
            $value
         );
      }
   }

   foreach ($attributs_users as $key => $value) {
      if ($DB->tableExists('glpi_users')) {
         $migration->dropField(
            'glpi_users',
            $value
         );
      }
   }
   
   PluginServicesMenu::uninstall();
   return true;
}



function plugin_services_getDatabaseRelations(){
   return [
      'glpi_plugin_services_companies' => [
         'glpi_users'   => 'companies_id',
         'glpi_groups'   => 'companies_id',
         'glpi_plugin_services_departements'   => 'companies_id',
      ],

      'glpi_plugin_services_departements' => [
         'glpi_users'   => 'departements_id',
         'glpi_groups'   => 'departements_id',
      ]
   ];
}


