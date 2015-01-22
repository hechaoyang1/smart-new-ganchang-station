var gcxz= gcxz || {};


gcxz.tab = function(options){
	var _t = options;
	var btn = _t.dom.find('.tab-top').children();
	var content = _t.dom.find('.tab-content').children();
	btn.bind('click',function(){
		var t = $(this);
		var i = t.index();
		t.addClass('active').siblings().removeClass('active');
		content.eq(i).show().siblings().hide();
	});
};

(function(){

	gcxz.tab({
		dom:$('.classify-tab')
	});
})();