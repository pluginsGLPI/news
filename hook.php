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
 
require_once "inc/alert.class.php";
require_once "inc/profile.class.php";

function plugin_news_install() {
   if (!PluginNewsAlert::createTable()
      || !PluginNewsProfile::createTable()) {

      Session::addMessageAfterRedirect('Installation failed');

      return false;
   }

   PluginNewsProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id'], true);

   return true;
}

function plugin_news_uninstall() {
   if (!PluginNewsAlert::dropTable()
      || !PluginNewsProfile::dropTable()) {

      Session::addMessageAfterRedirect('Uninstallation failed');

      return false;
   }
   return true;
}
