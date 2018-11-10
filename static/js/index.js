// 显示 or 隐藏子目录
function onClickTreeBranch(obj) {
	var nextEle = $(obj).next('.main_tree_branch'),
		isHidden = nextEle.is(':hidden');
	if(isHidden) {
		nextEle.show();
		$(obj).addClass('active');
	} else {
		nextEle.hide();
		$(obj).removeClass('active');
	}
}
// 打开目录页面
function openWeb(obj) {
	var url = $(obj).attr('data-href');
	$('#iframe').prop('src', url);
}
$(function() {
	var isShowUserInfo = false
	// 显示 or 隐藏用户信息
	$('#user').click(function() {
		if(isShowUserInfo) {
			$('#user_info').hide();
			$('#user_arrow').removeClass('active');
		} else {
			$('#user_info').show();
			$('#user_arrow').addClass('active');
		}
		isShowUserInfo = isShowUserInfo ? false : true;
	});
})