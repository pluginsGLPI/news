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

pluginNewsCloseAlerts = function() {
   $(document).on("click", "a.plugin_news_alert-close",function() {
      var alert = $(this).parent(".plugin_news_alert");
      var id    = alert.attr('data-id');
      var ajax_baseurl = CFG_GLPI.root_doc+"/"+GLPI_PLUGINS_PATH.news+"/ajax";
      $.post(ajax_baseurl+"/hide_alert.php", {'id' : id})
         .done(function() {
            alert.remove();
         });
   });
};

pluginNewsToggleAlerts = function() {
   $(document).on("click", ".plugin_news_alert-toggle",function() {
      var alert = $(this).parent(".plugin_news_alert");
      alert.toggleClass('expanded');
   });
}

$(document).ready(function() {
   pluginNewsCloseAlerts();
   pluginNewsToggleAlerts();

   $(".glpi_tabs").on("tabsload", function(event, ui) {
      pluginNewsCloseAlerts();
      pluginNewsToggleAlerts();
   });
});