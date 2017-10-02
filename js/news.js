$(document).ready(function() {
   var ajax_baseurl = '../plugins/news/ajax';
   var path = document.location.pathname;
   // construct url for plugin pages
   if (path.indexOf('plugins/') !== -1) {
      var plugin_path = path.substring(path.indexOf('plugins'));
      var nb_directory = (plugin_path.match(/\//g) || []).length + 1;
      var ajax_baseurl = Array(nb_directory).join("../") + 'plugins/news/ajax';
   }
});

pluginNewsCloseAlerts = function() {
   $(document).on("click", "a.plugin_news_alert-close",function() {
      var alert = $(this).parent(".plugin_news_alert");
      var id    = alert.attr('data-id');
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