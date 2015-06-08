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

class PluginNewsProfile extends CommonDBTM {

   static function createTable() {

      global $DB;

      return $DB->query("
         CREATE TABLE IF NOT EXISTS `glpi_plugin_news_profiles` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `profiles_id` INT(11) NOT NULL DEFAULT '0',
            `alert` CHAR(1),
            PRIMARY KEY (`id`),
            KEY `profiles_id` (`profiles_id`)
         ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
      ");
   }

   static function dropTable() {
      global $DB;

      return $DB->query("DROP TABLE IF EXISTS `glpi_plugin_news_profiles`");
   }

   static function createFirstAccess($ID, $admin = false) {

      $profile = new self();

      if (!$profile->getFromDBByProfile($ID)) {

         $profile->add(array(
            'profiles_id' => $ID,
            'alert' => $admin ? 'w' : '',
         ));
      }

      self::changeProfile();
   }

   static function changeProfile() {

      $profile = new self();

      if ($profile->getFromDBByProfile($_SESSION['glpiactiveprofile']['id'])) {
         $_SESSION['glpiactiveprofile']['news_alert'] = $profile->getField('alert');
      } else {
         unset($_SESSION['glpiactiveprofile']['news_alert']);
      }
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      return $LANG['plugin_news']['title'];
   }

   function getFromDBByProfile($profiles_id) {

      global $DB;

      $query = "SELECT * FROM `".$this->getTable()."`
               WHERE `profiles_id` = '" . $profiles_id . "' ";

      if ($result = $DB->query($query)) {
         if (!$DB->numrows($result)) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         }
         return false;
      }
      return false;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      $profile = new self();

      if(!$profile->getFromDBByProfile($item->getID())) {

         self::createFirstAccess($item->getID());

         $profile->getFromDBByProfile($item->getID());
      }
      $profile->showForm();
   }

   function showForm() {

      global $LANG;

      echo '<form method="post" action="'.$this->getFormURL().'">';
      echo '<table class="tab_cadre_fixe">';
      echo '<tr>';
      echo '<th>'.$LANG['plugin_news']['title'].'</th>';
      echo '<td align="center">';
      Profile::dropdownNoneReadWrite('alert', $this->getField('alert'), 1, 0, 1);
      echo '</td>';
      echo '<td align="center">';
      echo '<input class="submit" type="submit" value="'.__('Save').'" />';
      echo '</td>';
      echo '</tr>';
      echo '</table>';
      echo '<input type="hidden" name="id" value="'.$this->getID().'" />';
      HTML::closeForm();
   }
}
