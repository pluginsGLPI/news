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

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

header("Content-type: application/javascript");

if($alerts = PluginNewsAlert::findAllToNotify()): ?>

$(function(){

<?php foreach($alerts as $alert): ?>

   $('#page').prepend('<table class="plugin_news_alert"><tr><th><?php echo Toolbox::addslashes_deep($alert['name']) ?></th></tr><tr><td><?php echo str_replace(chr(13).chr(10), '', Html::entity_decode_deep(Toolbox::addslashes_deep($alert['message']))) ?></td></tr></table><br />');

<?php endforeach; ?>

});

<?php endif;
