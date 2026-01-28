<?php

/**
 * -------------------------------------------------------------------------
 * News plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of News.
 *
 * News is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * News is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with News. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2015-2023 by News plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/news
 * -------------------------------------------------------------------------
 */
use Glpi\Application\View\TemplateRenderer;

use function Safe\preg_match;
use function Safe\strtotime;

if (!defined('GLPI_ROOT')) {
    echo "Sorry. You can't access directly to this file";
    return;
}

class PluginNewsAlert extends CommonDBTM
{
    public static $rightname = 'plugin_news_alert';
    public $dohistory        = true;

    // Available templates
    public const GENERAL = 1;
    public const INFO    = 2;
    public const WARNING = 3;
    public const PROBLEM = 4;

    // Available sizes
    public const SMALL   = 'small';
    public const MEDIUM  = 'medium';
    public const BIG     = 'big';
    public const MAXIMUM = 'maximum';

    // Available icons
    public const SETTINGS       = 'settings';
    public const ALERT_CIRCLE   = 'alert-circle';
    public const ALERT_TRIANGLE = 'alert-triangle';
    public const ALERT_OCTAGON  = 'alert-octagon';

    // Available colors
    public const DARK   = 'dark';
    public const WHITE  = 'white';
    public const BLUE   = 'blue';
    public const CYAN   = 'cyan';
    public const INDIGO = 'indigo';
    public const PURPLE = 'purple';
    public const PINK   = 'pink';
    public const RED    = 'red';
    public const ORANGE = 'orange';
    public const YELLOW = 'yellow';
    public const LIME   = 'lime';

    public static function canDelete(): bool
    {
        return self::canPurge();
    }

    /**
     * Returns the type name with consideration of plural
     *
     * @param number $nb Number of item(s)
     * @return string Itemtype name
     */
    public static function getTypeName($nb = 0)
    {
        return __s('Alerts', 'news');
    }

    /**
     * @see CommonGLPI::defineTabs()
    **/
    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
           ->addStandardTab('PluginNewsAlert_Target', $ong, $options)
           ->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __s('Name', 'news'),
            'datatype'      => 'itemlink',
            'itemlink_type' => $this->getType(),
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => 2,
            'table'    => $this->getTable(),
            'field'    => 'date_start',
            'name'     => __s('Visibility start date', 'news'),
            'datatype' => 'date',
        ];

        $tab[] = [
            'id'       => 3,
            'table'    => $this->getTable(),
            'field'    => 'date_end',
            'name'     => __s('Visibility end date', 'news'),
            'datatype' => 'date',
        ];

        $tab[] = [
            'id'            => 4,
            'table'         => 'glpi_entities',
            'field'         => 'completename',
            'name'          => __s('Entity', 'news'),
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 5,
            'table'         => $this->getTable(),
            'field'         => 'is_recursive',
            'name'          => __s('Recursive', 'news'),
            'datatype'      => 'bool',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'               => 6,
            'table'            => PluginNewsAlert_Target::getTable(),
            'field'            => 'items_id',
            'name'             => PluginNewsAlert_Target::getTypename(),
            'datatype'         => 'specific',
            'forcegroupby'     => true,
            'joinparams'       => ['jointype' => 'child'],
            'additionalfields' => ['itemtype', 'all_items'],
        ];

        $tab[] = [
            'id'            => 7,
            'table'         => $this->getTable(),
            'field'         => 'is_close_allowed',
            'name'          => __s('Can close alert', 'news'),
            'datatype'      => 'bool',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 8,
            'table'         => $this->getTable(),
            'field'         => 'is_displayed_onlogin',
            'name'          => __s('Show on login page', 'news'),
            'datatype'      => 'bool',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 9,
            'table'         => $this->getTable(),
            'field'         => 'is_displayed_onhelpdesk',
            'name'          => __s('Show on helpdesk page', 'news'),
            'datatype'      => 'bool',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => 10,
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => __s('Active', 'news'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'            => 19,
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __s('Last update', 'news'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 121,
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __s('Creation date', 'news'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
    }

    public function post_updateItem($history = true)
    {
        // if close is not allowed update all user alerts to force display
        if (
            isset($this->input['is_close_allowed'])
            && !$this->input['is_close_allowed']
        ) {
            $alert_user = new PluginNewsAlert_User();
            //get all Alert_User for this alert where state is hidden
            $all_alert = $alert_user->find(
                [
                    'plugin_news_alerts_id' => $this->getID(),
                    'state'                 => PluginNewsAlert_User::HIDDEN,
                ],
            );
            foreach ($all_alert as $alert) {
                //update state to force display
                $alert_user->update(
                    [
                        'id'    => $alert['id'],
                        'state' => PluginNewsAlert_User::VISIBLE,
                    ],
                );
            }
        }
    }

    public static function findAllToNotify($params = [])
    {
        /** @var DBmysql $DB */
        global $DB;

        $p['show_only_login_alerts']    = false;
        $p['show_only_central_alerts']  = false;
        $p['show_hidden_alerts']        = false;
        $p['show_only_helpdesk_alerts'] = false;
        $p['entities_id']               = false;
        foreach ($params as $key => $value) {
            $p[$key] = $value;
        }

        $alerts   = [];
        $today    = date('Y-m-d H:i:s');
        $table    = self::getTable();
        $utable   = PluginNewsAlert_User::getTable();
        $ttable   = PluginNewsAlert_Target::getTable();
        $hidstate = PluginNewsAlert_User::HIDDEN;
        $users_id = $_SESSION['glpiID'] ?? -1;
        $group_u  = new Group_User();
        $fndgroup = [];
        if (isset($_SESSION['glpiID']) && $fndgroup_user = $group_u->find(['users_id' => $_SESSION['glpiID']])) {
            foreach ($fndgroup_user as $group) {
                $fndgroup[] = $group['groups_id'];
            }
        }

        if ($fndgroup === []) {
            $fndgroup = [-1];
        }

        // filters for query
        $targets_sql           = [];
        $login_sql             = [];
        $login_show_hidden_sql = ["{$utable}.id" => null];
        $entity_sql            = [];
        $show_helpdesk_sql     = [];
        $show_central_sql      = [];
        if (isset($_SESSION['glpiID']) && isset($_SESSION['glpiactiveprofile']['id'])) {
            $targets_sql = [
                'AND' => [
                    [
                        'OR' => [
                            [
                                'AND' => [
                                    "$ttable.itemtype" => 'Profile',
                                    'OR'               => [
                                        "$ttable.items_id"  => $_SESSION['glpiactiveprofile']['id'],
                                        "$ttable.all_items" => 1,
                                    ],
                                ],
                            ],
                            [
                                'AND' => [
                                    "$ttable.itemtype" => 'Group',
                                    "$ttable.items_id" => $fndgroup,
                                ],
                            ],
                            [
                                'AND' => [
                                    "$ttable.itemtype" => 'User',
                                    "$ttable.items_id" => $_SESSION['glpiID'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        } elseif ($p['show_only_login_alerts']) {
            $login_sql = ["{$table}.is_displayed_onlogin" => 1];
        }

        if ($p['show_hidden_alerts']) {
            //dont show hidden alert if they should no longer be visible
            $login_show_hidden_sql = ['NOT' => ["{$utable}.id" => null]];
        }

        if ($p['show_only_central_alerts']) {
            //dont show central alert if they should no longer be visible
            $show_central_sql = ["{$table}.is_displayed_oncentral" => 1];
        }

        //If the alert must be displayed on helpdesk form : filter by ticket's entity
        //and not the current entity
        if ($p['show_only_helpdesk_alerts']) {
            $show_helpdesk_sql = ["{$table}.is_displayed_onhelpdesk" => 1];
        }
        if (!$p['show_only_login_alerts']) {
            $entity_sql = getEntitiesRestrictCriteria($table, '', $p['entities_id'], true);
        }
        $criteria = [
            'SELECT'    => ["$table.*"],
            'DISTINCT'  => true,
            'FROM'      => $table,
            'LEFT JOIN' => [
                $utable => [
                    'ON' => [
                        $utable => 'plugin_news_alerts_id',
                        $table  => 'id',
                        [
                            'AND' => [
                                "$utable.users_id" => $users_id,
                                "$utable.state"    => $hidstate,
                            ],
                        ],
                    ],
                ],
            ],
            'INNER JOIN' => [
                $ttable => [
                    'ON' => [
                        $ttable => 'plugin_news_alerts_id',
                        $table  => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                $login_show_hidden_sql,
                [
                    'OR' => [
                        ["$table.date_start" => ['<=', $today]],
                        ["$table.date_start" => null],
                    ],
                ],
                [
                    'OR' => [
                        ["$table.date_end" => ['>=', $today]],
                        ["$table.date_end" => null],
                    ],
                ],
                'is_deleted' => 0,
                'is_active'  => 1,
            ],
        ];
        if ($targets_sql !== []) {
            $criteria['INNER JOIN'][$ttable]['ON'][] = $targets_sql;
        }
        if (!empty($entity_sql)) {
            $criteria['WHERE'][] = $entity_sql;
        }
        if ($login_sql !== []) {
            $criteria['WHERE'][] = $login_sql;
        }
        if ($show_central_sql !== []) {
            $criteria['WHERE'][] = $show_central_sql;
        }
        if ($show_helpdesk_sql !== []) {
            $criteria['WHERE'][] = $show_helpdesk_sql;
        }
        $it = $DB->request($criteria);
        if (count($it) === 0) {
            return false;
        }
        foreach ($it as $data) {
            $alerts[] = $data;
        }

        return $alerts;
    }

    public static function getMenuContent()
    {
        $menu                    = parent::getMenuContent();
        $menu['links']['search'] = PluginNewsAlert::getSearchURL(false);

        return $menu;
    }

    public function checkDate($datetime)
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $datetime) !== 0) {
            $datetime                 = explode(' ', $datetime);
            [$year, $month, $day] = explode('-', $datetime[0]);

            return checkdate((int) $month, (int) $day, (int) $year);
        }

        return false;
    }

    public function prepareInputForAdd($input)
    {
        $errors = [];

        $missing_name = $this->isNewItem() ? empty($input['name'] ?? '') : isset($input['name']) && empty($input['name']);

        if ($missing_name) {
            $errors[] = __s('Please enter a name.', 'news');
        }

        if (!empty($input['date_start']) && !empty($input['date_end']) && strtotime($input['date_end']) < strtotime($input['date_start'])) {
            $errors[] = __s('The end date must be greater than the start date.', 'news');
        }

        if ($errors !== []) {
            Session::addMessageAfterRedirect(implode('<br />', $errors));
        }

        return $errors !== [] ? false : $input;
    }

    public function prepareInputForUpdate($input)
    {
        if ($input['_transfer'] ?? false) {
            return $input;
        }

        return $this->prepareInputForAdd($input);
    }

    // @codingStandardsIgnoreStart
    public function post_addItem()
    {
        // @codingStandardsIgnoreEnd
        $target = new PluginNewsAlert_Target();
        $target->add(
            [
                'plugin_news_alerts_id' => $this->getID(),
                'itemtype'              => 'Profile',
                'items_id'              => 0,
                'all_items'             => 1,
            ],
        );
    }

    public function getEmpty()
    {
        if (!parent::getEmpty()) {
            return false;
        }

        $this->fields['is_close_allowed'] = 1;
        $this->fields['display_dates']    = 1;
        $this->fields['background_color'] = self::WHITE;
        $this->fields['text_color']       = self::DARK;
        $this->fields['emphasis_color']   = self::DARK;
        $this->fields['size']             = self::MEDIUM;

        return true;
    }

    public function showForm($ID, $options = [])
    {
        $twig = TemplateRenderer::getInstance();
        $twig->display('@news/alert_form.html.twig', [
            'item'             => $this,
            'templates'        => self::getTypes(),
            'sizes'            => self::getSizes(),
            'colors'           => self::getColors(),
            'icons'            => self::getIcons(),
            'templates_values' => self::getTemplatesValues(),
        ]);

        return true;
    }

    public static function displayOnCentral()
    {
        echo "<tr><td colspan='2'>";
        self::displayAlerts(['show_only_central_alerts' => true]);
        echo '</td></tr>';
    }

    public static function displayOnLogin()
    {
        echo Html::css(Plugin::getPhpDir('news', false) . '/css/styles.css');
        echo "<div class='plugin_news_alert-login'>";
        self::displayAlerts(['show_only_login_alerts' => true]);
        echo '</div>';
    }

    public static function displayOnTicket()
    {
        echo "<tr><th colspan='2'>";
        self::displayAlerts(['show_only_helpdesk_alerts' => true]);
        echo '</th></tr>';
    }

    public static function displayAlerts($params = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $p['show_only_login_alerts']    = false;
        $p['show_only_central_alerts']  = false;
        $p['show_hidden_alerts']        = false;
        $p['show_only_helpdesk_alerts'] = false;
        $p['entities_id']               = false;
        foreach ($params as $key => $value) {
            $p[$key] = $value;
        }

        echo "<div class='plugin_news_alert-container row align-items-stretch'>";
        if ($alerts = self::findAllToNotify($p)) {
            foreach ($alerts as $alert) {
                self::displayAlert($alert, $p);
            }
        }

        $hidden_params = [
            'show_hidden_alerts'        => true,
            'show_only_login_alerts'    => false,
            'show_only_central_alerts'  => $p['show_only_central_alerts'],
            'show_only_helpdesk_alerts' => $p['show_only_helpdesk_alerts'],
            'entities_id'               => $p['entities_id'],
        ];

        if (
            !$p['show_only_login_alerts']
            && $alerts = self::findAllToNotify($hidden_params)
            && !$p['show_hidden_alerts']
        ) {
            echo "<div class='center'>";
            echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/news/front/hidden_alerts.php'>";
            echo __s('You have hidden alerts valid for current date', 'news');
            echo '</a>';
            echo '</div>';
        }
        echo '</div>';

        if ($p['show_only_login_alerts']) {
            echo Html::script(Plugin::getPhpDir('news', false) . '/js/news.js');
        }
    }

    /**
     * Compute alert size classes
     *
     * @param string $size Alert size
     *
     * @return string Bootstrap col classes
     */
    public static function getSizeClasses(string $size): string
    {
        // Note: the 'w-100' class will be added using javascript when we are
        // displaying ITIL forms.
        // See display_alert.html.twig for more details.

        switch ($size) {
            case self::SMALL:
                return 'col-xxl-4 col-xl-4 col-12';

            default:
            case self::MEDIUM:
                return 'col-xxl-6 col-xl-6 col-12';

            case self::BIG:
                return 'col-xxl-8 col-xl-8 col-12';

            case self::MAXIMUM:
                return 'col-12';
        }
    }

    public static function displayAlert($alert, $p)
    {
        $twig = TemplateRenderer::getInstance();
        $size = $p['show_only_login_alerts'] ? 'col-12' : self::getSizeClasses($alert['size']);
        $twig->display('@news/display_alert.html.twig', [
            'size'                   => $size,
            'alert_fields'           => $alert,
            'content'                => $alert['message'],
            'can_close'              => $alert['is_close_allowed'] && !$p['show_hidden_alerts'],
            'show_only_login_alerts' => $p['show_only_login_alerts'],
        ]);
    }

    /**
     * Get available templates for alerts
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::GENERAL => __s('General', 'news'),
            self::INFO    => __s('Information', 'news'),
            self::WARNING => __s('Warning', 'news'),
            self::PROBLEM => __s('Problem', 'news'),
        ];
    }

    /**
     * Get available sizes for alerts
     *
     * @return array
     */
    public static function getSizes(): array
    {
        return [
            self::SMALL   => __s('Small', 'news'),
            self::MEDIUM  => __s('Medium', 'news'),
            self::BIG     => __s('Big', 'news'),
            self::MAXIMUM => __s('Max', 'news'),
        ];
    }

    /**
     * Get available icons for alerts
     *
     * @return array
     */
    public static function getIcons(): array
    {
        return [
            self::SETTINGS       => __s('Settings', 'news'),
            self::ALERT_CIRCLE   => __s('Alert circle', 'news'),
            self::ALERT_TRIANGLE => __s('Alert triangle', 'news'),
            self::ALERT_OCTAGON  => __s('Alert octagon', 'news'),
        ];
    }

    /**
     * Get available colors for alerts (text, background and accent)
     *
     * @return array
     */
    public static function getColors(): array
    {
        return [
            self::DARK   => __s('Black', 'news'),
            self::WHITE  => __s('White', 'news'),
            self::BLUE   => __s('Blue', 'news'),
            self::CYAN   => __s('Cyan', 'news'),
            self::INDIGO => __s('Indigo', 'news'),
            self::PURPLE => __s('Purple', 'news'),
            self::PINK   => __s('Pink', 'news'),
            self::RED    => __s('Red', 'news'),
            self::ORANGE => __s('Orange', 'news'),
            self::YELLOW => __s('Yellow', 'news'),
            self::LIME   => __s('Lime', 'news'),
        ];
    }

    /**
     * Get icon and colors values for each available templates
     *
     * @return array
     */
    public static function getTemplatesValues(): array
    {
        return [
            self::GENERAL => [
                'icon'             => self::SETTINGS,
                'background_color' => self::WHITE,
                'text_color'       => self::DARK,
                'emphasis_color'   => self::DARK,
            ],
            self::INFO => [
                'icon'             => self::ALERT_CIRCLE,
                'background_color' => self::WHITE,
                'text_color'       => self::DARK,
                'emphasis_color'   => self::BLUE,
            ],
            self::WARNING => [
                'icon'             => self::ALERT_TRIANGLE,
                'background_color' => self::ORANGE,
                'text_color'       => self::WHITE,
                'emphasis_color'   => self::ORANGE,
            ],
            self::PROBLEM => [
                'icon'             => self::ALERT_OCTAGON,
                'background_color' => self::RED,
                'text_color'       => self::WHITE,
                'emphasis_color'   => self::RED,
            ],
        ];
    }

    public function cleanDBOnPurge()
    {
        $target = new PluginNewsAlert_Target();
        $target->deleteByCriteria(['plugin_news_alerts_id' => $this->getID()]);
    }

    public static function preItemForm($params = [])
    {
        if (
            isset($params['item'])
            && $params['item'] instanceof CommonITILObject
        ) {
            $item        = $params['item'];
            $itemtype    = get_class($item);
            $entities_id = $params['item']->fields['entities_id'] ?? false; // false to use current entity
            self::displayAlerts(['show_only_helpdesk_alerts' => true,
                'show_hidden_alerts'                         => false,
                'entities_id'                                => $entities_id,
            ]);
        }
    }

    public static function preItemList($params = [])
    {
        if (isset($params['itemtype']) && $params['itemtype'] == 'Ticket') {
            echo "<tr><th colspan='2'>";
            self::displayAlerts(['show_only_helpdesk_alerts' => true]);
            echo '</th></tr>';
        }
    }

    public static function getIcon()
    {
        return 'fas fa-bell';
    }
}
