"use strict";var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},_createClass=function(){function o(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(e,t,n){return t&&o(e.prototype,t),n&&o(e,n),e}}();function _toConsumableArray(e){if(Array.isArray(e)){for(var t=0,n=Array(e.length);t<e.length;t++)n[t]=e[t];return n}return Array.from(e)}function _possibleConstructorReturn(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function _inherits(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}!function(a){if(void 0===a.Navigation||!0!==a.Navigation.firstLoadDone){"function"!=typeof window.console.log&&(window.console.log=function(){}),"function"!=typeof window.console.group&&(window.console.group=function(){}),"function"!=typeof window.console.groupEnd&&(window.console.groupEnd=function(){}),"function"!=typeof window.console.clear&&(window.console.clear=function(){}),window.mk=function(){return document.createElement.apply(document,arguments)},a.mk=function(e,t){var n=a(document.createElement.call(document,e));return"string"==typeof t&&n.attr("id",t),n};var o=function(){function e(){_classCallCheck(this,e),this.emulatedStorage={}}return _createClass(e,[{key:"getItem",value:function(e){return void 0===this.emulatedStorage[e]?null:this.emulatedStorage[e]}},{key:"setItem",value:function(e,t){this.emulatedStorage[e]="string"==typeof t?t:""+t}},{key:"removeItem",value:function(e){delete this.emulatedStorage[e]}}]),e}(),e=function(){function n(e){_classCallCheck(this,n);var t=e+"Storage";try{this.store=window[e+"Storage"]}catch(e){console.error(t+" is unavailable, falling back to EmulatedStorage"),this.store=new o}}return _createClass(n,[{key:"get",value:function(e){var t=void 0;try{t=this.store.getItem(e)}catch(e){}return void 0===t?null:t}},{key:"set",value:function(e,t){try{this.store.setItem(e,t)}catch(e){}}},{key:"remove",value:function(e){try{this.store.removeItem(e)}catch(e){}}}]),n}();a.LocalStorage=new e("local"),a.SessionStorage=new e("session"),a.toAbsoluteURL=function(e){var t=mk("a");return t.href=e,t.href},window.$w=a(window),window.$d=a(document),window.CommonElements=function(){window.$header=a("header"),window.$sbToggle=a(".sidebar-toggle"),window.$main=a("#main"),window.$content=a("#content"),window.$sidebar=a("#sidebar"),window.$footer=a("footer"),window.$body=a("body"),window.$head=a("head"),window.$navbar=$header.find("nav")},window.CommonElements(),window.Key={Backspace:8,Tab:9,Enter:13,Alt:18,Escape:27,Space:32,LeftArrow:37,UpArrow:38,RightArrow:39,DownArrow:40,Delete:46,0:48,1:49,A:65,H:72,I:73,O:79,Z:90,Comma:188},a.isKey=function(e,t){return t.keyCode===e},function(u){var c={order:"Do MMMM YYYY, H:mm:ss"};c.orderwd="dddd, "+c.order;var d=function(e){function o(e,t){_classCallCheck(this,o);var n=_possibleConstructorReturn(this,(o.__proto__||Object.getPrototypeOf(o)).call(this,e));return n.name="DateFormatError",n.element=t,n}return _inherits(o,Error),o}(),e=function(){function l(){_classCallCheck(this,l)}return _createClass(l,null,[{key:"Update",value:function(){u("time[datetime]:not(.nodt)").addClass("dynt").each(function(){var e=u(this),t=e.attr("datetime");if("string"!=typeof t)throw new TypeError('Invalid date data type: "'+(void 0===t?"undefined":_typeof(t))+'"');var n=moment(t);if(!n.isValid())throw new d('Invalid date format: "'+t+'"',this);var o=moment(),r=!e.attr("data-noweekday"),i=n.from(o),a=e.parent().children(".dynt-el"),s=e.data("dyntime-beforeupdate");if("function"==typeof s&&!1===s(l.Difference(o.toDate(),n.toDate())))return;0<a.length||e.hasClass("no-dynt-el")?(e.html(n.format(r?c.orderwd:c.order)),a.html(i)):e.attr("title",n.format(c.order)).html(i)})}},{key:"Difference",value:function(e,t){var n=(e.getTime()-t.getTime())/1e3,o={past:0<n,time:Math.abs(n),target:t,week:0,month:0,year:0},r=o.time;return o.day=Math.floor(r/this.InSeconds.day),r-=o.day*this.InSeconds.day,o.hour=Math.floor(r/this.InSeconds.hour),r-=o.hour*this.InSeconds.hour,o.minute=Math.floor(r/this.InSeconds.minute),r-=o.minute*this.InSeconds.minute,o.second=Math.floor(r),7<=o.day&&(o.week=Math.floor(o.day/7),o.day-=7*o.week),4<=o.week&&(o.month=Math.floor(o.week/4),o.week-=4*o.month),12<=o.month&&(o.year=Math.floor(o.month/12),o.month-=12*o.year),o}}]),l}();e.InSeconds={year:31557600,month:2592e3,week:604800,day:86400,hour:3600,minute:60},(window.Time=e).Update(),setInterval(e.Update,1e4)}(jQuery),a.capitalize=function(e,t){return t?e.replace(/((?:^|\s)[a-z])/g,function(e){return e.toUpperCase()}):1===e.length?e.toUpperCase():e[0].toUpperCase()+e.substring(1)},"function"!=typeof Array.prototype.includes&&(Array.prototype.includes=function(e){return-1!==this.indexOf(e)}),"function"!=typeof String.prototype.includes&&(String.prototype.includes=function(e){return-1!==this.indexOf(e)}),a.pad=function(e,t,n,o){if("string"!=typeof e&&(e=""+e),"string"!=typeof t&&(t="0"),"boolean"!=typeof o&&(o=!0),(n="number"!=typeof n&&!isFinite(n)&&isNaN(n)?2:parseInt(n,10))<=e.length)return e;var r=new Array(n-e.length+1).join(t);return e=o===a.pad.left?r+e:e+r},a.pad.right=!(a.pad.left=!0),a.scaleResize=function(e,t,n){var o=!(3<arguments.length&&void 0!==arguments[3])||arguments[3],r=void 0,i={scale:n.scale,width:n.width,height:n.height};if(isNaN(i.scale))if(isNaN(i.width)){if(isNaN(i.height))throw new Error("[scalaresize] Invalid arguments");o||(i.height=Math.min(i.height,t)),r=i.height/t,!o&&1<r&&(r=1),i.width=Math.round(e*r),i.scale=r}else o||(i.width=Math.min(i.width,e)),r=i.width/e,!o&&1<r&&(r=1),i.height=Math.round(t*r),i.scale=r;else(o||i.scale<=1)&&(i.height=Math.round(t*i.scale),i.width=Math.round(e*i.scale));return i},a.clearSelection=function(){if(window.getSelection){var e=window.getSelection();e.empty?e.empty():e.removeAllRanges&&e.removeAllRanges()}else document.selection&&document.selection.empty()},a.toArray=function(e){var t=1<arguments.length&&void 0!==arguments[1]?arguments[1]:0;return[].slice.call(e,t)},a.clearFocus=function(){document.activeElement!==$body[0]&&document.activeElement.blur()},$w.on("ajaxerror",function(){var e="";if(1<arguments.length){var t=a.toArray(arguments,1);if("abort"===t[1])return;e=" Details:<pre><code>"+t.slice(1).join("\n").replace(/</g,"&lt;")+"</code></pre>Response body:";var n=/^(?:<br \/>\n)?(<pre class='xdebug-var-dump'|<font size='1')/;n.test(t[0].responseText)?e+='<div class="reset">'+t[0].responseText.replace(n,"$1")+"</div>":"string"==typeof t[0].responseText&&(e+="<pre><code>"+t[0].responseText.replace(/</g,"&lt;")+"</code></pre>")}a.Dialog.fail(!1,"There was an error while processing your request."+e)}),a.mkAjaxHandler=function(t){return function(e){if("object"!==(void 0===e?"undefined":_typeof(e)))return console.log(e),void $w.triggerHandler("ajaxerror");"function"==typeof t&&t.call(e,e)}},a.callCallback=function(e,t,n){return"object"===(void 0===t?"undefined":_typeof(t))&&a.isArray(t)||(n=t,t=[]),"function"!=typeof e?n:e.apply(void 0,_toConsumableArray(t))},a.fn.mkData=function(e){var t=this.find(":input:valid").serializeArray(),n={};return a.each(t,function(e,t){/\[]$/.test(t.name)?(void 0===n[t.name]&&(n[t.name]=[]),n[t.name].push(t.value)):n[t.name]=t.value}),"object"===(void 0===e?"undefined":_typeof(e))&&a.extend(n,e),n},a.getCSRFToken=function(){var e=document.cookie.match(/CSRF_TOKEN=([a-f\d]+)/i);if(e&&e.length)return e[1];throw a.Dialog.fail(!1,'<p>A request could not be sent due to a missing CSRF_TOKEN, please <a class="send-feedback">let us know</a>. Additional information:</p><pre><code>'+(document.cookie||"&lt;empty&gt;")+"</code></pre>"),new Error("Missing CSRF_TOKEN")},a.ajaxPrefilter(function(e,t){if("POST"===(t.type||e.type).toUpperCase()){var n=a.getCSRFToken();if(void 0===e.data&&(e.data=""),"string"==typeof e.data){var o=0<e.data.length?e.data.split("&"):[];o.push("CSRF_TOKEN="+n),e.data=o.join("&")}else e.data.CSRF_TOKEN=n}});var n=void 0,t={401:function(){a.Dialog.fail(void 0,"Cross-site Request Forgery attack detected. Please <a class='send-feedback'>let us know</a> about this issue so we can look into it.")},404:function(){a.Dialog.fail(!1,"Error 404: The requested endpoint ("+n.replace(/</g,"&lt;").replace(/\//g,"/<wbr>")+") could not be found")},500:function(){a.Dialog.fail(!1,'A request failed due to an internal server error. If this persists, please <a class="send-feedback">let us know</a>!')},503:function(){a.Dialog.fail(!1,'A request failed because the server is temporarily unavailable. This shouldn\'t take too long, please try again in a few seconds.<br>If the problem still persist after a few minutes, please let us know by clicking the "Send feedback" link in the footer.')},504:function(){a.Dialog.fail(!1,'A request failed because the server took too long to respond. A refresh should fix this issue, but if it doesn\'t, please <a class="send-feedback">let us know</a>.')}};a.ajaxSetup({dataType:"json",error:function(e){"function"!=typeof t[e.status]&&$w.triggerHandler("ajaxerror",a.toArray(arguments))},beforeSend:function(e,t){n=t.url},statusCode:t});var s,l,r,i=void 0,u=function(e,t){if(void 0===i||t){if(void 0===i&&(i=a.mk("span").attr({id:"copy-notify",class:e?void 0:"fail"}).html('<span class="typcn typcn-clipboard"></span> <span class="typcn typcn-'+(e?"tick":"cancel")+'"></span>').appendTo($body)),t){var n=i.outerWidth(),o=i.outerHeight(),r=t.clientY-o/2;return i.stop().css({top:r,left:t.clientX-n/2,bottom:"initial",right:"initial",opacity:1}).animate({top:r-20,opacity:0},1e3,function(){a(this).remove(),i=void 0})}i.fadeTo("fast",1)}else i.stop().css("opacity",1);i.delay(e?300:1e3).fadeTo("fast",0,function(){a(this).remove(),i=void 0})};a.copy=function(e,t){if(void 0===navigator.clipboard){if(!document.queryCommandSupported("copy"))return prompt("Copy with Ctrl+C, close with Enter",e),!0;var n=a.mk("textarea"),o=!1;n.css({opacity:0,width:0,height:0,position:"fixed",left:"-10px",top:"50%",display:"block"}).text(e).appendTo("body").focus(),n.get(0).select();try{o=document.execCommand("copy")}catch(e){}setTimeout(function(){n.remove(),u(o,t)},1)}else navigator.clipboard.writeText(e).then(function(){u(!0,t)}).catch(function(){u(!1,t)})},a.compareFaL=function(e,t){return JSON.stringify(e)===JSON.stringify(t)},a.rgb2hex=function(e){return a.RGBAColor.fromRGB(e).toHex()},"function"!=typeof a.expr[":"].valid&&(a.expr[":"].valid=function(e){return"object"===_typeof(e.validity)?e.validity.valid:(t=a(e),n=t.attr("pattern"),o=t.hasAttr("required"),r=t.val(),!(o&&("string"!=typeof r||!r.length))&&(!n||new RegExp(n).test(r)));var t,n,o,r}),a.roundTo=function(e){var t=1<arguments.length&&void 0!==arguments[1]?arguments[1]:0;0===t&&console.warn("$.roundTo called with precision 0; you might as well use Math.round");var n=Math.pow(10,t);return Math.round(e*n)/n},a.clamp=function(e,t,n){return Math.min(n,Math.max(t,e))},a.clampCycle=function(e,t,n){return n<e?t:e<t?n:e},a.fn.select=function(){var e=document.createRange();e.selectNodeContents(this.get(0));var t=window.getSelection();t.removeAllRanges(),t.addRange(e)},a.momentToYMD=function(e){return e.format("YYYY-MM-DD")},a.momentToHM=function(e){return e.format("HH:mm")},a.mkMoment=function(e,t,n){return moment(e+"T"+t+(n?"Z":""))},a.nth=function(e){switch(e%10){case 1:return e+(/11$/.test(e+"")?"th":"st");case 2:return e+(/12$/.test(e+"")?"th":"nd");case 3:return e+(/13$/.test(e+"")?"th":"rd");default:return e+"th"}},a.escapeRegex=function(e){return e.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&")},a.fn.toggleHtml=function(e){return this.html(e[a.clampCycle(e.indexOf(this.html())+1,0,e.length-1)])},a.fn.moveAttr=function(n,o){return this.each(function(){var e=a(this),t=e.attr(n);void 0!==t&&e.removeAttr(n).attr(o,t)})},a.fn.backgroundImageUrl=function(e){return this.css("background-image",'url("'+e.replace(/"/g,"%22")+'")')},a.attributifyRegex=function(e){return"string"==typeof e?e:e.toString().replace(/(^\/|\/[img]*$)/g,"")},a.fn.patternAttr=function(e){if(void 0===e)throw new Error("$.fn.patternAttr: regex is undefined");return this.attr("pattern",a.attributifyRegex(e))},a.fn.enable=function(e){return void 0!==e&&this.removeClass(e),this.attr("disabled",!1)},a.fn.disable=function(e){return void 0!==e&&this.removeClass(e),this.attr("disabled",!0)},a.fn.hasAttr=function(e){var t=this.get(0);return t&&t.hasAttribute(e)},a.fn.isOverflowing=function(){var e=this.get(0),t=e.style.overflow;t&&"visible"!==t||(e.style.overflow="hidden");var n=e.clientWidth<e.scrollWidth||e.clientHeight<e.scrollHeight;return e.style.overflow=t,n},a.scrollTo=function(e,t,n){var o=function(){return!1};a("html,body").on("mousewheel scroll",o).animate({scrollTop:e},t,n).off("mousewheel scroll",o),$w.on("beforeunload",function(){a("html,body").stop().off("mousewheel scroll",o)})},a.getAceEditor=function(e,t,n){var o=function(){a.Dialog.clearNotice(),n("ace/mode/"+t)};void 0===window.ace?(a.Dialog.wait(e,"Loading Ace Editor"),a.getScript("/js/min/ace/ace.js",function(){window.ace.config.set("basePath","/js/min/ace"),o()}).fail(function(){return a.Dialog.fail(!1,"Failed to load Ace Editor")})):o()},a.aceInit=function(e){e.$blockScrolling=1/0,e.setShowPrintMargin(!1);var r=e.getSession();return r.setUseSoftTabs(!1),r.setOption("indentedSoftWrap",!1),r.setOption("useWorker",!0),r.on("changeAnnotation",function(){for(var e=r.getAnnotations()||[],t=0,n=e.length,o=!1;t<n;)/doctype first\. Expected/.test(e[t].text)?(e.splice(t,1),n--,o=!0):t++;o&&r.setAnnotations(e)}),r},a.isInViewport=function(e){var t=void 0;try{t=e.getBoundingClientRect()}catch(e){return!0}return 0<t.bottom&&0<t.right&&t.left<(window.innerWidth||document.documentElement.clientWidth)&&t.top<(window.innerHeight||document.documentElement.clientHeight)},a.fn.isInViewport=function(){return!!this[0]&&a.isInViewport(this[0])},a.loadImages=function(e){var n=a(e);return new Promise(function(t){n.find("img").length?n.find("img").on("load error",function(e){t(n,e)}):t(n)})},a.isRunningStandalone=function(){return window.matchMedia("(display-mode: standalone)").matches},window.sidebarForcedVisible=function(){return 1200<=Math.max(document.documentElement.clientWidth,window.innerWidth||0)},window.withinMobileBreakpoint=function(){return Math.max(document.documentElement.clientWidth,window.innerWidth||0)<=650},a.randomString=function(){return parseInt(Math.random().toFixed(20).replace(/[.,]/,""),10).toString(36)},a.hrefToPath=function(e){return e.replace(/^.*?[\w\d]\//,"/")},l=[!(s=[/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})?$/i,/^#?([a-f\d])([a-f\d])([a-f\d])$/i,/^rgba?\(\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*([10]|0?\.\d+))?\s*\)$/i]),!1,!0],(r=function(){function i(e,t,n){var o=3<arguments.length&&void 0!==arguments[3]?arguments[3]:1;_classCallCheck(this,i),this.red=isNaN(e)?NaN:parseFloat(e),this.green=isNaN(e)?NaN:parseFloat(t),this.blue=isNaN(e)?NaN:parseFloat(n),this.alpha=parseFloat(o)}return _createClass(i,[{key:"setRed",value:function(e){return this.red=e,this}},{key:"setGreen",value:function(e){return this.green=e,this}},{key:"setBlue",value:function(e){return this.blue=e,this}},{key:"setAlpha",value:function(e){return this.alpha=e,this}},{key:"isTransparent",value:function(){return 1!==this.alpha}},{key:"yiq",value:function(){return(299*this.red+587*this.green+114*this.blue)/1e3}},{key:"isLight",value:function(){return 127<this.yiq()||this.alpha<.5}},{key:"isDark",value:function(){return!this.isLight()}},{key:"toHex",value:function(){return"#"+(a.pad(this.red.toString(16))+a.pad(this.green.toString(16))+a.pad(this.blue.toString(16))).toUpperCase()}},{key:"toHexa",value:function(){return this.toHex()+a.pad(Math.round(255*this.alpha).toString(16)).toUpperCase()}},{key:"toRGB",value:function(){return"rgb("+this.red+","+this.green+","+this.blue+")"}},{key:"toRGBA",value:function(){return"rgba("+this.red+","+this.green+","+this.blue+","+this.alpha+")"}},{key:"toRGBString",value:function(){return this.isTransparent()?this.toRGBA():this.toRGB()}},{key:"toHexString",value:function(){return this.isTransparent()?this.toHexa():this.toHex()}},{key:"toString",value:function(){return this.isTransparent()?this.toRGBA():this.toHex()}},{key:"toRGBArray",value:function(){return[this.red,this.green,this.blue]}},{key:"invert",value:function(){var e=0<arguments.length&&void 0!==arguments[0]&&arguments[0];return this.red=255-this.red,this.green=255-this.green,this.blue=255-this.blue,e&&(this.alpha=1-this.alpha),this}},{key:"round",value:function(){return 0<arguments.length&&void 0!==arguments[0]&&arguments[0]?new a.RGBAColor.fromRGB(this).round():(this.red=Math.round(this.red),this.green=Math.round(this.green),this.blue=Math.round(this.blue),this.alpha=a.roundTo(this.alpha,2),this)}}],[{key:"_parseWith",value:function(e,t,n){var o=e.match(t);if(!o)return null;var r=o.slice(1,5);return l[n]||(1===r[0].length&&(r=r.map(function(e){return e+e})),r[0]=parseInt(r[0],16),r[1]=parseInt(r[1],16),r[2]=parseInt(r[2],16),void 0!==r[3]&&(r[3]=a.roundTo(parseInt(r[3],16)/255,3))),new(Function.prototype.bind.apply(i,[null].concat(_toConsumableArray(r))))}},{key:"parse",value:function(o){var r=this,i=null;return o instanceof a.RGBAColor?o:("string"==typeof o&&(o=o.trim(),a.each(s,function(e,t){var n=r._parseWith(o,t,e);if(null!==n)return i=n,!1})),i)}},{key:"fromRGB",value:function(e){return new a.RGBAColor(e.r||e.red,e.g||e.green,e.b||e.blue,e.a||e.alpha||1)}}]),i}()).COMPONENTS=["red","green","blue"],a.RGBAColor=r}}(jQuery);
//# sourceMappingURL=/js/min/shared-utils.js.map
