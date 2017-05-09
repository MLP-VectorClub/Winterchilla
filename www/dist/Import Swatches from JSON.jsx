"use strict";var _typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e};!function(nil){function _RGBColor(e,t,o){var n=new RGBColor;return n.red=e,n.green=t,n.blue=o,n}function _ColorGroup(e){var t=_Document.swatchGroups.add();return t.name=e,t}function _Swatch(e,t,o,n){var r=activeDocument.swatches.add();return r.name=e,r.color=_RGBColor(t,o,n),r}var swimg,JSON={};!function(){function f(e){return 10>e?"0"+e:e}function this_value(){return this.valueOf()}function quote(e){return rx_escapable.lastIndex=0,rx_escapable.test(e)?'"'+e.replace(rx_escapable,function(e){var t=meta[e];return"string"==typeof t?t:"\\u"+("0000"+e.charCodeAt(0).toString(16)).slice(-4)})+'"':'"'+e+'"'}function str(e,t){var o,n,r,i,a,u=gap,s=t[e];switch(s&&"object"==(void 0===s?"undefined":_typeof(s))&&"function"==typeof s.toJSON&&(s=s.toJSON(e)),"function"==typeof rep&&(s=rep.call(t,e,s)),void 0===s?"undefined":_typeof(s)){case"string":return quote(s);case"number":return isFinite(s)?String(s):"null";case"boolean":case"null":return String(s);case"object":if(!s)return"null";if(gap+=indent,a=[],"[object Array]"===Object.prototype.toString.apply(s)){for(i=s.length,o=0;i>o;o+=1)a[o]=str(o,s)||"null";return r=0===a.length?"[]":gap?"[\n"+gap+a.join(",\n"+gap)+"\n"+u+"]":"["+a.join(",")+"]",gap=u,r}if(rep&&"object"==(void 0===rep?"undefined":_typeof(rep)))for(i=rep.length,o=0;i>o;o+=1)"string"==typeof rep[o]&&(n=rep[o],(r=str(n,s))&&a.push(quote(n)+(gap?": ":":")+r));else for(n in s)Object.prototype.hasOwnProperty.call(s,n)&&(r=str(n,s))&&a.push(quote(n)+(gap?": ":":")+r);return r=0===a.length?"{}":gap?"{\n"+gap+a.join(",\n"+gap)+"\n"+u+"}":"{"+a.join(",")+"}",gap=u,r}}var rx_one=/^[\],:{}\s]*$/,rx_two=/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,rx_three=/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,rx_four=/(?:^|:|,)(?:\s*\[)+/g,rx_escapable=/[\\"\u0000-\u001f\u007f-\u009f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,rx_dangerous=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;"function"!=typeof Date.prototype.toJSON&&(Date.prototype.toJSON=function(){return isFinite(this.valueOf())?this.getUTCFullYear()+"-"+f(this.getUTCMonth()+1)+"-"+f(this.getUTCDate())+"T"+f(this.getUTCHours())+":"+f(this.getUTCMinutes())+":"+f(this.getUTCSeconds())+"Z":null},Boolean.prototype.toJSON=this_value,Number.prototype.toJSON=this_value,String.prototype.toJSON=this_value);var gap,indent,meta,rep;"function"!=typeof JSON.stringify&&(meta={"\b":"\\b","\t":"\\t","\n":"\\n","\f":"\\f","\r":"\\r",'"':'\\"',"\\":"\\\\"},JSON.stringify=function(e,t,o){var n;if(gap="",indent="","number"==typeof o)for(n=0;o>n;n+=1)indent+=" ";else"string"==typeof o&&(indent=o);if(rep=t,t&&"function"!=typeof t&&("object"!=(void 0===t?"undefined":_typeof(t))||"number"!=typeof t.length))throw new Error("JSON.stringify");return str("",{"":e})}),"function"!=typeof JSON.parse&&(JSON.parse=function(text,reviver){function walk(e,t){var o,n,r=e[t];if(r&&"object"==(void 0===r?"undefined":_typeof(r)))for(o in r)Object.prototype.hasOwnProperty.call(r,o)&&(n=walk(r,o),void 0!==n?r[o]=n:delete r[o]);return reviver.call(e,t,r)}var j;if(text=String(text),rx_dangerous.lastIndex=0,rx_dangerous.test(text)&&(text=text.replace(rx_dangerous,function(e){return"\\u"+("0000"+e.charCodeAt(0).toString(16)).slice(-4)})),rx_one.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,"@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,"]").replace(/(?:^|:|,)(?:\s*\[)+/g,"")))return j=eval("("+text+")"),"function"==typeof reviver?walk({"":j},""):j;throw new SyntaxError("JSON.parse")})}(),swimg='\x89PNG\r\n\x1a\n\0\0\0\rIHDR\0\0\0\x88\0\0\x006\b\x06\0\0\0\x1cz\xd1W\0\0\0\x01sRGB\0\xae\xce\x1c\xe9\0\0\0\x04gAMA\0\0\xb1\x8f\v\xfca\x05\0\0\0\tpHYs\0\0\x0e\xc3\0\0\x0e\xc3\x01\xc7o\xa8d\0\0\x07jIDATx^\xed\x9aYk\x14K\x14\xc7\xe73\x88\x82\x1a\xd4\xbc\xb8\x04w\xd4\xa8\xd1\x04\xf7}\x1bqCT4\x8a+j\x10D\x10\x05}sA\x9c\xab\xe0\xae(.\xb8D\x8d\xfb\xbe\xe2\x16\x15Q\x82\xf8\xe2\x87\x90<\xdc\xfbx\xee\xfcO\xa6z\xaa\xab\xab;\x95\xcet\x9c\x81\xf3\xf0\xa3\xab\xaa\xeb\xd4\xa9\xd4\xf9\xd7\xa9\xea\xd1\xd4\xb0a\xc3H\xd1\xbd{wA\xf0\x11\x10\xc8\xbcy\xf3h\xfe\xfc\xf9\x89\x80\xb1\xe1C\xf7)\x147\x01\x81\xa4\xd3\xe9D\x11\x81\x94\x16\xd6\f\x92$"\x90\xd2\xc2Y \xe5\xe5\xe5\x1e\xb6\xf7\xae\xb4G =z\xf4\xa0n\xdd\xba\xf1\xd3\xf6\xde\x05\xd8\x9b\xd8\xfa%\x89m\x0e6l\xb6q\xe8\xdf\xbf\xbf\x136\xdb\x80@\xe6\xce\x9d\xeb\xa1\x04\x81\xf2\x93\'O\xe8\xc7\x8f\x1f\xfc\x9c5k\x16\xa3\xf7u\xc5U C\x87\x0e\xa5\x81\x03\x07REE\x05?\xd1\xb6k\xd7.\xfa\xf6\xed\x1b?\xcd\xfe\xae\xbcy\xf3&@\x92\xc1\xb1\xf1\xfc\xf9s\'l\xb6qX\xb2d\x89\x136\xdb\x80@\xe6\xcc\x99\xe3\xf1\xf8\xf1cF\x95\xd5s\xc8\x90!4~\xfcx__W\\\x04\x02Q Heee\x9c-\xbav\xedJ]\xbat\xa1\x07\x0f\x1ePSS\x13\xfd\xfc\xf9\xd3j\xe7\xc2\xcb\x97/\x03u\x1bx\x07a\x0e\x1a4\xc8\xd7\xbf\x10 \xf8\xfb\xf7\xefgPV\x97x\x94\xf7\xee\xdd\xcb\x94\xb4@\xde\xbd{G\x07\x0f\x1e\xa4\x993g\xfa\xfa\xbb\xe0"\x90\x9e={\xd2\xaaU\xab\xe8\xee\xdd\xbb\xd4\xd0\xd0@uuu\xb4x\xf1b\xfa\xfe\xfd;\xfd\xfe\xfd\x9bN\x9d:e\xb5s\xe1\xe9\xd3\xa7\xd6v\x9d#G\x8e\xb08\xf1\xf7\x8d\x1b7\x8eE\x1a\x96\x82\xe3\xf07\x04bk\xd7q\x16\xc8\xec\xd9\xb3=\xee\xdf\xbf\xcf\xa8r\xaf^\xbd\xbc\xb6g\xcf\x9e\xd1\xd4\xa9S}\xfd]p\x11\xc8\x9a5k\xe8\xf4\xe9\xd3\x9c\xa5\xe0s\xe9\xd2\xa5|\xbcm\xdf\xbe\x9d\xfa\xf4\xe9c\xb5q\xe5\xd1\xa3G\xd6v\b\0\xc7)2\xd7\xc8\x91#\xf9\xe8\xc1\xdf\x88@\xe1X\xc5\x02\x16J$\x18\xcf\x05\x9bm\x1cT\xf0\x97/_\xee=u\xf4>&\x01\x81\xa8\xfb\x05\xc0\xee\x05z\x19\x1c:t\x88\x17p\xe3\xc6\x8d\xbe\xfe.\xb8\b\xa4\xbe\xbe\x9eF\x8f\x1eM\x9b6m\xa2\x87\x0f\x1f\xd2\x8b\x17/\xf8\x0f@&\x19<x\xb0\xd5\xc6\x15\x88\xdbl\xeb\xd7\xaf\x1f-Z\xb4\x88.\\\xb8@\x1f>|\xa0\xb7o\xdf\xd2\xea\xd5\xaby\x0e#F\x8c\xa0\xad[\xb7r[\xd8"\xb6\x95{\xf7\xeeyY\x03edc\x80r\xa7N\x9d\x18\x94m\xb6q@\xf6\xb5\xb5\xeb\x84\xf5\x89\x14\xc8\x8d\x1b7\x18\xbd\xac\xea\xd8](\xd7\xd4\xd4\xf8lZ\xc3E \x10\x1f\xee!w\xee\xdc\xe1\xfb\0\x82\n\xbb\xf6\x1c-\n\b\xdclCfB@0\xbf\xb1c\xc7\xd2\xb4i\xd38\xfd\xdf\xbe}\x9b\xae]\xbb\xc6ul\x06\x9bm\x1cn\xdd\xba\xe5\t\x04e%\x10\x94\x95@P\xb6\xd9\xc6a\xe1\xc2\x85\xfc\x8c\xca \xaa\x8fI@ 8w\x15W\xae\\a\xf4\xb2\xaa\x1f>|\x98/\x8d+W\xae\xf4\xd9\xb4\x86\x8b@ \x8a\xea\xeajZ\xbf~=\x07\t\xc7\x02v\xf8\xda\xb5k\xf9\x88\xc1\x17\x8e\xcd\xce\x85\x9b7o\x06\xda \xc0\xe9\xd3\xa7\xd3\x8a\x15+\xe8\xdc\xb9s\xb4s\xe7N\xce\x1eg\xcf\x9e\xa5W\xaf^\xd1\xc5\x8b\x17\xb9\xfe\xfa\xf5\xeb\x80m\x1c :\x17l\xb6q\b\v\xbe\x8e\xb3@f\xcc\x98\xe1\x81\x94\v\xf4\xb2\xaac@\b\x04\x17\xba\t\x13&p[\xef\xde\xbd\x03\xa8\xb1\x14.\x02\xd9\xb6m\x1b\v\x03\xbf\x9b\xe0^\0_\xef\xdf\xbf\xa7)S\xa6P\xe7\xce\x9d\xdb\xf5;\x88m\xe1q\x84\xe1\xdeq\xe9\xd2%\xbeH\xef\xdb\xb7\x8f\xf6\xec\xd9C\'O\x9e\xe4L\t\xd1\xe0\xfd\xc7\x8f\x1f\x03\xb6q\x80\x1f\x95)\x94O\x80\xb2\xba\xa4\xa2\x8c\xbe\xfa\xa7\xb7\xc2\x1c\xaf5\x16,X\xc0\xcf\xa8\f\xa2\xfa\x98\x04\x04\x82\x9d\xa48s\xe6\f\xa3\x97U\x1d\x1c?~\x9c\xae_\xbf\xce\x97H\xd4q_0Q}\x15.\x02\x01\x10\t\x02\x87]\x8b\xbb\xc7\xe6\xcd\x9b\xf9H\x83(\xb7l\xd9b\xb5q\xe1\xf2\xe5\xcb\x81\xb6/_\xbe\xf0\xd7\n\xfc C"C!\xfd\xe3\xa2\f\xf0\xae\xb6\xb6\x96\x8f>\xd36\x0e\x10\x9c\x12\b\xcaJ (+\x81\xa0\x8c\xbe\xc8x&\xe6x\xad\x81\xbf\xc5\xd6\xae\x13\xd6\' \x10\x9c\xb7\n\xec\x1e\xa0\x97U\x1d,[\xb6\x8c/o;v\xec\xf0\xdapoP\xa86\x1dW\x81\xe8\xe0b\xda\xb7o_>z\xe0_\xfdN\x11\x07\xccW\x07m\'N\x9c`\xe1\xe3X\xc3\x1d\xe4\xe8\xd1\xa3\x9c\xbd*++i\xd4\xa8Q|\xb4\xe1\xd3~\xc3\x86\r\x81\xf1\xe2\0\xe1\xe3"\x0eP\x0eC\xf5\xbfz\xf5\xaa\x87>\x8e+f\xc6\b\xc3f\x1b\x10\b>]\xdb\x02\xfe\x01\xcel;\x7f\xfe|\xa0M\x11G \n|=}\xfe\xfc\x99\x9f\xb6\xf7q\x81\b2\x99\f\x7fZ\xe2r\b\x1a\x1b\x1b=\xb0kq\xd7\xb2\xd9v\x14\xc7\x8e\x1d\xb3\xb6\'M@ 8\xe7\x93\xa4=\x02\x11:\x9e\x80@&O\x9e\x9c("\x90\xd2" \x90I\x93&%\x8a\b\xa4\xb4\b\b\x04\x9f\xac\x13\'NL\x04\x8c-\x02)-R\xf8]A\x10\xc2H\x8d\x193\x86\x14_\xbf~\x15\x04\x1f\xa9\xe1\xc3\x87\x93\x02\r\xcd\xcd\xcd\x82\xe0!\x02\x11"\t\x15H\xe6\xd3\xbf\x1d\xca?\x8d\xff\tE\x88\bD\x88D\x04"D"\x02\x11"\xf1\xfd\x0e"\x02\x11LR\xfa\x7f\x07\x14\x81\b&\xc5-\x90L-\xa5R\xa9\x1c\xb5\xb4\xce\xd6\xa7Pt\xa4/\x1f\rT\x95\xaa\xa4t\xbd\xed\xdd\xdf\xa7x\x05R\x7f\x80\xca\xf5@e\xeb\xe9\x8c\xf6\xbe\x90$\xe2\xcb5\xf0"\x10\'\x02\x93\xc3\x8e\x1ep\x80v\x9b\xedI\x90\x88/\x11HA\tN\x0e\v\x97\xa2\xf2\xba\xa6\xc0\xbbuiu\x14dI7d\xdb\x9a(= EU\xfa\xae\xd7\x83\xce\x19"\xea\xf8\xb0\xfb\x82\x9f\xfc\x98F 1&\xfb\xb6\xcd\xa7e<\x7f\x1b\xecZ\xe6\xa9\xda[\xc6\xce\x8d\x9b\xc9\xcf\xd17\x0f\xeb\xdcm\xe3$C\x91_R\xf3\va_\x04-h\x10\x84\x17\b=\xb8F`\xb3\xfdl\xa2\xb3\xfa\xd2\xc7\x84\xdd\x80J\xcfvw]\xbe\x9cG\xf7e\xf8\xcd\x8do\xb7\xc9\xfaUb\x86OO\b!s\xd7\xe7\x950E.\x90\x1c\xb9]\xe4\v\x1c\x16\x95\xd1\x03\xa2-\xac5{\xe4\x88Z\\\xdd\x17\xca\xb9q\xd6\xa5\xb3~\xb0\xcb\xb9\x8e`\xfb\x03g\x9f\x8f\xd6\x87\xc7\r\xcb^Z?\xbd\x1e6\xf7\\\xbb]\xe8\x85\xa54\x04\x92\x05;6\xbf8z\xaa\xcd/\xae\xda\xd5\xbe\xdd\x1d\x1a\x98p<_\xde\xf8Jp\xa8g\xc7\xc2\x98>\x01\xda\xe6S(\x81\x84\xcf\x9d\xe7\x99\x15\x8a=\xbb\x16\x86"\xbe\xa4f\xbf$\xbcE\xc3\xc2\xe7v\fv\xab/8F\x10\xb2\x01\xacB\x10\xd58\xbc\xe0\xad\xec\xb60_\xd9:\x8b\xcd8Z\xaa\xd2\xda1\x15:\x1f3\xf0\xfeq\xf3D\b\xc4a\xee\xf9\xcd`\xda\x85\xcd\xa3m\x14\xfd%\xd5\x97Z\xb9\xbde\xa1\xb9\x8d\xc5\xa0\xff\xf1\xb9w\xe6\x11\xc2\x81\xcb\xd9\xf8\xc6R\x84\xf9R\xb6\x9a\x0f\xb3\x1e1\x1f\xef\xf2\xea\x8d\xe7\xf7c\xbd#\x99u\xdb\xdc}G\x9a\xda\f\xba]X\xb9\xed\x94\xcc\x11#\xfc\x1dD B$"\x10!\x12\x11\x88\x10\x89\bD\x88D\x04"D"\x02\x11"\t\x15\xc8\x9f?\x7f:\x14\xe5W(.D B$"\x10!\x92\xa2\x11\xc8\xaf_\xbf\x84"D2\x88\x10\x89\bD\x88D\x04"D"\x02\x11"h\xa6\xff\x01 \x8c\xe2\xe9n\xe9\xc4`\0\0\0\0IEND\xaeB`\x82';var _Version=function(e){var t=e.split(".");this.major=t[0],this.minor=t[1]};_Version.prototype.toString=function(){return this.major+"."+this.minor},_Version.prototype.base=function(){return this.major+".0"},_Version.prototype.compatibleWith=function(e){if(!(e instanceof _Version))throw new Error("ver must be an instance of Version");return this.major===e.major};var madeNewDoc=0===app.documents.length,_Document=madeNewDoc?app.documents.add(DocumentColorSpace.RGB):app.activeDocument,SCRIPT_VERSION=new _Version("1.4"),title="Import swatches from JSON (by MLP-VectorClub, version "+SCRIPT_VERSION+")",alert=function(e,t){var o=new Window("dialog",title,nil,{closeButton:!1}),n=-1!==e.indexOf("\n");return o.add("statictext",n?[0,0,340,70]:nil,e,{multiline:n}),"function"==typeof t&&t(o),o.add("group").add("button",nil,"OK"),!0!==t&&o.show(),{close:function(){o.close()}}},fin=function(e){alert(e,function(e){e.add("group").add("image",nil,swimg)})},wait=function(e){var t=new Window("palette",title,nil,{closeButton:!1}),o=-1!==e.indexOf("\n");return t.add("statictext",o?[0,0,340,70]:nil,e,{multiline:o}),t.show(),{close:function(){t.close()}}},importers={"1.0":function(e){for(var t in e)if(e.hasOwnProperty(t)){var o,n=e[t];if("object"===(void 0===n?"undefined":_typeof(n))){importSingleCG.value&&(o=new _ColorGroup(t));for(var r in n)if(n.hasOwnProperty(r)){var i=n[r];importSingleCG.value||(o=new _ColorGroup(r));for(var a in i)if(i.hasOwnProperty(a)){var u=i[a],s=r+" | "+a,l=/(^|\s)([A-Z])[^.\s]+/;for(s.length>31&&(s=s.replace(/\bHighlight\b/g,"HL"));s.length>31&&l.test(s);)s=s.replace(l,"$1$2.");o.addSwatch(new _Swatch(s,parseInt(u.substring(1,3),16),parseInt(u.substring(3,5),16),parseInt(u.substring(5,7),16)))}}}}}},fileDialog=new Window("dialog",title);fileDialog.add("statictext",[0,0,520,20],"Please click the button below and select the JSON file you got from our Color Guide.");var importSingleCG=fileDialog.add("checkbox",nil,"\xa0Import as a single swatch group");importSingleCG.value=!0,fileDialog.add("statictext",[0,0,520,35],"By default, all colors will be imported to a single swatch group.\nIf you'd like to have each color group in a separate swatch group, untick the checkbox above.",{multiline:!0});var btngrp=fileDialog.add("group"),browsebtn=btngrp.add("button",nil,"Browse..."),closebtn=btngrp.add("button",nil,"Cancel");browsebtn.onClick=function(){var e=File.openDialog("Find the JSON file downloaded from our Color Guide",$.os.match(/Macintosh/i)?function(e){return e.name.match(/\.json$/i)}:"JSON files:*.json");if(e){var t=wait("Importing...");e.open("r");var o=e.read();e.close();try{o=JSON.parse(o)}catch(e){return fileDialog.close(),alert("Error while parsing JSON (in "+e.fileName+":"+e.line+"):"+e+"\n"+e.stack)}"string"!=typeof o.Version&&(o.Version="1.4");var n=new _Version(o.Version),r=n.base();if(!n.compatibleWith(SCRIPT_VERSION)||"function"!=typeof importers[r])return alert("The selected JSON file (v"+n+") is not compatible with your current script file (v"+SCRIPT_VERSION+").\nPlease grab an updated script file from the guide to import this appearance.");if(madeNewDoc){for(var i,a=0;0!==_Document.swatchGroups.length&&a++<20;)for(i=0;i<_Document.swatchGroups.length;i++)_Document.swatchGroups[i].remove();for(a=0;0!==_Document.swatches.length&&a++<20;)for(i=0;i<_Document.swatches.length;i++)_Document.swatches[i].remove();madeNewDoc=!1}importers[r](o),t.close(),fileDialog.close(),fin("All color groups have been imported successfully.\nWe suggest that you remove any built-in swatches you don't need, then save the rest for future use.\n(Icon with books on Swatches panel > Save Swatches...)")}},closebtn.onClick=function(){fileDialog.close()},fileDialog.show()}();