"use strict";!function(){var r=$("#sort-by"),n=$("#full-list"),i=$("#guide-reorder"),a=$("#guide-reorder-cancel"),s=!!window.EQG;r.on("change",function(){var e=r.data("base-url"),t=r.val(),a=e+"?ajax&sort_by="+t;$.Dialog.wait("Changing sort order"),$.get(a,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);n.html(this.html),l(),i.prop("disabled",0<t.length),history.replaceState(history.state,"",this.stateUrl),$.Dialog.close()}))});var o=new IntersectionObserver(function(e){e.forEach(function(e){if(e.isIntersecting){var t=e.target;o.unobserve(t);var a=new Image;a.src=t.dataset.src,$(a).on("load",function(){t.classList.contains("border")&&a.classList.add("border"),$(t).replaceWith(a).css("opacity",0).animate({opacity:1},300)})}})});function l(){n.find("section > ul .image-promise").each(function(e,t){return o.observe(t)})}l(),"function"==typeof window.Sortable&&(n.on("click",".sort-alpha",function(){var e=$(this).closest("section").children("ul");e.children().sort(function(e,t){return $(e).text().trim().localeCompare($(t).text().trim())}).appendTo(e)}),i.on("click",function(){if(i.hasClass("typcn-tick")){$.Dialog.wait("Re-ordering appearances");var e=[];n.children().children("ul").children().each(function(){e.push($(this).children().attr("data-href").split("/").pop().replace(/^(\d+)\D.*$/,"$1"))});var t={list:e.join(","),ordering:r.val()};s&&(t.eqg=!0),$.API.post("/cg/full/reorder",t,$.mkAjaxHandler(function(){if(!this.status)return $.Dialog.fail(!1,this.message);n.removeClass("sorting").html(this.html),l(),i.removeClass("typcn-tick green").addClass("typcn-arrow-unsorted darkblue").html("Re-order"),a.addClass("hidden"),$.Dialog.close()}))}else i.removeClass("typcn-arrow-unsorted darkblue").addClass("typcn-tick green").html("Save"),n.addClass("sorting").children().each(function(){var e=$(this).children("ul");e.children().each(function(){var e=$(this);e.data("orig-index",e.index())}).children().moveAttr("href","data-href"),e.data("sortable-instance",new Sortable(e.get(0),{ghostClass:"moving",animation:300}))}),$(".sort-alpha").add(a).removeClass("hidden")}),a.on("click",function(){i.removeClass("typcn-tick green").addClass("typcn-arrow-unsorted darkblue").html("Re-order"),n.removeClass("sorting").children().each(function(){var e=$(this).children("ul");e.children().sort(function(e,t){return e=$(e).data("orig-index"),(t=$(t).data("orig-index"))<e?1:e<t?-1:0}).appendTo(e).removeData("orig-index").children().moveAttr("data-href","href"),e.data("sortable-instance").destroy(),e.removeData("sortable-instance")}),$(".sort-alpha").add(a).addClass("hidden")}))}();