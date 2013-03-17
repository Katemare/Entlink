<?

abstract class EntityStorage
{
	public $owner=null;
	
	abstract public function req_member($member, $context=null, $args='');
	
	abstract public function get_member($member_code, $context);
	
	abstract public function receive($tables, $context, $args);
}
?>