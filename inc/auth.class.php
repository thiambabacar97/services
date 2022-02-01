<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  Identification class used to login
 */
class PluginServicesAuth extends Auth {

    static function redirectIfAuthenticated($redirect = null) {
        global $CFG_GLPI;
  
        if (!Session::getLoginUserID()) {
           return false;
        }
  
        if (Session::mustChangePassword()) {
           Html::redirect($CFG_GLPI['root_doc'] . '/front/updatepassword.php');
        }
  
        if (!$redirect) {
           if (isset($_POST['redirect']) && (strlen($_POST['redirect']) > 0)) {
              $redirect = $_POST['redirect'];
           } else if (isset($_GET['redirect']) && strlen($_GET['redirect']) > 0) {
              $redirect = $_GET['redirect'];
           }
        }
  
        //Direct redirect
        if ($redirect) {
           Toolbox::manageRedirect($redirect);
        }
  
        // Redirect to Command Central if not post-only
        if (Session::getCurrentInterface() == "helpdesk") {
           if ($_SESSION['glpiactiveprofile']['create_ticket_on_login']) {
              Html::redirect($CFG_GLPI['root_doc'] . "/portal");
           }
           Html::redirect($CFG_GLPI['root_doc'] . "/portal");
  
        } else {
           if ($_SESSION['glpiactiveprofile']['create_ticket_on_login']) {
              Html::redirect(Ticket::getFormURL());
           }
           Html::redirect($CFG_GLPI['root_doc'] . "/home");
        }
     }


   function login($login_name, $login_password, $noauto = false, $remember_me = false, $login_auth = '') {
      global $DB, $CFG_GLPI;

      $this->getAuthMethods();
      $this->user_present  = 1;
      $this->auth_succeded = false;
      //In case the user was deleted in the LDAP directory
      $user_deleted_ldap   = false;

      // Trim login_name : avoid LDAP search errors
      $login_name = trim($login_name);

      // manage the $login_auth (force the auth source of the user account)
      $this->user->fields["auths_id"] = 0;
      if ($login_auth == 'local') {
         $authtype = self::DB_GLPI;
         $this->user->fields["authtype"] = self::DB_GLPI;
      } else if (strstr($login_auth, '-')) {
         $auths = explode('-', $login_auth);
         $this->user->fields["auths_id"] = $auths[1];
         if ($auths[0] == 'ldap') {
            $authtype = self::LDAP;
            $this->user->fields["authtype"] = self::LDAP;
         } else if ($auths[0] == 'mail') {
            $authtype = self::MAIL;
            $this->user->fields["authtype"] = self::MAIL;
         } else if ($auths[0] == 'external') {
            $authtype = self::EXTERNAL;
            $this->user->fields["authtype"] = self::EXTERNAL;
         }
      }
      if (!$noauto && ($authtype = self::checkAlternateAuthSystems())) {
         if ($this->getAlternateAuthSystemsUserLogin($authtype)
             && !empty($this->user->fields['name'])) {
            // Used for log when login process failed
            $login_name                        = $this->user->fields['name'];
            $this->auth_succeded               = true;
            $this->user_present                = $this->user->getFromDBbyName(addslashes($login_name));
            $this->extauth                     = 1;
            $user_dn                           = false;

            if (array_key_exists('_useremails', $this->user->fields)) {
                $email = $this->user->fields['_useremails'];
            }

            $ldapservers = [];
            //if LDAP enabled too, get user's infos from LDAP
            if (Toolbox::canUseLdap()) {
               //User has already authenticate, at least once : it's ldap server if filled
               if (isset($this->user->fields["auths_id"])
                   && ($this->user->fields["auths_id"] > 0)) {
                  $authldap = new AuthLDAP();
                  //If ldap server is enabled
                  if ($authldap->getFromDB($this->user->fields["auths_id"])
                      && $authldap->fields['is_active']) {
                     $ldapservers[] = $authldap->fields;
                  }
               } else { // User has never been authenticated : try all active ldap server to find the right one
                  foreach (getAllDataFromTable('glpi_authldaps', ['is_active' => 1]) as $ldap_config) {
                     $ldapservers[] = $ldap_config;
                  }
               }

               $ldapservers_status = false;
               foreach ($ldapservers as $ldap_method) {
                  $ds = AuthLDAP::connectToServer($ldap_method["host"],
                                                  $ldap_method["port"],
                                                  $ldap_method["rootdn"],
                                                  Toolbox::sodiumDecrypt($ldap_method["rootdn_passwd"]),
                                                  $ldap_method["use_tls"],
                                                  $ldap_method["deref_option"]);

                  if ($ds) {
                     $ldapservers_status = true;
                     $params = [
                        'method' => AuthLDAP::IDENTIFIER_LOGIN,
                        'fields' => [
                           AuthLDAP::IDENTIFIER_LOGIN => $ldap_method["login_field"],
                        ],
                     ];
                     try {
                        $user_dn = AuthLDAP::searchUserDn($ds, [
                           'basedn'            => $ldap_method["basedn"],
                           'login_field'       => $ldap_method['login_field'],
                           'search_parameters' => $params,
                           'condition'         => $ldap_method["condition"],
                           'user_params'       => [
                              'method' => AuthLDAP::IDENTIFIER_LOGIN,
                              'value'  => $login_name
                           ],
                        ]);
                     } catch (\RuntimeException $e) {
                        Toolbox::logError($e->getMessage());
                        $user_dn = false;
                     }
                     if ($user_dn) {
                        $this->user_found = true;
                        $this->user->fields['auths_id'] = $ldap_method['id'];
                        $this->user->getFromLDAP($ds, $ldap_method, $user_dn['dn'], $login_name,
                                                 !$this->user_present);
                        break;
                     }
                  }
               }
            }
            if ((count($ldapservers) == 0)
                && ($authtype == self::EXTERNAL)) {
               // Case of using external auth and no LDAP servers, so get data from external auth
               $this->user->getFromSSO();
            } else {
               if ($this->user->fields['authtype'] == self::LDAP) {
                  if (!$ldapservers_status) {
                     $this->auth_succeded = false;
                     $this->addToError(_n('Connection to LDAP directory failed',
                                          'Connection to LDAP directories failed',
                                          count($ldapservers)));
                  } else if (!$user_dn && $this->user_present) {
                     //If user is set as present in GLPI but no LDAP DN found : it means that the user
                     //is not present in an ldap directory anymore
                     $user_deleted_ldap = true;
                     $this->addToError(_n('User not found in LDAP directory',
                                          'User not found in LDAP directories',
                                           count($ldapservers)));
                  }
               }
            }
            // Reset to secure it
            $this->user->fields['name']       = $login_name;
            $this->user->fields["last_login"] = $_SESSION["glpi_currenttime"];

         } else {
            $this->addToError(__('Empty login or password'));
         }
      }

      if (!$this->auth_succeded) {
         if (empty($login_name) || strstr($login_name, "\0")
             || empty($login_password) || strstr($login_password, "\0")) {
            $this->addToError(__('Empty login or password'));
         } else {

            // Try connect local user if not yet authenticated
            if (empty($login_auth)
                  || $this->user->fields["authtype"] == $this::DB_GLPI) {
               $this->auth_succeded = $this->connection_db(addslashes($login_name),
                                                           $login_password);
            }

            // Try to connect LDAP user if not yet authenticated
            if (!$this->auth_succeded) {
               if (empty($login_auth)
                     || $this->user->fields["authtype"] == $this::CAS
                     || $this->user->fields["authtype"] == $this::EXTERNAL
                     || $this->user->fields["authtype"] == $this::LDAP) {

                  if (Toolbox::canUseLdap()) {
                     AuthLDAP::tryLdapAuth($this, $login_name, $login_password,
                                             $this->user->fields["auths_id"]);
                     if (!$this->auth_succeded && !$this->user_found) {
                        $search_params = [
                           'name'     => addslashes($login_name),
                           'authtype' => $this::LDAP];
                        if (!empty($login_auth)) {
                           $search_params['auths_id'] = $this->user->fields["auths_id"];
                        }
                        if ($this->user->getFromDBByCrit($search_params)) {
                           $user_deleted_ldap = true;
                        };
                     }
                  }
               }
            }

            // Try connect MAIL server if not yet authenticated
            if (!$this->auth_succeded) {
               if (empty($login_auth)
                     || $this->user->fields["authtype"] == $this::MAIL) {
                  AuthMail::tryMailAuth(
                     $this,
                     $login_name,
                     $login_password,
                     $this->user->fields["auths_id"]
                  );
               }
            }
         }
      }

      if ($user_deleted_ldap) {
         User::manageDeletedUserInLdap($this->user->fields["id"]);
         $this->auth_succeded = false;
      }
      // Ok, we have gathered sufficient data, if the first return false the user
      // is not present on the DB, so we add him.
      // if not, we update him.
      if ($this->auth_succeded) {

         //Set user an not deleted from LDAP
         $this->user->fields['is_deleted_ldap'] = 0;

         // Prepare data
         $this->user->fields["last_login"] = $_SESSION["glpi_currenttime"];
         if ($this->extauth) {
            $this->user->fields["_extauth"] = 1;
         }

         if ($DB->isSlave()) {
            if (!$this->user_present) { // Can't add in slave mode
               $this->addToError(__('User not authorized to connect in GLPI'));
               $this->auth_succeded = false;
            }
         } else {
            if ($this->user_present) {
               // First stripslashes to avoid double slashes
               $input = Toolbox::stripslashes_deep($this->user->fields);
               // Then ensure addslashes
               $input = Toolbox::addslashes_deep($input);

               // Add the user e-mail if present
               if (isset($email)) {
                   $this->user->fields['_useremails'] = $email;
               }
               $this->user->update($input);
            } else if ($CFG_GLPI["is_users_auto_add"]) {
               // Auto add user
               // First stripslashes to avoid double slashes
               $input = Toolbox::stripslashes_deep($this->user->fields);
               // Then ensure addslashes
               $input = Toolbox::addslashes_deep($input);
               unset ($this->user->fields);
               if ($authtype == self::EXTERNAL && !isset($input["authtype"])) {
                  $input["authtype"] = $authtype;
               }
               $this->user->add($input);
            } else {
               // Auto add not enable so auth failed
               $this->addToError(__('User not authorized to connect in GLPI'));
               $this->auth_succeded = false;
            }
         }
      }

      // Log Event (if possible)
      if (!$DB->isSlave()) {
         // GET THE IP OF THE CLIENT
         $ip = getenv("HTTP_X_FORWARDED_FOR")?
            Toolbox::clean_cross_side_scripting_deep(getenv("HTTP_X_FORWARDED_FOR")):
            getenv("REMOTE_ADDR");

         if ($this->auth_succeded) {
            if (GLPI_DEMO_MODE) {
               // not translation in GLPI_DEMO_MODE
               Event::log(-1, "system", 3, "login", $login_name." log in from ".$ip);
            } else {
               //TRANS: %1$s is the login of the user and %2$s its IP address
               Event::log(-1, "system", 3, "login", sprintf(__('%1$s log in from IP %2$s'),
                                                            $login_name, $ip));
            }

         } else {
            if (GLPI_DEMO_MODE) {
               Event::log(-1, "system", 3, "login", "login",
                          "Connection failed for " . $login_name . " ($ip)");
            } else {
               //TRANS: %1$s is the login of the user and %2$s its IP address
               Event::log(-1, "system", 3, "login", sprintf(__('Failed login for %1$s from IP %2$s'),
                                                            $login_name, $ip));
            }
         }
      }

      Session::init($this);

      if ($noauto) {
         $_SESSION["noAUTO"] = 1;
      }

      if ($this->auth_succeded && $CFG_GLPI['login_remember_time'] > 0 && $remember_me) {
         $token = $this->user->getAuthToken('cookie_token', true);

         if ($token) {
            //Cookie name (Allow multiple GLPI)
            $cookie_name = session_name() . '_rememberme';
            //Cookie session path
            $cookie_path = ini_get('session.cookie_path');

            $data = json_encode([
                $this->user->fields['id'],
                $token,
            ]);

            //Send cookie to browser
            setcookie($cookie_name, $data, time() + $CFG_GLPI['login_remember_time'], $cookie_path);
            $_COOKIE[$cookie_name] = $data;
         }
      }

      if ($this->auth_succeded && !empty($this->user->fields['timezone']) && 'null' !== strtolower($this->user->fields['timezone'])) {
         //set user timezone, if any
         $_SESSION['glpi_tz'] = $this->user->fields['timezone'];
         $DB->setTimezone($this->user->fields['timezone']);
      }

      return $this->auth_succeded;
   }
    
}