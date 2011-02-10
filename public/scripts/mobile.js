j(function() {
	Controls.init();
});

var socket = null;

var GUI = {
	selectTab : function(tabNode) {
		if (j(tabNode).hasClass('ui-state-active')) return;
		var tab = $(tabNode.id.substr(tabNode.id.indexOf('-')+1));
		j(tabNode).closest('.tab-panel-tabs').find('a.ui-state-active').removeClass('ui-state-active');
		j(tabNode).addClass('ui-state-active');
		j(tabNode).closest('.tab-panel').find('.tab').hide();
		j(tab).show();

		if (tab.id == 'tab-playlist') {
			Controls.focusActiveSong();
		}
	},
	showMenu : function(src, id, event) {
		var offset = j(src).addClass('ui-state-active').position();
		j(id).css({
			left : offset.left - (j(id).outerWidth() - j(src).outerWidth()) + 'px',
			top : offset.top + j(src).outerHeight() + 'px'
		}).show();
		event.stopPropagation();
	},
	showContextMenu : function(id, x, y) {
		var maxw = j(document.body).outerWidth();
		var w = j(id).outerWidth();
		var left = x + 5 + w > maxw ? x - w - 5 : x + 5;
		var maxh = j(document.body).outerHeight();
		var h = j(id).outerHeight();
		var top = y + 5 + h > maxh ? y - h - 5 : y + 5;
		j(id).show().css({
			left : left + 'px',
			top : top + 'px'
		});
	}
};

var Settings = {
	gui : {
		playlistCols : []
	},
	rdmfilesGenres : null,
	userGenres : null,
	username : ''
};

var Controls = {
	UPDATE_INTERVAL : 500000,
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
		window.localStorage.settings = '';
		Settings.gui.playlistCols = [
			{name : '#', id : 'index'},
			{name : msg.title, id : 'title'},
			{name : msg.artist, id : 'artist'},
			{name : msg.album, id : 'album'},
			{name : msg.file, id : 'file'},
			{name : msg.duration, id : 'time'}
		];

		if (window.localStorage && window.localStorage.settings && JSON) {
			Settings = JSON.parse(window.localStorage.settings);
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
		}

		j.each(Settings.gui.playlistCols, function(k, v) {
			j('#playlist-columns-menu :checkbox[value=' + v.id + ']').attr('checked', true);
		});
	},

	_initUI : function() {
		var me = this;

		j('#volume-slider .slider-handle').bind('toushstart touchmove', function(e) {
			var value =  Math.round(100 * (e.clientX - j(this).offset().left) / this.clientWidth);
			this.style.left = e.clientX - j('#volume-slider').offset().left - (e.clientX - j(this).offset().left) + 'px';
			MPD.setVolume(value);
		});
		
		/*j('#volume-slider').change(function(e) {
			MPD.setVolume(e.target.value);
			j('#volume-slider + span').html(e.target.value + '%');
		});
		j('#crossfade-slider').change(function(e) {
			me.exec('crossfade', {value : e.target.value});
			j('#crossfade-slider + span').html(e.target.value);
		});
		j('#progress-slider-wrapper').click(function(e) {
			var value =  Math.round(100 * (e.clientX - j(this).offset().left) / j(this).width());
			$('progress-slider').value = value;
			j('#progress-perc').html('(' + value + '%)')
			me.exec('seek', {value : value});
		});*/

		me._initPlaylistColumns();
		me._initEvents();
		FileBrowser.init({
			effects : !IS_MOBILE,
			touchEvents : IS_MOBILE,
			dragDrop : !IS_MOBILE
		});
	},

	_initEvents : function() {
		var me = this;

		/* tabs */
		j('.tab-panel-tabs li a').bind('click', function(e) {
			GUI.selectTab(this);
		});

		j(window).focus(function() {
			me.focusActiveSong();
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
			j('.popupmenu').hide();
			j('.popup-trigger').removeClass('ui-state-active');
		});

		j('#playlist-columns-menu :checkbox').change(function(e) {
			if (j('#playlist-columns-menu :checked').length == 0) this.checked = true;
			me._updatePlaylistColumns();
			me.updateStatus();
		});

		/* search library text field */
		var searchQueryText = j('#search-query').val();
		j('#search-query').focus(function(e) {
			if (j(this).val() == searchQueryText) j(this).val('');
		}).blur(function(e) {
			if (j(this).val() == '') j(this).val(searchQueryText);
		});

		/* library search list */
		/*j('#lib-list').bind('touchhold', function(e) {
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
		});*/

		/* play url */
		var _value = j('#play-url').val();
		j('#play-url').focus(function(e) {
			if (j(this).val() == _value) j(this).val('');
		}).blur(function(e) {
			if (j(this).val() == '') j(this).val(_value);
		});

		/* preferences */
		j('#pref_shortcuts').click(function(e) {
			Settings.enableShortcuts = this.checked;
		});

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
		$('playlist-head').innerHTML = html;

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

	init : function() {
		window.onunload = function() {
			if (window.localStorage && JSON) {
				window.localStorage.settings = JSON.stringify(Settings);
			}
		};
		this._loadSettings();
		this._initUI();
		this.updateStatus(this.focusActiveSong);
		Library.update(false);
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
			}
			else {
				j('#toggle-button').attr({
					src : '/images/new/play.png',
					title : msg.play
				});
			}

			if (json.status == 'stopped') {
				j('#vote-button, #progress-slider').hide();
				j('#status').addClass('stopped');
			}
			else {
				if (json.status == 'paused') document.title = 'Zedmusic - ' + msg.paused;
				j('#vote-button, #progress-slider').show();
				j('#status').removeClass('stopped');
			}

			/* toggle buttons */
			/*if (json.repeat) j('#repeat-button').addClass('ui-state-active');
			else j('#repeat-button').removeClass('ui-state-active');
			if (json.random) j('#random-button').addClass('ui-state-active');
			else j('#random-button').removeClass('ui-state-active');*/

			/* text status */
			j('#status').html(json.fstatus);

			/* controls */
			/*j('#volume-slider').val(json.volume);
			j('#volume-slider-label').html(json.volume + '%');

			$('progress-slider').value = json.progress;
			j('#progress-perc').html('(' + json.progress + '%)');

			j('#crossfade-slider').val(json.crossfade);
			j('#crossfade-slider-label').html(json.crossfade);*/

			/* playlist */
			if (!me._sorting) {
				me._initPlaylist(json.playlist, json.playing_index);
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

	focusActiveSong : function() {
		if (j('#playlist-body li.active').length) {
			j('#playlist-body').scrollTo('li.active', 0);
		}
	}
};