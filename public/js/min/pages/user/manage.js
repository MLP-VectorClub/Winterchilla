"use strict";!function(){if(void 0!==window.ROLES){var e=$content.children(".briefing"),t=(e.find(".username").text().trim(),e.find(".role-label").text().trim()),n=$.mk("form").attr("id","rolemod").html('<select name="newrole" required><optgroup label="Possible roles"></optgroup></select>'),i=n.find("optgroup"),o=$("#change-role"),a=$("#change-dev-role-mask");$.each(window.ROLES,function(e,t){i.append("<option value="+e+">"+t+"</option>")}),o.on("click",function(){var a=o.attr("data-for");$.Dialog.request("Change group",n.clone(),"Change",function(n){var i=n.find("option").filter(function(){return this.innerHTML===t}).attr("selected",!0);n.on("submit",function(e){if(e.preventDefault(),n.children("select").val()===i.attr("value"))return $.Dialog.close();var t=n.mkData();$.Dialog.wait(!1,"Moving user to the new group"),$.API.put("/user/"+a+"/role",t,$.mkAjaxHandler(function(){return!0===this.already_in?$.Dialog.close():this.status?void $.Navigation.reload(!0):$.Dialog.fail(!1,this.message)}))})})}),a.on("click",function(){$.Dialog.request(a.attr("title"),n.clone(),"Change",function(n){var i=n.find("option").filter(function(){return this.innerHTML===t}).attr("selected",!0);n.on("submit",function(e){if(e.preventDefault(),n.children("select").val()===i.attr("value"))return $.Dialog.close();var t=n.mkData();$.Dialog.wait(!1,"Changing role mask"),$.API.put("/setting/dev_role_label",t,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);$.Navigation.reload(!0)}))})})})}}();