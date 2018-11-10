//文件图片上传
//haodaquan
//2016-11-28

/**
 * [plupFile 上传文件]
 * @param  {String} file        [文件地址]
 * @param  {String} btn         [按钮]
 * @param  {String} inputName   [file path and file name and alert]
 * @param  {String} maxFileSize [文件最大]
 * file='',btn='upload_file',inputName='upload_file',maxFileSize='2mb'
 * @return {[type]}             [description]
 */

function plupFile(file, btn, maxFileSize, listName, ext) {
    if(!ext) {
        ext = 'xls,xlsx,doc,docx,ppt,pptx,zip,rar,tar,pdf';
    }
    var snailLoading = '';
    var url = "/public/upload/file/?size="+maxFileSize+"&file="+file;
    var uploader = new plupload.Uploader({ //创建实例的构造方法 
        runtimes: 'html5,flash,silverlight,html4',  //上传插件初始化选用那种方式的优先级顺序 
        browse_button: btn, // 上传按钮 
        url: url, //远程上传地址 
        flash_swf_url: '/static/admin/Plugins/plupload-2.1.2/js/Moxie.swf',  //flash文件地址 
        silverlight_xap_url: '/static/admin/Plugins/plupload-2.1.2/js/Moxie.xap',  //silverlight文件地址 
        filters: { 
            max_file_size: maxFileSize, //最大上传文件大小（格式100b, 10kb, 10mb, 1gb） 
            mime_types: [{ //允许文件上传类型 
                title: "files", 
                extensions: ext
            }],
        }, 
        multi_selection: false, //true:ctrl多文件上传, false 单文件上传 
        init: { 
            FilesAdded: function(up, files) { //文件上传前 
                uploader.start();
                snailLoading = '<div class="snail_loading" style="width:100%;height:100%;background:url(/static/image/loading.gif) no-repeat 50% 50%;position:fixed;left:0px;top:0px;z-index:9999;"></div>';
                $('body').append(snailLoading);
                //uploader.destroy();
            }, 
            UploadProgress: function(up, file) { //上传中，显示进度条 
                // $("#" + file.id).find('.bar').css({ 
                //     "width": file.percent + "%"  
                // }).find(".percent").text(file.percent + "%"); 
                // 
            }, 
            FileUploaded: function(up, file, info) { //文件上传成功的时候触发
                $('body').find('.snail_loading').remove();
                var data = JSON.parse(info.response);
                if(data.error !=0 ) {
                    tips(data.error);
                    return false;
                } else {
                    var htmlStr = '<li class="clearfix" data-id="'+ data.id +'" data-path="'+ data.path +'" data-name="'+ data.name +'">';
                        htmlStr += '<h6>'+ data.name +'</h6><span>删除</span></li>';
                    $('#'+listName).append(htmlStr);
                }
            }, 
            Error: function(up, err) { 
                //上传出错的时候触发 
                $('body').find('.snail_loading').remove();
                console.error(err);
                tips(err.message); 
            } 
        } 
    }); 
	uploader.init();
}

/**
 * [plupImage 单图上传js]
 * @Author haodaquan
 * @Date   2016-04-12
 * @param  {String}   file        [文件名]
 * @param  {String}   width       [图片宽度]
 * @param  {String}   height      [图片高度]
 * @param  {String}   btn         [图片按钮]
 * @param  {String}   imgShowId   [显示图片的id]
 * @param  {String}   inputName   [提交input按钮]
 * @param  {String}   maxFileSize [最大上传大小]
 * @param  {String}   ext         [文件扩展名，]
 * @return {[type]}               [void]
 */
function plupImage(file, width, height, btn, maxFileSize, tarName, ext) {
    if(!ext) {
        ext = 'jpg,png,gif,jpeg';
    }
    var snailLoading = '';
    var url = "/public/upload/image?w="+width+"&h="+height+"&size="+maxFileSize+"&file="+file;
    var uploader = new plupload.Uploader({ //创建实例的构造方法 
        runtimes: 'html5,flash,silverlight,html4', //上传插件初始化选用那种方式的优先级顺序 
        browse_button: btn, // 上传按钮 
        url: url, //远程上传地址 
        flash_swf_url: '/static/admin/Plugins/plupload-2.1.2/js/Moxie.swf', //flash文件地址 
        silverlight_xap_url: '/static/admin/Plugins/plupload-2.1.2/js/Moxie.xap', //silverlight文件地址 
        filters: { 
            max_file_size: maxFileSize, //最大上传文件大小（格式100b, 10kb, 10mb, 1gb） 
            mime_types: [{ //允许文件上传类型 
                title: "files", 
                extensions: ext 
            }],
        }, 
        multi_selection: false, //true:ctrl多文件上传, false 单文件上传 
        init: { 
            FilesAdded: function(up, files) { //文件上传前 
                uploader.start();
                snailLoading = '<div class="snail_loading" style="width:100%;height:100%;background:rgba(0,0,0,0.4) url(/static/image/loading.gif) no-repeat 50% 50%;position:fixed;left:0px;top:0px;z-index:9999;"></div>';
                $('body').append(snailLoading);
                //uploader.destroy();
            }, 
            UploadProgress: function(up, file) { //上传中，显示进度条 
                // $("#" + file.id).find('.bar').css({ 
                //     "width": file.percent + "%"  
                // }).find(".percent").text(file.percent + "%"); 
            }, 
            FileUploaded: function(up, file, info) { //文件上传成功的时候触发
                $('body').find('.snail_loading').remove();
                var data = JSON.parse(info.response);
                if(data.error != 0) {
                    tips(data.error);
                    return false;
                } else {
                    var htmlStr = '<li class="clearfix" data-pic="'+ data.pic +'">';
                        htmlStr += '<div class="pic"><img src="'+ data.pic +'"></div><span>删除</span></li>';
                    $('#'+tarName).html(htmlStr);
                }
            }, 
            Error: function(up, err) { 
                //上传出错的时候触发 
                $('body').find('.snail_loading').remove();
                console.error(err.error);
                tips(err.message); 
            } 
        } 
    }); 
    uploader.init();
}