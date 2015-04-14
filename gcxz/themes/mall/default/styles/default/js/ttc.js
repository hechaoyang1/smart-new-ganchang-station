var showtimer;
var show = false;
var mopt = {
	tctimer : null
}
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
		show = false;
		var rid = this.id.substr(2);
		clearTimeout(showtimer);
		showtimer = setTimeout("showtj(" + rid + ",11" + ")", 400);
	}).mouseover(function() {
		clearTimeout(showtimer);
		var rid = this.id.substr(2);
		$("div.tjsp:not(#tj_"+rid+") .native_map_1").stop().animate({
			opacity : "0"
		}, 200, null, function() {
			$(this).hide();
		});
		showMap(this);
		$("#r_" + rid).toggleClass("active");
	}).mouseout(
			function(e) {
				clearTimeout(showtimer);
				var rid = this.id.substr(2);
				hideMap(this);
				if (!$(e.toElement).is(".native_map_2")
						&&$(e.toElement).parents(".native_map_2").length==0&& !$(e.toElement).is(".native_map_1")) {
					$("div.tjsp .native_map_1").stop().show().animate({
						opacity : "1"
					}, 500, null);
					// $("#tc_"+rid).hide();
					mopt.tctimer = setTimeout('$("#tc_' + rid + '").hide()',
							800);
				}
				$("#r_" + rid).toggleClass("active");
			}).click(function() {
		window.location.href = SITE_URL + "/index.php?app=special&rid=" + rid;
	});
	$(".native_names1 .native_namesli1").mouseenter(function() {
		var rid = this.id.substr(2);
		$("#m_" + rid).css("fill", "#f18101");
		$(this).toggleClass("active");
	}).mouseleave(function() {
		var rid = this.id.substr(2);
		$("#m_" + rid).css("fill", "#dddddd");
		$(this).toggleClass("active");
	});
})
function showtj(rid, lc) {
	show = true;
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
					$("#tc_" + rid).mouseleave(
							function(e) {
//								if (!$(e.toElement).is("#m_" + rid)) {
									$(this).hide();
//									mopt.tctimer = setTimeout('$("#tc_' + rid
//											+ '").hide()', 800);
//								}
							}).mouseover(function() {
								clearTimeout(mopt.tctimer);
								$("#tj_"+rid+" .native_map_1").stop().animate({
									opacity : "0"
								}, 200, null, function() {
									$(this).hide();
								});
							});
					if (show) {
						$("#tc_" + rid).show();
					}
				}
			});
}
function showMap(obj) {
	$(obj).css({
		fill : "#f18101",
		opacity : "1"
	});
}
function hideMap(obj) {
	$(obj).css("opacity", "0");
}
$(function() {
	cbpBGSlideshow.init();
});
$(document).ready(function() {
	if ($(".native_names ul").length > 1) {
		$(".native_names0").click(function() {
			$("#native_names").toggleClass("native_names-js");
		});
	}
});