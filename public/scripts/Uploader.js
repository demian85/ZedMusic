function formatFileSize(bytes) {
	var measures = {
		0 : ["Bytes", 0],
		1 : ["KB", 0],
		2 : ["MB", 1],
		3 : ["GB", 2],
		4 : ["TB", 2]
	};
	var size = bytes;
	for (var i = 0; size >= 1024; i++) {
		size /= 1024;
	}
	size = Math.round(size, measures[i][1]);
	return size + " " + measures[i][0];
}

var Uploader = {
	BOUNDARY : '---------------------------1966284435497298061834782736',
	_files : [],
	_loadedFiles : 0,
	_formData : typeof FormData != 'undefined' ? new FormData() : null,
	_parsedFiles : [],
	_events : {
		'load' : []
	},
	uploadDir : '',

	_reset : function() {
		this._files = [];
		this._parsedFiles = [];
		this._loadedFiles = 0;
		this._formData = null;
		j('#upload-progressbar').progressbar('value', 0).hide();
		j('#upload-file-list').html('');
	},

	_notifyLoaded : function() {
		this._events.load.forEach(function(v) {
			v();
		});
	},

	on : function(evt, cb) {
		this._events[evt].push(cb);
	},

	getPostData : function() {
		var me = this
		var rn = "\r\n";
		var req = '';
		me._parsedFiles.forEach(function(v, k) {
			req += "--" + me.BOUNDARY + rn + "Content-Disposition: form-data; name=\"files[]\"";
			req += '; filename="' + v.name + '"' + rn + 'Content-type: ' + v.type;
			req += rn + rn + v.content + rn;
		});
		req += "--" + me.BOUNDARY + '--';
		return req;
	},

	addFiles : function(files) {
		var me = this;

		var processFile = function(file) {
			var reader = new FileReader();
			reader.onloadend = function(e) {
				var id = me._loadedFiles++;
				if (me._formData) {
					me._formData.append('files[]', file);
				}
				else {
					me._parsedFiles.push({
						name : file.name,
						type : file.type,
						size : file.size,
						content : e.target.result
					});
				}
				var html = '<li id="upf-' + id + '" class="ui-state-default">'
					+ '<span class="name" title="' + file.name + '">' + file.name + '</span><span>' + (file.type || 'text/plain') + '</span>'
					+ '<span>' + formatFileSize(file.size) + '</span>'
					+ '</li>';
				j('#upload-file-list').append(html);
			};
			reader.readAsBinaryString(file);
		};

		for (var i = 0; i < files.length; i++) {
			if (files[i].size > 30*1024*1024*2) {
				alert(files[i].name + ': ' + msg.file_too_big);
				return;
			}
			me._files.push(files[i]);
			processFile(files[i]);
		}
	},

	send : function() {
		var me = this;

		if (!me._files.length) {
			alert(msg._select_files);
			return;
		}

		if (me._loadedFiles != me._files.length) {
			alert(msg.files_not_ready);
			return;
		}

		j('#upload-progressbar').show();

		var xhr = new XMLHttpRequest();

		xhr.onload = function(e) {};
		xhr.upload.addEventListener('progress', function(e) {
			if (e.lengthComputable) {
				var perc = Math.round((e.loaded * 100) / e.total);
				j('#upload-progressbar').progressbar('value', perc);
			}
		}, false);
		xhr.upload.addEventListener('load', function(e) {
			me._reset();
			setTimeout(function() {
				j('#upload-dialog').dialog('close');
			}, 500);
			me._notifyLoaded();
		}, false);
		xhr.upload.addEventListener('error', function(e) {
			me._reset();
		}, false);

		var uri = '/' + LANG + '/ctrl/upload?dir=' + encodeURIComponent(FileBrowser.getSelectedPath());

		if (!me._formData && xhr.sendAsBinary) {
			xhr.open("POST", uri);
			xhr.setRequestHeader("Content-Type", "multipart/form-data; boundary=" + me.BOUNDARY);
			xhr.sendAsBinary(me.getPostData());
		}
		else if (me._formData) {
			xhr.open("POST", uri);
			xhr.send(me._formData);
		}
		else {
			alert(msg.upload_not_supported);
		}
	},

	cancel : function() {
		this._reset();
	}
};