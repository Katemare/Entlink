<?
function autoloader($class)
{
	// STUB
	include($class.'.php');
}

spl_autoload_register('autoloader');