"use strict";(self.webpackChunkant_design_pro=self.webpackChunkant_design_pro||[]).push([[587,35],{19048:function(Qt,X,n){n.r(X),n.d(X,{default:function(){return Jt}});var Ce=n(15009),k=n.n(Ce),Te=n(99289),Pe=n.n(Te),je=n(5574),z=n.n(je),be=n(13769),V=n.n(be),Ie=n(97857),m=n.n(Ie),q=n(97584),De=n(43425),w=n(38345),Se=n(99956),B=n(7837),_=n(15746),Fe=n(48096),Ze=n(71230),Re=n(48054),$=n(42075),ee=n(14726),Ee=n(86250),j=n(67294),Me=n(67666),$e=n(53463),Ne=n(89649),N=n(10936),Oe=n(55647),H=n(47666),ze=n(14056),G=n(65302),Ae=n(96604),Ve=n(6466),we=n(96074),Be=n(38703),Le=n(61503),Ue=n(63430),We=n(79611),He=n(78677),Ge=n(77682),Y=function(){return Y=Object.assign||function(h){for(var e,r=1,l=arguments.length;r<l;r++){e=arguments[r];for(var a in e)Object.prototype.hasOwnProperty.call(e,a)&&(h[a]=e[a])}return h},Y.apply(this,arguments)},Ye=(0,j.forwardRef)(function(h,e){return j.createElement(Ge.P,Y({},h,{chartType:"Area",ref:e}))}),Je=Ye,Ke=n(80652),te=n(96486),Qe=n(92077),J=n.n(Qe),Xe=n(58092),t=n(85893),ke=["type","data","config"],qe=["label"],_e=function(e){var r,l=e.type,a=e.data,s=e.config,d=V()(e,ke),i=(0,te.cloneDeep)(s),T=function(v){var b;return d==null||(b=d.fields)===null||b===void 0?void 0:b.find(function(Z){return(Z==null?void 0:Z.value)==v})};delete i.type;var o=function(v){for(var b=/{{\s*([^{}]*)\s*}}/g,Z=[],R;(R=b.exec(v))!==null;)Z.push(R[1].trim());return Z},y={};if((r=i.label)!==null&&r!==void 0&&r.text){var I=o(i.label.text);try{y.labelText=function(f){return new Function("$root","with($root) { return (".concat(I,"); }"))({d:f})}}catch(f){console.log("exp error",I)}}if(l=="pie"){var P=i.colorField,u=i.angleField,D=(0,te.sum)(a.map(function(f){return f[u]}));return(0,t.jsx)(Ue.Z,m()({appendPadding:10,data:a,label:{position:"outside",text:function(v){return"".concat(v[P],": ").concat(J()(v[u]).format("0,0"))},transform:[{type:"overlapDodgeY"}]},tooltip:function(v,b,Z,R){return{value:"".concat(v[P],": ").concat(J()(v[u]).format("0,0"))}},annotations:[{type:"text",style:{text:`\u603B\u8BA1
`.concat(J()(D).format("0,0")),x:"50%",y:"50%",textAlign:"center",fontSize:18}}],interactions:[{type:"element-active"}],radius:.9,innerRadius:.6,legend:{color:{maxCols:1,maxRows:1,position:"top"}}},i))}else{if(l=="bar")return(0,t.jsx)(We.Z,m()({data:a,scale:{x:{paddingInner:.4}}},i));if(l=="column"){var F=i.xField,p=i.yField,g=T(p),x=i.label,C=V()(i,qe);return(0,t.jsx)(He.Z,m()({data:a,scale:{x:{paddingInner:.4}},label:m()(m()({textBaseline:"bottom"},x),{},{text:y.labelText?y.labelText:function(f){return f==null?void 0:f[p]}}),style:{radiusTopLeft:10,radiusTopRight:10},tooltip:{name:g==null?void 0:g.label,field:p}},C))}else{if(l=="area")return(0,t.jsx)(Je,m()({data:a},i));if(l=="mapDots")return(0,t.jsx)(Le.O1,m()(m()({},i),{},{dots:(0,G.H1)(a)?a:[]}));if(l=="areaMap"){var c=m()({map:{style:"blank",center:[120.19382669582967,30.258134],zoom:13,pitch:0},source:{data:a,parser:{type:"geojson"}},autoFit:!0,color:{field:i.field,value:["#1A4397","#3165D1","#6296FE","#98B7F7","#DDE6F7","#F2F5FC"].reverse(),scale:{type:"quantile"}},style:{opacity:1,stroke:"#eee",lineWidth:.8,lineOpacity:1},state:{active:!0,select:{stroke:"blue",lineWidth:1.5,lineOpacity:.8}},label:{visible:!0,field:"name",style:{fill:"black",opacity:.5,fontSize:12,spacing:1,padding:[15,15]}},zoom:{position:"bottomright"},legend:{position:"bottomleft"}},i);return(0,t.jsx)(Xe.Z,{config:c,data:a})}else return(0,t.jsx)(Ke.Z,m()({data:a},i))}}},et=_e,tt=function(e){var r,l,a=e.title,s=e.data,d=e.config,i=d===void 0?{}:d,T=e.height,o=e.label,y=i.open,I=function(g,x){var C=g!=null&&g.href?g.href:x!=null&&x.href?x.href:null;if(C){var c,f;return(0,t.jsx)(B.Link,{to:C,style:{fontSize:12},children:(c=g.statistic)!==null&&c!==void 0&&c.description?(f=g.statistic)===null||f===void 0?void 0:f.description:"\u67E5\u770B"})}else{var v;return(v=g.statistic)===null||v===void 0?void 0:v.description}},P=function(){var g=arguments.length>0&&arguments[0]!==void 0?arguments[0]:[],x=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"horizontal",C=g.map(function(c,f){return(0,t.jsx)(Ae.Z,m()({},c),f)});return x=="vertical"?C:(0,t.jsx)($.Z,{children:C})},u=function(g,x){var C,c=g.footer||{type:"text"};if(!((C=g.open)!==null&&C!==void 0&&C.footer))return null;var f;if(c.type=="trend"){if(!x.trend)return null;f=P(x.trend,c==null?void 0:c.layout)}else{var v=x.footer?x.footer:c.text;if(!v)return null;f=v}return(0,t.jsxs)(t.Fragment,{children:[(0,t.jsx)(we.Z,{style:{marginTop:-16,marginBottom:10}}),f]})},D=y!=null&&y.statistic?m()(m()({},i==null?void 0:i.statistic),{},{value:(0,G.BP)(s)?s==null?void 0:s.value:s,description:I(i,s),title:i!=null&&(r=i.statistic)!==null&&r!==void 0&&r.title?i==null||(l=i.statistic)===null||l===void 0?void 0:l.title:o}):!1,F=function(g,x){var C=g.chart,c=C===void 0?{}:C,f=g.open,v=c.type,b=v===void 0?"":v;if(!(f!=null&&f.chart)||!b)return null;var Z=c!=null&&c.height?{height:c==null?void 0:c.height,lineHeight:(c==null?void 0:c.height)+"px"}:{};if(b=="trend"){var R;return(0,t.jsx)("div",{style:Z,children:P(x.trend,(R=g.chart)===null||R===void 0||(R=R.trend)===null||R===void 0?void 0:R.layout)})}else if(b=="progress"){var S;return(0,t.jsx)("div",{style:Z,children:(S=x.progress)===null||S===void 0?void 0:S.map(function(K,W){return(0,t.jsx)(Be.Z,m()({},K),W)})})}else return(0,t.jsx)(et,m()(m()({},x==null?void 0:x.chart),{},{config:c,type:b}));return null};return(0,t.jsx)(Ve.Z,{footer:u(i,s),style:{height:T||"100%"},title:a,statistic:D,chart:F(i,s)})},lt=tt,at=n(17003),nt=n(53110),le=n(51042),ae=n(52108),ne=n(47389),it=n(26706),ot=n(94261),rt=n(53682),st=n(93967),dt=n.n(st),ie=n(70145),ut=n(61723),oe=n(75686),re=n(10399),vt=n(19632),L=n.n(vt),ct=function(e){return[{valueType:"group",columns:[{title:"x\u8F74\u5B57\u6BB5",dataIndex:["defaultConfig","chart","xField"],colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"y\u8F74\u5B57\u6BB5",dataIndex:["defaultConfig","chart","yField"],colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},ve(6)]}]},ft=ct,pt=function(e){return[{valueType:"group",columns:[{title:"\u5B57\u6BB5",dataIndex:["defaultConfig","chart","field"],colProps:{span:12},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"\u533A\u95F4\u503Cmin",dataIndex:["defaultConfig","chart","domain_min"],colProps:{span:6},valueType:"digit",width:"100%"},{title:"\u533A\u95F4\u503Cmax",dataIndex:["defaultConfig","chart","domain_max"],colProps:{span:6},valueType:"digit",width:"100%"}]}]},gt=pt,mt=function(e){return[{valueType:"group",columns:[{title:"x\u8F74\u5B57\u6BB5",dataIndex:["defaultConfig","chart","xField"],colProps:{span:12},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"y\u8F74\u5B57\u6BB5",dataIndex:["defaultConfig","chart","yField"],colProps:{span:12},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}}]}]},xt=mt,ht=function(e){return[{valueType:"group",columns:[{title:"x\u8F74\u5B57\u6BB5",dataIndex:["defaultConfig","chart","xField"],colProps:{span:12},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"y\u8F74\u5B57\u6BB5",dataIndex:["defaultConfig","chart","yField"],colProps:{span:12},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}}]}]},yt=ht,Ct=function(e){return[{valueType:"group",columns:[{title:"x\u8F74\u5B57\u6BB5",dataIndex:["defaultConfig","chart","xField"],colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"y\u8F74\u5B57\u6BB5",dataIndex:["defaultConfig","chart","yField"],colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"\u5206\u7EC4\u5B57\u6BB5",tooltip:"\u591A\u6761\u7EBF\u53EF\u4F7F\u7528\u8BE5\u53C2\u6570",dataIndex:["defaultConfig","chart","colorField"],colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},ve(6)]}]},Tt=Ct,Pt=function(e){return[{valueType:"group",columns:[{title:"\u7F29\u653E\u7EA7\u522B",dataIndex:["defaultConfig","chart","zoom"],colProps:{span:12},valueType:"digit",width:"100%"},{title:"\u4E2D\u5FC3\u70B9lat",dataIndex:["defaultConfig","chart","lat"],colProps:{span:6},valueType:"digit",width:"100%"},{title:"\u4E2D\u5FC3\u70B9lng",dataIndex:["defaultConfig","chart","lng"],colProps:{span:6},valueType:"digit",width:"100%"}]}]},jt=Pt,bt=function(e){return[{valueType:"group",columns:[{title:"\u89D2\u5EA6\u6620\u5C04\u5B57\u6BB5",dataIndex:["defaultConfig","chart","angleField"],colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"\u989C\u8272\u6620\u5C04\u5B57\u6BB5",dataIndex:["defaultConfig","chart","colorField"],colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"\u997C\u56FE\u534A\u5F84",dataIndex:["defaultConfig","chart","radius"],valueType:"digit",colProps:{span:6},width:"100%",fieldProps:{min:.1,max:1,step:.1,defaultValue:.9}},{title:"\u997C\u56FE\u5185\u534A\u5F84",dataIndex:["defaultConfig","chart","innerRadius"],valueType:"digit",colProps:{span:6},width:"100%",fieldProps:{min:.1,max:1,step:.1,defaultValue:.6}}]}]},It=bt,Dt=function(e){var r,l,a=e!=null&&(r=e.data)!==null&&r!==void 0&&r.chart?e==null||(l=e.data)===null||l===void 0?void 0:l.chart:e;return[{title:"\u56FE\u8868\u7C7B\u578B",dataIndex:["defaultConfig","chart","type"],valueType:"select",fieldProps:{options:[{label:"\u997C\u56FE",value:"pie"},{label:"\u6298\u7EBF\u56FE",value:"line"},{label:"\u67F1\u5F62\u56FE",value:"column"},{label:"\u6761\u5F62\u56FE",value:"bar"},{label:"\u533A\u57DF\u56FE",value:"area"},{label:"\u533A\u57DF\u5730\u56FE",value:"areaMap"},{label:"\u8D8B\u52BF",value:"trend"},{label:"\u8FDB\u5EA6\u6761",value:"progress"},{label:"\u6807\u70B9\u5730\u56FE",value:"mapDots"}]},colProps:{span:12}},{title:"\u9AD8\u5EA6",dataIndex:["defaultConfig","chart","height"],valueType:"digit",width:"100%",colProps:{span:12}},{name:[["defaultConfig","chart","type"]],valueType:"dependency",columns:function(d){var i=d.defaultConfig;if(!i)return[];var T=i.chart,o=T===void 0?{}:T;return(o==null?void 0:o.type)=="pie"?It(a):(o==null?void 0:o.type)=="line"?Tt(a):(o==null?void 0:o.type)=="area"?ft(a):(o==null?void 0:o.type)=="column"?yt(a):(o==null?void 0:o.type)=="bar"?xt(a):(o==null?void 0:o.type)=="areaMap"?gt(a):(o==null?void 0:o.type)=="trend"?ue(["defaultConfig","chart","trend","layout"]):(o==null?void 0:o.type)=="mapDots"?jt(a):[]}}]},se=Dt,St=[{valueType:"group",title:"statistic\u8BBE\u7F6E",columns:[{title:"\u6807\u9898",dataIndex:["defaultConfig","statistic","title"],colProps:{span:6}},{title:"\u63D0\u793A",dataIndex:["defaultConfig","statistic","tip"],colProps:{span:6}},{title:"\u524D\u7F00",dataIndex:["defaultConfig","statistic","prefix"],colProps:{span:6}},{title:"\u540E\u7F00",dataIndex:["defaultConfig","statistic","suffix"],colProps:{span:6}}]},{valueType:"group",columns:[{title:"\u72B6\u6001",dataIndex:["defaultConfig","statistic","status"],valueType:"select",fieldProps:{options:[{label:"success",value:"success"},{label:"processing",value:"processing"},{label:"default",value:"default"},{label:"error",value:"error"},{label:"warning",value:"warning"}]},colProps:{span:12}},{title:"\u94FE\u63A5",dataIndex:["defaultConfig","href"],colProps:{span:12}}]},{valueType:"group",columns:[{title:"\u63CF\u8FF0",dataIndex:["defaultConfig","statistic","description"],valueType:"textarea",colProps:{span:24}}]}],Ft=[{valueType:"group",title:"footer\u8BBE\u7F6E",columns:[{title:"\u7C7B\u578B",dataIndex:["defaultConfig","footer","type"],valueType:"select",tooltip:"\u5982\u679C\u7C7B\u578B\u662F\u8D8B\u52BF\uFF0C\u6240\u9009\u6570\u636E\u5E94\u8BE5\u8FD4\u56DE\u6709trend:[{title,value,trend}]\u683C\u5F0F\u6570\u636E",fieldProps:{options:[{label:"\u6587\u672C",value:"text"},{label:"\u8D8B\u52BF",value:"trend"}],defaultValue:"text"},colProps:{span:12}},{name:[["defaultConfig","footer","type"]],valueType:"dependency",columns:function(e){var r,l=e.defaultConfig;return(l==null||(r=l.footer)===null||r===void 0?void 0:r.type)=="trend"?ue(["defaultConfig","footer","layout"]):[{title:"\u5E95\u90E8\u8BBE\u7F6E",dataIndex:["defaultConfig","footer","text"],valueType:"textarea",colProps:{span:24}}]}}]}],Zt=function(e){return[{valueType:"group",columns:[{title:"statistic",dataIndex:["defaultConfig","open","statistic"],valueType:"switch",colProps:{span:8}},{title:"chart",dataIndex:["defaultConfig","open","chart"],valueType:"switch",colProps:{span:8}},{title:"footer",dataIndex:["defaultConfig","open","footer"],valueType:"switch",colProps:{span:8}}]},{name:["defaultConfig","sourceDataName"],valueType:"dependency",columns:function(l){var a,s,d,i=l.defaultConfig,T=l.sourceDataName;if(!i)return[];var o=[];if((a=i.open)!==null&&a!==void 0&&a.statistic&&(o=[].concat(L()(o),St)),(s=i.open)!==null&&s!==void 0&&s.chart){var y=se(e);o=[].concat(L()(o),[{valueType:"group",title:"chart\u8BBE\u7F6E",columns:L()(y)}])}return(d=i.open)!==null&&d!==void 0&&d.footer&&(o=[].concat(L()(o),Ft)),o}}]},Rt=Zt,Et=n(21352),Mt=function(e){var r=arguments.length>1&&arguments[1]!==void 0?arguments[1]:["defaultConfig","columns"];return[{valueType:"group",columns:[{title:"\u5217\u8868\u5B57\u6BB5",dataIndex:r,colProps:{span:24},rowProps:{gutter:0},valueType:"formList",columns:[{valueType:"group",columns:[{title:"\u8868\u5355\u9879",dataIndex:"dataIndex",colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"Label",dataIndex:"title",colProps:{span:6},fieldProps:{placeholder:"\u4E0D\u8F93\u5165\u7684\u8BDD\u4F7F\u7528\u540E\u53F0\u6570\u636E"}},{title:"\u7EC4\u4EF6\u7C7B\u578B",dataIndex:"valueType",colProps:{span:6},valueType:"select",fieldProps:{options:Et.lG}},{title:"fieldProps",dataIndex:"fieldProps",colProps:{span:6},valueType:"modalJson"}]}]}]}]},de=Mt,$t=function(e){return[{valueType:"group",columns:[{title:"Table Size",dataIndex:["defaultConfig","size"],colProps:{span:12},valueType:"select",fieldProps:{options:["small","middle","large"]}},{title:"\u5206\u9875",tooltip:"\u8BBE\u7F6E\u4E3A0\u7684\u8BDD\u8868\u793A\u5173\u95ED\u5206\u9875",dataIndex:["defaultConfig","cpage"],colProps:{span:12},valueType:"digit",width:"100%"},{title:"\u5217\u8868\u5B57\u6BB5",dataIndex:["defaultConfig","columns"],colProps:{span:24},rowProps:{gutter:0},valueType:"formList",columns:[{valueType:"group",columns:[{title:"dataIndex",dataIndex:"dataIndex",colProps:{span:6},valueType:"select",fieldProps:{options:e==null?void 0:e.fields}},{title:"title",dataIndex:"title",colProps:{span:6}},{title:"width",dataIndex:"width",colProps:{span:6},valueType:"digit"},{title:"\u6392\u5E8F",dataIndex:"sort",valueType:"switch",colProps:{span:6}}]}]}]}]},Nt=$t,Ot={title:"\u914D\u7F6E",valueType:"jsonEditor",dataIndex:"config"},ue=function(e){return[{title:"\u6392\u5217\u65B9\u5F0F",dataIndex:e,valueType:"select",fieldProps:{options:[{label:"\u6A2A\u5411",value:"horizontal"},{label:"\u7EB5\u5411",value:"vertical"}],defaultValue:"horizontal"},colProps:{span:12}}]},ve=function(e){return{title:"\u5F62\u72B6",dataIndex:["defaultConfig","chart","shapeField"],valueType:"select",fieldProps:{options:[{label:"smooth - \u5E73\u6ED1",value:"smooth"},{label:"trail - \u53D8\u7C97",value:"trail"}]},colProps:{span:e}}},zt=function(e){return[{title:"\u57FA\u7840\u4FE1\u606F",formColumns:[{title:"Title",dataIndex:"title",colProps:{span:12}}]}]},At=function(e){return[{title:"\u57FA\u7840\u4FE1\u606F",formColumns:[{valueType:"group",columns:[{title:"\u7C7B\u578B",dataIndex:"type",valueType:"select",fieldProps:{options:[{label:"StatisticCard - \u6307\u6807\u5361",value:"StatisticCard"},{label:"tab",value:"tab"},{label:"\u8868\u683C",value:"table"},{label:"\u5BB9\u5668",value:"rows"},{label:"\u67E5\u8BE2\u8868\u5355",value:"form"},{label:"\u4E2A\u4EBA\u4FE1\u606F",value:"user"}]},colProps:{span:24}}]},{valueType:"group",columns:[{title:"Title",dataIndex:"title",colProps:{span:12}},{title:"\u6570\u636E\u6E90",dataIndex:"sourceDataName",valueType:"select",fieldProps:{options:(0,G.H1)(e)?e:[],showSearch:!0},colProps:{span:12}}]},{name:["type","sourceDataName"],valueType:"dependency",columns:function(l){var a=l.type,s=l.sourceDataName;return a=="chart"?se(e==null?void 0:e.find(function(d){return d.value==s})):a=="table"?Nt(e==null?void 0:e.find(function(d){return d.value==s})):a=="StatisticCard"?Rt(e==null?void 0:e.find(function(d){return d.value==s})):a=="form"?de(e==null?void 0:e.find(function(d){return d.value==s})):a=="tab"?de(e==null?void 0:e.find(function(d){return d.value==s}),["defaultConfig","form","columns"]):[]}}]},{title:"\u914D\u7F6E",formColumns:[{valueType:"group",columns:[{title:"\u5217\u5BBD",dataIndex:"customer_span",valueType:"digit",width:"100%",colProps:{span:12}},{title:"\u81EA\u5B9A\u4E49\u9AD8\u5EA6",dataIndex:"height",valueType:"digit",width:"100%",colProps:{span:12}}]},{valueType:"group",columns:[{title:"\u663E\u793A\u6761\u4EF6",dataIndex:"show_condition",tooltip:"\u53EF\u6839\u636E\u5F53\u524D\u7528\u6237\u7F16\u5199\u662F\u5426\u663E\u793A\u8BE5\u7EC4\u4EF6,\u4F8B\u5982 {{ user.id == 1?true:false }} user\u4E3A\u5F53\u524D\u767B\u5F55\u7528\u6237\u4FE1\u606F",valueType:"textarea",colProps:{span:24}}]},Ot]}]},Vt=At,U=function(e){var r=e.title,l=e.uid,a=e.data,s=e.type,d=s===void 0?"col":s,i=e.extpost,T=(0,j.useContext)(N.x),o=T.tableDesigner,y=o.pageMenu,I=o.reflush,P=o.editUrl,u=o.sourceData,D=(0,j.useContext)(ie.r),F=D.setting,p=(0,j.useContext)(H.YF),g=p.setVisible,x=(0,j.useState)({}),C=z()(x,2),c=C[0],f=C[1];return(0,j.useEffect)(function(){f(a)},[y,a]),(0,t.jsx)("div",{onClick:function(b){b.preventDefault()},children:(0,t.jsx)(ut.default,{trigger:(0,t.jsx)("div",{style:{width:"100%"},onClick:function(b){g==null||g(!1)},children:r}),tabs:d=="col"?Vt(u):zt(u),value:c,postUrl:P,data:m()({id:y==null?void 0:y.id,uid:l},i),callback:function(b){var Z=b.data;return I(Z),!0},saFormProps:{devEnable:!1}})})},O=function(e){var r=e.title,l=e.uid,a=e.devData,s=e.col,d=e.row,i=e.otitle,T=e.style,o=a.itemType,y=o===void 0?"row":o,I=(0,j.useContext)(N.x),P=I.tableDesigner.devEnable,u=function(){var v=arguments.length>0&&arguments[0]!==void 0?arguments[0]:0;return{label:(0,t.jsx)(U,{title:(0,t.jsxs)($.Z,{children:[v==0?(0,t.jsx)(le.Z,{}):(0,t.jsx)(ae.Z,{}),(0,t.jsx)("span",{children:"+ \u5217"})]}),uid:l,extpost:{actionType:v==0?"insertCol":"addCol"}}),key:"addCol"+v}},D=function(){var v=arguments.length>0&&arguments[0]!==void 0?arguments[0]:0;return{label:(0,t.jsx)(U,{title:(0,t.jsxs)($.Z,{children:[v==0?(0,t.jsx)(le.Z,{}):(0,t.jsx)(ae.Z,{}),(0,t.jsx)("span",{children:"+ \u884C"})]}),uid:l,type:"row",extpost:{actionType:v==0?"insertRow":"addRow"}}),key:"addRow"+v}},F={label:(0,t.jsx)(U,{title:(0,t.jsxs)($.Z,{children:[(0,t.jsx)(ne.Z,{}),(0,t.jsx)("span",{children:"\u7F16\u8F91"})]}),uid:l,data:d,type:"row",extpost:{actionType:"editRow"}}),key:"editRow"},p={label:(0,t.jsx)(U,{title:(0,t.jsxs)($.Z,{children:[(0,t.jsx)(ne.Z,{}),(0,t.jsx)("span",{children:"\u7F16\u8F91"})]}),uid:l,data:s,type:"col",extpost:{actionType:"editCol"}}),key:"editCol"},g=function(v){return{label:(0,t.jsx)(re.VV,{title:(0,t.jsxs)($.Z,{children:[(0,t.jsx)(it.Z,{}),(0,t.jsx)("span",{children:"\u5220\u9664"})]}),uid:l,extpost:{actionType:v=="col"?"deleteCol":"deleteRow"}}),key:"deleteItem",danger:!0}},x=y=="row"?[F,u(0),{type:"divider"},D(1),{type:"divider"},g("row")]:[p,D(0),{type:"divider"},u(1),{type:"divider"},g("col")],C=(0,re.i3)(),c=C.styles;return P?(0,t.jsxs)(oe.TR,{className:c.saSortItem,id:l,eid:l,devData:m()({type:"panel"},a),style:T,children:[(0,t.jsx)("div",{className:dt()("general-schema-designer",c.overrideAntdCSS),children:(0,t.jsx)("div",{className:"general-schema-designer-icons",children:(0,t.jsxs)($.Z,{size:3,align:"center",children:[(0,t.jsx)(oe.IW,{children:(0,t.jsx)(ot.Z,{role:"button","aria-label":"drag-handler"})}),(0,t.jsx)(H.cM,{title:(0,t.jsx)(rt.Z,{role:"button",style:{cursor:"pointer"}}),items:x})]})})}),(0,t.jsx)("div",{role:"button",children:r})]}):i?(0,t.jsx)(t.Fragment,{children:i}):null},wt=["columns"],Bt=function(e){var r=e.config,l=e.data,a=e.title,s=e.uid,d=r.columns,i=V()(r,wt),T=(0,B.useModel)("@@initialState"),o=T.initialState,y=(0,j.useContext)(N.x),I=y.tableDesigner.devEnable,P=I?(0,t.jsx)(O,{otitle:null,uid:s,col:e,devData:{itemType:"col"},title:(a||"Table")+" - "+s}):a||!1;return(0,t.jsx)(w.Z,{style:{height:"100%"},title:P,children:(0,t.jsx)(nt.Z,m()({columns:(0,at.SS)({initRequest:!0,columns:d,initialState:o,devEnable:!1}),dataSource:l==null?void 0:l.data,rowKey:"id"},i))})},Lt=Bt,Ut=n(36942),Wt=["url","pageMenu","path"],Ht=function(e){var r=e.span,l=e.title,a=e.uid,s=e.rows,d=e.data,i=e.config,T=e.sourceDataName,o=e.chart,y=e.noTitle,I=y===void 0?!1:y,P=e.height,u=e.type,D=e.getData,F=e.show_condition,p=d==null?void 0:d.find(function(Z){return Z.value==T}),g=(0,j.useContext)(N.x),x=g.tableDesigner.devEnable,C=(0,B.useModel)("@@initialState"),c=C.initialState,f=F&&!x?(0,Me.mF)(F,{user:c==null?void 0:c.currentUser}):!0,v=function(){var R=arguments.length>0&&arguments[0]!==void 0?arguments[0]:"";return I?null:(0,t.jsx)(O,{otitle:R||l,title:(R||l||"\u5143\u7D20")+" - "+a,uid:a,col:e,devData:{itemType:"col"}})},b=x?v(l):l||!1;return f?(0,t.jsx)(_.Z,{span:r,children:u=="tab"?(0,t.jsx)(Gt,m()({},e)):u=="table"?(0,t.jsx)(Lt,m()(m()({},e),{},{data:p})):s||u=="rows"?(0,t.jsxs)(t.Fragment,{children:[v(),(0,t.jsx)(pe,{rows:s,data:d})]}):u=="form"?(0,t.jsx)(ce,m()(m()({},e),{},{idata:p})):u=="user"?(0,t.jsx)(w.Z,{headStyle:x?{width:"100%",display:"block"}:{},title:x?v():!1,children:(0,t.jsx)($e.PagePanelHeader,{flash:D})}):u=="StatisticCard"?(0,t.jsx)(lt,{title:b,data:p==null?void 0:p.data,label:p==null?void 0:p.label,config:i,height:P}):null}):null},ce=function(e){var r,l=e.config,a=e.idata,s=e.title,d=e.uid,i=e.getData,T=e.simple,o=(0,j.useContext)(N.x),y=o.tableDesigner.devEnable,I=function(){var p=arguments.length>0&&arguments[0]!==void 0?arguments[0]:"";return(0,t.jsx)(O,{otitle:p||s,title:p||s||"\u5143\u7D20 - "+d,uid:d,col:e,devData:{itemType:"col"}})},P=l==null||(r=l.columns)===null||r===void 0?void 0:r.map(function(F){var p,g=a==null||(p=a.data)===null||p===void 0?void 0:p.find(function(x){return x.name==F.dataIndex});return g&&(F=m()(m()({},F),g.props)),F}),u=(0,Ne.ab)({initRequest:!0,columns:P,devEnable:!1}),D=(0,t.jsx)(Se.Z,{layoutType:T?"LightFilter":"QueryFilter",columns:u,rowProps:{gutter:[16,16]},colProps:{span:12},grid:!1,initialValues:a==null?void 0:a.initialValues,onFinish:function(p){i==null||i(p)}});return T?D:(0,t.jsx)(w.Z,{bodyStyle:{padding:0},title:y?I():!1,children:D})},Gt=function(e){var r=e.uid,l=e.rows,a=e.data,s=e.getData,d=e.config,i=e.sourceDataName,T=e.height,o=(0,j.useContext)(N.x),y=o.tableDesigner.devEnable,I=a==null?void 0:a.find(function(u){return u.value==i}),P=d.form?(0,t.jsx)(ce,{simple:!0,idata:I,config:d.form,getData:s}):null;return(0,t.jsx)(w.Z,{style:{borderTopRightRadius:0,borderTopLeftRadius:0},title:y?(0,t.jsx)(O,{uid:r,col:e,devData:{itemType:"col"},title:"Tab - "+r,otitle:null}):!1,children:(0,t.jsx)(Fe.Z,{style:{background:"none"},tabBarExtraContent:P?{right:P}:null,destroyInactiveTabPane:!0,items:l==null?void 0:l.map(function(u,D){return{key:D,label:(0,t.jsx)(O,{title:u!=null&&u.title?u==null?void 0:u.title:"tabItem",otitle:u==null?void 0:u.title,uid:u==null?void 0:u.uid,row:u,devData:{itemType:"row"}}),children:(0,t.jsx)(fe,{index:D,row:u,data:a,noTitle:!0},D)}})})})},fe=function(e){var r,l=e.row,a=e.data,s=e.noTitle,d=s===void 0?!1:s,i=e.index,T=e.getData;return(0,t.jsxs)(Ze.Z,{gutter:[16,16],style:{marginTop:-16},children:[d?null:(0,t.jsx)(_.Z,{span:24,children:(0,t.jsx)(O,{span:24,otitle:null,title:l!=null&&l.title?l==null?void 0:l.title:"\u5206\u7EC4 - "+(l==null?void 0:l.uid),uid:l==null?void 0:l.uid,devData:{itemType:"row"},row:l})},"row"),l==null||(r=l.cols)===null||r===void 0?void 0:r.map(function(o,y){return(0,j.createElement)(Ht,m()(m()({},o),{},{key:y,data:a,getData:T}))})]})},Yt=function(e){var r,l,a,s=e.url,d=s===void 0?"":s,i=e.pageMenu,T=e.path,o=V()(e,Wt),y=(0,j.useState)(),I=z()(y,2),P=I[0],u=I[1],D=(0,j.useState)(),F=z()(D,2),p=F[0],g=F[1],x=(0,j.useContext)(ie.r),C=x.messageApi,c=function(){var E=Pe()(k()().mark(function M(){var xe,he,ye,Q=arguments;return k()().wrap(function(A){for(;;)switch(A.prev=A.next){case 0:return xe=Q.length>0&&Q[0]!==void 0?Q[0]:{},C==null||C.loading({content:"\u52A0\u8F7D\u4E2D...",key:q.iU,duration:0}),A.next=4,q.ZP.get(d,{params:m()(m()({},p),xe)});case 4:he=A.sent,ye=he.data,C==null||C.destroy(),u(ye);case 8:case"end":return A.stop()}},M)}));return function(){return E.apply(this,arguments)}}(),f=(0,j.useState)(),v=z()(f,2),b=v[0],Z=v[1],R=(0,B.useModel)("@@initialState"),S=R.initialState,K=(0,j.useState)(!!(!(S!=null&&(r=S.settings)!==null&&r!==void 0&&r.devDisable)&&S!==null&&S!==void 0&&(l=S.settings)!==null&&l!==void 0&&(l=l.adminSetting)!==null&&l!==void 0&&l.dev)),W=z()(K,2),ge=W[0],Kt=W[1];(0,j.useEffect)(function(){var E;Z(i==null||(E=i.data)===null||E===void 0?void 0:E.panel),c()},[]);var me=(0,H.Yx)({pageMenu:i,setColumns:Z,getColumnsRender:function(M){return M},type:"panel",devEnable:ge,sourceData:P});return(0,j.useEffect)(function(){var E,M;Kt(!!(!(S!=null&&(E=S.settings)!==null&&E!==void 0&&E.devDisable)&&S!==null&&S!==void 0&&(M=S.settings)!==null&&M!==void 0&&(M=M.adminSetting)!==null&&M!==void 0&&M.dev))},[S==null||(a=S.settings)===null||a===void 0?void 0:a.devDisable]),(0,t.jsx)(Ut.PageContainer404,{title:!1,path:T,children:(0,t.jsxs)(N.x.Provider,{value:{tableDesigner:me},children:[(0,t.jsx)(Oe.L,{children:P&&(0,t.jsx)(pe,m()(m()({},o),{},{rows:b,data:P,getData:c}))},"content"),!P&&(0,t.jsx)(Re.Z,{paragraph:{rows:10},active:!0}),ge?(0,t.jsxs)($.Z,{style:{marginTop:10},children:[(0,t.jsx)(ee.ZP,{type:"dashed",onClick:function(){me.edit({base:{id:i==null?void 0:i.id,actionType:"insertRow"}})},children:"+ Row"},"addrow"),(0,t.jsx)(ze.SE,{trigger:(0,t.jsx)(ee.ZP,{type:"dashed",danger:!0,children:(0,t.jsx)(De.Z,{})}),pageMenu:i},"devsetting")]}):null]})})},pe=function(e){var r=e.rows,l=e.data,a=e.getData;return(0,t.jsx)(Ee.Z,{vertical:!0,gap:"middle",children:r==null?void 0:r.map(function(s,d){return(0,t.jsx)(fe,{index:d,row:s,data:l,getData:a},d)})})},Jt=Yt}}]);