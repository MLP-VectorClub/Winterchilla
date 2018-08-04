"use strict";!function(){var e=!1,i=[],s=$("#send-hello"),a=$("#wss-status"),o=$("#wss-heartbeat"),l=$("#wss-response-time"),c=$("#connection-list"),d="Anonymous";!function t(){o.removeClass("beat");var n=(new Date).getTime();if($.WS.down||$.WS.conn.disconnected){if(!1!==e)return;return e=setInterval(t,1e3),a.removeClass("info success").addClass("fail").text($.WS.down?"Socket.IO server is down and/or client library failed to load":"Disconnected"),o.addClass("dead"),void s.disable()}if(!1!==e&&(clearInterval(e),e=!1,a.removeClass("info fail").addClass("success"),o.removeClass("dead"),s.enable()),c.is(":hover"))return setTimeout(t,500),void a.text("Paused while hovering entries");a.text("Connected"),$.WS.devquery("status",{},function(s){o.addClass("beat");var a=[],r={};Object.keys(s.clients).forEach(function(e){var t=s.clients[e];t.ip=t.ip.replace(/^::ffff:/,"");var n="ip-"+t.ip.replace(/[^a-f\d]/g,"-");a.push(n),r[n]||(r[n]=[]),r[n].push(t)}),0===a.length?c.empty():(c.children().filter(function(e,t){return!r[t.id]}).remove(),a.forEach(function(e){var t=r[e],n={},s={};t.forEach(function(e){e.page&&(n[e.page]={since:e.connectedSince}),e.username?s[e.username]=(s[e.username]||0)+1:s[d]=(s[d]||0)+1});var a=Object.keys(s),i=$(document.getElementById(e));0===i.length&&(i=$.mk("li",e),c.append(i)),i.empty().append("<h3>"+t[0].ip+"</h3>"),a.length&&i.append("<p><strong>Users:</strong></p>",$.mk("ul").append(a.map(function(e){var t=s[e];return $.mk("li").html((e!==d?'<a href="/@'+e+'" target="_blank">'+e+"</a>":d)+(1<t?" ("+t+")":""))})));var o=Object.keys(n);o.length&&i.append("<p><strong>Pages:</strong></p>",$.mk("ul").append(o.map(function(e){return $.mk("li").append($.mk("a").attr({href:e,target:"_blank"}).text(e)," ("+n[e].since+")")})))}));var e=(new Date).getTime();i.push(e-n),i=i.slice(-20),l.text($.average(i).toFixed(0)+"ms"),setTimeout(t,1e3)})}(),s.on("click",function(){$.Dialog.wait("Test PHP to WS server connectivity","Sending hello");var n=$.randomString(),e=$.WS.getClientId(),s=!1;$w.on("ws-hello",function(e,t){t.priv===n&&($w.off("ws-hello"),s=!0,$.Dialog.success(!1,"Hello response received",!0))}),$.API.get("/admin/wsdiag/hello",{priv:n,clientid:e},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);s||$.Dialog.success(!1,"Hello sent successfully"),s||$.Dialog.wait(!1,"Waiting for reply (timeout 5s)"),setTimeout(function(){s||($w.off("ws-hello"),$.Dialog.fail(!1,"Hello response timed out"))},5e3)}))})}();
//# sourceMappingURL=/js/min/pages/admin/wsdiag.js.map
