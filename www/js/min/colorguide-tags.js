"use strict";DocReady.push(function(){var t=window.TAG_TYPES_ASSOC,e=$("#tags").children("tbody"),n=function(t,n){if(!this.status)return $.Dialog.fail(!1,this.message);if("function"==typeof t)return t.call(this,n);t.remove(),$.Dialog.success(!1,this.message);var a=window.location.pathname;0===e.children().length&&(a=a.replace(/(\d+)$/,function(t){return t>1?t-1:t})),$.toPage(a,!0,!0)},a=function(t){return $.mkAjaxHandler(function(){if(this.status||$.Dialog.fail(!1,this.message),this.counts){var n=this.counts;e.children().each(function(){var t=$(this).children(),e=parseInt(t.first().text().trim(),10);void 0!==n[e]&&t.last().children("span").text(n[e])})}t?$.Dialog.success(!1,this.message,!0):$.Dialog.close()})};window.CGTagEditing=function(e,i,o,s){switch(o){case"delete":$.Dialog.confirm("Detele tag: "+e,"Deleting this tag will also remove it from every appearance where it’s been used.<br>Are you sure?",["Delete it","Nope"],function(t){t&&($.Dialog.wait(!1,"Sending removal request"),$.post("/cg/tag/del/"+i,$.mkAjaxHandler(function(){n.call(this,s,o)})))});break;case"synon":case"merge":var r="merge"===o,c=r?"Merge":"Synonymize";$.Dialog.wait(c+" "+e+" "+(r?"into":"with")+" another tag","Retrieving tag list from server"),$.post("/cg/get-tags",{not:i,action:o},$.mkAjaxHandler(function(){if(!this.length)return this.undo?window.CGTagEditing.call(this,e,i,"unsynon",s):$.Dialog.fail(!1,this.message);var a=$.mk("form","tag-"+o),l=$.mk("select").attr("required",!0).attr("name","targetid"),g={},u=[];$.each(this,function(e,n){var a=n.type,i='<option value="'+n.tid+'">'+n.name+"</option>";if(!a)return l.append(i);void 0===g[a]&&(g[a]=$.mk("optgroup").attr("label",t[a]),u.push(a)),g[a].append(i)}),$.each(u,function(t,e){l.append(g[e])}),a.append("<p>"+(r?"Merging a tag into another will permanently delete it, while replacing it with the merge target on every appearance which used it.":"Synonymizing a tag will keep both tags in the database, but when searching, the source tag will automatically redirect to the target tag.")+"</p>",$.mk("label").append("<span>"+c+" <strong>"+e+"</strong> "+(r?"into":"with")+" the following:</span>",l)),$.Dialog.request(!1,a,c,function(t){t.on("submit",function(e){e.preventDefault();var a=t.mkData();$.Dialog.wait(!1,(r?"Merging":"Synonymizing")+" tags"),$.post("/cg/tag/"+o+"/"+i,a,$.mkAjaxHandler(function(){n.call(this,s,o)}))})})}));break;case"unsynon":var l=this.message;$.Dialog.close(function(){$.Dialog.confirm("Remove synonym from "+e,l,["Yes, continue…","Cancel"],function(t){if(t){var a=$.mk("div").html(l).find("strong").prop("outerHTML"),r=$.mk("form","synon-remove").html("<p>If you leave the option below checked, <strong>"+e+"</strong> will be added to all appearances where "+a+" is used, preserving how the tags worked while the synonym was active.</p>\n\t\t\t\t\t\t\t\t<p>If you made these tags synonyms by accident and don’t want <strong>"+e+"</strong> to be added to each appearance where "+a+' is used, you should uncheck the box below.</p>\n\t\t\t\t\t\t\t\t<label><input type="checkbox" name="keep_tagged" checked><span>Preserve current tag connections</span></label>');$.Dialog.request(!1,r,"Remove synonym",function(t){t.on("submit",function(e){e.preventDefault();var a=t.mkData();$.Dialog.wait(!1,"Removing synonym"),$.post("/cg/tag/unsynon/"+i,a,$.mkAjaxHandler(function(){n.call(this,s,o)}))})})}})});break;case"refresh":$.Dialog.wait("Refresh use count of "+e,"Updating use count"),$.post("/cg/tags/recount-uses",{tagids:i},a())}},e.on("click","button",function(t){t.preventDefault();var e=$(this),n=e.parents("tr"),a=n.children().eq(1).text().trim(),i=parseInt(n.children().first().text().trim(),10),o=this.className.split(" ").pop();window.CGTagEditing(a,i,o,n)}),$(".refresh-all").on("click",function(){var t=[];e.children().each(function(){t.push($(this).children().first().text().trim())}),$.Dialog.wait("Recalculate tag usage data","Updating use count"+(1!==t.length?"s":"")),$.post("/cg/tags/recount-uses",{tagids:t.join(",")},a(!0))})});
//# sourceMappingURL=/js/min/colorguide-tags.js.map
