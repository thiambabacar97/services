<?php

define('SERVICES_VERSION', '1.0');

/**
 * Init the hooks of the plugins - Needed
 *
 * @return void
 */
function plugin_init_services() {

   global $PLUGIN_HOOKS;
   $PLUGIN_HOOKS['csrf_compliant']['services'] = true;

   if (Session::getLoginUserID()) {
      CronTask::register('PluginServicesAuthLDAP', 'importuserldap', MINUTE_TIMESTAMP, array(
                           'comment'   => 'Importer les utilisateurs AD',
                           'mode'      => CronTask::MODE_EXTERNAL
                           ));
      Plugin::registerClass('PluginServicesAuthLDAP', ['addtabon' => 'AuthLDAP']);
      Plugin::registerClass('PluginServicesProfile', ['addtabon' => 'PluginServicesProfile']);
      Plugin::registerClass('PluginServicesEntity', ['addtabon' => 'PluginServicesEntity']);
      Plugin::registerClass('PluginServicesUser', ['addtabon' => 'PluginServicesUser']);
      Plugin::registerClass('PluginServicesGroup', ['addtabon' => 'PluginServicesGroup']);   
      Plugin::registerClass('PluginServicesSLM', ['addtabon' => 'PluginServicesSLM']);
      Plugin::registerClass('PluginServicesSLA', ['addtabon' => 'SLA']);
      Plugin::registerClass('PluginServicesSlaLevel', ['addtabon' => 'SlaLevel']);
      Plugin::registerClass('PluginServicesRuleTicket', ['addtabon' => 'PluginServicesRuleTicket']);   
      Plugin::registerClass('PluginServicesDepartement', ['addtabon' => 'PluginServicesDepartement']);
      Plugin::registerClass('PluginServicesCompany', ['addtabon' => 'PluginServicesCompany']); 
      Plugin::registerClass('PluginServicesGrid', ['addtabon' => 'PluginServicesGrid']);
      Plugin::registerClass('PluginServicesDashboard', ['addtabon' => 'PluginServicesDashboard']);
   }
}

/**
 * Get the name and the version of the plugin - Needed
 *
 * @return array
 */
function plugin_version_services() {
   return [
      'name'           => 'Services',
      'version'        => SERVICES_VERSION,
      'author'         => 'Babacar Gaye',
      'license'        => 'GLPv3',
      'homepage'       => 'http://perdu.com',
      'requirements'   => [
         'glpi'   => [
            'min' => '9.1'
         ]
      ]
   ];
}

/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
 *
 * @return boolean
 */
function plugin_services_check_prerequisites() {
   //do what the checks you want
   return true;
}

/**
 * Check configuration process for plugin : need to return true if succeeded
 * Can display a message only if failure and $verbose is true
 *
 * @param boolean $verbose Enable verbosity. Default to false
 *
 * @return boolean
 */
function plugin_services_check_config($verbose = false) {
   // Your configuration check
   if (true) { 
      return true;
   }

   if ($verbose) {
      echo "Installed, but not configured";
   }
   return false;
}


