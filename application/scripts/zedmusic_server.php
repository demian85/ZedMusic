<?
	$DIR = dirname(__FILE__);
	
	require_once $DIR . '/../bootstrap.php';
	
	//Config::$configDir = '../config/zedplan.com';
	
	$config = Config::getInstance();
	$config->init();
	
	import('db.DB', 'log.Logger');
	
	$controller = new MPDController();

	$currentSong = '';
	echo "\n";
	
	// check when current song changes
	while (true) {

		try {
			$db = DB::getConnection();
			$dbConfig = $db->query("SELECT conf_key, conf_value FROM Config")->fetchAllPairs();

			echo "Waiting for events...\n";

			exec('mpc idle', $output);

			if ($output[0] == 'player' || $output[0] == 'playlist') {
				echo "New Event: {$output[0]}\n";

				$info = $controller->getPlayingSongInfo();

				// update most played
				if ($info['filename'] && $output[0] == 'player' && $info['filename'] != $currentSong) {
					$currentSong = $info['filename'];
					$hash = md5($currentSong);
					$sql = "SELECT 1 FROM MostPlayed
							WHERE file_hash = '$hash'";
					if ($db->query($sql)->rowCount() == 0) {
						$sql = "INSERT INTO MostPlayed
								SET file_hash = '$hash',
								file_path = '".$db->quote($info['filename'])."',
								file_title = '".$db->quote($info['title'])."',
								file_artist = '".$db->quote($info['artist'])."',
								file_album = '".$db->quote($info['album'])."',
								file_playcount = 1";
						$db->exec($sql);
					}
					else {
						$sql = "UPDATE MostPlayed
								SET file_playcount = file_playcount + 1
								WHERE file_hash = '$hash'";
						$db->exec($sql);
					}
				}

				$playListLength = $controller->getPlaylistLength();

				// add random files
				if ($dbConfig['autoplay.enabled'] && $controller->getPlayingIndex() >= ($playListLength - 1)) {
					$randomFiles = $controller->getRandomFiles(explode(',', $dbConfig['autoplay.genres']), $dbConfig['autoplay.filecount']);
					$_files = array_diff($randomFiles, $controller->getPlaylist('%file%', true));
					$controller->add($_files);
					$_log = "Added random files";
					$controller->log($_log);
					echo $_log . ":\n\t" . implode("\n\t", $_files) . "\n";
				}

				$playListLength = $controller->getPlaylistLength();

				if ($playListLength > $dbConfig['playlist.automatic_max_length']) {
					$diff = ($playListLength - $dbConfig['playlist.automatic_max_length']);
					echo "Deleted $diff files.\n";
					$controller->deleteRange(1, $diff);
				}
			}

		} catch (Exception $ex) {
			try {
				Logger::getInstance()->exception($ex);
			} catch (Exception $ex2) { }

			echo $ex->getMessage();
		}
	}
?>