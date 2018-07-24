"use strict";var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},_createClass=function(){function o(t,e){for(var i=0;i<e.length;i++){var o=e[i];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(t,o.key,o)}}return function(t,e,i){return e&&o(t.prototype,e),i&&o(t,i),t}}();function _classCallCheck(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}!function(h,g){var f={fail:"red",success:"green",wait:"blue",request:"",confirm:"orange",info:"darkblue",segway:"lavender"},p={fail:"fail",success:"success",wait:"info",request:"warn",confirm:"caution",info:"info",segway:"reload"},y={fail:"Error",success:"Success",wait:"Sending request",request:"Input required",confirm:"Confirmation",info:"Info",segway:"Pending navigation"},v={fail:"There was an issue while processing the request.",success:"Whatever you just did, it was completed successfully.",wait:"Sending request",request:"The request did not require any additional info.",confirm:"Are you sure?",info:"No message provided.",segway:"A previous action requires reloading the current page. Press reload once you're ready."},s=function(){h.Dialog.close()},r=function(){function o(t,e){var i=this;_classCallCheck(this,o),this.label=t,h.each(e,function(t,e){return i[t]=e})}return _createClass(o,[{key:"setLabel",value:function(t){return this.label=t,this}},{key:"setFormId",value:function(t){return this.formid=t,this}}]),o}(),t=function(){function t(){_classCallCheck(this,t),this.$dialogOverlay=h("#dialogOverlay"),this.$dialogContent=h("#dialogContent"),this.$dialogHeader=h("#dialogHeader"),this.$dialogBox=h("#dialogBox"),this.$dialogWrap=h("#dialogWrap"),this.$dialogScroll=h("#dialogScroll"),this.$dialogButtons=h("#dialogButtons"),this._open=this.$dialogContent.length?{}:g,this._CloseButton=new r("Close",{action:s}),this._$focusedElement=g}return _createClass(t,[{key:"isOpen",value:function(){return"object"===_typeof(this._open)}},{key:"_display",value:function(t){var o=this;if("string"!=typeof t.type||void 0===f[t.type])throw new TypeError("Invalid dialog type: "+_typeof(t.type));t.content||(t.content=v[t.type]);var n=h.extend({content:v[t.type]},t);n.color=f[t.type];var e=Boolean(this._open),i=h.mk("div").append(n.content),a=e&&"request"===this._open.type&&["fail","wait"].includes(n.type)&&!n.forceNew,l=void 0;n.color.length&&i.addClass(n.color);var s=i.find(".tab-wrap");if(0<s.length){var r=function(t){var e=t.closest(".tab-wrap").find(".tab-contents");t.addClass("selected").siblings().removeClass("selected"),e.children().addClass("hidden").filter(".content-"+t.attr("data-content")).removeClass("hidden")};s.on("click",".tab-list .tab",function(){r(h(this))});var d=s.find(".tab-default");0===d.length&&(d=s.find(".tab").first()),r(d)}if(e)if(this.$dialogOverlay=h("#dialogOverlay"),this.$dialogBox=h("#dialogBox"),this.$dialogHeader=h("#dialogHeader"),"string"==typeof n.title&&this.$dialogHeader.text(n.title),this.$dialogContent=h("#dialogContent"),a){var c=(l=this.$dialogContent.children(":not(#dialogButtons)").last()).children(".notice:last-child");c.length?c.show():(c=h.mk("div").append(h.mk("p")),l.append(c)),c.attr("class","notice "+p[n.type]).children("p").html(n.content).show(),this._controlInputs("wait"===n.type)}else this._open=n,this.$dialogButtons=h("#dialogButtons").empty(),this._controlInputs(!0),this.$dialogContent.append(i),n.buttons&&(0===this.$dialogButtons.length&&(this.$dialogButtons=h.mk("div","dialogButtons")),this.$dialogButtons.appendTo(this.$dialogContent));else this._storeFocus(),this._open=n,this.$dialogOverlay=h.mk("div","dialogOverlay"),this.$dialogHeader=h.mk("div","dialogHeader"),"string"==typeof n.title?this.$dialogHeader.text(n.title):!1===n.title&&this.$dialogHeader.text(y[n.type]),this.$dialogContent=h.mk("div","dialogContent"),this.$dialogBox=h.mk("div","dialogBox").attr({role:"dialog","aria-labelledby":"dialogHeader"}),this.$dialogScroll=h.mk("div","dialogScroll"),this.$dialogWrap=h.mk("div","dialogWrap"),this.$dialogContent.append(i),this.$dialogButtons=h.mk("div","dialogButtons").appendTo(this.$dialogContent),this.$dialogBox.append(this.$dialogHeader).append(this.$dialogContent),this.$dialogOverlay.append(this.$dialogScroll.append(this.$dialogWrap.append(this.$dialogBox))).appendTo($body),$body.addClass("dialog-open"),this.$dialogOverlay.siblings().prop("inert",!0);if(a||(this.$dialogHeader.attr("class",n.color?n.color+"-bg":""),this.$dialogContent.attr("class",n.color?n.color+"-border":"")),!a&&n.buttons&&h.each(n.buttons,function(t,e){var i=h.mk("input").attr({type:"button",class:n.color?n.color+"-bg":g});e.form&&1===(l=h("#"+e.form)).length&&(i.on("click",function(){l.find("input[type=submit]").first().trigger("click")}),l.prepend(h.mk("input").attr("type","submit").hide().on("focus",function(t){t.preventDefault(),o.$dialogButtons.children().first().focus()}))),i.val(e.label).on("click",function(t){t.preventDefault(),h.callCallback(e.action,[t])}),o.$dialogButtons.append(i)}),window.withinMobileBreakpoint()||this._setFocus(),$w.trigger("dialog-opened"),Time.Update(),h.callCallback(n.callback,[l]),e){var u=this.$dialogContent.children(":not(#dialogButtons)").last();a&&(u=u.children(".notice").last()),this.$dialogOverlay.stop().animate({scrollTop:"+="+(u.position().top+parseFloat(u.css("margin-top"),10)+parseFloat(u.css("border-top-width"),10))},"fast")}}},{key:"fail",value:function(){var t=0<arguments.length&&arguments[0]!==g?arguments[0]:y.fail,e=1<arguments.length&&arguments[1]!==g?arguments[1]:v.fail,i=2<arguments.length&&arguments[2]!==g&&arguments[2];this._display({type:"fail",title:t,content:e,buttons:[this._CloseButton],forceNew:i})}},{key:"success",value:function(){var t=0<arguments.length&&arguments[0]!==g?arguments[0]:y.success,e=1<arguments.length&&arguments[1]!==g?arguments[1]:v.success,i=2<arguments.length&&arguments[2]!==g&&arguments[2],o=3<arguments.length&&arguments[3]!==g?arguments[3]:g;this._display({type:"success",title:t,content:e,buttons:i?[this._CloseButton]:g,callback:o})}},{key:"wait",value:function(){var t=0<arguments.length&&arguments[0]!==g?arguments[0]:y.wait,e=1<arguments.length&&arguments[1]!==g?arguments[1]:v.wait,i=2<arguments.length&&arguments[2]!==g&&arguments[2],o=3<arguments.length&&arguments[3]!==g?arguments[3]:g;this._display({type:"wait",title:t,content:h.capitalize(e)+"&hellip;",forceNew:i,callback:o})}},{key:"request",value:function(){var t=0<arguments.length&&arguments[0]!==g?arguments[0]:y.request,e=1<arguments.length&&arguments[1]!==g?arguments[1]:v.request,i=2<arguments.length&&arguments[2]!==g?arguments[2]:"Submit",o=3<arguments.length&&arguments[3]!==g?arguments[3]:g;"function"==typeof i&&void 0===o&&(o=i,i=g);var n=[],a=void 0;if(e instanceof h)a=e.attr("id");else if("string"==typeof e){var l=e.match(/<form\sid=["']([^"']+)["']/);l&&(a=l[1])}!1!==i?(a&&n.push(new r(i,{submit:!0,form:a})),n.push(new r("Cancel",{action:s}))):n.push(new r("Close",{action:s})),this._display({type:"request",title:t,content:e,buttons:n,callback:o})}},{key:"confirm",value:function(){var t=0<arguments.length&&arguments[0]!==g?arguments[0]:y.confirm,e=1<arguments.length&&arguments[1]!==g?arguments[1]:v.confirm,i=this,o=2<arguments.length&&arguments[2]!==g?arguments[2]:["Eeyup","Nope"],n=3<arguments.length&&arguments[3]!==g?arguments[3]:g;void 0===n&&(n="function"==typeof o?o:s),h.isArray(o)||(o=["Eeyup","Nope"]);var a=[new r(o[0],{action:function(){n(!0)}}),new r(o[1],{action:function(){n(!1),i._CloseButton.action()}})];this._display({type:"confirm",title:t,content:e,buttons:a})}},{key:"info",value:function(){var t=0<arguments.length&&arguments[0]!==g?arguments[0]:y.info,e=1<arguments.length&&arguments[1]!==g?arguments[1]:v.info,i=2<arguments.length&&arguments[2]!==g?arguments[2]:g;this._display({type:"info",title:t,content:e,buttons:[this._CloseButton],callback:i})}},{key:"segway",value:function(){var t=0<arguments.length&&arguments[0]!==g?arguments[0]:y.reload,e=1<arguments.length&&arguments[1]!==g?arguments[1]:v.reload,i=2<arguments.length&&arguments[2]!==g?arguments[2]:"Reload",o=3<arguments.length&&arguments[3]!==g?arguments[3]:g;void 0===o&&"function"==typeof i&&(o=i,i="Reload"),this._display({type:"segway",title:t,content:e,buttons:[new r(i,{action:function(){h.callCallback(o),h.Navigation.reload(!0)}})]})}},{key:"setFocusedElement",value:function(t){t instanceof h&&(this._$focusedElement=t)}},{key:"_storeFocus",value:function(){if(!(void 0!==this._$focusedElement&&this._$focusedElement instanceof h)){var t=h(":focus");this._$focusedElement=0<t.length?t.last():g}}},{key:"_restoreFocus",value:function(){void 0!==this._$focusedElement&&this._$focusedElement instanceof h&&(this._$focusedElement.focus(),this._$focusedElement=g)}},{key:"_setFocus",value:function(){var t=this.$dialogContent.find("input,select,textarea").filter(":visible"),e=this.$dialogButtons.children();0<t.length?t.first().focus():0<e.length&&e.first().focus()}},{key:"_controlInputs",value:function(t){var e=this.$dialogContent.children(":not(#dialogButtons)").last().add(this.$dialogButtons).find("input, button, select, textarea");t?e.filter(":not(:disabled)").addClass("temp-disable").disable():e.filter(".temp-disable").removeClass("temp-disable").enable()}},{key:"close",value:function(t){if(!this.isOpen())return h.callCallback(t,!1);this.$dialogOverlay.siblings().prop("inert",!1),this.$dialogOverlay.remove(),this._open=g,this._restoreFocus(),h.callCallback(t),$body.removeClass("dialog-open")}},{key:"clearNotice",value:function(t){var e=this.$dialogContent.children(":not(#dialogButtons)").children(".notice:last-child");return!!e.length&&(!(void 0!==t&&!t.test(e.html()))&&(e.hide(),e.hasClass("info")&&this._controlInputs(!1),!0))}}]),t}();h.Dialog=new t;var e=function(){h.Dialog.isOpen()&&window.withinMobileBreakpoint()&&h.Dialog.$dialogContent.css("margin-top",h.Dialog.$dialogHeader.outerHeight())};$w.on("resize",h.throttle(200,e)).on("dialog-opened",e)}(jQuery);
//# sourceMappingURL=/js/min/dialog.js.map