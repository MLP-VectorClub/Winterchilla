"use strict";
/*! @source http://purl.eligrey.com/github/canvas-toBlob.js/blob/master/canvas-toBlob.js */
/*! @source http://purl.eligrey.com/github/canvas-toBlob.js/blob/master/canvas-toBlob.js */
!function(t){var d,f=t.Uint8Array,o=t.HTMLCanvasElement,e=o&&o.prototype,b=/\s*;\s*base64\s*(?:;|$)/i,r="toDataURL";f&&(d=new f([62,-1,-1,-1,63,52,53,54,55,56,57,58,59,60,61,-1,-1,-1,0,-1,-1,-1,0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,-1,-1,-1,-1,-1,-1,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51])),!o||e.toBlob&&e.toBlobHD||(e.toBlob||(e.toBlob=function(t,o){if(o||(o="image/png"),this.mozGetAsFile)t(this.mozGetAsFile("canvas",o));else if(this.msToBlob&&/^\s*image\/png\s*(?:$|;)/i.test(o))t(this.msToBlob());else{var e,n=Array.prototype.slice.call(arguments,1),i=this[r].apply(this,n),s=i.indexOf(","),a=i.substring(s+1),l=b.test(i.substring(0,s));Blob.fake?((e=new Blob).encoding=l?"base64":"URI",e.data=a,e.size=a.length):f&&(e=l?new Blob([function(t){for(var o,e,n=t.length,i=new f(n/4*3|0),s=0,a=0,l=[0,0],b=0,r=0;n--;)e=t.charCodeAt(s++),255!==(o=d[e-43])&&void 0!==o&&(l[1]=l[0],l[0]=e,r=r<<6|o,4==++b&&(i[a++]=r>>>16,61!==l[1]&&(i[a++]=r>>>8),61!==l[0]&&(i[a++]=r),b=0));return i}(a)],{type:o}):new Blob([decodeURIComponent(a)],{type:o})),t(e)}}),!e.toBlobHD&&e.toDataURLHD?e.toBlobHD=function(){r="toDataURLHD";var t=this.toBlob();return r="toDataURL",t}:e.toBlobHD=e.toBlob)}("undefined"!=typeof self&&self||"undefined"!=typeof window&&window||(void 0).content||void 0);