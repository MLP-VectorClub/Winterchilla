"use strict";!function(){var u=new RegExp("^/(.*)/([a-z]*)$","u");Array.from(document.querySelectorAll(".datastore")).forEach(function(r){var t=JSON.parse(r.innerText),e=!0,n=!1,a=void 0;try{for(var o,i=Object.keys(t)[Symbol.iterator]();!(e=(o=i.next()).done);e=!0){var f=o.value;if(!(f in window)){var c=t[f];if("string"==typeof c&&u.test(c)){var l=c.match(u);c=new RegExp(l[1],l[2])}window[f]=c}}}catch(r){n=!0,a=r}finally{try{!e&&i.return&&i.return()}finally{if(n)throw a}}})}();
//# sourceMappingURL=/js/min/datastore.js.map