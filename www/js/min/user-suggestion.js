"use strict";DocReady.push(function(){var e=$(".pending-reservations");e.on("click","#suggestion",function(t){t.preventDefault();var i=!1;$.Dialog.info("Request Roulette™",'<p>If you feel like making a vector but don’t have any screencap in mind, then you are in the right place.</p>\n\t\t\t<p>With this tool you can get a random request from the site instantly delivered straight to your screen. Club Members can choose to reserve the requests immediately, and everyone can ask for consequent suggestions. You are not forced to commit to a suggestion, whether you take it or leave it is all up to you.</p>\n\t\t\t<div class="align-center"><button id="suggestion-press" class="btn large orange typcn typcn-lightbulb">Give me a suggestion</button></button>',function(){var t=$("#dialogContent").find("#suggestion-press"),n=$.mk("ul","suggestion-output").insertAfter(t),s=$.mk("div").addClass("notice fail").hide().text("The image failed to load - just click the button again to get a different suggestion.").insertAfter(n),o=$.mk("div").addClass("notice info").hide().html("Loading Fluidbox plugin&hellip;").insertAfter(n),a=function(){$.post("/user/suggestion",$.mkAjaxHandler(function(){if(!this.status)return t.enable(),$.Dialog.fail(!1,this.message);var i=$(this.suggestion),o=i.attr("id");i.find("img").on("load",function(){var e=$(this);e.parents(".image").addClass("loaded"),e.parents("a").fluidboxThis()}).on("error",function(){s.show(),$(this).parents(".image").hide()}),i.find(".reserve-request").on("click",function(){var t=$(this);$.post("/post/reserve/"+o.replace("-","/"),{SUGGESTED:!0},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);t.replaceWith(this.button),e.html($(this.pendingReservations).children())}))}),n.html(i),t.enable()})).fail(function(){t.enable()})};t.on("click",function(e){e.preventDefault(),t.disable(),s.hide(),i?a():(o.show(),n.find(".screencap > a").fluidboxThis(function(){i=!0,o.remove(),a()}))})})})});
//# sourceMappingURL=/js/min/user-suggestion.js.map
