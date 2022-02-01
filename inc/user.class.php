<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

use Sabre\VObject;
use Glpi\Exception\ForgetPasswordException;
use Glpi\Exception\PasswordTooWeakException;
use Glpi\Event;

class PluginServicesUser extends User {

    static $rightname = 'user';
    private $entities = null;

    static function getTable($classname = null){
        return "glpi_users";
    }

    function getLinkURL() {
        global $CFG_GLPI;
        
        if (!isset($this->fields['id'])) {
            return '';
        }

        $link_item = $this->getFormURL();
        
        $link  = $link_item;
        $link .= (strpos($link,'?') ? '&amp;':'?').'id=' . $this->fields['id'];
        $link .= ($this->isTemplate() ? "&amp;withtemplate=1" : "");

        return $link;
    }

    static function getFormURL($full = false) {
        return PluginServicesToolbox::getItemTypeFormURL(get_called_class(), $full);
    }

    static function getSearchURL($full = true) {
        return PluginServicesToolbox::getItemTypeSearchURL(get_called_class(), $full);
    }

    static function canView() {
        return Session::haveRight(self::$rightname, READ);
    }

    static function canCreate() {
        return Session::haveRight(self::$rightname, CREATE);
    }

    static function canUpdate() {
        return Session::haveRight(self::$rightname, UPDATE);
    }

    static function getType() {
        return "PluginServicesUser";
    }

    static function getClassName() {
        return get_called_class();
    }

    static function getTypeName($nb = 0) {
        return _n('User', 'Users', $nb);
    }

    
    static function getGroupsForUsers($item){
        PluginServicesGroup_User::showForUser($item);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        
        $relatedListabName = PluginServicesRelatedlist::tabNameForItem($item, $withtemplate);
        $tabNam = [
            self::createTabEntry(__("Log")) 
        ];
        $tab = array_merge($relatedListabName,  $tabNam);
        return $tab;
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $lastIndex = count(PluginServicesRelatedlist::tabNameForItem($item, $withtemplate));
        PluginServicesRelatedlist::tabcontent($item, $tabnum, $withtemplate);
        switch ($tabnum) {
            case $lastIndex:
                PluginServicesLog::showForitem($item, $withtemplate);
                break;
        }
    }
    

    /**
    * Get list of entities ids for current user.
    *
    * @return integer[]
    */
    private function getEntities() {
        //get user entities
        if ($this->entities == null) {
        $this->entities = Profile_User::getUserEntities($this->fields['id'], true);
        }
        return $this->entities;
    }


    function canViewItem() {
        if (Session::canViewAllEntities()
            || Session::haveAccessToOneOfEntities($this->getEntities())) {
            return true;
        }
        return false;
    }

    function canCreateItem() {
        // Will be created from form, with selected entity/profile
        if (isset($this->input['_profiles_id']) && ($this->input['_profiles_id'] > 0)
            && PluginServicesProfile::currentUserHaveMoreRightThan([$this->input['_profiles_id']])
            && isset($this->input['_entities_id'])
            && Session::haveAccessToEntity($this->input['_entities_id'])) {
            return true;
        }
        // Will be created with default value
        if (Session::haveAccessToEntity(0) // Access to root entity (required when no default profile)
            || (PluginServicesProfile::getDefault() > 0)) {
            return true;
        }

        if (($_SESSION['glpiactive_entity'] > 0)
            && (PluginServicesProfile::getDefault() == 0)) {
            echo "<div class='tab_cadre_fixe warning'>".
                    __('You must define a default profile to create a new user')."</div>";
        }

        return false;
    }

    function canUpdateItem() {
        $entities = PluginServicesProfile_User::getUserEntities($this->fields['id'], false);
        if (Session::canViewAllEntities()
            || Session::haveAccessToOneOfEntities($entities)) {
            return true;
        }
        return false;
    }

    function canDeleteItem() {
        if (Session::canViewAllEntities()
            || Session::haveAccessToAllOfEntities($this->getEntities())) {
            return true;
        }
        return false;
    }

    function canPurgeItem() {
        return $this->canDeleteItem();
    }
    
    function isEntityAssign() {
        // glpi_users.entities_id is only a pref.
        return false;
    }

    /**
     * Compute preferences for the current user mixing config and user data.
    *
    * @return void
    */
    function computePreferences() {
        global $CFG_GLPI;

        if (isset($this->fields['id'])) {
            foreach ($CFG_GLPI['user_pref_field'] as $f) {
                if (is_null($this->fields[$f])) {
                    $this->fields[$f] = $CFG_GLPI[$f];
                }
            }
        }
        /// Specific case for show_count_on_tabs : global config can forbid
        if ($CFG_GLPI['show_count_on_tabs'] == -1) {
            $this->fields['show_count_on_tabs'] = 0;
        }
    }

    static function getUsersTickets($item){
        PluginServicesTicket::showListForItem($item);
    }

    function showTabsContent($options = []) {
        // for objects not in table like central
        if (isset($this->fields['id'])) {
            $ID = $this->fields['id'];
        } else {
            if (isset($options['id'])) {
                $ID = $options['id'];
            } else {
                $ID = 0;
            }
        }
    
        $target         = $_SERVER['PHP_SELF'];
        $extraparamhtml = "";
        $withtemplate   = "";
        if (is_array($options) && count($options)) {
        if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
        }
        $cleaned_options = $options;
        if (isset($cleaned_options['id'])) {
            unset($cleaned_options['id']);
        }
        if (isset($cleaned_options['stock_image'])) {
            unset($cleaned_options['stock_image']);
        }
        if ($this instanceof CommonITILObject && $this->isNewItem()) {
            $this->input = $cleaned_options;
            $this->saveInput();
            // $extraparamhtml can be tool long in case of ticket with content
            // (passed in GET in ajax request)
            unset($cleaned_options['content']);
        }
    
        // prevent double sanitize, because the includes.php sanitize all data
        $cleaned_options = Toolbox::stripslashes_deep($cleaned_options);
    
        $extraparamhtml = "&amp;".Toolbox::append_params($cleaned_options, '&amp;');
        }
        echo "<div style='width:100%;' class='glpi_tabs ".($this->isNewID($ID)?"new_form_tabs":"")."'>";
        echo "<div id='tabspanel' class='center-h'></div>";
        $onglets     = $this->defineAllTabsFago($options);
        $display_all = false;
        if (isset($onglets['no_all_tab'])) {
        $display_all = false;
        unset($onglets['no_all_tab']);
        }
    
        if (count($onglets)) {
        $tabpage = $this->getTabsURL();
        $tabs    = [];
    
        foreach ($onglets as $key => $val) {
            $tabs[$key] = ['title'  => $val,
                                'url'    => $tabpage,
                                'params' => "_target=$target&amp;_itemtype=".$this->getType().
                                            "&amp;_glpi_tab=$key&amp;id=$ID$extraparamhtml"];
        }
    
        // Not all tab for templates and if only 1 tab
        if ($display_all
            && empty($withtemplate)
            && (count($tabs) > 1)) {
            $tabs[-1] = ['title'  => __('All'),
                                'url'    => $tabpage,
                                'params' => "_target=$target&amp;_itemtype=".$this->getType().
                                            "&amp;_glpi_tab=-1&amp;id=$ID$extraparamhtml"];
        }
    
        PluginServicesAjax::createTabs('tabspanel', 'tabcontent', $tabs, $this->getType(), $ID,
                            "horizontal", $options);
        }
        echo "</div>";
    }

    function defineAllTabsFago($options = []) {
        global $CFG_GLPI;

        $onglets = [];
        // Object with class with 'addtabon' attribute
        if ((isset(self::$othertabs[$this->getType()])
            && !$this->isNewItem())) {
                
        foreach (self::$othertabs[$this->getType()] as $typetab) {
            $this->addStandardTab($typetab, $onglets, $options);
        }
        }

        $class = $this->getType();
        return $onglets;
    }

    static function dropdown($options = []) {
        return PluginServicesDropdown::show('User', $options);
    }

    static function dropdownMultiple($select_name, $options=[]) {
        $options['multiple'] = true;
        $users = [];
        $user = new User();
        foreach ($user->find()  as $row) {
            $name = ( empty($row['name']) ) ? $row['id'] : $row['name'] ;
            $users[$row['id']] = $name;
        }
        return PluginServicesDropdown::showFromArray($select_name, $users, $options);
    }

    /**
    * Get all groups where the current user have delegating.
    *
    * @since 0.83
    *
    * @param integer|string $entities_id ID of the entity to restrict
    *
    * @return integer[]
    */
    static function getDelegateGroupsForUser($entities_id = '') {
        global $DB;

        $iterator = $DB->request([
        'SELECT'          => 'glpi_groups_users.groups_id',
        'DISTINCT'        => true,
        'FROM'            => 'glpi_groups_users',
        'INNER JOIN'      => [
            'glpi_groups'  => [
                'FKEY'   => [
                    'glpi_groups_users'  => 'groups_id',
                    'glpi_groups'        => 'id'
                ]
            ]
        ],
        'WHERE'           => [
            'glpi_groups_users.users_id'        => Session::getLoginUserID(),
            'glpi_groups_users.is_userdelegate' => 1
        ] + getEntitiesRestrictCriteria('glpi_groups', '', $entities_id, 1)
        ]);

        $groups = [];
        while ($data = $iterator->next()) {
        $groups[$data['groups_id']] = $data['groups_id'];
        }
        return $groups;
    }

    /**
    * Execute the query to select box with all glpi users where select key = name
    *
    * Internaly used by showGroup_Users, dropdownUsers and ajax/getDropdownUsers.php
    *
    * @param boolean         $count            true if execute an count(*) (true by default)
    * @param string|string[] $right            limit user who have specific right (default 'all')
    * @param integer         $entity_restrict  Restrict to a defined entity (default -1)
    * @param integer         $value            default value (default 0)
    * @param integer[]       $used             Already used items ID: not to display in dropdown
    * @param string          $search           pattern (default '')
    * @param integer         $start            start LIMIT value (default 0)
    * @param integer         $limit            limit LIMIT value (default -1 no limit)
    * @param boolean         $inactive_deleted true to retreive also inactive or deleted users
    *
    * @return mysqli_result|boolean
    */
    static function getSqlSearchResult ($count = true, $right = "all", $entity_restrict = -1, $value = 0,
                                        array $used = [], $search = '', $start = 0, $limit = -1,
                                        $inactive_deleted = 0) {
        global $DB;

        // No entity define : use active ones
        if ($entity_restrict < 0) {
            $entity_restrict = $_SESSION["glpiactiveentities"];
        }

        $joinprofile      = false;
        $joinprofileright = false;
        $WHERE = [];

        switch ($right) {
            case "interface" :
            $joinprofile = true;
            $WHERE = [
                'glpi_profiles.interface' => 'central'
            ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1);
            break;

            case "id" :
            $WHERE = ['glpi_users.id' => Session::getLoginUserID()];
            break;

            case "delegate" :
            $groups = self::getDelegateGroupsForUser($entity_restrict);
            $users  = [];
            if (count($groups)) {
                $iterator = $DB->request([
                    'SELECT'    => 'glpi_users.id',
                    'FROM'      => 'glpi_groups_users',
                    'LEFT JOIN' => [
                        'glpi_users'   => [
                        'FKEY'   => [
                            'glpi_groups_users'  => 'users_id',
                            'glpi_users'         => 'id'
                        ]
                        ]
                    ],
                    'WHERE'     => [
                        'glpi_groups_users.groups_id' => $groups,
                        'glpi_groups_users.users_id'  => ['<>', Session::getLoginUserID()]
                    ]
                ]);
                while ($data = $iterator->next()) {
                        $users[$data["id"]] = $data["id"];
                }
            }
            // Add me to users list for central
            if (Session::getCurrentInterface() == 'central') {
                $users[Session::getLoginUserID()] = Session::getLoginUserID();
            }

            if (count($users)) {
                $WHERE = ['glpi_users.id' => $users];
            }
            break;

            case "groups" :
            $groups = [];
            if (isset($_SESSION['glpigroups'])) {
                $groups = $_SESSION['glpigroups'];
            }
            $users  = [];
            if (count($groups)) {
                $iterator = $DB->request([
                    'SELECT'    => 'glpi_users.id',
                    'FROM'      => 'glpi_groups_users',
                    'LEFT JOIN' => [
                        'glpi_users'   => [
                        'FKEY'   => [
                            'glpi_groups_users'  => 'users_id',
                            'glpi_users'         => 'id'
                        ]
                        ]
                    ],
                    'WHERE'     => [
                        'glpi_groups_users.groups_id' => $groups,
                        'glpi_groups_users.users_id'  => ['<>', Session::getLoginUserID()]
                    ]
                ]);
                while ($data = $iterator->next()) {
                    $users[$data["id"]] = $data["id"];
                }
            }
            // Add me to users list for central
            if (Session::getCurrentInterface() == 'central') {
                $users[Session::getLoginUserID()] = Session::getLoginUserID();
            }

            if (count($users)) {
                $WHERE = ['glpi_users.id' => $users];
            }

            break;

            case "all" :
            $WHERE = [
                'glpi_users.id' => ['>', 0],
                'OR' => [
                    'glpi_profiles_users.entities_id' => null
                ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
            ];
            break;

            default :
            $joinprofile = true;
            $joinprofileright = true;
            if (!is_array($right)) {
                $right = [$right];
            }
            $forcecentral = true;

            $ORWHERE = [];
            foreach ($right as $r) {
                switch ($r) {
                    case  'own_ticket' :
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'     => 'ticket',
                            'glpi_profilerights.rights'   => ['&', Ticket::OWN]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];
                        break;

                    case 'create_ticket_validate' :
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'  => 'ticketvalidation',
                            'OR'                       => [
                                ['glpi_profilerights.rights'   => ['&', TicketValidation::CREATEREQUEST]],
                                ['glpi_profilerights.rights'   => ['&', TicketValidation::CREATEINCIDENT]]
                            ]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];
                        $forcecentral = false;
                        break;

                    case 'validate_request' :
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'     => 'ticketvalidation',
                            'glpi_profilerights.rights'   => ['&', TicketValidation::VALIDATEREQUEST]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];
                        $forcecentral = false;
                        break;

                    case 'validate_incident' :
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'     => 'ticketvalidation',
                            'glpi_profilerights.rights'   => ['&', TicketValidation::VALIDATEINCIDENT]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];
                        $forcecentral = false;
                        break;

                    case 'validate' :
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'     => 'changevalidation',
                            'glpi_profilerights.rights'   => ['&', ChangeValidation::VALIDATE]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];
                        break;

                    case 'create_validate' :
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'     => 'changevalidation',
                            'glpi_profilerights.rights'   => ['&', ChangeValidation::CREATE]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];
                        break;

                    case 'see_project' :
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'     => 'project',
                            'glpi_profilerights.rights'   => ['&', Project::READMY]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];
                        break;

                    case 'faq' :
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'     => 'knowbase',
                            'glpi_profilerights.rights'   => ['&', KnowbaseItem::READFAQ]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];

                    default :
                        // Check read or active for rights
                        $ORWHERE[] = [
                        [
                            'glpi_profilerights.name'     => $r,
                            'glpi_profilerights.rights'   => [
                                '&',
                                READ | CREATE | UPDATE | DELETE | PURGE
                            ]
                        ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_restrict, 1)
                        ];
                }
                if (in_array($r, Profile::$helpdesk_rights)) {
                    $forcecentral = false;
                }
            }

            if (count($ORWHERE)) {
                $WHERE[] = ['OR' => $ORWHERE];
            }

            if ($forcecentral) {
                $WHERE['glpi_profiles.interface'] = 'central';
            }
        }

        if (!$inactive_deleted) {
            $WHERE = array_merge(
            $WHERE, [
                'glpi_users.is_deleted' => 0,
                'glpi_users.is_active'  => 1,
                [
                    'OR' => [
                        ['glpi_users.begin_date' => null],
                        ['glpi_users.begin_date' => ['<', new QueryExpression('NOW()')]]
                    ]
                ],
                [
                    'OR' => [
                        ['glpi_users.end_date' => null],
                        ['glpi_users.end_date' => ['>', new QueryExpression('NOW()')]]
                    ]
                ]

            ]
            );
        }

        if ((is_numeric($value) && $value)
            || count($used)) {

            $WHERE[] = [
            'NOT' => [
                'glpi_users.id' => $used
            ]
            ];
        }

        $criteria = [
            'FROM'            => 'glpi_users',
            'LEFT JOIN'       => [
            'glpi_useremails'       => [
                'ON' => [
                    'glpi_useremails' => 'users_id',
                    'glpi_users'      => 'id'
                ]
            ],
            'glpi_profiles_users'   => [
                'ON' => [
                    'glpi_profiles_users'   => 'users_id',
                    'glpi_users'            => 'id'
                ]
            ]
            ]
        ];
        if ($count) {
            $criteria['SELECT'] = ['COUNT' => 'glpi_users.id AS CPT'];
            $criteria['DISTINCT'] = true;
        } else {
            $criteria['SELECT'] = 'glpi_users.*';
            $criteria['DISTINCT'] = true;
        }

        if ($joinprofile) {
            $criteria['LEFT JOIN']['glpi_profiles'] = [
            'ON' => [
                'glpi_profiles_users'   => 'profiles_id',
                'glpi_profiles'         => 'id'
            ]
            ];
            if ($joinprofileright) {
            $criteria['LEFT JOIN']['glpi_profilerights'] = [
                'ON' => [
                    'glpi_profilerights' => 'profiles_id',
                    'glpi_profiles'      => 'id'
                ]
            ];
            }
        }

        if (!$count) {
            if ((strlen($search) > 0)) {
            $txt_search = Search::makeTextSearchValue($search);

            $firstname_field = $DB->quoteName(self::getTableField('firstname'));
            $realname_field = $DB->quoteName(self::getTableField('realname'));
            $fields = $_SESSION["glpinames_format"] == self::FIRSTNAME_BEFORE
                ? [$firstname_field, $realname_field]
                : [$realname_field, $firstname_field];

            $concat = new \QueryExpression(
                'CONCAT(' . implode(',' . $DB->quoteValue(' ') . ',', $fields) . ')'
                . ' LIKE ' . $DB->quoteValue($txt_search)
            );
            $WHERE[] = [
                'OR' => [
                    'glpi_users.name'       => ['LIKE', $txt_search],
                    'glpi_users.realname'   => ['LIKE', $txt_search],
                    'glpi_users.firstname'  => ['LIKE', $txt_search],
                    'glpi_users.phone'      => ['LIKE', $txt_search],
                    'glpi_useremails.email' => ['LIKE', $txt_search],
                    $concat
                ]
            ];
            }

            if ($_SESSION["glpinames_format"] == self::FIRSTNAME_BEFORE) {
            $criteria['ORDERBY'] = [
                'glpi_users.firstname',
                'glpi_users.realname',
                'glpi_users.name'
            ];
            } else {
            $criteria['ORDERBY'] = [
                'glpi_users.realname',
                'glpi_users.firstname',
                'glpi_users.name'
            ];
            }

            if ($limit > 0) {
            $criteria['LIMIT'] = $limit;
            $criteria['START'] = $start;
            }
        }
        $criteria['WHERE'] = $WHERE;
        return $DB->request($criteria);
    }

    /**
    * Change auth method for given users.
    *
    * @param integer[] $IDs      IDs of users
    * @param integer   $authtype Auth type (see Auth constants)
    * @param integer   $server   ID of auth server
    *
    * @return boolean
    */
    static function changeAuthMethod(array $IDs = [], $authtype = 1, $server = -1) {
        global $DB;

        if (!Session::haveRight(self::$rightname, self::UPDATEAUTHENT)) {
        return false;
        }

        if (!empty($IDs)
            && in_array($authtype, [Auth::DB_GLPI, Auth::LDAP, Auth::MAIL, Auth::EXTERNAL])) {

        $result = $DB->update(
            self::getTable(), [
                'authtype'        => $authtype,
                'auths_id'        => $server,
                'password'        => '',
                'is_deleted_ldap' => 0
            ], [
                'id' => $IDs
            ]
        );
        if ($result) {
            foreach ($IDs as $ID) {
                $changes = [
                    0,
                    '',
                    addslashes(
                    sprintf(
                        __('%1$s: %2$s'),
                        __('Update authentification method to'),
                        Auth::getMethodName($authtype, $server)
                    )
                    )
                ];
                Log::history($ID, __CLASS__, $changes, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
            }

            return true;
        }
        }
        return false;
    }

    /**
    * Show simple add user form for external auth.
    *
    * @return void|boolean false if user does not have rights to import users from external sources,
    *    print form otherwise
    */
    static function showAddExtAuthForm() {

        if (!Session::haveRight("user", self::IMPORTEXTAUTHUSERS)) {
        return false;
        }

        echo "<div class='center'>\n";
        echo "<form method='post' action='".Toolbox::getItemTypeFormURL('User')."'>\n";

        echo "<table class='tab_cadre'>\n";
        echo "<tr><th colspan='4'>".__('Automatically add a user of an external source')."</th></tr>\n";

        echo "<tr class='tab_bg_1'><td>".__('Login')."</td>\n";
        echo "<td><input type='text' name='login'></td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='tab_bg_2 center' colspan='2'>\n";
        echo "<input type='submit' name='add_ext_auth_ldap' value=\"".__s('Import from directories')."\"
            class='submit'>\n";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='tab_bg_2 center' colspan='2'>\n";
        echo "<input type='submit' name='add_ext_auth_simple' value=\"".__s('Import from other sources')."\"
            class='submit'>\n";
        echo "</td></tr>\n";

        echo "</table>";
        PluginServicesHtml::closeForm();
        echo "</div>\n";
    }
    
    function postForm($post) {
        if (empty($_GET["id"])) {
            $_GET["id"] = "";
        }    
        $user      = new self();
        $groupuser = new PluginServicesGroup_User();
        if (empty($_GET["id"]) && isset($_GET["name"])) {
            $user->getFromDBbyName($_GET["name"]);
            PluginServicesHtml::redirect($user->getFormURLWithID($user->fields['id']));
        }
        
        if (empty($_GET["name"])) {
            $_GET["name"] = "";
        }
        
        if (isset($_GET['getvcard'])) {
            if (empty($_GET["id"])) {
                PluginServicesHtml::redirect($CFG_GLPI["root_doc"]."/front/user.php");
            }
            $user->check($_GET['id'], READ);
            $user->generateVcard();
        
        } else if (isset($_POST["add"])) {
            $user->check(-1, CREATE, $_POST);
        
            if (($newID = $user->add($_POST))) {
                Event::log($newID, "users", 4, "setup",
                            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));

                PluginServicesFagoUtils::returnResponse($newID);
            }
            PluginServicesFagoUtils::returnResponse();
        
        } else if (isset($_POST["delete"])) {
            $user->check($_POST['id'], DELETE);
            $user->delete($_POST);
            Event::log($_POST["id"], "users", 4, "setup",
                        //TRANS: %s is the user login
                        sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
            $user->redirectToList();
        
        } else if (isset($_POST["restore"])) {
            $user->check($_POST['id'], DELETE);
            $user->restore($_POST);
            Event::log($_POST["id"], "users", 4, "setup",
                        //TRANS: %s is the user login
                        sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
            $user->redirectToList();
        
        } else if (isset($_POST["purge"])) {
            $user->check($_POST['id'], PURGE);
            $user->delete($_POST, 1);
            Event::log($_POST["id"], "users", 4, "setup",
                        sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
            $user->redirectToList();
        
        } else if (isset($_POST["force_ldap_resynch"])) {
            Session::checkRight('user', PluginServicesUser::UPDATEAUTHENT);
            
            $user->getFromDB($_POST["id"]);
            AuthLDAP::forceOneUserSynchronization($user);
            PluginServicesHtml::back();
        
        } else if (isset($_POST["update"])) {
            $user->check($_POST['id'], UPDATE);
            $user->update($_POST);
            Event::log($_POST['id'], "users", 5, "setup",
                        //TRANS: %s is the user login
                        sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
                        
            PluginServicesFagoUtils::returnResponse();
        
        } else if (isset($_POST["addgroup"])) {
            $groupuser->check(-1, CREATE, $_POST);
            if ($groupuser->add($_POST)) {
                Event::log($_POST["users_id"], "users", 4, "setup",
                            //TRANS: %s is the user login
                            sprintf(__('%s adds a user to a group'), $_SESSION["glpiname"]));
            }
            PluginServicesHtml::back();
        
        } else if (isset($_POST["deletegroup"])) {
            if (count($_POST["item"])) {
                foreach (array_keys($_POST["item"]) as $key) {
                    if ($groupuser->can($key, DELETE)) {
                        $groupuser->delete(['id' => $key]);
                    }
                }
            }
            Event::log($_POST["users_id"], "users", 4, "setup",
                        //TRANS: %s is the user login
                        sprintf(__('%s deletes users from a group'), $_SESSION["glpiname"]));
            PluginServicesHtml::back();
        
        } else if (isset($_POST["change_auth_method"])) {
            Session::checkRight('user', PluginServicesUser::UPDATEAUTHENT);
            
            if (isset($_POST["auths_id"])) {
                self::changeAuthMethod([$_POST["id"]], $_POST["authtype"], $_POST["auths_id"]);
            }
            PluginServicesHtml::back();
        
        } else if (isset($_POST['language']) && !GLPI_DEMO_MODE) {
            if (Session::getLoginUserID()) {
                $user->update(
                    [
                        'id'        => Session::getLoginUserID(),
                        'language'  => $_POST['language']
                    ]
                );
            } else {
                $_SESSION["glpilanguage"] = $_POST['language'];
            }
            Session::addMessageAfterRedirect(__('Lang has been changed!'));
            PluginServicesHtml::back();
        
        } else if (isset($_POST['impersonate']) && $_POST['impersonate']) {
        
            if (!Session::startImpersonating($_POST['id'])) {
                Session::addMessageAfterRedirect(__('Unable to impersonate user'), false, ERROR);
                PluginServicesFagoUtils::returnResponse('', 500);
            }
            
            PluginServicesFagoUtils::returnResponse($_POST['id']);
        
        } else if (isset($_POST['impersonate']) && !$_POST['impersonate']) {
        
            $impersonated_user_id = Session::getLoginUserID();
            
            if (!Session::stopImpersonating()) {
                Session::addMessageAfterRedirect(__('Unable to stop impersonating user'), false, ERROR);
                PluginServicesFagoUtils::returnResponse('', 400);
            }
            
            PluginServicesFagoUtils::returnResponse($impersonated_user_id);        
        } else {
            if (isset($_GET["ext_auth"])) {
                self::showAddExtAuthForm();
            } else if (isset($_POST['add_ext_auth_ldap'])) {
                Session::checkRight("user", self::IMPORTEXTAUTHUSERS);
            
                if (isset($_POST['login']) && !empty($_POST['login'])) {
                    AuthLDAP::importUserFromServers(['name' => $_POST['login']]);
                }
                PluginServicesHtml::back();
            } else if (isset($_POST['add_ext_auth_simple'])) {
                if (isset($_POST['login']) && !empty($_POST['login'])) {
                    Session::checkRight("user", self::IMPORTEXTAUTHUSERS);
                    $input = ['name'     => $_POST['login'],
                                '_extauth' => 1,
                                'add'      => 1];
                    $user->check(-1, CREATE, $input);
                    $newID = $user->add($input);
                    Event::log($newID, "users", 4, "setup",
                            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"],
                                    $_POST["login"]));
                }
                PluginServicesHtml::back();
            } else {
                Session::checkRight("user", READ);
                PluginServicesHtml::back();
            
            }
        }
            
    }

    /**
     * Check right on an item with block
    *
    * @param integer $ID    ID of the item (-1 if new item)
    * @param mixed   $right Right to check : r / w / recursive
    * @param array   $input array of input data (used for adding item) (default NULL)
    *
    * @return void
    **/
    function check($ID, $right, array &$input = null) {
        // Check item exists
        if (!$this->isNewID($ID)
            && (!isset($this->fields['id']) || $this->fields['id'] != $ID)
            && !$this->getFromDB($ID)) {
            // Gestion timeout session
            Session::redirectIfNotLoggedIn();
            PluginServicesHtml::displayNotFoundError();

        } else {
            if (!$this->can($ID, $right, $input)) {
                // Gestion timeout session
                Session::redirectIfNotLoggedIn();
                PluginServicesHtml::displayRightError();
            }
        }
    }
    
    function showForm($ID, array $options = []) {
        global $CFG_GLPI, $DB;
        // Affiche un formulaire User
        if (($ID != Session::getLoginUserID()) && !User::canView()) {
            PluginServicesHtml::displayRightError();
            return false;
        }

        $user = new User();
        if(!empty($ID)){
            
            $user->getFromDB($ID);
            $this->fields = $user->fields;
            if (!$user->can($ID, READ)) {
                PluginServicesHtml::displayRightError();
                return false;
            }
        }
        $this->initForm($ID, $options);
    
        $ismyself = $ID == Session::getLoginUserID();
        $higherrights = $this->currentUserHaveMoreRightThan($ID);
        if ($ID) {
        $caneditpassword = $higherrights || ($ismyself && Session::haveRight('password_update', 1));
        } else {
        // can edit on creation form
        $caneditpassword = true;
        }

        if($ID){
            $extauth = !(($this->fields["authtype"] == Auth::DB_GLPI)
                || (($this->fields["authtype"] == Auth::NOT_YET_AUTHENTIFIED)
                    && !empty($this->fields["password"])));
        }else{
            $extauth = '';
        }

        $formtitle = $this->getTypeName(1);

        $all_fields = [
            [
                'label'=> 'First name',
                'name'=> 'firstname',
                'type'=> 'text'
            ], [
                'label'=> 'Surname',
                'name'=> 'realname',
                'type'=> 'text'
            ],[
                'label'=> 'Login',
                'name'=> 'name',
                'type'=> 'text',
                'mandatory'=> true
            ],[
                'label'=> 'Phone',
                'name'=> 'phone',
                'type'=> 'number'
            ],[
                'label'=>  'Email',
                'type'=> 'function',
                'name' => 'dropdownEmail',
                'params'=> [
                    'name'  => '_profiles_id'
                ],
            ],[
                'label'=> 'Category',
                'name'=> 'usercategories_id',
                'type'=> 'dropdown'
            ],[
                'label'=> 'Title',
                'name'=> 'usertitles_id',
                'type'=> 'dropdown'
            ],[
                'label'=> Profile::getTypeName(1),
                'type'=> 'function',
                'name' => 'dropdownUnder',
                'itemtype'=> 'PluginServicesProfile',
                'params'=> [
                    'name'  => '_profiles_id',
                    'rand'  =>mt_rand(),
                    'value' => Profile::getDefault()
                ],
                'is_new_id' => true
            ],[
                'label'=> 'Default profile',
                'type'=> 'function',
                'name'=> 'defautProfile',
                'params'=> [
                    'name' =>  'profiles_id',
                    'value'    => $this->fields["profiles_id"],
                    'rand'  => mt_rand(),
                    'display_emptychoice' => true
                ],
                'is_new_id' => false
            ],[
                'label'=> Entity::getTypeName(1),
                'name'=> 'entities_id',
                'type'=> 'dropdown',
                'is_new_id' => true
            ],[
                'label'=> 'Default entity',
                'name'=> 'entities_id',
                'entity' => $this->getEntities(),
                'type'=> 'dropdown',
                'is_new_id' => false
            ],[
                'label'=> 'Password',
                'name'=> 'password',
                'type'=> 'password',
            ],[
                'label'=> 'Password confirmation',
                'name'=> 'password2',
                'type'=> 'password'
            ],[
                'label'=> 'Responsible',
                'name'=> 'users_id_supervisor',
                'type'=> 'dropdown',
                'is_new_id' => false
            ],[
                'label'=> 'Société',
                'name'=> 'dropdownCompany',
                'itemtype'=> "PluginServicesCompany",
                'type'=> 'function',
                'params' => [
                    'name'=> 'companies_id',
                    'value'=> $this->fields['companies_id'],
                ],
                'events' => [
                    'type'  => ['change'],
                    'input_type' => 'dropdown',
                    'action' => 'setInputData',
                    'input_cible' => 'departements_id',
                    'url' =>   $CFG_GLPI["root_doc"]."/ajax/dropdownCompanyDepartement.php",
                    'params' => [
                        'rand' => mt_rand(),
                        'right' =>  'all',
                        'display_emptychoice' =>  true,
                    ]
                ]
            ],[
                'label'=> 'Département',
                'name'=> 'dropdownDepartement',
                'itemtype'=> "PluginServicesDepartement",
                'type'=> 'function',
                'params' => [
                    'name'=> 'departements_id',
                    'value'=> $this->fields['departements_id'],
                ]
            ],[
                'label'=> 'Active',
                'name'=> 'is_active',
                'type'=> 'boolean'
            ],[
                'name'=> 'comment',
                'type'=> 'textarea',
                'label'=> 'Description',
                'full'=> true
            ]
        ];
        PluginServicesHtml::generateForm($ID, $this, $options, $all_fields);
        $profile = PluginServicesProfile_User::getProfileByUser($ID);
        $homeurl = (isset( $profile['interface'])&&  $profile['interface']  == 'helpdesk') ? $CFG_GLPI['root_doc'].'/portal' : $CFG_GLPI['root_doc'].'/home';
        $target = $this->getFormURL();
        $id = ($ID) ? $ID : -1 ;
        $jsScript = '
            var tokenurl = "/ajax/generatetoken.php";
            var request;

            function impersonate (event){
                event.preventDefault();

                if (request) { // Abort any pending request
                    request.abort();
                }

                $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
                    var data = {
                        impersonate: 1,
                        id: '.$id.',
                        _glpi_csrf_token: token
                    }

                    request = $.ajax({
                        url: "'.$target.'",
                        type: "post",
                        data: data
                    });

                    request.done(function (response, textStatus, jqXHR){
                        window.location.href = "'.$homeurl.'";
                        var res = JSON.parse(response);
                        showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
                    });

                    request.fail(function (jqXHR, textStatus, errorThrown){
                        console.error(jqXHR, textStatus, errorThrown);
                    });
                }); 
            }

            $( document ).ready(function() {
                var form = $("#pluginservicesuserform");
                form.validate({
                    rules: {
                        name : {
                            required: true
                        }
                    }
                });
                $("#addForm").click(function(e){
                    event.preventDefault();
                    if (!form.valid()) { // stop script where form is invalid
                        return false;
                    }
                    if (request) { // Abort any pending request
                        request.abort();
                    }

                    $("button[name=add]").addClass("m-loader m-loader--light m-loader--right"); // add loader
                    $("button[name=add]").prop("disabled", true);

                    var serializedData = form.serializeArray();
                    $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
                        serializedData[serializedData.length] = { name: "add", value:"add" };
                        serializedData[serializedData.length] = {  name: "_glpi_csrf_token", value:token };
                        request = $.ajax({
                            url: "'.$target.'",
                            type: "post",
                            data: serializedData
                        });

                        request.done(function (response, textStatus, jqXHR){
                            var res = JSON.parse(response);
                            loadPage("'.$target.'?id="+res.response);
                            showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
                        });

                        request.fail(function (jqXHR, textStatus, errorThrown){
                            console.error(jqXHR, textStatus, errorThrown);

                            $("button[name=add]").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                            $("button[name=add]").prop("disabled", false);
                        });
                    }); 
                })


                $("#editForm").click(function(e){
                    event.preventDefault();
        
                    if (!form.valid()) { // stop script where form is invalid
                        return false;
                    }
                    
                    if (request) { // Abort any pending request
                        request.abort();
                    }

                    $("#editForm").addClass("m-loader m-loader--light m-loader--right"); // add loader
                    $("#editForm").prop("disabled", true);

                    var serializedData = form.serializeArray();

                    $.ajax({ url: tokenurl, type: "GET", datatype: "json"}).done(function (token){ 
                        serializedData[serializedData.length] = { name: "update", value:"update" };
                        serializedData[serializedData.length] = {  name: "_glpi_csrf_token", value:token };
                        request = $.ajax({
                            url: "'.$target.'",
                            type: "post",
                            data: serializedData
                        });

                        request.done(function (response, textStatus, jqXHR){
                            var res = JSON.parse(response);
                            console.log( Object.values(res.message)[0]);
                            showAlertMessage(Object.keys(res.message)[0], Object.values(res.message)[0]);
                            $("#editForm").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                            $("#editForm").prop("disabled", false);
                        });

                        request.fail(function (jqXHR, textStatus, errorThrown){
                            removeSubmitFormLoader("add");
                            console.error(jqXHR, textStatus, errorThrown);

                            $("#editForm").removeClass("m-loader m-loader--light m-loader--right"); // remove loader
                            $("#editForm").prop("disabled", false);
                        });
                    }); 
                })
            })  
        ';
        echo Html::scriptBlock($jsScript);
        return true;

    }

    function defautProfile(array $options = []){
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $data = PluginServicesDropdown::getDropdownArrayNames('glpi_profiles', Profile_User::getUserProfiles($this->fields['id']));
        return PluginServicesDropdown::showFromArray($options['name'], $data, $p);
    }

    function dropdownEmail(){
        return  PluginServicesUserEmail::showForUser($this);
    }

    public function showList($itemtype, $params){
        PluginServicesHtml::showList($itemtype, $params);
    }

    function rawSearchOptions() {
        // forcegroup by on name set force group by for all items
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Login'),
            'datatype'           => 'itemlink',
            'forcegroupby'       => true,
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => $this->getTable(),
            'field'              => 'realname',
            'name'               => __('Last name'),
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'firstname',
            'name'               => __('First name'),
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => 'glpi_useremails',
            'field'              => 'email',
            'name'               => _n('Email', 'Emails', Session::getPluralNumber()),
            'datatype'           => 'email',
            'joinparams'         => [
                'jointype'           => 'child'
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '150',
            'table'              => $this->getTable(),
            'field'              => 'picture',
            'name'               => __('Picture'),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '28',
            'table'              => $this->getTable(),
            'field'              => 'sync_field',
            'name'               => __('Synchronization field'),
            'massiveaction'      => false,
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'phone',
            'name'               => Phone::getTypeName(1),
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'phone2',
            'name'               => __('Phone 2'),
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'mobile',
            'name'               => __('Mobile phone'),
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => PluginServicesGroup::getTypeName(Session::getPluralNumber()),
            'forcegroupby'       => true,
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_users',
                    'joinparams'         => [
                    'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'last_login',
            'name'               => __('Last login'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $this->getTable(),
            'field'              => 'authtype',
            'name'               => __('Authentication'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => [
                '0'                  => 'auths_id'
            ]
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => 'glpi_authldaps',
            'field'              => 'name',
            'linkfield'          => 'auths_id',
            'name'               => __('LDAP directory for authentication'),
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'          => 'AND REFTABLE.`authtype` = ' . Auth::LDAP
            ],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => 'glpi_authmails',
            'field'              => 'name',
            'linkfield'          => 'auths_id',
            'name'               => __('Email server for authentication'),
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'          => 'AND REFTABLE.`authtype` = ' . Auth::MAIL
            ],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => $this->getTable(),
            'field'              => 'language',
            'name'               => __('Language'),
            'datatype'           => 'language',
            'display_emptychoice' => true,
            'emptylabel'         => 'Default value'
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => 'glpi_profiles',
            'field'              => 'name',
            'name'               => sprintf(__('%1$s (%2$s)'), Profile::getTypeName(Session::getPluralNumber()),
                                                    Entity::getTypeName(1)),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_profiles_users',
                    'joinparams'         => [
                    'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => $this->getTable(),
            'field'              => 'user_dn',
            'name'               => __('User DN'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => $this->getTable(),
            'field'              => 'registration_number',
            'name'               => __('Administrative number'),
            'datatype'           => 'string',
            'autocomplete'       => true,
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => $this->getTable(),
            'field'              => 'date_sync',
            'datatype'           => 'datetime',
            'name'               => __('Last synchronization'),
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => $this->getTable(),
            'field'              => 'is_deleted_ldap',
            'name'               => __('Deleted user in LDAP directory'),
            'datatype'           => 'bool',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'linkfield'          => 'entities_id',
            'field'              => 'completename',
            'name'               => sprintf(__('%1$s (%2$s)'), Entity::getTypeName(Session::getPluralNumber()),
                                                    Profile::getTypeName(1)),
            'forcegroupby'       => true,
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_profiles_users',
                    'joinparams'         => [
                    'jointype'           => 'child'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '81',
            'table'              => 'glpi_usertitles',
            'field'              => 'name',
            'name'               => __('Title'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '82',
            'table'              => 'glpi_usercategories',
            'field'              => 'name',
            'name'               => __('Category'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '79',
            'table'              => 'glpi_profiles',
            'field'              => 'name',
            'name'               => __('Default profile'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '77',
            'table'              => 'glpi_entities',
            'field'              => 'name',
            'massiveaction'      => true,
            'name'               => __('Default entity'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '62',
            'table'              => $this->getTable(),
            'field'              => 'begin_date',
            'name'               => __('Begin date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '63',
            'table'              => $this->getTable(),
            'field'              => 'end_date',
            'name'               => __('End date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '99',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_supervisor',
            'name'               => __('Responsible'),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '100',
            'table'              => 'glpi_plugin_services_companies',
            'field'              => 'name',
            'linkfield'          => 'companies_id',
            'name'               => __('Société'),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];
        $tab[] = [
            'id'                 => '1000',
            'table'              => 'glpi_plugin_services_departements',
            'field'              => 'name',
            'linkfield'          => 'departements_id',
            'name'               => __('Département'),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];
        
        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        return $tab;
    }
    
    static function getGroupUserHaveRights(array $options = []) {
        $params = [
            'entity' => $_SESSION['glpiactive_entity'],
            'right' => 'own_ticket',
        ];

        $params['groups_id'] = 0;

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        $list       = [];
        $restrict   = [];

        $res = self::getSqlSearchResult(false, $params['right'], $params['entity']);
        while ($data = $res->next()) {
            $list[] = $data['id'];
        }
        if (count($list) > 0) {
            $restrict = ['glpi_users.id' => $list];
        }
        $users = Group_User::getGroupUsers($params['groups_id'], $restrict);

        return $users;
    }
}