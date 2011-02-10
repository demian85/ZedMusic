<div id="grid-left" class="rsgrid">

	<div id="controls">

		<div id="control-buttons" class="panel center">						
			<img class="button ui-state-default" title="<?=_("Previous")?>" src="/images/new/previous.png" onclick="Controls.exec('prev')"/>
			<img class="button ui-state-default" id="toggle-button" title="<?=_("Play")?>" src="/images/new/play.png" onclick="Controls.exec('toggle')"/>
			<img class="button ui-state-default" title="<?=_("Stop")?>" src="/images/new/stop.png" onclick="Controls.exec('stop')"/>
			<img class="button ui-state-default" title="<?=_("Next")?>" src="/images/new/next.png" onclick="Controls.exec('next')"/>
			<div style="margin-top:5px;">
				<img class="button ui-state-default" title="<?=_("Repeat mode")?>" src="/images/repeat-mode.png" id="repeat-button" onclick="Controls.exec('repeat')"/>
				<img class="button ui-state-default" title="<?=_("Random mode")?>" src="/images/random-mode.png" id="random-button" onclick="Controls.exec('random')"/>
			</div>
		</div>

		<div class="panel center">
			<div id="volume-slider" class="slider"><?=_("Volume")?> (<span></span>)</div>
			<div id="crossfade-slider" class="slider"><?=_("Crossfade")?> (<span></span>)</div>
		</div>

	</div>

	<div id="grid-leftbottom">

		<div id="grid-leftbottom-top">

			<div class="listblock expanded">
				<h3 class="title"><img src="/images/shoutcast.png" alt="" class="vmiddle" /> <?=_("Shoutcast")?></h3>
				<ul id="shoutcast"></ul>
				<div><a href="javascript:;" onclick="j('#add-shoutcast-dialog').dialog('open')"><?=_("Add New...")?></a></div>
			</div>

			<div class="listblock expanded">
				<h3 class="title"><img src="/images/favourites.16x16.ico" alt="" class="vmiddle" /> <?=_("Playlists")?></h3>
				<ul id="playlists"></ul>
			</div>

		</div>

		<div id="grid-leftbottom-bottom" style="display:none">
			<div id="artist-lyrics">
				<h3><span></span></h3>
				<div id="artist-lyrics-txt"></div>
			</div>
		</div>
	</div>
	
</div>

<div id="grid-right" class="rsgrid">

	<div id="grid-righttop" class="rsgrid">

		<div class="tab-panel">

			<ul>
				<li><a href="javascript:;" id="label-tab-playlist" class="ui-state-default ui-state-active"><img src="/images/playlist.png" alt="" /> <?=_("Playlist")?> (<span id="playlist-item-count">0</span>)</a></li>
				<li style="display:none"><a href="javascript:;" id="label-tab-artist" class="ui-state-default"><img src="/images/new/info.20x20.png" alt="" /> <?=_("Artist Info")?></a></li>
			</ul>

			<div id="status-panel">

				<button id="vote-button" class="button ui-state-default" title="<?=_("I don't like this!")?>" onclick="Controls.vote()" style="display:none"><img src="/images/thumbs_down.png" alt="" /> <span>0</span></button>

				<div id="progress-slider" class="slider" style="display:none">
					<div id="progress-label"><span id="status"></span> <span id="progress-perc"></span></div>
				</div>

			</div>
		</div>

		<div id="tab-playlist" class="tab">

			<div class="boxwrap flexbox flexv">

				<div id="playlist-panel-top">

					<div class="panel-left">
						<button onclick="Controls.clearPlaylist()" class="button ui-state-default" title="<?=_("Clear List")?>"><img src="/images/cancel.16x16.ico" alt="" /> <?=_("Clear List")?></button>
						<button onclick="Controls.exec('crop')" class="button ui-state-default" title="<?=_("Crop")?>"><img src="/images/cut.16x16.ico" alt="" /> <?=_("Crop")?></button>
						<button onclick="Controls.savePlayList()" class="button ui-state-default" title="<?=_("Save playlist")?>"><img src="/images/save.16x16.ico" alt="" /> <?=_("Save")?></button>
						<button onclick="Controls.exec('shuffle')" class="button ui-state-default" title="<?=_("Shuffle")?>"><img src="/images/shuffle.16x16.png" alt="" /> <?=_("Shuffle")?></button>
						<div>
							<form method="get" action="" onsubmit="Controls.playURL(); return false;">
								<input type="text" id="play-url" value="<?=_("Add URL...")?>" class="textfield ui-state-default" size="35" />
								<input type="submit" value="" style="display:none" />
								<img src="/images/ajax-loader1.gif" alt="" style="display:none" />
							</form>
						</div>
					</div>

					<button onclick="GUI.showMenu(this, '#playlist-columns-menu', event)" class="fright button ui-state-default popup-trigger" title="<?=_("Columns")?>"><img src="/images/cols.png" alt="" /></button>

					<ul id="playlist-columns-menu" class="popupmenu">
						<li><label for="plcolumn-1"><input type="checkbox" name="plcolumn" id="plcolumn-1" value="index" />#</label></li>
						<li><label for="plcolumn-2"><input type="checkbox" name="plcolumn" id="plcolumn-2" value="file" /><?=_("File")?></label></li>
						<li><label for="plcolumn-3"><input type="checkbox" name="plcolumn" id="plcolumn-3" value="artist" /><?=_("Artist")?></label></li>
						<li><label for="plcolumn-4"><input type="checkbox" name="plcolumn" id="plcolumn-4" value="album" /><?=_("Album")?></label></li>
						<li><label for="plcolumn-5"><input type="checkbox" name="plcolumn" id="plcolumn-5" value="title" /><?=_("Title")?></label></li>
						<li><label for="plcolumn-6"><input type="checkbox" name="plcolumn" id="plcolumn-6" value="time" /><?=_("Duration")?></label></li>
					</ul>

				</div>

				<ul id="playlist-head" class="flexbox flexh"></ul>

				<div class="scrollpane">
					<ul id="playlist-body"></ul>
				</div>

			</div>

			<div id="empty-playlist-msg" class="center" style="display:none"><?=_("Playlist is empty. Check the library and add some files!")?></div>

		</div>

		<div id="tab-artist" class="tab" style="display:none">

			<div id="artist-error" style="display:none"></div>

			<div id="artist-txt">

				<div class="bio-main">
					<div class="bio-summary">
						<p></p>
						<a href="javascript:;" onclick="GUI.toggleArtistBio(this)" class="toggle-link"><?=_("Read more...")?></a>
						<div class="bio-content" style="display:none"></div>
					</div>
				</div>
				
				<div id="artist-albums-main" style="display:none">
					<div class="separator"></div>
					<div class="box">
						<h3><?=_("Top Albums")?></h3>
						<div id="artist-albums"></div>
					</div>
				</div>

				<div style="overflow:auto">

					<div class="separator"></div>
					<div class="lbox">
						<h3><?=_("Tags")?></h3>
						<ul id="artist-tags"></ul>
					</div>

					<div class="box lbox" style="display:none">
						<h3><?=_("Top Tracks")?></h3>
						<div id="artist-tracks"></div>
					</div>

					<div class="box lbox">
						<h3><?=_("Similar Artists")?></h3>
						<ul id="artist-similar"></ul>
					</div>

				</div>

			</div>

		</div>
		
	</div>		
	
	<div id="grid-rightbottom" class="rsgrid">

		<div class="tab-panel">
			<ul>
				<li><a href="javascript:;" id="label-tab-library" class="ui-state-default ui-state-active"><img src="/images/library.png" alt="" /> <?=_("Library")?></a></li>
				<li><a href="javascript:;" id="label-tab-filebrowser" class="ui-state-default"><img src="/images/filebrowser.png" alt="" /> <?=_("File Browser")?></a></li>
				<li><a href="javascript:;" id="label-tab-log" class="ui-state-default"><img src="/images/log.png" alt="" /> <?=_("Log")?></a></li>
				<li style="display:none"><a href="javascript:;" id="label-tab-stats" class="ui-state-default"><img src="/images/statistics.png" alt="" /> <?=_("Statistics")?></a></li>
				<li><a href="javascript:;" id="label-tab-pref" class="ui-state-default"><img src="/images/settings.png" alt="" /> <?=_("Preferences")?></a></li>
			</ul>
		</div>


		<div id="tab-filebrowser" class="tab" style="display:none">

			<div id="lib-filebrowser-wrapper" class="radius">
				<ul id="lib-filebrowser">
					<li class="expanded">
						<div><span class="exp"></span><span class="name"><?=basename($this->_config['app.music_path'])?></span></div>
					</li>
				</ul>
				<div id="dragdrop-legend"><?=_("Drop your files here")?></div>
			</div>

			<div id="lib-filebrowser-files" class="radius">
				<select id="lib-filebrowser-files-list" name="files[]" multiple="multiple" size="20"></select>
			</div>
		</div>

		<div id="tab-library" class="tab">

			<div class="boxwrap flexbox flexv">

				<div id="lib-search-panel">

					<div class="fleft">

						<form method="get" id="search-query-form" action="" onsubmit="Controls.updateLibrary(); return false;">
							<button class="button ui-state-default" onclick="Controls.updateLibrary(true)" title="<?=_("Update Library")?>"><img src="/images/new/refresh.16x16.png" alt="" /> <?=_("Update Library")?></button>
							<!--button id="upload-button" onclick="j('#upload-dialog').dialog('open')" class="button ui-state-default" title="<?=_("Upload Files")?>"><img src="/images/upload.16x16.png" alt="" /> <?=_("Upload")?></button-->
							<button onclick="j('#randomfiles-dialog').dialog('open')" class="button ui-state-default" title="<?=_("Add random files")?>"><img src="/images/new/add.16x16.png" alt="" /> <?=_("Add random files")?></button>
							<input type="text" id="search-query" value="" placeholder="<?=_("Search library...")?>" size="40" class="textfield ui-state-default" />
							<input type="image" src="/images/search.16x16.png" value="" class="button ui-state-default" title="<?=_("Search")?>" />
							<button id="search-query-clear" onclick="Controls.clearSearch()" class="button ui-state-default" title="<?=_("Clear")?>"><img src="/images/cancel.16x16.ico" alt="" /></button>
							<img src="/images/ajax-loader1.gif" alt="" id="search-status" class="vmiddle" style="display:none" />
						</form>

					</div>

					<div id="lib-file-count" class="fright"><?=_("Displaying")?> <span></span> <?=_("files")?></div>

				</div>

				<div id="lib-filter-pane" class="flexbox flexh">

					<div class="flexbox flexv radius" style="margin-right:5px;">
						<div class="boxtitle"><strong><?=_("Genre")?></strong> (<span id="lib-genre-count">0</span> items)</div>
						<div class="flex flexwrap">
							<select id="lib-genre" size="6" onchange="Controls.updateLibrary(false, this)">
								<option value="" selected="selected"><?=_("ALL")?></option>
							</select>
						</div>
					</div>

					<div class="flexbox flexv radius" style="margin-right:5px;">
						<div class="boxtitle"><strong><?=_("Artist")?></strong> (<span id="lib-artist-count">0</span> items)</div>
						<div class="flex flexwrap">
							<select id="lib-artist" size="6" onchange="Controls.updateLibrary(false, this)">
								<option value="" selected="selected"><?=_("ALL")?></option>
							</select>
						</div>
					</div>

					<div class="flexbox flexv radius">
						<div class="boxtitle"><strong><?=_("Album")?></strong> (<span id="lib-album-count">0</span> items)</div>
						<div class="flex flexwrap">
							<select id="lib-album" size="6" onchange="Controls.updateLibrary(false, this)">
								<option value="" selected="selected"><?=_("ALL")?></option>
							</select>
						</div>
					</div>

				</div>

				<div id="lib-list-pane" class="radius">
					<div>
						<select id="lib-list" name="files[]" multiple="multiple"></select>
					</div>
				</div>

			</div>

		</div>

		<div id="tab-log" class="tab" style="display:none">
			<div id="log-table-scrollpane">
				<table id="log-table" class="tablegrid">
					<thead>
						<tr>
							<th style="width:70px"><?=_("User")?></th>
							<th style="width:60px"><?=_("IP")?></th>
							<th style="width:70px"><?=_("Time")?></th>
							<th><?=_("Action")?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>

		<div id="tab-stats" class="tab" style="display:none">
			<div id="mpd-stats"></div>
			<h3><?=_("Most played songs")?></h3>
			<div id="file-stats"></div>
		</div>

		<div id="tab-pref" class="tab" style="display:none">

			<table>
				<tr>
					<td>
						<?=_("User name")?>:
						<input type="text" name="username" id="username" maxlength="35" value="" class="textfield ui-state-default" />
					</td>
					<td>
						<?=_("Language")?>:
						<select name="pref_lang" id="pref_lang" onchange="Pref.setLang(this);" class="ui-state-default">
							<?=HTML::options(array(
								'es'	=> 'Español',
								'en'	=> 'English'
							), true, $this->_config['app.language'])?>
						</select>
					</td>
					<td>
						<?=HTML::checkbox('pref_shortcuts', 'pref_shortcuts', 1, _("Enable shortcuts"))?>
						<ul class="plain shortcuts-info">
							<li><strong>F</strong>: <?=_("Search library")?></li>
							<li><strong>R</strong>: <?=_("Add random files")?></li>
							<li><strong>F1</strong>: <?=_("Decrease volume by 5")?> </li>
							<li><strong>F2</strong>: <?=_("Increase volume by 5")?></li>
							<li><strong>F3</strong>: <?=_("Play/Pause")?></li>
						</ul>
					</td>
				</tr>
			</table>
			<div>
				<h3><?=_("The following music genres will be considered when adding random files:")?></h3>
				<div class="user-genres">
					<?
						$i = 0;
						foreach ($genres as $g) {
							echo '<label for="user-genre-' . $i . '"><input type="checkbox" name="user_genres[]" id="user-genre-' . $i .'" value="'.$g.'" /> <span>'.$g.'</span></label><br/>';
							$i++;
						}
					?>
				</div>
				<div>
					<button id="server-status-button" onclick="Controls.toggleServerStatus()" class="ui-state-default button"><?=_("Enable Server")?></button>
					<button id="save-settings-btn" onclick="Pref.save()" class="ui-state-default button" disabled="disabled"><?=_("Save")?></button>
				</div>
			</div>

		</div>

	</div>
	
</div>

<ul id="search-list-menu" class="popupmenu">
	<li onclick="Controls.add()"><?=_("Add selection")?></li>
	<li onclick="Controls.add(1)"><?=_("Add selection &amp; Play")?></li>
	<li onclick="Controls.selectAll(); Controls.add()"><?=_("Add all")?></li>
	<li onclick="Controls.selectAll(); Controls.add(1)"><?=_("Add all &amp; Play")?></li>
</ul>

<ul id="filebrowser-menu" class="popupmenu">
	<li onclick="FileBrowser.addFolder()"><?=_("Add folder recursively")?></li>
	<li onclick="FileBrowser.addFolder(1)"><?=_("Add folder recursively &amp; Play")?></li>
	<li onclick="FileBrowser.refresh()"><?=_("Refresh")?></li>
	<li onclick="j('#create-folder-dialog').dialog('open')" class="writeopt"><?=_("Create folder")?></li>
</ul>

<ul id="filebrowser-list-menu" class="popupmenu">
	<li onclick="FileBrowser.add()"><?=_("Add selection")?></li>
	<li onclick="FileBrowser.add(1)"><?=_("Add selection &amp; Play")?></li>
	<li onclick="FileBrowser.selectAll(); FileBrowser.add()"><?=_("Add all")?></li>
	<li onclick="FileBrowser.selectAll(); FileBrowser.add(1)"><?=_("Add all &amp; Play")?></li>
	<li class="single" onclick="FileBrowser.localPlay()"><?=_("Local Play")?></li>
	<li class="single" onclick="FileBrowser.download()"><?=_("Download File")?></li>
</ul>

<div id="save-dialog" style="display:none">
	<form method="get" action="" onsubmit="return false;">
		<div>
			<?=_("Enter playlist name")?>:<br />
			<input type="text" id="playlist-name" value="" class="textfield ui-state-default" style="margin-top:3px;width:95%" />
		</div>
	</form>
</div>

<div id="upload-dialog" style="display:none">
	<form method="get" action="" onsubmit="return false;" id="upload-form">
		<!--p style="margin-bottom:5px;font-size:1.5em;"><?=_("Drag & Drop files on any folder within the file browser.")?></p>
		<div id="upload-directory-label" style="margin-bottom:5px;"><?=_("Selected upload directory: ")?>&nbsp;<strong>/</strong></div>
		<div>
			<?=_("You can select multiple files or a directory")?>:<br />
			<input type="file" multiple="multiple" directory="directory" id="files-input" name="files[]" class="textfield ui-state-default" style="margin-top:3px;width:95%" />
		</div-->
		<div id="upload-progressbar" style="display:none"></div>
		<ul id="upload-file-list"></ul>
	</form>
</div>

<div id="randomfiles-dialog" style="display:none">
	<table>
		<tr>
			<th><?=_("How many files?")?></th>
			<td><input type="text" id="randomfiles-count" value="10" size="3" maxlength="3" class="textfield ui-state-default" /></td>
		</tr>
		<tr>
			<th><?=_("Genres")?>
				<div style="margin-top:20px"><input type="checkbox" id="randomfiles-checkall" value="1" checked="checked" class="vmiddle" /> <label for="randomfiles-checkall"><?=_("Check all")?></label></div>
			</th>
			<td>
				<div class="genres">
					<?
						$i = 0;
						foreach ($genres as $g) {
							echo '<label for="randomfiles-genre-' . $i . '"><input type="checkbox" name="randomfiles_genres[]" id="randomfiles-genre-' . $i .'" value="'.$g.'" /> <span>'.$g.'</span></label><br/>';
							$i++;
						}
					?>
				</div>
			</td>
		</tr>
	</table>
</div>

<div id="add-shoutcast-dialog" style="display:none">
	<form method="get" action="" onsubmit="return false;">
		<div>
			<?=_("Name")?>:<br />
			<input type="text" id="shoutcast-name" value="" class="textfield ui-state-default" style="margin-top:3px;width:95%" />
		</div>
		<div>
			<?=_("URL")?>:<br />
			<input type="text" id="shoutcast-url" value="" class="textfield ui-state-default" style="margin-top:3px;width:95%" />
		</div>
	</form>
</div>

<div id="create-folder-dialog" style="display:none">
	<form method="get" action="" onsubmit="return false;">
		<div>
			<?=_("Name")?>:<br />
			<input type="text" id="folder-name" value="" class="textfield ui-state-default" style="margin-top:3px;width:95%" />
		</div>
	</form>
</div>

<!--div id="xdialog" style="display:none">
	<div class="xdialog-header"></div>
	<div class="xdialog-content"></div>
	<div class="xdialog-footer"></div>
</div-->

<audio id="userplayer" autobuffer></audio>