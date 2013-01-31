<?
function autoloader_main($class)
{
	$file='classes/'.$class.'.php';
	if (!file_exists($file)) return false;
	include($file);
}
function autoloader_plugins($class)
{
	global $entlink_classes;
	if (!array_key_exists($class, $entlink_classes)) return false;
	$file='classes/'.$entlink_classes[$class].'/'.$class.'.php';
	include($file);
}

spl_autoload_register('autoloader_main');
spl_autoload_register('autoloader_plugins');

$entlink_classes=array();
$plugins=array('image', 'pokemon');
foreach ($plugins as $plugin)
{
	$path=$_SERVER['DOCUMENT_ROOT'].'/v2/classes/'.$plugin.'/init.php';
	include($path);
	$class='Plugin_'.$plugin;
	$classes=$class::$classes;
	if (is_array($classes['types']))
	{
		foreach ($classes['types'] as $type)
		{
			$entlink_classes['EntityFactory_'.$type]=$plugin;
		}
	}
	if (is_array($classes['storage']))
	{
		foreach ($classes['storage'] as $storage)
		{
			$entlink_classes['EntityStorage_'.$storage]=$plugin;
		}
	}
	if (is_array($classes['behavior']))
	{
		foreach ($classes['behavior'] as $behavior)
		{
			$entlink_classes['EntityBehavior_'.$behavior]=$plugin;
		}
	}
}

/* $path=$_SERVER['DOCUMENT_ROOT'].'/v2/';
$classes = opendir($path.'classes');
while ($dir = readdir($classes))
{
	if (is_file($path.'classes/'.$dir))
	{
		//echo 'file '.$dir.'<br>';
		preg_match('/^(.+)\.php$/i', $dir, $m);
		$entlink_classes[$m[1]]='.';
	}
	elseif ( ($dir!='.') && ($dir!='..') && (is_dir($path.'classes/'.$dir)) )
	{
		// echo 'dir '.$dir.'<br>';	
		$subdir=opendir($path.'classes/'.$dir);
		while ($file = readdir($subdir))
		{
			if (is_file($path.'classes/'.$dir.'/'.$file))
			{
				preg_match('/^(.+)\.php$/i', $file, $m);
				$entlink_classes[$m[1]]=$dir;
			}
		}
	}
} */

//var_dump($entlink_classes);
?>