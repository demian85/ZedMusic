<div id="main" class="flexbox flexv">
	
	<div id="grid-top" class="flexbox flexv">

		<div id="controls">

			<div id="control-buttons">
				<img class="button ui-state-default" title="<?=_("Previous")?>" src="/images/new/previous.png" onclick="Controls.exec('prev')"/>
				<img class="button ui-state-default" id="toggle-button" title="<?=_("Play")?>" src="/images/new/play.png" onclick="Controls.exec('toggle')"/>
				<img class="button ui-state-default" title="<?=_("Stop")?>" src="/images/new/stop.png" onclick="Controls.exec('stop')"/>
				<img class="button ui-state-default" title="<?=_("Next")?>" src="/images/new/next.png" onclick="Controls.exec('next')"/>
				<button id="vote-button" class="button ui-state-default" title="<?=_("I don't like this!")?>" onclick="Controls.vote()" style="display:none"><img src="/images/thumbs_down.png" alt="" /> <span>0</span></button>
				<!--div style="margin-top:5px;">
					<img class="button ui-state-default" title="<?=_("Repeat mode")?>" src="/images/repeat-mode.png" id="repeat-button" onclick="Controls.exec('repeat')"/>
					<img class="button ui-state-default" title="<?=_("Random mode")?>" src="/images/random-mode.png" id="random-button" onclick="Controls.exec('random')"/>
				</div-->
			</div>
			
		</div>

		<div id="status-panel" class="flex">

			<!--div id="progress-slider-wrapper">
				<meter id="progress-slider" min="0" max="100" value="50"></meter>
				<div><span id="status"></span> <span id="progress-perc"></span></div>
			</div-->

			<!--div>
				<input type="range" value="0" min="0" max="100" id="volume-slider" /> Volume (<span id="volume-slider-label">0%</span>)
				<input type="range" value="0" min="0" max="5" id="crossfade-slider" /> Crossfade (<span id="crossfade-slider-label">0</span>)
			</div-->

			<div id="progress-slider" class="slider" style="display:none">
				<div id="progress-label"><span id="status"></span> <span id="progress-perc"></span></div>
			</div>

			<div class="flexbox flexh">
				<div class="flex">
					<div id="volume-slider" class="ui-state-default slider">
						Volume (<span id="volume-slider-label">0%</span>)
						<div class="slider-handle ui-state-active"></div>
					</div>
				</div>
				<div class="flex">
					<div id="crossfade-slider" class="ui-state-default slider">
						Crossfade (<span id="crossfade-slider-label">0</span>)
						<div class="slider-handle ui-state-active"></div>
					</div>
				</div>
			</div>

		</div>

	</div>

	<div class="tab-panel flex flexv">

		<div class="tab-panel-tabs">
			<ul>
				<li><a href="javascript:;" id="label-tab-playlist" class="ui-state-default ui-state-active"><img src="/images/playlist.png" alt="" /> (<span id="playlist-item-count">0</span>)</a></li>
				<li><a href="javascript:;" id="label-tab-library" class="ui-state-default"><img src="/images/library.png" alt="" /></a></li>
				<li><a href="javascript:;" id="label-tab-filebrowser" class="ui-state-default"><img src="/images/filebrowser.png" alt="" /></a></li>
				<li><a href="javascript:;" id="label-tab-pref" class="ui-state-default"><img src="/images/settings.png" alt="" /></a></li>
			</ul>
		</div>

		<div id="tab-playlist" class="tab">

			<div class="boxwrap flexbox flexv">

				<div id="playlist-panel-top">

					<div class="fleft">
						<button onclick="Controls.clearPlaylist()" class="button ui-state-default" title="<?=_("Clear List")?>"><img src="/images/cancel.16x16.ico" alt="" /></button>
						<button onclick="Controls.exec('crop')" class="button ui-state-default" title="<?=_("Crop")?>"><img src="/images/cut.16x16.ico" alt="" /></button>						
						<button onclick="Controls.exec('shuffle')" class="button ui-state-default" title="<?=_("Shuffle")?>"><img src="/images/shuffle.16x16.png" alt="" /></button>
						<form method="get" action="" onsubmit="Controls.playURL(); return false;">
							<input type="text" id="play-url" value="<?=_("Add URL...")?>" class="textfield ui-state-default" size="35" />
							<input type="submit" value="" style="display:none" />
							<img src="/images/ajax-loader1.gif" alt="" style="display:none" />
						</form>
					</div>

					<button onclick="GUI.showMenu(this, '#playlist-columns-menu', event)" class="fright button ui-state-default popup-trigger" title="<?=_("Columns")?>"><img src="/images/cols.png" alt="" /></button>

					<ul id="playlist-columns-menu" class="popupmenu" style="display:none">
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

				<div id="empty-playlist-msg" class="center" style="display:none"><?=_("Playlist is empty. Check the library and add some files!")?></div>

			</div>
		</div>

		<div id="tab-filebrowser" class="tab" style="display:none">

			<div id="lib-filebrowser-wrapper" class="radius">
				<ul id="lib-filebrowser">
					<li class="expanded">
						<div><span class="exp"></span><span class="name"><?=basename($this->_config['app.music_path'])?></span></div>
					</li>
				</ul>
			</div>

			<div id="lib-filebrowser-files" class="radius">
				<select id="lib-filebrowser-files-list" name="files[]" multiple="multiple" size="20"></select>
			</div>
		</div>

		<div id="tab-library" class="tab" style="display:none">

			<div class="boxwrap flexbox flexv">

				<div id="lib-search-panel">

					<div class="fleft">

						<form method="get" id="search-query-form" action="" onsubmit="Controls.updateLibrary(); return false;">
							<button class="button ui-state-default" onclick="Controls.updateLibrary(true)" title="<?=_("Update Library")?>"><img src="/images/new/refresh.16x16.png" alt="" /></button>
							<input type="text" id="search-query" value="<?=_("Search library...")?>" size="40" class="textfield ui-state-default" />
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
							<select id="lib-genre" size="6" onchange="Library.update(false, this)">
								<option value="" selected="selected"><?=_("ALL")?></option>
							</select>
						</div>
					</div>

					<div class="flexbox flexv radius" style="margin-right:5px;">
						<div class="boxtitle"><strong><?=_("Artist")?></strong> (<span id="lib-artist-count">0</span> items)</div>
						<div class="flex flexwrap">
							<select id="lib-artist" size="6" onchange="Library.update(false, this)">
								<option value="" selected="selected"><?=_("ALL")?></option>
							</select>
						</div>
					</div>

					<div class="flexbox flexv radius">
						<div class="boxtitle"><strong><?=_("Album")?></strong> (<span id="lib-album-count">0</span> items)</div>
						<div class="flex flexwrap">
							<select id="lib-album" size="6" onchange="Library.update(false, this)">
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

		<div id="tab-pref" class="tab" style="display:none">

			<table>
				<tr>
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

	<ul id="search-list-menu" class="popupmenu" style="display:none">
		<li onclick="Controls.add()"><?=_("Add selection")?></li>
		<li onclick="Controls.add(1)"><?=_("Add selection &amp; Play")?></li>
		<li onclick="Controls.selectAll(); Controls.add()"><?=_("Add all")?></li>
		<li onclick="Controls.selectAll(); Controls.add(1)"><?=_("Add all &amp; Play")?></li>
	</ul>

	<ul id="filebrowser-menu" class="popupmenu" style="display:none">
		<li onclick="FileBrowser.addFolder()"><?=_("Add folder recursively")?></li>
		<li onclick="FileBrowser.addFolder(1)"><?=_("Add folder recursively &amp; Play")?></li>
	</ul>

	<ul id="filebrowser-list-menu" class="popupmenu" style="display:none">
		<li onclick="FileBrowser.add()"><?=_("Add selection")?></li>
		<li onclick="FileBrowser.add(1)"><?=_("Add selection &amp; Play")?></li>
		<li onclick="FileBrowser.selectAll(); FileBrowser.add()"><?=_("Add all")?></li>
		<li onclick="FileBrowser.selectAll(); FileBrowser.add(1)"><?=_("Add all &amp; Play")?></li>
		<li class="single" onclick="FileBrowser.localPlay()"><?=_("Local Play")?></li>
		<li class="single" onclick="FileBrowser.download()"><?=_("Download File")?></li>
	</ul>

</div>