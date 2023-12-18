<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2010 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * On-the-fly thumbnail generator
 *
 * $URL$
 * $Id$
 */

 /**
 * @package e107
 * @subpackage core
 * @author secretr
 *
 *
 * On-the-fly thumbnail generator
 */

const e107_INIT = true;


function thumbExceptionHandler(Throwable $exception)
{
	http_response_code(500);
	echo "Fatal Thumbnail Error\n";
	error_log($exception->getMessage());
}

function thumbErrorHandler($errno, $errstr, $errfile, $errline)
{

	switch($errno)
	{
		case E_USER_ERROR:
			echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
			echo "  Fatal error on line $errline in file $errfile";
			echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
			echo "Aborting...<br />\n";
			thumbExceptionHandler(new Exception);
			exit(1);
			break;

		default:
	}

}

set_exception_handler('thumbExceptionHandler'); // disable to troubleshoot.
set_error_handler("thumbErrorHandler"); // disable to troubleshoot.

// error_reporting(0); // suppress all errors or image will be corrupted.



ini_set('gd.jpeg_ignore_warning', 1);
//require_once './e107_handlers/benchmark.php';
//$bench = new e_benchmark();
//$bench->start();

/**
 * Class e_thumbpage
 * @todo Simplify all this, e.g. e107::getInstance()->initMinimal($path_to_e107_config);
 */
class e_thumbpage
{

	function __construct()
	{

		$self = realpath(__DIR__);

		$e_ROOT = $self."/";

		if ((substr($e_ROOT,-1) !== '/') && (substr($e_ROOT,-1) !== '\\') )
		{
			$e_ROOT .= DIRECTORY_SEPARATOR;  // Should function correctly on both windows and Linux now.
		}

		define('e_ROOT', $e_ROOT);

		$mySQLdefaultdb = '';
		$HANDLERS_DIRECTORY = '';
		$mySQLprefix = '';

		// Config

		include($self.DIRECTORY_SEPARATOR.'e107_config.php');

		// support early include feature
		if(!empty($CLASS2_INCLUDE))
		{
			 require_once(realpath(__DIR__ .'/'.$CLASS2_INCLUDE));
		}


		ob_end_clean(); // Precaution - clearout utf-8 BOM or any other garbage in e107_config.php

		if(empty($HANDLERS_DIRECTORY))
		{
			$HANDLERS_DIRECTORY = 'e107_handlers/'; // quick fix for CLI Unit test.
		}

		$tmp = $self.DIRECTORY_SEPARATOR.$HANDLERS_DIRECTORY;

		//Core functions - now API independent
		require($tmp.DIRECTORY_SEPARATOR.'core_functions.php');
		//e107 class
		require($tmp.DIRECTORY_SEPARATOR.'e107_class.php');

		if(!class_exists('e107_config')) // old e107_config.php format.
		{
			$dirNames = ['ADMIN_DIRECTORY', 'FILES_DIRECTORY', 'IMAGES_DIRECTORY', 'THEMES_DIRECTORY', 'PLUGINS_DIRECTORY', 'HANDLERS_DIRECTORY', 'LANGUAGES_DIRECTORY', 'HELP_DIRECTORY', 'DOWNLOADS_DIRECTORY','UPLOADS_DIRECTORY','SYSTEM_DIRECTORY', 'MEDIA_DIRECTORY','CACHE_DIRECTORY','LOGS_DIRECTORY', 'CORE_DIRECTORY', 'WEB_DIRECTORY'];

			$e107_paths = [];
			foreach ($dirNames as $name)
			{
			    if (isset($$name))
			    {
			        $e107_paths[$name] = $$name;
			    }
			}

			$sql_info = array_combine(array_map(function($k) {
				return str_replace('mySQL', '', $k);
				}, array_keys($legacy_sql_info)),
		        $legacy_sql_info
			);
		}
		else // New e107_config.php format. v2.4+
		{
			$e107_paths = e107_config::paths();
			$sql_info = e107_config::database();
			$E107_CONFIG = e107_config::other() ?? [];
		}

		$e107 = e107::getInstance()->initCore($e107_paths, e_ROOT, $sql_info, varset($E107_CONFIG, array()));
		// basic Admin area detection - required for proper path parsing
		define('ADMIN', strpos(e_SELF, (e107::getFolder('admin')) != false || strpos(e_PAGE, 'admin') !== false));

		// Next function call maintains behavior identical to before; might not be needed
		//  See https://github.com/e107inc/e107/issues/3033
		$e107->set_urls_deferred();

		$pref = e107::getPref();


		require_once(e_HANDLER."e_thumbnail_class.php");

		$thm = new e_thumbnail;
		$thm->init($pref);

		if(!$thm->checkSrc())
		{
			die('Bad URL');
		}

		$thm->sendImage();
	}
}

new e_thumbpage;
// Check your e_LOG folder
//$bench->end()->logResult('thumb.php', $_GET['src'].' - no cache');
exit;


