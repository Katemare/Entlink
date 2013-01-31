<?
$start=time();
$start_m=microtime();
include('def.php');

$poke=EntityFactory::create_blank(1, 'pokemon');
$context=new Context('preprocess');
$context->root=$poke;
echo '+++'.htmlspecialchars($poke->display($context));

echo '<br><br>';
EntityRetriever::retrieve();
debug('<hr>');
$context->purpose='edit';
echo '+++'.htmlspecialchars($poke->display($context));

$end=time();
$end_m=microtime();
echo '<br><br>';
debug( 'time: '. round(( ($end+$end_m) - ($start+$start_m) ), 3).'s');
?>