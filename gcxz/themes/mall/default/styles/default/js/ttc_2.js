var showtimer;
var show = false;
var mopt = {
	tctimer : new Array(),
	liheight : 46
}
$(function() {
	$("svg circle").mousemove(function() {
		show = false;
		var rid = this.id.substr(2);
		clearTimeout(showtimer);
		showtimer = setTimeout("showtj(" + rid + ",11" + ")", 400);
	}).mouseover(function() {
		clearTimeout(showtimer);
		var rid = this.id.substr(2);
		hidetop(rid);
		active(rid, true);
	}).mouseout(
			function(e) {
				clearTimeout(showtimer);
				var rid = this.id.substr(2);
				if (!$(e.toElement).is(".native_map_2")
						&& $(e.toElement).parents(".native_map_2").length == 0) {
				showtop();
					// $("#tc_"+rid).hide();
					mopt.tctimer[rid] = setTimeout('$("#tc_' + rid + '").hide()',
							800);
				}
				active(rid, false);
			}).click(
			function() {
				if ($(this).data("is_show")) {
					var rid = this.id.substr(2);
					window.location.href = SITE_URL
							+ "/index.php?app=special&rid=" + rid;
				}
			});
	$(".native_names1 .native_namesli1").mouseenter(function() {
		var rid = this.id.substr(2);
		active(rid, false);
		hidetop(rid);
	}).mouseleave(function() {
		var rid = this.id.substr(2);
		active(rid, false);
		showtop();
	});
})
function showtj(rid, lc) {
	if(!$("#m_"+rid).data("is_show")){
		return;
	}
	show = true;
	clearTimeout(showtimer);
	var data = $("#tc_" + rid);
	if (data.length > 0) {
		data.show();
		hidetop();
		return;
	}
	$.get(SITE_URL + '/index.php?app=special&act=tj&rid=' + rid, null,
			function(data) {
				if (data != 0) {
					$("#tj_" + rid).append(data);
					$("#tc_" + rid).mouseleave(function(e) {
						// if (!$(e.toElement).is("#m_" + rid)) {
						$(this).hide();
						// mopt.tctimer = setTimeout('$("#tc_' + rid
						// + '").hide()', 800);
						// }
					}).mouseover(function() {
						clearTimeout(mopt.tctimer[rid]);
						hidetop();
					});
					if (show) {
						$("#tc_" + rid).show();
					}
				}
			});
}
function active(rid, flag) {
	var r = $("#r_" + rid);
	r.toggleClass("active");
	if (flag) {
		var order = r.attr("o");
		if (order > 4) {
			$(".native_names .native_names1").stop().animate({
				marginTop : (-(order - 4) * mopt.liheight) + "px"
			})
		}
	}
}
function showtop(){
	hidetop();
	$("div.tjsp:not(.not_active) .native_map_1").stop().show().animate({
		opacity : "1"
	}, 500);
}
function hidetop(rid) {
	if (rid) {
		$("div.tjsp:not(#tj_" + rid + ") .native_map_1").stop().animate({
			opacity : "0"
		}, 200, null, function() {
			$(this).hide();
		});
		$("#tj_" + rid + ".not_active .native_map_1").stop().show().animate({
			opacity : "1"
		}, 500);
	} else {
		$("div.tjsp .native_map_1").stop().animate({
			opacity : "0"
		}, 200, null, function() {
			$(this).hide();
		});
	}
}
$(function() {
	cbpBGSlideshow.init();
});
$(document).ready(function() {
	for ( var k in regions) {
		$("#m_" + regions[k].region_id).data("is_show", regions[k].if_show);
	}
	$("svg circle").each(function(){
		var $this=$(this);
		var rid=this.id.substr("2");
		if(!$this.data("is_show")){
			$("#container>div").append('<div id="tj_'+rid+'" class="tjsp not_active" style="display:none;"><div class="native_map_1 native_map_1_no" style="display:none;position: absolute; top:-112px; left:-6px;"></div></div>');
		}
	});
	
	$("div.tjsp").each(function() {
		var $this = $(this);
		var id = "#m_" + this.id.substr(3);
		var location = $(id).attr("lc").split(",");
		$this.css({
			position : "absolute",
			top : location[0] + "px",
			left : location[1] + "px"
		});
	}).show();
});