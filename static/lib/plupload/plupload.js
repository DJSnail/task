/**
 * [plupFile 上传文件]
 * @param  {String} button         [按钮]
 * @param  {String} maxSize        [文件最大]
 */
function pluploadFile(button, maxSize) {
	var url = '';
	var upLoader = new plupload.Uploader({
		url: url, // 远程上传地址
		runtimes: 'html5,flash,silverlight,html4', // 上传方式
		browse_button: button, // 上传按钮
		flash_swf_url: './Moxie.swf', // flash上传时需要
		silverlight_xap_url: 'Moxie.xap', // silverlight上传时需要
		multi_selection: true, // 上传多个
		filters: {
			max_file_size: maxSize, //上传文件最大值（100b, 10kb, 10mb, 1gb）
			prevent_duplicates: false, // 是否允许重复上传，false（允许）/ true（不允许）
			mime_types: [
				{title : "files", extensions : "jpg,gif,png"} // 允许上传图片
			]
		},
		init: {
            // 当文件添加到上传队列后触发
            FilesAdded: function(uploader, files) {
                upLoader.start();
            },
            // 会在文件上传过程中不断触发
			UploadProgress: function(uploader, file) {

            },
            // 当队列中的某一个文件上传完成后触发
            FileUploaded: function(uploader, file, responseObject) {

            },
            // 当发生错误时触发
            Error: function(uploader, errObject) {

            }
		}
	});
	upLoader.init();
}