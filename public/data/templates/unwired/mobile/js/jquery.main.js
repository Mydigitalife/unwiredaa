$(function(){
	$('ul.accordion').accordion({
		active: ".selected",
		autoHeight: false,
		header: ".opener",
		collapsible: true,
		event: "click",
		change: function(event, ui) {
			if ($(ui.newHeader).length && $(ui.newHeader).attr('href') != '#') {
				window.location.href = $(ui.newHeader).attr('href');
			}
		}
	});

	$('select').each(function(){
		var fakeSelect = $('<div class="selectArea"><span class="left"></span><span class="center">' + $(this).find('option:selected:first').text() + '</span><a class="selectButton" href="javascript:;"></a></div>');
		fakeSelect.width($(this).width());
		$(this).before(fakeSelect)
			   .css('opacity', 0.0001)
			   .css('position', 'relative')
			   .css('left', $(this).width());
	});
});

function isElementBefore(_el,_class) {
	var _parent = _el;
	do {
		_parent = _parent.parentNode;
	}
	while(_parent && _parent.className != null && _parent.className.indexOf(_class) == -1)
	return _parent.className && _parent.className.indexOf(_class) != -1;
}

function findPosY(obj) {
	if (obj.getBoundingClientRect) {
		var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
		var clientTop = document.documentElement.clientTop || document.body.clientTop || 0;
		return Math.round(obj.getBoundingClientRect().top + scrollTop - clientTop);
	} else {
		var posTop = 0;
		while (obj.offsetParent) {posTop += obj.offsetTop; obj = obj.offsetParent;}
		return posTop;
	}
}

function findPosX(obj) {
	if (obj.getBoundingClientRect) {
		var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft;
		var clientLeft = document.documentElement.clientLeft || document.body.clientLeft || 0;
		return Math.round(obj.getBoundingClientRect().left + scrollLeft - clientLeft);
	} else {
		var posLeft = 0;
		while (obj.offsetParent) {posLeft += obj.offsetLeft; obj = obj.offsetParent;}
		return posLeft;
	}
}
// crossplatform position fixed emulation
var overflowFix = {
	options: {
		forceMobile: false,
		fixedClass: 'iscroll-added',
		iScrollOptions: {onBeforeScrollStart: function(e){e.preventDefault()}},
		mobileReg: /(ipad|iphone|ipod|android|blackberry|opera mobi)/gi
	},
	init: function() {
		this.domReady(function(){
			this.getElements();
			this.addEvents();
		});
		return this;
	},
	getElements: function() {
		this.isMobile = this.options.mobileReg.test(navigator.userAgent) || this.options.forceMobile;
		if(this.isMobile || this.options.forceMobile) {
			this.scrollBlocks = document.getElementsByTagName('div');
			for(var i = 0; i < this.scrollBlocks.length; i++) {
				var overflowStyle = this.getStyle(this.scrollBlocks[i], 'overflow');
				if(overflowStyle === 'scroll' || overflowStyle === 'auto') {
					this.initScroll(this.scrollBlocks[i]);
				}
			}
		}
	},
	initScroll: function(block) {
		if(block.iScrollInst) {
			block.iScrollInst.refresh();
		} else {
			var createBlock = document.createElement('div');
			while(block.childNodes.length) createBlock.appendChild(block.childNodes[0]);
			createBlock.className = 'iscroll-wrapper';
			block.className += ' '+this.options.fixedClass;
			block.style.position = 'relative';
			block.appendChild(createBlock);
			block.iScrollInst = new iScroll(block, this.options.iScrollOptions);
		}
	},
	addEvents: function() {
		if (window.addEventListener) {
			window.addEventListener('resize', this.bind(this.refreshAll), false);
			window.addEventListener('orientationchange', this.bind(this.refreshAll), false);
		}
	},
	refreshAll: function() {
		if(this.scrollBlocks) {
			for(var i = 0; i < this.scrollBlocks.length; i++) {
				if(this.scrollBlocks[i].iScrollInst) {
					this.scrollBlocks[i].iScrollInst.refresh();
				}
			}
		}
	},
	domReady: function(fn) {
		var scope = this, calledFlag;
		(function(){
			if (document.addEventListener) {
				document.addEventListener('DOMContentLoaded', function(){
					if(!calledFlag) { calledFlag = true; fn.call(scope); }
				}, false)
			}
			if (!document.readyState || document.readyState.indexOf('in') != -1) {
				setTimeout(arguments.callee, 9);
			} else {
				if(!calledFlag) { calledFlag = true; fn.call(scope); }
			}
		}());
	},
	getStyle: function(el, prop) {
		if (document.defaultView && document.defaultView.getComputedStyle) {
			return document.defaultView.getComputedStyle(el, null)[prop];
		} else if (el.currentStyle) {
			return el.currentStyle[prop];
		} else {
			return el.style[prop];
		}
	},
	bind: function(fn, scope, args){
		var newScope = scope || this;
		return function() {
			return fn.apply(newScope, args || arguments);
		}
	}
}.init();

/*!
 * iScroll v4.1.9 ~ Copyright (c) 2011 Matteo Spinelli, http://cubiq.org
 * Released under MIT license, http://cubiq.org/license
 */
;(function(){var p=Math,t=(/webkit/i).test(navigator.appVersion)?"webkit":(/firefox/i).test(navigator.userAgent)?"Moz":"opera" in window?"O":"",j="WebKitCSSMatrix" in window&&"m11" in new WebKitCSSMatrix(),s="ontouchstart" in window,e=t+"Transform" in document.documentElement.style,u=(/android/gi).test(navigator.appVersion),h=(/iphone|ipad/gi).test(navigator.appVersion),c=(/playbook/gi).test(navigator.appVersion),f=h||c,l=(function(){return window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.oRequestAnimationFrame||window.msRequestAnimationFrame||function(m){return setTimeout(m,1)}})(),k=(function(){return window.cancelRequestAnimationFrame||window.webkitCancelRequestAnimationFrame||window.mozCancelRequestAnimationFrame||window.oCancelRequestAnimationFrame||window.msCancelRequestAnimationFrame||clearTimeout})(),g="onorientationchange" in window?"orientationchange":"resize",b=s?"touchstart":"mousedown",n=s?"touchmove":"mousemove",d=s?"touchend":"mouseup",r=s?"touchcancel":"mouseup",o=t=="Moz"?"DOMMouseScroll":"mousewheel",a="translate"+(j?"3d(":"("),i=j?",0)":")",q=function(w,m){var x=this,y=document,v;x.wrapper=typeof w=="object"?w:y.getElementById(w);x.wrapper.style.overflow="hidden";x.scroller=x.wrapper.children[0];x.options={hScroll:true,vScroll:true,x:0,y:0,bounce:true,bounceLock:false,momentum:true,lockDirection:true,useTransform:true,useTransition:false,topOffset:0,checkDOMChanges:false,hScrollbar:true,vScrollbar:true,fixedScrollbar:u,hideScrollbar:h,fadeScrollbar:h&&j,scrollbarClass:"",zoom:false,zoomMin:1,zoomMax:4,doubleTapZoom:2,wheelAction:"scroll",snap:false,snapThreshold:1,onRefresh:null,onBeforeScrollStart:function(z){z.preventDefault()},onScrollStart:null,onBeforeScrollMove:null,onScrollMove:null,onBeforeScrollEnd:null,onScrollEnd:null,onTouchEnd:null,onDestroy:null,onZoomStart:null,onZoom:null,onZoomEnd:null};for(v in m){x.options[v]=m[v]}x.x=x.options.x;x.y=x.options.y;x.options.useTransform=e?x.options.useTransform:false;x.options.hScrollbar=x.options.hScroll&&x.options.hScrollbar;x.options.vScrollbar=x.options.vScroll&&x.options.vScrollbar;x.options.zoom=x.options.useTransform&&x.options.zoom;x.options.useTransition=f&&x.options.useTransition;x.scroller.style[t+"TransitionProperty"]=x.options.useTransform?"-"+t.toLowerCase()+"-transform":"top left";x.scroller.style[t+"TransitionDuration"]="0";x.scroller.style[t+"TransformOrigin"]="0 0";if(x.options.useTransition){x.scroller.style[t+"TransitionTimingFunction"]="cubic-bezier(0.33,0.66,0.66,1)"}if(x.options.useTransform){x.scroller.style[t+"Transform"]=a+x.x+"px,"+x.y+"px"+i}else{x.scroller.style.cssText+=";position:absolute;top:"+x.y+"px;left:"+x.x+"px"}if(x.options.useTransition){x.options.fixedScrollbar=true}x.refresh();x._bind(g,window);x._bind(b);if(!s){x._bind("mouseout",x.wrapper);x._bind(o)}if(x.options.checkDOMChanges){x.checkDOMTime=setInterval(function(){x._checkDOMChanges()},500)}};q.prototype={enabled:true,x:0,y:0,steps:[],scale:1,currPageX:0,currPageY:0,pagesX:[],pagesY:[],aniTime:null,wheelZoomCount:0,handleEvent:function(v){var m=this;switch(v.type){case b:if(!s&&v.button!==0){return}m._start(v);break;case n:m._move(v);break;case d:case r:m._end(v);break;case g:m._resize();break;case o:m._wheel(v);break;case"mouseout":m._mouseout(v);break;case"webkitTransitionEnd":m._transitionEnd(v);break}},_checkDOMChanges:function(){if(this.moved||this.zoomed||this.animating||(this.scrollerW==this.scroller.offsetWidth*this.scale&&this.scrollerH==this.scroller.offsetHeight*this.scale)){return}this.refresh()},_scrollbar:function(m){var w=this,x=document,v;if(!w[m+"Scrollbar"]){if(w[m+"ScrollbarWrapper"]){if(e){w[m+"ScrollbarIndicator"].style[t+"Transform"]=""}w[m+"ScrollbarWrapper"].parentNode.removeChild(w[m+"ScrollbarWrapper"]);w[m+"ScrollbarWrapper"]=null;w[m+"ScrollbarIndicator"]=null}return}if(!w[m+"ScrollbarWrapper"]){v=x.createElement("div");if(w.options.scrollbarClass){v.className=w.options.scrollbarClass+m.toUpperCase()}else{v.style.cssText="position:absolute;z-index:100;"+(m=="h"?"height:7px;bottom:1px;left:2px;right:"+(w.vScrollbar?"7":"2")+"px":"width:7px;bottom:"+(w.hScrollbar?"7":"2")+"px;top:2px;right:1px")}v.style.cssText+=";pointer-events:none;-"+t+"-transition-property:opacity;-"+t+"-transition-duration:"+(w.options.fadeScrollbar?"350ms":"0")+";overflow:hidden;opacity:"+(w.options.hideScrollbar?"0":"1");w.wrapper.appendChild(v);w[m+"ScrollbarWrapper"]=v;v=x.createElement("div");if(!w.options.scrollbarClass){v.style.cssText="position:absolute;z-index:100;background:rgba(0,0,0,0.5);border:1px solid rgba(255,255,255,0.9);-"+t+"-background-clip:padding-box;-"+t+"-box-sizing:border-box;"+(m=="h"?"height:100%":"width:100%")+";-"+t+"-border-radius:3px;border-radius:3px"}v.style.cssText+=";pointer-events:none;-"+t+"-transition-property:-"+t+"-transform;-"+t+"-transition-timing-function:cubic-bezier(0.33,0.66,0.66,1);-"+t+"-transition-duration:0;-"+t+"-transform:"+a+"0,0"+i;if(w.options.useTransition){v.style.cssText+=";-"+t+"-transition-timing-function:cubic-bezier(0.33,0.66,0.66,1)"}w[m+"ScrollbarWrapper"].appendChild(v);w[m+"ScrollbarIndicator"]=v}if(m=="h"){w.hScrollbarSize=w.hScrollbarWrapper.clientWidth;w.hScrollbarIndicatorSize=p.max(p.round(w.hScrollbarSize*w.hScrollbarSize/w.scrollerW),8);w.hScrollbarIndicator.style.width=w.hScrollbarIndicatorSize+"px";w.hScrollbarMaxScroll=w.hScrollbarSize-w.hScrollbarIndicatorSize;w.hScrollbarProp=w.hScrollbarMaxScroll/w.maxScrollX}else{w.vScrollbarSize=w.vScrollbarWrapper.clientHeight;w.vScrollbarIndicatorSize=p.max(p.round(w.vScrollbarSize*w.vScrollbarSize/w.scrollerH),8);w.vScrollbarIndicator.style.height=w.vScrollbarIndicatorSize+"px";w.vScrollbarMaxScroll=w.vScrollbarSize-w.vScrollbarIndicatorSize;w.vScrollbarProp=w.vScrollbarMaxScroll/w.maxScrollY}w._scrollbarPos(m,true)},_resize:function(){var m=this;setTimeout(function(){m.refresh()},u?200:0)},_pos:function(m,v){m=this.hScroll?m:0;v=this.vScroll?v:0;if(this.options.useTransform){this.scroller.style[t+"Transform"]=a+m+"px,"+v+"px"+i+" scale("+this.scale+")"}else{m=p.round(m);v=p.round(v);this.scroller.style.left=m+"px";this.scroller.style.top=v+"px"}this.x=m;this.y=v;this._scrollbarPos("h");this._scrollbarPos("v")},_scrollbarPos:function(m,x){var w=this,y=m=="h"?w.x:w.y,v;if(!w[m+"Scrollbar"]){return}y=w[m+"ScrollbarProp"]*y;if(y<0){if(!w.options.fixedScrollbar){v=w[m+"ScrollbarIndicatorSize"]+p.round(y*3);if(v<8){v=8}w[m+"ScrollbarIndicator"].style[m=="h"?"width":"height"]=v+"px"}y=0}else{if(y>w[m+"ScrollbarMaxScroll"]){if(!w.options.fixedScrollbar){v=w[m+"ScrollbarIndicatorSize"]-p.round((y-w[m+"ScrollbarMaxScroll"])*3);if(v<8){v=8}w[m+"ScrollbarIndicator"].style[m=="h"?"width":"height"]=v+"px";y=w[m+"ScrollbarMaxScroll"]+(w[m+"ScrollbarIndicatorSize"]-v)}else{y=w[m+"ScrollbarMaxScroll"]}}}w[m+"ScrollbarWrapper"].style[t+"TransitionDelay"]="0";w[m+"ScrollbarWrapper"].style.opacity=x&&w.options.hideScrollbar?"0":"1";w[m+"ScrollbarIndicator"].style[t+"Transform"]=a+(m=="h"?y+"px,0":"0,"+y+"px")+i},_start:function(C){var B=this,v=s?C.touches[0]:C,w,m,D,A,z;if(!B.enabled){return}if(B.options.onBeforeScrollStart){B.options.onBeforeScrollStart.call(B,C)}if(B.options.useTransition||B.options.zoom){B._transitionTime(0)}B.moved=false;B.animating=false;B.zoomed=false;B.distX=0;B.distY=0;B.absDistX=0;B.absDistY=0;B.dirX=0;B.dirY=0;if(B.options.zoom&&s&&C.touches.length>1){A=p.abs(C.touches[0].pageX-C.touches[1].pageX);z=p.abs(C.touches[0].pageY-C.touches[1].pageY);B.touchesDistStart=p.sqrt(A*A+z*z);B.originX=p.abs(C.touches[0].pageX+C.touches[1].pageX-B.wrapperOffsetLeft*2)/2-B.x;B.originY=p.abs(C.touches[0].pageY+C.touches[1].pageY-B.wrapperOffsetTop*2)/2-B.y;if(B.options.onZoomStart){B.options.onZoomStart.call(B,C)}}if(B.options.momentum){if(B.options.useTransform){w=getComputedStyle(B.scroller,null)[t+"Transform"].replace(/[^0-9-.,]/g,"").split(",");m=w[4]*1;D=w[5]*1}else{m=getComputedStyle(B.scroller,null).left.replace(/[^0-9-]/g,"")*1;D=getComputedStyle(B.scroller,null).top.replace(/[^0-9-]/g,"")*1}if(m!=B.x||D!=B.y){if(B.options.useTransition){B._unbind("webkitTransitionEnd")}else{k(B.aniTime)}B.steps=[];B._pos(m,D)}}B.absStartX=B.x;B.absStartY=B.y;B.startX=B.x;B.startY=B.y;B.pointX=v.pageX;B.pointY=v.pageY;B.startTime=C.timeStamp||Date.now();if(B.options.onScrollStart){B.options.onScrollStart.call(B,C)}B._bind(n);B._bind(d);B._bind(r)},_move:function(C){var A=this,D=s?C.touches[0]:C,y=D.pageX-A.pointX,w=D.pageY-A.pointY,m=A.x+y,E=A.y+w,z,x,v,B=C.timeStamp||Date.now();if(A.options.onBeforeScrollMove){A.options.onBeforeScrollMove.call(A,C)}if(A.options.zoom&&s&&C.touches.length>1){z=p.abs(C.touches[0].pageX-C.touches[1].pageX);x=p.abs(C.touches[0].pageY-C.touches[1].pageY);A.touchesDist=p.sqrt(z*z+x*x);A.zoomed=true;v=1/A.touchesDistStart*A.touchesDist*this.scale;if(v<A.options.zoomMin){v=0.5*A.options.zoomMin*Math.pow(2,v/A.options.zoomMin)}else{if(v>A.options.zoomMax){v=2*A.options.zoomMax*Math.pow(0.5,A.options.zoomMax/v)}}A.lastScale=v/this.scale;m=this.originX-this.originX*A.lastScale+this.x,E=this.originY-this.originY*A.lastScale+this.y;this.scroller.style[t+"Transform"]=a+m+"px,"+E+"px"+i+" scale("+v+")";if(A.options.onZoom){A.options.onZoom.call(A,C)}return}A.pointX=D.pageX;A.pointY=D.pageY;if(m>0||m<A.maxScrollX){m=A.options.bounce?A.x+(y/2):m>=0||A.maxScrollX>=0?0:A.maxScrollX}if(E>A.minScrollY||E<A.maxScrollY){E=A.options.bounce?A.y+(w/2):E>=A.minScrollY||A.maxScrollY>=0?A.minScrollY:A.maxScrollY}if(A.absDistX<6&&A.absDistY<6){A.distX+=y;A.distY+=w;A.absDistX=p.abs(A.distX);A.absDistY=p.abs(A.distY);return}if(A.options.lockDirection){if(A.absDistX>A.absDistY+5){E=A.y;w=0}else{if(A.absDistY>A.absDistX+5){m=A.x;y=0}}}A.moved=true;A._pos(m,E);A.dirX=y>0?-1:y<0?1:0;A.dirY=w>0?-1:w<0?1:0;if(B-A.startTime>300){A.startTime=B;A.startX=A.x;A.startY=A.y}if(A.options.onScrollMove){A.options.onScrollMove.call(A,C)}},_end:function(C){if(s&&C.touches.length!=0){return}var A=this,I=s?C.changedTouches[0]:C,D,H,w={dist:0,time:0},m={dist:0,time:0},z=(C.timeStamp||Date.now())-A.startTime,E=A.x,B=A.y,G,F,v,y,x;A._unbind(n);A._unbind(d);A._unbind(r);if(A.options.onBeforeScrollEnd){A.options.onBeforeScrollEnd.call(A,C)}if(A.zoomed){x=A.scale*A.lastScale;x=Math.max(A.options.zoomMin,x);x=Math.min(A.options.zoomMax,x);A.lastScale=x/A.scale;A.scale=x;A.x=A.originX-A.originX*A.lastScale+A.x;A.y=A.originY-A.originY*A.lastScale+A.y;A.scroller.style[t+"TransitionDuration"]="200ms";A.scroller.style[t+"Transform"]=a+A.x+"px,"+A.y+"px"+i+" scale("+A.scale+")";A.zoomed=false;A.refresh();if(A.options.onZoomEnd){A.options.onZoomEnd.call(A,C)}return}if(!A.moved){if(s){if(A.doubleTapTimer&&A.options.zoom){clearTimeout(A.doubleTapTimer);A.doubleTapTimer=null;if(A.options.onZoomStart){A.options.onZoomStart.call(A,C)}A.zoom(A.pointX,A.pointY,A.scale==1?A.options.doubleTapZoom:1);if(A.options.onZoomEnd){setTimeout(function(){A.options.onZoomEnd.call(A,C)},200)}}else{A.doubleTapTimer=setTimeout(function(){A.doubleTapTimer=null;D=I.target;while(D.nodeType!=1){D=D.parentNode}if(D.tagName!="SELECT"&&D.tagName!="INPUT"&&D.tagName!="TEXTAREA"){H=document.createEvent("MouseEvents");H.initMouseEvent("click",true,true,C.view,1,I.screenX,I.screenY,I.clientX,I.clientY,C.ctrlKey,C.altKey,C.shiftKey,C.metaKey,0,null);H._fake=true;D.dispatchEvent(H)}},A.options.zoom?250:0)}}A._resetPos(200);if(A.options.onTouchEnd){A.options.onTouchEnd.call(A,C)}return}if(z<300&&A.options.momentum){w=E?A._momentum(E-A.startX,z,-A.x,A.scrollerW-A.wrapperW+A.x,A.options.bounce?A.wrapperW:0):w;m=B?A._momentum(B-A.startY,z,-A.y,(A.maxScrollY<0?A.scrollerH-A.wrapperH+A.y-A.minScrollY:0),A.options.bounce?A.wrapperH:0):m;E=A.x+w.dist;B=A.y+m.dist;if((A.x>0&&E>0)||(A.x<A.maxScrollX&&E<A.maxScrollX)){w={dist:0,time:0}}if((A.y>A.minScrollY&&B>A.minScrollY)||(A.y<A.maxScrollY&&B<A.maxScrollY)){m={dist:0,time:0}}}if(w.dist||m.dist){v=p.max(p.max(w.time,m.time),10);if(A.options.snap){G=E-A.absStartX;F=B-A.absStartY;if(p.abs(G)<A.options.snapThreshold&&p.abs(F)<A.options.snapThreshold){A.scrollTo(A.absStartX,A.absStartY,200)}else{y=A._snap(E,B);E=y.x;B=y.y;v=p.max(y.time,v)}}A.scrollTo(p.round(E),p.round(B),v);if(A.options.onTouchEnd){A.options.onTouchEnd.call(A,C)}return}if(A.options.snap){G=E-A.absStartX;F=B-A.absStartY;if(p.abs(G)<A.options.snapThreshold&&p.abs(F)<A.options.snapThreshold){A.scrollTo(A.absStartX,A.absStartY,200)}else{y=A._snap(A.x,A.y);if(y.x!=A.x||y.y!=A.y){A.scrollTo(y.x,y.y,y.time)}}if(A.options.onTouchEnd){A.options.onTouchEnd.call(A,C)}return}A._resetPos(200);if(A.options.onTouchEnd){A.options.onTouchEnd.call(A,C)}},_resetPos:function(w){var m=this,x=m.x>=0?0:m.x<m.maxScrollX?m.maxScrollX:m.x,v=m.y>=m.minScrollY||m.maxScrollY>0?m.minScrollY:m.y<m.maxScrollY?m.maxScrollY:m.y;if(x==m.x&&v==m.y){if(m.moved){m.moved=false;if(m.options.onScrollEnd){m.options.onScrollEnd.call(m)}}if(m.hScrollbar&&m.options.hideScrollbar){if(t=="webkit"){m.hScrollbarWrapper.style[t+"TransitionDelay"]="300ms"}m.hScrollbarWrapper.style.opacity="0"}if(m.vScrollbar&&m.options.hideScrollbar){if(t=="webkit"){m.vScrollbarWrapper.style[t+"TransitionDelay"]="300ms"}m.vScrollbarWrapper.style.opacity="0"}return}m.scrollTo(x,v,w||0)},_wheel:function(z){var x=this,y,w,v,m,A;if("wheelDeltaX" in z){y=z.wheelDeltaX/12;w=z.wheelDeltaY/12}else{if("detail" in z){y=w=-z.detail*3}else{y=w=-z.wheelDelta}}if(x.options.wheelAction=="zoom"){A=x.scale*Math.pow(2,1/3*(w?w/Math.abs(w):0));if(A<x.options.zoomMin){A=x.options.zoomMin}if(A>x.options.zoomMax){A=x.options.zoomMax}if(A!=x.scale){if(!x.wheelZoomCount&&x.options.onZoomStart){x.options.onZoomStart.call(x,z)}x.wheelZoomCount++;x.zoom(z.pageX,z.pageY,A,400);setTimeout(function(){x.wheelZoomCount--;if(!x.wheelZoomCount&&x.options.onZoomEnd){x.options.onZoomEnd.call(x,z)}},400)}return}v=x.x+y;m=x.y+w;if(v>0){v=0}else{if(v<x.maxScrollX){v=x.maxScrollX}}if(m>x.minScrollY){m=x.minScrollY}else{if(m<x.maxScrollY){m=x.maxScrollY}}x.scrollTo(v,m,0)},_mouseout:function(v){var m=v.relatedTarget;if(!m){this._end(v);return}while(m=m.parentNode){if(m==this.wrapper){return}}this._end(v)},_transitionEnd:function(v){var m=this;if(v.target!=m.scroller){return}m._unbind("webkitTransitionEnd");m._startAni()},_startAni:function(){var A=this,v=A.x,m=A.y,y=Date.now(),z,x,w;if(A.animating){return}if(!A.steps.length){A._resetPos(400);return}z=A.steps.shift();if(z.x==v&&z.y==m){z.time=0}A.animating=true;A.moved=true;if(A.options.useTransition){A._transitionTime(z.time);A._pos(z.x,z.y);A.animating=false;if(z.time){A._bind("webkitTransitionEnd")}else{A._resetPos(0)}return}w=function(){var B=Date.now(),D,C;if(B>=y+z.time){A._pos(z.x,z.y);A.animating=false;if(A.options.onAnimationEnd){A.options.onAnimationEnd.call(A)}A._startAni();return}B=(B-y)/z.time-1;x=p.sqrt(1-B*B);D=(z.x-v)*x+v;C=(z.y-m)*x+m;A._pos(D,C);if(A.animating){A.aniTime=l(w)}};w()},_transitionTime:function(m){m+="ms";this.scroller.style[t+"TransitionDuration"]=m;if(this.hScrollbar){this.hScrollbarIndicator.style[t+"TransitionDuration"]=m}if(this.vScrollbar){this.vScrollbarIndicator.style[t+"TransitionDuration"]=m}},_momentum:function(B,v,z,m,D){var A=0.0006,w=p.abs(B)/v,x=(w*w)/(2*A),C=0,y=0;if(B>0&&x>z){y=D/(6/(x/w*A));z=z+y;w=w*z/x;x=z}else{if(B<0&&x>m){y=D/(6/(x/w*A));m=m+y;w=w*m/x;x=m}}x=x*(B<0?-1:1);C=w/A;return{dist:x,time:p.round(C)}},_offset:function(m){var w=-m.offsetLeft,v=-m.offsetTop;while(m=m.offsetParent){w-=m.offsetLeft;v-=m.offsetTop}if(m!=this.wrapper){w*=this.scale;v*=this.scale}return{left:w,top:v}},_snap:function(E,D){var B=this,A,z,C,w,v,m;C=B.pagesX.length-1;for(A=0,z=B.pagesX.length;A<z;A++){if(E>=B.pagesX[A]){C=A;break}}if(C==B.currPageX&&C>0&&B.dirX<0){C--}E=B.pagesX[C];v=p.abs(E-B.pagesX[B.currPageX]);v=v?p.abs(B.x-E)/v*500:0;B.currPageX=C;C=B.pagesY.length-1;for(A=0;A<C;A++){if(D>=B.pagesY[A]){C=A;break}}if(C==B.currPageY&&C>0&&B.dirY<0){C--}D=B.pagesY[C];m=p.abs(D-B.pagesY[B.currPageY]);m=m?p.abs(B.y-D)/m*500:0;B.currPageY=C;w=p.round(p.max(v,m))||200;return{x:E,y:D,time:w}},_bind:function(w,v,m){(v||this.scroller).addEventListener(w,this,!!m)},_unbind:function(w,v,m){(v||this.scroller).removeEventListener(w,this,!!m)},destroy:function(){var m=this;m.scroller.style[t+"Transform"]="";m.hScrollbar=false;m.vScrollbar=false;m._scrollbar("h");m._scrollbar("v");m._unbind(g,window);m._unbind(b);m._unbind(n);m._unbind(d);m._unbind(r);if(m.options.hasTouch){m._unbind("mouseout",m.wrapper);m._unbind(o)}if(m.options.useTransition){m._unbind("webkitTransitionEnd")}if(m.options.checkDOMChanges){clearInterval(m.checkDOMTime)}if(m.options.onDestroy){m.options.onDestroy.call(m)}},refresh:function(){var x=this,z,w,m,v,A=0,y=0;if(x.scale<x.options.zoomMin){x.scale=x.options.zoomMin}x.wrapperW=x.wrapper.clientWidth||1;x.wrapperH=x.wrapper.clientHeight||1;x.minScrollY=-x.options.topOffset||0;x.scrollerW=p.round(x.scroller.offsetWidth*x.scale);x.scrollerH=p.round((x.scroller.offsetHeight+x.minScrollY)*x.scale);x.maxScrollX=x.wrapperW-x.scrollerW;x.maxScrollY=x.wrapperH-x.scrollerH+x.minScrollY;x.dirX=0;x.dirY=0;if(x.options.onRefresh){x.options.onRefresh.call(x)}x.hScroll=x.options.hScroll&&x.maxScrollX<0;x.vScroll=x.options.vScroll&&(!x.options.bounceLock&&!x.hScroll||x.scrollerH>x.wrapperH);x.hScrollbar=x.hScroll&&x.options.hScrollbar;x.vScrollbar=x.vScroll&&x.options.vScrollbar&&x.scrollerH>x.wrapperH;z=x._offset(x.wrapper);x.wrapperOffsetLeft=-z.left;x.wrapperOffsetTop=-z.top;if(typeof x.options.snap=="string"){x.pagesX=[];x.pagesY=[];v=x.scroller.querySelectorAll(x.options.snap);for(w=0,m=v.length;w<m;w++){A=x._offset(v[w]);A.left+=x.wrapperOffsetLeft;A.top+=x.wrapperOffsetTop;x.pagesX[w]=A.left<x.maxScrollX?x.maxScrollX:A.left*x.scale;x.pagesY[w]=A.top<x.maxScrollY?x.maxScrollY:A.top*x.scale}}else{if(x.options.snap){x.pagesX=[];while(A>=x.maxScrollX){x.pagesX[y]=A;A=A-x.wrapperW;y++}if(x.maxScrollX%x.wrapperW){x.pagesX[x.pagesX.length]=x.maxScrollX-x.pagesX[x.pagesX.length-1]+x.pagesX[x.pagesX.length-1]}A=0;y=0;x.pagesY=[];while(A>=x.maxScrollY){x.pagesY[y]=A;A=A-x.wrapperH;y++}if(x.maxScrollY%x.wrapperH){x.pagesY[x.pagesY.length]=x.maxScrollY-x.pagesY[x.pagesY.length-1]+x.pagesY[x.pagesY.length-1]}}}x._scrollbar("h");x._scrollbar("v");if(!x.zoomed){x.scroller.style[t+"TransitionDuration"]="0";x._resetPos(200)}},scrollTo:function(m,D,C,B){var A=this,z=m,w,v;A.stop();if(!z.length){z=[{x:m,y:D,time:C,relative:B}]}for(w=0,v=z.length;w<v;w++){if(z[w].relative){z[w].x=A.x-z[w].x;z[w].y=A.y-z[w].y}A.steps.push({x:z[w].x,y:z[w].y,time:z[w].time||0})}A._startAni()},scrollToElement:function(m,w){var v=this,x;m=m.nodeType?m:v.scroller.querySelector(m);if(!m){return}x=v._offset(m);x.left+=v.wrapperOffsetLeft;x.top+=v.wrapperOffsetTop;x.left=x.left>0?0:x.left<v.maxScrollX?v.maxScrollX:x.left;x.top=x.top>v.minScrollY?v.minScrollY:x.top<v.maxScrollY?v.maxScrollY:x.top;w=w===undefined?p.max(p.abs(x.left)*2,p.abs(x.top)*2):w;v.scrollTo(x.left,x.top,w)},scrollToPage:function(w,v,A){var z=this,m,B;if(z.options.onScrollStart){z.options.onScrollStart.call(z)}if(z.options.snap){w=w=="next"?z.currPageX+1:w=="prev"?z.currPageX-1:w;v=v=="next"?z.currPageY+1:v=="prev"?z.currPageY-1:v;w=w<0?0:w>z.pagesX.length-1?z.pagesX.length-1:w;v=v<0?0:v>z.pagesY.length-1?z.pagesY.length-1:v;z.currPageX=w;z.currPageY=v;m=z.pagesX[w];B=z.pagesY[v]}else{m=-z.wrapperW*w;B=-z.wrapperH*v;if(m<z.maxScrollX){m=z.maxScrollX}if(B<z.maxScrollY){B=z.maxScrollY}}z.scrollTo(m,B,A||400)},disable:function(){this.stop();this._resetPos(0);this.enabled=false;this._unbind(n);this._unbind(d);this._unbind(r)},enable:function(){this.enabled=true},stop:function(){if(this.options.useTransition){this._unbind("webkitTransitionEnd")}else{k(this.aniTime)}this.steps=[];this.moved=false;this.animating=false},zoom:function(m,B,A,z){var v=this,w=A/v.scale;if(!v.options.useTransform){return}v.zoomed=true;z=z===undefined?200:z;m=m-v.wrapperOffsetLeft-v.x;B=B-v.wrapperOffsetTop-v.y;v.x=m-m*w+v.x;v.y=B-B*w+v.y;v.scale=A;v.refresh();v.x=v.x>0?0:v.x<v.maxScrollX?v.maxScrollX:v.x;v.y=v.y>v.minScrollY?v.minScrollY:v.y<v.maxScrollY?v.maxScrollY:v.y;v.scroller.style[t+"TransitionDuration"]=z+"ms";v.scroller.style[t+"Transform"]=a+v.x+"px,"+v.y+"px"+i+" scale("+A+")";v.zoomed=false},isReady:function(){return !this.moved&&!this.zoomed&&!this.animating}};if(typeof exports!=="undefined"){exports.iScroll=q}else{window.iScroll=q}})();