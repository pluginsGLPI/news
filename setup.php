<?php
/*
 *
 -------------------------------------------------------------------------
 Plugin GLPI News
 Copyright (C) 2015 by teclib.
 http://www.teclib.com
 -------------------------------------------------------------------------
 LICENSE
 This file is part of Plugin GLPI News.
 Plugin GLPI News is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 Plugin GLPI News is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with Plugin GLPI News. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

function plugin_init_news() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['news'] = true;

   $PLUGIN_HOOKS['change_profile']['news'] = array('PluginNewsProfile', 'changeProfile');

   Plugin::registerClass('PluginNewsProfile', array('addtabon' => 'Profile'));

   $plugin = new Plugin();

   if (isset($_SESSION['glpiID'])
      && $plugin->isInstalled('news')
         && $plugin->isActivated('news')) {
      if(!isset($_SESSION['glpi_news_alert'])) {
         $PLUGIN_HOOKS['add_javascript']['news'][] = 'scripts/jquery-1.9.1.min.js';
         $PLUGIN_HOOKS['add_javascript']['news'][] = 'scripts/alert.php';
         $_SESSION['glpi_news_alert'] = true;
      }
      if(Session::haveRight('news_alert', 'w')) {
         $PLUGIN_HOOKS['menu_entry']['news'] = 'front/alert.php';
         $PLUGIN_HOOKS['submenu_entry']['news']['options']['alert'] = array(
            'links' => array( 'add' => '/plugins/news/front/alert.form.php')
         );
      }
   }
}

function plugin_version_news() {
   global $LANG;

   return array(
      'name' => $LANG['plugin_news']['title'],
      'version' => '0.84-1.0',
      'author' => "<a href=\"mailto:contact@teclib.com\">TECLIB'</a>",
      'license' => "GPLv2+",
      'homepage' => 'http://www.teclib.com/',
      'minGlpiVersion' => '0.84'
   );
}

function plugin_news_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '0.84', 'lt')
      || version_compare(GLPI_VERSION, '0.85', 'ge')) {
      echo "GLPI v0.84.x is required.";
      return false;
   }
   return true;
}

function plugin_news_check_config() {
   return true;
}
