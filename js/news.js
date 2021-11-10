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