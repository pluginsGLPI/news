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
   static $rightname = 'reminder_public';

   const GENERAL = 1;
   const INFO    = 2;
   const WARNING = 3;
   const PROBLEM = 4;

   static function canCreate() {
      return self::canUpdate();
   }

   static function canDelete() {
      return self::canUpdate();
   }

   static function canPurge() {
      return self::canUpdate();
   }

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
      $tab[1]['table']            = $this->getTable();
      $tab[1]['field']            = 'name';
      $tab[1]['name']             = __('Name');
      $tab[1]['datatype']         = 'itemlink';
      $tab[1]['itemlink_type']    = $this->getType();
      $tab[1]['massiveaction']    = false;

      $tab[2]['table']            = $this->getTable();
      $tab[2]['field']            = 'date_start';
      $tab[2]['datatype']         = 'date';
      $tab[2]['name']             = __("Visibility start date");

      $tab[3]['table']            = $this->getTable();
      $tab[3]['field']            = 'date_end';
      $tab[3]['datatype']         = 'date';
      $tab[3]['name']             = __("Visibility end date");

      $tab[4]['table']            = 'glpi_entities';
      $tab[4]['field']            = 'completename';
      $tab[4]['name']             = __('Entity');
      $tab[4]['massiveaction']    = false;

      $tab[5]['table']            = $this->getTable();
      $tab[5]['field']            = 'is_recursive';
      $tab[5]['name']             = __('Recursive');
      $tab[5]['massiveaction']    = false;
      $tab[5]['datatype']         = 'bool';

      $tab[6]['table']            = PluginNewsAlert_Target::getTable();
      $tab[6]['field']            = 'items_id';
      $tab[6]['name']             = PluginNewsAlert_Target::getTypename();
      $tab[6]['datatype']         = 'specific';
      $tab[6]['forcegroupby']     = true;
      $tab[6]['joinparams']       = array('jointype' => 'child');
      $tab[6]['additionalfields'] = array('itemtype');

      return $tab;
   }


   public static function findAllToNotify($show_only_login_alerts = false,
                                          $show_hidden_alerts = false) {
      global $DB;

      $alerts   = array();
      $today    = date('Y-m-d H:i:s');
      $table    = self::getTable();
      $utable   = PluginNewsAlert_User::getTable();
      $ttable   = PluginNewsAlert_Target::getTable();
      $hidstate = PluginNewsAlert_User::HIDDEN;
      $users_id = isset($_SESSION['glpiID'])
                     ? $_SESSION['glpiID']
                     : -1;
      $group_u  = new Group_User;
      $fndgroup = array();
      if (isset($_SESSION['glpiID'])
          && $fndgroup_user = $group_u->find("users_id = ".$_SESSION['glpiID'])) {
         foreach ($fndgroup_user as $group) {
            $fndgroup[] = $group['groups_id'];
         }
         $fndgroup = implode(',', $fndgroup);
      }
      if (empty($fndgroup)) {
         $fndgroup = "-1";
      }

      // filters for query
      $targets_sql = "";
      $login_sql = "";
      $login_show_hiden_sql = " `$utable`.`id` IS NULL ";
      $entity_sql = "";
      if (isset($_SESSION['glpiID'])) {
         $targets_sql = "AND (
                           `$ttable`.`itemtype` = 'Profile'
                           AND (
                              `$ttable`.`items_id` = ".$_SESSION['glpiactiveprofile']['id']."
                              OR `$ttable`.`items_id` = -1
                           )
                           OR `$ttable`.`itemtype` = 'Group'
                              AND `$ttable`.`items_id` IN ($fndgroup)
                           OR `$ttable`.`itemtype` = 'User'
                              AND `$ttable`.`items_id` = ".$_SESSION['glpiID']."
                        )";
      } else if ($show_only_login_alerts){
         $login_sql = " AND `$table`.`is_displayed_onlogin` = 1";
      }

      if ($show_hidden_alerts) {
         $login_show_hiden_sql = " `$utable`.`id` IS NOT NULL ";
      }

      if (!$show_only_login_alerts) {
         $entity_sql = getEntitiesRestrictRequest("AND", $table, "", "", true, true);
      }

      $query = "SELECT DISTINCT `$table`.`id`, `$table`.*
                  FROM `$table`
                  LEFT JOIN `$utable`
                     ON `$utable`.`plugin_news_alerts_id` = `$table`.`id`
                     AND `$utable`.`users_id` = $users_id
                     AND `$utable`.`state` = $hidstate
                  INNER JOIN `$ttable`
                     ON `$ttable`.`plugin_news_alerts_id` = `$table`.`id`
                  $targets_sql
                  WHERE ($login_show_hiden_sql $login_sql)
                     AND (`$table`.`date_start` < '$today'
                           OR `$table`.`date_start` = '$today'
                           OR `$table`.`date_start` IS NULL
                     )
                     AND (`$table`.`date_end` IS NULL
                           OR `$table`.`date_end` > '$today'
                           OR `$table`.`date_end` = '$today'
                     )
                  AND `is_deleted` = 0
                  $entity_sql";

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


   public function checkDate($datetime) {
      if ( preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $datetime) ) {
         $datetime = explode(" ", $datetime);
         list($year , $month , $day) = explode('-',$datetime[0]);
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

      if (!empty($input['date_start'])
          && !empty($input['date_end'])) {
         if (strtotime($input['date_end']) < strtotime($input['date_start'])) {
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
      Html::showDateTimeField("date_start",
                              array('value'      => $this->fields["date_start"],
                                    'timestep'   => 1,
                                    'maybeempty' => true,
                                    'canedit'    => $canedit));
      echo '</td>';
      echo '<td style="width: 150px">' . __("Visibility end date") .'</td>';
      echo '<td>';
      Html::showDateTimeField("date_end",
                              array('value'      => $this->fields["date_end"],
                                    'timestep'   => 1,
                                    'maybeempty' => true,
                                    'canedit'    => $canedit));
      echo '</td>';
      echo '</tr>';

      echo '<tr>';
      echo '<td>' . __("Type (to add an icon before alert title)", 'news') .'</td>';
      echo '</td>';
      echo '<td>';
      $types = self::getTypes();
      Dropdown::showFromArray('type', $types, array('value' => $this->fields['type'],
                                                    'display_emptychoice' => true));
      echo '</td>';
      echo '<td>' . __("Show on login page", 'news') .'</td>';
      echo '</td>';
      echo '<td>';
      Dropdown::showYesNo('is_displayed_onlogin', $this->fields['is_displayed_onlogin']);
      echo '</td>';
      echo '</tr>';

      $options['colspan'] = 4;
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

   static function displayAlerts($show_only_login_alerts = false,
                                 $show_hidden_alerts = false) {
      global $CFG_GLPI;

      echo "<div class='plugin_news_alert-container'>";
      if($alerts = self::findAllToNotify($show_only_login_alerts,
                                         $show_hidden_alerts)) {
         foreach($alerts as $alert) {
            $title      = $alert['name'];
            $type       = $alert['type'];
            $date_start = Html::convDateTime($alert['date_start']);
            $date_end   = Html::convDateTime($alert['date_end']);
            if (!empty($date_end)) {
               $date_end = " - $date_end";
            }
            $content    = Html::entity_decode_deep($alert['message']);
            echo "<div class='plugin_news_alert' data-id='".$alert['id']."'>";
            if (!$show_hidden_alerts) {
               echo "<a class='plugin_news_alert-close'></a>";
            }
            if ($show_only_login_alerts) {
               echo "<a class='plugin_news_alert-toggle'></a>";
            }
            echo "<div class='plugin_news_alert-title'>";
            echo "<span class='plugin_news_alert-icon type_$type'></span>";
            echo "<div class='plugin_news_alert-title-content'>$title</div>";
            echo "<div class='plugin_news_alert-date'>$date_start$date_end</div>";
            echo "</div>";
            echo "<div class='plugin_news_alert-content'>$content</div>";
            echo "</div>";
         }
      }
      if(!$show_only_login_alerts
         && $alerts = self::findAllToNotify(false, true)
          && !$show_hidden_alerts) {
         echo "<div class='center'>";
         echo "<a href='".$CFG_GLPI['root_doc'].
                          "/plugins/news/front/hidden_alerts.php'>";
         echo __("You have hidden alerts valid for current date", 'news');
         echo "</a>";
         echo "</div>";
      }
      echo "</div>";

      if ($show_only_login_alerts) {
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jquery/js/jquery-1.10.2.min.js");
         echo Html::script($CFG_GLPI["root_doc"]."/lib/jquery/js/jquery-ui-1.10.4.custom.min.js");
         echo Html::script($CFG_GLPI["root_doc"]."/plugins/news/scripts/news.js");
      }

      echo Html::scriptBlock("$(document).ready(function() {
         pluginNewsCloseAlerts();
         pluginNewsToggleAlerts();
      })");
   }

   static function getTypes() {
      return array(self::GENERAL => __("General", 'news'),
                   self::INFO    => __("Information", 'news'),
                   self::WARNING => __("Warning", 'news'),
                   self::PROBLEM => __("Problem", 'news'));
   }

   function cleanDBOnPurge() {
      $target = new PluginNewsAlert_Target;
      $target->deleteByCriteria(array('plugin_news_alerts_id' => $this->getID()));
   }
}
