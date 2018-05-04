pluginNewsGetBaseUrl = function() {
   var ajax_baseurl = '../plugins/news/ajax';
   var path = document.location.pathname;
   // construct url for plugin pages
   if (path.indexOf('plugins/') !== -1) {
      var plugin_path = path.substring(path.indexOf('plugins'));
      var nb_directory = (plugin_path.match(/\//g) || []).length + 1;
      ajax_baseurl = Array(nb_directory).join("../") + 'plugins/news/ajax';
   }

   return ajax_baseurl;
};

pluginNewsCloseAlerts = function() {
   $(document).on("click", "a.plugin_news_alert-close",function() {
      var alert = $(this).parent(".plugin_news_alert");
      var id    = alert.attr('data-id');
      var ajax_baseurl = pluginNewsGetBaseUrl();
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