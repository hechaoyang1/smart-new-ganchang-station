var gcxz={};
// 选项卡
gcxz.tab = function(options){
	var _t = options;
	var btn = _t.dom.find('.tab-top').children();
	var content = _t.dom.find('.tab-content').children();
	btn.on('click',function(){
		var t = $(this);
		var i = t.index();
		t.addClass('active').siblings().removeClass('active');
		content.eq(i).show().siblings().hide();
	});
};
// 返回顶部
gcxz.backTop = function(){
	$(document).scrollTop(0);
};

// 购物车
gcxz.shoppingCar = function(ele){
	if(gcxz.shoppingCar.isopen)return;
	gcxz.shoppingCar.isopen = true;
	$.post(urls.shoppingCar,function(data){
		 	ele = $(ele);
			var eleH = ele.height(),
			offsetHeight = $(document).scrollTop(),
			eleTop = ele.offset().top,
			centerHeight = eleTop - offsetHeight + eleH/2,
			appendHtml = $(data).hide().appendTo('body'),
			appendHtmlHeight = appendHtml.height();
		appendHtml.css({
			top:centerHeight - appendHtmlHeight/2-20
		}).show();

		setTimeout(function(){
			$(document).on('click',removeAppendHtml);
		});
	});
	
	function removeAppendHtml(e){
		var t = $(e.target);
		var modal = t.closest('.modal');
		var modal2 =$('.modal');
		if(modal2.length != 0 && modal.length === 0){
			modal2.remove();
			$(document).off('click',removeAppendHtml);
			gcxz.shoppingCar.isopen = false;
		}
	}
};
gcxz.drop_good = function(store_id, rec_id,obj) {
	var li = $(obj).parents('li');
	var shop = $(obj).parents('.shop');
	$.getJSON('index.php?app=cart&act=drop&rec_id=' + rec_id, function(result){
        if(result.done){
        	 //删除成功
            if(result.retval.cart.quantity == 0){
            	$('.rightTools').click();    //刷新
            }
            else{
            	/*如果还有其他商品，移除该商品*/
            	if(li.siblings('li').length>0){
	                li.remove();        //移除
            	}else{
            	/*如果没有其他商品，移除店铺*/
            		shop.remove(); 
            	}
            }
        }
    });
}
gcxz.shoppingCar.isopen = false;