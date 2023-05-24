<?php

use Glpi\Application\View\TemplateRenderer;

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
 * @copyright Copyright (C) 2015-2022 by News plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/news
 * -------------------------------------------------------------------------
 */

class PluginNewsProfile extends Profile {

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      return self::createTabEntry(
         PluginNewsAlert::getTypeName(Session::getPluralNumber())
      );
   }

   public static function displayTabContentForItem(
      CommonGLPI $item,
      $tabnum = 1,
      $withtemplate = 0
   ) {
      if (!$item instanceof Profile || !self::canView()) {
         return false;
      }

      $profile = new Profile();
      $profile->getFromDB($item->getID());

      $twig = TemplateRenderer::getInstance();
      $twig->display("@news/profile.html.twig", [
         'id'      => $item->getID(),
         'profile' => $profile,
         'title'   => PluginNewsAlert::getTypeName(Session::getPluralNumber()),
         'rights'  => [
            [
               'itemtype' => PluginNewsAlert::getType(),
               'label'    => PluginNewsAlert::getTypeName(Session::getPluralNumber()),
               'field'    => PluginNewsAlert::$rightname,
            ]
         ]
      ]);

      return true;
   }
}
