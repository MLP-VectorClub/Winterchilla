"use strict";DocReady.push(function(){var t=$("#butwhy"),e=$("#thisiswhy");t.on("click",function(a){a.preventDefault(),a.stopPropagation(),t.addClass("hidden"),e.removeClass("hidden")}),Chart.defaults.global.responsive=!0,Chart.defaults.global.maintainAspectRatio=!1,Chart.defaults.global.animation=!1;var a=$("#stats"),s=function(t){var e=arguments.length>1&&void 0!==arguments[1]?arguments[1]:.2,a=t.r+","+t.g+","+t.b,s="rgb("+a+")";return{lineTension:0,backgroundColor:0===e?"transparent":"rgba("+a+","+e+")",borderColor:s,borderWidth:2,pointBackgroundColor:s,pointRadius:3,pointHitRadius:6,pointBorderColor:"#fff",pointBorderWidth:2,pointHoverBackgroundColor:"#fff",pointHoverBorderColor:s}},n={position:"bottom",labels:{boxWidth:12}},i=[{type:"time",time:{unit:"day",unitStepSize:1,displayFormats:{day:"Do MMM"}},ticks:{autoSkip:!0,maxTicksLimit:15}}],o=[{type:"linear",ticks:{autoSkip:!0,maxTicksLimit:6}}],r=a.children(".stats-posts"),d=r.children("h3"),l=r.children(".legend"),p=r.find("canvas").get(0).getContext("2d"),m=void 0,c=["#46ACD3","#5240C3"];$.post("/about/stats?stat=posts",$.mkAjaxHandler(function(){if(!this.status)return r.remove();var t=this.data;if($.mk("p").append("Last updated: ",$.mk("time").attr("datetime",t.timestamp)).insertAfter(d),Time.Update(),0===t.datasets.length)return l.html("<strong>No data available</strong>");l.remove(),$.each(t.datasets,function(e,a){var n=$.hex2rgb(c[a.clrkey]);$.extend(t.datasets[e],s(n))}),m=new Chart.Line(p,{data:t,options:{tooltips:{mode:"label",callbacks:{title:function(t){return moment(t[0].xLabel).format("Do MMMM, YYYY")}}},legend:n,scales:{xAxes:i,yAxes:o}}}),$w.on("resize",function(){m.resize()})}));var u=a.children(".stats-approvals"),h=u.children("h3"),f=u.children(".legend"),v=u.find("canvas").get(0).getContext("2d"),b=void 0,g=$.hex2rgb("#4DC742");$.post("/about/stats?stat=approvals",$.mkAjaxHandler(function(){if(!this.status)return u.remove();var t=this.data,e=g;if($.mk("p").append("Last updated: ",$.mk("time").attr("datetime",t.timestamp)).insertAfter(h),Time.Update(),0===t.datasets.length)return f.html("<strong>No data available</strong>");f.remove(),$.extend(t.datasets[0],s(e)),b=new Chart.Line(v,{data:t,options:{tooltips:{mode:"label",callbacks:{title:function(t){return moment(t[0].xLabel).format("Do MMMM, YYYY")},label:function(t){var e=parseInt(t.yLabel,10);return(0===e?"No":e)+" post"+(1!==e?"s":"")+" approved"}}},legend:n,scales:{xAxes:i,yAxes:o}}}),$w.on("resize",function(){b.resize()})}));var x=a.children(".stats-alltimeposts"),k=x.children("h3"),C=x.children(".legend"),y=x.find("canvas").get(0).getContext("2d"),M=void 0,A=["#4DC742","#46ACD3","#5240C3"];$.post("/about/stats?stat=alltimeposts",$.mkAjaxHandler(function(){if(!this.status)return x.remove();var t=this.data;if($.mk("p").append("Last updated: ",$.mk("time").attr("datetime",t.timestamp)).insertAfter(k),Time.Update(),0===t.datasets.length)return C.html("<strong>No data available</strong>");C.remove(),$.each(t.datasets,function(e,a){var n=$.hex2rgb(A[a.clrkey]);$.extend(t.datasets[e],s(n,0===e?0:.1))}),M=new Chart.Line(y,{data:t,options:{tooltips:{mode:"label",callbacks:{title:function(t){return"Totals as of "+moment(t[0].xLabel).format("MMM 'YY")},label:function(t){var e=parseInt(t.yLabel,10);return 0===t.datasetIndex?(0===e?"No":e)+" approved post"+(1!==e?"s":""):(0===e?"No":e)+" "+(1===t.datasetIndex?"request":"reservation")+(1!==e?"s":"")}}},legend:n,scales:{xAxes:[{type:"time",time:{unit:"month",unitStepSize:1,displayFormats:{month:"MMM 'YY"}},ticks:{autoSkip:!0}}],yAxes:o}}}),$w.on("resize",function(){M.resize()})}))},function(){delete window.Chart,$("script").filter('[src^="/js/Chart.min.js"], [data-src^="/js/Chart.min.js"]').remove()});
//# sourceMappingURL=/js/min/about.js.map
