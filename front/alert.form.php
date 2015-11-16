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

include ("../../../inc/includes.php");

Session::checkRight('plugin_news', READ);

$alert = new PluginNewsAlert();

if(isset($_POST['update'])) {
   $alert->update($_POST);
   Html::back();

} elseif(isset($_POST['add'])) {
   $alert->add($_POST);
   Html::back();

} elseif(isset($_POST['delete'])) {
   $alert->delete($_POST);
   $alert->redirectToList();

} elseif(isset($_POST['restore'])) {
   $alert->restore($_POST);
   Html::back();

} elseif(isset($_POST['purge'])) {
   $alert->delete($_POST, 1);
   $alert->redirectToList();
}

Html::header(
   __('Alerts', 'news'),
   $_SERVER["PHP_SELF"],
   'tools',
   "PluginNewsAlert"
);

$alert->showForm(isset($_GET['id']) ? $_GET['id'] : 0);

Html::footer();
