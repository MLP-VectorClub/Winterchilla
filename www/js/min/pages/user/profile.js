"use strict";!function(){var t=window,l=t.username,u=t.userId;$(".personal-cg-say-what").on("click",function(t){t.preventDefault(),$.Dialog.info("About Personal Color Guides",'<p>The Personal Color Guide is a place where you can store and share colors for any of your OCs, similar to our <a href="/cg/">Official Color Guide</a>. You have full control over the colors and other metadata for any OCs you add to the system, and you can chose to keep your PCG publicly visible on your profile or make individual appearances private and share them with only certain people using a direct link.</p>\n\t\t\t<p><em>&ldquo;So where\'s the catch?&rdquo;</em> &mdash; you might ask. Everyone starts with 1 slot (10 points), which lets you add a single OC to your personal guide. This limit can be increased by joining <a href="https://mlp-vectorclub.deviantart.com/">our group</a> on DeviantArt, then fulfilling requests on this website. You will be given a point for every request you finish and we approve. In order to get an additional slot, you will need 10 points, which means 10 requests.</p>\n\t\t\t<p>If you have no use for your additional slots you may choose to give them away to other users - even non-members! The only restrictions are that only whole slots can be gifted and the free slot everyone starts out with cannot be gifted away. To share your hard-earned slots with others, simply visit their profile and click the <strong class="color-green"><span class="typcn typcn-gift"></span> Gift slots</strong> button under the Personal Color Guide section.</p>\n\t\t\t<br>\n\t\t\t<p><strong>However</strong>, there are a few things to keep in mind:</p>\n\t\t\t<ul>\n\t\t\t\t<li>You may only add characters made by you, for you, or characters you\'ve purchased to your Personal Color Guide. If we\'re asked to remove someone else\'s character from your guide we\'ll certainly comply. Please stick to species canon to the show, we\'re a pony community after all.</li>\n\t\t\t\t<li>Finished requests only count toward additional slots after they have been submitted to the group and have been accepted to the gallery. This is indicated by a tick symbol (<span class="color-green typcn typcn-tick"></span>) on the post throughout the site.</li>\n\t\t\t\t<li>A finished request does not count towards additional slots if you were the one who requested it in the first place. We\'re not against this behaviour generally, but allowing this would defeat the purpose of this feature: encouraging members to help others.</li>\n\t\t\t\t<li>Do not attempt to abuse the system in any way. Exploiting any bugs you may encounter instead of <a class="send-feedback">reporting them</a> could result in us disabling your access to this feature.</li>\n\t\t\t</ul>')});var i=$(".pending-reservations");i.length&&(i.on("click","button.cancel",function(){var a=$(this),o=a.prev();$.Dialog.confirm("Cancel reservation","Are you sure you want to cancel this reservation?",function(t){if(t){$.Dialog.wait(!1,"Cancelling reservation");var e=o.prop("hash").substring(1).split("-")[1];$.API.delete("/post/"+e+"/reservation",{from:"profile"},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);var t=this.pendingReservations;a.closest("li").fadeOut(1e3,function(){$(this).remove(),t&&(i.html($(t).children()),Time.Update())}),$.Dialog.close()}))}})}),i.on("click","button.fix",function(){var o=$(this).next().prop("hash").substring(1).split("-")[1],t=$.mk("form").attr("id","img-update-form").append($.mk("label").append($.mk("span").text("New image URL"),$.mk("input").attr({type:"text",maxlength:255,pattern:"^.{2,255}$",name:"image_url",required:!0,autocomplete:"off",spellcheck:"false"})));$.Dialog.request("Update image of post #"+o,t,"Update",function(a){a.on("submit",function(t){t.preventDefault();var e=a.mkData();$.Dialog.wait(!1,"Replacing image"),$.API.put("/post/"+o+"/image",e,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.success(!1,"Image has been updated"),$.Navigation.reload(!0)}))})})}));var e=$("#gift-pcg-slots");e.length&&e.on("click",function(t){t.preventDefault(),$.Dialog.wait("Gifting PCG slots to "+l,"Checking your available slot count"),$.API.get("/user/pcg/giftable-slots",$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);var t=this.avail,e=$.mk("form","pcg-slot-gift-form").append($.mk("label").append("<p>Choose how many slots you want to give</p>",$.mk("input").attr({type:"number",name:"amount",min:1,max:t,value:1,step:1,class:"large-number-input",required:!0})));$.Dialog.request(!1,e,"Continue",function(i){i.on("submit",function(t){t.preventDefault();var e=i.mkData();if(isNaN(e.amount)||e.amount<1)return $.Dialog.fail(!1,"Invalid amount entered");e.amount=parseInt(e.amount,10);var a=1===e.amount?"":"s",o=1===e.amount?"is":"are";$.Dialog.confirm(!1,"<p>You are about to send <strong>"+e.amount+" slot"+a+"</strong> to <strong>"+l+"</strong>. The slots will be immediately removed from your account and a notification will be sent to "+l+" where they can choose to accept or reject your gift.</p><p>If they reject, the slot"+a+" "+o+" returned to you. You will be notified if they decide, and if they don't do so within <strong>2 weeks</strong> you may ask the staff to have your slot"+a+" refunded.</p><p>Are you sure?</p>",["Gift "+e.amount+" slot"+a+" to "+l,"Cancel"],function(t){t&&($.Dialog.wait(!1,"Sending gift"),$.API.post("/user/"+u+"/pcg/slots",e,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.success(!1,this.message,!0)})))})})})}))});var a=$("#give-pcg-points");a.length&&a.on("click",function(t){t.preventDefault(),$.Dialog.wait("Giving PCG points to "+l,"Checking user's total points"),$.API.get("/user/"+u+"/pcg/points",$.mkAjaxHandler(function(){var t=$.mk("form","pcg-point-give-form").append($.mk("label").append("<p>Choose how many <strong>points</strong> you want to give. Enter a negative number to take points. You cannot take more points than what the user has, and the free slot cannot be taken away.</p><p><strong>Remember, 10 points = 1 slot!</strong></p>",$.mk("input").attr({type:"number",name:"amount",step:1,min:-this.amount,class:"large-number-input",required:!0})),$.mk("label").append("<p>Comment (optional, 2-140 chars.)</p>",$.mk("textarea").attr({name:"comment",maxlength:255})));$.Dialog.request(!1,t,"Continue",function(r){r.on("submit",function(t){t.preventDefault();var e=r.mkData();if(isNaN(e.amount))return $.Dialog.fail(!1,"Invalid amount entered");if(e.amount=parseInt(e.amount,10),0===e.amount)return $.Dialog.fail(!1,"You have to enter an integer that isn't 0");var a=Math.abs(e.amount),o=1===a?"":"s",i=0<e.amount,n=i?"give":"take",s=i?"to":"from";$.Dialog.confirm(!1,"<p>You are about to "+n+" <strong>"+a+" point"+o+"</strong> "+s+" <strong>"+l+"</strong>. The point"+o+" will be "+n+"n "+s+" them immediately, and they will not receive any notification on the site.</p><p>Are you sure?</p>",[$.capitalize(n)+" "+a+" point"+o+" "+s+" "+l,"Cancel"],function(t){t&&($.Dialog.wait(!1,"Giving points"),$.API.post("/user/"+u+"/pcg/points",e,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.segway(!1,this.message)})))})})})}))});var r=$("#signout"),o=$(".session-list"),c=l===$sidebar.children(".welcome").find(".un").text().trim();o.find("button.remove").off("click").on("click",function(t){t.preventDefault();var e="Deleting session",a=$(this).closest("li"),o=a.children(".browser").text().trim(),i=a.children(".platform"),n=i.length?" on <em>"+i.children("strong").text().trim()+"</em>":"";if(0===a.index()&&-1!==a.children().last().text().indexOf("Current"))return r.triggerHandler("click");var s=a.attr("id").replace(/^session-/,"");if(void 0===s)return $.Dialog.fail(e,"Could not locate Session ID, please reload the page and try again.");$.Dialog.confirm(e,(c?"You":l)+" will be signed out of <em>"+o+"</em>"+n+".<br>Continue?",function(t){t&&($.Dialog.wait(e,"Signing out of "+o+n),$.API.delete("/user/session/"+s,$.mkAjaxHandler(function(){return this.status?0!==a.siblings().length?(a.remove(),$.Dialog.close()):void $.Navigation.reload(!0):$.Dialog.fail(e,this.message)})))})}),o.find("button.useragent").on("click",function(t){t.preventDefault();var e=$(this);$.Dialog.info("User-Agent for session "+e.parents("li").attr("id").substring(8),"<code>"+e.data("agent")+"</code>")}),$("#sign-out-everywhere").on("click",function(){$.Dialog.confirm("Sign out from ALL sessions","This will invalidate ALL sessions. Continue?",function(t){t&&($.Dialog.wait(!1,"Signing out"),$.API.post("/da-auth/signout?everywhere",{name:l},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Navigation.reload(!0)})))})});var n=$("#discord-connect");n.find(".sync").on("click",function(t){t.preventDefault(),$.Dialog.wait("Syncing"),$.post("/discord-connect/sync/"+l,$.mkAjaxHandler(function(){this.status?$.Navigation.reload(!0):this.segway?$.Dialog.segway(!1,$.mk("div").attr("class","color-red").html(this.message)):$.Dialog.fail(!1,this.message)}))}),n.find(".unlink").on("click",function(t){t.preventDefault();var e=l===$sidebar.find(".user-data .name").text(),a=e?"you":"they",o=e?"your":"their";$.Dialog.confirm("Unlink Discord account","<p>If you unlink "+(e?"your":"this user's")+" Discord account "+a+" will no longer be able to use "+o+" Discord avatar on the site or submit new entries to events for Discord server members.</p>\n\t\t\t<p>Are you sure you want to unlink "+o+" Discord account?</p>",function(t){t&&($.Dialog.wait(!1),$.post("/discord-connect/unlink/"+l,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.segway(!1,this.message)})))})});var s=$("#discord-sync-info");if(s.length){var d=s.find("time"),g=parseInt(s.attr("data-cooldown"),10);d.data("dyntime-beforeupdate",function(t){t.time>g&&(s.find(".wait-message").remove(),n.find(".sync").enable(),d.removeData("dyntime-beforeupdate"))})}window._UserScroll=$.throttle(400,function(){$(".post-deviation-promise:not(.loading)").each(function(){var o=$(this);if(o.isInViewport()){var t=o.attr("data-post").split("-").pop(),e=o.attr("data-viewonly");o.addClass("loading"),$.API.get("/post/"+t+"/lazyload",{viewonly:e},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail("Cannot load "+t.replace("/"," #"),this.message);$.loadImages(this.html).then(function(t){var e=o.closest("li[id]");e.children(".image").replaceWith(t.$el);var a=e.children(".image").find("img").attr("alt");a&&e.children(".label").removeClass("hidden").find("a").text(a)})}))}})}),$w.on("scroll mousewheel",window._UserScroll),window._UserScroll(),$(".awaiting-approval").on("click","button.check",function(t){t.preventDefault();var e=$(this).parents("li"),a=e.attr("id").split("-").pop();$.Dialog.wait("Deviation acceptance status","Checking"),$.API.post("/post/"+a+"/approval",$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);e.remove(),$.Dialog.success(!1,this.message,!0)}))});var p=$("#settings"),h=p.find("form > label");p.on("submit","form",function(t){t.preventDefault();var e=$(this),a=e.attr("action"),o=e.mkData(),i=e.find('[name="value"]'),n=i.data("orig");$.Dialog.wait("Saving setting","Please wait"),$.API.put(a,o,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);i.is("[type=number]")?i.val(this.value):i.is("[type=checkbox]")&&(this.value=Boolean(this.value),i.prop("checked",this.value)),i.data("orig",this.value).triggerHandler("change"),function(t,e,a){switch(t){case"p_vectorapp":if(0===a.length&&0!==e.length){var o="app-"+e;return $("."+o).removeClass(o),$(".title h1 .vectorapp-logo").remove(),$.Dialog.close()}$.Navigation.reload(!0);break;case"p_hidediscord":var i=$sidebar.find(".welcome .discord-join");a?i.length&&i.remove():i.length||$sidebar.find(".welcome .buttons").append('<a class="btn typcn discord-join" href="http://fav.me/d9zt1wv" target="_blank">Join Discord</a>'),$.Dialog.close();break;case"p_avatarprov":var n={};$(".avatar-wrap:not(.provider-"+a+")").each(function(){var t=$(this),e=t.attr("data-for");void 0===n[e]&&(n[e]=[]),n[e].push(t)}),e&&$(".provider-"+e+":not(.avatar-wrap)").removeClass("provider-"+e).addClass("provider-"+a);var s=!1;$.each(n,function(t,e){$.API.get("/user/"+t+"/avatar-wrap",$.mkAjaxHandler(function(){var a=this;if(!this.status)return s=!0,$.Dialog.fail("Update avatar elements for "+t,!1);$.each(e,function(t,e){e.replaceWith(a.html)})}))}),s||$.Dialog.close();break;case"p_disable_ga":if(a)return $.Dialog.wait(!1,"Performing a hard reload to remove user ID from the tracking code"),window.location.reload();$.Dialog.close();break;case"p_hidepcg":$.Dialog.wait("Navigation","Reloading page"),$.Navigation.reload();break;default:$.Dialog.close()}}(a.split("/").pop(),n,this.value)}))}),h.children("input[type=number]").each(function(){var t=$(this);t.data("orig",parseInt(t.val().trim(),10)).on("keydown keyup change",function(){var t=$(this);t.siblings(".save").attr("disabled",parseInt(t.val().trim(),10)===t.data("orig"))})}),h.children("input[type=checkbox]").each(function(){var t=$(this);t.data("orig",t.prop("checked")).on("keydown keyup change",function(){var t=$(this);t.siblings(".save").attr("disabled",t.prop("checked")===t.data("orig"))})}),h.children("select").each(function(){var t=$(this);t.data("orig",t.find("option:selected").val()).on("keydown keyup change",function(){var t=$(this),e=t.find("option:selected");t.siblings(".save").attr("disabled",e.val()===t.data("orig"))})})}();
//# sourceMappingURL=/js/min/pages/user/profile.js.map
