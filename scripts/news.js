$(document).ready(function() {

   pluginNewsCloseAlerts = function() {
      $(document).on("click", "a.plugin_news_alert-close",function() {
         var alert = $(this).parent(".plugin_news_alert");
         var id    = alert.attr('data-id');
         $.post("../plugins/news/ajax/hide_alert.php", {'id' : id})
            .done(function() {
               alert.remove();
            });
      });
   };

   pluginNewsDisplayOnSelfService = function() {
      // page index
      $("#page > .tab_cadre_postonly > tbody")
         .prepend("<tr><td colspan='2' id='alerts_inserted'></td></tr>");

      // page create ticket
      $("#page > form[name=helpdeskform]").prepend("<div id='alerts_inserted'></div>");

      $("#alerts_inserted").load("../plugins/news/ajax/display_alerts.php");
   }

   if (window.location.href.indexOf("helpdesk.public.php") > 0) {
      pluginNewsDisplayOnSelfService();
   }
});