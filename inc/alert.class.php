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

class PluginNewsAlert extends CommonDBTM
{
   static $rightname = 'plugin_news';

   /**
    * Returns the type name with consideration of plural
    *
    * @param number $nb Number of item(s)
    * @return string Itemtype name
    */
   public static function getTypeName($nb = 0)
   {
      return __('Alerts', 'news');
   }

   public function getSearchOptions()
   {
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

   public static function findAllToNotify()
   {
      $self = new self;

      $user_entities_id = array();

      foreach($_SESSION['glpiprofiles'] as $profile) {
         foreach($profile['entities'] as $entity) {
            if($entity['is_recursive']) {
               $user_entities_id = array_merge(getSonsOf('glpi_entities', $entity['id']));
            } else {
               array_push($user_entities_id, $entity['id']);
            }
         }
      }

      echo '/*' . chr(10) . 'user_entities_id' . chr(10) . var_export($user_entities_id, true) . chr(10) . '*/';

      $user_profiles_id = array_keys($_SESSION['glpiprofiles']);

      echo '/*' . chr(10) . 'user_profiles_id' . chr(10) . var_export($user_profiles_id, true) . chr(10) . '*/';

      $alerts = $self->find("
         `is_deleted` = 0
         AND ( DATE(NOW()) BETWEEN `date_start` AND `date_end` )
         AND `profiles_id` IN (". implode(", ", $user_profiles_id) .")
      ");

      foreach($alerts as $index => $alert) {
         if($alert['is_recursive']) {
            $alert_entities_id = getSonsOf('glpi_entities', $alert['entities_id']);
         } else {
            $alert_entities_id = array($alert['entities_id']);
         }

         echo '/*' . chr(10) . 'alert_entities_id' . chr(10) . var_export($alert_entities_id, true) . chr(10) . '*/';

         if(!array_intersect($user_entities_id, $alert_entities_id)) {
            unset($alerts[$index]);
         }
      }

      return $alerts;
   }

   public static function getMenuContent()
   {
      global $CFG_GLPI;

      $menu  = parent::getMenuContent();
      $menu['links']['search'] = PluginNewsAlert::getSearchURL(false);

      return $menu;
   }


   public function checkDate($date)
   {
      if ( preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $date) ) {
         list($year , $month , $day) = explode('-',$date);
         return checkdate($month , $day , $year);
      }
      return false;
   }

   public function prepareInputForAdd($input)
   {
      $errors = array();

      if(!$input['name']) {
         array_push($errors, __('Please enter a name.', 'news'));
      }

      if(!$input['message']) {
         array_push($errors, __('Please enter a message.', 'news'));
      }

      if(!$input['date_start'] || !$this->checkDate($input['date_start'])) {
         array_push($errors, __('Please enter a valid start date.', 'news'));
      } elseif(!$input['date_end'] || !$this->checkDate($input['date_end'])) {
         array_push($errors, __('Please enter a valid end date.', 'news'));
      } elseif($input['date_end'] < $input['date_start']) {
         array_push($errors, __('The end date must be greater than the start date.', 'news'));
      }

      if(!$input['profiles_id']) {
         array_push($errors, __('Please enter a profile.', 'news'));
      }

      if($errors) {
         Session::addMessageAfterRedirect(implode('<br />', $errors));
      }

      return $errors ? false : $input;
   }

   public function prepareInputForUpdate($input)
   {
      return $this->prepareInputForAdd($input);
   }

   public function showForm($ID, $options = array())
   {
      $this->check($ID, UPDATE);

      if($this->getField('message') == 'N/A') {
         $this->fields['message'] = "";
      }

      Html::initEditorSystem('message');

      $this->showFormHeader($options);

      echo '<tr>';
      echo '<td style="width: 150px">' . __('Name') .'</td>';
      echo '<td colspan="3"><input name="name" type="text" value="'.$this->getField('name').'" style="width: 565px" /></td>';
      echo '</tr>';
      echo '<tr>';
      echo '<td>' . __('Description') .'</td>';
      echo '<td colspan="3">';
      echo '<textarea name="message" rows="12" cols="80">'.$this->getField('message').'</textarea>';
      echo '</td>';
      echo '</tr>';
      echo '<tr>';
      echo '<td style="width: 150px">' . __("Visibility start date") .'</td>';
      echo '<td>';
      Html::showDateFormItem('date_start', Html::convDate($this->getField('date_start')), false);
      echo '</td>';
      echo '<td style="width: 150px">' . __("Visibility end date") .'</td>';
      echo '<td>';
      Html::showDateFormItem('date_end', Html::convDate($this->getField('date_end')), false);
      echo '</td>';
      echo '</tr>';
      echo '<tr>';
      echo '<td>' . __("Profile") .'</td>';
      echo '<td colspan="3">';
      Dropdown::show('Profile', array('name' => 'profiles_id', 'value' => $this->getField('profiles_id') ?: 0));
      echo '</td>';
      echo '</tr>';

      $this->showFormButtons($options);
   }

   public static function createTable()
   {
      global $DB;

      return $DB->query("
         CREATE TABLE IF NOT EXISTS `glpi_plugin_news_alerts` (
         `id` INT NOT NULL AUTO_INCREMENT,
         `date_mod` DATETIME NOT NULL,
         `name` VARCHAR(255) NOT NULL,
         `message` TEXT NOT NULL,
         `date_start` DATE NOT NULL,
         `date_end` DATE NOT NULL,
         `is_deleted` TINYINT(1) NOT NULL,
         `profiles_id` INT NOT NULL,
         `entities_id` INT NOT NULL,
         `is_recursive` TINYINT(1) NOT NULL,
         PRIMARY KEY (`id`)
         ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
      ");
   }

   public static function dropTable()
   {
      global $DB;

      return $DB->query("DROP TABLE IF EXISTS `glpi_plugin_news_alerts`");
   }
}
