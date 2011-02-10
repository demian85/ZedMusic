<?php

class HomeController extends Controller
{
	private function _filterGenre($genre) {
		return preg_match('#^[a-z]+\w+$#i', $genre)
				&& !in_array(strtolower($genre), $this->config['app.invalid_genres']);
	}
	
	private function _setupDesktopView($view) {
		$style = 'dark-hive';		
		$view->addCSS("/styles/jquery/$style/jquery-ui-1.8.css", '', 'theme-css');		
		$view->addCSS("/styles/global.css");
		$view->addCSS("/styles/desktop.css");
		$view->addCSS("/styles/{$style}.css", '', 'theme-css2');
		$view->addJS('/scripts/global.js');
		$view->addJS('/scripts/FileBrowser.js');
		$view->addJS('/scripts/jquery/jquery-ui-1.8.min.js');			
		$view->addJS('/scripts/Uploader.js');
		$view->addJS('/scripts/socket.io/socket.io.min.js');
		$view->addJS('/scripts/desktop.js');
		
		return $view;
	}

	private function _setupMobileView($view) {
		$style = 'dark-hive';
		$view->addCSS("/styles/global.css");
		$view->addCSS("/styles/mobile.css");
		$view->addCSS("/styles/{$style}-mobile.css", '', 'theme-css2');
		$view->addJS('/scripts/global.js');
		$view->addJS('/scripts/FileBrowser.js');
		$view->addJS('/scripts/mobile.js');
		$view->addMeta('viewport', 'user-scalable=no, width=device-width');

		return $view;
	}

	private function _getView() {
		$tmp = $this->request->getSubdomainLabels();
		$isMobile = (count($tmp) > 0 && $tmp[0] == 'm');
		$tpl = $isMobile ? 'home-mobile' : 'home';

		$view = new DocumentView($tpl, 'Zedmusic');
		$view->setJSVar('msg', array(
			'play'				=> _('Play'),
			'playing'			=> _('Playing'),
			'stopped'			=> _('Stopped'),
			'pause'				=> _('Pause'),
			'paused'			=> _('Paused'),
			'remove'			=> _("Remove"),
			'load'				=> _("Load"),
			'all'				=> _("All"),
			'save'				=> _("Save"),
			'add'				=> _("Add"),
			'add_and_play'		=> _('Add & Play'),
			'cancel'			=> _("Cancel"),
			'file'				=> _("File"),
			'title'				=> _("Title"),
			'artist'			=> _("Artist"),
			'album'				=> _("Album"),
			'duration'			=> _("Duration"),
			'invalid_name'		=> _("Invalid file name"),
			'save_playlist'		=> _("Save playlist"),
			'load_playlist'		=> _("Load playlist..."),
			'read_more'			=> _("Read more..."),
			'read_less'			=> _("Read less..."),
			'no_artist_info'	=> _("No artist biography found"),
			'upload_files'		=> _("Upload Files"),
			'file_too_big'		=> _("File is too large"),
			'upload'			=> _("Upload"),
			'files_not_ready'	=> _("Files are not ready yet! Please wait..."),
			'_select_files'		=> _("Please select files to upload"),
			'add_random_files'	=> _("Add random files"),
			'confirm_remove_list'	=> _("Are you sure?"),
			'confirm_clear'			=> _("Are you sure?"),
			'add_shoutcast'		=> _("Add Shoutcast"),
			'stop_server'		=> _('Stop Server'),
			'enable_server'		=> _('Enable Server'),
			'upload_not_supported'	=> _("File upload is not supported in your browser!"),
			'create_folder'		=> _("Create folder")
		));
		$lang = $this->config->get('app.language');
		$view->setJSVar('LASTFM_WS', 'http://ws.audioscrobbler.com/2.0/?api_key=de6627f2a83f5adfa3f2cc3f382557d5&format=json&lang=' . $lang . '&callback=?');
		$view->setJSVar('LANG', $lang);
		$view->setJSVar('IS_MOBILE', $isMobile);
		$view->setJSVar('SEARCH_QUERY_PLACEHOLDER', _("Search library..."));

		if ($isMobile) $this->_setupMobileView($view);
		else $this->_setupDesktopView($view);

		return $view;
	}

	function __construct() {
		parent::__construct();
	}
	
	public function defaultAction() {

		$view = $this->_getView();
		
		$mpd = new MPDController();
		$genres = $mpd->tagList('genre');
		$view->genres = array_filter($genres, array($this, '_filterGenre'));

		echo $view;
	}
}
?>