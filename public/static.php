<?php
/**
 * ZedPlan OpenCorePHP Framework
 *
 * Copyright (c) 2005-2010, ZedPlan (http://www.zedplan.com)
 *
 *
 *
 * LICENSE
 *
 * This source file is subject to the GPL license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opencorephp.zedplan.com/license.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to opencorephp@zedplan.com so we can send you a copy immediately.
 *
 * @copyright	Copyright (c) 2005-2010, ZedPlan (http://www.zedplan.com)
 * @link	http://opencorephp.zedplan.com
 * @license	http://opencorephp.zedplan.com/license.txt     GPL License
 */

	// Main core initialization
	require_once(realpath(dirname(__FILE__) .  '/../application/bootstrap.php'));

	define('COMPRESS_CSS', 1);
	define('COMPRESS_JS', 2);

	// Init config
	$config = Config::getInstance();
	$config->init();
	
	$type = isset($_GET['type']) ? (string)$_GET['type'] : '';
	$compress = isset($_GET['compress']) ? (int)$_GET['compress'] : '';
	$files = isset($_GET['files']) ? explode(';', $_GET['files']) : array();
	
	function loadSource($path, $type, $compress = false) {
		$config = Config::getInstance();
		$request = Request::getInstance();
		$path = preg_replace('#\.\.?/#', '', $path);
		$filePath = realpath($path);

		if (!$filePath) {
			if (strpos($path, '/') === 0) {
				$filePath = $config['core.root'] . $path;
				if (!file_exists($filePath)) {
					if (DEBUG_MODE) {
						import('log.Logger');
						$_error = ($type == 'js') ? 'JS file not found: ' . $path
									: ($type == 'css' ? 'CSS file not found: ' . $path : '');
						Logger::getInstance()->error($_error);
					}
					$source = '';
				}
				else {
					$source = file_get_contents($filePath);
				}
			}
			else if (strpos($path, 'http') === 0) {
				$source = file_get_contents($path);
			}
		}
		else {
			$source = file_get_contents($filePath);
		}

		if ($compress) {
			$source = str_replace(array("\n", "\t", "\r"), '', $source);
		}
		
		return (string)$source;
	}
	
	$source = '';
	
	switch ($type) {
		case 'js':
			foreach ($files as $f) {
				$source .= "/*========== $f =========*/\n" . loadSource($f, 'js', $compress & COMPRESS_JS) . "\n\n";
			}
			header("Content-Type: text/javascript;");
			break;
		case 'css':
			foreach ($files as $f) {
				$source .= "/*========== $f =========*/\n" . loadSource($f, 'css', $compress & COMPRESS_CSS) . "\n\n";
			}
			header("Content-Type: text/css;");
			break;
	}

	if ($config['views.static_loader.expires'] > 0) {
		$maxAge = $config['views.static_loader.expires'];
		header("Cache-Control: max-age=$maxAge, public, must-revalidate");
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + $maxAge) . " GMT");
	}
	
	echo $source;
?>