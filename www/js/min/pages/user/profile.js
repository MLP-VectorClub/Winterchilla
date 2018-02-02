"use strict";$(function(){var t=$content.children(".briefing").find(".username").text().trim();$(".personal-cg-say-what").on("click",function(t){t.preventDefault(),$.Dialog.info("About Personal Color Guides",'<p>The Personal Color Guide is a place where you can store and share colors for any of your OCs, similar to our <a href="/cg/">Official Color Guide</a>. You have full control over the colors and other metadata for any OCs you add to the system, and you can chose to keep your PCG publicly visible on your profile or make individual appearances private and share them with only certain people using a direct link.</p>\n\t\t\t<p><em>&ldquo;So where\'s the catch?&rdquo;</em> &mdash; you might ask. Everyone starts with 1 slot (10 points), which lets you add a single OC to your personal guide. This limit can be increased by joining <a href="https://mlp-vectorclub.deviantart.com/">our group</a> on DeviantArt, then fulfilling requests on this website. You will be given a point for every request you finish and we approve. In order to get an additional slot, you will need 10 points, which means 10 requests.</p>\n\t\t\t<p>If you have no use for your additional slots you may choose to give them away to other users - even non-members! The only restrictions are that only whole slots can be gifted and the free slot everyone starts out with cannot be gifted away. To share your hard-earned slots with others, simply visit their profile and click the <strong class="color-green"><span class="typcn typcn-gift"></span> Gift slots</strong> button under the Personal Color Guide section.</p>\n\t\t\t<br>\n\t\t\t<p><strong>However</strong>, there are a few things to keep in mind:</p>\n\t\t\t<ul>\n\t\t\t\t<li>You may only add characters made by you, for you, or characters you\'ve purchased to your Personal Color Guide. If we\'re asked to remove someone else\'s character from your guide we\'ll certainly comply. Please stick to species canon to the show, we\'re a pony community after all.</li>\n\t\t\t\t<li>Finished requests only count toward additional slots after they have been submitted to the group and have been accepted to the gallery. This is indicated by a tick symbol (<span class="color-green typcn typcn-tick"></span>) on the post throughout the site.</li>\n\t\t\t\t<li>A finished request does not count towards additional slots if you were the one who requested it in the first place. We\'re not against this behaviour generally, but allowing this would defeat the purpose of this feature: encouraging members to help others.</li>\n\t\t\t\t<li>Do not attempt to abuse the system in any way. Exploiting any bugs you may encounter instead of <a class="send-feedback">reporting them</a> could result in us disabling your access to this feature.</li>\n\t\t\t</ul>')});var e=$(".pending-reservations");e.length&&(e.on("click","button.cancel",function(){var t=$(this),a=t.prev();$.Dialog.confirm("Cancel reservation","Are you sure you want to cancel this reservation?",function(i){if(i){$.Dialog.wait(!1,"Cancelling reservation");var o=a.prop("hash").substring(1).split("-");$.post("/post/unreserve/"+o.join("/"),{FROM_PROFILE:!0},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);var a=this.pendingReservations;t.closest("li").fadeOut(1e3,function(){$(this).remove(),a&&(e.html($(a).children()),Time.Update())}),$.Dialog.close()}))}})}),e.on("click","button.fix",function(){var t=$(this).next().prop("hash").substring(1).split("-"),e=t[0],a=t[1],i=$.mk("form").attr("id","img-update-form").append($.mk("label").append($.mk("span").text("New image URL"),$.mk("input").attr({type:"text",maxlength:255,pattern:"^.{2,255}$",name:"image_url",required:!0,autocomplete:"off",spellcheck:"false"})));$.Dialog.request("Update image of "+e+" #"+a,i,"Update",function(t){t.on("submit",function(i){i.preventDefault();var o=t.mkData();$.Dialog.wait(!1,"Replacing image"),$.post("/post/set-image/"+e+"/"+a,o,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.success(!1,"Image has been updated"),$.Navigation.reload(!0)}))})})}));var a=$(".gift-pcg-slots");a.length&&a.on("click",function(e){e.preventDefault(),$.Dialog.wait("Gifting PCG slots to "+t,"Checking your available slot count"),$.post("/user/verify-giftable-slots",$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);var e=this.avail,a=$.mk("form","pcg-slot-gift-form").append($.mk("label").append("<p>Choose how many slots you want to give</p>",$.mk("input").attr({type:"number",name:"amount",min:1,max:e,value:1,step:1,class:"large-number-input",required:!0})));$.Dialog.request(!1,a,"Continue",function(e){e.on("submit",function(a){a.preventDefault();var i=e.mkData();if(isNaN(i.amount)||i.amount<1)return $.Dialog.fail(!1,"Invalid amount entered");i.amount=parseInt(i.amount,10);var o=1===i.amount?"":"s",n=1===i.amount?"is":"are";$.Dialog.confirm(!1,"<p>You are about to send <strong>"+i.amount+" slot"+o+"</strong> to <strong>"+t+"</strong>. The slots will be immediately removed from your account and a notification will be sent to "+t+" where they can choose to accept or reject your gift.</p><p>If they reject, the slot"+o+" "+n+" returned to you. You will be notified if they decide, and if they don't do so within <strong>2 weeks</strong> you may ask the staff to have your slot"+o+" refunded.</p><p>Are you sure?</p>",["Gift "+i.amount+" slot"+o+" to "+t,"Cancel"],function(e){e&&($.Dialog.wait(!1,"Sending gift"),$.post("/user/gift-pcg-slots/"+t,i,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.success(!1,this.message,!0)})))})})})}))});var i=$(".give-pcg-points");i.length&&i.on("click",function(e){e.preventDefault(),$.Dialog.wait("Giving PCG points to "+t,"Checking user's total points"),$.post("/user/get-deductable-points/"+t,$.mkAjaxHandler(function(){var e=$.mk("form","pcg-point-give-form").append($.mk("label").append("<p>Choose how many <strong>points</strong> you want to give. Enter a negative number to take points. You cannot take more points than what the user has, and the free slot cannot be taken away.</p><p><strong>Remember, 10 points = 1 slot!</strong></p>",$.mk("input").attr({type:"number",name:"amount",step:1,min:-this.amount,class:"large-number-input",required:!0})),$.mk("label").append("<p>Comment (optional, 2-140 chars.)</p>",$.mk("textarea").attr({name:"comment",maxlength:255})));$.Dialog.request(!1,e,"Continue",function(e){e.on("submit",function(a){a.preventDefault();var i=e.mkData();if(isNaN(i.amount))return $.Dialog.fail(!1,"Invalid amount entered");if(i.amount=parseInt(i.amount,10),0===i.amount)return $.Dialog.fail(!1,"You have to enter an integer that isn't 0");var o=Math.abs(i.amount),n=1===o?"":"s",s=i.amount>0,r=s?"give":"take",l=s?"to":"from";$.Dialog.confirm(!1,"<p>You are about to "+r+" <strong>"+o+" point"+n+"</strong> "+l+" <strong>"+t+"</strong>. The point"+n+" will be "+r+"n "+l+" them immediately, and they will not receive any notification on the site.</p><p>Are you sure?</p>",[$.capitalize(r)+" "+o+" point"+n+" "+l+" "+t,"Cancel"],function(e){e&&($.Dialog.wait(!1,"Giving points"),$.post("/user/give-pcg-points/"+t,i,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.segway(!1,this.message)})))})})})}))});var o=$("#signout"),n=$(".session-list"),s=t===$sidebar.children(".welcome").find(".un").text().trim();n.find("button.remove").off("click").on("click",function(e){e.preventDefault();var a="Deleting session",i=$(this).closest("li"),n=i.children(".browser").text().trim(),r=i.children(".platform"),l=r.length?" on <em>"+r.children("strong").text().trim()+"</em>":"";if(0===i.index()&&-1!==i.children().last().text().indexOf("Current"))return o.triggerHandler("click");var u=i.attr("id").replace(/\D/g,"");if(void 0===u||isNaN(u)||!isFinite(u))return $.Dialog.fail(a,"Could not locate Session ID, please reload the page and try again.");$.Dialog.confirm(a,(s?"You":t)+" will be signed out of <em>"+n+"</em>"+l+".<br>Continue?",function(t){t&&($.Dialog.wait(a,"Signing out of "+n+l),$.post("/user/sessiondel/"+u,$.mkAjaxHandler(function(){return this.status?0!==i.siblings().length?(i.remove(),$.Dialog.close()):void $.Navigation.reload(!0):$.Dialog.fail(a,this.message)})))})}),n.find("button.useragent").on("click",function(t){t.preventDefault();var e=$(this);$.Dialog.info("User Agent string for session #"+e.parents("li").attr("id").substring(8),"<code>"+e.data("agent")+"</code>")}),$("#sign-out-everywhere").on("click",function(){$.Dialog.confirm("Sign out from ALL sessions","This will invalidate ALL sessions. Continue?",function(e){e&&($.Dialog.wait(!1,"Signing out"),$.post("/da-auth/signout?everywhere",{name:t},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Navigation.reload(!0)})))})});var r=$("#discord-connect");r.find(".sync").on("click",function(e){e.preventDefault(),$.Dialog.wait("Syncing"),$.post("/discord-connect/sync/"+t,$.mkAjaxHandler(function(){this.status?$.Navigation.reload(!0):this.segway?$.Dialog.segway(!1,$.mk("div").attr("class","color-red").html(this.message)):$.Dialog.fail(!1,this.message)}))}),r.find(".unlink").on("click",function(e){e.preventDefault();var a=t===$sidebar.find(".user-data .name").text(),i=a?"you":"they",o=a?"your":"their";$.Dialog.confirm("Unlink Discord account","<p>If you unlink "+(a?"your":"this user's")+" Discord account "+i+" will no longer be able to use "+o+" Discord avatar on the site or submit new entries to events for Discord server members.</p>\n\t\t\t<p>Are you sure you want to unlink "+o+" Discord account?</p>",function(e){e&&($.Dialog.wait(!1),$.post("/discord-connect/unlink/"+t,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.segway(!1,this.message)})))})});var l=$("#discord-sync-info");if(l.length){var u=l.find("time"),c=parseInt(l.attr("data-cooldown"),10);u.data("dyntime-beforeupdate",function(t){t.time>c&&(l.find(".wait-message").remove(),r.find(".sync").enable(),u.removeData("dyntime-beforeupdate"))})}$("#unlink").on("click",function(t){t.preventDefault();var e="Unlink account & sign out";$.Dialog.confirm(e,"Are you sure you want to unlink your account?",function(t){t&&($.Dialog.wait(e,"Removing account link"),$.post("/da-auth/signout?unlink",$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Navigation.reload(!0)})))})});window._UserScroll=$.throttle(400,function(){$(".post-deviation-promise:not(.loading)").each(function(){var t=$(this);if(t.isInViewport()){var e=t.attr("data-post").replace("-","/"),a=t.attr("data-viewonly");t.addClass("loading"),$.get("/post/lazyload/"+e,{viewonly:a},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail("Cannot load "+e.replace("/"," #"),this.message);$.loadImages(this.html).then(function(e){var a=t.closest("li[id]");a.children(".image").replaceWith(e);var i=a.children(".image").find("img").attr("alt");i&&a.children(".label").removeClass("hidden").find("a").text(i)})}))}})}),$w.on("scroll mousewheel",window._UserScroll),window._UserScroll(),$(".awaiting-approval").on("click","button.check",function(t){t.preventDefault();var e=$(this).parents("li"),a=e.attr("id").split("-"),i=a[0],o=a[1];$.Dialog.wait("Deviation acceptance status","Checking"),$.post("/post/lock/"+i+"/"+o,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);e.remove(),$.Dialog.success(!1,this.message,!0)}))});var d=$("section.known-ips");d.on("click","button",function(){var e=$(this);e.disable(),$.post("/user/known-ips/"+t,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail("Load full list of known IPs",this.message);d.replaceWith(this.html)})).fail(function(){e.enable()})});var g=$("#settings"),h=g.find("form > label");g.on("submit","form",function(t){t.preventDefault();var e=$(this),a=e.attr("action"),i=e.mkData(),o=e.find('[name="value"]'),n=o.data("orig");$.Dialog.wait("Saving setting","Please wait"),$.post(a,i,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);o.is("[type=number]")?o.val(this.value):o.is("[type=checkbox]")&&(this.value=Boolean(this.value),o.prop("checked",this.value)),o.data("orig",this.value).triggerHandler("change"),function(t,e,a){switch(t){case"p_vectorapp":if(0===a.length&&0!==e.length){var i="app-"+e;return $("."+i).removeClass(i),$(".title h1 .vectorapp-logo").remove(),void $.Dialog.close()}$.Navigation.reload(!0);break;case"p_hidediscord":var o=$sidebar.find(".welcome .discord-join");a?o.length&&o.remove():o.length||$sidebar.find(".welcome .buttons").append('<a class="btn typcn discord-join" href="http://fav.me/d9zt1wv" target="_blank">Join Discord</a>'),$.Dialog.close();break;case"p_avatarprov":var n={};$(".avatar-wrap:not(.provider-"+a+")").each(function(){var t=$(this),e=t.attr("data-for");void 0===n[e]&&(n[e]=[]),n[e].push(t)}),e&&$(".provider-"+e+":not(.avatar-wrap)").removeClass("provider-"+e).addClass("provider-"+a);var s=!1;$.each(n,function(t,e){$.post("/user/avatar-wrap/"+t,$.mkAjaxHandler(function(){var a=this;if(!this.status)return s=!0,$.Dialog.fail("Update avatar elements for "+t,!1);$.each(e,function(t,e){e.replaceWith(a.html)})}))}),s||$.Dialog.close();break;case"p_disable_ga":if(a)return $.Dialog.wait(!1,"Performing a hard reload to remove user ID from the tracking code"),window.location.reload();$.Dialog.close();break;case"p_hidepcg":$.Dialog.wait("Navigation","Reloading page"),$.Navigation.reload();break;default:$.Dialog.close()}}(a.split("/").pop(),n,this.value)}))}),h.children("input[type=number]").each(function(){var t=$(this);t.data("orig",t.val().trim()).on("keydown keyup change",function(){var t=$(this);t.siblings(".save").attr("disabled",parseInt(t.val().trim(),10)===t.data("orig"))})}),h.children("input[type=checkbox]").each(function(){var t=$(this);t.data("orig",t.prop("checked")).on("keydown keyup change",function(){var t=$(this);t.siblings(".save").attr("disabled",t.prop("checked")===t.data("orig"))})}),h.children("select").each(function(){var t=$(this);t.data("orig",t.find("option:selected").val()).on("keydown keyup change",function(){var t=$(this),e=t.find("option:selected");t.siblings(".save").attr("disabled",e.val()===t.data("orig"))})})});
//# sourceMappingURL=/js/min/pages/user/profile.js.map
