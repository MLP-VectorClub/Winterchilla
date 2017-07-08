"use strict";function _classCallCheck(e,o){if(!(e instanceof o))throw new TypeError("Cannot call a class as a function")}var _createClass=function(){function e(e,o){for(var t=0;t<o.length;t++){var n=o[t];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(e,n.key,n)}}return function(o,t,n){return t&&e(o.prototype,t),n&&e(o,n),o}}();!function(e){if(void 0===e.Navigation||!0!==e.Navigation.firstLoadDone){var o=function(o){o.fluidbox({immediateOpen:!0,loader:!0}).on("openstart.fluidbox",function(){$body.addClass("fluidbox-open"),e(this).parents("#dialogContent").length&&$body.addClass("fluidbox-in-dialog")}).on("openend.fluidbox",function(){var o=e(this),t=o.attr("href");o.data("href",t),o.removeAttr("href"),0===o.find(".fluidbox__ghost").children().length&&o.find(".fluidbox__ghost").append(e.mk("img").attr("src",t).css({opacity:0,width:"100%",height:"100%"}))}).on("closestart.fluidbox",function(){$body.removeClass("fluidbox-open");var o=e(this);o.attr("href",o.data("href")),o.removeData("href")}).on("closeend.fluidbox",function(){$body.removeClass("fluidbox-in-dialog")})};e.fn.fluidboxThis=function(t){var n=this;return"function"==typeof e.fn.fluidbox?(o(this),e.callCallback(t)):e.getScript("/js/min/jquery.ba-throttle-debounce.js",function(){e.getScript("/js/min/jquery.fluidbox.js",function(){o(n),e.callCallback(t)}).fail(function(){e.Dialog.fail(!1,"Loading Fluidbox plugin failed")})}).fail(function(){e.Dialog.fail(!1,"Loading Debounce/throttle plugin failed")}),this};var t=function(e,o){var t=void 0!==window.screenLeft?window.screenLeft:screen.left,n=void 0!==window.screenTop?window.screenTop:screen.top,i=window.innerWidth?window.innerWidth:document.documentElement.clientWidth?document.documentElement.clientWidth:screen.width;return{top:(window.innerHeight?window.innerHeight:document.documentElement.clientHeight?document.documentElement.clientHeight:screen.height)/2-o/2+n,left:i/2-e/2+t}};e.PopupOpenCenter=function(e,o,n,i){var a=t(n,i),r=window.open(e,o,"scrollbars=yes,width="+n+",height="+i+",top="+a.top+",left="+a.left);return window.focus&&r.focus(),r},e.PopupMoveCenter=function(e,o,n){var i=t(o,n);e.resizeTo(o,n),e.moveTo(i.left,i.top)};var n=window.OAUTH_URL,i=function(){return(~~(99999999*Math.random())).toString(36)};$d.on("click","#turbo-sign-in",function(o){o.preventDefault();var t=e(this),a=t.parent().html();t.disable(),n=t.attr("data-url");var r=i(),s=!1,l=void 0,c=void 0;window[" "+r]=function(){s=!0,"request"===e.Dialog._open.type?e.Dialog.clearNotice(/Redirecting you to DeviantArt/):e.Dialog.close(),c.close()};try{c=window.open(n+"&state="+r)}catch(o){return e.Dialog.fail(!1,"Could not open login pop-up. Please open another page")}e.Dialog.wait(!1,"Redirecting you to DeviantArt"),l=setInterval(function(){try{if(!c||c.closed){if(clearInterval(l),s)return;e.Dialog.fail(!1,a)}}catch(e){}},500)});var a=function(){console.log("> docReadyAlwaysRun()"),$d.triggerHandler("paginate-refresh"),e.LocalStorage.remove("cookie_consent");var o=e.LocalStorage.get("cookie_consent_v2");n=window.OAUTH_URL,e("#signin").off("click").on("click",function(){var t=e(this),a=function(o){if(o){e.Dialog.close(),e.LocalStorage.set("cookie_consent_v2",1),t.disable();var a=function(){e.Dialog.wait(!1,"Redirecting you to DeviantArt"),location.href=n+"&state="+encodeURIComponent(location.href.replace(location.origin,""))};if(-1!==navigator.userAgent.indexOf("Trident"))return a();e.Dialog.wait("Sign-in process","Opening popup window");var r=i(),s=!1,l=void 0,c=void 0,d=!1;window[" "+r]=function(o,n){if(clearInterval(l),!0!==o)s=!0,e.Dialog.success(!1,"Signed in successfully"),e.Dialog.wait(!1,"Reloading page"),d=!0,setTimeout(function(){c.close()},750),e.Navigation.reload(function(){d=!1,e.Dialog.close()});else{if(n.jQuery){var i=n.$("#content").children("h1").text(),a=n.$("#content").children(".notice").html();e.Dialog.fail(!1,'<p class="align-center"><strong>'+i+"</strong></p><p>"+a+"</p>"),c.close()}else e.Dialog.fail(!1,"Sign in failed, check popup for details.");t.enable()}};try{c=e.PopupOpenCenter(n+"&state="+r,"login","450","580")}catch(e){}var u=function(){if(!s){if(-1!==document.cookie.indexOf("auth="))return window[" "+r];e.Dialog.fail(!1,"Popup-based login unsuccessful"),a()}};l=setInterval(function(){try{c&&!c.closed||(clearInterval(l),u())}catch(e){}},500),$w.on("beforeunload",function(){s=!0,d||c.close()}),e.Dialog.wait(!1,"Waiting for you to sign in")}};o?a(!0):e.Dialog.confirm("Privacy Notice",'<p>We must inform you that our website will store cookies on your device to remember your logged in status between browser sessions.</p><p>If you would like to avoid these completly harmless pieces of text which are required to log in to this website, click "Decline" and continue browsing as a guest.</p><p><em>This warning will not appear again if you accept our use of persistent cookies.</em></p>',["Accept","Decline"],a)}),e("#signout").off("click").on("click",function(){e.Dialog.confirm("Sign out","Are you sure you want to sign out?",function(o){o&&(e.Dialog.wait("Sign out","Signing out"),e.post("/da-auth/signout",e.mkAjaxHandler(function(){if(!this.status)return e.Dialog.fail("Sign out",this.message);e.Navigation.reload(function(){e.Dialog.close()})})))})});try{if(/^https/.test(location.protocol))throw void 0;var t=e.SessionStorage.get("canhttps");if("false"===t)throw void 0;e.ajax({method:"POST",url:"https://"+location.host+"/ping",success:e.mkAjaxHandler(function(){this.status&&$sidebar.append(e.mk("a").attr({class:"btn green typcn typcn-lock-closed",href:location.href.replace(/^http:/,"https:")}).text("Switch to HTTPS")),e.SessionStorage.set("canhttps",t=this.status.toString())}),error:function(){e.SessionStorage.set("canhttps",t="false")}})}catch(e){}},r=function(){function o(t){if(_classCallCheck(this,o),this._options=e.extend({min:0,max:.98,intervalEnabled:!0,intervalDelay:800,randomIncrement:.08},t),this._$element=$header.find(".loading-indicator").children(".loading-circle"),1!==this._$element.length)throw new Error("Loader: Element not found");this._circumference=2*this._$element.attr("r")*Math.PI,this._$element.css("stroke-dasharray",this._circumference),this.val(1,1)}return _createClass(o,[{key:"show",value:function(){$body.addClass("loading"),this.val(0),this._startInterval()}},{key:"_startInterval",value:function(){var e=this;this._options.intervalEnabled&&void 0===this._interval&&(this._interval=setInterval(function(){e.inc(null,.5)},this._options.intervalDelay))}},{key:"_clearInterval",value:function(){if(void 0===this._interval)return!1;clearInterval(this._interval),this._interval=void 0}},{key:"enableInterval",value:function(){this._options.intervalEnabled=!0,this._startInterval()}},{key:"disableInterval",value:function(){this._options.intervalEnabled=!1,this._clearInterval()}},{key:"val",value:function(o,t){isNaN(o)||(this.at=e.rangeLimit(o,!1,this._options.min,t||this._options.max)),this._$element.css("stroke-dashoffset",this._circumference*(1-this.at))}},{key:"inc",value:function(e,o){if(isNaN(e)||(e=Math.random()*this._options.randomIncrement),this._options.max<this.at+e)return!1;this.val(this.at+e,o)}},{key:"finish",value:function(){this.val(1,1),this.hide()}},{key:"hide",value:function(){this._clearInterval(),$body.removeClass("loading")}}]),o}();e.Loader=new r;var s=function(){function o(){_classCallCheck(this,o),this._DocReadyHandlers=[],this._xhr=!1,this._lastLoadedPathname=window.location.pathname,this._lastLoadedHref=window.location.href,this.firstLoadDone=!1}return _createClass(o,[{key:"_docReady",value:function(){console.groupCollapsed("> _docReady()"),a(),console.groupEnd();for(var e=0,o=this._DocReadyHandlers.length;e<o;e++)try{console.group("DocReadyHandlers[%d]()",e),this._DocReadyHandlers[e].call(window),console.groupEnd()}catch(o){console.error("Error while executing DocReadyHandlers["+e+"]\n"+o.stack)}}},{key:"flushDocReady",value:function(){e(".marquee").trigger("destroy.simplemarquee").removeClass("marquee");for(var o=0,t=this._DocReadyHandlers.length;o<t;o++)"function"==typeof this._DocReadyHandlers[o].flush&&(this._DocReadyHandlers[o].flush(),console.log("Flushed DocReady handler #%d",o));this._DocReadyHandlers=[]}},{key:"_loadCSS",value:function(o,t,n){if(!o.length)return e.callCallback(n);console.groupCollapsed("Loading CSS");var i=this;!function a(r){if(r>=o.length)return console.groupEnd(),e.callCallback(n);var s=o[r];i=e.ajax({url:s,dataType:"text",success:function(o){o=o.replace(/url\((['"])?(?:\.\.\/)+/g,"url($1/"),$head.append(e.mk("style").attr("href",s).text(o)),console.log("%c#%d (%s)","color:green",r,s)},error:function(){console.log("%c#%d (%s)","color:red",r,s)},complete:function(){t(),a(r+1)}})}(0)}},{key:"_loadJS",value:function(o,t,n){if(!o.length)return e.callCallback(n);console.groupCollapsed("Loading JS");var i=this;!function a(r){if(r>=o.length)return console.groupEnd(),e.callCallback(n);var s=o[r];i._xhr=e.ajax({url:s,dataType:"text",success:function(o){$body.append(e.mk("script").attr("data-src",s).text(o)),console.log("%c#%d (%s)","color:green",r,s)},error:function(){console.log("%c#%d (%s)","color:red",r,s)},complete:function(){t(),a(r+1)}})}(0)}},{key:"visit",value:function(o,t,n){console.clear(),console.group("[AJAX-Nav] PING %s (block_reload: %s)",o,n);var i=this;if(!1!==i._xhr){try{i._xhr.abort(),console.log("Previous AJAX request aborted")}catch(e){}i._xhr=!1}e.Loader.show(),e.Loader.enableInterval();var a=e.ajax({url:o,data:{"via-js":!0},success:e.mkAjaxHandler(function(){if(i._xhr!==a)return console.log("%cAJAX request objects do not match, bail","color:red"),void console.groupEnd();if(!this.status)return $body.removeClass("loading"),e.Loader.finish(),i._xhr=!1,console.log("%cNavigation error %s","color:red",this.message),console.groupEnd(),e.Dialog.fail("Navigation error",this.message);e.Loader.val(.5),e.Loader.disableInterval(),o=new URL(this.responseURL).pathString+new URL(o).hash,$w.triggerHandler("unload"),window.OpenSidebarByDefault()||$sbToggle.trigger("sb-close");var r=this.css,s=this.js,l=this.content,c=this.sidebar,d=this.footer,u=this.title,f=this.avatar,h=this.signedIn;$main.empty();var g=!1,p=new URL(location.href),v=!n&&p.pathString===o;if(i.flushDocReady(),console.groupCollapsed("Checking JS files to skip..."),$body.children("script[src], script[data-src]").each(function(){var o=e(this),t=o.attr("src")||o.attr("data-src");if(v)return/min\/dialog\.js/.test(t)||o.remove(),!0;var n=s.indexOf(t);if(-1===n||/min\/(colorguide[.-]|events-manage|episodes-manage|moment-timezone|episode|Chart|user[.-])/.test(t)){if(t.includes("global"))return!(g=!0);o.remove()}else s.splice(n,1),console.log("%cSkipped %s","color:saddlebrown",t)}),console.log("%cFinished.","color:green"),console.groupEnd(),!1!==g)return console.log("%cFull page reload forced by changes in global.js","font-weight:bold;color:orange"),console.groupEnd(),i._xhr.abort(),void(location.href=o);console.groupCollapsed("Checking CSS files to skip...");$head.children("link[href], style[href]").each(function(){var o=e(this),t=o.attr("href"),n=r.indexOf(t);-1!==n?(r.splice(n,1),console.log("%cSkipped %s","color:saddlebrown",t)):o.attr("data-remove","true")}),console.log("%cFinished.","color:green"),console.groupEnd(),console.groupEnd(),console.group("[AJAX-Nav] GET %s",o);var m=0,y=r.length+s.length;$w.trigger("beforeunload"),i._loadCSS(r,function(){m++,e.Loader.val(.5+m/y*.5)},function(){$head.children("link[href], style[href]".replace(/href/g,"data-remove=true")).remove(),$main.addClass("pls-wait").html(l),$sidebar.html(c),e.WS.essentialElements(),$footer.html(d),Time.Update(),window.setUpcomingCountdown();var a=$header.find("nav").children();a.children().first().children("img").attr("src",f),a.children(":not(:first-child)").remove(),a.append($sidebar.find("nav").children().children().clone()),window.CommonElements(),n||history[p.pathString===o?"replaceState":"pushState"]({"via-js":!0},"",o),document.title=u,i._lastLoadedPathname=window.location.pathname,i._lastLoadedHref=window.location.href,i._loadJS(s,function(){m++,e.Loader.val(.5+m/y*.5)},function(){console.log("> $(document).ready() [simulated]"),i._docReady(),console.log("%cDocument ready handlers called","color:green"),console.groupEnd(),$main.removeClass("pls-wait"),h?e.WS.authme():e.WS.unauth(),e.WS.navigate(),e.callCallback(t),e.Loader.finish(),i._xhr=!1})})})});i._xhr=a}},{key:"reload",value:function(e){this.visit(location.pathname+location.search+location.hash,e)}}]),o}();e.Navigation=new s,window.DocReady={push:function(o,t){"function"==typeof t&&(o.flush=t),e.Navigation._DocReadyHandlers.push(o)}}}}(jQuery),$(function(){if(!$.Navigation.firstLoadDone){if($.Navigation.firstLoadDone=!0,console.log("[HTTP-Nav] > $(document).ready()"),console.group("[HTTP-Nav] GET "+window.location.pathname+window.location.search+window.location.hash),"serviceWorker"in navigator&&window.addEventListener("load",function(){navigator.serviceWorker.register("/sw.js").then(function(){}).catch(function(){})}),!0!==window.ServiceUnavailableError&&$.get("/footer-git",$.mkAjaxHandler(function(){this.footer&&$footer.prepend(this.footer)})),function(){var e=function(){setTimeout(function(){$w.trigger("resize")},510)};$sbToggle.off("click sb-open sb-close").on("click",function(e){e.preventDefault(),$sbToggle.trigger("sb-"+($body.hasClass("sidebar-open")?"close":"open"))}).on("sb-open sb-close",function(o){var t="close"===o.type.substring(3);$body[t?"removeClass":"addClass"]("sidebar-open");try{$.LocalStorage[t?"set":"remove"]("sidebar-closed","true")}catch(e){}e()});var o=void 0;try{o=$.LocalStorage.get("sidebar-closed")}catch(e){}"true"!==o&&window.OpenSidebarByDefault()&&($body.addClass("sidebar-open"),e())}(),function(){var e=void 0,o=void 0,t=function(){void 0!==o&&(clearInterval(o),o=void 0)},n=function o(){var n="function"==typeof e.parent&&0!==e.parent().length,i={},a=void 0,r=void 0;if(n&&(a=new Date,r=new Date(e.attr("datetime")),i=Time.Difference(a,r)),!n||i.past)return n&&(e.find(".marquee").trigger("destroy.simplemarquee"),e.parents("li").remove()),t(),window.setUpcomingCountdown();var s=void 0;i.time<Time.InSeconds.month&&0===i.month?(i.week>0&&(i.day+=7*i.week),s="in ",i.day>0&&(s+=i.day+" day"+(1!==i.day?"s":"")+" & "),i.hour>0&&(s+=i.hour+":"),s+=$.pad(i.minute)+":"+$.pad(i.second)):(t(),setTimeout(o,1e4),s=moment(r).from(a)),e.text(s)};window.setUpcomingCountdown=function(){var i=$("#upcoming");if(i.length){var a=i.children("ul").children();if(!a.length)return i.remove();e=a.first().find("time").addClass("nodt"),t(),o=setInterval(n,1e3),n(),i.find("li").each(function(){var e=$(this),o=e.children(".calendar"),t=moment(e.find(".countdown").data("airs")||e.find("time").attr("datetime"));o.children(".top").text(t.format("MMM")),o.children(".bottom").text(t.format("D"))}),Time.Update();var r=function(){a.find(".title").simplemarquee({speed:25,cycles:1/0,space:25,handleHover:!1,delayBetweenCycles:0}).addClass("marquee")};"function"!=typeof jQuery.fn.simplemarquee?$.ajax({url:"/js/min/jquery.simplemarquee.js",dataType:"script",cache:!0,success:r}):r()}},window.setUpcomingCountdown()}(),$(document).off("click",".send-feedback").on("click",".send-feedback",function(e){e.preventDefault(),e.stopPropagation(),$("#ctxmenu").hide(),$.Dialog.info($.Dialog.isOpen()?void 0:"Send feedback","<h3>How to send feedback</h3>\n\t\t\t<p>If you're having an issue with the site and would like to let us know or have an idea/feature request you’d like to share, here’s how:</p>\n\t\t\t<ul>\n\t\t\t\t<li><a href='https://discord.gg/0vv70fepSILbdJOD'>Join our Discord server</a> and describe your issue in the <strong>#support</strong> channel</li>\n\t\t\t\t<li><a href='http://mlp-vectorclub.deviantart.com/notes/'>Send a note </a>to the group on DeviantArt</li>\n\t\t\t\t<li><a href='mailto:seinopsys@gmail.com'>Send an e-mail</a> to seinopsys@gmail.com</li>\n\t\t\t\t<li>If you have a GitHub account, you can also  <a href=\""+$footer.find("a.issues").attr("href")+'">create an issue</a> on the project’s GitHub page.\n\t\t\t</ul>')}),$(document).off("click",".action--color-avg").on("click",".action--color-avg",function(e){e.preventDefault(),e.stopPropagation();var o="Color Average Calculator",t=function(){$.Dialog.close();var e=window.$ColorAvgFormTemplate.clone(!0,!0);$.Dialog.request(o,e,!1,function(){e.triggerHandler("added")})};if(void 0===window.$ColorAvgFormTemplate){$.Dialog.wait(o,"Loading form, please wait");var n="/js/min/global-color_avg_form.js";$.getScript(n,t).fail(function(){setTimeout(function(){$.Dialog.close(function(){$.Dialog.wait(o,"Loading script (attempt #2)"),$.getScript(n.replace(/min\./,""),t).fail(function(){$.Dialog.fail(o,"Form could not be loaded")})})},1)})}else t()}),$d.on("click","a[href]",function(e){if(e.which>2||e.ctrlKey||e.shiftKey)return!0;var o=this;return o.host!==location.host||(o.pathname===location.pathname&&o.search===location.search?o.protocol!==location.protocol||(e.preventDefault(),window._trighashchange=o.hash!==location.hash,!0===window._trighashchange&&history.replaceState(history.state,"",o.href),setTimeout(function(){delete window._trighashchange},1),void $w.triggerHandler("hashchange")):!/^.*\/[^.]*$/.test(o.pathname)||(0!==$(this).parents("#dialogContent").length&&$.Dialog.close(),e.preventDefault(),void $.Navigation.visit(this.href)))}),$w.on("popstate",function(e){var o=e.originalEvent.state,t=function(e,o){return $.Navigation.visit(e,o,!0)};if(null!==o&&!o["via-js"]&&!0===o.paginate)return $w.trigger("nav-popstate",[o,t]);$.Navigation._lastLoadedHref.replace(/#.*$/,"")!==location.href.replace(/#.*$/,"")?t(location.href):console.log("[AJAX-Nav] Hashchange detected, navigation blocked")}),$.isRunningStandalone()){var e=$body.scrollTop(),o=function(){if(window.WithinMobileBreakpoint()){var o=$body.scrollTop(),t=$header.outerHeight(),n=parseInt($header.css("top"),10);$header.css("top",o>e?Math.max(-t,n-(o-e)):Math.min(0,n+(e-o))),e=o}};$w.on("scroll",o),o()}!function(){function e(){var e=function(){c(),o||((o=io(t,{reconnectionDelay:1e4})).on("connect",function(){console.log("[WS] %cConnected","color:green"),$.WS.recvPostUpdates(void 0!==window.EpisodePage),$.WS.navigate()}),o.on("auth",n(function(e){s=!0,console.log("[WS] %cAuthenticated as "+e.name,"color:teal")})),o.on("auth-guest",n(function(){console.log("[WS] %cReceiving events as a guest","color:teal")})),o.on("notif-cnt",n(function(e){var o=e.cnt?parseInt(e.cnt,10):0;console.log("[WS] Unread notification count: %d",o),c(),0===o?a.stop().slideUp("fast",function(){r.empty(),i.empty()}):$.post("/notifications/get",$.mkAjaxHandler(function(){i.text(o),r.html(this.list),Time.Update(),l(),a.stop().slideDown()}))})),o.on("post-delete",n(function(e){if(e.type&&e.id){var o=e.type+"-"+e.id,t=$("#"+o+":not(.deleting)");console.log("[WS] Post deleted (postid=%s)",o),t.length&&(t.find(".fluidbox--opened").fluidbox("close"),t.find(".fluidbox--initialized").fluidbox("destroy"),t.attr({class:"deleted",title:"This post has been deleted; click here to hide"}).on("click",function(){var e=$(this);e[window.WithinMobileBreakpoint()?"slideUp":"fadeOut"](500,function(){e.remove()})}))}})),o.on("post-break",n(function(e){if(e.type&&e.id){var o=e.type+"-"+e.id,t=$("#"+o+":not(.admin-break)");console.log("[WS] Post broken (postid=%s)",o),t.length&&(t.find(".fluidbox--opened").fluidbox("close"),t.find(".fluidbox--initialized").fluidbox("destroy"),t.reloadLi())}})),o.on("post-add",n(function(e){e.type&&e.id&&window.EPISODE===e.episode&&window.SEASON===e.season&&($(".posts #"+e.type+"-"+e.id).length>0||$.post("/post/reload/"+e.type+"/"+e.id,$.mkAjaxHandler(function(){if(this.status&&!($(".posts #"+e.type+"-"+e.id).length>0)){var o=$(this.li);$(this.section).append(o),o.rebindFluidbox(),Time.Update(),o.rebindHandlers(!0).parent().reorderPosts(),console.log("[WS] Post added (postid="+e.type+"-#"+e.id+") to container "+this.section)}})))})),o.on("post-update",n(function(e){if(e.type&&e.id){var o=e.type+"-"+e.id,t=$("#"+o+":not(.deleting)");console.log("[WS] Post updated (postid=%s)",o),t.length&&t.reloadLi(!1)}})),o.on("entry-score",n(function(e){if(void 0!==e.entryid){var o=$("#entry-"+e.entryid);console.log("[WS] Entry score updated (entryid=%s, score=%s)",e.entryid,e.score),o.length&&o.refreshVoting()}})),o.on("disconnect",function(){s=!1,console.log("[WS] %cDisconnected","color:red")}))};window.io?e():$.ajax({url:t+"socket.io/socket.io.js",cache:"true",dataType:"script",success:e,statusCode:{404:function(){console.log("%c[WS] Server down!","color:red"),$.WS.down=!0,$sidebar.find(".notif-list").on("click",".mark-read",function(e){e.preventDefault(),$.Dialog.fail("Mark notification read",'The notification server appears to be down. Please <a class="send-feedback">let us know</a>, and sorry for the inconvenience.')})}}})}var o=void 0,t="https://ws."+location.hostname+":8667/",n=function(e){return function(o){if("string"==typeof o)try{o=JSON.parse(o)}catch(e){}e(o)}},i=void 0,a=void 0,r=void 0,s=!1,l=function(){r.off("click",".mark-read").on("click",".mark-read",function(e){e.preventDefault(),e.stopPropagation();var o=$(this);if(!o.is(":disabled")){var t=o.attr("data-id"),n={read_action:o.attr("data-value")},i=function(){o.css("opacity",".5").disable(),$.post("/notifications/mark-read/"+t,n,$.mkAjaxHandler(function(){if(!this.status)return o.css("opacity","").enable(),$.Dialog.fail("Mark notification as read",this.message)}))};n.read_action?$.Dialog.confirm("Actionable notification",'Please confirm your choice: <strong class="color-'+o.attr("class").replace(/^.*variant-(\w+)\b.*$/,"$1")+'">'+o.attr("title")+"</strong>",["Confirm","Cancel"],function(e){e&&($.Dialog.close(),i())}):i()}})},c=function(){0===(i=$sbToggle.children(".notif-cnt")).length&&(i=$.mk("span").attr({class:"notif-cnt",title:"New notifications"}).prependTo($sbToggle)),a=$sidebar.children(".notifications"),r=a.children(".notif-list"),l()};e(),$.WS=function(){var t=function(){return e()},i={postUpdates:!1,entryUpdates:!1};return t.down=!1,t.navigate=function(){if(void 0!==o){var e=location.pathname+location.search+location.hash;o.emit("navigate",{page:e})}},t.recvPostUpdates=function(e){if(void 0===o)return setTimeout(function(){t.recvPostUpdates(e)},2e3);"boolean"==typeof e&&i.postUpdates!==e&&o.emit("post-updates",String(e),n(function(o){if(!o.status)return console.log("[WS] %cpost-updates subscription status change failed (subscribe=%s)","color:red",e);i.postUpdates=e,$("#episode-live-update")[i.postUpdates?"removeClass":"addClass"]("hidden"),console.log("[WS] %c%s","color:green",o.message)}))},t.recvEntryUpdates=function(e){if(void 0===o)return setTimeout(function(){t.recvEntryUpdates(e)},2e3);"boolean"==typeof e&&i.entryUpdates!==e&&o.emit("entry-updates",String(e),n(function(o){if(!o.status)return console.log("[WS] %centry-updates subscription status change failed (subscribe=%s)","color:red",e);i.entryUpdates=e,$("#entry-live-update")[i.entryUpdates&&"contest"===window.EventType?"removeClass":"addClass"]("hidden"),console.log("[WS] %c%s","color:green",o.message)}))},t.authme=function(){void 0!==o&&!0!==s&&(console.log("[WS] %cReconnection needed for identity change","color:teal"),o.disconnect(0),setTimeout(function(){o.connect()},100))},t.unauth=function(){void 0!==o&&!0===s&&o.emit("unauth",null,function(e){if(!e.status)return console.log("[WS] %cUnauth failed","color:red");s=!1,console.log("[WS] %cAuthentication dropped","color:brown")})},t.disconnect=function(e){void 0!==o&&(console.log("[WS] Forced disconnect (reason="+e+")"),o.disconnect(0))},t.status=function(){if(void 0===o)return setTimeout(function(){t.status()},2e3);o.emit("status",null,n(function(e){console.log("[WS] Status: ID=%s; Name=%s; Rooms=%s",e.User.id,e.User.name,e.rooms.join(","))}))},t.devquery=function(e){var i=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},a=arguments.length>2&&void 0!==arguments[2]?arguments[2]:void 0;if(void 0===o)return setTimeout(function(){t.devquery(e,i,a)},2e3);o.emit("devquery",{what:e,data:i},n(function(e){if("function"==typeof a)return a(e);console.log("[WS] DevQuery "+(e.status?"Success":"Fail"),e)}))},t.essentialElements=function(){c()},t}()}(),$.Navigation._docReady(),console.log("%cDocument ready handlers called","color:green"),console.groupEnd()}}),$w.on("load",function(){$body.removeClass("loading")});
//# sourceMappingURL=/js/min/global.js.map
