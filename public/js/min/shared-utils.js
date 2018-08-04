"use strict";var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},_createClass=function(){function o(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(e,t,n){return t&&o(e.prototype,t),n&&o(e,n),e}}();function _toConsumableArray(e){if(Array.isArray(e)){for(var t=0,n=Array(e.length);t<e.length;t++)n[t]=e[t];return n}return Array.from(e)}function _possibleConstructorReturn(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function _inherits(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function _classCallCheck(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}!function(){if(void 0===$.Navigation||!0!==$.Navigation.firstLoadDone){"function"!=typeof window.console.log&&(window.console.log=function(){}),"function"!=typeof window.console.group&&(window.console.group=function(){}),"function"!=typeof window.console.groupEnd&&(window.console.groupEnd=function(){}),"function"!=typeof window.console.clear&&(window.console.clear=function(){}),window.mk=function(){return document.createElement.apply(document,arguments)},$.mk=function(e,t){var n=$(document.createElement.call(document,e));return"string"==typeof t&&n.attr("id",t),n};var o=function(){function e(){_classCallCheck(this,e),this.emulatedStorage={}}return _createClass(e,[{key:"getItem",value:function(e){return void 0===this.emulatedStorage[e]?null:this.emulatedStorage[e]}},{key:"setItem",value:function(e,t){this.emulatedStorage[e]="string"==typeof t?t:""+t}},{key:"removeItem",value:function(e){delete this.emulatedStorage[e]}}]),e}(),e=function(){function n(e){_classCallCheck(this,n);var t=e+"Storage";try{this.store=window[e+"Storage"]}catch(e){console.error(t+" is unavailable, falling back to EmulatedStorage"),this.store=new o}}return _createClass(n,[{key:"get",value:function(e){var t=void 0;try{t=this.store.getItem(e)}catch(e){}return void 0===t?null:t}},{key:"set",value:function(e,t){try{this.store.setItem(e,t)}catch(e){}}},{key:"remove",value:function(e){try{this.store.removeItem(e)}catch(e){}}}]),n}();$.LocalStorage=new e("local"),$.SessionStorage=new e("session"),$.toAbsoluteURL=function(e){var t=mk("a");return t.href=e,t.href},window.$w=$(window),window.$d=$(document),window.CommonElements=function(){window.$header=$("header"),window.$sbToggle=$(".sidebar-toggle"),window.$main=$("#main"),window.$content=$("#content"),window.$sidebar=$("#sidebar"),window.$footer=$("footer"),window.$body=$("body"),window.$head=$("head"),window.$navbar=$header.find("nav")},window.CommonElements(),window.Key={Backspace:8,Tab:9,Enter:13,Alt:18,Escape:27,Space:32,LeftArrow:37,UpArrow:38,RightArrow:39,DownArrow:40,Delete:46,0:48,1:49,A:65,H:72,I:73,O:79,Z:90,Comma:188},$.isKey=function(e,t){return t.keyCode===e},function(l){var c={order:"Do MMMM YYYY, H:mm:ss"};c.orderwd="dddd, "+c.order;var d=function(e){function o(e,t){_classCallCheck(this,o);var n=_possibleConstructorReturn(this,(o.__proto__||Object.getPrototypeOf(o)).call(this,e));return n.name="DateFormatError",n.element=t,n}return _inherits(o,Error),o}(),e=function(){function u(){_classCallCheck(this,u)}return _createClass(u,null,[{key:"Update",value:function(){l("time[datetime]:not(.nodt)").addClass("dynt").each(function(){var e=l(this),t=e.attr("datetime");if("string"!=typeof t)throw new TypeError('Invalid date data type: "'+(void 0===t?"undefined":_typeof(t))+'"');var n=moment(t);if(!n.isValid())throw new d('Invalid date format: "'+t+'"',this);var o=moment(),r=!e.attr("data-noweekday"),i=n.from(o),a=e.parent().children(".dynt-el"),s=e.data("dyntime-beforeupdate");if("function"==typeof s&&!1===s(u.Difference(o.toDate(),n.toDate())))return;0<a.length||e.hasClass("no-dynt-el")?(e.html(n.format(r?c.orderwd:c.order)),a.html(i)):e.attr("title",n.format(c.order)).html(i)})}},{key:"Difference",value:function(e,t){var n=(e.getTime()-t.getTime())/1e3,o={past:0<n,time:Math.abs(n),target:t,week:0,month:0,year:0},r=o.time;return o.day=Math.floor(r/this.InSeconds.day),r-=o.day*this.InSeconds.day,o.hour=Math.floor(r/this.InSeconds.hour),r-=o.hour*this.InSeconds.hour,o.minute=Math.floor(r/this.InSeconds.minute),r-=o.minute*this.InSeconds.minute,o.second=Math.floor(r),7<=o.day&&(o.week=Math.floor(o.day/7),o.day-=7*o.week),4<=o.week&&(o.month=Math.floor(o.week/4),o.week-=4*o.month),12<=o.month&&(o.year=Math.floor(o.month/12),o.month-=12*o.year),o}}]),u}();e.InSeconds={year:31557600,month:2592e3,week:604800,day:86400,hour:3600,minute:60},(window.Time=e).Update(),setInterval(e.Update,1e4)}(jQuery),$.capitalize=function(e,t){return t?e.replace(/((?:^|\s)[a-z])/g,function(e){return e.toUpperCase()}):1===e.length?e.toUpperCase():e[0].toUpperCase()+e.substring(1)},"function"!=typeof Array.prototype.includes&&(Array.prototype.includes=function(e){return-1!==this.indexOf(e)}),"function"!=typeof String.prototype.includes&&(String.prototype.includes=function(e){return-1!==this.indexOf(e)}),$.pad=function(e,t,n,o){if("string"!=typeof e&&(e=""+e),"string"!=typeof t&&(t="0"),"boolean"!=typeof o&&(o=!0),(n="number"!=typeof n&&!isFinite(n)&&isNaN(n)?2:parseInt(n,10))<=e.length)return e;var r=new Array(n-e.length+1).join(t);return e=o===$.pad.left?r+e:e+r},$.pad.right=!($.pad.left=!0),$.scaleResize=function(e,t,n){var o=!(3<arguments.length&&void 0!==arguments[3])||arguments[3],r=void 0,i={scale:n.scale,width:n.width,height:n.height};if(isNaN(i.scale))if(isNaN(i.width)){if(isNaN(i.height))throw new Error("[scalaresize] Invalid arguments");o||(i.height=Math.min(i.height,t)),r=i.height/t,!o&&1<r&&(r=1),i.width=Math.round(e*r),i.scale=r}else o||(i.width=Math.min(i.width,e)),r=i.width/e,!o&&1<r&&(r=1),i.height=Math.round(t*r),i.scale=r;else(o||i.scale<=1)&&(i.height=Math.round(t*i.scale),i.width=Math.round(e*i.scale));return i},$.clearSelection=function(){if(window.getSelection){var e=window.getSelection();e.empty?e.empty():e.removeAllRanges&&e.removeAllRanges()}else document.selection&&document.selection.empty()},$.toArray=function(e){var t=1<arguments.length&&void 0!==arguments[1]?arguments[1]:0;return[].slice.call(e,t)},$.clearFocus=function(){document.activeElement!==$body[0]&&document.activeElement.blur()},$w.on("ajaxerror",function(){var e="";if(1<arguments.length){var t=$.toArray(arguments,1);if("abort"===t[1])return;e=" Details:<pre><code>"+t.slice(1).join("\n").replace(/</g,"&lt;")+"</code></pre>Response body:";var n=/^(?:<br \/>\n)?(<pre class='xdebug-var-dump'|<font size='1')/;n.test(t[0].responseText)?e+='<div class="reset">'+t[0].responseText.replace(n,"$1")+"</div>":"string"==typeof t[0].responseText&&(e+="<pre><code>"+t[0].responseText.replace(/</g,"&lt;")+"</code></pre>")}$.Dialog.fail(!1,"There was an error while processing your request."+e)}),$.mkAjaxHandler=function(t){return function(e){if("object"!==(void 0===e?"undefined":_typeof(e)))return console.log(e),void $w.triggerHandler("ajaxerror");"function"==typeof t&&t.call(e,e)}},$.callCallback=function(e,t,n){return"object"===(void 0===t?"undefined":_typeof(t))&&$.isArray(t)||(n=t,t=[]),"function"!=typeof e?n:e.apply(void 0,_toConsumableArray(t))},$.fn.mkData=function(e){var t=this.find(":input:valid").serializeArray(),n={};return $.each(t,function(e,t){/\[]$/.test(t.name)?(void 0===n[t.name]&&(n[t.name]=[]),n[t.name].push(t.value)):n[t.name]=t.value}),"object"===(void 0===e?"undefined":_typeof(e))&&$.extend(n,e),n},$.getCSRFToken=function(){var e=document.cookie.match(/CSRF_TOKEN=([a-f\d]+)/i);if(e&&e.length)return e[1];throw $.Dialog.fail(!1,'<p>A request could not be sent due to a missing CSRF_TOKEN, please <a class="send-feedback">let us know</a>. Additional information:</p><pre><code>'+(document.cookie||"&lt;empty&gt;")+"</code></pre>"),new Error("Missing CSRF_TOKEN")},$.ajaxPrefilter(function(e,t){if("GET"!==(t.type||e.type).toUpperCase()){var n=$.getCSRFToken();if(void 0===e.data&&(e.data=""),"string"==typeof e.data){var o=0<e.data.length?e.data.split("&"):[];o.push("CSRF_TOKEN="+n),e.data=o.join("&")}else e.data.CSRF_TOKEN=n}});var t=function(e){var t=void 0;if(e.responseJSON)t=e.responseJSON.message;else try{t=JSON.parse(e.responseText).message}catch(e){}$.Dialog.fail(!1,t)},n={400:t,401:function(){$.Dialog.fail(void 0,"Cross-site Request Forgery attack detected. Please <a class='send-feedback'>let us know</a> about this issue so we can look into it.")},403:t,404:t,405:t,500:function(){$.Dialog.fail(!1,'A request failed due to an internal server error. If this persists, please <a class="send-feedback">let us know</a>!')},503:function(){$.Dialog.fail(!1,'A request failed because the server is temporarily unavailable. This shouldn\'t take too long, please try again in a few seconds.<br>If the problem still persist after a few minutes, please let us know by clicking the "Send feedback" link in the footer.')},504:function(){$.Dialog.fail(!1,'A request failed because the server took too long to respond. A refresh should fix this issue, but if it doesn\'t, please <a class="send-feedback">let us know</a>.')}};$.ajaxSetup({dataType:"json",error:function(e){"function"!=typeof n[e.status]&&$w.triggerHandler("ajaxerror",$.toArray(arguments))},statusCode:n});var r,i,a,s,u,l,c,d=void 0,f=function(e,t){if(void 0===d||t){if(void 0===d&&(d=$.mk("span").attr({id:"copy-notify",class:e?void 0:"fail"}).html('<span class="typcn typcn-clipboard"></span> <span class="typcn typcn-'+(e?"tick":"cancel")+'"></span>').appendTo($body)),t){var n=d.outerWidth(),o=d.outerHeight(),r=t.clientY-o/2;return d.stop().css({top:r,left:t.clientX-n/2,bottom:"initial",right:"initial",opacity:1}).animate({top:r-20,opacity:0},1e3,function(){$(this).remove(),d=void 0})}d.fadeTo("fast",1)}else d.stop().css("opacity",1);d.delay(e?300:1e3).fadeTo("fast",0,function(){$(this).remove(),d=void 0})};$.copy=function(e,t){if(void 0===navigator.clipboard){if(!document.queryCommandSupported("copy"))return prompt("Copy with Ctrl+C, close with Enter",e),!0;var n=$.mk("textarea"),o=!1;n.css({opacity:0,width:0,height:0,position:"fixed",left:"-10px",top:"50%",display:"block"}).text(e).appendTo("body").focus(),n.get(0).select();try{o=document.execCommand("copy")}catch(e){}setTimeout(function(){n.remove(),f(o,t)},1)}else navigator.clipboard.writeText(e).then(function(){f(!0,t)}).catch(function(){f(!1,t)})},$.compareFaL=function(e,t){return JSON.stringify(e)===JSON.stringify(t)},$.rgb2hex=function(e){return $.RGBAColor.fromRGB(e).toHex()},"function"!=typeof $.expr[":"].valid&&($.expr[":"].valid=function(e){return"object"===_typeof(e.validity)?e.validity.valid:(t=$(e),n=t.attr("pattern"),o=t.hasAttr("required"),r=t.val(),!(o&&("string"!=typeof r||!r.length))&&(!n||new RegExp(n).test(r)));var t,n,o,r}),$.roundTo=function(e){var t=1<arguments.length&&void 0!==arguments[1]?arguments[1]:0;0===t&&console.warn("$.roundTo called with precision 0; you might as well use Math.round");var n=Math.pow(10,t);return Math.round(e*n)/n},$.average=function(e){return e.reduce(function(e,t){return e+t},0)/e.length},$.clamp=function(e,t,n){return Math.min(n,Math.max(t,e))},$.clampCycle=function(e,t,n){return n<e?t:e<t?n:e},$.fn.select=function(){var e=document.createRange();e.selectNodeContents(this.get(0));var t=window.getSelection();t.removeAllRanges(),t.addRange(e)},$.momentToYMD=function(e){return e.format("YYYY-MM-DD")},$.momentToHM=function(e){return e.format("HH:mm")},$.mkMoment=function(e,t,n){return moment(e+"T"+t+(n?"Z":""))},$.nth=function(e){switch(e%10){case 1:return e+(/11$/.test(e+"")?"th":"st");case 2:return e+(/12$/.test(e+"")?"th":"nd");case 3:return e+(/13$/.test(e+"")?"th":"rd");default:return e+"th"}},$.escapeRegex=function(e){return e.replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&")},$.fn.toggleHtml=function(e){return this.html(e[$.clampCycle(e.indexOf(this.html())+1,0,e.length-1)])},$.fn.moveAttr=function(n,o){return this.each(function(){var e=$(this),t=e.attr(n);void 0!==t&&e.removeAttr(n).attr(o,t)})},$.fn.backgroundImageUrl=function(e){return this.css("background-image",'url("'+e.replace(/"/g,"%22")+'")')},$.attributifyRegex=function(e){return"string"==typeof e?e:e.toString().replace(/(^\/|\/[img]*$)/g,"")},$.fn.patternAttr=function(e){if(void 0===e)throw new Error("$.fn.patternAttr: regex is undefined");return this.attr("pattern",$.attributifyRegex(e))},$.fn.enable=function(e){return void 0!==e&&this.removeClass(e),this.prop("disabled",!1)},$.fn.disable=function(e){return void 0!==e&&this.removeClass(e),this.prop("disabled",!0)},$.fn.hasAttr=function(e){var t=this.get(0);return t&&t.hasAttribute(e)},$.fn.isOverflowing=function(){var e=this.get(0),t=e.style.overflow;t&&"visible"!==t||(e.style.overflow="hidden");var n=e.clientWidth<e.scrollWidth||e.clientHeight<e.scrollHeight;return e.style.overflow=t,n},r=jQuery,i=function(){return!1},a="mousewheel scroll keydown",s="html,body",r.scrollTo=function(e,t,n){r(s).on(a,i).animate({scrollTop:e},t,n).off(a,i),$w.on("beforeunload",function(){r(s).stop().off(a,i)})},$.getAceEditor=function(e,t,n){var o=function(){$.Dialog.clearNotice(),n("ace/mode/"+t)};void 0===window.ace?($.Dialog.wait(e,"Loading Ace Editor"),$.getScript("/js/min/ace/ace.js",function(){window.ace.config.set("basePath","/js/min/ace"),o()}).fail(function(){return $.Dialog.fail(!1,"Failed to load Ace Editor")})):o()},$.aceInit=function(e){e.$blockScrolling=1/0,e.setShowPrintMargin(!1);var r=e.getSession();return r.setUseSoftTabs(!1),r.setOption("indentedSoftWrap",!1),r.setOption("useWorker",!0),r.on("changeAnnotation",function(){for(var e=r.getAnnotations()||[],t=0,n=e.length,o=!1;t<n;)/doctype first\. Expected/.test(e[t].text)?(e.splice(t,1),n--,o=!0):t++;o&&r.setAnnotations(e)}),r},$.isInViewport=function(e){var t=void 0;try{t=e.getBoundingClientRect()}catch(e){return!0}return 0<t.bottom&&0<t.right&&t.left<(window.innerWidth||document.documentElement.clientWidth)&&t.top<(window.innerHeight||document.documentElement.clientHeight)},$.fn.isInViewport=function(){return!!this[0]&&$.isInViewport(this[0])},$.loadImages=function(e){var n=$(e);return new Promise(function(t){n.find("img").length?n.find("img").on("load error",function(e){t({$el:n,e:e})}):t({$el:n})})},$.isRunningStandalone=function(){return window.matchMedia("(display-mode: standalone)").matches},window.sidebarForcedVisible=function(){return 1200<=Math.max(document.documentElement.clientWidth,window.innerWidth||0)},window.withinMobileBreakpoint=function(){return Math.max(document.documentElement.clientWidth,window.innerWidth||0)<=650},$.randomString=function(){return parseInt(Math.random().toFixed(20).replace(/[.,]/,""),10).toString(36)},$.hrefToPath=function(e){return e.replace(/^.*?[\w\d]\//,"/")},l=[!(u=[/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})?$/i,/^#?([a-f\d])([a-f\d])([a-f\d])$/i,/^rgba?\(\s*(\d+),\s*(\d+),\s*(\d+)(?:,\s*([10]|0?\.\d+))?\s*\)$/i]),!1,!0],(c=function(){function i(e,t,n){var o=3<arguments.length&&void 0!==arguments[3]?arguments[3]:1;_classCallCheck(this,i),this.red=isNaN(e)?NaN:parseFloat(e),this.green=isNaN(e)?NaN:parseFloat(t),this.blue=isNaN(e)?NaN:parseFloat(n),this.alpha=parseFloat(o)}return _createClass(i,[{key:"setRed",value:function(e){return this.red=e,this}},{key:"setGreen",value:function(e){return this.green=e,this}},{key:"setBlue",value:function(e){return this.blue=e,this}},{key:"setAlpha",value:function(e){return this.alpha=e,this}},{key:"isTransparent",value:function(){return 1!==this.alpha}},{key:"yiq",value:function(){return(299*this.red+587*this.green+114*this.blue)/1e3}},{key:"isLight",value:function(){return 127<this.yiq()||this.alpha<.5}},{key:"isDark",value:function(){return!this.isLight()}},{key:"toHex",value:function(){return"#"+($.pad(this.red.toString(16))+$.pad(this.green.toString(16))+$.pad(this.blue.toString(16))).toUpperCase()}},{key:"toHexa",value:function(){return this.toHex()+$.pad(Math.round(255*this.alpha).toString(16)).toUpperCase()}},{key:"toRGB",value:function(){return"rgb("+this.red+","+this.green+","+this.blue+")"}},{key:"toRGBA",value:function(){return"rgba("+this.red+","+this.green+","+this.blue+","+this.alpha+")"}},{key:"toRGBString",value:function(){return this.isTransparent()?this.toRGBA():this.toRGB()}},{key:"toHexString",value:function(){return this.isTransparent()?this.toHexa():this.toHex()}},{key:"toString",value:function(){return this.isTransparent()?this.toRGBA():this.toHex()}},{key:"toRGBArray",value:function(){return[this.red,this.green,this.blue]}},{key:"invert",value:function(){var e=0<arguments.length&&void 0!==arguments[0]&&arguments[0];return this.red=255-this.red,this.green=255-this.green,this.blue=255-this.blue,e&&(this.alpha=1-this.alpha),this}},{key:"round",value:function(){return 0<arguments.length&&void 0!==arguments[0]&&arguments[0]?new $.RGBAColor.fromRGB(this).round():(this.red=Math.round(this.red),this.green=Math.round(this.green),this.blue=Math.round(this.blue),this.alpha=$.roundTo(this.alpha,2),this)}}],[{key:"_parseWith",value:function(e,t,n){var o=e.match(t);if(!o)return null;var r=o.slice(1,5);return l[n]||(1===r[0].length&&(r=r.map(function(e){return e+e})),r[0]=parseInt(r[0],16),r[1]=parseInt(r[1],16),r[2]=parseInt(r[2],16),void 0!==r[3]&&(r[3]=$.roundTo(parseInt(r[3],16)/255,3))),new(Function.prototype.bind.apply(i,[null].concat(_toConsumableArray(r))))}},{key:"parse",value:function(o){var r=this,i=null;return o instanceof $.RGBAColor?o:("string"==typeof o&&(o=o.trim(),$.each(u,function(e,t){var n=r._parseWith(o,t,e);if(null!==n)return i=n,!1})),i)}},{key:"fromRGB",value:function(e){return new $.RGBAColor(e.r||e.red,e.g||e.green,e.b||e.blue,e.a||e.alpha||1)}}]),i}()).COMPONENTS=["red","green","blue"],$.RGBAColor=c,$.each(["put","delete"],function(e,i){$[i]=function(e,t,n,o){var r;return"function"==typeof(r=t)&&"number"!=typeof r.nodeType&&(o=o||n,n=t,t=void 0),jQuery.ajax(jQuery.extend({url:e,type:i,dataType:o,data:t,success:n},$.isPlainObject(e)&&e))}}),void 0!==$.API&&$.each(["get","post","put","delete"],function(e,t){var i;i=t,$.API[i]=function(e){for(var t,n=arguments.length,o=Array(1<n?n-1:0),r=1;r<n;r++)o[r-1]=arguments[r];return(t=$)[i].apply(t,[$.API.API_PATH+e].concat(o))}})}}();
//# sourceMappingURL=/js/min/shared-utils.js.map
