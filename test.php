<?
include('def.php');
include('Entity.php');
$names=new Entity_translation();
$names->build();
$names->listFormat();
$dic=array('name_eng'=>'Meow', 'name_rus'=>'���', 'name_jap'=>'Nyan', 'name_ukr'=>'����');
$names->input($dic);
echo $names->display('input');

$lang='it';
echo '<br><br>������� '.$lang.': '.$names->translate($lang).'<br><br>';

$store=$names->store();
foreach ($store as $q)
{
	$query=EntityStorage::compose_query($q);
	echo $query.'<br>';
}
?>