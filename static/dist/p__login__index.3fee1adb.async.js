"use strict";(self.webpackChunkant_design_pro=self.webpackChunkant_design_pro||[]).push([[939],{92594:function(jn,ve,n){n.r(ve),n.d(ve,{default:function(){return fn}});var Re=n(15009),H=n.n(Re),be=n(99289),q=n.n(be),De=n(97857),G=n.n(De),Le=n(5574),A=n.n(Le),Ee=n(56034),he=n(45360),ge=n(42075),fe=n(55102),me=n(14726),Ne=n(75081),r=n(67294),_=n(33067),e=n(85893),ze=function(i){var u=i.value,j=u===void 0?{}:u,m=i.onChange,C=i.reload,P=C===void 0?0:C,R=i.placeholder,b=R===void 0?"\u8BF7\u8F93\u5165\u9A8C\u8BC1\u7801":R,D=(0,r.useState)(""),B=A()(D,2),p=B[0],K=B[1],Q=(0,r.useState)(""),L=A()(Q,2),k=L[0],W=L[1],w=(0,r.useState)(""),y=A()(w,2),x=y[0],E=y[1],V=(0,r.useState)(!1),N=A()(V,2),O=N[0],z=N[1],v=function(){var U=q()(H()().mark(function t(){var F;return H()().wrap(function(S){for(;;)switch(S.prev=S.next){case 0:return S.prev=0,S.next=3,(0,_.xx)({key:k});case 3:if(F=S.sent,F.code!==0){S.next=6;break}return S.abrupt("return",F.data);case 6:S.next=12;break;case 8:return S.prev=8,S.t0=S.catch(0),he.ZP.error("\u83B7\u53D6\u90E8\u95E8\u6811\u5931\u8D25,\u8BF7\u91CD\u8BD5"),S.abrupt("return",[]);case 12:return he.ZP.error("\u83B7\u53D6\u90E8\u95E8\u6811\u5931\u8D25,\u8BF7\u91CD\u8BD5"),S.abrupt("return",[]);case 14:case"end":return S.stop()}},t,null,[[0,8]])}));return function(){return U.apply(this,arguments)}}(),h=function(t){m&&m(G()(G()({captchaCode:p,captchaKey:k},j),t))},g=function(t){z(!0),v().then(function(F){z(!1),W(F.key),E(F.img),t&&h({captchaKey:F.key})})};(0,r.useEffect)(function(){g(!1)},[]),(0,r.useEffect)(function(){P>0&&g(!0)},[P]);var c=function(t){var F=t.target.value||"";F&&K(F),h({captchaCode:F})},Z=function(){g(!0)};return(0,e.jsxs)(ge.Z,{children:[(0,e.jsx)(fe.Z,{prefix:(0,e.jsx)(Ee.Z,{}),size:"large",placeholder:b,onChange:c}),(0,e.jsx)(me.ZP,{size:"large",style:{overflow:"hidden",padding:0,width:112},children:(0,e.jsx)(Ne.Z,{spinning:O,size:"small",children:(0,e.jsx)("img",{src:x,onClick:Z})})})]})},oe=ze,$e=n(99702),Ge=n(62871),ke=n(64182),ee=n(19179),We=n(74700),pe=n(87547),xe=n(94149),we=n(71356),Me=n(84517),le=n(5966),ne=n(34994),He=n(97462),s=n(1413),te=n(74165),Se=n(15861),se=n(45987),ue=n(97685),Ke=n(8232),ye=n(90789),Oe=["rules","name","phoneName","fieldProps","onTiming","captchaTextRender","captchaProps"],Ue=r.forwardRef(function(a,i){var u=Ke.Z.useFormInstance(),j=(0,r.useState)(a.countDown||60),m=(0,ue.Z)(j,2),C=m[0],P=m[1],R=(0,r.useState)(!1),b=(0,ue.Z)(R,2),D=b[0],B=b[1],p=(0,r.useState)(),K=(0,ue.Z)(p,2),Q=K[0],L=K[1],k=a.rules,W=a.name,w=a.phoneName,y=a.fieldProps,x=a.onTiming,E=a.captchaTextRender,V=E===void 0?function(v,h){return v?"".concat(h," \u79D2\u540E\u91CD\u65B0\u83B7\u53D6"):"\u83B7\u53D6\u9A8C\u8BC1\u7801"}:E,N=a.captchaProps,O=(0,se.Z)(a,Oe),z=function(){var v=(0,Se.Z)((0,te.Z)().mark(function h(g){return(0,te.Z)().wrap(function(Z){for(;;)switch(Z.prev=Z.next){case 0:return Z.prev=0,L(!0),Z.next=4,O.onGetCaptcha(g);case 4:L(!1),B(!0),Z.next=13;break;case 8:Z.prev=8,Z.t0=Z.catch(0),B(!1),L(!1),console.log(Z.t0);case 13:case"end":return Z.stop()}},h,null,[[0,8]])}));return function(g){return v.apply(this,arguments)}}();return(0,r.useImperativeHandle)(i,function(){return{startTiming:function(){return B(!0)},endTiming:function(){return B(!1)}}}),(0,r.useEffect)(function(){var v=0,h=a.countDown;return D&&(v=window.setInterval(function(){P(function(g){return g<=1?(B(!1),clearInterval(v),h||60):g-1})},1e3)),function(){return clearInterval(v)}},[D]),(0,r.useEffect)(function(){x&&x(C)},[C,x]),(0,e.jsxs)("div",{style:(0,s.Z)((0,s.Z)({},y==null?void 0:y.style),{},{display:"flex",alignItems:"center"}),ref:i,children:[(0,e.jsx)(fe.Z,(0,s.Z)((0,s.Z)({},y),{},{style:(0,s.Z)({flex:1,transition:"width .3s",marginRight:8},y==null?void 0:y.style)})),(0,e.jsx)(me.ZP,(0,s.Z)((0,s.Z)({style:{display:"block"},disabled:D,loading:Q},N),{},{onClick:(0,Se.Z)((0,te.Z)().mark(function v(){var h;return(0,te.Z)().wrap(function(c){for(;;)switch(c.prev=c.next){case 0:if(c.prev=0,!w){c.next=9;break}return c.next=4,u.validateFields([w].flat(1));case 4:return h=u.getFieldValue([w].flat(1)),c.next=7,z(h);case 7:c.next=11;break;case 9:return c.next=11,z("");case 11:c.next=16;break;case 13:c.prev=13,c.t0=c.catch(0),console.log(c.t0);case 16:case"end":return c.stop()}},v,null,[[0,13]])})),children:V(D,C)}))]})}),Qe=(0,ye.G)(Ue),Ve=Qe,Ce=n(98082),Xe=n(10915),Ye=n(28459),Je=n(93967),qe=n.n(Je),ce=n(4942),_e=function(i){return(0,ce.Z)((0,ce.Z)({},i.componentCls,{"&-container":{display:"flex",flex:"1",flexDirection:"column",height:"100%",paddingInline:32,paddingBlock:24,overflow:"auto",background:"inherit"},"&-top":{textAlign:"center"},"&-header":{display:"flex",alignItems:"center",justifyContent:"center",height:"44px",lineHeight:"44px",a:{textDecoration:"none"}},"&-title":{position:"relative",insetBlockStart:"2px",color:"@heading-color",fontWeight:"600",fontSize:"33px"},"&-logo":{width:"44px",height:"44px",marginInlineEnd:"16px",verticalAlign:"top",img:{width:"100%"}},"&-desc":{marginBlockStart:"12px",marginBlockEnd:"40px",color:i.colorTextSecondary,fontSize:i.fontSize},"&-main":{minWidth:"328px",maxWidth:"580px",margin:"0 auto","&-other":{marginBlockStart:"24px",lineHeight:"22px",textAlign:"start"}}}),"@media (min-width: @screen-md-min)",(0,ce.Z)({},"".concat(i.componentCls,"-container"),{paddingInline:0,paddingBlockStart:32,paddingBlockEnd:24,backgroundRepeat:"no-repeat",backgroundPosition:"center 110px",backgroundSize:"100%"}))};function en(a){return(0,Ce.Xj)("LoginForm",function(i){var u=(0,s.Z)((0,s.Z)({},i),{},{componentCls:".".concat(a)});return[_e(u)]})}var nn=["logo","message","contentStyle","title","subTitle","actions","children","containerStyle","otherStyle"];function tn(a){var i,u=a.logo,j=a.message,m=a.contentStyle,C=a.title,P=a.subTitle,R=a.actions,b=a.children,D=a.containerStyle,B=a.otherStyle,p=(0,se.Z)(a,nn),K=(0,Xe.YB)(),Q=p.submitter===!1?!1:(0,s.Z)((0,s.Z)({searchConfig:{submitText:K.getMessage("loginForm.submitText","\u767B\u5F55")}},p.submitter),{},{submitButtonProps:(0,s.Z)({size:"large",style:{width:"100%"}},(i=p.submitter)===null||i===void 0?void 0:i.submitButtonProps),render:function(N,O){var z,v=O.pop();if(typeof(p==null||(z=p.submitter)===null||z===void 0?void 0:z.render)=="function"){var h,g;return p==null||(h=p.submitter)===null||h===void 0||(g=h.render)===null||g===void 0?void 0:g.call(h,N,O)}return v}}),L=(0,r.useContext)(Ye.ZP.ConfigContext),k=L.getPrefixCls("pro-form-login"),W=en(k),w=W.wrapSSR,y=W.hashId,x=function(N){return"".concat(k,"-").concat(N," ").concat(y)},E=(0,r.useMemo)(function(){return u?typeof u=="string"?(0,e.jsx)("img",{src:u}):u:null},[u]);return w((0,e.jsxs)("div",{className:qe()(x("container"),y),style:D,children:[(0,e.jsxs)("div",{className:"".concat(x("top")," ").concat(y).trim(),children:[C||E?(0,e.jsxs)("div",{className:"".concat(x("header")),children:[E?(0,e.jsx)("span",{className:x("logo"),children:E}):null,C?(0,e.jsx)("span",{className:x("title"),children:C}):null]}):null,P?(0,e.jsx)("div",{className:x("desc"),children:P}):null]}),(0,e.jsxs)("div",{className:x("main"),style:(0,s.Z)({width:328},m),children:[(0,e.jsxs)(ne.A,(0,s.Z)((0,s.Z)({isKeyPressSubmit:!0},p),{},{submitter:Q,children:[j,b]})),R?(0,e.jsx)("div",{className:x("main-other"),style:B,children:R}):null]})]}))}var Ze=n(22270),an=n(84567),je=n(33925),rn=["options","fieldProps","proFieldProps","valueEnum"],on=r.forwardRef(function(a,i){var u=a.options,j=a.fieldProps,m=a.proFieldProps,C=a.valueEnum,P=(0,se.Z)(a,rn);return(0,e.jsx)(je.Z,(0,s.Z)({ref:i,valueType:"checkbox",valueEnum:(0,Ze.h)(C,void 0),fieldProps:(0,s.Z)({options:u},j),lightProps:(0,s.Z)({labelFormatter:function(){return(0,e.jsx)(je.Z,(0,s.Z)({ref:i,valueType:"checkbox",mode:"read",valueEnum:(0,Ze.h)(C,void 0),filedConfig:{customLightMode:!0},fieldProps:(0,s.Z)({options:u},j),proFieldProps:m},P))}},P.lightProps),proFieldProps:m},P))}),ln=r.forwardRef(function(a,i){var u=a.fieldProps,j=a.children;return(0,e.jsx)(an.Z,(0,s.Z)((0,s.Z)({ref:i},u),{},{children:j}))}),sn=(0,ye.G)(ln,{valuePropName:"checked"}),Pe=sn;Pe.Group=on;var un=Pe,ae=n(7837),cn=n(9361),dn=n(10397),vn=n(48096),Fe=n(73935),X={container:"container___ldtvs",lang:"lang___qrAB8",content:"content___wB3e_","ant-pro-form-login-container":"ant-pro-form-login-container___Z1bnX",icon:"icon___CBYA8"},hn=function(){return(0,e.jsx)(gn,{})},gn=function(){var i,u,j=(0,ae.useModel)("@@initialState"),m=j.initialState,C=j.setInitialState,P=(0,r.useState)(0),R=A()(P,2),b=R[0],D=R[1],B=(0,r.useState)(0),p=A()(B,2),K=p[0],Q=p[1],L=(0,r.useState)(!1),k=A()(L,2),W=k[0],w=k[1],y=(0,r.useState)(!1),x=A()(y,2),E=x[0],V=x[1],N=(0,ae.useSearchParams)(),O=A()(N,1),z=O[0],v=(0,r.useContext)(ke.DN),h=v.clientId,g=v.messageData,c=v.bind,Z=(0,r.useState)(),U=A()(Z,2),t=U[0],F=U[1],Be=(0,r.useState)(),S=A()(Be,2),Y=S[0],Ie=S[1];(0,r.useEffect)(function(){(0,We.A)().then(function(o){F(o),Ie(o==null?void 0:o.loginTypeDefault)})},[]);var Te=function(l){localStorage.setItem(_.lH,l.access_token),c==null||c(),C(function(f){return G()(G()({},f),{},{currentUser:l.userinfo,settings:G()(G()({},f==null?void 0:f.settings),l.setting)})}).then(function(){var f,M="/",d="/";if(l.userinfo.redirect)d=l.userinfo.redirect;else if(m!=null&&(f=m.settings)!==null&&f!==void 0&&f.baseurl){var $;d=M.replace(m==null||($=m.settings)===null||$===void 0?void 0:$.baseurl,"/")}ae.history.push(d)})};(0,r.useEffect)(function(){if(g){var o=g.data;(o==null?void 0:o.action)=="login"&&(localStorage.setItem("Sa-Remember","1"),ee.yw.success({content:o.msg,duration:1,onClose:function(){(0,Fe.flushSync)(function(){Te(o)})}}))}},[g]);var mn=function(){var o=q()(H()().mark(function l(f){return H()().wrap(function(d){for(;;)switch(d.prev=d.next){case 0:return d.next=2,_.ZP.post("login",{data:G()(G()({},f),{},{loginType:Y}),duration:1,then:function(I){var ie=I.code,J=I.msg,de=I.data;I.code?(ee.t6.error({description:J,message:"\u63D0\u793A"}),Y=="phone"&&I.code==3&&Q(b+1),W&&D(b+1),(I.code==3||I.code==2)&&w(!0)):(f.autoLogin?localStorage.setItem("Sa-Remember","1"):localStorage.setItem("Sa-Remember","0"),ee.yw.success({content:J,duration:1,onClose:function(){(0,Fe.flushSync)(function(){Te(I.data)})}}))}});case 2:return d.abrupt("return");case 3:case"end":return d.stop()}},l)}));return function(f){return o.apply(this,arguments)}}(),re={};t!=null&&t.loginBgImgage&&(re.backgroundImage='url("'+t.loginBgImgage+'")');var Ae=(0,r.useRef)(),pn=[{label:"\u624B\u673A\u53F7\u767B\u5F55",key:"phone",children:Y!="phone"?null:(0,e.jsxs)(e.Fragment,{children:[(0,e.jsx)(le.Z,{name:"mobile",fieldProps:{size:"large",prefix:(0,e.jsx)(pe.Z,{className:X.prefixIcon})},placeholder:"\u8BF7\u8F93\u5165\u624B\u673A\u53F7\u7801",rules:[{required:!0,message:"\u8BF7\u8F93\u5165\u624B\u673A\u53F7\uFF01"},{pattern:/^1\d{10}$/,message:"\u624B\u673A\u53F7\u683C\u5F0F\u9519\u8BEF\uFF01"}]}),(0,e.jsx)(ne.A.Item,{name:"captchaPhone",rules:[{required:!0,message:"\u83B7\u53D6\u624B\u673A\u9A8C\u8BC1\u7801\u8BF7\u8F93\u5165\u56FE\u5F62\u9A8C\u8BC1\u7801"}],children:(0,e.jsx)(oe,{reload:K,placeholder:"\u83B7\u53D6\u624B\u673A\u9A8C\u8BC1\u7801\u8BF7\u8F93\u5165\u56FE\u5F62\u9A8C\u8BC1\u7801"})}),(0,e.jsx)(He.Z,{name:["captchaPhone"],children:function(l){var f=l.captchaPhone;return(0,e.jsx)(Ve,{fieldProps:{size:"large",prefix:(0,e.jsx)(xe.Z,{})},countDown:60,captchaProps:{size:"large"},phoneName:"mobile",placeholder:"\u8BF7\u8F93\u5165\u9A8C\u8BC1\u7801",captchaTextRender:function(d,$){return d?"".concat($," \u83B7\u53D6\u9A8C\u8BC1\u7801"):"\u83B7\u53D6\u9A8C\u8BC1\u7801"},name:"mobilecode",rules:[{required:!0,message:"\u8BF7\u8F93\u5165\u9A8C\u8BC1\u7801\uFF01"}],onGetCaptcha:function(){var M=q()(H()().mark(function d($){var I,ie,J,de;return H()().wrap(function(T){for(;;)switch(T.prev=T.next){case 0:return T.prev=0,T.next=3,(I=Ae.current)===null||I===void 0?void 0:I.validateFields(["captcha"]);case 3:T.next=8;break;case 5:throw T.prev=5,T.t0=T.catch(0),new Error("\u8868\u5355\u9A8C\u8BC1\u5931\u8D25");case 8:return T.next=10,_.ZP.post("sms",{data:{mobile:$,captcha:f}});case 10:if(ie=T.sent,J=ie.code,de=ie.msg,!J){T.next=15;break}throw new Error(de);case 15:case"end":return T.stop()}},d,null,[[0,5]])}));return function(d){return M.apply(this,arguments)}}()})}}),W&&(0,e.jsx)(ne.A.Item,{name:"captcha",rules:[{required:!0,message:"\u8BF7\u8F93\u5165\u56FE\u5F62\u9A8C\u8BC1\u7801"}],children:(0,e.jsx)(oe,{reload:b})})]})},{label:"\u8D26\u53F7\u5BC6\u7801\u767B\u5F55",key:"password",children:Y!="password"?null:(0,e.jsxs)(e.Fragment,{children:[(0,e.jsx)(le.Z,{name:"username",fieldProps:{size:"large",prefix:(0,e.jsx)(pe.Z,{className:X.prefixIcon})},placeholder:"\u8BF7\u8F93\u5165\u8D26\u53F7\u540D\u79F0",rules:[{required:!0,message:"\u8BF7\u8F93\u5165\u7528\u6237\u540D!"}]}),(0,e.jsx)(le.Z.Password,{name:"password",fieldProps:{size:"large",prefix:(0,e.jsx)(xe.Z,{className:X.prefixIcon})},placeholder:"\u8BF7\u8F93\u5165\u767B\u5F55\u5BC6\u7801",rules:[{required:!0,message:"\u8BF7\u8F93\u5165\u5BC6\u7801\uFF01"}]}),W&&(0,e.jsx)(ne.A.Item,{name:"captcha",rules:[{required:!0,message:"\u8BF7\u8F93\u5165\u56FE\u5F62\u9A8C\u8BC1\u7801"}],children:(0,e.jsx)(oe,{reload:b})})]})}],xn=cn.Z.useToken(),Sn=xn.token,yn={marginInlineStart:"16px",color:(0,Ce.uK)(Sn.colorTextBase,.2),fontSize:"24px",verticalAlign:"middle",cursor:"pointer"},Cn=function(l){var f=l.type;if(f=="wechat"){var M=t==null?void 0:t.loginWechat,d=M.url,$=M.desc;return(0,e.jsxs)("div",{style:{textAlign:"center"},children:[(0,e.jsx)(dn.Z,{style:{margin:"0 auto"},value:d+"?client_id="+h}),(0,e.jsx)("div",{style:{marginTop:20},children:$})]})}return null};return t?(0,e.jsxs)("div",{className:X.container,style:G()({},re),children:[(0,e.jsx)(ae.Helmet,{children:(0,e.jsxs)("title",{children:["\u767B\u5F55 - ",t==null?void 0:t.title]})}),(0,e.jsx)("div",{className:X.content,style:re.backgroundImage?{}:{padding:0},children:(0,e.jsx)(Me.ZP,{style:{margin:"0px auto",padding:"20px 0",background:re.backgroundImage?"#fff":"none"},children:(0,e.jsxs)(tn,{contentStyle:{minWidth:280,maxWidth:"75vw"},containerStyle:{paddingInline:0},formRef:Ae,logo:t==null?void 0:t.logo,title:t==null?void 0:t.title,subTitle:t!=null&&t.subtitle?(0,e.jsx)("span",{dangerouslySetInnerHTML:{__html:t==null?void 0:t.subtitle}}):null,initialValues:{autoLogin:!0},onFinish:function(){var o=q()(H()().mark(function l(f){return H()().wrap(function(d){for(;;)switch(d.prev=d.next){case 0:return d.next=2,mn(f);case 2:case"end":return d.stop()}},l)}));return function(l){return o.apply(this,arguments)}}(),actions:t!=null&&t.loginActions?(0,e.jsxs)(ge.Z,{children:["\u5176\u4ED6\u767B\u5F55\u65B9\u5F0F",(0,e.jsx)(Ge.Z,{trigger:(0,e.jsx)(we.Z,{style:yn}),width:350,title:"\u626B\u7801\u767B\u5F55",children:t==null||(i=t.loginActions)===null||i===void 0?void 0:i.map(function(o,l){return(0,e.jsx)(Cn,{type:o},l)})})]}):null,children:[(0,e.jsx)(vn.Z,{centered:!0,activeKey:Y,onChange:function(l){Ie(l),V(l=="phone")},items:(u=t.loginType)===null||u===void 0?void 0:u.map(function(o){return pn.find(function(l){return l.key==o})})}),E?null:(0,e.jsxs)("div",{style:{marginBottom:24},children:[(0,e.jsx)(un,{noStyle:!0,name:"autoLogin",children:"\u81EA\u52A8\u767B\u5F55"}),(0,e.jsx)("a",{style:{float:"right"},onClick:function(){ee.yw.info("\u8BF7\u4F7F\u7528\u624B\u673A\u53F7\u767B\u5F55\u540E\u4FEE\u6539,\u6216\u8054\u7CFB\u540E\u53F0\u7BA1\u7406\u5458\u4FEE\u6539\u8D26\u53F7\u5BC6\u7801\uFF01")},children:"\u5FD8\u8BB0\u5BC6\u7801"})]})]})})}),(0,e.jsx)($e.Z,{})]}):null},fn=hn}}]);