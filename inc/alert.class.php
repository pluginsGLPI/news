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

class PluginNewsAlert extends CommonDBTM {
   static $rightname = 'entity';

   /**
    * Returns the type name with consideration of plural
    *
    * @param number $nb Number of item(s)
    * @return string Itemtype name
    */
   public static function getTypeName($nb = 0) {
      return __('Alerts', 'news');
   }

   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong)
           ->addStandardTab('PluginNewsAlert_Target', $ong, $options);

      return $ong;
   }


   public function getSearchOptions() {
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'date_start';
      $tab[2]['datatype']      = 'date';
      $tab[2]['name']          = __("Visibility start date");

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'date_end';
      $tab[3]['datatype']      = 'date';
      $tab[3]['name']          = __("Visibility end date");

      $tab[4]['table']         = 'glpi_entities';
      $tab[4]['field']         = 'completename';
      $tab[4]['name']          = __('Entity');
      $tab[4]['massiveaction'] = false;

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'is_recursive';
      $tab[5]['name']          = __('Recursive');
      $tab[5]['massiveaction'] = false;
      $tab[5]['datatype']      = 'bool';

      $tab[6]['table']         = 'glpi_profiles';
      $tab[6]['field']         = 'name';
      $tab[6]['name']          = __('Profile');

      return $tab;
   }

   public static function findAllToNotify($show_only_login_alerts =  false) {
      global $DB;

      $alerts   = array();
      $today    = date('Y-m-d');
      $table    = self::getTable();
      $utable   = PluginNewsAlert_User::getTable();
      $ttable   = PluginNewsAlert_Target::getTable();
      $hidstate = PluginNewsAlert_User::HIDDEN;
      $users_id = isset($_SESSION['glpiID'])
                     ? $_SESSION['glpiID']
                     : -1;
      $group_u  = new Group_User;
      $fndgroup = implode(',', $group_u->find("users_id = ".$_SESSION['glpiID']));
      if (empty($fndgroup)) {
         $fndgroup = "-1";
      }

      $query = "SELECT `$table`.*
                  FROM `$table`
                  LEFT JOIN `$utable`
                     ON `$utable`.`plugin_news_alerts_id` = `$table`.`id`
                     AND `$utable`.`users_id` = $users_id
                     AND `$utable`.`state` = $hidstate
                  INNER JOIN `$ttable`
                     ON `$ttable`.`plugin_news_alerts_id` = `$table`.`id`
                     AND (
                        `$ttable`.`itemtype` = 'Profile'
                        AND (
                           `$ttable`.`items_id` = {$_SESSION['glpiactiveprofile']['id']}
                           OR `$ttable`.`items_id` = -1
                        )
                        OR `$ttable`.`itemtype` = 'Group'
                           AND `$ttable`.`items_id` IN ($fndgroup)
                        OR `$ttable`.`itemtype` = 'User'
                           AND `$ttable`.`items_id` = {$_SESSION['glpiactiveprofile']['id']}
                     )
                  WHERE `$utable`.`id` IS NULL
                     AND (`$table`.`date_start` < '$today'
                           OR `$table`.`date_start` = '$today'
                     )
                     AND (`$table`.`date_end` IS NULL
                           OR `$table`.`date_end` > '$today'
                           OR `$table`.`date_end` = '$today'
                     )
                  AND `is_deleted` = 0
                  ";

      if ($show_only_login_alerts) {
         $query.= " AND `$table`.`is_displayed_onlogin` = 1";
      } else {
         $query.= getEntitiesRestrictRequest("AND", $table, "", "", true, true);
      }
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) < 1) {
            return false;
         }

         while ($data = $DB->fetch_assoc($result)) {
            $alerts[] = $data;
         }
      }

      return $alerts;
   }

   public static function getMenuContent() {
      $menu  = parent::getMenuContent();
      $menu['links']['search'] = PluginNewsAlert::getSearchURL(false);

      return $menu;
   }


   public function checkDate($date) {
      if ( preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $date) ) {
         list($year , $month , $day) = explode('-',$date);
         return checkdate($month , $day , $year);
      }
      return false;
   }

   public function prepareInputForAdd($input) {
      $errors = array();

      if(!$input['name']) {
         array_push($errors, __('Please enter a name.', 'news'));
      }

      if(!$input['message']) {
         array_push($errors, __('Please enter a message.', 'news'));
      }

      if(!$input['date_start'] || !$this->checkDate($input['date_start'])) {
         array_push($errors, __('Please enter a valid start date.', 'news'));
      }

      if (!empty($input['date_end'])) {
         if(!$input['date_end'] || !$this->checkDate($input['date_end'])) {
            array_push($errors, __('Please enter a valid end date.', 'news'));
         } elseif($input['date_end'] < $input['date_start']) {
            array_push($errors, __('The end date must be greater than the start date.', 'news'));
         }
      }

      if($errors) {
         Session::addMessageAfterRedirect(implode('<br />', $errors));
      }

      return $errors ? false : $input;
   }

   public function prepareInputForUpdate($input) {
      return $this->prepareInputForAdd($input);
   }

   function post_addItem() {
      $target = new PluginNewsAlert_Target;
      $target->add(array('plugin_news_alerts_id' => $this->getID(),
                         'itemtype'              => 'Profile',
                         'items_id'              => -1));
   }

   public function showForm($ID, $options = array()) {
      $this->initForm($ID, $options);

      $canedit = $this->can($ID, UPDATE);

      if($this->getField('message') == 'N/A') {
         $this->fields['message'] = "";
      }

      $this->showFormHeader($options);

      echo "<tr  class='tab_bg_1'>";
      echo '<td style="width: 150px">' . __('Name') .'</td>';
      echo '<td colspan="3"><input name="name" type="text" value="'.$this->getField('name').'" style="width: 565px" /></td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td>' . __('Description') .'</td>';
      echo '<td colspan="3">';
      echo '<textarea name="message" rows="12" cols="80">'.$this->getField('message').'</textarea>';
      Html::initEditorSystem('message');
      echo '</td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td style="width: 150px">' . __("Visibility start date") .'</td>';
      echo '<td>';
      Html::showDateField("date_start",
                              array('value'      => $this->fields["date_start"],
                                    'timestep'   => 1,
                                    'maybeempty' => false,
                                    'canedit'    => $canedit));
      echo '</td>';
      echo '<td style="width: 150px">' . __("Visibility end date") .'</td>';
      echo '<td>';
      Html::showDateField("date_end",
                              array('value'      => $this->fields["date_end"],
                                    'timestep'   => 1,
                                    'maybeempty' => true,
                                    'canedit'    => $canedit));
      echo '</td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td>' . __("Show on login page", 'news') .'</td>';
      echo '</td>';
      echo '<td>';
      Dropdown::showYesNo('is_displayed_onlogin', $this->fields['is_displayed_onlogin']);
      echo '</td>';
      echo '</tr>';

      $this->showFormButtons($options);
   }

   static function displayOnCentral() {
      echo "<tr><th colspan='2'>";
      self::displayAlerts(false);
      echo "</th></tr>";
   }

   static function displayOnLogin() {
      global $CFG_GLPI;

      echo Html::css($CFG_GLPI["root_doc"]."/plugins/news/css/styles.css");
      echo "<div class='plugin_news_alert-login'>";
      self::displayAlerts(true);
      echo "</div>";
   }

   static function displayAlerts($show_only_login_alerts = false) {
      if($alerts = self::findAllToNotify($show_only_login_alerts)) {
         echo "<div class='plugin_news_alert-container'>";
         foreach($alerts as $alert) {
            $title = $alert['name'];
            $content = Html::entity_decode_deep($alert['message']);
            echo "<div class='plugin_news_alert' data-id='".$alert['id']."'>
                  <a class='plugin_news_alert-close'></a>
                  <div class='plugin_news_alert-title'>$title</div>
                  <div class='plugin_news_alert-content'>$content</div>
                  </div>";
         }
         echo "</div>";
      }

      echo Html::scriptBlock("$(document).ready(function() {
         pluginNewsCloseAlerts();
      })");
   }

}
