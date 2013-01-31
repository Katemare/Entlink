<?
class EntityBehavior_link_list_item extends EntityBehavior_link
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