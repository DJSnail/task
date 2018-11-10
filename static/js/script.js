var login_timer = null;
var login_count = 0;

function userLogin() {
    if(login_count >= 60){
        alert("登录过期, 请重新登录");
        $('#login_mask').hide();
        login_count = 0;
        clearInterval(login_timer);
        return;
    }
    $.ajax({
        type: 'get',
        url: "/user/user_login",
        data: {},
        dataType: 'json',
        success: function(data) {
            if(data == -1) {
                $('#login_mask').hide();
                login_count = 0;
                clearInterval(login_timer);
            }
            if(data && data['nickname']) {
                login_count = 0;
                clearInterval(login_timer);
                var image = data["image"], nickName = data['nickname'];
                var html = '<span id="dorpdown" class="after" style="background-image:url('+ image +');"></span>';
                $('.login').html(html);
                $('#login_mask').hide();
            }
            login_count ++;
        }
    });
}

$(function() {
    var oBody = $('body'), oDorpdownLayer = $('#dorpdown_layer');
    oBody.on('click', '*', function(event) {event.stopPropagation();});
    // 关闭提示框
    oBody.on('click', '.snail_tips_close, .snail_tips_closeBtn', function() {
        $(this).closest('.snail_tips').remove();
    });
    oBody.on('click', '.snail_tips2', function() {
        $(this).remove();
    });
    // 关闭模态框
    $('.snail_module').find('.close').click(function () {
        $(this).closest('.snail_module').attr('data-status', 'hide');
    });
    // 个人中心
    oBody.on('click', '#dorpdown, #dorpdown_layer a', function() {
        var status = oDorpdownLayer.attr('data-type');
        if(status == 'hide') {
            oDorpdownLayer.attr('data-type', 'show');
        } else {
            oDorpdownLayer.attr('data-type', 'hide');
        }
    });
    // 登录操作
    oBody.on('click', '#login', function() {
        $.ajax({
            type: 'get',
            url: "/sns/get_wx_pic",
            data: {},
            dataType: 'json',
            success: function(data) {
                if(data){
                    var url = data['url'];
                    var htmlStr = '<div id="login_mask" class="login_mask">' +
                        '<div class="login_panel"><div class="pic"><img src="' + url +'"></div><h2><span>微信</span>扫描直接注册登录</h2></div></div>';
                    oBody.append(htmlStr);
                    login_timer = setInterval(userLogin, 1000);
                }
            }
        });
    });
    oBody.on('click', '#login_mask', function() {
        login_count = 0;
        clearInterval(login_timer);
        $(this).remove();
    });
    // 分类筛选
    $('.list_group .more_btn').click(function() {
        var state = $(this).attr('data-state'),
            nextAll = $(this).prev('ul').find('li').eq(4).nextAll('li');
        if(state === 'hide') {
            nextAll.show();
            $(this).html('收起&gt;&gt;').attr('data-state', 'show');
        } else {
            nextAll.hide();
            $(this).html('更多&gt;&gt;').attr('data-state', 'hide');
        }
    });
    // 订阅推送
    var moduleObj = null, moduleBody = null, moduleNewkey = null, modulePastkey = null, keyCount = 0;
    $('#subscribe_btn').click(function() {
        var uid = $("#user_id").val();
        if(!uid){
           tips("请先注册登录!");return;
        }
        moduleObj = $('#subscribe_module');
        moduleObj.attr('data-status', 'show');
        moduleBody = moduleObj.find('.snail_module_body');
        moduleNewkey = moduleObj.find('.new_key');
        modulePastkey = moduleObj.find('.past_key');
        var newKeycount = moduleNewkey.children('li').length,
            pastKeycount = modulePastkey.children('li').length;
        keyCount = newKeycount + pastKeycount;
    });
    $('#subscribe_module').on('click', '.add', function() {
        if(keyCount < 6) {
            addKey(moduleNewkey);
            keyCount++;
        } else {
            tips('不能添加更多了');
        }
    });
    $('#subscribe_module').on('click', '.new_key .del, .past_key .del', function() {
        $(this).closest('li').remove();
        keyCount--;
    });
    $('#subscribe_module .next').click(function() {
        var oThis = $(this), keyGroup = [];
        moduleNewkey.find('input').each(function() {
            var keyVal = $.trim($(this).val());
            if(keyVal) {
                keyGroup.push(keyVal);
            }
        });
        modulePastkey.find('p').each(function() {
            var keyVal = $(this).attr('title');
            if(keyVal) {
               keyGroup.push(keyVal); 
            }
        });
        var type = $("#subscribe_type").val();
        if(!type){
            return;
        }
        $.ajax({
            type: 'post',
            url: '/sns/show_qrcode',
            data: {
                subscribe: keyGroup,
                type: type
            },
            //dataType: 'json',
            success: function(data) {
                oThis.hide().siblings('button.prev').show();
                moduleObj.find('.step_one').hide();
                moduleObj.find('.step_two').show();
                moduleObj.find('.QRcode').html(data);
                var key_word ='全部更新';
                if(keyGroup.length > 0){
                    key_word = '';
                    for(var i = 0;i < keyGroup.length;i++){
                        if(i == keyGroup.length - 1){
                            key_word += keyGroup[i];
                        }else{
                            key_word += keyGroup[i] + " , ";
                        }
                    };
                }
                $('#sub_word').html(key_word);
            },
            error: function() {

            }
        });
    });
    $('#subscribe_module .prev').click(function() {
        $(this).hide().siblings('button.next').show();
        moduleObj.find('.step_two').hide();
        moduleObj.find('.step_one').show();
    });
});
// 提示框
function tips(content) {
    content = content || '';
    var htmlStr = '<div class="snail_tips"><div class="snail_tips_panel">';
            htmlStr += '<div class="snail_tips_head"><h2>提示</h2><span class="snail_tips_close">&times;</span></div>';
            htmlStr += '<div class="snail_tips_body"><p>'+ content +'</p></div>';
            htmlStr += '<div class="snail_tips_foot"><button type="type" data-type="close" class="snail_tips_closeBtn">关闭</button></div>';
            htmlStr += '</div></div>';
    $('body').append(htmlStr);
}
function tips2() {
    var htmlStr = '<div class="snail_tips snail_tips2"><div class="snail_tips_panel snail_tips_panel2">';
            htmlStr += '<span class="icon"></span><p>您的需求已提交</p>';
            htmlStr += '<p class="apply">请到<a href="/user/order_list">我的申请</a>中查看最新进展</p>';
            htmlStr += '<div class="entry clearfix"><p><a href="/">返回首页</a></p>';
            htmlStr += '<p><a class="go_on" href="javascript:location.reload();">继续申请</a></p>';
            htmlStr += '</div></div></div>';
    $('body').append(htmlStr);
}
// 添加关键字
function addKey(obj) {
    var htmlStr = '<li class="clearfix">';
            htmlStr += '<div class="key_input"><input type="text" placeholder="请输入关键字"></div>';
            htmlStr += '<span class="add">+</span><span class="del">&minus;</span>';
        htmlStr += '</li>';
    obj.append(htmlStr);
}














