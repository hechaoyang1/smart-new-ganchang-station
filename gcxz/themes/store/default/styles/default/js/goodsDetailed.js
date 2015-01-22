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
	
	$('input[name=comment]').click(function(){
		var value = $(this).val();
		var goods_id = $('#goods_id').val();
		 var url = SITE_URL + '/index.php?app=goods&act=getComment';
		    $.get(url, {'goods_id':goods_id,'eval':value}, function(data){
		    	 if (data.done)
		         {
		    		 //清除以前数据
		    		 var comments = data.retval.comments;
		    		 $('.table tbody tr').remove();
		    		 for(var i in comments){
		    			 $tr = $('<tr> <td> <p></p> </td> <td class="mid center">好评</td> <td class="center">落花<div>2015-03-03 10:32:41</div> </td> </tr>');
		    			 $tr.find('p').text(comments[i]['comment']);
		    			 var _eval = '';
		    			 if(comments[i]['evaluation'] == 3){
		    				 _eval = '好评';
		    			 }else if(comments[i]['evaluation'] == 2){
		    				 _eval = '好评';
		    			 }else{
		    				 _eval = '差评';
		    			 }
		    			 $tr.find('td.mid').text(_eval);
		    			 $tr.find('td:last').html(comments[i]['buyer_name']+'<div>'+comments[i]['time']+'</div>');
		    			 $('.table tbody').append($tr);
		    		 }
		         }else{
		        	 alert('获取评论异常,请重新再试');
		         }
		    },'json');
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