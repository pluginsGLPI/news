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

class PluginNewsProfile extends Profile {

   static $rightname = 'plugin_news';

   public static function getAllRights() {

      return array(
         array(
            'itemtype' => 'PluginNewsProfile',
            'label'    =>  __('Manage alerts', 'news'),
            'field'    => 'plugin_news'
         ),
      );
   }

   public static function addDefaultProfileInfos($profiles_id, $rights) {

      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (!countElementsInTable('glpi_profilerights',
                                   "`profiles_id`='$profiles_id' AND `name`='$right'")) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);

            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

   public static function createFirstAccess($profiles_id) {

      $profile = new self();
      foreach ($profile->getAllRights() as $right) {
         self::addDefaultProfileInfos($profiles_id, array($right['field'] => ALLSTANDARDRIGHT));
      }
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType() == 'Profile') {
         if ($item->getField('interface') == 'central') {
            return __('Alerts', 'news');
         }
         return '';
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Profile') {
         $profile = new self();
         $ID   = $item->getField('id');
         //In case there's no right for this profile, create it
         self::addDefaultProfileInfos($item->getID(), 
                                      array('plugin_news' => 0));
         $profile->showForm($ID);
      }
      return true;
   }

   public function showForm($profiles_id=0, $openform=TRUE, $closeform=TRUE) {

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRightsOr('profile', array(CREATE, UPDATE, PURGE)))
          && $openform) {
         $profile = new Profile();
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }

      $profile = new Profile();
      $profile->getFromDB($profiles_id);

      $rights = self::getAllRights();
      $profile->displayRightsChoiceMatrix(self::getAllRights(), 
                                          array('canedit'       => $canedit,
                                                'default_class' => 'tab_bg_2',
                                                'title'         => __('General')));
      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', array('value' => $profiles_id));
         echo Html::submit(_sx('button', 'Save'), array('name' => 'update'));
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }

   public function cleanProfiles($ID) {
      global $DB;
      $query = "DELETE FROM `glpi_profiles` 
                WHERE `profiles_id`='$ID' 
                   AND `name` LIKE '%plugin_news%'";
      $DB->query($query);
   }

}