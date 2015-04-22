var artopt={
		is_load:false
}
$(function(){
	$(".leftsidebar_box dt").click(function(){
		var $this=$(this);
		$this.parent().siblings().find('.cl').removeClass("cl");
		$this.parent().siblings().find("dd").slideUp();
		$this.parent().find("dd").stop().slideToggle();
		$this.find("i").toggleClass("cl");
	});
	$(".leftsidebar_box dt:first").click();
	$(".leftsidebar_box dd:first a").click();
})

function switch_article(aid){
	if(artopt.is_load){
		return false;
	}
	show_loading();
	$.get(SITE_URL + '/index.php?app=article&act=view&aid='+aid,null,function(data){
		if(data!=0){
			hide_loading();
			$(".leftsidebar_right").html(data);
		}else{
			hide_loading();
			$(".leftsidebar_right").html("内容不存在");
		}
	});
}
function show_loading(){
	$(".leftsidebar_right").html('<img class="loading" src="'+SITE_URL+'/themes/mall/default/styles/default/img/zrz.gif">');
	artopt.is_load=true;
}
function hide_loading(){
	$(".leftsidebar_right").empty();
	artopt.is_load=false;
}