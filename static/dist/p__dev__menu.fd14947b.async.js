"use strict";(self.webpackChunkant_design_pro=self.webpackChunkant_design_pro||[]).push([[833],{33579:function(Fe,ae,a){a.r(ae),a.d(ae,{default:function(){return Re}});var oe=a(57288),de=a(32250),se=a(85175),ue=a(71965),$=a(42075),d=a(67294),ce=a(9783),fe=a.n(ce),ve=a(97857),Q=a.n(ve),me=a(19632),W=a.n(me),he=a(5574),I=a.n(he),_=a(72644),pe=a(82061),ge=a(51042),Ce=a(63783),ee=a(83062),ie=a(66309),re=a(96365),xe=a(9361),H=a(60887),q=a(45587),t=a(85893),ye=function(e){var i=e.model,b=e.setOpen,l=e.actionRef,c=e.contentRender,r=(0,d.useRef)();return(0,t.jsx)(_.T,{msgcls:function(f){var k=f.code;if(!k){var g;console.log("loading dispear here"),b(!1),(g=l.current)===null||g===void 0||g.reload();return}},formColumns:["id",{dataIndex:"other_config",valueType:"jsonEditor",title:"\u914D\u7F6E\u4FE1\u606F",fieldProps:{height:700}}],formRef:r,paramExtra:{id:i==null?void 0:i.id},url:"dev/menu/show",postUrl:"dev/menu/otherConfig",showTabs:!1,submitter:"dom",formProps:{contentRender:c,submitter:{searchConfig:{resetText:"\u53D6\u6D88"},resetButtonProps:{onClick:function(){b(!1)}}}},align:"left",dataId:i.id,pageType:"drawer"})},be=function(C){var e=C.model,i=C.setOpen,b=C.actionRef,l=C.contentRender,c=(0,d.useState)(),r=I()(c,2),n=r[0],f=r[1],k=(0,d.useState)([]),g=I()(k,2),A=g[0],w=g[1],D=(0,d.useState)([]),m=I()(D,2),x=m[0],T=m[1],z=[{label:"\u7F16\u8F91\u8868\u683C - saFormTable",value:"saFormTable"},{label:"\u81EA\u5B9A\u4E49\u663E\u793A - customerColumn",value:"customerColumn"},{label:"\u5C5E\u6027\u914D\u7F6E - jsonForm",value:"jsonForm"},{label:"\u591A\u884C\u7F16\u8F91 - formList",value:"formList"},{label:"select",value:"select"},{label:"json\u7F16\u8F91\u5668 - jsonEditor",value:"jsonEditor"},{label:"jsonCode",value:"jsonCode"},{label:"\u5934\u50CF\u663E\u793A - avatar",value:"avatar"},{label:"\u5F39\u5C42\u9009\u62E9\u5668 - modalSelect",value:"modalSelect"},{label:"\u5BCC\u6587\u672C - tinyEditor",value:"tinyEditor"},{label:"\u89C4\u683C\u7F16\u8F91 - guigePanel",value:"guigePanel"},{label:"\u6743\u9650\u914D\u7F6E - permGroup",value:"permGroup"},{label:"\u81EA\u5B9A\u4E49 - cdependency",value:"cdependency"},{label:"\u5FAE\u4FE1\u81EA\u5B9A\u4E49\u83DC\u5355 - wxMenu",value:"wxMenu"},{label:"\u7A7F\u68AD\u6846 - saTransfer",value:"saTransfer"},{label:"html\u663E\u793A",value:"html"},{label:"\u5730\u56FE\u9009\u70B9 - tmapInput",value:"tmapInput"},{label:"\u5730\u56FE\u70B9\u663E\u793A - tmapShow",value:"tmapShow"},{label:"\u62FE\u8272\u5668 - colorPicker",value:"colorPicker"}],L=[{dataIndex:"columns",valueType:"formList",columns:[{valueType:"group",columns:[{title:"\u5B57\u6BB5\u9009\u62E9",valueType:"cascader",dataIndex:"key",width:240,fieldProps:{options:n,showSearch:!0,changeOnSelect:!0}},{valueType:"dependency",name:["props"],columns:function(o){var v=o.props;return[{dataIndex:"",title:"\u81EA\u5B9A\u4E49Title",readonly:!0,render:function(){return(0,t.jsx)("div",{style:{width:100},children:v!=null&&v.title?v.title:" - "})}}]}},{dataIndex:"props",title:"\u66F4\u591A\u914D\u7F6E",valueType:"customerColumnDev",fieldProps:{relationModel:A,allMenus:x,modelColumns:n},width:160},{dataIndex:"type",title:"\u8868\u5355\u7C7B\u578B",width:"sm",valueType:"select",fieldProps:{options:z,placeholder:"\u8BF7\u9009\u62E9\u8868\u5355\u989D\u5916\u7C7B\u578B"}},{dataIndex:"readonly",title:"\u662F\u5426\u53EA\u8BFB",valueType:"switch",fieldProps:{checkedChildren:"readonly",unCheckedChildren:"readonly",defaultChecked:!1}},{dataIndex:"required",title:"\u662F\u5426\u5FC5\u586B",valueType:"switch",fieldProps:{checkedChildren:"required",unCheckedChildren:"required",defaultChecked:!1}},{dataIndex:"hidden",title:"\u662F\u5426\u9690\u85CF",valueType:"switch",fieldProps:{checkedChildren:"hidden",unCheckedChildren:"hidden",defaultChecked:!1}},{dataIndex:"disabled",title:"\u662F\u5426\u7981\u7528",valueType:"switch",fieldProps:{checkedChildren:"disabled",unCheckedChildren:"disabled",defaultChecked:!1}}]}]}],N=(0,d.useState)([]),G=I()(N,2),K=G[0],U=G[1],V=(0,d.useState)([]),M=I()(V,2),F=M[0],B=M[1],s=(0,d.useState)("0"),u=I()(s,2),S=u[0],O=u[1],j=(0,d.useRef)();(0,d.useEffect)(function(){Z(F)},[F]);var Z=function(o){var v=o==null?void 0:o.map(function(h,y){if(h.hidden)return null;var R={dataIndex:"form_config".concat(y==0?"":y),valueType:"saFormList",columns:[].concat(L)};return{title:h.title,formColumns:y==0?["id",R]:[R]}}).filter(function(h){return h});U(v),B(o)};return(0,t.jsxs)(t.Fragment,{children:[(0,t.jsxs)($.Z,{children:[(0,t.jsx)(Ie,{tags:F,onChange:function(o){Z(o)},formRef:j}),(0,t.jsx)(ee.Z,{title:"\u6E05\u7A7A\u5F53\u524Dtab\u7684\u503C",children:(0,t.jsx)(pe.Z,{onClick:function(){var o,v="form_config"+(S=="0"?"":S);(o=j.current)===null||o===void 0||o.setFieldValue(v,[])}})})]}),(0,t.jsx)("br",{}),(0,t.jsx)(_.T,{onTabChange:function(o){O(o)},beforeGet:function(o){var v,h=(v=o.admin_model)===null||v===void 0?void 0:v.columns;if(o.form_config||(o.form_config=h.map(function(p){return{columns:[{key:p.name}]}})),T(o.menus),o.admin_model){var y,R=h.map(function(p){return{label:[p.title,p.name].join(" - "),value:p.name}}),X=(y=o.admin_model.relations)===null||y===void 0?void 0:y.filter(function(p){return p.type=="one"||p.type=="many"}).map(function(p){var le=JSON.parse(p.foreign_model.columns),Y=le.map(function(E){return{label:[E.title,E.name].join(" - "),value:E.name}});return{label:[p.title,p.name,p.type=="one"?"hasOne":"hasMany"].join(" - "),value:p.name,children:Y}});w(X);var te=[].concat(W()(R),W()(X));f(te),B(o.tabs)}},msgcls:function(o){var v=o.code;if(!v){var h;console.log("loading dispear here"),i(!1),(h=b.current)===null||h===void 0||h.reload();return}},tabs:K,formRef:j,paramExtra:{id:e==null?void 0:e.id},postExtra:{id:e==null?void 0:e.id,tags:F},url:"dev/menu/show",postUrl:"dev/menu/formConfig",showTabs:!0,submitter:"dom",formProps:{contentRender:l,submitter:{searchConfig:{resetText:"\u53D6\u6D88"},resetButtonProps:{onClick:function(){i(!1)}}}},align:"left",dataId:e.id,pageType:"drawer"})]})},Te=function(e){var i,b=e.tag,l=b===void 0?{title:"\u57FA\u7840\u4FE1\u606F"}:b,c=e.index,r=e.handleClose,n=e.setTag,f=(0,q.nB)({id:l.title}),k=f.listeners,g=f.setNodeRef,A=f.setActivatorNodeRef,w=f.transform,D=f.transition,m=f.isDragging,x=(0,d.useState)(!1),T=I()(x,2),z=T[0],L=T[1],N={cursor:"move",transition:"unset",display:"inline-block"},G=w?Q()(Q()({},N),{},{transform:"translate3d(".concat(w.x,"px, ").concat(w.y,"px, 0)"),transition:m?"unset":D}):N,K=(0,d.useState)(""),U=I()(K,2),V=U[0],M=U[1],F=(l==null||(i=l.title)===null||i===void 0?void 0:i.length)>20,B=(0,t.jsx)(t.Fragment,{children:(0,t.jsx)(ie.Z,{closable:c!==0,style:{userSelect:"none"},onClose:function(u){r(l),u.preventDefault()},children:(0,t.jsx)("span",{onDoubleClick:function(u){c!==-1&&(M(l.title),L(!0),u.preventDefault())},children:F?"".concat(l.title.slice(0,20),"..."):l.title})},l==null?void 0:l.title)});return(0,t.jsx)("div",Q()(Q()({style:G,ref:g},k),{},{children:l.hidden?null:z?(0,t.jsx)(re.Z,{size:"small",style:{width:"auto",verticalAlign:"top"},value:V,onChange:function(u){M(u.target.value)},onBlur:function(){L(!1),n(V,c)},onPressEnter:function(){L(!1),n(V,c)}},l.title):F?(0,t.jsx)(ee.Z,{title:l.title,children:B},l.title):B}))},Ie=function(e){var i=e.formRef,b=xe.Z.useToken(),l=b.token,c=(0,d.useState)([]),r=I()(c,2),n=r[0],f=r[1];(0,d.useEffect)(function(){e.tags&&f(e.tags)},[e.tags]);var k=(0,d.useState)(!1),g=I()(k,2),A=g[0],w=g[1],D=(0,d.useState)(""),m=I()(D,2),x=m[0],T=m[1],z=(0,d.useRef)(null),L=(0,d.useRef)(null);(0,d.useEffect)(function(){if(A){var s;(s=z.current)===null||s===void 0||s.focus()}},[A]),(0,d.useEffect)(function(){var s;(s=L.current)===null||s===void 0||s.focus()},[x]);var N=(0,H.Dy)((0,H.VT)(H.we,{activationConstraint:{distance:10}})),G=function(u){var S=u.active,O=u.over;if(O&&S.id!==O.id){var j,Z=function(P,o,v,h){var y=P.findIndex(function(ne){return ne.title===S.id}),R=P.findIndex(function(ne){return ne.title===O.id}),X=i==null||(o=i.current)===null||o===void 0?void 0:o.getFieldsValue(!0),te="form_config"+(y||""),p="form_config"+(R||""),le=i==null||(v=i.current)===null||v===void 0?void 0:v.getFieldValue(te),Y=fe()({},p,le);if(y>R)for(var E=R+1;E<=y;E++){var we="form_config"+(E||""),Ee="form_config"+(E-1?E-1:"");Y[we]=X[Ee]}else for(var J=y;J<R;J++){var ke="form_config"+(J||""),De="form_config"+(J+1?J+1:"");Y[ke]=X[De]}return i==null||(h=i.current)===null||h===void 0||h.setFieldsValue(Q()({},Y)),(0,q.Rp)(P,y,R)}(n);f(Z),(j=e.onChange)===null||j===void 0||j.call(e,Z)}},K=function(u){var S,O=n==null?void 0:n.findIndex(function(j){return j.title==u.title});n[O].hidden=!0,f(n),(S=e.onChange)===null||S===void 0||S.call(e,n)},U=function(){w(!0)},V=function(u){T(u.target.value)},M=function(){if(x&&n.findIndex(function(S){return S.title==x})===-1){var u;f([].concat(W()(n),[{title:x}])),(u=e.onChange)===null||u===void 0||u.call(e,[].concat(W()(n),[{title:x}]))}w(!1),T("")},F={width:78,verticalAlign:"top"},B={background:l.colorBgContainer,borderStyle:"dashed"};return(0,t.jsxs)($.Z,{size:[0,8],wrap:!0,children:[(0,t.jsx)($.Z,{size:[0,8],wrap:!0,children:(0,t.jsx)(H.LB,{sensors:N,onDragEnd:G,collisionDetection:H.pE,children:(0,t.jsx)(q.Fo,{items:n.map(function(s){return s.title}),strategy:q.PG,children:n==null?void 0:n.map(function(s,u){return(0,t.jsx)(Te,{tag:s,index:u,handleClose:K,setTag:function(O,j){var Z;n[j].title=O,f(n),(Z=e.onChange)===null||Z===void 0||Z.call(e,W()(n))}},s.title)})})})}),A?(0,t.jsx)(re.Z,{ref:z,type:"text",size:"small",style:F,value:x,onChange:V,onBlur:M,onPressEnter:M}):(0,t.jsxs)(ie.Z,{style:B,onClick:U,children:[(0,t.jsx)(ge.Z,{})," new Tab"]}),(0,t.jsx)(ee.Z,{title:"\u5220\u9664\u6216\u62D6\u62FD\u6392\u5E8Ftab \u8868\u5355\u6E32\u67D3\u53EF\u80FD\u9700\u8981\u4E00\u70B9\u65F6\u95F4",children:(0,t.jsx)(Ce.Z,{})})]})},Se=a(70145),je=a(21352),Pe=function(C){var e=C.model,i=C.setOpen,b=C.actionRef,l=C.contentRender,c=(0,d.useState)(),r=I()(c,2),n=r[0],f=r[1],k=(0,d.useContext)(Se.r),g=k.setting;(0,d.useEffect)(function(){f((0,je.dm)({model_id:e.admin_model_id,dev:g==null?void 0:g.dev}))},[]);var A=function(m){if(m.admin_model){var x=m.admin_model.columns;m.table_config||(m.table_config=x.map(function(T){return{key:T.name}}),m.table_config.push({key:"option"}))}},w=(0,d.useRef)();return(0,t.jsx)(_.T,{beforeGet:function(m){A(m)},msgcls:function(m){var x=m.code;if(!x){var T;console.log("loading dispear here"),i(!1),(T=b.current)===null||T===void 0||T.reload();return}},formColumns:["id",{dataIndex:"table_config",valueType:"saFormList",fieldProps:{showtype:"table"},columns:n}],formRef:w,paramExtra:{id:e==null?void 0:e.id},url:"dev/menu/show",postUrl:"dev/menu/tableConfig",showTabs:!1,formProps:{contentRender:l,submitter:{searchConfig:{resetText:"\u53D6\u6D88"},resetButtonProps:{onClick:function(){i(!1)}}}},align:"left",dataId:e.id,pageType:"drawer"})},Re=function(){var C=function(l){var c=[];for(var r in l)c.push({label:l[r],value:r});return c},e=(0,d.useRef)(),i=function(l){return[{title:"\u83DC\u5355\u540D\u79F0",dataIndex:"title2",key:"title2",render:function(r,n){return(0,t.jsxs)($.Z,{children:[oe.q0[n.icon],n.title]})}},{title:"path",dataIndex:"path",key:"path"},"displayorder",{title:"\u914D\u7F6E",dataIndex:"type",valueType:"customerColumn",search:!1,readonly:!0,width:300,fieldProps:{items:[{domtype:"button",modal:{title:'{{record.title + " - \u5217\u8868\u914D\u7F6E"}}',drawerProps:{width:1600},childrenRender:function(r){return(0,t.jsx)(Pe,{model:r,actionRef:e})}},action:"drawer",btn:{text:"\u5217\u8868",size:"small"}},{domtype:"button",modal:{title:'{{record.title + " - \u8868\u5355\u914D\u7F6E"}}',drawerProps:{width:1600},childrenRender:function(r){return(0,t.jsx)(be,{model:{id:r==null?void 0:r.id},actionRef:e})}},action:"drawer",btn:{text:"\u8868\u5355",size:"small"}},{domtype:"button",modal:{title:'{{record.title + " - \u5176\u5B83\u914D\u7F6E"}}',drawerProps:{width:1600},childrenRender:function(r){return(0,t.jsx)(ye,{model:{id:r==null?void 0:r.id},actionRef:e})}},action:"drawer",btn:{text:"\u5176\u5B83",size:"small"}},{domtype:"button",modal:{msg:"\u8BF7\u9009\u62E9\u590D\u5236\u5230",formColumns:[{dataIndex:"toid",width:"md",title:"\u590D\u5236\u5230",valueType:"treeSelect",fieldProps:{options:l==null?void 0:l.menus,treeLine:{showLeafIcon:!0},treeDefaultExpandAll:!0}}]},request:{url:"dev/menu/copyTo"},action:"confirmForm",btn:{text:"",size:"small",icon:(0,t.jsx)(se.Z,{}),tooltip:"\u590D\u5236"}},{domtype:"button",modal:{msg:"\u8BF7\u9009\u62E9\u79FB\u52A8\u5230",formColumns:[{dataIndex:"toid",width:"md",title:"\u79FB\u52A8\u5230",valueType:"treeSelect",fieldProps:{options:l==null?void 0:l.menus,treeLine:{showLeafIcon:!0},treeDefaultExpandAll:!0}}]},request:{postUrl:"dev/menu/moveTo"},action:"confirmForm",btn:{text:"",size:"small",icon:(0,t.jsx)(ue.Z,{}),tooltip:"\u79FB\u52A8\u81F3"}}]}},{dataIndex:"state",title:"\u72B6\u6001",valueType:"customerColumn",search:!1,readonly:!0,fieldProps:{items:[{domtype:"text",action:"dropdown",request:{url:"{{url}}",modelName:"state",fieldNames:"value,label",data:{actype:"state"}}}]}},{dataIndex:"status",title:"\u663E\u793A",valueType:"select",search:!1,valueEnum:[{text:"\u9690\u85CF",status:"error"},{text:"\u663E\u793A",status:"success"}]},"option"]};return(0,t.jsx)(de.Z,{name:"\u83DC\u5355",title:!1,actionRef:e,table_menu_key:"state",table_menu_all:!1,tableColumns:i,formColumns:[{valueType:"group",columns:[{title:"\u83DC\u5355\u540D\u79F0",dataIndex:"title",width:"md",fieldProps:{placeholder:"\u4E3A\u7A7A\u65F6\u83DC\u5355\u4F1A\u9690\u85CF"}},{title:"path",dataIndex:"path",width:"sm",fieldProps:{placeholder:"\u8BF7\u8F93\u5165\u8DEF\u5F84"}},{title:"\u56FE\u6807",dataIndex:"icon",valueType:"select",width:"sm",tooltip:"\u9700\u8981\u5148\u5C06\u8981\u4F7F\u7528\u5230\u7684\u56FE\u6807\u914D\u7F6E\u5230iconmap\u4E2D\u624D\u80FD\u9009\u62E9",fieldProps:{placeholder:"\u8BF7\u9009\u62E9\u56FE\u6807",options:C(oe.q0)}},{title:"\u83DC\u5355\u7C7B\u578B",dataIndex:"type",valueType:"select",requestDataName:"types",width:"sm"},{title:"\u65B0\u589E\u6309\u94AE",dataIndex:"addable",valueType:"switch",tooltip:"\u5F00\u542F\u540E\u5217\u8868\u4E2D\u65E0\u65B0\u5EFA\u6309\u94AE",fieldProps:{checkedChildren:"\u663E\u793A",unCheckedChildren:"\u9690\u85CF",defaultChecked:!0}},{title:"form\u662F\u5426\u53EF\u7F16\u8F91",dataIndex:"editable",valueType:"switch",tooltip:"\u5F00\u542F\u540E\u8868\u5355\u65E0\u63D0\u4EA4\u6309\u94AE",fieldProps:{checkedChildren:"\u53EF\u7F16\u8F91",unCheckedChildren:"\u53EA\u8BFB",defaultChecked:!0}},{title:"\u662F\u5426\u53EF\u5220\u9664",dataIndex:"deleteable",valueType:"switch",tooltip:"\u6570\u636E\u662F\u5426\u53EF\u4EE5\u5220\u9664",fieldProps:{checkedChildren:"\u53EF\u5220\u9664",unCheckedChildren:"\u4E0D\u53EF\u5220",defaultChecked:!0}}]},{valueType:"group",columns:[{dataIndex:"admin_model_id",title:"\u5173\u8054\u6A21\u578B",valueType:"treeSelect",requestDataName:"admin_model_ids",fieldProps:{treeLine:{showLeafIcon:!0},treeDefaultExpandAll:!0,allowClear:!0},width:"md"},{title:"\u9875\u9762\u7C7B\u578B",dataIndex:"page_type",valueType:"radioButton",fieldProps:{options:[{label:"\u5217\u8868",value:"table"},{label:"\u5206\u7C7B",value:"category"},{label:"\u8868\u5355",value:"form"},{label:"\u9762\u677F",value:"panel"}]}},{title:"form\u6253\u5F00\u65B9\u5F0F",dataIndex:"open_type",valueType:"radioButton",fieldProps:{options:[{label:"page",value:"page"},{label:"drawer",value:"drawer"},{label:"modal",value:"modal"}]}},"displayorder","state",{title:"\u663E\u793A",dataIndex:"status",valueType:"switch",tooltip:"\u9690\u85CF\u540E\u83DC\u5355\u4E0D\u663E\u793A\uFF0C\u4F46\u8FD8\u662F\u53EF\u4EE5\u8BBF\u95EE",fieldProps:{checkedChildren:"\u663E\u793A",unCheckedChildren:"\u9690\u85CF",defaultChecked:!0}},{title:"\u8BBE\u7F6E",dataIndex:"setting",valueType:"confirmForm",fieldProps:{btn:{title:"\u8BBE\u7F6E",size:"middle"},formColumns:[{dataIndex:"steps_form",title:"\u5206\u6B65\u8868\u5355",valueType:"switch"}]}}]},{title:"\u5C5E\u6027\u8BBE\u7F6E",dataIndex:"desc",valueType:"jsonEditor",fieldProps:{height:600}},{title:"\u5B50\u6743\u9650",dataIndex:"perms",valueType:"jsonEditor"},"parent_id"],expandAll:!1,level:4,openWidth:1600,tableProps:{scroll:{y:600}},url:"dev/menu"})}}}]);