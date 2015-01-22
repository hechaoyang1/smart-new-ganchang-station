function reLocate() {
	var condition='';
	if($('#keyword').length==1){
		condition += "&keyword=" + $('#keyword').val();
	}
	if($('#goodsId').length==1 && $('#goodsId').val()!=''){
		condition += "&goodsId=" + $('#goodsId').val();
	}
	if($('#cate_id').length==1 && $('#cate_id').val()!=''){
		condition += "&cate_id=" + $('#cate_id').val();
	}
	// 获取地域条件
	$area = $('dd.area');
	if ($area.length == 1) {
		condition += '&region_id=' + $area.attr('attr_id');
	}
	var order = 'asc';
	if ($('.title.clr a.foucs').text() == '新品') {
		condition += '&timeOrder=';
		if ($('.title.clr a.foucs').attr("class").indexOf("down") >= 0) {
			order = 'desc';
		}
		condition += order;
	} else if ($('.title.clr a.foucs').text() == '价格') {
		condition += '&priceOrder=';
		if ($('.title.clr a.foucs').attr("class").indexOf("down") >= 0) {
			order = 'desc';
		}
		condition += order;
	}

	// 价格过滤
	condition += '&price=' + $('input[name="minPrice"]').val() + '-'
			+ $('input[name="maxPrice"]').val();

	window.location.href = 'index.php?app=search' + condition;
}

function RetSelecteds() {
	var result = "";
	$("#filter a[class='seled']").each(function() {
		result += $(this).html() + "\n";
	});
	return result;
}

$(function() {
	// 选中filter下的所有a标签，为其添加hover方法，该方法有两个参数，分别是鼠标移上和移开所执行的函数。
	$("#filter a").hover(function() {
		$(this).addClass("seling");
	}, function() {
		$(this).removeClass("seling");
	});

	$('dl.term dd').click(function() {
		$(this).remove();
		reLocate();
	});
	// 选中filter下所有的dt标签，并且为dt标签后面的第一个dd标签下的a标签添加样式seled。
	// $("#filter dt+dd a").attr("class", "seled");
	/*
	 * 注意：这儿应该是设置(attr)样式，而不是添加样式(addClass)，不然后面通过$("#filter
	 * a[class='seled']")访问不到class样式为seled的a标签。
	 */

	// 为filter下的所有a标签添加单击事件
	$("#filter a").click(
			function() {
				var flag = $(this).parents("dl").attr("class");
				$(this).parents("dl").children("dd").each(function() {
					$('a', this).removeClass("seled");
				});
				$(this).attr("class", "seled");
				if (flag == "dropdown") {
					$("dl.term dd.dropdown-menu").remove();
				}
				$("dl.term dd." + flag).remove();
				$(
						"<dd class='" + flag + "' attr_id='"
								+ $(this).attr('attr_id') + "'><span>"
								+ $(this).html() + "</dd></span>").appendTo(
						'dl.term').bind('click', function(event) {
					$(this).remove();
					reLocate();
				});
				reLocate();
			});

	// 排序方式操作
	$(".title a").click(function() {
		if ($(this).text() == '价格' || $(this).text() == '新品') {
			if ($(this).attr("class").indexOf("up") == 0) {
				$(this).removeClass("up").addClass("down");
			} else {
				$(this).removeClass("down").addClass("up");
			}
		}

		$(".title a").removeClass("foucs");
		$(this).addClass("foucs");
		reLocate();
	});

	$('#changePrice').click(
			function() {
				$min = $('input[name="minPrice"]');
				$max = $('input[name="maxPrice"]');
				if ($min.val() != $min.attr('oldValue')
						|| $max.val() != $max.attr('oldValue')) {
					reLocate();
				}
			});

	$('input[name="minPrice"]').mouseout(function() {
		var patrn = /^[0-9]+(.[0-9]{1,2})?$/;
		if (!patrn.test($(this).val())) {
			$(this).val('');
		}
	});
	$('input[name="maxPrice"]').mouseout(function() {
		var patrn = /^[0-9]+(.[0-9]{1,2})?$/;
		if (!patrn.test($(this).val())) {
			$(this).val('');
		}
	});

});
