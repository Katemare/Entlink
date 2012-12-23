<?
include('Entity.php');
$names=new Entity_translation();
$names->build();
$names->listFormat();
$dic=array('name_eng'=>'Meow', 'name_rus'=>'Ìÿó', 'name_jap'=>'Nyan');
$names->input($dic);
echo $names->display('input');
?>