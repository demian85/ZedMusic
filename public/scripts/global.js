function $(id) {
	return document.getElementById(id);
}

function fb(obj) {
	if (console) console.debug(obj);
}

String.prototype.escapeHTML = function() {
	return this.replace('<', '&lt;').replace('>', '&gt;').replace('"', '&quot;');
};

j.fn.addHover = function() {
	return this.hover(function() {
		j(this).addClass('ui-state-hover');
	}, function() {
		j(this).removeClass('ui-state-hover');
	});
};

j.fn.visible = function() {
	return j(this).css('display').toLowerCase() != 'none';
};

j.fn.touchHold = function(fn) {
	var _timer = null;
	j(this).bind('touchstart', function(e) {
		e.preventDefault();
		var touchTime = Date.now();
		var checkTimer = function() {
			if (Date.now() - touchTime >= 1000) {
				fn(e);
				_timer = null;
			}
			else _timer = setTimeout(checkTimer, 10);
		};
		checkTimer();
	}, false);
	j(this).bind('touchend', function(e) {
		if (_timer) {
			clearTimeout(_timer);
			_timer = null;
		}
	}, false);
};

j.fn.delegateTouch = function(selector, type, fn) {
	switch (type) {
		case 'hold':
			var _timer = null;
			j(this).delegate(selector, 'touchstart', function(e) {
				e.preventDefault();
				var touchTime = Date.now();
				var checkTimer = function() {
					if (Date.now() - touchTime >= 1000) {
						fn(e);
						_timer = null;
					}
					else _timer = setTimeout(checkTimer, 10);
				};
				checkTimer();
			}, false);
			j(this).delegate(selector, 'touchend', function(e) {
				if (_timer) {
					clearTimeout(_timer);
					_timer = null;
				}
			}, false);
	}
};

var core = {};

core.util = {
	createOptions : function(list, all) {
		var html = all ? '<option value="" selected="selected">' + msg.all + '</option>' : '';
		j.each(list, function(k, v) {
			html += '<option value="' + v.escapeHTML() + '">' + v.escapeHTML() + '</option>'
		});
		return html;
	}
};

var MPD = {
	getStatus : function(callback) {
		j.getJSON('/' + LANG + '/ctrl/update?user=' + encodeURIComponent(Settings.username), callback);
	},
	exec : function(cmd, params, callback) {
		params = params || {};
		callback = callback || function() {};
		var uri = '/' + LANG + '/ctrl/exec/' + cmd + '?user=' + encodeURIComponent(Settings.username);
		j.post(uri, params, callback, 'json');
	},
	vote : function(callback) {
		callback = callback || function() {};
		j.getJSON('/' + LANG + '/ctrl/vote?user=' + encodeURIComponent(Settings.username), callback);
	},
	setVolume : function(v) {
		if (v < 0) v = 0;
		else if (v > 100) v = 100;
		this.exec('volume', {value : v});
	}
};

var Library = {
	update : function(updateMpd, src) {
		var me = this;
		src = src || null;
		var genre = j('#lib-genre').val() || '';
		var artist = src && src.id != 'lib-genre' ? j('#lib-artist').val() : '';
		var album = src && src.id != 'lib-artist' ? j('#lib-album').val() : '';
		var q = j('#search-query').val();
		var data = {
			'updatempd' : updateMpd ? 1 : 0,
			'q' : q == SEARCH_QUERY_PLACEHOLDER ? '' : q,
			'genre' : genre,
			'artist' : artist,
			'album' : album,
			'itemlimit' : IS_MOBILE ? 20 : 0
		};

		j('#search-status').show();

		j.getJSON('/' + LANG + '/ctrl/update-library', data, function(json) {
			j('#search-status').hide();
			if (!src) {
				j('#lib-genre').html(core.util.createOptions(json.genre, true));
				j('#lib-genre-count').html(json.genre.length);
			}
			if (!src || src && src.id != 'lib-artist' && src.id != 'lib-album') {
				j('#lib-artist').html(core.util.createOptions(json.artist, true));
				j('#lib-artist-count').html(json.artist.length);
			}
			if (!src || src && src.id != 'lib-album') {
				j('#lib-album').html(core.util.createOptions(json.album, true));
				j('#lib-album-count').html(json.album.length);
			}
			j('#lib-list').html(core.util.createOptions(json.list, false));
			j('#lib-file-count span').html(json.list.length);
		});
	}
};

var TouchSlider = function(id) {
	
};
TouchSlider.prototype = (function() {
	return {

	}
})();