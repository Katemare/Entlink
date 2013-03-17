<?
class EntityBehavior_variant extends EntityBehavior
{
	static $def_formats=array(
		'input'=>array('behavior', 'select'),
	);
	public $formats=array();
	
	public function __construct()
	{
		parent::__construct();
		$this->formats=array_merge($this->formats, self::$def_formats);
	}
	
	public function select($args, $context)
	{
	}
	
	public function check_safe($make=false)
	{
		// FIX!
	}
	
	public function check_correct($make=false)
	{
		// FIX!
	}
	
}
?>