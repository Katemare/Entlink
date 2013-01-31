<?

abstract class EntityStorage
{
	public $owner=null;
	
	abstract public function req_link($link, $context=null, $args='');
	
	abstract public function get_linked($link, $context);
	
	abstract public function receive($tables, $context, $args);
	
	public function analyzeData() { }
}
?>