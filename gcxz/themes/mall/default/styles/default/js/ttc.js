var showtimer;
$(function() {
	$("div.tjsp").each(function() {
		var $this = $(this);
		var id = "#m_" + this.id.substr(3);
		var location = $(id).attr("lc").split(",");
		$this.css({
			position : "absolute",
			top : location[0] + "px",
			left : location[1] + "px"
		}).show();
	});
	$("svg polygon.fil").mousemove(function() {
		clearTimeout(showtimer);
		var rid = this.id.substr(2);
		showtimer = setTimeout("showtj(" + rid + ",11" + ")", 400);
	}).mouseover(function() {
		clearTimeout(showtimer);
		$(this).css("fill","#f18101");
		$("div.tjsp .native_map_1").stop().animate({
			opacity : "0"
		}, 200,null,function(){
			$(this).hide();
		});

	}).mouseout(function(e) {
		clearTimeout(showtimer);
		$(this).css("fill","#dddddd");
		var rid = this.id.substr(2);
		if(!$(e.toElement).is("#tc_"+rid)&&!$(e.toElement).is(".native_map_1")){
			$("div.tjsp .native_map_1").stop().show().animate({
				opacity : "1"
			}, 500,null);
			$("#tc_"+rid).hide();
		}
	}).click(function(){
		var rid = this.id.substr(2);
		window.location.href=SITE_URL + "/index.php?app=special&rid="+rid;
	});
	$(".native_names1 .native_namesli1").mouseenter(function(){
		var rid=this.id.substr(2);
		$("#m_"+rid).css("fill","#f18101");
	}).mouseleave(function(){
		var rid=this.id.substr(2);
		$("#m_"+rid).css("fill","#dddddd");
	})
})
function showtj(rid, lc) {
	clearTimeout(showtimer);
	var data = $("#tc_" + rid);
	if (data.length > 0) {
		data.show();
		return;
	}
	$.get(SITE_URL + '/index.php?app=special&act=tj&rid=' + rid, null,
			function(data) {
				if (data != 0) {
					$("#tj_" + rid).append(data);
					$("#tc_" + rid).mouseleave(function(e){
						if(!$(e.toElement).is("#m_" + rid)){
							$(this).hide();
						}
					}).show();
				}
			});
}
$(function() {
	cbpBGSlideshow.init();
});

$(document).ready(function() {
	if($(".native_names ul").length>1){
		$(".native_names0").click(function() {
			$("#native_names").toggleClass("native_names-js");
		});
	}
});