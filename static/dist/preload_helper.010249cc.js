!function(){"use strict";var t="/antadmin/".replace(/([^/])$/,"$1/"),e=location.pathname,n=e.startsWith(t)&&decodeURI("/".concat(e.slice(t.length)));if(n){var a=document,c=a.head,r=a.createElement.bind(a),i=function(t,e,n){var a,c=e.r[t]||(null===(a=Object.entries(e.r).find((function(e){var n=e[0];return new RegExp("^".concat(n.replace(/\/:[^/]+/g,"/[^/]+").replace("/*","/.+"),"$")).test(t)})))||void 0===a?void 0:a[1]);return null==c?void 0:c.map((function(t){var a=e.f[t][1],c=e.f[t][0];return{type:c.split(".").pop(),url:"".concat(n.publicPath).concat(c),attrs:[["data-".concat(e.b),"".concat(e.p,":").concat(a)]]}}))}(n,{"p":"ant-design-pro","b":"webpack","f":[["p__dev__setting.34e7765c.async.js",132],["212.82ef9a37.async.js",212],["p__dev__test.f11714d1.async.js",263],["t__plugin-layout__Layout.5012e1ab.chunk.css",301],["t__plugin-layout__Layout.f8c1fb58.async.js",301],["390.ff8de9ea.async.js",390],["p__404.9b003890.chunk.css",571],["p__404.1b706cef.async.js",571],["687.b119e164.async.js",687],["p__403.8ca11ad4.async.js",864],["p__login__index.5b9f8fa4.async.js",939]],"r":{"/*":[1,6,7,3,4,8],"/":[1,6,7,3,4,8],"/login":[10],"/403":[9,3,4,8],"/dev/menu":[3,4,8],"/dev/setting":[0,3,4,8],"/dev/model":[3,4,8],"/dev/test":[2,3,4,8]}},{publicPath:"/antadmin/"});null==i||i.forEach((function(t){var e,n=t.type,a=t.url;if("js"===n)(e=r("script")).src=a,e.async=!0;else{if("css"!==n)return;(e=r("link")).href=a,e.rel="preload",e.as="style"}t.attrs.forEach((function(t){e.setAttribute(t[0],t[1]||"")})),c.appendChild(e)}))}}();