gcxz.Slide = function (options) {
	var _t = this;
	_t.dom = options.dom;
	_t.auto = options.auto;
	_t.step = options.step || 1;
	_t.bindEvent();
}
gcxz.Slide.prototype.bindEvent = function() {
	var _t = this;
	_t.index = 0;
	_t.btn = _t.dom.find('.slide-btn>li');
	_t.imgList = _t.dom.find('.imgList');
	_t.lrbtn = _t.dom.find('.slide-left,.slide-right');
	_t.lrbtn.on('click',function () {
		var t = $(this);
		if(t.hasClass('slide-left')){
			--_t.index;
		}else if(t.hasClass('slide-right')){
			++_t.index;
		}
		var max = _t.imgList.find('li').length / _t.step;
		if(_t.index >= max){
			_t.index = 0;
		}
		if(_t.index < 0){
			_t.index = max-1;
		}
		_t.cur(t);
		_t.animate(_t.index);
	});
	_t.btn.on('mouseover',function(){
		var t = $(this);
		_t.index = t.index();
		_t.cur(t);
		_t.animate(_t.index);
	});
	_t.autoLoop();
};
gcxz.Slide.prototype.cur = function(obj) {
	obj.addClass('cur').siblings().removeClass('cur');
};
gcxz.Slide.prototype.autoLoop = function() {
	var _t = this;
	if(_t.auto){
		_t.timer = setInterval(function(){
			var max = _t.imgList.find('li').length / _t.step;
			if(_t.index >= max){
				_t.index = 0;
			}
			if(_t.index < 0){
				_t.index = max - 1;
			}
			_t.cur(_t.btn.eq(_t.index));
			_t.animate(_t.index);
		},5000);
	}
};
gcxz.Slide.prototype.animate = function(i) {
	var _t = this;
	var imgs = _t.imgList;
	if(!_t.imgWidth){
		_t.imgWidth = imgs.find('li').eq(0).outerWidth(true);
	}
	if(_t.timer){
		clearInterval(_t.timer);
	}
	imgs.stop().animate({
		marginLeft:-i*_t.imgWidth*_t.step
	},function(){
		_t.autoLoop();
	});
};
(function(){
	// 幻灯片
	new gcxz.Slide({
		dom:$('.slide'),
		auto:1
	});

	// 小站特色
	new gcxz.Slide({
		dom:$('.characteristic'),
		step:5
	});

	var f1 = $("#f1");
	var nav = $('.nav');
	var fixedTools = $('.fixedTools');
	var fixedSearch = $('.fixedSearch');

	fixedTools.on('click','a',function(){
		var className = $(this).attr('class');
		console.log($('#'+className).offset());
		$(document).scrollTop($('#'+className).offset().top - 79);
	});

	// 左侧楼层跳转/浮动搜索
	$(window).on('scroll',function () {
		var range = f1[0].getBoundingClientRect();
		var navRange = nav[0].getBoundingClientRect();
		if(range.top < 10){
			fixedTools.addClass('fixed');
		}else{
			fixedTools.removeClass('fixed');
		}

		if(navRange.top < 10){
			fixedSearch.fadeIn();
		}else{
			fixedSearch.fadeOut();
			// fixedSearch.css({
			// 	opacity:0
			// });
		}
	});
})();

//楼层广告切换
$(function() {
                $("#1f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#2f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });
				
				$("#3f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#4f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#5f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#6f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#7f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#8f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#9f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#10f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#11f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#12f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

                $("#13f").ggqh({
                    
                    slideshow_window_width: '203',
                    slideshow_window_height: '421',
                    slideshow_button_current_background_color: '#666',
                });

            });