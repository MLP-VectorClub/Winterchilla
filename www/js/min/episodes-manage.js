"use strict";DocReady.push(function(){function t(t){var e=$.mk("form").attr("id",t).append('<div class="label episode-only">\n\t\t\t\t<span>Season, Episode & Overall #</span>\n\t\t\t\t<div class=input-group-3>\n\t\t\t\t\t<input type="number" min="1" max="8" name="season" placeholder="Season #" required>\n\t\t\t\t\t<input type="number" min="1" max="26" name="episode" placeholder="Episode #" required>\n\t\t\t\t\t<input type="number" min="1" max="255" name="no" placeholder="Overall #" required>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class="label movie-only">\n\t\t\t\t<span>Overall movie number</span>\n\t\t\t\t<input type="number" min="1" max="26" name="episode" placeholder="Overall #" required>\n\t\t\t</div>\n\t\t\t<input class="movie-only" type="hidden" name="season" value="0">',$.mk("label").append("<span>Title (5-35 chars.)</span>",$.mk("input").attr({type:"text",minlength:5,name:"title",placeholder:"Title",autocomplete:"off",required:!0}).patternAttr(d)),'<div class="notice info align-center movie-only">\n\t\t\t\t<p>Include "Equestria Girls: " if applicable. Prefixes don’t count towards the character limit.</p>\n\t\t\t</div>\n\t\t\t<div class="label">\n\t\t\t\t<span>Air date & time</span>\n\t\t\t\t<div class="input-group-2">\n\t\t\t\t\t<input type="date" name="airdate" placeholder="YYYY-MM-DD" required>\n\t\t\t\t\t<input type="time" name="airtime" placeholder="HH:MM" required>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class="notice info align-center button-here">\n\t\t\t\t<p>Specify the <span class="episode-only">episode</span><span class="movie-only">movie</span>’s air date and time in <strong>your computer’s timezone</strong>.</p>\n\t\t\t</div>\n\t\t\t<label class="episode-only"><input type="checkbox" name="twoparter"> Has two parts</label>\n\t\t\t<div class="notice info align-center episode-only">\n\t\t\t\t<p>If this is checked, only specify the episode number of the first part</p>\n\t\t\t</div>');return $.mk("button").attr("class","episode-only").text("Set time to "+p+" this "+l).on("click",function(t){t.preventDefault(),$(this).parents("form").find('input[name="airdate"]').val(s).next().val(p)}).appendTo(e.children(".button-here")),e}function e(t){t.preventDefault();var e=$(this),i="edit-ep"===e.attr("id"),o=i?n?"S"+n+"E"+a:"Movie#"+a:e.closest("tr").attr("data-epid"),s=/^Movie/.test(o);$.Dialog.wait("Editing "+o,"Getting "+(s?"movie":"episode")+" details from server"),s&&(o="S0E"+o.split("#")[1]),$.post("/episode/get/"+o,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);var t=c.clone(!0,!0);t.find(s?".episode-only":".movie-only").remove(),s||t.find("input[name=twoparter]").prop("checked",!!this.ep.twoparter),delete this.ep.twoparter,(!this.caneditid||i&&$("#reservations, #requests").find("li").length)&&t.find("input").filter('[name="season"],[name="episode"]').disable();var e=moment(this.ep.airs);this.ep.airdate=$.momentToYMD(e),this.ep.airtime=$.momentToHM(e);var n=this.epid;delete this.epid,$.each(this.ep,function(e,i){t.find("input[name="+e+"]").val(i)}),$.Dialog.request(!1,t,"Save",function(t){t.on("submit",function(t){t.preventDefault();var e=$(this).mkData(),i=$.mkMoment(e.airdate,e.airtime);delete e.airdate,delete e.airtime,e.airs=i.toISOString(),$.Dialog.wait(!1,"Saving changes"),$.post("/episode/set/"+n,e,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.wait(!1,"Updating page",!0),$.Navigation.reload(function(){$.Dialog.close()})}))})})}))}var i=$("#content").find("table"),n=window.SEASON,a=window.EPISODE;/*!
  * Timezone data string taken from:
  * http://momentjs.com/downloads/moment-timezone-with-data.js
  * version 0.4.1 by Tim Wood, licensed MIT
  */
moment.tz.add("America/Los_Angeles|PST PDT PWT PPT|80 70 70 70|010102301010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010|-261q0 1nX0 11B0 1nX0 SgN0 8x10 iy0 5Wp0 1Vb0 3dB0 WL0 1qN0 11z0 1o10 11z0 1o10 11z0 1o10 11z0 1o10 11z0 1qN0 11z0 1o10 11z0 1o10 11z0 1o10 11z0 1o10 11z0 1qN0 WL0 1qN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1fz0 1a10 1fz0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1fz0 1cN0 1cL0 1cN0 1cL0 s10 1Vz0 LB0 1BX0 1cN0 1fz0 1a10 1fz0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1fz0 1a10 1fz0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 14p0 1lb0 14p0 1nX0 11B0 1nX0 11B0 1nX0 14p0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0 11B0 1nX0 14p0 1lb0 14p0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0 11B0 1nX0 14p0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0 11B0 1nX0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0");var o=moment.tz(new Date,"America/Los_Angeles").set({day:"Saturday",h:8,m:30,s:0}).local(),s=$.momentToYMD(o),p=$.momentToHM(o),l=o.format("dddd"),d=window.EP_TITLE_REGEX,r=new t("addep"),c=new t("editep");$("#add-episode, #add-movie").on("click",function(t){t.preventDefault();var e=/movie/.test(this.id),i=r.clone(!0,!0);i.find(e?".episode-only":".movie-only").remove(),$.Dialog.request("Add "+(e?"Movie":"Episode"),i,"Add",function(t){t.on("submit",function(i){i.preventDefault();var n=t.find("input[name=airdate]").attr("disabled",!0).val(),a=t.find("input[name=airtime]").attr("disabled",!0).val(),o=$.mkMoment(n,a).toISOString(),s=$(this).mkData({airs:o});$.Dialog.wait(!1,"Adding "+(e?"movie":"episode")+" to database"),$.post("/episode/add",s,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.wait(!1,"Opening "+(e?"movie":"episode")+" page",!0),$.Navigation.visit(this.url,function(){$.Dialog.close()})}))})})}),$("#edit-ep").on("click",e),i.on("click",".edit-episode",e).on("click",".delete-episode",function(t){t.preventDefault();var e=$(this),i=e.closest("tr").data("epid"),n=/^Movie/.test(i);$.Dialog.confirm("Deleting "+i,"<p>This will remove <strong>ALL</strong><ul><li>requests</li><li>reservations</li><li>video links</li><li>and votes</li></ul>associated with the "+(n?"movie":"episode")+", too.</p><p>Are you sure you want to delete it?</p>",function(t){t&&($.Dialog.wait(!1,"Removing episode"),$.post("/episode/delete/"+i,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Dialog.wait(!1,"Reloading page",!0),$.Navigation.reload(function(){$.Dialog.close()})})))})})});
//# sourceMappingURL=/js/min/episodes-manage.js.map
