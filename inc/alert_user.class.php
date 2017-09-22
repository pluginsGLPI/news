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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginNewsAlert_User extends CommonDBTM {
   const HIDDEN = 1;

   static function hideAlert($params = []) {
      global $DB;

      if (!isset($params['id'])) {
         return false;
      }

      $plugin_news_alerts_id = intval($params['id']);
      $users_id = $_SESSION['glpiID'];

      $query_hidealert = "REPLACE INTO `".self::getTable()."`
                           (`users_id`, `plugin_news_alerts_id`, `state`)
                           VALUES
                           ($users_id, $plugin_news_alerts_id, ".self::HIDDEN.")";
      return $res_hidealert = $DB->query($query_hidealert);
   }
}
