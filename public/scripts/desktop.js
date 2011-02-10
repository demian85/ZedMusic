j(function() {
	Controls.init();
});

var socket = null;

var GUI = {
	selectTab : function(tabNode) {
		if (j(tabNode).hasClass('ui-state-active')) return;
		var tab = $(tabNode.id.substr(tabNode.id.indexOf('-')+1));
		j(tabNode).closest('.tab-panel').find('a.ui-state-active').removeClass('ui-state-active');
		j(tabNode).addClass('ui-state-active');
		j(tabNode).closest('.rsgrid').find('.tab').hide();
		j(tab).show();

		if (tab.id == 'tab-playlist') {
			Controls.focusActiveSong();
		}
	},
	showMenu : function(src, id, event) {
		var offset = j(src).addClass('ui-state-active').position();
		j(id).css({
			left : offset.left - (j(id).outerWidth() - j(src).outerWidth()) + 'px',
			top : offset.top + j(src).outerHeight() + 3 + 'px'
		}).addClass('visible');
		event.stopPropagation();
	},
	showContextMenu : function(id, x, y) {
		j('.popupmenu').removeClass('visible');
		var maxw = j(document.body).outerWidth();
		var w = j(id).outerWidth();
		var left = x + 5 + w > maxw ? x - w - 5 : x + 5;
		var maxh = j(document.body).outerHeight();
		var h = j(id).outerHeight();
		var top = y + 5 + h > maxh ? y - h - 5 : y + 5;
		j(id).addClass('visible').css({
			left : left + 'px',
			top : top + 'px'
		});
	},
	toggleLyrics : function(show) {
		if (show) {
			j('#grid-leftbottom-bottom').show();
			$('grid-leftbottom-top').style.bottom = $('grid-leftbottom-bottom').offsetHeight + 'px';
		}
		else {
			j('#grid-leftbottom-bottom').hide();
			$('grid-leftbottom-top').style.bottom = 0;
		}

	},
	toggleArtistBio : function(src) {
		if (j('#artist-txt .bio-content').visible()) {
			j('#artist-txt .bio-content').hide();
			j(src).html(msg.read_more);
		}
		else {
			j('#artist-txt .bio-content').show();
			j(src).html(msg.read_less);
		}
	}
};

var Settings = {
	gui : {
		gridleft: 0,
		gridbottom: 0,
		gridleftbottom : {height:0},
		playlistCols : []
	},
	rdmfilesGenres : null,
	enableShortcuts : true,
	userGenres : null,
	username : ''
};

var Controls = {
	UPDATE_INTERVAL : 5000,
	_updateTimer : null,
	_updating : false,
	_sorting : false,
	_searchTimer : null,
	_songInfo : null,
	_currentSongLabel : null,
	_oneClickPlay : true,
	_sort : {
		item : null,
		index : 0
	},
	_status : null, /* last status update */

	_loadSettings : function() {
		//window.localStorage.settings = '';
		Settings.gui.playlistCols = [
			{name : '#', id : 'index'},
			{name : msg.title, id : 'title'},
			{name : msg.artist, id : 'artist'},
			{name : msg.album, id : 'album'},
			{name : msg.duration, id : 'time'},
			{name : msg.file, id : 'file'}
		];
		
		if (window.localStorage && window.localStorage.settings && JSON) {
			Settings = j.extend(Settings, JSON.parse(window.localStorage.settings));

			j('#grid-left').width(Settings.gui.gridleft.width + 'px');
			j('#grid-right').css({
				left : j('#grid-left').outerWidth() + 'px'
			});

			//j('#grid-leftbottom-bottom').height(Settings.gui.gridleftbottom.height);
			j('#grid-leftbottom-top').css({
				bottom : j('#grid-leftbottom-bottom').outerHeight() + 'px'
			});

			j('#grid-rightbottom').height(Settings.gui.gridbottom.height);
			j('#grid-righttop').css({
				bottom : j('#grid-rightbottom').outerHeight() + 'px'
			});

			j('#pref_shortcuts').attr('checked', Settings.enableShortcuts);

			if (Settings.rdmfilesGenres) {
				j('input[name^=randomfiles_genres]:checked').attr('checked', false);
				j.each(Settings.rdmfilesGenres.split(','), function(k, v) {
					j('input[name^=randomfiles_genres][value="' + v + '"]').attr('checked', true);
				});
			}

			if (Settings.userGenres) {
				j('input[name^=user_genres]:checked').attr('checked', false);
				j.each(Settings.userGenres.split(','), function(k, v) {
					j('input[name^=user_genres][value="' + v + '"]').attr('checked', true);
				});
			}
			
			$('username').value = Settings.username;
		}
		
		j.each(Settings.gui.playlistCols, function(k, v) {
			j('#playlist-columns-menu :checkbox[value=' + v.id + ']').attr('checked', true);
		});
	},

	_initUI : function() {
		var me = this;

		j('#grid-left').resizable({
			handles : 'e',
			minWidth : 180,
			maxWidth : 300,
			resize : function(e, ui) {
				j('#grid-right').css({
					left : j('#grid-left').outerWidth() + 'px'
				});
			},
			stop : function(e, ui) {
				Settings.gui.gridleft = {width : ui.size.width};
				j(this).css('height', '100%');
			}
		});

		j('#grid-leftbottom-bottom').resizable({
			handles : 'n',
			minHeight : 100,
			maxHeight : 650,
			resize : function(e, ui) {
				j('#grid-leftbottom-top').css({
					bottom : j('#grid-leftbottom-bottom').outerHeight() + 'px'
				});
			},
			stop : function(e, ui) {
				Settings.gui.gridleftbottom = {height : j('#grid-leftbottom-bottom').outerHeight()};
				j(this).css({
					width : '100%',
					top : 'auto'
				});
			}
		});

		j('#grid-rightbottom').resizable({
			handles : 'n',
			minHeight : 200,
			maxHeight : 550,
			resize : function(e, ui) {
				j('#grid-righttop').css({
					bottom : j('#grid-rightbottom').outerHeight() + 'px'
				});
			},
			stop : function(e, ui) {
				Settings.gui.gridbottom = {height : ui.size.height};
				j(this).css({
					width : '100%',
					top : 'auto'
				});
			}
		});

		j('#volume-slider').slider({
			min : 0,
			max : 100,
			change : function(e, ui) {
				if (e.originalEvent) Controls.exec('volume', {value : ui.value});
			},
			slide : function(e, ui) {
				j('#volume-slider span').html(ui.value + '%');
			}
		});

		j('#progress-slider').mousedown(function(e) {
			var value =  Math.round(100 * (e.clientX - j(this).offset().left) / j(this).width());
			j('#progress-slider').progressbar('option', 'value', value);
			j('#progress-perc').html('(' + value + '%)')
			Controls.exec('seek', {value : value});
		}).progressbar();

		j('#crossfade-slider').slider({
			min : 0,
			max : 5,
			change : function(e, ui) {
				if (e.originalEvent) Controls.exec('crossfade', {value : ui.value});
			},
			slide : function(e, ui) {
				j('#crossfade-slider span').html(ui.value);				
			}
		});

		/* dialogs */
		(function() {
			var _buttons = {};
			_buttons[msg.save] = function() {
				var fname = j('#playlist-name').val().trim();
				if (!fname) {
					alert(msg.invalid_name);
					return;
				}
				Controls.exec('save', {file : fname}, function() {
					j('#save-dialog').dialog('close');
					Controls._initPlaylists();
				});

			};
			_buttons[msg.cancel] = function() {
				j(this).dialog('close');
			};
			j('#save-dialog').dialog({
				autoOpen : false,
				resizable : false,
				modal : true,
				title : msg.save_playlist,
				buttons : _buttons,
				open : function() {
					j('#playlist-name')[0].focus();
				}
			});
			j('#save-dialog form').submit(function(e) {
				_buttons[msg.save]();
				e.preventDefault();
				e.stopPropagation();
			});
		})();

		(function() {
			var _buttons = {};
			_buttons[msg.cancel] = function() {
				Uploader.cancel();
				j(this).dialog('close');
			};
			_buttons[msg.upload] = function() {
				Uploader.send();
			};
			j('#upload-dialog').dialog({
				autoOpen : false,
				resizable : true,
				modal : true,
				width : 500,
				title : msg.upload_files,
				buttons : _buttons
			});
		})();

		(function() {
			var _buttons = {};
			_buttons[msg.cancel] = function() {
				j(this).dialog('close');
			};
			_buttons[msg.add] = function() {
				var url = '/' + LANG + '/ctrl/add-shoutcast';
				var params = {
					name : j('#shoutcast-name').val(),
					url : j('#shoutcast-url').val()
				};
				j.post(url, params, function(json) {
					if (json.error) {
						alert(json.error);
						return;
					}
					Controls._initPlaylists();
					j('#add-shoutcast-dialog').dialog('close');
				}, 'json');
			};
			j('#add-shoutcast-dialog').dialog({
				autoOpen : false,
				resizable : true,
				modal : true,
				width : 300,
				title : msg.add_shoutcast,
				buttons : _buttons,
				open : function() {
					j('#shoutcast-name').val('')[0].focus();
					j('#shoutcast-url').val('');
				}
			});
		})();

		(function() {
			var _buttons = {};
			var _add = function(play) {
				var diag = this;
				var genres = j('input[name^=randomfiles_genres]:checked').map(function() {
					return j(this).val();
				}).get().join(',');
				var url = '/' + LANG + '/ctrl/add-random';
				var params = {
					count : j('#randomfiles-count').val(),
					genres: genres,
					play: (play ? 1 : 0)
				};
				j.post(url, params, function() {
					Controls.updateStatus();
					j(diag).dialog('close');
				});
			};
			_buttons[msg.cancel] = function() {
				j(this).dialog('close');
			};
			_buttons[msg.add] = function() {
				_add(0);
				j(this).dialog('close');
			};
			_buttons[msg.add_and_play] = function() {
				_add(1);
				j(this).dialog('close');
			};
			j('#randomfiles-dialog').dialog({
				autoOpen : false,
				resizable : true,
				modal : true,
				width : 650,
				title : msg.add_random_files,
				buttons : _buttons,
				open : function() {
					j('#randomfiles-count')[0].focus();
				},
				close : function() {
					Settings.rdmfilesGenres = j('input[name^=randomfiles_genres]:checked').map(function() {
						return j(this).val();
					}).get().join(',');
				}
			});
			j('#randomfiles-checkall').click(function(e) {
				j('input[name^=randomfiles_genres]').attr('checked', this.checked);
			});
		})();

		j('#upload-progressbar').progressbar();

		me._initPlaylistColumns();
		me._initPlaylists();
		//me._updateStats();
		me._initEvents();

		if (!Settings.username) {
			GUI.selectTab($('label-tab-pref'));
			$('username').focus();
		}

		/* drag drop on filebrowser tab */
		$('label-tab-filebrowser').ondragenter = function(e) {
			GUI.selectTab(this);
		};

		FileBrowser.init();
	},
	
	_initEvents : function() {
		var me = this;

		/* tabs */
		j('.tab-panel li a').mousedown(function(e) {
			GUI.selectTab(this);
		});

		j(window).focus(function() {
			me.focusActiveSong();
		});

		j('.listblock .title').click(function() {
			j(this).parent().toggleClass('expanded');
		});

		/* shoutcast */
		j('#shoutcast').delegate('.play', 'click', function(e) {
			var name = j(this).parent().find('.url').text();
			me.exec('add', {'files[]' : name, play : 1});
		});
		j('#shoutcast').delegate('.load', 'click', function(e) {
			var name = j(this).parent().find('.url').text();
			me.exec('add', {'files[]' : name});
		});
		j('#shoutcast').delegate('.remove', 'click', function(e) {
			if (confirm(msg.confirm_remove_list)) {
				var id = j(this).parent().find('.id').text();
				j.getJSON('/' + LANG + '/ctrl/delete-shoutcast/' + id, function(json) {
					if (json.error) {
						alert(json.error);
						return;
					}
					me._initPlaylists();
				});
			}
		});

		/* playlists */
		j('#playlists').delegate('.play', 'click', function(e) {
			var name = j(this).parent().find('.name').text();
			me.exec('loadplay', {file : name});
		});
		j('#playlists').delegate('.load', 'click', function(e) {
			var name = j(this).parent().find('.name').text();
			me.exec('load', {file : name});
		});
		j('#playlists').delegate('.remove', 'click', function(e) {
			if (confirm(msg.confirm_remove_list)) {
				var name = j(this).parent().find('.name').text();
				me.exec('rm', {file : name}, function() {
					me._initPlaylists();
				});
			}
		});

		/* playlist */
		j('#playlist-body').delegate('li', 'click', function(e) {
			if (!me._oneClickPlay) return;
			var i = j('#playlist-body li').index(this) + 1;
			me.exec('play', {index : i});
		}).delegate('.remove', 'click', function(e) {
			var item = j(this).parent();
			var i = j('#playlist-body li').index(item) + 1;
			me.exec('del', {index : i}, function() {
				item.remove();
			});
			e.stopPropagation();
			return false;
		});

		/* sortable columns */
		$('playlist-head').addEventListener('dragenter', function(e) {
			var src = j(e.target).closest('li')[0];
			if (j(src).is('[draggable]')
					&& j(me._sort.item).is('#playlist-head li') && me._sort.item != src) {
				j(src).addClass('dragover');
			}
		}, false);
		$('playlist-head').addEventListener('dragover', function(e) {
			var src = j(e.target).closest('li')[0];
			if (j(src).is('[draggable]')
					&& j(me._sort.item).is('#playlist-head li') && me._sort.item != src) {
				e.preventDefault();
			}
		}, false);
		$('playlist-head').addEventListener('dragleave', function(e) {
			j(e.target).closest('li').removeClass('dragover');
		}, false);
		$('playlist-head').addEventListener('drop', function(e) {
			var src = j(e.target).closest('li')[0];
			var fromIndex = e.dataTransfer.getData('text/plain');
			var targetIndex = j('#playlist-head li').index(src);
			if (fromIndex < targetIndex) targetIndex++;
			var ref = j('#playlist-head li').get(targetIndex) || null;
			src.parentNode.insertBefore(me._sort.item, ref);
			me._updatePlaylistColumns();
			me.updateStatus();
			e.preventDefault();
		}, false);
		$('playlist-head').addEventListener('dragend', function(e) {
			me._sort.item = null;
		}, false);
		
		/* sortable playlist */
		$('playlist-body').addEventListener('dragover', function(e) {
			var src = j(e.target).closest('li')[0];
			if (j(me._sort.item).is('#playlist-body li') && me._sort.item != src) {
				e.preventDefault();
			}
		}, false);
		$('playlist-body').addEventListener('dragenter', function(e) {
			var src = j(e.target).closest('li')[0];
			if (src && j(me._sort.item).is('#playlist-body li') && me._sort.item != src) {
				j(src).addClass('dragover');

				var scroll = $('playlist-body').scrollTop;
				var h1 = scroll + src.offsetHeight*2;
				var h2 = $('playlist-body').clientHeight + scroll - src.offsetHeight*2;
				if (src.offsetTop < h1) {
					$('playlist-body').scrollTop = scroll - src.offsetHeight;
				}
				if (src.offsetTop > h2) {
					$('playlist-body').scrollTop = scroll + src.offsetHeight;
				}
			}
		}, false);
		$('playlist-body').addEventListener('dragleave', function(e) {
			j(e.target).closest('li').removeClass('dragover');
		}, false);
		$('playlist-body').addEventListener('drop', function(e) {
			var src = j(e.target).closest('li')[0];
			var sortIndex = e.dataTransfer.getData('text/plain');
			var newIndex = j('#playlist-body li').index(src) + 1;
			me.exec('move', {from : sortIndex, to : newIndex});
			e.preventDefault();
		}, false);
		$('playlist-body').addEventListener('dragend', function(e) {
			me._sort.item = null;
			me._sorting = false;
			setTimeout(function() {
				me._oneClickPlay = true;
			}, 10);
		}, false);

		j(document.body).click(function() {
			j('.popupmenu').removeClass('visible');
			j('.popup-trigger').removeClass('ui-state-active');
		});

		j('#playlist-columns-menu :checkbox').change(function(e) {
			if (j('#playlist-columns-menu :checked').length == 0) this.checked = true;
			me._updatePlaylistColumns();
			me.updateStatus();
		});

		/* effects */
		j('body').delegate('#search-list-menu li, .button, .textfield, .tab-panel li a', 'mouseenter', function() {
			if (j(this).hasClass('ui-state-default')) j(this).addClass('ui-state-hover');
		}).delegate('#search-list-menu li, .button, .textfield, .tab-panel li a', 'mouseleave', function() {
			j(this).removeClass('ui-state-hover');
		}).delegate('.textfield', 'focus', function() {
			j(this).addClass('ui-state-active');
		}).delegate('.textfield', 'blur', function() {
			j(this).removeClass('ui-state-active');
		});

		/*j('.tab-panel ul li a').mousemove(function(e) {
			var x = e.layerX + 'px';
			var y = e.layerY + 'px';
			j(this).css('background', '-moz-radial-gradient(' + x + ' ' + y + ', circle farthest-side, #0972A5, #003147)');
		});*/

		/* search library text field */
		var searchQueryText = j('#search-query').val();
		j('#search-query').focus(function(e) {
			if (j(this).val() == searchQueryText) j(this).val('');
		}).blur(function(e) {
			if (j(this).val() == '') j(this).val(searchQueryText);
		})/*.keypress(function(e) {
			if (me._searchTimer) {
				clearTimeout(me._searchTimer);
				me._searchTimer = null;
			}
			me._searchTimer = setTimeout(function() {
				me.updateLibrary();
			}, 600);
		})*/;

		/* library search list */
		j('#lib-list').dblclick(function(e) {
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
			if (!found) {
				j(this).find('option').attr('selected', false);
				j(e.target).attr('selected', true);
			}

			GUI.showContextMenu('#search-list-menu', e.clientX, e.clientY);

			e.stopPropagation();
			return false;
		});

		/* play url */
		var _value = j('#play-url').val();
		j('#play-url').focus(function(e) {
			if (j(this).val() == _value) j(this).val('');
		}).blur(function(e) {
			if (j(this).val() == '') j(this).val(_value);
		});

		/* shortcuts */
		j(window).keydown(function(e) {
			if (!Settings.enableShortcuts) return;
			var input = j(document.activeElement).is('input,select,textarea');
			switch (e.keyCode) {
				case 70: // F
					if (input) return;
					GUI.selectTab($('label-tab-library'));
					$('search-query').focus();
					e.preventDefault();
					e.stopPropagation();
					break;
				case 82: // R
					if (input) return;
					j('#randomfiles-dialog').dialog('open');
					e.preventDefault();
					e.stopPropagation();
					break;
				case 112: // F1
					me.setVolume(j('#volume-slider').slider('value') - 5);
					e.preventDefault();
					e.stopPropagation();
					break;
				case 113: // F2
					me.setVolume(j('#volume-slider').slider('value') + 5);
					e.preventDefault();
					e.stopPropagation();
					break;
				case 114: // F3
					me.exec('toggle');
					e.preventDefault();
					e.stopPropagation();
					break;
			}
		});

		/* uploader */
		j('#files-input').bind('change', function(e) {
			Uploader.addFiles(e.target.files);
			this.value = '';
		});

		/* preferences */
		j('#pref_shortcuts').click(function(e) {
			Settings.enableShortcuts = this.checked;
		});
		j('#username').change(function(e) {
			Settings.username = this.value;
		})
		j('#tab-pref .user-genres :checkbox').change(function(e) {
			j('#save-settings-btn').attr('disabled', false);
			Settings.userGenres = j('input[name^=user_genres]:checked').map(function() {
				return j(this).val();
			}).get().join(',');
		});
	},

	_initPlaylist : function(playlist, curIndex) {
		var me = this;
		
		var getColHTML = function(i, data) {
			var col = Settings.gui.playlistCols[i];
			if (!col) return null;
			var value = data[col.id];
			var html = '<div title="' + value + '" data-coltype="' + col.id + '">' + (value ? value : '-') + '</div>';
			return html;
		}
		var html = '';

		j.each(playlist, function(k, v) {
			var css = (curIndex-1 == k) ? ' class="active"' : '';
			html += '<li draggable="true"' + css + '><div class="remove" title="' + msg.remove + '"></div>';
			for (var i = 0, td; i < 6; i++) {
				td = getColHTML(i, v);
				html += td ? td : '';
			}
			html += '</li>';
		});
		j('#playlist-body').html(html);

		me._oneClickPlay = true;

		var rows = $('playlist-body').querySelectorAll('li');
		var onDragStart = function(e) {
			var sortIndex = j('#playlist-body li').index(this) + 1;
			e.dataTransfer.setData('text/plain', sortIndex);
			e.dataTransfer.effectAllowed = 'move';
			me._sort.item = this;
			me._sorting = true;
			me._oneClickPlay = false;
		};

		for (var i = 0, len = rows.length; i < len; i++) {
			rows[i].addEventListener('dragstart', onDragStart, false);
		}

		j('#playlist-item-count').html(playlist.length);

		if (playlist.length) {
			j('#empty-playlist-msg').hide();
			j('#playlist-head').show();
			j('#playlist-panel-top button').attr('disabled', false);
		}
		else {
			j('#empty-playlist-msg').show();
			j('#playlist-head').hide();
			j('#playlist-panel-top button').attr('disabled', true);
		}
	},

	_initPlaylistColumns : function() {
		var me = this;
		
		var html = '';
		var c = Settings.gui.playlistCols;
		for (var i = 0, len = c.length; i < len; i++) {
			html += '<li draggable="true" data-coltype="' + c[i].id + '">' + c[i].name + '</li>';
		}
		j('#playlist-head').html(html);

		/* drag & drop */
		(function() {
			var cols = $('playlist-head').querySelectorAll('li');
			var onDragStart = function(e) {
				var sortIndex = j('#playlist-head li').index(this);
				e.dataTransfer.setData('text/plain', sortIndex);
				e.dataTransfer.effectAllowed = 'move';
				me._sort.item = this;
			};
			for (var i = 0, len = cols.length; i < len; i++) {
				cols[i].addEventListener('dragstart', onDragStart, false);
			}
		})();
	},

	_updatePlaylistColumns : function() {
		var me = this;

		Settings.gui.playlistCols = [];
		j('#playlist-head li').each(function() {
			var checked = j('#playlist-columns-menu :checkbox[value=' + j(this).attr('data-coltype') + ']:checked').length > 0;
			if (checked) {
				Settings.gui.playlistCols.push({
					name : j(this).text(),
					id : j(this).attr('data-coltype')
				});
			}
		});
		j('#playlist-columns-menu :checked').each(function() {
				var exists = j('#playlist-head li[data-coltype=' + this.value + ']').length > 0;
				if (!exists) {
					Settings.gui.playlistCols.push({
						name : j(this).parent().text(),
						id : this.value
					});
				}
		});
		me._initPlaylistColumns();
	},

	_initPlaylists : function(list) {
		var me = this;

		j.getJSON('/' + LANG + '/ctrl/playlists', function(json) {
			if (json.error) {
				alert(json.error);
				return;
			}
			var html = j(json.playlists).map(function(k, v) {
				return '<li>\
					<span class="name">' + v + '</span>\
					<span class="remove opt button ui-state-default" title="' + msg.remove + '"></span>\
					<span class="load opt button ui-state-default" title="' + msg.load + '"></span>\
					<span class="play opt button ui-state-default" title="' + msg.play + '"></span>\
				</li>';
			}).get().join('');
			j('#playlists').html(html);

			html = j(json.shoutcast).map(function(k, v) {
				return '<li>\
					<span class="id">' + v.id + '</span>\
					<span class="url">' + v.url + '</span>\
					<span class="remove opt button ui-state-default" title="' + msg.remove + '"></span>\
					<span class="load opt button ui-state-default" title="' + msg.add + '"></span>\
					<span class="play opt button ui-state-default" title="' + msg.play + '"></span>\
					<span class="name" title="' + v.url + '">' + v.name + '</span>\
				</li>';
			}).get().join('');
			j('#shoutcast').html(html);
		});
	},

	_updateArtistInfo : function() {
		var me = this;

		me.getLyrics();

		var params = {
			method : 'artist.getinfo',
			artist : me._songInfo.artist
		};

		j.getJSON(LASTFM_WS, params, function(json) {
			if (json.error) {
				j('#artist-error').html(json.message).show();
				j('#artist-txt').hide();
				return;
			}

			j('#artist-error').hide();

			var html = '';
			var imgs = j.grep(json.artist.image, function(v, k) {
				return (v.size == 'large' || v.size == 'medium');
			});
			var img = (imgs.length) ? '<img src="' + imgs[imgs.length-1]['#text'] + '" alt="" />' : '';
			var summary = json.artist.bio.summary ? json.artist.bio.summary.trim().replace(/[\r\n]+/g, '<br/>') : msg.no_artist_info;
			var content = json.artist.bio.content ? json.artist.bio.content.trim().replace(/[\r\n]+/g, '<br/>') : msg.no_artist_info;
			j('#artist-txt .bio-summary p').html(img + summary);
			j('#artist-txt .bio-content').html(content);
			j('#tab-artist').scrollTo(0);

			/* tags */
			try {
				var tags = j.isArray(json.artist.tags.tag) ? json.artist.tags.tag : [json.artist.tags.tag];
				j.each(tags, function(k, v) {
					html += '<li><a href="'+ v.url +'">' + v.name + '</a></li>';
				});
				j('#artist-tags').html(html);

				/* similar artists */
				html = '';
				j.each(json.artist.similar.artist, function(k, v) {
					var imgs = j.grep(v.image, function(v1, k1) {
						return (v1.size == 'large' || v1.size == 'medium');
					});
					var img = (imgs.length && imgs[0]['#text']) ? '<img src="' + imgs[0]['#text'] + '" alt="" />' : '';
					html += '<li><a href="'+ v.url +'" target="_blank">' + img + '<br />' + v.name + '</a></li>';
				});
				j('#artist-similar').html(html);
			} catch (e) {}

			j('#artist-txt').show();
		});

		/* top albums */
		params = {
			method : 'artist.gettopalbums',
			artist : me._songInfo.artist
		}
		j.getJSON(LASTFM_WS, params, function(json) {
			if (json.error) {
				j('#artist-albums').html(json.message);
				return;
			}
			var html = '';
			if (!json.topalbums.album) {
				html = '';
				j('#artist-albums-main').hide();
			}
			else {
				html = '<ul>';
				var albums = j.isArray(json.topalbums.album) ? json.topalbums.album : [json.topalbums.album];
				j.each(albums.slice(0, 7), function(k, v) {
					var imgs = j.grep(v.image, function(v1, k1) {
						return (v1.size == 'large' || v1.size == 'medium');
					});
					var img = (imgs.length) ? '<img src="' + imgs[0]['#text'] + '" alt="" />' : '';
					html += '<li><a href="'+ v.url +'" target="_blank">' + img + '<br />' + v.name + '</a></li>';
				});
				html += '</ul>';
				j('#artist-albums-main').show();
			}

			j('#artist-albums').html(html);
		});

		/* top tracks */
		params = {
			method : 'artist.gettoptracks',
			artist : me._songInfo.artist
		}
		j.getJSON(LASTFM_WS, params, function(json) {
			if (json.error) {
				j('#artist-tracks').html(json.message);
				return;
			}
			var html = '';
			if (!json.toptracks.track) {
				html = '';
				j('#artist-tracks').parent().hide();
			}
			else {
				html = '<ol>';
				var tracks = j.isArray(json.toptracks.track) ? json.toptracks.track : [json.toptracks.track];
				j.each(tracks.slice(0, 10), function(k, v) {
					html += '<li><a href="'+ v.url +'" target="_blank">' + v.name + '</a></li>';
				});
				html += '</ol>';
				j('#artist-tracks').parent().show();
			}

			j('#artist-tracks').html(html);
		});
	},

	_updateStats : function() {
		j.getJSON('/' + LANG + '/ctrl/stats', function(json) {
			//j('#mpd-stats').html(json.stats.mpd);
			j('#file-stats').html(json.stats.files);
			setTimeout(Controls._updateStats, 90000);
		});
	},
	
	init : function() {
		window.onunload = function() {
			if (window.localStorage && JSON) {
				window.localStorage.settings = JSON.stringify(Settings);
			}
		};
		
		this.initSocket();
		this._loadSettings();
		this._initUI();
		this.updateStatus(this.focusActiveSong);
		this.updateLibrary(false, null);
	},

	initSocket : function() {
		try {
			io.setPath('/scripts/socket.io');
			socket = new io.Socket(location.host, {port:8080});
			socket.connect();
			socket.on('connect', function() {
				fb('Socket connection established.');
			});
			socket.on('message', function(msg) {
				var data = JSON.parse(msg);
				if (data.action == 'update') Controls.updateStatus();
			});
		} catch (e) {
			fb('Unable to establish socket connection.');
		}
	},
	
	updateStatus : function(afterUpdate) {
		var me = this;
		
		if (me._updating) return;
		
		if (me._updateTimer) {
			clearTimeout(me._updateTimer);
			me._updateTimer = null;
		}
		
		me._updating = true;

		MPD.getStatus(function(json) {

			me._status = json;
			me._songInfo = json.song_info;

			/* play button */
			if (json.status == 'playing') {
				j('#toggle-button').attr({
					src : '/images/new/pause.png',
					title : msg.pause
				});
				document.title = 'Zedmusic - ' + msg.playing + ' "' + json.current_song + '"';
			}
			else {
				j('#toggle-button').attr({
					src : '/images/new/play.png',
					title : msg.play
				});
			}

			if (json.status == 'stopped') {
				j('#vote-button, #progress-slider').hide();
				j('#label-tab-artist').parent().hide();
				GUI.selectTab($('label-tab-playlist'));
				j('#status').addClass('stopped');
				GUI.toggleLyrics(false);
				document.title = 'Zedmusic - ' + msg.stopped;
			}
			else {
				if (json.status == 'paused') document.title = 'Zedmusic - ' + msg.paused;
				j('#vote-button, #progress-slider').show();
				j('#label-tab-artist').parent().show();
				j('#status').removeClass('stopped');
				GUI.toggleLyrics(true);
			}

			/* toggle buttons */
			if (json.repeat) j('#repeat-button').addClass('ui-state-active');
			else j('#repeat-button').removeClass('ui-state-active');
			if (json.random) j('#random-button').addClass('ui-state-active');
			else j('#random-button').removeClass('ui-state-active');

			/* text status */
			j('#status').html(json.fstatus);

			/* controls */
			j('#volume-slider').slider('value', json.volume);
			j('#volume-slider span').html(json.volume + '%');

			j('#progress-slider').progressbar('value', json.progress);
			j('#progress-perc').html('(' + json.progress + '%)');

			j('#crossfade-slider').slider('value', json.crossfade);
			j('#crossfade-slider span').html(json.crossfade);

			/* playlist */
			if (!me._sorting) me._initPlaylist(json.playlist, json.playing_index);

			/* log */
			html = '';
			j.each(json.log, function(k, v) {
				html += '<tr><td>' + (v.user || '-') + '</td><td>' + v.ip + '</td><td>' + v.fdate + '</td><td>' + v.action + '</td></tr>';
			});
			j('#log-table tbody').html(html);

			/* detect song change / artist details */
			if (me._currentSongLabel != json.current_song) {
				if (json.current_song) {
					me._updateArtistInfo();
					j('#vote-button').attr('disabled', false);
				}
				else {
					j('#artist-txt').hide();
				}
			}

			/* votes */
			j('#vote-button span').html(json.votes_info);

			/* server status */
			j('#server-status-button').html(json.server_status == 1 ? msg.stop_server : msg.enable_server);

			me._currentSongLabel = json.current_song;

			if (typeof afterUpdate == 'function') afterUpdate();

			me._updating = false;

			me._updateTimer = setTimeout(function() {
				me.updateStatus();
			}, me.UPDATE_INTERVAL);
		});
	},
	
	updateLibrary : function(updateMpd, src) {
		Library.update(updateMpd, src);
	},
	
	exec : function(cmd, params, callback) {
		var me = this;
		MPD.exec(cmd, params, function(json) {
			if (json.error) {
				alert(json.error);
				return;
			}
			if (socket) socket.send(JSON.stringify({action : 'update'}));
			me.updateStatus();
			if (typeof callback == 'function') callback();
		});
	},

	vote : function() {
		var me = this;
		MPD.vote(function() {
			j('#vote-button').removeClass('ui-state-hover').attr('disabled', true);
			me.updateStatus();
		});
	},

	toggleServerStatus : function() {
		var me = this;
		var st = me._status.server_status ? 0 : 1;
		j.getJSON('/' + LANG + '/ctrl/server/' + st, function(json) {
			me.updateStatus();
			me.initSocket();
		});
	},

	getLyrics : function() {
		var data = {
			artist : this._songInfo.artist,
			title : this._songInfo.title
		};
		j.getJSON('/' + LANG + '/ctrl/lyrics', data, function(json) {
			if (json.error) {
				j('#artist-lyrics h3').hide();
				j('#artist-lyrics-txt').html(json.error);
			}
			else {
				j('#artist-lyrics').show();
				j('#artist-lyrics h3').show().find('span').html(json.title);
				j('#artist-lyrics-txt').html(json.lyrics);
			}
		});
	},
	
	add : function(play) {
		var files = j('#lib-list').serialize();
		this.exec('add', files + '&play=' + (play || 0));
		j('#lib-list option').attr('selected', false);
	},

	savePlayList : function() {
		j('#save-dialog').dialog('open');
	},

	clearPlaylist : function() {
		if (confirm(msg.confirm_clear)) {
			this.exec('clear');
		}
	},

	setVolume : function(v) {
		if (v < 0) v = 0;
		else if (v > 100) v = 100;
		this.exec('volume', {value : v});
	},

	playURL : function() {
		var f = j('#play-url').val().trim();
		if (!f) return;
		j('#play-url').next('img').show();
		this.exec('add', {'files[]' : f}, function() {
			j('#play-url').val('').blur();
			j('#play-url').next('img').hide();
		});
	},

	focusActiveSong : function() {
		if (j('#playlist-body li.active').length) {
			j('#playlist-body').scrollTo('li.active', 1000);
		}
	},

	clearSearch : function() {
		j('#search-query').val('').blur();
		this.updateLibrary();
	},

	selectAll : function() {
		j('#lib-list option').attr('selected', true);
		j('#lib-list')[0].focus();
	}
};




var Pref = {
	setLang : function(src) {
		location.href = '/' + j(src).val();
	},
	setTheme : function(src) {
		var theme = j(src).val();
		j('#theme-css').attr('href', '/styles/jquery/' + theme + '/jquery-ui-1.8.css');
		j('#theme-css2').attr('href', '/styles/' + theme + '.css');
		j.setCookie('theme', theme, {});
	},
	save : function() {
		var data = {
			genres : Settings.userGenres
		};
		j.post('/' + LANG + '/ctrl/preferences', data, function(json) {
			if (json.error) {
				alert(json.error);
				return;
			}
			j('#save-settings-btn').removeClass('ui-state-hover').attr('disabled', true);
		}, 'json');
	}
};