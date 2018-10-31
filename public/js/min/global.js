"use strict";!function(){$.fn.fluidboxThis=function(e){return this.fluidbox({immediateOpen:!0,loader:!0}).on("openstart.fluidbox",function(){$body.addClass("fluidbox-open"),$(this).parents("#dialogContent").length&&$body.addClass("fluidbox-in-dialog")}).on("openend.fluidbox",function(){var e=$(this),t=e.attr("href");e.data("href",t),e.removeAttr("href"),0===e.find(".fluidbox__ghost").children().length&&e.find(".fluidbox__ghost").append($.mk("img").attr("src",t).css({opacity:0,width:"100%",height:"100%"}))}).on("closestart.fluidbox",function(){$body.removeClass("fluidbox-open");var e=$(this);e.attr("href",e.data("href")),e.removeData("href")}).on("closeend.fluidbox",function(){$body.removeClass("fluidbox-in-dialog")}),$.callCallback(e),this};var r,n,l,o,s=function(e,t){var n=void 0!==window.screenLeft?window.screenLeft:screen.left,o=void 0!==window.screenTop?window.screenTop:screen.top,i=window.innerWidth?window.innerWidth:document.documentElement.clientWidth?document.documentElement.clientWidth:screen.width;return{top:(window.innerHeight?window.innerHeight:document.documentElement.clientHeight?document.documentElement.clientHeight:screen.height)/2-t/2+o,left:i/2-e/2+n}};$.PopupOpenCenter=function(e,t,n,o){var i=s(n,o),a=window.open(e,t,"scrollbars=yes,width="+n+",height="+o+",top="+i.top+",left="+i.left);return window.focus&&a.focus(),a},$.PopupMoveCenter=function(e,t,n){var o=s(t,n);e.resizeTo(t,n),e.moveTo(o.left,o.top)},$d.on("click","#turbo-sign-in",function(e){e.preventDefault();var t=$(this),n=t.parent().html();t.disable();var o=!1,i=void 0,a=void 0;window.__authCallback=function(){o=!0,"request"===$.Dialog._open.type?$.Dialog.clearNotice(/Redirecting you to DeviantArt/):$.Dialog.close(),a.close()};try{a=window.open("/da-auth/begin")}catch(e){return $.Dialog.fail(!1,"Could not open login pop-up. Please open another page")}$.Dialog.wait(!1,"Redirecting you to DeviantArt"),i=setInterval(function(){try{if(!a||a.closed){if(clearInterval(i),o)return;$.Dialog.fail(!1,n)}}catch(e){}},500)}),$.Navigation={visit:function(e){window.location.href=e},reload:function(){0<arguments.length&&void 0!==arguments[0]&&arguments[0]&&$.Dialog.wait(!1,"Reloading page",!0),window.location.reload()}},$sbToggle.off("click sb-open sb-close").on("click",function(e){e.preventDefault(),window.sidebarForcedVisible()||$sbToggle.trigger("sb-"+($body.hasClass("sidebar-open")?"close":"open"))}).on("sb-open sb-close",function(e){var t="close"===e.type.substring(3);$body[t?"removeClass":"addClass"]("sidebar-open");try{$.LocalStorage[t?"set":"remove"]("sidebar-closed","true")}catch(e){}setTimeout(function(){$w.trigger("resize")},510)}),n=r=void 0,l=function(){void 0!==n&&(clearInterval(n),n=void 0)},o=function e(){var t="function"==typeof r.parent&&0!==r.parent().length,n={},o=void 0,i=void 0;if(t&&(o=new Date,i=new Date(r.attr("datetime")),n=Time.Difference(o,i)),!t||n.past)return l(),void $.API.get("/about/upcoming",$.mkAjaxHandler(function(){if(!this.status)return console.error("Failed to load upcoming event list: "+this.message);var e=$("#upcoming");e.find("ul").html(this.html),this.html?e.removeClass("hidden"):e.addClass("hidden"),window.setUpcomingCountdown()}));var a=void 0;n.time<Time.InSeconds.month&&0===n.month?(0<n.week&&(n.day+=7*n.week),a="in ",0<n.day&&(a+=n.day+" day"+(1!==n.day?"s":"")+" & "),0<n.hour&&(a+=n.hour+":"),a+=$.pad(n.minute)+":"+$.pad(n.second)):(l(),setTimeout(e,1e4),a=moment(i).from(o)),r.text(a)},window.setUpcomingCountdown=function(){var e=$("#upcoming");if(e.length){var t=e.children("ul").children();if(!t.length)return e.addClass("hidden");e.removeClass("hidden"),r=t.first().find("time").addClass("nodt"),l(),n=setInterval(o,1e3),o(),e.find("li").each(function(){var e=$(this),t=e.children(".calendar"),n=moment(e.find(".countdown").data("airs")||e.find("time").attr("datetime"));t.children(".top").text(n.format("MMM")),t.children(".bottom").text(n.format("D"))}),Time.Update(),t.find(".title").simplemarquee({speed:25,cycles:1/0,space:25,handleHover:!1,delayBetweenCycles:0}).addClass("marquee")}},window.setUpcomingCountdown(),$(document).off("click",".send-feedback").on("click",".send-feedback",function(e){e.preventDefault(),e.stopPropagation(),$("#ctxmenu").hide();var t=["seinopsys","gmail.com"].join("@");$.Dialog.info($.Dialog.isOpen()?void 0:"Contact Us","<h3>How to contact us</h3>\n\t\t\t<p>You can use any of the following methods to reach out to us:</p>\n\t\t\t<ul>\n\t\t\t\t<li><a href='https://discord.gg/0vv70fepSILbdJOD'>Join our Discord server</a> and describe your issue/idea in the <strong>#support</strong> channel</li>\n\t\t\t\t<li><a href='https://www.deviantart.com/mlp-vectorclub/notes/'>Send a note </a>to the group on DeviantArt</li>\n\t\t\t\t<li><a href='mailto:"+t+"'>Send an e-mail</a> to "+t+"</li>\n\t\t\t</ul>")});var i=$.mk("form","color-avg-form").on("added",function(){var t=$(this).on("submit",function(e){e.preventDefault(),$.Dialog.close()}),n=$.mk("td").attr("class","color-red"),o=$.mk("td").attr("class","color-green"),i=$.mk("td").attr("class","color-darkblue"),d=$.mk("td").attr("colspan","3"),c=$.mk("span").css({position:"absolute",top:0,left:0,width:"100%",height:"100%",display:"block"}).html("&nbsp;"),e=$.mk("td").attr("rowspan","2").css({width:"25%",position:"relative"}).append(c),a=function(){var a=0,r=0,l=0,s=0;t.find(".input-group-3").each(function(){var e=$(this).children("[type=number]"),t=e.eq(0).val(),n=e.eq(1).val(),o=e.eq(2).val();if(t.length&&n.length&&o.length){var i={r:parseInt(t,10),g:parseInt(n,10),b:parseInt(o,10)};!isNaN(i.r)&&0<=i.r&&i.r<=255&&!isNaN(i.g)&&0<=i.g&&i.g<=255&&!isNaN(i.b)&&0<=i.b&&i.b<=255&&(a++,r+=parseInt(i.r,10),l+=parseInt(i.g,10),s+=parseInt(i.b,10))}else e.attr("required",0<t.length+n.length+o.length)}),a&&(r=Math.round(r/a),l=Math.round(l/a),s=Math.round(s/a)),n.text(r),o.text(l),i.text(s);var e=new $.RGBAColor(r,l,s).toString();c.css("background-color",e),d.text(e)},r=$("<input type='number' min='0' max='255' step='1' class='align-center'>"),l=$.mk("div").attr("class","input-group-3").append(r.clone().attr("placeholder","Red"),r.clone().attr("placeholder","Green"),r.clone().attr("placeholder","Blue"),$("<input type='text' pattern='^#?([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$' maxlength='7' placeholder='HEX' class='align-center color-ui' spellcheck='false'>").on("change blur",function(e){e.stopPropagation();var t=$(this);if(t.is(":valid")&&0!==t.val().trim().length){var n=t.siblings(),o=$.RGBAColor.parse(t.val().toUpperCase());t.val(o.toHex()),n.eq(0).val(o.red),n.eq(1).val(o.green),n.eq(2).val(o.blue).triggerHandler("change")}})),s=$.mk("div").attr("class","inputs"),u=function(){s.empty();for(var e=0;e<10;e++)s.append(l.clone(!0,!0));a()};l.children().on("paste",function(){var t=$(this);setTimeout(function(){if(t.is(":valid")){t.val(t.val().trim()).triggerHandler("change");var e=t.index()<2?t.next():t.parent().next().children().first();e.length&&e.focus()}},1)}).on("change keyup blur",a),t.append(s,$.mk("div").attr("class","btn-group").append($.mk("button").attr("class","green typcn typcn-plus").text("Add row").on("click",function(e){e.preventDefault(),s.append(l.clone(!0,!0))}),$.mk("button").attr("class","orange typcn typcn-times").text("Reset form").on("click",function(e){e.preventDefault(),u()})),$.mk("table").attr({class:"align-center",style:'display:table;width:100%;font-family:"Source Code Pro","Consolas",monospace;font-size:1.3em;border-collapse:collapse'}).append($.mk("tr").append(e,n,o,i),$.mk("tr").append(d)).find("td").css("border","1px solid black").end()),u()});$(document).off("click",".action--color-avg").on("click",".action--color-avg",function(e){e.preventDefault(),e.stopPropagation();var t=i.clone(!0,!0);$.Dialog.request("Color Average Calculator",t,!1,function(){t.triggerHandler("added")})});var a=$("html");if($.isRunningStandalone()){var d=a.scrollTop(),e=function(){if(window.withinMobileBreakpoint()&&!a.is(":animated")){var e=a.scrollTop(),t=$header.outerHeight(),n=parseInt($header.css("top"),10);$header.css("top",d<e?Math.max(-t,n-(e-d)):Math.min(0,n+(d-e))),d=e}};$d.on("scroll",e),e()}var t=$("#to-the-top").on("click",function(e){e.preventDefault(),a.stop().animate({scrollTop:0},200),t.removeClass("show")});function c(){if(window.withinMobileBreakpoint()&&!a.is(":animated")){var e=0!==a.scrollTop();!e&&t.hasClass("show")?t.removeClass("show"):e&&!t.hasClass("show")&&t.addClass("show")}}$d.on("scroll",c),c(),$("#signin").off("click").on("click",function(){var i=$(this);i.disable();var e=function(){$.Dialog.wait(!1,"Redirecting you to DeviantArt"),$.Navigation.visit("/da-auth/begin?return="+encodeURIComponent($.hrefToPath(location.href)))};if(-1!==navigator.userAgent.indexOf("Trident"))return e();$.Dialog.wait("Sign-in process","Opening popup window");var a=!1,r=void 0,l=void 0,t=null;window.__authCallback=function(e,t){if(clearInterval(r),!0!==e)a=!0,$.Dialog.success(!1,"Signed in successfully"),l.close(),$.Navigation.reload(!0);else{if(t.jQuery){var n=t.$("#content").children("h1").html(),o=t.$("#content").children(".notice").html();$.Dialog.fail(!1,'<p class="align-center"><strong>'+n+"</strong></p><p>"+o+"</p>"),l.close()}else $.Dialog.fail(!1,"Sign in failed, check popup for details.");i.enable()}};try{l=$.PopupOpenCenter("/da-auth/begin","login","450","580"),t=new Date}catch(e){}r=setInterval(function(){try{l&&!l.closed||(clearInterval(r),function(){if(t=null,!a){if(-1!==document.cookie.indexOf("auth="))return window.__authCallback;if(t&&(new Date).getTime()-t.getTime()<4e3)return $.Dialog.confirm(!1,"Popup-based login failed."),e();$.Dialog.close(),i.enable()}}())}catch(e){}},500),$w.on("beforeunload",function(){a=!0,l.close()}),$.Dialog.wait(!1,"Waiting for you to sign in")}),$("#signout").off("click").on("click",function(){var t="Sign out";$.Dialog.confirm(t,"Are you sure you want to sign out?",function(e){e&&($.Dialog.wait(t,"Signing out"),$.API.post("/da-auth/sign-out",$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(t,this.message);$.Navigation.reload()})))})});var u=$(".logged-in.updating-session");if(u.length){var p="Session refresh issue";setTimeout(function e(){null!==u&&$.API.get("/da-auth/status",$.mkAjaxHandler(function(){if(null!==u){if(!this.status)return $.Dialog.fail(p,this.message);if(!0===this.updating)return setTimeout(e,1e3);!0===this.deleted&&$.Dialog.fail(p,"We couldn't refresh your DeviantArt session automatically so you have been signed out. Due to elements on the page assuming you are signed in some actions will not work as expected until the page is reloaded."),u.html(this.loggedIn).removeClass("updating-session")}}))},1e3)}window.ServiceUnavailableError||$body.swipe($.throttle(10,function(e,t){if(!window.sidebarForcedVisible()&&$body.hasClass("sidebar-open")){var n=Math.abs(t.x),o=Math.abs(t.y),i=Math.min($body.width()/2,200);"left"!==e.x||n<i||75<o||$sbToggle.trigger("click")}}))}(),$w.on("load",function(){$body.removeClass("loading")});
//# sourceMappingURL=/js/min/global.js.map
