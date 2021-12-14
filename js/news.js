/**
 * -------------------------------------------------------------------------
 * Plugin GLPI News
 *
 * Copyright (C) 2015-2021 by teclib.
 *
 * http://www.teclib.com
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Plugin GLPI News.
 *
 * Plugin GLPI News is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Plugin GLPI News is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Plugin GLPI News. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

pluginNewsCloseAlerts = function() {
    $(document).on("mousedown", ".plugin_news_alert .alert a[data-bs-dismiss=alert]", function(event) {
        var alert = $(this).closest(".plugin_news_alert");
        var id    = alert.attr('data-id');
        var a_url = CFG_GLPI.root_doc+"/"+GLPI_PLUGINS_PATH.news+"/ajax";
        $.post(a_url+"/hide_alert.php", {'id' : id});
    });
};

pluginNewsToggleAlerts = function() {
    $(document).on("click", ".plugin_news_alert-toggle",function() {
        var alert = $(this).closest(".plugin_news_alert");
        alert.toggleClass('expanded');
    });
}

$(function() {
    pluginNewsCloseAlerts();
    pluginNewsToggleAlerts();

    $(document).on('glpi.tab.loaded', function() {
        pluginNewsCloseAlerts();
        pluginNewsToggleAlerts();
    });
});