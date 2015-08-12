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
   $PLUGIN_HOOKS['add_javascript']['news'] = 'scripts/alert.php';
   $PLUGIN_HOOKS['add_css']['news']        = 'css/styles.css';
   $PLUGIN_HOOKS['change_profile']['news'] = array('PluginNewsProfile', 'changeProfile');

   Plugin::registerClass('PluginNewsProfile', array('addtabon' => 'Profile'));

   $plugin = new Plugin();

   if (isset($_SESSION['glpiID']) && $plugin->isInstalled('news') && $plugin->isActivated('news')) {
      if(Session::haveRight('plugin_news', READ)) {
         $PLUGIN_HOOKS['menu_toadd']['news'] = array(
            'tools'    => 'PluginNewsAlert',
         );
      }
   }
}

function plugin_version_news() {
   global $LANG;

   return array(
      'name'           => $LANG['plugin_news']['title'],
      'version'        => '0.85-1.0',
      'author'         => "<a href=\"mailto:contact@teclib.com\">TECLIB'</a>",
      'license'        => "GPLv2+",
      'homepage'       => 'http://www.teclib.com/',
      'minGlpiVersion' => '0.85'
   );
}

function plugin_news_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '0.84', 'lt') || version_compare(GLPI_VERSION, '0.91', 'ge')) {
      echo "This version require GLPI 0.85.x or 0.90.x";
      return false;
   }
   return true;
}

function plugin_news_check_config() {
   return true;
}
