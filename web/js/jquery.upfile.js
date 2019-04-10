/**** 上传文件
/***jquery.upfile.js v1.0 
/***by windy (c)2017-05 chemailbox@163.com
****/
(function ($) {
	function file(fileObj, options){
		this.opts = $.extend({
			eventType : ["change", "#id"],		//改变就上传类型【change, click】#id点击对象
			url       : "/upload/UploadAction",  		// 上传文件的路径
			fileType  : "jpg|png|js|exe",		// 上传文件的类型
			fileSize  : 1,                			// 上传文件的大小
			size      : "",								//上传文件数量。默认不限制
			fileName  : "fileList",
			onProgress: function(evt){},				//进度条回调函数
			onSelect: function(selectFiles){},    		// 选择文件的回调方法
			onSuccess: function(file, response){},      // 文件上传成功的回调方法
			onFailure: function(file, response){},      // 文件上传失败的回调方法
			onSelectFail: function(errtext){}           	// 选择文件回调方法
		}, options)
		this.nameObj = []; //文件的大小数组
		this.flieList = []; //选择的文件文件列表
		this.index = 1;		//id索引保证唯一性
		var _file = this;
		this.fileObj = $(fileObj);
		this.fileObj.change(function(e){
			//var files = e.target.files || e.dataTransfer.files;
			var tmpFiles = e.target || e.dataTransfer;
			_file.changeFile(tmpFiles);
		})
		this.resetFile = function(name){
			var index = this.nameObj.indexOf(name);
			if(index > -1){
				this.nameObj.splice(index, 1);
			}
		}
		this.resetUpFile = function(name){
			//console.log(this.flieList)
			this.flieList = [];
		}
	};

	file.prototype.fileType =  function(name){//通过文件名，返回文件的后缀名
		var nameArr = name.split(".");
		return nameArr[nameArr.length-1].toLowerCase();
	};

	file.prototype.createObjectURL = function (blob){
		return window[window.webkitURL ? 'webkitURL' : 'URL']['createObjectURL'](blob);
	};

	file.prototype.upfile = function(index, data){
		if(index == data.length-1){
			this.opts.onSelect(this.flieList);//选择回调函数
			if(this.opts.eventType[0] == "change"){
				this.upLoadFiles(this.flieList);//上传文件
			}else if(this.opts.eventType[0] == "click"){
				var self = this;
				$(this.opts.eventType[1]).click(function(){
					self.upLoadFiles(self.flieList);//上传文件
					//$(this).off();
				})
			}
		}
	};

	file.prototype.changeFile = function(tmpFiles){
		var data = tmpFiles.files;	//图片数据
		//如果没有文件
		if( data.length < 1 || (this.opts.size && data.length > this.opts.size)){
			this.opts.onSelectFail("文件不能为空或文件个数不能超过" + this.opts.size + "个");
			return false;
		}
		if(!this.fileObj.attr("multiple")){
			this.nameObj = []; //重置
			this.flieList = []; //重置
		}
		for( var i=0;i<data.length;i++ ){
			var name = data[i].name;	//文件名
			var size = data[i].size;	//文件大小
			var type = this.fileType(name);	//文件类型，获取的是文件的后缀

			var reg = new RegExp("(.*)\\.("+this.opts.fileType+")$", "i");
            if(!reg.test(name)){
            	//console.log('“'+ name +'”格式不支持');
				this.opts.onSelectFail('“'+ name +'”格式不支持');
				this.nameObj = []; //重置
				this.flieList = []; //重置
				this.fileObj.val("");
				return
            }

			if(size > this.opts.fileSize*1024*1024 || size == 0){
				//console.log('“'+ name +'”超过了'+this.opts.fileSize+'M，不能上传');
				this.opts.onSelectFail('“'+ name +'”超过了'+this.opts.fileSize+'M，不能上传');
				this.nameObj = []; //重置
				this.flieList = []; //重置
				this.fileObj.val("");
				return
			}

			//把名称放到一个数组中，然后再去比较，就认为重复了
			if(this.nameObj.indexOf(name) != -1){
				this.fileObj.val("");
				return;
			}

			var imgUrl = this.createObjectURL(data[i]);
			this.index++;
			var json = {
				file: data[i],
				id: (new Date()).getTime() + this.index,
				name: name,
				size: size,
				type: type,
				path: imgUrl
			}

			this.flieList.push(json);
			this.nameObj.push(name);//把这个文件的大小放进数组中
			
			this.upfile(i, data);
		}
	};

	file.prototype.upLoadFiles = function(data){
		for(var i=0; i<data.length; i++){
			var formData = new FormData();
			//formData.append(this.opts.fileName + "" + i, data[i].file);
			formData.append(this.opts.fileName, data[i].file);
			this.loadFile(formData, data[i]);
		}
		this.fileObj.val("");
		this.resetUpFile();
	};

	file.prototype.loadFile = function(formData, file){
		var opts = this.opts;
		var _this = this;
		(function(_file){
			$.ajax({
				type: "POST",
				url: opts.url,
				data: formData,			//这里上传的数据使用了formData 对象
				processData : false, 	//必须false才会自动加上正确的Content-Type
				contentType : false,
				dataType:"json",					
				//增加 progress 事件绑定，然后再返回交给ajax使用
				xhr: function(){
					var xhr = $.ajaxSettings.xhr();
					if(opts.onProgress && xhr.upload) {
						//function () { uploadProgress(event,t) }
						xhr.upload.addEventListener("progress" , function(event){
							opts.onProgress(event.total, event.loaded, _file);
						}, false);　
						return xhr;
					}
				},
				success: function(re){
					opts.onSuccess(re);
					_this.resetUpFile(_file.name);
				},
				error: function(re){
					_this.resetUpFile(_file.name);
					opts.onFailure(re);		//上传失败后回调
				} 
			});
		})(file)
	};

    $.fn.uploadFile = function(options){
        return (new file(this, options));
    };
})(jQuery);
