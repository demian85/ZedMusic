<?php

import("util.Response");
import('io.FileManager');

class CtrlController extends Controller {

	private $controller;

	/**
	 * Get user name.
	 * @return string
	 */
	private function _getUser() {
		return isset($_GET['user']) ? $_GET['user'] : '';
	}

	private function _speech($text, $lang = '') {
		$text = escapeshellcmd($text);
		exec('echo "'.$text.'" | text2wave -scale 10 -o ' . SPEECH_TEMP_FILE);
		exec("mplayer -ao sdl " . SPEECH_TEMP_FILE);
	}
	private function _getServerStatus() {
		exec('ps aux | grep zedmusic_server', $output);
		return count($output) > 2 ? 1 : 0;
	}




	function __construct() {
		parent::__construct('update');
		$this->controller = new MPDController($this->_getUser());
	}

	function serverAction($status = 0) {
		$response = array();

		if ($status == 1 && $this->_getServerStatus() == 0) {
			// start process
			exec('php ' . APPLICATION_DIR . '/scripts/zedmusic_server.php > /dev/null 2>&1 & echo $!', $output);
			$pid = $output[0];
			file_put_contents(APPLICATION_DIR . '/scripts/zedmusic_server_pid.txt', $pid);
			$response['status'] = 1;
		}
		else {
			// kill process
			exec('kill -9 ' . (int)file_get_contents(APPLICATION_DIR . '/scripts/zedmusic_server_pid.txt'));
			$response['status'] = 0;
		}
		
		Response::json($response);
	}

	function updateAction() {
		$this->controller->reportStatus();
		$status = array_merge($this->controller->getStatus(), array(
			'server_status' => $this->_getServerStatus()
		));
		Response::json($status);
	}

	function lyricsAction() {
		$artist = isset($_GET['artist']) ? $_GET['artist'] : '';
		$title = isset($_GET['title']) ? $_GET['title'] : '';
		$response = array();

		if (empty($artist) || empty($title)) {
			$response['error'] = _("Unable to find lyrics for current song.");
		}

		$wsURI = "http://api.chartlyrics.com/apiv1.asmx/SearchLyricDirect?artist=" . urlencode($artist) . "&song=" . urlencode($title);
		$xml = @simplexml_load_file($wsURI);

		if (!$xml || !isset($xml->Lyric) || empty($xml->Lyric)) {
			$response['error'] = sprintf(_("Unable to find lyrics for <em>“%s”</em>"), htmlspecialchars($title));
		}
		else {
			$response['title'] = sprintf(_("Lyrics for <em>“%s”</em>"), htmlspecialchars($title));
			$response['lyrics'] = nl2br(htmlspecialchars((string)$xml->Lyric));
		}
		
		Response::json($response);
	}

	function playlistsAction() {
		Response::json(array(
			'playlists'	=> $this->controller->getPlaylists(),
			'shoutcast'	=> $this->controller->getShoutcasts()
		));
	}

	function statsAction() {
		$stats = $this->controller->getStatistics();
		
		$fileStats = new View('file_stats');
		$fileStats->files = $stats['files'];
		
		Response::json(array(
			'stats'	=> array(
				'mpd'	=> str_replace("\n", "<br />", $stats['mpd']),
				'files'	=> $fileStats->render()
			)
		));
	}

	function updateLibraryAction() {
		$input = new DataInput($_GET);
		$input->init(array('genre', 'artist', 'album', 'q'), 'string', '', 'trim');
		$input->init(array('updatempd','itemlimit'), 'int');

		if ($input['updatempd']) {
			$this->controller->exec("update");
		}

		$itemLimit = ($input['itemlimit'] > 0 && $input['itemlimit'] < 1000) ? $input['itemlimit'] : 1000;

		$response = array(
			'genre'		=> $this->controller->tagList('genre', array('any' => $input['q']), $itemLimit),
			'artist'	=> $this->controller->tagList('artist', array('genre' => $input['genre'], 'any' => $input['q']), $itemLimit),
			'album'		=> $this->controller->tagList('album', array('genre' => $input['genre'], 'artist' => $input['artist'], 'any' => $input['q']), $itemLimit),
			'list'		=> $this->controller->getList(array(
								'genre' => $input['genre'],
								'artist' => $input['artist'],
								'album' => $input['album'],
								'any' => $input['q']
							), $itemLimit, false)
			);

		Response::json($response);
	}

	public function execAction($action = '') {
		$response = array();

		if (!$action) {
			Response::json(array('error' => _("Invalid action")));
			exit;
		}

		$config = $this->controller->getConfig();
		$log = '';

		switch ($action) {
			case 'play':
				$index = isset($_REQUEST['index']) ? abs((int)$_REQUEST['index']) : 1;
				$this->controller->play($index);
				$log .= "Play song #$index";
				break;
			case 'stop':
				$this->controller->stop();
				$log .= "Stop";
				break;
			case 'volume':
				$value = isset($_REQUEST['value']) ? abs((int)$_REQUEST['value']) : 0;
				$this->controller->setVolume($value);
				$log .= "Set volume ($value%)";
				break;
			case 'seek':
				$value = isset($_REQUEST['value']) ? abs((int)$_REQUEST['value']) : 0;
				$this->controller->seek($value);
				$log .= "Seek ($value%)";
				break;
			case 'crossfade':
				$value = isset($_REQUEST['value']) ? abs((int)$_REQUEST['value']) : 0;
				$this->controller->setCrossfade($value);
				$log .= "Crossfade $value";
				break;
			case 'add':
				$files = isset($_REQUEST['files']) && is_array($_REQUEST['files']) ? $_REQUEST['files'] : array();
				$play = isset($_REQUEST['play']) ? (int)$_REQUEST['play'] : 0;
				$this->controller->add($files, $play);
				$log .= "Added ".count($files)." file(s)";
				break;
			case 'addfolder':
				$path = isset($_REQUEST['path']) ? $_REQUEST['path'] : null;
				$play = isset($_REQUEST['play']) ? (int)$_REQUEST['play'] : 0;
				$this->controller->addFolder($path, true, $play);
				$log .= "Added folder '$path'";
				break;
			case 'del':
				$index = isset($_REQUEST['index']) ? abs((int)$_REQUEST['index']) : 0;
				$this->controller->delete($index);
				$log .= "Deleted song #$index";
				break;
			case 'move':
				$from = isset($_REQUEST['from']) ? abs((int)$_REQUEST['from']) : 0;
				$to = isset($_REQUEST['to']) ? abs((int)$_REQUEST['to']) : 0;
				$this->controller->move($from, $to);
				$log .= "Move from #$from to #$to";
				break;
			// load playlist
			case 'load':
			case 'loadplay':
				$file = isset($_REQUEST['file']) ? $_REQUEST['file'] : null;
				if (!$file) {
					Response::json(array('error' => _("Invalid file name")));
					exit;
				}
				$this->controller->loadPlaylist($file, $action == 'loadplay');
				$log .= "Load playlist \"$file\"";
				break;
			// save playlist
			case 'save':
				$file = isset($_REQUEST['file']) ? $_REQUEST['file'] : null;
				if (!$file) {
					Response::json(array('error' => _("Invalid file name")));
					exit;
				}
				$this->controller->savePlaylist($file);
				$log .= "Saved playlist \"$file\"";
				break;
			// delete playlist
			case 'rm':
				$file = isset($_REQUEST['file']) ? $_REQUEST['file'] : null;
				if (!$file) {
					Response::json(array('error' => _("Invalid file name")));
					exit;
				}
				$this->controller->deletePlaylist($file);
				$log .= "Removed playlist \"$file\"";
				break;
			case 'clear':
				$length = $this->controller->getPlaylistLength();
				if ($length < $config['playlist.clear_min_length']) {
					Response::json(array('error' => sprintf(_("This operation is not allowed if the current playlist does not have at least %d songs."), $config['playlist.clear_min_length'])));
					exit;
				}
				if ((float)$config['playlist.clear_percent'] == 100) $this->controller->exec('clear');
				else $this->controller->deleteRange(1, floor($length * (float)$config['playlist.clear_percent']/100));
				$log .= "Clear";
				break;
			case 'crop':
				$length = $this->controller->getPlaylistLength();
				if ($length < $config['playlist.clear_min_length']) {
					Response::json(array('error' => sprintf(_("This operation is not allowed if the current playlist does not have at least %d songs."), $config['playlist.clear_min_length'])));
					exit;
				}
				$this->controller->exec('crop');
				$log .= "Crop";
				break;
			/*case 'bg_sound':
				$file = stripslashes(@$_REQUEST['file']);
				$this->_playBGSound($file);
				$log .= "BG sound \"$file\"";
				break;
			case 'get_sounds':
				$sounds = array();
				$tmpDir = @opendir(SOUND_PATH);
				while (($file = @readdir($tmpDir)) !== false) {
					if (is_dir($file) || !in_array(strtolower(substr($file, strrpos($file, '.')+1)), array('mp3','wav'))) continue;
					$sounds[] = $file;
				}
				@closedir($tmpDir);
				echo @implode("\n", $sounds);
				break;*/
			case 'vote':
				$song = trim(@$_REQUEST['song']);
				$log .= "Negative vote for \"$song\"";
				$this->controller->vote($song);
				break;
			case 'report_status':
				$this->controller->reportStatus();
				break;
			case 'speech':
				$this->_speech(@$_REQUEST['value'], @$_REQUEST['lang']);
				break;
			default:
				$this->controller->exec($action);
				$log .= ucfirst($action);
		}

		if (!in_array($action, array('update_status','playlist','stats','search',
										'get_sounds','update','report_status','vote'))) {
			$this->controller->log($log);
		}

		Response::json($response);
	}

	function uploadAction() {		
		$basePath = isset($_GET['dir']) ? '/' . rtrim($_GET['dir'], '/') : '';
		$encoding = isset($_GET['enc']) ? $_GET['enc'] : 'binary';
		try {
			$files = FileManager::upload('files', $this->config['app.music_path'] . $basePath, null, false, 0777);
			if ($files === null) {
				Response::json(array('error' => _("Invalid file(s)")));
			}
			else if ($encoding == 'base64') {
				foreach ($files as $f) {
					file_put_contents($f, base64_decode(file_get_contents($f)));
				}
			}
			$this->controller->exec('update');
		} catch (FileUploadException $ex) {
			Response::json(array('error' => _("An error occured uploading the file(s)")));
		}
	}

	function browseAction($type = 'files') {
		$parent = isset($_GET['folder']) ? $_GET['folder'] : '';
		$iterator = new DirectoryIterator($this->config['app.music_path'] . '/' . $parent);
		$response = array(
			'files' => null,
			'folders' => null
		);

		if ($type == 'files' || $type == 'all') {
			$files = array();
			foreach ($iterator as $file) {
				if ($file->isDot() || strpos($file->getBasename(), '.') === 0
						|| !$file->isReadable()) continue;
				if (!$file->isDir() && preg_match('#\.(mp3|wmv|wma|ogg)$#i', strtolower($file->getFileName()))) {
					$files[] = $file->getFileName();
				}
			}

			sort($files);

			$response['files'] = $files;
		}
		if ($type == 'folders' || $type == 'all') {
			$folders = array();
			foreach ($iterator as $file) {
				if ($file->isDot() || strpos($file->getBasename(), '.') === 0
						|| !$file->isReadable()) continue;
				if ($file->isDir()) {
					$folders[] = array(
						'name' => $file->getFileName(),
						'writable' => $file->isWritable()
					);
				}
			}

			usort($folders, function($a, $b) {
				if (strtolower($a['name']) == strtolower($b['name'])) return 0;
				return (strtolower($a['name']) < strtolower($b['name'])) ? -1 : 1;
			});

			$response['folders'] = $folders;
		}

		Response::json($response);
	}

	function createFolderAction() {
		$response = array();
		$parent = isset($_GET['parent']) ? preg_replace('#\./|\.\./#', '', $_GET['parent']) : '';
		$folderName = isset($_GET['name']) ? preg_replace('#\./|\.\./#', '', trim($_GET['name'])) : '';
		$path = $this->config['app.music_path'] . DIRECTORY_SEPARATOR . $parent . DIRECTORY_SEPARATOR . $folderName;
		if (!mkdir($path, 0777)) {
			$response['error'] = _("Unable to create folder. Check write permissions.");
		}
		Response::json($response);
	}

	function addRandomAction() {
		$genres = isset($_POST['genres']) ? $_POST['genres'] : 'rock';
		$count = isset($_POST['count']) ? (int)$_POST['count'] : 10;
		$play = isset($_POST['play']) ? (int)$_POST['play'] : 0;
		$files = $this->controller->getRandomFiles(explode(',', $genres), max($count, 1));
		$this->controller->add($files, $play);
		$this->controller->log("Added " . count($files) . " random files");
	}

	function deleteShoutcastAction($id = 0) {
		$this->controller->deleteShoutcast($id);
		Response::json(array());
	}

	function addShoutcastAction() {
		$name = isset($_POST['name']) ? $_POST['name'] : '';
		$url = isset($_POST['url']) ? $_POST['url'] : '';
		if (empty($name) || empty($url)) {
			Response::json(array(
				'error'	=> _("Invalid name or URL.")
			));
		}
		else {
			$this->controller->addShoutcast($name, $url);
			Response::json(array());
		}
	}

	function voteAction() {
		$this->controller->vote();
		Response::json(array());
	}

	function preferencesAction() {
		$ugenres = isset($_POST['genres']) ? $_POST['genres'] : array();
		$conf = $this->controller->getConfig();
		$genres = array_unique(array_merge(explode(',', $ugenres), explode(',', $conf['autoplay.genres'])));
		$this->controller->setConfig('autoplay.genres', implode(',', $genres));
		Response::json(array());
	}

	function getFileAction($download = 1) {
		$file = isset($_GET['file']) ? $_GET['file'] : '';
		$file = $this->config['app.music_path'] . '/' . preg_replace('#\./|\.\./#', '', ltrim($file, '/'));
		if (file_exists($file)) {
			header('content-type: ' . FileManager::getFileMime($file));
			if ($download) {
				Response::fileDownload($file);
			}
			else {
				readfile($file);
			}
		}
		else {
			Response::json(array('error' => _("Invalid file path.")));
		}
	}
}

?>
