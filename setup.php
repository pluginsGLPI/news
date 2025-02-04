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

define('PLUGIN_NEWS_VERSION', '1.12.4');

// Minimal GLPI version, inclusive
define('PLUGIN_NEWS_MIN_GLPI', '11.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_NEWS_MAX_GLPI', '11.0.99');

function plugin_init_news()
{
    /**
     * @var array $PLUGIN_HOOKS
     * @var array $CFG_GLPI
     */
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['news'] = true;

    $plugin = new Plugin();
    if (
        $plugin->isInstalled('news')
        && $plugin->isActivated('news')
    ) {
        Plugin::registerClass('PluginNewsProfile', ['addtabon' => 'Profile']);

        $PLUGIN_HOOKS['add_css']['news']          = 'css/styles.css';
        $PLUGIN_HOOKS['add_javascript']['news'][] = 'js/news.js';
        $PLUGIN_HOOKS['display_login']['news']    = [
            'PluginNewsAlert', 'displayOnLogin',
        ];
        $PLUGIN_HOOKS['display_central']['news'] = [
            'PluginNewsAlert', 'displayOnCentral',
        ];
        $PLUGIN_HOOKS['pre_item_list']['news'] = ['PluginNewsAlert', 'preItemList'];

        $PLUGIN_HOOKS['pre_item_form']['news'] = ['PluginNewsAlert', 'preItemForm'];

        if (Session::haveRight(PluginNewsAlert::$rightname, READ)) {
            $PLUGIN_HOOKS['menu_toadd']['news'] = [
                'tools' => 'PluginNewsAlert',
            ];
            $PLUGIN_HOOKS['config_page']['news'] = 'front/alert.php';

            // require tinymce (for glpi >= 9.2)
            $CFG_GLPI['javascript']['tools']['pluginnewsalert'] = ['tinymce'];
        }
    }
}

function plugin_version_news()
{
    return [
        'name'         => __('Alerts', 'news'),
        'version'      => PLUGIN_NEWS_VERSION,
        'author'       => "<a href='mailto:contact@teclib.com'>TECLIB'</a>",
        'license'      => 'GPLv2+',
        'homepage'     => 'https://github.com/pluginsGLPI/news',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_NEWS_MIN_GLPI,
                'max' => PLUGIN_NEWS_MAX_GLPI,
            ],
        ],
    ];
}
