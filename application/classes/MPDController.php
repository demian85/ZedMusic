<?php
/**
 * Music Player Daemon Controller. Uses MPC
 *
 * Last.fm
 * Your API Key is de6627f2a83f5adfa3f2cc3f382557d5 and your secret  is 0e078b3df8da20c07a326993fbcde3e4
 *
 * @author demian
 * @version 1.0
 */
class MPDController {

   /* const LYRICS_WS_URI = 'http://lyrics.wikia.com/server.php?wsdl';
	const LASTFM_WS = 'http://ws.audioscrobbler.com/2.0/';
	const LASTFM_APIKEY = 'de6627f2a83f5adfa3f2cc3f382557d5';*/

	private $ip;
	private $username;
	private $_gconfig;
	private $_config;

	/**
	 * Get album image path for specified directory
	 * @param string $path mp3 file path
	 * @return string|null
	 */
	/*private function _getAlbumImage($path) {
		$dir = dirname($path);
		$folder = basename($dir);
		$fileName = substr(basename($path), 0, strrpos(basename($path), '.'));

		$d = @opendir($config['app.music_path'] . $dir);
		if (!$d) return null;

		$regFolder = preg_quote($folder, "#");
		$regFileName = preg_quote($fileName, "#");
		while (($file = @readdir($d)) !== false) {
			if (strpos($file, '.') === 0) continue;
			if (preg_match("#(?:$regFolder|$regFileName|folder|album|front|cover)\.(jpg|png|gif|bmp|jpeg)$#i", $file)) return "$dir/$file";
		}
		@closedir($d);
		return null;
	}*/

	private function _playBGSound($file, $volume = 70) {
		exec('mplayer -softvol -volume '.intval($volume).' "'.$file.'"');
	}

	/**
	 * Check vote percentage for current song and change song if necessary.
	 * @return void
	 */
	private function _checkVotes() {
		$currentSong = $this->getPlayingSong();
		if (!$currentSong) return;

		$voteCount = $this->getVoteCount($currentSong);
		$onlineUsers = $this->getOnlineUsers();

		// build percentaje
		$porc = ($voteCount*100) / (($onlineUsers == 0) ? 1 : $onlineUsers);

		// we need at least 3 users and 60% votes to change
		if ($onlineUsers >= $this->_config['votes.min_users']
				&& $porc >=  $this->_config['votes.min_percentage']) {
			// next song!
			//$this->_playBGSound($this->_gconfig['app.sound_path'] . '/cambiame_la_musica.mp3');
			exec("mpc next");
			$this->log(round($porc)."% negative votes: next!");
		}
	}

	private function _stripSongNumber($song) {
		return preg_replace('#^ *>? *\d+\) *#', "", $song);
	}

	private function _orderList($a, $b) {
		$a = strtolower($a);
		$b = strtolower($b);
		if ($a == $b) return 0;
    	return ($a < $b) ? -1 : 1;
	}
	
	private function _parseFile($file) {
		$file = trim($file);
		if (preg_match('#^http://(www\.)?youtube\.com/watch\?v=#i', $file)) {
			exec(APPLICATION_DIR . '/scripts/youtube-dl -g ' . escapeshellarg($file), $output);
			return $output[0];
		}
		else {
			return $file;
		}
	}




	function __construct($username = '') {
		$this->ip = @$_SERVER['REMOTE_ADDR'];
		$this->username = $username;
		$this->_gconfig = Config::getInstance();
		$db = DB::getConnection();
		$this->_config = $db->query("SELECT conf_key, conf_value FROM Config")->fetchAllPairs();
	}

	/**
	 * Report online status and delete offline users.
	 * @return void
	 */
	function reportStatus() {
		$db = DB::getConnection();

		// delete offline users
		$sql = "DELETE FROM Online
				WHERE DATE_ADD(st_date, INTERVAL 2 MINUTE) < NOW()";
		$db->exec($sql);

		// update online status
		$username = $db->quote($this->username);
		$sql = "REPLACE INTO Online
				(st_username, st_ip, st_date) VALUES ('$username', '{$this->ip}', NOW())";
		$db->exec($sql);

		$this->_checkVotes();
	}

	/**
	 * Get logs as array.
	 * @return array
	 */
	function getLog() {
		$db = DB::getConnection();
		$sql = "SELECT user, ip, TIME(date) as fdate, action
				FROM Logs
				WHERE date > DATE_SUB(NOW(), INTERVAL 24 HOUR)
				ORDER BY date DESC
				LIMIT 100";
		$logs = $db->query($sql)->fetchAll();
		return $logs;
	}

	/**
	 * Database log.
	 *
	 * @param string $text
	 * @param string $user
	 */
	function log($text) {
		$db = DB::getConnection();
		$ip = @$_SERVER['REMOTE_ADDR'];
		$sql = "INSERT INTO Logs (
					user, ip, date, action
				) VALUES (
					'".$db->quote($this->username)."', '".$db->quote($ip)."',
					NOW(), '".$db->quote($text)."'
				)";
		$db->exec($sql);
	}

	/**
	 * Get current playing song formatted as "%artist% - %title%"
	 * @return string
	 */
	function getPlayingSong() {
		exec("mpc current", $songLabel);
		return implode('', $songLabel);
	}

	function getPlayingSongInfo() {
		exec("mpc --format \"%artist%\n%title%\n%file%\n%album%\" current", $songInfo);
		return array(
			'artist'	=> @$songInfo[0],
			'title'		=> @$songInfo[1],
			'filename'	=> @$songInfo[2],
			'album'		=> @$songInfo[3]
		);
	}

	function getPlaylists() {
		exec("mpc lsplaylists", $playlists);
		sort($playlists);
		return $playlists;
	}

	function getShoutcasts() {
		$db = DB::getConnection();
		$sql = "SELECT radio_id as id, radio_url as url, radio_name as name
				FROM Radios
				ORDER BY radio_name";
		return $db->query($sql)->fetchAll();
	}

	function deleteShoutcast($id) {
		$db = DB::getConnection();
		$sql = "DELETE FROM Radios
				WHERE radio_id = " . intval($id);
		$db->exec($sql);
	}

	function addShoutcast($name, $url) {
		$db = DB::getConnection();
		$sql = "INSERT INTO Radios
				SET radio_url = '".$db->quote($url)."',
				radio_name = '".$db->quote($name)."'";
		$db->exec($sql);
	}

	/**
	 * Get status
	 * @return array
	 */
	function getStatus() {
		exec("mpc", $status);

		exec("mpc crossfade", $output);
		preg_match("#^crossfade: *(\\d+)#i", implode("", $output), $matches);
		$crossfade = (int)$matches[1];

		$output = array();

		exec("mpc volume", $output);
		preg_match("#^volume: *([\\d-]+)%#i", implode("", $output), $matches);
		$volume = (int)$matches[1];

		$currentSong = $this->getPlayingSong();

		$lines = count($status);

		if ($lines > 2) {
			// currently playing/paused (3) / playing/paused and updating db (4)
			$infoLine = ($lines == 3) ? $status[2] : $status[3];

			// parse song info. ex:
			// [paused]  #23/28   2:19/5:18 (44%)
			preg_match('#^\s*\[(\w+)\]\s*\#(\d+)/\d+(?:.*?)\((\d+)%\)#i', $status[1], $matches);
			if (!empty($matches)) {
				$playerStatus = $matches[1];
				$progress = (int)$matches[3];
				$playingIndex = (int)$matches[2];
			}
			else {
				$playerStatus = 'stopped';
				$progress = 0;
				$playingIndex = -1;
			}
		}
		else {
			// stopped (1) / stopped and updating db (2)
			$playerStatus = 'stopped';
			$progress = 0;
			$infoLine = $status[0];
			$playingIndex = -1;
		}

		if ($playerStatus != 'stopped') {
			// build vote info
			$voteCount = $this->getVoteCount($currentSong);
			$onlineUsers = $this->getOnlineUsers();
			$userCount = ($onlineUsers == 0) ? 1 : $onlineUsers;
			if ($voteCount > 0) {
				$votesInfo = $voteCount . "/$onlineUsers (" . round(($voteCount*100) / $userCount) . "%)";
			}
			else {
				$votesInfo = ($voteCount == -1) ? "" : "0/$onlineUsers";
			}
		}
		else {
			$votesInfo = '';
		}

		// parse extra info
		$match = preg_match("#volume: *(n/a|\\d+%) *repeat: *(on|off) *random: *(on|off)(?: *single: *(on|off) *consume: *(on|off))?#", $infoLine, $matches);

		if ($playerStatus == 'stopped') {
			$friendlyStatus = _("Stopped");
		}
		else if ($playerStatus == 'playing') {
			$friendlyStatus = _("Playing") . ": " . $currentSong;
		}
		else {
			$friendlyStatus = _("Paused");
		}

		$response = array(
			'status'		=> $playerStatus,
			'volume'		=> $volume,
			'crossfade'		=> $crossfade,
			'repeat' 		=> $match && $matches[2] == 'on' ? true : false,
			'random' 		=> $match && $matches[3] == 'on' ? true : false,
			'single' 		=> $match && @$matches[4] == 'on' ? true : false,
			'consume' 		=> $match && @$matches[5] == 'on' ? true : false,
			'updating'		=> strpos(implode("", $status), "Updating DB") !== false,
			'playlist'		=> $this->getPlaylist(null),
			'current_song'	=> $currentSong,
			'song_info'		=> $this->getPlayingSongInfo(),
			'playing_index'	=> $playingIndex,
			'progress'		=> $progress,
			'votes_info'	=> $votesInfo,
			'log' 			=> $this->getLog(),
			'fstatus' 		=> $friendlyStatus
		);

		return $response;
	}

	/**
	 * Get current playing index
	 *
	 * @return int
	 */
	function getPlayingIndex() {
		exec("mpc", $status);
		
		$lines = count($status);
		$playingIndex = -1;
		
		if ($lines > 2) {
			preg_match("#(?:.*?)\\#(\\d+)/\\d+(?:.*?)\\((\\d+)%\\)#i", $status[1], $matches);
			if (!empty($matches)) {
				$playingIndex = (int)$matches[1];
			}
		}
		
		return $playingIndex;
	}

	function getPlaylist($format = '[[%artist%[ #[%album%#]] - ]%title%]|[%file%]&[ (%time%)]', $stripNumbers = false) {
		if (!$format) {
			exec("mpc --format \"///%artist%///%album%///%title%///%file%///%time%\" playlist", $list);
			if ($stripNumbers) {
				$list = array_map(array($this, '_stripSongNumber'), $list);
			}
			$items = array();
			$songIndex = 1;
			foreach ($list as $item) {
				$tmp = explode('///', $item);
				$items[] = array(
					'index'		=> $songIndex++, //(int)preg_replace("#[ \\)]+#", "", $tmp[0]),
					'artist'	=> $tmp[1],
					'album'		=> $tmp[2],
					'title'		=> $tmp[3],
					'file'		=> $tmp[4],
					'time'		=> $tmp[5],
				);
			}
			return $items;
		}
		else {
			exec("mpc --format \"$format\" playlist", $list);
			if ($stripNumbers) {
				$list = array_map(array($this, '_stripSongNumber'), $list);
			}
			return $list;
		}
	}

	function getPlaylistLength() {
		exec("mpc playlist", $list);
		return count($list);
	}

	/**
	 * Get online user count.
	 * @return int
	 */
	function getOnlineUsers() {
		$db = DB::getConnection();
		$sql = "SELECT COUNT(*) as c
				FROM Online";
		return $db->query($sql)->fetchObject()->c;
	}

	function tagList($tag, array $searchQuery = array(), $limit = 1000) {
		$params = array();
		foreach ($searchQuery as $q => $v) {
			if (empty($v)) continue;
			$params[] = escapeshellarg($q) . " " . escapeshellarg($v);
		}
		$output = array();
		$cmd = "mpc list " . escapeshellarg($tag) . (!empty($params) ? " " . implode(" ", $params) : "");
		exec($cmd, $output);
		$output = array_filter($output);
		sort($output);
		return $limit ? array_slice($output, 0, $limit) : $output;
	}

	/**
	 * List files searching by the specified parameters.
	 *
	 * @param array $searchQuery
	 * @param int $limit
	 * @param boolean $exactMatch
	 * @return string[]
	 */
	function getList(array $searchQuery = array(), $limit = 750, $exactMatch = false) {
		$params = array();
		foreach ($searchQuery as $q => $v) {
			if (empty($v)) continue;
			$params[] = escapeshellarg($q) . " " . escapeshellarg($v);
		}
		$cmd = $exactMatch ? "mpc find" : "mpc search";
		$cmd .= (!empty($params) ? " " . implode(" ", $params) : " any ''");
		exec($cmd, $output);
		sort($output);
		return $limit ? array_slice($output, 0, $limit) : $output;
	}

	/**
	 * Get random files for the specified genres.
	 *
	 * @param string|string[] $genre
	 * @param int $count how many files
	 * @return string[]
	 */
	function getRandomFiles($genre, $count = 1) {
		$tagList = array();
		foreach ((array)$genre as $g) {
			$tagList = array_merge($tagList, $this->getList(array('genre' => $g), 0, true));
		}
		$files = array();
		$keys = (array)array_rand($tagList, min($count, count($tagList)));
		foreach ($keys as $k) $files[] = $tagList[$k];
		return $files;
	}

	function getStatistics($rankLimit = 10) {
		$db = DB::getConnection();
		$sql = "SELECT * FROM MostPlayed
				ORDER BY file_playcount DESC
				LIMIT " . intval($rankLimit);
		$fileStats = $db->query($sql)->fetchAll();
		exec("mpc stats", $output);
		$stats = array(
			'mpd'	=> implode("\n", $output),
			'files'	=> $fileStats
		);
		
		return $stats;
	}

	function play($index) {
		$index = (int)$index;
		exec("mpc play $index");
	}

	function stop() {
		exec("mpc stop");
	}

	function setVolume($value) {
		$value = (int)$value;
		exec("mpc volume $value");
	}

	function seek($value) {
		$value = (int)$value;
		exec("mpc seek $value%");
	}

	function setCrossfade($value = 3) {
		$value = (int)$value;
		exec("mpc crossfade $value");
	}

	function addFolder($path, $recursive = true, $play = false) {
		$base = $this->_gconfig['app.music_path'] . DIRECTORY_SEPARATOR;
		$playIndex = $play ? $this->getPlaylistLength() + 1 : 0;
		$d = new DirectoryIterator($base . $path);
		foreach ($d as $file) {
			if ($file->isDot() || !$file->isReadable()) continue;
			$filePath = str_replace($base, "", $file->getPathname());
			if ($file->isDir() && $recursive) $this->addFolder($filePath, true, false);
			else if (!$file->isDir()) {
				exec('mpc add '.escapeshellarg($this->_parseFile($filePath)));
			}
		}
		if ($playIndex) $this->play($playIndex);
	}

	function add($files, $play = false) {
		$playIndex = $play ? $this->getPlaylistLength() + 1 : 0;
		foreach ((array)$files as $file) {
			exec('mpc add '.escapeshellarg($this->_parseFile($file)));
		}
		if ($playIndex) $this->play($playIndex);
	}

	function delete($index) {
		$index = (int)$index;
		exec("mpc del $index");
	}

	function deleteRange($from, $to) {
		for ($i = $from; $i <= $to; $i++) {
			$this->delete($i);
		}
	}

	function move($from, $to) {
		$from = (int)$from;
		$to = (int)$to;
		exec("mpc move $from $to");
	}

	function loadPlaylist($name, $play = false) {
		$playIndex = $play ? $this->getPlaylistLength() + 1 : 0;
		exec("mpc load ".escapeshellarg($name));
		if ($playIndex) $this->play($playIndex);
	}

	function savePlaylist($name) {
		exec("mpc save ".escapeshellarg($name));
	}

	function deletePlaylist($name) {
		exec("mpc rm ".escapeshellarg($name));
	}

	function exec($cmd) {
		exec("mpc $cmd", $output);
		return implode('', $output);
	}

	/**
	 * Get vote count for specified song.
	 * @return int
	 */
	function getVoteCount($songName) {
		$hash = md5($songName);
		$db = DB::getConnection();

		// check vote count
		$sql = "SELECT COUNT(*) as c
				FROM Votes
				WHERE vote_song = '$hash'";
		$count = $db->query($sql)->fetchObject()->c;

		return $count;
	}

	/**
	 * Vote and check votes.
	 *
	 * @param $songName string
	 * @return int vote true or false if user voted before
	 */
	function vote($songName = null) {
		if (!$songName) $songName = $this->getPlayingSong();
		$hash = md5($songName);
		$db = DB::getConnection();

		// delete votes older than 30 minutes
		$sql = "DELETE FROM Votes
				WHERE DATE_ADD(vote_date, INTERVAL {$this->_config['votes.duration']} MINUTE) < NOW()";
		$db->exec($sql);

		// check if user voted before
		$sql = "SELECT 1 FROM Votes
				WHERE vote_ip = '{$this->ip}'
				AND vote_song = '$hash'";
		$result = $db->query($sql);
		if ($result->rowCount() > 0) {
			return null;
		}

		$sql = "REPLACE INTO Votes
				(vote_ip, vote_date, vote_song)
				VALUES
				('{$this->ip}', NOW(), '$hash')";
		$result = $db->exec($sql);

		$this->_checkVotes();
	}
	
	function getConfig() {
		return $this->_config;
	}

	function setConfig($key, $value) {
		$db = DB::getConnection();
		$sql = "UPDATE Config
				SET conf_value = '".$db->quote($value)."'
				WHERE conf_key = '".$db->quote($key)."'";
		$db->exec($sql);
	}
}