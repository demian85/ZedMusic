var FileBrowser = {
	_expandTimer : null,
	_options : null,

	_update : function(node, folders, files) {
		var me = this;
		var html;

		if (folders && folders.length) {
			html = '<ul style="display:none">';
			j.each(folders, function(k, v) {
				var klass = v.writable ? ' class="writable"' : '';
				html += '<li' + klass + '><div><span class="exp"></span><span class="name">' + v.name + '</span></div></li>';
			});
			html += '</ul>';
			j(node).find('ul').remove()
			j(node).append(html);
			j(node).find('ul:first').slideDown();
		}

		if (files) {
			html = '';
			j.each(files, function(k, v) {
				html += '<option value="' + v + '">' + v + '</option>';
			});
			j(me.fileList).html(html);
		}
	},

	_getPath : function(node) {
		if (!node) {
			node = j(this.node).find('li.selected')[0];
		}
		if (node == this.fnode) return '/';
		var p = node.parentNode;
		var path = [node];
		while (p) {
			if (p.tagName == 'LI') path.push(p);
			p = p.parentNode;
			if (p == this.fnode) break;
		}

		return j(path).map(function(k, v) {
			return j(v).find('> div .name').text();
		}).get().reverse().join('/');
	},

	_selectNode : function(node) {
		var me = this;
		if (!j(node).hasClass('selected')) {
			j(me.node).find('li').removeClass('selected');
			j(node).addClass('selected');
			me.browse(node, 'files');
			me._expandNode(node);
		}
	},

	_expandNode : function(node) {
		var me = this;
		j(node).addClass('expanded');
		if (j(node).find('ul').length == 0) {
			me.browse(node, 'folders');
		}
		else {
			if (me._options.effects) j(node).find('ul:first').slideDown();
			else j(node).find('ul:first').show();
		}
	},

	_initDragDrop : function() {
		var me = this;

		me.node.ondrop = function(e) {
			var files = e.dataTransfer.files;
			if (files.length) {
				var node = j(e.target).closest('li')[0];
				if (node && j(node).hasClass('writable')) {
					me._selectNode(node);
					j('#upload-dialog').dialog('open');
					Uploader.addFiles(files);					
					e.preventDefault();
					e.stopPropagation();
					j(this).find('.name').removeClass('hover');
				}
			}
		};
		me.node.ondragenter = function(e) {
			var node = j(e.target).closest('li')[0];
			if (node && j(node).hasClass('writable')) {
				e.dataTransfer.dropEffect = 'copy';				
				j(node).find('> div .name').addClass('hover');
				me._expandTimer = setTimeout(function() {
					me._expandNode(node);
				}, 500);
			}
			else {
				e.dataTransfer.dropEffect = 'none';
			}
			return false;
		};
		me.node.ondragover = function(e) {			
			return false;
		};
		me.node.ondragleave = function(e) {
			if (me._expandTimer) {
				clearTimeout(me._expandTimer);
				me._expandTimer = null;
			}
			var node = j(e.target).closest('li')[0];
			j(node).find('> div .name').removeClass('hover');
		};
	},

	init : function(options) {
		var me = this;

		me._options = j.extend({
			dragDrop : true,
			effects : true,
			touchEvents : false
		}, options || {});

		me.node = $('lib-filebrowser');
		me.fnode = j('#lib-filebrowser li:first-child')[0];
		me.fileList = $('lib-filebrowser-files-list');

		me._selectNode(me.fnode);

		j('#lib-filebrowser').delegate('.exp', 'click', function(e) {
			var node = j(this).closest('li');
			if (node.hasClass('expanded')) {
				node.removeClass('expanded');
				node.find('ul:first').slideUp();
			}
			else me._expandNode(node[0]);
		});

		j('#lib-filebrowser').delegate('.name', 'click', function(e) {
			var node = j(this).closest('li')[0];
			me._selectNode(node);
		}).delegate('.name', 'contextmenu', function(e) {
			var node = j(this).closest('li')[0];
			if (j(node).hasClass('writable')) {
				j('#filebrowser-menu .writeopt').show();
			}
			else {
				j('#filebrowser-menu .writeopt').hide();
			}
			me._selectNode(node);
			GUI.showContextMenu('#filebrowser-menu', e.clientX, e.clientY);
			e.stopPropagation();
			return false;
		});

		/* library search list */
		j(me.fileList).dblclick(function(e) {
			me.add();
		}).bind('contextmenu', function(e) {
			var found = false;
			var selected = j(this).find('option:selected');
			for (var i = 0; i < selected.length; i++) {
				if (selected[i] == e.target) {
					found = true;
					break;
				}
			}
			if (!found && e.target.tagName == 'OPTION') {
				j(this).find('option').attr('selected', false);
				j(e.target).attr('selected', true);
			}

			selected = j(this).find('option:selected');
			if (selected.length > 0) {
				if (selected.length != 1) j('#filebrowser-list-menu .single').hide();
				else j('#filebrowser-list-menu .single').show();

				GUI.showContextMenu('#filebrowser-list-menu', e.clientX, e.clientY);

				e.stopPropagation();
				return false;
			}
		});

		Uploader.on('load', function() {
			me._selectNode(me.fnode);
		});

		if (me._options.dragDrop) me._initDragDrop();

		(function() {
			var _buttons = {};
			_buttons[msg.cancel] = function() {
				j(this).dialog('close');
			};
			_buttons[msg.add] = function() {
				var data = {
					parent : me.getSelectedPath(),
					name : j('#folder-name').val()
				};
				j.getJSON('/' + LANG + '/ctrl/create-folder/', data, function(json) {
					if (json.error) {
						alert(json.error);
						return;
					}
					me.browse(me.getSelectedNode(), 'folders');
					j('#create-folder-dialog').dialog('close');
				}, 'json');
			};
			j('#create-folder-dialog').dialog({
				autoOpen : false,
				resizable : true,
				modal : true,
				width : 300,
				title : msg.create_folder,
				buttons : _buttons,
				open : function() {
					j('#folder-name').val('')[0].focus();
				}
			});
		})();
	},

	getSelectedPath : function() {
		var node = this.getSelectedNode();
		return node ? this._getPath(node) : null;
	},

	getSelectedNode : function() {
		return j(this.node).find('li.selected')[0];
	},

	getSelectedFile : function() {
		return this._getPath() + '/' + j(this.fileList).find(':selected:first').val();
	},

	browse : function(node, type) {
		var me = this;

		folder = me._getPath(node);
		type = type || 'all';

		j.getJSON('/' + LANG + '/ctrl/browse/' + type, {folder: folder}, function(json) {
			if (json.error) {
				alert(json.error);
				return;
			}
			me._update(node, json.folders, json.files);
		}, 'json');
	},

	addFolder : function(play) {
		var folder = this._getPath();
		Controls.exec('addfolder', {path : folder, play : play});
	},

	add : function(play) {
		var me = this;
		var base = me._getPath();
		var files = j(me.fileList).find(':selected').map(function(k, v) {
			return base + '/' + j(v).val();
		}).get();
		Controls.exec('add', {'files[]' : files, play : play});
		j(me.fileList).find('option').attr('selected', false);
	},

	selectAll : function() {
		j(this.fileList).find('option').attr('selected', true);
		this.fileList.focus();
	},

	refresh : function() {
		this.browse(this.getSelectedNode(), 'all');
	},
	
	localPlay : function() {
		var f = this.getSelectedFile();
		var uri = '/en/ctrl/get-file/0?file=' + encodeURIComponent(f);
		$('userplayer').src = uri;
		$('userplayer').play();

		/*var audio = new Audio(uri);
		audio.play();
		fb(audio);*/
	},

	download : function() {
		var f = this.getSelectedFile();
		var uri = '/en/ctrl/get-file/1?file=' + encodeURIComponent(f);
		location.href = uri;
	}
};