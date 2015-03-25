!function () {
	var bigImg = $('.goodsImg').find('.bigImg');
	// 商品图片
	goodsDeltailedImg('.goodsImg');
	// goodsZoom(bigImg);
	// 商品详细 选项卡
	gcxz.tab({
		dom:$('.goodsShow')
	});

	$('.bigImg').jqueryzoom({
		zoomType: 'standard',
		lens:true,
		yzoom:290,
		xzoom:270,
		preloadImages: false,
		alwaysOn:false
	});

	// 商品图片切换
	function goodsDeltailedImg (dom) {
		var dom = $(dom);
		var list = dom.find('.imgList>li');
		var bigImg = dom.find('.bigImg').find('img');
		list.on('mouseenter',function  () {
			var t = $(this);
			var link = t.attr('big-img');
			var maxLink = t.attr('max-img');
			t.addClass('active').siblings().removeClass('active');
			bigImg.attr('src',link).attr('jqimg',maxLink);
		});
	}
	
	var goods_id = $('#goods_id').val();
	$('input[name=comment]').click(function(){
		var value = $(this).val();
		var url = SITE_URL + '/index.php?app=goods&act=ajax_goods_comments';
		$.get(url, {
			'goods_id' : goods_id,
			'evaluation' : value
		}, function(data) {
			$('.comment').find('.table').remove();
			$('.comment').find('.page').remove();
			$('.comment').append(data);
		});
	});
	
	//初始选中全部评论
	$('input[name=comment]:first').click();
	
	//初始设置销售记录
	$.get( SITE_URL + '/index.php?app=goods&act=ajax_goods_sales_log', {'goods_id' : goods_id}, function(data) {
		$('.soldNote').empty();
		$('.soldNote').append(data);
	});
	
	// // 商品放大镜
	// function goodsZoom (dom) {
	// 	var img = dom.children('img');
	// 	var moveElement;
	// 	dom.on('mouseenter',function () {
	// 		var maxImgSrc = img.attr('max-img');
	// 		var maxImg = new Image;
	// 		maxImg.onload = function () {
	// 			moveElement = mouseRange(this.width,this.height,dom);
	// 		};
	// 		maxImg.src = maxImgSrc;
	// 	});
	// 	dom.on('mouseleave',function () {
	// 		moveElement.remove();
	// 	});
	// 	dom.on('mousemove',function (e) {
	// 		// moveElement.css({
	// 		// 	left:e.pageX,
	// 		// 	top:e.pageY
	// 		// });
	// 	});

	// 	function mouseRange (w,h,box) {
	// 		var range = $('<div></div>');
	// 		var rangeW = 270/w * 270;
	// 		var rangeH = 290/h * 290;
	// 		range.css({
	// 			position:'absolute',
	// 			left:0,
	// 			top:0,
	// 			border:'1px solid #ccc',
	// 			backgroundColor:'rgba(255,255,255,0.7)',
	// 			height:rangeH,
	// 			width:rangeW
	// 		});
	// 		range.appendTo(box);

	// 		return range;
	// 	}
	// }
}();

function ajaxCommentsData(obj){
	var url = $(obj).attr('link');
	$.get(url, null, function(data) {
		$('.comment').find('.table').remove();
		$('.comment').find('.page').remove();
		$('.comment').append(data);
	});
}

function ajaxSalesLogData(obj){
	var url = $(obj).attr('link');
	$.get(url, null, function(data) {
		$('.soldNote').empty();
		$('.soldNote').append(data);
	});
}
