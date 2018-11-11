"use strict";var _createClass=function(){function r(e,t){for(var a=0;a<t.length;a++){var r=t[a];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}return function(e,t,a){return t&&r(e.prototype,t),a&&r(e,a),e}}();function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}!function(){var r=["red","green","blue"],c={red:0,green:1,blue:2},e=function(e,t,a){return(a-e*t)/(1-e)},t=function(e,t,a){return(a-e*t)/(1-e)},i={normal:function(e,n){var l=e.length,o=new $.RGBAColor(0,0,0),s=new $.RGBAColor(0,0,0),d=0,v=0;$.RGBAColor.COMPONENTS.forEach(function(i){e.forEach(function(e,t){var a=e[i],r=a-n[t][i];o[i]+=a,s[i]+=r,d-=l*Math.pow(r,2),v-=l*a*r}),d+=Math.pow(s[i],2),v+=s[i]*o[i]});var a=$.clamp(d/v,0,1);return o.alpha=a,$.RGBAColor.COMPONENTS.forEach(function(e){var t=a?(o[e]-s[e]/a)/l:0;o[e]=Math.round($.clamp(t,0,255))}),o},multiply:function(a,i){console.log(a);var n=new $.RGBAColor(0,0,0);$.RGBAColor.COMPONENTS.forEach(function(r){var e=a.reduce(function(e,t,a){return e+t[r]*(t[r]-i[a][r])},0),t=a.reduce(function(e,t,a){return e+Math.pow(t[r]-i[a][r],2)},0);n[r]=e/(2*t)});var r=Math.max(1,1/n.toRGBArray().reduce(function(e,t){return Math.min(e,t)}));return $.RGBAColor.COMPONENTS.forEach(function(e){var t=255-255/(n[e]*r);n[e]=Math.round($.clamp(t,0,255))}),n.alpha=$.clamp(.5*r,0,1),n}};new(function(){function a(){var r=this;_classCallCheck(this,a),this.$controls=$("#controls"),this.$knownColorsTbody=$("#known-colors").find("tbody"),this.$backupImage=$.mk("img"),this.backupImage=this.$backupImage.get(0),this.overlayColor=new $.RGBAColor(255,0,255,.75),this.filteredColor=null,this.haveImage=!1,this.targetType="image",this.filterOverrideActive=!1,this.fileName=null,this.selectedFilterColor=null,this.$freezing=$("#freezing"),this.$preview=$("#preview"),this.$previewImageCanvas=$("#preview-image"),this.previewImageCanvas=this.$previewImageCanvas.get(0),this.previewImageCtx=this.previewImageCanvas.getContext("2d"),this.$previewOverlayCanvas=$("#preview-overlay"),this.previewOverlayCanvas=this.$previewOverlayCanvas.get(0),this.previewOverlayCtx=this.previewOverlayCanvas.getContext("2d"),this.$addKnownColor=$("#add-known-color").on("click",function(e){e.preventDefault(),r.addKnownValueInputRow()}),this.$imageSelect=$("#image-select"),this.$imageSelectFileInput=this.$imageSelect.children("input").on("change",function(e){var t=e.target;if(t.files&&t.files[0]){r.fileName=t.files[0].name.split(/[\/]/g).pop();var a=new FileReader;a.onload=function(e){r.backupImage.src=e.target.result,r.$backupImage.one("load",function(){r.$backupImage.off("error"),r.haveImage=!0,r.updatePreview()}).one("error",function(){r.$backupImage.off("load"),$.Dialog.fail("Could not load image. Please make sure it is an actual image file.")})},a.readAsDataURL(t.files[0])}}),this.$imageSelectFileButton=this.$imageSelect.children("button").on("click",function(e){e.preventDefault(),r.$imageSelectFileInput.click()}),this.$colorSelect=$("#color-select"),this.$colorSelectColorInput=this.$colorSelect.find("input").on("change",function(e){var t=$.RGBAColor.parse(e.target.value);null!==t?(e.target.value=t,r.filteredColor=t,r.haveImage=!0,r.updatePreview()):r.haveImage=!1}).on("change input blur",a.colorInputEventHandler),this.$filterTypeSelect=$("#filter-type").children("select").on("change",function(){r.updateFilterCandidateList(),r.updatePreview()}),this.$sensitivityControls=$("#sensitivity"),this.$sensitivitySlider=this.$sensitivityControls.children("div"),this.$sensitivityDisplay=this.$sensitivityControls.find(".display"),this.sensitivitySlider=this.$sensitivitySlider.get(0),noUiSlider.create(this.sensitivitySlider,{start:[10],range:{min:0,max:255},step:1,behaviour:"drag snap",format:{to:function(e){return parseInt(e,10)},from:function(e){return parseInt(e,10)}}}),this.sensitivitySlider.noUiSlider.on("update",function(e,t){r.$sensitivityDisplay.text(e[t])}),this.sensitivitySlider.noUiSlider.on("end",function(){r.updatePreview()}),this.$resultSaveButton=$("#result").children("button").on("click",function(e){if(e.preventDefault(),r.haveImage&&null!==r.selectedFilterColor){var t=void 0;if(r.isOverlayEnabled()){(t=document.createElement("canvas")).width=r.previewImageCanvas.width,t.height=r.previewImageCanvas.height;var a=t.getContext("2d");a.drawImage(r.previewImageCanvas,0,0),a.drawImage(r.previewOverlayCanvas,0,0)}else t=r.previewImageCanvas;t.toBlob(function(e){var t=" (no "+r.getFilterType()+" filter)";saveAs(e,r.fileName.replace(/^(.*?)(\.(?:[^.]+))?$/,"$1"+t+"$2")||"image"+t+".png")})}}),this.$filterCandidates=$("#filter-candidates").children("ul"),this.$filterCandidates.on("click","li",function(e){var t=$(e.target).closest("li"),a=t.hasClass("selected");r.$filterCandidates.children(".selected").removeClass("selected"),a||(t.addClass("selected"),r.selectedFilterColor=$.RGBAColor.parse(t.attr("data-rgba"))),r.updatePreview()}),this.$overlayControls=$("#overlay"),this.$overlayToggleInput=this.$overlayControls.find('input[type="checkbox"]').on("change input",function(e){r.$previewOverlayCanvas[e.target.checked?"removeClass":"addClass"]("hidden")}),this.$overlayColorInput=this.$overlayControls.find('input[type="text"]').on("change",function(e){var t=$.RGBAColor.parse(e.target.value);null!==t&&(e.target.value=t,r.overlayColor=t,r.repaintOverlay())}).on("change input blur",a.colorInputEventHandler),this.$overlayColorInput.val(this.overlayColor.toString()).trigger("input"),$("#filter-override").find('input[type="checkbox"]').on("change input",function(e){if(r.filterOverrideActive=e.target.checked,r.$filterCandidates.parent()[r.filterOverrideActive?"addClass":"removeClass"]("hidden"),r.filterOverrideActive)r.updateOverriddenFilterColor();else{var t=r.$filterCandidates.children(".selected");r.selectedFilterColor=t.length?$.RGBAColor.parse(t.attr("data-rgba")):null,r.updatePreview()}}),this.$filterOverrideOpacity=$("#filter-override-opacity").on("change",function(e){e.target.value=$.clamp(e.target.value,0,100),r.updateOverriddenFilterColor()}),this.$filterOverrideColor=$("#filter-override-color").on("change",function(e){var t=$.RGBAColor.parse(e.target.value);null!==t&&(e.target.value=t.toHex(),r.updateOverriddenFilterColor())}).on("change input blur",a.colorInputEventHandler),this.$reverseWhat=$("#reverse-what").on("click change","input",function(e){r.targetType=e.target.value,"image"!==r.targetType?r.$imageSelect.addClass("hidden"):r.$imageSelect.removeClass("hidden"),"color"!==r.targetType?r.$colorSelect.addClass("hidden"):r.$colorSelect.removeClass("hidden"),r.updatePreview()}),this.addKnownValueInputRow(!0),this.addKnownValueInputRow()}return _createClass(a,[{key:"isOverlayEnabled",value:function(){return!this.$previewOverlayCanvas.hasClass("hidden")}},{key:"updateOverriddenFilterColor",value:function(){if(this.filterOverrideActive){var e=$.RGBAColor.parse(this.$filterOverrideColor.val());null!==e&&(e.alpha=this.$filterOverrideOpacity.val()/100),this.selectedFilterColor=e,this.updatePreview()}}},{key:"createKnownValueInput",value:function(e){var r=this;return $.mk("td").attr("class","color-cell "+e).append($.mk("input").attr({type:"text",required:!0,autocomplete:"off",spellcheck:"false"}).on("input change blur",function(e){var t=$(e.target),a=t.val(),r=$.RGBAColor.parse(a);null===r?t.css({color:"",backgroundColor:""}):t.css({color:r.isLight()?"black":"white",backgroundColor:r.toHex()})}).on("blur",function(e){var t=$(e.target),a=$.RGBAColor.parse(t.val());null!==a?t.removeAttr("pattern").val(a):t.attr("pattern","^[^\\s\\S]$"),r.updateFilterCandidateList()}).on("paste",function(e){window.requestAnimationFrame(function(){$(e.target).trigger("blur")})}))}},{key:"addKnownValueInputRow",value:function(){var i=this,e=0<arguments.length&&void 0!==arguments[0]&&arguments[0],n="reference";this.$knownColorsTbody.append($.mk("tr").attr("class",e?n:"").append(this.createKnownValueInput("original"),this.createKnownValueInput("filtered"),$.mk("td").attr("class","actions").append($.mk("button").attr({disabled:e,class:"red typcn typcn-minus",title:"Remove known color pair"}).on("click",function(e){e.preventDefault();var t=$(e.target).closest("tr");2===t.siblings().length&&t.siblings().find("button.red").disable().addClass("hidden"),t.remove(),i.updateFilterCandidateList()}),$.mk("button").attr({class:"darkblue typcn typcn-anchor",title:"Set as reference color",disabled:e}).on("click",function(e){e.preventDefault();var t=$(e.target);if(!t.is(":disabled")){var a=t.closest("tr"),r=a.find("input:invalid");r.length?r.first().focus():(a.addClass(n).siblings().removeClass(n).find("button").enable(),t.siblings().addBack().disable(),i.updateFilterCandidateList())}}))));var t=this.$knownColorsTbody.children();2<t.length?(t.find("button.red").removeClass("hidden"),t.filter(":not(.reference)").find("button.red").enable()):t.find("button.red").addClass("hidden")}},{key:"redrawPreviewImage",value:function(){var e="image"===this.targetType,t=e?this.backupImage.width:192,a=e?this.backupImage.height:108;this.previewOverlayCanvas.width=this.previewImageCanvas.width=t,this.previewOverlayCanvas.height=this.previewImageCanvas.height=a,"image"===this.targetType?this.previewImageCtx.drawImage(this.backupImage,0,0):(this.previewImageCtx.fillStyle=this.filteredColor,this.previewImageCtx.fillRect(0,0,t,a)),this.previewOverlayCtx.clearRect(0,0,this.previewOverlayCanvas.width,this.previewOverlayCanvas.height)}},{key:"repaintOverlay",value:function(){if(this.haveImage){for(var e=this.previewOverlayCtx.getImageData(0,0,this.previewOverlayCanvas.width,this.previewOverlayCanvas.height),t=0;t<e.data.length;t+=4)1===e.data[t+3]&&(e.data[t]=this.overlayColor.red,e.data[t+1]=this.overlayColor.green,e.data[t+2]=this.overlayColor.blue);this.previewOverlayCtx.putImageData(e,0,0)}}},{key:"updatePreview",value:function(){var o=this;if(this.haveImage){this.redrawPreviewImage();var e=null===this.selectedFilterColor;if(this.$resultSaveButton.prop("disabled",e),!e){var s=this.sensitivitySlider.noUiSlider.get(),d=this.previewImageCtx.getImageData(0,0,this.previewImageCanvas.width,this.previewImageCanvas.height),a=this.previewOverlayCtx.getImageData(0,0,this.previewOverlayCanvas.width,this.previewOverlayCanvas.height),v=this.getReverseCalculator();this.$freezing.removeClass("hidden"),setTimeout(function(){for(var e=function(i){var n=!1,l=!1;$.each(r,function(e,t){var a=i+c[t],r=v(o.selectedFilterColor.alpha,o.selectedFilterColor[t],d.data[a]);!l&&255<r-s&&(l=!0),!n&&r+s<0&&(n=!0),d.data[a]=$.clamp(r,0,255)}),(n||l)&&(a.data[i]=o.overlayColor.red,a.data[i+1]=o.overlayColor.green,a.data[i+2]=o.overlayColor.blue,a.data[i+3]=255*o.overlayColor.alpha)},t=0;t<d.data.length;t+=4)e(t);o.previewImageCtx.putImageData(d,0,0),o.previewOverlayCtx.putImageData(a,0,0),o.$freezing.addClass("hidden")},200)}}else this.$resultSaveButton.disable()}},{key:"updateFilterCandidateList",value:function(){var r={original:[],filtered:[]};this.$filterCandidates.empty(),this.selectedFilterColor=null;var e=this.$knownColorsTbody.children();if(!(e.length<2||(e.each(function(e,t){var a=$(t).find("input:valid");2===a.length&&a.each(function(e,t){r[t.parentNode.className.split(" ")[1]].push($.RGBAColor.parse(t.value))})}),r.original.length<2||r.filtered.length<2))){var t=i[this.getFilterType()](r.original,r.filtered);this.$filterCandidates.append(a.getFilterDisplayLi(t.round()))}}},{key:"getFilterType",value:function(){return this.$filterTypeSelect.children(":selected").attr("value")}},{key:"getReverseCalculator",value:function(){switch(this.getFilterType()){case"multiply":return t;case"normal":return e}}}],[{key:"getFilterDisplayLi",value:function(e){console.log(e);var t=e.toRGBA(),a=$.mk("ul").attr("class","pairs");return $.mk("li").attr({"data-rgba":t,title:"Click to select & apply"}).append($.mk("div").attr("class","color").append($.mk("div").attr("class","color-preview").append($.mk("span").css("background-color",t)),$.mk("div").attr("class","color-rgba").append('<div><strong>R:</strong> <span class="color-red">'+e.red+"</span></div>",'<div><strong>G:</strong> <span class="color-green">'+e.green+"</span></div>",'<div><strong>B:</strong> <span class="color-blue">'+e.blue+"</span></div>","<div><strong>A:</strong> <span>"+Math.round(100*e.alpha)+"%</span></div>")),a)}},{key:"colorInputEventHandler",value:function(e){var t=$(e.target),a=$.RGBAColor.parse(e.target.value);null!==a?t.css({color:a.isLight()?"black":"white",backgroundColor:a.toHex()}):t.css({color:"",backgroundColor:""})}}]),a}())}();