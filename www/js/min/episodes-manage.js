"use strict";DocReady.push(function(){function t(t,e,i){return moment(t+"T"+e+(i?"Z":""))}function e(t){var e=$.mk("form").attr("id",t).append('<div class="label episode-only">\n\t\t\t\t<span>Season, Episode & Overall #</span>\n\t\t\t\t<div class=input-group-3>\n\t\t\t\t\t<input type="number" min="1" max="8" name="season" placeholder="Season #" required>\n\t\t\t\t\t<input type="number" min="1" max="26" name="episode" placeholder="Episode #" required>\n\t\t\t\t\t<input type="number" min="1" max="255" name="no" placeholder="Overall #" required>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class="label movie-only">\n\t\t\t\t<span>Overall movie number</span>\n\t\t\t\t<input type="number" min="1" max="26" name="episode" placeholder="Overall #" required>\n\t\t\t</div>\n\t\t\t<input class="movie-only" type="hidden" name="season" value="0">',$.mk("label").append("<span>Title (5-35 chars.)</span>",$.mk("input").attr({type:"text",minlength:5,name:"title",placeholder:"Title",autocomplete:"off",required:!0}).patternAttr(m)),'<div class="notice info align-center movie-only">\n\t\t\t\t<p>Include "Equestria Girls: " if applicable. Prefixes don\'t count towards the character limit.</p>\n\t\t\t</div>\n\t\t\t<div class="label">\n\t\t\t\t<span>Air date & time</span>\n\t\t\t\t<div class="input-group-2">\n\t\t\t\t\t<input type="date" name="airdate" placeholder="YYYY-MM-DD" required>\n\t\t\t\t\t<input type="time" name="airtime" placeholder="HH:MM" required>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class="notice info align-center button-here">\n\t\t\t\t<p>Specify the <span class="episode-only">episode</span><span class="movie-only">movie</span>\'s air date and time in <strong>your computer\'s timezone</strong>.</p>\n\t\t\t</div>\n\t\t\t<label class="episode-only"><input type="checkbox" name="twoparter"> Has two parts</label>\n\t\t\t<div class="notice info align-center episode-only">\n\t\t\t\t<p>If this is checked, only specify the episode number of the first part</p>\n\t\t\t</div>');return $.mk("button").attr("class","episode-only").text("Set time to "+r+" this "+c).on("click",function(t){t.preventDefault(),$(this).parents("form").find('input[name="airdate"]').val(l).next().val(r)}).appendTo(e.children(".button-here")),e}function i(e){e.preventDefault();var i=$(this),n="edit-ep"===i.attr("id"),s=n?a?"S"+a+"E"+o:"Movie#"+o:i.closest("tr").attr("data-epid"),l=/^Movie/.test(s);$.Dialog.wait("Editing "+s,"Getting "+(l?"movie":"episode")+" details from server"),$.post("/episode/get",{epid:s},$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);var e=v.clone(!0,!0);e.find(l?".episode-only":".movie-only").remove(),l||e.find("input[name=twoparter]").prop("checked",!!this.ep.twoparter),delete this.ep.twoparter,(!this.caneditid||n&&$("#reservations, #requests").find("li").length)&&e.find("input").filter('[name="season"],[name="episode"]').disable();var i=moment(this.ep.airs);this.ep.airdate=p(i),this.ep.airtime=d(i);var a=this.epid;delete this.epid,$.each(this.ep,function(t,i){e.find("input[name="+t+"]").val(i)}),$.Dialog.request(!1,e,"Save",function(e){e.on("submit",function(e){e.preventDefault();var i=$(this).mkData(),n=t(i.airdate,i.airtime);delete i.airdate,delete i.airtime,i.airs=n.toISOString(),$.Dialog.wait(!1,"Saving changes"),i.epid=a,$.post("/episode/edit",i,$.mkAjaxHandler(function(){return this.status?($.Dialog.wait(!1,"Updating page",!0),void $.Navigation.reload(function(){$.Dialog.close()})):$.Dialog.fail(!1,this.message)}))})})}))}var n=$("#content").find("table"),a=window.SEASON,o=window.EPISODE;/*!
  * Timezone data string taken from:
  * http://momentjs.com/downloads/moment-timezone-with-data.js
  * version 0.4.1 by Tim Wood, licensed MIT
  */
moment.tz.add("America/Los_Angeles|PST PDT PWT PPT|80 70 70 70|010102301010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010101010|-261q0 1nX0 11B0 1nX0 SgN0 8x10 iy0 5Wp0 1Vb0 3dB0 WL0 1qN0 11z0 1o10 11z0 1o10 11z0 1o10 11z0 1o10 11z0 1qN0 11z0 1o10 11z0 1o10 11z0 1o10 11z0 1o10 11z0 1qN0 WL0 1qN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1fz0 1a10 1fz0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1fz0 1cN0 1cL0 1cN0 1cL0 s10 1Vz0 LB0 1BX0 1cN0 1fz0 1a10 1fz0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 1cN0 1fz0 1a10 1fz0 1cN0 1cL0 1cN0 1cL0 1cN0 1cL0 14p0 1lb0 14p0 1nX0 11B0 1nX0 11B0 1nX0 14p0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0 11B0 1nX0 14p0 1lb0 14p0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0 11B0 1nX0 14p0 1lb0 14p0 1lb0 14p0 1nX0 11B0 1nX0 11B0 1nX0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Rd0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0 Op0 1zb0");var s=moment.tz(new Date,"America/Los_Angeles").set({day:"Saturday",h:8,m:30,s:0}).local(),p=function(t){return t.format("YYYY-MM-DD")},d=function(t){return t.format("HH:mm")},l=p(s),r=d(s),c=s.format("dddd"),m=window.EP_TITLE_REGEX,u=($content.children("h1").first(),new e("addep")),v=new e("editep");$("#add-episode, #add-movie").on("click",function(e){e.preventDefault();var i=/movie/.test(this.id),n=u.clone(!0,!0);n.find(i?".episode-only":".movie-only").remove(),$.Dialog.request("Add "+(i?"Movie":"Episode"),n,"Add",function(e){e.on("submit",function(n){n.preventDefault();var a=e.find("input[name=airdate]").attr("disabled",!0).val(),o=e.find("input[name=airtime]").attr("disabled",!0).val(),s=t(a,o).toISOString(),p=$(this).mkData({airs:s});$.Dialog.wait(!1,"Adding "+(i?"movie":"episode")+" to database"),$.post("/episode/add",p,$.mkAjaxHandler(function(){return this.status?($.Dialog.wait(!1,"Opening "+(i?"movie":"episode")+" page",!0),void $.Navigation.visit(this.url,function(){$.Dialog.close()})):$.Dialog.fail(!1,this.message)}))})})}),$("#edit-ep").on("click",i),n.on("click",".edit-episode",i).on("click",".delete-episode",function(t){t.preventDefault();var e=$(this),i=e.closest("tr").data("epid"),n=/^Movie/.test(i);$.Dialog.confirm("Deleting "+i,"<p>This will remove <strong>ALL</strong><ul><li>requests</li><li>reservations</li><li>video links</li><li>and votes</li></ul>associated with the "+(n?"movie":"episode")+", too.</p><p>Are you sure you want to delete it?</p>",function(t){t&&($.Dialog.wait(!1,"Removing episode"),$.post("/episode/delete",{epid:i},$.mkAjaxHandler(function(){return this.status?($.Dialog.wait(!1,"Reloading page",!0),void $.Navigation.reload(function(){$.Dialog.close()})):$.Dialog.fail(!1,this.message)})))})})});
//# sourceMappingURL=/js/min/episodes-manage.js.map
