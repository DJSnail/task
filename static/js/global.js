function onOrderScreen(curEle, tarEle) {
	var orderState = $(curEle).attr('data-state'),
		orderType = $('#'+tarEle).val();
	if(orderState === 'normal') {
		// 正常订单
		console.log('未删除='+orderType)
	} else {
		// 已删除订单
		console.log('已删除='+orderType)
	}	
}
function onCollectScreen(curEle, tarEle1, tarEle2) {
	var collectState = $(curEle).attr('data-state'),
		collectType = $('#'+tarEle1).val(),
		collectChannel = $('#'+tarEle2).val();
	if(collectState === 'normal') {
		// 正常采集
		console.log('未删除='+collectType)
	} else {
		// 已删除采集
		console.log('已删除='+collectType)
	}	
}
function tips(content) {
    alert(content);
	/*content = content || '';
	var htmlStr = '<div class="snail_tips"><div class="snail_tips_panel">';
	htmlStr += '<div class="snail_tips_head"><h2>提示</h2><span class="snail_tips_close">&times;</span></div>';
	htmlStr += '<div class="snail_tips_body"><p>'+ content +'</p></div>';
	htmlStr += '<div class="snail_tips_foot"><button type="type" data-type="close" class="snail_tips_closeBtn">关闭</button></div>';
	htmlStr += '</div></div>';
	$('body').append(htmlStr);*/
}
$(function() {
	var pageNum = 1, pageTotal = $('#page .item[data-tar=item]').length;
	// 翻页
	$('#page .item').on('click', function() {
		var targetEle = $(this).attr('data-tar');
		if(targetEle === 'item') {
			var targetNum = parseInt($(this).attr('data-num'));
			if(targetNum === pageNum) {return}
			pageNum = targetNum;
			$(this).addClass('active').siblings('.item').removeClass('active');
		} else {
			if(targetEle === 'prev') {
				if(pageNum <= 1) {return}
				pageNum -= 1;
			} else {
				if(pageNum >= pageTotal) {return}
				pageNum += 1;
			}
			$(this).siblings('.item').removeClass('active');
			$(this).siblings('.item[data-num='+pageNum+']').addClass('active');
		}
		console.log(pageNum);
	});
	// 全选
	$('#select_all').click(function() {
		var isChecked = $(this).prop('checked');
		$('#tbody').find('.select_btn').prop('checked', function(i, val) {
			return isChecked ? true : false;
		});
	});
})