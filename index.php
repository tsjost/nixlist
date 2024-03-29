<?php
/**
 * nixList :: Directory Listing
 *
 * A script to list and browse files in directories
 *
 * @author tsjost <vcs@tsjo.st>
 * @copyright 2009 tsjost
 * @version 0.0.1
 */

/** CONFIG **/

$CONFIG['username']     = 'Guest'; // Leave blank to use the webserver's user
$CONFIG['hostname']     = ''; // Leave blank to use the domain name
$CONFIG['numColumns']   = 5;

$CONFIG['showLegend']   = false; // Display the legend for different colors
$CONFIG['showSelf']     = false; // Display this file in the directory listing
$CONFIG['showBackup']   = false; // Display backup files (~)

/** DO NOT EDIT BELOW IF YOU DO NOT KNOW EXACTLY WHAT YOU ARE DOING **/

function getLs($path, $file) {
	global $CONFIG;
	if ($CONFIG['showSelf'] == false and $path == './' and $file == basename(__FILE__)) {
		return false;
	}
	if ($CONFIG['showBackup'] == false and substr($file,-1) == '~') {
		return false;
	}
	if ($path == './' and $file == '..') {
		return false;
	}

	$linktitle = '';
	$class = '';

	if ( ! is_link($path . $file) or (is_link($path . $file) and file_exists($path . readlink($path . $file)))) {
		$permissions = substr(decoct(fileperms($path.$file)), -3);
		$permArr = str_split($permissions);
	}

	if (is_dir($path.$file)) {
		$class = 'dir';

		if ($file == '.') {
			$urlpath = substr($path, 2);

			if (empty($urlpath)) {
				$link = ROOTPATH;
			} else {
				$link = ROOTPATH .'?dir='. $urlpath;
			}
		} else if ($file == '..') {
			$dirs = explode('/', substr($path, 2, -1));
			if (count($dirs) == 1) {
				$link = ROOTPATH;
			} else {
				unset($dirs[count($dirs) - 1]);
				$link = ROOTPATH .'?dir='. implode('/', $dirs) .'/';
			}
		} else
			$link = '?dir=' . substr($path, 2) . $file . '/';
	} else if (is_link($path . $file)) {
		$link = $linktitle = readlink($path . $file);
		$link = $path . $link;

		if (file_exists($link)) {
			$class = 'symlink';
		} else {
			$class = 'symlinkbroken';
		}
	} else if (preg_match('/\.(jpg|jpeg|png|bmp|tif|gif)$/i', $file)) {
		$class = 'image';
		$link = $path . $file;
	} else if (preg_match('/\.(iso|tar|bz2|gz|[s]?7z|arj|cab|rar|tgz|zip)$/i', $file)) {
		$class = 'archive';
		$link = $path . $file;
	} else {
		foreach($permArr as $permission) {
			if ($permission & 1) {
				$class = 'executable';
				break;
			}
		}
		$link = $path . $file;
	}
	$return = '<a href="'. $link .'" title="'. $linktitle .'"><span class="'. $class .'">'. $file .'</span></a><br>';

	return $return;
}

$rootpath = 'http://'. $_SERVER['HTTP_HOST'] . substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/')) .'/';
define('ROOTPATH', $rootpath);
define('VERSION', '0.0.1');
define('CURRENTPATH', ROOTPATH . empty($_SERVER['QUERY_STRING']) ? '' : '?'. $_SERVER['QUERY_STRING']);
$username = empty($CONFIG['username']) ? trim(`whoami`) : $CONFIG['username'];
$hostname = empty($CONFIG['hostname']) ? $_SERVER['HTTP_HOST'] : $CONFIG['hostname'];

$path = '';
if ( ! empty($_GET['dir'])) {
	$path = $_GET['dir'];
	$path = str_replace('..', '', $path);
	$path = str_replace('./', '', $path);

	if (substr($path, 0, 1) == '.') {
		$path = substr($path, 1);
	}
	if (substr($path, 0, 1) == '/') {
		$path = substr($path, 1);
	}

	do {
		$path = str_replace('//','',$path,$count);
	} while($count);

	if (substr($path, -1) != '/') {
		$path = $path . '/';
	}
	if ($path == '/') {
		unset($path);
	}
	if ($path != $_GET['dir']) {
		header('HTTP/1.1 301 Moved Permanently');
		if (empty($path)) {
			header('Location: '. ROOTPATH);
		} else {
			header('Location: '. ROOTPATH .'?dir='. $path);
		}
		die();
	}
}
$path = './'. $path;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>nixList v<?=VERSION?></title>
	<style>
	body { background-color:#000; color:#FFF; font:11px Monospace; }
	#dirlist { float:left; margin:10px; }
	a { color:#FFF; }
	#dirlist a { text-decoration:none; }
	#dirlist span { padding:0 2px; }
	.dir { color:#729fcf; font-weight:bold; }
	.symlink { color:#34e2e2; font-weight:bold; }
	.symlinkbroken { color:#ef2929; font-weight:bold; background-color:#2e3436; }
	.image { color:#ad7fa8; font-weight:bold; }
	.archive { color:#ef2929; font-weight:bold; }
	.executable { color:#8ae234; font-weight:bold; }
	</style>
</head>
<body>

<p>
<?=$username?>@<?=$hostname?>:<?=substr($path,1)?>$ ./about<br>
/**<br>
 * nixList :: Directory Listing<br>
 *<br>
 * @author tsjost &lt;<a href="https://tsjo.st">tsjo.st</a>&gt;<br>
 * @version <?=VERSION?><br>
 * @link <a href="https://github.com/tsjost/nixlist">https://github.com/tsjost/nixlist</a><br>
 */
</p>
<p>
<?=$username?>@<?=$hostname?>:<?=substr($path, 1)?>$ ls
</p>
<div id="dirlist">
<?php
if (is_dir($path)) {
	$files = scandir($path);
	if ($CONFIG['numColumns'] > 0) {
		$perColumn = ceil( count($files) / $CONFIG['numColumns'] );
	} else {
		$perColumn = count($files);
	}
	$i = 0;
	foreach($files as $file) {
		if ($i >= $perColumn) {
			$i = 0;
			echo '</div><div id="dirlist">'."\n";
		}
		$list = getLs($path, $file);
		if ($list != false) {
			echo $list."\n";
			$i++;
		}
	}
} else {
	echo 'No such file or directory.';
}
?>
</div>

<?php if ($CONFIG['showLegend'] == true): ?>
<p style="clear:left;">
<?=$username?>@<?=$hostname?>:<?=substr($path,1)?>$ ./legend<br>
Normal file<br>
<span class="dir">Directory</span><br>
<span class="symlink">Symlink</span><br>
<span class="symlinkbroken">Broken symlink</span><br>
<span class="image">Image</span><br>
<span class="archive">Archive</span><br>
<span class="executable">Executable</span><br>
</p>
<?php endif ?>

</body>
</html>
