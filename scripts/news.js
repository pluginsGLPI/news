$(document).ready(function() {

   pluginNewsCloseAlerts = function() {
      $(document).on("click", "a.plugin_news_alert-close",function() {
         var alert = $(this).parent(".plugin_news_alert");
         var id    = alert.attr('data-id');
         var nurl  = "../plugins/news/ajax/hide_alert.php"
         if (window.location.href.indexOf('plugin') >= 0) {
            nurl   = "../../../plugins/news/ajax/hide_alert.php"
         }
         $.post(nurl, {'id' : id})
            .done(function() {
               alert.remove();
            });
      });
   };
});